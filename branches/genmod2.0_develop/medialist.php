<?php
/**
 * Displays a list of the multimedia objects
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
 * @subpackage Lists
 * @version $Id: medialist.php,v 1.27 2009/03/25 16:53:52 sjouke Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * @global boolean $MEDIA_EXTERNAL
*/
global $MEDIA_EXTERNAL;

if (!isset($level)) $level=0;
if (!isset($action)) $action="";
if (!isset($filter)) $filter="";
if (!isset($search)) $search="no";
if (!isset($start)) $start = 0;
if (!isset($max)) $max = 20;
if (!isset($media)) {
	$media = new Media();
}

// Header for the page
print_header($gm_lang["multi_title"]);
print '<div class="center"><h3>'.$gm_lang["multi_title"].'</h3></div>';

// A form for filtering the media items
?>
<form action="medialist.php" method="GET">
	<input type="hidden" name="action" value="filter" />
	<input type="hidden" name="search" value="yes" />
	<table class="list-table center">
	<tr>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>"><?php print $gm_lang["filter"]; ?></td>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>">&nbsp;<input id="filter" name="filter" value="<?php print $filter; ?>" /></td>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>"><input type="submit" value=" &gt; " />
	<?php print_help_link("simple_filter_help","qm"); ?></td>
	</tr>
	</table>
</form>
<?php  

// Retrieve the media items
if (empty($filter)) $media->RetrieveMedia(0, $start, $max);
else $media->RetrieveFilterMedia($filter, $start, $max);

// Count the number of items in the medialist
$count = $max;
if ($count > $media->mediainlist) $count = $media->mediainlist;

print '<div align="center">'.$media->CountMediaItems().' '.$gm_lang["media_found"].'<br />';

// Dropdown selector for number of items to show
if ($media->totalmediaitems > 0) {
	print '<form action="'.$SCRIPT_NAME.'" method="get" > '.$gm_lang["medialist_show"].' <select name="max" onchange="javascript:submit();">';
	for ($i=1;($i<=20&&$i-1<ceil($media->totalmediaitems/10));$i++) {
		print '<option value="'.($i*10).'" ';
		if ($i*10==$max) print 'selected="selected" ';
		print ' >'.($i*10).'</option>';
	}
	print '</select> '.$gm_lang["per_page"];
	print '</form>';
}

print '<table class="list_table">';
	print '<tr>';
	print '<td align="'.($TEXT_DIRECTION == "ltr"?"left":"right").'">';
	if ($start>0) {
		$newstart = $start-$max;
		if ($newstart<0) $newstart = 0;
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.htmlentities($gm_lang["prev"]).'</a>';
	}
	print '</td><td align="'.($TEXT_DIRECTION == "ltr"?"right":"left").'">';
	if ($max <= $media->mediainlist) {
		$newstart = $start + $max;
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.htmlentities($gm_lang["next"]).'</a>';
	}
	print '</td></tr>';
print '<tr>';

