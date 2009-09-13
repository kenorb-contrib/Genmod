<?php
/**
 * Display an hourglass chart
 *
 * Set the root person using the $pid variable
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
 * This Page Is Valid XHTML 1.0 Transitional! > 23 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the chart functions
*/
require("includes/functions/functions_charts.php");

function print_descendency($pid, $count) {
	global $show_spouse, $dgenerations, $bwidth, $bheight, $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $generations, $box_width, $view, $show_full, $gm_lang, $boxcount;
	if ($count>=$dgenerations) return 0;
	print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"vertical-align:middle;\">\n";
	print "<tr>";
	print "<td width=\"$bwidth\" style=\"vertical-align:middle;\">\n";
	$numkids = 0;
	$famids = FindSfamilyIds($pid);
	if (count($famids)>0) {
		$firstkids = 0;
		foreach($famids as $indexval => $famid) {
			print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"vertical-align:middle;\">\n";
			$famrec = FindFamilyRecord($famid["famid"]);
			$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				$rowspan = 2;
				if (($i>0)&&($i<$ct-1)) $rowspan=1;
				$chil = trim($match[$i][1]);
				print "<tr><td rowspan=\"$rowspan\" width=\"$bwidth\" style=\"vertical-align:middle; padding-top: 2px;\">\n";
				if ($count+1 < $dgenerations) {
					$kids = print_descendency($chil, $count+1);
					if ($i==0) $firstkids = $kids;
					$numkids += $kids;
				}
				else {
					print_pedigree_person($chil, 1, true, $boxcount);
					$boxcount++;
					$numkids++;
				}
				print "</td>\n";
				$twidth = 7;
				if ($ct==1) $twidth+=3;
				print "<td rowspan=\"$rowspan\" style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"$twidth\" height=\"3\" alt=\"\" /></td>\n";
				if ($ct>1) {
					if ($i==0) {
						print "<td style=\"vertical-align:middle;\" height=\"50%\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
						print "<tr><td height=\"50%\" style=\"vertical-align:middle; background: url('".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
					}
					else if ($i==$ct-1) {
						print "<td height=\"50%\" style=\"vertical-align:middle; background: url('".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
						print "<tr><td height=\"50%\" style=\"vertical-align:middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
					}
					else {
						print "<td style=\"vertical-align:middle; background: url('".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
					}
				}
				print "</tr>\n";
			}
			print "</table>\n";
		}
	}
	print "</td>\n";
	print "<td style=\"vertical-align:middle;\" width=\"$bwidth\">\n";
	// NOTE: If statement OK
	if ($numkids==0) {
		$numkids = 1;
		$tbwidth = $bwidth+16;
		for($j=$count; $j<$dgenerations; $j++) {
			print "<div style=\"width: ".($tbwidth)."px;\"><br /></div>\n</td>\n<td style=\"vertical-align:middle;\" width=\"$bwidth\">\n";
		}
	}
	//-- add offset divs to make things line up better
	if ($show_spouse) {
		foreach($famids as $indexval => $famid) {
			$famrec = FindFamilyRecord($famid["famid"]);
			if (!empty($famrec) && PrivacyFunctions::DisplayDetailsById($famid["famid"], "FAM")) {
				$marrec = GetSubRecord(1, "1 MARR", $famrec);
				if (!empty($marrec)) {
					print "<br />";
				}
				print "<div style=\"vertical-align:middle; height: ".$bheight."px; width: ".$bwidth."px;\"><br /></div>\n";
			}
		}
	}
	print_pedigree_person($pid, 1, true, $boxcount);
	$boxcount++;
	// NOTE: If statement OK
	if ($show_spouse) {
		foreach($famids as $indexval => $famid) {
			$famrec = FindFamilyRecord($famid["famid"]);
			if (!empty($famrec) && PrivacyFunctions::DisplayDetailsById($famid["famid"], "FAM")) {
				$parents = FindParentsInRecord($famrec);
				$marrec = GetSubRecord(1, "1 MARR", $famrec);
				if (!empty($marrec)) {
					print "<br />";
					print_simple_fact($famrec, "MARR", $famid["famid"]);
				}
				if ($parents["HUSB"]!=$pid) print_pedigree_person($parents["HUSB"], 1, true, $boxcount);
				else print_pedigree_person($parents["WIFE"], 1, true, $boxcount);
				$boxcount++;
			}
		}
	}
	// NOTE: If statement OK
	if ($count==0) {
		// NOTE: If statement OK
		if (PrivacyFunctions::showLivingNameByID($pid)) {
			// -- print left arrow for decendants so that we can move down the tree
			$famids = FindSfamilyIds($pid);
			//-- make sure there is more than 1 child in the family with parents
			$cfamids = FindFamilyIds($pid);
			$num=0;
			// NOTE: For statement OK
			for($f=0; $f<count($cfamids); $f++) {
				$famrec = FindFamilyRecord($cfamids[$f]["famid"]);
				if ($famrec) {
					$num += preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
				}
			}
			// NOTE: If statement OK
			if ($famids||($num>1)) {
				print "\n\t\t<div id=\"childarrow$boxcount\" dir=\"";
				if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; ";
				else print "ltr\" style=\"position:absolute; ";
				print "width:10px; height:10px; \">";
				if ($view!="preview") {
					print "<a href=\"javascript: ".$gm_lang["show"]."\" onclick=\"togglechildrenbox($boxcount); return false;\" onmouseover=\"swap_image('larrow$boxcount',3);\" onmouseout=\"swap_image('larrow$boxcount',3);\">";
					print "<img id=\"larrow$boxcount\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"]."\" border=\"0\" alt=\"\" />";
					print "</a>";
				}
				print "\n\t\t<div id=\"childbox$boxcount\" dir=\"";
				if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right: 20px; ";
				else print "ltr\" style=\"position:absolute; left: 20px;";
				print " width:".$bwidth."px; height:".$bheight."px; visibility: hidden;\">";
				print "\n\t\t\t<table class=\"person_box\"><tr><td style=\"vertical-align:middle;\">";
				for($f=0; $f<count($famids); $f++) {
					$famrec = FindFamilyRecord(trim($famids[$f]["famid"]));
					if ($famrec) {
						$parents = FindParents($famids[$f]["famid"]);
						if($parents) {
							if($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
							else $spid=$parents["WIFE"];
							if (!empty($spid)) {
								print "\n\t\t\t\t<a href=\"hourglass.php?pid=$spid&amp;show_spouse=$show_spouse&amp;show_full=$show_full&amp;generations=$generations&amp;box_width=$box_width\"><span ";
								if (PrivacyFunctions::showLivingNameByID($spid)) {
									$name = GetPersonName($spid);
									$name = rtrim($name);
									if (hasRTLText($name))
									     print "class=\"name2\">";
					   				else print "class=\"name1\">";
									print PrintReady($name);
								}
								else print $gm_lang["private"];
								print "<br /></span></a>";
							}
						}
						$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
						for($i=0; $i<$num; $i++) {
							//-- add the following line to stop a bad PHP bug
							if ($i>=$num) break;
							$cid = $smatch[$i][1];
							print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"hourglass.php?pid=$cid&amp;show_spouse=$show_spouse&amp;show_full=$show_full&amp;generations=$generations&amp;box_width=$box_width\"><span ";
							if (PrivacyFunctions::showLivingNameByID($cid)) {
								$name = GetPersonName($cid);
								$name = rtrim($name);
								if (hasRTLText($name))
								     print "class=\"name2\">&lt; ";
					   			else print "class=\"name1\">&lt; ";
								print PrintReady($name);
							}
							else print ">" . $gm_lang["private"];
							print "<br /></span></a>";
						}
					}
				}
				//-- print the siblings
				for($f=0; $f<count($cfamids); $f++) {
					$famrec = FindFamilyRecord($cfamids[$f]["famid"]);
					if ($famrec) {
						$parents = FindParents($cfamids[$f]["famid"]);
						if($parents) {
							print "<span class=\"name1\"><br />".$gm_lang["parents"]."<br /></span>";
							if (!empty($parents["HUSB"])) {
								$spid = $parents["HUSB"];
								print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"hourglass.php?pid=$spid&amp;show_spouse=$show_spouse&amp;show_full=$show_full&amp;generations=$generations&amp;box_width=$box_width\"><span ";
								if (PrivacyFunctions::showLivingNameByID($spid)) {
									$name = GetPersonName($spid);
									$name = rtrim($name);
									if (hasRTLText($name))
									     print "class=\"name2\">";
					   				else print "class=\"name1\">";
									print PrintReady($name);
								}
								else print $gm_lang["private"];
								print "<br /></span></a>";
							}
							if (!empty($parents["WIFE"])) {
								$spid = $parents["WIFE"];
								print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"hourglass.php?pid=$spid&amp;show_spouse=$show_spouse&amp;show_full=$show_full&amp;generations=$generations&amp;box_width=$box_width\"><span ";
								if (PrivacyFunctions::showLivingNameByID($spid)) {
									$name = GetPersonName($spid);
									$name = rtrim($name);
									if (hasRTLText($name))
									     print "class=\"name2\">";
					   				else print "class=\"name1\">";
									print PrintReady($name);
								}
								else print $gm_lang["private"];
								print "<br /></span></a>";
							}
						}
						$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
						if ($num>1) print "<span class=\"name1\"><br />".$gm_lang["siblings"]."<br /></span>";
						for($i=0; $i<$num; $i++) {
							//-- add the following line to stop a bad PHP bug
							if ($i>=$num) break;
							$cid = $smatch[$i][1];
							if ($cid!=$pid) {
								print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"hourglass.php?pid=$cid&amp;show_spouse=$show_spouse&amp;show_full=$show_full&amp;generations=$generations&amp;box_width=$box_width\"><span ";
								if (PrivacyFunctions::showLivingNameByID($cid)) {
									$name = GetPersonName($cid);
									$name = rtrim($name);
									if (hasRTLText($name))
									print "class=\"name2\"> ";
					   				else print "class=\"name1\"> ";
									print PrintReady($name);
								}
								else print ">". $gm_lang["private"];
								print "<br /></span></a>";
							}
						}
					}
				}
				print "\n\t\t\t</td></tr></table>";
				print "\n\t\t</div>";
				print "\n\t\t</div>";
			}
		}
	}
	print "</td></tr>\n";
	print "</table>\n";
	return $numkids;
}

