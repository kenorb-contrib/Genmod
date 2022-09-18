<?php
/**
 * Gedcom Favorites Block
 *
 * This block prints the active gedcom favorites
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
 * @version $Id: gedcom_favorites.php,v 1.27 2009/03/29 13:20:10 sjouke Exp $
 */

$GM_BLOCKS["print_gedcom_favorites"]["name"]        = $gm_lang["gedcom_favorites_block"];
$GM_BLOCKS["print_gedcom_favorites"]["descr"]       = "gedcom_favorites_descr";
$GM_BLOCKS["print_gedcom_favorites"]["canconfig"]   = true;
$GM_BLOCKS["print_gedcom_favorites"]["rss"]				= false;

//-- print gedcom favorites
function print_gedcom_favorites($block = true, $config="", $side, $index) {
	global $gm_lang, $factarray, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $command, $sourcelist, $TEXT_DIRECTION, $gm_username, $GEDCOMID, $Users;
	
	$userfavs = getUserFavorites($GEDCOM);
	if (!is_array($userfavs)) $userfavs = array();
	print "<div id=\"gedcom_favorites\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
			print_help_link("index_favorites_help", "qm", "gedcom_favorites");
			print $gm_lang["gedcom_favorites"]." &lrm;(".count($userfavs).")&lrm;";
		print "</div>";
		print "<div class=\"blockcontent\">";
			if ($block) print "<div class=\"small_inner_block\">\n";
			if (count($userfavs)==0) {
					if ($Users->userGedcomAdmin($gm_username)) print_text("no_favorites");
					else print_text("no_gedcom_favorites");
			}
			else {
				if ($block) $style = 1;
				else $style = 2;
				foreach($userfavs as $key=>$favorite) {
					if (isset($favorite["id"])) $key=$favorite["id"];
					if ($favorite["type"]=="URL") {
						print "<div id=\"boxurl".$key.".0\" class=\"person_box";
							print "\"><ul>\n";
							print "<li><a href=\"".$favorite["url"]."\" target=\"_blank\">".PrintReady($favorite["title"])."</a></li>";
							print "</ul>";
							print "<span class=\"favorite_padding\">".PrintReady($favorite["note"])."</span>";
						print "</div>\n";
					}
					else {
						$indirec = FindPersonRecord($favorite["gid"]);
						if ($favorite["type"]=="INDI") {
							if (displayDetailsbyId($favorite["gid"], "INDI")) {
								print "<div id=\"box".$favorite["gid"].".0\" class=\"person_box";
									if (preg_match("/1 SEX F/", $indirec)>0) print "F";
									else if (preg_match("/1 SEX M/", $indirec)>0) print "";
									else print "NN";
									print "\">\n";
									print_pedigree_person($favorite["gid"], $style, 1, $key);
								print "</div>\n";
								print "<span class=\"favorite_padding\">".PrintReady($favorite["note"])."</span>";
							}
						}
						if ($favorite["type"]=="FAM") {
							if (displayDetailsbyId($favorite["gid"], "FAM")) {
								print "<div id=\"box".$favorite["gid"].".0\" class=\"person_box";
								print "\"><ul>\n";
									print_list_family($favorite["gid"], array(GetFamilyDescriptor($favorite["gid"]), $favorite["file"]));
									print "</ul>";
									print "<span class=\"favorite_padding\">".PrintReady($favorite["note"])."</span>";
								print "</div>\n";
							}
						}
						if ($favorite["type"]=="SOUR") {
							if (displayDetailsbyId($favorite["gid"], "SOUR", 1, true)) {
								print "<div id=\"box".$favorite["gid"].".0\" class=\"person_box";
									print "\"><ul>\n";
									print_list_source($favorite["gid"], $sourcelist[$favorite["gid"]]);
									print "</ul>";
									print "<span class=\"favorite_padding\">".PrintReady($favorite["note"])."</span>";
								print "</div>\n";
							}
						}
						if ($favorite["type"]=="OBJE") {
							if (displayDetailsbyId($favorite["gid"], "OBJE", 1, true)) {
	//							print "<tr><td>";
								print "<div id=\"box".$favorite["gid"].".0\" class=\"person_box";
								print "\"><ul>\n";
								print_media_links("0 OBJE @".$favorite["gid"]."@", 0);
								print "</ul>";
								print "<span class=\"favorite_padding\">".PrintReady($favorite["note"])."</span>";
								print "</div>\n";
							}
						}
					}
					// NOTE: Print the links to either remove or edit a favorite
					if ($command=="user" || $Users->userGedcomAdmin($gm_username)) {
						if (!empty($favorite["note"])) print "&nbsp;&nbsp;";
						print "<a class=\"font9\" href=\"index.php?command=$command&amp;action=deletefav&amp;fv_id=".$key."\" onclick=\"return confirm('".$gm_lang["confirm_fav_remove"]."');\">".$gm_lang["remove"]."</a>\n";
						print "&nbsp;";
						print "<a class=\"font9\" href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?favid=$key&amp;name=$gm_username&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=400,scrollbars=1,resizable=1'); return false;\">".$gm_lang["edit"]."</a>";
					}
				}
			}
			if ($Users->userGedcomAdmin($gm_username)) { ?>
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
				print_help_link("index_add_favorites_help", "qm", "add_favorite");
				print "<b><a href=\"javascript: ".$gm_lang["add_favorite"]." \" onclick=\"expand_layer('add_ged_fav'); return false;\"><img id=\"add_ged_fav_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" />&nbsp;".$gm_lang["add_favorite"]."</a></b>";
				print "<br /><div id=\"add_ged_fav\" style=\"display: none;\">\n";
				print "<form name=\"addgfavform\" method=\"get\" action=\"index.php\">\n";
				print "<input type=\"hidden\" name=\"action\" value=\"addfav\" />\n";
				print "<input type=\"hidden\" name=\"command\" value=\"$command\" />\n";
				print "<input type=\"hidden\" name=\"favtype\" value=\"gedcom\" />\n";
				print "<table border=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td>".$gm_lang["add_fav_enter_id"]." <br />";
				print "<input class=\"pedigree_form\" type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
				PrintFindIndiLink("gid",$GEDCOMID);
				print_findfamily_link("gid");
				print_findsource_link("gid");
				print_findobject_link("gid");
				print_findnote_link("gid");
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
function print_gedcom_favorites_config($favid="") {
	global $GEDCOM, $gm_lang;
	
	if ($favid == "" && isset($_GET["favid"])) $favid = $_GET["favid"];
	
	$userfave = getUserFavorites($GEDCOM, $favid);
	
	print "<br />";
	if ($userfave[0]["type"] == "INDI") print GetPersonName($userfave[0]["gid"]);
	if ($userfave[0]["type"] == "FAM") print GetFamilyDescriptor($userfave[0]["gid"]);
	if ($userfave[0]["type"] == "SOUR") print GetSourceDescriptor($userfave[0]["gid"]);
	if ($userfave[0]["type"] == "OBJE") print GetMediaDescriptor($userfave[0]["gid"]);
	print "<br />";
	print "<input type=\"hidden\" name=\"action\" value=\"storefav\" />\n";
	print "<input type=\"hidden\" name=\"id\" value=\"".$userfave[0]["id"]."\" />\n";
	print "<input type=\"hidden\" name=\"username\" value=\"".$userfave[0]["username"]."\" />\n";
	print "<input type=\"hidden\" name=\"type\" value=\"".$userfave[0]["type"]."\" />\n";
	print "<input type=\"hidden\" name=\"file\" value=\"".$userfave[0]["file"]."\" />\n";
	if ($userfave[0]["type"] == "URL") {
		print "<label for=\"favurl\">".$gm_lang["url"]."</label>";
		print "<input type=\"text\" id=\"favurl\" name=\"favurl\" size=\"40\" value=\"".$userfave[0]["url"]."\">";
		print "<br />";
		print "<label for=\"title\">".$gm_lang["title"]."</label>";
		print "<input type=\"text\" id=\"title\" name=\"favtitle\" size=\"40\" value=\"".$userfave[0]["title"]."\">";
		print "<br />";
	}
	print "<label for=\"favnote\">".$gm_lang["add_fav_enter_note"]."</label>";
	print "<textarea id=\"favnote\" name=\"favnote\" rows=\"6\" cols=\"40\">".$userfave[0]["note"]."</textarea>";
}
?>
