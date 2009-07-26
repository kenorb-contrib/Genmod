<?php
/**
 * Displays pedigree tree as a printable booklet
 *
 * with Sosa-Stradonitz numbering system
 * ($rootid=1, father=2, mother=3 ...)
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
 * @version $Id$
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
 * print a child ascendancy
 *
 * @param string $pid individual Gedcom Id
 * @param int $sosa child sosa number
 * @param int $depth the ascendancy depth to show
 */
function print_child_ascendancy($pid, $sosa, $depth) {
	global $gm_lang, $view, $show_full, $OLD_PGENS, $box_width, $chart_style;
	global $GM_IMAGE_DIR, $GM_IMAGES, $Dindent;
	global $SHOW_EMPTY_BOXES, $pidarr;

	// child
	print "<li>";
	print "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td style=\"vertical-align:middle;\"><a name=\"sosa".$sosa."\"></a>";
	$new=($pid=="" or !isset($pidarr["$pid"]));
	if ($sosa==1) print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" height=\"2\" width=\"$Dindent\" border=\"0\" alt=\"\" /></td><td>\n";
	else print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" height=\"2\" width=\"$Dindent\" border=\"0\" alt=\"\" /></td><td>\n";
	print_pedigree_person($pid, 1, $view!="preview");
	print "</td>";
	print "<td style=\"vertical-align:middle;\">";
	if ($sosa>1) PrintUrlArrow($pid, "?rootid=$pid&amp;PEDIGREE_GENERATIONS=$OLD_PGENS&amp;show_full=$show_full&amp;box_width=$box_width&amp;chart_style=$chart_style", $gm_lang["ancestry_chart"], 3);
	print "</td>";
	print "<td class=\"details1\" style=\"vertical-align:middle;\">&nbsp;<span class=\"person_box". (($sosa==1) ? "NN" : (($sosa%2) ? "F" : "")) . "\">&nbsp;$sosa&nbsp;</span>&nbsp;";
	print "</td><td class=\"details1\" style=\"vertical-align:middle;\">";
	$relation ="";
	if (!$new) $relation = "<br />[=<a href=\"#sosa".$pidarr["$pid"]."\">".$pidarr["$pid"]."</a> - ".GetSosaName($pidarr["$pid"])."]";
	else $pidarr["$pid"]=$sosa;
	print GetSosaName($sosa).$relation;
	print "</td>";
	print "</tr></table>";

	// parents
	$famids = FindFamilyIds($pid);
	$famid = "";
	// Not now. If we let the primary family be leading here, we get foster ancestry, etc... :-(
//	foreach ($famids as $key => $ffamid) {
//		if ($ffamid[1] == "Y") {
//			$famid = @$famid[$key][0];
//			break;
//		}
//	}
	if (empty($famid)) $famid = @$famids[0]["famid"];
	$famrec = FindFamilyRecord($famid);
	$parents = @FindParents($famid);
	if (($parents or $SHOW_EMPTY_BOXES) and $new and $depth>0) {
		// print marriage info
		print "<span class=\"details1\" style=\"white-space: nowrap;\" >";
		print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" height=\"2\" width=\"$Dindent\" border=\"0\" align=\"middle\" alt=\"\" /><a href=\"javascript: ".$gm_lang["view_family"]."\" onclick=\"expand_layer('sosa_".$sosa."'); return false;\" class=\"top\"><img id=\"sosa_".$sosa."_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" align=\"middle\" hspace=\"0\" vspace=\"3\" border=\"0\" alt=\"".$gm_lang["view_family"]."\" /></a> ";
		print "&nbsp;<span class=\"person_box\">&nbsp;".($sosa*2)."&nbsp;</span>&nbsp;".$gm_lang["and"];
 		print "&nbsp;<span class=\"person_boxF\">&nbsp;".($sosa*2+1)." </span>&nbsp;";
		if (showFact("MARR", $famid)) print_simple_fact($famrec, "MARR", $parents["WIFE"]); else print $gm_lang["private"];
		print "</span>";
		// display parents recursively
		print "<ul style=\"list-style: none; display: block;\" id=\"sosa_$sosa\">";
		print_child_ascendancy($parents["HUSB"], $sosa*2, $depth-1);
		print_child_ascendancy($parents["WIFE"], $sosa*2+1, $depth-1);
		print "</ul>\r\n";
	}
	print "</li>\r\n";
}

