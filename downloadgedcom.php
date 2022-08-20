<?php
/**
 * Allow an admin user to download the entire gedcom	file.
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
 * @subpackage Admin
 * @version $Id: downloadgedcom.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

require "config.php";

$dl_controller = new DownloadGedcomController();

if ($dl_controller->action == "download") {
	if ($dl_controller->zip == "yes") {
		$dl_controller->DownloadZip();
	}
	else {
		header("Content-Type: text/plain; charset=".GedcomConfig::$CHARACTER_SET);
		header("Content-Disposition: attachment; filename=DL_".get_gedcom_from_id($dl_controller->gedcomid));
		AdminFunctions::PrintGedcom($dl_controller->gedcomid, $dl_controller->convert, $dl_controller->remove, $dl_controller->zip, $dl_controller->privatize_export, $dl_controller->privatize_export_level, "", $dl_controller->embedmm, $dl_controller->embednote, $dl_controller->addaction);
		exit;
	}
}
else {
	
	PrintHeader($dl_controller->pagetitle);
	?>
	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
		<?php AdminFunctions::AdminLink("editgedcoms.php", GM_LANG_manage_gedcoms); ?>
	</div>
	<!-- Setup the right box -->
	<div id="AdminColumnRight">
	</div>
	<div id="AdminColumnMiddle">
		<form name="genmodform" method="post" action="<?php print SCRIPT_NAME; ?>">
		<input type="hidden" name="action" value="download" />
		<input type="hidden" name="gedid" value="<?php print $dl_controller->gedcomid; ?>" />
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
				<td class="NavBlockHeader AdminNavBlockHeader" colspan="3">
					<?php print "<span class=\"AdminNavBlockTitle\">".GM_LANG_download_gedcom."</span>"; ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockColumnHeader AdminNavBlockColumnHeader">
					<?php print GM_LANG_options; ?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel"><div class="HelpIconContainer">
					<?php  PrintHelpLink("utf8_ansi_help", "qm"); print "</div>".GM_LANG_utf8_to_ansi; ?>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="convert" value="yes" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel"><div class="HelpIconContainer">
					<?php PrintHelpLink("remove_tags_help", "qm"); print "</div>".GM_LANG_remove_custom_tags; ?>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="remove" value="yes" checked="checked" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel"><div class="HelpIconContainer">
					<?php PrintHelpLink("embedmm_help", "qm"); print "</div>".GM_LANG_embedmm; ?>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="embedmm" value="yes" checked="checked" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel"><div class="HelpIconContainer">
					<?php PrintHelpLink("embednote_help", "qm"); print "</div>".GM_LANG_embednote; ?>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="embednote" value="yes" checked="checked" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel"><div class="HelpIconContainer">
					<?php PrintHelpLink("add_action_help", "qm"); print "</div>".GM_LANG_add_action; ?>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="addaction" value="yes" checked="checked" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel"><div class="HelpIconContainer">
					<?php PrintHelpLink("download_zipped_help", "qm"); print "</div>".GM_LANG_download_zipped; ?>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="zip" value="yes" checked="checked" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel"><div class="HelpIconContainer">
					<?php PrintHelpLink("apply_privacy_help", "qm"); print "</div>".GM_LANG_apply_privacy; ?>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<div><input type="checkbox" name="privatize_export" value="yes" onclick="expand_layer('DownloadPrivacyLevel'); return true;" /></div>
					<div id="DownloadPrivacyLevel" style="display: none">
						<?php print GM_LANG_choose_priv; ?>
						<br />
						<input type="radio" name="privatize_export_level" value="visitor" checked="checked" /><?php print GM_LANG_visitor; ?>
						<br />
						<input type="radio" name="privatize_export_level" value="user" /><?php print GM_LANG_user; ?>
						<br />
						<input type="radio" name="privatize_export_level" value="gedadmin" /><?php print GM_LANG_gedadmin; ?>
						<br />
						<input type="radio" name="privatize_export_level" value="siteadmin" /><?php print GM_LANG_siteadmin; ?>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockFooter">
					<input type="submit" value="<?php print GM_LANG_download_now; ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockColumnHeader">
					<?php print GM_LANG_download_note; ?>
				</td>
		</table>
		</form>
	</div>
	<?php
	PrintFooter();
}
?>