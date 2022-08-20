<?php
/**
 * Gedcom Favorites Block
 *
 * This block prints the active gedcom favorites
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
 * @package Genmod
 * @subpackage Blocks
 * @version $Id: gedcom_favorites.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

$GM_BLOCKS["print_gedcom_favorites"]["name"]        = GM_LANG_gedcom_favorites_block;
$GM_BLOCKS["print_gedcom_favorites"]["descr"]       = "gedcom_favorites_descr";
$GM_BLOCKS["print_gedcom_favorites"]["canconfig"]   = true;
$GM_BLOCKS["print_gedcom_favorites"]["rss"]				= false;

//-- print gedcom favorites
function print_gedcom_favorites($block = true, $config="", $side, $index) {
	global $GM_IMAGES, $command, $sourcelist, $TEXT_DIRECTION;
	global $gm_user;
	
	print "<!-- Start Gedcom Favorites Block //-->";
	$userfavs = FavoritesController::getGedcomFavorites(GedcomConfig::$GEDCOMID);
	if (!is_array($userfavs)) $userfavs = array();
	print "<div id=\"gedcom_favorites\" class=\"BlockContainer\">\n";
		print "<div class=\"BlockHeader\">";
			PrintHelpLink("index_favorites_help", "qm", "gedcom_favorites");
			print "<div class=\"BlockHeaderText\">".GM_LANG_gedcom_favorites." &lrm;(".count($userfavs).")&lrm;</div>";
		print "</div>";
		print "<div class=\"BlockContent\">";
			if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
			else print "<div class=\"RestrictedBlockHeightMain\">\n";
				if (count($userfavs)==0) {
					if ($gm_user->userGedcomAdmin()) PrintText("no_favorites");
					else PrintText("no_gedcom_favorites");
				}
				else {
					BlockFunctions::PrintBlockFavorites($userfavs, $side, $index);
				}
				if ($gm_user->userGedcomAdmin()) { 
					BlockFunctions::PrintBlockAddFavorite($command, "gedcom", $side);
				}
			print "</div>\n";
		print "</div>"; // blockcontent
	print "</div>"; // block
	print "<!-- End Gedcom Favorites Block //-->";
}
function print_gedcom_favorites_config($favid="") {
	
	if ($favid == "" && isset($_GET["favid"])) $favid = $_GET["favid"];
	
	$userfave = FavoritesController::getGedcomFavorites(GedcomConfig::$GEDCOMID, $favid);
	$fav = $userfave[0];
	
	print "<tr><td colspan=\"2\" class=\"NavBlockLabel\">";
	print $fav->title;
	print "<input type=\"hidden\" name=\"action\" value=\"storefav\" />\n";
	print "<input type=\"hidden\" name=\"id\" value=\"".$fav->id."\" />\n";
	print "<input type=\"hidden\" name=\"gid\" value=\"".$fav->gid."\" />\n";
	print "<input type=\"hidden\" name=\"username\" value=\"\" />\n";
	print "<input type=\"hidden\" name=\"type\" value=\"".$fav->type."\" />\n";
	print "<input type=\"hidden\" name=\"file\" value=\"".$fav->file."\" />\n";
	print "</td></tr>";
	if ($fav->type == "URL") {
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_url."</td>";
		print "<td class=\"NavBlockField\"><input type=\"text\" id=\"favurl\" name=\"favurl\" size=\"40\" value=\"".$fav->url."\"></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_title."</td>";
		print "<td class=\"NavBlockField\"><input type=\"text\" id=\"title\" name=\"favtitle\" size=\"40\" value=\"".$fav->title."\"></td></tr>";
	}
	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_add_fav_enter_note."</td>";
	print "<td class=\"NavBlockField\"><textarea id=\"favnote\" name=\"favnote\" rows=\"6\" cols=\"40\">".$fav->note."</textarea></td></tr>";
}
?>