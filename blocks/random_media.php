<?php
/**
 * Random Media Block
 *
 * This block will randomly choose media items and show them in a block
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

//-- only enable this block if multi media has been enabled
$GM_BLOCKS["print_random_media"]["name"]		= $gm_lang["random_media_block"];
$GM_BLOCKS["print_random_media"]["descr"]		= "random_media_descr";
$GM_BLOCKS["print_random_media"]["canconfig"]	= false;
$GM_BLOCKS["print_random_media"]["rss"]     = false;

//-- function to display a random picture from the gedcom
function print_random_media($block = true, $config="", $side, $index) {
	global $gm_lang, $GEDCOMID, $foundlist, $medialist, $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES;
	global $MEDIA_EXTERNAL, $MEDIA_DIRECTORY, $SHOW_SOURCES, $GEDCOM_ID_PREFIX, $FAM_ID_PREFIX, $SOURCE_ID_PREFIX;
	global $MEDIATYPE, $medialist, $USE_GREYBOX;
	
	$foundlist = array();
	srand();
	$random = 10;
	$mediacontroller = new MediaListController;
	$mediacontroller->RetrieveMedia($random,0,1); 
	if ($mediacontroller->mediainlist > 0) {
		$media = $mediacontroller->lastitem;
		print "<div id=\"random_picture\" class=\"block\">\n";
		print "<div class=\"blockhc ltr\">";
		print_help_link("index_media_help", "qm", "random_picture");
		print $gm_lang["random_picture"];
		print "</div>";
		print "<div class=\"blockcontent";
		if ($block) print " details1 wrap\"";
		else print " details2 wrap\"";
		print " >";
		$imgwidth = 300;
		$imgheight = 300;
		if (preg_match("'://'", $media->filename)) {
			if (in_array(strtolower($media->extension), $MEDIATYPE)){
			   $imgwidth = 400;
			   $imgheight = 500;
			} 
		}
		else if ((preg_match("'://'", $MEDIA_DIRECTORY)>0)||$media->fileobj->f_file_exists) {
			   $imgwidth = $media->fileobj->f_width+50;
			   $imgheight = $media->fileobj->f_height+50;
		}
		if ($USE_GREYBOX && $media->fileobj->f_is_image) {
			print "<a href=\"".FilenameEncode($media->fileobj->f_main_file)."\" title=\"".$media->title."\" rel=\"gb_imageset[random]\">";
		}
		else print "<a href=\"#\" onclick=\"return openImage('".$media->fileobj->f_main_file."', '".$imgwidth."', '".$imgheight."', '".$media->fileobj->f_is_image."');\">";
		if ($block) {
			print "<img src=\"".$media->fileobj->f_thumb_file."\" border=\"0\" class=\"thumbnail\" alt=\"\" ";
			if ($media->fileobj->f_twidth > 175) print "width=\"175\" ";
			print "/>";
		}
		else {
			if ($media->fileobj->f_file_exists || strstr($media->filename, "://")) {
				print "<img src=\"".$media->m_main_file."\" border=\"0\" class=\"thumbnail\" alt=\"\" ";
				if (!stristr($media->m_main_file, "://")) {
					if ($media->fileobj->f_width > 175) print "width=\"175\" ";
				}
				else print "width=\"175\" ";
				print "/>";
			}
		}
		print "</a>\n";
		if ($block) print "<br />";
		if ($media->title!=$media->filename) {
		    print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$GEDCOMID."\">";
		    if (strlen($media->title) > 0) print "<b>".PrintReady($media->title)."</b><br />";
			print "</a>";
		}
		foreach($media->indilist as $key => $indi) {
			print " <a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\">".$gm_lang["view_person"].": ";
			print $indi->name.($indi->addname == "" ? "" : " - ".$indi->addname).$indi->addxref;
			print "</a><br />";
		}
		foreach($media->famlist as $key => $family) {
			print " <a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">".$gm_lang["view_family"].": ";
			print $family->descriptor.$family->addxref;
			print "</a><br />";
		}
		foreach($media->sourcelist as $key => $source) {
			print " <a href=\"source.php?sid=".$source->xref."&amp;gedid=".$source->gedcomid."\">".$gm_lang["view_source"].": ";
			print $source->descriptor.$source->addxref;
			print "</a><br />";
		}
		foreach($media->repolist as $key => $repo) {
			print " <a href=\"repo.php?rid=".$repo->xref."&amp;gedid=".$repo->gedcomid."\">".$gm_lang["view_repo"].": ";
			print $repo->descriptor.$repo->addxref;
			print "</a><br />";
		}
		print "<br /><div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
		FactFunctions::PrintFactNotes($media->gedrec, "1");
		print "</div>";
		print "</div>"; // blockcontent
		print "</div>"; // block
	}
}
?>
