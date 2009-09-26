<?php
/**
 * Various functions used by the Edit interface
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
 * @subpackage Edit
 * @see functions_places.php
 * @version $Id$
 */

if (strstr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

require_once("includes/values/edit_data.php");
/**
 * Replace a gedcom record
 *
 * It takes an old and a new gedcom record and stores it in the changes table.
 * Further the type of change (replace person, edit source...) and the change
 * ID are stored. The change ID is used to keep of all changes made in 1 single
 * action since they all need to be approved at once.
 *
 * @author	Genmod Development Team
 * @param		string	$gid			The ID of the item that is being changes
 * @param		string	$oldrec		The old gedcom record
 * @param		string	$new			The new gedcom record
 * @param		string	$fact		The fact that has been changed
 * @param		string	$change_id	The ID that is used for the change
 * @param		string	$change_type	The name of the change
 * @return 	boolean	true if succeed/false if failed
 */
function ReplaceGedrec($gid, $oldrec, $newrec, $fact="", $change_id, $change_type, $gedid="", $gid_type) {
	global $manual_save, $gm_user, $GEDCOMID, $chcache, $can_auto_accept, $aa_attempt;

	// NOTE: Check if auto accept is possible. If there are already changes present for any ID in one $change_id, changes cannot be auto accepted.
	if (!isset($can_auto_accept)) $can_auto_accept = true;
	if ($can_auto_accept) {
		if (HasOtherChanges($gid, $change_id)) {
			// We already have changes for this ID, so we cannot auto accept!
			$can_auto_accept = false;
		}
	}
	// NOTE: Uppercase the ID to make sure it is consistent
	$gid = strtoupper($gid);
	$newrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $newrec);
	$newrec = trim($newrec);
	// NOTE: Determine which gedcom is being updated
	if ($gedid == "") $gedid = $GEDCOMID;
//	else $gedid = $GEDCOMS[$gedid]["id"];
	
	//-- the following block of code checks if the XREF was changed in this record.
	//-- if it was changed we add a warning to the change log
	$ct = preg_match("/0 @(.*)@/", $newrec, $match);
	if ($ct>0) {
		$oldgid = $gid;
		$gid = trim($match[1]);
		if ($oldgid!=$gid) {
			WriteToLog("ReplaceGedrec-> Warning: $oldgid was changed to $gid", "W", "G", $gedid);
		}
	}
	
	// NOTE: Check if there are changes present, if so flag pending changes so they cannot be approved
	if (GetChangeData(true, $gid, true) && ($change_type == "raw_edit" || $change_type == "reorder_families" || $change_type == "reorder_children")) {
		$sql = "select ch_cid as cid from ".TBLPREFIX."changes where ch_gid = '".$gid."' and ch_file = '".$gedid."' order by ch_cid ASC";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$sqlcid = "update ".TBLPREFIX."changes set ch_delete = '1' where ch_cid = '".$row["cid"]."'";
			$rescid = NewQuery($sqlcid);
		}
	}

	$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_file, ch_type, ch_user, ch_time, ch_fact, ch_old, ch_new, ch_gid_type)";
	$sql .= "VALUES ('".$change_id."', '".$gid."', '".$gedid."', '".$change_type."', '".$gm_user->username."', '".time()."'";
	$sql .= ", '".$fact."', '".DbLayer::EscapeQuery($oldrec)."', '".DbLayer::EscapeQuery($newrec)."', '".$gid_type."')";
	$res = NewQuery($sql);
	
	WriteToLog("ReplaceGedrec-> Replacing gedcom record $gid ->" . $gm_user->username ."<-", "I", "G", $gedid);
	// Clear the GetChangeData cache
	ResetChangeCaches();
	return true;
}

//-------------------------------------------- AppendGedrec
//-- this function will append a new gedcom record at
//-- the end of the gedcom file.
function AppendGedrec($newrec, $fact="", $change_id, $change_type, $gedid="") {
	global $manual_save, $gm_user, $GEDCOMID, $chcache;

	$newrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $newrec);
	$newrec = stripslashes(trim($newrec));
	// NOTE: Determine which gedcom is being updated
	if ($gedid == "") $gedid = $GEDCOMID;
	
	$ct = preg_match("/0 @(.*)@\s(\w+)/", $newrec, $match);
	$type = trim($match[2]);
	$xref = GetNewXref($type);
	$_SESSION["last_used"][$type] = JoinKey($xref, $GEDCOMID);
	$newrec = preg_replace("/0 @(.*)@/", "0 @$xref@", $newrec);
	
	$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_file, ch_type, ch_user, ch_time, ch_fact, ch_new, ch_gid_type)";
	$sql .= "VALUES ('".$change_id."', '".$xref."', '".$gedid."', '".$change_type."', '".$gm_user->username."', '".time()."', '".$fact."', '".DbLayer::EscapeQuery($newrec)."', '".$type."')";
	$res = NewQuery($sql);
	WriteToLog("AppendGedrec-> Appending new $type record $xref ->" . $gm_user->username ."<-", "I", "G", $gedid);

	// Clear the GetChangeData cache
	ResetChangeCaches();
	return $xref;
}

//-------------------------------------------- delete_gedrec
//-- this function will delete the gedcom record with
//-- the given $gid
function DeleteGedrec($gid, $change_id, $change_type, $gid_type) {
	global $manual_save, $gm_user, $GEDCOMID, $chcache, $can_auto_accept, $aa_attempt;
	$gid = strtoupper($gid);
	
	// NOTE: Check if auto accept is possible. If there are already changes present for any ID in one $change_id, changes cannot be auto accepted.
	if ($can_auto_accept) {
		if (HasOtherChanges($gid, $change_id)) {
			// We already have changes for this ID, so we cannot auto accept!
			$can_auto_accept = false;
		}
	}
	// NOTE: Check if there are changes present, if so flag pending changes so they cannot be approved
	if (GetChangeData(true, $gid, true)) {
		$sql = "SELECT ch_cid AS cid FROM ".TBLPREFIX."changes WHERE ch_gid = '".$gid."' AND ch_file = '".$GEDCOMID."' ORDER BY ch_cid ASC";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$sqlcid = "UPDATE ".TBLPREFIX."changes SET ch_delete = '1' WHERE ch_cid = '".$row["cid"]."'";
			$rescid = NewQuery($sqlcid);
		}
		// Clear the GetChangeData cache
		ResetChangeCaches();
	}
	
	// NOTE Check if record exists in the database
	if (!FindGedcomRecord($gid) && !GetChangeData(true, $gid, true)) {
		print "DeleteGedrec-> Could not find gedcom record with xref: $gid. <br />";
		WriteToLog("DeleteGedrec-> Could not find gedcom record with xref: $gid ->" . $gm_user->username ."<-", "E", "G", $GEDCOMID);
		return false;
	}
	else {
		$rec = GetChangeData(false, $gid, true, "gedlines","");
		if (isset($rec[$GEDCOMID][$gid])) $oldrec = $rec[$GEDCOMID][$gid];
		else $oldrec = FindGedcomRecord($gid);
		$ct = preg_match("/0 @.*@\s(\w+)\s/", $oldrec, $match);
		$ch_fact = $match[1];
		$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_fact, ch_old, ch_file, ch_type, ch_user, ch_time, ch_gid_type)";
		$sql .= "VALUES ('".$change_id."', '".$gid."', '".$ch_fact."', '".DbLayer::EscapeQuery($oldrec)."', '".$GEDCOMID."', '".$change_type."', '".$gm_user->username."', '".time()."', '".$gid_type."')";
		$res = NewQuery($sql);
		// Also delete the asso recs to an indi, to preserve referential integrity
		if ($ch_fact == "INDI" || $ch_fact == "FAM") {
			$assos = GetAssoList($ch_fact, $gid);
			foreach ($assos as $p1key => $pidassos) {
				foreach ($pidassos as $nothing =>$asso) {
					$pid1 = SplitKey($p1key, "id");
					$pid2 = SplitKey($asso["pid2"], "id");
					$arec = GetChangeData(false, $pid2, true, "gedlines","");
					if (isset($rec[$GEDCOMID][$pid2])) $arec = $rec[$GEDCOMID][$pid2];
					else $arec = FindGedcomRecord($pid2);
					if (strstr($arec, "1 ASSO")) {
						$i = 1;
						do {
							$asubrec = GetSubrecord(1, "1 ASSO @".$pid1."@", $arec, $i);
							if (!empty($asubrec)) {
								$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_fact, ch_old, ch_file, ch_type, ch_user, ch_time, ch_gid_type)";
								$sql .= "VALUES ('".$change_id."', '".$pid2."', 'ASSO', '".DbLayer::EscapeQuery($asubrec)."', '".$GEDCOMID."', '".$change_type."', '".$gm_user->username."', '".time()."', 'INDI')";
							}
							$i++;
						} while (!empty($asubrec));
					}
					if (strstr($arec, "2 ASSO")) {
						$recs = GetAllSubrecords($arec, "CHAN", false, false, false);
						foreach ($recs as $sub => $subrec) {
							if (preg_match("/\n2 ASSO @$pid1@/", $subrec, $match)) {
								$asubrec = GetSubrecord(2, "2 ASSO @".$pid1."@", $subrec);
								$newsubrec = preg_replace($asubrec, "", $subrec);
								$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_fact, ch_old, ch_new, ch_file, ch_type, ch_user, ch_time, ch_gid_type)";
								$sql .= "VALUES ('".$change_id."', '".$pid2."', 'ASSO', '".DbLayer::EscapeQuery($subrec)."', '".DbLayer::EscapeQuery($newsubrec)."', '".$GEDCOMID."', '".$change_type."', '".$gm_user->username."', '".time()."', 'INDI')";
							}
						}
					}
				}
			}
		}
					
			
	}
	WriteToLog("DeleteGedrec-> Deleting gedcom record $gid ->" . $gm_user->username ."<-", "I", "G", $GEDCOMID);
	// Clear the GetChangeData cache
	ResetChangeCaches();
	return true;
}

