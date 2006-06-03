<?php
/**
 * RSS Block
 *
 * This is the RSS block
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
 * @version $Id: rss_block.php,v 1.4 2006/04/09 15:53:27 roland-d Exp $
 */
$GM_BLOCKS["print_RSS_block"]["name"]			= $gm_lang["rss_feeds"];
$GM_BLOCKS["print_RSS_block"]["descr"]			= "rss_descr";
$GM_BLOCKS["print_RSS_block"]["type"]			= "gedcom";
$GM_BLOCKS["print_RSS_block"]["canconfig"]		= false;
/**
 * Print RSS Block
 *
 * Prints a block allowing the user to login to the site directly from the portal
 */
function print_RSS_block($block = true, $config="", $side, $index) {
		global $LANGUAGE, $gm_lang, $GEDCOM, $GEDCOMS, $command, $SCRIPT_NAME, $QUERY_STRING, $ENABLE_MULTI_LANGUAGE;

		print "<div id=\"login_block\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("rss_feed_help", "qm");
		print $gm_lang["rss_feeds"];
		print "</div>";
		print "<div class=\"blockcontent center\">";
		print "<form method=\"post\" action=\"\" name=\"rssform\">\n";
		//print get_lang_select();
		print "<br />";
		//print "\n\t<select name=\"rssStyle\" class=\"header_select\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=' + document.rssform.lang.value + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";
		print "\n\t<select name=\"rssStyle\" class=\"header_select\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=" . $LANGUAGE . "' + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";
		print "\n\t\t<option value=\"RSS0.91\">RSS 0.91</option>";
		print "\n\t\t<option value=\"RSS1.0\" selected=\"selected\">RSS 1.0</option>";
		print "\n\t\t<option value=\"RSS2.0\">RSS 2.0</option>";
		print "\n\t\t<option value=\"ATOM\">ATOM</option>";
		//print "\n\t\t<option value=\"ATOM0.3\">ATOM 0.3</option>";
		print "\n\t\t<option value=\"HTML\">HTML</option>";
		print "\n\t\t<option value=\"JS\">JavaScript</option>";
		print "\n\t</select>";
		//print "\n\t<select name=\"module\" class=\"header_select\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=' + document.rssform.lang.value + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";
		print "\n\t<select name=\"module\" class=\"header_select\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=" . $LANGUAGE . "' + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";
		print "\n\t\t<option value=\"\">" . $gm_lang["all"] . "</option>";
		print "\n\t\t<option value=\"today\">" . $gm_lang["on_this_day"] . " </option>";
		print "\n\t\t<option value=\"upcoming\">" . $gm_lang["upcoming_events"] . "</option>";
		print "\n\t\t<option value=\"gedcomStats\">" . $gm_lang["gedcom_stats"] . "</option>";
		print "\n\t\t<option value=\"gedcomNews\">" . $gm_lang["gedcom_news"] . "</option>";
		print "\n\t\t<option value=\"top10Surnames\">" . $gm_lang["block_top10"] . "</option>";
		print "\n\t</select>";
		print "<br /><br /><a id=\"rss_button\" href=\"rss.php?lang=" . $LANGUAGE . "\"><img class=\"icon\" src=\"images/xml.gif\" alt=\"RSS\" title=\"RSS\" /></a>";
		print "</form>\n";
		print "</div>";
		print "</div>";
}

/*function get_lang_select() {
	 global $ENABLE_MULTI_LANGUAGE, $gm_lang, $gm_language, $flagsfile, $LANGUAGE, $language_settings;
	 global $LANG_FORM_COUNT;
	 global $SCRIPT_NAME, $QUERY_STRING;
	 $ret="";
	 if ($ENABLE_MULTI_LANGUAGE) {
		if (empty($LANG_FORM_COUNT)) $LANG_FORM_COUNT=1;
		else $LANG_FORM_COUNT++;

		//$ret .= "<select name=\"lang\" class=\"header_select\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=' + document.rssform.lang.value + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";
		$ret .= "<select name=\"lang\" class=\"header_select\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=" . $LANGUAGE . "' + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";

		foreach ($gm_language as $key=>$value) {
			if ($language_settings[$key]["gm_lang_use"]) {
				$ret .= "\n\t\t\t<option value=\"$key\" ";
				if ($LANGUAGE == $key) {
					$ret .=  "selected=\"selected\"";
				}
				$ret .=  ">".$gm_lang[$key]."</option>";
			}
		}
		$ret .=  "</select>\n\n";
	 }
	 return $ret;
}*/
?>
