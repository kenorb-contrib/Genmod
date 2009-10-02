<?php
/**
 * Searches based on user query.
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

/**
 * Inclusion of the configuration file
*/
require("config.php"); 

$COMBIKEY = true;

if (!isset($view)) $view = "";
if (!isset($action)) $action = "";

// We come from the quick start block
if ($action == "quickstart") {
	if (isset($query) && !empty($query)) {
		$action = "general";
	}
	else {
		$action = "soundex";
		$soundex = "Russell";
		if (!empty($firstname) && HasChinese($firstname)) $soundex = "DaitchM"; 
		if (!empty($lastname) && HasChinese($lastname)) $soundex = "DaitchM"; 
		if (!empty($place) && HasChinese($place)) $soundex = "DaitchM";
	}
	if (isset($crossged) && $crossged == "yes" && $ALLOW_CHANGE_GEDCOM) {
		foreach ($GEDCOMS as $key=>$ged) {
			$str = "sg".$key;
			$$str = "yes";
		}
	}
}

// Remove slashes
if (isset($query)) {
	// Reset the "Search" text from the page header
	if ($query == $gm_lang["search"]) {
		unset($query);
		$action = "";
		unset($topsearch);
	}
	else {
		$query = stripslashes($query);
		$myquery = $query;
	}
}
if (!isset($soundex)) $soundex = "Russell";
if (!isset($nameprt)) $nameprt = "";
if (!isset($tagfilter)) $tagfilter = "on";
if (!isset($showasso)) $showasso = "off";
if (!isset($sorton)) $sorton = "";

// if no general search specified, search in indi's
if (!isset($srindi) && !isset($srfams) && !isset($srsour)&& !isset($srmedia)&& !isset($srnote)&& !isset($srrepo)) $srindi = "yes";

// If we only search on a place, no hits are likely to occur in person names. So, set nameprt to all.
if (empty($firstname) && empty($lastname) && !empty($place)) $nameprt = "all";

if (!empty($firstname)) $myfirstname = stripslashes($firstname);
else {
	unset($firstname);
	$myfirstname = "";
}
if (!empty($lastname)) $mylastname = stripslashes($lastname);
else {
	unset($lastname);
	$mylastname = "";
}	
if (!empty($place)) $myplace = stripslashes($place);
else {
	unset($place);
	$myplace = "";
}
if (!empty($year)) $myyear = $year;
else {
	unset($year);
	$myyear = "";
}

// This section is to handle searches entered in the top search box in the themes
if (isset($topsearch)) {
	
	// first set some required variables. Search only in current gedcom, only in indi's.
	$srindi = "yes";
	
	// Enable the default gedcom for search
	$str = "sg".$GEDCOMID;
	$$str = "yes";

	// Then see if an ID is typed in. If so, we might want to jump there.
	if (isset($query)) {

		// see if it's an indi ID. If it's found and privacy allows it, JUMP!!!!
		if (FindPersonRecord($query)) {
			if (PrivacyFunctions::showLivingNameByID(str2upper($query))) {
				header("Location: individual.php?pid=".$query."&gedid=".$GEDCOMID);
				exit;
			}
		}

		// see if it's a family ID. If it's found and privacy allows it, JUMP!!!!
		$f = FindFamilyRecord($query); 
		if ($f) {
			//-- check if we can display both parents
			if (PrivacyFunctions::displayDetailsByID(str2upper($query), "FAM") == true) {
				$parents = FindParents($query);
				if (PrivacyFunctions::showLivingNameByID($parents["HUSB"]) && PrivacyFunctions::showLivingNameByID($parents["WIFE"])) {
					$ct = preg_match("/0 @(.*)@ (.*)/", $f, $match);
					if ($ct>0) {
						$fid = trim($match[1]);
						header("Location: family.php?famid=".$fid."&gedid=".$GEDCOMID);
						exit;
					}
				}
			}
		}

		// see if it's an source ID. If it's found and privacy allows it, JUMP!!!!
		if ($SHOW_SOURCES >= $gm_user->getUserAccessLevel()) {
			if (FindSourceRecord($query)) {
				header("Location: source.php?sid=".$query."&gedid=".$GEDCOMID);
				exit;
			}
		}

		// see if it's a repository ID. If it's found and privacy allows it, JUMP!!!!
		if ($SHOW_SOURCES >= $gm_user->getUserAccessLevel()) {
			if (FindRepoRecord($query)) {
				header("Location: repo.php?rid=".$query."&gedid=".$GEDCOMID);
				exit;
			}
		}
		
		// see if it's a media ID. If it's found and privacy allows it, JUMP!!!!
		$f = FindMediaRecord($query);
		if (!empty($f) && PrivacyFunctions::DisplayDetailsByID(str2upper($query), "OBJE", 1, true)) {
			header("Location: mediadetail.php?mid=".$query."&gedid=".$GEDCOMID);
			exit;
		}
		
		// see if it's a note ID. If it's found and privacy allows it, JUMP!!!!
		$f = FindOtherRecord($query);
		if (!empty($f) && PrivacyFunctions::DisplayDetailsByID(str2upper($query), "NOTE", 1, true)) {
			$type = GetRecType($f);
			if ($type == "NOTE") {
				header("Location: note.php?oid=".$query."&gedid=".$GEDCOMID);
				exit;
			}
		}
	}
}

// Retrieve the gedcoms to search in
$sgeds = array();
if (($ALLOW_CHANGE_GEDCOM) && (count($GEDCOMS) > 1)) {
	foreach ($GEDCOMS as $key=>$ged) {
		// BUT we must NOT search in a gedcom with authentication required and a user not logged in!
		$str = "sg".$key;
		SwitchGedcom($key);
		if ($REQUIRE_AUTHENTICATION && $gm_user->username == "") unset($$str);
		if (isset($$str)) $sgeds[] = $key;
	}
	SwitchGedcom();
}
else $sgeds[] = $GEDCOMID;

// if no search gedcom is specified, take the default gedcom
if (empty($sgeds)) $sgeds[] = $GEDCOMID;

// If we want to show associated persons, build the list
if ($showasso == "on") GetAssoList();

