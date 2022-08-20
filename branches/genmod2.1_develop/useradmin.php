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
 * This Page Is Valid XHTML 1.0 Transitional! > 30 August 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: useradmin.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * load configuration and context
 */
require "config.php";

global $TEXT_DIRECTION;

// Remove slashes
if (isset($ufirstname)) $ufirstname = stripslashes($ufirstname);
if (isset($ulastname)) $ulastname = stripslashes($ulastname);

if (!isset($action)) $action="";
if (!isset($filter)) $filter="";
if (!isset($namefilter)) $namefilter="";
if (!isset($sort)) $sort="";
if (!isset($gedid)) $gedid="";
if (!isset($usrlang)) $usrlang="";
if (isset($refreshlist)) $action="listusers";
$message = "";
//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
if (!$gm_user->userIsAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=useradmin.php?".GetQueryString(true));
	else header("Location: ".LOGIN_URL."?url=useradmin.php?".GetQueryString(true));
	exit;
}
PrintHeader("Genmod ".GM_LANG_user_admin);

// Javascript for edit form
?>
<script language="JavaScript" type="text/javascript">
<!--
	function checkform(frm) {
		if (frm.uusername.value=="") {
			alert("<?php print GM_LANG_enter_username; ?>");
			frm.uusername.focus();
			return false;
		}
		if (frm.ufirstname.value=="") {
			alert("<?php print GM_LANG_enter_fullname; ?>");
			frm.ufirstname.focus();
			return false;
		}
		if (frm.ulastname.value=="") {
			alert("<?php print GM_LANG_enter_fullname; ?>");
			frm.ulastname.focus();
			return false;
		}
	    if ((frm.pass1.value!="")&&(frm.pass1.value.length < 6)) {
	      alert("<?php print GM_LANG_passwordlength; ?>");
	      frm.pass1.value = "";
	      frm.pass2.value = "";
	      frm.pass1.focus();
	      return false;
	    }
		if ((frm.emailadress.value!="")&&(frm.emailadress.value.indexOf("@")==-1)) {
			alert("<?php print GM_LANG_enter_email; ?>");
			frm.emailadress.focus();
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
<?php
//-- section to create a new user
if ($action=="createuser") {
	$alphabet = GetAlphabet();
	$alphabet .= "_-. ";
	$i = 1;
	$pass = TRUE;
	while (strlen($uusername) > $i) {
		if (stristr($alphabet, $uusername{$i}) != TRUE){
			$pass = FALSE;
			break;
		}
		$i++;
	}
	if ($pass == TRUE){
		$uuser =& User::GetInstance($uusername);
		if (!$uuser->is_empty) {
			print "<span class=\"Error\">".GM_LANG_duplicate_username."</span><br />";
		}
		else if ($pass1==$pass2) {
			$user = new User();
			$user->username=$uusername;
			$user->firstname=$ufirstname;
			$user->lastname=$ulastname;
			$user->email=$emailadress;
			if (!isset($verified)) $verified = "";
			$user->verified = $verified;
			if (!isset($verified_by_admin)) $verified_by_admin = "";
			$user->verified_by_admin = $verified_by_admin;
			if (!empty($user_language)) $user->language = $user_language;
			else $user->language = $LANGUAGE;
			$user->reg_timestamp = $reg_timestamp;
			$user->reg_hashcode = (isset($reg_hashcode) ? $reg_hashcode : "");
			$user->gedcomid=array();
			$user->rootid=array();
			$user->canedit=array();
			$user->password=password_hash($pass1, PASSWORD_DEFAULT);
			if ((isset($canadmin))&&($canadmin=="yes")) $user->canadmin=true;
			else $user->canadmin=false;
			if ((isset($visibleonline))&&($visibleonline=="yes")) $user->visibleonline=true;
			else $user->visibleonline=false;
			if ((isset($editaccount))&&($editaccount=="yes")) $user->editaccount=true;
			else $user->editaccount=false;
			if (!isset($new_user_theme)) $new_user_theme="";
			$user->theme = $new_user_theme;
			$user->loggedin = "N";
			$user->sessiontime = 0;
			if (!isset($new_contact_method)) $new_contact_method="messaging2";
			$user->contactmethod = $new_contact_method;
			if (isset($new_default_tab)) $user->default_tab = $new_default_tab;
			if (isset($new_comment)) $user->comment = $new_comment;
			if (isset($new_comment_exp)) $user->comment_exp = $new_comment_exp;
			if (isset($new_sync_gedcom)) $user->sync_gedcom = $new_sync_gedcom;
			else $user->sync_gedcom = "N";
			$user->auto_accept = false;
			if (isset($new_auto_accept))  $user->auto_accept = true;
			foreach($GEDCOMS as $gedcomid=>$gedarray) {
				$varname = "gedcomid_$gedcomid";
				if (isset($$varname)) $user->gedcomid[$gedcomid]=$$varname;
				$varname = "rootid_$gedcomid";
				if (isset($$varname)) $user->rootid[$gedcomid]=$$varname;
				$varname = "canedit_$gedcomid";
				if (isset($$varname)) $user->canedit[$gedcomid]=$$varname;
				else $user->canedit[$gedcomid]="none";
				$varname = "privgroup_$gedcomid";
				if (isset($$varname)) $user->privgroup[$gedcomid]=$$varname;
				else $user->privgroup[$gedcomid]="none";
				$varname = "new_gedadmin_$gedcomid";
				if (isset($$varname) && $$varname == "Y") $user->gedcomadmin[$gedcomid] = true;
				else $user->gedcomadmin[$gedcomid] = false;
				$varname = "new_relationship_privacy_$gedcomid";
				if (isset($$varname)) $user->relationship_privacy[$gedcomid] = $$varname;
				else $user->relationship_privacy[$gedcomid] = "";
				$varname = "new_max_relation_path_$gedcomid";
				if (isset($$varname)) $user->max_relation_path[$gedcomid] = $$varname;
			}
			
			$au = UserController::AddUser($user, "added");
			
			if ($au) {
				$message .= GM_LANG_user_created;
				//-- update Gedcom record with new email address
				AdminFunctions::UpdateUserIndiEmail($user);
			}
			else {
				$message .= "<span class=\"Error\">".GM_LANG_user_create_error."<br /></span>";
			}
		}
		else {
			$message .= "<span class=\"Error\">".GM_LANG_password_mismatch."</span><br />";
		}
	}
	else {
		$message .= "<span class=\"Error\">".GM_LANG_invalid_username."</span><br />";
	}
	$action = "";
}
//-- section to delete a user
if ($action=="deleteuser") {
	if (UserController::DeleteUser($username, "deleted")) {
		$message .= GM_LANG_delete_user_ok;
		NewsController::DeleteUserNews($username);
	}
	else $message .= "<span class=\"Error\">".GM_LANG_delete_user_nok."</span>";
}
//-- section to update a user by first deleting them
//-- and then adding them again
if ($action=="edituser2") {
	$alphabet = GetAlphabet();
	$alphabet .= "_-. ";
	$i = 1;
	$pass = TRUE;
	while (strlen($uusername) > $i) {
		if (stristr($alphabet, $uusername{$i}) != TRUE){
			$pass = FALSE;
			break;
		}
		$i++;
	}
	if ($pass == TRUE){
		$u =& User::GetInstance($uusername);
		if ($uusername!=$oldusername && !$u->is_empty) {
			print "<span class=\"Error\">".GM_LANG_duplicate_username."</span><br />";
			$action="edituser";
			$username = $oldusername;
		}
		else if ((!isset($pass1) && !isset($pass2)) || $pass1==$pass2) {
			$sync_data_changed = false;
			$olduser =& User::GetInstance($oldusername);
			$newuser = CloneObj($olduser);

			if (empty($pass1)) $newuser->password=$olduser->password;
			else $newuser->password=password_hash($pass1, PASSWORD_DEFAULT);
			UserController::DeleteUser($oldusername, "changed");
			$newuser->username=$uusername;
			$newuser->firstname=$ufirstname;
			$newuser->lastname=$ulastname;

			if (!empty($user_language)) $newuser->language = $user_language;

			if ($olduser->email!=$emailadress) $sync_data_changed = true;
			$newuser->email=$emailadress;
			if (!isset($verified)) $verified = "";
			$newuser->verified = $verified;
			if (!isset($verified_by_admin)) $verified_by_admin = "";
			$newuser->verified_by_admin = $verified_by_admin;

			if (!empty($new_contact_method)) $newuser->contactmethod = $new_contact_method;
			if (isset($new_default_tab)) $newuser->default_tab = $new_default_tab;
			if (isset($new_comment)) $newuser->comment = $new_comment;
			if (isset($new_comment_exp)) $newuser->comment_exp = $new_comment_exp;
			if (isset($new_sync_gedcom)) $newuser->sync_gedcom = $new_sync_gedcom;
			else $newuser->sync_gedcom = "N";
			$newuser->auto_accept = false;
			if (isset($new_auto_accept)) $newuser->auto_accept = true;

			if (!isset($user_theme)) $user_theme="";
			$newuser->theme = $user_theme;
			foreach($GEDCOMS as $gedcomid=>$gedarray) {
				$varname = "gedcomid_$gedcomid";
				if (isset($$varname)) $newuser->gedcomid[$gedcomid]=$$varname;
				$varname = "rootid_$gedcomid";
				if (isset($$varname)) $newuser->rootid[$gedcomid]=$$varname;
				$varname = "canedit_$gedcomid";
				if (isset($$varname)) $newuser->canedit[$gedcomid]=$$varname;
				else $newuser->canedit[$gedcomid]="none";
				$varname = "privgroup_$gedcomid";
				if (isset($$varname)) $newuser->privgroup[$gedcomid]=$$varname;
				else $newuser->privgroup[$gedcomid]="access";
				$varname = "new_gedadmin_$gedcomid";
				if (isset($$varname) && $$varname == "Y") $newuser->gedcomadmin[$gedcomid] = true;
				else $newuser->gedcomadmin[$gedcomid] = false;
				$varname = "new_relationship_privacy_$gedcomid";
				if (isset($$varname)) $newuser->relationship_privacy[$gedcomid] = $$varname;
				else $newuser->relationship_privacy[$gedcomid] = "";
				$varname = "new_max_relation_path_$gedcomid";
				if (isset($$varname)) $newuser->max_relation_path[$gedcomid] = $$varname;
				$varname = "new_hide_live_people_$gedcomid";
				if (isset($$varname)) $newuser->hide_live_people[$gedcomid] = $$varname;
				$varname = "new_check_marriage_relations_$gedcomid";
				if (isset($$varname)) $newuser->check_marriage_relations[$gedcomid] = $$varname;
				$varname = "new_show_living_names_$gedcomid";
				if (isset($$varname)) $newuser->show_living_names[$gedcomid] = $$varname;
			}
			if ($olduser->username != $gm_user->username) {
				if ((isset($canadmin))&&($canadmin=="yes")) $newuser->canadmin=true;
				else $newuser->canadmin=false;
			}
			else $newuser->canadmin=$olduser->canadmin;
			if ((isset($visibleonline))&&($visibleonline=="yes")) $newuser->visibleonline=true;
			else $newuser->visibleonline=false;
			if ((isset($editaccount))&&($editaccount=="yes")) $newuser->editaccount=true;
			else $newuser->editaccount=false;
			UserController::AddUser($newuser, "changed");
			
			//-- update Gedcom record with new email address
			if ($sync_data_changed) AdminFunctions::UpdateUserIndiEmail($newuser);
			
			//-- if the user was just verified by the admin, then send the user a message
			if (($olduser->verified_by_admin!=$newuser->verified_by_admin)&&(!empty($newuser->verified_by_admin))) {
				// Switch to the users language
				$oldlanguage = $LANGUAGE;
				$LANGUAGE = $newuser->language;
				if (isset($gm_language[$LANGUAGE])) $templang = LanguageFunctions::LoadEnglish(true, false, true);
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];

				$message = new Message();
				$message->to = $newuser->username;
				$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
				$message->from_email = "Genmod-noreply@".$host;
				$message->from_name = $newuser->firstname.' '.$newuser->lastname;
				$message->from = "genmod-noreply@".$host;
				if (substr(SERVER_URL, -1) == "/"){
					$message->subject = str_replace("#SERVER_NAME#", substr(SERVER_URL,0, (strlen(SERVER_URL)-1)), $templang["admin_approved"]);
					$message->body = str_replace("#SERVER_NAME#", SERVER_URL, $templang["admin_approved"])." ".$templang["you_may_login"]."\r\n\r\n"."<a href=\"".substr(SERVER_URL,0, (strlen(SERVER_URL)-1))."/index.php?command=user\">".substr(SERVER_URL,0, (strlen(SERVER_URL)-1))."/index.php?command=user</a>\r\n";
				}
				else {
					$message->subject = str_replace("#SERVER_NAME#", SERVER_URL, $templang["admin_approved"]);
					$message->body = str_replace("#SERVER_NAME#", SERVER_URL, $templang["admin_approved"])." ".$templang["you_may_login"]."\r\n\r\n"."<a href=\"".SERVER_URL."/index.php?command=user\">".SERVER_URL."/index.php?command=user</a>\r\n";
				}
				$message->created = "";
				$message->method = "messaging2";
				$message->AddMessage(true);

				// Switch back to the page language
				$LANGUAGE = $oldlanguage;
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
			}
		}
		else {
			print "<span class=\"Error\">".GM_LANG_password_mismatch."</span><br />";
			$action="edituser";
			$username = $oldusername;
		}
		$message = "";
	}
	else {
		print "<span class=\"Error\">".GM_LANG_invalid_username."</span><br />";
	}
}
//-- print the form to edit a user
// NOTE: WORKING
InitCalendarPopUp();
if ($action=="edituser" || $action == "createform") { ?>
	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
		<?php AdminFunctions::AdminLink("useradmin.php", GM_LANG_user_admin); ?>
		<?php AdminFunctions::AdminLink("useradmin.php?action=listusers&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."&amp;namefilter=".$namefilter, GM_LANG_current_users); ?>
	</div>
	<div id="AdminColumnMiddle">
		<?php
		switch ($action) {
			case "edituser": 
				$user =& User::GetInstance($username);
				if (!empty($user->username)) {
					if (!isset($user->contactmethod)) $user->contactmethod = "none"; ?>
					<form name="editform" method="post" action="useradmin.php" onsubmit="return checkform(this);">
					<input type="hidden" name="action" value="edituser2" />
					<input type="hidden" name="filter" value="<?php print $filter; ?>" />
					<input type="hidden" name="namefilter" value="<?php print $namefilter; ?>" />
					<input type="hidden" name="sort" value="<?php print $sort; ?>" />
					<input type="hidden" name="gedid" value="<?php print $gedid; ?>" />
					<input type="hidden" name="usrlang" value="<?php print $usrlang; ?>" />
					<input type="hidden" name="oldusername" value="<?php print $username; ?>" />
				<?php } 
				break;
			case "createform": ?>
				<form name="newform" method="post" action="<?php print SCRIPT_NAME;?>" onsubmit="return checkform(this);">
				<input type="hidden" name="action" value="createuser" />
				<input type="hidden" name="reg_timestamp" value="<?php print date("U");?>" />
				<input type="hidden" name="reg_hashcode" value="" />
				<?php break;
		}
		$tab=0; ?>
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
				<?php switch ($action) {
					case "edituser":
						print GM_LANG_update_user;
						break;
					case "createform":
						print GM_LANG_add_user;
						break;
				} ?>
				</div>
			</td>
		</tr>
		<?php
		if ((isset($user) && !empty($user->username)) || $action == "createform") { ?>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_username_help", "qm","username");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_username; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="text" name="uusername" tabindex="<?php $tab++; print $tab; ?>" <?php if ($action == "edituser") { ?> value="<?php print $user->username.'"'; }?> />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_firstname_help", "qm", "firstname");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_firstname; ?>
				</div>
			</td>
				<td class="NavBlockField">
				<input type="text" name="ufirstname" tabindex="<?php $tab++; print $tab; ?>" <?php if ($action == "edituser") { ?> value="<?php print PrintReady($user->firstname).'"'; } ?> size="50" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_lastname_help", "qm","lastname");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_lastname; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="text" name="ulastname" tabindex="<?php $tab++; print $tab; ?>" <?php if ($action == "edituser") { ?> value="<?php print PrintReady($user->lastname).'"'; } ?> size="50" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_password_help", "qm","password");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_password; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="password" name="pass1" tabindex="<?php $tab++; print $tab; ?>" /><br /><?php if ($action == "edituser") { print GM_LANG_leave_blank; } ?>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_conf_password_help", "qm","confirm");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_confirm; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="password" name="pass2" tabindex="<?php $tab++; print $tab; ?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_sync_gedcom_help", "qm", "sync_gedcom");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_sync_gedcom; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="new_sync_gedcom" tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($action == "edituser") { if ($user->sync_gedcom=="Y") print "checked=\"checked\""; }; ?> />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_can_admin_help", "qm", "can_admin");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_can_admin; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="canadmin" tabindex="<?php $tab++; print $tab; ?>" <?php if ($action == "edituser") {?> value="yes" <?php if ($user->canadmin) print "checked=\"checked\""; if ($user->username==$gm_user->username) print " disabled=\"disabled\""; }?> />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_auto_accept_help", "qm", "user_auto_accept");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_user_auto_accept; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="new_auto_accept" tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($action == "edituser") if ($user->auto_accept) print "checked=\"checked\"";?> />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_email_help", "qm", "emailadress");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_emailadress; ?>
				</div>
			</td>
			<td class="NavBlockField">
					<input type="text" name="emailadress" tabindex="<?php $tab++; print $tab; ?>" dir="ltr" <?php if ($action == "edituser") {?> value="<?php print $user->email.'"'; } ?> size="50" onchange="sndReq('errem', 'checkemail', 'email', true, this.value);" />&nbsp;&nbsp;<span id="errem"></span>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_verified_help", "qm", "verified");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_verified; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="verified" tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($action == "edituser") { if ($user->verified) print "checked=\"checked\""; } else print "checked=\"checked\"";?> />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_verbyadmin_help", "qm", "verified_by_admin");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_verified_by_admin; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="verified_by_admin" tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($action == "edituser") { if ($user->verified_by_admin) print "checked=\"checked\""; } else print "checked=\"checked\"";?> />
			</td>
		</tr>
			<?php if ($action == "createform") $user =& User::GetInstance($gm_user->username);
			if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) { ?>
				<tr>
					<td class="NavBlockLabel AdminNavBlockOption">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("edituser_change_lang_help", "qm", "change_lang");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_change_lang; ?>
						</div>
					</td>
					<td class="NavBlockField">
						<?php
						
						$tab++;
						print "<select name=\"user_language\" tabindex=\"".$tab."\" dir=\"ltr\">";
						foreach ($gm_language as $key => $value) {
							if ($language_settings[$key]["gm_lang_use"]) {
								print "\n\t\t\t<option value=\"$key\"";
								if ($key == $user->language) print " selected=\"selected\"";
								print ">" . constant("GM_LANG_lang_name_".$key) . "</option>";
							}
						}
						print "</select>\n\t\t";
						?>
					</td>
				</tr>
			<?php }
			if (SystemConfig::$ALLOW_USER_THEMES) { ?>
				<tr>
					<td class="NavBlockLabel AdminNavBlockOption">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_user_theme_help", "qm", "user_theme");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_user_theme; ?>
						</div>
					</td>
					<td class="NavBlockField">
						<select name="user_theme" tabindex="<?php $tab++; print $tab; ?>" dir="ltr">
							<option value=""><?php print GM_LANG_site_default; ?></option>
							<?php
							$themes = GetThemeNames();
							foreach($themes as $indexval => $themedir) {
								print "<option value=\"".$themedir["dir"]."\"";
								if ($action == "edituser") if ($themedir["dir"] == $user->theme) print " selected=\"selected\"";
								print ">".$themedir["name"]."</option>\n";
							}
							?>
						</select>
					</td>
				</tr>
			<?php } ?>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_user_contact_help", "qm", "user_contact_method");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_user_contact_method; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<select name="new_contact_method" tabindex="<?php $tab++; print $tab; ?>">
					<?php if (SystemConfig::$GM_STORE_MESSAGES) { ?>
						<option value="messaging" <?php if ($action == "edituser") if ($user->contactmethod=='messaging') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging;?></option>
						<option value="messaging2" <?php if ($action == "edituser") { if ($user->contactmethod=='messaging2') print "selected=\"selected\""; } else print "selected=\"selected\"";?>><?php print GM_LANG_messaging2;?></option>
					<?php } 
					else { ?>
						<option value="messaging3" <?php if ($action == "edituser") { if ($user->contactmethod=='messaging3') print "selected=\"selected\""; } else print "selected=\"selected\"";?>><?php print GM_LANG_messaging3;?></option>
					<?php } ?>
					<option value="mailto" <?php if ($action == "edituser") if ($user->contactmethod=='mailto') print "selected=\"selected\""; ?>><?php print GM_LANG_mailto;?></option>
					<option value="none" <?php if ($action == "edituser") if ($user->contactmethod=='none') print "selected=\"selected\""; ?>><?php print GM_LANG_no_messaging;?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_visibleonline_help", "qm", "visibleonline");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_visibleonline; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="visibleonline" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($action == "edituser") { if ($user->visibleonline) print "checked=\"checked\""; } else print "checked=\"checked\"";?> />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_editaccount_help", "qm", "editaccount");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_editaccount; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="editaccount" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($action == "edituser") { if ($user->editaccount) print "checked=\"checked\""; } else print "checked=\"checked\"";?> />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("useradmin_user_default_tab_help", "qm", "user_default_tab");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_user_default_tab; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<select name="new_default_tab" tabindex="<?php $tab++; print $tab; ?>">
					<option value="9" <?php if ($action == "edituser") if (@$user->default_tab==9) print "selected=\"selected\""; ?>><?php print GM_LANG_site_default; ?></option>
					<option value="0" <?php if ($action == "edituser") if (@$user->default_tab==0) print "selected=\"selected\""; ?>><?php print GM_LANG_personal_facts;?></option>
					<option value="1" <?php if ($action == "edituser") if (@$user->default_tab==1) print "selected=\"selected\""; ?>><?php print GM_LANG_notes;?></option>
					<option value="2" <?php if ($action == "edituser") if (@$user->default_tab==2) print "selected=\"selected\""; ?>><?php print GM_LANG_ssourcess;?></option>
					<option value="3" <?php if ($action == "edituser") if (@$user->default_tab==3) print "selected=\"selected\""; ?>><?php print GM_LANG_media;?></option>
					<option value="4" <?php if ($action == "edituser") if (@$user->default_tab==4) print "selected=\"selected\""; ?>><?php print GM_LANG_relatives;?></option>
					<option value="6" <?php if ($action == "edituser") if (@$user->default_tab==6) print "selected=\"selected\""; ?>><?php print GM_LANG_all;?></option>
				</select>
			</td>
		</tr>
		<?php if ($gm_user->userIsAdmin()) { ?>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_comment_help", "qm", "comment");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_comment; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<textarea cols="40" rows="5" name="new_comment" tabindex="<?php $tab++; print $tab; ?>" ><?php if ($action == "edituser") print stripslashes(PrintReady($user->comment)); ?></textarea>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_comment_exp_help", "qm", "comment_exp");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_comment_exp; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<input type="text" name="new_comment_exp" id="new_comment_exp" tabindex="<?php $tab++; print $tab; ?>" value="<?php if ($action == "edituser") print $user->comment_exp; ?>" />&nbsp;&nbsp;<?php EditFunctions::PrintCalendarPopup("new_comment_exp"); ?>
				</td>
			</tr>
			<?php } ?>
		<?php
			
			
		foreach($GEDCOMS as $gedcomid=>$gedarray) { ?>
			<tr>
				<td colspan="2" class="NavBlockRowSpacer">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockHeader">
					<?php print $gedarray["title"]; ?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_gedcomid_help", "qm","gedcomid");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_gedcomid; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<?php
					$tab++;
					print "<input type=\"text\" name=\"gedcomid_".$gedcomid."\" id=\"gedcomid_".$gedcomid."\" size=\"6\" tabindex=\"".$tab."\" value=\"";
					if ($action == "edituser") if (isset($user->gedcomid[$gedcomid])) print $user->gedcomid[$gedcomid];
					print "\" onblur=\"sndReq('usgid".$gedarray["id"]."', 'getpersonnamefact', true, 'pid', this.value, 'gedid', '".$gedarray["id"]."');\" />";
					LinkFunctions::PrintFindIndiLink("gedcomid_$gedcomid",$gedarray["id"]);
					print "\n<span id=\"usgid".$gedarray["id"]."\" class=\"ListItem\"> ";
					if ($action == "edituser") {
						if (isset($user->gedcomid[$gedcomid]) && !empty($user->gedcomid[$gedcomid])) {
							SwitchGedcom($gedcomid);
							$person =& Person::GetInstance($user->gedcomid[$gedcomid], "", $gedcomid);
								if (!$person->isempty) {
								print $person->name;
								PersonFunctions::PrintFirstMajorFact($person);
							}
							SwitchGedcom();
						}
					}
					print "</span>\n";
					?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_rootid_help", "qm", "rootid");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_rootid; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<?php
					$tab++;
					print "<input type=\"text\" name=\"rootid_".$gedcomid."\" id=\"rootid_".$gedcomid."\" tabindex=\"".$tab."\" size=\"6\" value=\"";
					if ($action == "edituser") if (isset($user->rootid[$gedcomid])) print $user->rootid[$gedcomid];
					print "\" onblur=\"sndReq('usroot".$gedarray["id"]."', 'getpersonnamefact', true, 'pid', this.value, 'gedid', '".$gedarray["id"]."');\" />";
					LinkFunctions::PrintFindIndiLink("rootid_$gedcomid",$gedarray["id"]);
					print "\n<span id=\"usroot".$gedarray["id"]."\" class=\"ListItem\"> ";
					if ($action == "edituser") {
						if (isset($user->rootid[$gedcomid]) && !empty($user->rootid[$gedcomid])) {
							SwitchGedcom($gedcomid);
							$person =& Person::GetInstance($user->rootid[$gedcomid], "", $gedcomid);
							if (!$person->isempty) {
								print $person->name;
								PersonFunctions::PrintFirstMajorFact($person);
							}
							SwitchGedcom();
						}
					}
					print "</span>\n";
					?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_gedcom_admin_help", "qm", "gedadmin");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_gedadmin; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<input type="checkbox" name="new_gedadmin_<?php print $gedcomid;?>" <?php if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\""; ?>tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($action == "edituser") if (isset($user->gedcomadmin[$gedcomid]) && $user->gedcomadmin[$gedcomid]) print "checked=\"checked\"";?> />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_privgroup_help", "qm","accpriv_conf");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_accpriv_conf; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<?php
					if ($action == "edituser") {
						if (!isset($user->privgroup[$gedcomid])) $user->privgroup[$gedcomid]="access";
					}
					$tab++;
					print "<select name=\"privgroup_$gedcomid\" tabindex=\"".$tab."\"";
					if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\"";
					print ">\n";
					print "<option value=\"none\"";
					if ($action == "edituser") if ($user->privgroup[$gedcomid]=="none") print " selected=\"selected\"";
					print ">".GM_LANG_visitor."</option>\n";
					print "<option value=\"access\"";
					if ($action == "edituser") if ($user->privgroup[$gedcomid]=="access") print " selected=\"selected\"";
					print ">".GM_LANG_user."</option>\n";
					print "<option value=\"admin\"";
					if ($action == "edituser") if ($user->privgroup[$gedcomid]=="admin" || $user->canadmin) print " selected=\"selected\"";
					print ">".GM_LANG_administrator."</option>\n";
//								print "<option value=\"admin\"";
//								if ($action == "edituser") if ($user->canedit[$file]=="admin") print " selected=\"selected\"";
//								print ">".GM_LANG_admin_gedcom."</option>\n";
					print "</select>\n";
					?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_relation_priv_help", "qm", "user_relationship_priv");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_user_relationship_priv; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<select name="new_relationship_privacy_<?php print $gedcomid; ?>"<?php if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\""; ?> tabindex="<?php $tab++; print $tab; ?>" >
						<option value=""<?php if ($action == "edituser") if (isset($user->relationship_privacy[$gedcomid]) && $user->relationship_privacy[$gedcomid]=="") print " selected=\"selected\"";?>><?php print GM_LANG_default; ?></option>
						<option value="Y"<?php if ($action == "edituser") if (isset($user->relationship_privacy[$gedcomid]) && $user->relationship_privacy[$gedcomid]=="Y") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
						<option value="N"<?php if ($action == "edituser") if (isset($user->relationship_privacy[$gedcomid]) && $user->relationship_privacy[$gedcomid]=="N") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
						</select>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_path_length_help", "qm", "user_path_length");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_user_path_length; ?>
					</div>
				</td>
				<td class="NavBlockField">
          			<select size="1" <?php if ($user->canadmin && $action == "edituser") print "disabled=\"disabled\""; ?> name="new_max_relation_path_<?php print $gedcomid; ?>"><?php
          				for ($y = 1; $y <= 10; $y++) {
            				print "<option";
            				if ($action == "edituser" && isset($user->max_relation_path[$gedcomid]) && $y == $user->max_relation_path[$gedcomid]) print " selected=\"selected\"";
            				else if ($y == 1) print " selected=\"selected\"";
			            	print ">";
            				print $y;
            				print "</option>";
          				}?>
          			</select>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_marr_priv_help", "qm", "user_path_marr");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_user_path_marr; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<select name="new_check_marriage_relations_<?php print $gedcomid; ?>"<?php if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\""; ?> tabindex="<?php $tab++; print $tab; ?>" >
						<option value=""<?php if ($action == "edituser") if (isset($user->check_marriage_relations[$gedcomid]) && $user->check_marriage_relations[$gedcomid]=="") print " selected=\"selected\"";?>><?php print GM_LANG_default; ?></option>
						<option value="Y"<?php if ($action == "edituser") if (isset($user->check_marriage_relations[$gedcomid]) && $user->check_marriage_relations[$gedcomid]=="Y") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
						<option value="N"<?php if ($action == "edituser") if (isset($user->check_marriage_relations[$gedcomid]) && $user->check_marriage_relations[$gedcomid]=="N") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
						</select>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_hide_live_people_help", "qm", "HIDE_LIVE_PEOPLE");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_HIDE_LIVE_PEOPLE; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<select name="new_hide_live_people_<?php print $gedcomid; ?>"<?php if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\""; ?> tabindex="<?php $tab++; print $tab; ?>" >
						<option value=""<?php if ($action == "edituser") if (isset($user->hide_live_people[$gedcomid]) && $user->hide_live_people[$gedcomid]=="") print " selected=\"selected\"";?>><?php print GM_LANG_default; ?></option>
						<option value="Y"<?php if ($action == "edituser") if (isset($user->hide_live_people[$gedcomid]) && $user->hide_live_people[$gedcomid]=="Y") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
						<option value="N"<?php if ($action == "edituser") if (isset($user->hide_live_people[$gedcomid]) && $user->hide_live_people[$gedcomid]=="N") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
						</select>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_show_living_names_help", "qm", "SHOW_LIVING_NAMES");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_SHOW_LIVING_NAMES; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<select name="new_show_living_names_<?php print $gedcomid; ?>"<?php if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\""; ?> tabindex="<?php $tab++; print $tab; ?>" >
						<option value=""<?php if ($action == "edituser") if (isset($user->show_living_names[$gedcomid]) && $user->show_living_names[$gedcomid]=="") print " selected=\"selected\"";?>><?php print GM_LANG_default; ?></option>
						<option value="Y"<?php if ($action == "edituser") if (isset($user->show_living_names[$gedcomid]) && $user->show_living_names[$gedcomid]=="Y") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
						<option value="N"<?php if ($action == "edituser") if (isset($user->show_living_names[$gedcomid]) && $user->show_living_names[$gedcomid]=="N") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
						</select>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel AdminNavBlockOption">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_can_edit_help", "qm","edit_rights");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_edit_rights; ?>
					</div>
				</td>
				<td class="NavBlockField">
					<?php
					if ($action == "edituser") {
						if (isset($user->canedit[$gedcomid])) {
							if ($user->canedit[$gedcomid]===true) $user->canedit[$gedcomid]="yes";
						}
						else $user->canedit[$gedcomid]="no";
					}
					$tab++;
					print "<select name=\"canedit_$gedcomid\" tabindex=\"".$tab."\"";
					if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\"";
					print ">\n";
					print "<option value=\"none\"";
					if ($action == "edituser") if ($user->canedit[$gedcomid]=="none") print " selected=\"selected\"";
					print ">".GM_LANG_none."</option>\n";
					print "<option value=\"edit\"";
					if ($action == "edituser") if ($user->canedit[$gedcomid]=="edit") print " selected=\"selected\"";
					print ">".GM_LANG_edit."</option>\n";
					print "<option value=\"accept\"";
					if ($action == "edituser") if ($user->canedit[$gedcomid]=="accept") print " selected=\"selected\"";
					print ">".GM_LANG_accept."</option>\n";
