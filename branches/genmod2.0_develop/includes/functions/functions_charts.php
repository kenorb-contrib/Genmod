<?php
/**
 * Functions used for charts
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
 * @subpackage Charts
 * @version $Id$
 */
if (strstr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * print a table cell with sosa number
 *
 * @param int $sosa
 * @param string $pid optional pid
 */
function PrintSosaNumber($sosa, $pid = "") {
	global $view, $pbwidth, $pbheight;
	global $GM_IMAGE_DIR, $GM_IMAGES;

	print "<td class=\"subheaders\" style=\"vertical-align: middle; white-space: nowrap;\">";
	print $sosa;
	if ($sosa != "1") {
		print "<br />";
		PrintUrlArrow($pid, "#$pid", "#$pid");
		print "&nbsp;";
	}
	print "</td>";
}

/**
 * print family header
 *
 * @param string $famid family gedcom ID
 */
function PrintFamilyHeader($famid, $famrec="", $changes = false) {
	global $gm_lang;

	//-- check if we can display both parents
	if (empty($famrec)) $parents = FindParents($famid);
	else $parents = FindParentsInRecord($famrec);

	if (displayDetailsByID($famid, "FAM") || showLivingNameByID($parents["HUSB"]) || showLivingNameByID($parents["WIFE"])) {
		$fam = GetFamilyDescriptor($famid, true, $famrec, $changes);
		$addfam = GetFamilyAddDescriptor($famid, true, $famrec, $changes);
	}
	else {
		$fam = $gm_lang["private"];
		$addfam = "";
	}
	print "<p class=\"name_head\">".PrintReady($fam);
	if ($addfam != $fam) print "<br />".PrintReady($addfam);
	print "</p>\r\n";
}

/**
 * print the parents table for a family
 *
 * @param string $famid family gedcom ID
 * @param int $sosa optional child sosa number
 * @param string $label optional indi label (descendancy booklet)
 * @param string $parid optional parent ID (descendancy booklet)
 * @param string $gparid optional gd-parent ID (descendancy booklet)
 */
function PrintFamilyParents($famid, $sosa = 0, $label="", $parid="", $gparid="", $personcount="1") {
	global $gm_lang, $view, $show_full, $show_famlink;
	global $TEXT_DIRECTION, $SHOW_EMPTY_BOXES, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS, $LANGUAGE;
	global $pbwidth, $pbheight;
	global $GM_IMAGE_DIR, $GM_IMAGES;
	global $show_changes, $GEDCOM, $gm_username, $Users;

	$hfamids = 0;
	$famrec = FindFamilyRecord($famid);
//	Removed, this causes changes in parents not to show
//	if ((!isset($show_changes) ||$show_changes != "no") && UserCanEdit($gm_username) && GetChangeData(true, $famid, true, "", "")) {
//		$rec = GetChangeData(false, $famid, true, "gedlines", "");
//		$famrec = $rec[$GEDCOM][$famid];
//	}
	$parents = FindParentsInRecord($famrec);
	print "<a name=\"" . $parents["HUSB"] . "\"></a>\r\n";
	print "<a name=\"" . $parents["WIFE"] . "\"></a>\r\n";

	if ((!isset($show_changes) ||$show_changes != "no") && $Users->UserCanEdit($gm_username)) PrintFamilyHeader($famid, $famrec, true);
	else PrintFamilyHeader($famid, $famrec);

	// -- get the new record and parents if in editing show changes mode
	$recchanged = false;
	if ((!isset($show_changes) ||$show_changes != "no") && $Users->UserCanEdit($gm_username) && GetChangeData(true, $famid, true, "", "")) {
		$rec = GetChangeData(false, $famid, true, "gedlines", "");
		$newrec = $rec[$GEDCOM][$famid];
		$newparents = FindParentsInRecord($newrec);
		$recchanged = true;
	}
	if (!$recchanged) {
		$oldhusb = true;
		$newhusb = false;
		$oldwife = true;
		$newwife = false;
	}
	else {
		if (!isset($parents["HUSB"]) || empty ($parents["HUSB"])) $oldhusb = false;
		else $oldhusb = true;
		if (!isset($parents["WIFE"]) || empty ($parents["WIFE"])) $oldwife = false;
		else $oldwife = true;
		if (!isset($newparents["HUSB"]) || empty ($newparents["HUSB"])) $newhusb = false;
		else $newhusb = true;
		if (!isset($newparents["WIFE"]) || empty ($newparents["WIFE"])) $newwife = false;
		else $newwife = true;
		if (isset($parents["HUSB"]) && !empty($parents["HUSB"]) && isset($newparents["HUSB"]) && !empty($newparents["HUSB"]) && $parents["HUSB"] == $newparents["HUSB"]) $newhusb = false;
		if (isset($parents["WIFE"]) && !empty($parents["WIFE"]) && isset($newparents["WIFE"]) && !empty($newparents["WIFE"]) && $parents["WIFE"] == $newparents["WIFE"]) $newwife = false;
		if ($recchanged && isset($newparents) && empty($newparents["WIFE"])) $newwife = true;
		if ($recchanged && isset($newparents) && empty($newparents["HUSB"])) $newhusb = true;
	}
	/**
	 * husband side
	 */
	print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td rowspan=\"2\" style=\"vertical-align:middle;\">";
	print "<span class=\"subheaders\">" . GetSosaName($sosa*2) . "</span>";
	print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
	if ($parid) {
		if ($parents["HUSB"]==$parid) PrintSosaNumber($label);
		else PrintSosaNumber(str_repeat("&nbsp; ", strlen($label)-1));
	}
	else if ($sosa > 0) PrintSosaNumber($sosa * 2);

	if (!$newhusb) {
		print "\n\t<td style=\"vertical-align:middle;\">";
		print_pedigree_person($parents['HUSB'], 1, $show_famlink, 2, $personcount);
		$hfamids = FindFamilyIds($parents['HUSB']);
	}
	else {
		if ($oldhusb && empty($newparents['HUSB'])) {
			print "\n\t<td style=\"vertical-align:middle;\" class=\"facts_valuered\">";
			print_pedigree_person($parents['HUSB'], 1, $show_famlink, 2, $personcount);
			$hfamids = FindFamilyIds($parents['HUSB']);
		}
		if (!empty($newparents['HUSB'])) {
			print "\n\t<td style=\"vertical-align:middle;\" class=\"facts_valueblue\">";
			print_pedigree_person($newparents['HUSB'], 1, $show_famlink, 2, $personcount);
			if (!$oldhusb) $hfamids = FindFamilyIds($newparents['HUSB']);
		}
	}
	print "</td></tr></table>";
	print "</td>\n";
	
	// husband's parents
	$hparents = false;
	$upfamid = "";
	if (!empty($hfamids[0]["famid"]) or ($sosa != 0 and $SHOW_EMPTY_BOXES)) {
		print "<td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" width=\"3\" height=\"" . ($pbheight) . "\" alt=\"\" /></td>";
		print "<td style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
		$hparents = false;
		$j = 0;
		while ((!$hparents) && ($j < count($hfamids))) {
			$hparents = FindParents($hfamids[$j]["famid"]);
			$upfamid = $hfamids[$j]["famid"];
			$j++;
		}
		if ($hparents or ($sosa != 0 and $SHOW_EMPTY_BOXES)) {
			// husband's father
			print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
			if ($sosa > 0) PrintSosaNumber($sosa * 4);
			if (!empty($gparid) and $hparents['HUSB']==$gparid) PrintSosaNumber(trim(substr($label,0,-3),".").".");
			print "\n\t<td style=\"vertical-align:middle;\">";
			print_pedigree_person($hparents['HUSB'], 1, $show_famlink, 4, $personcount);
			print "</td></tr></table>";
		}
		print "</td>";
	}
	if (!empty($upfamid) and ($sosa!=-1) and ($view != "preview")) {
		print "<td style=\"vertical-align:middle;\" rowspan=\"2\">";
		
		PrintUrlArrow($upfamid, ($sosa==0 ? "?famid=$upfamid&amp;show_full=$show_full" : "#$upfamid"), PrintReady($gm_lang["start_at_parents"]."&nbsp;-&nbsp;".htmlspecialchars(GetFamilyDescriptor($upfamid, true, "", false, false))), 1);
		print "</td>\n";
	}
	if ($hparents or ($sosa != 0 and $SHOW_EMPTY_BOXES)) {
		// husband's mother
		print "</tr><tr><td style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
		print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
		if ($sosa > 0) PrintSosaNumber($sosa * 4 + 1);
		if (!empty($gparid) and $hparents['WIFE']==$gparid) PrintSosaNumber(trim(substr($label,0,-3),".").".");
		print "\n\t<td style=\"vertical-align:middle;\">";
		print_pedigree_person($hparents['WIFE'], 1, $show_famlink, 5, $personcount);
		print "</td></tr></table>";
		print "</td>\n";
	}
	print "</tr></table>\n\n";
	if ($sosa!=0) {
		print "<a href=\"family.php?famid=$famid\" class=\"details1\">";
		if ($SHOW_FAM_ID_NUMBERS) print "($famid)&nbsp;&nbsp;";
		else print str_repeat("&nbsp;", 10);
		if (showFact("MARR", $famid) && DisplayDetailsByID($parents["WIFE"]) && DisplayDetailsByID($parents["HUSB"])) print_simple_fact($famrec, "MARR", $parents["WIFE"]); else print $gm_lang["private"];
		print "</a>";
	}
	else print "<br />\n";

	/**
	 * wife side
	 */
	print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td rowspan=\"2\" style=\"vertical-align:middle;\">";
	print "<span class=\"subheaders\">" . GetSosaName($sosa*2+1) . "</span>";
	print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\"><tr>";
	if ($parid) {
		if ($parents["WIFE"]==$parid) PrintSosaNumber($label);
		else PrintSosaNumber(str_repeat("&nbsp; ", strlen($label)-1));
	}
	else if ($sosa > 0) PrintSosaNumber($sosa * 2 + 1);
	$hfamids = array();
	if (!$newwife) {
		print "\n\t<td style=\"vertical-align:middle;\">";
		print_pedigree_person($parents['WIFE'], 1, $show_famlink, 2, $personcount);
		$hfamids = FindFamilyIds($parents['WIFE']);
	}
	else {
		if ($oldwife && empty($newparents['WIFE'])) {
			print "\n\t<td style=\"vertical-align:middle;\" class=\"facts_valuered\">";
			print_pedigree_person($parents['WIFE'], 1, $show_famlink, 2, $personcount);
			$hfamids = FindFamilyIds($parents['WIFE']);
		}
		if (!empty($newparents['WIFE'])) {
			print "\n\t<td style=\"vertical-align:middle;\" class=\"facts_valueblue\">";
			print_pedigree_person($newparents['WIFE'], 1, $show_famlink, 2, $personcount);
			if (!$oldwife) $hfamids = FindFamilyIds($newparents['WIFE']);
		}
	}
	print "</td></tr></table>";
	print "</td>\n";
	
	// wife's parents
	$hparents = false;
	$upfamid = "";
	if (!empty($hfamids[0]["famid"]) or ($sosa != 0 and $SHOW_EMPTY_BOXES)) {
		print "<td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" width=\"3\" height=\"" . ($pbheight) . "\" alt=\"\" /></td>";
		print "<td style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
		$j = 0;
		while ((!$hparents) && ($j < count($hfamids))) {
			$hparents = FindParents($hfamids[$j]["famid"]);
			$upfamid = $hfamids[$j]["famid"];
			$j++;
		}
		if ($hparents or ($sosa != 0 and $SHOW_EMPTY_BOXES)) {
			// wife's father
			print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\"><tr>";
			if ($sosa > 0) PrintSosaNumber($sosa * 4 + 2);
			if (!empty($gparid) and $hparents['HUSB']==$gparid) PrintSosaNumber(trim(substr($label,0,-3),".").".");
			print "\n\t<td style=\"vertical-align:middle;\">";
			print_pedigree_person($hparents['HUSB'], 1, $show_famlink, 6, $personcount);
			print "</td></tr></table>";
		}
		print "</td>\n";
	}
	if (!empty($upfamid) and ($sosa!=-1) and ($view != "preview")) {
		print "<td style=\"vertical-align:middle;\" rowspan=\"2\">";
		PrintUrlArrow($upfamid.$label, ($sosa==0 ? "?famid=$upfamid&amp;show_full=$show_full" : "#$upfamid"), PrintReady($gm_lang["start_at_parents"]."&nbsp;-&nbsp;".htmlspecialchars(GetFamilyDescriptor($upfamid, true, "", false, false))), 1);
		print "</td>\n";
	}
	if ($hparents or ($sosa != 0 and $SHOW_EMPTY_BOXES)) {
		// wife's mother
		print "</tr><tr><td style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
		print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\"><tr>";
		if ($sosa > 0) PrintSosaNumber($sosa * 4 + 3);
		if (!empty($gparid) and $hparents['WIFE']==$gparid) PrintSosaNumber(trim(substr($label,0,-3),".").".");
		print "\n\t<td style=\"vertical-align:middle;\">";
		print_pedigree_person($hparents['WIFE'], 1, $show_famlink, 7, $personcount);
		print "</td></tr></table>\n";
		print "</td>\n";
	}
	print "</tr></table>\n\n";
}

/**
 * print the children table for a family
 *
 * @param string $famid family gedcom ID
 * @param string $childid optional child ID
 * @param int $sosa optional child sosa number
 * @param string $label optional indi label (descendancy booklet)
 */
function PrintFamilyChildren($famid, $childid = "", $sosa = 0, $label="", $personcount="1") {
	global $gm_lang, $pbwidth, $pbheight, $view, $show_famlink, $show_cousins;
	global $GM_IMAGE_DIR, $GM_IMAGES, $show_changes, $GEDCOM, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS, $TEXT_DIRECTION, $gm_username, $Users;

	if ((!isset($show_changes) ||$show_changes != "no") && $Users->UserCanEdit($gm_username)) $canshow = true;
	else $canshow = false;
	 
	$children = GetChildrenIds($famid);
	
	print "<table border=\"0\" cellpadding=\"0\" cellspacing=\"2\"><tr>";
	if ($sosa>0) print "<td></td>";
	print "<td><span class=\"subheaders\">".$gm_lang["children"]."</span></td>";
	if ($sosa>0) print "<td></td><td></td>";
	print "</tr>\n";

	$newchildren = array();
	$oldchildren = array();
	if ($Users->userCanEdit($gm_username)) {
		$oldchil = array();
		if (!empty($famid) && $canshow && (GetChangeData(true, $famid, true, "", "CHIL,FAM"))) {
			$rec = GetChangeData(false, $famid, true, "gedlines", "CHIL,FAM");
			$newrec = $rec[$GEDCOM][$famid];
			$ct = preg_match_all("/1 CHIL @(.*)@/", $newrec, $match, PREG_SET_ORDER);
			for($i = 0; $i < $ct; $i++) {
				if (!in_array($match[$i][1], $children)) $newchildren[] = $match[$i][1];
				else $oldchil[] = $match[$i][1];
			}
			foreach($children as $indexval => $chil) {
				if (!in_array($chil, $oldchil)) $oldchildren[] = $chil;
			}
		}
//		if ((!isset($show_changes) || $show_changes != "no") && (GetChangeData(true, $famid, true, "", "FAM"))) {
//			$rec = GetChangeData(false, $famid, true, "gedlines", "FAM");
//			$newrec = $rec[$GEDCOM][$famid];
//			$ct = preg_match_all("/1 CHIL @(.*)@/", $newrec, $match, PREG_SET_ORDER);
//			for($i = 0; $i < $ct; $i++) {
//				if (!in_array($match[$i][1], $children)) $newchildren[] = $match[$i][1];
//				else $oldchil[] = $match[$i][1];
//			}
//			foreach($children as $indexval => $chil) {
//				if (!in_array($chil, $oldchil)) $oldchildren[] = $chil;
//			}
//				//-- if there are no old or new children then the children were reordered
//				if ((count($newchildren)==0)&&(count($oldchildren)==0)) {
//					$children = array();
//					for($i = 0; $i < $ct; $i++) {
//						$children[] = $match[$i][1];
//					}
//				}
//		}
	}
	$nchi=1;
	if ((count($children) > 0) || (count($newchildren) > 0) || (count($oldchildren) > 0)) {
		// Get the new order of children
		if ($Users->userCanEdit($gm_username)) {
			if ($canshow && GetChangeData(true, $famid, true, "", "")) {
				$nowchildren = array();
				$rec = GetChangeData(false, $famid, true, "gedlines", "");
				$newrec = $rec[$GEDCOM][$famid];
				$ct = preg_match_all("/1 CHIL @(.*)@/", $newrec, $match, PREG_SET_ORDER);
				for($i = 0; $i < $ct; $i++) $nowchildren[] = $match[$i][1];
			}
			else $nowchildren = $children;
		}
		else $nowchildren = $children;
		foreach($nowchildren as $indexval => $chil) {
			if (!in_array($chil, $newchildren)) {
				print "<tr>\n";
				if ($sosa != 0) {
					if ($chil == $childid) PrintSosaNumber($sosa, $childid);
					else if (empty($label)) PrintSosaNumber("");
					else PrintSosaNumber($label.($nchi++).".");
				}
				print "<td style=\"vertical-align:middle;\" >";
				$indirec = FindPersonRecord($chil);
				if ($canshow && GetChangeData(true, $chil, true, "", "")) {
					$rec = GetChangeData(false, $chil, true, "gedlines", "");
					$indirec = $rec[$GEDCOM][$chil];
				}
				$pedirec = GetSubRecord(1, "1 FAMC @".$famid."@", $indirec);
				$pedi = GetGedcomValue("PEDI", 2, $pedirec);
				print GetPediName($pedi, GetGender($indirec));
				print_pedigree_person($chil, 1, $show_famlink, 8, $personcount);
				$personcount++;
				print "</td>";
				if ($sosa != 0) {
					// loop for all families where current child is a spouse
					$famids = FindSfamilyIds($chil, true);
					$maxfam = count($famids)-1;
					for ($f=0; $f<=$maxfam; $f++) {
						$famid = $famids[$f]["famid"];
						if (!$famid) continue;
						$parents = FindParents($famid);
						if (!$parents) continue;
						if ($parents["HUSB"] == $chil) $spouse = $parents["WIFE"];
						else $spouse =  $parents["HUSB"];
						// multiple marriages
						if ($f>0) {
							print "</tr>\n<tr><td>&nbsp;</td>";
							print "<td style=\"vertical-align:middle;\"";
							if ($TEXT_DIRECTION == "rtl") print " align=\"left\">";
							else print " align=\"right\">";
							if ($f==$maxfam) print "<img height=\"50%\"";
							else print "<img height=\"100%\"";
							print " width=\"3\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" alt=\"\" />";
							print "</td>";
						}
						print "<td class=\"details1\" style=\"vertical-align:middle;\" align=\"center\">";
 						$divrec = "";
						if (showFact("MARR", $famid) && DisplayDetailsByID($chil) && DisplayDetailsByID($spouse)) {
							// marriage date
							$famrec = FindFamilyRecord($famid);
							$ct = preg_match("/2 DATE.*(\d\d\d\d)/", GetSubRecord(1, "1 MARR", $famrec), $match);
							if ($ct>0) print "<span class=\"date\">".trim($match[1])."</span>";
							// divorce date
							$divrec = GetSubRecord(1, "1 DIV", $famrec);
							$ct = preg_match("/2 DATE.*(\d\d\d\d)/", $divrec, $match);
							if ($ct>0) print "-<span class=\"date\">".trim($match[1])."</span>";
						}
						print "<br /><img width=\"100%\" height=\"3\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" />";
						// family link
						if ($famid) {
							print "<br />";
							print "<a class=\"details1\" href=\"family.php?famid=$famid\">";
							if ($SHOW_FAM_ID_NUMBERS) print "&lrm;&nbsp;($famid)&nbsp;&lrm;";
							print "</a>";
						}
						print "</td>\n";
						// spouse information
						print "<td style=\"vertical-align: middle;";
						if (!empty($divrec) and ($view != "preview")) print " filter:alpha(opacity=40);-moz-opacity:0.4\">";
						else print "\">";
						print_pedigree_person($spouse, 1, $show_famlink, 9, $personcount);
						$personcount++;
						print "</td>\n";
						// cousins
						if ($show_cousins) {
							PrintCousins($famid, $personcount);
							$personcount++;
						}
					}
				}
				print "</tr>\n";
			}
		}
		foreach($newchildren as $indexval => $chil) {
			print "<tr >";
			print "<td valign=\"top\" class=\"facts_valueblue\" style=\"vertical-align:middle; width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\">\n";
			if ($canshow && GetChangeData(true, $chil, true, "", "")) {
				$rec = GetChangeData(false, $chil, true, "gedlines", "");
				$indirec = $rec[$GEDCOM][$chil];
			}
			else $indirec = FindPersonRecord($chil);
			$pedirec = GetSubRecord(1, "1 FAMC @".$famid."@", $indirec);
			$pedi = GetGedcomValue("PEDI", 2, $pedirec);
			print GetPediName($pedi, GetGender($indirec));
			print_pedigree_person($chil, 1, $show_famlink, 0, $personcount, $indirec);
			$personcount++;
			print "</td></tr>\n";
		}
		foreach($oldchildren as $indexval => $chil) {
			print "<tr >";
			print "<td style=\"vertical-align:middle;\" class=\"facts_valuered\" style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\">\n";
			$indirec = FindPersonRecord($chil);
			$pedirec = GetSubRecord(1, "1 FAMC @".$famid."@", $indirec);
			$pedi = GetGedcomValue("PEDI", 2, $pedirec);
			print GetPediName($pedi, GetGender($indirec));
			print_pedigree_person($chil, 1, $show_famlink, 0, $personcount);
			$personcount++;
			print "</td></tr>\n";
		}
		// message 'no children' except for sosa
   }
   else if ($sosa<1) {
			print "<tr><td></td>";
			print "<td valign=\"top\"><span class=\"label\">" . $gm_lang["no_children"] . "</span></td></tr>";
   }
   else {
	   print "<tr>\n";
	   PrintSosaNumber($sosa, $childid);
	   print "<td style=\"vertical-align:middle;\">";
	   print_pedigree_person($childid, 1, $show_famlink, 0, $personcount);
	   $personcount++;
	   print "</td></tr>\n";
   }
   print "</table><br />";

	if (($view != "preview") && ($sosa == 0) && ($Users->userCanEdit($gm_username))) {
		if (GetChangeData(true, $famid, true, "", "FAM")) {
			$rec = GetChangeData(false, $famid, true, "gedlines", "FAM");
		}
	}
}
/**
 * print the facts table for a family
 *
 * @param string $famid family gedcom ID
 * @param int $sosa optional child sosa number
 */
function PrintFamilyFacts($famid, $sosa = 0, $mayedit=true) {
	global $gm_lang, $pbwidth, $pbheight, $view, $hits;
	global $nonfacts, $factarray;
	global $TEXT_DIRECTION, $GEDCOM, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS;
	global $show_changes, $gm_username, $Users, $SHOW_COUNTER;
	
	// -- if both parents are displayable then print the marriage facts
	if (displayDetailsByID($famid, "FAM")) {
		// -- array of GEDCOM elements that will be found but should not be displayed
		$nonfacts = array("FAMS", "FAMC", "MAY", "BLOB", "HUSB", "WIFE", "CHIL", "_MEND", "");
		// -- find all the fact information
		$indifacts = array(); // -- array to store the fact records in for sorting and displaying
		$otheritems = array();
		$allfamsubs = GetAllSubrecords(FindFamilyRecord($famid), "", true, false, false);
		$f = 0;
		$count = array();
		foreach ($allfamsubs as $key => $subrecord) {
			$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
			if ($ft > 0) {
				$fact = $match[1];
				$gid = trim(str_replace("@", "", $match[2]));
			}
			else {
				$fact = "";
				$gid = "";
			}
			$fact = trim($fact);
			if (!isset($count[$fact])) $count[$fact] = 1;
			else $count[$fact]++;
			
			// -- handle special source fact case
			if ($fact == "SOUR") {
				$otheritems[] = array($fact, $subrecord, $count[$fact]);
			}
			// -- handle special media object case
			else if ($fact == "OBJE") {
				$otheritems[] = array($fact, $subrecord, $count[$fact]);
			}
			// -- handle special note fact case
			else if ($fact == "NOTE") {
				$otheritems[] = array($fact, $subrecord, $count[$fact]);
			} 
			else {
				if (!in_array($fact, $nonfacts)) {
					$indifacts[$f] = array($fact, $subrecord, $count[$fact]);
					$f++;
				}
			}
		} 
		if (($sosa == 0) && $Users->userCanEdit($gm_username)) {
			if ((!isset($show_changes)) || $show_changes != "no") {
				$newrecs = RetrieveNewFacts($famid);
				foreach ($newrecs as $key => $subrecord) {
					$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
					if ($ft > 0) {
						$fact = $match[1];
						$gid = trim(str_replace("@", "", $match[2]));
					}
					else {
						$fact = "";
						$gid = "";
					}
					$fact = trim($fact);
					if (!isset($count[$fact])) $count[$fact] = 1;
					else $count[$fact]++;
			
					// -- handle special source fact case
					if ($fact == "SOUR") {
						$otheritems[] = array($fact, $subrecord, $count[$fact], "new");
					}
					// -- handle special media object case
					else if ($fact == "OBJE") {
						$otheritems[] = array($fact, $subrecord, $count[$fact], "new");
					}
					// -- handle special note fact case
					else if ($fact == "NOTE") {
						$otheritems[] = array($fact, $subrecord, $count[$fact], "new");
					} 
					else {
						if (!in_array($fact, $nonfacts)) {
							$indifacts[$f] = array($fact, $subrecord, $count[$fact], "new");
							$f++;
						}
					}
				} 
			}
		}
		if ((count($indifacts) > 0) || (count($otheritems) > 0)) {
			//usort($indifacts, "CompareFacts");
			SortFacts($indifacts);
			
			print "\n\t<span class=\"subheaders\">" . $gm_lang["family_group_info"];
			if ($SHOW_FAM_ID_NUMBERS and $famid != "") print " ($famid)";
			print "</span>";
			if($SHOW_COUNTER) {
				// Print indi counter only if displaying a non-private person
				print "\n<span style=\"margin-left: 3px; vertical-align:bottom;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
			}
			
			print "<br />\n\t<table class=\"facts_table\">";
			foreach ($indifacts as $key => $value) {
				if (IsChangedFact($famid, $value[1])) {
					if (!isset($value[3]) || $value[3] != "new") print_fact($value[1], $famid, $value[0], $value[2], false, "change_old", $mayedit);
					$changedfact = RetrieveChangedFact($famid, $value[0], $value[1]);
					if ($changedfact) print_fact($changedfact, $famid, $value[0], $value[2], false, "change_new", $mayedit);
				}
				else if (isset($value[3]) && $value[3] == "new") print_fact($value[1], $famid, $value[0], $value[2], false, "change_new", $mayedit);
				else print_fact($value[1], $famid, $value[0], $value[2], false, "", $mayedit);
			}
			// do not print otheritems for sosa
			if ($sosa == 0) {
				foreach($otheritems as $key => $value) {
					$ft = preg_match("/1\s(\w+)\s(.*)/", $value[1], $match);
					if ($ft > 0) $fact = $match[1];
					else $fact = "";
					$fact = trim($fact);
					// -- handle special source fact case
					if ($fact == "SOUR") {
						if (IsChangedFact($famid, $value[1])) {
							if (!isset($value[3]) || $value[3] != "new") print_main_sources($value[1], 1, $famid, $value[2], "change_old", $mayedit);
							print_main_sources(RetrieveChangedFact($famid, $value[0], $value[1]), 1, $famid, $value[2], "change_new", $mayedit);
						}
						else if (isset($value[3]) && $value[3] == "new") print_main_sources($value[1], 1, $famid, $value[2], "change_new", $mayedit);
						else print_main_sources($value[1], 1, $famid, $value[2], "", $mayedit);
					}
					// -- handle special note fact case
					else if ($fact == "NOTE") {
						if (IsChangedFact($famid, $value[1])) {
						if (!isset($value[3]) || $value[3] != "new") print_main_notes($value[1], 1, $famid, $value[2], "change_old", $mayedit);
						print_main_notes(RetrieveChangedFact($famid, $value[0], $value[1]), 1, $famid, $value[2], "change_new", $mayedit);
					}
					else if (isset($value[3]) && $value[3] == "new") print_main_notes($value[1], 1, $famid, $value[2], "change_new", $mayedit);
					else print_main_notes($value[1], 1, $famid, $value[2], "", $mayedit);
					}
					// NOTE: Print the media
					else if ($fact == "OBJE") {
						$ct = preg_match("/\d\sOBJE\s@(.*)@/", $value[1], $match);
						$media_id = $match[1];
						$mediaged = FindMediaRecord($media_id);
						if (IsChangedFact($famid, $value[1]) || IsChangedFact($media_id, $mediaged)) {
							if (!isset($value[3]) || $value[3] != "new") print_main_media($value[1], $famid, 0, $value[2], false, "change_old", $mayedit);
							$factnew = RetrieveChangedFact($famid, $value[0], $value[1]);
							if (empty($factnew)) print_main_media($value[1], $famid, 0, true,  "change_new", $mayedit);
							else print_main_media($factnew, $famid, 0, true,  "change_new", $mayedit);
						}
						else if (isset($value[3]) && $value[3] == "new") print_main_media($value[1], $famid, 0, $value[2], true, "change_new", $mayedit);
						else print_main_media($value[1], $famid, 0, $value[2], false, "", $mayedit);
					}
				}
			}
		}
		else {
			if ($sosa==0) {
				print "\n\t<span class=\"subheaders\">" . $gm_lang["family_group_info"];
				if ($SHOW_FAM_ID_NUMBERS and $famid != "") print " ($famid)";
				print "</span><br />\n\t";
			}
			print "<table class=\"facts_table\">";
			if ($sosa == 0) {
				print "<tr><td class=\"messagebox\" colspan=\"2\">";
				print $gm_lang["no_family_facts"];
				print "</td></tr>\n";
			}
		}
		// -- new fact link
   		$print_add_link = false;
		if ($view != "preview" && $sosa == 0 && $Users->userCanEdit($gm_username) && $mayedit) {
			$print_add_link = true;
			if (GetChangeData(true, $famid, true, "", "FAM")) {
				$rec = GetChangeData(false, $famid, true, "gedlines", "FAM");
				if (empty($rec[$GEDCOM][$famid])) {
					$print_add_link = false;
   				}
			}
		}
		if ($print_add_link && $mayedit) {
			PrintAddNewFact($famid, $indifacts, "FAM");
			// -- new source citation
			print "<tr><td class=\"shade2\">";
			print_help_link("add_source_help", "qm", "add_source_lbl");
			print $gm_lang["add_source_lbl"] . "</td>";
			print "<td class=\"shade1\">";
			print "<a href=\"#\" onclick=\"return add_new_record('$famid','SOUR', 'add_source');\">" . $gm_lang["add_source"] . "</a>";
			print "<br />\n";
			print "</td></tr>\n";
			// -- new media
			print "<tr><td class=\"shade2\">";
			print_help_link("add_media_help", "qm", "add_media_lbl");
			print $gm_lang["add_media_lbl"] . "</td>";
			print "<td class=\"shade1\">";
			print "<a href=\"javascript: ".$gm_lang["add_media_lbl"]."\" onclick=\"add_new_record('".$famid."', 'OBJE', 'add_media'); return false;\">".$gm_lang["add_media"]."</a>";
			print "<br />\n";
			print "</td></tr>\n";
			// -- new note
			print "<tr><td class=\"shade2\">";
			print_help_link("add_note_help", "qm" ,"add_note_lbl");
			print $gm_lang["add_note_lbl"] . "</td>";
			print "<td class=\"shade1\">";
			print "<a href=\"#\" onclick=\"return add_new_record('$famid','NOTE', 'add_note');\">" . $gm_lang["add_note"] . "</a>";
			print "<br />\n";
			print "</td></tr>\n";
			// -- new general note
			print "<tr><td class=\"shade2\">";
			print_help_link("add_general_note_help", "qm" ,"add_gnote_lbl");
			print $gm_lang["add_gnote_lbl"] . "</td>";
			print "<td class=\"shade1\">";
			print "<a href=\"#\" onclick=\"return add_new_record('$famid','GNOTE', 'add_gnote');\">" . $gm_lang["add_gnote"] . "</a>";
			print "<br />\n";
			print "</td></tr>\n";
			// -- end new objects
		}
		print "\n\t</table>\n";
	}
}

/**
 * print a family with Sosa-Stradonitz numbering system
 * ($rootid=1, father=2, mother=3 ...)
 *
 * @param string $famid family gedcom ID
 * @param string $childid tree root ID
 * @param string $sosa starting sosa number
 * @param string $label optional indi label (descendancy booklet)
 * @param string $parid optional parent ID (descendancy booklet)
 * @param string $gparid optional gd-parent ID (descendancy booklet)
 */
function PrintSosaFamily($famid, $childid, $sosa, $label="", $parid="", $gparid="", $personcount="1") {
	global $gm_lang, $pbwidth, $pbheight, $view;

	if ($view != "preview") print "<hr />";
	print "\r\n\r\n<p style='page-break-before:always' />\r\n";
	print "<a name=\"$famid\"></a>\r\n";
	PrintFamilyParents($famid, $sosa, $label, $parid, $gparid, $personcount);
	$personcount++;
	print "\n\t<br />\n";
	print "<table width=\"95%\"><tr><td valign=\"top\" style=\"width: " . ($pbwidth) . "px;\">\n";
	PrintFamilyChildren($famid, $childid, $sosa, $label, $personcount);
	print "</td><td valign=\"top\">";
	if ($sosa == 0) PrintFamilyFacts($famid, $sosa);
	print "</td></tr></table>\n";
	print "<br />";
}
/**
 * check root id for pedigree tree
 *
 * @param string $rootid root ID
 * @return string $rootid validated root ID
 */
function CheckRootId($rootid) {
	global $user, $GEDCOM, $GEDCOM_ID_PREFIX, $PEDIGREE_ROOT_ID, $USE_RIN, $gm_username, $Users;
	
	// -- if the $rootid is not already there then find the first person in the file and make him the root
	if (empty($rootid)) {
		$user = $Users->getUser($gm_username);
		if ((!empty($user->rootid[$GEDCOM])) && (FindPersonRecord($user->rootid[$GEDCOM]))) $rootid = $user->rootid[$GEDCOM];
		else if ((!empty($user->gedcomid[$GEDCOM])) && (FindPersonRecord($user->gedcomid[$GEDCOM]))) $rootid = $user->gedcomid[$GEDCOM];
		
		// -- allow users to overide default id in the config file.
		if (empty($rootid)) {
			$PEDIGREE_ROOT_ID = trim($PEDIGREE_ROOT_ID);
			if ((!empty($PEDIGREE_ROOT_ID)) && (FindPersonRecord($PEDIGREE_ROOT_ID))) $rootid = $PEDIGREE_ROOT_ID;
			else $rootid = FindFirstPerson();
		}
	}
	
	if ($USE_RIN) {
		$indirec = FindPersonRecord($rootid);
		if ($indirec == false) $rootid = FindRinId($rootid);
	} else {
		if (preg_match("/[A-Za-z]+/", $rootid) == 0) {
			$GEDCOM_ID_PREFIX = trim($GEDCOM_ID_PREFIX);
			$rootid = $GEDCOM_ID_PREFIX . $rootid;
		}
	}

	return strtoupper($rootid);
}

/**
 * creates an array with all of the individual ids to be displayed on an ascendancy chart
 *
 * the id in position 1 is the root person.  The other positions are filled according to the following algorithm
 * if an individual is at position $i then individual $i's father will occupy position ($i*2) and $i's mother
 * will occupy ($i*2)+1
 *
 * @param string $rootid
 * @return array $treeid
 */
function AncestryArray($rootid) {
	global $PEDIGREE_GENERATIONS, $SHOW_EMPTY_BOXES;
	// -- maximum size of the id array
	$treesize = pow(2, ($PEDIGREE_GENERATIONS+1));

	$treeid = array();
	$treeid[0] = "";
	$treeid[1] = $rootid;
	// -- fill in the id array
	for($i = 1; $i < ($treesize / 2); $i++) {
		$treeid[($i * 2)] = false; // -- father
		$treeid[($i * 2) + 1] = false; // -- mother
		if (!empty($treeid[$i])) {
			print " ";
			$famids = FindFamilyIds($treeid[$i]);
			if (count($famids) > 0) {
				$parents = false;
				$j = 0;
				while ((!$parents) && ($j < count($famids))) {
					$parents = FindParents($famids[$j]["famid"]);
					$j++;
				}

				if ($parents) {
					$treeid[($i * 2)] = $parents["HUSB"]; // -- set father id
					$treeid[($i * 2) + 1] = $parents["WIFE"]; // -- set mother id
				}
			}
		}
	}
//	print_r($treeid);
	return $treeid;
}

/**
 * creates an array with all of the individual ids to be displayed on the pedigree chart
 *
 * the id in position 0 is the root person.  The other positions are filled according to the following algorithm
 * if an individual is at position $i then individual $i's father will occupy position ($i*2)+1 and $i's mother
 * will occupy ($i*2)+2
 *
 * @deprecated	This function has been deprecated by the ancestry_array function, it is still
 *				provided for backwards compatibility but it should no longer be used in new code
 * @param string $rootid
 * @return array $treeid
 */
function PedigreeArray($rootid) {
	global $PEDIGREE_GENERATIONS, $SHOW_EMPTY_BOXES;
	// -- maximum size of the id array is 2^$PEDIGREE_GENERATIONS - 1
	$treesize = pow(2, (int)($PEDIGREE_GENERATIONS))-1;

	$treeid = array();
	$treeid[0] = $rootid;
	// -- fill in the id array
	for($i = 0; $i < ($treesize / 2); $i++) {
		if (!empty($treeid[$i])) {
			print " ";
			$person = new Person(FindPersonRecord($treeid[$i]));
			$famids = $person->getChildFamilies();
			// $famids = FindFamilyIds($treeid[$i]);
			if (count($famids) > 0) {
				$parents = false;
//				$j = 0;
				$wife = null;
				$husb = null;
				// First see if there is a primary family
				foreach($famids as $famid=>$family) {
//					print "fsp:".$family->ShowPrimary."<br />";
					if (!is_null($family) && $family->ShowPrimary) {
						$wife = $family->getWife();
						$husb = $family->getHusband();
						if (!is_null($wife) || !is_null($husb)) {
							$parents = true;
							break;
						}
					}
				}
				// If no primary found, take the first fam with at least one parent
				if (!$parents) {
					foreach($famids as $famid=>$family) {
						if (!is_null($family)) {
							$wife = $family->getWife();
							$husb = $family->getHusband();
							if (!is_null($wife) || !is_null($husb)) {
								$parents = true;
								break;
							}
						}
						//$parents = FindParents($famids[$j]);
//						$j++;
					}
				}

				if ($parents) {
					if (!is_null($husb)) $treeid[($i * 2) + 1] = $husb->getXref(); // -- set father id
					else $treeid[($i * 2) + 1] = false;
					if (!is_null($wife)) $treeid[($i * 2) + 2] = $wife->getXref(); // -- set mother id
					else $treeid[($i * 2) + 2] = false;
				}
			} else {
				$treeid[($i * 2) + 1] = false; // -- father not found
				$treeid[($i * 2) + 2] = false; // -- mother not found
			}
		} else {
			$treeid[($i * 2) + 1] = false; // -- father not found
			$treeid[($i * 2) + 2] = false; // -- mother not found
		}
	}
	// -- detect the highest generation that actually has a person in it and use it for the pedigree generations
	if (!$SHOW_EMPTY_BOXES) {
		for($i = ($treesize-1); empty($treeid[$i]); $i--);
		$PEDIGREE_GENERATIONS = ceil(log($i + 2) / log(2));
		if ($PEDIGREE_GENERATIONS < 2) $PEDIGREE_GENERATIONS = 2;
		// print "$i:$PEDIGREE_GENERATIONS";
	}

	return $treeid;
}

/**
 * find all children from a family
 *
 * @todo get the kids out of the database
 * @param string $famid family ID
 * @return array array of child ID
 */
function GetChildrenIds($famid) {
	$children = array();
	$famrec = FindFamilyRecord($famid);
	$ct = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $match, PREG_SET_ORDER);
	for($i = 0; $i < $ct; $i++) {
		$children[] = $match[$i][1];
	}
	return $children;
}

