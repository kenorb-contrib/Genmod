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
 * This Page Is Valid XHTML 1.0 Transitional! > 01 September 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: admin.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * load the main configuration and context
 */
require "config.php";

if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=admin.php");
	else header("Location: ".LOGIN_URL."?url=admin.php");
	exit;
}
if (!isset($action)) $action="";

if ($action == "loadlanguage" && isset($language)) {
	AdminFunctions::StoreLanguage($language);
}

PrintHeader(GM_LANG_administration);
?>
<script type="text/javascript">
<!--
function reload() {
	window.location.reload();
}
//-->
</script>
<?php

$err_write = AdminFunctions::FileIsWriteable("config.php");
$users = UserController::GetUsers();

$verify_msg = false;		// Users to be verified by admin
$emergency_msg = false;		// Emergency logfile exists
$warn_msg = false;			// Users with expiration set
$export_msg = false;		// Langfiles should be exported
$import_msg = false;		// Langfiles should be imported

foreach($users as $indexval => $user) {
	if (!$user->verified_by_admin && $user->verified)  {
		$verify_msg = true;
	}
	if (!empty($user->comment_exp)) {
		if ((strtotime($user->comment_exp) != "-1") && (strtotime($user->comment_exp) < time("U"))) $warn_msg = true;
	}
	if (($verify_msg) && ($warn_msg)) break;
}

$implangs = AdminFunctions::GetLangfileInfo("import");
if (count($implangs) > 0) $import_msg = true;
$explangs = AdminFunctions::GetLangfileInfo("export");
if (count($explangs) > 0) $export_msg = true;

if (file_exists(INDEX_DIRECTORY."emergency_syslog.txt")) {
	$emergency_msg = true;
	$emergency_text = AdminFunctions::ImportEmergencyLog();
}
?>
<!-- Setup the left box -->
<div id="AdminColumnLeft">

<?php
$news = AdminFunctions::GetGMNewsItems();
if (count($news)>0) { ?>
     <div class="NavBlockHeader"><?php print GM_LANG_genmodnews; ?></div>
      <div class="AdminGenmodContent">
      <?php 
      foreach ($news as $key => $item) {
           if (SystemConfig::$NEWS_TYPE == "Normal" || $item["type"] == SystemConfig::$NEWS_TYPE) {
                print "<b>".$item["date"];
                if ($item["type"] == "Urgent") print "&nbsp;-&nbsp;<span class=\"Error\">".$item["type"]."</span>";
                print "&nbsp;-&nbsp;".$item["header"]."</b><br />";
                print $item["text"]."<br /><br />";
           }
      }
      print "</div>";
} ?>
</div>
	
<!-- Setup the right box -->
<div id="AdminColumnRight">
     <div class="<?php print $TEXT_DIRECTION ?>">
     	<div class="NavBlockHeader"><?php print GM_LANG_messages; ?></div>
          <?php
          if ($gm_user->userIsAdmin()) {
               if ($err_write) {
                    print "<div class=\"Error AdminGenmodContent\">";
                    print GM_LANG_config_still_writable;
                    print "</div>";
               }
               if ($verify_msg) {
                    print "<div class=\"AdminGenmodContent\">";
                    print "<a href=\"useradmin.php?action=listusers&amp;filter=admunver\" class=\"Error\">".GM_LANG_admin_verification_waiting."</a>";
                    print "</div>";
               }
               if ($warn_msg) {
                    print "<div class=\"AdminGenmodContent\">";
                    print "<a href=\"useradmin.php?action=listusers&amp;filter=warnings\" class=\"Error\" >".GM_LANG_admin_user_warnings."</a>";
                    print "</div>";
               }
               if ($export_msg) {
                    print "<div class=\"Error AdminGenmodContent\">";
                    print GM_LANG_export_warn;
                    foreach ($explangs as $key => $explang) {
	                    print "<br /><a href=\"editlang.php?action=export&amp;language2=".$explang["lang"]."\" >".$explang["name"]."</a>";
                    }
                    print "</div>";
               }
               if ($import_msg) {
                    print "<div class=\"Error AdminGenmodContent\">";
                    print GM_LANG_import_warn;
                    $skip = false;
                    foreach ($implangs as $key => $implang) {
	                    if ($implang["lang"] == "english") {
		                    print "<br /><a href=\"admin.php?action=loadlanguage&language=".$implang["lang"]."\" >".$implang["name"]."</a>";
		                    $skip = true;
	                    }
                    }
                    if (!$skip) {
	                    foreach ($implangs as $key => $implang) {
		                    print "<br /><a href=\"admin.php?action=loadlanguage&language=".$implang["lang"]."\" >".$implang["name"]."</a>";
        	            }
    	            }
                    print "</div>";
               }
               if ($emergency_msg) {
                    print "<div class=\"Error AdminGenmodContent\">";
                    print $emergency_text;
                    print "</div>";
               }
          }?>
     </div>
