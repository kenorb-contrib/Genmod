<?php
/**
 * Controller for the indilist Page
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
 * @subpackage Charts
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
class IndilistController extends ListController {
	
	public $classname = "IndilistController";	// Name of this class
	
	// Parameters
	public $alpha = null;
	public $surname = null;
	public $surname_sublist = null;
	public $show_all = null;
	public $show_all_firstnames = null;
	public $allgeds = null;
	
	
	public function __construct() {
		global $ALLOW_CHANGE_GEDCOM, $gm_user;
		
		global $GEDCOM_DEFAULT_TAB, $USE_RIN, $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $nonfacts, $nonfamfacts;
		global $ENABLE_CLIPPINGS_CART, $show_changes, $gm_user, $SHOW_ID_NUMBERS;
		
		parent::__construct();
		
		// Get the incoming letter
		if (isset($_GET["alpha"])) $this->alpha = $_GET["alpha"];
		if (is_null($this->alpha)) $this->alpha = "";
		
		// Get the incoming surname
		if (isset($_GET["surname"])) $this->surname = $_GET["surname"];
		if ($this->alpha == "" && $this->surname != "") $this->alpha = GetFirstLetter(StripPrefix($this->surname));
		
		// Get the sublist switch
		if (isset($_GET["surname_sublist"])) $this->surname_sublist = $_GET["surname_sublist"];
		if (is_null($this->surname_sublist)) $this->surname_sublist = "yes";
		
		if (isset($_GET["show_all"])) $this->show_all = $_GET["show_all"];
		if (is_null($this->show_all)) $this->show_all = "no";
		
		if (!isset($_GET["allgeds"]) || $$_GET["allgeds"] != "yes" || !$ALLOW_CHANGE_GEDCOM) $this->allgeds = "no";
		else $this->allgeds = "yes";
		
		if ($this->allgeds == "yes" && $gm_user->username == "") {
			if (GedcomConfig::AnyGedcomHasAuth()) $this->allgeds = "no";
		}
		

	}

	public function __get($property) {
		switch($property) {
			default:
				return parent::__get($property);
				break;
		}
	}
	
	
	protected function GetPageTitle() {
		global $gm_lang, $SHOW_ID_NUMBERS;

		if (is_null($this->pagetitle)) {
			if ($this->indi->disp) $this->pagetitle = $this->indi->name;
			else $this->pagetitle = $gm_lang["private"];
			
			if ($SHOW_ID_NUMBERS) $this->pagetitle .= " - ".$this->indi->xref;
			$this->pagetitle .= " - ".$gm_lang["indi_info"];
		}
		return $this->pagetitle;
	}
		
			
	 /**
	 * Get all first letters of individual's last names
	 *
	 * The function takes all the distinct lastname starting letters 
	 * found in both the individual and names table. Then some language specific
	 * letter substitution is done
	 *
	 * @see indilist.php
	 * @author	Genmod Development Team
	 * @return 	array	An array of all letters found in the active gedcom
	 */
	
	public function GetIndiAlpha($allgeds="no") {
		global $LANGUAGE, $GEDCOMID;
		
		$indialpha = array();
		
		$sql = "SELECT DISTINCT n_letter AS alpha ";
		$sql .= "FROM ".TBLPREFIX."names ";
		if ($allgeds == "no") $sql .= "WHERE n_file = '".$GEDCOMID."'";
		$res = NewQuery($sql);
		
		$hungarianex = array("DZS", "CS", "DZ" , "GY", "LY", "NY", "SZ", "TY", "ZS");
		$danishex = array("OE", "AE", "AA");
		while($row = $res->FetchAssoc()){
			$letter = $row["alpha"];
			if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian"){
				if (in_array(strtoupper($letter), $danishex)) {
					if (strtoupper($letter) == "OE") $letter = "Ø";
					else if (strtoupper($letter) == "AE") $letter = "Æ";
					else if (strtoupper($letter) == "AA") $letter = "Å";
				}
			}
			if (strlen($letter) > 1){
				if (ord($letter) < 92){
					if ($LANGUAGE != "hungarian" && in_array($letter, $hungarianex)) $letter = substr($letter, 0, 1);
					if (($LANGUAGE != "danish" || $LANGUAGE != "norwegian") && in_array($letter, $danishex)) $letter = substr($letter, 0, 1);
				}
			}
			$indialpha[]=$letter;
		}
		$res->FreeResult();
		$indialpha = array_flip(array_flip($indialpha));
		uasort($indialpha, "stringsort");
		
		if ($this->alpha != "" && !isset($indialpha[$this->alpha])) $this->alpha = "";
		
		return $indialpha;
	}
}
?>
