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
if (strstr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

//-- search through the gedcom records for individuals
/**
 * Search the database for individuals that match the query
 *
 * uses a regular expression to search the gedcom records of all individuals and returns an
 * array list of the matching individuals
 *
 * @author	Genmod Development Team
 * @param		string $query a regular expression query to search for
 * @param		boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function SearchIndis($query, $allgeds=false, $ANDOR="AND") {
	global $indilist, $GEDCOMS, $GEDCOMID;
	
	$myindilist = array();
	if (!is_array($query)) $sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead FROM ".TBLPREFIX."individuals WHERE (i_gedcom REGEXP '".DbLayer::EscapeQuery($query)."')";
	else {
		$sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead FROM ".TBLPREFIX."individuals WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(i_gedcom REGEXP '".DbLayer::EscapeQuery($q)."')";
			$i++;
		}
		$sql .= ")";
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
			if (count($allgeds) > 1) $key = $row["i_key"];
			else $key = $row["i_id"];
			$myindilist[$key]["names"] = GetIndiNames($row["i_gedcom"]);
			$myindilist[$key]["gedfile"] = $row["i_file"];
			$myindilist[$key]["gedcom"] = $row["i_gedcom"];
			$myindilist[$key]["isdead"] = $row["i_isdead"];
//			if ($myindilist[$row["i_key"]]["gedfile"] == $GEDCOMID) $indilist[$row[0]] = $myindilist[$row[0]."[".$row[2]."]"];
		}
		$res->FreeResult();
	}
	return $myindilist;
}

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
function FTSearchIndis($query, $allgeds=false, $ANDOR="AND") {
	global $indilist, $GEDCOMID, $GEDCOMS, $ftminwlen, $ftmaxwlen, $COMBIKEY;
	
	// Get the min and max search word length
	GetFTWordLengths();

	// if all search terms are below the minimum length, the FT search query will return nothing.
	// if all positive short terms with + are added as AND, nothing will be returned either.
	// so, if all positive search terms are shorter than min len, we must omit FT search anyway.	
	
	$myindilist = array();
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);

	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (!Utf8_isascii($keyword["term"])) $addsql .= " ".$keyword["operator"]." i_gedcom REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." i_gedcom REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (!Utf8_isascii($keyword["term"])) $addsql .= " AND i_gedcom NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND i_gedcom NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (MATCH (i_gedcom) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
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
				$myindilist[$key]["gedcom"] = $row["i_gedcom"];
				$myindilist[$key]["isdead"] = $row["i_isdead"];
			}
			else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$indilist[$key] = $myindilist[$key];
		}
		$res->FreeResult();
	}
	return $myindilist;
}

/**
 * Search through the gedcom records for individuals in families
 *
 * @package Genmod
 * @subpackage Calendar
**/
function SearchIndisFam($add2myindilist) {
	global $indilist, $myindilist, $GEDCOMID;

	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_type, n_surname, n_letter FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND i_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->fetchAssoc()){
		if (isset($add2myindilist[$row["i_id"]])){
			$add2my_fam=$add2myindilist[$row["i_id"]];
			$row = db_cleanup($row);
			if (!isset($myindilist[$row["i_id"]])) {
				$myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$myindilist[$row["i_id"]]["gedfile"] = $row["i_file"];
				$myindilist[$row["i_id"]]["gedcom"] = $row["i_gedcom"].$add2my_fam;
				$myindilist[$row["i_id"]]["isdead"] = $row["i_isdead"];
			}
			else $myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$indilist[$row["i_id"]] = $myindilist[$row["i_id"]];
		}
	}
	$res->FreeResult();
	return $myindilist;
}

/**
* @package Genmod
* @subpackage Calendar
**/
function SearchIndisYearRange($startyear, $endyear, $allgeds=false) {
	global $indilist, $GEDCOMID;

	$myindilist = array();
	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_surname, n_letter, n_type FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (";
	$i=$startyear;
	while($i <= $endyear) {
		if ($i > $startyear) $sql .= " OR ";
		$sql .= "i_gedcom REGEXP '".DbLayer::EscapeQuery("2 DATE[^\n]* ".$i)."'";
		$i++;
	}
	$sql .= ")";
	if (!$allgeds) $sql .= " AND i_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->fetchAssoc()){
		$row = db_cleanup($row);
		if (!isset($myindilist[$row["i_id"]])) {
			$myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$myindilist[$row["i_id"]]["gedfile"] = $row["i_file"];
			$myindilist[$row["i_id"]]["gedcom"] = $row["i_gedcom"];
			$myindilist[$row["i_id"]]["isdead"] = $row["i_isdead"];
		}
		else $myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		$indilist[$row["i_id"]] = $myindilist[$row["i_id"]];
	}
	$res->FreeResult();
	return $myindilist;
}

