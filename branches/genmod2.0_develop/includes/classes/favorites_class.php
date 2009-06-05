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

class Favorites {
	
	var $classname = "Favorites";
	var $favorites = array();
	
	
	/**
	 * deleteFavorite
	 * deletes a favorite in the database
	 * @param int $fv_id	the id of the favorite to delete
	 */
	public function DeleteFavorite($fv_id) {
		global $TBLPREFIX;
	
		$sql = "DELETE FROM ".$TBLPREFIX."favorites WHERE fv_id=".$fv_id;
		$res = NewQuery($sql);
		if ($res) return true;
		else {
			WriteToLog("Favorites->DeleteFavorite-> Error deleting favorite ".$fv_id, "E", "S");
			return false;
		}
	}
	
	/**
	 * Get a user's favorites
	 * Return an array of a users messages
	 * @param string $username		the username to get the favorites for
	 */
	public function getUserFavorites($username, $favid="") {
		global $TBLPREFIX, $GEDCOMS, $DBCONN, $CONFIGURED;
	
		$this->favorites = array();
		//-- make sure we don't try to look up favorites for unconfigured sites
		if (!$CONFIGURED) return $this->favorites;
		
		$sql = "SELECT * FROM ".$TBLPREFIX."favorites WHERE fv_username='".$DBCONN->EscapeQuery($username)."'";
		if ($favid != "") $sql .= " AND fv_id = '".$favid."'";
		$res = NewQuery($sql);
		if (!$res) return $this->favorites;
		while($row = $res->FetchAssoc()){
			if (isset($GEDCOMS[get_gedcom_from_id($row["fv_file"])])) {
				$this->favorites[] = new Favorite($row);
			}
		}
		$res->FreeResult();
		return $this->favorites;
	}
	
	/**
	 * Get the gedcom favorites
	 * Return an array of a users messages
	 * @param string $username		the username to get the favorites for
	 */
	public function getGedcomFavorites($gedid, $favid="") {
		global $TBLPREFIX, $GEDCOMS, $DBCONN, $CONFIGURED;
	
		$this->favorites = array();
		//-- make sure we don't try to look up favorites for unconfigured sites
		if (!$CONFIGURED) return $this->favorites;
		
		$sql = "SELECT * FROM ".$TBLPREFIX."favorites WHERE fv_file='".$gedid."' AND fv_username=''";
		if ($favid != "") $sql .= " AND fv_id = '".$favid."'";
		$res = NewQuery($sql);
		if (!$res) return $this->favorites;
		while($row = $res->FetchAssoc()){
			if (isset($GEDCOMS[get_gedcom_from_id($row["fv_file"])])) {
				$this->favorites[] = new Favorite($row);
			}
		}
		$res->FreeResult();
		return $this->favorites;
	}
}
?>