<?php
/**
 * Calculates the relationship between two individuals in the gedcom
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
 * This Page Is Valid XHTML 1.0 Transitional! > 20 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: relationship.php,v 1.3 2006/03/08 20:46:01 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the language files
*/
require($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY . $factsfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $factsfile[$LANGUAGE];

if (!isset($show_full)) $show_full=$PEDIGREE_FULL_DETAILS;
if (!isset($path_to_find)){
	$path_to_find = 0;
	$pretty = 1;
	unset($_SESSION["relationships"]);
}
if ($path_to_find == -1){
	$path_to_find = 0;
	unset($_SESSION["relationships"]);
}

//-- previously these variables were set in theme.php, now they are no longer required to be set there
$Dbasexoffset = 0;
$Dbaseyoffset = 0;

if ($show_full==false) {
	$Dbheight=25;
	$Dbwidth-=40;
}

$bwidth = $Dbwidth;
$bheight = $Dbheight;

$title_string = "";
if (!isset($pid1)) $pid1 = "";
if (!isset($pid2)) $pid2 = "";
if (!isset($followspouse)) $followspouse = 0;
if (!isset($pretty)) $pretty = 0;
if (!isset($asc)) $asc=1;
if ($asc=="") $asc=1;
if (empty($pid1)) {
	$followspouse = 1;
	$pretty = 1;
}
$check_node = true;
$disp = true;

//-- cleanup user input
$pid1 = clean_input($pid1);
$pid2 = clean_input($pid2);

$title_string .= $gm_lang["relationship_chart"];
// -- print html header information
print_header($title_string);
if (!empty($pid1)) {
	if (preg_match("/[A-Za-z]+/", $pid1)==0) $pid1 = $GEDCOM_ID_PREFIX.$pid1;
	//-- check if the id is valid
	$indirec = find_person_record($pid1);
	if (empty($indirec)) $pid1 = "";
	if ((!displayDetailsByID($pid1))&&(!showLivingNameByID($pid1))) $title_string .= ": ".$gm_lang["private"];
	else $title_string .= ":<br />".get_person_name($pid1);
	if (!empty($_SESSION["pid1"]) && ($_SESSION["pid1"]!=$pid1)) {
		unset($_SESSION["relationships"]);
		$path_to_find=0;
	}
}
if (!empty($pid2)) {
	if (preg_match("/[A-Za-z]+/", $pid2)==0) $pid2 = $GEDCOM_ID_PREFIX.$pid2;
	//-- check if the id is valid
	$indirec = find_person_record($pid2);
	if (empty($indirec)) $pid2 = "";
	if ((!displayDetailsByID($pid2))&&(!showLivingNameByID($pid2))) $title_string .= " - " . $gm_lang["private"]." ";
	else $title_string .= " ".$gm_lang["and"]." ".get_person_name($pid2)." ";
	if (!empty($_SESSION["pid2"]) && ($_SESSION["pid2"]!=$pid2)) {
		unset($_SESSION["relationships"]);
		$path_to_find=0;
	}
}
//	print_help_link("relationship_help", "page_help");
?>
<script language="JavaScript" type="text/javascript">
var pastefield;
function paste_id(value) {
	pastefield.value=value;
}
</script>
<?php
print "<div style=\"position: relative; z-index: 1; width:98%;\">\n";
print "<table class=\"list_table $TEXT_DIRECTION\" style=\"width:100%;\"><tr><td valign=\"top\">";
print "\n\t<h2>".PrintReady($title_string)."</h2>";
print "</td><td>";
// -- print the form to change the number of displayed generations
if ($view!="preview") {
	$Dbaseyoffset += 70;
	print "\n\t<form name=\"people\" method=\"get\" action=\"relationship.php\">\n";
	print "<input type=\"hidden\" name=\"path_to_find\" value=\"$path_to_find\" />\n";

	print "\n\t\t<table class=\"list_table $TEXT_DIRECTION\" align=\"";
	if ($TEXT_DIRECTION == "ltr") print "right";
	else print "left";
	print "\">";

	// Relationship header
	print "<tr><td colspan=\"2\" class=\"topbottombar center\">";
	print $gm_lang["relationship_chart"]."</td>";

	// Empty space
	print "<td>&nbsp;</td>";

	// Options header
	print "<td colspan=\"2\" class=\"topbottombar center\">";
	print $gm_lang["options"]."</td></tr>";

	// Person 1
	print "<tr><td class=\"shade2\">";
	print_help_link("relationship_id_help", "qm");
	print $gm_lang["person1"]."</td>";
	print "<td class=\"shade1 vmiddle\">";
	print "<input tabindex=\"1\" class=\"pedigree_form\" type=\"text\" name=\"pid1\" id=\"pid1\" size=\"3\" value=\"$pid1\" />";
	print_findindi_link("pid1","");
        print "</td>";

	// Empty space
	print "<td></td>";

	// Show details
	print "<td class=\"shade2\">";
	print_help_link("show_full_help", "qm");
	print $gm_lang["show_details"];
	print "</td>";
	print "<td class=\"shade1 vmiddle\">";
	print "<input type=\"hidden\" name=\"show_full\" value=\"$show_full\" />";
	if (!$pretty && $asc==-1) print "<input type=\"hidden\" name=\"asc\" value=\"$asc\" />";
	print "<input tabindex=\"3\" type=\"checkbox\" name=\"showfull\" value=\"0\"";
	if ($show_full) print " checked=\"checked\"";
	print " onclick=\"document.people.show_full.value='".(!$show_full)."';\" />";
	print "</td></tr>";

	// Person 2
	print "<tr><td class=\"shade2\">";
	print_help_link("relationship_id_help", "qm");
	print $gm_lang["person2"]."</td>\n";
	print "<td class=\"shade1 vmiddle\">";
	print "<input tabindex=\"2\" class=\"pedigree_form\" type=\"text\" name=\"pid2\" id=\"pid2\" size=\"3\" value=\"$pid2\" />";
        print_findindi_link("pid2","");
        print "</td>";

	// Empty space
	print "<td>&nbsp;</td>";

	// Line up generations
	print "<td class=\"shade2\">";
	print_help_link("line_up_generations_help", "qm");
	print $gm_lang["line_up_generations"]."</td>";
	print "<td class=\"shade1\">";
	print "<input tabindex=\"5\" type=\"checkbox\" name=\"pretty\" value=\"2\"";
	if ($pretty) print " checked=\"checked\"";
	print " onclick=\"expand_layer('oldtop1'); expand_layer('oldtop2');\" /></td></tr>";

	// Empty line
	print "<tr><td class=\"shade2\">&nbsp;</td>";
	print "<td class=\"shade1\">&nbsp;</td>";

	// Empty space
	print "<td>&nbsp;</td>";

	// Show oldest top
	print "<td class=\"shade2\">";
	print "<div id=\"oldtop1\" style=\"display: ";
	if ($pretty) print "block\">";
	else print "none\">";
	print_help_link("oldest_top_help", "qm");
	print $gm_lang["oldest_top"];
	print "</div>";
	print "</td><td class=\"shade1\">";
	print "<div id=\"oldtop2\" style=\"display: ";
	if ($pretty) print "block\">";
	else print "none\">";
	print "<input tabindex=\"4\" type=\"checkbox\" name=\"asc\" value=\"-1\" ";
	if ($asc==-1) print " checked=\"checked\"";
	print " />";
	print "</div></td></tr>";

	// Show path
	print "<tr><td class=\"shade2\">";
	$pass = false;
	if ((isset($_SESSION["relationships"]))&&((!empty($pid1))&&(!empty($pid2)))) {
		$pass = true;
		$i=0;
		$new_path=true;
		if (isset($_SESSION["relationships"][$path_to_find])) $node = $_SESSION["relationships"][$path_to_find];
		else $node = get_relationship($pid1, $pid2, $followspouse, 0, true, $path_to_find);
		if (!$node){
			$path_to_find--;
			$check_node=$node;
		}
		foreach($_SESSION["relationships"] as $indexval => $node) {
			if ($i==0) print $gm_lang["show_path"].": </td><td class=\"list_value\" style=\"padding: 3px;\">";
			if ($i>0) print " | ";
			if ($i==$path_to_find){
				print "<span class=\"error\" style=\"valign: middle\">".($i+1)."</span>";
				$new_path=false;
			}
			else {
				print "<a href=\"relationship.php?pid1=$pid1&amp;pid2=$pid2&amp;path_to_find=$i&amp;followspouse=$followspouse&amp;pretty=$pretty&amp;show_full=$show_full&amp;asc=$asc\">".($i+1)."</a>\n";
			}
			$i++;
		}
		if (($new_path)&&($path_to_find<$i+1)&&($check_node)) print " | <span class=\"error\">".($i+1)."</span>";
		print "</td>";
	}
	else {
		if ((!empty($pid1))&&(!empty($pid2))) {
			if ((!displayDetailsByID($pid1))&&(!showLivingNameByID($pid1))) $disp = false;
			else if ((!displayDetailsByID($pid2))&&(!showLivingNameByID($pid2))) $disp = false;
			if ($disp) {
				print $gm_lang["show_path"].": </td>";
				print "\n\t\t<td class=\"shade1\">";
				print " <span class=\"error vmmiddle\">";
				$check_node = get_relationship($pid1, $pid2, $followspouse, 0, true, $path_to_find);
				print ($check_node?"1":"&nbsp;".$gm_lang["no_results"])."</span></td>";
				$prt = true;
			}
		}
		if (!isset($prt)) print "&nbsp;</td><td class=\"shade1\">&nbsp;</td>";
	}

	// Empty space
	print "<td></td>";

	// Check relationships by marriage
	print "<td class=\"shade2\">";
	print_help_link("follow_spouse_help", "qm");
	print $gm_lang["follow_spouse"];
	print "</td>";
	print "<td class=\"shade1\">";
	print "<input tabindex=\"6\" type=\"checkbox\" name=\"followspouse\" value=\"1\"";
	if ($followspouse) print " checked=\"checked\"";
	print " onclick=\"document.people.path_to_find.value='-1';\" /></td>";

	if ((!empty($pid1))&&(!empty($pid2))&&($disp)){
		print "</tr><tr>";
		if (($disp)&&(!$check_node)) print "<td class=\"wrap vmiddle center\" colspan=\"2\"><span class=\"error\">".(isset($_SESSION["relationships"])?$gm_lang["no_link_found"]:"")."</span></td>";
		else print "<td class=\"vmiddle center\" colspan=\"2\"><input type=\"submit\" value=\"".$gm_lang["next_path"]."\" onclick=\"document.people.path_to_find.value='".($path_to_find+1)."';\" /></td>\n";
		$pass = true;
	}

	if ($pass == false) print "</tr><tr><td colspan=\"2\" class=\"center wrap\">&nbsp;</td>";

	// Empty space
	print "<td></td>";

	// View button
	print "<td class=\"vmiddle center\" colspan=\"2\">";
	print "<input tabindex=\"7\" type=\"submit\" value=\"".$gm_lang["view"]."\" />";
	print "</td></tr>";


	print "</table></form>";
}
else {
	$Dbaseyoffset=55;
	$Dbasexoffset=10;
}
print "</td></tr></table>";
print "</div>\n";

$maxyoffset = $Dbaseyoffset;
if ((!empty($pid1))&&(!empty($pid2))) {
	if (!$disp) print_privacy_error($CONTACT_EMAIL);
	else {
		if (isset($_SESSION["relationships"][$path_to_find])) $node = $_SESSION["relationships"][$path_to_find];
		else $node = get_relationship($pid1, $pid2, $followspouse, 0, true, $path_to_find);
		if ($node!==false) {
			$_SESSION["pid1"] = $pid1;
			$_SESSION["pid2"] = $pid2;
			if (!isset($_SESSION["relationships"])) $_SESSION["relationships"] = array();
			$_SESSION["relationships"][$path_to_find] = $node;
			$yoffset = $Dbaseyoffset;
			$xoffset = $Dbasexoffset;
			$previous="";
			$previous2="";
            $xs = $Dbxspacing+70;
            $ys = $Dbyspacing+50;
			// step1 = tree depth calculation
			if ($pretty) {
               $dmin=0;
               $dmax=0;
               $depth=0;
               foreach($node["path"] as $index=>$pid) {
                  if (($node["relations"][$index]=="father")||($node["relations"][$index]=="mother")) {
                     $depth++;
                     if ($depth>$dmax) $dmax=$depth;
                     if ($asc==0) $asc=1; // the first link is a parent link
                  }
                  if ($node["relations"][$index]=="child") {
                     $depth--;
                     if ($depth<$dmin) $dmin=$depth;
                     if ($asc==0) $asc=-1; // the first link is a child link
                  }
               }
               $depth=$dmax+$dmin;
			   // need more yoffset before the first box ?
               if ($asc==1) $yoffset -= $dmin*($Dbheight+$ys);
               if ($asc==-1) $yoffset += $dmax*($Dbheight+$ys);
			}

			$maxxoffset = -1*$Dbwidth-20;
			$maxyoffset = $yoffset;
			if ($TEXT_DIRECTION=="rtl") {
				$GM_IMAGES["rarrow"]["other"] = $GM_IMAGES["larrow"]["other"];
			}
			print "<div id=\"relationship_chart";
			if ($TEXT_DIRECTION=="rtl") print "_rtl";
			print "\">\n";
			foreach($node["path"] as $index=>$pid) {
			    print "\r\n\r\n<!-- Node $index -->\r\n";
				$linex = $xoffset;
				$liney = $yoffset;
				$mfstyle = "NN";
				$indirec = find_person_record($pid);
				if (preg_match("/1 SEX F/", $indirec, $smatch)>0) $mfstyle="F";
				if (preg_match("/1 SEX M/", $indirec, $smatch)>0) $mfstyle="";
				$arrow_img = $GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"];
				if (($node["relations"][$index]=="father")||($node["relations"][$index]=="mother")) {
					$line = $GM_IMAGES["vline"]["other"];
					$liney += $Dbheight;
					$linex += $Dbwidth/2;
					$lh = 54;
					$lw = 3;
					if ($pretty) {
                       if ($asc==0) $asc=1;
                       if ($asc==-1) $arrow_img = $GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"];
					   $lh=$ys;
                       $linex=$xoffset+$Dbwidth/2;
                       // put the box up or down ?
                       $yoffset += $asc*($Dbheight+$lh);
                       if ($asc==1) $liney = $yoffset-$lh; else $liney = $yoffset+$Dbheight;
                       // need to draw a joining line ?
                       if ($previous=="child" and $previous2!="parent") {
                          $joinh = 3;
                          $joinw = $xs/2+2;
                          $xoffset += $Dbwidth+$xs;
                          $linex = $xoffset-$xs/2;
                          if ($asc==-1) $liney=$yoffset+$Dbheight; else $liney=$yoffset-$lh;
                          $joinx = $xoffset-$xs;
                          $joiny = $liney-2-($asc-1)/2*$lh;
                          print "<div id=\"joina$index\" style=\"position:absolute; ".($TEXT_DIRECTION=="ltr"?"left":"right").":".($joinx+$Dbxspacing)."px; top:".($joiny+$Dbyspacing)."px; z-index:".(count($node["path"])-$index)."; \" align=\"center\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
                          $joinw = $xs/2+2;
                          $joinx = $joinx+$xs/2;
                          $joiny = $joiny+$asc*$lh;
                          print "<div id=\"joinb$index\" style=\"position:absolute; ".($TEXT_DIRECTION=="ltr"?"left":"right").":".($joinx+$Dbxspacing)."px; top:".($joiny+$Dbyspacing)."px; z-index:".(count($node["path"])-$index)."; \" align=\"center\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
                       }
                       $previous2=$previous;;
                       $previous="parent";
                    }
					else $yoffset += $Dbheight+$Dbyspacing+50;
			    }
				if ($node["relations"][$index]=="sibling") {
					$arrow_img = $GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"];
					if ($mfstyle=="F") $node["relations"][$index]="sister";
					if ($mfstyle=="") $node["relations"][$index]="brother";
					$xoffset += $Dbwidth+$Dbxspacing+70;
					$line = $GM_IMAGES["hline"]["other"];
					$linex += $Dbwidth;
					$liney += $Dbheight/2;
					$lh = 3;
					$lw = 70;
					if ($pretty) {
					   $lw = $xs;
                       $linex = $xoffset-$lw;
                       $liney = $yoffset+$Dbheight/4;
                       $previous2=$previous;;
					   $previous="";
					}
				}
				if ($node["relations"][$index]=="spouse") {
					$arrow_img = $GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"];
					if ($mfstyle=="F") $node["relations"][$index]="wife";
					if ($mfstyle=="") $node["relations"][$index]="husband";
					$xoffset += $Dbwidth+$Dbxspacing+70;
					$line = $GM_IMAGES["hline"]["other"];
					$linex += $Dbwidth;
					$liney += $Dbheight/2;
					$lh = 3;
					$lw = 70;
					if ($pretty) {
					   $lw = $xs;
                       $linex = $xoffset-$lw;
                       $liney = $yoffset+$Dbheight/4;
                       $previous2=$previous;;
					   $previous="";
					}
				}
				if ($node["relations"][$index]=="child") {
					if ($mfstyle=="F") $node["relations"][$index]="daughter";
					if ($mfstyle=="") $node["relations"][$index]="son";
					$line = $GM_IMAGES["vline"]["other"];
					$liney += $Dbheight;
					$linex += $Dbwidth/2;
					$lh = 54;
					$lw = 3;
					if ($pretty) {
				       if ($asc==0) $asc=-1;
                       if ($asc==1) $arrow_img = $GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"];
					   $lh=$ys;
                       $linex = $xoffset+$Dbwidth/2;
                       // put the box up or down ?
                       $yoffset -= $asc*($Dbheight+$lh);
                       if ($asc==-1) $liney = $yoffset-$lh; else $liney = $yoffset+$Dbheight;
                       // need to draw a joining line ?
                       if ($previous=="parent" and $previous2!="child") {
                          $joinh = 3;
                          $joinw = $xs/2+2;
                          $xoffset += $Dbwidth+$xs;
                          $linex = $xoffset-$xs/2;
                          if ($asc==1) $liney=$yoffset+$Dbheight; else $liney=$yoffset-($lh+$Dbyspacing);
                          $joinx = $xoffset-$xs;
                          $joiny = $liney-2+($asc+1)/2*$lh;
                          print "<div id=\"joina$index\" style=\"position:absolute; ".($TEXT_DIRECTION=="ltr"?"left":"right").":".($joinx+$Dbxspacing)."px; top:".($joiny+$Dbyspacing)."px; z-index:".(count($node["path"])-$index)."; \" align=\"center\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
                          $joinw = $xs/2+2;
                          $joinx = $joinx+$xs/2;
                          $joiny = $joiny-$asc*$lh;
                          print "<div id=\"joinb$index\" style=\"position:absolute; ".($TEXT_DIRECTION=="ltr"?"left":"right").":".($joinx+$Dbxspacing)."px; top:".($joiny+$Dbyspacing)."px; z-index:".(count($node["path"])-$index)."; \" align=\"center\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" align=\"left\" width=\"".$joinw."\" height=\"".$joinh."\" alt=\"\" /></div>\n";
                       }
                       $previous2=$previous;;
                       $previous="child";
                    }
					else $yoffset += $Dbheight+$Dbyspacing+50;
				}
				if ($yoffset > $maxyoffset) $maxyoffset = $yoffset;
				$plinex = $linex;
				$pxoffset = $xoffset;
				if ($index>0) {
					if ($TEXT_DIRECTION=="rtl" && $line!=$GM_IMAGES["hline"]["other"]) {
						print "<div id=\"line$index\" dir=\"ltr\" style=\"background:none; position:absolute; right:".($plinex+$Dbxspacing)."px; top:".($liney+$Dbyspacing)."px; width:".($lw+$lh*2)."px; z-index:".(count($node["path"])-$index)."; \" align=\"right\">";
						print "<img src=\"$GM_IMAGE_DIR/$line\" align=\"right\" width=\"$lw\" height=\"$lh\" alt=\"\" />\n";
						print "<br />";
						print $gm_lang[$node["relations"][$index]]."\n";
						print "<img src=\"$arrow_img\" border=\"0\" align=\"middle\" alt=\"\" />\n";
					}
					else {
						print "<div id=\"line$index\" style=\"background:none;  position:absolute; ".($TEXT_DIRECTION=="ltr"?"left":"right").":".($plinex+$Dbxspacing)."px; top:".($liney+$Dbyspacing)."px; width:".($lw+$lh*2)."px; z-index:".(count($node["path"])-$index)."; \" align=\"".($lh==3?"center":"left")."\"><img src=\"$GM_IMAGE_DIR/$line\" align=\"left\" width=\"$lw\" height=\"$lh\" alt=\"\" />\n";
						print "<br />";
						print "<img src=\"$arrow_img\" border=\"0\" align=\"middle\" alt=\"\" />\n";
						if ($lh == 3) print "<br />"; // note: $lh==3 means horiz arrow
						print $gm_lang[$node["relations"][$index]]."\n";
					}
					print "</div>\n";
				}
				print "<div id=\"box$pid.1.0\" style=\"position:absolute; ".($TEXT_DIRECTION=="ltr"?"left":"right").":".$pxoffset."px; top:".$yoffset."px; width:".$Dbwidth."px; height:".$Dbheight."px; z-index:".(count($node["path"])-$index)."; \"><table><tr><td colspan=\"2\" width=\"$Dbwidth\" height=\"$Dbheight\">";
				print_pedigree_person($pid, 1, ($view!="preview"));
				print "</td></tr></table></div>\n";
			}
			print "</div>\n";
		}
	}
}

$maxyoffset += 100;
?>
<script language="JavaScript" type="text/javascript">
	relationship_chart_div = document.getElementById("relationship_chart");
	if (!relationship_chart_div) relationship_chart_div = document.getElementById("relationship_chart_rtl");
	if (relationship_chart_div) {
		relationship_chart_div.style.height = <?php print $maxyoffset; ?> + "px";
		relationship_chart_div.style.width = "100%";
	}
</script>
<?php
print_footer();

?>