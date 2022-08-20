<?php
/**
 * Login Page
 *
 * Provides links for administrators to get to other administrative areas of the site
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
 * @subpackage Display
 * @version $Id: login.php 29 2022-07-17 13:18:20Z Boudewijn $
 */ 

/**
 * Inclusion of the configuration file
*/
require "config.php";
$message="";
if (!isset($action)) {
	$action="";
	$username="";
	$password="";
}

if (!isset($type)) $type = "full";

if (isset($url)) $url = html_entity_decode(urldecode($url));
if ($action=="login") {
	if (isset($_POST['username'])) $username = $_POST['username'];
	else $username="";
	if (isset($_POST['password'])) $password = $_POST['password'];
	else $password="";
	if (isset($_POST['remember'])) $remember = $_POST['remember'];
	else $remember = "no";
	$auth = UserController::AuthenticateUser($username, $password);
	if ($auth) {
		if (!empty($_POST["usertime"])) {
			$_SESSION["usertime"]=@strtotime($_POST["usertime"]);
		}
		else $_SESSION["usertime"]=time();
		$_SESSION["timediff"]=time()-$_SESSION["usertime"];
		$MyUserName = UserController::GetUserName();
		$MyUser =& User::GetInstance($MyUserName);
		if (isset($MyUser->language)) {
		  if (isset($_SESSION['CLANGUAGE']))$_SESSION['CLANGUAGE'] = $MyUser->language;
		  else if (isset($HTTP_SESSION_VARS['CLANGUAGE'])) $HTTP_SESSION_VARS['CLANGUAGE'] = $MyUser->language;
		}
		session_write_close();
		$url = preg_replace("/logout=1/", "", $url);
		if (stristr("http://".$url, SERVER_URL) === false && stristr("https://".$url, SERVER_URL) === false) $url = SERVER_URL . $url;
		else $url = SERVER_URL;
		if (( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) && !stristr($url, 'https')) {
			$url = preg_replace("/http/", "https", $url);
		} 
		if ($remember=="yes") setcookie("gm_rem", $username, time()+60*60*24*7);
		else setcookie("gm_rem", "", time()-60*60*24*7);
		header("Location: $url");
		exit;
	}
	else $message = GM_LANG_no_login;
}
else {
	// Check the DB layout
	DbLayer::CheckDBLayout();
	$tSERVER_URL = preg_replace(array("'https?://'", "'www.'", "'/$'"), array("","",""), SERVER_URL);
	$tLOGIN_URL = preg_replace(array("'https?://'", "'www.'", "'/$'"), array("","",""), LOGIN_URL);
	if (empty($url)) {
		if ((isset($_SERVER['HTTP_REFERER'])) && ((stristr($_SERVER['HTTP_REFERER'],$tSERVER_URL)!==false)||(stristr($_SERVER['HTTP_REFERER'],$tLOGIN_URL)!==false))) {
			$url = basename($_SERVER['HTTP_REFERER']);
			if (stristr($url, ".php")===false) {
				$url = SERVER_URL."index.php?command=gedcom&amp;gedid=".GedcomConfig::$GEDCOMID;
			}
		}
		else {
			if (isset($url)) {
				if (stristr($url,SERVER_URL)!==false) $url = $SERVER_URL;
			}
			else $url = SERVER_URL;
		}
	}
	else if (stristr($url, "index.php")&&!stristr($url, "command=")) {
		$url.="&amp;command=gedcom";
	}
}

if ($type=="full") PrintHeader(GM_LANG_login_head);
else PrintSimpleHeader(GM_LANG_login_head);
print "<div class=\"LoginPageContainer\">\n";

