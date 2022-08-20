<?php
/**
 * Displays a list of the multimedia objects
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
 * @subpackage Lists
 * @version $Id: medialist.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($level)) $level=0;
if (!isset($action)) $action="";
if (!isset($filter)) $filter="";
if (!isset($search)) $search="no";
if (!isset($start)) $start = 0;
if (!isset($max)) $max = 20;
if (!isset($media)) {
	$mediacontroller = new MediaListController();
}

// Header for the page
PrintHeader(GM_LANG_multi_title);
print "<div id=\"MediaListPage\">";
print '<div class="PageTitleName">'.GM_LANG_multi_title.'</div>';

// Retrieve the media items
if (empty($filter)) $mediacontroller->RetrieveMedia(0, $start, $max);
else $mediacontroller->RetrieveFilterMedia($filter, $start, $max);

// Count the number of items in the medialist
$count = $max;
if ($count > $mediacontroller->mediainlist) $count = $mediacontroller->mediainlist;

// A form for filtering the media items
?>
<form action="medialist.php" method="get">
	<input type="hidden" name="action" value="filter" />
	<input type="hidden" name="search" value="yes" />
	<table class="NavBlockTable">
		<tr>
			<td colspan="3" class="NavBlockHeader"><?php print GM_LANG_options; ?></td>
		</tr>
		<tr>
			<td class="NavBlockLabel"><?php print GM_LANG_filter; ?></td>
			
			<td class="NavBlockField"><input id="filter" name="filter" value="<?php print $filter; ?>" /></td>
			<td class="NavBlockLabel">
			<?php
			// Dropdown selector for number of items to show
			if ($mediacontroller->totalmediaitems > 0) {
				print GM_LANG_medialist_show.' <select name="max" onchange="javascript:submit();">';
				for ($i=1;($i<=20&&$i-1<ceil($mediacontroller->totalmediaitems/10));$i++) {
					print '<option value="'.($i*10).'" ';
					if ($i*10==$max) print 'selected="selected" ';
					print ' >'.($i*10).'</option>';
				}
				print '</select> '.GM_LANG_per_page;
			}
			?>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="NavBlockLabel"><?php print $mediacontroller->totalmediaitems.' '.GM_LANG_media_found;?></td>
		</tr>
		<tr>
			<td colspan="3" class="NavBlockFooter <?php print $TEXT_DIRECTION; ?>"><input type="submit" value=" &gt; " /><?php PrintHelpLink("simple_filter_help","qm"); ?></td>
		</tr>
	</table>
<?php  
print '</form>';

print "<div class=\"MediaListArrowContainer\">";
if ($start>0) {
	print "<div class=\"MediaListLeftArrow\">";
	$newstart = $start-$max;
	if ($newstart<0) $newstart = 0;
	print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.GM_LANG_prev.'</a></div>';
}
if ($max < $mediacontroller->mediainlist) {
	$newstart = $start + $max;
	print "<div class=\"MediaListRightArrow\">";
	print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.GM_LANG_next.'</a></div>';
}
print "</div>";

// -- print the array
print "<div class=\"MediaListResult\">";
$i=0;
print "<table class=\"ListTable MediaListTable\"><tr>";
foreach($mediacontroller->medialist as $index => $mediaitem) {
	
	print "<td class=\"ListTableContent MediaListTableContent\">";
	print "<table class=\"MediaListObjectTable\">";
	print "<tr><td class=\"MediaListImage\">";
	MediaFS::DispImgLink($mediaitem->fileobj->f_main_file, $mediaitem->fileobj->f_thumb_file, $mediaitem->title, "", 0, 0, $mediaitem->fileobj->f_width, $mediaitem->fileobj->f_height, $mediaitem->fileobj->f_is_image, $mediaitem->fileobj->f_file_exists);
	print "</td>";

	print "<td class=\"MediaListText\">";
	
	// Print the title/link to the media object
	if (!GedcomConfig::$MEDIA_EXTERNAL && !$mediaitem->fileobj->f_file_exists);
	else print "<a href=\"mediadetail.php?mid=".$mediaitem->xref."\">";

	if ($mediaitem->title == $mediaitem->filename) print "<b>&lrm;".$mediaitem->title."</b>";
	else if ($mediaitem->title != "") print "<b>".PrintReady($mediaitem->title)."</b>";
	else print "<b>".PrintReady($mediaitem->filename)."</b>";
	
	if (!GedcomConfig::$MEDIA_EXTERNAL && !$mediaitem->fileobj->f_file_exists);
	else print "</a>";
	
	// Print the objects that link to this item
	$indiexists = false;
	$famexists = false;
	$sourexists = false;
	$repoexists = false;
	foreach($mediaitem->indilist as $key => $indi) {
		print "<br /><a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\">".GM_LANG_view_person.": ".$indi->name.($indi->addname == "" ? "" : " - ".$indi->addname).$indi->addxref."</a>";
		$indiexists = true;
	}
	if ($indiexists) print "<br />";
	foreach($mediaitem->famlist as $key => $family) {
		print "<br /><a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">".GM_LANG_view_family.": ".$family->name.$family->addxref."</a>";
		$famexists = true;
	}
	if ($famexists) print "<br />";
	foreach($mediaitem->sourcelist as $key => $source) {
		print "<br /><a href=\"source.php?sid=".$source->xref."&amp;gedid=".$source->gedcomid."\">".GM_LANG_view_source.": ".$source->descriptor.$source->addxref."</a>";
		$sourexists = true;
	}
	if ($sourexists) print "<br />";
	foreach($mediaitem->repolist as $key => $repo) {
		print "<br /><a href=\"repo.php?rid=".$repo->xref."&amp;gedid=".$repo->gedcomid."\">".GM_LANG_view_repo.": ".$repo->descriptor.$repo->addxref."</a>";
		$repoexists = true;
	}
	if ($repoexists) print "<br />";
	print "<br />";
	if (is_null($mediaitem->filename) || $mediaitem->filename == "") print '<br /><span class="Error">'.GM_LANG_file_empty.' '.$mediaitem->filename.'</span>';
	else if (!strstr($mediaitem->filename, "://") && !$mediaitem->fileobj->f_file_exists) print '<br /><span class="Error">'.GM_LANG_file_not_found.'<br />'.$mediaitem->filename.'</span>';
	
	if (FactFunctions::PrintFactNotes($mediaitem, $mediaitem->level+1)) print "<br />";
	
	if (!is_null($mediaitem->filename) && $mediaitem->filename != "") print "<span class=\"FactDetailLabel\"><br />".GM_LANG_filename." : </span> <span class=\"FactDetailField\" style=\"direction: ltr;\">".$mediaitem->filename."</span>";
	if ($mediaitem->fileobj->f_mimedescr != "") print '<span class="FactDetailLabel"><br />'.GM_LANG_media_format.': </span> <span class="FactDetailField" style="direction: ltr;">'.$mediaitem->fileobj->f_mimedescr."</span>";
	if ($mediaitem->fileobj->f_is_image && $mediaitem->fileobj->f_width > 0) print '<span class="FactDetailLabel"><br />'.GM_LANG_image_size.': </span> <span class="FactDetailField" style="direction: ltr;">'.$mediaitem->fileobj->f_width.($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ").$mediaitem->fileobj->f_height.'</span>';
	if ($mediaitem->fileobj->f_file_size > 0) print '<span class="FactDetailLabel"><br />'.GM_LANG_media_file_size.': </span> <span class="FactDetailField" style="direction: ltr;">'.GetFileSize($mediaitem->fileobj->f_file_size).'</span>';
	print "</td></tr></table>";
	print "</td>";
	
	if ($i%2 == 1 && $i < ($count-1)) print "</tr><tr>";
	$i++;
	if ($i == $count) break;
}
// Print an extra empty cell if the numer is odd.
if ($i%2) print "<td class=\"ListTableContent MediaListTableContent\"&nbsp;</td>";
print "</tr>";
print "</table></div>";

// NOTE: print the next and previous links
if ($start>0) {
	print "<div class=\"MediaListLeftArrow\">";
	$newstart = $start-$max;
	if ($newstart<0) $newstart = 0;
	print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.GM_LANG_prev.'</a></div>';
}
if ($max < $mediacontroller->mediainlist) {
	$newstart = $start + $max;
	print "<div class=\"MediaListRightArrow\">";
	print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.GM_LANG_next.'</a></div>';
}
print "</div>";
PrintFooter();
?>