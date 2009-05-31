<?php
/**
 * PopUp Window to provide users with a simple quick update form.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * This Page Is Valid XHTML 1.0 Transitional! > 19 August 2005
 *
 * @package Genmod
 * @subpackage Edit
 * @version $Id: edit_quickupdate.php,v 1.11 2006/02/19 18:40:23 roland-d Exp $
 */

 /**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the editing functions
*/
require("includes/functions_edit.php");

/**
 * Inclusion of the language files
*/
require($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY . $factsfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $factsfile[$LANGUAGE];

if ($_SESSION["cookie_login"]) {
	header("Location: login.php?type=simple&url=edit_interface.php");
	exit;
}

//-- @TODO make list a configurable list
$addfacts = preg_split("/[,; ]/", $QUICK_ADD_FACTS);
usort($addfacts, "factsort");

$reqdfacts = preg_split("/[,; ]/", $QUICK_REQUIRED_FACTS);

//-- @TODO make list a configurable list
$famaddfacts = preg_split("/[,; ]/", $QUICK_ADD_FAMFACTS);
usort($famaddfacts, "factsort");
$famreqdfacts = preg_split("/[,; ]/", $QUICK_REQUIRED_FAMFACTS);

// NOTE: These tags do not require any extra information. See page 32 of GEDCOM standard
$famemptyfacts = array("ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARR","MARL","MARS");

$align="right";
if ($TEXT_DIRECTION=="rtl") $align="left";

print_simple_header($gm_lang["quick_update_title"]);

//-- only allow logged in users to access this page
$uname = $gm_username;
if ((!$ALLOW_EDIT_GEDCOM)||(!$USE_QUICK_UPDATE)||(empty($uname))) {
	print $gm_lang["access_denied"];
	print_simple_footer();
	exit;
}

$user = getUser($uname);

if (!isset($action)) $action="";
if (!isset($closewin)) $closewin=0;
if (empty($pid)) {
	if (!empty($user["gedcomid"][$GEDCOM])) $pid = $user["gedcomid"][$GEDCOM];
	else $pid = "";
}
$pid = clean_input($pid);

//-- only allow editors or users who are editing their own individual or their immediate relatives
$pass = false;
if (!empty($user["gedcomid"][$GEDCOM])) {
	if ($pid==$user["gedcomid"][$GEDCOM]) $pass=true;
	else {
		$famids = gm_array_merge(find_sfamily_ids($user["gedcomid"][$GEDCOM]), find_family_ids($user["gedcomid"][$GEDCOM]));
		foreach($famids as $indexval => $famid) {
			if (!isset($gm_changes[$famid."_".$GEDCOM])) $famrec = find_family_record($famid);
			if (preg_match("/1 HUSB @$pid@/", $famrec)>0) $pass=true;
			if (preg_match("/1 WIFE @$pid@/", $famrec)>0) $pass=true;
			if (preg_match("/1 CHIL @$pid@/", $famrec)>0) $pass=true;
		}
	}
}
if (empty($pid)) $pass=false;
if ((!userCanEdit($uname))&&(!$pass)) {
	print $gm_lang["access_denied"];
	print_simple_footer();
	exit;
}

//-- find the latest gedrec for the individual
$gedrec = find_gedcom_record($pid);

//-- only allow edit of individual records
$disp = true;
$ct = preg_match("/0 @$pid@ (.*)/", $gedrec, $match);
if ($ct>0) {
	$type = trim($match[1]);
	if ($type=="INDI") {
		$disp = displayDetailsById($pid);
	}
	else {
		print $gm_lang["access_denied"];
		print_simple_footer();
		exit;
	}
}

if ((!$disp)||(!$ALLOW_EDIT_GEDCOM)) {

	print $gm_lang["access_denied"];
	//-- display messages as to why the editing access was denied
	if (!userCanEdit($gm_username)) print "<br />".$gm_lang["user_cannot_edit"];
	if (!$ALLOW_EDIT_GEDCOM) print "<br />".$gm_lang["gedcom_editing_disabled"];
	if (!$disp) {
		print "<br />".$gm_lang["privacy_prevented_editing"];
		if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
	}
	print_simple_footer();
	exit;
}

//-- put the updates into the gedcom record
if ($action=="update") {
	print "<h2>".$gm_lang["quick_update_title"]."</h2>\n";
	print "<b>".PrintReady(get_person_name($pid))."</b><br /><br />";
	
	WriteToLog("Quick update attempted for $pid by >".$gm_username."<", "I", "G", $GEDCOM);
	$change_id = get_new_xref("CHANGE");
	$updated = false;
	$error = "";
	$oldgedrec = $gedrec;
	//-- check for name update
	if (!empty($GIVN) || !empty($SURN)) {
		$oldrec = get_sub_record(1, "1 NAME", $gedrec);
		$newrec = $oldrec;
		if (!empty($newrec)) {
			if (!empty($GIVN)) {
				$newrec = preg_replace("/1 NAME.+\/(.*)\//", "1 NAME $GIVN /$1/", $newrec);
				$newrec = preg_replace("/2 GIVN.+\n/", "2 GIVN $GIVN\r\n", $newrec);
			}
			if (!empty($SURN)) {
				$newrec = preg_replace("/1 NAME(.+)\/.*\//", "1 NAME$1/$SURN/", $newrec);
				$newrec = preg_replace("/2 SURN.+\n/", "2 SURN $SURN\r\n", $newrec);
			}
		}
		else $newrec = "\r\n1 NAME $GIVN /$SURN/\r\n2 GIVN $GIVN\r\n2 SURN $SURN";
	}
	//-- rtl name update
	if ($USE_RTL_FUNCTIONS) {
		if (!empty($HSURN) || !empty($HGIVN)) {
			if (preg_match("/2 _HEB/", $newrec)>0) {
				if (!empty($HGIVN)) {
					$newrec = preg_replace("/2 _HEB.+\/(.*)\//", "2 _HEB $HGIVN /$1/", $newrec);
				}
				if (!empty($HSURN)) {
					$newrec = preg_replace("/2 _HEB(.+)\/.*\//", "2 _HEB$1/$HSURN/", $newrec);
				}
			}
			else $newrec .= "1 NAME $HGIVN /$HSURN/\r\n2 _HEB $HGIVN /$HSURN/\r\n";
		}
	}
	if (replace_gedrec($pid, $oldrec, $newrec, $fact, $change_id, $change_type)) $updated = true;
	
	// NOTE: check for updated facts
	$count_tags = count($TAGS);
	$facts_searched = array();
	if ($count_tags>0) {
		$repeat_tags = array();
		for($i=0; $i<$count_tags; $i++) {
			if (!empty($TAGS[$i])) {
				// NOTE: Set the fact to work with (BIRT, DEAT...)
				$fact = $TAGS[$i];
				// NOTE: Check if the fact can be changed or not, if not notify the user
				if (FactEditRestricted($pid, $fact)) {
					WriteToLog("Attempt to update fact $fact for record $pid", "W", "G", $GEDCOM);
					print "<br />".$gm_lang["update_fact_restricted"]." ".$factarray[$fact]."<br /><br />";
				}
				else {
					// NOTE: Keep track of the facts already retrieved
					// NOTE: When retrieving the fact from the gedcom record
					// NOTE: we need to know which one. First, second, etc.
					if (isset($facts_searched[$fact])) $facts_searched[$fact]["number"]++;
					else $facts_searched[$fact]["number"] = 1;
					$oldrec = get_sub_record(1, "1 $fact", $gedrec, $facts_searched[$fact]["number"]);
					// NOTE: Remove all data for this particular fact
					if (isset($REMS[$i]) && ($REMS[$i]==1)) {
						$DATES[$i]="";
						$PLACS[$i]="";
						$TEMPS[$i]="";
						$RESNS[$i]="";
						$DESCS[$i]="";
					}
					if (!empty($DATES[$i])) {
						$DATES[$i] = check_input_date($DATES[$i]);
					}
					
					if ((empty($DATES[$i]))&&(empty($PLACS[$i]))&&(empty($TEMPS[$i]))&&(empty($RESNS[$i]))&&(empty($DESCS[$i]))) {
						// NOTE: If all data is empty because it is deleted, the new record will need to be empty too
						// NOTE: replace the old data
						$newrec="";
					}
					else {
						// NOTE: The entered data needs to be put into the new record
						if (!in_array($fact, $typefacts)) $newrec = "1 $fact";
						else $newrec = "1 EVEN\r\n2 TYPE $fact";
						if (!empty($DESCS[$i])) $newrec .= " ".$DESCS[$i];
						$newrec .= "\r\n";
						if (!empty($DATES[$i])) $newrec .= "2 DATE $DATES[$i]\r\n";
						if (!empty($PLACS[$i])) $newrec .= "2 PLAC $PLACS[$i]\r\n";
						if (!empty($TEMPS[$i])) $newrec .= "2 TEMP $TEMPS[$i]\r\n";
						if (!empty($RESNS[$i])) $newrec .= "2 RESN $RESNS[$i]\r\n";
					}
					if (trim($oldrec) != trim($newrec)) replace_gedrec($pid, $oldrec, $fact, $newrec, $change_id, $change_type);
				}
			}
		}
	}
	
	//-- check for new fact
	if (!empty($newfact)) {
		if (!in_array($newfact, $typefacts)) $newrec = "1 $newfact\r\n";
		else $newrec = "1 EVEN\r\n2 TYPE $newfact\r\n";
		if (!empty($DATE)) {
			$DATE = check_input_date($DATE);
			$newrec .= "2 DATE $DATE\r\n";
		}
		if (!empty($PLAC)) $newrec .= "2 PLAC $PLAC\r\n";
		if (!empty($TEMP)) $newrec .= "2 TEMP $TEMP\r\n";
		if (!empty($RESN)) $newrec .= "2 RESN $RESN\r\n";
		replace_gedrec($pid, "", $newrec, $fact, $change_id, $change_type);
	}

	//-- check for photo update
	if (!empty($_FILES["FILE"]['tmp_name'])) {
		$upload_errors = array($gm_lang["file_success"], $gm_lang["file_too_big"], $gm_lang["file_too_big"],$gm_lang["file_partial"], $gm_lang["file_missing"]);
		if (!move_uploaded_file($_FILES['FILE']['tmp_name'], $MEDIA_DIRECTORY.basename($_FILES['FILE']['name']))) {
			$error .= "<br />".$gm_lang["upload_error"]."<br />".$upload_errors[$_FILES['FILE']['error']];
		}
		else {
			$path_parts = pathinfo($_FILES['FILE']['name']);
			$extension = $path_parts["extension"];
			$filename = $MEDIA_DIRECTORY.$path_parts["basename"];
			$thumbnail = $MEDIA_DIRECTORY."thumbs/".$path_parts["basename"];
			generate_thumbnail($filename, $thumbnail);
			
			// NOTE: A new media object needs 2 parts
			// NOTE: 1. A new media record
			// NOTE: 2. Reference to the new media record
			$newrec = "0 @new@ OBJE\r\n";
			$newrec .= "1 FILE ".$filename."\r\n";
			$newrec .= "2 FORM ".$extension."\r\n";
			if (!empty($TITL)) $newrec .= "2 TITL $TITL\r\n";
			
			$media_id = append_gedrec($newrec, "OBJE", $change_id, $change_type);
			$change["OBJE"] = true;
			
			$newrec = "1 OBJE @".$media_id."@";
			replace_gedrec($pid, "", $newrec, "OBJE", $change_id, $change_type, $media_id);
		}
	}
	
	// NOTE: Update address
	$oldrec = get_sub_record(1, "1 ADDR", $gedrec);
	$newrec = "";
	if (!empty($ADDR)) {
		if (!empty($ADR1)||!empty($CITY)||!empty($POST)) {
			$newrec = "1 ADDR\r\n";
			if (!empty($_NAME)) $newrec.="2 _NAME ".$_NAME."\r\n";
			if (!empty($ADR1)) $newrec.="2 ADR1 ".$ADR1."\r\n";
			if (!empty($ADR2)) $newrec.="2 ADR2 ".$ADR2."\r\n";
			if (!empty($CITY)) $newrec.="2 CITY ".$CITY."\r\n";
			if (!empty($STAE)) $newrec.="2 STAE ".$STAE."\r\n";
			if (!empty($POST)) $newrec.="2 POST ".$POST."\r\n";
			if (!empty($CTRY)) $newrec.="2 CTRY ".$CTRY."\r\n";
		}
		else {
			$newrec = "1 ADDR ";
			$lines = preg_split("/\r*\n/", $ADDR);
			$newrec .= $lines[0]."\r\n";
			for($i=1; $i<count($lines); $i++) $newrec .= "2 CONT ".$lines[$i]."\r\n";
		}
	}
	if (trim($oldrec) != trim($newrec)) replace_gedrec($pid, $oldrec, $newrec, $fact, $change_id, $change_type);
	
	// NOTE: Update phone
	$oldrec = get_sub_record(1, "1 PHON", $gedrec);
	$newrec = "";
	if (!empty($PHON)) $newrec = "1 PHON $PHON\r\n";
	if (trim($oldrec) != trim($newrec)) replace_gedrec($pid, $oldrec, $newrec, "PHON", $change_id, $change_type);
	
	// NOTE: Update fax
	$oldrec = get_sub_record(1, "1 FAX", $gedrec);
	$newrec = "";
	if (!empty($FAX)) $newrec = "1 FAX $FAX\r\n";
	if (trim($oldrec) != trim($newrec)) replace_gedrec($pid, $oldrec, $newrec, "FAX", $change_id, $change_type);
	
	// NOTE: Update E-mail
	$oldrec = get_sub_record(1, "1 EMAIL", $gedrec);
	$newrec = "";
	if (!empty($EMAIL)) $newrec = "1 EMAIL $EMAIL\r\n";
	if (trim($oldrec) != trim($newrec)) replace_gedrec($pid, $oldrec, $newrec, "EMAIL", $change_id, $change_type);
	
	// NOTE: Spouse family tabs
	$sfams = find_families_in_record($gedrec, "FAMS");
	$count_sfams = count($sfams);
	for($i=1; $i<=$count_sfams; $i++) {
		$famupdate = false;
		$famid = $sfams[$i-1];
		$famrec = find_family_record($famid);
		$oldfamrec = $famrec;
		$parents = find_parents($famid);
		//-- update the spouse
		$tag = "HUSB";
		if($parents) {
			if($pid != $parents["HUSB"]) $tag = "HUSB";
			else $tag = "WIFE";
		}
		// NOTE: Retrieve the HUSB/WIFE record
		$oldrec = get_sub_record(1, "1 $tag", $famrec);
		
		$sgivn = "SGIVN$i";
		$ssurn = "SSURN$i";
		// NOTE: Add new spouse name, birth
		if (!empty($$sgivn) || !empty($$ssurn)) {
			//-- first add the new spouse
			$spouserec = "0 @REF@ INDI\r\n";
			$spouserec .= "1 NAME ".$$sgivn." /".$$ssurn."/\r\n";
			$spouserec .= "2 GIVN ".$$sgivn."\r\n";
			$spouserec .= "2 SURN ".$$ssurn."\r\n";
			$hsgivn = "HSGIVN$i";
			$hssurn = "HSSURN$i";
			if (!empty($$hsgivn) || !empty($$hssurn)) {
				$spouserec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
			}
			$ssex = "SSEX$i";
			if (!empty($$ssex)) $spouserec .= "1 SEX ".$$ssex."\r\n";
			$bdate = "BDATE$i";
			$bplac = "BPLAC$i";
			if (!empty($bdate)||!empty($bplac)) {
				$spouserec .= "1 BIRT\r\n";
				if (!empty($$bdate)) {
					$bdate = $$bdate;
					$bdate = check_input_date($bdate);
					$spouserec .= "2 DATE $bdate\r\n";
				}
				if (!empty($$bplac)) $spouserec .= "2 PLAC ".$$bplac."\r\n";
				$bresn = "BRESN$i";
				if (!empty($$bresn)) $spouserec .= "2 RESN ".$$bresn."\r\n";
			}
			$spouserec .= "1 FAMS @$famid@\r\n";
			$SPID[$i] = append_gedrec($spouserec, "INDI", $change_id, $change_type);
			$change["INDI"] = true;
		}
		
		if (!empty($SPID[$i])) {
			// NOTE: We have the new ID for the spouse, lets add it to the person record
			$newrec = "1 $tag @$SPID[$i]@\r\n";
			// NOTE: Check if the old and new HUSB/WIFE record is the same or different
			// NOTE: if different, store in the changes DB
			if (trim($oldrec) != trim($newrec)) {
				if (replace_gedrec($famid, "", $newfamrec, $fact, $change_id, $change_type)) $famupdate = true;
			}
		}
		
		// NOTE: Check for updated facts
		$var = "F".$i."TAGS";
		if (!empty($$var)) $TAGS = $$var;
		else $TAGS = array();
		$count_tags = count($TAGS);
		if ($count_tags > 0) {
			$repeat_tags = array();
			for($j=0; $j < $count_tags; $j++) {
				if (!empty($TAGS[$j])) {
					$fact = $TAGS[$j];
					// NOTE: Check if the fact can be changed or not, if not notify the user
					if (FactEditRestricted($pid, $fact)) {
						WriteToLog("Attempt to update fact $fact for record $pid", "W", "G", $GEDCOM);
						print "<br />".$gm_lang["update_fact_restricted"]." ".$factarray[$fact]."<br /><br />";
					}
					else {
						// NOTE: Keep track of the facts already retrieved
						// NOTE: When retrieving the fact from the gedcom record
						// NOTE: we need to know which one. First, second, etc.
						if (isset($facts_searched[$fact])) $facts_searched[$fact]["number"]++;
						else $facts_searched[$fact]["number"] = 1;
						$oldrec = get_sub_record(1, "1 $fact", $oldfamrec, $facts_searched[$fact]["number"]);
						
						if (!isset($repeat_tags[$fact])) $repeat_tags[$fact] = 1;
						else $repeat_tags[$fact]++;
						$var = "F".$i."DATES";
						if (!empty($$var)) $DATES = $$var;
						else $DATES = array();
						$var = "F".$i."PLACS";
						if (!empty($$var)) $PLACS = $$var;
						else $PLACS = array();
						$var = "F".$i."TEMPS";
						if (!empty($$var)) $TEMPS = $$var;
						else $TEMPS = array();
						$var = "F".$i."RESNS";
						if (!empty($$var)) $RESNS = $$var;
						else $RESNS = array();
						$var = "F".$i."REMS";
						if (!empty($$var)) $REMS = $$var;
						else $REMS = array();
						
						$DATES[$j] = check_input_date($DATES[$j]);
						if ($REMS[$j]==1) {
							$DATES[$j]="";
							$PLACS[$j]="";
							$TEMPS[$j]="";
							$RESNS[$j]="";
						}
						if ((empty($DATES[$j]))&&(empty($PLACS[$j]))&&(empty($TEMPS[$j]))&&(empty($RESNS[$j]))) {
							// NOTE: Certain tags can be empty, check if we are dealing with those tags
							if (in_array($fact, $famemptyfacts)) $newrec = "1 ".$fact;
							else {
								// NOTE: If all data is empty because it is deleted, the new record will need to be empty to
								// NOTE: replace the old data
								$newrec="";
							}
						}
						else {
							if (!in_array($fact, $typefacts)) $newrec = "1 $fact\r\n";
							else $newrec = "1 EVEN\r\n2 TYPE $fact\r\n";
							if (!empty($DATES[$j])) $newrec .= "2 DATE $DATES[$j]\r\n";
							if (!empty($PLACS[$j])) $newrec .= "2 PLAC $PLACS[$j]\r\n";
							if (!empty($TEMPS[$j])) $newrec .= "2 TEMP $TEMPS[$j]\r\n";
							if (!empty($RESNS[$j])) $newrec .= "2 RESN $RESNS[$j]\r\n";
						}
					}
					if (trim($oldrec) != trim($newrec)) replace_gedrec($famid, $oldrec, $newrec, $fact, $change_id, $change_type);
				}
			}
		}
		
		//-- check for new fact
		$var = "F".$i."newfact";
		if (!empty($$var)) $newfact = $$var;
		else $newfact = "";
		if (!empty($newfact)) {
			if (!in_array($newfact, $typefacts)) $newrec = "1 $newfact\r\n";
			else $newrec = "1 EVEN\r\n2 TYPE $newfact\r\n";
			$var = "F".$i."DATE";
			if (!empty($$var)) $FDATE = $$var;
			else $FDATE = "";
			if (!empty($FDATE)) {
				$FDATE = check_input_date($FDATE);
				$newrec .= "2 DATE $FDATE\r\n";
			}
			$var = "F".$i."PLAC";
			if (!empty($$var)) $FPLAC = $$var;
			else $FPLAC = "";
			if (!empty($FPLAC)) $newrec .= "2 PLAC $FPLAC\r\n";
			$var = "F".$i."TEMP";
			if (!empty($$var)) $FTEMP = $$var;
			else $FTEMP = "";
			if (!empty($FTEMP)) $newrec .= "2 TEMP $FTEMP\r\n";
			$var = "F".$i."RESN";
			if (!empty($$var)) $FRESN = $$var;
			else $FRESN = "";
			if (!empty($FRESN)) $newrec .= "2 RESN $FRESN\r\n";
			if (replace_gedrec($famid, "", $newrec, $fact, $change_id, $change_type)) $famupdate = true;
		}
		
		// NOTE: Add an existing child to the family
		if (!empty($CHIL[$i])) {
			$oldrec = find_gedcom_record($CHIL[$i]);
			$newrec = "1 CHIL @".$CHIL[$i]."@\r\n";
			if (trim($oldrec) != trim($newrec)) {
				if (replace_gedrec($famid, "", $newrec, $fact, $change_id, $change_type)) $famupdate = true;
			}
		}
		
		// NOTE: Delete child
		$var = "F".$i."CDEL";
		if (!empty($$var)) $fcdel = $$var;
		else $fcdel = "";
		if (!empty($fcdel)) {
			$oldrec = find_gedcom_record($CHIL[$i]);
			if (replace_gedrec($famid, $oldrec, "", $fact, $change_id, $change_type)) $famupdate = true;
		}
		
		// NOTE: Add new child, name, birth
		$cgivn = "";
		$var = "C".$i."GIVN";
		if (!empty($$var)) $cgivn = $$var;
		else $cgivn = "";
		$csurn = "";
		$var = "C".$i."SURN";
		if (!empty($$var)) $csurn = $$var;
		else $csurn = "";
		if (!empty($cgivn) || !empty($csurn)) {
			//-- first add the new child
			$childrec = "0 @REF@ INDI\r\n";
			$childrec .= "1 NAME $cgivn /$csurn/\r\n";
			$hsgivn = "HC{$i}GIVN";
			$hssurn = "HC{$i}SURN";
			if (!empty($$hsgivn) || !empty($$hssurn)) {
				$childrec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
			}
			$var = "C".$i."SEX";
			$csex = "";
			if (!empty($$var)) $csex = $$var;
			if (!empty($csex)) $childrec .= "1 SEX $csex\r\n";
			$var = "C".$i."DATE";
			$cdate = "";
			if (!empty($$var)) $cdate = $$var;
			$var = "C".$i."PLAC";
			$cplac = "";
			if (!empty($$var)) $cplac = $$var;
			if (!empty($cdate)||!empty($cplac)) {
				$childrec .= "1 BIRT\r\n";
				$cdate = check_input_date($cdate);
				if (!empty($cdate)) $childrec .= "2 DATE $cdate\r\n";
				if (!empty($cplac)) $childrec .= "2 PLAC $cplac\r\n";
				$var = "C".$i."RESN";
				$cresn = "";
				if (!empty($$var)) $cresn = $$var;
				if (!empty($cresn)) $childrec .= "2 RESN $cresn\r\n";
			}
			$childrec .= "1 FAMC @$famid@\r\n";
			$cxref = append_gedrec($childrec, "INDI", $change_id, $change_type);
			$changes["INDI"] = true;
			// NOTE: Check if the Child ID does not already exist in the famrecord
			$oldrec = find_gedcom_record($cxref);
			$newrec = "1 CHIL @$cxref@\r\n";
			if (trim($oldrec) != trim($newrec)) {
				if (replace_gedrec($famid, "", $newrec, $fact, $change_id, $change_type)) $famupdate = true;
			}
		}
	}
	
	// NOTE: Add new spouse name, birth, marriage
	if (!empty($SGIVN) || !empty($SSURN)) {
		//-- first add the new spouse
		$spouserec = "0 @REF@ INDI\r\n";
		$spouserec .= "1 NAME $SGIVN /$SSURN/\r\n";
		$spouserec .= "2 GIVN $SGIVN\r\n";
		$spouserec .= "2 SURN $SSURN\r\n";
		if (!empty($SSEX)) $spouserec .= "1 SEX $SSEX\r\n";
		if (!empty($BDATE)||!empty($BPLAC)) {
			$spouserec .= "1 BIRT\r\n";
			if (!empty($BDATE)) $spouserec .= "2 DATE $BDATE\r\n";
			if (!empty($BPLAC)) $spouserec .= "2 PLAC $BPLAC\r\n";
			if (!empty($BRESN)) $spouserec .= "2 RESN $BRESN\r\n";
		}
		$xref = append_gedrec($spouserec, "INDI", $change_id, $change_type);
		$changes["INDI"] = true;

		// NOTE: Next add the new family record
		$famrec = "0 @REF@ FAM\r\n";
		if ($SSEX=="M") $famrec .= "1 HUSB @$xref@\r\n1 WIFE @$pid@\r\n";
		else $famrec .= "1 HUSB @$pid@\r\n1 WIFE @$xref@\r\n";
		$newfamid = append_gedrec($famrec, "FAM", $change_id, $change_type);
		$changes["FAM"] = true;

		// NOTE: Add the new family id to the new spouse record
		$spouserec = "1 FAMS @$newfamid@\r\n";
		replace_gedrec($xref, "", $spouserec, "FAMS", $change_id, $change_type);
		
		//-- last add the new family id to the persons record
		$newrec .= "1 FAMS @$newfamid@\r\n";
		replace_gedrec($pid, "", $spouserec, "FAMS", $change_id, $change_type);
	}
	if (!empty($MDATE)||!empty($MPLAC)) {
		if (empty($newfamid)) {
			$famrec = "0 @REF@ FAM\r\n";
			if (preg_match("/1 SEX M/", $gedrec)>0) $famrec .= "1 HUSB @$pid@\r\n";
			else $famrec .= "1 WIFE @$pid@";
			$newfamid = append_gedrec($famrec, "FAM", $change_id, $change_type);
			$changes["FAM"] = true;
			
			// NOTE: Add the new family ID to the current person
			$newrec = "1 FAMS @$newfamid@\r\n";
			replace_gedrec($pid, "", $newrec, "FAMS", $change_id, $change_type);
		}
		// NOTE: Create the marriage details and add them to the famrecord
		$newrec = "1 MARR\r\n";
		$MDATE = check_input_date($MDATE);
		if (!empty($MDATE)) $newrec .= "2 DATE $MDATE\r\n";
		if (!empty($MPLAC)) $newrec .= "2 PLAC $MPLAC\r\n";
		if (!empty($MRESN)) $newrec .= "2 RESN $MRESN\r\n";
		replace_gedrec($newfamid, "", $newrec, "MARR", $change_id, $change_type);
	}

	// NOTE: Add new child, name, birth
	if (!empty($CGIVN) || !empty($CSURN)) {
		//-- first add the new child
		$childrec = "0 @REF@ INDI\r\n";
		$childrec .= "1 NAME $CGIVN /$CSURN/\r\n";
		if (!empty($HCGIVN) || !empty($HCSURN)) {
			$childrec .= "2 _HEB $HCGIVN /$HCSURN/\r\n";
		}
		if (!empty($CSEX)) $childrec .= "1 SEX $CSEX\r\n";
		if (!empty($CDATE)||!empty($CPLAC)) {
			$childrec .= "1 BIRT\r\n";
			$CDATE = check_input_date($CDATE);
			if (!empty($CDATE)) $childrec .= "2 DATE $CDATE\r\n";
			if (!empty($CPLAC)) $childrec .= "2 PLAC $CPLAC\r\n";
			if (!empty($CRESN)) $childrec .= "2 RESN $CRESN\r\n";
		}
		$cxref = append_gedrec($childrec, "INDI", $change_id, $change_type);
		$changes["INDI"] = true;

		//-- if a new family was already made by adding a spouse or a marriage
		//-- then use that id, otherwise create a new family
		if (empty($newfamid)) {
			$famrec = "0 @REF@ FAM\r\n";
			if (preg_match("/1 SEX M/", $gedrec)>0) $famrec .= "1 HUSB @$pid@\r\n";
			else $famrec .= "1 WIFE @$pid@\r\n";
			$newfamid = append_gedrec($famrec, "FAM", $change_id, $change_type);
			$changes["FAM"] = true;
			
			// NOTE: add the new family to the new child
			$childrec = "1 FAMC @$newfamid@\r\n";
			replace_gedrec($cxref, "", $childrec, "FAMC", $change_id, $change_type);
			
			// NOTE: add the new family to the original person
			$newrec = "1 FAMS @$newfamid@\r\n";
			replace_gedrec($pid, "", $newrec, "FAMS", $change_id, $change_type);
			$updated = true;
		}
		else {
			$famrec = "1 CHIL @$cxref@\r\n";
			
			// NOTE: add the family to the new child
			$childrec = "1 FAMC @$newfamid@\r\n";
			replace_gedrec($cxref, "", $childrec, "FAMC", $change_id, $change_type);
			
			// NOTE: Add the child to the family
			$famrec = "1 CHIL @$cxref@\r\n";
			replace_gedrec($newfamid, "", $famrec, "CHIL", $change_id, $change_type);
		}
		print $gm_lang["update_successful"]."<br />\n";;
	}
	
	//------------------------------------------- updates for family with parents
	$cfams = find_families_in_record($famrec, "FAMC");
	$i++;
	$count_cfams = count($cfams);
	if ($count_cfams == 0) {
		$cfams[] = "";
		$sfgivn = "FGIVN$i";
		$sfsurn = "FSURN$i";
		$smgivn = "MGIVN$i";
		$smsurn = "MSURN$i";
		if (!empty($FATHER[$i]) || !empty($MOTHER[$i]) || !empty($sfgivn) || !empty($sfsurn) || !empty($smgivn) || !empty($smsurn)) $count_cfams = 1;
	}
	
	for($j=1; $j<= $count_cfams; $j++) {
		$famid = $cfams[$j-1];
		$famupdate = false;
		if (!empty($famid)) {
			$famrec = find_family_record($famid);
			$oldfamrec = $famrec;
		}
		else {
			$famrec = "0 @REF@ FAM\r\n1 CHIL @$pid@\r\n";
			$oldfamrec = "";
		}
		if (empty($FATHER[$i])) {
			//-- update the parents
			$sgivn = "FGIVN$i";
			$ssurn = "FSURN$i";
			//--add new spouse name, birth
			if (!empty($$sgivn) || !empty($$ssurn)) {
				//-- first add the new spouse
				$spouserec = "0 @REF@ INDI\r\n";
				$spouserec .= "1 NAME ".$$sgivn." /".$$ssurn."/\r\n";
				$spouserec .= "2 GIVN ".$$sgivn."\r\n";
				$spouserec .= "2 SURN ".$$ssurn."\r\n";
				$hsgivn = "HFGIVN$i";
				$hssurn = "HFSURN$i";
				if (!empty($$hsgivn) || !empty($$hssurn)) {
					$spouserec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
				}
				$ssex = "FSEX$i";
				if (!empty($$ssex)) $spouserec .= "1 SEX ".$$ssex."\r\n";
				$bdate = "FBDATE$i";
				$bplac = "FBPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$spouserec .= "1 BIRT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = check_input_date($bdate);
					if (!empty($bdate)) $spouserec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $spouserec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "FBRESN$i";
					if (!empty($$bresn)) $spouserec .= "2 RESN ".$$bresn."\r\n";
				}
				$bdate = "FDDATE$i";
				$bplac = "FDPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$spouserec .= "1 DEAT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = check_input_date($bdate);
					if (!empty($bdate)) $spouserec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $spouserec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "FDRESN$i";
					if (!empty($$bresn)) $spouserec .= "2 RESN ".$$bresn."\r\n";
				}
				
				if (empty($famid)) {
					// NOTE: Create a new family record
					$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
					$changes["FAM"] = true;
					// NOTE: Update the active person with the new family ID
					$newrec = "1 FAMC @$famid@\r\n";
					replace_gedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
					$changes["INDI"] = true;
				}
				$spouserec .= "1 FAMS @$famid@\r\n";
				// NOTE: Create the new spouse record
				$FATHER[$i] = append_gedrec($spouserec, "FAMS", $change_id, $change_type);
			}
		}
		else {
			if (empty($famid)) {
				// NOTE: Create a new family record
				$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
				$changes["FAM"] = true;
				// NOTE: Update the active person with the new family ID
				$newrec = "1 FAMC @$famid@\r\n";
				replace_gedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
				$changes["INDI"] = true;
			}
			if (empty($oldfamrec)) {
				$newrec = "1 FAMS @$famid@\r\n";
				replace_gedrec($FATHER[$i], "", $newrec, "FAMS", $change_id, $change_type);
				$changes["INDI"] = true;
			}
		}
		
		if (!empty($FATHER[$i])) {
			$oldrec = get_sub_record(1, "1 HUSB", $famrec);
			$newrec = "1 HUSB @$FATHER[$i]@";
			if (trim($oldrec) != trim($newrec)) {
				replace_gedrec($famid, $oldrec, "HUSB", $newrec, $change_id, $change_type);
			}
		}
		
		if (empty($MOTHER[$i])) {
			//-- update the parents
			$sgivn = "MGIVN$i";
			$ssurn = "MSURN$i";
			//--add new spouse name, birth
			if (!empty($$sgivn) || !empty($$ssurn)) {
				//-- first add the new spouse
				$spouserec = "0 @REF@ INDI\r\n";
				$spouserec .= "1 NAME ".$$sgivn." /".$$ssurn."/\r\n";
				$spouserec .= "2 GIVN ".$$sgivn."\r\n";
				$spouserec .= "2 SURN ".$$ssurn."\r\n";
				$hsgivn = "HMGIVN$i";
				$hssurn = "HMSURN$i";
				if (!empty($$hsgivn) || !empty($$hssurn)) {
					$spouserec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
				}
				$ssex = "MSEX$i";
				if (!empty($$ssex)) $spouserec .= "1 SEX ".$$ssex."\r\n";
				$bdate = "MBDATE$i";
				$bplac = "MBPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$spouserec .= "1 BIRT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = check_input_date($bdate);
					if (!empty($bdate)) $spouserec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $spouserec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "MBRESN$i";
					if (!empty($$bresn)) $spouserec .= "2 RESN ".$$bresn."\r\n";
				}
				$bdate = "MDDATE$i";
				$bplac = "MDPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$spouserec .= "1 DEAT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = check_input_date($bdate);
					if (!empty($bdate)) $spouserec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $spouserec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "MDRESN$i";
					if (!empty($$bresn)) $spouserec .= "2 RESN ".$$bresn."\r\n";
				}
				
				if (empty($famid)) {
					// NOTE: Create a new family record
					$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
					$changes["FAM"] = true;
					// NOTE: Update the active person with the new family ID
					$newrec = "1 FAMC @$famid@\r\n";
					replace_gedrec($pid, "", $newrec, "HUSB", $change_id, $change_type);
					$changes["INDI"] = true;
				}
				$spouserec .= "1 FAMS @$famid@\r\n";
				$MOTHER[$i] = append_gedrec($spouserec, "INDI", $change_id, $change_type);
			}
		}
		else {
			if (empty($famid)) {
				// NOTE: Create a new family record
				$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
				$changes["FAM"] = true;
				// NOTE: Update the active person with the new family ID
				$newrec = "1 FAMC @$famid@\r\n";
				replace_gedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
				$changes["INDI"] = true;
			}
			if (empty($oldfamrec)) {
				$newrec = "1 FAMS @$famid@\r\n";
				replace_gedrec($MOTHER[$i], "", $newrec, "FAMS", $change_id, $change_type);
				$changes["INDI"] = true;
			}
		}
		if (!empty($MOTHER[$i])) {
			$oldrec = get_sub_record(1, "1 WIFE", $famrec);
			$newrec = "1 WIFE @$MOTHER[$i]@\r\n";
			if (trim($oldrec) != trim($newrec)) {
				replace_gedrec($famid, $oldrec, "WIFE", $newrec, $change_id, $change_type);
			}
		}
		
		//-- check for updated facts
		$var = "F".$i."TAGS";
		if (!empty($$var)) $TAGS = $$var;
		else $TAGS = array();
		$count_tags = count($TAGS);
		if ($count_tags > 0) {
			$repeat_tags = array();
			for($j=0; $j <= $count_tags; $j++) {
				if (!empty($TAGS[$j])) {
					$fact = $TAGS[$j];
					if (!isset($repeat_tags[$fact])) $repeat_tags[$fact] = 1;
					else $repeat_tags[$fact]++;
					$var = "F".$i."DATES";
					if (!empty($$var)) $DATES = $$var;
					else $DATES = array();
					$var = "F".$i."PLACS";
					if (!empty($$var)) $PLACS = $$var;
					else $PLACS = array();
					$var = "F".$i."TEMPS";
					if (!empty($$var)) $TEMPS = $$var;
					else $TEMPS = array();
					$var = "F".$i."RESNS";
					if (!empty($$var)) $RESNS = $$var;
					else $RESNS = array();
					$var = "F".$i."REMS";
					if (!empty($$var)) $REMS = $$var;
					else $REMS = array();
					
					$DATES[$j] = check_input_date($DATES[$j]);
					if ($REMS[$j]==1) {
						$DATES[$j]="";
						$PLACS[$j]="";
						$TEMPS[$j]="";
						$RESNS[$j]="";
					}
					if ((empty($DATES[$j]))&&(empty($PLACS[$j]))&&(empty($TEMPS[$j]))&&(empty($RESNS[$j]))) {
						$newrec="";
					}
					else {
						if (!in_array($fact, $typefacts)) $newrec = "1 $fact\r\n";
						else $newrec = "1 EVEN\r\n2 TYPE $fact\r\n";
						if (!empty($DATES[$j])) $newrec .= "2 DATE $DATES[$j]\r\n";
						if (!empty($PLACS[$j])) $newrec .= "2 PLAC $PLACS[$j]\r\n";
						if (!empty($TEMPS[$j])) $newrec .= "2 TEMP $TEMPS[$j]\r\n";
						if (!empty($RESNS[$j])) $newrec .= "2 RESN $RESNS[$j]\r\n";
					}
					if (trim($oldrec) != trim($newrec)) replace_gedrec($famid, $oldrec, $newrec, $fact, $change_id, $change_type);
				}
			}
		}
		
		//-- check for new fact
		$var = "F".$i."newfact";
		$newfact = "";
		$newfact = $$var;
		if (!empty($newfact)) {
			if (empty($famid)) {
				$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
				$changes["FAM"] = true;
				// NOTE: Update the active person with the new family ID
				$newrec = "1 FAMC @$famid@\r\n";
				replace_gedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
				$changes["INDI"] = true;
			}
			if (!in_array($newfact, $typefacts)) $newrec = "1 $newfact\r\n";
			else $newrec = "1 EVEN\r\n2 TYPE $newfact\r\n";
			$var = "F".$i."DATE";
			if (!empty($$var)) $FDATE = $$var;
			else $FDATE = "";
			$FDATE = check_input_date($FDATE);
			if (!empty($FDATE)) $newrec .= "2 DATE $FDATE\r\n";
			$var = "F".$i."PLAC";
			if (!empty($$var)) $FPLAC = $$var;
			else $FPLAC = "";
			if (!empty($FPLAC)) $newrec .= "2 PLAC $FPLAC\r\n";
			$var = "F".$i."TEMP";
			if (!empty($$var)) $FTEMP = $$var;
			else $FTEMP = "";
			if (!empty($FTEMP)) $newrec .= "2 TEMP $FTEMP\r\n";
			$var = "F".$i."RESN";
			if (!empty($$var)) $FRESN = $$var;
			else $FRESN = "";
			if (!empty($FRESN)) $newrec .= "2 RESN $FRESN\r\n";
			if (replace_gedrec($famid, "", $newrec, $newfact, $change_id, $change_type)) $famupdate = true;
		}
		// NOTE: Add an existing child to the family
		if (!empty($CHIL[$i])) {
			if (empty($famid)) {
				$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
				$changes["FAM"] = true;
				// NOTE: Update the active person with the new family ID
				$newrec = "1 FAMC @$famid@\r\n";
				replace_gedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
				$changes["INDI"] = true;
			}
			$oldrec = find_gedcom_record($CHIL[$i]);
			$newrec = "1 CHIL @".$CHIL[$i]."@\r\n";
			if (trim($oldrec) != trim($newrec)) {
				if (replace_gedrec($famid, "", $newrec, "CHIL", $change_id, $change_type)) $famupdate = true;
			}
		}
		// NOTE: Delete child
		$var = "F".$i."CDEL";
		if (!empty($$var)) $fcdel = $$var;
		else $fcdel = "";
		if (!empty($fcdel)) {
			$oldrec = find_gedcom_record($CHIL[$i]);
			if (replace_gedrec($famid, $oldrec, "", "CHIL", $change_id, $change_type)) $famupdate = true;
		}
		
		//--add new child, name, birth
		$cgivn = "C".$i."GIVN";
		$csurn = "C".$i."SURN";
		if (!empty($$cgivn) || !empty($$csurn)) {
			if (empty($famid)) {
				$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
				$changes["FAM"] = true;
				// NOTE: Update the active person with the new family ID
				$newrec = "1 FAMC @$famid@\r\n";
				replace_gedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
				$changes["INDI"] = true;
			}
			//-- first add the new child
			$childrec = "0 @REF@ INDI\r\n";
			$childrec .= "1 NAME ".$$cgivn." /".$$csurn."/\r\n";
			$hsgivn = "HC{$i}GIVN";
			$hssurn = "HC{$i}SURN";
			if (!empty($$hsgivn) || !empty($$hssurn)) {
				$childrec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
			}
			$var = "C".$i."SEX";
			if (!empty($$var)) $csex = $$var;
			else $csex = "";
			if (!empty($csex)) $childrec .= "1 SEX $csex\r\n";
			$var = "C".$i."DATE";
			if (!empty($$var)) $cdate = $$var;
			else $cdate = "";
			$var = "C".$i."PLAC";
			if (!empty($$var)) $cplac = $$var;
			else $cplac = "";
			if (!empty($cdate)||!empty($cplac)) {
				$childrec .= "1 BIRT\r\n";
				$cdate = check_input_date($cdate);
				if (!empty($cdate)) $childrec .= "2 DATE $cdate\r\n";
				if (!empty($cplac)) $childrec .= "2 PLAC $cplac\r\n";
				$var = "C".$i."RESN";
				if (!empty($$var)) $cresn = $$var;
				else $cresn = "";
				if (!empty($cresn)) $childrec .= "2 RESN $cresn\r\n";
			}
			$childrec .= "1 FAMC @$famid@\r\n";
			$cxref = append_gedrec($childrec, "FAMC", $change_id, $change_type);
			$changes["INDI"] = true;
			// NOTE: Check if the Child ID does not already exist in the famrecord
			$oldrec = find_gedcom_record($cxref);
			$newrec = "1 CHIL @$cxref@\r\n";
			if (trim($oldrec) != trim($newrec)) {
				if (replace_gedrec($famid, "", $newrec, "CHIL", $change_id, $change_type)) $famupdate = true;
			}
		}
		$i++;
	}

	if ($updated && empty($error)) {
		print $gm_lang["update_successful"];
		WriteToLog("Quick update for $pid by >".$gm_username."<", "I", "G", $GEDCOM);
	}
	if (!empty($error)) {
		print "<span class=\"error\">".$error."</span>";
	}

	if ($closewin) {
		print "<center><br /><br /><br />";
		print "<a href=\"#\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".$gm_lang["close_window"]."</a><br /></center>\n";
		print_simple_footer();
		exit;
	}
}

