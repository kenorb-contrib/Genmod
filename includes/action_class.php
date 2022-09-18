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
 * @version $Id: action_class.php,v 1.5 2008/08/02 07:14:11 sjouke Exp $
 */

if (strstr($_SERVER["SCRIPT_NAME"],"action_class")) {
	require "../intrusion.php";
}

class Actions {
	
	var $actionlist= array();

	function AddItem($pid, $text, $repo, $status=0) {
		global $GEDCOMID;

		$item = new ActionItem();
		$item->pid = $pid;
		$item->text = addslashes($text);
		$item->repo = $repo;
		$item->gedfile = $GEDCOMID;
		$item->status = $status;
		$item->AddThis();
		return $item;
	}
	
	function DeleteItem($id) {
		
		$item = $this->GetItem($id);
		$item->DeleteThis();
		return true;
	}
	
	function GetItem($id) {
		global $TBLPREFIX;
		
		$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_id='".$id."'";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			return new ActionItem($row);
		}
	}
	
	function GetActionListByID($pid) {
		global $TBLPREFIX, $GEDCOMID;
		
		$this->actionlist = array();
		$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$GEDCOMID."' AND a_pid='".$pid."'";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$this->actionlist[] = new ActionItem($row);
		}
		return $this->actionlist;
	}
			
	function GetActionListByRepo($repo) {
		global $TBLPREFIX, $GEDCOMID;
		
		$this->actionlist = array();
		$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$GEDCOMID."' AND a_repo='".$repo."' ORDER BY a_status ASC";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$this->actionlist[] = new ActionItem($row);
		}
		return $this->actionlist;
	}

	function GetActionList($status="", $reposort=false) {
		global $TBLPREFIX, $GEDCOMID;
		
		$this->actionlist = array();
		$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$GEDCOMID."'";
		if ($status == "0" || $status == "1") $sql .= " AND a_status='".$status."'";
		$sql .= " ORDER BY a_repo ASC, a_status ASC";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$this->actionlist[] = new ActionItem($row);
		}
		if ($reposort) {
			$this->RepoSort();
		}
		return $this->actionlist;
	}

	function PrintAddLink() {
		global $gm_lang;
		
		print "<tr>";
		print "<td class=\"shade2 width20\"><div id=\"todo_txt\">";
		print_help_link("add_todo_help", "qm");
		print $gm_lang["add_todo"]."</div></td>";
		print "<td class=\"shade1\" id=\"add_todo\"><a href=\"javascript: ".$gm_lang["add_todo"]."\" onclick=\"document.getElementById('todo_txt').style.display='none'; sndReq('add_todo', 'action_add'); return false;\">".$gm_lang["add_todo"]."</a>";
		print "</td>";
		print "</tr>";
	}

	function RepoSort() {
		uasort($this->actionlist, array($this, "ActionObjSort"));
	}
	
	function ActionObjSort($a, $b) {
		return StringSort($a->repodesc, $b->repodesc);
	}
}

	
class ActionItem {
	
	var $id = 0;
	var $pid = "";
	var $gedfile = "";
	var $text = "";
	var $repo = "";
	var $repodesc = "";
	var $status = 0;
	var $exists = false;
	
	function AddThis() {
		global $GEDCOMID, $TBLPREFIX, $DBCONN;
		
		$sql = "INSERT INTO ".$TBLPREFIX."actions VALUES('', '".$this->pid."', '".$this->repo."','".$this->gedfile."', '".$DBCONN->EscapeQuery($this->text)."','".$this->status."')";
		$res = NewQuery($sql);
		$this->id = $res->InsertID();
	}
	
	function DeleteThis() {
		global $TBLPREFIX;
		
		$sql = "DELETE FROM ".$TBLPREFIX."actions WHERE a_id='".$this->id."'";
		$res = NewQuery($sql);
	}

	function UpdateThis() {
		global $TBLPREFIX, $DBCONN;
		$sql = "UPDATE ".$TBLPREFIX."actions SET a_pid='".$this->pid."', a_text='".$DBCONN->EscapeQuery($this->text)."', a_repo='".$this->repo."', a_status='".$this->status."' WHERE a_id='".$this->id."'";
		$res = NewQuery($sql);
	}
	
		
	function ActionItem($data="") {
		
		if (is_array($data)) {
			$this->id = $data["a_id"];
			$this->pid = $data["a_pid"];
			$this->text = stripslashes($data["a_text"]);
			$this->gedfile = $data["a_gedfile"];
			$this->repo = $data["a_repo"];
			$this->repodesc = GetRepoDescriptor($this->repo);
			$this->status = $data["a_status"];
			$this->exists = true;
		}
	}
	
