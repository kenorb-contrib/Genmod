<?php
/**
 * PopUp Window to provide users with a simple quick update form.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 19 August 2005
 *
 * @package Genmod
 * @subpackage Edit
 * @version $Id$
 */

require("config.php");

if ($_SESSION["cookie_login"]) {
	if (LOGIN_URL == "") header("Location: login.php?type=simple&url=edit_interface.php");
	else header("Location: ".LOGIN_URL."?type=simple&url=edit_interface.php");
	exit;
}

//-- @TODO make list a configurable list
$addfacts = preg_split("/[,; ]/", GedcomConfig::$QUICK_ADD_FACTS);
usort($addfacts, "factsort");

$reqdfacts = preg_split("/[,; ]/", GedcomConfig::$QUICK_REQUIRED_FACTS);

//-- @TODO make list a configurable list
$famaddfacts = preg_split("/[,; ]/", GedcomConfig::$QUICK_ADD_FAMFACTS);
usort($famaddfacts, "factsort");
$famreqdfacts = preg_split("/[,; ]/", GedcomConfig::$QUICK_REQUIRED_FAMFACTS);

$align="right";
if ($TEXT_DIRECTION=="rtl") $align="left";

PrintSimpleHeader(GM_LANG_quick_update_title);

//print "<pre>";
//print_r($_POST);
//print "</pre>";

//-- only allow logged in users to access this page
if ((!GedcomConfig::$ALLOW_EDIT_GEDCOM)||(!GedcomConfig::$USE_QUICK_UPDATE)||(empty($gm_user->username))) {
	print GM_LANG_access_denied;
	PrintSimpleFooter();
	exit;
}