if ($action!="update") print "<h2>".$gm_lang["quick_update_title"]."</h2>\n";
print $gm_lang["quick_update_instructions"]."<br /><br />";

init_calendar_popup();
?>
<script language="JavaScript" type="text/javascript">
<!--
var pastefield;
function paste_id(value) {
	pastefield.value = value;
}

var helpWin;
function helpPopup(which) {
	if ((!helpWin)||(helpWin.closed)) helpWin = window.open('help_text.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
	else helpWin.location = 'help_text.php?help='+which;
	return false;
}
//-->
</script>
<?php
if ($action=="choosepid") {
	?>
	<form method="post" action="edit_quickupdate.php?pid=<?php print $pid;?>" name="quickupdate" enctype="multipart/form-data">
	<input type="hidden" name="action" value="" />
	<table>
	<tr>
		<td><?php print $gm_lang["enter_pid"]; ?></td>
		<td><input type="text" size="6" name="pid" id="pid" />
		<?php print_findindi_link("pid","");?>
                </td>
	</tr>
	</table>
	<input type="submit" value="<?php print $gm_lang["continue"]; ?>" />
	</form>
		<?
	}
	else {
	$GIVN = "";
	$SURN = "";
	$subrec = get_sub_record(1, "1 NAME", $gedrec);
	if (!empty($subrec)) {
		$ct = preg_match("/2 GIVN (.*)/", $subrec, $match);
		if ($ct>0) $GIVN = trim($match[1]);
		else {
			$ct = preg_match("/1 NAME (.*)/", $subrec, $match);
			if ($ct>0) {
				$GIVN = preg_replace("~/.*/~", "", trim($match[1]));
			}
		}
		$ct = preg_match("/2 SURN (.*)/", $subrec, $match);
		if ($ct>0) $SURN = trim($match[1]);
		else {
			$ct = preg_match("/1 NAME (.*)/", $subrec, $match);
			if ($ct>0) {
				$st = preg_match("~/(.*)/~", $match[1], $smatch);
				if ($st>0) $SURN = $smatch[1];
			}
		}
		$HGIVN = "";
		$HSURN = "";
		if ($USE_RTL_FUNCTIONS) {
			$hname = get_gedcom_value("_HEB", 2, $subrec, '', false);
			if (!empty($hname)) {
				$ct = preg_match("~(.*)/(.*)/(.*)~", $hname, $matches);
				if ($ct>0) {
					$HSURN = $matches[2];
					$HGIVN = trim($matches[1]).trim($matches[3]);
				}
				else $HGIVN = $hname;
			}
		}
	}
	$ADDR = "";
	$subrec = get_sub_record(1, "1 ADDR", $gedrec);
	if (!empty($subrec)) {
		$ct = preg_match("/1 ADDR (.*)/", $subrec, $match);
		if ($ct>0) $ADDR = trim($match[1]);
		$ADDR_CONT = get_cont(2, $subrec);
		if (!empty($ADDR_CONT)) $ADDR .= $ADDR_CONT;
		else {
			$_NAME = get_gedcom_value("_NAME", 2, $subrec);
			if (!empty($_NAME)) $ADDR .= "\r\n". check_NN($_NAME);
			$ADR1 = get_gedcom_value("ADR1", 2, $subrec);
			if (!empty($ADR1)) $ADDR .= "\r\n". $ADR1;
			$ADR2 = get_gedcom_value("ADR2", 2, $subrec);
			if (!empty($ADR2)) $ADDR .= "\r\n". $ADR2;
			$cityspace = "\r\n";
			if (!$POSTAL_CODE) {
				$POST = get_gedcom_value("POST", 2, $subrec);
				if (!empty($POST)) $ADDR .= "\r\n". $POST;
				else $ADDR .= "\r\n";
				$cityspace = " ";
			}
			$CITY = get_gedcom_value("CITY", 2, $subrec);
			if (!empty($CITY)) $ADDR .= $cityspace. $CITY;
			else $ADDR .= $cityspace;
			$STAE = get_gedcom_value("STAE", 2, $subrec);
			if (!empty($STAE)) $ADDR .= ", ". $STAE;
			if ($POSTAL_CODE) {
				$POST = get_gedcom_value("POST", 2, $subrec);
				if (!empty($POST)) $ADDR .= "  ". $POST;
			}
			$CTRY = get_gedcom_value("CTRY", 2, $subrec);
			if (!empty($CTRY)) $ADDR .= "\r\n". $CTRY;
		}
		/**
		 * @todo add support for ADDR subtags ADR1, CITY, STAE etc
		 */
	}
	$PHON = "";
	$subrec = get_sub_record(1, "1 PHON", $gedrec);
	if (!empty($subrec)) {
		$ct = preg_match("/1 PHON (.*)/", $subrec, $match);
		if ($ct>0) $PHON = trim($match[1]);
		$PHON .= get_cont(2, $subrec);
	}
	$EMAIL = "";
	$ct = preg_match("/1 (_?EMAIL) (.*)/", $gedrec, $match);
	if ($ct>0) {
		$EMAIL = trim($match[2]);
		$subrec = get_sub_record(1, "1 ".$match[1], $gedrec);
		$EMAIL .= get_cont(2, $subrec);
	}
	$FAX = "";
	$subrec = get_sub_record(1, "1 FAX", $gedrec);
	if (!empty($subrec)) {
			$ct = preg_match("/1 FAX (.*)/", $subrec, $match);
			if ($ct>0) $FAX = trim($match[1]);
			$FAX .= get_cont(2, $subrec);
	}
	
	$indifacts = array();
	$subrecords = get_all_subrecords($gedrec, "ADDR,PHON,FAX,EMAIL,_EMAIL,NAME,FAMS,FAMC", false, false, false);
	$repeat_tags = array();
	foreach($subrecords as $ind=>$subrec) {
		$ft = preg_match("/1 (\w+)(.*)/", $subrec, $match);
		if ($ft>0) {
			$fact = trim($match[1]);
			$event = trim($match[2]);
		}
		else {
			$fact="";
			$event="";
		}
		if ($fact=="EVEN" || $fact=="FACT") $fact = get_gedcom_value("TYPE", 2, $subrec, '', false);
		if (in_array($fact, $addfacts)) {
			if (!isset($repeat_tags[$fact])) $repeat_tags[$fact]=1;
			else $repeat_tags[$fact]++;
			$newreqd = array();
			foreach($reqdfacts as $r=>$rfact) {
				if ($rfact!=$fact) $newreqd[] = $rfact;
			}
			$reqdfacts = $newreqd;
			$indifacts[] = array($fact, $subrec, false, $repeat_tags[$fact]);
		}
	}
	foreach($reqdfacts as $ind=>$fact) {
		$indifacts[] = array($fact, "1 $fact\r\n", true, 0);
	}
	// NOTE: Commented out because it intereferes with the updating process of
	// NOTE: multiple facts with the same name. The order cannot be established
	// TODO: Update the update process to handle this correctly.
	// usort($indifacts, "compare_facts");
	
	$sfams = find_families_in_record($gedrec, "FAMS");
	$cfams = find_families_in_record($gedrec, "FAMC");
	if (count($cfams)==0) $cfams[] = "";
		
	$tabkey = 1;
	
	print "<b>".PrintReady(get_person_name($pid));
	if ($SHOW_ID_NUMBERS) print " ".PrintReady("($pid)");
	print "</b><br />";
?>
<script language="JavaScript" type="text/javascript">
<!--
var tab_count = <?php print (count($sfams)+count($cfams)); ?>;
function switch_tab(tab) {
	for(i=0; i<=tab_count+1; i++) {
		var pagetab = document.getElementById('pagetab'+i);
		var pagetabbottom = document.getElementById('pagetab'+i+'bottom');
		var tabdiv = document.getElementById('tab'+i);
		if (i==tab) {
			pagetab.className='tab_cell_active';
			tabdiv.style.display = 'block';
			pagetabbottom.className='tab_active_bottom';
		}
		else {
			pagetab.className='tab_cell_inactive';
			tabdiv.style.display = 'none';
			pagetabbottom.className='tab_inactive_bottom';
		}
	}
}
//-->
</script>
<form method="post" action="edit_quickupdate.php?pid=<?php print $pid;?>" name="quickupdate" enctype="multipart/form-data">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
<input type="hidden" name="closewin" value="1" />
<table class="tabs_table">
   <tr>
		<td id="pagetab0" class="tab_cell_active"><a href="javascript: <?php print $gm_lang["personal_facts"];?>" onclick="switch_tab(0); return false;"><?php print $gm_lang["personal_facts"]?></a></td>
		<?php
		for($i=1; $i<=count($sfams); $i++) {
			$famid = $sfams[$i-1];
			if (!isset($gm_changes[$famid."_".$GEDCOM])) $famrec = find_family_record($famid);
			$parents = find_parents_in_record($famrec);
			$spid = "";
			if($parents) {
				if($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
				else $spid=$parents["WIFE"];
			}			
			print "<td id=\"pagetab$i\" class=\"tab_cell_inactive\" onclick=\"switch_tab($i); return false;\"><a href=\"javascript: ".$gm_lang["family_with"]."&nbsp;";
			if (!empty($spid)) {
				if (displayDetailsById($spid) && showLivingNameById($spid)) {
					print PrintReady(get_person_name($spid));
					print "\" onclick=\"switch_tab($i); return false;\">".$gm_lang["family_with"]." ";
					print PrintReady(get_person_name($spid));
				}
				else {
					print $gm_lang["private"];
					print "\" onclick=\"switch_tab($i); return false;\">".$gm_lang["family_with"]." ";
					print $gm_lang["private"];
				}
			}
			else print $gm_lang["unknown"];
			print "</a></td>\n";
		}
		?>
		<td id="pagetab<?php echo $i; ?>" class="tab_cell_inactive" onclick="switch_tab(<?php echo $i; ?>); return false;"><a href="javascript: <?php print $gm_lang["add_new_wife"];?>" onclick="switch_tab(<?php echo $i; ?>); return false;">
		<?php if (preg_match("/1 SEX M/", $gedrec)>0) print $gm_lang["add_new_wife"]; else print $gm_lang["add_new_husb"]; ?></a></td>
		<?php
		$i++;
		for($j=1; $j<=count($cfams); $j++) {
			print "<td id=\"pagetab$i\" class=\"tab_cell_inactive\" onclick=\"switch_tab($i); return false;\"><a href=\"#\" onclick=\"switch_tab($i); return false;\">".$gm_lang["as_child"];
			print "</a></td>\n";
			$i++;
		}
		?>
		</tr>
		<tr>
		  <td id="pagetab0bottom" class="tab_active_bottom"><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]; ?>" width="1" height="1" alt="" /></td>
		  <?php
		for($i=1; $i<=count($sfams); $i++) {
			print "<td id=\"pagetab{$i}bottom\" class=\"tab_inactive_bottom\"><img src=\"$GM_IMAGE_DIR/".$GM_IMAGES["spacer"]["other"]."\" width=\"1\" height=\"1\" alt=\"\"/></td>\n";
		}
		for($j=1; $j<=count($cfams); $j++) {
			print "<td id=\"pagetab{$i}bottom\" class=\"tab_inactive_bottom\"><img src=\"$GM_IMAGE_DIR/".$GM_IMAGES["spacer"]["other"]."\" width=\"1\" height=\"1\" alt=\"\" /></td>\n";
			$i++;
		}
		?>
			<td id="pagetab<?php echo $i; ?>bottom" class="tab_inactive_bottom"><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]; ?>" width="1" height="1" alt="" /></td>
			<td class="tab_inactive_bottom_right" style="width:10%;"><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]; ?>" width="1" height="1" alt="" /></td>
   </tr>
