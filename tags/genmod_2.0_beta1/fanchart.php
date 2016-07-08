<?php
/**
 * Displays a fan chart
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
 * This Page Is Valid XHTML 1.0 Transitional! > 23 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id$
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

$fanchart_controller = new FanchartController();

// -- print html header information
PrintHeader($fanchart_controller->pagetitle);

// -- Print the page title
print "<div id=\"content_pedigree\">";
if ($fanchart_controller->view == "preview") print "<h3>" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($fanchart_controller->num_generations), GM_LANG_gen_fan_chart) . ":";
else print "<h3>" . GM_LANG_fan_chart . ":";
print "<br />".PrintReady($fanchart_controller->root->name);
if ($fanchart_controller->root->addname != "") print "<br />" . PrintReady($fanchart_controller->root->addname);
print "</h3>";

// -- print the form to change the number of displayed generations
if ($fanchart_controller->view != "preview") {
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
	if ($fanchart_controller->max_generation == true) print "<span class=\"error\">" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($fanchart_controller->num_generations), GM_LANG_max_generation) . "</span>";
	if ($fanchart_controller->min_generation == true) print "<span class=\"error\">" . GM_LANG_min_generation . "</span>";
	print "\n\t<form name=\"people\" method=\"get\" action=\"?\">";
	print "\n\t\t<table class=\"list_table ".$TEXT_DIRECTION."\">\n\t\t";
	
	// Option header
	$fanchart_controller->PrintInputHeader();
	
	// Rootid
	$fanchart_controller->PrintInputRootId();
	
	// NOTE: fan style
	$fanchart_controller->PrintInputFanStyle();
	
	// NOTE: generations
	$fanchart_controller->PrintInputGenerations(GedcomConfig::$MAX_PEDIGREE_GENERATIONS, "PEDIGREE_GENERATIONS_help");
	
	// NOTE: fan width
	$fanchart_controller->PrintInputBoxWidth();
	
	// NOTE: submit
	$fanchart_controller->PrintInputSubmit();
	
	print "</table>";
	print "\n\t\t</form>";
} 
else {
	print "<script language='JavaScript' type='text/javascript'><!--\n";
	print "if (IE) document.write('<span class=\"warning\">".str_replace("'", "\'", GM_LANG_fanchart_IE)."</span>');";
	print "//--></script>";
}

$treeid = ChartFunctions::AncestryArray($fanchart_controller->root->xref, $fanchart_controller->num_generations);

$fanchart_controller->PrintFanChart($treeid, 640*$fanchart_controller->box_width/100, $fanchart_controller->fan_style*90);
print "</div>";
PrintFooter();
?>