/**
 * print an arrow to a new url
 *
 * @param string $id Id used for arrow img name (must be unique on the page)
 * @param string $url target url
 * @param string $label arrow label
 * @param string $dir arrow direction 0=left 1=right 2=up 3=down (default=2)
 */
function PrintUrlArrow($id, $url, $label, $dir=2) {
	global $gm_lang, $view;
	global $GM_IMAGE_DIR, $GM_IMAGES;
	global $TEXT_DIRECTION;

	if ($id=="" or $url=="") return;
	if ($view=="preview") return;

	// arrow direction
	$adir=$dir;
	if ($TEXT_DIRECTION=="rtl" and $dir==0) $adir=1;
	if ($TEXT_DIRECTION=="rtl" and $dir==1) $adir=0;

	// arrow style		0		  1 		2		  3
	$array_style=array("larrow", "rarrow", "uarrow", "darrow");
	$astyle=$array_style[$adir];

	print "<a href=\"$url\" onmouseover=\"swap_image('".$astyle.$id."',$adir); window.status ='" . $label . "'; return true; \" onmouseout=\"swap_image('".$astyle.$id."',$adir); window.status=''; return true; \"><img id=\"".$astyle.$id."\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES[$astyle]["other"]."\" hspace=\"0\" vspace=\"0\" border=\"0\" alt=\"".$label."\" title=\"".$label."\" /></a>";
}