</table>
<div id="tab0">
<table class="<?php print $TEXT_DIRECTION; ?> width80">
<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_name_help", "qm"); ?><?php print $gm_lang["update_name"]; ?></td></tr>
<tr><td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="GIVN" value="<?php print PrintReady(htmlspecialchars($GIVN)); ?>" /></td></tr>
<?php $tabkey++; ?>
<tr><td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SURN" value="<?php print PrintReady(htmlspecialchars($SURN)); ?>" /></td></tr>
<?php $tabkey++; ?>
<?php if ($USE_RTL_FUNCTIONS) { ?>
<tr><td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HGIVN" value="<?php print PrintReady(htmlspecialchars($HGIVN)); ?>" /></td></tr>
<?php $tabkey++; ?>
<tr><td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSURN" value="<?php print PrintReady(htmlspecialchars($HSURN)); ?>" /></td></tr>
<?php $tabkey++; ?>
</tr>
<?php }

// NOTE: Update fact
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_fact_help", "qm"); print $gm_lang["update_fact"]; ?></td></tr>
<tr>
	<td class="shade2">&nbsp;</td>
	<td class="shade2"><?php print $factarray["DATE"]; ?></td>
	<td class="shade2"><?php print $factarray["PLAC"]; ?></td>
	<td class="shade2"><?php print $gm_lang["delete"]; ?></td>
