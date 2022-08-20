<?php
/**
 * Top 10 botviews Block
 *
 * This block will show the top 10 records from the Gedcom that have been viewed the most by bots
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
 * @version $Id: top10_botviews.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["top10_botviews"]["name"]        = GM_LANG_top10_botviews;
$GM_BLOCKS["top10_botviews"]["descr"]       = "top10_pageviews_descr";
$GM_BLOCKS["top10_botviews"]["canconfig"]	= true;
$GM_BLOCKS["top10_botviews"]["config"] 		= array("num"=>10, "count_placement"=>"left");
$GM_BLOCKS["top10_botviews"]["rss"]       	= false;

function top10_botviews($block=true, $config="", $side, $index) {
	global $GM_BLOCKS, $command, $GM_IMAGES, $gm_user;

	print "<!-- Start Top10 Botviews Block //-->";
	// This block is only for admins
	if (!$gm_user->userGedcomAdmin()) return;
	if (empty($config)) $config = $GM_BLOCKS["top10_botviews"]["config"];
	if (isset($config["count_placement"])) $CountSide = $config["count_placement"];
	else $CountSide = "left";

	//-- load the lines from the database
	$ids = array();
	$limit = $config["num"]+1;
	$ids = CounterFunctions::GetCounters($limit, GedcomConfig::$GEDCOMID, true);
	

	//-- if no results are returned then don't do anything
	if (count($ids) == 0) {
		if ($gm_user->userIsAdmin()) {
			print "<div id=\"top10\" class=\"BlockContainer\">\n";
				print "<div class=\"BlockHeader\">";
					PrintHelpLink("index_top10_pageviews_help", "qm");
					print "<div class=\"BlockHeaderText\">".GM_LANG_top10_botviews."</div>";
				print "</div>";
				print "<div class=\"BlockContent\">\n";
					print "<span class=\"Warning\">\n".GM_LANG_top10_pageviews_msg."</span>\n";
				print "</div>";
			print "</div>\n";
		}
		return;
	}

	
	print "<div id=\"top10hits\" class=\"BlockContainer\">\n";
		print "<div class=\"BlockHeader\">";
			PrintHelpLink("index_top10_pageviews_help", "qm", "top10_botviews");
			if ($GM_BLOCKS["top10_botviews"]["canconfig"]) {
				if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
					if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
					else $name = $gm_user->username;
					print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
					BlockFunctions::PrintAdminIcon();
					print "</a>";
				}
			}
			print "<div class=\"BlockHeaderText\">".GM_LANG_top10_botviews."</div>";
		print "</div>";
		print "<div class=\"BlockContent\">\n";
			if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
			else print "<div class=\"RestrictedBlockHeightMain\">\n";
				BlockFunctions::PrintPageViews($ids, $CountSide, $config["num"], $block);
			print "</div>\n";
		print "</div>";
	print "</div>";
	print "<!-- End Top10 Botviews Block //-->";
}

function top10_botviews_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION;
	
	if (empty($config)) $config = $GM_BLOCKS["top10_botviews"]["config"];
	?>
	<tr><td class="NavBlockLabel"><?php print GM_LANG_num_to_show; ?></td><td class="NavBlockField"><input type="text" name="num" size="2" value="<?php print $config["num"]; ?>" /></td></tr>
	<tr><td class="NavBlockLabel"><?php print GM_LANG_before_or_after;?></td><td class="NavBlockField"><select name="count_placement">
		<option value="left"<?php if ($config["count_placement"]=="left") print " selected=\"selected\"";?>><?php print GM_LANG_before; ?></option>
		<option value="right"<?php if ($config["count_placement"]=="right") print " selected=\"selected\"";?>><?php print GM_LANG_after; ?></option>
	</select>
	</td></tr>
	<?php
}
?>
