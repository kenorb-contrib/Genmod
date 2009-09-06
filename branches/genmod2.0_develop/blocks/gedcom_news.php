<?php
/**
 * Gedcom News Block
 *
 * This block allows administrators to enter news items for the active gedcom
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

$GM_BLOCKS["print_gedcom_news"]["name"]        	= $gm_lang["gedcom_news_block"];
$GM_BLOCKS["print_gedcom_news"]["descr"]        = "gedcom_news_descr";
$GM_BLOCKS["print_gedcom_news"]["type"]        	= "gedcom";
$GM_BLOCKS["print_gedcom_news"]["canconfig"]   	= false;
$GM_BLOCKS["print_gedcom_news"]["rss"]			= true;

/**
 * Prints a gedcom news/journal
 *
 * @todo Add an allowed HTML translation
 */
function print_gedcom_news($block = true, $config="", $side, $index) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $TEXT_DIRECTION, $GEDCOM, $command, $TIME_FORMAT, $gm_username, $gm_user;

	$usernews = NewsController::getUserNews($GEDCOM);
	print "<div id=\"gedcom_news\" class=\"block\">\n";
	print "<div class=\"blockhc\">";
	if ($gm_user->userGedcomAdmin()) print_help_link("index_gedcom_news_ahelp", "qm_ah");
	else print_help_link("index_gedcom_news_help", "qm", "gedcom_news");
	print $gm_lang["gedcom_news"];
	print "</div>";
	print "<div class=\"blockcontent\">";

	if ($block) print "<div class=\"small_inner_block, $TEXT_DIRECTION\">\n";
	if (count($usernews)==0) {
		print $gm_lang["no_news"];
		print "<br />";
	}
	foreach($usernews as $key => $news) {
		$day = date("j", $news->date);
		$mon = date("M", $news->date);
		$year = date("Y", $news->date);
		print "<div id=\"".$news->anchor."\">\n";
		$news->title = ReplaceEmbedText($news->title);
		print "<span class=\"news_title\">".PrintReady($news->title)."</span><br />\n";
		print "<span class=\"news_date\">".GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, $news->date)."</span><br /><br />\n";
		$news->text = ReplaceEmbedText($news->text);
		$trans = get_html_translation_table(HTML_SPECIALCHARS);
		$trans = array_flip($trans);
		$news->text = strtr($news->text, $trans);
		$news->text = nl2br($news->text);
		print PrintReady($news->text)."<br />\n";
		if ($gm_user->userGedcomAdmin()) {
			print "<hr size=\"1\" />";
			print "<a href=\"#\" onclick=\"editnews('".$news->id."'); return false;\">".$gm_lang["edit"]."</a> | ";
			print "<a href=\"index.php?action=deletenews&amp;news_id=".$news->id."&amp;command=$command\" onclick=\"return confirm('".$gm_lang["confirm_news_delete"]."');\">".$gm_lang["delete"]."</a><br />";
		}
		print "</div>\n";
	}
	if ($block) print "</div>\n";
	if ($gm_user->userGedcomAdmin()) print "<a href=\"#\" onclick=\"addnews('".preg_replace("/'/", "\'", $GEDCOM)."'); return false;\">".$gm_lang["add_news"]."</a>\n";
	print "</div>\n";
	print "</div>";
}
?>