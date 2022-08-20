<?php
/**
 * Family List
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
 * @version $Id: famlist.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");
$famlist_controller = new FamlistController();

$trace = false;

PrintHeader($famlist_controller->pagetitle);
print "<div id=\"FamListPage\">";
	print "<div class =\"PageTitleName\">";
	PrintHelpLink("name_list_help", "qm", "name_list");
	print GM_LANG_family_list;
print "</div>";

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

// Print the letter bar
if (count($famalpha) > 0) {
	print "<div class=\"IndiFamLetterBar\">";
	PrintHelpLink("alpha_help", "qm", "alpha_index");
	print GM_LANG_first_letter_fam_sname."<br />\n";
	foreach($famalpha as $index => $letter) {
		if ($letter != "@") {
			print "<a href=\"famlist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$famlist_controller->surname_sublist;
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
			if ($famlist_controller->alpha == $letter && $famlist_controller->show_all=="no") print "<span class=\"Warning\">".htmlspecialchars($letter)."</span>";
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
	// Add the N.N. link
	if ($pass == TRUE) {
		if ($famlist_controller->alpha == "@") {
			print "<a href=\"famlist.php?alpha=".urlencode("@")."&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"Warning\">".PrintReady(GM_LANG_NN)."</span></a>";
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
	// Add the ALL link
	if (GedcomConfig::$LISTS_ALL) {
		print " | \n";
		if ($famlist_controller->show_all=="yes") {
			print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=".$famlist_controller->surname_sublist;
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"Warning\">".GM_LANG_all."</span></a>\n";
		}
		else {
			print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=".$famlist_controller->surname_sublist;
			if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".GM_LANG_all."</a>\n";
		}
	}
	if (isset($startalpha)) $famlist_controller->alpha = $startalpha;
	print "</div>";
}
if ($famlist_controller->surname_sublist == "yes" && $famlist_controller->show_all == "yes") {
	// Get the surnames of all individuals belonging to a family
	if ($trace) print "option 1";
	$namelist = $famlist_controller->GetAlphaFamSurnames($famlist_controller->alpha, $famlist_controller->allgeds);
	$famlist_controller->PrintSurnameList($namelist);
}
else if ($famlist_controller->surname_sublist == "yes" && $famlist_controller->surname == "" && $famlist_controller->show_all == "no") {

	if ($trace) print "option 2";
	// NOTE: Get all of the individuals whose last names start with this letter
	if ($famlist_controller->alpha != "") {
		$namelist = $famlist_controller->GetAlphaFamSurnames($famlist_controller->alpha, $famlist_controller->allgeds);
		$famlist_controller->PrintSurnameList($namelist);
		
	}
}
else {
	// NOTE: If the surname is set then only get the names in that surname list
	if ($famlist_controller->surname != "" && $famlist_controller->surname_sublist == "yes") {
		if ($trace) print "option 3";
		$tfamlist = $famlist_controller->GetFams();
	}
	// NOTE: Get all individuals for the sublist
	if ($famlist_controller->surname_sublist == "no" && $famlist_controller->alpha != "" && $famlist_controller->show_all == "no") {
		if ($trace)  print "option 4 for ".$famlist_controller->alpha;
		$tfamlist = $famlist_controller->GetFams();
	}
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if ($famlist_controller->surname_sublist == "no" && $famlist_controller->show_all == "yes") {
		if ($trace)  print "option 5";
		$tfamlist = $famlist_controller->GetFams();
		$famlist_controller->PrintFamilyList($tfamlist, true);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		if ($trace) print "option 6";
		$famlist_controller->PrintFamilyList($tfamlist, true);
	}
}

if ($famlist_controller->alpha != "" && $famlist_controller->surname == "") {
	print "<div class=\"IndiFamShowHideSurnameList\">";
		PrintHelpLink("skip_sublist_help", "qm", "skip_surnames");
		print "<br /><a href=\"famlist.php?alpha=".urlencode($famlist_controller->alpha)."&amp;surname_sublist=".($famlist_controller->surname_sublist == "yes" ? "no" : "yes")."&amp;show_all=".$famlist_controller->show_all;
		if ($famlist_controller->allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".($famlist_controller->surname_sublist == "yes" ? GM_LANG_skip_surnames : GM_LANG_show_surnames)."</a>";
	print "</div>";
}
print "</div>";
PrintFooter();
?>