<?php
/**
 * Top 10 Surnames Block
 *
 * This block will show the top 10 surnames that occur most frequently in the active gedcom
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
 * @version $Id$
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_block_name_top10"]["name"]        = $gm_lang["block_top10"];
$GM_BLOCKS["print_block_name_top10"]["descr"]       = "block_top10_descr";
$GM_BLOCKS["print_block_name_top10"]["canconfig"]   = true;
$GM_BLOCKS["print_block_name_top10"]["config"] 		= array("num"=>10, "count_placement"=>"left");
$GM_BLOCKS["print_block_name_top10"]["rss"]			= true;

function print_block_name_top10($block=true, $config="", $side, $index) {
	global $gm_lang, $GEDCOMID, $DEBUG, $TEXT_DIRECTION;
	global $COMMON_NAMES_ADD, $COMMON_NAMES_REMOVE, $COMMON_NAMES_THRESHOLD, $GM_BLOCKS, $command, $GM_IMAGES, $GM_IMAGE_DIR, $gm_user;

	function top_surname_sort($a, $b) {
		return $b["match"] - $a["match"];
	}

	if (empty($config)) $config = $GM_BLOCKS["print_block_name_top10"]["config"];
	if (isset($config["count_placement"])) $CountSide = $config["count_placement"];
	else $CountSide = "left";

	//-- cache the result in the session so that subsequent calls do not have to
	//-- perform the calculation all over again.
	$surnames = GetTopSurnames($config["num"]);

	if (count($surnames)>0) {
		print "<div id=\"top10surnames\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("index_common_names_help", "qm");
		if ($GM_BLOCKS["print_block_name_top10"]["canconfig"]) {
			if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
				if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id($GEDCOMID));
				else $name = $gm_user->username;
				print "<a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
				print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
			}
		}
		print "<b>".str_replace("10", $config["num"], $gm_lang["block_top10_title"])."</b>";
		print "</div>";
		print "<div class=\"blockcontent\">\n";
		if ($block) print "<div class=\"small_inner_block\">\n";
		if ($block) print "<table width=\"90%\">";
		else print "<table>";
		foreach($surnames as $indexval => $surname) {
			print "<tr valign=\"top\">";
			if ($CountSide=="left") {
				print "<td dir=\"ltr\" align=\"right\">";
				if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
				print "[".$surname["match"]."]";
				if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
				print "</td>";
			}
			print "<td class=\"name2\" ";
			if ($block) print "width=\"86%\"";
			print "><a href=\"indilist.php?surname=".urlencode($surname["name"])."\">".PrintReady($surname["name"])."</a></td>";
			if ($CountSide=="right") {
				print "<td dir=\"ltr\" align=\"right\">";
				if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
				print "[".$surname["match"]."]";
				if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
				print "</td>";
			}
			print "</tr>";
		}
		print "</table>";
		if ($block) print "</div>\n";
		print "</div>";
		print "</div>";
	}
}

function print_block_name_top10_config($config) {
	global $gm_lang, $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_block_name_top10"]["config"];

	print "<tr><td class=\"shade2 width20\">".$gm_lang["num_to_show"]."</td>";?>
	<td class="shade1">
		<input type="text" name="num" size="2" value="<?php print $config["num"]; ?>" />
	</td></tr>

	<?php
  	print "<tr><td class=\"shade2 width20\">".$gm_lang["before_or_after"]."</td>";?>
	<td class="shade1">
	<select name="count_placement">
		<option value="left"<?php if ($config["count_placement"]=="left") print " selected=\"selected\"";?>><?php print $gm_lang["before"]; ?></option>
		<option value="right"<?php if ($config["count_placement"]=="right") print " selected=\"selected\"";?>><?php print $gm_lang["after"]; ?></option>
	</select>
	</td></tr>
	<?php
}
?>
