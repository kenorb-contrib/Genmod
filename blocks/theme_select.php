<?php
/**
 * Theme Select Block
 *
 * This block will print a form that allows the visitor to change the theme
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
 * @package Genmod
 * @subpackage Blocks
 * @version $Id: theme_select.php,v 1.3 2006/04/17 20:01:52 roland-d Exp $
 */

$GM_BLOCKS["print_block_theme_select"]["name"]         = $gm_lang["theme_select_block"];
$GM_BLOCKS["print_block_theme_select"]["descr"]        = "theme_select_descr";
$GM_BLOCKS["print_block_theme_select"]["type"]        = "gedcom";
$GM_BLOCKS["print_block_theme_select"]["canconfig"]   = false;

function print_block_theme_select($style=0, $config="", $side, $index) {
	global $gm_lang;
	print "<div id=\"theme_select\" class=\"block\">\n";
	print "<div class=\"blockhc\">";
	print_help_link("change_theme", "qm", "change_theme");
	print $gm_lang["change_theme"];
	print "</div>";
	print "<div class=\"blockcontent center\">";
	global $ALLOW_THEME_DROPDOWN, $ALLOW_USER_THEMES, $THEME_DIR, $gm_lang, $themeformcount;

	if (!isset($themeformcount)) $themeformcount = 0;
	$themeformcount++;
	$uname = getUserName();
	$user = getUser($uname);
	isset($_SERVER["QUERY_STRING"]) == true?$tqstring = "?".$_SERVER["QUERY_STRING"]:$tqstring = "";
	$frompage = $_SERVER["SCRIPT_NAME"].$tqstring;
	$themes = get_theme_names();
	$style=0;
	switch ($style) {
			case 0:
			print "<form action=\"themechange.php\" name=\"themeform$themeformcount\" method=\"post\">";
			print "<input type=\"hidden\" name=\"frompage\" value=\"".urlencode($frompage)."\" />";
			print "<select name=\"mytheme\" class=\"header_select\" onchange=\"document.themeform$themeformcount.submit();\">";
			print "<option value=\"\">".$gm_lang["change_theme"]."</option>\n";
			foreach($themes as $indexval => $themedir) {
					print "<option value=\"".$themedir["dir"]."\"";
					if ($uname) {
							if ($themedir["dir"] == $user["theme"]) print " class=\"selected-option\"";
					}
					else {
							 if ($themedir["dir"] == $THEME_DIR) print " class=\"selected-option\"";
					}
					print ">".$themedir["name"]."</option>\n";
			}
			print "</select></form>";
			break;
			case 1:
					$menu = array();
					$menu["label"] = $gm_lang["change_theme"];
					$menu["labelpos"] = "left";
					$menu["link"] = "#";
					$menu["class"] = "thememenuitem";
					$menu["hoverclass"] = "thememenuitem_hover";
					$menu["flyout"] = "down";
					$menu["submenuclass"] = "favsubmenu";
					$menu["items"] = array();
					foreach($themes as $indexval => $themedir) {
							$submenu = array();
							$submenu["label"] = $themedir["name"];
							$submenu["labelpos"] = "right";
							$submenu["link"] = "themechange.php?frompage=".urlencode($frompage)."&amp;mytheme=".$themedir["dir"];
							$submenu["class"] = "favsubmenuitem";
							$submenu["hoverclass"] = "favsubmenuitem_hover";
							$menu["items"][] = $submenu;
					}
					print_menu($menu);
			break;
	}
	print "</div></div>";
}
?>