// -- args

if (!isset($show_full)) $show_full = $PEDIGREE_FULL_DETAILS;
if ($show_full == "") $show_full = 0;
if (!isset($chart_style)) $chart_style = 0;
if ($chart_style=="") $chart_style = 0;
if (!isset($show_cousins)) $show_cousins = 0;
if ($show_cousins == "") $show_cousins = 0;
if ((!isset($PEDIGREE_GENERATIONS)) || ($PEDIGREE_GENERATIONS == "")) $PEDIGREE_GENERATIONS = $DEFAULT_PEDIGREE_GENERATIONS;

if ($PEDIGREE_GENERATIONS > $MAX_PEDIGREE_GENERATIONS) {
	$PEDIGREE_GENERATIONS = $MAX_PEDIGREE_GENERATIONS;
	$max_generation = true;
}

if ($PEDIGREE_GENERATIONS < 2) {
	$PEDIGREE_GENERATIONS = 2;
	$min_generation = true;
}
$OLD_PGENS = $PEDIGREE_GENERATIONS;

if (!isset($rootid)) $rootid = "";
$rootid = CleanInput($rootid);
$rootid = CheckRootId($rootid);

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

if (showLivingNameByID($rootid)) {
	$name = GetPersonName($rootid);
	$addname = GetAddPersonName($rootid);
}
else {
	$name = $gm_lang["private"];
	$addname = "";
}
// -- print html header information
$title = $name;
if ($SHOW_ID_NUMBERS) $title .= " - ".$rootid;
$title .= " - ".$gm_lang["ancestry_chart"];
print_header($title);
if (strlen($name)<30) $cellwidth="420";
else $cellwidth=(strlen($name)*14);
print "\n\t<table class=\"list_table $TEXT_DIRECTION\"><tr><td width=\"${cellwidth}\" valign=\"top\">\n\t\t";
if ($view == "preview") print "<h3>" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($PEDIGREE_GENERATIONS), $gm_lang["gen_ancestry_chart"]) . ":";
else print "<h3>" . $gm_lang["ancestry_chart"] . ":";
print "<br />".PrintReady($name);
if ($addname != "") print "<br />" . PrintReady($addname);
print "</h3>";
// -- print the form to change the number of displayed generations
if ($view != "preview") {
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
	if (isset($max_generation) == true) print "<span class=\"error\">" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($PEDIGREE_GENERATIONS), $gm_lang["max_generation"]) . "</span>";
	if (isset($min_generation) == true) print "<span class=\"error\">" . $gm_lang["min_generation"] . "</span>";
	print "\n\t</td><td><form name=\"people\" id=\"people\" method=\"get\" action=\"?\">";
	print "<input type=\"hidden\" name=\"chart_style\" value=\"$chart_style\" />";
	print "<input type=\"hidden\" name=\"show_full\" value=\"$show_full\" />";
	print "<input type=\"hidden\" name=\"show_cousins\" value=\"$show_cousins\" />";
	print "\n\t\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t";

	// NOTE: Root ID
	print "<tr><td class=\"shade2\">";
	print_help_link("rootid_help", "qm");
	print $gm_lang["root_person"]."&nbsp;</td>";
	print "<td class=\"shade1 vmiddle\">";
	print "<input class=\"pedigree_form\" type=\"text\" name=\"rootid\" id=\"rootid\" size=\"3\" value=\"$rootid\" />";
	PrintFindIndiLink("rootid","");
	print "</td>";

	// NOTE: Box width
	print "<td class=\"shade2\">";
	print_help_link("box_width_help", "qm");
	print $gm_lang["box_width"] . "&nbsp;</td>";
	print "<td class=\"shade1 vmiddle\"><input type=\"text\" size=\"3\" name=\"box_width\" value=\"$box_width\" /> <b>%</b>";
	print "</td>";

	// NOTE: chart style
	print "<td rowspan=\"2\" class=\"shade2\">";
	print_help_link("chart_style_help", "qm");
	print $gm_lang["displ_layout_conf"];
	print "</td>";
	print "<td rowspan=\"2\" class=\"shade1 vmiddle\">";
	print "<input type=\"radio\" name=\"chart_style\" value=\"0\"";
	if ($chart_style == "0") print " checked=\"checked\" ";
	print "onclick=\"toggleStatus('cousins');";
	if ($chart_style != "1") print " document.people.chart_style.value='1';";
	print "\" />".$gm_lang["chart_list"];
	print "<br /><input type=\"radio\" name=\"chart_style\" value=\"1\" ";
	if ($chart_style == "1") print "checked=\"checked\" ";
	print "onclick=\"toggleStatus('cousins');";
	if ($chart_style != "1") print " document.people.chart_style.value='0';";
	print "\" />".$gm_lang["chart_booklet"];

	// NOTE: show cousins
	print "<br />";
	print_help_link("show_cousins_help", "qm");
	print "<input ";
	if ($chart_style == "0") print "disabled=\"disabled\" ";
	print "id=\"cousins\" type=\"checkbox\" value=\"";
	if ($show_cousins) print "1\" checked=\"checked\" onclick=\"document.people.show_cousins.value='0';\"";
	else print "0\" onclick=\"document.people.show_cousins.value='1';\"";
	print " />" . $gm_lang["show_cousins"];
	print "</td>";

	// NOTE: submit
	print "<td rowspan=\"2\">";
	print "<input type=\"submit\"  value=\"".$gm_lang["view"]."\" />";
	print "</td></tr>";

	// NOTE: generations
	print "<tr><td class=\"shade2\">";
	print_help_link("PEDIGREE_GENERATIONS_help", "qm");
	print $gm_lang["generations"] . "&nbsp;</td>";

	print "<td class=\"shade1 vmiddle\">";
	print "<select name=\"PEDIGREE_GENERATIONS\">";
	for ($i=2; $i<=$MAX_PEDIGREE_GENERATIONS; $i++) {
	print "<option value=\"".$i."\"" ;
	if ($i == $OLD_PGENS) print " selected=\"selected\" ";
		print ">".$i."</option>";
	}
	print "</select>";
	
	print "</td>";
	
	
	
