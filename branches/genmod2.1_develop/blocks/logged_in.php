<?php
/**
 * Logged In Users Block
 *
 * This block will print a list of the users who are currently logged in
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
 * @subpackage Blocks
 * @version $Id: logged_in.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

$GM_BLOCKS["print_logged_in_users"]["name"]        	= GM_LANG_logged_in_users_block;
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
		global $TEXT_DIRECTION, $NAME_REVERSE, $gm_user;
		
	print "<!-- Start Logged In Block //-->";
		$block = true;			// Always restrict this block's height
		
		$NumAnonymous = 0;
		$users = UserController::GetUsers("username", "asc", "firstname", "u_loggedin='Y'");
		$loggedusers = array();
		foreach($users as $indexval => $user) {
			if (time() - $user->sessiontime > GM_SESSION_TIME) UserController::UserLogout($user->username);
			else {
				if (($gm_user->userIsAdmin()) || (($user->visibleonline) && ($gm_user->visibleonline))) $loggedusers[] = $user;
				else $NumAnonymous ++;
			}
		}

		print "<div id=\"logged_in_users\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
				PrintHelpLink("index_loggedin_help", "qm", "users_logged_in");
				print "<div class=\"BlockHeaderText\">".GM_LANG_users_logged_in."</div>";
			print "</div>";
			print "<div class=\"BlockContent\">";
				if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
				else print "<div class=\"RestrictedBlockHeightMain\">\n";
					$LoginUsers = count($loggedusers);
					if (($LoginUsers == 0) and ($NumAnonymous == 0)) {
						print "<div class=\"LoggedInBlockMessage\">".GM_LANG_no_login_users."</div>";
					}
					$Advisory = "anon_user";
					if ($NumAnonymous > 1) $Advisory .= "s";
					
					if ($NumAnonymous > 0) {
						// Anonymous = 1
						define("GM_LANG_global_num1", $NumAnonymous);	// Make it visible
						print "<div class=\"LoggedInBlockMessage\">".PrintText($Advisory,0,1)."</div>";
					}
					$Advisory = "login_user";
					if ($LoginUsers > 1) $Advisory .= "s";
					if ($LoginUsers > 0) {
						// Logged_in = 2
						define("GM_LANG_global_num2", $LoginUsers);		// Make it visible
						print "<div class=\"LoggedInBlockMessage\">".PrintText($Advisory,0,1)."</div>";
					}
					foreach($loggedusers as $indexval => $user) {
						print "<div class=\"LoggedInBlockUser\">";
							if ($NAME_REVERSE) print PrintReady($user->lastname." ".$user->firstname);
							else print PrintReady($user->firstname." ".$user->lastname);
							print " - ".$user->username;
							if (($gm_user->username != $user->username) and ($user->contactmethod != "none")) {
								print "<br /><a href=\"#\" onclick=\"return message('".$user->username."');\">".GM_LANG_message."</a>";
							}
						print "</div>";
					}
				print "</div>\n";
			print "</div>"; // blockcontent
		print "</div>"; // block
	print "<!-- End Logged In Block //-->";
}

?>
