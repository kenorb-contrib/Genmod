<?php
/**
 * Administrative User Interface.
 *
 * Provides links for administrators to get to other administrative areas of the site
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
 * This Page Is Valid XHTML 1.0 Transitional! > 01 September 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: admin.php,v 1.19 2006/03/08 20:46:01 roland-d Exp $
 */

/**
 * load the main configuration and context
 */
require "config.php";
if (!userGedcomAdmin($gm_username)) {
	if (empty($LOGIN_URL)) header("Location: login.php?url=admin.php");
	else header("Location: ".$LOGIN_URL."?url=admin.php");
	exit;
}

if (!isset($action)) $action="";

print_header($gm_lang["administration"]);
$err_write = file_is_writeable("config.php");

$users = getUsers();
$verify_msg = false;
$warn_msg = false;
foreach($users as $indexval => $user) {
	if (!$user["verified_by_admin"] && $user["verified"])  {
		$verify_msg = true;
	}
	if (!empty($user["comment_exp"])) {
		if ((strtotime($user["comment_exp"]) != "-1") && (strtotime($user["comment_exp"]) < time("U"))) $warn_msg = true;
	}
	if (($verify_msg) && ($warn_msg)) break;
}
?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
     <div class="<?php print $TEXT_DIRECTION ?>">

 	<?php
	$news = GetNewsItems();
	if (count($news)>0) { ?>
	     <div class="admin_topbottombar"><?php print $gm_lang["genmodnews"]; ?></div>
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
          if (userIsAdmin($gm_username)) {
               if ($err_write) {
                    
                    print "<div class=\"error admin_genmod_content\">";
                    print $gm_lang["config_still_writable"];
                    print "</div>";
               }
               if ($verify_msg) {
                    print "<div class=\"admin_genmod_content\">";
                    print "<a href=\"useradmin.php?action=listusers&amp;filter=admunver\" class=\"error\">".$gm_lang["admin_verification_waiting"]."</a>";
                    print "</div>";
               }
               if ($warn_msg) {
                    print "<div class=\"admin_genmod_content\">";
                    print "<a href=\"useradmin.php?action=listusers&amp;filter=warnings\" class=\"error\" >".$gm_lang["admin_user_warnings"]."</a>";
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
      	print "<h2>Genmod v" . $VERSION . " " . $VERSION_RELEASE . "<br />";
      	print $gm_lang["administration"];
      	print "</h2>";
      	print $gm_lang["system_time"];
      	print " ".get_changed_date(date("j M Y"))." - ".date($TIME_FORMAT);
     ?>
     </div>
     <div>
     <br />
          <div class="admin_topbottombar"><?php print $gm_lang["admin_info"]; ?></div>
          <div>
             	<table style="width: 99%;">
          	<tr>
               <td class="shade1 width50"><?php print_help_link("readmefile_help", "qm"); ?><a href="readme.txt" target="manual" title="<?php print $gm_lang["view_readme"]; ?>"><?php print $gm_lang["readme_documentation"];?></a></td>
               <td class="shade1"><?php print_help_link("phpinfo_help", "qm"); ?><a href="gminfo.php?action=phpinfo" title="<?php print $gm_lang["show_phpinfo"]; ?>"><?php print $gm_lang["phpinfo"];?></a></td>
          	</tr>
          	<tr>
               <td class="shade1"><?php print_help_link("config_help_help", "qm", "help_config"); ?><a href="gminfo.php?action=confighelp"><?php print $gm_lang["help_config"];?></a></td>
               <td class="shade1"><?php print_help_link("changelog_help", "qm"); ?><a href="changelog.php" target="manual" title="<?php print $gm_lang["view_changelog"]; ?>"><?php print_text("changelog"); ?></a></td>
          	</tr>
          	</table>
          	
          	<div class="admin_topbottombar"><?php print $gm_lang["admin_geds"]; ?></div>
               <table style="width: 99%;">
               <tr>
               <td class="shade1 width50"><?php print_help_link("edit_gedcoms_help", "qm"); ?><a href="editgedcoms.php"><?php print $gm_lang["manage_gedcoms"];?></a></td>
               <td class="shade1"><?php print_help_link("help_edit_merge.php", "qm"); ?><a href="edit_merge.php"><?php print $gm_lang["merge_records"]; ?></a></td>
               </tr>
               <tr>
               <td class="shade1"><?php print_help_link("help_uploadmedia.php", "qm", "manage_media"); ?><a href="media.php"><?php print $gm_lang["manage_media"];?></a></td>
               <td class="shade1"><?php print_help_link("edit_add_unlinked_person_help", "qm"); ?><a href="javascript: <?php print $gm_lang["add_unlinked_person"]; ?>" onclick="addnewchild(''); return false;"><?php print $gm_lang["add_unlinked_person"]; ?></a></td>
               </tr>
               <tr>
          		<?php if (change_present()) print "<td class=\"shade1\"><a href=\"#\" onclick=\"window.open('edit_changes.php','','width=600,height=600,resizable=1,scrollbars=1'); return false;\">".$gm_lang["accept_changes"]."</a></td>";
          		else print "<td class=\"shade1\">&nbsp;</td>";
          		print "<td class=\"shade1\">&nbsp;</td>";
          		?>
               </tr>
               </table>
               <?php if (userIsAdmin($gm_username)) { ?>
               	<div class="admin_topbottombar"><?php print $gm_lang["admin_site"]; ?></div>
                    
                    <table style="width: 99%;">
                    <tr>
                    <td class="shade1 width50"><?php print_help_link("help_editconfig.php", "qm"); ?><a href="editconfig.php"><?php print $gm_lang["configuration"];?></a></td>
                    <td class="shade1"><?php print_help_link("um_bu_help", "qm"); ?><a href="backup.php?action=backup"><?php print $gm_lang["um_backup"];?></a></td>
                    </tr>
                    <tr>
                    <td class="shade1"><?php print_help_link("help_useradmin.php", "qm"); ?><a href="useradmin.php"><?php print $gm_lang["user_admin"];?></a></td>
                    <td class="shade1"><?php print_help_link("um_rest_help", "qm"); ?><a href="backup.php?action=restore"><?php print $gm_lang["um_restore"];?></a></td>
                    </tr>
                    <tr>
                    <td class="shade1"><?php print_help_link("help_editlang.php", "qm"); ?><a href="editlang.php"><?php print $gm_lang["translator_tools"];?></a>
                    </td>
                    <td class="shade1"><?php print_help_link("help_faq.php", "qm"); ?><a href="faq.php"><?php print $gm_lang["faq_list"];?></a></td>
                    </tr>
               	    <?php
               		print "<tr><td class=\"shade1\">";
               		print_help_link("help_sanity.php", "qm");
               		print "<a href=\"sanity.php\">".$gm_lang["sc_sanity_check"]."</a></td>";
               		print "<td class=\"shade1\">";
                   	     print_help_link("help_viewlog.php", "qm", "view_syslog");
                         print "<a href=\"javascript: ".$gm_lang["view_syslog"]."\" onclick=\"window.open('viewlog.php?cat=S&amp;max=20', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1'); return false;\">".$gm_lang["view_syslog"]."</a>";
                         print "</td></tr>";
                    print "</table>";
               }?>
          </div>
     </div>
</div>
<?php
print_footer();
?>
