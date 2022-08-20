<?php
/**
 * PopUp Window to provide editing features.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
 * @version $Id: edit_interface.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

// Force to show changes
$show_changes = true;

/** * Inclusion of the country names
*/
require(SystemConfig::$GM_BASE_DIRECTORY."languages/countries.en.php");
if (file_exists(SystemConfig::$GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$LANGUAGE].".php")) require(SystemConfig::$GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$LANGUAGE].".php");
asort($countries);

if ($_SESSION["cookie_login"]) {
	if (LOGIN_URL == "") header("Location: login.php?type=simple&gedid=".GedcomConfig::$GEDCOMID."&url=edit_interface.php".urlencode("?".$QUERY_STRING));
	else header("Location: ".LOGIN_URL."?type=simple&gedid=".GedcomConfig::$GEDCOMID."&url=edit_interface.php".urlencode("?".$QUERY_STRING));
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

$assorela = EditFunctions::GetAssoRela();

PrintSimpleHeader("Edit Interface ".GM_VERSION);

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
		window.opener.location.reload();
		window.close();
	}
//-->
</script>
<?php

// Submitter records must be found first on add/edit
if ($action == "submitter") {
	SwitchGedcom($gedfile);
	$subm = EditFunctions::FindSubmitter($gedfile);
	if (is_object($subm) && !$subm->isempty) {
		$pid = $subm->xref;
		$pid_type = $subm->datatype;
	}
	else $pid_type="SUBM";
}
if ($action == "update_submitter") {
	SwitchGedcom($gedfile);
}

