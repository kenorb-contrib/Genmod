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

$GM_BLOCKS["print_recent_changes"]["name"]      = $gm_lang["recent_changes_block"];
$GM_BLOCKS["print_recent_changes"]["descr"]     = "recent_changes_descr";
$GM_BLOCKS["print_recent_changes"]["canconfig"]	= true;
$GM_BLOCKS["print_recent_changes"]["config"] 	= array("days"=>30, "hide_empty"=>"no");
$GM_BLOCKS["print_recent_changes"]["rss"]       = true;

//-- Recent Changes block
//-- this block prints a list of changes that have occurred recently in your gedcom
/**
 * @todo Find out why TOTAL_QUERIES is here???
**/
function print_recent_changes($block=true, $config="", $side, $index) {
	global $gm_lang, $month, $year, $day, $monthtonum, $HIDE_LIVE_PEOPLE, $SHOW_ID_NUMBERS, $command, $TEXT_DIRECTION, $SHOW_FAM_ID_NUMBERS;
	global $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOMID, $DEBUG, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $TOTAL_QUERIES, $LAST_QUERY, $GM_BLOCKS, $SHOW_SOURCES;
	global $medialist, $gm_user;

	$block = true;			// Always restrict this block's height

	if (empty($config)) $config = $GM_BLOCKS["print_recent_changes"]["config"];
	if ($config["days"]<1 or $config["days"]>30) $config["days"] = 30;
	if (isset($config["hide_empty"])) $HideEmpty = $config["hide_empty"];
	else $HideEmpty = "no";


	$action = "today";

	$found_facts = GetRecentChangeFacts($day, $month, $year, $config["days"]);

// Start output
	if (count($found_facts)==0 and $HideEmpty=="yes") return false;
	//	Print block header
	print "<div id=\"recent_changes\" class=\"block\">";
	print "<div class=\"blockhc\">";
	print_help_link("recent_changes_help", "qm", "recent_changes");
	if ($GM_BLOCKS["print_recent_changes"]["canconfig"]) {
		$username = $gm_user->username;
		if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id($GEDCOMID));
			else $name = $username;
			print "<a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
		}
	}
	print $gm_lang["recent_changes"];
	print "</div>";
	print "<div class=\"blockcontent\" >";
	if ($block) print "<div class=\"small_inner_block\">\n";

	//	Print block content
	$gm_lang["global_num1"] = $config["days"];		// Make this visible
	if (count($found_facts)==0) {
		print_text("recent_changes_none");
	} else {
		print_text("recent_changes_some");
		$ASC = true;
		$IGNORE_FACTS = 1;
		$IGNORE_YEAR = 0;
		uasort($found_facts, "CompareFacts");
		$lastgid="";
		foreach($found_facts as $index=>$factarr) {
			if ($factarr[2]=="INDI") {
				$person = Person::GetInstance($factarr[0]);
				$fact = New Fact($person->xref, $factarr[3], $factarr[1]);
				if ($lastgid != $person->xref) {
					print "<a href=\"individual.php?pid=".$person->xref."&amp;gedid=".$person->gedcomid."\"><b>";
					print $person->sortable_name;
					print "</b>";
					print "<img id=\"box-".$person->xref."-".$index."-sex\" src=\"$GM_IMAGE_DIR/";
					if ($person->sex == "M") print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
					else  if ($person->sex == "F") print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
					else print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
					print "\" class=\"sex_image\" />";
					print $person->addxref;
					print "</a><br />\n";
					$lastgid = $person->xref;
				}
				print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
				print GM_FACT_CHAN;
				print " - <span class=\"date\">".GetChangedDate($fact->datestring);
				if ($fact->timestring != "") {
					print " - ".$fact->timestring;
				}
				print "</span>\n";
				print "</div><br />";
			}

			if ($factarr[2]=="FAM") {
				$family = Family::GetInstance($factarr[0]);
				$fact = New Fact($family->xref, $factarr[3], $factarr[1]);
				if ($lastgid != $family->xref) {
					print "<a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\"><b>";
					print $family->sortable_name;
					print "</b>";
					print $family->addxref;
					print "</a><br />\n";
					$lastgid = $family->xref;
				}
				print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
				print GM_FACT_CHAN;
				print " - <span class=\"date\">".GetChangedDate($fact->datestring);
				if ($fact->timestring != "") {
					print " - ".$fact->timestring;
				}
				print "</span>\n";
				print "</div><br />";
			}

			if ($factarr[2]=="SOUR") {
				$source =& Source::GetInstance($factarr[0]);
				$fact = New Fact($source->xref, $factarr[3], $factarr[1]);
				if ($lastgid != $source->xref) {
					print "<a href=\"source.php?sid=".$source->xref."&amp;gedid=".$source->gedcomid."\"><b>";
					print $source->descriptor;
					print "</b>";
					print $source->addxref;
					print "</a><br />\n";
					$lastgid = $source->xref;
				}
				print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
				print GM_FACT_CHAN;
				print " - <span class=\"date\">".GetChangedDate($fact->datestring);
				if ($fact->timestring != "") {
					print " - ".$fact->timestring;
				}
				print "</span>\n";
				print "</div><br />";
			}

			if ($factarr[2]=="REPO") {
				$repo =& Repository::GetInstance($factarr[0]);
				$fact = New Fact($repo->xref, $factarr[3], $factarr[1]);
				if ($lastgid != $repo->xref) {
					print "<a href=\"repo.php?rid=".$repo->xref."&amp;gedid=".$repo->gedcomid."\"><b>";
					print $repo->descriptor;
					print "</b>";
					print $repo->addxref;
					print "</a><br />\n";
					$lastgid = $repo->xref;
				}
				print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
				print GM_FACT_CHAN;
				print " - <span class=\"date\">".GetChangedDate($fact->datestring);
				if ($fact->timestring != "") {
					print " - ".$fact->timestring;
				}
				print "</span>\n";
				print "</div><br />";
			}
			if ($factarr[2]=="OBJE") {
				$media =& MediaItem::GetInstance($factarr[0]);
				$fact = New Fact($media->xref, $factarr[3], $factarr[1]);
				if ($lastgid != $media->xref) {
					print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\"><b>";
					print $media->title;
					print "</b>";
					print $media->addxref;
					print "</a><br />\n";
					$lastgid = $media->xref;
				}
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

	if ($block) print "</div>\n"; //small_inner_block
	print "</div>"; // blockcontent
	print "</div>"; // block

}

function print_recent_changes_config($config) {
	global $gm_lang, $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_recent_changes"]["config"];

	print "<tr><td width=\"20%\" class=\"shade2\">".$gm_lang["days_to_show"]."</td>";?>
	<td class="shade1">
		<input type="text" name="days" size="2" value="<?php print $config["days"]; ?>" />
	</td></tr>

	<?php
  	print "<tr><td width=\"20%\" class=\"shade2\">".$gm_lang["show_empty_block"]."</td>";?>
	<td class="shade1">
	<select name="hide_empty">
		<option value="no"<?php if ($config["hide_empty"]=="no") print " selected=\"selected\"";?>><?php print $gm_lang["no"]; ?></option>
		<option value="yes"<?php if ($config["hide_empty"]=="yes") print " selected=\"selected\"";?>><?php print $gm_lang["yes"]; ?></option>
	</select>
	</td></tr>
	<tr><td colspan="2" class="shade1 wrap">
		<span class="error"><?php print $gm_lang["hide_block_warn"]; ?></span>
	</td></tr>
	<?php
}
?>
