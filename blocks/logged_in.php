<?php
/**
 * Logged In Users Block
 *
 * This block will print a list of the users who are currently logged in
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

$GM_BLOCKS["print_logged_in_users"]["name"]        	= $gm_lang["logged_in_users_block"];
$GM_BLOCKS["print_logged_in_users"]["descr"]        = "logged_in_users_descr";
$GM_BLOCKS["print_logged_in_users"]["canconfig"]	= false;
$GM_BLOCKS["print_logged_in_users"]["rss"]     		= false;

/**
 * logged in users
 *
 * prints a list of other users who are logged in
 */
/**
 * logged in users
 *
 * prints a list of other users who are logged in
 */
function print_logged_in_users($block=true, $config="", $side, $index) {
		global $gm_lang, $GM_SESSION_TIME, $TEXT_DIRECTION, $NAME_REVERSE, $gm_user;
		
		$block = true;			// Always restrict this block's height
		
		$NumAnonymous = 0;
		$users = UserController::GetUsers("username", "asc", "firstname", "u_loggedin='Y'");
		$loggedusers = array();
		foreach($users as $indexval => $user) {
			if (time() - $user->sessiontime > $GM_SESSION_TIME) UserController::UserLogout($user->username);
			else {
				if (($gm_user->userIsAdmin()) or (($user->visibleonline) and ($gm_user->visibleonline))) $loggedusers[] = $user;
				else $NumAnonymous ++;
			}
		}

		print "<div id=\"logged_in_users\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("index_loggedin_help", "qm", "users_logged_in");
		print $gm_lang["users_logged_in"];
		print "</div>";
		print "<div class=\"blockcontent\">";
		if ($block) print "<div class=\"small_inner_block\">\n";
		$LoginUsers = count($loggedusers);
		if (($LoginUsers == 0) and ($NumAnonymous == 0)) {
			print "<b>".$gm_lang["no_login_users"]."</b>";
		}
		$Advisory = "anon_user";
		if ($NumAnonymous > 1) $Advisory .= "s";
		
		if ($NumAnonymous > 0) {
			$gm_lang["global_num1"] = $NumAnonymous;	// Make it visible
			print "<b>".print_text($Advisory,0,1)."</b>";
		}
		$Advisory = "login_user";
		if ($LoginUsers > 1) $Advisory .= "s";
		if ($LoginUsers > 0) {
			$gm_lang["global_num1"] = $LoginUsers;		// Make it visible
			print "<b>".print_text($Advisory,0,1)."</b>";
		}
		if (count($loggedusers) > 0) print "<table width=\"90%\">";
		foreach($loggedusers as $indexval => $user) {
			print "<tr><td>";
			if ($NAME_REVERSE) print PrintReady($user->lastname." ".$user->firstname);
			else print PrintReady($user->firstname." ".$user->lastname);
			print " - ".$user->username;
			if (($gm_user->username != $user->username) and ($user->contactmethod != "none")) {
				print "<br /><a href=\"#\" onclick=\"return message('".$user->username."');\">".$gm_lang["message"]."</a>";
			}
			print "</td></tr>";
		}
		if (count($loggedusers) > 0) print "</table>";
		if ($block) print "</div>\n";
		print "</div>"; // blockcontent
		print "</div>"; // block
}

?>
