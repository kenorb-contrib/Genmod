<?php
/**
 * Controller for the media page view
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
 * @version $Id: media_ctrl.php,v 1.8 2009/03/16 19:51:12 sjouke Exp $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the media page.
 */
class MediaControllerRoot extends BaseController {
	var $classname = "MediaControllerRoot";
	var $mid;
	var $show_changes = "yes";
	var $action = "";
	var $media = null;
	var $uname = "";
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
		if (!empty($_REQUEST["mid"])) $this->mid = strtoupper($_REQUEST["mid"]);
		$this->mid = CleanInput($this->mid);
		
		$mediarec = FindMediaRecord($this->mid);
		if (!$mediarec) {
			if (!GetChangeData(true, $this->mid, true, "", "")) $this->isempty = true;
			$mediarec = "0 @".$this->mid."@ OBJE\r\n";
		}

		if ($this->show_changes) $this->media = new MediaItem($this->mid, true);
		else $this->media = new MediaItem($this->mid);
		
		$this->uname = $Users->GetUserName();
		
		//-- perform the desired action
		switch($this->action) {
			case "addfav":
				$this->addFavorite();
				break;
			}
		
		
		if ($this->media->canDisplayDetails()) {
			$this->canedit = $Users->userCanEdit($this->uname);
		}
		
		if ($this->media->canDisplayDetails() && ($Users->userCanViewGedlines() || $ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel() || !empty($this->uname))) {
			$this->display_other_menu = true;
		}
	}
	
	/**
	 * Add a new favorite for the action user
	 */
	function addFavorite() {
		global $GEDCOM;
		if (empty($this->uname)) return;
		if (!empty($_REQUEST["mid"])) {
			$mid = strtoupper($_REQUEST["mid"]);
			$mediarec = FindMediaRecord($mid);
			if ($mediarec) {
				$favorite = array();
				$favorite["username"] = $this->uname;
				$favorite["type"] = "OBJE";
				$favorite["gid"] = $mid;
				$favorite["file"] = $GEDCOM;
				$favorite["url"] = "";
				$favorite["title"] = $this->media->m_titl;
				$favorite["note"] = "";
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
		return $this->media->getTitle()." - ".$this->mid." - ".$gm_lang["media_info"];
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
		
		// edit media menu
		$menu = new Menu($gm_lang['edit_media']);

		if ($this->userCanEdit()) {
			// edit media / edit_raw
			if ($Users->userCanEditGedlines()) {
				$submenu = new Menu($gm_lang['edit_raw']);
				$submenu->addLink("edit_raw('".$this->mid."', 'edit_raw');");
				$menu->addSubmenu($submenu);
			}

			// edit media / delete_media
			$submenu = new Menu($gm_lang['delete_media']);
			$submenu->addLink("if (confirm('".$gm_lang["confirm_delete_media"]."'))  deletemedia('".$this->mid."', 'delete_media'); ");
			$menu->addSubmenu($submenu);

			if (GetChangeData(true, $this->mid, true)) {
				// edit_sour / seperator
				$submenu = new Menu();
				$submenu->isSeperator();
				$menu->addSubmenu($submenu);

				// edit_sour / show/hide changes
				if ($show_changes == "no") $submenu = new Menu($gm_lang['show_changes']);
				else $submenu = new Menu($gm_lang['hide_changes']);
				$submenu->addLink('showchanges();');
				$menu->addSubmenu($submenu);

				if ($Users->userCanAccept($this->uname)) {
					// edit_media / accept_all
					$submenu = new Menu($gm_lang['accept_all']);
					$submenu->addLink('mediadetail.php?mid='.$this->mid.'&action=accept');
					$menu->addSubmenu($submenu);
				}
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
				$submenu->addLink('clippings.php?action=add&id='.$this->mid.'&type=sour');
				$menu->addSubmenu($submenu);
		}
		if ($this->media->canDisplayDetails() && !empty($this->uname)) {
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites']);
				$submenu->addLink('mediadetail.php?action=addfav&mid='.$this->mid.'&gid='.$this->mid);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}
// -- end of class
//-- load a user extended class if one exists
if (file_exists('includes/controllers/media_ctrl_user.php'))
{
	include_once 'includes/controllers/media_ctrl_user.php';
}
else
{
	class mediaController extends mediaControllerRoot
	{
	}
}
?>
