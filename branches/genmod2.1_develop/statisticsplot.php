<?php
/**
 * Creates some statistics out of the GEDCOM information.
 * We will start with the following possibilities
 * number of persons -> periodes of 10 years from 1700-2010
 * age -> periodes of 10 years (different for 0-1,1-5,5-10,10-20 etc)
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @subpackage Lists
 * @version $Id: statisticsplot.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

//-- You should install JpGraph routines on your computer. I implemented them in genmod/modules/jpgraph/
//=== You have to check if the reference to internal subroutines (like plotmark.inc) has the right path
//=== I had to change them (by adding the directory) to jpgraph/plotmark.inc 
//-- Please check this with any availability test

//-- The info below comes from www.php.net when looking at functions

PlotFunctions::CheckPlotExtensions();

include ("modules/jpgraph/jpgraph.php");
include ("modules/jpgraph/jpgraph_line.php");
include ("modules/jpgraph/jpgraph_bar.php");


//--	========= start of main program =========
global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg,$key2ind;
global $legend, $xdata, $ydata, $xmax, $xgrenzen, $zmax, $zgrenzen, $xgiven,$zgiven, $percentage, $man_vrouw;
global $colors;

//-- Out of range values or no values entered; redirect tot statistics.php
if (!isset($x_as) || !isset($y_as) || !isset($z_as) || $x_as <  11 || $x_as >  21 || $y_as < 201 ||$y_as > 202 || $z_as < 300 ||$z_as > 302) {
	header("Location: statistics.php");
	exit;
}

$legend= array();
$xdata= array();
$ydata= array();
$xgrenzen= array();
$zgrenzen= array();
$famgeg = array();
$persgeg= array();
$key2ind= array();
$colors= array();

	if (count($colors) < 1) {
//LERMAN - with a maximum of 7 entries in the Z-axis (years), 8 colors are needed.
// This will automatically control the number of BarPlot objects created & passed to GroupBarPlot and calc_legend().
// Increasing the maximum entries will need more colors. First color is also used for male, second for female.
		$colors= array("blue","red","orange","brown","green","yellow","pink","magenta");
//Ideas for other ways of using colors. The trick is to make them (a) easily distinguishable and (b) not looking obnoxious
// 1) use the 200 names colors provided by JpGraph. These will not necessarily be (a)
//		$myrgb = new RGB();
//		$colors = array_keys($myrgb->rgb_table);
// If this "if" block were after the $graph was created, could do the following. However, $zmax would not be set up right.
//		$colors = array_keys($graph->img->rgb->rgb_table);
//		sort($colors);
// 2) Algorithmically create a pile of colors using pure PHP
// start with primary colors (male, female, other)
//		$colors[]="#0000ff";$colors[]="#ff0000";$colors[]="#00ff00";
// continuing on with some algorithm
// 3) tie into the themetable in jpgraph/src/jpgraph_pie.php
//	it uses the $colors array (from #1 above) indexed by value such as $colors(12);
	}

if (isset ($NEW_VALUE_ANGLE)) $value_angle = $NEW_VALUE_ANGLE;
else $value_angle = 0;

// Save the input variables
$savedInput = array();
$savedInput["x_as"] = $x_as;
$savedInput["y_as"] = $y_as;
$savedInput["z_as"] = $z_as;
$savedInput["xasGrLeeftijden"] = $xasGrLeeftijden;
$savedInput["xasGrMaanden"] = $xasGrMaanden;
$savedInput["xasGrAantallen"] = $xasGrAantallen;
$savedInput["zasGrPeriode"] = $zasGrPeriode;
$savedInput["showShadow"] = $showShadow;
$savedInput["valuePos"] = $valuePos;
$savedInput["graphSize"] = $graphSize;
$_SESSION[GedcomConfig::$GEDCOMID."statisticsplot"] = $savedInput;
unset($savedInput);

//--	print " sort, x_as:" . $x_as . ", y_as:". $y_as . ", x_as:". $z_as . ", xas_gr_leef:" . $xas_grenzen_leeftijden . ", xas_gr_maan:" . $xas_grenzen_maanden . ", xas_gr_aant:" . $xas_grenzen_aantallen . ", zas_gr_peri:" . $zas_grenzen_periode . "<BR>";

PrintHeader(GM_LANG_statistiek_list);
print "\n\t<div class=\"StatisticsPageTitle\"><span class=\"PageTitleName\">".GM_LANG_statistiek_list."</span></div>\n\t";

//--print ("aantal namen, families, male and female=".$nrpers . ":" . $nrfam . ":" $nrman . ":" $nrvrouw . "<BR>");

PlotFunctions::GetPlotData();

$xstr="";
$ystr="";
//regel 508
//-- Set params for request out of the information for plot
$g_xas= "1,2,3,4,5,6,7,8,9,10,11,12"; //should not be needed. but just for month
$xgl= $xasGrLeeftijden;
$xgm= $xasGrMaanden;
$xga= $xasGrAantallen;
$zgp= $zasGrPeriode;

//-- end of setting variables	

//---------nr,bron ,xgiven,zgiven,	title,      xtitle,   ytitle, grenzen_xas, grenzen-zas,,
//--print "true false". true .":" . false ."<br>";
PlotFunctions::SetParams(11,"IND", true,  false, "stat_11_mb",  "stplmonth", $y_as, $g_xas, $zgp,"Bimo");  //plot aantal geboorten per maand
PlotFunctions::SetParams(12,"IND", true,  false, "stat_12_md",  "stplmonth", $y_as, $g_xas, $zgp,"Demo");  //plot aantal overlijdens per maand
PlotFunctions::SetParams(13,"FAM", true,  false, "stat_13_mm",  "stplmonth", $y_as, $g_xas, $zgp,"Mamo");  //plot aantal huwelijken per maand
PlotFunctions::SetParams(14,"FAM", true,  false, "stat_14_mb1", "stplmonth", $y_as, $g_xas, $zgp,"Bimo1"); //plot aantal 1e geboorten per huwelijk per maand
PlotFunctions::SetParams(15,"FAM", true,  false, "stat_15_mm1", "stplmonth", $y_as, $g_xas, $zgp,"Mamo1"); //plot 1e huwelijken per maand
PlotFunctions::SetParams(16,"FAM", false, false, "stat_16_mmb", "stplmarrbirth",$y_as, $xgm,$zgp,"Mamam"); //plot tijd tussen 1e geboort en huwelijksdatum
PlotFunctions::SetParams(17,"IND", false, false, "stat_17_arb", "stplage",   $y_as, $xgl,   $zgp,"Agbi");  //plot leeftijd t.o.v. geboortedatum
PlotFunctions::SetParams(18,"IND", false, false, "stat_18_ard", "stplage",   $y_as, $xgl,   $zgp,"Agde");  //plot leeftijd t.o.v. overlijdensdatum
PlotFunctions::SetParams(19,"FAM", false, false, "stat_19_arm", "stplage",   $y_as, $xgl,   $zgp,"Agma");  //plot leeftijd op de huwelijksdatum
PlotFunctions::SetParams(20,"FAM", false, false, "stat_20_arm1","stplage",   $y_as, $xgl,   $zgp,"Agma1"); //plot leeftijd op de 1e huwelijksdatum
PlotFunctions::SetParams(21,"FAM", false, false, "stat_21_nok", "stplnumbers",$y_as,$xga,   $zgp,"Nuch");  //plot plot aantal kinderen in een maand

// Back button
if ($view !="preview") {
	print "<form method=\"post\" name=\"form\" action=\"statisticsplot.php\">";
	print "<div class=\"StatisticsPlotBackButton\"><input type=\"button\" value=\"".GM_LANG_back."\" onclick=\"location.href='statistics.php';\" />";
	print "</div></form>";
}

PrintFooter();
?>