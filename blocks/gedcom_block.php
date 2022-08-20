<?php
/**
 * Gedcom Welcome Block
 *
 * This block prints basic information about the active gedcom
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
 * @version $Id: gedcom_block.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
$GM_BLOCKS["print_gedcom_block"]["name"]        = GM_LANG_gedcom_block;
$GM_BLOCKS["print_gedcom_block"]["descr"]       = "gedcom_descr";
$GM_BLOCKS["print_gedcom_block"]["type"]        = "gedcom";
$GM_BLOCKS["print_gedcom_block"]["canconfig"]   = false;
$GM_BLOCKS["print_gedcom_block"]["rss"]			= false;

//-- function to print the gedcom block
function print_gedcom_block($block = true, $config="", $side, $index) {
	global $hits, $GEDCOMS, $TIME_FORMAT, $command,$TEXT_DIRECTION, $gm_user;

	print "<!-- Start gedcom Block //-->";
	print "<div id=\"gedcom_welcome\" class=\"BlockContainer\" >\n";
		print "<div class=\"BlockHeader\">";
			print "<div class=\"BlockHeaderText\">".PrintReady($GEDCOMS[GedcomConfig::$GEDCOMID]["title"])."</div>";
		print "</div>";
		print "<div class=\"BlockContent\">";
			print "<div class=\"GedcomBlockTime\">".GetChangedDate(GetCurrentDay()." ".GetCurrentMonth()." ".GetCurrentYear())." - ".date($TIME_FORMAT, time()-$_SESSION["timediff"])."</div>\n";
			if(GedcomConfig::$SHOW_COUNTER)
					print "<div class=\"PageCounter GedcomBlockCounter\">".GM_LANG_hit_count."  ".$hits."</div>\n";
			print "<div id=\"GedcomBlockFavLink\"><a href=\"javascript: ".GM_LANG_add_site_to_favs."\" onclick='window.external.AddFavorite(location.href, document.title); return false;'>".GM_LANG_add_site_to_favs."</a></div>";
			if ($gm_user->userGedcomAdmin()) {
				print "<div class=\"GedcomBlockCustLink\"><a href=\"javascript: ".GM_LANG_customize_gedcom_page."\" onclick=\"window.open('index_edit.php?name=".preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID))."&amp;command=gedcom', '', 'top=50,left=10,width=1000,height=400,scrollbars=1,resizable=1'); return false;\">".GM_LANG_customize_gedcom_page."</a></div>\n";
			}
		print "</div>\n";
	print "</div>";
	print "<!-- End gedcom Block //-->";
}
?>
