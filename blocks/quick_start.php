<?php
/**
 * Login Block
 *
 * This block prints a form that will allow a user perform a quick search
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

$GM_BLOCKS["print_quickstart_block"]["name"]        = GM_LANG_quickstart_block;
$GM_BLOCKS["print_quickstart_block"]["descr"]       = "quickstart";
$GM_BLOCKS["print_quickstart_block"]["type"]        = "both";
$GM_BLOCKS["print_quickstart_block"]["canconfig"]	= true;
$GM_BLOCKS["print_quickstart_block"]["config"] 		= array("search_all_geds"=>"no");
$GM_BLOCKS["print_quickstart_block"]["rss"]       	= false;

/**
 * Print Login Block
 *
 * Prints a block allowing the user to login to the site directly from the portal
 */
function print_quickstart_block($block = true, $config="", $side, $index) {
	global $command, $gm_user, $TEXT_DIRECTION, $GM_IMAGES, $GM_BLOCKS, $GEDCOMS;
	
	if (empty($config)) $config = $GM_BLOCKS["print_quickstart_block"]["config"];
	if (!isset($config['search_all_geds'])) $config = $GM_BLOCKS["print_quickstart_block"]["config"];

	print "<div id=\"quickstart_block\" class=\"block $TEXT_DIRECTION\">\n";
	print "<div class=\"blockhc\">";
	PrintHelpLink("index_quickstart_help", "qm", "quickstart");
	if ($GM_BLOCKS["print_quickstart_block"]["canconfig"]) {
		if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
			else $name = $gm_user->username;
			print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=350,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".GM_LANG_config_block."\" /></a>\n";
		}
	}
	print GM_LANG_quickstart;
	print "</div>";
	print "<form method=\"post\" action=\"search.php\" name=\"searchform\">";
	print "<input type=\"hidden\" name=\"action\" value=\"quickstart\" />";
	print "<input type=\"hidden\" name=\"crossged\" value=\"";
	if (SystemConfig::$ALLOW_CHANGE_GEDCOM && $config["search_all_geds"] == "yes") print "yes";
	else print "no";
	print "\" />";
	print "<table class=\"blockcontent $TEXT_DIRECTION\">";
	print "<tr><td colspan=\"2\"><b>".GM_LANG_soundex_search."</b><br /></td>";
	print "<td rowspan=\"8\" width=\"100\">&nbsp;</td>";
	print "<td><b>".GM_LANG_qs_jump."</b></td>";
	print "</tr><tr>";
	print "<td>".GM_LANG_lastname_search."</td>";
	print "<td><input type=\"text\" name=\"lastname\" tabindex=\"1\" /></td>";
	print "<td rowspan=\"8\">";
	print "<a href=\"indilist.php";
	if (SystemConfig::$ALLOW_CHANGE_GEDCOM && $config["search_all_geds"] == "yes") print "?allgeds=yes";
	print "\">".GM_LANG_qs_findindi."</a><br />";
	print "<a href=\"famlist.php";
	if (SystemConfig::$ALLOW_CHANGE_GEDCOM && $config["search_all_geds"] == "yes") print "?allgeds=yes";
	print "\">".GM_LANG_qs_findfam."</a><br />";
	print "<a href=\"calendar.php\">".GM_LANG_qs_calendar."</a><br />";
	if ($gm_user->username == "" && count($GEDCOMS) > 0) {
		print "<a href=\"login.php\">".GM_LANG_qs_login."</a><br />";
		print "<a href=\"login_register.php?action=register\">".GM_LANG_requestaccount."</a><br />";
	}
	else {
		if (!empty($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) print "<a href=\"pedigree.php?rootid=".$gm_user->gedcomid[GedcomConfig::$GEDCOMID]."\">".GM_LANG_my_pedigree."</a><br />";
	}
	print "<br /><br />";
	PrintHelpLink("QS_search_help", "qm", "QS_search_tips");
	print "<b><a href=\"javascript: ".GM_LANG_QS_search_tips."\" onclick=\"helpPopup('QS_search_help'); return false;\">".GM_LANG_QS_search_click."</a></b> \n";
	print "</td></tr><tr>";
	print "<td>".GM_LANG_firstname_search."</td>";
	print "<td><input type=\"text\" name=\"firstname\" tabindex=\"2\" /></td>";
	print "</tr><tr>";
	print "<td>".GM_LANG_search_place."</td>";
	print "<td><input type=\"text\" name=\"place\" tabindex=\"3\" /></td>";
	print "</tr><tr>";
	print "<td colspan=\"2\"><b>".GM_LANG_or."</b></td>";
	print "</tr><tr>";
	print "<td colspan=\"2\"><b>".GM_LANG_search_general."</b></td>";
	print "</tr><tr>";
	print "<td>".GM_LANG_enter_terms."</td>";
	print "<td><input tabindex=\"4\" type=\"text\" name=\"query\" value=\"\" /></td>";
	print "</tr><tr>";
	print "<td colspan=\"2\" class=\"center\"><input  type=\"submit\" id=\"submit\" value=\"".GM_LANG_search."\" />&nbsp;";
	print "</td></tr>";
	print "</table></form>\n";
	print "</div>";
	print "<script language=\"JavaScript\" type=\"text/javascript\"><!--\n";
	print "document.searchform.lastname.focus();\n//-->\n";
	print "</script>";

}
function print_quickstart_block_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_quickstart_block"]["config"];

  	print "<tr><td width=\"20%\" class=\"shade2\">".GM_LANG_qs_search_all."</td>";?>
	<td class="shade1">
	<select name="search_all_geds">
		<option value="no"<?php if ($config["search_all_geds"]=="no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["search_all_geds"]=="yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>
	<?php
}
?>