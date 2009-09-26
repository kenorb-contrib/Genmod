<?php
/**
 * Parses gedcom file and displays a list of the general notes in the file.
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
/**
 * Inclusion of the note note_controller
*/
require_once("includes/controllers/note_ctrl.php");

print_header($gm_lang["note_list"]);
print "<div class=\"center\">";
print "<h3>".$gm_lang["note_list"]."</h3>\n\t";

$note_controller->GetNoteList();

$note_total = GetListSize("notelist");
$note_count = count($note_controller->notelist); 
$note_hide = $note_total - $note_count;

print "\n\t<table class=\"list_table $TEXT_DIRECTION center\">\n\t\t<tr><td class=\"shade2 center\"";
if($note_count > 12) print " colspan=\"2\"";
print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" title=\"".$gm_lang["notes"]."\" alt=\"".$gm_lang["notes"]."\" />&nbsp;&nbsp;";
print $gm_lang["titles_found"];
print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
$i=1;
if ($note_count > 0){
	// -- print the array
	foreach ($note_controller->notelist as $key => $note) {
		$note->PrintListNote();
		if ($i==ceil($note_count/2) && $note_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
		$i++;
	}
	print "\n\t\t</ul></td>\n\t\t";
 
	print "</tr><tr><td>".$gm_lang["total_notes"]." ".$note_total;
	if ($note_hide > 0) print "<br />".$gm_lang["hidden"]." ".$note_hide;
}
else print "<span class=\"warning\"><i>".$gm_lang["no_results"]."</span>";

print "</td>\n\t\t</tr>\n\t</table>";

print_help_link("notelist_listbox_help", "qm");
print "</div>";
print "<br /><br />";
print_footer();
?>