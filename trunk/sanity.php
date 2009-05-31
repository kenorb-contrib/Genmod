<?php
/**
 * Sanity check on Genmod.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2002 to 2005  Genmod Development Team
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
 * This Page Is Valid XHTML 1.0 Transitional! > 30 August 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: sanity.php,v 1.10 2006/04/09 15:53:27 roland-d Exp $
 */

/**
 * load configuration and context
 */
require "config.php";
require $GM_BASE_DIRECTORY.$confighelpfile["english"];
global $TEXT_DIRECTION;
include "includes/functions_edit.php";

//-- make sure that they have gedcom admin status before they can use this page
//-- otherwise have them login again
if (!userGedcomAdmin($gm_username)) {
	header("Location: login.php?url=sanity.php");
	exit;
}
print_header("Genmod ".$gm_lang["sc_sanity_check_short"]);

// init vars
if (!isset($check_components)) $check_components = "";
$check_gedcoms = "";
foreach($GEDCOMS as $ged => $value) {
	$var = "check_gedcoms".$value["id"];
	if (isset($$var)) {
		$check_gedcoms = "yes";
	}
}
if (!isset($check_settings)) $check_settings = "";
if (!isset($check_filesys)) $check_filesys = "";
if (!isset($action)) $action = "";
?>
<div id="sanity_content" class="width60">
<?php
// Display the options menu
if (empty($action)) {
	?>
	<form action="<?php print $SCRIPT_NAME; ?>" method="post">
	<input type="hidden" name="action" value="checksanity" />
	<table class="width100">
	<tr><td class="facts_label center" colspan="2"><h2><?php print $gm_lang["sc_sanity_check"]; ?></h2></td></tr>
		<tr><td colspan="2" class="topbottombar"><?php print $gm_lang["options"]; ?></td></tr>
		<tr><td class="shade2"><?php print $gm_lang["sc_check_components"]; ?></td><td class="shade1"><input type="checkbox" name="check_components" value="yes" /></td></tr>
		<?php if (count($GEDCOMS) > 0) { ?>
		<tr><td class="shade2"><?php print $gm_lang["sc_check_sys_settings"]; ?></td><td class="shade1"><input type="checkbox" name="check_settings" value="yes" /></td></tr><?php } ?>
		<tr><td class="shade2"><?php print $gm_lang["sc_ged_sets"]; ?></td><td class="shade1"><?php
		foreach($GEDCOMS as $ged => $value) {
			print "<input type=\"checkbox\" name=\"check_gedcoms".$value["id"]."\" value=\"yes\" />&nbsp;".$value["title"]."<br />";
		}
		?></td></tr>
		<tr><td class="shade2"><?php print $gm_lang["sc_fs_security"]; ?></td><td class="shade1"><input type="checkbox" name="check_filesys" value="yes" /></td></tr>
		<tr><td style="padding: 5px" colspan="2" class="center"><button type="submit" name="submit"><?php print $gm_lang["sc_start"]; ?></button>
		<input type="button" value="<?php print $gm_lang["lang_back_admin"];?>"  onclick="window.location='admin.php';"/></td></tr>
	</table></form><br /><br />
	</div>
	<?php
	print_footer();
	exit;
}

//-- Include the requirements here
$min_php_version = "4.3";
$min_mysql_version = "4.0";
$min_memory_limit = "32";
$php_ini_settings = array(
	"output_buffering"=>"4096", 
	"allow_call_time_pass_reference"=>"Off", 
	"register_globals"=>"Off", 
	"register_argc_argv"=>"Off", 
	"magic_quotes_gpc"=>"On", 
	"session.auto_start"=>"0", 
	"memory_limit"=>"16", 
	"max_execution_time"=>"30"
	); 

//-- Array of translated boolean values
$boolean["off"] = "0";
$boolean["Off"] = "0";
$boolean[""] = "0";
$boolean["On"] = "1";
$boolean["on"] = "1";
$boolean["true"] = "1";
$boolean["false"] = "0";
$boolean["1"] = "1";
$boolean["0"] = "0";