</tr>
<?php
foreach($indifacts as $f=>$fact) {
	$fact_tag = $fact[0];
	$fact_num = $fact[3];
	$date = get_gedcom_value("DATE", 2, $fact[1], '', false);
	$plac = get_gedcom_value("PLAC", 2, $fact[1], '', false);
	$temp = get_gedcom_value("TEMP", 2, $fact[1], '', false);
	$desc = get_gedcom_value($fact_tag, 1, $fact[1], '', false);
	?>
	<tr>
	<td class="shade2">
		<?php if (isset($factarray[$fact_tag])) print $factarray[$fact_tag]; 
		else if (isset($gm_lang[$fact_tag])) print $gm_lang[$fact_tag]; 
		else print $fact_tag;
		?>		
		<input type="hidden" name="TAGS[]" value="<?php echo $fact_tag; ?>" />
		<input type="hidden" name="NUMS[]" value="<?php echo $fact_num; ?>" />
	</td>
	<?php if (!in_array($fact_tag, $emptyfacts)) { ?>
	<td class="shade1" colspan="2">
		<input type="text" name="DESCS[]" size="40" value="<?php print htmlspecialchars($desc); ?>" />
		<input type="hidden" name="DATES[]" value="<?php print htmlspecialchars($date); ?>" />
		<input type="hidden" name="PLACS[]" value="<?php print htmlspecialchars($plac); ?>" />
		<input type="hidden" name="TEMPS[]" value="<?php print htmlspecialchars($temp); ?>" />
	</td>
	<?php }	else {
		if (!in_array($fact_tag, $nondatefacts)) { ?>
			<td class="shade1">
				<input type="hidden" name="DESCS[]" value="<?php print htmlspecialchars($desc); ?>" />
				<input type="text" tabindex="<?php print $tabkey; $tabkey++;?>" size="15" name="DATES[]" id="DATE<?php echo $f; ?>" onblur="valid_date(this);" value="<?php echo htmlspecialchars($date); ?>" />&nbsp;<?php print_calendar_popup("DATE$f");?>
			</td>
		<?php }
		if (empty($temp) && (!in_array($fact_tag, $nonplacfacts))) { ?>
			<td class="shade1">
				<input type="text" size="30" tabindex="<?php print $tabkey; $tabkey++; ?>" name="PLACS[]" id="place<?php echo $f; ?>" value="<?php print PrintReady(htmlspecialchars($plac)); ?>" />
				<?php print_findplace_link("place$f"); ?>
				<input type="hidden" name="TEMPS[]" value="" />
			</td>
		<?php
		}
		else {
			print "<td class=\"shade2\">".$factarray["PLAC"]."</td>";
			print "<td class=\"shade1\"><select tabindex=\"".$tabkey."\" name=\"TEMPS[]\" >\n";
			print "<option value=''>".$gm_lang["no_temple"]."</option>\n";
			foreach($TEMPLE_CODES as $code=>$temple) {
				print "<option value=\"$code\"";
				if ($code==$temp) print " selected=\"selected\"";
				print ">$temple</option>\n";
			}
			print "</select>\n";
			print "<input type=\"hidden\" name=\"PLACS[]\" value=\"\" />\n";
			print "</td></tr>\n";
			$tabkey++;
		}
	}
	if (!$fact[2]) { ?>
		<td class="shade1 center">
			<input type="hidden" name="REMS[]" id="REM<?php echo $f; ?>" value="0" />
			<a href="javascript: <?php print $gm_lang["delete"]; ?>" onclick="document.quickupdate.closewin.value='0'; document.quickupdate.REM<?php echo $f; ?>.value='1'; document.quickupdate.submit(); return false;">
				<img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" width="15" alt="<?php print $gm_lang["delete"]; ?>" />
			</a>
		</td>
	</tr>
	<?php }
	else {?>
		<td class="shade1">&nbsp;</td>
	</tr>
	<?php }
	if ($SHOW_QUICK_RESN) {
		print_quick_resn("RESNS[]");
	}
}

