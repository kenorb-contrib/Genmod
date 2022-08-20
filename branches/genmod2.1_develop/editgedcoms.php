<?php
/**
 * UI for online updating of the config file.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @see index/gedcoms.php
 * @version $Id: editgedcoms.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

global $TEXT_DIRECTION;

if (!isset($action)) $action="";
if (!isset($ged)) $ged = "";
$message = "";

// NOTE: make sure that they have admin status before they can use this page
// NOTE: otherwise have them login again

if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=editgedcoms.php");
	else header("Location: ".LOGIN_URL."?url=editgedcoms.php");
	exit;
}
PrintHeader(GM_LANG_gedcom_adm_head);

if ($action=="delete") {
	if (isset($GEDCOMS[$delged])) {
		PrivacyController::DeletePrivacy($delged);
		AdminFunctions::DeleteGedcom($delged);
		unset($GEDCOMS[$delged]);
		AdminFunctions::StoreGedcoms();
		GedcomConfig::DeleteGedcomConfig($delged);
		if (isset($_SESSION["GEDCOMID"]) && $_SESSION["GEDCOMID"] == $delged) $_SESSION["GEDCOMID"] = $DEFAULT_GEDCOMID;
		$message = str_replace("#GED#", $delged, GM_LANG_gedcom_deleted)."\n";
	}
	else $message =  "<span class=\"Error\">".GM_LANG_gedcom_not_exist."</span>";
}

if (($action == "setdefault") && isset($default_ged)) {
	$DEFAULT_GEDCOMID = $_POST["default_ged"];
	AdminFunctions::StoreGedcoms();
}

if ($action == "deletecount") {
	$sql = "DELETE FROM ".TBLPREFIX."counters WHERE c_id LIKE '%[".$GEDCOMS[$delged]["id"]."]%'";
	$res = NewQuery($sql);
	unset($_SESSION[$delged."gm_counter"]);
}
?>
<!-- Setup the left box -->
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	<?php if ($gm_user->userIsAdmin()) { ?>
		<?php AdminFunctions::AdminLink("editconfig_gedcom.php?source=add_form", GM_LANG_add_gedcom, "add_gedcom_help", "qm", "add_gedcom"); ?>
		<?php AdminFunctions::AdminLink("editconfig_gedcom.php?source=upload_form", GM_LANG_upload_gedcom, "upload_gedcom_help", "qm", "upload_gedcom"); ?>
		<?php AdminFunctions::AdminLink("editconfig_gedcom.php?source=add_new_form", GM_LANG_add_new_gedcom, "add_new_gedcom_help", "qm", "add_new_gedcom"); ?>
		<?php AdminFunctions::AdminLink("uploadgedcom.php?action=merge_form", GM_LANG_merge_gedcom, "merge_gedcom_help", "qm", "merge_gedcom"); ?>
	<?php } ?>
</div>
	
<!-- Setup the middle box -->
<div id="AdminColumnMiddle">
	<form name="defaultform" method="post" action="editgedcoms.php">
	<input type="hidden" name="action" value="setdefault" />
	<table class="NavBlockTable AdminNavBlockTable">
	<tr><td class="NavBlockHeader AdminNavBlockHeader" colspan="5">
		<?php print "<span class=\"AdminNavBlockTitle\">".GM_LANG_current_gedcoms."</span>"; 
		if (!empty($message)) print "<br />".$message;
		?>
	</td></tr>
		<tr><td class="NavBlockLabel EditGedcomsAdminNavBlockLabel" colspan="5">
			<?php
			// Default gedcom choice
			if (count($GEDCOMS)>0) {
				if ($gm_user->userIsAdmin()) {
					PrintHelpLink("default_gedcom_help", "qm");
					print GM_LANG_DEFAULT_GEDCOM."&nbsp;";
					print "<select name=\"default_ged\" onchange=\"document.defaultform.submit();\">";
					foreach($GEDCOMS as $gedc => $gedarray) {
						if (empty($DEFAULT_GEDCOMID)) $DEFAULT_GEDCOM = $gedc;
						print "<option value=\"".$gedc."\"";
						if ($DEFAULT_GEDCOMID == $gedc) print " selected=\"selected\"";
						print " onclick=\"document.defaultform.submit();\">";
						print PrintReady($gedarray["title"])."</option>";
					}
					print "</select>";
				}
			}
			?>
		</td></tr>
	<?php
	
	$current_ged = GedcomConfig::$GEDCOMID;
	$GedCount = 0;
	// Print the table of available GEDCOMs
	if (count($GEDCOMS)>0) {
		foreach($GEDCOMS as $gedc=>$gedarray) {
			if ($gm_user->userGedcomAdmin($gedc)) {
				if (empty($DEFAULT_GEDCOMID)) $DEFAULT_GEDCOMID = $gedc;
				
				// Row 0: Separator line
				print "<tr>";
				print "<td colspan=\"5\" class=\"NavBlockRowSpacer\">&nbsp;";
				print "</td>";
				print "</tr>";
				$GedCount++;
	
				// Row 1: Title
				print "<tr>";
				print "<td colspan=\"5\" class=\"NavBlockHeader\">";
				if ($DEFAULT_GEDCOMID == $gedc) print PrintReady($gedarray["title"])."</td>";
				else print PrintReady($gedarray["title"])."</td>";
				print "</tr>";
				
				// Row 2: Column headings
				print "<tr>";
				print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_id.": ".$gedarray["id"]."</td>";
				print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">";
				if (file_exists($gedarray["path"])) {
					if ($TEXT_DIRECTION=="ltr") print $gedarray["path"]." (";
					else print $gedarray["path"]." &rlm;(";
					printf("%.2fKb", (filesize($gedarray["path"])/1024));
					print ")";
					$url = AdminFunctions::CheckGedcomDownloadable($gedarray["path"]);
					if ($url!==false) {
						print "<br /><span class=\"Error\">".GM_LANG_gedcom_downloadable." :</span>";
						print "<br /><a href=\"$url\">$url</a>";
					}
				}
				else print "<span class=\"Error\">".GM_LANG_file_not_found."</span>";
				print "</td>";
				print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_edit."</td>";
				print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_view."</td>";
				print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_delete."</td>";
				print "</tr>";
				
				// Row 3: Options
				$imported = CheckForImport($gedc);
				print "<tr>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">&nbsp;</td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">";
				if (file_exists($gedarray["path"])) {
					print "<a href=\"uploadgedcom.php?gedcomid=".$gedc."&amp;verify=verify_gedcom&amp;action=add_form&amp;import_existing=1\">".GM_LANG_ged_import."</a>";
					if (!$imported) {
						print "<br /><span class=\"Error\">".GM_LANG_gedcom_not_imported."</span>";
					}
				}
				else print "&nbsp;";
				print "</td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\"><a href=\"editconfig_gedcom.php?gedid=".$gedc."\">".GM_LANG_ged_config."</a></td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\"><a href=\"javascript: ".GM_LANG_view_searchlog."\" onclick=\"window.open('viewlog.php?cat=F&amp;gedid=".$gedarray["id"]."', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">";
				if (AdminFunctions::NewLogRecs("F", $gedc)) print "<span class=\"Error\">".GM_LANG_view_searchlog."</span>";
				else print GM_LANG_view_searchlog;
				print "</a></td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\"><a href=\"editgedcoms.php?action=delete&amp;delged=".$gedc."\" onclick=\"return confirm('".GM_LANG_confirm_gedcom_delete." ".preg_replace("/'/", "\'", get_gedcom_from_id($gedc))."?');\">".GM_LANG_ged_gedcom."</a></td>";
				print "</tr>";
				
				// Row 4: Options
				print "<tr>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">&nbsp;</td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">";
				if ($imported) print "<a href=\"downloadgedcom.php?gedid=$gedc\">".GM_LANG_ged_download."</a>";
				else print "&nbsp;";
				print "</td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\"><a href=\"edit_privacy.php?action=edit&amp;gedid=".$gedc."\">".GM_LANG_ged_privacy."</a></td>";
	
			  print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\"><a href=\"javascript: ".GM_LANG_view_gedlog."\" onclick=\"window.open('viewlog.php?cat=G&amp;gedid=".$gedarray["id"]."', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); ChangeClass('gedlog".$GedCount."', ''); return false; \">";
			  if (AdminFunctions::NewLogRecs("G", $gedc)) print "<span id=\"gedlog".$GedCount."\" class=\"Error\">".GM_LANG_view_gedlog."</span>";
			  else print "<span id=\"gedlog".$GedCount."\">".GM_LANG_view_gedlog."</span>";
				print "</a></td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\"><a href=\"editgedcoms.php?action=deletecount&amp;delged=".$gedc."\" onclick=\"return confirm('".GM_LANG_confirm_count_delete." ".preg_replace("/'/", "\'", get_gedcom_from_id($gedc))."?');\">".GM_LANG_counters."</a></td>";
				print "</tr>";
				
				// Row 5: Options
				print "<tr>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">&nbsp;</td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">";
				print "<a href=\"editconfig_gedcom.php?source=reupload_form&amp;gedid=$gedc\">".GM_LANG_ged_reupload."</a>";
				print "</td>";
				print "<td class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\"><a href=\"javascript: ".GM_LANG_submitter_record."\" onclick=\"window.open('edit_interface.php?action=submitter&amp;gedfile=".$gedc."','','width=800,height=600,resizable=1,scrollbars=1'); return false;\">".GM_LANG_submitter_record."</a></td>";
				print "<td  class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">&nbsp;</td>";
				print "<td  class=\"NavBlockLabel EditGedcomsAdminNavBlockLabel\">&nbsp;</td>";
				print "</tr>";
			}
		}
	}
	if (isset($GEDCOMS[$current_ged])) SwitchGedcom($GEDCOMS[$current_ged]["gedcom"]);
	
	print "</table></form>";
print "</div>";

PrintFooter();

?>
