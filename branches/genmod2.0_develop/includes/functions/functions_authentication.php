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
 * Stores a new message in the database
 *
 * 	$message["to"]
 *	$message["from"]
 *	$message["subject"]
 *	$message["body"]
 *	$message["method"]
 *	$message["url"]
 *	$message["no_from"]
 *	$message["from_name"]
 *
 * @author	Genmod Development Team
 * @param		array	$message		The text to be added to the message
 * @return 	boolean	True if mail has been stored/send or false if this failed
 */
function AddMessage($message, $admincopy=false) {
	global $TBLPREFIX, $CONTACT_METHOD, $gm_lang,$CHARACTER_SET, $LANGUAGE, $GM_STORE_MESSAGES, $SERVER_URL, $gm_language, $GM_BASE_DIRECTORY, $GM_SIMPLE_MAIL, $WEBMASTER_EMAIL, $DBCONN, $Users;
	global $TEXT_DIRECTION, $TEXT_DIRECTION_array, $DATE_FORMAT, $DATE_FORMAT_array, $TIME_FORMAT, $TIME_FORMAT_array, $WEEK_START, $WEEK_START_array, $NAME_REVERSE, $NAME_REVERSE_array;

	// NOTE: Strip slashes from the body text
	$message["body"] = stripslashes($message["body"]);
	
	// NOTE: Details e-mail for the sender
	$message_sender = "";
	
	// NOTE: Check if it is a message from an unregistered user (from_name)
	if (isset($message["from_name"])) {
		// NOTE: Username
		$message_sender = $gm_lang["message_from_name"]." ".$message["from_name"]."<br />";
		// NOTE: Email address
		$message_sender .= $gm_lang["message_from"]." ".$message["from_email"]."<br /><br />";
		
		// NOTE: From header
		$messagefrom = $gm_lang["message_from_name"]." ".$message["from_name"]."<br />";
		$messagefrom .= $gm_lang["message_from"]." ".$message["from_email"]."<br /><br />";
	}
	else $message["from_name"] = '';

	if (!isset($message["from_email"])) {
		$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
		$message["from_email"] = "Genmod-noreply@".$host;
	}
	
	// NOTE: Message body
	$message_sender .= $message["body"]."<br /><br />";
		
	// NOTE: Check the URL where the message is sent from and add it to the message
	if (!empty($message["url"])) {
		$message_sender .= "--------------------------------------<br />";
		$message_sender .= $gm_lang["viewing_url"]."<br /><a href=\"".$SERVER_URL.$message["url"]."\">".$SERVER_URL.$message["url"]."</a><br />";
	}
	
	// NOTE: Add system details
	$message_sender .= "=--------------------------------------=<br />";
	$message_sender .= "IP ADDRESS: ".$_SERVER['REMOTE_ADDR']."<br />";
	$message_sender .= "DNS LOOKUP: ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."<br />";
	$message_sender .= "LANGUAGE: ".$LANGUAGE."<br />";
	
	// NOTE: E-mail Subject
	$subject_sender = "[".$gm_lang["Genmod_message"]."] ".stripslashes($message["subject"]);
	
	// NOTE: E-mail from
	$from ="";
	$fuser = $Users->GetUser($message["from_name"]);
	
	if (empty($fuser->username)) {
		$from = $message["from_email"];
		$message_sender = $gm_lang["message_email3"]."<br /><br />".stripslashes($message_sender);
	}
	else {
		if (!$GM_SIMPLE_MAIL) $from = "'".stripslashes($fuser->firstname." ".$fuser->lastname). "' <".$fuser->email.">";
		else $from = $fuser->email;
		$message_sender = $gm_lang["message_email2"]."<br /><br />".stripslashes($message_sender);

	}

	// NOTE: Details e-mail for the recipient
	$message_recipient = "";
	
	$tuser = $Users->GetUser($message["to"]);
	
	// NOTE: Load the recipients language
	$oldlanguage = $LANGUAGE;
	if (is_object($tuser) && !empty($tuser->language) && $tuser->language!=$LANGUAGE) {
		$LANGUAGE = $tuser->language;
		LoadEnglish(false, false, true);
		$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
		$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
		$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
		$WEEK_START	= $WEEK_START_array[$LANGUAGE];
		$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
	}
	
	// NOTE: Check the URL where the message is sent from and add it to the message
	if (!empty($message["url"])) {
		$messagebody = "<br /><br />--------------------------------------<br />";
		$messagebody .= $gm_lang["viewing_url"]."<br /><a href=\"".$SERVER_URL.$message["url"]."\">".$SERVER_URL.$message["url"]."</a><br />\n";
	}
	else $messagebody = "";
	
	// NOTE: Add system details
	$messagebody .= "=--------------------------------------=<br />";
	$messagebody .= "IP ADDRESS: ".$_SERVER['REMOTE_ADDR']."<br />";
	$messagebody .= "DNS LOOKUP: ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."<br />";
	$messagebody .= "LANGUAGE: $LANGUAGE<br />";
	
	// NOTE: If admin wants the messages to be stored, we do it here
	if (!isset($message["created"]) || empty($message["created"])) $message["created"] = gmdate ("M d Y H:i:s");
	if ($GM_STORE_MESSAGES && ($message["method"] != "messaging3" && $message["method"] != "mailto" && $message["method"] != "none")) {
		$messagestore = "";
		if (isset($messagefrom)) $messagestore .= $messagefrom;
		$messagestore .= $message["body"].$messagebody;
		$sql = "INSERT INTO ".$TBLPREFIX."messages VALUES ('0', '".$DBCONN->EscapeQuery($message["from"])."','".$DBCONN->EscapeQuery($message["to"])."','".$DBCONN->EscapeQuery($message["subject"])."','".$DBCONN->EscapeQuery($messagestore)."','".$DBCONN->EscapeQuery($message["created"])."')";
		$res = NewQuery($sql);
	}
	
	if ($message["method"] != "messaging") {
		// NOTE: E-mail subject recipient
		$subject_recipient = "[".$gm_lang["Genmod_message"]."] ".stripslashes($message["subject"]);
		
		// NOTE: E-mail from recipient
		if (!is_object($fuser)) {
			$message_recipient = $gm_lang["message_email1"];
			if (!empty($message["from_name"])) $message_recipient .= $message["from_name"]."<br /><br />".stripslashes($message["body"]);
			else $message_recipient .= $gm_lang["message_from_name"].$from."<br /><br />".stripslashes($message["body"]);
		}
		else {
			$message_recipient = $gm_lang["message_email1"]."<br /><br />";
			$message_recipient .= stripslashes($fuser->firstname." ".$fuser->lastname)."<br /><br />".stripslashes($message["body"]);
		}
		// NOTE: Message body
		$message_recipient .= $messagebody."<br /><br />";
		
		$tuser = $Users->GetUser($message["to"]);
		// NOTE: the recipient must be a valid user in the system before it will send any mails
		if (!is_object($tuser)) return false;
		else {
			if (!$GM_SIMPLE_MAIL) $to = "'".stripslashes($tuser->firstname." ".$tuser->lastname). "' <".$tuser->email.">";
			else $to = $tuser->email;
		}
		if (!$fuser) {
			$header_sender = '';
		} 
		else $header_sender = $from;
		
		// Send the notification email to the admin
		if (!empty($tuser->email)) {
			GmMail($to, $subject_recipient, $message_recipient, $message["from_name"], $from, $header_sender, "", "", $admincopy);
			
		}
	}
	
	// NOTE: Unload the recipients language and load the users language
	if (($tuser)&&(!empty($LANGUAGE))&&($oldlanguage!=$LANGUAGE)) {
		$LANGUAGE = $oldlanguage;
		LoadEnglish(false, false, true);
		$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
		$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
		$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
		$WEEK_START	= $WEEK_START_array[$LANGUAGE];
		$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
	}
	if ($message["method"] != "messaging") {
		if (!isset($message["no_from"])) {
			if (stristr($from, "Genmod-noreply@")){
				$admuser = $Users->GetUser($WEBMASTER_EMAIL);
				$from = $admuser->email;
			}
			if (!empty($from)) {
				GmMail($from, $subject_sender, $message_sender, "", "", "", "", "", "");
			}
		}
	}
	return true;
}

