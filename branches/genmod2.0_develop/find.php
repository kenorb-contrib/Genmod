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
if (!isset($gedid)) $gedid = $GEDCOMID;
if (isset($GEDCOMS[$gedid])) $gedid = $GEDCOMS[$gedid]["id"];
if (!isset($type)) $type = "indi";
if (!isset($external_links)) $external_links = "";
if (!isset($filter)) $filter="";
else $filter = trim($filter);
if (!isset($callback)) $callback="paste_id";

// Variables for find media
if (!isset($create)) $create="";
if (!isset($media)) $media="";
if (!isset($embed)) $embed=false;
if (!isset($directory)) $directory = GedcomConfig::$MEDIA_DIRECTORY;
$badmedia = array(".","..","CVS","thumbs","index.php","MediaInfo.txt");
$showthumb= isset($showthumb);
if ($embed) check_media_db();
$thumbget = "";
if ($showthumb) {$thumbget = "&amp;showthumb=true";}

//-- force the thumbnail directory to have the same layout as the media directory
//-- Dots and slashes should be escaped for the preg_replace
$srch = "/".addcslashes(GedcomConfig::$MEDIA_DIRECTORY,'/.')."/";
$repl = addcslashes(GedcomConfig::$MEDIA_DIRECTORY."thumbs/",'/.');
$thumbdir = stripcslashes(preg_replace($srch, $repl, $directory));
if (!isset($level)) $level=0;

