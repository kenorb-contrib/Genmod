<?php
/**
 * User Welcome Block
 *
 * This block will print basic information and links for the user.
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

$GM_BLOCKS["print_welcome_block"]["name"]       = GM_LANG_welcome_block;
$GM_BLOCKS["print_welcome_block"]["descr"]      = "welcome_descr";
$GM_BLOCKS["print_welcome_block"]["type"]       = "user";
$GM_BLOCKS["print_welcome_block"]["canconfig"]	= false;
$GM_BLOCKS["print_welcome_block"]["rss"]       	= false;

//-- function to print the welcome block
function print_welcome_block($block=true, $config="", $side, $index) {
		global $day, $month, $year, $GM_IMAGES, $GEDCOMID, $TIME_FORMAT, $gm_user;

		print "<div id=\"user_welcome\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print GM_LANG_welcome." ".$gm_user->firstname." ".$gm_user->lastname;
		print "</div>";
		print "<div class=\"blockcontent center\">";
		if ($gm_user->editaccount) {
			print "<div><a href=\"edituser.php\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["mygedview"]["small"]."\" border=\"0\" alt=\"".GM_LANG_myuserdata."\" title=\"".GM_LANG_myuserdata."\" /><br />".GM_LANG_myuserdata."</a></div>";
		}
		if (!empty($gm_user->gedcomid[$GEDCOMID])) {
			print "<div><a href=\"pedigree.php?rootid=".$gm_user->gedcomid[$GEDCOMID]."\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["pedigree"]["small"]."\" border=\"0\" alt=\"".GM_LANG_my_pedigree."\" title=\"".GM_LANG_my_pedigree."\" /><br />".GM_LANG_my_pedigree."</a></div>";
			print "<div><a href=\"individual.php?pid=".$gm_user->gedcomid[$GEDCOMID]."\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"".GM_LANG_my_indi."\" title=\"".GM_LANG_my_indi."\" /><br />".GM_LANG_my_indi."</a></div>\n";
		}
		print "<div>";
		PrintHelpLink("mygedview_customize_help", "qm", "customize_page");
		print "<a href=\"#\" onclick=\"window.open('index_edit.php?name=".$gm_user->username."&amp;command=user', '', 'top=50,left=10,width=1000,height=400,scrollbars=1,resizable=1');\">".GM_LANG_customize_page."</a></div>\n";
		print "\n<div>".GetChangedDate("$day $month $year")." - ".date($TIME_FORMAT, time()-$_SESSION["timediff"])."</div>\n";
		print "</div>"; // blockcontent
		print "</div>"; // block

}
?>
