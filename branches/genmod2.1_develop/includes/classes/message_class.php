<?php
/**
 * Class file for Genmod messages
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
 * @version $Id: message_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Message {
	
	// General class information
	public $classname = "Message";	// The name of this class
	
	// Data
	private $id = 0;			// The ID of this item in the database
	private $to = null;			// The username of the recipient
	private $from = null;		// The username OR email address (for visitors) of the sender. 
								// Only if mails are sent from a registered user, this is set.
	private $subject = null;	// The subject of the message
	private $body = null;		// The message body
	private $created = "";		// Creation date/time of the message
	
	// Additional info set for sending purposes
	private $from_email = null;	// The email address (for visitors) of the sender. If not visitor, this is not set.
	private $from_name = null;	// The name (for visitors) of the sender. If not visitor, this is not set.
	private $method = null;		// Messaging method to be used
	private $url = null;		// URL where the message is sent from
	private $no_from = null;	// I guess this is for system generated messages
	
	private $cansend = true;	// is false if this message is created with information from the DB. Only new messages can be sent.

	public function __construct($values="") {
		
		if (is_array($values)) {
			$this->id = $values["m_id"];
			$this->to = $values["m_to"];
			$this->from = $values["m_from"];
			$this->subject = stripslashes($values["m_subject"]);
			$this->body = stripslashes($values["m_body"]);
			$this->created = $values["m_created"];
			$this->cansend = false;
		}
	}

	public function __get($property) {
		switch ($property) {
			case "id":
				return $this->id;
				break;
			case "to":
				return $this->to;
				break;
			case "from":
				return $this->from;
				break;
			case "subject":
				return $this->subject;
				break;
			case "body":
				return $this->body;
				break;
			case "created":
				return $this->created;
				break;
			case "age":
				return $this->GetMessageAge();
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function __set($property, $value) {
		switch ($property) {
			case "to":
				$this->to = $value;
				break;
			case "from":
				$this->from = $value;
				break;
			case "from_email":
				$this->from_email = $value;
				break;
			case "from_name":
				$this->from_name = $value;
				break;
			case "subject":
				$this->subject = $value;
				break;
			case "body":
				$this->body = stripslashes($value);
				break;
			case "created":
				$this->created= $value;
				break;
			case "method":
				$this->method = $value;
				break;
			case "url":
				$this->url = $value;
				break;
			case "no_from":
				if (is_bool($value)) $this->no_from = $value;
				break;
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}
	
	public function DeleteThis() {
		
		$sql = "DELETE FROM ".TBLPREFIX."messages WHERE m_id=".$message_id;
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}

	// Get the age in months of a message
	private function GetMessageAge() {

		$now = time();
		$nowday = date("j", $now);
		$nowmon = date("m", $now);
		$nowyear = date("Y", $now);
		
		if ($this->created != "") $time = strtotime($this->created);
		else $time = time();
		$day = date("j", $time);
		$mon = date("m", $time);
		$year = date("Y", $time);
		$mmon = ($nowyear - $year) * 12;
		$mmon = $mmon + $nowmon - $mon;
		if ($day > $nowday) $mmon--;
		return $mmon;
	}
	
	public function AddMessage($admincopy=false) {
		global $LANGUAGE;
		global $TEXT_DIRECTION, $TEXT_DIRECTION_array, $DATE_FORMAT, $DATE_FORMAT_array, $TIME_FORMAT, $TIME_FORMAT_array, $WEEK_START, $WEEK_START_array, $NAME_REVERSE, $NAME_REVERSE_array;

		// We cannot send already stored messages
		if ($this->cansend == false) return false;
		
		// NOTE: Details e-mail for the sender
		$message_sender = "";
		
		// NOTE: Check if it is a message from an unregistered user (from_name and from_email are set)
		if (!is_null($this->from_name)) {
			// NOTE: Username
			$message_sender = GM_LANG_message_from_name." ".$this->from_name."<br />";
			// NOTE: Email address
			$message_sender .= GM_LANG_message_from." ".$this->from_email."<br /><br />";
			
			// NOTE: From header
			$messagefrom = GM_LANG_message_from_name." ".$this->from_name."<br />";
			$messagefrom .= GM_LANG_message_from." ".$this->from_email."<br /><br />";
		}
		else $this->from_name = '';
	
		if (is_null($this->from_email)) {
			$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
			$this->from_email = "Genmod-noreply@".$host;
		}
		
		// NOTE: Message body
		$message_sender .= $this->body."<br /><br />";
			
		// NOTE: Check the URL where the message is sent from and add it to the message
		if (!is_null($this->url) && $this->url != "") {
			$message_sender .= "--------------------------------------<br />";
			$message_sender .= GM_LANG_viewing_url."<br /><a href=\"".SERVER_URL.$this->url."\">".SERVER_URL.$this->url."</a><br />";
		}
		
		// NOTE: Add system details
		$message_sender .= "=--------------------------------------=<br />";
		$message_sender .= "IP ADDRESS: ".$_SERVER['REMOTE_ADDR']."<br />";
		$message_sender .= "DNS LOOKUP: ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."<br />";
		$message_sender .= "LANGUAGE: ".$LANGUAGE."<br />";
		
		// NOTE: E-mail Subject
		$subject_sender = "[".GM_LANG_Genmod_message."] ".stripslashes($this->subject);
		
		// NOTE: E-mail from
		$from ="";
		$fuser =& User::GetInstance($this->from);
		$tuser =& User::GetInstance($this->to);
		
		if ($fuser->username == "") {
			$from = $this->from_email;
			$message_sender = GM_LANG_message_email3."<br /><br />".stripslashes($message_sender);
		}
		else {
			if (!SystemConfig::$GM_SIMPLE_MAIL) $from = "'".stripslashes($fuser->firstname." ".$fuser->lastname). "' <".$fuser->email.">";
			else $from = $fuser->email;
			$message_sender = GM_LANG_message_email2." ".$tuser->firstname." ".$tuser->lastname."<br /><br />".stripslashes($message_sender);
	
		}
	
		// NOTE: Details e-mail for the recipient
		$message_recipient = "";
		
		
		// NOTE: Load the recipients language
		$oldlanguage = $LANGUAGE;
		if (is_object($tuser) && $tuser->language != "" && $tuser->language != $LANGUAGE) {
			$LANGUAGE = $tuser->language;
			$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
			$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
			$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
			$WEEK_START	= $WEEK_START_array[$LANGUAGE];
			$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
		}
		$templang = LanguageFunctions::LoadEnglish(true, false, true);
		
		// NOTE: Check the URL where the message is sent from and add it to the message
		if (!is_null($this->url)) {
			$messagebody = "<br /><br />--------------------------------------<br />";
			$messagebody .= $templang["viewing_url"]."<br /><a href=\"".SERVER_URL.$this->url."\">".SERVER_URL.$this->url."</a><br />\n";
		}
		else $messagebody = "";
		
		// NOTE: Add system details
		$messagebody .= "=--------------------------------------=<br />";
		$messagebody .= "IP ADDRESS: ".$_SERVER['REMOTE_ADDR']."<br />";
		$messagebody .= "DNS LOOKUP: ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."<br />";
		$messagebody .= "LANGUAGE: $LANGUAGE<br />";

		// NOTE: If admin wants the messages to be stored, we do it here
		if (is_null($this->created) || $this->created == "") $this->created = gmdate ("M d Y H:i:s");
		if (SystemConfig::$GM_STORE_MESSAGES && ($this->method != "messaging3" && $this->method != "mailto" && $this->method != "none")) {
			$messagestore = "";
			if (isset($messagefrom)) $messagestore .= $messagefrom;
			$messagestore .= $this->body.$messagebody;
			$sql = "INSERT INTO ".TBLPREFIX."messages VALUES ('0', '".DbLayer::EscapeQuery($this->from)."','".DbLayer::EscapeQuery($this->to)."','".DbLayer::EscapeQuery($this->subject)."','".DbLayer::EscapeQuery($messagestore)."','".DbLayer::EscapeQuery($this->created)."')";
			$res = NewQuery($sql);
		}
		
		if ($this->method != "messaging") {
			// NOTE: E-mail subject recipient
			$subject_recipient = "[".$templang["Genmod_message"]."] ".stripslashes($this->subject);
			
			// NOTE: E-mail from recipient
			if (!is_object($fuser)) {
				$message_recipient = $templang["message_email1"];
				if (!is_null($this->from_name && $this->from_name != "")) $message_recipient .= $this->from_name."<br /><br />".stripslashes($this->body);
				else $message_recipient .= $templang["message_from_name"].$from."<br /><br />".stripslashes($this->body);
			}
			else {
				$message_recipient = $templang["message_email1"]."<br /><br />";
				$message_recipient .= stripslashes($fuser->firstname." ".$fuser->lastname)."<br /><br />".stripslashes($this->body);
			}
			// NOTE: Message body
			$message_recipient .= $messagebody."<br /><br />";
			
			// NOTE: the recipient must be a valid user in the system before it will send any mails
			if (!is_object($tuser)) return false;
			else {
				if (!SystemConfig::$GM_SIMPLE_MAIL) $to = "'".stripslashes($tuser->firstname." ".$tuser->lastname). "' <".$tuser->email.">";
				else $to = $tuser->email;
			}
			if (!$fuser) {
				$header_sender = '';
			} 
			else $header_sender = $from;
			
			// Send the notification email to the admin
			if ($tuser->email != "") {
				GmMail($to, $subject_recipient, $message_recipient, $this->from_name, $from, $header_sender, "", "", $admincopy);
				
			}
		}
		
		// NOTE: Unload the recipients language and load the users language
		if (($tuser)&&(!empty($LANGUAGE))&&($oldlanguage!=$LANGUAGE)) {
			$LANGUAGE = $oldlanguage;
			$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
			$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
			$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
			$WEEK_START	= $WEEK_START_array[$LANGUAGE];
			$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
		}
		if ($this->method != "messaging") {
			if (is_null($this->no_from)) {
				if (stristr($from, "Genmod-noreply@")){
					$admuser =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
					$from = $admuser->email;
				}
				if (!empty($from)) {
					GmMail($from, $subject_sender, $message_sender, "", "", "", "", "", "");
				}
			}
		}
		return true;
	}

}
?>