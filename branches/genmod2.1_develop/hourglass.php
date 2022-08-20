<?php
/**
 * Display an hourglass chart
 *
 * Set the root person using the $pid variable
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
 * This Page Is Valid XHTML 1.0 Transitional! > 23 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: hourglass.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

require("config.php");

$hourglass_controller = new FamilyBookController();

PrintHeader($hourglass_controller->pagetitle);

// -- size of the boxes
if (!$hourglass_controller->show_full) $bwidth = $bwidth / 1.5;
$bwidth*=$box_width/100;
if ($hourglass_controller->show_full == false) {
	$bheight = $bheight / 2.5;
}

// -- print html header information
print "<div id=\"content_hourglass\">";

print "\n\t<span class=\"PageTitleName\">".GM_LANG_hourglass_chart.": ";
print PrintReady($hourglass_controller->root->name);
if ($hourglass_controller->root->addname != "") print "&nbsp;" . PrintReady($hourglass_controller->root->addname);
print "</span>";
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
if ($hourglass_controller->view != "preview") {
	print "<form method=\"get\" name=\"people\" action=\"?\">\n";
	print "\n\t\t<table class=\"ListTable ".$TEXT_DIRECTION."\" align=\"";
	if ($TEXT_DIRECTION == "ltr") print "left";
	else print "right";
	print "\">";
	
	// Header
	$hourglass_controller->PrintInputHeader();
	
		// NOTE: rootid
	$hourglass_controller->PrintInputRootId();

	// NOTE: generations
	$hourglass_controller->PrintInputGenerations(GedcomConfig::$MAX_PEDIGREE_GENERATIONS, "desc_generations_help");
	
	// NOTE: Descent steps
	$hourglass_controller->PrintInputDescentSteps();
	
	// NOTE: box width
	$hourglass_controller->PrintInputBoxWidth();
	
	// NOTE: show full
	$hourglass_controller->PrintInputShowFull();
	
	// NOTE: show full
	$hourglass_controller->PrintInputShowSpouse();
	
	// Submit
	$hourglass_controller->PrintInputSubmit();
	
	print "</table>";
	print "</form>\n";
}
$hourglass_controller->PrintFamilyBook($hourglass_controller->root, $hourglass_controller->num_descent);
print "<br /><br />\n";
print "</div>\n";
PrintFooter();

?>
