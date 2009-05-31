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
 * @version $Id: patriarchlist.php,v 1.3 2006/04/30 18:44:15 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$patrilist = array();
$patrialpha = array();


function indi2roots() {
	//--global $TBLPREFIX, $GEDCOM;
	global $ct,$patrilist,$patrialpha;

	$my2indilist= array();
	$keys= array();
	$orignames= array();
	$person = array();
	
	$my2indilist = GetIndiList();
	$ct = count($my2indilist);

	// NOTE: First select the names then do the alphabetic sort
	$orignum=0;
	$i=0;
	$keys = array_keys($my2indilist);
	//--key is I<nr>
	
	while ($i < $ct) {
		$key=$keys[$i];
		$value= $my2indilist[$key]["names"][0][0];
		$value2= $my2indilist[$key]["gedfile"];
		$person= find_person_record($key);
		$famc="";
		$ctc= preg_match("/1\s*FAMC\s*@(.*)@/",$person,$match);
		if ($ctc > 0) {
			$famc= $match[1];
			$parents= find_parents($famc);
			if (($parents["WIFE"] == "") and ($parents["HUSB"] == "")) $famc= "";
		}
		
		//-- we assume that when there is a famc record, this person is not a patriarch
		//-- in special cases it is possible that a child is a member of a famc record but no parents are given
		//-- and so they are the patriarch's

		//-- first spouse record. assuming a person has just one father and one mother.
		if ($famc == "") {
			//--print "select:$orignum,$key,$value,$person<br>";
			$orignum ++;
			$orignames["$key"]["name"]=$value;
		 	$orignames["$key"]["gedfile"]=$value2;
		}
		$i++;
	}
	$ct= $orignum;
	$patrilist=$orignames;
	uasort($patrilist, "itemsort");
	$i=0;
	$keys = array_keys($patrilist);
	$oldletter= "";
	while ($i < $ct) {
		$key=$keys[$i];
		$value = get_sortable_name($key);
		$value2= $patrilist[$key]["gedfile"];
		$person= find_person_record($key);
		//--> Changed MA @@@ as in extract_surname() etc.
		$tmpnames = preg_split("/,/", $value);
		$tmpnames[0] = preg_replace(array("/ [jJsS][rR]\.?,/", "/ I+,/", "/^[a-z\. ]*/"), array(",",",",""), $tmpnames[0]);
		$tmpnames[0] = trim($tmpnames[0]);
		//-- check for all lowercase name and start over
		if (empty($tmpnames[0])) {
			$tmpnames = preg_split("/,/", $value);
			$tmpnames[0] = trim($tmpnames[0]);
		}
		$tmpletter = get_first_letter($tmpnames[0]);
		if ($tmpletter!=$oldletter) $oldletter=$tmpletter;
		if ((!isset($alpha)) || ($alpha = $tmpletter)) {
			$orignames["$key"]["name"]=$value;
		 	$orignames["$key"]["gedfile"]=$value2;
			$letter=$tmpletter;
			//<---- MA @@@
			if (!isset($patrialpha[$letter])) {
	 			$patrialpha[$letter]["letter"]= "$letter";
	 			$patrialpha[$letter]["gid"]= "$key";
			}
			else $patrialpha[$letter]["gid"].= ",$key";
		}
		$i++;
	}
	$patrilist=$orignames;
}
// end indi2roots


function put_patri_list() {
	//-- save the items in the database
	global $ct,$patrilist,$patrialpha;
	//-- print "start roots2database<br />";
	global $GEDCOM,$INDEX_DIRECTORY, $FP, $gm_lang;

	$indexfile = $INDEX_DIRECTORY.$GEDCOM."_patriarch.php";
	$FP = fopen($indexfile, "wb");
	if (!$FP) {
		print "<font class=\"error\">".$gm_lang["unable_to_create_index"]."</font>";
		exit;
	}

	fwrite($FP, 'a:1:{s:13:"patrilist";');
	fwrite($FP, serialize($patrilist));
	fwrite($FP, '}');
	fclose($FP);
}

//-- find all of the individuals who start with the given letter within patriarchlist
function get_alpha_patri($letter) {
	global $patrialpha, $patrilist;

	$tpatrilist = array();

	$list = $patrialpha[$letter]["gid"];
	$gids = preg_split("/[+,]/", $list);
	foreach($gids as $indexval => $gid)	{
		$tpatrilist[$gid] = $patrilist[$gid];
	}
	return $tpatrilist;
}

// NOTE: Get the patriarchlist from the datastore
function get_patri_list() {
	global $patrilist;
	
	return $patrilist;
}


print_header($gm_lang["dynasty_list"]);


print "<div class =\"center\">";
print "\n\t<h2>";
print_help_link("name_list_help", "qm", "name_list");
print $gm_lang["dynasty_list"]."</h2>";

indi2roots();
put_patri_list();
if (empty($surname_sublist)) $surname_sublist = "yes";
if (empty($show_all)) $show_all = "no";

// Remove slashes
if (isset($alpha)) $alpha = stripslashes($alpha);
if (isset($surname)) $surname = stripslashes($surname);

$pass = FALSE;
$tpatrilist = array();

//-- name in $patriarchalpha for sorting??? MA @@@ =====>

