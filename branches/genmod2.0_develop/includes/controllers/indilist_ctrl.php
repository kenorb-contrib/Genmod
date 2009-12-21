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
	
	// Parameters
	public $alpha = null;
	public $surname = null;
	public $surname_sublist = null;
	public $show_all = null;
	public $show_all_firstnames = null;
	public $allgeds = null;
	private $addheader = null;
	private $count_indi = 0;
	private $count_names = 0;
	
	
	public function __construct() {
		global $ALLOW_CHANGE_GEDCOM, $gm_user;
		
		global $GM_IMAGES, $nonfacts, $nonfamfacts;
		global $ENABLE_CLIPPINGS_CART, $show_changes;
		
		parent::__construct();
		
		// Get the incoming letter
		if (isset($_GET["alpha"])) {
			$this->alpha = urldecode($_GET["alpha"]);
			$this->addheader = "(".$this->alpha.")";
		}
		else $this->alpha = "";
		
		// Get the incoming surname
		if (isset($_GET["surname"])) {
			$this->surname = urldecode($_GET["surname"]);
			$this->addheader = "(".CheckNN($this->surname).")";
		}
		else $this->surname = "";

		if ($this->alpha == "" && $this->surname != "") $this->alpha = GetFirstLetter(StripPrefix($this->surname));
		
		// Get the sublist switch
		if (isset($_GET["surname_sublist"])) $this->surname_sublist = $_GET["surname_sublist"];
		if (is_null($this->surname_sublist)) $this->surname_sublist = "yes";
		
		if (isset($_GET["show_all"])) $this->show_all = $_GET["show_all"];
		if (is_null($this->show_all)) $this->show_all = "no";
		
		if (isset($_GET["show_all_firstnames"])) $this->show_all_firstnames = $_GET["show_all_firstnames"];
		if (is_null($this->show_all_firstnames)) $this->show_all_firstnames = "no";
		
		if (!isset($_GET["allgeds"]) || $_GET["allgeds"] != "yes" || !$ALLOW_CHANGE_GEDCOM) $this->allgeds = "no";
		else $this->allgeds = "yes";
		
		if ($this->allgeds == "yes" && $gm_user->username == "") {
			if (GedcomConfig::AnyGedcomHasAuth()) $this->allgeds = "no";
		}
	}