$can_auto_accept = true;
if (!isset($action)) $action="";
if (!isset($closewin)) $closewin=0;
if (empty($pid)) {
	if (!empty($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) $pid = $gm_user->gedcomid[GedcomConfig::$GEDCOMID];
	else $pid = "";
}
$pid = CleanInput($pid);

//-- only allow editors or users who are editing their own individual or their immediate relatives
if (!$gm_user->userCanEditOwn($pid)) {
	print GM_LANG_access_denied;
	PrintSimpleFooter();
	exit;
}

//-- find the latest gedrec for the individual
if (ChangeFunctions::GetChangeData(true, $pid, true, "", "")) {
	$rec = ChangeFunctions::GetChangeData(false, $pid, true, "gedlines", "");
	$gedrec = $rec[GedcomConfig::$GEDCOMID][$pid];
}
else $gedrec = FindGedcomRecord($pid);

//-- only allow edit of individual records
$disp = true;
$ct = preg_match("/0 @$pid@ (.*)/", $gedrec, $match);
if ($ct>0) {
	$type = trim($match[1]);
	if ($type=="INDI") {
		$disp = PrivacyFunctions::displayDetailsById($pid);
	}
	else {
		print GM_LANG_access_denied;
		PrintSimpleFooter();
		exit;
	}
}

if ((!$disp)||(!GedcomConfig::$ALLOW_EDIT_GEDCOM)) {

	print GM_LANG_access_denied;
	//-- display messages as to why the editing access was denied
	if (!$gm_user->userCanEdit()) print "<br />".GM_LANG_user_cannot_edit;
	if (!GedcomConfig::$ALLOW_EDIT_GEDCOM) print "<br />".GM_LANG_gedcom_editing_disabled;
	if (!$disp) {
		print "<br />".GM_LANG_privacy_prevented_editing;
		if (!empty($pid)) print "<br />".GM_LANG_privacy_not_granted." pid $pid.";
	}
	PrintSimpleFooter();
	exit;
}

//-- privatize the record so that line numbers etc. match what was in the display
//-- data that is hidden because of privacy is stored in the $gm_private_records array
//-- any private data will be restored when the record is replaced
$gedrec = PrivacyFunctions::PrivatizeGedcom($gedrec);

//-- put the updates into the gedcom record
if ($action=="update") {
	function check_updated_facts($i, &$famrec, $TAGS, $prefix){
		global $typefacts, $pid, $change_type, $change_id, $can_auto_accept;
		$famrec = trim($famrec);
		$ct = preg_match("/0 @(.+)@/", $famrec, $match);
//		print "famrec: ".$famrec;
//		if (preg_match("/0 @(.+)@ FAM/", $famrec, $match)) print $match[1]." - ".GetFamilyDescriptor($match[1]);
//		else if (preg_match("/0 @(.+)@ INDI/", $famrec, $match)) print $match[1]." - ".GetPersonName($match[1]);
		$famupdate = false;
		$repeat_tags = array();
		$var = $prefix.$i."DATES";
		if (!empty($GLOBALS[$var])) $DATES = $GLOBALS[$var];
		else $DATES = array();
		$var = $prefix.$i."PLACS";
		if (!empty($GLOBALS[$var])) $PLACS = $GLOBALS[$var];
		else $PLACS = array();
		$var = $prefix.$i."TEMPS";
		if (!empty($GLOBALS[$var])) $TEMPS = $GLOBALS[$var];
		else $TEMPS = array();
		$var = $prefix.$i."RESNS";
		if (!empty($GLOBALS[$var])) $RESNS = $GLOBALS[$var];
		else $RESNS = array();
		$var = $prefix.$i."REMS";
		if (!empty($GLOBALS[$var])) $REMS = $GLOBALS[$var];
		else $REMS = array();
		
		for($j=0; $j<count($TAGS); $j++) {
			if (!empty($TAGS[$j])) {
				$fact = $TAGS[$j];
//				print $fact;
				if (!isset($repeat_tags[$fact])) $repeat_tags[$fact] = 1;
				else $repeat_tags[$fact]++;
				$DATES[$j] = EditFunctions::CheckInputDate($DATES[$j]);
				if (!isset($REMS[$j])) $REMS[$j] = 0;
				if ($REMS[$j]==1) {
					$DATES[$j]="";
					$PLACS[$j]="";
					$TEMPS[$j]="";
					$RESNS[$j]="";
				}
				if ((empty($DATES[$j]))&&(empty($PLACS[$j]))&&(empty($TEMPS[$j]))&&(empty($RESNS[$j]))) {
					$factrec="";
				}
				else {
					if (!in_array($fact, $typefacts)) $factrec = "1 $fact\r\n";
					else $factrec = "1 EVEN\r\n2 TYPE $fact\r\n";
					if (!empty($DATES[$j])) $factrec .= "2 DATE $DATES[$j]\r\n";
					if (!empty($PLACS[$j])) $factrec .= "2 PLAC ".stripslashes($PLACS[$j])."\r\n";
					if (!empty($TEMPS[$j])) $factrec .= "2 TEMP $TEMPS[$j]\r\n";
					if (!empty($RESNS[$j])) $factrec .= "2 RESN $RESNS[$j]\r\n";
				}
				if (!in_array($fact, $typefacts)) $lookup = "1 $fact";
				else $lookup = "1 EVEN\r\n2 TYPE $fact\r\n";
				$pos1 = strpos($famrec, $lookup);
//				print $pos1."=pos1";
				$k=1;
				//-- make sure we are working with the correct fact
				while($k<$repeat_tags[$fact]) {
					$pos1 = strpos($famrec, $lookup, $pos1+5);
					$k++;
					if ($pos1===false) break;
				}
//				print $pos1."=pos1";
				$noupdfact = false;
				if ($pos1!==false) {
					$pos2 = strpos($famrec, "\n1 ", $pos1+5);
					if ($pos2===false) $pos2 = strlen($famrec);
					$oldfac = trim(substr($famrec, $pos1, $pos2-$pos1));
					$noupdfact = PrivacyFunctions::FactEditRestricted($pid, $oldfac);
					if ($noupdfact) {
						print "<br />".GM_LANG_update_fact_restricted." ".$factarray[$fact]."<br /><br />";
					}
					else {
						//-- delete the fact
						if ($REMS[$j]==1) { 
//							$famupdate = true;
							if (EditFunctions::ReplaceGedrec($match[1], $oldfac, "", $fact, $change_id, $change_type, "", "FAM")) $famupdate = true;
							$famrec = substr($famrec, 0, $pos1) . "\r\n". substr($famrec, $pos2);
//							print "sfamupdate_del [".$factrec."]{".$oldfac."}";
						}
						else if (!empty($oldfac) && !empty($factrec)) {
							$factrec = $oldfac;
							
							if (!empty($DATES[$j])) {
								if (strstr($factrec, "\n2 DATE")) $factrec = preg_replace("/2 DATE.*/", "2 DATE $DATES[$j]", $factrec);
								else $factrec = $factrec."\r\n2 DATE $DATES[$j]";
							}
							else {
								if (strstr($factrec, "\n2 DATE")) $factrec = preg_replace("/2 DATE.*/", "", $factrec);
							}
							
							if (!empty($PLACS[$j])) {
								$PLACS[$j] = stripslashes($PLACS[$j]);
								if (strstr($factrec, "\n2 PLAC")) $factrec = preg_replace("/2 PLAC.*/", "2 PLAC $PLACS[$j]", $factrec);
								else $factrec = $factrec."\r\n2 PLAC $PLACS[$j]";
							}
							else {
								if (strstr($factrec, "\n2 PLAC")) $factrec = preg_replace("/2 PLAC.*/", "", $factrec);
							}
							
							if (!empty($TEMPS[$j])) {
								if (strstr($factrec, "\n2 TEMP")) $factrec = preg_replace("/2 TEMP.*/", "2 TEMP $TEMPS[$j]", $factrec);
								else $factrec = $factrec."\r\n2 TEMP $TEMPS[$j]";
							}
							else {
								if (strstr($factrec, "\n2 TEMP")) $factrec = preg_replace("/2 TEMP.*/", "", $factrec);
							}
							
							if (!empty($RESNS[$j])) {
								if (strstr($factrec, "\n2 RESN")) $factrec = preg_replace("/2 RESN.*/", "2 RESN $RESNS[$j]", $factrec);
								else $factrec = $factrec."\r\n2 RESN $RESNS[$j]";
							}
							else {
								if (strstr($factrec, "\n2 RESN")) $factrec = preg_replace("/2 RESN.*/", "", $factrec);
							}
								
							$factrec = preg_replace("/[\r\n]+/", "\r\n", $factrec);
							$oldfac = preg_replace("/[\r\n]+/", "\r\n", $oldfac);
//			print "<table><tr><th>new</th><th>old</th></tr><tr><td><pre>$factrec</pre></td><td><pre>$oldfac</pre></td></tr></table>";
							if (trim($factrec) != trim($oldfac)) {
//Print "<br />This record will be updated";								
//								$famupdate = true;
								if (EditFunctions::ReplaceGedrec($match[1], $oldfac, $factrec, $fact, $change_id, $change_type, "", "FAM")) $famupdate = true;

								$famrec = substr($famrec, 0, $pos1) . trim($factrec)."\r\n" . substr($famrec, $pos2);
//			print "sfamupdate3 [".$factrec."]{".$oldfac."}";
							}
						}
					}
				}
				else if (!empty($factrec)) {
					if (EditFunctions::ReplaceGedrec($match[1], "", $factrec, $fact, $change_id, $change_type, "", "FAM")) $famupdate = true;
					$famrec .= "\r\n".$factrec;
//					$famupdate = true;
//						print "sfamupdate2";
				}
			}
		}
		return $famupdate;
	}
	
	print "<h3>".GM_LANG_quick_update_title."</h3>\n";
	print "<b>".PrintReady(GetPersonName($pid, $gedrec))."</b><br /><br />";
	
	WriteToLog("EditQuickUpdate-> Quick update attempted for $pid by >".$gm_user->username."<", "I", "G", GedcomConfig::$GEDCOMID);

	$change_id = EditFunctions::GetNewXref("CHANGE");
	$updated = false;
	$error = "";
	$oldgedrec = $gedrec;
	//-- check for name update
	$namerec = (GetSubRecord(1, "1 NAME", $gedrec));
	$oldnamerec = $namerec;
	if (!empty($GIVN) || !empty($SURN)) {
		if (!empty($namerec)) {
			if (!empty($GIVN)) {
				$namerec = preg_replace("/1 NAME.+\/(.*)\//", "1 NAME $GIVN /$1/", $namerec);
				if (preg_match("/2 GIVN/", $namerec)>0) {
					$oldgivn = GetGedcomValue("GIVN", 2, $namerec);
					$namerec = preg_replace("/2 GIVN $oldgivn/", "2 GIVN $GIVN", $namerec);
				}
				else $namerec.="\r\n2 GIVN $GIVN";
			}
			if (!empty($SURN)) {
				$namerec = preg_replace("/1 NAME(.+)\/.*\//", "1 NAME$1/$SURN/", $namerec);
				if (preg_match("/2 SURN/", $namerec)>0) {
					$oldsurn = GetGedcomValue("SURN", 2, $namerec);
					$namerec = preg_replace("/2 SURN $oldsurn/", "2 SURN $SURN", $namerec);
				}
				else $namerec.="\r\n2 SURN $SURN";
			}
		}
		else $namerec = "1 NAME $GIVN /$SURN/\r\n2 GIVN $GIVN\r\n2 SURN $SURN";
		
	}
	if (!empty($RSURN) || !empty($RGIVN)) {
		if (preg_match("/2 ROMN/", $namerec)>0) {
			if (!empty($RGIVN)) {
				$namerec = preg_replace("/2 ROMN.+\/(.*)\//", "2 ROMN $RGIVN /$1/", $namerec);
			}
			if (!empty($RSURN)) {
				$namerec = preg_replace("/2 ROMN(.+)\/.*\//", "2 ROMN$1/$RSURN/", $namerec);
			}
		}
		else {
			if (empty($namerec)) $namerec = "1 NAME $RGIVN /$RSURN/\r\n2 ROMN $RGIVN /$RSURN/";
			else $namerec .= "\r\n2 ROMN $RGIVN /$RSURN/"; 
		}
	}
	//-- rtl name update
	if (GedcomConfig::$USE_RTL_FUNCTIONS) {
		if (!empty($HSURN) || !empty($HGIVN)) {
			if (preg_match("/2 _HEB/", $namerec)>0) {
				if (!empty($HGIVN)) {
					$namerec = preg_replace("/2 _HEB.+\/(.*)\//", "2 _HEB $HGIVN /$1/", $namerec);
				}
				if (!empty($HSURN)) {
					$namerec = preg_replace("/2 _HEB(.+)\/.*\//", "2 _HEB$1/$HSURN/", $namerec);
				}
			}
			else $namerec .= "\r\n2 _HEB $HGIVN /$HSURN/"; 
		}
	}
//		print "<pre>NAME\n".$oldnamerec."</pre>\n";
//		print "Length: ".strlen($oldnamerec);
//		print "<pre>NAME\n".$namerec."</pre>\n";
//		print "Length: ".strlen($namerec);
	
	if ($namerec != $oldnamerec) {
		if (empty($oldnamerec)) $type = "add_name";
		else $type = "edit_name";
		if (EditFunctions::ReplaceGedrec($pid, $oldnamerec, $namerec, "NAME", $change_id, $type, "", "INDI")) $updated = true;
	}
	
	//-- update the person's gender
	if (!empty($SEX)) {
		$sexrec = trim(GetSubRecord(1, "1 SEX", $gedrec));
		$oldsexrec = $sexrec;
		if (preg_match("/1 SEX (\w*)/", $sexrec, $match)>0) {
			if ($match[1] != $SEX) {
				$sexrec = preg_replace("/1 SEX (\w*)/", "1 SEX $SEX", $sexrec);
			}
		}
		else {
			$sexrec = "1 SEX $SEX";
		}
		if (trim($sexrec) != trim($oldsexrec)) {
		if (empty($oldsexrec)) $type = "add_gender";
		else $type = "edit_gender";
			if (EditFunctions::ReplaceGedrec($pid, $oldsexrec, $sexrec, "SEX", $change_id, $type)) $updated = true;
		}
	}

	//-- check for updated facts
	if (count($TAGS)>0) {
		$updated |= check_updated_facts("", $gedrec, $TAGS, "");
//		print "FACTS <pre>$gedrec</pre>";
	}

	//-- check for new fact
	if (!empty($newfact)) {
		if (!in_array($newfact, $typefacts)) $factrec = "1 $newfact\r\n";
		else $factrec = "1 EVEN\r\n2 TYPE $newfact\r\n";
		if (!empty($DATE)) {
			$DATE = EditFunctions::CheckInputDate($DATE);
			$factrec .= "2 DATE $DATE\r\n";
		}
		if (!empty($PLAC)) $factrec .= "2 PLAC $PLAC\r\n";
		if (!empty($TEMP)) $factrec .= "2 TEMP $TEMP\r\n";
		if (!empty($RESN)) $factrec .= "2 RESN $RESN\r\n";
		//-- make sure that there is at least a Y
		if (preg_match("/\n2 \w*/", $factrec)==0) $factrec = "1 $newfact Y\r\n";
		$gedrec .= "\r\n".$factrec;
		$updated = EditFunctions::ReplaceGedrec($pid, "", $factrec, $newfact, $change_id, $change_type);
	}

	//-- check for photo update
	if (!empty($_FILES["FILE"]['tmp_name'])) {
		$upload_errors = array(GM_LANG_file_success, GM_LANG_file_too_big, GM_LANG_file_too_big,GM_LANG_file_partial, GM_LANG_file_missing);
		if (!move_uploaded_file($_FILES['FILE']['tmp_name'], GedcomConfig::$MEDIA_DIRECTORY.basename($_FILES['FILE']['name']))) {
			$error .= "<br />".GM_LANG_upload_error."<br />".$upload_errors[$_FILES['FILE']['error']];
		}
		else {
			$filename = GedcomConfig::$MEDIA_DIRECTORY.basename($_FILES['FILE']['name']);
			$thumbnail = GedcomConfig::$MEDIA_DIRECTORY."thumbs/".basename($_FILES['FILE']['name']);
			generate_thumbnail($filename, $thumbnail);

			$factrec = "1 OBJE\r\n";
			$factrec .= "2 FILE ".$filename."\r\n";
			if (!empty($TITL)) $factrec .= "2 TITL $TITL\r\n";

			if (empty($replace)) $gedrec .= "\r\n".$factrec;
			else {
				$fact = "OBJE";
				$pos1 = strpos($gedrec, "1 $fact");
				if ($pos1!==false) {
					$pos2 = strpos($gedrec, "\n1 ", $pos1+1);
					if ($pos2===false) $pos2 = strlen($gedrec)-1;
					$gedrec = substr($gedrec, 0, $pos1) . "\r\n".$factrec . substr($gedrec, $pos2);
				}
				else $gedrec .= "\r\n".$factrec;
			}
			$updated = true;
		}
	}

	//--address phone email
	$factrec = "";
	if (!empty($ADDR)) {
		if (!empty($ADR1)||!empty($CITY)||!empty($POST)) {
			$factrec = "1 ADDR\r\n";
			if (!empty($_NAME)) $factrec.="2 _NAME ".$_NAME."\r\n";
			if (!empty($ADR1)) $factrec.="2 ADR1 ".$ADR1."\r\n";
			if (!empty($ADR2)) $factrec.="2 ADR2 ".$ADR2."\r\n";
			if (!empty($CITY)) $factrec.="2 CITY ".$CITY."\r\n";
			if (!empty($STAE)) $factrec.="2 STAE ".$STAE."\r\n";
			if (!empty($POST)) $factrec.="2 POST ".$POST."\r\n";
			if (!empty($CTRY)) $factrec.="2 CTRY ".$CTRY."\r\n";
		}
		else {
			$factrec = "1 ADDR ";
			$lines = preg_split("/\r*\n/", $ADDR);
			$factrec .= $lines[0]."\r\n";
			for($i=1; $i<count($lines); $i++) $factrec .= "2 CONT ".$lines[$i]."\r\n";
		}
	}
	$oldfact = GetSubRecord(1, "1 ADDR", $gedrec);
	if (!empty($oldfact)) {
		$oldsubs = preg_split("/\r?\n/", $oldfact);
		foreach ($oldsubs as $key => $sub) {
			if (!preg_match("/(1|2) (ADDR|_NAME|ADR1|ADR2|CITY|STAE|POST|CTRY|CONT)/", $sub)) $factrec = $factrec . $sub ."\r\n";
		}
		if (trim($oldfact) != trim($factrec)) $updated = EditFunctions::ReplaceGedrec($pid, $oldfact, $factrec, "ADDR", $change_id, $change_type);
	}
	else if (!empty($factrec)) {
		$updated = EditFunctions::ReplaceGedrec($pid, "", $factrec, "ADDR", $change_id, $change_type);
	}

	$factrec = "";
	if (!empty($PHON)) $factrec = "1 PHON $PHON\r\n";
	$oldfact = GetSubRecord(1, "1 PHON", $gedrec);
	if (!empty($oldfact)) {
		$pos2 = strpos($oldfact, "\n2 ", 0);
		if ($pos2===false) $pos2 = strlen($oldfact)-1;
		$factrec = $factrec. substr($oldfact, $pos2+1);
		if (trim($oldfact) != trim($factrec)) $updated = EditFunctions::ReplaceGedrec($pid, $oldfact, $factrec, "PHON", $change_id, $change_type);
	}
	else if (!empty($factrec)) {
		$updated = EditFunctions::ReplaceGedrec($pid, "", $factrec, "PHON", $change_id, $change_type);
	}

	$factrec = "";
	if (!empty($FAX)) $factrec = "1 FAX $FAX\r\n";
	$oldfact = GetSubRecord(1, "1 FAX", $gedrec);
	if (!empty($oldfact)) {
		$pos2 = strpos($oldfact, "\n2 ", 0);
		if ($pos2===false) $pos2 = strlen($oldfact)-1;
		$factrec = $factrec . substr($oldfact, $pos2+1);
		if (trim($oldfact) != trim($factrec)) $updated = EditFunctions::ReplaceGedrec($pid, $oldfact, $factrec, "FAX", $change_id, $change_type);
	}
	else if (!empty($factrec)) {
		$updated = EditFunctions::ReplaceGedrec($pid, "", $factrec, "FAX", $change_id, $change_type);
	}

	$factrec = "";
	if (!empty($EMAIL)) $factrec = "1 EMAIL $EMAIL\r\n";
	$oldfact = GetSubRecord(1, "1 EMAIL", $gedrec);
	if (!empty($oldfact)) {
		$pos2 = strpos($oldfact, "\n2 ", 0);
		if ($pos2===false) $pos2 = strlen($oldfact)-1;
		$factrec = $factrec . substr($oldfact, $pos2+1);
		if (trim($oldfact) != trim($factrec)) $updated = EditFunctions::ReplaceGedrec($pid, $oldfact, $factrec, "EMAIL", $change_id, $change_type);
	}
	else if (!empty($factrec)) {
		$updated = EditFunctions::ReplaceGedrec($pid, "", $factrec, "EMAIL", $change_id, $change_type);
	}
	
	//-- spouse family tabs
//	$sfams = find_families_in_record($gedrec, "FAMS");
	$sfams = FindSfamilyIds($pid, true);
	for($i=1; $i<=count($sfams); $i++) {
		$famupdate = false;
		$famid = $sfams[$i-1]["famid"];
		if (ChangeFunctions::GetChangeData(true, $famid, true)) {
			$rec = ChangeFunctions::GetChangeData(false, $famid, true, "gedlines");
			$famrec = $rec[GedcomConfig::$GEDCOMID][$famid];
		}
		else $famrec = FindFamilyRecord($famid);
		$oldfamrec = $famrec;
		$parents = FindParentsInRecord($famrec);
		//-- update the spouse
		$spid = "";
		if($parents) {
			if($pid!=$parents["HUSB"]) {
				$tag="HUSB";
				$spid = $parents['HUSB'];
			}
			else {
				$tag = "WIFE";
				$spid = $parents['WIFE'];
			}
		}
		
		$sgivn = "SGIVN$i";
		$ssurn = "SSURN$i";
		//--add new spouse name, birth
		if (!empty($$sgivn) || !empty($$ssurn)) {
			//-- first add the new spouse
			$spouserec = "0 @REF@ INDI\r\n";
			$spouserec .= "1 NAME ".$$sgivn." /".$$ssurn."/\r\n";
			if (!empty($$sgivn)) $spouserec .= "2 GIVN ".$$sgivn."\r\n";
			if (!empty($$ssurn)) $spouserec .= "2 SURN ".$$ssurn."\r\n";
			$hsgivn = "HSGIVN$i";
			$hssurn = "HSSURN$i";
			if (!empty($$hsgivn) || !empty($$hssurn)) {
				$spouserec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
			}
			$rsgivn = "RSGIVN$i";
			$rssurn = "RSSURN$i";
			if (!empty($$rsgivn) || !empty($$rssurn)) {
				$spouserec .= "2 ROMN ".$$rsgivn." /".$$rssurn."/\r\n";
			}
			$SPID[$i] = EditFunctions::AppendGedrec($spouserec, "INDI", $change_id, $change_type);
			$ssex = "SSEX$i";
			if (!empty($$ssex)) {
				$spouserec .= "1 SEX ".$$ssex."\r\n";
				EditFunctions::ReplaceGedrec($SPID[$i], "", "1 SEX ".$$ssex, "SEX", $change_id, $change_type);
			}
			$bdate = "BDATE$i";
			$bplac = "BPLAC$i";
			if (!empty($$bdate)||!empty($$bplac)) {
				$brec = "1 BIRT\r\n";
				if (!empty($$bdate)) {
					$bdate = $$bdate;
					$bdate = EditFunctions::CheckInputDate($bdate);
					$brec .= "2 DATE $bdate\r\n";
				}
				if (!empty($$bplac)) $brec .= "2 PLAC ".$$bplac."\r\n";
				$bresn = "BRESN$i";
				if (!empty($$bresn)) $brec .= "2 RESN ".$$bresn."\r\n";
				$spouserec .= $brec;
				EditFunctions::ReplaceGedrec($SPID[$i], "", $brec, "BIRT", $change_id, $change_type);
			}
			$spouserec .= "\r\n1 FAMS @$famid@\r\n";
			EditFunctions::ReplaceGedrec($SPID[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
//			$SPID[$i] = append_gedrec($spouserec);
//			$SPID[$i] = EditFunctions::AppendGedrec($spouserec, "INDI", $change_id, $change_type);
			$change["INDI"] = true;
		}
		
		if (!empty($SPID[$i]) && $spid!=$SPID[$i]) {
			if (strstr($famrec, "1 $tag")!==false) {
//				$ct = preg_match("/1 $tag @(.*)@/", $famrec, $match);
//				$oldpid = $match[1];
//				print "Oldpid: ".$oldpid;
//				if (ChangeFunctions::GetChangeData(true, $spid, true)) {
//					$rec = ChangeFunctions::GetChangeData(false, $spid, true, "gedlines");
//					$oldgedrec = $rec[GedcomConfig::$GEDCOMID][$spid];
//				}
//				else $oldgedrec = FindGedcomRecord($oldpid);
//				$oldfam = GetSubRecord(1, "1 FAMS", $oldgedrec);
//				print "Oldfam: ".$oldfam;
				$updated = EditFunctions::ReplaceGedrec($spid, "1 FAMS @$famid@", "", "FAMS", $change_id, $change_type);
				$updated = EditFunctions::ReplaceGedrec($SPID[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
				$famrec = preg_replace("/1 $tag @.*@/", "1 $tag @$SPID[$i]@", $famrec);
				$updated = EditFunctions::ReplaceGedrec($famid, "1 $tag @$spid@", "1 $tag @$SPID[$i]@", "FAM", $change_id, $change_type);
			}
			else {
				$famrec .= "\r\n1 $tag @$SPID[$i]@";
				$famupdate = EditFunctions::ReplaceGedrec($famid, "", "1 $tag @$SPID[$i]@", "FAM", $change_id, $change_type);
			}
//			print "sfamupdate1";
		}
		
		//-- check for updated facts
		$var = "F".$i."TAGS";
		if (!empty($$var)) $TAGS = $$var;
		else $TAGS = array();
		if (count($TAGS)>0) {
			// The famid may still be REF, replace this with the actual famid
			$famrec = preg_replace("/@REF@/","@".$famid."@", $famrec);
			$famupdate |= check_updated_facts($i, $famrec, $TAGS, "F");
		}
		
		//-- check for new fact
		$var = "F".$i."newfact";
		if (!empty($$var)) $newfact = $$var;
		else $newfact = "";
		if (!empty($newfact)) {
			if (!in_array($newfact, $typefacts)) $factrec = "1 $newfact\r\n";
			else $factrec = "1 EVEN\r\n2 TYPE $newfact\r\n";
			$var = "F".$i."DATE";
			if (!empty($$var)) $FDATE = $$var;
			else $FDATE = "";
			if (!empty($FDATE)) {
				$FDATE = EditFunctions::CheckInputDate($FDATE);
				$factrec .= "2 DATE $FDATE\r\n";
			}
			$var = "F".$i."PLAC";
			if (!empty($$var)) $FPLAC = $$var;
			else $FPLAC = "";
			if (!empty($FPLAC)) $factrec .= "2 PLAC $FPLAC\r\n";
			$var = "F".$i."TEMPS";
			if (!empty($$var)) $FTEMP = $$var;
			else $FTEMP = "";
			if (!empty($FTEMP)) $factrec .= "2 TEMP $FTEMP\r\n";
			$var = "F".$i."RESN";
			if (!empty($$var)) $FRESN = $$var;
			else $FRESN = "";
			if (!empty($FRESN)) $factrec .= "2 RESN $FRESN\r\n";
			//-- make sure that there is at least a Y
			if (preg_match("/\n2 \w*/", $factrec)==0) $factrec = "1 $newfact Y\r\n";
			$famrec .= "\r\n".$factrec;
			$famupdate = EditFunctions::ReplaceGedrec($famid, "", $factrec, $newfact, $change_id, $change_type);
//			print "sfamupdate4";
		}
		
		if (!empty($CHIL[$i])) {
			$famupdate = true;
//			print "sfamupdate5";
			$famrec .= "\r\n1 CHIL @".$CHIL[$i]."@";
			$famupdate = EditFunctions::ReplaceGedrec($famid, "", "1 CHIL @".$CHIL[$i]."@", "CHIL", $change_id, $change_type);
			if (ChangeFunctions::GetChangeData(true, $CHIL[$i], true)) {
					$rec = ChangeFunctions::GetChangeData(false, $CHIL[$i], true, "gedlines");
					$childrec = $rec[GedcomConfig::$GEDCOMID][$CHIL[$i]];
				}
			else $childrec = FindGedcomRecord($CHIL[$i]);
			if (preg_match("/1 FAMC @$famid@/", $childrec)==0) {
				$childrec .= "\r\n1 FAMC @$famid@";
				$famupdate = EditFunctions::ReplaceGedrec($CHIL[$i], "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			}
		}
		
		$var = "F".$i."CDEL";
		if (!empty($$var)) $fcdel = $$var;
		else $fcdel = "";
		if (!empty($fcdel)) {
			$famrec = preg_replace("/1 CHIL @$fcdel@/", "", $famrec);
			$famupdate = EditFunctions::ReplaceGedrec($famid, "1 CHIL @$fcdel@", "", "CHIL", $change_id, $change_type);
//			print "sfamupdate6";
		}
		
		//--add new child, name, birth
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
			if (!empty($cgivn)) $childrec .= "2 GIVN $cgivn\r\n";
			if (!empty($csurn)) $childrec .= "2 SURN $csurn\r\n";
			$hsgivn = "HC{$i}GIVN";
			$hssurn = "HC{$i}SURN";
			if (!empty($$hsgivn) || !empty($$hssurn)) {
				$childrec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
			}
			$rsgivn = "RC{$i}GIVN";
			$rssurn = "RC{$i}SURN";
			if (!empty($$rsgivn) || !empty($$rssurn)) {
				$childrec .= "2 ROMN ".$$rsgivn." /".$$rssurn."/\r\n";
			}
			$cxref = EditFunctions::AppendGedrec($childrec, "INDI", $change_id, $change_type);
			$var = "C".$i."SEX";
			$csex = "";
			if (!empty($$var)) $csex = $$var;
			if (!empty($csex)) {
				$childrec .= "1 SEX $csex\r\n";
				EditFunctions::ReplaceGedrec($cxref, "", "1 SEX $csex", "SEX", $change_id, $change_type);
			}
			$var = "C".$i."DATE";
			$cdate = "";
			if (!empty($$var)) $cdate = $$var;
			$var = "C".$i."PLAC";
			$cplac = "";
			if (!empty($$var)) $cplac = $$var;
			if (!empty($cdate)||!empty($cplac)) {
				$brec = "1 BIRT\r\n";
				$cdate = EditFunctions::CheckInputDate($cdate);
				if (!empty($cdate)) $brec .= "2 DATE $cdate\r\n";
				if (!empty($cplac)) $brec .= "2 PLAC $cplac\r\n";
				$var = "C".$i."RESN";
				$cresn = "";
				if (!empty($$var)) $cresn = $$var;
				if (!empty($cresn)) $brec .= "2 RESN $cresn\r\n";
				$famupdate = EditFunctions::ReplaceGedrec($cxref, "", $brec, "BIRT", $change_id, $change_type);
				$childrec .= $brec;
			}
//			$cxref = EditFunctions::AppendGedrec($childrec, "INDI", $change_id, $change_type);
			$childrec .= "1 FAMC @$famid@\r\n";
			$famupdate = EditFunctions::ReplaceGedrec($cxref, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			$famrec .= "\r\n1 CHIL @$cxref@";
			$famupdate = EditFunctions::ReplaceGedrec($famid, "", "1 CHIL @$cxref@", "CHIL", $change_id, $change_type);
//			print "sfamupdate7";
		}
		
//		if ($famupdate && ($famrec!=$oldfamrec)) replace_gedrec($famid, $famrec);
	}

	//--add new spouse name, birth, marriage
	if (!empty($SGIVN) || !empty($SSURN)) {
		//-- first add the new spouse
		$spouserec = "0 @REF@ INDI\r\n";
		$spouserec .= "1 NAME $SGIVN /$SSURN/\r\n";
		if (!empty($SGIVN)) $spouserec .= "2 GIVN $SGIVN\r\n";
		if (!empty($SSURN)) $spouserec .= "2 SURN $SSURN\r\n";
		$xref = EditFunctions::AppendGedrec($spouserec, "INDI", $change_id, $change_type);
		if (!empty($SSEX)) {
			$spouserec .= "1 SEX $SSEX\r\n";
			EditFunctions::ReplaceGedrec($xref, "", "1 SEX $SSEX", "SEX", $change_id, $change_type);
		}
		if (!empty($BDATE)||!empty($BPLAC)) {
			$brec = "1 BIRT\r\n";
			if (!empty($BDATE)) $brec .= "2 DATE $BDATE\r\n";
			if (!empty($BPLAC)) $brec .= "2 PLAC $BPLAC\r\n";
			if (!empty($BRESN)) $brec .= "2 RESN $BRESN\r\n";
			$spouserec .= $brec;
			EditFunctions::ReplaceGedrec($xref, "", $brec, "BIRT", $change_id, $change_type);
		}

		//-- next add the new family record
		$famrec = "0 @REF@ FAM\r\n";
		if ($SSEX=="M") $famrec .= "1 HUSB @$xref@\r\n1 WIFE @$pid@\r\n";
		else $famrec .= "1 HUSB @$pid@\r\n1 WIFE @$xref@\r\n";
		$newfamid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);

		//-- add the new family id to the new spouse record
		$rec = ChangeFunctions::GetChangeData(false, $xref, true, "gedlines");
		$spouserec = $rec[GedcomConfig::$GEDCOMID][$xref];
//		$spouserec = find_record_in_file($xref);
		$spouserec .= "\r\n1 FAMS @$newfamid@\r\n";
		EditFunctions::ReplaceGedrec($xref, "", "1 FAMS @$newfamid@", "FAMS", $change_id, $change_type);
		
		//-- last add the new family id to the persons record
		$gedrec .= "\r\n1 FAMS @$newfamid@\r\n";
		$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMS @$newfamid@", "FAMS", $change_id, $change_type);

	}
	if (!empty($MDATE)||!empty($MPLAC)) {
		if (empty($newfamid)) {
			$famrec = "0 @REF@ FAM\r\n";
			if (preg_match("/1 SEX M/", $gedrec)>0) $famrec .= "1 HUSB @$pid@\r\n";
			else $famrec .= "1 WIFE @$pid@";
			$newfamid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
			$gedrec .= "\r\n1 FAMS @$newfamid@";
			$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMS @$newfamid@", "FAMS", $change_id, $change_type);
		}
		$factrec = "1 MARR\r\n";
		$MDATE = EditFunctions::CheckInputDate($MDATE);
		if (!empty($MDATE)) $factrec .= "2 DATE $MDATE\r\n";
		if (!empty($MPLAC)) $factrec .= "2 PLAC $MPLAC\r\n";
		if (!empty($MRESN)) $factrec .= "2 RESN $MRESN\r\n";
		$famrec .= "\r\n".$factrec;
		$updated = EditFunctions::ReplaceGedrec($newfamid, "", $factrec, "MARR", $change_id, $change_type);
	}

	//--add new child, name, birth
	if (!empty($CGIVN) || !empty($CSURN)) {
		//-- first add the new child
		$childrec = "0 @REF@ INDI\r\n";
		$childrec .= "1 NAME $CGIVN /$CSURN/\r\n";
		if (!empty($CGIVN)) $childrec .= "2 GIVN $CGIVN\r\n";
		if (!empty($CSURN)) $childrec .= "2 SURN $CSURN\r\n";
		if (!empty($HCGIVN) || !empty($HCSURN)) {
			$childrec .= "2 _HEB $HCGIVN /$HCSURN/\r\n";
		}
		$cxref = EditFunctions::AppendGedrec($childrec, "INDI", $change_id, $change_type);
		if (!empty($CSEX)) {
			$childrec .= "1 SEX $CSEX\r\n";
			EditFunctions::ReplaceGedrec($cxref, "", "1 SEX $CSEX", "SEX", $change_id, $change_type);
		}
		if (!empty($CDATE)||!empty($CPLAC)) {
			$brec = "1 BIRT\r\n";
			$CDATE = EditFunctions::CheckInputDate($CDATE);
			if (!empty($CDATE)) $brec .= "2 DATE $CDATE\r\n";
			if (!empty($CPLAC)) $brec .= "2 PLAC $CPLAC\r\n";
			if (!empty($CRESN)) $brec .= "2 RESN $CRESN\r\n";
			$famupdate = EditFunctions::ReplaceGedrec($cxref, "", $brec, "BIRT", $change_id, $change_type);
			$childrec .= $brec;
		}

		//-- if a new family was already made by adding a spouse or a marriage
		//-- then use that id, otherwise create a new family
		if (empty($newfamid)) {
			$famrec = "0 @REF@ FAM\r\n";
			if (preg_match("/1 SEX M/", $gedrec)>0) $famrec .= "1 HUSB @$pid@\r\n";
			else $famrec .= "1 WIFE @$pid@\r\n";
			$famrec .= "1 CHIL @$cxref@\r\n";
			$newfamid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
			
			//-- add the new family to the new child
			$rec = ChangeFunctions::GetChangeData(false, $cxref, true, "gedlines");
			$childrec = $rec[GedcomConfig::$GEDCOMID][$cxref];
//			$childrec = find_record_in_file($cxref);
			$childrec .= "\r\n1 FAMC @$newfamid@\r\n";
			EditFunctions::ReplaceGedrec($cxref, "", "1 FAMC @$newfamid@", "FAMC", $change_id, $change_type);
			
			//-- add the new family to the original person
			$gedrec .= "\r\n1 FAMS @$newfamid@";
			$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMS @$newfamid@", "FAMS", $change_id, $change_type);
		}
		else {
			$famrec .= "\r\n1 CHIL @$cxref@\r\n";
			$updated = EditFunctions::ReplaceGedrec($newfamid, "", "1 CHIL @$cxref@", "CHIL", $change_id, $change_type);
			
			//-- add the family to the new child
			$rec = ChangeFunctions::GetChangeData(false, $cxref, true, "gedlines");
			$childrec = $rec[GedcomConfig::$GEDCOMID][$cxref];
//			$childrec = find_record_in_file($cxref);
			$childrec .= "\r\n1 FAMC @$newfamid@\r\n";
//			replace_gedrec($cxref, $childrec);
			EditFunctions::ReplaceGedrec($cxref, "", "1 FAMC @$newfamid@", "FAMC", $change_id, $change_type);
		}
		print GM_LANG_update_successful."<br />\n";;
	}
//	if (!empty($newfamid)) {
//		$famrec = preg_replace("/0 @(.*)@/", "0 @".$newfamid."@", $famrec);
//		replace_gedrec($newfamid, $famrec);
//	}
	
	//------------------------------------------- updates for family with parents
	$cfams = FindFamilyIds($pid, $gedrec, true);
	if (count($cfams)==0) $cfams[] = array("famid"=>"","primary"=>"","relation"=>"");
	$i++;
	for($j=1; $j<=count($cfams); $j++) {
		$famid = $cfams[$j-1]["famid"];
//		print $famid;
		$famupdate = false;
		if (!empty($famid)) {
			if (ChangeFunctions::GetChangeData(true, $famid, true)) {
				$rec = ChangeFunctions::GetChangeData(false, $famid, true, "gedlines");
				$famrec = $rec[GedcomConfig::$GEDCOMID][$famid];
			}
			else $famrec = FindFamilyRecord($famid);
			$oldfamrec = $famrec;
		}
		else {
			$famrec = "0 @REF@ FAM\r\n1 CHIL @$pid@";
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
				if (!empty($$sgivn)) $spouserec .= "2 GIVN ".$$sgivn."\r\n";
				if (!empty($$ssurn)) $spouserec .= "2 SURN ".$$ssurn."\r\n";
				$hsgivn = "HFGIVN$i";
				$hssurn = "HFSURN$i";
				if (!empty($$hsgivn) || !empty($$hssurn)) {
					$spouserec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
				}
				$rsgivn = "RFGIVN$i";
				$rssurn = "RFSURN$i";
				if (!empty($$rsgivn) || !empty($$rssurn)) {
					$spouserec .= "2 ROMN ".$$rsgivn." /".$$rssurn."/\r\n";
				}
				$FATHER[$i] = EditFunctions::AppendGedrec($spouserec, "INDI", $change_id, $change_type);
				$ssex = "FSEX$i";
				if (!empty($$ssex)) {
					$spouserec .= "1 SEX ".$$ssex."\r\n";
					EditFunctions::ReplaceGedrec($FATHER[$i], "", "1 SEX ".$$ssex, "SEX", $change_id, $change_type);
				}
				$bdate = "FBDATE$i";
				$bplac = "FBPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$brec = "1 BIRT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = EditFunctions::CheckInputDate($bdate);
					if (!empty($bdate)) $brec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $brec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "FBRESN$i";
					if (!empty($$bresn)) $brec .= "2 RESN ".$$bresn."\r\n";
					EditFunctions::ReplaceGedrec($FATHER[$i], "", $brec, "BIRT", $change_id, $change_type);
					$spouserec .= $brec;
				}
				$bdate = "FDDATE$i";
				$bplac = "FDPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$drec = "1 DEAT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = EditFunctions::CheckInputDate($bdate);
					if (!empty($bdate)) $drec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $drec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "FDRESN$i";
					if (!empty($$bresn)) $drec .= "2 RESN ".$$bresn."\r\n";
					EditFunctions::ReplaceGedrec($FATHER[$i], "", $drec, "DEAT", $change_id, $change_type);
					$spouserec .= $drec;
				}
				if (empty($famid)) {
					//print "HERE 1";
					$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
					//print "<pre>$famrec</pre>";
					$gedrec .= "\r\n1 FAMC @$famid@\r\n";
					$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
				}
				$spouserec .= "\r\n1 FAMS @$famid@\r\n";
				EditFunctions::ReplaceGedrec($FATHER[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
			}
		}
		else {
			if (empty($famid)) {
				//print "HERE 2";
				$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
				$gedrec .= "\r\n1 FAMC @$famid@\r\n";
				$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			}
			if (empty($oldfamrec)) {
				if (ChangeFunctions::GetChangeData(true, $FATHER[$i], true)) {
					$rec = ChangeFunctions::GetChangeData(false, $FATHER[$i], true, "gedlines");
					$spouserec = $rec[GedcomConfig::$GEDCOMID][$FATHER[$i]];
				}
				else $spouserec = FindGedcomRecord($FATHER[$i]);
//				$spouserec = find_record_in_file($FATHER[$i]);
				$spouserec .= "\r\n1 FAMS @$famid@";
				EditFunctions::ReplaceGedrec($FATHER[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
			}
		}
		
		$parents = FindParentsInRecord($famrec);
		if (!empty($FATHER[$i]) && $parents['HUSB']!=$FATHER[$i]) {
//			if (strstr($famrec, "1 HUSB")!==false) $famrec = preg_replace("/1 HUSB @.*@/", "1 HUSB @$FATHER[$i]@", $famrec);
			if (preg_match("/1 HUSB @.*@/", $famrec, $match) != 0) {
				$famrec = preg_replace("/1 HUSB @.*@/", "1 HUSB @$FATHER[$i]@", $famrec);
				$famupdate = EditFunctions::ReplaceGedrec($famid, "1 HUSB @".$parents['HUSB']."@", "1 HUSB @".$FATHER[$i]."@", "FAM", $change_id, $change_type);
				EditFunctions::ReplaceGedrec($parents["HUSB"], "1 FAMS @$famid@", "", "FAMS", $change_id, $change_type);
			}
			else {
				$famrec .= "\r\n1 HUSB @$FATHER[$i]@";
				$famupdate = EditFunctions::ReplaceGedrec($famid, "", "1 HUSB @$FATHER[$i]@", "FAM", $change_id, $change_type);
			}
			if (!empty($oldfamrec)) EditFunctions::ReplaceGedrec($FATHER[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
//			print "famupdate1";
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
				if (!empty($$sgivn)) $spouserec .= "2 GIVN ".$$sgivn."\r\n";
				if (!empty($$ssurn)) $spouserec .= "2 SURN ".$$ssurn."\r\n";
				$hsgivn = "HMGIVN$i";
				$hssurn = "HMSURN$i";
				if (!empty($$hsgivn) || !empty($$hssurn)) {
					$spouserec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
				}
				$rsgivn = "RMGIVN$i";
				$rssurn = "RMSURN$i";
				if (!empty($$rsgivn) || !empty($$rssurn)) {
					$spouserec .= "2 ROMN ".$$rsgivn." /".$$rssurn."/\r\n";
				}
				$MOTHER[$i] = EditFunctions::AppendGedrec($spouserec, "INDI", $change_id, $change_type);
				$ssex = "MSEX$i";
				if (!empty($$ssex)) {
					$spouserec .= "1 SEX ".$$ssex."\r\n";
					EditFunctions::ReplaceGedrec($MOTHER[$i], "", "1 SEX ".$$ssex, "SEX", $change_id, $change_type);
				}
				$bdate = "MBDATE$i";
				$bplac = "MBPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$brec = "1 BIRT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = EditFunctions::CheckInputDate($bdate);
					if (!empty($bdate)) $brec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $brec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "MBRESN$i";
					if (!empty($$bresn)) $brec .= "2 RESN ".$$bresn."\r\n";
					EditFunctions::ReplaceGedrec($MOTHER[$i], "", $brec, "BIRT", $change_id, $change_type);
					$spouserec .= $brec;
				}
				$bdate = "MDDATE$i";
				$bplac = "MDPLAC$i";
				if (!empty($$bdate)||!empty($$bplac)) {
					$drec = "1 DEAT\r\n";
					if (!empty($$bdate)) $bdate = $$bdate;
					else $bdate = "";
					$bdate = EditFunctions::CheckInputDate($bdate);
					if (!empty($bdate)) $drec .= "2 DATE $bdate\r\n";
					if (!empty($$bplac)) $drec .= "2 PLAC ".$$bplac."\r\n";
					$bresn = "MDRESN$i";
					if (!empty($$bresn)) $drec .= "2 RESN ".$$bresn."\r\n";
					EditFunctions::ReplaceGedrec($MOTHER[$i], "", $drec, "DEAT", $change_id, $change_type);
					$spouserec .= $drec;
				}
				if (empty($famid)) {
					//print "HERE 3";
					$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
					$gedrec .= "\r\n1 FAMC @$famid@\r\n";
					$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
				}
				$spouserec .= "\r\n1 FAMS @$famid@\r\n";
				EditFunctions::ReplaceGedrec($MOTHER[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
			}
		}
		else {
			if (empty($famid)) {
// 				print "HERE 4";
				$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
				$gedrec .= "\r\n1 FAMC @$famid@\r\n";
				$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			}
			if (empty($oldfamrec)) {
				if (ChangeFunctions::GetChangeData(true, $MOTHER[$i], true)) {
					$rec = ChangeFunctions::GetChangeData(false, $MOTHER[$i], true, "gedlines");
					$spouserec = $rec[GedcomConfig::$GEDCOMID][$MOTHER[$i]];
				}
				else $spouse = FindGedcomRecord($MOTHER[$i]);
//				$spouserec = find_record_in_file($MOTHER[$i]);
				$spouserec .= "\r\n1 FAMS @$famid@";
				EditFunctions::ReplaceGedrec($MOTHER[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
			}
		}
		if (!empty($MOTHER[$i]) && $parents['WIFE']!=$MOTHER[$i]) {
//			if (strstr($famrec, "1 WIFE")!==false) $famrec = preg_replace("/1 WIFE @.*@/", "1 WIFE @$MOTHER[$i]@", $famrec);
			if (preg_match("/1 WIFE @.*@/", $famrec, $match) != 0) {
				$famrec = preg_replace("/1 WIFE @.*@/", "1 WIFE @$MOTHER[$i]@", $famrec);
				$famupdate = EditFunctions::ReplaceGedrec($famid, "1 WIFE @".$parents['WIFE']."@", "1 WIFE @".$MOTHER[$i]."@", "FAM", $change_id, $change_type);
				EditFunctions::ReplaceGedrec($parents["WIFE"], "1 FAMS @$famid@", "", "FAMS", $change_id, $change_type);
			}
			else {
				$famrec .= "\r\n1 WIFE @$MOTHER[$i]@";
				$famupdate = EditFunctions::ReplaceGedrec($famid, "", "1 WIFE @$MOTHER[$i]@", "FAM", $change_id, $change_type);
			}
			if (!empty($oldfamrec)) EditFunctions::ReplaceGedrec($MOTHER[$i], "", "1 FAMS @$famid@", "FAMS", $change_id, $change_type);
//			print "famupdate2";
		}
		
		//-- check for updated facts
		$var = "F".$i."TAGS";
		if (!empty($$var)) $TAGS = $$var;
		else $TAGS = array();
		if (count($TAGS)>0) {
			// The famid may still be REF, replace this with the actual famid
			$famrec = preg_replace("/@REF@/","@".$famid."@", $famrec);
			$famupdate |= check_updated_facts($i, $famrec, $TAGS, "F");
		}
		
		//-- check for new fact
		$var = "F".$i."newfact";
		$newfact = "";
		if (isset($$var)) $newfact = $$var;
		if (!empty($newfact)) {
			if (empty($famid)) {
				//print "HERE 6";
				$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
				$gedrec .= "\r\n1 FAMC @$famid@\r\n";
				$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			}
			if (!in_array($newfact, $typefacts)) $factrec = "1 $newfact\r\n";
			else $factrec = "1 EVEN\r\n2 TYPE $newfact\r\n";
			$var = "F".$i."DATE";
			if (!empty($$var)) $FDATE = $$var;
			else $FDATE = "";
			$FDATE = EditFunctions::CheckInputDate($FDATE);
			if (!empty($FDATE)) $factrec .= "2 DATE $FDATE\r\n";
			$var = "F".$i."PLAC";
			if (!empty($$var)) $FPLAC = $$var;
			else $FPLAC = "";
			if (!empty($FPLAC)) $factrec .= "2 PLAC $FPLAC\r\n";
			$var = "F".$i."TEMPS";
			if (!empty($$var)) $FTEMP = $$var;
			else $FTEMP = "";
			if (!empty($FTEMP)) $factrec .= "2 TEMP $FTEMP\r\n";
			$var = "F".$i."RESN";
			if (!empty($$var)) $FRESN = $$var;
			else $FRESN = "";
			if (!empty($FRESN)) $factrec .= "2 RESN $FRESN\r\n";
			//-- make sure that there is at least a Y
			if (preg_match("/\n2 \w*/", $factrec)==0) $factrec = "1 $newfact Y\r\n";
			$famrec .= "\r\n".$factrec;
			$famupdate = EditFunctions::ReplaceGedrec($famid, "", $factrec, $newfact, $change_id, $change_type);
//			print "famupdate5";
		}
		if (!empty($CHIL[$i])) {
			if (empty($famid)) {
				//print "HERE 7";
				$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
				$gedrec .= "\r\n1 FAMC @$famid@\r\n";
				$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			}
			$famrec .= "\r\n1 CHIL @".$CHIL[$i]."@";
			if (ChangeFunctions::GetChangeData(true, $CHIL[$i], true)) {
					$rec = ChangeFunctions::GetChangeData(false, $CHIL[$i], true, "gedlines");
					$childrec = $rec[GedcomConfig::$GEDCOMID][$CHIL[$i]];
				}
			else $childrec = FindGedcomRecord($CHIL[$i]);
//			$childrec = find_record_in_file($CHIL[$i]);
			if (preg_match("/1 FAMC @$famid@/", $childrec)==0) {
				$childrec = "\r\n1 FAMC @$famid@";
				EditFunctions::ReplaceGedrec($CHIL[$i], "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			}
			$famupdate = EditFunctions::ReplaceGedrec($famid, "", "1 CHIL @".$CHIL[$i]."@", "CHIL", $change_id, $change_type);
//			print "famupdate6";
		}

		// Process PEDI tag
		$var = "F".$i."CPEDI";
		if (isset($$var)) {
			$pedifamrec = GetSubRecord(1, "1 FAMC @$famid@", $gedrec);
			$oldpedifamrec = $pedifamrec;
			$pedival = GetGedcomValue("PEDI", 2, $pedifamrec);
			$pedirec = GetSubRecord(2, "2 PEDI", $pedifamrec);
			if ($$var == "birth") $$var = "";
			if ($$var != $pedival) {
				if (empty($$var)) $pedifamrec = preg_replace("/$pedirec/", "", $pedifamrec);
				else if(empty($pedirec)) $pedifamrec .= "\r\n2 PEDI ".$$var;
				else $pedifamrec = preg_replace("/2 PEDI.*/", "2 PEDI ".$$var, $pedifamrec);
				$famupdate = EditFunctions::ReplaceGedrec($pid, $oldpedifamrec, $pedifamrec, "FAMC", $change_id, $change_type);
			}
		}
			
				
		$var = "F".$i."CDEL";
		if (!empty($$var)) $fcdel = $$var;
		else $fcdel = "";
		if (!empty($fcdel)) {
			$famrec = preg_replace("/1 CHIL @$fcdel@/", "", $famrec);
			$famupdate = EditFunctions::ReplaceGedrec($famid, "1 CHIL @".$fcdel."@", "", "CHIL", $change_id, $change_type);
			EditFunctions::ReplaceGedrec($fcdel, "1 CHIL @".$famid."@", "", "FAMC", $change_id, $change_type);
//			print "famupdate7";
		}
		
		//--add new child, name, birth
		$cgivn = "C".$i."GIVN";
		$csurn = "C".$i."SURN";
		if (!empty($$cgivn) || !empty($$csurn)) {
			if (empty($famid)) {
				//print "HERE 8";
				$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
				$gedrec .= "\r\n1 FAMC @$famid@\r\n";
				$updated = EditFunctions::ReplaceGedrec($pid, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
			}
			//-- first add the new child
			$childrec = "0 @REF@ INDI\r\n";
			$childrec .= "1 NAME ".$$cgivn." /".$$csurn."/\r\n";
			if (!empty($$cgivn)) $childrec .= "2 GIVN ".$$cgivn."\r\n";
			if (!empty($$csurn)) $childrec .= "2 SURN ".$$csurn."\r\n";
			$hsgivn = "HC{$i}GIVN";
			$hssurn = "HC{$i}SURN";
			if (!empty($$hsgivn) || !empty($$hssurn)) {
				$childrec .= "2 _HEB ".$$hsgivn." /".$$hssurn."/\r\n";
			}
			$rsgivn = "RC{$i}GIVN";
			$rssurn = "RC{$i}SURN";
			if (!empty($$rsgivn) || !empty($$rssurn)) {
				$childrec .= "2 ROMN ".$$rsgivn." /".$$rssurn."/\r\n";
			}
			$cxref = EditFunctions::AppendGedrec($childrec, "INDI", $change_id, $change_type);
			$var = "C".$i."SEX";
			if (!empty($$var)) $csex = $$var;
			else $csex = "";
			if (!empty($csex)) {
				$childrec .= "1 SEX $csex\r\n";
				EditFunctions::ReplaceGedrec($cxref, "", "1 SEX $csex", "SEX", $change_id, $change_type);
			}
			$var = "C".$i."DATE";
			if (!empty($$var)) $cdate = $$var;
			else $cdate = "";
			$var = "C".$i."PLAC";
			if (!empty($$var)) $cplac = $$var;
			else $cplac = "";
			if (!empty($cdate)||!empty($cplac)) {
				$brec = "1 BIRT\r\n";
				$cdate = EditFunctions::CheckInputDate($cdate);
				if (!empty($cdate)) $brec .= "2 DATE $cdate\r\n";
				if (!empty($cplac)) $brec .= "2 PLAC $cplac\r\n";
				$var = "C".$i."RESN";
				if (!empty($$var)) $cresn = $$var;
				else $cresn = "";
				if (!empty($cresn)) $brec .= "2 RESN $cresn\r\n";
				$famupdate = EditFunctions::ReplaceGedrec($cxref, "", $brec, "BIRT", $change_id, $change_type);
				$childrec .= $brec;
			}
			$childrec .= "1 FAMC @$famid@\r\n";
			EditFunctions::ReplaceGedrec($cxref, "", "1 FAMC @$famid@", "FAMC", $change_id, $change_type);
//			$cxref = append_gedrec($childrec);
//			$cxref = EditFunctions::AppendGedrec($childrec, "INDI", $change_id, $change_type);
			$famrec .= "\r\n1 CHIL @$cxref@";
			$famupdate = EditFunctions::ReplaceGedrec($famid, "", "1 CHIL @$cxref@", "CHIL", $change_id, $change_type);
//			print "famupdate8";
		}
//		if ($famupdate &&($oldfamrec!=$famrec)) {
//			$famrec = preg_replace("/0 @(.*)@/", "0 @".$famid."@", $famrec);
////			print $famrec;
//			replace_gedrec($famid, $famrec);
//		}
		$i++;
	}

	if ($updated && empty($error)) {
		print GM_LANG_update_successful."<br />";
//		AddToChangeLog("Quick update for $pid by >".getUserName()."<");
		//print "<pre>$gedrec</pre>";
//		if ($oldgedrec!=$gedrec) replace_gedrec($pid, $gedrec);
	}
	if (!empty($error)) {
		print "<span class=\"error\">".$error."</span>";
	}
		if ($can_auto_accept && ($gm_user->UserCanAccept() || $gm_user->userAutoAccept())) {
		ChangeFunctions::AcceptChange($change_id, GedcomConfig::$GEDCOMID);
	}

	if ($closewin) {
		// autoclose window when update successful
		if (GedcomConfig::$EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nif (window.opener.showchanges) window.opener.showchanges(); window.close();\n//-->\n</script>";
		
		print "<center><br /><br /><br />";
		print "<a href=\"#\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".GM_LANG_close_window."</a><br /></center>\n";
		PrintSimpleFooter();
		exit;
	}
}

if ($action!="update") print "<h3>".GM_LANG_quick_update_title."</h3>\n";
print GM_LANG_quick_update_instructions."<br /><br />";

InitCalendarPopUp();
?>
<script language="JavaScript" type="text/javascript">
<!--
var pastefield;
function paste_id(value) {
	pastefield.value = value;
	pastefield.focus();
}

var helpWin;
function helpPopup(which) {
	if ((!helpWin)||(helpWin.closed)) helpWin = window.open('help_text.php?help='+which,'_blank','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
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
		<td><?php print GM_LANG_enter_pid; ?></td>
		<td><input type="text" size="6" name="pid" id="pid" />
		<?php LinkFunctions::PrintFindIndiLink("pid","");?>
                </td>
	</tr>
	</table>
	<input type="submit" value="<?php print GM_LANG_continue; ?>" />
	</form>
		<?php
	}
	else {
		$SEX = GetGedcomValue("SEX", 1, $gedrec, '', false);
		$child_surname = "";
		//if ($SEX=="M") {
		//	$ct = preg_match("~1 NAME.*/(.*)/~", $gedrec, $match);
		//	if ($ct>0) $child_surname = $match[1];
		//}
	$GIVN = "";
	$SURN = "";
	$subrec = GetSubRecord(1, "1 NAME", $gedrec);
	$namerec = $subrec;
	if (!empty($subrec) && !PrivacyFunctions::FactEditRestricted($pid, $subrec)) {
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
		$RGIVN = "";
		$RSURN = "";
		if (GedcomConfig::$USE_RTL_FUNCTIONS) {
			$hname = GetGedcomValue("_HEB", 2, $subrec, '', false);
			if (!empty($hname)) {
				$ct = preg_match("~(.*)/(.*)/(.*)~", $hname, $matches);
				if ($ct>0) {
					$HSURN = $matches[2];
					$HGIVN = trim($matches[1]).trim($matches[3]);
				}
				else $HGIVN = $hname;
			}
		}
		$rname = GetGedcomValue("ROMN", 2, $subrec, '', false);
		if (!empty($rname)) {
			$ct = preg_match("~(.*)/(.*)/(.*)~", $rname, $matches);
			if ($ct>0) {
				$RSURN = $matches[2];
				$RGIVN = trim($matches[1]).trim($matches[3]);
			}
			else $RGIVN = $rname;
		}
	}
	$ADDR = "";
	$subrec = GetSubRecord(1, "1 ADDR", $gedrec);
	if (!empty($subrec) && !PrivacyFunctions::FactEditRestricted($pid, $subrec)) {
		$ct = preg_match("/1 ADDR (.*)/", $subrec, $match);
		if ($ct>0) $ADDR = trim($match[1]);
		$ADDR_CONT = GetCont(2, $subrec);
		if (!empty($ADDR_CONT)) $ADDR .= $ADDR_CONT;
		else {
			$_NAME = GetGedcomValue("_NAME", 2, $subrec);
			if (!empty($_NAME)) $ADDR .= "\r\n". check_NN($_NAME);
			$ADR1 = GetGedcomValue("ADR1", 2, $subrec);
			if (!empty($ADR1)) $ADDR .= "\r\n". $ADR1;
			$ADR2 = GetGedcomValue("ADR2", 2, $subrec);
			if (!empty($ADR2)) $ADDR .= "\r\n". $ADR2;
			$cityspace = "\r\n";
			if (!GedcomConfig::$POSTAL_CODE) {
				$POST = GetGedcomValue("POST", 2, $subrec);
				if (!empty($POST)) $ADDR .= "\r\n". $POST;
				else $ADDR .= "\r\n";
				$cityspace = " ";
			}
			$CITY = GetGedcomValue("CITY", 2, $subrec);
			if (!empty($CITY)) $ADDR .= $cityspace. $CITY;
			else $ADDR .= $cityspace;
			$STAE = GetGedcomValue("STAE", 2, $subrec);
			if (!empty($STAE)) $ADDR .= ", ". $STAE;
			if (GedcomConfig::$POSTAL_CODE) {
				$POST = GetGedcomValue("POST", 2, $subrec);
				if (!empty($POST)) $ADDR .= "  ". $POST;
			}
			$CTRY = GetGedcomValue("CTRY", 2, $subrec);
			if (!empty($CTRY)) $ADDR .= "\r\n". $CTRY;
		}
		/**
		 * @todo add support for ADDR subtags ADR1, CITY, STAE etc
		 */
	}
	$PHON = "";
	$subrec = GetSubRecord(1, "1 PHON", $gedrec);
	if (!empty($subrec) && !PrivacyFunctions::FactEditRestricted($pid, $subrec)) {
		$ct = preg_match("/1 PHON (.*)/", $subrec, $match);
		if ($ct>0) $PHON = trim($match[1]);
		$PHON .= GetCont(2, $subrec);
	}
	$EMAIL = "";
	$ct = preg_match("/1 (_?EMAIL) (.*)/", $gedrec, $match);
	if ($ct>0) {
		$subrec = GetSubRecord(1, "1 ".$match[1], $gedrec);
		if (!PrivacyFunctions::FactEditRestricted($pid, $subrec)) {
			$EMAIL = trim($match[2]);
			$EMAIL .= GetCont(2, $subrec);
		}
	}
	$FAX = "";
	$subrec = GetSubRecord(1, "1 FAX", $gedrec);
	if (!empty($subrec) && !PrivacyFunctions::FactEditRestricted($pid, $subrec)) {
			$ct = preg_match("/1 FAX (.*)/", $subrec, $match);
			if ($ct>0) $FAX = trim($match[1]);
			$FAX .= GetCont(2, $subrec);
	}
	
	$indifacts = array();
	$subrecords = GetAllSubrecords($gedrec, "ADDR,PHON,FAX,EMAIL,_EMAIL,NAME,FAMS,FAMC,_UID", false, false, false);
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
		if ($fact=="EVEN" || $fact=="FACT") $fact = GetGedcomValue("TYPE", 2, $subrec, '', false);
		if (in_array($fact, $addfacts)) {
			if (!isset($repeat_tags[$fact])) $repeat_tags[$fact]=1;
			else $repeat_tags[$fact]++;
			$newreqd = array();
			foreach($reqdfacts as $r=>$rfact) {
				if ($rfact!=$fact) $newreqd[] = $rfact;
			}
			$reqdfacts = $newreqd;
			if (!PrivacyFunctions::FactEditRestricted($pid, $subrec)) $indifacts[] = array($fact, $subrec, false, $repeat_tags[$fact]);
		}
	}
	foreach($reqdfacts as $ind=>$fact) {
		$indifacts[] = array($fact, "1 $fact\r\n", true, 0);
	}
//	usort($indifacts, "CompareFacts");
	SortFacts($indifacts);
	$sfams = FindSfamilyIds($pid, true);
//	$sfams = find_families_in_record($gedrec, "FAMS");
	$cfams = FindfamilyIds($pid, "", true);
//	$cfams = find_families_in_record($gedrec, "FAMC");
	if (count($cfams)==0) $cfams[] = array("famid"=>"","primary"=>"","relation"=>"");
		
	$tabkey = 1;
	$name = PrintReady(GetPersonName($pid, $namerec));
	print "<b>".$name;
	if (GedcomConfig::$SHOW_ID_NUMBERS) print "&nbsp;&nbsp;(".$pid.")";
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

function checkform(frm) {
	if (frm.EMAIL) {
		if ((frm.EMAIL.value!="") && 
			((frm.EMAIL.value.indexOf("@")==-1) || 
			(frm.EMAIL.value.indexOf("<")!=-1) ||
			(frm.EMAIL.value.indexOf(">")!=-1))) {
			alert("<?php print GM_LANG_enter_email; ?>");
			frm.EMAIL.focus();
			return false;
		} 
	}
	return true;
}
//-->
</script>
<form method="post" action="edit_quickupdate.php?pid=<?php print $pid;?>" name="quickupdate" enctype="multipart/form-data" onsubmit="return checkform(this);">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="change_type" value="quick_update" />
<input type="hidden" name="closewin" value="1" />
<br /><input type="submit" value="<?php print GM_LANG_save; ?>" /><br /><br />
<table class="tabs_table">
   <tr>
		<td id="pagetab0" class="tab_cell_active"><a href="javascript: <?php print GM_LANG_personal_facts;?>" onclick="switch_tab(0); return false;"><?php print GM_LANG_personal_facts?></a></td>
		<?php
		for($i=1; $i<=count($sfams); $i++) {
			$famid = $sfams[$i-1]["famid"];
			$famrec = FindFamilyRecord($famid);
			if (ChangeFunctions::GetChangeData(true, $famid, true)) {
				$rec = ChangeFunctions::GetChangeData(false, $famid, true, "gedlines");
				$famrec = $rec[GedcomConfig::$GEDCOMID][$famid];
			}
			$parents = FindParentsInRecord($famrec);
			$spid = "";
			if($parents) {
				if($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
				else $spid=$parents["WIFE"];
			}
			$parrec = FindGedcomRecord($spid);
			if (!empty($spid) && ChangeFunctions::GetChangeData(true, $spid, true)) {
				$rec = ChangeFunctions::GetChangeData(false, $spid, true, "gedlines");
				$parrec = $rec[GedcomConfig::$GEDCOMID][$spid];
			}
						
			print "<td id=\"pagetab$i\" class=\"tab_cell_inactive\" onclick=\"switch_tab($i); return false;\"><a href=\"javascript: ".GM_LANG_family_with."&nbsp;";
			if (!empty($spid)) {
				if (PrivacyFunctions::displayDetailsById($spid) && PrivacyFunctions::showLivingNameById($spid)) {
					print PrintReady(str_replace(array("<span class=\"starredname\">", "</span>"), "", GetPersonName($spid, $parrec)));
					print "\" onclick=\"switch_tab($i); return false;\">".GM_LANG_family_with." ";
					print PrintReady(GetPersonName($spid, $parrec));
				}
				else {
					print GM_LANG_private;
					print "\" onclick=\"switch_tab($i); return false;\">".GM_LANG_family_with." ";
					print GM_LANG_private;
				}
			}
			else print "\" onclick=\"switch_tab($i); return false;\">".GM_LANG_family_with." ".GM_LANG_unknown;
			print "</a></td>\n";
		}
		?>
		<td id="pagetab<?php echo $i; ?>" class="tab_cell_inactive" onclick="switch_tab(<?php echo $i; ?>); return false;"><a href="javascript: <?php print GM_LANG_add_new_wife;?>" onclick="switch_tab(<?php echo $i; ?>); return false;">
		<?php if (preg_match("/1 SEX M/", $gedrec)>0) print GM_LANG_add_new_wife; else print GM_LANG_add_new_husb; ?></a></td>
		<?php
		$i++;
		for($j=1; $j<=count($cfams); $j++) {
			print "<td id=\"pagetab$i\" class=\"tab_cell_inactive\" onclick=\"switch_tab($i); return false;\"><a href=\"#\" onclick=\"switch_tab($i); return false;\">".GM_LANG_as_child;
			print "</a></td>\n";
			$i++;
		}
		?>
		</tr>
		<tr>
		  <td id="pagetab0bottom" class="tab_active_bottom"><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]; ?>" width="1" height="1" alt="" /></td>
		  <?php
		for($i=1; $i<=count($sfams); $i++) {
			print "<td id=\"pagetab{$i}bottom\" class=\"tab_inactive_bottom\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"1\" height=\"1\" alt=\"\"/></td>\n";
		}
		for($j=1; $j<=count($cfams); $j++) {
			print "<td id=\"pagetab{$i}bottom\" class=\"tab_inactive_bottom\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"1\" height=\"1\" alt=\"\" /></td>\n";
			$i++;
		}
		?>
			<td id="pagetab<?php echo $i; ?>bottom" class="tab_inactive_bottom"><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]; ?>" width="1" height="1" alt="" /></td>
			<td class="tab_inactive_bottom_right" style="width:10%;"><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]; ?>" width="1" height="1" alt="" /></td>
   </tr>
</table>
<div id="tab0">
<table class="<?php print $TEXT_DIRECTION; ?> width80">
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_name_help", "qm"); ?><?php print GM_LANG_update_name; ?></td></tr>
<tr><td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="GIVN" value="<?php print PrintReady(htmlspecialchars($GIVN)); ?>" /></td></tr>
<?php $tabkey++; ?>
<tr><td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SURN" value="<?php print PrintReady(htmlspecialchars($SURN)); ?>" /></td></tr>
<?php $tabkey++; ?>
<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
<tr><td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HGIVN" value="<?php print PrintReady(htmlspecialchars($HGIVN)); ?>" /></td></tr>
<?php $tabkey++; ?>
<tr><td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSURN" value="<?php print PrintReady(htmlspecialchars($HSURN)); ?>" /></td></tr>
<?php $tabkey++; ?>
</tr>
<tr><td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RGIVN" value="<?php print PrintReady(htmlspecialchars($RGIVN)); ?>" /></td></tr>
<?php $tabkey++; ?>
<tr><td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RSURN" value="<?php print PrintReady(htmlspecialchars($RSURN)); ?>" /></td></tr>
<?php $tabkey++; ?>
</tr>
<?php } ?>

<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="SEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"<?php if ($SEX=="M") print " selected=\"selected\""; ?>><?php print GM_LANG_male; ?></option>
			<option value="F"<?php if ($SEX=="F") print " selected=\"selected\""; ?>><?php print GM_LANG_female; ?></option>
			<option value="U"<?php if ($SEX=="U") print " selected=\"selected\""; ?>><?php print GM_LANG_unknown; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php
// NOTE: Update fact
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_fact_help", "qm"); print GM_LANG_update_fact; ?></td></tr>
<tr>
	<td class="descriptionbox">&nbsp;</td>
	<td class="descriptionbox"><?php print $factarray["DATE"]; ?></td>
	<td class="descriptionbox"><?php print $factarray["PLAC"]; ?></td>
	<td class="descriptionbox"><?php print GM_LANG_delete; ?></td>
</tr>
<?php
foreach($indifacts as $f=>$fact) {
	$fact_tag = $fact[0];
	$fact_num = $fact[3];
	$date = GetGedcomValue("DATE", 2, $fact[1], '', false);
	$plac = GetGedcomValue("PLAC", 2, $fact[1], '', false);
	$temp = GetGedcomValue("TEMP", 2, $fact[1], '', false);
	$desc = GetGedcomValue($fact_tag, 1, $fact[1], '', false);
	$resn = GetGedcomValue("RESN", 2, $fact[1], '', false);
	?>
<tr>
	<td class="descriptionbox">
		<?php if (isset($factarray[$fact_tag])) print $factarray[$fact_tag]; 
		else if (defined("GM_LANG_".$fact_tag)) print constant("GM_LANG_".$fact_tag); 
		else print $fact_tag;
		?>		
		<input type="hidden" name="TAGS[]" value="<?php echo $fact_tag; ?>" />
		<input type="hidden" name="NUMS[]" value="<?php echo $fact_num; ?>" />
	</td>
	<?php if (!in_array($fact_tag, $emptyfacts)) { ?>
	<td class="optionbox" colspan="2">
		<input type="text" name="DESCS[]" size="40" value="<?php print htmlspecialchars($desc); ?>" />
		<input type="hidden" name="DATES[]" value="<?php print htmlspecialchars($date); ?>" />
		<input type="hidden" name="PLACS[]" value="<?php print htmlspecialchars($plac); ?>" />
		<input type="hidden" name="TEMPS[]" value="<?php print htmlspecialchars($temp); ?>" />
	</td>
	<?php }	else {
		if (!in_array($fact_tag, $nondatefacts)) { ?>
			<td class="optionbox">
				<input type="hidden" name="DESCS[]" value="<?php print htmlspecialchars($desc); ?>" />
				<input type="text" tabindex="<?php print $tabkey; $tabkey++;?>" size="15" name="DATES[]" id="DATE<?php echo $f; ?>" onblur="valid_date(this);" value="<?php echo htmlspecialchars($date); ?>" />&nbsp;<?php EditFunctions::PrintCalendarPopup("DATE$f");?>
			</td>
		<?php }
		if (empty($temp) && (!in_array($fact_tag, $nonplacfacts))) { ?>
			<td class="optionbox">
				<input type="text" size="30" tabindex="<?php print $tabkey; $tabkey++; ?>" name="PLACS[]" id="place<?php echo $f; ?>" value="<?php print PrintReady(htmlspecialchars($plac)); ?>" />
				<?php LinkFunctions::PrintFindPlaceLink("place$f"); ?>
				<input type="hidden" name="TEMPS[]" value="" />
			</td>
		<?php
		}
		else {
			print "<td class=\"optionbox\"><select tabindex=\"".$tabkey."\" name=\"TEMPS[]\" >\n";
			print "<option value=''>".GM_LANG_no_temple."</option>\n";
			foreach($TEMPLE_CODES as $code=>$temple) {
				print "<option value=\"$code\"";
				if ($code==$temp) print " selected=\"selected\"";
				print ">$temple</option>\n";
			}
			print "</select>\n";
			print "<input type=\"hidden\" name=\"PLACS[]\" value=\"\" />\n";
			print "</td>\n";
			$tabkey++;
		}
	}
	if (!$fact[2]) { ?>
		<td class="optionbox center">
			<input type="hidden" name="REMS[<?php echo $f; ?>]" id="REM<?php echo $f; ?>" value="0" />
			<a href="javascript: <?php print GM_LANG_delete; ?>" onclick="document.quickupdate.closewin.value='0'; document.quickupdate.REM<?php echo $f; ?>.value='1'; document.quickupdate.submit(); return false;">
				<img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" alt="<?php print GM_LANG_delete; ?>" />
			</a>
		</td>
	</tr>
	<?php }
	else {?>
		<td class="optionbox">&nbsp;</td>
	</tr>
	<?php }
	if (GedcomConfig::$SHOW_QUICK_RESN) {
		EditFunctions::PrintQuickResn("RESNS[]", $resn);
	}
}

// NOTE: Add fact
if (count($addfacts)>0) { ?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_fact_help", "qm"); print GM_LANG_add_fact; ?></td></tr>
<tr>
	<td class="descriptionbox">&nbsp;</td>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["DATE"]; ?></td>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"]; ?></td>
	<td class="descriptionbox">&nbsp;</td>
	</tr>
<tr><td class="optionbox">
	<script language="JavaScript" type="text/javascript">
	<!--
	function checkDesc(newfactSelect) {
		if (newfactSelect.selectedIndex==0) return;
		var fact = newfactSelect.options[newfactSelect.selectedIndex].value;
		var emptyfacts = "<?php foreach($emptyfacts as $ind=>$efact) print $efact.","; ?>";
		descFact = document.getElementById('descFact');
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
		<option value=""><?php print GM_LANG_select_fact; ?></option>
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
			<?php print GM_LANG_description; ?><input type="text" size="35" name="DESC" />
		</div>
	</td>
	<td class="optionbox"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="DATE" id="DATE" onblur="valid_date(this);" />&nbsp;<?php EditFunctions::PrintCalendarPopup("DATE");?></td>
	<?php $tabkey++; ?>
	<td class="optionbox"><input type="text" tabindex="<?php print $tabkey; ?>" name="PLAC" id="place" />
	<?php LinkFunctions::PrintFindPlaceLink("place"); ?>
	</td>
	<td class="optionbox">&nbsp;</td></tr>
	<?php $tabkey++; ?>
	<?php EditFunctions::PrintQuickResn("RESN"); ?>
<?php }

// NOTE: Add photo
if (MediaFS::DirIsWritable(GedcomConfig::$MEDIA_DIRECTORY)) { ?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><b><?php PrintHelpLink("quick_update_photo_help", "qm"); print GM_LANG_update_photo; ?></b></td></tr>
<tr>
	<td class="descriptionbox">
		<?php print $factarray["TITL"]; ?>
	</td>
	<td class="optionbox" colspan="3">
		<input type="text" tabindex="<?php print $tabkey; ?>" name="TITL" size="40" />
	</td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox">
		<?php print $factarray["FILE"]; ?>
	</td>
	<td class="optionbox" colspan="3">
		<input type="file" tabindex="<?php print $tabkey; ?>" name="FILE" size="40" />
	</td>
	<?php $tabkey++; ?>
</tr>
<?php if (preg_match("/1 OBJE/", $gedrec)>0) { ?>
<tr>
	<td class="descriptionbox">&nbsp;</td>
	<td class="optionbox" colspan="3">
		<input type="checkbox" tabindex="<?php print $tabkey; ?>" name="replace" value="yes" /> <?php print GM_LANG_photo_replace; ?>
	</td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<?php }

// Address update
if (!IsDeadId($pid) || !empty($ADDR) || !empty($PHON) || !empty($FAX) || !empty($EMAIL)) { //-- don't show address for dead people 
	 ?>
<tr><td>&nbsp;</td></tr> 
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_address_help", "qm"); print GM_LANG_update_address; ?></td></tr>
<tr>
	<td class="descriptionbox">
		<?php print $factarray["ADDR"]; ?>
	</td>
	<td class="optionbox" colspan="3">
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
	<td class="descriptionbox">
		<?php print $factarray["PHON"]; ?>
	</td>
	<td class="optionbox" colspan="3">
		<input type="text" tabindex="<?php print $tabkey; $tabkey++; ?>" name="PHON" size="20" value="<?php print PrintReady($PHON); ?>" />
	</td>
</tr>
<tr>
		<td class="descriptionbox">
				<?php print $factarray["FAX"]; ?>
		</td>
		<td class="optionbox" colspan="3">
				<input type="text" tabindex="<?php print $tabkey; $tabkey++; ?>" name="FAX" size="20" value="<?php print PrintReady($FAX); ?>" />
	</td>
</tr>
<tr>
	<td class="descriptionbox">
		<?php print $factarray["EMAIL"]; ?>
	</td>
	<td class="optionbox" colspan="3">
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
	$famreqdfacts = preg_split("/[,; ]/", GedcomConfig::$QUICK_REQUIRED_FAMFACTS);
	$famid = $sfams[$i-1]["famid"];
	$famrec = FindFamilyRecord($famid);
	if (ChangeFunctions::GetChangeData(true, $famid, true)) {
		$rec = ChangeFunctions::GetChangeData(false, $famid, true, "gedlines");
		$famrec = $rec[GedcomConfig::$GEDCOMID][$famid];
	}
	print GM_LANG_family_with." ";
	$parents = FindParentsInRecord($famrec);
	$spid = "";
	if($parents) {
		if($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
		else $spid=$parents["WIFE"];
	}
	if (!empty($spid)) {
		$parrec = FindGedcomRecord($spid);
		if (ChangeFunctions::GetChangeData(true, $spid, true)) {
			$rec = ChangeFunctions::GetChangeData(false, $spid, true, "gedlines");
			$parrec = $rec[GedcomConfig::$GEDCOMID][$spid];
		}
		if (PrivacyFunctions::displayDetailsById($spid) && PrivacyFunctions::showLivingNameById($spid)) {
			print "<a href=\"#\" onclick=\"return quickEdit('".$spid."');\">";
			$name = PrintReady(GetPersonName($spid, $parrec));
			if (GedcomConfig::$SHOW_ID_NUMBERS) $name .= " (".$spid.")";
			$name .= " [".GM_LANG_edit."]";
			print $name."</a>\n";
		}
		else print GM_LANG_private;
	}
	else print GM_LANG_unknown;
	$subrecords = GetAllSubrecords($famrec, "HUSB,WIFE,CHIL", false, false, false);
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
		if ($fact=="EVEN" || $fact=="FACT") $fact = GetGedcomValue("TYPE", 2, $subrec, '', false);
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
//	usort($famfacts, "CompareFacts");
	SortFacts($famfacts);
?>
</td></tr>
<tr>
	<td class="descriptionbox"><?php print GM_LANG_enter_pid; ?></td>
	<td class="optionbox" colspan="3"><input type="text" size="10" name="SPID[<?php echo $i; ?>]" id="SPID<?php echo $i; ?>" value="<?php echo $spid; ?>" />
		<?php LinkFunctions::PrintFindindiLink("SPID$i","");?>
     </td>
	</tr>
<?php if (empty($spid)) { ?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_spouse_help", "qm"); if (preg_match("/1 SEX M/", $gedrec)>0) print GM_LANG_add_new_wife; else print GM_LANG_add_new_husb;?></td></tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RSGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RSSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="SSEX<?php echo $i; ?>" tabindex="<?php print $tabkey; ?>">
			<option value="M"<?php if (preg_match("/1 SEX F/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print GM_LANG_male; ?></option>
			<option value="F"<?php if (preg_match("/1 SEX M/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print GM_LANG_female; ?></option>
			<option value="U"<?php if (preg_match("/1 SEX U/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print GM_LANG_unknown; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?><?php print $factarray["DATE"];?></td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="BDATE<?php echo $i; ?>" id="BDATE<?php echo $i; ?>" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("BDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="BPLAC<?php echo $i; ?>" id="bplace<?php echo $i; ?>" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="banchor1x" id="banchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("place$f"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php EditFunctions::PrintQuickResn("BRESN".$i); 
}
//NOTE: Update fact
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_fact_help", "qm"); print GM_LANG_update_fact; ?></td></tr>
<tr>
	<td class="descriptionbox">&nbsp;</td>
	<td class="descriptionbox"><?php print $factarray["DATE"]; ?></td>
	<td class="descriptionbox"><?php print $factarray["PLAC"]; ?></td>
	<td class="descriptionbox"><?php print GM_LANG_delete; ?></td>
	</tr>
<?php
foreach($famfacts as $f=>$fact) {
	$fact_tag = $fact[0];
	$date = GetGedcomValue("DATE", 2, $fact[1], '', false);
	$plac = GetGedcomValue("PLAC", 2, $fact[1], '', false);
	$temp = GetGedcomValue("TEMP", 2, $fact[1], '', false);
	$resn = GetGedcomValue("RESN", 2, $fact[1], '', false);
	?>
			<tr>
				<td class="descriptionbox">
				<?php if (isset($factarray[$fact_tag])) print $factarray[$fact_tag]; 
					else if (defined("GM_LANG_".$fact_tag)) print constant("GM_LANG_".$fact_tag); 
					else print $fact_tag;
				?>
					<input type="hidden" name="F<?php echo $i; ?>TAGS[]" value="<?php echo $fact_tag; ?>" />
				</td>
				<td class="optionbox"><input type="text" tabindex="<?php print $tabkey; $tabkey++;?>" size="15" name="F<?php echo $i; ?>DATES[]" id="F<?php echo $i; ?>DATE<?php echo $f; ?>" onblur="valid_date(this);" value="<?php echo htmlspecialchars($date); ?>" /><?php EditFunctions::PrintCalendarPopup("F{$i}DATE{$f}");?></td>
				<?php if (empty($temp) && (!in_array($fact_tag, $nonplacfacts))) { ?>
					<td class="optionbox"><input type="text" size="30" tabindex="<?php print $tabkey; $tabkey++; ?>" name="F<?php echo $i; ?>PLACS[]" id="F<?php echo $i; ?>place<?php echo $f; ?>" value="<?php print PrintReady(htmlspecialchars($plac)); ?>" />
                                        <?php LinkFunctions::PrintFindPlaceLink("F".$i."place$f"); ?>
                                        </td>
				<?php }
				else {
					print "<td class=\"optionbox\"><select tabindex=\"".$tabkey."\" name=\"F".$i."TEMPS[]\" >\n";
					print "<option value=''>".GM_LANG_no_temple."</option>\n";
					foreach($TEMPLE_CODES as $code=>$temple) {
						print "<option value=\"$code\"";
						if ($code==$temp) print " selected=\"selected\"";
						print ">$temple</option>\n";
					}
					print "</select>\n</td>\n";
					$tabkey++;
				}
				?>
				<td class="optionbox center">
					<input type="hidden" name="F<?php echo $i; ?>REMS[<?php echo $f; ?>]" id="F<?php echo $i; ?>REM<?php echo $f; ?>" value="0" />
					<?php if (!$fact[2]) { ?>
					<a href="javascript: <?php print GM_LANG_delete; ?>" onclick="document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>REM<?php echo $f; ?>.value='1'; document.quickupdate.submit(); return false;">
						<img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" alt="<?php print GM_LANG_delete; ?>" />
					</a>
					<?php } ?>
				</td>
			</tr>
			<?php if (GedcomConfig::$SHOW_QUICK_RESN) {
				EditFunctions::PrintQuickResn("F".$i."RESNS[]", $resn);
			} ?>
	<?php
}
// Note: add fact
if (count($famaddfacts)>0) { ?>
	<tr><td>&nbsp;</td></tr>
	<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_fact_help", "qm"); print GM_LANG_add_fact; ?></td></tr>
	<tr>
	<td class="descriptionbox">&nbsp;</td>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["DATE"]; ?></td>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"]; ?></td>
	<td class="descriptionbox">&nbsp;</td>
	</tr>
	<tr>
	<td class="optionbox"><select name="F<?php echo $i; ?>newfact" tabindex="<?php print $tabkey; ?>">
		<option value=""><?php print GM_LANG_select_fact; ?></option>
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
	<td class="optionbox"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="F<?php echo $i; ?>DATE" id="F<?php echo $i; ?>DATE" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("F".$i."DATE");?></td>
	<?php $tabkey++; ?>
	<td class="optionbox"><input type="text" tabindex="<?php print $tabkey; ?>" name="F<?php echo $i; ?>PLAC" id="F<?php echo $i; ?>place" />
	<?php LinkFunctions::PrintFindPlaceLink("F".$i."place"); ?>
	</td>
	<?php $tabkey++; ?>
	<td class="optionbox">&nbsp;</td>
	</tr>
	<?php EditFunctions::PrintQuickResn("F".$i."RESN"); ?>
<?php }
// NOTE: Children
$chil = FindChildrenInRecord($famrec);
	?>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="topbottombar" colspan="4"><?php print GM_LANG_children; ?></td>
	</tr>
	<tr>
			<input type="hidden" name="F<?php echo $i; ?>CDEL" value="" />
					<td class="descriptionbox"><?php print GM_LANG_name; ?></td>
					<td class="descriptionbox"><?php print $factarray["BIRT"]; ?></td>
					<td class="descriptionbox"><?php print $factarray["PEDI"]; ?></td>
					<td class="descriptionbox"><?php print GM_LANG_remove; ?></td>
	</tr>
			<?php
				foreach($chil as $c=>$child) {
					$childrec = FindPersonRecord($child);
					if (ChangeFunctions::GetChangeData(true, $child, true)) {
						$rec = ChangeFunctions::GetChangeData(false, $child, true, "gedlines");
						$childrec = $rec[GedcomConfig::$GEDCOMID][$child];
					}
					print "<tr><td class=\"optionbox\">";
					$name = GetPersonName($child, $childrec);
					$disp = PrivacyFunctions::displayDetailsById($child);
					if (GedcomConfig::$SHOW_ID_NUMBERS) $name .= " (".$child.")";
					else $childrec = FindPersonRecord($child);
					if ($disp || PrivacyFunctions::showLivingNameById($child)) {
						$isF = GetGedcomValue("SEX", 1, $childrec, "", false);
						$name .= "<img id=\"box-F.$i.$child-sex\" src=\"".GM_IMAGE_DIR."/";
						if ($isF=="M") $name .= $GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male;
						else  if ($isF=="F") $name .= $GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female;
						else  $name .= $GM_IMAGES["sexn"]["small"]."\" title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown;
						$name .= "\" class=\"sex_image\" />";
						$name.= "<a href=\"#\" onclick=\"return quickEdit('".$child."');\">&nbsp;"."[".GM_LANG_edit."]</a>";
						print PrintReady($name);
					}
					else print GM_LANG_private;
					print "</td>\n<td class=\"optionbox\">";
					if ($disp) {
						$birtrec = GetSubRecord(1, "BIRT", $childrec);
						if (!empty($birtrec)) {
							if (PrivacyFunctions::showFact("BIRT", $child) && !PrivacyFunctions::FactViewRestricted($child, $birtrec)) {
								print GetGedcomValue("DATE", 2, $birtrec);
								print " -- ";
								print GetGedcomValue("PLAC", 2, $birtrec);
							}
						}
					}
					print "</td>\n";
					$pedirec = GetSubRecord(1, "FAMC @$famid@", $childrec);
					$pedirec = GetGedcomValue("PEDI", 2, $pedirec);
					
					print "<td class=\"optionbox\">";
					if ($pid == $child) EditFunctions::PrintPedi("F".$i."CPEDI".$i, $pedirec);
					else if (!empty($pedirec)) print constant("GM_LANG_".$pedirec);
					else print GM_LANG_biological;
					print "</td>";
					?>
					<td class="optionbox center" colspan="3">
						<a href="javascript: <?php print GM_LANG_remove_child; ?>" onclick="if (confirm('<?php print GM_LANG_confirm_remove; ?>')) { document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>CDEL.value='<?php echo $child; ?>'; document.quickupdate.submit(); } return false;">
							<img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" alt="<?php print GM_LANG_remove_child; ?>" />
						</a>
					</td>
					<?php
					print "</tr>\n";
				}
			?>
			<tr>
				<td class="descriptionbox"><?php print GM_LANG_add_new_chil; ?></td>
				<td class="optionbox" colspan="3"><input type="text" size="10" name="CHIL[]" id="CHIL<?php echo $i; ?>" />
                                <?php LinkFunctions::PrintFindindiLink("CHIL$i","");?>
                                </td>
			</tr>
<?php 
// NOTE: Add a child
if (empty($child_surname)) $child_surname = "";
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><b><?php PrintHelpLink("quick_update_child_help", "qm"); print GM_LANG_add_new_chil; ?></b></td></tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>GIVN" /></td>
</tr>
	<?php $tabkey++; ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>SURN" value="<?php if (!empty($child_surname)) print $child_surname; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>GIVN" /></td>
</tr>
	<?php $tabkey++; ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RC<?php echo $i; ?>GIVN" /></td>
</tr>
	<?php $tabkey++; ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RC<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="C<?php echo $i; ?>SEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print GM_LANG_male; ?></option>
			<option value="F"><?php print GM_LANG_female; ?></option>
			<option value="U"><?php print GM_LANG_unknown; ?></option>
		</select>
	</td></tr>
	<?php $tabkey++; ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="C<?php echo $i; ?>DATE" id="C<?php echo $i; ?>DATE" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("C{$i}DATE");?></td>
	<?php $tabkey++; ?>
	</tr>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>PLAC" id="c<?php echo $i; ?>place" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="canchor1x" id="canchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("c".$i."place"); ?>
	</td>
	<?php $tabkey++; ?>
</tr>
<?php EditFunctions::PrintQuickResn("C".$i."RESN"); ?>
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
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_spouse_help", "qm"); if (preg_match("/1 SEX M/", $gedrec)>0) print GM_LANG_add_new_wife; else print GM_LANG_add_new_husb; ?></td></tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SGIVN" /></td></tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="SSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSGIVN" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HSSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RSGIVN" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RSSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="SSEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"<?php if (preg_match("/1 SEX F/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print GM_LANG_male; ?></option>
			<option value="F"<?php if (preg_match("/1 SEX M/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print GM_LANG_female; ?></option>
			<option value="U"<?php if (preg_match("/1 SEX U/", $gedrec)>0) print " selected=\"selected\""; ?>><?php print GM_LANG_unknown; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="BDATE" id="BDATE" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("BDATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="BPLAC" id="bplace" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="banchor1x" id="banchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("bplace"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php EditFunctions::PrintQuickResn("BRESN"); 

// NOTE: Marriage
?>
<tr><td>&nbsp;</td></tr>
<tr>
	<td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_marriage_help", "qm"); print $factarray["MARR"]; ?></td>
</tr>
<tr><td class="descriptionbox">
		<?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="MDATE" id="MDATE" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("MDATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="MPLAC" id="mplace" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="manchor1x" id="manchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("mplace"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php EditFunctions::PrintQuickResn("MRESN");

// NOTE: New child
if (empty($child_surname)) $child_surname = "";
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><b><?php PrintHelpLink("quick_update_child_help", "qm"); print GM_LANG_add_new_chil; ?></b></td></tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="CGIVN" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="CSURN" value="<?php if (!empty($child_surname)) print $child_surname; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HCGIVN" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HCSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RCGIVN" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RCSURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="CSEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print GM_LANG_male; ?></option>
			<option value="F"><?php print GM_LANG_female; ?></option>
			<option value="U"><?php print GM_LANG_unknown; ?></option>
		</select>
	</td></tr>
	<?php $tabkey++; ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="CDATE" id="CDATE" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("CDATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="CPLAC" id="cplace" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="canchor2x" id="canchor2x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("cplace"); ?>
	</td>
	<?php $tabkey++; ?>
</tr>
<?php EditFunctions::PrintQuickResn("CRESN"); ?>
</table>
</div>

<?php //------------------------------------------- FAMILY AS CHILD TABS ------------------------ 
$i++;
for($j=1; $j<=count($cfams); $j++) {
	?>
<div id="tab<?php echo $i; ?>" style="display: none;">
<table class="<?php print $TEXT_DIRECTION; ?> width80">
<?php
	$famreqdfacts = preg_split("/[,; ]/", GedcomConfig::$QUICK_REQUIRED_FAMFACTS);
	$famid = $cfams[$j-1]["famid"];
	// NOTE $famid can be empty to generate empty tab
	if (!empty($famid) && ChangeFunctions::GetChangeData(true, $famid, true)) {
		$rec = ChangeFunctions::GetChangeData(false, $famid, true, "gedlines");
		$famrec = $rec[GedcomConfig::$GEDCOMID][$famid];
	}
	else $famrec = FindFamilyRecord($famid);
	$parents = FindParentsInRecord($famrec);
	
	$subrecords = GetAllSubrecords($famrec, "HUSB,WIFE,CHIL", false, false, false);
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
		if ($fact=="EVEN" || $fact=="FACT") $fact = GetGedcomValue("TYPE", 2, $subrec, '', false);
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
//	usort($famfacts, "CompareFacts");
	SortFacts($famfacts);
	$spid = "";
	if($parents) {
		if($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
		else $spid=$parents["WIFE"];
	}

// NOTE: Father
?>
	<tr><td class="topbottombar" colspan="4">
	<?php
	$label = GM_LANG_father;
	if (!empty($parents["HUSB"])) {
		if (PrivacyFunctions::displayDetailsById($parents["HUSB"]) && PrivacyFunctions::showLivingNameById($parents["HUSB"])) {
			$fatherrec = FindPersonRecord($parents["HUSB"]);
			if (ChangeFunctions::GetChangeData(true, $parents["HUSB"], true)) {
				$rec = ChangeFunctions::GetChangeData(false, $parents["HUSB"], true, "gedlines");
				$fatherrec = $rec[GedcomConfig::$GEDCOMID][$parents["HUSB"]];
			}
			$fsex = GetGedcomValue("SEX", 1, $fatherrec, '', false);
			$child_surname = "";
			$ct = preg_match("~1 NAME.*/(.*)/~", $fatherrec, $match);
			if ($ct>0) $child_surname = $match[1];
			if ($fsex=="F") $label = GM_LANG_mother;
			print $label." ";
			print "<a href=\"#\" onclick=\"return quickEdit('".$parents["HUSB"]."');\">";
			$name = GetPersonName($parents["HUSB"], $fatherrec);
			if (GedcomConfig::$SHOW_ID_NUMBERS) $name .= " (".$parents["HUSB"].")";
			$name .= " [".GM_LANG_edit."]";
			print PrintReady($name)."</a>\n";
		}
		else print $label." ".GM_LANG_private;
	}
	else print $label." ".GM_LANG_unknown;
	print "</td></tr>";
	print "<tr><td class=\"descriptionbox\">".GM_LANG_enter_pid."<td  class=\"optionbox\" colspan=\"3\"><input type=\"text\" size=\"10\" name=\"FATHER[$i]\" id=\"FATHER$i\" value=\"".$parents['HUSB']."\" />";
	LinkFunctions::PrintFindIndiLink("FATHER$i","");
	print "</td></tr>";
?>
<?php if (empty($parents["HUSB"])) { ?>
	<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_spouse_help", "qm"); print GM_LANG_add_father; ?></td></tr>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="FGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="FSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
	</tr>
	<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HFGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HFSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
	</tr>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RFGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RFSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
	</tr>
	<?php } ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="FSEX<?php echo $i; ?>" tabindex="<?php print $tabkey; ?>">
			<option value="M" selected="selected"><?php print GM_LANG_male; ?></option>
			<option value="F"><?php print GM_LANG_female; ?></option>
			<option value="U"><?php print GM_LANG_unknown; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
	</tr>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="FBDATE<?php echo $i; ?>" id="FBDATE<?php echo $i; ?>" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("FBDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="FBPLAC<?php echo $i; ?>" id="Fbplace<?php echo $i; ?>" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="banchor1x" id="banchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("Fbplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
	</tr>
	<?php EditFunctions::PrintQuickResn("FBRESN$i"); ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["DEAT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="FDDATE<?php echo $i; ?>" id="FDDATE<?php echo $i; ?>" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("FDDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="FDPLAC<?php echo $i; ?>" id="Fdplace<?php echo $i; ?>" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="danchor1x" id="danchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("Fdplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
	</tr>
	<?php EditFunctions::PrintQuickResn("FDRESN$i"); 
}
?>
<?php
// NOTE: Mother
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4">
<?php
	$label = GM_LANG_mother;
	if (!empty($parents["WIFE"])) {
		if (PrivacyFunctions::displayDetailsById($parents["WIFE"]) && PrivacyFunctions::showLivingNameById($parents["WIFE"])) {
			$motherrec = FindPersonRecord($parents["WIFE"]);
			if (ChangeFunctions::GetChangeData(true, $parents["WIFE"], true)) {
				$rec = ChangeFunctions::GetChangeData(false, $parents["WIFE"], true, "gedlines");
				$motherrec = $rec[GedcomConfig::$GEDCOMID][$parents["WIFE"]];
			}
			$msex = GetGedcomValue("SEX", 1, $motherrec, '', false);
			if ($msex=="M") $label = GM_LANG_father;
			print $label." ";
			print "<a href=\"#\" onclick=\"return quickEdit('".$parents["WIFE"]."');\">";
			$name = GetPersonName($parents["WIFE"], $motherrec);
			if (GedcomConfig::$SHOW_ID_NUMBERS) $name .= " (".$parents["WIFE"].")";
			$name .= " [".GM_LANG_edit."]";
			print PrintReady($name)."</a>\n";
		}
		else print $label." ".GM_LANG_private;
	}
	else print $label." ".GM_LANG_unknown;
	print "</td></tr>\n";
	print "<tr><td  class=\"descriptionbox\">".GM_LANG_enter_pid."<td  class=\"optionbox\" colspan=\"3\"><input type=\"text\" size=\"10\" name=\"MOTHER[$i]\" id=\"MOTHER$i\" value=\"".$parents['WIFE']."\" />";
	LinkFunctions::PrintFindIndiLink("MOTHER$i","");
	?>
</td></tr>
<?php if (empty($parents["WIFE"])) { ?>
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_spouse_help", "qm"); print GM_LANG_add_mother; ?></td></tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="MGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="MSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HMGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	</tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HMSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RMGIVN<?php echo $i; ?>" /></td>
	</tr>
	<?php $tabkey++; ?>
	</tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RMSURN<?php echo $i; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="MSEX<?php echo $i; ?>" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print GM_LANG_male; ?></option>
			<option value="F" selected="selected"><?php print GM_LANG_female; ?></option>
			<option value="U"><?php print GM_LANG_unknown; ?></option>
		</select>
	<?php $tabkey++; ?>
	</td>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="MBDATE<?php echo $i; ?>" id="MBDATE<?php echo $i; ?>" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("MBDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="MBPLAC<?php echo $i; ?>" id="Mbplace<?php echo $i; ?>" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="banchor1x" id="banchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("Mbplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php EditFunctions::PrintQuickResn("MBRESN$i"); ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["DEAT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="MDDATE<?php echo $i; ?>" id="MDDATE<?php echo $i; ?>" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("MDDATE$i");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="MDPLAC<?php echo $i; ?>" id="Mdplace<?php echo $i; ?>" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="danchor1x" id="danchor1x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("Mdplace$i"); ?>
	<?php $tabkey++; ?>
	</td>
</tr>
<?php EditFunctions::PrintQuickResn("MDRESN$i"); 
}
// NOTE: Update fact 
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_fact_help", "qm"); print GM_LANG_update_fact; ?></td></tr>
<tr>
	<td class="descriptionbox">&nbsp;</td>
	<td class="descriptionbox"><?php print $factarray["DATE"]; ?></td>
	<td class="descriptionbox"><?php print $factarray["PLAC"]; ?></td>
	<td class="descriptionbox"><?php print GM_LANG_delete; ?></td>
<?php
foreach($famfacts as $f=>$fact) {
	$fact_tag = $fact[0];
	$date = GetGedcomValue("DATE", 2, $fact[1], '', false);
	$plac = GetGedcomValue("PLAC", 2, $fact[1], '', false);
	$temp = GetGedcomValue("TEMP", 2, $fact[1], '', false);
	$resn = GetGedcomValue("RESN", 2, $fact[1], '', false);
	?>
			<tr>
				<td class="descriptionbox">
				<?php if (isset($factarray[$fact_tag])) print $factarray[$fact_tag]; 
					else if (defined("GM_LANG_".$fact_tag)) print constant("GM_LANG_".$fact_tag); 
					else print $fact_tag;
				?>
					<input type="hidden" name="F<?php echo $i; ?>TAGS[]" value="<?php echo $fact_tag; ?>" />
				</td>
				<td class="optionbox"><input type="text" tabindex="<?php print $tabkey; $tabkey++;?>" size="15" name="F<?php echo $i; ?>DATES[]" id="F<?php echo $i; ?>DATE<?php echo $f; ?>" onblur="valid_date(this);" value="<?php echo htmlspecialchars($date); ?>" /><?php EditFunctions::PrintCalendarPopup("F{$i}DATE$f");?></td>
				<?php if (empty($temp) && (!in_array($fact_tag, $nonplacfacts))) { ?>
					<td class="optionbox"><input size="30" type="text" tabindex="<?php print $tabkey; $tabkey++; ?>" name="F<?php echo $i; ?>PLACS[]" id="F<?php echo $i; ?>place<?php echo $f; ?>" value="<?php print PrintReady(htmlspecialchars($plac)); ?>" />
					<?php LinkFunctions::PrintFindPlaceLink("F".$i."place$f"); ?>
                         </td>
				<?php }
				else {
					print "<td class=\"optionbox\"><select tabindex=\"".$tabkey."\" name=\"F".$i."TEMPS[]\" >\n";
					print "<option value=''>".GM_LANG_no_temple."</option>\n";
					foreach($TEMPLE_CODES as $code=>$temple) {
						print "<option value=\"$code\"";
						if ($code==$temp) print " selected=\"selected\"";
						print ">$temple</option>\n";
					}
					print "</select>\n</td>\n";
					$tabkey++;
				}
				?>
				<td class="optionbox center">
					<input type="hidden" name="F<?php echo $i; ?>REMS[<?php echo $f; ?>]" id="F<?php echo $i; ?>REM<?php echo $f; ?>" value="0" />
					<?php if (!$fact[2]) { ?>
					<a href="javascript: <?php print GM_LANG_delete; ?>" onclick="if (confirm('<?php print GM_LANG_confirm_remove; ?>')) { document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>REM<?php echo $f; ?>.value='1'; document.quickupdate.submit(); } return false;">
						<img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" alt="<?php print GM_LANG_delete; ?>" />
					</a>
					<?php } ?>
				</td>
			</tr>
			<?php if (GedcomConfig::$SHOW_QUICK_RESN) {
				EditFunctions::PrintQuickResn("F".$i."RESNS[]", $resn);
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
	<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_fact_help", "qm"); print GM_LANG_add_fact; ?></td></tr>
	<tr>
		<td class="descriptionbox">&nbsp;</td>
		<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["DATE"]; ?></td>
		<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"]; ?></td>
		<td class="descriptionbox">&nbsp;</td>
		</tr>
	<tr>
		<td class="optionbox"><select name="F<?php echo $i; ?>newfact" tabindex="<?php print $tabkey; ?>">
			<option value=""><?php print GM_LANG_select_fact; ?></option>
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
		<td class="optionbox"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="F<?php echo $i; ?>DATE" id="F<?php echo $i; ?>DATE" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("F".$i."DATE");?></td>
		<?php $tabkey++; ?>
		<td class="optionbox"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="F<?php echo $i; ?>PLAC" id="F<?php echo $i; ?>place" />
		<?php LinkFunctions::PrintFindPlaceLink("F".$i."place"); ?>
		</td>
		<?php $tabkey++; ?>
		<td class="optionbox">&nbsp;</td>
	</tr>
	<?php EditFunctions::PrintQuickResn("F".$i."RESN"); ?>
<?php }
// NOTE: Children
//$chil = FindChildrenInRecord($famrec, $pid);
$chil = FindChildrenInRecord($famrec);
	?>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="topbottombar" colspan="4"><?php print GM_LANG_children; ?></td>
	</tr>
	<tr>
		<input type="hidden" name="F<?php echo $i; ?>CDEL" value="" />
					<td class="descriptionbox"><?php print GM_LANG_name; ?></td>
					<td class="descriptionbox"><?php print $factarray["BIRT"]; ?></td>
					<td class="descriptionbox"><?php print $factarray["PEDI"]; ?></td>
					<td class="descriptionbox"><?php print GM_LANG_remove; ?></td>
				</tr>
			<?php
				foreach($chil as $c=>$child) {
					print "<tr><td class=\"optionbox\">";
					$childrec = FindPersonRecord($child);
					if (ChangeFunctions::GetChangeData(true, $child, true)) {
						$rec = ChangeFunctions::GetChangeData(false, $child, true, "gedlines");
						$childrec = $rec[GedcomConfig::$GEDCOMID][$child];
					}
					$name = GetPersonName($child, $childrec);
					$disp = PrivacyFunctions::displayDetailsById($child);
					if (GedcomConfig::$SHOW_ID_NUMBERS) $name .= " (".$child.")";
					if ($disp || PrivacyFunctions::showLivingNameById($child)) {
						$isF = GetGedcomValue("SEX", 1, $childrec, "", false);
						$name .= "<img id=\"box-F.$i.$child-sex\" src=\"".GM_IMAGE_DIR."/";
						if ($isF=="M") $name .= $GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male;
						else  if ($isF=="F") $name .= $GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female;
						else  $name .= $GM_IMAGES["sexn"]["small"]."\" title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown;
						$name .= "\" class=\"sex_image\" />";
						if ($pid != $child) $name.= "<a href=\"#\" onclick=\"return quickEdit('".$child."');\">&nbsp;"."[".GM_LANG_edit."]</a>";
						print PrintReady($name);
					}
					else print GM_LANG_private;
					print "</td>\n<td class=\"optionbox\">";
					if ($disp) {
						$birtrec = GetSubRecord(1, "BIRT", $childrec);
						if (!empty($birtrec)) {
							if (PrivacyFunctions::showFact("BIRT", $child) && !PrivacyFunctions::FactViewRestricted($child, $birtrec)) {
								print GetGedcomValue("DATE", 2, $birtrec);
								print " -- ";
								print GetGedcomValue("PLAC", 2, $birtrec);
							}
						}
					}
					print "</td>\n";
					
					print "<td class=\"optionbox\">";
					$pedirec = GetSubRecord(1, "FAMC @$famid@", $childrec);
					$pedirec = GetGedcomValue("PEDI", 2, $pedirec);
					if ($pid == $child) EditFunctions::PrintPedi("F".$i."CPEDI", $pedirec);
					else if (!empty($pedirec)) print constant("GM_LANG_".$pedirec);
					else print GM_LANG_biological;
					print "</td>";

					?><td class="optionbox center" colspan="1"><?php if ($pid != $child) {?>
						<a href="javascript: <?php print GM_LANG_remove_child; ?>" onclick="document.quickupdate.closewin.value='0'; document.quickupdate.F<?php echo $i; ?>CDEL.value='<?php echo $child; ?>'; document.quickupdate.submit(); return false;">
							<img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["remove"]["other"]; ?>" border="0" alt="<?php print GM_LANG_remove_child; ?>" />
						</a><?php }?>
					</td>
					<?php
					print "</tr>\n";
				}
			?>
			<tr>
				<td class="descriptionbox"><?php print GM_LANG_add_child_to_family; ?></td>
				<td class="optionbox" colspan="3"><input type="text" size="10" name="CHIL[<?php echo $i; ?>]" id="CHIL<?php echo $i; ?>" />
                                <?php LinkFunctions::PrintFindIndiLink("CHIL$i","");?>
                                </td>
			</tr>
<?php
// NOTE: Add a child
?>
<tr><td>&nbsp;</td></tr>
<tr><td class="topbottombar" colspan="4"><?php PrintHelpLink("quick_update_child_help", "qm"); print GM_LANG_add_child_to_family; ?></td></tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print $factarray["GIVN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>GIVN" /></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print $factarray["SURN"];?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>SURN" value="<?php //if (!empty($child_surname)) print $child_surname; ?>" /></td>
	<?php $tabkey++; ?>
</tr>
<?php if (GedcomConfig::$USE_RTL_FUNCTIONS) { ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_hebrew_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>GIVN" /></td>
	<?php $tabkey++; ?>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_hebrew_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="HC<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_given_name_help", "qm"); print GM_LANG_roman_givn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RC<?php echo $i; ?>GIVN" /></td>
	<?php $tabkey++; ?>
	<td class="descriptionbox"><?php PrintHelpLink("edit_surname_help", "qm"); print GM_LANG_roman_surn;?></td>
	<td class="optionbox" colspan="3"><input size="50" type="text" tabindex="<?php print $tabkey; ?>" name="RC<?php echo $i; ?>SURN" /></td>
	<?php $tabkey++; ?>
</tr>
<?php } ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_sex_help", "qm"); print GM_LANG_sex;?></td>
	<td class="optionbox" colspan="3">
		<select name="C<?php echo $i; ?>SEX" tabindex="<?php print $tabkey; ?>">
			<option value="M"><?php print GM_LANG_male; ?></option>
			<option value="F"><?php print GM_LANG_female; ?></option>
			<option value="U"><?php print GM_LANG_unknown; ?></option>
		</select>
	</td></tr>
	<?php $tabkey++; ?>
<tr>
	<td class="descriptionbox"><?php PrintHelpLink("def_gedcom_date_help", "qm"); print $factarray["BIRT"]; ?>
		<?php print $factarray["DATE"];?>
	</td>
	<td class="optionbox" colspan="3"><input type="text" tabindex="<?php print $tabkey; ?>" size="15" name="C<?php echo $i; ?>DATE" id="C<?php echo $i; ?>DATE" onblur="valid_date(this);" /><?php EditFunctions::PrintCalendarPopup("C{$i}DATE");?></td>
	</tr>
	<?php $tabkey++; ?>
	<tr>
	<td class="descriptionbox"><?php PrintHelpLink("edit_PLAC_help", "qm"); print $factarray["PLAC"];?></td>
	<td class="optionbox" colspan="3"><input size="30" type="text" tabindex="<?php print $tabkey; ?>" name="C<?php echo $i; ?>PLAC" id="C<?php echo $i; ?>place" /><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"];?>" name="canchor3x" id="canchor3x" alt="" />
	<?php LinkFunctions::PrintFindPlaceLink("c".$i."place"); ?>
	</td>
	<?php $tabkey++; ?>
</tr>
<?php EditFunctions::PrintQuickResn("C".$i."RESN"); ?>
</table>
</div>
	<?php
	$i++;
}
?>
<input type="submit" value="<?php print GM_LANG_save; ?>" />
</form>
<?php
}
PrintSimpleFooter();
?>