//-------------------------------------------- check_gedcom
//-- this function will check a GEDCOM record for valid gedcom format
function CheckGedcom($gedrec, $chan=true, $user="", $tstamp="") {
	global $gm_lang, $DEBUG, $GEDCOMID, $gm_user;

	$gedrec = stripslashes($gedrec);
	$ct = preg_match("/0 @(.*)@ (.*)/", $gedrec, $match);
	
	if ($ct==0) {
		$ct2 = preg_match("/0 HEAD/", $gedrec, $match2);
		if ($ct2 == 0) {
			print "CheckGedcom-> Invalid GEDCOM 5.5 format.\n";
			WriteToLog("CheckGedcom-> Invalid GEDCOM 5.5 format.->" . $gm_user->username ."<-", "I", "G", $GEDCOMID);
			return false;
		}
	}
	$gedrec = trim($gedrec);
	if ($chan) {
		if(empty($user)) $user = $gm_user->username;
		if (!empty($tstamp)) {
			$newd = date("d M Y", $tstamp);
			$newt = date("H:i:s", $tstamp);
		}
		else {
			$newd = date("d M Y");
			$newt = date("H:i:s");
		}
		$pos1 = strpos($gedrec, "1 CHAN");
		if ($pos1!==false) {
			$pos2 = strpos($gedrec, "\n1", $pos1+4);
			if ($pos2===false) $pos2 = strlen($gedrec);
			$newgedrec = substr($gedrec, 0, $pos1);
			$newgedrec .= "1 CHAN\r\n2 DATE ".$newd."\r\n";
			$newgedrec .= "3 TIME ".$newt."\r\n";
			$newgedrec .= "2 _GMU ".$user."\r\n";
			$newgedrec .= substr($gedrec, $pos2);
			$gedrec = $newgedrec;
		}
		else if (!isset($ct2)) {
			$newgedrec = "\r\n1 CHAN\r\n2 DATE ".$newd."\r\n";
			$newgedrec .= "3 TIME ".$newt."\r\n";
			$newgedrec .= "2 _GMU ".$user;
			$gedrec .= $newgedrec;
		}
	}
	$gedrec = preg_replace("/([\r\n])+/", "\r\n", $gedrec);
	return $gedrec;
}

/**
 * prints a form to add an individual or edit an individual's name
 *
 * @param string $nextaction	the next action the edit_interface.php file should take after the form is submitted
 * @param string $famid			the family that the new person should be added to
 * @param string $namerec		the name subrecord when editing a name
 * @param string $famtag		how the new person is added to the family
 */
