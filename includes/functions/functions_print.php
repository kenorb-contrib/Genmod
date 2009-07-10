<?php
/**
 * Function for printing
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
/**
 * print a submitter record
 *
 * find and print submitter information
 * @param string $sid  the Gedcom Xref ID of the submitter to print
 */
function print_submitter_info($sid) {
	 $srec = FindGedcomRecord($sid);
	 preg_match("/1 NAME (.*)/", $srec, $match);
	 // PAF creates REPO record without a name
	 // Check here if REPO NAME exists or not
	 if (isset($match[1])) print "$match[1]<br />";
	 print_address_structure($srec, 1);
	 print_media_links($srec, 1);
}
/**
 * print a repository record
 *
 * find and print repository information attached to a source
 * @param string $sid  the Gedcom Xref ID of the repository to print
 */
function print_repository_record($sid) {
	global $gm_lang, $show_changes, $GEDCOM;
	
	$prt = false;
	if ($show_changes && GetChangeData(true, $sid, true)) {
		$rec = GetChangeData(false, $sid, true, "gedlines");
		$repo = $rec[$GEDCOM][$sid];
	}
	else $repo = FindGedcomRecord($sid);
	$ct = preg_match("/1 NAME (.*)/", $repo, $match);
	if ($ct > 0) {
		$ct2 = preg_match("/0 @(.*)@/", $repo, $rmatch);
		if ($ct2>0) $rid = trim($rmatch[1]);
		print "<span class=\"label\">".$gm_lang["repo_name"]."</span><br /><span class=\"field\"><a href=\"repo.php?rid=$rid\">".PrintReady($match[1])."</a></span>";
		$prt = true;
	}
	$prt = print_address_structure($repo, 1, $prt) || $prt;
	print_fact_notes($repo, 1);
	return $prt;
}
/**
 * print a person in a list
 *
 * This function will print a
 * clickable link to the individual.php
 * page with the person's name
 * lastname, firstname and their
 * birthplace and date
 * @param string $key the GEDCOM xref id of the person to print
 * @param array $value is an array of the form array($name, $GEDCOM)
 */
function print_list_person($key, $value, $findid=false, $asso="", $useli=true, $fact="") {
	global $gm_lang, $SCRIPT_NAME, $pass, $indi_private, $indi_hide, $indi_total, $factarray, $NAME_REVERSE, $Privacy;
	global $GEDCOM, $GEDCOMS, $GEDCOMID, $SHOW_ID_NUMBERS, $TEXT_DIRECTION, $SHOW_PEDIGREE_PLACES, $GM_IMAGE_DIR, $GM_IMAGES, $SHOW_DEATH_LISTS;
	
	$key = splitkey($key, "id");
	SwitchGedcom($value[1]);
	
	if (!isset($indi_private)) $indi_private=array();
	if (!isset($indi_hide)) $indi_hide=array();
	if (!isset($indi_total)) $indi_total=array();
	$indi_total[$key."[".$GEDCOM."]"] = 1;

	$disp = displayDetailsByID($key);
	if ($disp) $disp2 = true;
	else $disp2 = showLivingNameByID($key);
	if ($disp2 || $disp) {
		if ($useli) {
			if (begRTLText($value[0]))                            //-- For future use
				 print "<li class=\"rtl\" dir=\"rtl\">";
			else print "<li class=\"ltr\" dir=\"ltr\">";
		}
//		if ($NAME_REVERSE || HasChinese($value[0])) $value[0] = str_replace(", ", "", $value[0]);
		if ($findid == true) {
			print "<a href=\"#\" onclick=\"pasteid('".$key."', '".urlencode(preg_replace("/'/", "\\'", PrintReady($value[0])));
			if ($disp) print "<br />".urlencode(print_first_major_fact($key, "", false));
			print "'); return false;\" class=\"list_item\"><b>";
			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
			else print PrintReady($value[0]);
			print "</b>";
		}
		else {
			print "<a href=\"individual.php?pid=$key&amp;ged=$value[1]\" class=\"list_item\"";
			if (!empty($fact)) print " target=\"blank\" ";
			print "><b>";
//			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
//			else print PrintReady($value[0]);
			print PrintReady($value[0]);
			print "</b>";
		}
		if ($SHOW_ID_NUMBERS){
		   if ($TEXT_DIRECTION=="ltr") print " <span dir=\"ltr\">($key)</span>";
  		   else print " <span dir=\"rtl\">($key)</span>";
		}

		if (!$disp) {
			print " -- <i>".$gm_lang["private"]."</i>";
			$indi_private[$key."[".$GEDCOM."]"] = 1;
		}
		else {
			$pfact = print_first_major_fact($key);
			if (isset($SHOW_DEATH_LISTS) && $SHOW_DEATH_LISTS==true) {
				if ($pfact!="DEAT") {
					$indirec = FindPersonRecord($key);
					$factrec = GetSubRecord(1, "1 DEAT", $indirec);
					if (strlen($factrec)>7 && showFact("DEAT", $key, "INDI") && !FactViewRestricted($key, $factrec)) {
						print " -- <i>";
						print $factarray["DEAT"];
						print " ";
						print_fact_date($factrec);
						print_fact_place($factrec);
						print "</i>";
					}
				}
			}
		}
		if (!empty($fact)) {
			print " <i>(";
			if (isset($factarray[$fact])) print $factarray[$fact];
			else print $fact;
			print ")</i>";
		}
		print "</a>";
		if (is_array($asso) && ($disp)) {
			foreach ($asso as $akey => $avalue) {
				$newged = splitkey($avalue[0], "ged");
				SwitchGedcom($newged);
				$key = splitkey($avalue[0], "id");
				if ($avalue[1] == "indi") {
					$name = GetPersonName($key);
					print "<br /><a href=\"individual.php?pid=$key&amp;ged=$GEDCOM\" title=\"$name\" class=\"list_item\">";
  				}
  				else {
					$name = GetFamilyDescriptor($key);
					print "<br /><a href=\"family.php?famid=$key&amp;ged=$GEDCOM\" title=\"$name\" class=\"list_item\">";
				}
				if ($TEXT_DIRECTION=="ltr") print " <span dir=\"ltr\">";
				else print " <span dir=\"rtl\">";
				print "(".$gm_lang["associate_with"]." ";
				if ($SHOW_ID_NUMBERS) print $key;
				print ": ".$name;
				if (!empty($avalue[2]) || !empty($avalue[3])) {
					print " - ";
					if (!empty($avalue[2])) print $factarray[$avalue[2]];
					if(!empty($avalue[2]) && !empty($avalue[3])) print " : ";
					if (isset($gm_lang[$avalue[3]])) print $gm_lang[$avalue[3]];
					else print $avalue[3];
				}
				print ")</span></a>";
	  			SwitchGedcom();
			}
		}

		if ($useli) print "</li>";
	}
	else {
		$pass = TRUE;
		$indi_hide[$key."[".$GEDCOM."]"] = 1;
	}
	SwitchGedcom();
}

//-- print information about a family for a list view
// param fact is for sanitycheck to print the fact and open a new page in a new window.
function print_list_family($key, $value, $findid=false, $asso="", $useli=true, $fact="") {
	global $gm_lang, $pass, $fam_private, $fam_hide, $fam_total, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS;
	global $GEDCOM, $GEDCOMS, $GEDCOMID, $HIDE_LIVE_PEOPLE, $SHOW_PEDIGREE_PLACES;
	global $TEXT_DIRECTION, $COMBIKEY, $Privacy, $factarray;

	SwitchGedcom($value[1]);
	
	if (!isset($fam_private)) $fam_private=array();
	if (!isset($fam_hide)) $fam_hide=array();
	if (!isset($fam_total)) $fam_total=array();
	$fam_total[$key."[".$GEDCOM."]"] = 1;
	$famrec=FindFamilyRecord($key);
	$display = displayDetailsByID($key, "FAM");
	//print "display: ".$display." key: ".$key." famrec: ".$famrec;
	$showLivingHusb=true;
	$showLivingWife=true;
	$parents = FindParents($key);
	//-- check if we can display both parents
	if (!$display) {
		if (!FactViewRestricted($key, $famrec, 1)) {
			$showLivingHusb=showLivingNameByID($parents["HUSB"]);
			$showLivingWife=showLivingNameByID($parents["WIFE"]);
		}
	}
	if ($showLivingWife && $showLivingHusb) {
		$kid = SplitKey($key, "id");
		if ($useli) {
			if (begRTLText($value[0]))                            //-- For future use
				 print "<li class=\"rtl\" dir=\"rtl\">";
			else print "<li class=\"ltr\" dir=\"ltr\">";
		}
		if ($findid == true) {
			print "<a href=\"#\" onclick=\"pasteid('".$kid."'); return false;\" class=\"list_item\"><b>";
//			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
//			else print PrintReady($value[0]);
			print PrintReady($value[0]);
			print "</b>";
		}
		else {
			print "<a href=\"family.php?famid=$kid&amp;ged=$value[1]\" class=\"list_item\"";
			if (!empty($fact)) print " target=\"blank\" ";
			print "><b>";
//			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
//			else print PrintReady($value[0]);
			print PrintReady($value[0]);
			print "</b>";
		}
		if ($SHOW_FAM_ID_NUMBERS) {
			if ($TEXT_DIRECTION=="ltr")	print " <span dir=\"ltr\">($kid)</span>";
  			else print " <span dir=\"rtl\">($kid)</span>";
			}
		if (!$display) {
			print " -- <i>".$gm_lang["private"]."</i>";
			$fam_private[$key."[".$GEDCOM."]"] = 1;
		}
		else {
			$bpos1 = strpos($famrec, "1 MARR");
			if ($bpos1) {
				$birthrec = GetSubRecord(1, "1 MARR", $famrec);
				if (!FactViewRestricted($key, $birthrec) && ShowFact("MARR", $kid)) {
					print " -- <i>".$gm_lang["marriage"]." ";
					$bt = preg_match("/1 \w+/", $birthrec, $match);
					if ($bt>0) {
						 $bpos2 = strpos($birthrec, $match[0]);
						 if ($bpos2) $birthrec = substr($birthrec, 0, $bpos2);
					}
					print_fact_date($birthrec);
					print_fact_place($birthrec);
				}
				print "</i>";
			}
		}
		if (!empty($fact)) {
			print " <i>(";
			if (isset($factarray[$fact])) print $factarray[$fact];
			else print $fact;
			print ")</i>";
		}
		print "</a>";
		if (is_array($asso) && ($display)) {
			foreach ($asso as $akey => $avalue) {
				$newged = splitkey($avalue[0], "ged");
				SwitchGedcom($newged);
				$key = splitkey($avalue[0], "id");
				if ($avalue[1] == "indi") {
					$name = GetPersonName($key);
					print "<br /><a href=\"individual.php?pid=$key&amp;ged=$GEDCOM\" title=\"$name\" class=\"list_item\">";
  				}
  				else {
					$name = GetFamilyDescriptor($key);
					print "<br /><a href=\"family.php?famid=$key&amp;ged=$GEDCOM\" title=\"$name\" class=\"list_item\">";
				}
				if ($TEXT_DIRECTION=="ltr") print " <span dir=\"ltr\">";
				else print " <span dir=\"rtl\">";
				print "(".$gm_lang["associate_with"]." ";
				if ($SHOW_ID_NUMBERS) print $key;
				print ": ".$name;
				if(!empty($avalue[2]) || !empty($avalue[3])) {
					print " - ";
					if (!empty($avalue[2])) print $factarray[$avalue[2]];
					if(!empty($avalue[2]) && !empty($avalue[3])) print " : ";
					if (isset($gm_lang[$avalue[3]])) print $gm_lang[$avalue[3]];
					else print $avalue[3];
				}
				print ")</span></a>";
	  			SwitchGedcom();
			}
		}
		if ($useli) print "</li>\n";
	}															//begin re-added by pluntke
	else {				   	//fixed THIS line (changed && to ||)
		$pass = true;
		$fam_hide[$key."[".$GEDCOM."]"] = 1;
	}															//end re-added by pluntke
	SwitchGedcom();
}

// Prints the information for a source in a list view
function print_list_source($key, $value) {
	global $GEDCOM, $GEDCOMS, $GEDCOMID, $source_total, $source_hide, $SHOW_SOURCES, $SHOW_ID_NUMBERS, $GEDCOM, $TEXT_DIRECTION, $Privacy;
	
	SwitchGedcom($value["gedfile"]);
	if (!isset($source_total)) $source_total=array();
	$source_total[$key."[".$GEDCOM."]"] = 1;

	if (displayDetailsByID($key, "SOUR", 1, true)) {
		if (begRTLText($value["name"])) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		print "\n\t\t\t<a href=\"source.php?sid=$key&amp;ged=".get_gedcom_from_id($value["gedfile"])."\" class=\"list_item\">".PrintReady($value["name"]);
		if ($SHOW_ID_NUMBERS) {
		if ($TEXT_DIRECTION=="ltr") print " &lrm;($key)&lrm;";
		else print " &rlm;($key)&rlm;";
	}
	print "</a>\n";
	print "</li>\n";
	}
	else $source_hide[$key."[".$GEDCOM."]"] = 1;
	
	SwitchGedcom();

}
// Prints the information for media in a list view
function print_list_media($key, $value, $skippriv=false) {
	global $GEDCOM, $GEDCOMS, $GEDCOMID, $media_total, $media_hide, $SHOW_ID_NUMBERS, $GEDCOM, $TEXT_DIRECTION, $Privacy;
	
	SwitchGedcom($value["gedfile"]);
	if (!isset($media_total)) $media_total=array();
	$media_total[$key."[".$GEDCOM."]"] = 1;
	if ($skippriv || displayDetailsByID($key, "OBJE", 1, true)) {
		if (begRTLText($value["name"])) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		print "\n\t\t\t<a href=\"mediadetail.php?mid=$key&amp;ged=".get_gedcom_from_id($value["gedfile"])."\" class=\"list_item\">".PrintReady($value["name"]);
		if ($SHOW_ID_NUMBERS) {
			if ($TEXT_DIRECTION=="ltr") print " &lrm;($key)&lrm;";
			else print " &rlm;($key)&rlm;";
		}
		print "</a>\n";
		print "</li>\n";
	}
	else $media_hide[$key."[".$GEDCOM."]"] = 1;
	SwitchGedcom();
}

// Prints the information for a repository in a list view
function print_list_repository($key, $value) {
	global $GEDCOM, $GEDCOMS, $GEDCOMID, $repo_total, $repo_hide, $SHOW_ID_NUMBERS, $GEDCOM, $TEXT_DIRECTION;

	SwitchGedcom($value["gedfile"]);
	if (!isset($repo_total)) $repo_total=array();
	$repo_total[$value["id"]."[".$GEDCOM."]"] = 1;
	if (displayDetailsByID($value["id"], "REPO")) {
		if (begRTLText($key))
		     print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";

		print "<a href=\"repo.php?rid=".$value["id"]."&amp;ged=".$GEDCOM."\" class=\"list_item\">";
		print PrintReady($key);
		if ($SHOW_ID_NUMBERS) {
			if ($TEXT_DIRECTION=="ltr") print " &lrm;(".$value["id"].")&lrm;";
			else print " &rlm;(".$value["id"].")&rlm;";
		}
		if (isset($value["actioncnt"][0]) && $value["actioncnt"][0] > 0) {
			if ($TEXT_DIRECTION=="ltr") print "<span class=\"error\"> &lrm;(".$value["actioncnt"][0].")&lrm;</span>";
			else print "<span class=\"error\"> &rlm;(".$value["repocnt"][0].")&rlm;</span>";
		}
		if (isset($value["actioncnt"][1]) && $value["actioncnt"][1] > 0) {
			if ($TEXT_DIRECTION=="ltr") print "<span class=\"okay\"> &lrm;(".$value["actioncnt"][1].")&lrm;</span>";
			else print "<span class=\"okay\"> &rlm;(".$value["repocnt"][1].")&rlm;</span>";
		}
			
		print "</a></li>\n";
	}
	else $repo_hide[$value["id"]."[".$GEDCOM."]"] = 1;
	SwitchGedcom();
}

// Initializes counters for lists
function InitListCounters($action = "reset") {
	global $indi_total, $indi_hide, $indi_private;
	global $fam_total, $fam_hide, $fam_private;
	global $repo_total, $repo_hide;
	global $source_total, $source_hide;
	global $note_total, $note_hide;
	global $media_total, $media_hide;

	if ($action != "reset") {
		if (!isset($indi_total)) $indi_total = array();
		if (!isset($indi_private)) $indi_private = array();
		if (!isset($indi_hide)) $indi_hide = array();
		if (!isset($fam_total)) $fam_total = array();
		if (!isset($fam_private)) $fam_private = array();
		if (!isset($fam_hide)) $fam_hide = array();
		if (!isset($source_total)) $source_total = array();
		if (!isset($source_hide)) $source_hide = array();
		if (!isset($repo_total)) $repo_total = array();
		if (!isset($repo_hide)) $repo_hide = array();
		if (!isset($note_total)) $note_total = array();
		if (!isset($note_hide)) $note_hide = array();
		if (!isset($media_total)) $media_total = array();
		if (!isset($media_hide)) $media_hide = array();
	}
	else {
		$indi_total = array();
		$indi_private = array();
		$indi_hide = array();
		$fam_total = array();
		$fam_private = array();
		$fam_hide = array();
		$source_total = array();
		$source_hide = array();
		$repo_total = array();
		$repo_hide = array();
		$note_total = array();
		$note_hide = array();
		$media_total = array();
		$media_hide = array();
	}
}

/**
 * Print the information for an individual chart box
 *
 * Find and print a given individuals information for a pedigree chart
 *
 * @param string $pid	the Gedcom Xref ID of the   to print
 * @param int $style	the style to print the box in, 1 for smaller boxes, 2 for larger boxes
 * @param boolean $show_famlink	set to true to show the icons for the popup links and the zoomboxes
 * @param int $count	on some charts it is important to keep a count of how many boxes were printed
 */
