<?php
/**
 * Backups and restores Genmod related info.
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
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id: backup.php,v 1.12 2006/03/19 15:20:16 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
if (!userIsAdmin($gm_username)) {
	header("Location: login.php?url=backup.php");
	exit;
}

if (!isset($action)) $action = "backup";

if ($action == "backup") print_header($gm_lang["um_backup"]);
if ($action == "restore") print_header($gm_lang["um_restore"]);

// Backup part
if ($action == "backup") {
	// If first time, let the user choose the options
	if ((!isset($_POST["um_config"])) && (!isset($_POST["um_lang"])) && (!isset($_POST["um_gedcoms"])) && (!isset($_POST["um_gedsets"])) &&(!isset($_POST["um_logs"])) &&(!isset($_POST["um_usinfo"]))) {
		?>
		<div id="backup_header"><?php print_help_link("um_bu_explain","qm"); print $gm_lang["um_backup"]; ?></div>
		<form action="<?php print $SCRIPT_NAME; ?>" method="post">
		<input type="hidden" name="action" value="backup" />
		<table class="width60 center <?php print $TEXT_DIRECTION?>">
			<tr><td colspan="2" class="admin_topbottombar"><?php print $gm_lang["options"]; ?></td></tr>
			<tr><td class="shade2"><?php print $gm_lang["um_bu_config"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_config" value="yes" checked="checked" /></td></tr>
			<tr><td class="shade2"><?php print $gm_lang["um_bu_lang"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_lang" value="yes" checked="checked" /></td></tr>
			<tr><td class="shade2"><?php print $gm_lang["um_bu_gedcoms"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_gedcoms" value="yes" checked="checked" /></td></tr>
			<tr><td class="shade2"><?php print $gm_lang["um_bu_gedsets"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_gedsets" value="yes" checked="checked" /></td></tr>
			<tr><td class="shade2"><?php print $gm_lang["um_bu_logs"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_logs" value="yes" checked="checked" /></td></tr>
			<tr><td class="shade2"><?php print $gm_lang["um_bu_usinfo"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_usinfo" value="yes" checked="checked" /></td></tr>
			<tr><td style="padding: 5px" colspan="2" class="center"><button type="submit" name="submit"><?php print $gm_lang["um_mk_bu"]; ?></button>
			<input type="button" value="<?php print $gm_lang["lang_back_admin"];?>"  onclick="window.location='admin.php';"/></td></tr>
		</table></form><br /><br />
		<?php
		print_footer();
		exit;
	}

	// Retrieve the maximum maximum execution time from the gedcom settings
	$sql = "SELECT max(gc_time_limit) FROM ".$TBLPREFIX."gedconf";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if ($res) while($row = mysql_fetch_row($res)) $time_limit = $row["0"];
	else $time_limit = $TIME_LIMIT;
	@set_time_limit($time_limit);
	print "Time limit set to ".$time_limit;
	
	$flist = array();
	
	// Backup user information
	if (isset($_POST["um_usinfo"])) {
		$tables = array("users", "news", "messages", "blocks");
		$fn = ExportTable($tables, "yes", "users");
		if (!empty($fn)) $flist = array_merge($fn, $flist);
	}

	// Backup config.php
	if (isset($_POST["um_config"])) {
		$flist[] = "config.php";
	}
	
	// backup language settings and extra language files
	if (isset($_POST["um_lang"])) {
		if (file_exists($INDEX_DIRECTORY."lang_settings.php")) $flist[] = $INDEX_DIRECTORY."lang_settings.php";
		$d = dir("languages");
		while (false !== ($entry = $d->read())) {
			if (strstr($entry, ".extra")==".extra.php") {
				$flist[] = "./languages/".$entry;
			}
		}
		$d->close();
	}
	
	// Backup gedcoms and changes
	if (isset($_POST["um_gedcoms"])) {
		foreach($GEDCOMS as $key=> $gedcom) {
			$ged = $gedcom["gedcom"];
			if (file_exists($INDEX_DIRECTORY."export_".$ged)) unlink($INDEX_DIRECTORY."export_".$ged);
			$gedname = $INDEX_DIRECTORY."export_".$ged;
			print_gedcom($ged, "no", "no", "yes", "no", "", $gedname);
			$flist[] = $gedname;
		}
		$fn = ExportTable("changes");
		if (!empty($fn)) $flist = array_merge($fn, $flist);
	}
	
	// Backup gedcom settings
	if (isset($_POST["um_gedsets"])) {

		$tables = array("gedcoms", "gedconf", "privacy");
		$fn = ExportTable($tables, "yes", "gedcomsettings");
		if (!empty($fn)) $flist = array_merge($fn, $flist);
	}

	// Backup logfiles and counters
	if (isset($_POST["um_logs"])) {

		// Gedcom counters
		$fn = ExportTable("counters");
		if (!empty($fn)) $flist = array_merge($fn, $flist);

		// Gedcom searchlogs, changelogs and systemlogs
		$fn = ExportTable("log");
		if (!empty($fn)) $flist = array_merge($fn, $flist);
	}
	
	// Make the zip
		print "<div id=\"backup_header\">".$gm_lang["um_backup"]."</div>";
		print "<table class=\"width60 center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\">".$gm_lang["um_results"]."</td></tr>";

	if (count($flist) > 0) {
		require "includes/pclzip.lib.php";
		require "includes/adodb-time.inc.php";
		$buname = adodb_date("YmdHis").".zip";
		$fname = $INDEX_DIRECTORY.$buname;
		$comment = "Created by Genmod ".$VERSION." ".$VERSION_RELEASE." on ".adodb_date("r").".";
		$archive = new PclZip($fname);
		$v_list = $archive->create($flist, PCLZIP_OPT_COMMENT, $comment);
		print "<tr><td class=\"shade2 center\">";
		if ($v_list == 0) print "Error : ".$archive->errorInfo(true)."</td></tr>";
		else {
			print $gm_lang["um_zip_succ"]."</td></tr>";
			$url = $SERVER_URL;
			if (substr($url,-1,1)!="/") $url .= "/";
			print "<tr><td class=\"shade1 center\"><a href=".$url."downloadbackup.php?fname=".$buname." target=_blank>".$gm_lang["um_zip_dl"]." ".$fname."</a>  (";
			printf("%.0f Kb", (filesize($fname)/1024));
			print")</td></tr>";
		}
		
		// Remove temporary files again
		foreach ($flist as $key=>$fn) {
			if (substr($fn, strlen($INDEX_DIRECTORY), 7) == "export_") unlink($fn);
		}
	}
	else print "<td class=\"shade2\">".$gm_lang["um_nofiles"]."</td></tr>";
	print "<tr><td class=\"center\"><input type=\"button\" value=\"".$gm_lang["lang_back_admin"]."\" onclick=\"window.location='admin.php';\" /></td></tr></table><br /><br />";

	@set_time_limit($time_limit);
	print_footer();
	exit;
}

if ($action == "restore") {
	// If first time, let the user choose the options
	if ((!isset($_POST["um_counters"])) && (!isset($_POST["um_changes"])) && (!isset($_POST["um_gedsets"])) &&(!isset($_POST["um_logs"])) &&(!isset($_POST["um_usinfo"]))) {
		$handle = opendir($INDEX_DIRECTORY);
		
		// Check if export files exist in the index dir. If not, we will display a message
		$files = array();
		while (false !== ($file = readdir($handle))) {
			if (substr($file, -4) == ".sql") $files[] = $file;
		}
		if (count($files) == "0") $nofiles = true;
		else $nofiles = false;
		?>
		<div id="backup_header"><?php print $gm_lang["um_restore"]; ?></div>
		
		<form action="<?php print $SCRIPT_NAME; ?>" method="post">
		<input type="hidden" name="action" value="restore" />
		<table class="width60 center <?php print $TEXT_DIRECTION?>">
			<tr><td colspan="2" class="topbottombar"><?php print $gm_lang["options"]; ?></td></tr>
			<?php 
			if(in_array("export_users.sql", $files)) { ?>
			<tr><td class="shade2"><?php print $gm_lang["um_res_usinfo"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_usinfo" value="yes" /></td></tr>
			<?php }
			if(in_array("export_gedcomsettings.sql", $files)) { ?>
			<tr><td class="shade2"><?php print $gm_lang["um_res_gedsets"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_gedsets" value="yes" /></td></tr>
			<?php }
			if(in_array("export_changes.sql", $files)) { ?>
			<tr><td class="shade2"><?php print $gm_lang["um_res_changes"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_changes" value="yes" /></td></tr>
			<?php }
			if(in_array("export_log.sql", $files)) { ?>
			<tr><td class="shade2"><?php print $gm_lang["um_res_logs"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_logs" value="yes" /></td></tr>
			<?php }
			if(in_array("export_counters.sql", $files)) { ?>
			<tr><td class="shade2"><?php print $gm_lang["um_res_counters"]; ?></td><td class="shade1 center"><input type="checkbox" name="um_counters" value="yes" /></td></tr>
			<?php } 
			if ($nofiles) { ?>
				<tr><td class="shade2" colspan="2"><?php print $gm_lang["um_res_nofiles"]; ?></td></tr>
			<?php } ?>
			<tr><td style="padding: 5px" colspan="2" class="center">
			<?php if (!$nofiles) { ?>
			<button type="submit" name="submit"><?php print $gm_lang["um_mk_res"]; ?></button>
			<?php } ?>
			<input type="button" value="<?php print $gm_lang["lang_back_admin"];?>"  onclick="window.location='admin.php';"/></td></tr>
		</table></form><br /><br />
		<?php
		print_footer();
		exit;
	}

	// Retrieve the maximum maximum execution time from the gedcom settings
	$sql = "SELECT max(gc_time_limit) FROM ".$TBLPREFIX."gedconf";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if ($res) while($row = mysql_fetch_row($res)) $time_limit = $row["0"];
	else $time_limit = $TIME_LIMIT;
	@set_time_limit($time_limit);

	$error = false;
	// Restore logfiles. Do this first, so we can write the restore results to the logfile.
	if (isset($_POST["um_logs"])) {
		$result = ImportTable("export_log.sql");
		if (empty($result)) WriteToLog("Restore of logfiles succesfull", "I", "S");
		else {
			WriteToLog("Restore of logfiles failed with error: ".$result, "E", "S");
			$error = true;
		}
	}
	
	// Restore counters 
	if (isset($_POST["um_counters"])) {
		$result = ImportTable("export_counters.sql");
		if (empty($result)) WriteToLog("Restore of counters succesfull", "I", "S");
		else {
			WriteToLog("Restore of counters failed with error: ".$result, "E", "S");
			$error = true;
		}
	}
	
	// Restore changes
	if (isset($_POST["um_changes"])) {
		$result = ImportTable("export_changes.sql");
		if (empty($result)) WriteToLog("Restore of changes succesfull", "I", "S");
		else {
			WriteToLog("Restore of changes failed with error: ".$result, "E", "S");
			$error = true;
		}
	}
	
	// Restore gedcom settings
	if (isset($_POST["um_gedsets"])) {
		$result = ImportTable("export_gedcomsettings.sql");
		if (empty($result)) WriteToLog("Restore of gedcom settings succesfull", "I", "S");
		else {
			WriteToLog("Restore of gedcom settings failed with error: ".$result, "E", "S");
			$error = true;
		}
	}
	
	// Restore usersettings
	if (isset($_POST["um_usinfo"])) {
		$result = ImportTable("export_users.sql");
		if (empty($result)) WriteToLog("Restore of usersettings successful", "I", "S");
		else {
			WriteToLog("Restore of usersettings failed with error: ".$result, "E", "S");
			$error = true;
		}
	}
	
	// Print the result report
	print "<div id=\"backup_header\">".$gm_lang["um_restore"]."</div>";
	print "<table class=\"width60 center $TEXT_DIRECTION\">";
	print "<tr><td class=\"topbottombar\">".$gm_lang["um_results"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">";
	if ($error) print "<a href=\"javascript: ".$gm_lang["view_syslog"]."\" onclick=\"window.open('viewlog.php?cat=S&amp;max=20&amp;type=E', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">".$gm_lang["um_res_errors"]."</a>";
	else print $gm_lang["um_res_success"];
	print "</td></tr>";
	print "<tr><td class=\"center\"><input type=\"button\" value=\"".$gm_lang["lang_back_admin"]."\" onclick=\"window.location='admin.php';\" /></td></tr></table><br /><br />";
}
@set_time_limit($TIME_LIMIT);
print_footer();
?>