//								print "<option value=\"admin\"";
//								if ($action == "edituser") if ($user->canedit[$file]=="admin") print " selected=\"selected\"";
//								print ">".GM_LANG_admin_gedcom."</option>\n";
					print "</select>\n";
					?>
				</td>
			</tr>
			<?php } // end of loop through gedcoms
			?>
			<tr>
				<td class="NavBlockFooter" colspan="2">
					<input type="submit" tabindex="<?php $tab++; print $tab; ?>" value="<?php print GM_LANG_update_user; ?>" />
				</td>
			</tr>
		</table>
		</form>
		<?php }
		else {
			print "<tr><td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\"><span class=\"Error\">".GM_LANG_user_not_exist."</span></td></tr></table>";
		}
		?>
	</div>
<?php }
//-- end of $action=='edituser'

if ($action == "massupdate") {
	// -- Count the number of users to be updated
	$userlist = UserController::GetUsers();

	foreach ($userlist as $key => $user) {
		$str = "select".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $key);
		if (!isset($$str)) unset($userlist[$key]);
	}
	?>
	<form name="massupdate" id="MassUpdateForm" method="post" action="useradmin.php">
	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
		<?php AdminFunctions::AdminLink("useradmin.php", GM_LANG_user_admin); ?>
		<?php AdminFunctions::AdminLink("useradmin.php?action=listusers&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."&amp;namefilter=".$namefilter, GM_LANG_current_users); ?>
	</div>
	<!-- Setup the right box -->
	<div id="AdminColumnRight">
		<div class="NavBlockHeader AdminNavBlockHeader"><?php print GM_LANG_mu_users; ?></div>
		<!-- Start print the form -->
		<?php if (count($userlist) > 0) { ?> 
			<!-- Print the users -->
			<div class="NavBlockField UserListMassUsers">
			<?php
			foreach ($userlist as $key => $user) { ?>
				<input type="hidden" name="select<?php print $user->username;?>" value="yes" />
					<?php
					if ($TEXT_DIRECTION=="ltr") print $user->username." - ".$user->firstname." ".$user->lastname."&lrm;";
					else                        print $user->username.$user->firstname." ".$user->lastname."&rlm;";
					?>
			<?php }
			?></div><?php
		} ?>
	</div>
	<div id="AdminColumnMiddle">
	<input type="hidden" name="action" value="massupdate2" />
	<input type="hidden" name="sort" value="<?php print $sort;?>" />
	<input type="hidden" name="filter" value="<?php print $filter;?>" />
	<input type="hidden" name="usrlang" value="<?php print $usrlang;?>" />
	<input type="hidden" name="gedid" value="<?php print $gedid; ?>" />
	<input type="hidden" name="namefilter" value="<?php print $namefilter;?>" />
	<?php $tab = 0; ?>
	<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td class="NavBlockHeader AdminNavBlockHeader" colspan="3">
				<?php print "<div class=\"AdminNavBlockTitle\">".GM_LANG_mass_update."</div>"; ?>
				<?php if (count($userlist) == 0) { ?>
					<span class="Error"><?php print GM_LANG_no_users_selected; ?></span>
				<?php } ?>
			</td>
		</tr>
		<?php if (count($userlist) > 0) { ?>
			<tr>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
					<?php print GM_LANG_mu_descr; ?>
				</td>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
					<?php print GM_LANG_select; ?>
				</td>
				<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
					<?php print GM_LANG_mu_new_value; ?>
				</td>
			</tr>
			<tr>
				<!-- Sync with gedcom -->
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_sync_gedcom_help", "qm","sync_gedcom");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_sync_gedcom; ?>
					</div>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="change_sync_gedcom" tabindex="<?php print $tab;?>" value="Y" />
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<input type="checkbox" name="new_sync_gedcom" tabindex="<?php print $tab;?>" value="Y" />
					<?php $tab++;?>
				</td>
			</tr>
			<!-- Auto accept -->
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_auto_accept_help", "qm", "user_auto_accept");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_user_auto_accept; ?>
					</div>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="change_auto_accept" tabindex="<?php print $tab;?>" value="Y" />
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<input type="checkbox" name="new_auto_accept" tabindex="<?php print $tab;?>" value="Y" />
					<?php $tab++;?>
				</td>
			</tr>
			<!-- User theme -->
			<?php
			if (SystemConfig::$ALLOW_USER_THEMES) { ?>
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_user_theme_help", "qm", "user_theme");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_user_theme; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="change_user_theme" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<select name="new_user_theme" tabindex="<?php print $tab;?>">
						<option value="" selected="selected"><?php print GM_LANG_site_default;?></option>
						<?php
						$themes = GetThemeNames();
						foreach($themes as $indexval => $themedir) {
							print "<option value=\"".$themedir["dir"]."\"";
							print ">".$themedir["name"]."</option>\n";
						} ?>
						</select>
					</td>
					<?php $tab++;?>
				</tr>
			<?php } ?>
			<!-- Contact method -->
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_user_contact_help", "qm", "user_contact_method");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_user_contact_method; ?>
					</div>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="change_contact_method" tabindex="<?php print $tab;?>" value="Y" />
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<select name="new_contact_method" tabindex="<?php print $tab;?>">
					<?php if (SystemConfig::$GM_STORE_MESSAGES) { ?>
						<option value="messaging"><?php print GM_LANG_messaging;?></option>
						<option value="messaging2" selected="selected"><?php print GM_LANG_messaging2;?></option>
					<?php }
					else { ?>
						<option value="messaging3" selected="selected"><?php print GM_LANG_messaging3;?></option>
					<?php } ?>
					<option value="mailto"><?php print GM_LANG_mailto;?></option>
					<option value="none"><?php print GM_LANG_no_messaging;?></option>
					</select>
					<?php $tab++;?>
				</td>
			</tr>
			<!-- Visible online -->
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_visibleonline_help", "qm", "visibleonline");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_visibleonline; ?>
					</div>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="change_visibleonline" tabindex="<?php print $tab;?>" value="Y" />
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<input type="checkbox" name="new_visibleonline" tabindex="<?php print $tab;?>" value="Y" checked="checked" />
					<?php $tab++;?>
				</td>
			</tr>
			<!-- Edit account -->
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_editaccount_help", "qm", "editaccount");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_editaccount; ?>
					</div>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="change_editaccount" tabindex="<?php print $tab;?>" value="Y" />
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<input type="checkbox" name="new_editaccount" tabindex="<?php print $tab;?>" value="Y" checked="checked" />
					<?php $tab++;?>
				</td>
			</tr>
			<!-- Default tab -->
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("useradmin_user_default_tab_help", "qm", "user_default_tab");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_user_default_tab; ?>
					</div>
				</td>
				<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
					<input type="checkbox" name="change_default_tab" tabindex="<?php print $tab;?>" value="Y" />
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<select name="new_default_tab" tabindex="<?php print $tab;?>">
						<option value="9"><?php print GM_LANG_site_default; ?></option>
						<option value="0"><?php print GM_LANG_personal_facts;?></option>
						<option value="1"><?php print GM_LANG_notes;?></option>
						<option value="2"><?php print GM_LANG_ssourcess;?></option>
						<option value="3"><?php print GM_LANG_media;?></option>
						<option value="4"><?php print GM_LANG_relatives;?></option>
						<option value="6"><?php print GM_LANG_all;?></option>
					</select>
					<?php $tab++;?>
				</td>
			</tr>
			<!-- Gedcom related settings -->
			<?php
			foreach($GEDCOMS as $gedcomid=>$gedarray) {
				print "<tr><td colspan=\"3\" class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
				print "<tr><td class=\"NavBlockHeader\" colspan=\"3\">".$gedarray["title"]."</td></tr>"; ?>
				<!-- Rootid -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_rootid_help", "qm","rootid");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_rootid; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<?php $tab++; ?>
						<input type="checkbox" name="change_rootid_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<?php $tab++; ?>
						<input type="text" size="6" name="new_rootid_<?php print $gedcomid;?>" id="new_rootid_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="" onblur="sndReq('usroot<?php print $gedarray["id"];?>', 'getpersonnamefact', true, 'pid', this.value, 'gedid', '<?php print $gedarray["id"];?>');" />
						<?php LinkFunctions::PrintFindIndiLink("new_rootid_$gedcomid",$gedarray["id"]);
						print "\n<span id=\"usroot".$gedarray["id"]."\" class=\"ListItem\"> </span>";?>
					</td>
				</tr>
				<!-- End of rootid -->
				<!-- Start of gedcom admin -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_gedcom_admin_help", "qm", "gedadmin");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_gedadmin; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<?php $tab++; ?>
						<input type="checkbox" name="change_gedadmin_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<input type="checkbox" name="new_gedadmin_<?php print $gedcomid;?>" <?php if ($user->canadmin && $action == "edituser") print " disabled=\"disabled\""; ?>tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($action == "edituser") if (isset($user->gedcomadmin[$gedcomid]) && $user->gedcomadmin[$gedcomid]) print "checked=\"checked\"";?> />
					</td>
				</tr>
				<!-- End of gedcom admin -->
				<!-- Start of general access level -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_can_edit_help", "qm","can_edit");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_accpriv_conf; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<?php $tab++; ?>
						<input type="checkbox" name="change_privgroup_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<?php if (!isset($user->privgroup[$gedcomid])) $user->privgroup[$gedcomid]="none";
						$tab++;
						print "<select name=\"new_privgroup_$gedcomid\" tabindex=\"".$tab."\">\n";
							print "<option value=\"none\" >".GM_LANG_visitor."</option>\n";
							print "<option value=\"access\" selected=\"selected\">".GM_LANG_user."</option>\n";
							print "<option value=\"admin\" >".GM_LANG_administrator."</option>\n";
						print "</select>\n";
						?>
					</td>
				</tr>
				<!-- End of general access level -->
				<!-- Relationship privacy -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_relation_priv_help", "qm", "user_relationship_priv");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_user_relationship_priv; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="change_relationship_privacy_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<select name="new_relationship_privacy_<?php print $gedcomid; ?>" tabindex="<?php $tab++; print $tab; ?>" >
							<option value=""><?php print GM_LANG_default; ?></option>
							<option value="Y"><?php print GM_LANG_yes; ?></option>
							<option value="N"><?php print GM_LANG_no; ?></option>
						</select>
						<?php $tab++;?>
					</td>
				</tr>
				<!-- End Relationship privacy -->
				<!-- Start Relation path length -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_path_length_help", "qm", "user_path_length");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_user_path_length; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="change_max_relation_path_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<select size="1" name="new_max_relation_path_<?php print $gedcomid; ?>"><?php
							for ($y = 1; $y <= 10; $y++) {
								print "<option>".$y."</option>";
							}?>
						</select>
							<?php $tab++;?>
					</td>
				</tr>
				<!-- End Relation path length -->
				<!-- Start Check Marriage Relations -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_marr_priv_help", "qm", "user_path_marr");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_user_path_marr; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="change_check_marriage_relations_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<select name="new_check_marriage_relations_<?php print $gedcomid; ?>" tabindex="<?php $tab++; print $tab; ?>" >
							<option value=""><?php print GM_LANG_default; ?></option>
							<option value="Y"><?php print GM_LANG_yes; ?></option>
							<option value="N"><?php print GM_LANG_no; ?></option>
						</select>
					<?php $tab++;?>
					</td>
				</tr>
				<!-- End Check Marriage Relations -->
				<!-- Start Hide live people -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_hide_live_people_help", "qm", "HIDE_LIVE_PEOPLE");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_HIDE_LIVE_PEOPLE; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="change_hide_live_people_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<select name="new_hide_live_people_<?php print $gedcomid; ?>" tabindex="<?php $tab++; print $tab; ?>" >
							<option value=""><?php print GM_LANG_default; ?></option>
							<option value="Y"><?php print GM_LANG_yes; ?></option>
							<option value="N"><?php print GM_LANG_no; ?></option>
						</select>
					</td>
					<?php $tab++;?>
				</tr>
				<!-- End Hide live people -->
				<!-- Start Show living names -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_show_living_names_help", "qm", "SHOW_LIVING_NAMES");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_SHOW_LIVING_NAMES; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="change_show_living_names_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<select name="new_show_living_names_<?php print $gedcomid; ?>" tabindex="<?php $tab++; print $tab; ?>" >
							<option value=""><?php print GM_LANG_default; ?></option>
							<option value="Y"><?php print GM_LANG_yes; ?></option>
							<option value="N"><?php print GM_LANG_no; ?></option>
						</select>
					</td>
					<?php $tab++;?>
				</tr>
				<!-- End Show living names -->
				<!-- Start edit rights -->
				<tr>
					<td class="NavBlockLabel AdimNavBlockLabel">
						<div class="HelpIconContainer">
							<?php PrintHelpLink("useradmin_can_edit_help", "qm","can_edit");?>
						</div>
						<div class="AdminNavBlockOptionText">
							<?php print GM_LANG_edit_rights; ?>
						</div>
					</td>
					<td class="NavBlockField AdimNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="change_canedit_<?php print $gedcomid;?>" tabindex="<?php print $tab;?>" value="Y" />
					</td>
					<td class="NavBlockField AdimNavBlockField">
						<?php
						$tab++;
						print "<select name=\"new_canedit_$gedcomid\" tabindex=\"".$tab."\"";
						print ">\n";
							print "<option value=\"none\" >".GM_LANG_none."</option>\n";
							print "<option value=\"edit\" >".GM_LANG_edit."</option>\n";
							print "<option value=\"accept\" >".GM_LANG_accept."</option>\n";
						print "</select>\n";
						?>
					</td>
				</tr>
				<!-- End edit rights -->
				
			<?php } ?>
			<!-- End Gedcom related settings -->
			
			<tr><td colspan="3" class="NavBlockFooter">
				<input type="submit" tabindex="<?php print $tab;?>" value="<?php print GM_LANG_mass_update; ?>" />
			</td></tr>
		<?php } ?>
	</table>
	</div>
	</form>
	<?php
}