function print_pedigree_person($pid, $style=1, $show_famlink=true, $count=0, $personcount="1", $indirec="") {
	global $HIDE_LIVE_PEOPLE, $SHOW_LIVING_NAMES, $PRIV_PUBLIC, $factarray, $ZOOM_BOXES, $LINK_ICONS, $view, $SCRIPT_NAME, $GEDCOM;
	global $gm_lang, $SHOW_HIGHLIGHT_IMAGES, $bwidth, $bheight, $show_full, $PEDIGREE_FULL_DETAILS, $SHOW_ID_NUMBERS, $SHOW_PEDIGREE_PLACES, $view;
	global $CONTACT_EMAIL, $CONTACT_METHOD, $TEXT_DIRECTION, $DEFAULT_PEDIGREE_GENERATIONS, $OLD_PGENS, $talloffset, $PEDIGREE_LAYOUT, $MEDIA_DIRECTORY;
	global $GM_IMAGE_DIR, $GM_IMAGES, $ABBREVIATE_CHART_LABELS;
	global $chart_style, $box_width, $generations, $gm_username, $show_changes, $Users;
	global $CHART_BOX_TAGS, $SHOW_LDS_AT_GLANCE;

	if ($show_changes && $Users->UserCanEdit($Users->GetUserName())) $canshow = true;
	else $canshow = false;

	if (!isset($OLD_PGENS)) $OLD_PGENS = $DEFAULT_PEDIGREE_GENERATIONS;
	if (!isset($talloffset)) $talloffset = $PEDIGREE_LAYOUT;
	if (!isset($show_full)) $show_full=$PEDIGREE_FULL_DETAILS;

	if ($TEXT_DIRECTION == "ltr") {
		$ldir = "left";
		$rdir = "right";
	}
	else {
		$ldir = "left";
		$rdir = "right";
	}
		
	// NOTE: Start div out-rand()
	if ($pid==false) {
		print "\n\t\t\t<div id=\"out-".rand()."\" class=\"person_boxNN\" style=\"width: ".$bwidth."px; height: ".$bheight."px; padding: 2px; overflow: hidden;\">";
		print "<br />";
		print "\n\t\t\t</div>";
		return false;
	}
	
	// NOTE: Set the width of the box
	$lbwidth = $bwidth*.75;
	if ($lbwidth < 150) $lbwidth = 150;
	
	// NOTE: Get record
	$newindi = false;
	if ($indirec == "") {
		$indirec=FindPersonRecord($pid);
		if ($canshow && GetChangeData(true, $pid, true)) {
			$rec = GetChangeData(false, $pid, true, "gedlines", "");
			if (isset($rec[$GEDCOM][$pid])) $indirec = $rec[$GEDCOM][$pid];
			$newindi = true;
		}
	}
	$isF = "NN";
	if (preg_match("/1 SEX F/", $indirec)>0) $isF="F";
	else if (preg_match("/1 SEX M/", $indirec)>0) $isF="";
	$disp = displayDetailsByID($pid, "INDI");
	$dispname = showLivingNameByID($pid);
	$random = rand();
	if ($disp || $dispname) {
		if ($show_famlink) {
			// NOTE: Go ahead if we can show the popup box for the links to other pages and family members
			if ($LINK_ICONS!="disabled") {
				// NOTE: draw a popup box for the links to other pages and family members
				// NOTE: Start div I.$pid.$personcount.$count.links
				// NOTE: ie_popup_width is needed to set the width of the popup box in IE for the gedcom favorites
				print "\n\t\t<div id=\"I".$pid.".".$personcount.".".$count.".".$random."links\" class=\"wrap ie_popup_width person_box$isF details1\" style=\"position:absolute; height:auto; ";
				print "visibility:hidden;\" onmouseover=\"keepbox('".$pid.".".$personcount.".".$count.".".$random."'); return false;\" ";
				print "onmouseout=\"moveout('".$pid.".".$personcount.".".$count.".".$random."'); return false;\">";
				// This div is filled by an AJAX call! Not yet as placement is a problem!
				// NOTE: Zoom
				print "<a href=\"pedigree.php?rootid=$pid&amp;PEDIGREE_GENERATIONS=$OLD_PGENS&amp;talloffset=$talloffset&amp;ged=$GEDCOM\"><b>".$gm_lang["index_header"]."</b></a>\n";
				print "<br /><a href=\"descendancy.php?pid=$pid&amp;show_full=$show_full&amp;generations=$generations&amp;box_width=$box_width&amp;ged=$GEDCOM\"><b>".$gm_lang["descend_chart"]."</b></a><br />\n";
				$username = $gm_username;
				if (!empty($username)) {
					$tuser = $Users->GetUser($username);
					if (!empty($tuser->gedcomid[$GEDCOM])) {
						print "<a href=\"relationship.php?pid1=".$tuser->gedcomid[$GEDCOM]."&amp;pid2=".$pid."&amp;ged=$GEDCOM\"><b>".$gm_lang["relationship_to_me"]."</b></a><br />\n";
					}
				}
				// NOTE: Zoom
				if (file_exists("ancestry.php")) print "<a href=\"ancestry.php?rootid=$pid&amp;chart_style=$chart_style&amp;PEDIGREE_GENERATIONS=$OLD_PGENS&amp;box_width=$box_width&amp;ged=$GEDCOM\"><b>".$gm_lang["ancestry_chart"]."</b></a><br />\n";
				if (file_exists("fanchart.php") and defined("IMG_ARC_PIE") and function_exists("imagettftext"))  print "<a href=\"fanchart.php?rootid=$pid&amp;PEDIGREE_GENERATIONS=$OLD_PGENS&amp;ged=$GEDCOM\"><b>".$gm_lang["fan_chart"]."</b></a><br />\n";
				if (file_exists("hourglass.php")) print "<a href=\"hourglass.php?pid=$pid&amp;chart_style=$chart_style&amp;PEDIGREE_GENERATIONS=$OLD_PGENS&amp;box_width=$box_width&amp;ged=$GEDCOM\"><b>".$gm_lang["hourglass_chart"]."</b></a><br />\n";
				$ct = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indirec, $match, PREG_SET_ORDER);
				$fams = array();
				for ($i=0; $i<$ct; $i++) {
					$fams[] = $match[$i][1];
				}
				foreach ($fams as $key => $famid) {
					$famrec = FindFamilyRecord($famid);
					if ($canshow && GetChangeData(true, $famid, true, "gedlines", "")) {
						$rec = GetChangeData(false, $famid, true, "gedlines", "");
						$famrec = $rec[$GEDCOM][$famid];
					}
					if ($famrec) {
						$parents = FindParentsInRecord($famrec);
						$spouse = "";
						if ($pid==$parents["HUSB"]) $spouse = $parents["WIFE"];
						if ($pid==$parents["WIFE"]) $spouse = $parents["HUSB"];
						$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
						if ((!empty($spouse))||($num>0)) {
							print "<a href=\"family.php?famid=$famid&amp;ged=$GEDCOM\"><b>".$gm_lang["fam_spouse"]."</b></a><br /> \n";
							if (!empty($spouse)) {
								$spouserec = "";
								if ($canshow && GetChangeData(true, $spouse, true, "gedlines", "")) {
									$rec = GetChangeData(false, $spouse, true, "gedlines", "");
									$spouserec = $rec[$GEDCOM][$spouse];
								}
								print "<a href=\"individual.php?pid=$spouse&amp;ged=$GEDCOM\">";
								if (($SHOW_LIVING_NAMES>=$PRIV_PUBLIC) || (displayDetailsByID($spouse))||(showLivingNameByID($spouse))) print PrintReady(GetPersonName($spouse, $spouserec));
								else print $gm_lang["private"];
								print "</a><br />\n";
							}
						}
						for($j=0; $j<$num; $j++) {
							$cpid = $smatch[$j][1];
							print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"individual.php?pid=$cpid&amp;ged=$GEDCOM\">";
							if (($SHOW_LIVING_NAMES>=$PRIV_PUBLIC) || (displayDetailsByID($cpid))||(showLivingNameByID($cpid))) print PrintReady(GetPersonName($cpid));
							else print $gm_lang["private"];
							print "<br /></a>";
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
	print "\n\t\t\t<div id=\"out-$pid.$personcount.$count.$random\"";
	if ($style==1) {
		print " class=\"person_box$isF\" style=\"width: ".$bwidth."px; height: ".$bheight."px; padding: 2px; overflow: hidden;\"";
	}
	else {
		print " style=\"padding: 2px;\"";
	}
				
	// NOTE: If box zooming is allowed and no person details are shown
	// NOTE: determine what mouse behavior to add
	if ($ZOOM_BOXES != "disabled" && !$show_full && ($disp || $dispname)) {
		if ($ZOOM_BOXES=="mouseover") print " onmouseover=\"expandbox('".$pid.".".$personcount.".".$count."', $style, '".$random."'); return false;\" onmouseout=\"restorebox('".$pid.".".$personcount.".".$count."', $style, '".$random."'); return false;\"";
		if ($ZOOM_BOXES=="mousedown") print " onmousedown=\"expandbox('".$pid.".".$personcount.".".$count."', $style, '".$random."');\" onmouseup=\"restorebox('".$pid.".".$personcount.".".$count."', $style, '".$random."');\"";
		if (($ZOOM_BOXES=="click")&&($view!="preview")) print " onclick=\"expandbox('".$pid.".".$personcount.".".$count."', $style, '".$random."');\"";
	}
	print ">";
	
	// NOTE: Show the persons primary picture if possible, but only in large boxes ($show_full)
	if ($SHOW_HIGHLIGHT_IMAGES && $disp && showFact("OBJE", $pid, "INDI") && $show_full) {
		$object = FindHighlightedObject($pid, $indirec);
		// NOTE: Print the pedigree tumbnail
		if (!empty($object["thumb"])) {
			$media = New MediaItem($object["id"]);
			// NOTE: IMG ID
			$class = "pedigree_image_portrait";
			if ($media->fileobj->f_width > $media->fileobj->f_height) $class = "pedigree_image_landscape";
			if($TEXT_DIRECTION == "rtl") $class .= "_rtl";
			// NOTE: IMG ID
			print "<div class=\"$class\" style=\"float: left; border: none;\">";
			print "<img id=\"box-$pid.$personcount.$count.-thumb\" src=\"".$media->fileobj->f_thumb_file."\" vspace=\"0\" hspace=\"0\" class=\"$class\" alt =\"\" title=\"\" ";
			$has_thumb = true;
			if (!$show_full) print " style=\"display: none;\"";
			print " /></div>\n";
		}
	}
			
	// NOTE: find the name
	$name = GetPersonName($pid, $indirec);
	// NOTE: Find additional name
	$addname = GetAddPersonName($pid);
	// NOTE: Start the person details div. Adjust the print width to the purpose and filling
	if (!$show_full) print "<div class=\"person_details width100\">";
	else if (isset($has_thumb)) print "<div class=\"person_details width60\">";
	else print "<div class=\"person_details width80\">";
		//-- check if the person is visible
		if (!$disp) {
			if ($dispname) {
				// NOTE: Start span namedef-$personcount.$pid.$count
				print "<a href=\"individual.php?pid=$pid&amp;ged=$GEDCOM\"><span id=\"namedef-$pid.$personcount.$count.$random\" ";
				if (hasRTLText($name) && $style=="1")
				print "class=\"name2\">";
				else print "class=\"name$style\">";
				print PrintReady($name);
				// NOTE: IMG ID
				print "<img id=\"box-$pid.$personcount.$count.$random-sex\" src=\"$GM_IMAGE_DIR/";
				if ($isF=="") print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
				else  if ($isF=="F")print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
				else  print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
				print "\" class=\"sex_image\" />";
				if ($SHOW_ID_NUMBERS) {
					print "</span><span class=\"details$style\">";
					if ($TEXT_DIRECTION=="ltr") print "&lrm;($pid)&lrm;";
					else print "&rlm;($pid)&rlm;";
					// NOTE: Close span namedef-$personcount.$pid.$count
					print "</span>";
				}
				if (strlen($addname) > 0) {
					print "<br />";
					// NOTE: Start span addnamedef-$personcount.$pid.$count
					// NOTE: Close span addnamedef-$personcount.$pid.$count
					if (hasRTLText($addname) && $style=="1") print "<span id=\"addnamedef-$pid.$personcount.$count.$random\" class=\"name2\"> ";
					else print "<span id=\"addnamedef-$pid.$personcount.$count\" class=\"name$style\"> ";
					print PrintReady($addname)."</span><br />";
				}
				print "</a>";
			}
			else {
				$user = $Users->GetUser($CONTACT_EMAIL);
				print "<a href=\"javascript: ".$gm_lang["private"]."\" onclick=\"if (confirm('".preg_replace("'<br />'", " ", $gm_lang["privacy_error"])."\\n\\n".str_replace("#user[fullname]#", $user->firstname." ".$user->lastname, $gm_lang["clicking_ok"])."')) ";
				if ($CONTACT_METHOD!="none") {
					if ($CONTACT_METHOD=="mailto") print "window.location = 'mailto:".$user->email."'; ";
					else print "message('$CONTACT_EMAIL', '$CONTACT_METHOD'); ";
				}
				// NOTE: Start span namedef-$pid.$personcount.$count
				// NOTE: Close span namedef-$pid.$personcount.$count
				print "return false;\"><span id=\"namedef-$pid.$personcount.$count.$random\" class=\"name$style\">".$gm_lang["private"]."</span></a>\n";
			}
			if ($show_full) {
				// NOTE: Start span fontdef-$pid.$personcount.$count
				// NOTE: Close span fontdef-$pid.$personcount.$count
				print "<br /><span id=\"fontdef-$pid.$personcount.$count.$random\" class=\"details$style\">";
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
			print "<a href=\"individual.php?pid=$pid&amp;ged=$GEDCOM\"";
			if (!$show_full) {
				//not needed or wanted for mouseover //if ($ZOOM_BOXES=="mouseover") print " onmouseover=\"event.cancelBubble = true;\"";
				if ($ZOOM_BOXES=="mousedown") print "onmousedown=\"event.cancelBubble = true;\"";
				if ($ZOOM_BOXES=="click") print "onclick=\"event.cancelBubble = true;\"";
			}
			// NOTE: Start span namedef-$pid.$personcount.$count
			if (hasRTLText($name) && $style=="1") print "><span id=\"namedef-$pid.$personcount.$count.$random\" class=\"name2";
			else print "><span id=\"namedef-$pid.$personcount.$count.$random\" class=\"name$style";
			// NOTE: Add optional CSS style for each fact
//			$cssfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","CAST","DSCR","EDUC","IDNO","NATI","NCHI","NMR","OCCU","PROP","RELI","RESI","SSN","TITL","BAPL","CONL","ENDL","SLGC","_MILI");
//			foreach($cssfacts as $indexval => $fact) {
//				$ct = preg_match_all("/1 $fact/", $indirec, $nmatch, PREG_SET_ORDER);
//				if ($ct>0) print "&nbsp;".$fact;
//			}
			print "\">";
			print PrintReady($name);
			// NOTE: Close span namedef-$pid.$personcount.$count
			print "</span>";
			print "<span class=\"name$style\">";
			// NOTE: IMG ID
			print "<img id=\"box-$pid.$personcount.$count.$random-sex\" src=\"$GM_IMAGE_DIR/";
			if ($isF=="") print $GM_IMAGES["sex"]["small"]."\" title=\"".$gm_lang["male"]."\" alt=\"".$gm_lang["male"];
			else  if ($isF=="F")print $GM_IMAGES["sexf"]["small"]."\" title=\"".$gm_lang["female"]."\" alt=\"".$gm_lang["female"];
			else  print $GM_IMAGES["sexn"]["small"]."\" title=\"".$gm_lang["unknown"]."\" alt=\"".$gm_lang["unknown"];
			print "\" class=\"sex_image\" />";
			print "</span>\r\n";
			if ($SHOW_ID_NUMBERS) {
				if ($TEXT_DIRECTION=="ltr") print "<span class=\"details$style\">&lrm;($pid)&lrm; </span>";
				else print "<span class=\"details$style\">&rlm;($pid)&rlm; </span>";
			}
			if ($SHOW_LDS_AT_GLANCE) print "<span class=\"details$style\">".GetLdsGlance($indirec)."</span>";
			if (strlen($addname) > 0) {
				print "<br />";
				if (hasRTLText($addname) && $style=="1")
				print "<span id=\"addnamedef-$pid.$count.$random\" class=\"name2\"> ";
				else print "<span id=\"addnamedef-$pid.$count.$random\" class=\"name$style\"> ";
				print PrintReady($addname)."</span><br />";
			}
			print "</a>";
		
			// NOTE: Start div inout-$pid.$personcount.$count
			if (!$show_full) print "\n<div id=\"inout-$pid.$personcount.$count.$random\" style=\"display: none;\">\n";
			// NOTE: Start div fontdev-$pid.$personcount.$count
			print "<div id=\"fontdef-$pid.$personcount.$count.$random\" class=\"details$style\">";
			// NOTE: Start div inout2-$pid.$personcount.$count
			if ($show_full) print "\n<div id=\"inout2-$pid.$personcount.$count.$random\" style=\"display: block;\">\n";
			
			//-- section to display tags in the boxes
			// First get the optional tags and check if they exist
			$tagstoprint = array();
			if (!empty($CHART_BOX_TAGS)) {
				$opt_tags = preg_split("/[, ]+/", $CHART_BOX_TAGS);
				foreach ($opt_tags as $key => $tag) {
					if (strpos($indirec, "\n1 ".$tag)) {
						$tagstoprint[] = $tag;
						break;
					}
				}
			}
			// Then add the fixed tags
			// First the birth related tags
			foreach (array("BIRT", "CHR", "BAPM") as $key => $tag) {
				if (strpos($indirec, "\n1 ".$tag)) {
					$tagstoprint[] = $tag;
					break;
				}
			}
			// Then add the death related tags
			foreach (array("DEAT", "CREM", "BURI") as $key => $tag) {
				if (strpos($indirec, "\n1 ".$tag)) {
					$tagstoprint[] = $tag;
					break;
				}
			}
			// Remove double tags
			$tagstoprint = array_flip(array_flip($tagstoprint));
			// Get the subrecords and sort them
			$factstoprint = array();
			foreach ($tagstoprint as $key => $tag) {
				if (ShowFact($tag, $pid, "INDI")) $factstoprint[] = GetSubrecord(1, "1 ".$tag, $indirec);
			}
			SortFacts($factstoprint, "INDI");
			// Print the facts
			foreach($factstoprint as $key => $factrec) {
				$ft = preg_match("/1\s(\w+)(.*)/", $factrec, $match);
				print_simple_fact($factrec, $match[1], $pid);
			}
			// NOTE: Close div inout2-$pid.$personcount.$count
			if ($show_full) print "</div>\n";
			
			// NOTE: Find all level 1 sub records
			// The content is printed with AJAX calls
			// NOTE: Open div inout-$pid.$personcount.$count
			if ($show_full) {
				print "\n<div id=\"inout-$pid.$personcount.$count.$random\" style=\"display: none;\">";
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
		print "<div id=\"icons-$pid.$personcount.$count\"  class=\"width10\" style=\"float:";
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
			if ($ZOOM_BOXES != "disabled" && $show_full && !$view == "preview" && ($disp || $dispname)) {
				print "<a href=\"javascript: ".$gm_lang["zoom_box"]."\"";
				if ($ZOOM_BOXES=="mouseover") print " onmouseover=\"expandbox('".$pid.".".$personcount.".".$count."', $style, '".$random."'); if(document.getElementById('inout-$pid.$personcount.$count.$random').innerHTML=='') sndReq('inout-$pid.$personcount.$count.$random', 'getzoomfacts', 'pid', '".$pid."', 'canshow', '".$canshow."', 'view', '".$view."');\" onmouseout=\"restorebox('".$pid.".".$personcount.".".$count."', $style, '".$random."');\" onclick=\"return false;\"";
				if ($ZOOM_BOXES=="mousedown") print " onmousedown=\"expandbox('".$pid.".".$personcount.".".$count."', $style, '".$random."'); if(document.getElementById('inout-$pid.$personcount.$count.$random').innerHTML=='') sndReq('inout-$pid.$personcount.$count.$random', 'getzoomfacts', 'pid', '".$pid."', 'canshow', '".$canshow."', 'view', '".$view."');\" onmouseup=\"restorebox('".$pid.".".$personcount.".".$count."', $style, '".$random."');\" onclick=\"return false;\"";
				if ($ZOOM_BOXES=="click") print " onclick=\"expandbox('".$pid.".".$personcount.".".$count."', $style, '".$random."'); if(document.getElementById('inout-$pid.$personcount.$count.$random').innerHTML=='') sndReq('inout-$pid.$personcount.$count.$random', 'getzoomfacts', 'pid', '".$pid."', 'canshow', '".$canshow."', 'view', '".$view."'); return false;\"";
				print "><img id=\"iconz-$pid.$personcount.$count\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["zoomin"]["other"]."\" width=\"25\" height=\"25\" border=\"0\" alt=\"".$gm_lang["zoom_box"]."\" title=\"".$gm_lang["zoom_box"]."\" /></a>";
			}
			// NOTE: Popup box icon (don't show if the person is private)
			if ($LINK_ICONS!="disabled" && $show_famlink && ($disp || $dispname)) {
				$click_link="#";
				if (preg_match("/pedigree.php/", $SCRIPT_NAME)>0) $click_link="pedigree.php?rootid=$pid&amp;PEDIGREE_GENERATIONS=$OLD_PGENS&amp;talloffset=$talloffset&amp;ged=$GEDCOM";
				if (preg_match("/hourglass.php/", $SCRIPT_NAME)>0) $click_link="hourglass.php?pid=$pid&amp;generations=$generations&amp;box_width=$box_width&amp;ged=$GEDCOM";
				if (preg_match("/ancestry.php/", $SCRIPT_NAME)>0) $click_link="ancestry.php?rootid=$pid&amp;chart_style=$chart_style&amp;PEDIGREE_GENERATIONS=$OLD_PGENS&amp;box_width=$box_width&amp;ged=$GEDCOM";
				if (preg_match("/descendancy.php/", $SCRIPT_NAME)>0) $click_link="descendancy.php?pid=$pid&amp;show_full=$show_full&amp;generations=$generations&amp;box_width=$box_width&amp;ged=$GEDCOM";
				if ((preg_match("/family.php/", $SCRIPT_NAME)>0)&&!empty($famid)) $click_link="family.php?famid=$famid&amp;ged=$GEDCOM";
				if (preg_match("/individual.php/", $SCRIPT_NAME)>0) $click_link="individual.php?pid=$pid&amp;ged=$GEDCOM";
				print "<br /><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["pedigree"]["small"]."\" width=\"25\" border=\"0\" vspace=\"0\" hspace=\"0\" alt=\"".$gm_lang["person_links"]."\" title=\"".$gm_lang["person_links"]."\"";
				if ($LINK_ICONS=="mouseover") print " onmouseover";
				if ($LINK_ICONS=="click") print " onclick";
				print "=\"";
 //				print "if(document.getElementById('I".$pid.".".$personcount.".".$count.".".$random."links').innerHTML=='') sndReq('I".$pid.".".$personcount.".".$count.".".$random."links', 'getindilinks', 'pid', '".$pid."', 'OLD_PGENS', '".$OLD_PGENS."', 'talloffset', '".$talloffset."', 'generations', '".$generations."', 'canshow', '".$canshow."', 'show_full', '".$show_full."', 'box_width', '".$box_width."', 'chart_style', '".$chart_style."'); ";
				print "showbox(this, '".$pid.".".$personcount.".".$count.".".$random."', '";
				if ($style==1) print "box$pid";
				else print "relatives";
				print "');";
				print " return false;\" ";
				// NOTE: Removed so IE will keep showing the box
				// NOTE: Keep it here in case we might need it
				print "onmouseout=\"moveout('".$pid.".".$personcount.".".$count.".".$random."');";
				print " return false;\"";
				if (($click_link=="#")&&($LINK_ICONS!="click")) print " onclick=\"return false;\"";
				print " />";
			}
		// NOTE: Close div icons-$personcount.$pid.$count
		print "</div>\n";
	print "<br style=\"clear:both;\" />";
	print "</div>\n";
	
}
/**
 * print out standard HTML header
 *
 * This function will print out the HTML, HEAD, and BODY tags and will load in the CSS javascript and
 * other auxiliary files needed to run GM.  It will also include the theme specific header file.
 * This function should be called by every page, except popups, before anything is output.
 *
 * Popup pages, because of their different format, should invoke function print_simple_header() instead.
 *
 * @param string $title	the title to put in the <TITLE></TITLE> header tags
 * @param string $head
 * @param boolean $use_alternate_styles
 */
function print_header($title, $head="",$use_alternate_styles=true) {
	global $gm_lang, $bwidth, $gm_username, $Users, $Favorites;
	global $HOME_SITE_URL, $HOME_SITE_TEXT, $SERVER_URL;
	global $BROWSERTYPE, $indilist, $INDILIST_RETRIEVED;
	global $view, $cart, $menubar, $USE_GREYBOX;
	global $CHARACTER_SET, $GM_IMAGE_DIR, $GEDCOMS, $GEDCOM, $CONTACT_EMAIL, $COMMON_NAMES_THRESHOLD, $INDEX_DIRECTORY;
	global $SCRIPT_NAME, $QUERY_STRING, $action, $query, $changelanguage,$theme_name;
	global $FAVICON, $stylesheet, $print_stylesheet, $rtl_stylesheet, $headerfile, $toplinks, $THEME_DIR, $print_headerfile;
	global $GM_IMAGES, $TEXT_DIRECTION, $ONLOADFUNCTION,$REQUIRE_AUTHENTICATION, $SHOW_SOURCES;
	global $META_AUTHOR, $META_PUBLISHER, $META_COPYRIGHT, $META_DESCRIPTION, $META_PAGE_TOPIC, $META_AUDIENCE, $META_PAGE_TYPE, $META_ROBOTS, $META_REVISIT, $META_KEYWORDS, $META_TITLE, $META_SURNAME_KEYWORDS;
	// globals for the bot 304 mechanism
	global $bot, $_SERVER, $GEDCOMID, $pid, $famid, $rid, $sid;
	
	// Determine browser type
	if (!isset($_SERVER["HTTP_USER_AGENT"])) $BROWSERTYPE = "other";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "Opera"))
		$BROWSERTYPE = "opera";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "Netscape"))
		$BROWSERTYPE = "netscape";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "Gecko"))
		$BROWSERTYPE = "mozilla";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
		$BROWSERTYPE = "msie";
	else
		$BROWSERTYPE = "other";
		
	// This sends back a 304 if the CHAN record contains a date/time before or on the date sent by the bot.
	// NOTE: Pending changes in Genmod are NOT considered.

	$debug = false;
	if ($debug && !empty($bot)) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Visited your site.", "I", "S");
	
	if (!isset($ifModifiedSinceDate) &&
		isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) &&
		!empty($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
		$ifModifiedSinceDate = $_SERVER["HTTP_IF_MODIFIED_SINCE"];
	}
	if (isset($ifModifiedSinceDate)) {
		// Tells the requestor that you.ve recognized the conditional GET
		header("X-Requested-If-Modified-Since: ".$ifModifiedSinceDate, TRUE);

		switch (basename($SCRIPT_NAME)) {
		case "individual.php":
			$lastchange = GetLastChangeDate("INDI", $pid, $GEDCOMID, true);
			break;
		case "family.php":
			$lastchange = GetLastChangeDate("FAM", $famid, $GEDCOMID, true);
			break;
		case "source.php":
			$lastchange = GetLastChangeDate("FAM", $sid, $GEDCOMID, true);
			break;
		case "repo.php":
			$lastchange = GetLastChangeDate("REPO", $rid, $GEDCOMID, true);
			break;
		}
		// If the last change date cannot be retrieved, just continue processing
		if (isset($lastchange) && $lastchange) {
			$gmt_mtime = gmdate("D, d M Y H:i:s", $lastchange) . " GMT";
			$lastModifiedHeader = "Last-Modified: " . $gmt_mtime;
			if ($lastchange <= strtotime($ifModifiedSinceDate)) {
				// send 304
				header("HTTP/1.0 304 Not Modified");
				//header($lastModifiedHeader, TRUE, 304);
				// Log for debugging
				if ($debug) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Cached by bot on: ".$_SERVER['HTTP_IF_MODIFIED_SINCE']."<br />Modified on: ".gmdate("D, d M Y H:i:s", $lastchange)." GMT<br />Sent 304!", "I", "S");
				exit;
			}
			header($lastModifiedHeader, TRUE);
			// Log for debugging
			if ($debug) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Cached by bot on: ".$_SERVER['HTTP_IF_MODIFIED_SINCE']."<br />Modified on: ".gmdate("D, d M Y H:i:s", $lastchange)." GMT<br />Continued processing!", "I", "S");
		}
		else if ($debug) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Cached by bot on: ".$_SERVER['HTTP_IF_MODIFIED_SINCE']."<br />No last change date found.<br />Continued processing!", "I", "S");

	}
	
	// Continue normal processing	
	header("Content-Type: text/html; charset=$CHARACTER_SET");
	header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");

		
	print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
	print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$CHARACTER_SET\" />\n\t\t";
	if( $FAVICON ) {
	   print "<link rel=\"shortcut icon\" href=\"$FAVICON\" type=\"image/x-icon\"></link>\n\t\t";
	}
	if (!isset($META_TITLE)) $META_TITLE = "";
	if (isset($GEDCOMS[$GEDCOM]["title"])) $title = $GEDCOMS[$GEDCOM]["title"]." :: ".$title;
	print "<title>".PrintReady(strip_tags($title)." - ".$META_TITLE." - Genmod", TRUE)."</title>\n\t";
	 if (!$REQUIRE_AUTHENTICATION){
		print "<link href=\"" . $SERVER_URL .  "rss.php\" rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS\"></link>\n\t";
	 }
	 print "<link rel=\"stylesheet\" href=\"$stylesheet\" type=\"text/css\" media=\"all\"></link>\n\t";
	 if ((!empty($rtl_stylesheet))&&($TEXT_DIRECTION=="rtl")) print "<link rel=\"stylesheet\" href=\"$rtl_stylesheet\" type=\"text/css\" media=\"all\"></link>\n\t";
	 if ($use_alternate_styles) {
		if ($BROWSERTYPE != "other") {
			print "<link rel=\"stylesheet\" href=\"".$THEME_DIR.$BROWSERTYPE.".css\" type=\"text/css\" media=\"all\"></link>\n\t";
		}
	 }
	 print "<link rel=\"stylesheet\" href=\"$print_stylesheet\" type=\"text/css\" media=\"print\"></link>\n\t";
	 if ($BROWSERTYPE == "msie") print "<style type=\"text/css\">\nFORM { margin-top: 0px; margin-bottom: 0px; }\n</style>\n";
	 print "<!-- Genmod v".GM_VERSION." -->\n";
	 if (isset($changelanguage)) {
		  $terms = preg_split("/[&?]/", $QUERY_STRING);
		  $vars = "";
		  for ($i=0; $i<count($terms); $i++) {
			   if ((!empty($terms[$i]))&&(strstr($terms[$i], "changelanguage")===false)&&(strpos($terms[$i], "NEWLANGUAGE")===false)) {
					$vars .= $terms[$i]."&";
			   }
		  }
		  $query_string = $vars;
	 }
	 else $query_string = $QUERY_STRING;
	 if ($view!="preview") {
		 $old_META_AUTHOR = $META_AUTHOR;
		 $old_META_PUBLISHER = $META_PUBLISHER;
		 $old_META_COPYRIGHT = $META_COPYRIGHT;
		 $old_META_DESCRIPTION = $META_DESCRIPTION;
		 $old_META_PAGE_TOPIC = $META_PAGE_TOPIC;
		  $cuser = $Users->GetUser($CONTACT_EMAIL);
		  if (!empty($cuser->username)) {
			  if (empty($META_AUTHOR)) $META_AUTHOR = $cuser->firstname." ".$cuser->lastname;
			  if (empty($META_PUBLISHER)) $META_PUBLISHER = $cuser->firstname." ".$cuser->lastname;
			  if (empty($META_COPYRIGHT)) $META_COPYRIGHT = $cuser->firstname." ".$cuser->lastname;
		  }
		  if (!empty($META_AUTHOR)) print "<meta name=\"author\" content=\"".$META_AUTHOR."\" />\n";
		  if (!empty($META_PUBLISHER)) print "<meta name=\"publisher\" content=\"".$META_PUBLISHER."\" />\n";
		  if (!empty($META_COPYRIGHT)) print "<meta name=\"copyright\" content=\"".$META_COPYRIGHT."\" />\n";
		  print "<meta name=\"keywords\" content=\"".$META_KEYWORDS;
		  $surnames = GetCommonSurnamesIndex($GEDCOM);
		  foreach($surnames as $surname=>$count) if (!empty($surname)) print ", $surname";
		  print "\" />\n";
		  if ((empty($META_PAGE_TOPIC))&&(!empty($GEDCOMS[$GEDCOM]["title"]))) $META_PAGE_TOPIC = $GEDCOMS[$GEDCOM]["title"];
		//LERMAN - make meta description unique, like the title
		  if (empty($META_DESCRIPTION)) $META_DESCRIPTION = PrintReady(strip_tags($title)." - ".$META_TITLE." - Genmod", TRUE);
		  //if ((empty($META_DESCRIPTION))&&(!empty($GEDCOMS[$GEDCOM]["title"]))) $META_DESCRIPTION = $GEDCOMS[$GEDCOM]["title"];
		  if (!empty($META_DESCRIPTION)) print "<meta name=\"description\" content=\"".preg_replace("/\"/", "", $META_DESCRIPTION)."\" />\n";
		  if (!empty($META_PAGE_TOPIC)) print "<meta name=\"page-topic\" content=\"".preg_replace("/\"/", "", $META_PAGE_TOPIC)."\" />\n";
	 	  if (!empty($META_AUDIENCE)) print "<meta name=\"audience\" content=\"$META_AUDIENCE\" />\n";
	 	  if (!empty($META_PAGE_TYPE)) print "<meta name=\"page-type\" content=\"$META_PAGE_TYPE\" />\n";
	 	  if (!empty($META_ROBOTS)) print "<meta name=\"robots\" content=\"$META_ROBOTS\" />\n";
	 	  if (!empty($META_REVISIT)) print "<meta name=\"revisit-after\" content=\"$META_REVISIT\" />\n";
		  print "<meta name=\"generator\" content=\"Genmod v".GM_VERSION." - http://www.genmod.net\" />\n";
		 $META_AUTHOR = $old_META_AUTHOR;
		 $META_PUBLISHER = $old_META_PUBLISHER;
		 $META_COPYRIGHT = $old_META_COPYRIGHT;
		 $META_DESCRIPTION = $old_META_DESCRIPTION;
		 $META_PAGE_TOPIC = $old_META_PAGE_TOPIC;
	}
	else {
?>
<script language="JavaScript" type="text/javascript">
function hidePrint() {
	 var printlink = document.getElementById('printlink');
	 var printlinktwo = document.getElementById('printlinktwo');
	 if (printlink) {
		  printlink.style.display='none';
		  printlinktwo.style.display='none';
	 }
}
function showBack() {
	 var backlink = document.getElementById('backlink');
	 if (backlink) {
		  backlink.style.display='block';
	 }
}
</script>
<?php
}
?>
<script language="JavaScript" type="text/javascript">
	 <?php print "query = \"$query_string\";\n"; ?>
	 <?php print "textDirection = \"$TEXT_DIRECTION\";\n"; ?>
	 <?php print "browserType = \"$BROWSERTYPE\";\n"; ?>
	 <?php print "themeName = \"".strtolower($theme_name)."\";\n"; ?>
	 <?php print "SCRIPT_NAME = \"$SCRIPT_NAME\";\n"; ?>
	 /* keep the session id when opening new windows */
	 <?php print "sessionid = \"".session_id()."\";\n"; ?>
	 <?php print "sessionname = \"".session_name()."\";\n"; ?>
	 plusminus = new Array();
	 plusminus[0] = new Image();
	 plusminus[0].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]; ?>";
	 plusminus[1] = new Image();
	 plusminus[1].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]; ?>";
	 zoominout = new Array();
	 zoominout[0] = new Image();
	 zoominout[0].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["zoomin"]["other"]; ?>";
	 zoominout[1] = new Image();
	 zoominout[1].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["zoomout"]["other"]; ?>";
	 arrows = new Array();
	 arrows[0] = new Image();
	 arrows[0].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["larrow2"]["other"]; ?>";
	 arrows[1] = new Image();
	 arrows[1].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["rarrow2"]["other"]; ?>";
	 arrows[2] = new Image();
	 arrows[2].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["uarrow2"]["other"]; ?>";
	 arrows[3] = new Image();
	 arrows[3].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["darrow2"]["other"]; ?>";

function message(username, method, url, subject) {
	 if ((!url)||(url=="")) url='<?php print urlencode(basename($SCRIPT_NAME)."?".$QUERY_STRING); ?>';
	 if ((!subject)||(subject=="")) subject= '';
	 window.open('message.php?to='+username+'&method='+method+'&url='+url+'&subject='+subject+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
	 return false;
}
var whichhelp = 'help_<?php print basename($SCRIPT_NAME)."&amp;action=".$action; ?>';
</script>
<script src="genmod.js" language="JavaScript" type="text/javascript"></script>
<script src="gmrpc.js" language="JavaScript" type="text/javascript"></script>
<?php if ($USE_GREYBOX) { ?>
<script type="text/javascript">
    var GB_ROOT_DIR = "<?php print $SERVER_URL."modules/greybox/";?>";
</script>

<?php }
	 print $head;
	 print "</head>\n\t<body";
	 if ($view=="preview") print " onbeforeprint=\"hidePrint();\" onafterprint=\"showBack();\"";
	 if ($TEXT_DIRECTION=="rtl" || !empty($ONLOADFUNCTION)) {
		print " onload=\"$ONLOADFUNCTION";
	 	if ($TEXT_DIRECTION=="rtl") print " maxscroll = document.documentElement.scrollLeft;";
	 	print " loadHandler();";
	 	print "\"";
 	}
 	else {
		echo ' onload="loadHandler();';
		if ($view !== "preview") echo 'init();"';
		else echo '"';
	}
	 print ">\n\t";
	 // Start the container
	 // print "<div id=\"container\">";
	 print "<!-- begin header section -->\n";
	 include("includes/values/include_top.php");
	 
	 // Initialize the favorites class here, as it always comes with the menus.
	 // Exception: index_edit for editing a user favorite.
	 $Favorites = new Favorites();
	 
	 if ($view!="preview") include($menubar);
	 else include($print_headerfile);
	 print "<!-- end header section -->\n";
	 print "<!-- begin content section -->\n";
	
	 if ($USE_GREYBOX) { ?>
		<script type="text/javascript" src="modules/greybox/AJS.js"></script>
		<script type="text/javascript" src="modules/greybox/AJS_fx.js"></script>
		<script type="text/javascript" src="modules/greybox/gb_scripts.js"></script>
		<link href="modules/greybox/gb_styles.css" rel="stylesheet" type="text/css" />
	 <?php
 	}
	 // Unset the indilist as it is contaminated with ID's from other gedcoms
	 $INDILIST_RETRIEVED = false;
	 $indilist = array();
}
/**
 * print simple HTML header
 *
 * This function will print out the HTML, HEAD, and BODY tags and will load in the CSS javascript and
 * other auxiliary files needed to run GM.  It does not include any theme specific header files.
 * This function should be called by every page before anything is output on popup pages.
 *
 * @param string $title	the title to put in the <TITLE></TITLE> header tags
 * @param string $head
 * @param boolean $use_alternate_styles
 */
function print_simple_header($title) {
	 global $gm_lang, $Users;
	 global $HOME_SITE_URL, $USE_GREYBOX, $SERVER_URL;
	 global $HOME_SITE_TEXT;
	 global $view, $rtl_stylesheet;
	 global $CHARACTER_SET, $GM_IMAGE_DIR;
	 global $SCRIPT_NAME, $QUERY_STRING, $action, $query, $changelanguage;
	 global $FAVICON, $stylesheet, $headerfile, $toplinks, $THEME_DIR, $print_headerfile, $SCRIPT_NAME;
	 global $TEXT_DIRECTION, $GEDCOMS, $GEDCOM, $CONTACT_EMAIL, $COMMON_NAMES_THRESHOLD,$GM_IMAGES;
	 global $META_AUTHOR, $META_PUBLISHER, $META_COPYRIGHT, $META_DESCRIPTION, $META_PAGE_TOPIC, $META_AUDIENCE, $META_PAGE_TYPE, $META_ROBOTS, $META_REVISIT, $META_KEYWORDS, $META_TITLE;
	 header("Content-Type: text/html; charset=$CHARACTER_SET");
	 print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	 print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
	 print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$CHARACTER_SET\" />\n\t\t";
	if( $FAVICON ) {
	   print "<link rel=\"shortcut icon\" href=\"$FAVICON\" type=\"image/x-icon\"></link>\n\t\t";
	}
	if (!isset($META_TITLE)) $META_TITLE = "";
	print "<title>".PrintReady(strip_tags($title))." - ".$META_TITLE." - Genmod</title>\n\t<link rel=\"stylesheet\" href=\"$stylesheet\" type=\"text/css\"></link>\n\t";
	if ((!empty($rtl_stylesheet))&&($TEXT_DIRECTION=="rtl")) print "<link rel=\"stylesheet\" href=\"$rtl_stylesheet\" type=\"text/css\" media=\"all\"></link>\n\t";
	$old_META_AUTHOR = $META_AUTHOR;
	$old_META_PUBLISHER = $META_PUBLISHER;
	$old_META_COPYRIGHT = $META_COPYRIGHT;
	$old_META_DESCRIPTION = $META_DESCRIPTION;
	$old_META_PAGE_TOPIC = $META_PAGE_TOPIC;
	$cuser = $Users->GetUser($CONTACT_EMAIL);
	if (!empty($cuser->username)) {
		if (empty($META_AUTHOR)) $META_AUTHOR = $cuser->firstname." ".$cuser->lastname;
		if (empty($META_PUBLISHER)) $META_PUBLISHER = $cuser->firstname." ".$cuser->lastname;
		if (empty($META_COPYRIGHT)) $META_COPYRIGHT = $cuser->firstname." ".$cuser->lastname;
	}
	if (!empty($META_AUTHOR)) print "<meta name=\"author\" content=\"".$META_AUTHOR."\" />\n";
	if (!empty($META_PUBLISHER)) print "<meta name=\"publisher\" content=\"".$META_PUBLISHER."\" />\n";
	if (!empty($META_COPYRIGHT)) print "<meta name=\"copyright\" content=\"".$META_COPYRIGHT."\" />\n";
	print "<meta name=\"keywords\" content=\"".$META_KEYWORDS;
	$surnames = GetCommonSurnamesIndex($GEDCOM);
	foreach($surnames as $surname=>$count) print ", $surname";
	print "\" />\n";
	if ((empty($META_PAGE_TOPIC))&&(!empty($GEDCOMS[$GEDCOM]["title"]))) $META_PAGE_TOPIC = $GEDCOMS[$GEDCOM]["title"];
	//LERMAN - make meta description unique, like the title
	if (empty($META_DESCRIPTION)) $META_DESCRIPTION = PrintReady(strip_tags($title)." - ".$META_TITLE." - Genmod", TRUE);
	//if ((empty($META_DESCRIPTION))&&(!empty($GEDCOMS[$GEDCOM]["title"]))) $META_DESCRIPTION = $GEDCOMS[$GEDCOM]["title"];
	if (!empty($META_DESCRIPTION)) print "<meta name=\"description\" content=\"".preg_replace("/\"/", "", $META_DESCRIPTION)."\" />\n";
	if (!empty($META_PAGE_TOPIC)) print "<meta name=\"page-topic\" content=\"".preg_replace("/\"/", "", $META_PAGE_TOPIC)."\" />\n";
	if (!empty($META_AUDIENCE)) print "<meta name=\"audience\" content=\"$META_AUDIENCE\" />\n";
	if (!empty($META_PAGE_TYPE)) print "<meta name=\"page-type\" content=\"$META_PAGE_TYPE\" />\n";
	if (!empty($META_ROBOTS)) print "<meta name=\"robots\" content=\"$META_ROBOTS\" />\n";
	if (!empty($META_REVISIT)) print "<meta name=\"revisit-after\" content=\"$META_REVISIT\" />\n";
	print "<meta name=\"generator\" content=\"Genmod v".GM_VERSION." - http://www.Genmod.net\" />\n";
	$META_AUTHOR = $old_META_AUTHOR;
	$META_PUBLISHER = $old_META_PUBLISHER;
	$META_COPYRIGHT = $old_META_COPYRIGHT;
	$META_DESCRIPTION = $old_META_DESCRIPTION;
	$META_PAGE_TOPIC = $old_META_PAGE_TOPIC;
	?>
	<style type="text/css">
	<!--
	.largechars {
		font-size: 18px;
	}
	-->
	</style>
	 <script language="JavaScript" type="text/javascript">
	 <!--
	 /* set these vars so that the session can be passed to new windows */
	 <?php print "sessionid = \"".session_id()."\";\n"; ?>
	 <?php print "sessionname = \"".session_name()."\";\n"; ?>
	 plusminus = new Array();
	 plusminus[0] = new Image();
	 plusminus[0].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]; ?>";
	 plusminus[1] = new Image();
	 plusminus[1].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]; ?>";
	 zoominout = new Array();
	 zoominout[0] = new Image();
	 zoominout[0].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["zoomin"]["other"]; ?>";
	 zoominout[1] = new Image();
	 zoominout[1].src = "<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["zoomout"]["other"]; ?>";

	var helpWin;
	function helpPopup(which) {
		if ((!helpWin)||(helpWin.closed)) helpWin = window.open('help_text.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
		else helpWin.location = 'help_text.php?help='+which;
		return false;
	}
	function message(username, method, url, subject) {
		if ((!url)||(url=="")) url='<?php print urlencode(basename($SCRIPT_NAME)."?".$QUERY_STRING); ?>';
		if ((!subject)||(subject=="")) subject= '';
		window.open('message.php?to='+username+'&method='+method+'&url='+url+'&subject='+subject+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
		return false;
	}
	//-->
	</script>
	<script src="genmod.js" language="JavaScript" type="text/javascript"></script>
	<script src="gmrpc.js" language="JavaScript" type="text/javascript"></script>
	<?php if ($USE_GREYBOX) { ?>
		<script type="text/javascript">
	    	var GB_ROOT_DIR = "<?php print $SERVER_URL."modules/greybox/";?>";
		</script>
	<?php }
	print "</head>\n\t<body style=\"margin: 5px;\"";
	print " onload=\"loadHandler();\">\n\t";
	 if ($USE_GREYBOX) { ?>
		<script type="text/javascript" src="modules/greybox/AJS.js"></script>
		<script type="text/javascript" src="modules/greybox/AJS_fx.js"></script>
		<script type="text/javascript" src="modules/greybox/gb_scripts.js"></script>
		<link href="modules/greybox/gb_styles.css" rel="stylesheet" type="text/css" />
	 <?php
 	}
}
// -- print the html to close the page
function print_footer() {
	global $without_close, $gm_lang, $view, $buildindex;
	global $SHOW_STATS, $SCRIPT_NAME, $QUERY_STRING, $footerfile, $print_footerfile, $GEDCOMS, $ALLOW_CHANGE_GEDCOM, $printlink;
	global $GM_IMAGE_DIR, $theme_name, $GM_IMAGES, $TEXT_DIRECTION, $footer_count, $debugcollector;
	
	if (!isset($footer_count)) $footer_count = 1;
	else $footer_count++;
	
	print "<!-- begin footer -->\n";
	$QUERY_STRING = preg_replace("/&/", "&", $QUERY_STRING);
	if ($view != "preview") include($footerfile);
	else {
		include($print_footerfile);
		print "\n\t<div class=\"center width95\"><br />";
		$backlink = $SCRIPT_NAME."?".GetQueryString();
		if (!$printlink) {
			print "\n\t<br /><a id=\"printlink\" href=\"#\" onclick=\"print(); return false;\">".$gm_lang["print"]."</a><br />";
			print "\n\t <a id=\"printlinktwo\"	  href=\"#\" onclick=\"window.location='".$backlink."'; return false;\">".$gm_lang["cancel_preview"]."</a><br />";
		}
		$printlink = true;
		print "\n\t<a id=\"backlink\" style=\"display: none;\" href=\"#\" onclick=\"window.location='".$backlink."'; return false;\">".$gm_lang["cancel_preview"]."</a><br />";
		print "</div>";
	}
	// print "<!-- close container -->\n";
	// print "</div>";
	if (isset($debugcollector->show)) PrintDebug();
	include("includes/values/include_bottom.php");	
	print "\n\t</body>\n</html>";
	//-- We write the session data and close it. Fix for intermittend logoff.
	session_write_close();

}
// -- print the html to close the page
function print_simple_footer() {
	global $gm_lang, $start_time, $buildindex;
	global $SHOW_STATS, $CONFIG_PARMS, $MediaFS;
	global $SCRIPT_NAME, $QUERY_STRING, $debugcollector;
	
	if (empty($SCRIPT_NAME)) {
		$SCRIPT_NAME = $_SERVER["SCRIPT_NAME"];
		$QUERY_STRING = $_SERVER["QUERY_STRING"];
	}
	print "\n\t<br /><br /><div class=\"center\" style=\"width: 99%;\">";
	print_contact_links();
	print "<br />Running <a href=\"http://www.genmod.net/\" target=\"_blank\">Genmod";
	if (count($CONFIG_PARMS) >1) print " Enterprise";
	print $MediaFS->GetStorageType();
	print "</a> Version ".GM_VERSION." ".GM_VERSION_RELEASE;
	if ($SHOW_STATS) print_execution_stats();
	print "</div>";
	if (isset($debugcollector->show)) PrintDebug();
	print "\n\t</body>\n</html>";
	//-- We write the session data and close it. Fix for intermittend logoff.
	session_write_close();

}

if( !function_exists('memory_get_usage') ) {
	function memory_get_usage() {
		//If its Windows
       	//Tested on Win XP Pro SP2. Should work on Win 2003 Server too
       	//Doesn't work for 2000
       	//If you need it to work for 2000 look at http://us2.php.net/manual/en/function.memory-get-usage.php#54642
       	if ( substr(PHP_OS,0,3) == 'WIN') {
           	$output = array();
            $ret = @exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output );
            if(!$ret) return 0;
			if (isset($output[5])) return preg_replace( '/[\D]/', '', $output[5] ) * 1024;
			else return 0;
		}
		else {
			//We now assume the OS is UNIX
			//Tested on Mac OS X 10.4.6, Linux Red Hat Enterprise 4 and Solaris 10
			//This should work on most UNIX systems
			$pid = getmypid();
			if (PHP_OS == 'SunOS') {
				exec("ps -eopmem,rss,pid | grep $pid | awk -F' ' '{print $2;}'", $output);
			}
			else {
				exec("ps -eo%mem,rss,pid | grep $pid | awk -F' ' '{print $2;}'", $output);
			}
			//rss is given in 1024 byte units
			return $output[0] * 1024;
		}
   	}
}

