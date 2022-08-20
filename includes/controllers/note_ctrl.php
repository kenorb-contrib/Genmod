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
 * @version $Id: note_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the Note page.
 */
class NoteController extends DetailController {
	
	public $classname = "NoteController";	// Name of this class
	
	public $note = null;					// Holder for the note object
	public $notelist = null;				// Holder for the notelist array
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $ENABLE_CLIPPINGS_CART, $nonfacts;
		
		parent::__construct();

		$nonfacts = array();
				
		if (!empty($_REQUEST["oid"])) $this->xref = strtoupper($_REQUEST["oid"]);
		
		if (!is_null($this->xref)) {
			$this->xref = CleanInput($this->xref);
		
			$this->note =& Note::GetInstance($this->xref);
		
			//-- perform the desired action
			switch($this->action) {
				case "addfav":
					$this->addFavorite();
					break;
			}
		}
	}
	
	public function __get($property) {
		switch($property) {
			default:
				return parent::__get($property);
		}
	}
	
	/**
	 * get the title for this page
	 * @return string
	 */
	protected function getPageTitle() {

		if (is_null($this->pagetitle)) {
			$this->pagetitle = "";
			$this->pagetitle .= $this->note->getTitle().$this->note->addxref;
			$this->pagetitle .= " - ".GM_LANG_note_info;
		}
		return $this->pagetitle;
	}
	
	/**
	 * get edit menut
	 * @return Menu
	 */
	public function &getEditMenu() {
		global $gm_user;
		
		// edit note menu
		$menu = new Menu(GM_LANG_edit_note);

		if (!$this->note->isdeleted) {
			// edit note / edit_raw
			if ($gm_user->userCanEditGedlines()) {
				$submenu = new Menu(GM_LANG_edit_raw);
				$submenu->addLink("edit_raw('".$this->xref."', 'edit_raw', 'NOTE');");
				$menu->addSubmenu($submenu);
			}

			// edit note / delete_note
			$submenu = new Menu(GM_LANG_delete_note);
			$submenu->addLink("if (confirm('".GM_LANG_confirm_delete_note."'))  deletegnote('".$this->xref."', 'delete_note'); ");
			$menu->addSubmenu($submenu);

		}
		if ($this->note->ischanged) {
			// edit_note / seperator
			$submenu = new Menu();
			$submenu->isSeperator();
			$menu->addSubmenu($submenu);

			// edit_note / show/hide changes
			if (!$this->show_changes) $submenu = new Menu(GM_LANG_show_changes);
			else $submenu = new Menu(GM_LANG_hide_changes);
			$submenu->addLink('showchanges();');
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * get the other menu
	 * @return Menu
	 */
	public function &getOtherMenu() {
		global $ENABLE_CLIPPINGS_CART, $gm_user;
		
		// other menu
		$menu = new Menu(GM_LANG_other);
		if ($gm_user->userCanViewGedlines()) {
				// other / view_gedcom
				if ($this->show_changes) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu(GM_LANG_view_gedcom);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $gm_user->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu(GM_LANG_add_to_cart);
				$submenu->addLink('clippings.php?action=add&id='.$this->xref.'&type=note');
				$menu->addSubmenu($submenu);
		}
		if ($this->note->disp && !empty($this->uname) && !$this->note->isuserfav) {
				// other / add_to_my_favorites
				$submenu = new Menu(GM_LANG_add_to_my_favorites);
				$submenu->addLink('note.php?action=addfav&oid='.$this->xref.'&gedid='.GedcomConfig::$GEDCOMID);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	public function GetNoteList($filter="", $selection="") {
		global $note_hide;
		
 		$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_type='NOTE' AND o_file='".GedcomConfig::$GEDCOMID."'";
 		if (!empty($filter)) $sql .= " AND o_gedrec LIKE '%".$filter."%'";
 		if (!empty($selection)) $sql .= " AND o_id IN (".$selection.")";
 		$res = NewQuery($sql);
 		while ($row = $res->FetchAssoc()) {
	 		$note =& Note::GetInstance($row["o_id"], $row, $row["o_file"]);
	 		$note->GetTitle(40);
	 		if ($note->disp) $this->notelist[] = $note;
	 		else $note_hide++;
 		}
 		$this->NotelistSort();
	}

	
	private function NotelistSort() {
		uasort($this->notelist, array($this, "TitleObjSort"));
	}
	
	public function TitleObjSort($a, $b) {
		if ($a->title != $b->title) return StringSort(ltrim($a->title), ltrim($b->title));
		else {
			$anum = preg_replace("/\D*/", "", $a->xref);
			$bnum = preg_replace("/\D*/", "", $b->xref);
			return $anum > $bnum;
		}
	}
	/**
	 * print main note row
	 *
	 * this function will print a table row for a fact table for a level 1 note in the main record
	 * @param string $factrec	the raw gedcom sub record for this note
	 * @param int $level		The start level for this note, usually 1
	 * @param string $pid		The gedcom XREF id for the level 0 record that this note is a part of
	 */
	protected function PrintGeneralNote($styleadd="", $mayedit=true) {
		global $GM_IMAGES;

		if (!$this->note->disp) return false;

		if (($this->note->textchanged || $this->note->isdeleted) && $this->note->show_changes && !($this->view == "preview")) {
			$styleadd = "ChangeOld";
			print "\n\t\t<tr><td class=\"FactLabelCell $styleadd\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />".GM_LANG_note.":";
			print " </td>\n<td class=\"FactDetailCell $styleadd\">";
			if (PrivacyFunctions::showFactDetails("NOTE", $this->note->xref)) {
				print PrintReady($this->note->text)."<br />\n";
				// See if RESN tag prevents display or edit/delete
			 	$resn_tag = preg_match("/2 RESN (.*)/", $this->note->gedrec, $match);
	 			if ($resn_tag > 0) $resn_value = strtolower(trim($match[1]));
				// -- Find RESN tag
				if (isset($resn_value)) {
					PrintHelpLink("RESN_help", "qm");
					print constant("GM_LANG_".$resn_value)."\n";
				}
				print "<br />\n";
			}
			print "</td></tr>";
		}
		if (($this->note->textchanged || $this->note->isnew) && !$this->note->isdeleted && $this->show_changes && !($this->view == "preview")) $styleadd = "ChangeNew";
		if (!$this->note->isdeleted || !$this->show_changes) {
			print "\n\t\t<tr><td class=\"FactLabelCell $styleadd\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />".GM_LANG_note.":";
			if ($this->note->canedit && ($styleadd!="ChangeOld")&&($this->view != "preview")&& $mayedit) {
				$menu = array();
				$menu["label"] = GM_LANG_edit;
				$menu["labelpos"] = "right";
				$menu["icon"] = "";
				$menu["link"] = "#";
				$menu["onclick"] = "return edit_record('$this->xref', 'NOTE', '1', 'edit_general_note', 'NOTE');";
				$menu["class"] = "";
				$menu["hoverclass"] = "";
				$menu["flyout"] = "down";
				$menu["submenuclass"] = "submenu";
				$menu["items"] = array();
				$submenu = array();
				$submenu["label"] = GM_LANG_edit;
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return edit_record('$this->xref', 'NOTE', '1', 'edit_general_note', 'NOTE');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				$submenu = array();
				$submenu["label"] = GM_LANG_copy;
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return copy_record('$this->xref', 'NOTE', '1', 'copy_general_note', 'NOTE');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				// No delete option. A note cannot be without text!
				print "<div class=\"FactLabelCellEdit\">";
				FactFunctions::PrintFactMenu($menu);
				print "</div>";
			}
			print " </td>\n<td class=\"FactDetailCell $styleadd\">";
			if (PrivacyFunctions::showFactDetails("NOTE", $this->note->xref)) {
				if ($styleadd == "ChangeNew") print PrintReady($this->note->GetNoteText(true))."<br />\n";
				else print PrintReady($this->note->GetNoteText())."<br />\n";
				// See if RESN tag prevents display or edit/delete
			 	$resn_tag = preg_match("/2 RESN (.*)/", $this->note->gedrec, $match);
		 		if ($resn_tag > 0) $resn_value = strtolower(trim($match[1]));
				// -- Find RESN tag
				if (isset($resn_value)) {
					PrintHelpLink("RESN_help", "qm");
					print constant("GM_LANG_".$resn_value)."\n";
				}
				print "<br />\n";
			}
			print "</td></tr>";
		}
	}
}
?>