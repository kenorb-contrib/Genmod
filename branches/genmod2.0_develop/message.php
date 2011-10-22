<?php
/**
 * Send a message to a user in the system
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
 * @package Genmod
 * @subpackage Admin
 * @version $Id$
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
			print "<center><br /><span class=\"Error\">".GM_LANG_invalid_email."</span>\n";
			print "<br /><br /></center>";
			$action="compose";
	    }
	}
	//-- check referer for possible spam attack
	if (!isset($_SERVER['HTTP_REFERER']) || stristr($_SERVER['HTTP_REFERER'],"message.php")===false) {
		print "<center><br /><span class=\"Error\">Invalid page referer.</span>\n";
		print "<br /><br /></center>";
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
	print '<span class="SubHeader">'.GM_LANG_message.'</span>';
	$_SESSION["good_to_send"] = true;
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
		function validateEmail(email) {
			if (email.value.search("(.*)@(.*)")==-1) {
				alert('<?php print GM_LANG_invalid_email; ?>');
				email.focus();
				return false;
			}
			return checkForm(document.messageform);
		}
		function checkForm(frm) {
			if (frm.subject.value=="") {
				alert('<?php print GM_LANG_enter_subject; ?>');
				document.messageform.subject.focus();
				return false;
			}
			if (frm.body.value=="") {
				alert('<?php print GM_LANG_enter_body; ?>');
				document.messageform.body.focus();
				return false;
			}
			return true;
		}
	//-->
	</script>
	<?php
	$username = $gm_user->username;
	if (empty($username)) {
		print "<br /><br />".GM_LANG_message_instructions;
	}
	print "<br /><form name=\"messageform\" method=\"post\" action=\"message.php\" onsubmit=\"t = new Date(); document.messageform.time.value=t.toUTCString(); ";
	if (empty($username)) print "return validateEmail(document.messageform.from_email);";
	else print "return checkForm(this);";
	print "\">\n";
	print "<table>\n";
	$touser =& User::GetInstance($to);
	$lang_temp = "lang_name_".$touser->language;
	if (!empty($touser->username)) {
		print "<tr><td></td><td>".str_replace("#TO_USER#", "<b>".$touser->firstname." ".$touser->lastname."</b>", GM_LANG_sending_to)."<br />";
		print str_replace("#USERLANG#", "<b>".constant("GM_LANG_".$lang_temp)."</b>", GM_LANG_preferred_lang)."</td></tr>\n";
	}

	if (empty($username)){
		print "<tr><td valign=\"top\" width=\"15%\" align=\"right\">".GM_LANG_message_from_name."</td>";
		print "<td><input type=\"text\" name=\"from_name\" size=\"40\" value=\"$from_name\" /></td></tr><tr><td valign=\"top\" align=\"right\">".GM_LANG_message_from."</td><td class=\"wrap\"><input type=\"text\" name=\"from_email\" size=\"40\" value=\"$from_email\" onchange=\"sndReq('mailerr', 'checkemail', 'email', this.value);\" /> <span id=\"mailerr\"></span><br />".GM_LANG_provide_email."<br /><br /></td></tr>\n";
	}
	print "<tr><td align=\"right\">".GM_LANG_message_subject."</td>";
	print "<td>";
	if (!empty($username)){
		print "<input type=\"hidden\" name=\"from\" value=\"$username\"/>\n";
	}
	print "<input type=\"hidden\" name=\"action\" value=\"send\" />\n";
	print "<input type=\"hidden\" name=\"to\" value=\"$to\" />\n";
	print "<input type=\"hidden\" name=\"time\" value=\"\" />\n";
	print "<input type=\"hidden\" name=\"method\" value=\"$method\" />\n";
	print "<input type=\"hidden\" name=\"url\" value=\"$url\" />\n";
	print "<input type=\"text\" name=\"subject\" size=\"50\" value=\"".stripslashes($subject)."\" /><br /></td></tr>\n";
	print "<tr><td valign=\"top\" align=\"right\">".GM_LANG_message_body."<br /></td><td><textarea name=\"body\" cols=\"50\" rows=\"7\">$body</textarea><br /></td></tr>\n";
	print "<tr><td></td><td><input type=\"submit\" value=\"".GM_LANG_send."\" /></td></tr>\n";
	print "</table>\n";
	print "</form>\n";
	if ($method=="messaging2") PrintText("messaging2_help");
}
else if ($action=="delete") {
	if (MessageController::deleteMessage($id)) print GM_LANG_message_deleted;
}
print "<center><br /><br /><a href=\"#\" onclick=\"if (window.opener.refreshpage) window.opener.refreshpage(); window.close();\">".GM_LANG_close_window."</a><br /></center>";

PrintSimpleFooter();
?>