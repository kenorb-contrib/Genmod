<?php
/**
 * Handle AJAX RPC's
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
 * @subpackage zwooff
 * @version $Id$
 */
 
require "config.php";

// Switch off debug as that output is also sent!
$olddebug = $DEBUG;
$DEBUG = false;

if (!isset($action)) $action = "";

switch($action) {
	
	case "lastused":
		if (isset($id) && isset($type)) $_SESSION["last_used"][$type] = $id;
		print "";
	break;
		
	case "remembertab":
		if (!isset($xref) || !isset($tab_tab) || !isset($type)) print "";
		else {
			if (empty($tab_tab)) $tab_tab = 0;
			if (in_array($type, array("indi", "sour", "note"))) $_SESSION[$type][$xref] = $tab_tab;
		}
	break;
	
	case "set_show_changes":
		if (!$Users->userCanEdit($gm_username)) {
			print "";
			exit;
		}
		$_SESSION["show_changes"] = $set_show_changes;
	break;

	case "getnextids":
		if (!$Users->userCanEdit($gm_username)) {
			print "";
			exit;
		}
		$types = array("INDI", "FAM", "SOUR", "REPO", "OBJE", "NOTE");
		$desc = array("individual", "family", "source", "repo", "media_object", "note");
		foreach($types as $k=>$type) {
			print $gm_lang["next_free"]." ".$gm_lang[$desc[$k]].": ".GetNewXref($type)."<br />";
		}
	break;
	
	case "checkuser":
		$u = $Users->GetUser($username);
		if (!$u->is_empty) print "<span class=\"error\">".$gm_lang["duplicate_username"]."</span>";
		else print "";
	break;
	
	case "checkemail":
		if (empty($email) || CheckEmailAddress($email)) print "";
		else print "<span class=\"error\">".$gm_lang["invalid_email"]."</span>";
	break;

	case "getpersonname":
		if (!isset($gedid)) $gedid = $GEDCOMID;
		SwitchGedcom($gedid);
		if (!empty($pid) && DisplayDetailsByID($pid, "INDI")) {
			$pid = strtoupper($pid);
			$rec = FindPersonRecord($pid);
			if (!empty($rec)) {
				print GetPersonName($pid, $rec)." (".$pid.")";
			}
			else print "<span class=\"error\">".$gm_lang["indi_id_no_exists"]."</span>";
//			else print "";
		}
		else print "";
		SwitchGedcom();
	break;
	
	case "getpersonnamefact":
		SwitchGedcom($gedid);
		if (DisplayDetailsByID($pid, "INDI")) {
			$pid = strtoupper($pid);
			$rec = FindPersonRecord($pid);
			if (!empty($rec)) {
				print GetPersonName($pid, $rec);
				print_first_major_fact($pid, $rec);
			}
			else print "";
		}
		else print "";
		SwitchGedcom();
	break;
	
	case "getfamilydescriptor":
		$famid = strtoupper($famid);
		if (empty($famid)) print "";
		else {
			$famrec = FindFamilyRecord($famid);
			if (!$famrec && !GetChangeData(true, $famid, true, "", "")) {
				print "<span class=\"error\">".$gm_lang["fam_id_no_exists"]."</span>";
			}
			else print GetFamilyDescriptor($famid, false, $famrec)." (".$famid.")";
		}
	break;
	
	case "getsourcedescriptor":
		$sid = strtoupper($sid);
		if (empty($sid)) print "";
		else {
			$sourcerec = FindSourceRecord($sid);
			if (!$sourcerec && !GetChangeData(true, $sid, true, "", "")) {
				print "<span class=\"error\">".$gm_lang["source_id_no_exists"]."</span>";
			}
			else if (DisplayDetailsByID($sid, "SOUR", 1, true)) print GetSourceDescriptor($sid)." (".$sid.")";
			else print "";
		}
	break;
	
	case "getrepodescriptor":
		$rid = strtoupper($rid);
		if (empty($rid)) print "";
		else {
			$reporec = FindRepoRecord($rid);
			if (!$reporec && !GetChangeData(true, $rid, true, "", "")) {
				print "<span class=\"error\">".$gm_lang["repo_id_no_exists"]."</span>";
			}
			else if (DisplayDetailsByID($rid, "REPO", 1, true)) print GetRepoDescriptor($rid)." (".$rid.")";
			else print "";
		}
	break;

	case "getmediadescriptor":
		$rid = strtoupper($mid);
		if (empty($mid)) print "";
		else {
			$mediarec = FindMediaRecord($mid);
			if (!$mediarec && !GetChangeData(true, $mid, true, "", "")) {
				print "<span class=\"error\">".$gm_lang["media_id_no_exists"]."</span>";
			}
			else if (DisplayDetailsByID($mid, "OBJE", 1, true)) print GetMediaDescriptor($mid)." (".$mid.")";
			else print "";
		}
	break;
	
	case "getnotedescriptor":
		$oid = strtoupper($oid);
		if (empty($oid)) print "";
		else {
			require_once("includes/controllers/note_ctrl.php");
			// Note is deleted or doesn't exist
			if ($note_controller->isempty) {
				print "<span class=\"error\">".$gm_lang["note_id_no_exists"]."</span>";
			}
			else if ($note_controller->note->canDisplayDetails()) print $note_controller->note->GetTitle(40, true)." (".$oid.")";
			else print "";
		}
	break;
			
	case "getchangeddate":
		require('includes/functions_edit.php');
		print GetChangedDate(CheckInputDate($date));
	break;
	
	case "action_edit":
		if (is_object($Actions)) {
			$action = $Actions->GetItem($aid);
			$action->EditThisItem();
		}
		else print "";
	break;
	
	// Actions for the ToDo list 
	case "action_delete":
		if (is_object($Actions)) {
			$action = $Actions->GetItem($aid);
			$action->DeleteThis();
		}
		else print "";
	break;
	
	case "action_update":
		if (is_object($Actions)) {
			$action = $Actions->GetItem($aid);
			if (isset($actiontext))$action->text = urldecode($actiontext);
			if (isset($repo))$action->repo = $repo;
			if (isset($status)) $action->status = $status;
			$action->pid = $pid;
			$action->gedfile = $GEDCOMID;
			$action->UpdateThis();
			$action->PrintThisItem();
		}
		else print "";
	break;

	case "action_add":
		if (is_object($Actions)) {
			$action = new ActionItem();
			$action->AddThisItem();
		}
		else print "";
	break;
	
	case "action_add2":
		if (is_object($Actions)) {
			$action = new ActionItem();
			if (isset($actiontext))$action->text = urldecode($actiontext);
			if (isset($repo))$action->repo = $repo;
			if (isset($status)) $action->status = $status;
			$action->pid = $pid;
			$action->gedfile = $GEDCOMID;
			$action->AddThis();
			print "";
		}
		else print "";
	break;
	// End actions for the ToDo list 

	case "getzoomfacts":
		$indirec=FindPersonRecord($pid);
		if ($canshow && GetChangeData(true, $pid, true)) {
			$rec = GetChangeData(false, $pid, true, "gedlines", "");
			if (isset($rec[$GEDCOM][$pid])) $indirec = $rec[$GEDCOM][$pid];
			$newindi = true;
		}
		$skipfacts = array("SEX","FAMS","FAMC","NAME","TITL","NOTE","SOUR","SSN","OBJE","HUSB","WIFE","CHIL","ALIA","ADDR","PHON","SUBM","_EMAIL","CHAN","URL","EMAIL","WWW","RESI");
		$subfacts = GetAllSubrecords($indirec, implode(",", $skipfacts));
		$f2 = 0;
		foreach($subfacts as $indexval => $factrec) {
			if (!FactViewRestricted($pid, $factrec)){
				if ($f2>0) print "<br />\n";
				$f2++;
				// NOTE: Handle ASSO record
				if (strstr($factrec, "1 ASSO")) {
					print_asso_rela_record($pid, $factrec, false);
					continue;
				}
				$fft = preg_match("/^1 (\w+)(.*)/m", $factrec, $ffmatch);
				if ($fft>0) {
					$fact = trim($ffmatch[1]);
					$details = trim($ffmatch[2]);
				}
				if (($fact!="EVEN")&&($fact!="FACT")) {
					print "<span class=\"details_label\">";
					if (isset($factarray[$fact])) print $factarray[$fact];
					else print $fact;
					print "</span> ";
				}
				else {
					$tct = preg_match("/2 TYPE (.*)/", $factrec, $match);
					if ($tct>0) {
						$facttype = trim($match[1]);
						print "<span class=\"details_label\">";
						if (isset($factarray[$facttype])) print PrintReady($factarray[$facttype]);
						else print $facttype;
						print "</span> ";
					}
				}
				print_fact_date($factrec, false, false, $fact, $pid, $indirec);
				if (GetSubRecord(2, "2 DATE", $factrec)=="") {
					if ($details!="Y" && $details!="N") print PrintReady($details);
				}
				else print PrintReady($details);
				//-- print spouse name for marriage events
				$ct = preg_match("/_GMFS @(.*)@/", $factrec, $match);
				if ($ct>0) $famid = $match[1];
				$ct = preg_match("/_GMS @(.*)@/", $factrec, $match);
				if ($ct>0) {
					$spouse=$match[1];
					if ($spouse!=="") {
						print " <a href=\"individual.php?pid=$spouse&amp;ged=$GEDCOM\">";
						if (showLivingNameById($spouse)) print PrintReady(GetPersonName($spouse));
						else print $gm_lang["private"];
						print "</a>";
					}
					if (($view!="preview") && ($spouse!=="")) print " - ";
					if ($view!="preview") print "<a href=\"family.php?famid=$famid\">[".$gm_lang["view_family"]."]</a>\n";
				}
				print_fact_place($factrec, true, true);
			}
		}
	break;
	
	case "send_empty":
		print "";
	break;
}
session_write_close();
$DEBUG = $olddebug;
?>