/**
 * Search through the gedcom records for individuals
 *
 * @package Genmod
 * @subpackage Find
**/
function SearchIndisNames($query, $allgeds=false, $gedid=0) {
	global $indilist, $GEDCOMID;
	
	//-- split up words and find them anywhere in the record... important for searching names
	//-- like "givenname surname"
	if (!is_array($query)) {
		$query = preg_split("/[\s,]+/", $query);
	}

	$myindilist = array();
	if (!is_array($query)) $sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND n_name REGEXP '".DbLayer::EscapeQuery($query)."'";
	else {
		$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (";
		$i=0;
		foreach($query as $indexval => $q) {
			if (!empty($q)) {
				if ($i>0) $sql .= " AND ";
				$sql .= "n_name REGEXP '".DbLayer::EscapeQuery($q)."'";
				$i++;
			}
		}
		$sql .= ")";
	}
	if (!$allgeds) {
		if ($gedid == 0) $sql .= " AND i_file='".$GEDCOMID."'";
		else $sql .= " AND i_file='".$gedid."'";
	}
	$res = NewQuery($sql);
	while($row = $res->fetchAssoc()){
		$row = db_cleanup($row);
		if ($allgeds) $key = $row["i_id"]."[".$row["i_file"]."]";
		else $key = $row["i_id"];
		if (!isset($myindilist[$key])) {
			$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$myindilist[$key]["gedfile"] = $row["i_file"];
			$myindilist[$key]["gedcom"] = $row["i_gedcom"];
			$myindilist[$key]["isdead"] = $row["i_isdead"];
		}
		else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		$indilist[$key] = $myindilist[$key];
	}
	$res->FreeResult();
	return $myindilist;
}

/**
 * Search the dates table for individuals that had events on the given day
 *
 * @author	yalnifj
 * @param	int $day the day of the month to search for, leave empty to include all
 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
 * @param	int $year the year to search for, leave empty to include all
 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
 * 				prepend the fact with a ! to not include that fact
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function SearchIndisDates($day="", $month="", $year="", $fact="", $allgeds=false, $ANDOR="AND") {
	global $indilist, $GEDCOMID;
	$myindilist = array();
	
	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, d_gid, d_fact, n_name, n_surname, n_type, n_letter FROM ".TBLPREFIX."dates, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=d_key AND n_key=i_key ";
	if (!empty($day)) $sql .= "AND d_day='".DbLayer::EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".DbLayer::EscapeQuery($month)."' ";
	if (!empty($year)) $sql .= "AND d_year='".DbLayer::EscapeQuery($year)."' ";
	if (!empty($fact)) {
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
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchAssoc()){
			$row = db_cleanup($row);
			if ($allgeds) $key = $row["i_id"]."[".$row["i_file"]."]";
			else $key = $row["i_id"];
			if (!isset($myindilist[$key])) {
				$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$myindilist[$key]["gedfile"] = $row["i_file"];
				$myindilist[$key]["gedcom"] = $row["i_gedcom"];
				$myindilist[$key]["isdead"] = $row["i_isdead"];
			}
			else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			if ($myindilist[$key]["gedfile"] == $GEDCOMID) $indilist[$row["i_id"]] = $myindilist[$key];
		}
		$res->FreeResult();
	}
	return $myindilist;
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
function SearchIndisDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $filter="all", $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $GEDCOMID, $indilist;
	$myindilist = array();
//print "Dstart: ".$dstart."<br />";
//print "Mstart: ".$mstart." ".date("M", mktime(1,0,0,$mstart,1))."<br />";
//print "Dend: ".$dend."<br />";
//print "Mend: ".$mend." ".date("M", mktime(1,0,0,$mend,1))."<br />";
	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_surname, n_letter, n_type FROM ".TBLPREFIX."dates, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=d_key AND n_key=i_key ";
	if ($onlyBDM == "yes") $sql .= " AND d_fact NOT IN ('BIRT', 'DEAT')";
	if ($filter == "alive") $sql .= "AND i_isdead!='0'";

	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
	$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";
//print $sql;
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$row = db_cleanup($row);
		if ($allgeds) $key = $row["i_id"]."[".$row["i_file"]."]";
		else $key = $row["i_id"];
		if (!isset($myindilist[$key])) {
			$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$myindilist[$key]["gedfile"] = $row["i_file"];
			$myindilist[$key]["gedcom"] = $row["i_gedcom"];
			$myindilist[$key]["isdead"] = $row["i_isdead"];
		}
		else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		if ($myindilist[$key]["gedfile"] == $GEDCOMID) $indilist[$row["i_id"]] = $myindilist[$key];
	}
	$res->FreeResult();
	return $myindilist;
}

function DateRangeforQuery($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
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

/**
 * Search the dates table for families that had events on the given day
 *
 * @author	yalnifj
 * @param	int $day the day of the month to search for, leave empty to include all
 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
 * @param	int $year the year to search for, leave empty to include all
 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
 * 				prepend the fact with a ! to not include that fact
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchFamsDateRange($dstart="1", $mstart="1", $ystart, $dend="31", $mend="12", $yend, $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $GEDCOMID, $famlist, $GEDCOMS, $GEDCOMID;
	$myfamlist = array();
	$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedcom FROM ".TBLPREFIX."dates, ".TBLPREFIX."families WHERE f_key=d_key ";

	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);

	if ($onlyBDM == "yes") $sql .= " AND d_fact='MARR'";
	$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";

	$res = NewQuery($sql);
	$gedold = $GEDCOMID;
	while($row = $res->fetchAssoc()){
		$row = db_cleanup($row);
		$GEDCOMID = $row["f_file"];
		$hname = GetSortableName(SplitKey($row["f_husb"], "id"));
		$wname = GetSortableName(SplitKey($row["f_wife"], "id"));
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		if ($allgeds) $key = $row["f_key"];
		else $key = $row["f_id"];
		$myfamlist[$key]["name"] = $name;
		$myfamlist[$key]["HUSB"] = SplitKey($row["f_husb"], "id");
		$myfamlist[$key]["WIFE"] = SplitKey($row["f_wife"], "id");
		$myfamlist[$key]["gedfile"] = $row["f_file"];
		$myfamlist[$key]["gedcom"] = $row["f_gedcom"];
		$famlist[$key] = $myfamlist[$key];
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myfamlist;
}

//-- search through the gedcom records for families
function SearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $famlist, $GEDCOMS, $GEDCOMID;
	
	$myfamlist = array();
	if (!is_array($query)) $sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".TBLPREFIX."families WHERE (f_gedcom REGEXP '".DbLayer::EscapeQuery($query)."')";
	else {
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedcom FROM ".TBLPREFIX."families WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(f_gedcom REGEXP '".DbLayer::EscapeQuery($q)."')";
			$i++;
		}
		$sql .= ")";
	}
	
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
		if (count($allgeds) > 1) $key = $row["f_key"];
		else $key = $row["f_id"];
		$myfamlist[$key]["name"] = $name;
		$myfamlist[$key]["gedfile"] = $row["f_file"];
		$myfamlist[$key]["gedcom"] = $row["f_gedcom"];
		$myfamlist[$key]["HUSB"] = $husb;
		$myfamlist[$key]["WIFE"] = $wife;
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myfamlist;
}

// Get the minimum and maximum Full Text Search term lengths
function GetFTWordLengths() {
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
function FTSearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $famlist, $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen, $COMBIKEY;

	// Get the min and max search word length
	GetFTWordLengths();
	
	$myfamlist = array();
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);
	
	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." f_gedcom REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." f_gedcom REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " AND f_gedcom NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND f_gedcom NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedcom FROM ".TBLPREFIX."families WHERE (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedcom FROM ".TBLPREFIX."families WHERE (MATCH (f_gedcom) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
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
		$myfamlist[$key]["gedcom"] = $row["f_gedcom"];
		$myfamlist[$key]["HUSB"] = $husb;
		$myfamlist[$key]["WIFE"] = $wife;
		$famlist[$key] = $myfamlist[$key];
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myfamlist;
}


//-- search through the gedcom records for families
function SearchFamsNames($query, $ANDOR="AND", $allnames=false) {
	global $famlist, $GEDCOMS, $GEDCOMID, $COMBIKEY;

	$myfamlist = array();
	$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedcom FROM ".TBLPREFIX."families WHERE (";
	$i=0;
	foreach($query as $indexval => $q) {

		if ($i>0) $sql .= " $ANDOR ";
		$sql .= "((f_husb='".DbLayer::EscapeQuery(JoinKey($q[0], $q[1]))."' OR f_wife='".DbLayer::EscapeQuery(JoinKey($q[0], $q[1]))."') AND f_file='".DbLayer::EscapeQuery($q[1])."')";
		$i++;
	}
	$sql .= ")";

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
		if ($COMBIKEY) $key = $row["f_key"];
		else $key = $row["f_id"];
		$myfamlist[$key]["name"] = $name;
		$myfamlist[$key]["gedfile"] = $row["f_file"];
		$myfamlist[$key]["HUSB"] = $husb;
		$myfamlist[$key]["WIFE"] = $wife;
		$myfamlist[$key]["gedcom"] = $row["f_gedcom"];
		$famlist[$key] = $myfamlist[$key];
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myfamlist;
}

/**
 * Search the families table for individuals are part of that family 
 * either as a husband, wife or child.
 *
 * @author	roland-d
 * @param	string $query the query to search for as a single string
 * @param	array $query the query to search for as an array
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @param	string $ANDOR setting if the sql query should be constructed with AND or OR
 * @param	boolean $allnames true returns all names in an array
 * @return	array $myfamlist array with all families that matched the query
 */
function SearchFamsMembers($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $famlist, $GEDCOMID;
	
	if (is_array($query)) $id = JoinKey($q[0], $q[1]);
	else $id = JoinKey($query, $GEDCOMID);
	
	$myfamlist = array();
	$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file FROM ".TBLPREFIX."individual_family, ".TBLPREFIX."families WHERE if_pkey='".DbLayer::EscapeQuery($id)."' AND f_key = if_fkey";
	
	$res = NewQuery($sql);
	while($row = $res->fetchAssoc()){
		$row = db_cleanup($row);
		$husb = SplitKey($row["f_husb"], "id");
		$wife = SplitKey($row["f_wife"], "id");
		$hname = GetSortableName($husb);
		$wname = GetSortableName($wife);
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = CheckNN($hname." + ".$wname);
		$myfamlist[$row["f_id"]][] = $name;
		$myfamlist[$row["f_id"]][] = $row["f_id"];
		$myfamlist[$row["f_id"]][] = $row["f_file"];
		$famlist[$row["f_id"]] = $myfamlist[$row["f_id"]];
	}
	$res->FreeResult();
	return $myfamlist;
}

/**
 * Search through the gedcom records for families with daterange
 *
 * @package Genmod
 * @subpackage Calendar
**/
function SearchFamsYearRange($startyear, $endyear, $allgeds=false) {
	global $famlist, $GEDCOMID;

	$myfamlist = array();
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".TBLPREFIX."families WHERE (";
	$i=$startyear;
	while($i <= $endyear) {
		if ($i > $startyear) $sql .= " OR ";
		$sql .= "f_gedcom REGEXP '".DbLayer::EscapeQuery("2 DATE[^\n]* ".$i)."'";
		$i++;
	}
	$sql .= ")";
	if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$hname = GetSortableName(SplitKey($row[1], "id"));
		$wname = GetSortableName(SplitKey($row[2], "id"));
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		$myfamlist[$row[0]]["name"] = $name;
		$myfamlist[$row[0]]["gedfile"] = $row[3];
		$myfamlist[$row[0]]["HUSB"] = SplitKey($row[1], "id");
		$myfamlist[$row[0]]["WIFE"] = SplitKey($row[2], "id");
		$myfamlist[$row[0]]["gedcom"] = $row[4];
		$famlist[$row[0]] = $myfamlist[$row[0]];
	}
	$res->FreeResult();
	return $myfamlist;
}

/**
 * Search the dates table for families that had events on the given day
 *
 * @author	yalnifj
 * @param	int $day the day of the month to search for, leave empty to include all
 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
 * @param	int $year the year to search for, leave empty to include all
 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
 * 				prepend the fact with a ! to not include that fact
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchFamsDates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $famlist, $GEDCOMS, $GEDCOMID;
	$myfamlist = array();
	
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom, d_gid, d_fact FROM ".TBLPREFIX."dates, ".TBLPREFIX."families WHERE f_key=d_key ";
	if (!empty($day)) $sql .= "AND d_day='".DbLayer::EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".DbLayer::EscapeQuery(Str2Upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".DbLayer::EscapeQuery($year)."' ";
	if (!empty($fact)) {
		$sql .= "AND (";
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
	}
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOMID;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOMID = $row[3];
		$hname = GetSortableName(SplitKey($row[1], "id"));
		$wname = GetSortableName(SplitKey($row[2], "id"));
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		if ($allgeds) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["HUSB"] = SplitKey($row[1], "id");
			$myfamlist[$row[0]."[".$row[3]."]"]["WIFE"] = SplitKey($row[2], "id");
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["HUSB"] = SplitKey($row[1], "id");
			$myfamlist[$row[0]]["WIFE"] = SplitKey($row[2], "id");
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myfamlist;
}

//-- search through the gedcom records for sources
function SearchSources($query, $allgeds=false, $ANDOR="AND") {
	global $GEDCOMID;
	
	$mysourcelist = array();	
	if (!is_array($query)) $sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".TBLPREFIX."sources WHERE (s_gedcom REGEXP '".DbLayer::EscapeQuery($query)."')";
	else {
		$sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".TBLPREFIX."sources WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(s_gedcom REGEXP '".DbLayer::EscapeQuery($q)."')";
			$i++;
		}
		$sql .= ")";
	}	
	if (!$allgeds) $sql .= " AND s_file='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "s_file='".$allgeds[$i]."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}

	$sql .= " ORDER BY s_name";
	$res = NewQuery($sql);
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
	return $mysourcelist;
}


//-- search through the gedcom records for sources, full text
function FTSearchSources($query, $allgeds=false, $ANDOR="AND") {
	global $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen;
	
	// Get the min and max search word length
	GetFTWordLengths();

	$mysourcelist = array();	
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);
	
	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." s_gedcom REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." s_gedcom REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " AND s_gedcom NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND s_gedcom NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".TBLPREFIX."sources WHERE (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".TBLPREFIX."sources WHERE (MATCH (s_gedcom) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
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
function FTSearchRepos($query, $allgeds=false, $ANDOR="AND") {
	global $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen;
	
	// Get the min and max search word length
	GetFTWordLengths();

	$myrepolist = array();	
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);
	
	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." o_gedcom REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." o_gedcom REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " AND o_gedcom NOT REGEXP '".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND o_gedcom NOT REGEXP '[[:<:]]".DbLayer::EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT o_id, o_file, o_type, o_gedcom FROM ".TBLPREFIX."other WHERE (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT o_id, o_file, o_type, o_gedcom FROM ".TBLPREFIX."other WHERE (MATCH (o_gedcom) AGAINST ('".DbLayer::EscapeQuery($query)."' IN BOOLEAN MODE))";
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
			$tt = preg_match("/1 NAME (.*)/", $row["o_gedcom"], $match);
			if ($tt == "0") $name = $row["o_id"]; else $name = $match[1];
			if (count($allgeds) > 1) {
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["id"] = $row["o_id"];
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["name"] = $name;
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["gedfile"] = $row["o_file"];
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["gedcom"] = $row["o_gedcom"];
			}
			else {
				$myrepolist[$row["o_id"]]["id"] = $row["o_id"];
				$myrepolist[$row["o_id"]]["name"] = $name;
				$myrepolist[$row["o_id"]]["gedfile"] = $row["o_file"];
				$myrepolist[$row["o_id"]]["gedcom"] = $row["o_gedcom"];
			}
		}
		$res->FreeResult();
	}
	return $myrepolist;
}



/**
 * Search the dates table for sources that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchSourcesDates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $famlist, $GEDCOMID;
	$mysourcelist = array();
	
	$sql = "SELECT s_id, s_name, s_file, s_gedcom, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."sources WHERE s_id=d_gid AND s_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".DbLayer::EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".DbLayer::EscapeQuery(Str2Upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".DbLayer::EscapeQuery($year)."' ";
	if (!empty($fact)) $sql .= "AND d_fact='".DbLayer::EscapeQuery(Str2Upper($fact))."' ";
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY s_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOMID;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allgeds) {
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
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $mysourcelist;
}

/**
 * Search the dates table for sources that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchSourcesDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $famlist, $GEDCOMID;
	$mysourcelist = array();
	
	$sql = "SELECT s_id, s_name, s_file, s_gedcom, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."sources WHERE s_id=d_gid AND s_file=d_file ";
	
	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
	$sql .= "GROUP BY s_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOMID;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allgeds) {
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
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $mysourcelist;
}

/**
 * Search the dates table for other records that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchOtherDates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $famlist, $GEDCOMID;
	$myrepolist = array();
	
	$sql = "SELECT o_id, o_file, o_type, o_gedcom, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."other WHERE o_id=d_gid AND o_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".DbLayer::EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".DbLayer::EscapeQuery(Str2Upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".DbLayer::EscapeQuery($year)."' ";
	if (!empty($fact)) $sql .= "AND d_fact='".DbLayer::EscapeQuery(Str2Upper($fact))."' ";
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY o_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOMID;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$tt = preg_match("/1 NAME (.*)/", $row[2], $match);
		if ($tt == "0") $name = $row[0]; else $name = $match[1];
		if ($allgeds) {
			$myrepolist[$row[0]."[".$row[1]."]"]["name"] = $name;
			$myrepolist[$row[0]."[".$row[1]."]"]["gedfile"] = $row[1];
			$myrepolist[$row[0]."[".$row[1]."]"]["type"] = $row[2];
			$myrepolist[$row[0]."[".$row[1]."]"]["gedcom"] = $row[3];
		}
		else {
			$myrepolist[$row[0]]["name"] = $name;
			$myrepolist[$row[0]]["gedfile"] = $row[1];
			$myrepolist[$row[0]]["type"] = $row[2];
			$myrepolist[$row[0]]["gedcom"] = $row[3];
		}
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myrepolist;
}

/**
 * Search the dates table for other records that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchOtherDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $GEDCOMID;
	$myrepolist = array();
	
	$sql = "SELECT o_id, o_file, o_type, o_gedcom, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."other WHERE o_id=d_gid AND o_file=d_file ";
	
	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
	$sql .= "GROUP BY o_id ORDER BY d_year, d_month, d_day DESC";

	$res = NewQuery($sql);
	$gedold = $GEDCOMID;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$tt = preg_match("/1 NAME (.*)/", $row[2], $match);
		if ($tt == "0") $name = $row[0]; else $name = $match[1];
		if ($allgeds) {
			$myrepolist[$row[0]."[".$row[1]."]"]["name"] = $name;
			$myrepolist[$row[0]."[".$row[1]."]"]["gedfile"] = $row[1];
			$myrepolist[$row[0]."[".$row[1]."]"]["type"] = $row[2];
			$myrepolist[$row[0]."[".$row[1]."]"]["gedcom"] = $row[3];
		}
		else {
			$myrepolist[$row[0]]["name"] = $name;
			$myrepolist[$row[0]]["gedfile"] = $row[1];
			$myrepolist[$row[0]]["type"] = $row[2];
			$myrepolist[$row[0]]["gedcom"] = $row[3];
		}
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myrepolist;
}

/**
 * Search the dates table for other records that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $mymedia array with all individuals that matched the query
 */
function SearchMediaDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $GEDCOMID;
	$mymedia = array();
	
	$sql = "SELECT m_media, m_file, m_gedfile, m_ext, m_titl, m_gedrec FROM ".TBLPREFIX."dates, ".TBLPREFIX."media WHERE m_media=d_gid AND m_gedfile=d_file ";
	
	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
	$sql .= "GROUP BY m_media ORDER BY d_year, d_month, d_day DESC";

	$res = NewQuery($sql);
	$gedold = $GEDCOMID;

	while($row = $res->fetchAssoc()){
		if ($allgeds) $mid = $row["m_media"]."[".$row["m_gedfile"]."]";
		else $mid = $row["m_media"];
		$mymedia[$mid]["ext"] = $row["m_ext"];
		$mymedia[$mid]["title"] = $row["m_titl"];
		$mymedia[$mid]["file"] = MediaFS::CheckMediaDepth($row["m_file"]);
		$mymedia[$mid]["gedcom"] = $row["m_gedrec"];
		$mymedia[$mid]["gedfile"] = $row["m_gedfile"];
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $mymedia;
}

?>