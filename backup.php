<?php
/**
 * Backups and restores Genmod related info.
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
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id: backup.php 29 2022-07-17 13:18:20Z Boudewijn $
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
	if (SystemConfig::$MEDIA_IN_DB && (2 * MediaFS::GetTotalMediaSize() >= disk_free_space(INDEX_DIRECTORY) || !MediaFS::DirIsWritable(GedcomConfig::$MEDIA_DIRECTORY, false))) $nomedia = true;
	else $nomedia = false;
}

switch ($action) {
	case "backup" :
		PrintHeader(GM_LANG_um_backup);
		break;
	case "restore" :
		PrintHeader(GM_LANG_um_restore);
		break;
}
?>
<!-- Setup the left box -->
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
</div>
<div id="AdminColumnMiddle">
	<?php
	// Backup part
	if ($action == "backup") { ?>
		<form action="<?php print SCRIPT_NAME; ?>" method="post">
		<input type="hidden" name="action" value="backup" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
				<span class="AdminNavBlockTitle">
					<?php PrintHelpLink("um_bu_explain","qm","um_backup"); print GM_LANG_um_backup;?>
				</span>
			</td>
		</tr>
		<?php
		// If first time, let the user choose the options
		if ($step == 1) {
			?>
			<?php if ($nomedia) { 
				print "<tr><td class=\"NavBlockColumnHeader\"><br />".GM_LANG_um_nomedia."<br /><br /></td></tr>";
			}
			?>	
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_config; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_config" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_lang; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_lang" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_gedcoms; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_gedcoms" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_media; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_media" value="yes" <?php if ($nomedia) { ?> disabled="disabled" <?php } else { ?> checked="checked" <?php } ?>  /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_gedsets; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_gedsets" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_logs; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_logs" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_usinfo; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_usinfo" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_mypages; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_mypages" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td class="NavBlockLabel"><?php print GM_LANG_um_bu_actions; ?></td>
				<td class="NavBlockField"><input type="checkbox" name="um_actions" value="yes" checked="checked" /></td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockFooter">
					<input type="submit" name="submit" value="<?php print GM_LANG_um_mk_bu; ?>" />
					<input type="hidden" name="step" value="2" />
				</td>
			</tr>
			</table>
		</form>
		<?php
		}
		else {
			$time_limit = GedcomConfig::GetHighMaxTime();
			@set_time_limit($time_limit);
			// print "Time limit set to ".$time_limit;
			
			if ($step == 2 && isset($um_media) && SystemConfig::$MEDIA_IN_DB) {
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
					MediaFS::CreateFile(RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY.MediaFS::CheckMediaDepth($mfile)));
				}
				print "<tr><td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_um_mediaexp."</td></tr>";
				print "<tr><td class=\"NavBlockFooter\">";
				print "<input type=\"submit\" name=\"submit\" value=\"".GM_LANG_um_proceed_bu."\" />";
				print "</td></tr></table>";
				print "</form>";
				PrintFooter();
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
					$fn = AdminFunctions::ExportTable($tables, "yes", "config");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup MyPages
				if (isset($_POST["um_mypages"])) {
					$tables = array("pages");
					$fn = AdminFunctions::ExportTable($tables, "yes", "mypages");
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
					$fn = AdminFunctions::ExportTable("lang_settings");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup gedcoms and changes
				if (isset($_POST["um_gedcoms"])) {
					foreach($GEDCOMS as $key=> $gedcom) {
						$ged = $gedcom["gedcom"];
						if (file_exists(INDEX_DIRECTORY."export_".$ged)) unlink(INDEX_DIRECTORY."export_".$ged);
						$gedname = INDEX_DIRECTORY."export_".$ged;
						AdminFunctions::PrintGedcom($ged, "no", "no", "yes", "no", "", $gedname, "", "", "yes");
						$flist[] = $gedname;
					}
					$fn = AdminFunctions::ExportTable("changes");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup multimediafiles in GM
				if (isset($_POST["um_media"])) {
					$mlist = GetMediaFiles(GedcomConfig::$GEDCOMID);
					foreach ($mlist as $key => $mfile) {
						// While filling the medialist, it was already checked if the file exists. Just add it here
						$flist[] = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY.MediaFS::CheckMediaDepth($mfile));
					}
				}
				
				// Backup gedcom settings
				if (isset($_POST["um_gedsets"])) {
					$tables = array("gedcoms", "gedconf", "privacy");
					$fn = AdminFunctions::ExportTable($tables, "yes", "gedcomsettings");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
			
				// Backup logfiles and counters
				if (isset($_POST["um_logs"])) {
			
					// Gedcom counters
					$fn = AdminFunctions::ExportTable("counters");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
			
					// Gedcom searchlogs, changelogs and systemlogs
					$fn = AdminFunctions::ExportTable("log");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup user information
				if (isset($_POST["um_usinfo"])) {
					$tables = array("users", "users_gedcoms", "news", "messages", "blocks", "favorites", "faqs");
					$fn = AdminFunctions::ExportTable($tables, "yes", "users");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Backup Actions
				if (isset($_POST["um_actions"])) {
					$fn = AdminFunctions::ExportTable("actions");
					if (!empty($fn)) $flist = array_merge($fn, $flist);
				}
				
				// Make the zip
				?>
				<tr>
					<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
						<?php print GM_LANG_um_results; ?>
					</td>
				</tr>
				<tr>
					<td class="NavBlockField">
				<?php
				if (count($flist) > 0) {
					require "includes/pclzip.lib.php";
					$buname = adodb_date("YmdHis").".zip";
					$fname = INDEX_DIRECTORY.$buname;
					$comment = "Created by Genmod ".GM_VERSION." ".GM_VERSION_RELEASE." on ".adodb_date("r").".";
					$archive = new PclZip($fname);
					$v_list = $archive->create($flist, PCLZIP_OPT_COMMENT, $comment);
					if ($v_list == 0) print "Error : ".$archive->errorInfo(true);
					else {
						print GM_LANG_um_zip_succ."<br />";
						$url = SERVER_URL;
						if (substr($url,-1,1)!="/") $url .= "/";
						print "<a href=".$url."downloadbackup.php?fname=".urlencode($fname)." target=_blank>".GM_LANG_um_zip_dl." ".$fname."</a>  (";
						printf ("%.0f Kb", (filesize($fname)/1024));
						print ")";
					}
					print "</td></tr>";
					
					// Remove temporary files again
					foreach ($flist as $key=>$fn) {
						if (substr($fn, strlen(INDEX_DIRECTORY), 7) == "export_") unlink($fn);
					}
				}
				else print GM_LANG_um_nofiles."<br /></td></tr>";
			}
			
			@set_time_limit($time_limit);
			print "</table></form>";
		}
	}
	
	if ($action == "restore") { ?>
		<form action="<?php print SCRIPT_NAME; ?>" method="post">
		<input type="hidden" name="action" value="restore" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
				<span class="AdminNavBlockTitle">
					<?php print GM_LANG_um_restore;?>
				</span>
			</td>
		</tr>
		<?php
		// If first time, let the user choose the options
		if (!isset($_POST["um_counters"]) && !isset($_POST["um_changes"]) && !isset($_POST["um_gedsets"]) && !isset($_POST["um_logs"]) && !isset($_POST["um_usinfo"]) && !isset($_POST["um_mypages"]) && !isset($_POST["um_config"]) && !isset($_POST["um_actions"])) {
			$handle = opendir(INDEX_DIRECTORY);
			
			// Check if export files exist in the index dir. If not, we will display a message
			$files = array();
			while (false !== ($file = readdir($handle))) {
				if (substr($file, -4) == ".sql") $files[] = $file;
			}
			$found = false;
			?>
			
				<?php 
				if(in_array("export_config.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_config; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_config" value="yes" /></td>
					</tr>
				<?php }
				if(in_array("export_lang_settings.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_lang; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_lang" value="yes" /></td>
					</tr>
				<?php }
				if(in_array("export_changes.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_changes; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_changes" value="yes" /></td>
					</tr>
				<?php }
				if(in_array("export_gedcomsettings.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_gedsets; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_gedsets" value="yes" /></td>
					</tr>
				<?php }
				if(in_array("export_log.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_logs; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_logs" value="yes" /></td>
					</tr>
				<?php }
				if(in_array("export_users.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_usinfo; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_usinfo" value="yes" /></td>
					</tr>
				<?php }
				if(in_array("export_mypages.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_mypages; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_mypages" value="yes" /></td>
					</tr>
				<?php }
				if(in_array("export_actions.sql", $files)) { 
					$found = true;
					?>
					<tr>
						<td class="NavBlockLabel"><?php print GM_LANG_um_res_actions; ?></td>
						<td class="NavBlockField"><input type="checkbox" name="um_actions" value="yes" /></td>
					</tr>
				<?php }
				if (!$found) { ?>
					<tr>
						<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
							<?php print GM_LANG_um_res_nofiles; ?>
						</td>
					</tr>
				<?php }
				else { ?>
					<tr>
						<td colspan="2" class="NavBlockFooter">
							<input type="submit" name="submit" value="<?php print GM_LANG_um_mk_res; ?>" />
						</td>
					</tr>
				<?php } ?>
			</table>
			</form>
			<?php
		}
		else {
			$time_limit = GedcomConfig::GetHighMaxTime();
			@set_time_limit($time_limit);
		
			$error = false;
			// Restore logfiles. Do this first, so we can write the restore results to the logfile.
			if (isset($_POST["um_logs"])) {
				$result = AdminFunctions::ImportTable("export_log.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of logfiles and counters successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of logfiles and counters failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore lockout table
			if (isset($_POST["um_config"])) {
				$result = AdminFunctions::ImportTable("export_config.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of lockout table successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of lockout table failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore language settings 
			if (isset($_POST["um_lang"])) {
				$result = AdminFunctions::ImportTable("export_lang_settings.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of language settings successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of language settings failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore changes
			if (isset($_POST["um_changes"])) {
				$result = AdminFunctions::ImportTable("export_changes.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of changes successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of changes failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore gedcom settings
			if (isset($_POST["um_gedsets"])) {
				$result = AdminFunctions::ImportTable("export_gedcomsettings.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of gedcom settings successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of gedcom settings failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore usersettings
			if (isset($_POST["um_usinfo"])) {
				$result = AdminFunctions::ImportTable("export_users.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of usersettings successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of usersettings failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Restore MyPages
			if (isset($_POST["um_mypages"])) {
				$result = AdminFunctions::ImportTable("export_mypages.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of MyPages successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of MyPages failed with error: ".$result, "E", "S");
					$error = true;
				}
			}

			// Restore Actions
			if (isset($_POST["um_actions"])) {
				$result = AdminFunctions::ImportTable("export_actions.sql");
				if (empty($result)) WriteToLog("Restore-&gt; Restore of ToDo's successful", "I", "S");
				else {
					WriteToLog("Restore-&gt; Restore of ToDo's failed with error: ".$result, "E", "S");
					$error = true;
				}
			}
			
			// Print the result report
			?>
			<tr>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
					<?php print GM_LANG_um_results; ?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel">
					<?php if ($error) print "<a href=\"javascript: ".GM_LANG_view_syslog."\" onclick=\"window.open('viewlog.php?cat=S&amp;max=20&amp;type=E', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">".GM_LANG_um_res_errors."</a>";
					else print GM_LANG_um_res_success;
					?>
				</td>
			</tr>
			</table>
			</form>
			<?php
		}
	}
	@set_time_limit(GedcomConfig::$TIME_LIMIT);
	?>
</div>
<?php


function GetMediaFiles($gedid="") {
	
	$mlist = array();
	$sql = "SELECT m_mfile from ".TBLPREFIX."media WHERE m_mfile NOT LIKE '%://%'";
	if (!empty($gedid)) $sql .= " AND m_file='".$gedid."'";
	$sql .= "ORDER BY m_mfile ASC";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		if (SystemConfig::$MEDIA_IN_DB || file_exists(RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY.MediaFS::CheckMediaDepth($row[0])))) $mlist[] = $row[0];
	}
	return array_flip(array_flip($mlist));
}
PrintFooter();
?>