// -- Perform the mass update
if ($action == "massupdate2") {
	// -- Get the users to be updated
	$userlist = UserController::GetUsers();
	foreach ($userlist as $key => $user) {
		$str = "select".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $key);
		if (!isset($$str)) unset($userlist[$key]);
	}
	// -- Do the update
	$update = false;
	foreach ($userlist as $key => $user) {
		$newuser = CloneObj($user);
		
		foreach($GEDCOMS as $gedcomid=>$gedarray) {
			// Rootid
			$varname = "new_rootid_$gedcomid";
			$chname = "change_rootid_$gedcomid";
			if (isset($$chname)) {
				if (isset($$varname)) $newuser->rootid[$gedcomid]=$$varname;
			}
			// Edit rights
			$chname = "change_canedit_$gedcomid";
			$varname = "new_canedit_$gedcomid";
			if (isset($$chname)) $newuser->canedit[$gedcomid]=$$varname;
			// Relation privacy 
			$chname = "change_relationship_privacy_$gedcomid";
			$varname = "new_relationship_privacy_$gedcomid";
			if (isset($$chname)) {
				if ((isset($$varname))) $newuser->relationship_privacy[$gedcomid] = $$varname;
				else $newuser->relationship_privacy[$gedcomid] = "";
			}
			// Relationship privacy path
			$chname = "change_max_relation_path_$gedcomid";
			$varname = "new_max_relation_path_$gedcomid";
			if (isset($$chname)) {
				if (isset($$varname)) $newuser->max_relation_path[$gedcomid] = $$varname;
			}
			// Check marriage relation
			$chname = "change_check_marriage_relations_$gedcomid";
			$varname = "new_check_marriage_relations_$gedcomid";
			if (isset($$chname)) {
				if (isset($$varname)) $newuser->check_marriage_relations[$gedcomid] = $$varname;
			}
			// Hide live people
			$chname = "change_hide_live_people_$gedcomid";
			$varname = "new_hide_live_people_$gedcomid";
			if (isset($$chname)) {
				if (isset($$varname)) $newuser->hide_live_people[$gedcomid] = $$varname;
			}
			// Show living names
			$chname = "change_show_living_names_$gedcomid";
			$varname = "new_show_living_names_$gedcomid";
			if (isset($$chname)) {
				if (isset($$varname)) $newuser->show_living_names[$gedcomid] = $$varname;
			}
			// Privacy group
			$chname = "change_privgroup_$gedcomid";
			$varname = "new_privgroup_$gedcomid";
			if (isset($$chname)) {
				if (isset($$varname)) $newuser->privgroup[$gedcomid] = $$varname;
			}
			// Gedcom admin 
			$chname = "change_gedadmin_$gedcomid";
			$varname = "new_gedadmin_$gedcomid";
			if (isset($$chname)) {
				if ((isset($$varname)) && ($$varname == "Y")) $newuser->gedcomadmin[$gedcomid] = true;
				else $newuser->gedcomadmin[$gedcomid] = false;
			}
		}
		if (isset($change_auto_accept)) {
			if (isset($new_auto_accept)) $newuser->auto_accept = true;
			else $newuser->auto_accept = false;
		}
		if (isset($change_user_theme)) {
			if (!isset($new_user_theme)) $new_user_theme="";
			$newuser->theme = $new_user_theme;
		}
		if (isset($change_contact_method)) {
			if (!empty($new_contact_method)) $newuser->contactmethod = $new_contact_method;
		}
		if (isset($change_visibleonline)) {
			if (isset($new_visibleonline)) $newuser->visibleonline=true;
			else $newuser->visibleonline=false;
		}
		if (isset($change_editaccount)) {
			if (isset($new_editaccount)) $newuser->editaccount=true;
			else $newuser->editaccount=false;
		}
		if (isset($change_default_tab)) {
			if (isset($new_default_tab)) $newuser->default_tab = $new_default_tab;
		}
		if (isset($change_sync_gedcom)) {
			if (isset($new_sync_gedcom)) {
				$newuser->sync_gedcom = "Y";
				AdminFunctions::UpdateUserIndiEmail($newuser);
			}
			else $newuser->sync_gedcom = "N";
		}
		if (UserController::DeleteUser($user->username, "changed")) {
			if (UserController::AddUser($newuser, "changed")) {
				$update = true;
			}
			else {
				$update = false;
			}
		}
		else {
			$update = false;
		}

	}
	if ($update) $message .= GM_LANG_update_users_selected_ok;
	else $message .= GM_LANG_update_users_selected_nok;
	$action = "listusers";
}

