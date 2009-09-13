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
 * @version $Id$
 */

/**
 * load configuration and context
 */
require "config.php";
global $TEXT_DIRECTION;
include "includes/functions/functions_edit.php";

//-- make sure that they have gedcom admin status before they can use this page
//-- otherwise have them login again
if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=sanity.php");
	else header("Location: ".LOGIN_URL."?url=sanity.php");
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
if (!isset($check_oldgeds)) $check_oldgeds = "";
if (!isset($check_gedtags)) $check_gedtags = "";
if (!isset($check_unlinked)) $check_unlinked = "";
if (!isset($check_cits)) $check_sits = "";
if (!isset($action)) $action = "";
$info_icon = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".$gm_lang["information"]."\" />&nbsp;";
$warn_icon = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".$gm_lang["warning"]."\" />&nbsp;";
$error_icon = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".$gm_lang["error"]."\" />&nbsp;";
?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print $gm_lang["admin"];?></a></div>
	<div class="admin_link"><a href="admin_maint.php"><?php print $gm_lang["administration_maintenance"];?></a></div>
	<?php if (!empty($action)) { ?><div class="admin_link"><a href="sanity.php"><?php print $gm_lang["sc_sanity_check"];?></a></div><?php } ?>
</div>
<div id="content">
	<div class="admin_topbottombar">
		<h3><?php print $gm_lang["sc_sanity_check"];?></h3>
	</div>
<?php
// Display the options menu
if (empty($action)) {
	?>

	<form action="<?php print $SCRIPT_NAME; ?>" method="post">
	<input type="hidden" name="action" value="checksanity" />
	<table class="width100">
		<tr><td colspan="2" class="topbottombar"><?php print $gm_lang["options"]; ?></td></tr>
		<tr><td class="shade2"><?php print $gm_lang["sc_check_components"]; ?></td><td class="shade1"><input type="checkbox" name="check_components" value="yes" /></td></tr>
		<tr><td class="shade2"><?php print $gm_lang["sc_check_sys_settings"]; ?></td><td class="shade1"><input type="checkbox" name="check_settings" value="yes" /></td></tr>
		<tr><td class="shade2"><?php print $gm_lang["sc_check_oldgeds"]; ?></td><td class="shade1"><input type="checkbox" name="check_oldgeds" value="yes" /></td></tr>
		<?php if (count($GEDCOMS) > 0) { ?>
		<tr><td class="shade2"><?php print $gm_lang["sc_ged_sets"]; ?></td><td class="shade1"><?php
		foreach($GEDCOMS as $ged => $value) {
			print "<span style=\"vertical-align: 25%\"><input type=\"checkbox\" name=\"check_gedcoms".$value["id"]."\" value=\"yes\" />&nbsp;".$value["title"]."</span><br />";
		}
		print "<span style=\"vertical-align: 25%\">".$gm_lang["options"]."</span><br />";
		print "<span style=\"vertical-align: 25%\"><input type=\"checkbox\" name=\"check_gedtags\" value=\"yes\" />&nbsp;".$gm_lang["sc_check_gedtags"]."</span><br />";
		print "<span style=\"vertical-align: 25%\"><input type=\"checkbox\" name=\"check_cits\" value=\"yes\" />&nbsp;".$gm_lang["sc_cits"]."</span><br />";
		print "<span style=\"vertical-align: 25%\"><input type=\"checkbox\" name=\"check_unlinked\" value=\"yes\" />&nbsp;".$gm_lang["sc_ged_displ_unlinked"]."</span><br />";
		?></td></tr><?php } ?>
		<tr><td class="shade2"><?php print $gm_lang["sc_fs_security"]; ?></td><td class="shade1"><input type="checkbox" name="check_filesys" value="yes" /></td></tr>
		<tr><td style="padding: 5px" colspan="2" class="center"><span style="vertical-align: 25%"><button type="submit" name="submit"><?php print $gm_lang["sc_start"]; ?></button>
		</span></td></tr>
	</table></form><br /><br />
	</div>
	<?php
	print_footer();
	exit;
}