// Section to gather results for general search
if ($action=="general") {

	//-- perform the search
	if (isset($query)) {
		
		// -- array of names to be used for results. Must be here and empty.
		$sindilist = array();
		$ssourcelist = array();
		$sfamlist = array();
		$sfamlist2 = array();
		$srepolist = array();
		$smedialist = array();
		$snotelist = array();

		// Keep track of what indi's are already printed to keep a reliable counter
		$indi_printed = array();
		$fam_printed = array();
		$sour_printed = array();
		$repo_printed = array();
  
		// init various counters
		InitListCounters();
		
		// Now see if there is a query left after the cleanup
		if (trim($query)!="") {

			// Write a log entry
			$logstring = "General, ".$query;
			WriteToLog($logstring, "I", "F", $sgeds);
		
			// Cleanup the querystring so it can be used in a database query
			// Note: when more than one word is entered, this will return results where one word
			// is in one subrecord, another in another subrecord. Theze results are filtered later.
			if (strlen($query) == 1) $query = preg_replace(array("/\?/", "/\|/", "/\*/"), array("\\\?","\\\|", "\\\\\*") , $query);
			$query = preg_replace(array("/\(/", "/\)/", "/\//", "/\]/", "/\[/", "/\s+/"), array('\(','\)','\/','\]','\[', ' '), $query);
			
			// Get the cleaned up query to use in result comparisons
			$cquery = SearchFunctions::ParseFTSearchQuery($query);
			
			// Search the indi's
			if ((isset($srindi)) && (count($sgeds)>0)) {
				$sindilist = SearchFunctions::FTSearchIndis($query, $sgeds);
			}

			// Search the fams
			if ((isset($srfams)) && (count($sgeds)>0)) {
				// Get the indilist, to check name hits. Store the ID's/names found in
				// the search array, so the fam records can be retrieved.
				// This way we include hits on family names.
				// If indi's are not searched yet, we have to search them first
				if (!isset($srindi)) $sindilist = SearchFunctions::FTSearchIndis($query, $sgeds);
				$famquery = array();
				foreach($sindilist as $key1 => $myindi) {
					$found = false;
					foreach($myindi["names"] as $key2 => $name) {
						foreach ($cquery["includes"] as $qindex => $squery) {
							if ((preg_match("/".$squery["term"]."/i", $name[0]) > 0)) {
								$found = true;
								$ged = SplitKey($key1, "gedid");
								$key1 = SplitKey($key1, "id");
								$famquery[] = array($key1, $ged);
								break;
							}
						}
						if ($found) break;
					}
				}
				// Get the famrecs with hits on names from the family table
				if (!empty($famquery)) $sfamlist = SearchFamsNames($famquery, "OR", true);

				// Get the famrecs with hits in the gedcom record from the family table
				if (!empty($query)) $sfamlist2 = SearchFunctions::FTSearchFams($query, $sgeds, "OR", true);
				$sfamlist = GmArrayMerge($sfamlist, $sfamlist2);
				// clear the myindilist is no search was intended for indi's
				// if (!isset($srindi)) $sindilist = array();
			}

			// Search the sources
			if ((isset($srsour)) && (count($sgeds)>0)) {
				if (!empty($query)) $ssourcelist = SearchFunctions::FTSearchSources($query, $sgeds);
			}

			// Search the repositories
			if ((isset($srrepo)) && (count($sgeds)>0)) {
				if (!empty($query)) $srepolist = SearchFunctions::FTSearchRepos($query, $sgeds);
			}
			
			// Search the notes
			if ((isset($srnote)) && (count($sgeds)>0)) {
				if (!empty($query)) {
					$snotelist = SearchFunctions::FTSearchNotes($query, $sgeds);
				}
			}
			
			// Search the media
			if ((isset($srmedia)) && (count($sgeds)>0)) {
				if (!empty($query)) {
					$smedialist = SearchFunctions::FTSearchMedia($query, $sgeds);
				}
			}
			
			//-- if only 1 item is returned, automatically forward to that item
			// Check for privacy first. If ID cannot be displayed, continue to the search page.
			if ((count($sindilist)==1)&&(count($sfamlist)==0)&&(count($ssourcelist)==0) && (count($srepolist)==0) && (count($smedialist)==0) && count($snotelist) == 0 && (isset($srindi))) {
				foreach($sindilist as $key=>$indi) {
					$ged = SplitKey($key, "gedid");
					$pid = SplitKey($key, "id");
					SwitchGedcom($ged);
					if (!isset($assolist[$key])) {
						if (PrivacyFunctions::showLivingNameByID($pid)) {
							header("Location: individual.php?pid=".$pid."&gedid=".$indi["gedfile"]);
							exit;
						}
					}
				}
				SwitchGedcom();
			}
			if ((count($sindilist)==0 || !isset($srindi))&&(count($sfamlist)==1)&&(count($ssourcelist)==0) && (count($srepolist)==0) && (count($smedialist)==0) && count($snotelist) == 0) {
				foreach($sfamlist as $famid=>$fam) {
					$ged = SplitKey($famid, "gedid");
					$famid = SplitKey($famid, "id");
					SwitchGedcom($ged);
					if (PrivacyFunctions::displayDetailsByID($famid, "FAM") == true) {
						$parents = FindParents($famid);
						if (PrivacyFunctions::showLivingNameByID($parents["HUSB"]) && PrivacyFunctions::showLivingNameByID($parents["WIFE"])) {
							header("Location: family.php?famid=".$famid."&gedid=".$fam["gedfile"]);
							exit;
						}
					}
				}
				SwitchGedcom();
			}
			if ((count($sindilist)==0 || !isset($srindi))&&(count($sfamlist)==0)&&(count($ssourcelist)==1) && (count($srepolist)==0) && (count($smedialist)==0) && count($snotelist==0)) {
				foreach($ssourcelist as $sid=>$source) {
					$ged = SplitKey($sid, "gedid");
					$sid = SplitKey($sid, "id");
					SwitchGedcom($ged);
					if (PrivacyFunctions::displayDetailsByID($sid, "SOUR", 1, true)) {
						header("Location: source.php?sid=".$sid."&gedid=".$source["gedfile"]);
						exit;
					}
				}
				SwitchGedcom();
			}
			if ((count($sindilist)==0 || !isset($srindi))&&(count($sfamlist)==0)&&(count($ssourcelist)==0) && (count($srepolist)==1)  && (count($smedialist)==0)&& count($snotelist) == 0) {
				foreach($srepolist as $rid=>$repo) {
					$ged = SplitKey($rid, "gedid");
					$rid = SplitKey($rid, "id");
					SwitchGedcom($ged);
					if (PrivacyFunctions::displayDetailsByID($rid, "REPO", 1, true)) {
						header("Location: repo.php?rid=".$rid."&gedid=".$repo["gedfile"]);
						exit;
					}
				}
				SwitchGedcom();
			}
			if ((count($sindilist)==0 || !isset($srindi))&&(count($sfamlist)==0)&&(count($ssourcelist)==0) && (count($srepolist)==0) && (count($smedialist)==0) && count($snotelist) == 1) {
				foreach($snotelist as $oid=>$note) {
					header("Location: note.php?oid=".$note->xref."&gedid=".$note->gedcomid);
					exit;
				}
			}
		}
	}
}

