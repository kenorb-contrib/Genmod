<?php
/**
 * On This Day Events Block
 *
 * This block will print a list of today's events
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
 * @subpackage Blocks
 * @version $Id: recent_changes.php,v 1.5 2006/04/17 20:01:52 roland-d Exp $
 */

$GM_BLOCKS["print_recent_changes"]["name"]        = $gm_lang["recent_changes_block"];
$GM_BLOCKS["print_recent_changes"]["descr"]        = "recent_changes_descr";
$GM_BLOCKS["print_recent_changes"]["canconfig"]        = true;
$GM_BLOCKS["print_recent_changes"]["config"] = array("days"=>30, "hide_empty"=>"no");

//-- Recent Changes block
//-- this block prints a list of changes that have occurred recently in your gedcom
function print_recent_changes($block=true, $config="", $side, $index) {
	global $gm_lang, $factarray, $month, $year, $day, $monthtonum, $HIDE_LIVE_PEOPLE, $SHOW_ID_NUMBERS, $command, $TEXT_DIRECTION, $SHOW_FAM_ID_NUMBERS;
	global $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $REGEXP_DB, $DEBUG, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $TOTAL_QUERIES, $LAST_QUERY, $GM_BLOCKS, $SHOW_SOURCES;
    global $medialist, $gm_username;

	$block = true;			// Always restrict this block's height

	if ($command=="user") $filter = "living";
	else $filter = "all";

	if (empty($config)) $config = $GM_BLOCKS["print_recent_changes"]["config"];
	if ($config["days"]<1 or $config["days"]>30) $config["days"] = 30;
	if (isset($config["hide_empty"])) $HideEmpty = $config["hide_empty"];
	else $HideEmpty = "no";


	$daytext = "";
	$action = "today";
	$dayindilist = array();
	$dayfamlist = array();
	$daysourcelist = array();
	$dayrepolist = array();
	$found_facts = array();
	// don't cache this block
	$monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day,$year);
	$mmon = strtolower(date("M", $monthstart));
	$mmon2 = strtolower(date("M", $monthstart-(60*60*24*$config["days"])));
	$mday2 = date("d", $monthstart-(60*60*24*$config["days"]));
	$myear2 = date("Y", $monthstart-(60*60*24*$config["days"]));

	$fromdate = $myear2.date("m", $monthstart-(60*60*24*$config["days"])).$mday2;
	if ($day < 10)
	     $mday3 = "0".$day;
	else $mday3 = $day;
	$todate   = $year.date("m", $monthstart).$mday3;

	$dayindilist = search_indis_dates("", $mmon, $year, "CHAN");
	$dayfamlist = search_fams_dates("", $mmon, $year, "CHAN");
	if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) $dayrepolist = search_other_dates("", $mmon, $year, "CHAN");
	if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) $daysourcelist = search_sources_dates("", $mmon, $year, "CHAN");
	if ($mmon!=$mmon2) {
		$dayindilist2 = search_indis_dates("", $mmon2, $myear2, "CHAN");
		$dayfamlist2 = search_fams_dates("", $mmon2, $myear2, "CHAN");
		if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) $dayrepolist2 = search_other_dates("", $mmon2, $myear2, "CHAN");
		if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) $daysourcelist2 = search_sources_dates("", $mmon2, $myear2, "CHAN");
		$dayindilist = gm_array_merge($dayindilist, $dayindilist2);
		$dayfamlist = gm_array_merge($dayfamlist, $dayfamlist2);
		if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) $daysourcelist = gm_array_merge($daysourcelist, $daysourcelist2);
		if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) $dayrepolist = gm_array_merge($dayrepolist, $dayrepolist2);
	}

	if ((count($dayindilist)>0)||(count($dayfamlist)>0)||(count($daysourcelist)>0)) {
		$found_facts = array();
		$last_total = $TOTAL_QUERIES;
		foreach($dayindilist as $gid=>$indi) {
			$disp = true;
			if (($filter=="living")&&(is_dead_id($gid)==1)) $disp = false;
			else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($gid);
			if ($disp) {
				$i = 1;
				$factrec = get_sub_record(1, "1 CHAN", $indi["gedcom"], $i);
				while(!empty($factrec)) {
					$ct = preg_match("/2 DATE (.*)/", $factrec, $match);
					if ($ct>0) {
						$date = parse_date(trim($match[1]));

                        $datemonth=$monthtonum[str2lower($date[0]["month"])];
				        if ($datemonth > 0 && $datemonth < 10) $datemonth = "0".$datemonth;
		                $factdate =  $date[0]["year"].$datemonth.$date[0]["day"];
                        if ($factdate <= $todate && $factdate > $fromdate) {
							$found_facts[] = array($gid, $factrec, "INDI");
						}
					}
					$i++;
					$factrec = get_sub_record(1, "1 CHAN", $indi["gedcom"], $i);
				}
			}
		}
		foreach($dayfamlist as $gid=>$fam) {
			$disp = true;
			if ($filter=="living") {
				$parents = find_parents_in_record($fam["gedcom"]);
				if (is_dead_id($parents["HUSB"])==1) $disp = false;
				else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($parents["HUSB"]);
				if ($disp) {
					if (is_dead_id($parents["WIFE"])==1) $disp = false;
					else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($parents["WIFE"]);
				}
			}
			else if ($HIDE_LIVE_PEOPLE) $disp = displayDetailsByID($gid, "FAM");
			if ($disp) {
				$i = 1;
				$factrec = get_sub_record(1, "1 CHAN", $fam["gedcom"], $i);
				while(!empty($factrec)) {
					$ct = preg_match("/2 DATE (.*)/", $factrec, $match);
					if ($ct>0) {
						$date = parse_date(trim($match[1]));

                        $datemonth=$monthtonum[str2lower($date[0]["month"])];
				        if ($datemonth > 0 && $datemonth < 10) $datemonth = "0".$datemonth;
		                $factdate =  $date[0]["year"].$datemonth.$date[0]["day"];

                        if ($factdate <= $todate && $factdate > $fromdate) {
							$found_facts[] = array($gid, $factrec, "FAM");
						}
					}
					$i++;
					$factrec = get_sub_record(1, "1 CHAN", $fam["gedcom"], $i);
				}
			}
		}
		foreach($daysourcelist as $gid=>$source) {
			$disp = true;
			$disp = displayDetailsByID($gid, "SOUR");
			if ($disp) {
				$i = 1;
				$factrec = get_sub_record(1, "1 CHAN", $source["gedcom"], $i);
				while(!empty($factrec)) {
					$ct = preg_match("/2 DATE (.*)/", $factrec, $match);
					if ($ct>0) {
						$date = parse_date(trim($match[1]));

                        $datemonth=$monthtonum[str2lower($date[0]["month"])];
				        if ($datemonth > 0 && $datemonth < 10) $datemonth = "0".$datemonth;
		                $factdate =  $date[0]["year"].$datemonth.$date[0]["day"];
                        if ($factdate <= $todate && $factdate > $fromdate) {
							$found_facts[] = array($gid, $factrec, "SOUR");
						}
					}
					$i++;
					$factrec = get_sub_record(1, "1 CHAN", $source["gedcom"], $i);
				}
			}
		}
		foreach($dayrepolist as $rid=>$repo) {
			$disp = false;
			if ($repo["type"] == "REPO") {
				$disp = displayDetailsByID($rid, "REPO");
				if ($disp) {
					$i = 1;
					$factrec = get_sub_record(1, "1 CHAN", $repo["gedcom"], $i);
					while(!empty($factrec)) {
						$ct = preg_match("/2 DATE (.*)/", $factrec, $match);
						if ($ct>0) {
							$date = parse_date(trim($match[1]));

	                        $datemonth=$monthtonum[str2lower($date[0]["month"])];
					        if ($datemonth > 0 && $datemonth < 10) $datemonth = "0".$datemonth;
			                $factdate =  $date[0]["year"].$datemonth.$date[0]["day"];
	                        if ($factdate <= $todate && $factdate > $fromdate) {
								$found_facts[] = array($rid, $factrec, "REPO");
							}
						}
						$i++;
						$factrec = get_sub_record(1, "1 CHAN", $repo["gedcom"], $i);
					}
				}
			}
			else if ($repo["type"] == "OBJE") {
				$disp = displayDetailsByID($rid, "OBJE");
				if ($disp) {
					$i = 1;
					$factrec = get_sub_record(1, "1 CHAN", $repo["gedcom"], $i);
					while(!empty($factrec)) {

						$ct = preg_match("/2 DATE (.*)/", $factrec, $match);
						if ($ct>0) {
							$date = parse_date(trim($match[1]));

	                        $datemonth=$monthtonum[str2lower($date[0]["month"])];
					        if ($datemonth > 0 && $datemonth < 10) $datemonth = "0".$datemonth;
			                $factdate =  $date[0]["year"].$datemonth.$date[0]["day"];
	                        if ($factdate <= $todate && $factdate > $fromdate) {
								$found_facts[] = array($rid, $factrec, "OBJE");
							}
						}
						$i++;
						$factrec = get_sub_record(1, "1 CHAN", $repo["gedcom"], $i);
					}
				}
			}
		}
	}
