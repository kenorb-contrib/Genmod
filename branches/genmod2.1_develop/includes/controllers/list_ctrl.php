<?php
/**
 * Controller for the list Pages
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
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: list_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
abstract class ListController extends BaseController {
	
	public $classname = "ListController";	// Name of this class
	
	// Parameters
	protected $alpha = null;				// Letter of which the indi's or surnames must be displayed
	protected $falpha = null;				// Letter of which the indi's with that firstname letter must be shown
	protected $surname = null;				// Surname of the indi's to display
	protected $surname_sublist = null;		// Whether or not to display a surname list after choosing a letter
	protected $show_all = null;				// Selected the "ALL" option from the letterbar
	protected $show_all_firstnames = null;	// Selected the "ALL" option from the firstname letterbar
	protected $allgeds = null;				// Indilist for all geds together or just the currend gedcom
	
	// Counters and additinoal data
	private $count_names = 0;				// Number of names found (surname sublist)
	private $count_objs = 0;				// Number of individuals or families found (surname sublist)
	protected $addheader = null;			// Adds the chosen letter/name to the page header
	
	
	public function __construct() {
		global $gm_user;
		
		parent::__construct();
		
		// Get the incoming letter
		if (isset($_GET["alpha"])) {
			$this->alpha = urldecode($_GET["alpha"]);
		}
		else $this->alpha = "";
		if (!empty($this->alpha)) $this->addheader = "(".$this->alpha.")";
		
		// Get the incoming first name letter
		if (isset($_GET["falpha"])) {
			$this->falpha = urldecode($_GET["falpha"]);
		}
		else $this->falpha = "";
		
		// Get the incoming surname
		if (isset($_GET["surname"])) {
			$this->surname = trim(urldecode($_GET["surname"]));
			$this->addheader = "(".NameFunctions::CheckNN($this->surname).")";
		}
		else $this->surname = "";

		if ($this->alpha == "" && $this->surname != "") $this->alpha = NameFunctions::GetFirstLetter(NameFunctions::StripPrefix($this->surname));
		
		// Get the sublist switch
		if (isset($_GET["surname_sublist"])) $this->surname_sublist = $_GET["surname_sublist"];
		if (is_null($this->surname_sublist)) $this->surname_sublist = "yes";
		if ($this->surname_sublist == "yes") {
			if (empty($this->surname)) $this->addheader = GM_LANG_surnames." ".$this->addheader;
		}
		
		if (isset($_GET["show_all"])) $this->show_all = $_GET["show_all"];
		if (is_null($this->show_all)) $this->show_all = "no";
		if ($this->show_all == "yes") $this->addheader .= "(".GM_LANG_all.")";
		
		if (isset($_GET["show_all_firstnames"])) $this->show_all_firstnames = $_GET["show_all_firstnames"];
		if (is_null($this->show_all_firstnames)) $this->show_all_firstnames = "no";
		
		if (!isset($_GET["allgeds"]) || $_GET["allgeds"] != "yes" || !SystemConfig::$ALLOW_CHANGE_GEDCOM) $this->allgeds = "no";
		else $this->allgeds = "yes";
		
		if ($this->allgeds == "yes" && $gm_user->username == "") {
			if (GedcomConfig::AnyGedcomHasAuth()) $this->allgeds = "no";
		}
	}

	public function __get($property) {
		switch($property) {
			case "alpha":
				return $this->alpha;
				break;
			case "falpha":
				return $this->falpha;
				break;
			case "surname":
				return $this->surname;
				break;
			case "surname_sublist":
				return $this->surname_sublist;
				break;
			case "show_all":
				return $this->show_all;
				break;
			case "show_all_firstnames":
				return $this->show_all_firstnames;
				break;
			case "allgeds":
				return $this->allgeds;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	protected function GetNameString($letter) {
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
	
	public function GetLetterBar($lastname=true) {
		global $LANGUAGE;
		
		$alphalist = array();
		
		if ($lastname) {
			if ($this->classname == "FamlistController") {
				$sql = "SELECT DISTINCT n_letter AS alpha FROM ".TBLPREFIX."names INNER JOIN ".TBLPREFIX."individual_family ON if_pkey=n_key WHERE if_role='S'";
				if ($this->allgeds == "no") $sql .= " AND n_file = '".GedcomConfig::$GEDCOMID."'";
			}
			else {
				$sql = "SELECT DISTINCT n_letter AS alpha FROM ".TBLPREFIX."names ";
				if ($this->allgeds == "no") $sql .= "WHERE n_file = '".GedcomConfig::$GEDCOMID."'";
			}
		}
		else {
			if ($this->classname == "FamlistController") {
				$sql = "SELECT DISTINCT n_fletter AS alpha FROM ".TBLPREFIX."names INNER JOIN ".TBLPREFIX."individual_family ON if_pkey=n_key WHERE if_role='S'";
				if ($this->surname != "") $sql .= " AND n_surname='".DbLayer::EscapeQuery($this->surname)."'";
				else if ($this->alpha != "") $sql .= " AND n_letter='".DbLayer::EscapeQuery($this->alpha)."'";
				if ($this->allgeds == "no") $sql .= " AND n_file = '".GedcomConfig::$GEDCOMID."'";
			}
			else {
				$sql = "SELECT DISTINCT n_fletter as alpha FROM ".TBLPREFIX."names WHERE";
				if ($this->surname != "") $sql .= " n_surname='".DbLayer::EscapeQuery($this->surname)."'";
				elseif ($this->alpha != "") $sql .= " n_letter='".DbLayer::EscapeQuery($this->alpha)."'";
				if ($this->allgeds == "no") $sql .= ($this->alpha != "" || $this->surname != "" ? " AND" : "")." n_file = '".GedcomConfig::$GEDCOMID."'";
			}
		}
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
			if (CheckUTF8($letter)) $alphalist[] = $letter;
		}
		$res->FreeResult();
		
		if ($this->classname == "FamlistController" && $lastname) {
			$sql = "SELECT count(f_id) FROM ".TBLPREFIX."families WHERE (f_husb='' || f_wife='')";
			if ($this->allgeds == "no") $sql .= " AND f_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			if ($row[0] > 0) {
				$alphalist[] = "@";
			}
			$res->FreeResult();
		}
		$alphalist = array_flip(array_flip($alphalist));
		uasort($alphalist, "stringsort");
		
		return $alphalist;
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
	public function PrintSurnameList($surnames, $resturl="") {
		global $TEXT_DIRECTION;
		global $indi_hide, $indi_total;
		
//		if ($this->classname == "FamlistController") $page = "famlist.php";
//		else if ($this->classname == "IndilistController") $page = "indilist.php";
//		else if ($this->classname == "AliveInYearController") $page = "aliveinyear.php";
		
		if (stristr(SCRIPT_NAME, "aliveinyear")) {
			$aiy = true;
			global $indi_dead, $indi_alive, $indi_unborn;
		}
		else $aiy = false;
		
		$i = 0;
		$col = 1;
		$this->count_names = count($surnames);
		if ($this->count_names == 0) return;
		else if ($this->count_names > 130) $col=5;
		else if ($this->count_names > 54) $col=4;
		else if ($this->count_names > 24) $col=3;
		else if ($this->count_names > 6) $col=2;
		$newcol = ceil($this->count_names/$col);
		print "<table class=\"ListTable IndiFamSurnameListTable\">";
		print "<tr><td class=\"ListTableHeader\" colspan=\"".$col."\">";
		print GM_LANG_surnames;
		print "</td></tr><tr>";
		print "<td class=\"ListTableContent\">\n";
		
		// Surnames with starting and ending letters in 2 text orientations is shown in
		// a wrong way on the page with different orientation from the orientation of the first name letter
		foreach($surnames as $surname=>$namecount) {
			if (begRTLText($namecount["name"])) {
	 			print "<div class =\"IndiFamSurnameLink\"><a href=\"".SCRIPT_NAME."?alpha=".urlencode($this->alpha)."&amp;surname_sublist=".$this->surname_sublist."&amp;surname=".urlencode($namecount["name"]).$resturl;
	 			if ($this->allgeds == "yes") print "&amp;allgeds=yes";
	 			print "\">";
	 			if (NameFunctions::HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".NameFunctions::GetPinYin($namecount["name"]).")");
	 			else if (NameFunctions::HasCyrillic($namecount["name"])) print PrintReady($namecount["name"]." (".NameFunctions::GetTransliterate($namecount["name"]).")");
	 			else print PrintReady($namecount["name"]);
	 			print "&rlm; - [".($namecount["match"])."]&rlm;";
			}
			else if (substr($namecount["name"], 0, 4) == "@N.N") {
				print "<div class =\"IndiFamSurnameLink\"><a href=\"".SCRIPT_NAME."?alpha=".urlencode($this->alpha)."&amp;surname_sublist=".$this->surname_sublist."&amp;surname=@N.N.".$resturl;
	 			if ($this->allgeds == "yes") print "&amp;allgeds=yes";
				print "\">".GM_LANG_NN . "&lrm; - [".($namecount["match"])."]&lrm;";
			}
			else {
				print "<div class =\"IndiFamSurnameLink\"><a href=\"".SCRIPT_NAME."?alpha=".urlencode($this->alpha)."&amp;surname_sublist=".$this->surname_sublist."&amp;surname=".urlencode($namecount["name"]).$resturl;
	 			if ($this->allgeds == "yes") print "&amp;allgeds=yes";
				print "\">";
	 			if (NameFunctions::HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".NameFunctions::GetPinYin($namecount["name"]).")");
	 			else if (NameFunctions::HasCyrillic($namecount["name"])) print PrintReady($namecount["name"]." (".NameFunctions::GetTransliterate($namecount["name"]).")");
				else print PrintReady($namecount["name"]);
				print "&lrm; - [".($namecount["match"])."]&lrm;";
			}
	
	 		print "</a></div>\n";
			$this->count_objs += $namecount["match"];
			$i++;
			if ($i==$newcol && $i < $this->count_names) {
				print "</td><td class=\"ListTableContent\">\n";
				$newcol = $i + ceil($this->count_names/$col);
			}
		}
		print "</td>\n";
		if ($this->count_names > 1) {
			print "</tr><tr><td colspan=\"$col\" class=\"ListTableColumnFooter\">";
			print GM_LANG_total_names." ".$this->count_names."<br />";
			if ($this->classname == "FamlistController") print GM_LANG_total_fams." ".round($this->count_objs/2,0);
			else print GM_LANG_total_indis." ".$this->count_objs;
			print "</td>\n";
		}
		print "</tr></table>";
	}
	
}
?>
