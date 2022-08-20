<?php
/**
 * Administrative User Interface.
 *
 * Provides links for administrators to get to other administrative areas of the site
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
 * @version $Id: admin_maint.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * load the main configuration and context
 */
require "config.php";
if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=admin_maint.php");
	else header("Location: ".LOGIN_URL."?url=admin_maint.php");
	exit;
}

if (!isset($action)) $action="";
$message = "";
$output = "";
switch ($action) {
	case "loadenglish" :
		if (AdminFunctions::StoreEnglish()) {
               WriteToLog("AdminMaint-&gt; ".GM_LANG_all_loaded);
               $message = GM_LANG_all_loaded;
          }
          else {
               LanguageFunctions::LoadEnglish();
               LanguageFunctions::LoadEnglishFacts();
               WriteToLog("AdminMaint-&gt; ".GM_LANG_all_not_loaded, "E", "S");
               $message = "<span class=\"Error\">".GM_LANG_all_not_loaded."</span>";
		}
		break;
	case "reports": 
		$files = GetReportList(true);
		if ($files) $message = GM_LANG_report_titles_generated;
		else $message = "<span class=\"Error\">".GM_LANG_report_titles_not_generated."</span>";
		break;
	case "resetisdead": 
		if (AdminFunctions::ResetIsDead()) $message = GM_LANG_isdead_reset;
		else $message = "<span class=\"Error\">".GM_LANG_isdead_not_reset."</span>";
		break;
	case "buildisdead":
		$sql = "SELECT i_id, i_gedrec, i_file, i_isdead FROM ".TBLPREFIX."individuals WHERE i_isdead=-1 AND i_file='".GedcomConfig::$GEDCOMID."'";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$person = Person::GetInstance($row["i_id"], $row);
			$p = $person->isdead;
		}
		$message = GM_LANG_rebuilt_isdead;
		break;
	case "dispdbsettings":
		$url = "http://".$_SERVER["SERVER_NAME"]."/";
		$message = GM_LANG_disp_db_settings;
		$output = GM_LANG_DBHOST.": ".DBHOST."<br />";
		$output .= GM_LANG_DBUSER.": ".DBUSER."<br />";
		$output .= GM_LANG_DBNAME.": ".DBNAME."<br />";
		$output .= GM_LANG_TBLPREFIX.": ".TBLPREFIX."<br />";
		$output .= GM_LANG_DBPERSIST.": ".DBPERSIST;
		break;
	case "resetcaches":
		GedcomConfig::ResetCaches();
		$message = GM_LANG_reset_caches_ok;
		break;
	case "createmd5":
		AdminFunctions::GetGMFileList();
		break;
	case "checkmd5":
		$message = AdminFunctions::CheckGMFileList();
		break;
	default:
		$message = "";
		break;
}

PrintHeader(GM_LANG_administration_maintenance);
$err_write = AdminFunctions::FileIsWriteable("config.php");

$users = UserController::GetUsers();
$verify_msg = false;
$warn_msg = false;
foreach($users as $indexval => $user) {
	if (!$user->verified_by_admin && $user->verified)  {
		$verify_msg = true;
	}
	if (!empty($user->comment_exp)) {
		if ((strtotime($user->comment_exp) != "-1") && (strtotime($user->comment_exp) < time("U"))) $warn_msg = true;
	}
	if (($verify_msg) && ($warn_msg)) break;
}
?>
<!-- Setup the left box -->
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
</div>
	
<!-- Setup the right box -->
<div id="AdminColumnRight">
</div>

<!-- Setup the middle box -->
<div id="AdminColumnMiddle">
<table class="NavBlockTable AdminNavBlockTable">
	<?php
	$menu = new AdminMenu();
	$menu->SetBarText(GM_LANG_administration_maintenance);
	$menu->SetBarStyle("AdminNavBlockHeader");
	$menu->AddItem("help_sanity.php", "qm", "sc_sanity_check", "sanity.php", GM_LANG_sc_sanity_check, "left");
	$menu->AddItem("restart_setup_help", "qm", "restart_setup", "install/install.php", GM_LANG_restart_setup, "right");
	$menu->AddItem("load_english_help", "qm", "load_english", "admin_maint.php?action=loadenglish", GM_LANG_load_all_langs, "left");
	$menu->AddItem("reset_isdead_help", "qm", "reset_isdead", "admin_maint.php?action=resetisdead", GM_LANG_reset_isdead, "right");
	$menu->AddItem("generate_report_title_help", "qm", "generate_report_title", "admin_maint.php?action=reports", GM_LANG_generate_report_title, "left");
	$menu->AddItem("build_isdead", "qm", "build_isdead", "admin_maint.php?action=buildisdead", GM_LANG_build_isdead, "right");
	$menu->AddItem("reset_caches_help", "qm", "reset_caches", "admin_maint.php?action=resetcaches", GM_LANG_reset_caches, "left");
	$menu->AddItem("disp_db_settings_help", "qm", "disp_db_settings", "admin_maint.php?action=dispdbsettings", GM_LANG_disp_db_settings, "right");
	if (count($CONFIG_PARMS) > 1) $menu->AddItem("config_maint_help", "qm", "config_maint", "config_maint.php", GM_LANG_config_maint, "left");
	$menu->AddItem("lockout_maint_help", "qm", "lockout_maint", "lockout_maint.php", GM_LANG_lockout_maint, "right");
	$menu->AddItem("check_md5_help", "qm", "check_md5", "admin_maint.php?action=checkmd5", GM_LANG_check_md5, "left");
	$menu->PrintItems();
	
	if ($message != "") {
		print "<tr><td colspan=\"2\" class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".$message."</td></tr>";
	}
	if ($output != "") {
		print "<tr><td colspan=\"2\" class=\"NavBlockLabel AdminNavBlockLabel\">".$output."</td></tr>";
	}
	?>
	</table>
</div>
<?php
PrintFooter();
?>
