<?php
/**
 * Parses gedcom file and displays a pedigree tree.
 *
 * Specify a $rootid to root the pedigree tree at a certain person
 * with id = $rootid in the GEDCOM file.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 22 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $ Id: $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the chart functions
*/
require("includes/functions/functions_charts.php");

$log2 = log(2);
$show_changes = false;
function adjust_subtree($index, $diff) {
	global $offsetarray, $treeid, $PEDIGREE_GENERATIONS, $log2, $talloffset,$boxspacing, $mdiff, $SHOW_EMPTY_BOXES;
	$f = ($index*2)+1; //-- father index
	$m = $f+1; //-- mother index

	if (!$SHOW_EMPTY_BOXES && empty($treeid[$index])) return;
	if (empty($offsetarray[$index])) return;
	$offsetarray[$index]["y"] += $diff;
	if ($f<count($treeid)) adjust_subtree($f, $diff);
	if ($m<count($treeid)) adjust_subtree($m, $diff);
}

function collapse_tree($index, $curgen, $diff) {
	global $offsetarray, $treeid, $PEDIGREE_GENERATIONS, $log2, $talloffset,$boxspacing, $mdiff, $minyoffset, $SHOW_ID_NUMBERS;

	//print "$index:$curgen:$diff<br />\n";
	$f = ($index*2)+1; //-- father index
	$m = $f+1; //-- mother index
	if (empty($treeid[$index])) {
		$pgen=$curgen;
		$genoffset=0;
		while($pgen<=$PEDIGREE_GENERATIONS) {
			$genoffset += pow(2, ($PEDIGREE_GENERATIONS-$pgen));
			$pgen++;
		}
		if ($talloffset==1) $diff+=.5*$genoffset;
		else $diff+=$genoffset;
		if (isset($offsetarray[$index]["y"])) $offsetarray[$index]["y"]-=($boxspacing*$diff)/2;
		return $diff;
	}
	if ($curgen==$PEDIGREE_GENERATIONS) {
		$offsetarray[$index]["y"] -= $boxspacing*$diff;
		//print "UP $index BY $diff<br />\n";
		return $diff;
	}
	$odiff=$diff;
	$fdiff = collapse_tree($f, $curgen+1, $diff);
	if (($curgen<($PEDIGREE_GENERATIONS-1))||($index%2==1)) $diff=$fdiff;
	if (isset($offsetarray[$index]["y"])) $offsetarray[$index]["y"] -= $boxspacing*$diff;
	//print "UP $index BY $diff<br />\n";
	$mdiff = collapse_tree($m, $curgen+1, $diff);
	$zdiff = $mdiff - $fdiff;
	if (($zdiff>0)&&($curgen<$PEDIGREE_GENERATIONS-2)) {
		$offsetarray[$index]["y"] -= $boxspacing*$zdiff/2;
		//print "UP $index BY ".($zdiff/2)."<br />\n";
		if ((empty($treeid[$m]))&&(!empty($treeid[$f]))) adjust_subtree($f, -1*($boxspacing*$zdiff/4));
		$diff+=($zdiff/2);
	}
	return $diff;
}

$show_famlink = true;
if ((isset($view))&&($view=="preview")) {
	$show_famlink = false;
}

if (!isset($show_full)) $show_full=$PEDIGREE_FULL_DETAILS;
if ($show_full=="") $show_full = 0;
if (!isset($talloffset)) $talloffset = $PEDIGREE_LAYOUT;
if ($talloffset=="") $talloffset = 0;

if ((!isset($PEDIGREE_GENERATIONS))||($PEDIGREE_GENERATIONS=="")) $PEDIGREE_GENERATIONS=$DEFAULT_PEDIGREE_GENERATIONS;

if ($PEDIGREE_GENERATIONS > $MAX_PEDIGREE_GENERATIONS) {
	$PEDIGREE_GENERATIONS = $MAX_PEDIGREE_GENERATIONS;
	$max_generation = TRUE;
}

if ($PEDIGREE_GENERATIONS < 3) {
	$PEDIGREE_GENERATIONS = 3;
	$min_generation = TRUE;
}
$OLD_PGENS = $PEDIGREE_GENERATIONS;

if (!isset($rootid)) $rootid="";
$rootid = CleanInput($rootid);
$rootid = CheckRootId($rootid);

if (showLivingNameByID($rootid)) {
	$name = GetPersonName($rootid);
	$addname = GetAddPersonName($rootid);
}
else {
	$name = $gm_lang["private"];
	$addname = "";
}

