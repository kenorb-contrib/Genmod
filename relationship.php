<?php
/**
 * Calculates the relationship between two individuals in the gedcom
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
 * This Page Is Valid XHTML 1.0 Transitional! > 20 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$relationship_controller = new RelationshipController();

// Set the box sizes
if ($relationship_controller->show_full == false) {
	$Dbheight = 25;
	$Dbwidth -= 40;
}
$Dbwidth *= $box_width / 100;
$bwidth = $Dbwidth;
$bheight = $Dbheight;

// Init some variables
$check_node = true;
$disp = true;

// -- print html header information
PrintHeader($relationship_controller->pagetitle);

// Reset the session cache if new persons are entered
if ((!empty($_SESSION["pid1"]) && $_SESSION["pid1"] != $relationship_controller->pid1) || (!empty($_SESSION["pid2"]) && $_SESSION["pid2"] != $relationship_controller->pid2)) {
	unset($_SESSION["relationships"]);
	$relationship_controller->path_to_find = "0";
}

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
print "<div style=\"position: relative; z-index: 1; width:98%;\">\n";
print "<table class=\"list_table $TEXT_DIRECTION\" style=\"width:100%;\"><tr><td valign=\"top\">";
$title_string = GM_LANG_relationship_chart;
if ($relationship_controller->pid1 != "" && $relationship_controller->pid2 != "") {
	$title_string .= ":<br /><br />".$relationship_controller->person1->name;
	$title_string .= "<br />".GM_LANG_and." ".$relationship_controller->person2->name;
}
print "\n\t<h3>".PrintReady($title_string)."</h3>";
print "</td><td>";

// -- print the form to change the number of displayed generations
if ($relationship_controller->view != "preview") {
	$Dbaseyoffset += 70;
	print "\n\t<form name=\"people\" method=\"get\" action=\"relationship.php\">\n";
	print "<input type=\"hidden\" name=\"path_to_find\" value=\"".$relationship_controller->path_to_find."\" />\n";

	print "\n\t\t<table class=\"list_table ".$TEXT_DIRECTION."\" align=\"";
	if ($TEXT_DIRECTION == "ltr") print "right";
	else print "left";
	print "\">";

	// First row
	print "<tr>";
	
	// Relationship header
	print "<td colspan=\"2\" class=\"topbottombar center\">";
	print GM_LANG_relationship_chart."</td>";

	// Empty space
	print "<td>&nbsp;</td>";

	// Options header
	$relationship_controller->PrintInputHeader(false);
	
	print "</tr>";
	// Second row
	print "<tr>";
	
	// Person 1
	print "<td class=\"shade2\">";
	PrintHelpLink("relationship_id_help", "qm");
	print GM_LANG_person1."</td>";
	print "<td class=\"shade1 vmiddle\">";
	print "<input tabindex=\"1\" class=\"pedigree_form\" type=\"text\" name=\"pid1\" id=\"pid1\" size=\"3\" value=\"".$relationship_controller->pid1."\" />";
	LinkFunctions::PrintFindIndiLink("pid1","");
        print "</td>";

	// Empty space
	print "<td>&nbsp;</td>";

	// Show details
	$relationship_controller->PrintInputShowFull(false);
	
	print "</tr>";
	// Third row
	print "<tr>";
	
	// Person 2
	print "<td class=\"shade2\">";
	PrintHelpLink("relationship_id_help", "qm");
	print GM_LANG_person2."</td>\n";
	print "<td class=\"shade1 vmiddle\">";
	print "<input tabindex=\"2\" class=\"pedigree_form\" type=\"text\" name=\"pid2\" id=\"pid2\" size=\"3\" value=\"".$relationship_controller->pid2."\" />";
        LinkFunctions::PrintFindIndiLink("pid2","");
        print "</td>";

	// Empty space
	print "<td>&nbsp;</td>";

	// Show details
	$relationship_controller->PrintInputBoxWidth(false);
	
	print "</tr>";
	// Fourth row
	print "<tr>";

	// Check relationships by marriage
	print "<td class=\"shade2\">";
	PrintHelpLink("follow_spouse_help", "qm");
	print GM_LANG_follow_spouse;
	print "</td>";
	print "<td class=\"shade1\">";
	print "<input tabindex=\"4\" type=\"checkbox\" name=\"followspouse\" value=\"1\"";
	if ($relationship_controller->followspouse) print " checked=\"checked\"";
	print " onclick=\"document.people.path_to_find.value='-1';\" /></td>";
	
	// Empty space
	print "<td>&nbsp;</td>";

	// Show oldest top
	print "<td class=\"shade2\">";
	PrintHelpLink("oldest_top_help", "qm");
	print GM_LANG_oldest_top;
	print "</td><td class=\"shade1\">";
	print "<input tabindex=\"5\" type=\"checkbox\" id=\"oldtop\" name=\"asc\" value=\"-1\" ";
	if ($relationship_controller->asc == -1) print " checked=\"checked\"";
	if (!$relationship_controller->pretty) print " disabled=\"disabled\"";
	print " />";
	print "</td>";
	
	print "</tr>";
	// Fifth row
	print "<tr>";
	
	// Show path
	print "<td class=\"shade2\">";

	$pass = false;
	if (isset($_SESSION["relationships"]) && !$relationship_controller->person1->isempty && !$relationship_controller->person2->isempty) {
		$pass = true;
		$i = 0;
		$new_path = true;
		if (isset($_SESSION["relationships"][$relationship_controller->path_to_find])) {
			$node = $_SESSION["relationships"][$relationship_controller->path_to_find];
		}
		else {
			$node = GetRelationship($relationship_controller->person1, $relationship_controller->person2, $relationship_controller->followspouse, 0, true, $relationship_controller->path_to_find);
			$_SESSION["pid1"] = $relationship_controller->pid1;
			$_SESSION["pid2"] = $relationship_controller->pid2;
			$_SESSION["relationships"][$relationship_controller->path_to_find] = $node;
		}
		if (!$node){
			//print "path --";
			$relationship_controller->path_to_find--;
			$check_node = $node;
		}
		foreach($_SESSION["relationships"] as $indexval => $node) {
			if ($node == false) {
				$check_node = false;
				break;
			}
			if ($i == 0) print GM_LANG_show_path.": </td><td class=\"list_value\" style=\"padding: 3px;\">";
			if ($i > 0) print " | ";
			if ($i == $relationship_controller->path_to_find){
				print "<span class=\"error\" style=\"valign: middle\">".($i+1)."</span>";
				$new_path = false;
			}
			else {
				print "<a href=\"relationship.php?pid1=".$relationship_controller->pid1."&amp;pid2=".$relationship_controller->pid2."&amp;path_to_find=".$i."&amp;followspouse=".$relationship_controller->followspouse."&amp;pretty=".$relationship_controller->pretty."&amp;show_details=".$relationship_controller->show_details."&amp;asc=".$relationship_controller->asc."&amp;box_width=".$box_width."\">".($i+1)."</a>\n";
			}
			$i++;
		}
		if ($new_path && $relationship_controller->path_to_find < $i+1 && $check_node) print " | <span class=\"error\">".($i+1)."</span>";
		if ($i == 0) print "</td><td class=\"shade1\">";
		print "</td>";
	}
	else {
		if (!$relationship_controller->person1->isempty && !$relationship_controller->person2->isempty) {
			if (!$relationship_controller->person1->disp_name) $disp = false;
			else if (!$relationship_controller->person2->disp_name) $disp = false;
			if ($disp) {
				print GM_LANG_show_path.": </td>";
				print "\n\t\t<td class=\"shade1\">";
				print " <span class=\"error vmmiddle\">";
				if (isset($_SESSION["relationships"][$relationship_controller->path_to_find])) $check_node = $_SESSION["relationships"][$relationship_controller->path_to_find];
				else {
					$check_node = GetRelationship($relationship_controller->person1, $relationship_controller->person2, $relationship_controller->followspouse, 0, true, $relationship_controller->path_to_find);
//					if ($check_node !== false) {
						$_SESSION["pid1"] = $relationship_controller->pid1;
						$_SESSION["pid2"] = $relationship_controller->pid2;
						if (!isset($_SESSION["relationships"])) $_SESSION["relationships"] = array();
						$_SESSION["relationships"][$relationship_controller->path_to_find] = $check_node;
//					}
				}
				print ($check_node ? "1" : "&nbsp;".GM_LANG_no_results)."</span></td>";
				$prt = true;
			}
		}
		if (!isset($prt)) print "&nbsp;</td><td class=\"shade1\">&nbsp;</td>";
	}

	// Empty space
	print "<td>&nbsp;</td>";

	// Line up generations
	print "<td class=\"shade2\">";
	PrintHelpLink("line_up_generations_help", "qm");
	print GM_LANG_line_up_generations."</td>";
	print "<td class=\"shade1\">";
	print "<input tabindex=\"6\" type=\"checkbox\" name=\"pretty\" value=\"2\"";
	if ($relationship_controller->pretty) print " checked=\"checked\"";
	print " onclick=\"toggleStatus('oldtop');\"";
	print " /></td>";

	print "</tr>";
	// Sixth row
	print "<tr>";
	
	if (!$relationship_controller->person1->isempty && !$relationship_controller->person2->isempty && $disp){
		if ($disp && !$check_node) print "<td class=\"wrap vmiddle center\" colspan=\"2\"><span class=\"error\">".(isset($_SESSION["relationships"])?GM_LANG_no_link_found : "")."</span></td>";
		else print "<td class=\"vmiddle center\" colspan=\"2\"><input type=\"submit\" value=\"".GM_LANG_next_path."\" onclick=\"document.people.path_to_find.value='".($relationship_controller->path_to_find + 1)."';\" /></td>\n";
		$pass = true;
	}

	if ($pass == false) print "<td colspan=\"2\" class=\"center wrap\">&nbsp;</td>";

	// Empty space
	print "<td>&nbsp;</td>";

	// View button
	$relationship_controller->PrintInputSubmit(false);
	
	print "</tr>";


	print "</table></form>";
}
else {
	$Dbaseyoffset=55;
	$Dbasexoffset=10;
}
print "</td></tr></table>";
print "</div>\n";

$maxyoffset = $Dbaseyoffset;
if (!$relationship_controller->person1->isempty && !$relationship_controller->person2->isempty) {
	if (!$disp) PrintPrivacyError(GedcomConfig::$CONTACT_EMAIL);
	else {
		if (isset($_SESSION["relationships"][$relationship_controller->path_to_find])) $node = $_SESSION["relationships"][$relationship_controller->path_to_find];
		else {
			$node = GetRelationship($relationship_controller->person1, $relationship_controller->person2, $relationship_controller->followspouse, 0, true, $relationship_controller->path_to_find);
			$_SESSION["pid1"] = $relationship_controller->pid1;
			$_SESSION["pid2"] = $relationship_controller->pid2;
			if (!isset($_SESSION["relationships"])) $_SESSION["relationships"] = array();
			$_SESSION["relationships"][$relationship_controller->path_to_find] = $node;
		}
		$yoffset = $Dbaseyoffset;
		$xoffset = $Dbasexoffset;
		$previous = "";
		$previous2 = "";
        $xs = $Dbxspacing + 70;
        $ys = $Dbyspacing + 50;
		// step1 = tree depth calculation
		if ($relationship_controller->pretty) {
           $dmin = 0;
           $dmax = 0;
           $depth = 0;
           if (isset($node["path"])) {
	           foreach($node["path"] as $index=>$pid) {
	              if ($node["relations"][$index] == "father" || $node["relations"][$index] == "mother") {
	                 $depth++;
	                 if ($depth>$dmax) $dmax=$depth;
	                 if ($relationship_controller->asc == 0) $relationship_controller->asc = 1; // the first link is a parent link
	              }
	              if ($node["relations"][$index] == "son" || $node["relations"][$index] == "daughter") {
	                 $depth--;
	                 if ($depth<$dmin) $dmin=$depth;
	                 if ($relationship_controller->asc == 0) $relationship_controller->asc = -1; // the first link is a child link
	              }
	           }
	           $depth=$dmax+$dmin;
			   // need more yoffset before the first box ?
	           if ($relationship_controller->asc == 1) $yoffset -= $dmin * ($Dbheight + $ys);
	           if ($relationship_controller->asc == -1) $yoffset += $dmax * ($Dbheight + $ys);
			}
		}
		$maxxoffset = -1 * $Dbwidth - 20;
		$maxyoffset = $yoffset;
		if ($TEXT_DIRECTION == "rtl") {
			$GM_IMAGES["rarrow"]["other"] = $GM_IMAGES["larrow"]["other"];
		}
		print "<div id=\"relationship_chart";
		if ($TEXT_DIRECTION=="rtl") print "_rtl";
		print "\">\n";
		if (isset($node["path"])) {
			foreach($node["path"] as $index=>$pid) {
			    print "\r\n\r\n<!-- Node ".$index." ".$node["relations"][$index]." ".$pid." -->\r\n";
				$linex = $xoffset;
				$liney = $yoffset;
				$arrow_img = GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"];
				if ($node["relations"][$index] == "father" || $node["relations"][$index] == "mother") {
					$line = $GM_IMAGES["vline"]["other"];
					$liney += $Dbheight;
					$linex += $Dbwidth/2;
					$lh = 54;
					$lw = 3;
					if ($relationship_controller->pretty) {
	                   if ($relationship_controller->asc == 0) $relationship_controller->asc = 1;
	                   if ($relationship_controller->asc == -1) $arrow_img = GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"];
					   $lh = $ys;
	                   $linex = $xoffset + $Dbwidth / 2;
	                   // put the box up or down ?
	                   $yoffset += $relationship_controller->asc * ($Dbheight + $lh);
	                   if ($relationship_controller->asc == 1) $liney = $yoffset - $lh; 
	                   else $liney = $yoffset+$Dbheight;
	                   // need to draw a joining line ?
	                   if ($previous == "child" && $previous2 != "parent") {
	                      $joinh = 3;
	                      $joinw = $xs / 2 + 2;
	                      $xoffset += $Dbwidth + $xs;
	                      $linex = $xoffset - $xs / 2;
	                      if ($relationship_controller->asc == -1) $liney = $yoffset + $Dbheight; 
	                      else $liney = $yoffset - $lh;
	                      $joinx = $xoffset - $xs;
	                      $joiny = $liney - 2 - ($relationship_controller->asc - 1) / 2 * $lh;
	                      print "<div id=\"joina".$index."\" style=\"position:absolute; ".($TEXT_DIRECTION=="ltr"?"left":"right").":".($joinx + $Dbxspacing)."px; top:".($joiny + $Dbyspacing)."px; z-index:".(count($node["path"]) - $index)."; \" align=\"center\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
	                      $joinw = $xs / 2 + 2;
	                      $joinx = $joinx + $xs / 2;
	                      $joiny = $joiny + $relationship_controller->asc * $lh;
	                      print "<div id=\"joinb".$index."\" style=\"position:absolute; ".($TEXT_DIRECTION == "ltr"?"left":"right").":".($joinx + $Dbxspacing)."px; top:".($joiny + $Dbyspacing)."px; z-index:".(count($node["path"]) - $index)."; \" align=\"center\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
	                   }
	                   $previous2 = $previous;
	                   $previous = "parent";
	                }
					else $yoffset += $Dbheight + $Dbyspacing + 50;
			    }
				if ($node["relations"][$index] == "brother" || $node["relations"][$index] == "sister" || $node["relations"][$index] == "sibling") {
					$arrow_img = GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"];
					$xoffset += $Dbwidth + $Dbxspacing + 70;
					$line = $GM_IMAGES["hline"]["other"];
					$linex += $Dbwidth;
					$liney += $Dbheight / 2;
					$lh = 3;
					$lw = 70;
					if ($relationship_controller->pretty) {
					   $lw = $xs;
	                   $linex = $xoffset - $lw;
	                   $liney = $yoffset + $Dbheight / 4;
	                   $previous2 = $previous;;
					   $previous = "";
					}
				}
				if ($node["relations"][$index] == "husband" || $node["relations"][$index] == "wife") {
					$arrow_img = GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"];
					$xoffset += $Dbwidth + $Dbxspacing + 70;
					$line = $GM_IMAGES["hline"]["other"];
					$linex += $Dbwidth;
					$liney += $Dbheight / 2;
					$lh = 3;
					$lw = 70;
					if ($relationship_controller->pretty) {
					   $lw = $xs;
	                   $linex = $xoffset - $lw;
	                   $liney = $yoffset + $Dbheight / 4;
	                   $previous2 = $previous;
					   $previous = "";
					}
				}
				if ($node["relations"][$index] == "son" || $node["relations"][$index] == "daughter" || $node["relations"][$index] == "child") {
					$line = $GM_IMAGES["vline"]["other"];
					$liney += $Dbheight;
					$linex += $Dbwidth / 2;
					$lh = 54;
					$lw = 3;
					if ($relationship_controller->pretty) {
				       if ($relationship_controller->asc == 0) $relationship_controller->asc = -1;
	                   if ($relationship_controller->asc == 1) $arrow_img = GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"];
					   $lh = $ys;
	                   $linex = $xoffset + $Dbwidth / 2;
	                   // put the box up or down ?
	                   $yoffset -= $relationship_controller->asc * ($Dbheight + $lh);
	                   if ($relationship_controller->asc == -1) $liney = $yoffset - $lh; 
	                   else $liney = $yoffset + $Dbheight;
	                   // need to draw a joining line ?
	                   if ($previous == "parent" && $previous2 != "child") {
	                      $joinh = 3;
	                      $joinw = $xs / 2 + 2;
	                      $xoffset += $Dbwidth + $xs;
	                      $linex = $xoffset-$xs / 2;
	                      if ($relationship_controller->asc == 1) $liney=$yoffset+$Dbheight; 
	                      else $liney = $yoffset - ($lh + $Dbyspacing);
	                      $joinx = $xoffset - $xs;
	                      $joiny = $liney - 2 + ($relationship_controller->asc + 1) / 2 * $lh;
	                      print "<div id=\"joina".$index."\" style=\"position:absolute; ".($TEXT_DIRECTION == "ltr"?"left":"right").":".($joinx + $Dbxspacing)."px; top:".($joiny + $Dbyspacing)."px; z-index:".(count($node["path"]) - $index)."; \" align=\"center\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
	                      $joinw = $xs / 2 + 2;
	                      $joinx = $joinx + $xs / 2;
	                      $joiny = $joiny - $relationship_controller->asc * $lh;
	                      print "<div id=\"joinb".$index."\" style=\"position:absolute; ".($TEXT_DIRECTION == "ltr"?"left":"right").":".($joinx + $Dbxspacing)."px; top:".($joiny + $Dbyspacing)."px; z-index:".(count($node["path"]) - $index)."; \" align=\"center\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
	                   }
	                   $previous2 = $previous;;
	                   $previous = "child";
	                }
					else $yoffset += $Dbheight + $Dbyspacing + 50;
				}
				if ($yoffset > $maxyoffset) $maxyoffset = $yoffset;
				$plinex = $linex;
				$pxoffset = $xoffset;
				if ($index > 0) {
					if ($TEXT_DIRECTION == "rtl" && $line != $GM_IMAGES["hline"]["other"]) {
						print "<div id=\"line".$index."\" dir=\"ltr\" style=\"background:none; position:absolute; right:".($plinex + $Dbxspacing)."px; top:".($liney + $Dbyspacing)."px; width:".($lw + $lh * 2)."px; z-index:".(count($node["path"]) - $index)."; \" align=\"right\">";
						print "<img src=\"".GM_IMAGE_DIR."/".$line."\" align=\"right\" width=\"".$lw."\" height=\"".$lh."\" alt=\"\" />\n";
						print "<br />";
						print constant("GM_LANG_".$node["relations"][$index])."\n";
						print "<img src=\"".$arrow_img."\" border=\"0\" align=\"middle\" alt=\"\" />\n";
					}
					else {
						print "<div id=\"line".$index."\" style=\"background:none;  position:absolute; ".($TEXT_DIRECTION == "ltr"?"left":"right").":".($plinex + $Dbxspacing)."px; top:".($liney + $Dbyspacing)."px; width:".($lw + $lh * 2)."px; z-index:".(count($node["path"]) - $index)."; \" align=\"".($lh==3?"center":"left")."\"><img src=\"".GM_IMAGE_DIR."/".$line."\" align=\"left\" width=\"".$lw."\" height=\"".$lh."\" alt=\"\" />\n";
						print "<br />";
						print "<img src=\"".$arrow_img."\" border=\"0\" align=\"middle\" alt=\"\" />\n";
						if ($lh == 3) print "<br />"; // note: $lh==3 means horiz arrow
						print constant("GM_LANG_".$node["relations"][$index])."\n";
					}
					print "</div>\n";
				}
				print "<div id=\"box".$pid.".1.0\" style=\"position:absolute; ".($TEXT_DIRECTION == "ltr" ? "left" : "right").":".$pxoffset."px; top:".$yoffset."px; width:".$Dbwidth."px; height:".$Dbheight."px; z-index:".(count($node["path"]) - $index)."; \"><table><tr><td colspan=\"2\" width=\"".$Dbwidth."\" height=\"".$Dbheight."\">";
				$person =& Person::GetInstance($pid);
				PersonFunctions::PrintPedigreePerson($person, 1, ($relationship_controller->view != "preview"));
				print "</td></tr></table></div>\n";
			}
		}
		print "</div>\n";
	}
}

$maxyoffset += 100;
?>
<script language="JavaScript" type="text/javascript">
<!--
	relationship_chart_div = document.getElementById("relationship_chart");
	if (!relationship_chart_div) relationship_chart_div = document.getElementById("relationship_chart_rtl");
	if (relationship_chart_div) {
		relationship_chart_div.style.height = <?php print $maxyoffset; ?> + "px";
		relationship_chart_div.style.width = "100%";
	}
//-->
</script>
<?php
PrintFooter();
?>