//-- store the results in the session to improve speed of future page loads
//	$_SESSION["recent_changes"][$command][$GEDCOM] = $found_facts;

// Start output
	if (count($found_facts)==0 and $HideEmpty=="yes") return false;
	//	Print block header
	print "<div id=\"recent_changes\" class=\"block\">";
	print "<div class=\"blockhc\">";
	print_help_link("recent_changes_help", "qm");
	if ($GM_BLOCKS["print_recent_changes"]["canconfig"]) {
		$username = $gm_username;
		if ((($command=="gedcom")&&(userGedcomAdmin($username))) || (($command=="user")&&(!empty($username)))) {
			if ($command=="gedcom") $name = preg_replace("/'/", "\'", $GEDCOM);
			else $name = $username;
			print "<a href=\"javascript: configure block\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
			print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
		}
	}
	print $gm_lang["recent_changes"];
	print "</div>";
	print "<div class=\"blockcontent\" >";
	if ($block) print "<div class=\"small_inner_block\">\n";

	//	Print block content
	$gm_lang["global_num1"] = $config["days"];		// Make this visible
	if (count($found_facts)==0) {
		print_text("recent_changes_none");
	} else {
		print_text("recent_changes_some");
		$ASC = 1;
		$IGNORE_FACTS = 1;
		$IGNORE_YEAR = 0;
		uasort($found_facts, "compare_facts");
		$lastgid="";
		foreach($found_facts as $index=>$factarr) {
			if ($factarr[2]=="INDI") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid)) {
					$indirec = find_person_record($gid);
					if ($lastgid!=$gid) {
						$name = check_NN(get_sortable_name($gid));
						print "<a href=\"individual.php?pid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
						print "<img id=\"box-".$gid."-".$index."-sex\" src=\"$GM_IMAGE_DIR/";
						if (preg_match("/1 SEX M/", $indirec)>0) print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
						else  if (preg_match("/1 SEX F/", $indirec)>0) print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
						else print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
						print "\" class=\"sex_image\" />";
						if ($SHOW_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print "&lrm;($gid)&lrm;";
						   else print "&rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print $factarray["CHAN"];
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".get_changed_date($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}

			if ($factarr[2]=="FAM") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "FAM")) {
					$famrec = find_family_record($gid);
					$name = get_family_descriptor($gid);
					if ($lastgid!=$gid) {
						print "<a href=\"family.php?famid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
						if ($SHOW_FAM_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print " &lrm;($gid)&lrm;";
						   else print " &rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print $factarray["CHAN"];
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".get_changed_date($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}

			if ($factarr[2]=="SOUR") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "SOUR")) {
					$sourcerec = find_source_record($gid);
					$name = get_source_descriptor($gid);
					if ($lastgid!=$gid) {
						print "<a href=\"source.php?sid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
						if ($SHOW_FAM_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print " &lrm;($gid)&lrm;";
						   else print " &rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print $factarray["CHAN"];
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".get_changed_date($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}

			if ($factarr[2]=="REPO") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "REPO")) {
					$reporec = find_repo_record($gid);
					$name = get_repo_descriptor($gid);
					if ($lastgid!=$gid) {
						print "<a href=\"repo.php?rid=$gid&amp;ged=".$GEDCOM."\"><b>".PrintReady($name)."</b>";
						if ($SHOW_FAM_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print " &lrm;($gid)&lrm;";
						   else print " &rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print $factarray["CHAN"];
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".get_changed_date($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}
			if ($factarr[2]=="OBJE") {
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if (displayDetailsById($gid, "OBJE")) {
					$mediarec = find_media_record($gid);
					if (isset($medialist[0]["title"]) && $medialist[0]["title"] != "") $title=$medialist[0]["title"];
					else $title = $medialist[0]["file"];
					$SearchTitle = preg_replace("/ /","+",$title);
					if ($lastgid!=$gid) {
 						print "<a href=\"medialist.php?action=filter&amp;search=yes&amp;filter=$SearchTitle&amp;ged=".$GEDCOM."\"><b>".PrintReady($title)."</b>";
						if ($SHOW_FAM_ID_NUMBERS) {
						   if ($TEXT_DIRECTION=="ltr")
								print " &lrm;($gid)&lrm;";
						   else print " &rlm;($gid)&rlm;";
						}
						print "</a><br />\n";
						$lastgid=$gid;
					}
					print "<div class=\"indent" . ($TEXT_DIRECTION=="rtl"?"_rtl":"") . "\">";
					print $factarray["CHAN"];
					$ct = preg_match("/\d DATE (.*)/", $factrec, $match);
					if ($ct>0) {
							print " - <span class=\"date\">".get_changed_date($match[1]);
							$tt = preg_match("/3 TIME (.*)/", $factrec, $match);
							if ($tt>0) {
									print " - ".$match[1];
							}
							print "</span>\n";
					}
					print "</div><br />";
				}
			}
		}

	}

	if ($block) print "</div>\n"; //small_inner_block
	print "</div>"; // blockcontent
	print "</div>"; // block

}

function print_recent_changes_config($config) {
	global $gm_lang, $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["print_recent_changes"]["config"];

	print "<tr><td width=\"20%\" class=\"shade2\">".$gm_lang["days_to_show"]."</td>";?>
	<td class="shade1">
		<input type="text" name="days" size="2" value="<?php print $config["days"]; ?>" />
	</td></tr>

	<?php
  	print "<tr><td width=\"20%\" class=\"shade2\">".$gm_lang["show_empty_block"]."</td>";?>
	<td class="shade1">
	<select name="hide_empty">
		<option value="no"<?php if ($config["hide_empty"]=="no") print " selected=\"selected\"";?>><?php print $gm_lang["no"]; ?></option>
		<option value="yes"<?php if ($config["hide_empty"]=="yes") print " selected=\"selected\"";?>><?php print $gm_lang["yes"]; ?></option>
	</select>
	</td></tr>
	<tr><td colspan="2" class="shade1 wrap">
		<span class="error"><?php print $gm_lang["hide_block_warn"]; ?></span>
	</td></tr>
	<?php
}
?>