//----------------------------------- deleteMessage
//-- deletes a message in the database
function deleteMessage($message_id) {
	global $TBLPREFIX;

	$sql = "DELETE FROM ".$TBLPREFIX."messages WHERE m_id=".$message_id;
	$res = NewQuery($sql);
	if ($res) return true;
	else return false;
}

// Delete all messages of a user
function DeleteUserMessages($username) {
	global $TBLPREFIX;

	$sql = "DELETE FROM ".$TBLPREFIX."messages WHERE m_to='".$username."' OR m_from='".$username."'";
	$res = NewQuery($sql);
	if ($res) return true;
	else return false;
}

//----------------------------------- getUserMessages
//-- Return an array of a users messages
function getUserMessages($username) {
	global $TBLPREFIX;

	$messages = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."messages ";
	if (!empty($username)) $sql .= "WHERE m_to='$username' OR m_from='$username' ";
	$sql .= "ORDER BY m_id DESC";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$row = db_cleanup($row);
		$message = array();
		$message["id"] = $row["m_id"];
		$message["to"] = $row["m_to"];
		$message["from"] = $row["m_from"];
		$message["subject"] = stripslashes($row["m_subject"]);
		$message["body"] = stripslashes($row["m_body"]);
		$message["created"] = $row["m_created"];
		$messages[] = $message;
	}
	return $messages;
}