//-- Include the requirements here
$min_php_version = "5.0";
$min_mysql_version = "5.0.3";
$min_memory_limit = "32";
$php_ini_settings = array(
	"output_buffering"=>"4096", 
	"allow_call_time_pass_reference"=>"Off", 
	"register_globals"=>"Off", 
	"register_argc_argv"=>"Off", 
	"magic_quotes_gpc"=>"Off", 
	"session.auto_start"=>"0", 
	"memory_limit"=>"32", 
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
if (!empty($check_components)) {
	//-- Platform components
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_check_components"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";

		// Check versions
		// PHP
		print "<tr><td class=\"shade1\">".$gm_lang["sc_php"]."</td><td class=\"shade1 wrap\">";
		if (phpversion()< $min_php_version) print $error_icon.$gm_lang["sc_php_low"]."<br />".$gm_lang["sc_ver_req"]." ".$min_php_version;
		else print $info_icon.$gm_lang["sc_ok"];
		print "<br />".$gm_lang["sc_ver_found"]." ".phpversion()."</td></tr>";
		// Presence of GD
		print "<tr><td class=\"shade1\">".$gm_lang["sc_gd"]."</td><td class=\"shade1\">";
		if (!defined("IMG_ARC_PIE")) print $warn_icon.$gm_lang["sc_gd_missing"];
		else {
			print $info_icon.$gm_lang["sc_ok"]."<br />".$gm_lang["sc_ver_found"]." ";
			$gd_data = gd_info();
			print $gd_data["GD Version"];
		}
		print "</td></tr>";

		// MySQL
		print "<tr><td class=\"shade1\">".$gm_lang["sc_mysql"]."</td><td class=\"shade1 wrap\">";
		if (substr(mysql_get_server_info(), 0, strlen($min_mysql_version)) < $min_mysql_version) print $error_icon.$gm_lang["sc_mysql_low"]."<br />".$gm_lang["sc_ver_req"]." ".$min_mysql_version;
		else print $info_icon.$gm_lang["sc_ok"];
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
			else if ($setting == "memory_limit") {
				$inival = preg_replace("/[a-zA-Z]+/", "", $inival);
				if (is_numeric($value) && $inival >= $value) $equal = true;
			}
			if ($equal != true) {
				if (!$error_printed) {
					print $warn_icon.$gm_lang["sc_ini_faults"];
					$error_printed = true;
				}
				print "<br /><br />".$gm_lang["sc_ini_name"]." ".$setting."<br />";
				print $gm_lang["sc_value_req"]." ".$value."<br />";
				print $gm_lang["sc_value_found"]." ".$inival;
				if (empty($inival)) print $gm_lang["sc_not_set"];
			}
		}
				
		if ($error_printed == false) print $info_icon.$gm_lang["sc_ok"];
		print "</td></tr>";
		
		// MySQL connect user rights
		print "<tr><td class=\"shade1\">".$gm_lang["sc_mysql_user"]."</td><td class=\"shade1 wrap\">";
		$res = NewQuery("DROP TABLE ".TBLPREFIX."testsanity", true);
		$res = NewQuery("CREATE TABLE ".TBLPREFIX."testsanity (s_text VARCHAR(255))", true);
		if (!$res) print $error_icon.$gm_lang["sc_no_rights"];
		else {
			$res = NewQuery("ALTER TABLE ".TBLPREFIX."testsanity CHANGE s_text s_text VARCHAR(200)", true);
			if (!$res) print $error_icon.$gm_lang["sc_no_rights"];
			else {
				$res = NewQuery("INSERT INTO ".TBLPREFIX."testsanity VALUES ('testdata1')", true);
				$res = NewQuery("INSERT INTO ".TBLPREFIX."testsanity VALUES ('testdata2')", true);
				if (!$res) print $error_icon.$gm_lang["sc_no_rights"];
				else {
					$res = NewQuery("UPDATE ".TBLPREFIX."testsanity SET s_text='newtestdata' WHERE s_text='testdata1'", true);
					if (!$res) print $error_icon.$gm_lang["sc_no_rights"];
					else {
						$res = NewQuery("SELECT * FROM ".TBLPREFIX."testsanity", true);
						if (!$res) print $error_icon.$gm_lang["sc_no_rights"];
						else {
							$res->FreeResult();
							$res = NewQuery("DELETE FROM ".TBLPREFIX."testsanity WHERE s_text='testdata2'", true);
							if (!$res) print $error_icon.$gm_lang["sc_no_rights"];
							else {
								$res = NewQuery("DROP TABLE ".TBLPREFIX."testsanity", true);
								if (!$res) print $gm_lang["sc_no_rights"];
								else print $info_icon.$gm_lang["sc_ok"];
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
		$res = NewQuery("SELECT * FROM MYSQL.USER", true);
		if ($res) {
			$res->FreeResult();
			print $gm_lang["sc_mysql_users"]."<br />";
			$errors = true;
		}
		$res = NewQuery("SHOW DATABASES", true);
		if ($res) {
			if ($res->NumRows() > 1) {
				// For MySQL 5 there is always usage access to INFORMATION_SCHEMA.
				while ($row = $res->FetchRow()) {
					if ($row[0] != "information_schema" && $row[0] != DBNAME) {
						print $warn_icon.$gm_lang["sc_mysql_databases"]." ".DBNAME."."."<br />";
						$errors = true;
						break;
					}
				}
			}
			$res->FreeResult();
		}
		$res = NewQuery("CREATE DATABASE testsanity", true);
		if ($res) {
			print $gm_lang["sc_mysql_dbcreate"]."<br />";
			$errors = true;
		}
		$res = NewQuery("DROP DATABASE testsanity", true);
		if ($res) {
			print $gm_lang["sc_mysql_dbdrop"]."<br />";
			$errors = true;
		}
		if (!$errors) print $info_icon.$gm_lang["sc_ok"];			
		print "</td></tr>";
		
		// MySQL table optimization
		print "<tr><td class=\"shade1\">".$gm_lang["sc_opt_tables"]."</td><td class=\"shade1 wrap\">";
		$errors = false;
		$res = NewQuery("SHOW TABLES", true);
		if (!$res) print $warn_icon.$gm_lang["sc_opt_cannot"];
		else {
			while ($row = $res->FetchRow()) {
				NewQuery("OPTIMIZE TABLE ".$row[0], true);
				if (!$res) {
					print $warn_icon.$gm_lang["sc_opt_cannot"]." ".$row[0];
					$errors = true;
				}
			}
			print $info_icon.$gm_lang["sc_opt_tables_done"];
		}
		print "</td></tr>";
}

if (!empty($check_oldgeds)) {
	
	//-- Old gedcom id's: the gedcoms table is leading here
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_check_oldgeds"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";
	print "<tr><td class=\"shade1\">".$gm_lang["sc_check_invgeds"]."</td><td class=\"shade1 wrap\">";
	$geds = array();
	$ageds = array();
	$gednames = array();
	$agednames = array();
	$errors = false;
	foreach($GEDCOMS as $ged => $value) {
		$ageds[] = $value["id"];
		$agednames[] = $value["gedcom"];
	}
	$geds = implode(", ", $ageds);
	$gednames = "'".implode("', '", $agednames)."'";

	// Gedcheck:
	// 1 - fieldname of the gedcom to which the record refers
	// 2 - table name
	// 3 - either 'geds' (gedcom is gedcom id) or 'gednames' (gedcom is gedcom names)
	// 4 - Language variable name of the message that this table contains records of non-existent gedcoms
	$gedcheck = array();
	$gedcheck[] = array("a_file", "actions", "geds", "sc_oldged_actions");
	$gedcheck[] = array("b_username", "blocks", "geds", "sc_oldged_blocks");
	$gedcheck[] = array("ch_file", "changes", "geds", "sc_oldged_changes");
	$gedcheck[] = array("d_file", "dates", "geds", "sc_oldged_dates");
	$gedcheck[] = array("ge_file", "eventcache", "geds", "sc_oldged_cache");
	$gedcheck[] = array("f_file", "families", "geds", "sc_oldged_fams");
	$gedcheck[] = array("fv_file", "favorites", "geds", "sc_oldged_favs");
	$gedcheck[] = array("gc_gedcomid", "gedconf", "geds", "sc_oldged_gedconf");
	$gedcheck[] = array("i_file", "individuals", "geds", "sc_oldged_indis");
	$gedcheck[] = array("if_file", "individual_family", "geds", "sc_oldged_indifamily");
	$gedcheck[] = array("l_file", "log", "geds", "sc_oldged_log");
	$gedcheck[] = array("m_gedfile", "media", "geds", "sc_oldged_media");
	$gedcheck[] = array("mm_gedfile", "media_mapping", "geds", "sc_oldged_mediam");
	$gedcheck[] = array("n_file", "names", "geds", "sc_oldged_names");
	$gedcheck[] = array("n_username", "news", "geds", "sc_oldged_news");
	$gedcheck[] = array("o_file", "other", "geds", "sc_oldged_other");
	$gedcheck[] = array("om_gedfile", "other_mapping", "geds", "sc_oldged_otherm");
	$gedcheck[] = array("pl_file", "placelinks", "geds", "sc_oldged_pl");
	$gedcheck[] = array("p_file", "places", "geds", "sc_oldged_places");
	$gedcheck[] = array("pd_file", "pdata", "geds", "sc_oldged_plot");
	$gedcheck[] = array("p_gedcomid", "privacy", "geds", "sc_oldged_privacy");
	$gedcheck[] = array("s_file", "sources", "geds", "sc_oldged_sources");
	$gedcheck[] = array("sm_gedfile", "source_mapping", "geds", "sc_oldged_sourcesm");
	$gedcheck[] = array("s_file", "soundex", "geds", "sc_oldged_soundex");
	$gedcheck[] = array("gs_file", "statscache", "geds", "sc_oldged_stats");
	$gedcheck[] = array("ug_gedfile", "users_gedcoms", "geds", "sc_oldged_ug");
		
	$users = UserController::GetUsers();
	
	foreach($gedcheck as $key => $check) {
		if (isset($cleanged) && isset($clean) && $cleanged == $check[1]) {
			if (($check[2] == "geds" && !in_array($clean, $ageds)) || ($check[2] == "gednames" && !in_array($clean, $agednames))) {
				$sql = "DELETE FROM ".TBLPREFIX.$check[1]." WHERE ".$check[0]."='".$clean."'";
				$res = NewQuery($sql);
			}
			else print $error_icon.$error_icon.$gm_lang["sc_oldgedclean_error"]."<br />";
		}
		$sql = "SELECT DISTINCT ".$check[0]." FROM ".TBLPREFIX.$check[1];
		if (count($GEDCOMS)>0) $sql .= " WHERE ".$check[0]." NOT IN (".$$check[2].")";
		if ($check[1] == "log") {
			if (count($GEDCOMS)>0) $sql .= " AND l_category<>'S'";
			else $sql .= " WHERE l_category<>'S'";
		} 
		$res = NewQuery($sql);
		if ($res) {
			if ($res->Numrows() > 0) {
				while ($row = $res->FetchRow()) {
					if (!(($check[1] == "blocks" || $check[1] == "news") && ($row[0] == "defaultuser" || array_key_exists($row[0], $users)))) {
						$errors = true;
						print $gm_lang[$check[3]]." ".$row[0]."  <a href=sanity.php?action=checksanity&check_oldgeds=yes&cleanged=$check[1]&clean=$row[0]>".$gm_lang["cleanup"]."</a><br />";
					}
				}
				if ($errors) print "<br />";
			}
		}
	}
	if (!$errors) print $info_icon.$gm_lang["sc_oldged_ok"];
	print "</td></tr>";
}
		
if (!empty($check_gedcoms)) {

	//-- GEDCOM settings
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_ged_sets"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";
	foreach ($GEDCOMS as $ged=>$value) {
		$var = "check_gedcoms".$value["id"];
		if (isset($$var)) {
			// Check settings per GEDCOM
			$id = $value["id"];
			//-- As ReadGedcomConfig also changes the language, we must save and restore it after reading the config
			$lang = $LANGUAGE;
			SwitchGedcom($ged);
			$LANGUAGE = $lang;
			print "<tr><td class=\"shade1 wrap\" rowspan=\"23\">".$value["title"]."</td><td class=\"shade1 wrap\">";
			
			// Get the partial indilist
			$sql = "SELECT i_id, i_gedcom, i_file FROM ".TBLPREFIX."individuals WHERE i_file='".$id."'";
			$res = NewQuery($sql);
			if ($res) {
				$indilist = array();
				while ($row = $res->FetchAssoc()) {
					$indilist[$row["i_id"]]{"gedcom"} = $row["i_gedcom"];
					$indilist[$row["i_id"]]{"gedfile"} = $row["i_file"];
				}
			}
			
			// Find unlinked indi's
			if ($check_unlinked) {
				$sql = "SELECT * FROM `gm_individuals` WHERE i_gedcom NOT LIKE '%1 FAMS%' AND i_file='".$id."' AND i_gedcom NOT LIKE '%1 FAMC%'";
				$res = NewQuery($sql);
				$found = false;
				$num = 0;
				if ($res) {
					while ($row = $res->FetchAssoc()) {
						$num++;
						if (!$found) {
							$found = true;
							print $warn_icon.$gm_lang["sc_ged_unlink"];
						}
						print "<br />";
						print_list_person($row["i_id"], array(GetPersonName($row["i_id"]), $GEDCOMID));
					}
					if ($found) print "<br />";
				}
				if (!$found) print $info_icon.$gm_lang["sc_ged_nounlink"];
				else print $gm_lang["sc_numrecs_found"]." ".$num;
			}
			else print $info_icon.$gm_lang["sc_ged_unlinked_noselect"];
			print "</td></tr>";

			// Get the partial indilist with asso's and alia's
			$sql = "SELECT i_id, i_gedcom FROM ".TBLPREFIX."individuals WHERE (i_gedcom LIKE '%ASSO @%' OR i_gedcom LIKE '%ALIA @') AND i_file='".$id."'";
			$res = NewQuery($sql);
			if ($res) {
				$cirelalist = array();
				while ($row = $res->FetchAssoc()) {
					$cirelalist[$row["i_id"]]{"gedcom"} = $row["i_gedcom"];
				}
			}
		
			// Get the partial famlist
			$sql = "SELECT f_id, f_gedcom FROM ".TBLPREFIX."families WHERE f_file='".$id."'";
			$res = NewQuery($sql);
			if ($res) {
				$cfamlist = array();
				while ($row = $res->FetchAssoc()) {
					$cfamlist[$row["f_id"]]["gedcom"] = $row["f_gedcom"];
				}
			}
		
			// Get the partial famlist with asso's
			$sql = "SELECT f_id, f_gedcom FROM ".TBLPREFIX."families WHERE (f_gedcom LIKE '%ASSO @%') AND f_file='".$id."'";
			$res = NewQuery($sql);
			if ($res) {
				$cfrelalist = array();
				while ($row = $res->FetchAssoc()) {
					$cfrelalist[$row["f_id"]]["gedcom"] = $row["f_gedcom"];
				}
			}

			// Get the partial sourcelist
			$sql = "SELECT s_id, s_gedcom, s_file, s_name FROM ".TBLPREFIX."sources WHERE s_file='".$id."'";
			$res = NewQuery($sql);
			if ($res) {
				$sourcelist = array();
				while ($row = $res->FetchAssoc()) {
					$csource = array();
					$csource["gedcom"] = $row["s_gedcom"];
					$csource["in_use"] = false;
					$csource["gedfile"] = $row["s_file"];
					$csource["name"] = stripslashes($row["s_name"]);
					$sourcelist[$row["s_id"]] = $csource;
				}
			}

			// Get the partial repolist
			$sql = "SELECT o_id, o_gedcom FROM ".TBLPREFIX."other WHERE o_file='".$id."' AND o_type='REPO'";
			$res = NewQuery($sql);
			if ($res) {
				$crepolist = array();
				while ($row = $res->FetchAssoc()) {
					$crepo = array();
					$crepo["gedcom"] = $row["o_gedcom"];
					$crepo["in_use"] = false;
					$crepolist[$row["o_id"]] = $crepo;
				}
			}
			
			// Get the partial level 0 notelist
			$sql = "SELECT o_id, o_gedcom FROM ".TBLPREFIX."other WHERE o_file='".$id."' AND o_type='NOTE'";
			$res = NewQuery($sql);
			if ($res) {
				$cnotelist = array();
				while ($row = $res->FetchAssoc()) {
					$cnote = array();
					$cnote["gedcom"] = $row["o_gedcom"];
					$cnote["in_use"] = false;
					$cnotelist[$row["o_id"]] = $cnote;
				}
			}
			
			// Get the partial MMlist
			$sql = "SELECT m_media, m_gedrec, m_file, m_titl, m_gedfile FROM ".TBLPREFIX."media WHERE m_gedfile='".$id."'";
			$res = NewQuery($sql);
			if ($res) {
				$cmedialist = array();
				while ($row = $res->FetchAssoc()) {
					$cmedia = array();
					$cmedia["gedcom"] = $row["m_gedrec"];
					$cmedia["file"] = $row["m_file"];
					$cmedia["gedfile"] = $row["m_gedfile"];
					$cmedia["in_use"] = false;
					$cmedia["title"] = $row["m_titl"];
					$cmedialist[$row["m_media"]] = $cmedia;
				}
			}

			// While doing the reference checking, we check the validity of fact tags on the fly
			// And we can also check if every fact record has a source citation
			$wrongfacts = array();
			$no_cits = array();
			$inv_noteref = array();
			$numnc = 0; // check for source citations
			$numcf = 0; // check for invalid tags
			$numcn = 0; // check for note references
			$rightfacts = array("CONT", "CONC");
			$non_cits_facts = array("CHAN", "NOTE", "SOUR", "FAMS", "FAMC", "_UID", "OBJE", "NAME", "SEX", "CHIL", "HUSB", "WIFE");
			
			// Check for reference to non existing sources for indi's
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($indilist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d SOUR @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$sid) {
						$num++;
						$sid = strtoupper($sid);
						if (isset($sourcelist[$sid])) $sourcelist[$sid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $error_icon.$gm_lang["sc_inv_sref"];
							}
							print "<br />".$gm_lang["source"]." ".$sid."<br />";
							print_list_person($key, array(GetPersonName($key), $GEDCOMID));
						}
					}
				}
				$s = preg_match_all("/\n\d NOTE @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$oid) {
						$numcn++;
						$oid = strtoupper($oid);
						if (isset($cnotelist[$oid])) $cnotelist[$oid]["in_use"] = true;
						else {
							$inv_noteref["INDI"][$key] = true;
						}
					}
				}
				// Now check the facts
				if (!empty($check_gedtags) || !empty($check_cits)) {
					$facts = GetAllSubrecords($gedlines["gedcom"], "", false, true, false);
					foreach($facts as $keyf => $fact) {
						// Tags
						if (!empty($check_tags)) {
							$subs = split("\n", $fact);
							foreach($subs as $keyf2 => $sub) {
								preg_match("/(\d)\s(\w+)[\s.+\r\n|\r\n]/", $sub, $tags);
								$numcf++;
								if (isset($tags[2]) && !defined("GM_FACT_".$tags[2]) && !in_array($tags[2],$rightfacts)) $wrongfacts[$tags[2]][] = array($key, $GEDCOMID, "INDI");
							}
						}
						// Source citations
						if (!empty($check_cits)) {
							if (substr($fact, 0, 1) > 0) {
								$ft = preg_match("/^1\s(\w+)/", $fact, $match);
								$type = $match[1];
								$numnc++;
								if (stristr($fact, "2 SOUR") == 0 && !in_array($type, $non_cits_facts)) {
//									print "found: ".$type."<br />";
									$no_cits[$key."[".$GEDCOMID."]"] = array($key, $GEDCOMID, "INDI", $type);
								}
							}
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_sref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
					
			// Check for ASSO/ALIA's that point to non existing indi's
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cirelalist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d ASSO @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$pid) {
						$num++;
						$pid = strtoupper($pid);
						if (!isset($indilist[$pid])) {
							if (!$error) {
								$error = true;
								print $error_icon.$gm_lang["sc_inv_aref"];
							}
							print "<br />".$gm_lang["asso_alia"].": ".$pid."<br />";
							print_list_person($key, array(GetPersonName($key), $GEDCOMID));
						}
					}
				}
			}
			foreach($cfrelalist as $key=>$gedlines) {
				$s = preg_match_all("/\n\d ASSO @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$pid) {
						$num++;
						$pid = strtoupper($pid);
						if (!isset($indilist[$pid])) {
							if (!$error) {
								$error = true;
								print $error_icon.$gm_lang["sc_inv_aref"];
							}
							print "<br />".$gm_lang["asso_alia"].": ".$pid."<br />";
							print_list_family($key, array(GetFamilyDescriptor($key), $GEDCOMID));
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_aref"]." ";
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
						if (isset($sourcelist[$sid])) $sourcelist[$sid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $error_icon.$gm_lang["sc_inv_sref_media"];
							}
							print "<br />".$gm_lang["source"]." ".$sid."<br />";
							print $gm_lang["sc_media"]." ".$key."<br />";
						}
					}
				}
				$s = preg_match_all("/\n\d NOTE @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$oid) {
						$numcn++;
						$oid = strtoupper($oid);
						if (isset($cnotelist[$oid])) $cnotelist[$oid]["in_use"] = true;
						else {
							$inv_noteref["OBJE"][$key] = true;
						}
					}
				}
				// Now check the facts
				if (!empty($check_gedtags)) {
					$facts = GetAllSubrecords($gedlines["gedcom"], "", false, true, false);
					foreach($facts as $keyf => $fact) {
						$subs = split("\n", $fact);
						foreach($subs as $keyf2 => $sub) {
							preg_match("/(\d)\s(\w+)[\s.+\r\n|\r\n]/", $sub, $tags);
							$numcf++;
							if (isset($tags[2]) && !defined("GM_FACT_".$tags[2]) && !in_array($tags[2],$rightfacts)) $wrongfacts[$tags[2]][] = array($key, $GEDCOMID, "MEDIA", $gedlines["file"], $gedlines["title"]);
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_sref_media"]." ";
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
						if (isset($sourcelist[$sid])) $sourcelist[$sid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $error_icon.$gm_lang["sc_inv_sref_fam"];
							}
							print "<br />".$gm_lang["source"]." ".$sid."<br />";
							print_list_family($key, array(GetFamilyDescriptor($key), $GEDCOMID));
						}
					}
				}
				$s = preg_match_all("/\n\d NOTE @(.+)@/", $gedlines["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$oid) {
						$numcn++;
						$oid = strtoupper($oid);
						if (isset($cnotelist[$oid])) $cnotelist[$oid]["in_use"] = true;
						else {
							$inv_noteref["FAM"][$key] = true;
						}
					}
				}
				// Now check the facts
				if (!empty($check_gedtags) || !empty($check_cits)) {
					$facts = GetAllSubrecords($gedlines["gedcom"], "", false, true, false);
					foreach($facts as $keyf => $fact) {
						$inames = false;
						$subs = split("\n", $fact);
						foreach($subs as $keyf2 => $sub) {
							preg_match("/(\d)\s(\w+)[\s.+\r\n|\r\n]/", $sub, $tags);
							$numcf++;
							if (isset($tags[2]) && !defined("GM_FACT_".$tags[2]) && !in_array($tags[2],$rightfacts)) {
								$wrongfacts[$tags[2]][] = array($key, $GEDCOMID, "FAM", $gedlines["gedcom"]);
								$inames = true;
							}
						}
						// Source citations
						if (!empty($check_cits)) {
							if (substr($fact, 0, 1) > 0) {
								$ft = preg_match("/^1\s(\w+)/", $fact, $match);
								$type = $match[1];
								if (stristr($fact, "2 SOUR") == 0 && !in_array($type, $non_cits_facts)) {
//									print "found: ".$type."<br />";
									$no_cits[$key."[".$GEDCOMID."]"] = array($key, $GEDCOMID, "FAM", $gedlines["gedcom"], $type);
									$numnc++;
									$inames = true;
								}
							}
						}
						if ($inames) {
							$parents = FindParentsInRecord($gedlines["gedcom"]);
							if (!empty($parents["HUSB"])) $indilist[$parents["HUSB"]]["names"] = GetIndiNames($indilist[$parents["HUSB"]]["gedcom"]);
							if (!empty($parents["WIFE"])) $indilist[$parents["WIFE"]]["names"] = GetIndiNames($indilist[$parents["WIFE"]]["gedcom"]);
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_sref_fam"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
		
			// Check for reference to non existing repo for sources
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($sourcelist as $key=>$source) {
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
								print $error_icon.$gm_lang["sc_inv_rref_sour"];
							}
							print "<br />".$gm_lang["repo"]." ".$rid."<br />";
							$srec = FindSourceRecord($key, $GEDCOMID);
							print_list_source($key, $sourcelist[$key]);
						}
					}
				}
				$s = preg_match_all("/\n\d NOTE @(.+)@/", $source["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$oid) {
						$numcn++;
						$oid = strtoupper($oid);
						if (isset($cnotelist[$oid])) $cnotelist[$oid]["in_use"] = true;
						else {
							$inv_noteref["SOUR"][$key] = true;
						}
					}
				}
				// Now check the facts
				if (!empty($check_gedtags)) {
					$facts = GetAllSubrecords($source["gedcom"], "", false, true, false);
					foreach($facts as $keyf => $fact) {
						$subs = split("\n", $fact);
						foreach($subs as $keyf2 => $sub) {
							preg_match("/(\d)\s(\w+)[\s.+\r\n|\r\n]/", $sub, $tags);
							$numcf++;
							if (isset($tags[2]) && !defined("GM_FACT_".$tags[2]) && !in_array($tags[2],$rightfacts)) $wrongfacts[$tags[2]][] = array($key, $GEDCOMID, "SOUR");
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_rref_sour"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print" </td></tr>";
		
			// Check for sources with no reference to a repository
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($sourcelist as $key=>$source) {
				$num++;
				$s = preg_match_all("/REPO @(.+)@/", $source["gedcom"], $match);
				// if more use $match[1] as array
				if ($s == 0) {
					if (!$error) {
						$error = true;
						print $warn_icon.$gm_lang["sc_noref_sour_repo"];
					}
					print "<br />";
					$srec = FindSourceRecord($key, $GEDCOMID);
					print_list_source($key, $sourcelist[$key]);
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_sour_repo_ref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print" </td></tr>";
		
			// Check for unreferenced sources
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($sourcelist as $sid=>$value) {
				$num++;
				if (!$value["in_use"]) {
					if (!$error) {
						$error = true;
						print $warn_icon.$gm_lang["sc_unu_sref"];
					}
					print "<br />";
					$srec = FindSourceRecord($sid, $GEDCOMID);
					print_list_source($sid, $sourcelist[$sid]);
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_all_sref"]." ";
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
						print $warn_icon.$gm_lang["sc_unu_rref"];
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
				$s = preg_match_all("/\n\d NOTE @(.+)@/", $value["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$oid) {
						$numcn++;
						$oid = strtoupper($oid);
						if (isset($cnotelist[$oid])) $cnotelist[$oid]["in_use"] = true;
						else {
							$inv_noteref["REPO"][$rid] = true;
						}
					}
				}
				// Now check the facts
				if (!empty($check_gedtags)) {
					$facts = GetAllSubrecords($value["gedcom"], "", false, true, false);
					foreach($facts as $keyf => $fact) {
						$subs = split("\n", $fact);
						foreach($subs as $keyf2 => $sub) {
							preg_match("/(\d)\s(\w+)[\s.+\r\n|\r\n]/", $sub, $tags);
							$numcf++;
							if (isset($tags[2]) && !defined("GM_FACT_".$tags[2]) && !in_array($tags[2],$rightfacts)) $wrongfacts[$tags[2]][] = array($key, $GEDCOMID, "REPO", $value["gedcom"]);
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_all_rref"]." ";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
	
			// Print the non-existent facts
			print "<tr><td class=\"shade1 wrap\">";
			if (!empty($check_gedtags)) {
				if (count($wrongfacts) == 0) print $info_icon.$gm_lang["sc_nowrongfacts"]." ";
				else {
					print $error_icon.$gm_lang["sc_haswrongfacts"]."<br />";
					foreach ($wrongfacts as $fact => $records) {
						print $gm_lang["sc_facttag"]." ".$fact."<br />";
						foreach ($records as $key => $wfact) {
							if ($wfact[2] == "INDI") print_list_person($wfact[0], array(GetPersonName($wfact[0]), $wfact[1]));
							else if ($wfact[2] == "FAM") print_list_family($wfact[0], array(GetFamilyDescriptor($wfact[0], false, $wfact[3]), $wfact[1]));
							else if ($wfact[2] == "SOUR") {
								$srec = FindSourceRecord($wfact[0], $wfact[1]);
								print_list_source($wfact[0], $sourcelist[$wfact[0]]);
							}
							else if ($wfact[2] == "REPO") {
								$repo = array();
								$tt = preg_match("/1 NAME (.*)/", $wfact[3], $match);
								if ($tt == "0") $name = $key; else $name = $match[1];
								$repo["id"] = $key;
								$repo["gedfile"] = $wfact[1];
								$repo["type"] = "REPO";
								$repo["gedcom"] = $wfact[3];
								print_list_repository($name, $repo);
							}
							else if ($wfact[2] == "MEDIA") {
			    				print "<a href=\"mediadetail.php?mid=".$wfact[0]."&amp;gedid=".$GEDCOMID."\">";
				    			if (!empty($wfact[4])) print $wfact[4];
				    			else print $wfact[3];
				    			print "</a><br />";
							}
						}
						print "<br />";
					}
 				}			
				print $gm_lang["sc_numrecs_checked"]." ".$numcf;
			}
			else print $info_icon.$gm_lang["sc_nocheck_gedtags"];
 			print "</td></tr>";
			
			// Print the records with no source citations
			print "<tr><td class=\"shade1 wrap\">";
			if (!empty($check_cits)) {
				if (count($no_cits) == 0) print $info_icon.$gm_lang["sc_cits_ok"]." ";
				else {
					print $warn_icon.$gm_lang["sc_hasno_cits"]."<br />";
					foreach ($no_cits as $key => $rec) {
						if ($rec[2] == "INDI") print_list_person($rec[0], array(GetPersonName($rec[0]), ""), false, "", true, $rec[3]);
						else if ($rec[2] == "FAM") print_list_family($rec[0], array(GetFamilyDescriptor($rec[0], false, $rec[3]), $rec[1]), false, "", true, $rec[4]);
						else print_r($rec);
					}
					print "<br />";
 				}			
				print $gm_lang["sc_numrecs_checked"]." ".$numnc;
			}
			else print $info_icon.$gm_lang["sc_nocits"];
 			print "</td></tr>";
			

 			// Expand the indi array with Indi->fam 
			foreach($indilist as $key=>$indi) {
				$s = preg_match_all("/\n1 FAMS @(.+)@/", $indi["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$fid) {
						$indilist[$key]["FAMS"][$fid] = true;
					}
				}
				$s = preg_match_all("/\n1 FAMC @(.+)@/", $indi["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$fid) {
						$indilist[$key]["FAMC"][$fid] = true;
					}
				}
			}
	
			// Expand the fam array with fam->indi and check for possible order problems
			$error = false;
			print "<tr><td class=\"shade1 wrap\">";
			foreach($cfamlist as $key=>$fam) {
				$s = preg_match_all("/\n1 CHIL @(.+)@/", $fam["gedcom"], $match, PREG_SET_ORDER);
				if ($s) {
					$first = true;
					$printed = false;
					foreach($match as $key2=>$cid) {
						$cfamlist[$key]["CHIL"][$cid[1]] = true;
						// Check the order of children in families
						$rec = GetSubRecord(1, "BIRT", $indilist[$cid[1]]['gedcom']);
						if (empty($rec)) $rec = GetSubRecord(1, "CHR", $indilist[$cid[1]]['gedcom']);
						if (!$first) {
							if (CompareFactsdate($oldrec, $rec) > 0) {
								if (!$error) {
									$error = true;
									print $warn_icon.$gm_lang["sc_order_fam"]."<br />";
								}
								if (!$printed) {
									print_list_family($key, array(GetFamilyDescriptor($key), $GEDCOMID));
									$printed = true;
								}
							}
							$oldcid = $cid[1];
							if (stristr($rec, "2 DATE")) $oldrec = $rec;
						}
						else {
							$first = false;
							$oldcid = $cid[1];
							$oldrec = $rec;
						}
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
			if (!$error) print $info_icon.$gm_lang["sc_ok_order_fam"]." ";
			else print "<br />";
			print "</td></tr>";

			// Check for empty fams with no reference to any indi
			$num = 0;
			$error = false;
			print "<tr><td class=\"shade1 wrap\">";
			foreach($cfamlist as $key=>$cfam) {
				$num++;
				if (!isset($cfam["CHIL"]) && !isset($cfam["WIFE"]) && !isset($cfam["HUSB"])) {
					if (!$error) {
						$error = true;
						print $error_icon.$gm_lang["sc_empty_fam"]."<br />";
					}
					print_list_family($key, array(GetFamilyDescriptor($key), $GEDCOMID));
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_empty_fam"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
			
			// Check the indi's for references to fams. If pair of references found, unset them.
			$num = 0;
			$error = false;
			print "<tr><td class=\"shade1 wrap\">";
			foreach($indilist as $key=>$cindi) {
				foreach($cindi as $role=>$pointerarr) {
					if ($role == "FAMS") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($cfamlist[$pointer]["HUSB"][$key])) {
								unset($cfamlist[$pointer]["HUSB"][$key]);
								unset($indilist[$key][$role][$pointer]);
							}
							else if (isset($cfamlist[$pointer]["WIFE"][$key])) {
								unset($cfamlist[$pointer]["WIFE"][$key]);
								unset($indilist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $error_icon.$gm_lang["sc_inv_pointer"]."<br />";
								if (isset($cfamlist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?gedid=".$GEDCOMID."&amp;pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?gedid=".$GEDCOMID."&amp;pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." ".$pointer."<br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
					else if ($role == "FAMC") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($cfamlist[$pointer]["CHIL"][$key])) {
								unset($cfamlist[$pointer]["CHIL"][$key]);
								unset($indilist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $error_icon.$gm_lang["sc_inv_pointer"]."<br />";
								if (isset($cfamlist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?gedid=".$GEDCOMID."&amp;pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_fam"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?gedid=".$GEDCOMID."&pid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_fam"]." ".$pointer."<br />".$gm_lang["sc_role"]." ".$role."<br />";
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
							if (isset($indilist[$pointer]["FAMC"][$key])) {
								unset($indilist[$pointer]["FAMC"][$key]);
								unset($cfamlist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $error_icon.$gm_lang["sc_inv_pointer"]."<br />";
								if (isset($indilist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_indi"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?gedid=".$GEDCOMID."&amp;pid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_indi"]."<br />".$gm_lang["sc_indi"]." ".$pointer."<br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
					else if ($role == "HUSB") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($indilist[$pointer]["FAMS"][$key])) {
								unset($indilist[$pointer]["FAMS"][$key]);
								unset($cfamlist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $error_icon.$gm_lang["sc_inv_pointer"]."<br />";
								if (isset($indilist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_indi"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?gedid=".$GEDCOMID."&amp;pid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_indi"]."<br />".$gm_lang["sc_indi"]." ".$pointer."<br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
					else if ($role == "WIFE") {
						foreach($pointerarr as $pointer=>$garbage) {
							$num++;
							if (isset($indilist[$pointer]["FAMS"][$key])) {
								unset($indilist[$pointer]["FAMS"][$key]);
								unset($cfamlist[$key][$role][$pointer]);
							}
							else {
								if (!$error) print $error_icon.$gm_lang["sc_inv_pointer"]."<br />";
								if (isset($indilist[$pointer])) {
									print "<br />".$gm_lang["sc_no_backward_indi"]."<br />".$gm_lang["sc_indi"]." <a href=\"individual.php?gedid=".$GEDCOMID."&amp;pid=".$pointer."\" target=\"_BLANK\">".$pointer."</a><br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								}
								else print "<br />".$gm_lang["sc_no_indi"]."<br />".$gm_lang["sc_indi"]." ".$pointer."<br />".$gm_lang["sc_fam"]." <a href=\"family.php?gedid=".$GEDCOMID."&amp;famid=".$key."\" target=\"_BLANK\">".$key."</a><br />".$gm_lang["sc_role"]." ".$role."<br />";
								$error = true;
							}
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_no_inv_point"]." ";
			else print "<br />";
			print $gm_lang["sc_numpoint_checked"]." ".$num;
			print "</td></tr>";
			
			// Check for reference to non existing 0 MM records from individuals
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($indilist as $key=>$gedlines) {
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
								print $error_icon.$gm_lang["sc_inv_mref"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							print_list_person($key, array(GetPersonName($key), $GEDCOMID));
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_mref"]." ";
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
								print $error_icon.$gm_lang["sc_inv_mref_fam"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							print_list_family($key, array(GetFamilyDescriptor($key), $GEDCOMID));
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_mref_fam"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
			
			// Check for reference to non existing 0 MM records from sources
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($sourcelist as $key=>$gedlines) {
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
								print $error_icon.$gm_lang["sc_inv_mref_sour"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							$srec = FindSourceRecord($key, $GEDCOMID);
							print_list_source($key, $sourcelist[$sid]);
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_mref_sour"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";

			// Check for reference to non existing 0 MM records from repositories
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($crepolist as $key=>$repo) {
				$s = preg_match_all("/\n\d OBJE @(.+)@/", $repo["gedcom"], $match);
				// if more use $match[1] as array
				if ($s) {
					foreach($match[1] as $key2=>$mid) {
						$num++;
						$mid = strtoupper($mid);
						if (isset($cmedialist[$mid])) $cmedialist[$mid]["in_use"] = true;
						else {
							if (!$error) {
								$error = true;
								print $error_icon.$gm_lang["sc_inv_mref_repo"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							$tt = preg_match("/1 NAME (.*)/", $repo["gedcom"], $rmatch);
							if ($tt == "0") $name = $key; else $name = $rmatch[1];
							$repo["id"] = $rid;
							$repo["gedfile"] = $id;
							$repo["type"] = "REPO";
							print_list_repository($name, $repo);
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_mref_repo"]." ";
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
						print $warn_icon.$gm_lang["sc_unu_mref"];
					}
					print "<br />";
					print $gm_lang["sc_media"]." ".$mid."<br />";
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_all_mref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";

			
			// Check for physical files 
			$flist = array();
			if (!$MEDIA_IN_DB) {
				
				// Build the directorylist
				$dirs = GetDirList(array($MEDIA_DIRECTORY));
				
				// exclude the GM trees
				$tpath = array($MEDIA_DIRECTORY."/thumbs", "/thumbs/", "./fonts/", "./hooks/", "./images/", "./includes", "./languages/", "./modules/", "./pgvnuke/", "./places/", "./reports/", "./ufpdf/", "./themes/", "./blocks/", "./install/", INDEX_DIRECTORY);
				$tpath = str_replace("//", "/", $tpath);
				foreach($dirs as $key => $dir) {
					if ($dir == "./") unset ($dirs[$key]);
					else foreach($tpath as $key2=> $exclude) {
						if (stristr($dir, $exclude)) unset($dirs[$key]);
					}
				}
				
				// Build the filelist
				foreach($dirs as $key => $dir) {
					$d = @dir($dir);
					if (!is_object($d)) {
						print $error_icon.$gm_lang["sc_dir_noaccess"]." ".$dir;
						$errors1 = true;
					}
					else {
						while (false !== ($entry = $d->read())) {
							if(!is_dir($entry) && $entry != ".") {
								$num++;
								if (MediaFS::IsValidMedia($entry)) {
									$file = RelativePathFile($d->path.$entry);
									$flist[$file] = false;
								}	
							}
						}
					$d->close();
					}
				}
			}
			else {
				$res = NewQuery("SELECT mf_file, mf_link FROM ".TBLPREFIX."media_files WHERE mf_file NOT LIKE '<DIR>'");
				if ($res) {
					while ($row = $res->FetchRow()) {
						if (empty($row[1])) $flist[$row[0]] = false;
						else $flist[$row[1]] = false;
					}
				}
			}

			// Check if all references to files from MM exist
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cmedialist as $mid=>$value) {
				$s = preg_match_all("/\n\d FILE (.+)?/", $value["gedcom"], $match);
				if ($s) {
					foreach($match[1] as $key2=>$file) {
						$file = trim($file);
						if (substr($file,0,7) != "http://") {
							if (substr($file,0,2) != "./") $file = "./".$file;
							$file = RelativePathFile($MEDIA_DIRECTORY.MediaFS::CheckMediaDepth($file));
						}
						$num++;
						if (isset($flist[$file])) {
							$flist[$file] = true;
						}
						else {
							if (!$error) {
								$error = true;
								print $error_icon.$gm_lang["sc_inv_mref_file"];
							}
							print "<br />".$gm_lang["sc_media"]." ".$mid."<br />";
							print $gm_lang["sc_file"]." ".$file."<br />";
						}
					}
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_all_file"]." ";
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
						print $warn_icon.$gm_lang["sc_unu_file"];
					}
					print "<br />".$gm_lang["sc_file"]." ".$file;
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_use_file"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
			
			// NOTE results
			// Check on references to non existing notes
			print "<tr><td class=\"shade1 wrap\">";
			if (count($inv_noteref) == 0) {
				print $info_icon.$gm_lang["sc_ok_noteref"]." ";
			}
			else {
				print $error_icon.$gm_lang["sc_inv_noteref"]."<br />";
				foreach ($inv_noteref as $type => $keys) {
					if ($type == "INDI") {
						foreach ($keys as $key => $nothing) {
							print_list_person($key, array(GetPersonName($key), $GEDCOMID));
						}
					}
					if ($type == "FAM") {
						foreach ($keys as $key => $nothing) {
							print_list_family($key, array(GetFamilyDescriptor($key), $GEDCOMID));
						}
					}
					if ($type == "SOUR") {
						foreach ($keys as $key => $nothing) {
							print_list_source($key, $sourcelist[$key]);
						}
					}
					if ($type == "REPO") {
						foreach ($keys as $key => $nothing) {
							$crepolist[$key]["id"] = $key;
							$crepolist[$key]["gedfile"] = $GEDCOMID;
							$tt = preg_match("/1 NAME (.*)/", $crepolist[$key]["gedcom"], $match);
							if ($tt == "0") $name = $key; else $name = $match[1];
							print_list_repository($name, $crepolist[$key]);
						}
					}
					if ($type == "OBJE") {
						foreach ($keys as $key => $nothing) {
							if (empty($cmedialist[$key]["title"])) $cmedialist[$key]["name"] = $cmedialist[$key]["file"];
							else $cmedialist[$key]["name"] = $cmedialist[$key]["title"];
							print_list_media($key, $cmedialist[$key]);
						}
					}
				}
			}
			print $gm_lang["sc_numrecs_checked"]." ".$numcn;
			print "</td></tr>";	
			
			// Check for unreferenced general notes
			print "<tr><td class=\"shade1 wrap\">";
			$error = false;
			$num = 0;
			foreach($cnotelist as $oid=>$value) {
				$num++;
				if (!$value["in_use"]) {
					if (!$error) {
						$error = true;
						print $warn_icon.$gm_lang["sc_unu_nref"];
					}
					print "<br />";
					$note =& Note::GetInstance($oid);
					print "<a href=\"note.php?oid=".$oid."&amp;gedid=".$GEDCOMID."\">".$note->GetTitle(40, true)." (".$oid.")</a>";
				}
			}
			if (!$error) print $info_icon.$gm_lang["sc_ok_all_nref"]." ";
			else print "<br />";
			print $gm_lang["sc_numrecs_checked"]." ".$num;
			print "</td></tr>";
		}
	}
	SwitchGedcom();
}

if (!empty($check_filesys)) {
		
	//-- File system security
	print "<tr><td colspan=\"2\" class=\"topbottombar\">".$gm_lang["sc_fs_security"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["sc_check"]."</td><td class=\"shade2 center\">".$gm_lang["sc_result"]."</td></tr>";
	
		// Root dir
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_main"]."</td><td class=\"shade1 wrap\">";
		$dir = "/"; // From PHP5 dir will not read ./ as the current dir. The dot is removed here.
		$errors1 = false;
		if (MediaFS::DirIsWritable($dir, false)) {
			print $error_icon.$gm_lang["sc_fs_main_error"]."<br />";
			$errors1 = true;
		}
		$d = dir($dir);
		if (!is_object($d)) print $error_icon.$gm_lang["sc_dir_noaccess"]." ".$dir;
		else {
			$errors2 = false;
			$num = 0;
			while (false !== ($entry = $d->read())) {
				if(!is_dir($entry) && $entry != ".") {
					$num++;
					if (FileIsWriteable($d->path."/".$entry)) {
						if (!$errors2) {
							print $error_icon.$gm_lang["sc_fs_filesrw"]."<br />";
							$errors2 = true;
						}
						print $dir.$entry."<br />";
					}
				}
			}
			$d->close();
			print $gm_lang["sc_numchecked"]." ".$num."<br />";
			if (!$errors1 && !$errors2) print $info_icon.$gm_lang["sc_ok"];
		}
		print "</td></tr>";
		
		// Index dir
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_index"]."</td><td class=\"shade1 wrap\">";
		$dir = INDEX_DIRECTORY;
		$errors1 = false;
		if (!MediaFS::DirIsWritable($dir, false)) {
			print $error_icon.$gm_lang["sc_fs_index_error"]."<br />";
			$errors1 = true;
		}
		$d = @dir($dir);
		if (!is_object($d)) print $error_icon.$gm_lang["sc_dir_noaccess"]." ".$dir;
		else {
			$errors2 = false;
			$num = 0;
			while (false !== ($entry = $d->read())) {
				// As of PHP5 also .. is returned and now excluded here.
				if(!is_dir($entry) && $entry != "." && $entry != "..") {
					$num++;
					if (!FileIsWriteable($d->path."/".$entry)) {
						if (!$errors2) {
							print $error_icon.$gm_lang["sc_fs_filesro"]."<br />";
							$errors2 = true;
						}
						print $dir.$entry."<br />";
					}
				}
			}
			$d->close();
			print $gm_lang["sc_numchecked"]." ".$num."<br />";
			if (!$errors1 && !$errors2) print $info_icon.$gm_lang["sc_ok"];
		}
		print "</td></tr>";
		
		// Languages directory
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_languages"]."</td><td class=\"shade1 wrap\">";
		$dir = "./languages/";
		$write1 = false;
		$num = 0;
		if (MediaFS::DirIsWritable($dir, false)) $write1 = true;
		$d = @dir($dir);
		if (!is_object($d)) print $warn_icon.$gm_lang["sc_dir_noaccess"]." ".$dir;
		else {
			$write2 = false;
			$num = 0;
			while (false !== ($entry = $d->read())) {
				$num++;
				if(!is_dir($entry) && $entry != ".") {
					if (FileIsWriteable($d->path."/".$entry)) $write2 = true;
					$num++;
				}
			}
			$d->close();
			print $gm_lang["sc_numchecked"]." ".$num."<br />";
			if (!$write1 && !$write2) print $warn_icon.$gm_lang["sc_fs_languages_ro"];
			else print $warn_icon.$gm_lang["sc_fs_languages_rw"];
		}
		print "</td></tr>";
		
		// Media directories
		// Check only if media is stored in the physical file system
		if (!$MEDIA_IN_DB) {
			print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_media"]."</td><td class=\"shade1 wrap\">";
			$res = NewQuery("SELECT gc_media_directory, gc_gedcomid FROM ".TBLPREFIX."gedconf");
			if ($res) {
				$dirs = array();
				while ($row = $res->FetchRow()) {
					$dirs[$row[0]] = $row[1];
				}
				$first = true;
				foreach ($dirs as $dir=> $value) {
					if (!$first) {
						print "<br />";
					}
					print $gm_lang["sc_gedname"].$GEDCOMS[$value]["title"]."<br />";
					if (!MediaFS::DirIsWritable($dir, false)) print $warn_icon.$gm_lang["sc_fs_media_ro"]." ".$dir;
					else print $warn_icon.$gm_lang["sc_fs_media_rw"]." ".$dir;
					print "<br />";
					$first = false;
				}
			}
			print "</td></tr>";
		}
		else {
			// If media is in DB, do a VFS sanity check instead
			print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_media"]."</td><td class=\"shade1 wrap\">";
			
			// Get the arrays of filedir, filedata and thumbdata
			$vferror = false;
			
			// 1. Get the files
			$filedata = array();
			$res = NewQuery("SELECT DISTINCT mdf_file FROM ".TBLPREFIX."media_datafiles");
			if ($res) {
				while ($row = $res->FetchRow()) {
					$filedata[] = $row[0];
				}
				// We move index 0 to the end and unset index 0. If an entry is found in index 0, array_search
				// will return 0 which is also used for 'false', not found.
				$filedata[] = $filedata[0];
				unset($filedata[0]);
			}
			
			// 2. Get the thumbfiles
			$thumbdata = array();
			$res = NewQuery("SELECT DISTINCT mtf_file FROM ".TBLPREFIX."media_thumbfiles");
			if ($res) {
				while ($row = $res->FetchRow()) {
					$thumbdata[] = $row[0];
				}
				$thumbdata[] = $thumbdata[0];
				unset($thumbdata[0]);
			}
			
			// 3. Get the directory data
			$dirdata = array();
			$dirprinted = array();
			$res = NewQuery("SELECT mf_path FROM ".TBLPREFIX."media_files WHERE mf_file LIKE '<DIR>'");
			if ($res) {
				while ($row = $res->FetchRow()) {
					$dirdata[] = $row[0];
				}
			}
			
			// Get the file entries and check them
			$num = 0;
			$fsize = 0;
			$tsize = 0;
			$res = NewQuery("SELECT mf_file, mf_path, mf_size, mf_tsize, mf_link FROM ".TBLPREFIX."media_files WHERE mf_file NOT LIKE '<DIR>'");
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					// Check if directory entry, thumbfile and file data are present if they should
					if ($row["mf_size"] != 0) {
						$i = array_search($row["mf_file"], $filedata);
						if (!$i) {
							print $gm_lang["sc_vfr_nodata"]." ".$row["mf_file"]."<br />";
							$vferror = true;
						}
						else {
							$fsize = $fsize + $row["mf_size"];
							unset($filedata[$i]);
						}
					}
					if ($row["mf_tsize"] != 0) {
						$i = array_search($row["mf_file"], $thumbdata);
						if (!$i) {
							print $gm_lang["sc_vfr_nothumb"]." ".$row["mf_file"]."<br />";
							$vferror = true;
						}
						else {
							$tsize = $tsize + $row["mf_tsize"];
							unset($thumbdata[$i]);
						}
					}
					if (empty($row["mf_link"]) && !in_array($row["mf_path"], $dirdata) && !in_array($row["mf_path"], $dirprinted)) {
						print $gm_lang["sc_vfr_nodir"]." ".$row["mf_path"]."<br />";
						$dirprinted[] = $row["mf_path"];
						$vferror = true;
					}
					$num++;
				}
				// We are left with the orphaned data
				foreach($filedata as $pipo => $name) {
					print $gm_lang["sc_vfr_ordata"]." ".$name."<br />";
					$vferror = true;
				}
				foreach($thumbdata as $pipo => $name) {
					print $gm_lang["sc_vfr_orthum"]." ".$name."<br />";
					$vferror = true;
				}
			}
			print $gm_lang["sc_numchecked"]." ".$num."<br />";
			print $gm_lang["sc_fdatasize"]." ";
			printf("%.1f Mb", ($fsize/1024)/1024);
			print "<br />".$gm_lang["sc_fthumbsize"]." ";
			printf("%.1f Mb", ($tsize/1024)/1024);
			print "<br />";
			if (!$vferror) print $info_icon.$gm_lang["sc_vfs_sane"]."<br />";

			print "</td></tr>";
		}
			
		
		// All other files and dirs
		print "<tr><td class=\"shade1 wrap\">".$gm_lang["sc_fs_other"]."</td><td class=\"shade1 wrap\">";
		$dirs = GetDirList(array("./blocks/", "./fonts/", "./hooks/", "./images/", "./includes/", "./modules/", "./places/", "./reports/", "./themes/", "./ufpdf/"));
		$errors1 = false;
		foreach ($dirs as $key=>$dir) {
			if (MediaFS::DirIsWritable($dir, false)) {
				if (!$errors1) {
					$errors1 = true;
					print $error_icon.$gm_lang["sc_fs_dirrw"]."<br />";
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
				print $error_icon.$gm_lang["sc_dir_noaccess"]." ".$dir."<br />";
				$errors1 = true;
			}
			else {
				while (false !== ($entry = $d->read())) {
					if(!is_dir($entry) && $entry != ".") {
						$num++;
						if (FileIsWriteable($d->path."/".$entry)) {
							if (!$errors2) {
								print $error_icon.$gm_lang["sc_fs_filesrw"]."<br />";
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
		if (!$errors1 && !$errors2) print $info_icon.$gm_lang["sc_ok"];
		print "</td></tr>";
}
print "</table><br />";
print "</div>";
print_footer();
?>
