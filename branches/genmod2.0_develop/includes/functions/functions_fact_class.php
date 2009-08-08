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
		global $gm_lang, $GEDCOM, $GEDCOMID, $gm_username, $Users;
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
		
		if (!$factobj->owner->canedit) {
			//-- do not print empty facts to visitors. Editors may want to change them
			$lines = preg_split("/\n/", trim($factobj->factrec));
			if (count($lines) < 2 && $factobj->fact == $factobj->factref) return;
		}
		
		// See if RESN tag prevents display or edit/delete
		$resn_tag = preg_match("/2 RESN (.*)/", $factobj->factrec, $match);
		if ($resn_tag == "1") $resn_value = strtolower(trim($match[1]));
		if (array_key_exists($fact, $factarray)) {
			if ($fact == "OBJE") return false;
			// -- handle generic facts
			// Print the label
			if ($styleadd != "rela") {
				print "\n\t\t<tr id=\"row_".$rowcnt."\" >";
				$rowcnt++;
			}
			else {
				print "\n\t\t<tr id=\"row_".$styleadd.$relacnt."\" >";
				$relacnt++;
			}
			self::PrintFactTagBox(&$factobj, $count, $styleadd, $mayedit);
			
			$prted = false;
			print "<td class=\"shade1 $styleadd wrap\">";
	//print "Event: ".$event."<br />Fact: ".$fact."<br />";
			$user = $Users->GetUser($gm_username);
			if ($factobj->disp) {
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
				$prted = $factobj->PrintFactDate(true, true, $fact, $factobj->owner->xref);
				
				//-- print spouse name for marriage events
				$ct = preg_match("/_GMS @(.*)@/", $factobj->factrec, $match);
				if ($ct>0) {
					$spouse=$match[1];
					if ($spouse != "") {
						$spouseobj =& Person::GetInstance($spouse);
						 print "<a href=\"individual.php?pid=".$spouseobj->xref."&amp;gedid=".$spouseobj->gedcomid."\">";
						 if ($spouseobj->disp_name) {
							print $spouseobj->name;
							if ($spouseobj->addname != "") print " - ".$spouseobj->addname;
						 }
						 else print $gm_lang["private"];
						 print "</a>";
					}
					$ct = preg_match("/_GMFS @(.*)@/", $factobj->factrec, $match);
					if (($view != "preview") && ($spouse !== "") && ($ct > 0)) {
						print " - ";
						$famid = $match[1];
						print "<a href=\"family.php?famid=".$famid."&amp;gedid=".$GEDCOMID."\">";
						if ($TEXT_DIRECTION == "ltr") print " &lrm;";
						else print " &rlm;";
						print "[".$gm_lang["view_family"];
						if ($SHOW_FAM_ID_NUMBERS) print " &lrm;(".$famid.")&lrm;";
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
						else if ($fact=="REPO") {
							$repo =& Repository::GetInstance($match[1]);
							if (!$repo->isempty) {
								print "<span class=\"label\">".$gm_lang["repo_name"]."</span><br /><span class=\"field\"><a href=\"repo.php?rid=".$repo->xref."&amp;gedid=".$repo->gedcomid."\">".$repo->title."</a></span>";
								$prt = true;
							}
							$prt = print_address_structure($repo->gedrec, 1, $prt) || $prt;
							$prted = self::PrintFactNotes($repo, 1) || $prt;
						}
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
				$prted = self::PrintAssoRelaRecord($pid, $factobj, $prted);
	
				// -- find _GMU field
				$ct = preg_match("/2 _GMU (.*)/", $factobj->factrec, $match);
				if ($ct>0) print $factarray["_GMU"].": ".$match[1];
				if ($fact!="ADDR") {
					//-- catch all other facts that could be here
					$special_facts = array("ADDR","ALIA","ASSO","CEME","CONC","CONT","DATE","DESC","EMAIL",
					"FAMC","FAMS","FAX","NOTE","OBJE","PHON","PLAC","RESN","SOUR","STAT","TEMP",
					"TIME","TYPE","WWW","_EMAIL","_GMU", "URL", "AGE", "RELA");
	
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
//				if ($prted && $fact != "ASSO") print "<br />";
				if ($prted) print "<br />";
				// -- find source for each fact
				if (ShowFact("SOUR", $pid, "SOUR")) $n1 = self::PrintFactSources($factobj, 2, $prted);
				// -- find notes for each fact
//				if ($fact != "ASSO" && ShowFact("NOTE", $pid, "NOTE")) $n2 = self::PrintFactNotes($factobj, 2, !$prted);
				if (ShowFact("NOTE", $pid, "NOTE")) $n2 = self::PrintFactNotes($factobj, 2, !$prted);
				//-- find multimedia objects
				if (ShowFact("OBJE", $pid, "OBJE")) $n3 = self::PrintFactMedia($factobj, 2, $prted);
				// -- Find RESN tag
				if (isset($resn_value)) {
//					if ($n1 ||$n2 || $n3) print "<br />";
					print_help_link("RESN_help", "qm");
					print PrintReady($factarray["RESN"].": ".$gm_lang[$resn_value])."\n";
				}
			}
			if (!$factobj->disp) print $gm_lang["private"];
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
				self::PrintFactNotes($factobj, 2);
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
				self::PrintFactMedia($factobj, 2);
				self::PrintFactNotes($factobj, 2);
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
					$nt = preg_match("/1 NOTE (.*)(?:\r\n|\n|\r)/", $factobj->factrec, $n1match);
					if ($nt>0) $text = preg_replace("/~~/", "<br />", $n1match[1]);
					$text .= GetCont(2, $factobj->factrec);
					$text = ExpandUrl($text);
					print PrintReady($text);
				}
				else {
					//-- print linked note records
				   	print "<a href=\"note.php?oid=".$note->xref."&amp;gedid=".$note->gedcomid."\">".$note->text."</a>";
					self::PrintFactSources($note, 2);
				}
				// See if RESN tag prevents display or edit/delete
				// -- Find RESN tag
				print "<br />\n";
				if (self::PrintFactSources($factobj, 2)) print "<br />";
				self::PrintResn(&$factobj);
			}
			print "</td></tr>";
		}
	}
	
	public function PrintFactNotes($factobj, $level, $nobr=true) {
		global $gm_lang, $show_changes, $gm_username, $Users, $GEDCOM;
		global $factarray;
		global $WORD_WRAPPED_NOTES, $INDI_EXT_FAM_FACTS;
		
		// This is to prevent that notes are printed as part of the fact for family facts displayed on the indipage
		if ($level == 2 && is_object($factobj) && !$INDI_EXT_FAM_FACTS && preg_match("/\n1 _GMFS @(.*)@/", $factobj->factrec)) return false;
	
		$factnotesprinted = false;
		if (!$nobr) print "<br />";
		$nlevel = $level+1;
		$n2level = $level+2;
		$first = true;

		$factnotes = array();
		
		if (is_string($factobj)) {
			$i = 1;
			do {
				$rec = GetSubRecord(2, "$level NOTE", $factobj, $i);
				if ($rec != "") $factnotes[] = $rec;
				$i++;
			} while ($rec != "");
		}
		else if ($factobj->disp) {
			// We have a factobject as input, and must print the level 2 or higher notes
			if ($level > 1 && $factobj->datatype == "sub") {
				$i = 1;
				do {
					$rec = GetSubRecord(2, "$level NOTE", $factobj->factrec, $i);
					if ($rec != "") $factnotes[] = $rec;
					$i++;
				} while ($rec != "");
			}
			// We have a main entity as input
			elseif ($level == 1 && $factobj->datatype != "sub") {
				foreach($factobj->facts as $key => $fact) {
					if ($fact->fact == "NOTE" && $fact->disp) {
						$factnotes[] = $fact->factrec;
					}
				}
			}
		}
		foreach($factnotes as $key => $noterec) {
			$ct = preg_match("/$level NOTE @(.+)@/", $noterec, $match);
			if ($ct > 0) {
				$note =& Note::GetInstance($match[1]);
				$disp = $note->disp;
				$link = true;
			}
			else {
				$disp = true;
				$link = false;
			}
			// Check if we can display the note
			if ($disp) {
				if (!$first) print "<br />";
				else $first = false;
				$factnotesprinted = true;
				print "\n\t\t<span class=\"label\">".$gm_lang["note"].": </span><span class=\"field wrap\">";
				if ($link) {
					$note->PrintListNote(0, false);
//					self::PrintFactSources($note, 1);
//					self::PrintFactMedia($note, 1, $factobj->owner->xref);
				}
				else {
					$text = nl2br(GetGedcomValue("NOTE", $level, $noterec, ""));
					print PrintReady($text);
				}
			}
//			if (preg_match("/$nlevel SOUR/", $noterec)>0) {
//				print "<div class=\"indent\">";
//			  	PrintFactSources($noterec, $nlevel);
//			  	print "</div>";
//		  	}
	  		print "</span>";
		}
		return $factnotesprinted;
	}

	private function PrintFactSources($factobj, $level, $nobr=true) {
		global $gm_lang, $factarray;
		global $FACT_COUNT, $GM_IMAGE_DIR, $GM_IMAGES;
		static $cnt;

		if (!isset($cnt)) $cnt = 0;
		
		if (!$nobr) print "<br />";
		$nlevel = $level + 1;
		$n2level = $level + 2;
		$printed = false;
		$newline = false;
		
		$factsources = array();
		
		if (is_string($factobj)) {
			$i = 1;
			do {
				$rec = GetSubRecord(2, "$level SOUR", $factobj, $i);
				if ($rec != "") $factsources[] = $rec;
				$i++;
			} while ($rec != "");
		}
		elseif ($factobj->disp) {
			// We have a factobject as input, and must print the level 2 or higher sources
			if ($level > 1 && $factobj->datatype == "sub") {
				$i = 1;
				do {
					$rec = GetSubRecord(2, "$level SOUR", $factobj->factrec, $i);
					if ($rec != "") $factsources[] = $rec;
					$i++;
				} while ($rec != "");
			}
			// We have a main entity as input
			elseif ($level == 1 && $factobj->datatype != "sub") {
				foreach($factobj->facts as $key => $fact) {
					if ($fact->fact == "SOUR" && $fact->disp) {
						$factsources[] = $fact->factrec;
					}
				}
			}
		}
			
		foreach($factsources as $key => $sourcerec) {
			$ct = preg_match("/$level SOUR @(.+)@/", $sourcerec, $match);
			if ($ct > 0) {
				$source =& Source::GetInstance($match[1]);
				$disp = $source->disp;
				$link = true;
			}
			else {
				$disp = true;
				$link = false;
			}
			// Check if we can display the source
			if ($disp) {
				if ($newline) print "<br />";
				$printed = true;
				$newline = true;
				print "\n\t\t<span class=\"label\">";
				if (is_object($factobj)) {
					if ($factobj->datatype == "sub") $owner = $factobj->owner->xref;
					else $owner = $factobj->xref;
				}
				else $owner = rand();
				if (strstr($sourcerec, "\n$n2level ")) print "<a href=\"#\" onclick=\"expand_layer('".$owner.$key."-".$FACT_COUNT."-".$cnt."'); return false;\"><img id=\"".$owner.$key."-".$FACT_COUNT."-".$cnt."_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
				print $gm_lang["source"].":</span> <span class=\"field\">";
				if ($link) $source->PrintListSource(false);
				else {
					print PrintReady(nl2br(GetGedcomValue("SOUR", $level, $sourcerec, "")));
				}
				print "</span>";
				$first = true;
				print "<div id=\"".$owner.$key."-".$FACT_COUNT."-".$cnt."\" class=\"source_citations\">";
				$cs1 = preg_match("/\n$nlevel PAGE (.*)/", $sourcerec, $cmatch);
				if ($cs1>0) {
					$first = false;
					print "\n\t\t\t<span class=\"label\">".$factarray["PAGE"].": </span><span class=\"field\">".PrintReady($cmatch[1]);
					$pagerec = GetSubRecord($nlevel, $cmatch[0], $sourcerec);
					$text = GetCont($nlevel+1, $pagerec);
					$text = ExpandUrl($text);
					print PrintReady($text);
					print "</span>";
				}
				$cs2 = preg_match("/\n$nlevel EVEN (.*)/", $sourcerec, $cmatch);
				if ($cs2>0) {
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"label\">".$factarray["EVEN"]." </span><span class=\"field\">".$cmatch[1]."</span>";
					$cs = preg_match("/".($nlevel+1)." ROLE (.*)/", $sourcerec, $cmatch);
					if ($cs>0) print "\n\t\t\t<br /><span class=\"label\">".$factarray["ROLE"]." </span><span class=\"field\">$cmatch[1]</span>";
				}
				$cs3 = preg_match("/\n$nlevel DATA/", $sourcerec, $cmatch);
			   	if ($cs3>0) {
					$cs4 = preg_match("/\n$n2level DATE (.*)/", $sourcerec, $cmatch);
					if ($cs4>0) {
						print "\n\t\t\t";
						if (!$first) print "<br />";
						else $first = false;
						print "<span class=\"label\">".$gm_lang["date"].": </span><span class=\"field\">".GetChangedDate($cmatch[1])."</span>";
					}
					$tt = preg_match_all("/\n$n2level TEXT (.*)(?:\r\n|\r|\n)/", $sourcerec, $tmatch, PREG_SET_ORDER);
					for($k=0; $k<$tt; $k++) {
						if (!$first || $k != 0) print "<br />";
						else $first = false;
						print "<span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".PrintReady($tmatch[$k][1]);
						print PrintReady(GetCont($n2level+1, $sourcerec));
						print "</span>";
					}
				}
				$cs = preg_match("/\n$nlevel DATE (.*)/", $sourcerec, $cmatch);
				if ($cs>0) {
					print "\n\t\t\t";
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"label\">".$gm_lang["date"].": </span><span class=\"field\">".GetChangedDate($cmatch[1])."</span>";
				}
				$cs = preg_match("/\n$nlevel QUAY (.*)/", $sourcerec, $cmatch);
				if ($cs>0) {
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"label\">".$factarray["QUAY"].": </span><span class=\"field\">".$cmatch[1]."</span>";
				}
				$cs = preg_match_all("/\n$nlevel TEXT (.*)(?:\r\n|\r|\n)/", $sourcerec, $tmatch, PREG_SET_ORDER);
				for($k=0; $k<$cs; $k++) {
					if (!$first || $k != 0) print "<br />";
					else $first = false;
					print "<span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".$tmatch[$k][1];
					$text = GetCont($nlevel+1, $sourcerec);
					$text = ExpandUrl($text);
					print PrintReady($text);
					print "</span>";
				}
//				print "<div class=\"indent\">";
//				self::PrintFactMedia($sourcerec, $nlevel);
//				self::PrintFactNotes($sourcerec, $nlevel, false);
//				print "</div>";
				print "</div>";
				$printed = true;
				$cnt++;
			}
		}
		return $printed;
	}
	//-- Print the links to multi-media objects
	public function PrintFactMedia($factobj, $level, $nobr=true) {
		global $TEXT_DIRECTION, $TBLPREFIX, $GEDCOMS, $MEDIATYPE;
		global $gm_lang, $factarray, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $MediaFS;
		global $WORD_WRAPPED_NOTES, $MEDIA_DIRECTORY, $MEDIA_EXTERNAL, $GEDCOMID, $USE_GREYBOX, $INDI_EXT_FAM_FACTS;
		
		// This is to prevent that notes are printed as part of the fact for family facts displayed on the indipage
		if ($level == 2 && !$INDI_EXT_FAM_FACTS && preg_match("/\n1 _GMFS @(.*)@/", $factobj->factrec)) return false;
		
		if (!$nobr) print "<br />";
	
		$printed = false;
		$nlevel = $level+1;
		if ($level==1) $size=50;
		else $size=25;

		$factmedia = array();
		if ($factobj->disp) {
			$i = 1;
			do {
				$rec = GetSubRecord(2, "$level OBJE", $factobj->factrec, $i);
				if ($rec != "") $factmedia[] = $rec;
				$i++;
			} while ($rec != "");
		}
		print "<!-- Start media link table //-->";
		print "\n<table class=\"facts_table\">";
		foreach($factmedia as $key => $linkrec) {
			$ct = preg_match("/$level OBJE @(.+)@/", $linkrec, $match);
			if ($ct > 0) {
				$media =& MediaItem::GetInstance($match[1]);
				$disp = $media->disp;
				$link = true;
			}
			else {
				$disp = true;
				$link = false;
			}
			// Check if we can display the source
			if ($disp) {
				$printed = true;
				print "<tr><td>";
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
						$imgwidth = $media->fileobj->f_width + 50;
						$imgheight = $media->fileobj->f_height + 50;
					}
				}
				if ($factobj->disp) {
					if (preg_match("'://'", $media->fileobj->f_thumb_file) || preg_match("'://'", $MEDIA_DIRECTORY) > 0 || $media->fileobj->f_file_exists) {
						if ($USE_GREYBOX && $media->fileobj->f_is_image) {
							print "<a href=\"".FilenameEncode($media->fileobj->f_main_file)."\" title=\"".$media->title."\" rel=\"gb_imageset[medialinks]\">";
						}
						else print "<a href=\"#\" onclick=\"return openImage('".$media->fileobj->f_main_file."', '".$imgwidth."', '".$imgheight."', '".$media->fileobj->f_is_image."');\">";
						print "<img src=\"".$media->fileobj->f_thumb_file."\" border=\"0\" align=\"" . ($TEXT_DIRECTION== "rtl"?"right": "left") . "\" class=\"thumbnail\" alt=\"\" /></a>";
					}
					print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\">";
					if ($TEXT_DIRECTION=="rtl" && !hasRTLText($media->title)) print "<i>&lrm;".PrintReady($media->title)."</i></a>";
					else print "<i>".PrintReady($media->title)."</i></a>";
	
					// NOTE: Print the format of the media
					if ($media->extension != "") {
						print "\n\t\t\t<br /><span class=\"label\">".$factarray["FORM"].": </span> <span class=\"field\">".$media->extension."</span>";
						if ($media->fileobj->f_width != 0 && $media->fileobj->f_height != 0) {
							print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["image_size"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $media->fileobj->f_width . ($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ") . $media->fileobj->f_height . "</span>";
						}
					}
					$ttype = preg_match("/\d TYPE (.*)/", $media->gedrec, $match);
					if ($ttype>0){
						print "\n\t\t\t<br /><span class=\"label\">".$gm_lang["type"].": </span> <span class=\"field\">$match[1]</span>";
					}
					print "<br />\n";
					if (ShowFact("NOTE", $media->xref) && ShowFactDetails("NOTE", $media->xref)) {
						$prtd = !self::PrintFactNotes($media, 1, !$printed); // Level is 1 because the notes are subordinate to the linked record, NOT to the link!
					}
					else $prtd = true;
					if (ShowFact("SOUR", $media->xref) && ShowFactDetails("SOUR", $media->xref)) {
						self::PrintFactSources($media, 1, $prtd); // Level is 1 because the sourcelinks are subordinate to the linked record, NOT to the link!
					}
				}
				print "</td></tr>";
			 }
		 }
		 print "</table>\n";
		 print "<!-- End media link table //-->";
		 return $printed;
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
		 global $n_chil, $n_gchi;
		
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
		else {
			$label = preg_replace("/^X_/", "_", $factobj->factref);
			if (isset($factarray[$label])) print $factarray[$label];
			else print $label;
			if ($factobj->factref == "X_BIRT_CHIL" && isset($n_chil)) print "<br />".$gm_lang["number_sign"].$n_chil++;
			if ($factobj->factref == "X_BIRT_GCHI" && isset($n_gchi)) print "<br />".$gm_lang["number_sign"].$n_gchi++;
			$edit_actions = array("edit_fact", "edit_fact", "copy_fact", "delete_fact");
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
			if (!stristr($factobj->factrec, "1 _GM")) {
				$submenu = array();
				$submenu["label"] = $gm_lang["copy"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return copy_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[2]."');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
			}
			$submenu = array();
			$submenu["label"] = $gm_lang["delete"];
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
//			$submenu["onclick"] = "return delete_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[3]."');";
			$submenu["onclick"] = "if (confirm('".$gm_lang["check_delete"]."')) return delete_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[3]."'); else return false;";
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
	/**
	 * print ASSO RELA information
	 *
	 * Ex1:
	 * <code>1 ASSO @I1@
	 * 2 RELA Twin</code>
	 *
	 * Ex2:
	 * <code>1 CHR
	 * 2 ASSO @I1@
	 * 3 RELA Godfather
	 * 2 ASSO @I2@
	 * 3 RELA Godmother</code>
	 *
	 * @author opus27
	 * @param string $pid		person or family ID
	 * @param string $factrec	the raw gedcom record to print
	 * @param string $linebr 	optional linebreak
	 */
	public function PrintAssoRelaRecord($pid, $factobj, $linebr=false) {
		global $GEDCOMID, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS, $TEXT_DIRECTION, $gm_lang, $factarray, $GM_IMAGE_DIR, $GM_IMAGES;
		
		$prted = false;
		// get ASSOciate(s) ID(s)
		$ct = preg_match_all("/\d ASSO @(.*)@/", $factobj->factrec, $match, PREG_SET_ORDER);
		if ($linebr && $ct > 0) print "<br />";
		for ($i=0; $i<$ct; $i++) {
			$level = substr($match[$i][0],0,1);
			$pid2 = $match[$i][1];
			if (!empty($pid2)) {
				// get RELAtionship field
				$assorec = GetSubRecord($level, " ASSO ", $factobj->factrec, $i+1);
				$rct = preg_match("/\d RELA (.*)/", $assorec, $rmatch);
				if ($rct>0) {
					// RELAtionship name in user language
					$key = strtolower(trim($rmatch[1]));
					if (isset($gm_lang["$key"])) $rela = $gm_lang[$key];
					else $rela = $rmatch[1];
					$p = strpos($rela, "(=");
					if ($p>0) $rela = trim(substr($rela, 0, $p));
					if ($pid2==$pid) print "<span class=\"details_label\">";
					print $rela.": ";
					if ($pid2==$pid) print "</span>";
				}
				else $rela = $factarray["RELA"]; // default
	
				// ASSOciate ID link
				$asso =& Person::GetInstance($pid2);
				if (!$asso->isempty) {
					print "<a href=\"individual.php?pid=".$asso->xref."&amp;gedid=".$asso->gedcomid."\">" . $asso->name;
					if (!empty($asso->addname)) print " - " . PrintReady($asso->addname);
					print $asso->addxref;
//					if ($SHOW_ID_NUMBERS && showLivingNameByID($pid2)) print " <span dir=\"$TEXT_DIRECTION\">($pid2)</span>";
					print "</a>";
					// ID age. The age and relationship links should only be printed if relevant, i.e. if the details of pid2 are not hidden.
					if (DisplayDetailsById($pid2, "INDI")) {
						if (!strstr($factobj->factrec, "_BIRT_")) {
							$dct = preg_match("/2 DATE (.*)/", $factobj->factrec, $dmatch);
							if ($dct>0) print " <span class=\"age\">".$asso->GetAge($dmatch[1])."</span>";
						}
						// RELAtionship calculation : for a family print relationship to both spouses
						if (!$asso->view) {
							$family =& Family::GetInstance($pid);
							if (!$family->isempty) {
								if ($family->husb_id != "" && $family->husb_id != $asso->xref) print " - <a href=\"relationship.php?pid1=".$family->husb_id."&amp;pid2=".$asso->xref."&amp;followspouse=1&amp;ged=".$asso->gedcomid."\">[" . $gm_lang["relationship_chart"] . "<img src=\"".$GM_IMAGE_DIR."/" . $GM_IMAGES["sex"]["small"] . "\" title=\"" . $gm_lang["husband"] . "\" alt=\"" . $gm_lang["husband"] . "\" class=\"sex_image\" />]</a>";
								if ($family->wife_id != "" && $family->wife_id != $asso->xref) print " - <a href=\"relationship.php?pid1=".$family->wife_id."&amp;pid2=".$asso->xref."&amp;followspouse=1&amp;ged=".$asso->gedcomid."\">[" . $gm_lang["relationship_chart"] . "<img src=\"".$GM_IMAGE_DIR."/" . $GM_IMAGES["sexf"]["small"] . "\" title=\"" . $gm_lang["wife"] . "\" alt=\"" . $gm_lang["wife"] . "\" class=\"sex_image\" />]</a>";
							}
							else if ($pid != $asso->xref) print " - <a href=\"relationship.php?pid1=".$pid."&amp;pid2=".$asso->xref."&amp;followspouse=1&amp;gedid=".$asso->gedcomid."\">[" . $gm_lang["relationship_chart"] . "]</a>";
						}
					}
				}
				else if (strstr($gedrec, "@ FAM")!==false) {
					print "<a href=\"family.php?famid=$pid2\">";
					if ($TEXT_DIRECTION == "ltr") print " &lrm;"; else print " &rlm;";
					print "[".$gm_lang["view_family"];
		  			if ($SHOW_FAM_ID_NUMBERS) print " &lrm;($pid2)&lrm;";
		  			if ($TEXT_DIRECTION == "ltr") print "&lrm;]</a>\n"; else print "&rlm;]</a>\n";
				}
				else {
					print $gm_lang["unknown"];
					if ($SHOW_ID_NUMBERS) print " <span dir=\"$TEXT_DIRECTION\">($pid2)</span>";
				}
				$prted = true;
//				print "<br />";
//				self::PrintFactNotes($assorec, $level+1, false);
				if ($linebr && $i != ($ct-1)) print "<br />\n";
				if (substr($_SERVER["SCRIPT_NAME"],1) == "pedigree.php") {
					print "<br />";
					self::PrintFactSources($assorec, $level+1);
				}
			}
		}
		return $prted;
	}
		
	// Print a new fact box on details pages
	public function PrintAddNewFact($id, $usedfacts, $type) {
		global $factarray, $gm_lang;
		global $INDI_FACTS_ADD, $INDI_FACTS_UNIQUE, $INDI_QUICK_ADDFACTS;
		global $FAM_FACTS_ADD, $FAM_FACTS_UNIQUE, $FAM_QUICK_ADDFACTS;
		global $SOUR_FACTS_ADD, $SOUR_FACTS_UNIQUE, $SOUR_QUICK_ADDFACTS;
		global $REPO_FACTS_ADD, $REPO_FACTS_UNIQUE, $REPO_QUICK_ADDFACTS;
		global $MEDIA_FACTS_UNIQUE, $MEDIA_FACTS_ADD, $MEDIA_QUICK_ADDFACTS;
		global $NOTE_FACTS_UNIQUE, $NOTE_FACTS_ADD, $NOTE_QUICK_ADDFACTS;
	
		if ($type == "SOUR") $addfacts = array_merge(CheckFactUnique(preg_split("/[, ;:]+/", $SOUR_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "SOUR"), preg_split("/[, ;:]+/", $SOUR_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if ($type == "REPO") $addfacts = array_merge(CheckFactUnique(preg_split("/[, ;:]+/", $REPO_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "REPO"), preg_split("/[, ;:]+/", $REPO_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if ($type == "INDI") $addfacts = array_merge(CheckFactUnique(preg_split("/[, ;:]+/", $INDI_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "INDI"), preg_split("/[, ;:]+/", $INDI_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if($type == "FAM") $addfacts = array_merge(CheckFactUnique(preg_split("/[, ;:]+/", $FAM_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "FAM"), preg_split("/[, ;:]+/", $FAM_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if($type == "OBJE") $addfacts = array_merge(CheckFactUnique(preg_split("/[, ;:]+/", $MEDIA_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "OBJE"), preg_split("/[, ;:]+/", $MEDIA_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if($type == "NOTE") $addfacts = array_merge(CheckFactUnique(preg_split("/[, ;:]+/", $NOTE_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "NOTE"), preg_split("/[, ;:]+/", $NOTE_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else return;
		if (count($addfacts) == 0) return;
	
		usort($addfacts, "FactSort");
		print "<tr><td class=\"shade2\">";
		print_help_link("add_new_facts_help", "qm");
		print $gm_lang["add_fact"]."</td>";
		print "<td class=\"shade1\">";
		print "<form method=\"get\" name=\"newfactform\" action=\"\">\n";
		print "<select id=\"newfact\" name=\"newfact\">\n";
		foreach($addfacts as $indexval => $fact) {
	  		print PrintReady("<option value=\"$fact\">".$factarray[$fact]. " [".$fact."]</option>\n");
		}
		if (($type == "INDI") || ($type == "FAM")) print "<option value=\"EVEN\">".$gm_lang["custom_event"]." [EVEN]</option>\n";
		if (!empty($_SESSION["clipboard"])) {
			foreach($_SESSION["clipboard"] as $key=>$fact) {
				if ($fact["type"]==$type) {
					if (isset($factarray[$fact["fact"]])) print "<option value=\"clipboard_$key\">".$gm_lang["add_from_clipboard"]." ".$factarray[$fact["fact"]]."</option>\n";
					else print "<option value=\"clipboard_$key\">".$gm_lang["add_from_clipboard"]." ".$fact["fact"]."</option>\n";
				}
			}
		}
		print "</select>";
		print "&nbsp;<input type=\"button\" value=\"".$gm_lang["add"]."\" onclick=\"add_record('$id', 'newfact', 'newfact');\" />\n";
		
		// Print the quick add fact links
		$qfacts = array();
		if ($type == "INDI" && !empty($INDI_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", $INDI_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "FAM" && !empty($FAM_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", $FAM_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "REPO" && !empty($REPO_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", $REPO_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "SOUR" && !empty($SOUR_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", $SOUR_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "OBJE" && !empty($MEDIA_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", $MEDIA_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "NOTE" && !empty($NOTE_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", $NOTE_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($qfacts as $key => $qfact) {
			if (in_array($qfact, $addfacts)) print "&nbsp;&nbsp;<a href=\"javascript: ".$factarray[$qfact]."\" onclick=\"add_new_record('".$id."', '".$qfact."', 'newfact'); return false;\">".$factarray[$qfact]."</a>";
		}
		print "</form>\n";
		print "</td></tr>\n";
	}
	/**
	 * print first major fact for an Individual
	 *
	 * @param string $key	indi pid
	 */
	public function PrintFirstMajorFact($person, $prt=true, $break=false) {
		global $gm_lang, $factarray, $GM_BASE_DIRECTORY, $factsfile, $LANGUAGE;
		
		$majorfacts = array("BIRT", "CHR", "BAPM", "DEAT", "BURI", "BAPL", "ADOP");
		$retstr = "";
		foreach ($majorfacts as $indexval => $fact) {
			$facts = $person->SelectFacts($fact);
			if (isset($facts[0])) {
				$factobj = $facts[0]; 
				if (strlen($factobj->factrec) > 7 && $factobj->disp) {
					if ($break) $retstr .= "<br />";
					else $retstr .= " -- ";
					$retstr .= "<i>";
					if (isset($gm_lang[$fact])) $retstr .= $gm_lang[$fact];
					else if (isset($factarray[$fact])) $retstr .= $factarray[$fact];
					else $retstr .= $fact;
					$retstr .= " ";
					$retstr .= $factobj->PrintFactDate(false, false, false, false, false, false);
					$retstr .= $factobj->PrintFactPlace(false, false, false, false);
					$retstr .= "</i>";
					break;
				}
			}
		}
		if ($prt) {
			print $retstr;
			return $fact;
		}
		else return addslashes($retstr);
	}

}
?>
