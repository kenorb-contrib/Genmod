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
/**
* @package Genmod
* @subpackage Calendar
**/

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
	if (!is_array($query)) $sql = "SELECT i_id, i_file, i_gedrec, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND n_name REGEXP '".DbLayer::EscapeQuery($query)."'";
	else {
		$sql = "SELECT i_id, i_file, i_gedrec, i_isdead, n_name, n_letter, n_type, n_surname FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND (";
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
		if ($allgeds) $key = $row["i_id"]."[".$row["i_file"]."]";
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
	return $myindilist;
}

//-- search through the gedcom records for families
function SearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $famlist, $GEDCOMS, $GEDCOMID;
	
	$myfamlist = array();
	if (!is_array($query)) $sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (f_gedrec REGEXP '".DbLayer::EscapeQuery($query)."')";
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
		$myfamlist[$key]["gedcom"] = $row["f_gedrec"];
		$myfamlist[$key]["HUSB"] = $husb;
		$myfamlist[$key]["WIFE"] = $wife;
	}
	$GEDCOMID = $gedold;
	$res->FreeResult();
	return $myfamlist;
}

//-- search through the gedcom records for families
function SearchFamsNames($query, $ANDOR="AND", $allnames=false) {
	global $famlist, $GEDCOMS, $GEDCOMID, $COMBIKEY;

	$myfamlist = array();
	$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."families WHERE (";
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
		$myfamlist[$key]["gedcom"] = $row["f_gedrec"];
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



//-- search through the gedcom records for sources
function SearchSources($query, $allgeds=false, $ANDOR="AND") {
	global $GEDCOMID;
	
	$mysourcelist = array();	
	if (!is_array($query)) $sql = "SELECT s_id, s_name, s_file, s_gedrec FROM ".TBLPREFIX."sources WHERE (s_gedrec REGEXP '".DbLayer::EscapeQuery($query)."')";
	else {
		$sql = "SELECT s_id, s_name, s_file, s_gedrec FROM ".TBLPREFIX."sources WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(s_gedrec REGEXP '".DbLayer::EscapeQuery($q)."')";
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

?>