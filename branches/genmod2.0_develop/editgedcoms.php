<?php
/**
 * UI for online updating of the config file.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @see index/gedcoms.php
 * @version $Id: editgedcoms.php,v 1.39 2008/08/03 04:29:47 sjouke Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

global $TEXT_DIRECTION;

if (!isset($action)) $action="";
if (!isset($ged)) $ged = "";

// NOTE: make sure that they have admin status before they can use this page
// NOTE: otherwise have them login again
$username = $gm_username;
if (!$Users->userGedcomAdmin($username)) {
	if (empty($LOGIN_URL)) header("Location: login.php?url=editgedcoms.php");
	else header("Location: ".$LOGIN_URL."?url=editgedcoms.php");
	exit;
}
print_header($gm_lang["gedcom_adm_head"]);
print "<center>\n";
if ($action=="delete") {
	if (isset($GEDCOMS[$delged])) {
		$Privacy->DeletePrivacy($GEDCOMS[$delged]["id"]);
		DeleteGedcom($delged);
		unset($GEDCOMS[$delged]);
		StoreGedcoms();
		$GedcomConfig->DeleteGedcomConfig($delged);
		print "<br />".str_replace("#GED#", $delged, $gm_lang["gedcom_deleted"])."<br />\n";
	}
	else print "<br /><span class=\"error\">".$gm_lang["gedcom_not_exist"]."</span>";
}

if (($action=="setdefault") && isset($default_ged)) {
	$DEFAULT_GEDCOM = urldecode($_POST["default_ged"]);
	StoreGedcoms();
}

if ($action == "deletecount") {
	$sql = "DELETE FROM ".$TBLPREFIX."counters WHERE c_id LIKE '%[".$GEDCOMS[$delged]["id"]."]%'";
	$res = NewQuery($sql);
}
?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print $gm_lang["admin"];?></a></div>
	<?php if ($Users->userIsAdmin($username)) { ?>
		<div class="admin_link">
			<?php print_help_link("add_gedcom_help", "qm", "add_gedcom"); ?>
			<a href="editconfig_gedcom.php?source=add_form"><?php print $gm_lang["add_gedcom"];?></a>
		</div>
		<div class="admin_link">
			<?php print_help_link("upload_gedcom_help", "qm", "upload_gedcom"); ?>
			<a href="editconfig_gedcom.php?source=upload_form"><?php print $gm_lang["upload_gedcom"];?></a>
		</div>
		<div class="admin_link">
			<?php print_help_link("add_new_gedcom_help", "qm", "add_new_gedcom"); ?>
			<a href="editconfig_gedcom.php?source=add_new_form"><?php print $gm_lang["add_new_gedcom"];?></a>
		</div>
		<div class="admin_link">
			<?php print_help_link("merge_gedcom_help", "qm", "merge_gedcom"); ?>
			<a href="mergegedcom.php"><?php print $gm_lang["merge_gedcom"];?></a>
		</div>
	<?php } ?>
</div>
	
<!-- Setup the middle box -->
<div id="content">
	<div class="admin_topbottombar">
		<?php print "<h3>".$gm_lang["current_gedcoms"]."</h3>"; ?>
	</div>
	<form name="defaultform" method="post" action="editgedcoms.php">
	<input type="hidden" name="action" value="setdefault" />
		<div class="admin_topbottombar">
			<?php
			// Default gedcom choice
			if (count($GEDCOMS)>0) {
				if ($Users->userIsAdmin($username)) {
					print_help_link("default_gedcom_help", "qm");
					print $gm_lang["DEFAULT_GEDCOM"]."&nbsp;";
					print "<select name=\"default_ged\" class=\"header_select\" onchange=\"document.defaultform.submit();\">";
					foreach($GEDCOMS as $gedc=>$gedarray) {
						if (empty($DEFAULT_GEDCOM)) $DEFAULT_GEDCOM = $gedc;
						print "<option value=\"".urlencode($gedc)."\"";
						if ($DEFAULT_GEDCOM==$gedc) print " selected=\"selected\"";
						print " onclick=\"document.defaultform.submit();\">";
						print PrintReady($gedarray["title"])."</option>";
					}
					print "</select>";
				}
			}
			?>
		</div>
	<?php
	
	$current_ged = $GEDCOM;
	$GedCount = 0;
	// Print the table of available GEDCOMs
	if (count($GEDCOMS)>0) {
		print "<table class=\"gedcom_table\">";
		foreach($GEDCOMS as $gedc=>$gedarray) {
			if ($Users->userGedcomAdmin($username, $gedc)) {
				if (empty($DEFAULT_GEDCOM)) $DEFAULT_GEDCOM = $gedc;
				
				// Row 0: Separator line
				if ($GedCount!=0) {
					print "<tr>";
					print "<td colspan=\"5\">";
					print "<br /><hr class=\"gedcom_table\" /><br />";
					print "</td>";
					print "</tr>";
				}
				$GedCount++;
	
				// Row 1: Title
				print "<tr>";
				print "<td colspan=\"5\" class=\"topbottombar width20\">";
				if ($DEFAULT_GEDCOM==$gedc) print "<span class=\"label\">".PrintReady($gedarray["title"])."</span></td>";
				else print PrintReady($gedarray["title"])."</td>";
				print "</tr>";
				
				// Row 2: Column headings
				print "<tr class=\"shade2\">";
				print "<td>".$gm_lang["id"]."</td>";
				print "<td>".$gm_lang["ged_gedcom"]."</td>";
				print "<td>".$gm_lang["edit"]."</td>";
				print "<td>".$gm_lang["view"]."</td>";
				print "<td>".$gm_lang["delete"]."</td>";
				print "</tr>";
				
				// Row 3: Files
				print "<tr class=\"subbar\">";
				print "<td>".$gedarray["id"]."</td>";
				print "<td>";
				if (file_exists($gedarray["path"])) {
					if ($TEXT_DIRECTION=="ltr") print $gedarray["path"]." (";
					else print $gedarray["path"]." &rlm;(";
					printf("%.2fKb", (filesize($gedarray["path"])/1024));
					print ")";
					$url = CheckGedcomDownloadable($gedarray["path"]);
					if ($url!==false) {
						print "<br />\n";
						print "<span class=\"error\">".$gm_lang["gedcom_downloadable"]." :</span>";
						print "<br /><a href=\"$url\">$url</a>";
					}
				}
				else print "<span class=\"error\">".$gm_lang["file_not_found"]."</span>";
				print "</td>";
				print "<td>&nbsp;</td>";
				print "<td>&nbsp;</td>";
				print "<td>&nbsp;</td>";
				print "</tr>";
				
				// Row 3: Options
				if (CheckForImport($gedc)) $imported = true;
				else $imported = false;
				print "<tr>";
				print "<td>&nbsp;</td>";
				print "<td>";
				if (file_exists($gedarray["path"])) {
					print "<a href=\"uploadgedcom.php?GEDFILENAME=$gedc&amp;verify=verify_gedcom&amp;action=add_form&amp;import_existing=1\">".$gm_lang["ged_import"]."</a>";
					if (!$imported) {
						print "<br /><span class=\"error\">".$gm_lang["gedcom_not_imported"]."</span>";
					}
				}
				else print "&nbsp;";
				print "</td>";
				print "<td><a href=\"editconfig_gedcom.php?ged=".urlencode($gedc)."\">".$gm_lang["ged_config"]."</a></td>";
				print "<td><a href=\"javascript: ".$gm_lang["view_searchlog"]."\" onclick=\"window.open('viewlog.php?cat=F&amp;ged=".urlencode($gedc)."', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">";
				if (NewLogRecs("F", $gedc)) print "<span class=\"error\">".$gm_lang["view_searchlog"]."</span>";
				else print $gm_lang["view_searchlog"];
				print "</a></td>";
				print "<td><a href=\"editgedcoms.php?action=delete&amp;delged=".urlencode($gedc)."\" onclick=\"return confirm('".$gm_lang["confirm_gedcom_delete"]." ".preg_replace("/'/", "\'", $gedc)."?');\">".$gm_lang["ged_gedcom"]."</a></td>";
				print "</tr>";
				
				// Row 4: Options
				print "<tr>";
				print "<td>&nbsp;</td>";
				print "<td>";
				if ($imported) print "<a href=\"downloadgedcom.php?ged=$gedc\">".$gm_lang["ged_download"]."</a>";
				else print "&nbsp;";
				print "</td>";
				print "<td><a href=\"edit_privacy.php?action=edit&amp;ged=".urlencode($gedc)."\">".$gm_lang["ged_privacy"]."</a></td>";
	
			  print "<td><a href=\"javascript: ".$gm_lang["view_gedlog"]."\" onclick=\"window.open('viewlog.php?cat=G&amp;ged=".urlencode($gedc)."', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); ChangeClass('gedlog".$GedCount."', ''); return false; \">";
			  if (NewLogRecs("G", $gedc)) print "<span id=\"gedlog".$GedCount."\" class=\"error\">".$gm_lang["view_gedlog"]."</span>";
			  else print "<span id=\"gedlog".$GedCount."\">".$gm_lang["view_gedlog"]."</span>";
				print "</a></td>";
				print "<td><a href=\"editgedcoms.php?action=deletecount&amp;delged=".urlencode($gedc)."\" onclick=\"return confirm('".$gm_lang["confirm_count_delete"]." ".preg_replace("/'/", "\'", $gedc)."?');\">".$gm_lang["counters"]."</a></td>";
				print "</tr>";
				
				// Row 5: Options
				print "<tr>";
				print "<td>&nbsp;</td>";
				print "<td>";
				print "<a href=\"editconfig_gedcom.php?source=reupload_form&amp;ged=$gedc\">".$gm_lang["ged_reupload"]."</a>";
				print "</td>";
				print "<td><a href=\"javascript: ".$gm_lang["submitter_record"]."\" onclick=\"window.open('edit_interface.php?action=submitter&amp;gedfile=".$gedc."','','width=800,height=600,resizable=1,scrollbars=1'); return false;\">".$gm_lang["submitter_record"]."</a></td>";
				print "<td>&nbsp;</td>";
				print "<td>&nbsp;</td>";
				print "</tr>";
				
				// print "<td valign=\"top\">";		// Column 6  (Create .SLK spreadsheet)
				// if (file_exists("slklist.php")) {
					// print "<a href=\"slklist.php?ged=$gedc\">".$gm_lang["make_slklist"]."</a>";
				// } else {
					// print "&nbsp;";
				// }
				// print "</td>";
				// print "</tr>";
			}
		}
	}
	if (isset($GEDCOMS[$current_ged])) SwitchGedcom($GEDCOMS[$current_ged]["gedcom"]);
	
	print "</table></form>";
	print "</center>";
print "</div>";

print_footer();

?>
