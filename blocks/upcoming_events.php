<?php
/**
 * On Upcoming Events Block
 *
 * This block will print a list of upcoming events
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package Genmod
 * @subpackage Blocks
 * $Id: upcoming_events.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

$GM_BLOCKS["print_upcoming_events"]["name"] 		= GM_LANG_upcoming_events_block;
$GM_BLOCKS["print_upcoming_events"]["descr"] 		= "upcoming_events_descr";
$GM_BLOCKS["print_upcoming_events"]["canconfig"] 	= true;
$GM_BLOCKS["print_upcoming_events"]["config"] 		= array("days"=>15, "filter"=>"all", "onlyBDM"=>"no");
$GM_BLOCKS["print_upcoming_events"]["rss"]			= true;

//-- upcoming events block
//-- this block prints a list of upcoming events of people in your gedcom
function print_upcoming_events($block=true, $config="", $side, $index) {
	global $command, $TEXT_DIRECTION;
	global $GM_IMAGES, $GM_BLOCKS;
	global $gm_user;

	print "<!-- Start Upcoming Events Block //-->";
	$block = true; // Always restrict this block's height
	if (empty($config)) $config = $GM_BLOCKS["print_upcoming_events"]["config"];
//	if (!isset(GedcomConfig::$DAYS_TO_SHOW_LIMIT)) GedcomConfig::$DAYS_TO_SHOW_LIMIT = 15;
	if (isset($config["days"])) $daysprint = $config["days"];
	else $daysprint = 30;
	if (isset($config["filter"])) $filter = $config["filter"]; // "living" or "all"
	else $filter = "all";
	if (isset($config["onlyBDM"])) $onlyBDM = $config["onlyBDM"]; // "yes" or "no"
	else $onlyBDM = "no";

	if ($daysprint < 1) $daysprint = 1;
	if ($daysprint > GedcomConfig::$DAYS_TO_SHOW_LIMIT) $daysprint = GedcomConfig::$DAYS_TO_SHOW_LIMIT; // valid: 1 to limit

	$skipfacts = "CHAN,BAPL,SLGC,SLGS,ENDL";	// These are always excluded

	$action = "upcoming";
	
	// Output starts here
	print "<div id=\"upcoming_events\" class=\"BlockContainer\">";
		print "<div class=\"BlockHeader\">";
			PrintHelpLink("index_events_help", "qm", "upcoming_events");
			if ($GM_BLOCKS["print_upcoming_events"]["canconfig"]) {
				if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
					if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
					else $name = $gm_user->username;
					print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
					BlockFunctions::PrintAdminIcon();
					print "</a>";
				}
			}
			print "<div class=\"BlockHeaderText\">".GM_LANG_upcoming_events."</div>";
		print "</div>";
		print "<div class=\"BlockContent\" >";
			if ($block) print "<div class=\"RestrictedBlockHeightRight\" id=\"UpcomingEventBlock\">\n";
			else print "<div class=\"RestrictedBlockHeightMain\" id=\"UpcomingEventBlock\">\n";
				?>
				<script language="javascript" type="text/javascript">
				<!--
				function load_upcoming_events_block() {
					sndReq("UpcomingEventBlock", "loadblockupcoming", true, "filter", "<?php print $filter; ?>", "onlyBDM", "<?php print $onlyBDM; ?>", "skipfacts", "<?php print $skipfacts; ?>", "blockaction", "<?php print $action; ?>", "daysprint", "<?php print $daysprint; ?>");
				}
				addLoadEvent(load_upcoming_events_block);
				//-->
				</script>
				<?php
			
			print "</div>\n";
		print "</div>"; // blockcontent
	print "</div>"; // block
	print "<!-- End Upcoming Events Block //-->";
}

function print_upcoming_events_config($config) {
	global $GM_BLOCKS;
	
	if (empty($config)) $config = $GM_BLOCKS["print_upcoming_events"]["config"];
//	if (!isset(GedcomConfig::$DAYS_TO_SHOW_LIMIT)) GedcomConfig::$DAYS_TO_SHOW_LIMIT = 30;
	if (!isset($config["days"])) $config["days"] = 30;
	if (!isset($config["filter"])) $config["filter"] = "all";
	if (!isset($config["onlyBDM"])) $config["onlyBDM"] = "no";

	if ($config["days"] < 1) $config["days"] = 1;
	if ($config["days"] > GedcomConfig::$DAYS_TO_SHOW_LIMIT) $config["days"] = GedcomConfig::$DAYS_TO_SHOW_LIMIT; // valid: 1 to limit

	print "<tr><td class=\"NavBlockLabel\">";
	PrintHelpLink("days_to_show_help", "qm");
	print GM_LANG_days_to_show."</td>";?>
	<td class="NavBlockField">
		<input type="text" name="days" size="2" value="<?php print $config["days"]; ?>" />
	</td></tr>

	<?php
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