/**
 * Prints exection statistics
 *
 * Prints out the execution time and the databse queries
 *
 * @author	Genmod Development Team
 */
function print_execution_stats() {
	global $start_time, $gm_lang, $TOTAL_QUERIES, $PRIVACY_CHECKS, $QUERY_EXECTIME;
	$end_time = GetMicrotime();
	$exectime = $end_time - $start_time;
	print "<br /><br />".$gm_lang["exec_time"];
	printf(" %.3f ".$gm_lang["sec"], $exectime);
	print "  ".$gm_lang["total_queries"]." $TOTAL_QUERIES.";
	print " ".$gm_lang["query_exec_time"];
	printf(" %.3f ".$gm_lang["sec"], $QUERY_EXECTIME);
	if (!$PRIVACY_CHECKS) $PRIVACY_CHECKS=0;
	print " ".$gm_lang["total_privacy_checks"]." $PRIVACY_CHECKS.";
	if (function_exists("memory_get_usage")) {
		$mu = memory_get_usage(true);
		if ($mu) {
			print " ".$gm_lang["total_memory_usage"]." ";
			print GetFileSize($mu);
			print ".";
		}
	}
	print "<br />";
}

/**
 * print links for genealogy and technical contacts
 *
 * this function will print appropriate links based on the preferred contact methods for the genealogy
 * contact user and the technical support contact user
 */
