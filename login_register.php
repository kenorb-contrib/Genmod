<?php
/**
 * Register as a new User or request new password if it is lost
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
 * This Page Is Valid XHTML 1.0 Transitional! > 29 August 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: login_register.php,v 1.5 2006/02/19 18:40:23 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
require $GM_BASE_DIRECTORY.$confighelpfile["english"];
if (file_exists($GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE];

// Remove slashes
if (isset($user_firstname)) $user_firstname = stripslashes($user_firstname);
if (isset($user_lastname)) $user_lastname = stripslashes($user_lastname);

$message="";
if (!isset($action)) $action = "";
if (!isset($url)) $url = "index.php";
switch ($action) {
	case "pwlost" :
  		print_header("Genmod - " . $gm_lang["lost_pw_reset"]);	?>
  		<script language="JavaScript" type="text/javascript">
		<!--
		function checkform(frm)
		{
		/*
		if (frm.user_email.value == "")
		{
		 alert("<?php print $gm_lang["enter_email"]; ?>");
		 frm.user_email.focus();
		 return false;
		}
		*/
		return true;
		}
		//-->
		</script>
		<div class="center">
			<form name="requestpwform" action="login_register.php" method="post" onsubmit="t = new Date(); document.requestpwform.time.value=t.toUTCString(); return checkform(this);">
			<input type="hidden" name="time" value="" />
			<input type="hidden" name="action" value="requestpw" />
			<span class="warning"><?php print $message?></span>
			<table class="center facts_table width20">
			<tr><td class="topbottombar" colspan="2"><?php print_help_link("pls_note11", "qm", "lost_pw_reset"); print $gm_lang["lost_pw_reset"];?></td></tr>
			 <tr><td class="shade2 ltr"><?php print $gm_lang["username"]?></td><td class="shade1 ltr"><input type="text" name="user_name" value="" /></td></tr>
			 <tr><td class="topbottombar" colspan="2"><input type="submit" value="<?php print $gm_lang["lost_pw_reset"]; ?>" /></td></tr>
		    </table>
		  </form>
		</div>
		<script language="JavaScript" type="text/javascript">
			document.requestpwform.user_name.focus();
		</script>
		<?php
		break;
	case "requestpw" :
		$QUERY_STRING = "";
		if (!isset($user_name)) $user_name = "";
		print_header("Genmod - " . $gm_lang["lost_pw_reset"]);
		print "<div class=\"center\">";
		$newuser = getUser($user_name);
		if ($newuser==false || empty($newuser["email"])) {
			print "<span class=\"warning\">";
			print_text("user_not_found");
			print "</span><br />";
		}
		else {
			$user_new_pw = md5 (uniqid (rand()));
			$newuser = getUser($user_name);
			$olduser = $newuser;
			deleteUser($user_name, "reqested new password for");
			
			$newuser["password"] = crypt($user_new_pw, $user_new_pw);
			$newuser["pwrequested"] = "1";
			//$newuser["reg_timestamp"] = date("U");
			addUser($newuser, "reqested new password for");

			// switch language to user settings
			$oldlanguage = $LANGUAGE;
			$LANGUAGE = $newuser["language"];
			if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
			$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
			$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
			$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
			$WEEK_START	= $WEEK_START_array[$LANGUAGE];
			$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
			
			$mail_body = "";
			$mail_body .= str_replace("#user_fullname#", $newuser["firstname"]." ".$newuser["lastname"], $gm_lang["mail04_line01"]) . "\r\n\r\n";
			$mail_body .= $gm_lang["mail04_line02"] . "\r\n\r\n";
			$mail_body .= $gm_lang["username"] . " " . $newuser["username"] . "\r\n";
			$mail_body .= $gm_lang["password"] . " " . $user_new_pw . "\r\n";
			$mail_body .= $gm_lang["mail04_line03"] . "\r\n";
			$mail_body .= $gm_lang["mail04_line04"] . "\r\n";
			$mail_body .= print_text("mail04_line05", 0, 1) . "\r\n\r\n";
			
			if (substr($SERVER_URL, -1) == "/"){
				$target = substr($SERVER_URL,0, (strlen($SERVER_URL)-1));
				$mail_body .= "<a href=\"".$target."\">".$target."</a>";
			}
			else {
				// $mail_body .= $SERVER_URL;
				$mail_body .= "<a href=\"".$SERVER_URL."\">".$SERVER_URL."</a>";
			}
			
			$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
			$headers = "From: genmod-noreply@".$host;
			gmMail($newuser["email"], str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["mail04_subject"]), $mail_body, $headers);
			
			// Reset language to original page language
			$LANGUAGE = $oldlanguage;
			if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
			$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
			$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
			$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
			$WEEK_START	= $WEEK_START_array[$LANGUAGE];
			$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
			?>
			<table class="center facts_table">
			<tr><td class="ltr"><?php print str_replace("#user[email]#", $newuser["email"], $gm_lang["pwreqinfo"]);?></td></tr>
			</table>
			<?php
			WriteToLog("Password request was sent to user: ".$user_name, "W", "S");
		}
		print "</div>";
		break;
	case "register" :
		$message = "";
		if (isset($user_name)&& strlen($user_name)==0) {
			$message .= $gm_lang["enter_username"]."<br />";
			$user_name_false = true;
		}
		else if (!isset($user_name)) $user_name_false = true;
		else $user_name_false = false;

		if (isset($user_password01)&& strlen($user_password01)==0) {
			$message .= $gm_lang["enter_password"]."<br />";
			$user_password01_false = true;
		}
		else if (!isset($user_password01)) $user_password01_false = true;
		else $user_password01_false = false;

		if (isset($user_password02)&& strlen($user_password02)==0) {
			$message .= $gm_lang["confirm_password"]."<br />";
			$user_password02_false = true;
		}
		else if (!isset($user_password02)) $user_password02_false = true;
		else $user_password02_false = false;

		if (isset($user_password02) && isset($user_password02)) {
			if ($user_password01 != $user_password02) {
				$message .= $gm_lang["password_mismatch"]."<br />";
				$password_mismatch = true;
			}
			else $password_mismatch = false;
		}
		
		if (isset($user_password01)&& strlen($user_password01)<6) {
			$message .= $gm_lang["passwordlength"]."<br />";
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
		
		if (isset($user_email)&& strlen($user_email)==0) $user_email_false = true;
		else if (!isset($user_email)) $user_email_false = true;
		else $user_email_false = false;
		
		if (isset($user_language)&& strlen($user_language)==0) $user_language_false = true;
		else if (!isset($user_language)) $user_language_false = true;
		else $user_language_false = false;
		
		if (isset($user_comments)&& strlen($user_comments)==0) $user_comments_false = true;
		else if (!isset($user_comments)) $user_comments_false = true;
		else $user_comments_false = false;
		
		if ($user_name_false == false && $user_password01_false == false && $user_password02_false == false && $user_firstname_false == false && $user_lastname_false == false && $user_email_false == false && $user_language_false == false && $user_comments_false == false && $password_mismatch == false && $user_password_length == false) $action = "registernew";
		else {
			print_header("Genmod - " . $gm_lang["requestaccount"]);
			// Empty user array in case any details might be left
			// and faulty users are requested and created
			$user = array();

			?>
			<script language="JavaScript" type="text/javascript">
			<!--
			function checkform(frm) {
				if (frm.user_name.value == "") {
				    alert("<?php print $gm_lang["enter_username"]; ?>");
				    frm.user_name.focus();
				    return false;
				}
				if (frm.user_password01.value == "") {
				    alert("<?php print $gm_lang["enter_password"]; ?>");
				    frm.user_password01.focus();
				    return false;
				}
				if (frm.user_password02.value == "") {
				    alert("<?php print $gm_lang["confirm_password"]; ?>");
				    frm.user_password02.focus();
				    return false;
				}
			    	if (frm.user_password01.value != frm.user_password02.value) {
					alert("<?php print $gm_lang["password_mismatch"]; ?>");
					frm.user_password01.value = "";
					frm.user_password02.value = "";
					frm.user_password01.focus();
					return false;
				}
				if (frm.user_password01.value.length < 6) {
					 alert("<?php print $gm_lang["passwordlength"]; ?>");
					 frm.user_password01.value = "";
					 frm.user_password02.value = "";
					 frm.user_password01.focus();
					 return false;
				}
				if (frm.user_firstname.value == "") {
					 alert("<?php print $gm_lang["enter_fullname"]; ?>");
					 frm.user_firstname.focus();
					 return false;
				}
				if (frm.user_lastname.value == "") {
					 alert("<?php print $gm_lang["enter_fullname"]; ?>");
					 frm.user_lastname.focus();
					 return false;
				}
				if ((frm.user_email.value == "")||(frm.user_email.value.indexOf('@')==-1)) {
					 alert("<?php print $gm_lang["enter_email"]; ?>");
					 frm.user_email.focus();
					 return false;
				}
				if (frm.user_comments.value == "") {
					alert("<?php print $gm_lang["enter_comments"]; ?>");
					frm.user_comments.focus();
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
			<div class="center">
			<form name="registerform" method="post" action="login_register.php" onsubmit="t = new Date(); document.registerform.time.value=t.toUTCString(); return checkform(this);">
				<input type="hidden" name="action" value="register" />
				<input type="hidden" name="time" value="" />
				<table class="center facts_table width20">
				<?php $i = 1;?>
				<tr><td class="topbottombar" colspan="2"><?php print_help_link("register_info_0".$WELCOME_TEXT_AUTH_MODE."", "qm", "requestaccount"); print $gm_lang["requestaccount"];?><?php if (strlen($message) > 0) print $message; ?></td></tr>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("username_help", "qm", "username"); print $gm_lang["username"];?></td><td class="shade1 ltr"><input type="text" name="user_name" value="<?php if (!$user_name_false) print $user_name;?>" tabindex="<?php print $i;?>" /> *</td></tr>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("edituser_password_help", "qm", "password"); print $gm_lang["password"];?></td><td class="shade1 ltr"><input type="password" name="user_password01" value="" tabindex="<?php print $i++;?>" /> *</td></tr>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("edituser_conf_password_help", "qm", "confirm");print $gm_lang["confirm"];?></td><td class="shade1 ltr"><input type="password" name="user_password02" value="" tabindex="<?php print $i++;?>" /> *</td></tr>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("new_user_firstname_help", "qm", "firstname");print $gm_lang["firstname"];?></td><td class="shade1 ltr"><input type="text" name="user_firstname" value="<?php if (!$user_firstname_false) print $user_firstname;?>" tabindex="<?php print $i++;?>" /> *</td></tr>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("new_user_lastname_help", "qm", "lastname");print $gm_lang["lastname"];?></td><td class="shade1 ltr"><input type="text" name="user_lastname" value="<?php if (!$user_lastname_false) print $user_lastname;?>" tabindex="<?php print $i++;?>" /> *</td></tr>
				<?php
				if ($ENABLE_MULTI_LANGUAGE) {
					print "<tr><td class=\"shade2 ltr\">";
					print_help_link("edituser_change_lang_help", "qm", "change_lang");
					print $gm_lang["change_lang"];
					print "</td><td class=\"shade1 ltr\"><select name=\"user_language\" tabindex=\"".($i++)."\">";
					foreach ($gm_language as $key => $value) {
						if ($language_settings[$key]["gm_lang_use"]) {
						print "\n\t\t\t<option value=\"$key\"";
						if (!$user_language_false) print " selected=\"selected\"";
						else if ($key == $LANGUAGE) print " selected=\"selected\"";
						print ">" . $gm_lang[$key] . "</option>";
					    }
					}
					print "</select>\n\t\t";
					print "</td></tr>\n";
				}
				?>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("edituser_email_help", "qm", "emailadress");print $gm_lang["emailadress"];?></td><td class="shade1 ltr"><input type="text" size="30" name="user_email" value="<?php if (!$user_email_false) print $user_email;?>" tabindex="<?php print $i++;?>" /> *</td></tr>
				<?php if ($REQUIRE_AUTHENTICATION && $SHOW_LIVING_NAMES>=$PRIV_PUBLIC) { ?>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("register_gedcomid_help", "qm", "gedcomid");print $gm_lang["gedcomid"];?></td><td class="shade1 ltr" valign="top" ><input type="text" size="10" name="user_gedcomid" id="user_gedcomid" value="" tabindex="<?php print $i++;?>" /><?php print_findindi_link("user_gedcomid",""); ?></td></tr>
				<?php } ?>
				<tr><td class="shade2 nowrap ltr"><?php print_help_link("register_comments_help", "qm", "comments");print $gm_lang["comments"];?></td><td class="shade1 ltr" valign="top" ><textarea cols="50" rows="5" name="user_comments" tabindex="<?php print $i++;?>"><?php if (!$user_comments_false) print $user_comments;?></textarea> *</td></tr>
				<tr><td class="topbottombar" colspan="2"><input type="submit" value="<?php print $gm_lang["requestaccount"]; ?>" tabindex="<?php print $i++;?>" /></td></tr>
				<tr><td align="left" colspan="2" ><?php print $gm_lang["mandatory"];?></td></tr>
				</table>
			</form>
			</div>
			<script language="JavaScript" type="text/javascript">
				document.registerform.user_name.focus();
			</script>
			<?php
			break;
		}
	case "registernew" :
		$QUERY_STRING = "";
		if (isset($user_name)) {
			print_header("Genmod - " . $gm_lang["registernew"]);
			print "<div class=\"center\">";
			$alphabet = getAlphabet();
			$alphabet .= "_-. ";
			$i = 1;
			$pass = TRUE;
			while (strlen($user_name) > $i) {
				if (stristr($alphabet, $user_name{$i}) != TRUE){
					$pass = FALSE;
					break;
				}
				$i++;
			}
			if ($pass == TRUE){
				$user_created_ok = false;
				
				WriteToLog("User registration requested for: ".$user_name, "I", "S");
				
				if (getUser($user_name)!== false) {
					print "<span class=\"warning\">";
					print_text("duplicate_username");
					print "</span><br /><br />";
				}
				else if ($user_password01 == $user_password02) {
					$user = array();
					$user["username"] = $user_name;
					$user["firstname"] = $user_firstname;
					$user["lastname"] = $user_lastname;
					$user["email"] = $user_email;
					if (!isset($user_language)) $user_language = $LANGUAGE;
					$user["language"] = $user_language;
					$user["verified"] = "";
					$user["verified_by_admin"] = "";
					$user["pwrequested"] = "";
					$user["reg_timestamp"] = date("U");
					srand((double)microtime()*1000000);
					$user["reg_hashcode"] = crypt(rand(), $user_password01);
					$user["gedcomid"] = array();
					$user["rootid"] = array();
					$user["canedit"] = array();
					$user["theme"] = "";
					$user["loggedin"] = "N";
					$user["sessiontime"] = 0;
					$user["contactmethod"] = "messaging2";
					$user["default_tab"] = $GEDCOM_DEFAULT_TAB;
					if (!empty($user_gedcomid)) {
						$user["gedcomid"][$GEDCOM] = $user_gedcomid;
						$user["rootid"][$GEDCOM] = $user_gedcomid;
					}
					$user["password"] = crypt($user_password01, $user_password01);
					if ((isset($canadmin)) && ($canadmin == "yes")) $user["canadmin"] = true;
					else $user["canadmin"] = false;
					$user["visibleonline"] = true;
					$user["editaccount"] = true;
					$user["comment"] = "";
					$user["comment_exp"] = "";
					$user["auto_accept"] = false;
					$au = addUser($user, "added");
					if ($au) $user_created_ok = true;
					else {
					    print "<span class=\"warning\">";
					    print_text("user_create_error");
					    print "<br /></span>";
					}
				}
				else {
					print "<span class=\"warning\">";
					print_text("password_mismatch");
					print "</span><br />";
				}
				if ($user_created_ok) {
					// switch to the users language
					$oldlanguage = $LANGUAGE;
					$LANGUAGE = $user_language;
					if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
					$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
					$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
					$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
					$WEEK_START	= $WEEK_START_array[$LANGUAGE];
					$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
					
					$mail_body = "";
					$mail_body .= str_replace("#user_fullname#", $user_firstname." ".$user_lastname, $gm_lang["mail01_line01"]) . "\r\n\r\n";
					$mail_body .= str_replace("#user_email#", $user_email, str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["mail01_line02"])) . "\r\n";
					$mail_body .= $gm_lang["mail01_line03"] . "\r\n\r\n";
					$mail_body .= $gm_lang["mail01_line04"] . "\r\n\r\n";
					if (substr($SERVER_URL, -1) == "/") {
						$mail_body .= substr($SERVER_URL,0, (strlen($SERVER_URL)-1)). "/login_register.php?action=userverify&user_name=".urlencode($user_name)."&user_hashcode=".urlencode($user["reg_hashcode"]) . "\r\n";
					}
					else {
						$mail_body .= $SERVER_URL. "/login_register.php?action=userverify&user_name=".urlencode($user_name)."&user_hashcode=".urlencode($user["reg_hashcode"]) . "\r\n";
					}
					$mail_body .= $gm_lang["username"] . " " . $user_name . "\r\n";
					//-- sending the password back to the user is a security risk
					//--$mail_body .= $gm_lang["password"] . " " . $user_password01 . "\r\n";
					$mail_body .= $gm_lang["hashcode"] . " " . $user["reg_hashcode"] . "\r\n\r\n";
					$mail_body .= $gm_lang["comments"].": " . $user_comments . "\r\n\r\n";
					$mail_body .= $gm_lang["mail01_line05"] . "\r\n";
					$mail_body .= $gm_lang["mail01_line06"] . "\r\n";
					$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
					$headers = "From: Genmod-noreply@".$host;
					gmMail($user_email, str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["mail01_subject"]), $mail_body, $headers);
					
					// switch language to webmaster settings
					$admuser = getuser($WEBMASTER_EMAIL);
					$LANGUAGE = $admuser["language"];
					if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
					$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
					$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
					$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
					$WEEK_START	= $WEEK_START_array[$LANGUAGE];
					$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
					
					$mail_body = "";
					$mail_body .= $gm_lang["mail02_line01"] . "\r\n\r\n";
					$mail_body .= str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["mail02_line02"]) . "\r\n\r\n";
					$mail_body .= $gm_lang["username"] . " " . $user_name . "\r\n";
					$mail_body .= $gm_lang["firstname"] . " " . $user_firstname . "\r\n";
					$mail_body .= $gm_lang["lastname"] . " " . $user_lastname . "\r\n\r\n";
					$mail_body .= $gm_lang["comments"].": " . $user_comments . "\r\n\r\n";
					$mail_body .= $gm_lang["mail02_line03"] . "\r\n";
					if ($REQUIRE_ADMIN_AUTH_REGISTRATION) {
						$mail_body .= $gm_lang["mail02_line04"] . "\r\n";
					} 
					else {
						$mail_body .= $gm_lang["mail02_line04a"] . "\r\n";
					}
					$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
					$headers = "From: Genmod-noreply@".$host;
					$message = array();
					$message["to"]=$WEBMASTER_EMAIL;
					$message["from"]=$user_email;
					$message["subject"] = str_replace("#SERVER_NAME#", $SERVER_URL, str_replace("#user_email#", $user_email, $gm_lang["mail02_subject"]));
					$message["body"] = $mail_body;
					$message["created"] = $time;
					$message["method"] = $SUPPORT_METHOD;
					$message["no_from"] = true;
					addMessage($message);
					
					// switch language back to earlier settings
					$LANGUAGE = $oldlanguage;
					if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) 	require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
					$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
					$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
					$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
					$WEEK_START	= $WEEK_START_array[$LANGUAGE];
					$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
					?>
					<table class="center facts_table">
					<tr><td class="ltr"><?php print str_replace("#user_fullname#", $user_firstname." ".$user_lastname, $gm_lang["thankyou"]);?><br /><br />
					<?php
					if ($REQUIRE_ADMIN_AUTH_REGISTRATION) print str_replace("#user_email#", $user_email, $gm_lang["pls_note06"]);
					else print str_replace("#user_email#", $user_email, $gm_lang["pls_note06a"]);
					?>
					</td></tr></table>
					<?php
				}
				print "</div>";
			}
			else {
				print "<span class=\"error\">".$gm_lang["invalid_username"]."</span><br />";
				print "<a href=\"javascript:history.back()\">".$gm_lang["back"]."</a><br />";
			}
		}
		else {
			header("Location: login.php");
			exit;
		}			
		break;
	case "userverify" :
		if (!isset($user_name)) $user_name = "";
		if (!isset($user_hashcode)) $user_hashcode = "";
		print_header("Genmod - " . $gm_lang["user_verify"]);
		print "<div class=\"center\">";
		?><form name="verifyform" method="post" action="" onsubmit="t = new Date(); document.verifyform.time.value=t.toUTCString();">
		<input type="hidden" name="action" value="verify_hash" />
		<input type="hidden" name="time" value="" />
		<table class="center facts_table width20">
			<tr><td class="topbottombar" colspan="2"><?php print_help_link("pls_note07", "qm", "user_verify"); print $gm_lang["user_verify"];?></td></tr>
			<tr><td class="shade2 ltr"><?php print $gm_lang["username"]; ?></td><td class="shade1 ltr"><input type="text" name="user_name" value="<?php print $user_name; ?>" /></td></tr>
			<tr><td class="shade2 ltr"><?php print $gm_lang["password"]; ?></td><td class="shade1 ltr"><input type="password" name="user_password" value="" /></td></tr>
			<tr><td class="shade2 ltr"><?php print $gm_lang["hashcode"]; ?></td><td class="facts_value ltr"><input type="text" name="user_hashcode" value="<?php print $user_hashcode; ?>" /></td></tr>
			<tr><td class="topbottombar" colspan="2"><input type="submit" value="<?php print $gm_lang["send"]; ?>" /></td></tr>
		</table>
		</form>
		</div>
		<script language="JavaScript" type="text/javascript">
			document.verifyform.user_name.focus();
		</script>
		<?php
		break;
	case "verify_hash" :
  		$QUERY_STRING = "";
		WriteToLog("User attempted to verify hashcode: ".$user_name, "I", "S");
		print_header("Genmod - " . $gm_lang["user_verify"]);# <-- better verification of authentication code
		print "<div class=\"center\">";
		print "<table class=\"center facts_table ltr\">";
		print "<tr><td class=\"topbottombar\">".$gm_lang["user_verify"]."</td></tr>";
		print "<tr><td class=\"shade1\">";
		print str_replace("#user_name#", $user_name, $gm_lang["pls_note08"]);
		$user = getUser($user_name);
		if ($user!==false) {
			$pw_ok = ($user["password"] == crypt($user_password, $user["password"]));
			$hc_ok = ($user["reg_hashcode"] == $user_hashcode);
			if (($pw_ok) and ($hc_ok)) {
				$newuser = $user;
				$olduser = $user;
				deleteUser($user_name, "verified");
				storeUsers();
				$newuser["verified"] = "yes";
				$newuser["pwrequested"] = "";
				$newuser["reg_timestamp"] = date("U");
				$newuser["hashcode"] = "";
				if (!$REQUIRE_ADMIN_AUTH_REGISTRATION) $newuser["verified_by_admin"] = "yes";
				addUser($newuser, "verified");
				// switch language to webmaster settings
				$admuser = getuser($WEBMASTER_EMAIL);
				$oldlanguage = $LANGUAGE;
				$LANGUAGE = $admuser["language"];
				if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
				$mail_body = "";
				$mail_body .= $gm_lang["mail03_line01"] . "\r\n\r\n";
				$mail_body .= str_replace("#newuser[username]# ( #newuser[fullname]# )", $newuser["username"] . " (" . $newuser["firstname"]." ".$newuser["lastname"] . ") ", $gm_lang["mail03_line02"]) . "\r\n\r\n";
				if ($REQUIRE_ADMIN_AUTH_REGISTRATION) $mail_body .= $gm_lang["mail03_line03"] . "\r\n";
				else $mail_body .= $gm_lang["mail03_line03a"] . "\r\n";
				$path = substr($SCRIPT_NAME, 0, strrpos($SCRIPT_NAME, "/"));
				$mail_body .= "http://".$_SERVER['SERVER_NAME'] . $path."/useradmin.php?action=edituser&username=" . urlencode($newuser["username"]) . "\r\n";
				$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
				$headers = "From: Genmod-noreply@".$host;
				$message = array();
				$message["to"]=$WEBMASTER_EMAIL;
				$message["from"]="Genmod-noreply@".$host;
				$message["subject"] = str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["mail03_subject"]);
				$message["body"] = $mail_body;
				$message["created"] = $time;
				$message["method"] = $SUPPORT_METHOD;
				$message["no_from"] = true;
				addMessage($message);
				
				// Reset language to original page language
				$LANGUAGE = $oldlanguage;
				if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
				
				print "<br /><br />".$gm_lang["pls_note09"]."<br /><br />";
				if ($REQUIRE_ADMIN_AUTH_REGISTRATION) print $gm_lang["pls_note10"];
				else print $gm_lang["pls_note10a"];
				print "<br /><br /></td></tr>";
			} 
			else {
				print "<br /><br />";
				print "<span class=\"warning\">";
				print $gm_lang["data_incorrect"];
				print "</span><br /><br /></td></tr>";
			}
		}
		else {
			print "<br /><br />";
			print "<span class=\"warning\">";
			print $gm_lang["user_not_found"];
			print "</span><br /><br /></td></tr>";
		}
		print "</table>";
		print "</div>";
		break;
	default :
  		if (stristr($SERVER_URL, $url)) $url = $SERVER_URL;
		header("Location: $url"); 
		break;
}

print_footer();
?>
