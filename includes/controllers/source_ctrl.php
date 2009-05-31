<?php
/**
 * Controller for the source page view
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
 * @version $Id: source_ctrl.php,v 1.26 2009/03/16 19:51:12 sjouke Exp $
 */
if (stristr($_SERVER["SCRIPT_NAME"],"source_ctrl")) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the source page.
 */
class SourceControllerRoot extends BaseController {
	var $classname = "SourceControllerRoot";
	var $sid;
	var $show_changes = "yes";
	var $action = "";
	var $source = null;
	var $uname = "";
	var $diffsource = null;
	var $canedit = false;
	var $isempty = false;
	var $display_other_menu = false;
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $gm_lang, $CONTACT_EMAIL, $GEDCOM;
		global $ENABLE_CLIPPINGS_CART, $Users, $nonfacts;
		
		parent::__construct();

		$nonfacts = array();
		
		if ((!isset($_REQUEST["show_changes"]) || $_REQUEST["show_changes"] != "no") && $Users->UserCanEdit($this->uname)) $this->show_changes = true;
		if (!empty($_REQUEST["action"])) $this->action = $_REQUEST["action"];
		if (!empty($_REQUEST["sid"])) $this->sid = strtoupper($_REQUEST["sid"]);
		$this->sid = CleanInput($this->sid);
		
		$sourcerec = FindSourceRecord($this->sid);
		if (!$sourcerec) {
			if (!GetChangeData(true, $this->sid, true, "", "")) $this->isempty = true;
			$sourcerec = "0 @".$this->sid."@ SOUR\r\n";
		}
		
		$this->source = new Source($sourcerec);
		
		if (!$this->source->canDisplayDetails()) {
			print_header($gm_lang["private"]." ".$gm_lang["source_info"]);
			print_privacy_error($CONTACT_EMAIL);
			print_footer();
			exit;
		}
		
		$this->uname = $Users->GetUserName();
		
		//-- perform the desired action
		switch($this->action) {
			case "addfav":
				$this->addFavorite();
				break;
			case "accept":
				$this->acceptChanges();
				break;
		}
		
		//-- check for the user
		//-- if the user can edit and there are changes then get the new changes
//		if ($this->show_changes=="yes" && userCanEdit($this->uname) && isset($gm_changes[$this->sid."_".$GEDCOM])) {
//			$newrec = FindGedcomRecord($this->sid);
//			$this->diffsource = new Source($newrec);
//			$sourcerec = $newrec;
//		}
		
		if ($this->source->canDisplayDetails()) {
			$this->canedit = $Users->userCanEdit($this->uname);
		}
		
		if ($this->show_changes=="yes" && $this->canedit) {
			$this->source->diffMerge($this->diffsource);
		}
		
		if ($this->source->canDisplayDetails() && ($Users->userCanViewGedlines() || $ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel() || !empty($this->uname))) {
			$this->display_other_menu = true;
		}
	}
	
	/**
	 * Add a new favorite for the action user
	 */
	function addFavorite() {
		global $GEDCOM;
		if (empty($this->uname)) return;
		if (!empty($_REQUEST["gid"])) {
			$gid = strtoupper($_REQUEST["gid"]);
			$indirec = FindSourceRecord($gid);
			if ($indirec) {
				$favorite = array();
				$favorite["username"] = $this->uname;
				$favorite["gid"] = $gid;
				$favorite["type"] = "SOUR";
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
		return $this->source->getTitle()." - ".$this->sid." - ".$gm_lang["source_info"];
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
		
		// edit source menu
		$menu = new Menu($gm_lang['edit_source']);

		if ($this->userCanEdit()) {
			// edit source / edit_raw
			if ($Users->userCanEditGedlines()) {
				$submenu = new Menu($gm_lang['edit_raw']);
				$submenu->addLink("edit_raw('".$this->sid."', 'edit_raw');");
				$menu->addSubmenu($submenu);
			}

			// edit source / delete_source
			$submenu = new Menu($gm_lang['delete_source']);
			$submenu->addLink("if (confirm('".$gm_lang["confirm_delete_source"]."'))  deletesource('".$this->sid."', 'delete_source'); ");
			$menu->addSubmenu($submenu);

			if (GetChangeData(true, $this->sid, true)) {
				// edit_sour / seperator
				$submenu = new Menu();
				$submenu->isSeperator();
				$menu->addSubmenu($submenu);

				// edit_sour / show/hide changes
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
				$submenu->addLink('clippings.php?action=add&id='.$this->sid.'&type=sour');
				$menu->addSubmenu($submenu);
		}
		if ($this->source->canDisplayDetails() && !empty($this->uname)) {
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites']);
				$submenu->addLink('source.php?action=addfav&sid='.$this->sid.'&gid='.$this->sid);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}
// -- end of class
//-- load a user extended class if one exists
if (file_exists('includes/controllers/source_ctrl_user.php'))
{
	include_once 'includes/controllers/source_ctrl_user.php';
}
else
{
	class SourceController extends SourceControllerRoot
	{
	}
}
?>
