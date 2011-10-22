<?php
/**
 * Recent Changes Block
 *
 * This block will print a list of recently accepted changes to the various record types
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package Genmod
 * @subpackage Blocks
 * @version $Id$
 */

$GM_BLOCKS["print_recent_changes"]["name"]      = GM_LANG_recent_changes_block;
$GM_BLOCKS["print_recent_changes"]["descr"]     = "recent_changes_descr";
$GM_BLOCKS["print_recent_changes"]["canconfig"]	= true;
$GM_BLOCKS["print_recent_changes"]["config"] 	= array("days"=>30, "hide_empty"=>"no", "show_indi"=>"yes", "show_fam"=>"yes", "show_sour"=>"yes", "show_repo"=>"yes", "show_obje"=>"yes");
$GM_BLOCKS["print_recent_changes"]["rss"]       = true;

//-- Recent Changes block
//-- this block prints a list of changes that have occurred recently in your gedcom
/**
 * @todo Find out why TOTAL_QUERIES is here???
**/
function print_recent_changes($block=true, $config="", $side, $index) {
	global $monthtonum, $HIDE_LIVE_PEOPLE, $command, $TEXT_DIRECTION;
	global $GM_IMAGES, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $TOTAL_QUERIES, $LAST_QUERY, $GM_BLOCKS, $SHOW_SOURCES;
	global $gm_user;

	$block = true;			// Always restrict this block's height

	if (empty($config)) $config = $GM_BLOCKS["print_recent_changes"]["config"];
	if ($config["days"] < 1 or $config["days"] > 30) $config["days"] = 30;
	if (isset($config["hide_empty"])) $HideEmpty = $config["hide_empty"];
	else $HideEmpty = "no";


	$action = "today";

	$found_facts = BlockFunctions::GetRecentChangeFacts(GetCurrentDay(), GetCurrentMonth(), GetCurrentYear(), $config["days"], $config);

// Start output
	if (count($found_facts)==0 and $HideEmpty=="yes") return false;
	//	Print block header
	print "<div id=\"recent_changes\" class=\"BlockContainer\">";
	print "<div class=\"BlockHeader\">";
	PrintHelpLink("recent_changes_help", "qm", "recent_changes");
	if ($GM_BLOCKS["print_recent_changes"]["canconfig"]) {
		$username = $gm_user->username;
		if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
			else $name = $username;
			print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".GM_LANG_config_block."\" /></a>\n";
		}
	}
	print GM_LANG_recent_changes;
	print "</div>";
	print "<div class=\"BlockContent\" >";
	if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
	else print "<div class=\"RestrictedBlockHeightMain\">\n";

	//	Print block content
	// 3 is recent changes
	define("GM_LANG_global_num3", $config["days"]);		// Make this visible
	if (count($found_facts)==0) {
		PrintText("recent_changes_none");
	} else {
		PrintText("recent_changes_some");
		$ASC = true;
		$IGNORE_FACTS = 1;
		$IGNORE_YEAR = 0;
		uasort($found_facts, "CompareFacts");
		$lastgid="";
		foreach($found_facts as $index=>$factarr) {
			if ($factarr[2]=="INDI") {
				$person =& Person::GetInstance($factarr[0]);
				$fact = New Fact($person->xref, "INDI", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $person->xref) {
					print "<a href=\"individual.php?pid=".$person->xref."&amp;gedid=".$person->gedcomid."\"><b>";
					print PrintReady($person->revname.($person->revaddname == "" ? "" : " (".$person->revaddname.")"))."</b>";
					print "<img id=\"box-".$person->xref."-".$index."-sex\" src=\"".GM_IMAGE_DIR."/";
					if ($person->sex == "M") print $GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male;
					else  if ($person->sex == "F") print $GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female;
					else print $GM_IMAGES["sexn"]["small"]."\" title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown;
					print "\" class=\"sex_image\" />";
					print $person->addxref;
					print "</a><br />\n";
					$lastgid = $person->xref;
				}
				if ($fact->disp) {
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					print " - <span class=\"date\">".GetChangedDate($fact->datestring);
					if ($fact->timestring != "") {
						print " - ".$fact->timestring;
					}
					print "</span>\n";
					print "</div><br />";
				}
			}

			if ($factarr[2]=="FAM") {
				$family =& Family::GetInstance($factarr[0]);
				$fact = New Fact($family->xref, "FAM", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $family->xref) {
					print "<a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\"><b>";
					print PrintReady($family->sortable_name.($family->sortable_addname == "" ? "" : "(".$family->sortable_addname.")"));
					print "</b>";
					print $family->addxref;
					print "</a><br />\n";
					$lastgid = $family->xref;
				}
				if ($fact->disp) {
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					print " - <span class=\"date\">".GetChangedDate($fact->datestring);
					if ($fact->timestring != "") {
						print " - ".$fact->timestring;
					}
					print "</span>\n";
					print "</div><br />";
				}
			}

			if ($factarr[2]=="SOUR") {
				$source =& Source::GetInstance($factarr[0]);
				$fact = New Fact($source->xref, "SOUR", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $source->xref) {
					print "<a href=\"source.php?sid=".$source->xref."&amp;gedid=".$source->gedcomid."\"><b>";
					print $source->descriptor;
					print "</b>";
					print $source->addxref;
					print "</a><br />\n";
					$lastgid = $source->xref;
				}
				if ($fact->disp) {
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					print " - <span class=\"date\">".GetChangedDate($fact->datestring);
					if ($fact->timestring != "") {
						print " - ".$fact->timestring;
					}
					print "</span>\n";
					print "</div><br />";
				}
			}

			if ($factarr[2]=="REPO") {
				$repo =& Repository::GetInstance($factarr[0]);
				$fact = New Fact($repo->xref, "REPO", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $repo->xref) {
					print "<a href=\"repo.php?rid=".$repo->xref."&amp;gedid=".$repo->gedcomid."\"><b>";
					print $repo->descriptor;
					print "</b>";
					print $repo->addxref;
					print "</a><br />\n";
					$lastgid = $repo->xref;
				}
				if ($fact->disp) {
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					print " - <span class=\"date\">".GetChangedDate($fact->datestring);
					if ($fact->timestring != "") {
						print " - ".$fact->timestring;
					}
					print "</span>\n";
					print "</div><br />";
				}
			}
			if ($factarr[2]=="OBJE") {
				$media =& MediaItem::GetInstance($factarr[0]);
				$fact = New Fact($media->xref, "OBJE", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $media->xref) {
					print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\"><b>";
					print $media->title;
					print "</b>";
					print $media->addxref;
					print "</a><br />\n";
					$lastgid = $media->xref;
				}
				if ($fact->disp) {
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					print " - <span class=\"date\">".GetChangedDate($fact->datestring);
					if ($fact->timestring != "") {
						print " - ".$fact->timestring;
					}
					print "</span>\n";
					print "</div><br />";
				}
			}
		}

	}

	print "</div>\n"; //small_inner_block
	print "</div>"; // blockcontent
	print "</div>"; // block

}