function print_contact_links($style=0) {
	global $WEBMASTER_EMAIL, $SUPPORT_METHOD, $CONTACT_EMAIL, $CONTACT_METHOD, $gm_lang, $Users;
	if ($SUPPORT_METHOD=="none" && $CONTACT_METHOD=="none") return array();
	if ($SUPPORT_METHOD=="none") $WEBMASTER_EMAIL = $CONTACT_EMAIL;
	if ($CONTACT_METHOD=="none") $CONTACT_EMAIL = $WEBMASTER_EMAIL;
	switch($style) {
		case 0:
			print "<div class=\"contact_links\">\n";
			//--only display one message if the contact users are the same
			if ($CONTACT_EMAIL==$WEBMASTER_EMAIL) {
				$user = $Users->GetUser($WEBMASTER_EMAIL);
				if (($user)&&($SUPPORT_METHOD!="mailto")) {
					print $gm_lang["for_all_contact"]." <a href=\"#\" accesskey=\"". $gm_lang["accesskey_contact"] ."\" onclick=\"message('$WEBMASTER_EMAIL', '$SUPPORT_METHOD'); return false;\">".$user->firstname." ".$user->lastname."</a><br />\n";
				}
				else {
					print $gm_lang["for_support"]." <a href=\"mailto:";
					if (!empty($user->username)) print $user->email."\" accesskey=\"". $gm_lang["accesskey_contact"] ."\">".$user->firstname." ".$user->lastname."</a><br />\n";
					else print $WEBMASTER_EMAIL."\">".$WEBMASTER_EMAIL."</a><br />\n";
				}
			}
			//-- display two messages if the contact users are different
			else {
				  $user = $Users->GetUser($CONTACT_EMAIL);
				  if (($user)&&($CONTACT_METHOD!="mailto")) {
					  print $gm_lang["for_contact"]." <a href=\"#\" accesskey=\"". $gm_lang["accesskey_contact"] ."\" onclick=\"message('$CONTACT_EMAIL', '$CONTACT_METHOD'); return false;\">".$user->firstname." ".$user->lastname."</a><br /><br />\n";
				  }
				  else {
					   print $gm_lang["for_contact"]." <a href=\"mailto:";
					   if (!empty($user->username)) print $user->email."\" accesskey=\"". $gm_lang["accesskey_contact"] ."\">".$user->firstname." ".$user->lastname."</a><br />\n";
					   else print $CONTACT_EMAIL."\">".$CONTACT_EMAIL."</a><br />\n";
				  }
				  $user = $Users->GetUser($WEBMASTER_EMAIL);
				  if (($user)&&($SUPPORT_METHOD!="mailto")) {
					  print $gm_lang["for_support"]." <a href=\"#\" onclick=\"message('$WEBMASTER_EMAIL', '$SUPPORT_METHOD'); return false;\">".$user->firstname." ".$user->lastname."</a><br />\n";
				  }
				  else {
					   print $gm_lang["for_support"]." <a href=\"mailto:";
					   if (!empty($user->username)) print $user->email."\">".$user->firstname." ".$user->lastname."</a><br />\n";
					   else print $WEBMASTER_EMAIL."\">".$WEBMASTER_EMAIL."</a><br />\n";
				  }
			}
			print "</div>\n";
			break;
		case 1:
			$menuitems = array();
			if ($CONTACT_EMAIL==$WEBMASTER_EMAIL) {
				$submenu = array();
				$user = $Users->GetUser($WEBMASTER_EMAIL);
				if (($user)&&($SUPPORT_METHOD!="mailto")) {
					$submenu["label"] = $gm_lang["support_contact"]." ".$user->firstname." ".$user->lastname;
					$submenu["link"] = "message('$WEBMASTER_EMAIL', '$SUPPORT_METHOD');";
				}
				else {
					$submenu["label"] = $gm_lang["support_contact"]." ";
					$submenu["link"] = "mailto:";
					if (!empty($user->username)) {
						$submenu["link"] .= $user->email;
						$submenu["label"] .= $user->firstname." ".$user->lastname;
					}
					else {
						$submenu["link"] .= $WEBMASTER_EMAIL;
						$submenu["label"] .= $WEBMASTER_EMAIL;
					}
				}
	            $submenu["label"] = $gm_lang["support_contact"];
	            $submenu["labelpos"] = "right";
	            $submenu["class"] = "submenuitem";
	            $submenu["hoverclass"] = "submenuitem_hover";
	            $menuitems[] = $submenu;
			}
			else {
				$submenu = array();
				$user = $Users->GetUser($CONTACT_EMAIL);
				if (($user)&&($CONTACT_METHOD!="mailto")) {
					$submenu["label"] = $gm_lang["genealogy_contact"]." ".$user->firstname." ".$user->lastname;
					$submenu["link"] = "message('$CONTACT_EMAIL', '$CONTACT_METHOD');";
				}
				else {
					$submenu["label"] = $gm_lang["genealogy_contact"]." ";
					$submenu["link"] = "mailto:";
					if (!empty($user->username)) {
						$submenu["link"] .= $user->email;
						$submenu["label"] .= $user->firstname." ".$user->lastname;
					}
					else {
						$submenu["link"] .= $CONTACT_EMAIL;
						$submenu["label"] .= $CONTACT_EMAIL;
					}
				}
	            $submenu["labelpos"] = "right";
	            $submenu["class"] = "submenuitem";
	            $submenu["hoverclass"] = "submenuitem_hover";
	            $menuitems[] = $submenu;
	            $submenu = array();
				$user = $Users->GetUser($WEBMASTER_EMAIL);
				if (($user)&&($SUPPORT_METHOD!="mailto")) {
					$submenu["label"] = $gm_lang["support_contact"]." ".$user->firstname." ".$user->lastname;
					$submenu["link"] = "message('$WEBMASTER_EMAIL', '$SUPPORT_METHOD');";
				}
				else {
					$submenu["label"] = $gm_lang["support_contact"]." ";
					$submenu["link"] = "mailto:";
					if (!empty($user->username)) {
						$submenu["link"] .= $user->email;
						$submenu["label"] .= $user->firstname." ".$user->lastname;
					}
					else {
						$submenu["link"] .= $WEBMASTER_EMAIL;
						$submenu["label"] .= $WEBMASTER_EMAIL;
					}
				}
	            $submenu["labelpos"] = "right";
	            $submenu["class"] = "submenuitem";
	            $submenu["hoverclass"] = "submenuitem_hover";
	            $menuitems[] = $submenu;
	        }
            return $menuitems;
			break;
	}
}

/**
 * print a simple form of the fact
 *
 * function to print the details of a fact in a simple format
 * @param string $indirec the gedcom record to get the fact from
 * @param string $fact the fact to print
 * @param string $pid the id of the individual to print, required to check privacy
 */
