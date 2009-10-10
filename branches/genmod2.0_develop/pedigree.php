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

$pedigree_controller = new PedigreeController();


function adjust_subtree($index, $diff) {
	global $offsetarray, $treeid, $boxspacing, $mdiff, $SHOW_EMPTY_BOXES;
	
	$f = ($index*2)+1; //-- father index
	$m = $f+1; //-- mother index

	if (!$SHOW_EMPTY_BOXES && empty($treeid[$index])) return;
	if (empty($offsetarray[$index])) return;
	$offsetarray[$index]["y"] += $diff;
	if ($f<count($treeid)) adjust_subtree($f, $diff);
	if ($m<count($treeid)) adjust_subtree($m, $diff);
}

function collapse_tree($index, $curgen, $diff) {
	global $offsetarray, $treeid, $boxspacing, $mdiff, $minyoffset;
	global $pedigree_controller;

	//print "$index:$curgen:$diff<br />\n";
	$f = ($index*2)+1; //-- father index
	$m = $f+1; //-- mother index
	if (empty($treeid[$index])) {
		$pgen=$curgen;
		$genoffset=0;
		while($pgen <= $pedigree_controller->num_generations) {
			$genoffset += pow(2, ($pedigree_controller->num_generations-$pgen));
			$pgen++;
		}
		if ($pedigree_controller->talloffset==1) $diff+=.5*$genoffset;
		else $diff+=$genoffset;
		if (isset($offsetarray[$index]["y"])) $offsetarray[$index]["y"]-=($boxspacing*$diff)/2;
		return $diff;
	}
	if ($curgen == $pedigree_controller->num_generations) {
		$offsetarray[$index]["y"] -= $boxspacing*$diff;
		//print "UP $index BY $diff<br />\n";
		return $diff;
	}
	$odiff=$diff;
	$fdiff = collapse_tree($f, $curgen+1, $diff);
	if (($curgen < ($pedigree_controller->num_generations-1)) || ($index%2 == 1)) $diff=$fdiff;
	if (isset($offsetarray[$index]["y"])) $offsetarray[$index]["y"] -= $boxspacing*$diff;
	//print "UP $index BY $diff<br />\n";
	$mdiff = collapse_tree($m, $curgen+1, $diff);
	$zdiff = $mdiff - $fdiff;
	if ($zdiff > 0 && $curgen < ($pedigree_controller->num_generations - 2)) {
		$offsetarray[$index]["y"] -= $boxspacing*$zdiff/2;
		//print "UP $index BY ".($zdiff/2)."<br />\n";
		if ((empty($treeid[$m]))&&(!empty($treeid[$f]))) adjust_subtree($f, -1*($boxspacing*$zdiff/4));
		$diff+=($zdiff/2);
	}
	return $diff;
}

print_header($pedigree_controller->pagetitle);