/**
 * builds and returns sosa relationship name in the active language
 *
 * @param string $sosa sosa number
 */
function GetSosaName($sosa) {
	global $LANGUAGE, $gm_lang;

	if ($sosa<2) return "";
	$sosaname = "";
	$sosanr = floor($sosa/2);
	$gen = floor( log($sosanr) / log(2) );

	if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian" || $LANGUAGE == "swedish") {
		$addname = "";
		$father = strtolower($gm_lang["father"]);
		$mother = strtolower($gm_lang["mother"]);
		$grand = "be".($LANGUAGE == "danish"?"dste":"ste");
		$great = "olde";
		$tip = "tip".($LANGUAGE == "danish"?"-":"p-");
		for($i = $gen; $i > 2; $i--) {
			$sosaname .= $tip;
		}
		if ($gen >= 2) $sosaname .= $great;
		if ($gen == 1) $sosaname .= $grand;

		for ($i=$gen; $i>0; $i--){
			if (!(floor($sosa/(pow(2,$i)))%2)) $addname .= $father;
			else $addname .= $mother;
			if (($gen%2 && !($i%2)) || (!($gen%2) && $i%2)) $addname .= "s ";
		}
		if ($LANGUAGE == "swedish") $sosaname = $addname;
		if (!($sosa%2)){
			$sosaname .= $father;
			if ($gen>0) $addname .= $father;
		}
		else {
			$sosaname .= $mother;
			if ($gen>0) $addname .= $mother;
		}
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		if ($LANGUAGE != "swedish") if (!empty($addname)) $sosaname .= ($gen>5?"<br />&nbsp;&nbsp;&nbsp;&nbsp;":"")." <small>(".$addname.")</small>";
	}
	if ($LANGUAGE == "dutch") {
		if ($gen & 256) $sosaname .= $gm_lang["sosa_11"];
		if ($gen & 128) $sosaname .= $gm_lang["sosa_10"];
		if ($gen & 64) $sosaname .= $gm_lang["sosa_9"];
		if ($gen & 32) $sosaname .= $gm_lang["sosa_8"];
		if ($gen & 16) $sosaname .= $gm_lang["sosa_7"];
		if ($gen & 8) $sosaname .= $gm_lang["sosa_6"];
		if ($gen & 4) $sosaname .= $gm_lang["sosa_5"];
		$gen = $gen - floor($gen / 4)*4;
		if ($gen == 3) $sosaname .= $gm_lang["sosa_4"].$gm_lang["sosa_3"].$gm_lang["sosa_2"];
		if ($gen == 2) $sosaname .= $gm_lang["sosa_3"].$gm_lang["sosa_2"];
		if ($gen == 1) $sosaname .= $gm_lang["sosa_2"];
		if ($sosa%2) $sosaname .= strtolower($gm_lang["mother"]);
		else $sosaname .= strtolower($gm_lang["father"]);
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		return $sosaname;
	}
	if ($LANGUAGE == "english") {
		for($i = $gen; $i > 1; $i--) {
			$sosaname .= "Great-";
		}
		if ($gen >= 1) $sosaname .= "Grand";
		if (!($sosa%2)) $sosaname .= strtolower($gm_lang["father"]);
		else $sosaname .= strtolower($gm_lang["mother"]);
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
	}
	if ($LANGUAGE == "finnish") {
		$father = Str2Lower($gm_lang["father"]);
		$mother = Str2Lower($gm_lang["mother"]);
//		$father = "isä";
//		$mother = "äiti";
//		$gm_lang["sosa_2"]= "äidin";	//Grand (mother)
		for ($i=$gen; $i>0; $i--){
			if (!(floor($sosa/(pow(2,$i)))%2)) $sosaname .= $father."n";
//			else $sosaname .= $gm_lang["sosa_2"];
			else $sosaname .= substr($mother, 0,3)."din";
		}
		if (!($sosa%2)) $sosaname .= $father;
		else $sosaname .= $mother;
		if (substr($sosaname, 0,1)=="i") $sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		else $sosaname = Str2Upper(substr($mother, 0,2)).substr($sosaname,2);
	}
	if ($LANGUAGE == "french") {
		if ($gen>4) $sosaname = "Arrière(x". ($gen-1) . ")-";
		else for($i = $gen; $i > 1; $i--) {
			$sosaname .= "Arrière-";
		}
		if ($gen >= 1) $sosaname .= "Grand-";
		if (!($sosa%2)) $sosaname .= $gm_lang["father"];
		else $sosaname .= $gm_lang["mother"];
		if ($gen == 1){
			if ($sosa<6) $sosaname .= " Pater";
			else $sosaname .= " Mater";
			$sosaname .= "nel";
			if ($sosa%2) $sosaname .= "le";
		}
	}
	if ($LANGUAGE == "german") {
		for($i = $gen; $i > 1; $i--) {
			$sosaname .= "Ur-";
		}
		if ($gen >= 1) $sosaname .= "Groß";
		if (!($sosa%2)) $sosaname .= strtolower($gm_lang["father"]);
		else $sosaname .= strtolower($gm_lang["mother"]);
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
	}
	if ($LANGUAGE == "hebrew") {
		$addname = "";
		$father = $gm_lang["father"];
		$mother = $gm_lang["mother"];
		$greatf = $gm_lang["sosa_22"];
		$greatm = $gm_lang["sosa_21"];
		$of = $gm_lang["sosa_23"];
		$grandfather = $gm_lang["sosa_4"];
		$grandmother = $gm_lang["sosa_5"];
//		$father = "Aba";
//		$mother = "Ima";
//		$grandfather = "Saba";
//		$grandmother = "Savta";
//		$greatf = " raba";
//		$greatm = " rabta";
//		$of = " shel ";
		for ($i=$gen; $i>=0; $i--){
			if ($i==0){
				if (!($sosa%2)) $addname .= "f";
				else $addname .= "m";
			}
			else if (!(floor($sosa/(pow(2,$i)))%2)) $addname .= "f";
			else $addname .= "m";
			if ($i==0 || strlen($addname)==3){
				if (strlen($addname)==3){
					if (substr($addname, 2,1)=="f") $addname = $grandfather.$greatf;
					else $addname = $grandmother.$greatm;
				}
				else if (strlen($addname)==2){
					if (substr($addname, 1,1)=="f") $addname = $grandfather;
					else $addname = $grandmother;
				}
				else {
					if ($addname=="f") $addname = $father;
					else $addname = $mother;
				}
				$sosaname = $addname.($i<$gen-2?$of:"").$sosaname;
				$addname="";
			}
		}
	}
	if (!empty($sosaname)) return "$sosaname<!-- sosa=$sosa nr=$sosanr gen=$gen -->";

	if (isset($gm_lang["sosa_$sosa"])) return $gm_lang["sosa_$sosa"];
	else return (($sosa%2) ? $gm_lang["mother"] : $gm_lang["father"]) . " " . floor($sosa/2);
}

