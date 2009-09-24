<?php
/**
 * Displays the details about a media record. Also shows how many people and families
 * reference this media.
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
 * @subpackage Display
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the media controller
*/
$media_controller = new mediaController();

print_header($media_controller->pagetitle);

$media_controller->CheckNoResult($gm_lang["media_not_found"]);

$media_controller->CheckPrivate();

$media_controller->CheckRawEdited();

?>
<div id="show_changes"></div>
<?php $media_controller->PrintDetailJS(); ?>
<table class="list_table">
	<tr>
		<td colspan="2">
		<?php
		
		// Print the title
		print "<span class=\"name_head\">".PrintReady($media_controller->media->title.$media_controller->media->addxref);
		print "</span><br />";
		
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
		else if ((preg_match("'://'", $MEDIA_DIRECTORY)>0)||($media_controller->media->fileobj->f_file_exists)) {
			if ($media_controller->media->fileobj->f_width > 0 && $media_controller->media->fileobj->f_height > 0) {
				$imgwidth = $media_controller->media->fileobj->f_width+50;
				$imgheight = $media_controller->media->fileobj->f_height + 50;
			}
		}
		if (preg_match("'://'", $thumbnail)||(preg_match("'://'", $MEDIA_DIRECTORY)>0)||($media_controller->media->fileobj->f_file_exists)) {
			if ($USE_GREYBOX && $media_controller->media->fileobj->f_is_image) print "<a href=\"".FilenameEncode($filename)."\" title=\"".PrintReady($media_controller->media->title)."\" rel=\"gb_imageset[]\">";
			else {
				print "<a href=\"#\" onclick=\"return openImage('".$filename."',".$imgwidth.", ".$imgheight.", ".$media_controller->media->fileobj->f_is_image.");\">";
			}
			print "<img src=\"".$thumbnail."\" border=\"0\" align=\"" . ($TEXT_DIRECTION== "rtl"?"right": "left") . "\" class=\"thumbnail\" alt=\"\" /></a>";
		}
		?>
		<?php if($SHOW_COUNTER) {
			print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
		}?>
		</td>
	</tr>
</table>
<?php	
$media_controller->PrintTabs();

print_footer(); 
?>