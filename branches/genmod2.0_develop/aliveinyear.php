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
 * translated version of the word ALL by means of variable <var>GM_LANG_all"]</var>.
 *
 * The details can be shown in two ways, with surnames or without surnames. By default
 * the user first sees a list of surnames of the chosen letter and by clicking on a
 * surname a list with names of people with that chosen surname is displayed.
 *
 * Beneath the details list is the option to skip the surname list or show it.
 * Depending on the current status of the list.
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
 * @package Genmod
 * @subpackage Lists
 * @version $Id$
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
 * @return 	int		return 0 if the person is alive, positive number if they died earlier, negative number if they will be born in the future
 */
function check_alive($indirec, $year, $type, $useMAA=false) {
	global $MAX_ALIVE_AGE;

	$bddates = estimateBD($indirec, $type);
	// First check if we must assume something
	if ($useMAA) {
		if (isset($bddates["birth"]["year"]) && $bddates["birth"]["type"] == "true" && (!isset($bddates["death"]["year"]) || $bddates["death"]["type"] != "true")) {
			$bddates["death"]["year"] = $bddates["birth"]["year"] + $MAX_ALIVE_AGE;
			$bddates["death"]["type"] = "est";
		}
		if (isset($bddates["death"]["year"]) && $bddates["death"]["type"] == "true" && (!isset($bddates["birth"]["year"]) || $bddates["birth"]["type"] != "true")) {
			$bddates["birth"]["year"] = $bddates["death"]["year"] + $MAX_ALIVE_AGE;
			$bddates["birth"]["type"] = "est";
		}
	}
	
	// For sure born after
	if (isset($bddates["birth"]["year"]) && $bddates["birth"]["year"] > $year) return -1;
	
	// For sure died before
	if (isset($bddates["death"]["year"]) && $bddates["death"]["year"] < $year) return 1;
	
	// For sure lived in that year
	if (isset($bddates["death"]["year"]) && isset($bddates["birth"]["year"]) && $bddates["birth"]["year"] <= $year && $bddates["death"]["year"] >= $year) return 0;
	
	// All else don't know, we assume not living
	return -1;
	
}
//-- end functions

if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";
if (empty($show_all_firstnames)) $show_all_firstnames = "no";
if (empty($year)) $year=date("Y");

// Type can be either "true", "wide" or "narrow"
if (!isset($type)) $type = "narrow";
if (!isset($useMAA)) $useMAA = 0;

// Remove slashes
if (isset($alpha)) $alpha = stripslashes($alpha);
if (isset($surname)) $surname = stripslashes($surname);

PrintHeader(GM_LANG_alive_in_year);
print "<div class =\"center\">";
print "\n\t<h3>";
print_help_link("alive_in_year_help", "qm");
print str_replace("#YEAR#", $year, GM_LANG_is_alive_in);

print "</h3>";

