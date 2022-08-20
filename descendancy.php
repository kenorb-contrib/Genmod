<?php
/**
 * Parses gedcom file and displays a descendancy tree.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 21 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: descendancy.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$descendancy_controller = new DescendancyController();

PrintHeader($descendancy_controller->pagetitle);

// -- size of the boxes
$Dbwidth *= $descendancy_controller->box_width / 100;

if (!$descendancy_controller->show_full) $Dbheight=25;
$bwidth = $Dbwidth;
$bheight = $Dbheight;
$pbwidth = $bwidth + 12;
$pbheight = $bheight + 14;
$show_full = $descendancy_controller->show_full;


// -- print html header information
print "<div id=\"content_descendancy\">";

print "\n\t<span class=\"PageTitleName\">".GM_LANG_descend_chart.":";
print "&nbsp;".PrintReady($descendancy_controller->root->name);
if ($descendancy_controller->root->addname != "") print "&nbsp;" . PrintReady($descendancy_controller->root->addname);
print "</span>";
?>

<script type="text/javascript">
<!--
var pastefield;
function paste_id(value) {
	pastefield.value=value;
}
//-->
</script>

<?php
if ($descendancy_controller->view != "preview") {
	print "<div class=\"DescendancyNavBlock\">";
		$show_famlink = true;
		print "<form method=\"get\" name=\"people\" action=\"?\">\n";
		// 	PrintHelpLink("descendancy_help", "page_help");
			print "\n\t\t<table class=\"NavBlockTable DescendancyNavBlockTable\">";
			
			// NOTE: Option header
			$descendancy_controller->PrintInputHeader();
			
			// NOTE: rootid
			$descendancy_controller->PrintInputRootId();
		
			// NOTE: generations
			$descendancy_controller->PrintInputGenerations(GedcomConfig::$MAX_DESCENDANCY_GENERATIONS, "desc_generations_help");	
			
			// NOTE: box width
			$descendancy_controller->PrintInputBoxWidth();
		
			// NOTE: show full
			$descendancy_controller->PrintInputShowFull();
		
			// NOTE: chart style
			$descendancy_controller->PrintInputChartStyle();
		
			// NOTE: show cousins
			$descendancy_controller->PrintInputShowCousins();
			
			// Submit
			$descendancy_controller->PrintInputSubmit();
			
			print "</table>";
		print "</form>\n";
	print "</div>";
}
?>

<?php

// descendancy booklet
if ($descendancy_controller->chart_style) {
	if (count($descendancy_controller->root->spousefamilies) > 0) {
		$descendancy_controller->PrintChildFamily($descendancy_controller->xref,$descendancy_controller->num_generations);
	}
}
else {
// descendancy list
	print "<ul id=\"descendancy_chart\">\r\n";
	$descendancy_controller->PrintChildDescendancy($descendancy_controller->xref, $descendancy_controller->num_generations);
	print "</ul>";
}
print "</div>";
PrintFooter();
?>
