<?php
/**
 * Merge Two Gedcom Records
 *
 * This page will allow you to merge 2 gedcom records
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
 * @package Genmod
 * @subpackage Edit
 * @version $Id: edit_merge.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
/**
 * Inclusion of the configuration file
*/
require("config.php");
$trace = false;

if (empty($action)) $action="choose";
if (empty($gid1)) $gid1="";
else $gid1 = strtoupper($gid1);
if (empty($gid2)) $gid2="";
else $gid2 = strtoupper($gid2);
if (empty($mergeged)) $mergeged = GedcomConfig::$GEDCOMID;

// keep2 might be empty, if so set it here 
if (isset($_POST["keep2"])) $keep2 = $_POST["keep2"];
else $keep2 = array();
/*
if (isset($_POST["keep1"])) $keep1 = $_POST["keep1"];
else $keep1 = array();

if (isset($_POST["skip1"])) $skip1 = $_POST["skip1"];
else $skip1 = array();
if (isset($_POST["skip2"])) $skip2 = $_POST["skip2"];
else $skip2 = array();
*/
$errorstring = "";
$error = 0;

PrintHeader(GM_LANG_merge_records);

// We can auto accept the merge
$can_auto_accept = true;

//-- make sure they have accept access privileges
if (!$gm_user->userCanAccept()) {
	print "<span class=\"Error\">".GM_LANG_access_denied."</span>";
	PrintFooter();
	exit;
}
?>	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php 
		AdminFunctions::AdminLink("admin.php", GM_LANG_admin);
		if ($action != "choose") {
			AdminFunctions::AdminLink("edit_merge.php", GM_LANG_merge_records);
		}
		 ?>
	</div>
	
	<!-- Setup the right box -->
	<div id="AdminColumnRight">
	</div>
