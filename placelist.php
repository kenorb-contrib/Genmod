<?php
/**
 * Displays a place hierachy
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

function case_in_array($value, $array) {
	foreach($array as $key=>$val) {
		if (strcasecmp($value, $val)==0) return true;
	}
	return false;
}

function ComparePlace($parray, $location) {
	$location = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $location);
	$places = preg_split("/,/", $location);
	$secalp = array_reverse($places);
//		print "<br /><br />";
//		print_r($parray);
//		print_r($secalp);
	foreach($parray as $key => $part) {
		if (!isset($secalp[$key]) || $part != trim($secalp[$key])) return false;
	}
	return true;
}


if (empty($action)) $action = "find";
if (empty($display)) $display = "hierarchy";
if (empty($select)) $select = "all";

if ($display=="hierarchy") print_header($gm_lang["place_list"]);
else print_header($gm_lang["place_list2"]);

print "\n\t<div class=\"center\" >";
if ($display=="hierarchy") print "<h3>".$gm_lang["place_list"]."</h3>\n\t";
else print "<h3>".$gm_lang["place_list2"]."</h3>\n\t";

if (!isset($parent)) $parent=array();
else {
	if (!is_array($parent)) $parent = array();
	else $parent = array_values($parent);
}
// Remove slashes
foreach ($parent as $p => $child){
	$parent[$p] = stripslashes($child);
}

if (!isset($level)) {
	$level=0;
}

if ($level>count($parent)) $level = count($parent);
if ($level<count($parent)) $level = 0;

//-- extract the place form encoded in the gedcom
$header = FindGedcomRecord("HEAD");
$hasplaceform = strpos($header, "1 PLAC");

//-- hierarchical display
if ($display=="hierarchy") {
	// -- array of names
	$placelist = array();
	$positions = array();
	$numfound = 0;
	GetPlaceList();
	// -- sort the array
	uasort($placelist, "stringsort");

	//-- create a query string for passing to search page
	$tempparent = array_reverse($parent);
	if (count($tempparent)>0) $squery = "&query=".urlencode($tempparent[0]);
	else $squery="";
	for($i=1; $i<$level; $i++) {
		$squery.=", ".urlencode($tempparent[$i]);
	}

	//-- if the number of places found is 0 then automatically redirect to search page
	if ($numfound==0) {
		$action="show";
	}

	// -- print the breadcrumb hierarchy
	$numls=0;
	if ($level>0) {
		//-- link to search results
		if ((($level>1)||($parent[0]!=""))&&($numfound>0)) {
			print $numfound."  ".$gm_lang["connections"].": ";
		}
		//-- breadcrumb
		$numls = count($parent)-1;
		$num_place="";
		//-- place and page text orientation is opposite -> top level added at the beginning of the place text
		print "<a href=\"placelist.php?level=0&amp;select=$select\">";
		if ($numls>=0 && (($TEXT_DIRECTION=="ltr" && hasRtLText($parent[$numls])) || ($TEXT_DIRECTION=="rtl" && !hasRtLText($parent[$numls])))) print $gm_lang["top_level"].", ";
		print "</a>";
	    for($i=$numls; $i>=0; $i--) {
			print "<a href=\"placelist.php?level=".($i+1)."&amp;";
			for ($j=0; $j<=$i; $j++) {
				$levels = preg_split ("/,/", trim($parent[$j]));
				// Routine for replacing ampersands
				foreach($levels as $pindex=>$ppart) {
					$ppart = urlencode($ppart);
					$ppart = preg_replace("/amp\%3B/", "", trim($ppart));
					print "&amp;parent[$j]=".$ppart;
				}
			}
 			print "&amp;select=$select\">";
 			if (trim($parent[$i])=="") print $gm_lang["unknown"];
			else {
				print PrintReady($parent[$i]);
				if (HasChinese($parent[$i])) print " (".printReady(GetPinYin($parent[$i])).")";
			}
			print "</a>";
 			if ($i>0) print ", ";
 			else if (($TEXT_DIRECTION=="rtl" && hasRtLText($parent[$i])) || ($TEXT_DIRECTION=="ltr" &&  !hasRtLText($parent[$i])))  print ", ";
			if (empty($num_place)) $num_place=$parent[$i];
		}
	}
	print "<a href=\"placelist.php?level=0&amp;select=$select\">";
	//-- place and page text orientation is the same -> top level added at the end of the place text
	if ($level==0 || ($numls>=0 && (($TEXT_DIRECTION=="rtl" && hasRtLText($parent[$numls])) || ($TEXT_DIRECTION=="ltr" && !hasRtLText($parent[$numls]))))) print $gm_lang["top_level"];
	print "</a>";

	print_help_link("ppp_levels_help", "qm");

	// show clickable map if found
	print "\n\t<br /><br />\n\t<table class=\"width90 center\"><tr><td class=\"center\">";
	if ($level>=1 and $level<=3) {
		$country = $parent[0];
		if ($country == "\xD7\x99\xD7\xA9\xD7\xA8\xD7\x90\xD7\x9C") $country = "ISR"; // Israel hebrew name
		$country = strtoupper($country);
		if (strlen($country)!=3) {
			// search country code using current language countries table
			require($GM_BASE_DIRECTORY."languages/countries.en.php");
			// changed $LANGUAGE to $deflang (the language set for the current gedcom)	// eikland
			// changed to $GEDCOMLANG sjouke
			if (file_exists($GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$GEDCOMLANG].".php")) require($GM_BASE_DIRECTORY."languages/countries.".$lang_short_cut[$GEDCOMLANG].".php");
			foreach ($countries as $countrycode => $countryname) {
				if (strtoupper($countryname) == $country) {
					$country = $countrycode;
					break;
				}
			}
			if (strlen($country)!=3) $country=substr($country,0,3);
		}
		$mapname = $country;
		$areaname = $parent[0];
		$imgfile = "places/".$country."/".$mapname.".gif";
		$mapfile = "places/".$country."/".$country.".".$lang_short_cut[$LANGUAGE].".htm";
		if (!file_exists($mapfile)) $mapfile = "places/".$country."/".$country.".htm";

		if ($level>1) {
			$state = SmartUtf8Decode($parent[1]);
			$mapname .= "_".$state;
			if ($level>2) {
				$county = SmartUtf8Decode($parent[2]);
				$mapname .= "_".$county;
				$parent[2] = str_replace("'","\'",$parent[2]);
				$areaname = $parent[2];
			}
			else {
				$parent[1] = str_replace("'","\'",$parent[1]);
				$areaname = $parent[1];
			}
			$mapname = strtr($mapname,"���������������������������������������������������������������������' ","SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy--");
			$imgfile = "places/".$country."/".$mapname.".gif";
		}
		if (file_exists($imgfile) and file_exists($mapfile)) {
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
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=2<?php print "&parent[0]=".$parent[0]."&parent[1]="?>'+search);
				// search without optional code [California]
				txt = txt.replace(/(\/)/,' ('); // case: finnish/swedish ==> finnish (swedish
				p=txt.indexOf(' (');
				if (p>1) search=txt.substring(0,p);
				else return;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=2<?php print "&parent[0]=".$parent[0]."&parent[1]="?>'+search);
				// search with code only [CA]
				search=txt.substring(p+2);
				p=search.indexOf(')');
				if (p>1) search=search.substring(0,p);
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=2<?php print "&parent[0]=".$parent[0]."&parent[1]="?>'+search);
			}
			function setPlaceCounty(txt) {
				if (txt=='') return;
				var search = txt;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=3<?php print "&parent[0]=".$parent[0]."&parent[1]=".@$parent[1]."&parent[2]="?>'+search);
				txt = txt.replace(/(\/)/,' (');
				p=txt.indexOf(' (');
				if (p>1) search=txt.substring(0,p);
				else return;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=3<?php print "&parent[0]=".$parent[0]."&parent[1]=".@$parent[1]."&parent[2]="?>'+search);
				search=txt.substring(p+2);
				p=search.indexOf(')');
				if (p>1) search=search.substring(0,p);
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=3<?php print "&parent[0]=".$parent[0]."&parent[1]=".@$parent[1]."&parent[2]="?>'+search);
			}
			function setPlaceCity(txt) {
				if (txt=='') return;
				var search = txt;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=4<?php print "&parent[0]=".$parent[0]."&parent[1]=".@$parent[1]."&parent[2]=".@$parent[2]."&parent[3]="?>'+search);
				txt = txt.replace(/(\/)/,' (');
				p=txt.indexOf(' (');
				if (p>1) search=txt.substring(0,p);
				else return;
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=4<?php print "&parent[0]=".$parent[0]."&parent[1]=".@$parent[1]."&parent[2]=".@$parent[2]."&parent[3]="?>'+search);
				search=txt.substring(p+2);
				p=search.indexOf(')');
				if (p>1) search=search.substring(0,p);
				if (places_accept.in_array(search)) return(location.href = 'placelist.php?level=4<?php print "&parent[0]=".$parent[0]."&parent[1]=".@$parent[1]."&parent[2]=".@$parent[2]."&parent[3]="?>'+search);
			}
			//-->
			</script>
			<?php
			print "</td><td style=\"margin-left:15; vertical-align: top;\">";
		}
	}

	//-- create a string to hold the variable links
	$linklevels="";
	for($j=0; $j<$level; $j++) {
		$linklevels .= "&amp;parent[$j]=".urlencode($parent[$j]);
	}
	$i=0;
	$ct1=count($placelist);

	// -- print the array
	foreach ($placelist as $key => $value) {
		if ($i==0) {
			print "\n\t<br />\n\t<table align=\"center\" class=\"list_table $TEXT_DIRECTION\"";
			if ($TEXT_DIRECTION=="rtl") print " dir=\"rtl\"";
			print ">\n\t\t<tr>\n\t\t<td class=\"shade2 center\" ";
			if ($ct1 > 20) print "colspan=\"3\"";
			else if ($ct1 > 4) print "colspan=\"2\"";
			print ">&nbsp;";
			print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["place"]["small"]."\" border=\"0\" title=\"".$gm_lang["search_place"]."\" alt=\"".$gm_lang["search_place"]."\" />&nbsp;&nbsp;";
			if ($level>0) {
				print " ".$gm_lang["place_list_aft"]." ";
				print PrintReady($num_place);
			}
			else print $gm_lang["place_list"];

			print "&nbsp;";
			print_help_link("ppp_placelist_help", "qm");
			print "</td></tr><tr><td class=\"shade1 center\"><ul>\n\t\t\t";
		}

//		print "<li ";
		if (begRTLText($value))
			 print "<li class=\"rtl\" dir=\"rtl\"";
		else print "<li class=\"ltr\" dir=\"ltr\"";
		print " type=\"square\">\n<a href=\"placelist.php?action=$action&amp;level=".($level+1).$linklevels;
		print "&amp;parent[$level]=".urlencode($value)."&amp;select=$select\" class=\"shade1\">";

		if (trim($value)=="") print $gm_lang["unknown"];
		else {
			print PrintReady($value);
			if (HasChinese($value)) {
				print " (".printReady(GetPinYin($value)).")";
			}
		}
		print "</a></li>\n";
		if ($ct1 > 20){
			if ($i == floor($ct1 / 3)) print "\n\t\t</ul></td>\n\t\t<td class=\"shade1\"><ul>";
			if ($i == floor(($ct1 / 3) * 2)) print "\n\t\t</ul></td>\n\t\t<td class=\"shade1\"><ul>";
		}
		else if ($ct1 > 4 && $i == floor($ct1 / 2)) print "\n\t\t</ul></td>\n\t\t<td class=\"shade1\"><ul>";
	    $i++;
	}
	if ($i>0){
		print "\n\t\t</ul></td></tr>";
		if (($action!="show")&&($level>0)) {
			print "<tr>\n\t\t<td class=\"shade2 center\" ";
			if ($ct1 > 20) print "colspan=\"3\"";
			else if ($ct1 > 4) print "colspan=\"2\"";
			print ">\n\t";
			print $gm_lang["view_records_in_place"];
			print_help_link("ppp_view_records_help", "qm");
			print "</td></tr><tr><td class=\"shade1\" ";
			if ($ct1 > 20) print "colspan=\"3\"";
			else if ($ct1 > 4) print "colspan=\"2\"";
			print " style=\"text-align: center;\">";
			print "<a href=\"placelist.php?select=$select&amp;action=show&amp;level=$level";
			foreach($parent as $key=>$value) {
				print "&amp;parent[$key]=".urlencode(trim($value));
			}
			print "\"><span class=\"formField\">";
			if (trim($value)=="") print $gm_lang["unknown"];
			else {
				print PrintReady($value);
				if (HasChinese($value)) print " (".printReady(GetPinYin($value)).")";
			}
			print "</span></a> ";
			print "</td></tr>";
		}
		print "</table>";
	}
	print "</td></tr></table>";

}

if ($level > 0) {
	if ($action=="show") {
		// -- array of names
		$myindilist = array();
		$mysourcelist = array();
		$myfamlist = array();
		
		$positions = GetPlacePositions($parent, $level);
		for($i=0; $i<count($positions); $i++) {
			$gid = $positions[$i];
			$indirec=FindGedcomRecord($gid);
			$ct = preg_match("/0 @(.*)@ (.*)/", $indirec, $match);
			if ($ct>0) {
				$type = trim($match[2]);
				if ($type == "INDI") {
					$prt = false;
					if ($select == "all") $prt = true;
					else {
						$srec = GetSubRecord(1, "1 $select", $indirec);
						$prec = GetGedcomValue("PLAC", 2, $srec);
						$prt = ComparePlace($parent, $prec);
					}
					if ($prt) {
						$myindilist["$gid"] = GetSortableName($gid);
						if (HasChinese($myindilist["$gid"])) $myindilist["$gid"] .= " (".GetSortableAddName($gid, $indirec).")";
					}
				}
				else if ($type == "FAM") {
					$prt = false;
					if ($select == "all") $prt = true;
					else {
						$srec = GetSubRecord(1, "1 $select", $indirec);
						$prec = GetGedcomValue("PLAC", 2, $srec);
						$prt = ComparePlace($parent, $prec);
					}
					if ($prt) {
						$myfamlist["$gid"] = GetFamilyDescriptor($gid);
						if (HasChinese($myfamlist["$gid"])) $myfamlist["$gid"] .= " (".GetFamilyAddDescriptor($gid, false, $indirec).")";
					}
				}
				else if ($type == "SOUR" && $select == "all") {
					$mysourcelist["$gid"] = GetSourceDescriptor($gid);
				}
			}
		}

		print "\n\t<br /><br /><table class=\"list_table $TEXT_DIRECTION center\">\n\t\t<tr>";
		$ci = count($myindilist);
		$cs = count($mysourcelist);
		$cf = count($myfamlist);
		$cnt = ($ci > 0) + ($cf > 0) + ($cs > 0);
		print "<td class=\"shade2 center\" colspan=\"".$cnt."\">";
		print "<form action=\"placelist.php\" name=\"selectplace\" method=\"get\">";
		print "<input type=\"hidden\" name=\"action\" value=\"".$action."\">";
		print "<input type=\"hidden\" name=\"display\" value=\"".$display."\">";
		print "<input type=\"hidden\" name=\"level\" value=\"".$level."\">";
		$j = 0;
		while (isset($parent[$j])) {
			print "<input type=\"hidden\" name=\"parent[]\" value=\"".$parent[$j]."\">";
			$j++;
		}
//		if ($cnt > 0) {
			print $gm_lang["pl_show_event"].":&nbsp";
			print "<select name=\"select\" onchange=\"document.selectplace.submit(); return false;\" />";
			PrintFilterEvent($select);
			print "</select>";
//		}
		print "</form>";
		print "</td></tr><tr>";
		if ($ci>0) print "<td class=\"shade2 center\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["individuals"]."</td>";
		if ($cs>0) print "<td class=\"shade2 center\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["sources"]."</td>";
		if ($cf>0) print "<td class=\"shade2 center\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["families"]."</td>";
		$i=0;
		$indisurnames = array();
		foreach($myindilist as $gid=>$indi) {
				$name = trim($indi);
				$names = preg_split("/,/", $name);
				$indi = CheckNN($names);
				$indisurnames[$gid] = array();
				$indisurnames[$gid]["name"] = $indi;
				$indisurnames[$gid]["gid"] = $gid;
		}
		print "</tr><tr>";
		if ($ci>0) {
			uasort($indisurnames, "ItemSort");
			print "\n\t\t<td class=\"shade1 wrap\">";
			print "\n<ul>";
			foreach ($indisurnames as $indexval => $value) {
	    		print_list_person($value["gid"], array($value["name"], $GEDCOMID));
				$i++;
			}
			/*
			if ($indi_hide>0) {
				print "<li>".$gm_lang["hidden"]." (".$indi_hide.")";
				print_help_link("privacy_error_help", "qm");
				print "</li>";
			}*/
			print "\n</ul>";
			print "\n\t\t</td>\n\t\t";
		}
		if ($cs>0) {
			print "<td class=\"shade1 wrap\">";
			print "\n<ul>";
			asort($mysourcelist);
			$i=0;
			foreach ($mysourcelist as $key => $value) {
			    print "\n\t\t\t";
//			    print "<li ";
			    if (begRTLText($value))
			         print "<li class=\"rtl\" dir=\"rtl\"";
		        else print "<li class=\"ltr\" dir=\"ltr\"";
			    print "type=\"circle\"><a href=\"source.php?sid=$key\"><span class=\"list_item\">".PrintReady($value)."</span></a></li>\n";
			    $i++;
			}
			print "\n</ul>";
			print "<br />\n\t\t</td>\n\t\t";
		}
		$surnames = array();
		foreach($myfamlist as $gid=>$fam) {
			// Added space to regexp after z to also remove prefixes
			$name = StripPrefix($fam);
			$names = preg_split("/[,+]/", $name);
			$surname = $names[0];
			$firstname = "";
			if (isset($names[1])) $firstname = trim($names[1]);
			else $surname = "";
			$surname = Str2Upper(trim($surname));
			if (!isset($surnames[$surname.$firstname.$gid])) {
				$surnames[$surname.$firstname.$gid] = array();
				// Convert names again to include prefixes for displaying
				// That is not needed, everything was ok in $fam!
//				$name = preg_replace(array("/ [jJsS][rR]\.?,/", "/ I+,/", "/\(.*\)/"), array(",",",",""), $fam);
//				$names = preg_split("/[,+]/", $name);
//				$fam = CheckNN($names);
				$surnames[$surname.$firstname.$gid]["name"] = $fam;
				$surnames[$surname.$firstname.$gid]["gid"] = $gid;
			}
		}
		$i=0;
		if (isset($surnames)) $ct=count($surnames);
		if ($ct>0) {
			uasort($surnames, "ItemSort");
			reset($surnames);
			print "<td class=\"shade1 wrap\">";
			print "\n<ul>";
			foreach ($surnames as $indexval => $value) {
				print_list_family($value["gid"], array($value["name"], $GEDCOMID));
				$i++;
			}
			/*if ($fam_hide>0) {
				print "<li>".$gm_lang["hidden"]." (".$fam_hide.")";
				print_help_link("privacy_error_help", "qm");
				print "</li>";
			}*/
			print "</ul></td>";
		}
		print "\n\t\t</tr><tr>";
		if ($ci>0) {
			print "<td>";
			print $gm_lang["total_indis"]." ".$ci;
			if ($indi_private>0) print "&nbsp;(".$gm_lang["private"]." ".count($indi_private).")";
			if ($indi_hide>0) {
				print "&nbsp;--&nbsp;";
				print $gm_lang["hidden"]." ".count($indi_hide);
				print_help_link("privacy_error_help", "qm");
			}
			print "</td>\n";
		}
		if ($cs>0) {
			print "<td>";
			print $gm_lang["total_sources"]." ".$cs;
			print "</td>\n";
		}
		if ($cf>0) {
			print "<td>";
			print $gm_lang["total_fams"]." ".$cf;
			if ($fam_private>0) print "&nbsp;(".$gm_lang["private"]." ".count($fam_private).")";
			if ($fam_hide>0) {
				print "&nbsp;--&nbsp;";
				print $gm_lang["hidden"]." ".count($fam_hide);
				print_help_link("privacy_error_help", "qm");
			}
			print "</td>\n";
		}
		print "</tr>\n\t</table>";
		print_help_link("ppp_name_list_help", "qm");
		print "<br />";
	}
}

