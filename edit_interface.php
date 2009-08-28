<?php
/**
 * PopUp Window to provide editing features.
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
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the editing functions
*/
require("includes/functions/functions_edit.php");

/** * Inclusion of the country names
*/
require($GM_BASE_DIRECTORY."languages/countries.en.php");
if (file_exists($GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$LANGUAGE].".php")) require($GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$LANGUAGE].".php");
asort($countries);

if ($_SESSION["cookie_login"]) {
	if (empty($LOGIN_URL)) header("Location: login.php?type=simple&ged=$GEDCOM&url=edit_interface.php".urlencode("?".$QUERY_STRING));
	else header("Location: ".$LOGIN_URL."?type=simple&ged=$GEDCOM&url=edit_interface.php".urlencode("?".$QUERY_STRING));
	exit;
}

// Remove slashes
if (isset($text)){
	foreach ($text as $l => $line){
		$text[$l] = stripslashes($line);
	}
}

if (!isset($action)) $action="";
if (!isset($linenum)) $linenum="";
if (isset($pid)) $pid = strtoupper($pid);
$uploaded_files = array();
if (!isset($aa_attempt)) $aa_attempt = false;
$can_auto_accept = true;

// items for ASSO RELA selector :
$assokeys = array(
"attendant",
"attending",
"circumciser",
"civil_registrar",
"friend",
"godfather",
"godmother",
"godparent",
"informant",
"lodger",
"nurse",
"priest",
"rabbi",
"registry_officer",
"servant",
"submitter",
"twin",
"twin_brother",
"twin_sister",
"witness",
"" // DO NOT DELETE
);
$assorela = array();
foreach ($assokeys as $indexval => $key) {
  if (isset($gm_lang["$key"])) $assorela["$key"] = $gm_lang["$key"];
  else $assorela["$key"] = "? $key";
}
natsort($assorela);

print_simple_header("Edit Interface ".GM_VERSION);

// Debug info
//print "Action: ".$action."<br />Pid: ".$pid;
?>
<script type="text/javascript">
<!--
	function openerpasteid(id) {
		window.opener.paste_id(id);
		window.close();
	}
	var pastefield;
	function paste_id(value) {
		pastefield.value = value;
		if (pastefield.type != 'hidden') pastefield.focus();
	}
	
	function paste_char(value,lang,mag) {
		pastefield.value += value;
		language_filter = lang;
		magnify = mag;
	}

	// This reload function is empty, as it may be called from edit_close from a 2nd or third edit level.
	function reload() {
	}
	
	function edit_close() {
		window.opener.reload();
		window.close();
	}
//-->
</script>
<?php
//-- check if user has access to the gedcom record
$disp = false;
$success = false;
$factdisp = true;
$factedit = true;
if (!empty($pid)) {
	// NOTE: Remove any illegal characters from the ID
	$pid = CleanInput($pid);
	if ((strtolower($pid) != "newsour") && (strtolower($pid) != "newrepo")&& (strtolower($pid) != "newgnote")) {
		// NOTE: Do not take the changed record here since non-approved changes should not yet appear
		$gedrec = FindGedcomRecord($pid);
		$rec = GetChangeData(false, $pid, true, "gedlines");
//		print_r($rec);
		if (isset($rec[$GEDCOM][$pid])) $gedrec = $rec[$GEDCOM][$pid];
//		print $gedrec;
		$type = GetRecType($gedrec);
		if ($type) {
			//-- if the record is for an INDI then check for display privileges for that indi
			if ($type=="INDI") {
				$disp = displayDetailsById($pid);
				//-- if disp is true, also check for resn access
				if ($disp == true){
					// First check level 1 RESN
					if (FactViewRestricted($pid, $gedrec, 1)) $factdisp = false;
					if (FactEditRestricted($pid, $gedrec, 1)) $factedit = false;
					// Then check level 2 RESN
					$subs = GetAllSubrecords($gedrec, "", false, false);
					foreach($subs as $indexval => $sub) {
						if (FactViewRestricted($pid, $sub)==true) $factdisp = false;
						if (FactEditRestricted($pid, $sub)==true) $factedit = false;
					}
				}
			}
			//-- for FAM check for display privileges on both parents
			else if ($type=="FAM") {
				// First check level 1 RESN
				if (FactViewRestricted($pid, $gedrec, 1)) $factdisp = false;
				if (FactEditRestricted($pid, $gedrec, 1)) $factedit = false;
				// Then check level 2 RESN
				$subs = GetAllSubrecords($gedrec, "", false, false);
				foreach($subs as $indexval => $sub) {
					if (FactViewRestricted($pid, $sub)==true) $factdisp = false;
					if (FactEditRestricted($pid, $sub)==true) $factedit = false;
				}
				//-- check if we can display both parents
				$parents = FindParentsInRecord($gedrec);
				$disp = displayDetailsById($parents["HUSB"]);
				if ($disp) {
					$disp = displayDetailsById($parents["WIFE"]);
				}
			}
			else {
				$disp=true;
			}
		}
		else {
			$disp = true;
		}
	}
	else {
		$disp = true;
	}
}
else if (!empty($famid)) {
	$famid = CleanInput($famid);
	if ($famid != "new") {
		
		// TODO: if the fam record has been changed, get the record from the changes table DONE
		if (GetChangeData(true, $famid, true, "", "")) {
			$rec = GetChangeData(false, $famid, true, "gedlines", "");
			$gedrec = $rec[$GEDCOM][$famid];
		}
		else $gedrec = FindGedcomRecord($famid);
		
		$ct = preg_match("/0 @$famid@ (.*)/", $gedrec, $match);
		if ($ct>0) {
			$type = trim($match[1]);
			//-- if the record is for an INDI then check for display privileges for that indi
			if ($type=="INDI") {
				$disp = displayDetailsById($famid);
				//-- if disp is true, also check for resn access
				if ($disp == true){
					if (FactViewRestricted($famid, $gedrec, 1)) $factdisp = false;
					if (FactEditRestricted($famid, $gedrec, 1)) $factedit = false;
					$subs = GetAllSubrecords($gedrec, "", false, false);
					foreach($subs as $indexval => $sub) {
						if (FactViewRestricted($famid, $sub)==true) $factdisp = false;
						if (FactEditRestricted($famid, $sub)==true) $factedit = false;
					}
				}
			}
			//-- for FAM check for display privileges on both parents
			else if ($type=="FAM") {
				//-- check if there are restrictions on the facts
				if (FactViewRestricted($famid, $gedrec, 1)) $factdisp = false;
				if (FactEditRestricted($famid, $gedrec, 1)) $factedit = false;
				$subs = GetAllSubrecords($gedrec, "", false, false);
				foreach($subs as $indexval => $sub) {
					if (FactViewRestricted($famid, $sub)==true) $factdisp = false;
					if (FactEditRestricted($famid, $sub)==true) $factedit = false;
				}
				//-- check if we can display both parents
				$parents = FindParentsInRecord($gedrec);
				$disp = displayDetailsById($parents["HUSB"]);
				if ($disp) {
					$disp = displayDetailsById($parents["WIFE"]);
				}
			}
			else {
				$disp=true;
			}
		}
	}
}
else {
	if (($action!="addchild")&&($action!="addchildaction")&&($action!="submitter")) {
		print "<span class=\"error\">The \$pid variable was empty.	Unable to perform $action.</span>";
		print_simple_footer();
		exit;
		// exit added, what's the following line for?
		$disp = true;
	}
	else {
		$disp = true;
	}
}

if ($action == "edit" || $action == "editraw") {
	// Get the fact record
	if ($action == "edit") {
		if ($change_type == "edit_general_note") {
			$recs = split("\r\n", trim($gedrec));
			$gedrec = $recs[0]."\r\n";
			for ($i=1;$i<count($recs);$i++) {
				$ct = preg_match("/\d\s(\w+)/", $recs[$i], $match);
				if (in_array($match[1], array("CONC", "CONT", "NOTE"))) $gedrec .= $recs[$i]."\r\n";
			}
			$oldrec = $gedrec;
		}
		else $oldrec = GetSubRecord(1, "1 $fact", $gedrec, $count);
	}
	else $oldrec = $gedrec;
	// Check links from the fact to hidden sources, media, etc.
	$cl = preg_match_all("/[1-9]\s(.+)\s@(.+)@/", $oldrec, $match);
	for ($i=0; $i<$cl;$i++) {
		$disp = $disp && DisplayDetailsByID($match[2][$i], $match[1][$i], 1, true);
		// print "2: ".$match[2][$i]." 1: ".$match[1][$i]." disp: ".$disp."<br />";
	}
}
// TODO edit submitter from other than $GEDCOM
if ((!$Users->userCanEdit($gm_username))||(!$disp)||(!$ALLOW_EDIT_GEDCOM) || (!$Users->userCanEditGedlines() && $action == "editraw")) {
	print $gm_lang["access_denied"];
	//-- display messages as to why the editing access was denied
	if (!$Users->userCanEdit($gm_username)) print "<br />".$gm_lang["user_cannot_edit"];
	else if (!$ALLOW_EDIT_GEDCOM) print "<br />".$gm_lang["gedcom_editing_disabled"];
	else if (!$disp) {
		print "<br />".$gm_lang["privacy_prevented_editing"];
		if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
		else if (!empty($famid)) print "<br />".$gm_lang["privacy_not_granted"]." famid $famid.";
	}
	print "<br /><br /><div class=\"center\"><a href=\"javascript: ".$gm_lang["close_window"]."\" onclick=\"window.close();\">".$gm_lang["close_window"]."</a></div>\n";
	print_simple_footer();
	exit;
}

if (!isset($type)) $type="";
if ($type=="INDI") {
	print "<b>".PrintReady(GetPersonName($pid, $gedrec))."</b><br />";
}
else if ($type=="FAM") {
	print "<b>".PrintReady(GetPersonName($parents["HUSB"]))." + ".PrintReady(GetPersonName($parents["WIFE"]))."</b><br />";
}
else if ($type=="SOUR") {
	print "<b>".PrintReady(GetSourceDescriptor($pid))."</b><br />";
}
if (strstr($action,"addchild")) {
	if (empty($famid)) {
		print_help_link("edit_add_unlinked_person_help", "qm");
		print "<b>".$gm_lang["add_unlinked_person"]."</b><br />\n";
	}
	else {
		print_help_link("edit_add_child_help", "qm");
		print "<b>".$gm_lang["add_child"]."</b><br />\n";
	}
}
else if (strstr($action,"addspouse")) {
	print_help_link("edit_add_spouse_help", "qm");
	print "<b>".$gm_lang["add_".strtolower($famtag)]."</b><br />\n";
}
else if (strstr($action,"addnewparent")) {
	print_help_link("edit_add_parent_help", "qm");
	if ($famtag=="WIFE") print "<b>".$gm_lang["add_mother"]."</b><br />\n";
	else print "<b>".$gm_lang["add_father"]."</b><br />\n";
}
else {
	if (defined("GM_FACT_".$type)) print "<b>".constant("GM_FACT_".$type)."</b><br />";
}

