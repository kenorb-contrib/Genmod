<?php
/**
 * Alive in year
 *
 * The individual list shows all individuals from a chosen gedcom file. The list is
 * setup in two sections. The alphabet bar and the details.
 *
 * The alphabet bar shows all the available letters users can click. The bar is built
 * up from the lastnames first letter. Added to this bar is the symbol @, which is
 * shown as a translated version of the variable <var>gm_lang["NN"]</var>, and a
 * translated version of the word ALL by means of variable <var>$gm_lang["all"]</var>.
 *
 * The details can be shown in two ways, with surnames or without surnames. By default
 * the user first sees a list of surnames of the chosen letter and by clicking on a
 * surname a list with names of people with that chosen surname is displayed.
 *
 * Beneath the details list is the option to skip the surname list or show it.
 * Depending on the current status of the list.
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
 * @version $Id: aliveinyear.php,v 1.4 2006/04/30 18:44:14 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Is the person alive in the given year
 *
 * Both the raw gedcom and the year to check
 * are passed to this function to see if the
 * person was alive in the given year.
 *
 * @author	GM Development Team
 * @param 	string 	$indirec	the persons raw gedcom record
 * @param 	int 		$year	the year to check if they are alive in
 * @return 	int		return 0 if the person is alive, negative number if they died earlier, positive number if they will be born in the future
 */
function check_alive($indirec, $year) {
	global $MAX_ALIVE_AGE;
	if (is_dead($indirec, $year)) return -1;

	//-- if death date after year return 0
	$deathrec = get_sub_record(1, "1 DEAT", $indirec);
	if (!empty($deathrec)) {
		$ct = preg_match("/\d DATE (.*)/", $deathrec, $match);
		if ($ct>0) {
			if (strstr($match[1], "@#DHEBREW@")===false) {
				$ddate = parse_date($match[1]);
				if ($year>$ddate[0]["year"]) {
					//print "A".$indirec;
					return -1;
				}
			}
		}
	}

	//-- if birthdate less than $MAX_ALIVE_AGE return false
	$birthrec = get_sub_record(1, "1 BIRT", $indirec);
	if (!empty($birthrec)) {
		$ct = preg_match("/\d DATE (.*)/", $birthrec, $match);
		if ($ct>0) {
			if (strstr($match[1], "@#DHEBREW@")===false) {
				$bdate = parse_date($match[1]);
				if ($year<$bdate[0]["year"]) {
					//print "B".$indirec;
					return 1;
				}
			}
		}
	}
	
	//-- check if year is between birth and death
	if (isset($bdate) && isset($ddate)) {
		//print "HERE $year>={$bdate[0]["year"]} && $year<={$ddate[0]["year"]}";
		if ($year>=$bdate[0]["year"] && $year<=$ddate[0]["year"]) {
			//print "C".$indirec;
			return 0;
		}
	}

	// If no death record than check all dates;
	$years = array();
	$subrecs = get_all_subrecords($indirec, "CHAN", true, true, false);
	foreach($subrecs as $ind=>$subrec) {
		$ct = preg_match("/\d DATE (.*)/", $subrec, $match);
		
		if ($ct>0 && strstr($match[0], "@#DHEBREW@")===false) {
			$bdate = parse_date($match[1]);
			if (!empty($bdate[0]["year"])) $years[] = $bdate[0]["year"];
		}
	}
	//print_r($years);
	if (count($years)>1) {
		//print "HERE $year>={$years[0]} && $year<={$years[count($years)-1]}";
		if ($year>=$years[0] && $year<=$years[count($years)-1]) {
			//print "E".$indirec;
			return 0;
		}
	}
	
	foreach($years as $ind=>$year1) {
		if (($year1-$year) > $MAX_ALIVE_AGE) {
			//print "F".$match[$i][0].$indirec;
			return -1;
		}
	}

	return 0;
}
//-- end functions

if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";
if (empty($show_all_firstnames)) $show_all_firstnames = "no";
if (empty($year)) $year=date("Y");

// Remove slashes
if (isset($alpha)) $alpha = stripslashes($alpha);
if (isset($surname)) $surname = stripslashes($surname);