function max_descendency_generations($pid, $depth) {
	global $generations;
	if ($depth >= $generations) return $depth;
	$famids = FindSfamilyIds($pid);
	$maxdc = $depth;
	foreach($famids as $indexval => $famid) {
		$famrec = FindFamilyRecord($famid["famid"]);
		$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$chil = trim($match[$i][1]);
			$dc = max_descendency_generations($chil, $depth+1);
			if ($dc >= $generations) return $dc;
			if ($dc > $maxdc) $maxdc = $dc;
		}
	}
	if ($maxdc==0) $maxdc++;
	return $maxdc;
}

function print_person_pedigree($pid, $count) {
	global $generations, $SHOW_EMPTY_BOXES, $GM_IMAGE_DIR, $GM_IMAGES, $bheight, $boxcount;
	if ($count>=$generations) return;
	$famids = FindFamilyIds($pid);
	foreach($famids as $indexval => $ffamid) {
		$famid = $ffamid["famid"];
		print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"empty-cells: show; vertical-align:middle;\">\n";
		$parents = FindParents($famid);
		$height="100%";
		print "<tr>";
		if ($count<$generations-1) print "<td style=\"vertical-align:middle;\" height=\"50%\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
		if ($count<$generations-1) print "<td style=\"vertical-align:middle;\" rowspan=\"2\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"7\" height=\"3\" alt=\"\" /></td>\n";
		print "<td style=\"vertical-align:middle;\" rowspan=\"2\">\n";
		print_pedigree_person($parents["HUSB"], 1, true, $boxcount);
		$boxcount++;
		print "</td>\n";
		print "<td style=\"vertical-align:middle;\" rowspan=\"2\">\n";
		print_person_pedigree($parents["HUSB"], $count+1);
		print "</td>\n";
		print "</tr>\n<tr>\n<td style=\"vertical-align:middle;";
		if ($count<$generations-1) print " background: url('".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');";
		print "\" height=\"50%\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n<tr>\n";
		if ($count<$generations-1) print "<td height=\"50%\" style=\"vertical-align:middle; background: url('".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>";
		if ($count<$generations-1) print "<td style=\"vertical-align:middle;\" rowspan=\"2\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"7\" height=\"3\" alt=\"\" /></td>\n";
		print "<td style=\"vertical-align:middle;\" rowspan=\"2\">\n";
		print_pedigree_person($parents["WIFE"], 1, true, $boxcount);
		$boxcount++;
		print "</td>\n";
		print "<td style=\"vertical-align:middle;\" rowspan=\"2\">\n";
		print_person_pedigree($parents["WIFE"], $count+1);
		print "</td>\n";
		print "</tr>\n";
		if ($count<$generations-1) print "<tr>\n<td style=\"vertical-align:middle;\" height=\"50%\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
		print "</table>\n";
	}
}

