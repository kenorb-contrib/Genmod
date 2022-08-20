<?php
/**
 * Class file for research actions
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
 * @subpackage DataModel
 * @version $Id: actionitem_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class ActionItem {
	
	// General class information
	public $classname = "ActionItem";	// The name of this class
	
	// Data
	private $id = 0;					// The ID of this item in the database
	private $pid = null;				// The xref of the person that this action relates to
	private $type = null;				// Type of pid: INDI, FAM, etc.
	private $pid_obj = null;			// The related object
	private $pid_disp = null;		 	// Can we display the related object
	private $gedcomid = "";				// The gedfile ID in which this action exists
	private $text = "";					// Text for the item
	private $repo = null;				// The xref of the repository that this action relates to
	private $repo_obj = null;			// The repository object
	private $repo_disp = null;			// Can we display the repository
	private $status = 0;				// Status 0 = closed or 1 = open
	private $disp = null;				// If we can display both the individual/family and the repository related to this action
	private $canshow = null;			// If we can show actions at all
	private $me = null;					// The xref that will display this action
	private $repodesc = null;			// Descriptor for the repository
	private $piddesc = null;			// Name of the person/family/etc.

	public function __construct($values="", $me="") {
		
		if (is_array($values)) {
			$this->id = $values["a_id"];
			$this->pid = $values["a_pid"];
			$this->type = $values["a_type"];
			$this->repo = $values["a_repo"];
			$this->gedcomid = $values["a_file"];
			$this->text = stripslashes($values["a_text"]);
			$this->status = $values["a_status"];
		}
		
		$this->canShow();
		if (!empty($me) && ($me == $this->pid || $me == $this->repo)) $this->me = $me;
	}

	public function __get($property) {
		if ($this->canshow) {
			switch ($property) {
				case "pid":
					return $this->pid;
					break;
				case "gedcomid":
					return $this->gedcomid;
					break;
				case "type":
					return $this->type;
					break;
				case "gedcomid":
					return $this->gedcomid;
					break;
				case "text":
					return $this->text;
					break;
				case "repo":
					return $this->repo;
					break;
				case "status":
					return $this->status;
					break;
				case "disp":
					return $this->canDisplay();
					break;
				case "canshow":
					return $this->canshow;
					break;
				case "repodesc":
					return $this->GetRepoDesc();
					break;
				case "repo_obj":
					return $this->getRepoObj();
					break;
				case "piddesc":
					return $this->GetPidDesc();
					break;
				case "pid_obj":
					return $this->getPidObj();
					break;
				default:
					PrintGetSetError($property, get_class($this), "get");
					break;
			}
		}
	}
	
	public function __set($property, $value) {
		if ($this->canshow) {
			switch ($property) {
				case "pid":
					$this->pid = $value;
					break;
				case "gedcomid":
					$this->gedcomid = $value;
					break;
				case "type":
					$this->type = $value;
					break;
				case "gedfile":
					if (is_numeric($value)) $this->gedcomid = $value;
					break;
				case "text":
					$this->text = $value;
					break;
				case "repo":
					$this->repo = $value;
					break;
				case "status":
					if ($value == "1" || $value == "0") $this->status = $value;
					break;
				default:
					PrintGetSetError($property, get_class($this), "set");
					break;
			}
		}
	}
	
	private function getPidObj() {
		
		if (!is_object($this->pid_obj) && $this->pid != "") {
			if ($this->type == "INDI") $this->pid_obj =& Person::GetInstance($this->pid);
			else if ($this->type == "FAM") $this->pid_obj =& Family::GetInstance($this->pid);
		}
		return $this->pid_obj;
	}
	
	private function getRepoObj() {
		
		if (!is_object($this->repo_obj) && $this->repo != "") {
			$this->repo_obj =& Repository::GetInstance($this->repo);
		}
		return $this->repo_obj;
	}
	
	private function canDisplay() {
		
		if (is_null($this->disp)) {
			
			if ($this->pid != "" && !is_object($this->pid_obj)) $this->getPidObj();
			if (is_object($this->pid_obj)) $this->pid_disp = $this->pid_obj->disp;
			else $this->pid_disp = true;
			
			if ($this->repo != "" && !is_object($this->repo_obj)) $this->getRepoObj();
			if (is_object($this->repo_obj)) $this->repo_disp = $this->repo_obj->disp;
			else $this->repo_disp = true;
			
			if (is_null($this->me)) {
				if (!$this->pid_disp) $this->disp = false;
				else if (!$this->repo_disp) $this->disp = false;
				else $this->disp = true;
			}
			else if ($this->repo == $this->me) {
				$this->disp = $this->pid_disp;
			}
			else if ($this->pid == $this->me) {
				$this->disp = $this->repo_disp;
			}
			else if (!$this->pid_disp || !$this->repo_disp) $this->disp = false;
			else $this->disp = true;
		}
		return $this->disp;
	}
		
	private function getRepoDesc() {
		
		if (is_null($this->repodesc)) {
			if (is_null($this->repo) || empty($this->repo)) $this->repodesc = "";
			else {
				if (!is_object($this->repo_obj)) $this->getRepoObj();
				if (is_object($this->repo_obj)) $this->repodesc = $this->repo_obj->title;
				else $this->repodesc = "";
			}
		}
		return $this->repodesc;
	}
		
	private function GetPidDesc() {
		
		if (is_null($this->piddesc)) {
			if (is_null($this->pid) || empty($this->pid)) $this->piddesc = "";
			else {
				if (!is_object($this->pid_obj)) $this->getPidObj();
				if (is_object($this->pid_obj)) {
					if ($this->pid_obj->datatype == "INDI") $this->piddesc = $this->pid_obj->revname;
					if ($this->pid_obj->datatype == "FAM") $this->piddesc = $this->pid_obj->sortable_name;
				}
				else $this->piddesc = "";
			}
		}
		return $this->piddesc;
	}

	private function canShow() {
		global $gm_user;
		
		if (is_null($this->canshow)) $this->canshow = $gm_user->ShowActionLog();
		return $this->canshow;
	}
 	
	public function AddThis($import=false) {

		if ($import || $this->canShow()) {
			$sql = "INSERT INTO ".TBLPREFIX."actions VALUES('', '".$this->pid."', '".$this->type."', '".$this->repo."','".$this->gedcomid."', '".DbLayer::EscapeQuery($this->text)."','".$this->status."')";
			$res = NewQuery($sql);
			$this->id = $res->InsertID();
		}
	}
	
	public function DeleteThis() {
		
		if ($this->canShow()) {
			$sql = "DELETE FROM ".TBLPREFIX."actions WHERE a_id='".$this->id."'";
			$res = NewQuery($sql);
		}
	}

	public function UpdateThis() {

		if ($this->canShow()) {
			$sql = "UPDATE ".TBLPREFIX."actions SET a_pid='".$this->pid."', a_text='".DbLayer::EscapeQuery($this->text)."', a_repo='".$this->repo."', a_status='".$this->status."' WHERE a_id='".$this->id."'";
			$res = NewQuery($sql);
		}
	}
			
	public function PrintThis() {
		global $gm_user;
		
		if ($this->canShow()) {
			print "<tr>";
			print "\n\t\t\t<td id=\"actionfull_".$this->id."\" class=\"FactLabelCell\" style=\"vertical-align: middle\">";
			if ($gm_user->userCanEdit()) {
				$menu = array();
				$menu["label"] = GM_LANG_edit;
				$menu["labelpos"] = "right";
				$menu["icon"] = "";
				$menu["link"] = "#";
				$menu["onclick"] = "sndReq('action_".$this->id."', 'action_edit', true, 'aid', '".$this->id."', '', '');";
				$menu["class"] = "";
				$menu["hoverclass"] = "";
				$menu["flyout"] = "down";
				$menu["submenuclass"] = "submenu";
				$menu["items"] = array();
				$submenu = array();
				$submenu["label"] = GM_LANG_edit;
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "sndReq('action_".$this->id."', 'action_edit', true, 'aid', '".$this->id."', '', '');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				$submenu = array();
				$submenu["label"] = GM_LANG_delete;
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "if (confirm('".GM_LANG_check_delete."')) { sndReq('action_".$this->id."', 'action_delete', true, 'aid', '".$this->id."', '', ''); window.location.reload(); }";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				print "<div class=\"FactLabelCellEdit\" id=\"menu_".$this->id."\">";
				FactFunctions::PrintFactMenu($menu);
				print "</div>";
			}
			print "</td>";
			print "<td class=\"FactDetailCell\" id=\"action_".$this->id."\">";
			$this->PrintThisItem();
			print "</td></tr>";
		}
	}
	
	public function EditThisItem() {
		
		if ($this->canShow()) {
			print "<b>".GM_LANG_todo."</b><br />";
			print "<textarea id=\"actiontext\" name=\"actiontext\" rows=\"4\" cols=\"60\">".stripslashes($this->text)."</textarea>";
			print "<br /><br /><b>".GM_LANG_repo."</b><br />";
			print "<input type=\"text\" size=\"10\" value=\"".$this->repo."\" id=\"repo\" name=\"repo\" onblur=\"sndReq('desc_".$this->id."', 'getrepodescriptor', true, 'rid', this.value,'','');\">";
			LinkFunctions::PrintFindRepositoryLink('repo');
			LinkFunctions::PrintAddNewRepositoryLink('repo');
		
			print "&nbsp;&nbsp;<span id=\"desc_".$this->id."\">".$this->GetRepoDesc()."</span>";
			print "<br /><br /><b>".GM_LANG_status."</b><br />";
			print "<select name=\"status\">";
			print "<option value=\"0\"";
			if ($this->status == 0) print "selected=\"selected\" >";
			else print ">";
			print GM_LANG_action0."</option>";
			print "<option value=\"1\"";
			if ($this->status == 1) print "selected=\"selected\" >";
			else print ">";
			print GM_LANG_action1."</option>";
			print "</select><br /><br />";
			print "<input type=\"button\" value=\"".GM_LANG_save."\" onclick=\"sndReq('action_".$this->id."', 'action_update', true, 'aid','".$this->id."','actiontext', encodeURI(document.actionform.actiontext.value), 'repo', document.actionform.repo.value, 'status', document.actionform.status.value, 'pid', document.actionform.pid.value); \" />";
		}
	}
	
	public function AddThisItem() {
		
		if ($this->canShow()) {
			print "<b>".GM_LANG_todo."</b><br />";
			print "<textarea id=\"actiontext\" name=\"actiontext\" rows=\"4\" cols=\"60\"></textarea>";
			print "<br /><br /><b>".GM_LANG_repo."</b><br />";
			print "<input type=\"text\" size=\"10\" value=\"\" id=\"repo\" name=\"repo\" onblur=\"sndReq('desc_".$this->id."', 'getrepodescriptor', true, 'rid', this.value,'','');\">";
			LinkFunctions::PrintFindRepositoryLink('repo');
			LinkFunctions::PrintAddNewRepositoryLink('repo');
	
			print "&nbsp;&nbsp;<span id=\"desc_".$this->id."\"></span>";
			print "<br /><br /><b>".GM_LANG_status."</b><br />";
			print "<select name=\"status\">";
			print "<option value=\"0\" selected=\"selected\" >".GM_LANG_action0."</option>";
			print "<option value=\"1\" >".GM_LANG_action1."</option>";
			print "</select><br /><br />";
			print "<input type=\"button\" value=\"".GM_LANG_save."\" onclick=\"sndReq('add_todo', 'action_add2', true, 'aid','".$this->id."','actiontext', encodeURI(document.actionform.actiontext.value), 'repo', document.actionform.repo.value, 'status', document.actionform.status.value, 'pid', document.actionform.pid.value, 'type', '".strtoupper($this->type)."'); window.location.reload();\" />";
		}
	}
	
	public function PrintThisItem() {
		
		if ($this->canShow()) {
			print "<b>".GM_LANG_todo."</b><br />";
			print nl2br(stripslashes($this->text));
			print "<br /><br /><b>".GM_LANG_repo."</b><br />";
			print "<a href=\"repo.php?rid=".$this->repo."&amp;gedid=".$this->gedcomid."\">".$this->GetRepoDesc()."</a>";
			print "<br /><br /><b>".GM_LANG_status."</b><br />";
			print constant("GM_LANG_action".$this->status);
			print "<br />";
		}
	}
	
	public function GetGedcomString() {
		
		$string = "1 _TODO\r\n";
		if ($this->pid != "") $string .= "2 REPO @".$this->repo."@\r\n";
		$string .= "2 ID ".$this->id."\r\n";
		$string .= "2 STAT ".($this->status == 0 ? "Open" : "Closed")."\r\n";
		$string .= "2 TEXT ".preg_replace("/\r\n/", "\r\n3 CONT ", trim($this->text))."\r\n";
		return $string;
	}
}
?>