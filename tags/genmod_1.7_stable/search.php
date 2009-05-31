<?php
/**
 * Searches based on user query.
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
 * @version $Id: search.php,v 1.17 2006/04/09 15:53:27 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($view)) $view = "";

// Remove slashes
if (isset($query)) {
	// Reset the "Search" text from the page header
	if ($query == $gm_lang["search"]) {
		unset($query);
		unset($action);
		unset($topsearch);
	}
	else {
		$query = stripslashes($query);
		$myquery = $query;
	}
}
if (!isset($soundex)) $soundex = "Russell";
if (!isset($action)) $action = "";
if (!isset($nameprt)) $nameprt = "";
if (!isset($tagfilter)) $tagfilter = "on";
if (!isset($showasso)) $showasso = "off";
if (!isset($sorton)) $sorton = "";

if (!empty($firstname)) $myfirstname = $firstname;
else {
	unset($firstname);
	$myfirstname = "";
}
if (!empty($lastname)) $mylastname = $lastname;
else {
	unset($lastname);
	$mylastname = "";
}	
if (!empty($place)) $myplace = $place;
else {
	unset($place);
	$myplace = "";
}
if (!empty($year)) $myyear = $year;
else {
	unset($year);
	$myyear = "";
}

// This section is to handle searches entered in the top search box in the themes
if (isset($topsearch)) {
	
	// first set some required variables. Search only in current gedcom, only in indi's.
	$srindi = "yes";
	
	// Enable the default gedcom for search
	$str = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $GEDCOM);
	$$str = "yes";

	// Then see if an ID is typed in. If so, we might want to jump there.
	if (isset($query)) {

		// see if it's an indi ID. If it's found and privacy allows it, JUMP!!!!
		if (find_person_record($query)) {
			if (showLivingNameByID($query)||displayDetailsByID($query)) {
				header("Location: individual.php?pid=".$query."&ged=".$GEDCOM);
				exit;
			}
		}

		// see if it's a family ID. If it's found and privacy allows it, JUMP!!!!
		if (find_family_record($query)) {
			//-- check if we can display both parents
			if (displayDetailsByID($query, "FAM") == true) {
				$parents = find_parents($query);
				if (showLivingNameByID($parents["HUSB"]) && showLivingNameByID($parents["WIFE"])) {
					header("Location: family.php?famid=".$query."&ged=".$GEDCOM);
					exit;
				}
			}
		}

		// see if it's an source ID. If it's found and privacy allows it, JUMP!!!!
		if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) {
			if (find_source_record($query)) {
				header("Location: source.php?sid=".$query."&ged=".$GEDCOM);
				exit;
			}
		}

		// see if it's a repository ID. If it's found and privacy allows it, JUMP!!!!
		if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) {
			if (find_repo_record($query)) {
				header("Location: repo.php?rid=".$query."&ged=".$GEDCOM);
				exit;
			}
		}
	}
}

// Retrieve the gedcoms to search in
$sgeds = array();
if (($ALLOW_CHANGE_GEDCOM) && (count($GEDCOMS) > 1)) {
	foreach ($GEDCOMS as $key=>$ged) {
		$str = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $key);
		if (isset($$str)) $sgeds[] = $key;
	}
}
else $sgeds[] = $GEDCOM;
// If we want to show associated persons, build the list
if ($showasso == "on") get_asso_list();

// Section to gather results for general search
if ($action=="general") {

	//-- perform the search
	if (isset($query)) {
		
		// -- array of names to be used for results. Must be here and empty.
		$indilist = array();
		$sourcelist = array();
		$famlist = array();
		$famlist2 = array();

		// Now see if there is a query left after the cleanup
		if (trim($query)!="") {

			// Write a log entry
			$logstring = "General, ".$query;
			WriteToLog($logstring, "I", "F", $sgeds);
		
			// Cleanup the querystring so it can be used in a database query
			// Note: when more than one word is entered, this will return results where one word
			// is in one subrecord, another in another subrecord. Theze results are filtered later.
			if (strlen($query) == 1) $query = preg_replace(array("/\?/", "/\|/", "/\*/"), array("\\\?","\\\|", "\\\\\*") , $query);
			if ($REGEXP_DB) $query = preg_replace(array("/\(/", "/\)/", "/\//", "/\]/", "/\[/", "/\s+/"), array('\(','\)','\/','\]','\[', '.*'), $query);
			else {
				$query = "%".preg_replace("/\s+/", "%", $query)."%";
			}
			
			// Search the indi's
			if ((isset($srindi)) && (count($sgeds)>0)) {
				$indilist = search_indis($query, $sgeds);
			}

			// Search the fams
			if ((isset($srfams)) && (count($sgeds)>0)) {
				// Get the indilist, to check name hits. Store the ID's/names found in
				// the search array, so the fam records can be retrieved.
				// This way we include hits on family names.
				// If indi's are not searched yet, we have to search them first
				if (!isset($srindi)) $indilist = search_indis($query, $sgeds);
				$famquery = array();
				$cntgeds = count($sgeds);
				foreach($indilist as $key1 => $myindi) {
					foreach($myindi["names"] as $key2 => $name) {
						if ((preg_match("/".$query."/i", $name[0]) > 0)) {
							if ($cntgeds > 1) {
								$ged = splitkey($key1, "ged");
								$key1 = splitkey($key1, "id");
							}
							else $ged = $sgeds[0];
							$famquery[] = array($key1, $ged);
							break;
						}
					}
				}
				// Get the famrecs with hits on names from the family table
				if (!empty($famquery)) $famlist = search_fams_names($famquery, "OR", true, $cntgeds);
				// Get the famrecs with hits in the gedcom record from the family table
				if (!empty($query)) $famlist2 = search_fams($query, $sgeds, "OR", true);
				$famlist = gm_array_merge($famlist, $famlist2);
//				// clear the myindilist is no search was intended for indi's
//				if (!isset($srindi)) $indilist = array();

			}

			// Search the sources
			if ((isset($srsour)) && (count($sgeds)>0)) {
				if (!empty($query)) $sourcelist = search_sources($query, $sgeds);
			}

			//-- if only 1 item is returned, automatically forward to that item
			// Check for privacy first. If ID cannot be displayed, continue to the search page.
			if ((count($indilist)==1)&&(count($famlist)==0)&&(count($sourcelist)==0) && (isset($srindi))) {
				foreach($indilist as $key=>$indi) {
					if (count($sgeds) > 1) {
						$ged = splitkey($key, "ged");
						$pid = splitkey($key, "id");
						if ($GEDCOM != $ged) {
							$oldged = $GEDCOM;
							$GEDCOM = $ged;
							ReadPrivacy($GEDCOM);
						}
					}
					else {
						$pid = $key;
						$key = $key."[".$indi["gedfile"]."]";
					}
					if (!isset($assolist[$key])) {
						if (showLivingNameByID($pid)||displayDetailsByID($pid)) {
							header("Location: individual.php?pid=".$pid."&ged=".get_gedcom_from_id($indi["gedfile"]));
							exit;
						}
					}
					if ((count($sgeds> 1)) && (isset($oldged))) {
						$GEDCOM = $oldged;
						ReadPrivacy($GEDCOM);
					}
				}
			}
			if ((count($indilist)==0 || !isset($srindi))&&(count($famlist)==1)&&(count($sourcelist)==0)) {
				foreach($famlist as $famid=>$fam) {
					if (count($sgeds) >1) {
						$ged = splitkey($famid, "ged");
						$famid = splitkey($famid, "id");
						if ($GEDCOM != $ged) {
							$oldged = $GEDCOM;
							$GEDCOM = $ged;
							ReadPrivacy($GEDCOM);
						}
					}
					if (displayDetailsByID($famid, "FAM") == true) {
						$parents = find_parents($famid);
						if (showLivingNameByID($parents["HUSB"]) && showLivingNameByID($parents["WIFE"])) {
							header("Location: family.php?famid=".$famid."&ged=".$GEDCOM);
							exit;
						}
					}
					if (count($sgeds> 1)) {
						$GEDCOM = $oldged;
						ReadPrivacy($GEDCOM);
					}
				}
			}
			if ((count($indilist)==0 || !isset($srindi))&&(count($famlist)==0)&&(count($sourcelist)==1)) {
				foreach($sourcelist as $sid=>$source) {
					if (count($sgeds) >1) {
						$ged = splitkey($sid, "ged");
						$sid = splitkey($sid, "id");
						if ($GEDCOM != $ged) {
							$oldged = $GEDCOM;
							$GEDCOM = $ged;
							ReadPrivacy($GEDCOM);
						}
					}
					if (displayDetailsByID($sid, "SOUR")) {
						header("Location: source.php?sid=".$sid."&ged=".get_gedcom_from_id($source["gedfile"]));
						exit;
					}
					if (count($sgeds> 1)) {
						$GEDCOM = $oldged;
						ReadPrivacy($GEDCOM);
					}
				}
			}
		}
	}
}

