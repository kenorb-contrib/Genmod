<?php
/**
 * Displays a list of the multimedia objects
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
 * @subpackage Lists
 * @version $Id: medialist.php,v 1.8 2006/01/10 01:10:24 roland-d Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * @global boolean $MEDIA_EXTERNAL
*/
global $MEDIA_EXTERNAL, $MEDIATYPE;
function mediasort($a, $b) {
	return strnatcasecmp($a["TITL"], $b["TITL"]);
}

if (!isset($level)) $level=0;
if (!isset($action)) $action="";
if (!isset($filter)) $filter="";
if (!isset($search)) $search="yes";
if (!isset($medialist) && !isset($_SESSION["medialist"])) $medialist = array();
print_header($gm_lang["multi_title"]);
print "\n\t<div class=\"center\"><h2>".$gm_lang["multi_title"]."</h2></div>\n\t";

if ($search == "yes") {
	// -- array of names
	$foundlist = array();
	get_medialist();
	
	//-- sort the media by title
	usort($medialist, "mediasort");
	
	//-- remove all private media objects
	$newmedialist = array();
	foreach($medialist as $indexval => $media) {
	     $disp = true;
	     $links = $media["LINKS"];
		if (count($links) != 0) {
	        foreach($links as $id=>$type) {
			   $disp = $disp && displayDetailsByID($id, $type);
	        }
	        if ($disp) $newmedialist[] = $media;
		}
		else $newmedialist[] = $media;
	}
	$medialist = $newmedialist;
	$_SESSION["medialist"] = $medialist;
}
else $medialist = $_SESSION["medialist"];
// A form for filtering the media items
?>
<form action="medialist.php" method="GET">
	<input type="hidden" name="action" value="filter" />
	<input type="hidden" name="search" value="yes" />
	<table class="list-table center">
	<tr>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>"><?php print $gm_lang["filter"]; ?></td>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>">&nbsp;<input id="filter" name="filter" value="<?php print $filter; ?>"/></td>
	<td class="list-label <?php print $TEXT_DIRECTION; ?>"><input type="submit" value=" &gt; "/>
	<?php print_help_link("simple_filter_help","qm"); ?></td>
	</tr>
	</table>
</form>
<?php  
      
if ($action=="filter") {
	if (strlen($filter) >= 1) {
		foreach($medialist as $key => $value) {
			if ((stristr($value["FILE"], $filter) === false) && (stristr($value["TITL"], $filter) === false)) {
				 $links = $value["LINKS"];
				 if (count($links) != 0){
					 $person = false;
					 $family = false;
					 $source = false;
					 foreach($links as $id=>$type) {
						 if ($type=="INDI") {
							 if (!stristr(get_person_name($id), $filter)) $person = false;
							 else {
								 $person = true;
								 break;
							 }
						 }
						 if ($type=="FAM") {
							 if (!stristr(get_family_descriptor($id), $filter)) $family = false;
							 else {
								 $family = true;
								 break;
							 }
						 }
						    if ($type=="SOUR") {
							    if (!stristr(get_source_descriptor($id), $filter)) $source = false;
							    else {
								    $source = true;
								    break;
							    }
						    }
					 }
					if ($person == false && $family == false && $source == false) unset($medialist[$key]);
				}
			}
		}
		usort($medialist, "mediasort"); // Reset numbering of medialist array
		$_SESSION["medialist"] = $medialist;
	}
}
// Count the number of items in the medialist
$ct = count($medialist);
if (!isset($start)) $start = 0;
if (!isset($max)) $max = 20;
$count = $max;
if ($start+$count > $ct) $count = $ct-$start;

print "\n\t<div align=\"center\">$ct ".$gm_lang["media_found"]." <br />";
if ($ct > 0) {
	print "<form action=\"$SCRIPT_NAME\" method=\"get\" > ".$gm_lang["medialist_show"]." <select name=\"max\" onchange=\"javascript:submit();\">";
	for ($i=1;($i<=20&&$i-1<ceil($ct/10));$i++) {
		print "<option value=\"".($i*10)."\" ";
		if ($i*10==$max) print "selected=\"selected\" ";
		print " >".($i*10)."</option>";
	}
	print "</select> ".$gm_lang["per_page"];
	print "</form>";
}

