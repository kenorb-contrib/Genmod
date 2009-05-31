<?php
/**
 * Parses gedcom file and displays a descendancy tree.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 21 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: descendancy.php,v 1.24 2009/03/25 16:53:52 sjouke Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");
/**
 * Inclusion of the chart functions
*/
require("includes/functions/functions_charts.php");

/**
 * print a child family
 *
 * @param string $pid individual Gedcom Id
 * @param int $depth the descendancy depth to show
 */
function print_child_family($pid, $depth, $label="1.", $gpid="") {
	global $gm_lang, $view, $show_full;
	global $GM_IMAGE_DIR, $GM_IMAGES, $personcount;

	if ($depth<1) return;
	$famids = FindSfamilyIds($pid);
	foreach($famids as $famkey => $ffamid) {
		$famid = $ffamid["famid"];
		PrintSosaFamily($famid, "", -1, $label, $pid, $gpid, $personcount);
		$personcount++;
		$children = GetChildrenIds($famid);
		$i=1;
		foreach ($children as $childkey => $child) {
			print_child_family($child, $depth-1, $label.($i++).".", $pid);
		}
	}
}

/**
 * print a child descendancy
 *
 * @param string $pid individual Gedcom Id
 * @param int $depth the descendancy depth to show
 */
function print_child_descendancy($pid, $depth) {
	global $gm_lang, $view, $chart_style, $show_full, $generations, $box_width;
	global $GM_IMAGE_DIR, $GM_IMAGES, $Dindent;
	global $dabo, $personcount;

	// print child
	print "<li>";
	print "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td style=\"vertical-align:middle;\">";
	if ($depth==$generations) print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" height=\"2\" width=\"$Dindent\" border=\"0\" alt=\"\" /></td><td style=\"vertical-align:middle;\">\n";
	else print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" height=\"2\" width=\"$Dindent\" border=\"0\" alt=\"\" /></td><td style=\"vertical-align:middle;\">\n";
	print_pedigree_person($pid, 1, $view!="preview",'',$personcount);
	print "</td>";

	// check if child has parents and add an arrow
	print "<td>&nbsp;</td>";
	print "<td>";
	$sfamids = FindFamilyIds($pid);
	foreach($sfamids as $indexval => $fsfamid) {
		$sfamid = $fsfamid["famid"];
		$parents = FindParents($sfamid);
		if ($parents) {
			$parid=$parents["HUSB"];
			if ($parid=="") $parid=$parents["WIFE"];
			if ($parid!="") {
				$desc = GetFamilyDescriptor($sfamid, true);
				PrintUrlArrow($parid.$personcount.$pid, "?pid=$parid&amp;generations=$generations&amp;chart_style=$chart_style&amp;show_full=$show_full&amp;box_width=$box_width", PrintReady($gm_lang["start_at_parents"]."&nbsp;-&nbsp;".$desc), 2);
				$personcount++;
			}
		}
	}

	// d'Aboville child number
	$level =$generations-$depth;
	if ($show_full) print "<br /><br />&nbsp;";
	print "<span dir=\"ltr\">"; //needed so that RTL languages will display this properly
	if (!isset($dabo[$level])) $dabo[$level]=0;
	$dabo[$level]++;
	$dabo[$level+1]=0;
	for ($i=0; $i<=$level;$i++) print $dabo[$i].".";
	print "</span>";
	print "</td></tr>";

	// empty descendancy
	$sfam = FindSfamilyIds($pid);
	print "</table>";
	print "</li>\r\n";
	if ($depth<1) return;

	// loop for each spouse
	foreach ($sfam as $indexval => $ffamid) {
		$famid = $ffamid["famid"];
		$personcount++;
		print_family_descendancy($pid, $famid, $depth);
	}
}

/**
 * print a family descendancy
 *
 * @param string $pid individual Gedcom Id
 * @param string $famid family Gedcom Id
 * @param int $depth the descendancy depth to show
 */
