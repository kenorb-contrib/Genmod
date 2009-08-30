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
	global $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $GEDCOMID, $REGEXP_DB, $DEBUG, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $TOTAL_QUERIES, $LAST_QUERY, $GM_BLOCKS, $SHOW_SOURCES;
	global $medialist, $gm_username, $gm_user;

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
		$username = $gm_username;
		if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", $GEDCOM);
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
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid)) {
					$indirec = FindPersonRecord($gid);
					if ($lastgid!=$gid) {
						$name = CheckNN(GetSortableName($gid));
						print "<a href=\"individual.php?pid=$gid&amp;ged=".$GEDCOM."\"><b>";
						if (HasChinese($name)) print PrintReady($name." (".GetSortableAddName($gid, $indirec, false).")");
						else print PrintReady($name);
						print "</b>";
						print "<img id=\"box-".$gid."-".$index."-sex\" src=\"$GM_IMAGE_DIR/";
						if (preg_match("/1 SEX M/", $indirec)>0) print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
						else  if (preg_match("/1 SEX F/", $indirec)>0) print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
						else print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
						print "\" class=\"sex_image\" />";
						if ($SHOW_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print "&lrm;($gid)&lrm;";
						   else print "&rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".GetChangedDate($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}

			if ($factarr[2]=="FAM") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "FAM")) {
					$famrec = FindFamilyRecord($gid);
					$name = GetFamilyDescriptor($gid);
					if ($lastgid!=$gid) {
						print "<a href=\"family.php?famid=$gid&amp;ged=".$GEDCOM."\"><b>";
						if (HasChinese($name)) print PrintReady($name." (".GetFamilyAddDescriptor($gid).")");
						else print PrintReady($name);
						print "</b>";
						if ($SHOW_FAM_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print " &lrm;($gid)&lrm;";
						   else print " &rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".GetChangedDate($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}

			if ($factarr[2]=="SOUR") {
				$source =& Source::GetInstance($factarr[0]);
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if ($source->disp) {
					if ($lastgid!=$gid) {
						print "<a href=\"source.php?sid=$gid&amp;gedid=".$GEDCOMID."\"><b>".PrintReady($source->descriptor)."</b>";
						if ($SHOW_FAM_ID_NUMBERS) print $source->addxref;
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					print " - <span class=\"date\">".$source->lastchanged."</span>\n";
					print "</div><br />";
				}
			}

			if ($factarr[2]=="REPO") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "REPO")) {
					$reporec = FindRepoRecord($gid);
					$name = GetRepoDescriptor($gid);
					if ($lastgid!=$gid) {
						print "<a href=\"repo.php?rid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
						if ($SHOW_FAM_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print " &lrm;($gid)&lrm;";
						   else print " &rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".GetChangedDate($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}
			if ($factarr[2]=="OBJE") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "OBJE", 1, true)) {
					$mediarec = FindMediaRecord($gid);
					if (isset($medialist[$gid]["title"]) && $medialist[$gid]["title"] != "") $title=$medialist[$gid]["title"];
					else $title = $medialist[$gid]["file"];
					$SearchTitle = preg_replace("/ /","+",$title);
					if ($lastgid!=$gid) {
 						print "<a href=\"medialist.php?action=filter&amp;search=yes&amp;filter=$SearchTitle&amp;ged=".$GEDCOM."\"><b>".PrintReady($title)."</b>";
						if ($SHOW_FAM_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print " &lrm;($gid)&lrm;";
						   else print " &rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print GM_FACT_CHAN;
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".GetChangedDate($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
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
