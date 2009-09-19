<?php
/**
 * User Blog Block
 *
 * This block allows users to have their own blog
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
 * $Id$
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_user_news"]["name"]       = $gm_lang["user_news_block"];
$GM_BLOCKS["print_user_news"]["descr"]      = "user_news_descr";
$GM_BLOCKS["print_user_news"]["type"]       = "user";
$GM_BLOCKS["print_user_news"]["canconfig"]	= false;
$GM_BLOCKS["print_user_news"]["rss"]        = false;

/**
 * Prints a user news/journal
 *
 */
function print_user_news($block=true, $config="", $side, $index) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $TEXT_DIRECTION, $command, $TIME_FORMAT, $gm_user;

	$usernews = NewsController::getUserNews($gm_user->username);

	print "<div id=\"user_news\" class=\"block\">\n";
	print "<div class=\"blockhc\">";
	print_help_link("mygedview_myjournal_help", "qm", "my_journal");
	print $gm_lang["my_journal"];
	print "</div>";
	print "<div class=\"blockcontent\">";
	if ($block) print "<div class=\"small_inner_block, $TEXT_DIRECTION\">\n";
	if (count($usernews)==0) print $gm_lang["no_journal"];
	foreach($usernews as $key => $news) {
		$day = date("j", $news->date);
		$mon = date("M", $news->date);
		$year = date("Y", $news->date);
		print "<div class=\"person_box\">\n";
		
		$news->title = ReplaceEmbedText($news->title);
		print "<span class=\"news_title\">".PrintReady($news->title)."</span><br />\n";
		print "<span class=\"news_date\">".GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, $news->date)."</span><br /><br />\n";
		
		$news->text = ReplaceEmbedText($news->text);
		$trans = get_html_translation_table(HTML_SPECIALCHARS);
		$trans = array_flip($trans);
		$news->text = strtr($news->text, $trans);
		$news->text = nl2br($news->text);
		print PrintReady($news->text)."<br /><br />\n";
		print "<a href=\"#\" onclick=\"editnews('".$news->id."'); return false;\">".$gm_lang["edit"]."</a> | ";
		print "<a href=\"index.php?action=deletenews&amp;news_id=".$news->id."&amp;command=$command\" onclick=\"return confirm('".$gm_lang["confirm_journal_delete"]."');\">".$gm_lang["delete"]."</a><br />";
		print "</div><br />\n";
	}
	if ($block) print "</div>\n";
	if ($gm_user->username != "") print "<br /><a href=\"#\" onclick=\"addnews('".$gm_user->username."'); return false;\">".$gm_lang["add_journal"]."</a>\n";
	print "</div>\n";
	print "</div>";
}
?>