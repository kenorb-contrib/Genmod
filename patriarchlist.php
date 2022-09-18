<?php
/**
 * Patriarch List
 *
 * The individual list shows all individuals from a chosen gedcom file. The list is
 * setup in two sections. The alphabet bar and the details.
 *
 * The alphabet bar shows all the available letters users can click. The bar is build
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
 * to be done;
 * - just run first part only if neccessary (file changes)
 * - put info on patriarch in SQL file
 * - add helpfile
 * - put different routines in subroutine file
 *
 * Parses gedcom file and displays a list of 'earthfathers' == patriarch.
 * This program was made in analogy of the family.php program
 * ==	You probably do not have to check the whole list but just select on -no- spouse in the list
 * ==	You still have to deal with the 'singles' and mother with children
 * ==	lOOKS LIKE: SELECT * FROM `gm_individuals` WHERE i_file='$GEDCOM' and i_cfams IS NULL ORDER BY `i_cfams` ASC, 'i_sfams' ASC
 * ==	The IS NULL check does not work??? Set to another value?
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
 * @version $Id: patriarchlist.php,v 1.9 2007/03/31 10:10:59 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

require_once 'includes/patriarch_class.php';

print_header($gm_lang["dynasty_list"]);

// Include the Patriarch class
$patriarch = new Patriarch();

print "<div class =\"center\">";
print "\n\t<h2>";
print_help_link("name_list_help", "qm", "name_list");
print $gm_lang["dynasty_list"]."</h2>";

if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";

// Remove slashes
if (isset($alpha)) $alpha = stripslashes($alpha);
if (isset($surname)) $surname = stripslashes($surname);

$pass = FALSE;

print_help_link("alpha_help", "qm", "alpha_index");
foreach($patriarch->patrialpha as $letter=>$list) {
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
		print "<a href=\"patriarchlist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$surname_sublist."\">";
		if (($alpha==$letter)&&($show_all=="no")) print "<span class=\"warning\">".$letter."</span>";
		else print $letter;
		print "</a> | \n";
	}
	if ($letter === "@") $pass = TRUE;
}
if ($pass == TRUE) {
	if ($alpha == "@") print "<span class=\"warning\">".PrintReady($gm_lang["NN"])."</span>";
	else print "<a href=\"patriarchlist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.\">".PrintReady($gm_lang["NN"])."</a>";
	print " | \n";
	$pass = FALSE;
}
print "<a href=\"patriarchlist.php?show_all=yes&amp;surname_sublist=$surname_sublist\">";
if ($show_all=="yes") print "<span class=\"warning\">";
print_text("all"); 
if ($show_all=="yes") print "</span>";
print "</a>\n";
if (isset($startalpha)) $alpha = $startalpha;

if (!isset($alpha)) $alpha="";
$patriarch->get_alpha_patri($alpha);

$expalpha = $alpha;
if ($expalpha=="(") $expalpha = '\(';
if ($expalpha=="[") $expalpha = '\[';
if ($expalpha=="?") $expalpha = '\?';
if ($expalpha=="/") $expalpha = '\/';
print "<br /><br /><table class=\"list_table $TEXT_DIRECTION\"><tr>";

if (($surname_sublist=="yes")&&($show_all=="yes")) {
	if (!isset($alpha)) $alpha="";
	// NOTE: Start printing names
	$surnames = array();
	$indi_hide = array();
	foreach($patriarch->patrilist as $gid=>$fam) {
		// NOTE: Make sure that favorites from other gedcoms are not shown
		if ($fam["gedfile"]==$GEDCOMID) {
			if (showLivingNameById($gid)) ExtractSurname($fam["name"]);
			else $indi_hide[$gid."[".$fam["gedfile"]."]"] = 1;
		}
	}
	$i = 0;
	uasort($surnames, "ItemSort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else if (($surname_sublist=="yes")&&(empty($surname))&&($show_all=="no")) {
	$surnames = array();
	$indi_hide=array();
	foreach($patriarch->patrilist as $gid=>$fam) {
	//-- make sure that favorites from other gedcoms are not shown
        if ($fam["gedfile"]==$GEDCOMID) {
			if (showLivingNameById($gid)) {
				ExtractSurname($fam["name"]);
			}
			else $indi_hide[$gid."[".$fam["gedfile"]."]"] = 1;
	    }
	}
	$i = 0;
	uasort($surnames, "ItemSort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else {
	$firstname_alpha = false;
	//-- if the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$surname = trim($surname);
		$patriarch->tpatrilist = GetSurnameFams($surname);
	}
	// NOTE: Get all individuals for the sublist
	if (($surname_sublist=="no")&&(!empty($alpha))&&($show_all=="no")) $patriarch->tpatrilist = GetAlphaFams($alpha);
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if (($surname_sublist=="no")&&($show_all=="yes")) {
		print "<div class=\"topbar\">".$gm_lang["individuals"]."</div>\n";
		PrintFamilyList($patriarch->patrilist);
	}
	else {
		print "<div class=\"topbar\">";
		if ($surname_sublist == "no") print $gm_lang["surnames"];
		else	print PrintReady(str_replace("#surname#", CheckNN($surname), $gm_lang["fams_with_surname"]));
		print "</div>\n";
		PrintFamilyList($patriarch->tpatrilist);
	}
}
print "</tr></table>";

print "<br />";
if ($alpha != "@") {
	print_help_link("skip_sublist_help", "qm", "skip_surnames");
	if ($surname_sublist=="yes") print "<br /><a href=\"patriarchlist.php?alpha=$alpha&amp;surname_sublist=no&amp;show_all=$show_all\">".$gm_lang["skip_surnames"]."</a>";
	else print "<br /><a href=\"patriarchlist.php?alpha=$alpha&amp;surname_sublist=yes&amp;show_all=$show_all\">".$gm_lang["show_surnames"]."</a>";
}
print "</div>\n";
print_footer();
?>