print "<div id=\"content_pedigree\">";
	// Print the page title
	if ($pedigree_controller->view == "preview") print "<h3>".str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($pedigree_controller->num_generations), $gm_lang["gen_ped_chart"]).":";
	else print "<h3>".$gm_lang["index_header"].":";
	print "<br />".$pedigree_controller->root->name;
	if ($pedigree_controller->root->addname != "") print "<br />" . $pedigree_controller->root->addname;
	
	// Print the family relation
	$fam = $pedigree_controller->root->primaryfamily;
	$family =& Family::GetInstance($fam, "", $GEDCOMID);
	$famrela = $family->pedigreetype;
	if (!empty($famrela)) {
		if ($TEXT_DIRECTION == "ltr") print "&nbsp;&lrm;(".$gm_lang[$famrela."_parents"].")&lrm;";
		else print "&nbsp;&rlm;(".$gm_lang[$famrela."_parents"].")&rlm;";
	}
	print "</h3>";

	// NOTE: Print the form to change the number of displayed generations
	if ($pedigree_controller->view != "preview") {
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
		if ($pedigree_controller->max_generation == true) print "<span class=\"error\">".str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($pedigree_controller->num_generations), $gm_lang["max_generation"])."</span>";
		if ($pedigree_controller->min_generation == true) print "<span class=\"error\">".$gm_lang["min_generation"]."</span>";
		print "<form name=\"people\" method=\"get\" action=\"pedigree.php\">";
		print "<input type=\"hidden\" name=\"show_full\" value=\"".$pedigree_controller->show_full."\" />";
		print "<input type=\"hidden\" name=\"talloffset\" value=\"".$pedigree_controller->talloffset."\" />";
		print "<table class=\"pedigree_table $TEXT_DIRECTION\" width=\"225\">";
		
		print "<tr><td colspan=\"2\" class=\"topbottombar\" style=\"text-align:center; \">";
		print $gm_lang["options"]."</td></tr>";
	
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("rootid_help", "qm");
		print $gm_lang["root_person"];
		print "</td>";
		print "<td class=\"shade1\">";
		print "<input class=\"pedigree_form\" type=\"text\" id=\"rootid\" name=\"rootid\" size=\"3\" value=\"".$pedigree_controller->xref."\" />";
		LinkFunctions::PrintFindIndiLink("rootid","");
		print "</td></tr>";
	
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("PEDIGREE_GENERATIONS_help", "qm");
		print $gm_lang["generations"];
		print "</td>";
		print "<td class=\"shade1\">";
		print "<select name=\"num_generations\">";
		for ($i=3; $i<=$MAX_PEDIGREE_GENERATIONS; $i++) {
			print "<option value=\"".$i."\"" ;
			if ($i == $pedigree_controller->num_generations) print " selected=\"selected\" ";
			print ">".$i."</option>";
		}
		print "</select></td></tr>";
		
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("talloffset_help", "qm");
		print $gm_lang["orientation"];
		print "</td><td class=\"shade1\">";
		print "<input type=\"radio\" name=\"talloffset\" value=\"0\" ";
		if (!$pedigree_controller->talloffset) print "checked=\"checked\" ";
		print "onclick=\"document.people.talloffset.value='1';\" />".$gm_lang["portrait"];
		print "<br /><input type=\"radio\" name=\"talloffset\" value=\"1\" ";
		if ($pedigree_controller->talloffset) print "checked=\"checked\" ";
		print "onclick=\"document.people.talloffset.value='0';\" />".$gm_lang["landscape"];
		print "<br /></td></tr>";
	
		print "<tr><td class=\"shade2 wrap\">";
		print_help_link("show_full_help", "qm");
		print $gm_lang["show_details"];
		print "</td>";
		print "<td class=\"shade1\">";
		print "<input type=\"checkbox\" value=\"";
		if ($pedigree_controller->show_full) print "1\" checked=\"checked\" onclick=\"document.people.show_full.value='0';\"";
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
	
	// Start calculating the tree
	// NOTE: Adjustments for hide details
	if ($pedigree_controller->show_full == false) {
		$bheight=30;
		$bwidth-=40;
		$baseyoffset+=30;
	}
	// NOTE: Adjustments for portrait mode
	if ($pedigree_controller->talloffset == 0) {
		$bxspacing+=12;
		$basexoffset+=50;
		$bwidth+=20;
	}
	// NOTE: Adjustments for preview
	if ($pedigree_controller->view=="preview") {
		$baseyoffset-=20;
	}
	
	$pbwidth = $bwidth;
	$pbheight = $bheight-1;
	$pbwidth = $bwidth+6;
	$pbheight = $bheight+5;
	
	// Get the ID's in the tree
	$treeid = ChartFunctions::PedigreeArray($pedigree_controller->xref, $pedigree_controller->num_generations);
	$treesize = pow(2, (int)($pedigree_controller->num_generations))-1;
	
	if ($pedigree_controller->num_generations < 5 && $pedigree_controller->show_full == false) {
		// Set the width of the pedigree
		$baseyoffset+=($pedigree_controller->num_generations*$pbheight/2)+20;
		if ($pedigree_controller->view == "preview") $baseyoffset-=120;
	}
	
	if ($pedigree_controller->num_generations == 3) {
		$baseyoffset+=$pbheight*1.6;
	}
	if ($pedigree_controller->max_generation == true || $pedigree_controller->min_generation == true) $baseyoffset+=20;
	
	// NOTE: This next section will create and position the DIV layers for the pedigree tree
	$curgen = 1;			// -- variable to track which generation the algorithm is currently working on
	$yoffset=0;				// -- used to offset the position of each box as it is generated
	$xoffset=0;
	$prevyoffset=0;			// -- used to track the y position of the previous box
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
		$boxpos = $i-pow(2, $pedigree_controller->num_generations - $curgen);
		//-- offset multiple for current generation
		$genoffset = pow(2, $curgen-$pedigree_controller->talloffset);
		$boxspacing = $pbheight+$byspacing;
		// -- calculate the yoffset		Position in the generation		Spacing between boxes		put child between parents
		$yoffset = $baseyoffset+($boxpos * ($boxspacing * $genoffset))+(($boxspacing/2)*$genoffset)+($boxspacing * $genoffset);
		if ($pedigree_controller->talloffset == 0) {
			//-- compact the tree
			if ($curgen < $pedigree_controller->num_generations) {
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
		$xoffset = 20+$basexoffset+ (($pedigree_controller->num_generations - $curgen) * (($pbwidth+$bxspacing)/(2-$pedigree_controller->talloffset)));
		$offsetarray[$i]["x"]=$xoffset;
		$offsetarray[$i]["y"]=$yoffset;
	}
	
	//-- collapse the tree if boxes are missing
	if (!$SHOW_EMPTY_BOXES) {
		if ($pedigree_controller->num_generations > 1) collapse_tree(0, 1, 0);
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
	if (($pedigree_controller->view != "preview")&&($offsetarray[0]["y"]+$baseyoffset<300)) adjust_subtree(0, 300-($offsetarray[0]["y"]+$baseyoffset));
	
	print "<div id=\"pedigree_chart";
	if ($TEXT_DIRECTION=="rtl") print "_rtl";
	if ($pedigree_controller->view == "preview") {
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
		if (($curgen >(1 + $pedigree_controller->talloffset)) && ($curgen < $pedigree_controller->num_generations)) {
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
			if (($curgen > (1 + $pedigree_controller->talloffset)) && ($curgen < $pedigree_controller->num_generations)) {
				print "<tr><td style=\"vertical-align: middle;\">";
				print "\n\t\t\t<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" hspace=\"0\" vspace=\"0\" alt=\"\" />";
				print "\n\t\t\t</td><td width=\"100%\">";
			}
			else print "<tr><td width=\"100%\">";
			
			if (!isset($treeid[$i])) $treeid[$i] = false;
			$person =& Person::GetInstance($treeid[$i], "", $GEDCOMID);
			$fams = count($person->childfamilies);
			
			PersonFunctions::PrintPedigreePerson($person, 1, 1, 0, 1, $pedigree_controller->view, $pedigree_controller->num_generations, $pedigree_controller->talloffset);
			
			if ($curgen == 1 && $fams > 0) {
				$did = 1;
				if ($i > ($treesize/2) + ($treesize/4)-1) $did++;
				print "\n\t\t\t\t</td><td style=\"vertical-align: middle\">";
				if ($pedigree_controller->view != "preview") {
					print "<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$treeid[$did]."&amp;show_full=".$pedigree_controller->show_full."&amp;talloffset=".$pedigree_controller->talloffset."\" ";
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
	if ($pedigree_controller->root->disp_name) {
		
		// -- print left arrow for decendants so that we can move down the tree
		$yoffset += ($pbheight / 2)-10;
		$famids = count($pedigree_controller->root->spousefamilies);
		
		//-- make sure there is more than 1 child in the family with parents
		$num=0;
		foreach($pedigree_controller->root->childfamilies as $key =>$family) {
			$num += $family->children_count;
		}
		if ($famids > 0 || $num > 1) {
			print "\n\t\t<div id=\"childarrow\" dir=\"";
			if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right:";
			else print "ltr\" style=\"position:absolute; left:";
			print $basexoffset."px; top:".$yoffset."px; width:10px; height:10px; \">";
			if ($pedigree_controller->view != "preview") {
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
			foreach($pedigree_controller->root->spousefamilies as $key => $fam) {
				if($pedigree_controller->xref != $fam->husb_id) $me = $fam->husb;
				else $me = $fam->wife;
				if (is_object($me) && !$me->isempty) {
					print "\n\t\t\t\t<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$me->xref."&amp;show_full=".$pedigree_controller->show_full."&amp;talloffset=".$pedigree_controller->talloffset."\"><span class=\"name1\">";
					print $me->name;
					print "<br /></a>";
				}
				foreach($fam->children as $key2 => $child) {
					print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$child->xref."&amp;show_full=".$pedigree_controller->show_full."&amp;talloffset=".$pedigree_controller->talloffset."\"><span class=\"name1\">";
					print $child->name;
					print "<br /></a>";
				}
			}
			//-- print the siblings
			foreach ($pedigree_controller->root->childfamilies as $key => $fam) {
				if ($fam->children_count > 1) print "<span class=\"name1\"><br />".$gm_lang["siblings"]."<br /></span>";
				foreach ($fam->children as $key2 => $child) {
					if ($child->xref != $pedigree_controller->xref) {
						print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$child->xref."&amp;show_full=".$pedigree_controller->show_full."&amp;talloffset=".$pedigree_controller->talloffset."\"><span class=\"name1\">";
						print $child->name;
						print "<br /></span></a>";
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
<!--
	content_div = document.getElementById("content_pedigree");
	if (content_div) {
		content_div.style.height = <?php print $maxyoffset; ?> + "px";
	}
//-->
</script>
<?php
if ($pedigree_controller->view == "preview") print "<br /><br /><br />";
print "</div>";
print_footer();
?>
