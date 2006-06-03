<?php
/**
 * Family List
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @version $Id: famlist.php,v 1.3 2006/04/30 18:44:14 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

print_header($gm_lang["family_list"]);
print "<div class =\"center\">";
print "\n\t<h2>";
print_help_link("name_list_help", "qm", "name_list");
print $gm_lang["family_list"]."</h2>";

if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";

// Remove slashes
if (isset($alpha)) $alpha = stripslashes($alpha);
if (isset($surname)) $surname = stripslashes($surname);
if (empty($show_all_firstnames)) $show_all_firstnames = "no";

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

$famalpha = get_fam_alpha();

uasort($famalpha, "stringsort");

if (isset($alpha) && !isset($famalpha["$alpha"])) unset($alpha);

$TableTitle = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".$gm_lang["families"]."\" alt=\"".$gm_lang["families"]."\" />&nbsp;&nbsp;";

if (count($famalpha) > 0) {
	print_help_link("alpha_help", "qm", "alpha_index");
	foreach($famalpha as $letter=>$list) {
		if (empty($alpha)) {
			if (!empty($surname)) {
				if ($USE_RTL_FUNCTIONS && isRTLText($surname)) $alpha = substr(preg_replace(array("/ [jJsS][rR]\.?,/", "/ I+,/", "/^[a-z. ]*/"), array(",",",",""), $surname),0,2);
				else $alpha = substr(preg_replace(array("/ [jJsS][rR]\.?,/", "/ I+,/", "/^[a-z. ]*/"), array(",",",",""), $surname),0,1);
			}
		}
		if ($letter != "@") {
			if (!isset($startalpha) && !isset($alpha)) {
				$startalpha = $letter;
				$alpha = $letter;
			}
			print "<a href=\"famlist.php?alpha=".urlencode($letter)."&amp;surname_sublist=".$surname_sublist."\">";
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
		if (isset($alpha) && $alpha == "@") print "<a href=\"famlist.php?alpha=@&amp;surname_sublist=".$surname_sublist."&amp;surname=@N.N.\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
		else print "<a href=\"famlist.php?alpha=@&amp;surname_sublist=yes&amp;surname=@N.N.\">".PrintReady($gm_lang["NN"])."</a>";
		print " | \n";
		/**
		 * @ignore
		*/
		$pass = FALSE;
	}
	if ($show_all=="yes") print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=$surname_sublist\"><span class=\"warning\">".$gm_lang["all"]."</span>\n";
	else print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=$surname_sublist\">".$gm_lang["all"]."</a>\n";
	if (isset($startalpha)) $alpha = $startalpha;
}
print "<br /><br />";
if (($surname_sublist=="yes")&&($show_all=="yes")) {
	get_fam_list();
	if (!isset($alpha)) $alpha="";
	$surnames = array();
	$fam_hide = array();
	foreach($famlist as $gid=>$fam) {
		if (displayDetailsById($gid, "FAM")||showLivingNameById($gid, "FAM")) {
			$names = preg_split("/\+/", $fam["name"]);
			$foundnames = array();
			for($i=0; $i<count($names); $i++) {
				$name = trim($names[$i]);
				$sname = extract_surname($name);
				if (isset($foundnames[$sname])) {
					if (isset($surnames[$sname]["match"])) $surnames[$sname]["match"]--;
				}
				else $foundnames[$sname]=1;
			}
		}
		else $fam_hide[$gid."[".$fam["gedfile"]."]"] = 1;
	}
	$i = 0;
	uasort($surnames, "itemsort");
	// NOTE: Print header
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else if (($surname_sublist=="yes")&&(empty($surname))&&($show_all=="no")) {
	if (!isset($alpha)) $alpha="";
	$tfamlist = get_alpha_fams($alpha);
	$surnames = array();
	$fam_hide = array();
	foreach($tfamlist as $gid=>$fam) {
		if ((displayDetailsByID($gid, "FAM"))||(showLivingNameById($gid, "FAM"))) {
			$i=0;
			foreach($fam["surnames"] as $indexval => $name) {
				surname_count(trim($name));
				$i++;
			}
		}
		else $fam_hide[$gid."[".$fam["gedfile"]."]"] = 1;
	}
	uasort($surnames, "itemsort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else {
	$firstname_alpha = false;
	// NOTE: If the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$surname = trim($surname);
		$tfamlist = get_surname_fams($surname);
	}
	// NOTE: Get all individuals for the sublist
	if (($surname_sublist=="no")&&(!empty($alpha))&&($show_all=="no")) $tfamlist = get_alpha_fams($alpha);
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if (($surname_sublist=="no")&&($show_all=="yes")) {
		$tfamlist = get_fam_list();
		print "<div class=\"topbar\">".$gm_lang["families"]."</div>\n";
		PrintFamilyList($tfamlist);
	}
	else {
		// NOTE: If user wishes to skip surname do not print the surname
		print "<div class=\"topbar\">";
		if ($surname_sublist == "no") print $gm_lang["surnames"];
		else	print PrintReady(str_replace("#surname#", check_NN($surname), $gm_lang["fams_with_surname"]));
		print "</div>\n";
		PrintFamilyList($tfamlist);
	}
}
print "</tr></table>";

print "<br />";
if ($alpha != "@") {
	if ($surname_sublist=="yes") print_help_link("skip_sublist_help", "qm", "skip_surnames");
	else print_help_link("skip_sublist_help", "qm", "show_surnames");
}
if ($show_all=="yes" && $alpha != "@"){
	if ($surname_sublist=="yes") print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=no\">".$gm_lang["skip_surnames"]."</a>";
 	else print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>";
}
else if (empty($alpha)) {
	if ($surname_sublist=="yes") print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=no\">".$gm_lang["skip_surnames"]."</a>";
	else print "<a href=\"famlist.php?show_all=yes&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>\n";
}
else if ($alpha != "@" && is_array(isset($surname))) {
	print "<a href=\"famlist.php?alpha=$alpha&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>";
}
else if ($alpha != "@") {
	if ($surname_sublist=="yes") print "<a href=\"famlist.php?alpha=$alpha&amp;surname_sublist=no\">".$gm_lang["skip_surnames"]."</a>";
	else print "<a href=\"famlist.php?alpha=$alpha&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>";
}
print "</div>\n";
print_footer();
?>