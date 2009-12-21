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
 
$GM_BLOCKS["print_gedcom_block"]["name"]        = GM_LANG_gedcom_block;
$GM_BLOCKS["print_gedcom_block"]["descr"]       = "gedcom_descr";
$GM_BLOCKS["print_gedcom_block"]["type"]        = "gedcom";
$GM_BLOCKS["print_gedcom_block"]["canconfig"]   = false;
$GM_BLOCKS["print_gedcom_block"]["rss"]			= false;

//-- function to print the gedcom block
function print_gedcom_block($block = true, $config="", $side, $index) {
	global $hits, $day, $month, $year, $GEDCOMID, $GEDCOMS, $TIME_FORMAT, $command,$TEXT_DIRECTION, $gm_user;


	print "<div id=\"gedcom_welcome\" class=\"block\" >\n";
	print "<div class=\"blockhc\">";
	print PrintReady($GEDCOMS[$GEDCOMID]["title"]);	 
	print "</div>";
	print "<div class=\"blockcontent center\">";
	print "<br />".GetChangedDate("$day $month $year")." - ".date($TIME_FORMAT, time()-$_SESSION["timediff"])."<br />\n";
	if(GedcomConfig::$SHOW_COUNTER)
			print GM_LANG_hit_count."  ".$hits."<br />\n";
	print "\n<br />";
	print "<a href=\"javascript: ".GM_LANG_add_site_to_favs."\" onclick='window.external.AddFavorite(location.href, document.title); return false;'>".GM_LANG_add_site_to_favs."</a><br />";
	if ($gm_user->userGedcomAdmin()) {
		print "<a href=\"javascript: ".GM_LANG_customize_gedcom_page."\" onclick=\"window.open('index_edit.php?name=".preg_replace("/'/", "\'", get_gedcom_from_id($GEDCOMID))."&amp;command=gedcom', '', 'top=50,left=10,width=1000,height=400,scrollbars=1,resizable=1'); return false;\">".GM_LANG_customize_gedcom_page."</a>\n";
	}
	print "</div>\n";
	print "</div>";
}
?>
