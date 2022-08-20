<?php
/**
 * English Language Configure Help file for Genmod
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
 * @version $Id: editconfig_help.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Admin
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the help text variables
*/
require ("helptext_vars.php");
PrintSimpleHeader(GM_LANG_help_config);
print '<span class="HelpHeader">';
print GM_LANG_help_config;
print '</span><br /><br /><span class="helptext">';
if ($help == "help_contents_help") {
		if ($gm_user->userIsAdmin()) {
		$help = "admin_help_contents_help";
		PrintText("admin_help_contents_head_help");
	}
	else PrintText("help_contents_head_help");
	PrintHelpIndex($help);
}
else {
	if ($help == "help_uploadgedcom.php") $help = "help_addgedcom.php";
	PrintText($help);
}
print "</span><br /><br />";
print "<a href=\"help_text.php?help=help_contents_help\"><b>";
print GM_LANG_help_contents;
print "</b></a><br />";
print "<a href=\"#\" onclick=\"window.close();\"><b>";
print GM_LANG_close_window;
print "</b></a>";
PrintSimpleFooter();
?>