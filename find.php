<?php
/**
 * Popup window that will allow a user to search for a family id, person id
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

$search_controller = new SearchController("find");

// Variables for Find Special Character
if ($search_controller->type == "specialchar") require("includes/values/specialchars.php");

// End variables for Find Special Character
PrintSimpleHeader($search_controller->pagetitle);

?>
<script language="JavaScript" type="text/javascript">
<!--
	function pasteid(id, name) {
		window.opener.paste_id(id);
		if (window.opener.pastename) window.opener.pastename(name);
		window.close();
	}

	var language_filter;
	function paste_char(selected_char,language_filter,magnify) {
		window.opener.paste_char(selected_char,language_filter,magnify);
		return false;
	}

	function setMagnify() {
		document.filterspecialchar.magnify.value = '<?PHP print !$search_controller->magnify; ?>';
		document.filterspecialchar.submit();
	}

	function checknames(frm) {
		button = typeof(document.forms[0].subclick) != 'undefined' ? document.forms[0].subclick.value : 'any';
		if (frm.query.value.length < 2 & button != 'all' && frm.query.value.charCodeAt(0) < 256) {
			alert("<?php print GM_LANG_search_more_chars?>");
			frm.query.focus();
			return false;
		}
		if (button=="all") {
			frm.query.value = "";
		}
		return true;
	}
//-->
</script>
<?php
print "<div class=\"topbottombar width60\">";

switch ($search_controller->type) {
	case "indi" :
		print GM_LANG_find_individual;
		break;
	case "fam" :
		print GM_LANG_find_fam_list;
		break;
	case "media" :
		print GM_LANG_find_media;
		break;
	case "file" :
		print GM_LANG_find_mfile;
		break;
	case "place" :
		print GM_LANG_find_place;
		break;
	case "repo" :
		print GM_LANG_repo_list;
		break;
	case "source" :
		print GM_LANG_find_source;
		break;
	case "note" :
		print GM_LANG_find_note;
		break;
	case "specialchar" :
		print GM_LANG_find_specialchar;
		break;
}
print "</div>\n";

// NOTE: Show inputs for indi, fam
if (in_array($search_controller->type, array("indi", "fam", "media", "note", "source", "repo")))  {
	print "<form name=\"filter".$search_controller->type."\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<div class=\"width60 center shade1\"><br />";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"".$search_controller->type."\" />";
	print "<label class=\"width10\" style=\"padding: 5px;\">";
	print constant("GM_LANG_".($search_controller->type == "indi" || $search_controller->type == "fam" ? "name" : $search_controller->type)."_contains")."</label> <input type=\"text\" name=\"query\" value=\"";
	if (!is_null($search_controller->query)) print stripslashes($search_controller->query);
	print "\" />";
	PrintHelpLink("simple_filter_help","qm");
	print "<br /><br /></div>";
	print "<div class=\"width60 center\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  value=\"".GM_LANG_filter."\" /><br />";
	print "</div></form>";
}		

// Show mediafiles and hide the rest
else if ($search_controller->type == "file") {
	print "<form name=\"filterfile\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">\n";
	print "<div class=\"width60 center shade1\">\n";
	print "<input type=\"hidden\" name=\"directory\" value=\"".$search_controller->directory."\" />\n";
	print "<input type=\"hidden\" name=\"thumbdir\" value=\"".$search_controller->thumbdir."\" />\n";
	print "<input type=\"hidden\" name=\"level\" value=\"".$search_controller->level."\" />\n";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />\n";
	print "<input type=\"hidden\" name=\"external_links\" value=\"".$search_controller->external_links."\" />\n";
	print "<input type=\"hidden\" name=\"type\" value=\"file\" />\n";
	print "<input type=\"hidden\" name=\"subclick\" value=\"\" />\n"; // This is for passing the name of which submit button was clicked		
	print "<label class=\"width10\" style=\"padding: 5px;\">";
	print GM_LANG_file_contains."</label> <input type=\"text\" name=\"query\" value=\"";
	if (!is_null($search_controller->query)) print $search_controller->query;
	print "\" />\n";
	PrintHelpLink("simple_filter_help","qm");
	print "</div>\n";
	print "<div class=\"width60 center\" style=\"padding: 5px;\">\n";
	print "<input type=\"checkbox\" name=\"showthumb\" value=\"true\" ";
	if( $search_controller->showthumb) print "checked=\"checked\" ";
	print "onclick=\"javascript: this.form.submit();\" />".GM_LANG_show_thumbnail;
	PrintHelpLink("show_thumb_help","qm");
	print "<br /><br /><input type=\"submit\"  name=\"search\" value=\"".GM_LANG_filter."\" onclick=\"this.form.subclick.value=this.name; return true;\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".GM_LANG_display_all."\" onclick=\"this.form.subclick.value=this.name; return true;\" />";
	print "</div>\n</form>\n";
}

// Show place and hide the rest
else if ($search_controller->type == "place") {
	print "<form name=\"filterplace\" method=\"post\"  onsubmit=\"return checknames(this);\" action=\"find.php\">\n";
	print "<div class=\"width60 center shade1\"><br />\n";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />\n";
	print "<input type=\"hidden\" name=\"type\" value=\"place\" />\n";
	print "<input type=\"hidden\" name=\"subclick\" />\n"; // This is for passing the name of which submit button was clicked				
	print "<label class=\"width10\" style=\"padding: 5px;\">";
	print GM_LANG_place_contains."</label> <input type=\"text\" name=\"query\" value=\"";
	if (!is_null($search_controller->query)) print stripslashes($search_controller->query);
	print "\" />";
	print "<br /><br /></div>";
	print "<div class=\"width60 center\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  name=\"search\" value=\"".GM_LANG_filter."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".GM_LANG_display_all."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</div></form>";
}

// Show specialchar and hide the rest
else if ($search_controller->type == "specialchar") {
	print "<form name=\"filterspecialchar\" method=\"post\" action=\"find.php\">";
	print "<div class=\"width60 center shade1\"><br />";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"specialchar\" />";
	print "<input type=\"hidden\" name=\"magnify\" value=\"".$search_controller->magnify."\" />";
	print "<label class=\"width10\" style=\"padding: 5px;\">";
	print GM_LANG_change_lang."</label> ";
	print "<select id=\"language_filter\" name=\"language_filter\" onchange=\"submit();\">";
	foreach($specialchar_languages as $key=>$value) {
		print "\n\t<option value=\"".$key."\"";
		if ($key == $search_controller->language_filter) print " selected=\"selected\"";
		print ">".$value."</option>";
	}
	print "</select><br /><br /><a href=\"#\" onclick=\"setMagnify()\">".($search_controller->magnify ? GM_LANG_reduce : GM_LANG_magnify)."</a><br />";
	print "</div></form>";
}
// end column for find options
print "<br />";
print "<div class=\"center\">";
print "<a href=\"#\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".GM_LANG_close_window."</a><br />\n";
print "</div>";

if ($search_controller->action == "filter") {
	// Output Individual
	if ($search_controller->type == "indi") {
		print "<div id=\"indis\" class=\"center width100\">";
		if (!SearchFunctions::PrintIndiSearchResults($search_controller, true)) print GM_LANG_no_results;
		print "</div>";
	}

	// Output Family
	else if ($search_controller->type == "fam") {
		print "<div id=\"fams\" class=\"center width100\">";
		if (!SearchFunctions::PrintFamSearchResults($search_controller, true)) print GM_LANG_no_results;
		print "</div>";
	}

	// Output Repositories
	else if ($search_controller->type == "repo") {
		print "<div id=\"repos\" class=\"center width100\">";
		if (!SearchFunctions::PrintRepoSearchResults($search_controller, true)) print GM_LANG_no_results;
		print "</div>";
	}
	
	// Output Sources
	else if ($search_controller->type == "source") {
		print "<div id=\"sources\" class=\"center width100\">";
		if (!SearchFunctions::PrintSourceSearchResults($search_controller, true)) print GM_LANG_no_results;
		print "</div>";
	}
	
	// Output Notes
	else if ($search_controller->type == "note") {
		print "<div id=\"notes\" class=\"center width100\">";
		if (!SearchFunctions::PrintNoteSearchResults($search_controller, true)) print GM_LANG_no_results;
		print "</div>";
	}

	// Output Media Files
	else if ($search_controller->type == "file") {
		
		$thumbget = ($search_controller->showthumb ? "&amp;showthumb=true" : "");
//		print "find dir: ".$search_controller->directory."<br />";
		$dirs = MediaFS::GetMediaDirList($search_controller->directory, false, 1, false, false);
		//print_r($dirs);
		print "<br />";
		print "<div align=\"center\">";
		print "\n\t<table class=\"tabs_table center $TEXT_DIRECTION width100\">\n\t\t";
		$directory = RelativePathFile($search_controller->directory);
		$mdir = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
		
		// Show link to previous folder		
		if ($search_controller->level > 0) {
			$levels = preg_split("'/'", $directory);
			$pdir = "";
			for($i=0; $i<count($levels)-2; $i++) $pdir.=$levels[$i]."/";
			$levels = preg_split("'/'", $search_controller->thumbdir);
			$pthumb = "";
			for($i=0; $i<count($levels)-2; $i++) $pthumb.=$levels[$i]."/";
			$uplink = "<a href=\"find.php?directory=$pdir&amp;thumbdir=".$pthumb."&amp;level=".($search_controller->level-1).$thumbget."&amp;type=file&amp;query=".$search_controller->query."\">&nbsp;&nbsp;&nbsp;&lt;-- $pdir&nbsp;&nbsp;&nbsp;</a><br />\n";
		}

		// Tell the user where he is
		print "<tr><td class=\"list_value wrap $TEXT_DIRECTION\" colspan=\"4\">".GM_LANG_current_dir;
		if ($search_controller->external_links == "1") print GM_LANG_external_media;
		else print $directory;
		print "</td></tr>\n";

		
		// display the directory list
		if (count($dirs) || $search_controller->level) {
			sort($dirs);
			if ($search_controller->level){
				print "<tr><td class=\"list_value $TEXT_DIRECTION\" colspan=\"4\">";
				print $uplink."</td></tr>";
			}
			print "<tr><td class=\"shade2 $TEXT_DIRECTION\" colspan=\"4\">";
			print "<a href=\"find.php?directory=&amp;external_links=1&amp;type=file".$thumbget."&amp;level=0\">".GM_LANG_external_media."</a>";
			print "</td></tr>\n";
			// If we view the external links, add a link to the main directory
			if ($search_controller->external_links == "1") {
				print "<tr><td class=\"list_value wrap $TEXT_DIRECTION\" colspan=\"4\" width=\"45%\">";
				print "<a href=\"find.php?directory=".GedcomConfig::$MEDIA_DIRECTORY."&amp;thumbdir=".GedcomConfig::$MEDIA_DIRECTORY.$thumbget."&amp;level=0&amp;type=file&amp;query=".$search_controller->query."\">".$mdir."</a>";
				print "</td></tr>\n";
			}
			if ($search_controller->level < GedcomConfig::$MEDIA_DIRECTORY_LEVELS) {
				foreach ($dirs as $indexval => $dir) {
					if ($dir != $directory) {
						print "<tr><td class=\"list_value wrap $TEXT_DIRECTION\" colspan=\"4\" width=\"45%\">";
						print "<a href=\"find.php?directory=".$dir."&amp;thumbdir=".$dir."&amp;level=".($search_controller->level+1).$thumbget."&amp;type=file&amp;query=".$search_controller->query."\">".$dir."</a>";
						print "</td></tr>\n";
					}
				}
			}
		}
		
		$applyfilter = ($search_controller->query != "");
		
		if ($search_controller->external_links == "1") $directory = "external_links";
		$medialist = MediaFS::GetMediaFilelist($directory, $search_controller->query);

		// Privacy is already checked in the function
		// An empty media object is returned for not coupled files
		if (count($medialist) > 0) {
			$prt = 0;
			foreach ($medialist as $file => $mediaobjs) {
				if ($prt%2 == 0) print "<tr>";
				MediaFS::PrintViewLink($mediaobjs, $search_controller->showthumb, true);
				$prt++;
				if ($prt%2 == 0) print "</tr>";
			}
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print GM_LANG_no_results;
			print "</td></tr>";
		}
		print "</table></div>";
	}
	else if ($search_controller->type == "media") {
		print "\n\t<table class=\"list_table center $TEXT_DIRECTION\">\n\t\t";
		if (count($search_controller->media_total) > 0) {
			foreach ($search_controller->printmedia as $key => $mediakey) {
				$media = $search_controller->smedialist[$mediakey];
				print "\n\t\t<tr><td class=\"shade1 wrap $TEXT_DIRECTION\">";
				MediaFS::DispImgLink($media->fileobj->f_file, $media->fileobj->f_thumb_file, $media->title, "", 50, 0, ($media->fileobj->f_width+50), ($media->fileobj->f_height+50), $media->fileobj->f_is_image, $media->fileobj->f_file_exists);
				print "&nbsp;<a href=\"#\" onclick=\"pasteid('".$media->xref."');\">".PrintReady($media->title.$media->addxref)."</a>";
				print "\n\t\t</td></tr>";
			}
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print GM_LANG_no_results;
			print "</td></tr>";
		}
		print "</table>";
	}
	// Output Places
	else if ($search_controller->type == "place") {
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t";
		$placelist = ListFunctions::FindPlaceList($search_controller->query);
		$ctplace = count($placelist);
		if ($ctplace>0) {
			print "\n\t\t<tr><td class=\"list_value_wrap $TEXT_DIRECTION\"><ul>";
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
			print "<tr><td class=\"list_label\">".GM_LANG_total_places." ".$ctplace;
			print "</td></tr>";
		}
		else {
			print "<tr><td class=\"list_value_wrap $TEXT_DIRECTION\"><ul>";
			print GM_LANG_no_results;
			print "</td></tr>";
		}
		print "</table>";
	}

	// Output Special Characters
	else if ($search_controller->type == "specialchar") {
		print "\n\t<div class=\"list_value center width90 ".$TEXT_DIRECTION."\">";
		//upper case special characters
		foreach($ucspecialchars as $key=>$value) {
			$value = str_replace("'","\'",$value);
			print "\n\t\t\t<a href=\"#\" onclick=\"return paste_char('".$value."','".$search_controller->language_filter."','".$search_controller->magnify."');\"><span class=\"list_item\" dir=\"".$TEXT_DIRECTION."\">";
			if ($search_controller->magnify) print "<span class=\"largechars\">";
			print $key;
			if ($search_controller->magnify) print "</span>";
			print "</span></a>  \n";
		}
		print "</div>";
		print "\n\t<div class=\"list_value center width90 $TEXT_DIRECTION\" style=\"margin-top:5px;\">";
		// lower case special characters
		foreach($lcspecialchars as $key=>$value) {
			$value = str_replace("'","\'",$value);
			print "\n\t\t\t<a href=\"#\" onclick=\"return paste_char('".$value."','".$search_controller->language_filter."','".$search_controller->magnify."');\"><span class=\"list_item\" dir=\"".$TEXT_DIRECTION."\">";
			if ($search_controller->magnify) print "<span class=\"largechars\">";
			print $key;
			if ($search_controller->magnify) print "</span>";
			print "</span></a>  \n";
		}
		print "</div>";
		print "\n\t<div class=\"list_value center width90 $TEXT_DIRECTION\" style=\"margin-top:5px;\">";
		// other special characters (not letters)
		foreach($otherspecialchars as $key=>$value) {
			$value = str_replace("'","\'",$value);
			print "\n\t\t\t<a href=\"#\" onclick=\"return paste_char('".$value."','".$search_controller->language_filter."','".$search_controller->magnify."');\"><span class=\"list_item\" dir=\"".$TEXT_DIRECTION."\">";
			if ($search_controller->magnify) print "<span class=\"largechars\">";
			print $key;
			if ($search_controller->magnify) print "</span>";
			print "</span></a>  \n";
		}
		print "</div>";
	}
}
if ($search_controller->type != "specialchar") {?>
<script language="JavaScript" type="text/javascript">
<!--
	document.filter<?php print $search_controller->type;?>.query.focus();
//-->
</script>
<?php }
PrintSimpleFooter();
?>