function PrintIndiForm($nextaction, $famid, $linenum="", $namerec="", $famtag="CHIL") {
	global $gm_lang, $pid, $GM_IMAGE_DIR, $GM_IMAGES, $monthtonum, $WORD_WRAPPED_NOTES;
	global $NPFX_accept, $SPFX_accept, $NSFX_accept, $FILE_FORM_accept, $USE_RTL_FUNCTIONS, $change_type;
	global $GEDCOMID, $gm_user;

	init_calendar_popup();
	print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\">\n";
	print "<input type=\"hidden\" name=\"action\" value=\"".$nextaction."\" />\n";
	print "<input type=\"hidden\" name=\"famid\" value=\"".$famid."\" />\n";
	print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
	print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
	print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
	print "<table class=\"facts_table\">";
	
	// preset child/father SURN
	$surn = "";
	if (empty($namerec)) {
		$indirec = "";
		if ($famtag=="CHIL" and $nextaction=="addchildaction") {
			if (!empty($famid) && GetChangeData(true, $famid, true, "", "FAM")) {
				$rec = GetChangeData(false, $famid, true, "gedlines", "FAM");
				$famrec = $rec[$GEDCOMID][$famid];
			}
			else $famrec = FindGedcomRecord($famid);
			$parents = FindParentsInRecord($famrec);
			if (!empty($famid) && !empty($parents["HUSB"]) && GetChangeData(true, $parents["HUSB"], true, "", "INDI")) {
				$rec = GetChangeData(false, $parents["HUSB"], true, "gedlines", "INDI");
				$indirec = $rec[$GEDCOMID][$parents["HUSB"]];
			}
			else $indirec = FindPersonRecord($parents["HUSB"]);
		}
		if ($famtag=="HUSB" and $nextaction=="addnewparentaction") {
			$famrec = FindGedcomRecord($famid);
			$parents = FindParentsInRecord($famrec);
			if (!empty($parents["HUSB"]) && GetChangeData(true, $parents["HUSB"], true, "", "INDI")) {
				$rec = GetChangeData(false, $parents["HUSB"], true, "gedlines", "INDI");
				$indirec = $rec[$GEDCOMID][$parents["HUSB"]];
			}
			else $indirec = FindPersonRecord($parents["HUSB"]);
		}
		if ($famtag=="WIFE" and $nextaction=="addnewparentaction") {
			$famrec = FindGedcomRecord($famid);
			$parents = FindParentsInRecord($famrec);
			if (!empty($parents["WIFE"]) && GetChangeData(true, $parents["WIFE"], true, "", "INDI")) {
				$rec = GetChangeData(false, $parents["WIFE"], true, "gedlines", "INDI");
				$indirec = $rec[$GEDCOMID][$parents["WIFE"]];
			}
			else $indirec = FindPersonRecord($parents["WIFE"]);
		}
		// If the surname is split in SPFX and SURN we get the wrong surname. So, as NAME is most reliable
		// we get it from there
//		$nt = preg_match("/\d SURN (.*)/", $indirec, $ntmatch);
//		if ($nt) $surn = $ntmatch[1];
//		else {
			$nt = preg_match("/1 NAME (.*)[\/](.*)[\/]/", $indirec, $ntmatch);
			if ($nt) $surn = $ntmatch[2];
//		}
		if ($surn) $namerec = "1 NAME  /".trim($surn,"\r\n")."/";
	}
	AddTagSeparator("NAME");
	// handle PAF extra NPFX [ 961860 ]
	$nt = preg_match("/\d NPFX (.*)/", $namerec, $nmatch);
	$npfx=trim(@$nmatch[1]);
	// 1 NAME = NPFX GIVN /SURN/ NSFX
	$nt = preg_match("/\d NAME (.*)/", $namerec, $nmatch);
	$name=@$nmatch[1];
	if (strlen($npfx) and strpos($name, $npfx)===false) $name = $npfx." ".$name;
	AddSimpleTag("0 NAME ".$name);
	// 2 NPFX
	AddSimpleTag("0 NPFX ".$npfx);
	// 2 GIVN
	// Start input field is here
	$nt = preg_match("/\d GIVN (.*)/", $namerec, $nmatch);
	$focusfld = AddSimpleTag("0 GIVN ".@$nmatch[1]);
	// 2 NICK
	$nt = preg_match("/\d NICK (.*)/", $namerec, $nmatch);
	AddSimpleTag("0 NICK ".@$nmatch[1]);
	// 2 SPFX
	$nt = preg_match("/\d SPFX (.*)/", $namerec, $nmatch);
	AddSimpleTag("0 SPFX ".@$nmatch[1]);
	// 2 SURN
	$nt = preg_match("/\d SURN (.*)/", $namerec, $nmatch);
	AddSimpleTag("0 SURN ".@$nmatch[1]);
	// 2 NSFX
	$nt = preg_match("/\d NSFX (.*)/", $namerec, $nmatch);
	AddSimpleTag("0 NSFX ".@$nmatch[1]);
	// 2 _HEB
	$nt = preg_match("/\d _HEB (.*)/", $namerec, $nmatch);
	if ($nt>0 || $USE_RTL_FUNCTIONS) {
		AddSimpleTag("0 _HEB ".@$nmatch[1]);
	}
	// 2 ROMN
	$nt = preg_match("/\d ROMN (.*)/", $namerec, $nmatch);
	AddSimpleTag("0 ROMN ".@$nmatch[1]);

	if ($surn) $namerec = ""; // reset if modified

	if (empty($namerec)) {
		// 2 _MARNM
		AddSimpleTag("0 _MARNM");
		// 1 SEX
		AddTagSeparator("SEX");
		if ($famtag=="HUSB") AddSimpleTag("0 SEX M");
		else if ($famtag=="WIFE") AddSimpleTag("0 SEX F");
		else AddSimpleTag("0 SEX");
		// 1 BIRT
		// 2 DATE
		// 2 PLAC
		AddTagSeparator("BIRT");
		AddSimpleTag("0 BIRT");
		AddSimpleTag("0 DATE", "BIRT");
		AddSimpleTag("0 TIME", "BIRT");
		AddSimpleTag("0 PLAC", "BIRT");
		// 1 CHR
		// 2 DATE
		// 2 PLAC
		AddTagSeparator("CHR");
		AddSimpleTag("0 CHR");
		AddSimpleTag("0 DATE", "CHR");
		AddSimpleTag("0 TIME", "CHR");
		AddSimpleTag("0 PLAC", "CHR");
		// 1 DEAT
		// 2 DATE
		// 2 PLAC
		AddTagSeparator("DEAT");
		AddSimpleTag("0 DEAT");
		AddSimpleTag("0 DATE", "DEAT");
		AddSimpleTag("0 TIME", "DEAT");
		AddSimpleTag("0 PLAC", "DEAT");
		// print $famtag." ".$nextaction;
//		if (($famtag=="CHIL" and $nextaction=="addchildaction") || (($famtag == "HUSB" || $famtag == "WIFE") && $nextaction == "addnewparentaction")) 
		if ($famtag=="CHIL" and $nextaction=="addchildaction" and !empty($famid)) {
			AddTagSeparator("PEDI");
			AddSimpleTag("0 PEDI");
		}
		//-- if adding a spouse add the option to add a marriage fact to the new family
		if ($nextaction=='addspouseaction' || ($nextaction=='addnewparentaction' && $famid!='new')) {
			print "\n";
			AddTagSeparator("MARR");
			AddSimpleTag("0 MARR");
			AddSimpleTag("0 DATE", "MARR");
			AddSimpleTag("0 PLAC", "MARR");
		}
		print "</table>\n";
		PrintAddLayer("SOUR", 1, true);
		PrintAddLayer("OBJE", 1);
		PrintAddLayer("NOTE", 1);
		PrintAddLayer("GNOTE", 1);
//		print "<input type=\"checkbox\" name=\"addsource\" />".$gm_lang["add_source_to_fact"]."<br />\n";
		if ($gm_user->UserCanAccept() && !$gm_user->userAutoAccept()) print "<br /><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."<br /><br />\n";
		print "<input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
	}
	else {
		if ($namerec!="NEW") {
			$gedlines = split("\n", $namerec);	// -- find the number of lines in the record
			$fields = preg_split("/\s/", $gedlines[0]);
			$glevel = $fields[0];
			$level = $glevel;
			$type = trim($fields[1]);
			$level1type = $type;
			$tags=array();
			$i = 0;
			$namefacts = array("NPFX", "GIVN", "NICK", "SPFX", "SURN", "NSFX", "NAME", "_HEB", "ROMN");
			do {
				if (!in_array($type, $namefacts)) {
					$text = "";
					for($j=2; $j<count($fields); $j++) {
						if ($j>2) $text .= " ";
						$text .= $fields[$j];
					}
					$iscont = false;
					while(($i+1<count($gedlines))&&(preg_match("/".($level+1)." (CON[CT])\s?(.*)/", $gedlines[$i+1], $cmatch)>0)) {
						$iscont=true;
						if ($cmatch[1]=="CONT") $text.="\r\n";
						if ($WORD_WRAPPED_NOTES) $text .= " ";
						$text .= $cmatch[2];
						$i++;
					}
					AddSimpleTag($level." ".$type." ".$text);
				}
				$tags[]=$type;
				$i++;
				if (isset($gedlines[$i])) {
					$fields = preg_split("/\s/", $gedlines[$i]);
					$level = $fields[0];
					if (isset($fields[1])) $type = trim($fields[1]);
				}
			} while (($level>$glevel)&&($i<count($gedlines)));
		}
		// 2 _MARNM
		AddSimpleTag("0 _MARNM");
		print "</tr>\n";
		print "</table>\n";
		PrintAddLayer("SOUR");
		PrintAddLayer("NOTE");
		PrintAddLayer("GNOTE");
//		print "<input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
		if ($gm_user->UserCanAccept() && !$gm_user->userAutoAccept()) print "<br /><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."<br /><br />\n";
		print "<input type=\"button\" value=\"".$gm_lang["save"]."\" onclick=\"document.addchildform.submit(); return false;\"/><br />\n";
	}
	print "</form>\n";
	if ($nextaction != "update") print "<br /><div id=\"show_ids\"><a href=\"javascript: ".$gm_lang["show_next_id"]."\" onclick=\"sndReq('show_ids', 'getnextids'); return false;\">".$gm_lang["show_next_id"]."</a></div>";
	?>
	<script type="text/javascript" src="autocomplete.js"></script>
	<script type="text/javascript">
	<!--
	//	copy php arrays into js arrays
	var npfx_accept = new Array(<?php foreach ($NPFX_accept as $indexval => $npfx) print "'".$npfx."',"; print "''";?>);
	var spfx_accept = new Array(<?php foreach ($SPFX_accept as $indexval => $spfx) print "'".$spfx."',"; print "''";?>);
	Array.prototype.in_array = function(val) {
		for (var i in this) {
			if (this[i] == val) return true;
		}
		return false;
	}
	function trim(str) {
		return str.replace(/(^\s*)|(\s*$)/g,'');
	}
	function updatewholename() {
		frm = document.forms[0];
		var npfx=trim(frm.NPFX.value);
		if (npfx) npfx+=" ";
		var givn=trim(frm.GIVN.value);
		var spfx=trim(frm.SPFX.value);
		if (spfx) spfx+=" ";
		var surn=trim(frm.SURN.value);
		var nsfx=trim(frm.NSFX.value);
		frm.NAME.value = npfx + givn + " /" + spfx + surn + "/ " + nsfx;
	}
	function togglename() {
		frm = document.forms[0];

		// show/hide NAME
		var ronly = frm.NAME.readOnly;
		if (ronly) {
			updatewholename();
			frm.NAME.readOnly=false;
			frm.NAME_spec.style.display="inline";
			frm.NAME_plus.style.display="inline";
			frm.NAME_minus.style.display="none";
			disp="none";
		}
		else {
			// split NAME = (NPFX) GIVN / (SPFX) SURN / (NSFX)
			var name=frm.NAME.value+'//';
			var name_array=name.split("/");
			var givn=trim(name_array[0]);
			var givn_array=givn.split(" ");
			var surn=trim(name_array[1]);
			var surn_array=surn.split(" ");
			var nsfx=trim(name_array[2]);

			// NPFX
			var npfx='';
			do {
				search=givn_array[0]; // first word
				search=search.replace(/(\.*$)/g,''); // remove trailing '.'
				if (npfx_accept.in_array(search)) npfx+=givn_array.shift()+' ';
				else break;
			} while (givn_array.length>0);
			frm.NPFX.value=trim(npfx);

			// GIVN
			frm.GIVN.value=trim(givn_array.join(' '));

			// SPFX
			var spfx='';
			do {
				search=surn_array[0]; // first word
				search=search.replace(/(\.*$)/g,''); // remove trailing '.'
				if (spfx_accept.in_array(search)) spfx+=surn_array.shift()+' ';
				else break;
			} while (surn_array.length>0);
			frm.SPFX.value=trim(spfx);

			// SURN
			frm.SURN.value=trim(surn_array.join(' '));

			// NSFX
			frm.NSFX.value=trim(nsfx);

			// NAME
			frm.NAME.readOnly=true;
			frm.NAME_spec.style.display="none";
			frm.NAME_plus.style.display="none";
			frm.NAME_minus.style.display="inline";
			disp="table-row";
			if (document.all) disp="inline"; // IE
		}
		// show/hide
		document.getElementById("NPFX_tr").style.display=disp;
		document.getElementById("GIVN_tr").style.display=disp;
		document.getElementById("NICK_tr").style.display=disp;
		document.getElementById("SPFX_tr").style.display=disp;
		document.getElementById("SURN_tr").style.display=disp;
		document.getElementById("NSFX_tr").style.display=disp;
	}
	function checkform() {
		frm = document.addchildform;
		/* if (frm.GIVN.value=="") {
			alert('<?php print $gm_lang["must_provide"]; print $gm_lang["given_name"]; ?>');
			frm.GIVN.focus();
			return false;
		}
		if (frm.SURN.value=="") {
			alert('<?php print $gm_lang["must_provide"]; print $gm_lang["surname"]; ?>');
			frm.SURN.focus();
			return false;
		}*/
		var fname=frm.NAME.value;
		fname=fname.replace(/ /g,'');
		fname=fname.replace(/\//g,'');
		if (fname=="") {
			alert('<?php print $gm_lang["must_provide"]; print " ".GM_FACT_NAME; ?>');
			frm.NAME.focus();
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php
	// force name expand on form load (maybe optional in a further release...)
	print "<script type='text/javascript'><!--\ntogglename(); document.getElementById('".$focusfld."').focus();\n//-->\n</script>";
}

/**
 * generates javascript code for calendar popup in user's language
 *
 * @param string id		form text element id where to return date value
 * @see init_calendar_popup()
 */
function PrintCalendarPopup($id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	// calendar button
	$text = $gm_lang["select_date"];
	if (isset($GM_IMAGES["calendar"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["button"]."\" name=\"img".$id."\" id=\"img".$id."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print "<a href=\"javascript: ".$text."\" onclick=\"cal_toggleDate('caldiv".$id."', '".$id."'); return false;\">";
	print $Link;
	print "</a>\n";
	print "<div id=\"caldiv".$id."\" style=\"position:absolute;visibility:hidden;background-color:white;layer-background-color:white;\"></div>\n";
}

function AddTagSeparator($fact="") {
	global $gm_lang;
	
	print "<tr><td colspan=\"2\" class=\"shade3 center\">";
	if(!empty($fact)) {
		if (isset($gm_lang[$fact])) print $gm_lang[$fact];
		else if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
		else print $fact;
	}
	print "</td></tr>";
}


/**
 * add a new tag input field
 *
 * called for each fact to be edited on a form.
 * Fact level=0 means a new empty form : data are POSTed by name
 * else data are POSTed using arrays :
 * glevels[] : tag level
 *  islink[] : tag is a link
 *     tag[] : tag name
 *    text[] : tag value
 *
 * @param string $tag			fact record to edit (eg 2 DATE xxxxx)
 * @param string $upperlevel	optional upper level tag (eg BIRT)
 */
function AddSimpleTag($tag, $upperlevel="", $tab="1") {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $MEDIA_DIRECTORY, $TEMPLE_CODES, $STATUS_CODES, $REPO_ID_PREFIX, $SPLIT_PLACES;
	global $assorela, $tags, $emptyfacts, $TEXT_DIRECTION, $confighelpfile, $GM_BASE_DIRECTORY, $GEDCOMID;
	global $NPFX_accept, $SPFX_accept, $NSFX_accept, $FILE_FORM_accept, $upload_count, $separatorfacts, $canhavey_facts;
	static $tabkey;
	if (!isset($tabkey)) $tabkey = $tab;
	
	@list($level, $fact, $value) = explode(" ", $tag);
// 	print "adding ".$tag." at level ".$level." upperlevel ".$upperlevel.", fact is ". $fact." value is ".nl2br($value)."<br />";

	$largetextfacts = array("TEXT","PUBL","NOTE");
	$subnamefacts = array("NPFX", "GIVN", "NICK", "SPFX", "SURN", "NSFX");


	// element name : used to POST data
	if ($upperlevel) $element_name=$upperlevel."_".$fact; // ex: BIRT_DATE | DEAT_DATE | ...
	else if ($level==0) $element_name=$fact; // ex: OCCU
	else $element_name="text[]";

	// element id : used by javascript functions
	if ($level==0) $element_id=$fact; // ex: NPFX | GIVN ...
	else $element_id=$fact.floor(microtime()*1000000); // ex: SOUR56402
	if ($upperlevel) $element_id=$upperlevel."_".$fact; // ex: BIRT_DATE | DEAT_DATE ...

	// field value
	$islink = (substr($value,0,1)=="@" && substr($value,0,2)!="@#");
	if ($islink) {
		$value=trim(trim($value), "@");
	}
	else $value = rtrim(substr($tag, strlen($fact)+3));
	
	// rows & cols
	$rows=1;
	$cols=60;
	if ($islink) $cols=10;
	else if ($fact=="NCHI" || $fact == "NMR") $cols=3;
	else if ($fact=="FORM") $cols=5;
	else if ($fact=="DATE" or $fact=="TIME" or $fact=="TYPE" or $fact=="PEDI") $cols=20;
	else if ($fact=="LATI" or $fact=="LONG") $cols=12;
	else if (in_array($fact, $subnamefacts)) $cols=25;
	else if (in_array($fact, $largetextfacts)) { $rows=10; $cols=70; }
	else if ($fact=="ADDR") $rows=5;
	else if ($fact=="REPO") $cols = strlen($REPO_ID_PREFIX) + 4;

	// label
	if (in_array($fact, $separatorfacts) && $level <= 2) AddTagSeparator($fact, $islink);
	$style="";
	print "<tr id=\"".$element_id."_tr\" ";
	if (in_array($fact, $subnamefacts)) print " style=\"display:none;\""; // hide subname facts
	print " >\n";
	// NOTE: Help link
	if (!in_array($fact, $emptyfacts) || in_array($fact, $canhavey_facts)) {
		print "<td class=\"shade2 $TEXT_DIRECTION\">";
		if ($fact=="DATE") print_help_link("def_gedcom_date_help", "qm", "date");
		else if ($fact=="RESN") print_help_link($fact."_help", "qm");
		else print_help_link("edit_".$fact."_help", "qm");
		if (isset($gm_lang[$fact])) print $gm_lang[$fact];
		else if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
		else print $fact;
		print "\n";
		print "\n</td>";
	}
	
	// NOTE: Tag level
	if ($level>0) {
		print "<input type=\"hidden\" name=\"glevels[]\" value=\"".$level."\" />\n";
		print "<input type=\"hidden\" name=\"islink[]\" value=\"".($islink)."\" />\n";
		print "<input type=\"hidden\" name=\"tag[]\" value=\"".$fact."\" />\n";
	}
	
	// NOTE: value
	if (!in_array($fact, $emptyfacts) || in_array($fact, $canhavey_facts)) print "<td class=\"shade1\">\n";
	// NOTE: Retrieve linked NOTE
	// we must disable editing for this field if the note already has changes.
	$disable_edit = false;
	if(in_array($fact, $emptyfacts) && !in_array($fact, $canhavey_facts)) print "<input type=\"hidden\" name=\"text[]\" value=\"\" />\n"; 
	if (in_array($fact, $canhavey_facts)&& (empty($value) || $value=="y" || $value=="Y")) {
		$value = strtoupper($value);
		//-- don't default anything to Y when adding events through people
		//-- default to Y when specifically adding one of these events
		if ($level==1) $value="Y"; // default YES
		print "<input type=\"hidden\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".$value."\" />";
		if ($level<=1) {
			print "<input type=\"checkbox\" tabindex=\"".$tabkey."\"";
			$tabkey++;
			if ($value=="Y") print " checked=\"checked\"";
			print " onClick=\"if (this.checked) ".$element_id.".value='Y'; else ".$element_id.".value=''; \" />";
			print $gm_lang["yes"];
		}
	}
	// Added
	else if ($fact=="TEMP") {
		print "<select tabindex=\"".$tabkey."\" name=\"".$element_name."\" >\n";
		print "<option value=''>".$gm_lang["no_temple"]."</option>\n";
		foreach($TEMPLE_CODES as $code=>$temple) {
			print "<option value=\"$code\"";
			if ($code==$value) print " selected=\"selected\"";
			print ">$temple</option>\n";
		}
		print "</select>\n";
	}
	else if ($fact=="STAT") {
		print "<select tabindex=\"".$tabkey."\" name=\"".$element_name."\" >\n";
		print "<option value=''>No special status</option>\n";
		foreach($STATUS_CODES as $code=>$status) {
			print "<option value=\"$code\"";
			if ($code==$value) print " selected=\"selected\"";
			print ">$status</option>\n";
		}
		print "</select>\n";
	}
	else if ($fact=="RELA") {
		$text=strtolower($value);
		// add current relationship if not found in default list
		if (!array_key_exists($text, $assorela)) $assorela[$text]=$text;
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" >\n";
		foreach ($assorela as $key=>$value) {
			print "<option value=\"". $key . "\"";
			if ($key==$text) print " selected=\"selected\"";
			print ">" . $assorela["$key"] . "</option>\n";
		}
		print "</select>\n";
	}
	else if ($fact=="RESN") {
		print "<table><tr valign=\"top\">\n";
		foreach (array("none", "locked", "privacy", "confidential") as $resn_index => $resn_val) {
			if ($resn_val=="none") $resnv=""; else $resnv=$resn_val;
			print "<td><input tabindex=\"".$tabkey."\" type=\"radio\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".$resnv."\"";
			if ($value==$resnv) print " checked=\"checked\"";
			print " /><small>".$gm_lang[$resn_val]."</small>";
			print "</td>\n";
		}
		print "</tr></table>\n";
	}
	else if ($fact=="_PRIM" or $fact=="_THUM" or $fact=="_SSHOW" or $fact=="_SCBK") {
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" >\n";
		print "<option value=\"\"></option>\n";
		print "<option value=\"Y\"";
		if ($value=="Y") print " selected=\"selected\"";
		print ">".$gm_lang["yes"]."</option>\n";
		print "<option value=\"N\"";
		if ($value=="N") print " selected=\"selected\"";
		print ">".$gm_lang["no"]."</option>\n";
		print "</select>\n";
	}
	else if ($fact=="SEX") {
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\">\n<option value=\"M\"";
		if ($value=="M") print " selected=\"selected\"";
		print ">".$gm_lang["male"]."</option>\n<option value=\"F\"";
		if ($value=="F") print " selected=\"selected\"";
		print ">".$gm_lang["female"]."</option>\n<option value=\"U\"";
		if ($value=="U" || empty($value)) print " selected=\"selected\"";
		print ">".$gm_lang["unknown"]."</option>\n</select>\n";
	}
	else if ($fact=="PEDI") {
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\">\n";
		
		print "<option value=\"birth\"";
		if ($value=="birth") print " selected=\"selected\"";
		print ">".$gm_lang["biological"]."</option>\n";
		
		print "<option value=\"adopted\"";
		if ($value=="adopted") print " selected=\"selected\"";
		print ">".$gm_lang["adopted"]."</option>\n";

		print "<option value=\"foster\"";
		if ($value=="foster") print " selected=\"selected\"";
		print ">".$gm_lang["foster"]."</option>\n";
		
		print "<option value=\"sealing\"";
		if ($value=="sealing") print " selected=\"selected\"";
		print ">".$gm_lang["sealing"]."</option>\n";
		
		print "</select>\n";
	}
	else if ($fact == "TYPE" && $level == '3') {?>
		<select name="text[]">
		<option selected="selected" value=""> <?php print $gm_lang["choose"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_audio"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_audio"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_book"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_book"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_card"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_card"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_electronic"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_electronic"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_fiche"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_fiche"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_film"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_film"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_magazine"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_magazine"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_manuscript"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_manuscript"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_map"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_map"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_newspaper"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_newspaper"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_photo"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_photo"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_tombstone"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_tombstone"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_video"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_video"]; ?> </option>
		</select>
		<?php
	}
	else if ($fact=="QUAY") {
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\">\n";
		print "<option value=\"\"";
			if ($value=="") print " selected=\"selected\"";
		print "></option>\n";
		for ($number = 0; $number < 4; $number++) {
			print "<option value=\"".$number."\"";
			if ($value==$number) print " selected=\"selected\"";
			print ">".$number."</option>\n";
		}
		print "</select>\n";
	}
	else {
		// textarea
		if ($rows>1 && !($fact == "NOTE" && $islink)) {
			if ($disable_edit) {
				print "<textarea tabindex=\"".$tabkey."\" rows=\"".$rows."\" cols=\"".$cols."\" disabled=\"disabled\">".$value."</textarea>\n";
				print "<input type=\"hidden\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".$value."\" />";
			}
			else {
				// The \n before the value is a workaround for stupid browsers not handling the starting newline correctly.
				print "<textarea tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" rows=\"".$rows."\" cols=\"".$cols."\">"."\n".$value."</textarea>\n";
			}
		}
		// text
		else {
			if (!in_array($fact, $emptyfacts)) {
				print "<input tabindex=\"".$tabkey."\" type=\"text\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".htmlspecialchars($value)."\" size=\"".$cols."\" dir=\"ltr\"";
				if ($fact=="NPFX") print " onkeyup=\"wactjavascript_autoComplete(npfx_accept,this,event)\" autocomplete=\"off\" ";
				if (in_array($fact, $subnamefacts)) print " onchange=\"updatewholename();\"";
				if ($fact=="DATE") print " onblur=\"valid_date(this); sndReq('".$element_id."_date', 'getchangeddate', 'date', this.value, '', '');\"";
				if ($fact=="EMAIL") print " onblur=\"sndReq('".$element_id."_email', 'checkemail', 'email', this.value, '', '');\"";
				if ($fact == "SOUR") print " onblur=\"sndReq('".$element_id."_src', 'getsourcedescriptor', 'sid', this.value, '', '');\"";
				if ($fact == "REPO") print " onblur=\"sndReq('".$element_id."_repo', 'getrepodescriptor', 'rid', this.value, '', '');\"";
				if ($fact == "ASSO") print " onblur=\"sndReq('".$element_id."_asso', 'getpersonname', 'pid', this.value, '', '');\"";
				if ($fact == "NOTE") print " onblur=\"sndReq('".$element_id."_gnote', 'getnotedescriptor', 'oid', this.value, '', '');\"";
				if ($fact == "OBJE") print " onblur=\"sndReq('".$element_id."_obj', 'getmediadescriptor', 'mid', this.value, '', '');\"";
				print " />\n";
			}
		}
		// split PLAC
		if ($fact=="PLAC") {
			print "<div id=\"".$element_id."_pop\" style=\"display: inline;\">\n";
			LinkFunctions::PrintSpecialCharLink($element_id);
			LinkFunctions::PrintFindPlaceLink($element_id);
			print "</div>\n";
			if ($SPLIT_PLACES) {
				if (!function_exists("print_place_subfields")) require("includes/functions/functions_places.php");
				print_place_subfields($element_id);
			}
		}
		else if ($cols>20 && $fact!="NPFX" && !in_array($fact, $emptyfacts)) {
			if (in_array($fact, array("GIVN", "SURN", "NPSX"))) LinkFunctions::PrintSpecialCharLink($element_id);
			else LinkFunctions::PrintSpecialCharLink($element_id);
		}
	}
	// MARRiage TYPE : hide text field and show a selection list
	if ($fact=="TYPE" and $tags[0]=="MARR") {
		print "<script type='text/javascript'><!--\n";
		print "document.getElementById('".$element_id."').style.display='none'";
		print "\n//-->\n</script>";
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."_sel\" onchange=\"document.getElementById('".$element_id."').value=this.value;\" >\n";
		foreach (array("Unknown", "Civil", "Religious", "Partners") as $indexval => $key) {
			if ($key=="Unknown") print "<option value=\"\"";
			else print "<option value=\"".$key."\"";
			$a=strtolower($key);
			$b=strtolower($value);
			if (@strpos($a, $b)!==false or @strpos($b, $a)!==false) print " selected=\"selected\"";
			print ">".constant("GM_FACT_MARR_".strtoupper($key))."</option>\n";
		}
		print "</select>";
	}

	// popup links
	if ($fact=="DATE") PrintCalendarPopup($element_id);
	if ($fact=="FAMC") LinkFunctions::PrintFindFamilyLink($element_id);
	if ($fact=="FAMS") LinkFunctions::PrintFindFamilyLink($element_id);
	if ($fact=="ASSO") LinkFunctions::PrintFindIndiLink($element_id,"");
	if ($fact=="FILE") LinkFunctions::PrintFindMediaLink($element_id);
	if ($fact=="OBJE" && $islink) {
		LinkFunctions::PrintFindObjectLink($element_id);
		LinkFunctions::PrintAddNewObjectLink($element_id);
	}
	if ($fact=="SOUR") {
		LinkFunctions::PrintFindSourceLink($element_id);
		LinkFunctions::PrintAddNewSourceLink($element_id);
	}
	if ($fact=="REPO") {
		LinkFunctions::PrintFindRepositoryLink($element_id);
		LinkFunctions::PrintAddNewRepositoryLink($element_id);
	}
	if ($fact=="NOTE" && $islink) {
		LinkFunctions::PrintFindNoteLink($element_id);
		LinkFunctions::PrintAddNewGNoteLink($element_id);
	}

	// current value
	if ($fact=="DATE") print " <span id=\"".$element_id."_date\">".GetChangedDate($value)."</span>";
	if ($fact=="REPO") {
		print " <span id=\"".$element_id."_repo\">";
		if ($value) print GetRepoDescriptor($value)." (".$value.")";
		else {
			if (isset($_SESSION["last_used"]["REPO"])) {
				$gedid = SplitKey($_SESSION["last_used"]["REPO"], "gedid");
				if ($gedid == $GEDCOMID) {
					$id = SplitKey($_SESSION["last_used"]["REPO"], "id");
					if (CheckExists($id, "REPO")) print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_repo', 'getrepodescriptor', 'rid', '".$id."', '', ''); return false;\">".$gm_lang["click_for"]." ".GetRepoDescriptor($id)."</a>";
				}
			}
		}
		print "</span>";
		print "<br />";
	}
	if ($fact=="EMAIL") print " <span id=\"".$element_id."_email\"></span>";
	if ($fact=="ASSO") {
		print " <span id=\"".$element_id."_asso\">";
		if ($value) print GetPersonName($value)." (".$value.")";
		print "</span>";
	}
	if ($fact=="NOTE" && $islink) {
		print " <span id=\"".$element_id."_gnote\">";
		if ($value) {
			print "</span>";
			?>
			<script>
			<!--
			sndReq('<?php print $element_id."_gnote";?>', 'getnotedescriptor', 'oid', '<?php print $value;?>', '', '');
			//-->
			</script>
		<?php
		}
		else {
			if (isset($_SESSION["last_used"]["NOTE"])) {
				$gedid = SplitKey($_SESSION["last_used"]["NOTE"], "gedid");
				if ($gedid == $GEDCOMID) {
					$id = SplitKey($_SESSION["last_used"]["NOTE"], "id");
					if (CheckExists($id, "NOTE")) {
						print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_gnote', 'getnotedescriptor', 'oid', '".$id."', '', ''); return false;\">".$gm_lang["click_for"]." ";
						print "<span id=\"".$element_id."_gnote2\"></span></a>";
						?>
						<script>
						<!--
						sndReq('<?php print $element_id."_gnote2";?>', 'getnotedescriptor', 'oid', '<?php print $id;?>', '', '');
						//-->
						</script>
						<?php
					}
				}
			}
			print "</span>";
		}
	}
	if ($fact=="SOUR") {
		print " <span id=\"".$element_id."_src\">";
		if ($value) print GetSourceDescriptor($value)." (".$value.")";
		else {
			if (isset($_SESSION["last_used"]["SOUR"])) {
				$gedid = SplitKey($_SESSION["last_used"]["SOUR"], "gedid");
				if ($gedid == $GEDCOMID) {
					$id = SplitKey($_SESSION["last_used"]["SOUR"], "id");
					if (CheckExists($id, "SOUR")) print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_src', 'getsourcedescriptor', 'sid', '".$id."', '', ''); return false;\">".$gm_lang["click_for"]." ".GetSourceDescriptor($id)."</a>";
				}
			}
		}
		print "</span>";
	}

	if ($fact=="OBJE") {
		print " <span id=\"".$element_id."_obj\">";
		if ($value) print GetMediaDescriptor($value)." (".$value.")";
		else {
			if (isset($_SESSION["last_used"]["OBJE"])) {
				$gedid = SplitKey($_SESSION["last_used"]["OBJE"], "gedid");
				if ($gedid == $GEDCOMID) {
					$id = SplitKey($_SESSION["last_used"]["OBJE"], "id");
					if (CheckExists($id, "OBJE")) print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_obj', 'getmediadescriptor', 'mid', '".$id."', '', ''); return false;\">".$gm_lang["click_for"]." ".GetMediaDescriptor($id)."</a>";
				}
			}
		}
		print "</span>";
	}
	
	// pastable values
	if ($fact=="NPFX") {
		$text = $gm_lang["autocomplete"];
		if (isset($GM_IMAGES["autocomplete"]["button"])) $Link = "<img id=\"".$element_id."_spec\" name=\"".$element_id."_spec\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["autocomplete"]["button"]."\"  alt=\"".$text."\"  title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print "&nbsp;".$Link;
	}
	if ($fact=="SPFX") LinkFunctions::PrintAutoPasteLink($element_id, $SPFX_accept);
	if ($fact=="NSFX") LinkFunctions::PrintAutoPasteLink($element_id, $NSFX_accept);
	if ($fact=="FORM") LinkFunctions::PrintAutoPasteLink($element_id, $FILE_FORM_accept, false, false);

	// split NAME
	if ($fact=="NAME") {
		print "&nbsp;<a href=\"javascript: ".$gm_lang["show_details"]."\" onclick=\"togglename(); return false;\"><img id=\"".$element_id."_plus\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /></a>\n";
		print "<a href=\"javascript: ".$gm_lang["show_details"]."\" onclick=\"togglename(); return false;\"><img style=\"display:none;\" id=\"".$element_id."_minus\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /></a>\n";
	}
	
	if (!in_array($fact, $emptyfacts) || in_array($fact, $canhavey_facts)) print "</td>";
	print "</tr>\n";
	$tabkey++;
	return $element_id;
}

/**
 * prints collapsable fields to add ASSO/RELA, SOUR, OBJE ...
 *
 * @param string $tag		Gedcom tag name
 */
function PrintAddLayer($tag, $level=2, $addfact=false) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $TEXT_DIRECTION;
	global $MEDIA_DIRECTORY;

	if ($tag=="SOUR") {
		//-- Add new source to fact
		print "<a href=\"#\" onclick=\"expand_layer('newsource'); if(document.getElementById('newsource').style.display == 'block') document.getElementById(addsourcefocus).focus(); return false;\"><img id=\"newsource_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_source"]."</a>";
		print_help_link("edit_add_SOUR_help", "qm");
		print "<br />";
		print "<div id=\"newsource\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 SOUR
		$sour_focus_element = AddSimpleTag("$level SOUR @");
		?>
		<script type="text/javascript">
		<!--
		var addsourcefocus = <?php print "'".$sour_focus_element."'"; ?>;
		//-->
		</script>
		<?php
		// 3 PAGE
		AddSimpleTag(($level+1)." PAGE");
		// 3 DATA
		AddSimpleTag(($level+1)." DATA");
		// 4 DATE
		AddSimpleTag(($level+2)." DATE");
		// 4 TEXT
		AddSimpleTag(($level+2)." TEXT");
		if ($addfact) {
			print "<tr><td colspan=\"2\" class=\"shade2 $TEXT_DIRECTION\"><input type=\"radio\" name=\"addsource\" value=\"0\" />".$gm_lang["add_source_citation_to_person"]."<br />\n";
			print "<input type=\"radio\" name=\"addsource\" value=\"1\" />".$gm_lang["add_source_reference_to_fact"]."<br />\n";
			print "<input type=\"radio\" name=\"addsource\" value=\"2\" checked=\"checked\" />".$gm_lang["add_source_citation_to_fact"]."<br />\n";
			print "</td></tr>";
		}
		print "</table></div>";
	}
	if ($tag=="ASSO") {
		//-- Add a new ASSOciate
		print "<a href=\"#\" onclick=\"expand_layer('newasso'); if(document.getElementById('newasso').style.display == 'block') document.getElementById(addassofocus).focus(); return false;\"><img id=\"newasso_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_asso"]."</a>";
		print_help_link("edit_add_ASSO_help", "qm");
		print "<br />";
		print "<div id=\"newasso\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 ASSO
		$asso_focus_element = AddSimpleTag(($level)." ASSO @");
		?>
		<script type="text/javascript">
		<!--
		var addassofocus = <?php print "'".$asso_focus_element."'"; ?>;
		//-->
		</script>
		<?php
		// 3 RELA
		AddSimpleTag(($level+1)." RELA");
		// 3 NOTE
		AddSimpleTag(($level+1)." NOTE");
		print "</table></div>";
	}
	if ($tag=="NOTE") {
		//-- Add new note to fact
		print "<a href=\"#\" onclick=\"expand_layer('newnote'); if(document.getElementById('newnote').style.display == 'block') document.getElementById(addnotefocus).focus(); return false;\"><img id=\"newnote_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_note"]."</a>";
		print_help_link("edit_add_NOTE_help", "qm");
		print "<br />\n";
		print "<div id=\"newnote\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 NOTE
		$note_focus_element = AddSimpleTag(($level)." NOTE");
		?>
		<script type="text/javascript">
		<!--
		var addnotefocus = <?php print "'".$note_focus_element."'"; ?>;
		//-->
		</script>
		<?php
		print "</table></div>";
	}
	if ($tag=="GNOTE") {
		//-- Add new general note to fact
		print "<a href=\"#\" onclick=\"expand_layer('newgnote'); if(document.getElementById('newgnote').style.display == 'block') document.getElementById(addgnotefocus).focus(); return false;\"><img id=\"newnote_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_gnote"]."</a>";
		print_help_link("edit_add_NOTE_help", "qm");
		print "<br />";
		print "<div id=\"newgnote\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 NOTE
		$gnote_focus_element = AddSimpleTag("$level NOTE @");
		?>
		<script type="text/javascript">
		<!--
		var addgnotefocus = <?php print "'".$gnote_focus_element."'"; ?>;
		//-->
		</script>
		<?php
		print "</table></div>";
	}
	if ($tag=="OBJE") {
		//-- Add new obje to fact
		print "<a href=\"#\" onclick=\"expand_layer('newobje'); if(document.getElementById('newobje').style.display == 'block') document.getElementById(addobjefocus).focus(); return false;\"><img id=\"newobje_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_obje"]."</a>";
		print_help_link("add_media_help", "qm");
		print "<br />";
		print "<div id=\"newobje\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 OBJE <=== as link
		$obje_focus_element = AddSimpleTag(($level)." OBJE @");
		?>
		<script type="text/javascript">
		<!--
		var addobjefocus = <?php print "'".$obje_focus_element."'"; ?>;
		//-->
		</script>
		<?php
		// 2 OBJE <=== as embedded new object
//		AddSimpleTag(($level)." OBJE");
		// 3 FORM
//		AddSimpleTag(($level+1)." FORM");
		// 3 FILE
//		AddSimpleTag(($level+1)." FILE");
		// 3 TITL
//		AddSimpleTag(($level+1)." TITL");
		if ($level==1) {
			// 3 _PRIM
			AddSimpleTag(($level+1)." _PRIM");
			// 3 _THUM
			AddSimpleTag(($level+1)." _THUM");
		}
		print "</table></div>";
	}
}