// Get the age in months of a message
function GetMessageAge($message) {
	static $now, $nowday, $nowmon, $nowyear;
	
	if (!isset($now)) {
		$now = time();
		$nowday = date("j", $now);
		$nowmon = date("m", $now);
		$nowyear = date("Y", $now);
	}
	if (!empty($message["created"])) $time = strtotime($message["created"]);
	else $time = time();
	$day = date("j", $time);
	$mon = date("m", $time);
	$year = date("Y", $time);
	$mmon = ($nowyear - $year) * 12;
	$mmon = $mmon + $nowmon - $mon;
	if ($day > $nowday) $mmon--;
	return $mmon;
}


/**
 * Get blocks for the given username
 *
 * Retrieve the block configuration for the given user
 * if no blocks have been set yet, and the username is a valid user (not a gedcom) then try and load
 * the default user blocks.
 * @param string $username	the username or gedcom name for the blocks
 * @return array	an array of the blocks.  The two main indexes in the array are "main" and "right"
 */
function GetBlocks($username) {
	global $TBLPREFIX, $GEDCOMS, $DBCONN, $Users;

	$blocks = array();
	$blocks["main"] = array();
	$blocks["right"] = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."blocks WHERE b_username='".$DBCONN->EscapeQuery($username)."' ORDER BY b_location, b_order";
	$res = NewQuery($sql);
	if ($res->NumRows() > 0) {
		while($row = $res->FetchAssoc()){
			$row = db_cleanup($row);
			if (!isset($row["b_config"])) $row["b_config"]="";
			if ($row["b_location"]=="main") $blocks["main"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
			if ($row["b_location"]=="right") $blocks["right"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
		}
	}
	else {
		$user = $Users->GetUser($username);
		if (!empty($user->username)) {
			//-- if no blocks found, check for a default block setting
			$sql = "SELECT * FROM ".$TBLPREFIX."blocks WHERE b_username='defaultuser' ORDER BY b_location, b_order";
			$res2 = NewQuery($sql);
			while($row = $res2->FetchAssoc()){
				$row = db_cleanup($row);
				if (!isset($row["b_config"])) $row["b_config"]="";
				if ($row["b_location"]=="main") $blocks["main"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
				if ($row["b_location"]=="right") $blocks["right"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
			}
			$res2->FreeResult();
		}
	}
	$res->FreeResult();
	return $blocks;
}

/**
 * Set Blocks
 *
 * Sets the blocks for a gedcom or user portal
 * the $setdefault parameter tells the program to also store these blocks as the blocks used by default
 * @param String $username the username or gedcom name to update the blocks for
 * @param array $ublocks the new blocks to set for the user or gedcom
 * @param boolean $setdefault	if true tells the program to also set these blocks as the blocks for the defaultuser
 */
function setBlocks($username, $ublocks, $setdefault=false) {
	global $TBLPREFIX, $DBCONN;

	$sql = "DELETE FROM ".$TBLPREFIX."blocks WHERE b_username='".$DBCONN->EscapeQuery($username)."'";
	$res = NewQuery($sql);
	foreach($ublocks["main"] as $order=>$block) {
		$newid = GetNextId("blocks", "b_id");
		$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, '".$DBCONN->EscapeQuery($username)."', 'main', '$order', '".$DBCONN->EscapeQuery($block[0])."', '".$DBCONN->EscapeQuery(serialize($block[1]))."')";
		$res = NewQuery($sql);
		if ($setdefault) {
			$newid = GetNextId("blocks", "b_id");
			$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, 'defaultuser', 'main', '$order', '".$DBCONN->EscapeQuery($block[0])."', '".$DBCONN->EscapeQuery(serialize($block[1]))."')";
			$res = NewQuery($sql);
		}
	}
	foreach($ublocks["right"] as $order=>$block) {
		$newid = GetNextId("blocks", "b_id");
		$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, '".$DBCONN->EscapeQuery($username)."', 'right', '$order', '".$DBCONN->EscapeQuery($block[0])."', '".$DBCONN->EscapeQuery(serialize($block[1]))."')";
		$res = NewQuery($sql);
		if ($setdefault) {
			$newid = GetNextId("blocks", "b_id");
			$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, 'defaultuser', 'right', '$order', '".$DBCONN->EscapeQuery($block[0])."', '".$DBCONN->EscapeQuery(serialize($block[1]))."')";
			$res = NewQuery($sql);
		}
	}
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
	global $TBLPREFIX, $DBCONN;

	if (!isset($news["date"])) $news["date"] = time()-$_SESSION["timediff"];
	if (!empty($news["id"])) {
		// In case news items are added from usermigrate, it will also contain an ID.
		// So we check first if the ID exists in the database. If not, insert instead of update.
		$sql = "SELECT * FROM ".$TBLPREFIX."news where n_id=".$news["id"];
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) {
			$sql = "INSERT INTO ".$TBLPREFIX."news VALUES (".$news["id"].", '".$DBCONN->EscapeQuery($news["username"])."','".$DBCONN->EscapeQuery($news["date"])."','".$DBCONN->EscapeQuery($news["title"])."','".$DBCONN->EscapeQuery($news["text"])."')";
		}
		else {
			$sql = "UPDATE ".$TBLPREFIX."news SET n_date='".$DBCONN->EscapeQuery($news["date"])."', n_title='".$DBCONN->EscapeQuery($news["title"])."', n_text='".$DBCONN->EscapeQuery($news["text"])."' WHERE n_id=".$news["id"];
		}
		$res->FreeResult();
	}
	else {
		$newid = GetNextId("news", "n_id");
		$sql = "INSERT INTO ".$TBLPREFIX."news VALUES ($newid, '".$DBCONN->EscapeQuery($news["username"])."','".$DBCONN->EscapeQuery($news["date"])."','".$DBCONN->EscapeQuery($news["title"])."','".$DBCONN->EscapeQuery($news["text"])."')";
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
	global $TBLPREFIX;

	$sql = "DELETE FROM ".$TBLPREFIX."news WHERE n_id=".$news_id;
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
	global $TBLPREFIX, $DBCONN;

	$news = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."news WHERE n_username='".$DBCONN->EscapeQuery($username)."' ORDER BY n_date DESC";
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
	global $TBLPREFIX;

	$news = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."news WHERE n_id='$news_id'";
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