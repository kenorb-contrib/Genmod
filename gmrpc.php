<?php
/**
 * Handle AJAX RPC's
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
 * @subpackage zwooff
 * @version $Id: gmrpc.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
require "config.php";

if (!isset($action)) $action = "";

switch($action) {
	
	case "check_captcha":
		if (isset($return) && $return = "bool") {
			if (isset($captcha) && !empty($captcha) && $_SESSION["phpcaptcha"] == $captcha) print "1";
			else print "0";
		}
		else {
			if (isset($captcha) && !empty($captcha) && $_SESSION["phpcaptcha"] == $captcha) print "";
			else print "<span class=\"Error\">".GM_LANG_enter_captcha."</span>";
		}
	break;
	
	case "lastused":
		if (isset($id) && isset($type)) $_SESSION["last_used"][$type] = $id;
		print "";
	break;

	case "clear_clipboard":
		unset($_SESSION["clipboard"]);
		print "";
	break;
			
	case "remembertab":
		if (!isset($xref) || !isset($tab_tab) || !isset($type)) print "";
		else {
			if (empty($tab_tab)) $tab_tab = 0;
			if (in_array($type, array("indi", "sour", "note", "repo"))) $_SESSION["last_tab"][$type][$xref] = $tab_tab;
		}
	break;

	case "set_show_changes":
		if (!$gm_user->userCanEdit()) {
			print "";
			exit;
		}
		$_SESSION["show_changes"] = ($set_show_changes == 1 ? true : false);
	break;

	case "getnextids":
		if (!$gm_user->userCanEdit()) {
			print "";
			exit;
		}
		$types = array("INDI", "FAM", "SOUR", "REPO", "OBJE", "NOTE");
		$desc = array("individual", "family", "source", "repo", "media_object", "note");
		foreach($types as $k=>$type) {
			print GM_LANG_next_free." ".constant("GM_LANG_".$desc[$k]).": ".EditFunctions::GetNewXref($type)."<br />";
		}
	break;

	case "checkuser":
		if(isset($username)) {
			$u =& User::GetInstance($username);
			if (!$u->is_empty) print "<span class=\"Error\">".GM_LANG_duplicate_username."</span>";
		}
		else print "";
	break;

	case "checkemail":
		if (empty($email) || CheckEmailAddress($email, false)) print "";
		else print "<span class=\"Error\">".GM_LANG_invalid_email."</span>";
	break;

	case "getpersonname":
		if (!isset($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		SwitchGedcom($gedid);
		if (empty($pid)) print "";
		else {
			$person =& Person::GetInstance($pid);
			if ($person->isempty) {
				print "<span class=\"Error\">".GM_LANG_indi_id_no_exists."</span>";
			}
			else if ($person->disp_name) print $person->name.$person->addxref;
			else print "";
		}
		SwitchGedcom();
	break;

	case "getpersonnamefact":
		if (!isset($gedid)) $gedid = GedcomConfig::$GEDCOMID;
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
				print "<span class=\"Error\">".GM_LANG_fam_id_no_exists."</span>";
			}
			else if ($family->disp) print $family->name.$family->addxref;
			else print "";
		}
	break;

	case "getsourcedescriptor":
		$sid = strtoupper($sid);
		if (empty($sid)) print "";
		else {
			$source =& Source::GetInstance($sid);
			if ($source->isempty) {
				print "<span class=\"Error\">".GM_LANG_source_id_no_exists."</span>";
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
				print "<span class=\"Error\">".GM_LANG_repo_id_no_exists."</span>";
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
				print "<span class=\"Error\">".GM_LANG_media_id_no_exists."</span>";
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
				print "<span class=\"Error\">".GM_LANG_note_id_no_exists."</span>";
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
		$action->gedcomid = GedcomConfig::$GEDCOMID;
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
		$action->gedcomid = GedcomConfig::$GEDCOMID;
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
					print "<span class=\"FactDetailLabel\">";
					if (defined("GM_FACT_".$factobj->fact)) print constant("GM_FACT_".$factobj->fact);
					else print $factobj->fact;
					print "</span> ";
				}
				else {
					if ($factobj->fact != $factobj->factref) {
						print "<span class=\"FactDetailLabel\">";
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
					if (!$factobj->owner->view) print "<a href=\"family.php?famid=".$famid."&amp;gedid=".GedcomConfig::$GEDCOMID."\">[".GM_LANG_view_family."]</a>\n";
				}
				$factobj->PrintFactPlace(true, true);
				$prted = FactFunctions::PrintAssoRelaRecord($factobj, $pid, true);
			}
		}
	break;

	case "extsearchformprint":
		$es_controller = new ExternalSearchController($pid, $gedcomid);
		$es_controller->PrintSearchForm(isset($formno) ? $formno : 0);
	break;

	case "extsearchservice":
		$es_controller = new ExternalSearchController($pid, $gedcomid);
		// See what params we must have received
		$params = $es_controller->GetParams(isset($formno) ? $formno : 0);
		$searchparms = array();
		foreach ($params as $inputname => $formname) {
			$searchparms[$inputname] = (isset($$inputname) ? $$inputname : "");
		}
		print $es_controller->PrintServiceResults($formno, $searchparms);
		break;
		
	case "extsearchjsonservice":
		$es_controller = new ExternalSearchController($pid, $gedcomid);
		// See what params we must have received
		$formno = (isset($formno) ? $formno : 0);
		$params = $es_controller->GetParams($formno);
		$searchparms = array();
		foreach ($params as $inputname => $formname) {
			$searchparms[$inputname] = (isset($$inputname) ? $$inputname : "");
		}
		print $es_controller->PrintJSONServiceResults($formno, $searchparms);
		break;
	
	case "getpinyin":
		print (isset($chinese) ? GM_LANG_PinYin_translation." ".NameFunctions::GetPinYin(urldecode($chinese)) : "");
		break;

	case "send_empty":
		print "";
	break;
	
	case "loadblockonthisday":
		$OutputDone = false;
		$PrivateFacts = false;
		$lastgid="";

		$found_facts = BlockFunctions::GetCachedEvents($blockaction, 1, $filter, $onlyBDM, $skipfacts);
		
		// Cache the selected indi's and fams in the indilist and famlist
		$selindi = array();
		$selfam = array();
		foreach($found_facts as $key=>$factarr) {
			if ($factarr[2] == "INDI") $selindi[] = $factarr[0];
			if ($factarr[2] == "FAM") $selfam[] = $factarr[0];
		}
		$selindi = implode("[".GedcomConfig::$GEDCOMID."]','", $selindi);
		$selindi .= "[".GedcomConfig::$GEDCOMID."]'";
		$selindi = "'".$selindi;
		ListFunctions::GetIndiList("no", $selindi);
		$selfam = implode("[".GedcomConfig::$GEDCOMID."]','", $selfam);
		$selfam .= "[".GedcomConfig::$GEDCOMID."]'";
		$selfam = "'".$selfam;
		ListFunctions::GetFamList("no", $selfam);
		
		foreach($found_facts as $key=>$factarr) {
			$datestamp = $factarr[3];
			if ($factarr[2]=="INDI") {
				$person =& Person::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
				$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if ($person->disp && $fact->disp) {
					$text = FactFunctions::GetCalendarFact($fact, $blockaction, $filter, "all", GetCurrentYear(), GetCurrentMonth(), GetCurrentDay());
					if ($text != "filter") {
						if ($lastgid != $gid) {
							//if ($lastgid != "") print "<br />";
							print "<div class=\"TodaysEventsLink\">";
								print "<a href=\"individual.php?pid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><span class=\"TodaysEventsName\">".PrintReady($person->revname.($person->revaddname == "" ? "" : " (".$person->revaddname.")"));
								print "</span><img id=\"box-".$gid."-".$key."-sex\" src=\"".GM_IMAGE_DIR."/";
								if ($factarr[5] == "M") print $GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male;
								else if ($factarr[5] == "F") print $GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female;
								else print $GM_IMAGES["sexn"]["small"]."\" title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown;
								print "\" class=\"GenderImage\" />";
								print "<span class=\"ListItemXref\">".$person->addxref."</span>";
								print "</a>";
							print "</div>\n";
							$lastgid = $gid;
						}
						print "<div class=\"TodaysEventsFact\">";
						print $text;
						print "</div>";
						$OutputDone = true;
					}
				}
				else $PrivateFacts = true;
			}
	
			if ($factarr[2]=="FAM") {
				$family =& Family::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
				$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
				if ($family->disp && $fact->disp) {
					$text = FactFunctions::GetCalendarFact($fact, $blockaction, $filter, "all", GetCurrentYear(), GetCurrentMonth(), GetCurrentDay());
					if ($text!="filter") {
						if ($lastgid!=$factarr[0]) {
//									if ($lastgid != "") print "<br />";
							print "<div class=\"TodaysEventsLink\">";
								print "<a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">";
								print "<span class=\"TodaysEventsName\">".PrintReady($family->sortable_name.($family->sortable_addname == "" ? "" : "(".$family->sortable_addname.")"))."</span>";
								print "<span class=\"ListItemXref\">".$family->addxref."</span>";
								print "</a>\n";
							print "</div>";
							$lastgid=$family->xref;
						}
						print "<div class=\"TodaysEventsFact\">";
						print $text;
						print "</div>";
						$OutputDone = true;
					}
				}
				else $PrivateFacts = true;
			}
		}
		if ($PrivateFacts) {// Facts were found but not printed for some reason
			$Advisory = "none_today_privacy";
			if ($OutputDone) $Advisory = "more_today_privacy";
			print "<div class=\"TodaysEventsMessage\">";
				PrintText($Advisory);
			print "</div>";
		} else if (!$OutputDone) {// No Facts were found
			$Advisory = "none_today_" . $filter;
			print "<div class=\"TodaysEventsMessage\">";
				PrintText($Advisory);
			print "</div>";
		}
	
	break;
	
	case "loadblockusermessage":
	
		print "<form name=\"messageform\" action=\"\" onsubmit=\"return confirm('".GM_LANG_confirm_message_delete."');\">\n";				
		$usermessages = MessageController::getUserMessages($gm_user->username);
		print "<form name=\"messageform\" action=\"\" onsubmit=\"return confirm('".GM_LANG_confirm_message_delete."');\">\n";
		if (count($usermessages)==0) {
			print "<div class=\"UserMessagesMessage\">".GM_LANG_no_messages."</div>";
		}
		else {
			print "<input type=\"hidden\" name=\"action\" value=\"deletemessage\" />\n";
			print "<table class=\"ListTable\"><tr>\n";
			print "<td class=\"ListTableColumnHeader\">".GM_LANG_delete."</td>\n";
			print "<td class=\"ListTableColumnHeader\">".GM_LANG_message_subject."</td>\n";
			print "<td class=\"ListTableColumnHeader\">".GM_LANG_date_created."</td>\n";
			print "<td class=\"ListTableColumnHeader\">".GM_LANG_message_from."</td>\n";
			print "</tr>\n";
			foreach($usermessages as $key=>$message) {
				if (!is_null($message->id)) $key = $message->id;
				print "<tr>";
				print "<td class=\"ListTableContent\">";
					print "<input type=\"checkbox\" name=\"message_id[]\" value=\"$key\" />";
				print "</td>\n";
				$showmsg=preg_replace("/(\w)\/(\w)/","\$1/<span style=\"font-size:1px;\"> </span>\$2",PrintReady($message->subject));
				$showmsg=preg_replace("/@/","@<span style=\"font-size:1px;\"> </span>",$showmsg);
				print "<td class=\"ListTableContent\">";
					print "<a href=\"#\" onclick=\"expand_layer('message$key'); return false;\"><b>".$showmsg."</b> <img id=\"message${key}_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" alt=\"\" title=\"\" /></a>";
				print "</td>\n";
				if (!is_null($message->created) && $message->created != "") $time = strtotime($message->created);
				else $time = time();
				$day = date("j", $time);
				$mon = date("M", $time);
				$year = date("Y", $time);
				// if incoming, print the from address.
				// if outgoing, print the to address.
				if ($message->from == $gm_user->username) $mdir = "to";
				else $mdir = "from";
				$tempuser =& User::GetInstance($message->$mdir);
				print "<td class=\"ListTableContent\">";
					print GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, $time);
				print "</td>\n";
				print "<td class=\"ListTableContent\">";
					// If it's an existing user, print the details. Also do this if it doesn't appear to be a valid e-mail address
					if (!empty($tempuser->username) || stristr($message->$mdir, "Genmod-noreply") || !CheckEmailAddress($message->$mdir, false)) {
						print PrintReady($tempuser->firstname." ".$tempuser->lastname);
						if (!empty($tempuser->username)) $prt = " - ";
						else $prt = "";
						if ($TEXT_DIRECTION=="ltr") print " &lrm;".$prt.htmlspecialchars($message->$mdir)."&lrm;";
						else print " &rlm;".$prt.htmlspecialchars($message->$mdir)."&rlm;";
					}
					else print "<a href=\"mailto:".$message->$mdir."?SUBJECT=".$message->subject."\">".preg_replace("/@/","@<span style=\"font-size:1px;\"> </span>",$message->$mdir)."</a>";
				print "</td>\n";
				print "</tr>\n";
				print "<tr><td class=\"UserMessageMessageContainer\" colspan=\"4\">";
					print "<div id=\"message$key\" class=\"UserMessageMessageContent\" style=\"display: none;\">\n";
						$message->body = nl2br(preg_replace('#\( (http://\S+) \)#', "<a href=\"$1\" dir =\"ltr\">$1</a>", $message->body));
	
						print PrintReady($message->body)."\n";
						if (preg_match("/RE:/", $message->subject)==0) $message->subject = "RE:".$message->subject;
						// Only print the reply link if it's an incoming message.
						// Also, we don't use Genmod to send mail to non-users.
						// If the originator is not a user, let the Genmod user send a mail from his own mail system
						if ($mdir == "from") {
							if (!empty($tempuser->username)) print "<a href=\"#\" onclick=\"reply('".$message->$mdir."', '".addslashes($message->subject)."'); return false;\">".GM_LANG_reply."</a> | ";
							else if (!stristr($message->$mdir, "Genmod-noreply")) print "<a href=\"mailto:".$message->from."?SUBJECT=".$message->subject."\">".GM_LANG_reply."</a> | ";
						}
						print "<a href=\"index.php?action=deletemessage&amp;message_id=$key\" onclick=\"return confirm('".GM_LANG_confirm_message_delete."');\">".GM_LANG_delete."</a>";
					print "</div></td>";
				print "</tr>\n";
			}
			print "</table>\n";
			print "<input type=\"submit\"  value=\"".GM_LANG_delete."\" /><br /><br />\n";
		}
		$users = UserController::GetUsers("lastname", "asc", "firstname");
		if (count($users)>1) {
			print GM_LANG_message." <select name=\"touser\" id=\"touser\">\n";
			$username = $gm_user->username;
			if ($gm_user->userIsAdmin()) {
				print "<option value=\"all\">".GM_LANG_broadcast_all."</option>\n";
				print "<option value=\"never_logged\">".GM_LANG_broadcast_never_logged_in."</option>\n";
				print "<option value=\"last_6mo\">".GM_LANG_broadcast_not_logged_6mo."</option>\n";
			}
			foreach($users as $indexval => $user) {
				if ($username != $user->username && $user->verified_by_admin)  {
					print "<option value=\"".$user->username."\"";
					print ">".PrintReady($user->lastname.", ".$user->firstname);
					if ($TEXT_DIRECTION=="ltr") print " &lrm; - ".$user->username."&lrm;</option>\n";
					else print " &rlm; - ".$user->username."&rlm;</option>\n";
				}
			}
			print "</select><input type=\"button\" value=\"".GM_LANG_send."\" onclick=\"message(document.getElementById('touser').value, 'messaging2', ''); return false;\" />\n";
		}
		print "</form>\n";
	
	break;
	
	case "loadblockupcoming":

		$found_facts = BlockFunctions::GetCachedEvents($blockaction, $daysprint, $filter, $onlyBDM, $skipfacts);
		$OutputDone = false;
		$PrivateFacts = false;
		$lastgid="";
	//	print "Facts found: ".count($found_facts)."<br />";
	
		// Cache the selected indi's and fams in the indilist and famlist
		$selindi = array();
		$selfam = array();
		foreach($found_facts as $key=>$factarr) {
			if ($factarr[2] == "INDI") $selindi[] = $factarr[0];
			if ($factarr[2] == "FAM") $selfam[] = $factarr[0];
		}
		
		$selindi = implode("[".GedcomConfig::$GEDCOMID."]','", $selindi);
		$selindi .= "[".GedcomConfig::$GEDCOMID."]'";
		$selindi = "'".$selindi;
		ListFunctions::GetIndiList("no", $selindi);
		$selfam = implode("[".GedcomConfig::$GEDCOMID."]','", $selfam);
		$selfam .= "[".GedcomConfig::$GEDCOMID."]'";
		$selfam = "'".$selfam;
		ListFunctions::GetFamList("no", $selfam);
		
		foreach($found_facts as $key=>$factarr) {
			$datestamp = $factarr[3];
			if ($factarr[2]=="INDI") {
				$person =& Person::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
				$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
				$gid = $factarr[0];
				$factrec = $factarr[1];
				if ($person->disp && $fact->disp) {
					$text = FactFunctions::GetCalendarFact($fact, $blockaction, $filter);
					if ($text!="filter") {
						if ($lastgid!=$gid) {
							//if ($lastgid != "") print "<br />";
							print "<div class=\"UpcomingEventsLink\">";
								print "<a href=\"individual.php?pid=$gid&amp;gedid=".GedcomConfig::$GEDCOMID."\"><span class=\"UpcomingEventsName\">";
								print PrintReady($person->revname.($person->revaddname == "" ? "" : " (".$person->revaddname.")"))."</span>";
								print "<img id=\"box-".$gid."-".$key."-sex\" src=\"".GM_IMAGE_DIR."/";
								if ($factarr[5] == "M") print $GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male;
								else if ($factarr[5] == "F") print $GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female;
								else print $GM_IMAGES["sexn"]["small"]."\" title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown;
								print "\" class=\"GenderImage\" />";
								print "<span class=\"ListItemXref\">".$person->addxref."</span>";
								print "</a>";
							print "</div>\n";
							$lastgid = $gid;
						}
						print "<div class=\"UpcomingEventsFact\">";
							print $text;
						print "</div>";
						$OutputDone = true;
					}
				}
				else $PrivateFacts = true;
			}
	
			if ($factarr[2]=="FAM") {
				$family =& Family::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
				$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
				if ($family->disp && $fact->disp) {
					$text = FactFunctions::GetCalendarFact($fact, $blockaction, $filter);
					if ($text!="filter") {
						if ($lastgid!=$factarr[0]) {
//									if ($lastgid != "") print "<br />";
							print "<div class=\"UpcomingEventsLink\">";
								print "<a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\"><span class=\"UpcomingEventsName\">";
								print PrintReady($family->sortable_name.($family->sortable_addname == "" ? "" : "(".$family->sortable_addname.")"))."</span>";
								print "<span class=\"ListItemXref\">".$family->addxref."</span>";
								print "</a>";
							print "</div>\n";
							$lastgid = $family->xref;
						}
						print "<div class=\"UpcomingEventsFact\">";
							print $text;
						print "</div>";
						$OutputDone = true;
					}
				}
				else $PrivateFacts = true;
			}
		}
		
		if ($PrivateFacts) { // Facts were found but not printed for some reason
			// 4 is upcoming
			define("GM_LANG_global_num4",$daysprint);
			$Advisory = "no_events_privacy";
			if ($OutputDone) $Advisory = "more_events_privacy";
			if ($daysprint==1) $Advisory .= "1";
			print "<div class=\"UpcomingEventsMessage\">";
				PrintText($Advisory);
			print "</div>";
		} 
		else if (!$OutputDone) { // No Facts were found
			define("GM_LANG_global_num4", $daysprint);
			$Advisory = "no_events_" . $config["filter"];
			if ($daysprint==1) $Advisory .= "1";
			print "<div class=\"UpcomingEventsMessage\">";
				PrintText($Advisory);
			print "</div>";
		}


	break;

}
session_write_close();
?>