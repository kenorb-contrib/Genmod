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
 * @version $Id: indilist.php,v 1.5 2006/04/30 18:44:14 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

print_header($gm_lang["individual_list"]);
print "<div class =\"center\">";
print "\n\t<h2>";
print_help_link("name_list_help", "qm", "name_list");
print $gm_lang["individual_list"]."</h2>";

if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";
if (empty($show_all_firstnames)) $show_all_firstnames = "no";

// Remove slashes
if (isset($alpha)) $alpha = stripslashes($alpha);
if (isset($surname)) $surname = stripslashes($surname);

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
$indialpha = GetIndiAlpha();

uasort($indialpha, "stringsort");

if (isset($alpha) && !isset($indialpha["$alpha"])) unset($alpha);

if (count($indialpha) > 0) {
	print_help_link("alpha_help", "qm");
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
			print "<a href=\"indilist.php?alpha=".urlencode($letter)."&amp;surname_sublist=$surname_sublist\">";
			if (($alpha==$letter)&&($show_all=="no")) print "<span class=\"warning\">".$letter."</span>";
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
		if (isset($alpha) && $alpha == "@") print "<a href=\"indilist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
		else print "<a href=\"indilist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.\">".PrintReady($gm_lang["NN"])."</a>";
		print " | \n";
		/**
		 * @ignore
		*/
		$pass = FALSE;
	}
	if ($show_all=="yes") print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=$surname_sublist\"><span class=\"warning\">".$gm_lang["all"]."</span></a>\n";
	else print "<a href=\"indilist.php?show_all=yes&amp;surname_sublist=$surname_sublist\">".$gm_lang["all"]."</a>\n";
	if (isset($startalpha)) $alpha = $startalpha;
}

//-- escaped letter for regular expressions
$expalpha = $alpha;
if ($expalpha=="(" || $expalpha=="[" || $expalpha=="?" || $expalpha=="/" || $expalpha=="*" || $expalpha=="+") $expalpha = "\\".$expalpha;
print "<br /><br />";

if (($surname_sublist=="yes")&&($show_all=="yes")) {
	GetIndiList();
	if (!isset($alpha)) $alpha="";
	$surnames = array();
	$indi_hide=array();
	foreach($indilist as $gid=>$indi) {
		if (displayDetailsById($gid)||showLivingNameById($gid)) {
			foreach($indi["names"] as $indexval => $name) {
				surname_count($name[2], $name[1]);
			}
		}
		else $indi_hide[$gid."[".$indi["gedfile"]."]"] = 1;
	}
	uasort($surnames, "itemsort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else if (($surname_sublist=="yes")&&(empty($surname))&&($show_all=="no")) {
	if (!isset($alpha)) $alpha="";
	// NOTE: Get all of the individuals whose last names start with this letter
	$tindilist = GetAlphaIndis($alpha);
	$surnames = array();
	$indi_hide=array();
	foreach($tindilist as $gid=>$indi) {
		if ((displayDetailsByID($gid))||(showLivingNameById($gid))) {
			foreach($indi["names"] as $name) {
				if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
					if ($alpha == "Ø") $text = "OE";
					else if ($alpha == "Æ") $text = "AE";
					else if ($alpha == "Å") $text = "AA";
					if (isset($text)) {
						if ((preg_match("/^$expalpha/", $name[1])>0)||(preg_match("/^$text/", $name[1])>0)) surname_count($name[2], $alpha);
					}
					else if (preg_match("/^$expalpha/", $name[1])>0) surname_count($name[2], $alpha);
				}
				else {
					if (preg_match("/^$expalpha/", $name[1])>0) surname_count($name[2], $alpha);
				}
			}
		}
		else $indi_hide[$gid."[".$indi["gedfile"]."]"] = 1;
	}
	$i = 0;
	uasort($surnames, "itemsort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else {
	// NOTE: If the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$surname = trim($surname);
		$tindilist = get_surname_indis($surname);
	}
	// NOTE: Get all individuals for the sublist
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
	print_help_link("skip_sublist_help", "qm", "skip_surnames");
	if ($surname_sublist=="yes") print "<br /><a href=\"indilist.php?alpha=$alpha&amp;surname_sublist=no&amp;show_all=$show_all\">".$gm_lang["skip_surnames"]."</a>";
	else print "<br /><a href=\"indilist.php?alpha=$alpha&amp;surname_sublist=yes&amp;show_all=$show_all\">".$gm_lang["show_surnames"]."</a>";
}
print "</div>\n";
print_footer();
?>