// NOTE: Add fact
if (count($addfacts)>0) { ?>
	<tr><td>&nbsp;</td></tr>
	<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_fact_help", "qm"); print $gm_lang["add_fact"]; ?></td></tr>
	<tr>
	<td class="shade2">&nbsp;</td>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["DATE"]; ?></td>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"]; ?></td>
	<td class="shade2">&nbsp;</td>
	</tr>
	<tr><td class="shade1">
	<script language="JavaScript" type="text/javascript">
	<!--
	function checkDesc(newfactSelect) {
		if (newfactSelect.selectedIndex==0) return;
		var fact = newfactSelect.options[newfactSelect.selectedIndex].value;
		var emptyfacts = "<?php foreach($emptyfacts as $ind=>$efact) print $efact.","; ?>";
		descfact = document.getElementById('descFact');
		if (!descFact) return;
		if (emptyfacts.indexOf(fact)!=-1) {
			descFact.style.display='none';
		}
		else {
			descFact.style.display='block';
		}
	}
	//-->
	</script>
	<select name="newfact" tabindex="<?php print $tabkey; ?>" onchange="checkDesc(this);">
		<option value=""><?php print $gm_lang["select_fact"]; ?></option>
	<?php $tabkey++; ?>
	<?php
	foreach($addfacts as $indexval => $fact) {
		$found = false;
		foreach($indifacts as $ind=>$value) {
			if ($fact==$value[0]) {
				$found=true;
				break;
			}
		}
		if (!$found) print "\t\t<option value=\"$fact\">".$factarray[$fact]."</option>\n";
	}
	?>
		</select>
		<div id="descFact" style="display:none;"><br />
			<?php print $gm_lang["description"]; ?><input type="text" size="35" name="DESC" />
		</div>
	</td>
	<td class="shade1"<input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="DATE" id="DATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("DATE");?></td>
	<?php $tabkey++; ?>
	<td class="shade1"><input type="text" tabindex="<?php print $tabkey; ?>" name="PLAC" id="place" />
	<?php print_findplace_link("place"); ?>
	</td>
	<td class="shade1">&nbsp;</td></tr>
	<?php $tabkey++; ?>
	<?php print_quick_resn("RESN"); ?>
<?php }

