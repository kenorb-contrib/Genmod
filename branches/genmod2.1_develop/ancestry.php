<?php
/**
 * Displays pedigree tree as a printable booklet
 *
 * with Sosa-Stradonitz numbering system
 * ($rootid=1, father=2, mother=3 ...)
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
 * @version $Id: ancestry.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");
$ancestry_controller = new AncestryController();

// -- size of the boxes
$Dbwidth *= $ancestry_controller->box_width/100;
if (!$ancestry_controller->show_full) $Dbheight=25;
$bwidth=$Dbwidth;
$bheight=$Dbheight;
$pbwidth = $bwidth+12;
$pbheight = $bheight+14;


// -- print html header information
PrintHeader($ancestry_controller->pagetitle);
print "<div id=\"content_ancestry\">";

if ($ancestry_controller->view == "preview") print "<span class=\"PageTitleName\">" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($ancestry_controller->num_generations), GM_LANG_gen_ancestry_chart) . ":";
else print "<span class=\"PageTitleName\">" . GM_LANG_ancestry_chart . ":";
print "&nbsp;".PrintReady($ancestry_controller->root->name);
if ($ancestry_controller->root->addname != "") print "&nbsp;" . PrintReady($ancestry_controller->root->addname);
print "</span>";

// -- print the form to change the number of displayed generations
if ($ancestry_controller->view != "preview") {
	$show_famlink = true;
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
	if (isset($ancestry_controller->max_generation) == true) print "<span class=\"Error\">" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($ancestry_controller->num_generations), GM_LANG_max_generation) . "</span>";
	if (isset($ancestry_controller->min_generation) == true) print "<span class=\"Error\">" . GM_LANG_min_generation . "</span>";
	print "<div class=\"AncestryNavBlock\">";
		print "<form name=\"people\" id=\"people\" method=\"get\" action=\"?\">";
			print "\n\t\t<table class=\"NavBlockTable AncestryNavBlockTable\">";
		
			// Option header
			$ancestry_controller->PrintInputHeader();
			
			// Root ID
			$ancestry_controller->PrintInputRootId();
		
			// Generations
			$ancestry_controller->PrintInputGenerations(GedcomConfig::$MAX_DESCENDANCY_GENERATIONS, "PEDIGREE_GENERATIONS_help");	
			
			// Box width
			$ancestry_controller->PrintInputBoxWidth();
		
			// NOTE: show full
			$ancestry_controller->PrintInputShowFull();
					
			// NOTE: chart style
			$ancestry_controller->PrintInputChartStyle();
		
			// NOTE: show cousins
			$ancestry_controller->PrintInputShowCousins();
		
			// Submit
			$ancestry_controller->PrintInputSubmit();
			
			print "</table>";
		print "</form>\n";
	print "</div>";
}

if ($ancestry_controller->chart_style) {
	// first page : show indi facts
	PersonFunctions::PrintPedigreePerson($ancestry_controller->root, 2, false, 1, 1, "", $ancestry_controller->params);
	// expand the layer
	echo <<< END
	<script language="JavaScript" type="text/javascript">
	<!--
		expandbox("$ancestry_controller->xref.1", 2);
	//-->
	</script>
	<br />
END;
	// process the tree
	$treeid = ChartFunctions::PedigreeArray($ancestry_controller->xref, $ancestry_controller->num_generations);
	$treesize = pow(2, (int)($ancestry_controller->num_generations))-1;
	for ($i = 0; $i < $treesize; $i++) {
		$pid = $treeid[$i];
		if ($pid) {
			$person =& Person::GetInstance($pid);
			$fam = $person->primaryfamily;
			if (!empty($fam)) {
				$family =& Family::GetInstance($fam);
				ChartFunctions::PrintSosaFamily($family, $pid, $i + 1, "", "", "", $ancestry_controller->view);
			}
			// show empty family only if it is the first and only one
			else if ($i == 0) ChartFunctions::PrintSosaFamily("", $pid, $i + 1, "", "", "", $ancestry_controller->view);
		}
	}
}
else {
	print "<ul id=\"ancestry_chart\">\r\n";
	$ancestry_controller->PrintChildAscendancy($ancestry_controller->root, 1, $ancestry_controller->num_generations);
	print "</ul>";
}
print "</div>";
PrintFooter();
?>
