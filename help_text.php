<?php
/**
 * Shows helptext to the users
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id: help_text.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if (!isset($help)) $help = "";

/**
 * Inclusion of the help text variables
*/
require ("helptext_vars.php");
PrintSimpleHeader(GM_LANG_help_header);
print "<div id=\"HelpPage\">\n";
print "<a name=\"top\"></a><div class=\"HelpHeader\">".GM_LANG_help_header."</div>\n";
print "<div class=\"HelpContent\">\n";
$actione = "";

if (isset($action)) $actione = $action;
if (($help == "help_useradmin.php")&& ($actione == "edituser")) $help = "edit_useradmin_help";
if (($help == "help_login_register.php")&& ($actione == "pwlost")) $help = "help_login_lost_pw.php";
if ($help == "help_contents_help") {
	if ($gm_user->userIsAdmin()) {
		$help = "admin_help_contents_help";
		PrintText("admin_help_contents_head_help");
	}
	else PrintText("help_contents_head_help");
	PrintHelpIndex($help);
}
else {
	$text = PrintText($help, 0, 1);
	print $text;
	if ($gm_user->UserIsAdmin()) {
		$stat = GetLangvarStatus($help, $LANGUAGE, $type="help");
		// Already translated, edit it
		if ($stat == 0) print "<br /><a href=\"#\" onclick=\"window.name='help'; window.open('editlang_edit.php?ls01=$help&amp;ls02=$help&amp;language2=$LANGUAGE&amp;file_type=help_text&amp;realtime=true', '', 'top=50,left=50,width=700,height=400,scrollbars=1,resizable=1');\">".GM_LANG_thishelp_edit_trans."</a><br />";
		// Add translation in the current language
		if ($stat == 1) print "<br /><a href=\"#\" onclick=\"window.name='help'; window.open('editlang_edit.php?ls01=$help&amp;ls02=-1&amp;language2=$LANGUAGE&amp;file_type=help_text&amp;realtime=true', '', 'top=50,left=50,width=700,height=400,scrollbars=1,resizable=1');\">".GM_LANG_thishelp_add_trans."</a><br />";
		// Add English helptext, only if the var is truly not found (may be in helptext_vars)
		if ($stat == 2 && stristr(GM_LANG_help_not_exist, $text)) {
			print "<br /><a href=\"#\" onclick=\"window.name='help'; window.open('editlang_edit.php?ls01=$help&amp;ls02=-1&amp;language2=english&amp;file_type=help_text&amp;realtime=true', '', 'top=50,left=50,width=700,height=400,scrollbars=1,resizable=1');\">".GM_LANG_thishelp_add_text."</a><br />";
		}
	}
}
print "\n</div>\n"; // Close HelpContent

print "<div class=\"HelpFooter\">";
print "<div class=\"HelpFooterLink\"><a href=\"#top\" title=\"".GM_LANG_move_up."\">$UpArrow</a></div>";
print "<div class=\"HelpFooterLink\"><a href=\"help_text.php?help=help_contents_help\">".GM_LANG_help_contents."</a></div>";
print "<div class=\"HelpFooterLink\"><a href=\"#\" onclick=\"window.close();\">".GM_LANG_close_window."</a></div>";
print "<div class=\"ClearBoth;\">&nbsp;</div>";
print "</div>"; // Close HelpFooter
print "</div>"; // Close HelpPage
PrintSimpleFooter();
?>