//-- print header lines
print "<table class=\"width100\">";
print "<tr><td class=\"facts_label center\" colspan=\"2\"><h2>".$gm_lang["sc_sanity_check"]."</h2></td></tr>";

if (!empty($check_components)) {
	//-- Platform components
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_check_components"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";

		// Check versions
		// PHP
		print "<tr><td class=\"shade1\">".$gm_lang["sc_php"]."</td><td class=\"shade1 wrap\">";
		if (phpversion()< $min_php_version) print $gm_lang["sc_php_low"]."<br />".$gm_lang["sc_ver_req"]." ".$min_php_version;
		else print $gm_lang["sc_ok"];
		print "<br />".$gm_lang["sc_ver_found"]." ".phpversion()."</td></tr>";
		// Presence of GD
		print "<tr><td class=\"shade1\">".$gm_lang["sc_gd"]."</td><td class=\"shade1\">";
		if (!defined("IMG_ARC_PIE")) print $gm_lang["sc_gd_missing"];
		else {
			print $gm_lang["sc_ok"]."<br />".$gm_lang["sc_ver_found"]." ";
			$gd_data = gd_info();
			print $gd_data["GD Version"];
		}
		print "</td></tr>";

		// MySQL
		print "<tr><td class=\"shade1\">".$gm_lang["sc_mysql"]."</td><td class=\"shade1 wrap\">";
		if (substr(mysql_get_server_info(), 0, strlen($min_mysql_version)-1) < $min_mysql_version) print $gm_lang["sc_mysql_low"]."<br />".$gm_lang["sc_ver_req"]." ".$min_mysql_version;
		else print $gm_lang["sc_ok"];
		print "<br />".$gm_lang["sc_ver_found"]." ".mysql_get_server_info()."</td></tr>";
}

