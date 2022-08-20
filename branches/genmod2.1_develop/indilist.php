<?php
/**
 * Individual List
 *
 * The individual list shows all individuals from a chosen gedcom file. The list is
 * setup in two sections. The alphabet bar and the details.
 *
 * The alphabet bar shows all the available letters users can click. The bar is built
 * up from the lastnames first letter. Added to this bar is the symbol @, which is
 * shown as a translated version of the variable <var>gm_lang["NN"]</var>, and a
 * translated version of the word ALL by means of variable <var>GM_LANG_all</var>.
 *
 * The details can be shown in two ways, with surnames or without surnames. By default
 * the user first sees a list of surnames of the chosen letter and by clicking on a
 * surname a list with names of people with that chosen surname is displayed.
 *
 * Beneath the details list is the option to skip the surname list or show it.
 * Depending on the current status of the list.
 *
 * @package Genmod
 * @subpackage Lists
 * @version $Id: indilist.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");
$indilist_controller = new IndilistController();

$trace = false;

PrintHeader($indilist_controller->pagetitle);
print "<div id=\"IndividualListPage\">";
	print "<div class =\"PageTitleName\">";
		PrintHelpLink("name_list_help", "qm", "name_list");
		print GM_LANG_individual_list;
	print "</div>";

/**
 * Check for the @ symbol
 *
 * This variable is used for checking if the @ symbol is present in the alphabet list.
 * @var boolean $pass
 */
$pass = FALSE;

/**
 * Total indilist array
 *
 * The tindilist array will contain individuals that are extracted from the database.
 * @var array $tindilist
 */
$tindilist = array();

/**
 * Individual alpha array
 *
 * The indialpha array will contain all first letters that are extracted from an individuals
 * lastname.
 * @var array $indialpha
 */
$indialpha = $indilist_controller->GetLetterBar();

// Print the letter bar
if (count($indialpha) > 0) {
	print "<div class=\"IndiFamLetterBar\">";
	PrintHelpLink("alpha_help", "qm");
	print GM_LANG_first_letter_sname."<br />\n";
	foreach($indialpha as $key=>$letter) {
		if ($letter != "@") {
			print "<a href=\"indilist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$indilist_controller->surname_sublist."&amp;show_all=no";
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
			if ($indilist_controller->alpha == $letter && $indilist_controller->show_all == "no") print "<span class=\"Warning\">".htmlspecialchars($letter)."</span>";
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
		if ($indilist_controller->alpha == "@") {
			print "<a href=\"indilist.php?alpha=".urlencode("@")."&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"Warning\">".PrintReady(GM_LANG_NN)."</span></a>";
		}
		else {
			print "<a href=\"indilist.php?alpha=".urlencode("@")."&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
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
		if ($indilist_controller->show_all == "yes") {
			print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=".$indilist_controller->surname_sublist;
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"Warning\">".GM_LANG_all."</span></a>\n";
		}
		else {
			print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=".$indilist_controller->surname_sublist;
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".GM_LANG_all."</a>\n";
		}
	}
	if (isset($startalpha)) $indilist_controller->alpha = $startalpha;
	print "</div>";
}
//-- escaped letter for regular expressions
if ($indilist_controller->surname_sublist == "yes" && $indilist_controller->show_all == "yes") {
	// Get the surnames of all individuals
	if ($trace) print "option 1";
	$surnames = $indilist_controller->GetAlphaIndiNames();
	$indilist_controller->PrintSurnameList($surnames);
}
else if ($indilist_controller->surname_sublist == "yes" && $indilist_controller->surname == "" && $indilist_controller->show_all == "no") {

	if ($trace) print "option 2";
	// NOTE: Get all of the individuals whose last names start with this letter
	if ($indilist_controller->alpha != "") {
		$surnames = $indilist_controller->GetAlphaIndiNames();
		$indilist_controller->PrintSurnameList($surnames);
		
	}
}
else {
	// NOTE: If the surname is set then only get the names in that surname list
	if ($indilist_controller->surname != "" && $indilist_controller->surname_sublist == "yes") {
		if ($trace) print "option 3";
		$tindilist = $indilist_controller->GetIndis();
	}
	// NOTE: Get all individuals for the sublist
	if ($indilist_controller->surname_sublist == "no" && $indilist_controller->alpha != "" && $indilist_controller->show_all == "no") {
		if ($trace)  print "option 4 for ".$indilist_controller->alpha;
		$tindilist = $indilist_controller->GetIndis();
	}
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if ($indilist_controller->surname_sublist == "no" && $indilist_controller->show_all == "yes") {
		if ($trace)  print "option 5";
		$tindilist = $indilist_controller->GetIndis();
		$indilist_controller->PrintPersonList($tindilist, true);
	}
	else {
		if ($trace) print "option 6";
		$indilist_controller->PrintPersonList($tindilist, true);
	}
}
// Show the skip/show surnamelist only if a specific letter is chosen
if ($indilist_controller->alpha != "" && $indilist_controller->surname == "") {
	print "<div class=\"IndiFamShowHideSurnameList\">";
		PrintHelpLink("skip_sublist_help", "qm", "skip_surnames");
		print "<br /><a href=\"indilist.php?alpha=".urlencode($indilist_controller->alpha)."&amp;surname_sublist=".($indilist_controller->surname_sublist == "yes" ? "no" : "yes")."&amp;show_all=".$indilist_controller->show_all;
		if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".($indilist_controller->surname_sublist == "yes" ? GM_LANG_skip_surnames : GM_LANG_show_surnames)."</a>";
	print "</div>";
}
print "</div>\n";
PrintFooter();
?>
