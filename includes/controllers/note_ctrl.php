<?php
/**
 * Controller for the Note page view
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
 * @subpackage Charts
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the Note page.
 */
class NoteControllerRoot extends BaseController {
	var $classname = "NoteControllerRoot";
	var $oid;
	var $show_changes = "yes";
	var $action = "";
	var $note = null;
	var $uname = "";
	var $canedit = false;
	var $isempty = false;
	var $display_other_menu = false;
	var $notelist = array();
	var $xref = null;
	
	/**
	 * constructor
	 */
	public function __construct($oid="") {
		global $gm_lang, $CONTACT_EMAIL, $GEDCOM;
		global $ENABLE_CLIPPINGS_CART, $Users, $show_changes, $nonfacts;
		
		parent::__construct();

		$nonfacts = array();
				
		if ($show_changes == "yes" && $Users->UserCanEdit($this->uname)) $this->show_changes = true;
		if (!empty($_REQUEST["action"])) $this->action = $_REQUEST["action"];
		if (!empty($oid)) $this->xref = $oid;
		else if (!empty($_REQUEST["oid"])) $this->xref = strtoupper($_REQUEST["oid"]);
		
		$this->uname = $Users->GetUserName();

		if (!is_null($this->xref)) {
			$this->xref = CleanInput($this->xref);
		
			$noterec = FindOtherRecord($this->xref);
			if (!$noterec) {
				if (!GetChangeData(true, $this->xref, true, "", "")) $this->isempty = true;
				$noterec = "0 @".$this->xref."@ NOTE\r\n";
				$this->note = new Note($noterec, true);
			}
			else $this->note = new Note($noterec);
		
			if (!$this->note->canDisplayDetails()) {
				print_header($gm_lang["private"]." ".$gm_lang["note_info"]);
				print_privacy_error($CONTACT_EMAIL);
				print_footer();
				exit;
			}
		
			//-- perform the desired action
			switch($this->action) {
				case "addfav":
					$this->addFavorite();
					break;
				case "accept":
					$this->acceptChanges();
					break;
			}
			
			if ($this->note->canDisplayDetails()) {
				$this->canedit = $Users->userCanEdit($this->uname);
			}
			
			if ($this->note->canDisplayDetails() && ($Users->userCanViewGedlines() || $ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel() || !empty($this->uname))) {
				$this->display_other_menu = true;
			}
		}
		// This is to hide ThisNote menu if there is not just one note to display (notelist)
		else $this->isempty = true;
	}
	
	/**
	 * Add a new favorite for the action user
	 */
	function addFavorite() {
		global $GEDCOM;
		if (empty($this->uname)) return;
		if (!empty($_REQUEST["oid"])) {
			$oid = strtoupper($_REQUEST["oid"]);
			$indirec = FindOtherRecord($oid);
			if ($indirec) {
				$favorite = array();
				$favorite["username"] = $this->uname;
				$favorite["gid"] = $oid;
				$favorite["type"] = "NOTE";
				$favorite["file"] = $GEDCOM;
				$favorite["url"] = "";
				$favorite["note"] = "";
				$favorite["title"] = "";
				addFavorite($favorite);
			}
		}
	}
	
	/**
	 * get the title for this page
	 * @return string
	 */
	function getPageTitle() {
		global $gm_lang;
		return $this->note->getTitle()." - ".$this->xref." - ".$gm_lang["note_info"];
	}
	/**
	 * check if use can edit this person
	 * @return boolean
	 */
	function userCanEdit() {
		return $this->canedit;
	}
	
	/**
	 * get edit menut
	 * @return Menu
	 */
	function &getEditMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $Users, $show_changes;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// edit note menu
		$menu = new Menu($gm_lang['edit_note']);

