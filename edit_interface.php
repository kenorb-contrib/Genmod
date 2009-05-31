<?php
/**
 * PopUp Window to provide editing features.
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
 * @package Genmod
 * @subpackage Edit
 * @version $Id: edit_interface.php,v 1.42 2006/04/30 18:44:14 roland-d Exp $
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

/**
 * Inclusion of the country names
*/
require($GM_BASE_DIRECTORY."languages/countries.en.php");
if (file_exists($GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$LANGUAGE].".php")) require($GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$LANGUAGE].".php");
asort($countries);

if ($_SESSION["cookie_login"]) {
	header("Location: login.php?type=simple&ged=$GEDCOM&url=edit_interface.php".urlencode("?".$QUERY_STRING));
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

print_simple_header("Edit Interface $VERSION");

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
	}
	
	function paste_char(value,lang,mag) {
		pastefield.value += value;
		language_filter = lang;
		magnify = mag;
	}
	
	function edit_close() {
		if (window.opener.showchanges) window.opener.showchanges();
		window.close();
	}
//-->
</script>
<?php
//-- check if user has acces to the gedcom record
$disp = false;
$success = false;
$factdisp = true;
$factedit = true;
if (!empty($pid)) {
	// NOTE: Remove any illegal characters from the ID
	$pid = clean_input($pid);
	if ((strtolower($pid) != "newsour") && (strtolower($pid) != "newrepo")) {
		// NOTE: Do not take the changed record here since non-approved changes should not yet appear
		$gedrec = find_gedcom_record($pid);
		$ct = preg_match("/0 @$pid@ (.*)/", $gedrec, $match);
		if ($ct>0) {
			$type = trim($match[1]);
			//-- if the record is for an INDI then check for display privileges for that indi
			if ($type=="INDI") {
				$disp = displayDetailsById($pid);
				//-- if disp is true, also check for resn access
				if ($disp == true){
					$subs = get_all_subrecords($gedrec, "", false, false);
					foreach($subs as $indexval => $sub) {
						if (FactViewRestricted($pid, $sub)==true) $factdisp = false;
						if (FactEditRestricted($pid, $sub)==true) $factedit = false;
					}
				}
			}
			//-- for FAM check for display privileges on both parents
			else if ($type=="FAM") {
				//-- check if there are restrictions on the facts
				$subs = get_all_subrecords($gedrec, "", false, false);
				foreach($subs as $indexval => $sub) {
					if (FactViewRestricted($pid, $sub)==true) $factdisp = false;
					if (FactEditRestricted($pid, $sub)==true) $factedit = false;
				}
				//-- check if we can display both parents
				$parents = find_parents_in_record($gedrec);
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
	else {
		$disp = true;
	}
}
else if (!empty($famid)) {
	$famid = clean_input($famid);
	if ($famid != "new") {
		
		// TODO: if the fam record has been changed, get the record from the changes table
		if (!isset($gm_changes[$famid."_".$GEDCOM])) $gedrec = find_gedcom_record($famid);
		else $gedrec = find_gedcom_record($famid);
		
		$ct = preg_match("/0 @$famid@ (.*)/", $gedrec, $match);
		if ($ct>0) {
			$type = trim($match[1]);
			//-- if the record is for an INDI then check for display privileges for that indi
			if ($type=="INDI") {
				$disp = displayDetailsById($famid);
				//-- if disp is true, also check for resn access
				if ($disp == true){
					$subs = get_all_subrecords($gedrec, "", false, false);
					foreach($subs as $indexval => $sub) {
						if (FactViewRestricted($famid, $sub)==true) $factdisp = false;
						if (FactEditRestricted($famid, $sub)==true) $factedit = false;
					}
				}
			}
			//-- for FAM check for display privileges on both parents
			else if ($type=="FAM") {
				//-- check if there are restrictions on the facts
				$subs = get_all_subrecords($gedrec, "", false, false);
				foreach($subs as $indexval => $sub) {
					if (FactViewRestricted($famid, $sub)==true) $factdisp = false;
					if (FactEditRestricted($famid, $sub)==true) $factedit = false;
				}
				//-- check if we can display both parents
				$parents = find_parents_in_record($gedrec);
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
else if (($action!="addchild")&&($action!="addchildaction")) {
	print "<span class=\"error\">The \$pid variable was empty.	Unable to perform $action.</span>";
	print_simple_footer();
	$disp = true;
}
else {
	$disp = true;
}

if ((!userCanEdit($gm_username))||(!$disp)||(!$ALLOW_EDIT_GEDCOM)) {
	//print "pid: $pid<br />";
	//print "gedrec: $gedrec<br />";
	print $gm_lang["access_denied"];
	//-- display messages as to why the editing access was denied
	if (!userCanEdit($gm_username)) print "<br />".$gm_lang["user_cannot_edit"];
	if (!$ALLOW_EDIT_GEDCOM) print "<br />".$gm_lang["gedcom_editing_disabled"];
	if (!$disp) {
		print "<br />".$gm_lang["privacy_prevented_editing"];
		if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
		if (!empty($famid)) print "<br />".$gm_lang["privacy_not_granted"]." famid $famid.";
	}
	print "<br /><br /><div class=\"center\"><a href=\"javascript: ".$gm_lang["close_window"]."\" onclick=\"window.close();\">".$gm_lang["close_window"]."</a></div>\n";
	print_simple_footer();
	exit;
}

if (!isset($type)) $type="";
if ($type=="INDI") {
	print "<b>".PrintReady(get_person_name($pid))."</b><br />";
}
else if ($type=="FAM") {
	print "<b>".PrintReady(get_person_name($parents["HUSB"]))." + ".PrintReady(get_person_name($parents["WIFE"]))."</b><br />";
}
else if ($type=="SOUR") {
	print "<b>".PrintReady(get_source_descriptor($pid))."</b><br />";
}
if (strstr($action,"addchild")) {
	if (empty($famid)) {
		print_help_link("edit_add_unlinked_person_help", "qm");
		print "<b>".$gm_lang["add_unlinked_person"]."</b>\n";
	}
	else {
		print_help_link("edit_add_child_help", "qm");
		print "<b>".$gm_lang["add_child"]."</b>\n";
	}
}
else if (strstr($action,"addspouse")) {
	print_help_link("edit_add_spouse_help", "qm");
	print "<b>".$gm_lang["add_".strtolower($famtag)]."</b>\n";
}
else if (strstr($action,"addnewparent")) {
	print_help_link("edit_add_parent_help", "qm");
	if ($famtag=="WIFE") print "<b>".$gm_lang["add_mother"]."</b>\n";
	else print "<b>".$gm_lang["add_father"]."</b>\n";
}
else {
	if (isset($factarray[$type])) print "<b>".$factarray[$type]."</b>";
}
switch ($action) {
	// NOTE: Delete
	// NOTE: deleteperson done
	case "deleteperson":
		$change_id = get_new_xref("CHANGE");
		
		if (!$factedit) {
			print "<br />".$gm_lang["privacy_prevented_editing"];
			if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
			if (!empty($famid)) print "<br />".$gm_lang["privacy_not_granted"]." famid $famid.";
		}
		else {
			// TODO: Notify user if record has already been deleted
			// TODO: Add checking 
			if (!empty($gedrec)) {
				// NOTE: Remove references to families
				$ct = preg_match_all("/1 FAM. @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$famid = $match[$i][1];
					// NOTE: Inform session of change
					$_SESSION["changes"]["INDI"] = true;
					$famrec = find_gedcom_record($famid);
					
					// NOTE: If there is not at least two people in a family then the family is deleted
					$pt = preg_match_all("/1 (.{4}) @(.*)@/", $famrec, $pmatch, PREG_SET_ORDER);
					if ($pt<2) {
						for ($j=0; $j<$pt; $j++) {
							$xref = $pmatch[$j][2];
							if($xref!=$pid) {
								// NOTE: Remove family reference from left over person
								$oldrec = $pmatch[$j][0];
								replace_gedrec($xref, $oldrec, "", $pmatch[$j][1], $change_id, "delete_indi");
							}
						}
						// NOTE: Remove family since no one is left in it
						delete_gedrec($famid, $change_id, "delete_indi");
					}
					else {
						// Update family record to only remove the individual to be deleted
						$indis = preg_match_all("/1 (.{4}) @$pid@/", $famrec, $indismatch, PREG_SET_ORDER);
						for ($counter = 0; $counter < $indis; $counter++) {
							$oldrec = $indismatch[$counter][0];
							replace_gedrec($famid, $oldrec, "", $indismatch[$counter][1], $change_id, "delete_indi");
						}
					}
				}
				$asso = array();
				
				// NOTE: Find ASSO records in individual records
				$sql = "select i_id, i_gedcom from ".$TBLPREFIX."individuals where i_gedcom REGEXP '[1-9] ASSO @(.*)@'";
				$res = dbquery($sql);
				while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$asso[$row["i_id"]] = $row["i_gedcom"];
				}
				
				// NOTE: Find ASSO records in family records
				$sql = "select f_id, f_gedcom from ".$TBLPREFIX."families where f_gedcom REGEXP '[1-9] ASSO @(.*)@'";
				$res = dbquery($sql);
				while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$asso[$row["f_id"]] = $row["f_gedcom"];
				}
				
				foreach ($asso as $id => $record) {
					$asso_found = preg_match_all("/[1-9] ASSO @(.*)@/", $record, $asso_match, PREG_SET_ORDER);
					if ($asso_found > 0) {
						foreach ($asso_match as $key => $match) {
							if ($match[1] == $pid) replace_gedrec($id, $match[0], "", $fact, $change_id, "delete_indi");
						}
					}
				}
				
				// NOTE: Delete record since the rest has been removed
				delete_gedrec($pid, $change_id, "delete_indi");
				print "<br /><br />".$gm_lang["gedrec_deleted"];
			}
		}
		break;
	// NOTE: deletefamily done
	case "deletefamily":
		$change_id = get_new_xref("CHANGE");
		
		if (!$factedit) {
			print "<br />".$gm_lang["privacy_prevented_editing"];
			if (!empty($pid)) print "<br />".$gm_lang["privacy_not_granted"]." pid $pid.";
			if (!empty($famid)) print "<br />".$gm_lang["privacy_not_granted"]." famid $famid.";
		}
		else {
			if (!empty($gedrec)) {
				$success = true;
				$fact_reference = array("HUSB", "WIFE", "CHIL");
				$ct = preg_match_all("/1 (\w+) @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$fact = $match[$i][1];
					$id = $match[$i][2];
					if ($GLOBALS["DEBUG"]) print $fact." ".$id." ";
					if (in_array($fact, $fact_reference)) {
						$indirec = find_gedcom_record($id);
						if (!empty($indirec)) {
							$lines = preg_split("/[\r\n]+/", $indirec);
							foreach($lines as $indexval => $line) {
								if ((preg_match("/@$famid@/", $line)>0)) {
									$success = $success && replace_gedrec($id, $line, "", $fact, $change_id, $change_type);
								}
							}
						}
					}
				}
				if ($success) {
					$success = $success && delete_gedrec($famid, $change_id, $change_type);
				}
				if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
			}
		}
		break;
	// NOTE: deletesource done
	case "deletesource":
		$change_id = get_new_xref("CHANGE");
		
		if (!empty($gedrec)) {
			$success = true;
			$query = "SOUR @$pid@";
			// -- array of names
			$myindilist = array();
			$myfamlist = array();
	
			$myindilist = search_indis($query);
			foreach($myindilist as $key=>$value) {
				$indirec = $value["gedcom"];
				$lines = preg_split("/[\r\n]+/", $indirec);
				foreach($lines as $indexval => $line) {
					if ((preg_match("/@$pid@/", $line)>0)) {
						$success = $success && replace_gedrec($key, $line, "", "SOUR", $change_id, $change_type);
					}
				}
			}
			$myfamlist = search_fams($query);
			foreach($myfamlist as $key=>$value) {
				$indirec = $value["gedcom"];
				$lines = preg_split("/[\r\n]+/", $indirec);
				foreach($lines as $indexval => $line) {
					if ((preg_match("/@$pid@/", $line)>0)) {
						$success = $success && replace_gedrec($key, $line, "", "SOUR", $change_id, $change_type);
					}
				}
			}
			if ($success) {
				$success = $success && delete_gedrec($pid, $change_id, $change_type);
			}
			if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
		}
		break;
	// NOTE: deleterepo done
	case "deleterepo":
		$change_id = get_new_xref("CHANGE");
		
		if (!empty($gedrec)) {
			$success = true;
			$query = "REPO @$pid@";
			
			// -- array of names
			$mysourlist = array();
			
			$mysourlist = search_sources($query);
			foreach($mysourlist as $key=>$value) {
				$sourrec = $value["gedcom"];
				$oldrec = get_sub_record(1, "1 REPO", $sourrec);
				if ($oldrec != "") $success = $success && replace_gedrec($key, $oldrec, "", "REPO", $change_id, $change_type);
			}
			if ($success) {
				$success = $success && delete_gedrec($pid, $change_id, $change_type);
			}
			if ($success) print "<br /><br />".$gm_lang["gedrec_deleted"];
		}
		break;
		
		
	// NOTE: delete done
	case "delete":
		$change_id = get_new_xref("CHANGE");
		
		if ($GLOBALS["DEBUG"]) phpinfo(32);
		$oldrec = get_sub_record(1, "1 $fact", $gedrec, $count);
		if (replace_gedrec($pid, $oldrec, "", $fact, $change_id, $change_type)) print "<br /><br />".$gm_lang["gedrec_deleted"];
		break;
	
	
	// NOTE: Reorder
	// NOTE: reorder_children done
	case "reorder_children":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php">
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
				$ct = preg_match_all("/1 CHIL @(.+)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$child = trim($match[$i][1]);
					$irec = find_person_record($child);
					if (isset($indilist[$child])) $children[$child] = $indilist[$child];
				}
				if ((!empty($option))&&($option=="bybirth")) {
					uasort($children, "compare_date");
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
					print PrintReady(get_person_name($pid));
					print "<br />";
					print_first_major_fact($pid);
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
	// NOTE: reorder_update done
	case "reorder_update":
		$change_id = get_new_xref("CHANGE");
		asort($order);
		reset($order);
		$lines = preg_split("/\n/", $gedrec);
		$newgedrec = "";
		for($i=0; $i<count($lines); $i++) {
			if (preg_match("/1 CHIL/", $lines[$i])==0) $newgedrec .= $lines[$i]."\n";
		}
		foreach($order as $child=>$num) {
			$newgedrec .= "1 CHIL @".$child."@\r\n";
		}
		$success = (replace_gedrec($pid, $gedrec, $newgedrec, "CHIL", $change_id, $change_type));
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
	// NOTE: reorder_fams done
	case "reorder_fams":
		?>
		<form name="reorder_form" method="post" action="edit_interface.php">
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
				$fams = array();
				$ct = preg_match_all("/1 FAMS @(.+)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$famid = trim($match[$i][1]);
					$frec = find_family_record($famid);
					if ($frec===false) $frec = find_gedcom_record($famid);
					if (isset($famlist[$famid])) $fams[$famid] = $famlist[$famid];
				}
				if ((!empty($option))&&($option=="bymarriage")) {
					$sortby = "MARR";
					uasort($fams, "compare_date");
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
					print PrintReady(get_family_descriptor($famid));
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
	// NOTE: reorder_fams_update done
	case "reorder_fams_update":
		$change_id = get_new_xref("CHANGE");
		asort($order);
		reset($order);
		$lines = preg_split("/\n/", $gedrec);
		$newgedrec = "";
		for($i=0; $i<count($lines); $i++) {
			if (preg_match("/1 FAMS/", $lines[$i])==0) $newgedrec .= $lines[$i]."\n";
		}
		foreach($order as $famid=>$num) {
			$newgedrec .= "1 FAMS @".$famid."@\r\n";
		}
		$success = (replace_gedrec($pid, $gedrec, $newgedrec, "FAMS", $change_id, $change_type));
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
	
	
	
		
	// NOTE: changefamily done
	case "changefamily":
		require_once 'includes/family_class.php';
		$family = new Family($gedrec);
		$father = $family->getHusband();
		$mother = $family->getWife();
		$children = $family->getChildren();
		if (count($children)>0) {
			if (!is_null($father)) {
				if ($father->getSex()=="F") $father->setLabel($gm_lang["mother"]);
				else $father->setLabel($gm_lang["father"]);
			}
			if (!is_null($mother)) {
				if ($mother->getSex()=="M") $mother->setLabel($gm_lang["father"]);
				else $mother->setLabel($gm_lang["mother"]);
			}
			for($i=0; $i<count($children); $i++) {
				if (!is_null($children[$i])) {
					if ($children[$i]->getSex()=="M") $children[$i]->setLabel($gm_lang["son"]);
					else if ($children[$i]->getSex()=="F") $children[$i]->setLabel($gm_lang["daughter"]);
					else $children[$i]->setLabel($gm_lang["child"]);
				}
			}
		}
		else {
			if (!is_null($father)) {
				if ($father->getSex()=="F") $father->setLabel($gm_lang["wife"]);
				else if ($father->getSex()=="M") $father->setLabel($gm_lang["husband"]);
				else $father->setLabel($gm_lang["spouse"]);
			}
			if (!is_null($mother)) {
				if ($mother->getSex()=="F") $mother->setLabel($gm_lang["wife"]);
				else if ($mother->getSex()=="M") $mother->setLabel($gm_lang["husband"]);
				else $father->setLabel($gm_lang["spouse"]);
			}
		}
		?>
		<script type="text/javascript">
		<!--
		var nameElement = null;
		var remElement = null;
		function pastename(name) {
			if (nameElement) {
				nameElement.innerHTML = name;
			}
			if (remElement) {
				remElement.style.display = 'block';
			}
		}
		//-->
		</script>
		<br /><br />
		<form name="changefamform" method="post" action="edit_interface.php">
			<input type="hidden" name="action" value="changefamily_update" />
			<input type="hidden" name="famid" value="<?php print $famid;?>" />
			<input type="hidden" name="change_type" value="<?php print $change_type;?>" />
			<table class="width50 <?php print $TEXT_DIRECTION; ?>">
				<tr><td colspan="3" class="topbottombar"><?php print_help_link("change_family_instr","qm","change_family_instr"); ?><?php print $gm_lang["change_family_members"]; ?></td></tr>
				<tr>
				<?php
				if (!is_null($father)) {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $father->getLabel(); ?></b><input type="hidden" name="HUSB" value="<?php print $father->getXref();?>" /></td>
					<td id="HUSBName" class="shade1 <?php print $TEXT_DIRECTION; ?>"><?php print PrintReady($father->getName()); ?></td>
				<?php
				}
				else {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $gm_lang["spouse"]; ?></b><input type="hidden" name="HUSB" value="" /></td>
					<td id="HUSBName" class="shade1 <?php print $TEXT_DIRECTION; ?>"></td>
				<?php
				}
				?>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="husbrem" style="display: <?php print is_null($father) ? 'none':'block'; ?>;" onclick="document.changefamform.HUSB.value=''; document.getElementById('HUSBName').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('HUSBName'); remElement = document.getElementById('husbrem'); return findIndi(document.changefamform.HUSB);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
				<tr>
				<?php
				if (!is_null($mother)) {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $mother->getLabel(); ?></b><input type="hidden" name="WIFE" value="<?php print $mother->getXref();?>" /></td>
					<td id="WIFEName" class="shade1 <?php print $TEXT_DIRECTION; ?>"><?php print PrintReady($mother->getName()); ?></td>
				<?php
				}
				else {
				?>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $gm_lang["spouse"]; ?></b><input type="hidden" name="WIFE" value="" /></td>
					<td id="WIFEName" class="shade1 <?php print $TEXT_DIRECTION; ?>"></td>
				<?php
				}
				?>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="wiferem" style="display: <?php print is_null($mother) ? 'none':'block'; ?>;" onclick="document.changefamform.WIFE.value=''; document.getElementById('WIFEName').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('WIFEName'); remElement = document.getElementById('wiferem'); return findIndi(document.changefamform.WIFE);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
				<?php
				$i=0;
				foreach($children as $key=>$child) {
					if (!is_null($child)) {
					?>
				<tr>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $child->getLabel(); ?></b><input type="hidden" name="CHIL<?php print $i; ?>" value="<?php print $child->getXref();?>" /></td>
					<td id="CHILName<?php print $i; ?>" class="shade1"><?php print PrintReady($child->getName()); ?></td>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="childrem<?php print $i; ?>" style="display: block;" onclick="document.changefamform.CHIL<?php print $i; ?>.value=''; document.getElementById('CHILName<?php print $i; ?>').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('CHILName<?php print $i; ?>'); remElement = document.getElementById('childrem<?php print $i; ?>'); return findIndi(document.changefamform.CHIL<?php print $i; ?>);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
					<?php
						$i++;
					}
				}
					?>
				<tr>
					<td class="shade2 <?php print $TEXT_DIRECTION; ?>"><b><?php print $gm_lang["add_child"]; ?></b><input type="hidden" name="CHIL<?php print $i; ?>" value="" /></td>
					<td id="CHILName<?php print $i; ?>" class="shade1"></td>
					<td class="shade1 <?php print $TEXT_DIRECTION; ?>">
						<a href="#" id="childrem<?php print $i; ?>" style="display: none;" onclick="document.changefamform.CHIL<?php print $i; ?>.value=''; document.getElementById('CHILName<?php print $i; ?>').innerHTML=''; this.style.display='none'; return false;"><?php print $gm_lang["remove"]; ?></a>
						<a href="#" onclick="nameElement = document.getElementById('CHILName<?php print $i; ?>'); remElement = document.getElementById('childrem<?php print $i; ?>'); return findIndi(document.changefamform.CHIL<?php print $i; ?>);"><?php print $gm_lang["change"]; ?></a><br />
					</td>
				</tr>
			<tr>
				<td class="topbottombar" colspan="3">
					<input type="submit" value="<?php print $gm_lang["save"]; ?>" />&nbsp;<input type="button" value="<?php print $gm_lang["cancel"]; ?>" onclick="window.close();" />
				</td>
			</tr>
			</table>
		</form>
		<?php
		break;
	// NOTE: changefamily_update done
	case "changefamily_update":
		$change_id = get_new_xref("CHANGE");
		require_once 'includes/family_class.php';
		$family = new Family($gedrec);
		$father = $family->getHusband();
		$mother = $family->getWife();
		$children = $family->getChildren();
		$updated = false;
		//-- add the new father link
		if (!empty($HUSB) && (is_null($father) || $father->getXref()!=$HUSB)) {
			$oldrec = get_sub_record(1, "1 HUSB", $gedrec);
			$newrec = "1 HUSB @$HUSB@\r\n";
			if (trim($oldrec) != trim($newrec)) replace_gedrec($famid, "", $newrec, "HUSB", $change_id, $change_type);
			
			$famids = find_sfamily_ids($HUSB);
			if (!in_array($famid, $famids)) {
				$newrec = "1 FAMS @$famid@\r\n";
				replace_gedrec($HUSB, "", $newrec, "FAMS", $change_id, $change_type);
			}
			$updated = true;
		}
		//-- remove the father link
		if (empty($HUSB)) {
			$oldrec = get_sub_record(1, "1 HUSB", $gedrec);
			if ($oldrec != "") replace_gedrec($famid, $oldrec, "", "HUSB", $change_id, $change_type);
			$updated = true;
		}
		//-- remove the FAMS link from the old father
		if (!is_null($father) && $father->getXref()!=$HUSB) {
			$famids = find_sfamily_ids($father->getXref());
			if (in_array($famid, $famids)) replace_gedrec($famid, $oldrec, "", "FAMS", $change_id, $change_type);
		}
		//-- add the new mother link
		if (!empty($WIFE) && (is_null($mother) || $mother->getXref()!=$WIFE)) {
			$oldrec = get_sub_record(1, "1 WIFE", $gedrec);
			$newrec = "1 WIFE @$HUSB@\r\n";
			if (trim($oldrec) != trim($newrec)) replace_gedrec($famid, "", $newrec, "WIFE", $change_id, $change_type);
			
			$famids = find_sfamily_ids($WIFE);
			if (!in_array($famid, $famids)) {
				$newrec = "1 FAMS @$famid@\r\n";
				replace_gedrec($WIFE, "", $newrec, "FAMS", $change_id, $change_type);
			}
			$updated = true;
		}
		//-- remove the mother link
		if (empty($WIFE)) {
			$oldrec = get_sub_record(1, "1 WIFE", $gedrec);
			if ($oldrec != "") replace_gedrec($famid, $oldrec, "", "WIFE", $change_id, $change_type);
			$updated = true;
		}
		//-- remove the WIFE link from the family
		if (!is_null($mother) && $mother->getXref()!=$WIFE) {
			$famids = find_sfamily_ids($mother->getXref());
			if (in_array($famid, $famids)) replace_gedrec($famid, $oldrec, "", "WIFE", $change_id, $change_type);
		}
		
		//-- update the children
		$i=0;
		$var = "CHIL".$i;
		$newchildren = array();
		while(isset($$var)) {
			$CHIL = $$var;
			if (!empty($CHIL)) {
				$newchildren[] = $CHIL;
				// NOTE: Check if child is already in family record
				// NOTE: If not, add it to the family record
				if (preg_match("/1 CHIL @$CHIL@/", $gedrec)==0) {
					$newrec = "1 CHIL @$CHIL@\r\n";
					replace_gedrec($famid, "", $newrec, "CHIL", $change_id, $change_type);
					$_SESSIONS["changes"]["INDI"] = true;
					$updated = true;
					// NOTE: Check for a family reference in the child record
					$indirec = find_gedcom_record($CHIL);
					if (!empty($indirec) && (preg_match("/1 FAMC @$famid@/", $indirec)==0)) {
						$newrec = "1 FAMC @$famid@\r\n";
						replace_gedrec($CHIL, "", $indirec, "FAMC", $change_id, $change_type);
					}
				}
			}
			$i++;
			$var = "CHIL".$i;
		}
		
		//-- remove the old children
		foreach($children as $key=>$child) {
			if (!is_null($child)) {
				if (!in_array($child->getXref(), $newchildren)) {
					//-- remove the CHIL link from the family record
					$oldrec = "1 CHIL @".$child->getXref()."@\r\n";
					replace_gedrec($famid, $oldrec, "", "CHIL", $change_id, $change_type);
					
					//-- remove the FAMC link from the child record
					$oldrec = "1 FAMC @$famid@\r\n";
					replace_gedrec($child->getXref(), $oldrec, "" , "FAMC", $change_id, $change_type);
				}
			}
		}
		print "<br /><br />".$gm_lang["update_successful"];
		break;
		
		
	
	// NOTE: Add
	// NOTE: add done
	case "add":
		// handle  MARRiage TYPE
		$type_val="";
		if (substr($fact,0,5)=="MARR_") {
			$type_val=substr($fact,5);
			$fact="MARR";
		}
		
		if ($fact=="OBJE") {
			show_media_form($pid);
		}
		else {
		$tags=array();
		$tags[0]=$fact;
		init_calendar_popup();
		print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"update\" />\n";
		print "<input type=\"hidden\" name=\"fact\" value=\"".$fact."\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<table class=\"facts_table\">";
		
		if ($fact=="SOUR") add_simple_tag("1 SOUR @");
		else add_simple_tag("1 ".$fact);
		
		if ($fact=="EVEN" or $fact=="GRAD" or $fact=="MARR") {
			// 1 EVEN|GRAD|MARR
			// 2 TYPE
			add_simple_tag("2 TYPE ".$type_val);
		}
		if (in_array($fact, $templefacts)) {
			// 2 TEMP
			add_simple_tag("2 TEMP");
			// 2 STAT
			add_simple_tag("2 STAT");
		}
		if ($fact=="SOUR") {
			// 1 SOUR
			// 2 PAGE
			add_simple_tag("2 PAGE");
			// 2 DATA
			// 3 TEXT
			add_simple_tag("3 TEXT");
		}
		if ($fact=="EDUC" or $fact=="GRAD" or $fact=="OCCU") {
			// 1 EDUC|GRAD|OCCU
			// 2 CORP
			add_simple_tag("2 CORP");
		}
		if (!in_array($fact, $nondatefacts)) {
			// 2 DATE
			add_simple_tag("2 DATE");
			// 3 TIME
			add_simple_tag("3 TIME");
			// 2 PLAC
			if (!in_array($fact, $nonplacfacts)) add_simple_tag("2 PLAC");
		}
		if ($fact=="BURI") {
			// 1 BURI
			// 2 CEME
			add_simple_tag("2 CEME");
		}
		if ($fact=="BIRT" or $fact=="DEAT" or $fact=="EDUC"
		or $fact=="OCCU" or $fact=="ORDN" or $fact=="RESI") {
			// 1 BIRT|DEAT|EDUC|OCCU|ORDN|RESI
			// 2 ADDR
			add_simple_tag("2 ADDR");
		}
		if ($fact=="OCCU" or $fact=="RESI") {
			// 1 OCCU|RESI
			// 2 PHON|FAX|EMAIL|URL
			add_simple_tag("2 PHON");
			add_simple_tag("2 FAX");
			add_simple_tag("2 EMAIL");
			add_simple_tag("2 URL");
		}
		if ($fact=="DEAT") {
			// 1 DEAT
			// 2 CAUS
			add_simple_tag("2 CAUS");
		}
		if ($fact=="REPO") {
			//1 REPO
			//2 CALN
			add_simple_tag("2 CALN");
		}
		if ($fact!="OBJE") {
			// 2 RESN
			add_simple_tag("2 RESN");
		}
		
		
		if (($fact!="ASSO") && ($fact!="OBJE") && ($fact!="REPO")) print_add_layer("ASSO");
		if (($fact!="SOUR") && ($fact!="OBJE") && ($fact!="REPO")) print_add_layer("SOUR");
		if (($fact!="NOTE") && ($fact!="OBJE")) print_add_layer("NOTE");
		if (($fact!="OBJE") && ($fact!="REPO")) print_add_layer("OBJE");
		
		print "<tr><td class=\"topbottombar\" colspan=\"2\"><input type=\"submit\" value=\"".$gm_lang["add"]."\" /></td></tr>\n";
		print "</table>";
		print "</form>\n";
		}
		break;
		
	// NOTE: paste done
	case "paste":
		$newrec = $_SESSION["clipboard"][$fact]["factrec"];
		$change_id = get_new_xref("CHANGE");
		$success = replace_gedrec($pid, "", $newrec, $fact, $change_id, $change_type);
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
		
		
		
		
		
		
		
	// NOTE: addchild done
	case "addchild":
		print_indi_form("addchildaction", $famid);
		break;
	// NOTE: addchildaction done
	case "addchildaction":
		$change_id = get_new_xref("CHANGE");
		
		// NOTE: Inform session of change
		$_SESSION["changes"]["FAM"] = true;
		
		$gedrec = "0 @REF@ INDI\r\n1 NAME ".trim($NAME)."\r\n";
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
				$BIRT_DATE = check_input_date($BIRT_DATE);
				$gedrec .= "2 DATE $BIRT_DATE\r\n";
			}
			if (!empty($BIRT_PLAC)) $gedrec .= "2 PLAC $BIRT_PLAC\r\n";
		}
		if ((!empty($DEAT_DATE))||(!empty($DEAT_PLAC))) {
			$gedrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = check_input_date($DEAT_DATE);
				$gedrec .= "2 DATE $DEAT_DATE\r\n";
			}
			if (!empty($DEAT_PLAC)) $gedrec .= "2 PLAC $DEAT_PLAC\r\n";
		}
		if (!empty($famid)) $gedrec .= "1 FAMC @$famid@\r\n";
		$gedrec = handle_updates($gedrec);
		
		// NOTE: New record is appended to the database
		$xref = append_gedrec($gedrec, "FAMC", $change_id, $change_type);
		$_SESSION["changes"]["INDI"] = true;
		
		if ($xref) {
			print "<br /><br />".$gm_lang["update_successful"];
			$newrec = "";
			// NOTE: Inform session of change
			$_SESSION["changes"]["FAM"] = true;
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!change_present($famid,true)) $newrec = find_gedcom_record($famid);
			else $newrec = change_present($famid);
			$newrec = trim($newrec);
			// NOTE: Check if the record already has a link to this family
			$ct = preg_match("/1 CHIL @$xref@/", $newrec, $match);
			if ($ct == 0) {
				$newrec = "1 CHIL @$xref@\r\n";
				replace_gedrec($famid, "", $newrec, "CHIL", $change_id, $change_type);
				$_SESSION["changes"]["FAM"] = true;
				$success = true;
			}
			else print $famid." &lt -- &gt ".$xref.": ".$gm_lang["link_exists"];
		}
		break;
	
	// NOTE: addspouse done
	case "addspouse":
		print_indi_form("addspouseaction", $famid, "", "", $famtag);
		break;
		
		
		
	// NOTE: addspouseaction done
	case "addspouseaction":
		$change_id = get_new_xref("CHANGE");
		
		$newrec = "0 @REF@ INDI\r\n1 NAME ".trim($NAME)."\r\n";
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
				$BIRT_DATE = check_input_date($BIRT_DATE);
				$newrec .= "2 DATE $BIRT_DATE\r\n";
			}
			if (!empty($BIRT_PLAC)) $newrec .= "2 PLAC $BIRT_PLAC\r\n";
		}
		if ((!empty($DEAT_DATE))||(!empty($DEAT_PLAC))) {
			$newrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = check_input_date($DEAT_DATE);
				$newrec .= "2 DATE $DEAT_DATE\r\n";
			}
			if (!empty($DEAT_PLAC)) $newrec .= "2 PLAC $DEAT_PLAC\r\n";
		}
		$newrec = handle_updates($newrec);
		// NOTE: Inform session of change
		$_SESSION["changes"]["INDI"] = true;
		// NOTE: Save the new indi record and get the new ID
		$xref = append_gedrec($newrec, "INDI", $change_id, $change_type);
		
		if ($xref) print "<br /><br />".$gm_lang["update_successful"];
		else exit;
		
		$success = true;
		if ($famid=="new") {
			// NOTE: Inform session of change
			$_SESSION["changes"]["FAM"] = true;
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
			$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
		}
		else if (!empty($famid)) {
			$famrec = "";
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!change_present($famid,true)) $famrec = find_gedcom_record($famid);
			else $famrec = change_present($famid);
			if (!empty($famrec)) {
				$famrec = trim($famrec);
				$newrec = "1 $famtag @$xref@\r\n";
				$_SESSION["changes"]["FAM"] = true;
				replace_gedrec($famid, "", $newrec, $fact, $change_id, $change_type);
			}
		}
		if ((!empty($famid)) && ($famid != "new")) {
			$newrec = "";
			// NOTE: Inform session of change
			$_SESSION["changes"]["INDI"] = true;
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!change_present($xref,true)) $newrec = find_gedcom_record($xref);
			else $newrec = change_present($xref);
			$newrec = trim($newrec);
			// NOTE: Check if the record already has a link to this family
			$ct = preg_match("/1 FAMS @$famid@/", $newrec, $match);
			if ($ct == 0) {
				$newrec = "1 FAMS @$famid@\r\n";
				replace_gedrec($xref, "", $newrec, "FAMS", $change_id, $change_type);
			}
			else print $famid." &lt -- &gt ".$xref.": ".$gm_lang["link_exists"];
		}
		if (!empty($pid)) {
			$newrec = "";
			// NOTE: Inform session of change
			$_SESSION["changes"]["INDI"] = true;
			// NOTE: Check if there are changes present, if not get the record otherwise the changed record
			if (!change_present($pid,true)) $newrec = find_gedcom_record($pid);
			else $newrec = change_present($pid);
			$newrec = trim($newrec);
			// NOTE: Check if the record already has a link to this family
			$ct = preg_match("/1 FAMS @$famid@/", $newrec, $match);
			if ($ct == 0) {
				$newrec = "1 FAMS @$famid@\r\n";
				replace_gedrec($pid, "", $newrec, "FAMS", $change_id, $change_type);
			}
			else print $famid." &lt --- &gt ".$pid.": ".$gm_lang["link_exists"];
		}
		break;
	
	// NOTE: addfamlink done
	case "addfamlink":
		print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linkfamaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<table class=\"facts_table\">";
		print "<tr><td class=\"shade2\">".$gm_lang["family"]."</td>";
		print "<td class=\"shade1\"><input type=\"text\" id=\"famid\" name=\"famid\" size=\"8\" /> ";
		print_findfamily_link("famid");
		print "\n</td></tr>";
		print "\n<tr><td class=\"topbottombar\" colspan=\"2\">";
		print "<input type=\"submit\" value=\"".$gm_lang["set_link"]."\" />\n";
		print "\n</td></tr>";
		print "</table>\n";
		print "</form>\n";
		break;
	// NOTE: linkfamaction done
	case "linkfamaction":
		// NOTE: Get a change_id
		$change_id = get_new_xref("CHANGE");

		// NOTE: Check if there are changes present, if not get the record otherwise the changed record
		if (!change_present($famid,true)) $famrec = find_gedcom_record($famid);
		else $famrec = change_present($famid);
		$famrec = trim($famrec)."\r\n";
		
		if (!empty($famrec)) {
			$itag = "FAMC";
			if ($famtag=="HUSB" || $famtag=="WIFE") $itag="FAMS";
			
			//-- if it is adding a new child to a family
			if ($famtag=="CHIL") {
				if (preg_match("/1 $famtag @$pid@/", $famrec)==0) {
					// NOTE: Notify the session of change
					$_SESSION["changes"]["INDI"] = true;
					$newrec = "1 $famtag @$pid@\r\n";
					replace_gedrec($famid, "", $newrec, $famtag, $change_id, $change_type);
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
					print $factarray[$famtag].": ".PrintReady(get_person_name($spid, $famrec));
				}
				else {
					// NOTE: Update the individual record for the person
					if (preg_match("/1 $itag @$famid@/", $gedrec)==0) {
						// NOTE: Notify the session of change
						$_SESSION["changes"]["INDI"] = true;
						$newrec = "1 $itag @$famid@\r\n";
						replace_gedrec($pid, "", $newrec, $itag, $change_id, $change_type);
					}
					
					// NOTE: Notify the session of change
					$_SESSION["changes"]["FAM"] = true;
					$famrec .= "1 $famtag @$pid@\r\n";
					replace_gedrec($famid, "", $famrec, $famtag, $change_id, $change_type);
				}
			}
		}
		else print $gm_lang["family_not_found"];
		break;	
		
		
	// NOTE: addname done
	case "addname":
		print_indi_form("update", "", "new", "NEW");
		break;
	// NOTE: addnewparent done
	case "addnewparent":
		print_indi_form("addnewparentaction", $famid, "", "", $famtag);
		break;
		
	// NOTE: addnewparentaction done
	case "addnewparentaction":
		$change_id = get_new_xref("CHANGE");
		
		$newrec = "0 @REF@ INDI\r\n1 NAME ".trim($NAME)."\r\n";
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
				$BIRT_DATE = check_input_date($BIRT_DATE);
				$newrec .= "2 DATE $BIRT_DATE\r\n";
			}
			if (!empty($BIRT_PLAC)) $newrec .= "2 PLAC $BIRT_PLAC\r\n";
		}
		if ((!empty($DEAT_DATE))||(!empty($DEAT_PLAC))) {
			$newrec .= "1 DEAT\r\n";
			if (!empty($DEAT_DATE)) {
				$DEAT_DATE = check_input_date($DEAT_DATE);
				$newrec .= "2 DATE $DEAT_DATE\r\n";
			}
			if (!empty($DEAT_PLAC)) $newrec .= "2 PLAC $DEAT_PLAC\r\n";
		}
		$newrec = handle_updates($newrec);
		// NOTE: Inform session of change
		$_SESSION["changes"]["INDI"] = true;
		// NOTE: Save the new indi record and get the new ID
		$xref = append_gedrec($newrec, "INDI", $change_id, $change_type);
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
			$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
		}
		else if (!empty($famid)) {
			$famrec = "";
			$famrec = find_gedcom_record($famid);
			if (!empty($famrec)) {
				$newrec = "1 $famtag @$xref@\r\n";
				$_SESSION["changes"]["FAM"] = true;
				replace_gedrec($famid, "", $newrec, $famtag, $change_id, $change_type);
			}
		}
		if ((!empty($famid))&&($famid != "new")) {
				$newrec = "1 FAMS @$famid@\r\n";
				$_SESSION["changes"]["INDI"] = true;
				replace_gedrec($xref, "", $newrec, "FAMS", $change_id, $change_type);
		}
		if (!empty($pid)) {
			if ($gedrec) {
				$ct = preg_match("/1 FAMC @$famid@/", $gedrec);
				if ($ct==0) {
					$newrec = "1 FAMC @$famid@\r\n";
					$_SESSION["changes"]["INDI"] = true;
					replace_gedrec($pid, "", $newrec, $fact, $change_id, $change_type);
				}
			}
		}
		break;
	// NOTE: add new source
	case "addnewsource":
		?>
		<script type="text/javascript">
		<!--
			function check_form(frm) {
				if (frm.TITL.value=="") {
					alert('<?php print $gm_lang["must_provide"].$factarray["TITL"]; ?>');
					frm.TITL.focus();
					return false;
				}
				return true;
			}
		//-->
		</script>
		<b><?php print $gm_lang["create_source"];
		$tabkey = 1;
		 ?></b>
		<form method="post" action="edit_interface.php" onSubmit="return check_form(this);">
			<input type="hidden" name="action" value="addsourceaction" />
			<input type="hidden" name="pid" value="newsour" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="facts_table">
				<tr><td class="shade2"><?php print $factarray["ABBR"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="ABBR" id="ABBR" value="" size="40" maxlength="255" /> <?PHP print_specialchar_link("ABBR",false); ?></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["TITL"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="TITL" id="TITL" value="" size="60" /> <?PHP print_specialchar_link("TITL",false); ?></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["AUTH"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="AUTH" id="AUTH" value="" size="40" maxlength="255" /> <?PHP print_specialchar_link("AUTH",false); ?></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["PUBL"]; ?></td>
				<td class="shade1"><?PHP print_specialchar_link("PUBL",true); ?> <textarea tabindex="<?php print $tabkey; ?>" name="PUBL" id="PUBL" rows="5" cols="60"></textarea></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["REPO"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="REPO" id="REPO" value="" size="<?php print (strlen($REPO_ID_PREFIX) + 4); ?>" /> <?PHP print_findrepository_link("REPO"); print_addnewrepository_link("REPO"); ?></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["CALN"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="CALN" id="CALN" value="" /></td></tr>
			<tr><td class="topbottombar" colspan="2">
			<input type="submit" value="<?php print $gm_lang["create_source"]; ?>" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
		
	
		
		
	// NOTE: create a source record from the incoming variables done
	case "addsourceaction":
		$change_id = get_new_xref("CHANGE");
		
		$newgedrec = "0 @XREF@ SOUR\r\n";
		if (!empty($ABBR)) $newgedrec .= "1 ABBR $ABBR\r\n";
		if (!empty($TITL)) $newgedrec .= "1 TITL $TITL\r\n";
		if (!empty($AUTH)) $newgedrec .= "1 AUTH $AUTH\r\n";
		if (!empty($PUBL)) $newgedrec .= "1 PUBL $PUBL\r\n";
		if (!empty($REPO)) {
			$newgedrec .= "1 REPO @$REPO@\r\n";
			if (!empty($CALN)) $newgedrec .= "2 CALN $CALN\r\n";
		}
		$newlines = preg_split("/\r?\n/", $newgedrec);
		$newged = $newlines[0]."\r\n";
		for($k=1; $k<count($newlines); $k++) {
			if (((preg_match("/\d .... .*/", $newlines[$k])==0) and strlen($newlines[$k])!=0)) $newlines[$k] = "2 CONT ".$newlines[$k];
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
		$_SESSION["changes"]["SOUR"] = true;
		$xref = append_gedrec($newged, "SOUR", $change_id, $change_type);
		if ($xref) {
			print "<br /><br />\n".$gm_lang["new_source_created"]."<br /><br />";
			print "<a href=\"javascript:// SOUR $xref\" onclick=\"openerpasteid('$xref'); return false;\">".$gm_lang["paste_id_into_field"]." <b>$xref</b></a>\n";
		}
		break;
		
		//-- print a form to edit the raw gedcom record in a large textarea
	// NOTE: add new repository done
	case "addnewrepository":
		?>
		<script type="text/javascript">
		<!--
			function check_form(frm) {
				if (frm.NAME.value=="") {
					alert('<?php print $gm_lang["must_provide"]." ".$factarray["NAME"]; ?>');
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
		<form method="post" action="edit_interface.php" onSubmit="return check_form(this);">
			<input type="hidden" name="action" value="addrepoaction" />
			<input type="hidden" name="pid" value="newrepo" />
			<input type="hidden" name="change_type" value="<?php print $change_type; ?>" />
			<table class="facts_table">
				<tr><td class="shade2"><?php print $factarray["NAME"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="NAME" id="NAME" value="" size="40" maxlength="255" /> <?PHP print_specialchar_link("NAME",false); ?></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["ADDR"]; ?></td>
				<td class="shade1"><textarea tabindex="<?php print $tabkey; ?>" name="ADDR" id="ADDR" rows="5" cols="60"></textarea><?PHP print_specialchar_link("ADDR",true); ?> </td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["PHON"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="PHON" id="PHON" value="" size="40" maxlength="255" /> </td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["FAX"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="FAX" id="FAX" value="" size="40" /></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["EMAIL"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="EMAIL" id="EMAIL" value="" size="40" maxlength="255" /></td></tr>
				<?php $tabkey++; ?>
				<tr><td class="shade2"><?php print $factarray["WWW"]; ?></td>
				<td class="shade1"><input tabindex="<?php print $tabkey; ?>" type="text" name="WWW" id="WWW" value="" size="40" maxlength="255" /> </td></tr>
			<tr><td class="topbottombar" colspan="2">
			<input type="submit" value="<?php print $gm_lang["create_repository"]; ?>" />
			</td></tr>
			</table>
		</form>
		<?php
		break;
	// NOTE: create a repository record from the incoming variables done
	case "addrepoaction":
		$change_id = get_new_xref("CHANGE");
		
		$newgedrec = "0 @XREF@ REPO\r\n";
		if (!empty($NAME)) $newgedrec .= "1 NAME $NAME\r\n";
		if (!empty($ADDR)) $newgedrec .= "1 ADDR $ADDR\r\n";
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
		$_SESSION["changes"]["REPO"] = true;
		$xref = append_gedrec($newged, "REPO", $change_id, $change_type);
		if ($xref) {
			print "<br /><br />\n".$gm_lang["new_repo_created"]."<br /><br />";
			print "<a href=\"javascript:// REPO $xref\" onclick=\"openerpasteid('$xref'); return false;\">".$gm_lang["paste_rid_into_field"]." <b>$xref</b></a>\n";
		}
		break;
		
		
		
		
		
		
		
		
		
		
		
	
	// NOTE: Edit
	// NOTE: edit done
	case "edit":
		init_calendar_popup();
		print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"update\" />\n";
		print "<input type=\"hidden\" name=\"fact\" value=\"$fact\" />\n";
		print "<input type=\"hidden\" name=\"count\" value=\"$count\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"$change_type\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
		print "<table class=\"facts_table\">";
		if ($fact == "OBJE") show_media_form($pid, 'updatemedia', 'edit_media');
		else if ($fact == "BIRT") {
			 print_form($pid, $fact);
			
			 // NOTE: End of form
			print "</table>\n";
			print "</form>\n";
		}
		else {
			$oldrec = get_sub_record(1, "1 $fact", $gedrec, $count);
			$gedlines = split("\r\n", $oldrec);	// -- find the number of lines in the record
			$fields = preg_split("/\s/", $gedlines[0]);
			// glevel is always 1
			// $glevel = $fields[0];
			// $level = $glevel;
			$glevel = 1;
			$level = 1;
			// type is the same as fact
			// $type = trim($fields[1]);
			// $level1type = $type;
			if (count($fields)>2) {
				$ct = preg_match("/@.*@/",$fields[2]);
				$levellink = $ct > 0;
			}
			else $levellink = false;
			$tags=array();
			
			// we don't use linenum
			// linenum is always 1
			// $i = $linenum;
			
			// if ($change_type == "edit_gender") add_simple_tag(get_sub_record(1, "1 SEX", $gedrec));
			
			$i = 0;
			// Loop on existing tags :
			do {
				$text = "";
				// NOTE: This retrieves the data for each fact
				for($j=2; $j<count($fields); $j++) {
					if ($j>2) $text .= " ";
					$text .= $fields[$j];
				}
				$iscont = false;
				while(($i+1<count($gedlines))&&(preg_match("/".($level+1)." (CON[CT])\s?(.*)/", $gedlines[$i+1], $cmatch)>0)) {
					$iscont=true;
					if ($cmatch[1]=="CONT") $text.="\n";
					else if ($WORD_WRAPPED_NOTES) $text .= " ";
					$text .= $cmatch[2];
					$i++;
				}
				$text = trim($text);
				$tags[]=$fact;
				add_simple_tag($level." ".$fact." ".$text);
				if ($fact=="DATE" && !strpos(@$gedlines[$i+1], " TIME")) add_simple_tag(($level+1)." TIME");
				if ($fact=="MARR" && !strpos(@$gedlines[$i+1], " TYPE")) add_simple_tag(($level+1)." TYPE");
		
				$i++;
				if (isset($gedlines[$i])) {
					$fields = preg_split("/\s/", trim($gedlines[$i]));
					$level = $fields[0];
					if (isset($fields[1])) $fact = trim($fields[1]);
				}
			} while ($i<count($gedlines));
			// Now add some missing tags :
			if (in_array($tags[0], $templefacts)) {
				// 2 TEMP
				if (!in_array("TEMP", $tags)) add_simple_tag("2 TEMP");
				// 2 STAT
				if (!in_array("STAT", $tags)) add_simple_tag("2 STAT");
			}
			if ($fact=="GRAD") {
				// 1 GRAD
				// 2 TYPE
				if (!in_array("TYPE", $tags)) add_simple_tag("2 TYPE");
			}
			if ($fact=="EDUC" or $fact=="GRAD" or $fact=="OCCU") {
				// 1 EDUC|GRAD|OCCU
				// 2 CORP
				if (!in_array("CORP", $tags)) add_simple_tag("2 CORP");
			}
			if ($fact=="DEAT") {
				// 1 DEAT
				// 2 CAUS
				if (!in_array("CAUS", $tags)) add_simple_tag("2 CAUS");
			}
			if ($fact=="SOUR") {
				// 1 SOUR
				// 2 PAGE
				// 2 DATA
				// 3 TEXT
				if (!in_array("PAGE", $tags)) add_simple_tag("2 PAGE");
				if (!in_array("TEXT", $tags)) add_simple_tag("3 TEXT");
			}
			if ($fact=="REPO") {
				//1 REPO
				//2 CALN
				if (!in_array("CALN", $tags)) add_simple_tag("2 CALN");
			}
			if (!in_array($fact, $nondatefacts)) {
				// 2 DATE
				// 3 TIME
				if (!in_array("DATE", $tags)) {
					add_simple_tag("2 DATE");
					add_simple_tag("3 TIME");
				}
				// 2 PLAC
				if (!in_array("PLAC", $tags) && !in_array($fact, $nonplacfacts) && !in_array("TEMP", $tags)) add_simple_tag("2 PLAC");
			}
			if ($fact=="BURI") {
				// 1 BURI
				// 2 CEME
				if (!in_array("CEME", $tags)) add_simple_tag("2 CEME");
			}
			if ($fact=="BIRT" or $fact=="DEAT"
			or $fact=="EDUC" or $fact=="GRAD"
			or $fact=="OCCU" or $fact=="ORDN" or $fact=="RESI") {
				// 1 BIRT|DEAT|EDUC|GRAD|ORDN|RESI
				// 2 ADDR
				if (!in_array("ADDR", $tags)) add_simple_tag("2 ADDR");
			}
			if ($fact=="OCCU" or $fact=="RESI") {
				// 1 OCCU|RESI
				// 2 PHON|FAX|EMAIL|URL
				if (!in_array("PHON", $tags)) add_simple_tag("2 PHON");
				if (!in_array("FAX", $tags)) add_simple_tag("2 FAX");
				if (!in_array("EMAIL", $tags)) add_simple_tag("2 EMAIL");
				if (!in_array("URL", $tags)) add_simple_tag("2 URL");
			}
			if ($fact=="OBJE") {
				show_media_form($pid);
				// 1 OBJE
		
				if (!$levellink) {
					// 2 FORM
					if (!in_array("FORM", $tags)) add_simple_tag("2 FORM");
					// 2 FILE
					if (!in_array("FILE", $tags)) add_simple_tag("2 FILE");
					// 2 TITL
					if (!in_array("TITL", $tags)) add_simple_tag("2 TITL");
				}
				// 2 _PRIM
				if (!in_array("_PRIM", $tags)) add_simple_tag("2 _PRIM");
				// 2 _THUM
				if (!in_array("_THUM", $tags)) add_simple_tag("2 _THUM");
			}
			
			// 2 RESN
			if (!in_array("RESN", $tags)) add_simple_tag("2 RESN");
			print "</table>";
		
			if ($fact != "SEX") {
				if ($fact!="ASSO"  && $fact!="REPO") print_add_layer("ASSO");
				if ($fact!="SOUR"  && $fact!="REPO") print_add_layer("SOUR");
				if ($fact!="NOTE") print_add_layer("NOTE");
				if ($fact!="OBJE"  && $fact!="REPO" && $fact!="NOTE" && $MULTI_MEDIA) print_add_layer("OBJE");
			}
			print "<br /><input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
			print "</form>\n";
		}
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
			print_help_link("edit_edit_raw_help", "qm");
			print "<b>".$gm_lang["edit_raw"]."</b>";
			print "<form method=\"post\" action=\"edit_interface.php\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"updateraw\" />\n";
			print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
			print "<input type=\"hidden\" name=\"change_type\" value=\"$change_type\" />\n";
			print_specialchar_link("newgedrec",true);
			print "<textarea name=\"newgedrec\" id=\"newgedrec\" rows=\"20\" cols=\"82\" dir=\"ltr\">".$gedrec."</textarea>\n<br />";
			print "<input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
			print "</form>\n";
		}
		break;
		//-- edit a fact record in a form
	// NOTE: updateraw done
	case "updateraw":
		$change_id = get_new_xref("CHANGE");
		
		$newrec = trim($newgedrec);
		$success = (!empty($newrec)&&(replace_gedrec($pid, "", $newrec, "", $change_id, $change_type)));
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
	// NOTE editname done
	case "editname":
		$namerecnew = get_sub_record(1, "1 NAME", find_gedcom_record($pid), $count);
		print_indi_form("update", "", "", $namerecnew);
		break;
		
	
	// NOTE: Copy
	// NOTE: copy done
	case "copy":
		$factrec = get_sub_record(1, "1 $fact", $gedrec, $count);
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
		print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"linkspouseaction\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"famid\" value=\"new\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<table class=\"facts_table\">";
		print "<tr><td class=\"shade2\">";
		if ($famtag=="WIFE") print $gm_lang["wife"];
		else print $gm_lang["husband"];
		print "</td>";
		print "<td class=\"shade1\"><input id=\"spouseid\" type=\"text\" name=\"spid\" size=\"8\" /> ";
		print_findindi_link("spouseid", "");
		print "\n</td></tr>";
		add_simple_tag("0 MARR");
		add_simple_tag("0 DATE", "MARR");
		add_simple_tag("0 PLAC", "MARR");
		print "<tr><td class=\"topbottombar\" colspan=\"2\">";
		print "<input type=\"submit\" value=\"".$gm_lang["set_link"]."\" />\n";
		print "</td></tr>";
		print "</table>\n";
		print "</form>\n";
		break;
	
	
	
	
	
	
		
		
	
	// NOTE: linkspouseaction done
	case "linkspouseaction":
		// NOTE: Get a change_id
		$change_id = get_new_xref("CHANGE");
		
		if (!empty($spid)) {
			// NOTE: Check if the relation doesn't exist yet
			$sql = "SELECT f_id FROM ".$TBLPREFIX."families WHERE f_husb = ";
			if ($famtag == "HUSB") $sql .= "'".$spid."' AND f_wife = '".$pid."'";
			else if ($famtag == "WIFE") $sql .= "'".$pid."' AND f_wife = '".$spid."'";
			$res = dbquery($sql);
			if (count($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) > 0) {
				print $gm_lang["family_exists"];
				print "<br />";
				print $gm_lang["family"].": ".$row["f_id"];
			}
			else {
				// NOTE: Check if there are changes present, if not get the record otherwise the changed record
				if (!change_present($spid,true)) $gedrec = find_gedcom_record($spid);
				else $gedrec = change_present($spid);
				$gedrec = trim($gedrec);
				if (!empty($gedrec)) {
					// NOTE: Create a new family record
					if ($famid=="new") {
						// NOTE: Notify session of change
						$_SESSION["changes"]["FAM"] = true;
						// NOTE: Create the new family record
						$famrec = "0 @new@ FAM\r\n";
						$SEX = get_gedcom_value("SEX", 1, $gedrec, '', false);
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
								$MARR_DATE = check_input_date($MARR_DATE);
								$famrec .= "2 DATE $MARR_DATE\r\n";
							}
							if (!empty($MARR_PLAC)) $famrec .= "2 PLAC $MARR_PLAC\r\n";
						}
						$famid = append_gedrec($famrec, "FAM", $change_id, $change_type);
					}
					if ((!empty($famid)) && ($famid != "new")) {
						// NOTE: Notify the session of change
						$_SESSION["changes"]["INDI"] = true;
						// NOTE: Add the new FAM ID to the spouse record
						$newrec = "1 FAMS @$famid@\r\n";
						replace_gedrec($spid, "", $newrec, "FAMS", $change_id, $change_type);
					}
					if (!empty($pid)) {
						// NOTE: Notify the session of change
						$_SESSION["changes"]["INDI"] = true;
						// NOTE: Add the new FAM ID to the active person record
						$newrec = "1 FAMS @$famid@\r\n";
						replace_gedrec($pid, "", $newrec, "FAMS", $change_id, $change_type);
					}
				}
				if ($famtag == "HUSB") print $gm_lang["husband_added"];
				else if ($famtag == "WIFE") print $gm_lang["wife_added"];
			}
		}
		break;
	
	// NOTE updatemedia done
	case "updatemedia":
		$change_id = get_new_xref("CHANGE");
		
		// NOTE: Check for uploaded files
		if (count($_FILES)>0) {
			$uploaded_files = array();
			$upload_errors = array($gm_lang["file_success"], $gm_lang["file_too_big"], $gm_lang["file_too_big"],$gm_lang["file_partial"], $gm_lang["file_missing"]);
			if (substr($folder,0,1) == "/") $folder = substr($folder,1);
			if (substr($folder,-1,1) != "/") $folder .= "/";
			foreach($_FILES as $upload) {
				if (!empty($upload['tmp_name'])) {
					if (!move_uploaded_file($upload['tmp_name'], $MEDIA_DIRECTORY.$folder.basename($upload['name']))) {
						$error .= "<br />".$gm_lang["upload_error"]."<br />".$upload_errors[$upload['error']];
						$uploaded_files[] = "";
					}
					else {
						$filename = $MEDIA_DIRECTORY.$folder.basename($upload['name']);
						$uploaded_files[] = $MEDIA_DIRECTORY.$folder.basename($upload['name']);
						if (!is_dir($MEDIA_DIRECTORY.$folder."thumbs")) mkdir($MEDIA_DIRECTORY.$folder."thumbs");
						$thumbnail = $MEDIA_DIRECTORY.$folder."thumbs/".basename($upload['name']);
						generate_thumbnail($filename, $thumbnail);
						if (!empty($error)) {
							print "<span class=\"error\">".$error."</span>";
						}
					}
				}
				else $uploaded_files[] = "";
			}
		}
		
		// NOTE: Build the new record
		$newrec = "0 @$pid@ OBJE\r\n";
		$newrec = handle_updates($newrec);
		//NOTE: Add the change tag
		$newrec = check_gedcom($newrec);
		
		// NOTE: Store the change in the database
		$success = (replace_gedrec($pid, $gedrec, $newrec, $fact, $change_id, $change_type));
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
	
	// NOTE: reconstruct the gedcom from the incoming fields and store it in the file done
	case "update":
		$change_id = get_new_xref("CHANGE");
		
		// add or remove Y
		if ($text[0]=="Y" or $text[0]=="y") $text[0]="";
		if (in_array($tag[0], $emptyfacts) && array_unique($text)==array("") && !$islink[0]) $text[0]="Y";
		//-- check for photo update
		if (count($_FILES)>0) {
			$uploaded_files = array();
			$upload_errors = array($gm_lang["file_success"], $gm_lang["file_too_big"], $gm_lang["file_too_big"],$gm_lang["file_partial"], $gm_lang["file_missing"]);
			if (substr($folder,0,1) == "/") $folder = substr($folder,1);
			if (substr($folder,-1,1) != "/") $folder .= "/";
			foreach($_FILES as $upload) {
				if (!empty($upload['tmp_name'])) {
					if (!move_uploaded_file($upload['tmp_name'], $MEDIA_DIRECTORY.$folder.basename($upload['name']))) {
						$error .= "<br />".$gm_lang["upload_error"]."<br />".$upload_errors[$upload['error']];
						$uploaded_files[] = "";
					}
					else {
						$filename = $MEDIA_DIRECTORY.$folder.basename($upload['name']);
						$uploaded_files[] = $MEDIA_DIRECTORY.$folder.basename($upload['name']);
						if (!is_dir($MEDIA_DIRECTORY.$folder."thumbs")) mkdir($MEDIA_DIRECTORY.$folder."thumbs");
						$thumbnail = $MEDIA_DIRECTORY.$folder."thumbs/".basename($upload['name']);
						generate_thumbnail($filename, $thumbnail);
						if (!empty($error)) {
							print "<span class=\"error\">".$error."</span>";
						}
					}
				}
				else $uploaded_files[] = "";
			}
		}
		
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
		if (!isset($newrec)) $newrec = "1 ".$fact."\r\n";
		$newrec = handle_updates($newrec);
		
		if (!isset($count)) $count = 1;
		if (!isset($change_type)) $change_type = "unknown";
		
		// NOTE: Get old record only when we are editing a record
		$addfacts = array("newfact", "add_note", "add_source");
		if (in_array($change_type, $addfacts)) $oldrec = "";
		else if ((!isset($tag) || $change_type == "edit_name") && !empty($NAME)) $oldrec = get_sub_record(1, "1 NAME", $gedrec, $count);
		else $oldrec = get_sub_record(1, "1 ".$fact, $gedrec, $count);
		
		$success = (replace_gedrec($pid, $oldrec, $newrec, $fact, $change_id, $change_type));
		if ($success) print "<br /><br />".$gm_lang["update_successful"];
		break;
	
}

// autoclose window when update successful
if ($success and $EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nedit_close();\n//-->\n</script>";

print "<div class=\"center\"><a href=\"javascript:// ".$gm_lang["close_window"]."\" onclick=\"edit_close();\">".$gm_lang["close_window"]."</a></div><br />\n";
print_simple_footer();
?>
