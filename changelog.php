<?php
/**
 * Display changelog file with clickable bugs and RFEs
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
 * @subpackage Admin
 * @version $Id: changelog.php 13 2016-04-27 09:26:01Z Boudewijn $
 */
$search = @$HTTP_GET_VARS["search"];
if (empty($search)) $search = @$_GET["search"];

print "<title>Genmod : changelog ($search)</title>\n";

$text = file_get_contents("changelog.txt");
$wait = @file_get_contents("changelog.local.txt");
$text = $wait.$text;

// disable HTML tags
$text = preg_replace("/</", "&lt;", $text);
$text = preg_replace("/>/", "&gt;", $text);

// highlight search text (caseless)
if (!empty($search)) {
	$text = preg_replace("/(.*)(?i)($search)(.*)\\n/", "<span style=\"background-color:#DADADA\">\\0</span>", $text);
	$text = preg_replace("/(?i)$search/", "<span style=\"background-color:Yellow\">\\0</span>", $text);
}

// add link to tracker
$text = preg_replace("/RFE(\d{6,7})/", "RFE \\1", $text);	// RFE1234567 ==> RFE 1234567
$text = preg_replace("/#(\d{6,7})/", "# \\1", $text);		// #1234567 ==> # 1234567
$text = preg_replace("/\[(\d{6,7})/", "[ \\1", $text);		// [1234567 ==> [ 1234567
$text = preg_replace("/(\d{6,7})\]/", "\\1 ]", $text);		// 1234567] ==> 1234567 ]
$text = preg_replace("/\((\d{6,7})/", "( \\1", $text);		// (1234567 ==> ( 1234567
$text = preg_replace("/(\d{6,7})\)/", "\\1 )", $text);		// 1234567) ==> 1234567 )
$text = preg_replace("/(\d{6,7})\,/", "\\1 ,", $text);		// 1234567, ==> 1234567 ,
$text = preg_replace("/ (\d{6,7}) /", " <a name=\\1 href=http://sourceforge.net/support/tracker.php?aid=\\1>\\1</a> ", $text);

$text = preg_replace("/ \(([-\w]{4,12})\)\r\n/", " (<a name=\\1 href=?search=\\1>\\1</a>)\r\n", $text);
$text = preg_replace("/  /", " ", $text);

print "<pre>\n$text\n</pre>\n";
?>
