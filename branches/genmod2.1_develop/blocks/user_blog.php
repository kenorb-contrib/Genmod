<?php
/**
 * User Blog Block
 *
 * This block allows users to have their own blog
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
 * $Id: user_blog.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_user_news"]["name"]       = GM_LANG_user_news_block;
$GM_BLOCKS["print_user_news"]["descr"]      = "user_news_descr";
$GM_BLOCKS["print_user_news"]["type"]       = "user";
$GM_BLOCKS["print_user_news"]["canconfig"]	= false;
$GM_BLOCKS["print_user_news"]["rss"]        = false;

/**
 * Prints a user news/journal
 *
 */
function print_user_news($block=true, $config="", $side, $index) {
	global $TEXT_DIRECTION, $command, $TIME_FORMAT, $gm_user;

	print "<!-- Start User News Block //-->";
	$usernews = NewsController::getUserNews($gm_user->username);

	print "<div id=\"user_news\" class=\"BlockContainer\">\n";
		print "<div class=\"BlockHeader\">";
			PrintHelpLink("mygenmod_myjournal_help", "qm", "my_journal");
			print "<div class=\"BlockHeaderText\">".GM_LANG_my_journal."</div>";
		print "</div>";
		print "<div class=\"BlockContent\">";
			if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
			else print "<div class=\"RestrictedBlockHeightMain\">\n";
			if (count($usernews)==0) {
				print "<div class=\"NewsMessage\">".GM_LANG_no_journal."</div>";
			}
			foreach($usernews as $key => $news) {
				$day = date("j", $news->date);
				$mon = date("M", $news->date);
				$year = date("Y", $news->date);
				print "<div class=\"NewsBlockItem\">\n";
				
					$news->title = ReplaceEmbedText($news->title);
					print "<div class=\"NewsBlockTitle\">".PrintReady($news->title)."</div>\n";
					print "<div class=\"NewsBlockDate\">".GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, $news->date)."</div>\n";
					
					$news->text = ReplaceEmbedText($news->text);
					$trans = get_html_translation_table(HTML_SPECIALCHARS);
					$trans = array_flip($trans);
					$news->text = strtr($news->text, $trans);
			//		$news->text = nl2br($news->text);
					print "<div class=\"NewsBlockText\">".PrintReady($news->text)."</div>\n";
					print "<div class=\"SmallEditLinks\">";
						print "<a href=\"#\" onclick=\"editnews('".$news->id."'); return false;\">".GM_LANG_edit."</a> | ";
						print "<a href=\"index.php?action=deletenews&amp;news_id=".$news->id."&amp;command=$command\" onclick=\"return confirm('".GM_LANG_confirm_journal_delete."');\">".GM_LANG_delete."</a>";
						print "<hr size=\"1\" />";
					print "</div>";
				print "</div>\n";
			}
			print "</div>\n";
		if ($gm_user->username != "") print "<div class=\"SmallEditLinks\"><a href=\"#\" onclick=\"addnews('".$gm_user->username."'); return false;\">".GM_LANG_add_journal."</a></div>\n";
		print "</div>\n";
	print "</div>";
	print "<!-- End User News Block //-->";
}
?>