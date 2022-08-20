<?php
/**
 * Parses gedcom file and displays a list of the general notes in the file.
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
 * @version $Id: notelist.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");
/**
 * Inclusion of the note note_controller
*/
$note_controller = new NoteController();

PrintHeader(GM_LANG_note_list);
print "<div id=\"NoteListPage\">";
print "<div class=\"PageTitleName\">";
print GM_LANG_note_list;
print "</div>";

$note_controller->GetNoteList();

$note_total = GetListSize("notelist");
$note_count = count($note_controller->notelist); 
$note_hide = $note_total - $note_count;

print "\n\t<table class=\"ListTable NoteListTable\">\n\t\t";
print "<tr><td class=\"ListTableHeader\"";
if($note_count > 12) print " colspan=\"2\"";
print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" title=\"".GM_LANG_notes."\" alt=\"".GM_LANG_notes."\" />&nbsp;&nbsp;";
print GM_LANG_titles_found;
PrintHelpLink("notelist_listbox_help", "qm");
print "</td></tr><tr><td class=\"ListTableContent\">";
$i=1;
if ($note_count > 0){
	print "<ul>";
	// -- print the array
	foreach ($note_controller->notelist as $key => $note) {
		$note->PrintListNote();
		if ($i==ceil($note_count/2) && $note_count>12) print "</ul></td><td class=\"ListTableContent\"><ul>\n";
		$i++;
	}
	print "\n\t\t</ul></td>\n\t\t";
 
	print "</tr><tr><td class=\"ListTableColumnFooter\"".($note_count>12 ? " colspan=\"2\"" : "").">".GM_LANG_total_notes." ".$note_total;
	if ($note_hide > 0) print "<br />".GM_LANG_hidden." ".$note_hide;
}
else print "<span class=\"Warning\">".GM_LANG_no_results."</span>";

print "</td>\n\t\t</tr>\n\t</table>";

print "</div>";
PrintFooter();
?>