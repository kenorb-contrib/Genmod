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
 * This Page Is Valid XHTML 1.0 Transitional! > 01 September 2005
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
<div id="admin_genmod_left">
     <div class="<?php print $TEXT_DIRECTION ?>">

 	<?php
	$news = AdminFunctions::GetGMNewsItems();
	if (count($news)>0) { ?>
	     <div class="admin_topbottombar"><?php print GM_LANG_genmodnews; ?></div>
          <div class="admin_genmod_content">
          <?php 
          foreach ($news as $key => $item) {
               if ($NEWS_TYPE == "Normal" || $item["type"] == $NEWS_TYPE) {
                    print "<b>".$item["date"];
                    if ($item["type"] == "Urgent") print "&nbsp;-&nbsp;<span class=\"error\">".$item["type"]."</span>";
                    print "&nbsp;-&nbsp;".$item["header"]."</b><br />";
                    print $item["text"]."<br /><br />";
               }
          }
          print "</div>";
	} ?>
	</div>
</div>
	
<!-- Setup the right box -->
<div id="admin_genmod_right">
     <div class="<?php print $TEXT_DIRECTION ?>">
     	<div class="admin_topbottombar">Messages</div>
          <div class="admin_genmod_content">
          <?php
          if ($gm_user->userIsAdmin()) {
               if ($err_write) {
                    print "<div class=\"error admin_genmod_content\">";
                    print GM_LANG_config_still_writable;
                    print "</div>";
               }
               if ($verify_msg) {
                    print "<div class=\"admin_genmod_content\">";
                    print "<a href=\"useradmin.php?action=listusers&amp;filter=admunver\" class=\"error\">".GM_LANG_admin_verification_waiting."</a>";
                    print "</div>";
               }
               if ($warn_msg) {
                    print "<div class=\"admin_genmod_content\">";
                    print "<a href=\"useradmin.php?action=listusers&amp;filter=warnings\" class=\"error\" >".GM_LANG_admin_user_warnings."</a>";
                    print "</div>";
               }
               if ($export_msg) {
                    print "<div class=\"error admin_genmod_content\">";
                    print GM_LANG_export_warn;
                    foreach ($explangs as $key => $explang) {
	                    print "<br /><a href=\"editlang.php?action=export&amp;language2=".$explang["lang"]."\" >".$explang["name"]."</a>";
                    }
                    print "</div>";
               }
               if ($import_msg) {
                    print "<div class=\"error admin_genmod_content\">";
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
                    print "<div class=\"error admin_genmod_content\">";
                    print $emergency_text;
                    print "</div>";
               }
          }?>
          </div>
     </div>
</div>

