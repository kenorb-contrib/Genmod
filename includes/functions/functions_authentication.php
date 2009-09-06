<?php
/**
 * MySQL User and Authentication functions
 *
 * This file contains the MySQL specific functions for working with users and authenticating them.
 * It also handles the internal mail messages, favorites, news/journal, and storage of MyGedView
 * customizations.  Assumes that a database connection has already been established.
 *
 * You can extend Genmod to work with other systems by implementing the functions in this file.
 * Other possible options are to use LDAP for authentication.
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
 * @package Genmod
 * @subpackage DB
 * @version $Id$
 */
if (strstr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Adds a news item to the database
 *
 * This function adds a news item represented by the $news array to the database.
 * If the $news array has an ["id"] field then the function assumes that it is
 * as update of an older news item.
 *
 * @author Genmod Development Team
 * @param array $news a news item array
 */
function addNews($news) {

	if (!isset($news["date"])) $news["date"] = time()-$_SESSION["timediff"];
	if (!empty($news["id"])) {
		// In case news items are added from usermigrate, it will also contain an ID.
		// So we check first if the ID exists in the database. If not, insert instead of update.
		$sql = "SELECT * FROM ".TBLPREFIX."news where n_id=".$news["id"];
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) {
			$sql = "INSERT INTO ".TBLPREFIX."news VALUES (".$news["id"].", '".DbLayer::EscapeQuery($news["username"])."','".DbLayer::EscapeQuery($news["date"])."','".DbLayer::EscapeQuery($news["title"])."','".DbLayer::EscapeQuery($news["text"])."')";
		}
		else {
			$sql = "UPDATE ".TBLPREFIX."news SET n_date='".DbLayer::EscapeQuery($news["date"])."', n_title='".DbLayer::EscapeQuery($news["title"])."', n_text='".DbLayer::EscapeQuery($news["text"])."' WHERE n_id=".$news["id"];
		}
		$res->FreeResult();
	}
	else {
		$newid = GetNextId("news", "n_id");
		$sql = "INSERT INTO ".TBLPREFIX."news VALUES ($newid, '".DbLayer::EscapeQuery($news["username"])."','".DbLayer::EscapeQuery($news["date"])."','".DbLayer::EscapeQuery($news["title"])."','".DbLayer::EscapeQuery($news["text"])."')";
	}
	$res = NewQuery($sql);
	if ($res) return true;
	else return false;
}

/**
 * Deletes a news item from the database
 *
 * @author Genmod Development Team
 * @param int $news_id the id number of the news item to delete
 */
function deleteNews($news_id) {

	$sql = "DELETE FROM ".TBLPREFIX."news WHERE n_id=".$news_id;
	$res = NewQuery($sql);
	if ($res) return true;
	else return false;
}

/**
 * Gets the news items for the given user or gedcom
 *
 * @param String $username the username or gedcom file name to get news items for
 */
function getUserNews($username) {

	$news = array();
	$sql = "SELECT * FROM ".TBLPREFIX."news WHERE n_username='".DbLayer::EscapeQuery($username)."' ORDER BY n_date DESC";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$row = db_cleanup($row);
		$n = array();
		$n["id"] = $row["n_id"];
		$n["username"] = $row["n_username"];
		$n["date"] = $row["n_date"];
		$n["title"] = stripslashes($row["n_title"]);
		$n["text"] = stripslashes($row["n_text"]);
		$n["anchor"] = "article".$row["n_id"];
		$news[$row["n_id"]] = $n;
	}
	$res->FreeResult();
	return $news;
}

/**
 * Gets the news item for the given news id
 *
 * @param int $news_id the id of the news entry to get
 */
function getNewsItem($news_id) {

	$news = array();
	$sql = "SELECT * FROM ".TBLPREFIX."news WHERE n_id='$news_id'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$row = db_cleanup($row);
		$n = array();
		$n["id"] = $row["n_id"];
		$n["username"] = $row["n_username"];
		$n["date"] = $row["n_date"];
		$n["title"] = stripslashes($row["n_title"]);
		$n["text"] = stripslashes($row["n_text"]);
		$n["anchor"] = "article".$row["n_id"];
		$res->FreeResult();
		return $n;
	}
}
?>