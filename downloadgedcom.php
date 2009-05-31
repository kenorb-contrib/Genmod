<?php
/**
 * Allow an admin user to download the entire gedcom	file.
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
 * @subpackage Admin
 * @version $Id: downloadgedcom.php,v 1.8 2006/04/17 20:01:52 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if ((!userGedcomAdmin($gm_username))||(empty($ged))) {
	header("Location: editgedcoms.php");
	exit;
}
if (!isset($action)) $action="";
if (!isset($remove)) $remove="no";
if (!isset($convert)) $convert="no";
if (!isset($zip)) $zip="no";
if (!isset($privatize_export)) $privatize_export = "";

if ($action=="download" && $zip == "yes") {
	require "includes/pclzip.lib.php";
	require "includes/adodb-time.inc.php";
	$zipname = "dl".adodb_date("YmdHis").".zip";
	$zipfile = $INDEX_DIRECTORY.$zipname;
	$gedname = $INDEX_DIRECTORY."DL_".$ged;
	if (file_exists($gedname)) unlink($gedname);
	print_gedcom($ged, $convert, $remove, $zip, $privatize_export, $privatize_export_level, $gedname);
	$comment = "Created by Genmod ".$VERSION." ".$VERSION_RELEASE." on ".adodb_date("r").".";
	$archive = new PclZip($zipfile);
	$v_list = $archive->create($gedname, PCLZIP_OPT_COMMENT, $comment);
	if ($v_list == 0) print "Error : ".$archive->errorInfo(true);
	else {
		unlink($gedname);
		header("Location: downloadbackup.php?fname=$zipname");
		exit;
	}
	exit;
}

if ($action=="download") {
	header("Content-Type: text/plain; charset=$CHARACTER_SET");
	header("Content-Disposition: attachment; filename=$ged; size=".filesize($GEDCOMS[$GEDCOM]["path"]));
	print_gedcom($ged, $convert, $remove, $zip, $privatize_export, $privatize_export_level, "");
}
else {
	print_header($gm_lang["download_gedcom"]);
	?>
	<div class="center">
	<h2><?php print $gm_lang["download_gedcom"]; ?></h2>
	<br />
	<form name="convertform" method="post">
		<input type="hidden" name="action" value="download" />
		<table class="list_table" border="0" align="center" valign="top">
		<tr><td colspan="2" class="facts_label03" style="text-align:center;">
		<?php print $gm_lang["options"]; ?>
		</td></tr>
		<tr><td class="list_label" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; "><?php  print_help_link("utf8_ansi_help", "qm"); print $gm_lang["utf8_to_ansi"]; ?></td>
			<td class="list_value" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; "><input type="checkbox" name="convert" value="yes" /></td></tr>
		<tr><td class="list_label" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; "><?php print print_help_link("remove_tags_help", "qm"); print $gm_lang["remove_custom_tags"]; ?></td>
			<td class="list_value" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; "><input type="checkbox" name="remove" value="yes" checked="checked" /></td></tr>
		<tr><td class="list_label" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; "><?php print_help_link("download_zipped_help", "qm"); print $gm_lang["download_zipped"]; ?></td>
			<td class="list_value" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; "><input type="checkbox" name="zip" value="yes" checked="checked" /></td></tr>
		<tr><td class="list_label" valign="baseline" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; "><?php print_help_link("apply_privacy_help", "qm"); print $gm_lang["apply_privacy"]; ?>
			<div id="privtext" style="display: none"></div>
			</td>
			<td class="list_value" style="padding: 5px; text-align:<?php if ($TEXT_DIRECTION == "ltr") print "left"; else print "right";?>; ">
			<input type="checkbox" name="privatize_export" value="yes" onclick="expand_layer('privtext'); expand_layer('privradio');" />
			<div id="privradio" style="display: none"><br /><?php print $gm_lang["choose_priv"]; ?><br />
			<input type="radio" name="privatize_export_level" value="visitor" checked="checked" />
			<?php print $gm_lang["visitor"]; ?><br />
			<input type="radio" name="privatize_export_level" value="user" /><?php print $gm_lang["user"]; ?><br />
			<input type="radio" name="privatize_export_level" value="gedadmin" /><?php print $gm_lang["gedadmin"]; ?><br />
			<input type="radio" name="privatize_export_level" value="admin" /><?php print $gm_lang["siteadmin"]; ?><br />
		</div></td>
		</tr>
		<tr><td class="facts_label03" colspan="2" style="padding: 5px; ">
		<input type="submit" value="<?php print $gm_lang["download_now"]; ?>" />
		<input type="button" value="<?php print $gm_lang["back"];?>" onclick="window.location='editgedcoms.php';"/></td></tr>
		</table><br />
	<br /><br />
	</form>
	<?php
	print $gm_lang["download_note"]."<br /><br /><br />\n";
	print "</div>";
	print_footer();
}
?>