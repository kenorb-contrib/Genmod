<?php
/**
 * Online UI for removing site configs
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
 * This Page Is Valid XHTML 1.0 Transitional! > 17 September 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @see config.php
 * @version $Id: config_maint.php 29 2022-07-17 13:18:20Z Boudewijn $
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
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	<?php AdminFunctions::AdminLink("admin_maint.php", GM_LANG_administration_maintenance); ?>
</div>

<div id="AdminColumnMiddle">
	<form method="post" name="configform" action="config_maint.php">
	<input type="hidden" name="action" value="update" />
	<?php
	if ($action == "update" && isset($delconf) && is_array($delconf)) {
		foreach ($delconf as $key => $value) {
			if (!SystemConfig::DeleteConfig($value)) {
				$message = "<span class=\"Error\">".GM_LANG_gm_config_write_error."</span>";
				break;
			}
			else unset($CONFIG_PARMS[$value]);
		}
	}
	?>
	<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="3" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
					<?php PrintHelpLink("config_maint_help", "qm", "config_maint");?>
					<?php print GM_LANG_config_maint;?>
				</div><?php
				if (isset($message)) {
					print "<div class=\"Error\">".$message."</div>";
				} ?>
			</td>
		</tr>
		<?php
		?>
		<tr>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_select; ?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_site_name; ?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_SITE_ALIAS; ?>
			</td>
		</tr>
		<?php
		foreach ($CONFIG_PARMS as $site => $parms) {
			if (isset($parms["SITE_ALIAS"])) $aliases = explode(",", $parms["SITE_ALIAS"]);
			else $aliases = array();
			?>
			<tr>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="delconf[]" value="<?php print $site."\"";
					if ($site == SERVER_URL || in_array(SERVER_URL, $aliases)) print " disabled=\"disabled\""; 
					?>/>
				</td>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<?php print $site; ?>
				</td>
				<td class="NavBlockLabel AdimNavBlockLabel">
				<?php
				foreach ($aliases as $key => $alias) {
					print $alias."<br />";
				}
				?>
				</td>
			</tr>
			<?php
		} ?>
		<tr>
			<td colspan="3" class="NavBlockFooter">
				<input type="submit" value="<?php print GM_LANG_delete_sel_configs;?>" />
			</td>
		</tr>
	</table>
	</form>
</div>
<?php
PrintFooter();
?>