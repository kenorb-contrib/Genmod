<?php
/**
 * Gedcom Statistics Block
 *
 * This block prints statistical information for the currently active gedcom
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

$GM_BLOCKS["print_gedcom_stats"]["name"]        = $gm_lang["gedcom_stats_block"];
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
		global $GM_BLOCKS, $gm_lang, $GEDCOMID, $GEDCOMS, $ALLOW_CHANGE_GEDCOM, $command, $COMMON_NAMES_THRESHOLD, $GM_IMAGE_DIR, $GM_IMAGES;
		global $top10_block_present, $monthtonum, $gm_user;		// Set in index.php

		if (empty($config)) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];
		if (!isset($config['stat_indi'])) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];

		print "<div id=\"gedcom_stats\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("index_stats_help", "qm", "gedcom_stats");
		if ($GM_BLOCKS["print_gedcom_stats"]["canconfig"]) {
			$username = $gm_user->username;
			if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
				if ($command=="gedcom") $name = $GEDCOMID;
				else $name = $username;
				print "<a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=350,scrollbars=1,resizable=1'); return false;\">";
				print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
			}
		}
		print $gm_lang["gedcom_stats"];
		print "</div>";
		print "<div class=\"blockcontent\">";
		print "<b><a href=\"index.php?command=gedcom\">".PrintReady($GEDCOMS[$GEDCOMID]["title"])."</a></b><br />\n";
		$stats = GetCachedStatistics();
		if (isset($stats["gs_title"])) print $stats["gs_title"];
		
		print "<br />\n";
		print "<table><tr><td valign=\"top\" class=\"width20 wrap\"><table cellspacing=\"1\" cellpadding=\"0\">";
		if ($config["stat_indi"]=="yes") print "<tr><td>".$gm_lang["stat_individuals"]." </td><td class=\"rtl\"><b>&nbsp;<a href=\"indilist.php?surname_sublist=no\">".GetListSize("indilist")."</a></b></td></tr>";
		if ($config["stat_surname"]=="yes") {
			print "<tr><td>".$gm_lang["stat_surnames"]." </td><td class=\"rtl\"><b>&nbsp;<a href=\"indilist.php?surname_sublist=yes\">".$stats["gs_nr_surnames"]."</a></b></td></tr>";
		}
		if ($config["stat_fam"]=="yes") print "<tr><td>".$gm_lang["stat_families"]." </td><td class=\"rtl\"><b>&nbsp;<a href=\"famlist.php\">".$stats["gs_nr_fams"]."</a></b></td></tr>";
		if ($config["stat_sour"]=="yes") print "<tr><td>".$gm_lang["stat_sources"]." </td><td class=\"rtl\"><b>&nbsp;<a href=\"sourcelist.php\">".$stats["gs_nr_sources"]."</a></b></td></tr>";
		if ($config["stat_media"]=="yes") print "<tr><td>".$gm_lang["stat_media"]." </td><td class=\"rtl\"><b>&nbsp;<a href=\"medialist.php\">".$stats["gs_nr_media"]."</a></b></td></tr>";
		if ($config["stat_other"]=="yes") print "<tr><td>".$gm_lang["stat_other"]." </td><td class=\"rtl\"><b>&nbsp;".$stats["gs_nr_other"]."</b></td></tr>";
		if ($config["stat_events"]=="yes") {
			print "<tr><td>".$gm_lang["stat_events"]." </td><td class=\"rtl\"><b>&nbsp;".$stats["gs_nr_events"]."</b></td></tr>";
		}
		if ($config["stat_users"]=="yes") print "<tr><td>".$gm_lang["stat_users"]." </td><td class=\"rtl\"><b>&nbsp;".UserController::CountUsers()."</b></td></tr>";
		print "</table></td><td><br /></td><td valign=\"top\">";
		print "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\">";
		if ($config["stat_first_birth"]=="yes" && !empty($stats["gs_earliest_birth_gid"])) {
			print "<tr><td valign=\"top\">".$gm_lang["stat_earliest_birth"]."</td><td class=\"rtl\">&nbsp;<span style=\"font-weight: bold\"><a href=\"calendar.php?action=year&amp;year=".$stats["gs_earliest_birth_year"]."\">".$stats["gs_earliest_birth_year"]."</a></span>&nbsp;</td><td valign=\"top\" class=\"ltr wrap\">";
			$person =& Person::GetInstance($stats["gs_earliest_birth_gid"]);
			$person->PrintListPerson(false, false);
//			print_list_person($stats["gs_earliest_birth_gid"], array(GetPersonName($stats["gs_earliest_birth_gid"]), $GEDCOM), false, "", false);
			print "</td></tr>\n";
		}
		if ($config["stat_last_birth"]=="yes" && !empty($stats["gs_latest_birth_gid"])) {
			print "<tr><td valign=\"top\">".$gm_lang["stat_latest_birth"]."</td><td class=\"rtl\">&nbsp;<span style=\"font-weight: bold\"><a href=\"calendar.php?action=year&amp;year=".$stats["gs_latest_birth_year"]."\">".$stats["gs_latest_birth_year"]."</a></span>&nbsp;</td><td valign=\"top\" class=\"ltr wrap\">";
			$person =& Person::GetInstance($stats["gs_latest_birth_gid"]);
			$person->PrintListPerson(false, false);
//			print_list_person($stats["gs_latest_birth_gid"], array(GetPersonName($stats["gs_latest_birth_gid"]), $GEDCOM), false, "", false);
			print "</td></tr>\n";
		}
		if ($config["stat_long_life"]=="yes" && !empty($stats["gs_longest_live_gid"])) {
			print "<tr><td valign=\"top\">".$gm_lang["stat_longest_life"]."</td><td class=\"rtl\">&nbsp;<span style=\"font-weight: bold\">".$stats["gs_longest_live_years"]."</span>&nbsp;</td><td valign=\"top\" class=\"ltr wrap\">";
			$person =& Person::GetInstance($stats["gs_longest_live_gid"]);
			$person->PrintListPerson(false, false);
//			print_list_person($stats["gs_longest_live_gid"], array(GetPersonName($stats["gs_longest_live_gid"]), $GEDCOM), false, "", false);
			print "</td></tr>\n";
		}
		if ($config["stat_avg_life"]=="yes") {
			if (!empty($stats["gs_avg_age"])) {
				print "<tr><td valign=\"top\">".$gm_lang["stat_avg_age_at_death"]."</td><td valign=\"top\" class=\"rtl\">&nbsp;<span style=\"font-weight: bold\">";
				printf("%d", $stats["gs_avg_age"]);
				print "</span>&nbsp;</td><td>";
				print "</td></tr>\n";
			}
		}

		if ($config["stat_most_chil"]=="yes") {
			//-- most children
			if (!empty($stats["gs_most_children_nr"])) {
				print "<tr><td valign=\"top\">".$gm_lang["stat_most_children"]."</td><td class=\"rtl\">&nbsp;<span style=\"font-weight: bold\">".$stats["gs_most_children_nr"]."</span>&nbsp;</td><td valign=\"top\" class=\"ltr wrap\">";
				$family =& Family::GetInstance($stats["gs_most_children_gid"]);
				$family->PrintListFamily(false);
//				print_list_family($stats["gs_most_children_gid"], array(GetFamilyDescriptor($stats["gs_most_children_gid"]), $GEDCOM), false, "", false);
				print "</td></tr>\n";
			}
		}
		if ($config["stat_avg_chil"]=="yes") {
			//-- avg number of children
			if (!empty($stats["gs_avg_children"])) {
				print "<tr><td valign=\"top\">".$gm_lang["stat_average_children"]."</td><td valign=\"top\" class=\"rtl\">&nbsp;<span style=\"font-weight: bold\">";
				printf("%.2f", $stats["gs_avg_children"]);
				print "</span>&nbsp;</td><td>";
				print "</td></tr>\n";
			}
		}
		print "</table>";
		print "</td></tr></table>";
		// NOTE: Print the most common surnames
		if ($config["show_common_surnames"]=="yes") {
			$surnames = GetCommonSurnamesIndex($GEDCOMID);
			if (count($surnames)>0) {
				print "<br />";
				print_help_link("index_common_names_help", "qm", "common_surnames");
				print "<b>".$gm_lang["common_surnames"]."</b><br />\n";
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

		print "</div>\n";
		print "</div>";
}

function print_gedcom_stats_config($config) {
	global $gm_lang, $GM_BLOCKS, $TEXT_DIRECTION;
	
	if (empty($config)) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];
	if (!isset($config['stat_indi'])) $config = $GM_BLOCKS["print_gedcom_stats"]["config"];

	print "<tr><td class=\"shade2 width20\">".$gm_lang["gedcom_stats_show_surnames"]."</td>";?>
	<td class="shade1">
		<select name="show_common_surnames">
			<option value="yes"<?php if ($config["show_common_surnames"]=="yes") print " selected=\"selected\"";?>><?php print $gm_lang["yes"]; ?></option>
			<option value="no"<?php if ($config["show_common_surnames"]=="no") print " selected=\"selected\"";?>><?php print $gm_lang["no"]; ?></option>
		</select>
	</td></tr>
	<tr>
		<td class="shade2 width20"><?php print $gm_lang["stats_to_show"]; ?></td>
		<td class="shade1">
			<table>
				<tr>
					<td><input type="checkbox" value="yes" name="stat_indi" <?php if ($config['stat_indi']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_individuals"]; ?></td>
					<td><input type="checkbox" value="yes" name="stat_first_birth" <?php if ($config['stat_first_birth']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_earliest_birth"]; ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" value="yes" name="stat_surname" <?php if ($config['stat_surname']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_surnames"]; ?></td>
					<td><input type="checkbox" value="yes" name="stat_last_birth" <?php if ($config['stat_last_birth']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_latest_birth"]; ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" value="yes" name="stat_fam" <?php if ($config['stat_fam']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_families"]; ?></td>
					<td><input type="checkbox" value="yes" name="stat_long_life" <?php if ($config['stat_long_life']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_longest_life"]; ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" value="yes" name="stat_sour" <?php if ($config['stat_sour']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_sources"]; ?></td>
					<td><input type="checkbox" value="yes" name="stat_avg_life" <?php if ($config['stat_avg_life']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_avg_age_at_death"]; ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" value="yes" name="stat_other" <?php if ($config['stat_other']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_other"]; ?></td>
					<td><input type="checkbox" value="yes" name="stat_most_chil" <?php if ($config['stat_most_chil']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_most_children"]; ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" value="yes" name="stat_events" <?php if ($config['stat_events']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_events"]; ?></td>
					<td><input type="checkbox" value="yes" name="stat_avg_chil" <?php if ($config['stat_avg_chil']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_average_children"]; ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" value="yes" name="stat_users" <?php if ($config['stat_users']=="yes") print "checked=\"checked\""; ?> /> <?php print $gm_lang["stat_users"]; ?></td>
					<td><br /></td>
				</tr>
			</table>
		</td>
	</tr>
	<?php
}
?>