</div>

<!-- Setup the middle box -->
<div id="AdminColumnMiddle">
	<table class="NavBlockTable AdminNavBlockTable">
	<?php
		// Header bar
		$menu = new AdminMenu();
		$menu->SetBarStyle("AdminNavBlockHeader");
		$menu->SetBarText("Genmod v" . GM_VERSION . " " . GM_VERSION_RELEASE . "<br />" . GM_LANG_administration, false);
		$menu->SetSubBarText(GM_LANG_system_time." ".GetChangedDate(date("j M Y"))." - ".date($TIME_FORMAT));
		$menu->PrintItems();
		
		// Informational part of the menu
		$menu = new AdminMenu();
		$menu->SetBarText(GM_LANG_admin_info, true);
		$menu->SetBarStyle("");
		$menu->AddItem("readmefile_help", "qm", "", 'readme.txt" target="manual" title="'.GM_LANG_view_readme, GM_LANG_readme_documentation, "left");
		$menu->AddItem("phpinfo_help", "qm", "", 'gminfo.php?action=phpinfo" title="'.GM_LANG_show_phpinfo, GM_LANG_phpinfo, "right");
		$menu->AddItem("config_help_help", "qm", "help_config", "gminfo.php?action=confighelp", GM_LANG_help_config, "left");
		$menu->AddItem("changelog_help", "qm", "", 'changelog.php" target="manual" title="'.PrintText("changelog",0 ,1), PrintText("changelog",0 ,1), "right");
		$menu->PrintSpacer();
		$menu->PrintItems();

		// Gedcom related part of the menu
		$menu = new AdminMenu();
		$menu->SetBarText(GM_LANG_admin_geds, true);
		$menu->SetBarStyle("");
		$menu->AddItem("edit_gedcoms_help", "qm", "", "editgedcoms.php", GM_LANG_manage_gedcoms, "left");
		$menu->AddItem("help_edit_merge.php", "qm", "", "edit_merge.php", GM_LANG_merge_records, "right");
		$menu->AddItem("help_media.php", "qm", "manage_media", "media.php", GM_LANG_manage_media, "left");
		if (GedcomConfig::$ALLOW_EDIT_GEDCOM) {
			$menu->AddItem("edit_add_unlinked_person_help", "qm", "", "javascript: ".GM_LANG_add_unlinked_person."\" onclick=\"addnewchild('','add_unlinked_person'); return false;", GM_LANG_add_unlinked_person, "right");
		}
		if (ChangeFunctions::GetChangeData(true, "", true)) {
			$menu->AddItem("review_changes_help", "qm", "", "#\" onclick=\"window.open('edit_changes.php','','width=600,height=600,resizable=1,scrollbars=1'); return false;", GM_LANG_accept_changes, "left");
		}
		$menu->PrintSpacer();
		$menu->PrintItems();
		// Admin part of the menu
		if ($gm_user->userIsAdmin()) {
			$menu = new AdminMenu();
			$menu->SetBarText(GM_LANG_admin_site, true);
			$menu->SetBarStyle("");
			$menu->AddItem("help_editconfig.php", "qm", "", "editconfig.php", GM_LANG_configuration, "left");
			$menu->AddItem("um_bu_help", "qm", "", "backup.php?action=backup", GM_LANG_um_backup, "right");
			$menu->AddItem("help_useradmin.php", "qm", "", "useradmin.php", GM_LANG_user_admin, "left");
			$menu->AddItem("um_rest_help", "qm", "", "backup.php?action=restore", GM_LANG_um_restore, "right");
			$menu->AddItem("help_editlang.php", "qm", "", "editlang.php", GM_LANG_translator_tools, "left");
			$menu->AddItem("help_faq.php", "qm", "", "faq.php", GM_LANG_faq_list, "right");
			$menu->AddItem("admin_maint_help", "qm", "maintenance", "admin_maint.php", GM_LANG_maintenance, "left");
			$menu->AddItem("help_viewlog.php", "qm", "view_syslog", "javascript: ".GM_LANG_view_syslog."\" onclick=\"window.open('viewlog.php?cat=S&amp;max=20', '', 'top=50,left=10,width=1000,height=600,scrollbars=1,resizable=1'); ChangeClass('syslog', ''); return false;", (AdminFunctions::NewLogRecs("S") ? "<span id=\"syslog\" class=\"Error\">".GM_LANG_view_syslog."</span>" : "<span id=\"syslog\">".GM_LANG_view_syslog."</span>"), "right");
			$menu->PrintSpacer();
			$menu->PrintItems();
		}
	?>
	</table>
</div>
<?php
PrintFooter();
?>
