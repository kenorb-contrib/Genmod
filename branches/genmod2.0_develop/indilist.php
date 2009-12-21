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
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");
$indilist_controller = new IndilistController();

$COMBIKEY = true;

PrintHeader($indilist_controller->pagetitle);
print "<div class =\"center\">";
print "\n\t<h3>";
print_help_link("name_list_help", "qm", "name_list");
print GM_LANG_individual_list."</h3>";

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
$indialpha = $indilist_controller->GetIndiAlpha($indilist_controller->allgeds);

// Print the letter bar
if (count($indialpha) > 0) {
	print_help_link("alpha_help", "qm");
	foreach($indialpha as $key=>$letter) {
		if ($letter != "@") {
			print "<a href=\"indilist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$indilist_controller->surname_sublist."&amp;show_all=no";
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
			if ($indilist_controller->alpha == $letter && $indilist_controller->show_all == "no") print "<span class=\"warning\">".htmlspecialchars($letter)."</span>";
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
			print "\"><span class=\"warning\">".PrintReady(GM_LANG_NN)."</span></a>";
		}
		else {
			print "<a href=\"indilist.php?alpha=".urlencode("@")."@&amp;surname_sublist=yes&amp;surname=@N.N.";
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
			print "\"><span class=\"warning\">".GM_LANG_all."</span></a>\n";
		}
		else {
			print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=".$indilist_controller->surname_sublist;
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".GM_LANG_all."</a>\n";
		}
	}
	if (isset($startalpha)) $indilist_controller->alpha = $startalpha;
}
//-- escaped letter for regular expressions
$expalpha = $indilist_controller->alpha;
if ($expalpha=="(" || $expalpha=="[" || $expalpha=="?" || $expalpha=="/" || $expalpha=="*" || $expalpha=="+") $expalpha = "\\".$expalpha;
print "<br /><br />";

if ($indilist_controller->surname_sublist == "yes" && $indilist_controller->show_all == "yes") {
	// Get the surnames of all individuals
	// print "option 1";
	$surnames = $indilist_controller->GetAlphaIndiNames($indilist_controller->alpha, $indilist_controller->allgeds);
	uasort($surnames, "ItemSort");
	print "<div class=\"topbar\">".GM_LANG_surnames."</div>\n";
	$indilist_controller->PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);

}
else if ($indilist_controller->surname_sublist == "yes" && $indilist_controller->surname == "" && $indilist_controller->show_all == "no") {

	// print "option 2";
	// NOTE: Get all of the individuals whose last names start with this letter
	if ($indilist_controller->alpha != "") {
		$surnames = $indilist_controller->GetAlphaIndiNames($indilist_controller->alpha, $indilist_controller->allgeds);
		uasort($surnames, "ItemSort");
		print "<div class=\"topbar\">".GM_LANG_surnames."</div>\n";
		$indilist_controller->PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
		
	}
}
else {
	// NOTE: If the surname is set then only get the names in that surname list
	if ($indilist_controller->surname != "" && $indilist_controller->surname_sublist=="yes") {
		// print "option 3";
		$indilist_controller->surname = trim($indilist_controller->surname);
		$tindilist = $indilist_controller->GetSurnameIndis($indilist_controller->surname, "");
	}
	// NOTE: Get all individuals for the sublist
	if ($indilist_controller->surname_sublist == "no" && $indilist_controller->alpha != "" && $show_all == "no") {
		// print "option 4 for ".$indilist_controller->alpha;
		$tindilist = $indilist_controller->GetSurnameIndis("", $indilist_controller->alpha);
	}
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if ($indilist_controller->surname_sublist == "no" && $indilist_controller->show_all == "yes") {
		// print "option 5";
		$tindilist = $indilist_controller->GetSurnameIndis();
		$indilist_controller->PrintPersonList($tindilist, true, false, $indilist_controller->allgeds);
	}
	else {
		// print "option 6";
		// NOTE: If user wishes to skip surname do not print the surname
		print "<div class=\"topbar\">";
		if ($indilist_controller->surname_sublist == "no") print GM_LANG_surnames;
		else print PrintReady(str_replace("#surname#", CheckNN($indilist_controller->surname), GM_LANG_indis_with_surname));
		print "</div>\n";
		$indilist_controller->PrintPersonList($tindilist, true, false, $indilist_controller->allgeds);
	}
}

if ($indilist_controller->alpha != "@" && $indilist_controller->surname == "") {
	print_help_link("skip_sublist_help", "qm", "skip_surnames");
	print "<br /><a href=\"indilist.php?alpha=".urlencode($indilist_controller->alpha)."&amp;surname_sublist=".($indilist_controller->surname_sublist == "yes" ? "no" : "yes")."&amp;show_all=".$indilist_controller->show_all;
	if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
	print "\">".($indilist_controller->surname_sublist == "yes" ? GM_LANG_skip_surnames : GM_LANG_show_surnames)."</a>";
}
print "</div>\n";
PrintFooter();
?>