//-- print out a list of the current users
// NOTE: WORKING
if (($action == "listusers") || ($action == "edituser2") || ($action == "deleteuser") || ($action == "massupdate2")) {
	if ($view != "preview") $showprivs = false;
	else $showprivs = true;

	switch ($sort) {
		case "sortfname":
			$users = UserController::GetUsers("firstname","asc", "lastname");
			break;
		case "sortlname":
			$users = UserController::GetUsers("lastname","asc", "firstname");
			break;
		case "sortllgn":
			$users = UserController::GetUsers("sessiontime","desc");
			break;
		case "sortuname":
			$users = UserController::GetUsers("username","asc");
			break;
		case "sortreg":
			$users = UserController::GetUsers("reg_timestamp","desc");
			break;
		case "sortver":
			$users = UserController::GetUsers("verified","asc");
			break;
		case "sortveradm":
			$users = UserController::GetUsers("verified_by_admin","asc");
			break;
		default: 
			$users = UserController::GetUsers("username","asc");
			break;
	}
	
	// First filter the users, otherwise the javascript to unfold priviledges gets disturbed
	foreach($users as $username=>$user) {
		if ($filter == "warnings") {
			$warn = false;
			if (!empty($user->comment_exp)) {
				if ((strtotime($user->comment_exp) != "-1") && (strtotime($user->comment_exp) < time("U"))) $warn = true;
			}
			if (isset($users[$username])) {
				if (((date("U") - $user->reg_timestamp) > 604800) && ($user->verified!="Y")) $warn = true;
			}
			if (!$warn) unset($users[$username]);
		}
		else if ($filter == "adminusers") {
			if (!$user->canadmin) unset($users[$username]);
		}
		else if ($filter == "usunver") {
			if ($user->verified == "Y") unset($users[$username]);
		}
		else if ($filter == "admunver") {
			if (($user->verified_by_admin == "Y") || ($user->verified != "Y")) unset($users[$username]);
		}
		else if ($filter == "language") {
			if ($user->language != $usrlang) unset($users[$username]);
		}
		else if ($filter == "gedadmin") {
			if (isset($user->gedcomadmin[$gedid])) {
				if (!$user->gedcomadmin[$gedid] || $user->canadmin) unset($users[$username]);
			}
			else unset($users[$username]);
		}
		else if ($filter == "privoverride") {
			if ((!isset($user->relationship_privacy[$gedid]) || $user->relationship_privacy[$gedid] == "") &&
			(!isset($user->hide_live_people[$gedid]) || $user->hide_live_people[$gedid] == "") &&
			(!isset($user->check_marriage_relations[$gedid]) || $user->check_marriage_relations[$gedid] == "") &&
			(!isset($user->show_living_names[$gedid]) || $user->show_living_names[$gedid] == "")) unset($users[$username]);
		}
	}
	// If a name filter is entered, check for existence of the string in the user fullname
	if (!empty($namefilter)) {
		foreach($users as $username=>$user) {
			if (!stristr($user->firstname, $namefilter) && !stristr($user->lastname, $namefilter)&& !stristr($user->username, $namefilter)) unset($users[$username]);
		}
	}
	
	// Then show the users
	?>
	<!-- Setup the left box -->
	
		<div id="AdminColumnLeft">
			<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
			<?php AdminFunctions::AdminLink("useradmin.php", GM_LANG_user_admin); ?>
			<?php if ($view != "preview") AdminFunctions::AdminLink("javascript: ".GM_LANG_do_massupdate."\" onclick=\"document.userlist.action.value='massupdate'; document.userlist.submit();return false;" , GM_LANG_do_massupdate);
			?>
		</div>
		<div id="UserListing">
		<form name="userlist" method="post" action="useradmin.php">
			<input type="hidden" name="action" value="listusers" />
			<input type="hidden" name="sort" value="<?php print $sort; ?>" />
			<input type="hidden" name="filter" value="<?php print $filter; ?>" />
			<input type="hidden" name="usrlang" value="<?php print $usrlang; ?>" />
			<input type="hidden" name="gedid" value="<?php print $gedid; ?>" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="<?php print ($view == "preview" ? "8": "10");?>" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
					<?php print GM_LANG_current_users; ?>
				</div>
				<?php if ($message != "") {
					print "<span class=\"Error\">".$message."</span>";
				}?>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel" colspan="<?php print ($view == "preview" ? "4": "5");?>">
				<?php print GM_LANG_usernamefilter;?>
			</td>
			<td class="NavBlockField AdminNavBlockField" colspan="<?php print ($view == "preview" ? "4": "5");?>">
				<input type="text" name="namefilter" value="<?php print $namefilter;?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockFooter" colspan="<?php print ($view == "preview" ? "8": "10");?>">
				<input type="submit" name="refreshlist" value="<?php print GM_LANG_refresh; ?>" />
			</td>
		</tr>
		<tr>
		<?php if ($view != "preview") { ?>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<a href="javascript: <?php print GM_LANG_select;?> " onclick="
				<?php 
				foreach($users as $username=>$user) {
					print "document.userlist.select".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $username).".checked=document.userlist.select".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $username).".checked?false:true; ";
				}
				?>return false;">
				<?php print GM_LANG_select;?></a>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_delete."<br />".GM_LANG_edit;?>
			</td>
			<?php } ?>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortuname&amp;namefilter=".$namefilter."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."\">"; ?><?php print GM_LANG_username; ?></a>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortlname&amp;namefilter=".$namefilter."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."\">"; ?><?php print GM_LANG_full_name; ?></a>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_inc_languages; ?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader UserListRightsColumn">
				<a href="javascript: <?php print GM_LANG_privileges;?>" onclick="
				<?php
				$k = 1;
				for ($i=1, $max=count($users)+1; $i<=$max; $i++) print "expand_layer('user-geds".$i."'); ";
				print " return false;\">";
				print "<img id=\"user-geds".$k."_img\" src=\"".GM_IMAGE_DIR."/";
				if ($showprivs == false) print $GM_IMAGES["plus"]["other"];
				else print $GM_IMAGES["minus"]["other"]; ?>
				" width="11" height="11" alt="" />
				<?php print GM_LANG_privileges; ?>
				</a>
				<div id="user-geds<?php print $k;?>" style="display:
				<?php
				if ($showprivs == false) { ?> none"> <?php }
				else { ?> block"> <?php } ?>
				</div>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortreg&amp;namefilter=".$namefilter."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."\">"; ?><?php print GM_LANG_date_registered; ?></a>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortllgn&amp;namefilter=".$namefilter."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."\">"; ?><?php print GM_LANG_last_login; ?></a>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortver&amp;namefilter=".$namefilter."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."\">"; ?><?php print GM_LANG_verified; ?></a>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortveradm&amp;namefilter=".$namefilter."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."\">"; ?><?php print GM_LANG_verified_by_admin; ?></a>
			</td>
		</tr>
		<?php
		$k++;
		foreach($users as $username=>$user) {
			if (empty($user->language)) $user->language=$LANGUAGE; ?>
			<?php
			if ($view != "preview") { ?>
				<tr>
					<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
						<input type="checkbox" name="select<?php print preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $username);?>" value="yes" />
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel">
						<?php if ($user->username != $gm_user->username) {
							if ($TEXT_DIRECTION=="ltr") print "<a href=\"useradmin.php?action=deleteuser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."&amp;namefilter=".$namefilter."\" onclick=\"return confirm('".GM_LANG_confirm_user_delete." $username?');\">".GM_LANG_delete."</a><br />\n";
							else if (begRTLText($username)) print "<a href=\"useradmin.php?action=deleteuser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."&amp;namefilter=".$namefilter."\" onclick=\"return confirm('?".GM_LANG_confirm_user_delete." $username');\">".GM_LANG_delete."</a><br />\n";
							else print "<a href=\"useradmin.php?action=deleteuser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."&amp;namefilter=".$namefilter."\" onclick=\"return confirm('?$username ".GM_LANG_confirm_user_delete." ');\">".GM_LANG_delete."</a><br />\n";
						}
						print "<a href=\"useradmin.php?action=edituser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;gedid=".$gedid."&amp;namefilter=".$namefilter."\">".GM_LANG_edit."</a>\n";?>
					</td>
				<?php } ?>
					<td class="NavBlockLabel AdminNavBlockLabel"> <?php
					if (!empty($user->comment_exp)) {
						if ((strtotime($user->comment_exp) != "-1") && (strtotime($user->comment_exp) < time("U"))) print "<span class=\"Error\">".$username."</span>";
						else print $username;
					}
					else print $username;
					if (!empty($user->comment)) print "<br /><img class=\"BlockAdminIcon\" title=\"".PrintReady(stripslashes($user->comment))."\" alt=\"".PrintReady(stripslashes($user->comment))."\"  src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" />";?>
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel">
						<?php
						if ($TEXT_DIRECTION=="ltr") {
							if ($NAME_REVERSE) print $user->lastname." ".$user->firstname;
							else print $user->firstname." ".$user->lastname;
							print "&lrm;\n";
						}
						else {
							if ($NAME_REVERSE) print $user->lastname." ".$user->firstname;
							else print $user->firstname." ".$user->lastname;
							print "&rlm;\n";
						}
						?>
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel">
						<?php print constant("GM_LANG_lang_name_".$user->language);?><br />
						<img src="<?php print $language_settings[$user->language]["flagsfile"];?>" class="BrightFlag" alt="<?php print constant("GM_LANG_lang_name_".$user->language);?>" title="<?php print constant("GM_LANG_lang_name_".$user->language);?>" />
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel">
						<?php
						print "<a href=\"javascript: ".GM_LANG_privileges."\" onclick=\"expand_layer('user-geds".$k."'); return false;\"><img id=\"user-geds".$k."_img\" src=\"".GM_IMAGE_DIR."/";
						if ($showprivs == false) print $GM_IMAGES["plus"]["other"];
						else print $GM_IMAGES["minus"]["other"];
						print "\" width=\"11\" height=\"11\" alt=\"\" />";
						print "</a>";
						print "<div id=\"user-geds".$k."\" style=\"display: ";
						if ($showprivs == false) print "none;\">";
						else print "block;\">";
							print "<ul>";
							if ($user->canadmin) print "<li class=\"Warning\">".GM_LANG_can_admin."</li>\n";
							uksort($GEDCOMS, "strnatcasecmp");
							reset($GEDCOMS);
							foreach($GEDCOMS as $gedid=>$gedcom) {
								if (isset($user->privgroup[$gedid])) $vval = $user->privgroup[$gedid];
								else $vval = "none";
								if ($vval == "") $vval = "none";
								if (isset($user->gedcomadmin[$gedid]) && $user->gedcomadmin[$gedid]) $vval = "admin_gedcom";
								if (isset($user->gedcomid[$gedid])) $uged = $user->gedcomid[$gedid];
								else $uged = "";
								if ($vval=="accept") print "<li class=\"Warning\">"; 
								else print "<li>";
								print constant("GM_LANG_".$vval)." ";
								if ($uged != "") print "<a href=\"individual.php?pid=".$uged."&amp;gedid=".$gedid."\">".$gedcom["gedcom"]."</a></li>\n";
								else print $gedcom["gedcom"]."</li>\n";
							}
							print "</ul>";
						print "</div>";
						$k++; ?>
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel UserListDateColumn">
						<?php
						if (((date("U") - $user->reg_timestamp) > 604800) && ($user->verified!="Y")) print "<span class=\"Error\">";
						print GetChangedDate(date("d", $user->reg_timestamp)." ".date("M", $user->reg_timestamp)." ".date("Y", $user->reg_timestamp))."<br />".date($TIME_FORMAT, $user->reg_timestamp);
						if (((date("U") - $user->reg_timestamp) > 604800) && ($user->verified!="Y")) print "</span>";
						?>
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel UserListDateColumn">
						<?php
						if ($user->reg_timestamp > $user->sessiontime) {
							print GM_LANG_never;
						}
						else {
							print GetChangedDate(date("d", $user->sessiontime)." ".date("M", $user->sessiontime)." ".date("Y", $user->sessiontime))."<br />".date($TIME_FORMAT, $user->sessiontime);
						}
						?>
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel">
						<?php
						if ($user->verified=="Y") print GM_LANG_yes;
						else print GM_LANG_no;
						?>
					</td>
					<td class="NavBlockLabel AdminNavBlockLabel">
						<?php
						if ($user->verified_by_admin=="Y") print GM_LANG_yes;
						else print GM_LANG_no;
						?>
					</td>
				</tr>
		<?php } ?>
			<tr>
				<td colspan="<?php print ($view == "preview" ? "8": "10");?>" class="NavBlockFooter">
					<input type="submit" name="refreshlist" value="<?php print GM_LANG_refresh; ?>" />
				</td>
			</tr>
		</table>
	</form>
	</div>
	<?php
}

// Cleanup users and user rights
//NOTE: WORKING
if ($action == "cleanup") {
	?>
	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
		<?php AdminFunctions::AdminLink("useradmin.php", GM_LANG_user_admin); ?>
	</div>
	<div id="AdminColumnMiddle">
		<form name="cleanupform" method="post" action="">
			<input type="hidden" name="action" value="cleanup2" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="3" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
					<?php print GM_LANG_cleanup_users; ?>
				</div>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel" colspan="2">
				<?php
				// Check for idle users
				if (!isset($month)) $month = 1;
				print GM_LANG_usr_idle;?>
			</td>
			<td class="NavBlockField AdminNavBlockField">
				<select onchange="document.location=options[selectedIndex].value;">
					<?php
					for($i=1; $i<=12; $i++) { 
						print "<option value=\"useradmin.php?action=cleanup&amp;month=$i\"";
						if ($i == $month) print " selected=\"selected\"";
						print " >".$i."</option>";
					} ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_username;?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_message;?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_select;?>
			</td>
		</tr>
			<?php
			// Check users not logged in too long
			$users = UserController::GetUsers();
			$ucnt = 0;
			foreach($users as $key=>$user) {
				if ($user->sessiontime == "0") $datelogin = $user->reg_timestamp;
				else $datelogin = $user->sessiontime;
				if ((mktime(0, 0, 0, date("m")-$month, date("d"), date("Y")) > $datelogin) && ($user->verified == "Y") && ($user->verified_by_admin == "Y")) {
					?>
					<tr>
						<td class="NavBlockLabel AdminNavBlockLabel">
							<?php print $user->username." - ".$user->firstname." ".$user->lastname."</td>";
							print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".GM_LANG_usr_idle_toolong;
							print GetChangedDate(date("d", $datelogin)." ".date("M", $datelogin)." ".date("Y", $datelogin));?>
						</td>
						<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
							<input type="checkbox" name="<?php print "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user->username); ?>" value="yes" />
							<?php $ucnt++; ?>
						</td>
					</tr>
					<?php
				}
			}
			
			// Check unverified users
			foreach($users as $key=>$user) {
				if (((date("U") - $user->reg_timestamp) > 604800) && ($user->verified!="Y")) {
				?>
					<tr>
						<td class="NavBlockLabel AdminNavBlockLabel">
							<?php print $user->username." - ".$user->firstname." ".$user->lastname."</td>";
							print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".GM_LANG_del_unveru;?>
						</td>
						<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
							<input type="checkbox" checked="checked" name="<?php print "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user->username); ?>" value="yes" />
							<?php $ucnt++; ?>
						</td>
					</tr>
				<?php
				}
			}
			
			// Check users not verified by admin
			foreach($users as $key=>$user) {
				if (($user->verified_by_admin!="Y") && ($user->verified == "Y")) {
				?>
					<tr>
						<td class="NavBlockLabel AdminNavBlockLabel">
							<?php print $user->username." - ".$user->firstname." ".$user->lastname."</td>";
							print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".GM_LANG_del_unvera; ?>
						</td>
						<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
							<input type="checkbox" name="<?php print "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user->username); ?>" value="yes" />
							<?php $ucnt++; ?>
						</td>
					</tr>
				<?php
				}
			}
			
			// Then check obsolete gedcom rights
			$gedrights = array();
			foreach($users as $key=>$user) {
				foreach($user->canedit as $gedid=>$data) {
					if ((!isset($GEDCOMS[$gedid])) && (!in_array($gedid, $gedrights))) $gedrights[] = $gedid;
				}
				foreach($user->gedcomid as $gedid=>$data) {
					if ((!isset($GEDCOMS[$gedid])) && (!in_array($gedid, $gedrights))) $gedrights[] = $gedid;
				}
				foreach($user->rootid as $gedid=>$data) {
					if ((!isset($GEDCOMS[$gedid])) && (!in_array($gedid, $gedrights))) $gedrights[] = $gedid;
				}
			}
			ksort($gedrights);
			foreach($gedrights as $key=>$gedcomid) { ?>
					<tr>
						<td class="NavBlockLabel AdminNavBlockLabel">
							<?php print $GEDCOMS[$gedcomid]["title"]."</td>";
							print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".GM_LANG_del_gedrights;?>
						</td>
						<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
							<input type="checkbox" checked="checked" name="<?php print "delg_".$gedcomid; ?>" value="yes" />
							<?php $ucnt++; ?>
						</td>
					</tr>
				<?php
			}
			// NOTE: Nothing found to clean up
			if ($ucnt == 0) {
				print "<tr><td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\" colspan=\"3\"><span class=\"Error\">".GM_LANG_usr_no_cleanup."</span></td></tr>";
			}
			else { ?>
				<tr>
					<td class="NavBlockFooter" colspan="3">
						<input type="submit" value="<?php print GM_LANG_del_proceed; ?>" />
					</td>
				</tr>
			<?php } ?>
		</table>
		</form>
	</div>
	<?php
}
if ($action == "cleanup2") {
	$users = UserController::GetUsers();
	foreach($users as $key=>$user) {
		$var = "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user->username);
		if (isset($$var)) {
			if (UserController::DeleteUser($key)) $message .= GM_LANG_usr_deleted.$user->username."<br />";
		}
		else {
			foreach($user->canedit as $gedid=>$data) {
				$var = "delg_".$gedid;
				if (isset($$var)) {
					unset($user->canedit[$gedid]);
					$message .= $gedid.":&nbsp;&nbsp;".GM_LANG_usr_unset_rights.$user->username."<br />";
					if (isset($user->rootid[$gedid])) {
						unset($user->rootid[$gedid]);
						$message .= $gedid.":&nbsp;&nbsp;".GM_LANG_usr_unset_rootid.$user->username."<br />";
					}
					if (isset($user->gedcomid[$gedid])) {
						unset($user->gedcomid[$gedid]);
						$message .= $gedid.":&nbsp;&nbsp;".GM_LANG_usr_unset_gedcomid.$user->username."<br />";
					}
					UserController::DeleteUser($key, "changed");
					UserController::AddUser($user, "changed");
				}
			}
		}
	}
	$action = "";
}