print"\n\t<table class=\"list_table\">\n";
if ($ct>$max) {
	print "\n<tr>\n";
	print "<td align=\"" . ($TEXT_DIRECTION == "ltr"?"left":"right") . "\">";
	if ($start>0) {
		$newstart = $start-$max;
		if ($start<0) $start = 0;
		print "<a href=\"medialist.php?filter=$filter&amp;search=no&amp;start=$newstart&amp;max=$max\">".$gm_lang["prev"]."</a>\n";
	}
	print "</td><td align=\"" . ($TEXT_DIRECTION == "ltr"?"right":"left") . "\">";
	if ($start+$max < $ct) {
		$newstart = $start+$count;
		if ($start<0) $start = 0;
		print "<a href=\"medialist.php?filter=$filter&amp;search=no&amp;start=$newstart&amp;max=$max\">".$gm_lang["next"]."</a>\n";
	}
	print "</td></tr>\n";
}
print"\t\t<tr>\n\t\t";
// -- print the array
for($i=0; $i<$count; $i++) {
	$value = $medialist[$start+$i];
	if ($MEDIA_EXTERNAL && (strstr($value["FILE"], "://"))) {
		$path_end=substr($value["FILE"], strlen($value["FILE"])-5);
		$type=strtolower(substr($path_end, strpos($path_end, ".")+1));
		if (in_array($type, $MEDIATYPE)){
			$imgwidth = 400;
			$imgheight = 500;
		} 
		else {
			$imgwidth = 800;
			$imgheight = 400;
		}
	}
	else if (file_exists(filename_decode($value["FILE"]))) {
		$imgsize = getimagesize(filename_decode($value["FILE"]));
		$imgwidth = $imgsize[0]+50;
		$imgheight = $imgsize[1]+50;
	}
	else {
		$imgwidth=300;
		$imgheight=200;
	}
	print "\n\t\t\t<td class=\"list_value_wrap\" width=\"50%\">";
	print "<table class=\"$TEXT_DIRECTION\">\n\t<tr>\n\t\t<td valign=\"top\" style=\"white-space: normal;\">";
	
	if (!$MEDIA_EXTERNAL);
	else print "<a href=\"#\" onclick=\"return openImage('".urlencode($value["FILE"])."',$imgwidth, $imgheight);\">";
	// NOTE: Print the thumbnail
	print "<img src=\"".$value["THUMB"]."\" border=\"0\" align=\"left\" class=\"thumbnail\" alt=\"\" />";
	if (!$MEDIA_EXTERNAL);
	else print "</a>";
	print "</td>\n\t\t<td class=\"list_value_wrap\" style=\"border: none;\" width=\"100%\">";
	
	if (!$MEDIA_EXTERNAL);
	else print "<a href=\"#\" onclick=\"return openImage('".urlencode($value["FILE"])."',$imgwidth, $imgheight);\">";
	
	if ($value["TITL"]==$value["FILE"]) print "<b>&lrm;".$value["TITL"]."</b>";
	else if (trim($value["TITL"]) != "") print "<b>".PrintReady($value["TITL"])."</b>";
	else print "<b>".PrintReady($value["FILE"])."</b>";
	
	if (!$MEDIA_EXTERNAL);
	else print "</a>";
	
	$links = $value["LINKS"];
	if (count($links) != 0){
	$indiexists = 0;
	$famexists = 0;
	foreach($links as $id=>$type) {
		if (($type=="INDI")&&(displayDetailsByID($id))) {
			print " <br /><a href=\"individual.php?pid=".$id."\"> ".$gm_lang["view_person"]." - ".PrintReady(get_person_name($id))."</a>";
			$indiexists = 1;
		}
		if ($type=="FAM") {
			if ($indiexists && !$famexists) print "<br />";
			$famexists = 1;
			print "<br /> <a href=\"family.php?famid=".$id."\"> ".$gm_lang["view_family"]." - ".PrintReady(get_family_descriptor($id))."</a>";
		}
		if ($type=="SOUR") {
			if ($indiexists || $famexists) {
				print "<br />";
				$indiexists = 0;
				$famexists = 0;
			}
			print "<br /> <a href=\"source.php?sid=".$id."\"> ".$gm_lang["view_source"]." - ".PrintReady(get_source_descriptor($id))."</a>";
		  }
		}
	}
	$value["FILE"] = filename_decode($value["FILE"]);
	if (empty($value["FILE"])) print "<br /><span class=\"error\">".$gm_lang["file_empty"]." ".$value["FILE"]."</span>";
	else if ((!strstr($value["FILE"], "://")) && (!file_exists(filename_decode($value["FILE"])))) {
		print "<br /><span class=\"error\">".$gm_lang["file_not_found"]."<br />".$value["FILE"]."</span>";
	}
	print "<br /><br /><div class=\"indent\" style=\"white-space: normal; width: 95%;\">";
	print_fact_notes($value["GEDCOM"], $value["LEVEL"]+1);
	
	print "</div>";
	if (file_exists(filename_decode($value["FILE"]))) {
		$imageTypes = array("","GIF", "JPG", "PNG", "SWF", "PSD", "BMP", "TIFF", "TIFF", "JPC", "JP2", "JPX", "JB2", "SWC", "IFF", "WBMP", "XBM");
		if(!empty($imgsize[2])){
			print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["media_format"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $imageTypes[$imgsize[2]] . "</span>";
		} 
		else if(empty($imgsize[2])){
			$path_end=substr($value["FILE"], strlen($value["FILE"])-5);
			$imageType = strtoupper(substr($path_end, strpos($path_end, ".")+1));
			print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["media_format"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $imageType . "</span>";
		}
		
		if(!empty($imgsize[0]) && !empty($imgsize[1])){
			print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["image_size"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $imgsize[0] . ($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ") . $imgsize[1] . "</span>";
		}
		$fileSize = filesize(filename_decode($value["FILE"]));
		$sizeString = getfilesize($fileSize);
		print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["media_file_size"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $sizeString . "</span>";
	}
	print "</td></tr></table>\n";
	print "</td>";
	if ($i%2 == 1 && $i < ($count-1)) print "\n\t\t</tr>\n\t\t<tr>";
}
print "\n\t\t</tr>";
// NOTE: Print the next and previous links
if ($ct > $max) {
        print "\n<tr>\n";
        print "<td align=\"" . ($TEXT_DIRECTION == "ltr"?"left":"right") . "\">";
        if ($start>0) {
                $newstart = $start-$max;
                if ($start<0) $start = 0;
                print "<a href=\"medialist.php?filter=$filter&amp;search=no&amp;start=$newstart&amp;max=$max\">".$gm_lang["prev"]."</a>\n";
        }
        print "</td><td align=\"" . ($TEXT_DIRECTION == "ltr"?"right":"left") . "\">";
        if ($start+$max < $ct) {
                $newstart = $start+$count;
                if ($start<0) $start = 0;
                print "<a href=\"medialist.php?filter=$filter&amp;search=no&amp;start=$newstart&amp;max=$max\">".$gm_lang["next"]."</a>\n";
        }
        print "</td></tr>\n";
}
print "</table><br />";
print "\n</div>\n";
print_footer();
?>