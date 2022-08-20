<?php
/**
 * Simple HTML Block
 *
 * This block will print simple HTML text entered by an admin
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
 * @version $Id: html_block.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

$GM_BLOCKS["print_html_block"]["name"]        = GM_LANG_html_block_name;
$GM_BLOCKS["print_html_block"]["descr"]       = "html_block_descr";
$GM_BLOCKS["print_html_block"]["canconfig"]   = true;
$GM_BLOCKS["print_html_block"]["config"]      = array("html"=>GM_LANG_html_block_sample_part1." <img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]."\" alt=\"".GM_LANG_config_block."\" title=\"".GM_LANG_config_block."\" /> ".GM_LANG_html_block_sample_part2, "only_show_logged_in"=>"no");
$GM_BLOCKS["print_html_block"]["rss"]			= false;

function print_html_block($block=true, $config="", $side, $index) {
	global $TEXT_DIRECTION, $GM_IMAGES, $HTML_BLOCK_COUNT, $GM_BLOCKS, $command, $gm_user;

	if (empty($config)) $config = $GM_BLOCKS["print_html_block"]["config"];
	if ($config["only_show_logged_in"] != "no" && $gm_user->username == "") return;
	if (!isset($HTML_BLOCK_COUNT)) $HTML_BLOCK_COUNT = 0;
	$HTML_BLOCK_COUNT++;
	print "<!-- Start HTML Block //-->";
	print "<div id=\"html_block$HTML_BLOCK_COUNT\" class=\"BlockContainer\">\n";
		print "<div class=\"BlockContent\">\n";
			if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
			else print "<div class=\"RestrictedBlockHeightMain\">\n";

				$config["html"] = ReplaceEmbedText($config["html"]);
				print $config["html"];

			print "</div>\n";
			if ($GM_BLOCKS["print_html_block"]["canconfig"]) {
				$username = $gm_user->username;
				if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
					if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
					else $name = $username;
					print "<div class=\"BlockAdminLinkBottom\"><a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=750,height=550,scrollbars=1,resizable=1'); return false;\">";
					BlockFunctions::PrintAdminIcon();
					print "</a></div>";
				}
			}
		print "</div>"; // blockcontent
	print "</div>"; // block
	print "<!-- End HTML Block //-->";
}

function print_html_block_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION, $LANGUAGE, $language_settings;
	
	$useCK = file_exists("modules/CKeditor/ckeditor.php");
	if($useCK){
		include("modules/CKeditor/ckeditor.php");
	}
	if (empty($config)) $config = $GM_BLOCKS["print_html_block"]["config"];
	?>
	<tr><td class="NavBlockField" colspan="2">
	<?php
		if ($useCK) { // use CKeditor module
			?><script type="text/javascript" src="modules/CKEditor/ckeditor.js"></script><?php
			$oCKEditor = new CKeditor();
			$oCKEditor->BasePath = "modules/CKEditor/";
			$oCKEditor->config["height"] = 450;
			$oCKEditor->config["enterMode"] = "br";
			$oCKEditor->config["ShiftEnterMode"] = "p";
			$oCKeditor->config["language"] = $language_settings[$LANGUAGE]["lang_short_cut"];
			$oCKEditor->editor("html", $config['html']);
		} else { //use standard textarea
			print "<textarea name=\"html\" rows=\"10\" cols=\"80\">" . $config["html"] ."</textarea>";
		}
	?>
	</td></tr>
	<tr><td class="NavBlockLabel">
  	<?php print GM_LANG_only_show_logged_in;?></td>
  	<td class="NavBlockField">
	<select name="only_show_logged_in">
		<option value="no"<?php if ($config["only_show_logged_in"]=="no") print " selected=\"selected\"";?>><?php print GM_LANG_no; ?></option>
		<option value="yes"<?php if ($config["only_show_logged_in"]=="yes") print " selected=\"selected\"";?>><?php print GM_LANG_yes; ?></option>
	</select>
	</td></tr>
	<?php
}
?>