if ($action=="soundex") {
	if (((!empty($lastname))||(!empty($firstname))||(!empty($place)))&&(count($sgeds)>0)) {
		$logstring = "Soundex, ";
		if (!empty($lastname)) $logstring .= "Last name: ".$lastname."<br />";
		if (!empty($firstname)) $logstring .= "First name: ".$firstname."<br />";
		if (!empty($place)) $logstring .= "Place: ".$place."<br />";
		if (!empty($year)) $logstring .= "Year: ".$year."<br />";
		WriteToLog($logstring, "I", "F", $sgeds);
		
    	if (isset($lastname)) {
	    	// see if there are brackets around letter(groups)
	    	$bcount = substr_count($lastname, "[");
	    	if (($bcount == substr_count($lastname, "]")) && $bcount > 0) {
		    	$barr = array();
			    $ln = $lastname;
			    $pos = 0;
			    $npos = 0;
		    	for ($i=0; $i<$bcount; $i++) {
					$pos1 = strpos($ln, "[")+1;
					$pos2 = strpos($ln, "]");
					$barr[$i] = array(substr($ln, $pos1, $pos2-$pos1), $pos1+$npos-1, $pos2-$pos1);
					$npos = $npos + $pos2 - 1;
					$ln = substr($ln, $pos2+1);
				}
				// Then strip the brackets so we can search
				$lastname = preg_replace(array("/\[/", "/\]/"), array("",""), $lastname);
			}
		}
		if (isset($year)) {
			// We must be able to search regexp in the DB for years
	    	if (strlen($year) == 1) $year = preg_replace(array("/\?/", "/\|/", "/\*/"), array("\\\?","\\\|", "\\\\\*") , $year);
			if ($REGEXP_DB) $year = preg_replace(array("/\s+/", "/\(/", "/\)/"), array(".*",'\(','\)'), $year);
			else {
				$year = "%".preg_replace("/\s+/", "%", $year)."%";
			}		
		}
		$indilist = array();
			
		if ($soundex == "DaitchM") DMsoundex("", "opencache");

		// Do some preliminary stuff: determine the soundex codes for the search criteria
		if (!empty($lastname)) {
			$lastnames = preg_split("/\s/", trim($lastname));
			$larr = array();
			for($j=0; $j<count($lastnames); $j++) {
				if ($soundex == "Russell") $larr[$j] = soundex($lastnames[$j]);
				if ($soundex == "DaitchM") $larr[$j] = DMsoundex($lastnames[$j]);
			}
		}
		if (!empty($firstname)) {
			$firstnames = preg_split("/\s/", trim($firstname));
			$farr = array();
			for($j=0; $j<count($firstnames); $j++) {
				if ($soundex == "Russell") $farr[$j] = soundex($firstnames[$j]);
				if ($soundex == "DaitchM") $farr[$j] = DMsoundex($firstnames[$j]);
			}
		}
		if ((!empty($place)) && ($soundex == "DaitchM")) $parr = DMsoundex($place);
		if ((!empty($place)) && ($soundex == "Russell")) $parr = soundex(trim($place));
		
		// Start the search
		$oldged = $GEDCOM;
		$printname = array();
		$printfamname = array();
		
		// Build the query
		$sql = "SELECT i_id, i_gedcom, i_file FROM ".$TBLPREFIX."individuals WHERE (";
		if (isset($farr)) {
			$i = 0;
			foreach ($farr as $key => $code) {
				if ($soundex == "Russell") {
					if ($i > 0) $sql .= " OR ";
					$i++;
					$sql .= "i_fnsoundex LIKE '%".$code."%'";
				}
				else {
					foreach ($code as $key2 => $value) {
						if ($i > 0) $sql .= " OR ";
						$i++;
						$sql .= "i_fndmsoundex LIKE '%".$value."%'";
					}
				}
			}
			$sql .= ")";
		}
		if (isset($larr)) {
			if (isset($farr)) $sql .= " AND (";
			$i = 0;
			foreach ($larr as $key => $code) {
				if ($soundex == "Russell") {
					if ($i > 0) $sql .= " OR ";
					$i++;
					$sql .= "i_snsoundex LIKE '%".$code."%'";
				}
				else {
					foreach ($code as $key2 => $value) {
						if ($i > 0) $sql .= " OR ";
						$i++;
						$sql .= "i_sndmsoundex LIKE '%".$value."%'";
					}
				}
			}
			$sql .= ")";
		}
		if (count($sgeds) != count($GEDCOMS)) {
			if (isset($farr) || isset($larr)) $sql .= " AND (";
			$i = 0;
			foreach ($sgeds as $key => $gedcom) {
				if ($i != 0) $sql .= " OR ";
				$i++;
				$sql .= "i_file='".$GEDCOMS[$gedcom]["id"]."'";
			}
			$sql .= ")";
		}
		$res = mysql_query($sql);
		$indilist = array();
		if ($res) {
			while ($row = mysql_fetch_assoc($res)) {
				$indi = array();
				$indi["gedcom"] = $row["i_gedcom"];
				$indi["gedfile"] = $row["i_file"];
				$indi["names"] = get_indi_names($indi["gedcom"]);
				$key = $row["i_id"]."[".$row["i_file"]."]";
				$indilist[$key] = $indi;
			}
		}
		// -- Check the result on places
		foreach ($indilist as $key => $value) {
			$save = true;
			$ikey = splitkey($key, "id");
			if ((!empty($place))||(!empty($year))) {
				$indirec = $value["gedcom"];
				if (!empty($place)) {
					$savep=false;
					$pt = preg_match_all("/\d PLAC (.*)/i", $indirec, $match, PREG_PATTERN_ORDER );
					if ($pt>0) {
						$places = array();
						for ($pp=0; $pp<count($match[1]); $pp++){
							$places[$pp] =preg_split("/,\s/", trim($match[1][$pp]));
						}
						$cp=count($places);
						$p = 0;
						while($p<$cp && $savep == false) {
							$pp = 0;
							while($pp<count($places[$p]) && $savep == false) {
								if ($soundex == "Russell") {
									if (soundex(trim($places[$p][$pp]))==$parr) $savep = true;
								}
								if ($soundex == "DaitchM") {
									$arr1 = DMsoundex(trim($places[$p][$pp]));
									$a = array_intersect($arr1, $parr);
									if (!empty($a)) $savep = true;
								}
								$pp++;
							}
							$p++;
						}
					}
					if ($savep == false) $save = false;
				}
				// Check the result on years
				if (!empty($year) && $save==true) {
					$yt = preg_match("/\d DATE (.*$year.*)/i", $indirec, $match);
					if ($yt==0) $save = false;
				}
			}
			// Add either all names or names with a hit
			if ($save === true) {
				if ($nameprt == "all") {
					foreach($value["names"] as $indexval => $namearray) {
						$printname[] = array(sortable_name_from_name($namearray[0]), $ikey, get_gedcom_from_id($value["gedfile"]),"");
					}
				}
				else {
					foreach($value["names"] as $indexval => $namearray) {
						$print = false;
						$name = split("/", $namearray[0]);
						if (!empty($lastname)) {
							$lnames = preg_split("/\s/", trim($name[1]));
							foreach ($lnames as $namekey => $name) {
								if ($soundex == "DaitchM") {
									$lndm = DMSoundex($name);
									foreach($larr as $arrkey => $arrvalue) {
										$a = array_intersect($lndm, $arrvalue);
										if (!empty($a)) {
											$print = true;
											break;
										}
									}
								}
								else {
									if (in_array(soundex($name), $larr)) $print = true;
								}
								if ($print) break;
							}
						}
						if (!empty($firstname) && $print == false) {
							$fnames = preg_split("/\s/", trim($name[0]));
							foreach ($fnames as $namekey => $name) {
								if ($soundex == "DaitchM") {
									$fndm = DMSoundex($name);
									foreach($farr as $arrkey => $arrvalue) {
										$a = array_intersect($fndm, $arrvalue);
										if (!empty($a)) {
											$print = true;
											break;
										}
									}
								}
								else {
									if (in_array(soundex($name), $farr)) $print = true;
								}
								if ($print) break;
							}
						}
						if ($print) $printname[] = array(sortable_name_from_name($namearray[0]), $ikey, get_gedcom_from_id($value["gedfile"]),"");
					}
				}
			}
		}
		DMSoundex("", "closecache");
		$GEDCOM = $oldged;
		// check the result on required characters
		if (isset($barr)) {
			foreach ($printname as $pkey=>$pname) {
				$print = true;
				foreach ($barr as $key=>$checkchar) {
					if (str2upper(substr($pname[0], $checkchar[1], $checkchar[2])) != str2upper($checkchar[0])) {
						$print = false;
						break;
					}
				}
				if ($print == false) {
					unset($printname[$pkey]);
				}
			}
		}
		// Now we have the final list of indi's to be printed.
		// We may add the assos at this point.
			if ($showasso == "on") {
			foreach($printname as $key => $pname) {
				$apid = $pname[1]."[".$pname[2]."]";
				// Check if associates exist
				if (isset($assolist[$apid])) {
				// if so, print all indi's where the indi is associated to
					foreach($assolist[$apid] as $indexval => $asso) {
						if ($asso["type"] == "indi") {
							$indi_printed[$indexval] = "1";
							// print all names
							foreach($asso["name"] as $nkey => $assoname) {
								$key = splitkey($indexval, "id");
								$printname[] = array(sortable_name_from_name($assoname[0]), $key, get_gedcom_from_id($asso["gedfile"]), $apid);
							}
						}
						else if ($asso["type"] == "fam") {
							$fam_printed[$indexval] = "1";
							// print all names
							foreach($asso["name"] as $nkey => $assoname) {
								$assosplit = preg_split("/(\s\+\s)/", trim($assoname));
								// Both names have to have the same direction
								if (hasRTLText($assosplit[0]) == hasRTLText($assosplit[1])) {
									$apid2 = splitkey($indexval, "id");
									$printfamname[]=array(check_NN($assoname), $apid2, get_gedcom_from_id($asso["gedfile"]), $apid);
								}
							}
						}
					}
					unset($assolist[$apid]);
				}
			}
		}
		//-- if only 1 item is returned, automatically forward to that item
		if (count($printname)==1) {
			$oldged = $GEDCOM;
			$GEDCOM = $printname[0][2];
			ReadPrivacy($GEDCOM);
			if (showLivingNameByID($printname[0][1])||displayDetailsByID($printname[0][1])) {
				header("Location: individual.php?pid=".$printname[0][1]."&ged=".$printname[0][2]);
				exit;
			}
			else {
				$GEDCOM = $oldged;
				ReadPrivacy($GEDCOM);
			}
		}
		if ($sorton == "last") uasort($printname, "itemsort");
		else uasort($printname, "firstnamesort");
		reset($printname);
	}
}

