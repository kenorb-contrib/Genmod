<?php
/**
 * PinYin translation Block
 *
 * This block will print basic information and links for the user.
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
 * @version $Id: pinyin_block.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_pinyin_block"]["name"]       = GM_LANG_pinyin_block;
$GM_BLOCKS["print_pinyin_block"]["descr"]      = "pinyin_descr";
$GM_BLOCKS["print_pinyin_block"]["type"]       = "both";
$GM_BLOCKS["print_pinyin_block"]["canconfig"]	= false;
$GM_BLOCKS["print_pinyin_block"]["rss"]       	= false;

//-- function to print the welcome block
function print_pinyin_block($block=true, $config="", $side, $index) {

	print "<!-- Start PinYin translation Block //-->";
		print "<div id=\"pinyin_block\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
				print "<div class=\"BlockHeaderText\">".GM_LANG_pinyin_translator."</div>";
			print "</div>";
			print "<div class=\"BlockContent\">";
				print GM_LANG_pinyin_translate_desc."<br /><br />";
				print GM_LANG_pinyin_chinese_text."<br />";
				print "<form name=\"pinyinform\" action=\"index.php\">";
				print "<input id=\"chinese\" type=\"text\" name=\"chinese\" />\n";
				print "<input type=\"button\" value=\"".GM_LANG_pinyin_translate."\" onclick=\"sndReq('PinYinResult', 'getpinyin', true, 'chinese', encodeURI(document.pinyinform.chinese.value));\" />\n";
				print "</form>";
				print "<div id=\"PinYinResult\">&nbsp;</div>\n";
			print "</div>"; // blockcontent
		print "</div>"; // block
	print "<!-- End PinYin translation Block //-->";
}
?>