if ($view != "preview") {
	print "\n\t<form name=\"newyear\" action=\"aliveinyear.php\" method=\"get\">";
	if (!empty($alpha)) print "\n\t\t<input type=\"hidden\" name=\"alpha\" value=\"$alpha\" />";
	if (!empty($surname)) print "\n\t\t<input type=\"hidden\" name=\"surname\" value=\"$surname\" />";
	print "\n\t\t<input type=\"hidden\" name=\"surname_sublist\" value=\"$surname_sublist\" />";
	print "\n\t\t<input type=\"hidden\" name=\"show_all\" value=\"$show_all\" />";
	print "\n\t\t<table class=\"list_table center $TEXT_DIRECTION\">\n\t\t\t<tr>";
	print "\n\t\t\t<td class=\"shade3 center\" colspan=\"4\">".GM_LANG_choose."</td></tr>";
	print "<tr><td class=\"shade1\" rowspan=\"4\" style=\"vertical-align: middle; text-align: center; padding: 5px;\" >";
	print_help_link("year_help", "qm");
	print GM_LANG_year."</td>";
	print "\n\t\t\t<td class=\"shade2\" rowspan=\"4\" style=\"vertical-align: middle;\" >";
	print "\n\t\t\t\t<input class=\"pedigree_form\" type=\"text\" name=\"year\" size=\"3\" value=\"$year\" />";
	print "\n\t\t\t\t";
	print "\n\t\t\t</td>";
	print "<td class=\"shade1\">".GM_LANG_aiy_usemaa."</td>";
	print "<td class=\"shade2\" style=\"vertical-align: middle;\"><input type=\"checkbox\" name=\"useMAA\" value=\"1\" onclick=\"submit()\"";
	if ($useMAA == "1") print " checked=\"checked\"";
	print " /></td></tr>";
	print "<tr><td class=\"shade1\">".GM_LANG_aiy_trueyears."</td>";
	print "<td class=\"shade2\" style=\"vertical-align: middle;\"><input type=\"radio\" name=\"type\" value=\"true\" onclick=\"submit()\"";
	if ($type == "true") print " checked=\"checked\"";
	print " /></td></tr>";
	print "<tr><td class=\"shade1\">".GM_LANG_aiy_narrowyears."</td>";
	print "<td class=\"shade2\" style=\"vertical-align: middle;\"><input type=\"radio\" name=\"type\" value=\"narrow\" onclick=\"submit()\"";
	if ($type == "narrow") print " checked=\"checked\"";
	print " /></td></tr>";
	print "<tr><td class=\"shade1\">".GM_LANG_aiy_wideyears."</td>";
	print "<td class=\"shade2\" style=\"vertical-align: middle;\"><input type=\"radio\" name=\"type\" value=\"wide\" onclick=\"submit()\"";
	if ($type == "wide") print " checked=\"checked\"";
	print " /></td></tr>";
	print "\n\t\t\t<tr><td colspan=\"4\" class=\"center\">";
	print "<input type=\"submit\" value=\"".GM_LANG_view."\" /></td>";
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

uasort($indialpha, "StringSort");

if (isset($alpha) && !isset($indialpha["$alpha"])) unset($alpha);
print_help_link("alpha_help", "qm");
if (count($indialpha) > 0) {
	foreach($indialpha as $letter=>$list) {
		if (empty($alpha)) {
			if (!empty($surname)) {
				$alpha = GetFirstLetter(StripPrefix($surname));
			}
		}
		if ($letter != "@") {
			if (!isset($startalpha) && !isset($alpha)) {
				$startalpha = $letter;
				$alpha = $letter;
			}
			print "<a href=\"aliveinyear.php?year=$year&amp;alpha=".urlencode($letter)."&amp;surname_sublist=$surname_sublist&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year\">";
			if (($alpha==$letter)&&($show_all=="no")) print "<span class=\"warning\">".$letter."</span>";
			else print $letter;
			print "</a> | \n";
		}
		if ($letter === "@") $pass = TRUE;
	}
	if ($pass == TRUE) {
		if (isset($alpha) && $alpha == "@") print "<a href=\"aliveinyear.php?year=$year&amp;alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year\"><span class=\"warning\">".PrintReady(GM_LANG_NN)."</span></a>";
		else print "<a href=\"aliveinyear.php?year=$year&amp;alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year\">".PrintReady(GM_LANG_NN)."</a>";
		print " | \n";
		$pass = FALSE;
	}
	if ($show_all=="yes") print "<a href=\"aliveinyear.php?year=$year&amp;show_all=yes&amp;surname_sublist=$surname_sublist&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year\"><span class=\"warning\">".GM_LANG_all."</span>\n";
	else print "<a href=\"aliveinyear.php?year=$year&amp;show_all=yes&amp;surname_sublist=$surname_sublist&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year\">".GM_LANG_all."</a>\n";
	if (isset($startalpha)) $alpha = $startalpha;
}

// NOTE: Escaped letter for regular expressions
$expalpha = $alpha;
if ($expalpha=="(" || $expalpha=="[" || $expalpha=="?" || $expalpha=="/" || $expalpha=="*" || $expalpha=="+") $expalpha = "\\".$expalpha;

print "<br />";
print_help_link("name_list_help", "qm");

print "<br /><table class=\"list_table $TEXT_DIRECTION\"><tr>";
if (($surname_sublist=="yes")&&($show_all=="yes")) {
	GetIndiList("no");
	if (!isset($alpha)) $alpha="";
	$surnames = array();
	$indi_hide=array();
	$indi_dead=0;
	$indi_unborn=0;
	$indi_alive = 0;
	foreach($indilist as $gid=>$indi) {
		// NOTE: Make sure that favorites from other gedcoms are not shown
		if ($indi["gedfile"]==$GEDCOMID) {
			if (PrivacyFunctions::showLivingNameById($gid)) {
				$ret = check_alive($indi["gedcom"], $year, $type, $useMAA);
				if ($ret==0) {
					foreach($indi["names"] as $indexval => $name) {
						ListFunctions::SurnameCount($name[2]);
						$indi_alive++;
					}
				}
				else if ($ret>0) $indi_dead++;
				else $indi_unborn++;
			}
			else $indi_hide[$gid."[".$indi["gedfile"]."]"]=1;
		}
	}
	$i = 0;
	uasort($surnames, "ItemSort");
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"], "no", "&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year");
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
		$indi = $indilist[$gid];
		if (PrivacyFunctions::showLivingNameById($gid)) {
			$ret = check_alive($indi["gedcom"], $year, $type, $useMAA);
			if ($ret==0) {
				$indi_alive++;
				foreach($indi["names"] as $name) {
					if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
						if ($alpha == "Ø") $text = "OE";
						else if ($alpha == "Æ") $text = "AE";
						else if ($alpha == "Å") $text = "AA";
						if (isset($text)) {
							if ((preg_match("/^$expalpha/", $name[1])>0)||(preg_match("/^$text/", $name[1])>0)) ListFunctions::SurnameCount($name[2], $alpha);
						}
						else if (preg_match("/^$expalpha/", $name[1])>0) ListFunctions::SurnameCount($name[2], $alpha);
					}
					else {
						if (preg_match("/^$expalpha/", $name[1])>0) ListFunctions::SurnameCount($name[2], $alpha);
					}
				}
			}
			else if ($ret>0) $indi_dead++;
			else $indi_unborn++;
		}
		else $indi_hide[$gid."[".$indi["gedfile"]."]"]=1;
	}
	$i = 0;
	uasort($surnames, "ItemSort");
	print "<td class=\"topbar\">".GM_LANG_surnames."</td>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"], "no", "&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year&amp;show_all=$show_all");
}
else {
	$firstname_alpha = false;
	$indi_dead=0;
	$indi_unborn=0;
	$indi_alive=0;
	// NOTE: If the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$surname = trim($surname);
		$tindilist = GetSurnameIndis($surname);
	}
	if (($surname_sublist=="no")&&(!empty($alpha))&&($show_all=="no")) $tindilist = GetAlphaIndis($alpha);
	foreach($tindilist as $gid=>$indi) {
		$indi = $indilist[$gid];
		if (!PrivacyFunctions::showLivingNameById($gid)) {
			unset($tindilist[$gid]);
			$indi_hide[$gid."[".$indi["gedfile"]."]"]=1;
		}
		else {
			$ret = check_alive($indi["gedcom"], $year, $type, $useMAA);
			if ($ret==0) $indi_alive++;
			else if ($ret>0) {
				$indi_dead++;
				unset($tindilist[$gid]);
			}
			else {
				$indi_unborn++;
				unset($tindilist[$gid]);
			}
		}
	}
		
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if (($surname_sublist=="no")&&($show_all=="yes")) {
		$tindilist = GetIndiList("no");
		foreach($tindilist as $gid=>$indi) {
			if (!PrivacyFunctions::showLivingNameById($gid)) {
				unset($tindilist[$gid]);
				$indi_hide[$gid."[".$indi["gedfile"]."]"]=1;
			}
			else {
				$ret = check_alive($indi["gedcom"], $year, $type, $useMAA);
				if ($ret==0) $indi_alive++;
				else if ($ret<0) {
					$indi_dead++;
					unset($tindilist[$gid]);
				}
				else {
					$indi_unborn++;
					unset($tindilist[$gid]);
				}
			}
		}
		PrintPersonList($tindilist);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		print "<td class=\"topbar\">";
		if ($surname_sublist == "no") print GM_LANG_surnames;
		else	print PrintReady(str_replace("#surname#", CheckNN($surname), GM_LANG_indis_with_surname));
		print "</td>\n";
		PrintPersonList($tindilist);
	}
}
print "</tr></table>";
if ($alpha != "@") {
	print_help_link("skip_sublist_help", "qm");
	if ($surname_sublist=="yes") print "<a href=\"aliveinyear.php?year=$year&amp;alpha=".urlencode($alpha)."&amp;surname_sublist=no&amp;show_all=$show_all&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year\">".GM_LANG_skip_surnames."</a>";
	else print "<a href=\"aliveinyear.php?year=$year&amp;alpha=".urlencode($alpha)."&amp;surname_sublist=yes&amp;show_all=$show_all&amp;type=$type&amp;useMAA=$useMAA&amp;year=$year\">".GM_LANG_show_surnames."</a>";
}
print "</div>\n";
PrintFooter();
?>