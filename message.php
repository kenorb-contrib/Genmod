<?php
/**
 * Send a message to a user in the system
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
 * @package Genmod
 * @subpackage Admin
 * @version $Id: message.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($action)) $action="compose";

PrintSimpleHeader(GM_LANG_Genmod_message);

if (!isset($subject)) $subject = "";
if (!isset($url)) $url = "";
if (!isset($method)) $method="messaging2";
if (isset($body)) $body = stripslashes($body);
else $body = "";
if (!isset($from_name)) $from_name="";
if (!isset($from_email)) $from_email="";
$message = "";

if (empty($to)) {
	print "<span class=\"Error\">".GM_LANG_no_to_user."</span><br />";
	PrintSimpleFooter();
	exit;
}
if ($to=="all" && !$gm_user->userIsAdmin()) {
	print "<span class=\"Error\">".GM_LANG_no_to_user."</span><br />";
	PrintSimpleFooter();
	exit;
}

// NOTE: $_SESSION["good_to_send"] is used for preventing sending a message more than once
if (($action=="send")&&(isset($_SESSION["good_to_send"]))&&($_SESSION["good_to_send"]===true)) {
	$_SESSION["good_to_send"] = false;
	if (!empty($from_email)) $from = $from_email;
	$tuser =& User::GetInstance($from);
	if ($tuser->is_empty) {
		if (!CheckEmailAddress($from)) {
			$message = GM_LANG_invalid_email;
			$action="compose";
	    }
	}
	//-- check referer for possible spam attack
	if (!isset($_SERVER['HTTP_REFERER']) || stristr($_SERVER['HTTP_REFERER'],"message.php")===false) {
		$message = "Invalid page referer";
		WriteToLog('Message-&gt; Invalid page referer while trying to send a message. Possible spam attack.', 'W', 'S');
		$action="compose";
	}
	if ($action != "compose") {
		$toarray = array();
		$toarray[] = $to;
		if ($to == "all") {
			$toarray = array();
			$users = UserController::GetUsers();
			foreach($users as $indexval => $tuser) {
				if ($tuser->username != $gm_user->username) $toarray[] = $tuser->username;
			}
		}
		if ($to == "never_logged") {
			$toarray = array();
			$users = UserController::GetUsers();
			foreach($users as $indexval => $tuser) {
				if ($tuser->reg_timestamp > $tuser->sessiontime) {
					$toarray[] = $tuser->username;
				}
			}
		}
		if ($to == "last_6mo") {
			$toarray = array();
			$users = UserController::GetUsers();
			$sixmos = 60*60*24*30*6;	//-- timestamp for six months
			foreach($users as $indexval => $tuser) {
				if (time() - $tuser->sessiontime > $sixmos) {
					$toarray[] = $tuser->username;
				}
			}
		}
		$i = 0;
		foreach($toarray as $indexval => $to) {
			$message = null;
			$message = new Message();
			$message->to = $to;
			$message->from = $from;
			if (!empty($from_name)) {
				$message->from_name = $from_name;
				$message->from_email = $from_email;
			}
			$message->subject = $subject;
			$url = preg_replace("/".session_name()."=.*/", "", $url);
			$message->body = $body;
			$message->created = $time;
			$message->method = $method;
			$message->url = $url;
			if ($i>0) $message->no_from = true;
			if ($message->AddMessage()) {
				print GM_LANG_message_sent." - ";
				$touser =& User::GetInstance($to);
				if ($touser->username != $from) print $touser->firstname."&nbsp;".$touser->lastname."<br />";
				else print $to;
			}
			$i++;
		}
	}
}

