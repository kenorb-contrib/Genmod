<?php
/**
 * Online UI for editing locked out users and IP's
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
 * @version $Id: lockout_maint.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

if (empty($action)) $action="";

//-- make sure that they have gedcom admin status before they can use this page
//-- otherwise have them login again
if (!$gm_user->userIsAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=lockout_maint.php");
	else header("Location: ".LOGIN_URL."?url=lockout_maint.php");
	exit;
}

PrintHeader(GM_LANG_lockout_maint);

//if ($action == "update") {
//	print "<pre>";
//	print_r($_POST);
//	print "</pre>";
//}

?>
<!-- Setup the left box -->
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	<?php AdminFunctions::AdminLink("admin_maint.php", GM_LANG_administration_maintenance); ?>
</div>

<div id="AdminColumnMiddle">
	<?php
	$error1 = false;
	$error2 = false;
	
	if ($action == "update" && isset($dellock)) {
		foreach ($dellock as $key => $value) {
			$value = explode ("#", $value);
			if (!isset($value[1])) $value[1] = "";
			$sql = "DELETE FROM ".TBLPREFIX."lockout WHERE lo_ip='".trim($value[0])."' AND lo_username='".trim($value[1])."'";
			$res = NewQuery($sql);
			if (!$res) print "error";
		}
	}
	if ($action == "add") {
		if (!empty($add_ip)) {
			if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $add_ip)) {
				$error1 = true;
			}
			else {
				$sql = "INSERT INTO ".TBLPREFIX."lockout VALUES ('".$add_ip."' , '".time()."', '0', '') ON DUPLICATE KEY UPDATE lo_timestamp='".time()."', lo_release='0'";
				$res = NewQuery($sql);
			}
		}
		else {
			$u =& User::GetInstance($add_user);
			if ($u->is_empty || empty($add_user)) {
				$error2 = true;
			}
			else {
				$sql = "INSERT INTO ".TBLPREFIX."lockout VALUES ('' , '".time()."', '0', '".$add_user."') ON DUPLICATE KEY UPDATE lo_timestamp='".time()."', lo_release='0'";
				$res = NewQuery($sql);
			}
		}
	}
	?>
	<form method="post" name="lockoutform" action="lockout_maint.php">
		<input type="hidden" name="action" value="update" />
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
				<td colspan="5" class="NavBlockHeader AdminNavBlockHeader">
				<?php print "<div class=\"AdminNavBlockTitle\">".GM_LANG_lockout_maint; ?>
				<?php PrintHelpLink("lockout_maint_help", "qm", "lockout_maint");?></div>
				</td>
			</tr>
		<?php
		$sql = "SELECT * FROM ".TBLPREFIX."lockout ORDER BY lo_timestamp ASC";
		$res = NewQuery($sql);
		if ($res && $res->NumRows() > 0) { ?>
			<tr>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader"><?php print GM_LANG_select; ?></td>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader"><?php print GM_LANG_ip_address; ?></td>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader"><?php print GM_LANG_username; ?></td>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader"><?php print GM_LANG_lockout_at; ?></td>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader"><?php print GM_LANG_release_at; ?></td>
			</tr>
			<?php
			while ($row = $res->FetchRow()) {
				$locktime = GetChangedDate(date("d", $row[1])." ".date("M", $row[1])." ".date("Y", $row[1]))." - ".date($TIME_FORMAT, $row[1]);
				if($row[2] == "0") $releasetime = GM_LANG_until_unlocked;
				else {
					$releasetime = GetChangedDate(date("d", $row[2])." ".date("M", $row[2])." ".date("Y", $row[2]))." - ".date($TIME_FORMAT, $row[2]);
				}
				?>
				<tr>
					<td class="AdminNavBlockField NavBlockField"><input type="checkbox" name="dellock[]" value="
					<?php print $row[0]."#".$row[3]; ?>
					" />
					<td class="AdminNavBlockLabel NavBlockLabel"><?php print $row[0]; ?></td>
					<td class="AdminNavBlockLabel NavBlockLabel"><?php print $row[3]; ?></td>
					<td class="AdminNavBlockLabel NavBlockLabel"><?php print $locktime; ?></td>
					<td class="AdminNavBlockLabel NavBlockLabel"><?php print $releasetime; ?></td>
				</tr>
			<?php } ?>
			<tr>
				<td class="NavBlockFooter" colspan="5">
					<input type="submit" value="<?php print GM_LANG_delete_selected;?>" />
				</td>
			</tr>
		<?php } 
		else { ?>
			<tr>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
					<?php print GM_LANG_no_lockouts; ?>
				</td>
			</tr>
		<?php } ?>
		</table>
	</form>
	<form method="post" name="lockoutaddform" action="lockout_maint.php">
		<input type="hidden" name="action" value="add" />
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
				<td colspan="2" class="NavBlockRowSpacer">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockHeader">
					<?php print GM_LANG_add_lockout; ?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockLabel"><?php print GM_LANG_ip_address; ?></td>
				<td class="NavBlockField AdminNavBlockField"><input type="text" name="add_ip" value="<?php if (isset($add_ip)) print $add_ip;?>" size="15" maxlength="15" />
					<?php if ($error1) {?><span class="Error"><?php print GM_LANG_invalid_ip; ?></span>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockLabel"><?php print GM_LANG_username; ?></td>
				<td class="NavBlockField AdminNavBlockField"><input type="text" name="add_user" value="<?php if (isset($add_user)) print $add_user;?>" size="25" />
				<?php if ($error2) {?><span class="Error"><?php print GM_LANG_invalid_user; ?></span>
				<?php } ?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockFooter" colspan="2">
					<input type="submit" value="<?php print GM_LANG_lockout_submit;?>" />
				</td>
		</tr>
	</table>
	</form>
</div>
<?php
PrintFooter();
?>