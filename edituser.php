<?php
/**
 * Administrative User Interface.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 19 August 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the editing functions
*/
include "includes/functions/functions_edit.php";

if (!isset($action)) $action="";
if (isset($firstname)) $firstname = stripslashes($firstname);
if (isset($lastname)) $lastname = stripslashes($lastname);

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
if (empty($gm_username)||$_SESSION["cookie_login"]) {
	if (LOGIN_URL == "") header("Location: login.php?url=edituser.php");
	else header("Location: ".LOGIN_URL."?url=edituser.php");
	exit;
}

if (!isset($gm_user->default_tab)) $gm_user->default_tab = 9;
//-- prevent users with editing account disabled from being able to edit their account
if (!$gm_user->editaccount) {
	header("Location: index.php?command=user");
	exit;
}
print_header("Genmod ".$gm_lang["user_admin"]);
print "<div class=\"center\">\n";

//-- section to update a user by first deleting them
//-- and then adding them again
if ($action=="edituser2") {
	if ($username != $oldusername && !$gm_user->is_empty) {
		print "<span class=\"error\">".$gm_lang["duplicate_username"]."</span><br />";
	}
	else if ($pass1==$pass2) {
		$alphabet = GetAlphabet();
		$alphabet .= "_-. ";
		$i = 1;
		$pass = TRUE;
		while (strlen($username) > $i) {
			if (stristr($alphabet, $username{$i}) != TRUE){
				$pass = FALSE;
				break;
			}
			$i++;
		}
		if ($pass) {
			$sync_data_changed = false;
			$olduser =& User::GetInstance($oldusername);
			$newuser = CloneObj($olduser);

			if (empty($pass1)) $newuser->password = $olduser->password;
			else $newuser->password = crypt($pass1);
			UserController::DeleteUser($oldusername, "changed");
			$newuser->username = $username;
			$newuser->firstname = $firstname;
			$newuser->lastname = $lastname;
			$newuser->rootid[$GEDCOMID] = $rootid;
			if (isset($user_language)) $newuser->language = $user_language;
			if ($olduser->email != $user_email) $sync_data_changed = true;
			$newuser->email = $user_email;
			if (isset($user_theme)) $newuser->theme = $user_theme;
			if (isset($new_contact_method)) $newuser->contactmethod = $new_contact_method;
			if ((isset($new_visibleonline))&&($new_visibleonline=='yes')) $newuser->visibleonline = true;
			else $newuser->visibleonline = false;
			if (isset($new_default_tab)) $newuser->default_tab = $new_default_tab;
			UserController::AddUser($newuser, "changed");
			$gm_user = CloneObj($newuser);
			
			//-- update Gedcom record with new email address
			if ($gm_user->sync_gedcom == "Y" && $sync_data_changed && !empty($user->email)) {
				$oldged = $GEDCOMID;
				foreach($gm_user->gedcomid as $gedcid=>$gedid) {
					if (!empty($gedid) && isset($GEDCOMS[$gedcid])) {
						$GEDCOMID = $gedcid;
						$indirec = FindPersonRecord($gedid);
						$rec = GetChangeData(false, $gedid, true, "gedlines", "");
						if (isset($rec[$GEDCOMID][$gedid])) $indirec = $rec[$GEDCOMID][$gedid];
						if (!empty($indirec)) {
							$change_id = GetNewXref("CHANGE");
							if (preg_match("/(\d) (_?EMAIL .+)/", $indirec, $match)>0) {
								$level = $match[1];
								$oldrec = $match[0];
								$subrec = GetSubRecord($level, $oldrec, $indirec);
								$newrec = preg_replace("/(\d _?EMAIL)[^\r\n]*/", "$1 ".$user->email, $subrec);
								if ($subrec != $newrec) ReplaceGedrec($gedid, $subrec, $newrec, "EMAIL", $change_id, "edit_fact", $GEDCOMID);
							}
							else {
								ReplaceGedrec($gedid, "", "1 EMAIL ".$user->email."\r\n2 RESN private", "EMAIL", $change_id, "add_fact", $GEDCOMID);
							}
						}
					}
				}
				$GEDCOMID = $oldged;
			}
		}
		else {
			print "<span class=\"error\">".$gm_lang["invalid_username"]."</span><br />";
		}
	}
	else {
		print "<span class=\"error\">".$gm_lang["password_mismatch"]."</span><br />";
		$action="edituser";
	}
}
//-- print the form to edit a user
?>
<script language="JavaScript" type="text/javascript">
<!--
	function checkform(frm) {
		if (frm.username.value=="") {
			alert("<?php print $gm_lang["enter_username"]; ?>");
			frm.username.focus();
			return false;
		}
		if (frm.firstname.value=="") {
			alert("<?php print $gm_lang["enter_fullname"]; ?>");
			frm.firstname.focus();
			return false;
		}
		if (frm.lastname.value=="") {
			alert("<?php print $gm_lang["enter_fullname"]; ?>");
			frm.lastname.focus();
			return false;
		}
		if ((frm.user_email.value=="")||(frm.user_email.value.indexOf("@")==-1)) {
			alert("<?php print $gm_lang["enter_email"]; ?>");
			frm.user_email.focus();
			return false;
		}
		return true;
	}
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
		pastefield.focus();
	}
