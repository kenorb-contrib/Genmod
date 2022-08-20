<?php
/**
 * Repositories List
 *
 * Parses gedcom file and displays a list of the repositories in the file.
 *
 * The alphabet bar shows all the available letters users can click. The bar is built
 * up from the lastnames first letter. Added to this bar is the symbol @, which is
 * shown as a translated version of the variable <var>gm_lang["NN"]</var>, and a
 * translated version of the word ALL by means of variable <var>GM_LANG_all</var>.
 *
 * The details can be shown in two ways, with surnames or without surnames. By default
 * the user first sees a list of surnames of the chosen letter and by clicking on a
 * surname a list with names of people with that chosen surname is displayed.
 *
 * Beneath the details list is the option to skip the surname list or show it.
 * Depending on the current status of the list.
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
 * @version $Id: repolist.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$repolist_controller = New RepoListController();


PrintHeader($repolist_controller->pagetitle);
print "<div id=\"RepoListPage\">";
print "<div class=\"PageTitleName\">";
print GM_LANG_repo_list;
print "</div>";

$ctot = $repolist_controller->repo_total + $repolist_controller->repo_add - $repolist_controller->repo_hide;

print "\n\t<table class=\"ListTable RepoListTable\">\n\t\t";
print "<tr><td class=\"ListTableHeader\"";
if($ctot > 12) print " colspan=\"2\"";
print ">";
print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" title=\"".GM_LANG_titles_found."\" alt=\"".GM_LANG_titles_found."\" />&nbsp;&nbsp;";
print GM_LANG_titles_found;
PrintHelpLink("repolist_listbox_help", "qm");
print "</td></tr><tr><td class=\"ListTableContent\">";

if ($ctot > 0){
	$i=1;
	print "<ul>";
	// -- print the array
	foreach ($repolist_controller->repolist as $key => $repo) {
		$repo->PrintListRepository(true, 2);
		if ($i == ceil($ctot/2) && $ctot>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
		$i++;
	}
	
	if ($repolist_controller->repo_add > 0) {
		// -- print the additional array
		foreach ($repolist_controller->addrepolist as $key => $repo) {
			$repo->PrintListRepository(true, 3);
			if ($i==ceil($ctot/2) && $ctot>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
			$i++;
		}
	}

	print "\n\t\t</ul></td>\n\t\t";
 
	print "</tr><tr><td class=\"ListTableColumnFooter\"".($ctot>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_repositories." ".$repolist_controller->repo_total;
	if ($repolist_controller->repo_hide > 0) print "  --  ".GM_LANG_hidden." ".$repolist_controller->repo_hide;
}
else print "<span class=\"Warning\">".GM_LANG_no_results."</span>";

print "</td>\n\t\t</tr>\n\t</table>";

print "</div>";
PrintFooter();
?>