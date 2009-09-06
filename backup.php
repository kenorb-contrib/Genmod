<?php
/**
 * Backups and restores Genmod related info.
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
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id$
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
if (!$gm_user->userIsAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=backup.php");
	else header("Location: ".LOGIN_URL."?url=backup.php");
	exit;
}

if (!isset($action)) $action = "backup";
if (!isset($step)) $step = "1";

if ($action == "backup") {
	if ($MEDIA_IN_DB && (2 * MediaFS::GetTotalMediaSize() >= disk_free_space(INDEX_DIRECTORY) || !MediaFS::DirIsWritable($MEDIA_DIRECTORY, false))) $nomedia = true;
	else $nomedia = false;
}

switch ($action) {
	case "backup" :
		print_header($gm_lang["um_backup"]);
		break;
	case "restore" :
		print_header($gm_lang["um_restore"]);
		break;
}
?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print $gm_lang["admin"];?></a></div>
</div>
<div id="content">
	<?php
	// Backup part
	if ($action == "backup") { ?>
		<div class="admin_topbottombar">
			<h3> <?php print_help_link("um_bu_explain","qm","um_backup"); print $gm_lang["um_backup"];?></h3>
		</div>
		<form action="<?php print $SCRIPT_NAME; ?>" method="post">
			<input type="hidden" name="action" value="backup" />
		<?php
		// If first time, let the user choose the options
		if ($step == 1) {
			?>
			<input type="hidden" name="step" value="2" />
			<?php if ($nomedia) { 
				print "<div class=\"shade2\"><br />".$gm_lang["um_nomedia"]."<br /><br /></div>";
			}
			?>	
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_config"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_config" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_lang"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_lang" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_gedcoms"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_gedcoms" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_media"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_media" value="yes" <?php if ($nomedia) { ?> disabled="disabled" <?php } else { ?> checked="checked" <?php } ?>  /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_gedsets"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_gedsets" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_logs"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_logs" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_usinfo"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_usinfo" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_mypages"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_mypages" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box">
				<div class="width50 choice_left"><?php print $gm_lang["um_bu_actions"]; ?></div>
				<div class="width10 choice_right"><input type="checkbox" name="um_actions" value="yes" checked="checked" /></div>
			</div>
			<div class="admin_item_box center"><br />
				<input type="submit" name="submit" value="<?php print $gm_lang["um_mk_bu"]; ?>" />
			</div>
		</form>
		<?php
		}
		else {
			$time_limit = GedcomConfig::GetHighMaxTime();
			@set_time_limit($time_limit);
			// print "Time limit set to ".$time_limit;
			
			if ($step == 2 && isset($um_media)) {
				if (isset($um_config)) print "<input type=\"hidden\" name=\"um_config\" value=\"".$um_config."\" />";
				if (isset($um_lang)) print "<input type=\"hidden\" name=\"um_lang\" value=\"".$um_lang."\" />";
				if (isset($um_gedcoms)) print "<input type=\"hidden\" name=\"um_gedcoms\" value=\"".$um_gedcoms."\" />";
				if (isset($um_gedsets)) print "<input type=\"hidden\" name=\"um_gedsets\" value=\"".$um_gedsets."\" />";
				if (isset($um_logs)) print "<input type=\"hidden\" name=\"um_logs\" value=\"".$um_logs."\" />";
				if (isset($um_usinfo)) print "<input type=\"hidden\" name=\"um_usinfo\" value=\"".$um_usinfo."\" />";
				if (isset($um_mypages)) print "<input type=\"hidden\" name=\"um_mypages\" value=\"".$um_mypages."\" />";
				if (isset($um_actions)) print "<input type=\"hidden\" name=\"um_actions\" value=\"".$um_actions."\" />";
				print "<input type=\"hidden\" name=\"um_media\" value=\"".$um_media."\" />";
				print "<input type=\"hidden\" name=\"step\" value=\"3\" />";
				
				$flist = GetMediaFiles();
				foreach ($flist as $key => $mfile) {
					MediaFS::CreateFile(RelativePathFile($MEDIA_DIRECTORY.MediaFS::CheckMediaDepth($mfile)));
				}
				print "<div class=\"shade2\"><br />".$gm_lang["um_mediaexp"]."<br /></div>";
				print "<div class=\"admin_item_box center\">";
				print "<input type=\"submit\" name=\"submit\" value=\"".$gm_lang["um_proceed_bu"]."\" />";
				print "</div>";
				print "</form>";
				print_footer();
				exit;
			}
			else $step = 3;
			
			if ($step == 3) {
				$flist = array();
				
				// Backup config.php, user include files and lockout table
				if (isset($_POST["um_config"])) {
					$flist[] = "config.php";
					$flist[] = "includes/values/include_top.php";
					$flist[] = "includes/values/include_bottom.php";
					$tables = array("lockout");
					$fn = ExportTable($tables, "yes", "config");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup MyPages
				if (isset($_POST["um_mypages"])) {
					$tables = array("pages");
					$fn = ExportTable($tables, "yes", "mypages");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// backup language settings and extra language files
				if (isset($_POST["um_lang"])) {
					if (file_exists(INDEX_DIRECTORY."lang_settings.php")) $flist[] = INDEX_DIRECTORY."lang_settings.php";
					$d = dir("languages");
					while (false !== ($entry = $d->read())) {
						if (strstr($entry, ".extra")==".extra.php") {
							$flist[] = "./languages/".$entry;
						}
					}
					$d->close();
					$fn = ExportTable("lang_settings");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup gedcoms and changes
				if (isset($_POST["um_gedcoms"])) {
					foreach($GEDCOMS as $key=> $gedcom) {
						$ged = $gedcom["gedcom"];
						if (file_exists(INDEX_DIRECTORY."export_".$ged)) unlink(INDEX_DIRECTORY."export_".$ged);
						$gedname = INDEX_DIRECTORY."export_".$ged;
						PrintGedcom($ged, "no", "no", "yes", "no", "", $gedname, "");
						$flist[] = $gedname;
					}
					$fn = ExportTable("changes");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup multimediafiles in GM
				if (isset($_POST["um_media"])) {
					$mlist = GetMediaFiles($GEDCOMID);
					foreach ($mlist as $key => $mfile) {
						$file = RelativePathFile($MEDIA_DIRECTORY.MediaFS::CheckMediaDepth($mfile));
						if (file_exists($file)) $flist[] = $file;
					}
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
				
				// Backup user information
				if (isset($_POST["um_usinfo"])) {
					$tables = array("users", "users_gedcoms", "news", "messages", "blocks", "favorites");
					$fn = ExportTable($tables, "yes", "users");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup Actions
				if (isset($_POST["um_actions"])) {
					$fn = ExportTable("actions");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Make the zip
				?>
				<div class="shade2 center">
					<?php print $gm_lang["um_results"]; ?>
				</div>
				<?php
				if (count($flist) > 0) {
					require "includes/pclzip.lib.php";
					$buname = adodb_date("YmdHis").".zip";
					$fname = INDEX_DIRECTORY.$buname;
					$comment = "Created by Genmod ".GM_VERSION." ".GM_VERSION_RELEASE." on ".adodb_date("r").".";
					$archive = new PclZip($fname);
					$v_list = $archive->create($flist, PCLZIP_OPT_COMMENT, $comment);
					print "<div class=\"shade1\"><br />";
					if ($v_list == 0) print "Error : ".$archive->errorInfo(true)."</div>";
					else {
						print $gm_lang["um_zip_succ"]."</div>";
						$url = SERVER_URL;
						if (substr($url,-1,1)!="/") $url .= "/";
						print "<div class=\"shade1\"><a href=".$url."downloadbackup.php?fname=".urlencode($fname)." target=_blank>".$gm_lang["um_zip_dl"]." ".$fname."</a>  (";
						printf("%.0f Kb", (filesize($fname)/1024));
						print")<br /></div>";
					}
					
					// Remove temporary files again
					foreach ($flist as $key=>$fn) {
						if (substr($fn, strlen(INDEX_DIRECTORY), 7) == "export_") unlink($fn);
					}
				}
				else print "<div class=\"shade2\"><br />".$gm_lang["um_nofiles"]."<br /></div>";
			}
			
			@set_time_limit($time_limit);
		}
	}
	
	if ($action == "restore") { ?>
		<div class="admin_topbottombar">
			<h3><?php print $gm_lang["um_restore"]; ?></h3>
		</div>
		<?php
		// If first time, let the user choose the options
		if (!isset($_POST["um_counters"]) && !isset($_POST["um_changes"]) && !isset($_POST["um_gedsets"]) && !isset($_POST["um_logs"]) && !isset($_POST["um_usinfo"]) && !isset($_POST["um_mypages"]) && !isset($_POST["um_config"]) && !isset($_POST["um_actions"])) {
			$handle = opendir(INDEX_DIRECTORY);
			
			// Check if export files exist in the index dir. If not, we will display a message
			$files = array();
			while (false !== ($file = readdir($handle))) {
				if (substr($file, -4) == ".sql") $files[] = $file;
			}
			if (count($files) == "0") $nofiles = true;
			else $nofiles = false;
			?>
			
			<form action="<?php print $SCRIPT_NAME; ?>" method="post">
				<input type="hidden" name="action" value="restore" />
				<?php 
				if(in_array("export_config.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_config"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_config" value="yes" /></div>
					</div>
				<?php }
				if(in_array("export_lang_settings.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_lang"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_lang" value="yes" /></div>
					</div>
				<?php }
				if(in_array("export_changes.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_changes"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_changes" value="yes" /></div>
					</div>
				<?php }
				if(in_array("export_gedcomsettings.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_gedsets"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_gedsets" value="yes" /></div>
					</div>
				<?php }
				if(in_array("export_log.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_logs"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_logs" value="yes" /></div>
					</div>
				<?php }
				if(in_array("export_users.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_usinfo"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_usinfo" value="yes" /></div>
					</div>
				<?php }
				if(in_array("export_mypages.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_mypages"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_mypages" value="yes" /></div>
					</div>
				<?php }
				if(in_array("export_actions.sql", $files)) { ?>
					<div class="admin_item_box">
						<div class="width50 choice_left"><?php print $gm_lang["um_res_actions"]; ?></div>
						<div class="width10 choice_right"><input type="checkbox" name="um_actions" value="yes" /></div>
					</div>
				<?php }
				if ($nofiles) { ?>
					<div class="admin_item_box">
						<?php print $gm_lang["um_res_nofiles"]; ?>
					</div>
				<?php }
				else { ?>
					<div class="admin_item_box center"><br />
						<input type="submit" name="submit" value="<?php print $gm_lang["um_mk_res"]; ?>" />
					</div>
				<?php } ?>
			</form>
			<?php
		}
		else {
			$time_limit = GedcomConfig::GetHighMaxTime();
			@set_time_limit($time_limit);
		
			$error = false;
			// Restore logfiles. Do this first, so we can write the restore results to the logfile.
			if (isset($_POST["um_logs"])) {
				$result = ImportTable("export_log.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of logfiles and counters successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of logfiles and counters failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore lockout table
			if (isset($_POST["um_config"])) {
				$result = ImportTable("export_config.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of lockout table successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of lockout table failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore language settings 
			if (isset($_POST["um_lang"])) {
				$result = ImportTable("export_lang_settings.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of language settings successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of language settings failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore changes
			if (isset($_POST["um_changes"])) {
				$result = ImportTable("export_changes.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of changes successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of changes failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore gedcom settings
			if (isset($_POST["um_gedsets"])) {
				$result = ImportTable("export_gedcomsettings.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of gedcom settings successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of gedcom settings failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore usersettings
			if (isset($_POST["um_usinfo"])) {
				$result = ImportTable("export_users.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of usersettings successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of usersettings failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore MyPages
			if (isset($_POST["um_mypages"])) {
				$result = ImportTable("export_mypages.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of MyPages successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of MyPages failed with error: ".$result, "E", "S");
					$error = true;
				}
			}

			// Restore Actions
			if (isset($_POST["um_actions"])) {
				$result = ImportTable("export_actions.sql");
				if (empty($result)) WriteToLog("Restore-> Restore of ToDo's successful", "I", "S");
				else {
					WriteToLog("Restore-> Restore of ToDo's failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Print the result report
			?>
			<div class="shade2 center">
				<?php print $gm_lang["um_results"]; ?>
			</div>
			<div class="shade1">
				<?php if ($error) print "<a href=\"javascript: ".$gm_lang["view_syslog"]."\" onclick=\"window.open('viewlog.php?cat=S&amp;max=20&amp;type=E', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">".$gm_lang["um_res_errors"]."</a>";
				else print $gm_lang["um_res_success"];
				?>
			</div>
			<?php
		}
	}
	@set_time_limit($TIME_LIMIT);
	?>
</div>
<?php


function GetMediaFiles($gedid="") {
	global $MEDIA_IN_DB;
	
	$mlist = array();
	$sql = "SELECT m_file from ".TBLPREFIX."media WHERE m_file NOT LIKE '%://%'";
	if (!empty($gedid)) $sql .= " AND m_gedfile='".$gedid."'";
	$sql .= "ORDER BY m_file ASC";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		$row = db_cleanup($row);
		if ($MEDIA_IN_DB || file_exists($row[0])) $mlist[] = $row[0];
	}
	return array_flip(array_flip($mlist));
}
print_footer();
?>