function print_simple_fact($indirec, $fact, $pid) {
	global $gm_lang, $SHOW_PEDIGREE_PLACES, $factarray, $ABBREVIATE_CHART_LABELS;
	$emptyfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","EVEN","MARR","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","OBJE","CHAN","_SEPR","RESI", "DATA", "MAP");
	$factrec = GetSubRecord(1, "1 $fact", $indirec);
	if ((empty($factrec))||(FactViewRestricted($pid, $factrec))) return;
	$label = "";
	if (isset($gm_lang[$fact])) $label = $gm_lang[$fact];
	else if (isset($factarray[$fact])) $label = $factarray[$fact];
	if ($ABBREVIATE_CHART_LABELS) $label = GetFirstLetter($label);
	// RFE [ 1229233 ] "DEAT" vs "DEAT Y"
	// The check $factrec != "1 DEAT" will not show any records that only have 1 DEAT in them
	if (trim($factrec) != "1 DEAT"){
	   print "<span class=\"details_label\">".$label."</span> ";
	}
	if (showFactDetails($fact, $pid)) {
		if (!in_array($fact, $emptyfacts)) {
			$ct = preg_match("/1 $fact(.*)/", $factrec, $match);
			if ($ct>0) print PrintReady(trim($match[1]));
		}
		// 1 DEAT Y with no DATE => print YES
		// 1 DEAT N is not allowed
		// It is not proper GEDCOM form to use a N(o) value with an event tag to infer that it did not happen.
		/*-- handled by print_fact_date()
		 * if (GetSubRecord(2, "2 DATE", $factrec)=="") {
			if (strtoupper(trim(substr($factrec,6,2)))=="Y") print $gm_lang["yes"];
		}*/
		print_fact_date($factrec, false, false, $fact, $pid, $indirec);
		print_fact_place($factrec);
	}
	else print $gm_lang["private"];
	print "<br />\n";
}
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
function print_fact($factrec, $pid, $fact, $count=1, $indirec=false, $styleadd="", $mayedit=true) {
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

	$FACT_COUNT++;
	$estimates = array("abt","aft","bef","est","cir");

	// NOTE: Retrieve event for fact
	$ft = preg_match("/1 (\w+)(.*)/", $factrec, $match);
	if ($ft>0) $event = trim($match[2]);
	else $event="";

	if ($styleadd == "deleted") {
		$deleted = true;
		$styleadd = "";
	}
	else $deleted = false;

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
		$lines = preg_split("/\n/", trim($factrec));
		if ((count($lines)<2)&&($event=="")) return;
	}
	
	// See if RESN tag prevents display or edit/delete
	$resn_tag = preg_match("/2 RESN (.*)/", $factrec, $match);
	if ($resn_tag == "1") $resn_value = strtolower(trim($match[1]));
	if (array_key_exists($fact, $factarray)) {
		// -- handle generic facts
		// Print the label
		if (($fact!="EVEN" && $fact!="FACT" && $fact!="OBJE")) {
			$factref = $fact;
			if ($relafact && !ShowRelaFact($factrec)) return false;
//			else if (!showFact($factref, $pid)) return false;
			if ($relafact) $show_fact_details = showRelaFactDetails($factrec);
			else $show_fact_details = showFactDetails($factref, $pid);
			// The two lines below do not validate because when $styleadd does
			// not have a value, we create several instances of the same ID.
//			print "\n\t\t<tr>";
// Temporarily restored the following line; removing styleadd breaks display relatives events
print "\n\t\t<tr id=\"row_".$styleadd."\" name=\"row_".$styleadd."\" >";
			//print "\n\t\t\t<td class=\"facts_label facts_label$styleadd\">";
			print "\n\t\t\t<td class=\"shade2 $styleadd center width20\" style=\"vertical-align: middle\">";
			print $factarray[$fact];
			if ($fact=="_BIRT_CHIL" and isset($n_chil)) print "<br />".$gm_lang["number_sign"].$n_chil++;
			if ($fact=="_BIRT_GCHI" and isset($n_gchi)) print "<br />".$gm_lang["number_sign"].$n_gchi++;
			if ($Users->userCanEdit($gm_username) && $fact != "CHAN" && $show_fact_details && $styleadd!="change_old" && !$deleted && $view!="preview" && !FactEditRestricted($pid, $factrec) && $count > 0 && $mayedit) {
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
				if (!stristr($factrec, "1 _GM")) {
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
			$ct = preg_match("/2 TYPE (.*)/", $factrec, $match);
			if ($ct>0) $factref = trim($match[1]);
			else $factref = $fact;
//			if (!showFact($factref, $pid)) return false;
			$show_fact_details = showFactDetails($factref, $pid) && showFactDetails("EVEN", $pid);
			// The two lines below do not validate because when $styleadd does
			// not have a value, we create several instances of the same ID.
			// Furthermore, "name" is not valid for a TR tag.
			print "\n\t\t<tr id=\"row_".$styleadd."\" name=\"row_".$styleadd."\">";
			if (isset($factarray["$factref"])) $label = $factarray[$factref];
			else $label = $factref;
			//print "<td class=\"facts_label facts_label$styleadd\">" . $label;
			print "<td class=\"shade2 $styleadd center width20\" style=\"vertical-align: middle\">";
			print $label;
			if ($show_fact_details && $fact != "CHAN" && $Users->userCanEdit($gm_username) && $styleadd!="change_old" && $view!="preview" && !FactEditRestricted($pid, $factrec) && $count > 0 && $mayedit) {
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
				if (!stristr($factrec, "1 _GM")) {
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
//		if ((showFactDetails($factref, $pid)) && (!FactViewRestricted($pid, $factrec))) {
//		if ($show_fact_details && (!FactViewRestricted($pid, $factrec))) {
		if ($show_fact_details) {
			// -- first print TYPE for some facts
			if ($fact!="EVEN" && $fact!="FACT") {
				$ct = preg_match("/2 TYPE (.*)/", $factrec, $match);
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
			$prted = print_fact_date($factrec, true, true, $fact, $pid, $indirec);
			//-- print spouse name for marriage events
			$ct = preg_match("/_GMS @(.*)@/", $factrec, $match);
			if ($ct>0) {
				$spouse=$match[1];
				if ($spouse!=="") {
					 print "<a href=\"individual.php?pid=$spouse&amp;ged=$GEDCOM\">";
					 if (showLivingNameById($spouse)) {
						$srec = FindGedcomRecord($spouse);
						if ($show_changes && $Users->UserCanEdit($gm_username) && GetChangeData(true, $spouse, true)) {
							$rec = GetChangeData(false, $spouse, true, "gedlines");
							$srec = $rec[$GEDCOM][$spouse];
						}
						print PrintReady(GetPersonName($spouse, $srec));
						$addname = GetAddPersonName($spouse, $srec);
						if ($addname!="") print " - ".PrintReady($addname);
					 }
					 else print $gm_lang["private"];
					 print "</a>";
				}
				$ct = preg_match("/_GMFS @(.*)@/", $factrec, $match);
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
			$temp = trim(GetCont(2, $factrec), "\r\n");
			if (strstr("PHON ADDR ", $fact." ")===false && $temp!="") {
				if ($WORD_WRAPPED_NOTES) print " ";
				print PrintReady($temp);
			}
			//-- find description for some facts
			$ct = preg_match("/2 DESC (.*)/", $factrec, $match);
			if ($ct>0) print PrintReady($match[1]);
			// -- print PLACe, TEMPle and STATus
			$prted  = print_fact_place($factrec, true, true, true) || $prted;
			// -- print BURIal -> CEMEtery
			$ct = preg_match("/2 CEME (.*)/", $factrec, $match);
			if ($ct>0) {
				if ($prted) print "<br />";
				if (file_exists($GM_IMAGE_DIR."/facts/CEME.gif")) print "<img src=\"".$GM_IMAGE_DIR."/facts/CEME.gif\" alt=\"".$factarray["CEME"]."\" title=\"".$factarray["CEME"]."\" align=\"middle\" /> ";
				print $factarray["CEME"].": ".$match[1]."\n";
			}
			//-- print address structure
			if ($fact!="ADDR" && $fact!="PHON") {
				$prted = print_address_structure($factrec, 2, $prted) || $prted;
			}
			else {
				$prted = print_address_structure($factrec, 1, $prted) || $prted;
			}
			// -- Enhanced ASSOciates > RELAtionship
			print_asso_rela_record($pid, $factrec, true);

			// -- find _GMU field
			$ct = preg_match("/2 _GMU (.*)/", $factrec, $match);
			if ($ct>0) print $factarray["_GMU"].": ".$match[1];
			if ($fact!="ADDR") {
				//-- catch all other facts that could be here
				$special_facts = array("ADDR","ALIA","ASSO","CEME","CONC","CONT","DATE","DESC","EMAIL",
				"FAMC","FAMS","FAX","NOTE","OBJE","PHON","PLAC","RESN","SOUR","STAT","TEMP",
				"TIME","TYPE","WWW","_EMAIL","_GMU", "URL", "AGE");

				$ct = preg_match_all("/\n2 (\w+) (.*)/", $factrec, $match, PREG_SET_ORDER);
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
							$filerec = GetSubrecord("2", "2 ".$factref, $factrec, 1);
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
			if (preg_match("/ (PLAC)|(STAT)|(TEMP)|(SOUR) /", $factrec)>0 || (!empty($event) && $fact!="ADDR" && $fact!="ASSO" && $fact!="PHON")) print "<br />\n";
			if ($prted) print "<br />";
			// -- find source for each fact
			if (ShowFact("SOUR", $pid, "SOUR")) $n1 = print_fact_sources($factrec, 2, $pid, $prted);
			// -- find notes for each fact
			if ($fact != "ASSO" && ShowFact("NOTE", $pid, "NOTE")) $n2 = print_fact_notes($factrec, 2);
			//-- find multimedia objects
			if (ShowFact("OBJE", $pid, "OBJE")) $n3 = print_media_links($factrec, 2, $pid, $prted);
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
/**
 * print a source linked to a fact (2 SOUR)
 *
 * this function is called by the print_fact function and other functions to
 * print any source information attached to the fact
 * @param string $factrec	The fact record to look for sources in
 * @param int $level		The level to look for sources at
 * @param string $pid		Added for privacy check
 */
function print_fact_sources($factrec, $level, $pid="", $nobr=true) {
	global $gm_lang, $gm_username;
	global $factarray;
	global $WORD_WRAPPED_NOTES, $FACT_COUNT, $GM_IMAGE_DIR, $FACT_COUNT, $GM_IMAGES, $SHOW_SOURCES;
	
	if (!$nobr) print "<br />";
	$nlevel = $level+1;
	$printed = false;
	// -- Systems not using source records [ 1046971 ]
	$ct = preg_match_all("/$level SOUR (.*)/", $factrec, $match, PREG_SET_ORDER);
	for($j=0; $j<$ct; $j++) {
		if (strpos($match[$j][1], "@")===false) {
			$srec = GetSubRecord($level, " SOUR ", $factrec, $j+1);
			$srec = substr($srec, 5); // remove SOUR
			$srec = str_replace("\n".($level+1)." CONT ", " ", $srec); // remove n+1 CONT
			$srec = str_replace("\n".($level+1)." CONC ", "", $srec); // remove n+1 CONC
			print "<span class=\"label\">".$gm_lang["source"].":</span> <span class=\"field\">".PrintReady($srec)."</span>";
			$printed = true;
		}
	}
	// -- find source for each fact
	$ct = preg_match_all("/$level SOUR @(.*)@/", $factrec, $match, PREG_SET_ORDER);
	$spos2 = 0;
	$newline = false;
	for($j=0; $j<$ct; $j++) {
		// Check if we can display the source
		if (DisplayDetailsByID($match[$j][1], "SOUR", 1, true)) {
			$show_details = ShowFactDetails("SOUR", $pid);
			$spos1 = strpos($factrec, "$level SOUR @".$match[$j][1]."@", $spos2);
			$spos2 = strpos($factrec, "\n$level", $spos1);
			if (!$spos2) $spos2 = strlen($factrec);
			$srec = substr($factrec, $spos1, $spos2-$spos1)."\r\n";
			$lt = preg_match_all("/$nlevel \w+/", $srec, $matches);
			if ($newline) print "<br />";
			$newline = true;
			print "\n\t\t<span class=\"label\">";
			$sid = $match[$j][1];
			if ($lt>0 && $show_details) print "<a href=\"#\" onclick=\"expand_layer('$sid$j-$FACT_COUNT'); return false;\"><img id=\"{$sid}{$j}-{$FACT_COUNT}_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
			print $gm_lang["source"].":</span> <span class=\"field\"><a href=\"source.php?sid=".$sid."\">";
			print PrintReady(GetSourceDescriptor($sid));
			//-- Print additional source title
	    	$add_descriptor = GetAddSourceDescriptor($sid);
    		if ($add_descriptor) print " - ".PrintReady($add_descriptor);
			print "</a></span>";
			if ($show_details) {
				$first = true;
				print "<div id=\"$sid$j-$FACT_COUNT\" class=\"source_citations\">";
				$cs1 = preg_match("/$nlevel PAGE (.*)/", $srec, $cmatch);
				if ($cs1>0) {
					$first = false;
					print "\n\t\t\t<span class=\"label\">".$factarray["PAGE"].": </span><span class=\"field\">".PrintReady($cmatch[1]);
					$pagerec = GetSubRecord($nlevel, $cmatch[0], $srec);
					$text = GetCont($nlevel+1, $pagerec);
					$text = ExpandUrl($text);
					print PrintReady($text);
					print "</span>";
				}
				$cs2 = preg_match("/$nlevel EVEN (.*)/", $srec, $cmatch);
				if ($cs2>0) {
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"label\">".$factarray["EVEN"]." </span><span class=\"field\">".$cmatch[1]."</span>";
					$cs = preg_match("/".($nlevel+1)." ROLE (.*)/", $srec, $cmatch);
					if ($cs>0) print "\n\t\t\t<br /><span class=\"label\">".$factarray["ROLE"]." </span><span class=\"field\">$cmatch[1]</span>";
				}
				$cs3 = preg_match("/$nlevel DATA/", $srec, $cmatch);
		   		if ($cs3>0) {
					$cs4 = preg_match("/".($nlevel+1)." DATE (.*)/", $srec, $cmatch);
					if ($cs4>0) {
						print "\n\t\t\t";
						if (!$first) print "<br />";
						else $first = false;
						print "<span class=\"label\">".$gm_lang["date"].": </span><span class=\"field\">".GetChangedDate($cmatch[1])."</span>";
					}
					$tt = preg_match_all("/".($nlevel+1)." TEXT (.*)\r\n/", $srec, $tmatch, PREG_SET_ORDER);
					for($k=0; $k<$tt; $k++) {
						if (!$first || $k != 0) print "<br />";
						else $first = false;
						print "<span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".PrintReady($tmatch[$k][1]);
						print PrintReady(GetCont($nlevel+2, $srec));
						print "</span>";
					}
				}
				$cs = preg_match("/".$nlevel." DATE (.*)/", $srec, $cmatch);
				if ($cs>0) {
					print "\n\t\t\t";
					if (!$first) print "<br />";
					else $first = false;
					print "<span class=\"label\">".$gm_lang["date"].": </span><span class=\"field\">".GetChangedDate($cmatch[1])."</span>";
				}
				$cs = preg_match("/$nlevel QUAY (.*)/", $srec, $cmatch);
				if ($cs>0) {
					if (!$first) print "<br />";
					else $first = false;
					print "<br /><span class=\"label\">".$factarray["QUAY"].": </span><span class=\"field\">".$cmatch[1]."</span>";
				}
				$cs = preg_match_all("/$nlevel TEXT (.*)\r\n/", $srec, $tmatch, PREG_SET_ORDER);
				for($k=0; $k<$cs; $k++) {
					if (!$first || $k != 0) print "<br />";
					else $first = false;
					print "<span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".$tmatch[$k][1];
					$text = GetCont($nlevel+1, $srec);
					$text = ExpandUrl($text);
					print PrintReady($text);
					print "</span>";
				}
				print "<div class=\"indent\">";
				print_media_links($srec, $nlevel);
				print_fact_notes($srec, $nlevel);
				print "</div>";
				print "</div>";
			}			
			$printed = true;
		}
	}
	return $printed;
}
function print_main_sources($factrec, $level, $pid, $count, $styleadd="", $mayedit=true) {
	 global $gm_lang, $factarray, $view, $gm_username, $Users;
	 global $WORD_WRAPPED_NOTES, $GM_IMAGE_DIR, $GM_IMAGES, $SHOW_SOURCES;
	
	 if ($SHOW_SOURCES<$Users->getUserAccessLevel($gm_username)) return;
	
	 // Fact level privacy: if we cannot show sources for this pid, or resn is in place for the fact, we can return.
	 if (!showFact("SOUR", $pid) || FactViewRestricted($pid, $factrec)) return false;
	
	 $nlevel = $level+1;
	 if (empty($styleadd)) {
		 $styleadd="";
	 	$ct = preg_match("/GM_NEW/", $factrec, $match);
	 	if ($ct>0) $styleadd="change_new";
	 	$ct = preg_match("/GM_OLD/", $factrec, $match);
	 	if ($ct>0) $styleadd="change_old";
 	}
	 // -- find source for each fact
	 $ct = preg_match_all("/$level SOUR @(.*)@/", $factrec, $match, PREG_SET_ORDER);
	 $spos2 = 0;
	 for($j=0; $j<$ct; $j++) {
		$spos1 = strpos($factrec, "$level SOUR @".$match[$j][1]."@", $spos2);
		$spos2 = strpos($factrec, "\n$level", $spos1);
		if (!$spos2) $spos2 = strlen($factrec);
		$srec = trim(substr($factrec, $spos1, $spos2-$spos1))."\r\n";
		// Here we check if we can show the source at all!
		if (DisplayDetailsByID($match[$j][1], "SOUR", 1, true)) {
			$show_details = showFactDetails("SOUR", $pid);
			print "\n\t\t\t<tr><td class=\"shade2 $styleadd center\" style=\"vertical-align: middle;\">";
			//print "\n\t\t\t<tr><td class=\"facts_label$styleadd\">";
			print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["large"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />";
			print $gm_lang["source"];
			if ($Users->userCanEdit($gm_username) && !FactEditRestricted($pid, $factrec) && $show_details && $styleadd!="change_old" && $view!="preview" && $mayedit) {
				$menu = array();
				$menu["label"] = $gm_lang["edit"];
				$menu["labelpos"] = "right";
				$menu["icon"] = "";
				$menu["link"] = "#";
				$menu["onclick"] = "return edit_record('$pid', 'SOUR', '$count', 'edit_sour');";
				$menu["class"] = "";
				$menu["hoverclass"] = "";
				$menu["flyout"] = "down";
				$menu["submenuclass"] = "submenu";
				$menu["items"] = array();
				$submenu = array();
				$submenu["label"] = $gm_lang["edit"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return edit_record('$pid', 'SOUR', '$count', 'edit_sour');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				$submenu = array();
				$submenu["label"] = $gm_lang["copy"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return copy_record('$pid', 'SOUR', '$count', 'copy_sour');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				$submenu = array();
				$submenu["label"] = $gm_lang["delete"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return delete_record('$pid', 'SOUR', '$count', 'delete_sour');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				print " <div style=\"width:25px;\" class=\"center\">";
				print_menu($menu);
				print "</div>";
			}
			print "</td>";
			print "\n\t\t\t<td class=\"shade1 wrap $styleadd\"><span class=\"field\">";
			//print "\n\t\t\t<td class=\"facts_value$styleadd\">";
			print "<a href=\"source.php?sid=".$match[$j][1]."\">";
    		print PrintReady(GetSourceDescriptor($match[$j][1]));
   			//-- Print additional source title
   			$add_descriptor = GetAddSourceDescriptor($match[$j][1]);
   			if ($add_descriptor) print " - ".PrintReady($add_descriptor);
			print "</a>";
			if ($show_details) {
				// See if RESN tag prevents display or edit/delete
	 			$resn_tag = preg_match("/2 RESN (.*)/", $factrec, $rmatch);
		 		if ($resn_tag > 0) $resn_value = strtolower(trim($rmatch[1]));
				// -- Find RESN tag
				if (isset($resn_value)) {
				   print_help_link("RESN_help", "qm");
				   print $gm_lang[$resn_value]."\n";
				}
				$source = FindSourceRecord($match[$j][1]);
				if ($source) {
					$cs = preg_match("/$nlevel PAGE (.*)/", $srec, $cmatch);
					if ($cs>0) {
						print "\n\t\t\t<br />".$factarray["PAGE"].": $cmatch[1]";
						$text = GetCont($nlevel+1, $srec);
						$text = ExpandUrl($text);
						print PrintReady($text);
					}
					$cs = preg_match("/$nlevel EVEN (.*)/", $srec, $cmatch);
					if ($cs>0) {
						print "<br /><span class=\"label\">".$factarray["EVEN"]." </span><span class=\"field\">".$cmatch[1]."</span>";
						$cs = preg_match("/".($nlevel+1)." ROLE (.*)/", $srec, $cmatch);
						if ($cs>0) print "\n\t\t\t<br />&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"label\">".$factarray["ROLE"]." </span><span class=\"field\">$cmatch[1]</span>";
					}
					$cs = preg_match("/$nlevel DATA/", $srec, $cmatch);
					if ($cs>0) {
//						// Don't print the DATA tag, it doesn't contain data so is obsolete!
//						print "<br /><span class=\"label\">".$factarray["DATA"]." </span>";
						$cs = preg_match("/".($nlevel+1)." DATE (.*)/", $srec, $cmatch);
						if ($cs>0) print "\n\t\t\t<br /><span class=\"label\">".$gm_lang["date"].":  </span><span class=\"field\">".GetChangedDate($cmatch[1])."</span>";
						$tt = preg_match_all("/".($nlevel+1)." TEXT (.*)\r\n/", $srec, $tmatch, PREG_SET_ORDER);
						for($k=0; $k<$tt; $k++) {
							print "<br /><span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".$tmatch[$k][1];
							print GetCont($nlevel+2, $srec);
							print "</span>";
						}
					}
					$cs = preg_match("/$nlevel QUAY (.*)/", $srec, $cmatch);
					if ($cs>0) print "<br /><span class=\"label\">".$factarray["QUAY"]." </span><span class=\"field\">".$cmatch[1]."</span>";
					$cs = preg_match_all("/$nlevel TEXT (.*)\r\n/", $srec, $tmatch, PREG_SET_ORDER);
					for($k=0; $k<$cs; $k++) {
						print "<br /><span class=\"label\">".$gm_lang["text"]." </span><span class=\"field\">".$tmatch[$k][1];
						$trec = GetSubRecord($nlevel, $tmatch[$k][0], $srec);
						$text = GetCont($nlevel+1, $trec);
						$text = ExpandUrl($text);
						print $text;
						print "</span>";
					}
					print_media_links($srec, $nlevel);
					print_fact_notes($srec, $nlevel);
				}
			}
			print "</span></td></tr>";
		}
	}
}
//-- Print all of the notes in this fact record
function print_fact_notes($factrec, $level) {
	global $gm_lang, $show_changes, $gm_username, $Users, $GEDCOM;
	global $factarray;
	global $WORD_WRAPPED_NOTES, $INDI_EXT_FAM_FACTS;
	
	if (!$INDI_EXT_FAM_FACTS && preg_match("/\n1 _GMFS @(.*)@/", $factrec) && $level == 2) return false;

	$factnotesprinted = false;
	$nlevel = $level+1;
	$ct = preg_match_all("/$level NOTE(.*)(\r\n)*/", $factrec, $match, PREG_SET_ORDER);
	for($j=0; $j<$ct; $j++) {
		$factnotesprinted = true;
		$spos1 = strpos($factrec, $match[$j][0]);
		$spos2 = strpos($factrec, "\n$level", $spos1+1);
		if (!$spos2) $spos2 = strlen($factrec);
		$nrec = substr($factrec, $spos1, $spos2-$spos1);
		if (!isset($match[$j][1])) $match[$j][1]="";
		$nt = preg_match("/@(.*)@/", $match[$j][1], $nmatch);
		$closeSpan = false;
		if ($nt==0) {
			//-- print embedded note records
			$text = preg_replace("/~~/", "<br />", $match[$j][1]);
			$text .= GetCont($nlevel, $nrec);
			$text = ExpandUrl($text);
			$text = trim($text);
			if (!empty($text)) {
				print "\n\t\t<br /><span class=\"label\">".$gm_lang["note"].": </span><span class=\"field wrap\">";
				print PrintReady($text);
				$closeSpan = true;
		   	}
		}
		else {
			//-- print linked note records
			$noterec = FindGedcomRecord($nmatch[1]);
			if ($show_changes && $Users->UserCanEdit($gm_username)) {
				$rec = GetChangeData(false, $nmatch[1], true, "gedlines");
				if (isset($rec[$GEDCOM][$nmatch[1]])) {
					$noterec = $rec[$GEDCOM][$nmatch[1]];
				}
			}
			$nt = preg_match("/0 @$nmatch[1]@ NOTE (.*)/", $noterec, $n1match);
			$text ="";
			if ($nt>0) $text = preg_replace("/~~/", "<br />", trim($n1match[1]));
			$text .= GetCont(1, $noterec);
			// Don't expand url's $text = ExpandUrl($text);
			$text = trim($text);
			if (!empty($text)) {
				print "\n\t\t<br /><span class=\"label\">".$gm_lang["note"].": </span><span class=\"field\">";
			   	print "<a href=\"note.php?oid=$nmatch[1]\">".PrintReady($text)."</a>";
			   	$closeSpan = true;
		   	}
		   	if (preg_match("/1 SOUR/", $noterec)>0) {
				print "<br />\n";
				print_fact_sources($noterec, 1);
			}
			//-- find multimedia objects
			if (preg_match("/1 OBJE/", $noterec)>0) {
				print "<br />\n";
				print_media_links($noterec, 1, $nmatch[1]);
		  	}
		}
		if (preg_match("/$nlevel SOUR/", $factrec)>0) {
			print "<div class=\"indent\">";
		  	print_fact_sources($nrec, $nlevel);
		  	print "</div>";
	  	}
	  	if($closeSpan){
	  		print "</span>";
	  	}
	}
	return $factnotesprinted;
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
function print_main_notes($factrec, $level, $pid, $count, $styleadd="", $mayedit=true) {
	 global $gm_lang, $gm_username, $Users;
	 global $factarray, $view, $show_changes;
	 global $WORD_WRAPPED_NOTES, $GM_IMAGE_DIR;
	 global $GM_IMAGES, $GEDCOM;
	
	 if ($styleadd=="") {
		 $ct = preg_match("/GM_NEW/", $factrec, $match);
		 if ($ct>0) $styleadd="change_new";
		 $ct = preg_match("/GM_OLD/", $factrec, $match);
		 if ($ct>0) $styleadd="change_old";
	 }
	 $nlevel = $level+1;
	 $ct = preg_match_all("/$level NOTE(.*)/", $factrec, $match, PREG_SET_ORDER);
	 for($j=0; $j<$ct; $j++) {
		$spos1 = strpos($factrec, "$level NOTE ".$match[$j][1]);
		$spos2 = strpos($factrec, "\n$level", $spos1);
		if (!$spos2) $spos2 = strlen($factrec);
		$nrec = substr($factrec, $spos1, $spos2-$spos1);
		// Check for a linked note here.
		$nt = preg_match("/\d NOTE @(.*)@/", $match[$j][0], $nmatch);
		if ($nt > 0) {
			$noterec = FindGedcomRecord($nmatch[1]);
			if ($show_changes && $Users->UserCanEdit($gm_username)) {
				$rec = GetChangeData(false, $nmatch[1], true, "gedlines");
				if (isset($rec[$GEDCOM][$nmatch[1]])) {
					$noterec = $rec[$GEDCOM][$nmatch[1]];
				}
			}
		}
		  
		if (!showFact("NOTE", $pid)||FactViewRestricted($pid, $factrec)) return false;
		print "\n\t\t<tr><td class=\"shade2 $styleadd center\" style=\"vertical-align: middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />".$gm_lang["note"].":";
		if ($Users->userCanEdit($gm_username)&&(!FactEditRestricted($pid, $factrec))&&($styleadd!="change_old")&&($view!="preview")&& $mayedit) {
			$menu = array();
			$menu["label"] = $gm_lang["edit"];
			$menu["labelpos"] = "right";
			$menu["icon"] = "";
			$menu["link"] = "#";
			$menu["onclick"] = "return edit_record('$pid', 'NOTE', '$count', 'edit_note');";
			$menu["class"] = "";
			$menu["hoverclass"] = "";
			$menu["flyout"] = "down";
			$menu["submenuclass"] = "submenu";
			$menu["items"] = array();
			$submenu = array();
			$submenu["label"] = $gm_lang["edit"];
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
			$submenu["onclick"] = "return edit_record('$pid', 'NOTE', '$count', 'edit_note');";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			$submenu = array();
			$submenu["label"] = $gm_lang["copy"];
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
			$submenu["onclick"] = "return copy_record('$pid', 'NOTE', '$count', 'copy_note');";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			$submenu = array();
			$submenu["label"] = $gm_lang["delete"];
			$submenu["labelpos"] = "right";
			$submenu["icon"] = "";
			$submenu["onclick"] = "return delete_record('$pid', 'NOTE', '$count', 'delete_note');";
			$submenu["link"] = "#";
			$submenu["class"] = "submenuitem";
			$submenu["hoverclass"] = "submenuitem_hover";
			$menu["items"][] = $submenu;
			print " <div style=\"width:25px;\" class=\"center\">";
			print_menu($menu);
			print "</div>";
		}
		print " </td>\n<td class=\"shade1 $styleadd wrap\">";
		if (showFactDetails("NOTE", $pid)) {
			if ($nt==0) {
				//-- print embedded note records
				$text = preg_replace("/~~/", "<br />", trim($match[$j][1]));
				$text .= GetCont($nlevel, $nrec);
				$text = ExpandUrl($text);
				print PrintReady($text);
			}
			else {
				//-- print linked note records
				$nt2 = preg_match("/0 @$nmatch[1]@ NOTE (.*)/", $noterec, $n1match);
				$text ="";
				if ($nt2>0) $text = preg_replace("/~~/", "<br />", trim($n1match[1]));
				$text .= GetCont(1, $noterec);
//				$text = ExpandUrl($text);
			   	print "<a href=\"note.php?oid=$nmatch[1]\">".PrintReady($text)."</a><br />";
//				print PrintReady($text)."<br />\n";
				print_fact_sources($noterec, 1);
			}
			// See if RESN tag prevents display or edit/delete
	 		$resn_tag = preg_match("/2 RESN (.*)/", $factrec, $match);
	 		if ($resn_tag > 0) $resn_value = strtolower(trim($match[1]));
			// -- Find RESN tag
			if (isset($resn_value)) {
				print_help_link("RESN_help", "qm");
				print $gm_lang[$resn_value]."\n";
			}
			print "<br />\n";
			print_fact_sources($nrec, $nlevel);
		}
		print "</td></tr>";
	}
}

//-- Print a link to multi-media objects
// TODO: FIX delete_record AND copy_record
function print_main_media($factrec, $pid, $nlevel, $count=1, $change=false, $styleadd="", $mayedit=true) {
	global $TBLPREFIX, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS, $MEDIA_EXTERNAL;
	global $gm_lang, $factarray, $view, $GEDCOMID, $USE_GREYBOX;
	global $GEDCOMS, $GEDCOM, $MEDIATYPE, $gm_username, $Users, $MediaFS;
	global $WORD_WRAPPED_NOTES, $MEDIA_DIRECTORY, $GM_IMAGE_DIR, $GM_IMAGES, $TEXT_DIRECTION;
	
	$deleted = false;
	if ($styleadd == "deleted") {
		$deleted = true;
		$styleadd = "";
	}
	$media_found = false;
	// NOTE: Get ID
	$ct = preg_match("/\d\sOBJE\s@(.*)@/", $factrec, $match);
	// No id, then return
	if (!$ct) return;
	
	$media_id = $match[1];
	
	// return if we can't display the MM item at all
	if (!DisplayDetailsByID($media_id, "OBJE")) return false;

	if (!ShowFact("OBJE", $pid, "OBJE")) return false;
// New from here
	if ($change && $styleadd != "change_old") $media = new MediaItem($media_id, '', true);
	else $media = new MediaItem($media_id);

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
	$show_details = showFactDetails("OBJE", $pid);
	print "\n\t\t<tr><td class=\"shade2 $styleadd center\" style=\"vertical-align: middle;\">".$factarray["OBJE"].":";
	if ($Users->userCanEdit($gm_username) && !FactEditRestricted($pid, $factrec) && $show_details && $styleadd!="change_old" && $view!="preview" && !$deleted && $mayedit) {
		$menu = array();
		$menu["label"] = $gm_lang["edit"];
		$menu["labelpos"] = "right";
		$menu["icon"] = "";
		$menu["link"] = "#";
		$menu["onclick"] = "return edit_record('".$pid."', 'OBJE', '$count', 'edit_media_link');";
		$menu["class"] = "";
		$menu["hoverclass"] = "";
		$menu["flyout"] = "down";
		$menu["submenuclass"] = "submenu";
		$menu["items"] = array();
		$submenu = array();
		$submenu["label"] = $gm_lang["edit"];
		$submenu["labelpos"] = "right";
		$submenu["icon"] = "";
		$submenu["onclick"] = "return edit_record('".$pid."', 'OBJE', '$count', 'edit_media_link');";
		$submenu["link"] = "#";
		$submenu["class"] = "submenuitem";
		$submenu["hoverclass"] = "submenuitem_hover";
		$menu["items"][] = $submenu;
		$submenu = array();
		$submenu["label"] = $gm_lang["copy"];
		$submenu["labelpos"] = "right";
		$submenu["icon"] = "";
		$submenu["onclick"] = "return copy_record('$pid', 'OBJE', '$count', 'copy_media');";
		$submenu["link"] = "#";
		$submenu["class"] = "submenuitem";
		$submenu["hoverclass"] = "submenuitem_hover";
		$menu["items"][] = $submenu;
		$submenu = array();
		$submenu["label"] = $gm_lang["delete"];
		$submenu["labelpos"] = "right";
		$submenu["icon"] = "";
		$submenu["onclick"] = "return delete_record('$pid', 'OBJE', '$count', 'delete_media');";
		$submenu["link"] = "#";
		$submenu["class"] = "submenuitem";
		$submenu["hoverclass"] = "submenuitem_hover";
		$menu["items"][] = $submenu;
		print " <div style=\"width:25px;\" class=\"center\">";
		print_menu($menu);
		print "</div>";
	}

	// NOTE Print the title of the media
	print "</td><td class=\"shade1 $styleadd wrap\"><span class=\"field\">";
	if ($show_details) {
		if (preg_match("'://'", $media->fileobj->f_thumb_file)||(preg_match("'://'", $MEDIA_DIRECTORY)>0)||($media->fileobj->f_file_exists)) {
			if ($USE_GREYBOX && $media->fileobj->f_is_image) {
				print "<a href=\"".FilenameEncode($media->fileobj->f_main_file)."\" title=\"".$media->title."\" rel=\"gb_imageset[mainmedia]\">";
			}
			else print "<a href=\"#\" onclick=\"return openImage('".$media->fileobj->f_main_file."', '".$imgwidth."', '".$imgheight."', '".$media->fileobj->f_is_image."');\">";
			print "<img src=\"".$media->fileobj->f_thumb_file."\" border=\"0\" align=\"" . ($TEXT_DIRECTION== "rtl"?"right": "left") . "\" class=\"thumbnail\" alt=\"\" /></a>";
		}
	}
	print "<a href=\"mediadetail.php?mid=".$media->xref."\">";
	if ($TEXT_DIRECTION=="rtl" && !hasRTLText($media->title)) print "<i>&lrm;".PrintReady($media->title)."</i></a>";
	else {
		print "<i>".PrintReady($media->title)."</i></a>";
	}

	if (empty($media->fileobj->f_thumb_file) && preg_match("'://'", $media->m_file)) print "<br /><a href=\"".$media->filename."\" target=\"_blank\">".$media->filename."</a>";

	
	if ($show_details) {
		// NOTE: Print the format of the media
		if ($media->extension != "") {
			print "\n\t\t\t<br /><span class=\"label\">".$factarray["FORM"].": </span> <span class=\"field\">".$media->extension."</span>";
			if ($media->fileobj->f_width != 0 &&  $media->fileobj->f_height != 0) {
				print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["image_size"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $media->fileobj->f_width . ($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ") . $media->fileobj->f_height . "</span>";
			}
		}
		$ttype = preg_match("/3 TYPE (.*)/", $media->m_gedrec, $match);
		if ($ttype>0){
			print "\n\t\t\t<br /><span class=\"label\">".$gm_lang["type"].": </span> <span class=\"field\">".$match[1]."</span>";
		}
		// Print PRIM from indirec
		$ttype = preg_match("/\d _PRIM (\w)/", $factrec, $match);
		if ($ttype>0){
			if ($match[1] == "Y") $val = "yes";
			else $val = "no";
			print "\n\t\t\t<br /><span class=\"label\">".$factarray["_PRIM"].": </span> <span class=\"field\">".$gm_lang[$val]."</span>";
		}
		
		// Print THUM from factrec
		$ttype = preg_match("/\d _THUM (\w)/", $factrec, $match);
		if ($ttype>0){
			if ($match[1] == "Y") $val = "yes";
			else $val = "no";
			print "\n\t\t\t<br /><span class=\"label\">".$factarray["_THUM"].": </span> <span class=\"field\">".$gm_lang[$val]."</span>";
		}

		print "</span>";
		print "<br />\n";
		//-- print spouse name for marriage events
		$ct = preg_match("/GM_SPOUSE: (.*)/", $factrec, $match);
		if ($ct>0) {
			$spouse=$match[1];
			if ($spouse!=="") {
				print "<a href=\"individual.php?pid=$spouse&amp;ged=$GEDCOM\">";
				if (showLivingNameById($spouse)) {
					print PrintReady(GetPersonName($spouse));
				}
				else print $gm_lang["private"];
				print "</a>";
			}
			if (($view != "preview") && ($spouse!=="")) print " - ";
			if ($view != "preview") {
				$ct = preg_match("/GM_FAMILY_ID: (.*)/", $factrec, $match);
				if ($ct>0) {
					$famid = trim($match[1]);
					print "<a href=\"family.php?famid=$famid\">[".$gm_lang["view_family"];
					if ($SHOW_FAM_ID_NUMBERS) print " &lrm;($famid)&lrm;";
					print "]</a>\n";
				}
			}
		}
		print "<br />\n";
		// Print the notes in the MM link
		if (print_fact_notes($factrec, 2)) print "<br /><br />";
		// Print the notes in the media record
		if (print_fact_notes($media->m_gedrec, $nlevel+1)) print "<br /><br />";
		print_fact_sources($media->m_gedrec, $nlevel+1);
	}
	print "</span></td></tr>";
	
	$media_found = true;
	if ($media_found) return true;
	else return false;
}
//-- Print the links to multi-media objects
function print_media_links($factrec, $level, $pid="", $nobr=true) {
	global $TEXT_DIRECTION, $TBLPREFIX, $GEDCOMS, $MEDIATYPE;
	global $gm_lang, $factarray, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $MediaFS;
	global $WORD_WRAPPED_NOTES, $MEDIA_DIRECTORY, $MEDIA_EXTERNAL, $GEDCOMID, $USE_GREYBOX, $INDI_EXT_FAM_FACTS;
	
	if (!$INDI_EXT_FAM_FACTS && preg_match("/\n1 _GMFS @(.*)@/", $factrec) && $level == 2) return false;
	
	if (!$nobr) print "<br />";

	$printed = false;
	$nlevel = $level+1;
	if ($level==1) $size=50;
	else $size=25;
	$ct = preg_match_all("/$level OBJE(.*)/", $factrec, $omatch, PREG_SET_ORDER);
	if (count($omatch) > 0) {
		$printed = true;
		print "<table class=\"facts_table\">";
		foreach ($omatch as $key => $media) {
			print "<tr><td>";
			$media_id = preg_replace("/@/", "", trim($media[1]));
			$media = New MediaItem($media_id);
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
			if (showFactDetails("OBJE", $pid)) {
				if (preg_match("'://'", $media->fileobj->f_thumb_file) || preg_match("'://'", $MEDIA_DIRECTORY) > 0 || $media->fileobj->f_file_exists) {
					if ($USE_GREYBOX && $media->fileobj->f_is_image) {
						print "<a href=\"".FilenameEncode($media->fileobj->f_main_file)."\" title=\"".$media->title."\" rel=\"gb_imageset[medialinks]\">";
					}
					else print "<a href=\"#\" onclick=\"return openImage('".$media->fileobj->f_main_file."', '".$imgwidth."', '".$imgheight."', '".$media->fileobj->f_is_image."');\">";
					print "<img src=\"".$media->fileobj->f_thumb_file."\" border=\"0\" align=\"" . ($TEXT_DIRECTION== "rtl"?"right": "left") . "\" class=\"thumbnail\" alt=\"\" /></a>";
				}
				print "<a href=\"mediadetail.php?mid=".$media_id."\">";
				if ($TEXT_DIRECTION=="rtl" && !hasRTLText($media->title)) print "<i>&lrm;".PrintReady($media->title)."</i></a>";
				else print "<i>".PrintReady($media->title)."</i></a>";

				// NOTE: Print the format of the media
				if ($media->extension != "") {
					print "\n\t\t\t<br /><span class=\"label\">".$factarray["FORM"].": </span> <span class=\"field\">".$media->extension."</span>";
					if ($media->fileobj->f_width != 0 && $media->fileobj->f_height != 0) {
						print "\n\t\t\t<span class=\"label\"><br />".$gm_lang["image_size"].": </span> <span class=\"field\" style=\"direction: ltr;\">" . $media->fileobj->f_width . ($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ") . $media->fileobj->f_height . "</span>";
					}
				}
				$ttype = preg_match("/\d TYPE (.*)/", $media->m_gedrec, $match);
				if ($ttype>0){
					print "\n\t\t\t<br /><span class=\"label\">".$gm_lang["type"].": </span> <span class=\"field\">$match[1]</span>";
				}
				print "</span>";
				print "<br />\n";
				//-- print spouse name for marriage events
				$ct = preg_match("/GM_SPOUSE: (.*)/", $factrec, $match);
				if ($ct>0) {
					$spouse=$match[1];
					if ($spouse!=="") {
						print "<a href=\"individual.php?pid=$spouse&amp;ged=$GEDCOM\">";
						if (showLivingNameById($spouse)) {
							print PrintReady(GetPersonName($spouse));
						}
						else print $gm_lang["private"];
						print "</a>";
					}
					if (($view != "preview") && ($spouse!=="")) print " - ";
					if ($view != "preview") {
						$ct = preg_match("/GM_FAMILY_ID: (.*)/", $factrec, $match);
						if ($ct>0) {
							$famid = trim($match[1]);
							print "<a href=\"family.php?famid=$famid\">[".$gm_lang["view_family"];
							if ($SHOW_FAM_ID_NUMBERS) print " &lrm;($famid)&lrm;";
							print "]</a>\n";
						}
					}
				}
//				print "<br />\n";
				if (ShowFact("NOTE", $media_id) && ShowFactDetails("NOTE", $media_id)) {
					$prtd = !print_fact_notes($media->m_gedrec, 1); // Level is 1 because the notes are subordinate to the linked record, NOT to the link!
				}
				else $prtd = true;
				if (ShowFact("SOUR", $media_id) && ShowFactDetails("SOUR", $media_id)) {
					print_fact_sources($media->m_gedrec, 1, "", $prtd); // Level is 1 because the sourcelinks are subordinate to the linked record, NOT to the link!
				}
			}
			print "</td></tr>";
		 }
		 print "</table>";
	 }
	 return $printed;
}
/**
 * print an address structure
 *
 * takes a gedcom ADDR structure and prints out a human readable version of it.
 * @param string $factrec	The ADDR subrecord
 * @param int $level		The gedcom line level of the main ADDR record
 */
function print_address_structure($factrec, $level, $br=false) {
	 global $gm_lang;
	 global $factarray;
	 global $WORD_WRAPPED_NOTES;
	 global $POSTAL_CODE;

	 //	 $POSTAL_CODE = 'false' - before city, 'true' - after city and/or state
	 //-- define per gedcom till can do per address countries in address languages
	 //-- then this will be the default when country not recognized or does not exist
	 //-- both Finland and Suomi are valid for Finland etc.
	 //-- see http://www.bitboost.com/ref/international-address-formats.html
	
	$hasany = preg_match("/$level (WWW|URL|FAX|EMAIL|PHON|ADDR)/", $factrec);
	$hasmore = preg_match("/$level (WWW|URL|FAX|EMAIL|PHON)/", $factrec);
	 if ($br && $hasany) print "<br />";
	
	 $firstline = true;
	
	 $nlevel = $level+1;
	 $ct = preg_match_all("/$level ADDR(.*)/", $factrec, $omatch, PREG_SET_ORDER);
	 for($i=0; $i<$ct; $i++) {
		 $firstline = false;
 		  $arec = GetSubRecord($level, "$level ADDR", $factrec, $i+1);
 		  if ($level>1) print "\n\t\t<span class=\"label\">".$factarray["ADDR"].": </span><br /><div class=\"indent\">";
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

			  if (!$POSTAL_CODE) {
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
		  $firstline = false;
		  if ($hasmore && $level == 1) print "<br />";
	 }
	 $ct = preg_match_all("/$level PHON (.*)/", $factrec, $omatch, PREG_SET_ORDER);
	 if ($ct>0) {
		  for($i=0; $i<$ct; $i++) {
			  if (!$firstline) print "<br />";
			  else $firstline = false;
			   if ($level>1) print "\n\t\t<span class=\"label\">".$factarray["PHON"].": </span><span class=\"field\">";
			   print "&lrm;".$omatch[$i][1]."&lrm;";
			   if ($level>1) print "</span>\n";
		  }
	 }
	 $ct = preg_match_all("/$level EMAIL (.*)/", $factrec, $omatch, PREG_SET_ORDER);
	 if ($ct>0) {
		  for($i=0; $i<$ct; $i++) {
			  if (!$firstline) print "<br />";
			  else $firstline = false;
			   if ($level>1) print "\n\t\t<span class=\"label\">".$factarray["EMAIL"].": </span><span class=\"field\">";
			   print "<a href=\"mailto:".$omatch[$i][1]."\">".$omatch[$i][1]."</a>\n";
			   if ($level>1) print "</span>\n";
		  }
	 }
	 $ct = preg_match_all("/$level FAX (.*)/", $factrec, $omatch, PREG_SET_ORDER);
	 if ($ct>0) {
		  for($i=0; $i<$ct; $i++) {
			  if (!$firstline) print "<br />";
			  else $firstline = false;
			   if ($level>1) print "\n\t\t<span class=\"label\">".$factarray["FAX"].": </span><span class=\"field\">";
 			   print "&lrm;".$omatch[$i][1]."&lrm;";
			   if ($level>1) print "</span>\n";
		  }
	 }
	 $ct = preg_match_all("/$level (WWW|URL) (.*)/", $factrec, $omatch, PREG_SET_ORDER);
	 if ($ct>0) {
		  for($i=0; $i<$ct; $i++) {
			  if (!$firstline) print "<br />";
			  else $firstline = false;
			   if ($level>1) print "\n\t\t<span class=\"label\">".$factarray["URL"].": </span><span class=\"field\">";
			   print "<a href=\"".$omatch[$i][2]."\" target=\"_blank\">".$omatch[$i][2]."</a>\n";
			   if ($level>1) print "</span>\n";
		  }
	 }
	 return $hasany;
}
//-- function to print a privacy error with contact method
function print_privacy_error($username) {
	 global $gm_lang, $CONTACT_METHOD, $SUPPORT_METHOD, $WEBMASTER_EMAIL, $Users;
	
	 $method = $CONTACT_METHOD;
	
	 if ($username==$WEBMASTER_EMAIL) $method = $SUPPORT_METHOD;
	 $user = $Users->GetUser($username);
	 if (empty($user->username)) $method = "mailto";
	 print "<br /><span class=\"error\">".$gm_lang["privacy_error"];
	 if ($method=="none") {
		  print "</span><br />\n";
		  return;
	 }
	 print $gm_lang["more_information"];
	 if ($method=="mailto") {
		  if (!$user) {
			   $email = $username;
			   $fullname = $username;
		  }
		  else {
			   $email = $user->email;
			   $fullname = $user->firstname." ".$user->lastname;
		  }
		  print " <a href=\"mailto:$email\">".$fullname."</a></span><br />";
	 }
	 else {
		  print " <a href=\"#\" onclick=\"message('$username','$method'); return false;\">".$user->firstname." ".$user->lastname."</a></span><br />";
	 }
}

/* Function to print popup help boxes
 * @param string $help		The variable that needs to be processed.
 * @param int $helpText		The text to be printed if the theme does not use images for help links
 * @param int $show_desc		The text to be shown as JavaScript description
 * @param boolean $use_print_text	If the text needs to be printed with the print_text() function
 * @param boolean $output	return the text instead of printing it
 */
function print_help_link($help, $helpText, $show_desc="", $use_print_text=false, $return=false) {
	global $SHOW_CONTEXT_HELP, $gm_lang,$view, $GM_USE_HELPIMG, $GM_IMAGES, $GM_IMAGE_DIR, $gm_username, $Users;
	if ($GM_USE_HELPIMG) $sentense = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"icon\" width=\"15\" height=\"15\" alt=\"\" />";
	else $sentense = $gm_lang[$helpText];
	$output = "";
	if (($view!="preview")&&($_SESSION["show_context_help"])){
		if ($helpText=="qm_ah"){
			if ($Users->userIsAdmin($gm_username)){
				 $output .= " <a class=\"error help\" tabindex=\"0\" href=\"javascript:";
				 if ($show_desc == "") $output .= $help;
				 else if ($use_print_text) $output .= print_text($show_desc, 0, 1);
				 else if (stristr($gm_lang[$show_desc], "\"")) $output .= preg_replace('/\"/','\'',$gm_lang[$show_desc]);
				 else  $output .= strip_tags($gm_lang[$show_desc]);
				 $output .= "\" onclick=\"helpPopup('$help'); return false;\">".$sentense."</a> \n";
			}
		}
		else {
			$output .= " <a class=\"help\" tabindex=\"0\" href=\"javascript: ";
			if ($show_desc == "") $output .= $help;
			else if ($use_print_text) $output .= print_text($show_desc, 0, 1);
			else if (stristr($gm_lang[$show_desc], "\"")) $output .= preg_replace('/\"/','\'',$gm_lang[$show_desc]);
			else  $output .= strip_tags($gm_lang[$show_desc]);
			$output .= "\" onclick=\"helpPopup('$help'); return false;\">".$sentense."</a> \n";
		}
	}
	if (!$return) print $output;
	return $output;
}

/**
 * print a language variable
 *
 * It accepts any kind of language variable. This can be a single variable but also
 * a variable with included variables that needs to be converted.
 * print_text, which used to be called print_help_text, now takes 3 parameters
 *		of which only the 1st is mandatory
 * The first parameter is the variable that needs to be processed.  At nesting level zero,
 *		this is the name of a $gm_lang array entry.  "whatever" refers to
 *		$gm_lang["whatever"].  At nesting levels greater than zero, this is the name of
 *		any global variable, but *without* the $ in front.  For example, VERSION or
 *		gm_lang["whatever"] or factarray["rowname"].
 * The second parameter is $level for the nested vars in a sentence.  This indicates
 *		that the function has been called recursively.
 * The third parameter $noprint is for returning the text instead of printing it
 *		This parameter, when set to 2 means, in addition to NOT printing the result,
 *		the input string $help is text that needs to be interpreted instead of being
 *		the name of a $gm_lang array entry.  This lets you use this function to work
 *		on something other than $gm_lang array entries, but coded according to the
 *		same rules.
 * When we want it to return text we need to code:
 * print_text($mytext, 0, 1);
 * @param string $help		The variable that needs to be processed.
 * @param int $level		The position of the embedded variable
 * @param int $noprint		The switch if the text needs to be printed or returned
 */
function print_text($help, $level=0, $noprint=0){
	 global $gm_lang, $factarray, $COMMON_NAMES_THRESHOLD;
	 global $INDEX_DIRECTORY, $GEDCOMS, $GEDCOM, $GEDCOM_TITLE, $LANGUAGE;
	 global $GUESS_URL, $UpArrow, $DAYS_TO_SHOW_LIMIT, $MEDIA_DIRECTORY;
	 global $repeat, $thumbnail, $xref, $pid, $LANGUAGE;
	
	 if (!isset($_SESSION["DEBUG_LANG"])) $DEBUG_LANG = "no";
	 else $DEBUG_LANG = $_SESSION["DEBUG_LANG"];
	 if ($DEBUG_LANG == "yes") print "[LANG_DEBUG] Variable called: ".$help."<br /><br />";
	
	 $sentence = "";
	 if ($level>0) {
		 // check nested var
		 if (isset($$help)) $sentence = $$help;
		 // check constant
		 else if (defined($help)) $sentence = constant($help);
		 // check constant with prefix
		 else if (defined("GM_".$help)) $sentence = constant("GM_".$help);
		 // check langvar
		 else !isset($gm_lang[$help]) ? $sentence = GetString($help, $LANGUAGE) : $sentence = $gm_lang[$help];
	 }
	 if (empty($sentence)) {
		  if ($noprint == 2) {
			  $sentence = $help;
	  	  }
	  	  else {
			  if (!isset($gm_lang[$help])) $sentence = GetString($help, $LANGUAGE);
			  else $sentence = $gm_lang[$help];
		  }
		
		  if (empty($sentence)) {
			  if ($DEBUG_LANG == "yes") print "[LANG_DEBUG] Variable not present: ".$help."<br /><br />";
			  $sentence = $gm_lang["help_not_exist"];
		  }
	 }
	 $mod_sentence = "";
	 $replace = "";
	 $replace_text = "";
	 $sub = "";
	 $pos1 = 0;
	 $pos2 = 0;
	 $ct = preg_match_all("/#([a-zA-Z0-9_.\-\[\]]+)#/", $sentence, $match, PREG_SET_ORDER);
	 for($i=0; $i<$ct; $i++) {
		  $value = "";
		  $newreplace = preg_replace(array("/gm_lang/","/\[/","/\]/"), array("","",""), $match[$i][1]);
		  if ($DEBUG_LANG == "yes") print "[LANG_DEBUG] Embedded variable: ".$match[$i][1]."<br /><br />";
		  $value = print_text($newreplace, $level+1);
		  if (!empty($value)) $sentence = str_replace($match[$i][0], $value, $sentence);
		  else if ($noprint==0) $sentence = str_replace($match[$i][0], $match[$i][1].": ".$gm_lang["var_not_exist"], $sentence);
	 }
	 // ------ Replace paired ~  by tag_start and tag_end (those vars contain CSS classes)
	 while (stristr($sentence, "~") == TRUE){
		  $pos1 = strpos($sentence, "~");
		  $mod_sentence = substr_replace($sentence, " ", $pos1, 1);
		  if (stristr($mod_sentence, "~")){		// If there's a second one:
			  $pos2 = strpos($mod_sentence, "~");
			  $replace = substr($sentence, ($pos1+1), ($pos2-$pos1-1));
			  $replace_text = "<span class=\"helpstart\">".Str2Upper($replace)."</span>";
			  $sentence = str_replace("~".$replace."~", $replace_text, $sentence);
		  } else break;
	 }
	 if ($noprint>0) return $sentence;
	 if ($level>0) return $sentence;
	 print $sentence;
}
function print_help_index($help){
	 global $gm_lang, $factarray;
	 $sentence = $gm_lang[$help];
	 $mod_sentence = "";
	 $replace = "";
	 $replace_text = "";
	 $sub = "";
	 $pos1 = 0;
	 $pos2 = 0;
	 $admcol=false;
	 $ch=0;
	 $help_sorted = array();
	 $var="";
	 while (stristr($sentence, "#") == TRUE){
		$pos1 = strpos($sentence, "#");
		$mod_sentence = substr_replace($sentence, " ", $pos1, 1);
		$pos2 = strpos($mod_sentence, "#");
		$replace = substr($sentence, ($pos1+1), ($pos2-$pos1-1));
		$sub = preg_replace(array("/gm_lang\\[/","/\]/"), array("",""), $replace);
		if (isset($gm_lang[$sub])) {
			$items = preg_split("/,/", $gm_lang[$sub]);
			$var = print_text($items[1],0,1);
		}
		$sub = preg_replace(array("/factarray\\[/","/\]/"), array("",""), $replace);
		if (isset($factarray[$sub])) {
			$items = preg_split("/,/", $factarray[$sub]);
			$var = $factarray[$items[1]];
		}
		if (substr($var,0,1)=="_") {
			$admcol=true;
			$ch++;
		}
		$replace_text = "<a href=\"help_text.php?help=".$items[0]."\">".$var."</a><br />";
		$help_sorted[$replace_text] = $var;
		$sentence = str_replace("#".$replace."#", $replace_text, $sentence);
	 }
	 uasort($help_sorted, "StringSort");
	 if ($ch==0) $ch=count($help_sorted);
	 else $ch +=$ch;
	 if ($ch>0) print "<table width=\"100%\"><tr><td style=\"vertical-align: top;\"><ul>";
	 $i=0;
	 foreach ($help_sorted as $k => $help_item){
		print "<li>".$k."</li>";
		$i++;
		if ($i==ceil($ch/2)) print "</ul></td><td style=\"vertical-align: top;\"><ul>";
	 }
	 if ($ch>0) print "</ul></td></tr></table>";
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
function print_menu($menu, $parentmenu="") {
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
					$obj->$conv[$k] = $v;
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
						$sobj->$conv[$k2] = $v2;
					}
				}
			}
			$obj->addSubmenu($sobj);
		}
	}
	$obj->printMenu();
}
/**
 * Prepare text with parenthesis for printing
 * Convert & to &amp; for xhtml compliance
 *
 * @author	Genmod Development Team
 * @param		string	$text		The text that should be preperated
 * @param		boolean	$InHeaders	Is the text from the header, if so do not highlight it
 * @return 	string 	text to be printed
 */
function PrintReady($text, $InHeaders=false) {
	global $TEXT_DIRECTION, $SpecialChar, $SpecialPar, $query, $action, $firstname, $lastname, $place, $year;
	
	// Check whether Search page highlighting should be done or not
	$HighlightOK = false;
	if (strstr($_SERVER["SCRIPT_NAME"], "search.php")) {	// If we're on the Search page
		if (!$InHeaders) {								//   and also in page body
			//	if ((isset($query) and ($query != "")) || (isset($action) && ($action === "soundex"))) {		//   and the query isn't blank
			if ((isset($query) and ($query != "")) ) {		//   and the query isn't blank				
				//$HighlightOK = true;					// It's OK to mark search result
			}
		}
	}
	$SpecialOpen = '(';
	$SpecialClose = array('(');
	//-- convert all & to &amp;
	$text = preg_replace("/&/", "&amp;", $text);
	//-- make sure we didn't double convert &amp; to &amp;amp;
	$text = preg_replace("/&amp;(\w+);/", "&$1;", $text);
	
	// NOTE: Remove any lrm and rlm to prevent doubling up
	$text = preg_replace(array('/&rlm;/','/&lrm;/'), '', $text);
	
	$text=trim($text);
	//-- if we are on the search page body, then highlight any search hits
	if ($HighlightOK) {
		if (isset($query)) {
			$queries = preg_split("/\.\*/", $query);
			$newtext = $text;
			$hasallhits = true;
			foreach($queries as $index=>$query1) {
				if (preg_match("/(".$query1.")/i", $text)) {
					$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
				}
				else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
					$nlen = strlen($query1);
					$npos = strpos(Str2Upper($text), Str2Upper($query1));
					$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
					$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
				}
				else $hasallhits = false;
			}
			if ($hasallhits) $text = $newtext;
		}
		if (isset($action) && ($action === "soundex")) {
			if (isset($firstname)) {
			$queries = preg_split("/\.\*/", $firstname);
			$newtext = $text;
			$hasallhits = true;
			foreach($queries as $index=>$query1) {
			if (preg_match("/(".$query1.")/i", $text)) {
			$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
			}
			else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
			$nlen = strlen($query1);
			$npos = strpos(Str2Upper($text), Str2Upper($query1));
			$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
			$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
			}
			else $hasallhits = false;
			}
			if ($hasallhits) $text = $newtext;
			}
			if (isset($lastname)) {
				$queries = preg_split("/\.\*/", $lastname);
				$newtext = $text;
				$hasallhits = true;
				foreach($queries as $index=>$query1) {
					if (preg_match("/(".$query1.")/i", $text)) {
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
					}
					else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
						$nlen = strlen($query1);
						$npos = strpos(Str2Upper($text), Str2Upper($query1));
						$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
						$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
					}
					else $hasallhits = false;
				}
				if ($hasallhits) $text = $newtext;
			}
			if (isset($place)) {
				$queries = preg_split("/\.\*/", $place);
				$newtext = $text;
				$hasallhits = true;
				foreach($queries as $index=>$query1) {
					if (preg_match("/(".$query1.")/i", $text)) {
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
					}
					else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
						$nlen = strlen($query1);
						$npos = strpos(Str2Upper($text), Str2Upper($query1));
						$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
						$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
					}
					else $hasallhits = false;
				}
				if ($hasallhits) $text = $newtext;
			}
			if (isset($year)) {
				$queries = preg_split("/\.\*/", $year);
				$newtext = $text;
				$hasallhits = true;
				foreach($queries as $index=>$query1) {
					if (preg_match("/(".$query1.")/i", $text)) {
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
					}
					else $hasallhits = false;
				}
				if ($hasallhits) $text = $newtext;
			}
		}
	}
	if (hasRTLText($text)) {
		if (hasLTRText($text)) {
			// Text contains both RtL and LtR characters
			// return the parenthesis with surrounding &rlm; and the rest as is
			$printvalue = "";
			$first = 1;
			$linestart = 0;
			for ($i=0; $i<strlen($text); $i++) {
				$byte = substr($text,$i,1);
				if (substr($text,$i,6) == "<br />") $linestart = $i+6;
				if (in_array($byte,$SpecialPar) || (($i==strlen($text)-1 || substr($text,$i+1,6)=="<br />") && in_array($byte,$SpecialChar))) {
					if ($first==1) {
						if ($byte==")" && !in_array(substr($text,$i+1),$SpecialClose)) {
							$printvalue .= "&lrm;".$byte."&lrm;";
							$linestart = $i+1;
						}
						else	if (in_array($byte,$SpecialChar)) {                          //-- all special chars
							if (hasRTLText(substr($text,$linestart,4))) $printvalue .= "&rlm;".$byte."&rlm;";
							else $printvalue .= "&lrm;".$byte."&lrm;";
						}
						else {
							$first = 0;
							if (hasRTLText(substr($text,$i+1,4))) {
								$printvalue .= "&rlm;";
								$ltrflag = 0;
							}
							else {
								$printvalue .= "&lrm;";
								$ltrflag = 1;
							}
							$printvalue .= substr($text,$i,1);
						}
					}
					else {
						$first = 1;
						$printvalue .= substr($text,$i,1);
						if ($ltrflag) $printvalue .= "&lrm;";
						else $printvalue .= "&rlm;";
					}
				}
				else if (oneRTLText(substr($text,$i,2))) {
					$printvalue .= substr($text,$i,2);
					$i++;
				}
				else $printvalue .= substr($text,$i,1);
			}
			if (!$first) if ($ltrflag) $printvalue .= "&lrm;";
			else $printvalue .= "&rlm;";
			return $printvalue;
		}
		else return "&rlm;".$text."&rlm;";
	}
	else if ($TEXT_DIRECTION=="rtl" && hasLTRText($text)) {
		$printvalue = "";
		$linestart = 0;
		$first = 1;
		for ($i=0; $i<strlen($text); $i++) {
			$byte = substr($text,$i,1);
			if (substr($text,$i,6) == "<br />") $linestart = $i+6;
			if (in_array($byte,$SpecialPar)	|| (($i==strlen($text)-1 || substr($text,$i+1,6)=="<br />") && in_array($byte,$SpecialChar))) {
				if ($first==1) {
					if ($byte==")" && !in_array(substr($text,$i+1),$SpecialClose)) {
						$printvalue .= "&rlm;".$byte."&rlm;";
						$linestart = $i+1;
					}
					else if (in_array($byte,$SpecialChar) && ($i==strlen($text)-1 || substr($text,$i+1,6)=="<br />")) {
						if (hasRTLText(substr($text,$linestart,4))) $printvalue .= "&rlm;".$byte."&rlm;";
						else $printvalue .= "&lrm;".$byte."&lrm;";
					}
					else {
						$first = 0;
						if (hasRTLText(substr($text,$i+1,4))) {
							$printvalue .= "&rlm;";
							$ltrflag = 0;
						}
						else {
							$printvalue .= "&lrm;";
							$ltrflag = 1;
						}
						$printvalue .= substr($text,$i,1);
					}
				}
				else {
					$first = 1;
					$printvalue .= substr($text,$i,1);
					if ($ltrflag) $printvalue .= "&lrm;";
					else $printvalue .= "&rlm;";
				}
			}
			else {
				if (oneRTLText(substr($text,$i,2))) {
					$printvalue .= substr($text,$i,2);
					$i++;
				}
				else $printvalue .= substr($text,$i,1);
			}
		}
		if (!$first) if ($ltrflag) $printvalue .= "&lrm;";
		else $printvalue .= "&rlm;";
		return $printvalue;
	}
	else {
		return $text;
	}
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
function print_asso_rela_record($pid, $factrec, $linebr=false) {
	global $GEDCOM, $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS, $TEXT_DIRECTION, $gm_lang, $factarray, $GM_IMAGE_DIR, $GM_IMAGES, $view, $Users;
	// get ASSOciate(s) ID(s)
	$ct = preg_match_all("/\d ASSO @(.*)@/", $factrec, $match, PREG_SET_ORDER);
	if ($ct) print "<br />";
	for ($i=0; $i<$ct; $i++) {
		$level = substr($match[$i][0],0,1);
		$pid2 = $match[$i][1];
		if (!empty($pid2)) {
			// get RELAtionship field
			$assorec = GetSubRecord($level, " ASSO ", $factrec, $i+1);
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
			$gedrec = FindGedcomRecord($pid2);
			$change = false;
			if (empty($gedrec) && $show_changes && $Users->UserCanEdit($Users->GetUserName())) {
				if (GetChangeData(true, $pid2, true)) {
					$change = true;
					$rec = GetChangeData(false, $pid2, true, "gedlines");
					$gedrec = $rec[$GEDCOM][$pid2];
				}
			}
			if (strstr($gedrec, "@ INDI")!==false
			or  strstr($gedrec, "@ SUBM")!==false) {
				// ID name
				if (showLivingNameByID($pid2)) {
					$name = GetPersonName($pid2);
					$addname = GetAddPersonName($pid2);
				}
				else {
					$name = $gm_lang["private"];
					$addname = "";
				}
				print "<a href=\"individual.php?pid=$pid2&amp;ged=$GEDCOM\">" . PrintReady($name);
	//			if (!empty($addname)) print "<br />" . PrintReady($addname);
				if (!empty($addname)) print " - " . PrintReady($addname);
				if ($SHOW_ID_NUMBERS) print " <span dir=\"$TEXT_DIRECTION\">($pid2)</span>";
				print "</a>";
				// ID age. The age and relationship links should only be printed if relevant, i.e. if the details of pid2 are not hidden.
				if (DisplayDetailsById($pid2, "INDI")) {
					if (!strstr($factrec, "_BIRT_")) {
						$dct = preg_match("/2 DATE (.*)/", $factrec, $dmatch);
						if ($dct>0) print " <span class=\"age\">".GetAge($gedrec, $dmatch[1])."</span>";
					}
					// RELAtionship calculation : for a family print relationship to both spouses
					if ($view!="preview" && !$change) {
						$famrec = FindFamilyRecord($pid);
						if ($famrec) {
							$parents = FindParentsInRecord($famrec);
							$pid1 = $parents["HUSB"];
							if ($pid1 and $pid1!=$pid2) print " - <a href=\"relationship.php?pid1=$pid1&amp;pid2=$pid2&amp;followspouse=1&amp;ged=$GEDCOM\">[" . $gm_lang["relationship_chart"] . "<img src=\"$GM_IMAGE_DIR/" . $GM_IMAGES["sex"]["small"] . "\" title=\"" . $gm_lang["husband"] . "\" alt=\"" . $gm_lang["husband"] . "\" class=\"sex_image\" />]</a>";
							$pid1 = $parents["WIFE"];
							if ($pid1 and $pid1!=$pid2) print " - <a href=\"relationship.php?pid1=$pid1&amp;pid2=$pid2&amp;followspouse=1&amp;ged=$GEDCOM\">[" . $gm_lang["relationship_chart"] . "<img src=\"$GM_IMAGE_DIR/" . $GM_IMAGES["sexf"]["small"] . "\" title=\"" . $gm_lang["wife"] . "\" alt=\"" . $gm_lang["wife"] . "\" class=\"sex_image\" />]</a>";
						}
						else if ($pid!=$pid2) print " - <a href=\"relationship.php?pid1=$pid&amp;pid2=$pid2&amp;followspouse=1&amp;ged=$GEDCOM\">[" . $gm_lang["relationship_chart"] . "]</a>";
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
			print_fact_notes($assorec, $level+1);
			if ($linebr && $i != ($ct-1)) print "<br />\n";
			if (substr($_SERVER["SCRIPT_NAME"],1) == "pedigree.php") {
				print "<br />";
				print_fact_sources($assorec, $level+1);
			}
		}
	}
}
/**
 * Print age of parents
 *
 * @param string $pid	child ID
 * @param string $bdate	child birthdate
 */
function print_parents_age($pid, $bdate) {
	global $gm_lang, $SHOW_PARENTS_AGE, $GM_IMAGE_DIR, $GM_IMAGES;
	if ($SHOW_PARENTS_AGE) {
		$famids = FindFamilyIds($pid);
		// dont show age of parents if more than one family (ADOPtion)
		if (count($famids)==1) {
			$father_text = "";
			$mother_text = "";
			$parents = FindParents($famids[0]["famid"]);
			// father
			$spouse = $parents["HUSB"];
			if ($spouse && showFact("BIRT", $spouse)) {
				$age = ConvertNumber(GetAge(FindPersonRecord($spouse), $bdate, false));
				if (10<$age && $age<80) $father_text = "<img src=\"$GM_IMAGE_DIR/" . $GM_IMAGES["sex"]["small"] . "\" title=\"" . $gm_lang["father"] . "\" alt=\"" . $gm_lang["father"] . "\" class=\"sex_image\" />$age";
			}
			// mother
			$spouse = $parents["WIFE"];
			if ($spouse && showFact("BIRT", $spouse)) {
				$age = ConvertNumber(GetAge(FindPersonRecord($spouse), $bdate, false));
				if (10<$age && $age<80) $mother_text = "<img src=\"$GM_IMAGE_DIR/" . $GM_IMAGES["sexf"]["small"] . "\" title=\"" . $gm_lang["mother"] . "\" alt=\"" . $gm_lang["mother"] . "\" class=\"sex_image\" />$age";
			}
			if ((!empty($father_text)) || (!empty($mother_text))) print "<span class=\"age\">".$father_text.$mother_text."</span>";
		}
	}
}
/**
 * print fact DATE TIME
 *
 * @param string $factrec	gedcom fact record
 * @param boolean $anchor	option to print a link to calendar
 * @param boolean $time		option to print TIME value
 * @param string $fact		optional fact name (to print age)
 * @param string $pid		optional person ID (to print age)
 * @param string $indirec	optional individual record (to print age)
 */
function print_fact_date($factrec, $anchor=false, $time=false, $fact=false, $pid=false, $indirec=false, $prt=true) {
	global $factarray, $gm_lang;

	$prtstr = "";
	$ct = preg_match("/2 DATE (.+)/", $factrec, $match);
	if ($ct>0) {
		$prtstr .= " ";
		// link to calendar
		if ($anchor) $prtstr .= GetDateUrl($match[1]);
		// simple date
		else $prtstr .= GetChangedDate(trim($match[1]));
		// time
		if ($time) {
			$timerec = GetSubRecord(2, "2 TIME", $factrec);
			if (empty($timerec)) $timerec = GetSubRecord(2, "2 DATE", $factrec);
			$tt = preg_match("/[2-3] TIME (.*)/", $timerec, $tmatch);
			if ($tt>0) $prtstr .= " - <span class=\"date\">".$tmatch[1]."</span>";
		}
		if ($fact and $pid) {
			// age of parents at child birth
			if ($fact=="BIRT") print_parents_age($pid, $match[1]);
			// age at event
			else if ($fact!="CHAN") {
				if (!$indirec) $indirec=FindPersonRecord($pid);
				// do not print age after death
				$deatrec=GetSubRecord(1, "1 DEAT", $indirec);
				if ((CompareFacts($factrec, $deatrec)!=1)||(strstr($factrec, "1 DEAT"))) $prtstr .= GetAge($indirec,$match[1]);
			}
		}
		$prtstr .= " ";
	}
	else {
		// 1 DEAT Y with no DATE => print YES
		// 1 DEAT N is not allowed
		// It is not proper GEDCOM form to use a N(o) value with an event tag to infer that it did not happen.
		if (preg_match("/^1\s(BIRT|DEAT|MARR|DIV|CHR|CREM|BURI)\sY/", $factrec) && !preg_match("/\n2\s(DATE|PLAC)/", $factrec)) $prtstr .= $gm_lang["yes"]."&nbsp;";
	}
	// gedcom indi age
	$ages=array();
	$agerec = GetSubRecord(2, "2 AGE", $factrec);
	$daterec = GetSubRecord(2, "2 DATE", $factrec);
	if (empty($agerec)) $agerec = GetSubRecord(3, "3 AGE", $daterec);
	$ages[0] = $agerec;
	// gedcom husband age
	$husbrec = GetSubRecord(2, "2 HUSB", $factrec);
	if (!empty($husbrec)) $agerec = GetSubRecord(3, "3 AGE", $husbrec);
	else $agerec = "";
	$ages[1] = $agerec;
	// gedcom wife age
	$wiferec = GetSubRecord(2, "2 WIFE", $factrec);
	if (!empty($wiferec)) $agerec = GetSubRecord(3, "3 AGE", $wiferec);
	else $agerec = "";
	$ages[2] = $agerec;
	// print gedcom ages
	foreach ($ages as $indexval=>$agerec) {
		if (!empty($agerec)) {
			$prtstr .= "<span class=\"label\">";
			if ($indexval==1) $prtstr .= $gm_lang["husband"];
			else if ($indexval==2) $prtstr .= $gm_lang["wife"];
			else $prtstr .= $factarray["AGE"];
			$prtstr .= "</span>: ";
			$age = GetAgeAtEvent(substr($agerec,5));
			$prtstr .= PrintReady($age);
			$prtstr .= " ";
		}
	}
	if ($prt) {
		print $prtstr;
		if (!empty($prtstr)) return true;
		else return false;
	}
	else return $prtstr;
}
/**
 * print fact PLACe TEMPle STATus
 *
 * @param string $factrec	gedcom fact record
 * @param boolean $anchor	option to print a link to placelist
 * @param boolean $sub		option to print place subrecords
 * @param boolean $lds		option to print LDS TEMPle and STATus
 */
function print_fact_place($factrec, $anchor=false, $sub=false, $lds=false, $prt=true) {
	global $SHOW_PEDIGREE_PLACES, $TEMPLE_CODES, $gm_lang, $factarray;

	$printed = false;
	$out = false;
	$prtstr = "";
	$ct = preg_match("/2 PLAC (.*)/", $factrec, $match);
	if ($ct>0) {
		$printed = true;
		$prtstr .= "&nbsp;";
		// Split on chinese comma 239 188 140
		$match[1] = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $match[1]);
		$levels = preg_split("/,/", $match[1]);
		if ($anchor) {
			$place = trim($match[1]);
			$place = preg_replace("/\,(\w+)/",", $1", $place);
			// reverse the array so that we get the top level first
			$levels = array_reverse($levels);
			$prtstr .= "<a href=\"placelist.php?action=show&amp;";
			foreach($levels as $pindex=>$ppart) {
				 // routine for replacing ampersands
				 $ppart = preg_replace("/amp\%3B/", "", trim($ppart));
//				 print "parent[$pindex]=".htmlentities($ppart)."&amp;";
				 $prtstr .= "parent[$pindex]=".urlencode($ppart)."&amp;";			}
			$prtstr .= "level=".count($levels);
			$prtstr .= "\"> ";
			if (HasChinese($place)) $prtstr .= PrintReady($place."&nbsp;(".GetPinYin($place).")");
			else $prtstr .= PrintReady($place);
			$prtstr .= "</a>";
		}
		else {
			$prtstr .= " -- ";
			for ($level=0; $level<$SHOW_PEDIGREE_PLACES; $level++) {
				if (!empty($levels[$level])) {
					if ($level>0) $prtstr .= ", ";
					$prtstr .= PrintReady($levels[$level]);
				}
			}
			if (HasChinese($match[1])) {
				$ptext = "(";
				for ($level=0; $level<$SHOW_PEDIGREE_PLACES; $level++) {
					if (!empty($levels[$level])) {
						if ($level>0) $ptext .= ", ";
						$ptext .= GetPinYin($levels[$level]);
					}
				}
				$ptext .= ")";
				$prtstr .= " ".PrintReady($ptext);
			}
		}
	}
	$ctn=0;
	if ($sub) {
		$placerec = GetSubRecord(2, "2 PLAC", $factrec);
		if (!empty($placerec)) {
			$rorec = GetSubRecord(3, "3 ROMN", $placerec);
			if (!empty($rorec)) {
				$roplac = GetGedcomValue("ROMN", 3, $rorec);
				if (!empty($roplac)) {
					if ($ct>0) $prtstr .= " - ";
					$prtstr .= " ".PrintReady($roplac);
					$rotype = GetGedcomValue("TYPE", 4, $rorec);
					if (!empty($rotype)) {
						$prtstr .= " ".PrintReady("(".$rotype.")");
					}
				}
			}
			$cts = preg_match("/\d _HEB (.*)/", $placerec, $match);
			if ($cts>0) {
//				if ($ct>0) print "<br />\n";
				if ($ct>0) $prtstr .= " - ";
				$prtstr .= " ".PrintReady($match[1]);
			}
			$map_lati="";
			$cts = preg_match("/\d LATI (.*)/", $placerec, $match);
			if ($cts>0) {
				$map_lati = trim($match[1]);
				$prtstr .= "<br />".$factarray["LATI"].": ".$match[1];
			}
			$map_long="";
			$cts = preg_match("/\d LONG (.*)/", $placerec, $match);
			if ($cts>0) {
				$map_long = trim($match[1]);
				$prtstr .= " ".$factarray["LONG"].": ".$match[1];
			}
			if (!empty($map_lati) and !empty($map_long)) {
				$prtstr .= " <a target=\"_BLANK\" href=\"http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=decimal&latitude=".$map_lati."&longitude=".$map_long."\"><img src=\"images/mapq.gif\" border=\"0\" alt=\"Mapquest &copy;\" title=\"Mapquest &copy;\" /></a>";
				if (is_numeric($map_lati) && is_numeric($map_long)) {
					$prtstr .= " <a target=\"_BLANK\" href=\"http://maps.google.com/maps?spn=.2,.2&ll=".$map_lati.",".$map_long."\"><img src=\"images/bubble.gif\" border=\"0\" alt=\"Google Maps &copy;\" title=\"Google Maps &copy;\" /></a>";
				}
				else $prtstr .= " <a target=\"_BLANK\" href=\"http://maps.google.com/maps?q=".$map_lati.",".$map_long."\"><img src=\"images/bubble.gif\" border=\"0\" alt=\"Google Maps &copy;\" title=\"Google Maps &copy;\" /></a>";
				$prtstr .= " <a target=\"_BLANK\" href=\"http://www.multimap.com/map/browse.cgi?lat=".$map_lati."&lon=".$map_long."&scale=icon=x\"><img src=\"images/multim.gif\" border=\"0\" alt=\"Multimap &copy;\" title=\"Multimap &copy;\" /></a>";
				$prtstr .= " <a target=\"_BLANK\" href=\"http://www.terraserver.com/imagery/image_gx.asp?cpx=".$map_long."&cpy=".$map_lati."&res=30&provider_id=340\"><img src=\"images/terrasrv.gif\" border=\"0\" alt=\"TerraServer &copy;\" title=\"TerraServer &copy;\" /></a>";
			}
			$ctn = preg_match("/\d NOTE (.*)/", $placerec, $match);
			if ($ctn>0) {
				// To be done: part of returnstring of this function
				print_fact_notes($placerec, 3);
				$out = true;
			}
		}
	}
	if ($lds) {
		$ct = preg_match("/2 TEMP (.*)/", $factrec, $match);
		if ($ct>0) {
			$tcode = trim($match[1]);
			if (array_key_exists($tcode, $TEMPLE_CODES)) {
				$prtstr .= "<br />".$gm_lang["temple"].": ".$TEMPLE_CODES[$tcode];
			}
			else {
				$prtstr .= "<br />".$gm_lang["temple_code"].$tcode;
			}
		}
		$ct = preg_match("/2 STAT (.*)/", $factrec, $match);
		if ($ct>0) {
			$prtstr .= "<br />".$gm_lang["status"].": ";
			$prtstr .= trim($match[1]);
		}
	}
	if ($prt) {
		print $prtstr;
		return $printed;
	}
	else return $prtstr;
}
/**
 * print first major fact for an Individual
 *
 * @param string $key	indi pid
 */
function print_first_major_fact($key, $indirec="", $prt=true) {
	global $gm_lang, $factarray, $GM_BASE_DIRECTORY, $factsfile, $LANGUAGE;
	
	$majorfacts = array("BIRT", "CHR", "BAPM", "DEAT", "BURI", "BAPL", "ADOP");
	if (empty($indirec)) $indirec = FindPersonRecord($key);
	$retstr = "";
	foreach ($majorfacts as $indexval => $fact) {
		$factrec = GetSubRecord(1, "1 $fact", $indirec);
		if (strlen($factrec)>7 and showFact("$fact", $key) and !FactViewRestricted($key, $factrec)) {
			$retstr .= " -- <i>";
			if (isset($gm_lang[$fact])) $retstr .= $gm_lang[$fact];
			else if (isset($factarray[$fact])) $retstr .= $factarray[$fact];
			else $retstr .= $fact;
			$retstr .= " ";
			$retstr .= print_fact_date($factrec, false, false, false, false, false, false);
			$retstr .= print_fact_place($factrec, false, false, false, false);
			$retstr .= "</i>";
			break;
		}
	}
	if ($prt) {
		print $retstr;
		return $fact;
	}
	else return addslashes($retstr);
}

// Print a new fact box on details pages
function PrintAddNewFact($id, $usedfacts, $type) {
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
 * javascript declaration for calendar popup
 *
 * @param none
 */
function init_calendar_popup() {
	global $monthtonum, $gm_lang, $WEEK_START;

	print "<script language=\"JavaScript\" type='text/javascript'>\n<!--\n";
	// month names
	print "cal_setMonthNames(";
	foreach($monthtonum as $mon=>$num) {
		if (isset($gm_lang[$mon])) {
			if ($num>1) print ",";
			print "\"".$gm_lang[$mon]."\"";
		}
	}
	print ");\n";
	// day headers
	print "cal_setDayHeaders(";
	foreach(array('sunday_1st','monday_1st','tuesday_1st','wednesday_1st','thursday_1st','friday_1st','saturday_1st') as $indexval => $day) {
		if (isset($gm_lang[$day])) {
			if ($day!=="sunday_1st") print ",";
			print "\"".$gm_lang[$day]."\"";
		}
	}
	print ");\n";
	// week start day
	print "cal_setWeekStart(".$WEEK_START.");\n";
	print "//-->\n</script>\n";
}

/**
 * Print the find individual link
 *
 * A link is printed which will give the user a popup
 * window to select an individual ID from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function PrintFindIndiLink($element_id, $gedid) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_id"];
	if (isset($GM_IMAGES["indi"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indi"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findIndi(document.getElementById('".$element_id."'), '".$gedid."'); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the find place link
 *
 * A link is printed which will give the user a popup
 * window to select an place from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_findplace_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_place"];
	if (isset($GM_IMAGES["place"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["place"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findPlace(document.getElementById('".$element_id."')); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the find family link
 *
 * A link is printed which will give the user a popup
 * window to select a family ID from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_findfamily_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_family"];
	if (isset($GM_IMAGES["family"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["family"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findFamily(document.getElementById('".$element_id."')); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the find special character link
 *
 * A link is printed which will give the user a popup
 * window with a list of special characters that can be
 * inserted into the editing field.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_specialchar_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_specialchar"];
	if (isset($GM_IMAGES["keyboard"]["button"])) $Link = "<img id=\"".$element_id."_spec\" name=\"".$element_id."_spec\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["keyboard"]["button"]."\"  alt=\"".$text."\"  title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findSpecialChar(document.getElementById('$element_id')); return false;\">";
	print $Link;
	print "</a>";
}

function print_autopaste_link($element_id, $choices, $concat=1, $updatename=true) {
	global $gm_lang;

	print "<small>";
	foreach ($choices as $indexval => $choice) {
		print " &nbsp;<a href=\"javascript: ".$gm_lang["copy"]."\" onclick=\"document.getElementById('".$element_id."').value ";
		if ($concat) print "+=' "; else print "='";
		print $choice."'; ";
		if ($updatename) print "updatewholename(); ";
		print "return false;\">".$choice."</a>";
	}
	print "</small>";
}

/**
 * Print the find source link
 *
 * A link is printed which will give the user a popup
 * window to select a source ID from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_findsource_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_sourceid"];
	if (isset($GM_IMAGES["source"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findSource(document.getElementById('".$element_id."')); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the add source link
 *
 * A link is printed which will give the user a popup
 * window with an editing form to create a new source
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_addnewsource_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["create_source"];
	if (isset($GM_IMAGES["addsource"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["addsource"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewsource(document.getElementById('".$element_id."'), 'create_source'); return false;\">";
	print $Link;
	print "</a>";
}
/**
 * Print the find repository link
 *
 * A link is printed which will give the user a popup
 * window to select a repository ID from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_findrepository_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_repository"];
	if (isset($GM_IMAGES["repository"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findRepository(document.getElementById('".$element_id."')); return false;\">";
	print $Link;
	print "</a>";
}
/**
 * Print the add repository link
 *
 * A link is printed which will give the user a popup
 * window with an editing form to create a new repository
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_addnewrepository_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["create_repository"];
	if (isset($GM_IMAGES["addrepository"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["addrepository"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewrepository(document.getElementById('".$element_id."'), 'create_repository'); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the find media link
 *
 * A link is printed which will give the user a popup
 * window to select a media name from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_findmedia_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_media"];
	if (isset($GM_IMAGES["media"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findMedia(document.getElementById('".$element_id."')); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the find object link
 *
 * A link is printed which will give the user a popup
 * window to select an object ID from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_findobject_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_media"];
	if (isset($GM_IMAGES["media"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findObject(document.getElementById('".$element_id."')); return false;\">";
	print $Link;
	print "</a>";
}
/**
 * Print the add media link
 *
 * A link is printed which will give the user a popup
 * window with an editing form to create a new media object
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_addnewobject_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["add_media"];
	if (isset($GM_IMAGES["addmedia"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["addmedia"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewmedia(document.getElementById('".$element_id."'), 'create_media'); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the find note link
 *
 * A link is printed which will give the user a popup
 * window to select a note ID from the find screen.
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_findnote_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["find_noteid"];
	if (isset($GM_IMAGES["note"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print " <a href=\"javascript: ".$text."\" onclick=\"findNote(document.getElementById('".$element_id."')); return false;\">";
	print $Link;
	print "</a>";
}

/**
 * Print the add general note link
 *
 * A link is printed which will give the user a popup
 * window with an editing form to create a new repository
 *
 * @author	Genmod Development Team
 * @param		$element_id	The ID of the form field
 */
function print_addnewgnote_link($element_id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	$text = $gm_lang["create_general_note"];
	if (isset($GM_IMAGES["addnote"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["addnote"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewgnote(document.getElementById('".$element_id."'), 'create_general_note'); return false;\">";
	print $Link;
	print "</a>";
}


/**
 * Print a list of individual names
 *
 * A table with columns is printed from an array of names.
 * A distinction is made between a list for the find page or a
 * page listing.
 *
 * @todo		Add statistics for private and hidden links
 * @author	Genmod Development Team
 * @param		array		$personlist	The array with names to be printed
 * @param		boolean	$print_all	Set to yes to print all individuals
 * @param		boolean	$find		Set to yes to print links for the find pages
 */
function PrintPersonList($personlist, $print_all=true, $find=false, $allgeds = "no") {
	global $TEXT_DIRECTION, $GEDCOMS, $GEDCOM, $gm_lang, $SHOW_MARRIED_NAMES, $LISTS_ALL;
	global $indi_private, $indi_hide, $surname, $show_all_firstnames, $alpha, $falpha;
	global $surname_sublist, $show_all, $GEDCOMID, $ALPHA_INDEX_LISTS, $indilist, $year;

	print "<table class=\"center ".$TEXT_DIRECTION."\"><tr>";
	// NOTE: The list is really long so divide it up again by the first letter of the first name
	if ($ALPHA_INDEX_LISTS && count($personlist) > $ALPHA_INDEX_LISTS && $print_all == true && $find == false) {
		$firstalpha = array();
		foreach($personlist as $gid=>$indi) {
			$indi = $indilist[$gid];
			foreach($indi["names"] as $indexval => $namearray) {
				$letter = Str2Upper(GetFirstLetter($namearray[0]));
				if (!isset($firstalpha[$letter])) {
					$firstalpha[$letter] = array("letter"=>$letter, "ids"=>$gid);
				}
				else $firstalpha[$letter]["ids"] .= ",".$gid;
			}
		}
		// NOTE: Sort the person array
		uasort($firstalpha, "LetterSort");
		// NOTE: Print the second alpha letter list for the unknown names
		print "<td class=\"shade1 list_value wrap center\" colspan=\"2\">\n";
		print_help_link("firstname_alpha_help", "qm");
		print $gm_lang["first_letter_fname"]."<br />\n";
		foreach($firstalpha as $letter=>$list) {
			$pass = false;
			if ($letter != "@") {
				if (!isset($fstartalpha) && (!isset($falpha) || !isset($firstalpha[$falpha]))) {
					$fstartalpha = $letter;
					$falpha = $letter;
				}
				// NOTE: Print the link letter
				if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
				if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
				// NOTE: only include the alpha letter when not showing the ALL list
				if ($show_all == "no") print "alpha=".urlencode($alpha)."&amp;";
				if ($surname_sublist == "yes" && isset($surname)) print "surname=".$surname."&amp;";
				print "falpha=".urlencode($letter)."&amp;show_all=".$show_all."&amp;surname_sublist=".$surname_sublist;
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\">";
				// NOTE: Red color for the chosen letter otherwise simply print the letter
				if (($falpha==$letter)&&($show_all_firstnames=="no")) print "<span class=\"warning\">".$letter."</span>";
				else print $letter;
				print "</a> | \n";
			}
			if ($letter === "@") {
				$pass = true;
			}
		}
		if (!isset($pass)) $pass = false;
		// NOTE: Print the Unknown text on the letter bar
		if ($pass == true) {
			if (isset($falpha) && $falpha == "@") {
				if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
				if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
				print "alpha=".urlencode($alpha)."&amp;falpha=@&amp;surname_sublist=yes";
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
			}
			else {
				if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
				if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
				print "alpha=".urlencode($alpha)."&amp;falpha=@&amp;surname_sublist=yes";
				print "\">".PrintReady($gm_lang["NN"])."</a>";
			}
			$pass = false;
		}
		if ($LISTS_ALL) {
			print " | \n";
			if (strstr($_SERVER["SCRIPT_NAME"],"indilist.php")) print "<a href=\"indilist.php?";
			if (strstr($_SERVER["SCRIPT_NAME"],"aliveinyear.php")) print "<a href=\"aliveinyear.php?year=$year&amp;";
			// NOTE: only include the alpha letter when not showing the ALL list
			if ($show_all == "no") print "alpha=".urlencode($alpha)."&amp;";
			// NOTE: Include the surname if surnames are to be listed
			if ($allgeds == "yes") print "&amp;allgeds=yes&amp;";
			if ($surname_sublist == "yes" && isset($surname)) print "surname=".urlencode($surname)."&amp;";
			if ($show_all_firstnames=="yes") print "show_all_firstnames=no&amp;show_all=$show_all&amp;surname_sublist=$surname_sublist\"><span class=\"warning\">".$gm_lang["all"]."</span>\n";
			else print "show_all_firstnames=yes&amp;show_all=$show_all&amp;surname_sublist=$surname_sublist\">".$gm_lang["all"]."</a>\n";
		}
		print "</td></tr><tr>\n";
		
		if (isset($fstartalpha)) $falpha = $fstartalpha;
		// NOTE: Get only the names who start with the matching first letter
		if ($show_all_firstnames=="no") {
			$findilist = array();
			if (isset($firstalpha[$falpha])) {
				$ids = preg_split("/,/", $firstalpha[$falpha]["ids"]);
				foreach($ids as $indexval => $id) {
					$id = trim($id);
//					if (!empty($id)) $findilist[$id] = $personlist[$id];
					if (!empty($id)) $findilist[$id] = $indilist[$id];
				}
			}
			PrintPersonList($findilist, false, $find, $allgeds);
		}
		else PrintPersonList($personlist, false, $find, $allgeds);
	}
	else {
		$names = array();
		foreach($personlist as $gid => $indi) {
			$indi = $indilist[$gid];
			// NOTE: make sure that favorites from other gedcoms are not shown
			if ($indi["gedfile"]==$GEDCOMID || $find == true || $allgeds == "yes") {
				foreach($indi["names"] as $indexval => $namearray) {
					// NOTE: Only include married names if chosen to show so
					// NOTE: Do not include calculated names. Identified by C.
					if ($SHOW_MARRIED_NAMES || $namearray[3]!='C') {
						if ($allgeds == "yes") $names[] = array($namearray[0], $namearray[1], $namearray[2], $namearray[3], splitkey($gid, "id"), splitkey($gid, "ged"));
						else $names[] = array($namearray[0], $namearray[1], $namearray[2], $namearray[3], $gid, $GEDCOM);
					}
				}
			}
		}
		uasort($names, "ItemSort");
		reset($names);
		$total_indis = count($personlist);
		$count = count($names);
		$i=0;
		print "<td class=\"shade1 list_value indilist $TEXT_DIRECTION\">\n";
		foreach($names as $indexval => $namearray) {
			$name = CheckNN(SortableNameFromName($namearray[0]));
			if (HasChinese($name)) $name .= " (".CheckNN(SortableNameFromName(GetPinYin($namearray[0]))).")";
			print_list_person($namearray[4], array($name, $namearray[5]), $find);
			$i++;
			if ($i==ceil($count/2) && $count>8) print "</td><td class=\"shade1 list_value indilist $TEXT_DIRECTION\">\n";			
		}
		print "</td>\n";
		if ($count>1) {
			print "</tr><tr><td colspan=\"2\" class=\"center\">";
			if ($SHOW_MARRIED_NAMES) print $gm_lang["total_names"]." ".$count."<br />\n";
			print $gm_lang["total_indis"]." ".$total_indis;
			if (count($indi_private)>0) print "  (".$gm_lang["private"]." ".count($indi_private).")";
			if (count($indi_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($indi_hide);
			if (count($indi_private)>0 || count($indi_hide)>0) print_help_link("privacy_error_help", "qm");
			print "</td>\n";
		}
	}
	print "</tr></table>";
}

/**
 * Print a list of surnames
 *
 * A table with columns is printed from an array of surnames. This can be individuals
 * or families.
 *
 * @todo		Add statistics for private and hidden links
 * @author	Genmod Development Team
 * @param		array		$personlist	The array with names to be printed
 * @param		string		$page		The page the links should point to
 */
function PrintSurnameList($surnames, $page, $allgeds="no", $resturl="") {
	global $TEXT_DIRECTION, $GEDCOMS, $GEDCOM, $gm_lang, $SHOW_MARRIED_NAMES;
	global $surname_sublist, $indilist, $indi_hide, $indi_total;
	
	if (stristr($page, "aliveinyear")) {
		$aiy = true;
		global $indi_dead, $indi_alive, $indi_unborn;
	}
	else $aiy = false;
	
	$i = 0;
	$count_indi = 0;
	$col = 1;
	$count = count($surnames);
	if ($count == 0) return;
	else if ($count>36) $col=4;
	else if ($count>18) $col=3;
	else if ($count>6) $col=2;
	$newcol=ceil($count/$col);
	print "<table class=\"center $TEXT_DIRECTION\"><tr>";
	print "<td class=\"shade1 list_value wrap\">\n";
	
	// Surnames with starting and ending letters in 2 text orientations is shown in
	// a wrong way on the page with different orientation from the orientation of the first name letter
	foreach($surnames as $surname=>$namecount) {
		if (begRTLText($namecount["name"])) {
 			print "<div class =\"rtl\" dir=\"rtl\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=".$surname_sublist."&amp;surname=".urlencode($namecount["name"]).$resturl;
 			if ($allgeds == "yes") print "&amp;allgeds=yes";
 			print "\">&nbsp;";
 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
 			else print PrintReady($namecount["name"]);
 			print "&rlm; - [".($namecount["match"])."]&rlm;";
		}
		else if (substr($namecount["name"], 0, 4) == "@N.N") {
			print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".$namecount["alpha"]."&amp;surname_sublist=$surname_sublist&amp;surname=@N.N.".$resturl;
 			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">&nbsp;".$gm_lang["NN"] . "&lrm; - [".($namecount["match"])."]&lrm;&nbsp;";
		}
		else {
			print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=$surname_sublist&amp;surname=".urlencode($namecount["name"]).$resturl;
 			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
			else print PrintReady($namecount["name"]);
			print "&lrm; - [".($namecount["match"])."]&lrm;";
		}

 		print "</a></div>\n";
		$count_indi += $namecount["match"];
		$i++;
		if ($i==$newcol && $i<$count) {
			print "</td><td class=\"shade1 list_value wrap\">\n";
			$newcol=$i+ceil($count/$col);
		}
	}
	if ($aiy) $indi_total = $indi_alive + $indi_dead + $indi_unborn + count($indi_hide);
	else if (is_array($indi_total)) $indi_total = count($indi_total);
	print "</td>\n";
	if ($count>1 || count($indi_hide)>0) {
		print "</tr><tr><td colspan=\"$col\" class=\"center\">&nbsp;";
		if ($SHOW_MARRIED_NAMES && $count>1) print $gm_lang["total_names"]." ".$count_indi."<br />";
		if (isset($indi_total) && $count>1) print $gm_lang["total_indis"]." ".$indi_total."&nbsp;";
		if ($count>1 && count($indi_hide)>0) print "--&nbsp;";
		if (count($indi_hide)>0) print $gm_lang["hidden"]." ".count($indi_hide);
		if ($count>1 && $aiy) {
			print "<br />".$gm_lang["unborn"]."&nbsp;".$indi_unborn;
			print "&nbsp;--&nbsp;".$gm_lang["alive"]."&nbsp;".$indi_alive;
			print "&nbsp;--&nbsp;".$gm_lang["dead"]."&nbsp;".$indi_dead;
		}
		if ($count>1) print "<br />".$gm_lang["surnames"]." ".$count;
		print "</td>\n";
	}
	print "</tr></table>";
}
/**
 * Print a list of family names
 *
 * A table with columns is printed from an array of names.
 * A distinction is made between a list for the find page or a
 * page listing.
 *
 * @todo		Add statistics for private and hidden links
 * @author	Genmod Development Team
 * @param		array	$personlist	The array with names to be printed
 * @param		boolean	$print_all	Set to yes to print all individuals
 * @param		boolean	$find		Set to yes to print links for the find pages
 */
function PrintFamilyList($familylist, $print_all=true, $find=false, $allgeds="no") {
	global $TEXT_DIRECTION, $GEDCOMS, $GEDCOM, $gm_lang, $SHOW_MARRIED_NAMES, $COMBIKEY, $ALPHA_INDEX_LISTS, $LISTS_ALL;
	global $surname_sublist, $show_all, $famlist, $fam_hide, $alpha, $falpha;
	global $firstname_alpha, $fam_private, $show_all_firstnames, $surname;
	
	$count = count($familylist);
	
	print "<table class=\"center ".$TEXT_DIRECTION."\"><tr>";
	// NOTE: The list is really long so divide it up again by the first letter of the first name
	if ($ALPHA_INDEX_LISTS && $count > $ALPHA_INDEX_LISTS && $print_all == true) {
		$firstalpha = array();
		foreach($familylist as $gid=>$fam) {
			$fam = $famlist[$gid];
			$names = preg_split("/[,+] ?/", $fam["name"]);
			$letter = Str2Upper(GetFirstLetter(trim($names[1])));
			if (!isset($firstalpha[$letter])) {
				$firstalpha[$letter] = array("letter"=>$letter, "ids"=>$gid);
			}
			else $firstalpha[$letter]["ids"] .= ",".$gid;
			if (isset($names[2])&&isset($names[3])) {
				$letter = Str2Upper(GetFirstLetter(trim($names[2])));
				if ($letter==$alpha) {
					$letter = Str2Upper(GetFirstLetter(trim($names[3])));
					if (!isset($firstalpha[$letter])) {
						$firstalpha[$letter] = array("letter"=>$letter, "ids"=>$gid);
					}
					else $firstalpha[$letter]["ids"] .= ",".$gid;
				}
			}
		}
		// NOTE: Sort the family array
		uasort($firstalpha, "LetterSort");
		print "<td class=\"shade1 list_value wrap center\" colspan=\"2\">\n";
		print $gm_lang["first_letter_fname"]."<br />\n";
		foreach($firstalpha as $letter=>$list) {
			$pass = false;
			if ($letter != "@") {
				if (!isset($fstartalpha) && !isset($falpha)) {
					$fstartalpha = $letter;
					$falpha = $letter;
				}
				// NOTE: Print the link letter
				print "<a href=\"famlist.php?";
				// NOTE: only include the alpha letter when not showing the ALL list
				if ($show_all == "no") print "alpha=".urlencode($alpha)."&amp;";
				if ($surname_sublist == "yes" && isset($surname)) print "surname=".$surname."&amp;";
				print "falpha=".urlencode($letter)."&amp;show_all=$show_all&amp;surname_sublist=".$surname_sublist;
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\">";
				// NOTE: Red color for the chosen letter otherwise simply print the letter
				if (($falpha==$letter)&&($show_all_firstnames=="no")) print "<span class=\"warning\">".$letter."</span>";
				else print $letter;
				print "</a> | \n";
			}
			if ($letter === "@") {
				$pass = TRUE;
			}
		}
		// NOTE: Print the Unknown text on the letter bar
		if ($pass == TRUE) {
			if (isset($falpha) && $falpha == "@") {
				print "<a href=\"famlist.php?alpha=".urlencode($alpha)."&amp;surname=".urlencode($surname)."&amp;falpha=@&amp;surname_sublist=yes";
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\"><span class=\"warning\">".PrintReady($gm_lang["NN"])."</span></a>";
			}
			else {
				print "<a href=\"famlist.php?alpha=".urlencode($alpha)."&amp;surname=".urlencode($surname)."&amp;falpha=@&amp;surname_sublist=yes";
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\">".PrintReady($gm_lang["NN"])."</a>";
			}
			if ($LISTS_ALL) print " | \n";
			$pass = FALSE;
		}
		if ($LISTS_ALL) {
			print "<a href=\"famlist.php?";
			// NOTE: only include the alpha letter when not showing the ALL list
			if ($show_all == "no") print "alpha=".urlencode($alpha)."&amp;";
			// NOTE: Include the surname if surnames are to be listed
			if ($allgeds == "yes") print "allgeds=yes&amp;";
			if ($surname_sublist == "yes" && isset($surname)) print "surname=".urlencode($surname)."&amp;";
			if ($show_all_firstnames=="yes") print "show_all_firstnames=no&amp;show_all=$show_all&amp;surname_sublist=$surname_sublist\"><span class=\"warning\">".$gm_lang["all"]."</span>\n";
			else print "show_all_firstnames=yes&amp;show_all=$show_all&amp;surname_sublist=$surname_sublist\">".$gm_lang["all"]."</a>\n";
		}
		print "</td></tr><tr>\n";
		if (isset($fstartalpha)) $falpha = $fstartalpha;
		// NOTE: Get only the names who start with the matching first letter
		if ($show_all_firstnames=="no") {
			$ffamlist = array();
			if (isset($firstalpha[$falpha])) {
				$ids = preg_split("/,/", $firstalpha[$falpha]["ids"]);
				foreach($ids as $indexval => $id) {
					$ffamlist[$id] = $famlist[$id];
				}
			}
			PrintFamilyList($ffamlist, false, false, $allgeds);
		}
		else PrintFamilyList($familylist, false, false, $allgeds);
	}
	else {
		uasort($familylist, "ItemSort");
		$i=0;
		print "<td class=\"shade1 list_value indilist\">\n";
		foreach($familylist as $gid => $fam) {
			$fam = $famlist[$gid];
			$fam["name"] = CheckNN($fam["name"]);
			$pass = false;
			$famged = get_gedcom_from_id($fam["gedfile"]);
			if ($COMBIKEY) $gid = SplitKey($gid, "id");
			if (HasChinese($fam["name"])) $fam["name"] .= " (".GetFamilyAddDescriptor($gid, false, $fam["gedcom"], false).")";
			print_list_family($gid, array($fam["name"], $famged));
			$i++;
			if ($i==ceil($count/2) && $count>8) print "</td><td class=\"shade1 list_value indilist\">\n";
		}
		print "</td>\n";
		$i = 0;
		$count = count($familylist);
		$col = 1;
		if ($count>36) $col=4;
		else if ($count>18) $col=3;
		else if ($count>6) $col=2;
		$newcol=ceil($count/$col);
		if ($count>1 || count($fam_hide)>0) {
			print "</tr><tr><td colspan=\"$col\" align=\"center\">&nbsp;";
			if ($count>1) print $gm_lang["total_fams"]." ".count($famlist)."&nbsp;";
			if ($count>1 && count($fam_hide)>0) print "--&nbsp;";
			if (count($fam_hide)>0) print $gm_lang["hidden"]." ".count($fam_hide);
//			if ($count>1) print "<br />".$gm_lang["surnames"]." ".$count;
			print "</td>\n";
		}
	}
	print "</tr></table>";
}

/**
 * Creates a list of div ids to hide
 *
 * A list of divs to hide for a menu.
 *
 * @author	Genmod Development Team
 * @param		array	$pages	The array with pages to hide
 * @param		string	$show	The name of the page to show
 */
function HideDivs($pages, $show) {
	foreach ($pages as $id => $page) {
		if ($page == $show) {
			echo "expand_layer('".$page."', true); ";
			echo "ChangeClass('".$page."_tab', 'current'); ";
		}
		else {
			echo "expand_layer('".$page."', false); ";
			echo "ChangeClass('".$page."_tab', ''); ";
		}
	}
}

/**
 * Shows debug information
 *
 * Creates a menu with different sections to show debug info
 * It shows info on output (defined by developer), database queries,
 * _session, _post and _get variable
 *
 * @author	Genmod Development Team
 * @param		array	$pages	The array with pages to hide
 * @param		string	$show	The name of the page to show
 */
function PrintDebug() {
	global $debugcollector;
	
	// If we don't show the debug, return empty
	if (!$debugcollector->show) return false;
	else {
		$pages = array("output", "queries", "session", "post", "get");
		?>
		<div id="debug_output">
		<ul>
			<li id="output_tab" class="current" ><a href="#" onclick="<?php HideDivs($pages, 'output');  ?> return false;">Output</a></li>
			<li id="queries_tab"><a href="#" onclick="<?php HideDivs($pages, 'queries');  ?> return false;">Queries</a></li>
			<li id="session_tab"><a href="#" onclick="<?php HideDivs($pages, 'session');  ?> return false;">SESSION</a></li>
			<li id="post_tab"><a href="#" onclick="<?php HideDivs($pages, 'post');  ?> return false;">POST</a></li>
			<li id="get_tab"><a href="#" onclick="<?php HideDivs($pages, 'get');  ?> return false;">GET</a></li>
		</ul>
		</div>
		<?php
		echo '<div id="output" style="display: show;">';
		echo $debugcollector->PrintRHtml($debugcollector->debugoutputselect("output"));
		echo '</div>';
		echo '<div id="queries" style="display: none;">';
		echo $debugcollector->PrintRHtml($debugcollector->debugoutputselect("queries"));
		echo '</div>';
		echo '<div id="session" style="display: none;">';
		echo $debugcollector->PrintRHtml($_SESSION);
		echo '</div>';
		echo '<div id="post" style="display: none;">';
		echo $debugcollector->PrintRHtml($_POST);
		echo '</div>';
		echo '<div id="get" style="display: none;">';
		echo $debugcollector->PrintRHtml($_GET);
		echo '</div>';
	}
}

function ExpandUrl($text) {
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

function PrintFilterEvent($filterev) {
	global $gm_lang, $factarray;
	
	print "<option value=\"all\"";
	if ($filterev == "all") print " selected=\"selected\"";
	print ">".$gm_lang["all"]."</option>\n";		
	
	print "<option value=\"BIRT\"";
	if ($filterev == "BIRT") print " selected=\"selected\"";
	print ">".$factarray["BIRT"]."</option>\n";
	print "<option value=\"CHR\"";
	if ($filterev == "CHR") print " selected=\"selected\"";
	print ">".$factarray["CHR"]."</option>\n";
	print "<option value=\"CHRA\"";
	if ($filterev == "CHRA") print " selected=\"selected\"";
	print ">".$factarray["CHRA"]."</option>\n";
	print "<option value=\"BAPM\"";
	if ($filterev == "BAPM") print " selected=\"selected\"";
	print ">".$factarray["BAPM"]."</option>\n";
	print "<option value=\"_COML\"";
	if ($filterev == "_COML") print " selected=\"selected\"";
	print ">".$factarray["_COML"]."</option>\n";
	print "<option value=\"MARR\"";
	if ($filterev == "MARR") print " selected=\"selected\"";
	print ">".$factarray["MARR"]."</option>\n";
//	print "<option value=\"_SEPR\"";
//	if ($filterev == "_SEPR") print " selected=\"selected\"";
//	print ">".$factarray["_SEPR"]."</option>\n";
	print "<option value=\"DIV\"";
	if ($filterev == "DIV") print " selected=\"selected\"";
	print ">".$factarray["DIV"]."</option>\n";
	print "<option value=\"DEAT\"";
	if ($filterev == "DEAT") print " selected=\"selected\"";
	print ">".$factarray["DEAT"]."</option>\n";
	print "<option value=\"BURI\"";
	if ($filterev == "BURI") print " selected=\"selected\"";
	print ">".$factarray["BURI"]."</option>\n";
	print "<option value=\"IMMI\"";
	if ($filterev == "IMMI") print " selected=\"selected\"";
	print ">".$factarray["IMMI"]."</option>\n";
	print "<option value=\"EMIG\"";
	if ($filterev == "EMIG") print " selected=\"selected\"";
	print ">".$factarray["EMIG"]."</option>\n";
	print "<option value=\"EVEN\"";
	if ($filterev == "EVEN") print " selected=\"selected\"";
	print ">".$gm_lang["custom_event"]."</option>\n";
	print "</select>\n";
}
function PrintBlockFavorites($userfavs, $side, $index, $style) {
	global $GEDCOM, $command, $Users, $gm_username, $gm_lang;
	
	$mygedcom = $GEDCOM;
	$current_gedcom = $GEDCOM;
	foreach($userfavs as $key=>$favorite) {
		if (isset($favorite->id)) $key=$favorite->id;
		SwitchGedcom($favorite->file);
		if ($favorite->type=="URL") {
			print "<div id=\"boxurl".$key.".0\" class=\"person_box";
			print "\"><ul>\n";
			print "<li><a href=\"".$favorite->url."\">".PrintReady($favorite->title)."</a></li>";
			print "</ul>";
			print "<span class=\"favorite_padding\">".PrintReady($favorite->note)."</span>";
			print "</div>\n";
		}
		else {
			if ($favorite->type=="SOUR" && $favorite->object->disp) {
				print "<div id=\"box".$favorite->object->xref.".0\" class=\"person_box";
				print "\"><ul>\n";
				print_list_source($favorite->object->xref, array("name"=>$favorite->object->descriptor, "gedfile"=>$favorite->file));
				print "</ul>";
				if (!empty($favorite->note)) print "<span class=\"favorite_padding\">".PrintReady($gm_lang["note"].": ".$favorite->note)."</span>";
				print "</div>\n";
			}
			else if (DisplayDetailsByID($favorite->gid, $favorite->type, 1, true)) {
				$indirec = FindGedcomRecord($favorite->gid);
				if ($favorite->type=="INDI") {
					print "<div id=\"box".$favorite->gid.".0\" class=\"person_box";
					if (preg_match("/1 SEX F/", $indirec)>0) print "F";
					else if (preg_match("/1 SEX M/", $indirec)>0) print "";
					else print "NN";
					print "\">\n";
					print_pedigree_person($favorite->gid, $style, 1, $key);
//					print "</div>\n";
					if (!empty($favorite->note)) print "<span class=\"favorite_padding\">".PrintReady($gm_lang["note"].": ".$favorite->note)."</span>";
					print "</div>\n";
				}
				if ($favorite->type=="FAM") {
					print "<div id=\"box".$favorite->gid.".0\" class=\"person_box";
					print "\"><ul>\n";
					print_list_family($favorite->gid, array(GetFamilyDescriptor($favorite->gid), get_gedcom_from_id($favorite->file)));
					print "</ul>";
					if (!empty($favorite->note)) print "<span class=\"favorite_padding\">".PrintReady($gm_lang["note"].": ".$favorite->note)."</span>";
					print "</div>\n";
				}
				if ($favorite->type=="OBJE") {
					print "<div id=\"box".$favorite->gid.".0\" class=\"person_box";
					print "\"><ul>\n";
					print_media_links("0 OBJE @".$favorite->gid."@", 0);
					print "</ul>";
					if (!empty($favorite->note)) print "<span class=\"favorite_padding\">".PrintReady($gm_lang["note"].": ".$favorite->note)."</span>";
					print "</div>\n";
				}
				if ($favorite->type=="NOTE") {
					print "<div id=\"box".$favorite->gid.".0\" class=\"person_box";
					print "\"><ul>\n";
					$note_controller = new NoteController($favorite->gid);
						$note_controller->note->PrintListNote(80);
					print "</ul>";
					if (!empty($favorite->note)) print "<span class=\"favorite_padding\">".PrintReady($gm_lang["note"].": ".$favorite->note)."</span>";
					print "</div>\n";
				}
			}
			if ($command=="user" || $Users->userIsAdmin($gm_username)) {
				if (!empty($favorite->note)) print "&nbsp;&nbsp;";
				print "<a class=\"font9\" href=\"index.php?command=$command&amp;action=deletefav&amp;fv_id=".$key."\" onclick=\"return confirm('".$gm_lang["confirm_fav_remove"]."');\">".$gm_lang["remove"]."</a>\n";
				print "&nbsp;";
				print "<a class=\"font9\" href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?favid=$key&amp;name=$gm_username&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=400,scrollbars=1,resizable=1'); return false;\">".$gm_lang["edit"]."</a>";
			}
			SwitchGedcom();
		}
	}
}
function PrintBlockAddFavorite($command, $type) {
	global $GM_IMAGE_DIR, $GM_IMAGES, $gm_lang, $GEDCOMID;
	
	?>
		<script language="JavaScript" type="text/javascript">
		<!--
		var pastefield;
		function paste_id(value) {
			pastefield.value=value;
		}
		//-->
		</script>
		<br />
	<?php
	print_help_link("index_add_favorites_help", "qm", "add_favorite");
	print "<b><a href=\"javascript: ".$gm_lang["add_favorite"]." \" onclick=\"expand_layer('add_".$type."_fav'); return false;\"><img id=\"add_ged_fav_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" />&nbsp;".$gm_lang["add_favorite"]."</a></b>";
	print "<br /><div id=\"add_".$type."_fav\" style=\"display: none;\">\n";
	print "<form name=\"addfavform\" method=\"get\" action=\"index.php\">\n";
	print "<input type=\"hidden\" name=\"action\" value=\"addfav\" />\n";
	print "<input type=\"hidden\" name=\"command\" value=\"$command\" />\n";
	print "<input type=\"hidden\" name=\"favtype\" value=\"".$type."\" />\n";
	print "<table border=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td>".$gm_lang["add_fav_enter_id"]." <br />";
	print "<input class=\"pedigree_form\" type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
	PrintFindIndiLink("gid",$GEDCOMID);
	print_findfamily_link("gid");
	print_findsource_link("gid");
	print_findobject_link("gid");
	print_findnote_link("gid");
	print "\n<br />".$gm_lang["add_fav_or_enter_url"];
	print "\n<br />".$gm_lang["url"]."<input type=\"text\" name=\"url\" size=\"40\" value=\"\" />";
	print "\n<br />".$gm_lang["title"]." <input type=\"text\" name=\"favtitle\" size=\"40\" value=\"\" />";
	print "\n</td><td>";
	print "\n".$gm_lang["add_fav_enter_note"];
	print "\n<br /><textarea name=\"favnote\" rows=\"6\" cols=\"40\"></textarea>";
	print "</td></tr></table>\n";
	print "\n<br /><input type=\"submit\" value=\"".$gm_lang["add"]."\" style=\"font-size: 8pt; \" />";
	print "\n</form></div>\n";
}
?>
