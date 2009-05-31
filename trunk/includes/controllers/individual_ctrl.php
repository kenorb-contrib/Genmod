<?php
/**
 * Controller for the Individual Page
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @version $Id: individual_ctrl.php,v 1.29 2006/04/17 20:01:52 roland-d Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require_once("config.php");

/**
 * Inclusion of the base controller file
*/
require_once 'includes/controllers/basecontrol.php';

/**
 * Inclusion of the language files
*/
require_once($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE])) require_once($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE]);

/**
 * Inclusion of the menu file
*/
require_once 'includes/menu.php';

/**
 * Inclusion of the person class file
*/
require_once 'includes/person_class.php';

// -- array of GEDCOM elements that will be found but should not be displayed
$nonfacts[] = "FAMS";
$nonfacts[] = "FAMC";
$nonfacts[] = "MAY";
$nonfacts[] = "BLOB";
$nonfacts[] = "CHIL";
$nonfacts[] = "HUSB";
$nonfacts[] = "WIFE";
$nonfacts[] = "RFN";
$nonfacts[] = "";
$nonfamfacts[] = "UID";
$nonfamfacts[] = "";

/**
 * Main controller class for the individual page.
 */
class IndividualControllerRoot extends BaseController {
	
	var $action = "";
	var $pid = "";
	var $default_tab = 0;
	var $accept_success = false;
	var $accept_change = false;
	var $reject_change = false;
	var $show_changes = "yes";
	var $SEX_COUNT = 0;
	var $name_count = 0;
	var $total_names = 0;
	var $canedit = false;
	var $close_relatives = false;
	
	/**
	 * constructor
	 */
	function IndividualControllerRoot() {
		// NOTE: Parent parent is a reference to the parent class which this class extends.. BaseController in this case 
		// From the line: class IndividualControllerRoot extends BaseController { 
		// The class listed after the extends is referred to as the parent class 
		// so parent::BaseController calls the constructor for the parent class 
		parent::BaseController();
	}
	
	/**
	 * Initialization function
	 */
	function init() {
		global $GEDCOM_DEFAULT_TAB, $USE_RIN, $gm_lang, $SHOW_GEDCOM_RECORD;
		global $ENABLE_CLIPPINGS_CART;
		// NOTE: Determine the person ID
		if (!empty($_REQUEST["pid"])) $this->pid = strtoupper($_REQUEST["pid"]);
		$this->pid = clean_input($this->pid);
		
		// NOTE: Determine which tab should be shown global value
		$this->default_tab = $GEDCOM_DEFAULT_TAB;
		
		// NOTE: Get the persons GEDCOM record
		$indirec = find_person_record($this->pid);
		if (($USE_RIN)&&($indirec==false)) {
			$this->pid = find_rin_id($this->pid);
			$indirec = find_person_record($this->pid);
		}
		if ($indirec == "") {
			$indirec = "0 @".$this->pid."@ INDI\r\n";
		}
		// NOTE: Get the user details
		$this->uname = getUserName();
		if (!empty($this->uname)) {
			$this->user = getUser($this->uname);
			if ($this->user["default_tab"] != $this->default_tab) $this->default_tab = $this->user["default_tab"];
		}
		else {
			$this->user["canedit"] = false;
		}
		// NOTE: Create the person Object
		$this->indi = new Person($indirec);
		
		if (!$this->indi->exist) {
			$this->PageTitle = $gm_lang["person_not_found"];
			return false;
		}
		else {
			//-- perform the desired action
			switch($this->action) {
				case "addfav":
					$this->addFavorite();
					break;
				case "accept":
					$this->indi->acceptChanges();
					break;
				case "reject":
					$this->indi->rejectChanges();
					break;
			}
			
			// NOTE: Display page title
			$this->PageTitle = $this->indi->name." - ".$this->indi->xref." - ".$gm_lang["indi_info"];
			
			// NOTE: Can we display the highlighted object?
			// NOTE: If we can, the HighlightedObject is filled
			$this->canShowHighlightedObject = $this->canShowHighlightedObject();
			
			// NOTE: Can we show the gedcom record?
			if ($SHOW_GEDCOM_RECORD && $this->indi->disp) $this->canShowGedcomRecord = true;
			else $this->canShowGedcomRecord = false;
			
			// NOTE: What menus can we show?
			if ($this->indi->disp && ($SHOW_GEDCOM_RECORD || $ENABLE_CLIPPINGS_CART>=getUserAccessLevel())) 
				$this->show_menu_other = true;
			else $this->show_menu_other = false;
			
			// NOTE: Parse all facts into arrays
			$this->indi->parseFacts();
			
			// NOTE: add_family_facts parses all facts as it calls the parseFacts function
			$this->indi->add_family_facts();
			
			//-- remove any duplicate and sort facts
			$indifacts = $this->indi->indifacts;
			usort($indifacts, "compare_facts");
			
			//-- remove duplicate facts
			$indinewfacts = array();
			foreach ($indifacts as $key => $value) $indinewfacts[$key] = serialize($value[1]);
			$indinewfacts = array_unique($indinewfacts);
			foreach ($indifacts as $key => $value) if (array_key_exists($key, $indinewfacts)) $indinewfacts[$key] = $value;
			$this->indi->indifacts = $indinewfacts;
			
			// NOTE: Determine the number of names and sex records
			foreach ($this->indi->globalfacts as $key => $value) {
				$fact = trim($value[0]);
				if ($fact=="SEX") $this->SEX_COUNT++;
				if ($fact=="NAME") $this->total_names++;
			}
			
			// NOTE: Get the parents and siblings
			$this->indi->getParentFamily($this->pid);
			
			// NOTE: Get the spouses and kids
			$this->indi->getSpouseFamily($this->pid);
		}
		
	}
	//-- end of init function
	