<!-- Setup the middle box -->
<div id="content">
	<div class="admin_topbottombar">
		<?php
		print "<h3>Genmod v" . GM_VERSION . " " . GM_VERSION_RELEASE . "<br />";
		print GM_LANG_administration;
		print "</h3>";
		print GM_LANG_system_time;
		print " ".GetChangedDate(date("j M Y"))." - ".date($TIME_FORMAT);
		?>
	</div>
	<div class="admin_topbottombar" style="margin-top: 1em;"><?php print GM_LANG_admin_info; ?></div>
	<div class="admin_item_box">
		<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("readmefile_help", "qm"); ?></div><div class="description"><a href="readme.txt" target="manual" title="<?php print GM_LANG_view_readme; ?>"><?php print GM_LANG_readme_documentation;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php PrintHelpLink("phpinfo_help", "qm"); ?></div><div class="description"><a href="gminfo.php?action=phpinfo" title="<?php print GM_LANG_show_phpinfo; ?>"><?php print GM_LANG_phpinfo;?></a></div></div>
	</div>
	<div class="admin_item_box">
		<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("config_help_help", "qm", "help_config"); ?></div><div class="description"><a href="gminfo.php?action=confighelp"><?php print GM_LANG_help_config;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php PrintHelpLink("changelog_help", "qm"); ?></div><div class="description"><a href="changelog.php" target="manual" title="<?php PrintText("changelog"); ?>"><?php PrintText("changelog"); ?></a></div></div>
	</div>
	
	<div class="admin_topbottombar" style="margin-top: 1em;"><?php print GM_LANG_admin_geds; ?></div>
	<div class="admin_item_box">
		<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("edit_gedcoms_help", "qm"); ?></div><div class="description"><a href="editgedcoms.php"><?php print GM_LANG_manage_gedcoms;?></a></div></div>
		<div class="admin_item_right"><div class="helpicon"><?php PrintHelpLink("help_edit_merge.php", "qm"); ?></div><div class="description"><a href="edit_merge.php"><?php print GM_LANG_merge_records; ?></a></div></div>
	</div>
	<div class="admin_item_box">
		<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("help_media.php", "qm", "manage_media"); ?></div><div class="description"><a href="media.php"><?php print GM_LANG_manage_media;?></a></div></div>
		<div class="admin_item_right"><?php if (GedcomConfig::$ALLOW_EDIT_GEDCOM) { ?>
			<div class="helpicon"><?php PrintHelpLink("edit_add_unlinked_person_help", "qm"); ?></div><div class="description"><a href="javascript: <?php print GM_LANG_add_unlinked_person; ?>" onclick="addnewchild('','add_unlinked_person'); return false;"><?php print GM_LANG_add_unlinked_person; ?></a></div>
			<?php }
			else print "&nbsp;"; ?>
		</div>
	</div>
	<?php if (ChangeFunctions::GetChangeData(true, "", true)) { ?>
		<div class="admin_item_box">
			<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("review_changes_help", "qm"); ?></div><div class="description"><a href="#" onclick="window.open('edit_changes.php','','width=600,height=600,resizable=1,scrollbars=1'); return false;"><?php print GM_LANG_accept_changes; ?></a></div></div>
			<div class="admin_item_right"><div class="helpicon"></div><div class="description">&nbsp;</div></div>
		</div>
	<?php } ?>
	
	<?php if ($gm_user->userIsAdmin()) { ?>
		<div class="admin_topbottombar" style="margin-top: 1em;"><?php print GM_LANG_admin_site; ?></div>
		<div class="admin_item_box">
			<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("help_editconfig.php", "qm"); ?></div><div class="description"><a href="editconfig.php"><?php print GM_LANG_configuration;?></a></div></div>
			<div class="admin_item_right"><div class="helpicon"><?php PrintHelpLink("um_bu_help", "qm"); ?></div><div class="description"><a href="backup.php?action=backup"><?php print GM_LANG_um_backup;?></a></div></div>
		</div>
		<div class="admin_item_box">
			<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("help_useradmin.php", "qm"); ?></div><div class="description"><a href="useradmin.php"><?php print GM_LANG_user_admin;?></a></div></div>
			<div class="admin_item_right"><div class="helpicon"><?php PrintHelpLink("um_rest_help", "qm"); ?></div><div class="description"><a href="backup.php?action=restore"><?php print GM_LANG_um_restore;?></a></div></div>
		</div>
		<div class="admin_item_box">
			<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("help_editlang.php", "qm"); ?></div><div class="description"><a href="editlang.php"><?php print GM_LANG_translator_tools;?></a></div></div>
			<div class="admin_item_right"><div class="helpicon"><?php PrintHelpLink("help_faq.php", "qm"); ?></div><div class="description"><a href="faq.php"><?php print GM_LANG_faq_list;?></a></div></div>
		</div>
		<div class="admin_item_box">
			<div class="admin_item_left"><div class="helpicon"><?php PrintHelpLink("admin_maint_help", "qm", "maintenance"); ?></div><div class="description"><a href="admin_maint.php"><?php print GM_LANG_maintenance; ?></a></div></div>
			<div class="admin_item_right"><div class="helpicon"><?php PrintHelpLink("help_viewlog.php", "qm", "view_syslog"); ?></div><div class="description"><a href="javascript: <?php print GM_LANG_view_syslog;?>" onclick="window.open('viewlog.php?cat=S&amp;max=20', '', 'top=50,left=10,width=1000,height=600,scrollbars=1,resizable=1'); ChangeClass('syslog', 'shade1'); return false;">
			<?php
			if (AdminFunctions::NewLogRecs("S")) print "<span id=\"syslog\" class=\"error\">".GM_LANG_view_syslog."</span>";
			else print "<span id=\"syslog\">".GM_LANG_view_syslog."</span>";
			?>
			</a></div></div>
		</div>
	<?php } ?>
</div>
<?php
PrintFooter();
?>