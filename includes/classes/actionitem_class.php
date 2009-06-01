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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
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
		print $gm_lang["action0"]."</option>";
		print "<option value=\"1\"";
		if ($this->status == 1) print "selected=\"selected\" >";
		else print ">";
		print $gm_lang["action1"]."</option>";
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
		print "<option value=\"0\" selected=\"selected\" >".$gm_lang["action0"]."</option>";
		print "<option value=\"1\" >".$gm_lang["action1"]."</option>";
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
		print $gm_lang["action".$this->status];
		print "<br />";
	}
}
?>