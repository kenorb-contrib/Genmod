<?php
/**
 * Controller class for FAQs
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
 * @version $Id: faq_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class FAQController {
	
	// General class information
	public $classname = "FAQController";	// Name of this class
	private $faqs = null;					// Holder for the FAQ array
	
	private $canconfig = null;				// Whether the user can edit FAQ's (gedcom admins)
	private $adminedit = null;				// To show a preview of the faq
	private $action = null;					// Action to perform
	private $type = null;					// Type of action to perform
	private $message = null;				// Message to display
	private $id = null;						// id incoming variable
	private $faq = null;					// faq belonging to the id
	private $error_message = null;			// Error message to display
	
	
	public function __construct() {
		global $gm_user;
		
		if ($gm_user->userGedcomAdmin()) $this->canconfig = true;
		else $this->canconfig = false;
				
		if (!isset($_REQUEST["action"])) $this->action = "show";
		else $this->action = $_REQUEST["action"];

		if (isset($_REQUEST["id"])) {
			$this->id = $_REQUEST["id"];
			if ($this->action != "commit" && $this->type != "delete") $this->faq = FAQ::GetInstance($this->id);
		}
		
		if (isset($_REQUEST["type"])) $this->type = $_REQUEST["type"];
		
		if (!isset($_REQUEST["adminedit"]) && $this->canconfig) $this->adminedit = true;
		else if (!isset($_REQUEST["adminedit"])) $this->adminedit = false;
		else $this->adminedit = $_REQUEST["adminedit"];
		
		$this->message = "";

		if ($this->action == "edit" && (is_null($this->id) || $this->faq->is_empty)) {
			$this->error_message = GM_LANG_no_faq_content;
			$this->action = "show";
		}
		
		if ($this->action == "commit" && $this->canconfig && !is_null($this->type)) {
			if (isset($_REQUEST["id"])) $id = $_REQUEST["id"];
			switch($this->type) {
				case "delete":
					$faq = FAQ::GetInstance($id);
					$faq->DeleteMe();
					break;
				case "add":
					if (isset($_REQUEST["header"])) $header = $_REQUEST["header"];
					else $header = "";
					if (isset($_REQUEST["body"])) $body = $_REQUEST["body"];
					else $body = "";
					if (empty($body) || empty($header)) $this->error_message = GM_LANG_no_faq_content;
					else  {
						$faq =& FAQ::GetInstance("0");
						$faq->AddMe($header, $body);
					}
					break;
				case "update":
					if (isset($_REQUEST["header"])) $header = $_REQUEST["header"];
					else $header = "";
					if (isset($_REQUEST["body"])) $body = $_REQUEST["body"];
					else $body = "";
					if (empty($body) || empty($header)) $this->error_message = GM_LANG_no_faq_content;
					else {
						$faq = FAQ::GetInstance($id);
						$faq->UpdateMe($header, $body);
					}
					break;
				case "moveup":
					$faq = FAQ::GetInstance($id);
					$faq->MoveMeUp();
					break;
				case "movedown":
					$faq = FAQ::GetInstance($id);
					$faq->MoveMeDown();
					break;
			}
			$this->action = "show";
		}
	}

	public function __get($property) {
		switch($property) {
			case "canconfig":
				return $this->canconfig;
				break;
			case "adminedit":
				return $this->adminedit;
				break;
			case "action":
				return $this->action;
				break;
			case "message":
				return $this->message;
				break;
			case "id":
				return $this->id;
				break;
			case "faq":
				return $this->faq;
				break;
			case "faqs":
				return $this->GetFaqData();
				break;
			case "error_message":
				return $this->error_message;
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function __set($property, $value) {
		switch($property) {
			case "action":
				$this->action = $value;
				break;
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}
	
	/**
	 * Retrieve the array of faqs from the DB table blocks
	 *
	 * @package Genmod
	 * @subpackage FAQ
	 * @param int $id		The FAQ ID to retrieve
	 * @return array $faqs	The array containing the FAQ items
	 */
	private function GetFaqData() {
		
		if (is_null($this->faqs)) {
			$this->faqs = array();
			// Read the faq data from the DB
			$sql = "SELECT fa_id, fa_order, fa_body, fa_header FROM ".TBLPREFIX."faqs WHERE fa_file='".GedcomConfig::$GEDCOMID."'";
	
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()){
				$this->faqs[$row["fa_order"]] = new FAQ($row["fa_id"], $row, GedcomConfig::$GEDCOMID);
			}
			ksort($this->faqs);
		}
		return $this->faqs;
	}

}
?>