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

PrintHeader($note_controller->pagetitle);

$note_controller->CheckNoResult($gm_lang["note_not_found"]);

$note_controller->CheckPrivate();

$note_controller->CheckRawEdited();
	
?>
<div id="show_changes"></div>
<?php $note_controller->PrintDetailJS(); ?>

<table class="list_table">
	<tr>
		<td>
		<span class="name_head"><?php print PrintReady($note_controller->note->GetTitle(40, $note_controller->note->show_changes).$note_controller->note->addxref); ?></span><br />
		<?php if(GedcomConfig::$SHOW_COUNTER) print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n"; ?><br />
		</td>
	</tr>
</table>

<?php 

// Print the tabs
$note_controller->PrintTabs();

PrintFooter();
?>