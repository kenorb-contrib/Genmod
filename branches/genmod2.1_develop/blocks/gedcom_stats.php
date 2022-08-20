<?php
/**
 * Gedcom Statistics Block
 *
 * This block prints statistical information for the currently active gedcom
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
 * @version $Id: gedcom_stats.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_gedcom_stats"]["name"]        = GM_LANG_gedcom_stats_block;
$GM_BLOCKS["print_gedcom_stats"]["descr"]        = "gedcom_stats_descr";
$GM_BLOCKS["print_gedcom_stats"]["canconfig"]   = true;
$GM_BLOCKS["print_gedcom_stats"]["config"] = array("show_common_surnames"=>"yes"
	,"stat_indi"=>"yes"
	,"stat_fam"=>"yes"
	,"stat_sour"=>"yes"
	,"stat_media"=>"yes"
	,"stat_other"=>"yes"
	,"stat_surname"=>"yes"
	,"stat_events"=>"yes"
	,"stat_users"=>"yes"
	,"stat_first_birth"=>"yes"
	,"stat_last_birth"=>"yes"
	,"stat_long_life"=>"yes"
	,"stat_avg_life"=>"yes"
	,"stat_most_chil"=>"yes"
	,"stat_avg_chil"=>"yes"
	);
$GM_BLOCKS["print_gedcom_stats"]["rss"]			= true;

//-- function to print the gedcom statistics block

function print_gedcom_stats($block = true, $config="", $side, $index) {
	global $GM_BLOCKS, $GEDCOMS, $command, $GM_IMAGES;
	global $top10_block_present, $monthtonum, $gm_user;		// Set in index.php

	print "<!-- Start Gedcom Stats Block //-->";
		if (empty($config)) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];
		if (!isset($config['stat_indi'])) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];

		print "<div id=\"gedcom_stats\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
				PrintHelpLink("index_stats_help", "qm", "gedcom_stats");
				if ($GM_BLOCKS["print_gedcom_stats"]["canconfig"]) {
					$username = $gm_user->username;
					if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
						if ($command=="gedcom") $name = GedcomConfig::$GEDCOMID;
						else $name = $username;
						print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=350,scrollbars=1,resizable=1'); return false;\">";
						BlockFunctions::PrintAdminIcon();
						print "</a>";
					}
				}
				print "<div class=\"BlockHeaderText\">".GM_LANG_gedcom_stats."</div>";
			print "</div>"; // Close BlockHeader
			print "<div class=\"BlockContent\">";
				print "<div class=\"GedcomStatsTextBlock\">";
					print "<div class=\"BlockSubTitle\"><a href=\"index.php?command=gedcom\">".PrintReady($GEDCOMS[GedcomConfig::$GEDCOMID]["title"])."</a></div>\n";
					$stats = BlockFunctions::GetCachedStatistics();
					if (isset($stats["gs_title"])) print $stats["gs_title"];
				print "</div>\n"; // Close GedcomStatsTextBlock
			
			print "<div class=\"GedcomStatsLeftData\">";
				print "<table class=\"BlockTable\">";
				if ($config["stat_indi"]=="yes") print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_individuals." </td><td class=\"BlockField\"><b>&nbsp;<a href=\"indilist.php?surname_sublist=no\">".GetListSize("indilist")."</a></b></td></tr>";
				if ($config["stat_surname"]=="yes") {
					print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_surnames." </td><td class=\"BlockField\"><b>&nbsp;<a href=\"indilist.php?surname_sublist=yes\">".$stats["gs_nr_surnames"]."</a></b></td></tr>";
				}
				if ($config["stat_fam"]=="yes") print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_families." </td><td class=\"BlockField\"><b>&nbsp;<a href=\"famlist.php\">".$stats["gs_nr_fams"]."</a></b></td></tr>";
				if ($config["stat_sour"]=="yes") print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_sources." </td><td class=\"BlockField\"><b>&nbsp;<a href=\"sourcelist.php\">".$stats["gs_nr_sources"]."</a></b></td></tr>";
				if ($config["stat_media"]=="yes") print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_media." </td><td class=\"BlockField\"><b>&nbsp;<a href=\"medialist.php\">".$stats["gs_nr_media"]."</a></b></td></tr>";
				if ($config["stat_other"]=="yes") print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_other." </td><td class=\"BlockField\"><b>&nbsp;".$stats["gs_nr_other"]."</b></td></tr>";
				if ($config["stat_events"]=="yes") {
					print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_events." </td><td class=\"BlockField\"><b>&nbsp;".$stats["gs_nr_events"]."</b></td></tr>";
				}
				if ($config["stat_users"]=="yes") print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_users." </td><td class=\"BlockField\"><b>&nbsp;".UserController::CountUsers()."</b></td></tr>";
				print "</table>";
			print "</div>"; // close GedcomStatsLeftData
			print "<div class=\"GedcomStatsRightData\">";
				print "<table class=\"BlockTable\">";
				if ($config["stat_first_birth"]=="yes" && !empty($stats["gs_earliest_birth_gid"])) {
					print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_earliest_birth."</td><td class=\"BlockField GedcomStatsBlockField\"><a href=\"calendar.php?link=17&amp;action=year&amp;year=".$stats["gs_earliest_birth_year"]."\">".$stats["gs_earliest_birth_year"]."</a></td><td class=\"BlockLabel\">";
					$person =& Person::GetInstance($stats["gs_earliest_birth_gid"]);
					if ($person->disp) $person->PrintListPerson(false, false);
					print "</td></tr>\n";
				}
				if ($config["stat_last_birth"]=="yes" && !empty($stats["gs_latest_birth_gid"])) {
					print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_latest_birth."</td><td class=\"BlockField GedcomStatsBlockField\"><a href=\"calendar.php?link=18&amp;action=year&amp;year=".$stats["gs_latest_birth_year"]."\">".$stats["gs_latest_birth_year"]."</a></td><td class=\"BlockLabel\">";
					$person =& Person::GetInstance($stats["gs_latest_birth_gid"]);
					if ($person->disp) $person->PrintListPerson(false, false);
					print "</td></tr>\n";
				}
				if ($config["stat_long_life"]=="yes" && !empty($stats["gs_longest_live_gid"])) {
					print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_longest_life."</td><td class=\"BlockField GedcomStatsBlockField\">".$stats["gs_longest_live_years"]."</td><td class=\"BlockLabel\">";
					$person =& Person::GetInstance($stats["gs_longest_live_gid"]);
					$person->PrintListPerson(false, false);
					print "</td></tr>\n";
				}
				if ($config["stat_avg_life"]=="yes") {
					if (!empty($stats["gs_avg_age"])) {
						print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_avg_age_at_death."</td><td class=\"BlockField GedcomStatsBlockField\">";
						printf("%d", $stats["gs_avg_age"]);
						print "</td><td>";
						print "</td></tr>\n";
					}
				}
		
				if ($config["stat_most_chil"]=="yes") {
					//-- most children
					if (!empty($stats["gs_most_children_nr"])) {
						print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_most_children."</td><td class=\"BlockField GedcomStatsBlockField\">".$stats["gs_most_children_nr"]."</td><td class=\"BlockLabel\">";
						$family =& Family::GetInstance($stats["gs_most_children_gid"]);
						if ($family->disp) $family->PrintListFamily(false);
						print "</td></tr>\n";
					}
				}
				if ($config["stat_avg_chil"]=="yes") {
					//-- avg number of children
					if (!empty($stats["gs_avg_children"])) {
						print "<tr><td class=\"BlockLabel\">".GM_LANG_stat_average_children."</td><td class=\"BlockField GedcomStatsBlockField\">";
						printf("%.2f", $stats["gs_avg_children"]);
						print "</td><td>";
						print "</td></tr>\n";
					}
				}
				print "</table>";
			print "</div>"; // close GedcomStatsRightData
			print "<div class=\"GedcomStatsTextBlock\">";
				// NOTE: Print the most common surnames
				if ($config["show_common_surnames"]=="yes") {
					$surnames = NameFunctions::GetCommonSurnamesIndex(GedcomConfig::$GEDCOMID);
					if (count($surnames)>0) {
						PrintHelpLink("index_common_names_help", "qm", "common_surnames");
						print "<b>".GM_LANG_common_surnames."</b><br />\n";
						$i=0;
						foreach($surnames as $indexval => $surname) {
							if (stristr($surname["name"], "@N.N")===false) {
								if ($i>0) {
									print ", ";
								}
								print "<a href=\"indilist.php?surname=".urlencode($surname["name"])."\">".PrintReady($surname["name"])."</a>";
								$i++;
							}
						}
					}
				}
		
			print "</div>\n"; // Close GedcomStatsTextBlock
		print "</div>"; // Close BlockContent
	print "</div>"; // Close BlockContainer
	print "<!-- End Gedcom Stats Block //-->";
}

function print_gedcom_stats_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION;
	
	if (empty($config)) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];
	if (!isset($config['stat_indi'])) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];

	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_gedcom_stats_show_surnames."</td>";?>
	<td class="NavBlockField">
		<select name="show_common_surnames">
			<option value="yes"<?php if ($config["show_common_surnames"]=="yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
			<option value="no"<?php if ($config["show_common_surnames"]=="no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		</select>
	</td></tr>
	<tr>
		<td class="NavBlockLabel"><?php print GM_LANG_stats_to_show; ?></td>
		<td class="NavBlockField">
			<input type="checkbox" value="yes" name="stat_indi" <?php if ($config['stat_indi']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_individuals; ?><br />
			<input type="checkbox" value="yes" name="stat_first_birth" <?php if ($config['stat_first_birth']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_earliest_birth; ?><br />
			<input type="checkbox" value="yes" name="stat_surname" <?php if ($config['stat_surname']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_surnames; ?><br />
			<input type="checkbox" value="yes" name="stat_last_birth" <?php if ($config['stat_last_birth']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_latest_birth; ?><br />
			<input type="checkbox" value="yes" name="stat_fam" <?php if ($config['stat_fam']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_families; ?><br />
			<input type="checkbox" value="yes" name="stat_long_life" <?php if ($config['stat_long_life']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_longest_life; ?><br />
			<input type="checkbox" value="yes" name="stat_sour" <?php if ($config['stat_sour']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_sources; ?><br />
			<input type="checkbox" value="yes" name="stat_avg_life" <?php if ($config['stat_avg_life']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_avg_age_at_death; ?><br />
			<input type="checkbox" value="yes" name="stat_other" <?php if ($config['stat_other']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_other; ?><br />
			<input type="checkbox" value="yes" name="stat_most_chil" <?php if ($config['stat_most_chil']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_most_children; ?><br />
			<input type="checkbox" value="yes" name="stat_events" <?php if ($config['stat_events']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_events; ?><br />
			<input type="checkbox" value="yes" name="stat_avg_chil" <?php if ($config['stat_avg_chil']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_average_children; ?><br />
			<input type="checkbox" value="yes" name="stat_users" <?php if ($config['stat_users']=="yes") print "checked=\"checked\""; ?> /> <?php print GM_LANG_stat_users; ?>
		</td>
	</tr>
	<?php
}
?>