// NOTE: Add photo
if ($MULTI_MEDIA && (is_writable($MEDIA_DIRECTORY))) { ?>
	<tr><td>&nbsp;</td></tr>
	<tr><td class="topbottombar" colspan="4"><b><?php print_help_link("quick_update_photo_help", "qm"); print $gm_lang["update_photo"]; ?></b></td></tr>
	<tr>
	<td class="shade2">
		<?php print $factarray["TITL"]; ?>
	</td>
	<td class="shade1" colspan="3">
		<input type="text" tabindex="<?php print $tabkey; ?>" name="TITL" size="40" />
	</td>
	<?php $tabkey++; ?>
	</tr>
	<tr>
	<td class="shade2">
		<?php print $factarray["FILE"]; ?>
	</td>
	<td class="shade1" colspan="3">
		<input type="file" tabindex="<?php print $tabkey; ?>" name="FILE" size="40" />
	</td>
	<?php $tabkey++; ?>
	</tr>
	<?php if (preg_match("/1 OBJE/", $gedrec)>0) { ?>
		<tr>
	<td class="shade2">&nbsp;</td>
	<td class="shade1" colspan="3">
		<input type="checkbox" tabindex="<?php print $tabkey; ?>" name="replace" value="yes" /> <?php print $gm_lang["photo_replace"]; ?>
	</td>
	<?php $tabkey++; ?>
	</tr>
	<?php } ?>
<?php }

// Address update
if (!is_dead_id($pid) || !empty($ADDR) || !empty($PHON) || !empty($FAX) || !empty($EMAIL)) { //-- don't show address for dead people 
	 ?>
	<tr><td>&nbsp;</td></tr> 
	<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_address_help", "qm"); print $gm_lang["update_address"]; ?></td></tr>
	<tr>
	<td class="shade2">
		<?php print $factarray["ADDR"]; ?>
	</td>
	<td class="shade1" colspan="3">
		<?php if (!empty($CITY)&&!empty($POST)) { ?>
			<?php if (!empty($_NAME)) { ?><?php print $factarray["NAME"]; ?><input type="text" name="_NAME" size="35" value="<?php print PrintReady(strip_tags($_NAME)); ?>" /><br /><?php } ?>
			<?php print $factarray["ADR1"]; ?><input type="text" name="ADR1" size="35" value="<?php print PrintReady(strip_tags($ADR1)); ?>" /><br />
			<?php print $factarray["ADR2"]; ?><input type="text" name="ADR2" size="35" value="<?php print PrintReady(strip_tags($ADR2)); ?>" /><br />
			<?php print $factarray["CITY"]; ?><input type="text" name="CITY" value="<?php print PrintReady(strip_tags($CITY)); ?>" />
			<?php print $factarray["STAE"]; ?><input type="text" name="STAE" value="<?php print PrintReady(strip_tags($STAE)); ?>" /><br />
			<?php print $factarray["POST"]; ?><input type="text" name="POST" value="<?php print PrintReady(strip_tags($POST)); ?>" /><br />
			<?php print $factarray["CTRY"]; ?><input type="text" name="CTRY" value="<?php print PrintReady(strip_tags($CTRY)); ?>" />
			<input type="hidden" name="ADDR" value="<?php print PrintReady(strip_tags($ADDR)); ?>" />
		<?php } else { ?>
		<textarea name="ADDR" tabindex="<?php print $tabkey; ?>" cols="35" rows="4"><?php print PrintReady(strip_tags($ADDR)); ?></textarea>
		<?php } ?>
	</td>
	<?php $tabkey++; ?>
	</tr>
	<tr>
	<td class="shade2">
		<?php print $factarray["PHON"]; ?>
	</td>
	<td class="shade1" colspan="3">
		<input type="text" tabindex="<?php print $tabkey; $tabkey++; ?>" name="PHON" size="20" value="<?php print PrintReady($PHON); ?>" />
	</td>
	</tr>
	<tr>
		<td class="shade2">
				<?php print $factarray["FAX"]; ?>
		</td>
		<td class="shade1" colspan="3">
				<input type="text" tabindex="<?php print $tabkey; $tabkey++; ?>" name="FAX" size="20" value="<?php print PrintReady($FAX); ?>" />
	</td>
	</tr>
	<tr>
	<td class="shade2">
		<?php print $factarray["EMAIL"]; ?>
	</td>
	<td class="shade1" colspan="3">
		<input type="text" tabindex="<?php print $tabkey; ?>" name="EMAIL" size="40" value="<?php print PrintReady($EMAIL); ?>" />
	</td>
	<?php $tabkey++; ?>
	</tr>
	<tr><td colspan="4"><br /></td></tr>
<?php } ?>
</table>
</div>

