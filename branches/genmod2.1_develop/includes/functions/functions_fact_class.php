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
 * @version $Id: functions_fact_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
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
	 */
	public function PrintFact($factobj, $pid, $mayedit=true) {
		global $nonfacts;
		global $TEXT_DIRECTION;
		global $FACT_COUNT;
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
		if (substr($factobj->fact,0,2) == "X_") {
			$relafact = true;
			$factobj->style = "FactRela"; // not editable
			// NOTE this is a workaround for generated and real asso records.
			if (defined("GM_FACT_".substr($factobj->fact,1))) $fact = substr($factobj->fact, 1);
			else $fact = "EVEN";
		}
		else {
			$relafact = false;
			$fact = $factobj->fact;
		}
	
		// -- avoid known non facts
		if (in_array($fact, $nonfacts)) return;

		if (!$factobj->owner->canedit) {
			//-- do not print empty facts to visitors. Editors may want to change them
			$lines = preg_split("/\n/", trim($factobj->factrec));
			if (count($lines) < 2 && $event == "") return;
		}

		// See if RESN tag prevents display or edit/delete
		$resn_tag = preg_match("/2 RESN (.*)/", $factobj->factrec, $match);
		if ($resn_tag == "1") $resn_value = strtolower(trim($match[1]));
		if (defined("GM_FACT_".$fact)) {
			if ($fact == "OBJE") return false;
			// -- handle generic facts
			// Print the label
			if ($factobj->style != "FactRela") {
				print "\n\t\t<tr id=\"row_".$rowcnt."\" >";
				$rowcnt++;
			}
			else {
				print "\n\t\t<tr id=\"row_rela".$relacnt."\" >";
				$relacnt++;
			}
			self::PrintFactTagBox($factobj, $mayedit);
			
			$prted = false;
			print "<td class=\"FactDetailCell $factobj->style\">";
	//print "Event: ".$event."<br />Fact: ".$fact."<br />";
			if ($factobj->disp) {
				// -- first print TYPE for some facts
				if ($fact!="EVEN" && $fact!="FACT") {
					$ct = preg_match("/2 TYPE (.*)/", $factobj->factrec, $match);
					if ($ct>0) {
						$type = trim($match[1]);
						if (defined("GM_FACT_MARR_".Str2Upper($type))) print constant("GM_FACT_MARR_".Str2Upper($type));
						else if (defined("GM_FACT_".$type)) print constant("GM_FACT_".$type);
						else if (defined("GM_LANG_".$type)) print constant("GM_LANG_".$type);
						else print $type;
						print "<br />";
					}
				}
				// -- find date for each fact
				$prted = $factobj->PrintFactDate(true, true, true, true);
				
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
						 else print "<span class=\"FactDetailField\">".GM_LANG_private."</span>";
						 print "</a>";
					}
					$ct = preg_match("/_GMFS @(.*)@/", $factobj->factrec, $match);
					if ((!$factobj->owner->view) && ($spouse !== "") && ($ct > 0)) {
						print " - ";
						$famid = $match[1];
						print "<a href=\"family.php?famid=".$famid."&amp;gedid=".GedcomConfig::$GEDCOMID."\">";
						if ($TEXT_DIRECTION == "ltr") print " &lrm;";
						else print " &rlm;";
						print "<span class=\"FactAssoLink\">[".GM_LANG_view_family;
						if (GedcomConfig::$SHOW_FAM_ID_NUMBERS) print " &lrm;(".$famid.")&lrm;";
						if ($TEXT_DIRECTION == "ltr") print "&lrm;]</span></a>\n";
						else print "&rlm;]</a>\n";
					}
				}
				//-- print other characterizing fact information
				if ($event != "" && $fact != "ASSO") {
					$ct = preg_match("/@(.*)@/", $event, $match);
					if ($ct>0) {
						if (IdType($match[1]) == "INDI") {
							$person =& Person::GetInstance($match[1]);
							print "<span class=\"FactDetailField\"><a href=\"individual.php?pid=".$person->xref."&amp;gedid=".$person->gedcomid."\">".$person->name."</a></span><br />";
						}
						else if ($fact == "REPO") {
							$repo =& Repository::GetInstance($match[1]);
							if (!$repo->isempty) {
								print "<span class=\"FactDetailLabel\">".GM_LANG_repo_name."</span><br /><span class=\"FactDetailField\"><a href=\"repo.php?rid=".$repo->xref."&amp;gedid=".$repo->gedcomid."\">".$repo->title."</a></span>";
								$prt = true;
							}
							$prt = self::PrintAddressStructure($repo, 1, $prt) || $prt;
							$prted = self::PrintFactNotes($repo, 1, !$prt) || $prt;
						}
						else if ($fact == "SUBM") {
							$subm =& Submitter::GetInstance($match[1]);
							$prted = self::PrintSubmitterInfo($subm) || $prted;
						}
					}
					else if ($fact=="ALIA") {
						 //-- strip // from ALIA tag for FTM generated gedcoms
						 print "<span class=\"FactDetailField\">".preg_replace("'/'", "", $event)."</span><br />";
						$prted = true;
					}
					else if (strstr("URL WWW ", $fact." ")) {
						if (!preg_match('/^http/', $event)) $event = "http://".$event;
						print "<span class=\"FactDetailField\"><a href=\"".$event."\" target=\"new\">".PrintReady($event)."</a></span>";
						$prted = true;
					}
					else if (strstr("_EMAIL EMAIL", $fact." ")) {
						 print "<span class=\"FactDetailField\"><a href=\"mailto:".$event."\">".$event."</a></span>";
						$prted = true;
					}
					else if (strstr("FAX", $fact)) {
						print "<span class=\"FactDetailField\">&lrm;".$event." &lrm;</span>";
						$prted = true;
					}
					else if ($fact == "RESN") {
						print PrintReady(PrintHelpLink("RESN_help", "qm", "", "", true)."<span class=\"FactDetailField\">".constant("GM_LANG_".$event)."</span>");
					}
					else if (!strstr("PHON ADDR ", $fact." ") && $event!="Y") {
						print "<span class=\"FactDetailField\">".PrintReady($event." ")."</span>";
						$prted = true;
					}
					else if ($fact == "_PRIM" || $fact == "_THUM") {
						print "<span class=\"FactDetailField\">";
						if ($event == "Y") print PrintReady(GM_LANG_yes);
						else print PrintReady(GM_LANG_no);
						print "</span>";
						$prted = true;
					}
				}
				// The GetCont must also be done if $event is empty, which is if 1 TEXT has no value.
				$temp = trim(GetCont(2, $factobj->factrec), "\r\n");
				if (strstr("PHON ADDR ", $fact." ")===false && $temp!="") {
					if (GedcomConfig::$WORD_WRAPPED_NOTES) print " ";
					print "<span class=\"FactDetailField\">".PrintReady($temp)."</span>";
				}
				//-- find description for some facts
				$ct = preg_match("/2 DESC (.*)/", $factobj->factrec, $match);
				if ($ct>0) print "<span class=\"FactDetailField\">".PrintReady($match[1])."</span>";
				// -- print PLACe, TEMPle and STATus
				$prted = $factobj->PrintFactPlace(true, true, true) || $prted;
				// -- print BURIal -> CEMEtery
				$ct = preg_match("/2 CEME (.*)/", $factobj->factrec, $match);
				if ($ct>0) {
					if ($prted) print "<br />";
					if (file_exists(GM_IMAGE_DIR."/facts/CEME.gif")) print "<img src=\"".GM_IMAGE_DIR."/facts/CEME.gif\" alt=\"".GM_FACT_CEME."\" title=\"".GM_FACT_CEME."\" align=\"middle\" /> ";
					print "<span class=\"FactDetailLabel\">".GM_FACT_CEME.": </span><span class=\"FactDetailField\">".$match[1]."</span>\n";
				}
				//-- print address structure
				if ($fact!="ADDR" && $fact!="PHON") {
					$prted = self::PrintAddressStructure($factobj, 2, $prted) || $prted;
				}
				else {
					$prted = self::PrintAddressStructure($factobj, 1, $prted) || $prted;
				}
				// -- Enhanced ASSOciates > RELAtionship
				$prted = self::PrintAssoRelaRecord($factobj, $pid, $prted) || $prted;
				// -- find _GMU field
				$ct = preg_match("/2 _GMU (.*)/", $factobj->factrec, $match);
				if ($ct>0) {			
					$cuser =& User::GetInstance($match[1]);
					print "<br /><span class=\"".($prted?"AssoIndent ":"")."FactDetailLabel\">".GM_FACT__GMU.": </span>".$match[1].(!$cuser->is_empty ? " ".PrintReady("(".$cuser->firstname." ".$cuser->lastname.")") : "");
					$prted = false;
				}
				if ($fact!="ADDR") {
					//-- catch all other facts that could be here
					$special_facts = array("ADDR","ALIA","ASSO","CEME","CONC","CONT","DATE","DESC","EMAIL",
					"FAMC","FAMS","FAX","NOTE","OBJE","PHON","PLAC","RESN","SOUR","STAT","TEMP",
					"TIME","TYPE","WWW","_EMAIL","_GMU", "URL", "AGE", "RELA");
					$suppress_subs = array("_GMS", "_GMFS");
	
					$ct = preg_match_all("/\n2 (\w+) (.*)/", $factobj->factrec, $match, PREG_SET_ORDER);
					$prtbr = false;
					for($i=0; $i<$ct; $i++) {
						$factref = $match[$i][1];
						if (!in_array($factref, $special_facts) && !in_array($factref, $suppress_subs)) {
							if ($prtbr || $prted) {
								print "<br />";
								$prtbr = true;
							}
							if (defined("GM_FACT_".$factref)) $label = constant("GM_FACT_".$factref);
							else $label = $factref;
							if ($factref == "SUBM") print "<br />";
							if (file_exists(GM_IMAGE_DIR."/facts/".$factref.".gif")) print "<img src=\"".GM_IMAGE_DIR."/facts/".$factref.".gif\" alt=\"".$label."\" title=\"".$label."\" align=\"middle\" /> ";
							else print "<span class=\"FactDetailLabel\">".$label.": </span>";
							$value = trim($match[$i][2]);
							if (stristr($value, "@")) {
								if ($factref == "SUBM") {
									$subm =& Submitter::GetInstance(str_replace("@", "", $value));
									self::PrintSubmitterInfo($subm);
								}
							}
							else {
								print "<span class=\"FactDetailField\">";
								if (defined("GM_LANG_".strtolower($value))) print constant("GM_LANG_".strtolower($value));
								else print PrintReady($value);
								print "</span>";
							}
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
										if (defined("GM_FACT_".$factref)) $label = constant("GM_FACT_".$factref);
										else $label = $factref;
										if (file_exists(GM_IMAGE_DIR."/facts/".$factref.".gif")) print "<img src=\"".GM_IMAGE_DIR."/facts/".$factref.".gif\" alt=\"".$label."\" title=\"".$label."\" align=\"middle\" /> ";
										else print "<span class=\"FactDetailLabel\">".$label.": </span>";
										$value = trim($matchf[$j][2]);
										print "<span class=\"FactDetailField\">";
										if (defined("GM_LANG_".strtolower($value))) print constant("GM_LANG_".strtolower($value));
										else print PrintReady($value);
	//									if ($j < $ctf) print "<br />";
										print "</span>\n";
									}
								}
							}
								
						}
					}
				}
				if (!$relafact) {
					if ($prted) print "<br /><br />";
					$prted = true; // Set this so regardless if anything is printed before, source/notes/obje's will print on a new line
					
					// -- find source for each fact
					if (PrivacyFunctions::showFact("SOUR", $pid, "SOUR")) $n1 = self::PrintFactSources($factobj, 2, $prted);
					
					// -- find notes for each fact
					if (PrivacyFunctions::showFact("NOTE", $pid, "NOTE")) $n2 = self::PrintFactNotes($factobj, 2, !$prted);
					
					//-- find multimedia objects
					if (PrivacyFunctions::showFact("OBJE", $pid, "OBJE")) $n3 = self::PrintFactMedia($factobj, 2, !$prted);
					
					// -- Find RESN tag
					if (isset($resn_value)) {
						if ($n1 ||$n2 || $n3) print "<br />";
						self::PrintResn($factobj);
					}
				}
			}
			if (!$factobj->disp) print "<span class=\"FactDetailField\">".GM_LANG_private."</span>";
			print "</td>";
			print "\n\t\t</tr>";
		}
		else {
			// -- catch all unknown codes here
			$body = GM_LANG_unrecognized_code." ".$fact;
			if (!GedcomConfig::$HIDE_GEDCOM_ERRORS) print "\n\t\t<tr><td class=\"FactLabelCell $factobj->style\"><span class=\"Error\">".GM_LANG_unrecognized_code.": $fact</span></td><td class=\"FactDetailCell\">$event<br />".GM_LANG_unrecognized_code_msg." <a href=\"#\" onclick=\"message('".GedcomConfig::$CONTACT_EMAIL."','', '', '$body'); return false;\">".GedcomConfig::$CONTACT_EMAIL."</a>.</td></tr>";
		}
	}
	//------------------- end print fact function
	
	public function PrintMainMedia($factobj, $pid, $mayedit=true) {
		global $TEXT_DIRECTION;
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
			else if ((preg_match("'://'", GedcomConfig::$MEDIA_DIRECTORY)>0)||($media->fileobj->f_file_exists)) {
				if ($media->fileobj->f_width > 0 && $media->fileobj->f_height > 0) {
					$imgwidth = $media->fileobj->f_width+50;
					$imgheight = $media->fileobj->f_height + 50;
				}
			}
			
			// NOTE: Start printing the media details
			print "\n\t\t<tr id=\"mrow_".$rowcnt."\">";
			$rowcnt++;
			self::PrintFactTagBox($factobj, $mayedit);
	
			// NOTE Print the title of the media
			print "<td class=\"FactDetailCell ".$factobj->style."\">";
			if ($factobj->disp) {
				print "<span class=\"FactDetailField\">";
				MediaFS::DispImgLink($media->fileobj->f_main_file, $media->fileobj->f_thumb_file, $media->title, "mainmedia", 0, 0, $imgwidth, $imgheight, $media->fileobj->f_is_image, $media->fileobj->f_file_exists);
				print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\"><i>".PrintReady(($factobj->style == "ChangeOld" ? $media->oldtitle : $media->title))."</i></a>";
				if ($media->fileobj->f_thumb_file == "" && preg_match("'://'", $media->filename)) print "<br /><a href=\"".$media->filename."\" target=\"_blank\">".$media->filename."</a>";
				print "</span>";
				// NOTE: Print the format of the media
				if ($media->extension != "") {
					print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_FACT_FORM.": </span> <span class=\"FactDetailField\">".$media->extension."</span>";
					if ($media->fileobj->f_width != 0 &&  $media->fileobj->f_height != 0) {
						print "\n\t\t\t<span class=\"FactDetailLabel\"><br />".GM_LANG_image_size.": </span> <span class=\"FactDetailField\" style=\"direction: ltr;\">" . $media->fileobj->f_width . ($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ") . $media->fileobj->f_height . "</span>";
					}
				}
				$ttype = preg_match("/3 TYPE (.*)/", $media->gedrec, $match);
				if ($ttype>0){
					print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_LANG_type.": </span> <span class=\"FactDetailField\">".$match[1]."</span>";
				}
				// Print PRIM from owner, NOT from the media object
				$ttype = preg_match("/\d _PRIM (\w)/", $factobj->factrec, $match);
				if ($ttype>0){
					if ($match[1] == "Y") $val = "yes";
					else $val = "no";
					print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_FACT__PRIM.": </span> <span class=\"FactDetailField\">".constant("GM_LANG_".$val)."</span>";
				}
				
				// Print THUM from from owner, NOT from the media object
				$ttype = preg_match("/\d _THUM (\w)/", $factobj->factrec, $match);
				if ($ttype>0){
					if ($match[1] == "Y") $val = "yes";
					else $val = "no";
					print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_FACT__THUM.": </span> <span class=\"FactDetailField\">".constant("GM_LANG_".$val)."</span>";
				}
		
				print "<br />\n";
				
				// print the media notes
				self::PrintFactNotes($media, 1);
				
				// Print the notes in the MM link
				self::PrintFactNotes($factobj, 2);
			}
			else print "<span class=\"FactDetailField\">".GM_LANG_private."</span>";
			print "</td></tr>";
		}
	}
	
	public function PrintMainSources($factobj, $pid, $mayedit=true) {
		
		 // -- find source for each fact
		// Here we check if we can show the source at all!
		$source =& Source::GetInstance($factobj->linkxref);
		if ($source->disp) {
			print "\n\t\t\t<tr>";
			self::PrintFactTagBox($factobj, $mayedit);
			print "\n\t\t\t<td class=\"FactDetailCell ".$factobj->style."\">";
			if ($factobj->disp) {
				print "<span class=\"FactDetailField\"><a href=\"source.php?sid=".$source->xref."\">";
    			print ($factobj->style == "ChangeOld" ? $source->olddescriptor : $source->descriptor);
   				//-- Print additional source title
   				if ($factobj->style == "ChangeOld" && $source->oldadddescriptor != "") print " - ".$source->oldadddescriptor;
   				else if ($source->adddescriptor != "") print " - ".$source->adddescriptor;
				print "</a></span>";
				$cs = preg_match("/2 PAGE (.*)/", $factobj->factrec, $cmatch);
				if ($cs>0) {
					print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_FACT_PAGE.": </span><span class=\"FactDetailField\">".$cmatch[1];
					$srec = GetSubRecord(2, "2 PAGE", $factobj->factrec);
					$text = GetCont(3, $srec);
					$text = self::ExpandUrl($text);
					print PrintReady($text);
					print "</span>";
				}
				$cs = preg_match("/2 EVEN (.*)/", $factobj->factrec, $cmatch);
				if ($cs>0) {
					print "<br /><span class=\"FactDetailLabel\">".GM_FACT_EVEN." </span><span class=\"FactDetailField\">".$cmatch[1]."</span>";
					$cs = preg_match("/3 ROLE (.*)/", $factobj->factrec, $cmatch);
					if ($cs>0) print "\n\t\t\t<br />&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"FactDetailLabel\">".GM_FACT_ROLE." </span><span class=\"FactDetailField\">$cmatch[1]</span>";
				}
				$cs = preg_match("/2 DATA/", $factobj->factrec, $cmatch);
				if ($cs>0) {
//						// Don't print the DATA tag, it doesn't contain data so is obsolete!
//						print "<br /><span class=\"FactDetailLabel\">".GM_FACT_DATA." </span>";
					$srec = GetSubRecord(2, "2 DATA", $factobj->factrec);
					$cs = preg_match("/3 DATE (.*)/", $srec, $cmatch);
					if ($cs>0) print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_LANG_date.":  </span><span class=\"FactDetailField\">".GetChangedDate($cmatch[1])."</span>";
					$i = 1;
					do {
						$trec = GetSubRecord(3, "3 TEXT", $srec, $i);
						if ($trec != "") {
							// fix: only print the level 3 TEXT, as the CONT will be added below.
							$cs = preg_match("/3 TEXT (.*)/", $trec, $cmatch);
							print "<br /><span class=\"FactDetailLabel\">".GM_LANG_text." </span><span class=\"FactDetailField\">".GetGedcomValue("TEXT", 3, $cmatch[0]);
							$text = GetCont(4, $trec);
							$text = self::ExpandUrl($text);
							print $text;
							print "</span>";
						}
						$i++;
					} while ($trec != "");
				}
				$cs = preg_match("/2 QUAY (.*)/", $factobj->factrec, $cmatch);
				if ($cs>0) print "<br /><span class=\"FactDetailLabel\">".GM_FACT_QUAY." </span><span class=\"FactDetailField\">".$cmatch[1]."</span>";
				$i = 1;
				do {
					$trec = GetSubRecord(2, "2 TEXT", $factobj->factrec, $i);
					print $trec;
					if ($trec != "") {
						print "<br /><span class=\"FactDetailLabel\">".GM_LANG_text." </span><span class=\"FactDetailField\">".GetGedcomValue("TEXT", 2, $trec);
						$text = GetCont(3, $trec);
						$text = self::ExpandUrl($text);
						print $text;
						print "</span>";
					}
					$i++;
				} while ($trec != "");
				// See if RESN tag prevents display or edit/delete
				// -- Find RESN tag
				print "<br />";
				self::PrintResn($factobj);
				self::PrintFactMedia($factobj, 2);
				self::PrintFactNotes($factobj, 2);
			}
			else print "<span class=\"FactDetailField\">".GM_LANG_private."</span>";
			print "</td></tr>";
		}
	}
	
	/**
	 * print main note row
	 *
	 * this function will print a table row for a fact table for a level 1 note in the main record
	 * @param string $factrec	the raw gedcom sub record for this note
	 * @param int $level		The start level for this note, usually 1
	 * @param string $pid		The gedcom XREF id for the level 0 record that this note is a part of
	 */
	public function PrintMainNotes($factobj, $pid, $mayedit=true) {
		
		if ($factobj->linktype == "Note") $note =& Note::GetInstance($factobj->linkxref);
		if ($factobj->linktype != "Note" || $note->disp) {
			print "\n\t\t\t<tr>";
			self::PrintFactTagBox($factobj, $mayedit);
			print "\n<td class=\"FactDetailCell ".$factobj->style."\">";
			if ($factobj->disp) {
				print "<span class=\"FactDetailField\">";
				if ($factobj->linktype == "") {
					$text = "";
					$nt = preg_match("/1 NOTE (.*)(\r\n|\n|\r)*/", $factobj->factrec, $n1match);
					if ($nt>0) $text = preg_replace("/~~/", "<br />", $n1match[1]);
					$text .= GetCont(2, $factobj->factrec);
					$text = self::ExpandUrl($text);
					print PrintReady($text);
				}
				else {
					//-- print linked note records
				   	print "<a href=\"note.php?oid=".$note->xref."&amp;gedid=".$note->gedcomid."\">".($factobj->style == "ChangeOld" ? $note->oldtext : $note->text)."</a>";
					self::PrintFactSources($note, 2);
				}
				print "</span>";
				// See if RESN tag prevents display or edit/delete
				// -- Find RESN tag
				print "<br />\n";
				if (self::PrintFactSources($factobj, 2)) print "<br />";
				self::PrintResn($factobj);
			}
			print "</td></tr>";
		}
	}
	
	public function PrintFactNotes($factobj, $level, $nobr=true) {

		// This is to prevent that notes are printed as part of the fact for family facts displayed on the indipage
		if ($level == 2 && is_object($factobj) && !GedcomConfig::$INDI_EXT_FAM_FACTS && preg_match("/\n2 _GMFS @(.*)@/", $factobj->factrec)) return false;
	
		$factnotesprinted = false;
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
					$rec = GetSubRecord($level, "$level NOTE", $factobj->factrec, $i);
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
		if (!$nobr && count($factnotes) > 0) print "<br />";
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
				print "\n\t\t<span class=\"FactDetailLabel\">".GM_LANG_note.": </span><span class=\"FactDetailField\">";
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
//				print "<div class=\"Indent\">";
//			  	PrintFactSources($noterec, $nlevel);
//			  	print "</div>";
//		  	}
	  		print "</span>";
		}
		return $factnotesprinted;
	}

	public function PrintFactSources($factobj, $level, $nobr=true) {
		global $FACT_COUNT, $GM_IMAGES;
		static $cnt;

		if (!isset($cnt)) $cnt = 0;
		
		if (!$nobr) print "<br />";
		$nlevel = $level + 1;
		$n2level = $level + 2;
		$printed = false;
		$newline = false;
		
		$factsources = array();
		
		if (is_string($factobj)) {
			$type = "";
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
				$type = ($factobj->style == "ChangeOld" ? "old" : "");
				$i = 1;
				do {
					$rec = GetSubRecord(2, "$level SOUR", $factobj->factrec, $i);
					if ($rec != "") $factsources[] = $rec;
					$i++;
				} while ($rec != "");
			}
			// We have a main entity as input
			elseif ($level == 1 && $factobj->datatype != "sub") {
				$type = "";
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
				print "\n\t\t<span class=\"FactDetailLabel\">";
				if (is_object($factobj)) {
					if ($factobj->datatype == "sub") $owner = $factobj->owner->xref;
					else $owner = $factobj->xref;
				}
				else $owner = rand();
				if (strstr($sourcerec, "\n$nlevel ")) print "<a href=\"#\" onclick=\"expand_layer('".$owner.$key."-".$FACT_COUNT."-".$cnt."'); return false;\"><img id=\"".$owner.$key."-".$FACT_COUNT."-".$cnt."_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
				print GM_LANG_source.":</span> <span class=\"FactDetailField\">";
				if ($link) $source->PrintListSource(false, 1, "", false, $type);
				else {
					print PrintReady(nl2br(GetGedcomValue("SOUR", $level, $sourcerec, "")));
				}
				print "</span>";
				$first = true;
				print "<div id=\"".$owner.$key."-".$FACT_COUNT."-".$cnt."\" class=\"FactSourceCitationDetails\">";
				$cs1 = preg_match("/\n$nlevel PAGE (.*)/", $sourcerec, $cmatch);
				if ($cs1>0) {
					$first = false;
					print "\n\t\t\t<span class=\"FactDetailLabel\">".GM_FACT_PAGE.": </span><span class=\"FactDetailField\">".PrintReady($cmatch[1]);
					$pagerec = GetSubRecord($nlevel, $cmatch[0], $sourcerec);
					$text = GetCont($nlevel+1, $pagerec);
					$text = self::ExpandUrl($text);
					print PrintReady($text);
					print "</span>";
				}
				$cs2 = preg_match("/\n$nlevel EVEN (.*)/", $sourcerec, $cmatch);
				if ($cs2>0) {
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"FactDetailLabel\">".GM_FACT_EVEN." </span><span class=\"FactDetailField\">".$cmatch[1]."</span>";
					$cs = preg_match("/".($nlevel+1)." ROLE (.*)/", $sourcerec, $cmatch);
					if ($cs>0) print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_FACT_ROLE." </span><span class=\"FactDetailField\">".$cmatch[1]."</span>";
				}
				$cs3 = preg_match("/\n$nlevel DATA/", $sourcerec, $cmatch);
			   	if ($cs3>0) {
					$cs4 = preg_match("/\n$n2level DATE (.*)/", $sourcerec, $cmatch);
					if ($cs4>0) {
						print "\n\t\t\t";
						if (!$first) print "<br />";
						else $first = false;
						print "<span class=\"FactDetailLabel\">".GM_LANG_date.": </span><span class=\"FactDetailField\">".GetChangedDate($cmatch[1])."</span>";
					}
					$tt = preg_match_all("/\n$n2level\sTEXT\s(.*)(\r\n|\r|\n)?/", $sourcerec, $tmatch, PREG_SET_ORDER);
					for($k=0; $k<$tt; $k++) {
						if (!$first || $k != 0) print "<br />";
						else $first = false;
						print "<span class=\"FactDetailLabel\">".GM_LANG_text." </span><span class=\"FactDetailField\">".PrintReady($tmatch[$k][1]);
						print PrintReady(GetCont($n2level+1, $sourcerec));
						print "</span>";
					}
				}
				$cs = preg_match("/\n$nlevel DATE (.*)/", $sourcerec, $cmatch);
				if ($cs>0) {
					print "\n\t\t\t";
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"FactDetailLabel\">".GM_LANG_date.": </span><span class=\"FactDetailField\">".GetChangedDate($cmatch[1])."</span>";
				}
				$cs = preg_match("/\n$nlevel QUAY (.*)/", $sourcerec, $cmatch);
				if ($cs>0) {
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"FactDetailLabel\">".GM_FACT_QUAY.": </span><span class=\"FactDetailField\">".$cmatch[1]."</span>";
				}
				$cs = preg_match_all("/\n$nlevel TEXT (.*)(\r\n|\r|\n)?/", $sourcerec, $tmatch, PREG_SET_ORDER);
				for($k=0; $k<$cs; $k++) {
					if (!$first || $k != 0) print "<br />";
					else $first = false;
					print "<span class=\"FactDetailLabel\">".GM_LANG_text." </span><span class=\"FactDetailField\">".$tmatch[$k][1];
					$text = GetCont($nlevel+1, $sourcerec);
					$text = self::ExpandUrl($text);
					print PrintReady($text);
					print "</span>";
				}
				print "<div class=\"Indent\">";
//				self::PrintFactMedia($sourcerec, $nlevel);
				self::PrintFactNotes($sourcerec, $nlevel, false);
				print "</div>";
				print "</div>";
				$printed = true;
				$cnt++;
			}
		}
		return $printed;
	}
	
	//-- Print the links to multi-media objects
	public function PrintFactMedia($factobj, $level, $nobr=true) {
		global $TEXT_DIRECTION;
		global $GM_IMAGES;
		
		// This is to prevent that notes are printed as part of the fact for family facts displayed on the indipage
		if ($level == 2 && !GedcomConfig::$INDI_EXT_FAM_FACTS && preg_match("/\n2 _GMFS @(.*)@/", $factobj->factrec)) return false;
		
		$printed = false;
		$nlevel = $level+1;
		if ($level==1) $size=50;
		else $size=25;
		
		$factmedia = array();
		if (is_string($factobj)) {
			$i = 1;
			do {
				$rec = GetSubRecord(2, "$level OBJE", $factobj, $i);
				if ($rec != "") $factmedia[] = $rec;
				$i++;
			} while ($rec != "");
		}
		elseif ($factobj->disp) {
			// We have a factobject as input, and must print the level 2 or higher media
			if ($level > 1 && $factobj->datatype == "sub") {
				$i = 1;
				do {
					$rec = GetSubRecord(2, "$level OBJE", $factobj->factrec, $i);
					if ($rec != "") $factmedia[] = $rec;
					$i++;
				} while ($rec != "");
			}
			// We have a main entity as input
			elseif ($level == 1 && $factobj->datatype != "sub") {
				foreach($factobj->facts as $key => $fact) {
					if ($fact->fact == "OBJE" && $fact->disp) {
						$factmedia[] = $fact->factrec;
					}
				}
			}
		}

		if (count($factmedia) > 0) {
			if (!$nobr) print "<br />";
			print "<!-- Start media link table //-->";
			print "\n<table class=\"FactsTable\">";
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
					else if ((preg_match("'://'", GedcomConfig::$MEDIA_DIRECTORY)>0)||($media->fileobj->f_file_exists)) {
						if ($media->fileobj->f_width > 0 && $media->fileobj->f_height > 0) {
							$imgwidth = $media->fileobj->f_width + 50;
							$imgheight = $media->fileobj->f_height + 50;
						}
					}
					if ($factobj->disp) {
						MediaFS::DispImgLink($media->fileobj->f_main_file, $media->fileobj->f_thumb_file, $media->title, "medialinks", 0, 0, $imgwidth, $imgheight, $media->fileobj->f_is_image, $media->fileobj->f_file_exists);
						print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\">";
						if ($TEXT_DIRECTION=="rtl" && !hasRTLText($media->title)) print "<i>&lrm;".PrintReady($media->title)."</i></a>";
						else print "<i>".PrintReady($media->title)."</i></a>";
		
						// NOTE: Print the format of the media
						if ($media->extension != "") {
							print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_FACT_FORM.": </span> <span class=\"FactDetailField\">".$media->extension."</span>";
							if ($media->fileobj->f_width != 0 && $media->fileobj->f_height != 0) {
								print "\n\t\t\t<span class=\"FactDetailLabel\"><br />".GM_LANG_image_size.": </span> <span class=\"FactDetailField\" style=\"direction: ltr;\">" . $media->fileobj->f_width . ($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ") . $media->fileobj->f_height . "</span>";
							}
						}
						$ttype = preg_match("/\d TYPE (.*)/", $media->gedrec, $match);
						if ($ttype>0){
							print "\n\t\t\t<br /><span class=\"FactDetailLabel\">".GM_LANG_type.": </span> <span class=\"FactDetailField\">$match[1]</span>";
						}
						print "<br />\n";
						if (PrivacyFunctions::showFact("NOTE", $media->xref) && PrivacyFunctions::showFactDetails("NOTE", $media->xref)) {
							$prtd = self::PrintFactNotes($media, 1, !$printed); // Level is 1 because the notes are subordinate to the linked record, NOT to the link!
						}
						else $prtd = true;
						if (PrivacyFunctions::showFact("SOUR", $media->xref) && PrivacyFunctions::showFactDetails("SOUR", $media->xref)) {
							self::PrintFactSources($media, 1, !$prtd); // Level is 1 because the sourcelinks are subordinate to the linked record, NOT to the link!
						}
					}
					print "</td></tr>";
				}
			 }
			 print "</table>\n";
			 print "<!-- End media link table //-->";
		 }
		 return $printed;
	}
	
	private function PrintResn(&$factobj) {
		
		if ($factobj->resnvalue != "") {
			PrintHelpLink("RESN_help", "qm");
			print PrintReady("<span class=\"FactDetailLabel\">".GM_FACT_RESN.": </span><span class=\"FactDetailField\">".constant("GM_LANG_".$factobj->resnvalue)."</span>")."\n";
		}
	}
			
	private function PrintFactTagBox(&$factobj, $mayedit) {
		 global $GM_IMAGES;
		 global $n_chil, $n_gchi;

		print "<td class=\"FactLabelCell ".$factobj->style."\">";
		if ($factobj->fact == "SOUR") {
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["large"]."\" class=\"FactLabelCellImage\" alt=\"\" /><br />";
			print "<span class=\"FactLabelCellText\">".GM_LANG_source."</span>";
			$edit_actions = array("edit_sour", "edit_sour", "copy_sour", "delete_sour");
		}
		else if ($factobj->fact == "NOTE") {
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" class=\"FactLabelCellImage\" alt=\"\" /><br />";
			print "<span class=\"FactLabelCellText\">".GM_LANG_note."</span>";
			$edit_actions = array("edit_note", "edit_note", "copy_note", "delete_note");
		}
		else if ($factobj->fact == "OBJE") {
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["media"]["large"]."\" class=\"FactLabelCellImage\" alt=\"\" /><br />";
			print "<span class=\"FactLabelCellText\">".GM_FACT_OBJE."</span>";
			$edit_actions = array("edit_media_link", "edit_media_link", "copy_media", "delete_media");
		}
		else if ($factobj->factref == "_HIST") {
			// We cannot edit historical events!
			print "<span class=\"FactLabelCellText\">".GM_FACT__HIST."</span>";
			$mayedit = false;
		}
		else {
			print "<span class=\"FactLabelCellText\">".$factobj->descr;
//			$label = preg_replace("/^X_/", "_", $factobj->factref);
//			if (defined("GM_FACT_".$label)) print constant("GM_FACT_".$label);
//			else print $label;
			if ($factobj->factref == "X_BIRT_CHIL" && isset($n_chil)) print "<br />".GM_LANG_number_sign.$n_chil++;
			if ($factobj->factref == "X_BIRT_GCHI" && isset($n_gchi)) print "<br />".GM_LANG_number_sign.$n_gchi++;
			$edit_actions = array("edit_fact", "edit_fact", "copy_fact", "delete_fact");
			print "</span>";
		}
		if ($factobj->canedit && $factobj->style!="ChangeOld" && !$factobj->isdeleted && !$factobj->owner->view && $mayedit) {
			$menu = array();
			$menu["label"] = GM_LANG_edit;
			$menu["labelpos"] = "right";
			$menu["icon"] = "";
			$menu["link"] = "#";
			$menu["onclick"] = "return edit_record('".$factobj->owner->xref."', '".$factobj->fact."', '".$factobj->count."', '".$edit_actions[0]."', '".$factobj->owner_type."');";
			$menu["class"] = "";
			$menu["hoverclass"] = "";
			$menu["flyout"] = "down";
			$menu["submenuclass"] = "submenu";
			$menu["items"] = array();
			$submenu = array();
			$submenu["label"] = GM_LANG_edit;
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
			$submenu["onclick"] = "return edit_record('".$factobj->owner->xref."', '".$factobj->fact."', '".$factobj->count."', '".$edit_actions[1]."', '".$factobj->owner_type."');";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			if (!stristr($factobj->factrec, "2 _GM")) {
				$submenu = array();
				$submenu["label"] = GM_LANG_copy;
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return copy_record('".$factobj->owner->xref."', '".$factobj->fact."', '".$factobj->count."', '".$edit_actions[2]."', '".$factobj->owner_type."');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
			}
			$submenu = array();
			$submenu["label"] = GM_LANG_delete;
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
//			$submenu["onclick"] = "return delete_record('".$factobj->owner->xref."', '".$factobj->fact."', '$count', '".$edit_actions[3]."');";
			$submenu["onclick"] = "if (confirm('".GM_LANG_check_delete."')) return delete_record('".$factobj->owner->xref."', '".$factobj->fact."', '".$factobj->count."', '".$edit_actions[3]."', '".$factobj->owner_type."'); else return false;";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			print "<div class=\"FactLabelCellEdit\">";
			self::PrintFactMenu($menu);
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
	public function PrintAssoRelaRecord($factobj, $pid, $linebr=false) {
		global $TEXT_DIRECTION, $GM_IMAGES;
		
		$prted = false;
		// get ASSOciate(s) ID(s)
		$ct = preg_match_all("/\d ASSO @(.*)@/", $factobj->factrec, $match, PREG_SET_ORDER);
		if ($linebr && $ct > 0) print "<br />";
		for ($i=0; $i<$ct; $i++) {
			$level = substr($match[$i][0],0,1);
			$pid2 = $match[$i][1];
			$asso =& Person::GetInstance($pid2);
			if ($asso->isempty) $asso =& Family::GetInstance($pid2); 
			if (!$asso->isempty) {
				// get RELAtionship field
				$assorec = GetSubRecord($level, " ASSO ", $factobj->factrec, $i+1);
				$rct = preg_match("/\d RELA (.*)/", $assorec, $rmatch);
				if ($rct>0) {
					// RELAtionship name in user language
					$key = strtolower(trim($rmatch[1]));
					if (defined("GM_LANG_".$key)) $rela = constant("GM_LANG_".$key);
					else $rela = $rmatch[1];
					$p = strpos($rela, "(=");
					if ($p>0) $rela = trim(substr($rela, 0, $p));
//					if ($pid2 == $pid) print "<span class=\"FactDetailLabel\">";
					print "<span class=\"FactDetailLabel AssoIndent\">";
					print $rela.": ";
//					if ($pid2 == $pid) print "</span>";
					print "</span>";
				}
				else $rela = GM_FACT_RELA; // default
	
				// ASSOciate ID link
				if ($asso->datatype == "INDI") {
					print "<a href=\"individual.php?pid=".$asso->xref."&amp;gedid=".$asso->gedcomid."\">" . $asso->name;
					if ($asso->addname != "") print " - " . PrintReady($asso->addname);
					print $asso->addxref;
					print "</a>";
					// ID age. The age and relationship links should only be printed if relevant, i.e. if the details of pid2 are not hidden.
					if ($asso->disp) {
						if (!strstr($factobj->factrec, "_BIRT_")) {
							$dct = preg_match("/2 DATE (.*)/", $factobj->factrec, $dmatch);
							if ($dct>0) print " <span class=\"FactAge\">".$asso->GetAge($dmatch[1])."</span>";
						}
						// RELAtionship calculation : for a family print relationship to both spouses
						if (!$asso->view) {
							$family =& Family::GetInstance($pid);
							if (!$family->isempty) {
								if ($family->husb_id != "" && $family->husb_id != $asso->xref) print " - <span class=\"FactAssoLink\"><a href=\"relationship.php?pid1=".$family->husb_id."&amp;pid2=".$asso->xref."&amp;followspouse=1&amp;gedid=".$asso->gedcomid."\">[" . GM_LANG_relationship_chart . "<img src=\"".GM_IMAGE_DIR."/" . $GM_IMAGES["sex"]["small"] . "\" title=\"" . GM_LANG_husband . "\" alt=\"" . GM_LANG_husband . "\" class=\"GenderImage\" />]</a></span>";
								if ($family->wife_id != "" && $family->wife_id != $asso->xref) print " - <span class=\"FactAssoLink\"><a href=\"relationship.php?pid1=".$family->wife_id."&amp;pid2=".$asso->xref."&amp;followspouse=1&amp;gedid=".$asso->gedcomid."\">[" . GM_LANG_relationship_chart . "<img src=\"".GM_IMAGE_DIR."/" . $GM_IMAGES["sexf"]["small"] . "\" title=\"" . GM_LANG_wife . "\" alt=\"" . GM_LANG_wife . "\" class=\"GenderImage\" />]</a></span>";
							}
							else if ($pid != $asso->xref) print " - <span class=\"FactAssoLink\"><a href=\"relationship.php?pid1=".$pid."&amp;pid2=".$asso->xref."&amp;followspouse=1&amp;gedid=".$asso->gedcomid."\">[" . GM_LANG_relationship_chart . "]</a></span>";
						}
					}
				}
				else if ($asso->disp) {
					print "<span class=\"FactAssoLink\"><a href=\"family.php?famid=".$pid2."&amp;gedid=".$asso->gedcomid."\">";
					print $asso->name;
					if ($asso->addname != "") print " - " . PrintReady($asso->addname);
					print $asso->addxref;
					print "</a></span>\n";
				}
				else {
					print "<span class=\"FactAssoLink\">".GM_LANG_unknown;
					if (GedcomConfig::$SHOW_ID_NUMBERS) print " <span dir=\"$TEXT_DIRECTION\">($pid2)</span>";
					print "</span>";
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
	
		if ($type == "SOUR") $addfacts = array_merge(self::CheckFactUnique(preg_split("/[, ;:]+/", GedcomConfig::$SOUR_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "SOUR"), preg_split("/[, ;:]+/", GedcomConfig::$SOUR_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if ($type == "REPO") $addfacts = array_merge(self::CheckFactUnique(preg_split("/[, ;:]+/", GedcomConfig::$REPO_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "REPO"), preg_split("/[, ;:]+/", GedcomConfig::$REPO_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if ($type == "INDI") $addfacts = array_merge(self::CheckFactUnique(preg_split("/[, ;:]+/", GedcomConfig::$INDI_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "INDI"), preg_split("/[, ;:]+/", GedcomConfig::$INDI_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if($type == "FAM") $addfacts = array_merge(self::CheckFactUnique(preg_split("/[, ;:]+/", GedcomConfig::$FAM_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "FAM"), preg_split("/[, ;:]+/", GedcomConfig::$FAM_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if($type == "OBJE") $addfacts = array_merge(self::CheckFactUnique(preg_split("/[, ;:]+/", GedcomConfig::$MEDIA_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "OBJE"), preg_split("/[, ;:]+/", GedcomConfig::$MEDIA_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else if($type == "NOTE") $addfacts = array_merge(self::CheckFactUnique(preg_split("/[, ;:]+/", GedcomConfig::$NOTE_FACTS_UNIQUE, -1, PREG_SPLIT_NO_EMPTY), $usedfacts, "NOTE"), preg_split("/[, ;:]+/", GedcomConfig::$NOTE_FACTS_ADD, -1, PREG_SPLIT_NO_EMPTY));
		else return;
		if (count($addfacts) == 0) return;
	
		usort($addfacts, "FactSort");
		print "<tr><td class=\"FactLabelCell\"><span class=\"FactLabelCellText\">";
		PrintHelpLink("add_new_facts_help", "qm");
		print GM_LANG_add_fact."</span>";
		if (isset($_SESSION["clipboard"]) && count($_SESSION["clipboard"]) != 0) {
			print "<span class id=\"clear_clipboard\"><br />";
			print "<a href=\"#\" onclick=\"sndReq('clear_clipboard', 'clear_clipboard', true); window.location.reload(); return false;\">";
			print GM_LANG_clear_clipboard;
			print "</a></span>";
		}
		print "</td>";
		print "<td class=\"FactDetailCell\">";
		print "<form method=\"get\" name=\"newfactform\" action=\"\">\n";
		print "<select id=\"newfact\" name=\"newfact\">\n";
		foreach($addfacts as $indexval => $fact) {
	  		print PrintReady("<option value=\"$fact\">".constant("GM_FACT_".$fact). " [".$fact."]</option>\n");
		}
		if (($type == "INDI") || ($type == "FAM")) print "<option value=\"EVEN\">".GM_LANG_custom_event." [EVEN]</option>\n";
		if (!empty($_SESSION["clipboard"])) {
			foreach($_SESSION["clipboard"] as $key=>$fact) {
				if ($fact["type"] == $type) {
					if (defined("GM_FACT_".$fact["fact"])) print "<option value=\"clipboard_$key\">".GM_LANG_add_from_clipboard." ".constant("GM_FACT_".$fact["fact"])."</option>\n";
					else print "<option value=\"clipboard_$key\">".GM_LANG_add_from_clipboard." ".$fact["fact"]."</option>\n";
				}
			}
		}
		print "</select>";
		print "&nbsp;<input type=\"button\" value=\"".GM_LANG_add."\" onclick=\"add_record('$id', 'newfact', 'newfact', '".$type."');\" />\n";
		
		// Print the quick add fact links
		$qfacts = array();
		if ($type == "INDI" && !empty(GedcomConfig::$INDI_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", GedcomConfig::$INDI_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "FAM" && !empty(GedcomConfig::$FAM_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", GedcomConfig::$FAM_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "REPO" && !empty(GedcomConfig::$REPO_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", GedcomConfig::$REPO_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "SOUR" && !empty(GedcomConfig::$SOUR_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", GedcomConfig::$SOUR_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "OBJE" && !empty(GedcomConfig::$MEDIA_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", GedcomConfig::$MEDIA_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		else if ($type == "NOTE" && !empty(GedcomConfig::$NOTE_QUICK_ADDFACTS)) $qfacts = preg_split("/[, ;:]+/", GedcomConfig::$NOTE_QUICK_ADDFACTS, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($qfacts as $key => $qfact) {
			if (in_array($qfact, $addfacts)) print "&nbsp;&nbsp;<a href=\"javascript: ".constant("GM_FACT_".$qfact)."\" onclick=\"add_new_record('".$id."', '".$qfact."', 'newfact', '".$type."'); return false;\">".constant("GM_FACT_".$qfact)."</a>";
		}
		print "</form>\n";
		print "</td></tr>\n";
	}
		
	/**
	 * print an address structure
	 *
	 * takes a gedcom ADDR structure and prints out a human readable version of it.
	 * @param string $factrec	The ADDR subrecord
	 * @param int $level		The gedcom line level of the main ADDR record
	 */
	public function PrintAddressStructure($object, $level, $br=false) {
		
		//	 $POSTAL_CODE = 'false' - before city, 'true' - after city and/or state
		//-- define per gedcom till can do per address countries in address languages
		//-- then this will be the default when country not recognized or does not exist
		//-- both Finland and Suomi are valid for Finland etc.
		//-- see http://www.bitboost.com/ref/international-address-formats.html
		
		if ($object->datatype != "sub") {
			$facts = $object->SelectFacts(array("ADDR", "WWW", "URL", "FAX", "EMAIL", "PHON"));
			if (count($facts) == 0) return 0;
			$found = false;
			foreach ($facts as $key => $factobj) {
				$found = self::PrintAddressStructure($factobj, 1, ($found || $br)) || $found;
			}
			return $found;
		}
		
		$hasany = preg_match("/$level (WWW|URL|FAX|EMAIL|PHON|ADDR)/", $object->factrec);
		$hasmore = preg_match("/$level (WWW|URL|FAX|EMAIL|PHON)/", $object->factrec);
		if ($br && $hasany) print "<br />";
		$firstline = true;
		
		$nlevel = $level+1;
		$ct = preg_match_all("/$level ADDR(.*)/", $object->factrec, $omatch, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$firstline = false;
	 		$arec = GetSubRecord($level, "$level ADDR", $object->factrec, $i+1);
	 		if ($level>1) print "\n\t\t<span class=\"FactDetailLabel\">".GM_FACT_ADDR.": </span><br /><div class=\"Indent\">";
	 		print "<span class=\"FactDetailField\">";
			$cn = preg_match("/$nlevel _NAME (.*)/", $arec, $cmatch);
			if ($cn>0) print str_replace("/", "", $cmatch[1])."<br />\n";
			if (strlen(trim($omatch[$i][1])) > 0 && $cn > 0) print "<br />";
			print PrintReady(trim($omatch[$i][1]));
			$cont = GetCont($nlevel, $arec);
			if (!empty($cont)) print PrintReady($cont);
			else {
				if (strlen(trim($omatch[$i][1])) > 0) print "<br />";
				$cs = preg_match("/$nlevel ADR1 (.*)/", $arec, $cmatch);
				if ($cs>0) {
					if ($cn==0) {
						print "<br />";
						$cn=0;
					}
					print PrintReady($cmatch[1]);
				}
				$cs = preg_match("/$nlevel ADR2 (.*)/", $arec, $cmatch);
				if ($cs>0) {
					if ($cn==0) {
						print "<br />";
						$cn=0;
					}
					print PrintReady($cmatch[1]);
				}
	
				if (!GedcomConfig::$POSTAL_CODE) {
					$cs = preg_match("/$nlevel POST (.*)/", $arec, $cmatch);
					if ($cs>0) {
						print "<br />";
					  	print PrintReady($cmatch[1]);
					}
					$cs = preg_match("/$nlevel CITY (.*)/", $arec, $cmatch);
					if ($cs>0) {
						print " ".PrintReady($cmatch[1]);
					}
					$cs = preg_match("/$nlevel STAE (.*)/", $arec, $cmatch);
					if ($cs>0) {
						print ", ".PrintReady($cmatch[1]);
					}
				}
				else {
					$cs = preg_match("/$nlevel CITY (.*)/", $arec, $cmatch);
					if ($cs>0) {
						print "<br />";
						print PrintReady($cmatch[1]);
					}
					$cs = preg_match("/$nlevel STAE (.*)/", $arec, $cmatch);
					if ($cs>0) {
						print ", ".PrintReady($cmatch[1]);
					}
	 				$cs = preg_match("/$nlevel POST (.*)/", $arec, $cmatch);
	 				if ($cs>0) {
	 					print " ".PrintReady($cmatch[1]);
	 				}
				}
				$cs = preg_match("/$nlevel CTRY (.*)/", $arec, $cmatch);
				if ($cs>0) {
					print "<br />";
					print PrintReady($cmatch[1]);
				}
			}
			if ($level>1) print "</div>\n";
			print "</span>";
			$firstline = false;
			if ($hasmore && $level == 1) print "<br />";
		}
		$ct = preg_match_all("/$level PHON (.*)/", $object->factrec, $omatch, PREG_SET_ORDER);
		if ($ct>0) {
			  for($i=0; $i<$ct; $i++) {
				  if (!$firstline) print "<br />";
				  else $firstline = false;
				   if ($level>1) print "\n\t\t<span class=\"FactDetailLabel\">".GM_FACT_PHON.": </span><span class=\"FactDetailField\">";
				   print "&lrm;".$omatch[$i][1]."&lrm;";
				   if ($level>1) print "</span>\n";
			  }
		 }
		 $ct = preg_match_all("/$level EMAIL (.*)/", $object->factrec, $omatch, PREG_SET_ORDER);
		 if ($ct>0) {
			  for($i=0; $i<$ct; $i++) {
				  if (!$firstline) print "<br />";
				  else $firstline = false;
				   if ($level>1) print "\n\t\t<span class=\"FactDetailLabel\">".GM_FACT_EMAIL.": </span><span class=\"FactDetailField\">";
				   print "<a href=\"mailto:".$omatch[$i][1]."\">".$omatch[$i][1]."</a>\n";
				   if ($level>1) print "</span>\n";
			  }
		 }
		 $ct = preg_match_all("/$level FAX (.*)/", $object->factrec, $omatch, PREG_SET_ORDER);
		 if ($ct>0) {
			  for($i=0; $i<$ct; $i++) {
				  if (!$firstline) print "<br />";
				  else $firstline = false;
				   if ($level>1) print "\n\t\t<span class=\"FactDetailLabel\">".GM_FACT_FAX.": </span><span class=\"FactDetailField\">";
	 			   print "&lrm;".$omatch[$i][1]."&lrm;";
				   if ($level>1) print "</span>\n";
			  }
		 }
		 $ct = preg_match_all("/$level (WWW|URL) (.*)/", $object->factrec, $omatch, PREG_SET_ORDER);
		 if ($ct>0) {
			  for($i=0; $i<$ct; $i++) {
				  if (!$firstline) print "<br />";
				  else $firstline = false;
				   if ($level>1) print "\n\t\t<span class=\"FactDetailLabel\">".GM_FACT_URL.": </span><span class=\"FactDetailField\">";
				   print "<a href=\"".(!preg_match('/^http/', $omatch[$i][2]) ? "http://" : "") . $omatch[$i][2]."\" target=\"_blank\">".$omatch[$i][2]."</a>\n";
				   if ($level>1) print "</span>\n";
			  }
		 }
		 return $hasany;
	}
	
	/**
	 * print a submitter record
	 *
	 * find and print submitter information
	 * @param string $sid  the Gedcom Xref ID of the submitter to print
	 */
	private function PrintSubmitterInfo($subm, $br=false) {

		if ($br) print "<br />"; 
		print "<span class=\"FactDetailField\">".$subm->name."</span><br />";
		self::PrintAddressStructure($subm, 1);
		self::PrintFactMedia($subm, 1);
	}
	
	public function PrintSimpleFact($factobj, $print_parents_age=false, $print_age_at_event=false) {
		
		if (!is_object($factobj)) return false;
		
		$emptyfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","EVEN","MARR","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","OBJE","CHAN","_SEPR","RESI", "DATA", "MAP");
		
		if ($factobj->factrec != "1 DEAT"){
		   print "<span class=\"FactDetailLabel\">".$factobj->descr."</span> ";
		}
		if ($factobj->disp) {
			if (!in_array($factobj->fact, $emptyfacts)) {
				$ct = preg_match("/1 $factobj->fact(.*)/", $factobj->factrec, $match);
				if ($ct>0) print "<span class=\"FactDetailLabel\">".PrintReady(trim($match[1]))."</span>";
			}
			print "<span class=\"FactDetailField\">";
			$factobj->PrintFactDate(false, false, $print_parents_age, $print_age_at_event);
			$factobj->PrintFactPlace();
			print "</span>";
		}
		else print "<span class=\"FactDetailField\">".GM_LANG_private."</span>";
		print "<br />\n";
	}
	
	/**
	 * Check for facts that may exist only once for a certain record type.
	 * If the fact already exists in the second array, delete it from the first one.
	 */
	 private function CheckFactUnique($uniquefacts, $recfacts, $type) {
	
		foreach($recfacts as $indexval => $fact) {
	
			if ($fact->fact != "") {
				$key = array_search($fact->fact, $uniquefacts);
				if ($key !== false) unset($uniquefacts[$key]);
			}
		}
		return $uniquefacts;
	}

	/**
	 * format a fact for calendar viewing
	 *
	 * @param string $factrec the fact record
	 * @param string $action tells what type calendar the user is viewing
	 * @param string $filter should the fact be filtered by living people etc
	 * @param string $filterev "all" to show all events; "bdm" to show only Births, Deaths, Marriages; Event code to show only that event
	 * @return string a html text string that can be printed
	 */
	public function GetCalendarFact($factobj, $action, $filterof, $filterev="all", $year="", $month="", $day="", $CalYear="", $currhYear="") {
		global $TEMPLE_CODES, $monthtonum, $TEXT_DIRECTION, $caltype;
		
		$Upcoming = false;
		if ($action == "upcoming") {
			$action = "today";
			$Upcoming = true;
		}
	
		$skipfacts = array("CHAN", "BAPL", "SLGC", "SLGS", "ENDL");
		$BDMfacts = array("BIRT", "DEAT", "MARR");
	
		if (in_array($factobj->fact, $skipfacts)) return "filter";
	
		if (!$factobj->disp || !$factobj->show) return "";
		
		// Use current year for age in dayview
		if ($action == "today"){
			$yearnow = getdate();
			$yearnow = $yearnow["year"];
		}
		else	{
			$yearnow = intval($year);
		}
	
		$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factobj->factrec, $match);
		if ($hct>0 && GedcomConfig::$USE_RTL_FUNCTIONS)
			if ($action == "today") $yearnow = $currhYear;
			else $yearnow = $CalYear;
	
		$text = "";
	
		// See whether this Fact should be filtered out or not
		$Filtered = false;
		if ($filterev == "bdm") {
			if (!in_array($factobj->fact, $BDMfacts) && !in_array($factobj->factref, $BDMfacts)) $Filtered = true;
		}
		if ($filterev != "all" && $filterev != "bdm") {
			if ($factobj->fact != $filterev && $factobj->factref != $filterev) $Filtered = true;
		}
	
		if (!$Filtered) {
			if ($factobj->fact == "EVEN" || $factobj->fact == "FACT") {
				if ($factobj->factref != "") {
					if (defined("GM_FACT_".$factobj->factref)) $text .= constant("GM_FACT_".$factobj->factref);
					else $text .= $factobj->factref;
				}
				else $text .= constant("GM_FACT_".$factobj->fact);
			}
			else {
				if (defined("GM_FACT_".$factobj->fact)) $text .= constant("GM_FACT_".$factobj->fact);
				else $text .= $factobj->fact;
			}
	
			if ($text!="") $text=PrintReady($text);
			
			$ct = preg_match("/\d DATE(.*)/", $factobj->factrec, $match);
			if ($ct>0) {
				$text .= " - <span class=\"Date\">".GetDateUrl($match[1], $CalYear)."</span>";
				$yt = preg_match("/ (\d\d\d\d|\d\d\d)/", $match[1], $ymatch);
				if ($yt>0) {
					$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $match[1], $hmatch);
		            if ($hct>0 && GedcomConfig::$USE_RTL_FUNCTIONS && $action=='today')
	                   $age = $currhYear - intval($ymatch[1]);
					else
					   $age = $yearnow - intval($ymatch[1]);
					$yt2 = preg_match("/(...) (\d\d\d\d|\d\d\d)/", $match[1], $bmatch);
					if ($yt2>0) {
						if (isset($monthtonum[strtolower(trim($bmatch[1]))])) {
							$emonth = $monthtonum[strtolower(trim($bmatch[1]))];
							if (!$Upcoming && isset($monthtonum[strtolower($month)]) && ($emonth < $monthtonum[strtolower($month)])) $age--;
							$bt = preg_match("/(\d+) ... (\d\d\d\d|\d\d\d)/", $match[1], $bmatch);
							if ($bt>0) {
								$edate = trim($bmatch[1]);
								if (!$Upcoming && ($edate<$day)) $age--;
							}
						}
					}
					$yt3 = preg_match("/(.+) ... (\d\d\d\d|\d\d\d)/", $match[1], $bmatch);
					if ($yt3>0) {
						if (!$Upcoming && ($bmatch[1]>$day)) $age--;
					}
					if ($filterof == "recent" && $age > 100) return "filter";
					// Limit facts to before the given year in monthview
					if ($age < 0 && $action == "calendar") return "filter";
					if ($action != 'year'){
						$text .= " (" . str_replace("#year_var#", ConvertNumber($age), GM_LANG_year_anniversary).")";
					}
	 				if($TEXT_DIRECTION == "rtl"){
	 					$text .= "&lrm;";
	 				}
				}
				if ($action == 'today' || $action == 'year') {
					// -- find place for each fact
					if (GedcomConfig::$SHOW_PEDIGREE_PLACES > 0) {
						$ct = preg_match("/2 PLAC (.*)/", $factobj->factrec, $match);
						if ($ct>0) {
							$text .= ($action == 'today' ? "<br />" : " ");
							$plevels = preg_split("/,/", $match[1]);
							$plactext = "";
							for($plevel=0; $plevel < GedcomConfig::$SHOW_PEDIGREE_PLACES; $plevel++) {
								if (!empty($plevels[$plevel])) {
									if ($plevel > 0) $plactext .= ", ";
									$plactext .= PrintReady($plevels[$plevel]);
								}
							}
							if (NameFunctions::HasChinese($plactext)) $plactext .= PrintReady(" (".NameFunctions::GetPinYin($plactext).")");
							else if (NameFunctions::HasCyrillic($plactext)) $plactext .= PrintReady(" (".NameFunctions::GetTransliterate($plactext).")");
							$text .= PrintReady($plactext);
						}
					}
	
					// -- find temple code for lds facts
					$ct = preg_match("/2 TEMP (.*)/", $factobj->factrec, $match);
					if ($ct>0) {
						$tcode = $match[1];
						$tcode = trim($tcode);
						if (array_key_exists($tcode, $TEMPLE_CODES)) $text .= "<br />".GM_LANG_temple.": ".$TEMPLE_CODES[$tcode];
						else $text .= "<br />".GM_LANG_temple_code.$tcode;
					}
				}
			}
			$text .= "<br />";
		}
		if ($text=="") return "filter";
	
		return $text;
	}
	
	/**
	 * prints a JavaScript popup menu
	 *
	 * This function will print the DHTML required
	 * to create a JavaScript Popup menu.  The $menu
	 * parameter is an array that looks like this
	 * $menu["label"] = "Charts";
	 * $menu["labelpos"] = "down"; // tells where the text should be positioned relative to the picture options are up down left right
	 * $menu["icon"] = "images/pedigree.gif";
	 * $menu["hovericon"] = "images/pedigree2.gif";
	 * $menu["link"] = "pedigree.php";
	 * $menu["accesskey"] = "Z"; // optional accesskey
	 * $menu["class"] = "menuitem";
	 * $menu["hoverclass"] = "menuitem_hover";
	 * $menu["flyout"] = "down"; // options are up down left right
	 * $menu["items"] = array(); // an array of like menu items
	 * $menu["onclick"] = "return javascript";  // java script to run on click
	 * @author Genmod Development Team
	 * @param array $menu the menuitems array to print
	 */
	public function PrintFactMenu($menu, $parentmenu="") {
		$conv = array(
			'label'=>'label',
			'labelpos'=>'labelpos',
			'icon'=>'icon',
			'hovericon'=>'hovericon',
			'link'=>'link',
			'accesskey'=>'accesskey',
			'class'=>'class',
			'hoverclass'=>'hoverclass',
			'flyout'=>'flyout',
			'submenuclass'=>'submenuclass',
			'onclick'=>'onclick'
		);
		$obj = new Menu();
		if ($menu == 'separator') {
			$obj->isSeperator();
			$obj->printMenu();
			return;
		}
		$items = false;
		foreach ($menu as $k=>$v) {
			if ($k == 'items' && is_array($v) && count($v) > 0) $items = $v;
			else {
				if (isset($conv[$k])){
					if ($v != '') {
						$obj->{$conv[$k]} = $v;
					}
				}
			}
		}
		if ($items !== false) {
			foreach ($items as $sub) {
				$sobj = new Menu();
				if ($sub == 'separator') {
					$sobj->isSeperator();
					$obj->addSubmenu($sobj);
					continue;
				}
				foreach ($sub as $k2=>$v2) {
					if (isset($conv[$k2])) {
						if ($v2 != '') {
							$sobj->{$conv[$k2]} = $v2;
						}
					}
				}
				$obj->addSubmenu($sobj);
			}
		}
		$obj->printMenu();
	}
	
	/**
	 * translate gedcom age string
	 *
	 * Examples:
	 * 4y 8m 10d.
	 * Chi
	 * INFANT
	 *
	 * @param string $agestring gedcom AGE field value
	 * @return string age in user language
	 * @see http://homepages.rootsweb.com/~pmcbride/gedcom/55gcch2.htm#AGE_AT_EVENT
	 */
	
	public function GetAgeAtEvent($agestring) {
	
		$age = "";
		$match = explode(" ", strtolower($agestring));
		for ($i=0; $i<count($match); $i++) {
			$txt = trim($match[$i]);
			$txt = trim($txt, ".");
			if ($txt=="chi") $txt="child";
			if ($txt=="inf") $txt="infant";
			if ($txt=="sti") $txt="stillborn";
			if (defined("GM_LANG_".$txt)) $age .= constant("GM_LANG_".$txt);
			else {
				$n = trim(substr($txt,0,-1));
				$u = substr($txt,-1,1);
				if ($u=="y") {
					$age.= " ".$n." ";
					if ($n == 1) $age .= GM_LANG_year1;
					else $age .= GM_LANG_years;
				}
				else if ($u=="m") {
					$age.= " ".$n." ";
					if ($n == 1) $age .= GM_LANG_month1;
					else $age .= GM_LANG_months;
				}
				else if ($u=="d") {
					$age.= " ".$n." ";
					if ($n == 1) $age .= GM_LANG_day1;
					else $age .= GM_LANG_days;
				}
				else $age.=" ".$txt;
			}
		}
		return $age;
	}

	private function ExpandUrl($text) {
	  // Some versions of RFC3987 have an appendix B which gives the following regex
	  // (([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?
	  // This matches far too much while a "precise" regex is several pages long.
	  // This is a compromise.
	  $URL_REGEX='((https?|ftp]):)(//([^\s/?#<\)\,]*))?([^\s?#<\)\,]*)(\?([^\s#<\)\,]*))?(#(\S*))?';
	
	  return preg_replace_callback(
	    '/'.addcslashes("(?!>)$URL_REGEX(?!</a>)", '/').'/i',
	    create_function( // Insert <wbr/> codes into the replaced string
	      '$m',
	      'if (strlen($m[0])>30) $url = substr($m[0],0,30).".....";
	      else $url = $m[0];
	      return "<a href=\"".$m[0]."\" target=\"blank\">".preg_replace("/\b/", "<wbr/>", $url)."</a>";'
	    ),
	    $text
	  );
	}

	
}
?>
