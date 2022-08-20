<?php
/**
 * Class file for custom pages
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
 * @subpackage DataModel
 * @version $Id: custompage_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class CustomPage {
	
	// General class information
	public $classname = "CustomPage";	// The name of this class
	
	// Data
	private $id = null;					// Unique ID for this page
	private $content = null;			// Content of the page
	private $title = null;				// Title of the page
	
	public function __construct($values="") {
		
		if (is_array($values)) {
			$this->id = $values["pag_id"];
			$this->content = stripslashes($values["pag_content"]);
			$this->title = stripslashes($values["pag_title"]);
		}
		else if(!empty($values)) $this->ReadPage($values);
	}

	public function __get($property) {
		switch ($property) {
			case "id":
				return $this->id;
				break;
			case "content":
				return $this->content;
				break;
			case "title":
				return $this->title;
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function __set($property, $value) {
		switch ($property) {
			case "content":
				$this->content = $value;
				break;
			case "title":
				$this->title = $value;
				break;
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}
	
	private function ReadPage($id) {
		// Retrieve the page to be shown
		$sql = "SELECT * FROM ".TBLPREFIX."pages WHERE `pag_id` = '".$id."'";
		$result = NewQuery($sql);
		while ($row = $result->FetchAssoc()) {
			$this->id = $row["pag_id"];
			$this->content = stripslashes($row["pag_content"]);
			$this->title = stripslashes($row["pag_title"]);
		}
	}

	public function Delete() {
		
		$sql = "DELETE FROM ".TBLPREFIX."pages WHERE `pag_id` = '".$this->id."'";
		$result = NewQuery($sql);
	}

	public function Save() {
		
		if (is_null($this->id)) {
			$sql = "INSERT INTO ".TBLPREFIX."pages (`pag_content`, `pag_title`) VALUES ('".DbLayer::EscapeQuery($this->content)."', '".DbLayer::EscapeQuery($this->title)."')";
				$result = NewQuery($sql);
			}
		else {
			$sql = "UPDATE ".TBLPREFIX."pages SET `pag_content` = '".DbLayer::EscapeQuery($this->content)."', `pag_title` = '".DbLayer::EscapeQuery($this->title)."' WHERE `pag_id` = '".$this->id."'";
			$result = NewQuery($sql);
		}
	}	
	
}
?>