<?php
if ($action != "choose") {
	if ($gid1 == $gid2) {
		$error = 1;
		$action = "choose";
	}
	else {
		$oldged = GedcomConfig::$GEDCOMID;
		SwitchGedcom($mergeged);
		$object1 = ConstructObject($gid1, "", $mergeged); 
		$object2 = ConstructObject($gid2, "", $mergeged); 
		if (!is_object($object1) || !is_object($object2)) {
			$error = 3;
			$action="choose";
		}
		else if ($object1->ischanged || $object2->ischanged) {
			$error = 2;
			$action = "choose";
		}
		else if ($object1->type != $object2->type) {
				$error = 4;
				$action = "choose";
		}
		if ($action != "choose") {
			$orggedrec1 = $object1->gedrec;
			$gedrec1 = $object1->gedrec;
			$gedrec2 = $object2->gedrec;
			// if it's a note, we move the text in the level 0 part to a separate temporary fact (NOTETEXT)
			if ($object1->type == "NOTE") {
				$notetext1 = "";
				$cn = preg_match("/@ NOTE (.*)/", $gedrec1, $match);
				if ($cn) $notetext1 = trim($match[1]);
				$notetext1 .= GetCont(1, $gedrec1);
				$gedrec1 = trim($gedrec1)."\r\n1 NOTETEXT ".$notetext1;
				$notetext2 = "";
				$cn = preg_match("/@ NOTE (.*)/", $gedrec2, $match);
				if ($cn) $notetext2 = trim($match[1]);
				$notetext2 .= GetCont(1, $gedrec2);
				$gedrec2 = trim($gedrec2)."\r\n1 NOTETEXT ".$notetext2;
			}
			$facts1 = array();
			$facts2 = array();
			$prev_tags = array();
			$ct = preg_match_all("/\n1 (\w+)(.*)/", $gedrec1, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				$fact = trim($match[$i][1]);
				if (isset($prev_tags[$fact])) $prev_tags[$fact]++;
				else $prev_tags[$fact] = 1;
				$subrec = GetSubRecord(1, "1 $fact", $gedrec1, $prev_tags[$fact]);
				if ($object1->type != "NOTE" || ($fact != "CONC" && $fact != "CONT")) $facts1[] = array("fact"=>$fact, "subrec"=>trim($subrec));
			}
			$prev_tags = array();
			$ct = preg_match_all("/\n1 (\w+)(.*)/", $gedrec2, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				$fact = trim($match[$i][1]);
				if (isset($prev_tags[$fact])) $prev_tags[$fact]++;
				else $prev_tags[$fact] = 1;
				$subrec = GetSubRecord(1, "1 $fact", $gedrec2, $prev_tags[$fact]);
				if ($object2->type != "NOTE" || ($fact != "CONC" && $fact != "CONT")) $facts2[] = array("fact"=>$fact, "subrec"=>trim($subrec));
			}
			if ($action == "merge") {
				// before we do anything, we check for double selecting unique facts
				$mtype = ($object1->type == "OBJE" ? "MEDIA" : $object1->type);
				$type = trim($mtype)."_FACTS_UNIQUE";
				$unique = explode(",", GEDCOMCONFIG::$$type);
				$factcount = array();
				for($i=0; ($i<count($facts1) || $i<count($facts2)); $i++) {
					if (isset($facts1[$i])) {
						if (in_array($i, $keep1)) {
							if (!isset($factcount[$facts1[$i]["fact"]])) $factcount[$facts1[$i]["fact"]] = 1;
							else $factcount[$facts1[$i]["fact"]]++;
						}
					}
					if (isset($facts2[$i])) {
						if (in_array($i, $keep2)) {
							if (!isset($factcount[$facts2[$i]["fact"]])) $factcount[$facts2[$i]["fact"]] = 1;
							else $factcount[$facts2[$i]["fact"]]++;
						}
					}
				}
				$errfacts = array();
				foreach ($factcount as $fact=>$count) {
					if (in_array($fact, $unique) && $count > 1) $errfacts[] = $fact;
				}
				if (count($errfacts) > 0) {
					$action = "select";
					$errorstring = implode(", ", $errfacts);
				}
			}
			if ($action == "merge") {
				if ($trace) {
					print "gedrec1: ".$orggedrec1;
					print "<br /><br />gedrec2: ".$gedrec2;
					print "<br /><br />facts1: ";
					print_r($facts1);
					print "<br /><br />keep1: ";
					print_r($keep1);
					print "<br /><br />skip1: ";
					print_r($skip1);
					print "<br /><br />facts2: ";
					print_r($facts2);
					print "<br /><br />keep2: ";
					print_r($keep2);
					print "<br /><br />skip2: ";
					print_r($skip2);
					print "<br /><br />errfacts: ";
					print_r($errfacts);
				}
				$change_id = EditFunctions::GetNewXref("CHANGE");
				$change_type = "MERGE";
				print "<div id=\"AdminColumnMiddle\">";
				print "<div class=\"NavBlockHeader AdminNavBlockHeader\"><span class=\"AdminNavBlockTitle\">".GM_LANG_merge_step3."</span><br /><br />\n";
				print GM_LANG_merge_perform."</div>\n";
				// Delete the old record2
				EditFunctions::DeleteGedrec($gid2, $change_id, $change_type, $object1->type);

				// First add facts 2-> 1
				$textfrom1 = "";
				$textfrom2 = "";
				for($i=0; ($i<count($facts2)); $i++) {
					if (in_array($i, $keep2)) {
						// If notetext is kept, we must reconstruct the first gedrec.
						// If 1 is selected and 2 is not, nothing will change in the notetext.
						// If 1 is selected and 2 is selected, 1 AND 2 are added as notetext.
						// If 1 is not selected and 2 is selected, 2 replaces 1 as notetext.
						// I neither is selected, the text for 1 remains as is.
						// This ignores the deselection of 1 AND 2. If 2 is not kept, 1 will not get deleted anyway.
						if ($facts2[$i]["fact"] == "NOTETEXT") {
							// If both notetexts are selected, merge them.
							for ($j=0; $j<count($facts1); $j++) {
								if (in_array($j, $keep1)) {
									if ($facts1[$j]["fact"] == "NOTETEXT") {
										$textfrom1 = substr($facts1[$j]["subrec"], 11)."\r\n";
										break;
									}
								}
							}
							// print "textfrom1: ".$textfrom1."<br />";
							$textfrom2 = substr($facts2[$i]["subrec"], 11);
							// print "textfrom2: ".$textfrom2."<br />";
							if (!empty($textfrom1) || !empty($textfrom2)) {
								$newrec1 = "0 @$gid1@ NOTE";
								$newrec1 = MakeCont($newrec1, $textfrom1.$textfrom2);
								// print "newrec1: ".$newrec1;
								$subs1 = GetAllSubrecords($gedrec1, "CONC,CONT", false, false, false);
								$newrec1 .= implode("\r\n", $subs1);
								EditFunctions::ReplaceGedrec($gid1, $gedrec1, $newrec1, "NOTE", $change_id, $change_type, $mergeged, "NOTE");
							}
						}
						else EditFunctions::ReplaceGedrec($gid1, "", $facts2[$i]["subrec"], $facts2[$i]["fact"], $change_id, $change_type, $mergeged, $object1->type);
					}
				}
				
				// Then check for indi-fam relations in ged2; these must be deleted from the designated records, or changed to the new ID.
				if ($mtype == "INDI" || $mtype == "FAM") {
					for($i=0; $i<count($facts2); $i++) {
						if ($facts2[$i]["fact"] == "FAMC" || $facts2[$i]["fact"] == "FAMS") {
							// This is a indi->fam link, so we must remove or change the fam->indi link from the fam record
							// Get the famid
							$ct = preg_match("/1 FAM. @(.+)@/", $facts2[$i]["subrec"], $match);
							$famid = $match[1];
							// Get the role
							$fam =& Family::GetInstance($famid, "", $mergeged);
							$famrec = $fam->gedrec;
							$ct = preg_match("/1 (HUSB|WIFE|CHIL) @$gid2@/", $famrec, $match);
							$role = $match[1];
							$subrec = GetSubrecord(1, "1 $role @$gid2@", $famrec);
							if (!in_array($i, $keep2)) {
								if ($trace) print "1. fam: ".$famid." out: ".$subrec."<br />";
								EditFunctions::ReplaceGedrec($famid, $subrec, "", $role, $change_id, $change_type, $mergeged, "FAM");
							}
							else {
								$subrecnew = preg_replace("/@$gid2@/", "@$gid1@", $subrec);
								if ($trace) print "2. fam: ".$famid." out: ".$subrec." in: ".$subrecnew."<br />";
								EditFunctions::ReplaceGedrec($famid, $subrec, $subrecnew, $role, $change_id, $change_type, $mergeged, "FAM");
							}
						}
						if ($facts2[$i]["fact"] == "HUSB" || $facts2[$i]["fact"] == "WIFE" || $facts2[$i]["fact"] == "CHIL") {
							// This is a fam->indi link, so we must remove or change the indi->fam link from the indi record
							// Get the pid
							$ct = preg_match("/1 (HUSB|WIFE|CHIL) @(.+)@/", $facts2[$i]["subrec"], $match);
							$pid = $match[2];
							// Get the role
							if ($match[1] == "CHIL") $role = "FAMC";
							else $role = "FAMS";
							$person =& Person::GetInstance($pid, "", $mergeged);
							$pidrec = $person->gedrec;
							$subrec = GetSubrecord(1, "1 $role @$gid2@", $pidrec);
							if (!in_array($i, $keep2)) {
								if ($trace) print "3. indi: ".$pid." out: ".$subrec."<br />";
								EditFunctions::ReplaceGedrec($pid, $subrec, "", $role, $change_id, $change_type, $mergeged, "INDI");
							}
							else {
								$subrecnew = preg_replace("/@$gid2@/", "@$gid1@", $subrec);
								if ($trace) print "4. indi: ".$pid." out: ".$subrec." in: ".$subrecnew."<br />";
								EditFunctions::ReplaceGedrec($pid, $subrec, $subrecnew, $role, $change_id, $change_type, $mergeged, "INDI");
							}
						}
					}
					for($i=0; $i<count($facts1); $i++) {
						if ($facts1[$i]["fact"] == "FAMC" || $facts1[$i]["fact"] == "FAMS") {
							// This is a indi->fam link, so we must remove or change the fam->indi link from the fam record
							// Get the famid
							$ct = preg_match("/1 FAM. @(.+)@/", $facts1[$i]["subrec"], $match);
							$famid = $match[1];
							// Get the role
							$fam =& Family::GetInstance($famid, "", $mergeged);
							$famrec = $fam->gedrec;
							$ct = preg_match("/1 (HUSB|WIFE|CHIL) @$gid1@/", $famrec, $match);
							$role = $match[1];
							$subrec = GetSubrecord(1, "1 $role @$gid1@", $famrec);
							if (!in_array($i, $keep1)) {
								if ($trace) print "5. fam: ".$famid." out: ".$subrec."<br />";
								EditFunctions::ReplaceGedrec($famid, $subrec, "", $role, $change_id, $change_type, $mergeged, "FAM");
							}
							// Don't move anything here, only for 2 to 1
						}
						if ($facts1[$i]["fact"] == "HUSB" || $facts1[$i]["fact"] == "WIFE" || $facts1[$i]["fact"] == "CHIL") {
							// This is a fam->indi link, so we must remove or change the indi->fam link from the indi record
							// Get the pid
							$ct = preg_match("/1 (HUSB|WIFE|CHIL) @(.+)@/", $facts1[$i]["subrec"], $match);
							$pid = $match[2];
							// Get the role
							if ($match[1] == "CHIL") $role = "FAMC";
							else $role = "FAMS";
							$person =& Person::GetInstance($pid, "", $mergeged);
							$pidrec = $person->gedrec;
							$subrec = GetSubrecord(1, "1 $role @$gid1@", $pidrec);
							if (!in_array($i, $keep1)) {
								if ($trace) print "6. indi: ".$pid." out: ".$subrec."<br />";
								EditFunctions::ReplaceGedrec($pid, $subrec, "", $role, $change_id, $change_type, $mergeged, "INDI");
							}
							// Don't move anything here, only for 2 to 1
						}
					}
				}
				
				// Now remove the subrecs that are not kept in ged1 but keep the subrecs that were equal in both ged1 and 2				
				for($i=0; ($i<count($facts1)); $i++) {
					if (!in_array($i, $keep1) && $facts1[$i]["fact"] != "CHAN" && $facts1[$i]["fact"] != "NOTETEXT") {
						if ($trace) print "7. Remove ".$gid1." fact ".$facts1[$i]["subrec"]."<br />";
						EditFunctions::ReplaceGedrec($gid1, $facts1[$i]["subrec"], "", $facts1[$i]["fact"], $change_id, $change_type, $mergeged, $object1->type);
					}
				}

				// Now update all links in other records from ged2 to ged1
				EditFunctions::ReplaceLinks($gid2, $gid1, $mtype, $change_id, $change_type, $mergeged);
				print "<div class=\"NavBlockLabel\">";
				if (isset($change_id) && $can_auto_accept &&  $gm_user->userAutoAccept()) {
					ChangeFunctions::AcceptChange($change_id, GedcomConfig::$GEDCOMID);
					print GM_LANG_merge_success_auto;
				}
				else print GM_LANG_merge_success;
				
				print "</div>\n";
				print "<div class=\"NavBlockFooter\"><a href=\"edit_merge.php?action=choose\">".GM_LANG_merge_more."</a></div>\n";
				print "</div>"; //Close middle section
			}
			if ($action == "select") {
				print "<div id=\"AdminColumnMiddle\">";
				print "<form method=\"post\" action=\"edit_merge.php\">\n";
				print "<input type=\"hidden\" name=\"gid1\" value=\"$gid1\" />\n";
				print "<input type=\"hidden\" name=\"gid2\" value=\"$gid2\" />\n";
				print "<input type=\"hidden\" name=\"mergeged\" value=\"$mergeged\" />\n";
				print "<input type=\"hidden\" name=\"action\" value=\"merge\" />\n";
				$equal_count=0;
				$skip1 = array();
				$skip2 = array();
				print "<table class=\"NavBlockTable AdminNavBlockTable\">\n";
				print "<tr><td class=\"NavBlockHeader AdminNavBlockHeader\" colspan=\"2\"><span class=\"AdminNavBlockTitle\">".GM_LANG_merge_step2."</span><br /><br />";
				if (!empty($errorstring)) print "<span class=\"Error\">".GM_LANG_merge_notunique."&nbsp;".$errorstring."</span><br />";
				print "</td></tr>";
				print "<tr><td class=\"NavBlockRowSpacer\" colspan=\"2\">&nbsp;</td></tr>";
				print "<tr><td class=\"NavBlockColumnHeader\" colspan=\"2\">".GM_LANG_merge_facts_same."</td></tr>";
				foreach($facts1 as $i=>$fact1) {
					foreach($facts2 as $j=>$fact2) {
						if (Str2Upper($fact1["subrec"])==Str2Upper($fact2["subrec"])) {
							if ($trace) print "skip1 waarde ".$i." skip2 waarde ".$j." subrec ".$fact2["subrec"]."<br />";
							$skip1[] = $i;
							$skip2[] = $j;
							$equal_count++;
							print "<tr><td class=\"NavBlockLabel\">";
							if (defined("GM_FACT_".$fact1["fact"])) print constant("GM_FACT_".$fact1["fact"]);
							else print $fact1["fact"];
							print "<input type=\"hidden\" name=\"keep1[]\" value=\"$i\" /></td>\n<td class=\"NavBlockField\">".nl2br($fact1["subrec"])."</td></tr>\n";
						}
					}
				}
				if ($equal_count==0) {
					print "<tr><td class=\"NavBlockLabel\" colspan=\"2\">".GM_LANG_no_matches_found."</td></tr>\n";
				}
				print "<tr><td class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
				print "<tr><td class=\"NavBlockColumnHeader\" colspan=\"2\">".GM_LANG_unmatching_facts."</td></tr>";
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_record." $gid1</td><td class=\"NavBlockLabel\">".GM_LANG_record." $gid2</td></tr>\n";
				print "<tr><td class=\"NavBlockField MergeFactsBlock\">\n";
				print "<table class=\"MergeFactsTable\">\n";
				foreach($facts1 as $i=>$fact1) {
					if (($fact1["fact"]!="CHAN")&&(!in_array($i, $skip1))) {
						print "<tr><td><input class=\"MergeFactSelect\" type=\"checkbox\" name=\"keep1[]\" value=\"$i\" checked=\"checked\" /></td>";
						print "<td class=\"MergeFactData\">".nl2br($fact1["subrec"])."</td></tr>\n";
					}
				}
				print "</table>\n";
				print "</td><td class=\"NavBlockField MergeFactsBlock\">\n";
				print "<table class=\"MergeFactsTable\">\n";
				foreach($facts2 as $j=>$fact2) {
					if (($fact2["fact"]!="CHAN")&&(!in_array($j, $skip2))) {
						print "<tr><td><input class=\"MergeFactSelect\" type=\"checkbox\" name=\"keep2[]\" value=\"$j\" checked=\"checked\" /></td>";
						print "<td class=\"MergeFactData\">".nl2br($fact2["subrec"])."</td></tr>\n";
					}
				}
				print "</table>\n";
				print "</td></tr>\n";
				print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\"  value=\"".GM_LANG_merge_records."\" /></td></tr>\n";
				print "</table>\n";
				print "</form></div>\n";
			}
		}
	}
	SwitchGedcom($oldged);
}
?>
<script language="JavaScript" type="text/javascript">
<!--
	function reload() {
	}
