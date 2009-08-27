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

class DownloadGedcom {
	
	var $name = "DownloadGedcom";
	var $action = "";
	var $ged = "";
	var $remove = "no";
	var $convert = "";
	var $zip = "no";
	var $privatize_export = "";
	var $privatize_export_level = "";
	var $embedmm = "";
	var $comment = "";
	var $output = "";
	var $zipname = "";
	var $zipfile = "";
	var $gedname = "";
	
	function DownloadGedcom(&$genmod, &$gm_username, &$gm_lang) {
		$this->GetPageValues();
		if (!$this->CheckAccess($gm_username)) return false;
		else {
			switch ($genmod['action']) {
				case 'download':
					switch ($this->zip) {
						case 'yes':
							$this->DownloadZip($genmod, $gm_username);
							break;
						case 'no':
							header("Content-Type: text/plain; charset=".$genmod['character_set']);
							if (file_exists($genmod['gedcoms'][$this->ged]["path"])) header("Content-Disposition: attachment; filename=$this->ged; size=".filesize($genmod['gedcoms'][$this->ged]["path"]));
							else header("Content-Disposition: attachment; filename=$this->ged");
							PrintGedcom($this->ged, $this->convert, $this->remove, $this->zip, $this->privatize_export, $this->privatize_export_level, "", $this->embedmm);
							exit;
							break;
					}
					break;
				default:
					$this->AddHeader($gm_lang);
					$this->ShowForm($genmod, $gm_lang);
					$this->AddFooter();
					break;
			}
		}
	}
	
	function CheckAccess(&$gm_username) {
		global $Users;
		
		if ((!$Users->userGedcomAdmin($gm_username))||(empty($this->ged))) {
			// header("Location: editgedcoms.php");
			// exit;
			return false;
		}
		else return true;
	}
	
	function AddHeader($gm_lang) {
		print_header($gm_lang["download_gedcom"]);
	}
	
	function AddFooter() {
		print_footer();
	}
	
	function ShowForm(&$genmod, &$gm_lang) {
		?>
		<div class="center">
			<h3><?php echo $gm_lang["download_gedcom"]; ?></h3>
			<br />
			<div id="downloadgedcom_form">
				<form name="genmodform" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
					<input type="hidden" name="page" value="<?php echo $genmod['page'];?>" />
					<input type="hidden" name="action" value="download" />
					<input type="hidden" name="ged" value="<?php echo $this->ged; ?>" />
					<div class="topbottombar"><?php echo $gm_lang["options"]; ?></div>
					<div id="downloadgedcom_content">
						<div id="downloadgedcom_labels">
							<label for="convert"><?php  print_help_link("utf8_ansi_help", "qm"); echo $gm_lang["utf8_to_ansi"]; ?></label>
							<br />
							<label for="remove"><?php print_help_link("remove_tags_help", "qm"); echo $gm_lang["remove_custom_tags"]; ?></label>
							<br />
							<label for="embedmm"><?php print_help_link("embedmm_help", "qm"); echo $gm_lang["embedmm"]; ?></label>
							<br />
							<label for="zip"><?php print_help_link("download_zipped_help", "qm"); echo $gm_lang["download_zipped"]; ?></label>
							<br />
							<label for="privatize_export"><?php print_help_link("apply_privacy_help", "qm"); echo $gm_lang["apply_privacy"]; ?></label>
							<br />
						</div>
						<div id="downloadgedcom_options">
							<input type="checkbox" name="convert" value="yes" />
							<br />
							<input type="checkbox" name="remove" value="yes" checked="checked" />
							<br />
							<input type="checkbox" name="embedmm" value="yes" checked="checked" />
							<br />
							<input type="checkbox" name="zip" value="yes" checked="checked" />
							<br />
							<input type="checkbox" name="privatize_export" value="yes" onclick="expand_layer('privradio'); return true;" />
							<br />
							<div id="privradio" style="display: none">
								<?php echo $gm_lang["choose_priv"]; ?>
								<br />
								<input type="radio" name="privatize_export_level" value="visitor" checked="checked" /><?php echo $gm_lang["visitor"]; ?>
								<br />
								<input type="radio" name="privatize_export_level" value="user" /><?php echo $gm_lang["user"]; ?>
								<br />
								<input type="radio" name="privatize_export_level" value="gedadmin" /><?php echo $gm_lang["gedadmin"]; ?>
								<br />
								<input type="radio" name="privatize_export_level" value="siteadmin" /><?php echo $gm_lang["siteadmin"]; ?>
							</div>
						</div>
					</div>
					<br />
					<div class="topbottombar">
						<input type="submit" value="<?php echo $gm_lang["download_now"]; ?>" />
						<input type="button" value="<?php echo $gm_lang["back"];?>" onclick="window.location='editgedcoms.php';"/>
					</div>
				</form>
				<br />
				<div id="notice">
					<?php echo $gm_lang["download_note"]; ?>
				</div>
			</div>
		</div>
		<?php
	}
	
	function GetPageValues() {
		if (isset($_REQUEST['convert'])) $this->convert = $_REQUEST['convert'];
		if (isset($_REQUEST['remove'])) $this->remove = $_REQUEST['remove'];
		if (isset($_REQUEST['embedmm'])) $this->embedmm = $_REQUEST['embedmm'];
		if (isset($_REQUEST['zip'])) $this->zip = $_REQUEST['zip'];
		if (isset($_REQUEST['privatize_export'])) $this->privatize_export = $_REQUEST['privatize_export'];
		if (isset($_REQUEST['privatize_export_level'])) $this->privatize_export_level = $_REQUEST['privatize_export_level'];
		if (isset($_REQUEST['ged'])) $this->ged = $_REQUEST['ged'];
	}
	
	function DownloadZip($genmod, $gm_username) {
		global $Users, $MEDIATYPE;
		// TODO: Remove below line in the future when all vars are in $genmod
		$INDEX_DIRECTORY = $genmod['index_directory'];
		require('includes/pclzip.lib.php');
		$this->zipname = "dl".adodb_date("YmdHis").".zip";
		$this->zipfile = $genmod['index_directory'].$this->zipname;
		$this->gedname = $genmod['index_directory']."DL_".$this->ged;
		if (file_exists($this->gedname)) unlink($this->gedname);
		PrintGedcom($this->ged, $this->convert, $this->remove, $this->zip, $this->privatize_export, $this->privatize_export_level, $this->gedname, $this->embedmm);
		$this->comment = "Created by Genmod ".$genmod["version"]." ".$genmod["version_release"]." on ".adodb_date("r").".";
		$archive = new PclZip($this->zipfile);
		$v_list = $archive->create($this->gedname, PCLZIP_OPT_COMMENT, $this->comment);
		if ($v_list == 0) echo "Error : ".$archive->errorInfo(true);
		else {
			unlink($this->gedname);
			$fname = $this->zipfile;
			include('downloadbackup.php');
		}
	}
}
require "config.php";
require "includes/setgenmod.php";
$downloadgedcom = new DownloadGedcom($genmod, $gm_username, $gm_lang);

?>