// NOTE: Print html header information
$title = $name;
if ($SHOW_ID_NUMBERS) $title .= " - ".$rootid;
$title .= " - ".$gm_lang["index_header"];
print_header($title);
print "<div id=\"content_pedigree\">";
	if ($view=="preview") print "<h3>".str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($PEDIGREE_GENERATIONS), $gm_lang["gen_ped_chart"]).":";
	else print "<h3>".$gm_lang["index_header"].":";
	print "<br />".PrintReady($name);
	if ($addname != "") print "<br />" . PrintReady($addname);
	$rootfams = FindPrimaryFamilyId($rootid);
	$famrela = "";
	foreach ($rootfams as $key=>$fam) {
		if ($fam["primary"] == "Y") {
			$famrela = $fam["relation"];
			break;
		}
	}
	if (!empty($famrela)) {
		if ($TEXT_DIRECTION == "ltr") print "&nbsp;&lrm;(".$gm_lang[$famrela."_parents"].")&lrm;";
		else print "&nbsp;&rlm;(".$gm_lang[$famrela."_parents"].")&rlm;";
	}
	print "</h3>";

	// NOTE: Print the form to change the number of displayed generations
	if ($view!="preview") {
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
		if (isset($max_generation) == TRUE) print "<span class=\"error\">".str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($PEDIGREE_GENERATIONS), $gm_lang["max_generation"])."</span>";
		if (isset($min_generation) == TRUE) print "<span class=\"error\">".$gm_lang["min_generation"]."</span>";
		print "<form name=\"people\" method=\"get\" action=\"pedigree.php\">";
		print "<input type=\"hidden\" name=\"show_full\" value=\"$show_full\" />";
		print "<input type=\"hidden\" name=\"talloffset\" value=\"$talloffset\" />";
		print "<table class=\"pedigree_table $TEXT_DIRECTION\" width=\"225\">";
		
		print "<tr><td colspan=\"2\" class=\"topbottombar\" style=\"text-align:center; \">";
		print $gm_lang["options"]."</td></tr>";
	
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("rootid_help", "qm");
		print $gm_lang["root_person"];
		print "</td>";
		print "<td class=\"shade1\">";
		print "<input class=\"pedigree_form\" type=\"text\" id=\"rootid\" name=\"rootid\" size=\"3\" value=\"$rootid\" />";
		PrintFindIndiLink("rootid","");
		print "</td></tr>";
	
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("PEDIGREE_GENERATIONS_help", "qm");
		print $gm_lang["generations"];
		print "</td>";
		print "<td class=\"shade1\">";
		print "<select name=\"PEDIGREE_GENERATIONS\">";
		for ($i=3; $i<=$MAX_PEDIGREE_GENERATIONS; $i++) {
			print "<option value=\"".$i."\"" ;
			if ($i == $OLD_PGENS) print " selected=\"selected\" ";
			print ">".$i."</option>";
		}
		print "</select></td></tr>";
		
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("talloffset_help", "qm");
		print $gm_lang["orientation"];
		print "</td><td class=\"shade1\">";
		print "<input type=\"radio\" name=\"talloffset\" value=\"0\" ";
		if (!$talloffset) print "checked=\"checked\" ";
		print "onclick=\"document.people.talloffset.value='1';\" />".$gm_lang["portrait"];
		print "<br /><input type=\"radio\" name=\"talloffset\" value=\"1\" ";
		if ($talloffset) print "checked=\"checked\" ";
		print "onclick=\"document.people.talloffset.value='0';\" />".$gm_lang["landscape"];
		print "<br /></td></tr>";
	
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("show_full_help", "qm");
		print $gm_lang["show_details"];
		print "</td>";
		print "<td class=\"shade1\">";
		print "<input type=\"checkbox\" value=\"";
		if ($show_full) print "1\" checked=\"checked\" onclick=\"document.people.show_full.value='0';\"";
		else print "0\" onclick=\"document.people.show_full.value='1';\"";
		print " />";
		print "</td></tr>";
		
		print "<tr><td class=\"center\" colspan=\"2\">";
		print "\n\t\t<input type=\"submit\" value=\"".$gm_lang["view"]."\" />";
		print "</td></tr>";
		
		print "</table>";
		print "</form>";
	}
	print "<div style=\"position: relative; z-index:0;\">\n";
	// NOTE: Adjustments for hide details
	if ($show_full==false) {
		$bheight=30;
		$bwidth-=40;
		$baseyoffset+=30;
	}
	// NOTE: Adjustments for portrait mode
	if ($talloffset==0) {
		$bxspacing+=12;
		$basexoffset+=50;
		$bwidth+=20;
	}
	// NOTE: Adjustments for preview
	if ($view=="preview") {
		$baseyoffset-=20;
	}
	
	$pbwidth = $bwidth;
	$pbheight = $bheight-1;
	$pbwidth = $bwidth+6;
	$pbheight = $bheight+5;
	
	$treeid = PedigreeArray($rootid);
	$treesize = pow(2, (int)($PEDIGREE_GENERATIONS))-1;
	
	if (($PEDIGREE_GENERATIONS < 5)&&($show_full==false)) {
		// Set the width of the pedigree
		$baseyoffset+=($PEDIGREE_GENERATIONS*$pbheight/2)+20;
		if ($view=="preview") $baseyoffset-=120;
	}
	
	if ($PEDIGREE_GENERATIONS==3) {
		$baseyoffset+=$pbheight*1.6;
	}
	if ((isset($max_generation) == TRUE)||(isset($min_generation) == TRUE)) $baseyoffset+=20;
	
	// NOTE: This next section will create and position the DIV layers for the pedigree tree
	$curgen = 1;			// -- variable to track which generation the algorithm is currently working on
	$yoffset=0;				// -- used to offset the position of each box as it is generated
	$xoffset=0;
	$prevyoffset=0;		// -- used to track the y position of the previous box
	$offsetarray = array();
	$minyoffset = 0;
	if ($treesize<3) $treesize=3;
	// -- loop through all of id's in the array starting at the last and working to the first
	//-- calculation the box positions
	for($i=($treesize-1); $i>=0; $i--) {
		// -- check to see if we have moved to the next generation
		if ($i < floor($treesize / (pow(2, $curgen)))) {
			$curgen++;
		}
		//-- box position in current generation
		$boxpos = $i-pow(2, $PEDIGREE_GENERATIONS-$curgen);
		//-- offset multiple for current generation
		$genoffset = pow(2, $curgen-$talloffset);
		$boxspacing = $pbheight+$byspacing;
		// -- calculate the yoffset		Position in the generation		Spacing between boxes		put child between parents
		$yoffset = $baseyoffset+($boxpos * ($boxspacing * $genoffset))+(($boxspacing/2)*$genoffset)+($boxspacing * $genoffset);
		if ($talloffset==0) {
			//-- compact the tree
			if ($curgen<$PEDIGREE_GENERATIONS) {
				$parent = floor(($i-1)/2);
				if ($i%2 == 0) $yoffset=$yoffset - (($boxspacing/2) * ($curgen-1));
				else $yoffset=$yoffset + (($boxspacing/2) * ($curgen-1));
				
				$pgen = $curgen;
				while($parent>0) {
					if ($parent%2 == 0) $yoffset=$yoffset - (($boxspacing/2) * $pgen);
					else $yoffset=$yoffset + (($boxspacing/2) * $pgen);
					$pgen++;
					if ($pgen>3) {
						$temp=0;
						for($j=1; $j<($pgen-2); $j++) $temp += (pow(2, $j)-1);
						if ($parent%2 == 0) $yoffset=$yoffset - (($boxspacing/2) * $temp);
						else $yoffset=$yoffset + (($boxspacing/2) * $temp);
					}
					$parent = floor(($parent-1)/2);
				}
				if ($curgen>3) {
					$temp=0;
						for($j=1; $j<($curgen-2); $j++) $temp += (pow(2, $j)-1);
					if ($i%2 == 0) $yoffset=$yoffset - (($boxspacing/2) * $temp);
					else $yoffset=$yoffset + (($boxspacing/2) * $temp);
				}
			}
		}
		// -- calculate the xoffset
		$xoffset = 20+$basexoffset+ (($PEDIGREE_GENERATIONS - $curgen) * (($pbwidth+$bxspacing)/(2-$talloffset)));
		$offsetarray[$i]["x"]=$xoffset;
		$offsetarray[$i]["y"]=$yoffset;
	}
	
	//-- collapse the tree if boxes are missing
	if (!$SHOW_EMPTY_BOXES) {
		if ($PEDIGREE_GENERATIONS>1) collapse_tree(0, 1, 0);
	}
	
	//-- calculate the smallest yoffset and adjust the tree to that offset
	$minyoffset = 0;
	for($i=0; $i<count($treeid); $i++) {
		if ($SHOW_EMPTY_BOXES || !empty($treeid[$i])) {
			if (!empty($offsetarray[$i])) {
				if (($minyoffset==0)||($minyoffset>$offsetarray[$i]["y"]))  $minyoffset = $offsetarray[$i]["y"];
			}
		}
	}
	
	$ydiff = $baseyoffset+35-$minyoffset;
	adjust_subtree(0, $ydiff);
	
	//-- if no father keep the tree off of the pedigree form
	if (($view!="preview")&&($offsetarray[0]["y"]+$baseyoffset<300)) adjust_subtree(0, 300-($offsetarray[0]["y"]+$baseyoffset));
	print "<div id=\"pedigree_chart";
	if ($TEXT_DIRECTION=="rtl") print "_rtl";
	if ($view=="preview") {
		print "\" style=\"top: 1px;";
	}
	else print "\" style=\"z-index: 1;";
	print "\">\n";
	//-- print the boxes
	$curgen = 1;
	$yoffset=0;				// -- used to offset the position of each box as it is generated
	$xoffset=0;
	$prevyoffset=0;		// -- used to track the y position of the previous box
	$maxyoffset = 0;
	for($i=($treesize-1); $i>=0; $i--) {
		// -- check to see if we have moved to the next generation
		if ($i < floor($treesize / (pow(2, $curgen)))) {
			$curgen++;
		}
		$prevyoffset = $yoffset;
		$xoffset = $offsetarray[$i]["x"];
		$yoffset = $offsetarray[$i]["y"];
		// -- if we are in the middle generations then we need to draw the connecting lines
		if (($curgen >(1+$talloffset)) && ($curgen < $PEDIGREE_GENERATIONS)) {
			if ($i%2==1) {
				if ($SHOW_EMPTY_BOXES || ($treeid[$i]) || ($treeid[$i+1])) {
					$vlength = ($prevyoffset-$yoffset);
					if (!$SHOW_EMPTY_BOXES && (empty($treeid[$i+1]))) {
						$parent = ceil(($i-1)/2);
						$vlength = $offsetarray[$parent]["y"]-$yoffset;
					}
					$linexoffset = $xoffset-1;
					print "<div id=\"line$i\" dir=\"";
					if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right:";
					else print "ltr\" style=\"position:absolute; left:";
					print $linexoffset."px; top:".($yoffset+$pbheight/2)."px; z-index: 0;\">";
					print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" width=\"3\" height=\"".$vlength."\" alt=\"\" />";
					print "</div>";
				}
			}
		}
		// -- draw the box
		if (!empty($treeid[$i]) || $SHOW_EMPTY_BOXES) {
			if ($yoffset>$maxyoffset) $maxyoffset=$yoffset;
			print "\n\t\t<div id=\"box";
			if (empty($treeid[$i])) print "$i";
			else print $treeid[$i];
			if ($TEXT_DIRECTION=="rtl") print ".1.0\" style=\"position:absolute; right:";
			else print ".1.0\" style=\"position:absolute; left:";
			print $xoffset."px; top:".($yoffset-2)."px; width:".$pbwidth."px; height:".$pbheight."px; z-index: 0;\">";
			print "\n\t\t\t<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" dir=\"$TEXT_DIRECTION\">";
			if (($curgen >(1+$talloffset)) && ($curgen < $PEDIGREE_GENERATIONS)) {
				print "<tr><td style=\"vertical-align: middle;\">";
				print "\n\t\t\t<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" hspace=\"0\" vspace=\"0\" alt=\"\" />";
				print "\n\t\t\t</td><td width=\"100%\">";
			}
			else print "<tr><td width=\"100%\">";
			$mfstyle = "";
			if (!empty($treeid[$i])) {
				$indirec = FindPersonRecord($treeid[$i]);
				$ct = preg_match("/1 SEX F/", $indirec);
				if ($ct>0) $mfstyle="F";
			}
			if (!isset($treeid[$i])) $treeid[$i] = false;
			print_pedigree_person($treeid[$i], 1, $show_famlink, 0, 1);
			
			if (($curgen==1)&&(count(FindFamilyIds($treeid[$i]))>0)) {
				$did = 1;
				if ($i > ($treesize/2) + ($treesize/4)-1) $did++;
				print "\n\t\t\t\t</td><td style=\"vertical-align: middle\">";
				if ($view!="preview") {
					print "<a href=\"pedigree.php?PEDIGREE_GENERATIONS=$OLD_PGENS&amp;rootid=".$treeid[$did]."&amp;show_full=$show_full&amp;talloffset=$talloffset\" ";
					if ($TEXT_DIRECTION=="rtl") {
						print "onmouseover=\"swap_image('arrow$i',0);\" onmouseout=\"swap_image('arrow$i',0);\">";
						print "<img id=\"arrow$i\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["larrow"]["other"]."\" border=\"0\" alt=\"\" />";
					}
					else {
						print "onmouseover=\"swap_image('arrow$i',1);\" onmouseout=\"swap_image('arrow$i',1);\">";
						print "<img id=\"arrow$i\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"]."\" border=\"0\" alt=\"\" />";
					}
					print "</a>";
				}
			}
			print "\n\t\t\t</td></tr></table>\n\t\t</div>";
		}
	}
	
	if (showLivingNameByID($rootid)) {
		// -- print left arrow for decendants so that we can move down the tree
		$yoffset += ($pbheight / 2)-10;
		$famids = FindSfamilyIds($rootid);
		//-- make sure there is more than 1 child in the family with parents
		$cfamids = FindFamilyIds($rootid);
		$num=0;
		for($f=0; $f<count($cfamids); $f++) {
			$famrec = FindFamilyRecord($cfamids[$f]["famid"]);
			if ($famrec) {
				$num += preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
			}
		}
		if ($famids||($num>1)) {
			print "\n\t\t<div id=\"childarrow\" dir=\"";
			if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right:";
			else print "ltr\" style=\"position:absolute; left:";
			print $basexoffset."px; top:".$yoffset."px; width:10px; height:10px; \">";
			if ($view!="preview") {
				if ($TEXT_DIRECTION=="rtl") print "<a href=\"javascript: ".$gm_lang["show"]."\" onclick=\"togglechildrenbox(); return false;\" onmouseover=\"swap_image('larrow',1);\" onmouseout=\"swap_image('larrow',1);\">";
				else print "<a href=\"javascript: ".$gm_lang["show"]."\" onclick=\"togglechildrenbox(''); return false;\" onmouseover=\"swap_image('larrow',0);\" onmouseout=\"swap_image('larrow',0);\">";
				if ($TEXT_DIRECTION=="rtl") print "<img id=\"larrow\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"]."\" border=\"0\" alt=\"\" />";
				else print "<img id=\"larrow\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["larrow"]["other"]."\" border=\"0\" alt=\"\" />";
				print "</a>";
			}
			print "\n\t\t</div>";
			$yoffset += ($pbheight / 2)+10;
			print "\n\t\t<div id=\"childbox\" dir=\"";
			if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right:";
			else print "ltr\" style=\"position:absolute; left:";
			print $xoffset."px; top:".$yoffset."px; width:".$pbwidth."px; height:".$pbheight."px; visibility: hidden;\">";
			print "\n\t\t\t<table class=\"person_box\"><tr><td>";
			for($f=0; $f<count($famids); $f++) {
				$famrec = FindFamilyRecord(trim($famids[$f]["famid"]));
				if ($famrec) {
					$parents = FindParents($famids[$f]["famid"]);
					if($parents) {
						if($rootid!=$parents["HUSB"]) $spid=$parents["HUSB"];
						else $spid=$parents["WIFE"];
						if (!empty($spid)) {
							print "\n\t\t\t\t<a href=\"pedigree.php?PEDIGREE_GENERATIONS=$OLD_PGENS&amp;rootid=$spid&amp;show_full=$show_full&amp;talloffset=$talloffset\"><span ";
							if (showLivingNameById($spid)) {
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
						$pid = $smatch[$i][1];
						print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"pedigree.php?PEDIGREE_GENERATIONS=$OLD_PGENS&amp;rootid=$pid&amp;show_full=$show_full&amp;talloffset=$talloffset\"><span ";
						if (showLivingNameById($pid)) {
							$name = GetPersonName($pid);
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
					$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
					if ($num>1) print "<span class=\"name1\"><br />".$gm_lang["siblings"]."<br /></span>";
					for($i=0; $i<$num; $i++) {
						//-- add the following line to stop a bad PHP bug
						if ($i>=$num) break;
						$pid = $smatch[$i][1];
						if ($pid!=$rootid) {
							print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"pedigree.php?PEDIGREE_GENERATIONS=$OLD_PGENS&amp;rootid=$pid&amp;show_full=$show_full&amp;talloffset=$talloffset\"><span ";
							if (showLivingNameById($pid)) {
								$name = GetPersonName($pid);
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
		}
	}
	// -- print html footer
print "</div>\n";
print "</div>\n";

// Resize the DIV so that the footer is at the bottom of the page
$maxyoffset+=140;
?>
<script language="JavaScript" type="text/javascript">
	content_div = document.getElementById("content_pedigree");
	if (content_div) {
		content_div.style.height = <?php print $maxyoffset; ?> + "px";
	}
</script>
<?php
if ($view=="preview") print "<br /><br /><br />";
print "</div>";
print_footer();
?>
