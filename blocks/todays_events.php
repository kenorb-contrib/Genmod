<?php
/**
 * On This Day Events Block
 *
 * This block will print a list of today's events
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA02111-1307USA
 *
 * @package Genmod
 * @subpackage Blocks
 * @version $Id: todays_events.php 29 2022-07-17 13:18:20Z Boudewijn $
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

	print "\n<!-- Start Todays Events Block //-->";
	$block = true;// Always restrict this block's height

	if (empty($config)) $config = $GM_BLOCKS["print_todays_events"]["config"];
	if (isset($config["filter"])) $filter = $config["filter"];// "living" or "all"
	else $filter = "all";
	if (isset($config["onlyBDM"])) $onlyBDM = $config["onlyBDM"];// "yes" or "no"
	else $onlyBDM = "no";

	$skipfacts = "CHAN,BAPL,SLGC,SLGS,ENDL";

	$action = "today";

	 //-- Start output
	print "<div id=\"on_this_day_events\" class=\"BlockContainer\">";
		print "<div class=\"BlockHeader\">";
			PrintHelpLink("index_onthisday_help", "qm", "on_this_day");
			if ($GM_BLOCKS["print_upcoming_events"]["canconfig"]) {
				if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
					if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
					else $name = $gm_user->username;
					print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
					BlockFunctions::PrintAdminIcon();
					print "</a>";
				}
			}
			print "<div class=\"BlockHeaderText\">".GM_LANG_on_this_day."</div>";
		print "</div>";
		print "<div class=\"BlockContent\" >";
			if ($block) print "<div class=\"RestrictedBlockHeightRight\" id=\"TodaysEventBlock\">\n";
			else print "<div class=\"RestrictedBlockHeightMain\" id=\"TodaysEventBlock\">\n";
				?>
				<script language="javascript" type="text/javascript">
				<!--
				function load_todays_events_block() {
					sndReq("TodaysEventBlock", "loadblockonthisday", true, "filter", "<?php print $filter; ?>", "onlyBDM", "<?php print $onlyBDM; ?>", "skipfacts", "<?php print $skipfacts; ?>", "blockaction", "<?php print $action; ?>");
				}
				addLoadEvent(load_todays_events_block);
				//-->
				</script>
				<?php
			print "</div>\n";
		print "</div>"; // blockcontent
	print "</div>"; // block
	print "<!-- End Todays Events Block //-->\n";
}

function print_todays_events_config($config) {
	global $GM_BLOCKS;
	
	if (empty($config)) $config = $GM_BLOCKS["print_todays_events"]["config"];
	if (!isset($config["filter"])) $config["filter"] = "all";
	if (!isset($config["onlyBDM"])) $config["onlyBDM"] = "no";

	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_living_or_all."</td>";?>
	<td class="NavBlockField">
 	<select name="filter">
	<option value="all"<?php if ($config["filter"]=="all") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
	<option value="living"<?php if ($config["filter"]=="living") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>

	<?php
	print "<tr><td class=\"NavBlockLabel\">";
 	PrintHelpLink("basic_or_all_help", "qm");
	print GM_LANG_basic_or_all."</td>";?>
	<td class="NavBlockField">
	<select name="onlyBDM">
	<option value="no"<?php if ($config["onlyBDM"]=="no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
	<option value="yes"<?php if ($config["onlyBDM"]=="yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>
	<?php
}
?>