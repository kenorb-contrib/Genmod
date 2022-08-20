<?php
/**
 * Displays a place hierachy
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
 * @version $Id: placelist.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$placelist_controller = new PlacelistController();

PrintHeader($placelist_controller->pagetitle);
print "<div id=\"PlaceListPage\">";
print '<div class="PageTitleName">'.$placelist_controller->title.'</div>';


//-- hierarchical display
if ($placelist_controller->display == "hierarchy") {
	// -- array of names
	$placelist = $placelist_controller->GetPlaceList($placelist_controller->parent, $placelist_controller->level);
	$numfound = count($placelist);
	
	//-- create a query string for passing to search page
	$tempparent = array_reverse($placelist_controller->parent);
	if (count($tempparent)>0) $squery = "&query=".urlencode($tempparent[0]);
	else $squery="";
	for($i=1; $i<$placelist_controller->level; $i++) {
		$squery.=", ".urlencode($tempparent[$i]);
	}

	// -- print the breadcrumb hierarchy
	$numls=0;
	print "<div class=\"PlaceListBreadCrumb\">";
	if ($placelist_controller->level > 0) {
		//-- link to search results
		if (($placelist_controller->level > 1 || $placelist_controller->parent[0] != "") && $numfound > 0) {
			print $numfound."  ".GM_LANG_connections.": ";
		}
		//-- breadcrumb
		$numls = count($placelist_controller->parent)-1;
		$num_place="";
		//-- place and page text orientation is opposite -> top level added at the beginning of the place text
		print "<a href=\"placelist.php?level=0&amp;select=".$placelist_controller->select."\">";
		if ($numls>=0 && (($TEXT_DIRECTION=="ltr" && hasRtLText($placelist_controller->parent[$numls])) || ($TEXT_DIRECTION=="rtl" && !hasRtLText($placelist_controller->parent[$numls])))) print GM_LANG_top_level.", ";
		print "</a>";
	    for($i=$numls; $i>=0; $i--) {
			print "<a href=\"placelist.php?level=".($i+1)."&amp;";
			for ($j=0; $j<=$i; $j++) {
				$levels = preg_split ("/,/", trim($placelist_controller->parent[$j]));
				// Routine for replacing ampersands
				foreach($levels as $pindex=>$ppart) {
					$ppart = urlencode($ppart);
					$ppart = preg_replace("/amp\%3B/", "", trim($ppart));
					print "&amp;parent[$j]=".$ppart;
				}
			}
 			print "&amp;select=".$placelist_controller->select."\">";
 			if (trim($placelist_controller->parent[$i])=="") print GM_LANG_unknown;
			else {
				print PrintReady($placelist_controller->parent[$i]);
				if (NameFunctions::HasChinese($placelist_controller->parent[$i])) print " (".printReady(NameFunctions::GetPinYin($placelist_controller->parent[$i])).")";
				else if (NameFunctions::HasCyrillic($placelist_controller->parent[$i])) print " (".printReady(NameFunctions::GetTransliterate($placelist_controller->parent[$i])).")";
			}
			print "</a>";
 			if ($i>0) print ", ";
 			else if (($TEXT_DIRECTION=="rtl" && hasRtLText($placelist_controller->parent[$i])) || ($TEXT_DIRECTION=="ltr" &&  !hasRtLText($placelist_controller->parent[$i])))  print ", ";
			if (empty($num_place)) $num_place=$placelist_controller->parent[$i];
		}
	}
	print "<a href=\"placelist.php?level=0&amp;select=".$placelist_controller->select."\">";
	//-- place and page text orientation is the same -> top level added at the end of the place text
	if ($placelist_controller->level == 0 || ($numls>=0 && (($TEXT_DIRECTION=="rtl" && hasRtLText($placelist_controller->parent[$numls])) || ($TEXT_DIRECTION=="ltr" && !hasRtLText($placelist_controller->parent[$numls]))))) print GM_LANG_top_level;
	print "</a>";

	PrintHelpLink("ppp_levels_help", "qm");
	print "</div>";
	
	$map_printed = false;
	// show clickable map if found
	if ($placelist_controller->level >= 1 && $placelist_controller->level <= 3) {
		$country = $placelist_controller->parent[0];
		if ($country == "\xD7\x99\xD7\xA9\xD7\xA8\xD7\x90\xD7\x9C") $country = "ISR"; // Israel hebrew name
		$country = strtoupper($country);
		if (strlen($country)!=3) {
			// search country code using current language countries table
			require(SystemConfig::$GM_BASE_DIRECTORY."languages/countries.en.php");
			// changed $LANGUAGE to $deflang (the language set for the current gedcom)	// eikland
			// changed to $GEDCOMLANG sjouke
			if (file_exists(SystemConfig::$GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[GedcomConfig::$GEDCOMLANG].".php")) require(SystemConfig::$GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[GedcomConfig::$GEDCOMLANG].".php");
			foreach ($countries as $countrycode => $countryname) {
				if (strtoupper($countryname) == $country) {
					$country = $countrycode;
					break;
				}
			}
			if (strlen($country)!=3) $country=substr($country,0,3);
		}
		$mapname = $country;
		$areaname = $placelist_controller->parent[0];
		$imgfile = "places/".$country."/".$mapname.".gif";
		$mapfile = "places/".$country."/".$country.".".$lang_short_cut[$LANGUAGE].".htm";
		if (!file_exists($mapfile)) $mapfile = "places/".$country."/".$country.".htm";

		if ($placelist_controller->level > 1) {
			$state = SmartUtf8Decode($placelist_controller->parent[1]);
			$mapname .= "_".$state;
			if ($placelist_controller->level > 2) {
				$county = SmartUtf8Decode($placelist_controller->parent[2]);
				$mapname .= "_".$county;
//				$placelist_controller->parent[2] = str_replace("'","\'",$placelist_controller->parent[2]);
//				$areaname = $placelist_controller->parent[2];
				$areaname = str_replace("'","\'",$placelist_controller->parent[2]);
			}
			else {
//				$placelist_controller->parent[1] = str_replace("'","\'",$placelist_controller->parent[1]);
//				$areaname = $placelist_controller->parent[1];
				$areaname = str_replace("'","\'",$placelist_controller->parent[1]);			
			}
			$mapname = strtr($mapname,"���������������������������������������������������������������������' ","SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy--");
			$imgfile = "places/".$country."/".$mapname.".gif";
		}
		if (file_exists($imgfile) and file_exists($mapfile)) {
			print "\n\t\n\t<div class=\"PlaceListMap\">";
			$map_printed = true;
			include ($mapfile);
//			changed $mapname to $areaname for alt and title to show the area full name and not just area code	// eikland
			print "<img src='".$imgfile."' usemap='#".$mapname."' border='0' alt='".$areaname."' title='".$areaname."' />";
			?>
			<script type="text/javascript" src="strings.js"></script>
			<script type="text/javascript">
			<!--
			//	copy php array into js array
			var places_accept = new Array(<?php foreach ($placelist as $key => $value) print "'".str_replace("'", "\'", $value)."',"; print "''";?>)
			Array.prototype.in_array = function(val) {
				for (var i in this) {
					if (this[i] == val) return true;
				}
				return false;
			}
			function setPlaceState(txt) {
				if (txt=='') return;
				// search full text [California (CA)]
				var search = txt;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=2<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]="?>'+search);
				// search without optional code [California]
				txt = txt.replace(/(\/)/,' ('); // case: finnish/swedish ==> finnish (swedish
				p=txt.indexOf(' (');
				if (p>1) search=txt.substring(0,p);
				else return;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=2<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]="?>'+search);
				// search with code only [CA]
				search=txt.substring(p+2);
				p=search.indexOf(')');
				if (p>1) search=search.substring(0,p);
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=2<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]="?>'+search);
			}
			function setPlaceCounty(txt) {
				if (txt=='') return;
				var search = txt;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=3<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]=".@$placelist_controller->parent[1]."&parent[2]="?>'+search);
				txt = txt.replace(/(\/)/,' (');
				p=txt.indexOf(' (');
				if (p>1) search=txt.substring(0,p);
				else return;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=3<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]=".@$placelist_controller->parent[1]."&parent[2]="?>'+search);
				search=txt.substring(p+2);
				p=search.indexOf(')');
				if (p>1) search=search.substring(0,p);
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=3<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]=".@$placelist_controller->parent[1]."&parent[2]="?>'+search);
			}
			function setPlaceCity(txt) {
				if (txt=='') return;
				var search = txt;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=4<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]=".@$placelist_controller->parent[1]."&parent[2]=".@$placelist_controller->parent[2]."&parent[3]="?>'+search);
				txt = txt.replace(/(\/)/,' (');
				p=txt.indexOf(' (');
				if (p>1) search=txt.substring(0,p);
				else return;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=4<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]=".@$placelist_controller->parent[1]."&parent[2]=".@$placelist_controller->parent[2]."&parent[3]="?>'+search);
				search=txt.substring(p+2);
				p=search.indexOf(')');
				if (p>1) search=search.substring(0,p);
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=4<?php print "&parent[0]=".$placelist_controller->parent[0]."&parent[1]=".@$placelist_controller->parent[1]."&parent[2]=".@$placelist_controller->parent[2]."&parent[3]="?>'+search);
			}
			//-->
			</script>
			<?php
		print "</div>";
		}
	}

	//-- create a string to hold the variable links
	$linklevels="";
	for($j=0; $j<$placelist_controller->level; $j++) {
		$linklevels .= "&amp;parent[$j]=".urlencode($placelist_controller->parent[$j]);
	}
	$i=0;
	$ct1=count($placelist);

	// -- print the array
	foreach ($placelist as $key => $value) {
		if ($i==0) {
			print "<div class=\"".($map_printed ? "PlaceListMapNavRight" : "PlaceListMapNavCenter")."\">";
			print "\n\t<table class=\"ListTable PlaceListTable\"";
			if ($TEXT_DIRECTION=="rtl") print " dir=\"rtl\"";
			print ">\n\t\t<tr>\n\t\t<td class=\"ListTableHeader\" ";
			if ($ct1 > 20) print "colspan=\"3\"";
			else if ($ct1 > 4) print "colspan=\"2\"";
			print ">&nbsp;";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["place"]["small"]."\" border=\"0\" title=\"".GM_LANG_search_place."\" alt=\"".GM_LANG_search_place."\" />&nbsp;&nbsp;";
			if ($placelist_controller->level > 0) {
				print " ".GM_LANG_place_list_aft." ";
				print PrintReady($num_place);
			}
			else print GM_LANG_place_list;

			print "&nbsp;";
			PrintHelpLink("ppp_placelist_help", "qm");
			print "</td></tr><tr><td class=\"ListTableContent\"><ul>\n\t\t\t";
		}

//		print "<li ";
		if (begRTLText($value))
			 print "<li class=\"rtl\" dir=\"rtl\">";
		else print "<li class=\"ltr\" dir=\"ltr\">";
		print "\n<a href=\"placelist.php?action=".$placelist_controller->action."&amp;level=".($placelist_controller->level + 1).$linklevels;
		print "&amp;parent[$placelist_controller->level]=".urlencode($value)."&amp;select=".$placelist_controller->select."\">";

		if (trim($value)=="") print GM_LANG_unknown;
		else {
			print PrintReady($value);
			if (NameFunctions::HasChinese($value)) {
				print " (".printReady(NameFunctions::GetPinYin($value)).")";
			}
			else if (NameFunctions::HasCyrillic($value)) {
				print " (".printReady(NameFunctions::GetTransliterate($value)).")";
			}
		}
		print "</a></li>\n";
		if ($ct1 > 20){
			if ($i == floor($ct1 / 3)) print "\n\t\t</ul></td>\n\t\t<td class=\"ListTableContent\"><ul>";
			if ($i == floor(($ct1 / 3) * 2)) print "\n\t\t</ul></td>\n\t\t<td class=\"ListTableContent\"><ul>";
		}
		else if ($ct1 > 4 && $i == floor($ct1 / 2)) print "\n\t\t</ul></td>\n\t\t<td class=\"ListTableContent\"><ul>";
	    $i++;
	}
	if ($i>0){
		print "\n\t\t</ul></td></tr>";
		if ($placelist_controller->action != "show" && $placelist_controller->level > 0) {
			print "<tr>\n\t\t<td class=\"ListTableContent\" ";
			if ($ct1 > 20) print "colspan=\"3\"";
			else if ($ct1 > 4) print "colspan=\"2\"";
			print ">\n\t";
			print GM_LANG_view_records_in_place;
			PrintHelpLink("ppp_view_records_help", "qm");
			print "</td></tr><tr><td class=\"ListTableColumnFooter\" ";
			if ($ct1 > 20) print "colspan=\"3\"";
			else if ($ct1 > 4) print "colspan=\"2\"";
			print ">";
			print "<a href=\"placelist.php?select=".$placelist_controller->select."&amp;action=show&amp;level=".$placelist_controller->level;
			foreach($placelist_controller->parent as $key=>$value) {
				print "&amp;parent[$key]=".urlencode(trim($value));
			}
			print "\"><span class=\"formField\">";
			if (trim($value)=="") print GM_LANG_unknown;
			else {
				print PrintReady($value);
				if (NameFunctions::HasChinese($value)) print " (".printReady(NameFunctions::GetPinYin($value)).")";
			}
			print "</span></a> ";
			print "</td></tr>";
		}
		print "</table>";
		print "</div>";
	}
}

if ($placelist_controller->level > 0) {
	if ($placelist_controller->action == "show") {
		
		
		// -- array of names
		$positions = $placelist_controller->GetPlacePositions($placelist_controller->parent, $placelist_controller->level, $placelist_controller->select);
		print "<form action=\"placelist.php\" name=\"selectplace\" method=\"get\">";
		print "<input type=\"hidden\" name=\"action\" value=\"".$placelist_controller->action."\" />";
		print "<input type=\"hidden\" name=\"display\" value=\"".$placelist_controller->display."\" />";
		print "<input type=\"hidden\" name=\"level\" value=\"".$placelist_controller->level."\" />";
		$j = 0;
		while (isset($placelist_controller->parent[$j])) {
			print "<input type=\"hidden\" name=\"parent[]\" value=\"".$placelist_controller->parent[$j]."\" />";
			$j++;
		}
		
		//Print the event filter
		print "<div class=\"PlaceListNavContainer\">";
			print "<table class=\"NavBlockTable\">";
			print "<tr><td class=\"NavBlockHeader\" colspan=\"2\">".GM_LANG_filter."</td></tr>";
			print "<tr>";
			print "<td class=\"NavBlockLabel\">".GM_LANG_pl_show_event."</td>";
			print "<td class=\"NavBlockField\"><select name=\"select\" onchange=\"document.selectplace.submit(); return false;\">";
			PrintFilterEvent($placelist_controller->select);
			print "</select></td>";
			print "</tr></table>";
		print "</div>";
		
		// print the list of indi's, fams and sources
		print "<div class=\"PlaceListTableContainer\">";
		print "\n\t<table class=\"ListTable PlaceListTable\">\n\t\t<tr>";
		$ci = count($placelist_controller->indi_total);
		$cs = count($placelist_controller->sour_total);
		$cf = count($placelist_controller->fam_total);

		$cnt = ($ci > 0) + ($cf > 0) + ($cs > 0);
		print "<td class=\"ListTableHeader\" colspan=\"".$cnt."\">".GM_LANG_place_list_objects;
		PrintHelpLink("ppp_name_list_help", "qm");
		print "</td></tr>";
		if ($cnt > 0) print "<tr>";
		if ($ci>0) print "<td class=\"ListTableColumnHeader\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".GM_LANG_individuals."</td>";
		if ($cs>0) print "<td class=\"ListTableColumnHeader\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"\" /> ".GM_LANG_sources."</td>";
		if ($cf>0) print "<td class=\"ListTableColumnHeader\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"\" /> ".GM_LANG_families."</td>";
		if ($cnt > 0) print "</tr><tr>";
		if ($ci>0) {
			print "\n\t\t<td class=\"ListTableContent\">";
			print "\n<ul>";
			foreach($positions["INDI"] as $key => $indi) {
				$indi->PrintListPerson();
			}
			print "\n</ul>";
			print "\n\t\t</td>\n\t\t";
		}
		if ($cs>0) {
			print "<td class=\"ListTableContent\">";
			print "\n<ul>";
			foreach($positions["SOUR"] as $key => $source) {
				$source->PrintListSource();
			}
			print "\n</ul>";
			print "\n\t\t</td>\n\t\t";
		}
		if ($cf>0) {
			print "<td class=\"ListTableContent\">";
			print "\n<ul>";
			foreach($positions["FAM"] as $key => $family) {
				$family->PrintListFamily();
			}
			print "</ul></td>";
		}
		if ($cnt > 0) print "\n\t\t</tr><tr>";
		if ($ci>0) {
			print "<td class=\"ListTableColumnFooter\">";
			print GM_LANG_total_indis." ".$ci;
			if (count($placelist_controller->indi_hide) > 0) {
				print "&nbsp;--&nbsp;";
				print GM_LANG_hidden." ".count($placelist_controller->indi_hide);
				PrintHelpLink("privacy_error_help", "qm");
			}
			print "</td>\n";
		}
		if ($cs>0) {
			print "<td class=\"ListTableColumnFooter\">";
			print GM_LANG_total_sources." ".$cs;
			if (count($placelist_controller->sour_hide) > 0) {
				print "&nbsp;--&nbsp;";
				print GM_LANG_hidden." ".count($placelist_controller->sour_hide);
				PrintHelpLink("privacy_error_help", "qm");
			}
			print "</td>\n";
		}
		if ($cf>0) {
			print "<td class=\"ListTableColumnFooter\">";
			print GM_LANG_total_fams." ".$cf;
			if (count($placelist_controller->fam_hide) > 0) {
				print "&nbsp;--&nbsp;";
				print GM_LANG_hidden." ".count($placelist_controller->fam_hide);
				PrintHelpLink("privacy_error_help", "qm");
			}
			print "</td>\n";
		}
		if ($cnt == 0) {
			print "<tr><td class=\"ListTableColumnFooter\">".GM_LANG_no_results."</td>";
		}
		print "</tr>\n\t</table>";
		print "</div>";
		print "</form>";
	}
}

//-- list type display
if ($placelist_controller->display=="list") {
	
	$placelist = ListFunctions::FindPlaceList("");
	
	if (count($placelist)==0) {
		print "<b>".GM_LANG_no_results."</b>";
	}
	else {
		print "\n\t<table class=\"ListTable PlaceListTable\"";
		if ($TEXT_DIRECTION=="rtl") print " dir=\"rtl\"";
		print ">\n\t\t<tr>\n\t\t<td class=\"ListTableHeader\" ";
		$ct = count($placelist);
		print " colspan=\"".($ct>20?"3":"2")."\">&nbsp;";
		print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["place"]["small"]."\" border=\"0\" title=\"".GM_LANG_search_place."\" alt=\"".GM_LANG_search_place."\" />&nbsp;&nbsp;";
		print GM_LANG_place_list2;
		print "&nbsp;";
		PrintHelpLink("ppp_placelist_help2", "qm");
		print "</td></tr><tr><td class=\"ListTableContent\"><ul>\n\t\t\t";
		$i=0;
		foreach($placelist as $indexval => $revplace) {
			$linklevels = "";
			$levels = preg_split ("/,/", $revplace);		// -- split the place into comma seperated values
			$level=0;
			$revplace = "";
			foreach($levels as $indexval => $place) {
				$place = trim($place);
				$linklevels .= "&amp;parent[$level]=".urlencode($place);
				$level++;
				if ($level>1) $revplace .= ", ";
				if ($place=="") $revplace .= GM_LANG_unknown;
				else $revplace .= $place;
			}
//			print "<li";
			if (begRTLText($revplace))
			     print "<li class=\"rtl\" dir=\"rtl\">";
		    else print "<li class=\"ltr\" dir=\"ltr\">";
			print "<a href=\"placelist.php?action=show&amp;display=hierarchy&amp;level=".$level.$linklevels."&amp;select=".$placelist_controller->select."\">";
			if ($LANGUAGE != "chinese" && GedcomConfig::$DISPLAY_PINYIN) {
				if (NameFunctions::HasChinese($revplace)) $revplace .= " (".NameFunctions::GetPinYin($revplace).")";
			}
			if ($LANGUAGE != "russian" && GedcomConfig::$DISPLAY_TRANSLITERATE) {
				if (NameFunctions::HasCyrillic($revplace)) $revplace .= " (".NameFunctions::GetTransliterate($revplace).")";
			}
			print PrintReady($revplace);
			print "</a></li>\n";
			$i++;
			if ($ct > 20){
				if ($i == floor($ct / 3)) print "\n\t\t</ul></td>\n\t\t<td class=\"ListTableContent\"><ul>";
				if ($i == floor(($ct / 3) * 2)) print "\n\t\t</ul></td>\n\t\t<td class=\"ListTableContent\"><ul>";
			}
			else if ($i == floor($ct/2)) print "</ul></td><td class=\"ListTableContent\"><ul>\n\t\t\t";
		}
		print "\n\t\t</ul></td></tr>\n\t\t";
		if ($i>1) {
			print "<tr><td class=\"ListTableColumnFooter\" colspan=\"".($ct>20?"3":"2")."\">";
			if ($i>0) print GM_LANG_total_unic_places." ".$i;
			print "</td></tr>\n";
		}
		print "\n\t\t</table>";
	}
}
print "<div class=\"PlaceListBottomLinks\">";
	print "<a href=\"placelist.php?select=all&amp;display=";
	if ($placelist_controller->display == "list") print "hierarchy\">".GM_LANG_show_place_hierarchy;
	else print "list\">".GM_LANG_show_place_list;
	print "</a><br />\n";
	
	$head = Header::GetInstance("HEAD", "", GedcomConfig::$GEDCOMID);
	if ($head->placeformat != "") {
		print  GM_LANG_form.$head->placeformat;
		PrintHelpLink("ppp_match_one_help", "qm");
	}
	else {
		print GM_LANG_form.GM_LANG_default_form."  ".GM_LANG_default_form_info;
		PrintHelpLink("ppp_default_form_help", "qm");
	}
print "</div>";
print "</div>";
PrintFooter();
?>
