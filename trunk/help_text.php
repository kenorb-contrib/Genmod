<?php
/**
 * Shows helptext to the users
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id: help_text.php,v 1.6 2006/04/17 20:01:52 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
require($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY . $factsfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $factsfile[$LANGUAGE];

if (!isset($help)) $help = "";

/**
 * Inclusion of the help text variables
*/
require ("help_text_vars.php");
print_simple_header($gm_lang["help_header"]);
print "<a name=\"top\"></a><span class=\"helpheader\">".$gm_lang["help_header"]."</span><br /><br />\n<div class=\"left\">\n";
$actione = "";

if (isset($action)) $actione = $action;
if (($help == "help_useradmin.php")&& ($actione == "edituser")) $help = "edit_useradmin_help";
if (($help == "help_login_register.php")&& ($actione == "pwlost")) $help = "help_login_lost_pw.php";
if ($help == "help_contents_help") {
	if (userIsAdmin($gm_username)) {
		$help = "admin_help_contents_help";
		print_text("admin_help_contents_head_help");
	}
	else print_text("help_contents_head_help");
	print_help_index($help);
}
else print_text($help);
print "\n</div>\n";
print "<div class=\"left\">";
print "<a href=\"#top\" title=\"".$gm_lang["move_up"]."\">$UpArrow</a><br />";
print "<a href=\"help_text.php?help=help_contents_help\"><b>".$gm_lang["help_contents"]."</b></a><br />";
print "<a href=\"#\" onclick=\"window.close();\"><b>".$gm_lang["close_window"]."</b></a>";
print "</div>";
print_simple_footer();
?>
