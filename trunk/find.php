<?php
/**
 * Popup window that will allow a user to search for a family id, person id
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
 * @subpackage Display
 * @version $Id: find.php,v 1.13 2006/04/30 18:44:14 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($type)) $type = "indi";
if (!isset($filter)) $filter="";
else $filter = trim($filter);
if (!isset($callback)) $callback="paste_id";

// Variables for find media
if (!isset($create)) $create="";
if (!isset($media)) $media="";
if (!isset($embed)) $embed=false;
if (!isset($directory)) $directory = $MEDIA_DIRECTORY;
$badmedia = array(".","..","CVS","thumbs","index.php","MediaInfo.txt");
$showthumb= isset($showthumb);
if ($embed) check_media_db();
$thumbget = "";
if ($showthumb) {$thumbget = "&amp;showthumb=true";}

//-- force the thumbnail directory to have the same layout as the media directory
//-- Dots and slashes should be escaped for the preg_replace
$srch = "/".addcslashes($MEDIA_DIRECTORY,'/.')."/";
$repl = addcslashes($MEDIA_DIRECTORY."thumbs/",'/.');
$thumbdir = stripcslashes(preg_replace($srch, $repl, $directory));
if (!isset($level)) $level=0;

//-- prevent script from accessing an area outside of the media directory
//-- and keep level consistency
if (($level < 0) || ($level > $MEDIA_DIRECTORY_LEVELS)){
	$directory = $MEDIA_DIRECTORY;
	$level = 0;
} elseif (preg_match("'^$MEDIA_DIRECTORY'", $directory)==0){
	$directory = $MEDIA_DIRECTORY;
	$level = 0;
}
// End variables for find media

// Variables for Find Special Character
if (!isset($language_filter)) $language_filter="";
if (empty($language_filter)) {
	if (!empty($_SESSION["language_filter"])) $language_filter = $_SESSION["language_filter"];
	else $language_filter=$lang_short_cut[$LANGUAGE];
}
if (!isset($magnify)) $magnify=false;
require("includes/specialchars.php");

// End variables for Find Special Character

switch ($type) {
	case "indi" :
		print_simple_header($gm_lang["find_individual"]);
		break;
	case "fam" :
		print_simple_header($gm_lang["find_fam_list"]);
		break;
	case "media" :
		print_simple_header($gm_lang["find_media"]);
		$action="filter";
		break;
	case "object" :
		print_simple_header($gm_lang["find_media"]);
		$action="filter";
		break;
	case "place" :
		print_simple_header($gm_lang["find_place"]);
		$action="filter";		
		break;
	case "repo" :
		print_simple_header($gm_lang["repo_list"]);
		$action="filter";		
		break;
	case "source" :
		print_simple_header($gm_lang["find_source"]);
		$action="filter";
		break;
	case "specialchar" :
		print_simple_header($gm_lang["find_specialchar"]);
		$action="filter";		
		break;
}

?>
<script language="JavaScript" type="text/javascript">
<!--
	function pasteid(id, name) {
		window.opener.<?php print $callback; ?>(id);
		if (window.opener.pastename) window.opener.pastename(name);
		window.close();
	}

	var language_filter;
	function paste_char(selected_char,language_filter,magnify) {
		window.opener.paste_char(selected_char,language_filter,magnify);
		return false;
	}

	function setMagnify() {
		document.filterspecialchar.magnify.value = '<?PHP print !$magnify; ?>';
		document.filterspecialchar.submit();
	}

	function checknames(frm) {
		if (document.forms[0].subclick) button = document.forms[0].subclick.value;
		else button = "";
		if (frm.filter.value.length<2&button!="all") {
			alert("<?php print $gm_lang["search_more_chars"]?>");
			frm.filter.focus();
			return false;
		}
		if (button=="all") {
			frm.filter.value = "";
		}
		return true;
	}
//-->
</script>
<?php
$options = array();
$options["option"][]= "findindi";
$options["option"][]= "findfam";
$options["option"][]= "findmedia";
$options["option"][]= "findobject";
$options["option"][]= "findplace";
$options["option"][]= "findrepo";
$options["option"][]= "findsource";
$options["option"][]= "findspecialchar";
$options["form"][]= "formindi";
$options["form"][]= "formfam";
$options["form"][]= "formmedia";
$options["form"][]= "formobject";
$options["form"][]= "formplace";
$options["form"][]= "formrepo";
$options["form"][]= "formsource";
$options["form"][]= "formspecialchar";

global $TEXT_DIRECTION, $MULTI_MEDIA;
print "<div class=\"topbottombar width60\">";
// print "<table class=\"list_table $TEXT_DIRECTION center width60\">";
//print "<tr><td class=\"topbottombar width50\">"; // start column for find text header

switch ($type) {
	case "indi" :
		print $gm_lang["find_individual"];
		break;
	case "fam" :
		print $gm_lang["find_fam_list"];
		break;
	case "media" :
		print $gm_lang["find_media"];
		break;
	case "object" :
		print $gm_lang["find_media"];
		break;
	case "place" :
		print $gm_lang["find_place"];
		break;
	case "repo" :
		print $gm_lang["repo_list"];
		break;
	case "source" :
		print $gm_lang["find_source"];
		break;
	case "specialchar" :
		print $gm_lang["find_specialchar"];
		break;
}
print "</div>";

// NOTE: Show indi and hide the rest
if ($type == "indi") {
	print "<div class=\"width60 center shade1\">";
	print "<form name=\"filterindi\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"indi\" />";
	print "<label class=\"width10\" style=\"padding: 5px;\">";
	print $gm_lang["name_contains"]."</label> <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print $filter;
	print "\" />";
	print "</div>";
	print "<div class=\"width60 center\" style=\"padding: 5px;\">";
	print "<input type=\"submit\" value=\"".$gm_lang["filter"]."\" /><br />";
	print "</form></div>";
}		

// Show fam and hide the rest
else if ($type == "fam") {
	print "<div align=\"center\">";
	print "<form name=\"filterfam\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"fam\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["name_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print $filter;
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\" value=\"".$gm_lang["filter"]."\" /><br />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show media and hide the rest
else if ($type == "media" && $MULTI_MEDIA) {
	print "<div align=\"center\">";
	print "<form name=\"filtermedia\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"embed\" value=\"".$embed."\" />";
	print "<input type=\"hidden\" name=\"directory\" value=\"".$directory."\" />";
	print "<input type=\"hidden\" name=\"thumbdir\" value=\"".$thumbdir."\" />";
	print "<input type=\"hidden\" name=\"level\" value=\"".$level."\" />";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"media\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked		
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["media_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print $filter;
	print "\" />";
	print_help_link("simple_filter_help","qm");
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"checkbox\" name=\"showthumb\" value=\"true\"";
	if( $showthumb) print "checked=\"checked\"";
	print "onclick=\"javascript: this.form.submit();\" />".$gm_lang["show_thumbnail"];
	print_help_link("show_thumb_help","qm");
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\" name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\" name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

else	if ($type == "object" && $MULTI_MEDIA) {
	print "<div class=\"center\">";
	print "<form name=\"filterobje\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"object\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked		
	print "<table class=\"list_table $TEXT_DIRECTION width30\">";
	print "<tr><td class=\"list_label width10\" style=\"padding: 5px;\">";
	print_help_link("simple_filter_help","qm");
	print $gm_lang["media_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print $filter;
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label width10\" style=\"padding: 5px;\">";
	print "<input type=\"submit\" name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\" name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show place and hide the rest
else if ($type == "place") {
	print "<div align=\"center\">";
	print "<form name=\"filterplace\" method=\"post\"  onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"place\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked				
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["place_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print $filter;
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\" name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\" name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show repo and hide the rest
else if ($type == "repo" && $SHOW_SOURCES>=getUserAccessLevel($gm_username)) {
	print "<div align=\"center\">";
	print "<form name=\"filterrepo\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"repo\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked				
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["repo_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print $filter;
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\" name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\" name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show source and hide the rest
else if ($type == "source" && $SHOW_SOURCES>=getUserAccessLevel($gm_username)) {
	print "<div align=\"center\">";
	print "<form name=\"filtersource\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"source\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["source_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print $filter;
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\" name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\" name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show specialchar and hide the rest
else if ($type == "specialchar") {
	print "<div align=\"center\">";
	print "<form name=\"filterspecialchar\" method=\"post\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"specialchar\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"magnify\" value=\"".$magnify."\" />";
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<select id=\"language_filter\" name=\"language_filter\" onchange=\"submit();\">";
	print "\n\t<option value=\"\">".$gm_lang["change_lang"]."</option>";
	$language_options = "";
	foreach($specialchar_languages as $key=>$value) {
		$language_options.= "\n\t<option value=\"$key\">$value</option>";
	}
	$language_options = str_replace("\"$language_filter\"","\"$language_filter\" selected",$language_options);
	print $language_options;
	print "</select><br /><a href=\"#\" onclick=\"setMagnify()\">".$gm_lang["magnify"]."</a>";
	print "</td></tr></table>";
	print "</form></div>";
}
// end column for find options
print "<br />";
print "<div class=\"center\">";
print "<a href=\"#\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".$gm_lang["close_window"]."</a><br />\n";
print "</div>";

if ($action=="filter") {
	// Output Individual
	if ($type == "indi") {
		$oldged = $GEDCOM;
		// print "\n\t<table class=\"tabs_table $TEXT_DIRECTION center\" width=\"90%\">\n\t\t<tr>";
		$myindilist = search_indis_names($filter);
		$cti=count($myindilist);
		if ($cti>0) {
			PrintPersonList($myindilist, true);
			/**
			$curged = $GEDCOM;
			$printname = array();
			$names = preg_split("/[\s,]+/", $filter);
			print "<td class=\"list_value_wrap\"><ul>";
			foreach ($myindilist as $key => $value) {
				foreach($value["names"] as $indexval => $namearray) {
					foreach($names as $ni=>$name) {
						$found = true;
						if (preg_match("/".$name."/i", $namearray[0])==0) $found=false;
					}
					if ($found) $printname[] = array(sortable_name_from_name($namearray[0]), $key, get_gedcom_from_id($value["gedfile"]));
				}
			}
			uasort($printname, "itemsort");
			foreach($printname as $pkey => $pvalue) {
				$GEDCOM = $pvalue[2];
				if ($GEDCOM != $curged) {
					ReadPrivacy($GEDCOM);
					$curged = $GEDCOM;
				}
				print_list_person($pvalue[1], array(check_NN($pvalue[0]), $pvalue[2]), true);
				print "\n";
			}
			print "\n\t\t</ul></td>";
			$GEDCOM = $oldged;
			if ($GEDCOM != $curged) {
				ReadPrivacy($GEDCOM);
				$curged = $GEDCOM;
			}
			print "</tr>";
			if ($cti > 0) {
				print "<tr><td class=\"list_value\">".$gm_lang["total_indis"]." ".$cti;
				if (count($indi_private)>0) print "  (".$gm_lang["private"]." ".count($indi_private).")";
				if (count($indi_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($indi_hide);
				if (count($indi_private)>0 || count($indi_hide)>0) print_help_link("privacy_error_help", "qm");
				print "</td></tr>";
			}
			**/
		}
		else {
			print "<div class=\"center width60\">";
			print $gm_lang["no_results"];
			print "</div>";
		}
		// print "</table>";
	}

	// Output Family
	else if ($type == "fam") {
		$oldged = $GEDCOM;
		$myindilist = array();
		$myfamlist = array();
		$myfamlist2 = array();
		$famquery = array();
		
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION center\" width=\"90%\">\n\t\t<tr>";
		if (find_person_record($filter)) {
			$printname = search_fams_members($filter);
			$ctf = count($printname);
		}
		else {
			$myindilist = search_indis($filter);
			foreach($myindilist as $key1 => $myindi) {
				foreach($myindi["names"] as $key2 => $name) {
					if ((preg_match("/".$filter."/i", $name[2]) > 0)) {
						$famquery[] = array($key1, $GEDCOM);
						break;
					}
				}
			}
			$ctf=count($famquery);
			$printname = array();
			if ($ctf>0) {
				// Get the famrecs with hits on names from the family table
				$myfamlist = search_fams_names($famquery, "OR", true);
				// Get the famrecs with hits in the gedcom record from the family table
				$myfamlist2 = search_fams($filter, false, "OR", true);		
				$myfamlist = gm_array_merge($myfamlist, $myfamlist2);
				foreach ($myfamlist as $key => $found) {
					foreach ($found["name"] as $foundkey => $foundname) {
						if (stristr($foundname[$foundkey], $filter)) $found = true;
					}
					if ($found != true) unset ($myfamlist[$key]);
				}
				foreach ($myfamlist as $key => $value) {
					// lets see where the hit is
					foreach($value["name"] as $nkey => $famname) {
						$famsplit = preg_split("/(\s\+\s)/", trim($famname));
						if (preg_match("/".$filter."/i", $famsplit[0]) != 0) {
							$printname[]=array(check_NN($famname), $key, get_gedcom_from_id($value["gedfile"]));
							break;
						}
				    }
				}		
			}					
		}
		
		if ($ctf>0) {
			$curged = $GEDCOM;
			print "\n\t\t<td class=\"list_value_wrap $TEXT_DIRECTION\"><ul>";
			uasort($printname, "itemsort");
			foreach($printname as $pkey => $pvalue) {
				$GEDCOM = $pvalue[2];
				if ($GEDCOM != $curged) {
					ReadPrivacy($GEDCOM);
					$curged = $GEDCOM;
				}
				print_list_family($pvalue[1], array($pvalue[0], $pvalue[2]), true);
				print "\n";
			}
			print "\n\t\t</ul></td>";
			$GEDCOM = $oldged;
			if ($GEDCOM != $curged) {
				ReadPrivacy($GEDCOM);
				$curged = $GEDCOM;
			}
			print "</tr>\n";
			print "<tr><td class=\"list_label\">".$gm_lang["total_fams"]." ".$ctf;
			if (count($fam_private)>0) print "  (".$gm_lang["private"]." ".count($fam_private).")";
			if (count($fam_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($fam_hide);
			if (count($fam_private)>0 || count($fam_hide)>0) print_help_link("privacy_error_help", "qm");
			print "</tr></td>";
		}
		else {
			print "<td class=\"list_value_wrap\">";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table>";
	}

	// Output Media
	else if ($type == "media") {
		global $dirs;
		
		$medialist = get_medialist(false, $directory);
		print "<div align=\"center\">";
		print "\n\t<table class=\"tabs_table center $TEXT_DIRECTION width90\">\n\t\t";
		// Show link to previous folder		
		if ($level>0) {
			$levels = preg_split("'/'", $directory);
			$pdir = "";
			for($i=0; $i<count($levels)-2; $i++) $pdir.=$levels[$i]."/";
			$levels = preg_split("'/'", $thumbdir);
			$pthumb = "";
			for($i=0; $i<count($levels)-2; $i++) $pthumb.=$levels[$i]."/";
			$uplink = "<a href=\"find.php?embed=$embed&amp;directory=$pdir&amp;thumbdir=$pthumb&amp;level=".($level-1).$thumbget."&type=media\">&nbsp;&nbsp;&nbsp;&lt;-- $pdir&nbsp;&nbsp;&nbsp;</a><br />\n";
		}

		// Start of media directory table
		print "<table class=\"width30\">";
	
		// Tell the user where he is
		print "<tr><td class=\"list_value $TEXT_DIRECTION\" colspan=\"2\">".$gm_lang["current_dir"].substr($directory,0,-1)."</td></tr>";
		
		// display the directory list
		if (count($dirs) || $level) {
			sort($dirs);
			if ($level){
				print "<tr><td class=\"list_value $TEXT_DIRECTION\" colspan=\"2\">";
				print $uplink."</td></tr>";
			}
			print "<tr><td class=\"shade2 $TEXT_DIRECTION\" colspan=\"2\">";
			print "<a href=\"find.php?directory=$directory&amp;thumbdir=$directory&amp;level=".$level.$thumbget."&amp;external_links=http&amp;type=media\">External media</a>";
			print "</td></tr>";
			foreach ($dirs as $indexval => $dir) {
				print "<tr><td class=\"list_value $TEXT_DIRECTION\" width=\"45%\">";
				print "<a href=\"find.php?directory=$directory$dir/&thumbdir=$directory$dir/&level=".($level+1).$thumbget."&type=media\">$dir</a>";
				print "</td></tr>";
			}
		}
		print "<tr><td class=\"list_value $TEXT_DIRECTION\">";
		
		$applyfilter = ($filter != "");
		print "<br />";

		// display the images TODO x across if lots of files??
		if (count($medialist) > 0) {
			foreach ($medialist as $indexval => $media) {
				
				// Check if the media belongs to the current folder
				preg_match_all("/\//", $media["FILE"], $hits);
				$ct = count($hits[0]);
				
				if ($ct <= $level+1) {
					// simple filter to reduce the number of items to view
					if ($applyfilter) $isvalid = (strpos(str2lower($media["FILE"]),str2lower($filter)) !== false);
					else $isvalid = true;
					if ($isvalid) {
						print "<tr>";
						
						//-- thumbnail field
						if ($showthumb) {
							print "\n\t\t\t<td class=\"list_value $TEXT_DIRECTION\">";
							if (isset($media["THUMB"])) print "<a href=\"#\" onclick=\"return openImage('".preg_replace("/'/", "\'", urlencode($media["FILE"]))."','".($media["WIDTH"]+50)."','".($media["HEIGHT"]+50)."');\"><img src=\"".filename_encode($media["THUMB"])."\" border=\"0\" width=\"50\"></a>\n";
							else print "&nbsp;";
						}
						
						//-- name and size field
						print "\n\t\t\t<td class=\"list_value $TEXT_DIRECTION\">";
						if ($media["TITL"] != "") $title = "<b>".$media["TITL"]."</b> (".$media["XREF"].")";
						else $title = "";
						print PrintReady($title)."<br />";
						if (!$embed){
							print "<a href=\"#\" onclick=\"pasteid('".preg_replace("/'/", "\'", filename_encode($media["FILE"]))."');\">".filename_encode($media["FILE"])."</a> -- ";
						}
						else print "&nbsp;".$imag." -- ";
						print "<a href=\"#\" onclick=\"return openImage('".preg_replace("/'/", "\'", filename_encode($media["FILE"]))."','".($media["WIDTH"]+50)."','".($media["HEIGHT"]+50)."');\">".$gm_lang["view"]."</a><br />";
						if (!file_exists($media["FILE"]) && !stristr($media["FILE"], "://")) print filename_encode($media["FILE"])."<br /><span class=\"error\">".$gm_lang["file_not_exists"]."</span><br />";
						else if (!stristr($media["FILE"], "://")) {
							print "<br /><sub>&nbsp;&nbsp;".$gm_lang["image_size"]." -- ".$media["WIDTH"]."x".$media["HEIGHT"]."</sub><br />";
						}
						if ($media["LINKED"]) {
							print $gm_lang["media_linked"]."<br />";
							foreach ($media["LINKS"] as $indi => $type_record) {
								if ($type_record=="INDI") {
						            print " <br /><a href=\"individual.php?pid=".$indi."\"> ".$gm_lang["view_person"]." - ".PrintReady(get_person_name($indi))."</a>";
								}
								else if ($type_record=="FAM") {
						           	print "<br /> <a href=\"family.php?famid=".$indi."\"> ".$gm_lang["view_family"]." - ".PrintReady(get_family_descriptor($indi))."</a>";
								}
								else if ($type_record=="SOUR") {
						            	print "<br /> <a href=\"source.php?sid=".$indi."\"> ".$gm_lang["view_source"]." - ".PrintReady(get_source_descriptor($indi))."</a>";
								}
								//-- no reason why we might not get media linked to media. eg stills from movie clip, or differents resolutions of the same item
								else if ($type_record=="OBJE") {
									//-- TODO add a similar function get_media_descriptor($gid)
								}
							}
						}
						else {
							print $gm_lang["media_not_linked"];
						}
						print "\n\t\t\t</td>";
					}
				}
			}
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table></div>";
	}
	else if ($type == "object") {
		print "\n\t<table class=\"list_table center $TEXT_DIRECTION\">\n\t\t";
		get_medialist();
		$isvalid = false;
		if (count($medialist > 0)) {
			foreach ($medialist as $key => $media) {
				if ($filter) $isvalid = (strpos(str2lower($media["FILE"]),str2lower($filter)) !== false || strpos(str2lower($media["TITL"]),str2lower($filter)) !== false);
				else $isvalid = true;
				if ($isvalid) {
					print "\n\t\t<tr><td class=\"shade1 wrap $TEXT_DIRECTION\">";
					if ($media["TITL"] != "") $title = $media["TITL"]." (".$media["XREF"].")";
					else $title = $media["FILE"];
					if (isset($media["THUMB"])) print "<a href=\"#\" onclick=\"return openImage('".preg_replace("/'/", "\'", urlencode($media["FILE"]))."','".($media["WIDTH"]+50)."','".($media["HEIGHT"]+50)."');\"><img src=\"".filename_encode($media["THUMB"])."\" border=\"0\" width=\"50\" align=\"left\" ></a>\n";
					print "&nbsp;<a href=\"#\" onclick=\"pasteid('".$media["XREF"]."');\">".PrintReady($title)."</a>";
					print "\n\t\t</td></tr>";
					$isvalid = false;
				}
			}
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table>";
	}
	// Output Places
	else if ($type == "place") {
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t<tr>";
		$placelist = array();
		find_place_list($filter);
		uasort($placelist, "stringsort");
		$ctplace = count($placelist);
		if ($ctplace>0) {
			print "\n\t\t<td class=\"list_value_wrap $TEXT_DIRECTION\"><ul>";
			foreach($placelist as $indexval => $revplace) {
				$levels = preg_split ("/,/", $revplace);		// -- split the place into comma seperated values
				$levels = array_reverse($levels);				// -- reverse the array so that we get the top level first
				$placetext="";
				$j=0;
				foreach($levels as $indexval => $level) {
					if ($j>0) $placetext .= ", ";
					$placetext .= trim($level);
					$j++;
				}
				print "<li><a href=\"#\" onclick=\"pasteid('".preg_replace(array("/'/",'/"/'), array("\'",'&quot;'), $placetext)."');\">".PrintReady($revplace)."</a></li>\n";
			}
			print "\n\t\t</ul></td></tr>";
			print "<tr><td class=\"list_label\">".$gm_lang["total_places"]." ".$ctplace;
			print "</td></tr>";
		}
		else {
			print "<tr><td class=\"list_value_wrap $TEXT_DIRECTION\"><ul>";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table>";
	}

	// Output Repositories
	else if ($type == "repo") {
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t<tr>";
		$repolist = get_repo_list();
		$ctrepo = count($repolist);
		if ($ctrepo>0) {
			print "\n\t\t<td class=\"list_value_wrap\"><ul>";
			?><pre><?php
			print_r($repolist);
			?></pre><?php
			foreach ($repolist as $key => $value) {
				$id = $value["id"];
			    print "<li><a href=\"#\" onclick=\"pasteid('$id');\"><span class=\"list_item\">".PrintReady($key)."</span></a></li>";
			}
			print "</ul></td></tr>";
			print "<tr><td class=\"list_label\">".$gm_lang["repos_found"]." ".$ctrepo;
			print "</td></tr>";
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table>";

	}
	// Output Sources
	else if ($type == "source") {
		$oldged = $GEDCOM;
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t<tr>\n\t\t<td class=\"list_value\"><tr>";
		if (!isset($filter) || !$filter) $mysourcelist = get_source_list();
		else $mysourcelist = search_sources($filter);
		$cts=count($mysourcelist);
		if ($cts>0) {
			$curged = $GEDCOM;
			print "\n\t\t<td class=\"list_value_wrap\"><ul>";
			foreach ($mysourcelist as $key => $value) {
				print "<li>";
			    print "<a href=\"#\" onclick=\"pasteid('$key'); return false;\"><span class=\"list_item\">".PrintReady($value["name"])."</span></a>\n";
			    print "</li>\n";
			}
			print "</ul></td></tr>";
			$GEDCOM = $oldged;
			if ($GEDCOM != $curged) {
				ReadPrivacy($GEDCOM);
				$curged = $GEDCOM;
			}
			if ($cts > 0) print "<tr><td class=\"list_label\">".$gm_lang["total_sources"]." ".$cts."</td></tr>";
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table>";
	}

	// Output Special Characters
	else if ($type == "specialchar") {
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t<tr>\n\t\t<td class=\"list_value\">";
		//upper case special characters
		foreach($ucspecialchars as $key=>$value) {
			$value = str_replace("'","\'",$value);
			print "\n\t\t\t<a href=\"#\" onclick=\"return paste_char('$value','$language_filter','$magnify');\"><span class=\"list_item\" dir=\"$TEXT_DIRECTION\">";
			if ($magnify) print "<span class=\"largechars\">";
			print $key;
			if ($magnify) print "</span>";
			print "</span></a><br />";
		}
		print "</td>\n\t\t<td class=\"list_value\">";
		// lower case special characters
		foreach($lcspecialchars as $key=>$value) {
			$value = str_replace("'","\'",$value);
			print "\n\t\t\t<a href=\"#\" onclick=\"return paste_char('$value','$language_filter','$magnify');\"><span class=\"list_item\" dir=\"$TEXT_DIRECTION\">";
			if ($magnify) print "<span class=\"largechars\">";
			print $key;
			if ($magnify) print "</span>";
			print "</span></a><br />\n";
		}
		print "</td>\n\t\t<td class=\"list_value\">";
		// other special characters (not letters)
		foreach($otherspecialchars as $key=>$value) {
			$value = str_replace("'","\'",$value);
			print "\n\t\t\t<a href=\"#\" onclick=\"return paste_char('$value','$language_filter','$magnify');\"><span class=\"list_item\" dir=\"$TEXT_DIRECTION\">";
			if ($magnify) print "<span class=\"largechars\">";
			print $key;
			if ($magnify) print "</span>";
			print "</span></a><br />\n";
		}
		print "\n\t\t</td>\n\t\t</tr>\n\t</table>";
	}
}
print "</div>";
?>
<script language="JavaScript" type="text/javascript">
<!--
	document.filter<?php print $type;?>.filter.focus();
//-->
</script>
<?php
print_simple_footer();
?>