/**
 * Add new gedcom lines from interface update arrays
 * @param string $newged	the new gedcom record to add the lines to
 * @return string	The updated gedcom record
 */
function HandleUpdates($newged) {
	global $glevels, $islink, $tag, $uploaded_files, $text, $NOTE, $change_id, $change_type, $success, $can_auto_accept, $gm_lang, $link_error;
	
	$link_error = false;
//	print_r($_POST);
	// NOTE: Cleanup text fields
	foreach ($text as $key => $line) {
		$text[$key] = trim($line);
	}
	for($j=0; $j<count($glevels); $j++) {
		//print "value j: ".$j."<br />";
		// NOTE: update external note records first
		
		// print "value j:".$j."glevels: ".$glevels[$j]." tag: ".$tag[$j]." text: ".$text[$j]." strl: ".strlen($text[$j])." islink: ".$islink[$j]."<br />";
		//-- for facts with empty values they must have sub records
		//-- this section checks if they have subrecords
		if (empty($text[$j])) {
			$k=$j+1;
			$pass=false;
			while(($k<count($glevels))&&($glevels[$k]>$glevels[$j])) {
				if (!empty($text[$k])) {
					if (($tag[$j]!="OBJE")||($tag[$k]=="FILE")) {
						$pass=true;
						break;
					}
				}
				if (($tag[$k]=="FILE")&&(count($uploaded_files)>0)) {
					$filename = array_shift($uploaded_files);
					if (!empty($filename)) {
						$text[$k] = $filename;
						$pass=true;
						break;
					}
				}
				$k++;
			}
		}
		else $pass = true;
		
		//print "pass: ".$pass."<br />";	
		// NOTE: if the value is not empty then write the line to the gedcom record
		// The value 0 is considered as empty, se we must check this separately (1 NCHI 0)
		if ($pass == true) {			
			// Only add a link if an ID is given
			$skip = false;
			//print "check for link ".$islink[$j];
			if ($islink[$j]) {
				$passlink = false;
				//print "link found<br />";
				if (!empty($text[$j]) || $text[$j] == "0") {
					//print "ok link found".$text[$j];
					$trec = FindGedcomRecord($text[$j]);
					if (!empty($trec)) $passlink = true;
					if (GetChangeData(true, $text[$j], true, "", "")) {
						$t = GetChangeData(false, $text[$j], true, "gedlines", "");
						$ctrec = $t[$GEDCOMID][$text[$j]];
						if (!empty($ctrec)) $passlink = true;
						else $passlink = false;
					}
					if (!$passlink) {
						print "<span class=\"error\">".$gm_lang["link_not_added"].": ".$text[$j]."</span><br />";
						$link_error = true;
					}
					else {
						$text[$j]="@".$text[$j]."@";
						$newline = $glevels[$j]." ".$tag[$j];
					}
				}
				if (!$passlink) {
					//print "empty link found<br />";
					// We found an empty link. Also the subsequent subrecords must be skipped.
					$newline = "";
					$lev = $glevels[$j];
					for ($a=$j+1; $a<count($glevels); $a++) {
						if ($glevels[$a] == $lev) {
							$j = $a - 1;
							$skip = true;
							break;
						}
					}
					if (!$skip) $j = count($glevels) - 1;
				}
			}
			if (!$skip) {
			
				// NOTE: check and translate the incoming dates
				if ($tag[$j]=="DATE" && !empty($text[$j])) {
					$text[$j] = CheckInputDate($text[$j]);
				}
			
				if (!empty($text[$j]) || $text[$j] == "0") {
					$newline = $glevels[$j]." ".$tag[$j];
					if (($newline == "2 SOUR" || $newline == "1 SOUR") && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
					else if (($newline == "2 OBJE" || $newline == "1 OBJE") && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
					else if ($newline == "1 REPO" && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
					else if ($newline == "2 ASSO" && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
					else if (!empty($newline)) $newline .= " ".$text[$j]; // This is for note text in the link record
				}
				else {
					// Handle empty tags because lower level tags may follow
					if (isset($glevels[$j+1]) && $glevels[$j+1] > $glevels[$j]) {
						$newline = $glevels[$j]." ".$tag[$j];
						// print "1: ".$newline."<br />";
					}
				}
						
				// NOTE: Check if the new record already contains this line, if so, empty the new record
				if (isset($newline) && trim($newged) == trim($newline)) $newged = "";
				
				// NOTE: convert returns to CONT lines and break up lines longer than 255 chars
				$newlines = preg_split("/\r?\n/", $newline);
				for($k=0; $k<count($newlines); $k++) {
					if ($k>0) $newlines[$k] = ($glevels[$j]+1)." CONT ".$newlines[$k];
					if (strlen($newlines[$k])>255) {
						while(strlen($newlines[$k])>255) {
							for ($ch = 255;1;$ch--) {
								if (substr($newlines[$k],$ch-1,1) != " ") break;
							}
							$str = substr($newlines[$k], 0, $ch);
							$newged .= $str."\r\n";
							$newlines[$k] = substr($newlines[$k], $ch);
							$newlines[$k] = ($glevels[$j]+1)." CONC ".$newlines[$k];
						}
						$newged .= trim($newlines[$k])."\r\n";
					}
					else {
						$newged .= trim($newlines[$k])."\r\n";
					}
				}
				// print "2: ".$newline."<br />";
				// print "2 newged: ".$newged."<br />";
			}
		}
	}
	$newged = CleanupTagsY($newged);
	return $newged;
}

/**
 * check the given date that was input by a user and convert it
 * to proper gedcom date if possible
 * @author Genmod Development Team
 * @param string $datestr	the date input by the user
 * @return string	the converted date string
 */
function CheckInputDate($datestr) {
	$date = ParseDate($datestr);

//	if (count($date)==1 && empty($date[0]['ext']) && !empty($date[0]['month'])) {
//		$datestr = $date[0]['day']." ".$date[0]['month']." ".$date[0]['year'];
//	}
	$str = "";
	if (isset($date[0]['ext']) && !empty($date[0]['ext'])) $str .= $date[0]['ext']." ";
	if (isset($date[0]['day']) && !empty($date[0]['day'])) $str .= $date[0]['day']." ";
	if (isset($date[0]['month']) && !empty($date[0]['month'])) $str .= $date[0]['month']." ";
	if (isset($date[0]['year']) && !empty($date[0]['year'])) $str .= $date[0]['year']." ";
	if (isset($date[1])) {
		if (isset($date[1]['ext']) && !empty($date[1]['ext'])) $str .= $date[1]['ext']." ";
		if (isset($date[1]['day']) && !empty($date[1]['day'])) $str .= $date[1]['day']." ";
		if (isset($date[1]['month']) && !empty($date[1]['month'])) $str .= $date[1]['month']." ";
		if (isset($date[1]['year']) && !empty($date[1]['year'])) $str .= $date[1]['year']." ";
	}
	$str = trim ($str);
	if (!empty($str)) return $str;
	else return $datestr;
}

function PrintQuickResn($name, $default="") {
	global $SHOW_QUICK_RESN, $align, $gm_lang, $tabkey;
	
	if ($SHOW_QUICK_RESN) {
		print "<tr><td class=\"shade2\">";
		print_help_link("RESN_help", "qm");
		print GM_FACT_RESN; 
		print "</td>\n";
		print "<td class=\"shade1\" colspan=\"3\">\n";
		print "<select name=\"$name\" tabindex=\"".$tabkey."\" ><option value=\"\"></option><option value=\"confidential\"";
		if ($default == "confidential") print "selected=\"selected\"";
		$tabkey++;
		print ">".$gm_lang["confidential"]."</option><option value=\"locked\"";
		if ($default == "locked") print "selected=\"selected\"";
		print ">".$gm_lang["locked"]."</option><option value=\"privacy\"";
		if ($default == "privacy") print "selected=\"selected\"";
		print ">".$gm_lang["privacy"]."</option>";
		print "</select>\n";
		print "</td>\n";
		print "</tr>\n";
	}
}
function PrintPedi($name, $value="", $showbio=true) {
	global $align, $gm_lang, $tabkey;

	print "<select tabindex=\"".$tabkey."\" id=\"".$name."\" name=\"".$name."\">\n";
		
	if ($showbio) {
		print "<option value=\"birth\"";
		if ($value=="birth" || $value=="") print " selected=\"selected\"";
		print ">".$gm_lang["biological"]."</option>\n";
	}
		
	print "<option value=\"adopted\"";
	if ($value=="adopted") print " selected=\"selected\"";
	print ">".$gm_lang["adopted"]."</option>\n";

	print "<option value=\"foster\"";
	if ($value=="foster") print " selected=\"selected\"";
	print ">".$gm_lang["foster"]."</option>\n";
		
	print "<option value=\"sealing\"";
	if ($value=="sealing") print " selected=\"selected\"";
	print ">".$gm_lang["sealing"]."</option>\n";
		
	print "</select>\n";
}

function SubmitterRecord($level, $gedrec) {
	if ($gedrec == "") {
		AddSimpleTag(($level+1)." NAME");
		AddSimpleTag(($level+1)." ADDR");
		AddSimpleTag(($level+2)." CTRY");
		AddSimpleTag(($level+1)." PHON");
		AddSimpleTag(($level+1)." EMAIL");
		AddSimpleTag(($level+1)." NOTE");
	}
	else {
		$gedtype = GetSubRecord(($level+1), "NAME", $gedrec);
		if (empty($gedtype)) $gedtype = "NAME";
		AddSimpleTag(($level+1)." ".$gedtype);
		
		$gedtype = GetSubRecord(($level+1), "ADDR", $gedrec);
		if (empty($gedtype)) $gedtype = "ADDR";
		
		$gedlines = split("\r\n", $gedtype);
		$fields = preg_split("/\s/", $gedlines[0]);
		
		$i = 0;
		$text = "";
		// NOTE: This retrieves the data for each fact
		for($j=1; $j<count($fields); $j++) {
			if ($j>1) $text .= " ";
			$text .= $fields[$j];
		}
		// NOTE: This retrieves the continuance of the address
		while(($i+1<count($gedlines))&&(preg_match("/".($level+2)." (CON[CT])\s?(.*)/", $gedlines[$i+1], $cmatch)>0)) {
			$iscont=true;
			if ($cmatch[1]=="CONT") $text.="\n";
			else if ($WORD_WRAPPED_NOTES) $text .= " ";
			$text .= $cmatch[2];
			$i++;
		}
		AddSimpleTag(($level+1)." ADDR ".$text);
		
		$gedtype = GetSubRecord(($level+2), "CTRY", $gedtype);
		if (empty($gedtype)) $gedtype = "CTRY";
		AddSimpleTag(($level+2)." ".$gedtype);
		
		$gedtype = GetSubRecord(($level+1), "PHON", $gedrec);
		if (empty($gedtype)) $gedtype = "PHON";
		AddSimpleTag(($level+1)." ".$gedtype);
		
		$gedtype = GetSubRecord(($level+1), "EMAIL", $gedrec);
		if (empty($gedtype)) $gedtype = "EMAIL";
		AddSimpleTag(($level+1)." ".$gedtype);

		$gednote = GetGedcomValue("NOTE", ($level+1), $gedrec);
		if (empty($gednote)) $gednote = "";
		AddSimpleTag(($level+1)." NOTE ".$gednote);
	}
}
function ShowMediaForm($pid, $action="newentry", $change_type="add_media") {
	global $GEDCOMID, $gm_lang, $TEXT_DIRECTION, $MEDIA_ID_PREFIX, $WORD_WRAPPED_NOTES, $MEDIA_DIRECTORY;
	global $MEDIA_FACTS_ADD, $MEDIA_FACTS_UNIQUE, $gm_user;
	
	$facts_add = explode(",", $MEDIA_FACTS_ADD);
	$facts_unique = explode(",", $MEDIA_FACTS_UNIQUE);
	
	// NOTE: add a table and form to easily add new values to the table
	print "<form method=\"post\" name=\"newmedia\" action=\"addmedia.php\" enctype=\"multipart/form-data\">\n";
	print "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
	print "<input type=\"hidden\" name=\"paste\" value=\"1\" />\n";
	print "<input type=\"hidden\" name=\"change_type\" value=\"$change_type\" />\n";
	print "<input type=\"hidden\" name=\"gedid\" value=\"$GEDCOMID\" />\n";
	if (isset($pid)) print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
	print "<table class=\"facts_table center $TEXT_DIRECTION\">\n";
	print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["add_media"]."</td></tr>";
	if ($pid == "") {
		print "<tr><td class=\"shade2\">".$gm_lang["add_fav_enter_id"]."</td>";
		print "<td class=\"shade1\"><input type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
		LinkFunctions::PrintFindIndiLink("gid","");
		LinkFunctions::PrintFindFamilyLink("gid");
		LinkFunctions::PrintFindSourceLink("gid");
		print "</td></tr>";
	}
	if (IdType($pid) == "OBJE") {
		$gedrec = FindMediaRecord($pid);
		if (GetChangeData(true, $pid, true)) {
			$rec = GetChangeData(false, $pid, true, "gedlines");
			$gedrec = $rec[$GEDCOMID][$pid];
		}
	}
	else {
		$gedrec = "";
	}
	// Store the gedcom rec to compare with the new values
	print "<input type=\"hidden\" name=\"oldgedrec\" value=\"$gedrec\" />\n";

	// Print the JS to check for valid characters in the filename
	?><script language="JavaScript">
	<!--
	function check( filename ) {
		if( !filename.value ) return true;
		if( filename.match( /^[a-zA-Z]:[\w- \\\\]+\..*$/ ) ) 
			return true;
		else
			alert( "<?php print $gm_lang["invalid_file"]; ?>" ) ;
			return false;
	}
	//-->
	</script> <?php
	// 0 OBJE
	// 1 FILE
	// NOTE: probably this part of the code for existing media is not in use anymore.
	if (!empty($gedrec)) {
		$chan = GetSubrecord(1, "CHAN", $gedrec, 1);
		if (!empty($chan)) $gedrec = preg_replace("/$chan/", "", $gedrec);
		$facts = GetAllSubrecords($gedrec, "", false, false, false);
		$notefound = false;
		$foundfacts = array();
		foreach($facts as $key=>$fact) {
			$ct = preg_match_all("/(\d)\s(\w+)\s(.*)/", $fact, $match);
			for($i=0;$i<$ct;$i++) {
				$level = $match[1][$i];
				$tag = $match[2][$i];
				if (!in_array(array($tag, $level), $foundfacts)) $foundfacts[] = array($tag, $level);
				$value = $match[3][$i];
				if ($tag == "NOTE") {
					// if already in a note, write the old note first
					if ($notefound) AddSimpleTag("$notelevel, NOTE, $notetext");
					// Start build the note
					$notetext = $value;
					$notelevel = $level;
					$notefound = true;
				}
				else if ($tag == "CONT" || $tag == "CONC") {
					// strip the newline from the text as CONC should not start on a new line
					$notetext = preg_replace("/[\r|\n]+$/", "", $notetext);
					if ($tag == "CONT") $notetext.="\n";
					else if ($WORD_WRAPPED_NOTES) $notetext .= " ";
					$notetext .= $value;
				}
				else AddSimpleTag("$level $tag $value");
				if ($tag == "FILE") {
					// Box for user to choose to upload file from local computer
					print "<tr><td class=\"shade2\">&nbsp;</td><td class=\"shade1\"><input type=\"file\" name=\"picture\" size=\"60\"></td></tr>";
					// Box for user to choose the folder to store the image
					print "<tr><td class=\"shade2\">".$gm_lang["folder"]."</td><td class=\"shade1\"><input type=\"text\" name=\"folder\" size=\"60\"></td></tr>";
				}
			}
		}
		if (!in_array(array("FILE", 1), $foundfacts)) AddSimpleTag("1 FILE");
		if (!in_array(array("FORM", 2), $foundfacts)) AddSimpleTag("2 FORM");
		if (!in_array(array("TYPE", 3), $foundfacts)) AddSimpleTag("3 TYPE");
		if (!in_array(array("TITL", 2), $foundfacts)) AddSimpleTag("2 TITL");
		AddSimpleTag("1 REFN");
		AddSimpleTag("2 TYPE");
		AddSimpleTag("1 RIN");
		if ($notefound == true) AddSimpleTag("1 NOTE $notetext");
		AddSimpleTag("1 NOTE");
		AddSimpleTag("1 SOUR");
	}
	else {
		if (in_array("FILE", $facts_add) || in_array("FILE", $facts_unique)) {
			$element_id = AddSimpleTag("1 FILE");
			// Box for user to choose to upload file from local computer
			print "<tr><td class=\"shade2\">".$gm_lang["upload_file"]."</td><td class=\"shade1\"><input type=\"file\" name=\"picture\" size=\"60\"></td></tr>";
			// Box for user to choose the folder to store the image
			$dirlist = MediaFS::GetMediaDirList($MEDIA_DIRECTORY, true, 1, true, false);
			print "<tr><td class=\"shade2\">".$gm_lang["upload_to_folder"]."</td><td class=\"shade1\">";
	//		<input type=\"text\" name=\"folder\" size=\"60\">
			print "<select name=\"folder\">";
			foreach($dirlist as $key => $dir) {
				print "<option value=\"".$dir."\">".$dir."</option>";
			}
			print "</select></td></tr>";
			AddSimpleTag("2 FORM");
			AddSimpleTag("3 TYPE");
			AddSimpleTag("2 TITL");
		}
		if (in_array("REFN", $facts_add) || in_array("REFN", $facts_unique)) {
			AddSimpleTag("1 REFN");
			AddSimpleTag("2 TYPE");
		}
		if (in_array("RIN", $facts_add) || in_array("RIN", $facts_unique)) AddSimpleTag("1 RIN");
// 		PRIM and THUM are added at linklevel		
		if (in_array("NOTE", $facts_add) || in_array("NOTE", $facts_unique)) AddSimpleTag("1 NOTE");
//		if (in_array("SOUR", $facts_add) || in_array("SOUR", $facts_unique)) AddSimpleTag("1 SOUR");
		if (in_array("SOUR", $facts_add) || in_array("SOUR", $facts_unique)) {
			print "<tr><td colspan=\"2\">";
			PrintAddLayer("SOUR", 1);
			print"</td></tr>";
		}
	}
		
	if ($gm_user->UserCanAccept() && !$gm_user->userAutoAccept()) print "<tr><td class=\"shade1\" colspan=\"2\"><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."</td></tr>";
	print "<tr><td class=\"topbottombar\" colspan=\"2\"><input type=\"submit\" onclick=\"return check(document.newmedia.picture.value);\" value=\"".$gm_lang["add_media_button"]."\" /></td></tr>\n";
	print "</table>\n";
	print "</form>\n";
	if (isset($element_id)) print "\n<script type=\"text/javascript\">\n<!--\ndocument.getElementById(\"".$element_id."\").focus();\n//-->\n</script>";
}

function ReplaceLinks($oldgid, $newgid, $mtype, $change_id, $change_type, $ged) {

	$records = GetLinkedGedRecs($oldgid, $mtype, $ged);
		
	foreach ($records as $key1=>$record) {	
		$tt = preg_match("/0 @(.+)@ (.+)/", $record, $match);
		$gid = $match[1];
		$type = $match[2];
		$subs = Getallsubrecords($record, "", false, false, false);
		foreach ($subs as $key=>$sub) {
			if (preg_match("/@$oldgid@/", $sub)) {
				$tag = substr($sub, 2, 4);
				if ($tag != "CHIL" && $tag != "HUSB" && $tag != "WIFE" && $tag != "FAMC" && $tag != "FAMS") {
					$newsub = preg_replace("/(\d) (\w+) @$oldgid@/", "$1 $2 @$newgid@", $sub);
					ReplaceGedrec($gid, $sub, $newsub, $tag, $change_id, $change_type, $ged);
				}
			}
		}
	}
}

function DeleteLinks($oldgid, $mtype, $change_id, $change_type, $ged) {
	global $GEDCOMID;

	// We miss the links on new records, which are only in the _changes table
	$records = GetLinkedGedRecs($oldgid, $mtype, $ged);
	$success = true;
	foreach ($records as $key1=>$record) {	
		$tt = preg_match("/0 @(.+)@ (.+)/", $record, $match);
		$gid = $match[1];
		$type = $match[2];
		if (GetChangeData(true, $gid, true, "", "")) {
			$rec = GetChangeData(false, $gid, true, "gedlines", "");
			$record = $rec[$GEDCOMID][$gid];
		}
		$subs = Getallsubrecords($record, "", false, false, false);
		foreach ($subs as $key=>$sub) {
			$ttt = preg_match("/1\s(\w+)/", $sub, $match3);
			$fact = $match3[1];
			if (preg_match("/(\d) (\w+) @$oldgid@/", $sub, $match2)) {
				$subdel = GetSubRecord($match2[1], $match2[1]." ".$match2[2]." @".$oldgid."@", $sub);
				$subnew = preg_replace("/$subdel/", "", $sub);
				$success = $success && ReplaceGedrec($gid, $sub, $subnew, $fact, $change_id, $change_type);
			}
		}
	}
	return $success;
}

function GetLinkedGedrecs($oldgid, $mtype, $ged) {
	
	$records = array();
	
	//-- Collect all gedcom records
	
	//-- References from sources (REPO, OBJE, NOTE)
	if ($mtype == "REPO" || $mtype == "OBJE" || $mtype == "NOTE") {
		$sql = "SELECT s_gedcom, s_id FROM ".TBLPREFIX."sources WHERE s_gedcom REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND s_file='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc()) {
				$records[] = $row["s_gedcom"];
			}
		}
	}
	
	//-- References from individuals (SOUR, INDI, FAM, NOTE, OBJE)
	if ($mtype == "SOUR" || $mtype == "INDI" || $mtype == "FAM" || $mtype == "NOTE" || $mtype == "OBJE") {
		$sql = "SELECT i_gedcom, i_id FROM ".TBLPREFIX."individuals WHERE i_gedcom REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND i_file='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc()) {
				$records[] = $row["i_gedcom"];
			}
		}
	}

	//-- References from families (SOUR, INDI, NOTE, OBJE)
	if ($mtype == "SOUR" || $mtype == "INDI" || $mtype == "NOTE" || $mtype == "OBJE") {
		$sql = "SELECT f_gedcom, f_id FROM ".TBLPREFIX."families WHERE f_gedcom REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND f_file='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc()) {
				$records[] = $row["f_gedcom"];
			}
		}
	}
	
	//-- References from multimedia (SOUR, NOTE)
	if ($mtype == "SOUR" || $mtype == "NOTE") {
		$sql = "SELECT m_gedrec, m_media FROM ".TBLPREFIX."media WHERE m_gedrec REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND m_gedfile='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc()) {
				$records[] = $row["m_gedrec"];
			}
		}
	}

	//-- References from notes, submitter recs (SOUR, INDI) <== links to notes and repositories
	if ($mtype == "SOUR" || $mtype == "INDI" || $mtype == "REPO" || $mtype == "OBJE") {
		$sql = "SELECT o_gedcom, o_id FROM ".TBLPREFIX."other WHERE o_gedcom REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND o_file='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc()) {
				$records[] = $row["f_gedcom"];
			}
		}
	}
	
	return $records;
}

