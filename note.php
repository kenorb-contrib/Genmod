<?php
/**
 * Displays the details about a note record.
 * Also shows the links of other records to this note.
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
 * @subpackage Display
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the note controller
*/
$note_controller = new NoteController();

if ($note_controller->isempty) {
	print_header($gm_lang["note_not_found"]);
	print "<span class=\"error\">".$gm_lang["note_not_found"]."</span>";
	print_footer();
	exit;
}

print_header($note_controller->getPageTitle());
	
?>
<div id="show_changes"></div>
<script type="text/javascript">
<!--
	function show_gedcom_record() {
		var recwin = window.open("gedrecord.php?pid=<?php print $oid ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
	}
	function showchanges() {
		sndReq('show_changes', 'set_show_changes', 'set_show_changes', '<?php if ($show_changes == "yes") print "no"; else print "yes"; ?>');
		window.location.reload();
	}
	function reload() {
		window.location.reload();
	}
//-->
</script>
<table width="100%"><tr><td>
<?php
print "\n\t<span class=\"name_head\">";
print $note_controller->note->GetTitle(40, $note_controller->note->showchanges);
if ($SHOW_ID_NUMBERS) print " &lrm;($oid)&lrm;";
print "</span><br />";
if($SHOW_COUNTER) {
	print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
}
print "<br />";

if ($note_controller->note->isempty && !$note_controller->note->changed) {
	print "&nbsp;&nbsp;&nbsp;<span class=\"warning\"><i>".$gm_lang["no_results"]."</i></span>";
	print "<br /><br /><br /><br /><br /><br />\n";
	print_footer();
	exit;
}
// Print the tabs
$note_controller->PrintTabs();

print_footer();
?>