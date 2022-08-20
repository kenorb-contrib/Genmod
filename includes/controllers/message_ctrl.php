<?php
/**
 * Controller class for messages
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
 * @version $Id: message_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class MessageController {
	
	public $classname = "MessageController";	// Name of this class
	
	//-- deletes a message in the database
	public function deleteMessage($message_id) {
	
		$sql = "DELETE FROM ".TBLPREFIX."messages WHERE m_id=".$message_id;
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
	
	// Delete all messages of a user
	public function DeleteUserMessages($username) {
	
		$sql = "DELETE FROM ".TBLPREFIX."messages WHERE m_to='".$username."' OR m_from='".$username."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
	
	//----------------------------------- getUserMessages
	//-- Return an array of a users messages
	public function getUserMessages($username="") {
	
		$messages = array();
		$sql = "SELECT * FROM ".TBLPREFIX."messages ";
		if (!empty($username)) $sql .= "WHERE m_to='".$username."' OR m_from='".$username."' ";
		$sql .= "ORDER BY m_id DESC";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$messages[] = new Message($row);
		}
		return $messages;
	}
	
	//----------------------------------- getUserMessagesCount
	//-- Return an array of a users messages
	public function getUserMessagesCount($username="") {
	
		$sql = "SELECT count(m_id) FROM ".TBLPREFIX."messages ";
		if (!empty($username)) $sql .= "WHERE m_to='".$username."' OR m_from='".$username."' ";
		$res = NewQuery($sql);
		while($row = $res->FetchRow()){
			$messagescount = $row[0];
		}
		return $messagescount;
	}
}
?>