function DeleteFamIfEmpty($famid, $change_id, $change_type, $ged="") {
	global $GEDCOMID;
	
	if (empty($ged)) $ged = $GEDCOMID;
	$famrec = FindFamilyRecord($famid, get_gedcom_from_id($ged));
	if (GetChangeData(true, $famid, true)) {
		$rec = GetChangeData(false, $famid, true, "gedlines");
		$famrec = $rec[$GEDCOMID][$famid];
	}
	$ct = preg_match("/1 CHIL|HUSB|WIFE/", $famrec);
//	print $famrec."<br />".$ct."<br />";
	if ($ct == 0) return ReplaceGedRec($famid, $famrec, "", "FAM", $change_id, $change_type, $ged);
	else return true;
}

function SortFactDetails($gedrec) {
	
	// Check for level 1
	if (substr($gedrec,0,1) != 1) return $gedrec;
	
	// Split the level 2 records
	$gedlines = split("\r\n2 ", trim($gedrec));
	if (count($gedlines) <= 2) return $gedrec;
	
	// Keep the level 1 apart
	$l1rec = array_shift($gedlines);
	
	// Split the level 2 recs into their arrays
	$other = array();
	$asso = array();
	$sour = array();
	$obje = array();
	$note = array();
	$resn = array();
	foreach($gedlines as $key=>$gedline) {
		$tag = substr($gedline, 0, strpos($gedline, " ")); 
		if ($tag == "SOUR") $sour[] = $gedline;
		else if ($tag == "OBJE") $obje[] = $gedline;
		else if ($tag == "RESN") $resn[] = $gedline;
		else if ($tag == "ASSO") $asso[] = $gedline;
		else if ($tag == "NOTE") $note[] = $gedline;
		else $other[] = $gedline;
	}
	// Add the missing tags to the basic info
//	$tags = array();
//	print_r($other);
//	$otherrec = $l1rec."\r\n2 ".implode("\r\n2 ", $other);
//	$ct = preg_match_all("/\d\s(\w+).+/", $otherrec, $match);
//	if ($ct > 0) {
//		foreach($match[1] as $index=>$tag) {
//			$tags[] = $tag;
//		}
//		print_r($tags);	
//	}
	
	// Recompose the record
	$all = array_merge($other, $asso);
	$all = array_merge($all, $obje);
	$all = array_merge($all, $sour);
	$all = array_merge($all, $note);
	$all = array_merge($all, $resn);
		
	// Rebuild the string
	$gedrec = $l1rec."\r\n2 ".implode("\r\n2 ", $all);
	return $gedrec;
}

