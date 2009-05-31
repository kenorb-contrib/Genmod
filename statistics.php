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
 * @version $Id: statistics.php,v 1.17 2009/03/25 16:53:52 sjouke Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");
require_once($GM_BASE_DIRECTORY."includes/functions/functions_plot.php");


//--	========= start of main program =========

$famgeg = array();
$persgeg= array();
$famgeg1 = array();
$persgeg1= array();
$key2ind= array();
$match1= array();
$match2= array();

if (isset($cleanup) && $cleanup == "yes") unset($_SESSION[$GEDCOM."statisticsplot"]);

global $nrfam, $famgeg, $nrpers, $persgeg,$key2ind,$nrman,$nrvrouw;
global $match1,$match2;

print_header($gm_lang["statistics"]);
//-- You should install JpGraph routines on your computer. I implemented them in genmod/modules/jpgraph
//-- Please check this with any availability test

//-- The info below comes from www.php.net when looking at functions

$mypath= ini_get("include_path");
//--	On some servers the include path does not support the (current) directory from the calling routine
//--	ini_set("include_path", $mypath);


CheckPlotExtensions();

GetPlotData();

print "\n\t<center><h2>".$gm_lang["statistiek_list"]."</h2>\n\t";
print "</center>";
print "<form method=\"post\" name=\"form\" action=\"statisticsplot.php\">";
print "<table class=\"facts_table width60 center $TEXT_DIRECTION\">";

// statistics
print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["statistics"].print_help_link("stat_help","qm", "", false, true)."</td></tr>";
print "<tr><td class=\"shade2 wrap width30 vmiddle\">".$gm_lang["statnnames"]."</td><td class=\"shade1\">".$nrpers."</td></tr>";
print "<tr><td class=\"shade2 wrap width30 vmiddle\">".$gm_lang["statnfam"]."</td><td class=\"shade1\">".$nrfam."</td></tr>";
print "<tr><td class=\"shade2 wrap width30 vmiddle\">".$gm_lang["statnmale"]."</td><td class=\"shade1\">".$nrman."</td></tr>";
print "<tr><td class=\"shade2 wrap width30 vmiddle\">".$gm_lang["statnfemale"]."</td><td class=\"shade1\">".$nrvrouw."</td></tr>";

//if (!isset($plottype)) $plottype=0;
if (isset($_SESSION[$GEDCOM."statisticsplot"])) {
	foreach ($_SESSION[$GEDCOM."statisticsplot"] as $name => $value) {
		$$name = $value;
	}
}
else {
	//$xasGrLeeftijden = "1,5,10,20,30,40,50,60,70,80,90";
	$xasGrLeeftijden = "1,5,10,20,30,40,50,60,70,80,90,100";
	$xasGrMaanden = "-24,-12,0,8,12,18,24,48";
	$xasGrAantallen = "1,2,3,4,5,6,7,8,9,10";
	// A maximum of 7 entries will work with statisticsplot.php as is
	$zasGrPeriode = "1800,1900,1950,1980"; 
	//$zasGrPeriode = "1700,1750,1800,1850,1900,1950,2000";
	$showShadow = "yes";
	$valuePos = "top";
	$graphSize = "autoWindow";
	$x_as = "11";
	$y_as = "201";
	$z_as = "302";
}

// plotting variables
print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["statvars"]."</td></tr>";

print "<tr><td class=\"shade2 wrap width50 vmiddle\">";
print $gm_lang["statlxa"];
print "</td><td class=\"shade1\">";
print "<select name=\"x_as\">";
print "<option value= \"11\" "; if ($x_as == "11") print "selected=\"selected\""; print">".$gm_lang["stat_11_mb"]; print "</option>";
print "<option value= \"12\" "; if ($x_as == "12") print "selected=\"selected\""; print">".$gm_lang["stat_12_md"]; print "</option>";
print "<option value= \"13\" "; if ($x_as == "13") print "selected=\"selected\""; print">".$gm_lang["stat_13_mm"]; print "</option>";
print "<option value= \"14\" "; if ($x_as == "14") print "selected=\"selected\""; print">".$gm_lang["stat_14_mb1"]; print "</option>";
print "<option value= \"15\" "; if ($x_as == "15") print "selected=\"selected\""; print">".$gm_lang["stat_15_mm1"]; print "</option>";
print "<option value= \"16\" "; if ($x_as == "16") print "selected=\"selected\""; print">".$gm_lang["stat_16_mmb"]."&nbsp;<i>".$gm_lang["stat_gmx"]."</i>"; print "</option>";
print "<option value= \"17\" "; if ($x_as == "17") print "selected=\"selected\""; print">".$gm_lang["stat_17_arb"]."&nbsp;<i>".$gm_lang["stat_gax"]."</i>"; print "</option>";
print "<option value= \"18\" "; if ($x_as == "18") print "selected=\"selected\""; print">".$gm_lang["stat_18_ard"]."&nbsp;<i>".$gm_lang["stat_gax"]."</i>"; print "</option>";
print "<option value= \"19\" "; if ($x_as == "19") print "selected=\"selected\""; print">".$gm_lang["stat_19_arm"]."&nbsp;<i>".$gm_lang["stat_gax"]."</i>"; print "</option>";
print "<option value= \"20\" "; if ($x_as == "20") print "selected=\"selected\""; print">".$gm_lang["stat_20_arm1"]."&nbsp;<i>".$gm_lang["stat_gax"]."</i>"; print "</option>";
print "<option value= \"21\" "; if ($x_as == "21") print "selected=\"selected\""; print">".$gm_lang["stat_21_nok"]."&nbsp;<i>".$gm_lang["stat_gnx"]."</i>"; print "</option>";
print "</select></td></tr>";

