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
 * $Id: functions_block_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class BlockFunctions {
	
	public function PrintAdminIcon() {
		global $GM_IMAGES;
		
		print "<img class=\"BlockAdminIcon\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]."\" alt=\"".GM_LANG_config_block."\" title=\"".GM_LANG_config_block."\" />\n";
	}
	/**
	 * get the top surnames
	 * @param int $num	how many surnames to return
	 * @return array
	 */
	public function GetTopSurnames($num) {
	
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
		$sql = "(SELECT COUNT(n_surname) as count, n_surname FROM ".TBLPREFIX."names WHERE n_file='".GedcomConfig::$GEDCOMID."' AND n_type!='C' AND n_surname<>'@N.N.'".$delstrn." GROUP BY n_surname) ORDER BY count DESC LIMIT ".$num;
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
		global $monthtonum, $monthstart;
		global $ASC, $IGNORE_FACTS, $IGNORE_YEAR;
		global $CIRCULAR_BASE;
		
		$day = GetCurrentDay();
		$month = GetCurrentMonth();
		$year = GetCurrentYear();
		$found_facts = array();
		$skip = preg_split("/[;, ]/", $skipfacts);
		
		// Add 1 to day to start from tomorrow
		if ($action == "upcoming") {
			$monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day+1,$year);
			// Fix for 31 december!
			if (date("Y", $monthstart) > $year) $year +=1;
			//print "jaar: ".date("Y", $monthstart)." ".$year;
		}
		else $monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day,$year);
		$mstart = date("n", $monthstart);
		
		// Look for cached Facts data
		$cache_load = false;
		$cache_refresh = false;
		
		// to force cache rebuild
		// GedcomConfig::ResetCaches();
	
		// Retrieve the last change date
		$mday = GedcomConfig::GetLastCacheDate($action, GedcomConfig::$GEDCOMID);
		if ($mday == $monthstart) {
			$cache_load = true;
		}
		else {
			$sql = "DELETE FROM ".TBLPREFIX."eventcache WHERE ge_cache='".$action."' AND ge_file='".GedcomConfig::$GEDCOMID."'";
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
				//print "dstart: ".$dstart." mstart: ".$mstart." dend: ".$dend." mend: ".$mend;
			}
			else {
				$monthend = $monthstart;
				$dend = $dstart;
				$mend = date("n", $monthstart);
			}
			$indilist = self::SearchIndisDateRange($dstart, $mstart, "", $dend, $mend, "", $filter, "no", $skipfacts);
			// Search database for raw Family data if no cache was found
			$famlist = self::SearchFamsDateRange($dstart, $mstart, "", $dend, $mend, "", $filter, "no", $skipfacts);

			// Apply filter criteria and perform other transformations on the raw data
			foreach($indilist as $gid => $indi) {
				$allsubs = GetAllSubrecords($indi->gedrec, "ASSO, FAMS, FAMC, CHIL, REPO, SOUR, OBJE, NOTE", false, false, false);
				foreach($allsubs as $key => $factrec) {
					if (!in_array(GetFactType($factrec), $skip)) {
						$date = 0; //--- MA @@@
						if (GedcomConfig::$USE_RTL_FUNCTIONS) {
							$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
							if ($hct > 0) {
								$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
								if ($dct>0) {
									$hebrew_date = ParseDate(trim($match[1]));
									$date = JewishGedcomDateToCurrentGregorian($hebrew_date);
								}
							}
						}
						if ($date == 0) {
							$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
							if ($dct > 0) {
								$date = ParseDate(trim($match[1]));
							}
						}
						if (!empty($date[0]["mon"]) && !empty($date[0]["day"])) {
							if ($date[0]["mon"]< $mstart) $y = $year+1;
							else $y = $year;
							$datestamp = mktime(1,0,0,$date[0]["mon"],$date[0]["day"],$y);
							if ($datestamp >= $monthstart && $datestamp <= $monthend) {
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
				$allsubs = GetAllSubrecords($family->gedrec, "ASSO, FAMS, FAMC, CHIL, REPO, SOUR, OBJE, NOTE", false, false, false);
				foreach($allsubs as $key => $factrec) {
					if (!in_array(GetFactType($factrec), $skip)) {
						$date = 0; //--- MA @@@
						if (GedcomConfig::$USE_RTL_FUNCTIONS) {
							$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
							if ($hct>0) {
								$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
								$hebrew_date = ParseDate(trim($match[1]));
								$date = JewishGedcomDateToCurrentGregorian($hebrew_date);
							}
						}
						if ($date == 0) {
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
		
			$sqlstr = "";
			$first = true;
			foreach ($found_facts as $key => $factr) {
				if (!$first) $sqlstr .= ", ";
				$first = false;
				$sqlstr .= "('0','".GedcomConfig::$GEDCOMID."', '".$action."', '".$factr[0]."', '".$factr[7]."', '".$factr[6]."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $factr[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".$factr[2]."', '".$factr[3]."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $factr[4]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".$factr[5]."')";
			}
			if (!empty($sqlstr)) {
				$sql = "INSERT INTO ".TBLPREFIX."eventcache VALUES ".$sqlstr;
				$res = NewQuery($sql);
				if ($res) $res->FreeResult();
			}
			GedcomConfig::SetLastCacheDate($action, $monthstart, GedcomConfig::$GEDCOMID);
		}
		
		// load the cache from DB
	
		$monthend = $monthstart + (60*60*24*($daysprint-1));
		$found_facts = array();
		$sql = "SELECT ge_gid, ge_factrec, ge_type, ge_datestamp, ge_name, ge_gender, ge_fact, ge_isdead FROM ".TBLPREFIX."eventcache WHERE ge_cache='".$action."' AND ge_file='".GedcomConfig::$GEDCOMID."' AND ge_datestamp BETWEEN ".$monthstart." AND ".$monthend;
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
	
	public function GetRecentChangeFacts($day, $month, $year, $days, $config) {
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
		
		if ($config["show_indi"] == "yes") $dayindilist = self::SearchIndisDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", "no", "", false, "CHAN");
		if ($config["show_fam"] == "yes") $dayfamlist = self::SearchFamsDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", "no", "", false, "CHAN");
		if ($config["show_repo"] == "yes" && $SHOW_SOURCES >= $gm_user->getUserAccessLevel()) $dayrepolist = self::SearchOtherDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
		if ($config["show_sour"] == "yes" && $SHOW_SOURCES >= $gm_user->getUserAccessLevel()) $daysourcelist = self::SearchSourcesDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
		if ($config["show_obje"] == "yes") $daymedialist = self::SearchMediaDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
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
			if ($block) print "<table class=\"Top10BlockTable\">";
			else print "<table class=\"Top10BlockTableWide\">";
			$i=0;
			foreach($ids as $id=>$counter) {
				$count = $counter["number"];
				$type = $counter["type"];
				$gedid = $counter["file"];
				$object = ConstructObject($id, $type, $gedid);
				if (is_object($object)) {
					if ($object->disp_name && !$object->isempty) {
						print "<tr>";
						if ($CountSide == "left") {
							print "<td class=\"Top10LeftCounter\">";
							print "[".$count."]";
							print "</td>";
						}
						if ($type == "INDI") {
							print "<td class=\"Top10BlockLink\"";
							print "><a href=\"individual.php?pid=".urlencode($id)."&amp;gedid=".$gedid."\"><span class=\"Top10Name\">\n";
							print $object->revname.($object->revaddname == "" ? "" : "&nbsp;(".$object->revaddname.")")."</span>".$object->addxref;
							print "</a></td>\n";
							$i++;
						}
						elseif ($type == "FAM") {
							print "<td class=\"Top10BlockLink\"><a href=\"family.php?famid=".urlencode($id)."&amp;gedid=".$gedid."\"><span class=\"Top10Name\">";
							print $object->sortable_name.($object->sortable_addname == "" ? "" : "&nbsp;(".$object->sortable_addname.")")."</span>".$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type == "REPO") {
							print "<td class=\"Top10BlockLink\"><a href=\"repo.php?rid=".urlencode($id)."&amp;gedid=".$gedid."\"><span class=\"Top10Name\">";
							print $object->descriptor.($object->adddescriptor == "" ? "" : "&nbsp;(".$object->adddescriptor.")")."</span>".$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type == "SOUR") {
							print "<td class=\"Top10BlockLink\"><a href=\"source.php?sid=".urlencode($id)."&amp;gedid=".$gedid."\"><span class=\"Top10Name\">";
							print $object->descriptor.($object->adddescriptor == "" ? "" : "&nbsp;(".$object->adddescriptor.")")."</span>".$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type == "OBJE") {
							print "<td class=\"Top10BlockLink\"><a href=\"mediadetail.php?mid=".urlencode($id)."&amp;gedid=".$gedid."\"><span class=\"Top10Name\">";
							print "</span>".$object->title.$object->addxref;
							print "</a></td>";
							$i++;
						}
						elseif ($type == "NOTE") {
							print "<td class=\"Top10BlockLink\"><a href=\"note.php?oid=".urlencode($id)."&amp;gedid=".$gedid."\"><span class=\"Top10Name\">";
							print "</span>".$object->title.$object->addxref;
							print "</a></td>";
							$i++;
						}
						if ($CountSide == "right") {
							print "<td class=\"Top10RightCounter\">";
							print "[".$count."]";
							print "</td>";
						}
						print "</tr>";
						if ($i >= $num) break;
					}
				}
			}
			print "</table>";
		}
		else print "<b>".GM_LANG_top10_pageviews_nohits."</b>\n";
	}
	
	public function PrintBlockFavorites(&$userfavs, $side, $index) {
		global $command, $gm_user, $view;
		
		foreach($userfavs as $key=>$favorite) {
			if (isset($favorite->id)) $key=$favorite->id;
			$oldgedid = 
			SwitchGedcom($favorite->file);
			if ($favorite->type == "URL") {
				print "<div id=\"boxurl".$key.".0\" class=\"PersonBox";
				print "\"><ul>\n";
				print "<li><a href=\"".$favorite->url."\">".PrintReady($favorite->title)."</a></li>";
				print "</ul>";
				print "<span class=\"FavoriteNotePadding\">".PrintReady($favorite->note)."</span>";
			}
			else {
				$favorite->GetObject();
				print "<div id=\"box".$favorite->object->xref.".0\" class=\"PersonBox";
				if ($favorite->type == "INDI") {
					if ($favorite->object->sex == "F") print "F\">\n";
					elseif ($favorite->object->sex == "U") print "NN\">\n";
					else print "\">\n";
					PersonFunctions::PrintPedigreePerson($favorite->object, 2, 1, $key, 1, $view);
				}
				else {
					print "\">";
					if ($favorite->type != "OBJE") print "<ul>\n";
					if ($favorite->type == "SOUR") $favorite->object->PrintListSource();
					elseif ($favorite->type == "REPO") $favorite->object->PrintListRepository();
					elseif ($favorite->type == "NOTE") $favorite->object->PrintListNote();
					elseif ($favorite->type == "FAM") $favorite->object->PrintListFamily();
					elseif ($favorite->type == "OBJE") {
						MediaFS::DispImgLink($favorite->object->fileobj->f_main_file, $favorite->object->fileobj->f_thumb_file, $favorite->object->title, "", $favorite->object->fileobj->f_twidth, 0, 100, 100, $favorite->object->fileobj->f_is_image, $favorite->object->fileobj->f_file_exists, true, true);
						$favorite->object->PrintListMedia(false, "", false);
					}
					if ($favorite->type != "OBJE") print "</ul>";
					else print "<br />";
				}
			}
			if (!empty($favorite->note)) print "<div class=\"FavoriteNotePadding\">".PrintReady("<span class='FactDetailLabel'>".GM_LANG_note.": </span>".$favorite->note)."</div>";
			print "</div>\n";
			if ($command == "user" || $gm_user->userIsAdmin()) {
				if (!empty($favorite->note)) print "&nbsp;&nbsp;";
				print "<a class=\"SmallEditLinks\" href=\"index.php?command=$command&amp;action=deletefav&amp;fv_id=".$key."\" onclick=\"return confirm('".GM_LANG_confirm_fav_remove."');\">".GM_LANG_remove."</a>\n";
				print "&nbsp;";
				print "<a class=\"SmallEditLinks\" href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?favid=$key&amp;name=$gm_user->username&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=400,scrollbars=1,resizable=1'); return false;\">".GM_LANG_edit."</a>";
			}
			else print "&nbsp;";
			SwitchGedcom();
		}
	}
	
	public function PrintBlockAddFavorite($command, $type, $side) {
		global $GM_IMAGES;
		
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
		PrintHelpLink("index_add_favorites_help", "qm", "add_favorite");
		print "<b><a href=\"javascript: ".GM_LANG_add_favorite." \" onclick=\"expand_layer('add_".$type."_fav'); return false;\"><img id=\"add_".$type."_fav_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" alt=\"".GM_LANG_add_favorite."\" title=\"".GM_LANG_add_favorite."\" />&nbsp;".GM_LANG_add_favorite."</a></b>";
		print "<br /><div id=\"add_".$type."_fav\" style=\"display: none;\">\n";
			print "<form name=\"addfavform\" method=\"get\" action=\"index.php\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"addfav\" />\n";
			print "<input type=\"hidden\" name=\"command\" value=\"$command\" />\n";
			print "<input type=\"hidden\" name=\"favtype\" value=\"".$type."\" />\n";
			print "<div class=\"AddFavLeftBlock\">";
				print "<table class=\"BlockTable\">";
					print "<tr>";
						print "<td class=\"BlockLabel\">".GM_LANG_add_fav_enter_id."</td>";
						print "<td class=\"BlockField\">";
							print "<input class=\"PidInputField\" type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
							LinkFunctions::PrintFindIndiLink("gid",GedcomConfig::$GEDCOMID);
							LinkFunctions::PrintFindFamilyLink("gid");
							LinkFunctions::PrintFindSourceLink("gid");
							LinkFunctions::PrintFindMediaLink("gid");
							LinkFunctions::PrintFindNoteLink("gid");
						print "</td>";
					print "</tr>";
					print "<tr>";
						print "<td colspan=\"2\" class=\"BlockLabel\">".GM_LANG_add_fav_or_enter_url."</td>";
					print "</tr>";
					print "<tr>";
						print "<td class=\"BlockLabel\">".GM_LANG_url."</td>";
						print "<td class=\"BlockField\"><input type=\"text\" name=\"url\" size=\"40\" value=\"\" /></td>";
					print "</tr>";
					print "<tr>";
						print "<td class=\"BlockLabel\">".GM_LANG_title."</td>";
						print "<td class=\"BlockField\"><input type=\"text\" name=\"favtitle\" size=\"40\" value=\"\" /></td>";
					print "</tr>";
					print "<tr><td colspan=\"2\" class=\"BlockFooter\"><input type=\"submit\" value=\"".GM_LANG_add."\" style=\"font-size: 8pt; \" /></td></tr>";
				print "</table>\n";
			print "</div>";
			print "<div class=\"AddFavRightBlock\">";
				print "<table class=\"BlockTable\">";
					print "<tr><td class=\"BlockLabel\">".GM_LANG_add_fav_enter_note."</td></tr>";
					print "<tr><td class=\"BlockField\"><textarea name=\"favnote\" rows=\"6\" cols=\"40\"></textarea></td></tr>";
				print "</table>\n";
			print "</div>";
		print "\n</form></div>\n";
	}

	public function GetCachedStatistics() {
		global $monthtonum;
		
		// First see if the cache must be refreshed
		$cache_load = GedcomConfig::GetLastCacheDate("stats", GedcomConfig::$GEDCOMID);
		// $cache_load=false;
		if (!$cache_load) {
			$sql = "DELETE FROM ".TBLPREFIX."statscache WHERE gs_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
		}
		$stats = array();
		// The title must be generated every time because the language may differ
		$stats["gs_title"] = "";
		$head = "";
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_id='HEAD' AND o_file='".GedcomConfig::$GEDCOMID."'";
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
					$text = str_replace("#SOFTWARE#", $title, GM_LANG_gedcom_created_using);
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
				$text = str_replace("#DATE#", GetChangedDate($date), GM_LANG_gedcom_created_on);
				$text = str_replace("#TIME#", $time, $text);
			}
			else {
				$text = str_replace("#DATE#", GetChangedDate($date), GM_LANG_gedcom_created_on2);
				$text = str_replace("#TIME#", $time, $text);
			}
			$stats["gs_title"] .= " ".$text;
		}
	
		if (!$cache_load) {
	
			//-- total unique surnames
			$sql = "SELECT count(surnames) FROM (";
			$sql .= "SELECT distinct n_surname as surnames FROM ".TBLPREFIX."names WHERE n_file='".GedcomConfig::$GEDCOMID."'";
			$sql .= ") as sn GROUP BY surnames";
			$res = NewQuery($sql);
			$stats["gs_nr_surnames"] = $res->NumRows();
			$res->FreeResult();
	
			$stats["gs_nr_fams"] = GetListSize("famlist");
			$stats["gs_nr_sources"] = GetListSize("sourcelist");
			$stats["gs_nr_other"] = GetListSize("otherlist");
			$stats["gs_nr_media"] = GetListSize("medialist");
	
			//-- total events
			$sql = "SELECT COUNT(d_gid) FROM ".TBLPREFIX."dates WHERE d_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$stats["gs_nr_events"] = $row[0];
			$res->FreeResult();
	
			// NOTE: Get earliest birth year
			$sql = "SELECT d_gid, d_year, d_month, FIELD(d_month";
			foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
			$sql .= ") as d_mon, d_day FROM ".TBLPREFIX."dates WHERE d_file = '".GedcomConfig::$GEDCOMID."' AND d_fact = 'BIRT' AND d_year != '0' AND d_type IS NULL ORDER BY d_year ASC, d_mon ASC, d_day ASC LIMIT 1";
			// print $sql;
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_earliest_birth_year"] = $row[1];
			$stats["gs_earliest_birth_gid"] = $row[0];
			
			// NOTE: Get the latest birth year
			$sql = "select d_gid, d_year, d_month, FIELD(d_month";
			foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
			$sql .= ") as d_mon, d_day from ".TBLPREFIX."dates where d_file = '".GedcomConfig::$GEDCOMID."' and d_fact = 'BIRT' and d_year != '0' and d_type is null ORDER BY d_year DESC, d_mon DESC , d_day DESC LIMIT 1";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_latest_birth_year"] = $row[1];
			$stats["gs_latest_birth_gid"] = $row[0];
	
			// NOTE: Get the person who lived the longest
			$sql = "SELECT death.d_year-birth.d_year AS age, death.d_gid FROM ".TBLPREFIX."dates AS death, ".TBLPREFIX."dates AS birth WHERE birth.d_gid=death.d_gid AND death.d_file='".GedcomConfig::$GEDCOMID."' and birth.d_file=death.d_file AND birth.d_fact='BIRT' AND death.d_fact='DEAT' AND birth.d_year>0 AND death.d_year>0 AND birth.d_type IS NULL AND death.d_type IS NULL AND birth.d_ext='' AND death.d_ext='' ORDER BY age DESC limit 1";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_longest_live_years"] = $row[0];
			$stats["gs_longest_live_gid"] = $row[1];
			
			//-- avg age at death
			$sql = "SELECT AVG(death.d_year-birth.d_year) AS age FROM ".TBLPREFIX."dates AS death, ".TBLPREFIX."dates AS birth WHERE birth.d_gid=death.d_gid AND death.d_file='".GedcomConfig::$GEDCOMID."' AND birth.d_file=death.d_file AND birth.d_fact='BIRT' AND death.d_fact='DEAT' AND birth.d_year>0 AND death.d_year>0 AND birth.d_type IS NULL AND death.d_type IS NULL";
			$res = NewQuery($sql);
			if ($res) {
				$row = $res->FetchRow();
				$stats["gs_avg_age"] = floor($row[0]);
			}
			else $stats["gs_avg_age"] = "";
						
			//-- most children
			$sql = "SELECT f_numchil, f_id FROM ".TBLPREFIX."families WHERE f_file='".GedcomConfig::$GEDCOMID."' ORDER BY f_numchil DESC LIMIT 10";
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
			$sql = "SELECT AVG(f_numchil) FROM ".TBLPREFIX."families WHERE f_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql, false);
			if ($res) {
				$row = $res->FetchRow();
				$res->FreeResult();
				$stats["gs_avg_children"] = $row[0];
			}
			else $stats["gs_avg_children"] = "";
			
			$sql = "REPLACE INTO ".TBLPREFIX."statscache ";
			$sqlf = "(gs_file";
			$sqlv = "('".GedcomConfig::$GEDCOMID."'";
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
			GedcomConfig::SetLastCacheDate("stats", time(), GedcomConfig::$GEDCOMID);
		}
		$sql = "SELECT * FROM ".TBLPREFIX."statscache WHERE gs_file='".GedcomConfig::$GEDCOMID."'";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc($res->result)){
			foreach ($row as $key => $value) {
				$stats[$key] = $value;
			}
		}
		return $stats;
	}
	
	/**
	 * Search the dates table for other records that had events on the given day
	 * Used in: functions_block_class
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $mymedia array with all individuals that matched the query
	 */
	private function SearchMediaDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		$medialist = array();
		
		$sql = "SELECT m_media, m_mfile, m_file, m_ext, m_titl, m_gedrec FROM ".TBLPREFIX."dates, ".TBLPREFIX."media WHERE m_media=d_gid AND m_file=d_file ";
		
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
		$sql .= "GROUP BY m_media ORDER BY d_year, d_month, d_day DESC";
	
		$res = NewQuery($sql);
	
		while($row = $res->fetchAssoc()){
			$media = null;
			$media =& MediaItem::GetInstance($row["m_media"], $row, $row["m_file"]);
			$medialist[JoinKey($row["m_media"], $row["m_file"])] = $media;
		}
		$res->FreeResult();
		return $medialist;
	}

	/**
	 * Search the dates table for sources that had events on the given day
	 *
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myfamlist array with all individuals that matched the query
	 */
	private function SearchSourcesDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		
		$sourcelist = array();
		
		$sql = "SELECT s_key, s_id, s_name, s_file, s_gedrec, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."sources WHERE s_id=d_gid AND s_file=d_file ";
		
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
		$sql .= "GROUP BY s_id ORDER BY d_year, d_month, d_day DESC";
		
		$res = NewQuery($sql);
		while($row = $res->fetchAssoc()){
			$source = null;
			$source =& Source::GetInstance($row["s_id"], $row, $row["s_file"]);
			$sourcelist[$row["s_key"]] = $source;
		}
		$res->FreeResult();
		return $sourcelist;
	}
	/**
	 * Search the dates table for individuals that had events on the given day
	 *
	 * @param	int $day the day of the month to search for, leave empty to include all
	 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
	 * @param	int $year the year to search for, leave empty to include all
	 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
	 * 				prepend the fact with a ! to not include that fact
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myindilist array with all individuals that matched the query
	 */
	private function SearchIndisDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $filter="all", $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
		
		$indilist = array();
	//print "Dstart: ".$dstart."<br />";
	//print "Mstart: ".$mstart." ".date("M", mktime(1,0,0,$mstart,1))."<br />";
	//print "Dend: ".$dend."<br />";
	//print "Mend: ".$mend." ".date("M", mktime(1,0,0,$mend,1))."<br />";
		$sql = "SELECT i_key, i_id, i_file, i_gedrec, i_isdead, n_name, n_surname, n_nick, n_letter, n_fletter, n_type FROM ".TBLPREFIX."dates, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=d_key AND n_key=i_key ";
		if ($onlyBDM == "yes") $sql .= " AND d_fact IN ('BIRT', 'DEAT')";
		if ($filter == "living") $sql .= "AND i_isdead!='1'";
	
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
		$sql .= "GROUP BY n_id ORDER BY i_key, n_id, d_year, d_month, d_day DESC";

		$res = NewQuery($sql);
		$key = "";
		while($row = $res->FetchAssoc($res->result)){
			if ($key != $row["i_key"]) {
				if ($key != "") $person->names_read = true;
				$person = null;
				$key = $row["i_key"];
				$person =& Person::GetInstance($row["i_id"], $row);
				$indilist[$row["i_key"]] = $person;
			}
			if ($person->disp_name) $indilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
		}
		if ($key != "") $person->names_read = true;
		$res->FreeResult();
		return $indilist;
	}
	
	/**
	 * Search the dates table for families that had events on the given day
	 *
	 * @param	int $day the day of the month to search for, leave empty to include all
	 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
	 * @param	int $year the year to search for, leave empty to include all
	 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
	 * 				prepend the fact with a ! to not include that fact
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myfamlist array with all individuals that matched the query
	 */
	private function SearchFamsDateRange($dstart="1", $mstart="1", $ystart, $dend="31", $mend="12", $yend, $filter="", $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
		
		$famlist = array();
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec FROM ".TBLPREFIX."dates, ".TBLPREFIX."families WHERE f_key=d_key ";
	
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
		if ($onlyBDM == "yes") $sql .= " AND d_fact='MARR'";
		$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";
	
		$res = NewQuery($sql);
		$select = array();
		while($row = $res->fetchAssoc()) {
			$fam =& Family::GetInstance($row["f_id"], $row);
			$famlist[$row["f_key"]] = $fam;
			if ($row["f_husb"] != "" && !Person::IsInstance(SplitKey($row["f_husb"], "id"), $row["f_file"])) $select[] = $row["f_husb"];
			if ($row["f_wife"] != "" && !Person::IsInstance(SplitKey($row["f_wife"], "id"), $row["f_file"])) $select[] = $row["f_wife"];
		}
		$res->FreeResult();
		if (count($select) > 0) {
			array_flip(array_flip($select));
			//print "Indi's selected for fams: ".count($select)."<br />";
			$selection = "'".implode("','", $select)."'";
			ListFunctions::GetIndilist($allgeds, $selection, false);
		}
		if ($filter == "living") {
			foreach($famlist as $key => $fam) {
				// If both are dead, delete, delete the record
				if (($fam->husb_id != "" && $fam->husb->isdead) && ($fam->wife_id != "" && $fam->wife->isdead)) {
					// print $fam->xref." ".$fam->husb->isdead." ".$fam->wife->isdead."<br />";
					unset($famlist[$key]);
				}
			}
		}
		return $famlist;
	}
	
	/**
	 * Search the dates table for other records that had events on the given day
	 *
	 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
	 * @return	array $myfamlist array with all individuals that matched the query
	 */
	private function SearchOtherDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		$repolist = array();
		
		$sql = "SELECT o_key, o_id, o_file, o_gedrec, d_gid FROM ".TBLPREFIX."dates, ".TBLPREFIX."other WHERE o_id=d_gid AND o_file=d_file AND o_type='REPO' ";
		
		$sql .= self::DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
		$sql .= "GROUP BY o_id ORDER BY d_year, d_month, d_day DESC";
	
		$res = NewQuery($sql);
		while($row = $res->fetchAssoc()){
			$repo = null;
			$repo =& Repository::GetInstance($row["o_id"], $row, $row["o_file"]);
			$repolist[$row["o_key"]] = $repo;
		}
		$res->FreeResult();
		return $repolist;
	}
	
	private function DateRangeforQuery($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
		
		//-- Compute start
		$sql = "";
		// SQL for 1 day
		if ($dstart == $dend && $mstart == $mend && $ystart == $yend) {
			$sql .= " AND d_day=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
			if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$ystart."'";
		}
		// SQL for dates in 1 month
		else if ($mstart == $mend && $ystart == $yend) {
			$sql .= " AND d_day BETWEEN ".$dstart." AND ".$dend." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
			if (!empty($ystart) && !empty($yend)) $sql .= " AND d_year='".$ystart."'";
		}
		// SQL for >=2 months
		else {
			$sql .= " AND d_day!='0' AND ((d_day>=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1));
			if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$ystart;
			$sql .= "')";
			//-- intermediate months
			if (!empty($ystart) && !empty($yend)) {
				if ($mend < $mstart) $mend = $mend + 12*($yend-$ystart);
				else $mend = $mend + 12*($yend-$ystart);
			}
			else if ($mend < $mstart) $mend += 12;
			for($i=$mstart+1; $i<$mend;$i++) {
				if ($i>12) {
					$m = $i%12;
					if (!empty($ystart) && !empty($yend)) $y = $ystart + (($i - ($i % 12)) / 12);
				}
				else {
					$m = $i;
					if (!empty($ystart) && !empty($yend)) $y = $ystart;
				}
				$sql .= " OR (d_month='".date("M", mktime(1,0,0,$m,1))."'";
				if (!empty($ystart) && !empty($yend)) $sql .= " AND d_year='".$y."'";
				$sql .= ")";
			}
			//-- End 
			$sql .= " OR (d_day<=".$dend." AND d_month='".date("M", mktime(1,0,0,$mend,1));
			if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$yend;
			$sql .= "')";
			$sql .= ")";
		}
		if (!empty($skipfacts)) {
			$skip = preg_split("/[;, ]/", $skipfacts);
			$sql .= " AND d_fact NOT IN (";
			$i = 0;
			foreach ($skip as $key=>$value) {
				if ($i != 0 ) $sql .= ", ";
				$i++; 
				$sql .= "'".$value."'";
			}
		}
	
		if (!empty($onlyfacts)) {
			$only = preg_split("/[;, ]/", $onlyfacts);
			$sql .= " AND d_fact IN (";
			$i = 0;
			foreach ($only as $key=>$value) {
				if ($i != 0 ) $sql .= ", ";
				$i++; 
				$sql .= "'".$value."'";
			}
			$sql .= ")";
		}
		else $sql .= ")";	
		
		if (!$allgeds) $sql .= " AND d_file='".GedcomConfig::$GEDCOMID."' ";
		// General part ends here
		
		return $sql;
	}
}
?>