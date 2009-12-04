<?php
/**
 * Functions used in or by blocks
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
 * @package Genmod
 * @subpackage Tools
 * @see validategedcom.php
 *
 * $Id$
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class BlockFunctions {
	
	
	/**
	 * get the top surnames
	 * @param int $num	how many surnames to return
	 * @return array
	 */
	public function GetTopSurnames($num) {
		global $GEDCOMID;
	
		//-- Exclude the common surnames to be removed
		$delnames = array();
		if (GedcomConfig::$COMMON_NAMES_REMOVE != "") {
			$delnames = preg_split("/[,;] /", GedcomConfig::$COMMON_NAMES_REMOVE);
		}
		$delstrn = "";
		foreach($delnames as $key => $delname) {
			$delstrn .= " AND n_surname<>'".$delname."'";
		}
		//-- Perform the query
		$surnames = array();
		$sql = "(SELECT COUNT(n_surname) as count, n_surname FROM ".TBLPREFIX."names WHERE n_file='".$GEDCOMID."' AND n_type!='C' AND n_surname<>'@N.N.'".$delstrn." GROUP BY n_surname) ORDER BY count DESC LIMIT ".$num;
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchRow()) {
				if (isset($surnames[Str2Upper($row[1])]["match"])) $surnames[Str2Upper($row[1])]["match"] += $row[0];
				else {
					$surnames[Str2Upper($row[1])]["name"] = $row[1];
					$surnames[Str2Upper($row[1])]["match"] = $row[0];
				}
			}
			$res->FreeResult();
		}
		return $surnames;
	}
	
	public function GetCachedEvents($action, $daysprint, $filter, $onlyBDM="no", $skipfacts) {
		global $gm_lang, $month, $year, $day, $monthtonum, $monthstart;
		global $GEDCOMID, $ASC, $IGNORE_FACTS, $IGNORE_YEAR;
		global $CIRCULAR_BASE;
		
		$found_facts = array();
		$skip = preg_split("/[;, ]/", $skipfacts);
		
		// Add 1 to day to start from tomorrow
		if ($action == "upcoming") $monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day+1,$year);
		else $monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day,$year);
		$mstart = date("n", $monthstart);
	
		// Look for cached Facts data
		$cache_load = false;
		$cache_refresh = false;
		// Retrieve the last change date
		$mday = GedcomConfig::GetLastCacheDate($action, $GEDCOMID);
	// $mday = 0;  // to force cache rebuild
		if ($mday==$monthstart) {
			$cache_load = true;
	//		print "Retrieve from cache";
		}
		else {
			$sql = "DELETE FROM ".TBLPREFIX."eventcache WHERE ge_cache='".$action."' AND ge_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
		
	// Search database for raw Indi data if no cache was found
		if (!$cache_load) {
			//print "Rebuild cache";
			// Substract 1 to make # of days correct: including start date
			$dstart = date("j", $monthstart);
			if ($action == "upcoming") {
				$monthend = $monthstart + (60*60*24*(GedcomConfig::$DAYS_TO_SHOW_LIMIT-1));
				$dend = date("j", $monthstart+(60*60*24*(GedcomConfig::$DAYS_TO_SHOW_LIMIT-1)));
				$mend = date("n", $monthstart+(60*60*24*(GedcomConfig::$DAYS_TO_SHOW_LIMIT-1)));
			}
			else {
				$monthend = $monthstart;
				$dend = $dstart;
				$mend = date("n", $monthstart);
			}
			$indilist = SearchFunctions::SearchIndisDateRange($dstart, $mstart, "", $dend, $mend, "", $filter, "no", $skipfacts);
			// Search database for raw Family data if no cache was found
			$famlist = SearchFunctions::SearchFamsDateRange($dstart, $mstart, "", $dend, $mend, "", "no", $skipfacts);

			// Apply filter criteria and perform other transformations on the raw data
			foreach($indilist as $gid=>$indi) {
				foreach($indi->facts as $key => $factobj) {
					if (!in_array($factobj->fact, $skip)) {
						$factrec = $factobj->factrec;
						$date = 0; //--- MA @@@
						$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
						if ($hct>0) {
							if (GedcomConfig::$USE_RTL_FUNCTIONS) {
								$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
								if ($dct>0) {
									$hebrew_date = ParseDate(trim($match[1]));
									$date = JewishGedcomDateToCurrentGregorian($hebrew_date);
								}
							}
						}
						else {
							$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
							if ($dct>0) {
								$date = ParseDate(trim($match[1]));
								//print $gid." ".$factobj->fact." ".$match[1]."<br />";
							}
						}
						if (!empty($date[0]["mon"]) && !empty($date[0]["day"])) {
							if ($date[0]["mon"]< $mstart) $y = $year+1;
							else $y = $year;
							$datestamp = mktime(1,0,0,$date[0]["mon"],$date[0]["day"],$y);
							if (($datestamp >= $monthstart) && ($datestamp<=$monthend)) {
								// Strip useless information:
								//   NOTE, ADDR, OBJE, SOUR, PAGE, DATA, TEXT
								$factrec = preg_replace("/\d\s+(NOTE|ADDR|OBJE|SOUR|PAGE|DATA|TEXT|CONT|CONC|QUAY|CAUS|CEME)\s+(.+)\n/", "", $factrec);
								$fct = preg_match("/1\s(\w+)/", $factrec, $fact);
								// let op: naam moet incl addname en addxref.
								$found_facts[] = array($indi->xref, $factrec, "INDI", $datestamp, $indi->revname.($indi->revaddname == "" ? "" : "(".$indi->revaddname.")").$indi->addxref, $indi->sex, $fact[1], $indi->isdead);
							}
						}
					}
				}
			}
			foreach($famlist as $gid => $family) {
				foreach($family->facts as $key => $factobj) {
					if (!in_array($factobj->fact, $skip)) {
						$factrec = $factobj->factrec;
						$date = 0; //--- MA @@@
						$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
						if ($hct>0) {
							if ($USE_RTL_FUNCTIONS) {
								$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
								$hebrew_date = ParseDate(trim($match[1]));
								$date = JewishGedcomDateToCurrentGregorian($hebrew_date);
							}
						}
						else {
							$ct = preg_match("/2 DATE (.+)/", $factrec, $match);
							if ($ct>0) $date = ParseDate(trim($match[1]));
						}
						if (!empty($date[0]["mon"]) && !empty($date[0]["day"])) {
							if ($date[0]["mon"]< $mstart) $y = $year+1;
							else $y = $year;
							$datestamp = mktime(1,0,0,$date[0]["mon"],$date[0]["day"],$y);
							if (($datestamp >= $monthstart) && ($datestamp<=$monthend)) {
								// Strip useless information:
								//   NOTE, ADDR, OBJE, SOUR, PAGE, DATA, TEXT
								$factrec = preg_replace("/\d\s+(NOTE|ADDR|OBJE|SOUR|PAGE|DATA|TEXT|CONT|CONC|QUAY|CAUS|CEME)\s+(.+)\n/", "", $factrec);
								if ((!is_object($family->husb) || $family->husb->isdead) && (!is_object($family->wife) || $family->wife->isdead)) $isdead = "1";
								else $isdead = 0;
								$fct = preg_match("/1\s(\w+)/", $factrec, $fact);
								$found_facts[] = array($family->xref, $factrec, "FAM", $datestamp, "", "", $fact[1], $isdead);
							}
						}
					}
				}
			}
			// Sort the data
			$CIRCULAR_BASE = $mstart;
			$ASC = 0;
			$IGNORE_FACTS = 1;
			$IGNORE_YEAR = 1;
			uasort($found_facts, "CompareFacts");
			
			foreach ($found_facts as $key => $factr) {
				$sql = "INSERT INTO ".TBLPREFIX."eventcache VALUES('0','".$GEDCOMID."', '".$action."', '".$factr[0]."', '".$factr[7]."', '".$factr[6]."', '".mysql_real_escape_string($factr[1])."', '".$factr[2]."', '".$factr[3]."', '".mysql_real_escape_string($factr[4])."', '".$factr[5]."')";
				$res = NewQuery($sql);
				$error = mysql_error();
				if (!empty($error)) print $error."<br />";
			}
			GedcomConfig::SetLastCacheDate($action, $monthstart, $GEDCOMID);
		}
		
		// load the cache from DB
	
		$monthend = $monthstart + (60*60*24*($daysprint-1));
		$found_facts = array();
		$sql = "SELECT ge_gid, ge_factrec, ge_type, ge_datestamp, ge_name, ge_gender, ge_fact, ge_isdead FROM ".TBLPREFIX."eventcache WHERE ge_cache='".$action."' AND ge_file='".$GEDCOMID."' AND ge_datestamp BETWEEN ".$monthstart." AND ".$monthend;
		if ($onlyBDM == "yes") $sql .= " AND ge_fact IN ('BIRT', 'DEAT', 'MARR')";
		if ($filter == "alive") $sql .= " AND ge_isdead=0";
		$sql .= " ORDER BY ge_order";
		$res = NewQuery($sql);
		if($res) {
			while ($row = $res->FetchRow()) {
				$found_facts[] = $row;
			}
		}
		return $found_facts;
	}
	
	public function GetRecentChangeFacts($day, $month, $year, $days) {
		global $monthtonum, $gm_user, $SHOW_SOURCES, $TOTAL_QUERIES;
		
		$dayindilist = array();
		$dayfamlist = array();
		$daysourcelist = array();
		$dayrepolist = array();
		$daymedialist = array();
		$found_facts = array();
	
		$monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day,$year);
		
		$mmon = strtolower(date("M", $monthstart));
		$mmon2 = strtolower(date("M", $monthstart-(60*60*24*$days)));
		$mday2 = date("d", $monthstart-(60*60*24*$days));
		$myear2 = date("Y", $monthstart-(60*60*24*$days));
	
		$fromdate = $myear2.date("m", $monthstart-(60*60*24*$days)).$mday2;
		if ($day < 10)
			$mday3 = "0".$day;
		else $mday3 = $day;
		
		$todate = $year.date("m", $monthstart).$mday3;
		
		$dayindilist = SearchFunctions::SearchIndisDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", "no", "", false, "CHAN");
		$dayfamlist = SearchFunctions::SearchFamsDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "no", "", false, "CHAN");
		if ($SHOW_SOURCES >= $gm_user->getUserAccessLevel()) $dayrepolist = SearchFunctions::SearchOtherDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
		if ($SHOW_SOURCES >= $gm_user->getUserAccessLevel()) $daysourcelist = SearchFunctions::SearchSourcesDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
		$daymedialist = SearchFunctions::SearchMediaDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
	
		if (count($dayindilist)>0 || count($dayfamlist)>0 || count($daysourcelist)>0 || count($dayrepolist) > 0 || count($daymedialist) > 0) {
			$found_facts = array();
			$last_total = $TOTAL_QUERIES;
			foreach($dayindilist as $gid=>$indi) {
				if ($indi->disp) {
					$factrecs = $indi->SelectFacts(array("CHAN"));
					foreach ($factrecs as $key => $fact) {
						$found_facts[] = array($indi->xref, $fact->factrec, "INDI", $fact->fact);
					}
				}
			}
			foreach($dayfamlist as $gid=>$fam) {
				if ($fam->disp) {
					$factrecs = $fam->SelectFacts(array("CHAN"));
					foreach ($factrecs as $key => $fact) {
						$found_facts[] = array($fam->xref, $fact->factrec, "FAM", $fact->fact);
					}
				}
			}
			foreach($daysourcelist as $gid=>$source) {
				if ($source->disp) {
					$factrecs = $source->SelectFacts(array("CHAN"));
					foreach ($factrecs as $key => $fact) {
						$found_facts[] = array($source->xref, $fact->factrec, "SOUR", $fact->fact);
					}
				}
			}
			foreach($dayrepolist as $rid=>$repo) {
				if ($repo->disp) {
					$factrecs = $repo->SelectFacts(array("CHAN"));
					foreach ($factrecs as $key => $fact) {
						$found_facts[] = array($repo->xref, $fact->factrec, "REPO", $fact->fact);
					}
				}
			}
			foreach($daymedialist as $mid=>$media) {
				if ($media->disp) {
					$factrecs = $media->SelectFacts(array("CHAN"));
					foreach ($factrecs as $key => $fact) {
						$found_facts[] = array($media->xref, $fact->factrec, "OBJE", $fact->fact);
					}
				}
			}
		}
		return $found_facts;
	}
	
	public function PrintPageViews($ids, $CountSide, $num, $block) {
		global $TEXT_DIRECTION, $gm_user;
		
		if (count($ids)>0) {
			if ($block) print "<table width=\"90%\">";
			else print "<table>";
			$i=0;
			foreach($ids as $id=>$counter) {
				$count = $counter["number"];
				$type = $counter["type"];
				$gedid = $counter["file"];
				$object = ConstructObject($id, $type, $gedid);
				if (is_object($object)) {
					if ($object->disp_name && !$object->isempty) {
						print "<tr valign=\"top\">";
						if ($CountSide=="left") {
							print "<td dir=\"ltr\" align=\"right\">";
							if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
							print "[".$count."]";
							if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
							print "</td>";
						}
						if ($type=="INDI") {
							print "<td class=\"name2 wrap\" ";
							if ($block) print "width=\"86%\"";
							print "><a href=\"individual.php?pid=".urlencode($id)."&amp;gedid=".$gedid."\">";
							print $object->name.($object->addname == "" ? "" : "&nbsp;".$object->addname).$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type=="FAM") {
							print "<td class=\"name2 wrap\" ><a href=\"family.php?famid=".urlencode($id)."&amp;gedid=".$gedid."\">";
							print $object->descriptor.($object->adddescriptor == "" ? "" : "&nbsp;".$object->adddescriptor).$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type=="REPO") {
							print "<td class=\"name2 wrap\" ><a href=\"repo.php?rid=".urlencode($id)."&amp;gedid=".$gedid."\">";
							print $object->descriptor.($object->adddescriptor == "" ? "" : "&nbsp;".$object->adddescriptor).$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type=="SOUR") {
							print "<td class=\"name2 wrap\" ><a href=\"source.php?sid=".urlencode($id)."&amp;gedid=".$gedid."\">";
							print $object->descriptor.($object->adddescriptor == "" ? "" : "&nbsp;".$object->adddescriptor).$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type=="OBJE") {
							print "<td class=\"name2 wrap\" ><a href=\"mediadetail.php?mid=".urlencode($id)."&amp;gedid=".$gedid."\">";
							print $object->title.$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type=="NOTE") {
							print "<td class=\"name2 wrap\" ><a href=\"note.php?oid=".urlencode($id)."&amp;gedid=".$gedid."\">";
							print $object->title.$object->addxref;
							print "</a></td>";
							$i++;
						}
						if ($CountSide=="right") {
							print "<td dir=\"ltr\" align=\"right\">";
							if ($TEXT_DIRECTION=="ltr") print "&nbsp;";
							print "[".$count."]";
							if ($TEXT_DIRECTION=="rtl") print "&nbsp;";
							print "</td>";
						}
						print "</tr>";
						if ($i >= $num) break;
					}
				}
			}
			print "</table>";
		}
		else print "<b>".$gm_lang["top10_pageviews_nohits"]."</b>\n";
	}
	
	public function PrintBlockFavorites(&$userfavs, $side, $index, $style) {
		global $command, $gm_user, $gm_lang, $view;
		
		foreach($userfavs as $key=>$favorite) {
			if (isset($favorite->id)) $key=$favorite->id;
			$oldgedid = 
			SwitchGedcom($favorite->file);
			if ($favorite->type=="URL") {
				print "<div id=\"boxurl".$key.".0\" class=\"person_box";
				print "\"><ul>\n";
				print "<li><a href=\"".$favorite->url."\">".PrintReady($favorite->title)."</a></li>";
				print "</ul>";
				print "<span class=\"favorite_padding\">".PrintReady($favorite->note)."</span>";
			}
			else {
				$favorite->GetObject();
				print "<div id=\"box".$favorite->object->xref.".0\" class=\"person_box";
				if ($favorite->type == "INDI") {
					if ($favorite->object->sex == "F") print "F\">\n";
					elseif ($favorite->object->sex == "U") print "NN\">\n";
					else print "\">\n";
					PersonFunctions::PrintPedigreePerson($favorite->object, $style, 1, $key, 1, $view);
				}
				else {
					print "\"><ul>\n";
					if ($favorite->type=="SOUR") $favorite->object->PrintListSource();
					elseif ($favorite->type=="REPO") $favorite->object->PrintListRepository();
					elseif ($favorite->type=="NOTE") $favorite->object->PrintListNote();
					elseif ($favorite->type=="FAM") $favorite->object->PrintListFamily();
					elseif ($favorite->type=="OBJE") $favorite->object->PrintListMedia();
					print "</ul>";
				}
			}
			if (!empty($favorite->note)) print "<span class=\"favorite_padding\">".PrintReady($gm_lang["note"].": ".$favorite->note)."</span>";
			print "</div>\n";
			if ($command=="user" || $gm_user->userIsAdmin()) {
				if (!empty($favorite->note)) print "&nbsp;&nbsp;";
				print "<a class=\"font9\" href=\"index.php?command=$command&amp;action=deletefav&amp;fv_id=".$key."\" onclick=\"return confirm('".$gm_lang["confirm_fav_remove"]."');\">".$gm_lang["remove"]."</a>\n";
				print "&nbsp;";
				print "<a class=\"font9\" href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?favid=$key&amp;name=$gm_user->username&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=400,scrollbars=1,resizable=1'); return false;\">".$gm_lang["edit"]."</a>";
			}
			SwitchGedcom();
		}
	}
	
	public function PrintBlockAddFavorite($command, $type) {
		global $GM_IMAGES, $gm_lang, $GEDCOMID;
		
		?>
			<script language="JavaScript" type="text/javascript">
			<!--
			var pastefield;
			function paste_id(value) {
				pastefield.value=value;
			}
			//-->
			</script>
			<br />
		<?php
		print_help_link("index_add_favorites_help", "qm", "add_favorite");
		print "<b><a href=\"javascript: ".$gm_lang["add_favorite"]." \" onclick=\"expand_layer('add_".$type."_fav'); return false;\"><img id=\"add_ged_fav_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" alt=\"".$gm_lang["add_favorite"]."\" />&nbsp;".$gm_lang["add_favorite"]."</a></b>";
		print "<br /><div id=\"add_".$type."_fav\" style=\"display: none;\">\n";
		print "<form name=\"addfavform\" method=\"get\" action=\"index.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"addfav\" />\n";
		print "<input type=\"hidden\" name=\"command\" value=\"$command\" />\n";
		print "<input type=\"hidden\" name=\"favtype\" value=\"".$type."\" />\n";
		print "<table border=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td>".$gm_lang["add_fav_enter_id"]." <br />";
		print "<input class=\"pedigree_form\" type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
		LinkFunctions::PrintFindIndiLink("gid",$GEDCOMID);
		LinkFunctions::PrintFindFamilyLink("gid");
		LinkFunctions::PrintFindSourceLink("gid");
		LinkFunctions::PrintFindObjectLink("gid");
		LinkFunctions::PrintFindNoteLink("gid");
		print "\n<br />".$gm_lang["add_fav_or_enter_url"];
		print "\n<br />".$gm_lang["url"]."<input type=\"text\" name=\"url\" size=\"40\" value=\"\" />";
		print "\n<br />".$gm_lang["title"]." <input type=\"text\" name=\"favtitle\" size=\"40\" value=\"\" />";
		print "\n</td><td>";
		print "\n".$gm_lang["add_fav_enter_note"];
		print "\n<br /><textarea name=\"favnote\" rows=\"6\" cols=\"40\"></textarea>";
		print "</td></tr></table>\n";
		print "\n<br /><input type=\"submit\" value=\"".$gm_lang["add"]."\" style=\"font-size: 8pt; \" />";
		print "\n</form></div>\n";
	}

	public function GetCachedStatistics() {
		global $gm_lang, $monthtonum, $GEDCOMID;
		
		// First see if the cache must be refreshed
		$cache_load = GedcomConfig::GetLastCacheDate("stats", $GEDCOMID);
		if (!$cache_load) {
			$sql = "DELETE FROM ".TBLPREFIX."statscache WHERE gs_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
		$stats = array();
		// The title must be generated every time because the language may differ
		$stats["gs_title"] = "";
		$head = "";
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_id='HEAD' AND o_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		if ($res->NumRows() > 0) {
			$row = $res->FetchAssoc();
			$head = $row["o_gedrec"];
		}
		$ct=preg_match("/1 SOUR (.*)/", $head, $match);
		
		if ($ct>0) {
			$softrec = GetSubRecord(1, "1 SOUR", $head);
			$tt= preg_match("/2 NAME (.*)/", $softrec, $tmatch);
			if ($tt>0) $title = trim($tmatch[1]);
			else $title = trim($match[1]);
			if (!empty($title)) {
					$text = str_replace("#SOFTWARE#", $title, $gm_lang["gedcom_created_using"]);
					$tt = preg_match("/2 VERS (.*)/", $softrec, $tmatch);
					if ($tt>0) $version = trim($tmatch[1]);
					else $version="";
					$text = str_replace("#VERSION#", $version, $text);
					$stats["gs_title"] .= $text;
			}
		}
		$ct=preg_match("/1 DATE (.*)/", $head, $match);
		if ($ct>0) {
			$date = trim($match[1]);
			$ct2 = preg_match("/2 TIME (.*)/", $head, $match);
			if ($ct2 > 0) $time = trim($match[1]);
			else $time = "";	
			if (empty($title)) {
				$text = str_replace("#DATE#", GetChangedDate($date), $gm_lang["gedcom_created_on"]);
				$text = str_replace("#TIME#", $time, $text);
			}
			else {
				$text = str_replace("#DATE#", GetChangedDate($date), $gm_lang["gedcom_created_on2"]);
				$text = str_replace("#TIME#", $time, $text);
			}
			$stats["gs_title"] .= " ".$text;
		}
	
		if (!$cache_load) {
	
			//-- total unique surnames
			$sql = "SELECT count(surnames) FROM (";
			$sql .= "SELECT distinct n_surname as surnames FROM ".TBLPREFIX."names WHERE n_file='".$GEDCOMID."'";
			$sql .= ") as sn GROUP BY surnames";
			$res = NewQuery($sql);
			$stats["gs_nr_surnames"] = $res->NumRows();
			$res->FreeResult();
	
			$stats["gs_nr_fams"] = GetListSize("famlist");
			$stats["gs_nr_sources"] = GetListSize("sourcelist");
			$stats["gs_nr_other"] = GetListSize("otherlist");
			$stats["gs_nr_media"] = GetListSize("medialist");
	
			//-- total events
			$sql = "SELECT COUNT(d_gid) FROM ".TBLPREFIX."dates WHERE d_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$stats["gs_nr_events"] = $row[0];
			$res->FreeResult();
	
			// NOTE: Get earliest birth year
			$sql = "SELECT d_gid, d_year, d_month, FIELD(d_month";
			foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
			$sql .= ") as d_mon, d_day FROM ".TBLPREFIX."dates WHERE d_file = '".$GEDCOMID."' AND d_fact = 'BIRT' AND d_year != '0' AND d_type IS NULL ORDER BY d_year ASC, d_mon ASC, d_day ASC LIMIT 1";
			// print $sql;
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_earliest_birth_year"] = $row[1];
			$stats["gs_earliest_birth_gid"] = $row[0];
			
			// NOTE: Get the latest birth year
			$sql = "select d_gid, d_year, d_month, FIELD(d_month";
			foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
			$sql .= ") as d_mon, d_day from ".TBLPREFIX."dates where d_file = '".$GEDCOMID."' and d_fact = 'BIRT' and d_year != '0' and d_type is null ORDER BY d_year DESC, d_mon DESC , d_day DESC LIMIT 1";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_latest_birth_year"] = $row[1];
			$stats["gs_latest_birth_gid"] = $row[0];
	
			// NOTE: Get the person who lived the longest
			$sql = "SELECT death.d_year-birth.d_year AS age, death.d_gid FROM ".TBLPREFIX."dates AS death, ".TBLPREFIX."dates AS birth WHERE birth.d_gid=death.d_gid AND death.d_file='".$GEDCOMID."' and birth.d_file=death.d_file AND birth.d_fact='BIRT' AND death.d_fact='DEAT' AND birth.d_year>0 AND death.d_year>0 AND birth.d_type IS NULL AND death.d_type IS NULL ORDER BY age DESC limit 1";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_longest_live_years"] = $row[0];
			$stats["gs_longest_live_gid"] = $row[1];
			
			//-- avg age at death
			$sql = "SELECT AVG(death.d_year-birth.d_year) AS age FROM ".TBLPREFIX."dates AS death, ".TBLPREFIX."dates AS birth WHERE birth.d_gid=death.d_gid AND death.d_file='".$GEDCOMID."' AND birth.d_file=death.d_file AND birth.d_fact='BIRT' AND death.d_fact='DEAT' AND birth.d_year>0 AND death.d_year>0 AND birth.d_type IS NULL AND death.d_type IS NULL";
			$res = NewQuery($sql);
			if ($res) {
				$row = $res->FetchRow();
				$stats["gs_avg_age"] = floor($row[0]);
			}
			else $stats["gs_avg_age"] = "";
						
			//-- most children
			$sql = "SELECT f_numchil, f_id FROM ".TBLPREFIX."families WHERE f_file='".$GEDCOMID."' ORDER BY f_numchil DESC LIMIT 10";
			$res = NewQuery($sql);
			if ($res) {
				$row = $res->FetchRow();
				$res->FreeResult();
				$stats["gs_most_children_nr"] = $row[0];
				$stats["gs_most_children_gid"] = $row[1];
			}
			else {
				$stats["gs_most_children_nr"] = "";
				$stats["gs_most_children_gid"] = "";
			}
	
			//-- avg number of children
			$sql = "SELECT AVG(f_numchil) FROM ".TBLPREFIX."families WHERE f_file='".$GEDCOMID."'";
			$res = NewQuery($sql, false);
			if ($res) {
				$row = $res->FetchRow();
				$res->FreeResult();
				$stats["gs_avg_children"] = $row[0];
			}
			else $stats["gs_avg_children"] = "";
			
			$sql = "INSERT INTO ".TBLPREFIX."statscache ";
			$sqlf = "(gs_file";
			$sqlv = "('".$GEDCOMID."'";
			foreach($stats as $skey => $svalue) {
				if ($skey != "gs_title") {
					$sqlf .= ", ".$skey;
					$sqlv .= ", '".$svalue."'";
				}
			}
			$sqlf .= ")";
			$sqlv .= ")";
			$sql .= $sqlf." VALUES ".$sqlv;
			$res = NewQuery($sql);
			GedcomConfig::SetLastCacheDate("stats", time(), $GEDCOMID);
		}
		$sql = "SELECT * FROM ".TBLPREFIX."statscache WHERE gs_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc($res->result)){
			foreach ($row as $key => $value) {
				$stats[$key] = $value;
			}
		}
		return $stats;
	}
}
?>