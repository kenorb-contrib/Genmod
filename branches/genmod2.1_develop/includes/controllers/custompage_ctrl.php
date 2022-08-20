<?php
/**
 * Controller for custom page
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
 * @version $Id: custompage_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
class CustomPageController extends BaseController {
	
	public $classname = "CustomPageController";	// Name of this class
	private $message = '';						// Message to show after a task is performed
	private $task = null;						// Task to perform
	private $pages = array();					// Holder for the pages array
	private $page_id = null;					// Page ID of the page that is being edited
	private $page = null;						// Holder for the single page
	
	public function __construct() {
		global $gm_user;
		
		parent::__construct();
		
		if (isset($_REQUEST["task"])) $this->task = $_REQUEST["task"];
		if (isset($_REQUEST["page_id"])) $this->page_id = $_REQUEST["page_id"];
		
		
		if ($this->action == "edit" && $gm_user->userIsAdmin()) {
		 	if ($this->task == GM_LANG_delete && !is_null($this->page_id)) {
				$page = new CustomPage($this->page_id);
				$page->Delete();
				$this->message = GM_LANG_page_deleted;
			}
			else if ($this->task == GM_LANG_edit || $this->task == GM_LANG_save) {
				if ($this->page_id == "newpage") {
					$this->page = new CustomPage();
				}
				else {
					$this->page = new CustomPage($this->page_id);
				}
			}
			if ($this->task == GM_LANG_save) {
				$this->page->title = $_REQUEST["title"];
				$this->page->content = $_REQUEST["html"];
				$this->page->Save();
			}
		}
		else {
			$this->page = new CustomPage($this->page_id);
		}
			
	}
	
	public function __get($property) {
		switch ($property) {
			case "pages":
				return $this->GetPages();
				break;
			case "page":
				return $this->page;
				break;
			case "page_id":
				return $this->page_id;
				break;
			case "task":
				return $this->task;
				break;
			case "message":
				return $this->message;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	protected function GetPageTitle() {
		
		if ($this->action == "edit") return GM_LANG_my_pages;
		else return $this->page->title;
	}		
	
	private function GetPages() {
		
		// Retrieve the current pages stored in the DB
		$sql = "SELECT * FROM ".TBLPREFIX."pages";
		$result = NewQuery($sql);
		while ($row = $result->FetchAssoc()) {
			$this->pages[$row["pag_id"]] = new CustomPage($row);
		}
		return $this->pages;
	}
	
	
	private function CheckAccess() {
		global $gm_user;
		if (!$gm_user->userIsAdmin()) return false;
		else return true;
	}
}
?>
