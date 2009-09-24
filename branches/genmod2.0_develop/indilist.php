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
 * translated version of the word ALL by means of variable <var>$gm_lang["all"]</var>.
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
$addheader = "";
// Remove slashes
if (isset($alpha)) {
	$alpha = stripslashes($alpha);
	$addheader = "(".$alpha.")";
}
if (isset($surname)) {
	$surname = stripslashes($surname);
	$addheader = "(".CheckNN($surname).")";
}

print_header($gm_lang["individual_list"]." ".$addheader);
print "<div class =\"center\">";
print "\n\t<h3>";
print_help_link("name_list_help", "qm", "name_list");
print $gm_lang["individual_list"]."</h3>";

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

if (count($indialpha) > 0) {
	print_help_link("alpha_help", "qm");
	foreach($indialpha as $key=>$letter) {
		if ($letter != "@") {
			print "<a href=\"indilist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$indilist_controller->surname_sublist."&amp;show_all=no";
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
			if ($indilist_controller->alpha == $letter && $indilist_controller->show_all == "no") print "<span class=\"warning\">".$letter."</span>";
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
		if ($indilist_controller->alpha == "@") {
			print "<a href=\"indilist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
		}
		else {
			print "<a href=\"indilist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".PrintReady($gm_lang["NN"])."</a>";
		}
		/**
		 * @ignore
		*/
		$pass = FALSE;
	}
	if ($LISTS_ALL) {
		print " | \n";
		if ($indilist_controller->show_all == "yes") {
			print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=".$indilist_controller->surname_sublist;
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".$gm_lang["all"]."</span></a>\n";
		}
		else {
			print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=".$indilist_controller->surname_sublist;
			if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".$gm_lang["all"]."</a>\n";
		}
	}
	if (isset($startalpha)) $indilist_controller->alpha = $startalpha;
}
//-- escaped letter for regular expressions
$expalpha = $indilist_controller->alpha;
if ($expalpha=="(" || $expalpha=="[" || $expalpha=="?" || $expalpha=="/" || $expalpha=="*" || $expalpha=="+") $expalpha = "\\".$expalpha;
print "<br /><br />";

if ($indilist_controller->surname_sublist == "yes" && $indilist_controller->show_all == "yes") {
	$indilist = ListFunctions::GetIndiList($indilist_controller->allgeds);
	$surnames = array();
	$indi_hide = array();
	$thisgedid = $GEDCOMID;
	foreach($indilist as $gid=>$indi) {
		foreach($indi->name_array as $indexval => $name) {
			ListFunctions::SurnameCount($name[2], $name[1]);
		}
	}
	uasort($surnames, "ItemSort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	ListFunctions::PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"], $indilist_controller->allgeds);

}
else if ($indilist_controller->surname_sublist == "yes" && $indilist_controller->surname == "" && $indilist_controller->show_all == "no") {

	// NOTE: Get all of the individuals whose last names start with this letter
	if ($indilist_controller->alpha != "") {
		$tindilist = GetAlphaIndis($alpha, $indilist_controller->allgeds);
		$surnames = array();
		$indi_hide = array();
		$thisgedid = $GEDCOMID;
		$indi_total = array();
		foreach($tindilist as $gid=>$indi) {
			$indi = $indilist[$gid];
			$thisgid = splitkey($gid, "id");
			$thisgedid = splitkey($gid, "gedid");
			SwitchGedcom($thisgedid);
			$indi_total[$thisgid."[".$indi["gedfile"]."]"] = 1;
			if (PrivacyFunctions::showLivingNameByID($thisgid)) {
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
			else $indi_hide[$thisgid."[".$indi["gedfile"]."]"] = 1;
		}
		SwitchGedcom();
		$i = 0;
		uasort($surnames, "ItemSort");
		print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
		PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"], $indilist_controller->allgeds);
	}
}
else {
	// NOTE: If the surname is set then only get the names in that surname list
	if ($indilist_controller->surname != "" && $indilist_controller->surname_sublist=="yes") {
		$indilist_controller->surname = trim($indilist_controller->surname);
		$tindilist = GetSurnameIndis($indilist_controller->surname, $indilist_controller->allgeds);
	}
	// NOTE: Get all individuals for the sublist
	if ($indilist_controller->surname_sublist == "no" && $indilist_controller->alpha != "" && $show_all == "no") {
		$tindilist = GetAlphaIndis($indilist_controller->alpha, $indilist_controller->allgeds);
	}
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if ($indilist_controller->surname_sublist == "no" && $indilist_controller->show_all == "yes") {
		$tindilist = GetIndiList($indilist_controller->allgeds);
		PrintPersonList($tindilist, true, false, $indilist_controller->allgeds);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		print "<div class=\"topbar\">";
		if ($indilist_controller->surname_sublist == "no") print $gm_lang["surnames"];
		else	print PrintReady(str_replace("#surname#", CheckNN($indilist_controller->surname), $gm_lang["indis_with_surname"]));
		print "</div>\n";
		PrintPersonList($tindilist, true, false, $indilist_controller->allgeds);
	}
}

if ($indilist_controller->alpha != "@") {
	print_help_link("skip_sublist_help", "qm", "skip_surnames");
	if ($indilist_controller->surname_sublist=="yes") {
		print "<br /><a href=\"indilist.php?alpha=".$indilist_controller->alpha."&amp;surname_sublist=no&amp;show_all=".$indilist_controller->show_all;
		if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".$gm_lang["skip_surnames"]."</a>";
	}
	else {
		print "<br /><a href=\"indilist.php?alpha=".$indilist_controller->alpha."&amp;surname_sublist=yes&amp;show_all=".$indilist_controller->show_all;
		if ($indilist_controller->allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".$gm_lang["show_surnames"]."</a>";
	}
}
print "</div>\n";
print_footer();
?>
