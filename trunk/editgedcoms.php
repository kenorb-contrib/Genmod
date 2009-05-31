<?php
/**
 * UI for online updating of the config file.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @see index/gedcoms.php
 * @version $Id: editgedcoms.php,v 1.13 2006/02/19 11:32:04 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the configuration file
*/
// require $GM_BASE_DIRECTORY.$confighelpfile["english"];
// if (file_exists($GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE];

global $TEXT_DIRECTION;

if (!isset($action)) $action="";
if (!isset($ged)) $ged = "";

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
$username = $gm_username;
if (!userGedcomAdmin($username)) {
	header("Location: login.php?url=editgedcoms.php");
	exit;
}
print_header($gm_lang["gedcom_adm_head"]);
print "<center>\n";
if ($action=="delete") {
	delete_gedcom($delged);
	unset($GEDCOMS[$delged]);
	store_gedcoms();
	DeleteGedcomConfig($delged);
	print "<br />".str_replace("#GED#", $delged, $gm_lang["gedcom_deleted"])."<br />\n";
}

if (($action=="setdefault") && isset($default_ged)) {
	$DEFAULT_GEDCOM = urldecode($_POST["default_ged"]);
	store_gedcoms();
}

print "<br /><br />";
?>
<span class="subheaders"><?php print_text("current_gedcoms"); ?></span><br />
<form name="defaultform" method="post" action="editgedcoms.php">
<input type="hidden" name="action" value="setdefault" />
<?php
// Default gedcom choice
print "<br />";
if (count($GEDCOMS)>0) {
	if (userIsAdmin($username)) {
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
		print "</select><br /><br />";
	}
}