if (!empty($check_settings)) {
	//-- PHP and MySQL settings
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_check_sys_settings"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";
	
		// PHP ini settings
		print "<tr><td class=\"shade1\">".$gm_lang["sc_php_ini"]."</td><td class=\"shade1 wrap\">";
		$error_printed = false;
		foreach($php_ini_settings as $setting => $value) {
			$equal = false;
			$inival = trim(ini_get($setting));
			if (isset($boolean[$value]) && isset($boolean[$inival])) {
				if ($boolean[$inival] == $boolean[$value]) $equal = true;
			}
			else if (strtoupper($inival) == strtoupper($value)) $equal = true;
			else if (is_numeric($inival) && is_numeric($value) && $inival >= $value) $equal = true;
			if ($equal != true) {
				if (!$error_printed) {
					print $gm_lang["sc_ini_faults"];
					$error_printed = true;
				}
				print "<br /><br />".$gm_lang["sc_ini_name"]." ".$setting."<br />";
				print $gm_lang["sc_value_req"]." ".$value."<br />";
				print $gm_lang["sc_value_found"]." ".$inival;
				if (empty($inival)) print $gm_lang["sc_not_set"];
			}
		}
				
		if ($error_printed == false) print $gm_lang["sc_ok"];
		print "</td></tr>";
		
		// MySQL connect user rights
		print "<tr><td class=\"shade1\">".$gm_lang["sc_mysql_user"]."</td><td class=\"shade1 wrap\">";
		$res = mysql_query("DROP TABLE ".$TBLPREFIX."testsanity");
		$TOTAL_QUERIES++;
		$res = mysql_query("CREATE TABLE ".$TBLPREFIX."testsanity (s_text VARCHAR(255))");
		$TOTAL_QUERIES++;
		if (!$res) print $gm_lang["sc_no_rights"];
		else {
			$res = mysql_query("ALTER TABLE ".$TBLPREFIX."testsanity CHANGE s_text s_text VARCHAR(200)");
			$TOTAL_QUERIES++;
			if (!$res) print $gm_lang["sc_no_rights"];
			else {
				$res = mysql_query("INSERT INTO ".$TBLPREFIX."testsanity VALUES ('testdata1')");
				$TOTAL_QUERIES++;
				$res = mysql_query("INSERT INTO ".$TBLPREFIX."testsanity VALUES ('testdata2')");
				$TOTAL_QUERIES++;
				if (!$res) print $gm_lang["sc_no_rights"];
				else {
					$res = mysql_query("UPDATE ".$TBLPREFIX."testsanity SET s_text='newtestdata' WHERE s_text='testdata1'");
					$TOTAL_QUERIES++;
					if (!$res) print $gm_lang["sc_no_rights"];
					else {
						$res = mysql_query("SELECT * FROM ".$TBLPREFIX."testsanity");
						$TOTAL_QUERIES++;
						if (!$res) print $gm_lang["sc_no_rights"];
						else {
							mysql_free_result($res);
							$res = mysql_query("DELETE FROM ".$TBLPREFIX."testsanity WHERE s_text='testdata2'");
							$TOTAL_QUERIES++;
							if (!$res) print $gm_lang["sc_no_rights"];
							else {
								$res = mysql_query("DROP TABLE ".$TBLPREFIX."testsanity");
								$TOTAL_QUERIES++;
								if (!$res) print $gm_lang["sc_no_rights"];
								else print $gm_lang["sc_ok"];
							}
						}
					}
				}
			}
		}
		print "</td></tr>";

		// Obsolete MySQL user rights
		print "<tr><td class=\"shade1\">".$gm_lang["sc_mysql_user_obs"]."</td><td class=\"shade1 wrap\">";
		$errors = false;
		$res = mysql_query("SELECT * FROM MYSQL.USER");
		$TOTAL_QUERIES++;
		if ($res) {
			mysql_free_result($res);
			print $gm_lang["sc_mysql_users"]."<br />";
			$errors = true;
		}
		$res = mysql_query("SHOW DATABASES");
		$TOTAL_QUERIES++;
		if ($res) {
			if (mysql_num_rows($res) > 1) {
				print $gm_lang["sc_mysql_databases"]." ".$DBNAME."."."<br />";
				$errors = true;
			}
			mysql_free_result($res);
		}
		$res = mysql_query("CREATE DATABASE testsanity");
		$TOTAL_QUERIES++;
		if ($res) {
			print $gm_lang["sc_mysql_dbcreate"]."<br />";
			$errors = true;
		}
		$res = mysql_query("DROP DATABASE testsanity");
		$TOTAL_QUERIES++;
		if ($res) {
			print $gm_lang["sc_mysql_dbdrop"]."<br />";
			$errors = true;
		}
		if (!$errors) print $gm_lang["sc_ok"];			
		print "</td></tr>";
		
		// MySQL table optimization
		print "<tr><td class=\"shade1\">".$gm_lang["sc_opt_tables"]."</td><td class=\"shade1 wrap\">";
		$errors = false;
		$res = mysql_query("SHOW TABLES");
		$TOTAL_QUERIES++;
		if (!$res) print $gm_lang["sc_opt_cannot"];
		else {
			while ($row = mysql_fetch_row($res)) {
				mysql_query("OPTIMIZE TABLE ".$row[0]);
				$TOTAL_QUERIES++;
				if (!$res) {
					print $gm_lang["sc_opt_cannot"]." ".$row[0];
					$errors = true;
				}
			}
			print $gm_lang["sc_opt_tables_done"];
		}
		print "</td></tr>";
}
		