switch ($action) {
	case "submitter":
		$change_id = GetNewXref("CHANGE");
		$subid = FindSubmitter($GEDCOMS[$gedfile]["id"]);
		if (!empty($subid)) {
			$record = FindGedcomRecord($subid, $gedfile);
			if (GetChangeData(true, $subid, true, "", "SUBM")) {
				$rec = GetChangeData(false, $subid, true, "gedlines", "SUBM");
				if (isset($rec[$gedfile][$subid])) $record = $rec[$gedfile][$subid];
			}
		}
		else {
			$subid = "new";
			$record = "";
		}
		$gedfile = $GEDCOMS[$gedfile]["id"];
		print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\" style=\"display:inline;\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"update_submitter\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$subid."\" />\n";
		print "<input type=\"hidden\" name=\"gedfile\" value=\"".$gedfile."\" />\n";
		print "<input type=\"hidden\" name=\"fact\" value=\"SUBM\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"submitter_record\" />\n";
		print "<table class=\"facts_table\">";
		SubmitterRecord(0, $record);
		print "</table>";
		if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<br /><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."<br />\n";
		print "<br /><input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
		print "</form>\n";
		$disp = true;
		break;
	// NOTE: Delete
	// NOTE: deleteperson 4/8/2007 ok
	case "deleteperson":
		$change_id = GetNewXref("CHANGE");
		
		if (!$factedit) {
			print "<br />".$gm_lang["privacy_prevented_editing"];
			if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
			if (!empty($famid)) print "<br />".$gm_lang["privacy_not_granted"]." famid $famid.";
		}
		else {
			// TODO: Notify user if record has already been deleted
			// TODO: Add checking 
			if (!empty($gedrec)) {
				
				$success = false;
				// Save the fams for later, to check on empty families
				$fams = Array_Merge(FindSFamilyIDs($pid, true), FindFamilyIDs($pid, "", true));
				
				// Delete all links to this person
				$success = DeleteLinks($pid, "INDI", $change_id, $change_type, $GEDCOMID);
				
				// Remove resulting empty fams
				foreach($fams as $key=>$fam) {
					if ($success) $success = $success && DeleteFamIfEmpty($fam["famid"], $change_id, $change_type, $GEDCOMID);
				}
				
				// Delete the person
				if ($success) $success = $success && DeleteGedrec($pid, $change_id, "delete_indi");
				
				if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
			}
		}
		break;
	// NOTE: deletefamily 4/8/2007 ok
	case "deletefamily":
		$change_id = GetNewXref("CHANGE");
		
		if (!$factedit) {
			print "<br />".$gm_lang["privacy_prevented_editing"];
			if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
			if (!empty($famid)) print "<br />".$gm_lang["privacy_not_granted"]." famid $famid.";
		}
		else {
			if (!empty($gedrec)) {
				$success = false;
				$success = DeleteLinks($famid, "FAM", $change_id, $change_type, $GEDCOMID);
				
				if ($success) {
					$success = $success && DeleteGedrec($famid, $change_id, $change_type);
				}
				if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
			}
		}
		break;
	// NOTE: deletesource 3/8/2007 ok
	case "deletesource":
		$change_id = GetNewXref("CHANGE");
		
		if (!empty($gedrec)) {
			$success = false;
			$success = DeleteLinks($pid, "SOUR", $change_id, $change_type, $GEDCOMID);
			if ($success) {
				$success = $success && DeleteGedrec($pid, $change_id, $change_type);
			}
			if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
		}
		break;
		
	// NOTE: deleterepo 3/8/2007 ok
	case "deleterepo":
		$change_id = GetNewXref("CHANGE");
		
		if (!empty($gedrec)) {
			$success = false;
			$success = DeleteLinks($pid, "REPO", $change_id, $change_type, $GEDCOMID);
			if ($success) {
				$success = $success && DeleteGedrec($pid, $change_id, $change_type);
			}
			if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
		}
		break;
		
	case "deletegnote":
		$change_id = GetNewXref("CHANGE");
		
		if (!empty($gedrec)) {
			$success = false;
			$success = DeleteLinks($pid, "NOTE", $change_id, $change_type, $GEDCOMID);
			if ($success) {
				$success = $success && DeleteGedrec($pid, $change_id, $change_type);
			}
			if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
		}
		break;
		
	case "deletemedia":
		$change_id = GetNewXref("CHANGE");
		if (!$factedit) {
			print "<br />".$gm_lang["privacy_prevented_editing"];
			if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
		}
		else {
			if (!empty($gedrec)) {
				$success = false;
				$success = DeleteLinks($pid, "OBJE", $change_id, $change_type, $GEDCOMID);
				
				if ($success) {
					$success = $success && DeleteGedrec($pid, $change_id, $change_type);
				}
				if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
			}
		}
		break;
		
	// NOTE: delete done
	case "delete":
		$change_id = GetNewXref("CHANGE");
		$success = false;
		// Before using getsubrec, we must get the privatised gedrec. Otherwise the counter is not correct.
		$gedrecpriv = implode("\r\n", GetAllSubrecords($gedrec, "", false, false));
		$oldrec = GetSubRecord(1, "1 $fact", $gedrecpriv, $count);
		$success =  ReplaceGedrec($pid, $oldrec, "", $fact, $change_id, $change_type);
		if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
		break;
	
	// NOTE: Reorder media
	case "reorder_media":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php" style="display:inline;">
			<input type="hidden" name="action" value="reorder_media_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="facts_table">
			<tr><td class="topbottombar" colspan="2">
			<?php print_help_link("reorder_media_help", "qm", "reorder_media"); ?>
			<?php print $gm_lang["reorder_media"]; ?>
			</td></tr>
			<?php
				if (GetChangeData(true, $pid, true, "", "")) {
					$rec = GetChangeData(false, $pid, true, "gedlines", "");
					if (isset($rec[$GEDCOM][$pid])) $gedrec = $rec[$GEDCOM][$pid];
				}
				$ct = preg_match_all("/1\s+OBJE\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
				if ($ct>0) {
					$i=0;
					foreach($fmatch as $key=>$value) {
						print "<tr>\n<td class=\"shade2\">\n";
						print "<select name=\"order[$value[1]]\">\n";
						for($j=0; $j<$ct; $j++) {
							print "<option value=\"".($j)."\"";
							if ($j==$i) print " selected=\"selected\"";
							print ">".($j+1)."</option>\n";
						}
						print "</select>\n";
						print "</td><td class=\"shade1\">";
						print PrintReady(GetMediaDescriptor($value[1]));
						print "</td>\n</tr>\n";
						$i++;
					}
				}
			?>
			<tr><td class="topbottombar" colspan="2">
			<input type="submit" value="<?php print $gm_lang["save"]; ?>" />&nbsp;
			</td</tr>
			</table>
		</form>
		<?php
		break;

	case "reorder_media_update":
		$change_id = GetNewXref("CHANGE");
		$success = true;
		asort($order);
		reset($order);
		$rec = trim($gedrec);
		$lines = count($order);
		$f = array();
		for ($i = 1; $i <= $lines; $i++) {
			$t = getsubrecord(1,"1 OBJE", $gedrec, $i);
			$rc = preg_match("/1 OBJE @(.*)@/", $t, $match);
			$f[$match[1]] = $t;
			$success = $success && (ReplaceGedrec($pid, $f[$match[1]], "", "OBJE", $change_id, $change_type));
		}
		$order = array_flip($order);
		foreach($order as $ord => $famkey) {
			$success = $success && (ReplaceGedrec($pid, "", $f[$famkey], "OBJE", $change_id, $change_type));
		}
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
		
	
	// NOTE: Reorder
	// NOTE: reorder_children 4/8/2007 ok
	case "reorder_children":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php" style="display:inline;">
			<input type="hidden" name="action" value="reorder_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<input type="hidden" name="option" value="bybirth" />
			<table class="facts_table">
			<tr><td class="topbottombar" colspan="2">
			<?php print_help_link("reorder_children_help", "qm", "reorder_children"); ?>
			<?php print $gm_lang["reorder_children"]; ?>
			</td></tr>
			<?php
				$children = array();
				if (GetChangeData(true, $pid, true, "", "")) {
					$rec = GetChangeData(false, $pid, true, "gedlines", "");
					if (isset($rec[$GEDCOM][$pid])) $gedrec = $rec[$GEDCOM][$pid];
				}
				$childids = FindChildrenInRecord($gedrec);
				$ct = count($childids);
				foreach($childids as $key=>$childid) {
					$irec = FindPersonRecord($childid);
					if (isset($indilist[$childid])) $children[$childid] = $indilist[$childid];
					// Always process changed records, they may contain changed birth dates
					if (GetChangeData(true, $childid, true, "", "")) {
						$rec = GetChangeData(false, $childid, true, "gedlines", "");
						if (isset($rec[$GEDCOM][$childid])) $childrec = $rec[$GEDCOM][$childid];
						$children[$childid]["names"] = GetIndiNames($childrec);
						$children[$childid]["gedfile"] = $GEDCOMID;
						$children[$childid]["gedcom"] = $childrec;
					}
				}
				if ((!empty($option))&&($option=="bybirth")) {
					uasort($children, "CompareDate");
				}
				$i=0;
				foreach($children as $pid=>$child) {
					print "<tr>\n<td class=\"shade2\">\n";
					print "<select name=\"order[$pid]\">\n";
					for($j=0; $j<$ct; $j++) {
						print "<option value=\"".($j)."\"";
						if ($j==$i) print " selected=\"selected\"";
						print ">".($j+1)."</option>\n";
					}
					print "</select>\n";
					print "</td><td class=\"shade1\">";
					print PrintReady(GetPersonName($pid, $child["gedcom"]));
					print "<br />";
					print_first_major_fact($pid, $child["gedcom"]);
					print "</td>\n</tr>\n";
					$i++;
				}
			?>
			<tr><td class="topbottombar" colspan="2">
			<input type="submit" value="<?php print $gm_lang["save"]; ?>" />&nbsp;
			<input type="button" value="<?php print $gm_lang["sort_by_birth"]; ?>" onclick="document.reorder_form.action.value='reorder_children'; document.reorder_form.submit();" />
			</td</tr>
			</table>
		</form>
		<?php
		break;
	// NOTE: reorder_update 4/8/2007 ok
	case "reorder_update":
		$change_id = GetNewXref("CHANGE");
		$success = true;
		asort($order);
		reset($order);
		$rec = trim($gedrec);
		$lines = count($order);
		$f = array();
		for ($i = 1; $i <= $lines; $i++) {
			$t = getsubrecord(1,"1 CHIL", $gedrec, $i);
			$rc = preg_match("/1 CHIL @(.*)@/", $t, $match);
			$f[$match[1]] = $t;
			$success = $success && (ReplaceGedrec($pid, $f[$match[1]], "", "CHIL", $change_id, $change_type));
		}
		$order = array_flip($order);
		foreach($order as $ord => $famkey) {
			$success = $success && (ReplaceGedrec($pid, "", $f[$famkey], "CHIL", $change_id, $change_type));
		}
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
		
	// NOTE: reorder_fams 5/8/2007 ok
	case "reorder_fams":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php" style="display:inline;">
			<input type="hidden" name="action" value="reorder_fams_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<input type="hidden" name="option" value="bymarriage" />
			<table class="facts_table">
			<tr><td class="topbottombar <?php print $TEXT_DIRECTION; ?>" colspan="2">
			<?php print_help_link("reorder_families_help", "qm", "reorder_families"); ?>
			<?php print $gm_lang["reorder_families"]; ?>
			</td></tr>
			<?php
			print "<tr class=\"shade2\"><td>".$gm_lang["order"]."</td><td>".$gm_lang["family"]."</td></tr>";
				$fams = array();
				$ct = preg_match_all("/1 FAMS @(.+)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$famid = trim($match[$i][1]);
					$frec = FindFamilyRecord($famid);
					if ($frec===false) $frec = FindGedcomRecord($famid);
					if (isset($famlist[$famid])) $fams[$famid] = $famlist[$famid];
					if (GetChangeData(true, $famid, true, "", "")) {
						$rec = GetChangeData(false, $famid, true, "gedlines", "");
						if (isset($rec[$GEDCOM][$famid])) $fams[$famid]["gedcom"] = $rec[$GEDCOM][$famid];
					}
				}
				if ((!empty($option))&&($option=="bymarriage")) {
					$sortby = "MARR";
					uasort($fams, "CompareDate");
				}
				$i=0;
				foreach($fams as $famid=>$fam) {
					print "<tr>\n<td class=\"shade2\">\n";
					print "<select name=\"order[$famid]\">\n";
					for($j=0; $j<$ct; $j++) {
						print "<option value=\"".($j)."\"";
						if ($j==$i) print " selected=\"selected\"";
						print ">".($j+1)."</option>\n";
					}
					print "</select>\n";
					print "</td><td class=\"shade1\">";
					print PrintReady(GetFamilyDescriptor($famid, false, $fam["gedcom"], true));
					print "<br />";
					print_simple_fact($fam["gedcom"], "MARR", $famid);
					print "</td>\n</tr>\n";
					$i++;
				}
			?>
			<tr><td class="topbottombar" colspan = "2">
			<input type="submit" value="<?php print $gm_lang["save"]; ?>" />
			<input type="button" value="<?php print $gm_lang["sort_by_marriage"]; ?>" onclick="document.reorder_form.action.value='reorder_fams'; document.reorder_form.submit();" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
	// NOTE: reorder_fams_update 5/8/2007 ok
	case "reorder_fams_update":
		$change_id = GetNewXref("CHANGE");
		asort($order);
		reset($order);
		$lines = count($order);
		$f = array();
		$success = true;
		for ($i = 1; $i <= $lines; $i++) {
			$t = getsubrecord(1,"1 FAMS", $gedrec, $i);
			$rc = preg_match("/1 FAMS @(.*)@/", $t, $match);
			$f[$match[1]] = $t;
			$success = $success && ReplaceGedrec($pid, $f[$match[1]], "", "FAMS", $change_id, $change_type);
		}
		$order = array_flip($order);
		foreach($order as $ord => $famkey) {
			$success = $success && ReplaceGedrec($pid, "", $f[$famkey], "FAMS", $change_id, $change_type);
		}
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
		
	// NOTE relation_fams ok 5/8/2007	
	case "relation_fams":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php" style="display:inline;">
			<input type="hidden" name="action" value="relation_fams_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="facts_table">
				<tr><td class="topbottombar <?php print $TEXT_DIRECTION; ?>" colspan="3">
				<?php print_help_link("relation_families_help", "qm", "relation_families"); ?>
				<?php print $gm_lang["relation_families"]; ?>
				</td></tr>
				<?php
				print "<tr class=\"shade2\"><td>".GM_FACT_PEDI."</td><td>".$gm_lang["family"]."</td><td>".$gm_lang["primary"]."</tr>";
				$famids = FindFamilyIds($pid, "", true);
				$hasprimary = false;
				foreach($famids as $famid=>$fam) {
					if (GetChangeData(true, $fam["famid"], true, "", "")) {
						$rec = GetChangeData(false, $fam["famid"], true, "gedlines", "");
						if (isset($rec[$GEDCOM][$fam["famid"]])) $famrec = $rec[$GEDCOM][$fam["famid"]];
					}
					else $famrec = FindFamilyRecord($fam["famid"]);
					print "<tr>\n<td class=\"shade2\">\n";
					PrintPedi("pedi_".$fam["famid"], $fam["relation"]);
					print "</td><td class=\"shade1\">";
					print PrintReady(GetFamilyDescriptor($fam["famid"], false, $famrec, true));
					print "<br />";
					print_simple_fact($famrec, "MARR", $fam["famid"]);
					print "</td>\n<td class=\"shade1\">";
					print "<input type=\"radio\" name=\"select_prim\" value=\"".$fam["famid"]."\" ";
					if ($fam["primary"] == "Y") {
						print "checked=\"checked\" ";
						$hasprimary = true;
					}
					print "></td></tr>\n";
					$i++;
				}
				print "<tr class=\"shade2\"><td>&nbsp;</td><td>".$gm_lang["no_primary"]."</td><td>";
				print "<input type=\"radio\" name=\"select_prim\" value=\"noprim\" ";
				if (!$hasprimary) print "checked=\"checked\" ";
				print "></td></tr>\n";
			?>
			<tr><td class="topbottombar" colspan = "3">
			<input type="submit" value="<?php print $gm_lang["save"]; ?>" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: relation_fams_update ok 5/8/2007
	case "relation_fams_update":
		$change_id = GetNewXref("CHANGE");
		$famids = FindFamilyIds($pid, "", true);
		$success = true;
		foreach($famids as $famid=>$fam) {
			if (GetChangeData(true, $pid, true, "", "")) {
				$rec = GetChangeData(false, $pid, true, "gedlines", "");
				$pidrec = $rec[$GEDCOM][$pid];
			}
			else {
				$pidrec = FindPersonRecord($pid);
			}
			$rec = GetSubRecord(1, "1 FAMC @".$fam["famid"]."@", $pidrec, 1);
			$k = "pedi_".$fam["famid"];
			if ($$k == "birth") $$k = "";
			if ($$k != $fam["relation"]) {
				if ($fam["relation"] == "") {
					$recnew = trim($rec)."\r\n"."2 PEDI ".$$k;
				}
				else {
					$repl = "2 PEDI ".$fam["relation"];
					if (!empty($$k)) $for = "2 PEDI ".$$k;
					else $for = "";
					$recnew = preg_replace("/$repl/", "$for", $rec);
				}
				$success = $success && ReplaceGedrec($pid, $rec, $recnew, "FAMC", $change_id, $change_type);
			}
			if (GetChangeData(true, $pid, true, "", "")) {
				$rec = GetChangeData(false, $pid, true, "gedlines", "");
				$pidrec = $rec[$GEDCOM][$pid];
			}
			else $pidrec = FindPersonRecord($pid);

			$rec = GetSubRecord(1, "1 FAMC @".$fam["famid"]."@", $pidrec, 1);
			if ($fam["famid"] != $select_prim) {
				if ($fam["primary"] == "Y") {
					$recnew = preg_replace("/2 _PRIMARY Y/", "", $rec);
					$success = $success && ReplaceGedrec($pid, $rec, $recnew, "FAMC", $change_id, $change_type);
				}
			}
			else if ($fam["primary"] != "Y") {
				$recnew = trim($rec)."\r\n2 _PRIMARY Y";
				$success = $success && ReplaceGedrec($pid, $rec, $recnew, "FAMC", $change_id, $change_type);
			}
		}
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
		
	// NOTE: changefamily 9/8/2007 ok
	case "changefamily":
		if(GetChangeData(true, $famid, true, "","")) {
			$rec = GetChangeData(false, $famid, true, "gedlines", "");
			$gedrec = $rec[$GEDCOM][$famid];
		}
		$family =& Family::GetInstance($famid, $gedrec);
		$father = $family->husb;
		$mother = $family->wife;
		$children = $family->children;
		if (count($children)>0) {
			if (is_object($father)) {
				if ($father->sex=="F") $father->setLabel($gm_lang["mother"]);
				else $father->setLabel($gm_lang["father"]);
			}
			if (is_object($mother)) {
				if ($mother->sex=="M") $mother->setLabel($gm_lang["father"]);
				else $mother->setLabel($gm_lang["mother"]);
			}
//			for($i=0; $i<count($children); $i++) {
			foreach ($children as $i => $child) {
				if (is_object($children[$i])) {
					if ($children[$i]->sex=="M") $children[$i]->setLabel($gm_lang["son"]);
					else if ($children[$i]->sex=="F") $children[$i]->setLabel($gm_lang["daughter"]);
					else $children[$i]->setLabel($gm_lang["child"]);
				}
			}
		}
		else {
			if (is_object($father)) {
				if ($father->sex=="F") $father->setLabel($gm_lang["wife"]);
				else if ($father->sex=="M") $father->setLabel($gm_lang["husband"]);
				else $father->setLabel($gm_lang["spouse"]);
			}
			if (is_object($mother)) {
				if ($mother->sex=="F") $mother->setLabel($gm_lang["wife"]);
				else if ($mother->sex=="M") $mother->setLabel($gm_lang["husband"]);
				else $father->setLabel($gm_lang["spouse"]);
			}
		}
		?>
		<script type="text/javascript">
		<!--
		var nameElement = null;
		var remElement = null;
		var pediElement = null;
		function pastename(name) {
			if (nameElement) {
				nameElement.innerHTML = Urldecode(name);
			}
			if (remElement) {
				remElement.style.display = 'block';
				pediElement.style.display = 'block';
			}
		}
		//-->
		</script>
		<br /><br />
		<form name="changefamform" method="post" action="edit_interface.php" style="display:inline;">
			<input type="hidden" name="action" value="changefamily_update" />
			<input type="hidden" name="famid" value="<?php print $famid;?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type;?>" />
			<table class="width50 <?php print $TEXT_DIRECTION; ?>">
				<tr><td colspan="4" class="topbottombar"><?php print_help_link("change_family_instr","qm","change_family_instr"); ?><?php print $gm_lang["change_family_members"]; ?></td></tr>
				<tr>
				<?php
				if (is_object($father)) {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $father->getLabel(); ?></b><input type="hidden" name="HUSB" value="<?php print $father->xref;?>" /></td>
					<td id="HUSBName" class="shade1 <?php print $TEXT_DIRECTION; ?>"><?php print PrintReady($father->name); ?><br /><?php print_first_major_fact($father->xref, $father->gedrec); ?></td><td class="shade1">&nbsp;</td>
				<?php
				}
				else {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $gm_lang["father"]; ?></b><input type="hidden" name="HUSB" value="" /></td>
					<td id="HUSBName" class="shade1 <?php print $TEXT_DIRECTION; ?>"></td><td class="shade1">&nbsp;</td>
				<?php
				}
				?>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="husbrem" style="display: <?php print !is_object($father) ? 'none':'block'; ?>;" onclick="document.changefamform.HUSB.value=''; document.getElementById('HUSBName').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('HUSBName'); remElement = document.getElementById('husbrem'); return findIndi(document.changefamform.HUSB);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
				<tr>
				<?php
				if (is_object($mother)) {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $mother->getLabel(); ?></b><input type="hidden" name="WIFE" value="<?php print $mother->xref;?>" /></td>
					<td id="WIFEName" class="shade1 <?php print $TEXT_DIRECTION; ?>"><?php print PrintReady($mother->name); ?><br /><?php print_first_major_fact($mother->xref, $mother->gedrec); ?></td><td class="shade1">&nbsp;</td>
				<?php
				}
				else {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $gm_lang["mother"]; ?></b><input type="hidden" name="WIFE" value="" /></td>
					<td id="WIFEName" class="shade1 <?php print $TEXT_DIRECTION; ?>"></td><td class="shade1">&nbsp;</td>
				<?php
				}
				?>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="wiferem" style="display: <?php print !is_object($mother) ? 'none':'block'; ?>;" onclick="document.changefamform.WIFE.value=''; document.getElementById('WIFEName').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('WIFEName'); remElement = document.getElementById('wiferem'); return findIndi(document.changefamform.WIFE);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
				<?php
				$i=0;
				foreach($children as $key=>$child) {
					if (!is_null($child)) {
						$rec = $child->newgedrec;
						if (empty($rec)) $rec = $child->gedrec;
						$pedirec = GetSubRecord(1, "1 FAMC @".$famid."@", $rec);
						$pedi = GetGedcomValue("PEDI", 2, $pedirec);
					?>
				<tr>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $child->getLabel(); ?></b><input type="hidden" name="CHIL<?php print $i; ?>" value="<?php print $child->xref;?>" /></td>
					<td id="CHILName<?php print $i; ?>" class="shade1"><?php print PrintReady($child->name); ?><br /><?php print_first_major_fact($child->xref, $child->gedrec); ?></td>
					<td id="CHILPedi<?php print $i; ?>" class="shade1"><?php PrintPedi("CHILPedisel".$i, $pedi); ?></td>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="childrem<?php print $i; ?>" style="display:block;" onclick="document.changefamform.CHIL<?php print $i; ?>.value=''; document.getElementById('CHILName<?php print $i; ?>').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('CHILName<?php print $i; ?>'); remElement = document.getElementById('childrem<?php print $i; ?>'); return findIndi(document.changefamform.CHIL<?php print $i; ?>);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
					<?php
						$i++;
					}
				}
				$pedi = "";
					?>
				<tr>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $gm_lang["add_child"]; ?></b><input type="hidden" name="CHIL<?php print $i; ?>" value="" /></td>
					<td id="CHILName<?php print $i; ?>" class="shade1">
					<td id="CHILPedi<?php print $i; ?>" class="shade1"><div id="CHILHide<?php print $i; ?>" style="display: none;"><?php PrintPedi("CHILPedisel".$i, $pedi); ?></div></td>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="childrem<?php print $i; ?>" style="display: none;" onclick="document.changefamform.CHIL<?php print $i; ?>.value=''; document.getElementById('CHILName<?php print $i; ?>').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="pediElement = document.getElementById('CHILHide<?php print $i; ?>'); nameElement = document.getElementById('CHILName<?php print $i; ?>'); remElement = document.getElementById('childrem<?php print $i; ?>'); return findIndi(document.changefamform.CHIL<?php print $i; ?>);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
			<tr>
				<td class="topbottombar" colspan="4">
					<input type="submit" value="<?php print $gm_lang["save"]; ?>" />&nbsp;<input type="button" value="<?php print $gm_lang["cancel"]; ?>" onclick="window.close();" />
				</td>
			</tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: changefamily_update 9/8/2007 ok
	case "changefamily_update":
		$change_id = GetNewXref("CHANGE");
		if(GetChangeData(true, $famid, true, "","")) {
			$rec = GetChangeData(false, $famid, true, "gedlines", "");
			$gedrec = $rec[$GEDCOM][$famid];
		}
		$family =& Family::GetInstance($famid, $gedrec);
		$father = $family->husb;
		$mother = $family->wife;
		$children = $family->children;
		$updated = false;
		$success = true;
		//-- action: replace existing father link
		if (!empty($HUSB) && !empty($father) && $father->xref!=$HUSB) {

			// Replace the father link in the fam record
			$oldrec = GetSubRecord(1, "1 HUSB @$father->xref@", $gedrec);
			$newrec = preg_replace("/1 HUSB @$father->xref@/", "1 HUSB @$HUSB@\r\n", $oldrec);
			if (trim($oldrec) != trim($newrec)) ReplaceGedrec($famid, $oldrec, $newrec, "HUSB", $change_id, $change_type);

			// Remove the fam link from the old father record
			$famids = FindSfamilyIds($father->xref, true);
			$frec = $father->changedgedrec;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) {
					$oldfath = GetSubrecord(1, "1 FAMS @$famid@", $frec, 1);
					$success = $success && ReplaceGedrec($father->xref, $oldfath, "", "FAMS", $change_id, $change_type);
					break;
				}
			}
			
			// Add the fam link to the new father
			$famids = FindSfamilyIds($HUSB, true);
			$found = false;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && ReplaceGedrec($HUSB, "", $newrec, "FAMS", $change_id, $change_type);
			}
			$updated = true;
		}
		//-- action: remove the father link
		if (empty($HUSB) && !empty($father)) {
			
			// Remove the father link from the fam
			$oldfam = GetSubrecord(1, "1 HUSB @$father->xref", $gedrec, 1);
			$success = $success && ReplaceGedrec($famid, $oldfam, "", "HUSB", $change_id, $change_type);
			$updated = true;
			
			// Remove the fam link from the old father record
			$famids = FindSfamilyIds($father->xref, true);
			$frec = $father->changedgedrec;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) {
					$oldfath = GetSubrecord(1, "1 FAMS @$famid@", $frec, 1);
					$success = $success && ReplaceGedrec($father->xref, $oldfath, "", "FAMS", $change_id, $change_type);
					break;
				}
			}
			$updated = true;
		}
		//-- Add the father link
		if (!empty($HUSB) && empty($father)) {
			
			// Add the father link to the fam
			$hrec = GetSubrecord(1, "1 HUSB @$HUSB@", $gedrec, 1);
			if (empty($hrec)) {
				$newrec = "1 HUSB @$HUSB@\r\n";
				$success = $success && ReplaceGedrec($famid, "", $newrec, "HUSB", $change_id, $change_type);
			}
			
			// Add the fam link to the new father
			$famids = FindSfamilyIds($HUSB, true);
			$found = false;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && ReplaceGedrec($HUSB, "", $newrec, "FAMS", $change_id, $change_type);
			}
			$updated = true;
		}
		
		//-- action: replace existing mother link
		if (!empty($WIFE) && !empty($mother) && $mother->xref!=$WIFE) {
			
			// Replace the mother link in the fam record
			$oldrec = GetSubRecord(1, "1 WIFE @$mother->xref@", $gedrec);
			$newrec = preg_replace("/1 WIFE @$father->xref@/", "1 WIFE @$HUSB@\r\n", $oldrec);
			if (trim($oldrec) != trim($newrec)) ReplaceGedrec($famid, $oldrec, $newrec, "WIFE", $change_id, $change_type);
			
			// Remove the fam link from the old mother record
			$famids = FindSfamilyIds($mother->xref, true);
			$mrec = $mother->changedgedrec;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) {
					$oldmoth = GetSubrecord(1, "1 FAMS @$famid@", $mrec, 1);
					$success = $success && ReplaceGedrec($mother->xref, $oldmoth, "", "FAMS", $change_id, $change_type);
					break;
				}
			}
			
			// Add the fam link to the new mother
			$famids = FindSfamilyIds($WIFE, true);
			$found = false;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && ReplaceGedrec($WIFE, "", $newrec, "FAMS", $change_id, $change_type);
			}
			$updated = true;
		}
		
		//-- action: remove the mother link
		if (empty($WIFE) && !empty($mother)) {
			
			// Remove the mother link from the fam
			$oldfam = GetSubrecord(1, "1 WIFE @$mother->xref", $gedrec, 1);
			$success = $success && ReplaceGedrec($famid, $oldfam, "", "WIFE", $change_id, $change_type);
			$updated = true;
			
			// Remove the fam link from the old mother record
			$famids = FindSfamilyIds($mother->xref, true);
			$mrec = $mother->changedgedrec;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) {
					$oldmoth = GetSubrecord(1, "1 FAMS @$famid@", $mrec, 1);
					$success = $success && ReplaceGedrec($mother->xref, $oldmoth, "", "FAMS", $change_id, $change_type);
					break;
				}
			}
			$updated = true;
		}
		
		//-- action: Add the mother link
		if (!empty($WIFE) && empty($mother)) {
			
			// Add the mother link to the fam
			$mrec = GetSubrecord(1, "1 WIFE @$WIFE@", $gedrec, 1);
			if (empty($mrec)) {
				$newrec = "1 WIFE @$WIFE@\r\n";
				$success = $success && ReplaceGedrec($famid, "", $newrec, "WIFE", $change_id, $change_type);
			}
			
			// Add the fam link to the new mother
			$famids = FindSfamilyIds($WIFE, true);
			$found = false;
			foreach ($famids as $key => $fam) {
				if ($fam["famid"] == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && ReplaceGedrec($WIFE, "", $newrec, "FAMS", $change_id, $change_type);
			}
			$updated = true;
		}
		
		//-- update the children
		$i=0;
		$var = "CHIL".$i;
		$varp = "CHILPedisel".$i;
		$newchildren = array();
		while(isset($$var)) {
			$CHIL = $$var;
			if (!empty($CHIL)) {
				if (isset($$varp)) $pedi = $$varp;
				else $pedi = "";
				if ($pedi == "birth") $pedi = "";
				$newchildren[] = $CHIL;
				if (GetChangeData(true, $CHIL, false, "", "")) {
					$rec = GetChangeData(false, $CHIL, false, "gedlines", "");
					$indirec = $rec[$GEDCOM][$CHIL];
				}					
				else $indirec = FindGedcomRecord($CHIL);
				// NOTE: Check if child is already in family record
				// NOTE: If not, add it to the family record
				if (preg_match("/1 CHIL @$CHIL@/", $gedrec)==0) {
					$newrec = "1 CHIL @$CHIL@\r\n";
					$success = $success && ReplaceGedrec($famid, "", $newrec, "CHIL", $change_id, $change_type);
					$updated = true;
					// NOTE: Check for a family reference in the child record
					if (!empty($indirec) && (preg_match("/1 FAMC @$famid@/", $indirec)==0)) {
						$newrec = "1 FAMC @$famid@\r\n";
						if (!empty($pedi)) $newrec .= "2 PEDI ".$pedi."\r\n";
						$success = $success && ReplaceGedrec($CHIL, "", $newrec, "FAMC", $change_id, $change_type);
					}
				}
				// NOTE: it's already there, check for PEDI update
				else {
					$oldfrec = GetSubRecord(1, "1 FAMC @$famid@", $indirec, 1);
					$oldprec = GetSubrecord(2, "2 PEDI", $oldfrec);
					$pediold = GetGedcomValue("PEDI", 2, $oldprec);
					if (empty($pediold) && (!empty($pedi))) {
						$newrec = trim($oldfrec) . "\r\n"."2 PEDI ".$pedi; 
						$success = $success && ReplaceGedrec($CHIL, $oldfrec, $newrec, "FAMC", $change_id, $change_type);
					}
					else if (!empty($pediold) && !empty($pedi)) {
						$newrec = preg_replace("/2 PEDI $pediold/", "2 PEDI $pedi", $oldfrec);
						$success = $success && ReplaceGedrec($CHIL, $oldfrec, $newrec, "FAMC", $change_id, $change_type);
					}
					else if (!empty($pediold) && empty($pedi)) {
						$newrec = preg_replace("/$oldprec/", "", $oldfrec);
						$success = $success && ReplaceGedrec($CHIL, $oldfrec, $newrec, "FAMC", $change_id, $change_type);
					}
				}
			}
			$i++;
			$var = "CHIL".$i;
			$varp = "CHILPedisel".$i;
		}
		
		//-- remove the old children
		foreach($children as $key=>$child) {
			if (!is_null($child)) {
				if (!in_array($child->xref, $newchildren)) {
					//-- remove the CHIL link from the family record
					$oldrec = GetSubRecord(1, "1 CHIL @$child->xref@", $gedrec, 1);
					$success = $success && ReplaceGedrec($famid, $oldrec, "", "CHIL", $change_id, $change_type);
					
					//-- remove the FAMC link from the child record
					$chgedrec = $child->changedgedrec;
					$oldrec = GetSubrecord(1, "1 FAMC @$famid@", $chgedrec, 1);
					$success = $success && ReplaceGedrec($child->xref, $oldrec, "" , "FAMC", $change_id, $change_type);
				}
			}
		}
		
		// Check if any family members are there
		if (GetChangeData(true, $famid, true, "", "")) {
			$rec = GetChangeData(false, $famid, true, "gedlines");
			$newfamrec = $rec[$GEDCOM][$famid];
			$ct = preg_match("/1 CHIL|HUSB|WIFE/", $newfamrec, $match);
			if ($ct == 0) $success = $success && ReplaceGedRec($famid, $newfamrec, "", "FAM", $change_id, $change_type);
		}
		
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
	
	// NOTE: Add
	// NOTE: add 24/8/2007 ok
	case "add":
		// Get the record type
		$ct = preg_match("/0\s@\w+@\s(\w+)/", $gedrec, $match);
		if ($ct>0) $rectype = $match[1];
		else $rectype = "";

		// handle  MARRiage TYPE
		$type_val="";
//		if (substr($fact,0,5)=="MARR_") {
//			$type_val=substr($fact,5);
//			$fact="MARR";
//		}
		
		if ($fact=="OBJE" && $change_type == "create_media") {
			ShowMediaForm($pid);
		}
		else {
			$tags=array();
			$tags[0]=$fact;
			init_calendar_popup();
			print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\" style=\"display:inline;\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"update\" />\n";
			print "<input type=\"hidden\" name=\"fact\" value=\"".$fact."\" />\n";
			if ($change_type != "add_media_link") print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
			print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
			print "<table class=\"facts_table\">";
			
			if (!in_array($fact, $separatorfacts)) AddTagSeparator($fact);
			if ($change_type == "add_media_link") {
				AddTagSeparator($fact);
				print "<tr><td class=\"shade2 $TEXT_DIRECTION\">";
				print_help_link("find_add_mmlink", "qm", "find_add_mmlink");
				print $gm_lang["find_add_mmlink"]."</td>";
				print "<td class=\"shade1 $TEXT_DIRECTION\">";
                print "<input type=\"text\" name=\"pid\" size=\"4\" tabindex=\"1\"/>";
				print "<input type=\"hidden\" name=\"glevels[]\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"islink[]\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"tag[]\" value=\"OBJE\" />\n";
				print "<input type=\"hidden\" name=\"text[]\" value=\"".$pid."\" />\n";
			 	LinkFunctions::PrintFindIndiLink("pid", $GEDCOMID);
			 	LinkFunctions::PrintFindFamilyLink("pid");
			 	LinkFunctions::PrintFindSourceLink("pid");
			 	print "</td></tr>";
		 	}
			if ($fact=="GNOTE") {
//				$fact = "NOTE";
				$focus = AddSimpleTag("1 NOTE @");
			}
			else if ($fact=="SOUR") $focus = AddSimpleTag("1 SOUR @");
			else if ($fact=="ASSO") $focus = AddSimpleTag("1 ASSO @");
			else if ($fact=="OBJE") {
				if ($change_type != "add_media_link") $focus = AddSimpleTag("1 OBJE @");
				// 3 _PRIM
				AddSimpleTag("2 _PRIM", "", "2");
				// 3 _THUM
				AddSimpleTag("2 _THUM");
			}
			else {
				if (!in_array($fact, $emptyfacts)) $focus = AddSimpleTag("1 ".$fact);
				else AddSimpleTag("1 ".$fact);
			}
			
			AddMissingTags(array($fact));
		
			if ($fact=="SOUR") {
				// 1 SOUR
				// 2 PAGE
				AddSimpleTag("2 PAGE");
				// 2 DATA
				AddSimpleTag("2 DATA");
				// 3 DATE
				// 3 TEXT
				AddSimpleTag("3 DATE");
				AddSimpleTag("3 TEXT");
			}
			if ($fact=="ASSO") {
				// 2 RELA
				AddSimpleTag("2 RELA");
			}
			if ($fact!="OBJE" && $rectype != "NOTE" && $rectype != "OBJE" && $fact!="RESN") {
				// 2 RESN
				AddSimpleTag("2 RESN");
			}

			if ($rectype != "OBJE" && $rectype != "NOTE" && $fact!="RESN") {
				print "<tr><td colspan=\"2\">";
				if ($fact != "ASSO" && $fact != "OBJE" && $fact != "REPO" &&  $fact!="SOUR" && $fact != "NOTE" && $fact != "GNOTE") PrintAddLayer("ASSO");
				if ($fact != "SOUR" && $fact != "OBJE" && $fact != "REPO" && $fact != "GNOTE") PrintAddLayer("SOUR");
				if ($fact != "OBJE" && $fact != "REPO" && $fact != "NOTE" && $fact != "GNOTE") PrintAddLayer("OBJE");
				if ($fact != "NOTE" && $fact != "OBJE" && $fact != "GNOTE") {
					PrintAddLayer("NOTE");
					PrintAddLayer("GNOTE");
				}
				print"</td></tr>";
			}
			if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<tr><td class=\"shade1\" colspan=\"2\"><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."</td></tr>";
			print "<tr><td class=\"topbottombar\" colspan=\"2\">";
			print "<input type=\"submit\" value=\"".$gm_lang["add"]."\" /></td></tr>\n";
			print "</table>";
			print "</form>\n";
			if (isset($focus)) {
				?>
				<script>
				<!--
				document.getElementById('<?php print $focus; ?>').focus();
				//-->
				</script>
				<?php
			}
		}
		break;
		
	// NOTE: paste done
	case "paste":
		$success = false;
		$newrec = $_SESSION["clipboard"][$fact]["factrec"];
		$change_id = GetNewXref("CHANGE");
		$ct = preg_match("/1\s(\w+).*/", $newrec, $match);
		if ($ct > 0) {
			$fact = $match[1];
			$success = ReplaceGedrec($pid, "", $newrec, $fact, $change_id, $change_type);
		}
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
		
	// NOTE: addchild done
	case "addchild":
		PrintIndiForm("addchildaction", $famid);
		break;
		
	// NOTE: addchildaction done
	case "addchildaction":
		$change_id = GetNewXref("CHANGE");
		
		// NOTE: Check if there is a source to be added to the facts
		$addsourcevalue = "";
		if (!isset($addsource)) $addsource = false;
		$key = array_search("SOUR", $tag);
		if (($addsource) && ($key !== false) && (!empty($text[$key]))) {
			if ($addsource == '1') $addsourcevalue = "2 SOUR @".$text[$key]."@\r\n";
		}
		$gedrec = "0 @REF@ INDI\r\n";
		if (!empty($NAME)) $gedrec .= "1 NAME ".trim($NAME)."\r\n";
		if (!empty($NPFX)) $gedrec .= "2 NPFX $NPFX\r\n";
		if (!empty($GIVN)) $gedrec .= "2 GIVN $GIVN\r\n";
		if (!empty($NICK)) $gedrec .= "2 NICK $NICK\r\n";
		if (!empty($SPFX)) $gedrec .= "2 SPFX $SPFX\r\n";
		if (!empty($SURN)) $gedrec .= "2 SURN $SURN\r\n";
		if (!empty($NSFX)) $gedrec .= "2 NSFX $NSFX\r\n";
		if (!empty($_MARNM)) $gedrec .= "2 _MARNM $_MARNM\r\n";
		if (!empty($_HEB)) $gedrec .= "2 _HEB $_HEB\r\n";
		if (!empty($ROMN)) $gedrec .= "2 ROMN $ROMN\r\n";
		$gedrec .= "1 SEX $SEX\r\n";
		if ((!empty($BIRT_DATE))||(!empty($BIRT_PLAC))) {
			$gedrec .= "1 BIRT\r\n";
			if (!empty($BIRT_DATE)) {
				$BIRT_DATE = CheckInputDate($BIRT_DATE);
				$gedrec .= "2 DATE $BIRT_DATE\r\n";
				if (!empty($BIRT_TIME)) $gedrec .= "3 TIME $BIRT_TIME\r\n";
			}
			if (!empty($BIRT_PLAC)) $gedrec .= "2 PLAC $BIRT_PLAC\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($BIRT)) {
			$gedrec .= "1 BIRT Y\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		
		if ((!empty($CHR_DATE))||(!empty($CHR_PLAC))) {
			$gedrec .= "1 CHR\r\n";
			if (!empty($CHR_DATE)) {
				$CHR_DATE = CheckInputDate($CHR_DATE);
				$gedrec .= "2 DATE $CHR_DATE\r\n";
				if (!empty($CHR_TIME)) $gedrec .= "3 TIME $CHR_TIME\r\n";
			}
			if (!empty($CHR_PLAC)) $gedrec .= "2 PLAC $CHR_PLAC\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($CHR)) {
			$gedrec .= "1 CHR Y\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}

		if ((!empty($DEAT_DATE))||(!empty($DEAT_PLAC))) {
			$gedrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = CheckInputDate($DEAT_DATE);
				$gedrec .= "2 DATE $DEAT_DATE\r\n";
				if (!empty($DEAT_TIME)) $gedrec .= "3 TIME $DEAT_TIME\r\n";
			}
			if (!empty($DEAT_PLAC)) $gedrec .= "2 PLAC $DEAT_PLAC\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($DEAT)) {
			$gedrec .= "1 DEAT Y\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		
		if (!empty($famid)) {
			$gedrec .= "1 FAMC @$famid@\r\n";
			if(!empty($PEDI) && $PEDI != "birth") $gedrec .= "2 PEDI ".$PEDI;
		}
		$gedrec = HandleUpdates($gedrec);
		if ($addsource == "2") {
			$addsourcevalue = GetSubRecord(1, "1 SOUR", $gedrec);
			$addsourcevalue = substr(preg_replace("/\n(\d) /e", "'\n'.SumNums($1, 1).' '", $addsourcevalue),1);
			if (!empty($addsourcevalue)) $addsourcevalue = "2".$addsourcevalue;
		}
		if ($addsource) $gedrec = preg_replace("/2 SOUR @XXX@\r\n/", $addsourcevalue, $gedrec); 
		
		// NOTE: New record is appended to the database
		$xref = AppendGedrec($gedrec, "INDI", $change_id, $change_type);
		
		if ($xref && !empty($famid)) {
			print "<br /><br />".$gm_lang["update_successful"];
			$newrec = "";
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!GetChangeData(true, $famid, true)) $newrec = FindGedcomRecord($famid);
			else {
				$rec = GetChangeData(false, $famid, true, "gedlines");
				$newrec = $rec[$GEDCOM][$famid];
			}
			$newrec = trim($newrec);
			// NOTE: Check if the record already has a link to this family
			$ct = preg_match("/1 CHIL @$xref@/", $newrec, $match);
			if ($ct == 0) {
				$newrec = "1 CHIL @$xref@\r\n";
				ReplaceGedrec($famid, "", $newrec, "CHIL", $change_id, $change_type);
				$success = true;
			}
			else print $famid." &lt -- &gt ".$xref.": ".$gm_lang["link_exists"];
		}
		break;
	
	// NOTE: addspouse done
	case "addspouse":
		PrintIndiForm("addspouseaction", $famid, "", "", $famtag);
		break;
		
	// NOTE: addspouseaction done
	case "addspouseaction":
		$change_id = GetNewXref("CHANGE");
		
		// NOTE: Check if there is a source to be added to the facts
		if (!isset($addsource)) $addsource = false;
		$addsourcevalue = "";
		$key = array_search("SOUR", $tag);
		if (($addsource) && ($key !== false) && (!empty($text[$key]))) {
			if ($addsource == '1') $addsourcevalue = "2 SOUR @".$text[$key]."@\r\n";
		}
		$newrec = "0 @REF@ INDI\r\n";
		if (!empty($NAME)) $newrec .= "1 NAME ".trim($NAME)."\r\n";
		if (!empty($NPFX)) $newrec .= "2 NPFX $NPFX\r\n";
		if (!empty($GIVN)) $newrec .= "2 GIVN $GIVN\r\n";
		if (!empty($NICK)) $newrec .= "2 NICK $NICK\r\n";
		if (!empty($SPFX)) $newrec .= "2 SPFX $SPFX\r\n";
		if (!empty($SURN)) $newrec .= "2 SURN $SURN\r\n";
		if (!empty($NSFX)) $newrec .= "2 NSFX $NSFX\r\n";
		if (!empty($_MARNM)) $newrec .= "2 _MARNM $_MARNM\r\n";
		if (!empty($_HEB)) $newrec .= "2 _HEB $_HEB\r\n";
		if (!empty($ROMN)) $newrec .= "2 ROMN $ROMN\r\n";
		$newrec .= "1 SEX $SEX\r\n";
		if ((!empty($BIRT_DATE))||(!empty($BIRT_PLAC))) {
			$newrec .= "1 BIRT\r\n";
			if (!empty($BIRT_DATE)) {
				$BIRT_DATE = CheckInputDate($BIRT_DATE);
				$newrec .= "2 DATE $BIRT_DATE\r\n";
				if (!empty($BIRT_TIME)) $newrec .= "3 TIME $BIRT_TIME\r\n";
			}
			if (!empty($BIRT_PLAC)) $newrec .= "2 PLAC $BIRT_PLAC\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($BIRT)) {
			$newrec .= "1 BIRT Y\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		
		if ((!empty($CHR_DATE))||(!empty($CHR_PLAC))) {
			$gedrec .= "1 CHR\r\n";
			if (!empty($CHR_DATE)) {
				$CHR_DATE = CheckInputDate($CHR_DATE);
				$gedrec .= "2 DATE $CHR_DATE\r\n";
				if (!empty($CHR_TIME)) $gedrec .= "3 TIME $CHR_TIME\r\n";
			}
			if (!empty($CHR_PLAC)) $gedrec .= "2 PLAC $CHR_PLAC\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($CHR)) {
			$gedrec .= "1 CHR Y\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		
		if ((!empty($DEAT_DATE))||(!empty($DEAT_PLAC))) {
			$newrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = CheckInputDate($DEAT_DATE);
				$newrec .= "2 DATE $DEAT_DATE\r\n";
				if (!empty($DEAT_TIME)) $newrec .= "3 TIME $DEAT_TIME\r\n";
			}
			if (!empty($DEAT_PLAC)) $newrec .= "2 PLAC $DEAT_PLAC\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($DEAT)) {
			$newrec .= "1 DEAT Y\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		
		$newrec = HandleUpdates($newrec);
		if ($addsource == "2") {
			$addsourcevalue = GetSubRecord(1, "1 SOUR", $newrec);
			$addsourcevalue = substr(preg_replace("/\n(\d) /e", "'\n'.SumNums($1, 1).' '", $addsourcevalue),1);
			if (!empty($addsourcevalue)) $addsourcevalue = "2".$addsourcevalue;
		}
		if ($addsource) $newrec = preg_replace("/2 SOUR @XXX@\r\n/", $addsourcevalue, $newrec); 
		// NOTE: Save the new indi record and get the new ID
		$xref = AppendGedrec($newrec, "INDI", $change_id, $change_type);
		
		if ($xref) print "<br /><br />".$gm_lang["update_successful"];
		else exit;
		
		$success = true;
		if ($famid=="new") {
			$famrec = "0 @new@ FAM\r\n";
			if ($SEX=="M") $famtag = "HUSB";
			if ($SEX=="F") $famtag = "WIFE";
			if ($famtag=="HUSB") {
				$famrec .= "1 HUSB @$xref@\r\n";
				$famrec .= "1 WIFE @$pid@\r\n";
			}
			else {
				$famrec .= "1 WIFE @$xref@\r\n";
				$famrec .= "1 HUSB @$pid@\r\n";
			}
			if ((!empty($MARR_DATE))||(!empty($MARR_PLAC))) {
				$famrec .= "1 MARR\r\n";
				if (!empty($MARR_DATE)) {
					$MARR_DATE = CheckInputDate($MARR_DATE);
					$famrec .= "2 DATE $MARR_DATE\r\n";
				}
				if (!empty($MARR_PLAC)) $famrec .= "2 PLAC $MARR_PLAC\r\n";
				if ($addsource) $famrec .= $addsourcevalue;
			}
			else if (!empty($MARR)) {
				$famrec .= "1 MARR Y\r\n";
				if ($addsource) $famrec .= $addsourcevalue;
			}
			$famid = AppendGedrec($famrec, "FAM", $change_id, $change_type);
		}
		else if (!empty($famid)) {
			$famrec = "";
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!GetChangeData(true, $famid, true)) $famrec = FindGedcomRecord($famid);
			else $famrec = GetChangeData(false, $famid, true, "gedlines");
			if (!empty($famrec)) {
				$famrec = trim($famrec);
				$newrec = "1 $famtag @$xref@\r\n";
				ReplaceGedrec($famid, "", $newrec, $fact, $change_id, $change_type);
			}
		}
		if ((!empty($famid)) && ($famid != "new")) {
			$newrec = "";
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!GetChangeData(true, $xref, true)) $newrec = FindGedcomRecord($xref);
			else $newrec = GetChangeData(false, $xref, true, "gedlines");
			$newrec = trim($newrec[$GEDCOM][$xref]);
			// NOTE: Check if the record already has a link to this family
			$ct = preg_match("/1 FAMS @$famid@/", $newrec, $match);
			if ($ct == 0) {
				$newrec = "1 FAMS @$famid@\r\n";
				ReplaceGedrec($xref, "", $newrec, "FAMS", $change_id, $change_type);
			}
			else print $famid." &lt -- &gt ".$xref.": ".$gm_lang["link_exists"];
		}
		if (!empty($pid)) {
			$newrec = "";
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!GetChangeData(true, $pid, true)) $newrec = trim(FindGedcomRecord($pid));
			else {
				$rec = GetChangeData(false, $pid, true, "gedlines");
				$newrec = $rec[$GEDCOM][$pid];
			}
			// NOTE: Check if the record already has a link to this family
			$ct = preg_match("/1 FAMS @$famid@/", $newrec, $match);
			if ($ct == 0) {
				$newrec = "1 FAMS @$famid@\r\n";
				ReplaceGedrec($pid, "", $newrec, "FAMS", $change_id, $change_type);
			}
			else print $famid." &lt --- &gt ".$pid.": ".$gm_lang["link_exists"];
		}
		break;
	
	case "addnewfamlink":
		print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\" style=\"display:inline;\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linknewfamaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<table class=\"facts_table\">";
		print "<tr>";
		AddTagSeparator($change_type);
		print "</tr>";
		if ($famtag == "CHIL"){
			$ids = FindFamilyIDs($pid, "", true);
			$showbio = true;
			foreach($ids as $key => $fam) {
				if ($fam["relation"] == "") {
					$showbio = false;
					break;
				}
			}
			print "<tr><td class=\"shade2\">".GM_FACT_PEDI."</td>";
			print "<td class=\"shade1\">";
			PrintPedi("PEDI", "", $showbio);
			print "</td></tr>";
		}
		if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<tr><td class=\"shade1\" colspan=\"2\"><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."</td></tr>";
		print "\n<tr><td class=\"topbottombar\" colspan=\"2\">";
		print "<input type=\"submit\" value=\"".$gm_lang["add"]."\" />\n";
		print "\n</td></tr>";
		print "</table>\n";
		print "</form>\n";
		break;

	case "linknewfamaction":
		// NOTE: Get a change_id
		$change_id = GetNewXref("CHANGE");
		$success = false;
		
		if ($famtag == "CHIL") {
			// Create the new family
			$newrec = "0 @REF@ FAM\r\n1 CHIL @".$pid."@\r\n";
			$famid = AppendGedrec($newrec, "FAM", $change_id, $change_type);
			if (!empty($famid)) $success = true;
			
			// Append the famid to the childs record
			$newrec = "1 FAMC @".$famid."@\r\n";
			if (isset($PEDI) && $PEDI != "birth") $newrec .= "2 PEDI ".$PEDI."\r\n";
			$success = $success && ReplaceGedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
		}
		else {
			// Create the new family
			if ($famtag == "HUSB") $newrec = "0 @REF@ FAM\r\n1 HUSB @".$pid."@\r\n";
			else $newrec = "0 @REF@ FAM\r\n1 WIFE @".$pid."@\r\n";
			$famid = AppendGedrec($newrec, "FAM", $change_id, $change_type);
			if (!empty($famid)) $success = true;
			
			// Append the famid to the persons record
			$newrec = "1 FAMS @".$famid."@\r\n";
			$success = $success && ReplaceGedrec($pid, "", $newrec, "FAMS", $change_id, $change_type);
		}
				
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;			

		
	// NOTE: addfamlink 5/8/2007 link as husb ok
	case "addfamlink":
		print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\" style=\"display:inline;\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linkfamaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<table class=\"facts_table\">";
		print "<tr>";
		AddTagSeparator("link_as_child");
		print "<td class=\"shade2\">".$gm_lang["family"]."</td>";
		print "<td class=\"shade1\"><input type=\"text\" id=\"famid\" name=\"famid\" size=\"8\" onblur=\"sndReq('famlink', 'getfamilydescriptor', 'famid', this.value, '', '');\"/> ";
		LinkFunctions::PrintFindFamilyLink("famid");
		print "&nbsp;<span id=\"famlink\"></span>";
		print "\n</td></tr>";
		if ($famtag == "CHIL") {
			print "<tr><td class=\"shade2\">".GM_FACT_PEDI."</td>";
			print "<td class=\"shade1\">";
			PrintPedi("PEDI");
			print "</td></tr>";
		}
		if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<tr><td class=\"shade1\" colspan=\"2\"><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."</td></tr>";
		print "\n<tr><td class=\"topbottombar\" colspan=\"2\">";
		print "<input type=\"submit\" id=\"submit\" value=\"".$gm_lang["set_link"]."\" />\n";
		print "\n</td></tr>";
		print "</table>\n";
		print "</form>\n";
		print "<script>";
		print "<!--\naddchildform.famid.focus();\n//-->";
		print "</script>";
		break;
		
		// NOTE: linkfamaction done
	case "linkfamaction":
		if (!empty($famid)) {
			// NOTE: Get a change_id
			$change_id = GetNewXref("CHANGE");
			$famid = str2upper($famid);
			$success = true;
	
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!GetChangeData(true, $famid, true)) $famrec = FindGedcomRecord($famid);
			else {
				$rec = GetChangeData(false, $famid, true, "gedlines");
				$famrec = $rec[$GEDCOM][$famid];
			}
			
			if (!empty($famrec)) {
				$famrec = trim($famrec)."\r\n";
				$itag = "FAMC";
				if ($famtag=="HUSB" || $famtag=="WIFE") $itag="FAMS";
				
				//-- if it is adding a new child to a family
				if ($famtag=="CHIL") {
					if (preg_match("/1 $famtag @$pid@/", $famrec)==0) {
						// NOTE: Notify the session of change
						$newrec = "1 $famtag @$pid@\r\n";
						$success = $success && ReplaceGedrec($famid, "", $newrec, $famtag, $change_id, $change_type);
						$newrec = "1 $itag @$famid@\r\n";
						if (isset($PEDI) && $PEDI != "birth") $newrec .= "2 PEDI ".$PEDI."\r\n";
						$success = $success && ReplaceGedrec($pid, "", $newrec, $itag, $change_id, $change_type);
					}
				}
				//-- if it is adding a husband or wife
				else {
					//-- check if the family already has a HUSB or WIFE
					$ct = preg_match("/1 $famtag @(.*)@/", $famrec, $match);
					if ($ct>0) {
						$spid = trim($match[1]);
						if ($famtag == "HUSB") print $gm_lang["husb_present"];
						else if ($famtag == "WIFE") print $gm_lang["wife_present"];
						print "<br />";
						print constant("GM_FACT_".$famtag).": ".PrintReady(GetPersonName($spid));
						$success = false;
					}
					else {
						// NOTE: Update the individual record for the person
						if (preg_match("/1 $itag @$famid@/", $gedrec)==0) {
							$newrec = "1 $itag @$famid@\r\n";
							$success = $success && ReplaceGedrec($pid, "", $newrec, $itag, $change_id, $change_type);
						}
						
						$newrec = "1 $famtag @$pid@\r\n";
						$success = $success && ReplaceGedrec($famid, "", $newrec, $famtag, $change_id, $change_type);
					}
				}
				if ($success) print "<br /><br />".$gm_lang["update_successful"];
			}
			else print "<span class=\"error\">".$gm_lang["family_not_found"].": ".$famid."</span>";
		}
		break;	
		
		
	// NOTE: addname done
	case "addname":
		PrintIndiForm("update", "", "new", "NEW");
		break;
	// NOTE: addnewparent done
	
	case "addnewparent":
		PrintIndiForm("addnewparentaction", $famid, "", "", $famtag);
		break;
		
	// NOTE: addnewparentaction done
	case "addnewparentaction":
		$change_id = GetNewXref("CHANGE");
		
		// NOTE: Check if there is a source to be added to the facts
		if (!isset($addsource)) $addsource = false;
		$addsourcevalue = "";
		$key = array_search("SOUR", $tag);
		if (($addsource) && ($key !== false) && (!empty($text[$key]))) {
			if ($addsource == '1') $addsourcevalue = "2 SOUR @".$text[$key]."@\r\n";
		}
		$newrec = "0 @REF@ INDI\r\n";
		if (!empty($NAME)) $newrec .= "1 NAME ".trim($NAME)."\r\n";
		if (!empty($NPFX)) $newrec .= "2 NPFX $NPFX\r\n";
		if (!empty($GIVN)) $newrec .= "2 GIVN $GIVN\r\n";
		if (!empty($NICK)) $newrec .= "2 NICK $NICK\r\n";
		if (!empty($SPFX)) $newrec .= "2 SPFX $SPFX\r\n";
		if (!empty($SURN)) $newrec .= "2 SURN $SURN\r\n";
		if (!empty($NSFX)) $newrec .= "2 NSFX $NSFX\r\n";
		if (!empty($_MARNM)) $newrec .= "2 _MARNM $_MARNM\r\n";
		if (!empty($_HEB)) $newrec .= "2 _HEB $_HEB\r\n";
		if (!empty($ROMN)) $newrec .= "2 ROMN $ROMN\r\n";
		$newrec .= "1 SEX $SEX\r\n";
		if ((!empty($BIRT_DATE))||(!empty($BIRT_PLAC))) {
			$newrec .= "1 BIRT\r\n";
			if (!empty($BIRT_DATE)) {
				$BIRT_DATE = CheckInputDate($BIRT_DATE);
				$newrec .= "2 DATE $BIRT_DATE\r\n";
				if (!empty($BIRT_TIME)) $newrec .= "3 TIME $BIRT_TIME\r\n";
			}
			if (!empty($BIRT_PLAC)) $newrec .= "2 PLAC $BIRT_PLAC\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($BIRT)) {
			$newrec .= "1 BIRT Y\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		
		if ((!empty($CHR_DATE))||(!empty($CHR_PLAC))) {
			$gedrec .= "1 CHR\r\n";
			if (!empty($CHR_DATE)) {
				$CHR_DATE = CheckInputDate($CHR_DATE);
				$gedrec .= "2 DATE $CHR_DATE\r\n";
				if (!empty($CHR_TIME)) $gedrec .= "3 TIME $CHR_TIME\r\n";
			}
			if (!empty($CHR_PLAC)) $gedrec .= "2 PLAC $CHR_PLAC\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($CHR)) {
			$gedrec .= "1 CHR Y\r\n";
			if ($addsource) $gedrec .= "2 SOUR @XXX@\r\n";
		}
		
		if ((!empty($DEAT_DATE))||(!empty($DEAT_PLAC))) {
			$newrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = CheckInputDate($DEAT_DATE);
				$newrec .= "2 DATE $DEAT_DATE\r\n";
				if (!empty($DEAT_TIME)) $newrec .= "3 TIME $DEAT_TIME\r\n";
			}
			if (!empty($DEAT_PLAC)) $newrec .= "2 PLAC $DEAT_PLAC\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($DEAT)) {
			$newrec .= "1 DEAT Y\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		
		$newrec = HandleUpdates($newrec);
		if ($addsource == "2") {
			$addsourcevalue = GetSubRecord(1, "1 SOUR", $newrec);
			$addsourcevalue = substr(preg_replace("/\n(\d) /e", "'\n'.SumNums($1, 1).' '", $addsourcevalue),1);
			if (!empty($addsourcevalue)) $addsourcevalue = "2".$addsourcevalue;
		}
		if ($addsource) $newrec = preg_replace("/2 SOUR @XXX@\r\n/", $addsourcevalue, $newrec); 
		
		// NOTE: Save the new indi record and get the new ID
		$xref = AppendGedrec($newrec, "INDI", $change_id, $change_type);
		if ($xref) print "<br /><br />".$gm_lang["update_successful"];
		else exit;
		$success = true;
		if ($famid=="new") {
			$famrec = "0 @new@ FAM\r\n";
			if ($famtag=="HUSB") {
				$famrec .= "1 HUSB @$xref@\r\n";
				$famrec .= "1 CHIL @$pid@\r\n";
			}
			else {
				$famrec .= "1 WIFE @$xref@\r\n";
				$famrec .= "1 CHIL @$pid@\r\n";
			}
			$famid = AppendGedrec($famrec, "FAM", $change_id, $change_type);
		}
		else if (!empty($famid)) {
			$famrec = "";
			$famrec = FindGedcomRecord($famid);
			if (GetChangeData(true, $famid, true)) {
				$rec = GetChangeData(false, $famid, true, "gedlines");
				$famrec = $rec[$GEDCOM][$famid];
			}
			if (!empty($famrec)) {
				$newrec = "1 $famtag @$xref@\r\n";
				ReplaceGedrec($famid, "", $newrec, $famtag, $change_id, $change_type);
			}
		}
		if ((!empty($famid))&&($famid != "new")) {
				$newrec = "1 FAMS @$famid@\r\n";
				ReplaceGedrec($xref, "", $newrec, "FAMS", $change_id, $change_type);
		}
		if (!empty($pid)) {
			if ($gedrec) {
				$ct = preg_match("/1 FAMC @$famid@/", $gedrec);
				if ($ct==0) {
					$newrec = "1 FAMC @$famid@\r\n";
					ReplaceGedrec($pid, "", $newrec, "FAMC", $change_id, $change_type);
				}
			}
		}
		// Don't forget to add the marriage
		if (!empty($famid) && (!empty($MARR_DATE) || !empty($MARR_PLAC))) {
			$newrec = "1 MARR\r\n";
			if (!empty($MARR_DATE)) $newrec .= "2 DATE ".$MARR_DATE."\r\n";
			if (!empty($MARR_PLAC)) $newrec .= "2 PLAC ".$MARR_PLAC."\r\n";
			if ($addsource) $newrec .= $addsourcevalue;
			ReplaceGedrec($famid, "", $newrec, "MARR", $change_id, $change_type);
		}
		else if (!empty($famid) && !empty($MARR)) {
			$newrec = "1 MARR Y\r\n";
			if ($addsource) $newrec .= $addsourcevalue;
			ReplaceGedrec($famid, "", $newrec, "MARR", $change_id, $change_type);
		}
		break;
		
	// NOTE: add new source
	case "addnewsource":
		?>
		<b><?php print $gm_lang["create_source"];
		$tabkey = 1;
		 ?></b>
		<form method="post" action="edit_interface.php" onSubmit="return check_ansform(this);" style="display:inline;">
		<input type="hidden" name="action" value="addsourceaction" />
		<input type="hidden" name="pid" value="newsour" />
		<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
		<table class="facts_table"><?php
		
		AddTagSeparator("create_source");
		// 1 TITL
		$element_id = AddSimpleTag("1 TITL");
		?>
		<script type="text/javascript">
		<!--
			function check_ansform(frm) {
				if (document.getElementById("<?php print $element_id?>").value=="") {
					alert('<?php print $gm_lang["must_provide"].GM_FACT_TITL; ?>');
					document.getElementById("<?php print $element_id?>").focus();
					return false;
				}
				return true;
			}
		//-->
		</script>
		<?php
		// 1 ABBR
		AddSimpleTag("1 ABBR");
		// 1 AUTH
		AddSimpleTag("1 AUTH");
		// 1 PUBL
		AddSimpleTag("1 PUBL");
		// 1 TEXT
		AddSimpleTag("1 TEXT");
		// 1 REPO
		AddSimpleTag("1 REPO @");
		print "</table>";
		
		// 1 OBJE
		PrintAddLayer("OBJE", 1);
		// 1 NOTE
		PrintAddLayer("NOTE", 1);
		PrintAddLayer("GNOTE", 1);
		if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<br /><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."<br />";
		print "<br /><input class=\"center\" type=\"submit\" value=\"".$gm_lang["create_source"]."\" /><br />";
		print "</form>";
		print "\n<script type=\"text/javascript\">\n<!--\ndocument.getElementById(\"".$element_id."\").focus();\n//-->\n</script>";
		break;
		
	// NOTE: create a source record from the incoming variables done
	case "addsourceaction":
		$change_id = GetNewXref("CHANGE");
		
		$newged = "0 @XREF@ SOUR\r\n";
		$newged = HandleUpdates($newged);
		$xref = AppendGedrec($newged, "SOUR", $change_id, $change_type);
		if ($xref) {
			print "<br /><br />\n".$gm_lang["new_source_created"]."<br /><br />";
			if ($EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>";
			else print "<a href=\"javascript:// SOUR $xref\" onclick=\"openerpasteid('$xref'); return false;\">".$gm_lang["paste_id_into_field"]." <b>$xref</b></a>\n";
		}
		break;
		
	// NOTE: add new repository done
	case "addnewrepository":
		?>
		<script type="text/javascript">
		<!--
			function check_form(frm) {
				if (frm.NAME.value=="") {
					alert('<?php print $gm_lang["must_provide"]." ".GM_FACT_NAME; ?>');
					frm.NAME.focus();
					return false;
				}
				return true;
			}
		//-->
		</script>
		<b><?php print $gm_lang["create_repository"];
		$tabkey = 1;
		?></b>
		<form method="post" action="edit_interface.php" onSubmit="return check_form(this);" style="display:inline;">
			<input type="hidden" name="action" value="addrepoaction" />
			<input type="hidden" name="pid" value="newrepo" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="facts_table">
				<?php AddTagSeparator("create_repository"); ?>
				<tr><td class="shade2"><?php print GM_FACT_NAME; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="NAME" id="NAME" value="" size="40" maxlength="255" /> <?php LinkFunctions::PrintSpecialCharLink("NAME"); ?></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print GM_FACT_ADDR; ?></td>
				<td class="shade1"><textarea tabindex="<?php print $tabkey; ?>" name="ADDR" id="ADDR" rows="5" cols="60"></textarea><?php LinkFunctions::PrintSpecialCharLink("ADDR"); ?> </td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print GM_FACT_PHON; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="PHON" id="PHON" value="" size="40" maxlength="255" /> </td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print GM_FACT_FAX; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="FAX" id="FAX" value="" size="40" /></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print GM_FACT_EMAIL; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="EMAIL" id="EMAIL" value="" size="40" maxlength="255" onchange="sndReq('errem', 'checkemail', 'email', this.value);" />&nbsp;&nbsp;<span id="errem"></span></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print GM_FACT_WWW; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="WWW" id="WWW" value="" size="40" maxlength="255" /> </td></tr>
				<?php if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) {?>
					<tr><td class="shade1" colspan="2"><input name="aa_attempt" type="checkbox" value="1" /><?php print $gm_lang["attempt_auto_acc"]?></td></tr>
				<?php } ?>
			<tr><td class="topbottombar" colspan="2">
			<input type="submit" value="<?php print $gm_lang["create_repository"]; ?>" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: create a repository record from the incoming variables done
	case "addrepoaction":
		$change_id = GetNewXref("CHANGE");
		
		$newgedrec = "0 @XREF@ REPO\r\n";
		if (!empty($NAME)) $newgedrec .= "1 NAME $NAME\r\n";
		if (!empty($ADDR)) {
			$addrlines = preg_split("/\r?\n/", $ADDR);
			for($k=0; $k<count($addrlines); $k++) {
				if ($k == 0) $addr = "1 ADDR ".$addrlines[$k]."\r\n";
				else $addr .= "2 CONT ".$addrlines[$k]."\r\n";
			}
			$newgedrec .= $addr;
		}
		if (!empty($PHON)) $newgedrec .= "1 PHON $PHON\r\n";
		if (!empty($FAX)) $newgedrec .= "1 FAX $FAX\r\n";
		if (!empty($EMAIL)) $newgedrec .= "1 EMAIL $EMAIL\r\n";
		if (!empty($WWW)) $newgedrec .= "1 WWW $WWW\r\n";
		$newlines = preg_split("/\r?\n/", $newgedrec);
		$newged = $newlines[0]."\r\n";
		for($k=1; $k<count($newlines); $k++) {
			if ((preg_match("/\d (.....|....|...) .*/", $newlines[$k])==0) and (strlen($newlines[$k])!=0)) $newlines[$k] = "2 CONT ".$newlines[$k];
			if (strlen($newlines[$k])>255) {
				while(strlen($newlines[$k])>255) {
					$newged .= substr($newlines[$k], 0, 255)."\r\n";
					$newlines[$k] = substr($newlines[$k], 255);
					$newlines[$k] = "2 CONC ".$newlines[$k];
				}
				$newged .= trim($newlines[$k])."\r\n";
			}
			else {
				$newged .= trim($newlines[$k])."\r\n";
			}
		}
		$xref = AppendGedrec($newged, "REPO", $change_id, $change_type);
		if ($xref) {
			print "<br /><br />\n".$gm_lang["new_repo_created"]."<br /><br />";
			if ($EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>";
			else print "<a href=\"javascript:// REPO $xref\" onclick=\"openerpasteid('$xref'); return false;\">".$gm_lang["paste_rid_into_field"]." <b>$xref</b></a>\n";
		}
		break;
		
	// NOTE: add new general note
	case "addnewgnote":
		?>
		<b><?php print $gm_lang["create_general_note"];
		$tabkey = 1;
		 ?></b>
		<form method="post" action="edit_interface.php" "style="display:inline;">
		<input type="hidden" name="action" value="addgnoteaction" />
		<input type="hidden" name="pid" value="newgnote" />
		<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
		<table class="facts_table"><?php
		
//		AddTagSeparator("create_gnote");

		$element_id = AddSimpleTag("0 NOTE");
		AddSimpleTag("1 RESN");
		print "</table>";
		print PrintAddLayer("SOUR", 1);
		if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<br /><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."<br />";
		print "<br /><input class=\"center\" type=\"submit\" value=\"".$gm_lang["create_general_note"]."\" /><br />";
		print "</form>";
		print "\n<script type=\"text/javascript\">\n<!--\ndocument.getElementById(\"".$element_id."\").focus();\n//-->\n</script>";
		break;
		
	// NOTE: create a note record from the incoming variables done
	case "addgnoteaction":

		if (empty($NOTE)) {
			print "<span class=\"error\">".$gm_lang["no_empty_notes"]."</span>";
		}
		else {
			$change_id = GetNewXref("CHANGE");
			$newged = "0 @XREF@ NOTE";
			$newged = MakeCont($newged, $NOTE);
			$newged = HandleUpdates($newged);
			$xref = AppendGedrec($newged, "NOTE", $change_id, $change_type);
			if ($xref) {
				print "<br /><br />\n".$gm_lang["new_gnote_created"]."<br /><br />";
				if ($EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>";
				else print "<a href=\"javascript:// NOTE $xref\" onclick=\"openerpasteid('$xref'); return false;\">".$gm_lang["paste_noteid_into_field"]." <b>$xref</b></a>\n";
			}
		}
		break;
	
	// NOTE: Edit
	// NOTE: edit done
	case "edit":

		init_calendar_popup();

		print "<form name=\"editform\" method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\" style=\"display:inline;\">";
		print "<input type=\"hidden\" name=\"action\" value=\"update\" />";
		print "<input type=\"hidden\" name=\"fact\" value=\"$fact\" />";
		print "<input type=\"hidden\" name=\"count\" value=\"$count\" />";
		print "<input type=\"hidden\" name=\"change_type\" value=\"$change_type\" />";
		print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />";
		print "<table class=\"facts_table\">";
		$orgfact = $fact;
//		print $gedrec;
//		if ($fact == "OBJE" && $change_type == "edit_media_link") {
//			$oldrec = GetSubRecord(1, "1 $fact", $gedrec, $count);
//			print $oldrec;
			
//			AddSimpleTag(
			// ShowMediaForm($pid, 'updatemedia', 'edit_media');
//		}
//		else {
			// Get the record type
			$ct = preg_match("/0\s@\w+@\s(\w+)/", $gedrec, $match);
			if ($ct>0) $rectype = $match[1];
			else $rectype = "";
			$oldrec = SortFactDetails($oldrec);
			$gedlines = split("\r\n", trim($oldrec));	// -- find the number of lines in the record
			$fields = preg_split("/\s/", $gedlines[0]);
			// glevel is always 1
			$glevel = $fields[0];
			$level = $glevel;
//			$glevel = 1;
//			$level = 1;
			// type is the same as fact
			// $type = trim($fields[1]);
			// $level1type = $type;
			
			// Determine if it's a link
			if (count($fields)>2) {
				$ct = preg_match("/@.*@/",$fields[2]);
				if ($ct > 0) $levellink = true;
				else $levellink = false;
			}
			else $levellink = false;
			
			$factlink = $levellink;
			$tags=array();

			// we don't use linenum
			// linenum is always 1
			// $i = $linenum;
			
			// if ($change_type == "edit_gender") AddSimpleTag(GetSubRecord(1, "1 SEX", $gedrec));
			$i = 0;
			$insour = false;
			$inobj = false;
			$addfactsdone = false;
			if (!in_array($fact, $separatorfacts)) AddTagSeparator($fact);
			// Loop on existing tags :
			do {
				$text = "";
				// For level 0 notes, start at 3
				if ($change_type == "edit_general_note") $start = 3;
				else $start = 2;
				// NOTE: This retrieves the data for each fact
				for($j=$start; $j<count($fields); $j++) {
					if ($j>$start) $text .= " ";
					$text .= $fields[$j];
				}
				$iscont = false;
				while(($i+1<count($gedlines))&&(preg_match("/".($level+1)." (CON[CT])\s?(.*)/", $gedlines[$i+1], $cmatch)>0)) {
					$iscont=true;
					if ($cmatch[1] == "CONT") $text.="\r\n";
					else if ($WORD_WRAPPED_NOTES) $text .= " ";
					$text .= $cmatch[2];
					$i++;
				}
//				$text = trim($text);
				$text = rtrim($text);
				$tags[]=$fact;
				if (($fact == "SOUR" || $fact == "OBJE" || $fact == "RESN" || $fact == "ASSO") && !$insour && !$inobj && !$addfactsdone) {
					AddMissingTags($tags);
					$addfactsdone = true;
				}
				// Finished adding missing tags
				// Print the next fact
				AddSimpleTag($level." ".$fact." ".$text);
//				print "adding tag ".$level." ".$fact." ".nl2br($text)."<br />";
				if ($fact=="DATE" && $orgfact != "SOUR" && !strpos(@$gedlines[$i+1], " TIME")) {
					AddSimpleTag(($level+1)." TIME");
					$tags[] = "TIME";
				}
				if ($fact=="MARR" && !strpos(@$gedlines[$i+1], " TYPE")) {
					AddSimpleTag(($level+1)." TYPE");
					$tags[] = "TYPE";
				}
				
				if ($fact=="ASSO") {
					if (!strpos(@$gedlines[$i+1], " RELA")) {
						AddSimpleTag(($level+1)." RELA");
						$tags[] = "RELA";
					}
					if (!strpos(@$gedlines[$i+2], " NOTE")) {
						AddSimpleTag(($level+1)." NOTE");
						$tags[] = "NOTE";
					}
				}
				
				if ($tags[0]=="SOUR" || $fact == "SOUR" || $insour) {
					if (!$insour) $insourlevel = $level;
					// Dont do unless it's a linked source
					if (!empty($text)) $insour = true;
					$insourfacts[] = $fact;
					// In case of DATA, we must look ahead to see what other tags will follow. 
					if ($fact == "DATA") {
						$datalevel = $insourlevel + 2; // 2 SOUR, 3 DATA, 4 TEXT
						$line = $i + 1;
						$arrdata = array();
						while ($line < count($gedlines)) {
							if (substr($gedlines[$line], 0, 1) == $level) break;
							$dfields = preg_split("/\s/", trim($gedlines[$line]));
							$arrdata[$dfields[1]] = true;
							$line++;
						}
						// we have the DATA subtags, now add the missing ones
						if (!array_key_exists("DATE", $arrdata)) AddSimpleTag($datalevel." DATE");
						if (!array_key_exists("TEXT", $arrdata)) AddSimpleTag($datalevel." TEXT");
					}
					// Wait until we are eof or end of source, then we can insert the remaining level+n tags
					if (!isset($gedlines[$i+1]) || substr($gedlines[$i+1],0,1) <= $insourlevel) {
						$insour = false;			
						// 1 SOUR OOPS can be on various levels, so we calculate on which level to add tags!
						// 2 PAGE
						// 2 DATA
						// 3 DATE
						// 3 TEXT
						$l1 = $insourlevel+1;
						$l2 = $insourlevel+2;
						if (!in_array("PAGE", $insourfacts)) AddSimpleTag($l1." PAGE");
						if (!in_array("DATA", $insourfacts)) {
							AddSimpleTag($l1." DATA");
							AddSimpleTag($l2." DATE");
							AddSimpleTag($l2." TEXT");
						}
//						else {
//							if (!in_array("DATE", $insourfacts)) AddSimpleTag($l2." DATE");
//							if (!in_array("TEXT", $insourfacts)) AddSimpleTag($l2." TEXT");
//						}
						$insourfacts = array();
					}
				}
				if ($tags[0]=="OBJE" || $inobj) {
					if (!$inobj) $inobjlevel = $level;
					// Dont do unless it's a linked object
					if (!empty($text)) $inobj = true;
					$inobjfacts[] = $fact;
					// Wait until we are eof or end of object
					if (!isset($gedlines[$i+1]) || substr($gedlines[$i+1],0,1) <= $inobjlevel) {
						$inobj = false;			
//						ShowMediaForm($pid);
						// 1 OBJE
						if (!$factlink) {
							// 2 FORM
							if (!in_array("FORM", $tags)) AddSimpleTag(($inobjlevel+1)." FORM");
							// 2 FILE
							if (!in_array("FILE", $tags)) AddSimpleTag(($inobjlevel+1)." FILE");
							// 2 TITL
							if (!in_array("TITL", $tags)) AddSimpleTag(($inobjlevel+1)." TITL");
						}
						// 2 _PRIM
						if (!in_array("_PRIM", $tags)) AddSimpleTag(($inobjlevel+1)." _PRIM");
						// 2 _THUM
						if (!in_array("_THUM", $tags)) AddSimpleTag(($inobjlevel+1)." _THUM");
					}
				}
				$i++;
				if (isset($gedlines[$i])) {
					$fields = preg_split("/\s/", trim($gedlines[$i]));
					$level = $fields[0];
					if (isset($fields[1])) $fact = trim($fields[1]);
					if (count($fields)>2) {
						$ct = preg_match("/@.*@/",$fields[2]);
						$levellink = $ct > 0;
					}
					else $levellink = false;
				}
				if ($i == count($gedlines) && !$addfactsdone) AddMissingTags($tags);
			} while ($i<count($gedlines));
			// 2 RESN
			if (!in_array("RESN", $tags)&& $rectype != "OBJE") AddSimpleTag("2 RESN");
			print "</table>";
			if ($orgfact != "SEX" && $orgfact != "RESN" && $rectype != "OBJE" && $rectype != "NOTE") {
				if ($orgfact!="ASSO"  && $orgfact!="REPO" && $orgfact!="OBJE" && $orgfact!="SOUR" && $orgfact!="NOTE") PrintAddLayer("ASSO");
				if ($orgfact!="SOUR"  && $orgfact!="REPO" && $orgfact!="OBJE" && !($orgfact == "NOTE" && $levellink)) PrintAddLayer("SOUR");
				if ($orgfact!="OBJE"  && $orgfact!="REPO" && $orgfact!="NOTE") PrintAddLayer("OBJE");
				if ($orgfact!="NOTE") {
					PrintAddLayer("NOTE");
					PrintAddLayer("GNOTE");
				}
			}
			if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<br /><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."<br />\n";
			print "<br /><input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
			print "</form>\n";
//		}
		break;
		
	// NOTE: editraw done
	case "editraw":
		if (!$factedit) {
			print "<br />".$gm_lang["privacy_prevented_editing"];
			if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
			if (!empty($famid)) print "<br />".$gm_lang["privacy_not_granted"]." famid $famid.";
			print_simple_footer();
			exit;
		}
		else {
			print "<br />";
			$gedrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $gedrec);
			print_help_link("edit_edit_raw_help", "qm");
			print "<b>".$gm_lang["edit_raw"]."</b>";
			print "<form method=\"post\" action=\"edit_interface.php\" style=\"display:inline;\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"updateraw\" />\n";
			print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
			print "<input type=\"hidden\" name=\"oldrec\" value=\"".urlencode($oldrec)."\" />\n";
			print "<input type=\"hidden\" name=\"change_type\" value=\"$change_type\" />\n";
			LinkFunctions::PrintSpecialCharLink("newgedrec");
			print "<textarea name=\"newgedrec\" id=\"newgedrec\" rows=\"20\" cols=\"82\" dir=\"ltr\">".$gedrec."</textarea>\n<br />";
			if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<br /><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."<br />\n";
			print "<input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
			print "</form>\n";
		}
		break;
	//-- edit a fact record in a form
	// NOTE: updateraw done
	case "updateraw":
		$oldrec = urldecode($oldrec);
		$change_id = GetNewXref("CHANGE");
		$newrec = trim($newgedrec);
		$rectype = GetRecType($newrec);
		if (!$rectype) $rectype = "";
		if (trim($oldrec) != trim($newrec)) {
			$success = (!empty($newrec)&&(ReplaceGedrec($pid, $oldrec, $newrec, $rectype, $change_id, $change_type)));
			if ($success) print "<br /><br />".$gm_lang["update_successful"];
		}
		break;
	
	// NOTE editname done
	case "editname":
		if (GetChangeData(true, $pid, true)) {
			$rec = GetChangeData(false, $pid, true, "gedlines");
			$namerecnew = GetSubRecord(1, "1 NAME", $rec[$GEDCOM][$pid], $count);
		}
		else $namerecnew = GetSubRecord(1, "1 NAME", $gedrec, $count);
		PrintIndiForm("update", "", "", $namerecnew);
		break;
		
	
	// NOTE: Copy
	// NOTE: copy done
	case "copy":
		$factrec = GetSubRecord(1, "1 $fact", $gedrec, $count);
		if (!isset($_SESSION["clipboard"])) $_SESSION["clipboard"] = array();
		$ft = preg_match("/1 (_?[A-Z]{3,5})(.*)/", $factrec, $match);
		if ($ft>0) {
			$fact = trim($match[1]);
			if ($fact=="EVEN" || $fact=="FACT") {
				$ct = preg_match("/2 TYPE (.*)/", $factrec, $match);
				if ($ct>0) $fact = trim($match[1]);
			}
			if (count($_SESSION["clipboard"])>4) array_shift($_SESSION["clipboard"]);
			$_SESSION["clipboard"][] = array("type"=>$type, "factrec"=>$factrec, "fact"=>$fact);
			print "<b>".$gm_lang["record_copied"]."</b>\n";
		}
		break;
	
	
	// NOTE: Link
	// NOTE: linkspouse done
	case "linkspouse":
		init_calendar_popup();
		print "<form method=\"post\" name=\"linkspouseform\" action=\"edit_interface.php\" style=\"display:inline;\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linkspouseaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"famid\" value=\"new\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<table class=\"facts_table\">";
		AddTagSeparator($famtag);
		print "<tr><td class=\"shade2\">";
		if ($famtag=="WIFE") print $gm_lang["wife"];
		else print $gm_lang["husband"];
		print "</td>";
		print "<td class=\"shade1\"><input id=\"spouseid\" type=\"text\" name=\"spid\" size=\"8\"  onblur=\"sndReq('spouselink', 'getpersonname', 'pid', this.value, '', '');\"/> ";
		LinkFunctions::PrintFindIndiLink("spouseid", "");
		print "&nbsp;<span id=\"spouselink\"></span>";
		print "\n</td></tr>";
		AddTagSeparator("MARR");
		AddSimpleTag("0 MARR");
		AddSimpleTag("0 TYPE", "MARR");
		AddSimpleTag("0 DATE", "MARR");
		AddSimpleTag("0 PLAC", "MARR");
		if ($Users->UserCanAccept($gm_username) && !$Users->userAutoAccept($gm_username)) print "<tr><td class=\"shade1\" colspan=\"2\"><input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".$gm_lang["attempt_auto_acc"]."</td></tr>";
		print "<tr><td class=\"topbottombar\" colspan=\"2\">";
		print "<input type=\"submit\" id=\"submit\" value=\"".$gm_lang["set_link"]."\" />\n";
		print "</td></tr>";
		print "</table>\n";
		print "</form>\n";
		print "<script><!--\n";
		print "linkspouseform.spouseid.focus();";
		print "//--></script>";
		break;
	
	// NOTE: linkspouseaction done
	case "linkspouseaction":
		// NOTE: Get a change_id
		$change_id = GetNewXref("CHANGE");
		$exist = false;
		if (!empty($spid)) {
			// NOTE: Check if the relation doesn't exist yet
			$sql = "SELECT f_id FROM ".$TBLPREFIX."families WHERE f_husb = ";
			if ($famtag == "HUSB") $sql .= "'".JoinKey($spid, $GEDCOMID)."' AND f_wife = '".JoinKey($pid, $GEDCOMID)."'";
			else if ($famtag == "WIFE") $sql .= "'".JoinKey($pid, $GEDCOMID)."' AND f_wife = '".JoinKey($spid, $GEDCOMID)."'";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc($res->result)) {
				if (!empty($row["f_id"])) {
					$exist = true;
					print $gm_lang["family_exists"];
					print "<br />";
					print $gm_lang["family"].": ".$row["f_id"];
				}
			}
			if (!$exist) {
				// NOTE: Check if there are changes present, if not get the record otherwise the changed record
				if (!GetChangeData(true, $spid, true)) $gedrec = FindGedcomRecord($spid);
				else {
					$rec = GetChangeData(false, $spid, true, "gedlines");
					$gedrec = $rec[$GEDCOM][$spid];
				}
				if (!empty($gedrec)) {
					// NOTE: Create a new family record
					if ($famid=="new") {
						// NOTE: Create the new family record
						$famrec = "0 @new@ FAM\r\n";
						$SEX = GetGedcomValue("SEX", 1, $gedrec, '', false);
						if ($SEX=="M") $famtag = "HUSB";
						if ($SEX=="F") $famtag = "WIFE";
						if ($famtag=="HUSB") {
							$famrec .= "1 HUSB @$spid@\r\n";
							$famrec .= "1 WIFE @$pid@\r\n";
						}
						else {
							$famrec .= "1 WIFE @$spid@\r\n";
							$famrec .= "1 HUSB @$pid@\r\n";
						}
						if ((!empty($MARR_DATE))||(!empty($MARR_PLAC))) {
							$famrec .= "1 MARR\r\n";
							if (!empty($MARR_DATE)) {
								$MARR_DATE = CheckInputDate($MARR_DATE);
								$famrec .= "2 DATE $MARR_DATE\r\n";
							}
							if (!empty($MARR_PLAC)) $famrec .= "2 PLAC $MARR_PLAC\r\n";
						}
						else if (!empty($MARR)) {
							$famrec .= "1 MARR Y\r\n";
							ReplaceGedrec($famid, "", $famrec, "MARR", $change_id, $change_type);
						}
						$famid = AppendGedrec($famrec, "FAM", $change_id, $change_type);
					}
					if ((!empty($famid)) && ($famid != "new")) {
						// NOTE: Notify the session of change
						// NOTE: Add the new FAM ID to the spouse record
						$newrec = "1 FAMS @$famid@\r\n";
						ReplaceGedrec($spid, "", $newrec, "FAMS", $change_id, $change_type);
					}
					if (!empty($pid)) {
						// NOTE: Notify the session of change
						// NOTE: Add the new FAM ID to the active person record
						$newrec = "1 FAMS @$famid@\r\n";
						ReplaceGedrec($pid, "", $newrec, "FAMS", $change_id, $change_type);
					}
					if ($famtag == "HUSB") print $gm_lang["husband_added"];
					else if ($famtag == "WIFE") print $gm_lang["wife_added"];
				}
				else print "<br /><span class=\"error\">".$gm_lang["person_not_found"].": ".$spid."</span>";
			}
		}
		break;
	
	// NOTE updatemedia done ====> obsolete
	/* 
	case "updatemedia":
		$change_id = GetNewXref("CHANGE");
		
		// NOTE: Check for uploaded files
		if (count($_FILES)>0) {
			$MediaFS->UploadFiles($_FILES, $folder, true);
		}
		
		// NOTE: Build the new record
		$newrec = "0 @$pid@ OBJE\r\n";
		$newrec = HandleUpdates($newrec);
		
		// NOTE: Get the old record and remove the CHAN info
		$crec = GetSubRecord(1, "1 CHAN", $oldgedrec);
		if (!empty($crec)) {
			$oldgedrec = str_replace($crec, "", $oldgedrec);
		}
		// Compare old and new record. If changed, update.
		if (trim($newrec) != trim($oldgedrec)) {
		
			// NOTE: Store the change in the database
			$success = (ReplaceGedrec($pid, $gedrec, $newrec, $fact, $change_id, $change_type));
			if ($success) print "<br /><br />".$gm_lang["update_successful"];
		}
		break;
		*/

	case "update_submitter":
	$change_id = GetNewXref("CHANGE");
	if ($pid !== "NEW") {
		$oldrecord = FindGedcomRecord($pid, get_gedcom_from_id($gedfile));
		if (GetChangeData(true, $pid, true, "", "")) {
			$rec = GetChangeData(false, $pid, true, "gedlines", "");
			if (isset($rec[get_gedcom_from_id($gedfile)][$pid])) $oldrecord = $rec[get_gedcom_from_id($gedfile)][$pid];
		}
		$newrec = "0 @".$pid."@ SUBM\r\n";
		$newrec = HandleUpdates($newrec);
		$chanrec = GetSubrecord(1, "1 CHAN", $oldrecord);
		$orec = preg_replace("/$chanrec/", "", $oldrecord);
		if (trim($newrec) != trim($orec)) {
			$success = (ReplaceGedrec($pid, $oldrecord, $newrec, $fact, $change_id, $change_type, $gedfile));
			if ($success) print "<br /><br />".$gm_lang["update_successful"];
		}
	}
	else {
		$newrec = "0 @new@ SUBM\r\n";
		$newrec = HandleUpdates($newrec);
		$subid = AppendGedrec($newrec, "SUBM", $change_id, $change_type, $gedfile);
		$success = (ReplaceGedrec("HEAD", "", "1 SUBM @".$subid."@", "SUBM", $change_id, $change_type, $gedfile));
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		
	}
	break;	
		
			
	// NOTE: reconstruct the gedcom from the incoming fields and store it in the file done
	case "update":
		$change_id = GetNewXref("CHANGE");
		if ($change_type == "add_name" || $change_type == "edit_name") $fact = "NAME";
		// add or remove Y
//		if ($text[0]=="Y" or $text[0]=="y") $text[0]="";
		if (in_array($tag[0], $emptyfacts) && array_unique($text)==array("") && !$islink[0]) $text[0]="Y";
		
		if (!empty($NAME)) $newrec = "1 NAME ".trim($NAME)."\r\n";
		if (!empty($NPFX)) $newrec .= "2 NPFX $NPFX\r\n";
		if (!empty($GIVN)) $newrec .= "2 GIVN $GIVN\r\n";
		if (!empty($NICK)) $newrec .= "2 NICK $NICK\r\n";
		if (!empty($SPFX)) $newrec .= "2 SPFX $SPFX\r\n";
		if (!empty($SURN)) $newrec .= "2 SURN $SURN\r\n";
		if (!empty($NSFX)) $newrec .= "2 NSFX $NSFX\r\n";
		if (!empty($_MARNM)) $newrec .= "2 _MARNM $_MARNM\r\n";
		if (!empty($_HEB)) $newrec .= "2 _HEB $_HEB\r\n";
		if (!empty($ROMN)) $newrec .= "2 ROMN $ROMN\r\n";
		// NOTE: Build the edited record

		if (isset($fact)) {
			// This is to remove empty 1 RESN tags
			if ($fact == "RESN" && empty($text[0])) $newrec = "";
			// This is to remove empty TEXT tags
			else if ($fact == "TEXT" && empty($text[0])) $newrec = "";
			// This is to remove empty NOTE tags
			else if ($fact == "NOTE") {
				if ($change_type == "edit_general_note") {
					$newrec = "0 @".$pid."@ NOTE";
					$newrec = MakeCont($newrec, $NOTE);
				}
				else $newrec = "";
			}
//			else if (!isset($newrec) && $fact != "SUBM") $newrec = "1 ".$fact."\r\n";
			else if (!isset($newrec) && $fact != "SUBM") $newrec = "";
			else if ($fact == "SUBM") $newrec = "0 @SUB1@ SUBM\r\n";
		}
		$newrec = HandleUpdates($newrec);
		if (!isset($count)) $count = 1;
		if (!isset($change_type)) $change_type = "unknown";

		// NOTE: Get old record only when we are editing a record
		$addfacts = array("newfact", "add_note", "add_source", "add_media");
		if (in_array($change_type, $addfacts)) $oldrec = "";
		else if ((!isset($tag) || $change_type == "edit_name") && !empty($NAME)) $oldrec = GetSubRecord(1, "1 NAME", $gedrec, $count);
		else if ($change_type != "add_name" && $change_type != "add_media_link" && $change_type != "edit_general_note") $oldrec = GetSubRecord(1, "1 ".$fact, $gedrec, $count);
		else if ($change_type == "edit_general_note") {
			$recs = split("\r\n", trim($gedrec));
			$oldrecpart = $recs[0]."\r\n";
			for ($i=1;$i<count($recs);$i++) {
				$ct = preg_match("/\d\s(\w+)/", $recs[$i], $match);
				if (in_array($match[1], array("CONC", "CONT", "NOTE"))) $oldrecpart .= $recs[$i]."\r\n";
			}
			$oldrecpart = trim($oldrecpart);
			$newrec = str_replace($oldrecpart, $newrec, $gedrec);
			$oldrec = $gedrec;
		}
		else $oldrec = "";
		
		if (!isset($gedfile)) $gedfile = $GEDCOMID;
		
		//-- check for photo update
		if (count($_FILES)>0) {
			$result = $MediaFS->UploadFiles($_FILES, $folder, true);
			if ($result["errno"] != 0) {
				print "<span class=\"error\">".$gm_lang["upload_error"]."<br />".$result["error"]."</span><br />";
			}
			else {
				$newmfile = $result["filename"];
				if (!empty($newmfile)) {
					$m = RelativePathFile($MEDIA_DIRECTORY);
					if (!empty($m)) $newmfile = preg_replace("~$m~", "", $newmfile);
					if (!empty($oldrec)) $old = GetGedcomValue("FILE", 1, $oldrec);
					if (isset($old) && !empty($old)) $newrec = preg_replace("~".addslashes($old)."~", $newmfile, $newrec);
				}
			}
		}
		if (trim(SortFactDetails($oldrec)) != trim(SortFactDetails($newrec))) $success = ReplaceGedrec($pid, $oldrec, $newrec, $fact, $change_id, $change_type, $gedfile);
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
	
}

// if there were link errors, don't auto-accept and don't auto-close.
if (!isset($link_error)) $link_error = false;

if (isset($change_id) && $can_auto_accept && !$link_error && (($Users->UserCanAccept($gm_username) && $aa_attempt) || $Users->userAutoAccept())) {
	AcceptChange($change_id, $GEDCOMID);
}

// autoclose window when update successful
if ($success && !$link_error && $EDIT_AUTOCLOSE) {
	session_write_close();
	print "\n<script type=\"text/javascript\">\n<!--\nedit_close();\n//-->\n</script>";
}

print "<div class=\"center\"><a href=\"javascript:// ".$gm_lang["close_window"]."\" onclick=\"edit_close();\">".$gm_lang["close_window"]."</a></div><br />\n";
print_simple_footer();
?>