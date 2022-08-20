<?php
/**
 * Random Media Block
 *
 * This block will randomly choose media items and show them in a block
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
 * @version $Id: random_media.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

//-- only enable this block if multi media has been enabled
$GM_BLOCKS["print_random_media"]["name"]		= GM_LANG_random_media_block;
$GM_BLOCKS["print_random_media"]["descr"]		= "random_media_descr";
$GM_BLOCKS["print_random_media"]["canconfig"]	= false;
$GM_BLOCKS["print_random_media"]["rss"]     = false;

//-- function to display a random picture from the gedcom
function print_random_media($block = true, $config="", $side, $index) {
	global $TEXT_DIRECTION, $GM_IMAGES;
	
	print "<!-- Start Random Media Block //-->";
	srand();
	$random = 10;
	$mediacontroller = new MediaListController;
	$mediacontroller->RetrieveMedia($random,0,1); 
	if ($mediacontroller->mediainlist > 0) {
		$media = $mediacontroller->lastitem;
		print "<div id=\"random_picture\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
				PrintHelpLink("index_media_help", "qm", "random_picture");
				print "<div class=\"BlockHeaderText\">".GM_LANG_random_picture."</div>";
			print "</div>";
			print "<div class=\"BlockContent\">";
				$imgwidth = 300;
				$imgheight = 300;
				if (preg_match("'://'", $media->filename)) {
					if (MediaFS::IsValidMedia($media->filename)) {
					   $imgwidth = 400;
					   $imgheight = 500;
					} 
				}
				else if ((preg_match("'://'", GedcomConfig::$MEDIA_DIRECTORY)>0)||$media->fileobj->f_file_exists) {
					   $imgwidth = $media->fileobj->f_width+50;
					   $imgheight = $media->fileobj->f_height+50;
				}
				$twidth = 0;
				if ($block) {
					$tfile = $media->fileobj->f_thumb_file;
					if ($media->fileobj->f_twidth > 175) $twidth = 175;
				}
				else {
					if ($media->fileobj->f_file_exists || strstr($media->filename, "://")) {
						$tfile = $media->fileobj->f_main_file;
						if (!stristr($media->fileobj->f_main_file, "://")) {
							if ($media->fileobj->f_width > 175) print $twidth = 175;
						}
						else $twidth = 175;
					}
				}
				MediaFS::DispImgLink($media->fileobj->f_main_file, $media->fileobj->f_thumb_file, $media->title, "random", $twidth, 0, $imgwidth, $imgheight, $media->fileobj->f_is_image, $media->fileobj->f_file_exists, true, true);
				if ($block) print "<br />";
				if ($media->title!=$media->filename) {
				    print "<div class=\"RandomMediaBlockImageLink\"><a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\">";
				    if (strlen($media->title) > 0) print PrintReady($media->title);
					print "</a></div>";
				}
				foreach($media->indilist as $key => $indi) {
					print "<div class=\"RandomMediaBlockLinks\"><a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\">".GM_LANG_view_person.": ";
					print $indi->name.($indi->addname == "" ? "" : "  (".$indi->addname.")").$indi->addxref;
					print "</a></div>";
				}
				foreach($media->famlist as $key => $family) {
					print "<div class=\"RandomMediaBlockLinks\"><a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">".GM_LANG_view_family.": ";
					print $family->name.($family->addname == "" ? "" : "  (".$family->addname.")").$family->addxref;
					print "</a></div>";
				}
				foreach($media->sourcelist as $key => $source) {
					print "<div class=\"RandomMediaBlockLinks\"><a href=\"source.php?sid=".$source->xref."&amp;gedid=".$source->gedcomid."\">".GM_LANG_view_source.": ";
					print $source->descriptor.$source->addxref;
					print "</a></div>";
				}
				foreach($media->repolist as $key => $repo) {
					print "<div class=\"RandomMediaBlockLinks\"><a href=\"repo.php?rid=".$repo->xref."&amp;gedid=".$repo->gedcomid."\">".GM_LANG_view_repo.": ";
					print $repo->descriptor.$repo->addxref;
					print "</a></div>";
				}
				print "<div class=\"RandomMediaBlockNotes\">";
					FactFunctions::PrintFactNotes($media->gedrec, "1");
				print "</div>";
			print "</div>"; // blockcontent
		print "</div>"; // block
	}
	print "<!-- End Random Media Block //-->";
}
?>