// Print main menu
// NOTE: WORKING
if ($action == "") {
	?>
	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	</div>
	<!-- Setup the middle box -->
	<div id="AdminColumnMiddle">
	<table class="NavBlockTable AdminNavBlockTable">
		<?php
			$menu = new AdminMenu();
			$menu->SetBarText(GM_LANG_user_admin);
			$menu->SetBarStyle("AdminNavBlockHeader");
			$menu->AddItem("", "", "", "useradmin.php?action=listusers", GM_LANG_current_users, "left");
			$menu->AddItem("", "", "", "javascript: ".GM_LANG_message_to_all."\" onclick=\"message('all', 'messaging2', '', ''); return false;",  GM_LANG_message_to_all, "right");
			$menu->AddItem("", "", "", "useradmin.php?action=cleanup", GM_LANG_cleanup_users, "left");
			$menu->AddItem("", "", "", "javascript: ".GM_LANG_broadcast_never_logged_in."\" onclick=\"message('never_logged', 'messaging2', '', ''); return false;", GM_LANG_broadcast_never_logged_in, "right");
			$menu->AddItem("", "", "", "useradmin.php?action=cleanup_messages", GM_LANG_cleanup_messages, "left");
			$menu->AddItem("", "", "", "javascript: ".GM_LANG_broadcast_not_logged_6mo."\" onclick=\"message('last_6mo', 'messaging2', '', ''); return false;", GM_LANG_broadcast_not_logged_6mo, "right");
			$menu->AddItem("", "", "", "useradmin.php?action=createform", GM_LANG_add_user, "left");
			$menu->PrintItems();
			if ($message != "") {
				print "<div class=\"Error\">".$message."</div>";
			} ?>
			</table>
		<!-- Setup the top bar for info -->
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="2" class="NavBlockRowSpacer">&nbsp;</td>
		</tr>
		<tr>
			<td class="NavBlockHeader" colspan="2">
				<?php print GM_LANG_admin_info; ?>
			</td>
		</tr>
		<?php
		$users = UserController::GetUsers();
		$totusers = 0;			// Total number of users
		$warnusers = 0;			// Users with warning
		$applusers = 0;			// Users who have not verified themselves
		$nverusers = 0;			// Users not verified by admin but verified themselves
		$adminusers = 0;		// Administrators
		$userlang = array();	// Array for user languages
		$gedadmin = array();	// Array for gedcom admins
		foreach($users as $username=>$user) {
			if (empty($user->language)) $user->language=$LANGUAGE;
			$totusers = $totusers + 1;
			if (((date("U") - $user->reg_timestamp) > 604800) && ($user->verified!="Y")) $warnusers++;
			else {
				if (!empty($user->comment_exp)) {
					if ((strtotime($user->comment_exp) != "-1") && (strtotime($user->comment_exp) < time("U"))) $warnusers++;
				}
			}
			if (($user->verified_by_admin != "Y") && ($user->verified == "Y")) $nverusers++;
			if ($user->verified != "Y") $applusers++;
			if ($user->canadmin) $adminusers++;
			foreach($user->gedcomadmin as $gedid=>$rights) {
				if ($rights == true && !$user->canadmin) {
					if (isset($GEDCOMS[$gedid])) {
						if (isset($gedadmin[$GEDCOMS[$gedid]["title"]])) $gedadmin[$GEDCOMS[$gedid]["title"]]["number"]++;
						else {
							$gedadmin[$GEDCOMS[$gedid]["title"]]["name"] = $GEDCOMS[$gedid]["title"];
							$gedadmin[$GEDCOMS[$gedid]["title"]]["number"] = 1;
							$gedadmin[$GEDCOMS[$gedid]["title"]]["ged"] = $gedid;
						}
					}
				}
			}
			if (isset($userlang[constant("GM_LANG_lang_name_".$user->language)])) $userlang[constant("GM_LANG_lang_name_".$user->language)]["number"]++;
			else {
				$userlang[constant("GM_LANG_lang_name_".$user->language)]["langname"] = $user->language;
				$userlang[constant("GM_LANG_lang_name_".$user->language)]["number"] = 1;
			}
		}
		?>
		<!-- Setup the info block -->
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print GM_LANG_users_total;?>
			</td>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print $totusers; ?>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php 
				if ($adminusers == 0) print GM_LANG_users_admin;
				else print "<a href=\"useradmin.php?action=listusers&amp;filter=adminusers\">".GM_LANG_users_admin."</a>";
				?>
			</td>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print $adminusers; ?>
			</td>
		</tr>
		<?php
		// GEDCOM Administrators
		?>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print GM_LANG_users_gedadmin;?>
			</td>
			<?php
			if (count($gedadmin) == 0) { ?>
			<td class="NavBlockLabel AdminNavBlockLabel">
					0
			</td>
			<?php }  			
			asort($gedadmin);
			$pass = 1;
			foreach ($gedadmin as $key=>$geds) { 
				if ($pass != 1) print "</tr><tr>";
				$pass++;
				?>
				<td class="NavBlockLabel AdminNavBlockLabel">
				<?php
				if ($geds["number"] == 0) print $geds["name"];
				else print "<a href=\"useradmin.php?action=listusers&amp;filter=gedadmin&amp;gedid=".$geds["ged"]."\">".$geds["name"]."&nbsp;"."(".(int)$geds["number"].")</a>";
			} ?>
		</tr>
		<?php 
		// Users with warnings
		?>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php 
				if ($warnusers == 0) print GM_LANG_warn_users;
				else print "<a href=\"useradmin.php?action=listusers&amp;filter=warnings\">".GM_LANG_warn_users."</a>";
				?>
			</td>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print $warnusers; ?>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php 
				if ($applusers == 0) print GM_LANG_users_unver;
				else print "<a href=\"useradmin.php?action=listusers&amp;filter=usunver\">".GM_LANG_users_unver."</a>";
				?>
			</td>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print $applusers; ?>
			</td>
		</tr>
		<?php
		// Unverified users
		?>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php 
				if ($nverusers == 0) print GM_LANG_users_unver_admin;
				else print "<a href=\"useradmin.php?action=listusers&amp;filter=admunver\">".GM_LANG_users_unver_admin."</a>";
				?>
			</td>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print $nverusers; ?>
			</td>
		</tr>
		<?php
		// User languages
		?>
		<tr>
			<td class="NavBlockLabel AdminNavBlockLabel">
				<?php print GM_LANG_users_langs; ?>
			</td>
			<td class="NavBlockLabel AdminNavBlockLabel">
			<?php asort($userlang);
			foreach ($userlang as $key=>$ulang) {
				?>
				<img src="<?php print $language_settings[$ulang["langname"]]["flagsfile"];?>" class="BrightFlag" alt="<?php print $key; ?>" title="<?php print $key;?>." />
					<a href="useradmin.php?action=listusers&amp;filter=language&amp;usrlang=<?php print $ulang["langname"];?>"><?php print $key;?></a>
					<?php print "(".$ulang["number"].")";?><br />
			<?php } ?>
			</td>
		</tr>
		</table>
	</div>
<?php }
// Cleanup messages

