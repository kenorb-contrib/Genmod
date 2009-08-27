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
if (!$Users->userGedcomAdmin($gm_username)) {
	if (empty($LOGIN_URL)) header("Location: login.php?url=admin_maint.php");
	else header("Location: ".$LOGIN_URL."?url=admin_maint.php");
	exit;
}

if (!isset($action)) $action="";
$message = "";
$output = "";
switch ($action) {
	case "loadenglish" :
		if (StoreEnglish()) {
               WriteToLog("AdminMaint-> ".$gm_lang["all_loaded"]);
               $message = $gm_lang["all_loaded"];
          }
          else {
               LoadEnglish();
               LoadEnglishFacts();
               WriteToLog("AdminMaint-> ".$gm_lang["all_not_loaded"], "E", "S");
               $message = "<span class=\"error\">".$gm_lang["all_not_loaded"]."</span>";
		}
		break;
	case "reports": 
		$files = GetReportList(true);
		if ($files) $message = $gm_lang["report_titles_generated"];
		else $message = "<span class=\"error\">".$gm_lang["report_titles_not_generated"]."</span>";
		break;
	case "resetisdead": 
		if (ResetIsDead()) $message = $gm_lang["isdead_reset"];
		else $message = "<span class=\"error\">".$gm_lang["isdead_not_reset"]."</span>";
		break;
	case "buildisdead":
		$sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_isdead=-1 AND i_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			IsDeadId($row["i_id"]);
		}
		break;
	case "dispdbsettings":
		$url = "http://".$_SERVER["SERVER_NAME"]."/";
		$message = $gm_lang["disp_db_settings"];
		$output = $gm_lang["DBHOST"].": ".$DBHOST."<br />";
		$output .= $gm_lang["DBUSER"].": ".$DBUSER."<br />";
		$output .= $gm_lang["DBNAME"].": ".$DBNAME."<br />";
		$output .= $gm_lang["TBLPREFIX"].": ".$TBLPREFIX."<br />";
		$output .= $gm_lang["DBPERSIST"].": ".$DBPERSIST;
		break;
	case "resetcaches":
		ResetCaches();
		$message = $gm_lang["reset_caches_ok"];
		break;
	default:
		$message = "";
		break;
}

print_header($gm_lang["administration_maintenance"]);
$err_write = FileIsWriteable("config.php");

$users = $Users->GetUsers();
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
	<div class="admin_link"><a href="admin.php"><?php print $gm_lang["admin"];?></a></div>
</div>
	
<!-- Setup the right box -->
<div id="admin_genmod_right">
</div>

<!-- Setup the middle box -->
<div id="content">
	<div class="admin_topbottombar">
		<?php print "<h3>".$gm_lang["administration_maintenance"]."</h3>"; ?>
	</div>
	<div class="admin_item_box">
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("help_sanity.php", "qm", "sc_sanity_check"); ?></div><div class="description"><a href="sanity.php"><?php print $gm_lang["sc_sanity_check"];?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("restart_setup_help", "qm", "restart_setup"); ?></div><div class="description"><a href="install/install.php"><?php print $gm_lang["restart_setup"];?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("load_english_help", "qm", "load_english"); ?></div><div class="description"><a href="admin_maint.php?action=loadenglish"><?php print $gm_lang["load_all_langs"];?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("reset_isdead_help", "qm", "reset_isdead"); ?></div><div class="description"><a href="admin_maint.php?action=resetisdead"><?php print $gm_lang["reset_isdead"];?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("generate_report_title_help", "qm", "generate_report_title"); ?></div><div class="description"><a href="admin_maint.php?action=reports"><?php print $gm_lang["generate_report_title"];?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("build_isdead", "qm", "build_isdead"); ?></div><div class="description"><a href="admin_maint.php?action=buildisdead"><?php print $gm_lang["build_isdead"];?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("reset_caches_help", "qm", "reset_caches"); ?></div><div class="description"><a href="admin_maint.php?action=resetcaches"><?php print $gm_lang["reset_caches"];?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("disp_db_settings_help", "qm", "disp_db_settings"); ?></div><div class="description"><a href="admin_maint.php?action=dispdbsettings"><?php print $gm_lang["disp_db_settings"];?></a></div></div>
		<div class="admin_item_left"><div class="helpicon"><?php print_help_link("config_maint_help", "qm", "config_maint"); ?></div><div class="description"><a href="config_maint.php"><?php print $gm_lang["config_maint"];?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php print_help_link("lockout_maint_help", "qm", "lockout_maint"); ?></div><div class="description"><a href="lockout_maint.php"><?php print $gm_lang["lockout_maint"];?></a></div></div>
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
print_footer();
?>
