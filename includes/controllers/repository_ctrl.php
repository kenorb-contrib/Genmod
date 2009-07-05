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
 * @version $Id$
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
	
	private $display_other_menu = false;
	private $pagetitle = null;
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $GEDCOMID, $ENABLE_CLIPPINGS_CART;
		global $Users, $nonfacts;
		
		parent::__construct();

		$nonfacts = array();
		
		if (!empty($_REQUEST["action"])) $this->action = $_REQUEST["action"];
		if (!empty($_REQUEST["rid"])) $this->xref = strtoupper($_REQUEST["rid"]);
		$this->xref = CleanInput($this->xref);
		$this->gedcomid = $GEDCOMID;
		
		$reporec = FindRepoRecord($this->xref);
		
		$this->repo = new Repository($this->xref, $reporec);
				
		if ($this->repo->disp && ($Users->userCanViewGedlines() || $ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel() || !empty($this->uname))) {
			$this->display_other_menu = true;
		}
	}
	
	public function __get($property) {
		switch($property) {
			case "pagetitle":
				return $this->GetPageTitle();
				break;
			case "display_other_menu":
				return $this->display_other_menu;
				break;
			default:
				parent::__get($property);
		}
	}
	/**
	 * Add a new favorite for the action user
	 */
	protected function addFavorite() {
		global $GEDCOMID;
		
		if (empty($this->uname)) return;

		if (!$this->repo->isempty && !$this->repo->isdeleted) {	
			$favorite = new Favorite();
			$favorite->username = $this->uname;
			$favorite->gid = $this->repo->xref;
			$favorite->type = 'REPO';
			$favorite->file = $GEDCOMID;
			$favorite->SetFavorite();
		}
	}
	
	/**
	 * get the title for this page
	 * @return string
	 */
	private function getPageTitle() {
		global $gm_lang;

		if ($this->repo->title) $this->pagetitle = $this->repo->title." - ".$this->repo->xref." - ".$gm_lang["repo_info"];
		else $this->pagetitle =  $this->repo->xref." - ".$gm_lang["repo_info"];
		return $this->pagetitle;
	}
	
	/**
	 * get edit menut
	 * @return Menu
	 */
	public function &getEditMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $Users;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// edit repo menu
		$menu = new Menu($gm_lang['edit_repo']);

		if ($this->repo->canedit) {
			// edit repo / edit_raw
			if ($Users->userCanEditGedlines()) {
				$submenu = new Menu($gm_lang['edit_raw']);
				$submenu->addLink("edit_raw('".$this->repo->xref."', 'edit_raw');");
				$menu->addSubmenu($submenu);
			}

			// edit repo / delete_repo
			$submenu = new Menu($gm_lang['delete_repo']);
			$submenu->addLink("if (confirm('".$gm_lang["confirm_delete_repo"]."'))  deleterepository('".$this->repo->xref."', 'delete_repository'); ");
			$menu->addSubmenu($submenu);

			if ($this->repo->ischanged) {
				// edit_repo / seperator
				$submenu = new Menu();
				$submenu->isSeperator();
				$menu->addSubmenu($submenu);

				// edit_repo / show/hide changes
				if (!$this->repo->show_changes) $submenu = new Menu($gm_lang['show_changes']);
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
	public function &getOtherMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $GEDCOMID, $gm_lang;
		global $ENABLE_CLIPPINGS_CART, $Users;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// other menu
		$menu = new Menu($gm_lang['other']);
		if ($Users->userCanViewGedlines()) {
				// other / view_gedcom
				if ($this->repo->show_changes && $this->repo->canedit) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu($gm_lang['view_gedcom']);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu($gm_lang['add_to_cart']);
				$submenu->addLink('clippings.php?action=add&id='.$this->repo->xref.'&type=repo');
				$menu->addSubmenu($submenu);
		}
		if ($this->repo->disp && !empty($this->uname)) {
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites']);
				$submenu->addLink('repo.php?action=addfav&sid='.$this->repo->xref.'&gedid='.$GEDCOMID);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}
?>
