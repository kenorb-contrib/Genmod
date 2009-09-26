<?php
/**
 * On This Day Events Block
 *
 * This block will print a list of today's events
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA02111-1307USA
 *
 * @package Genmod
 * @subpackage Blocks
 * @version $Id$
 */

$GM_BLOCKS["print_todays_events"]["name"]		= $gm_lang["todays_events_block"];
$GM_BLOCKS["print_todays_events"]["descr"]		= "todays_events_descr";
$GM_BLOCKS["print_todays_events"]["canconfig"]	= true;
$GM_BLOCKS["print_todays_events"]["config"] 	= array("filter"=>"all", "onlyBDM"=>"no");
$GM_BLOCKS["print_todays_events"]["rss"]		= true;

//-- today's events block
//-- this block prints a list of today's upcoming events of living people in your gedcom
function print_todays_events($block=true, $config="", $side, $index) {
	global $gm_lang, $command, $TEXT_DIRECTION;
	global $GM_IMAGE_DIR, $GM_IMAGES, $GM_BLOCKS;
	global $NAME_REVERSE, $GEDCOMID;
	global $gm_user;

	$block = true;// Always restrict this block's height

	if (empty($config)) $config = $GM_BLOCKS["print_todays_events"]["config"];
	if (isset($config["filter"])) $filter = $config["filter"];// "living" or "all"
	else $filter = "all";
	if (isset($config["onlyBDM"])) $onlyBDM = $config["onlyBDM"];// "yes" or "no"
	else $onlyBDM = "no";

	$skipfacts = "CHAN,BAPL,SLGC,SLGS,ENDL";

	$action = "today";

	$found_facts = BlockFunctions::GetCachedEvents($action, 1, $filter, $onlyBDM, $skipfacts);

	 //-- Start output
	print "<div id=\"on_this_day_events\" class=\"block\">";
	print "<div class=\"blockhc\">";
	print_help_link("index_onthisday_help", "qm", "on_this_day");
	if ($GM_BLOCKS["print_upcoming_events"]["canconfig"]) {
		if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id($GEDCOMID));
			else $name = $gm_user->username;
			print "<a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
		}
	}
	print $gm_lang["on_this_day"];
	print "</div>";
	print "<div class=\"blockcontent\" >";
	if ($block) print "<div class=\"small_inner_block\">\n";

	$OutputDone = false;
	$PrivateFacts = false;
	$lastgid="";
	
	// Cache the selected indi's and fams in the indilist and famlist
	$selindi = array();
	$selfam = array();
	foreach($found_facts as $key=>$factarr) {
		if ($factarr[2] == "INDI") $selindi[] = $factarr[0];
		if ($factarr[2] == "FAM") $selfam[] = $factarr[0];
	}
	$selindi = implode("[".$GEDCOMID."]','", $selindi);
	$selindi .= "[".$GEDCOMID."]'";
	$selindi = "'".$selindi;
	ListFunctions::GetIndiList("no", $selindi);
	$selfam = implode("[".$GEDCOMID."]','", $selfam);
	$selfam .= "[".$GEDCOMID."]'";
	$selfam = "'".$selfam;
	ListFunctions::GetFamList("no", $selfam);
	
	foreach($found_facts as $key=>$factarr) {
		$datestamp = $factarr[3];
		if ($factarr[2]=="INDI") {
			$person =& Person::GetInstance($factarr[0], "", $GEDCOMID);
			$fact = new Fact($factarr[0], $factarr[6], $factarr[1]);
			$gid = $factarr[0];
			$factrec = $factarr[1];
			if ($person->disp && $fact->disp) {
				$text = GetCalendarFact($factrec, $action, $filter, $gid);
				if ($text != "filter") {
					if ($lastgid != $gid) {
						if ($lastgid != "") print "<br />";
						if ($NAME_REVERSE) $name = str_replace(",", "", $factarr[4]);
						else $name = $factarr[4];
						print "<a href=\"individual.php?pid=$gid&amp;gedid=".$GEDCOMID."\"><b>".$name."</b>";
						print "<img id=\"box-".$gid."-".$key."-sex\" src=\"".$GM_IMAGE_DIR."/";
						if ($factarr[5] == "M") print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
						else if ($factarr[5] == "F") print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
						else print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
						print "\" class=\"sex_image\" />";
						print "</a><br />\n";
						$lastgid = $gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print $text;
					print "</div>";
					$OutputDone = true;
				}
			}
			else $PrivateFacts = true;
		}

		if ($factarr[2]=="FAM") {
			$family =& Family::GetInstance($factarr[0], "", $GEDCOMID);
			$fact = new Fact($factarr[0], $factarr[6], $factarr[1]);
			if ($family->disp && $fact->disp) {
				$text = GetCalendarFact($factarr[1], $action, $filter, $factarr[0]);
				if ($text!="filter") {
					if ($lastgid!=$factarr[0]) {
						if ($lastgid != "") print "<br />";
						print "<a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\"><b>";
						print $family->sortable_name;
						print "</b>";
						print $family->addxref;
						print "</a><br />\n";
						$lastgid=$family->xref;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print $text;
					print "</div>";
					$OutputDone = true;
				}
			}
			else $PrivateFacts = true;
		}
	}
	if ($PrivateFacts) {// Facts were found but not printed for some reason
		$Advisory = "none_today_privacy";
		if ($OutputDone) $Advisory = "more_today_privacy";
		print "<b>";
		print_text($Advisory);
		print "</b><br />";
	} else if (!$OutputDone) {// No Facts were found
		$Advisory = "none_today_" . $config["filter"];
		print "<b>";
		print_text($Advisory);
		print "</b><br />";
	}

	if ($block) print "</div>\n";
	print "</div>"; // blockcontent
	print "</div>"; // block
}

function print_todays_events_config($config) {
	global $gm_lang, $GM_BLOCKS;
	
	if (empty($config)) $config = $GM_BLOCKS["print_todays_events"]["config"];
	if (!isset($config["filter"])) $config["filter"] = "all";
	if (!isset($config["onlyBDM"])) $config["onlyBDM"] = "no";

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