// Print table heading
print "<table class=\"gedcom_table\">";
if (userIsAdmin($username)) {
	print "<tr class=\"topbottombar\"><td>";
	print_help_link("add_gedcom_help", "qm", "add_gedcom");
	print "<a href=\"editconfig_gedcom.php?source=add_form\">".$gm_lang["add_gedcom"]."</a>";
	print "</td>";
}
print "<td>";
print_help_link("upload_gedcom_help", "qm", "upload_gedcom");
print "<a href=\"editconfig_gedcom.php?source=upload_form\">".$gm_lang["upload_gedcom"]."</a>";
print "</td>";
if (userIsAdmin($username)) {
	print "<td>";
	print_help_link("add_new_gedcom_help", "qm", "add_new_gedcom");
	print "<a href=\"editconfig_gedcom.php?source=add_new_form\">".$gm_lang["add_new_gedcom"]."</a>";
	print "</td>";
}
print  "<td><a href=\"admin.php\">" . $gm_lang["lang_back_admin"] . "</a></td></tr>";
print "</table><br />";
$current_ged = $GEDCOM;
$GedCount = 0;
// Print the table of available GEDCOMs
if (count($GEDCOMS)>0) {
		print "<table class=\"gedcom_table\">";
	foreach($GEDCOMS as $gedc=>$gedarray) {
		if (userGedcomAdmin($username, $gedc)) {
			if (empty($DEFAULT_GEDCOM)) $DEFAULT_GEDCOM = $gedc;

			// Row 0: Separator line
			if ($GedCount!=0) {
				print "<tr>";
				print "<td colspan=\"6\">";
				print "<br /><hr class=\"gedcom_table\" /><br />";
				print "</td>";
				print "</tr>";
			}
			$GedCount++;

			// Row 1: Title
			print "<tr>";
			print "<td class=\"topbottombar width20\">".$gm_lang["ged_title"]."</td>";
			print "<td class=\"shade1\" colspan=\"4\">";
			if ($DEFAULT_GEDCOM==$gedc) print "<span class=\"label\">".PrintReady($gedarray["title"])."</span></td>";
			else print PrintReady($gedarray["title"])."</td>";
			print "</tr>";
//			print "</table>";
			
			// Row 2: Column headings
//			print "<table class=\"gedcom_table\">";
			print "<tr class=\"shade2\">";
			print "<td>".$gm_lang["id"]."</td>";
			print "<td>".$gm_lang["ged_gedcom"]."</td>";
			print "<td>".$gm_lang["ged_config"]."</td>";
			print "<td>".$gm_lang["ged_privacy"]."</td>";
			print "<td>".$gm_lang["logs"]."</td>";
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
				$url = check_gedcom_downloadable($gedarray["path"]);
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
			print "<tr>";
			print "<td>&nbsp;</td>";
			print "<td>";
			if (file_exists($gedarray["path"])) {
				print "<a href=\"uploadgedcom.php?GEDFILENAME=$gedc&amp;verify=verify_gedcom&amp;action=add_form&amp;import_existing=1\">".$gm_lang["ged_import"]."</a>";
				if (!check_for_import($gedc)) {
					print "<br /><span class=\"error\">".$gm_lang["gedcom_not_imported"]."</span>";
				}
			}
			else print "&nbsp;";
			print "</td>";
			print "<td><a href=\"editconfig_gedcom.php?ged=".urlencode($gedc)."\">".$gm_lang["edit"]."</a></td>";
			print "<td><a href=\"edit_privacy.php?action=edit&amp;ged=".urlencode($gedc)."\">".$gm_lang["edit"]."</a></td>";
			print "<td><a href=\"javascript: ".$gm_lang["view_gedlog"]."\" onclick=\"window.open('viewlog.php?cat=F&amp;ged=".urlencode($gedc)."', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">".$gm_lang["view_searchlog"]."</a></td>";
			print "</tr>";
			
			// Row 4: Options
			print "<tr>";
			print "<td>&nbsp;</td>";
			print "<td><a href=\"editgedcoms.php?action=delete&amp;delged=".urlencode($gedc)."\" onclick=\"return confirm('".$gm_lang["confirm_gedcom_delete"]." ".preg_replace("/'/", "\'", $gedc)."?');\">".$gm_lang["delete"]."</a></td>";
			print "<td>&nbsp;</td>";
			print "<td>&nbsp;</td>";
			print "<td><a href=\"javascript: ".$gm_lang["view_gedlog"]."\" onclick=\"window.open('viewlog.php?cat=G&amp;ged=".urlencode($gedc)."', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">".$gm_lang["view_gedlog"]."</a></td>";
			print "</tr>";
			
			// Row 5: Options
			print "<tr>";
			print "<td>&nbsp;</td>";
			print "<td>";
			if ((file_exists($gedarray["path"])) && (check_for_import($gedc))) print "<a href=\"downloadgedcom.php?ged=$gedc\">".$gm_lang["ged_download"]."</a>";
			else print "&nbsp;";
			print "</td>";
			print "<td>&nbsp;</td>";
			print "<td>&nbsp;</td>";
			print "<td>&nbsp;</td>";
			print "</tr>";
//			print "</table>\n";
			
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
if (isset($GEDCOMS[$current_ged])) ReadGedcomConfig($GEDCOMS[$current_ged]["gedcom"]);

print "</table></form>";

print "<br /><table class=\"gedcom_table\">";
if (userIsAdmin($username)) {
	print "<tr class=\"topbottombar\"><td>";
	print_help_link("add_gedcom_help", "qm", "add_gedcom");
	print "<a href=\"editconfig_gedcom.php?source=add_form\">".$gm_lang["add_gedcom"]."</a>";
	print "</td>";
}
print "<td>";
print_help_link("upload_gedcom_help", "qm", "upload_gedcom");
print "<a href=\"editconfig_gedcom.php?source=upload_form\">".$gm_lang["upload_gedcom"]."</a>";
print "</td>";
if (userIsAdmin($username)) {
	print "<td>";
	print_help_link("add_new_gedcom_help", "qm", "add_new_gedcom");
	print "<a href=\"editconfig_gedcom.php?source=add_new_form\">".$gm_lang["add_new_gedcom"]."</a>";
	print "</td>";
}
print  "<td><a href=\"admin.php\">" . $gm_lang["lang_back_admin"] . "</a></td></tr>";
print "</table><br />";

print "<br /><br />\n";
print "</center>";

print_footer();

?>
