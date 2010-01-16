<?php
/**
 * Controller for the indilist Page
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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
class IndilistController extends ListController {
	
	public $classname = "IndilistController";	// Name of this class
	
	// Calculated values
	
	
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
			$this->pagetitle = GM_LANG_individual_list." ".$this->addheader;
		}
		return $this->pagetitle;
	}
		
	
	/**
	 * Get Individual surnames starting with a letter
	 *
	 * This function finds all of the individuals surnames who start with the given letter
	 *
	 * @param string $letter	The letter to search on
	 * @return array	$names array
	 */
	public function GetAlphaIndiNames() {
		global $GEDCOMID;
	
		$search_letter = $this->GetNameString($this->alpha);
		
		// NOTE: Select the records from the individual table
		$sql = "";
		$sql = "SELECT DISTINCT n_surname, n_letter, count(i_key) as i_count FROM ".TBLPREFIX."names LEFT JOIN ".TBLPREFIX."individuals ON n_key=i_key";
		$where = " WHERE ";
		if (!empty($search_letter)) {
			$sql .= $where.$search_letter;
			$where = " AND ";
		}
		if (!GedcomConfig::$SHOW_MARRIED_NAMES) {
			$sql .= $where."n_type NOT LIKE 'C'";
			$where = " AND ";
		}
		if ($this->allgeds != "yes") {
			$sql .= $where."n_file LIKE '".$GEDCOMID."'";
			$where = " AND ";
		}
		$sql .= " GROUP BY n_surname";

		$res = NewQuery($sql);
		$names = array();
		while($row = $res->FetchAssoc()) {
			$names[] = array("name" => $row["n_surname"], "match" =>$row["i_count"], "alpha" => $row["n_letter"]);
		}
		uasort($names, "ItemSort");
		return $names;
	}
	
	
	/**
	 * Get Individuals with a given surname or a given first surname letter or simply all indis
	 *
	 * This function finds all of the individuals who have the given surname
	 * @param string $surname	The surname to search on
	 * @return array	$indilist array
	 */
	public function GetIndis() {
		global $GEDCOMID;

		$search_letter = $this->GetNameString($this->alpha);
		
		$tindilist = array();
		$sql = "SELECT i_key, i_id, i_file, i_isdead, i_gedrec, n_letter, n_fletter, n_name, n_surname, n_nick, n_type FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key";
		
		if ($this->surname != "") $sql .= " AND n_surname LIKE '".DbLayer::EscapeQuery($this->surname)."'";
		else if (!empty($search_letter)) $sql .= " AND ".$search_letter;
		if ($this->falpha != "") $sql .= " AND n_fletter='".$this->falpha."'";
		if (!GedcomConfig::$SHOW_MARRIED_NAMES) $sql .= " AND n_type!='C'";
		
		if ($this->allgeds == "no") $sql .= " AND i_file='".$GEDCOMID."'";

		$sql .= " ORDER BY i_key, n_id"; // Don't remove this!
	
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			SwitchGedcom($row["i_file"]);
			$key = $row["i_key"];
			if (!isset($tindilist[$key])) {
				$tindilist[$key] = Person::GetInstance($row["i_id"], array("i_isdead" => $row["i_isdead"], "i_file" =>$row["i_file"], "i_id" => $row["i_id"], "i_gedrec" => $row["i_gedrec"]), $row["i_file"]);
			}
			$tindilist[$key]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
		}
		SwitchGedcom();
		$res->FreeResult();
		return $tindilist;
	}
	/**
	 * Print a list of individual names
	 *
	 * A table with columns is printed from an array of names.
	 * A distinction is made between a list for the find page or a
	 * page listing.
	 *
	 * @todo		Add statistics for private and hidden links
	 * @author	Genmod Development Team
	 * @param		array		$personlist	The array with names to be printed
	 * @param		boolean	$print_all	Set to yes to print all individuals
	 */
	public function PrintPersonList($personlist, $print_all=true) {
		global $TEXT_DIRECTION;
		global $GEDCOMID, $year;
	
		// NOTE: The list is really long so divide it up again by the first letter of the first name
		if (((GedcomConfig::$ALPHA_INDEX_LISTS && count($personlist) > GedcomConfig::$ALPHA_INDEX_LISTS) || $this->falpha != "") && $print_all == true) {
			
			$firstalpha = $this->GetLetterBar(false);
			
			// NOTE: Print the second alpha letter list for the unknown names
			print "<br /><div class=\"shade1 list_value center\" style=\"width:auto; margin: 0 auto 0 auto;\">\n";
			PrintHelpLink("firstname_alpha_help", "qm");
			print GM_LANG_first_letter_fname."<br />\n";
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
					if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
					if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
					// NOTE: only include the alpha letter when not showing the ALL list
					if ($this->show_all == "no") print "alpha=".urlencode($this->alpha)."&amp;";
					if ($this->surname_sublist == "yes" && !empty($this->surname)) print "surname=".$this->surname."&amp;";
					print "falpha=".urlencode($letter)."&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist;
					if ($this->allgeds == "yes") print "&amp;allgeds=yes";
					print "\">";
					// NOTE: Red color for the chosen letter otherwise simply print the letter
					if ($this->falpha == $letter && $this->show_all_firstnames == "no") print "<span class=\"warning\">".htmlspecialchars($letter)."</span>";
					else print htmlspecialchars($letter);
					print "</a>\n";
				}
				if ($letter === "@") $pass = true;
			}
			// NOTE: Print the Unknown text on the letter bar
			if ($pass == true) {
				print " | ";
				if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
				if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
				if ($this->surname_sublist == "yes" && !empty($this->surname)) print "surname=".$this->surname."&amp;";
				print "alpha=".urlencode($this->alpha)."&amp;falpha=@&amp;surname_sublist=".$this->surname_sublist."&amp;show_all=".$this->show_all;
				if ($this->allgeds == "yes") print "&amp;allgeds=yes";
				if (!empty($this->falpha) && $this->falpha == "@") print "\"><span class=\"warning\">".PrintReady(GM_LANG_NN)."</span></a>\n";
				else print "\">".PrintReady(GM_LANG_NN)."</a>\n";
			$pass = false;
			}
			if (GedcomConfig::$LISTS_ALL) {
				print " | ";
				if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
				if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
				// NOTE: only include the alpha letter when not showing the ALL list
				if ($this->show_all == "no") print "alpha=".urlencode($this->alpha)."&amp;";
				// NOTE: Include the surname if surnames are to be listed
				if ($this->allgeds == "yes") print "&amp;allgeds=yes&amp;";
				if ($this->surname_sublist == "yes" && !empty($this->surname)) print "surname=".urlencode($this->surname)."&amp;";
				if ($this->show_all_firstnames == "yes") print "show_all_firstnames=no&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist."\"><span class=\"warning\">".GM_LANG_all."</span>\n";
				else print "show_all_firstnames=yes&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist."\">".GM_LANG_all."</a>\n";
			}
			print "</div><br />\n";
			print "<div class=\"topbar\">".GM_LANG_individual_list."</div>\n";
			
			if (isset($fstartalpha)) $this->falpha = $fstartalpha;
			// NOTE: Get only the names who start with the matching first letter. This is the case if no firstletter is chosen yet, and the list must be split because of the number of records found.
			if ($this->show_all_firstnames == "no") {
				foreach($personlist as $key => $person) {
					$hit = false;
					foreach($person->name_array as $key2 => $namearr) {
						if ($namearr[5] == $this->falpha) {
							$hit = true;
							break;
						}
					}
					if (!$hit) unset($personlist[$key]);
				}
			}
			$this->PrintPersonList($personlist, false);
		}
		else {
			$names = array();
			foreach($personlist as $gid => $indi) {
				// NOTE: make sure that favorites from other gedcoms are not shown
				if ($indi->gedcomid == $GEDCOMID || $this->allgeds == "yes") {
					foreach($indi->name_array as $indexval => $namearray) {
						// NOTE: Only include married names if chosen to show so
						// NOTE: Do not include calculated names. Identified by C.
							$names[] = array($namearray[0], $gid, $indexval);
					}
				}
			}
			uasort($names, "ItemSort");
			reset($names);
			$total_indis = count($personlist);
			$count = count($names);
			$i=0;
			print "<table class=\"center ".$TEXT_DIRECTION."\"><tr>";
			print "<td class=\"shade1 list_value indilist $TEXT_DIRECTION\"><ul>\n";
			foreach($names as $indexval => $namearray) {
				$person = $personlist[$namearray[1]];
				if (!$person->PrintListPerson(true, false, "", $namearray[2])) {
					$indi_hide[$person->key] = true;
				}
				$i++;
				if ($i==ceil($count/2) && $count>8) print "</ul></td><td class=\"shade1 list_value indilist $TEXT_DIRECTION\"><ul>\n";			
			}
			print "</ul></td>\n";
			print "</tr><tr><td colspan=\"2\" class=\"center\">";
			if (GedcomConfig::$SHOW_MARRIED_NAMES) print GM_LANG_total_names." ".count($names)."<br />\n";
			print GM_LANG_total_indis." ".$total_indis;
			if (count($indi_private)>0) print "  (".GM_LANG_private." ".count($indi_private).")";
			if (count($indi_hide)>0) print "  --  ".GM_LANG_hidden." ".count($indi_hide);
			if (count($indi_private)>0 || count($indi_hide)>0) PrintHelpLink("privacy_error_help", "qm");
			print "</td>\n";
			print "</tr></table>";
		}
	}

}
?>