//-->
</script>
<form name="editform" method="post" action="" onsubmit="return checkform(this);">
<input type="hidden" name="action" value="edituser2" />
<input type="hidden" name="oldusername" value="<?php print $gm_username; ?>" />
<?php $tab=0; ?>
<table class="list_table <?php print $TEXT_DIRECTION; ?>">
	<tr><td class="topbottombar" colspan="2"><h3><?php print $gm_lang["editowndata"];?></h3></td></tr>
	<tr><td class="shade2 width20 wrap"><div class="helpicon"><?php print_help_link("edituser_username_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["username"];?></div></td><td class="shade1"><input type="text" name="username" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->username?>" /></td></tr>
	<tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_firstname_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["firstname"];?></div></td><td class="shade1"><input type="text" name="firstname" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->firstname?>" /></td></tr>
	<tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_lastname_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["lastname"];?></div></td><td class="shade1"><input type="text" name="lastname" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->lastname?>" /></td></tr>
	<tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_gedcomid_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["gedcomid"];?></div></td><td class="shade1">
		<?php
			if (!empty($gm_user->gedcomid[$GEDCOMID])) {
				print "<ul>";
				print_list_person($gm_user->gedcomid[$GEDCOMID], array(GetPersonName($gm_user->gedcomid[$GEDCOMID]), $GEDCOMID));
				print "</ul>";
			}
			else print "&nbsp;";
		?>
	</td></tr>
	<tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_rootid_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["rootid"];?></div></td><td class="shade1"><input type="text" name="rootid" id="rootid" tabindex="<?php $tab++; print $tab; ?>" value="<?php if (isset($gm_user->rootid[$GEDCOMID])) print $gm_user->rootid[$GEDCOMID]; ?>" />
	<?php LinkFunctions::PrintFindIndiLink("rootid",$GEDCOMID); ?>
	</td></tr>
	<tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_password_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["password"];?></div></td><td class="shade1"><input type="password" name="pass1" tabindex="<?php $tab++; print $tab; ?>" /><br /><?php print $gm_lang["leave_blank"];?></td></tr>
	<tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_conf_password_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["confirm"];?></div></td><td class="shade1"><input type="password" name="pass2" tabindex="<?php $tab++; print $tab; ?>" /></td></tr>
	<tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_change_lang_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["change_lang"];?></div></td><td class="shade1" valign="top"><?php
	if ($ENABLE_MULTI_LANGUAGE) {
		$tab++;
		print "<select name=\"user_language\" tabindex=\"".$tab."\" style=\"{ font-size: 9pt; }\">";
		foreach ($gm_language as $key => $value) {
			if ($language_settings[$key]["gm_lang_use"]) {
				print "\n\t\t\t<option value=\"$key\"";
				if ($key == $gm_user->language) print " selected=\"selected\"";
				print ">" . $gm_lang[$key] . "</option>";
			}
		}
		print "</select>\n\t\t";
	}
	else print "&nbsp;";
    ?></td></tr>
    <tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_email_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["emailadress"];?></div></td><td class="shade1" valign="top"><input type="text" name="user_email" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->email; ?>" size="50" onchange="sndReq('errem', 'checkemail', 'email', this.value);" />&nbsp;&nbsp;<span id="errem"></span></td></tr>
    <?php if ($ALLOW_USER_THEMES) { ?>
    <tr><td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_user_theme_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["user_theme"];?></div></td><td class="shade1" valign="top">
    	<select name="user_theme" tabindex="<?php $tab++; print $tab; ?>">
    	<option value=""><?php print $gm_lang["site_default"]; ?></option>
				<?php
					$themes = GetThemeNames();
					foreach($themes as $indexval => $themedir) {
						print "<option value=\"".$themedir["dir"]."\"";
						if ($themedir["dir"] == $gm_user->theme) print " selected=\"selected\"";
						print ">".$themedir["name"]."</option>\n";
					}
				?>
			</select>
	</td></tr>
	<?php } ?>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_user_contact_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["user_contact_method"];?></td>
		<td class="shade1"><select name="new_contact_method" tabindex="<?php $tab++; print $tab; ?>">
		<?php if ($GM_STORE_MESSAGES) { ?>
				<option value="messaging" <?php if ($gm_user->contactmethod=='messaging') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging"];?></option>
				<option value="messaging2" <?php if ($gm_user->contactmethod=='messaging2') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging2"];?></option>
		<?php } else { ?>
				<option value="messaging3" <?php if ($gm_user->contactmethod=='messaging3') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging3"];?></option>
		<?php } ?>
				<option value="mailto" <?php if ($gm_user->contactmethod=='mailto') print "selected=\"selected\""; ?>><?php print $gm_lang["mailto"];?></option>
				<option value="none" <?php if ($gm_user->contactmethod=='none') print "selected=\"selected\""; ?>><?php print $gm_lang["no_messaging"];?></option>
			</select>
		</div></td>
	</tr>
	<tr>
      <td class="shade2 wrap"><div class="helpicon"><?php print_help_link("useradmin_visibleonline_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["visibleonline"];?></div></td>
      <td class="shade1"><input type="checkbox" name="new_visibleonline" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($gm_user->visibleonline) print "checked=\"checked\""; ?> /></td>
    </tr>
    <tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("edituser_user_default_tab_help", "qm"); print "</div><div class=\"description\">"; print $gm_lang["user_default_tab"];?></div></td>
		<td class="shade1"><select name="new_default_tab" tabindex="<?php $tab++; print $tab; ?>">
				<option value="9" <?php if ($gm_user->default_tab==9) print "selected=\"selected\""; ?>><?php print $gm_lang["site_default"]; ?></option>
				<option value="0" <?php if ($gm_user->default_tab==0) print "selected=\"selected\""; ?>><?php print $gm_lang["personal_facts"];?></option>
				<option value="1" <?php if ($gm_user->default_tab==1) print "selected=\"selected\""; ?>><?php print $gm_lang["notes"];?></option>
				<option value="2" <?php if ($gm_user->default_tab==2) print "selected=\"selected\""; ?>><?php print $gm_lang["ssourcess"];?></option>
				<option value="3" <?php if ($gm_user->default_tab==3) print "selected=\"selected\""; ?>><?php print $gm_lang["media"];?></option>
				<option value="4" <?php if ($gm_user->default_tab==4) print "selected=\"selected\""; ?>><?php print $gm_lang["relatives"];?></option>
				<option value="6" <?php if ($gm_user->default_tab==6) print "selected=\"selected\""; ?>><?php print $gm_lang["all"];?></option>
			</select>
		</td>
	</tr>
	<tr><td class="center" colspan="2"><input type="submit" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_lang["update_myaccount"]; ?>" /></td></tr>
</table>
</form><br />
</div>
<?php
print_footer();
?>