function print_family_descendancy($pid, $famid, $depth) {
	global $gm_lang, $factarray, $view, $show_full, $generations, $box_width, $bwidth;
	global $GEDCOM, $GM_IMAGE_DIR, $GM_IMAGES, $Dindent, $personcount;

	if ($famid=="") return;

	$famrec = FindFamilyRecord($famid);
	$parents = FindParents($famid);
	if ($parents) {

		// spouse id
		$id = $parents["WIFE"];
		if ($id==$pid) $id = $parents["HUSB"];

		// print marriage info
		print "<li>";
		print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" height=\"2\" width=\"$Dindent\" border=\"0\" alt=\"\" />";
		print "<span class=\"details1\" style=\"white-space: nowrap; \" >";
		print "<a href=\"#\" onclick=\"expand_layer('".$famid.$personcount."'); return false;\" class=\"top\"><img id=\"".$famid.$personcount."_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" align=\"middle\" hspace=\"0\" vspace=\"3\" border=\"0\" alt=\"".$gm_lang["view_family"]."\" /></a> ";
		if (showFact("MARR", $famid) && DisplayDetailsByID($famid, "FAM")) print_simple_fact($famrec, "MARR", $id); else print $gm_lang["private"];
		print "</span>";

		// print spouse
		print "<ul style=\"list-style: none; display: block;\" id=\"$famid$personcount\">";
		print "<li>";
		print "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>";
		print_pedigree_person("$id", 1, $view!="preview",''.$personcount);
		print "</td>";

		// check if spouse has parents and add an arrow
		print "<td>&nbsp;</td>";
		print "<td>";
		$sfamids = FindFamilyIds($id);
		foreach($sfamids as $indexval => $fsfamid) {
			$sfamid = $fsfamid["famid"];
			$parents = FindParents($sfamid);
			if ($parents) {
				$parid=$parents["HUSB"];
				if ($parid=="") $parid=$parents["WIFE"];
				if ($parid!="") {
					$desc = GetFamilyDescriptor($sfamid, true);
					PrintUrlArrow($parid.$personcount.$pid, "?pid=$parid&amp;generations=$generations&amp;show_full=$show_full&amp;box_width=$box_width", PrintReady($gm_lang["start_at_parents"]."&nbsp;-&nbsp;".$desc), 2);
					$personcount++;
				}
			}
		}
		if ($show_full) print "<br /><br />&nbsp;";
		print "</td></tr>";

		// children
		$children = GetChildrenIds($famid);
		print "<tr><td colspan=\"3\" class=\"details1\" >&nbsp;";
		if (count($children)<1) print $gm_lang["no_children"];
		else print $factarray["NCHI"].": ".count($children);
		print "</td></tr></table>";
		print "</li>\r\n";
		foreach ($children as $indexval => $child) {
			$personcount++;
			print_child_descendancy($child, $depth-1);
		}
		print "</ul>\r\n";
		print "</li>\r\n";
	}
}

// -- args
if (!isset($show_full)) $show_full=$PEDIGREE_FULL_DETAILS;
if (!isset($chart_style)) $chart_style = 0;
if ($chart_style=="") $chart_style = 0;
if (!isset($generations)) $generations = 2;
if (empty($generations)) $generations = 2;
if ($generations > $MAX_DESCENDANCY_GENERATIONS) $generations = $MAX_DESCENDANCY_GENERATIONS;
if (!isset($view)) $view="";
if (!isset($personcount)) $personcount = 1;

// -- size of the boxes
if (!isset($box_width)) $box_width = "100";
if (empty($box_width)) $box_width = "100";
$box_width=max($box_width, 50);
$box_width=min($box_width, 300);
$Dbwidth*=$box_width/100;
if (!$show_full) $Dbheight=25;
$bwidth=$Dbwidth;
$bheight=$Dbheight;
$pbwidth = $bwidth+12;
$pbheight = $bheight+14;

// -- root id
if (!isset($pid)) $pid="";
$pid = CleanInput($pid);
$pid=CheckRootId($pid);
if (showLivingNameByID($pid)) {
	$name = GetPersonName($pid);
	$addname = GetAddPersonName($pid);
}
else {
	$name = $gm_lang["private"];
	$addname = "";
}

