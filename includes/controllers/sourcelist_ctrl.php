<?php
/**
 * Controller for the sourcelist
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
 * @version $Id: sourcelist_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the repository page.
 */
class SourceListController extends ListController {
	
	public $classname = "SourceListController";		// Name of this class
	private $sourcelist = null;						// Container for the sourcelist
	private $addsourcelist = null;					// Container for the sourcelist with addnames
	private $sour_total = null;						// Total number of source records
	private $sour_add = null;						// Total number of source records with an additional name
	private $sour_hide = null;						// Number of hidden source records
	
	public function __construct() {
		
		parent::__construct();

		
	}
	
	public function __get($property) {
		switch($property) {
			case "sourcelist":
				return $this->GetSourceList();
				break;
			case "addsourcelist":
				return $this->GetAddSourceList();
				break;
			case "sour_total":
				if (is_null($this->sour_total)) $this->GetSourceList();
				return $this->sour_total;
				break;
			case "sour_add":
				if (is_null($this->sour_add)) $this->GetAddSourceList();
				return $this->sour_add;
				break;
			case "sour_hide":
				if (is_null($this->sour_hide)) $this->GetSourceList();
				return $this->sour_hide;
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
			$this->pagetitle = GM_LANG_source_list;
		}
		return $this->pagetitle;
	}
	
	private function GetSourceList() {
		
		if (is_null($this->sourcelist)) {
			$this->sourcelist =& ListFunctions::GetSourceList("", true);
			uasort($this->sourcelist, "SourceDescrSort"); 
			$this->sour_total = count(ListFunctions::$sour_total);
			$this->sour_hide =  count(ListFunctions::$sour_hide);
		}
		return $this->sourcelist;
	}
	
	private function GetAddSourceList() {

		if (is_null($this->addsourcelist)) {
			$this->addsourcelist = array();
			$this->sour_add = 0;
			if (is_null($this->sourcelist)) {
				$this->GetSourceList();
			}
			foreach ($this->sourcelist as $key => $source) {
				if ($source->adddescriptor != "") $this->addsourcelist[] = $source;
			}
			$this->sour_add = count($this->addsourcelist);
			uasort($this->addsourcelist, "SourceAddDescrSort"); 
		}
		return $this->addsourcelist;
	}
}
?>
