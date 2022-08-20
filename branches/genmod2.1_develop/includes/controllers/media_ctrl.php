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
 * @subpackage Controllers
 * @version $Id: media_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the media page.
 */
class MediaController extends DetailController {
	
	public $classname = "MediaController";	// Name of this class
	public $media = null;					// Object it's controlling
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $nonfacts;
		
		parent::__construct();
		
		$nonfacts = array();
				
		if (!empty($_REQUEST["mid"])) $this->xref = strtoupper($_REQUEST["mid"]);
		$this->xref = CleanInput($this->xref);
		
		$this->media =& MediaItem::GetInstance($this->xref);
		
		//-- perform the desired action
		switch($this->action) {
			case "addfav":
				$this->addFavorite();
				break;
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
			$this->pagetitle .= $this->media->title.$this->media->addxref;
			$this->pagetitle .= " - ".GM_LANG_media_info;
		}
		return $this->pagetitle;
	}
	
	/**
	 * get edit menut
	 * @return Menu
	 */
	public function &getEditMenu() {
		global $TEXT_DIRECTION, $gm_user;
		
		// edit media menu
		$menu = new Menu(GM_LANG_edit_media);

		if (!$this->media->isdeleted) {
			// edit media / edit_raw
			if ($gm_user->userCanEditGedlines()) {
				$submenu = new Menu(GM_LANG_edit_raw);
				$submenu->addLink("edit_raw('".$this->media->xref."', 'edit_raw', 'OBJE');");
				$menu->addSubmenu($submenu);
			}

			// edit media / delete_media
			$submenu = new Menu(GM_LANG_delete_media);
			$submenu->addLink("if (confirm('".GM_LANG_confirm_delete_media."'))  deletemedia('".$this->media->xref."', 'delete_media'); ");
			$menu->addSubmenu($submenu);

		}
		if ($this->media->ischanged) {
			// edit_sour / seperator
			$submenu = new Menu();
			$submenu->isSeperator();
			$menu->addSubmenu($submenu);

			// edit_sour / show/hide changes
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
		global $TEXT_DIRECTION;
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
				$submenu->addLink('clippings.php?action=add&id='.$this->media->xref.'&type=sour');
				$menu->addSubmenu($submenu);
		}
		if ($this->media->disp && !empty($this->uname) && !$this->media->isuserfav) {
				// other / add_to_my_favorites
				$submenu = new Menu(GM_LANG_add_to_my_favorites);
				$submenu->addLink('mediadetail.php?action=addfav&mid='.$this->media->xref.'&gedid='.$this->media->gedcomid);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}
// -- end of class
?>
