<?php
/**
 * Class controller for research actions
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
 * @version $Id: action_class.php,v 1.7 2009/05/03 06:20:06 sjouke Exp $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class ActionController {
	
	var $classname = "Actions";
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
			
	function GetActionListByRepo($repo, $status="") {
		global $TBLPREFIX, $GEDCOMID;

		$this->actionlist = array();
		$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$GEDCOMID."' AND a_repo='".$repo."'";
		if ($status != "") $sql .= " AND a_status='".$status."'";
		else $sql .= " ORDER BY a_status ASC";
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
	
	function GetNewItem() {
		return new ActionItem();
	}
}
?>