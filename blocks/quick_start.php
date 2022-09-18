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
 * @version $Id: quick_start.php,v 1.18 2009/02/07 08:45:14 sjouke Exp $
 */

$GM_BLOCKS["print_quickstart_block"]["name"]        = $gm_lang["quickstart_block"];
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
	global $gm_lang, $GEDCOM, $GEDCOMS, $command, $SCRIPT_NAME, $QUERY_STRING, $USE_REGISTRATION_MODULE, $LOGIN_URL, $ALLOW_REMEMBER_ME, $gm_username, $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $user, $ALLOW_CHANGE_GEDCOM, $GM_BLOCKS, $Users;
	if (empty($config)) $config = $GM_BLOCKS["print_quickstart_block"]["config"];
	if (!isset($config['search_all_geds'])) $config = $GM_BLOCKS["print_quickstart_block"]["config"];

	print "<div id=\"quickstart_block\" class=\"block $TEXT_DIRECTION\">\n";
	print "<div class=\"blockhc\">";
	print_help_link("index_quickstart_help", "qm", "quickstart");
	if ($GM_BLOCKS["print_quickstart_block"]["canconfig"]) {
		$username = $gm_username;
		if ((($command=="gedcom")&&($Users->userGedcomAdmin($username))) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", $GEDCOM);
			else $name = $username;
			print "<a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=350,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
		}
	}
	print $gm_lang["quickstart"];
	print "</div>";
	print "<form method=\"post\" action=\"search.php\" name=\"searchform\">";
	print "<input type=\"hidden\" name=\"action\" value=\"quickstart\" />";
	print "<input type=\"hidden\" name=\"crossged\" value=\"";
	if ($ALLOW_CHANGE_GEDCOM && $config["search_all_geds"] == "yes") print "yes";
	else print "no";
	print "\" />";
	print "<table class=\"blockcontent $TEXT_DIRECTION\">";
	print "<tr><td colspan=\"2\"><b>".$gm_lang["soundex_search"]."</b><br /></td>";
	print "<td rowspan=\"8\" width=\"100\">&nbsp;</td>";
	print "<td><b>".$gm_lang["qs_jump"]."</b></td>";
	print "</tr><tr>";
	print "<td>".$gm_lang["lastname_search"]."</td>";
	print "<td><input type=\"text\" name=\"lastname\" tabindex=\"1\" /></td>";
	print "<td rowspan=\"8\">";
	print "<a href=\"indilist.php";
	if ($ALLOW_CHANGE_GEDCOM && $config["search_all_geds"] == "yes") print "?allgeds=yes";
	print "\">".$gm_lang["qs_findindi"]."</a><br />";
	print "<a href=\"famlist.php";
	if ($ALLOW_CHANGE_GEDCOM && $config["search_all_geds"] == "yes") print "?allgeds=yes";
	print "\">".$gm_lang["qs_findfam"]."</a><br />";
	print "<a href=\"calendar.php\">".$gm_lang["qs_calendar"]."</a><br />";
	if (empty($gm_username)) {
		print "<a href=\"login.php\">".$gm_lang["qs_login"]."</a><br />";
		print "<a href=\"login_register.php?action=register\">".$gm_lang["requestaccount"]."</a><br />";
	}
	else {
		if (!empty($user->gedcomid[$GEDCOM])) print "<a href=\"pedigree.php?rootid=".$user->gedcomid[$GEDCOM]."\">".$gm_lang["my_pedigree"]."</a><br />";
	}
	print "<br /><br />";
	print_help_link("QS_search_help", "qm", "QS_search_tips");
	print "<b><a href=\"javascript: ".$gm_lang["QS_search_tips"]."\" onclick=\"helpPopup('QS_search_help'); return false;\">".$gm_lang["QS_search_click"]."</a></b> \n";
	print "</td></tr><tr>";
	print "<td>".$gm_lang["firstname_search"]."</td>";
	print "<td><input type=\"text\" name=\"firstname\" tabindex=\"2\" /></td>";
	print "</tr><tr>";
	print "<td>".$gm_lang["search_place"]."</td>";
	print "<td><input type=\"text\" name=\"place\" tabindex=\"3\" /></td>";
	print "</tr><tr>";
	print "<td colspan=\"2\"><b>".$gm_lang["or"]."</b></td>";
	print "</tr><tr>";
	print "<td colspan=\"2\"><b>".$gm_lang["search_general"]."</b></td>";
	print "</tr><tr>";
	print "<td>".$gm_lang["enter_terms"]."</td>";
	print "<td><input tabindex=\"4\" type=\"text\" name=\"query\" value=\"\" /></td>";
	print "</tr><tr>";
	print "<td colspan=\"2\" class=\"center\"><input  type=\"submit\" id=\"submit\" value=\"".$gm_lang["search"]."\" />&nbsp;";
	print "</td></tr>";
	print "</table></form>\n";
	print "</div>";
	print "<script language=\"JavaScript\" type=\"text/javascript\">";
	print "document.searchform.lastname.focus()";
	print "</script>";

}
function print_quickstart_block_config($config) {
	global $gm_lang, $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_quickstart_block"]["config"];

  	print "<tr><td width=\"20%\" class=\"shade2\">".$gm_lang["qs_search_all"]."</td>";?>
	<td class="shade1">
	<select name="search_all_geds">
		<option value="no"<?php if ($config["search_all_geds"]=="no") print " selected=\"selected\"";?>><?php print $gm_lang["no"]; ?></option>
		<option value="yes"<?php if ($config["search_all_geds"]=="yes") print " selected=\"selected\"";?>><?php print $gm_lang["yes"]; ?></option>
	</select>
	</td></tr>
	<?php
}
?>