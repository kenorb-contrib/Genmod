<?php
/**
 * Top 10 Pageviews Block
 *
 * This block will show the top 10 records from the Gedcom that have been viewed the most
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @version $Id: top10_pageviews.php,v 1.7 2006/04/09 15:53:27 roland-d Exp $
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["top10_pageviews"]["name"]           = $gm_lang["top10_pageviews"];
$GM_BLOCKS["top10_pageviews"]["descr"]          = "top10_pageviews_descr";
$GM_BLOCKS["top10_pageviews"]["canconfig"]        = true;
$GM_BLOCKS["top10_pageviews"]["config"] = array("num"=>10, "count_placement"=>"left");

function top10_pageviews($block=true, $config="", $side, $index) {
	global $gm_lang, $GEDCOM, $INDEX_DIRECTORY, $GM_BLOCKS, $command, $GM_IMAGES, $GM_IMAGE_DIR, $SHOW_SOURCES, $TEXT_DIRECTION, $TBLPREFIX, $gm_username;

	if (empty($config)) $config = $GM_BLOCKS["top10_pageviews"]["config"];
	if (isset($config["count_placement"])) $CountSide = $config["count_placement"];
	else $CountSide = "left";

	//-- load the lines from the database
	$ids = array();
	$limit = $config["num"]+1;
	$sql = "SELECT * from ".$TBLPREFIX."counters WHERE (c_id REGEXP '".$GEDCOM."') ORDER BY c_number DESC LIMIT ".$limit;
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$p1 = strpos($row["c_id"],"[");
		$id = substr($row["c_id"],0,$p1);
		$ids[$id] = $row["c_number"];
	}
	$res->free();

	//-- if no results are returned then don't do anything
	if (count($ids) == 0) {
		if (userIsAdmin($gm_username)) {
			print "<div id=\"top10\" class=\"block\">\n";
			print "<div class=\"blockhc\">";
			print_help_link("index_top10_pageviews_help", "qm");
			print $gm_lang["top10_pageviews"];
			print "</div>";
			print "<div class=\"blockcontent\">\n";
			print "<span class=\"error\">\n".$gm_lang["top10_pageviews_msg"]."</span>\n";
			print "</div>";
			print "</div>\n";
		}
		return;
	}

	
	print "<div id=\"top10hits\" class=\"block\">\n";
	print "<div class=\"blockhc\">";
	print_help_link("index_top10_pageviews_help", "qm");
	if ($GM_BLOCKS["top10_pageviews"]["canconfig"]) {
		$username = $gm_username;
		if ((($command=="gedcom")&&(userGedcomAdmin($username))) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", $GEDCOM);
			else $name = $username;
			print "<a href=\"javascript: configure block\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
		}
	}
	print $gm_lang["top10_pageviews"];
	print "</div>";
	print "<div class=\"blockcontent\">\n";
	if ($block) print "<div class=\"small_inner_block\">\n";
	if (count($ids)>0) {
		if ($block) print "<table width=\"90%\">";
		else print "<table>";
		$i=0;
		foreach($ids as $id=>$count) {
			$gedrec = find_gedcom_record($id);
			$ct = preg_match("/0 @(.*)@ (.*)/", $gedrec, $match);
			if ($ct>0) {
				$type = trim($match[2]);
				$disp = displayDetailsById($id, $type);
				if ($disp) {
					if ($type=="INDI") {
						print "<tr valign=\"top\">";
						if ($CountSide=="left") {
							print "<td dir=\"ltr\" align=\"right\">";
							if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
							print "[".$count."]";
							if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
							print "</td>";
						}
						print "<td class=\"name2\" ><a href=\"individual.php?pid=".urlencode($id)."\">".PrintReady(get_person_name($id))."</a></td>";
						if ($CountSide=="right") {
							print "<td dir=\"ltr\" align=\"right\">";
							if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
							print "[".$count."]";
							if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
							print "</td>";
						}
						print "</tr>";
						$i++;
					}
					if ($type=="FAM") {
						print "<tr valign=\"top\">";
						if ($CountSide=="left") {
							print "<td dir=\"ltr\" align=\"right\">";
							if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
							print "[".$count."]";
							if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
							print "</td>";
						}
						print "<td class=\"name2\" ><a href=\"family.php?famid=".urlencode($id)."\">".PrintReady(get_family_descriptor($id))."</a></td>";
						if ($CountSide=="right") {
							print "<td dir=\"ltr\" align=\"right\">";
							if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
							print "[".$count."]";
							if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
							print "</td>";
						}
						print "</tr>";
						$i++;
					}
					if ($type=="REPO") {
						if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) {
							print "<tr valign=\"top\">";
							if ($CountSide=="left") {
								print "<td dir=\"ltr\" align=\"right\">";
								if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
								print "[".$count."]";
								if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
								print "</td>";
							}
							print "<td class=\"name2\" ><a href=\"repo.php?rid=".urlencode($id)."\">".PrintReady(get_repo_descriptor($id))."</a></td>";
							if ($CountSide=="right") {
								print "<td dir=\"ltr\" align=\"right\">";
								if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
								print "[".$count."]";
								if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
								print "</td>";
							}
							print "</tr>";
							$i++;
						}
					}
					if ($type=="SOUR") {
						if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) {
							print "<tr valign=\"top\">";
							if ($CountSide=="left") {
								print "<td dir=\"ltr\" align=\"right\">";
								if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
								print "[".$count."]";
								if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
								print "</td>";
							}
							print "<td class=\"name2\" ><a href=\"source.php?sid=".urlencode($id)."\">".PrintReady(get_source_descriptor($id))."</a></td>";
							if ($CountSide=="right") {
								print "<td dir=\"ltr\" align=\"right\">";
								if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
								print "[".$count."]";
								if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
								print "</td>";
							}
							print "</tr>";
							$i++;
						}
					}
					if ($i>=$config["num"]) break;
				}
			}
		}
		print "</table>";
	}
	else print "<b>".$gm_lang["top10_pageviews_nohits"]."</b>\n";
	if ($block) print "</div>\n";
	print "</div>";
	print "</div>";
}

function top10_pageviews_config($config) {
	global $gm_lang, $GM_BLOCKS,$TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["top10_pageviews"]["config"];
	?>
	<table class="facts_table <?php print $TEXT_DIRECTION; ?>">
	<tr><td class="shade2"><?php print $gm_lang["num_to_show"]; ?></td><td class="shade1"><input type="text" name="num" size="2" value="<?php print $config["num"]; ?>" /></td></tr>
	<tr><td class="shade2"><?php print $gm_lang["before_or_after"];?></td><td class="shade1"><select name="count_placement">
		<option value="left"<?php if ($config["count_placement"]=="left") print " selected=\"selected\"";?>><?php print $gm_lang["before"]; ?></option>
		<option value="right"<?php if ($config["count_placement"]=="right") print " selected=\"selected\"";?>><?php print $gm_lang["after"]; ?></option>
	</select>
	</td></tr>
	<?php
}
?>
