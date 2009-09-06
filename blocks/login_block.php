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

$GM_BLOCKS["print_login_block"]["name"]        	= $gm_lang["login_block"];
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
	global $gm_lang, $GEDCOM, $GEDCOMS, $command, $SCRIPT_NAME, $QUERY_STRING, $USE_REGISTRATION_MODULE, $ALLOW_REMEMBER_ME, $gm_username, $TEXT_DIRECTION;
	if ((LOGIN_URL == "" && substr(SERVER_URL,0,5) != "https") || substr(LOGIN_URL,0,5) != "https") {
		$uname = $gm_username;
		if (!empty($uname)) return;
		if (LOGIN_URL == "") $login = "login.php";
		else $login = LOGIN_URL;
		print "<div id=\"login_block\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		if ($USE_REGISTRATION_MODULE) print_help_link("index_login_register_help", "qm");
		else print_help_link("index_login_help", "qm", "login");
		print $gm_lang["login"];
		print "</div>";
		print "<div class=\"blockcontent width100 center $TEXT_DIRECTION\">";
		print "<form method=\"post\" action=\"$login\" name=\"loginform\" onsubmit=\"t = new Date(); document.loginform.usertime.value=t.getFullYear()+'-'+(t.getMonth()+1)+'-'+t.getDate()+' '+t.getHours()+':'+t.getMinutes()+':'+t.getSeconds(); return true;\">\n";
		print "<input type=\"hidden\" name=\"url\" value=\"index.php?command=$command&amp;\" />\n";
		print "<input type=\"hidden\" name=\"ged\" value=\"";if (isset($GEDCOM)) print $GEDCOM; print "\" />";
		print "<input type=\"hidden\" name=\"pid\" value=\"";if (isset($pid)) print $pid; print "\" />";
		print "<input type=\"hidden\" name=\"usertime\" value=\"\" />\n";
		print "<input type=\"hidden\" name=\"action\" value=\"login\" />\n";
		print "<label class=\"label_form\" for=\"username\">".$gm_lang["username"]."</label>";
		print "<input type=\"text\" id=\"username\" name=\"username\" tabindex=\"20\" /><br style=\"clear: left;\"/>";
		print "<label class=\"label_form\" for=\"password\">".$gm_lang["password"]."</label>";
		print "<input type=\"password\" id=\"password\" name=\"password\" tabindex=\"21\" /><br style=\"clear: left;\"/>";
		if ($ALLOW_REMEMBER_ME) {
			print "<label class=\"label_form_remember\" for=\"remember\">";
			print_help_link("remember_me_help", "qm", "remember_me");
			print $gm_lang["remember_me"]."</label>";
			print "<input class=\"input_form_remember\" type=\"checkbox\" tabindex=\"22\" id=\"remember\" name=\"remember\" value=\"yes\" ";
			if (!empty($_COOKIE["gm_rem"])) print "checked=\"checked\" ";
			print "/>";
			print "<br style=\"clear: left;\"/>\n";
		}
		print "<input type=\"submit\" id=\"submitlogin\"  tabindex=\"23\" value=\"".$gm_lang["login"]."\" />&nbsp;";
		print "</form>\n";
		print "<br />";
		if ($USE_REGISTRATION_MODULE){
			print_help_link("new_user_help", "qm", "no_account_yet");
			print $gm_lang["no_account_yet"];
			print "<br />";
			print "<a href=\"login_register.php?action=register\">";
			print $gm_lang["requestaccount"];
			print "</a>";
			print "<br /><br />";
			print_help_link("new_password_help", "qm", "lost_password");
			print $gm_lang["lost_password"];
			print "<br />";
			print "<a href=\"login_register.php?action=pwlost\">";
			print $gm_lang["requestpassword"];
			print "</a>";
		}
		print "</div>";
		print "</div>";
	}
}
?>
