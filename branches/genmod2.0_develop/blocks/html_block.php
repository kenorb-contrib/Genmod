<?php
/**
 * Simple HTML Block
 *
 * This block will print simple HTML text entered by an admin
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

$GM_BLOCKS["print_html_block"]["name"]        = $gm_lang["html_block_name"];
$GM_BLOCKS["print_html_block"]["descr"]       = "html_block_descr";
$GM_BLOCKS["print_html_block"]["canconfig"]   = true;
$GM_BLOCKS["print_html_block"]["config"]      = array("html"=>$gm_lang["html_block_sample_part1"]." <img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]."\" alt=\"".$gm_lang["config_block"]."\" /> ".$gm_lang["html_block_sample_part2"], "only_show_logged_in"=>"no");
$GM_BLOCKS["print_html_block"]["rss"]			= false;

function print_html_block($block=true, $config="", $side, $index) {
	global $gm_lang, $GM_IMAGE_DIR, $TEXT_DIRECTION, $GM_IMAGES, $HTML_BLOCK_COUNT, $GM_BLOCKS, $command, $GEDCOMID, $gm_username, $gm_user;

	if (empty($config)) $config = $GM_BLOCKS["print_html_block"]["config"];
	if ($config["only_show_logged_in"] != "no" && empty($gm_username)) return;
	if (!isset($HTML_BLOCK_COUNT)) $HTML_BLOCK_COUNT = 0;
	$HTML_BLOCK_COUNT++;
	print "<div id=\"html_block$HTML_BLOCK_COUNT\" class=\"block\">\n";
	print "<div class=\"blockcontent\">";
	if ($block) print "<div class=\"small_inner_block\">\n";

	$config["html"] = ReplaceEmbedText($config["html"]);
	print $config["html"];

	if ($block) print "</div>\n";
	if ($GM_BLOCKS["print_html_block"]["canconfig"]) {
		$username = $gm_username;
		if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id($GEDCOMID));
			else $name = $username;
			print "<br /><a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=750,height=550,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
		}
	}
	print "</div>"; // blockcontent
	print "</div>"; // block
}

function print_html_block_config($config) {
	global $gm_lang, $GM_BLOCKS, $TEXT_DIRECTION, $LANGUAGE, $language_settings;
	$useFCK = file_exists("./modules/FCKeditor/fckeditor.php");
	if($useFCK){
		include("./modules/FCKeditor/fckeditor.php");
	}
	if (empty($config)) $config = $GM_BLOCKS["print_html_block"]["config"];
	?>
	<tr><td class="shade1">
	<?php
		if ($useFCK) { // use FCKeditor module
			$oFCKeditor = new FCKeditor('html') ;
			$oFCKeditor->BasePath =  './modules/FCKeditor/';
			$oFCKeditor->Value = $config["html"];
			$oFCKeditor->Width = 700;
			$oFCKeditor->Height = 450;
			$oFCKeditor->Config['AutoDetectLanguage'] = false ;
			$oFCKeditor->Config['DefaultLanguage'] = $language_settings[$LANGUAGE]["lang_short_cut"];
			$oFCKeditor->Create() ;
		} else { //use standard textarea
			print "<textarea name=\"html\" rows=\"10\" cols=\"80\">" . $config["html"] ."</textarea>";
		}
	?>
	</td></tr>
	<tr><td class="shade1">
  	<?php print $gm_lang["only_show_logged_in"];?>
	<select name="only_show_logged_in">
		<option value="no"<?php if ($config["only_show_logged_in"]=="no") print " selected=\"selected\"";?>><?php print $gm_lang["no"]; ?></option>
		<option value="yes"<?php if ($config["only_show_logged_in"]=="yes") print " selected=\"selected\"";?>><?php print $gm_lang["yes"]; ?></option>
	</select>
	</td></tr>
	<?php
}
?>
