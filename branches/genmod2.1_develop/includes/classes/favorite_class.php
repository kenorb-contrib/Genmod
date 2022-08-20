<?php
/**
 * Class file for favorites
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
 * @version $Id: favorite_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Favorite {
	
	public $classname = "Favorite";	// Name of this class
	public $id = "0";				// ID of the favorite in the DB
	public $username = null;		// Username of the user having this favorite or blank if a gedcom favorite
	public $gid = null;				// xref of the favorite object
	public $type = null;			// Type of the favorite object (INDI, FAM, SOUR, REPO, NOTE, OBJ)
	public $file = null;			// Gedcom ID in which this favorite exists
	private $title = null;			// Title (descriptive) if the favorite is an URL
	public $note = null;			// Remarks, note to the favorite
	public $url = null;				// If URL, the URL, else blank.
	private $link = null;			// Quick, short URL to the favorite
	public $object = null;			// Holder of the object to which this favorite references
	
	public function __construct($favdata="") {
		
		if (is_array($favdata)) {
			$this->id = $favdata["fv_id"];
			$this->username = $favdata["fv_username"];
			$this->gid = $favdata["fv_gid"];
			$this->type = $favdata["fv_type"];
			$this->file = $favdata["fv_file"];
			$this->title = $favdata["fv_title"];
			$this->note = $favdata["fv_note"];
			$this->url = $favdata["fv_url"];
		}
	}

	public function __get($property) {
		switch($property) {
			
			case "title":
				return $this->GetTitle();
				break;
			case "link":
				return $this->GetLink();
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function __set($property, $value) {
		switch($property) {
			
			case "title":
				$this->title = $value;
				break;
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}
	
	public function GetObject() {
		
		if (is_null($this->object)) {
			$this->object =& ConstructObject($this->gid, $this->type, $this->file);
		}
	}

	private function GetTitle() {
		
		if (is_null($this->title) || $this->title == "") {
			SwitchGedcom($this->file);
			$this->GetObject();
			if (is_object($this->object)) $this->title = $this->object->title.$this->object->addxref;
			else $this->title = "";
			SwitchGedcom();
		}
		return $this->title;
	}
				
	private function GetLink() {
		
		if (is_null($this->link)) {
			SwitchGedcom($this->file);
			$this->GetObject();
			if ($this->url != "") $this->link = $this->url;
			if ($this->type == "INDI") $this->link = "individual.php?pid=".$this->gid."&gedid=".$this->file;
			elseif ($this->type == "FAM") $this->link = "family.php?famid=".$this->gid."&gedid=".$this->file;
			elseif ($this->type == "SOUR") $this->link = "source.php?sid=".$this->gid."&gedid=".$this->file;
			elseif ($this->type == "REPO") $this->link = "repo.php?rid=".$this->gid."&gedid=".$this->file;
			elseif ($this->type == "OBJE") $this->link = "mediadetail.php?mid=".$this->gid."&gedid=".$this->file;
			elseif ($this->type == "NOTE") $this->link = "note.php?oid=".$this->gid."&gedid=".$this->file;
			SwitchGedcom();
		}
		return $this->link;
	}
	
	public function SetFavorite() {
		
		// -- make sure something favorite is added
		if (empty($this->gid) && empty($this->url)) return false;
		
		if ($this->id != "0") {
			// NOTE: make sure a favorite is added
			
			// NOTE: Construct the query
			$sql = "UPDATE ".TBLPREFIX."favorites SET fv_url = '".DbLayer::EscapeQuery($this->url)."'";
			$sql .= ", fv_note = '".DbLayer::EscapeQuery($this->note)."'";
			$sql .= ", fv_title = '".DbLayer::EscapeQuery($this->title)."' ";
			$sql .= "WHERE fv_id = ".$this->id;
			
			$res = NewQuery($sql);
			if ($res) return true;
			else {
				WriteToLog("Favorite-&gt;SetFavorite-&gt; Error editing favorite ".$this->gid, "E", "S");
				return false;
			}
		}
		else {
	
			//-- make sure this is not a duplicate entry
			$sql = "SELECT * FROM ".TBLPREFIX."favorites WHERE ";
			if (!empty($this->gid)) $sql .= "fv_gid='".DbLayer::EscapeQuery($this->gid)."' ";
			if (!empty($this->url)) $sql .= "fv_url='".DbLayer::EscapeQuery($this->url)."' ";
			$sql .= "AND fv_file='".DbLayer::EscapeQuery($this->file)."' AND fv_username='".DbLayer::EscapeQuery($this->username)."'";
			$res = NewQuery($sql);
			if ($res->NumRows()>0) return false;
			
			//-- get the next favorite id number for the primary key
			//-- add the favorite to the database
			$sql = "INSERT INTO ".TBLPREFIX."favorites VALUES ('0', '".DbLayer::EscapeQuery($this->username)."'," .
					"'".DbLayer::EscapeQuery($this->gid)."','".DbLayer::EscapeQuery($this->type)."'," .
					"'".DbLayer::EscapeQuery($this->file)."'," .
					"'".DbLayer::EscapeQuery($this->url)."'," .
					"'".DbLayer::EscapeQuery($this->title)."'," .
					"'".DbLayer::EscapeQuery($this->note)."')";
			$res = NewQuery($sql);
			if ($res) return true;
			else {
				WriteToLog("Favorite-&gt;AddFavorite-&gt; Error adding favorite ".$this->gid, "E", "S");
				return false;
			}
		}
	}
	
}
?>