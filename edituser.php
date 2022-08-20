<?php
/**
 * Administrative User Interface.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 19 August 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: edituser.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if (!isset($action)) $action="";
if (isset($firstname)) $firstname = stripslashes($firstname);
if (isset($lastname)) $lastname = stripslashes($lastname);
if (!isset($pass1)) $pass1 = "";
if (!isset($pass2)) $pass2 = "";
$successmessage = "";

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
PrintHeader("Genmod ".GM_LANG_user_admin);
print "<div id=\"EditUserContent\">\n";

//-- section to update a user by first deleting them
//-- and then adding them again
if ($action=="edituser2") {
	if ($username != $oldusername && !$gm_user->is_empty) {
		print "<span class=\"Error\">".GM_LANG_duplicate_username."</span><br />";
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
			else $newuser->password = password_hash($pass1, PASSWORD_DEFAULT);
			UserController::DeleteUser($oldusername, "changed");
			$newuser->username = $username;
			$newuser->firstname = $firstname;
			$newuser->lastname = $lastname;
			$newuser->rootid[GedcomConfig::$GEDCOMID] = $rootid;
			if (isset($user_language)) $newuser->language = $user_language;
			if ($olduser->email != $user_email) $sync_data_changed = true;
			$newuser->email = $user_email;
			if (isset($user_theme)) $newuser->theme = $user_theme;
			if (isset($new_contact_method)) $newuser->contactmethod = $new_contact_method;
			if ((isset($new_visibleonline))&&($new_visibleonline=='yes')) $newuser->visibleonline = true;
			else $newuser->visibleonline = false;
			if (isset($new_default_tab)) $newuser->default_tab = $new_default_tab;
			$successmessage = (UserController::AddUser($newuser, "changed") ? GM_LANG_update_successful : GM_LANG_update_fail);
			$gm_user = CloneObj($newuser);

			//-- update Gedcom record with new email address
			if ($sync_data_changed) AdminFunctions::UpdateUserIndiEmail($newuser);
		}
		else {
			print "<span class=\"Error\">".GM_LANG_invalid_username."</span><br />";
		}
	}
	else {
		print "<span class=\"Error\">".GM_LANG_password_mismatch."</span><br />";
		$action="edituser";
	}
}
//-- print the form to edit a user
?>
<script language="JavaScript" type="text/javascript">
<!--
	function checkform(frm) {
		if (frm.username.value=="") {
			alert("<?php print GM_LANG_enter_username; ?>");
			frm.username.focus();
			return false;
		}
		if (frm.firstname.value=="") {
			alert("<?php print GM_LANG_enter_fullname; ?>");
			frm.firstname.focus();
			return false;
		}
		if (frm.lastname.value=="") {
			alert("<?php print GM_LANG_enter_fullname; ?>");
			frm.lastname.focus();
			return false;
		}
		if ((frm.user_email.value=="")||(frm.user_email.value.indexOf("@")==-1)) {
			alert("<?php print GM_LANG_enter_email; ?>");
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
<input type="hidden" name="oldusername" value="<?php print $gm_user->username; ?>" />
<?php $tab=0; ?>
<table class="ListTable EditUserTable">
	<tr>
		<td class="NavBlockHeader" colspan="2"><?php print GM_LANG_editowndata.(empty($successmessage) ? "" : "<br /><span class=\"Error\">".$successmessage."</span>");?></td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_username_help", "qm"); print "</div>".GM_LANG_username;?></td>
		<td class="NavBlockField"><input type="text" name="username" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->username?>" /></td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_firstname_help", "qm"); print "</div>".GM_LANG_firstname;?></td>
		<td class="NavBlockField"><input type="text" name="firstname" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->firstname?>" /></td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_lastname_help", "qm"); print "</div>".GM_LANG_lastname;?></td>
		<td class="NavBlockField"><input type="text" name="lastname" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->lastname?>" /></td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_gedcomid_help", "qm"); print "</div>".GM_LANG_gedcomid;?></td>
		<td class="NavBlockField">
		<?php
			if (!empty($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) {
				$person =& Person::GetInstance($gm_user->gedcomid[GedcomConfig::$GEDCOMID], "", GedcomConfig::$GEDCOMID);
				$person->PrintListPerson(false);
			}
			else print "&nbsp;";
		?>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_rootid_help", "qm"); print "</div>".GM_LANG_rootid;?></td>
		<td class="NavBlockField"><input type="text" name="rootid" id="rootid" tabindex="<?php $tab++; print $tab; ?>" value="<?php if (isset($gm_user->rootid[GedcomConfig::$GEDCOMID])) print $gm_user->rootid[GedcomConfig::$GEDCOMID]; ?>" />
		<?php LinkFunctions::PrintFindIndiLink("rootid",GedcomConfig::$GEDCOMID); ?>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_password_help", "qm"); print "</div>".GM_LANG_password;?></td>
		<td class="NavBlockField"><input type="password" name="pass1" tabindex="<?php $tab++; print $tab; ?>" /><br /><?php print GM_LANG_leave_blank;?></td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_conf_password_help", "qm"); print "</div>".GM_LANG_confirm;?></td>
		<td class="NavBlockField"><input type="password" name="pass2" tabindex="<?php $tab++; print $tab; ?>" /></td>
	</tr>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_change_lang_help", "qm"); print "</div>".GM_LANG_change_lang;?></td>
		<td class="NavBlockField" valign="top"><?php
		if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) {
			$tab++;
			print "<select name=\"user_language\" tabindex=\"".$tab."\">";
			foreach ($gm_language as $key => $value) {
				if ($language_settings[$key]["gm_lang_use"]) {
					print "\n\t\t\t<option value=\"$key\"";
					if ($key == $gm_user->language) print " selected=\"selected\"";
					print ">" . constant("GM_LANG_lang_name_".$key) . "</option>";
				}
			}
			print "</select>\n\t\t";
		}
		else print "&nbsp;";?></td>
	</tr>
    <tr>
    	<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_email_help", "qm"); print "</div>".GM_LANG_emailadress;?></td>
    	<td class="NavBlockField" valign="top"><input type="text" name="user_email" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_user->email; ?>" size="50" onchange="sndReq('errem', 'checkemail', true, 'email', this.value);" />&nbsp;&nbsp;<span id="errem"></span></td>
    </tr>
    <?php if (SystemConfig::$ALLOW_USER_THEMES) { ?>
    <tr>
    	<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_user_theme_help", "qm"); print "</div>".GM_LANG_user_theme;?></td>
    	<td class="NavBlockField" valign="top">
    	<select name="user_theme" tabindex="<?php $tab++; print $tab; ?>">
    	<option value=""><?php print GM_LANG_site_default; ?></option>
			<?php
				$themes = GetThemeNames();
				foreach($themes as $indexval => $themedir) {
					print "<option value=\"".$themedir["dir"]."\"";
					if ($themedir["dir"] == $gm_user->theme) print " selected=\"selected\"";
					print ">".$themedir["name"]."</option>\n";
				}
			?>
		</select></td>
	</tr>
	<?php } ?>
	<tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_user_contact_help", "qm"); print "</div>".GM_LANG_user_contact_method;?></td>
		<td class="NavBlockField"><select name="new_contact_method" tabindex="<?php $tab++; print $tab; ?>">
		<?php if (SystemConfig::$GM_STORE_MESSAGES) { ?>
				<option value="messaging" <?php if ($gm_user->contactmethod=='messaging') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging;?></option>
				<option value="messaging2" <?php if ($gm_user->contactmethod=='messaging2') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging2;?></option>
		<?php } else { ?>
				<option value="messaging3" <?php if ($gm_user->contactmethod=='messaging3') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging3;?></option>
		<?php } ?>
				<option value="mailto" <?php if ($gm_user->contactmethod=='mailto') print "selected=\"selected\""; ?>><?php print GM_LANG_mailto;?></option>
				<option value="none" <?php if ($gm_user->contactmethod=='none') print "selected=\"selected\""; ?>><?php print GM_LANG_no_messaging;?></option>
			</select>
		</td>
	</tr>
	<tr>
      <td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("useradmin_visibleonline_help", "qm"); print "</div>".GM_LANG_visibleonline;?></td>
      <td class="NavBlockField"><input type="checkbox" name="new_visibleonline" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($gm_user->visibleonline) print "checked=\"checked\""; ?> /></td>
    </tr>
    <tr>
		<td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("edituser_user_default_tab_help", "qm"); print "</div>".GM_LANG_user_default_tab;?></td>
		<td class="NavBlockField"><select name="new_default_tab" tabindex="<?php $tab++; print $tab; ?>">
				<option value="9" <?php if ($gm_user->default_tab==9) print "selected=\"selected\""; ?>><?php print GM_LANG_site_default; ?></option>
				<option value="0" <?php if ($gm_user->default_tab==0) print "selected=\"selected\""; ?>><?php print GM_LANG_personal_facts;?></option>
				<option value="1" <?php if ($gm_user->default_tab==1) print "selected=\"selected\""; ?>><?php print GM_LANG_notes;?></option>
				<option value="2" <?php if ($gm_user->default_tab==2) print "selected=\"selected\""; ?>><?php print GM_LANG_ssourcess;?></option>
				<option value="3" <?php if ($gm_user->default_tab==3) print "selected=\"selected\""; ?>><?php print GM_LANG_media;?></option>
				<option value="4" <?php if ($gm_user->default_tab==4) print "selected=\"selected\""; ?>><?php print GM_LANG_relatives;?></option>
				<option value="6" <?php if ($gm_user->default_tab==6) print "selected=\"selected\""; ?>><?php print GM_LANG_all;?></option>
			</select>
		</td>
	</tr>
	<tr><td class="NavBlockFooter" colspan="2"><input type="submit" tabindex="<?php $tab++; print $tab; ?>" value="<?php print GM_LANG_update_myaccount; ?>" /></td></tr>
</table>
</form>
</div>
<?php
PrintFooter();
?>