function AddMissingTags($tags) {
	global $templefacts, $nondatefacts, $nonplacfacts, $gm_lang, $MEDIA_DIRECTORY, $focus;

	// Now add some missing tags :
	if (in_array($tags[0], $templefacts)) {
		// 2 TEMP
		if (!in_array("TEMP", $tags)) AddSimpleTag("2 TEMP");
		// 2 STAT
		if (!in_array("STAT", $tags)) AddSimpleTag("2 STAT");
	}
	if ($tags[0]=="EVEN" or $tags[0]=="GRAD" or $tags[0]=="MARR") {
		// 1 EVEN|GRAD|MARR
		// 2 TYPE
		if (!in_array("TYPE", $tags)) AddSimpleTag("2 TYPE");
	}
	if ($tags[0]=="EDUC" or $tags[0]=="GRAD" or $tags[0]=="OCCU") {
		// 1 EDUC|GRAD|OCCU
		// 2 CORP
		if (!in_array("CORP", $tags)) AddSimpleTag("2 CORP");
	}
	if ($tags[0]=="DEAT") {
		// 1 DEAT
		// 2 CAUS
		if (!in_array("CAUS", $tags)) AddSimpleTag("2 CAUS");
	}
	if ($tags[0]=="REPO") {
		//1 REPO
		//2 CALN
		if (!in_array("CALN", $tags)) AddSimpleTag("2 CALN");
		}
	if (!in_array($tags[0], $nondatefacts)) {
		// 2 DATE
		// 3 TIME
		if (!in_array("DATE", $tags)) {
			AddSimpleTag("2 DATE");
			AddSimpleTag("3 TIME");
		}
		// 2 PLAC
		if (!in_array("PLAC", $tags) && !in_array($tags[0], $nonplacfacts) && !in_array("TEMP", $tags)) AddSimpleTag("2 PLAC");
	}
	if ($tags[0]=="BURI") {
		// 1 BURI
		// 2 CEME
		if (!in_array("CEME", $tags)) AddSimpleTag("2 CEME");
	}
	if ($tags[0]=="BIRT" or $tags[0]=="DEAT"
	or $tags[0]=="EDUC" or $tags[0]=="GRAD"
	or $tags[0]=="OCCU" or $tags[0]=="ORDN" or $tags[0]=="RESI") {
		// 1 BIRT|DEAT|EDUC|GRAD|ORDN|RESI
		// 2 ADDR
		if (!in_array("ADDR", $tags)) AddSimpleTag("2 ADDR");
	}
	if ($tags[0]=="OCCU" or $tags[0]=="RESI") {
		// 1 OCCU|RESI
		// 2 PHON|FAX|EMAIL|URL
		if (!in_array("PHON", $tags)) AddSimpleTag("2 PHON");
		if (!in_array("FAX", $tags)) AddSimpleTag("2 FAX");
		if (!in_array("EMAIL", $tags)) AddSimpleTag("2 EMAIL");
		if (!in_array("URL", $tags)) AddSimpleTag("2 URL");
	}
	if ($tags[0]=="FILE") {
		// 1 FILE
		// Box for user to choose to upload file from local computer
		print "<tr><td class=\"shade2\">".$gm_lang["upload_file"]."</td><td class=\"shade1\"><input type=\"file\" name=\"picture\" size=\"60\"></td></tr>";
		// Box for user to choose the folder to store the image
		$dirlist = MediaFS::GetMediaDirList($MEDIA_DIRECTORY, true, 1, true, false);
		print "<tr><td class=\"shade2\">".$gm_lang["upload_to_folder"]."</td><td class=\"shade1\">";
		print "<select name=\"folder\">";
		foreach($dirlist as $key => $dir) {
			print "<option value=\"".$dir."\">".$dir."</option>";
		}
		print "</select></td></tr>";
		// 2 TITL
		if (!in_array("TITL", $tags)) AddSimpleTag("2 TITL");
		// 2 FORM
		if (!in_array("FORM", $tags)) AddSimpleTag("2 FORM");
		// 3 TYPE
		if (!in_array("TYPE", $tags)) AddSimpleTag("3 TYPE");
	}
	if (in_array($tags[0], $templefacts)) {
		// 2 TEMP
		AddSimpleTag("2 TEMP");
		// 2 STAT
		AddSimpleTag("2 STAT");
	}
}
?>