//-- check if user has access to the gedcom record
$disp = false;
$success = false;
$factdisp = true;
$factedit = true;
$object = "";
if (!empty($pid)) {
	// NOTE: Remove any illegal characters from the ID
	$pid = CleanInput($pid);
	if ((strtolower($pid) != "newsour") && (strtolower($pid) != "newrepo") && (strtolower($pid) != "newmedia") && (strtolower($pid) != "newgnote")&& (strtolower($pid) != "newsubmitter")) {
		// NOTE: Do not take the changed record here since non-approved changes should not yet appear
		$object =& ConstructObject($pid, $pid_type, GedcomConfig::$GEDCOMID);
		if ($object->isdeleted) $disp = false; // We cannot edit a deleted record!
		if ($object->ischanged) $gedrec = $object->changedgedrec;
		else $gedrec = $object->gedrec;
		if (!$object->isempty) {
			$disp = $object->disp;
			//-- if the record is for an INDI then check for display privileges for that indi
			if ($pid_type == "INDI" || $pid_type == "FAM") {
				//-- if disp is true, also check for resn access
				if ($object->hiddenfacts) $factdisp = false;
				if (!$object->canedit) $factedit = false;
				if ($factdisp || $factedit)	{
					foreach ($object->facts as $key => $factobj) {
						if ($factobj->fact != "CHAN") {
							if (!$factobj->disp) $factdisp = false;
							if (!$factobj->canedit) $factedit = false;
						}
					}
				}
			}
			//-- for FAM check for display privileges on both parents
			if ($pid_type == "FAM") {
				$disp = true;
				//-- check if we can display both parents
				if (is_object($object->wife) && !$object->wife->disp) $disp = false;
				if ($disp) {
					if (is_object($object->husb) && !$object->husb->disp) $disp = false;
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
	else {
		$gedrec = "";
		$disp = true;
	}
}
else if (!empty($famid)) {
	$famid = CleanInput($famid);
	if ($famid != "new") {
		$object =& ConstructObject($famid, $pid_type, GedcomConfig::$GEDCOMID);
		if ($object->isdeleted) $disp = false; // We cannot edit a deleted record!
		if ($object->ischanged) $gedrec = $object->changedgedrec;
		else $gedrec = $object->gedrec;
		if (!$object->isempty) {
			$disp = $object->disp;
			//-- if the record is for an INDI then check for display privileges for that indi
			if ($pid_type == "INDI" || $pid_type == "FAM") {
				//-- if disp is true, also check for resn access
				if ($object->hiddenfacts) $factdisp = false;
				if (!$object->canedit) $factedit = false;
				if ($factdisp || $factedit)	{
					foreach ($object->facts as $key => $factobj) {
						if ($factobj->fact != "CHAN") {
							if (!$factobj->disp) $factdisp = false;
							if (!$factobj->canedit) $factedit = false;
						}
					}
				}
			}
			//-- for FAM check for display privileges on both parents
			if ($pid_type=="FAM") {
				//-- check if we can display both parents
				if (is_object($object->wife) && !$object->wife->disp) $disp = false;
				if ($disp) {
					if (is_object($object->husb) && !$object->husb->disp) $disp = false;
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
}
else {
	if (($action!="addchild")&&($action!="addchildaction")&&($action!="submitter")) {
		EditFunctions::PrintFailMessage("The \$pid variable was empty. Unable to perform $action");
		PrintSimpleFooter();
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
			$recs = explode("\r\n", trim($gedrec));
			$gedrec = $recs[0]."\r\n";
			for ($i=1;$i<count($recs);$i++) {
				$ct = preg_match("/\d\s(\w+)/", $recs[$i], $match);
				if (in_array($match[1], array("CONC", "CONT", "NOTE"))) $gedrec .= $recs[$i]."\r\n";
			}
			$oldrec = $gedrec;
		}
		else $oldrec = GetSubRecord(1, "1 $fact", $gedrec, $count);
	}
	else {
		// We make an exception for the admin!
		if ($object->hiddenfacts && !$gm_user->userIsAdmin()) $disp = false;
		$oldrec = $gedrec;
	}
	// Check links from the fact to hidden sources, media, etc. is not needed: done in hiddenfacts
}
if (!$gm_user->userCanEdit() || !$disp || !GedcomConfig::$ALLOW_EDIT_GEDCOM || (!$gm_user->userCanEditGedlines() && $action == "editraw")) {
	print "<div class=\"EditMessageFail\">";
	print GM_LANG_access_denied;
	//-- display messages as to why the editing access was denied
	if (!$gm_user->userCanEdit()) print "<br />".GM_LANG_user_cannot_edit;
	else if (!GedcomConfig::$ALLOW_EDIT_GEDCOM) print "<br />".GM_LANG_gedcom_editing_disabled;
	else if (!$disp) {
		EditFunctions::PrintFailMessage(GM_LANG_privacy_prevented_editing.(!empty($pid) ? "<br />".GM_LANG_privacy_not_granted." pid $pid." : "").(!empty($famid) ? "<br />".GM_LANG_privacy_not_granted." famid $famid." : ""));
	}
	print "</div>";
	print "<div class=\"CloseWindow\"><a href=\"javascript: ".GM_LANG_close_window."\" onclick=\"window.close();\">".GM_LANG_close_window."</a></div>\n";
	PrintSimpleFooter();
	exit;
}
// Page header
print "<div class=\"NavBlockHeader EditHeader\">";
if (!isset($pid_type)) {
	if (is_object($object)) $pid_type = $object->datatype;
	else $pid_type="";
}
if (!empty($pid_type) && is_object($object)) {
	print $object->name."<br />";
}

if (strstr($action,"addchild")) {
	if (empty($famid)) {
		PrintHelpLink("edit_add_unlinked_person_help", "qm");
		print GM_LANG_add_unlinked_person."\n";
	}
	else {
		PrintHelpLink("edit_add_child_help", "qm");
		print GM_LANG_add_child."\n";
	}
}
else if (strstr($action,"addspouse")) {
	PrintHelpLink("edit_add_spouse_help", "qm");
	print constant("GM_LANG_add_".strtolower($famtag))."\n";
}
else if (strstr($action,"addnewparent")) {
	PrintHelpLink("edit_add_parent_help", "qm");
	if ($famtag == "WIFE") print GM_LANG_add_mother."\n";
	else print GM_LANG_add_father."\n";
}
else {
	if (defined("GM_FACT_".$pid_type)) print constant("GM_FACT_".$pid_type)."\n";
}
print "</div>";
switch ($action) {
	// NOTE: Edit/add submitter
	// NOTE: Done for Genmod 2.0
	case "submitter":
		if (empty($pid)) {
			$pid = "newsubmitter";
			$gedrec = "";
		}
		else {
			// As the whole level 0 record is rewritten, we can only do one change at the time
			if ($object->ischanged) {
				EditFunctions::PrintFailMessage(GM_LANG_approve_first.": ".$pid);
				PrintSimpleFooter();
				exit;
			}
		}
		$change_id = EditFunctions::GetNewXref("CHANGE");
		print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"update_submitter\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"gedfile\" value=\"".$gedfile."\" />\n";
		print "<input type=\"hidden\" name=\"fact\" value=\"SUBM\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"submitter_record\" />\n";
		print "<input type=\"hidden\" name=\"pid_type\" value=\"SUBM\" />\n";
		print "<table class=\"NavBlockTable EditTable\">";
		EditFunctions::SubmitterRecord(0, $gedrec);
		EditFunctions::AddAutoAcceptLink();
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\" value=\"".GM_LANG_save."\" /></td></tr>\n";
		print "</table>";
		print "</form>\n";
		$disp = true;
		break;
		
	// NOTE: Delete
	// NOTE: deleteperson
	// NOTE: Done for Genmod 2.0
	case "deleteperson":
		if (!$factedit) {
			EditFunctions::PrintFailMessage(GM_LANG_privacy_prevented_editing.(!empty($pid) ? "<br />".GM_LANG_privacy_not_granted." pid $pid." : "").(!empty($famid) ? "<br />".GM_LANG_privacy_not_granted." famid $famid." : ""));
		}
		else {
			// TODO: Notify user if record has already been deleted
			// TODO: Add checking 
			if (!empty($gedrec)) {
				$change_id = EditFunctions::GetNewXref("CHANGE");
				
				$success = false;
				// Save the fams for later, to check on empty families
				$fams = $object->fams;
				foreach($object->famc as $key => $famc) {
					$fams[] = $famc["famid"];
				}
			
				// Delete all links to this person
				$success = EditFunctions::DeleteLinks($pid, "INDI", $change_id, $change_type, GedcomConfig::$GEDCOMID);

				// Remove resulting empty fams
				foreach($fams as $key=>$fam) {
					if ($success) $success = $success && EditFunctions::DeleteFamIfEmpty($fam, $change_id, $change_type, GedcomConfig::$GEDCOMID);
				}
				
				// Delete the person
				if ($success) $success = $success && EditFunctions::DeleteGedrec($pid, $change_id, "delete_indi", "INDI");
				
				if ($success) EditFunctions::PrintSuccessMessage(GM_LANG_gedrec_deleted);
			}
		}
		break;
		
	// NOTE: deletefamily 
	// NOTE: Done for Genmod 2.0
	case "deletefamily":
		if (!$factedit) {
			EditFunctions::PrintFailMessage(GM_LANG_privacy_prevented_editing.(!empty($pid) ? "<br />".GM_LANG_privacy_not_granted." pid $pid." : "").(!empty($famid) ? "<br />".GM_LANG_privacy_not_granted." famid $famid." : ""));
		}
		else {
			if (!empty($gedrec)) {
			$change_id = EditFunctions::GetNewXref("CHANGE");
				$success = false;
				$success = EditFunctions::DeleteLinks($famid, "FAM", $change_id, $change_type, GedcomConfig::$GEDCOMID);
				
				if ($success) {
					$success = $success && EditFunctions::DeleteGedrec($famid, $change_id, $change_type, "FAM");
				}
				if ($success) EditFunctions::PrintSuccessMessage(GM_LANG_gedrec_deleted);
			}
		}
		break;
		
	// NOTE: deletesource
	// NOTE: Done for Genmod 2.0
	case "deletesource":
		if (!empty($gedrec)) {
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$success = false;
			$success = EditFunctions::DeleteLinks($pid, "SOUR", $change_id, $change_type, GedcomConfig::$GEDCOMID);
			if ($success) {
				$success = $success && EditFunctions::DeleteGedrec($pid, $change_id, $change_type, "SOUR");
			}
			if ($success) EditFunctions::PrintSuccessMessage(GM_LANG_gedrec_deleted);
		}
		break;
		
	// NOTE: deleterepo
	// NOTE: Done for Genmod 2.0
	case "deleterepo":
		if (!empty($gedrec)) {
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$success = false;
			$success = EditFunctions::DeleteLinks($pid, "REPO", $change_id, $change_type, GedcomConfig::$GEDCOMID);
			if ($success) {
				$success = $success && EditFunctions::DeleteGedrec($pid, $change_id, $change_type, "REPO");
			}
			if ($success) EditFunctions::PrintSuccessMessage(GM_LANG_gedrec_deleted);
		}
		break;
		
	// NOTE: Done for Genmod 2.0
	case "deletegnote":
		if (!empty($gedrec)) {
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$success = false;
			$success = EditFunctions::DeleteLinks($pid, "NOTE", $change_id, $change_type, GedcomConfig::$GEDCOMID);
			if ($success) {
				$success = $success && EditFunctions::DeleteGedrec($pid, $change_id, $change_type, "NOTE");
			}
			if ($success) EditFunctions::PrintSuccessMessage(GM_LANG_gedrec_deleted);
		}
		break;
		
	// NOTE: Done for Genmod 2.0
	case "deletemedia":
		if (!$factedit) {
			EditFunctions::PrintFailMessage(GM_LANG_privacy_prevented_editing.(!empty($pid) ? "<br />".GM_LANG_privacy_not_granted." pid $pid." : ""));
		}
		else {
			if (!empty($gedrec)) {
				$change_id = EditFunctions::GetNewXref("CHANGE");
				$success = false;
				$success = EditFunctions::DeleteLinks($pid, "OBJE", $change_id, $change_type, GedcomConfig::$GEDCOMID);
				
				if ($success) {
					$success = $success && EditFunctions::DeleteGedrec($pid, $change_id, $change_type, "OBJE");
				}
				if ($success) EditFunctions::PrintSuccessMessage(GM_LANG_gedrec_deleted);
			}
		}
		break;
		
	// NOTE: delete
	// NOTE: Done for Genmod 2.0
	case "delete":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		$success = false;
		// Before using getsubrec, we must get the privatised gedrec. Otherwise the counter is not correct.
		$gedrecpriv = implode("\r\n", GetAllSubrecords($gedrec, "", false, false));
		$oldrec = GetSubRecord(1, "1 $fact", $gedrecpriv, $count);
		$success =  EditFunctions::ReplaceGedrec($pid, $oldrec, "", $fact, $change_id, $change_type, "", $pid_type); 
		if ($success) EditFunctions::PrintSuccessMessage(GM_LANG_gedrec_deleted);
		break;
	
	// NOTE: Reorder media
	// NOTE: Done for Genmod 2.0
	case "reorder_media":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php">
			<input type="hidden" name="action" value="reorder_media_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="pid_type" value="<?php print $pid_type; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="NavBlockTable EditTable">
			<tr><td class="NavBlockColumnHeader EditTableColumnHeader" colspan="2">
			<?php PrintHelpLink("reorder_media_help", "qm", "reorder_media"); ?>
			<?php print GM_LANG_reorder_media; ?>
			</td></tr>
			<?php
			$mfacts = $object->SelectFacts(array("OBJE"));
			$okfacts = array();
			foreach ($mfacts as $key => $factobj) {
				if ($factobj->linktype == "MediaItem" && $factobj->linkxref != "" && $factobj->style != "ChangeOld") {
					$okfacts[] = $factobj;
				}
			}
			$ct = count($okfacts);
			if ($ct > 0) {
				$i=1;
				foreach($okfacts as $key => $factobj) {
					print "<tr>\n<td class=\"NavBlockField\">\n";
					print "<select name=\"order[$i]\">\n";
					for($j=1; $j<=$ct; $j++) {
						print "<option value=\"".($j)."\"";
						if ($j == $i) print " selected=\"selected\"";
						print ">".($j)."</option>\n";
					}
					print "</select>\n";
					print "</td><td class=\"NavBlockLabel\">";
					$media =& MediaItem::GetInstance($factobj->linkxref, "", $factobj->gedcomid);
					print $media->name;
					print "</td>\n</tr>\n";
					$i++;
				}
			}
			?>
			<tr><td class="NavBlockFooter" colspan="2">
			<input type="submit" value="<?php print GM_LANG_save; ?>" />&nbsp;
			</td></tr>
			</table>
		</form>
		<?php
		break;

	// NOTE: Done for Genmod 2.0
	case "reorder_media_update":
		if (EditFunctions::CheckReorder($order)) {
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$success = true;
			$lines = count($order);
			$f = array();
			for ($i = 1; $i <= $lines; $i++) {
				$t = getsubrecord(1,"1 OBJE", $gedrec, $i);
				$f[$i] = $t;
			}
			foreach($order as $oldord => $neword) {
				if ($neword != $oldord) {
					$success = $success && (EditFunctions::ReplaceGedrec($pid, $f[$neword], $f[$oldord], "OBJE", $change_id, $change_type, "", $pid_type));
				}
			}
			if ($success) EditFunctions::PrintSuccessMessage();
		}
		else {
			EditFunctions::PrintFailMessage(GM_LANG_invalid_order);
			$success = false;
		}
		break;
		
	
	// NOTE: Reorder children
	// NOTE: Done for Genmod 2.0
	case "reorder_children":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php">
			<input type="hidden" name="action" value="reorder_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="pid_type" value="<?php print $pid_type; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<input type="hidden" name="option" value="bybirth" />
			<table class="NavBlockTable EditTable">
			<tr><td class="NavBlockColumnHeader EditTableColumnHeader" colspan="2">
			<?php PrintHelpLink("reorder_children_help", "qm", "reorder_children"); ?>
			<?php print GM_LANG_reorder_children; ?>
			</td></tr>
			<?php
				$children = $object->children;
				// Filter out the deleted children
				$keys = array();
				$index = 1;
				foreach($children as $key => $child) {
					if ($child->isdeleted) unset ($children[$key]);
					else {
						$keys[$child->xref] = $index;
						$index++;
					}
					
				}
				if (!empty($option) && $option == "bybirth") {
					uasort($children, "IndiBirthSort");
				}
				$ct = count($children);
				$i = 1;
				foreach($children as $pid => $child) {
					print "<tr>\n<td class=\"NavBlockField\">\n";
					print "<select name=\"order[".$keys[$child->xref]."]\">\n";
					for($j = 1; $j <= $ct; $j++) {
						print "<option value=\"".$j."\"";
						if ($j == $i) print " selected=\"selected\"";
						print ">".($j)."</option>\n";
					}
					print "</select>\n";
					print "</td><td class=\"NavBlockLabel\">";
					print $child->name;
					print "<br />";
					PersonFunctions::PrintFirstMajorFact($child);
					print "</td>\n</tr>\n";
					$i++;
				}
			?>
			<tr><td class="NavBlockFooter" colspan="2">
			<input type="submit" value="<?php print GM_LANG_save; ?>" />&nbsp;
			<input type="button" value="<?php print GM_LANG_sort_by_birth; ?>" onclick="document.reorder_form.action.value='reorder_children'; document.reorder_form.submit();" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: reorder_update 
	// NOTE: Done for Genmod 2.0
	case "reorder_update":
		if (EditFunctions::CheckReorder($order)) {
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$success = true;
			$lines = count($order);
			$f = array();
			for ($i = 1; $i <= $lines; $i++) {
				$t = getsubrecord(1,"1 CHIL", $gedrec, $i);
				$f[$i] = $t;
			}
			foreach($order as $oldord => $neword) {
				if ($neword != $oldord) {
					$success = $success && (EditFunctions::ReplaceGedrec($pid, $f[$neword], $f[$oldord], "CHIL", $change_id, $change_type, "", $pid_type));
				}
			}
			if ($success) EditFunctions::PrintSuccessMessage();
		}
		else {
			EditFunctions::PrintFailMessage(GM_LANG_invalid_order);
			$success = false;
		}
		break;
		
	// NOTE: reorder_fams 
	// NOTE: Done for Genmod 2.0
	case "reorder_fams":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php">
			<input type="hidden" name="action" value="reorder_fams_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<input type="hidden" name="pid_type" value="<?php print $pid_type; ?>" />
			<input type="hidden" name="option" value="bymarriage" />
			<table class="NavBlockTable EditTable">
			<tr><td class="NavBlockColumnHeader EditTableColumnHeader" colspan="2">
			<?php PrintHelpLink("reorder_families_help", "qm", "reorder_families"); ?>
			<?php print GM_LANG_reorder_families; ?>
			</td></tr>
			<?php
			print "<tr><td class=\"NavBlockLabel\">".GM_LANG_order."</td><td class=\"NavBlockLabel\">".GM_LANG_family."</td></tr>";
				$fams = $object->spousefamilies;
				// Filter out the deleted children
				$keys = array();
				$index = 1;
				foreach($fams as $key => $family) {
					if ($family->isdeleted) unset ($fams[$key]);
					else {
						$keys[$family->xref] = $index;
						$index++;
					}
				}
				if (!empty($option) && $option == "bymarriage") {
					$sortby = "MARR";
					uasort($fams, "CompareDate");
				}
				
				$i=1;
				$ct = count($fams);
				foreach($fams as $famid => $family) {
					print "<tr>\n<td class=\"NavBlockField\">\n";
					print "<select name=\"order[".$keys[$family->xref]."]\">\n";
					for($j = 1; $j <= $ct; $j++) {
						print "<option value=\"".($j)."\"";
						if ($j == $i) print " selected=\"selected\"";
						print ">".($j)."</option>\n";
					}
					print "</select>\n";
					print "</td><td class=\"NavBlockLabel\">";
					print $family->name;
					print "<br />";
					FactFunctions::PrintSimpleFact($family->marr_fact, false, false); 
					print "</td>\n</tr>\n";
					$i++;
				}
			?>
			<tr><td class="NavBlockFooter" colspan = "2">
			<input type="submit" value="<?php print GM_LANG_save; ?>" />
			<input type="button" value="<?php print GM_LANG_sort_by_marriage; ?>" onclick="document.reorder_form.action.value='reorder_fams'; document.reorder_form.submit();" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: reorder_fams_update 
	// NOTE: Done for Genmod 2.0
	case "reorder_fams_update":
		if (EditFunctions::CheckReorder($order)) {
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$success = true;
			$lines = count($order);
			$f = array();
			for ($i = 1; $i <= $lines; $i++) {
				$t = getsubrecord(1,"1 FAMS", $gedrec, $i);
				$f[$i] = $t;
			}
			foreach($order as $oldord => $neword) {
				if ($neword != $oldord) {
					$success = $success && (EditFunctions::ReplaceGedrec($pid, $f[$neword], $f[$oldord], "FAMS", $change_id, $change_type, "", $pid_type));
				}
			}
			if ($success) EditFunctions::PrintSuccessMessage();
		}
		else {
			EditFunctions::PrintFailMessage(GM_LANG_invalid_order);
			$success = false;
		}
		break;
	
	// NOTE relation_fams	
	// NOTE: Done for Genmod 2.0
	case "relation_fams":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php">
			<input type="hidden" name="action" value="relation_fams_update" />
			<input type="hidden" name="pid" value="<?php print $pid; ?>" />
			<input type="hidden" name="pid_type" value="<?php print $pid_type; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="NavBlockTable EditTable">
				<tr><td class="NavBlockColumnHeader EditTableColumnHeader" colspan="3">
				<?php PrintHelpLink("relation_families_help", "qm", "relation_families"); ?>
				<?php print GM_LANG_relation_families; ?>
				</td></tr>
				<?php
				print "<tr><td class=\"NavBlockLabel\">".GM_FACT_PEDI."</td><td class=\"NavBlockLabel\">".GM_LANG_family."</td><td class=\"NavBlockLabel\">".GM_LANG_primary."</td></tr>";
				$families = $object->childfamilies;
				foreach($families as $key => $family) {
					if ($family->isdeleted) unset($families[$key]);
				}
				$hasprimary = false;
				foreach($families as $famid => $fam) {
					print "<tr>\n<td class=\"NavBlockField\">\n";
					EditFunctions::PrintPedi("pedi_".$fam->xref, "", $fam->pedigreetype);
					print "</td><td class=\"NavBlockField\">";
					print $fam->name;
					print "<br />";
					FactFunctions::PrintSimpleFact($fam->marr_fact, false, false); 
					print "</td>\n<td class=\"NavBlockField\">";
					print "<input type=\"radio\" name=\"select_prim\" value=\"".$fam->xref."\" ";
					if ($fam->showprimary) {
						print "checked=\"checked\" ";
						$hasprimary = true;
					}
					print "/></td></tr>\n";
				}
				print "<tr><td colspan=\"2\" class=\"NavBlockLabel\">".GM_LANG_no_primary."</td><td class=\"NavBlockField\">";
				print "<input type=\"radio\" name=\"select_prim\" value=\"noprim\" ";
				if (!$hasprimary) print "checked=\"checked\" ";
				print "/></td></tr>\n";
			?>
			<tr><td class="NavBlockFooter" colspan = "3">
			<input type="submit" value="<?php print GM_LANG_save; ?>" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: relation_fams_update
	// NOTE: Done for Genmod 2.0
	case "relation_fams_update":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		$families = $object->childfamilies;
		foreach($families as $key => $family) {
			if ($family->isdeleted) unset($families[$key]);
		}
		// Check for more than 1 birth relation
		$nobirths = 0;
		foreach($families as $famid => $fam) {
			$k = "pedi_".$fam->xref;
			if ($$k == "birth") $nobirths++;
		}
		if ($nobirths <= 1) {
			$success = true;
			foreach($families as $famid => $fam) {
				$update = false;
				$rec = GetSubRecord(1, "1 FAMC @".$fam->xref."@", $gedrec, 1);
				$k = "pedi_".$fam->xref;
				if ($$k == "birth") $$k = "";
				if ($$k != $fam->pedigreetype) {
					if ($fam->pedigreetype == "") {
						$recnew = trim($rec)."\r\n"."2 PEDI ".$$k;
					}
					else {
						$repl = "2 PEDI ".$fam->pedigreetype;
						if (!empty($$k)) $for = "2 PEDI ".$$k;
						else $for = "";
						$recnew = preg_replace("/$repl/", "$for", $rec);
					}
					$update = true;
				}
				else $recnew = $rec;
	
				if ($fam->xref != $select_prim) {
					if ($fam->showprimary) {
						$recnew = preg_replace("/2 _PRIMARY Y/", "", $recnew);
						$update = true;
					}
				}
				else if (!$fam->showprimary) {
					$recnew = trim($recnew)."\r\n2 _PRIMARY Y";
					$update = true;
				}
				if ($update) $success = $success && EditFunctions::ReplaceGedrec($pid, $rec, $recnew, "FAMC", $change_id, $change_type, "", "INDI");
			}
			if ($success) EditFunctions::PrintSuccessMessage();
		}
		else {
			EditFunctions::PrintFailMessage(GM_LANG_invalid_birthfams);
			$success = false;
		}
		
		break;
		
	// NOTE: changefamily
	// NOTE: Done for Genmod 2.0
	case "changefamily":
		$father = $object->husb;
		$mother = $object->wife;
		$children = $object->children;
		if (count($children)>0) {
			if (is_object($father)) {
				if ($father->sex == "F") $father->setFamLabel($famid, GM_LANG_mother);
				else $father->setFamLabel($famid, GM_LANG_father);
			}
			if (is_object($mother)) {
				if ($mother->sex == "M") $mother->setFamLabel($famid, GM_LANG_father);
				else $mother->setFamLabel($famid, GM_LANG_mother);
			}
			foreach ($children as $i => $child) {
				if (is_object($children[$i])) {
					if ($children[$i]->sex == "M") $children[$i]->setFamLabel($famid, GM_LANG_son);
					else if ($children[$i]->sex == "F") $children[$i]->setFamLabel($famid, GM_LANG_daughter);
					else $children[$i]->setFamLabel($famid, GM_LANG_child);
				}
			}
		}
		else {
			if (is_object($father)) {
				if ($father->sex == "F") $father->setFamLabel($famid, GM_LANG_wife);
				else if ($father->sex == "M") $father->setFamLabel($famid, GM_LANG_husband);
				else $father->setFamLabel($famidGM_LANG_spouse);
			}
			if (is_object($mother)) {
				if ($mother->sex == "F") $mother->setFamLabel($famid, GM_LANG_wife);
				else if ($mother->sex == "M") $mother->setFamLabel($famid, GM_LANG_husband);
				else $father->setFamLabel($famid, GM_LANG_spouse);
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
			}
			if (pediElement) {
				pediElement.style.display = 'block';
			}
		}
		//-->
		</script>
		<form name="changefamform" method="post" action="edit_interface.php">
			<input type="hidden" name="action" value="changefamily_update" />
			<input type="hidden" name="famid" value="<?php print $famid;?>" />
			<input type="hidden" name="pid_type" value="<?php print $pid_type; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type;?>" />
			<table class="NavBlockTable EditTable">
				<tr><td colspan="4" class="NavBlockColumnHeader EditTableColumnHeader"><?php PrintHelpLink("change_family_instr","qm","change_family_members"); ?><?php print GM_LANG_change_family_members; ?></td></tr>
				<tr>
					<td class="NavBlockLabel"><?php print GM_LANG_family_role; ?></td>
					<td class="NavBlockLabel"><?php print GM_LANG_name; ?></td>
					<td class="NavBlockLabel"><?php print GM_FACT_PEDI; ?></td>
					<td class="NavBlockLabel"><?php print GM_LANG_action; ?></td>
				</tr>
				<tr>
				<?php
				if (is_object($father)) {
				?>
					<td class="NavBlockField"><?php print $father->label[$famid]; ?><input type="hidden" name="HUSB" value="<?php print $father->xref;?>" /></td>
					<td id="HUSBName" class="NavBlockField"><?php print PrintReady($father->name); ?><br /><?php PersonFunctions::PrintFirstMajorFact($father); ?></td><td class="NavBlockField">&nbsp;</td>
				<?php
				}
				else {
				?>
					<td class="NavBlockField"><?php print GM_LANG_father; ?><input type="hidden" name="HUSB" value="" /></td>
					<td id="HUSBName" class="NavBlockField"></td><td class="NavBlockField">&nbsp;</td>
				<?php
				}
				?>
					<td class="NavBlockField">
						<a href="#" id="husbrem" style="display: <?php print !is_object($father) ? 'none':'block'; ?>;" onclick="document.changefamform.HUSB.value=''; document.getElementById('HUSBName').innerHTML=''; this.style.display='none'; return false;"><?php print GM_LANG_remove; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('HUSBName'); remElement = document.getElementById('husbrem'); return findIndi(document.changefamform.HUSB);"><?php print GM_LANG_change; ?></a><br />
					</td>
				</tr>
				<tr>
				<?php
				if (is_object($mother)) {
				?>
					<td class="NavBlockField"><?php print $mother->label[$famid]; ?><input type="hidden" name="WIFE" value="<?php print $mother->xref;?>" /></td>
					<td id="WIFEName" class="NavBlockField"><?php print PrintReady($mother->name); ?><br /><?php PersonFunctions::PrintFirstMajorFact($mother); ?></td><td class="NavBlockField">&nbsp;</td>
				<?php
				}
				else {
				?>
					<td class="NavBlockField"><?php print GM_LANG_mother; ?><input type="hidden" name="WIFE" value="" /></td>
					<td id="WIFEName" class="NavBlockField"></td><td class="NavBlockField">&nbsp;</td>
				<?php
				}
				?>
					<td class="NavBlockField">
						<a href="#" id="wiferem" style="display: <?php print !is_object($mother) ? 'none':'block'; ?>;" onclick="document.changefamform.WIFE.value=''; document.getElementById('WIFEName').innerHTML=''; this.style.display='none'; return false;"><?php print GM_LANG_remove; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('WIFEName'); remElement = document.getElementById('wiferem'); return findIndi(document.changefamform.WIFE);"><?php print GM_LANG_change; ?></a><br />
					</td>
				</tr>
				<?php
				$i=0;
				foreach($children as $key=>$child) {
					if (!is_null($child)) {
						$pedi = $child->childfamilies[$famid]->pedigreetype;
					?>
				<tr>
					<td class="NavBlockField"><?php print $child->label[$famid]; ?><input type="hidden" name="CHIL<?php print $i; ?>" value="<?php print $child->xref;?>" /></td>
					<td id="CHILName<?php print $i; ?>" class="NavBlockField"><?php print PrintReady($child->name); ?><br /><?php PersonFunctions::PrintFirstMajorFact($child); ?></td>
					<td id="CHILPedi<?php print $i; ?>" class="NavBlockField"><?php EditFunctions::PrintPedi("CHILPedisel".$i, "", $pedi); ?></td>
					<td class="NavBlockField <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="childrem<?php print $i; ?>" style="display:block;" onclick="document.changefamform.CHIL<?php print $i; ?>.value=''; document.getElementById('CHILName<?php print $i; ?>').innerHTML=''; this.style.display='none'; return false;"><?php print GM_LANG_remove; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('CHILName<?php print $i; ?>'); remElement = document.getElementById('childrem<?php print $i; ?>'); return findIndi(document.changefamform.CHIL<?php print $i; ?>);"><?php print GM_LANG_change; ?></a><br />
					</td>
				</tr>
					<?php
						$i++;
					}
				}
				$pedi = "";
					?>
				<tr>
					<td class="NavBlockField"><?php print GM_LANG_add_child; ?><input type="hidden" name="CHIL<?php print $i; ?>" value="" /></td>
					<td id="CHILName<?php print $i; ?>" class="NavBlockField"></td>
					<td id="CHILPedi<?php print $i; ?>" class="NavBlockField"><div id="CHILHide<?php print $i; ?>" style="display: none;"><?php EditFunctions::PrintPedi("CHILPedisel".$i, "", $pedi); ?></div></td>
					<td class="NavBlockField">
						<a href="#" id="childrem<?php print $i; ?>" style="display: none;" onclick="document.changefamform.CHIL<?php print $i; ?>.value=''; document.getElementById('CHILName<?php print $i; ?>').innerHTML=''; this.style.display='none'; return false;"><?php print GM_LANG_remove; ?></a>
						<a href="#" onclick="pediElement = document.getElementById('CHILHide<?php print $i; ?>'); nameElement = document.getElementById('CHILName<?php print $i; ?>'); remElement = document.getElementById('childrem<?php print $i; ?>'); return findIndi(document.changefamform.CHIL<?php print $i; ?>);"><?php print GM_LANG_change; ?></a><br />
					</td>
				</tr>
			<tr>
				<td class="NavBlockFooter" colspan="4">
					<input type="submit" value="<?php print GM_LANG_save; ?>" />&nbsp;<input type="button" value="<?php print GM_LANG_cancel; ?>" onclick="window.close();" />
				</td>
			</tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: changefamily_update
	// NOTE: Done for Genmod 2.0
	case "changefamily_update":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		$father = $object->husb;
		$mother = $object->wife;
		$children = $object->children;
		$updated = false;
		$success = true;
		//-- action: replace existing father link
		if (!empty($HUSB) && is_object($father) && $father->xref != $HUSB) {

			// Replace the father link in the fam record
			$oldrec = GetSubRecord(1, "1 HUSB @$father->xref@", $gedrec);
			$newrec = preg_replace("/1 HUSB @$father->xref@/", "1 HUSB @$HUSB@\r\n", $oldrec);
			if (trim($oldrec) != trim($newrec)) EditFunctions::ReplaceGedrec($famid, $oldrec, $newrec, "HUSB", $change_id, $change_type, "", "FAM");

			// Remove the fam link from the old father record
			if ($father->ischanged) $frec = $father->changedgedrec;
			else $frec = $father->gedrec;
			foreach ($father->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) {
					$oldfath = GetSubrecord(1, "1 FAMS @$famid@", $frec, 1);
					$success = $success && EditFunctions::ReplaceGedrec($father->xref, $oldfath, "", "FAMS", $change_id, $change_type, "", "INDI");
					break;
				}
			}
			
			// Add the fam link to the new father
			$newhusb =& Person::GetInstance($HUSB);
			$found = false;
			foreach ($newhusb->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && EditFunctions::ReplaceGedrec($HUSB, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
			}
			$updated = true;
		}
		//-- action: remove the father link
		if (empty($HUSB) && is_object($father)) {
			
			// Remove the father link from the fam
			$oldfam = GetSubrecord(1, "1 HUSB @$father->xref", $gedrec, 1);
			$success = $success && EditFunctions::ReplaceGedrec($famid, $oldfam, "", "HUSB", $change_id, $change_type, "", "FAM");
			$updated = true;
			
			// Remove the fam link from the old father record
			if ($father->ischanged) $frec = $father->changedgedrec;
			else $frec = $father->gedrec;
			foreach ($father->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) {
					$oldfath = GetSubrecord(1, "1 FAMS @$famid@", $frec, 1);
					$success = $success && EditFunctions::ReplaceGedrec($father->xref, $oldfath, "", "FAMS", $change_id, $change_type, "", "INDI");
					break;
				}
			}
			$updated = true;
		}
		//-- Add the father link
		if (!empty($HUSB) && !is_object($father)) {
			
			// Add the father link to the fam
			$hrec = GetSubrecord(1, "1 HUSB @$HUSB@", $gedrec, 1);
			if (empty($hrec)) {
				$newrec = "1 HUSB @$HUSB@\r\n";
				$success = $success && EditFunctions::ReplaceGedrec($famid, "", $newrec, "HUSB", $change_id, $change_type, "", "INDI");
			}
			
			// Add the fam link to the new father
			$newhusb =& Person::GetInstance($HUSB);
			$found = false;
			foreach ($newhusb->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && EditFunctions::ReplaceGedrec($HUSB, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
			}
			$updated = true;
		}
		
		//-- action: replace existing mother link
		if (!empty($WIFE) && is_object($mother) && $mother->xref != $WIFE) {

			// Replace the mother link in the fam record
			$oldrec = GetSubRecord(1, "1 WIFE @$mother->xref@", $gedrec);
			$newrec = preg_replace("/1 WIFE @$mother->xref@/", "1 WIFE @$WIFE@\r\n", $oldrec);
			if (trim($oldrec) != trim($newrec)) EditFunctions::ReplaceGedrec($famid, $oldrec, $newrec, "WIFE", $change_id, $change_type, "", "FAM");

			// Remove the fam link from the old mother record
			if ($mother->ischanged) $mrec = $mother->changedgedrec;
			else $mrec = $mother->gedrec;
			foreach ($mother->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) {
					$oldmoth = GetSubrecord(1, "1 FAMS @$famid@", $mrec, 1);
					$success = $success && EditFunctions::ReplaceGedrec($mother->xref, $oldmoth, "", "FAMS", $change_id, $change_type, "", "INDI");
					break;
				}
			}
			
			// Add the fam link to the new mother
			$newwife =& Person::GetInstance($WIFE);
			$found = false;
			foreach ($newwife->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && EditFunctions::ReplaceGedrec($WIFE, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
			}
			$updated = true;
		}
		//-- action: remove the mother link
		if (empty($WIFE) && is_object($mother)) {
			
			// Remove the father link from the fam
			$oldfam = GetSubrecord(1, "1 WIFE @$mother->xref", $gedrec, 1);
			$success = $success && EditFunctions::ReplaceGedrec($famid, $oldfam, "", "WIFE", $change_id, $change_type, "", "FAM");
			$updated = true;
			
			// Remove the fam link from the old father record
			if ($mother->ischanged) $mrec = $mother->changedgedrec;
			else $mrec = $mother->gedrec;
			foreach ($mother->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) {
					$oldmoth = GetSubrecord(1, "1 FAMS @$famid@", $mrec, 1);
					$success = $success && EditFunctions::ReplaceGedrec($mother->xref, $oldmoth, "", "FAMS", $change_id, $change_type, "", "INDI");
					break;
				}
			}
			$updated = true;
		}
		//-- Add the mother link
		if (!empty($WIFE) && !is_object($mother)) {
			
			// Add the mother link to the fam
			$wrec = GetSubrecord(1, "1 WIFE @$WIFE@", $gedrec, 1);
			if (empty($wrec)) {
				$newrec = "1 WIFE @$WIFE@\r\n";
				$success = $success && EditFunctions::ReplaceGedrec($famid, "", $newrec, "WIFE", $change_id, $change_type, "", "INDI");
			}
			
			// Add the fam link to the new mother
			$newwife =& Person::GetInstance($WIFE);
			$found = false;
			foreach ($newwife->spousefamilies as $key => $fam) {
				if ($fam->xref == $famid) $found = true;
				break;
			}
			if (!$found) {
				$newrec = "1 FAMS @$famid@\r\n";
				$success = $success && EditFunctions::ReplaceGedrec($WIFE, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
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
				$child = Person::GetInstance($CHIL);
				if ($child->ischanged) $indirec = $child->changedgedrec;
				else $indirec = $child->gedrec;
				// NOTE: Check if child is already in family record
				// NOTE: If not, add it to the family record
				if (preg_match("/1 CHIL @$CHIL@/", $gedrec) == 0) {
					$newrec = "1 CHIL @$CHIL@\r\n";
					$success = $success && EditFunctions::ReplaceGedrec($famid, "", $newrec, "CHIL", $change_id, $change_type, "", "FAM");
					$updated = true;
					// NOTE: Check for a family reference in the child record
					if (!empty($indirec) && (preg_match("/1 FAMC @$famid@/", $indirec)==0)) {
						$newrec = "1 FAMC @$famid@\r\n";
						if (!empty($pedi)) $newrec .= "2 PEDI ".$pedi."\r\n";
						$success = $success && EditFunctions::ReplaceGedrec($CHIL, "", $newrec, "FAMC", $change_id, $change_type, "", "INDI");
					}
				}
				// NOTE: it's already there, check for PEDI update
				else {
					$oldfrec = GetSubRecord(1, "1 FAMC @$famid@", $indirec, 1);
					$oldprec = GetSubrecord(2, "2 PEDI", $oldfrec);
					$pediold = GetGedcomValue("PEDI", 2, $oldprec);
					if (empty($pediold) && (!empty($pedi))) {
						$newrec = trim($oldfrec) . "\r\n"."2 PEDI ".$pedi; 
						$success = $success && EditFunctions::ReplaceGedrec($CHIL, $oldfrec, $newrec, "FAMC", $change_id, $change_type, "", "INDI");
					}
					else if (!empty($pediold) && !empty($pedi)) {
						$newrec = preg_replace("/2 PEDI $pediold/", "2 PEDI $pedi", $oldfrec);
						$success = $success && EditFunctions::ReplaceGedrec($CHIL, $oldfrec, $newrec, "FAMC", $change_id, $change_type, "", "INDI");
					}
					else if (!empty($pediold) && empty($pedi)) {
						$newrec = preg_replace("/$oldprec/", "", $oldfrec);
						$success = $success && EditFunctions::ReplaceGedrec($CHIL, $oldfrec, $newrec, "FAMC", $change_id, $change_type, "", "INDI");
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
					$success = $success && EditFunctions::ReplaceGedrec($famid, $oldrec, "", "CHIL", $change_id, $change_type, "", "FAM");
					
					//-- remove the FAMC link from the child record
					if ($child->ischanged) $chgedrec = $child->changedgedrec;
					else $chgedrec = $child->gedrec;
					$oldrec = GetSubrecord(1, "1 FAMC @$famid@", $chgedrec, 1);
					$success = $success && EditFunctions::ReplaceGedrec($child->xref, $oldrec, "" , "FAMC", $change_id, $change_type, "", "INDI");
				}
			}
		}
		
		// Check if any family members are there
		$fam =& Family::NewInstance($famid);
		if ($fam->ischanged) {
			$ct = preg_match("/1 CHIL|HUSB|WIFE/", $fam->changedgedrec);
			if ($ct == 0) {
				// Clean previous deletions of family members of this group first, otherwise the change will not accept
				$success = $success && EditFunctions::DeleteIDFromChangeGroup($famid, $object->gedcomid, $change_id);
				$success = $success && EditFunctions::ReplaceGedRec($famid, $gedrec, "", "FAM", $change_id, $change_type, "", "FAM");
			}
		}
		
		if ($success) EditFunctions::PrintSuccessMessage();
		break;
	
	// NOTE: Add
	// NOTE: Done for Genmod 2.0
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
			EditFunctions::ShowMediaForm($pid);
		}
		else {
			$tags=array();
			$tags[0]=$fact;
			InitCalendarPopUp();
			print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"update\" />\n";
			print "<input type=\"hidden\" name=\"fact\" value=\"".$fact."\" />\n";
			if ($change_type != "add_media_link") print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
			print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
			// We don't know the pid type, as the pid is selected on this page and can be anything
			if ($change_type == "add_media_link") print "<input type=\"hidden\" name=\"pid_type\" value=\"\" />\n";
			else print "<input type=\"hidden\" name=\"pid_type\" value=\"".$pid_type."\" />\n";
			print "<table class=\"NavBlockTable EditTable\">";
			
			if (!in_array($fact, $separatorfacts)) EditFunctions::AddTagSeparator($fact);
			if ($change_type == "add_media_link") {
				EditFunctions::AddTagSeparator($fact);
				print "<tr><td class=\"NavBlockLabel\">";
				PrintHelpLink("find_add_mmlink", "qm", "find_add_mmlink");
				print GM_LANG_find_add_mmlink."</td>";
				print "<td class=\"NavBlockField\">";
                print "<input type=\"text\" name=\"pid\" size=\"4\" tabindex=\"1\"/>";
				print "<input type=\"hidden\" name=\"glevels[]\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"islink[]\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"tag[]\" value=\"OBJE\" />\n";
				print "<input type=\"hidden\" name=\"text[]\" value=\"".$pid."\" />\n";
			 	LinkFunctions::PrintFindIndiLink("pid", GedcomConfig::$GEDCOMID);
			 	LinkFunctions::PrintFindFamilyLink("pid");
			 	LinkFunctions::PrintFindSourceLink("pid");
			 	print "</td></tr>";
		 	}
			if ($fact=="GNOTE") {
//				$fact = "NOTE";
				$focus = EditFunctions::AddSimpleTag("1 NOTE @");
			}
			else if ($fact=="SOUR") $focus = EditFunctions::AddSimpleTag("1 SOUR @");
			else if ($fact=="ASSO") $focus = EditFunctions::AddSimpleTag("1 ASSO @");
			else if ($fact=="OBJE") {
				if ($change_type != "add_media_link") $focus = EditFunctions::AddSimpleTag("1 OBJE @");
				// 3 _PRIM
				EditFunctions::AddSimpleTag("2 _PRIM", "", "2");
				// 3 _THUM
				EditFunctions::AddSimpleTag("2 _THUM");
			}
			else {
				if (!in_array($fact, $emptyfacts)) $focus = EditFunctions::AddSimpleTag("1 ".$fact);
				else EditFunctions::AddSimpleTag("1 ".$fact);
			}
			
			EditFunctions::AddMissingTags(array($fact));
		
			if ($fact=="SOUR") {
				// 1 SOUR
				// 2 PAGE
				EditFunctions::AddSimpleTag("2 PAGE");
				// 2 DATA
				EditFunctions::AddSimpleTag("2 DATA");
				// 3 DATE
				// 3 TEXT
				EditFunctions::AddSimpleTag("3 DATE");
				EditFunctions::AddSimpleTag("3 TEXT");
			}
			if ($fact=="ASSO") {
				// 2 RELA
				EditFunctions::AddSimpleTag("2 RELA");
			}
			if ($rectype != "NOTE" && $rectype != "OBJE" && $fact != "RESN") {
				// 2 RESN
				EditFunctions::AddSimpleTag("2 RESN");
			}

			if ($rectype != "OBJE" && $fact!="RESN") {
				if (!in_array($fact, $nonassolayerfacts)) EditFunctions::PrintAddLayer("ASSO");
				if (!in_array($fact, $nonsourlayerfacts)) EditFunctions::PrintAddLayer("SOUR");
				if (!in_array($fact, $nonobjelayerfacts)) EditFunctions::PrintAddLayer("OBJE");
				if (!in_array($fact, $nonnotelayerfacts)) {
					EditFunctions::PrintAddLayer("NOTE");
					EditFunctions::PrintAddLayer("GNOTE");
				}
			}
			EditFunctions::AddAutoAcceptLink();
			print "<tr><td class=\"NavBlockFooter\" colspan=\"2\">";
			print "<input type=\"submit\" value=\"".GM_LANG_add."\" /></td></tr>\n";
			print "</table>";
			print "</form>\n";
			if (isset($focus)) {
				?>
				<script type="text/javascript">
				<!--
				document.getElementById('<?php print $focus; ?>').focus();
				//-->
				</script>
				<?php
			}
		}
		break;
		
	// NOTE: paste 
	// NOTE: Done for Genmod 2.0
	case "paste":
		$success = false;
		if (!isset($fact)) $fact = "0";
		$newrec = $_SESSION["clipboard"][$fact]["factrec"];
		$change_id = EditFunctions::GetNewXref("CHANGE");
		$ct = preg_match("/1\s(\w+).*/", $newrec, $match);
		if ($ct > 0) {
			$fact = $match[1];
			$success = EditFunctions::ReplaceGedrec($pid, "", $newrec, $fact, $change_id, $change_type, "", $pid_type);
		}
		if ($success) EditFunctions::PrintSuccessMessage();
		break;
		
	// NOTE: addchild 
	// NOTE: Done for Genmod 2.0
	case "addchild":
		EditFunctions::PrintIndiForm("addchildaction", $famid);
		break;
		
	// NOTE: addchildaction 
	// NOTE: Done for Genmod 2.0
	case "addchildaction":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		
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
				$BIRT_DATE = EditFunctions::CheckInputDate($BIRT_DATE);
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
				$CHR_DATE = EditFunctions::CheckInputDate($CHR_DATE);
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
				$DEAT_DATE = EditFunctions::CheckInputDate($DEAT_DATE);
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
			if(!empty($PEDI) && $PEDI != "birth") $gedrec .= "2 PEDI ".$PEDI."\r\n";
		}
		$gedrec = EditFunctions::HandleUpdates($gedrec);
		if ($addsource == "2") {
			$addsourcevalue = GetSubRecord(1, "1 SOUR", $gedrec);
			//$addsourcevalue = substr(preg_replace("/\n(\d) /e", "'\n'.SumNums($1, 1).' '", $addsourcevalue),1);
			// $addsourcevalue = substr(preg_replace_callback("/\n(\d) /", function($matches){return "\n".SumNums($1, 1)." ";}, $addsourcevalue),1);
			$addsourcevalue = substr(preg_replace_callback("/\n(\d)/", function($matches){return $matches[1]+1;}, $addsourcevalue),1);
			if (!empty($addsourcevalue)) $addsourcevalue = trim("2".$addsourcevalue)."\r\n";
		}
		if ($addsource) $gedrec = preg_replace("/2 SOUR @XXX@\r\n/", $addsourcevalue, $gedrec); 
		
		// NOTE: New record is appended to the database
		$xref = EditFunctions::AppendGedrec($gedrec, "INDI", $change_id, $change_type);
		
		if ($xref && !empty($famid)) {
			EditFunctions::PrintSuccessMessage();
			$newrec = "";
			if (!in_array($xref, $object->children_ids)) {
				$newrec = "1 CHIL @$xref@\r\n";
				EditFunctions::ReplaceGedrec($famid, "", $newrec, "CHIL", $change_id, $change_type, "", "FAM");
				$success = true;
			}
			else print $famid." &lt -- &gt ".$xref.": ".GM_LANG_link_exists;
		}
		break;
	
	// NOTE: addspouse done
	// NOTE: Done for Genmod 2.0
	case "addspouse":
		EditFunctions::PrintIndiForm("addspouseaction", $famid, "", "", $famtag);
		break;
		
	// NOTE: addspouseaction done
	// NOTE: Done for Genmod 2.0
	case "addspouseaction":
		$change_id = EditFunctions::GetNewXref("CHANGE");

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
				$BIRT_DATE = EditFunctions::CheckInputDate($BIRT_DATE);
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
			$newrec .= "1 CHR\r\n";
			if (!empty($CHR_DATE)) {
				$CHR_DATE = EditFunctions::CheckInputDate($CHR_DATE);
				$newrec .= "2 DATE $CHR_DATE\r\n";
				if (!empty($CHR_TIME)) $newrec .= "3 TIME $CHR_TIME\r\n";
			}
			if (!empty($CHR_PLAC)) $newrec .= "2 PLAC $CHR_PLAC\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($CHR)) {
			$newrec .= "1 CHR Y\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		
		if ((!empty($DEAT_DATE))||(!empty($DEAT_PLAC))) {
			$newrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = EditFunctions::CheckInputDate($DEAT_DATE);
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
		$newrec = EditFunctions::HandleUpdates($newrec);
		$l1source = GetSubRecord(1, "1 SOUR", $newrec);
		if ($addsource == "2") {
			$addsourcevalue = GetSubRecord(1, "1 SOUR", $newrec);
			//$addsourcevalue = substr(preg_replace("/\n(\d) /e", "'\n'.SumNums($1, 1).' '", $addsourcevalue),1);
			$addsourcevalue = substr(preg_replace_callback("/\n(\d)/", function($matches){return $matches[1]+1;}, $addsourcevalue),1);
			if (!empty($addsourcevalue)) $addsourcevalue = trim("2".$addsourcevalue)."\r\n";
		}
		if ($addsource) $newrec = preg_replace("/2 SOUR @XXX@\r\n/", $addsourcevalue, $newrec); 
		// NOTE: Save the new indi record and get the new ID
		$xref = EditFunctions::AppendGedrec($newrec, "INDI", $change_id, $change_type);
	
		if ($xref) EditFunctions::PrintSuccessMessage();
		else exit;

		$success = true;
		if ($famid == "new") {
			$famrec = "0 @new@ FAM\r\n";
			if ($SEX == "M") $famtag = "HUSB";
			if ($SEX == "F") $famtag = "WIFE";
			if ($famtag == "HUSB") {
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
					$MARR_DATE = EditFunctions::CheckInputDate($MARR_DATE);
					$famrec .= "2 DATE $MARR_DATE\r\n";
				}
				if (!empty($MARR_PLAC)) $famrec .= "2 PLAC $MARR_PLAC\r\n";
				if ($addsource) $famrec .= $addsourcevalue;
			}
			else if (!empty($MARR)) {
				$famrec .= "1 MARR Y\r\n";
				if ($addsource) $famrec .= $addsourcevalue;
			}
			// If there is no marriage info to attach the source info, do it to the family record
			else $famrec .= $l1source;
			$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
		}
		else if (!empty($famid)) {
			$family =& Family::GetInstance($famid);
			if (!$family->isempty) {
				$famrec = trim($famrec);
				$newrec = "1 $famtag @$xref@\r\n";
				EditFunctions::ReplaceGedrec($famid, "", $newrec, $fact, $change_id, $change_type, "", "FAM");
			}
		}
		if ((!empty($famid)) && ($famid != "new")) {
			$newperson =& Person::GetInstance($xref);
			// NOTE: Check if the record already has a link to this family
			if (!isset($newperson->spousefamilies[$famid])) {
				$newrec = "1 FAMS @$famid@\r\n";
				EditFunctions::ReplaceGedrec($xref, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
			}
			else print $famid." &lt -- &gt ".$xref.": ".GM_LANG_link_exists;
		}
		if (!empty($pid)) {
			if (!isset($object->spousefamilies[$famid])) {
				$newrec = "1 FAMS @$famid@\r\n";
				EditFunctions::ReplaceGedrec($pid, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
			}
			else print $famid." &lt --- &gt ".$pid.": ".GM_LANG_link_exists;
		}
		break;
	
	// NOTE: Done for Genmod 2.0
	case "addnewfamlink":
		print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linknewfamaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"pid_type\" value=\"".$pid_type."\" />\n";
		print "<table class=\"NavBlockTable EditTable\">";
		EditFunctions::AddTagSeparator($change_type);
		if ($famtag == "CHIL"){
			$showbio = true;
			foreach ($object->childfamilies as $id => $family) {
				if ($family->pedigreetype == "") {
					$showbio = false;
					break;
				}
			}
			print "<tr><td class=\"NavBlockLabel\">".GM_FACT_PEDI."</td>";
			print "<td class=\"NavBlockField\">";
			EditFunctions::PrintPedi("PEDI", "", "", $showbio);
			print "</td></tr>";
		}
		EditFunctions::AddAutoAcceptLink();
		print "\n<tr><td class=\"NavBlockFooter\" colspan=\"2\">";
		print "<input type=\"submit\" value=\"".GM_LANG_add."\" />\n";
		print "\n</td></tr>";
		print "</table>\n";
		print "</form>\n";
		break;

	// NOTE: Done for Genmod 2.0
	case "linknewfamaction":
		// NOTE: Get a change_id
		$change_id = EditFunctions::GetNewXref("CHANGE");
		$success = false;
		
		if ($famtag == "CHIL") {
			// Create the new family
			$newrec = "0 @REF@ FAM\r\n1 CHIL @".$pid."@\r\n";
			$famid = EditFunctions::AppendGedrec($newrec, "FAM", $change_id, $change_type);
			if (!empty($famid)) $success = true;
			
			// Append the famid to the childs record
			$newrec = "1 FAMC @".$famid."@\r\n";
			if (isset($PEDI) && $PEDI != "birth") $newrec .= "2 PEDI ".$PEDI."\r\n";
			$success = $success && EditFunctions::ReplaceGedrec($pid, "", $newrec, "FAMC", $change_id, $change_type, "", "INDI");
		}
		else {
			// Create the new family
			if ($famtag == "HUSB") $newrec = "0 @REF@ FAM\r\n1 HUSB @".$pid."@\r\n";
			else $newrec = "0 @REF@ FAM\r\n1 WIFE @".$pid."@\r\n";
			$famid = EditFunctions::AppendGedrec($newrec, "FAM", $change_id, $change_type);
			if (!empty($famid)) $success = true;
			
			// Append the famid to the persons record
			$newrec = "1 FAMS @".$famid."@\r\n";
			$success = $success && EditFunctions::ReplaceGedrec($pid, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
		}
				
		if ($success) EditFunctions::PrintSuccessMessage();
		break;			

		
	// NOTE: addfamlink 
	// NOTE: Done for Genmod 2.0
	case "addfamlink":
		print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linkfamaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"pid_type\" value=\"".$pid_type."\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<table class=\"NavBlockTable EditTable\">";
		EditFunctions::AddTagSeparator("link_as_child");
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_family."</td>";
		print "<td class=\"NavBlockField\"><input type=\"text\" id=\"famid\" name=\"famid\" size=\"8\" onblur=\"sndReq('famlink', 'getfamilydescriptor', true, 'famid', this.value, '', '');\"/> ";
		LinkFunctions::PrintFindFamilyLink("famid");
		print "&nbsp;<span id=\"famlink\"></span>";
		print "\n</td></tr>";
		if ($famtag == "CHIL") {
			$showbio = true;
			foreach ($object->childfamilies as $id => $family) {
				if ($family->pedigreetype == "") {
					$showbio = false;
					break;
				}
			}
			print "<tr><td class=\"NavBlockLabel\">".GM_FACT_PEDI."</td>";
			print "<td class=\"NavBlockField\">";
			EditFunctions::PrintPedi("PEDI", "", "", $showbio);
			print "</td></tr>";
		}
		EditFunctions::AddAutoAcceptLink();
		print "\n<tr><td class=\"NavBlockFooter\" colspan=\"2\">";
		print "<input type=\"submit\" id=\"submit\" value=\"".GM_LANG_set_link."\" />\n";
		print "\n</td></tr>";
		print "</table>\n";
		print "</form>\n";
		print "<script type=\"text/javascript\">";
		print "<!--\naddchildform.famid.focus();\n//-->";
		print "</script>";
		break;
		
	// NOTE: linkfamaction 
	// NOTE: Done for Genmod 2.0
	case "linkfamaction":
		if (!empty($famid)) {
			// NOTE: Get a change_id
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$famid = str2upper($famid);
			$success = true;
	
			$family =& Family::GetInstance($famid);
			if (!$family->isempty) {
				if ($family->ischanged) $famrec = $family->changedgedrec;
				else $famrec = $family->gedrec;
				$famrec = trim($famrec)."\r\n";
				$itag = "FAMC";
				if ($famtag=="HUSB" || $famtag=="WIFE") $itag="FAMS";
				
				//-- if it is adding a new child to a family
				if ($famtag=="CHIL") {
					if (preg_match("/1 $famtag @$pid@/", $famrec)==0) {
						// NOTE: Notify the session of change
						$newrec = "1 $famtag @$pid@\r\n";
						$success = $success && EditFunctions::ReplaceGedrec($famid, "", $newrec, $famtag, $change_id, $change_type, "", "FAM");
						$newrec = "1 $itag @$famid@\r\n";
						if (isset($PEDI) && $PEDI != "birth") $newrec .= "2 PEDI ".$PEDI."\r\n";
						$success = $success && EditFunctions::ReplaceGedrec($pid, "", $newrec, $itag, $change_id, $change_type, "", "INDI");
					}
					else {
						EditFunctions::PrintFailMessage(GM_LANG_child_present);
						$success = false;
					}
				}
				//-- if it is adding a husband or wife
				else {
					//-- check if the family already has a HUSB or WIFE
					$ct = preg_match("/1 $famtag @(.*)@/", $famrec, $match);
					if ($ct>0) {
						$spid = trim($match[1]);
						if ($famtag == "HUSB") EditFunctions::PrintFailMessage(GM_LANG_husb_present);
						else if ($famtag == "WIFE") EditFunctions::PrintFailMessage(GM_LANG_wife_present);
						print "<br />";
						$spouse =& Person::GetInstance($spid);
						print constant("GM_FACT_".$famtag).": ".$spouse->name;
						$success = false;
					}
					else {
						// NOTE: Update the individual record for the person
						if (preg_match("/1 $itag @$famid@/", $gedrec)==0) {
							$newrec = "1 $itag @$famid@\r\n";
							$success = $success && EditFunctions::ReplaceGedrec($pid, "", $newrec, $itag, $change_id, $change_type, "", "INDI");
						}
						
						$newrec = "1 $famtag @$pid@\r\n";
						$success = $success && EditFunctions::ReplaceGedrec($famid, "", $newrec, $famtag, $change_id, $change_type, "", "FAM");
					}
				}
				if ($success) EditFunctions::PrintSuccessMessage();
			}
			else EditFunctions::PrintFailMessage(GM_LANG_family_not_found);
		}
		break;	
		
		
	// NOTE: addname 
	// NOTE: Done for Genmod 2.0
	case "addname":
		EditFunctions::PrintIndiForm("update", "", "new", "NEW");
		break;
		
	// NOTE: addnewparent done
	// NOTE: Done for Genmod 2.0
	case "addnewparent":
		EditFunctions::PrintIndiForm("addnewparentaction", $famid, "", "", $famtag);
		break;
		
	// NOTE: addnewparentaction done
	// NOTE: Done for Genmod 2.0
	case "addnewparentaction":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		
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
		if (!empty($BIRT_DATE) || !empty($BIRT_PLAC)) {
			$newrec .= "1 BIRT\r\n";
			if (!empty($BIRT_DATE)) {
				$BIRT_DATE = EditFunctions::CheckInputDate($BIRT_DATE);
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
		
		if (!empty($CHR_DATE) || !empty($CHR_PLAC)) {
			$newrec .= "1 CHR\r\n";
			if (!empty($CHR_DATE)) {
				$CHR_DATE = EditFunctions::CheckInputDate($CHR_DATE);
				$newrec .= "2 DATE $CHR_DATE\r\n";
				if (!empty($CHR_TIME)) $newrec .= "3 TIME $CHR_TIME\r\n";
			}
			if (!empty($CHR_PLAC)) $newrec .= "2 PLAC $CHR_PLAC\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		else if (!empty($CHR)) {
			$newrec .= "1 CHR Y\r\n";
			if ($addsource) $newrec .= "2 SOUR @XXX@\r\n";
		}
		
		if (!empty($DEAT_DATE) || !empty($DEAT_PLAC)) {
			$newrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = EditFunctions::CheckInputDate($DEAT_DATE);
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
		
		$newrec = EditFunctions::HandleUpdates($newrec);
		$l1source = GetSubRecord(1, "1 SOUR", $newrec);
		if ($addsource == "2") {
			$addsourcevalue = GetSubRecord(1, "1 SOUR", $newrec);
			// $addsourcevalue = substr(preg_replace("/\n(\d) /e", "'\n'.SumNums($1, 1).' '", $addsourcevalue),1);
			$addsourcevalue = substr(preg_replace_callback("/\n(\d)/", function($matches){return $matches[1]+1;}, $addsourcevalue),1);
			if (!empty($addsourcevalue)) $addsourcevalue = trim("2".$addsourcevalue)."\r\n";
		}
		if ($addsource) $newrec = preg_replace("/2 SOUR @XXX@\r\n/", $addsourcevalue, $newrec); 
		
		// NOTE: Save the new indi record and get the new ID
		$xref = EditFunctions::AppendGedrec($newrec, "INDI", $change_id, $change_type);
		if ($xref) EditFunctions::PrintSuccessMessage();
		else exit;
		$success = true;
		if ($famid == "new") {
			$famrec = "0 @new@ FAM\r\n";
			if ($famtag == "HUSB") {
				$famrec .= "1 HUSB @$xref@\r\n";
				$famrec .= "1 CHIL @$pid@\r\n";
			}
			else {
				$famrec .= "1 WIFE @$xref@\r\n";
				$famrec .= "1 CHIL @$pid@\r\n";
			}
			$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
		}
		else if (!empty($famid)) {
			$family =& Family::GetInstance($famid);
			if (!$family->isempty) {
				$newrec = "1 $famtag @$xref@\r\n";
				EditFunctions::ReplaceGedrec($famid, "", $newrec, $famtag, $change_id, $change_type, "", "FAM");
			}
		}
		if (!empty($famid) && $famid != "new") {
				$newrec = "1 FAMS @$famid@\r\n";
				EditFunctions::ReplaceGedrec($xref, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
		}
		if (!empty($pid)) {
			if ($gedrec) {
				$ct = preg_match("/1 FAMC @$famid@/", $gedrec);
				if ($ct==0) {
					$newrec = "1 FAMC @$famid@\r\n";
					EditFunctions::ReplaceGedrec($pid, "", $newrec, "FAMC", $change_id, $change_type, "", "INDI");
				}
			}
		}
		// Don't forget to add the marriage
		if (!empty($famid) && (!empty($MARR_DATE) || !empty($MARR_PLAC))) {
			$newrec = "1 MARR\r\n";
			if (!empty($MARR_DATE)) $newrec .= "2 DATE ".$MARR_DATE."\r\n";
			if (!empty($MARR_PLAC)) $newrec .= "2 PLAC ".$MARR_PLAC."\r\n";
			if ($addsource) $newrec .= $addsourcevalue;
			EditFunctions::ReplaceGedrec($famid, "", $newrec, "MARR", $change_id, $change_type, "", "FAM");
		}
		else if (!empty($famid) && !empty($MARR)) {
			$newrec = "1 MARR Y\r\n";
			if ($addsource) $newrec .= $addsourcevalue;
			EditFunctions::ReplaceGedrec($famid, "", $newrec, "MARR", $change_id, $change_type, "", "FAM");
		}
		// If there is no marriage info to attach the source info, do it to the family record
		else {
			if (!empty($l1source)) EditFunctions::ReplaceGedrec($famid, "", $l1source, "SOUR", $change_id, $change_type, "", "FAM");;
		}
		break;
		
	// NOTE: add new source
	case "addnewsource":
		$tabkey = 1;
		?>
		<form method="post" action="edit_interface.php" onSubmit="return check_ansform(this);">
		<input type="hidden" name="action" value="addsourceaction" />
		<input type="hidden" name="pid" value="newsour" />
		<input type="hidden" name="pid_type" value="<php print $pid_type; ?>" />
		<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
		<table class="NavBlockTable EditTable"><?php
		
		EditFunctions::AddTagSeparator("create_source");
		// 1 TITL
		$element_id = EditFunctions::AddSimpleTag("1 TITL");
		?>
		<script type="text/javascript">
		<!--
			function check_ansform(frm) {
				if (document.getElementById("<?php print $element_id?>").value=="") {
					alert('<?php print GM_LANG_must_provide.GM_FACT_TITL; ?>');
					document.getElementById("<?php print $element_id?>").focus();
					return false;
				}
				return true;
			}
		//-->
		</script>
		<?php
		// 1 ABBR
		EditFunctions::AddSimpleTag("1 ABBR");
		// 1 AUTH
		EditFunctions::AddSimpleTag("1 AUTH");
		// 1 PUBL
		EditFunctions::AddSimpleTag("1 PUBL");
		// 1 TEXT
		EditFunctions::AddSimpleTag("1 TEXT");
		// 1 REPO
		EditFunctions::AddSimpleTag("1 REPO @");
		
		// 1 OBJE
		EditFunctions::PrintAddLayer("OBJE", 1);
		// 1 NOTE
		EditFunctions::PrintAddLayer("NOTE", 1);
		EditFunctions::PrintAddLayer("GNOTE", 1);
		EditFunctions::AddAutoAcceptLink();
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\" value=\"".GM_LANG_create_source."\" /></td></tr>";
		print "</table>";
		print "</form>";
		print "\n<script type=\"text/javascript\">\n<!--\ndocument.getElementById(\"".$element_id."\").focus();\n//-->\n</script>";
		break;
		
	// NOTE: create a source record from the incoming variables done
	case "addsourceaction":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		
		$newged = "0 @XREF@ SOUR\r\n";
		$newged = EditFunctions::HandleUpdates($newged);
		$xref = EditFunctions::AppendGedrec($newged, "SOUR", $change_id, $change_type);
		if ($xref) {
			EditFunctions::PrintSuccessMessage(GM_LANG_new_source_created."<br /><br />".(GedcomConfig::$EDIT_AUTOCLOSE ? "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>" : "<a href=\"javascript:// SOUR $xref\" onclick=\"openerpasteid('$xref'); return false;\">".GM_LANG_paste_id_into_field.": $xref</a>\n"));
		}
		break;
		
	// NOTE: add new repository done
	case "addnewrepository":
		?>
		<script type="text/javascript">
		<!--
			function check_form(frm) {
				if (frm.NAME.value=="") {
					alert('<?php print GM_LANG_must_provide." ".GM_FACT_NAME; ?>');
					frm.NAME.focus();
					return false;
				}
				return true;
			}
		//-->
		</script>
		<?php
		$tabkey = 1;
		?>
		<form method="post" action="edit_interface.php" onSubmit="return check_form(this);">
			<input type="hidden" name="action" value="addrepoaction" />
			<input type="hidden" name="pid" value="newrepo" />
			<input type="hidden" name="pid_type" value="<php print $pid_type; ?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="NavBlockTable EditTable">
				<?php EditFunctions::AddTagSeparator("create_repository"); ?>
				<tr><td class="NavBlockLabel"><?php print GM_FACT_NAME; ?></td>
				<td class="NavBlockField"><input tabindex="<?php print $tabkey; ?>" type="text" name="NAME" id="NAME" value="" size="40" maxlength="255" /> <?php LinkFunctions::PrintSpecialCharLink("NAME"); ?></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="NavBlockLabel"><?php print GM_FACT_ADDR; ?></td>
				<td class="NavBlockField"><textarea tabindex="<?php print $tabkey; ?>" name="ADDR" id="ADDR" rows="5" cols="60"></textarea><?php LinkFunctions::PrintSpecialCharLink("ADDR"); ?> </td></tr>
				<?php $tabkey++; ?>
				<tr><td class="NavBlockLabel"><?php print GM_FACT_PHON; ?></td>
				<td class="NavBlockField"><input tabindex="<?php print $tabkey; ?>" type="text" name="PHON" id="PHON" value="" size="40" maxlength="255" /> </td></tr>
				<?php $tabkey++; ?>
				<tr><td class="NavBlockLabel"><?php print GM_FACT_FAX; ?></td>
				<td class="NavBlockField"><input tabindex="<?php print $tabkey; ?>" type="text" name="FAX" id="FAX" value="" size="40" /></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="NavBlockLabel"><?php print GM_FACT_EMAIL; ?></td>
				<td class="NavBlockField"><input tabindex="<?php print $tabkey; ?>" type="text" name="EMAIL" id="EMAIL" value="" size="40" maxlength="255" onchange="sndReq('errem', 'checkemail', true, 'email', this.value);" />&nbsp;&nbsp;<span id="errem"></span></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="NavBlockLabel"><?php print GM_FACT_WWW; ?></td>
				<td class="NavBlockField"><input tabindex="<?php print $tabkey; ?>" type="text" name="WWW" id="WWW" value="" size="40" maxlength="255" /> </td></tr>
				<?php EditFunctions::AddAutoAcceptLink(); ?>
			<tr><td class="NavBlockFooter" colspan="2">
			<input type="submit" value="<?php print GM_LANG_create_repository; ?>" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
		
	// NOTE: create a repository record from the incoming variables done
	case "addrepoaction":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		
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
		$xref = EditFunctions::AppendGedrec($newged, "REPO", $change_id, $change_type);
		if ($xref) {
			EditFunctions::PrintSuccessMessage(GM_LANG_new_repo_created."<br /><br />".(GedcomConfig::$EDIT_AUTOCLOSE ? "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>" : "<a href=\"javascript:// REPO $xref\" onclick=\"openerpasteid('$xref'); return false;\">".GM_LANG_paste_rid_into_field.": $xref</a>\n"));
		}
		break;
		
	// NOTE: add new general note
	case "addnewgnote":
		$tabkey = 1;
		 ?>
		<form method="post" action="edit_interface.php">
		<input type="hidden" name="action" value="addgnoteaction" />
		<input type="hidden" name="pid" value="newgnote" />
		<input type="hidden" name="pid_type" value="<php print $pid_type; ?>" />
		<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
		<table class="NavBlockTable EditTable"><?php
		
//		EditFunctions::AddTagSeparator("create_gnote");

		$element_id = EditFunctions::AddSimpleTag("0 NOTE");
		EditFunctions::AddSimpleTag("1 RESN");
		print EditFunctions::PrintAddLayer("SOUR", 1);
		EditFunctions::AddAutoAcceptLink();
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\" value=\"".GM_LANG_create_general_note."\" /></td></tr>";
		print "</table>";
		print "</form>";
		print "\n<script type=\"text/javascript\">\n<!--\ndocument.getElementById(\"".$element_id."\").focus();\n//-->\n</script>";
		break;
		
	// NOTE: create a note record from the incoming variables done
	case "addgnoteaction":

		if (empty($NOTE)) {
			EditFunctions::PrintFailMessage(GM_LANG_no_empty_notes);
		}
		else {
			$change_id = EditFunctions::GetNewXref("CHANGE");
			$newged = "0 @XREF@ NOTE";
			$newged = MakeCont($newged, $NOTE);
			$newged = EditFunctions::HandleUpdates($newged);
			$xref = EditFunctions::AppendGedrec($newged, "NOTE", $change_id, $change_type);
			if ($xref) {
				EditFunctions::PrintSuccessMessage(GM_LANG_new_gnote_created."<br /><br />".(GedcomConfig::$EDIT_AUTOCLOSE ? "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>" : "<a href=\"javascript:// NOTE $xref\" onclick=\"openerpasteid('$xref'); return false;\">".GM_LANG_paste_noteid_into_field.": $xref</a>\n"));
			}
		}
		break;
	
	// NOTE: Edit
	// NOTE: edit done
	case "edit":

		InitCalendarPopUp();

		print "<form name=\"editform\" method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\">";
		print "<input type=\"hidden\" name=\"action\" value=\"update\" />";
		print "<input type=\"hidden\" name=\"fact\" value=\"".$fact."\" />";
		print "<input type=\"hidden\" name=\"count\" value=\"".$count."\" />";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />";
		print "<input type=\"hidden\" name=\"pid_type\" value=\"".$pid_type."\" />";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />";
		print "<table class=\"NavBlockTable EditTable\">";
		$orgfact = $fact;
//		if ($fact == "OBJE" && $change_type == "edit_media_link") {
//			$oldrec = GetSubRecord(1, "1 $fact", $gedrec, $count);
//			print $oldrec;
			
//			EditFunctions::AddSimpleTag(
			// EditFunctions::ShowMediaForm($pid, 'updatemedia', 'edit_media');
//		}
//		else {
			// Get the record type
			$ct = preg_match("/0\s@\w+@\s(\w+)/", $gedrec, $match);
			if ($ct>0) $rectype = $match[1];
			else $rectype = "";
			$oldrec = EditFunctions::SortFactDetails($oldrec);
			$gedlines = explode("\r\n", trim($oldrec));	// -- find the number of lines in the record
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
			
			// if ($change_type == "edit_gender") EditFunctions::AddSimpleTag(GetSubRecord(1, "1 SEX", $gedrec));
			$i = 0;
			$insour = false;
			$inobj = false;
			$addfactsdone = false;
			if (!in_array($fact, $separatorfacts)) EditFunctions::AddTagSeparator($fact);
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
					else if (GedcomConfig::$WORD_WRAPPED_NOTES) $text .= " ";
					$text .= $cmatch[2];
					$i++;
				}
//				$text = trim($text);
				$text = rtrim($text);
				$tags[]=$fact;
				if (($fact == "SOUR" || $fact == "OBJE" || $fact == "RESN" || $fact == "ASSO") && !$insour && !$inobj && !$addfactsdone) {
					EditFunctions::AddMissingTags($tags);
					$addfactsdone = true;
				}
				// Finished adding missing tags
				// Print the next fact
				EditFunctions::AddSimpleTag($level." ".$fact." ".$text);
//				print "adding tag. rectype: ".$rectype." level:".$level." fact: ".$fact." orgfact: ".$orgfact." content: ".nl2br($text)."<br />";
				if ($fact=="DATE" && $orgfact != "SOUR" && !strpos(@$gedlines[$i+1], " TIME")) {
					if (in_array($orgfact, $timefacts) && $level <= 3) {
						EditFunctions::AddSimpleTag(($level+1)." TIME"); 
						$tags[] = "TIME";
					}
				}
				if ($fact=="MARR" && !strpos(@$gedlines[$i+1], " TYPE")) {
					EditFunctions::AddSimpleTag(($level+1)." TYPE");
					$tags[] = "TYPE";
				}
				
				if ($fact=="ASSO") {
					if (!strpos(@$gedlines[$i+1], " RELA")) {
						EditFunctions::AddSimpleTag(($level+1)." RELA");
						$tags[] = "RELA";
					}
					if (!strpos(@$gedlines[$i+2], " NOTE")) {
						EditFunctions::AddSimpleTag(($level+1)." NOTE");
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
						if (!array_key_exists("DATE", $arrdata)) EditFunctions::AddSimpleTag($datalevel." DATE");
						if (!array_key_exists("TEXT", $arrdata)) EditFunctions::AddSimpleTag($datalevel." TEXT");
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
						if (!in_array("PAGE", $insourfacts)) EditFunctions::AddSimpleTag($l1." PAGE");
						if (!in_array("DATA", $insourfacts)) {
							EditFunctions::AddSimpleTag($l1." DATA");
							EditFunctions::AddSimpleTag($l2." DATE");
							EditFunctions::AddSimpleTag($l2." TEXT");
						}
//						else {
//							if (!in_array("DATE", $insourfacts)) EditFunctions::AddSimpleTag($l2." DATE");
//							if (!in_array("TEXT", $insourfacts)) EditFunctions::AddSimpleTag($l2." TEXT");
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
//						EditFunctions::ShowMediaForm($pid);
						// 1 OBJE
						if (!$factlink) {
							// 2 FORM
							if (!in_array("FORM", $tags)) EditFunctions::AddSimpleTag(($inobjlevel+1)." FORM");
							// 2 FILE
							if (!in_array("FILE", $tags)) EditFunctions::AddSimpleTag(($inobjlevel+1)." FILE");
							// 2 TITL
							if (!in_array("TITL", $tags)) EditFunctions::AddSimpleTag(($inobjlevel+1)." TITL");
						}
						// 2 _PRIM
						if (!in_array("_PRIM", $tags)) EditFunctions::AddSimpleTag(($inobjlevel+1)." _PRIM");
						// 2 _THUM
						if (!in_array("_THUM", $tags)) EditFunctions::AddSimpleTag(($inobjlevel+1)." _THUM");
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
				if ($i == count($gedlines) && !$addfactsdone) EditFunctions::AddMissingTags($tags);
			} while ($i<count($gedlines));
			// 2 RESN
			if (!in_array("RESN", $tags)&& $rectype != "OBJE") EditFunctions::AddSimpleTag("2 RESN");
			if ($orgfact != "SEX" && $orgfact != "RESN" && $rectype != "OBJE") {
				// Only show asso's for family or indi facts
				if (($rectype == "FAM" || $rectype == "INDI") && !in_array($orgfact, $nonassolayerfacts)) EditFunctions::PrintAddLayer("ASSO");
				if (!in_array($orgfact, $nonsourlayerfacts)) EditFunctions::PrintAddLayer("SOUR");
				if (!in_array($orgfact, $nonobjelayerfacts)) EditFunctions::PrintAddLayer("OBJE");
				if (!in_array($orgfact, $nonnotelayerfacts)) {
					EditFunctions::PrintAddLayer("NOTE");
					EditFunctions::PrintAddLayer("GNOTE");
				}
			}
			EditFunctions::AddAutoAcceptLink();
			print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\" value=\"".GM_LANG_save."\" /></td></tr>\n";
			print "</table>";
			print "</form>\n";
//		}
		break;
		
	// NOTE: editraw done
	case "editraw":
		if (!$factedit) {
			EditFunctions::PrintFailMessage(GM_LANG_privacy_prevented_editing.(!empty($pid) ? "<br />".GM_LANG_privacy_not_granted." pid $pid." : "").(!empty($famid) ? "<br />".GM_LANG_privacy_not_granted." famid $famid." : ""));
			PrintSimpleFooter();
			exit;
		}
		else {
			$gedrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $gedrec);
			print "<form method=\"post\" action=\"edit_interface.php\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"updateraw\" />\n";
			print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
			print "<input type=\"hidden\" name=\"pid_type\" value=\"".$pid_type."\" />\n";
			print "<input type=\"hidden\" name=\"oldrec\" value=\"".urlencode($oldrec)."\" />\n";
			print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
			print "<table class=\"NavBlockTable EditTable\">";
			print "<tr><td class=\"NavBlockColumnHeader EditTableColumnHeader\">";
			PrintHelpLink("edit_edit_raw_help", "qm");
			print GM_LANG_edit_raw."</td></tr>";
			print "<tr><td class=\"NavBlockField\"><textarea name=\"newgedrec\" id=\"newgedrec\" rows=\"20\" cols=\"82\" dir=\"ltr\">".$gedrec."</textarea>";
			LinkFunctions::PrintSpecialCharLink("newgedrec");
			print "</td></tr>\n";
			EditFunctions::AddAutoAcceptLink(1);
			print "<tr><td class=\"NavBlockFooter\"><input type=\"submit\" value=\"".GM_LANG_save."\" /></td></tr>\n";
			print "</table>";
			print "</form>\n";
		}
		break;
		
	//-- edit a fact record in a form
	// NOTE: updateraw done
	case "updateraw":
		$oldrec = urldecode($oldrec);
		$change_id = EditFunctions::GetNewXref("CHANGE");
		$newrec = trim($newgedrec);
		$rectype = GetRecType($newrec);
		if (!$rectype) $rectype = "";
		if (trim($oldrec) != trim($newrec)) {
			$success = (!empty($newrec) && (EditFunctions::ReplaceGedrec($pid, $oldrec, $newrec, $rectype, $change_id, $change_type, "", $rectype)));
			if ($success) EditFunctions::PrintSuccessMessage();
		}
		break;
	
	// NOTE editname done
	case "editname":
		$namerecnew = GetSubRecord(1, "1 NAME", $gedrec, $count);
		EditFunctions::PrintIndiForm("update", "", "", $namerecnew, "", $count);
		break;
		
	
	// NOTE: Copy
	// NOTE: Done for Genmod 2.0
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
			$_SESSION["clipboard"][] = array("type"=>$object->datatype, "factrec"=>$factrec, "fact"=>$fact);
			EditFunctions::PrintSuccessMessage(GM_LANG_record_copied);
		}
		break;
	
	
	// NOTE: Link
	// NOTE: linkspouse
	// NOTE: Done for Genmod 2.0
	case "linkspouse":
		InitCalendarPopUp();
		print "<form method=\"post\" name=\"linkspouseform\" action=\"edit_interface.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linkspouseaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"pid_type\" value=\"".$pid_type."\" />\n";
		print "<input type=\"hidden\" name=\"famid\" value=\"new\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<table class=\"NavBlockTable EditTable\">";
		EditFunctions::AddTagSeparator($famtag);
		print "<tr><td class=\"NavBlockLabel\">";
		if ($famtag=="WIFE") print GM_LANG_wife;
		else print GM_LANG_husband;
		print "</td>";
		print "<td class=\"NavBlockField\"><input id=\"spouseid\" type=\"text\" name=\"spid\" size=\"8\"  onblur=\"sndReq('spouselink', 'getpersonname', true, 'pid', this.value, '', '');\"/> ";
		LinkFunctions::PrintFindIndiLink("spouseid", "");
		print "&nbsp;<span id=\"spouselink\"></span>";
		print "\n</td></tr>";
		EditFunctions::AddTagSeparator("MARR");
		EditFunctions::AddSimpleTag("0 MARR");
		EditFunctions::AddSimpleTag("0 TYPE", "MARR");
		EditFunctions::AddSimpleTag("0 DATE", "MARR");
		EditFunctions::AddSimpleTag("0 PLAC", "MARR");
		EditFunctions::PrintAddLayer("SOUR", 1);
		EditFunctions::PrintAddLayer("OBJE", 1);
		EditFunctions::PrintAddLayer("NOTE", 1);
		EditFunctions::PrintAddLayer("GNOTE", 1);
		EditFunctions::AddAutoAcceptLink();
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\">";
		print "<input type=\"submit\" id=\"submit\" value=\"".GM_LANG_set_link."\" />\n";
		print "</td></tr>";
		print "</table>\n";
		print "</form>\n";
		print "<script type=\"text/javascript\"><!--\n";
		print "linkspouseform.spouseid.focus();";
		print "//--></script>";
		break;
	
	// NOTE: linkspouseaction
	// NOTE: Done for Genmod 2.0
	case "linkspouseaction":
		// NOTE: Get a change_id
		$change_id = EditFunctions::GetNewXref("CHANGE");
		$exist = false;
		print $famtag;
		if (!empty($spid)) {
			// NOTE: Check if the relation doesn't exist yet
			foreach($object->spousefamilies as $id => $family) {
				if (($famtag == "WIFE" && $family->wife_id == $spid) || ($famtag == "HUSB" && $family->husb_id == $spid)) {
					$exist = true;
					EditFunctions::PrintFailMessage(GM_LANG_family_exists."<br />".GM_LANG_family.": ".$row["f_id"]);
					break;
				}
			}
			if (!$exist) {
				$spouse =& Person::GetInstance($spid);
				if (!$spouse->isempty) {
					// NOTE: Create a new family record
					if ($famid == "new") {
						// NOTE: Create the new family record
						$famrec = "0 @new@ FAM\r\n";
						if ($spouse->sex == "M") $famtag = "HUSB";
						else if ($spouse->sex == "F") $famtag = "WIFE";
						else if ($object->sex == "M") $famtag = "WIFE";
						else $famtag = "HUSB";
						if ($famtag == "HUSB") {
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
								$MARR_DATE = EditFunctions::CheckInputDate($MARR_DATE);
								$famrec .= "2 DATE $MARR_DATE\r\n";
							}
							if (!empty($MARR_PLAC)) $famrec .= "2 PLAC $MARR_PLAC\r\n";
						}
						else if (!empty($MARR)) {
							$famrec .= "1 MARR Y\r\n";
						}
						$famrec = EditFunctions::HandleUpdates($famrec);
						$famid = EditFunctions::AppendGedrec($famrec, "FAM", $change_id, $change_type);
					}
					if ((!empty($famid)) && ($famid != "new")) {
						// NOTE: Add the new FAM ID to the spouse record
						$newrec = "1 FAMS @$famid@\r\n";
						EditFunctions::ReplaceGedrec($spid, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
					}
					if (!empty($pid)) {
						// NOTE: Add the new FAM ID to the active person record
						$newrec = "1 FAMS @$famid@\r\n";
						EditFunctions::ReplaceGedrec($pid, "", $newrec, "FAMS", $change_id, $change_type, "", "INDI");
					}
					print $famtag;
					if ($famtag == "HUSB") print GM_LANG_husband_added;
					else if ($famtag == "WIFE") print GM_LANG_wife_added;
				}
				else EditFunctions::PrintFailMessage(GM_LANG_person_not_found);
			}
		}
		break;
		
	// NOTE: Done for Genmod 2.0
	case "update_submitter":
		$change_id = EditFunctions::GetNewXref("CHANGE");
		if (strtolower($pid) != "newsubmitter") {
			if ($object->ischanged) $oldrecord = $object->changedgedrec;
			else $oldrecord = $object->gedrec;
			$newrec = "0 @".$pid."@ SUBM\r\n";
			$newrec = EditFunctions::HandleUpdates($newrec);
			$chanrec = GetSubrecord(1, "1 CHAN", $oldrecord);
			$orec = preg_replace("/$chanrec/", "", $oldrecord);
			if (trim($newrec) != trim($orec)) {
				$success = (EditFunctions::ReplaceGedrec($pid, $oldrecord, $newrec, $fact, $change_id, $change_type, $gedfile, "SUBM"));
				if ($success) EditFunctions::PrintSuccessMessage();
			}
		}
		else {
			$newrec = "0 @new@ SUBM\r\n";
			$newrec = EditFunctions::HandleUpdates($newrec);
			$subid = EditFunctions::AppendGedrec($newrec, "SUBM", $change_id, $change_type, $gedfile);
			$success = (EditFunctions::ReplaceGedrec("HEAD", "", "1 SUBM @".$subid."@", "SUBM", $change_id, $change_type, $gedfile, "HEAD"));
			if ($success) EditFunctions::PrintSuccessMessage();
			
		}
		break;	
		
			
	// NOTE: reconstruct the gedcom from the incoming fields and store it in the file done
	case "update":
		$change_id = EditFunctions::GetNewXref("CHANGE");
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
		$newrec = EditFunctions::HandleUpdates($newrec);
		if (!isset($count)) $count = 1;
		if (!isset($change_type)) $change_type = "unknown";

		// NOTE: Get old record only when we are editing a record
		$addfacts = array("newfact", "add_note", "add_source", "add_media");
		if (in_array($change_type, $addfacts)) $oldrec = "";
		else if ((!isset($tag) || $change_type == "edit_name") && !empty($NAME)) $oldrec = GetSubRecord(1, "1 NAME", $gedrec, $count);
		else if ($change_type != "add_name" && $change_type != "add_media_link" && $change_type != "edit_general_note") $oldrec = GetSubRecord(1, "1 ".$fact, $gedrec, $count);
		else if ($change_type == "edit_general_note") {
			$recs = explode("\r\n", trim($gedrec));
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

		if (!isset($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
		//-- check for photo update
		if (count($_FILES)>0) {
			$result = MediaFS::UploadFiles($_FILES, $folder, true);
			if ($result["errno"] != 0) {
				EditFunctions::PrintFailMessage(GM_LANG_upload_error."<br />".$result["error"]);
			}
			else {
				EditFunctions::PrintSuccessMessage($result["error"]);
				$newmfile = $result["filename"];
				if (!empty($newmfile)) {
					$m = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
					if (!empty($m)) $newmfile = preg_replace("~$m~", "", $newmfile);
					if (!empty($oldrec)) $old = GetGedcomValue("FILE", 1, $oldrec);
					// Old is not empty if a filename already follows the 1 FILE tag
					// If no file was selected/uploaded previously, it's a tag with no value
					if (isset($old) && !empty($old)) $newrec = preg_replace("~".addslashes($old)."~", $newmfile, $newrec);
					else $newrec = preg_replace("~1 FILE\s*~", "1 FILE ".$newmfile."\r\n", $newrec);
				}
			}
		}
		if (trim(EditFunctions::SortFactDetails($oldrec)) != trim(EditFunctions::SortFactDetails($newrec))) $success = EditFunctions::ReplaceGedrec($pid, $oldrec, $newrec, $fact, $change_id, $change_type, $gedfile, $pid_type);
		if ($success) EditFunctions::PrintSuccessMessage();
		break;
	
}

// if there were link errors, don't auto-accept and don't auto-close.
if (!isset($link_error)) $link_error = false;

if (isset($change_id) && $can_auto_accept && !$link_error && (($gm_user->UserCanAccept() && $aa_attempt) || $gm_user->userAutoAccept())) {
	ChangeFunctions::AcceptChange($change_id, GedcomConfig::$GEDCOMID);
}
	
// autoclose window when update successful
if ($success && !$link_error && GedcomConfig::$EDIT_AUTOCLOSE) {
	SwitchGedcom();
	session_write_close();
	print "\n<script type=\"text/javascript\">\n<!--\nedit_close();\n//-->\n</script>";
}
SwitchGedcom();

print "<div class=\"CloseWindow\"><a href=\"javascript:// ".GM_LANG_close_window."\" onclick=\"edit_close();\">".GM_LANG_close_window."</a></div>\n";
PrintSimpleFooter();
?>