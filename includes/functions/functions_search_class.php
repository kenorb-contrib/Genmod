<?php
/**
 * Database search functions file
 *
 * This file implements the datastore functions necessary for Genmod to use an SQL database as its
 * datastore. This file also implements array caches for the database tables.  Whenever data is
 * retrieved from the database it is stored in a cache.  When a database access is requested the
 * cache arrays are checked first before querying the database.
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
 * @version $Id$
 * @package Genmod
 * @subpackage DB
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class SearchFunctions {

	static public $indi_total = array();
	static public $indi_hide = array();
	static public $fam_total = array();
	static public $fam_hide = array();
	
	//-- search through the gedcom records for individuals
	/**
	 * Search the database for individuals that match the query
	 *
	 * uses full text search
	 *
	 * @author	Genmod Development Team
	 * @param		string $query a regular expression query to search for
	 * @param		boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myindilist array with all individuals that matched the query
	 */
	public function FTSearchIndis($query, $allgeds=false, $ANDOR="AND") {
		global $indilist, $GEDCOMID, $GEDCOMS, $ftminwlen, $ftmaxwlen, $COMBIKEY;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		// if all search terms are below the minimum length, the FT search query will return nothing.
		// if all positive short terms with + are added as AND, nothing will be returned either.
		// so, if all positive search terms are shorter than min len, we must omit FT search anyway.	
		
		$myindilist = array();
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
	
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (!Utf8_isascii($keyword["term"])) $addsql .= " ".$keyword["operator"]." i_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." i_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (!Utf8_isascii($keyword["term"])) $addsql .= " AND i_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND i_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (MATCH (i_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
			
		if (!$allgeds) $sql .= " AND i_file='".$GEDCOMID."'";
	
		if ((is_array($allgeds)) && (count($allgeds) != 0)) {
			if (count($allgeds) != count($GEDCOMS)) {
				$sql .= " AND (";
				for ($i=0; $i<count($allgeds); $i++) {
					$sql .= "i_file='".$allgeds[$i]."'";
					if ($i < count($allgeds)-1) $sql .= " OR ";
				}
				$sql .= ")";
			}
		}
	
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchAssoc()){
				if ($COMBIKEY) $key = $row["i_key"];
				else $key = $row["i_id"];
				if (!isset($myindilist[$key])) {
					$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
					$myindilist[$key]["gedfile"] = $row["i_file"];
					$myindilist[$key]["gedcom"] = $row["i_gedrec"];
					$myindilist[$key]["isdead"] = $row["i_isdead"];
				}
				else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$indilist[$key] = $myindilist[$key];
			}
			$res->FreeResult();
		}
		return $myindilist;
	}

	// Get the minimum and maximum Full Text Search term lengths
	private function GetFTWordLengths() {
		global $ftminwlen, $ftmaxwlen;
		
		if (!isset($ftminwlen)) {
			$sql = "SHOW VARIABLES LIKE '%ft_min_word_len%'";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$ftminwlen = $row[1];
			$res->FreeResult();
		}
		if (!isset($ftmaxwlen)) {
			$sql = "SHOW VARIABLES LIKE '%ft_max_word_len%'";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$maxwlen = $row[1];
			$res->FreeResult();
		}
	}	

	//-- search through the gedcom records for families
	public function FTSearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
		global $famlist, $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen, $COMBIKEY;
	
		// Get the min and max search word length
		self::GetFTWordLengths();
		
		$myfamlist = array();
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." f_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." f_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " AND f_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND f_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (MATCH (f_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
		
		if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";
	
		if ((is_array($allgeds)) && (count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0, $max=count($allgeds); $i<$max; $i++) {
				$sql .= "f_file='".$allgeds[$i]."'";
				if ($i < $max-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	
		$res = NewQuery($sql);
		$gedold = $GEDCOMID;
		while($row = $res->fetchAssoc()){
			$GEDCOMID = $row["f_file"];
			$husb = SplitKey($row["f_husb"], "id");
			$wife = SplitKey($row["f_wife"], "id");
			if ($allnames == true) {
				$hname = GetSortableName($husb, "", "", true);
				$wname = GetSortableName($wife, "", "", true);
				if (empty($hname)) $hname = "@N.N.";
				if (empty($wname)) $wname = "@N.N.";
				$name = array();
				foreach ($hname as $hkey => $hn) {
					foreach ($wname as $wkey => $wn) {
						$name[] = $hn." + ".$wn;
						$name[] = $wn." + ".$hn;
					}
				}
			}
			else {
				$hname = GetSortableName($husb);
				$wname = GetSortableName($wife);
				if (empty($hname)) $hname = "@N.N.";
				if (empty($wname)) $wname = "@N.N.";
				$name = $hname." + ".$wname;
			}
			if (count($allgeds) > 1 || $COMBIKEY) $key = $row["f_key"];
			else $key = $row["f_id"];
			$myfamlist[$key]["name"] = $name;
			$myfamlist[$key]["gedfile"] = $row["f_file"];
			$myfamlist[$key]["gedcom"] = $row["f_gedrec"];
			$myfamlist[$key]["HUSB"] = $husb;
			$myfamlist[$key]["WIFE"] = $wife;
			$famlist[$key] = $myfamlist[$key];
		}
		$GEDCOMID = $gedold;
		$res->FreeResult();
		return $myfamlist;
	}

	//-- search through the gedcom records for sources, full text
	public function FTSearchSources($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$mysourcelist = array();	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." s_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." s_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " AND s_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND s_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT s_id, s_name, s_file, s_gedrec FROM ".TBLPREFIX."sources WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT s_id, s_name, s_file, s_gedrec FROM ".TBLPREFIX."sources WHERE (MATCH (s_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND s_file='".$GEDCOMID."'";
	
		if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "s_file='".$allgeds[$i]."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->fetchRow()){
				if (count($allgeds) > 1) {
					$mysourcelist[$row[0]."[".$row[2]."]"]["name"] = $row[1];
					$mysourcelist[$row[0]."[".$row[2]."]"]["gedfile"] = $row[2];
					$mysourcelist[$row[0]."[".$row[2]."]"]["gedcom"] = $row[3];
				}
				else {
					$mysourcelist[$row[0]]["name"] = $row[1];
					$mysourcelist[$row[0]]["gedfile"] = $row[2];
					$mysourcelist[$row[0]]["gedcom"] = $row[3];
				}
			}
			$res->FreeResult();
		}
		return $mysourcelist;
	}

	//-- search through the gedcom records for repositories, full text
	public function FTSearchRepos($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$myrepolist = array();	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " AND o_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND o_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT o_id, o_file, o_type, o_gedrec FROM ".TBLPREFIX."other WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT o_id, o_file, o_type, o_gedrec FROM ".TBLPREFIX."other WHERE (MATCH (o_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND o_file='".$GEDCOMID."'";
		
		$sql .= " AND o_type='REPO'";
	
		if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "o_file='".$allgeds[$i]."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->fetchAssoc()){
				$tt = preg_match("/1 NAME (.*)/", $row["o_gedrec"], $match);
				if ($tt == "0") $name = $row["o_id"]; else $name = $match[1];
				if (count($allgeds) > 1) {
					$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["id"] = $row["o_id"];
					$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["name"] = $name;
					$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["gedfile"] = $row["o_file"];
					$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["gedcom"] = $row["o_gedrec"];
				}
				else {
					$myrepolist[$row["o_id"]]["id"] = $row["o_id"];
					$myrepolist[$row["o_id"]]["name"] = $name;
					$myrepolist[$row["o_id"]]["gedfile"] = $row["o_file"];
					$myrepolist[$row["o_id"]]["gedcom"] = $row["o_gedrec"];
				}
			}
			$res->FreeResult();
		}
		return $myrepolist;
	}

	//-- search through the gedcom records for media, full text
	public function FTSearchMedia($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen, $media_hide, $media_total;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." m_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." m_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " AND m_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND m_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT * FROM ".TBLPREFIX."media WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT * FROM ".TBLPREFIX."media WHERE (MATCH (m_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND m_file='".$GEDCOMID."'";
		
		if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "m_file='".$allgeds[$i]."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	
		$media_total = array();
		$media_hide = array();
		$medialist = array();
		$res = NewQuery($sql);
		if ($res) {
	 		while ($row = $res->FetchAssoc()) {
		 		$media = array();
		 		SwitchGedcom($row["m_file"]);
				$media_total[$row["m_media"]."[".$GEDCOMID."]"] = 1;
		 		if (PrivacyFunctions::DisplayDetailsByID($row["m_media"], "OBJE", 1, true)) {
					$media = array();
					$media["gedfile"] = $GEDCOMID;
					$media["name"] = GetMediaDescriptor($row["m_media"], $row["m_gedrec"]);
					$medialist[JoinKey($row["m_media"], $row["m_file"])] = $media;
	 			}
		 		else $media_hide[$row["m_media"]."[".$GEDCOMID."]"] = 1;
		 		SwitchGedcom();
	 		}
		}
		return $medialist;
	}
	
	//-- search through the gedcom records for notes, full text
	public function FTSearchNotes($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen, $note_hide, $note_total;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " AND o_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND o_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT * FROM ".TBLPREFIX."other WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT * FROM ".TBLPREFIX."other WHERE (MATCH (o_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND o_file='".$GEDCOMID."'";
		
		$sql .= " AND o_type='NOTE'";
	
		if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "o_file='".$allgeds[$i]."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	
		$note_total = array();
		$note_hide = array();
		$notelist = array();
		$res = NewQuery($sql);
		if ($res && $res->NumRows() > 0) {
	 		while ($row = $res->FetchAssoc()) {
		 		$note =& Note::GetInstance($row["o_id"], $row, $row["o_file"]);
		 		$note->GetTitle(40);
		 		SwitchGedcom($row["o_file"]);
				$note_total[$note->xref."[".$GEDCOMID."]"] = 1;
		 		if ($note->disp) $notelist[] = $note;
		 		else $note_hide[$note->xref."[".$GEDCOMID."]"] = 1;
		 		SwitchGedcom();
	 		}
	 		usort($notelist, "TitleObjSort");
		}
		return $notelist;
	}

	public function ParseFTSearchQuery($query) {
		$cquery = array();
		$cquery["includes"] = array();
		$cquery["excludes"] = array();
		
		
		// First extract the strings within quotes
		$ct = preg_match_all("/([+-]*\"[\w\s]*\")/", $query, $qstring);
		foreach ($qstring[0] as $key => $strquery) {
			$cquery[] = $strquery;
		}
		$query = preg_replace("/([+-]*\"[\w\s]*\")/", "", $query);
		
		// Then extract other keywords not in quotes
		$squery = preg_split("/\s/", $query);
		foreach($squery as $key => $strquery) {
			$strquery = trim ($strquery);
			if (!empty($strquery)) $cquery[] = $strquery;
		}
		foreach($cquery as $qindex => $squery) {
			if (empty($cquery[$qindex])) unset($cquery[$qindex]);
			else {
				if (substr($squery,-1) == "*") $wildcard = ".*";
				else $wildcard = "";
				if (substr($squery, 0, 1) == "-") {
					$term = preg_replace("/[+<>\*\"\~\?]/", "", substr($squery,1));
					if (!empty($term)) $cquery["excludes"][] = array("term"=>$term, "operator"=>"NOT", "wildcard"=>$wildcard);
				}
				else {
					if (substr($squery, 0, 1) == "+") $operator = "AND";
					else $operator = "OR";
					$term = preg_replace("/[+<>\*\"\~\?]/", "", $squery);
					if (!empty($term)) $cquery["includes"][] = array("term"=>$term, "operator"=>$operator, "wildcard"=>$wildcard);
				}
			}
		}
		return $cquery;
	}
	
	private function HasMySQLStopwords($cquery) {
		
		$stopwords = array("a's","able","about","above","according","accordingly","across","actually","after","afterwards","again","against","ain't","all","allow","allows","almost","alone","along","already","also","although","always","am","among","amongst","an","and","another","any","anybody","anyhow","anyone","anything","anyway","anyways","anywhere","apart","appear","appreciate","appropriate","are","aren't","around","as","aside","ask","asking","associated","at","available","away","awfully","be","became","because","become","becomes","becoming","been","before","beforehand","behind","being","believe","below","beside","besides","best","better","between","beyond","both","brief","but","by","c'mon","c's","came","can","can't","cannot","cant","cause","causes","certain","certainly","changes","clearly","co","com","come","comes","concerning","consequently","consider","considering","contain","containing","contains","corresponding","could","couldn't","course","currently","definitely","described","despite","did","didn't","different","do","does","doesn't","doing","don't","done","down","downwards","during","each","edu","eg","eight","either","else","elsewhere","enough","entirely","especially","et","etc","even","ever","every","everybody","everyone","everything","everywhere","ex","exactly","example","except","far","few","fifth","first","five","followed","following","follows","for","former","formerly","forth","four","from","further","furthermore","get","gets","getting","given","gives","go","goes","going","gone","got","gotten","greetings","had","hadn't","happens","hardly","has","hasn't","have","haven't","having","he","he's","hello","help","hence","her","here","here's","hereafter","hereby","herein","hereupon","hers","herself","hi","him","himself","his","hither","hopefully","how","howbeit","however","i'd","i'll","i'm","i've","ie","if","ignored","immediate","in","inasmuch","inc","indeed","indicate","indicated","indicates","inner","insofar","instead","into","inward","is","isn't","it","it'd","it'll","it's","its","itself","just","keep","keeps","kept","know","knows","known","last","lately","later","latter","latterly","least","less","lest","let","let's","like","liked","likely","little","look","looking","looks","ltd","mainly","many","may","maybe","me","mean","meanwhile","merely","might","more","moreover","most","mostly","much","must","my","myself","name","namely","nd","near","nearly","necessary","need","needs","neither","never","nevertheless","new","next","nine","no","nobody","non","none","noone","nor","normally","not","nothing","novel","now","nowhere","obviously","of","off","often","oh","ok","okay","old","on","once","one","ones","only","onto","or","other","others","otherwise","ought","our","ours","ourselves","out","outside","over","overall","own","particular","particularly","per","perhaps","placed","please","plus","possible","presumably","probably","provides","que","quite","qv","rather","rd","re","really","reasonably","regarding","regardless","regards","relatively","respectively","right","said","same","saw","say","saying","says","second","secondly","see","seeing","seem","seemed","seeming","seems","seen","self","selves","sensible","sent","serious","seriously","seven","several","shall","she","should","shouldn't","since","six","so","some","somebody","somehow","someone","something","sometime","sometimes","somewhat","somewhere","soon","sorry","specified","specify","specifying","still","sub","such","sup","sure","t's","take","taken","tell","tends","th","than","thank","thanks","thanx","that","that's","thats","the","their","theirs","them","themselves","then","thence","there","there's","thereafter","thereby","therefore","therein","theres","thereupon","these","they","they'd","they'll","they're","they've","think","third","this","thorough","thoroughly","those","though","three","through","throughout","thru","thus","to","together","too","took","toward","towards","tried","tries","truly","try","trying","twice","two","un","under","unfortunately","unless","unlikely","until","unto","up","upon","us","use","used","useful","uses","using","usually","value","various","very","via","viz","vs","want","wants","was","wasn't","way","we","we'd","we'll","we're","we've","welcome","well","went","were","weren't","what","what's","whatever","when","whence","whenever","where","where's","whereafter","whereas","whereby","wherein","whereupon","wherever","whether","which","while","whither","who","who's","whoever","whole","whom","whose","why","will","willing","wish","with","within","without","won't","wonder","would","would","wouldn't","yes","yet","you","you'd","you'll","you're","you've","your","yours","yourself","yourselves","zero");
		
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				$subkeywords = preg_split("/\s/", $keyword["term"]);
				foreach($subkeywords as $key => $subkeyword) {
					if (in_array(strtolower($subkeyword), $stopwords)) return true;
				}
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				$subkeywords = preg_split("/\s/", $keyword["term"]);
				foreach($subkeywords as $key => $subkeyword) {
					$subkeyword = trim ($subkeyword);
					if (in_array(strtolower($subkeyword), $stopwords)) return true;
				}
			}
		}
		return false;
	}
	
	private function GetFTMinLen($cquery) {
		
		$minwlen = 9999;
		$chinese = false;
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				$subkeywords = preg_split("/\s/", $keyword["term"]);
				foreach($subkeywords as $key => $subkeyword) {
					$subkeyword = trim ($subkeyword);
					$len = utf8_strlen($subkeyword);
					if (HasChinese($subkeyword)) $chinese = true;
					if ($len < $minwlen) $minwlen = $len;
				}
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				$subkeywords = preg_split("/\s/", $keyword["term"]);
				foreach($subkeywords as $key => $subkeyword) {
					$subkeyword = trim ($subkeyword);
					$len = utf8_strlen($subkeyword);
					if (HasChinese($subkeyword)) $chinese = true;
					if ($len < $minwlen) $minwlen = $len;
				}
			}
		}
		if ($chinese) return 1;
		else return $minwlen;
	}
	
	// Used in search.php
	public function SearchAddAssos() {
		global $assolist, $indi_printed, $fam_printed, $printindiname, $printfamname, $famlist;
	
		// Step 1: Pull the relations to the printed results from the assolist
		$toadd = array();
		foreach ($assolist as $p1key => $assos) {
			// Get the person who might be a relation
			// Check if we can show him/her
			SwitchGedcom(SplitKey($p1key, "gedid"));
			foreach ($assos as $key => $asso) {
				$p2key = $asso["pid2"];
				// Design choice: we add to->from and from->to
				// Check if he/she  and the persons/fams he/she is related to, actually exist
				// Also respect name privacy for all related individuals. If one is hidden, it prevents the relation to be displayed
				// p1key can be either indi or fam, p2key can only be indi.
				if (array_key_exists($p2key, $indi_printed) || array_key_exists($p1key, $indi_printed) || array_key_exists($p1key, $fam_printed)) {
					// p2key is always an indi, so check this first
					$disp = true;
					if (!PrivacyFunctions::showLivingNameByID(SplitKey($p2key, "id"))) $disp = false;
					else {
						if (!empty($asso["fact"]) &&!PrivacyFunctions::showFact($asso["fact"], SplitKey($p2key, "id"))) $disp = false;
						else {
							// assotype is the type of p1key
							if ($asso["type"] == "indi") {
								if (!PrivacyFunctions::showLivingNameByID(SplitKey($p1key, "id"))) $disp = false;
							}
							else {
								$parents = FindParentsInRecord($asso["gedcom"]);
								if (!PrivacyFunctions::showLivingNameByID($parents["HUSB"]) || !PrivacyFunctions::showLivingNameByID($parents["WIFE"])) $disp = false;
							}
						}
					}
					if ($disp && !empty($asso["resn"])) {
						if (!empty($asso["fact"])) {
							$rec = "1 ".$fact."\r\n2 ASSO @".SplitKey($p1key, "id")."@\r\n2 RESN ".$asso["resn"]."\r\n";
							$disp = PrivacyFunctions::FactViewRestricted(SplitKey($p2key, "id"), $rec, 2);
						}
						else {
							$rec = "1 ASSO @".SplitKey($p1key, "id")."@\r\n2 RESN ".$asso["resn"]."\r\n";
							$disp = PrivacyFunctions::FactViewRestricted(SplitKey($p2key, "id"), $rec, 2);
						}
					}
					// save his relation to existing search results
					if ($disp) {
						$toadd[$p1key][] = array($p2key, "indi", $asso["fact"], $asso["role"]);
						$toadd[$p2key][] = array($p1key, $asso["type"], $asso["fact"], $asso["role"]);
					}
				}
			}
		}
	
		// Step 2: Add the relations who are not printed themselves
		foreach ($toadd as $add => $links) {
			$arec = FindGedcomRecord(SplitKey($add, "id"));
			$type = GetRecType($arec);
			if ($type == "INDI") {
				if (!array_key_exists($add, $indi_printed)) {
					$indi_printed[$add] = "1";
					$names = GetIndiNames($arec);
					foreach ($names as $nkey => $namearray) {
						$printindiname[] = array(NameFunctions::SortableNameFromName($namearray[0]), SplitKey($add, "id"), SplitKey($add, "gedid"), "");
					}
				}
			}
			else {
				if (!array_key_exists($add, $fam_printed)) {
					$fam_printed[$add] = "1";
					$fam = $famlist[$add];
					$hname = GetSortableName($fam["HUSB"], "", "", true);
					$wname = GetSortableName($fam["WIFE"], "", "", true);
					if (empty($hname)) $hname = "@N.N.";
					if (empty($wname)) $wname = "@N.N.";
					$name = array();
					foreach ($hname as $hkey => $hn) {
						foreach ($wname as $wkey => $wn) {
							$name[] = $hn." + ".$wn;
							$name[] = $wn." + ".$hn;
						}
					}
					foreach ($name as $namekey => $famname) {
						$famsplit = preg_split("/(\s\+\s)/", trim($famname));
						// Both names have to have the same direction and combination of chinese/not chinese
						if (hasRTLText($famsplit[0]) == hasRTLText($famsplit[1]) && HasChinese($famsplit[0], true) == HasChinese($famsplit[1], true)) {
							$printfamname[]=array(CheckNN($famname), SplitKey($add, "id"), $fam["gedfile"],"");
						}
					}
				}
			}
		}
	
		// Step 3: now cycle through the indi search results to add a relation link
		foreach ($printindiname as $pkey => $printindi) {
			$pikey = JoinKey($printindi[1], get_id_from_gedcom($printindi[2]));
			if (isset($toadd[$pikey])) {
				foreach ($toadd[$pikey] as $rkey => $asso) {
					SwitchGedcom($printindi[2]);
					$printindiname[$pkey][3][] = $asso;
				}
			}
		}
		// Step 4: now cycle through the fam search results to add a relation link
		foreach ($printfamname as $pkey => $printfam) {
			$pikey = JoinKey($printfam[1], get_id_from_gedcom($printfam[2]));
			if (isset($toadd[$pikey])) {
				foreach ($toadd[$pikey] as $rkey => $asso) {
					SwitchGedcom($printfam[2]);
					$printfamname[$pkey][3][] = $asso;
				}
			}
		}
		SwitchGedcom();
	}
	// Used in calendar.php
	public function SearchIndisYearRange($startyear, $endyear, $allgeds=false, $type="", $est="") {
		global $GEDCOMID;
	
		$myindilist = array();
		$sql = "SELECT DISTINCT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_surname, n_letter, n_type FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."names ON i_key=n_key INNER JOIN ".TBLPREFIX."dates ON (i_key=d_key AND d_fact<>'CHAN') WHERE";
		if ($startyear < $endyear) $sql .= " d_year>='".$startyear."' AND d_year<='".$endyear."'";
		else $sql .= " d_year=".$startyear;
		if (!empty($type)) $sql .= " AND d_fact IN ".$type;
		if (!$allgeds) $sql .= " AND i_file='".$GEDCOMID."'";
//		print $sql;
		$res = NewQuery($sql);
		
		$key = "";
		while($row = $res->FetchAssoc($res->result)){
			if ($key != $row["i_key"]) {
				if ($key != "") $person->names_read = true;
				$person = null;
				$key = $row["i_key"];
				$person =& Person::GetInstance($row["i_id"], $row);
				if ($person->disp_name) {
					self::$indi_total[$row["i_key"]] = 1;
					$myindilist[$row["i_key"]] = $person;
				}
				else self::$indi_hide[$key] = 1;
			}
			if ($person->disp_name) $myindilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		}
		if ($key != "") $person->names_read = true;
		
		$res->FreeResult();
		return $myindilist;
	}
	
	/**
	 * Search through the gedcom records for families with daterange
	 * Used in:	calendar.php
	 *
	 * @package Genmod
	 * @subpackage Calendar
	**/
	public function SearchFamsYearRange($startyear, $endyear, $allgeds=false, $type="") {
		global $GEDCOMID;
	
		$myfamlist = array();
		$sql = "SELECT DISTINCT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families INNER JOIN ".TBLPREFIX."dates ON (d_key=f_key AND d_fact<>'CHAN') WHERE";
		if ($endyear > $startyear) $sql .= " d_year>='".$startyear."' AND d_year<='".$endyear."'";
		else $sql .= " d_year=".$startyear;
/*		$i=$startyear;
		while($i <= $endyear) {
			if ($i > $startyear) $sql .= " OR ";
			$sql .= "f_gedrec REGEXP '".DbLayer::EscapeQuery("2 DATE[^\n]* ".$i)."'";
			$i++;
		}
		$sql .= ")";
*/		if (!empty($type)) $sql .= " AND d_fact IN ".$type;
		if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		
		$select = array();
		while($row = $res->FetchAssoc()){
			if (!empty($row["f_husb"])) $select[] = $row["f_husb"];
			if (!empty($row["f_wife"])) $select[] = $row["f_wife"];
			$fam = null;
			$fam =& Family::GetInstance($row["f_id"], $row);
			$myfamlist[$row["f_key"]] = $fam;
//			if ($fam->disp) $myfamlist[$row["f_key"]] = $fam;
//			else self::$fam_hide[$row["f_key"]] = 1;
//			self::$fam_total[$row["f_key"]] = 1;
		}
		$res->FreeResult();
	
		if (count($select) > 0) {
			array_flip(array_flip($select));
			//print "Indi's selected for fams: ".count($select)."<br />";
			$selection = "'".implode("','", $select)."'";
			ListFunctions::GetIndilist($allgeds, $selection, false);
		}
		
		$res->FreeResult();
		return $myfamlist;
	}
	
	/**
	 * Search the database for individuals that match the query
	 * Used in:	calendar.php
	 *			find.php
	 *			search.php
	 *			reportpdf.php
	 *
	 * uses a regular expression to search the gedcom records of all individuals and returns an
	 * array list of the matching individuals
	 *
	 * @author	Genmod Development Team
	 * @param		string $query a regular expression query to search for
	 * @param		boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myindilist array with all individuals that matched the query
	 */
	public function SearchIndis($query, $allgeds=false, $ANDOR="AND", $type="") {
		global $GEDCOMS, $GEDCOMID;
		
		$myindilist = array();
		$sql = "SELECT DISTINCT(i_key), i_id, i_file, i_gedrec, i_isdead FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."dates on (d_key=i_key AND d_fact<>'CHAN') WHERE (d_year='".$query."' OR d_ext='BET')";
		if (!empty($type)) $sql .= " AND d_fact IN ".$type;
/*		if (!is_array($query)) $sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead FROM ".TBLPREFIX."individuals WHERE (i_gedrec REGEXP '".DbLayer::EscapeQuery($query)."')";
		else {
			$sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead FROM ".TBLPREFIX."individuals WHERE (";
			$i=0;
			foreach($query as $indexval => $q) {
				if ($i>0) $sql .= " $ANDOR ";
				$sql .= "(i_gedrec REGEXP '".DbLayer::EscapeQuery($q)."')";
				$i++;
			}
			$sql .= ")";
		}
*/		if (!$allgeds) $sql .= " AND i_file='".$GEDCOMID."'";
	
		if ((is_array($allgeds)) && (count($allgeds) != 0)) {
			if (count($allgeds) != count($GEDCOMS)) {
				$sql .= " AND (";
				for ($i=0; $i<count($allgeds); $i++) {
					$sql .= "i_file='".$allgeds[$i]."'";
					if ($i < count($allgeds)-1) $sql .= " OR ";
				}
				$sql .= ")";
			}
		}
//		print $sql;
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchAssoc()){
				$person =& Person::GetInstance($row["i_id"], $row);
				if ($person->disp) {
					self::$indi_total[$row["i_key"]] = 1;
					$myindilist[$row["i_key"]] = $person;
				}
				else {
					//print "hidden: ".$row["i_key"]."<br />";
					self::$indi_hide[$row["i_key"]] = 1;
				}
			}
			$res->FreeResult();
		}
		return $myindilist;
	}
	
	/**
	 * Search through the gedcom records for families with daterange
	 * Used in:	calendar.php
	 *			find.php
	 *			search.php
	 *			reportpdf.php
	 * @package Genmod
	 * @subpackage Calendar
	**/
	public function SearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false, $type="") {
		global $GEDCOMID;
	
	
		$myfamlist = array();
		$sql = "SELECT DISTINCT f_key, f_id, f_file, f_gedrec, f_wife, f_husb FROM ".TBLPREFIX."families INNER JOIN ".TBLPREFIX."dates on (d_key=f_key AND d_fact<>'CHAN') WHERE (d_year='".$query."' OR d_ext='BET')";
		if (!empty($type)) $sql .= " AND d_fact IN ".$type;
/*		if (!is_array($query)) $sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (f_gedrec REGEXP '".DbLayer::EscapeQuery($query)."')";
		else {
			$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (";
			$i=0;
			foreach($query as $indexval => $q) {
				if ($i>0) $sql .= " $ANDOR ";
				$sql .= "(f_gedrec REGEXP '".DbLayer::EscapeQuery($q)."')";
				$i++;
			}
			$sql .= ")";
		}
*/		
		if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";
	
		if ((is_array($allgeds)) && (count($allgeds) != 0)) {
			$sql .= " AND (";
			for ($i=0, $max=count($allgeds); $i<$max; $i++) {
				$sql .= "f_file='".$allgeds[$i]."'";
				if ($i < $max-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
		
		$res = NewQuery($sql);
		
		$select = array();
		while($row = $res->FetchAssoc()){
			$fam = null;
			$fam =& Family::GetInstance($row["f_id"], $row);
			if (!empty($row["f_husb"])) $select[] = $row["f_husb"];
			if (!empty($row["f_wife"])) $select[] = $row["f_wife"];
			$myfamlist[$row["f_key"]] = $fam;
			self::$fam_total[$row["f_key"]] = 1;
		}
		$res->FreeResult();
	
		if (count($myfamlist) > 0) {
			if (count($select) > 0) {
				array_flip(array_flip($select));
				//print "Indi's selected for fams: ".count($select)."<br />";
				$selection = "'".implode("','", $select)."'";
				ListFunctions::GetIndilist($allgeds, $selection, false);
			}
		}
		foreach ($myfamlist as $index => $fam) {
			if (!$fam->disp) {
				self::$fam_hide[$fam->key] = 1;
				unset($myfamlist[$index]);
			}
		}
				
		
		return $myfamlist;
	}
	
	/**
	 * Search the dates table for individuals that had events on the given day
	 * Used in:	calendar.php
	 *
	 * @param	int $day the day of the month to search for, leave empty to include all
	 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
	 * @param	int $year the year to search for, leave empty to include all
	 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
	 * 				prepend the fact with a ! to not include that fact
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myindilist array with all individuals that matched the query
	 */
	public function SearchIndisDates($day="", $month="", $year="", $fact="", $allgeds=false, $ANDOR="AND") {
		global $GEDCOMID;
		
		$myindilist = array();
		
		$sql = "SELECT DISTINCT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_surname, n_type, n_letter FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."names ON n_key=i_key INNER JOIN ".TBLPREFIX."dates ON (d_key=i_key AND d_fact<>'CHAN') WHERE";
		$and = "";
		if (!empty($day)) {
			$sql .= " d_day='".DbLayer::EscapeQuery($day)."'";
			$and = " AND";
		}
		if (!empty($month)) {
			$sql .= $and." d_month='".DbLayer::EscapeQuery($month)."'";
			$and = " AND";
		}
		if (!empty($year)) {
			$sql .= $and." d_year='".DbLayer::EscapeQuery($year)."'";
			$and = " AND";
		}

/*		if (!empty($fact)) {
			$sql .= "AND (";
			$facts = preg_split("/[,:; ]/", $fact);
			$i=0;
			foreach($facts as $fact) {
				if ($i!=0) $sql .= " OR ";
				$ct = preg_match("/!(\w+)/", $fact, $match);
				if ($ct > 0) {
					$fact = $match[1];
					$sql .= "d_fact!='".DbLayer::EscapeQuery($fact)."'";
				}
				else {
					$sql .= "d_fact='".DbLayer::EscapeQuery($fact)."'";
				}
				$i++;
			}
			$sql .= ") ";
		}
*/		
		if (!$allgeds) {
			$sql .= $and." d_file='".$GEDCOMID."'";
			$and = " AND";
		}
		if (!empty($fact)) $sql .= $and." d_fact IN ".$fact;
//		$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";
//print $sql;
		$res = NewQuery($sql);
		if ($res) {
			$key = "";
			while($row = $res->FetchAssoc($res->result)){
				if ($key != $row["i_key"]) {
					if ($key != "") $person->names_read = true;
					$person = null;
					$key = $row["i_key"];
					$person =& Person::GetInstance($row["i_id"], $row);
					if ($person->disp_name) {
						self::$indi_total[$row["i_key"]] = 1;
						$myindilist[$row["i_key"]] = $person;
					}
					else self::$indi_hide[$key] = 1;
				}
				if ($person->disp_name) $myindilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			}
			if ($key != "") $person->names_read = true;
		}
		return $myindilist;
	}

	/**
	 * Search the dates table for families that had events on the given day
	 * Used in:	calendar.php
	 *
	 * @param	int $day the day of the month to search for, leave empty to include all
	 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
	 * @param	int $year the year to search for, leave empty to include all
	 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
	 * 				prepend the fact with a ! to not include that fact
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myfamlist array with all individuals that matched the query
	 */
	public function SearchFamsDates($day="", $month="", $year="", $fact="", $allgeds=false) {
		global $famlist, $GEDCOMS, $GEDCOMID;
		$myfamlist = array();
		
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec, d_gid, d_fact FROM ".TBLPREFIX."dates, ".TBLPREFIX."families WHERE f_key=d_key";
		if (!empty($day)) $sql .= " AND d_day='".DbLayer::EscapeQuery($day)."'";
		if (!empty($month)) $sql .= " AND d_month='".DbLayer::EscapeQuery(Str2Upper($month))."'";
		if (!empty($year)) $sql .= " AND d_year='".DbLayer::EscapeQuery($year)."'";
		if (!empty($fact)) {
			$sql .= " AND d_fact IN ".$fact;
/*			$sql .= "AND (";
			$facts = preg_split("/[,:; ]/", $fact);
			$i=0;
			foreach($facts as $fact) {
				if ($i!=0) $sql .= " OR ";
				$ct = preg_match("/!(\w+)/", $fact, $match);
				if ($ct > 0) {
					$fact = $match[1];
					$sql .= "d_fact!='".DbLayer::EscapeQuery(Str2Upper($fact))."'";
				}
				else {
					$sql .= "d_fact='".DbLayer::EscapeQuery(Str2Upper($fact))."'";
				}
				$i++;
			}
			$sql .= ") ";
*/		}
		if (!$allgeds) $sql .= " AND d_file='".$GEDCOMID."'";
//		$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";
		
		$res = NewQuery($sql);
		$select = array();
		
		while($row = $res->FetchAssoc()){
			$fam = null;
			$fam =& Family::GetInstance($row["f_id"], $row);
			if (!empty($row["f_husb"])) $select[] = $row["f_husb"];
			if (!empty($row["f_wife"])) $select[] = $row["f_wife"];
			$myfamlist[$row["f_key"]] = $fam;
			self::$fam_total[$row["f_key"]] = 1;
		}
		$res->FreeResult();
	
		if (count($myfamlist) > 0) {
			if (count($select) > 0) {
				array_flip(array_flip($select));
				//print "Indi's selected for fams: ".count($select)."<br />";
				$selection = "'".implode("','", $select)."'";
				ListFunctions::GetIndilist($allgeds, $selection, false);
			}
		}
		foreach ($myfamlist as $index => $fam) {
			if (!$fam->disp) {
				self::$fam_hide[$fam->key] = 1;
				unset($myfamlist[$index]);
			}
		}
				
		
		return $myfamlist;
	}

}
?>