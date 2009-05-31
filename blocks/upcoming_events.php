<?php
/**
 * On Upcoming Events Block
 *
 * This block will print a list of upcoming events
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2008 Genmod Development Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package Genmod
 * @subpackage Blocks
 * $Id: upcoming_events.php,v 1.26 2009/02/07 08:45:14 sjouke Exp $
 */

$GM_BLOCKS["print_upcoming_events"]["name"] 		= $gm_lang["upcoming_events_block"];
$GM_BLOCKS["print_upcoming_events"]["descr"] 		= "upcoming_events_descr";
$GM_BLOCKS["print_upcoming_events"]["canconfig"] 	= true;
$GM_BLOCKS["print_upcoming_events"]["config"] 		= array("days"=>15, "filter"=>"all", "onlyBDM"=>"no");
$GM_BLOCKS["print_upcoming_events"]["rss"]			= true;

//-- upcoming events block
//-- this block prints a list of upcoming events of people in your gedcom
function print_upcoming_events($block=true, $config="", $side, $index) {
	global $gm_lang, $month, $year, $day, $monthtonum, $HIDE_LIVE_PEOPLE, $SHOW_ID_NUMBERS, $command, $TEXT_DIRECTION, $SHOW_FAM_ID_NUMBERS;
	global $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $REGEXP_DB, $DEBUG, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $LAST_QUERY, $GM_BLOCKS;
	global $INDEX_DIRECTORY, $USE_RTL_FUNCTIONS, $NAME_REVERSE, $GEDCOMID;
	global $DAYS_TO_SHOW_LIMIT, $CIRCULAR_BASE, $gm_username, $TBLPREFIX, $Users;

	$block = true; // Always restrict this block's height
	if (empty($config)) $config = $GM_BLOCKS["print_upcoming_events"]["config"];
	if (!isset($DAYS_TO_SHOW_LIMIT)) $DAYS_TO_SHOW_LIMIT = 15;
	if (isset($config["days"])) $daysprint = $config["days"];
	else $daysprint = 30;
	if (isset($config["filter"])) $filter = $config["filter"]; // "living" or "all"
	else $filter = "all";
	if (isset($config["onlyBDM"])) $onlyBDM = $config["onlyBDM"]; // "yes" or "no"
	else $onlyBDM = "no";

	if ($daysprint < 1) $daysprint = 1;
	if ($daysprint > $DAYS_TO_SHOW_LIMIT) $daysprint = $DAYS_TO_SHOW_LIMIT; // valid: 1 to limit

	$skipfacts = "CHAN,BAPL,SLGC,SLGS,ENDL";	// These are always excluded

	$action = "upcoming";
	
	$found_facts = GetCachedEvents($action, $daysprint, $filter, $onlyBDM, $skipfacts);
	
	// Output starts here
	print "<div id=\"upcoming_events\" class=\"block\">";
	print "<div class=\"blockhc\">";
	print_help_link("index_events_help", "qm", "upcoming_events");
	if ($GM_BLOCKS["print_upcoming_events"]["canconfig"]) {
		$username = $gm_username;
		if ((($command=="gedcom")&&($Users->userGedcomAdmin($username))) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", $GEDCOM);
			else $name = $username;
			print "<a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
		}
	}
	print $gm_lang["upcoming_events"];
	print "</div>";
	print "<div class=\"blockcontent\" >";
	if ($block) print "<div class=\"small_inner_block\">\n";
	
	$OutputDone = false;
	$PrivateFacts = false;
	$lastgid="";
//	print "Facts found: ".count($found_facts)."<br />";

	// Cache the selected indi's and fams in the indilist and famlist
	$selindi = array();
	$selfam = array();
	foreach($found_facts as $key=>$factarray) {
		if ($factarray[2] == "INDI") $selindi[] = $factarray[0];
		if ($factarray[2] == "FAM") $selfam[] = $factarray[0];
	}
	$selindi = implode("[".$GEDCOMID."]','", $selindi);
	$selindi .= "[".$GEDCOMID."]'";
	$selindi = "'".$selindi;
	GetIndiList("no", $selindi);
	$selfam = implode("[".$GEDCOMID."]','", $selfam);
	$selfam .= "[".$GEDCOMID."]'";
	$selfam = "'".$selfam;
	GetFamList("no", $selfam);
	
	foreach($found_facts as $key=>$factarray) {
		$datestamp = $factarray[3];
		if ($factarray[2]=="INDI") {
			$gid = $factarray[0];
			$factrec = $factarray[1];
			$disp = true;
			if (($filter=="living" and IsDeadId($gid)) || !ShowLivingNameByID($gid)) $disp = false;
			if ($disp) {
				if ($onlyBDM == "yes") $filterev = "bdm";
				else $filterev = "all";
				$text = GetCalendarFact($factrec, $action, $filter, $gid, $filterev);
				if ($text!="filter") {
					if ($text=="") {
						$PrivateFacts = true;
					} 
					else {
						if ($lastgid!=$gid) {
							if ($lastgid != "") print "<br />";
							if ($NAME_REVERSE) $name = str_replace(",", "", $factarray[4]);
							else $name = $factarray[4];
							print "<a href=\"individual.php?pid=$gid&amp;ged=".$GEDCOM."\"><b>";
							if (HasChinese($name)) print PrintReady($name." (".GetSortableAddName($gid, false).")");
							else print PrintReady($name);
							print "</b>";
							print "<img id=\"box-".$gid."-".$key."-sex\" src=\"$GM_IMAGE_DIR/";
							if ($factarray[5] == "M") print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
							else if ($factarray[5] == "F") print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
							else print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
							print "\" class=\"sex_image\" />";
							if ($SHOW_ID_NUMBERS) {
								if ($TEXT_DIRECTION=="ltr") print "&lrm;($gid)&lrm;";
								else print "&rlm;($gid)&rlm;";
							}
							print "</a><br />\n";
							$lastgid=$gid;
						}
						print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
						print $text;
						print "</div>";
						$OutputDone = true;
					}
				}
			}
		}

		if ($factarray[2]=="FAM") {
			$gid = $factarray[0];
			$factrec = $factarray[1];
			$disp = true;
			if ($filter=="living") {
				$parents = FindParents($gid);
				if (IsDeadId($parents["HUSB"])) $disp = false;
				else if (!displayDetailsByID($parents["HUSB"])) {
					$disp = false;
					$PrivateFacts = true;
				}
				if ($disp) {
					if (IsDeadId($parents["WIFE"])) $disp = false;
					else if (!displayDetailsByID($parents["WIFE"])) {
						$disp = false;
						$PrivateFacts = true;
					}
				}
			}
			else if (!displayDetailsByID($gid, "FAM")) {
				$disp = false;
				$PrivateFacts = true;
			}
			if ($disp) {
				if ($onlyBDM == "yes") $filterev = "bdm";
				else $filterev = "all";
				$text = GetCalendarFact($factrec, $action, $filter, $gid, $filterev);
				if ($text!="filter") {
					if ($text=="") {
						$PrivateFacts = true;
					} 
					else {
						if ($lastgid!=$gid) {
							$name = GetFamilyDescriptor($gid);
							if ($lastgid != "") print "<br />";
							print "<a href=\"family.php?famid=$gid&amp;ged=".$GEDCOM."\"><b>";
							if (HasChinese($name)) print PrintReady($name." (".GetFamilyAddDescriptor($gid).")");
							else print PrintReady($name);
							print "</b>";
							if ($SHOW_FAM_ID_NUMBERS) {
								if ($TEXT_DIRECTION=="ltr") print " &lrm;($gid)&lrm;";
								else print " &rlm;($gid)&rlm;";
							}
							print "</a><br />\n";
							$lastgid=$gid;
						}
						print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
						print $text;
						print "</div>";
						$OutputDone = true;
					}
				}
			}
		}
	}

	if ($PrivateFacts) { // Facts were found but not printed for some reason
		$gm_lang["global_num1"] = $daysprint;
		$Advisory = "no_events_privacy";
		if ($OutputDone) $Advisory = "more_events_privacy";
		if ($daysprint==1) $Advisory .= "1";
		print "<b>";
		print_text($Advisory);
		print "</b><br />";
	} 
	else if (!$OutputDone) { // No Facts were found
		$gm_lang["global_num1"] = $daysprint;
		$Advisory = "no_events_" . $config["filter"];
		if ($daysprint==1) $Advisory .= "1";
		print "<b>";
		print_text($Advisory);
		print "</b><br />";
	}

	if ($block) print "</div>\n";
	print "</div>"; // blockcontent
	print "</div>"; // block
}

