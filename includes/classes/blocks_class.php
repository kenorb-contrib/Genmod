<?php
/**
 * Class file for blocks
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
 * @version $Id: blocks_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Blocks {
	
	// General class information
	public $classname = "Blocks";			// Name of this class
	
	// Variables
	public $main = array();					// Array of block for the main part of the page
	public $right = array();				// Array of blocks for the right part of the page
	public $type = "gedcom";				// What type: gedcom or user
	public $username = "";					// Username for user blocks, blank for gedcom blocks
	public $gedid = null;					// Gedcom ID in which the blocks exist
	public $welcome_block_present = false;	// Switch to indicate if the welcome block is present
	public $gedcom_block_present = false;	// Switch to indicate if the gedcom block is present
	public $top10_block_present = false;	// Switch to indicate if the top10 block is present
	public $login_block_present = false;	// Switch to indicate if the login block is present
	
	public function __construct($type, $id="", $action="") {

		$this->type = $type;
		if ($this->type == "user") $this->username = $id;
		$this->gedid = GedcomConfig::$GEDCOMID;
		
		// action init gets an empty object to be filled
		if ($action != "init") {
			
			// action reset returns to the default
			if ($action == "reset") $this->GetDefaults();
			else $this->GetBlocks();
		
			$this->Presency();
		}
	}
	
	private function GetBlocks() {
		
		// Try to retrieve a user stored blocks config
		$sql = "SELECT * FROM ".TBLPREFIX."blocks WHERE b_username='".DbLayer::EscapeQuery($this->username)."' AND b_file='".$this->gedid."' AND b_name<>'faq' ORDER BY b_location, b_order";
		$res = NewQuery($sql);
		if ($res->NumRows() > 0) {
			while($row = $res->FetchAssoc()){
				if (!isset($row["b_config"])) $row["b_config"]="";
				if ($row["b_location"]=="main") $this->main[$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]), $row["b_id"]);
				if ($row["b_location"]=="right") $this->right[$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]), $row["b_id"]);
			}
			$res->FreeResult();
		}
		else {
			if ($this->type == "user") {
				// Try to retrieve a stored default blocks config
				$sql = "SELECT * FROM ".TBLPREFIX."blocks WHERE b_username='defaultuser' AND b_file='".$this->gedid."' AND b_name<>'faq' ORDER BY b_location, b_order";
				$res2 = NewQuery($sql);
				if ($res2->NumRows() > 0) {
					while($row = $res2->FetchAssoc()){
						if (!isset($row["b_config"])) $row["b_config"]="";
						if ($row["b_location"]=="main") $this->blocks["main"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]), $row["b_id"]);
						if ($row["b_location"]=="right") $this->blocks["right"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]), $row["b_id"]);
					}
					$res2->FreeResult();
				}
				else $this->GetDefaults();
			}
			if ($this->type == "gedcom") {
				$this->GetDefaults();
			}
		}
	}

	public function setValues($setdefault=false) {
	
		// Delete old settings
		$sql = "DELETE FROM ".TBLPREFIX."blocks WHERE b_username='".DbLayer::EscapeQuery($this->username)."' AND b_file='".$this->gedid."'AND b_name<>'faq'";
		$res = NewQuery($sql);
		// Insert new values for the main column
		foreach($this->main as $order=>$block) {
			$sql = "INSERT INTO ".TBLPREFIX."blocks VALUES ('0', '".DbLayer::EscapeQuery($this->username)."', 'main', '$order', '".DbLayer::EscapeQuery($block[0])."', '".DbLayer::EscapeQuery(serialize($block[1]))."', '".$this->gedid."')";
			$res = NewQuery($sql);
			if ($setdefault && $this->type == "user") {
				$sql = "INSERT INTO ".TBLPREFIX."blocks VALUES ('0', 'defaultuser', 'main', '$order', '".DbLayer::EscapeQuery($block[0])."', '".DbLayer::EscapeQuery(serialize($block[1]))."', '".$this->gedid."')";
				$res = NewQuery($sql);
			}
		}
		// Insert new values for the right column
		foreach($this->right as $order=>$block) {
			$sql = "INSERT INTO ".TBLPREFIX."blocks VALUES ('0', '".DbLayer::EscapeQuery($this->username)."', 'right', '$order', '".DbLayer::EscapeQuery($block[0])."', '".DbLayer::EscapeQuery(serialize($block[1]))."', '".$this->gedid."')";
			$res = NewQuery($sql);
			if ($setdefault && $this->type == "user") {
				$sql = "INSERT INTO ".TBLPREFIX."blocks VALUES ('0', 'defaultuser', 'right', '$order', '".DbLayer::EscapeQuery($block[0])."', '".DbLayer::EscapeQuery(serialize($block[1]))."', '".$this->gedid."')";
				$res = NewQuery($sql);
			}
		}
	}

	public function SetConfig($id, $config) {

		$sql = "UPDATE SET b_config='".DbLayer::EscapeQuery(serialize($config))."' WHERE b_id='".$id."'";	
		$res = NewQuery($sql);
	}
		
	private function GetDefaults() {
		
		if ($this->type == "user") {
			// Defaults for user blocks, main column
			$this->main[] = array("print_quickstart_block", "");
			$this->main[] = array("print_todays_events", "");
			$this->main[] = array("print_user_messages", "");
			$this->main[] = array("print_user_favorites", "");
	
			// Defaults for user blocks, right column
			$this->right[] = array("print_welcome_block", "");
			$this->right[] = array("print_random_media", "");
			$this->right[] = array("print_upcoming_events", "");
			$this->right[] = array("print_logged_in_users", "");
		}
		else {
			// Defaults for gedcom blocks, main column
			$this->main[] = array("print_quickstart_block", "");
			$this->main[] = array("print_gedcom_stats", "");
			$this->main[] = array("print_gedcom_news", "");
			$this->main[] = array("print_gedcom_favorites", "");
			$this->main[] = array("review_changes_block", "");
	
			// Defaults for gedcom blocks, right column
			$this->right[] = array("print_gedcom_block", "");
			$this->right[] = array("print_random_media", "");
			$this->right[] = array("print_todays_events", "");
			$this->right[] = array("print_logged_in_users", "");
		}
	}
	
	private function Presency() {
		//-- Set some behaviour controls that depend on which blocks are selected
		foreach($this->right as $block) {
			if ($block[0] == "print_welcome_block") $this->welcome_block_present = true;
			if ($block[0] == "print_gedcom_block") $this->gedcom_block_present = true;
			if ($block[0] == "print_block_name_top10") $this->top10_block_present = true;
			if ($block[0] == "print_login_block") $this->login_block_present = true;
		}
		foreach($this->main as $block) {
			if ($block[0] == "print_welcome_block") $this->welcome_block_present = true;
			if ($block[0] == "print_gedcom_block") $this->gedcom_block_present = true;
			if ($block[0] == "print_block_name_top10") $this->top10_block_present = true;
			if ($block[0] == "print_login_block") $this->login_block_present = true;
		}
	}
}
?>