if ($action=="soundex") {
	// Only place filled in is incorrect. Search place only in combination with the other fields.
	if ((!empty($lastname) || !empty($firstname)) && count($sgeds)>0) {
		$logstring = "Soundex, ";
		if (!empty($lastname)) $logstring .= "Last name: ".$lastname."<br />";
		if (!empty($firstname)) $logstring .= "First name: ".$firstname."<br />";
		if (!empty($place)) $logstring .= "Place: ".$place."<br />";
		if (!empty($year)) $logstring .= "Year: ".$year."<br />";
		WriteToLog($logstring, "I", "F", $sgeds);
		
    	if (isset($lastname)) {
	    	// see if there are brackets around letter(groups)
	    	$bcount = substr_count($lastname, "[");
	    	if (($bcount == substr_count($lastname, "]")) && $bcount > 0) {
		    	$barr = array();
			    $ln = $lastname;
			    $pos = 0;
			    $npos = 0;
		    	for ($i=0; $i<$bcount; $i++) {
					$pos1 = strpos($ln, "[")+1;
					$pos2 = strpos($ln, "]");
					$barr[$i] = array(substr($ln, $pos1, $pos2-$pos1), $pos1+$npos-1, $pos2-$pos1);
					$npos = $npos + $pos2 - 1;
					$ln = substr($ln, $pos2+1);
				}
				// Then strip the brackets so we can search
				$lastname = preg_replace(array("/\[/", "/\]/"), array("",""), $lastname);
			}
		}
		if (isset($year)) {
			// We must be able to search regexp in the DB for years
	    	if (strlen($year) == 1) $year = preg_replace(array("/\?/", "/\|/", "/\*/"), array("\\\?","\\\|", "\\\\\*") , $year);
			$year = preg_replace(array("/\s+/", "/\(/", "/\)/"), array(".*",'\(','\)'), $year);
		}
		$sindilist = array();
			
		if ($soundex == "DaitchM") DMsoundex("", "opencache");

		// Do some preliminary stuff: determine the soundex codes for the search criteria
		if (!empty($lastname)) {
			$orglastname = $lastname;
			if (HasChinese($lastname, true)) $lastname = GetPinYin($lastname, true);
			$lastnames = preg_split("/\s/", trim($lastname));
			$larr = array();
			for($j=0; $j<count($lastnames); $j++) {
				if ($soundex == "Russell") $larr[$j] = soundex($lastnames[$j]);
				if ($soundex == "DaitchM") $larr[$j] = DMsoundex($lastnames[$j]);
			}
		}
		if (!empty($firstname)) {
			$orgfirstname = $firstname;
			if (HasChinese($firstname, true)) $firstname = GetPinYin($firstname, true);
			$firstnames = preg_split("/\s/", trim($firstname));
			$farr = array();
			for($j=0; $j<count($firstnames); $j++) {
				if ($soundex == "Russell") $farr[$j] = soundex($firstnames[$j]);
				if ($soundex == "DaitchM") $farr[$j] = DMsoundex($firstnames[$j]);
			}
		}
		$parr = array();
		if (isset($place)) {
			if (HasChinese($place, true)) $place = GetPinYin($place, true);
			if ((!empty($place)) && ($soundex == "DaitchM")) $parr = DMsoundex($place);
			if ((!empty($place)) && ($soundex == "Russell")) $parr = soundex(trim($place));
		}
		// Start the search
		$oldged = $GEDCOMID;
		$printindiname = array();
		$printfamname = array();
		$indi_printed = array();
		$fam_printed = array();

		// Build the query
		$sql = "SELECT DISTINCT n_id, i_key, i_id, i_gedrec, i_file, i_isdead, n_name, n_surname, n_type, n_letter FROM ";

		if (isset($farr)) $sql .= TBLPREFIX."soundex as s1, ";
		if (isset($larr)) $sql .= TBLPREFIX."soundex as s2, ";
		$sql .= TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND ";
		
		if (isset($farr)) {
			
			if (count($sgeds) != count($GEDCOMS)) {
				$sql .= " (";
				$i = 0;
				foreach ($sgeds as $key => $gedcomid) {
					if ($i != 0) $sql .= " OR ";
					$i++;
					$sql .= "s1.s_file='".$gedcomid."'";
				}
				$sql .= ") AND";
			}
		
			$sql .= " i_key=s1.s_gid AND ";
			if ($soundex == "Russell") $sql .= "s1.s_type='R'";
			else $sql .= "s1.s_type='D'";
			$sql .= " AND s1.s_nametype='F' AND (";
			$i = 0;
			foreach ($farr as $key => $code) {
				if ($soundex == "Russell") {
					if ($i > 0) $sql .= " OR ";
					$i++;
					$sql .= "s1.s_code LIKE '".$code."'";
				}
				else {
					foreach ($code as $key2 => $value) {
						if ($i > 0) $sql .= " OR ";
						$i++;
						$sql .= "s1.s_code LIKE '".$value."'";
					}
				}
			}
			$sql .= ")";
		}
		if (isset($larr)) {
			if (isset($farr)) $sql .= " AND i_key=s2.s_gid AND s1.s_type=s2.s_type AND s2.s_nametype='L' AND (";
			else {
				if (count($sgeds) != count($GEDCOMS)) {
					$sql .= " (";
					$i = 0;
					foreach ($sgeds as $key => $gedcomid) {
						if ($i != 0) $sql .= " OR ";
						$i++;
						$sql .= "s2.s_file='".$gedcomid."'";
					}
					$sql .= ") AND";
				}
				
				$sql .= " i_key=s2.s_gid AND s2.s_nametype='L' AND (";
			}
			$i = 0;
			foreach ($larr as $key => $code) {
				if ($soundex == "Russell") {
					if ($i > 0) $sql .= " OR ";
					$i++;
					if (isset($farr)) $sql .= "s2.s_code LIKE '".$code."'";
					else $sql .= "s2.s_code LIKE '".$code."'";
				}
				else {
					foreach ($code as $key2 => $value) {
						if ($i > 0) $sql .= " OR ";
						$i++;
						if (isset($farr)) $sql .= "s2.s_code LIKE '".$value."'";
						else $sql .= "s2.s_code LIKE '".$value."'";
					}
				}
			}
			$sql .= ")";
		}
		$res = NewQuery($sql);		
		$sindilist = array();
		if ($res) {
			while ($row = $res->FetchAssoc()) {
				if (!isset($sindilist[$row["i_key"]])) {
					$indi = array();
					$indi["gedcom"] = $row["i_gedrec"];
					$indi["gedfile"] = $row["i_file"];
					$indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
					$indi["isdead"] = $row["i_isdead"];
					$sindilist[$row["i_key"]] = $indi;
				}
				else $sindilist[$row["i_key"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			}
		}

		// -- Check the result on places
		foreach ($sindilist as $key => $value) {
			$save = true;
			$ikey = SplitKey($key, "id");
			if ((!empty($place))||(!empty($year))) {
				$indirec = $value["gedcom"];
				if (!empty($place)) {
					$savep=false;
					$pt = preg_match_all("/\d PLAC (.*)/i", $indirec, $match, PREG_PATTERN_ORDER );
					if ($pt>0) {
						$places = array();
						for ($pp=0; $pp<count($match[1]); $pp++){
							// Split on chinese comma 239 188 140
							$match[1][$pp] = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $match[1][$pp]);
							$places[$pp] =preg_split("/,\s/", trim($match[1][$pp]));
						}
						$cp=count($places);
						$p = 0;
						while($p<$cp && $savep == false) {
							$pp = 0;
							while($pp<count($places[$p]) && $savep == false) {
								if (HasChinese($places[$p][$pp])) $pl = GetPinYin(trim($places[$p][$pp]));
								else $pl = trim($places[$p][$pp]);
								if ($soundex == "Russell") {
									if (soundex($pl) == $parr) $savep = true;
								}
								if ($soundex == "DaitchM") {
									$arr1 = DMsoundex($pl);
									$a = array_intersect($arr1, $parr);
									if (!empty($a)) $savep = true;
								}
								$pp++;
							}
							$p++;
						}
					}
					if ($savep == false) $save = false;
				}
				// Check the result on years
				if (!empty($year) && $save==true) {
					$yt = preg_match("/\d DATE (.*$year.*)/i", $indirec, $match);
					if ($yt==0) $save = false;
				}
			}
			// Add either all names or names with a hit
			if ($save === true) {
				if ($nameprt == "all") {
					foreach($value["names"] as $indexval => $namearray) {
						$printindiname[] = array(SortableNameFromName($namearray[0]), $ikey, $value["gedfile"],"");
					}
					$indi_printed[JoinKey($ikey, $value["gedfile"])] = "1";
				}
				else {
					foreach($value["names"] as $indexval => $namearray) {
						$print = false;
						$name = split("/", $namearray[0]);
						if (!empty($lastname) && HasChinese($orglastname, true) == HasChinese($name[1], true)) {
							if (HasChinese($name[1], true)) $name[1] = GetPinYin($name[1], true);
							$lnames = preg_split("/\s/", trim($name[1]));
							foreach ($lnames as $namekey => $name) {
								if ($soundex == "DaitchM") {
									$lndm = DMSoundex($name);
									foreach($larr as $arrkey => $arrvalue) {
										$a = array_intersect($lndm, $arrvalue);
										if (!empty($a)) {
											$print = true;
											break;
										}
									}
								}
								else {
									if (in_array(soundex($name), $larr)) $print = true;
								}
								if ($print) break;
							}
						}
						if (!empty($firstname) && $print == false  && HasChinese($orgfirstname, true) == HasChinese($name[0], true)) {
							if (HasChinese($name[0], true)) $name[0] = GetPinYin($name[0], true);
							$fnames = preg_split("/\s/", trim($name[0]));
							foreach ($fnames as $namekey => $name) {
								if ($soundex == "DaitchM") {
									$fndm = DMSoundex($name);
									foreach($farr as $arrkey => $arrvalue) {
										$a = array_intersect($fndm, $arrvalue);
										if (!empty($a)) {
											$print = true;
											break;
										}
									}
								}
								else {
									if (in_array(soundex($name), $farr)) $print = true;
								}
								if ($print) break;
							}
						}
						if ($print) {
							$printindiname[] = array(SortableNameFromName($namearray[0]), $ikey, $value["gedfile"],"");
							$indi_printed[JoinKey($ikey, $value["gedfile"])] = "1";
						}
					}
				}
			}
		}
		DMSoundex("", "closecache");
		$GEDCOMID = $oldged;
		// check the result on required characters
		if (isset($barr)) {
			foreach ($printindiname as $pkey=>$pname) {
				$print = true;
				foreach ($barr as $key=>$checkchar) {
					if (Str2Upper(substr($pname[0], $checkchar[1], $checkchar[2])) != Str2Upper($checkchar[0])) {
						$print = false;
						break;
					}
				}
				if ($print == false) {
					unset($indi_printed[JoinKey($printindiname[$pkey][2], $printindiname[$pkey][3])]);
					unset($printindiname[$pkey]);
				}
			}
		}
		// Now we have the final list of indi's to be printed.
		// We may add the assos at this point.
		if ($showasso == "on") SearchAddAssos();
		//-- if only 1 item is returned, automatically forward to that item
		if (count($printindiname)==1) {
			$GEDCOMID = $printindiname[0][2];
			SwitchGedcom($GEDCOMID);
			if (PrivacyFunctions::showLivingNameByID($printindiname[0][1])) {
				header("Location: individual.php?pid=".$printindiname[0][1]."&gedid=".$printindiname[0][2]);
				exit;
			}
			SwitchGedcom();
		}
		if ($sorton == "last" || $sorton == "") uasort($printindiname, "ItemSort");
		else uasort($printindiname, "FirstnameSort");
		reset($printindiname);
	}
}

print_header($gm_lang["search"]);
?>
<script language="JavaScript" type="text/javascript">
<!--
	function checknames(frm) {
		if (frm.action[1].checked) {
			if (frm.year.value!="") {
				message=true;
				if (frm.firstname.value!="") message=false;
				if (frm.lastname.value!="") message=false;
				if (frm.place.value!="") message=false;
				if (message) {
					alert("<?php print $gm_lang["invalid_search_input"]?>");
					frm.firstname.focus();
					return false;
				}
			}
		}
		if (frm.action[0].checked) {
			if (frm.query.value.length<1) {
					alert("<?php print $gm_lang["search_more_chars"]?>");
					frm.query.focus();
					return false;
			}
		}
		return true;
	}
//-->
</script>
<?php
	// print "<div id=\"search_content\">";
		print "<div id=\"search_header\">";
		print $gm_lang["search_gedcom"];
		print "</div>";

if ($view == "preview") {
	// to be done
	//	print "</td><tr><td align=\"center\">".$logstring."</td></tr></table>";
}
else {
	print "<div id=\"search_options\">";
		// start of new searchform
		print "<div class=\"topbottombar\">";
		print_help_link("search_options_help", "qm","search_options");
		print $gm_lang["search_options"];
		print "</div>";
		print "<form method=\"post\" onsubmit=\""?>return checknames(this);<?php print " \" action=\"$SCRIPT_NAME\">";
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		
		// If more than one GEDCOM, switching is allowed AND DB mode is set, let the user select
		if ((count($GEDCOMS) > 1) && ($ALLOW_CHANGE_GEDCOM)) {
			print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
			print $gm_lang["search_geds"];
			print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
			echo '<input type="checkbox" onclick="CheckAllGed(this)" />'.$gm_lang['select_deselect_all'].'<br>';
			foreach ($GEDCOMS as $key=>$ged) {
				SwitchGedcom($key);
				if ($gm_user->username != "" || !$REQUIRE_AUTHENTICATION) {
					$str = "sg".$key;
					print "<input type=\"checkbox\" ";
					if (($key == $GEDCOMID) && ($action == "")) print "checked=\"checked\" ";
					else {
						if (in_array($key, $sgeds)) print "checked=\"checked\" ";
					}
					print "value=\"yes\" class=\"checkged\" name=\"".$str."\""." />".$GEDCOMS[$key]["title"]."<br />";
				}
			}
			SwitchGedcom();
			print "</td></tr>";
		}
		
		// Show associated persons/fams?
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
		print $gm_lang["search_asso_label"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"checkbox\" name=\"showasso\" value=\"on\" ";
		if ($showasso == "on") print "checked=\"checked\" ";
		print "/>".$gm_lang["search_asso_text"];
		print "</td></tr>";
		
		// switch between general and soundex
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".$gm_lang["search_type"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"radio\" name=\"action\" value=\"general\" ";
		if ($action == "general") print "checked=\"checked\" ";
		print "onclick=\"expand_layer('gsearch'); expand_layer('ssearch');\" />".$gm_lang["search_general"];
		print "<br /><input type=\"radio\" name=\"action\" value=\"soundex\" ";
		if (($action == "soundex") || ($action == "")) print "checked=\"checked\" ";
		print "onclick=\"expand_layer('gsearch'); expand_layer('ssearch');\" />".$gm_lang["search_soundex"];
		print "</td></tr>";
		print "</table>";
	print "</div>";
	
		// The first searchform
	print "<div id=\"gsearch\" style=\"display: ";
		if ($action == "soundex" || $action == "") print "none\">";
		else print "block\">";
		print "<div class=\"topbottombar\">";
		print_help_link("search_enter_terms_help", "qm", "search_general");
		print $gm_lang["search_general"];
		print "</div>";
		
		// search terms
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
		print $gm_lang["enter_terms"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\"><input tabindex=\"1\" type=\"text\" name=\"query\" value=\"";
		if ($action=="general" && isset($myquery)) print htmlspecialchars($myquery);
		else print "";
		print "\" />";
		print "</td><td class=\"shade3\" style=\"vertical-align: middle; padding: 5px;\" rowspan=\"3\">";
		print "<input tabindex=\"2\" type=\"submit\" value=\"".$gm_lang["search"]."\" /></tr>";
		// Choice where to search
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".$gm_lang["search_inrecs"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"checkbox\"";
		if ((isset($srindi)) || ($action == "")) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srindi\" />".$gm_lang["search_indis"]."<br />";
		print "<input type=\"checkbox\"";
		if (isset($srfams)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srfams\" />".$gm_lang["search_fams"]."<br />";
		if (PrivacyFunctions::ShowSourceFromAnyGed()) {
			print "<input type=\"checkbox\"";
			if (isset($srsour)) print " checked=\"checked\"";
			print " value=\"yes\" name=\"srsour\" />".$gm_lang["search_sources"]."<br />";
			print "<input type=\"checkbox\"";
			if (isset($srrepo)) print " checked=\"checked\"";
			print " value=\"yes\" name=\"srrepo\" />".$gm_lang["search_repos"]."<br />";
		}
		print "<input type=\"checkbox\"";
		if (isset($srmedia)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srmedia\" />".$gm_lang["search_media"]."<br />";
		print "<input type=\"checkbox\"";
		if (isset($srnote)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srnote\" />".$gm_lang["search_notes"]."<br />";
		print "</td>";
		print "</tr>";
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".$gm_lang["search_tagfilter"]."</td>";
		print "<td class=\"shade1\" style=\"padding: 5px;\"><input type=\"radio\" name=\"tagfilter\" value=\"on\" ";
		if (($tagfilter == "on") || ($tagfilter == "")) print "checked=\"checked\" ";
		print ">".$gm_lang["search_tagfon"]."<br /><input type=\"radio\" name=\"tagfilter\" value=\"off\" ";
		if ($tagfilter == "off") print "checked=\"checked\"";
		print " />".$gm_lang["search_tagfoff"];
		print "</td></tr>";

		print "</table>";
	print "</div>";
	
		// The second searchform
	print "<div id=\"ssearch\" style=\"display: ";
		if ($action == "soundex" || $action == "") print "block\">";
		else print "none\">";
		print "<div class=\"topbottombar\">";
		print_help_link("soundex_search_help", "qm");
		print $gm_lang["soundex_search"];
		print "</div>";
		
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		
		print "<tr><td class=\"shade2\">";
		print $gm_lang["lastname_search"];
		print "</td><td class=\"shade1\"><input tabindex=\"3\" type=\"text\" name=\"lastname\" value=\"";
		if ($action=="soundex") print $mylastname;
		print "\" /></td>";

		print "<td class=\"shade3\" style=\"vertical-align: middle; text-align: center; padding: 5px;\"  rowspan=\"4\">";
		print "<input tabindex=\"7\" type=\"submit\" value=\"";
		print $gm_lang["search"];
		print "\" /></td></tr>";

		print "<tr><td class=\"shade2\">";
		print $gm_lang["firstname_search"];
		print "</td><td class=\"shade1\">";
		print "<input tabindex=\"4\" type=\"text\" name=\"firstname\" value=\"";
		if ($action=="soundex") print $myfirstname;
		print "\" /></td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_place"];
		print "</td><td class=\"shade1\"><input tabindex=\"5\" type=\"text\" name=\"place\" value=\"";
		if ($action=="soundex") print $myplace;
		print "\" /></td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_year"];
		print "</td><td class=\"shade1\"><input tabindex=\"6\" type=\"text\" name=\"year\" value=\"";
		if ($action=="soundex") print $myyear;
		print "\" /></td>";
		print "</tr>";
		print "<tr><td class=\"shade2\" >";
		print $gm_lang["search_soundextype"];
		print "<td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"soundex\" value=\"Russell\" ";
		if (($soundex == "Russell") || ($soundex == "")) print "checked=\"checked\" ";
		print " />".$gm_lang["search_russell"]."<br /><input type=\"radio\" name=\"soundex\" value=\"DaitchM\" ";
		if ($soundex == "DaitchM") print "checked=\"checked\" ";
		print " />".$gm_lang["search_DM"];
		print "</td>";
		print "</td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_prtnames"];
		print "</td><td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"nameprt\" value=\"hit\" ";
		if (($nameprt == "hit")) print "checked=\"checked\" ";
		print ">".$gm_lang["search_prthit"]."<br /><input type=\"radio\" name=\"nameprt\" value=\"all\" ";
		if ($nameprt == "all" || ($nameprt == "")) print "checked=\"checked\" ";;
		print " />".$gm_lang["search_prtall"];
		print "</td>";
		print "</td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_sorton"];
		print "</td><td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"sorton\" value=\"last\" ";
		if (($sorton == "last") || ($sorton == "")) print "checked=\"checked\" ";
		print ">".$gm_lang["lastname_search"]."<br /><input type=\"radio\" name=\"sorton\" value=\"first\" ";
		if ($sorton == "first") print "checked=\"checked\" ";;
		print " />".$gm_lang["firstname_search"];
		print "</td>";
		print "</td></tr>";
		print "</table>";
	print "</div>";
	print "</form>";
}			

// ---- section to search and display results on a general keyword search
if ($action=="general") {
	if ((isset($query)) && ($query!="")) {
		
		//--- Results in these tags will be ignored when the tagfilter is on

		// Never show results in _UID
		if ($gm_user->userIsAdmin()) $skiptags = "_UID";
		
		// If not admin, also hide searches in RESN tags
		else $skiptags = "RESN, _UID";
		
		// Add the optional tags
		$skiptags_option = ", _GMU, FILE, FORM, CHAN, SUBM, REFN";
    	if ($tagfilter == "on") $skiptags .= $skiptags_option;
   		$userlevel = $gm_user->GetUserAccessLevel();

		// printqueues for indi's and fams
		$printindiname = array();
		$printfamname = array();
		
		$cti=count($sindilist);
		$oldged = $GEDCOMID;
		if (($cti>0) && (isset($srindi))) {
			$curged = $GEDCOMID;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($sindilist as $key => $value) {
				$nodisplay = false;
				$GEDCOMID = SplitKey($key, "gedid");
				$key = SplitKey($key, "id");
				if ($GEDCOMID != $curged) {
					SwitchGedcom($GEDCOMID);
					$curged = $GEDCOMID;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
 					}
				}
				//-- make sure that the data that was searched on is not in a private part of the record
				$hit = false;
		    	$found = false;
		    	// First check if the hit is in the key!
		    	if ($tagfilter == "off") {
					foreach($cquery["includes"] as $qindex => $squery) {
		    			if (strpos(Str2Upper($key), Str2Upper($squery["term"])) !== false) {
			    			$hit = true;
			    			if (PrivacyFunctions::DisplayDetailsByID($key, "INDI") || PrivacyFunctions::showLivingNameByID($key)) $found = true;
			    			else {
				    			$nodisplay = true;
			    			}
			    			break;
		    			}
	    			}
	    		}
				if ($found == false && !$nodisplay) {
		    		$recs = GetAllSubrecords($value["gedcom"], "", false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						// We must remember the level 1 tag to check later. This was we can eliminate 
						// hits in 2 DATE which are valid, while we actually excluded hits in 1 CHAN.
						$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec, $level1tag);
						$level1tag = $level1tag[1];
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							foreach ($cquery["includes"] as $qindex => $squery) {
								if (preg_match("/".$squery["term"]."/i", $subrec2, $result)>0) {
									$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec2, $result2);
									if (($ct > 0) && (!empty($result2[1]))) {
//										print "Hit in".$subrec2."<br />leveltag: ".$level1tag."<br />tag:".$result2[1]."<br />";
										//$hit = true;
										// if the tag can be displayed, do so
										// but first check if the hit is in a link to a hidden record
										if (strpos($skiptagsged, $result2[1]) === false && strpos($skiptagsged, $level1tag) === false) {
											$ct9 = preg_match("/\d\s\w+\s@(.+)@/", $subrec2, $match9);
											if ($ct9) {
												$rtype = "";
												if (in_array($result2[1], array("FAMC", "FAMS"))) $rtype = "FAM";
												else if ($result2[1] == "ASSO") $rtype = "INDI";
												else if ($result2[1] == "SOUR") $rtype = "SOUR";
												else $rtype = "OBJE";
//												print "Check ".$match9[1]." leveltag ".$result2[1]." type ".$rtype." subrec ".$subrec2." for display<br />";
												if (!PrivacyFunctions::DisplayDetailsByID($match9[1], $rtype)) {
//													print "Don't show!<br />";
													$hit = true;
													$nodisplay = true;
													break;
												}
												else $found = true;
											}
											else $found = true;
										}
									}
								}
								if ($found == true || $nodisplay == true) break;
							}
							if ($found == true || $nodisplay == true) break;
						}
						if ($found == true || $nodisplay == true) break;
					}
				}
//				if ($hit) print "Hit is true";
				if ($found == true && $nodisplay == false) {
					// print all names from the indi found
			    	foreach($value["names"] as $indexval => $namearray) {
						$printindiname[] = array(SortableNameFromName($namearray[0]), $key, $value["gedfile"], "");
					}
					$indi_printed[JoinKey($key, $GEDCOMID)] = "1";
				}
				else if ($hit == true) $indi_hide[$key."[".$value["gedfile"]."]"] = 1;
			}
		}
		$GEDCOMID = $oldged;
		SwitchGedcom($GEDCOMID);
		// Get the fams to be printed
		$ctf=count($sfamlist);
		if ($ctf>0 || count($printfamname)>0) {
			$oldged = $GEDCOMID;
			$curged = $GEDCOMID;
			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($sfamlist as $key => $value) {
				$GEDCOMID = SplitKey($key, "gedid");
				$key = SplitKey($key, "id");
				if ($GEDCOMID != $curged) {
					SwitchGedcom($GEDCOMID);
					$curged = $GEDCOMID;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	   						if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
					}
			  	}

				// lets see where the hit is
				$nodisplay = false;
			    $found = false;
				$hit = false;
		    	// First check if the hit is in the key!
		    	if ($tagfilter == "off") {
					foreach ($cquery["includes"] as $qindex => $squery) {
			    		if (strpos(Str2Upper($key), Str2Upper($squery["term"])) !== false) {
				    		if (PrivacyFunctions::DisplayDetailsByID($key, "FAM")) $found = true;
				    		else {
					    		$nodisplay = true;
				    			$hit = true;
			    			}
				    		break;
		    			}
	    			}
	    		}
				// If a name is hit, no need to check for tags
				if ($found == false) {
					foreach($value["name"] as $nkey => $famname) {
						foreach ($cquery["includes"] as $qindex => $squery) {
							if ((preg_match("/".$squery["term"]."/i", $famname)) > 0) {
								$found = true;
								break;
							}
						}
						if ($found) break;
					}
				}
				// If no hit in a name or ID, check if there is a hit on a valid tag
				if ($found == false && !$nodisplay) {
					$recs = GetAllSubrecords($value["gedcom"], $skiptagsged, false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						// We must remember the level 1 tag to check later. This was we can eliminate 
						// hits in 2 DATE which are valid, while we actually excluded hits in 1 CHAN.
						$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec, $level1tag);
						$level1tag = $level1tag[1];
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							foreach ($cquery["includes"] as $qindex => $squery) {
								if (preg_match("/".$squery["term"]."/i", $subrec2, $result)>0) {
									$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec2, $result2);
									if (($ct > 0) && (!empty($result2[1]))) {
//										print "Hit in".$subrec2."<br />leveltag: ".$level1tag."<br />tag:".$result2[1]."<br />";
										//$hit = true;
										// if the tag can be displayed, do so
										// but first check if the hit is in a link to a hidden record
										if (strpos($skiptagsged, $result2[1]) === false && strpos($skiptagsged, $level1tag) === false) {
											$ct9 = preg_match("/\d\s\w+\s@(.+)@/", $subrec2, $match9);
											if ($ct9) {
												$rtype = "";
												if (in_array($result2[1], array("CHIL", "HUSB", "WIFE", "ASSO"))) $rtype = "INDI";
												else if ($result2[1] == "SOUR") $rtype = "SOUR";
												else $rtype = "OBJE";
//												print "Check ".$match9[1]." leveltag ".$result2[1]." type ".$rtype." subrec ".$subrec2." for display<br />";
												if (!PrivacyFunctions::DisplayDetailsByID($match9[1], $rtype)) {
//													print "Don't show!<br />";
													$hit = true;
													$nodisplay = true;
													break;
												}
												else $found = true;
											}
											else $found = true;
										}
									}
								}
								if ($found == true || $nodisplay == true) break;
							}
							if ($found == true || $nodisplay == true) break;
						}
						if ($found == true || $nodisplay == true) break;
					}
				}
				if ($found == true && $nodisplay == false) {
					$printed = false;	
					foreach ($value["name"] as $namekey => $famname) {
						$famsplit = preg_split("/(\s\+\s)/", trim($famname));
						// Both names have to have the same direction and combination of chinese/not chinese
						$foundf = false;
						$founds = false;
						$founde = false;
						if (hasRTLText($famsplit[0]) == hasRTLText($famsplit[1]) && HasChinese($famsplit[0], true) == HasChinese($famsplit[1], true)) {
							// do not print if the hit only in the second name. We want it first.
							foreach ($cquery["includes"] as $qindex => $squery) {
//								print $squery."<br />".$famsplit[0]."<br />".$famsplit[1]."<br />";
								if (preg_match("/".$squery["term"]."/i", $famsplit[0]) > 0) $foundf = true;
								if (preg_match("/".$squery["term"]."/i", $famsplit[1]) > 0) $founds = true;
							}
//							if ($foundf) print "ff is waar";
//							if ($founds) print "fs is waar";
//							print "<br /><br />";
							// now we must also check that excluded names are not printed anyway, regardless of in name 1 or 2
							if (isset($cquery["excludes"])) {
								foreach ($cquery["excludes"] as $qindex => $squery) {
	//								print $squery."<br />".$famsplit[0]."<br />".$famsplit[1]."<br />";
									if (preg_match("/".$squery["term"]."/i", $famsplit[0]) > 0) $founde = true;
									if (preg_match("/".$squery["term"]."/i", $famsplit[1]) > 0) $founde = true;
								}
							}
							 
//							if ($foundf && !$founds && !$founde) // Not ok. Hit can be in both name parts!
							if ($foundf && !$founde) {
								$printfamname[]=array(CheckNN($famname), $key, $value["gedfile"],"");
								$fam_printed[JoinKey($key, $GEDCOMID)] = "1";
								$printed = true;
							}
						}
					}
					if (!$printed && !$founde) {
						$printfamname[]=array(CheckNN($value["name"][0]), $key, $value["gedfile"],"");
						$fam_printed[JoinKey($key, $GEDCOMID)] = "1";
					}						
		    	}
				else if ($hit == true) $fam_hide[$key."[".$value["gedfile"]."]"] = 1;
			}
			SwitchGedcom();
		}
	
		// Add the assos to the indi and famlist
	  	if ($showasso == "on") {
		  	SearchAddAssos();
		}
		SwitchGedcom();

		?>
		<script type="text/javascript">
		<!--
		function tabswitch(n) {
			if (n==7) n = 0;
			var tabid = new Array('0','indis','fams','sources','repos','media','notes');
			// show all tabs ?
			var disp='none';
			if (n==0) disp='block';
			// reset all tabs areas
			for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
			if ('<?php echo $view; ?>' != 'preview') {
				// current tab area
				if (n>0) document.getElementById(tabid[n]).style.display='block';
				// empty tabs
				for (i=0; i<tabid.length; i++) {
					var elt = document.getElementById('door'+i);
					if (document.getElementById('no_tab'+i)) { // empty ?
						if (<?php if ($gm_user->username != "") echo 'true'; else echo 'false';?>) {
							elt.style.display='block';
							elt.style.opacity='0.4';
							elt.style.filter='alpha(opacity=40)';
						}
						else elt.style.display='none'; // empty and not editable ==> hide
					}
					else elt.style.display='block';
				}
				// current door
				for (i=0; i<tabid.length; i++) {
					document.getElementById('door'+i).className='shade1 rela';
				}
				document.getElementById('door'+n).className='shade1';
				return false;
			}
		}
		//-->
		</script>
		<div id="result" class="width100" style="display: inline-block;"><br /><br />
		<div class="door">
		<dl>
		<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print $gm_lang["search_indis"]." (".count($sindilist).")";?></a></dd>
		<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print $gm_lang["search_fams"]." (".count($sfamlist).")";?></a></dd>
		<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print $gm_lang["search_sources"]." (".count($ssourcelist).")";?></a></dd>
		<dd id="door4"><a href="javascript:;" onclick="tabswitch(4)" ><?php print $gm_lang["search_repos"]." (".count($srepolist).")";?></a></dd>
		<dd id="door5"><a href="javascript:;" onclick="tabswitch(5)" ><?php print $gm_lang["search_media"]." (".count($media_total).")";?></a></dd>
		<dd id="door6"><a href="javascript:;" onclick="tabswitch(6)" ><?php print $gm_lang["search_notes"]." (".count($note_total).")";?></a></dd>
		<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print $gm_lang["all"]?></a></dd>
		</dl>
		</div><br /><br />
	
		<?php

		// Print the indis	
		print "<div id=\"indis\" class=\"tab_page\" style=\"display:none;\" >";
		if ((count($sindilist)>0)&& (isset($srindi))) {
			print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
			$indi_count = count($printindiname);
			if($indi_count > 12) print " colspan=\"2\"";
			print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".$gm_lang["people"]."\" alt=\"".$gm_lang["individuals"]."\" />&nbsp;&nbsp;";
			print $gm_lang["individuals"];
			print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
			$i=1;
			uasort($printindiname, "ItemSort");
			foreach($printindiname as $pkey => $pvalue) {
				if (isset($sindilist[$pvalue[1]."[".$pvalue[2]."]"])) {
					 $indilist[$pvalue[1]."[".$pvalue[2]."]"] = $sindilist[$pvalue[1]."[".$pvalue[2]."]"];
				 }
				print_list_person($pvalue[1], array(CheckNN($pvalue[0]), $pvalue[2]),"", $pvalue[3]);
				print "\n";
				if ($i==ceil($indi_count/2) && $indi_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul>&nbsp;</td></tr>";
			if ((count($sindilist)>0) && (isset($srindi))) {
				print "<tr><td>".$gm_lang["total_indis"]." ".$cti;
				if (count($indi_private)>0) print "  (".$gm_lang["private"]." ".count($indi_private).")";
				if (count($indi_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($indi_hide);
				if (count($indi_private)>0 || count($indi_hide)>0) print_help_link("privacy_error_help", "qm");
				print "</td></tr>";
			}
			print "</table>";
			
		}
		else print "<div id=\"no_tab1\"></div>";
		print "<br /></div>";
		
		// print the fams
		print "<div id=\"fams\" class=\"tab_page\" style=\"display:none;\" >";
		$ctf=count($sfamlist);
		if ($ctf>0 || count($printfamname)>0) {
			$oldged = $GEDCOMID;
			$curged = $GEDCOMID;

			uasort($printfamname, "ItemSort");
			print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
			$fam_count = count($printfamname);
			if($fam_count > 12) print " colspan=\"2\"";
			print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"".$gm_lang["families"]."\" />&nbsp;&nbsp;";
			print $gm_lang["families"];
			print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
			$i=1;
			foreach($printfamname as $pkey => $pvalue) {
				$hkey = $pvalue[1]."[".$pvalue[2]."]";
				if (isset($sfamlist[$hkey])) {
					$famlist[$hkey] = $sfamlist[$hkey];
				}
				print_list_family($pvalue[1], array($pvalue[0], $pvalue[2]), "", $pvalue[3]);
				print "\n";
				if ($i==ceil($fam_count/2) && $fam_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul>&nbsp;</td></tr>";
			if ((count($sfamlist)>0) || (count($printfamname)>0)) {
				print "<tr><td>".$gm_lang["total_fams"]." ".$ctf;
				if (count($fam_private)>0) print "  (".$gm_lang["private"]." ".count($fam_private).")";
				if (count($fam_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($fam_hide);
				if (count($fam_private)>0 || count($fam_hide)>0) print_help_link("privacy_error_help", "qm");
				print "</td></tr>";
			}
			print "</table>";
		}
		else print "<div id=\"no_tab2\"></div>";
		print "<br /></div>";
		
		// Print the sources
		print "<div id=\"sources\" class=\"tab_page\" style=\"display:none;\" >";
		$cts=count($ssourcelist);
		if ($cts>0) {
			uasort($ssourcelist, "SourceSort"); 
			$oldged = $GEDCOMID;
			$curged = $GEDCOMID;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
			$sour_count = count($ssourcelist);
			if($sour_count > 12) print " colspan=\"2\"";
			print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"".$gm_lang["sources"]."\" />&nbsp;&nbsp;";
			print $gm_lang["sources"];
			print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
			$i=1;
			foreach ($ssourcelist as $key => $value) {
				$GEDCOMID = SplitKey($key, "gedid");
				$key = SplitKey($key, "id");
				if ($curged != $GEDCOMID) {
					SwitchGedcom($GEDCOMID);
					$curged = $GEDCOMID;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
				  	}
				}
		    	$found = false;
		    	$hit = false;
		    	// First check if the hit is in the key!
		    	if ($tagfilter == "off") {
					foreach ($cquery["includes"] as $qindex => $squery) {
			    		if (strpos(Str2Upper($key), Str2Upper($squery["term"])) !== false) {
				    		$found = true;
				    		$hit = true;
				    		break;
		    			}
		    		}
	    		}
				if ($found == false) {
					$recs = GetAllSubrecords($value["gedcom"], $skiptagsged, false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						// We must remember the level 1 tag to check later. This was we can eliminate 
						// hits in 2 DATE which are valid, while we actually excluded hits in 1 CHAN.
						$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec, $level1tag);
						$level1tag = $level1tag[1];
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							foreach ($cquery["includes"] as $qindex => $squery) {
								if (preg_match("/".$squery["term"]."/i",$subrec2, $result)>0) {
									$ct = preg_match("/\d.(\S*).*/i",$subrec2, $result2);
									if (($ct > 0) && (!empty($result2[1]))) {
										$hit = true;
										// if the tag can be displayed, do so
										if (strpos($skiptagsged, $result2[1]) === false && strpos($skiptagsged, $level1tag) === false) $found = true;
									}
								}
								if ($found == true) break;
							}
							if ($found == true) break;
						}
						if ($found == true) break;
					}
				}
				if ($found == true) {
					print_list_source($key, $value);
					$sour_printed[$key."[".$GEDCOMID."]"] = "1";
					if ($i==ceil($sour_count/2) && $sour_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
					$i++;
				}
				else if ($hit == true) $source_hide[$key."[".$value["gedfile"]."]"] = 1;
			}
			print "\n\t\t</ul>&nbsp;</td></tr>";
			SwitchGedcom();
			if (count($source_total)>0) {
			// if ($cts > 0) {
				print "<tr><td>".$gm_lang["total_sources"]." ".$cts;
				if (count($source_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($source_hide);
				print "</td></tr>";
			}
			print "</table>";
		}
		else print "<div id=\"no_tab3\"></div>";
		print "<br /></div>";
		
		// Print the repositories
		print "<div id=\"repos\" class=\"tab_page\" style=\"display:none;\" >";
		$ctr = count($srepolist);
		if ($ctr > 0) {
			uasort($srepolist, "SourceSort"); 
			$oldged = $GEDCOMID;
			$curged = $GEDCOMID;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
			$repo_count = count($srepolist);
			if($repo_count > 12) print " colspan=\"2\"";
			print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" alt=\"".$gm_lang["search_repos"]."\" />&nbsp;&nbsp;";
			print $gm_lang["search_repos"];
			print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
			$i=1;
			foreach ($srepolist as $key => $value) {
				$GEDCOMID = SplitKey($key, "gedid");
				$key = SplitKey($key, "id");
				if ($curged != $GEDCOMID) {
					SwitchGedcom($GEDCOMID);
					$curged = $GEDCOMID;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
				  	}
				}
		    	$found = false;
		    	$hit = false;
		    	// First check if the hit is in the key!
		    	if ($tagfilter == "off") {
					foreach ($cquery["includes"] as $qindex => $squery) {
			    		if (strpos(Str2Upper($key), Str2Upper($squery["term"])) !== false) {
				    		$found = true;
				    		$hit = true;
				    		break;
		    			}
		    		}
	    		}
				if ($found == false) {
					$recs = GetAllSubrecords($value["gedcom"], $skiptagsged, false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						// We must remember the level 1 tag to check later. This was we can eliminate 
						// hits in 2 DATE which are valid, while we actually excluded hits in 1 CHAN.
						$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec, $level1tag);
						$level1tag = $level1tag[1];
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							foreach ($cquery["includes"] as $qindex => $squery) {
								if (preg_match("/".$squery["term"]."/i",$subrec2, $result)>0) {
									$ct = preg_match("/\d.(\S*).*/i",$subrec2, $result2);
									if (($ct > 0) && (!empty($result2[1]))) {
										$hit = true;
										// if the tag can be displayed, do so
										if (strpos($skiptagsged, $result2[1]) === false && strpos($skiptagsged, $level1tag) === false) $found = true;
									}
								}
								if ($found == true) break;
							}
							if ($found == true) break;
						}
						if ($found == true) break;
					}
				}
				if ($found == true) {
					print_list_repository($value["name"], $value);
					$repo_printed[$key."[".$GEDCOMID."]"] = "1";
					if ($i==ceil($repo_count/2) && $repo_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
					$i++;
				}
				else if ($hit == true) $repo_hide[$key."[".$value["gedfile"]."]"] = 1;
			}
			print "\n\t\t</ul>&nbsp;</td></tr>";
			SwitchGedcom();
			if (count($repo_total)>0) {
			// if ($cts > 0) {
				print "<tr><td>".$gm_lang["total_repositories"]." ".count($repo_total);
				if (count($repo_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($repo_hide);
				print "</td></tr>";
			}
			print "</table>";
		}
		else print "<div id=\"no_tab4\"></div>";
		print "<br /></div>";
		
		// Print the media
		print "<div id=\"media\" class=\"tab_page\" style=\"display:none;\" >";
		if (count($media_total) > 0) {
			print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
			$media_count = count($media_total) - count($media_hide);
			if($media_count>12)	print " colspan=\"2\"";
			print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" title=\"".$gm_lang["media"]."\" alt=\"".$gm_lang["media"]."\" />&nbsp;&nbsp;";
			print $gm_lang["media"];
			print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
			$i=1;
			foreach ($smedialist as $key => $value) {
				$ged = SplitKey($key, "gedid");
				$id = SplitKey($key, "id");
				print_list_media($id, $value, true);
				if ($i==ceil($media_count/2) && $media_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td>\n\t\t";
		
			print "</tr>";
			if (count($media_total) > 0) { 
				print "<tr><td>";
				print $gm_lang["total_media"]." ".count($media_total);
				if (count($media_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($media_hide);
				print "</td></tr>";
			}
			print "</table><br />";
		}
		else print "<div id=\"no_tab5\"></div>";
		print "<br /></div>";
		
		// Print the notes
		print "<div id=\"notes\" class=\"tab_page\" style=\"display:none;\" >";
		$ctn = count($note_total);
		$curged = $GEDCOMID;
		if ($ctn>0) {

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
			$note_count = count($snotelist);
			if($note_count > 12) print " colspan=\"2\"";
			print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" alt=\"".$gm_lang["notes"]."\" />&nbsp;&nbsp;";
			print $gm_lang["notes"];
			print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
			$i=1;
			foreach ($snotelist as $key => $note) {
				if ($note->gedcomid != $GEDCOMID) {
					SwitchGedcom($note->gedcomid);
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
				  	}
				}
		    	$found = false;
		    	$hit = false;
		    	// First check if the hit is in the key!
		    	if ($tagfilter == "off") {
					foreach ($cquery["includes"] as $qindex => $squery) {
			    		if (strpos(Str2Upper($note->xref), Str2Upper($squery["term"])) !== false) {
				    		$found = true;
				    		$hit = true;
				    		break;
		    			}
		    		}
	    		}
	    		// We must check the text that is in the note here, because it's at level 0 and not in the subrecs
	    		$text = Str2Upper($note->text);
				foreach ($cquery["includes"] as $qindex => $squery) {
			    	if (strpos($text, Str2Upper($squery["term"])) !== false || Str2Upper($squery["term"]) == "NOTE") {
				   		$found = true;
				   		$hit = true;
				   		break;
		    		}
		    	}
				if ($found == false) {
					$recs = GetAllSubrecords($note->gedrec, $skiptagsged, false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						// We must remember the level 1 tag to check later. This was we can eliminate 
						// hits in 2 DATE which are valid, while we actually excluded hits in 1 CHAN.
						$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec, $level1tag);
						$level1tag = $level1tag[1];
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							foreach ($cquery["includes"] as $qindex => $squery) {
								if (preg_match("/".$squery["term"]."/i",$subrec2, $result)>0) {
									$ct = preg_match("/\d.(\S*).*/i",$subrec2, $result2);
									if (($ct > 0) && (!empty($result2[1]))) {
										$hit = true;
										// if the tag can be displayed, do so
										if (strpos($skiptagsged, $result2[1]) === false && strpos($skiptagsged, $level1tag) === false) $found = true;
									}
								}
								if ($found == true) break;
							}
							if ($found == true) break;
						}
						if ($found == true) break;
					}
				}
				if ($found == true) {
					$note->PrintListNote();
					$note_printed[$note->xref."[".$GEDCOMID."]"] = "1";
					if ($i==ceil($note_count/2) && $note_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
					$i++;
				}
				else if ($hit == true) $note_hide[$note->xref."[".$note->gedcomid."]"] = 1;
			}
			print "\n\t\t</ul>&nbsp;</td></tr>";
			SwitchGedcom();
			if (count($note_total)>0) {
			// if ($cts > 0) {
				print "<tr><td>".$gm_lang["total_notes"]." ".count($note_total);
				if (count($note_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($note_hide);
				print "</td></tr>";
			}
			print "</table>";
		}
		else print "<div id=\"no_tab6\"></div>";
		print "<br /></div>";
		
print "</div>"; // End result div
		
	}
//	else if (isset($query)) print "<br /><div class=\"warning width80\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i><br /></div>\n\t\t";
}

// ----- section to search and display results for a Soundex last name search
if ($action=="soundex") {
	if ($soundex == "DaitchM") DMsoundex("", "closecache");
// 	$query = "";	// Stop function PrintReady from doing strange things to accented names
	if (((!empty($lastname))||(!empty($firstname)) ||(!empty($place))) && (isset($printindiname))) {
		$ct=count($printindiname);
		if ($ct > 0) {
			print "<div class=\"search_results\"><br />";
			print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr>\n\t\t";
			$i=0;
			InitListCounters();
			$extrafams = false;
			if (count($printfamname)>0) $extrafams = true;
			if ($extrafams) {
				print "<td class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["people"]."</td>";
				print "<td class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["families"]."</td>";
			}
			else print "<td colspan=\"2\" class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["people"]."</td>";
			print "</tr><tr>\n\t\t<td class=\"shade1 wrap\"><ul>";

			foreach ($printindiname as $key => $pvalue) {
				if (isset($sindilist[$pvalue[1]."[".get_id_from_gedcom($pvalue[2])."]"])) $indilist[$pvalue[1]."[".get_id_from_gedcom($pvalue[2])."]"] = $sindilist[$pvalue[1]."[".get_id_from_gedcom($pvalue[2])."]"];
				print_list_person($pvalue[1], array(CheckNN($pvalue[0]), $pvalue[2]), "", $pvalue[3]);
				$indi_printed[$pvalue[1]."[".$pvalue[2]."]"] = 1;
				print "\n";
				if (!$extrafams) {
					if ($i == floor(($ct-1) / 2) && $ct>9) print "\n\t\t</ul></td>\n\t\t<td class=\"list_value_wrap\"><ul>";
					$i++;
				}
			}
			print "\n\t\t</ul></td>";

			// Start printing the associated fams
			if ($extrafams) {
				uasort($printfamname, "ItemSort");
				print "\n\t\t<td class=\"shade1 wrap\"><ul>";
				foreach($printfamname as $pkey => $pvalue) {
					print_list_family($pvalue[1], array($pvalue[0], $pvalue[2]), "", $pvalue[3]);
					print "\n";
				}
				print "\n\t\t</ul>&nbsp;</td>";
			}

			// start printing the table footer
			print "\n\t\t</tr>\n\t";
			if (count($indi_total)>0) {
				print "<tr><td ";
				if ((!$extrafams) && ($ct > 9)) print "colspan=\"2\">";
				else print ">";
				print $gm_lang["total_indis"]." ".count($indi_total);
				if (count($indi_private)>0) print "  (".$gm_lang["private"]." ".count($indi_private).")";
				if (count($indi_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($indi_hide);
				if (count($indi_private)>0 || count($indi_hide)>0) print_help_link("privacy_error_help", "qm");
				print "</td>";
				if ($extrafams) {
					print "<td>".$gm_lang["total_fams"]." ".count($fam_total);
					if (count($fam_private)>0) print "  (".$gm_lang["private"]." ".count($fam_private).")";
					if (count($fam_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($fam_hide);
					if (count($fam_private)>0 || count($fam_hide)>0) print_help_link("privacy_error_help", "qm");
					print "</td>";
				}
				print "</tr>";
			}
			print "</table></div>";
		}
		else if (!isset($topsearch)) print "<br /><br /><div class=\"warning width100\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i></div>\n\t\t";
	}
	else if (!isset($topsearch)) print "<br /><br /><div class=\"warning width100\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i><br /></div>\n\t\t";
}
print "<br />";
if ($action == "general") {
	if(isset($sindilist) && isset($srindi) && count($sindilist) > 0) $tab = 1;
	else if(isset($sfamlist) && count($sfamlist) > 0) $tab = 2;
	else if(isset($ssourcelist) && count($ssourcelist) > 0) $tab = 3;
	else if(isset($srepolist) && count($srepolist) > 0) $tab = 4;
	else if(isset($media_total) && count($media_total) > 0) $tab = 5;
	else if(isset($note_total) && count($note_total) > 0) $tab = 6;
	else {
		print "<br /><div class=\"warning\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i><br /></div>";
		print "<div id=\"no_tab0\"></div>";
		$tab = "0";
	}
	if ($tab != "0") {
		print "<script type=\"text/javascript\">\n<!--\n";
		print "tabswitch($tab)";
		print "\n//-->\n</script>\n";
	}
}

print_footer();
?>
