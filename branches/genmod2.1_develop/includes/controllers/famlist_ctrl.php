<?php
/**
 * Controller for the famlist Page
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2008 Genmod Development Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: famlist_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
class FamlistController extends ListController {
	
	public $classname = "FamlistController";	// Name of this class
	
	public function __construct() {
		
		parent::__construct();
		
	}

	public function __get($property) {
		switch($property) {
			default:
				return parent::__get($property);
				break;
		}
	}
	
	
	protected function GetPageTitle() {

		if (is_null($this->pagetitle)) {
			$this->pagetitle = GM_LANG_family_list." ".$this->addheader;
		}
		return $this->pagetitle;
	}
		
	public function GetAlphaFamSurnames($letter, $allgeds="no") {
	
		$search_letter = $this->GetNameString($letter);
		
		$namelist = array();
		$sql = "SELECT DISTINCT n_surname, count(DISTINCT if_fkey) as fams FROM ".TBLPREFIX."names, ".TBLPREFIX."individual_family WHERE n_key=if_pkey AND if_role='S' ";
		if (!empty($search_letter)) $sql .= "AND ".$search_letter;
		if ($allgeds != "yes") $sql .= " AND n_file = '".GedcomConfig::$GEDCOMID."' ";
		$sql .= "GROUP BY n_surname";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()) {
			$namelist[] = array("name"=>$row["n_surname"], "match"=>$row["fams"]);		
		}
		uasort($namelist, "ItemSort");
		return $namelist;
	}	
	
	/**
	 * Get Families with a given surname
	 *
	 * This function finds all of the individuals who have the given surname
	 * @param string $surname	The surname to search on
	 * @return array	$indilist array
	 */
	public function GetFams() {
			
		$search_letter = $this->GetNameString($this->alpha);
		$tfamlist = array();
	
		$sql = "SELECT DISTINCT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."names INNER JOIN ".TBLPREFIX."individual_family ON n_key=if_pkey INNER JOIN ".TBLPREFIX."families ON if_fkey=f_key WHERE if_role='S'";
		
		if ($this->surname != "") $sql .= " AND n_surname LIKE '".DbLayer::EscapeQuery($this->surname)."'";
		else if (!empty($search_letter)) $sql .= " AND ".$search_letter;
		if ($this->falpha != "") $sql .= " AND n_fletter='".$this->falpha."'";
		if (!GedcomConfig::$SHOW_MARRIED_NAMES) $sql .= " AND n_type!='C'";
		if ($this->allgeds != "yes") $sql .= " AND n_file = '".GedcomConfig::$GEDCOMID."' ";
		$sql .= "GROUP BY if_fkey";

		// The previous query works for all surnames, including @N.N.
		// But families with only one spouse (meaning the other is not known) are missing.
		// In that case, we also select families with one role Spouse.
		// What we exclude, is families where no spouses exist and which only consist of children.
		if ($this->surname == "@N.N.") {
			$sql .= " UNION SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."individual_family INNER JOIN ".TBLPREFIX."families on if_fkey=f_key WHERE if_role='S' ";
			if ($this->allgeds != "yes") $sql .= " AND if_file = '".GedcomConfig::$GEDCOMID."'";
			$sql .= " GROUP BY if_fkey HAVING count(if_fkey)=1";
		}

		$res = NewQuery($sql);
		$select = array();
		while($row = $res->FetchAssoc()){
			$fam = null;
			$fam =& Family::GetInstance($row["f_id"], $row, $row["f_file"]);
			if ($row["f_husb"] != "" && !Person::IsInstance(SplitKey($row["f_husb"], "id"), $row["f_file"])) $select[] = $row["f_husb"];
			if ($row["f_wife"] != "" && !Person::IsInstance(SplitKey($row["f_wife"], "id"), $row["f_file"])) $select[] = $row["f_wife"];
			$tfamlist[$row["f_key"]] = $fam;
		}
		$res->FreeResult();
	
		if (count($tfamlist) > 0) {
			if (count($select) > 0) {
				array_flip(array_flip($select));
				//print "Indi's selected for fams: ".count($select)."<br />";
				$selection = "'".implode("','", $select)."'";
				ListFunctions::GetIndilist($this->allgeds, $selection, false);
			}
		}
		return $tfamlist;
	}
	
	/**
	 * Print a list of family names
	 *
	 * A table with columns is printed from an array of names.
	 * A distinction is made between a list for the find page or a
	 * page listing.
	 *
	 * @todo		Add statistics for private and hidden links
	 * @author	Genmod Development Team
	 * @param		array	$personlist	The array with names to be printed
	 * @param		boolean	$print_all	Set to yes to print all individuals
	 */
	public function PrintFamilyList($familylist, $print_all=true) {
		global $TEXT_DIRECTION;
		
	
		// NOTE: The list is really long so divide it up again by the first letter of the first name
		if (((GedcomConfig::$ALPHA_INDEX_LISTS && count($familylist) > GedcomConfig::$ALPHA_INDEX_LISTS) || $this->falpha != "") && $print_all == true) {
			
			$firstalpha = $this->GetLetterBar(false);
			
			// NOTE: Print the second alpha letter list for the unknown names
			print "<div class=\"IndiFamLetterBar\">\n";
			PrintHelpLink("firstname_alpha_help", "qm");
			print GM_LANG_first_letter_fam_fname."<br />\n";
			$first = true;
			$pass = false;
			foreach($firstalpha as $index=>$letter) {
				if ($letter != "@") {
					if (!isset($fstartalpha) && ($this->falpha == "") || !in_array($this->falpha, $firstalpha)) {
						$fstartalpha = $letter;
						$this->falpha = $letter;
					}
					if (!$first) print " | ";
					$first = false;
					// NOTE: Print the link letter
					print "<a href=\"famlist.php?";
					// NOTE: only include the alpha letter when not showing the ALL list
					if ($this->show_all == "no") print "alpha=".urlencode($this->alpha)."&amp;";
					if ($this->surname_sublist == "yes" && !empty($this->surname)) print "surname=".$this->surname."&amp;";
					print "falpha=".urlencode($letter)."&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist;
					if ($this->allgeds == "yes") print "&amp;allgeds=yes";
					print "\">";
					// NOTE: Red color for the chosen letter otherwise simply print the letter
					if ($this->falpha == $letter && $this->show_all_firstnames == "no") print "<span class=\"Warning\">".htmlspecialchars($letter)."</span>";
					else print htmlspecialchars($letter);
					print "</a>\n";
				}
				if ($letter === "@") $pass = true;
			}
			// NOTE: Print the Unknown text on the letter bar
			if ($pass == true) {
				print " | ";
				print "<a href=\"famlist.php?";
				if ($this->surname_sublist == "yes" && !empty($this->surname)) print "surname=".$this->surname."&amp;";
				print "alpha=".urlencode($this->alpha)."&amp;falpha=@&amp;surname_sublist=".$this->surname_sublist."&amp;show_all=".$this->show_all;
				if ($this->allgeds == "yes") print "&amp;allgeds=yes";
				if (!empty($this->falpha) && $this->falpha == "@") print "\"><span class=\"Warning\">".PrintReady(GM_LANG_NN)."</span></a>\n";
				else print "\">".PrintReady(GM_LANG_NN)."</a>\n";
				$pass = false;
			}
			if (GedcomConfig::$LISTS_ALL) {
				print " | ";
				print "<a href=\"famlist.php?";
				// NOTE: only include the alpha letter when not showing the ALL list
				if ($this->show_all == "no") print "alpha=".urlencode($this->alpha)."&amp;";
				// NOTE: Include the surname if surnames are to be listed
				if ($this->allgeds == "yes") print "&amp;allgeds=yes&amp;";
				if ($this->surname_sublist == "yes" && !empty($this->surname)) print "surname=".urlencode($this->surname)."&amp;";
				if ($this->show_all_firstnames == "yes") print "show_all_firstnames=no&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist."\"><span class=\"Warning\">".GM_LANG_all."</span></a>\n";
				else print "show_all_firstnames=yes&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist."\">".GM_LANG_all."</a>\n";
			}
			print "</div>\n";
			
			if (isset($fstartalpha)) $this->falpha = $fstartalpha;
			// NOTE: Get only the names who start with the matching first letter. This is the case if no firstletter is chosen yet, and the list must be split because of the number of records found.
			if ($this->show_all_firstnames == "no") {
				foreach($familylist as $key => $family) {
					$lnames = $family->GetLetterNames($this->alpha, $this->falpha);
					if (count($lnames) == 0) unset($familylist[$key]);
				}
			}
			$this->PrintFamilyList($familylist, false);
		}
		else {
			$names = array();
			foreach($familylist as $gid => $fam) {
				// NOTE: make sure that favorites from other gedcoms are not shown
				if ($fam->gedcomid == GedcomConfig::$GEDCOMID || $this->allgeds == "yes") {
					$famnames = $fam->GetLetterNames($this->alpha, ($this->show_all_firstnames == "yes" ? "" : $this->falpha));
					foreach($famnames as $indexval => $name) {
							$names[] = array($name, $fam->key, $indexval);
					}
				}
			}
			uasort($names, "ItemSort");
			reset($names);
			$fam_private = array();
			$count = count($names);
			$i=0;
			print "<table class=\"ListTable IndiFamListTable\">";
			print "<tr><td class=\"ListTableHeader\" colspan=\"2\">";
			// List with a specific surname
			if ($this->surname_sublist == "yes" && $this->surname != "") {
				print PrintReady(str_replace("#surname#", NameFunctions::CheckNN($this->surname), GM_LANG_fams_with_surname));
			}
			else if ($this->show_all == "no") {
				// All persons with a surname with a specific letter 
				print PrintReady(str_replace("#letter#", ($this->alpha == "@" ? GM_LANG_NN : htmlspecialchars($this->alpha)), GM_LANG_fams_with_surname_letter));
			}
			else if ($this->show_all == "yes" && $this->show_all_firstnames == "yes") {
				print GM_LANG_family_list;
			}
			else {
				print PrintReady(str_replace("#letter#", ($this->falpha == "@" ? GM_LANG_PN : htmlspecialchars($this->falpha)), GM_LANG_fams_with_fistname_letter));
			}
			print "</td></tr>";
			print "<tr><td class=\"ListTableContent IndiFamListTableContent\"><ul>\n";
			foreach($names as $indexval => $namearray) {
				$family = $familylist[$namearray[1]];
				if (!$family->PrintListFamily(true, "", $namearray[0])) {
					$fam_private[$family->key] = true;
				}
				$i++;
				if ($i==ceil($count/2) && $count>8) print "</ul></td><td class=\"ListTableContent IndiFamListTableContent\"><ul>\n";			
			}
			print "</ul></td>\n";
			print "</tr><tr><td colspan=\"2\" class=\"ListTableColumnFooter\">";
			if (GedcomConfig::$SHOW_MARRIED_NAMES) print GM_LANG_total_names." ".count($names)."<br />\n";
			print GM_LANG_total_fams." ".count($familylist);
			if (count($fam_private) > 0) {
				print "  --  ".GM_LANG_hidden." ".count($fam_private);
				PrintHelpLink("privacy_error_help", "qm");
			}
			print "</td>\n";
			print "</tr></table>";
		}
	}
}
?>
