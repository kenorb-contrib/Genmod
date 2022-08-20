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
 * @version $Id: source_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],"source_ctrl")) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the source page.
 */
class SourceController extends DetailController {
	
	public $classname = "SourceController";	// Name of this class
	public $source = null;					// Holder for the source object
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $ENABLE_CLIPPINGS_CART, $nonfacts;
		
		parent::__construct();

		$nonfacts = array();
		
		if (!empty($_REQUEST["sid"])) $this->xref = strtoupper($_REQUEST["sid"]);
		$this->xref = CleanInput($this->xref);
		
		$this->source =& Source::GetInstance($this->xref);
		
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
				break;
		}
	}
	
	/**
	 * get the title for this page
	 * @return string
	 */
	protected function getPageTitle() {

		if (is_null($this->pagetitle)) {
			$this->pagetitle = "";
			if ($this->source->title) {
				$this->pagetitle .= $this->source->title.$this->source->addxref." - ";
			}
			$this->pagetitle .= GM_LANG_source_info;
		}
		return $this->pagetitle;
	}
	
	/**
	 * get edit menut
	 * @return Menu
	 */
	public function &getEditMenu() {
		global $gm_user;
		
		// edit source menu
		$menu = new Menu(GM_LANG_edit_source);
		if (!$this->source->isdeleted) {
			// edit source / edit_raw
			if ($gm_user->userCanEditGedlines()) {
				$submenu = new Menu(GM_LANG_edit_raw);
				$submenu->addLink("edit_raw('".$this->source->xref."', 'edit_raw', 'SOUR');");
				$menu->addSubmenu($submenu);
			}

			// edit source / delete_source
			$submenu = new Menu(GM_LANG_delete_source);
			$submenu->addLink("if (confirm('".GM_LANG_confirm_delete_source."'))  deletesource('".$this->source->xref."', 'delete_source'); ");
			$menu->addSubmenu($submenu);
		}
		if ($this->source->ischanged) {
			// edit_sour / seperator
			$submenu = new Menu();
			$submenu->isSeperator();
			$menu->addSubmenu($submenu);

			// edit_sour / show/hide changes
			if (!$this->source->show_changes) $submenu = new Menu(GM_LANG_show_changes);
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
				if ($this->source->show_changes && $this->source->canedit) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu(GM_LANG_view_gedcom);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $gm_user->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu(GM_LANG_add_to_cart);
				$submenu->addLink('clippings.php?action=add&id='.$this->source->xref.'&type=sour');
				$menu->addSubmenu($submenu);
		}
		if ($this->source->disp && !empty($this->uname) && !$this->source->isuserfav) {
				// other / add_to_my_favorites
				$submenu = new Menu(GM_LANG_add_to_my_favorites);
				$submenu->addLink('source.php?action=addfav&sid='.$this->source->xref.'&gedid='.GedcomConfig::$GEDCOMID);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}
?>
