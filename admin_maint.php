<?php
/**
 * Administrative User Interface.
 *
 * Provides links for administrators to get to other administrative areas of the site
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
		if (StoreEnglish()) {
               WriteToLog("AdminMaint-> ".GM_LANG_all_loaded);
               $message = GM_LANG_all_loaded;
          }
          else {
               LoadEnglish();
               LoadEnglishFacts();
               WriteToLog("AdminMaint-> ".GM_LANG_all_not_loaded, "E", "S");
               $message = "<span class=\"error\">".GM_LANG_all_not_loaded."</span>";
		}
		break;
	case "reports": 
		$files = GetReportList(true);
		if ($files) $message = GM_LANG_report_titles_generated;
		else $message = "<span class=\"error\">".GM_LANG_report_titles_not_generated."</span>";
		break;
	case "resetisdead": 
		if (ResetIsDead()) $message = GM_LANG_isdead_reset;
		else $message = "<span class=\"error\">".GM_LANG_isdead_not_reset."</span>";
		break;
	case "buildisdead":
		$sql = "SELECT i_id, i_gedrec, i_file, i_isdead FROM ".TBLPREFIX."individuals WHERE i_isdead=-1 AND i_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$person = Person::GetInstance($row["i_id"], $row);
			$p = $person->isdead;
		}
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
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print GM_LANG_admin;?></a></div>
</div>
	
<!-- Setup the right box -->
<div id="admin_genmod_right">
</div>

<!-- Setup the middle box -->
<div id="content">
	<div class="admin_topbottombar">
		<?php print "<h3>".GM_LANG_administration_maintenance."</h3>"; ?>
	</div>
	<div class="admin_item_box">
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("help_sanity.php", "qm", "sc_sanity_check"); ?></div><div class="description"><a href="sanity.php"><?php print GM_LANG_sc_sanity_check;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("restart_setup_help", "qm", "restart_setup"); ?></div><div class="description"><a href="install/install.php"><?php print GM_LANG_restart_setup;?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("load_english_help", "qm", "load_english"); ?></div><div class="description"><a href="admin_maint.php?action=loadenglish"><?php print GM_LANG_load_all_langs;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("reset_isdead_help", "qm", "reset_isdead"); ?></div><div class="description"><a href="admin_maint.php?action=resetisdead"><?php print GM_LANG_reset_isdead;?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("generate_report_title_help", "qm", "generate_report_title"); ?></div><div class="description"><a href="admin_maint.php?action=reports"><?php print GM_LANG_generate_report_title;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("build_isdead", "qm", "build_isdead"); ?></div><div class="description"><a href="admin_maint.php?action=buildisdead"><?php print GM_LANG_build_isdead;?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("reset_caches_help", "qm", "reset_caches"); ?></div><div class="description"><a href="admin_maint.php?action=resetcaches"><?php print GM_LANG_reset_caches;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("disp_db_settings_help", "qm", "disp_db_settings"); ?></div><div class="description"><a href="admin_maint.php?action=dispdbsettings"><?php print GM_LANG_disp_db_settings;?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("config_maint_help", "qm", "config_maint"); ?></div><div class="description"><a href="config_maint.php"><?php print GM_LANG_config_maint;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("lockout_maint_help", "qm", "lockout_maint"); ?></div><div class="description"><a href="lockout_maint.php"><?php print GM_LANG_lockout_maint;?></a></div></div>
	</div>
	<?php
	if ($message != "") {
		print "<div class=\"shade2 center\">".$message."</div>";
	}
	if ($output != "") {
		print "<div class=\"shade1 ltr\">".$output."</div>";
	}
	?>
</div>
<?php
PrintFooter();
?>