print_header($gm_lang["alive_in_year"]);
print "<div class =\"center\">";
print "\n\t<h2>";
print_help_link("alive_in_year_help", "qm");
print str_replace("#YEAR#", $year, $gm_lang["is_alive_in"]);

print "</h2>";

if ($view != "preview") {
	print "\n\t<form name=\"newyear\" action=\"aliveinyear.php\" method=\"get\">";
	if (!empty($alpha)) print "\n\t\t<input type=\"hidden\" name=\"alpha\" value=\"$alpha\" />";
	if (!empty($surname)) print "\n\t\t<input type=\"hidden\" name=\"surname\" value=\"$surname\" />";
	print "\n\t\t<input type=\"hidden\" name=\"surname_sublist\" value=\"$surname_sublist\" />";
	print "\n\t\t<table class=\"list_table center $TEXT_DIRECTION\">\n\t\t\t<tr>";
	print "\n\t\t\t<td class=\"shade1\">";
	print_help_link("year_help", "qm");
	print $gm_lang["year"]."</td>";
	print "\n\t\t\t<td class=\"shade2\">";
	print "\n\t\t\t\t<input class=\"pedigree_form\" type=\"text\" name=\"year\" size=\"3\" value=\"$year\" />";
	print "\n\t\t\t\t";
	print "\n\t\t\t</td>";
	print "\n\t\t\t<td rowspan=\"3\" class=\"shade3\">";
	print "<input type=\"submit\" value=\"".$gm_lang["view"]."\" /></td>";
	print "\n\t\t\t</tr>\n\t\t</table>";
	print "\n\t</form>\n";
	print "<br />";
}

/**
 * Check for the @ symbol
 *
 * This variable is used for checking if the @ symbol is present in the alphabet list.
 * @global boolean $pass
 */
$pass = FALSE;

/**
 * Total indilist array
 *
 * The tindilist array will contain individuals that are extracted from the database.
 * @global array $tindilist
 */
$tindilist = array();

/**
 * Individual alpha array
 *
 * The indialpha array will contain all first letters that are extracted from an individuals
 * lastname.
 * @global array $indialpha
 */
$indialpha = GetIndiAlpha();

uasort($indialpha, "stringsort");

if (isset($alpha) && !isset($indialpha["$alpha"])) unset($alpha);
print_help_link("alpha_help", "qm");
if (count($indialpha) > 0) {
	foreach($indialpha as $letter=>$list) {
		if (empty($alpha)) {
			if (!empty($surname)) {
				$alpha = get_first_letter(strip_prefix($surname));
			}
		}
		if ($letter != "@") {
			if (!isset($startalpha) && !isset($alpha)) {
				$startalpha = $letter;
				$alpha = $letter;
			}
			print "<a href=\"aliveinyear.php?year=$year&amp;alpha=".urlencode($letter)."&amp;surname_sublist=$surname_sublist\">";
			if (($alpha==$letter)&&($show_all=="no")) print "<span class=\"warning\">".$letter."</span>";
			else print $letter;
			print "</a> | \n";
		}
		if ($letter === "@") $pass = TRUE;
	}
	if ($pass == TRUE) {
		if (isset($alpha) && $alpha == "@") print "<a href=\"aliveinyear.php?year=$year&amp;alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
		else print "<a href=\"aliveinyear.php?year=$year&amp;alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.\">".PrintReady($gm_lang["NN"])."</a>";
		print " | \n";
		$pass = FALSE;
	}
	if ($show_all=="yes") print "<a href=\"aliveinyear.php?year=$year&amp;show_all=yes&amp;surname_sublist=$surname_sublist\"><span class=\"warning\">".$gm_lang["all"]."</span>\n";
	else print "<a href=\"aliveinyear.php?year=$year&amp;show_all=yes&amp;surname_sublist=$surname_sublist\">".$gm_lang["all"]."</a>\n";
	if (isset($startalpha)) $alpha = $startalpha;
}

// NOTE: Escaped letter for regular expressions
$expalpha = $alpha;
if ($expalpha=="(" || $expalpha=="[" || $expalpha=="?" || $expalpha=="/" || $expalpha=="*" || $expalpha=="+") $expalpha = "\\".$expalpha;

