<?php
/**
 * Displays the details about a source record. Also shows how many people and families
 * reference this source.
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
 * Inclusion of the source controller
*/
$source_controller = new SourceController();

if ($source_controller->source->isempty) {
	print_header($gm_lang["source_not_found"]);
	print "<span class=\"error\">".$gm_lang["source_not_found"]."</span>";
	print_footer();
	exit;
}

print_header($source_controller->getPageTitle());
?>
<div id="show_changes"></div>

<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record() {
		var recwin = window.open("gedrecord.php?pid=<?php print $source_controller->source->xref ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
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
<table class="list_table">
	<tr>
		<td>
		<span class="name_head"><?php print PrintReady($source_controller->source->title.$source_controller->source->addxref);?></span><br />
		<?php if($SHOW_COUNTER) {
			print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
		}?>
		</td>
	</tr>
	<tr>
</table>
<?php

// Print the tab doors
$source_controller->PrintTabs();

print_footer();
?>