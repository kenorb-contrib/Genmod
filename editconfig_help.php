<?php
/**
 * English Language Configure Help file for Genmod
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
 * @version $Id: editconfig_help.php,v 1.3 2006/01/22 19:50:24 roland-d Exp $
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
require ("help_text_vars.php");
print_simple_header($gm_lang["help_config"]);
print '<span class="helpheader">';
print $gm_lang["help_config"];
print '</span><br /><br /><span class="helptext">';
if ($help == "help_contents_help") {
		if (userIsAdmin($gm_username)) {
		$help = "admin_help_contents_help";
		print_text("admin_help_contents_head_help");
	}
	else print_text("help_contents_head_help");
	print_help_index($help);
}
else {
	if ($help == "help_uploadgedcom.php") $help = "help_addgedcom.php";
	print_text($help);
}
print "</span><br /><br />";
print "<a href=\"help_text.php?help=help_contents_help\"><b>";
print $gm_lang["help_contents"];
print "</b></a><br />";
print "<a href=\"#\" onclick=\"window.close();\"><b>";
print $gm_lang["close_window"];
print "</b></a>";
print_simple_footer();
?>