	/**
	 * Check if we can show the highlighted media object
	 * If we can, construct the code for the highlighted object
	 * @return boolean
	 */
	function canShowHighlightedObject() {
		global $MULTI_MEDIA, $SHOW_HIGHLIGHT_IMAGES, $USE_THUMBS_MAIN;
		
		if (($this->indi->disp) && ($MULTI_MEDIA && $SHOW_HIGHLIGHT_IMAGES)) {
			$firstmediarec = $this->indi->findHighlightedMedia();
			if ($firstmediarec) {
				if ($USE_THUMBS_MAIN) $filename = $firstmediarec["thumb"];
				else $filename = $firstmediarec["file"];
				if (empty($filename)) $filename = $firstmediarec["thumb"];
				if (!empty($filename)) {
					if (file_exists($filename)) {
						$imgsize = getimagesize($filename);
						if ($imgsize[1] > 150) $height = 150;
						else $height = $imgsize[1];
					}
					else $height = 150;
					if (file_exists($filename)) $this->HighlightedObject = "<img src=\"$filename\" class=\"image\" height=\"".$height."\" alt=\"".$firstmediarec["file"]."\" />";
					else $this->HighlightedObject = "";
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * get the edit menu
	 * @todo Revise
	 * @return Menu
	 */
	function &getEditMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $TOTAL_NAMES;
		global $NAME_LINENUM, $SEX_LINENUM, $gm_lang;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		//-- main edit menu
		$menu = new Menu($gm_lang["edit"]);
		if (!empty($GM_IMAGES["edit_indi"]["small"]))$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["edit_indi"]["small"]);
		$menu->addClass("submenuitem$ff", "submenuitem_hover$ff", "submenu$ff");
		if (!record_locked($this->pid)) {
			$menu->addOnclick("return quickEdit('".$this->pid."');");
			// NOTE: Quickedit sub menu
			$submenu = new Menu($gm_lang["quick_update_title"]);
			$submenu->addOnclick("return quickEdit('".$this->pid."', '', 'edit_quickupdate');");
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
			if ($this->user["canedit"]) {
				// NOTE: Raw editing
				$submenu = new Menu($gm_lang["edit_raw"]);
				$submenu->addOnclick("return edit_raw('".$this->pid."', 'edit_raw');");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				// NOTE: Reorder families
				if (count($this->indi->getSpouseFamilyIds())>0) {
					$submenu = new Menu($gm_lang["reorder_families"]);
					$submenu->addOnclick("return reorder_families('".$this->pid."', 'reorder_families');");
					$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
					$menu->addSubmenu($submenu);
				}
				// NOTE: Delete person
				$submenu = new Menu($gm_lang["delete_person"]);
				$submenu->addOnclick("if (confirm('".$gm_lang["confirm_delete_person"]."')) return deleteperson('".$this->pid."', 'delete_person'); else return false;");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				$menu->addSeperator();
				// NOTE: Edit name
				if ($TOTAL_NAMES<2) {
					$submenu = new Menu($gm_lang["edit_name"]);
					$submenu->addOnclick("return edit_name('".$this->pid."', 'NAME', 1, 'edit_name');");
					$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
					$menu->addSubmenu($submenu);
				}
				// NOTE: Add name
				$submenu = new Menu($gm_lang["add_name"]);
				$submenu->addOnclick("return add_name('".$this->pid."', 'add_name');");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				// NOTE: Gender
				if ($this->SEX_COUNT<2) {
					$submenu = new Menu($gm_lang["edit"]." ".$gm_lang["sex"]);
					if ($SEX_LINENUM=="new") $submenu->addOnclick("return add_new_record('".$this->pid."', 'SEX', 'sex_edit');");
					else $submenu->addOnclick("return edit_record('".$this->pid."', 1, 'edit_gender');");
					$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
					$menu->addSubmenu($submenu);
				}
			}
		}
		if (change_present($this->pid, true)) {
			$menu->addSeperator();
			if ($this->show_changes=="no") {
				$label = $gm_lang["show_changes"];
				$link = "individual.php?pid=".$this->pid."&amp;show_changes=yes";
			}
			else {
				$label = $gm_lang["hide_changes"];
				$link = "individual.php?pid=".$this->pid."&amp;show_changes=no";
			}
			$submenu = new Menu($label, $link);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
			
			if (userCanAccept($this->uname)) {
				$submenu = new Menu($gm_lang["accept_all"], "individual.php?pid=".$this->pid."&amp;action=accept");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				$submenu = new Menu($gm_lang["reject_all"], "individual.php?pid=".$this->pid."&amp;action=reject");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
			}
		}
		return $menu;
	}
	
	/**
	 * get the "other" menu
	 * @todo Revise
	 * @return Menu
	 */
	function &getOtherMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $THEME_DIR, $GM_BASE_DIRECTORY;
		global $SHOW_GEDCOM_RECORD, $ENABLE_CLIPPINGS_CART, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		//-- main other menu item
		$menu = new Menu($gm_lang["other"]);
		if ($SHOW_GEDCOM_RECORD) {
			if (!empty($GM_IMAGES["gedcom"]["small"]))
				$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["small"]);
			if ($this->show_changes=="yes"  && $this->user["canedit"])
				$menu->addOnclick("return show_gedcom_record('new');");
			else
				$menu->addOnclick("return show_gedcom_record('');");
		}
		else {
			if (!empty($GM_IMAGES["clippings"]["small"]))
				$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["clippings"]["small"]);
			$menu->addLink("clippings.php?action=add&amp;id=".$this->pid."&amp;type=indi");
		}
		$menu->addClass("submenuitem$ff", "submenuitem_hover$ff", "submenu$ff");
		if ($this->canShowGedcomRecord) {
			$submenu = new Menu($gm_lang["view_gedcom"]);
			if (!empty($GM_IMAGES["gedcom"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["small"]);
			if ($this->show_changes=="yes"  && $this->user["canedit"]) $submenu->addOnclick("return show_gedcom_record('new');");
			else $submenu->addOnclick("return show_gedcom_record();");
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		if ($this->indi->disp && $ENABLE_CLIPPINGS_CART>=getUserAccessLevel()) {
			$submenu = new Menu($gm_lang["add_to_cart"], "clippings.php?action=add&amp;id=".$this->pid."&amp;type=indi");
			if (!empty($GM_IMAGES["clippings"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["clippings"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		if ($this->indi->disp && !empty($this->uname)) {
			$submenu = new Menu($gm_lang["add_to_my_favorites"], "individual.php?action=addfav&amp;pid=".$this->pid."&amp;gid=".$this->pid);
			if (!empty($GM_IMAGES["gedcom"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * print information for a name record
	 *
	 * Called from the individual information page
	 * @see individual.php
	 * @param string $factrec	the raw gedcom record of the name to print
	 * @param int $linenum		the line number from the original INDI gedcom record where this name record started, used for editing
	 */
	function print_name_record($factrec, $count) {
		global $gm_lang, $factarray, $NAME_REVERSE;
		
		if ((!showFact("NAME", $this->pid))||(!showFactDetails("NAME", $this->pid))) return false;
		
		$lines = split("\n", $factrec);
		$this->name_count++;
		
		// NOTE: If there is more than one name, print the aka tag
		if ($this->name_count>1) print "\n\t\t<span class=\"label\">".$gm_lang["aka"]." </span><br />\n";
		
		$ct = preg_match_all("/2 (SURN)|(GIVN) (.*)/", $factrec, $nmatch, PREG_SET_ORDER);
		if ($ct==0) {
			$nt = preg_match("/1 NAME (.*)/", $factrec, $nmatch);
			if ($nt>0){
				print "\n\t\t<span class=\"label\">".$gm_lang["name"].": </span><br />";
				$name = trim($nmatch[1]);
				if ($NAME_REVERSE) $name = reverse_name($name);
				$name = preg_replace("'/,'", ",", $name);
	   			$name = preg_replace("'/'", " ", $name);
				// handle PAF extra NPFX [ 961860 ]
				$ct = preg_match("/2 NPFX (.*)/", $factrec, $match);
				if ($ct>0) {
					$npfx = trim($match[1]);
					if (strpos($name, $npfx)===false) $name = $npfx." ".$name;
				}
				print PrintReady($name)."<br />\n";
			}
		}
		$ct = preg_match_all("/\n2 (\w+) (.*)/", $factrec, $nmatch, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$fact = trim($nmatch[$i][1]);
			if (($fact!="SOUR")&&($fact!="NOTE")) {
				print "\n\t\t\t<span class=\"label\">";
				if (isset($gm_lang[$fact])) print $gm_lang[$fact];
				else if (isset($factarray[$fact])) print $factarray[$fact];
				else print $fact;
				print ":</span><span class=\"field\"> ";
				if (isset($nmatch[$i][2])) {
			  		$name = trim($nmatch[$i][2]);
			  		$name = preg_replace("'/,'", ",", $name);
					$name = preg_replace("'/'", " ", $name);
					print PrintReady(check_NN($name));
				}
				print " </span><br />";
			}
		}
		if ($this->total_names>1 && !$this->isPrintPreview() && $this->canedit) {
			if ($this->name_count==2) print_help_link("delete_name_help", "qm", "delete_name");
	   		print "&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"font9\" onclick=\"edit_name('".$this->pid."', 'NAME', '".$count."', 'edit_name'); return false;\">".$gm_lang["edit_name"]."</a> | ";
			print "<a class=\"font9\" href=\"#\" onclick=\"delete_record('".$this->pid."', 'NAME', '".$count."', 'delete_name'); return false;\">".$gm_lang["delete_name"]."</a>\n";
			print "<br />\n";
		}
		$ct = preg_match("/\d (NOTE)|(SOUR)/", $factrec);
		if ($ct>0) {
			// -- find sources for this name
			print "<div class=\"indent\">";
			print_fact_sources($factrec, 2);
			//-- find the notes for this name
			print "&nbsp;&nbsp;&nbsp;";
			print_fact_notes($factrec, 2);
			print "</div><br />";
		}
		print "</td>\n";
	}
	
	/**
	 * print information for a sex record
	 *
	 * Called from the individual information page
	 * @see individual.php
	 * @param string $factrec	the raw gedcom record to print
	 * @param int $linenum		the line number from the original INDI gedcom record where this sex record started, used for editing
	 */
	function gender_record($factrec, $linenum) {
		global $gm_lang, $sex, $GM_IMAGE_DIR, $GM_IMAGES;
		
		if ((!showFact("SEX", $this->pid))||(!showFactDetails("SEX", $this->pid))) return false;
		
		$this->indi->sexdetails["add"] = false;
		$ft = preg_match("/\d\s(\w+)(.*)/", $factrec, $match);
		$sex = strtoupper(trim($match[2]));
		if (empty($sex)) $sex = "U";
		switch ($sex) {
			case "M":
				$this->indi->sexdetails["gender"] = $gm_lang["male"];
				$this->indi->sexdetails["image"] = $GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"];
				break;
			case "F":
				$this->indi->sexdetails["gender"] = $gm_lang["female"];
				$this->indi->sexdetails["image"] = $GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"];
				break;
			case "U":
				$this->indi->sexdetails["gender"] = $gm_lang["unknown"];
				$this->indi->sexdetails["image"] = $GM_IMAGE_DIR."/".$GM_IMAGES["sexn"]["small"];
				break;
		}
		if ($this->SEX_COUNT>1) {
			if ((!$this->isPrintPreview()) && ($this->canedit) && (preg_match("/GM_OLD/", $factrec)==0)) {
				if ($linenum=="new") $this->indi->sexdetails["add"] = true;
			}
		}
	}
	
	/**
	 * Add a new favorite for the action user
	 */
	function addFavorite() {
		global $GEDCOM;
		if (empty($this->uname)) return;
		if (!empty($_REQUEST["gid"])) {
			$gid = strtoupper($_REQUEST["gid"]);
			$indirec = find_person_record($gid);
			if ($indirec) {
				$favorite = array();
				$favorite["username"] = $this->uname;
				$favorite["gid"] = $gid;
				$favorite["type"] = "INDI";
				$favorite["file"] = $GEDCOM;
				$favorite["url"] = "";
				$favorite["note"] = "";
				$favorite["title"] = "";
				addFavorite($favorite);
			}
		}
	}
	
	/**
	 * get the person box stylesheet class
	 * for the given person
	 * @param Person $person
	 * @return string	returns 'person_box', 'person_boxF', or 'person_boxNN'
	 */
	function getPersonStyle(&$person) {
		$sex = $person->getSex();
		switch($sex) {
			case "M":
				$isf = "";
				break;
			case "F":
				$isf = "F";
				break;
			default:
				$isf = "NN";
				break;
		}
		return "person_box".$isf;
	}
}
// -- end of class
//-- load a user extended class if one exists
if (file_exists('includes/controllers/individual_ctrl_user.php')) {
	include_once 'includes/controllers/individual_ctrl_user.php';
}
else {
	class IndividualController extends IndividualControllerRoot	{
		
	}
}
$controller = new IndividualController();
$controller->init();
?>
