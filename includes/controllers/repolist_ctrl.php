<?php
/**
 * Controller for the repositorylist
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
 * @version $Id: repolist_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the repository page.
 */
class RepoListController extends ListController {
	
	public $classname = "RepoListController";		// Name of this class
	private $repolist = null;						// Container for the repolist
	private $addrepolist = null;					// Container for the repolist with addnames
	private $repo_total = null;						// Total number of repo records
	private $repo_add = null;						// Number of repos with additional name
	private $repo_hide = null;						// Number of hidden repo records
	
	public function __construct() {
		
		parent::__construct();

		
	}
	
	public function __get($property) {
		switch($property) {
			case "repolist":
				return $this->GetRepoList();
				break;
			case "addrepolist":
				return $this->GetAddRepoList();
				break;
			case "repo_total":
				if (is_null($this->repo_total)) $this->GetRepoList();
				return $this->repo_total;
				break;
			case "repo_add":
				if (is_null($this->repo_add)) $this->GetAddRepoList();
				return $this->repo_add;
				break;
			case "repo_hide":
				if (is_null($this->repo_hide)) $this->GetRepoList();
				return $this->repo_hide;
				break;
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
			$this->pagetitle = GM_LANG_repo_list;
		}
		return $this->pagetitle;
	}
	
	private function GetRepoList() {
		
		if (is_null($this->repolist)) {
			$this->repolist =& ListFunctions::GetRepoList("", true);
			uasort($this->repolist, "SourceDescrSort"); 
			$this->repo_total = count(ListFunctions::$repo_total);
			$this->repo_hide =  count(ListFunctions::$repo_hide);
		}
		return $this->repolist;
	}
	
	private function GetAddRepoList() {

		if (is_null($this->addrepolist)) {
			$this->addrepolist = array();
			$this->repo_add = 0;
			if (is_null($this->repolist)) {
				$this->GetRepoList();
			}
			foreach ($this->repolist as $key => $repo) {
				if ($repo->adddescriptor != "") $this->addrepolist[] = $repo;
			}
			$this->repo_add = count($this->addrepolist);
			uasort($this->addrepolist, "SourceAddDescrSort"); 
		}
		return $this->addrepolist;
	}
	
}
?>