//-->
</script>
<?php

if ($action=="choose") {
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
	var pasteto;
	function iopen_find(textbox, gedselect) {
		pasteto = textbox;
		ged = gedselect.options[gedselect.selectedIndex].value;
		findwin = window.open('find.php?type=indi&gedid='+ged, '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	}
	function fopen_find(textbox, gedselect) {
		pasteto = textbox;
		ged = gedselect.options[gedselect.selectedIndex].value;
		findwin = window.open('find.php?type=fam&gedid='+ged, '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	}
	function sopen_find(textbox, gedselect) {
		pasteto = textbox;
		ged = gedselect.options[gedselect.selectedIndex].value;
		findwin = window.open('find.php?type=source&gedid='+ged, '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	}
	function nopen_find(textbox, gedselect) {
		pasteto = textbox;
		ged = gedselect.options[gedselect.selectedIndex].value;
		findwin = window.open('find.php?type=note&gedid='+ged, '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	}
	function paste_id(value) {
		pasteto.value=value;
	}
	//-->
	</script>
	
	<?php
	print "<div id=\"AdminColumnMiddle\">";
		print "<form method=\"post\" name=\"merge\" action=\"edit_merge.php\">";
		print "<input type=\"hidden\" name=\"action\" value=\"select\" />";
		print "<table class=\"NavBlockTable AdminNavBlockTable\">";
			print "<tr><td class=\"NavBlockHeader AdminNavBlockHeader\" colspan=\"2\"><span class=\"AdminNavBlockTitle\">".GM_LANG_merge_step1."</span><br /><br />";
			if ($error == "1") print "<span class=\"Error\">".GM_LANG_same_ids."</span><br />";
			if ($error == "2") print "<span class=\"Error\">".GM_LANG_merge_haschanges."</span><br />";
			if ($error == "3") print "<span class=\"Error\">".GM_LANG_unable_to_find_record."</span><br />";
			if ($error == "4") print "<span class=\"Error\">".GM_LANG_merge_same."</span><br />";
			print GM_LANG_select_gedcom_records."</td></tr>";
			print "<tr><td class=\"NavBlockLabel\">".GM_LANG_choose_gedcom."<br /></td>";
			print "<td class=\"NavBlockField\"><select name=\"mergeged\">\n";
			if (!isset($mergeged) || empty($mergeged)) $mergeged = GedcomConfig::$GEDCOMID;
			foreach($GEDCOMS as $gedc=>$gedarray) {
				$gedid = $gedarray["id"];
				if ($gm_user->userGedcomAdmin($gedc)) {
					print "<option value=\"".$gedid."\"";
					if ($mergeged == $gedid) print " selected=\"selected\"";
					print ">".$gedarray["title"]."</option>\n";
				}
			}
			print "</select>\n";
			print "</td></tr>";
			
			print "<tr><td class=\"NavBlockLabel\">".GM_LANG_merge_to."</td>";
			print "<td class=\"NavBlockField\"><input type=\"text\" name=\"gid1\" value=\"".$gid1."\" size=\"10\" tabindex=\"1\" /> ";
			print "<a href=\"javascript:iopen_find(document.merge.gid1, document.merge.mergeged);\"> ".GM_LANG_find_individual."</a> |";
			print " <a href=\"javascript:fopen_find(document.merge.gid1, document.merge.mergeged);\"> ".GM_LANG_find_familyid."</a> |";
			print " <a href=\"javascript:sopen_find(document.merge.gid1, document.merge.mergeged);\"> ".GM_LANG_find_sourceid."</a> |";
			print " <a href=\"javascript:nopen_find(document.merge.gid1, document.merge.mergeged);\"> ".GM_LANG_find_noteid."</a>";
			PrintHelpLink("rootid_help", "qm");
			print "</td></tr>";

			print "<tr><td class=\"NavBlockLabel\">".GM_LANG_merge_from."<br /></td>";
			print "<td class=\"NavBlockField\"><input type=\"text\" name=\"gid2\" value=\"".$gid2."\" size=\"10\" tabindex=\"2\" /> ";
			print "<a href=\"javascript:iopen_find(document.merge.gid2, document.merge.mergeged);\"> ".GM_LANG_find_individual."</a> |";
			print " <a href=\"javascript:fopen_find(document.merge.gid2, document.merge.mergeged);\"> ".GM_LANG_find_familyid."</a> |";
			print " <a href=\"javascript:sopen_find(document.merge.gid2, document.merge.mergeged);\"> ".GM_LANG_find_sourceid."</a> |";
			print " <a href=\"javascript:nopen_find(document.merge.gid2, document.merge.mergeged);\"> ".GM_LANG_find_noteid."</a>";
			PrintHelpLink("rootid_help", "qm");
			print "</td></tr>";

		print "<tr><td colspan=\"2\" class=\"NavBlockFooter\"><input type=\"submit\"  value=\"".GM_LANG_merge_records."\" /></td></tr>\n";
		print "</table>";
		print "</form>\n";
	print "</div>";
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
		merge.gid1.focus();
	//-->
	</script>
	<?php
}

PrintFooter();
?>