// -- args
$boxcount = 0;
if (!isset($show_full)) $show_full=$PEDIGREE_FULL_DETAILS;
if (!isset($show_spouse)) $show_spouse=0;
if (empty($generations)) $generations = 3;
if ($generations > $MAX_DESCENDANCY_GENERATIONS) $generations = $MAX_DESCENDANCY_GENERATIONS;
if (!isset($view)) $view="";

// -- size of the boxes
if (empty($box_width)) $box_width = "100";
$box_width=max($box_width, 50);
$box_width=min($box_width, 300);
if (!$show_full) $bwidth = $bwidth / 1.5;
$bwidth*=$box_width/100;
if ($show_full==false) {
	$bheight = $bheight / 2.5;
}

// -- root id
if (!isset($pid)) $pid="";
$pid=CheckRootId($pid);
if (PrivacyFunctions::showLivingNameByID($pid)) {
	$name = GetPersonName($pid);
	$addname = GetAddPersonName($pid);
}
else {
	$name = $gm_lang["private"];
	$addname = "";
}

// -- print html header information
$title = PrintReady($name);
if ($SHOW_ID_NUMBERS) $title .= " - ".$pid;
$title .= " - ".$gm_lang["hourglass_chart"];
print_header($title);
// NOTE: Start table header
print "\n\t<table width=\"100%\" class=\"list_table $TEXT_DIRECTION\"><tr><td valign=\"top\">\n\t\t";
if ($view!="preview") print "\n\t<h3>".$gm_lang["hourglass_chart"].":<br />".PrintReady($name);
else print "\n\t<h3 style=\"text-align: center\">".$gm_lang["hourglass_chart"].":<br />".PrintReady($name);
if ($addname != "") print "<br />" . PrintReady($addname);
print "</h3>";
?>