if ($action=="compose") {
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
		function validateEmail(email) {
			if (typeof(email) == "undefined" || email.value.search("(.*)@(.*)")==-1) {
				alert('<?php print GM_LANG_invalid_email; ?>');
				email.focus();
				return false;
			}
			return checkForm(document.messageform);
		}
		function checkForm(frm) {
			if (typeof(frm.subject.value) == "undefined" || frm.subject.value=="") {
				alert('<?php print GM_LANG_enter_subject; ?>');
				frm.subject.focus();
				return false;
			}
			if (typeof(frm.body.value) == "undefined" || frm.body.value=="") {
				alert('<?php print GM_LANG_enter_body; ?>');
				frm.body.focus();
				return false;
			}
			return true;
		}
	//-->
	</script>
	<?php
	$username = $gm_user->username;
	print "<form name=\"messageform\" method=\"post\" action=\"message.php\" onsubmit=\"t = new Date(); document.messageform.time.value=t.toUTCString(); ";
	if (empty($username)) print "return validateEmail(document.messageform.from_email);";
	else print "return checkForm(document.messageform);";
	print "\">\n";
	print "<table class=\"NavBlockTable\">\n";
	print "<tr><td class=\"NavBlockHeader\" colspan=\"2\">".GM_LANG_message;
	if (!empty($message)) print "<br /><span class=\"Error\">".$message;
	print "</td></tr>";
	$_SESSION["good_to_send"] = true;
	if (empty($username)) {
		print "<tr><td class=\"NavBlockLabel\" colspan=\"2\">".GM_LANG_message_instructions."</td></tr>";
	}
	$touser =& User::GetInstance($to);
	$lang_temp = "lang_name_".$touser->language;
	if (!empty($touser->username)) {
		print "<tr><td class=\"NavBlockLabel\" colspan=\"2\">".str_replace("#TO_USER#", "<b>".$touser->firstname." ".$touser->lastname."</b>", GM_LANG_sending_to)."<br />";
		print str_replace("#USERLANG#", "<b>".constant("GM_LANG_".$lang_temp)."</b>", GM_LANG_preferred_lang)."</td></tr>\n";
	}

	if (empty($username)){
		print "<tr>";
			print "<td class=\"NavBlockLabel\">".GM_LANG_message_from_name."</td>";
			print "<td class=\"NavBlockField\"><input type=\"text\" name=\"from_name\" size=\"40\" value=\"$from_name\" /></td>";
		print "</tr>";
		print "<tr>";
			print "<td class=\"NavBlockLabel\">".GM_LANG_message_from."</td>";
			print "<td class=\"NavBlockField\"><input type=\"text\" name=\"from_email\" size=\"40\" value=\"$from_email\" onchange=\"sndReq('mailerr', 'checkemail', true, 'email', this.value);\" /> <span id=\"mailerr\"></span><br />".GM_LANG_provide_email."</td>";
		print "</tr>\n";
	}
	print "<tr>";
		print "<td class=\"NavBlockLabel\">".GM_LANG_message_subject."</td>";
		print "<td class=\"NavBlockField\">";
		if (!empty($username)){
			print "<input type=\"hidden\" name=\"from\" value=\"$username\"/>\n";
		}
		print "<input type=\"hidden\" name=\"action\" value=\"send\" />\n";
		print "<input type=\"hidden\" name=\"to\" value=\"$to\" />\n";
		print "<input type=\"hidden\" name=\"time\" value=\"\" />\n";
		print "<input type=\"hidden\" name=\"method\" value=\"$method\" />\n";
		print "<input type=\"hidden\" name=\"url\" value=\"$url\" />\n";
		print "<input type=\"text\" name=\"subject\" size=\"50\" value=\"".stripslashes($subject)."\" /></td>";
	print "</tr>\n";
	print "<tr>";
		print "<td class=\"NavBlockLabel\">".GM_LANG_message_body."</td>";
		print "<td class=\"NavBlockField\"><textarea name=\"body\" cols=\"50\" rows=\"7\">$body</textarea></td>";
	print "</tr>\n";
	if ($method=="messaging2") {
		print "<tr><td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\" colspan=\"2\">";
		PrintText("messaging2_help");
		print "</td></tr>";
	}
	print "<tr>";
		print "<td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\" value=\"".GM_LANG_send."\" /></td>";
	print "</tr>\n";
	print "</table>\n";
	print "</form>\n";
}
else if ($action=="delete") {
	if (MessageController::deleteMessage($id)) print GM_LANG_message_deleted;
}
print "<div class=\"CloseWindow\"><a href=\"#\" onclick=\"if (window.opener.refreshpage) window.opener.refreshpage(); window.close();\">".GM_LANG_close_window."</a></div>";

PrintSimpleFooter();
?>