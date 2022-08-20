<?php
/**
 * Display an family book chart
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
 * @subpackage Charts
 * @version $Id: paternals.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

require("config.php");

$paternals_controller = new PaternalsController();

PrintHeader($paternals_controller->pagetitle);

// -- size of the boxes
if (!$paternals_controller->show_full) $bwidth = $bwidth / 1.5;
$bwidth*=$box_width/100;
if ($paternals_controller->show_full == false) {
	$bheight = $bheight / 2.5;
}

// -- print html header information
print "<div id=\"content_paternals\" style=\"width:".$paternals_controller->pagewidth."px;\">";

print "\n\t<div class=\"PageTitleName\">".GM_LANG_paternal_chart.": ";
print PrintReady($paternals_controller->root->name);
if ($paternals_controller->root->addname != "") print " (" . PrintReady($paternals_controller->root->addname).")";
print "</div>";
?>

<script language="JavaScript" type="text/javascript">
<!--
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
	}
//-->
</script>

<?php
if ($paternals_controller->view != "preview") {
	print "<div class=\"PaternalsNavBlock\">";
		print "<form method=\"get\" name=\"people\" action=\"?\">\n";
			print "\n\t\t<table class=\"NavBlockTable PaternalsNavBlockTable\">";
			
			// Header
			$paternals_controller->PrintInputHeader();
			
			// NOTE: rootid
			$paternals_controller->PrintInputRootId();
		
			// NOTE: line to follow
			$paternals_controller->PrintInputLine();
			
			// NOTE: start at
			$paternals_controller->PrintInputStart();
		
			// NOTE: box width
			$paternals_controller->PrintInputBoxWidth();
			
			// NOTE: show full
			$paternals_controller->PrintInputShowFull();
			
			// Submit
			$paternals_controller->PrintInputSubmit();
			
			print "</table>";
		print "</form>\n";
	print "</div>";
}
$paternals_controller->PrintPaternalLines();
print "</div>\n";

PrintFooter();
?>
