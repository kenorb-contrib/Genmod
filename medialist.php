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
 * @version $Id$
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
print '<div class="center"><h3>'.GM_LANG_multi_title.'</h3></div>';

// A form for filtering the media items
?>
<form action="medialist.php" method="GET">
	<input type="hidden" name="action" value="filter" />
	<input type="hidden" name="search" value="yes" />
	<table class="list-table center">
	<tr>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>"><?php print GM_LANG_filter; ?></td>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>">&nbsp;<input id="filter" name="filter" value="<?php print $filter; ?>" /></td>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>"><input type="submit" value=" &gt; " />
	<?php PrintHelpLink("simple_filter_help","qm"); ?></td>
	</tr>
	</table>
<?php  

// Retrieve the media items
if (empty($filter)) $mediacontroller->RetrieveMedia(0, $start, $max);
else $mediacontroller->RetrieveFilterMedia($filter, $start, $max);

// Count the number of items in the medialist
$count = $max;
if ($count > $mediacontroller->mediainlist) $count = $mediacontroller->mediainlist;

print '<div align="center">'.$mediacontroller->totalmediaitems.' '.GM_LANG_media_found.'<br />';

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
print '</form>';

print '<table class="list_table">';
	print '<tr>';
	print '<td align="'.($TEXT_DIRECTION == "ltr"?"left":"right").'">';
	if ($start>0) {
		$newstart = $start-$max;
		if ($newstart<0) $newstart = 0;
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.htmlentities(GM_LANG_prev).'</a>';
	}
	print '</td><td align="'.($TEXT_DIRECTION == "ltr"?"right":"left").'">';
	if ($max < $mediacontroller->mediainlist) {
		$newstart = $start + $max;
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.htmlentities(GM_LANG_next).'</a>';
	}
	print '</td></tr>';
print '<tr>';

// -- print the array
$i=0;
foreach($mediacontroller->medialist as $index => $mediaitem) {
	print '<td class="list_value wrap width50">';
	print '<table class="'.$TEXT_DIRECTION.'"><tr><td valign="top" class="wrap">';

	if (USE_GREYBOX && $mediaitem->fileobj->f_is_image) print "<a href=\"".FilenameEncode($mediaitem->fileobj->f_main_file)."\" title=\"".$mediaitem->title."\" rel=\"gb_imageset[]\">";
	else print "<a href=\"#\" onclick=\"return openImage('".$mediaitem->fileobj->f_main_file."','".$mediaitem->fileobj->f_width."','".$mediaitem->fileobj->f_height."','".$mediaitem->fileobj->f_is_image."');\">";
	// NOTE: print the thumbnail
	print '<img src="'.$mediaitem->fileobj->f_thumb_file.'" border="0" align="left" class="thumbnail" alt="" />';
	if (GedcomConfig::$MEDIA_EXTERNAL || $mediaitem->fileobj->f_file_exists) print "</a>";
	print '</td><td class="list_value wrap width100" style="border: none;">';

	if (!GedcomConfig::$MEDIA_EXTERNAL && !$mediaitem->fileobj->f_file_exists);
//	else print '<a href="#" onclick="return openImage(\''.urlencode($mediaitem->fileobj->f_file).'\','.$mediaitem->fileobj->f_width.','.$mediaitem->fileobj->f_height.');">';
	else print "<a href=\"mediadetail.php?mid=".$mediaitem->xref."\">";

	if ($mediaitem->title==$mediaitem->filename) print '<b>&lrm;'.$mediaitem->title.'</b>';
	else if ($mediaitem->title != "") print '<b>'.PrintReady($mediaitem->title).'</b>';
	else print '<b>'.PrintReady($mediaitem->filename).'</b>';
	
	if (!GedcomConfig::$MEDIA_EXTERNAL && !$mediaitem->fileobj->f_file_exists);
	else print '</a>';
	$indiexists = false;
	$famexists = false;
	$sourexists = false;
	foreach($mediaitem->indilist as $key => $indi) {
		print "<br /><a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\">".GM_LANG_view_person.": ".$indi->name.($indi->addname == "" ? "" : " - ".$indi->addname).$indi->addxref."</a>";
		$indiexists = true;
	}
	if ($indiexists) print "<br />";
	foreach($mediaitem->famlist as $key => $family) {
		print "<br /><a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">".GM_LANG_view_family.": ".$family->descriptor.$family->addxref."</a>";
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
	}
	if (is_null($mediaitem->filename) || $mediaitem->filename == "") print '<br /><span class="error">'.GM_LANG_file_empty.' '.$mediaitem->filename.'</span>';
	else if (!strstr($mediaitem->filename, "://") && !$mediaitem->fileobj->f_file_exists) print '<br /><span class="error">'.GM_LANG_file_not_found.'<br />'.$mediaitem->filename.'</span>';
	
	print '<br /><br /><div class="indent wrap width95">';
	FactFunctions::PrintFactNotes($mediaitem, $mediaitem->level+1);
	
	print '</div>';
	if (!is_null($mediaitem->filename) && $mediaitem->filename != "") print "<span class=\"label\"><br />".GM_LANG_filename." : </span> <span class=\"field\" style=\"direction: ltr;\">".$mediaitem->filename."</span>";
	if ($mediaitem->fileobj->f_mimedescr != "") print '<span class="label"><br />'.GM_LANG_media_format.': </span> <span class="field" style="direction: ltr;">'.$mediaitem->fileobj->f_mimedescr."</span>";
	if ($mediaitem->fileobj->f_is_image && $mediaitem->fileobj->f_width > 0) print '<span class="label"><br />'.GM_LANG_image_size.': </span> <span class="field" style="direction: ltr;">'.$mediaitem->fileobj->f_width.($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ").$mediaitem->fileobj->f_height.'</span>';
	if ($mediaitem->fileobj->f_file_size > 0) print '<span class="label"><br />'.GM_LANG_media_file_size.': </span> <span class="field" style="direction: ltr;">'.GetFileSize($mediaitem->fileobj->f_file_size).'</span>';
	
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
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.GM_LANG_prev.'</a>';
	}
	print '</td><td align="'.($TEXT_DIRECTION == "ltr"?"right":"left").'">';
	if ($max < $mediacontroller->mediainlist) {
		$newstart = $start + $max;
		print '<a href="medialist.php?filter='.$filter.'&amp;search=no&amp;start='.$newstart.'&amp;max='.$max.'">'.GM_LANG_next.'</a>';
	}
	print '</td></tr>';
print "</table><br />";
print "</div>";
PrintFooter();
?>