function print_upcoming_events_config($config) {
	global $gm_lang, $GM_BLOCKS, $DAYS_TO_SHOW_LIMIT, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_upcoming_events"]["config"];
	if (!isset($DAYS_TO_SHOW_LIMIT)) $DAYS_TO_SHOW_LIMIT = 30;
	if (!isset($config["days"])) $config["days"] = 30;
	if (!isset($config["filter"])) $config["filter"] = "all";
	if (!isset($config["onlyBDM"])) $config["onlyBDM"] = "no";

	if ($config["days"] < 1) $config["days"] = 1;
	if ($config["days"] > $DAYS_TO_SHOW_LIMIT) $config["days"] = $DAYS_TO_SHOW_LIMIT; // valid: 1 to limit

	print "<tr><td class=\"shade2 width20\">";
	print_help_link("days_to_show_help", "qm");
	print $gm_lang["days_to_show"]."</td>";?>
	<td class="shade1">
		<input type="text" name="days" size="2" value="<?php print $config["days"]; ?>" />
	</td></tr>

	<?php
 	print "<tr><td class=\"shade2 width20\">".$gm_lang["living_or_all"]."</td>";?>
	<td class="shade1">
	<select name="filter">
		<option value="all"<?php if ($config["filter"]=="all") print " selected=\"selected\"";?>><?php print $gm_lang["no"]; ?></option>
		<option value="living"<?php if ($config["filter"]=="living") print " selected=\"selected\"";?>><?php print $gm_lang["yes"]; ?></option>
	</select>
	</td></tr>

	<?php
 	print "<tr><td class=\"shade2 width20\">";
	print_help_link("basic_or_all_help", "qm");
	print $gm_lang["basic_or_all"]."</td>";?>
	<td class="shade1">
	<select name="onlyBDM">
 	<option value="no"<?php if ($config["onlyBDM"]=="no") print " selected=\"selected\"";?>><?php print $gm_lang["no"]; ?></option>
 	<option value="yes"<?php if ($config["onlyBDM"]=="yes") print " selected=\"selected\"";?>><?php print $gm_lang["yes"]; ?></option>
 	</select>
	</td></tr>
 <?php
}
?>
