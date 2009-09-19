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

if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";
if (empty($show_all_firstnames)) $show_all_firstnames = "no";
// Added if any of the gedcoms require authentication and the user is not logged on, we cannot do allgeds.
if (!isset($allgeds) || $allgeds != "yes" || !$ALLOW_CHANGE_GEDCOM) $allgeds = "no";
if ($allgeds == "yes" && $gm_user->username == "") {
	foreach($GEDCOMS as $key => $ged) {
		SwitchGedcom($key);
		if ($REQUIRE_AUTHENTICATION) $allgeds = "no";
	}
	SwitchGedcom();
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
$indialpha = GetIndiAlpha($allgeds);

uasort($indialpha, "stringsort");

if (isset($alpha) && !isset($indialpha["$alpha"])) unset($alpha);

if (count($indialpha) > 0) {
	print_help_link("alpha_help", "qm");
	foreach($indialpha as $letter=>$list) {
		if (empty($alpha)) {
			if (!empty($surname)) {
				$alpha = GetFirstLetter(StripPrefix($surname));
			}
		}
		if ($letter != "@") {
			print "<a href=\"indilist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$surname_sublist."&amp;show_all=no";
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
			if (isset($alpha) && $alpha == $letter && $show_all == "no") print "<span class=\"warning\">".$letter."</span>";
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
			print "<a href=\"indilist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
		}
		else {
			print "<a href=\"indilist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.";
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".PrintReady($gm_lang["NN"])."</a>";
		}
		/**
		 * @ignore
		*/
		$pass = FALSE;
	}
	if ($LISTS_ALL) {
		print " | \n";
		if ($show_all=="yes") {
			print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=$surname_sublist";
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\"><span class=\"warning\">".$gm_lang["all"]."</span></a>\n";
		}
		else {
			print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=$surname_sublist";
			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">".$gm_lang["all"]."</a>\n";
		}
	}
	if (isset($startalpha)) $alpha = $startalpha;
}

//-- escaped letter for regular expressions
if (!isset($alpha)) $alpha = "";
$expalpha = $alpha;
if ($expalpha=="(" || $expalpha=="[" || $expalpha=="?" || $expalpha=="/" || $expalpha=="*" || $expalpha=="+") $expalpha = "\\".$expalpha;
print "<br /><br />";

if (($surname_sublist=="yes")&&($show_all=="yes")) {
	GetIndiList($allgeds);
	$surnames = array();
	$indi_hide=array();
	$thisgedid = $GEDCOMID;
	foreach($indilist as $gid=>$indi) {
		$thisgid = splitkey($gid, "id");
		$thisgedid = splitkey($gid, "gedid");
		SwitchGedcom($thisgedid);
		$indi_total[$thisgid."[".$indi["gedfile"]."]"] = 1;
		if (PrivacyFunctions::showLivingNameByID($thisgid)) {
			foreach($indi["names"] as $indexval => $name) {
				SurnameCount($name[2], $name[1]);
			}
		}
		else $indi_hide[$thisgid."[".$indi["gedfile"]."]"] = 1;
	}
	SwitchGedcom();
	uasort($surnames, "ItemSort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"], $allgeds);
}
else if (($surname_sublist=="yes")&&(empty($surname))&&($show_all=="no")) {

	if (!isset($alpha)) $alpha="";
	// NOTE: Get all of the individuals whose last names start with this letter
	if (!empty($alpha)) {
		$tindilist = GetAlphaIndis($alpha, $allgeds);
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
							if ((preg_match("/^$expalpha/", $name[1])>0)||(preg_match("/^$text/", $name[1])>0)) SurnameCount($name[2], $alpha);
						}
						else if (preg_match("/^$expalpha/", $name[1])>0) SurnameCount($name[2], $alpha);
					}
					else {
						if (preg_match("/^$expalpha/", $name[1])>0) SurnameCount($name[2], $alpha);
					}
				}
			}
			else $indi_hide[$thisgid."[".$indi["gedfile"]."]"] = 1;
		}
		SwitchGedcom();
		$i = 0;
		uasort($surnames, "ItemSort");
		print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
		PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"], $allgeds);
	}
}
else {
	// NOTE: If the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$surname = trim($surname);
		$tindilist = GetSurnameIndis($surname, $allgeds);
	}
	// NOTE: Get all individuals for the sublist
	if (($surname_sublist=="no")&&(!empty($alpha))&&($show_all=="no")) {
		$tindilist = GetAlphaIndis($alpha, $allgeds);
	}
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if (($surname_sublist=="no")&&($show_all=="yes")) {
		$tindilist = GetIndiList($allgeds);
		PrintPersonList($tindilist, true, false, $allgeds);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		print "<div class=\"topbar\">";
		if ($surname_sublist == "no") print $gm_lang["surnames"];
		else	print PrintReady(str_replace("#surname#", CheckNN($surname), $gm_lang["indis_with_surname"]));
		print "</div>\n";
		PrintPersonList($tindilist, true, false, $allgeds);
	}
}

if ($alpha != "@") {
	print_help_link("skip_sublist_help", "qm", "skip_surnames");
	if ($surname_sublist=="yes") {
		print "<br /><a href=\"indilist.php?alpha=$alpha&amp;surname_sublist=no&amp;show_all=$show_all";
		if ($allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".$gm_lang["skip_surnames"]."</a>";
	}
	else {
		print "<br /><a href=\"indilist.php?alpha=$alpha&amp;surname_sublist=yes&amp;show_all=$show_all";
		if ($allgeds == "yes") print "&amp;allgeds=yes";
		print "\">".$gm_lang["show_surnames"]."</a>";
	}
}
print "</div>\n";
print_footer();
?>