print "<br />";
print_help_link("name_list_help", "qm");

print "<br /><table class=\"list_table $TEXT_DIRECTION\"><tr>";
if (($surname_sublist=="yes")&&($show_all=="yes")) {
	GetIndiList();
	if (!isset($alpha)) $alpha="";
	$surnames = array();
	$indi_hide=array();
	$indi_dead=0;
	$indi_unborn=0;
	$indi_alive = 0;
	foreach($indilist as $gid=>$indi) {
		// NOTE: Make sure that favorites from other gedcoms are not shown
		if ($indi["gedfile"]==$GEDCOMS[$GEDCOM]["id"]) {
			if (displayDetailsById($gid)||showLivingNameById($gid)) {
				$ret = check_alive($indi["gedcom"], $year);
				if ($ret==0) {
					foreach($indi["names"] as $indexval => $name) {
						surname_count($name[2]);
						$indi_alive++;
					}
				}
				else if ($ret<0) $indi_dead++;
				else $indi_unborn++;
			}
			else $indi_hide[$gid."[".$indi["gedfile"]."]"]=1;
		}
	}
	$i = 0;
	uasort($surnames, "itemsort");
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else if (($surname_sublist=="yes")&&(empty($surname))&&($show_all=="no")) {
	if (!isset($alpha)) $alpha="";
	$tindilist=array();
	// NOTE: Get all of the individuals whose last names start with this letter
	$tindilist = GetAlphaIndis($alpha);
	$surnames = array();
	$indi_hide=array();
	$indi_dead=0;
	$indi_unborn=0;
	$indi_alive = 0;
	$temp = 0;
	$surnames = array();
	foreach($tindilist as $gid=>$indi) {
		if ((displayDetailsByID($gid))||(showLivingNameById($gid))) {
			$ret = check_alive($indi["gedcom"], $year);
			if ($ret==0) {
				$indi_alive++;
				foreach($indi["names"] as $name) {
					if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
						if ($alpha == "Ø") $text = "OE";
						else if ($alpha == "Æ") $text = "AE";
						else if ($alpha == "Å") $text = "AA";
						if (isset($text)) {
							if ((preg_match("/^$expalpha/", $name[1])>0)||(preg_match("/^$text/", $name[1])>0)) surname_count($name[2]);
						}
						else if (preg_match("/^$expalpha/", $name[1])>0) surname_count($name[2]);
					}
					else {
						if (preg_match("/^$expalpha/", $name[1])>0) surname_count($name[2]);
					}
				}
			}
			else if ($ret<0) $indi_dead++;
			else $indi_unborn++;
		}
		else $indi_hide[$gid."[".$indi["gedfile"]."]"]=1;
	}
	$i = 0;
	uasort($surnames, "itemsort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else {
	$firstname_alpha = false;
	$indi_dead=0;
	$indi_unborn=0;
	// NOTE: If the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$surname = trim($surname);
		$tindilist = get_surname_indis($surname);
	}
	
	if (($surname_sublist=="no")&&(!empty($alpha))&&($show_all=="no")) $tindilist = GetAlphaIndis($alpha);
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if (($surname_sublist=="no")&&($show_all=="yes")) {
		$tindilist = GetIndiList();
		PrintPersonList($tindilist);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		print "<div class=\"topbar\">";
		if ($surname_sublist == "no") print $gm_lang["surnames"];
		else	print PrintReady(str_replace("#surname#", check_NN($surname), $gm_lang["indis_with_surname"]));
		print "</div>\n";
		PrintPersonList($tindilist);
	}
}
print "</tr></table>";
if ($alpha != "@") {
	print_help_link("skip_sublist_help", "qm");
	if ($surname_sublist=="yes") print "<a href=\"aliveinyear.php?year=$year&amp;alpha=$alpha&amp;surname_sublist=no&amp;show_all=$show_all\">".$gm_lang["skip_surnames"]."</a>";
	else print "<a href=\"aliveinyear.php?year=$year&amp;alpha=$alpha&amp;surname_sublist=yes&amp;show_all=$show_all\">".$gm_lang["show_surnames"]."</a>";
}
print "</div>\n";
print_footer();

?>