//	print "<td class=\"shade1 vmiddle\"><input type=\"text\" name=\"PEDIGREE_GENERATIONS\" size=\"3\" value=\"$OLD_PGENS\" /> ";
//	print "</td>";

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

if ($chart_style) {
	// first page : show indi facts
	print_pedigree_person($rootid, 2, false, 1);
	// expand the layer
	echo <<< END
	<script language="JavaScript" type="text/javascript">
	<!--
		expandbox("$rootid.1", 2);
	//-->
	</script>
	<br />
END;
	// process the tree
	$treeid = PedigreeArray($rootid);
	$treesize = pow(2, (int)($PEDIGREE_GENERATIONS))-1;
	for ($i = 0; $i < $treesize; $i++) {
		$pid = $treeid[$i];
		if ($pid) {
			$famids = FindFamilyIds($pid);
			$parents = @FindParents($famids[0]["famid"]);
			if ($parents) PrintSosaFamily($famids[0]["famid"], $pid, $i + 1);
			// show empty family only if it is the first and only one
			else if ($i == 0) PrintSosaFamily("", $pid, $i + 1);
		}
	}
}
else {
	$pidarr=array();
	print "<ul style=\"list-style: none; display: block;\" id=\"ancestry_chart".($TEXT_DIRECTION=="rtl" ? "_rtl" : "") ."\">\r\n";
	print_child_ascendancy($rootid, 1, $OLD_PGENS);
	print "</ul>";
	print "<br />";
}

print_footer();
?>
