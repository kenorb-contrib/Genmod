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
 * @version $Id$
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class ActionController {
	
	public $classname = "Actions";
	private static $actionlist = array();

	public function AddItem($pid, $text, $repo, $status=0) {
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
	
	public function DeleteItem($id) {
		
		$item = self::GetItem($id);
		$item->DeleteThis();
		return true;
	}
	
	public function GetItem($id) {
		global $TBLPREFIX;
		
		$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_id='".$id."'";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			return new ActionItem($row);
		}
	}
	
	public function GetSelectActionList($repo="", $pid="", $gedcomid="", $status="", $countonly=false) {
		global $TBLPREFIX, $GEDCOMID, $gm_user;
		
		self::$actionlist = array();
		$action_open = 0;
		$action_closed = 0;
		$action_hide = 0;
		if ($gm_user->ShowActionLog()) { 
			if ($gedcomid == "") $gedcomid = $GEDCOMID;
			if ($countonly) {
				$sql = "SELECT count(a_status) as count, a_status FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$gedcomid."'";
				if (!empty($repo)) $sql .= " AND a_repo='".$repo."'";
				if (!empty($pid)) $sql .= " AND a_pid='".$pid."'";
				if (!empty($status)) $sql .= " AND a_status='".$status."'";
				$sql .= " GROUP BY a_status";
				$res = NewQuery($sql);
				while ($row = $res->FetchAssoc()) {
					if ($row["a_status"] == "1") $action_open = $row["count"];
					else if ($row["a_status"] == "0") $action_closed = $row["count"];
				}
			}
			else {
				$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$gedcomid."' AND a_repo='".$repo."'";
				if ($status != "") $sql .= " AND a_status='".$status."'";
				$res = NewQuery($sql);
				while ($row = $res->FetchAssoc()) {
					$action = null;
					$action = new ActionItem($row, $repo);
					if ($action->disp) {
						if ($action->status == 1) $action_open++;
						else $action_closed++;
						self::$actionlist[] = $action;
					}
					else $action_hide++;
				}
				if (empty($repo)) self::RepoSort();
				else self::IndiSort();
			}
		}
		return array(self::$actionlist, $action_open, $action_closed, $action_hide);
	}

	public function GetActionList($status="", $reposort=false) {
		global $TBLPREFIX, $GEDCOMID, $gm_user;
		
		self::$actionlist = array();
		$actionopen = 0;
		$actionclosed = 0;
		if ($gm_user->ShowActionLog()) { 
			$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$GEDCOMID."'";
			if ($status == "0" || $status == "1") $sql .= " AND a_status='".$status."'";
			$sql .= " ORDER BY a_repo ASC, a_status ASC";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				self::$actionlist[] = new ActionItem($row);
			}
			if ($reposort) {
				self::RepoSort();
			}
			else self::IndiSort();
		}
		return self::$actionlist;
	}

	public function PrintAddLink() {
		global $gm_lang;
		
		print "<tr>";
		print "<td class=\"shade2 width20\"><div id=\"todo_txt\">";
		print_help_link("add_todo_help", "qm");
		print $gm_lang["add_todo"]."</div></td>";
		print "<td class=\"shade1\" id=\"add_todo\"><a href=\"javascript: ".$gm_lang["add_todo"]."\" onclick=\"document.getElementById('todo_txt').style.display='none'; sndReq('add_todo', 'action_add'); return false;\">".$gm_lang["add_todo"]."</a>";
		print "</td>";
		print "</tr>";
	}

	private function RepoSort() {
		uasort(self::$actionlist, array("ActionController", "ActionRepoSort"));
	}
	
	private function IndiSort() {
		uasort(self::$actionlist, array("ActionController", "ActionIndiSort"));
	}
	
	private function ActionRepoSort($a, $b) {
		if ($a->repodesc != $b->repodesc) return StringSort($a->repodesc, $b->repodesc);
		else return StringSort($a->indidesc, $b->indidesc);
	}
	
	private function ActionIndiSort($a, $b) {
		if ($a->indidesc != $b->indidesc) return StringSort($a->indidesc, $b->indidesc);
		else return StringSort($a->repodesc, $b->repodesc);
	}
	
	public function GetNewItem() {
		return new ActionItem();
	}
}
?>