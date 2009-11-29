<?
/**
 * Function for printing objects
 *
 * Various printing functions used by all scripts and included by the functions.php file.
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
 * @subpackage Display
 * @version $Id$
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class PersonFunctions {
	/**
	 * Print the information for an individual chart box
	 *
	 * Find and print a given individuals information for a pedigree chart
	 *
	 * @param string $pid	the Gedcom Xref ID of the   to print
	 * @param int $style	the style to print the box in, 1 for smaller boxes, 2 for larger boxes
	 * @param boolean $show_famlink	set to true to show the icons for the popup links and the zoomboxes
	 * @param int $count	on some charts it is important to keep a count of how many boxes were printed
	 * @param string $view	number of generations as parameter for links to various charts
	 * @param string $chart_style	style of chart as parameter for links to various charts
	 */
	public function PrintPedigreePerson($person, $style=1, $show_famlink=true, $count=0, $personcount="1", $view="", $num_gens=0, $chart_style=1) {
		// Global settings
		global $SCRIPT_NAME, $GEDCOMID;
		global $TEXT_DIRECTION;
		global $GM_IMAGES;
		global $gm_lang, $gm_user;
		
		// Theme dependent settings, must be kept global
		global $bwidth, $bheight;
		
		// Settings for pedigree, descendancy, ancestry etc.
		global $show_full, $box_width;
		
		static $personcount;
		if(!isset($personcount)) $personcount = 0;
		
		$personcount++;

		if (is_object($person) && $person->show_changes && $gm_user->UserCanEdit()) $canshow = true;
		else $canshow = false;
	
		if ($num_gens == 0) $num_gens = GedcomConfig::$DEFAULT_PEDIGREE_GENERATIONS;
		if (!isset($show_full)) $show_full = GedcomConfig::$PEDIGREE_FULL_DETAILS;
	
		if ($TEXT_DIRECTION == "ltr") {
			$ldir = "left";
			$rdir = "right";
		}
		else {
			$ldir = "left";
			$rdir = "right";
		}
			
		// NOTE: Start div out-rand()
		if (!is_object($person) || $person->isempty) {
			print "\n\t\t\t<div id=\"out-".rand()."\" class=\"person_boxNN\" style=\"width: ".$bwidth."px; height: ".$bheight."px; padding: 2px; overflow: hidden;\">";
			print "<br />";
			print "\n\t\t\t</div>";
			return false;
		}
		
		// NOTE: Set the width of the box
		$lbwidth = $bwidth*.75;
		if ($lbwidth < 150) $lbwidth = 150;
		
		$isF = "NN";
		if ($person->sex == "F") $isF = "F";
		elseif ($person->sex == "M") $isF = "";
		
		$random = rand();
		if ($person->disp_name) {
			if ($show_famlink && $view == "") {
				// NOTE: Go ahead if we can show the popup box for the links to other pages and family members
				if (GedcomConfig::$LINK_ICONS!="disabled") {
					// NOTE: draw a popup box for the links to other pages and family members
					// NOTE: Start div I.$pid.$personcount.$count.links
					// NOTE: ie_popup_width is needed to set the width of the popup box in IE for the gedcom favorites
					print "\n\t\t<div id=\"I".$person->xref.".".$personcount.".".$count.".".$random."links\" class=\"wrap ie_popup_width person_box$isF details1\" style=\"position:absolute; height:auto; ";
					print "visibility:hidden;\" onmouseover=\"keepbox('".$person->xref.".".$personcount.".".$count.".".$random."'); return false;\" ";
					print "onmouseout=\"moveout('".$person->xref.".".$personcount.".".$count.".".$random."'); return false;\">";
					// This div is filled by an AJAX call! Not yet as placement is a problem!
					// NOTE: Zoom
					print "<a href=\"pedigree.php?rootid=".$person->xref."&amp;num_generations=".$num_gens."&amp;talloffset=".$chart_style."&amp;gedid=".$person->gedcomid."\"><b>".$gm_lang["index_header"]."</b></a>\n";
					print "<br /><a href=\"descendancy.php?rootid=".$person->xref."&amp;show_full=$show_full&amp;num_generations=".$num_gens."&amp;box_width=$box_width&amp;gedid=".$person->gedcomid."\"><b>".$gm_lang["descend_chart"]."</b></a><br />\n";
					if ($gm_user->username != "") {
						if (!empty($gm_user->gedcomid[$GEDCOMID])) {
							print "<a href=\"relationship.php?pid1=".$gm_user->gedcomid[$GEDCOMID]."&amp;pid2=".$person->xref."&amp;gedid=".$person->gedcomid."\"><b>".$gm_lang["relationship_to_me"]."</b></a><br />\n";
						}
					}
					// NOTE: Zoom
					if (file_exists("ancestry.php")) print "<a href=\"ancestry.php?rootid=".$person->xref."&amp;chart_style=$chart_style&amp;num_generations=".$num_gens."&amp;box_width=$box_width&amp;gedid=".$person->gedcomid."\"><b>".$gm_lang["ancestry_chart"]."</b></a><br />\n";
					if (file_exists("fanchart.php") and defined("IMG_ARC_PIE") and function_exists("imagettftext"))  print "<a href=\"fanchart.php?rootid=".$person->xref."&amp;PEDIGREE_GENERATIONS=".$num_gens."&amp;gedid=".$person->gedcomid."\"><b>".$gm_lang["fan_chart"]."</b></a><br />\n";
					if (file_exists("hourglass.php")) print "<a href=\"hourglass.php?pid=".$person->xref."&amp;chart_style=$chart_style&amp;PEDIGREE_GENERATIONS=".$num_gens."&amp;box_width=$box_width&amp;gedid=".$person->gedcomid."\"><b>".$gm_lang["hourglass_chart"]."</b></a><br />\n";
					foreach ($person->spousefamilies as $skey => $sfam) {
						if (is_object($sfam)) {
							if ($person->xref == $sfam->husb_id) $spouse = "wife";
							else $spouse = "husb";
							if (is_object($sfam->$spouse) || $sfam->children_count > 0) {
								print "<a href=\"family.php?famid=".$sfam->xref."&amp;gedid=".$sfam->gedcomid."\"><b>".$gm_lang["fam_spouse"]."</b></a><br /> \n";
								if (is_object($sfam->$spouse)) {
									print "<a href=\"individual.php?pid=".$sfam->$spouse->xref."&amp;gedid=".$sfam->$spouse->gedcomid."\">";
									print $sfam->$spouse->name;
									print "</a><br />\n";
								}
							}
							foreach ($sfam->children as $ckey => $child) {
								print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"individual.php?pid=".$child->xref."&amp;gedid=".$child->gedcomid."\">";
								print $child->name;
								print "</a><br />";
							}
						}
					}
					// NOTE: Close div I.$pid.$personcount.$count.links
					print "</div>";
				}
			}
		}
		// NOTE: Draw the inner box that shows the person details
		// NOTE: Start div out-$pid.$personcount.$count
		print "\n\t\t\t<div id=\"out-".$person->xref.".".$personcount.".".$count.".".$random."\"";
		if ($style==1) {
			print " class=\"person_box$isF\" style=\"width: ".$bwidth."px; height: ".$bheight."px; padding: 2px; overflow: hidden;\"";
		}
		else {
			print " style=\"padding: 2px;\"";
		}
					
		// NOTE: If box zooming is allowed and no person details are shown
		// NOTE: determine what mouse behavior to add
		if (GedcomConfig::$ZOOM_BOXES != "disabled" && !$show_full && $person->disp_name) {
			if (GedcomConfig::$ZOOM_BOXES == "mouseover") print " onmouseover=\"expandbox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."'); return false;\" onmouseout=\"restorebox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."'); return false;\"";
			elseif (GedcomConfig::$ZOOM_BOXES == "mousedown") print " onmousedown=\"expandbox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."');\" onmouseup=\"restorebox('".$person->xref.".".$personcount.".".$count."', ".$style.", '".$random."');\"";
			elseif (GedcomConfig::$ZOOM_BOXES == "click") print " onclick=\"expandbox('".$person->xref.".".$personcount.".".$count."', '".$style."', '".$random."');\"";
		}
		print ">\n";
		// NOTE: Show the persons primary picture if possible, but only in large boxes ($show_full)
		if (GedcomConfig::$SHOW_HIGHLIGHT_IMAGES && $person->disp && PrivacyFunctions::showFact("OBJE", $person->xref, "INDI") && $show_full) {
			$object = $person->highlightedimage;
			// NOTE: Print the pedigree tumbnail
			if (!empty($object["thumb"])) {
				$media =& MediaItem::GetInstance($object["id"], "", $person->gedcomid);
				// NOTE: IMG ID
				$class = "pedigree_image_portrait";
				if ($media->fileobj->f_width > $media->fileobj->f_height) $class = "pedigree_image_landscape";
				if($TEXT_DIRECTION == "rtl") $class .= "_rtl";
				// NOTE: IMG ID
				print "<div class=\"$class\" style=\"float: left; border: none;\">";
				print "<img id=\"box-".$person->xref.".".$personcount.".".$count."-thumb\" src=\"".$media->fileobj->f_thumb_file."\" vspace=\"0\" hspace=\"0\" class=\"$class\" alt =\"\" title=\"\" ";
				$has_thumb = true;
				if (!$show_full) print " style=\"display: none;\"";
				print " /></div>\n";
			}
		}
				
		// NOTE: Start the person details div. Adjust the print width to the purpose and filling
		if (!$show_full) print "<div class=\"person_details width100\">";
		else if (isset($has_thumb)) print "<div class=\"person_details width60\">";
		else print "<div class=\"person_details width80\">";
			//-- check if the person is visible
			if (!$person->disp) {
				if ($person->disp_name) {
					// NOTE: Start span namedef-$personcount.$pid.$count
					print "<a href=\"individual.php?pid=".$person->xref."&amp;gedid=".$person->gedcomid."\"><span id=\"namedef-".$person->xref.".".$personcount.".".$count.".".$random."\" ";
					if (hasRTLText($person->name) && $style=="1")
					print "class=\"name2\">";
					else print "class=\"name$style\">";
					print PrintReady($person->name);
					// NOTE: IMG ID
					print "<img id=\"box-".$person->xref.".".$personcount.".".$count.".".$random."-sex\" src=\"".GM_IMAGE_DIR."/";
					if ($isF=="") print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
					else  if ($isF=="F")print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
					else  print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
					print "\" class=\"sex_image\" />";
					if (GedcomConfig::$SHOW_ID_NUMBERS) {
						print "</span><span class=\"details$style\">";
						print $person->addxref;
						// NOTE: Close span namedef-$personcount.$pid.$count
						print "</span>";
					}
					if ($person->addname != "") {
						print "<br />";
						// NOTE: Start span addnamedef-$personcount.$pid.$count
						// NOTE: Close span addnamedef-$personcount.$pid.$count
						if (hasRTLText($person->addname) && $style=="1") print "<span id=\"addnamedef-".$person->xref.".".$personcount.".".$count.".".$random."\" class=\"name2\"> ";
						else print "<span id=\"addnamedef-".$person->xref.".".$personcount.".".$count."\" class=\"name$style\"> ";
						print $person->addname."</span><br />";
					}
					print "</a>";
				}
				else {
					$user =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
					print "<a href=\"javascript: ".$gm_lang["private"]."\" onclick=\"if (confirm('".preg_replace("'<br />'", " ", $gm_lang["privacy_error"])."\\n\\n".str_replace("#user[fullname]#", $user->firstname." ".$user->lastname, $gm_lang["clicking_ok"])."')) ";
					if (GedcomConfig::$CONTACT_METHOD!="none") {
						if (GedcomConfig::$CONTACT_METHOD=="mailto") print "window.location = 'mailto:".$user->email."'; ";
						else print "message('".GedcomConfig::$CONTACT_EMAIL."', '".GedcomConfig::$CONTACT_METHOD."'); ";
					}
					// NOTE: Start span namedef-$pid.$personcount.$count
					// NOTE: Close span namedef-$pid.$personcount.$count
					print "return false;\"><span id=\"namedef-".$person->xref.".".$personcount.".".$count.".".$random."\" class=\"name$style\">".$gm_lang["private"]."</span></a>\n";
				}
				if ($show_full) {
					// NOTE: Start span fontdef-$pid.$personcount.$count
					// NOTE: Close span fontdef-$pid.$personcount.$count
					print "<br /><span id=\"fontdef-".$person->xref.".".$personcount.".".$count.".".$random."\" class=\"details$style\">";
					print $gm_lang["private"];
					print "</span>";
				}
				// NOTE: Close div out-$pid.$personcount.$count
				print "\n\t\t\t</div>"; // do not comment out!
				// NOTE: Why is this return here? Behaviour: if only names are displayed, all zoom boxes are hidden
	//			print "</div>";
	//			return;
			}
			else {
				// Added this else to show zoomboxes even if details are hidden. Privacy should handle the contents
				print "<a href=\"individual.php?pid=".$person->xref."&amp;gedid=".$person->gedcomid."\"";
				if (!$show_full) {
					//not needed or wanted for mouseover //if (GedcomConfig::$ZOOM_BOXES=="mouseover") print " onmouseover=\"event.cancelBubble = true;\"";
					if (GedcomConfig::$ZOOM_BOXES=="mousedown") print " onmousedown=\"event.cancelBubble = true;\"";
					if (GedcomConfig::$ZOOM_BOXES=="click") print " onclick=\"event.cancelBubble = true;\"";
				}
				// NOTE: Start span namedef-$pid.$personcount.$count
				if (hasRTLText($person->name) && $style=="1") print "><span id=\"namedef-".$person->xref.".".$personcount.".".$count.".".$random."\" class=\"name2";
				else print "><span id=\"namedef-".$person->xref.".".$personcount.".".$count.".".$random."\" class=\"name$style";
				// NOTE: Add optional CSS style for each fact
	//			$cssfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","CAST","DSCR","EDUC","IDNO","NATI","NCHI","NMR","OCCU","PROP","RELI","RESI","SSN","TITL","BAPL","CONL","ENDL","SLGC","_MILI");
	//			foreach($cssfacts as $indexval => $fact) {
	//				$ct = preg_match_all("/1 $fact/", $indirec, $nmatch, PREG_SET_ORDER);
	//				if ($ct>0) print "&nbsp;".$fact;
	//			}
				print "\">";
				print $person->name;
				// NOTE: Close span namedef-$pid.$personcount.$count
				print "</span>";
				print "<span class=\"name$style\">";
				// NOTE: IMG ID
				print "<img id=\"box-".$person->xref.".".$personcount.".".$count.".".$random."-sex\" src=\"".GM_IMAGE_DIR."/";
				if ($isF=="") print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
				else  if ($isF=="F")print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
				else  print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
				print "\" class=\"sex_image\" />";
				print "</span>\r\n";
				if (GedcomConfig::$SHOW_ID_NUMBERS) {
					print "<span class=\"details$style\">";
					print $person->addxref;
					print "</span>";
				}
				if (GedcomConfig::$SHOW_LDS_AT_GLANCE) print "<span class=\"details$style\">".GetLdsGlance($person->gedrec)."</span>";
				if ($person->addname != "") {
					print "<br />";
					if (hasRTLText($person->addname) && $style=="1")
					print "<span id=\"addnamedef-".$person->xref.".".$count.".".$random."\" class=\"name2\"> ";
					else print "<span id=\"addnamedef-".$person->xref.".".$count.".".$random."\" class=\"name$style\"> ";
					print $person->addname."</span><br />";
				}
				print "</a>";
			
				// NOTE: Start div inout-$pid.$personcount.$count
				if (!$show_full) print "\n<div id=\"inout-".$person->xref.".".$personcount.".".$count.".".$random."\" style=\"display: none;\">\n";
				// NOTE: Start div fontdev-$pid.$personcount.$count
				print "<div id=\"fontdef-".$person->xref.".".$personcount.".".$count.".".$random."\" class=\"details$style\">";
				// NOTE: Start div inout2-$pid.$personcount.$count
				if ($show_full) print "\n<div id=\"inout2-".$person->xref.".".$personcount.".".$count.".".$random."\" style=\"display: block;\">\n";
				
				//-- section to display tags in the boxes
				// First get the optional tags and check if they exist
				$tagstoprint = array();
				if (!empty(GedcomConfig::$CHART_BOX_TAGS)) {
					$opt_tags = preg_split("/[, ]+/", GedcomConfig::$CHART_BOX_TAGS);
					foreach ($opt_tags as $key => $tag) {
						if (strpos($person->gedrec, "\n1 ".$tag)) {
							$tagstoprint[] = $tag;
							break;
						}
					}
				}
				// Then add the fixed tags
				// First the birth related tags
				foreach (array("BIRT", "CHR", "BAPM") as $key => $tag) {
					if (strpos($person->gedrec, "\n1 ".$tag)) {
						$tagstoprint[] = $tag;
						break;
					}
				}
				// Then add the death related tags
				foreach (array("DEAT", "CREM", "BURI") as $key => $tag) {
					if (strpos($person->gedrec, "\n1 ".$tag)) {
						$tagstoprint[] = $tag;
						break;
					}
				}
				// Remove double tags
				$tagstoprint = array_flip(array_flip($tagstoprint));

				// Get the subrecords and sort them
				$factobjs = $person->SelectFacts($tagstoprint);	
				foreach($factobjs as $key => $factobj) {
					FactFunctions::PrintSimpleFact($factobj, true, false);
				}			
				// NOTE: Close div inout2-$pid.$personcount.$count
				if ($show_full) print "</div>\n";
				
				// NOTE: Find all level 1 sub records
				// The content is printed with AJAX calls
				// NOTE: Open div inout-$pid.$personcount.$count
				if ($show_full) {
					print "\n<div id=\"inout-".$person->xref.".".$personcount.".".$count.".".$random."\" style=\"display: none;\">";
					print "</div>\n";
				}
				// NOTE: Close div fontdev-$pid.$personcount.$count
				print "</div>\n";
				// NOTE: Close div inout-$pid.$personcount.$count
				if (!$show_full) print "</div>";
				// Close div person details
				print "</div>";
			}
					
			// NOTE: links and zoom icons
			// NOTE: Start div icons-$personcount.$pid.$count
			print "<div id=\"icons-".$person->xref.".".$personcount.".".$count.".".$random."\"  class=\"width10\" style=\"float:";
			if ($TEXT_DIRECTION == "rtl") print "left"; else print "right";
			print "; text-align: ";
			if ($TEXT_DIRECTION == "rtl") print "left"; else print "right";
			print ";";
			if ($show_full) print " display: block;";
			else print " display: none;";
			print "\">";
				// NOTE: If box zooming is allowed and person details are shown
				// NOTE: determine what mouse behavior to add
				// NOTE: Zoom icon
				if (GedcomConfig::$ZOOM_BOXES != "disabled" && $show_full && !$view && ($person->disp_name)) {
					print "<a href=\"javascript: ".$gm_lang["zoom_box"]."\"";
					if (GedcomConfig::$ZOOM_BOXES=="mouseover") print " onmouseover=\"expandbox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."'); if(document.getElementById('inout-".$person->xref.".".$personcount.".".$count.".".$random."').innerHTML=='') sndReq('inout-".$person->xref.".".$personcount.".".$count.".".$random."', 'getzoomfacts', 'pid', '".$person->xref."', 'gedcomid', '".$person->gedcomid."', 'canshow', '".$canshow."', 'view', '".$view."');\" onmouseout=\"restorebox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."');\" onclick=\"return false;\"";
					if (GedcomConfig::$ZOOM_BOXES=="mousedown") print " onmousedown=\"expandbox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."'); if(document.getElementById('inout-".$person->xref.".".$personcount.".".$count.".".$random."').innerHTML=='') sndReq('inout-".$person->xref.".".$personcount.".".$count.".".$random."', 'getzoomfacts', 'pid', '".$person->xref."', 'gedcomid', '".$person->gedcomid."', 'canshow', '".$canshow."', 'view', '".$view."');\" onmouseup=\"restorebox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."');\" onclick=\"return false;\"";
					if (GedcomConfig::$ZOOM_BOXES=="click") print " onclick=\"expandbox('".$person->xref.".".$personcount.".".$count."', $style, '".$random."'); if(document.getElementById('inout-".$person->xref.".".$personcount.".".$count.".".$random."').innerHTML=='') sndReq('inout-".$person->xref.".".$personcount.".".$count.".".$random."', 'getzoomfacts', 'pid', '".$person->xref."', 'gedcomid', '".$person->gedcomid."', 'canshow', '".$canshow."', 'view', '".$view."'); return false;\"";
					print "><img id=\"iconz-".$person->xref.".".$personcount.".".$count."\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["zoomin"]["other"]."\" width=\"25\" height=\"25\" border=\"0\" alt=\"".$gm_lang["zoom_box"]."\" title=\"".$gm_lang["zoom_box"]."\" /></a>";
				}
				// NOTE: Popup box icon (don't show if the person is private)
				if (GedcomConfig::$LINK_ICONS!="disabled" && $show_famlink && !$view && ($person->disp_name)) {
					$click_link="#";
					if (preg_match("/pedigree.php/", $SCRIPT_NAME)>0) $click_link="pedigree.php?rootid=".$person->xref."&amp;num_generations=".$num_gens."&amp;talloffset=".$chart_style."&amp;gedid=".$person->gedcomid;
					if (preg_match("/hourglass.php/", $SCRIPT_NAME)>0) $click_link="hourglass.php?pid=".$person->xref."&amp;generations=".$num_gens."&amp;box_width=$box_width&amp;gedid=".$person->gedcomid;
					if (preg_match("/ancestry.php/", $SCRIPT_NAME)>0) $click_link="ancestry.php?rootid=".$person->xref."&amp;chart_style=$chart_style&amp;num_generations=".$num_gens."&amp;box_width=$box_width&amp;gedid=".$person->gedcomid;
					if (preg_match("/descendancy.php/", $SCRIPT_NAME)>0) $click_link="descendancy.php?rootid=".$person->xref."&amp;show_full=$show_full&amp;num_generations=".$num_gens."&amp;box_width=$box_width&amp;gedid=".$person->gedcomid;
					if ((preg_match("/family.php/", $SCRIPT_NAME)>0)&&!empty($famid)) $click_link="family.php?famid=".$sfam->xref."&amp;gedid=".$sfam->gedcomid;
					if (preg_match("/individual.php/", $SCRIPT_NAME)>0) $click_link="individual.php?pid=".$person->xref."&amp;gedid=".$person->gedcomid;
					print "<br /><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["pedigree"]["small"]."\" width=\"25\" border=\"0\" vspace=\"0\" hspace=\"0\" alt=\"".$gm_lang["person_links"]."\" title=\"".$gm_lang["person_links"]."\"";
					if (GedcomConfig::$LINK_ICONS=="mouseover") print " onmouseover";
					if (GedcomConfig::$LINK_ICONS=="click") print " onclick";
					print "=\"";
					print "showbox(this, '".$person->xref.".".$personcount.".".$count.".".$random."', '";
					if ($style==1) print "box".$person->xref;
					else print "relatives";
					print "');";
					print " return false;\" ";
					// NOTE: Removed so IE will keep showing the box
					// NOTE: Keep it here in case we might need it
					print "onmouseout=\"moveout('".$person->xref.".".$personcount.".".$count.".".$random."');";
					print " return false;\"";
					if (($click_link=="#")&&(GedcomConfig::$LINK_ICONS!="click")) print " onclick=\"return false;\"";
					print " />";
				}
			// NOTE: Close div icons-$personcount.$pid.$count
			print "</div>\n";
		print "<br style=\"clear:both;\" />";
		print "</div>\n";
		
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
	public function PrintFamilyParents(&$family, $sosa = 0, $label="", $parid="", $gparid="", $view="") {
		global $gm_lang, $show_full;
		global $TEXT_DIRECTION;
		global $pbwidth, $pbheight;
		global $GM_IMAGES;

		print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td rowspan=\"2\" style=\"vertical-align:middle;\">";
		print "<span class=\"subheaders\">" . GetSosaName($sosa*2) . "</span>";
		print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
		if ($parid) {
			if ($family->husb_id == $parid) ChartFunctions::PrintSosaNumber($label);
			else ChartFunctions::PrintSosaNumber(str_repeat("&nbsp; ", strlen($label)-1));
		}
		else if ($sosa > 0) ChartFunctions::PrintSosaNumber($sosa * 2);
	
		$husb = "husb";
		if ($family->husb_status == "") $style = "";
		elseif ($family->husb_status == "deleted") {
			$style = " class=\"facts_valuered\"";
			$husb = "husbold";
		}
		elseif ($family->husb_status == "new" || $family->husb_status == "changed") $style = " class=\"facts_valueblue\""; 
		print "\n\t<td style=\"vertical-align:middle;\"".$style.">";
		// Fix for overloading error of array object
		$husband = $family->$husb;
		self::PrintPedigreePerson($husband, 1, true, 1, $family->view);
		print "</td></tr></table>";
		print "</td>\n";
		
		// husband's parents
		$fath = "";
		$moth = "";
		$hfam = "";
		if (is_object($family->$husb)) {
			$hfam = $family->$husb->primaryfamily;
			if ($hfam != "") {
				$fath = $family->$husb->childfamilies[$hfam]->husb;
				$moth = $family->$husb->childfamilies[$hfam]->wife;
			}
		}
		$upfamid = $hfam;
		if ($hfam != "" || ($sosa != 0 && GedcomConfig::$SHOW_EMPTY_BOXES)) {
			print "<td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" width=\"3\" height=\"" . ($pbheight) . "\" alt=\"\" /></td>";
			print "<td style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
	//		if (is_object($fath) or ($sosa != 0 and GedcomConfig::$SHOW_EMPTY_BOXES)) {
				// husband's father
				print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
				if ($sosa > 0) ChartFunctions::PrintSosaNumber($sosa * 4);
				if (is_object($fath) && $fath->xref == $gparid) ChartFunctions::PrintSosaNumber(trim(substr($label,0,-3),".").".");
				print "\n\t<td style=\"vertical-align:middle;\">";
				self::PrintPedigreePerson($fath, 1, true, 1, $family->view);
				print "</td></tr></table>";
	//		}
			print "</td>";
	//	}
		if (!empty($upfamid) and ($sosa!=-1) and ($view != "preview")) {
			print "<td style=\"vertical-align:middle;\" rowspan=\"2\">";
			
			ChartFunctions::PrintUrlArrow($upfamid, ($sosa==0 ? "?famid=".$upfamid."&amp;show_full=".$show_full : "#".$upfamid), PrintReady($gm_lang["start_at_parents"]."&nbsp;-&nbsp;".htmlspecialchars($family->$husb->childfamilies[$hfam]->sortable_name)), 1);
			print "</td>\n";
		}
	//	if ($hfam != "" || ($sosa != 0 &&  GedcomConfig::$SHOW_EMPTY_BOXES)) {
			// husband's mother
			print "</tr><tr><td style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
			print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
			if ($sosa > 0) ChartFunctions::PrintSosaNumber($sosa * 4 + 1);
			if (is_object($moth) && $moth->xref == $gparid) ChartFunctions::PrintSosaNumber(trim(substr($label,0,-3),".").".");
			print "\n\t<td style=\"vertical-align:middle;\">";
			self::PrintPedigreePerson($moth, 1, true, 1, $family->view);
			print "</td></tr></table>";
			print "</td>\n";
		}
		print "</tr></table>\n\n";
		if ($sosa!=0) {
			print "<a href=\"family.php?famid=".$family->xref."\" class=\"details1\">";
			print $family->addxref;
			if ($family->addxref != "") print "&nbsp;&nbsp;";
			FactFunctions::PrintSimpleFact($family->marr_fact, false, false); 
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
			if ($family->wife->xref == $parid) ChartFunctions::PrintSosaNumber($label);
			else ChartFunctions::PrintSosaNumber(str_repeat("&nbsp; ", strlen($label)-1));
		}
		else if ($sosa > 0) ChartFunctions::PrintSosaNumber($sosa * 2 + 1);
		
		$wife = "wife";
		if ($family->wife_status == "") $style = "";
		elseif ($family->wife_status == "deleted") {
			$style = " class=\"facts_valuered\"";
			$wife = "wifeold";
		}
		elseif ($family->wife_status == "new" || $family->wife_status == "changed") $style = " class=\"facts_valueblue\""; 
		print "\n\t<td style=\"vertical-align:middle;\"".$style.">";
		// Fix for overloading error of array object
		$wwife = $family->$wife;
		self::PrintPedigreePerson($wwife, 1, true, 1, $family->view);
		print "</td></tr></table>";
		print "</td>\n";
		
		// wife's parents
		$fath = "";
		$moth = "";
		$wfam = "";
		if (is_object($family->$wife)) {
			$wfam = $family->$wife->primaryfamily;
			if ($wfam != "") {
				$fath = $family->$wife->childfamilies[$wfam]->husb;
				$moth = $family->$wife->childfamilies[$wfam]->wife;
			}
		}
		$upfamid = $wfam;
		if ($wfam != "" || ($sosa != 0 && GedcomConfig::$SHOW_EMPTY_BOXES)) {
			print "<td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" width=\"3\" height=\"" . ($pbheight) . "\" alt=\"\" /></td>";
			print "<td style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
	//		if (is_object($fath) or ($sosa != 0 and GedcomConfig::$SHOW_EMPTY_BOXES)) {
				// wife's father
				print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\"><tr>";
				if ($sosa > 0) ChartFunctions::PrintSosaNumber($sosa * 4 + 2);
				if (is_object($fath) && $fath->xref == $gparid) ChartFunctions::PrintSosaNumber(trim(substr($label,0,-3),".").".");
				print "\n\t<td style=\"vertical-align:middle;\">";
				self::PrintPedigreePerson($fath, 1, true, 1, $family->view);
				print "</td></tr></table>";
	//		}
			print "</td>\n";
	//	}
		if (!empty($upfamid) and ($sosa!=-1) and ($view != "preview")) {
			print "<td style=\"vertical-align:middle;\" rowspan=\"2\">";
			
			ChartFunctions::PrintUrlArrow($upfamid.$label, ($sosa==0 ? "?famid=$upfamid&amp;show_full=$show_full" : "#$upfamid"), PrintReady($gm_lang["start_at_parents"]."&nbsp;-&nbsp;".htmlspecialchars($family->$wife->childfamilies[$wfam]->sortable_name)), 1);
			print "</td>\n";
		}
	//	if ($wfam != "" || ($sosa != 0 &&  GedcomConfig::$SHOW_EMPTY_BOXES)) {
			// wife's mother
			print "</tr><tr><td style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td>";
			print "\n\t<table style=\"width: " . ($pbwidth) . "px; height: " . $pbheight . "px;\" border=\"0\"><tr>";
			if ($sosa > 0) ChartFunctions::PrintSosaNumber($sosa * 4 + 1);
			if (is_object($moth) && $moth->xref == $gparid) ChartFunctions::PrintSosaNumber(trim(substr($label,0,-3),".").".");
			print "\n\t<td style=\"vertical-align:middle;\">";
			self::PrintPedigreePerson($moth, 1, true, 1, $family->view);
			print "</td></tr></table>";
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
	public function PrintFamilyChildren($family, $childid = "", $sosa = 0, $label="", $view="") {
		global $gm_lang, $pbwidth, $pbheight, $show_famlink, $show_cousins;
		global $GM_IMAGES, $show_changes, $TEXT_DIRECTION, $gm_user;
	
		if ($show_changes && $gm_user->UserCanEdit()) $canshow = true;
		else $canshow = false;
		 
		print "<table border=\"0\" cellpadding=\"0\" cellspacing=\"2\"><tr>";
		if ($sosa>0) print "<td></td>";
		print "<td><span class=\"subheaders\">".$gm_lang["children"]."</span></td>";
		if ($sosa>0) print "<td></td><td></td>";
		print "</tr>\n";
	
		$nchi=1;
		if ($family->children_count > 0) {
			foreach($family->children as $indexval => $chil) {
				print "<tr>\n";
				if ($sosa != 0) {
					if ($chil->xref == $childid) ChartFunctions::PrintSosaNumber($sosa, $childid);
						else if (empty($label)) ChartFunctions::PrintSosaNumber("");
						else ChartFunctions::PrintSosaNumber($label.($nchi++).".");
					}
					$style = "";
					if ($family->show_changes) {
						if ($family->GetChildStatus($chil->xref) == "new") $style = "class=\"change_new\" ";
						elseif ($family->GetChildStatus($chil->xref) == "deleted") $style = "class=\"change_old\" ";
					}
					
					print "<td ".$style."style=\"vertical-align:middle;\" >";
					print GetPediName($chil->famc[$family->xref]["relation"], $chil->sex);
					self::PrintPedigreePerson($chil, 1, true, 1, $chil->view);
					print "</td>";
					// Don't print the children's spouses and children if private
					if ($sosa != 0 && $chil->disp_name) {
						
						// loop for all families where current child is a spouse
						$f = 0;
						foreach($chil->fams as $key => $cfamid) {
							$cfam =& Family::GetInstance($cfamid);
							$f++;
							if ($cfam->husb_id == "" && $cfam->wife_id == "") continue;
							if ($cfam->husb_id == $chil->xref) $spouse = $cfam->wife;
							else $spouse =  $cfam->husb;
							
							// multiple marriages
							if ($f > 1) {
								print "</tr>\n<tr><td>&nbsp;</td>";
								print "<td style=\"vertical-align:middle;\"";
								if ($TEXT_DIRECTION == "rtl") print " align=\"left\">";
								else print " align=\"right\">";
								if ($f == count($chil->spousefamilies)) print "<img height=\"50%\"";
								else print "<img height=\"100%\"";
								print " width=\"3\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" alt=\"\" />";
								print "</td>";
							}
							print "<td class=\"details1\" style=\"vertical-align:middle;\" align=\"center\">";
							
							// marriage date. We only print this if the family can be shown
							if ($cfam->disp && $cfam->marr_date != "") print "<span class=\"date\">".$cfam->marr_fact->datestring."</span>";
							
							// divorce date. We only print this if the family can be shown
							print "<br /><img width=\"100%\" height=\"3\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" />";
							if ($cfam->disp && $cfam->div_date != "") print "-<br /><span class=\"date\">".$cfam->div_fact->datestring."</span>";
							
							// family link
							print "<br />";
							print "<a class=\"details1\" href=\"family.php?famid=".$cfam->xref."\">";
							print $cfam->addxref;
							print "</a>";
							print "</td>\n";
							
							// spouse information
							print "<td style=\"vertical-align: middle;";
							if ($cfam->div_date != "" && $view != "preview") print " filter:alpha(opacity=40);-moz-opacity:0.4\">";
							else print "\">";
							PersonFunctions::PrintPedigreePerson($spouse, 1, $show_famlink, 9);
							print "</td>\n";
							// cousins
							if ($show_cousins) {
								ChartFunctions::PrintCousins($cfam);
							}
						}
					}
					print "</tr>\n";
			}
	   }
	   else if ($sosa<1) {
				print "<tr><td></td>";
				print "<td valign=\"top\"><span class=\"label\">" . $gm_lang["no_children"] . "</span></td></tr>";
	   }
	   else {
		   	print "<tr>\n";
		   	ChartFunctions::PrintSosaNumber($sosa, $childid);
		   	print "<td style=\"vertical-align:middle;\">";
		   	$child =& Person::GetInstance($childid);
		   	PersonFunctions::PrintPedigreePerson($child, 1, $show_famlink, 0);
		   	print "</td></tr>\n";
	   	}
		print "</table><br />";
	}
	
	/**
	 * print first major fact for an Individual
	 *
	 * @param string $key	indi pid
	 */
	public function PrintFirstMajorFact($person, $prt=true, $break=false) {
		global $gm_lang;
		
		$majorfacts = array("BIRT", "CHR", "BAPM", "DEAT", "BURI", "BAPL", "ADOP");
		$retstr = "";
		$foundfact = "";
		$facts = $person->SelectFacts($majorfacts);
		foreach ($facts as $key => $factobj) {
			if (strlen($factobj->factrec) > 7 && $factobj->disp) {
				if ($break) $retstr .= "<br />";
				else $retstr .= " -- ";
				$retstr .= "<i>";
				if (isset($gm_lang[$factobj->fact])) $retstr .= $gm_lang[$factobj->fact];
				else if (defined("GM_FACT_".$factobj->fact)) $retstr .= constant("GM_FACT_".$factobj->fact);
				else $retstr .= $factobj->fact;
				$retstr .= "&nbsp;";
				$retstr .= $factobj->PrintFactDate(false, false, false, false, false);
				$retstr .= $factobj->PrintFactPlace(false, false, false, false);
				$retstr .= "</i>";
				$foundfact = $factobj->fact;
				break;
			}
		}
		if ($prt) {
			print $retstr;
			return $foundfact;
		}
		else return addslashes($retstr);
	}
	
	public function GetChangeNames($person) {
		global $changes, $gm_lang, $GEDCOMID, $show_changes, $gm_user;
		
		$name = array();
		if ($show_changes && $gm_user->UserCanEditOwn($person->xref)) $onlyold = false;
		else $onlyold = true;
	
		if($person->isempty) return $name;
		
		$newindi = false;
		// First see if the indi exists or is new
		$indirec = $person->gedrec;
		$fromchange = false;
		if (empty($indirec) && !$onlyold) {
			$newindi = true;
			// And see if it's a new indi
			if ($person->isnew) {
				$indirec = $person->changedgedrec;
				$fromchange = true;
			}
		}
		// Check if the indi is flagged for delete
		$deleted = false;
		if (!$onlyold && $person->isdeleted) $deleted = true;
	
		if (empty($indirec)) return false;
		$result = "aa";
		$num = 1;
		while($result != "") {
			$result = GetSubrecord(1, "1 NAME", $indirec, $num);
			if (!empty($result)) {
				if ($deleted) $resultnew = "";
				else $resultnew = $result;
				if ($fromchange) $name[] = array("old"=>"", "new"=>$resultnew);
				else $name[] = array("old"=>$result, "new"=>$resultnew);
			}
			$num++;
		}
		if ($deleted) return $name;
		
		// we have the original names, now we get all additions and changes TODO: DELETE
		if (!$onlyold && GetChangeData(true, $person->xref, true)) {
			$sql = "SELECT ch_type, ch_fact, ch_old, ch_new FROM ".TBLPREFIX."changes WHERE ch_gid='".$person->xref."' AND ch_fact='NAME' AND ch_file='".$person->gedcomid."' ORDER BY ch_id";
			$res = NewQuery($sql);
	//		if (!$res) return false;
		
			// Loop through the changes and apply them to the name records
			while ($row = $res->FetchAssoc($res->result)) {
				if ($row["ch_type"] == "add_name") {
					$name[] = array("old"=>"", "new"=>$row["ch_new"]);
				}
				if ($row["ch_type"] == "edit_name") {
					foreach($name as $key => $namerecs) {
						if (trim($namerecs["new"]) == trim($row["ch_old"])) {
							$name[$key]["new"] = $row["ch_new"];
						}
					}
				}
				if ($row["ch_type"] == "delete_name") {
					foreach($name as $key => $namerecs) {
						if (trim($namerecs["new"]) == trim($row["ch_old"])) {
							$name[$key]["new"] = $row["ch_new"];
						}
					}
				}
			}
		}
		return $name;
	}
}
?>