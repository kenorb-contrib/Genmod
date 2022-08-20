<?php
/**
 * Controller for the clippings page
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
 *
 * @package Genmod
 * @subpackage export
 * @version $Id: clippings_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class ClippingsController extends BaseController {
	
	public $classname = "ClippingsController";	// Name of this class
	// $action is defind in the base controller	// Action to perform (add, remove, empty, download)
	private $id = null;							// Id of the person/family to add
	private $remove = null;						// For download, remove GM customtags (yes or nothing)
	private $convert = null;					// Convert to ANSI or not (yes or nothing)
	private $type = null;						// Type of Id, indi or fam
	private $others = null;						// What others to add: 
												// - parents, members and descendants for families
												// - parents, ancestors, ancestorsfamilies, members, descendants
	private $cart = null;						// The actual clippings cart, retrieved from and saved to $_SESSION
	private $media = array();					// Array for media filenames to download
	private $mediacount = 0;					// Counter for media items to download
	
	public function __construct() {
		global $ENABLE_CLIPPINGS_CART, $PRIV_HIDE, $PRIV_PUBLIC, $gm_user;
		
		// -- setup session information for tree clippings cart features
		if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
		$this->cart = $_SESSION['cart'];
		
		if (!isset($ENABLE_CLIPPINGS_CART)) $ENABLE_CLIPPINGS_CART = $PRIV_HIDE;
		if ($ENABLE_CLIPPINGS_CART === true) $ENABLE_CLIPPING_CART = $PRIV_PUBLIC;
		if ($ENABLE_CLIPPINGS_CART < $gm_user->getUserAccessLevel()) {
		  header("Location: index.php");
		  exit;
		}
		if (!isset($_REQUEST["id"])) $this->id = "";
		else $this->id = $_REQUEST["id"];
		$this->id = CleanInput($this->id);
		
		if (!isset($_REQUEST["remove"])) $this->remove = "no";
		else $this->remove = $_REQUEST["remove"];
		
		if (!isset($_REQUEST["convert"])) $this->convert = "no";
		else $this->convert = $_REQUEST["convert"];
		
		if (!isset($_REQUEST["type"])) $this->type = "";
		else $this->type = $_REQUEST["type"];
		
		if (!isset($_REQUEST["others"])) $this->others = "";
		else $this->others = $_REQUEST["others"];

		parent::__construct();		
	}

	public function __get($property) {
		switch($property) {
			case "id":
				return $this->id;
				break;
			case "type":
				return $this->type;
				break;
			case "cart":
				return $this->cart;
				break;
			case "media":
				return $this->media;
				break;
			case "mediacount":
				return $this->mediacount;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	public function __set($property, $value) {
		switch($property) {
			case "action":
				$this->action = $value;
				break;
			default:
				parent::__set($property, $value);
				break;
		}
	}

	protected function GetPageTitle() {
			
		if (is_null($this->pagetitle)) {
			$this->pagetitle = GM_LANG_clip_cart;
		}
		return $this->pagetitle;
	}
	
	protected function GetTitle() {
			
		if (is_null($this->title)) {
			$this->title = GM_LANG_clippings_cart;
		}
		return $this->title;
	}
	
	private function IdInCart($id) {
		
		if (!isset($this->cart[GedcomConfig::$GEDCOMID])) return false;
		$ct = count($this->cart[GedcomConfig::$GEDCOMID]);
		for($i=0; $i<$ct; $i++) {
			$temp = $this->cart[GedcomConfig::$GEDCOMID][$i];
			if ($temp['id']==$id) {
				return true;
			}
		}
		return false;
	}
	
	private function AddClipping($clipping) {
		
		if (($clipping['id']==false)||($clipping['id']=="")) return false;
	
		if (!$this->IdInCart($clipping['id'])) {
			if ($clipping['type']=="indi") {
				$person =& Person::GetInstance($clipping['id'], "", GedcomConfig::$GEDCOMID);
				if ($person->disp_name) {
					$this->cart[GedcomConfig::$GEDCOMID][]=$clipping;
					$gedrec = $person->gedrec;
					$st = preg_match_all("/\d (\w+) @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$st; $i++) {
						$clipping = array();
						$clipping['type'] = strtolower($match[$i][1]);
						if (in_array($clipping['type'], array("note", "sour", "repo", "obje"))) {
							$clipping['id'] = $match[$i][2];
							$object =& ConstructObject($clipping['id'], $clipping['type']);
							if ($object->disp) {
								$this->AddClipping($clipping);
							}
						}
					}
				}
				else return false;
			}
			else if ($clipping['type']=="fam") {
				$family =& Family::GetInstance($clipping['id'], "", GedcomConfig::$GEDCOMID);
				if ((!is_object($family->husb) || $family->husb->disp_name) && (!is_object($family->wife) || $family->wife->disp_name)) {
					$this->cart[GedcomConfig::$GEDCOMID][]=$clipping;
					$gedrec = $family->gedrec;
					$st = preg_match_all("/\d (\w+) @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$st; $i++) {
						$clipping = array();
						$clipping['type'] = strtolower($match[$i][1]);
						if (in_array($clipping['type'], array("note", "sour", "repo", "obje"))) {
							$clipping['id'] = $match[$i][2];
							$object =& ConstructObject($clipping['id'], $clipping['type']);
							if ($object->disp) {
								$this->AddClipping($clipping);
							}
						}
					}
				}
				else return false;
			}
			else {
				$object =& ConstructObject($clipping['id'], $clipping['type']);
				if ($object->disp) {
					$this->cart[GedcomConfig::$GEDCOMID][] = $clipping;
					$gedrec = $object->gedrec;
					$nt = preg_match_all("/\d (\w+) @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$nt; $i++) {
						$clipping = array();
						$clipping['type'] = strtolower($match[$i][1]);
						if (in_array($clipping['type'], array("note", "sour", "repo", "obje"))) {
							$clipping['id'] = $match[$i][2];
							$this->AddClipping($clipping);
						}
					}
				}
			}
		}
		return true;
	}
	
	// --------------------------------- Recursive function to traverse the tree
	private function AddFamilyDescendancy($famid) {
	
		if (!$famid) return;
		//print "add_family_descendancy(" . $famid . ")<br />";					# --------------
		$family =& Family::GetInstance($famid);
		if (is_object($family)) {
			if ($family->husb_id != "") {
				$clipping = array();
				$clipping['type'] = "indi";
				$clipping['id'] = $family->husb_id;
				$this->AddClipping($clipping);
			}
			if ($family->wife_id != "") {
				$clipping = array();
				$clipping['type'] = "indi";
				$clipping['id'] = $family->wife_id;
				$this->AddClipping($clipping);
			}
			foreach($family->children as $key => $child) {
				foreach ($child->spousefamilies as $key2 => $schildfamily) {
					if (!$this->IdInCart($schildfamily->xref)) {
						$clipping = array();
						$clipping['type'] = "fam";
						$clipping['id'] = $schildfamily->xref;
						$ret = $this->AddClipping($clipping);		// add the childs family
						$this->AddFamilyDescendancy($schildfamily->xref);	// recurse on the childs family
					}
				}
				if (count($child->spousefamilies) == 0) {
					$clipping = array();
					$clipping['type'] = "indi";
					$clipping['id'] = $child->xref;
					$this->AddClipping($clipping);
				}
			}
		}
	}
	
	private function AddFamilyMembers($famid) {
		
		$family =& Family::GetInstance($famid);
		if ($family->husb_id != "") {
			$clipping = array();
			$clipping['type'] = "indi";
			$clipping['id'] = $family->husb_id;
			$this->AddClipping($clipping);
		}
		if ($family->wife_id != "") {
			$clipping = array();
			$clipping['type'] = "indi";
			$clipping['id'] = $family->wife_id;
			$this->AddClipping($clipping);
		}
		foreach ($family->children_ids as $key => $childid) {
			$clipping = array();
			$clipping['type'] = "indi";
			$clipping['id'] = $childid;
			$this->AddClipping($clipping);
		}
	}
	
	//-- recursively adds direct-line ancestors to cart
	private function AddAncestorsToCart($pid) {
		
		$person =& Person::GetInstance($pid);
		foreach ($person->childfamilies as $key => $family) {
			$clipping = array();
			$clipping['type'] = "fam";
			$clipping['id'] = $family->xref;
			$ret = $this->AddClipping($clipping);
			if ($ret) {
				if ($family->husb_id != "") {
					$clipping = array();
					$clipping['type'] = "indi";
					$clipping['id'] = $family->husb_id;
					$this->AddClipping($clipping);
					$this->AddAncestorsToCart($family->husb_id);
				}
				if ($family->wife_id != "") {
					$clipping = array();
					$clipping['type'] = "indi";
					$clipping['id'] = $family->wife_id;
					$this->AddClipping($clipping);
					$this->AddAncestorsToCart($family->wife_id);
				}
			}
		}
	}
	
	//-- recursively adds direct-line ancestors and their families to the cart
	private function AddAncestorsToCartFamilies($pid) {
		
		$person =& Person::GetInstance($pid);
		foreach ($person->childfamilies as $key => $family) {
			$clipping = array();
			$clipping['type'] = "fam";
			$clipping['id'] = $family->xref;
			$ret = $this->AddClipping($clipping);
			if ($ret) {
				if ($family->husb_id != "") {
					$clipping = array();
					$clipping['type'] = "indi";
					$clipping['id'] = $family->husb_id;
					$ret = $this->AddClipping($clipping);
					$this->AddAncestorsToCartFamilies($family->husb_id);
				}
				if ($family->wife_id != "") {
					$clipping = array();
					$clipping['type'] = "indi";
					$clipping['id'] = $family->wife_id;
					$ret = $this->AddClipping($clipping);
					$this->AddAncestorsToCartFamilies($family->wife_id);
				}
				foreach ($family->children_ids as $key2 => $childid) {
					$clipping = array();
					$clipping['type'] = "indi";
					$clipping['id'] = $childid;
					$this->AddClipping($clipping);
				}
			}
		}
	}
	
	public function PerformAction() {
		
		if ($this->action == 'add1') {
			$clipping = array();
			$clipping['type'] = $this->type;
			$clipping['id'] = $this->id;
			$ret = $this->AddClipping($clipping);
			if ($ret) {
				if ($this->type == 'fam') {
					if ($this->others == 'parents') {
						$family =& Family::GetInstance($this->id);
						if ($family->husb_id != "") {
							$clipping = array();
							$clipping['type'] = "indi";
							$clipping['id'] = $family->husb_id;
							$ret = $this->AddClipping($clipping);
						}
						if ($family->wife_id != "") {
							$clipping = array();
							$clipping['type'] = "indi";
							$clipping['id'] = $family->wife_id;
							$ret = $this->AddClipping($clipping);
						}
					}
					else if ($this->others == "members") {
						$this->AddFamilyMembers($this->id);
					}
					else if ($this->others == "descendants") {
						$this->AddFamilyDescendancy($this->id);
					}
				}
				else if ($this->type == 'indi') {
					if ($this->others == 'parents') {
						$person =& Person::GetInstance($this->id);
						foreach($person->childfamilies as $key => $childfamily) {
							$clipping = array();
							$clipping['type'] = "fam";
							$clipping['id'] = $childfamily->xref;
							$ret = $this->AddClipping($clipping);
							if ($ret) $this->AddFamilyMembers($childfamily->xref);
						}
					}
					else if ($this->others == 'ancestors') {
						$this->AddAncestorsToCart($this->id);
					}
					else if ($this->others == 'ancestorsfamilies') {
						$this->AddAncestorsToCartFamilies($this->id);
					}
					else if ($this->others == 'members') {
						$person =& Person::GetInstance($this->id);
						foreach ($person->spousefamilies as $key => $spousefamily) {
							$clipping = array();
							$clipping['type'] = "fam";
							$clipping['id'] = $spousefamily->xref;
							$ret = $this->AddClipping($clipping);
							if ($ret) $this->AddFamilyMembers($spousefamily->xref);
						}
					}
					else if ($this->others == 'descendants') {
						$person =& Person::GetInstance($this->id);
						foreach ($person->spousefamilies as $key => $spousefamily) {
							$clipping = array();
							$clipping['type'] = "fam";
							$clipping['id'] = $spousefamily->xref;
							$ret = $this->AddClipping($clipping);
							if ($ret) $this->AddFamilyDescendancy($spousefamily->xref);
						}
					}
				}
			}
		}
		else if($this->action == 'remove') {
			$ct = count($this->cart[GedcomConfig::$GEDCOMID]);
			for($i=$item+1; $i<$ct; $i++) {
				$this->cart[GedcomConfig::$GEDCOMID][$i-1] = $this->cart[GedcomConfig::$GEDCOMID][$i];
			}
			unset($this->cart[$ct-1]);
		}
		else if($this->action == 'empty') {
			$this->cart[GedcomConfig::$GEDCOMID] = array();
			$_SESSION["clippings"] = "";
		}
		else if($this->action == 'download') {
			$path = substr(SCRIPT_NAME, 0, strrpos(SCRIPT_NAME, "/"));
			if (empty($path)) $path="/";
			if ($path[strlen($path)-1]!="/") $path .= "/";
			if (substr(SERVER_URL, strlen(SERVER_URL) - 1) == "/") {
			  $dSERVER_URL = substr(SERVER_URL, 0, strlen(SERVER_URL) - 1);
			}
			else $dSERVER_URL = SERVER_URL;
			usort($this->cart[GedcomConfig::$GEDCOMID], "SameGroup");
			$ct = count($this->cart[GedcomConfig::$GEDCOMID]);
			
			$filetext = "0 HEAD\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS ".GM_VERSION." ".GM_VERSION_RELEASE."\r\n1 DEST DISKETTE\r\n1 DATE ".date("j M Y")."\r\n2 TIME ".date("h:i:s")."\r\n";
			$filetext .= "1 GEDC\r\n2 VERS 5.5\r\n2 FORM LINEAGE-LINKED\r\n1 CHAR ".GedcomConfig::$CHARACTER_SET."\r\n";
			
			$header =& Header::GetInstance("HEAD");
			$filetext .= "1 PLAC\r\n2 FORM ";
			if ($header->placeformat != "") $filetext .= $header->placeformat."\r\n";
			else $filetext .= "City, County, State/Province, Country"."\r\n";
			
			if ($this->convert == "yes") {
				$filetext = preg_replace("/UTF-8/", "ANSI", $filetext);
				$filetext = utf8_decode($filetext);
			}
			for($i=0; $i<$ct; $i++)	{
				$clipping = $this->cart[GedcomConfig::$GEDCOMID][$i];
				$object =& ConstructObject($clipping['id'], $clipping['type']);
				$record = $object->oldprivategedrec;
				$record = RemoveCustomTags($record, $this->remove);
				if ($this->convert == "yes") $record = utf8_decode($record);
				if ($clipping['type'] == 'indi') {
					$famid = $object->primaryfamily;
					if (!$this->IdInCart($famid)) {
						$record = preg_replace("/1 FAMC @".$famid."@.*/", "", $record);
					}
					foreach ($object->fams as $key => $famid) {
						if (!$this->IdInCart($famid)) {
							$record = preg_replace("/1 FAMS @".$famid."@.*/", "", $record);
						}
					}
					$filetext .= trim($record)."\r\n";
					$filetext .= "1 SOUR @SGM1@\r\n";
					$filetext .= "2 PAGE ".$dSERVER_URL."/individual.php?pid=".$clipping['id']."\r\n";
					$filetext .= "2 DATA\r\n";
					$filetext .= "3 TEXT ".GM_LANG_indi_downloaded_from."\r\n";
					$filetext .= "4 CONT ".$dSERVER_URL."/individual.php?pid=".$clipping['id']."&gedid=".GedcomConfig::$GEDCOMID."\r\n";
				}
				else if ($clipping['type'] == 'fam') {
					foreach ($object->children_ids as $key => $childid) {
						 if (!$this->IdInCart($childid)) {
						   /* if the child is not in the list delete the record of it */
						   $record = preg_replace("/1 CHIL @".$childid."@.*/", "", $record);
						 }
					}
					if ($object->husb_id != "" && !$this->IdInCart($object->husb_id)) $record = preg_replace("/1 HUSB @".$object->husb_id."@.*/", "", $record);
					if ($object->wife_id != "" && !$this->IdInCart($object->wife_id)) $record = preg_replace("/1 HUSB @".$object->wife_id."@.*/", "", $record);
		
					$filetext .= trim($record)."\r\n";
					$filetext .= "1 SOUR @SGM1@\r\n";
					$filetext .= "2 PAGE ".$dSERVER_URL.$path."family.php?famid=".$clipping['id']."&gedid=".GedcomConfig::$GEDCOMID."\r\n";
					$filetext .= "2 DATA\r\n";
					$filetext .= "3 TEXT ".GM_LANG_family_downloaded_from."\r\n";
					$filetext .= "4 CONT ".$dSERVER_URL."/family.php?famid=".$clipping['id']."&gedid=".GedcomConfig::$GEDCOMID."\r\n";
				}
				else if($clipping['type'] == "sour") {
					$filetext .= trim($record)."\r\n";
					$filetext .= "1 NOTE ".GM_LANG_source_downloaded_from."\r\n";
					$filetext .= "2 CONT ".$dSERVER_URL."/source.php?sid=".$clipping['id']."&gedid=".GedcomConfig::$GEDCOMID."\r\n";
				}
				else if($clipping['type'] == "repo") {
					$filetext .= trim($record)."\r\n";
					$filetext .= "1 NOTE ".GM_LANG_repo_downloaded_from."\r\n";
					$filetext .= "2 CONT ".$dSERVER_URL."/repo.php?rid=".$clipping['id']."&gedid=".GedcomConfig::$GEDCOMID."\r\n";
				}
				else if($clipping['type'] == "note") {
					$filetext .= trim($record)."\r\n";
					$filetext .= "1 CONT ".GM_LANG_note_downloaded_from."\r\n";
					$filetext .= "1 CONT ".$dSERVER_URL."/note.php?oid=".$clipping['id']."&gedid=".GedcomConfig::$GEDCOMID."\r\n";
				}
				else if($clipping['type'] == "obje") {
					$ft = preg_match_all("/\d FILE (.*)/", $record, $match, PREG_SET_ORDER);
					for ($k=0; $k<$ft; $k++) {
						$filename = MediaFS::CheckMediaDepth($match[$k][1]);
						 	$this->media[$this->mediacount] = $filename;
						 	$this->mediacount++;
					   	 	$record = preg_replace("@(\d FILE )".addslashes($match[$k][1])."@", "$1".$filename, $record);
					}
					$filetext .= trim($record)."\r\n";
					$filetext .= "1 NOTE ".GM_LANG_media_downloaded_from."\r\n";
					$filetext .= "2 CONT ".$dSERVER_URL."/mediadetail.php?mid=".$clipping['id']."&gedid=".GedcomConfig::$GEDCOMID."\r\n";
				}
				else $filetext .= trim($record)."\r\n";
			}
			$filetext .= "0 @SGM1@ SOUR\r\n";
			$tuser =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
			if ($tuser) {
				$filetext .= "1 AUTH ".$tuser->firstname." ".$tuser->lastname."\r\n";
			}
			$filetext .= "1 TITL ".GedcomConfig::$HOME_SITE_TEXT."\r\n";
			$filetext .= "1 ABBR ".GedcomConfig::$HOME_SITE_TEXT."\r\n";
			$filetext .= "1 PUBL ".GedcomConfig::$HOME_SITE_URL."\r\n";
			$filetext .= "0 TRLR\r\n";
			//-- make sure the gedcom doesn't have any empty lines
			$filetext = preg_replace("/(\r?\n)+/", "\r\n", $filetext);
			//-- make sure DOS line endings are used
			$filetext = preg_replace("/\r?\n/", "\r\n", $filetext);
		
			$_SESSION["clippings"] = $filetext;
		}
	}		
}
?>
