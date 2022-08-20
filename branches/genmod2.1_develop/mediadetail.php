<?php
/**
 * Displays the details about a media record. Also shows how many people and families
 * reference this media.
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
 * @subpackage Display
 * @version $Id: mediadetail.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the media controller
*/
$media_controller = new mediaController();

PrintHeader($media_controller->pagetitle);

$media_controller->CheckNoResult(GM_LANG_media_not_found);

$media_controller->CheckPrivate();

$media_controller->CheckRawEdited();

?>
<?php $media_controller->PrintDetailJS(); ?>
<div class="DetailHeaderSection"><?php
	// Print the title
	print "<div class=\"PageTitleName\">".PrintReady($media_controller->media->title.$media_controller->media->addxref)."</div>";
		
	// Print the picture!
	$filename = $media_controller->media->fileobj->f_main_file;
	$thumbnail = $media_controller->media->fileobj->f_thumb_file;
	// NOTE: Determine the size of the mediafile
	$imgwidth = 300;
	$imgheight = 300;
	if (preg_match("'://'", $filename)) {
		if ($media_controller->media->validmedia) {
			$imgwidth = 400;
			$imgheight = 500;
		}
		else {
			$imgwidth = 800;
			$imgheight = 400;
		}
	}
	else if ((preg_match("'://'", GedcomConfig::$MEDIA_DIRECTORY)>0)||($media_controller->media->fileobj->f_file_exists)) {
		if ($media_controller->media->fileobj->f_width > 0 && $media_controller->media->fileobj->f_height > 0) {
			$imgwidth = $media_controller->media->fileobj->f_width+50;
			$imgheight = $media_controller->media->fileobj->f_height + 50;
		}
	}
	MediaFS::DispImgLink($filename, $thumbnail, $media_controller->media->title, "", 0, 0, $imgwidth, $imgheight, $media_controller->media->fileobj->f_is_image, $media_controller->media->fileobj->f_file_exists);
	?>
	<?php if(GedcomConfig::$SHOW_COUNTER && !$media_controller->IsPrintPreview()) {
		print "\n<div class=\"PageCounter\">".GM_LANG_hit_count."&nbsp;".$hits."</div>\n";
	}?>
</div>
<?php	
$media_controller->PrintTabs();

PrintFooter(); 
?>