// -- print the array
$i=0;
foreach($media->medialist as $index => $mediaitem) {
	print '<td class="list_value wrap width50">';
	print '<table class="'.$TEXT_DIRECTION.'"><tr><td valign="top" class="wrap">';

	if ($USE_GREYBOX && $mediaitem->m_fileobj->f_is_image) print "<a href=\"".FilenameEncode($mediaitem->m_fileobj->f_main_file)."\" title=\"".$mediaitem->m_titl."\" rel=\"gb_imageset[]\">";
	else print "<a href=\"#\" onclick=\"return openImage('".$mediaitem->m_fileobj->f_main_file."','".$mediaitem->m_fileobj->f_width."','".$mediaitem->m_fileobj->f_height."','".$mediaitem->m_fileobj->f_is_image."');\">";
	// NOTE: print the thumbnail
	print '<img src="'.$mediaitem->m_fileobj->f_thumb_file.'" border="0" align="left" class="thumbnail" alt="" />';
	if ($MEDIA_EXTERNAL || $mediaitem->m_fileobj->f_file_exists) print "</a>";
	print '</td><td class="list_value wrap width100" style="border: none;">';

	if (!$MEDIA_EXTERNAL && !$mediaitem->m_fileobj->f_file_exists);
//	else print '<a href="#" onclick="return openImage(\''.urlencode($mediaitem->m_fileobj->f_file).'\','.$mediaitem->m_fileobj->f_width.','.$mediaitem->m_fileobj->f_height.');">';
	else print "<a href=\"mediadetail.php?mid=".$mediaitem->m_media."\">";

	if ($mediaitem->m_titl==$mediaitem->m_file) print '<b>&lrm;'.$mediaitem->m_titl.'</b>';
	else if ($mediaitem->m_titl != "") print '<b>'.PrintReady($mediaitem->m_titl).'</b>';
	else print '<b>'.PrintReady($mediaitem->m_file).'</b>';
	
	if (!$MEDIA_EXTERNAL && !$mediaitem->m_fileobj->f_file_exists);
	else print '</a>';
	if (count($mediaitem->links) != 0) {
		$indiexists = 0;
		$famexists = 0;
		foreach($mediaitem->links as $id => $type) {
			if ($type=="INDI") {
				print '<br /><a href="individual.php?pid='.$id.'">'.$gm_lang["view_person"].' - '.PrintReady(GetPersonName($id)).'</a>';
				$indiexists = 1;
			}
			if ($type=="FAM") {
				if ($indiexists && !$famexists) print "<br />";
				$famexists = 1;
				print '<br /><a href="family.php?famid='.$id.'">'.$gm_lang["view_family"].' - '.PrintReady(GetFamilyDescriptor($id)).'</a>';
			}
			if ($type=="SOUR") {
				if ($indiexists || $famexists) {
					print "<br />";
					$indiexists = 0;
					$famexists = 0;
				}
				print '<br /><a href="source.php?sid='.$id.'">'.$gm_lang["view_source"].' - '.PrintReady(GetSourceDescriptor($id)).'</a>';
			}
		}
	}
	if (empty($mediaitem->m_file)) print '<br /><span class="error">'.$gm_lang["file_empty"].' '.$mediaitem->m_file.'</span>';
	else if ((!strstr($mediaitem->m_file, "://")) && (!$mediaitem->m_fileobj->f_file_exists)) {
		print '<br /><span class="error">'.$gm_lang["file_not_found"].'<br />'.$mediaitem->m_file.'</span>';
	}
	print '<br /><br /><div class="indent wrap width95">';
	print_fact_notes($mediaitem->m_gedrec, $mediaitem->m_level+1);
	
	print '</div>';
	if (!empty($mediaitem->m_fileobj->f_mimedescr)) print '<span class="label"><br />'.$gm_lang["media_format"].': </span> <span class="field" style="direction: ltr;">'.$mediaitem->m_fileobj->f_mimedescr."</span>";
	if ($mediaitem->m_fileobj->f_is_image && $mediaitem->m_fileobj->f_width > 0) print '<span class="label"><br />'.$gm_lang["image_size"].': </span> <span class="field" style="direction: ltr;">'.$mediaitem->m_fileobj->f_width.($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ").$mediaitem->m_fileobj->f_height.'</span>';
	if ($mediaitem->m_fileobj->f_file_size > 0) print '<span class="label"><br />'.$gm_lang["media_file_size"].': </span> <span class="field" style="direction: ltr;">'.GetFileSize($mediaitem->m_fileobj->f_file_size).'</span>';
	
	print '</td></tr></table>';
	print '</td>';
	if ($i%2 == 1 && $i < ($count-1)) print "</tr><tr>";
	$i++;
	if ($i == $count) break;
}
print "</tr>";
// NOTE: print the next and previous links
	print '<tr>';
	print '<td align="'.($TEXT_DIRECTION == "ltr"?"left":"right").'">';
	if ($start>0) {
		$newstart = $start-$max;
		if ($newstart<0) $newstart = 0;
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.$gm_lang["prev"].'</a>';
	}
	print '</td><td align="'.($TEXT_DIRECTION == "ltr"?"right":"left").'">';
	if ($max <= $media->mediainlist) {
		$newstart = $start + $max;
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.$gm_lang["next"].'</a>';
	}
	print '</td></tr>';
print "</table><br />";
print "</div>";
print_footer();
?>
