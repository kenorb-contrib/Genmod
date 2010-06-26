<?php
/**
 * Allow an admin user to download the entire gedcom	file.
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
 * @subpackage Admin
 * @version $Id$
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
	<div id="admin_genmod_left">
		<div class="admin_link"><a href="admin.php"><?php print GM_LANG_admin;?></a></div>
		<div class="admin_link"><a href="editgedcoms.php"><?php print GM_LANG_manage_gedcoms;?></a></div>
	</div>
	<!-- Setup the right box -->
	<div id="admin_genmod_right">
	</div>
	<div id="content">
		<div class="topbottombar"><h3>
			<?php print GM_LANG_download_gedcom; ?></h3>
		</div>
		<br />
		<form name="genmodform" method="post" action="<?php print SCRIPT_NAME; ?>">
		<input type="hidden" name="action" value="download" />
		<input type="hidden" name="gedid" value="<?php print $dl_controller->gedcomid; ?>" />
		<div class="topbottombar"><?php print GM_LANG_options; ?></div>
		<div id="downloadgedcom_content">
			<div id="downloadgedcom_labels">
				<label for="convert"><?php  PrintHelpLink("utf8_ansi_help", "qm"); print GM_LANG_utf8_to_ansi; ?></label>
				<br />
				<label for="remove"><?php PrintHelpLink("remove_tags_help", "qm"); print GM_LANG_remove_custom_tags; ?></label>
				<br />
				<label for="embedmm"><?php PrintHelpLink("embedmm_help", "qm"); print GM_LANG_embedmm; ?></label>
				<br />
				<label for="embednote"><?php PrintHelpLink("embednote_help", "qm"); print GM_LANG_embednote; ?></label>
				<br />
				<label for="add_action"><?php PrintHelpLink("add_action_help", "qm"); print GM_LANG_add_action; ?></label>
				<br />
				<label for="zip"><?php PrintHelpLink("download_zipped_help", "qm"); print GM_LANG_download_zipped; ?></label>
				<br />
				<label for="privatize_export"><?php PrintHelpLink("apply_privacy_help", "qm"); print GM_LANG_apply_privacy; ?></label>
				<br />
			</div>
			<div id="downloadgedcom_options">
				<input type="checkbox" name="convert" value="yes" />
				<br />
				<input type="checkbox" name="remove" value="yes" checked="checked" />
				<br />
				<input type="checkbox" name="embedmm" value="yes" checked="checked" />
				<br />
				<input type="checkbox" name="embednote" value="yes" checked="checked" />
				<br />
				<input type="checkbox" name="addaction" value="yes" checked="checked" />
				<br />
				<input type="checkbox" name="zip" value="yes" checked="checked" />
				<br />
				<input type="checkbox" name="privatize_export" value="yes" onclick="expand_layer('privradio'); return true;" />
				<br />
				<div id="privradio" style="display: none">
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
			</div>
		</div>
		<br />
		<div class="topbottombar row">
			<input type="submit" value="<?php print GM_LANG_download_now; ?>" />
		</div>
		</form>
		<br />
		<div id="notice">
			<?php print GM_LANG_download_note; ?>
		</div>
	</div>
	<?php
	PrintFooter();
}
?>