<script language="JavaScript" type="text/javascript">
<!--
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
	}
//-->
</script>

<?php
$gencount=0;
if ($view!="preview") {
	// NOTE: Start form and table
	print "</td><td><form method=\"get\" name=\"people\" action=\"?\">\n";
	print "<input type=\"hidden\" name=\"show_full\" value=\"$show_full\" />";
	print "\n\t\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr>";
	
	// NOTE: Root ID
	print "<td class=\"shade2\">";
	print_help_link("desc_rootid_help", "qm");	
	print $gm_lang["root_person"] . "</td>";
	print "<td class=\"shade1\">";
	print "\n\t\t<input class=\"pedigree_form\" type=\"text\" name=\"pid\" id=\"pid\" size=\"3\" value=\"$pid\" />";
	LinkFunctions::PrintFindIndiLink("pid","");
	print "</td>";
	
	// NOTE: Show Details
	print "<td class=\"shade2\">";
	print_help_link("show_full_help", "qm");
	print $gm_lang["show_details"]."</td>";
	print "<td class=\"shade1\">";
	print "<input type=\"checkbox\" value=\"";
	if ($show_full) print "1\" checked=\"checked\" onclick=\"document.people.show_full.value='0';\"";
	else print "0\" onclick=\"document.people.show_full.value='1';\"";
	print " /></td>";
	
	// NOTE: Submit button
	print "<td rowspan=\"3\" class=\"center vmiddle\">";
	print "<input type=\"submit\"  value=\"".$gm_lang["view"]."\" />";
	print "</td></tr>\n";
	
	// NOTE: Generations
	print "<tr><td class=\"shade2\" >";
	print_help_link("desc_generations_help", "qm");
	print $gm_lang["generations"]."</td>";
	print "<td class=\"shade1\">";
//	print <input type=\"text\" size=\"3\" name=\"generations\" value=\"$generations\" />";
	print "<select name=\"generations\">";
	for ($i=2; $i<=$MAX_DESCENDANCY_GENERATIONS; $i++) {
	print "<option value=\"".$i."\"";
	if ($i == $generations) print " selected=\"selected\" ";
		print ">".$i."</option>";
	}
	print "</select>";
	print "</td>";
	
	// NOTE: Show spouses
	print "<td class=\"shade2\">";
	print_help_link("show_spouse_help", "qm");
	print $gm_lang["show_spouses"]."</td>";
	print "<td class=\"shade1\">";
	print "<input type=\"checkbox\" value=\"1\" name=\"show_spouse\"";
	if ($show_spouse) print " checked=\"checked\"";
	print " /></td></tr>";
	
	// NOTE: Box width
	print "<tr><td class=\"shade2\">";
	print_help_link("box_width_help", "qm");
	print $gm_lang["box_width"]."</td>";
	print "<td class=\"shade1\"><input type=\"text\" size=\"3\" name=\"box_width\" value=\"$box_width\" /> <b>%</b>";
	print "</td><td class=\"shade2\">&nbsp;</td><td class=\"shade1\">&nbsp;</td></tr>";
	
	// NOTE: End table and form
	print "</table></form>\n";
}
// NOTE: Close table header
print "</td></tr></table>";

print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr>\n";
//-- descendancy
print "<td style=\"vertical-align:middle;\" valign=\"middle\">\n";
$dgenerations = $generations;
$dgenerations = max_descendency_generations($pid, 0);
print_descendency($pid, 0);
print "</td>\n";
//-- pedigree
print "<td style=\"vertical-align:middle;\" valign=\"middle\">\n";
print_person_pedigree($pid, 0);
print "</td>\n";
print "</tr></table>\n";
print "<br /><br />\n";
print_footer();
?>
