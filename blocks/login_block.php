<?php
/**
 * Login Block
 *
 * This block prints a form that will allow a user to login
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
 * @subpackage Blocks
 * @version $Id$
 */

$GM_BLOCKS["print_login_block"]["name"]        	= GM_LANG_login_block;
$GM_BLOCKS["print_login_block"]["descr"]        = "login_descr";
$GM_BLOCKS["print_login_block"]["type"]        	= "gedcom";
$GM_BLOCKS["print_login_block"]["canconfig"]	= false;
$GM_BLOCKS["print_login_block"]["rss"]     		= false;

/**
 * Print Login Block
 *
 * Prints a block allowing the user to login to the site directly from the portal
 */
function print_login_block($block = true, $config="", $side, $index) {
	global $pid, $command, $gm_user, $TEXT_DIRECTION, $GEDCOMS;
	
	if ((LOGIN_URL == "" && substr(SERVER_URL,0,5) != "https") || substr(LOGIN_URL,0,5) != "https") {
		if (!empty($gm_user->username)) return;
		if (LOGIN_URL == "") $login = "login.php";
		else $login = LOGIN_URL;
		print "<div id=\"login_block\" class=\"BlockContainer\">\n";
		print "<div class=\"BlockHeader\">";
		if (SystemConfig::$USE_REGISTRATION_MODULE) PrintHelpLink("index_login_register_help", "qm");
		else PrintHelpLink("index_login_help", "qm", "login");
		print GM_LANG_login;
		print "</div>";
		print "<div class=\"BlockContent width100 center $TEXT_DIRECTION\">";
		print "<form method=\"post\" action=\"$login\" name=\"loginform\" onsubmit=\"t = new Date(); document.loginform.usertime.value=t.getFullYear()+'-'+(t.getMonth()+1)+'-'+t.getDate()+' '+t.getHours()+':'+t.getMinutes()+':'+t.getSeconds(); return true;\">\n";
		print "<input type=\"hidden\" name=\"url\" value=\"index.php?command=$command&amp;\" />\n";
		print "<input type=\"hidden\" name=\"gedid\" value=\"";if (isset(GedcomConfig::$GEDCOMID)) print GedcomConfig::$GEDCOMID; print "\" />";
		print "<input type=\"hidden\" name=\"pid\" value=\"";if (isset($pid)) print $pid; print "\" />";
		print "<input type=\"hidden\" name=\"usertime\" value=\"\" />\n";
		print "<input type=\"hidden\" name=\"action\" value=\"login\" />\n";
		print "<label class=\"label_form\" for=\"username\">".GM_LANG_username."</label>";
		print "<input type=\"text\" id=\"username\" name=\"username\" tabindex=\"20\" /><br style=\"clear: left;\"/>";
		print "<label class=\"label_form\" for=\"password\">".GM_LANG_password."</label>";
		print "<input type=\"password\" id=\"password\" name=\"password\" tabindex=\"21\" /><br style=\"clear: left;\"/>";
		if (SystemConfig::$ALLOW_REMEMBER_ME) {
			print "<label class=\"label_form_remember\" for=\"remember\">";
			PrintHelpLink("remember_me_help", "qm", "remember_me");
			print GM_LANG_remember_me."</label>";
			print "<input class=\"input_form_remember\" type=\"checkbox\" tabindex=\"22\" id=\"remember\" name=\"remember\" value=\"yes\" ";
			if (!empty($_COOKIE["gm_rem"])) print "checked=\"checked\" ";
			print "/>";
			print "<br style=\"clear: left;\"/>\n";
		}
		print "<div class=\"width100 center\"><input type=\"submit\" id=\"submitlogin\"  tabindex=\"23\" value=\"".GM_LANG_login."\" />&nbsp;";
		print "</form>\n";
		print "<br />";
		if (SystemConfig::$USE_REGISTRATION_MODULE && count($GEDCOMS) > 0){
			PrintHelpLink("new_user_help", "qm", "no_account_yet");
			print GM_LANG_no_account_yet;
			print "<br />";
			print "<a href=\"login_register.php?action=register\">";
			print GM_LANG_requestaccount;
			print "</a>";
			print "<br /><br />";
			PrintHelpLink("new_password_help", "qm", "lost_password");
			print GM_LANG_lost_password;
			print "<br />";
			print "<a href=\"login_register.php?action=pwlost\">";
			print GM_LANG_requestpassword;
			print "</a>";
		}
		print "</div></div>";
		print "</div>";
	}
}
?>
