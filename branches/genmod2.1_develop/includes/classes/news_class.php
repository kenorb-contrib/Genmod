<?php
/**
 * Class file for Genmod news
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
 * @version $Id: news_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class News {
	
	// General class information
	public $classname = "News";	// The name of this class
	
	// Data
	private $id = null;			// The ID of this item in the database
	private $username = null;	// The username or gedcomid 
	private $date = null;		// The date of the news 
	private $title = null;		// The title to display for this item
	private $text = null;		// The content of this news item
	
	private $anchor = null;		// Anchor for div identification
	private $isnew = null;		// To determine is this is a new item or one from the DB
	
	public function __construct($values="") {
		
		if (is_array($values)) {
			$this->id = $values["n_id"];
			$this->username = $values["n_username"];
			$this->date = $values["n_date"];
			$this->title = stripslashes($values["n_title"]);
			$this->text = stripslashes($values["n_text"]);
			$this->isnew = false;
			$this->anchor = "article".$values["n_id"];
		}
		else $this->isnew = true;
	}

	public function __get($property) {
		switch ($property) {
			case "id":
				return $this->id;
				break;
			case "username":
				return $this->username;
				break;
			case "title":
				return $this->title;
				break;
			case "text":
				return $this->text;
				break;
			case "date":
				return $this->date;
				break;
			case "anchor":
				return $this->anchor;
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function __set($property, $value) {
		switch ($property) {
			case "title":
				$this->title = $value;
				break;
			case "username":
				$this->username = $value;
				break;
			case "text":
				$this->text = $value;
				break;
			case "date":
				$this->date = $value;
				break;
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}
	
	public function addNews() {
	
		if (is_null($this->date)) $this->date = time()-$_SESSION["timediff"];
		if ($this->isnew == true) {
			$sql = "INSERT INTO ".TBLPREFIX."news VALUES ('', '".DbLayer::EscapeQuery($this->username)."','".DbLayer::EscapeQuery($this->date)."','".DbLayer::EscapeQuery($this->title)."','".DbLayer::EscapeQuery($this->text)."')";
		}
		else {
			$sql = "UPDATE ".TBLPREFIX."news SET n_date='".DbLayer::EscapeQuery($this->date)."', n_title='".DbLayer::EscapeQuery($this->title)."', n_text='".DbLayer::EscapeQuery($this->text)."' WHERE n_id='".$this->id."'";
		}
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
}
?>