uasort($patrialpha, "lettersort");

print_help_link("alpha_help", "qm", "alpha_index");
foreach($patrialpha as $letter=>$list) {
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
		print "<a href=\"patriarchlist.php?alpha=".urlencode($letter)."&amp;surname_sublist=no\">";
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

$expalpha = $alpha;
if ($expalpha=="(") $expalpha = '\(';
if ($expalpha=="[") $expalpha = '\[';
if ($expalpha=="?") $expalpha = '\?';
if ($expalpha=="/") $expalpha = '\/';
print "<br /><br /><table class=\"list_table $TEXT_DIRECTION\"><tr>";

if (($surname_sublist=="yes")&&($show_all=="yes")) {
	$tpatrilist = get_patri_list();
	if (!isset($alpha)) $alpha="";
	// NOTE: Start printing names
	$surnames = array();
	$indi_hide=array();
	foreach($tpatrilist as $gid=>$fam) {
	// NOTE: Make sure that favorites from other gedcoms are not shown
    	if ($fam["gedfile"]==$GEDCOMS[$GEDCOM]["id"]) {
			// Added space to regexp after z to also remove prefixes
			if (displayDetailsById($gid)||showLivingNameById($gid)) {
				extract_surname($fam["name"]);
			}
			else $indi_hide[$gid."[".$fam["gedfile"]."]"] = 1;
		}
	}
	$i = 0;
	uasort($surnames, "itemsort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else if (($surname_sublist=="yes")&&(empty($surname))&&($show_all=="no")) {
	if (!isset($alpha)) $alpha="";
	$tpatrilist = get_alpha_patri($alpha);
	$surnames = array();
	$indi_hide=array();
	foreach($tpatrilist as $gid=>$fam) {
	//-- make sure that favorites from other gedcoms are not shown
        if ($fam["gedfile"]==$GEDCOMS[$GEDCOM]["id"]) {
			if (displayDetailsById($gid)||showLivingNameById($gid)) {
				extract_surname($fam["name"]);
			}
			else $indi_hide[$gid."[".$fam["gedfile"]."]"] = 1;
	    }
	}
	$i = 0;
	uasort($surnames, "itemsort");
	print "<div class=\"topbar\">".$gm_lang["surnames"]."</div>\n";
	PrintSurnameList($surnames, $_SERVER["SCRIPT_NAME"]);
}
else {
	$firstname_alpha = false;
	//-- if the surname is set then only get the names in that surname list
	if ((!empty($surname))&&($surname_sublist=="yes")) {
		$tpatrilist = get_patri_list();
		$npatrilist = array();
		foreach($tpatrilist as $gid=>$fam) {
			if (stristr($fam["name"],$surname)) $npatrilist[$gid] = $fam;
		}
		$tpatrilist = $npatrilist;
	}
	// NOTE: Get all individuals for the sublist
	if (($surname_sublist=="no")&&(!empty($alpha))&&($show_all=="no")) $tpatrilist = get_alpha_fams($alpha);
	
	// NOTE: Simplify processing for ALL indilist
	// NOTE: Skip surname is yes and ALL is chosen
	if (($surname_sublist=="no")&&($show_all=="yes")) {
		$tpatrilist = get_patri_list();
		print "<div class=\"topbar\">".$gm_lang["individuals"]."</div>\n";
		PrintFamilyList($tpatrilist);
	}
	else {
		print "<div class=\"topbar\">";
		if ($surname_sublist == "no") print $gm_lang["surnames"];
		else	print PrintReady(str_replace("#surname#", check_NN($surname), $gm_lang["fams_with_surname"]));
		print "</div>\n";
		PrintFamilyList($tpatrilist);
	}
}
print "</tr></table>";

print "<br />";
if ($alpha != "@") {
	if ($surname_sublist=="yes") print_help_link("skip_sublist_help", "qm", "skip_surnames");
	else print_help_link("skip_sublist_help", "qm", "show_surnames");
}
if ($show_all=="yes" && $alpha != "@"){
	if ($surname_sublist=="yes") print "<a href=\"patriarchlist.php?show_all=yes&amp;surname_sublist=no\">".$gm_lang["skip_surnames"]."</a>";
 	else print "<a href=\"patriarchlist.php?show_all=yes&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>";
}
else if ((!isset($alpha)) || ($alpha=="" && $alpha != "@")) {
	if ($surname_sublist=="yes") print "<a href=\"patriarchlist.php?show_all=yes&amp;surname_sublist=no\">".$gm_lang["skip_surnames"]."</a>";
	else print "<a href=\"patriarchlist.php?show_all=yes&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>";
}
else if ($alpha != "@" && is_array(isset($surname))) {
	print "<a href=\"patriarchlist.php?alpha=$alpha&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>";
}
else if ($alpha != "@"){
	if ($surname_sublist=="yes") print "<a href=\"patriarchlist.php?alpha=$alpha&amp;surname_sublist=no\">".$gm_lang["skip_surnames"]."</a>";
	else print "<a href=\"patriarchlist.php?alpha=$alpha&amp;surname_sublist=yes\">".$gm_lang["show_surnames"]."</a>";
}
print "</div>\n";
print_footer();
?>