//-- prevent script from accessing an area outside of the media directory
//-- and keep level consistency
if (($level < 0) || ($level > GedcomConfig::$MEDIA_DIRECTORY_LEVELS)){
	$directory = GedcomConfig::$MEDIA_DIRECTORY;
	$level = 0;
} elseif (preg_match("'^".RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY)."'", $directory)==0){
	$directory = GedcomConfig::$MEDIA_DIRECTORY;
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
require("includes/values/specialchars.php");

// End variables for Find Special Character

switch ($type) {
	case "indi" :
		PrintSimpleHeader($gm_lang["find_individual"]);
		break;
	case "fam" :
		PrintSimpleHeader($gm_lang["find_fam_list"]);
		break;
	case "media" :
		PrintSimpleHeader($gm_lang["find_media"]);
		$action="filter";
		break;
	case "object" :
		PrintSimpleHeader($gm_lang["find_media"]);
		$action="filter";
		break;
	case "place" :
		PrintSimpleHeader($gm_lang["find_place"]);
		$action="filter";		
		break;
	case "repo" :
		PrintSimpleHeader($gm_lang["repo_list"]);
		$action="filter";		
		break;
	case "source" :
		PrintSimpleHeader($gm_lang["find_source"]);
		$action="filter";
		break;
	case "note" :
		PrintSimpleHeader($gm_lang["find_note"]);
		$action="filter";
		break;
	case "specialchar" :
		PrintSimpleHeader($gm_lang["find_specialchar"]);
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
$options["option"][]= "findnote";
$options["option"][]= "findspecialchar";
$options["form"][]= "formindi";
$options["form"][]= "formfam";
$options["form"][]= "formmedia";
$options["form"][]= "formobject";
$options["form"][]= "formplace";
$options["form"][]= "formrepo";
$options["form"][]= "formsource";
$options["form"][]= "formnote";
$options["form"][]= "formspecialchar";

global $TEXT_DIRECTION;
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
	case "note" :
		print $gm_lang["find_note"];
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
	print "<input type=\"hidden\" name=\"gedid\" value=\"".$gedid."\" />";
	print "<label class=\"width10\" style=\"padding: 5px;\">";
	print $gm_lang["name_contains"]."</label> <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print stripslashes($filter);
	print "\" />";
	print "</div>";
	print "<div class=\"width60 center\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  value=\"".$gm_lang["filter"]."\" /><br />";
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
	if (isset($filter)) print stripslashes($filter);
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  value=\"".$gm_lang["filter"]."\" /><br />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show media and hide the rest
else if ($type == "media") {
	print "<div align=\"center\">";
	print "<form name=\"filtermedia\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"embed\" value=\"".$embed."\" />";
	print "<input type=\"hidden\" name=\"directory\" value=\"".$directory."\" />";
	print "<input type=\"hidden\" name=\"thumbdir\" value=\"".$thumbdir."\" />";
	print "<input type=\"hidden\" name=\"level\" value=\"".$level."\" />";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"external_links\" value=\"".$external_links."\" />";
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
	print "<input type=\"submit\"  name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

else if ($type == "object") {
	print "<div class=\"center\">";
	print "<form name=\"filterobject\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"object\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked		
	print "<input type=\"hidden\" name=\"clicked\" value=\"dikkedeur\" />";
	print "<table class=\"list_table $TEXT_DIRECTION width30\">";
	print "<tr><td class=\"list_label width10\" style=\"padding: 5px;\">";
	print_help_link("simple_filter_help","qm");
	print $gm_lang["media_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print stripslashes($filter);
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label width10\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
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
	if (isset($filter)) print stripslashes($filter);
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show repo and hide the rest
else if ($type == "repo" && $SHOW_SOURCES >= $gm_user->getUserAccessLevel()) {
	print "<div align=\"center\">";
	print "<form name=\"filterrepo\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"repo\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked				
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["repo_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print stripslashes($filter);
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show note and hide the rest
else if ($type == "note") {
	print "<div align=\"center\">";
	print "<form name=\"filternote\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"clicked\" value=\"dikkedeur\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"note\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked				
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["note_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print stripslashes($filter);
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
	print "</td></tr></table>";
	print "</form></div>";
}

// Show source and hide the rest
else if ($type == "source" && $SHOW_SOURCES >= $gm_user->getUserAccessLevel()) {
	print "<div align=\"center\">";
	print "<form name=\"filtersource\" method=\"post\" onsubmit=\"return checknames(this);\" action=\"find.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"filter\" />";
	print "<input type=\"hidden\" name=\"clicked\" value=\"dikkedeur\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"source\" />";
	print "<input type=\"hidden\" name=\"callback\" value=\"$callback\" />";
	print "<input type=\"hidden\" name=\"subclick\">"; // This is for passing the name of which submit button was clicked
	print "<table class=\"list_table $TEXT_DIRECTION\" width=\"30%\" border=\"0\">";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print $gm_lang["source_contains"]." <input type=\"text\" name=\"filter\" value=\"";
	if (isset($filter)) print stripslashes($filter);
	print "\" />";
	print "</td></tr>";
	print "<tr><td class=\"list_label\" width=\"10%\" style=\"padding: 5px;\">";
	print "<input type=\"submit\"  name=\"search\" value=\"".$gm_lang["filter"]."\" onclick=\"this.form.subclick.value=this.name\" />&nbsp;";
	print "<input type=\"submit\"  name=\"all\" value=\"".$gm_lang["display_all"]."\" onclick=\"this.form.subclick.value=this.name\" />";
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
		$oldgedid = $GEDCOMID;
		$myindilist = SearchIndisNames($filter,false,$gedid);
		$cti=count($myindilist);
		if ($cti>0) {
			PrintPersonList($myindilist, true, true);
		}
		else {
			print "<div class=\"center width60\">";
			print $gm_lang["no_results"];
			print "</div>";
		}
	}

	// Output Family
	else if ($type == "fam") {
		$myindilist = array();
		$myfamlist = array();
		$myfamlist2 = array();
		$famquery = array();
		
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION center\" width=\"90%\">\n\t\t<tr>";
		if (FindPersonRecord($filter)) {
			$printname = SearchFamsMembers($filter);
			$ctf = count($printname);
		}
		else {
			$myindilist = SearchIndis($filter);
			foreach($myindilist as $key1 => $myindi) {
				foreach($myindi["names"] as $key2 => $name) {
					if ((preg_match("/".$filter."/i", $name[0]) > 0)) {
						$famquery[] = array($key1, $GEDCOMID);
						break;
					}
				}
			}
			$ctf=count($famquery);
			$printname = array();
			if ($ctf>0) {
				// Get the famrecs with hits on names from the family table
				$myfamlist = SearchFamsNames($famquery, "OR", true);
				// Get the famrecs with hits in the gedcom record from the family table
				$myfamlist2 = SearchFams($filter, false, "OR", true);		
				$myfamlist = GmArrayMerge($myfamlist, $myfamlist2);
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
							$printname[]=array(CheckNN($famname), $key, get_gedcom_from_id($value["gedfile"]));
							break;
						}
				    }
				}		
			}					
		}
		if (!empty($printname)) {
			print "\n\t\t<td class=\"list_value_wrap $TEXT_DIRECTION\"><ul>";
			uasort($printname, "ItemSort");
			foreach($printname as $pkey => $pvalue) {
				SwitchGedcom($pvalue[2]);
				print_list_family($pvalue[1], array($pvalue[0], $pvalue[2]), true);
				print "\n";
			}
			print "\n\t\t</ul></td>";
			SwitchGedcom();
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
		
//		print "find dir: ".$directory."<br />";
		$dirs = MediaFS::GetMediaDirList($directory, false, 1, false, false);
		//print_r($dirs);
		print "<br />";
		print "<div align=\"center\">";
		print "\n\t<table class=\"tabs_table center $TEXT_DIRECTION width80\">\n\t\t";
		$directory = RelativePathFile($directory);
		$mdir = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
		
		// Show link to previous folder		
		if ($level>0) {
			$levels = preg_split("'/'", $directory);
			$pdir = "";
			for($i=0; $i<count($levels)-2; $i++) $pdir.=$levels[$i]."/";
			$levels = preg_split("'/'", $thumbdir);
			$pthumb = "";
			for($i=0; $i<count($levels)-2; $i++) $pthumb.=$levels[$i]."/";
			$uplink = "<a href=\"find.php?embed=$embed&amp;directory=$pdir&amp;thumbdir=$pthumb&amp;level=".($level-1).$thumbget."&amp;type=media&amp;filter=".$filter."\">&nbsp;&nbsp;&nbsp;&lt;-- $pdir&nbsp;&nbsp;&nbsp;</a><br />\n";
		}

		// Tell the user where he is
		print "<tr><td class=\"list_value wrap $TEXT_DIRECTION\" colspan=\"4\">".$gm_lang["current_dir"];
		if ($external_links == "1") print $gm_lang["external_media"];
		else print $directory;
		print "</td></tr>";

		
		// display the directory list
		if (count($dirs) || $level) {
			sort($dirs);
			if ($level){
				print "<tr><td class=\"list_value $TEXT_DIRECTION\" colspan=\"4\">";
				print $uplink."</td></tr>";
			}
			print "<tr><td class=\"shade2 $TEXT_DIRECTION\" colspan=\"4\">";
			print "<a href=\"find.php?directory=&amp;external_links=1&amp;type=media".$thumbget."&amp;level=0\">".$gm_lang["external_media"]."</a>";
			print "</td></tr>";
			// If we view the external links, add a link to the main directory
			if ($external_links == "1") {
				print "<tr><td class=\"list_value wrap $TEXT_DIRECTION\" colspan=\"4\" width=\"45%\">";
				print "<a href=\"find.php?directory=".GedcomConfig::$MEDIA_DIRECTORY."&thumbdir=".GedcomConfig::$MEDIA_DIRECTORY.$thumbget."&level=0&amp;type=media&amp;filter=".$filter."\">".$mdir."</a>";
				print "</td></tr>";
			}
			if ($level < GedcomConfig::$MEDIA_DIRECTORY_LEVELS) {
				foreach ($dirs as $indexval => $dir) {
					if ($dir != $directory) {
						print "<tr><td class=\"list_value wrap $TEXT_DIRECTION\" colspan=\"4\" width=\"45%\">";
						print "<a href=\"find.php?directory=$dir&thumbdir=$dir&level=".($level+1).$thumbget."&amp;type=media&amp;filter=".$filter."\">".$dir."</a>";
						print "</td></tr>";
					}
				}
			}
		}
//		print "<tr><td class=\"list_value $TEXT_DIRECTION\">";
		print "<tr>";
		
		$applyfilter = ($filter != "");
		print "<br />";
		
		if ($external_links == "1") $directory = "external_links";
		$medialist = MediaFS::GetMediaFilelist($directory, $filter);

		// Privacy is already checked in the function
		// An empty media object is returned for not coupled files
		if (count($medialist) > 0) {
			$prt = 0;
			foreach ($medialist as $file => $mediaobjs) {
				if ($prt%2 == 0) print "<tr>";
				MediaFS::PrintViewLink($mediaobjs, $showthumb, true);
				$prt++;
				if ($prt%2 == 0) print "</tr>";
			}
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table></table></div>";
	}
	else if ($type == "object" && isset($clicked)) {
		print "\n\t<table class=\"list_table center $TEXT_DIRECTION\">\n\t\t";
		$fmedialist = new MediaListController;
		$fmedialist->RetrieveFilterMediaList($filter);
		if (count($fmedialist->medialist > 0)) {
			foreach ($fmedialist->medialist as $key => $media) {
				print "\n\t\t<tr><td class=\"shade1 wrap $TEXT_DIRECTION\">";
				if (!empty($media->fileobj->f_thumb_file)) {
					if (USE_GREYBOX && $media->fileobj->f_is_image) print "<a href=\"".FilenameEncode($media->fileobj->f_file)."\" title=\"".$media->title."\" rel=\"gb_imageset[]\">";
					else print "<a href=\"#\" onclick=\"return openImage('".$media->fileobj->f_main_file."','".($media->fileobj->f_width+50)."','".($media->fileobj->f_height+50)."', '".$media->fileobj->f_is_image."');\">";
					print "<img src=\"".FilenameEncode($media->fileobj->f_thumb_file)."\" border=\"0\" width=\"50\" align=\"left\" ></a>\n";
				}
				print "&nbsp;<a href=\"#\" onclick=\"pasteid('".$media-xref."');\">".PrintReady($media->title)."</a>";
				print "\n\t\t</td></tr>";
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
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t";
		$placelist = array();
		FindPlaceList($filter);
		uasort($placelist, "stringsort");
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
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t";
		$repolist = GetRepoList($filter);
		$ctrepo = count($repolist);
		if ($ctrepo>0) {
			print "\n\t\t<tr><td class=\"list_value_wrap\"><ul>";
			foreach ($repolist as $key => $value) {
				$id = $value["id"];
				if (PrivacyFunctions::DisplayDetailsByID($id, "REPO", 1, true)) {
			    	print "<li><a href=\"#\" onclick=\"sndReq(document.getElementById('dummy'), 'lastused', 'type', 'REPO', 'id', '".JoinKey($id, $GEDCOMID)."'); pasteid('$id');\"><span id=\"dummy\"></span><span class=\"list_item\">".PrintReady($key)."</span></a></li>";
		    	}
		    	else $ctrepo--;
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
	else if ($type == "source" && isset($clicked)) {
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t<tr>\n\t\t<td class=\"list_value\">";
		if (!isset($filter) || !$filter) {
			$mysourcelist = GetSourceList();
			GetAllSourceLinks(false);
		}
		else {
			$mysourcelist = SearchSources($filter);
		}
		$cts=count($mysourcelist);
		if ($cts>0) {
			print "\n\t\t<tr><td class=\"list_value_wrap\"><ul>";
			foreach ($mysourcelist as $key => $value) {
				SwitchGedcom($value["gedfile"]);
				if (PrivacyFunctions::DisplayDetailsByID($key, "SOUR", 1, true)) {
					print "<li>";
				    print "<a href=\"#\" onclick=\"sndReq(document.getElementById('dummy'), 'lastused', 'type', 'SOUR', 'id', '".JoinKey($key, $GEDCOMID)."'); pasteid('$key'); return false;\"><span class=\"list_item\">".PrintReady($value["name"])."</span></a>\n";
				    print "</li>\n";
			    }
			    else $cts--;
			}
			print "</ul></td></tr>";
			SwitchGedcom();
			if ($cts > 0) print "<tr><td class=\"list_label\">".$gm_lang["total_sources"]." ".$cts."</td></tr>";
		}
		else {
			print "<tr><td class=\"list_value_wrap\">";
			print $gm_lang["no_results"];
			print "</td></tr>";
		}
		print "</table>";
	}
	
	// Output Notes
	else if ($type == "note" && isset($clicked)) {
		$note_controller = new NoteController();
		$note_controller->GetNoteList($filter);
		print "\n\t<table class=\"tabs_table $TEXT_DIRECTION\" width=\"90%\">\n\t\t<tr>\n\t\t<td class=\"list_value\">";
		$ctn = count($note_controller->notelist);
		if ($ctn>0) {
			$curged = $GEDCOMID;
			print "\n\t\t<tr><td class=\"list_value_wrap\"><ul>";
			foreach ($note_controller->notelist as $key => $note) {
				SwitchGedcom($note->gedcomid);
				if (PrivacyFunctions::DisplayDetailsByID($key, "NOTE", 1, true)) {
					print "<li>";
				    print "<a href=\"#\" onclick=\"sndReq(document.getElementById('dummy'), 'lastused', 'type', 'NOTE', 'id', '".JoinKey($note->xref, $GEDCOMID)."'); pasteid('".$note->xref."'); return false;\"><span class=\"list_item\">".PrintReady($note->GetTitle()." ".$note->addxref)."</span></a>\n";
				    print "</li>\n";
			    }
			    else $ctn--;
			}
			print "</ul></td></tr>";
			SwitchGedcom();
			if ($ctn > 0) print "<tr><td class=\"list_label\">".$gm_lang["total_notes"]." ".$ctn."</td></tr>";
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
if ($type != "specialchar") {?>
<script language="JavaScript" type="text/javascript">
<!--
	document.filter<?php print $type;?>.filter.focus();
//-->
</script>
<?php }
PrintSimpleFooter();
?>