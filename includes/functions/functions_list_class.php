<?php
/**
 * Function for printing
 *
 * Various printing functions used by all scripts and included by the functions.php file.
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
 * @package Genmod
 * @subpackage Display
 * @version $Id$
 */
if (strstr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class ListFunctions {
	
	private static $indi_total = array();
	private static $indi_hide = array();
	
	public function GetIndiList($allgeds="", $selection = "") {
		global $GEDCOMID, $COMBIKEY;
		
		$indilist = array();
		$sql = "SELECT i_key, i_gedcom, i_isdead, i_id, i_file, n_name, n_surname, n_letter, n_type ";
		$sql .= "FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE n_key=i_key ";
		if ($allgeds == "no") {
			$sql .= "AND i_file = ".$GEDCOMID." ";
			if (!empty($selection)) $sql .= "AND i_key IN (".$selection.") ";
		}
		else if (is_array($allgeds)) {
			$sql .= "AND (";
			$first = true;
			foreach ($allgeds as $key => $ged) {
				if (!$first) $sql .= " OR ";
				$sql .= "i_file='".$ged."'";
			}
			$sql .= ")";
		}
		else if (!empty($selection)) $sql .= "AND i_key IN (".$selection.") ";
	//	$sql .= "ORDER BY i_letter, i_surname ASC";
		$res = NewQuery($sql);
		$ct = $res->NumRows($res->result);
		$key = "";
		while($row = $res->FetchAssoc($res->result)){
			if ($key != $row["i_key"]) {
				$person = null;
				$key = $row["i_key"];
				$person =& Person::GetInstance($row["i_id"], $row);
				self::$indi_total[$row["i_key"]] = 1;
				if ($person->disp_name) {
					$indilist[$row["i_key"]] = $person;
				}
				else {
					self::$indi_hide[$row["i_key"]] = 1;
				}
			}
			$row = db_cleanup($row);
			if ($person->disp_name) $indilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		}
		$res->FreeResult();
		return $indilist;
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
	public function PrintSurnameList($surnames, $page, $allgeds="no", $resturl="") {
		global $TEXT_DIRECTION, $gm_lang, $SHOW_MARRIED_NAMES;
		global $surname_sublist, $indilist;
		
		if (stristr($page, "aliveinyear")) {
			$aiy = true;
			global $indi_dead, $indi_alive, $indi_unborn;
		}
		else $aiy = false;
		
		$i = 0;
		$count_indi = 0;
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
	 			print "<div class =\"rtl\" dir=\"rtl\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=".$surname_sublist."&amp;surname=".urlencode($namecount["name"]).$resturl;
	 			if ($allgeds == "yes") print "&amp;allgeds=yes";
	 			print "\">&nbsp;";
	 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
	 			else print PrintReady($namecount["name"]);
	 			print "&rlm; - [".($namecount["match"])."]&rlm;";
			}
			else if (substr($namecount["name"], 0, 4) == "@N.N") {
				print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".$namecount["alpha"]."&amp;surname_sublist=$surname_sublist&amp;surname=@N.N.".$resturl;
	 			if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\">&nbsp;".$gm_lang["NN"] . "&lrm; - [".($namecount["match"])."]&lrm;&nbsp;";
			}
			else {
				print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=$surname_sublist&amp;surname=".urlencode($namecount["name"]).$resturl;
	 			if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\">";
	 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
				else print PrintReady($namecount["name"]);
				print "&lrm; - [".($namecount["match"])."]&lrm;";
			}
	
	 		print "</a></div>\n";
			$count_indi += $namecount["match"];
			$i++;
			if ($i==$newcol && $i<$count) {
				print "</td><td class=\"shade1 list_value wrap\">\n";
				$newcol=$i+ceil($count/$col);
			}
		}
		if ($aiy) $indi_total = $indi_alive + $indi_dead + $indi_unborn + count(self::$indi_hide);
		else if (is_array(self::$indi_total)) $indi_total = count(self::$indi_total);
		print "</td>\n";
		if ($count>1 || count(self::$indi_hide)>0) {
			print "</tr><tr><td colspan=\"$col\" class=\"center\">&nbsp;";
			if ($SHOW_MARRIED_NAMES && $count>1) print $gm_lang["total_names"]." ".$count_indi."<br />";
			if (isset($indi_total) && $count>1) print $gm_lang["total_indis"]." ".$indi_total."&nbsp;";
			if ($count>1 && count(self::$indi_hide)>0) print "--&nbsp;";
			if (count(self::$indi_hide)>0) print $gm_lang["hidden"]." ".count(self::$indi_hide);
			if ($count>1 && $aiy) {
				print "<br />".$gm_lang["unborn"]."&nbsp;".$indi_unborn;
				print "&nbsp;--&nbsp;".$gm_lang["alive"]."&nbsp;".$indi_alive;
				print "&nbsp;--&nbsp;".$gm_lang["dead"]."&nbsp;".$indi_dead;
			}
			if ($count>1) print "<br />".$gm_lang["surnames"]." ".$count;
			print "</td>\n";
		}
		print "</tr></table>";
	}
	/**
	 * Add a surname to the surnames array for counting
	 *
	 * @param string $nsurname
	 * @return string
	 */
	public function SurnameCount($nsurname, $sort_letter="") {
		global $surnames, $alpha, $surname, $show_all, $i, $testname;
		
		if ($sort_letter == "") $sort_letter = GetFirstLetter($nsurname);
		$lname = StripPrefix($nsurname);
		if (empty($lname)) $lname = $nsurname;
		if (($show_all=="yes") || empty($alpha) || ($alpha==$sort_letter)) {
	//		$tsurname = preg_replace(array("/ [jJsS][rR]\.?/", "/ I+/"), array("",""), $nsurname);
			$tsurname = Str2Upper(StripPrefix(preg_replace("/([^ ]+)\*/", "$1", $nsurname)));
			if (empty($surname) || (Str2Upper($surname)==$tsurname)) {
				if (!isset($surnames[$tsurname])) {
					$surnames[$tsurname] = array();
					$surnames[$tsurname]["name"] = preg_replace("/([^ ]+)\*/", "$1", $nsurname);
					$surnames[$tsurname]["match"] = 1;
					$surnames[$tsurname]["fam"] = 1;
					$surnames[$tsurname]["alpha"] = $sort_letter;
				}
				else {
					$surnames[$tsurname]["match"]++;
					if ($i==0 || $testname != $tsurname) $surnames[$tsurname]["fam"]++;
				}
				if ($i==0) $testname = $tsurname;
			}
			return $nsurname;
		}
		return false;
	}
	
	//-- get the famlist from the datastore
	function GetFamList($allgeds="no", $selection="", $renew=true, $trans=array()) {
		global $GEDCOMID;
		global $COMBIKEY;
	
		$sql = "SELECT * FROM ".TBLPREFIX."families";
		if ($allgeds != "yes") {
			if (!empty($selection)) $sql .= " WHERE f_key IN (".$selection.") ";
			else $sql .= " WHERE f_file='".$GEDCOMID."'";
		}
		else if (!empty($selection)) $sql .= " WHERE f_key IN (".$selection.") ";
	
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		while($row = $res->FetchAssoc()){
			$fam = null;
			$fam = Family::GetInstance($row["f_id"], $row);
			$famlist[$row["f_key"]] = $fam;
		}
		$res->FreeResult();
	
		if (count($famlist) > 0) {
			$select = array();
			foreach ($famlist as $key => $fam) {
				if ($fam->husb_id != "") $select[JoinKey($fam->husb_id, $fam->gedcomid)] = true;
				if ($fam->wife_id != "") $select[JoinKey($fam->wife_id, $fam->gedcomid)] = true;
			}
			if (count($select) > 0) {
				$selection = "";
				foreach ($select as $id => $value) {
					$selection .= "'".$id."', ";
				}
				$selection = substr($selection, 0, strlen($selection)-2);
				self::GetIndilist($allgeds, $selection, false);
			}
		}
		return $famlist;
	}
}
?>
