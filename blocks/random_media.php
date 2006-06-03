<?php
/**
 * Random Media Block
 *
 * This block will randomly choose media items and show them in a block
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
 * @version $Id: random_media.php,v 1.9 2006/02/19 11:32:04 roland-d Exp $
 */

//-- only enable this block if multi media has been enabled
if ($MULTI_MEDIA) {
	$GM_BLOCKS["print_random_media"]["name"]        = $gm_lang["random_media_block"];
	$GM_BLOCKS["print_random_media"]["descr"]        = "random_media_descr";
	$GM_BLOCKS["print_random_media"]["canconfig"]        = false;

	//-- function to display a random picture from the gedcom
	function print_random_media($block = true, $config="", $side, $index) {
		global $gm_lang, $GEDCOM, $foundlist, $medialist, $MULTI_MEDIA, $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES;
		global $MEDIA_EXTERNAL, $MEDIA_DIRECTORY, $SHOW_SOURCES, $GEDCOM_ID_PREFIX, $FAM_ID_PREFIX, $SOURCE_ID_PREFIX;
		global $MEDIATYPE, $medialist, $gm_username;
		
		if (!$MULTI_MEDIA) return;
		$foundlist = array();
		
		$random = "10";
		get_medialist(false, "", $random);
		$ct = count($medialist);
		if ($ct>0) {
			if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) $showsource = true;
			else $showsource = false;
			$disp = false;
			$i=0;
			while(!$disp && $i<$random) {
				$value = array_rand($medialist);
				$links = $medialist[$value]["LINKS"];
				$disp = true;
				if (count($links) != 0){
					foreach($links as $id=>$type) {
						switch (id_type($id)) {
							case "INDI":
								$disp = $disp && displayDetailsByID($id);
								break;
							case "FAM" :
								$parents = find_parents($id);
								$disp = $disp && displayDetailsByID($parents["HUSB"]);
								$disp = $disp && displayDetailsByID($parents["WIFE"]);
								break;
							case "SOUR" :
								if ($showsource) $disp = $disp && showFact("OBJE", $id);
								else $disp = false;
								break;
						}
					}
				}
				$i++;
			}
			if (!$disp) return false;
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
			if (preg_match("'://'", $medialist[$value]["FILE"])) {
				$extension=pathinfo($medialist[$value]["FILE"], PATHINFO_EXTENSION);
				if (in_array($extension, $MEDIATYPE)){
				   $imgwidth = 400;
				   $imgheight = 500;
				} 
			}
			else if ((preg_match("'://'", $MEDIA_DIRECTORY)>0)||$medialist[$value]["EXISTS"]) {
			   $imgsize = getimagesize(filename_decode($medialist[$value]["FILE"]));
			   if ($imgsize){
				   $imgwidth = $imgsize[0]+50;
				   $imgheight = $imgsize[1]+50;
			   }
			}
			print "<a href=\"#\" onclick=\"return openImage('".rawurlencode($medialist[$value]["FILE"])."',$imgwidth, $imgheight);\">";
			if ($block) {
				print "<img src=\"".$medialist[$value]["THUMB"]."\" border=\"0\" class=\"thumbnail\" alt=\"\" ";
				$imgsize = getimagesize(filename_decode($medialist[$value]["THUMB"]));
				if ($imgsize[0] > 175) print "width=\"175\" ";
				print "/>";
			}
			else {
				if (file_exists(filename_decode($medialist[$value]["FILE"])) || strstr($medialist[$value]["FILE"], "://")) {
					print "<img src=\"".$medialist[$value]["FILE"]."\" border=\"0\" class=\"thumbnail\" alt=\"\" ";
					if (!stristr($medialist[$value]["FILE"], "://")) {
						$imgsize = getimagesize(filename_decode($medialist[$value]["FILE"]));
						if ($imgsize[0] > 175) print "width=\"175\" ";
					}
					else print "width=\"175\" ";
					print "/>";
				}
			}
			print "</a>\n";
			if ($block) print "<br />";
			if ($medialist[$value]["TITL"]!=$medialist[$value]["FILE"]) {
			    print "<a href=\"medialist.php?action=filter&amp;search=yes&amp;filter=".rawurlencode($medialist[$value]["FILE"])."&amp;ged=".$GEDCOM."\">";
			    if (strlen($medialist[$value]["TITL"]) > 0) print "<b>".PrintReady($medialist[$value]["TITL"])."</b><br />";
			}
			else print "<a href=\"#\" onclick=\"return openImage('".rawurlencode($medialist[$value]["FILE"])."',$imgwidth, $imgheight);\">";
			print "</a>";
			$links = $medialist[$value]["LINKS"];
			if (count($links) != 0){
				foreach($links as $key=>$id) {
					if (($id=="INDI")&&(displayDetailsByID($key))) print " <a href=\"individual.php?pid=".$key."\">".$gm_lang["view_person"]." - ".PrintReady(get_person_name($key))."</a><br />";
					if ($id=="FAM") print " <a href=\"family.php?famid=".$key."\">".$gm_lang["view_family"]." - ".PrintReady(get_family_descriptor($key))."</a><br />";
					if ($id=="SOUR") print " <a href=\"source.php?sid=".$key."\">".$gm_lang["view_source"]." - ".PrintReady(get_source_descriptor($key))."</a><br />";
				}
			}
			print "<br /><div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
			print_fact_notes($medialist[$value]["GEDCOM"], "1");
			print "</div>";
			print "</div>"; // blockcontent
			print "</div>"; // block
		}
	}
}
?>