// Cleanup message boxes
if ($action == "cleanup_messbox") {
	$users = UserController::GetUsers();
	foreach ($users as $key => $user) {
		$fld = "msg_".$user->username;
		if (isset($$fld)) {
			MessageController::DeleteUserMessages($user->username);
		}
	}
	$action = "cleanup_messages";
}

// Cleanup old messages
if ($action == "cleanup_messold") {
	$messages = MessageController::GetUserMessages();
	foreach ($messages as $key => $message) {
		if ($message->age >= $cleanup) MessageController::DeleteMessage($message->id);
	}
	$action = "cleanup_messages";
}

//NOTE: WORKING
if ($action == "cleanup_messages") {
	?>
	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
		<?php AdminFunctions::AdminLink("useradmin.php", GM_LANG_user_admin); ?>
	</div>
	<div id="AdminColumnMiddle">
		<form name="cleanmessageform" method="post" action="">
		<input type="hidden" name="action" value="cleanup_messages2" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="6" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
					<?php print GM_LANG_cleanup_messages; ?>
				</div>
			</td>
		</tr>
		<tr>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_username;?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_number;?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_select;?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_username;?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_number;?>
			</td>
			<td class="NavBlockColumnHeader AdminNavBlockColumnHeader">
				<?php print GM_LANG_select;?>
			</td>
		</tr>
		<?php
			if (!isset($users)) $users = UserController::GetUsers();
			$count = 0;
			$mons = array();
			foreach($users as $key=>$user) {
				$messages = MessageController::GetUserMessages($user->username);
				// Only print users with messages
				if (count($messages) > 0) {
					$count++;
					
					// Meanwhile, we get the age of the messages
					foreach($messages as $id => $message) {
						$mmon = $message->age;
						if (isset($mons[$mmon])) $mons[$mmon]++;
						else $mons[$mmon] = 1;
					}
					// Now print the users
					if ($count%2) print "\n<tr>\n";
						print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
							print $user->username."&nbsp;(".$user->firstname." ".$user->lastname.")";
						print "</td>\n";
						print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
							print count($messages);
						print "</td>\n";
						print "<td class=\"NavBlockField AdminNavBlockField NavBlockCheckRadio\">";
							print "<input type=\"checkbox\" name=\"msg_".$user->username."\" value=\"yes\" />";
						print "</td>\n";
					if ($count%2 == 0) print "</tr>\n";
				}
			}
			if ($count%2) print "<td class=\"NavBlockLabel AdminNavBlockLabel\">&nbsp;</td><td class=\"NavBlockLabel AdminNavBlockLabel\">&nbsp;</td><td class=\"NavBlockField AdminNavBlockField\">&nbsp;</td></tr>";
			else print "</tr>";
			print "<tr><td colspan=\"6\" class=\"NavBlockFooter\"><input type=\"submit\" value=\"".GM_LANG_del_mail."\" onclick=\"document.cleanmessageform.action.value='cleanup_messbox'; return confirm('".GM_LANG_confirm_sure."');\" /></td></tr>\n";
			
			// Print the month cleanup
			$sum = array_sum($mons);
			print "<tr><td class=\"NavBlockLabel AdminNavBlockLabel\">";
			print GM_LANG_total_messages."</td><td class=\"NavBlockLabel AdminNavBlockLabel\" colspan=\"2\">".$sum."</td>";
			ksort($mons);	
			$maxmon = end(array_keys($mons));
			// Convert the totals to cumulative percentage
			$mons = array_reverse($mons, true);
			$tot = 0;
			foreach ($mons as $mon =>$number) {
				$tot = $tot + $number;
				$perc = round(100 * $tot / $sum);
//				print "perc: ".$perc." sum: ".$sum." tot: ".$tot."<br />";
				$mons[$mon] = $perc;
//				print $mon." ".$mons[$mon];
			}
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".GM_LANG_cleanup_older."</td>\n";
			print "<td class=\"NavBlockField AdminNavBlockField\" colspan=\"2\">";
			print "<select  name=\"cleanup\">\n";
			for ($i=0; $i<=$maxmon; $i++) {
				if (isset($mons[$i])) {
					print "<option value=\"".$i."\"";
					if ($i == $maxmon) print " selected=\"selected\" ";
					print ">".$i."&nbsp;".GM_LANG_months." (".$mons[$i]."%)</option>\n";
				}
			}
			print "</select>\n";
			print "</td></tr>";
			print "<tr><td colspan=\"6\" class=\"NavBlockFooter\"><input type=\"submit\" id=\"cleanup\" value=\"".GM_LANG_delete."\" onclick=\"document.cleanmessageform.action.value='cleanup_messold'; return confirm('".GM_LANG_confirm_sure."');\" /></td></tr>";
			?>
			</table>
		</form>
	</div>
	<?php
}
PrintFooter();
?>
