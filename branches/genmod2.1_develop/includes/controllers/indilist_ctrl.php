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
 * @version $Id: indilist_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
class IndilistController extends ListController {
	
	public $classname = "IndilistController";	// Name of this class
	
	// Extra variables for aliveinyear
	private $type = "narrow";					// Type can be either "true", "wide" or "narrow"
	private $useMAA = 0;						// Use the MAX_ALIVE_AGE to determine if a person is dead. Values 0 or 1
	private $year = null;						// Year in which the person must be alive
	
	// Calculated values
	
	
	public function __construct() {
		
		parent::__construct();

		if (isset($_REQUEST["type"])) $this->type = $_REQUEST["type"];
		if (isset($_REQUEST["useMAA"])) $this->useMAA = $_REQUEST["useMAA"];
		if (isset($_REQUEST["year"])) $this->year = $_REQUEST["year"];
		if (!is_numeric($this->year)) $this->year = date("Y");
		
	}

	public function __get($property) {
		switch($property) {
			case "type":
				return $this->type;
				break;
			case "useMAA":
				return $this->useMAA;
				break;
			case "year":
				return $this->year;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	
	protected function GetPageTitle() {

		if (is_null($this->pagetitle)) {
			if (stristr(SCRIPT_NAME, "indilist.php")) $this->pagetitle = GM_LANG_individual_list." ".$this->addheader;
			if (stristr(SCRIPT_NAME, "unlinked.php")) $this->pagetitle = GM_LANG_unlink_list;
			if (stristr(SCRIPT_NAME, "aliveinyear.php")) $this->pagetitle = GM_LANG_alive_in_year." ".$this->year;
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
			$sql .= $where."n_file LIKE '".GedcomConfig::$GEDCOMID."'";
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

		$search_letter = $this->GetNameString($this->alpha);
		
		$tindilist = array();
		$sql = "SELECT i_key, i_id, i_file, i_isdead, i_gedrec, n_letter, n_fletter, n_name, n_surname, n_nick, n_type FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key";
		
		if ($this->surname != "") $sql .= " AND n_surname LIKE '".DbLayer::EscapeQuery($this->surname)."'";
		else if (!empty($search_letter)) $sql .= " AND ".$search_letter;
		if ($this->falpha != "") $sql .= " AND n_fletter='".$this->falpha."'";
		if (!GedcomConfig::$SHOW_MARRIED_NAMES) $sql .= " AND n_type!='C'";
		
		if ($this->allgeds == "no") $sql .= " AND i_file='".GedcomConfig::$GEDCOMID."'";

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
		global $year;
	
		// NOTE: The list is really long so divide it up again by the first letter of the first name
		if (((GedcomConfig::$ALPHA_INDEX_LISTS && count($personlist) > GedcomConfig::$ALPHA_INDEX_LISTS) || $this->falpha != "") && $print_all == true) {
			
			$firstalpha = $this->GetLetterBar(false);
			
			// NOTE: Print the second alpha letter list for the unknown names
			print "<div class=\"IndiFamLetterBar\">\n";
			PrintHelpLink("firstname_alpha_help", "qm");
			print GM_LANG_first_letter_fname."<br />\n";
			$first = true;
			$pass = false;
			foreach($firstalpha as $index=>$letter) {
				if ($letter != "@") {
					if ((!isset($fstartalpha) && $this->falpha == "") || !in_array($this->falpha, $firstalpha)) {
						$fstartalpha = $letter;
						$this->falpha = $letter;
					}
					if (!$first) print " | ";
					$first = false;
					// NOTE: Print the link letter
					if (stristr(SCRIPT_NAME,"indilist.php")) print "<a href=\"indilist.php?";
					if (stristr(SCRIPT_NAME,"unlinked.php")) print "<a href=\"unlinked.php?";
					if (stristr(SCRIPT_NAME,"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=".$this->year."&amp;type=".$this->type."&amp;useMAA=".$this->useMAA."&amp;";
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
				if (stristr(SCRIPT_NAME,"indilist.php")) print "<a href=\"indilist.php?";
				if (stristr(SCRIPT_NAME,"unlinked.php")) print "<a href=\"unlinked.php?";
				if (stristr(SCRIPT_NAME,"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=".$this->year."&amp;type=".$this->type."&amp;useMAA=".$this->useMAA."&amp;";
				if ($this->surname_sublist == "yes" && !empty($this->surname)) print "surname=".$this->surname."&amp;";
				print "alpha=".urlencode($this->alpha)."&amp;falpha=@&amp;surname_sublist=".$this->surname_sublist."&amp;show_all=".$this->show_all;
				if ($this->allgeds == "yes") print "&amp;allgeds=yes";
				if (!empty($this->falpha) && $this->falpha == "@") print "\"><span class=\"Warning\">".PrintReady(GM_LANG_NN)."</span></a>\n";
				else print "\">".PrintReady(GM_LANG_NN)."</a>\n";
			$pass = false;
			}
			if (GedcomConfig::$LISTS_ALL) {
				print " | ";
				if (stristr(SCRIPT_NAME,"indilist.php")) print "<a href=\"indilist.php?";
				if (stristr(SCRIPT_NAME,"unlinked.php")) print "<a href=\"unlinked.php?";
				if (stristr(SCRIPT_NAME,"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=".$this->year."&amp;type=".$this->type."&amp;useMAA=".$this->useMAA."&amp;";
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
				if ($indi->gedcomid == GedcomConfig::$GEDCOMID || $this->allgeds == "yes") {
					foreach($indi->name_array as $indexval => $namearray) {
						// NOTE: Only include married names if chosen to show so
						// NOTE: Do not include calculated names. Identified by C.
							$names[] = array($namearray[0], $gid, $indexval);
					}
				}
			}
			uasort($names, "ItemSort");
			reset($names);
			$indi_private = array();
			$indi_dead = array();
			$indi_unborn = array();
			$indi_alive = array();
			$indi_unknown = array();
			$total_indis = count($personlist);
			$count = count($names);
			$i=0;
			print "<table class=\"ListTable IndiFamListTable\">";
			print "<tr><td class=\"ListTableHeader\" colspan=\"2\">";
			// List with a specific surname
			if (stristr(SCRIPT_NAME,"unlinked.php")) print GM_LANG_individual_list;
			else if ($this->surname_sublist == "yes" && $this->surname != "") {
				print PrintReady(str_replace("#surname#", NameFunctions::CheckNN($this->surname), GM_LANG_indis_with_surname));
			}
			else if ($this->show_all == "no") {
				// All persons with a surname with a specific letter 
				print PrintReady(str_replace("#letter#", ($this->alpha == "@" ? GM_LANG_NN : htmlspecialchars($this->alpha)), GM_LANG_indis_with_surname_letter));
			}
			else {
				print PrintReady(str_replace("#letter#", ($this->falpha == "@" ? GM_LANG_PN : htmlspecialchars($this->falpha)), GM_LANG_indis_with_fistname_letter));
			}
//			if ($this->surname_sublist == "yes" && $this->surname == "") print GM_LANG_surnames;
//			else print PrintReady(str_replace("#surname#", NameFunctions::CheckNN($this->surname), GM_LANG_indis_with_surname));
			print "</td></tr>";
			// Before we start printing, we first count the lines of printed individuals
			$printcount = 0;
			foreach($names as $indexval => $namearray) {
				$person = $personlist[$namearray[1]];
				if (stristr(SCRIPT_NAME,"aliveinyear.php")) {
					if ($this->CheckAlive($person) == -1) $indi_unborn[$person->key] = true;
					else if ($this->CheckAlive($person) == 1) $indi_dead[$person->key] = true;
					else if ($this->CheckAlive($person) == -2) $indi_unknown[$person->key] = true;
					else {
						$indi_alive[$person->key] = true;
						if (!$person->disp) $indi_private[$person->key] = true;
						else $printcount++;
					}
				}
				else {
					if (!$person->disp_name) $indi_private[$person->key] = true;
					else $printcount++;
				}
			}
			// Only print the table if we have anything to print
			$cols = 1;
			if ($printcount > 0) {
				print "<tr><td class=\"ListTableContent IndiFamListTableContent\"><ul>\n";
				foreach($names as $indexval => $namearray) {
					$person = $personlist[$namearray[1]];
					if (stristr(SCRIPT_NAME,"aliveinyear.php")) {
						if (isset($indi_alive[$person->key]) && !isset($indi_private[$person->key])) {
							$person->PrintListPerson(true, false, "", $namearray[2]);
							$i++;
						}
					}
					else {
						if (!isset($indi_private[$person->key])) {
							$person->PrintListPerson(true, false, "", $namearray[2]);
							$i++;
						}
					}
					// This is passed multiple times without changing $i, if persons are hidden. Therefore we must track the col
					if ($i==ceil($printcount/2) && $printcount>8 && $cols < 2) {
						print "</ul></td><td class=\"ListTableContent IndiFamListTableContent\"><ul>\n";
						$cols++;
					}
				}
				print "</ul></td></tr>\n";
			}
			print "<tr><td colspan=\"".$cols."\" class=\"ListTableColumnFooter\">";
			if (GedcomConfig::$SHOW_MARRIED_NAMES) print GM_LANG_total_names." ".count($names)."<br />\n";
			print GM_LANG_total_indis." ".$total_indis;
			if (count($indi_private)>0) {
				print "  (".GM_LANG_private." ".count($indi_private).")";
				PrintHelpLink("privacy_error_help", "qm");
			}
			if (stristr(SCRIPT_NAME,"aliveinyear.php")) {
				print "<br />".GM_LANG_unborn."&nbsp;".count($indi_unborn);
				print "&nbsp;--&nbsp;".GM_LANG_alive."&nbsp;".count($indi_alive);
				print "&nbsp;--&nbsp;".GM_LANG_dead."&nbsp;".count($indi_dead);
				print "&nbsp;--&nbsp;".GM_LANG_unknown."&nbsp;".count($indi_unknown);
			}
			print "</td>\n";
			print "</tr></table>";
		}
	}
	// Check the alive status of a person IN A SPECIFIC YEAR
	// This means that DEAT Y must be ignored as it's only true at the present moment
	private function CheckAlive($person) {
		global $MAX_ALIVE_AGE;
		static $alive;
		
		if (!is_numeric($this->year)) return -2;
		if (!isset($alive)) $alive = array();
		if (isset($alive[$person->key])) return $alive[$person->key];
		$bddates = estimateBD($person, $this->type);
		// First check if we must assume something
		if ($this->useMAA) {
			if (isset($bddates["birth"]["year"]) && $bddates["birth"]["type"] == "true" && (!isset($bddates["death"]["year"]) || $bddates["death"]["type"] != "true")) {
				if (!isset($bddates["death"]["year"]) || (isset($bddates["death"]["year"]) && $bddates["death"]["year"] > $bddates["birth"]["year"] + $MAX_ALIVE_AGE)) {
					$bddates["death"]["year"] = $bddates["birth"]["year"] + $MAX_ALIVE_AGE;
					$bddates["death"]["type"] = "est";
				}
			}
			if (isset($bddates["death"]["year"]) && $bddates["death"]["type"] == "true" && (!isset($bddates["birth"]["year"]) || $bddates["birth"]["type"] != "true")) {
				if (!isset($bddates["birth"]["year"]) || (isset($bddates["birth"]["year"]) && $bddates["birth"]["year"] < $bddates["death"]["year"] - $MAX_ALIVE_AGE)) {
					$bddates["birth"]["year"] = $bddates["death"]["year"] - $MAX_ALIVE_AGE;
					$bddates["birth"]["type"] = "est";
				}
			}
		}
		// Nothing to tell for sure: estimated death year is before estimated birth year
		if (isset($bddates["birth"]["year"]) && isset($bddates["death"]["year"]) && $bddates["death"]["year"] < $bddates["birth"]["year"]) {
			$alive[$person->key] = -2;
			//print $person->xref." not for sure (-2)<br />";
			return -2;
		}
		
		// For sure born after
		if (isset($bddates["birth"]["year"]) && $bddates["birth"]["year"] > $this->year) {
			$alive[$person->key] = -1;
			//print $person->xref." for sure born after (-1)<br />";
			return -1;
		}
		
		// For sure died before
		if (isset($bddates["death"]["year"]) && $bddates["death"]["year"] < $this->year) {
			$alive[$person->key] = 1;
			//print $person->xref." for sure died before (1)<br />";
			return 1;
		}
		
		// For sure lived in that year
		if (isset($bddates["death"]["year"]) && isset($bddates["birth"]["year"]) && $bddates["birth"]["year"] <= $this->year && $bddates["death"]["year"] >= $this->year) {
			$alive[$person->key] = "0";
			//print $person->xref." for sure alive (0)<br />";
			return 0;
		}
		
		// All else don't know, we assume nothing
		$alive[$person->key] = -2;
		//print $person->xref." we know nothing (-2)<br />";
		return -2;
		
	}

}
?>