print_header($gm_lang["search"]);

?>
<script language="JavaScript" type="text/javascript">
<!--
	function checknames(frm) {
		if (frm.action[1].checked) {
			if (frm.year.value!="") {
				message=true;
				if (frm.firstname.value!="") message=false;
				if (frm.lastname.value!="") message=false;
				if (frm.place.value!="") message=false;
				if (message) {
					alert("<?php print $gm_lang["invalid_search_input"]?>");
					frm.firstname.focus();
					return false;
				}
			}
		}
		if (frm.action[0].checked) {
		if (frm.query.value.length<2) {
				alert("<?php print $gm_lang["search_more_chars"]?>");
				frm.query.focus();
				return false;
			}
		}
		return true;
	}
//-->
</script>
<?php
	// print "<div id=\"search_content\">";
		print "<div id=\"search_header\">";
		print $gm_lang["search_gedcom"];
		print "</div>";

if ($view == "preview") {
	// to be done
	//	print "</td><tr><td align=\"center\">".$logstring."</td></tr></table>";
}
else {
	print "<div id=\"search_options\">";
		// start of new searchform
		print "<div class=\"topbottombar\">";
		print_help_link("search_options_help", "qm","search_options");
		print $gm_lang["search_options"];
		print "</div>";
		print "<form method=\"post\" onsubmit=\""?>return checknames(this);<?php print " \" action=\"$SCRIPT_NAME\">";
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		
		// If more than one GEDCOM, switching is allowed AND DB mode is set, let the user select
		if ((count($GEDCOMS) > 1) && ($ALLOW_CHANGE_GEDCOM)) {
			print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
			print $gm_lang["search_geds"];
			print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
			foreach ($GEDCOMS as $key=>$ged) {
				$str = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $key);
				print "<input type=\"checkbox\" ";
				if (($key == $GEDCOM) && ($action == "")) print "checked=\"checked\" ";
				else {
					if (isset($$str)) print "checked=\"checked\" ";
				}
				print "value=\"yes\" name=\"".$str."\""." />".$GEDCOMS[$key]["title"]."<br />";
			}
			print "</td></tr>";
		}
		
		// Show associated persons/fams?
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
		print $gm_lang["search_asso_label"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"checkbox\" name=\"showasso\" value=\"on\" ";
		if ($showasso == "on") print "checked=\"checked\" ";
		print "/>".$gm_lang["search_asso_text"];
		print "</td></tr>";
		
		// switch between general and soundex
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".$gm_lang["search_type"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"radio\" name=\"action\" value=\"general\" ";
		if ($action == "general") print "checked=\"checked\" ";
		print "onclick=\"expand_layer('gsearch'); expand_layer('ssearch');\" />".$gm_lang["search_general"];
		print "<br /><input type=\"radio\" name=\"action\" value=\"soundex\" ";
		if (($action == "soundex") || ($action == "")) print "checked=\"checked\" ";
		print "onclick=\"expand_layer('gsearch'); expand_layer('ssearch');\" />".$gm_lang["search_soundex"];
		print "</td></tr>";
		print "</table>";
	print "</div>";
	
		// The first searchform
	print "<div id=\"gsearch\" style=\"display: ";
		if ($action == "soundex" || $action == "") print "none\">";
		else print "block\">";
		print "<div class=\"topbottombar\">";
		print_help_link("search_enter_terms_help", "qm", "search_general");
		print $gm_lang["search_general"];
		print "</div>";
		
		// search terms
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
		print $gm_lang["enter_terms"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\"><input tabindex=\"1\" type=\"text\" name=\"query\" value=\"";
		if ($action=="general" && isset($myquery)) print $myquery;
		else print "";
		print "\" />";
		print "</td><td class=\"shade3\" style=\"vertical-align: middle; padding: 5px;\" rowspan=\"3\">";
		print "<input tabindex=\"2\" type=\"submit\" value=\"".$gm_lang["search"]."\" /></tr>";
		// Choice where to search
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".$gm_lang["search_inrecs"];
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"checkbox\"";
		if ((isset($srindi)) || ($action == "")) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srindi\" />".$gm_lang["search_indis"]."<br />";
		print "<input type=\"checkbox\"";
		if (isset($srfams)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srfams\" />".$gm_lang["search_fams"]."<br />";
		print "<input type=\"checkbox\"";
		if (isset($srsour)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srsour\" />".$gm_lang["search_sources"]."<br />";
		print "</td>";
		print "</tr>";
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".$gm_lang["search_tagfilter"]."</td>";
		print "<td class=\"shade1\" style=\"padding: 5px;\"><input type=\"radio\" name=\"tagfilter\" value=\"on\" ";
		if (($tagfilter == "on") || ($tagfilter == "")) print "checked=\"checked\" ";
		print ">".$gm_lang["search_tagfon"]."<br /><input type=\"radio\" name=\"tagfilter\" value=\"off\" ";
		if ($tagfilter == "off") print "checked=\"checked\"";
		print " />".$gm_lang["search_tagfoff"];
		print "</td></tr>";

		print "</table>";
	print "</div>";
	
		// The second searchform
	print "<div id=\"ssearch\" style=\"display: ";
		if ($action == "soundex" || $action == "") print "block\">";
		else print "none\">";
		print "<div class=\"topbottombar\">";
		print_help_link("soundex_search_help", "qm");
		print $gm_lang["soundex_search"];
		print "</div>";
		
		print "<table class=\"width100 center $TEXT_DIRECTION\"><tr><td class=\"shade2\">";
		print $gm_lang["firstname_search"];
		print "</td><td class=\"shade1\">";
		print "<input tabindex=\"3\" type=\"text\" name=\"firstname\" value=\"";
		if ($action=="soundex") print $myfirstname;
		print "\" />";

		print "</td><td class=\"shade3\" style=\"vertical-align: middle; text-align: center; padding: 5px;\"  rowspan=\"4\">";
		print "<input tabindex=\"7\" type=\"submit\" value=\"";
		print $gm_lang["search"];
		print "\" />";

		print "</td></tr><tr><td class=\"shade2\">";
		print $gm_lang["lastname_search"];
		print "</td><td class=\"shade1\"><input tabindex=\"4\" type=\"text\" name=\"lastname\" value=\"";
		if ($action=="soundex") print $mylastname;
		print "\" /></td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_place"];
		print "</td><td class=\"shade1\"><input tabindex=\"5\" type=\"text\" name=\"place\" value=\"";
		if ($action=="soundex") print $myplace;
		print "\" /></td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_year"];
		print "</td><td class=\"shade1\"><input tabindex=\"6\" type=\"text\" name=\"year\" value=\"";
		if ($action=="soundex") print $myyear;
		print "\" /></td>";
		print "</tr>";
		print "<tr><td class=\"shade2\" >";
		print $gm_lang["search_soundextype"];
		print "<td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"soundex\" value=\"Russell\" ";
		if (($soundex == "Russell") || ($soundex == "")) print "checked=\"checked\" ";
		print " />".$gm_lang["search_russell"]."<br /><input type=\"radio\" name=\"soundex\" value=\"DaitchM\" ";
		if ($soundex == "DaitchM") print "checked=\"checked\" ";
		print " />".$gm_lang["search_DM"];
		print "</td>";
		print "</td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_prtnames"];
		print "</td><td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"nameprt\" value=\"hit\" ";
		if (($nameprt == "hit") || ($nameprt == "")) print "checked=\"checked\" ";
		print ">".$gm_lang["search_prthit"]."<br /><input type=\"radio\" name=\"nameprt\" value=\"all\" ";
		if ($nameprt == "all") print "checked=\"checked\" ";;
		print " />".$gm_lang["search_prtall"];
		print "</td>";
		print "</td></tr>";
		print "<tr><td class=\"shade2\">";
		print $gm_lang["search_sorton"];
		print "</td><td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"sorton\" value=\"last\" ";
		if (($sorton == "last") || ($sorton == "")) print "checked=\"checked\" ";
		print ">".$gm_lang["lastname_search"]."<br /><input type=\"radio\" name=\"sorton\" value=\"first\" ";
		if ($sorton == "first") print "checked=\"checked\" ";;
		print " />".$gm_lang["firstname_search"];
		print "</td>";
		print "</td></tr>";
		print "</table>";
	print "</div>";
	print "</form>";
}			

