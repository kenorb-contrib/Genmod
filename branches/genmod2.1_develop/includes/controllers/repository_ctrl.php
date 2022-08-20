<?php
/**
 * Controller for the repository page view
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
 * @version $Id: repository_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],"repository_ctrl")) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the repository page.
 */
class RepositoryController extends DetailController {
	
	public $classname = "RepositoryController";
	public $repo = null;
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $nonfacts;
		
		parent::__construct();

		$nonfacts = array();
		
		if (!empty($_REQUEST["rid"])) $this->xref = strtoupper($_REQUEST["rid"]);
		$this->xref = CleanInput($this->xref);
		$this->gedcomid = GedcomConfig::$GEDCOMID;
		
		$this->repo =& Repository::GetInstance($this->xref, "", GedcomConfig::$GEDCOMID);
		
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
			if ($this->repo->title) {
				$this->pagetitle .= $this->repo->title.$this->repo->addxref." - ";
			}
			$this->pagetitle .= GM_LANG_repo_info;
		}
		return $this->pagetitle;
	}
	
	/**
	 * get edit menut
	 * @return Menu
	 */
	public function &getEditMenu() {
		global $gm_user;
		
		// edit repo menu
		$menu = new Menu(GM_LANG_edit_repo);

		if (!$this->repo->isdeleted) {
			// edit repo / edit_raw
			if ($gm_user->userCanEditGedlines()) {
				$submenu = new Menu(GM_LANG_edit_raw);
				$submenu->addLink("edit_raw('".$this->repo->xref."', 'edit_raw', 'REPO');");
				$menu->addSubmenu($submenu);
			}

			// edit repo / delete_repo
			$submenu = new Menu(GM_LANG_delete_repo);
			$submenu->addLink("if (confirm('".GM_LANG_confirm_delete_repo."'))  deleterepository('".$this->repo->xref."', 'delete_repository'); ");
			$menu->addSubmenu($submenu);

		}
		if ($this->repo->ischanged) {
			// edit_repo / seperator
			$submenu = new Menu();
			$submenu->isSeperator();
			$menu->addSubmenu($submenu);

			// edit_repo / show/hide changes
			if (!$this->repo->show_changes) $submenu = new Menu(GM_LANG_show_changes);
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
				if ($this->repo->show_changes && $this->repo->canedit) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu(GM_LANG_view_gedcom);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $gm_user->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu(GM_LANG_add_to_cart);
				$submenu->addLink('clippings.php?action=add&id='.$this->repo->xref.'&type=repo');
				$menu->addSubmenu($submenu);
		}
		if ($this->repo->disp && !empty($this->uname) && !$this->repo->isuserfav) {
				// other / add_to_my_favorites
				$submenu = new Menu(GM_LANG_add_to_my_favorites);
				$submenu->addLink('repo.php?action=addfav&rid='.$this->repo->xref.'&gedid='.GedcomConfig::$GEDCOMID);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}
?>