if (!empty($check_gedcoms)) {

	//-- GEDCOM settings
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_ged_sets"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";
	$gedsave = $GEDCOM;
	foreach ($GEDCOMS as $ged=>$value) {
		$var = "check_gedcoms".$value["id"];
		if (isset($$var)) {
			// Check settings per GEDCOM
			$id = $value["id"];
			$GEDCOM = $ged;
			//-- As ReadGedcomConfig also changes the language, we must save and restore it after reading the config
			$lang = $LANGUAGE;
			ReadGedcomConfig($GEDCOM);
			$LANGUAGE = $lang;
			ReadPrivacy($GEDCOM);
			print "<tr><td class=\"shade1 wrap\" rowspan=\"14\">".$value["title"]."</td><td class=\"shade1 wrap\">";
			$sql = "SELECT * FROM `gm_individuals` WHERE i_gedcom NOT LIKE '%1 FAMS%' AND i_file='".$id."' AND i_gedcom NOT LIKE '%1 FAMC%'";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			$found = false;
			$num = 0;
			if ($res) {
				while ($row = mysql_fetch_row($res)) {
					$num++;
					if (!$found) {
						$found = true;
						print $gm_lang["sc_ged_unlink"];
					}
					print "<br />";
					print_list_person($row[0], array(get_person_name($row[0]), $GEDCOM));
				}
				if ($found) print "<br />";
			}
			if (!$found) print $gm_lang["sc_ged_nounlink"];
			else print $gm_lang["sc_numrecs_found"]." ".$num;
			print "</td></tr>";

			// Get the partial indilist
			$sql = "SELECT i_id, i_gedcom FROM ".$TBLPREFIX."individuals WHERE i_file='".$id."'";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			if ($res) {
				$cindilist = array();
				while ($row = mysql_fetch_assoc($res)) {
					$cindilist[$row["i_id"]]{"gedcom"} = $row["i_gedcom"];
				}
			}
		
			// Get the partial famlist
			$sql = "SELECT f_id, f_gedcom FROM ".$TBLPREFIX."families WHERE f_file='".$id."'";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			if ($res) {
				$cfamlist = array();
				while ($row = mysql_fetch_assoc($res)) {
					$cfamlist[$row["f_id"]]["gedcom"] = $row["f_gedcom"];
				}
			}
		
			// Get the partial sourcelist
			$sql = "SELECT s_id, s_gedcom FROM ".$TBLPREFIX."sources WHERE s_file='".$id."'";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			if ($res) {
				$csourcelist = array();
				while ($row = mysql_fetch_assoc($res)) {
					$csource = array();
					$csource["gedcom"] = $row["s_gedcom"];
					$csource["in_use"] = false;
					$csourcelist[$row["s_id"]] = $csource;
				}
			}

			// Get the partial repolist
			$sql = "SELECT o_id, o_gedcom FROM ".$TBLPREFIX."other WHERE o_file='".$id."' AND o_type='REPO'";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			if ($res) {
				$crepolist = array();
				while ($row = mysql_fetch_assoc($res)) {
					$crepo = array();
					$crepo["gedcom"] = $row["o_gedcom"];
					$crepo["in_use"] = false;
					$crepolist[$row["o_id"]] = $crepo;
				}
			}
			
			// Get the partial MMlist
			$sql = "SELECT m_media, m_gedrec FROM ".$TBLPREFIX."media WHERE m_gedfile='".$id."'";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			if ($res) {
				$cmedialist = array();
				while ($row = mysql_fetch_assoc($res)) {
					$cmedia = array();
					$cmedia["gedcom"] = $row["m_gedrec"];
					$cmedia["in_use"] = false;
					$cmedialist[$row["m_media"]] = $cmedia;
				}
			}
		
			// Check for reference to non existing sources for indi's
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cindilist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d SOUR @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$sid) {
						$num++;
						$sid = strtoupper($sid);
						if (isset($csourcelist[$sid])) $csourcelist[$sid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_sref"];
							}
							print "<br />".$gm_lang["source"]." ".$sid."<br />";
							print_list_person($key, array(get_person_name($key), $GEDCOM));
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_sref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";

			// Check for reference to non existing sources for media
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cmedialist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d SOUR @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$sid) {
						$num++;
						$sid = strtoupper($sid);
						if (isset($csourcelist[$sid])) $csourcelist[$sid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_sref_media"];
							}
							print "<br />".$gm_lang["source"]." ".$sid."<br />";
							print $gm_lang["sc_media"]." ".$key."<br />";
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_sref_media"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
			
			// Check for reference to non existing sources for fams
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cfamlist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d SOUR @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$sid) {
						$num++;
						$sid = strtoupper($sid);
						if (isset($csourcelist[$sid])) $csourcelist[$sid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_sref_fam"];
							}
							print "<br />".$gm_lang["source"]." ".$sid."<br />";
							print_list_family($key, array(get_family_descriptor($key), $GEDCOM));
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_sref_fam"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
		
			// Check for reference to non existing repo for sources
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($csourcelist as $key=>$source) {
				$s = preg_match_all("/REPO @(.+)@/", $source["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$rid) {
						$num++;
						$rid = strtoupper($rid);
						if (isset($crepolist[$rid])) $crepolist[$rid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_rref_sour"];
							}
							print "<br />".$gm_lang["repo"]." ".$rid."<br />";
							$srec = find_repo_record($rid, $GEDCOM);
							print_list_repository($rid, $repolist[$rid]);
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_rref_sour"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print" </td></tr>";
		
			// Check for unreferenced sources
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($csourcelist as $sid=>$value) {
				$num++;
				if (!$value["in_use"]) {
					if (!$error) {
						$error = true;
						print $gm_lang["sc_unu_sref"];
					}
					print "<br />";
					$srec = find_source_record($sid, $GEDCOM);
					print_list_source($sid, $sourcelist[$sid]);
				}
			}
			if (!$error) print $gm_lang["sc_ok_all_sref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";

			// Check for unreferenced repositories
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($crepolist as $rid=>$value) {
				$num++;
				if (!$value["in_use"]) {
					if (!$error) {
						$error = true;
						print $gm_lang["sc_unu_rref"];
					}
					print "<br />";
					$repo = array();
					$tt = preg_match("/1 NAME (.*)/", $value["gedcom"], $match);
					if ($tt == "0") $name = $rid; else $name = $match[1];
					$repo["id"] = $rid;
					$repo["gedfile"] = $id;
					$repo["type"] = "REPO";
					$repo["gedcom"] = $value["gedcom"];
					print_list_repository($name, $repo);
				}
			}
			if (!$error) print $gm_lang["sc_ok_all_rref"]." ";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
	
			// Expand the indi array with Indi->fam 
			foreach($cindilist as $key=>$indi) {
				$s = preg_match_all("/\n1 FAMS @(.+)@/", $indi["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$fid) {
						$cindilist[$key]["FAMS"][$fid] = true;
					}
				}
				$s = preg_match_all("/\n1 FAMC @(.+)@/", $indi["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$fid) {
						$cindilist[$key]["FAMC"][$fid] = true;
					}
				}
			}
	
			// Expand the fam array with fam->indi 
			foreach($cfamlist as $key=>$fam) {
				$s = preg_match_all("/\n1 CHIL @(.+)@/", $fam["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$cid) {
						$cfamlist[$key]["CHIL"][$cid] = true;
					}
				}
				$s = preg_match_all("/\n1 HUSB @(.+)@/", $fam["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$hid) {
						$cfamlist[$key]["HUSB"][$hid] = true;
					}
				}
				$s = preg_match_all("/\n1 WIFE @(.+)@/", $fam["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$hid) {
						$cfamlist[$key]["WIFE"][$hid] = true;
					}
				}
			}
			$num = 0;
			$error = false;
			// Check the indi's for references to fams. If pair of references found, unset them.
			print "<tr><td class=\"shade1 wrap\">";
			foreach($cindilist as $key=>$cindi) {
				foreach($cindi as $role=>$pointerarr) {
					if ($role == "FAMS") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($cfamlist[$pointer]["HUSB"][$key])) {
								unset($cfamlist[$pointer]["HUSB"][$key]);
								unset($cindilist[$key][$role][$pointer]);
							}
							else if (isset($cfamlist[$pointer]["WIFE"][$key])) {
								unset($cfamlist[$pointer]["WIFE"][$key]);
								unset($cindilist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $gm_lang["sc_inv_pointer"]."<br />";
								if (isset($cfamlist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?ged=".$GEDCOM."&amp;pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?ged=".$GEDCOM."&amp;pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." ".$pointer."<br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
					else if ($role == "FAMC") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($cfamlist[$pointer]["CHIL"][$key])) {
								unset($cfamlist[$pointer]["CHIL"][$key]);
								unset($cindilist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $gm_lang["sc_inv_pointer"]."<br />";
								if (isset($cfamlist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?ged=".$GEDCOM."&amp;pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?ged=".$GEDCOM."&pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." ".$pointer."<br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
				}
			}

			// Check the fams's for references to indi's. If pair of references found, unset them. (should not!)
			foreach($cfamlist as $key=>$cfam) {
				foreach($cfam as $role=>$pointerarr) {
					if ($role == "CHIL") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($cindilist[$pointer]["FAMC"][$key])) {
								unset($cindilist[$pointer]["FAMC"][$key]);
								unset($cfamlist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $gm_lang["sc_inv_pointer"]."<br />";
								if (isset($cindilist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_indi"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?ged=".$GEDCOM."&amp;pid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_indi"]."<br />".$gm_lang["sc_indi"]." ".$pointer."<br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
					else if ($role == "HUSB") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($cindilist[$pointer]["FAMS"][$key])) {
								unset($cindilist[$pointer]["FAMS"][$key]);
								unset($cfamlist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $gm_lang["sc_inv_pointer"]."<br />";
								if (isset($cindilist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_indi"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?ged=".$GEDCOM."&amp;pid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_indi"]."<br />".$gm_lang["sc_indi"]." ".$pointer."<br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
					else if ($role == "WIFE") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($cindilist[$pointer]["FAMS"][$key])) {
								unset($cindilist[$pointer]["FAMS"][$key]);
								unset($cfamlist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $gm_lang["sc_inv_pointer"]."<br />";
								if (isset($cindilist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_indi"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?ged=".$GEDCOM."&amp;pid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_indi"]."<br />".$gm_lang["sc_indi"]." ".$pointer."<br />".$gm_lang["sc_fam"]." <a href=\"family.php?ged=".$GEDCOM."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_no_inv_point"]." ";
			else print "<br />";
			print $gm_lang["sc_numpoint_checked"]." ".$num;
			print "</td></tr>";

			// Check for reference to non existing 0 MM records from individuals
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cindilist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d OBJE @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$mid) {
						$num++;
						$mid = strtoupper($mid);
						if (isset($cmedialist[$mid])) $cmedialist[$mid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_mref"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							print_list_person($key, array(get_person_name($key), $GEDCOM));
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_mref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
			
			// Check for reference to non existing 0 MM records from fams
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cfamlist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d OBJE @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$mid) {
						$num++;
						$mid = strtoupper($mid);
						if (isset($cmedialist[$mid])) $cmedialist[$mid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_mref_fam"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							print_list_family($key, array(get_family_descriptor($key), $GEDCOM));
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_mref_fam"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
			
			// Check for reference to non existing 0 MM records from sources
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($csourcelist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d OBJE @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$mid) {
						$num++;
						$mid = strtoupper($mid);
						if (isset($cmedialist[$mid])) $cmedialist[$mid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_mref_sour"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							$srec = find_source_record($key, $GEDCOM);
							print_list_source($key, $sourcelist[$sid]);
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_mref_sour"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";

			// Check for unreferenced mediaitems
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cmedialist as $mid=>$value) {
				$num++;
				if (!$value["in_use"]) {
					if (!$error) {
						$error = true;
						print $gm_lang["sc_unu_mref"];
					}
					print "<br />";
					print $gm_lang["sc_media"]." ".$mid."<br />";
				}
			}
			if (!$error) print $gm_lang["sc_ok_all_mref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";

			// Check for physical files 
			// Build the directorylist
			$dirs = get_dir_list(array($MEDIA_DIRECTORY));
			// exclude the GM trees
			
			$tpath = array($MEDIA_DIRECTORY."/thumbs", "/thumbs/", "./fonts/", "./hooks/", "./images/", "./includes", "./languages/", "./modules/", "./pgvnuke/", "./places/", "./reports/", "./ufpdf/", "./themes/", "./blocks/", $INDEX_DIRECTORY);
			$tpath = str_replace("//", "/", $tpath);
			foreach($dirs as $key => $dir) {
				if ($dir == "./") unset ($dirs[$key]);
				else foreach($tpath as $key2=> $exclude) {
					if (stristr($dir, $exclude)) unset($dirs[$key]);
				}
			}
			// Build the filelist
			$flist = array();
			foreach($dirs as $key => $dir) {
				$d = @dir($dir);
				if (!is_object($d)) {
					print $gm_lang["sc_dir_noaccess"]." ".$dir;
					$errors1 = true;
				}
				else {
					while (false !== ($entry = $d->read())) {
						if(!is_dir($entry) && $entry != ".") {
							$num++;
							$ext = substr($entry, -3);
							if (in_array(strtolower($ext), $MEDIATYPE)) {
								$file = $d->path.$entry;
								$flist[$file] = false;
							}	
						}
					}
				$d->close();
				}
			}

			// Check if all references to files from MM exist
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cmedialist as $mid=>$value) {
				$num++;
				$s = preg_match_all("/\n\d FILE (.+)?/", $value["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$file) {
						$file = trim($file);
						if (substr($file,0,strlen($MEDIA_DIRECTORY)) != $MEDIA_DIRECTORY) $file = $MEDIA_DIRECTORY.$file;
						$num++;
						if (isset($flist[$file])) {
							$flist[$file] = true;
						}
						else {
							if (!$error) {
								$error = true;
								print $gm_lang["sc_inv_mref_file"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							print $gm_lang["sc_file"]." ".$file."<br />";
						}
					}
				}
			}
			if (!$error) print $gm_lang["sc_ok_all_file"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
			
			// Check if all files are referenced to
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($flist as $file=>$ref) {
				$num++;
				if (!$ref) {
					if (!$error) {
						$error = true;
						print $gm_lang["sc_unu_file"];
					}
					print "<br />".$gm_lang["sc_file"]." ".$file;
				}
			}
			if (!$error) print $gm_lang["sc_ok_use_file"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
		}
	}
	$GEDCOM = $gedsave;
	ReadGedcomConfig($GEDCOM);
	ReadPrivacy($GEDCOM);
}

if (!empty($check_filesys)) {
		
	//-- File system security
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_fs_security"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";
	
		// Root dir
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_main"]."</td><td class=\"shade1 wrap\">";
		$dir = "./";
		$errors1 = false;
		if (dir_is_writable($dir)) {
			print $gm_lang["sc_fs_main_error"]."<br />";
			$errors1 = true;
		}
		$d = @dir($dir);
		if (!is_object($d)) print $gm_lang["sc_dir_noaccess"]." ".$dir;
		else {
			$errors2 = false;
			$num = 0;
			while (false !== ($entry = $d->read())) {
				if(!is_dir($entry) && $entry != ".") {
					$num++;
					if (file_is_writeable($d->path."/".$entry)) {
						if (!$errors2) {
							print $gm_lang["sc_fs_filesrw"]."<br />";
							$errors2 = true;
						}
						print $dir.$entry."<br />";
					}
				}
			}
			$d->close();
			print $gm_lang["sc_numchecked"]." ".$num."<br />";
			if (!$errors1 && !$errors2) print $gm_lang["sc_ok"];
		}
		print "</td></tr>";
		
		// Index dir
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_index"]."</td><td class=\"shade1 wrap\">";
		$dir = $INDEX_DIRECTORY;
		$errors1 = false;
		if (!dir_is_writable($dir)) {
			print $gm_lang["sc_fs_index_error"]."<br />";
			$errors1 = true;
		}
		$d = @dir($dir);
		if (!is_object($d)) print $gm_lang["sc_dir_noaccess"]." ".$dir;
		else {
			$errors2 = false;
			$num = 0;
			while (false !== ($entry = $d->read())) {
				if(!is_dir($entry) && $entry != ".") {
					$num++;
					if (!file_is_writeable($d->path."/".$entry)) {
						if (!$errors) {
							print $gm_lang["sc_fs_filesro"]."<br />";
							$errors = true;
						}
						print $dir.$entry."<br />";
					}
				}
			}
			$d->close();
			print $gm_lang["sc_numchecked"]." ".$num."<br />";
			if (!$errors1 && !$errors2) print $gm_lang["sc_ok"];
		}
		print "</td></tr>";
		
		// Languages directory
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_languages"]."</td><td class=\"shade1 wrap\">";
		$dir = "./languages/";
		$write1 = false;
		$num = 0;
		if (dir_is_writable($dir)) $write1 = true;
		$d = @dir($dir);
		if (!is_object($d)) print $gm_lang["sc_dir_noaccess"]." ".$dir;
		else {
			$write2 = false;
			$num = 0;
			while (false !== ($entry = $d->read())) {
				$num++;
				if(!is_dir($entry) && $entry != ".") {
					if (file_is_writeable($d->path."/".$entry)) $write2 = true;
					$num++;
				}
			}
			$d->close();
			print $gm_lang["sc_numchecked"]." ".$num."<br />";
			if (!$write1 && !$write2) print $gm_lang["sc_fs_languages_ro"];
			else print $gm_lang["sc_fs_languages_rw"];
		}
		print "</td></tr>";
		
		// Media directories
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_media"]."</td><td class=\"shade1 wrap\">";
		$res = mysql_query("SELECT gc_media_directory, gc_gedcom FROM ".$TBLPREFIX."gedconf");
		$TOTAL_QUERIES++;
		if ($res) {
			$dirs = array();
			while ($row = mysql_fetch_row($res)) {
				$dirs[$row[0]] = $row[1];
			}
			$first = true;
			foreach ($dirs as $dir=> $value) {
				if (!$first) {
					print "<br />";
				}
				print $gm_lang["sc_gedname"].$GEDCOMS[$value]["title"]."<br />";
				if (!dir_is_writable($dir)) print $gm_lang["sc_fs_media_ro"]." ".$dir;
				else print $gm_lang["sc_fs_media_rw"]." ".$dir;
				print "<br />";
				$first = false;
			}
		}
		print "</td></tr>";
		
		// All other files and dirs
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_other"]."</td><td class=\"shade1 wrap\">";
		$dirs = get_dir_list(array("./blocks/", "./fonts/", "./hooks/", "./images/", "./includes/", "./modules/", "./places/", "./reports/", "./themes/", "./ufpdf/"));
		$errors1 = false;
		foreach ($dirs as $key=>$dir) {
			if (dir_is_writable($dir)) {
				if (!$errors1) {
					$errors1 = true;
					print $gm_lang["sc_fs_dirrw"]."<br />";
				}
				print $dir."<br />";
			}
		}
		if ($errors1) print "<br />";
		$errors2 = false;
		$num = 0;
		foreach ($dirs as $key=>$dir) {
			$d = @dir($dir);
			if (!is_object($d)) {
				print $gm_lang["sc_dir_noaccess"]." ".$dir;
				$errors1 = true;
			}
			else {
				while (false !== ($entry = $d->read())) {
					if(!is_dir($entry) && $entry != ".") {
						$num++;
						if (file_is_writeable($d->path."/".$entry)) {
							if (!$errors2) {
								print $gm_lang["sc_fs_filesrw"]."<br />";
								$errors2 = true;
							}
						print $d->path.$entry."<br />";
						}
					}
				}
			$d->close();
			}
		}
		print $gm_lang["sc_numchecked"]." ".$num."<br />";
		if (!$errors1 && !$errors2) print $gm_lang["sc_ok"];
		print "</td></tr>";
}
print "<tr><td class=\"center\" colspan=\"2\"><input type=\"button\" value=\"".$gm_lang["lang_back_admin"]."\" onclick=\"window.location='admin.php';\" /></td></tr>";
print "</table><br />";
print "</div>";
print_footer();
?>