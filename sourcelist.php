<?php
/**
 * Parses gedcom file and displays a list of the sources in the file.
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
 * @subpackage Lists
 * @version $Id: sourcelist.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

$sourcelist_controller = New SourceListController();


PrintHeader($sourcelist_controller->pagetitle);
print "<div id=\"SourceListPage\">";
print "<div class=\"PageTitleName\">".GM_LANG_source_list."</div>\n\t";

$ctot = $sourcelist_controller->sour_total + $sourcelist_controller->sour_add - $sourcelist_controller->sour_hide;

print "\n\t<table class=\"ListTable SourceListTable\">\n\t\t";
print "<tr><td class=\"ListTableHeader\"";
if ($ctot > 12)	print " colspan=\"2\"";
print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".GM_LANG_sources."\" alt=\"".GM_LANG_sources."\" />&nbsp;&nbsp;";
print GM_LANG_titles_found;
PrintHelpLink("sourcelist_listbox_help", "qm");
print "</td></tr>";
print "<tr><td class=\"ListTableContent\">";
$i=1;
if ($ctot > 0) {
	print "<ul>";	// -- print the array
	foreach ($sourcelist_controller->sourcelist as $key => $source) {
		$source->PrintListSource(true, 2);
		if ($i == ceil($ctot/2) && $ctot>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
		$i++;
	}
	
	if ($sourcelist_controller->sour_add > 0) {
		// -- print the additional array
		foreach ($sourcelist_controller->addsourcelist as $key => $source) {
			$source->PrintListSource(true, 3);
			if ($i==ceil($ctot/2) && $ctot>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
			$i++;
		}
	}

	print "\n\t\t</ul></td>\n\t\t";
 
	print "</tr><tr><td class=\"ListTableColumnFooter\"".($ctot>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_sources." ".$sourcelist_controller->sour_total;
	if ($sourcelist_controller->sour_total > 0) print "&nbsp;&nbsp;(".GM_LANG_titles_found."&nbsp;".$sourcelist_controller->sour_total.")";
	if ($sourcelist_controller->sour_hide > 0) print "  --  ".GM_LANG_hidden." ".$sourcelist_controller->sour_hide;
}
else print "<span class=\"Warning\">".GM_LANG_no_results."</span>";

print "</td>\n\t\t</tr>\n\t</table>";

print "</div>";
PrintFooter();
?>