if ($_SESSION["cookie_login"]) {
	print "<div class=\"LoginPageText\">\n";
	PrintText("cookie_login_help");
	print "</div>\n";
}
if (GedcomConfig::$MUST_AUTHENTICATE) {
	print "<div class=\"LoginPageText\">";
	if (empty($help_message) || !isset($help_message)) {
		if (!empty(GedcomConfig::$GEDCOMID)) SwitchGedcom(GedcomConfig::$GEDCOMID);
		switch (GedcomConfig::$WELCOME_TEXT_AUTH_MODE){
			case "1":
				$help_message = "welcome_text_auth_mode_1";
				PrintText($help_message,0,0,false);
				break;
			case "2":
				 $help_message = "welcome_text_auth_mode_2";
				 PrintText($help_message,0,0,false);
				 break;
			case "3":
				 $help_message = "welcome_text_auth_mode_3";
				 PrintText($help_message,0,0,false);
				 break;
			case "4":
				 if (GedcomConfig::$WELCOME_TEXT_CUST_HEAD == "true"){
					 $help_message = "welcome_text_cust_head";
					 PrintText($help_message,0,0,false);
				 }
				 print GedcomConfig::$WELCOME_TEXT_AUTH_MODE_4;
				 break;
		}
	}
	else PrintText($help_message);
	print "</div>\n";
}
else {
	if (!empty($help_message) || isset($help_message)) {
		print "<div class=\"LoginPageText\">";
		PrintText($help_message);
		print "</div>\n";
	}
}
	?>
	<form name="loginform" method="post" action="<?php print LOGIN_URL; ?>" onsubmit="t = new Date(); document.loginform.usertime.value=t.getFullYear()+'-'+(t.getMonth()+1)+'-'+t.getDate()+' '+t.getHours()+':'+t.getMinutes()+':'+t.getSeconds(); return true;">
		<?php $i = 0;?>
		<input type="hidden" name="action" value="login" />
		<input type="hidden" name="url" value="<?php print htmlspecialchars($url); ?>" />
		<input type="hidden" name="gedid" value="<?php if (isset($gedid)) print $gedid; else print GedcomConfig::$GEDCOMID; ?>" />
		<input type="hidden" name="pid" value="<?php if (isset($pid)) print $pid; ?>" />
		<input type="hidden" name="type" value="<?php print $type; ?>" />
		<input type="hidden" name="usertime" value="" />
		<span class="Error"><b><?php print $message?></b></span>
		<!--table-->
		<table class="NavBlockTable LoginPageTable">
		  <tr><td class="NavBlockHeader" colspan="2"><?php print GM_LANG_login?></td></tr>
		  <tr>
		    <td class="NavBlockLabel LoginPageTableTextWidth <?php print $TEXT_DIRECTION; ?>"><?php PrintHelpLink("username_help", "qm", "username"); print GM_LANG_username?></td>
		    <td class="NavBlockField <?php print $TEXT_DIRECTION; ?>"><input type="text" tabindex="<?php $i++; print $i?>" name="username" value="<?php print $username?>" size="20" /></td>
		  </tr>
		  <tr>
		    <td class="NavBlockLabel <?php print $TEXT_DIRECTION; ?>"><?php PrintHelpLink("password_help", "qm", "password"); print GM_LANG_password?></td>
		    <td class="NavBlockField <?php print $TEXT_DIRECTION; ?>"><input type="password" tabindex="<?php $i++; print $i?>" name="password" size="20" /></td>
		  </tr>
		  <?php if (SystemConfig::$ALLOW_REMEMBER_ME) { ?>
		  <tr>
		  	<td class="NavBlockLabel <?php print $TEXT_DIRECTION; ?>"><?php PrintHelpLink("remember_me_help", "qm", "remember_me"); print GM_LANG_remember_me?></td>
		    <td class="NavBlockField <?php print $TEXT_DIRECTION; ?> "><input type="checkbox" tabindex="<?php $i++; print $i?>" name="remember" value="yes" <?php if (!empty($_COOKIE["gm_rem"])) print "checked=\"checked\""; ?> /></td>
		  </tr>
		  <?php } ?>
		  <tr>
		    <td colspan="2" class="NavBlockFooter">
		    <?php
		        if (GedcomConfig::$SHOW_CONTEXT_HELP) {
		          if (GedcomConfig::$MUST_AUTHENTICATE) {
		            PrintHelpLink("login_buttons_aut_help", "qm", "login");
		          }
		          else {
		            PrintHelpLink("login_buttons_help", "qm", "login");
		          }
		        }
		    ?>
		      <input type="submit" tabindex="<?php $i++; print $i?>" value="<?php print GM_LANG_login; ?>" />&nbsp;
		    </td>
		  </tr>
		</table>
</form><br /><br />
<?php
$sessname = session_name();
if (!isset($_COOKIE[$sessname]) && !isset($_COOKIE["gm_rem"])) print "<span class=\"Error\">".GM_LANG_cookie_message."</span><br /><br />";
if (SystemConfig::$USE_REGISTRATION_MODULE && count($GEDCOMS) > 0) {?>
	<table class="NavBlockTable LoginPageTable">
	<tr><td class="NavBlockHeader" colspan="2"><?php print GM_LANG_account_information;?></td></tr>
	<tr><td class="NavBlockLabel LoginPageTableTextWidth <?php print $TEXT_DIRECTION; ?>"><?php PrintHelpLink("new_user_help", "qm", "requestaccount"); print GM_LANG_no_account_yet;?></td>
	<td class="NavBlockField <?php print $TEXT_DIRECTION; ?>"><a href="login_register.php?action=register"><?php print GM_LANG_requestaccount;?></a></td></tr>
	<tr><td class="NavBlockLabel <?php print $TEXT_DIRECTION; ?>"><?php PrintHelpLink("new_password_help", "qm", "lost_password"); print GM_LANG_lost_password;?></td>
	<td class="NavBlockField <?php print $TEXT_DIRECTION; ?>"><a href="login_register.php?action=pwlost"><?php print GM_LANG_requestpassword;?></a></td></tr>
	</table>
<?php
}
print "</div><br /><br />";
?>
<script language="JavaScript" type="text/javascript">
<!--
	document.loginform.username.focus();
//-->
</script>
<?php
if ($type=="full") PrintFooter();
else PrintSimpleFooter();
?>
