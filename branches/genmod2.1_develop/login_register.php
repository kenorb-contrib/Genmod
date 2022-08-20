<?php
/**
 * Register as a new User or request new password if it is lost
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
 * This Page Is Valid XHTML 1.0 Transitional! > 29 August 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: login_register.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

// Remove slashes
if (isset($user_firstname)) $user_firstname = stripslashes($user_firstname);
if (isset($user_lastname)) $user_lastname = stripslashes($user_lastname);

$message="";
if (!isset($action)) $action = "";
if (!isset($url)) $url = "index.php";
switch ($action) {
	case "pwlost" :
  		PrintHeader("Genmod - " . GM_LANG_lost_pw_reset);	?>
  		<script language="JavaScript" type="text/javascript">
		<!--
		function checkform(frm)
		{
		/*
		if (frm.user_email.value == "")
		{
		 alert("<?php print GM_LANG_enter_email; ?>");
		 frm.user_email.focus();
		 return false;
		}
		*/
		return true;
		}
		//-->
		</script>
		<div class="LoginPageContainer">
			<form name="requestpwform" action="login_register.php" method="post" onsubmit="t = new Date(); document.requestpwform.time.value=t.toUTCString(); return checkform(this);">
			<input type="hidden" name="time" value="" />
			<input type="hidden" name="action" value="requestpw" />
			<span class="Warning"><?php print $message;?></span>
			<table class="NavBlockTable LoginPageTable">
			<tr><td class="NavBlockHeader" colspan="2"><?php PrintHelpLink("pls_note11", "qm", "lost_pw_reset"); print GM_LANG_lost_pw_reset;?></td></tr>
			 <tr><td class="NavBlockLabel"><?php print GM_LANG_username?></td><td class="NavBlockField"><input type="text" name="user_name" value="" /></td></tr>
			 <tr><td class="NavBlockFooter" colspan="2"><input type="submit" value="<?php print GM_LANG_lost_pw_reset; ?>" /></td></tr>
		    </table>
		  </form>
		</div>
		<script language="JavaScript" type="text/javascript">
		<!--
			document.requestpwform.user_name.focus();
		//-->
		</script>
		<?php
		break;
	case "requestpw" :
		$QUERY_STRING = "";
		if (!isset($user_name)) $user_name = "";
		PrintHeader("Genmod - " . GM_LANG_lost_pw_reset);
		print "<div class=\"LoginPageContainer\">";
		$newuser =& User::GetInstance($user_name);
		if ($newuser->is_empty) {
			print "<span class=\"Warning\">";
			PrintText("user_not_found");
			print "</span><br />";
		}
		else if (empty($newuser->email)) {
			print "<span class=\"Warning\">";
			PrintText("user_no_email");
			print "</span><br />";
		}
		else {
			$user_new_pw = md5 (uniqid (rand()));
			$olduser = CloneObj($newuser);
			UserController::DeleteUser($user_name, "reqested new password for");
			
			$newuser->password = password_hash($user_new_pw, PASSWORD_DEFAULT);
			//$newuser->reg_timestamp = date("U");
			UserController::addUser($newuser, "reqested new password for");

			// switch language to user settings
			$oldlanguage = $LANGUAGE;
			$LANGUAGE = $newuser->language;
			if (isset($gm_language[$LANGUAGE])) $templang = LanguageFunctions::LoadEnglish(true, false, true);
			$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
			$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
			$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
			$WEEK_START	= $WEEK_START_array[$LANGUAGE];
			$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
			
			$mail_body = "";
			$mail_body .= str_replace("#user_fullname#", $newuser->firstname." ".$newuser->lastname, $templang["mail04_line01"]) . "\r\n\r\n";
			$mail_body .= $templang["mail04_line02"] . "\r\n\r\n";
			$mail_body .= $templang["username"] . " " . $newuser->username . "\r\n";
			$mail_body .= $templang["password"] . " " . $user_new_pw . "\r\n";
			$mail_body .= $templang["mail04_line03"] . "\r\n";
			$mail_body .= $templang["mail04_line04"] . "\r\n";
			$mail_body .= PrintText("mail04_line05", 0, 1) . "\r\n\r\n";
			
			if (LOGIN_URL == "") $target = SERVER_URL;
			else $target = LOGIN_URL;
			if (substr($target, -1) == "/") $target = substr($target,0, (strlen($target)-1));
			if (LOGIN_URL == "") $target .= "/login.php";
			$mail_body .= "<a href=\"".$target."\">".$target."</a>";
			
			GmMail($newuser->email, str_replace("#SERVER_NAME#", SERVER_URL, $templang["mail04_subject"]), $mail_body, "", "", "", "", "", true);
			
			// Reset language to original page language
			$LANGUAGE = $oldlanguage;
			$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
			$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
			$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
			$WEEK_START	= $WEEK_START_array[$LANGUAGE];
			$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
			?>
			<table class="FactsTable">
			<tr><td class="ltr"><?php print GM_LANG_pwreqinfo;?></td></tr>
			</table>
			<?php
			WriteToLog("LoginRegister-&gt; Password request was sent to user: ".$user_name, "W", "S");
		}
		print "</div>";
		break;
	case "register" :
		$message = "";
		$user_name_false = false;
		if (isset($user_name)) {
			if (strlen($user_name)==0) {
				$message .= GM_LANG_enter_username."<br />";
				$user_name_false = true;
			}
			$u =& User::GetInstance($user_name);
			if (!$u->is_empty) {
				$message .= GM_LANG_duplicate_username."<br />";
				$user_name_false = true;
			}
			$alphabet = GetAlphabet();
			$alphabet .= "_-. ";
			$i = 1;
			while (strlen($user_name) > $i) {
				if (stristr($alphabet, $user_name{$i}) != TRUE){
					$user_name_false = true;
					$message .= GM_LANG_invalid_username."<br />";
					break;
				}
				$i++;
			}
		}
		else $user_name_false = true;

		if (isset($user_password01)&& strlen($user_password01)==0) {
			$message .= GM_LANG_enter_password."<br />";
			$user_password01_false = true;
		}
		else if (!isset($user_password01)) $user_password01_false = true;
		else if (isset($user_name) && isset($user_password01) && $user_name == $user_password01) {
			$user_password01_false = true;
			$message .= GM_LANG_user_password_same;
		}
		else $user_password01_false = false;

		if (isset($user_password02)&& strlen($user_password02)==0) {
			$message .= GM_LANG_confirm_password."<br />";
			$user_password02_false = true;
		}
		else if (!isset($user_password02)) $user_password02_false = true;
		else $user_password02_false = false;

		if (isset($user_password02) && isset($user_password02)) {
			if ($user_password01 != $user_password02) {
				$message .= GM_LANG_password_mismatch."<br />";
				$password_mismatch = true;
			}
			else $password_mismatch = false;
		}
		
		if (isset($user_password01)&& strlen($user_password01)<6) {
			$message .= GM_LANG_passwordlength."<br />";
			$user_password_length = true;
		}
		else if (!isset($user_password_length)) $user_password_length = false;
		else $user_password_length = true;
		
		if (isset($user_firstname)&& strlen($user_firstname)==0) $user_firstname_false = true;
		else if (!isset($user_firstname)) $user_firstname_false = true;
		else $user_firstname_false = false;
		
		if (isset($user_lastname)&& strlen($user_lastname)==0) $user_lastname_false = true;
		else if (!isset($user_lastname)) $user_lastname_false = true;
		else $user_lastname_false = false;
		
		if (isset($user_email)&& (strlen($user_email)==0 || !CheckEmailAddress($user_email))) $user_email_false = true;
		else if (!isset($user_email)) $user_email_false = true;
		else $user_email_false = false;
		if ($user_email_false && !$user_name_false) $message .= GM_LANG_invalid_email;
		
		if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) {
			if (isset($user_language)&& strlen($user_language)==0) $user_language_false = true;
			else if (!isset($user_language)) $user_language_false = true;
			else $user_language_false = false;
		}
		else $user_language_false = false;
		
		if (isset($user_comments)&& strlen($user_comments)==0) $user_comments_false = true;
		else if (!isset($user_comments)) $user_comments_false = true;
		else $user_comments_false = false;
		
		if (!$user_firstname_false && !$user_lastname_false && $user_firstname == $user_lastname) {
			$user_first_last_false = true;
			$message .= GM_LANG_first_last_same."<br />";
		}
		else $user_first_last_false = false;
		
		if ($user_name_false == false && $user_password01_false == false && $user_password02_false == false && $user_firstname_false == false && $user_lastname_false == false && $user_email_false == false && $user_language_false == false && $user_comments_false == false && $password_mismatch == false && $user_password_length == false && $user_first_last_false == false) $action = "registernew";
		else {
			PrintHeader("Genmod - " . GM_LANG_requestaccount);
			// Empty user array in case any details might be left
			// and faulty users are requested and created
			$user = array();

			?>
			<script language="JavaScript" type="text/javascript">
			<!--
			function checkform(frm) {
				nosub = false;
				if (frm.user_name.value == "") {
					alert("<?php print GM_LANG_enter_username; ?>");
					frm.user_name.focus();
					nosub = true;
				}
				if (nosub == false && frm.user_password01.value == "") {
					alert("<?php print GM_LANG_enter_password; ?>");
					frm.user_password01.focus();
					nosub = true;
				}
				if (nosub == false && frm.user_password02.value == "") {
					alert("<?php print GM_LANG_confirm_password; ?>");
					frm.user_password02.focus();
					nosub = true;
				}
				if (nosub == false && frm.user_password01.value != frm.user_password02.value) {
					alert("<?php print GM_LANG_password_mismatch; ?>");
					frm.user_password01.value = "";
					frm.user_password02.value = "";
					frm.user_password01.focus();
					nosub = true;
				}
				if (nosub == false && frm.user_password01.value.length < 6) {
					alert("<?php print GM_LANG_passwordlength; ?>");
					frm.user_password01.value = "";
					frm.user_password02.value = "";
					frm.user_password01.focus();
					nosub = true;
				}
				if (nosub == false && frm.user_firstname.value == "") {
					alert("<?php print GM_LANG_enter_fullname; ?>");
					frm.user_firstname.focus();
					nosub = true;
				}
				if (nosub == false && frm.user_lastname.value == "") {
					alert("<?php print GM_LANG_enter_fullname; ?>");
					frm.user_lastname.focus();
					nosub = true;
				}
				if (nosub == false && (frm.user_email.value == "" || frm.user_email.value.indexOf('@')==-1)) {
					alert("<?php print GM_LANG_enter_email; ?>");
					frm.user_email.focus();
					nosub = true;
				}
				if (nosub == false && frm.user_comments.value == "") {
					alert("<?php print GM_LANG_enter_comments; ?>");
					frm.user_comments.focus();
					nosub = true;
				}
				if (nosub == false) {
					if (frm.captchatext.value == "" || frmerrcp.value != "") {
						alert("<?php print GM_LANG_enter_captcha; ?>");
						frm.captchatext.focus();
						nosub = true;
					}
				}
				if (nosub == true) {
					return false;
				}
				return true;
			}
			var pastefield;
			function paste_id(value) {
				pastefield.value=value;
			}
			//-->
			</script>
			<div class="LoginPageContainer">
			<form name="registerform" id="registerform" method="post" action="login_register.php">
				<input type="hidden" name="action" value="register" />
				<input type="hidden" name="time" value="" />
				<table class="NavBlockTable RegisterPageTable">
				<?php $i = 1;?>
				<tr><td class="NavBlockHeader" colspan="2"><?php PrintHelpLink("register_info_0".GedcomConfig::$WELCOME_TEXT_AUTH_MODE."", "qm", "requestaccount"); print GM_LANG_requestaccount;?><?php if (strlen($message) > 0) print "<br /><span class=\"Warning\">".$message."</span>"; ?></td></tr>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("username_help", "qm", "username"); print GM_LANG_username;?></td><td class="NavBlockField"><input type="text" id="user_name" name="user_name" value="<?php if (!$user_name_false) print $user_name;?>" tabindex="<?php print $i;?>" onchange="sndReq('errus', 'checkuser', true, 'username', this.value);" /> * <span id="errus"></span></td></tr>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("edituser_password_help", "qm", "password"); print GM_LANG_password;?></td><td class="NavBlockField"><input type="password" name="user_password01" value="" tabindex="<?php print $i++;?>" /> *</td></tr>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("edituser_conf_password_help", "qm", "confirm");print GM_LANG_confirm;?></td><td class="NavBlockField"><input type="password" name="user_password02" value="" tabindex="<?php print $i++;?>" /> *</td></tr>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("new_user_firstname_help", "qm", "firstname");print GM_LANG_firstname;?></td><td class="NavBlockField"><input type="text" name="user_firstname" value="<?php if (!$user_firstname_false) print $user_firstname;?>" tabindex="<?php print $i++;?>" /> *</td></tr>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("new_user_lastname_help", "qm", "lastname");print GM_LANG_lastname;?></td><td class="NavBlockField"><input type="text" name="user_lastname" value="<?php if (!$user_lastname_false) print $user_lastname;?>" tabindex="<?php print $i++;?>" /> *</td></tr>
				<?php
				if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) {
					print "<tr><td class=\"NavBlockLabel\">";
					PrintHelpLink("edituser_change_lang_help", "qm", "change_lang");
					print GM_LANG_change_lang;
					print "</td><td class=\"NavBlockField\"><select name=\"user_language\" tabindex=\"".($i++)."\">";
					if (isset($user_language) && !$user_language_false) $thislang = $user_language;
					else $thislang = $LANGUAGE;
					foreach ($gm_language as $key => $value) {
						if ($language_settings[$key]["gm_lang_use"]) {
							print "\n\t\t\t<option value=\"$key\"";
							if ($key == $thislang) print " selected=\"selected\"";
							print ">" . constant("GM_LANG_lang_name_".$key) . "</option>";
					    }
					}
					print "</select>\n\t\t";
					print "</td></tr>\n";
				}
				?>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("edituser_email_help", "qm", "emailadress");print GM_LANG_emailadress;?></td><td class="NavBlockField"><input type="text" size="30" name="user_email" value="<?php if (!$user_email_false) print $user_email;?>" tabindex="<?php print $i++;?>" onchange="sndReq('errem', 'checkemail', true, 'email', this.value);" /> * <span id="errem"></span></td></tr>
				<?php if (GedcomConfig::$MUST_AUTHENTICATE && $SHOW_LIVING_NAMES>=$PRIV_PUBLIC) { ?>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("register_gedcomid_help", "qm", "gedcomid");print GM_LANG_gedcomid;?></td><td class="NavBlockField" valign="top" ><input type="text" size="10" name="user_gedcomid" id="user_gedcomid" value="" tabindex="<?php print $i++;?>" /><?php LinkFunctions::PrintFindIndiLink("user_gedcomid",""); ?></td></tr>
				<?php } ?>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("register_comments_help", "qm", "comments");print GM_LANG_comments;?></td><td class="NavBlockField" valign="top" ><textarea cols="50" rows="5" name="user_comments" tabindex="<?php print $i++;?>"><?php if (!$user_comments_false) print $user_comments;?></textarea> *</td></tr>
				<tr><td class="NavBlockLabel"><?php PrintHelpLink("register_captcha_help", "qm", "comments"); CaptchaFunctions::initCaptcha();?><img src="imageflush.php?image_type=png" /></td><td class="NavBlockField" valign="top" ><input type="text" name="captchatext" id="captchatext" value="" tabindex="<?php print $i++;?>" onchange="sndReq('errcp', 'check_captcha', true, 'captcha', this.value);" /> * <span id="errcp"></span></td></tr>
				<tr><td class="NavBlockFooter" colspan="2"><input type="submit" value="<?php print GM_LANG_requestaccount; ?>" tabindex="<?php print $i++;?>" /></td></tr>
				<tr><td align="left" colspan="2" ><?php print GM_LANG_mandatory;?></td></tr>
				</table>
			</form>
			</div>
			<script language="JavaScript" type="text/javascript">
			<!--
			
			const form = document.getElementById('registerform');
			form.addEventListener('submit', (event) => {
				// stop form submission
				sndReq('errcp', 'check_captcha', true, 'captcha', document.getElementById('captchatext').value);
				var success = checkform(form);
				if (success === false) {
					event.preventDefault();
				}
			});
				document.registerform.user_name.focus();
			//-->
			</script>
			<?php
			break;
		}
	case "registernew" :
		$QUERY_STRING = "";
		if (isset($user_name) && isset($captchatext) && isset($_SESSION["phpcaptcha"]) && $captchatext == $_SESSION["phpcaptcha"]) {
			PrintHeader("Genmod - " . GM_LANG_registernew);
			print "<div class=\"LoginPageContainer\">";
			$user_created_ok = false;
				
			WriteToLog("LoginRegister-&gt; User registration requested for: ".$user_name, "I", "S");
			$user = new user();
			$user->username = $user_name;
			$user->firstname = $user_firstname;
			$user->lastname = $user_lastname;
			$user->email = $user_email;
			if (!isset($user_language)) $user_language = $LANGUAGE;
			$user->language = $user_language;
			$user->verified = "";
			$user->verified_by_admin = "";
			$user->reg_timestamp = date("U");
			srand((double)microtime()*1000000);
			$user->reg_hashcode = password_hash(rand(), PASSWORD_DEFAULT);
			$user->gedcomid = array();
			$user->rootid = array();
			$user->canedit = array();
			$user->theme = "";
			$user->loggedin = "N";
			$user->sessiontime = 0;
			$user->contactmethod = "messaging2";
			$user->default_tab = 9;
			if (!empty($user_gedcomid)) {
				$user->gedcomid[GedcomConfig::$GEDCOMID] = $user_gedcomid;
				$user->rootid[GedcomConfig::$GEDCOMID] = $user_gedcomid;
			}
			$user->password = password_hash($user_password01, PASSWORD_DEFAULT);
			if ((isset($canadmin)) && ($canadmin == "yes")) $user->canadmin = true;
			else $user->canadmin = false;
			$user->visibleonline = true;
			$user->editaccount = true;
			$user->comment = "";
			$user->comment_exp = "";
			$user->auto_accept = false;
			$au = UserController::AddUser($user, "added");
			if ($au) $user_created_ok = true;
			else {
			    print "<span class=\"Warning\">";
			    PrintText("user_create_error");
			    print "<br /></span>";
			}
			if ($user_created_ok) {
				// switch to the users language
				$oldlanguage = $LANGUAGE;
				$LANGUAGE = $user_language;
				if (isset($gm_language[$LANGUAGE])) $templang = LanguageFunctions::LoadEnglish(true, false, true);
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
				
				$mail_body = "";
				$mail_body .= str_replace("#user_fullname#", $user_firstname." ".$user_lastname, $templang["mail01_line01"]) . "\r\n\r\n";
				$mail_body .= str_replace("#user_email#", $user_email, str_replace("#SERVER_NAME#", SERVER_URL, $templang["mail01_line02"])) . "\r\n";
				$mail_body .= $templang["mail01_line03"] . "\r\n\r\n";
				// $mail_body .= GM_LANG_mail01_line04 . "\r\n\r\n";
				if (substr(SERVER_URL, -1) == "/") {
					$link = substr(SERVER_URL,0, (strlen(SERVER_URL)-1)). "/login_register.php?action=userverify&user_name=".urlencode($user_name)."&user_hashcode=".urlencode($user->reg_hashcode);
					$mail_body .= "<a href=\"". $link . "\">".$templang["mail01_line04"]."</a>\r\n";
				}
				else {
					$link = SERVER_URL. "/login_register.php?action=userverify&user_name=".urlencode($user_name)."&user_hashcode=".urlencode($user->reg_hashcode);
					$mail_body .= "<a href=\"". $link . "\">".$templang["mail01_line04"]."</a>\r\n";
				}
				$mail_body .= $templang["username"] . " " . $user_name . "\r\n";
				//-- sending the password back to the user is a security risk
				//--$mail_body .= GM_LANG_password . " " . $user_password01 . "\r\n";
				$mail_body .= $templang["hashcode"] . " " . $user->reg_hashcode . "\r\n\r\n";
				$mail_body .= $templang["comments"].": " . $user_comments . "\r\n\r\n";
				$mail_body .= $templang["mail01_line05"] . "\r\n";
				$mail_body .= $templang["mail01_line06"] . "\r\n";
				
				/* Send a confirmation mail to the user */
				GmMail($user_email, str_replace("#SERVER_NAME#", SERVER_URL, GM_LANG_mail01_subject), $mail_body, "", "", "", "", "", true);
				
				// switch language to webmaster settings
				$admuser =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
				$LANGUAGE = $admuser->language;
				
				if (isset($gm_language[$LANGUAGE])) $templang = LanguageFunctions::LoadEnglish(true, false, true);	//-- load language file
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
				
				$mail_body = "";
				$mail_body .= $templang["mail02_line01"] . "\r\n\r\n";
				$mail_body .= str_replace("#SERVER_NAME#", SERVER_URL, $templang["mail02_line02"]) . "\r\n\r\n";
				$mail_body .= $templang["username"] . " " . $user_name . "\r\n";
				$mail_body .= $templang["firstname"] . " " . $user_firstname . "\r\n";
				$mail_body .= $templang["lastname"] . " " . $user_lastname . "\r\n\r\n";
				$mail_body .= $templang["comments"].": " . $user_comments . "\r\n\r\n";
				$mail_body .= $templang["mail02_line03"] . " <a href=\"".$link."\">".$link."</a>\r\n";
				if (SystemConfig::$REQUIRE_ADMIN_AUTH_REGISTRATION) {
					$mail_body .= $templang["mail02_line04"] . "\r\n";
				} 
				else {
					$mail_body .= $templang["mail02_line04a"] . "\r\n";
				}
				/* 2 lines below seem obsolete */
				$message = new Message();
				$message->to = GedcomConfig::$WEBMASTER_EMAIL;
				$message->from = $user_name;
				$message->from_email = $user_email;
				$message->from_name = $user_firstname.' '.$user_lastname;
				$message->subject = str_replace("#SERVER_NAME#", SERVER_URL, str_replace("#user_email#", $user_email, $templang["mail02_subject"]));
				$message->body = $mail_body;
				$message->created = $time;
				$message->method = GedcomConfig::$SUPPORT_METHOD;
				$message->no_from = true;
				/* Store a message for the admin in the database and send out the email */
				$message->AddMessage();
				
				// switch language back to earlier settings
				$LANGUAGE = $oldlanguage;
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
				?>
				<table class="LoginPageTable">
				<tr><td class="FactLabelCell"><?php print str_replace("#user_fullname#", $user_firstname." ".$user_lastname, GM_LANG_thankyou);?><br /><br />
				<?php
				if (SystemConfig::$REQUIRE_ADMIN_AUTH_REGISTRATION) print str_replace("#user_email#", $user_email, GM_LANG_pls_note06);
				else print str_replace("#user_email#", $user_email, GM_LANG_pls_note06a);
				?>
				</td></tr></table>
				<?php
			}
			print "</div>";
		}
		else {
			if (!isset($captchatext) || !isset($_SESSION["phpcaptcha"]) || $captchatext != $_SESSION["phpcaptcha"]) {
				header("Location: login_register.php?action=register");
				exit;
			}
			if (LOGIN_URL == "") header("Location: login.php");
			else header("Location: ".LOGIN_URL);
			header("Location: login.php");
			exit;
		}			
		break;
	case "userverify" :
		if (!isset($user_name)) $user_name = "";
		if (!isset($user_hashcode)) $user_hashcode = "";
		PrintHeader("Genmod - " . GM_LANG_user_verify);
		print "<div class=\"LoginPageContainer\">";
		?><form name="verifyform" method="post" action="" onsubmit="t = new Date(); document.verifyform.time.value=t.toUTCString();">
		<input type="hidden" name="action" value="verify_hash" />
		<input type="hidden" name="time" value="" />
		<table class="NavBlockTable LoginPageTable">
			<tr><td class="NavBlockHeader" colspan="2"><?php PrintHelpLink("pls_note07", "qm", "user_verify"); print GM_LANG_user_verify;?></td></tr>
			<tr><td class="NavBlockLabel"><?php print GM_LANG_username; ?></td><td class="NavBlockField"><input type="text" name="user_name" value="<?php print $user_name; ?>" /></td></tr>
			<tr><td class="NavBlockLabel"><?php print GM_LANG_password; ?></td><td class="NavBlockField"><input type="password" name="user_password" value="" /></td></tr>
			<tr><td class="NavBlockLabel"><?php print GM_LANG_hashcode; ?></td><td class="NavBlockField"><input type="text" name="user_hashcode" value="<?php print $user_hashcode; ?>" /></td></tr>
			<tr><td class="NavBlockFooter" colspan="2"><input type="submit" value="<?php print GM_LANG_send; ?>" /></td></tr>
		</table>
		</form>
		</div>
		<script language="JavaScript" type="text/javascript">
		<!--
			document.verifyform.user_password.focus();
		//-->
		</script>
		<?php
		break;
	case "verify_hash" :
  		$QUERY_STRING = "";
		if (!isset($user_hashcode)) $user_hashcode = "";
		WriteToLog("LoginRegister-&gt; User attempted to verify hashcode: ".$user_name, "I", "S");
		PrintHeader("Genmod - " . GM_LANG_user_verify);# <-- better verification of authentication code
		print "<div class=\"LoginPageContainer\">";
		print "<table class=\"NavBlockTable LoginPageTable\">";
		print "<tr><td class=\"NavBlockHeader\">".GM_LANG_user_verify."</td></tr>";
		print "<tr><td class=\"NavBlockLabel\">";
		print str_replace("#user_name#", $user_name, GM_LANG_pls_note08);
		$user =& User::GetInstance($user_name);
		if (!$user->is_empty) {
			$pw_ok = password_verify($user_password, $user->password);
			$hc_ok = ($user_hashcode == $user->reg_hashcode);
			if (($pw_ok) and ($hc_ok)) {
				$newuser = CloneObj($user);
				$olduser = CloneObj($user);
				UserController::DeleteUser($user_name, "verified");
				$newuser->verified = "Y";
				$newuser->reg_timestamp = date("U");
				$newuser->hashcode = "";
				if (!SystemConfig::$REQUIRE_ADMIN_AUTH_REGISTRATION) $newuser->verified_by_admin = "Y";
				UserController::AddUser($newuser, "verified");
				// switch language to webmaster settings
				$admuser =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
				$oldlanguage = $LANGUAGE;
				$LANGUAGE = $admuser->language;
				if (isset($gm_language[$LANGUAGE])) $templang = LanguageFunctions::LoadEnglish(true, false, true);
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
				$mail_body = "";
				$mail_body .= $templang["mail03_line01"] . "\r\n\r\n";
				$mail_body .= str_replace("#newuser[username]# ( #newuser[fullname]# )", $newuser->username . " (" . $newuser->firstname." ".$newuser->lastname . ") ", $templang["mail03_line02"]) . "\r\n\r\n";
				if (SystemConfig::$REQUIRE_ADMIN_AUTH_REGISTRATION) $mail_body .= $templang["mail03_line03"] . "\r\n";
				else $mail_body .= $templang["mail03_line03a"] . "\r\n";
				$reflink = SERVER_URL;
				if (substr(SERVER_URL, -1) != "/") $reflink .= "/";
				$reflink .= "useradmin.php?action=edituser&username=".urlencode($newuser->username);
				$mail_body .= "<a href=\"".$reflink."\">".$reflink."</a>\r\n";
				$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
				$headers = "From: Genmod-noreply@".$host;
				$message = new Message();
				$message->to = GedcomConfig::$WEBMASTER_EMAIL;
				$message->from = "Genmod-noreply@".$host;
				$message->subject = str_replace("#SERVER_NAME#", SERVER_URL, $templang["mail03_subject"]);
				$message->body = $mail_body;
				$message->created = $time;
				$message->method = GedcomConfig::$SUPPORT_METHOD;
				$message->no_from = true;
				$message->AddMessage(true);
				
				// Reset language to original page language
				$LANGUAGE = $oldlanguage;
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
				
				print "<br /><br />".GM_LANG_pls_note09."<br /><br />";
				if (SystemConfig::$REQUIRE_ADMIN_AUTH_REGISTRATION) print GM_LANG_pls_note10;
				else print GM_LANG_pls_note10a;
				print "<br /><br /></td></tr>";
			} 
			else {
				print "<br /><br />";
				print "<span class=\"Warning\">";
				print GM_LANG_data_incorrect;
				print "</span><br /><br /></td></tr>";
			}
		}
		else {
			print "<br /><br />";
			print "<span class=\"Warning\">";
			print GM_LANG_user_not_found;
			print "</span><br /><br /></td></tr>";
		}
		print "</table>";
		print "</div>";
		break;
	default :
  		if (stristr(SERVER_URL, $url)) $url = SERVER_URL;
		header("Location: $url"); 
		break;
}

PrintFooter();
?>
