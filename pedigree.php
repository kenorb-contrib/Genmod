<?php
/**
 * Parses gedcom file and displays a pedigree tree.
 *
 * Specify a $rootid to root the pedigree tree at a certain person
 * with id = $rootid in the GEDCOM file.
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

PrintHeader($pedigree_controller->pagetitle);

print "<div id=\"content_pedigree\">";

	// Print the page title
	if ($pedigree_controller->view == "preview") print "<div class=\"PageTitleName\">".str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($pedigree_controller->num_generations), GM_LANG_gen_ped_chart).":";
	else print "<div class=\"PageTitleName\">".GM_LANG_index_header.":";
	print "&nbsp;".$pedigree_controller->root->name;
	if ($pedigree_controller->root->addname != "") print "<br />" . $pedigree_controller->root->addname;
	
	// Print the family relation
	$fam = $pedigree_controller->root->primaryfamily;
	$family =& Family::GetInstance($fam, "", GedcomConfig::$GEDCOMID);
	$famrela = $family->pedigreetype;
	if (!empty($famrela)) {
		if ($TEXT_DIRECTION == "ltr") print "&nbsp;&lrm;(".constant("GM_LANG_".$famrela."_parents").")&lrm;";
		else print "&nbsp;&rlm;(".constant("GM_LANG_".$famrela."_parents").")&rlm;";
	}
	print "</div>";

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
		if ($pedigree_controller->max_generation == true) print "<span class=\"Error\">".str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($pedigree_controller->num_generations), GM_LANG_max_generation)."</span>";
		if ($pedigree_controller->min_generation == true) print "<span class=\"Error\">".GM_LANG_min_generation."</span>";
		print "<div class=\"PedigreeNavBlock\">";
			print "<form name=\"people\" method=\"get\" action=\"pedigree.php\">";
				print "<table class=\"NavBlockTable PedigreeNavBlockTable\">";
				
				// Option header
				$pedigree_controller->PrintInputHeader();
			
				// RootId
				$pedigree_controller->PrintInputRootId();
			
				// Generations
				$pedigree_controller->PrintInputGenerations(GedcomConfig::$MAX_PEDIGREE_GENERATIONS, "PEDIGREE_GENERATIONS_help");	
		
				// Orientation
				print "<tr><td class=\"NavBlockLabel\">";
				PrintHelpLink("talloffset_help", "qm");
				print GM_LANG_orientation;
				print "</td><td class=\"NavBlockField\">";
				print "<input type=\"radio\" name=\"talloffset\" value=\"0\" ";
				if (!$pedigree_controller->talloffset) print "checked=\"checked\" ";
				print " />".GM_LANG_portrait;
				print "<br /><input type=\"radio\" name=\"talloffset\" value=\"1\" ";
				if ($pedigree_controller->talloffset) print "checked=\"checked\" ";
				print " />".GM_LANG_landscape;
				print "<br /></td></tr>";
			
				// box width
				$pedigree_controller->PrintInputBoxWidth();
				
				// Show details
				$pedigree_controller->PrintInputShowFull();
				
				// Submit
				$pedigree_controller->PrintInputSubmit();
				
				print "</table>";
			print "</form>";
		print "</div>";
	}
	print "<div class=\"PedigreeChart\">\n";
	
	// Start calculating the tree
	// NOTE: Adjustments for hide details
	if ($pedigree_controller->show_full == false) {
		$bheight = 30;
		$bwidth -= 40;
		$baseyoffset += 30;
	}
	// NOTE: Adjustments for portrait mode
	if ($pedigree_controller->talloffset == 0) {
		$bxspacing += 12;
		$basexoffset += 50;
		$bwidth += 20;
	}
	// NOTE: Adjustments for preview
	if ($pedigree_controller->view=="preview") {
		$baseyoffset -= 20;
	}
	// This line added for resizing of the boxes
	$bwidth = $bwidth * $pedigree_controller->box_width / 100;
	
	$pbwidth = $bwidth;
	$pbheight = $bheight - 1;
	$pbwidth = $bwidth + 6;
	$pbheight = $bheight + 5;
	
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
	$curgen = 1;				// -- variable to track which generation the algorithm is currently working on
	$yoffset = 0;				// -- used to offset the position of each box as it is generated
	$xoffset = 0;
	$prevyoffset = 0;			// -- used to track the y position of the previous box
	$offsetarray = array();
	$minyoffset = 0;
	if ($treesize < 3) $treesize = 3;
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
				while($parent > 0) {
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
				if ($curgen > 3) {
					$temp=0;
						for($j=1; $j<($curgen-2); $j++) $temp += (pow(2, $j)-1);
					if ($i%2 == 0) $yoffset=$yoffset - (($boxspacing/2) * $temp);
					else $yoffset=$yoffset + (($boxspacing/2) * $temp);
				}
			}
		}
		// -- calculate the xoffset
		$xoffset = 20 + $basexoffset + (($pedigree_controller->num_generations - $curgen) * (($pbwidth+$bxspacing)/(2-$pedigree_controller->talloffset)));
		$offsetarray[$i]["x"]=$xoffset;
		$offsetarray[$i]["y"]=$yoffset;
	}
	
	//-- collapse the tree if boxes are missing
	if (!GedcomConfig::$SHOW_EMPTY_BOXES) {
		if ($pedigree_controller->num_generations > 1) $pedigree_controller->CollapseTree(0, 1, 0);
	}
	
	//-- calculate the smallest yoffset and adjust the tree to that offset
	$minyoffset = 0;
	for($i=0; $i<count($treeid); $i++) {
		if (GedcomConfig::$SHOW_EMPTY_BOXES || !empty($treeid[$i])) {
			if (!empty($offsetarray[$i])) {
				if (($minyoffset==0)||($minyoffset>$offsetarray[$i]["y"]))  $minyoffset = $offsetarray[$i]["y"];
			}
		}
	}
	
	$ydiff = $baseyoffset + 35 - $minyoffset;
	$pedigree_controller->AdjustSubtree(0, $ydiff);
	
	//-- if no father keep the tree off of the pedigree form
	if (($pedigree_controller->view != "preview")&&($offsetarray[0]["y"]+$baseyoffset<300)) $pedigree_controller->AdjustSubtree(0, 300-($offsetarray[0]["y"]+$baseyoffset));
	
	print "<div id=\"pedigree_chart";
	if ($pedigree_controller->view == "preview") {
		print "\" style=\"top: 1px;";
	}
	else print "\" style=\"z-index: 1;";
	print "\">\n";
	
	//-- print the boxes
	$curgen = 1;
	$yoffset = 0;				// -- used to offset the position of each box as it is generated
	$xoffset = 0;
	$prevyoffset = 0;		// -- used to track the y position of the previous box
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
				if (GedcomConfig::$SHOW_EMPTY_BOXES || ($treeid[$i]) || ($treeid[$i+1])) {
					$vlength = ($prevyoffset-$yoffset);
					if (!GedcomConfig::$SHOW_EMPTY_BOXES && (empty($treeid[$i+1]))) {
						$parent = ceil(($i-1)/2);
						$vlength = $offsetarray[$parent]["y"]-$yoffset;
					}
					$linexoffset = $xoffset-1;
					print "<div id=\"line$i\" dir=\"";
					if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right:";
					else print "ltr\" style=\"position:absolute; left:";
					print $linexoffset."px; top:".($yoffset+$pbheight/2)."px; z-index: 0;\">";
					print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" width=\"3\" height=\"".$vlength."\" alt=\"\" />";
					print "</div>";
				}
			}
		}
		// -- draw the box
		if (!empty($treeid[$i]) || GedcomConfig::$SHOW_EMPTY_BOXES) {
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
				print "\n\t\t\t<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" hspace=\"0\" vspace=\"0\" alt=\"\" />";
				print "\n\t\t\t</td><td width=\"100%\">";
			}
			else print "<tr><td width=\"100%\">";
			
			if (!isset($treeid[$i])) $treeid[$i] = false;
			$person =& Person::GetInstance($treeid[$i], "", GedcomConfig::$GEDCOMID);
			$fams = count($person->childfamilies);
			
			PersonFunctions::PrintPedigreePerson($person, 1, $pedigree_controller->show_full, 0, 1, $pedigree_controller->view, $pedigree_controller->params);
			
			if ($curgen == 1 && $fams > 0) {
				$did = 1;
				if ($i > ($treesize/2) + ($treesize/4)-1) $did++;
				print "\n\t\t\t\t</td><td style=\"vertical-align: middle\">";
				if ($pedigree_controller->view != "preview") {
					print "<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$treeid[$did]."&amp;show_details=".$pedigree_controller->show_details."&amp;talloffset=".$pedigree_controller->talloffset."\" ";
					if ($TEXT_DIRECTION=="rtl") {
						print "onmouseover=\"swap_image('arrow$i',0);\" onmouseout=\"swap_image('arrow$i',0);\">";
						print "<img id=\"arrow$i\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["larrow"]["other"]."\" border=\"0\" alt=\"\" />";
					}
					else {
						print "onmouseover=\"swap_image('arrow$i',1);\" onmouseout=\"swap_image('arrow$i',1);\">";
						print "<img id=\"arrow$i\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"]."\" border=\"0\" alt=\"\" />";
					}
					print "</a>";
				}
			}
			print "\n\t\t\t</td></tr></table>\n\t\t</div>";
		}
	}
	if ($pedigree_controller->root->disp_name) {
		
		// -- print left arrow for decendants so that we can move down the tree
		$yoffset += ($pbheight / 2) - 10;
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
				if ($TEXT_DIRECTION=="rtl") print "<a href=\"javascript: ".GM_LANG_show."\" onclick=\"togglechildrenbox(); return false;\" onmouseover=\"swap_image('larrow',1);\" onmouseout=\"swap_image('larrow',1);\">";
				else print "<a href=\"javascript: ".GM_LANG_show."\" onclick=\"togglechildrenbox(''); return false;\" onmouseover=\"swap_image('larrow',0);\" onmouseout=\"swap_image('larrow',0);\">";
				if ($TEXT_DIRECTION=="rtl") print "<img id=\"larrow\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"]."\" border=\"0\" alt=\"\" />";
				else print "<img id=\"larrow\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["larrow"]["other"]."\" border=\"0\" alt=\"\" />";
				print "</a>";
			}
			print "\n\t\t</div>";
			$yoffset += ($pbheight / 2)+10;
			print "\n\t\t<div id=\"childbox\" dir=\"";
			if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right:";
			else print "ltr\" style=\"position:absolute; left:";
			print $xoffset."px; top:".$yoffset."px; width:".$pbwidth."px; height:".$pbheight."px; visibility: hidden;\">";
			print "\n\t\t\t<table class=\"PersonBox\"><tr><td>";
			foreach($pedigree_controller->root->spousefamilies as $key => $fam) {
				if($pedigree_controller->xref != $fam->husb_id) $me = $fam->husb;
				else $me = $fam->wife;
				if (is_object($me) && !$me->isempty) {
					print "\n\t\t\t\t<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$me->xref."&amp;show_details=".$pedigree_controller->show_details."&amp;talloffset=".$pedigree_controller->talloffset."\"><span class=\"PersonName1\">";
					print $me->name;
					print "<br /></span></a>";
				}
				foreach($fam->children as $key2 => $child) {
					print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$child->xref."&amp;show_details=".$pedigree_controller->show_details."&amp;talloffset=".$pedigree_controller->talloffset."\"><span class=\"PersonName1\">";
					print $child->name;
					print "<br /></span></a>";
				}
			}
			//-- print the siblings
			foreach ($pedigree_controller->root->childfamilies as $key => $fam) {
				if ($fam->children_count > 1) print "<span class=\"PersonName1\"><br />".GM_LANG_siblings."<br /></span>";
				foreach ($fam->children as $key2 => $child) {
					if ($child->xref != $pedigree_controller->xref) {
						print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"pedigree.php?num_generations=".$pedigree_controller->num_generations."&amp;rootid=".$child->xref."&amp;show_details=".$pedigree_controller->show_details."&amp;talloffset=".$pedigree_controller->talloffset."\"><span class=\"PersonName1\">";
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
-->
</script>
<?php
if ($pedigree_controller->view == "preview") print "<br /><br /><br />";
print "</div>";
PrintFooter();
?>
