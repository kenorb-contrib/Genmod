<?php
/**
 * Gedcom Welcome Block
 *
 * This block prints basic information about the active gedcom
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
 
$GM_BLOCKS["print_gedcom_block"]["name"]        = $gm_lang["gedcom_block"];
$GM_BLOCKS["print_gedcom_block"]["descr"]       = "gedcom_descr";
$GM_BLOCKS["print_gedcom_block"]["type"]        = "gedcom";
$GM_BLOCKS["print_gedcom_block"]["canconfig"]   = false;
$GM_BLOCKS["print_gedcom_block"]["rss"]			= false;

//-- function to print the gedcom block
function print_gedcom_block($block = true, $config="", $side, $index) {
	global $hits,$gm_lang, $day, $month, $year, $GEDCOM, $GEDCOMS, $ALLOW_CHANGE_GEDCOM, $TIME_FORMAT,$SHOW_COUNTER, $command,$THEME_DIR,$TEXT_DIRECTION, $gm_username, $gm_user;


	print "<div id=\"gedcom_welcome\" class=\"block\" >\n";
	print "<div class=\"blockhc\">";
	print PrintReady($GEDCOMS[$GEDCOM]["title"]);	 
	print "</div>";
	print "<div class=\"blockcontent center\">";
	print "<br />".GetChangedDate("$day $month $year")." - ".date($TIME_FORMAT, time()-$_SESSION["timediff"])."<br />\n";
	if($SHOW_COUNTER)
			print $gm_lang["hit_count"]."  ".$hits."<br />\n";
	print "\n<br />";
	print "<a href=\"javascript: ".$gm_lang["add_site_to_favs"]."\" onclick='window.external.AddFavorite(location.href, document.title); return false;'>".$gm_lang["add_site_to_favs"]."</a><br />";
	if ($gm_user->userGedcomAdmin()) {
		print "<a href=\"javascript: ".$gm_lang["customize_gedcom_page"]."\" onclick=\"window.open('index_edit.php?name=".preg_replace("/'/", "\'", $GEDCOM)."&amp;command=gedcom', '', 'top=50,left=10,width=1000,height=400,scrollbars=1,resizable=1'); return false;\">".$gm_lang["customize_gedcom_page"]."</a>\n";
	}
	print "</div>\n";
	print "</div>";
}
?>