// ---- section to search and display results on a general keyword search
if ($action=="general") {
	if ((isset($query)) && ($query!="")) {
		
		//--- Results in these tags will be ignored when the tagfilter is on

		// Never show results in _UID
		if (userIsAdmin($gm_username)) $skiptags = "_UID";
		
		// If not admin, also hide searches in RESN tags
		else $skiptags = "RESN, _UID";
		
		// Add the optional tags
		$skiptags_option = ", _GMU, FILE, FORM, CHAN, SUBM, REFN";
    	if ($tagfilter == "on") $skiptags .= $skiptags_option;
   		$userlevel = GetUserAccessLevel();

		// Keep track of what indi's are already printed to keep a reliable counter
		$indi_printed = array();
		$fam_printed = array();
  
		// init various counters
		init_list_counters();

		// printqueues for indi's and fams
		$printindiname = array();
		$printfamname = array();

		$cti=count($indilist);
		if (($cti>0) && (isset($srindi))) {
			$oldged = $GEDCOM;
			$curged = $GEDCOM;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}

			foreach ($indilist as $key => $value) {
				if (count($sgeds) > 1) {
					$GEDCOM = splitkey($key, "ged");
					$key = splitkey($key, "id");
					if ($GEDCOM != $curged) {
						ReadPrivacy($GEDCOM);
						$curged = $GEDCOM;
						// Recalculate the tags to skip
						$skiptagsged = $skiptags;
						foreach ($global_facts as $gfact => $gvalue) {
				    		if (isset($gvalue["show"])) {
		    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    					}
  					  	}
					}
				}
				//-- make sure that the data that was searched on is not in a private part of the record
				$hit = false;
		    	$found = false;
		    	// First check if the hit is in the key!
		    	if ($tagfilter == "off") {
	    			if (strpos(str2upper($key), str2upper($query)) !== false) $found = true;
	    		}
				if ($found == false) {
		    		$recs = get_all_subrecords($value["gedcom"], "", false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							if (preg_match("/$query/i", $subrec2, $result)>0) {
								$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec2, $result2);
								if (($ct > 0) && (!empty($result2[1]))) {
									$hit = true;
									// if the tag can be displayed, do so
									if (strpos($skiptagsged, $result2[1]) === false) $found = true;
								}
							}
							if ($found == true) break;
						}
						if ($found == true) break;
					}
				}
				if ($found == true) {
					
					// print all names from the indi found
			    	foreach($value["names"] as $indexval => $namearray) {
						$printindiname[] = array(sortable_name_from_name($namearray[0]), $key, get_gedcom_from_id($value["gedfile"]), "");
					}
					$indi_printed[$key."[".$GEDCOM."]"] = "1";
					
					// If associates must be shown, see if we can display them and add them to the print array
					if (($showasso == "on") && (strpos($skiptagsged, "ASSO") === false)) {
						$apid = $key."[".$value["gedfile"]."]";
						// Check if associates exist
						if (isset($assolist[$apid])) {
							// if so, print all indi's where the indi is associated to
							foreach($assolist[$apid] as $indexval => $asso) {
								if ($asso["type"] == "indi") {
									$indi_printed[$indexval] = "1";
									// print all names
									foreach($asso["name"] as $nkey => $assoname) {
										$key = splitkey($indexval, "id");
										$printindiname[] = array(sortable_name_from_name($assoname[0]), $key, get_gedcom_from_id($asso["gedfile"]), $apid);
									}
								}
								else if ($asso["type"] == "fam") {
									$fam_printed[$indexval] = "1";
									// print all names
									foreach($asso["name"] as $nkey => $assoname) {
										$assosplit = preg_split("/(\s\+\s)/", trim($assoname));
										// Both names have to have the same direction
										if (hasRTLText($assosplit[0]) == hasRTLText($assosplit[1])) {
											$apid2 = splitkey($indexval, "id");
											$printfamname[]=array(check_NN($assoname), $apid2, get_gedcom_from_id($asso["gedfile"]), $apid);
										}
									}
								}
							}
						}
					}
				}
				else if ($hit == true) $indi_hide[$key."[".get_gedcom_from_id($value["gedfile"])."]"] = 1;
			}
		}
		// Start output here, because from the indi's we may have printed some fams which need the column header.
		print "<br />";
		print "\n\t<div class=\"search_results\"><table class=\"list_table center $TEXT_DIRECTION\">\n\t\t<tr>";
		if ((count($indilist)>0)&& (isset($srindi))) print "<td class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["people"]."</td>";
		if ((count($famlist)>0) || (count($printfamname)>0)) print "<td class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["families"]."</td>";
		if (count($sourcelist)>0) print "<td class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["sources"]."</td>";
		print "</tr>\n\t\t<tr>";
			
			
		// Print the indis	
		if (count($printindiname)>0) {	
			uasort($printindiname, "itemsort");
			print "<td class=\"shade1 wrap\"><ul>";
			foreach($printindiname as $pkey => $pvalue) {
				$GEDCOM = $pvalue[2];
				if ($GEDCOM != $curged) {
					ReadPrivacy($GEDCOM);
					$curged = $GEDCOM;
				}
				print_list_person($pvalue[1], array(check_NN($pvalue[0]), $pvalue[2]),"", $pvalue[3]);
				print "\n";
			}
			print "\n\t\t</ul>&nbsp;</td>";
			$GEDCOM = $oldged;
			if ($GEDCOM != $curged) {
				ReadPrivacy($GEDCOM);
				$curged = $GEDCOM;
			}
		}
		
		// Process the fams
		$ctf=count($famlist);
		if ($ctf>0 || count($printfamname)>0) {
			$oldged = $GEDCOM;
			$curged = $GEDCOM;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
	
			foreach ($famlist as $key => $value) {
				if (count($sgeds) > 1) {
					$GEDCOM = splitkey($key, "ged");
					$key = splitkey($key, "id");
					if ($GEDCOM != $curged) {
						ReadPrivacy($GEDCOM);
						$curged = $GEDCOM;
						// Recalculate the tags to skip
						$skiptagsged = $skiptags;
						foreach ($global_facts as $gfact => $gvalue) {
				    		if (isset($gvalue["show"])) {
	    						if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    						}
						}
  				  	}
				}

				// lets see where the hit is
			    $found = false;
				// If a name is hit, no need to check for tags
				foreach($value["name"] as $nkey => $famname) {
					if ((preg_match("/".$query."/i", $famname)) > 0) {
						$found = true;
						break;
					}
				}
				$hit = false;
		    	// First check if the hit is in the key!
		    	if (($tagfilter == "off") && ($found == false)) {
		    		if (strpos(str2upper($key), str2upper($query)) !== false) {
			    		$found = true;
			    		$hit = true;
		    		}
	    		}
				// If no hit in a name or ID, check if there is a hit on a valid tag
				if ($found == false) {
					$recs = get_all_subrecords($value["gedcom"], $skiptagsged, false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							if (preg_match("/$query/i",$subrec2, $result)>0) {
								$ct = preg_match("/\d.(\S*).*/i",$subrec2, $result2);
								if (($ct > 0) && (!empty($result2[1]))) {
									$hit = true;
									// if the tag can be displayed, do so
									if (strpos($skiptagsged, $result2[1]) === false) $found = true;
								}
							}
							if ($found == true) break;
						}
						if ($found == true) break;
					}
				}
				if ($found == true) {	
					foreach ($value["name"] as $namekey => $famname) {
						$famsplit = preg_split("/(\s\+\s)/", trim($famname));
						// Both names have to have the same direction
						if (hasRTLText($famsplit[0]) == hasRTLText($famsplit[1])) {
							// do not print if the hit only in the second name. We want it first.
							if (!((preg_match("/".$query."/i", $famsplit[0]) == 0) && (preg_match("/".$query."/i", $famsplit[1]) > 0))) {
								$printfamname[]=array(check_NN($famname), $key, get_gedcom_from_id($value["gedfile"]),"");
							}
						}
					}
					$fam_printed[$key."[".$GEDCOM."]"] = "1";
		    	}
				else if ($hit == true) $fam_hide[$key."[".get_gedcom_from_id($value["gedfile"])."]"] = 1;
			}
			uasort($printfamname, "itemsort");
			print "\n\t\t<td class=\"shade1 wrap\"><ul>";
			foreach($printfamname as $pkey => $pvalue) {
				$GEDCOM = $pvalue[2];
				if ($GEDCOM != $curged) {
					ReadPrivacy($GEDCOM);
					$curged = $GEDCOM;
				}
				print_list_family($pvalue[1], array($pvalue[0], $pvalue[2]), "", $pvalue[3]);
				print "\n";
			}
			print "\n\t\t</ul>&nbsp;</td>";
			$GEDCOM = $oldged;
			if ($GEDCOM != $curged) {
				ReadPrivacy($GEDCOM);
				$curged = $GEDCOM;
			}
		}
		$cts=count($sourcelist);
		if ($cts>0) {
			uasort($sourcelist, "itemsort"); 
			$oldged = $GEDCOM;
			$curged = $GEDCOM;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			
			print "\n\t\t<td class=\"shade1 wrap\"><ul>";
			foreach ($sourcelist as $key => $value) {
				if (count($sgeds) > 1) {
					$GEDCOM = splitkey($key, "ged");
					$key = splitkey($key, "id");
					if ($curged != $GEDCOM) {
						ReadPrivacy($GEDCOM);
						$curged = $GEDCOM;
						// Recalculate the tags to skip
						$skiptagsged = $skiptags;
						foreach ($global_facts as $gfact => $gvalue) {
				    		if (isset($gvalue["show"])) {
		    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    					}
  					  	}
					}
				}
		    	$found = false;
		    	$hit = false;
		    	// First check if the hit is in the key!
		    	if ($tagfilter == "off") {
		    		if (strpos(str2upper($key), str2upper($query)) !== false) {
			    		$found = true;
			    		$hit = true;
		    		}
	    		}
				if ($found == false) {
					$recs = get_all_subrecords($value["gedcom"], $skiptagsged, false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							if (preg_match("/$query/i",$subrec2, $result)>0) {
								$ct = preg_match("/\d.(\S*).*/i",$subrec2, $result2);
								if (($ct > 0) && (!empty($result2[1]))) {
									$hit = true;
									// if the tag can be displayed, do so
									if (strpos($skiptagsged, $result2[1]) === false) $found = true;
								}
							}
							if ($found == true) break;
						}
						if ($found == true) break;
					}
				}
				if ($found == true) print_list_source($key, $value);
				else if ($hit == true) $source_hide[$key."[".get_gedcom_from_id($value["gedfile"])."]"] = 1;
			}
			print "\n\t\t</ul>&nbsp;</td>";
			$GEDCOM = $oldged;
			if ($GEDCOM != $curged) {
				ReadPrivacy($GEDCOM);
				$curged = $GEDCOM;
			}
		}
		print "</tr><tr>\n\t";
		$cti = count($indi_printed);
		$ctf = count($fam_printed);
		if ($cti > 0 || $cts > 0 || $ctf > 0) {
			if (($cti > 0) && (isset($srindi))) {
				print "<td>".$gm_lang["total_indis"]." ".$cti;
				if (count($indi_private)>0) print "  (".$gm_lang["private"]." ".count($indi_private).")";
				if (count($indi_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($indi_hide);
				if (count($indi_private)>0 || count($indi_hide)>0) print_help_link("privacy_error_help", "qm");
				print "</td>";
			}
			if ($ctf > 0) {
				print "<td>".$gm_lang["total_fams"]." ".$ctf;
				if (count($fam_private)>0) print "  (".$gm_lang["private"]." ".count($fam_private).")";
				if (count($fam_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($fam_hide);
				if (count($fam_private)>0 || count($fam_hide)>0) print_help_link("privacy_error_help", "qm");
				print "</td>";
			}
			if ($cts > 0) {
				print "<td>".$gm_lang["total_sources"]." ".$cts;
				if (count($source_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($source_hide);
				print "</td>";
			}
			if ($cti > 0 || $cts > 0 || $ctf > 0) print "</tr>\n\t";
		}
		else if (isset($query)) print "<td class=\"warning\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i><br /></td></tr>\n\t\t";
		print "</table></div>";
	}
	else if (isset($query)) print "<br /><div class=\"warning\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i><br /></div>\n\t\t";
}

// ----- section to search and display results for a Soundex last name search
if ($action=="soundex") {
	if ($soundex == "DaitchM") DMsoundex("", "closecache");
// 	$query = "";	// Stop function PrintReady from doing strange things to accented names
	if (((!empty($lastname))||(!empty($firstname)) ||(!empty($place))) && (isset($printname))) {
		print "<div class=\"search_results\"><br />";
		print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr>\n\t\t";
		$i=0;
		$ct=count($printname);
		if ($ct > 0) {
			init_list_counters();
			$oldged = $GEDCOM;
			$curged = $GEDCOM;
			$extrafams = false;
			if (count($printfamname)>0) $extrafams = true;
			if ($extrafams) {
				print "<td class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["people"]."</td>";
				print "<td class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["families"]."</td>";
			}
			else print "<td colspan=\"2\" class=\"topbottombar\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".$gm_lang["people"]."</td>";
			print "</tr><tr>\n\t\t<td class=\"shade1 wrap\"><ul>";

			foreach ($printname as $key => $pvalue) {
				$GEDCOM = $pvalue[2];
				if ($GEDCOM != $curged) {
					ReadPrivacy($GEDCOM);
					$curged = $GEDCOM;
				}
				print_list_person($pvalue[1], array(check_NN($pvalue[0]), $pvalue[2]), "", $pvalue[3]);
				$indiprinted[$pvalue[1]."[".$pvalue[2]."]"] = 1;
				print "\n";
				if (!$extrafams) {
					if ($i == floor($ct / 2) && $ct>9) print "\n\t\t</ul></td>\n\t\t<td class=\"list_value_wrap\"><ul>";
					$i++;
				}
			}
			$GEDCOM = $oldged;
			if ($GEDCOM != $curged) {
				ReadPrivacy($GEDCOM);
				$curged = $GEDCOM;
			}
			print "\n\t\t</ul></td>";

			// Start printing the associated fams
			if ($extrafams) {
				uasort($printfamname, "itemsort");
				print "\n\t\t<td class=\"shade1 wrap\"><ul>";
				foreach($printfamname as $pkey => $pvalue) {
					$GEDCOM = $pvalue[2];
					if ($GEDCOM != $curged) {
						ReadPrivacy($GEDCOM);
						$curged = $GEDCOM;
					}
					print_list_family($pvalue[1], array($pvalue[0], $pvalue[2]), "", $pvalue[3]);
					print "\n";
				}
				print "\n\t\t</ul>&nbsp;</td>";
				$GEDCOM = $oldged;
				if ($GEDCOM != $curged) {
					ReadPrivacy($GEDCOM);
					$curged = $GEDCOM;
				}
			}

			// start printing the table footer
			print "\n\t\t</tr>\n\t";
			if (count($indi_total)>0) {
				print "<tr><td ";
				if ((!$extrafams) && ($ct > 9)) print "colspan=\"2\">";
				else print ">";
				print $gm_lang["total_indis"]." ".count($indi_total);
				if (count($indi_private)>0) print "  (".$gm_lang["private"]." ".count($indi_private).")";
				if (count($indi_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($indi_hide);
				if (count($indi_private)>0 || count($indi_hide)>0) print_help_link("privacy_error_help", "qm");
				print "</td>";
				if ($extrafams) {
					print "<td>".$gm_lang["total_fams"]." ".count($fam_total);
					if (count($fam_private)>0) print "  (".$gm_lang["private"]." ".count($fam_private).")";
					if (count($fam_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($fam_hide);
					if (count($fam_private)>0 || count($fam_hide)>0) print_help_link("privacy_error_help", "qm");
					print "</td>";
				}
				print "</tr>";
			}
		}
		else if (!isset($topsearch)) print "<td class=\"warning\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i></td></tr>\n\t\t";
		print "</table></div>";
	}
	else if (!isset($topsearch)) print "<br /><div class=\"warning\" style=\" text-align: center;\"><i>".$gm_lang["no_results"]."</i><br /></div>\n\t\t";
}
print "<br /><br /><br />";
print_footer();
?>