		if ($this->userCanEdit()) {
			// edit note / edit_raw
			if ($Users->userCanEditGedlines()) {
				$submenu = new Menu($gm_lang['edit_raw']);
				$submenu->addLink("edit_raw('".$this->xref."', 'edit_raw');");
				$menu->addSubmenu($submenu);
			}

			// edit note / delete_note
			$submenu = new Menu($gm_lang['delete_note']);
			$submenu->addLink("if (confirm('".$gm_lang["confirm_delete_note"]."'))  deletegnote('".$this->xref."', 'delete_note'); ");
			$menu->addSubmenu($submenu);

			if (GetChangeData(true, $this->xref, true)) {
				// edit_note / seperator
				$submenu = new Menu();
				$submenu->isSeperator();
				$menu->addSubmenu($submenu);

				// edit_note / show/hide changes
				if ($show_changes == "no") $submenu = new Menu($gm_lang['show_changes']);
				else $submenu = new Menu($gm_lang['hide_changes']);
				$submenu->addLink('showchanges();');
				$menu->addSubmenu($submenu);
			}
		}
		return $menu;
	}
	
	/**
	 * get the other menu
	 * @return Menu
	 */
	function &getOtherMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang;
		global $ENABLE_CLIPPINGS_CART, $Users;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// other menu
		$menu = new Menu($gm_lang['other']);
		if ($Users->userCanViewGedlines()) {
				// other / view_gedcom
				if ($this->show_changes == 'yes' && $this->userCanEdit()) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu($gm_lang['view_gedcom']);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu($gm_lang['add_to_cart']);
				$submenu->addLink('clippings.php?action=add&id='.$this->xref.'&type=note');
				$menu->addSubmenu($submenu);
		}
		if ($this->note->canDisplayDetails() && !empty($this->uname)) {
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites']);
				$submenu->addLink('note.php?action=addfav&oid='.$this->xref.'&gid='.$this->xref);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	function GetNoteList($filter="", $selection="") {
		global $TBLPREFIX, $GEDCOMID, $note_hide;
		
 		$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_type='NOTE' AND o_file='".$GEDCOMID."'";
 		if (!empty($filter)) $sql .= " AND o_gedcom LIKE '%".$filter."%'";
 		if (!empty($selection)) $sql .= " AND o_id IN (".$selection.")";
 		$res = NewQuery($sql);
 		while ($row = $res->FetchAssoc()) {
	 		$note = new Note($row["o_gedcom"]);
	 		$note->GetTitle(40);
	 		if ($note->canDisplayDetails()) $this->notelist[] = $note;
	 		else $note_hide++;
 		}
 		$this->NotelistSort();
	}

	//-- search through the gedcom records for notes, full text
	function FTSearchNotes($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen, $note_hide, $note_total;
	
	// Get the min and max search word length
	GetFTWordLengths();

	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);
	
	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." o_gedcom REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." o_gedcom REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " AND o_gedcom NOT REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND o_gedcom NOT REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE (MATCH (o_gedcom) AGAINST ('".$DBCONN->EscapeQuery($query)."' IN BOOLEAN MODE))";
	}

	if (!$allgeds) $sql .= " AND o_file='".$GEDCOMID."'";
	
	$sql .= " AND o_type='NOTE'";

	if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "o_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}

	$note_total = array();
	$note_hide = array();
	$res = NewQuery($sql);
	if ($res) {
 		while ($row = $res->FetchAssoc()) {
	 		$note = new Note($row["o_gedcom"]);
	 		$note->GetTitle(40);
	 		$note->SetGedcomId($row["o_file"]);
	 		SwitchGedcom($row["o_file"]);
			$note_total[$note->xref."[".$GEDCOM."]"] = 1;
	 		if ($note->canDisplayDetails()) $this->notelist[] = $note;
	 		else $note_hide[$note->xref."[".$GEDCOM."]"] = 1;
	 		SwitchGedcom();
 		}
 		$this->NotelistSort();
	}
}
	
		
	function NotelistSort() {
		uasort($this->notelist, array($this, "NotelistObjSort"));
	}
	
	function NotelistObjSort($a, $b) {
		if ($a->title != $b->title) return StringSort(ltrim($a->title), ltrim($b->title));
		else {
			$anum = preg_replace("/\D*/", "", $a->xref);
			$bnum = preg_replace("/\D*/", "", $b->xref);
			return $anum > $bnum;
		}
	}
}
	
 		
// -- end of class
//-- load a user extended class if one exists
if (file_exists('includes/controllers/note_ctrl_user.php')) {
		include_once 'includes/controllers/note_ctrl_user.php';
} 
else {
	class NoteController extends NoteControllerRoot 
	{
	}
}
?>