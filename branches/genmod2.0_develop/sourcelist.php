<?php
/**
 * Parses gedcom file and displays a list of the sources in the file.
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
 * @subpackage Lists
 * @version $Id$
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

$sourcelist_controller = New SourceListController();


print_header($sourcelist_controller->pagetitle);
print "<div class=\"center\">";
print "<h3>".$gm_lang["source_list"]."</h3>\n\t";

$ctot = $sourcelist_controller->sour_total + $sourcelist_controller->sour_add - $sourcelist_controller->sour_hide;

print "\n\t<table class=\"list_table $TEXT_DIRECTION center\">\n\t\t<tr><td class=\"shade2 center\"";
if ($ctot > 12)	print " colspan=\"2\"";
print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".$gm_lang["sources"]."\" alt=\"".$gm_lang["sources"]."\" />&nbsp;&nbsp;";
print $gm_lang["titles_found"];
print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
$i=1;
if ($ctot > 0) {
	// -- print the array
	foreach ($sourcelist_controller->sourcelist as $key => $source) {
		$source->PrintListSource(true, 2);
		if ($i == ceil($ctot/2) && $ctot>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
		$i++;
	}
	
	if ($sourcelist_controller->sour_add > 0) {
		// -- print the additional array
		foreach ($sourcelist_controller->addsourcelist as $key => $source) {
			$source->PrintListSource(true, 3);
			if ($i==ceil($ctot/2) && $ctot>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
			$i++;
		}
	}

	print "\n\t\t</ul></td>\n\t\t";
 
	print "</tr><tr><td>".$gm_lang["total_sources"]." ".$sourcelist_controller->sour_total;
	if ($sourcelist_controller->sour_total > 0) print "&nbsp;&nbsp;(".$gm_lang["titles_found"]."&nbsp;".$sourcelist_controller->sour_total.")";
	if ($sourcelist_controller->sour_hide > 0) print "  --  ".$gm_lang["hidden"]." ".$sourcelist_controller->sour_hide;
}
else print "<span class=\"warning\"><i>".$gm_lang["no_results"]."</span>";

print "</td>\n\t\t</tr>\n\t</table>";

print_help_link("sourcelist_listbox_help", "qm");
print "</div>";
print "<br /><br />";
print_footer();
?>