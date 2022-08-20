<?php
/**
 * Top 10 Surnames Block
 *
 * This block will show the top 10 surnames that occur most frequently in the active gedcom
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
 * @version $Id: top10_surnames.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_block_name_top10"]["name"]        = GM_LANG_block_top10;
$GM_BLOCKS["print_block_name_top10"]["descr"]       = "block_top10_descr";
$GM_BLOCKS["print_block_name_top10"]["canconfig"]   = true;
$GM_BLOCKS["print_block_name_top10"]["config"] 		= array("num"=>10, "count_placement"=>"left");
$GM_BLOCKS["print_block_name_top10"]["rss"]			= true;

function print_block_name_top10($block=true, $config="", $side, $index) {
	global $TEXT_DIRECTION;
	global $GM_BLOCKS, $command, $GM_IMAGES, $gm_user;

	print "<!-- Start Top10 Surnames Block //-->";
	function top_surname_sort($a, $b) {
		return $b["match"] - $a["match"];
	}

	if (empty($config)) $config = $GM_BLOCKS["print_block_name_top10"]["config"];
	if (isset($config["count_placement"])) $CountSide = $config["count_placement"];
	else $CountSide = "left";

	//-- cache the result in the session so that subsequent calls do not have to
	//-- perform the calculation all over again.
	if (!isset($_SESSION["top10_surnames"])) {
		$surnames = BlockFunctions::GetTopSurnames($config["num"]);
		$_SESSION["top10_surnames"] = serialize($surnames);
	}
	else $surnames = unserialize($_SESSION["top10_surnames"]);

	if (count($surnames)>0) {
		print "<div id=\"top10surnames\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
			PrintHelpLink("index_common_names_help", "qm");
			if ($GM_BLOCKS["print_block_name_top10"]["canconfig"]) {
				if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
					if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
					else $name = $gm_user->username;
					print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
					BlockFunctions::PrintAdminIcon();
					print "</a>";
				}
			}
			print "<div class=\"BlockHeaderText\">".str_replace("10", $config["num"], GM_LANG_block_top10_title)."</div>";
			print "</div>";
			print "<div class=\"BlockContent\">\n";
				if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
				else print "<div class=\"RestrictedBlockHeightMain\">\n";
					if ($block) print "<table class=\"Top10BlockTable\">";
					else print "<table class=\"Top10BlockTableWide\">";
						foreach($surnames as $indexval => $surname) {
							print "<tr>";
							if ($CountSide=="left") {
								print "<td class=\"Top10LeftCounter\">";
									print "[".$surname["match"]."]";
								print "</td>";
							}
							print "<td class=\"Top10BlockLink\" ";
							print "><a href=\"indilist.php?surname=".urlencode($surname["name"])."\">".PrintReady($surname["name"])."</a></td>";
							if ($CountSide=="right") {
								print "<td class=\"Top10RightCounter\">";
									print "[".$surname["match"]."]";
								print "</td>";
							}
							print "</tr>";
						}
					print "</table>";
				print "</div>\n";
			print "</div>";
		print "</div>";
	}
	print "<!-- End Top10 Surnames Block //-->";
}

function print_block_name_top10_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_block_name_top10"]["config"];

	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_num_to_show."</td>";?>
	<td class="NavBlockField">
		<input type="text" name="num" size="2" value="<?php print $config["num"]; ?>" />
	</td></tr>

	<?php
  	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_before_or_after."</td>";?>
	<td class="NavBlockField">
	<select name="count_placement">
		<option value="left"<?php if ($config["count_placement"]=="left") print " selected=\"selected\"";?>><?php print GM_LANG_before; ?></option>
		<option value="right"<?php if ($config["count_placement"]=="right") print " selected=\"selected\"";?>><?php print GM_LANG_after; ?></option>
	</select>
	</td></tr>
	<?php
}
?>
