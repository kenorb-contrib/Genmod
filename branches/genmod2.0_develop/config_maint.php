<?php
/**
 * Online UI for removing site configs
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
 * This Page Is Valid XHTML 1.0 Transitional! > 17 September 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @see config.php
 * @version $Id$
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

if (empty($action)) $action="";

//-- make sure that they have gedcom admin status before they can use this page
//-- otherwise have them login again
if (!$gm_user->userIsAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=config_maint.php");
	else header("Location: ".LOGIN_URL."?url=config_maint.php");
	exit;
}

PrintHeader(GM_LANG_config_maint);

?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print GM_LANG_admin;?></a></div>
	<div class="admin_link"><a href="admin_maint.php"><?php print GM_LANG_administration_maintenance;?></a></div>
</div>

<div id="content">
	<?php
	if ($action == "update" && isset($delconf)) {
		foreach ($delconf as $key => $value) {
			if (!SystemConfig::DeleteConfig($value)) {
				$message = "<span class=\"error\">".GM_LANG_gm_config_write_error."</span>";
				break;
			}
			else unset($CONFIG_PARMS[$value]);
		}
	}
	?>
	<form method="post" name="configform" action="config_maint.php">
		<input type="hidden" name="action" value="update" />
		<div class="admin_topbottombar">
			<h3>
				<?php PrintHelpLink("config_maint_help", "qm", "config_maint");?>
				<?php print GM_LANG_config_maint; ?>
			</h3>
		</div>
		<?php
		if (isset($message)) {
			print "<div class=\"shade2 center\">".$message."</div>";
		}
		?>
		<div class="admin_item_box shade2">
			<div class="width10 choice_left"><?php print GM_LANG_select; ?></div>
			<div class="width30 choice_right"><?php print GM_LANG_site_name; ?></div>
			<div class="width30 choice_right"><?php print GM_LANG_SITE_ALIAS; ?></div>
		</div>
		<?php
			foreach ($CONFIG_PARMS as $site => $parms) {
				if (isset($parms["SITE_ALIAS"])) $aliases = explode(",", $parms["SITE_ALIAS"]);
				else $aliases = array();
				?>
				<div class="admin_item_box">
				<div class="width10 choice_left"><input type="checkbox" name="delconf[]" value="<?php print $site."\"";
					if ($site == SERVER_URL || in_array(SERVER_URL, $aliases)) print " disabled=\"disabled\""; 
				?>/>
				</div>
				<div class="width30 choice_right"><?php print $site; ?></div>
				<div class="width30 choice_right"><?php 
				foreach ($aliases as $key => $alias) {
					print $alias."<br />";
				}
				?></div>
				</div>
			<?php } ?>
			<div class="admin_item_box center shade2">
				<br />
				<input type="submit" value="<?php print GM_LANG_delete_sel_configs;?>" />
			</div>
	</form>
</div>
<?php
PrintFooter();
?>