// -- print html header information
$title = $name;
if ($SHOW_ID_NUMBERS) $title .= " - ".$pid;
$title .= " - ".$gm_lang["descend_chart"];
print_header($title);
if (strlen($name)<30) $cellwidth="420";
else $cellwidth=(strlen($name)*14);
print "\n\t<table class=\"list_table $TEXT_DIRECTION\"><tr><td width=\"${cellwidth}\" valign=\"top\">\n\t\t";
print "\n\t<h3>".$gm_lang["descend_chart"].":";
print "<br />".PrintReady($name);
if ($addname != "") print "<br />" . PrintReady($addname);
print "</h3>";
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
$gencount=0;
if ($view!="preview") {
	$show_famlink = true;
	print "</td><td><form method=\"get\" name=\"people\" action=\"?\">\n";
	// 	print_help_link("descendancy_help", "page_help");
	print "\n<table class=\"list_table $TEXT_DIRECTION\">\n";

	// NOTE: rootid
	print "<tr><td class=\"shade2\">";
	print_help_link("desc_rootid_help", "qm");
	print $gm_lang["root_person"]."&nbsp;</td>";
	print "<td class=\"shade1 vmiddle\">";
	print "\n\t\t<input class=\"pedigree_form\" type=\"text\" id=\"pid\" name=\"pid\" size=\"3\" value=\"$pid\" />";
	PrintFindIndiLink("pid","");
	print "</td>";

	// NOTE: box width
	print "<td class=\"shade2\">";
	print_help_link("box_width_help", "qm");
	print $gm_lang["box_width"] . "&nbsp;</td>";
	print "<td class=\"shade1 vmiddle\"><input type=\"text\" size=\"3\" name=\"box_width\" value=\"$box_width\" /> <b>%</b>";
	print "</td>";

	// NOTE: chart style
	print "<td rowspan=\"2\" class=\"shade2\">";
	//	print_help_link("chart_style_help", "qm");
	print $gm_lang["displ_layout_conf"];
	print "</td>";
	print "<td rowspan=\"2\" class=\"shade1 vmiddle\">";
	print "<input type=\"radio\" name=\"chart_style\" value=\"0\"";
	if (!$chart_style) print " checked=\"checked\"";
	else print " onclick=\"document.people.chart_style.value='1';\"";
	print " />".$gm_lang["chart_list"];
	print "<br /><input type=\"radio\" name=\"chart_style\" value=\"1\"";
	if ($chart_style) print " checked=\"checked\"";
	else print " onclick=\"document.people.chart_style.value='0';\"";
	print " />".$gm_lang["chart_booklet"];
	print "</td>";

	// NOTE: submit
	print "<td rowspan=\"2\">";
	print "<input type=\"submit\"  value=\"".$gm_lang["view"]."\" />";
	print "</td></tr>";

	// NOTE: generations
	print "<tr><td class=\"shade2\">";
	print_help_link("desc_generations_help", "qm");
	print $gm_lang["generations"] . "&nbsp;</td>";

	print "<td class=\"shade1 vmiddle\">";
	print "<select name=\"generations\">";
	for ($i=2; $i<=$MAX_DESCENDANCY_GENERATIONS; $i++) {
		print "<option value=\"".$i."\"" ;
		if ($i == $generations) print " selected=\"selected\"";
		print ">".$i."</option>";
	}
	print "</select>";
	
	print "</td>";

	// NOTE: show full
	print "<td class=\"shade2\">";
	print "<input type=\"hidden\" name=\"show_full\" value=\"$show_full\" />";
	print_help_link("show_full_help", "qm");
	print $gm_lang["show_details"];
	print "</td>";
	print "<td class=\"shade1 vmiddle\">";
	print "<input type=\"checkbox\" value=\"";
	if ($show_full) print "1\" checked=\"checked\" onclick=\"document.people.show_full.value='0';\"";
	else print "0\" onclick=\"document.people.show_full.value='1';\"";
	print " />";
	print "</td></tr>";

	print "</table>";
	print "</form>\n";
}
print "</td></tr></table>";
?>

<?php

// d'Aboville numbering system [ http://www.saintclair.org/numbers/numdob.html ]
$dabo=array();
// descendancy booklet
if ($chart_style) {
	$show_cousins = true;
	$famids = FindSfamilyIds($pid);
	if (count($famids)) {
		print_child_family($pid,$generations);
		print_footer();
		exit;
	}
}
// descendancy list
print "<ul style=\"list-style: none; display: block;\" id=\"descendancy_chart".($TEXT_DIRECTION=="rtl" ? "_rtl" : "") ."\">\r\n";
print_child_descendancy($pid, $generations);
print "</ul>";
print "<br />";

print_footer();
?>