<?php 
//------------------------------------------- FAMILY WITH SPOUSE TABS ------------------------ 
for($i=1; $i<=count($sfams); $i++) {
	?>
<div id="tab<?php echo $i; ?>" style="display: none;">
<table class="<?php print $TEXT_DIRECTION; ?> width80">
<tr><td class="topbottombar" colspan="4">
<?php
	$famreqdfacts = preg_split("/[,; ]/", $QUICK_REQUIRED_FAMFACTS);
	$famid = $sfams[$i-1];
	if (!isset($gm_changes[$famid."_".$GEDCOM])) $famrec = find_family_record($famid);
	print $gm_lang["family_with"]." ";
	$parents = find_parents_in_record($famrec);
	$spid = "";
	if($parents) {
		if($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
		else $spid=$parents["WIFE"];
	}
	if (!empty($spid)) {
		if (displayDetailsById($spid) && showLivingNameById($spid)) {
			print "<a href=\"#\" onclick=\"return quickEdit('".$spid."');\">";
			$name = get_person_name($spid);
			if ($SHOW_ID_NUMBERS) $name .= " (".$spid.")";
			$name .= " [".$gm_lang["edit"]."]";
			print PrintReady($name)."</a>\n";
		}
		else print $gm_lang["private"];
	}
	else print $gm_lang["unknown"];
	$subrecords = get_all_subrecords($famrec, "HUSB,WIFE,CHIL", false, false, false);
	$famfacts = array();
	foreach($subrecords as $ind=>$subrec) {
		$ft = preg_match("/1 (\w+)(.*)/", $subrec, $match);
		if ($ft>0) {
			$fact = trim($match[1]);
			$event = trim($match[2]);
		}
		else {
			$fact="";
			$event="";
		}
		if ($fact == "EVEN" || $fact == "FACT") $fact = get_gedcom_value("TYPE", 2, $subrec, '', false);
		if (in_array($fact, $famaddfacts)) {
			$newreqd = array();
			foreach($famreqdfacts as $r=>$rfact) {
				if ($rfact!=$fact) $newreqd[] = $rfact;
			}
			$famreqdfacts = $newreqd;
			$famfacts[] = array($fact, $subrec, 0);
		}
	}
	foreach($famreqdfacts as $ind=>$fact) {
		$famfacts[] = array($fact, "1 $fact\r\n", 1);
	}
	usort($famfacts, "compare_facts");
	?>
	</td></tr>
	<tr>
	<td class="shade2"><?php print $gm_lang["enter_pid"]; ?></td>
	<td class="shade1" colspan="3"><input type="text" size="10" name="SPID[<?php echo $i; ?>]" id="SPID<?php echo $i; ?>" value="<?php echo $spid; ?>" />
		<?php print_findindi_link("SPID$i","");?>
     </td>
	</tr>
	<?php if (!empty($spid)) { ?>
		<tr><td>&nbsp;</td></tr>
		<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_spouse_help", "qm"); if (preg_match("/1 SEX M/", $gedrec)>0) print $gm_lang["add_new_wife"]; else print $gm_lang["add_new_husb"];?></td></tr>
		<tr>
		<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
		<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SGIVN<?php echo $i; ?>" /></td>
		</tr>
		<?php $tabkey++; ?>
		<tr>
		<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
		<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SSURN<?php echo $i; ?>" /></td>
		<?php $tabkey++; ?>
		</tr>
		<?php if ($USE_RTL_FUNCTIONS) { ?>
			<tr>
			<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
			<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSGIVN<?php echo $i; ?>" /></td>
			</tr>
			<?php $tabkey++; ?>
			<tr>
			<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
			<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSSURN<?php echo $i; ?>" /></td>
			<?php $tabkey++; ?>
			</tr>
		<?php } ?>
		<tr>
		<td class="shade2"><?php print_help_link("edit_sex_help", "qm"); print $gm_lang["sex"];?></td>
		<td class="shade1" colspan="3">
			<select name="SSEX<?php echo $i; ?>" tabindex="<?php print $tabkey; ?>">
				<option value="M"<?php if (preg_match("/1 SEX F/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print $gm_lang["male"]; ?></option>
				<option value="F"<?php if (preg_match("/1 SEX M/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print $gm_lang["female"]; ?></option>
				<option value="U"<?php if (preg_match("/1 SEX U/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print $gm_lang["unknown"]; ?></option>
			</select>
		<?php $tabkey++; ?>
		</td>
		</tr>
		<tr>
		<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?><?php print $factarray["DATE"];?></td>
		<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="BDATE<?php echo $i; ?>" id="BDATE<?php echo $i; ?>" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("BDATE$i");?></td>
		</tr>
		<?php $tabkey++; ?>
		<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
		<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="BPLAC<?php echo $i; ?>" id="bplace<?php echo $i; ?>" />
		<?php print_findplace_link("place$f"); ?>
		<?php $tabkey++; ?>
		</td>
		</tr>
		<?php print_quick_resn("BRESN".$i); 
	}
	// NOTE: Update fact
	?>
	<tr><td>&nbsp;</td></tr>
	<tr><td class="topbottombar" colspan="5"><?php print_help_link("quick_update_fact_help", "qm"); print $gm_lang["update_fact"]; ?></td></tr>
	<tr>
	<td class="shade2">&nbsp;</td>
	<td class="shade2"><?php print $factarray["DATE"]; ?></td>
	<td class="shade2"><?php print $factarray["PLAC"]; ?></td>
	<td class="shade2"><?php print $gm_lang["delete"]; ?></td>
	</tr>
	<?php
	foreach($famfacts as $f=>$fact) {
		$fact_tag = $fact[0];
		$date = get_gedcom_value("DATE", 2, $fact[1], '', false);
		$plac = get_gedcom_value("PLAC", 2, $fact[1], '', false);
		$temp = get_gedcom_value("TEMP", 2, $fact[1], '', false);
		?>
			<tr>
				<td class="shade2">
				<?php if (isset($factarray[$fact_tag])) print $factarray[$fact_tag]; 
					else if (isset($gm_lang[$fact_tag])) print $gm_lang[$fact_tag]; 
					else print $fact_tag;
				?>
					<input type="hidden" name="F<?php echo $i; ?>TAGS[]" value="<?php echo $fact_tag; ?>" />
				</td>
				<td class="shade1"><input type="text" tabindex="<?php print $tabkey; $tabkey++;?>" size="15" name="F<?php echo $i; ?>DATES[]" id="F<?php echo $i; ?>DATE<?php echo $f; ?>" onblur="valid_date(this);" value="<?php echo htmlspecialchars($date); ?>" />&nbsp;<?php print_calendar_popup("F{$i}DATE{$f}");?></td>
				<?php if (empty($temp) && (!in_array($fact_tag, $nonplacfacts))) { ?>
					<td class="shade1"><input type="text" size="30" tabindex="<?php print $tabkey; $tabkey++; ?>" name="F<?php echo $i; ?>PLACS[]" id="F<?php echo $i; ?>place<?php echo $f; ?>" value="<?php print PrintReady(htmlspecialchars($plac)); ?>" />
					<?php print_findplace_link("F'.$i.'place$f"); ?>
					</td>
				<?php }
				else {
					print "<td class=\"shade1\"><select tabindex=\"".$tabkey."\" name=\"F".$i."TEMP[]\" >\n";
					print "<option value=''>".$gm_lang["no_temple"]."</option>\n";
					foreach($TEMPLE_CODES as $code=>$temple) {
						print "<option value=\"$code\"";
						if ($code==$temp) print " selected=\"selected\"";
						print ">$temple</option>\n";
					}
					print "</select>\n</td>\n";
					$tabkey++;
				}
				?>
				<td class="shade1 center">
					<input type="hidden" name="F<?php echo $i; ?>REMS[]" id="F<?php echo $i; ?>REM<?php echo $f; ?>" value="0" />
					<?php if (!$fact[2]) { ?>
					<a href="javascript: <?php print $gm_lang["delete"]; ?>" onclick="document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>REM<?php echo $f; ?>.value='1'; document.quickupdate.submit(); return false;">
						<img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" width="15" alt="<?php print $gm_lang["delete"]; ?>" />
					</a>
					<?php } ?>
				</td>
			</tr>
			<?php if ($SHOW_QUICK_RESN) {
				print_quick_resn("F".$i."RESNS[]");
			} ?>
		<?php
	}
// Note: add fact
if (count($famaddfacts)>0) { ?>
	<tr><td>&nbsp;</td></tr>
	<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_fact_help", "qm"); print $gm_lang["add_fact"]; ?></td></tr>
	<tr>
	<td class="shade2">&nbsp;</td>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["DATE"]; ?></td>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"]; ?></td>
	<td class="shade2">&nbsp;</td>
	</tr>
	<tr>
	<td class="shade1"><select name="F<?php echo $i; ?>newfact" tabindex="<?php print $tabkey; ?>">
		<option value=""><?php print $gm_lang["select_fact"]; ?></option>
	<?php $tabkey++; ?>
	<?php
	foreach($famaddfacts as $indexval => $fact) {
		$found = false;
		foreach($famfacts as $ind=>$value) {
			if ($fact==$value[0]) {
				$found=true;
				break;
			}
		}
		if (!$found) print "\t\t<option value=\"$fact\">".$factarray[$fact]."</option>\n";
	}
	?>
		</select>
	</td>
	<td class="shade1"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="F<?php echo $i; ?>DATE" id="F<?php echo $i; ?>DATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("F".$i."DATE");?></td>
	<?php $tabkey++; ?>
	<td class="shade1"><input type="text" tabindex="<?php print $tabkey; ?>" name="F<?php echo $i; ?>PLAC" id="F<?php echo $i; ?>place" />
	<?php print_findplace_link("F'.$i.'place"); ?>
	</td>
	<?php $tabkey++; ?>
	<td class="shade1">&nbsp;</td>
	</tr>
	<?php print_quick_resn("F".$i."RESN"); ?>
<?php }
// NOTE: Children
$chil = find_children_in_record($famrec);
	?>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="topbottombar" colspan="4"><?php print $gm_lang["children"]; ?></td>
	</tr>
	<tr>
			<input type="hidden" name="F<?php echo $i; ?>CDEL" value="" />
					<td class="shade2"><?php print $gm_lang["name"]; ?></td>
					<td class="shade2"><?php print $factarray["SEX"]; ?></td>
					<td class="shade2"><?php print $factarray["BIRT"]; ?></td>
					<td class="shade2"><?php print $gm_lang["remove"]; ?></td>
	</tr>
			<?php
				foreach($chil as $c=>$child) {
					print "<tr><td class=\"shade1\">";
					$name = get_person_name($child);
					$disp = displayDetailsById($child);
					if ($SHOW_ID_NUMBERS) $name .= " (".$child.")";
					$name .= " [".$gm_lang["edit"]."]";
					if ($disp||showLivingNameById($child)) {
						print "<a href=\"#\" onclick=\"return quickEdit('".$child."');\">";
						print PrintReady($name);
						print "</a>";
					}
					else print $gm_lang["private"];
					$childrec = find_person_record($child);
					print "</td>\n<td class=\"shade1 center\">";
					if ($disp) {
						print get_gedcom_value("SEX", 1, $childrec);
					}
					print "</td>\n<td class=\"shade1\">";
					if ($disp) {
						$birtrec = get_sub_record(1, "BIRT", $childrec);
						if (!empty($birtrec)) {
							if (showFact("BIRT", $child) && !FactViewRestricted($child, $birtrec)) {
								print get_gedcom_value("DATE", 2, $birtrec);
								print " -- ";
								print get_gedcom_value("PLAC", 2, $birtrec);
							}
						}
					}
					print "</td>\n";
					?>
					<td class="shade1 center" colspan="3">
						<a href="javascript: <?php print $gm_lang["remove_child"]; ?>" onclick="if (confirm('<?php print $gm_lang["confirm_remove"]; ?>')) { document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>CDEL.value='<?php echo $child; ?>'; document.quickupdate.submit(); } return false;">
							<img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" width="15" alt="<?php print $gm_lang["remove_child"]; ?>" />
						</a>
					</td>
					<?php
					print "</tr>\n";
				}
			?>
			<tr>
				<td class="shade2"><?php print $gm_lang["add_new_chil"]; ?></td>
				<td class="shade1" colspan="3"><input type="text" size="10" name="CHIL[]" id="CHIL<?php echo $i; ?>" />
                                <?php print_findindi_link("CHIL$i","");?>
                                </td>
			</tr>
<?php 
// NOTE: Add a child
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><b><?php print_help_link("quick_update_child_help", "qm"); print $gm_lang["add_new_chil"]; ?></b></td></tr>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>GIVN" /></td>
</tr>
	<?php $tabkey++; ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if ($USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>GIVN" /></td>
</tr>
	<?php $tabkey++; ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_sex_help", "qm"); print $gm_lang["sex"];?></td>
	<td class="shade1" colspan="3">
		<select name="C<?php echo $i; ?>SEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print $gm_lang["male"]; ?></option>
			<option value="F"><?php print $gm_lang["female"]; ?></option>
			<option value="U"><?php print $gm_lang["unknown"]; ?></option>
		</select>
	</td></tr>
	<?php $tabkey++; ?>
<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="C<?php echo $i; ?>DATE" id="C<?php echo $i; ?>DATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("C{$i}DATE");?></td>
	<?php $tabkey++; ?>
	</tr>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>PLAC" id="c<?php echo $i; ?>place" />
	<?php print_findplace_link("c'.$i.'place"); ?>
	</td>
	<?php $tabkey++; ?>
</tr>
<?php print_quick_resn("C".$i."RESN"); ?>
<tr><td colspan="4"><br /></td></tr>
</table>
</div>
	<?php
}

//------------------------------------------- NEW SPOUSE TAB ------------------------ 
?>
<div id="tab<?php echo $i; ?>" style="display: none;">
<table class="<?php print $TEXT_DIRECTION;?> width80">
<?php
// NOTE: New wife
?>
<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_spouse_help", "qm"); if (preg_match("/1 SEX M/", $gedrec)>0) print $gm_lang["add_new_wife"]; else print $gm_lang["add_new_husb"]; ?></td></tr>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SGIVN" /></td></tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if ($USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSGIVN" /></td>
</tr>
<tr>
	<?php $tabkey++; ?>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_sex_help", "qm"); print $gm_lang["sex"];?></td>
	<td class="shade1" colspan="3">
		<select name="SSEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"<?php if (preg_match("/1 SEX F/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print $gm_lang["male"]; ?></option>
			<option value="F"<?php if (preg_match("/1 SEX M/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print $gm_lang["female"]; ?></option>
			<option value="U"<?php if (preg_match("/1 SEX U/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print $gm_lang["unknown"]; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
</tr>
<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="BDATE" id="BDATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("BDATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="BPLAC" id="bplace" />
	<?php print_findplace_link("bplace"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php print_quick_resn("BRESN"); 

// NOTE: Marriage
?>
<tr><td>&nbsp;</td></tr>
<tr>
	<td class="topbottombar" colspan="4"><?php print_help_link("quick_update_marriage_help", "qm"); print $factarray["MARR"]; ?></td>
</tr>
<tr><td class="shade2">
		<?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="MDATE" id="MDATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("MDATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="MPLAC" id="mplace" />
	<?php print_findplace_link("mplace"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php print_quick_resn("MRESN");

// NOTE: New child
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><b><?php print_help_link("quick_update_child_help", "qm"); print $gm_lang["add_new_chil"]; ?></b></td></tr>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="CGIVN" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="CSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if ($USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HCGIVN" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HCSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_sex_help", "qm"); print $gm_lang["sex"];?></td>
	<td class="shade1" colspan="3">
		<select name="CSEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print $gm_lang["male"]; ?></option>
			<option value="F"><?php print $gm_lang["female"]; ?></option>
			<option value="U"><?php print $gm_lang["unknown"]; ?></option>
		</select>
	</td></tr>
	<?php $tabkey++; ?>
<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="CDATE" id="CDATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("CDATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="CPLAC" id="cplace" />
	<?php print_findplace_link("cplace"); ?>
	</td>
	<?php $tabkey++; ?>
</tr>
<?php print_quick_resn("CRESN"); ?>
</table>
</div>

<?php //------------------------------------------- FAMILY AS CHILD TABS ------------------------ 
$i++;
for($j=1; $j<=count($cfams); $j++) {
	?>
<div id="tab<?php echo $i; ?>" style="display: none;">
<table class="<?php print $TEXT_DIRECTION; ?> width80">
<?php
	$famreqdfacts = preg_split("/[,; ]/", $QUICK_REQUIRED_FAMFACTS);
	$parents = find_parents($cfams[$j-1]);
	$famid = $cfams[$j-1];
	if (!isset($gm_changes[$famid."_".$GEDCOM])) $famrec = find_family_record($famid);
	
	$subrecords = get_all_subrecords($famrec, "HUSB,WIFE,CHIL", false, false, false);
	$famfacts = array();
	foreach($subrecords as $ind=>$subrec) {
		$ft = preg_match("/1 (\w+)(.*)/", $subrec, $match);
		if ($ft>0) {
			$fact = trim($match[1]);
			$event = trim($match[2]);
		}
		else {
			$fact="";
			$event="";
		}
		if ($fact=="EVEN" || $fact=="FACT") $fact = get_gedcom_value("TYPE", 2, $subrec, '', false);
		if (in_array($fact, $famaddfacts)) {
			$newreqd = array();
			foreach($famreqdfacts as $r=>$rfact) {
				if ($rfact!=$fact) $newreqd[] = $rfact;
			}
			$famreqdfacts = $newreqd;
			$famfacts[] = array($fact, $subrec, 0);
		}
	}
	foreach($famreqdfacts as $ind=>$fact) {
		$famfacts[] = array($fact, "1 $fact\r\n", 1);
	}
	usort($famfacts, "compare_facts");
	$spid = "";
	if($parents) {
		if($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
		else $spid=$parents["WIFE"];
	}

// NOTE: Father
?>
	<tr><td class="topbottombar" colspan="4">
	<?php
	$label = $gm_lang["father"];
	if (!empty($parents["HUSB"])) {
		if (displayDetailsById($parents["HUSB"]) && showLivingNameById($parents["HUSB"])) {
			$fatherrec = find_person_record($parents["HUSB"]);
			$fsex = get_gedcom_value("SEX", 1, $fatherrec, '', false);
			if ($fsex=="F") $label = $gm_lang["mother"];
			print $label." ";
			print "<a href=\"#\" onclick=\"return quickEdit('".$parents["HUSB"]."');\">";
			$name = get_person_name($parents["HUSB"]);
			if ($SHOW_ID_NUMBERS) $name .= " (".$parents["HUSB"].")";
			$name .= " [".$gm_lang["edit"]."]";
			print PrintReady($name)."</a>\n";
		}
		else print $label." ".$gm_lang["private"];
	}
	else print $label." ".$gm_lang["unknown"];
	print "</td></tr>";
	print "<tr><td class=\"shade2\">".$gm_lang["enter_pid"]."<td  class=\"shade1\" colspan=\"3\"><input type=\"text\" size=\"10\" name=\"FATHER[$i]\" id=\"FATHER$i\" value=\"".$parents['HUSB']."\" />";
	print_findindi_link("FATHER$i","");
	print "</td></tr>";
?>
<?php if (empty($parents["HUSB"])) { ?>
	<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_spouse_help", "qm"); print $gm_lang["add_father"]; ?></td></tr>
	<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="FGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="FSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
	</tr>
	<?php if ($USE_RTL_FUNCTIONS) { ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HFGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HFSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
	</tr>
	<?php } ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_sex_help", "qm"); print $gm_lang["sex"];?></td>
	<td class="shade1" colspan="3">
		<select name="FSEX<?php echo $i; ?>" tabindex="<?php print $tabkey; ?>">
			<option value="M" selected="selected"><?php print $gm_lang["male"]; ?></option>
			<option value="F"><?php print $gm_lang["female"]; ?></option>
			<option value="U"><?php print $gm_lang["unknown"]; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
	</tr>
	<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="FBDATE<?php echo $i; ?>" id="FBDATE<?php echo $i; ?>" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("FBDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="FBPLAC<?php echo $i; ?>" id="Fbplace<?php echo $i; ?>" /><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="banchor1x" id="banchor1x" alt="" />
	<?php print_findplace_link("Fbplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
	</tr>
	<?php print_quick_resn("FBRESN$i");?>
	<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["DEAT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="FDDATE<?php echo $i; ?>" id="FDDATE<?php echo $i; ?>" onblur="valid_date(this);" /><?php print_calendar_popup("FDDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="FDPLAC<?php echo $i; ?>" id="Fdplace<?php echo $i; ?>" />
	<?php print_findplace_link("Fdplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
	</tr>
	<?php print_quick_resn("FDRESN$i"); 
}
?>
<?php
// NOTE: Mother
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4">
<?php
	$label = $gm_lang["mother"];
	if (!empty($parents["WIFE"])) {
		if (displayDetailsById($parents["WIFE"]) && showLivingNameById($parents["WIFE"])) {
			$motherrec = find_person_record($parents["WIFE"]);
			$msex = get_gedcom_value("SEX", 1, $motherrec, '', false);
			if ($msex=="M") $label = $gm_lang["father"];
			print $label." ";
			print "<a href=\"#\" onclick=\"return quickEdit('".$parents["WIFE"]."');\">";
			$name = get_person_name($parents["WIFE"]);
			if ($SHOW_ID_NUMBERS) $name .= " (".$parents["WIFE"].")";
			$name .= " [".$gm_lang["edit"]."]";
			print PrintReady($name)."</a>\n";
		}
		else print $label." ".$gm_lang["private"];
	}
	else print $label." ".$gm_lang["unknown"];
	print "</td></tr>\n";
	print "<tr><td  class=\"shade2\">".$gm_lang["enter_pid"]."<td  class=\"shade1\" colspan=\"3\"><input type=\"text\" size=\"10\" name=\"MOTHER[$i]\" id=\"MOTHER$i\" value=\"".$parents['WIFE']."\" />";
	print_findindi_link("MOTHER$i","");
	?>
</td></tr>
<?php if (empty($parents["WIFE"])) { ?>
<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_spouse_help", "qm"); print $gm_lang["add_mother"]; ?></td></tr>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="MGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="MSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if ($USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HMGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	</tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HMSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_sex_help", "qm"); print $gm_lang["sex"];?></td>
	<td class="shade1" colspan="3">
		<select name="MSEX<?php echo $i; ?>" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print $gm_lang["male"]; ?></option>
			<option value="F" selected="selected"><?php print $gm_lang["female"]; ?></option>
			<option value="U"><?php print $gm_lang["unknown"]; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
</tr>
<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="MBDATE<?php echo $i; ?>" id="MBDATE<?php echo $i; ?>" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("MBDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="MBPLAC<?php echo $i; ?>" id="Mbplace<?php echo $i; ?>" /><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="banchor1x" id="banchor1x" alt="" />
	<?php print_findplace_link("Mbplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php print_quick_resn("MBRESN$i");?>
<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["DEAT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="MDDATE<?php echo $i; ?>" id="MDDATE<?php echo $i; ?>" onblur="valid_date(this);" /><?php print_calendar_popup("MDDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="MDPLAC<?php echo $i; ?>" id="Mdplace<?php echo $i; ?>" />
	<?php print_findplace_link("Mdplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php print_quick_resn("MDRESN$i"); 
}
// NOTE: Update fact 
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_fact_help", "qm"); print $gm_lang["update_fact"]; ?></td></tr>
<tr>
	<td class="shade2">&nbsp;</td>
	<td class="shade2"><?php print $factarray["DATE"]; ?></td>
	<td class="shade2"><?php print $factarray["PLAC"]; ?></td>
	<td class="shade2"><?php print $gm_lang["delete"]; ?></td>
<?php
foreach($famfacts as $f=>$fact) {
	$fact_tag = $fact[0];
	$date = get_gedcom_value("DATE", 2, $fact[1], '', false);
	$plac = get_gedcom_value("PLAC", 2, $fact[1], '', false);
	$temp = get_gedcom_value("TEMP", 2, $fact[1], '', false);
	?>
			<tr>
				<td class="shade2">
				<?php if (isset($factarray[$fact_tag])) print $factarray[$fact_tag]; 
					else if (isset($gm_lang[$fact_tag])) print $gm_lang[$fact_tag]; 
					else print $fact_tag;
				?>
					<input type="hidden" name="F<?php echo $i; ?>TAGS[]" value="<?php echo $fact_tag; ?>" />
				</td>
				<td class="shade1"><input type="text" tabindex="<?php print $tabkey; $tabkey++;?>" size="15" name="F<?php echo $i; ?>DATES[]" id="F<?php echo $i; ?>DATE<?php echo $f; ?>" onblur="valid_date(this);" value="<?php echo htmlspecialchars($date); ?>" />&nbsp;<?php print_calendar_popup("F{$i}DATE$f");?></td>
				<?php if (empty($temp) && (!in_array($fact_tag, $nonplacfacts))) { ?>
					<td class="shade1"><input size="30" type="text" tabindex="<?php print $tabkey; $tabkey++; ?>" name="F<?php echo $i; ?>PLACS[]" id="F<?php echo $i; ?>place<?php echo $f; ?>" value="<?php print PrintReady(htmlspecialchars($plac)); ?>" />
					<?php print_findplace_link("F'.$i.'place$f"); ?>
                         </td>
				<?php }
				else {
					print "<td class=\"shade1\"><select tabindex=\"".$tabkey."\" name=\"F".$i."TEMP[]\" >\n";
					print "<option value=''>".$gm_lang["no_temple"]."</option>\n";
					foreach($TEMPLE_CODES as $code=>$temple) {
						print "<option value=\"$code\"";
						if ($code==$temp) print " selected=\"selected\"";
						print ">$temple</option>\n";
					}
					print "</select>\n</td>\n";
					$tabkey++;
				}
				?>
				<td class="shade1 center">
					<input type="hidden" name="F<?php echo $i; ?>REMS[]" id="F<?php echo $i; ?>REM<?php echo $f; ?>" value="0" />
					<?php if (!$fact[2]) { ?>
					<a href="javascript: <?php print $gm_lang["delete"]; ?>" onclick="if (confirm('<?php print $gm_lang["confirm_remove"]; ?>')) { document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>REM<?php echo $f; ?>.value='1'; document.quickupdate.submit(); } return false;">
						<img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" width="15" alt="<?php print $gm_lang["delete"]; ?>" />
					</a>
					<?php } ?>
				</td>
			</tr>
			<?php if ($SHOW_QUICK_RESN) {
				print_quick_resn("F".$i."RESNS[]");
			} ?>
	<?php
}
?>
</tr>
<?php
// NOTE: Add new fact
?>
<?php if (count($famaddfacts)>0) { ?>
	<tr><td>&nbsp;</td></tr>
	<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_fact_help", "qm"); print $gm_lang["add_fact"]; ?></td></tr>
	<tr>
		<td class="shade2">&nbsp;</td>
		<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["DATE"]; ?></td>
		<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"]; ?></td>
		<td class="shade2">&nbsp;</td>
		</tr>
	<tr>
		<td class="shade1"><select name="F<?php echo $i; ?>newfact" tabindex="<?php print $tabkey; ?>">
			<option value=""><?php print $gm_lang["select_fact"]; ?></option>
		<?php $tabkey++; ?>
		<?php
		foreach($famaddfacts as $indexval => $fact) {
			$found = false;
			foreach($famfacts as $ind=>$value) {
				if ($fact==$value[0]) {
					$found=true;
					break;
				}
			}
			if (!$found) print "\t\t<option value=\"$fact\">".$factarray[$fact]."</option>\n";
		}
		?>
			</select>
		</td>
		<td class="shade1"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="F<?php echo $i; ?>DATE" id="F<?php echo $i; ?>DATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("F".$i."DATE");?></td>
		<?php $tabkey++; ?>
		<td class="shade1"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="F<?php echo $i; ?>PLAC" id="F<?php echo $i; ?>place" />
		<?php print_findplace_link("F'.$i.'place"); ?>
		</td>
		<?php $tabkey++; ?>
		<td class="shade1">&nbsp;</td>
	</tr>
	<?php print_quick_resn("RESN"); ?>
<?php }
// NOTE: Children
$chil = find_children_in_record($famrec, $pid);
	?>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="topbottombar" colspan="4"><?php print $gm_lang["children"]; ?></td>
	</tr>
	<tr>
		<input type="hidden" name="F<?php echo $i; ?>CDEL" value="" />
					<td class="shade2"><?php print $gm_lang["name"]; ?></td>
					<td class="shade2"><?php print $factarray["SEX"]; ?></td>
					<td class="shade2"><?php print $factarray["BIRT"]; ?></td>
					<td class="shade2"><?php print $gm_lang["remove"]; ?></td>
				</tr>
			<?php
				foreach($chil as $c=>$child) {
					print "<tr><td class=\"shade1\">";
					$name = get_person_name($child);
					$disp = displayDetailsById($child);
					if ($SHOW_ID_NUMBERS) $name .= " (".$child.")";
					$name .= " [".$gm_lang["edit"]."]";
					if ($disp||showLivingNameById($child)) {
						print "<a href=\"#\" onclick=\"return quickEdit('".$child."');\">";
						print PrintReady($name);
						print "</a>";
					}
					else print $gm_lang["private"];
					$childrec = find_person_record($child);
					print "</td>\n<td class=\"shade1 center\">";
					if ($disp) {
						print get_gedcom_value("SEX", 1, $childrec);
					}
					print "</td>\n<td class=\"shade1\">";
					if ($disp) {
						$birtrec = get_sub_record(1, "BIRT", $childrec);
						if (!empty($birtrec)) {
							if (showFact("BIRT", $child) && !FactViewRestricted($child, $birtrec)) {
								print get_gedcom_value("DATE", 2, $birtrec);
								print " -- ";
								print get_gedcom_value("PLAC", 2, $birtrec);
							}
						}
					}
					print "</td>\n";
					?>
					<td class="shade1 center" colspan="3">
						<a href="javascript: <?php print $gm_lang["remove_child"]; ?>" onclick="document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>CDEL.value='<?php echo $child; ?>'; document.quickupdate.submit(); return false;">
							<img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" width="15" alt="<?php print $gm_lang["remove_child"]; ?>" />
						</a>
					</td>
					<?php
					print "</tr>\n";
					$i++;
				}
			?>
			<tr>
				<td class="shade2"><?php print $gm_lang["add_child_to_family"]; ?></td>
				<td class="shade1" colspan="3"><input type="text" size="10" name="CHIL[]" id="CHIL<?php echo $i; ?>" />
                                <?php print_findindi_link("CHIL$i","");?>
                                </td>
			</tr>
<?php
// NOTE: Add a child
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php print_help_link("quick_update_child_help", "qm"); print $gm_lang["add_child_to_family"]; ?></td></tr>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>GIVN" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if ($USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_given_name_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>GIVN" /></td>
</tr>
<tr>
	<?php $tabkey++; ?>
	<td class="shade2"><?php print_help_link("edit_surname_help", "qm"); print $factarray["_HEB"];?></td>
	<td class="shade1" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="shade2"><?php print_help_link("edit_sex_help", "qm"); print $gm_lang["sex"];?></td>
	<td class="shade1" colspan="3">
		<select name="C<?php echo $i; ?>SEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print $gm_lang["male"]; ?></option>
			<option value="F"><?php print $gm_lang["female"]; ?></option>
			<option value="U"><?php print $gm_lang["unknown"]; ?></option>
		</select>
	</td></tr>
	<?php $tabkey++; ?>
<tr>
	<td class="shade2"><?php print_help_link("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="shade1" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="C<?php echo $i; ?>DATE" id="C<?php echo $i; ?>DATE" onblur="valid_date(this);" />&nbsp;<?php print_calendar_popup("C{$i}DATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="shade2"><?php print_help_link("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="shade1" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>PLAC" id="c<?php echo $i; ?>place" />
	<?php print_findplace_link("c'.$i.'place"); ?>
	</td>
	<?php $tabkey++; ?>
</tr>
<?php print_quick_resn("C".$i."RESN"); ?>
</table>
</div>
	<?php
	$i++;
}
?>
<input type="submit" value="<?php print $gm_lang["save"]; ?>" />
</form>
<?php
}
print_simple_footer();
?>