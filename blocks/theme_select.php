<?php
/**
 * Theme Select Block
 *
 * This block will print a form that allows the visitor to change the theme
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

$GM_BLOCKS["print_block_theme_select"]["name"]      = GM_LANG_theme_select_block;
$GM_BLOCKS["print_block_theme_select"]["descr"]     = "theme_select_descr";
$GM_BLOCKS["print_block_theme_select"]["type"]      = "gedcom";
$GM_BLOCKS["print_block_theme_select"]["canconfig"]	= false;
$GM_BLOCKS["print_block_theme_select"]["rss"]       = false;

function print_block_theme_select($style=0, $config="", $side, $index) {
	global $ALLOW_USER_THEMES, $gm_user, $themeformcount;
	
	if (GedcomConfig::$ALLOW_THEME_DROPDOWN && $ALLOW_USER_THEMES) {
		print "<div id=\"theme_select\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("change_theme", "qm", "change_theme");
		print GM_LANG_change_theme;
		print "</div>";
		print "<div class=\"blockcontent center\">";
	
		if (!isset($themeformcount)) $themeformcount = 0;
		$themeformcount++;
		isset($_SERVER["QUERY_STRING"]) == true?$tqstring = "?".$_SERVER["QUERY_STRING"]:$tqstring = "";
		$frompage = $_SERVER["SCRIPT_NAME"].$tqstring;
		$themes = GetThemeNames();
		print "<form action=\"themechange.php\" name=\"themeform$themeformcount\" method=\"post\">";
		print "<input type=\"hidden\" name=\"frompage\" value=\"".urlencode($frompage)."\" />";
		print "<select name=\"mytheme\" class=\"header_select\" onchange=\"document.themeform$themeformcount.submit();\">";
		print "<option value=\"\">".GM_LANG_change_theme."</option>\n";
		foreach($themes as $indexval => $themedir) {
				print "<option value=\"".$themedir["dir"]."\"";
				if ($gm_user->username != "") {
						if ($themedir["dir"] == $gm_user->theme) print " class=\"selected-option\"";
				}
				else {
						 if ($themedir["dir"] == GedcomConfig::$THEME_DIR) print " class=\"selected-option\"";
				}
				print ">".$themedir["name"]."</option>\n";
		}
		print "</select></form>";
		print "</div></div>";
	}
}
?>
