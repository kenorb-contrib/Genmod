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
 * translated version of the word ALL by means of variable <var>GM_LANG_all"]</var>.
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
$famlist_controller = new FamlistController();

$COMBIKEY = true;

PrintHeader($famlist_controller->pagetitle);
print "<div class =\"center\">";
print "\n\t<h3>";
PrintHelpLink("name_list_help", "qm", "name_list");
print GM_LANG_family_list."</h3>";

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

$famalpha = $famlist_controller->GetLetterBar();

if (count($famalpha) > 0) {
	PrintHelpLink("alpha_help", "qm", "alpha_index");
	foreach($famalpha as $index => $letter) {
		if ($letter != "@") {
			print "<a href=\"famlist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$famlist_controller->surname_sublist;
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
			if ($famlist_controller->alpha == $letter && $famlist_controller->show_all=="no") print "<span class=\"warning\">".htmlspecialchars($letter)."</span>";
			else print htmlspecialchars($letter);
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
		if ($famlist_controller->alpha == "@") {
			print "<a href=\"famlist.php?alpha=".urlencode("@")."&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".PrintReady(GM_LANG_NN)."</span></a>";
		}
		else {
			print "<a href=\"famlist.php?alpha=".urlencode("@")."&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".PrintReady(GM_LANG_NN)."</a>";
		}
		/**
		 * @ignore
		*/
		$pass = FALSE;
	}
	if (GedcomConfig::$LISTS_ALL) {
		print " | \n";
		if ($famlist_controller->show_all=="yes") {
			print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=".$famlist_controller->surname_sublist;
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".GM_LANG_all."</span></a>\n";
		}
		else {
			print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=".$famlist_controller->surname_sublist;
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".GM_LANG_all."</a>\n";
		}
	}
	if (isset($startalpha)) $famlist_controller->alpha = $startalpha;
}
print "<br /><br />";
if ($famlist_controller->surname_sublist=="yes" && $famlist_controller->show_all == "yes") {
	// Get the surnames of all individuals belonging to a family
	// print "option 1";
	$namelist = $famlist_controller->GetAlphaFamSurnames($famlist_controller->alpha, $famlist_controller->allgeds);
	print "<div class=\"topbar\">".GM_LANG_surnames."</div>\n";
	$famlist_controller->PrintSurnameList($namelist);
}
else if ($famlist_controller->surname_sublist == "yes" && $famlist_controller->surname == "" && $famlist_controller->show_all == "no") {

	// print "option 2";
	// NOTE: Get all of the individuals whose last names start with this letter
	if ($famlist_controller->alpha != "") {
		$namelist = $famlist_controller->GetAlphaFamSurnames($famlist_controller->alpha, $famlist_controller->allgeds);
		print "<div class=\"topbar\">".GM_LANG_surnames."</div>\n";
		$famlist_controller->PrintSurnameList($namelist);
		
	}
}
else {
	// NOTE: If the surname is set then only get the names in that surname list
	if ($famlist_controller->surname != "" && $famlist_controller->surname_sublist=="yes") {
		$tfamlist = $famlist_controller->GetFams();
	}
	// NOTE: Get all individuals for the sublist
	if ($famlist_controller->surname_sublist == "no" && $famlist_controller->alpha != "" && $famlist_controller->show_all == "no") {
		$tfamlist = $famlist_controller->GetFams();
	}
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if ($famlist_controller->surname_sublist == "no" && $famlist_controller->show_all == "yes") {
		$tfamlist = $famlist_controller->GetFams();
		print "<div class=\"topbar\">".GM_LANG_families."</div>\n";
		$famlist_controller->PrintFamilyList($tfamlist, true);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		print "<div class=\"topbar\">";
		if ($famlist_controller->surname_sublist == "no") print GM_LANG_surnames;
		else	print PrintReady(str_replace("#surname#", NameFunctions::CheckNN($surname), GM_LANG_fams_with_surname));
		print "</div>\n";
		$famlist_controller->PrintFamilyList($tfamlist, true);
	}
}

print "<br />";
if ($famlist_controller->alpha != "@") {
	PrintHelpLink("skip_sublist_help", "qm", "skip_surnames");
	if ($famlist_controller->surname_sublist=="yes") {
		print "<br /><a href=\"famlist.php?alpha=$famlist_controller->alpha&amp;surname_sublist=no&amp;show_all=".$famlist_controller->show_all;
		if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".GM_LANG_skip_surnames."</a>";
	}
	else {
		print "<br /><a href=\"famlist.php?alpha=$famlist_controller->alpha&amp;surname_sublist=yes&amp;show_all=".$famlist_controller->show_all;
		if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".GM_LANG_show_surnames."</a>";
	}
}
print "</div>";
PrintFooter();
?>