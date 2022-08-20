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
 * @version $Id: functions_search_class.php 29 2022-07-17 13:18:20Z Boudewijn $
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
		global $GEDCOMS, $ftminwlen, $ftmaxwlen;
		
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
			$sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_letter, n_fletter, n_type, n_surname, n_nick FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_letter, n_fletter, n_type, n_surname, n_nick FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (MATCH (i_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
			
		if (!$allgeds) $sql .= " AND i_file='".GedcomConfig::$GEDCOMID."'";
	
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
		$sql .= "  ORDER BY i_key, n_id";
	
		$res = NewQuery($sql);
		if ($res) {
			$key = "";
			while($row = $res->FetchAssoc()){
				if ($key != $row["i_key"]) {
					if ($key != "") $person->names_read = true;
					$person = null;
					$key = $row["i_key"];
					$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
					$myindilist[$row["i_key"]] = $person;
				}
				$myindilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
			}
			if ($key != "") $person->names_read = true;
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
		global $GEDCOMS, $ftminwlen, $ftmaxwlen;
	
		// Get the min and max search word length
		self::GetFTWordLengths();
		
		$myfamlist = array();
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " ".$keyword["operator"]." f_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." f_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " AND f_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND f_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (MATCH (f_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
		
		if (!$allgeds) $sql .= " AND f_file='".GedcomConfig::$GEDCOMID."'";
	
		if ((is_array($allgeds)) && (count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0, $max=count($allgeds); $i<$max; $i++) {
				$sql .= "f_file='".$allgeds[$i]."'";
				if ($i < $max-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	
		$res = NewQuery($sql);
		$gedold = GedcomConfig::$GEDCOMID;
		$select = array();
		while($row = $res->fetchAssoc()){
			$fam = Family::GetInstance($row["f_id"], $row, $row["f_file"]);
			$myfamlist[$row["f_key"]] = $fam;
			if ($row["f_husb"] != "" && !Person::IsInstance(SplitKey($row["f_husb"], "id"), $row["f_file"])) $select[] = $row["f_husb"];
			if ($row["f_wife"] != "" && !Person::IsInstance(SplitKey($row["f_wife"], "id"), $row["f_file"])) $select[] = $row["f_wife"];
		}
		$res->FreeResult();
		if (count($select) > 0) {
			array_flip(array_flip($select));
			//print "Indi's selected for fams: ".count($select)."<br />";
			$selection = "'".implode("','", $select)."'";
			ListFunctions::GetIndilist($allgeds, $selection, false);
		}
		return $myfamlist;
	}

	//-- search through the gedcom records for sources, full text
	public function FTSearchSources($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $ftminwlen, $ftmaxwlen, $LINK_PRIVACY;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$mysourcelist = array();	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " ".$keyword["operator"]." s_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." s_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " AND s_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND s_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT s_key, s_id, s_name, s_file, s_gedrec, sm_sid, sm_gid, sm_type FROM ".TBLPREFIX."sources LEFT JOIN ".TBLPREFIX."source_mapping ON s_key=sm_key WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT s_key, s_id, s_name, s_file, s_gedrec, sm_sid, sm_gid, sm_type FROM ".TBLPREFIX."sources LEFT JOIN ".TBLPREFIX."source_mapping ON s_key=sm_key WHERE (MATCH (s_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND s_file='".GedcomConfig::$GEDCOMID."'";
	
		if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "s_file='".$allgeds[$i]."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
		$res = NewQuery($sql);
		$indisel = array();
		$famsel = array();
		if ($res) {
			$oldkey = "";
			while($row = $res->fetchAssoc()){
				if ($oldkey != $row["s_key"]) {
					$oldkey = $row["s_key"];
					$source = Source::GetInstance($row["s_id"], $row, $row["s_file"]);
					$mysourcelist[$row["s_key"]] = $source;
				}
				$source->addlink = array($row["sm_gid"], $row["sm_type"], $row["s_file"]);
				if ($LINK_PRIVACY) {
					if ($row["sm_type"] == "INDI" && !Person::IsInstance($row["sm_gid"], $row["s_file"])) $indisel[] = JoinKey($row["sm_gid"], $row["s_file"]);
					else if ($row["sm_type"] == "FAM" && !Family::IsInstance($row["sm_gid"], $row["s_file"])) $famsel[] = JoinKey($row["sm_gid"], $row["s_file"]);
				}
			}
			$res->FreeResult();
		}
		if (count($indisel) > 0) {
			array_flip(array_flip($indisel));
			$indiselect = "'".implode("','", $indisel)."'";
			ListFunctions::GetIndiList("", $indiselect, false);
		}
		if (count($famsel) > 0) {
			array_flip(array_flip($famsel));
			$famselect = "'".implode ("', '", $famsel)."'";
			ListFunctions::GetFamList("", $famselect, false);
		}
		return $mysourcelist;
	}

	//-- search through the gedcom records for repositories, full text
	public function FTSearchRepos($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $ftminwlen, $ftmaxwlen;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$myrepolist = array();	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " AND o_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND o_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT o_key, o_id, o_file, o_type, o_gedrec FROM ".TBLPREFIX."other WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT o_key, o_id, o_file, o_type, o_gedrec FROM ".TBLPREFIX."other WHERE (MATCH (o_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND o_file='".GedcomConfig::$GEDCOMID."'";
		
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
				$repo = Repository::GetInstance($row["o_id"], $row, $row["o_file"]);
				$myrepolist[$row["o_key"]] = $repo;
			}
			$res->FreeResult();
		}
		return $myrepolist;
	}

	//-- search through the gedcom records for media, full text
	public function FTSearchMedia($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $ftminwlen, $ftmaxwlen, $media_hide, $media_total, $LINK_PRIVACY;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " ".$keyword["operator"]." m_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." m_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " AND m_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND m_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT m_media, m_ext, m_titl, m_mfile, m_file, m_gedrec, mm_gid, mm_type, mm_file FROM ".TBLPREFIX."media LEFT JOIN ".TBLPREFIX."media_mapping ON m_media=mm_media AND m_file=mm_file WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT m_media, m_ext, m_titl, m_mfile, m_file, m_gedrec, mm_gid, mm_type, mm_file FROM ".TBLPREFIX."media LEFT JOIN ".TBLPREFIX."media_mapping ON m_media=mm_media AND m_file=mm_file WHERE (MATCH (m_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}

		if (!$allgeds) $sql .= " AND m_file='".GedcomConfig::$GEDCOMID."'";
		
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
		$indisel = array();
		$famsel = array();
		$soursel = array();
		$res = NewQuery($sql);
		if ($res) {
			$oldkey = "";
	 		while ($row = $res->FetchAssoc()) {
		 		$key = $row["m_media"]."[".$row["m_file"]."]";
				if ($oldkey != $key) {
					$oldkey = $key;
					$media = MediaItem::GetInstance($row["m_media"], $row, $row["m_file"]);
					$medialist[$media->key] = $media;
				}
				$media->addlink = array($row["mm_gid"], $row["mm_type"], $row["mm_file"]);
				if ($LINK_PRIVACY) {
					if ($row["mm_type"] == "INDI" && !Person::IsInstance($row["mm_gid"], $row["mm_file"])) $indisel[] = $row["mm_gid"]."[".$row["mm_file"]."]";
					else if ($row["mm_type"] == "FAM" && !Family::IsInstance($row["mm_gid"], $row["mm_file"])) $famsel[] = $row["mm_gid"]."[".$row["mm_file"]."]";
					else if ($row["mm_type"] == "SOUR" && !Source::IsInstance($row["mm_gid"], $row["mm_file"])) $soursel[] = $row["mm_gid"]."[".$row["mm_file"]."]";
				}
	 		}
			if (count($indisel) > 0) {
				array_flip(array_flip($indisel));
				$indiselect = "'".implode("','", $indisel)."'";
				ListFunctions::GetIndiList("", $indiselect, false);
			}
			if (count($famsel) > 0) {
				array_flip(array_flip($famsel));
				$famselect = "'".implode ("','", $famsel)."'";
				ListFunctions::GetFamList("", $famselect, false);
			}
			if (count($soursel) > 0) {
				array_flip(array_flip($soursel));
				$sourselect = "'".implode ("','", $soursel)."'";
				ListFunctions::GetSourceList($sourselect, false);
			}
		}
		return $medialist;
	}
	
	//-- search through the gedcom records for notes, full text
	public function FTSearchNotes($query, $allgeds=false, $ANDOR="AND") {
		global $GEDCOMS, $ftminwlen, $ftmaxwlen, $note_hide, $note_total;
		
		// Get the min and max search word length
		self::GetFTWordLengths();
	
		$cquery = self::ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = self::GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || self::HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." o_gedrec REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (NameFunctions::HasChinese($keyword["term"]) || NameFunctions::HasCyrillic($keyword["term"])) $addsql .= " AND o_gedrec NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND o_gedrec NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT * FROM ".TBLPREFIX."other WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT * FROM ".TBLPREFIX."other WHERE (MATCH (o_gedrec) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND o_file='".GedcomConfig::$GEDCOMID."'";
		
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
		 		$notelist[$row["o_key"]] = $note;
	 		}
	 		uasort($notelist, "TitleObjSort");
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
			if (empty($cquery[$qindex])) {
				if ($qindex != "includes" && $qindex != "excludes") {
					unset($cquery[$qindex]);
				}
			}
			else {
				if (substr($squery,-1) == "*") $wildcard = ".*";
				else $wildcard = "";
				if (substr($squery, 0, 1) == "-") {
					$term = preg_replace("/[+<>\*\"\~\?\\\]/", "", substr($squery,1));
					if (!empty($term)) $cquery["excludes"][] = array("term"=>$term, "operator"=>"NOT", "wildcard"=>$wildcard);
				}
				else {
					if (substr($squery, 0, 1) == "+") $operator = "AND";
					else $operator = "OR";
					$term = preg_replace("/[+<>\*\"\~\?\\\]/", "", $squery);
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
					if (NameFunctions::HasChinese($subkeyword)) $chinese = true;
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
					if (NameFunctions::HasChinese($subkeyword)) $chinese = true;
					if ($len < $minwlen) $minwlen = $len;
				}
			}
		}
		if ($chinese) return 1;
		else return $minwlen;
	}
	
	// Used in calendar.php
	public function SearchIndisYearRange($startyear, $endyear, $allgeds=false, $type="", $est="") {
	
		$myindilist = array();
		$sql = "SELECT DISTINCT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_surname, n_nick, n_letter, n_fletter, n_type FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."names ON i_key=n_key INNER JOIN ".TBLPREFIX."dates ON (i_key=d_key AND d_fact<>'CHAN') WHERE";
		if ($startyear < $endyear) $sql .= " d_year>='".$startyear."' AND d_year<='".$endyear."'";
		else $sql .= " d_year=".$startyear;
		if (!empty($type)) $sql .= " AND d_fact IN ".$type;
		if (!$allgeds) $sql .= " AND i_file='".GedcomConfig::$GEDCOMID."'";
		$sql .= " ORDER BY i_key, n_id";
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
			if ($person->disp_name) $myindilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
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
	
		$myfamlist = array();
		$sql = "SELECT DISTINCT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families INNER JOIN ".TBLPREFIX."dates ON (d_key=f_key AND d_fact<>'CHAN') WHERE";
		if ($endyear > $startyear) $sql .= " d_year>='".$startyear."' AND d_year<='".$endyear."'";
		else $sql .= " d_year=".$startyear;
		if (!empty($type)) $sql .= " AND d_fact IN ".$type;
		if (!$allgeds) $sql .= " AND f_file='".GedcomConfig::$GEDCOMID."'";
		$res = NewQuery($sql);

		$select = array();
		while($row = $res->FetchAssoc()){
			if ($row["f_husb"] != "" && !Person::IsInstance(SplitKey($row["f_husb"], "id"), $row["f_file"])) $select[] = $row["f_husb"];
			if ($row["f_wife"] != "" && !Person::IsInstance(SplitKey($row["f_wife"], "id"), $row["f_file"])) $select[] = $row["f_wife"];
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
	 * Used in:	reportpdf.php
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
		global $GEDCOMS;
		
		$myindilist = array();
		// From calendar, search for a year
		if (!is_array($query)) { 
			$sql = "SELECT DISTINCT i_key, i_id, i_file, i_gedrec, i_isdead FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."dates on (d_key=i_key AND d_fact<>'CHAN') WHERE (d_year='".$query."' OR d_ext='BET')";
			if (!empty($type)) $sql .= " AND d_fact IN ".$type;
		}
		// Else from reports, we have a regexp query array
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
		
		if (!$allgeds) $sql .= " AND i_file='".GedcomConfig::$GEDCOMID."'";
	
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
	 * Used in:	reportpdf.php
	 * @package Genmod
	 * @subpackage Calendar
	**/
	public function SearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false, $type="") {
	
		$myfamlist = array();
		if (!is_array($query)) {
			$sql = "SELECT DISTINCT f_key, f_id, f_file, f_gedrec, f_wife, f_husb FROM ".TBLPREFIX."families INNER JOIN ".TBLPREFIX."dates on (d_key=f_key AND d_fact<>'CHAN') WHERE (d_year='".$query."' OR d_ext='BET')";
			if (!empty($type)) $sql .= " AND d_fact IN ".$type;
		}
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
		if (!$allgeds) $sql .= " AND f_file='".GedcomConfig::$GEDCOMID."'";
	
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
			if ($row["f_husb"] != "" && !Person::IsInstance(SplitKey($row["f_husb"], "id"), $row["f_file"])) $select[] = $row["f_husb"];
			if ($row["f_wife"] != "" && !Person::IsInstance(SplitKey($row["f_wife"], "id"), $row["f_file"])) $select[] = $row["f_wife"];
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
	public function SearchIndisDates($day="", $month="", $year="", $fact="", $startyear="", $allgeds=false, $ANDOR="AND") {
		
		$myindilist = array();
		
		$sql = "SELECT DISTINCT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_surname, n_nick, n_type, n_letter, n_fletter FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."names ON n_key=i_key INNER JOIN ".TBLPREFIX."dates ON (d_key=i_key AND d_fact<>'CHAN') WHERE";
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
		if (!empty($startyear)) {
			$sql .= $and." d_year>='".DbLayer::EscapeQuery($startyear)."'";
			$and = " AND";
		}

		if (!$allgeds) {
			$sql .= $and." d_file='".GedcomConfig::$GEDCOMID."'";
			$and = " AND";
		}
		if (!empty($fact)) $sql .= $and." d_fact IN ".$fact;
		
		$sql .= "ORDER BY i_key, n_id";

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
				if ($person->disp_name) $myindilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
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
	public function SearchFamsDates($day="", $month="", $year="", $fact="", $startyear="", $allgeds=false) {
		global $GEDCOMS;
		$myfamlist = array();
		
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec, d_gid, d_fact FROM ".TBLPREFIX."dates, ".TBLPREFIX."families WHERE f_key=d_key";
		if (!empty($day)) $sql .= " AND d_day='".DbLayer::EscapeQuery($day)."'";
		if (!empty($month)) $sql .= " AND d_month='".DbLayer::EscapeQuery(Str2Upper($month))."'";
		if (!empty($year)) $sql .= " AND d_year='".DbLayer::EscapeQuery($year)."'";
		if (!empty($startyear)) $sql .= "AND d_year>='".DbLayer::EscapeQuery($startyear)."'";
		if (!empty($fact)) {
			$sql .= " AND d_fact IN ".$fact;
		}
		if (!$allgeds) $sql .= " AND d_file='".GedcomConfig::$GEDCOMID."'";
		
		$res = NewQuery($sql);
		$select = array();
		
		while($row = $res->FetchAssoc()){
			$fam = null;
			$fam =& Family::GetInstance($row["f_id"], $row);
			if ($row["f_husb"] != "" && !Person::IsInstance(SplitKey($row["f_husb"], "id"), $row["f_file"])) $select[] = $row["f_husb"];
			if ($row["f_wife"] != "" && !Person::IsInstance(SplitKey($row["f_wife"], "id"), $row["f_file"])) $select[] = $row["f_wife"];
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
	
	
	//-- search through the gedcom records for families
	function SearchFamsNames($query, $ANDOR="AND", $allnames=false) {
	
		$myfamlist = array();
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."individual_family INNER JOIN ".TBLPREFIX."families on f_key=if_fkey WHERE if_pkey IN ('".implode("','", $query)."') AND if_role='S'";
	
		$res = NewQuery($sql);
		$select = array();
		while($row = $res->fetchAssoc()){
			$fam = Family::GetInstance($row["f_id"], $row, $row["f_file"]);
			$myfamlist[$row["f_key"]] = $fam;
			if ($row["f_husb"] != "" && !Person::IsInstance(SplitKey($row["f_husb"], "id"), $row["f_file"])) $select[] = $row["f_husb"];
			if ($row["f_wife"] != "" && !Person::IsInstance(SplitKey($row["f_wife"], "id"), $row["f_file"])) $select[] = $row["f_wife"];
		}
		if (count($select) > 0) {
			array_flip(array_flip($select));
			//print "Indi's selected for fams: ".count($select)."<br />";
			$selection = "'".implode("','", $select)."'";
			ListFunctions::GetIndilist("", $selection, false);
		}
		$res->FreeResult();
		return $myfamlist;
	}
	
	public function PrintIndiSearchResults(&$controller, $paste=false) {
		global $TEXT_DIRECTION, $GM_IMAGES;
		
		if (count($controller->indi_total) > 0 && !is_null($controller->srindi)) {
			
			$cti = count($controller->printindiname);
			print "\n\t<table class=\"ListTable".($controller->classname == 'SearchController' && $controller->origin != 'find' ? ' SearchListTable' : '')."\">\n\t\t<tr><td class=\"ListTableColumnHeader\"";
			if($cti > 12) print " colspan=\"2\"";
			print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".GM_LANG_people."\" alt=\"".GM_LANG_individuals."\" title=\"".GM_LANG_individuals."\" />&nbsp;&nbsp;";
			print GM_LANG_individuals;
			print "</td></tr><tr><td class=\"ListTableContent\"><ul>";
			$i=1;
			$indi_private = array();
			foreach($controller->printindiname as $pkey => $pvalue) {
				$person = $controller->sindilist[JoinKey($pvalue[1], $pvalue[2])];
				if (!$person->PrintListPerson(true, false, "", $pvalue[4], "", $pvalue[3], $paste)) $indi_private[$person->key] = true;;
				print "\n";
				if ($i==ceil($cti/2) && $cti>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td></tr>";
			
			print "<tr><td class=\"ListTableColumnFooter\" ".($cti>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_indis." ".count($controller->indi_total);
			if (count($indi_private)>0) print "  (".GM_LANG_private." ".count($indi_private).")";
			if (count($controller->indi_hide) > 0) print "  --  ".GM_LANG_hidden." ".count($controller->indi_hide);
			if (count($indi_private) > 0 || count($controller->indi_hide) > 0) PrintHelpLink("privacy_error_help", "qm");
			print "</td></tr>";
			print "</table>";
			return true;
		}
		else return false;
	}
	
	public function PrintFamSearchResults(&$controller, $paste=false) {
		global $TEXT_DIRECTION, $GM_IMAGES;
		
		if (count($controller->fam_total) > 0) {
		
			$ctf = count($controller->printfamname);
			print "\n\t<table class=\"ListTable".($controller->classname == 'SearchController' && $controller->origin != 'find' ? ' SearchListTable' : '')."\">\n\t\t<tr><td class=\"ListTableColumnHeader\"";
			if ($ctf > 12) print " colspan=\"2\"";
			print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"".GM_LANG_families."\" title=\"".GM_LANG_families."\" />&nbsp;&nbsp;";
			print GM_LANG_families;
			print "</td></tr><tr><td class=\"ListTableContent\"><ul>";
			$i=1;
			$fam_private = array();
			foreach($controller->printfamname as $pkey => $pvalue) {
				$fam = $controller->sfamlist[JoinKey($pvalue[1], $pvalue[2])];
				if (!$fam->PrintListFamily(true, "", $pvalue[0], $pvalue[3], $paste)) $fam_private[$fam->key] = true;
				print "\n";
				if ($i==ceil($ctf/2) && $ctf>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td></tr>";
			
			print "<tr><td class=\"ListTableColumnFooter\" ".($ctf>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_fams." ".count($controller->fam_total);
			if (count($fam_private) > 0) print "  (".GM_LANG_private." ".count($fam_private).")";
			if (count($controller->fam_hide) > 0) print "  --  ".GM_LANG_hidden." ".count($controller->fam_hide);
			if (count($fam_private) > 0 || count($controller->fam_hide) > 0) PrintHelpLink("privacy_error_help", "qm");
			print "</td></tr>";
			print "</table>";
			return true;
		}
		else return false;
	}
	
	public function PrintSourceSearchResults(&$controller, $paste=false) {
		global $TEXT_DIRECTION, $GM_IMAGES;
		
		if (count($controller->sour_total) > 0) {
			
			$cts = count($controller->printsource);
			print "\n\t<table class=\"ListTable".($controller->classname == 'SearchController' && $controller->origin != 'find' ? ' SearchListTable' : '')."\">\n\t\t<tr><td class=\"ListTableColumnHeader\"";
			if($cts > 12) print " colspan=\"2\"";
			print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"".GM_LANG_sources."\" title=\"".GM_LANG_sources."\" />&nbsp;&nbsp;";
			print GM_LANG_sources;
			print "</td></tr><tr><td class=\"ListTableContent\"><ul>";
			$sour_private = array();
			$i=1;
			foreach ($controller->printsource as $key => $sourcekey) {
				$source = $controller->ssourcelist[$sourcekey];
				if (!$source->PrintListSource(true, 1, "", $paste)) $sour_private[$source->key] = true;
				if ($i==ceil($cts/2) && $cts>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td></tr>";
			
			print "<tr><td class=\"ListTableColumnFooter\" ".($cts>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_sources." ".count($controller->sour_total);
			if (count($controller->sour_hide) > 0) print "  --  ".GM_LANG_hidden." ".count($controller->sour_hide);
			if (count($sour_private) > 0) print "  --  ".GM_LANG_private." ".count($sour_private);
			if (count($sour_private) > 0 || count($controller->sour_hide) > 0) PrintHelpLink("privacy_error_help", "qm");
			print "</td></tr>";
			print "</table>";
			return true;
		}
		else return false;
	}
	
	public function PrintRepoSearchResults(&$controller, $paste=false) {
		global $TEXT_DIRECTION, $GM_IMAGES;
		
		if (count($controller->repo_total) > 0) {
			
			$ctr = count($controller->printrepo);
			print "\n\t<table class=\"ListTable".($controller->classname == 'SearchController' && $controller->origin != 'find' ? ' SearchListTable' : '')."\">\n\t\t<tr><td class=\"ListTableColumnHeader\"";
			if($ctr > 12) print " colspan=\"2\"";
			print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" alt=\"".GM_LANG_search_repos."\" title=\"".GM_LANG_search_repos."\" />&nbsp;&nbsp;";
			print GM_LANG_search_repos;
			print "</td></tr><tr><td class=\"ListTableContent\"><ul>";
			$repo_private = array();
			$i=1;
			foreach ($controller->printrepo as $key => $repokey) {
				$repo = $controller->srepolist[$repokey];
				if (!$repo->PrintListRepository(true, 1, false, "", $paste)) $repo_private[$repo->key] = true;
				if ($i==ceil($ctr/2) && $ctr>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td></tr>";
			
			print "<tr><td class=\"ListTableColumnFooter\" ".($ctr>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_repositories." ".count($controller->repo_total);
			if (count($controller->repo_hide) > 0) print "  --  ".GM_LANG_hidden." ".count($controller->repo_hide);
			if (count($repo_private)>0) print "  --  ".GM_LANG_private." ".count($repo_private);
			if (count($repo_private) > 0 || count($controller->repo_hide) > 0) PrintHelpLink("privacy_error_help", "qm");
			print "</td></tr>";
			print "</table>";
			return true;
		}
		else return false;
	}
	
	public function PrintNoteSearchResults(&$controller, $paste=false) {
		global $TEXT_DIRECTION, $GM_IMAGES;
		
		if (count($controller->note_total) > 0) {
			
			$ctn = count($controller->printnote);
			print "\n\t<table class=\"ListTable".($controller->classname == 'SearchController' && $controller->origin != 'find' ? ' SearchListTable' : '')."\">\n\t\t<tr><td class=\"ListTableColumnHeader\"";
			if($ctn > 12) print " colspan=\"2\"";
			print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" alt=\"".GM_LANG_notes."\" title=\"".GM_LANG_notes."\" />&nbsp;&nbsp;";
			print GM_LANG_notes;
			print "</td></tr><tr><td class=\"ListTableContent\"><ul>";
			$i=1;
			$note_private = array();
			foreach ($controller->printnote as $key => $notekey) {
				$note = $controller->snotelist[$notekey];
				if (!$note->PrintListNote(60, true, $paste)) $note_private[$note->key] = true;
				if ($i == ceil($ctn/2) && $ctn>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td></tr>";
			print "<tr><td class=\"ListTableColumnFooter\" ".($ctn>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_notes." ".count($controller->note_total);
			if (count($controller->note_hide) > 0) print "  --  ".GM_LANG_hidden." ".count($controller->note_hide);
			if (count($note_private) > 0) print "  --  ".GM_LANG_private." ".count($note_private);
			if (count($note_private) > 0 || count($controller->note_hide) > 0) PrintHelpLink("privacy_error_help", "qm");
			print "</td></tr>";
			print "</table>";
			return true;
		}
		else return false;
		
	}	
}
?>