/**
 * print cousins list
 *
 * @param string $famid family ID
 */
function PrintCousins($famid, $personcount="1") {
	global $show_full, $bheight, $bwidth;
	global $GM_IMAGE_DIR, $GM_IMAGES;

	$fchildren = GetChildrenIds($famid);
	$kids = count($fchildren);
	$save_show_full = $show_full;
	if ($save_show_full) {
		$bheight/=4;
		$bwidth-=40;
	}
	$show_full = false;
	print "<td style=\"vertical-align:middle\" height=\"100%\">";
	if ($kids) {
		print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" ><tr valign=\"middle\">";
		if ($kids>1) print "<td rowspan=\"".$kids."\" style=\"vertical-align:middle;\" align=\"right\"><img width=\"3px\" height=\"". (($bheight+5) * ($kids-1)) ."px\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" alt=\"\" /></td>";
		$ctkids = count($fchildren);
		$i = 1;
		foreach ($fchildren as $indexval => $fchil) {
			print "<td style=\"vertical-align:middle;\"><img width=\"7px\" height=\"3px\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td style=\"vertical-align:middle;\">";
			print_pedigree_person($fchil, 1 , false, 0, $personcount);
			$personcount++;
			print "</td></tr>";
			if ($i < $ctkids) {
				print "<tr>";
				$i++;
			}
		}
		print "</table>";
	}
	$show_full = $save_show_full;
	if ($save_show_full) {
		$bheight*=4;
		$bwidth+=40;
	}
	print "</td>\n";
}
?>
