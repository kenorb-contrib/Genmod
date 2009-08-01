<?php
/**
 * Class Function for printing
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
if (strstr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class FactFunctions {

	/**
	 * print a fact record
	 *
	 * prints a fact record designed for the personal facts and details page
	 *
	 * @todo Add counter for number of fact
	 * @param string $factrec	The gedcom subrecord
	 * @param string $pid		The Gedcom Xref ID of the person the fact belongs to (required to check fact privacy)
	 * @param string $fact		The fact to be printed
	 * @param string $indirec	optional INDI record for age calculation at family event
	 */
	public function PrintFact($factobj, $pid, $fact, $count=1, $indirec=false, $styleadd="", $mayedit=true) {
		global $factarray, $show_changes;
		global $nonfacts, $birthyear, $birthmonth, $birthdate;
		global $hebrew_birthyear, $hebrew_birthmonth, $hebrew_birthdate;
		global $BOXFILLCOLOR, $GM_IMAGE_DIR;
		global $gm_lang, $GEDCOM, $gm_username, $Users;
		global $WORD_WRAPPED_NOTES;
		global $TEXT_DIRECTION;
		global $HIDE_GEDCOM_ERRORS, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS;
		global $CONTACT_EMAIL, $view, $FACT_COUNT, $monthtonum;
		global $dHebrew;
		global $n_chil, $n_gchi;
		static $rowcnt;
		static $relacnt;
		
		if (!isset($relacnt)) $relacnt = 0;
		if (!isset($rowcnt)) $rowcnt = 0;
	
		$FACT_COUNT++;
		$estimates = array("abt","aft","bef","est","cir");
	
		// NOTE: Retrieve event for fact
		$ft = preg_match("/1 (\w+)(.*)/", $factobj->factrec, $match);
		if ($ft>0) $event = trim($match[2]);
		else $event="";
		
		// NOTE: This deals with close relatives events
		if (substr($fact,0,2) == "X_") {
			$relafact = true;
			$styleadd = "rela"; // not editable
			// NOTE this is a workaround for generated and real asso records.
			if (array_key_exists(substr($fact,1), $factarray)) $fact = substr($fact, 1);
			else $fact = "EVEN";
		}
		else $relafact = false;
	
		// -- avoid known non facts
		if (in_array($fact, $nonfacts)) return;
		
		if (!$Users->UserCanEdit($gm_username)) {
			//-- do not print empty facts to visitors. Editors may want to change them
			$lines = preg_split("/\n/", trim($factobj->factrec));
			if ((count($lines)<2)&&($event=="")) return;
		}
		
		// See if RESN tag prevents display or edit/delete
		$resn_tag = preg_match("/2 RESN (.*)/", $factobj->factrec, $match);
		if ($resn_tag == "1") $resn_value = strtolower(trim($match[1]));
		if (array_key_exists($fact, $factarray)) {
			// -- handle generic facts
			// Print the label
			if (($fact!="EVEN" && $fact!="FACT" && $fact!="OBJE")) {
				$factref = $fact;
				if ($relafact && !ShowRelaFact($factobj->factrec)) return false;
	//			else if (!showFact($factref, $pid)) return false;
				if ($relafact) $show_fact_details = showRelaFactDetails($factobj->factrec);
				else $show_fact_details = showFactDetails($factref, $pid);
				if ($styleadd != "rela") {
					print "\n\t\t<tr id=\"row_".$rowcnt."\" >";
					$rowcnt++;
				}
				else {
					print "\n\t\t<tr id=\"row_".$styleadd.$relacnt."\" >";
					$relacnt++;
				}
				//print "\n\t\t\t<td class=\"facts_label facts_label$styleadd\">";
				print "\n\t\t\t<td class=\"shade2 $styleadd center width20\" style=\"vertical-align: middle\">";
				print $factarray[$fact];
				if ($fact=="_BIRT_CHIL" and isset($n_chil)) print "<br />".$gm_lang["number_sign"].$n_chil++;
				if ($fact=="_BIRT_GCHI" and isset($n_gchi)) print "<br />".$gm_lang["number_sign"].$n_gchi++;
				if ($Users->userCanEdit($gm_username) && $fact != "CHAN" && $show_fact_details && $styleadd!="change_old" && $view!="preview" && !FactEditRestricted($pid, $factobj->factrec) && $count > 0 && $mayedit) {
					$menu = array();
					$menu["label"] = $gm_lang["edit"];
					$menu["labelpos"] = "right";
					$menu["icon"] = "";
					$menu["link"] = "#";
					$menu["onclick"] = "return edit_record('$pid', '$fact', '$count', 'edit_fact');";
					$menu["class"] = "";
					$menu["hoverclass"] = "";
					$menu["flyout"] = "down";
					$menu["submenuclass"] = "submenu";
					$menu["items"] = array();
					$submenu = array();
					$submenu["label"] = $gm_lang["edit"];
					$submenu["labelpos"] = "right";
					$submenu["icon"] = "";
					$submenu["onclick"] = "return edit_record('$pid', '$fact', '$count', 'edit_fact');";
					$submenu["link"] = "#";
					$submenu["class"] = "submenuitem";
					$submenu["hoverclass"] = "submenuitem_hover";
					$menu["items"][] = $submenu;
					if (!stristr($factobj->factrec, "1 _GM")) {
						$submenu = array();
						$submenu["label"] = $gm_lang["copy"];
						$submenu["labelpos"] = "right";
						$submenu["icon"] = "";
						$submenu["onclick"] = "return copy_record('$pid', '$fact', '$count', 'copy_fact');";
						$submenu["link"] = "#";
						$submenu["class"] = "submenuitem";
						$submenu["hoverclass"] = "submenuitem_hover";
						$menu["items"][] = $submenu;
					}
					$submenu = array();
					$submenu["label"] = $gm_lang["delete"];
					$submenu["labelpos"] = "right";
					$submenu["icon"] = "";
					$submenu["onclick"] = "if (confirm('".$gm_lang["check_delete"]."')) return delete_record('$pid', '$fact', '$count', 'delete_fact'); else return false;";
					$submenu["link"] = "#";
					$submenu["class"] = "submenuitem";
					$submenu["hoverclass"] = "submenuitem_hover";
					$menu["items"][] = $submenu;
					print " <div style=\"width:25px;\" class=\"center\">";
					print_menu($menu);
					print "</div>";
				}
				print "</td>";
			}
			else {
				if ($fact == "OBJE") return false;
	//			if (!showFact("EVEN", $pid)) return false;
				// -- find generic type for each fact
				$ct = preg_match("/2 TYPE (.*)/", $factobj->factrec, $match);
				if ($ct>0) $factref = trim($match[1]);
				else $factref = $fact;
	//			if (!showFact($factref, $pid)) return false;
				$show_fact_details = showFactDetails($factref, $pid) && showFactDetails("EVEN", $pid);
				// The two lines below do not validate because when $styleadd does
				// not have a value, we create several instances of the same ID.
				// Furthermore, "name" is not valid for a TR tag.
				if ($styleadd != "rela") {
					print "\n\t\t<tr id=\"row_".$rowcnt."\" >";
					$rowcnt++;
				}
				else {
					print "\n\t\t<tr id=\"row_".$styleadd.$relacnt."\" >";
					$relacnt++;
				}
				if (isset($factarray["$factref"])) $label = $factarray[$factref];
				else $label = $factref;
				//print "<td class=\"facts_label facts_label$styleadd\">" . $label;
				print "<td class=\"shade2 $styleadd center width20\" style=\"vertical-align: middle\">";
				print $label;
				if ($show_fact_details && $fact != "CHAN" && $Users->userCanEdit($gm_username) && $styleadd!="change_old" && $view!="preview" && !FactEditRestricted($pid, $factobj->factrec) && $count > 0 && $mayedit) {
					$menu = array();
					$menu["label"] = $gm_lang["edit"];
					$menu["labelpos"] = "right";
					$menu["icon"] = "";
					$menu["link"] = "#";
					$menu["onclick"] = "return edit_record('$pid', '$fact', '$count', 'edit_fact');";
					$menu["class"] = "";
					$menu["hoverclass"] = "";
					$menu["flyout"] = "down";
					$menu["submenuclass"] = "submenu";
					$menu["items"] = array();
					$submenu = array();
					$submenu["label"] = $gm_lang["edit"];
					$submenu["labelpos"] = "right";
					$submenu["icon"] = "";
					$submenu["onclick"] = "return edit_record('$pid', '$fact', '$count', 'edit_fact');";
					$submenu["link"] = "#";
					$submenu["class"] = "submenuitem";
					$submenu["hoverclass"] = "submenuitem_hover";
					$menu["items"][] = $submenu;
					if (!stristr($factobj->factrec, "1 _GM")) {
						$submenu = array();
						$submenu["label"] = $gm_lang["copy"];
						$submenu["labelpos"] = "right";
						$submenu["icon"] = "";
						$submenu["onclick"] = "return copy_record('$pid', '$fact', '$count', 'copy_fact');";
						$submenu["link"] = "#";
						$submenu["class"] = "submenuitem";
						$submenu["hoverclass"] = "submenuitem_hover";
						$menu["items"][] = $submenu;
					}
					$submenu = array();
					$submenu["label"] = $gm_lang["delete"];
					$submenu["labelpos"] = "right";
					$submenu["icon"] = "";
					$submenu["onclick"] = "if (confirm('".$gm_lang["check_delete"]."')) return delete_record('$pid', '$fact', '$count', 'delete_fact'); else return false;";
					$submenu["link"] = "#";
					$submenu["class"] = "submenuitem";
					$submenu["hoverclass"] = "submenuitem_hover";
					$menu["items"][] = $submenu;
					print " <div style=\"width:25px;\" class=\"center\">";
					print_menu($menu);
					print "</div>";
				}
				print "</td>";
			}
			// Print the value
			$prted = false;
			print "<td class=\"shade1 $styleadd wrap\">";
	//print "Event: ".$event."<br />Fact: ".$fact."<br />";
			$user = $Users->GetUser($gm_username);
	//		if ((showFactDetails($factref, $pid)) && (!FactViewRestricted($pid, $factobj->factrec))) {
	//		if ($show_fact_details && (!FactViewRestricted($pid, $factobj->factrec))) {
			if ($show_fact_details) {
				// -- first print TYPE for some facts
				if ($fact!="EVEN" && $fact!="FACT") {
					$ct = preg_match("/2 TYPE (.*)/", $factobj->factrec, $match);
					if ($ct>0) {
						$type = trim($match[1]);
						if (isset ($factarray["MARR_".Str2Upper($type)])) print $factarray["MARR_".Str2Upper($type)];
						else if (isset($factarray[$type])) print $factarray[$type];
						else if (isset($gm_lang[$type])) print $gm_lang[$type];
						else print $type;
						print "<br />";
					}
				}
				// -- find date for each fact
				$prted = $factobj->PrintFactDate(true, true, $fact, $pid, $indirec);
				//-- print spouse name for marriage events
				$ct = preg_match("/_GMS @(.*)@/", $factobj->factrec, $match);
				if ($ct>0) {
					$spouse=$match[1];
					if ($spouse!=="") {
						$spouseobj =& Person::GetInstance($spouse);
						 print "<a href=\"individual.php?pid=$spouse&amp;ged=$GEDCOM\">";
						 if ($spouseobj->disp_name) {
	//					 if (showLivingNameById($spouse)) {
	//						$srec = FindGedcomRecord($spouse);
	//						if ($show_changes && $Users->UserCanEdit($gm_username) && GetChangeData(true, $spouse, true)) {
	//							$rec = GetChangeData(false, $spouse, true, "gedlines");
	//							$srec = $rec[$GEDCOM][$spouse];
	//						}
							print $spouseobj->name;
	//						print PrintReady(GetPersonName($spouse, $srec));
	//						$addname = GetAddPersonName($spouse, $srec);
							if ($spouseobj->addname != "") print " - ".$spouseobj->addname;
	//						if ($addname!="") print " - ".PrintReady($addname);
						 }
						 else print $gm_lang["private"];
						 print "</a>";
					}
					$ct = preg_match("/_GMFS @(.*)@/", $factobj->factrec, $match);
					if (($view != "preview") && ($spouse !== "") && ($ct > 0)) {
						print " - ";
						$famid = $match[1];
						print "<a href=\"family.php?famid=$famid\">";
						if ($TEXT_DIRECTION == "ltr") print " &lrm;";
						else print " &rlm;";
						print "[".$gm_lang["view_family"];
						if ($SHOW_FAM_ID_NUMBERS) print " &lrm;($famid)&lrm;";
						if ($TEXT_DIRECTION == "ltr") print "&lrm;]</a>\n";
						else print "&rlm;]</a>\n";
					}
				}
				//-- print other characterizing fact information
				if ($event!="" && $fact!="ASSO") {
					$ct = preg_match("/@(.*)@/", $event, $match);
					if ($ct>0) {
						if ($show_changes && GetChangeData(true, $match[1], true)) {
							$rec = GetChangeData(false, $match[1], true, "gedlines");
							$gedrec = $rec[$GEDCOM][$match[1]];
						}
						else $gedrec = FindGedcomRecord($match[1]);
						if (strstr($gedrec, "INDI")!==false) print "<a href=\"individual.php?pid=$match[1]&amp;ged=$GEDCOM\">".GetPersonName($match[1])."</a><br />";
						else if ($fact=="REPO") $prted = print_repository_record($match[1]);
						else print_submitter_info($match[1]);
					}
					else if ($fact=="ALIA") {
						 //-- strip // from ALIA tag for FTM generated gedcoms
						 print preg_replace("'/'", "", $event)."<br />";
						$prted = true;
					}
					else if (strstr("URL WWW ", $fact." ")) {
						if (!preg_match('/^http/', $event)) $event = "http://".$event;
						print "<a href=\"".$event."\" target=\"new\">".PrintReady($event)."</a>";
						$prted = true;
					}
					else if (strstr("_EMAIL EMAIL", $fact." ")) {
						 print "<a href=\"mailto:".$event."\">".$event."</a>";
						$prted = true;
					}
					else if (strstr("FAX", $fact)) {
						print "&lrm;".$event." &lrm;";
						$prted = true;
					}
					else if ($fact == "RESN") {
						print PrintReady(print_help_link("RESN_help", "qm", "", "", true).$gm_lang[$event]);
					}
					else if (!strstr("PHON ADDR ", $fact." ") && $event!="Y") {
						print PrintReady($event." ");
						$prted = true;
					}
					else if ($fact == "_PRIM" || $fact == "_THUM") {
						if ($event == "Y") print PrintReady($gm_lang["yes"]);
						else print PrintReady($gm_lang["no"]);
						$prted = true;
					}
				}
				// The GetCont must also be done if $event is empty, which is if 1 TEXT has no value.
				$temp = trim(GetCont(2, $factobj->factrec), "\r\n");
				if (strstr("PHON ADDR ", $fact." ")===false && $temp!="") {
					if ($WORD_WRAPPED_NOTES) print " ";
					print PrintReady($temp);
				}
				//-- find description for some facts
				$ct = preg_match("/2 DESC (.*)/", $factobj->factrec, $match);
				if ($ct>0) print PrintReady($match[1]);
				// -- print PLACe, TEMPle and STATus
				$prted  = $factobj->PrintFactPlace(true, true, true) || $prted;
				// -- print BURIal -> CEMEtery
				$ct = preg_match("/2 CEME (.*)/", $factobj->factrec, $match);
				if ($ct>0) {
					if ($prted) print "<br />";
					if (file_exists($GM_IMAGE_DIR."/facts/CEME.gif")) print "<img src=\"".$GM_IMAGE_DIR."/facts/CEME.gif\" alt=\"".$factarray["CEME"]."\" title=\"".$factarray["CEME"]."\" align=\"middle\" /> ";
					print $factarray["CEME"].": ".$match[1]."\n";
				}
				//-- print address structure
				if ($fact!="ADDR" && $fact!="PHON") {
					$prted = print_address_structure($factobj->factrec, 2, $prted) || $prted;
				}
				else {
					$prted = print_address_structure($factobj->factrec, 1, $prted) || $prted;
				}
				// -- Enhanced ASSOciates > RELAtionship
				print_asso_rela_record($pid, $factobj->factrec, true);
	
				// -- find _GMU field
				$ct = preg_match("/2 _GMU (.*)/", $factobj->factrec, $match);
				if ($ct>0) print $factarray["_GMU"].": ".$match[1];
				if ($fact!="ADDR") {
					//-- catch all other facts that could be here
					$special_facts = array("ADDR","ALIA","ASSO","CEME","CONC","CONT","DATE","DESC","EMAIL",
					"FAMC","FAMS","FAX","NOTE","OBJE","PHON","PLAC","RESN","SOUR","STAT","TEMP",
					"TIME","TYPE","WWW","_EMAIL","_GMU", "URL", "AGE");
	
					$ct = preg_match_all("/\n2 (\w+) (.*)/", $factobj->factrec, $match, PREG_SET_ORDER);
					$prtbr = false;
					for($i=0; $i<$ct; $i++) {
						$factref = $match[$i][1];
						if (!in_array($factref, $special_facts)) {
							if ($prtbr || $prted) {
								print "<br />";
								$prtbr = true;
							}
							if (isset($factarray[$factref])) $label = $factarray[$factref];
							else $label = $factref;
							if (file_exists($GM_IMAGE_DIR."/facts/".$factref.".gif")) print "<img src=\"".$GM_IMAGE_DIR."/facts/".$factref.".gif\" alt=\"".$label."\" title=\"".$label."\" align=\"middle\" /> ";
							else print "<span class=\"label\">".$label.": </span>";
							$value = trim($match[$i][2]);
							if (isset($gm_lang[strtolower($value)])) print $gm_lang[strtolower($value)];
							else print PrintReady($value);
	//						print "<br />\n";
							$prted = true;
							if ($fact == "FILE") {
	//							if (!isset($first)) $first = true;
								$filerec = GetSubrecord("2", "2 ".$factref, $factobj->factrec, 1);
								$ctf = preg_match_all("/\n3 (\w+) (.*)/", $filerec, $matchf, PREG_SET_ORDER);
	//							if ($ctf>0 && $first) {
								if ($ctf>0) {
	//								$first = false;
									for($j=0; $j<$ctf; $j++) {
	//									if ($prted) print "<br />";
										if ($prtbr || $prted) {
											print "<br />";
											$prtbr = true;
										}
										$factref = $matchf[$j][1];
										if (isset($factarray[$factref])) $label = $factarray[$factref];
										else $label = $factref;
										if (file_exists($GM_IMAGE_DIR."/facts/".$factref.".gif")) print "<img src=\"".$GM_IMAGE_DIR."/facts/".$factref.".gif\" alt=\"".$label."\" title=\"".$label."\" align=\"middle\" /> ";
										else print "<span class=\"label\">".$label.": </span>";
										$value = trim($matchf[$j][2]);
										if (isset($gm_lang[strtolower($value)])) print $gm_lang[strtolower($value)];
										else print PrintReady($value);
	//									if ($j < $ctf) print "<br />";
										print "\n";
									}
								}
							}
								
						}
					}
				}
				if (preg_match("/ (PLAC)|(STAT)|(TEMP)|(SOUR) /", $factobj->factrec)>0 || (!empty($event) && $fact!="ADDR" && $fact!="ASSO" && $fact!="PHON")) print "<br />\n";
				if ($prted) print "<br />";
				// -- find source for each fact
				if (ShowFact("SOUR", $pid, "SOUR")) $n1 = print_fact_sources($factobj->factrec, 2, $pid, $prted);
				// -- find notes for each fact
				if ($fact != "ASSO" && ShowFact("NOTE", $pid, "NOTE")) $n2 = print_fact_notes($factobj->factrec, 2);
				//-- find multimedia objects
				if (ShowFact("OBJE", $pid, "OBJE")) $n3 = print_media_links($factobj->factrec, 2, $pid, $prted);
				// -- Find RESN tag
				if (isset($resn_value)) {
					if ($n1 ||$n2 || $n3) print "<br />";
					print_help_link("RESN_help", "qm");
					print PrintReady($factarray["RESN"].": ".$gm_lang[$resn_value])."\n";
				}
			}
			if (!$show_fact_details) print $gm_lang["private"];
			print "</td>";
			print "\n\t\t</tr>";
		}
		else {
			// -- catch all unknown codes here
			$body = $gm_lang["unrecognized_code"]." ".$fact;
			if (!$HIDE_GEDCOM_ERRORS) print "\n\t\t<tr><td class=\"shade2 $styleadd\"><span class=\"error\">".$gm_lang["unrecognized_code"].": $fact</span></td><td class=\"shade1\">$event<br />".$gm_lang["unrecognized_code_msg"]." <a href=\"#\" onclick=\"message('$CONTACT_EMAIL','', '', '$body'); return false;\">".$CONTACT_EMAIL."</a>.</td></tr>";
		}
	}
	//------------------- end print fact function
	
	public function PrintMainMedia($factobj, $pid, $nlevel, $count=1, $change=false, $styleadd="", $mayedit=true) {
		global $gm_lang, $factarray, $USE_GREYBOX, $MEDIA_DIRECTORY, $TEXT_DIRECTION;
		static $rowcnt;
		
		if (!isset($rowcnt)) $rowcnt = 0;
		
		$media =& MediaItem::GetInstance($factobj->linkxref);

		if ($media->disp) {
			// NOTE: Determine the size of the mediafile
			$imgwidth = 300;
			$imgheight = 300;
			if (preg_match("'://'", $media->filename)) {
				if ($media->validmedia) {
					$imgwidth = 400;
					$imgheight = 500;
				}
				else {
					$imgwidth = 800;
					$imgheight = 400;
				}
			}
			else if ((preg_match("'://'", $MEDIA_DIRECTORY)>0)||($media->fileobj->f_file_exists)) {
				if ($media->fileobj->f_width > 0 && $media->fileobj->f_height > 0) {
					$imgwidth = $media->fileobj->f_width+50;
					$imgheight = $media->fileobj->f_height + 50;
				}
			}
			
			// NOTE: Start printing the media details
			print "\n\t\t<tr id=\"mrow_".$rowcnt."\">";
			$rowcnt++;
			self::PrintFactTagBox(&$factobj, $count, $styleadd, $mayedit);
	
			// NOTE Print the title of the media
			print "<td class=\"shade1 $styleadd wrap\"><span class=\"field\">";
			if ($factobj->disp) {
				if (preg_match("'://'", $media->fileobj->f_thumb_file)||(preg_match("'://'", $MEDIA_DIRECTORY)>0)||($media->fileobj->f_file_exists)) {
					if ($USE_GREYBOX && $media->fileobj->f_is_image) {
						print "<a href=\"".FilenameEncode($media->fileobj->f_main_file)."\" title=\"".$media->title."\" rel=\"gb_imageset[mainmedia]\">";
					}
					else print "<a href=\"#\" onclick=\"return openImage('".$media->fileobj->f_main_file."', '".$imgwidth."', '".$imgheight."', '".$media->fileobj->f_is_image."');\">";
					print "<img src=\"".$media->fileobj->f_thumb_file."\" border=\"0\" align=\"" . ($TEXT_DIRECTION== "rtl"?"right": "left") . "\" class=\"thumbnail\" alt=\"\" /></a>";
				}
				print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\"><i>".PrintReady($media->title)."</i></a>";
				if (empty($media->fileobj->f_thumb_file) && preg_match("'://'", $media->filename)) print "<br /><a href=\"".$media->filename."\" target=\"_blank\">".$media->filename."</a>";
				// NOTE: Print the format of the media
				if ($media->extension != "") {
					print "\n\t\t\t<br /><span class=\"label\">".$factarray["FORM"].": </span> <span class=\"field\">".$media->extension."</span>";
					if ($media->fileobj->f_width != 0 &&  $media->fileobj->f_height != 0) {
						print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["image_size"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $media->fileobj->f_width . ($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ") . $media->fileobj->f_height . "</span>";
					}
				}
				$ttype = preg_match("/3 TYPE (.*)/", $media->gedrec, $match);
				if ($ttype>0){
					print "\n\t\t\t<br /><span class=\"label\">".$gm_lang["type"].": </span> <span class=\"field\">".$match[1]."</span>";
				}
				// Print PRIM from owner, NOT from the media object
				$ttype = preg_match("/\d _PRIM (\w)/", $factobj->factrec, $match);
				if ($ttype>0){
					if ($match[1] == "Y") $val = "yes";
					else $val = "no";
					print "\n\t\t\t<br /><span class=\"label\">".$factarray["_PRIM"].": </span> <span class=\"field\">".$gm_lang[$val]."</span>";
				}
				
				// Print THUM from from owner, NOT from the media object
				$ttype = preg_match("/\d _THUM (\w)/", $factobj->factrec, $match);
				if ($ttype>0){
					if ($match[1] == "Y") $val = "yes";
					else $val = "no";
					print "\n\t\t\t<br /><span class=\"label\">".$factarray["_THUM"].": </span> <span class=\"field\">".$gm_lang[$val]."</span>";
				}
		
				print "<br />\n";
				// Print the notes in the MM link
				print_fact_notes($factobj->factrec, 2);
			}
			else print $gm_lang["private"];
			print "</span></td></tr>";
		}
	}
	
	public function PrintMainSources($factobj, $pid, $count, $styleadd="", $mayedit=true) {
		 global $gm_lang, $factarray;
		
		 // -- find source for each fact
		// Here we check if we can show the source at all!
		$source =& Source::GetInstance($factobj->linkxref);
		if ($source->disp) {
			print "\n\t\t\t<tr>";
			self::PrintFactTagBox(&$factobj, $count, $styleadd, $mayedit);
			print "\n\t\t\t<td class=\"shade1 wrap $styleadd\"><span class=\"field\">";
			if ($factobj->disp) {
				print "<a href=\"source.php?sid=".$source->xref."\">";
    			print $source->descriptor;
   				//-- Print additional source title
   				if ($source->adddescriptor != "") print " - ".$source->adddescriptor;
				print "</a>";
				$cs = preg_match("/2 PAGE (.*)/", $factobj->factrec, $cmatch);
				if ($cs>0) {
					print "\n\t\t\t<br />".$factarray["PAGE"].": $cmatch[1]";
					$srec = GetSubRecord(2, "2 PAGE", $factobj->factrec);
					$text = GetCont(3, $srec);
					$text = ExpandUrl($text);
					print PrintReady($text);
				}
				$cs = preg_match("/2 EVEN (.*)/", $factobj->factrec, $cmatch);
				if ($cs>0) {
					print "<br /><span class=\"label\">".$factarray["EVEN"]." </span><span class=\"field\">".$cmatch[1]."</span>";
					$cs = preg_match("/3 ROLE (.*)/", $factobj->factrec, $cmatch);
					if ($cs>0) print "\n\t\t\t<br />&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"label\">".$factarray["ROLE"]." </span><span class=\"field\">$cmatch[1]</span>";
				}
				$cs = preg_match("/2 DATA/", $factobj->factrec, $cmatch);
				if ($cs>0) {
//						// Don't print the DATA tag, it doesn't contain data so is obsolete!
//						print "<br /><span class=\"label\">".$factarray["DATA"]." </span>";
					$srec = GetSubRecord(2, "2 DATA", $factobj->factrec);
					$cs = preg_match("/3 DATE (.*)/", $srec, $cmatch);
					if ($cs>0) print "\n\t\t\t<br /><span class=\"label\">".$gm_lang["date"].":  </span><span class=\"field\">".GetChangedDate($cmatch[1])."</span>";
					$i = 1;
					do {
						$trec = GetSubRecord(3, "3 TEXT", $srec, $i);
						if ($trec != "") {
							print "<br /><span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".GetGedcomValue("TEXT", 3, $trec);
							$text = GetCont(4, $trec);
							$text = ExpandUrl($text);
							print $text;
							print "</span>";
						}
						$i++;
					} while ($trec != "");
				}
				$cs = preg_match("/2 QUAY (.*)/", $factobj->factrec, $cmatch);
				if ($cs>0) print "<br /><span class=\"label\">".$factarray["QUAY"]." </span><span class=\"field\">".$cmatch[1]."</span>";
				$i = 1;
				do {
					$trec = GetSubRecord(2, "2 TEXT", $factobj->factrec, $i);
					print $trec;
					if ($trec != "") {
						print "<br /><span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".GetGedcomValue("TEXT", 2, $trec);
						$text = GetCont(3, $trec);
						$text = ExpandUrl($text);
						print $text;
						print "</span>";
					}
					$i++;
				} while ($trec != "");
				// See if RESN tag prevents display or edit/delete
				// -- Find RESN tag
				print "<br />";
				self::PrintResn(&$factobj);
				print_media_links($factobj->factrec, 2);
				print_fact_notes($factobj->factrec, 2);
			}
			else print $gm_lang["private"];
			print "</span></td></tr>";
		}
	}
	
	/**
	 * print main note row
	 *
	 * this function will print a table row for a fact table for a level 1 note in the main record
	 * @param string $factrec	the raw gedcom sub record for this note
	 * @param int $level		The start level for this note, usually 1
	 * @param string $pid		The gedcom XREF id for the level 0 record that this note is a part of
	 * @param int $count		The number of the level 1 fact.
	 */
	public function PrintMainNotes($factobj, $level, $pid, $count, $styleadd="", $mayedit=true) {

		 if ($factobj->linktype == "Note") $note =& Note::GetInstance($factobj->linkxref);
		 if ($factobj->linktype != "Note" || $note->disp) {
			print "\n\t\t\t<tr>";
			self::PrintFactTagBox(&$factobj, $count, $styleadd, $mayedit);
			print "\n<td class=\"shade1 $styleadd wrap\">";
			if ($factobj->disp) {
				if ($factobj->linktype == "") {
					$text = "";
					$nt = preg_match("/1 NOTE (.*)/", $factobj->factrec, $n1match);
					if ($nt>0) $text = preg_replace("/~~/", "<br />", $n1match[1]);
					$text .= GetCont(1, $factobj->factrec);
					$text = ExpandUrl($text);
					print PrintReady($text);
				}
				else {
					//-- print linked note records
				   	print "<a href=\"note.php?oid=".$note->xref."&amp;gedid=".$note->gedcomid."\">".$note->text."</a>";
					print_fact_sources($note->gedrec, 2);
				}
				// See if RESN tag prevents display or edit/delete
				// -- Find RESN tag
				print "<br />\n";
				if (print_fact_sources($factobj->factrec, 2)) print "<br />";
				self::PrintResn(&$factobj);
			}
			print "</td></tr>";
		}
	}

	private function PrintResn($factobj) {
		global $factarray, $gm_lang;
		
		if ($factobj->resnvalue != "") {
			print_help_link("RESN_help", "qm");
			print PrintReady($factarray["RESN"].": ".$gm_lang[$factobj->resnvalue])."\n";
		}
	}
			
	private function PrintFactTagBox($factobj, $count, $styleadd, $mayedit) {
		 global $GM_IMAGE_DIR, $GM_IMAGES, $gm_lang, $factarray;
		
		print "<td class=\"shade2 $styleadd center width20\" style=\"vertical-align: middle;\">";
		if ($factobj->fact == "SOUR") {
			print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["large"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />";
			print $gm_lang["source"];
			$edit_actions = array("edit_sour", "edit_sour", "copy_sour", "delete_sour");
		}
		else if ($factobj->fact == "NOTE") {
			print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />";
			print $gm_lang["note"];
			$edit_actions = array("edit_note", "edit_note", "copy_note", "delete_note");
		}
		else if ($factobj->fact == "OBJE") {
			print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["large"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />";
			print $factarray["OBJE"];
			$edit_actions = array("edit_media_link", "edit_media_link", "copy_media", "delete_media");
		}
		if ($factobj->canedit && $styleadd!="change_old" && !$factobj->owner->view && $mayedit) {
			$menu = array();
			$menu["label"] = $gm_lang["edit"];
			$menu["labelpos"] = "right";
			$menu["icon"] = "";
			$menu["link"] = "#";
			$menu["onclick"] = "return edit_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[0]."');";
			$menu["class"] = "";
			$menu["hoverclass"] = "";
			$menu["flyout"] = "down";
			$menu["submenuclass"] = "submenu";
			$menu["items"] = array();
			$submenu = array();
			$submenu["label"] = $gm_lang["edit"];
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
			$submenu["onclick"] = "return edit_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[1]."');";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			$submenu = array();
			$submenu["label"] = $gm_lang["copy"];
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
			$submenu["onclick"] = "return copy_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[2]."');";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			$submenu = array();
			$submenu["label"] = $gm_lang["delete"];
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
			$submenu["onclick"] = "return delete_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[3]."');";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			print " <div style=\"width:25px;\" class=\"center\">";
			print_menu($menu);
			print "</div>";
		}
		print "</td>";
	}
		

}
?>
