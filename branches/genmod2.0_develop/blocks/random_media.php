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
	global $gm_lang, $GEDCOM, $foundlist, $medialist, $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES;
	global $MEDIA_EXTERNAL, $MEDIA_DIRECTORY, $SHOW_SOURCES, $GEDCOM_ID_PREFIX, $FAM_ID_PREFIX, $SOURCE_ID_PREFIX;
	global $MEDIATYPE, $medialist, $gm_username, $USE_GREYBOX;
	
	$foundlist = array();
	srand();
	$random = 10;
	$mediaobjs = new Media;
	$mediaobjs->RetrieveMedia($random,0,1); 
	$ct = count($mediaobjs);
	if ($mediaobjs->mediainlist > 0) {
		$value = $mediaobjs->lastitem;
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
		if (preg_match("'://'", $value->filename)) {
			if (in_array(strtolower($value->extension), $MEDIATYPE)){
			   $imgwidth = 400;
			   $imgheight = 500;
			} 
		}
		else if ((preg_match("'://'", $MEDIA_DIRECTORY)>0)||$value->fileobj->f_file_exists) {
			   $imgwidth = $value->fileobj->f_width+50;
			   $imgheight = $value->fileobj->f_height+50;
		}
		if ($USE_GREYBOX && $value->fileobj->f_is_image) {
			print "<a href=\"".FilenameEncode($value->fileobj->f_main_file)."\" title=\"".$value->title."\" rel=\"gb_imageset[random]\">";
		}
		else print "<a href=\"#\" onclick=\"return openImage('".$value->fileobj->f_main_file."', '".$imgwidth."', '".$imgheight."', '".$value->fileobj->f_is_image."');\">";
		if ($block) {
			print "<img src=\"".$value->fileobj->f_thumb_file."\" border=\"0\" class=\"thumbnail\" alt=\"\" ";
			if ($value->fileobj->f_twidth > 175) print "width=\"175\" ";
			print "/>";
		}
		else {
			if ($value->fileobj->f_file_exists || strstr($value->filename, "://")) {
				print "<img src=\"".$value->m_main_file."\" border=\"0\" class=\"thumbnail\" alt=\"\" ";
				if (!stristr($value->m_main_file, "://")) {
					if ($value->fileobj->f_width > 175) print "width=\"175\" ";
				}
				else print "width=\"175\" ";
				print "/>";
			}
		}
		print "</a>\n";
		if ($block) print "<br />";
		if ($value->title!=$value->filename) {
		    print "<a href=\"mediadetail.php?mid=".$value->xref."&amp;ged=".$GEDCOM."\">";
		    if (strlen($value->title) > 0) print "<b>".PrintReady($value->title)."</b><br />";
			print "</a>";
		}
		$links = $value->links;
		if (count($links) != 0){
			foreach($links as $key=>$id) {
				if (($id=="INDI")&&(displayDetailsByID($key))) {
					print " <a href=\"individual.php?pid=".$key."\">".$gm_lang["view_person"]." - ";
					if (HasChinese(GetPersonName($key))) print PrintReady(GetPersonName($key)." (".GetPinYin(GetPersonName($key)).")");
					else print PrintReady(GetPersonName($key));
					print "</a><br />";
				}
				if ($id=="FAM") {
					print " <a href=\"family.php?famid=".$key."\">".$gm_lang["view_family"]." - ";
					if (HasChinese(GetPersonName($key))) print PrintReady(GetFamilyDescriptor($key)." (".GetPinYin(GetFamilyDescriptor($key)).")");
					else print PrintReady(GetFamilyDescriptor($key));
					print "</a><br />";
				}
				if ($id=="SOUR") print " <a href=\"source.php?sid=".$key."\">".$gm_lang["view_source"]." - ".PrintReady(GetSourceDescriptor($key))."</a><br />";
			}
		}
		print "<br /><div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
		print_fact_notes($value->gedrec, "1");
		print "</div>";
		print "</div>"; // blockcontent
		print "</div>"; // block
	}
}
?>