function print_recent_changes_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_recent_changes"]["config"];

	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_days_to_show."</td>";?>
	<td class="shade1">
		<input type="text" name="days" size="2" value="<?php print $config["days"]; ?>" />
	</td></tr>

	<?php
	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_rcblock_show_indi."</td>";?>
	<td class="shade1">
	<select name="show_indi">
		<option value="no"<?php if ($config["show_indi"] == "no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["show_indi"] == "yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>
	

	<?php
	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_rcblock_show_fam."</td>";?>
	<td class="shade1">
	<select name="show_fam">
		<option value="no"<?php if ($config["show_fam"] == "no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["show_fam"] == "yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>

	<?php
	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_rcblock_show_sour."</td>";?>
	<td class="shade1">
	<select name="show_sour">
		<option value="no"<?php if ($config["show_sour"] == "no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["show_sour"] == "yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>

	<?php
	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_rcblock_show_repo."</td>";?>
	<td class="shade1">
	<select name="show_repo">
		<option value="no"<?php if ($config["show_repo"] == "no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["show_repo"] == "yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>

	<?php
	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_rcblock_show_obje."</td>";?>
	<td class="shade1">
	<select name="show_obje">
		<option value="no"<?php if ($config["show_obje"] == "no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["show_obje"] == "yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>
	
	<?php
  	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_show_empty_block."</td>";?>
	<td class="shade1">
	<select name="hide_empty">
		<option value="no"<?php if ($config["hide_empty"]=="no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["hide_empty"]=="yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>
	<tr><td colspan="2" class="shade1 wrap">
		<span class="Error"><?php print GM_LANG_hide_block_warn; ?></span>
	</td></tr>
	<?php
}
?>