//-- list type display
if ($display=="list") {
	$placelist = array();

	FindPlaceList("");
	uasort($placelist, "stringsort");
	if (count($placelist)==0) {
		print "<b>".$gm_lang["no_results"]."</b><br />";
	}
	else {
		print "\n\t<table class=\"list_table $TEXT_DIRECTION\"";
		if ($TEXT_DIRECTION=="rtl") print " dir=\"rtl\"";
		print ">\n\t\t<tr>\n\t\t<td class=\"list_label\" ";
		$ct = count($placelist);
		print " colspan=\"".($ct>20?"3":"2")."\">&nbsp;";
		print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["place"]["small"]."\" border=\"0\" title=\"".$gm_lang["search_place"]."\" alt=\"".$gm_lang["search_place"]."\" />&nbsp;&nbsp;";
		print $gm_lang["place_list2"];
		print "&nbsp;";
		print_help_link("ppp_placelist_help2", "qm");
		print "</td></tr><tr><td class=\"shade1 wrap\"><ul>\n\t\t\t";
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
				if ($place=="") $revplace .= $gm_lang["unknown"];
				else $revplace .= $place;
			}
//			print "<li";
			if (begRTLText($revplace))
			     print "<li class=\"rtl\" dir=\"rtl\"";
		    else print "<li class=\"ltr\" dir=\"ltr\"";
			print "type=\"square\"><a href=\"placelist.php?action=show&amp;display=hierarchy&amp;level=$level$linklevels&amp;select=$select\">";
			print PrintReady($revplace)."</a></li>\n";
			$i++;
			if ($ct > 20){
				if ($i == floor($ct / 3)) print "\n\t\t</ul></td>\n\t\t<td class=\"shade1 wrap\"><ul>";
				if ($i == floor(($ct / 3) * 2)) print "\n\t\t</ul></td>\n\t\t<td class=\"shade1 wrap\"><ul>";
			}
			else if ($i == floor($ct/2)) print "</ul></td><td class=\"shade1 wrap\"><ul>\n\t\t\t";
		}
		print "\n\t\t</ul></td></tr>\n\t\t";
		if ($i>1) {
			print "<tr><td>";
			if ($i>0) print $gm_lang["total_unic_places"]." ".$i;
			print "</td></tr>\n";
		}
		print "\n\t\t</table>";
	}
}

print "<br /><a href=\"placelist.php?select=ALL&amp;display=";
if ($display=="list") print "hierarchy\">".$gm_lang["show_place_hierarchy"];
else print "list\">".$gm_lang["show_place_list"];
print "</a><br /><br />\n";
if ($hasplaceform) {
	$placeheader = substr($header, $hasplaceform);
	$ct = preg_match("/2 FORM (.*)/", $placeheader, $match);
	if ($ct>0) {
		print  $gm_lang["form"].$match[1];
		print_help_link("ppp_match_one_help", "qm");
	}
}
else {
	print $gm_lang["form"].$gm_lang["default_form"]."  ".$gm_lang["default_form_info"];
	print_help_link("ppp_default_form_help", "qm");
}

print "<br /><br /></div>";
print_footer();

?>