	function PrintThis() {
		global $gm_lang, $Users, $gm_username;
		
		print "<tr>";
			print "\n\t\t\t<td id=\"actionfull_".$this->id."\" class=\"shade2 center width20\" style=\"vertical-align: middle\">";
			if ($Users->userCanEdit($gm_username)) {
				$menu = array();
				$menu["label"] = $gm_lang["edit"];
				$menu["labelpos"] = "right";
				$menu["icon"] = "";
				$menu["link"] = "#";
				$menu["onclick"] = "sndReq('action_".$this->id."', 'action_edit', 'aid', '".$this->id."', '', '');";
				$menu["class"] = "";
				$menu["hoverclass"] = "";
				$menu["flyout"] = "down";
				$menu["submenuclass"] = "submenu";
				$menu["items"] = array();
				$submenu = array();
				$submenu["label"] = $gm_lang["edit"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "sndReq('action_".$this->id."', 'action_edit', 'aid', '".$this->id."', '', '');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				$submenu = array();
				$submenu["label"] = $gm_lang["delete"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "if (confirm('".$gm_lang["check_delete"]."')) { sndReq('action_".$this->id."', 'action_delete', 'aid', '".$this->id."', '', ''); window.location.reload(); }";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				print "<div style=\"width:25px;\" class=\"center\" id=\"menu_".$this->id."\">";
				print_menu($menu);
				print "</div>";
			}
			print "</td>";
		print "<td class=\"shade1 wrap\" id=\"action_".$this->id."\">";
		$this->PrintThisItem();
		print "</td></tr>";
	}
	
	function EditThisItem() {
		global $gm_lang;
		
		print "<b>".$gm_lang["todo"]."</b><br />";
		print "<textarea id=\"actiontext\" name=\"actiontext\" rows=\"4\" cols=\"60\">".stripslashes($this->text)."</textarea>";
		print "<br /><br /><b>".$gm_lang["repo"]."</b><br />";
		print "<input type=\"text\" size=\"10\" value=\"".$this->repo."\" id=\"repo\" name=\"repo\" onblur=\"sndReq('desc_".$this->id."', 'getrepodescriptor', 'rid', this.value,'','');\">";
		print_findrepository_link('repo');
		print_addnewrepository_link('repo');

		print "&nbsp;&nbsp;<span id=\"desc_".$this->id."\">".$this->repodesc."</span>";
		print "<br /><br /><b>".$gm_lang["status"]."</b><br />";
		print "<select name=\"status\">";
		print "<option value=\"0\"";
		if ($this->status == 0) print "selected=\"selected\" >";
		else print ">";
		print $gm_lang["action_0"]."</option>";
		print "<option value=\"1\"";
		if ($this->status == 1) print "selected=\"selected\" >";
		else print ">";
		print $gm_lang["action_1"]."</option>";
		print "</select><br /><br />";
		print "<input type=\"button\" value=\"".$gm_lang["save"]."\" onclick=\"sndReq('action_".$this->id."', 'action_update', 'aid','".$this->id."','actiontext', encodeURI(document.actionform.actiontext.value), 'repo', document.actionform.repo.value, 'status', document.actionform.status.value, 'pid', document.actionform.pid.value); \" />";
	}
	
	function AddThisItem() {
		global $gm_lang;
		
		print "<b>".$gm_lang["todo"]."</b><br />";
		print "<textarea id=\"actiontext\" name=\"actiontext\" rows=\"4\" cols=\"60\"></textarea>";
		print "<br /><br /><b>".$gm_lang["repo"]."</b><br />";
		print "<input type=\"text\" size=\"10\" value=\"\" id=\"repo\" name=\"repo\" onblur=\"sndReq('desc_".$this->id."', 'getrepodescriptor', 'rid', this.value,'','');\">";
		print_findrepository_link('repo');
		print_addnewrepository_link('repo');

		print "&nbsp;&nbsp;<span id=\"desc_".$this->id."\"></span>";
		print "<br /><br /><b>".$gm_lang["status"]."</b><br />";
		print "<select name=\"status\">";
		print "<option value=\"0\" selected=\"selected\" >".$gm_lang["action_0"]."</option>";
		print "<option value=\"1\" >".$gm_lang["action_1"]."</option>";
		print "</select><br /><br />";
		print "<input type=\"button\" value=\"".$gm_lang["save"]."\" onclick=\"sndReq('add_todo', 'action_add2', 'aid','".$this->id."','actiontext', encodeURI(document.actionform.actiontext.value), 'repo', document.actionform.repo.value, 'status', document.actionform.status.value, 'pid', document.actionform.pid.value); window.location.reload()\" />";
	}

	
	function PrintThisItem() {
		global $gm_lang;
		
		print "<b>".$gm_lang["todo"]."</b><br />";
		print nl2br(stripslashes($this->text));
		print "<br /><br /><b>".$gm_lang["repo"]."</b><br />";
		print "<a href=\"repo.php?rid=".$this->repo."\">".$this->repodesc."</a>";
		print "<br /><br /><b>".$gm_lang["status"]."</b><br />";
		print $gm_lang["action_".$this->status];
		print "<br />";
	}
}