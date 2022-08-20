<?php
/**
 * Controller file for favorites
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
 * @version $Id: favorites_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class FavoritesController {
	
	public $classname = "FavoritesController";	// Name of this class
	
	
	/**
	 * deleteFavorite
	 * deletes a favorite in the database
	 * @param int $fv_id	the id of the favorite to delete
	 */
	public function DeleteFavorite($fv_id) {
	
		$sql = "DELETE FROM ".TBLPREFIX."favorites WHERE fv_id=".$fv_id;
		$res = NewQuery($sql);
		if ($res) return true;
		else {
			WriteToLog("FavoritesController-&gt;DeleteFavorite-> Error deleting favorite ".$fv_id, "E", "S");
			return false;
		}
	}
	
	/**
	 * Get a user's favorites
	 * Return an array of a users messages
	 * @param string $username		the username to get the favorites for
	 */
	public function getUserFavorites($username, $favid="") {
		global $GEDCOMS;
	
		$favorites = array();
		//-- make sure we don't try to look up favorites for unconfigured sites
		if (!CONFIGURED) return $favorites;
		
		$sql = "SELECT * FROM ".TBLPREFIX."favorites WHERE fv_username='".DbLayer::EscapeQuery($username)."'";
		if ($favid != "") $sql .= " AND fv_id = '".$favid."'";
		$res = NewQuery($sql);
		if (!$res) return $favorites;
		while($row = $res->FetchAssoc()){
			if (isset($GEDCOMS[$row["fv_file"]])) {
				$favorites[] = new Favorite($row);
			}
		}
		$res->FreeResult();
		return $favorites;
	}

	public function IsUserFav($xref, $type, $gedid) {
		global $gm_user;

		if (!CONFIGURED) return false;
		
		$sql = "SELECT count(fv_id) as count FROM ".TBLPREFIX."favorites WHERE fv_username='".DbLayer::EscapeQuery($gm_user->username)."' AND fv_gid='".$xref."' AND fv_type='".$type."' AND fv_file='".$gedid."'";
		$res = NewQuery($sql);
		if (!$res) return false;
		while($row = $res->FetchAssoc()){
			return $row["count"];
		}
	}
			
	/**
	 * Get the gedcom favorites
	 * Return an array of a users messages
	 * @param string $username		the username to get the favorites for
	 */
	public function getGedcomFavorites($gedid, $favid="") {
		global $GEDCOMS;
	
		$favorites = array();
		//-- make sure we don't try to look up favorites for unconfigured sites
		if (!CONFIGURED) return $favorites;
		
		$sql = "SELECT * FROM ".TBLPREFIX."favorites WHERE fv_file='".$gedid."' AND fv_username=''";
		if ($favid != "") $sql .= " AND fv_id = '".$favid."'";
		$res = NewQuery($sql);
		if (!$res) return $favorites;
		while($row = $res->FetchAssoc()){
			if (isset($GEDCOMS[$row["fv_file"]])) {
				$favorites[] = new Favorite($row);
			}
		}
		$res->FreeResult();
		return $favorites;
	}
}
?>