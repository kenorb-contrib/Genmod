<?php
/**
 * Class file for an faq
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
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage DataModel
 * @version $Id: faq_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class FAQ {

	// General class information
	public $classname = "FAQ";			// Name of this class
	public $datatype = "FAQ";			// Type of data collected here
	
	private $header = null;				// Header text of the FAQ
	private $body = null;				// Body text of the FAQ
	private $order = null;				// Position to display
	private $gedcomid = null;			// Gedcom ID to display for
	private $id = null;					// Database ID of the FAQ
	private $is_empty = null;			// If this FAQ really exists
	
	private static $cache = array();	// Holder of the instances for this class
	
	
	public static function GetInstance($id, $data_array="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$id])) {
			self::$cache[$gedcomid][$id] = new FAQ($id, $data_array, $gedcomid);
		}
		return self::$cache[$gedcomid][$id];
	}
		
	public function __construct($id, $data_array="", $gedcomid="") {
		
		$this->id = $id;
		$this->gedcomid = $gedcomid;
		
		if (!is_array($data_array)) {
			$sql = "SELECT * FROM ".TBLPREFIX."faqs WHERE fa_id=".$id;
			$res = NewQuery($sql);
			if ($res->NumRows() == 0) $this->is_empty = true;
			else $data_array = $res->FetchAssoc();
				
		}
		if (is_array($data_array)) {
			$this->is_empty = false;
			$this->header = $data_array["fa_header"];
			$this->body = $data_array["fa_body"];
			$this->order = $data_array["fa_order"];
		}
		else $this->is_empty = true;
		
		self::$cache[$gedcomid][$id] = $this;
	}
	
	public function __get($property) {
		
		switch ($property) {
			case "id":
				return $this->id;
				break;
			case "header":
				return $this->header;
				break;
			case "body":
				return $this->body;
				break;
			case "order":
				return $this->order;
				break;
			case "is_empty":
				return $this->is_empty;
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function DeleteMe() {
		
		$sql = "DELETE FROM ".TBLPREFIX."faqs WHERE fa_id='".$this->id."'";
		$res = NewQuery($sql);
		$sql = "UPDATE ".TBLPREFIX."faqs SET fa_order=fa_order-1 WHERE fa_order>".$this->order." AND fa_file=".$this->gedcomid;
		$res = NewQuery($sql);
		unset(self::$cache[$this->gedcomid][$this->id]);
		WriteToLog("FAQ-&gt; FAQ item has been deleted.<br />ID: ".$this->id.".<br />Gedcom ID: ".$this->gedcomid, "I", "G", $this->gedcomid);
	}
	
	public function MoveMeUp() {
		
		if ($this->order == 1) return;
		
		$sql = "UPDATE ".TBLPREFIX."faqs SET fa_order=fa_order+1 WHERE fa_order=".($this->order-1)." AND fa_file=".$this->gedcomid;
		$res = NewQuery($sql);
		$sql = "UPDATE ".TBLPREFIX."faqs SET fa_order=fa_order-1 WHERE fa_id=".$this->id." AND fa_file=".$this->gedcomid;
		$res = NewQuery($sql);
		WriteToLog("FAQ-&gt; FAQ item has been moved up.<br />ID: ".$this->id.".<br />Gedcom ID: ".$this->gedcomid, "I", "G", $this->gedcomid);
	}
		
	public function MoveMeDown() {
		
		$sql = "UPDATE ".TBLPREFIX."faqs SET fa_order=fa_order-1 WHERE fa_order=".($this->order+1)." AND fa_file=".$this->gedcomid;
		$res = NewQuery($sql);
		$sql = "UPDATE ".TBLPREFIX."faqs SET fa_order=fa_order+1 WHERE fa_id=".$this->id." AND fa_file=".$this->gedcomid;
		$res = NewQuery($sql);
		WriteToLog("FAQ-&gt; FAQ item has been moved down.<br />ID: ".$this->id.".<br />Gedcom ID: ".$this->gedcomid, "I", "G", $this->gedcomid);
	}
	
	public function UpdateMe($header, $body) {
		
		$sql = "UPDATE ".TBLPREFIX."faqs SET fa_header='".DbLayer::EscapeQuery($header)."', fa_body='".DbLayer::EscapeQuery($body)."' WHERE fa_id='".$this->id."'";
		$res = NewQuery($sql);
		WriteToLog("FAQ-&gt; FAQ item has been edited.<br />ID: ".$this->id.".<br />Gedcom ID: ".$this->gedcomid, "I", "G", $this->gedcomid);
	}
	
	public function AddMe($header, $body) {
		
		$sql = "SELECT MAX(fa_order) FROM ".TBLPREFIX."faqs WHERE fa_file=".$this->gedcomid;
		$res = NewQuery($sql);
		$max = $res->FetchRow();
		$max = $max[0];
		if (is_null($max)) $order = 1;
		else $order = $max + 1;
		$sql = "INSERT INTO ".TBLPREFIX."faqs VALUES('', '".$order."', '".DbLayer::EscapeQuery($header)."', '".DbLayer::EscapeQuery($body)."', '".$this->gedcomid."')";
		$res = NewQuery($sql);
		WriteToLog("FAQ-&gt; FAQ item has been added.<br />ID: ".$res->InsertID().".<br />Gedcom ID: ".$this->gedcomid, "I", "G", $this->gedcomid);
	}
}
?>