print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["statlya"];
print "</td><td class=\"shade1\">";
print "<select name=\"y_as\">";
print "<option value= \"201\" "; if ($y_as == "201") print "selected=\"selected\""; print">".$gm_lang["stat_201_num"]; print "</option>";
print "<option value= \"202\" "; if ($y_as == "202") print "selected=\"selected\""; print">".$gm_lang["stat_202_perc"]; print "</option>";
print "</select></td></tr>";

print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["statlza"];
print "</td><td class=\"shade1\">";
print "<select name=\"z_as\">";
print "<option value= \"300\" "; if ($z_as == "300") print "selected=\"selected\""; print">".$gm_lang["stat_300_none"]; print "</option>";
print "<option value= \"301\" "; if ($z_as == "301") print "selected=\"selected\""; print">".$gm_lang["stat_301_mf"]; print "</option>";
print "<option value= \"302\" "; if ($z_as == "302") print "selected=\"selected\""; print">".$gm_lang["stat_302_cgp"]; print "</option>";
print "</select></td></tr>";

// tickvalues
print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["statmess1"]."</td></tr>";
print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["statar_xgl"]."</td>";
print "<td class=\"shade1\">";
print "<input type=\"text\" name=\"xasGrLeeftijden\" value=\"".$xasGrLeeftijden."\" size=\"60\" onfocus=\"getHelp('periode_help');\" />";
print "</td></tr>";

print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["statar_xgm"]."</td>";
print "<td class=\"shade1\">";
print "<input type=\"text\" name=\"xasGrMaanden\" value=\"".$xasGrMaanden."\" size=\"60\" onfocus=\"getHelp('periode_help');\" />";
print "</td></tr>";

print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["statar_xga"]."</td>";
print "<td class=\"shade1\">";
print "<input type=\"text\" name=\"xasGrAantallen\" value=\"".$xasGrAantallen."\" size=\"60\" onfocus=\"getHelp('periode_help');\" />";
print "</td></tr>";

print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["statar_zgp"]."</td>";
print "<td class=\"shade1\">";
print "<input type=\"text\" name=\"zasGrPeriode\" value=\"".$zasGrPeriode."\" size=\"60\" onfocus=\"getHelp('periode_help');\" />";
print "</td></tr>";

// Options
print "<tr><td class=\"topbottombar\" colspan=\"2\">"."Options"/*$gm_lang["statmess1"]*/."</td></tr>";
print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["pl_shadow"]."</td>";
print "<td class=\"shade1\"><select name=\"showShadow\">";
	print "<option value=\"yes\" ";if ($showShadow=="yes") print "selected=\"selected\""; print ">".$gm_lang["yes"]."</option>";
	print "<option value=\"no\" ";if ($showShadow=="no") print "selected=\"selected\""; print ">".$gm_lang["no"]."</option>";
print "</select></td></tr>";

print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["pl_val"]."</td>";
print "<td class=\"shade1\"><select name=\"valuePos\">";
	print "<option value=\"none\" ";if ($valuePos=="none") print "selected=\"selected\""; print ">".$gm_lang["none"]."</option>";
	print "<option value=\"top\" ";if ($valuePos=="top") print "selected=\"selected\""; print ">".$gm_lang["top"]."</option>";
	print "<option value=\"center\" ";if ($valuePos=="center") print "selected=\"selected\""; print ">".$gm_lang["center"]."</option>";
	print "<option value=\"bottom\" ";if ($valuePos=="bottom") print "selected=\"selected\""; print ">".$gm_lang["bottom"]."</option>";
print "</select></td></tr>";

print "<tr><td class=\"shade2 wrap vmiddle\">";
print $gm_lang["pl_size"]."</td>";
print "<td class=\"shade1\"><select name=\"graphSize\">";
	print "<option value=\"autoScreen\" ";if ($graphSize=="autoScreen") print "selected=\"selected\""; print ">".$gm_lang["pl_scr"]."</option>";
	print "<option value=\"autoWindow\" ";if ($graphSize=="autoWindow") print "selected=\"selected\""; print ">".$gm_lang["pl_win"]."</option>";
	print "<option value=\"700x400\" ";if ($graphSize=="700x400") print "selected=\"selected\""; print ">".$gm_lang["pl_std"]."</option>";
	print "<option value=\"1050x600\" ";if ($graphSize=="1050x600") print "selected=\"selected\""; print ">".$gm_lang["pl_large"]."</option>";
            print "<option value=\"1400x800\" ";if ($graphSize=="1400x800") print "selected=\"selected\""; print ">".$gm_lang["pl_xlarge"]."</option>";
    print "</select>";
print "<input type=\"hidden\" name=\"screenRes\" value=\"\" size=\"10\" />";
print "<input type=\"hidden\" name=\"windowRes\" value=\"\" size=\"10\" />";
print "</td></tr>";

// Submit bar
print "<tr><td class=\"topbottombar\" colspan=\"2\">";
	print "<input type=\"submit\" value=\"".$gm_lang["statsubmit"]."\" onclick=\"document.form.screenRes.value=screen.width+'x'+screen.height;document.form.windowRes.value=document.body.clientWidth+'x'+document.body.clientHeight;closeHelp();\" />&nbsp;&nbsp;&nbsp;&nbsp;";
	print "<input type=\"button\" value=\"".$gm_lang["statreset"]."\" onclick=\"location.href='statistics.php?cleanup=yes'; return false;\" />";
print "</td></tr></table>";
print "</form>";


//--print "plottype=".$plottype."<br>";
//$_SESSION["plottype"]=$plottype;

print "<br />";
print_footer();

?>
