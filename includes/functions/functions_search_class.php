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
				$row = db_cleanup($row);
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
			$row = db_cleanup($row);
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
				$row = db_cleanup($row);
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
				$row = db_cleanup($row);
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
	/**
	 * Search the dates table for individuals that had events on the given day
	 *
	 * @param	int $day the day of the month to search for, leave empty to include all
	 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
	 * @param	int $year the year to search for, leave empty to include all
	 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
	 * 				prepend the fact with a ! to not include that fact
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myindilist array with all individuals that matched the query
	 */
	public function SearchIndisDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $filter="all", $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
		
		$indilist = array();
	//print "Dstart: ".$dstart."<br />";
	//print "Mstart: ".$mstart." ".date("M", mktime(1,0,0,$mstart,1))."<br />";
	//print "Dend: ".$dend."<br />";
	//print "Mend: ".$mend." ".date("M", mktime(1,0,0,$mend,1))."<br />";
		$sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_surname, n_letter, n_type FROM ".TBLPREFIX."dates, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=d_key AND n_key=i_key ";
		if ($onlyBDM == "yes") $sql .= " AND d_fact IN ('BIRT', 'DEAT')";
		if ($filter == "living") $sql .= "AND i_isdead!='1'";
	
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
		$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";

		$res = NewQuery($sql);
		$key = "";
		while($row = $res->FetchAssoc($res->result)){
			if ($key != $row["i_key"]) {
				$person = null;
				$key = $row["i_key"];
				$person =& Person::GetInstance($row["i_id"], $row);
				$indilist[$row["i_key"]] = $person;
			}
			$row = db_cleanup($row);
			if ($person->disp_name) $indilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		}
		$res->FreeResult();
		return $indilist;
	}
	
	/**
	 * Search the dates table for families that had events on the given day
	 *
	 * @param	int $day the day of the month to search for, leave empty to include all
	 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
	 * @param	int $year the year to search for, leave empty to include all
	 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
	 * 				prepend the fact with a ! to not include that fact
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myfamlist array with all individuals that matched the query
	 */
	public function SearchFamsDateRange($dstart="1", $mstart="1", $ystart, $dend="31", $mend="12", $yend, $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
		
		$famlist = array();
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."dates, ".TBLPREFIX."families WHERE f_key=d_key ";
	
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
		if ($onlyBDM == "yes") $sql .= " AND d_fact='MARR'";
		$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";
	
		$res = NewQuery($sql);
		while($row = $res->fetchAssoc()) {
			$fam =& Family::GetInstance($row["f_id"], $row);
			$famlist[$row["f_key"]] = $fam;
		}
		$res->FreeResult();
		return $famlist;
	}
	
	/**
	 * Search the dates table for other records that had events on the given day
	 *
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myfamlist array with all individuals that matched the query
	 */
	function SearchOtherDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		$repolist = array();
		
		$sql = "SELECT o_key, o_id, o_file, o_gedrec, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."other WHERE o_id=d_gid AND o_file=d_file AND o_type='REPO' ";
		
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
		$sql .= "GROUP BY o_id ORDER BY d_year, d_month, d_day DESC";
	
		$res = NewQuery($sql);
		while($row = $res->fetchAssoc()){
			$repo = null;
			$repo =& Repository::GetInstance($row["o_id"], $row, $row["o_file"]);
			$repolist[$row["o_key"]] = $repo;
		}
		$res->FreeResult();
		return $repolist;
	}
	/**
	 * Search the dates table for sources that had events on the given day
	 *
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myfamlist array with all individuals that matched the query
	 */
	public function SearchSourcesDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		
		$sourcelist = array();
		
		$sql = "SELECT s_key, s_id, s_name, s_file, s_gedrec, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."sources WHERE s_id=d_gid AND s_file=d_file ";
		
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
		$sql .= "GROUP BY s_id ORDER BY d_year, d_month, d_day DESC";
		
		$res = NewQuery($sql);
		while($row = $res->fetchAssoc()){
			$source = null;
			$source =& Source::GetInstance($row["s_id"], $row, $row["s_file"]);
			$sourcelist[$row["s_key"]] = $source;
		}
		$res->FreeResult();
		return $sourcelist;
	}
	/**
	 * Search the dates table for other records that had events on the given day
	 *
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $mymedia array with all individuals that matched the query
	 */
	function SearchMediaDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		$medialist = array();
		
		$sql = "SELECT m_media, m_mfile, m_file, m_ext, m_titl, m_gedrec FROM ".TBLPREFIX."dates, ".TBLPREFIX."media WHERE m_media=d_gid AND m_file=d_file ";
		
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
		$sql .= "GROUP BY m_media ORDER BY d_year, d_month, d_day DESC";
	
		$res = NewQuery($sql);
	
		while($row = $res->fetchAssoc()){
			$media = null;
			$media =& MediaItem::GetInstance($row["m_media"], $row, $row["m_file"]);
			$medialist[JoinKey($row["m_media"], $row["m_file"])] = $media;
		}
		$res->FreeResult();
		return $medialist;
	}
	
	private function DateRangeforQuery($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		global $GEDCOMID;
		
		//-- Compute start
		$sql = "";
		// SQL for 1 day
		if ($dstart == $dend && $mstart == $mend && $ystart == $yend) {
			$sql .= " AND d_day=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
			if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$ystart."'";
		}
		// SQL for dates in 1 month
		else if ($mstart == $mend && $ystart == $yend) {
			$sql .= " AND d_day BETWEEN ".$dstart." AND ".$dend." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
			if (!empty($ystart) && !empty($yend)) $sql .= " AND d_year='".$ystart."'";
		}
		// SQL for >=2 months
		else {
			$sql .= " AND d_day!='0' AND ((d_day>=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1));
			if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$ystart;
			$sql .= "')";
			//-- intermediate months
			if (!empty($ystart) && !empty($yend)) {
				if ($mend < $mstart) $mend = $mend + 12*($yend-$ystart);
				else $mend = $mend + 12*($yend-$ystart);
			}
			else if ($mend < $mstart) $mend += 12;
			for($i=$mstart+1; $i<$mend;$i++) {
				if ($i>12) {
					$m = $i%12;
					if (!empty($ystart) && !empty($yend)) $y = $ystart + (($i - ($i % 12)) / 12);
				}
				else {
					$m = $i;
					if (!empty($ystart) && !empty($yend)) $y = $ystart;
				}
				$sql .= " OR (d_month='".date("M", mktime(1,0,0,$m,1))."'";
				if (!empty($ystart) && !empty($yend)) $sql .= " AND d_year='".$y."'";
				$sql .= ")";
			}
			//-- End 
			$sql .= " OR (d_day<=".$dend." AND d_month='".date("M", mktime(1,0,0,$mend,1));
			if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$yend;
			$sql .= "')";
			$sql .= ")";
		}
		if (!empty($skipfacts)) {
			$skip = preg_split("/[;, ]/", $skipfacts);
			$sql .= " AND d_fact NOT IN (";
			$i = 0;
			foreach ($skip as $key=>$value) {
				if ($i != 0 ) $sql .= ", ";
				$i++; 
				$sql .= "'".$value."'";
			}
		}
	
		if (!empty($onlyfacts)) {
			$only = preg_split("/[;, ]/", $onlyfacts);
			$sql .= " AND d_fact IN (";
			$i = 0;
			foreach ($only as $key=>$value) {
				if ($i != 0 ) $sql .= ", ";
				$i++; 
				$sql .= "'".$value."'";
			}
			$sql .= ")";
		}
		else $sql .= ")";	
		
		if (!$allgeds) $sql .= " AND d_file='".$GEDCOMID."' ";
		// General part ends here
		
		return $sql;
	}
}
?>