<?php
/**
 * Family List
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2008 Genmod Development Team
 *
 * The Family list shows all families from a chosen gedcom file. The list is
 * setup in two sections. The alphabet bar and the details.
 *
 * The alphabet bar shows all the available letters users can click. The bar is built
 * up from the lastnames' first letter. Added to this bar is the symbol @, which is
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
 * This Page Is Valid XHTML 1.0 Transitional! > 24 August 2005
 *
 * @package Genmod
 * @subpackage Lists
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$COMBIKEY = true;

if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";
if (!isset($allgeds) || $allgeds != "yes" || !$ALLOW_CHANGE_GEDCOM) $allgeds = "no";
// Added if any of the gedcoms require authentication and the user is not logged on, we cannot do allgeds.
if (!isset($allgeds) || $allgeds != "yes" || !$ALLOW_CHANGE_GEDCOM) $allgeds = "no";
if ($allgeds == "yes" && empty($gm_user->username)) {
	foreach($GEDCOMS as $key => $ged) {
		SwitchGedcom($key);
		if (GedcomConfig::$REQUIRE_AUTHENTICATION) $allgeds = "no";
	}
	SwitchGedcom();
}

// Remove slashes
$addheader = "";
if (empty($show_all_firstnames)) $show_all_firstnames = "no";
if (isset($alpha)) {
	$alpha = stripslashes($alpha);
	$addheader = "(".$alpha.")";
}
if (isset($surname)) {
	$surname = stripslashes($surname);
	$addheader = "(".CheckNN($surname).")";
}

PrintHeader($gm_lang["family_list"]." ".$addheader);
print "<div class =\"center\">";
print "\n\t<h3>";
print_help_link("name_list_help", "qm", "name_list");
print $gm_lang["family_list"]."</h3>";

/**
 * Check for the @ symbol
 *
 * This variable is used for checking if the @ symbol is present in the alphabet list.
 * @var boolean $pass
 */
$pass = FALSE;

/**
 * Total famlist array
 *
 * The tfamlist array will contain families that are extracted from the database.
 * @var array $tfamlist
 */
$tfamlist = array();

/**
 * Family alpha array
 *
 * The famalpha array will contain all first letters that are extracted from families last names
 * @var array $famalpha
 */

$famalpha = GetFamAlpha($allgeds);

uasort($famalpha, "stringsort");

if (isset($alpha) && !isset($famalpha["$alpha"])) unset($alpha);

$TableTitle = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".$gm_lang["families"]."\" alt=\"".$gm_lang["families"]."\" />&nbsp;&nbsp;";

if (count($famalpha) > 0) {
	print_help_link("alpha_help", "qm", "alpha_index");
	foreach($famalpha as $letter=>$list) {
		if (empty($alpha)) {
			if (!empty($surname)) {
				if (GedcomConfig::$USE_RTL_FUNCTIONS && isRTLText($surname)) $alpha = substr(StripPrefix($surname),0,2);
				else $alpha = substr(StripPrefix($surname),0,1);
			}
		}
		if ($letter != "@") {
			print "<a href=\"famlist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$surname_sublist;
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
			if (isset($alpha) && ($alpha==$letter)&&($show_all=="no")) print "<span class=\"warning\">".$letter."</span>";
			else print $letter;
			print "</a> | \n";
		}
		
		if ($letter === "@") {
			/**
			 * @ignore
			*/
			$pass = TRUE;
		}
	}
	if ($pass == TRUE) {
		if (isset($alpha) && $alpha == "@") {
			print "<a href=\"famlist.php?alpha=@&amp;surname_sublist=".$surname_sublist."&amp;surname=@N.N.";
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
		}
		else {
			print "<a href=\"famlist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".PrintReady($gm_lang["NN"])."</a>";
		}
		/**
		 * @ignore
		*/
		$pass = FALSE;
	}
	if (GedcomConfig::$LISTS_ALL) {
		print " | \n";
		if ($show_all=="yes") {
			print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=".$surname_sublist;
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".$gm_lang["all"]."</span></a>\n";
		}
		else {
			print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=".$surname_sublist;
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".$gm_lang["all"]."</a>\n";
		}
	}
	if (isset($startalpha)) $alpha = $startalpha;
}
print "<br /><br />";
if (!isset($alpha)) $alpha="";
if ($surname_sublist=="yes" && empty($surname)) {
	if (!isset($alpha)) $alpha="";
	if (!empty($alpha) || $show_all == "yes") {
		$indi_total = 0;
		$namelist = GetAlphaFamSurnames($alpha, $allgeds);
		foreach ($namelist as $key => $nsurname) {
			$tsurname = Str2Upper(StripPrefix(preg_replace("/([^ ]+)\*/", "$1", $nsurname["name"])));
			$surnames[$tsurname]["name"] = preg_replace("/([^ ]+)\*/", "$1", $nsurname["name"]);
			$surnames[$tsurname]["match"] = $nsurname["count"];
			$surnames[$tsurname]["alpha"] = $alpha;
			$indi_total += $nsurname["count"];
		}
		uasort($surnames, "ItemSort");
		print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
		PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"], $allgeds);
	}
}
else {
	$firstname_alpha = false;
	// NOTE: If the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$surname = trim($surname);
		$tfamlist = GetSurnameFams($surname, $allgeds);
	}
	// NOTE: Get all individuals for the sublist
	if (($surname_sublist=="no")&&(!empty($alpha))&&($show_all=="no")) {
		$tfamlist = GetAlphaFams($alpha, $allgeds);
	}
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if (($surname_sublist=="no")&&($show_all=="yes")) {
		$tfamlist = GetFamList($allgeds);
		print "<div class=\"topbar\">".$gm_lang["families"]."</div>\n";
		PrintFamilyList($tfamlist, true, false, $allgeds);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		print "<div class=\"topbar\">";
		if ($surname_sublist == "no") print $gm_lang["surnames"];
		else	print PrintReady(str_replace("#surname#", CheckNN($surname), $gm_lang["fams_with_surname"]));
		print "</div>\n";
		PrintFamilyList($tfamlist, true, false, $allgeds);
	}
}

print "<br />";
if ($alpha != "@") {
	print_help_link("skip_sublist_help", "qm", "skip_surnames");
	if ($surname_sublist=="yes") {
		print "<br /><a href=\"famlist.php?alpha=$alpha&amp;surname_sublist=no&amp;show_all=".$show_all;
		if ($allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".$gm_lang["skip_surnames"]."</a>";
	}
	else {
		print "<br /><a href=\"famlist.php?alpha=$alpha&amp;surname_sublist=yes&amp;show_all=".$show_all;
		if ($allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".$gm_lang["show_surnames"]."</a>";
	}
}
print "</div>";
PrintFooter();
?>