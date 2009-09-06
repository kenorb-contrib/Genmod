<?php
/**
 * Online UI for editing locked out users and IP's
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
	if (LOGIN_URL == "") header("Location: login.php?url=lockout_maint.php");
	else header("Location: ".LOGIN_URL."?url=lockout_maint.php");
	exit;
}

print_header($gm_lang["lockout_maint"]);

//if ($action == "update") {
//	print "<pre>";
//	print_r($_POST);
//	print "</pre>";
//}

?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print $gm_lang["admin"];?></a></div>
	<div class="admin_link"><a href="admin_maint.php"><?php print $gm_lang["administration_maintenance"];?></a></div>
</div>

<div id="content">
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
			if ($u->is_empty) {
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
		<div class="admin_topbottombar">
			<h3>
				<?php print_help_link("lockout_maint_help", "qm", "lockout_maint");?>
				<?php print $gm_lang["lockout_maint"]; ?>
			</h3>
		</div>
		<?php
		$sql = "SELECT * FROM ".TBLPREFIX."lockout ORDER BY lo_timestamp ASC";
		$res = NewQuery($sql);
		if ($res && $res->NumRows() > 0) {
			?><div class="admin_item_box shade2">
				<div class="width10 choice_left"><?php print $gm_lang["select"]; ?></div>
				<div class="width15 choice_middle"><?php print $gm_lang["ip_address"]; ?></div>
				<div class="width20 choice_middle"><?php print $gm_lang["username"]; ?></div>
				<div class="width20 choice_middle"><?php print $gm_lang["lockout_at"]; ?></div>
				<div class="width20 choice_right"><?php print $gm_lang["release_at"]; ?></div>
			</div><?php
			while ($row = $res->FetchRow()) {
				$locktime = GetChangedDate(date("d", $row[1])." ".date("M", $row[1])." ".date("Y", $row[1]))." - ".date($TIME_FORMAT, $row[1]);
				if($row[2] == "0") $releasetime = $gm_lang["until_unlocked"];
				else {
					$releasetime = GetChangedDate(date("d", $row[2])." ".date("M", $row[2])." ".date("Y", $row[2]))." - ".date($TIME_FORMAT, $row[2]);
				}
				?>
				<div class="admin_item_box">
				<div class="width10 choice_left"><input type="checkbox" name="dellock[]" value="
				<?php print $row[0]."#".$row[3]; ?>
				" />
				</div>
				<div class="width15 choice_middle"><?php print $row[0]; ?></div>
				<div class="width20 choice_middle"><?php print $row[3]; ?></div>
				<div class="width20 choice_middle"><?php print $locktime; ?></div>
				<div class="width20 choice_right"><?php print $releasetime; ?></div>
				</div>
			<?php } ?>
			<div class="admin_item_box center shade2">
				<input type="submit" value="<?php print $gm_lang["delete_selected"];?>" />
			</div>
		<?php } 
		else { ?>
			<div class="admin_item_box center">
			<?php print $gm_lang["no_lockouts"]; ?>
			</div>
		<?php } ?>
	</form><br />
	<form method="post" name="lockoutaddform" action="lockout_maint.php">
		<input type="hidden" name="action" value="add" />
		<div class="admin_item_box shade2 center">
			<div class="width100 choice_middle"><?php print $gm_lang["add_lockout"]; ?></div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left"><?php print $gm_lang["ip_address"]; ?></div>
			<div class="width30 choice_middle"><input type="text" name="add_ip" value="<?php if (isset($add_ip)) print $add_ip;?>" size="15" maxlength="15" /></div>
			<?php if ($error1) {?><div class="width30 choice_right"><span class="error"><?php print $gm_lang["invalid_ip"]; ?></span></div><?php } ?>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left"><?php print $gm_lang["username"]; ?></div>
			<div class="width30 choice_middle"><input type="text" name="add_user" value="<?php if (isset($add_user)) print $add_user;?>" size="25" /></div>
			<?php if ($error2) {?><div class="width30 choice_right"><span class="error"><?php print $gm_lang["invalid_user"]; ?></span></div><?php } ?>
		</div>
			<div class="admin_item_box center shade2">
				<input type="submit" value="<?php print $gm_lang["lockout_submit"];?>" />
			</div>
	</form>
</div>
<?php
print_footer();
?>