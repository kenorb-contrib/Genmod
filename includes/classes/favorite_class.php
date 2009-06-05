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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Favorite extends Favorites {
	
	var $classname = "Favorite";
	var $id = "0";
	var $username = "";
	var $gid = "";
	var $type = "";
	var $file = "";
	var $title = "";
	var $note = "";
	var $url = "";
	
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
	
	public function SetFavorite() {
		global $TBLPREFIX, $DBCONN;
		
		// -- make sure something favorite is added
		if (empty($this->gid) && empty($this->url)) return false;
		
		if ($this->id != "0") {
			// NOTE: make sure a favorite is added
			
			// NOTE: Construct the query
			$sql = "UPDATE ".$TBLPREFIX."favorites SET fv_url = '".$this->url."'";
			$sql .= ", fv_note = '".$this->note."'";
			$sql .= ", fv_title = '".$this->title."' ";
			$sql .= "WHERE fv_id = ".$this->id;
			
			$res = NewQuery($sql);
			if ($res)  return true;
			else {
				WriteToLog("Favorite->SetFavorite-> Error editing favorite ".$this->gid, "E", "S");
				return false;
			}
		}
		else {
	
			//-- make sure this is not a duplicate entry
			$sql = "SELECT * FROM ".$TBLPREFIX."favorites WHERE ";
			if (!empty($this->gid)) $sql .= "fv_gid='".$DBCONN->EscapeQuery($this->gid)."' ";
			if (!empty($this->url)) $sql .= "fv_url='".$DBCONN->EscapeQuery($this->url)."' ";
			$sql .= "AND fv_file='".$DBCONN->EscapeQuery($this->file)."' AND fv_username='".$DBCONN->EscapeQuery($this->username)."'";
			$res = NewQuery($sql);
			if ($res->NumRows()>0) return false;
			
			//-- get the next favorite id number for the primary key
			//-- add the favorite to the database
			$sql = "INSERT INTO ".$TBLPREFIX."favorites VALUES ('0', '".$DBCONN->EscapeQuery($this->username)."'," .
					"'".$DBCONN->EscapeQuery($this->gid)."','".$DBCONN->EscapeQuery($this->type)."'," .
					"'".$DBCONN->EscapeQuery($this->file)."'," .
					"'".$DBCONN->EscapeQuery($this->url)."'," .
					"'".$DBCONN->EscapeQuery($this->title)."'," .
					"'".$DBCONN->EscapeQuery($this->note)."')";
			$res = NewQuery($sql);
			if ($res) return true;
			else {
				WriteToLog("Favorite->AddFavorite-> Error adding favorite ".$this->gid, "E", "S");
				return false;
			}
		}
	}
	
}
			

?>