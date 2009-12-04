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
			if (in_array($type, array("indi", "sour", "note", "repo"))) $_SESSION[$type][$xref] = $tab_tab;
		}
	break;
	
	case "set_show_changes":
		if (!$gm_user->userCanEdit()) {
			print "";
			exit;
		}
		$_SESSION["show_changes"] = $set_show_changes;
	break;

	case "getnextids":
		if (!$gm_user->userCanEdit()) {
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
		$u =& User::GetInstance($username);
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
		if (empty($pid)) print "";
		else {
			$person =& Person::GetInstance($pid);
			if ($person->isempty) {
				print "<span class=\"error\">".$gm_lang["indi_id_no_exists"]."</span>";
			}
			else if ($person->disp_name) print $person->name.$person->addxref;
			else print "";
		}
		SwitchGedcom();
	break;
	
	case "getpersonnamefact":
		if (!isset($gedid)) $gedid = $GEDCOMID;
		SwitchGedcom($gedid);
		if (empty($pid)) print "";
		else {
			$person =& Person::GetInstance($pid);
			print $person->name;
			PersonFunctions::PrintFirstMajorFact($person);
		}
		SwitchGedcom();
	break;
	
	case "getfamilydescriptor":
		$famid = strtoupper($famid);
		if (empty($famid)) print "";
		else {
			$family =& Family::GetInstance($famid);
			if ($family->isempty) {
				print "<span class=\"error\">".$gm_lang["fam_id_no_exists"]."</span>";
			}
			else if ($family->disp) print $family->descriptor.$family->addxref;
			else print "";
		}
	break;
	
	case "getsourcedescriptor":
		$sid = strtoupper($sid);
		if (empty($sid)) print "";
		else {
			$source =& Source::GetInstance($sid);
			if ($source->isempty) {
				print "<span class=\"error\">".$gm_lang["source_id_no_exists"]."</span>";
			}
			else if ($source->disp) print $source->descriptor.$source->addxref;
			else print "";
		}
	break;
	
	case "getrepodescriptor":
		$rid = strtoupper($rid);
		if (empty($rid)) print "";
		else {
			$repo =& Repository::GetInstance($rid);
			if ($repo->isempty) {
				print "<span class=\"error\">".$gm_lang["repo_id_no_exists"]."</span>";
			}
			else if ($repo->disp) print $repo->title.$repo->addxref;
			else print "";
		}
	break;

	case "getmediadescriptor":
		$rid = strtoupper($mid);
		if (empty($mid)) print "";
		else {
			$media =& MediaItem::GetInstance($mid);
			if ($media->isempty) {
				print "<span class=\"error\">".$gm_lang["media_id_no_exists"]."</span>";
			}
			else if ($media->disp) print $media->title.$media->addxref;
			else print "";
		}
	break;
	
	case "getnotedescriptor":
		$oid = strtoupper($oid);
		if (empty($oid)) print "";
		else {
			$note =& Note::GetInstance($oid);
			// Note is deleted or doesn't exist
			if ($note->isempty) {
				print "<span class=\"error\">".$gm_lang["note_id_no_exists"]."</span>";
			}
			else if ($note->disp) print $note->GetTitle(40, true).$note->addxref;
			else print "";
		}
	break;
			
	case "getchangeddate":
		print GetChangedDate(EditFunctions::CheckInputDate($date));
	break;
	
	case "action_edit":
		$action = ActionController::GetItem($aid);
		$action->EditThisItem();
	break;
	
	// Actions for the ToDo list 
	case "action_delete":
		$action = ActionController::GetItem($aid);
		$action->DeleteThis();
	break;
	
	case "action_update":
		$action = ActionController::GetItem($aid);
		if (isset($actiontext))$action->text = urldecode($actiontext);
		if (isset($repo))$action->repo = $repo;
		if (isset($status)) $action->status = $status;
		$action->pid = $pid;
		$action->gedfile = $GEDCOMID;
		$action->UpdateThis();
		$action->PrintThisItem();
	break;

	case "action_add":
		$action = ActionController::GetNewItem($type);
		$action->AddThisItem();
	break;
	
	case "action_add2":
		$action = ActionController::GetNewItem($type);
		if (isset($actiontext))$action->text = urldecode($actiontext);
		if (isset($repo))$action->repo = $repo;
		if (isset($status)) $action->status = $status;
		$action->pid = $pid;
		$action->gedfile = $GEDCOMID;
		$action->AddThis();
		print "";
	break;
	// End actions for the ToDo list 

	case "getzoomfacts":
		SwitchGedcom($gedcomid);
		$indi =& Person::GetInstance($pid);
		$nonfacts = array("SEX","FAMS","FAMC","NAME","TITL","NOTE","SOUR","SSN","OBJE","HUSB","WIFE","CHIL","ALIA","ADDR","PHON","SUBM","_EMAIL","CHAN","URL","EMAIL","WWW","RESI","RESN");
		$nonfamfacts = array("_UID", "RESN");
		$indi->AddFamilyFacts(false);
		$f2 = 0;
		foreach($indi->facts as $indexval => $factobj) {
			if (!in_array($factobj->fact, $nonfacts) && $factobj->disp){
				if ($f2>0) print "<br />\n";
				$f2++;
				$fft = preg_match("/^1 (\w+)(.*)/m", $factobj->factrec, $ffmatch);
				if ($fft>0) {
					$fact = trim($ffmatch[1]);
					$details = trim($ffmatch[2]);
				}
				if ($factobj->fact != "EVEN" && $factobj->fact != "FACT") {
					print "<span class=\"details_label\">";
					if (defined("GM_FACT_".$factobj->fact)) print constant("GM_FACT_".$factobj->fact);
					else print $factobj->fact;
					print "</span> ";
				}
				else {
					if ($factobj->fact != $factobj->factref) {
						print "<span class=\"details_label\">";
						print $factobj->descr;
						print "</span> ";
					}
				}
				$factobj->PrintFactDate(false, false, true, true, true);
				if (GetSubRecord(2, "2 DATE", $factobj->factrec) == "") {
					// Don't display Y, N and ASSO links
					if ($details!="Y" && $details!="N" && $factobj->fact != "ASSO") print PrintReady($details);
				}
				else print PrintReady($details);
				//-- print spouse name for marriage events
				$ct = preg_match("/_GMFS @(.*)@/", $factobj->factrec, $match);
				if ($ct>0) $famid = $match[1];
				$ct = preg_match("/_GMS @(.*)@/", $factobj->factrec, $match);
				if ($ct>0) {
					$spouse=$match[1];
					if ($spouse!=="") {
						$sp =& Person::GetInstance($spouse);
						print " <a href=\"individual.php?pid=".$sp->xref."&amp;gedid=".$sp->gedcomid."\">";
						print $sp->name;
						print "</a>";
					}
					if ($spouse != "" && !$factobj->owner->view) print " - ";
					if (!$factobj->owner->view) print "<a href=\"family.php?famid=".$famid."&amp;gedid=".$GEDCOMID."\">[".$gm_lang["view_family"]."]</a>\n";
				}
				$factobj->PrintFactPlace(true, true);
				$prted = FactFunctions::PrintAssoRelaRecord($factobj, $pid, true);
			}
		}
	break;
	
	case "send_empty":
		print "";
	break;
}
session_write_close();
?>