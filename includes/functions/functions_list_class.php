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
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class ListFunctions {
	
	public static $indi_total = array();
	public static $indi_hide = array();
	public static $fam_total = array();
	public static $fam_hide = array();
	public static $sour_total = array();
	public static $sour_hide = array();
	public static $repo_total = array();
	public static $repo_hide = array();
	
	// $selection is a string with joined keys, quoted and comma separated
	// $applypriv true does not add indi's where disp_name is false to the array
	// $allgeds is either "no", or an array with gedcomid's to search in
	public function GetIndiList($allgeds="", $selection = "", $applypriv=true) {
		global $GEDCOMID;
		
		$indilist = array();
		$sql = "SELECT i_key, i_gedrec, i_isdead, i_id, i_file, n_name, n_surname, n_letter, n_type ";
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
		$sql .= "ORDER BY i_key, n_id ASC";
		$res = NewQuery($sql);
		$ct = $res->NumRows($res->result);
		$key = "";
		while($row = $res->FetchAssoc($res->result)){
			if ($key != $row["i_key"]) {
				if ($key != "") $person->names_read = true;
				$person = null;
				$key = $row["i_key"];
				$person =& Person::GetInstance($row["i_id"], $row);
				self::$indi_total[$row["i_key"]] = 1;
				if (!$applypriv || $person->disp_name) {
					$indilist[$row["i_key"]] = $person;
				}
				else {
					self::$indi_hide[$row["i_key"]] = 1;
				}
			}
			if ($person->disp_name || !$applypriv) $indilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		}
		if ($key != "") $person->names_read = true;
		$res->FreeResult();
		// print "Indis generated: ".count($indilist)."<br />";
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
		global $TEXT_DIRECTION;
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
				print "\">&nbsp;".GM_LANG_NN . "&lrm; - [".($namecount["match"])."]&lrm;&nbsp;";
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
			if (GedcomConfig::$SHOW_MARRIED_NAMES && $count>1) print GM_LANG_total_names." ".$count_indi."<br />";
			if (isset($indi_total) && $count>1) print GM_LANG_total_indis." ".$indi_total."&nbsp;";
			if ($count>1 && count(self::$indi_hide)>0) print "--&nbsp;";
			if (count(self::$indi_hide)>0) print GM_LANG_hidden." ".count(self::$indi_hide);
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
	public function GetFamList($allgeds="no", $selection="", $applypriv=true) {
		global $GEDCOMID;
	
		$famlist = array();
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
			$fam =& Family::GetInstance($row["f_id"], $row);
			$famlist[$row["f_key"]] = $fam;
			self::$fam_total[$row["f_key"]] = 1;
		}
		$res->FreeResult();
	
		if (count($famlist) > 0) {
			$select = array();
			foreach ($famlist as $key => $fam) {
				if ($fam->husb_id != "" && Person::IsInstance($fam->husb_id, $fam->gedcomid) == false) $select[] = JoinKey($fam->husb_id, $fam->gedcomid);
				if ($fam->wife_id != "" && Person::IsInstance($fam->wife_id, $fam->gedcomid) == false) $select[] = JoinKey($fam->wife_id, $fam->gedcomid);
			}
			if (count($select) > 0) {
				array_flip(array_flip($select));
				//print "Indi's selected for fams: ".count($select)."<br />";
				$selection = "'".implode("','", $select)."'";
				self::GetIndilist($allgeds, $selection, false);
			}
			if ($applypriv) {
				foreach($famlist as $key => $fam) {
					if (!$fam->disp) {
						unset($famlist[$key]);
						self::$fam_hide[$key] = 1;
					}
				}
			}
		}
		// print "Fams generated: ".count($famlist)."<br />";
		return $famlist;
	}
	
	public function GetSourceList($selection="", $applypriv=true) {
		global $GEDCOMID, $LINK_PRIVACY;
		
		$links = array();
		$famsel = array();
		$indisel = array();	
		$sourcelist = array();
		
		$sql = "SELECT s_id, s_gedrec, s_file, s_key, sm_sid, sm_gid, sm_type FROM ".TBLPREFIX."sources, ".TBLPREFIX."source_mapping WHERE sm_file='".$GEDCOMID."' AND sm_file=s_file AND s_key=sm_key";
		if (!empty($selection)) $sql .= " AND s_key IN (".$selection.") ";
		$res = NewQuery($sql);
		$oldkey = "";
		while ($row = $res->FetchAssoc()) {
			if ($oldkey != $row["s_key"]) {
				$oldkey = $row["s_key"];
				$source =& Source::GetInstance($row["s_id"], $row, $row["s_file"]);
				$sourcelist[$row["s_key"]] = $source;
				self::$sour_total[$row["s_key"]] = 1;
			}
			$source->addlink = array($row["sm_gid"], $row["sm_type"], $row["s_file"]);
			if ($LINK_PRIVACY) {
				if ($row["sm_type"] == "INDI") $indisel[] = $row["sm_gid"];
				else if ($row["sm_type"] == "FAM") $famsel[] = $row["sm_gid"];
			}
		}
		$res->FreeResult();
		
		if (count($indisel) > 0) {
			array_flip(array_flip($indisel));
			$indiselect = "'".implode("[".$GEDCOMID."]','", $indisel)."[".$GEDCOMID."]'";
			self::GetIndiList("no", $indiselect, false);
		}
		if (count($famsel) > 0) {
			array_flip(array_flip($famsel));
			$famselect = "'".implode ("[".$GEDCOMID."]','", $famsel)."[".$GEDCOMID."]'";
			self::GetFamList("no", $famselect, false);
		}
		if ($applypriv) {
			foreach ($sourcelist as $key => $source) {
				if (!$source->disp) {
					unset($sourcelist[$key]);
					self::$sour_hide[$key] = 1;
				}
			}
		}
		return $sourcelist;
	}

	//-- get the repositorylist from the datastore
	public function GetRepoList($selection="", $applypriv=true) {
		global $GEDCOMID;
		
		$repolist = array();
	
		$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".$GEDCOMID."' AND o_type='REPO'";
		if (!empty($filter)) $sql .= " AND o_gedrec LIKE '%".DbLayer::EscapeQuery($filter)."%'";
		if (!empty($selection)) $sql .= "AND o_id IN (".$selection.") ";
		$resr = NewQuery($sql);
		$ct = $resr->NumRows();
		while($row = $resr->FetchAssoc()){
			$repo = null;
			$key = JoinKey($row["o_id"], $row["o_file"]);
			$repo = Repository::GetInstance($row["o_id"], $row, $row["o_file"]);
			self::$repo_total[$key] = 1;
			if (!$applypriv) $repolist[$key] = $repo;
			else {
				if ($repo->disp) $repolist[$key] = $repo;
				else self::$repo_hide[$key] = 1;
			}
		}
		return $repolist;
	}
}
?>