//	public function __get($property) {
//		switch($property) {
//			default:
//				return parent::__get($property);
//				break;
//		}
//	}
	
	
	protected function GetPageTitle() {

		if (is_null($this->pagetitle)) {
			$this->pagetitle = GM_LANG_individual_list." ".$this->addheader;
		}
		return $this->pagetitle;
	}
		
			
	 /**
	 * Get all first letters of individual's last names
	 *
	 * The function takes all the distinct lastname starting letters 
	 * found in both the individual and names table. Then some language specific
	 * letter substitution is done
	 *
	 * @see indilist.php
	 * @author	Genmod Development Team
	 * @return 	array	An array of all letters found in the active gedcom
	 */
	
	public function GetIndiAlpha() {
		global $LANGUAGE, $GEDCOMID;
		
		$indialpha = array();
		
		$sql = "SELECT DISTINCT n_letter AS alpha ";
		$sql .= "FROM ".TBLPREFIX."names ";
		if ($this->allgeds == "no") $sql .= "WHERE n_file = '".$GEDCOMID."'";
		$res = NewQuery($sql);
		
		$hungarianex = array("DZS", "CS", "DZ" , "GY", "LY", "NY", "SZ", "TY", "ZS");
		$danishex = array("OE", "AE", "AA");
		while($row = $res->FetchAssoc()){
			$letter = $row["alpha"];
			if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian"){
				if (in_array(strtoupper($letter), $danishex)) {
					if (strtoupper($letter) == "OE") $letter = "Ø";
					else if (strtoupper($letter) == "AE") $letter = "Æ";
					else if (strtoupper($letter) == "AA") $letter = "Å";
				}
			}
			if (strlen($letter) > 1){
				if (ord($letter) < 92){
					if ($LANGUAGE != "hungarian" && in_array($letter, $hungarianex)) $letter = substr($letter, 0, 1);
					if (($LANGUAGE != "danish" || $LANGUAGE != "norwegian") && in_array($letter, $danishex)) $letter = substr($letter, 0, 1);
				}
			}
			if (CheckUTF8($letter)) $indialpha[]=$letter;
		}
		$res->FreeResult();
		$indialpha = array_flip(array_flip($indialpha));
		uasort($indialpha, "stringsort");
		
		if ($this->alpha != "" && !in_array($this->alpha, $indialpha)) $this->alpha = "";
		return $indialpha;
	}
	
	/**
	 * Get Individual surnames starting with a letter
	 *
	 * This function finds all of the individuals surnames who start with the given letter
	 *
	 * @param string $letter	The letter to search on
	 * @return array	$names array
	 */
	public function GetAlphaIndiNames($letter="") {
		global $GEDCOMID;
	
		$search_letter = $this->GetNameString($letter);
		
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
		return $names;
	}
	
	/**
	 * Print a list of surnames
	 *
	 * A table with columns is printed from an array of surnames. This can be individuals
	 * or families.
	 *
	 * @todo		Add statistics for private and hidden links
	 * @author	Genmod Development Team
	 * @param		array		$personlist	The array with names to be printed
	 * @param		string		$page		The page the links should point to
	 */
	public function PrintSurnameList($surnames, $page, $resturl="") {
		global $TEXT_DIRECTION;
		global $indi_hide, $indi_total;
		
		if (stristr($page, "aliveinyear")) {
			$aiy = true;
			global $indi_dead, $indi_alive, $indi_unborn;
		}
		else $aiy = false;
		
		$i = 0;
		$col = 1;
		$count = count($surnames);
		if ($count == 0) return;
		else if ($count>36) $col=4;
		else if ($count>18) $col=3;
		else if ($count>6) $col=2;
		$newcol=ceil($count/$col);
		print "<table class=\"center $TEXT_DIRECTION\"><tr>";
		print "<td class=\"shade1 list_value wrap\">\n";
		
		// Surnames with starting and ending letters in 2 text orientations is shown in
		// a wrong way on the page with different orientation from the orientation of the first name letter
		foreach($surnames as $surname=>$namecount) {
			if (begRTLText($namecount["name"])) {
	 			print "<div class =\"rtl\" dir=\"rtl\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=".$this->surname_sublist."&amp;surname=".urlencode($namecount["name"]).$resturl;
	 			if ($this->allgeds == "yes") print "&amp;allgeds=yes";
	 			print "\">&nbsp;";
	 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
	 			else print PrintReady($namecount["name"]);
	 			print "&rlm; - [".($namecount["match"])."]&rlm;";
			}
			else if (substr($namecount["name"], 0, 4) == "@N.N") {
				print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=".$this->surname_sublist."&amp;surname=@N.N.".$resturl;
	 			if ($this->allgeds == "yes") print "&amp;allgeds=yes";
				print "\">&nbsp;".GM_LANG_NN . "&lrm; - [".($namecount["match"])."]&lrm;&nbsp;";
			}
			else {
				print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=".$this->surname_sublist."&amp;surname=".urlencode($namecount["name"]).$resturl;
	 			if ($this->allgeds == "yes") print "&amp;allgeds=yes";
				print "\">";
	 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
				else print PrintReady($namecount["name"]);
				print "&lrm; - [".($namecount["match"])."]&lrm;";
			}
	
	 		print "</a></div>\n";
			$this->count_indi += $namecount["match"];
			$i++;
			if ($i==$newcol && $i<$count) {
				print "</td><td class=\"shade1 list_value wrap\">\n";
				$newcol=$i+ceil($count/$col);
			}
		}
		if ($aiy) $indi_total = $indi_alive + $indi_dead + $indi_unborn + count($indi_hide);
		else if (is_array($indi_total)) $indi_total = count($indi_total);
		print "</td>\n";
		if ($count>1 || count($indi_hide)>0) {
			print "</tr><tr><td colspan=\"$col\" class=\"center\">&nbsp;";
			if (GedcomConfig::$SHOW_MARRIED_NAMES && $count>1) print GM_LANG_total_names." ".$this->count_indi."<br />";
			if (isset($indi_total) && $count>1) print GM_LANG_total_indis." ".$indi_total."&nbsp;";
			if ($count>1 && count($indi_hide)>0) print "--&nbsp;";
			if (count($indi_hide)>0) print GM_LANG_hidden." ".count($indi_hide);
			if ($count>1 && $aiy) {
				print "<br />".GM_LANG_unborn."&nbsp;".$indi_unborn;
				print "&nbsp;--&nbsp;".GM_LANG_alive."&nbsp;".$indi_alive;
				print "&nbsp;--&nbsp;".GM_LANG_dead."&nbsp;".$indi_dead;
			}
			if ($count>1) print "<br />".GM_LANG_surnames." ".$count;
			print "</td>\n";
		}
		print "</tr></table>";
	}
	
	/**
	 * Get Individuals with a given surname or a given first surname letter or simply all indis
	 *
	 * This function finds all of the individuals who have the given surname
	 * @param string $surname	The surname to search on
	 * @return array	$indilist array
	 */
	public function GetSurnameIndis($surname="", $alpha="") {
		global $GEDCOMID;

		if (!empty($alpha)) {
			$search_letter = $this->GetNameString($alpha);
			$surname = "";
		}
		else $search_letter = "";
		
		$tindilist = array();
		$sql = "SELECT i_key, i_id, i_file, i_isdead, i_gedrec, n_letter, n_name, n_surname, n_type FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key";
		
		if (!empty($surname)) $sql .= " AND n_surname LIKE '".DbLayer::EscapeQuery($surname)."'";
		else if (!empty($search_letter)) $sql .= " AND ".$search_letter;
		
		if (!GedcomConfig::$SHOW_MARRIED_NAMES) $sql .= " AND n_type!='C'";
		
		if ($this->allgeds == "no") $sql .= " AND i_file='".$GEDCOMID."'";

		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			SwitchGedcom($row["i_file"]);
			if (GedcomConfig::$SHOW_NICK) {
				$n = NameFunctions::GetNicks($row["i_gedrec"]);
				if (count($n) > 0) {
					$ct = preg_match("~(.*)/(.*)/(.*)~", $row["n_name"], $match);
					if ($ct>0) $row["n_name"] = $match[1].substr(GedcomConfig::$NICK_DELIM, 0, 1).$n[0].substr(GedcomConfig::$NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
				}
			}
			$key = $row["i_key"];
			if (!isset($tindilist[$key])) {
				$tindilist[$key] = Person::GetInstance($row["i_id"], array("i_isdead" => $row["i_isdead"], "i_file" =>$row["i_file"], "i_id" => $row["i_id"], "i_gedrec" => $row["i_gedrec"]), $row["i_file"]);
			}
			$tindilist[$key]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
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
	 * @param		boolean	$find		Set to yes to print links for the find pages
	 */
	public function PrintPersonList($personlist, $print_all=true, $find=false) {
		global $TEXT_DIRECTION;
		global $indi_private, $indi_hide, $surname, $alpha, $falpha;
		global $GEDCOMID, $year;
	
		print "<table class=\"center ".$TEXT_DIRECTION."\"><tr>";
		// NOTE: The list is really long so divide it up again by the first letter of the first name
		if (GedcomConfig::$ALPHA_INDEX_LISTS && count($personlist) > GedcomConfig::$ALPHA_INDEX_LISTS && $print_all == true && $find == false) {
			$firstalpha = array();
			foreach($personlist as $gid => $indi) {
				foreach($indi->name_array as $indexval => $namearray) {
					$letter = GetFirstLetter($namearray[0], false);
					if (!isset($firstalpha[$letter])) {
						$firstalpha[$letter] = array("letter"=>$letter, "ids"=>$gid);
					}
					else $firstalpha[$letter]["ids"] .= ",".$gid;
				}
			}
			// NOTE: Sort the person array
			uasort($firstalpha, "LetterSort");
			// NOTE: Print the second alpha letter list for the unknown names
			print "<td class=\"shade1 list_value wrap center\" colspan=\"2\">\n";
			print_help_link("firstname_alpha_help", "qm");
			print GM_LANG_first_letter_fname."<br />\n";
			$first = true;
			foreach($firstalpha as $letter=>$list) {
				$pass = false;
				if ($letter != "@") {
					if (!isset($fstartalpha) && (!isset($falpha) || !isset($firstalpha[$falpha]))) {
						$fstartalpha = $letter;
						$falpha = $letter;
					}
					if (!$first) print " | ";
					$first = false;
					// NOTE: Print the link letter
					if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
					if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
					// NOTE: only include the alpha letter when not showing the ALL list
					if ($this->show_all == "no") print "alpha=".urlencode($this->alpha)."&amp;";
					if ($this->surname_sublist == "yes" && isset($surname)) print "surname=".$surname."&amp;";
					print "falpha=".urlencode($letter)."&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist;
					if ($this->allgeds == "yes") print "&amp;allgeds=yes";
					print "\">";
					// NOTE: Red color for the chosen letter otherwise simply print the letter
					if (($falpha == $letter)&&($this->show_all_firstnames == "no")) print "<span class=\"warning\">".htmlspecialchars($letter)."</span>";
					else print htmlspecialchars($letter);
					print "</a>\n";
				}
				if ($letter === "@") {
					$pass = true;
				}
			}
			if (!isset($pass)) $pass = false;
			// NOTE: Print the Unknown text on the letter bar
			if ($pass == true) {
				print " | ";
				if (isset($falpha) && $falpha == "@") {
					if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
					if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
					print "alpha=".urlencode($this->alpha)."&amp;falpha=@&amp;surname_sublist=yes";
					if ($this->allgeds == "yes") print "&amp;allgeds=yes";
					print "\"><span class=\"warning\">".PrintReady(GM_LANG_NN)."</span></a>\n";
				}
				else {
					if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
					if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
					print "alpha=".urlencode($this->alpha)."&amp;falpha=@&amp;surname_sublist=yes";
					print "\">".PrintReady(GM_LANG_NN)."</a>\n";
				}
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
				if ($this->surname_sublist == "yes" && isset($surname)) print "surname=".urlencode($surname)."&amp;";
				if ($this->show_all_firstnames == "yes") print "show_all_firstnames=no&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist."\"><span class=\"warning\">".GM_LANG_all."</span>\n";
				else print "show_all_firstnames=yes&amp;show_all=".$this->show_all."&amp;surname_sublist=".$this->surname_sublist."\">".GM_LANG_all."</a>\n";
			}
			print "</td></tr><tr>\n";
			
			if (isset($fstartalpha)) $falpha = $fstartalpha;
			// NOTE: Get only the names who start with the matching first letter
			if ($this->show_all_firstnames == "no") {
				$findilist = array();
				if (isset($firstalpha[$falpha])) {
					$ids = preg_split("/,/", $firstalpha[$falpha]["ids"]);
					foreach($ids as $indexval => $id) {
						$id = trim($id);
						if (!empty($id)) $findilist[$id] = $personlist[$id];
					}
				}
				$this->PrintPersonList($findilist, false, $find);
			}
			else $this->PrintPersonList($personlist, false, $find);
		}
		else {
			$names = array();
			foreach($personlist as $gid => $indi) {
				// NOTE: make sure that favorites from other gedcoms are not shown
				if ($indi->gedcomid == $GEDCOMID || $find == true || $this->allgeds == "yes") {
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
			print "<td class=\"shade1 list_value indilist $TEXT_DIRECTION\"><ul>\n";
			foreach($names as $indexval => $namearray) {
				$person = $personlist[$namearray[1]];
				if (!$person->PrintListPerson(true, false, "", $namearray[2])) {
					$indi_hide[$person->key] = true;
					$count--;
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
			if (count($indi_private)>0 || count($indi_hide)>0) print_help_link("privacy_error_help", "qm");
			print "</td>\n";
		}
		print "</tr></table>";
	}

	private function GetNameString($letter) {
		global $LANGUAGE;
		
		$search_letter = "";
		if (!empty($letter)) {
			// NOTE: Determine what letter to search for depending on the active language
			if ($LANGUAGE == "hungarian"){
				if (strlen($letter) >= 2) $search_letter = "'".DbLayer::EscapeQuery($letter)."' ";
				else {
					if ($letter == "C") $text = "CS";
					else if ($letter == "D") $text = "DZ";
					else if ($letter == "G") $text = "GY";
					else if ($letter == "L") $text = "LY";
					else if ($letter == "N") $text = "NY";
					else if ($letter == "S") $text = "SZ";
					else if ($letter == "T") $text = "TY";
					else if ($letter == "Z") $text = "ZS";
					if (isset($text)) $search_letter = "(n_letter = '".DbLayer::EscapeQuery($letter)."' AND n_letter != '".DbLayer::EscapeQuery($text)."') ";
					else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
				}
			}
			else if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
				if ($letter == "Ø") $text = "OE";
				else if ($letter == "Æ") $text = "AE";
				else if ($letter == "Å") $text = "AA";
				if (isset($text)) $search_letter = "(n_letter = '".DbLayer::EscapeQuery($letter)."' OR n_letter = '".DbLayer::EscapeQuery($text)."') ";
				else if ($letter=="A") $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
				else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
			}
			else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
		}
		return $search_letter;
	}
}
?>
