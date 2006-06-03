<?php
/**
 * Gedcom Favorites Block
 *
 * This block prints the active gedcom favorites
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
 * @version $Id: gedcom_favorites.php,v 1.5 2006/05/13 07:53:45 roland-d Exp $
 */

$GM_BLOCKS["print_gedcom_favorites"]["name"]        = $gm_lang["gedcom_favorites_block"];
$GM_BLOCKS["print_gedcom_favorites"]["descr"]        = "gedcom_favorites_descr";
$GM_BLOCKS["print_gedcom_favorites"]["canconfig"]   = false;

//-- print gedcom favorites
function print_gedcom_favorites($block = true, $config="", $side, $index) {
		global $gm_lang, $factarray, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $command, $sourcelist, $TEXT_DIRECTION, $gm_username;

		$userfavs = getUserFavorites($GEDCOM);
		if (!is_array($userfavs)) $userfavs = array();
		print "<div id=\"gedcom_favorites\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("index_favorites_help", "qm");
		print $gm_lang["gedcom_favorites"]." &lrm;(".count($userfavs).")&lrm;";
		print "</div>";
		print "<div class=\"blockcontent\">";
		if ($block) print "<div class=\"small_inner_block\">\n";
		if (count($userfavs)==0) {
				if (userGedcomAdmin($gm_username)) print_text("no_favorites");
				else print_text("no_gedcom_favorites");
		}
		else {
			print "<table width=\"99%\" class=\"$TEXT_DIRECTION wrap\">";
			if ($block) $style = 1;
			else $style = 2;
			foreach($userfavs as $key=>$favorite) {
				if (isset($favorite["id"])) $key=$favorite["id"];
				if ($favorite["type"]=="URL") {
					print "<tr><td>";
					print "<div id=\"boxurl".$key.".0\" class=\"person_box wrap";
					print "\"><ul>\n";
					print "<li><a href=\"".$favorite["url"]."\">".PrintReady($favorite["title"])."</a></li>";
					print "</ul>";
					print PrintReady($favorite["note"]);
					print "</div>\n";
				}
				else {
				$indirec = find_person_record($favorite["gid"]);
				if (displayDetailsbyId($favorite["gid"])) {
					if ($favorite["type"]=="INDI") {
						print "<tr><td>";
						print "<div id=\"box".$favorite["gid"].".0\" class=\"person_box wrap";
						if (preg_match("/1 SEX F/", $indirec)>0) print "F";
						else if (preg_match("/1 SEX M/", $indirec)>0) print "";
						else print "NN";
						print "\">\n";
						print_pedigree_person($favorite["gid"], $style, 1, $key);
						print PrintReady($favorite["note"]);
						print "</div>\n";
					}
					if ($favorite["type"]=="FAM") {
						print "<tr><td>";
						print "<div id=\"box".$favorite["gid"].".0\" class=\"person_box wrap";
						print "\"><ul>\n";
						print_list_family($favorite["gid"], array(get_family_descriptor($favorite["gid"]), $favorite["file"]));
						print "</ul>";
						print PrintReady($favorite["note"]);
						print "</div>\n";
					}
					if ($favorite["type"]=="SOUR") {
						print "<tr><td>";
						print "<div id=\"box".$favorite["gid"].".0\" class=\"person_box wrap";
						print "\"><ul>\n";
						print_list_source($favorite["gid"], $sourcelist[$favorite["gid"]]);
						print "</ul>";
						print PrintReady($favorite["note"]);
						print "</div>\n";
					}
				}
				}
				if ($command=="user" || userGedcomAdmin($gm_username)) print "<a class=\"font9\" href=\"index.php?command=$command&amp;action=deletefav&amp;fv_id=".$key."\" onclick=\"return confirm('".$gm_lang["confirm_fav_remove"]."');\">".$gm_lang["remove"]."</a><br />\n";
					print "</td></tr>\n";
			}
			print "</table>\n";
		}
		if (userGedcomAdmin($gm_username)) { ?>
			<script language="JavaScript" type="text/javascript">
			<!--
			var pastefield;
			function paste_id(value) {
				pastefield.value=value;
			}
			//-->
			</script>
			<br />
			<?php
			print_help_link("index_add_favorites_help", "qm");
			print "<b><a href=\"javascript: ".$gm_lang["add_favorite"]." \" onclick=\"expand_layer('add_ged_fav'); return false;\"><img id=\"add_ged_fav_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" />&nbsp;".$gm_lang["add_favorite"]."</a></b>";
			print "<br /><div id=\"add_ged_fav\" style=\"display: none;\">\n";
			print "<form name=\"addgfavform\" method=\"get\" action=\"index.php\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"addfav\" />\n";
			print "<input type=\"hidden\" name=\"command\" value=\"$command\" />\n";
			print "<input type=\"hidden\" name=\"favtype\" value=\"gedcom\" />\n";
			print "<table border=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td>".$gm_lang["add_fav_enter_id"]." <br />";
			print "<input class=\"pedigree_form\" type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
			print_findindi_link("gid","");
			print_findfamily_link("gid");
			print_findsource_link("gid");
			print "\n<br />".$gm_lang["add_fav_or_enter_url"];
			print "\n<br />".$gm_lang["url"]."<input type=\"text\" name=\"url\" size=\"40\" value=\"\" />";
			print "\n<br />".$gm_lang["title"]." <input type=\"text\" name=\"favtitle\" size=\"40\" value=\"\" />";
			print "\n</td><td>";
			print "\n".$gm_lang["add_fav_enter_note"];
			print "\n<br /><textarea name=\"favnote\" rows=\"6\" cols=\"40\"></textarea>";
			print "</td></tr></table>\n";
			print "\n<br /><input type=\"submit\" value=\"".$gm_lang["add"]."\" style=\"font-size: 8pt; \" />";
			print "\n</form></div>\n";
		}
		if ($block) print "</div>\n";
		print "</div>"; // blockcontent
		print "</div>"; // block
}
?>
