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

$GM_BLOCKS["print_todays_events"]["name"]		= GM_LANG_todays_events_block;
$GM_BLOCKS["print_todays_events"]["descr"]		= "todays_events_descr";
$GM_BLOCKS["print_todays_events"]["canconfig"]	= true;
$GM_BLOCKS["print_todays_events"]["config"] 	= array("filter"=>"all", "onlyBDM"=>"no");
$GM_BLOCKS["print_todays_events"]["rss"]		= true;

//-- today's events block
//-- this block prints a list of today's upcoming events of living people in your gedcom
function print_todays_events($block=true, $config="", $side, $index) {
	global $command, $TEXT_DIRECTION;
	global $GM_IMAGES, $GM_BLOCKS;
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
	PrintHelpLink("index_onthisday_help", "qm", "on_this_day");
	if ($GM_BLOCKS["print_upcoming_events"]["canconfig"]) {
		if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
			else $name = $gm_user->username;
			print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".GM_LANG_config_block."\" /></a>\n";
		}
	}
	print GM_LANG_on_this_day;
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
	$selindi = implode("[".GedcomConfig::$GEDCOMID."]','", $selindi);
	$selindi .= "[".GedcomConfig::$GEDCOMID."]'";
	$selindi = "'".$selindi;
	ListFunctions::GetIndiList("no", $selindi);
	$selfam = implode("[".GedcomConfig::$GEDCOMID."]','", $selfam);
	$selfam .= "[".GedcomConfig::$GEDCOMID."]'";
	$selfam = "'".$selfam;
	ListFunctions::GetFamList("no", $selfam);
	
	foreach($found_facts as $key=>$factarr) {
		$datestamp = $factarr[3];
		if ($factarr[2]=="INDI") {
			$person =& Person::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
			$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
			$gid = $factarr[0];
			$factrec = $factarr[1];
			if ($person->disp && $fact->disp) {
				$text = FactFunctions::GetCalendarFact($fact, $action, $filter, "all", GetCurrentYear(), GetCurrentMonth(), GetCurrentDay());
				if ($text != "filter") {
					if ($lastgid != $gid) {
						if ($lastgid != "") print "<br />";
						print "<a href=\"individual.php?pid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($person->revname.($person->revaddname == "" ? "" : " (".$person->revaddname.")"))."</b>";
						print "<img id=\"box-".$gid."-".$key."-sex\" src=\"".GM_IMAGE_DIR."/";
						if ($factarr[5] == "M") print $GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male;
						else if ($factarr[5] == "F") print $GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female;
						else print $GM_IMAGES["sexn"]["small"]."\" title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown;
						print "\" class=\"sex_image\" />";
						print $person->addxref;
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
			$family =& Family::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
			$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
			if ($family->disp && $fact->disp) {
				$text = FactFunctions::GetCalendarFact($fact, $action, $filter, "all", GetCurrentYear(), GetCurrentMonth(), GetCurrentDay());
				if ($text!="filter") {
					if ($lastgid!=$factarr[0]) {
						if ($lastgid != "") print "<br />";
						print "<a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\"><b>";
						print PrintReady($family->sortable_name.($family->sortable_addname == "" ? "" : "(".$family->sortable_addname.")"));
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
		PrintText($Advisory);
		print "</b><br />";
	} else if (!$OutputDone) {// No Facts were found
		$Advisory = "none_today_" . $config["filter"];
		print "<b>";
		PrintText($Advisory);
		print "</b><br />";
	}

	if ($block) print "</div>\n";
	print "</div>"; // blockcontent
	print "</div>"; // block
}

function print_todays_events_config($config) {
	global $GM_BLOCKS;
	
	if (empty($config)) $config = $GM_BLOCKS["print_todays_events"]["config"];
	if (!isset($config["filter"])) $config["filter"] = "all";
	if (!isset($config["onlyBDM"])) $config["onlyBDM"] = "no";

	print "<tr><td class=\"shade2 width20\">".GM_LANG_living_or_all."</td>";?>
	<td class="shade1">
 	<select name="filter">
	<option value="all"<?php if ($config["filter"]=="all") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
	<option value="living"<?php if ($config["filter"]=="living") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>

	<?php
	print "<tr><td class=\"shade2 width20\">";
 	PrintHelpLink("basic_or_all_help", "qm");
	print GM_LANG_basic_or_all."</td>";?>
	<td class="shade1">
	<select name="onlyBDM">
	<option value="no"<?php if ($config["onlyBDM"]=="no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
	<option value="yes"<?php if ($config["onlyBDM"]=="yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>
	<?php
}
?>