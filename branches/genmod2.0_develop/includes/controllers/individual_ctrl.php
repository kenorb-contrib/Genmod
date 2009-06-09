<?php
/**
 * Controller for the Individual Page
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
class IndividualController extends BaseController {
	
	var $classname = "IndividualController";
	var $action = "";
	var $pid = "";
	var $default_tab = 0;
	var $show_changes = false;
	var $SEX_COUNT = 0;
	var $name_count = 0;
	var $TOTAL_NAMES = 0;
	var $canedit = false;
	var $caneditown = false;
	var $close_relatives = false;
	var $newindi = false;
	var $PageTitle = "";
	var $show_menu_other = false;
	var $show_menu_edit = false;
	var $indi_username = "";
	var $user = array();
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $GEDCOM_DEFAULT_TAB, $USE_RIN, $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $nonfacts, $nonfamfacts;
		global $ENABLE_CLIPPINGS_CART, $show_changes, $GEDCOM, $gm_username, $Users, $SHOW_ID_NUMBERS;
		
		
		// NOTE: Parent parent is a reference to the parent class which this class extends.. BaseController in this case 
		// From the line: class IndividualControllerRoot extends BaseController { 
		// The class listed after the extends is referred to as the parent class 
		// so parent::BaseController calls the constructor for the parent class 
		parent::__construct();

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
		$nonfamfacts[] = "RESN";

		
				
		// NOTE: Determine the person ID
		if (!empty($_REQUEST["pid"])) $this->pid = strtoupper($_REQUEST["pid"]);
		$this->pid = CleanInput($this->pid);

		if (!empty($_REQUEST["action"])) $this->action = $_REQUEST["action"];
				
		if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEditOwn($gm_username, $this->pid)) $this->show_changes = true;
		// NOTE: Determine which tab should be shown global value
		$this->default_tab = $GEDCOM_DEFAULT_TAB;
		
		// NOTE: Get the user details
		$this->uname = $Users->GetUserName();
		if (!empty($this->uname)) {
			$this->user = $Users->getUser($this->uname);
			if ($this->user->default_tab != $this->default_tab && $this->user->default_tab != 9) $this->default_tab = $this->user->default_tab;
			// Only display the user link for authenticated users
			$this->indi_username = $Users->getUserByGedcomId($this->pid, $GEDCOM);
			$link = "";
			if ($this->indi_username) {
				if ($Users->UserIsAdmin($gm_username)) $link = "<a href=\"useradmin.php?action=edituser&username=".$this->indi_username."\">";
				$link .= "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"".PrintReady($gm_lang["gm_username"]." ".$this->indi_username)."\" />";
				if ($Users->UserIsAdmin($gm_username)) $link .= "</a>";
				$this->indi_username = $link;
			}
		}
		if ($Users->UserCanEdit($gm_username)) $this->canedit = true;
		
		// Note this is for Quick Update
		if ($Users->UserCanEditOwn($gm_username, $this->pid)) $this->caneditown = true;

		// NOTE: Get the persons GEDCOM record
		$indirec = FindPersonRecord($this->pid);
		if (($USE_RIN)&&($indirec==false)) {
			$this->pid = FindRinId($this->pid);
			$indirec = FindPersonRecord($this->pid);
		}
		
		// Recheck editing permissions based on 1 RESN. This will only affect the editing menu
		if (FactEditRestricted($this->pid, $indirec, 1)) {
			$this->canedit = false;
			$this->caneditown = false;
		}
		
		if(empty($indirec) && $this->show_changes) {
//			$rec = GetChangeData(false, $this->pid, true, "gedlines", "INDI");
			$rec = GetChangeData(false, $this->pid, true, "gedlines", "");
			if (isset($rec[$GEDCOM][$this->pid])) {
				$indirec = $rec[$GEDCOM][$this->pid];
				$this->newindi = true;
			}
			else {
				$rec = GetChangeData(false, $this->pid, true, "gedlines", "FAMC");
				if (isset($rec[$GEDCOM][$this->pid])) {
					$indirec = $rec[$GEDCOM][$this->pid];
					$this->newindi = true;
				}
			}
		}
		if ($indirec == "") {
			$indirec = "0 @".$this->pid."@ INDI\r\n";
		}
		
		// NOTE: Create the person Object
		if ($this->newindi) $this->indi = new Person($this->pid, $indirec, true);
		else $this->indi = new Person($this->pid, $indirec);
	//	print_r($this->indi);
		$this->close_relatives = $this->indi->close_relatives;
		
		if ($this->indi->isempty) {
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
			if ($this->indi->disp) {
				$this->PageTitle = $this->indi->name;
			} else {
				$this->PageTitle = $gm_lang["private"];
			}
			if ($SHOW_ID_NUMBERS) {
				$this->PageTitle .= " - ".$this->indi->xref;
			}
			$this->PageTitle .= " - ".$gm_lang["indi_info"];
			
			// NOTE: Can we display the highlighted object?
			// NOTE: If we can, the HighlightedObject is filled
			$this->canShowHighlightedObject = $this->canShowHighlightedObject();
			
			// NOTE: Can we show the gedcom record?
			if ($Users->userCanViewGedlines() && $this->indi->disp) $this->canShowGedcomRecord = true;
			else $this->canShowGedcomRecord = false;
			
			// NOTE: Can we edit the gedcom record?
			if ($Users->userCanEditGedlines() && $this->indi->disp) $this->canEditGedcomRecord = true;
			else $this->canEditGedcomRecord = false;
			
			// NOTE: What menus can we show?
			if ($this->indi->disp && (!empty($this->uname) || $Users->userCanViewGedlines() || $ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel())) $this->show_menu_other = true;
			if (!$this->indi->isempty && !$this->indi->indideleted && ($this->canedit || $this->caneditown)) $this->show_menu_edit = true;
			
			// NOTE: Parse all facts into arrays
			$this->indi->parseFacts();
			
			// NOTE: add_family_facts parses all facts as it calls the parseFacts function
			$this->indi->add_family_facts();
			
			//-- remove any duplicate and sort facts
			$indifacts = $this->indi->indifacts;
//			print_r($indifacts);
//			usort($indifacts, "CompareFacts");
			SortFacts($indifacts);

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
				if ($fact=="NAME") $this->TOTAL_NAMES++;
			}
			
			// NOTE: Get the parents and siblings
			$this->indi->getParentFamily($this->pid);
			
			// NOTE: Get the spouses and kids
			$this->indi->getSpouseFamily($this->pid);
			
			// NOTE: Get the parents other families
			$this->indi->getParentOtherFamily($this->pid);
		}
	}
	//-- end of construct
	
	/**
	 * Check if we can show the highlighted media object
	 * If we can, construct the code for the highlighted object
	 * @return boolean
	 */
	function canShowHighlightedObject() {
		global $SHOW_HIGHLIGHT_IMAGES, $USE_THUMBS_MAIN, $MEDIA_DIRECTORY;

		if ($this->indi->disp && $SHOW_HIGHLIGHT_IMAGES && ShowFact("OBJE", $this->pid, "OBJE")) {
			$firstmediarec = $this->indi->findHighlightedMedia();
			if ($firstmediarec) {
				// new from here
				$media = new MediaItem($firstmediarec["id"]);
				if ($USE_THUMBS_MAIN && $firstmediarec["use_thum"] != "Y") $filename = $media->m_fileobj->f_thumb_file;
				else $filename = $media->m_fileobj->f_main_file;
				if ($media->m_fileobj->f_height != 0 && $media->m_fileobj->f_height < 150) $height = $media->m_fileobj->f_height;
				else $height = 150;
				if ($media->m_fileobj->f_file_exists) $this->HighlightedObject = '<img src="'.$filename.'" class="image" height="'.$height.'" alt="'.$media->m_titl.'" />';
				else $this->HighlightedObject = "";
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
		global $TEXT_DIRECTION, $GEDCOM, $TOTAL_NAMES;
		global $NAME_LINENUM, $SEX_LINENUM, $gm_lang, $USE_QUICK_UPDATE, $show_changes;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		//-- main edit menu
		$menu = new Menu($gm_lang["edit"]);
		if ($this->canedit || $this->caneditown) {
			// NOTE: Quickedit sub menu
			if ($USE_QUICK_UPDATE) {
				$submenu = new Menu($gm_lang["quick_update_title"]);
				$submenu->addLink("quickEdit('".$this->pid."', '', 'edit_quickupdate');");
				$menu->addSubmenu($submenu);
				if ($this->canedit) $menu->addSeperator();
			}
				
			if ($this->canedit) {
				// Add a new father
				$submenu = new Menu($gm_lang["add_father"]);
				$submenu->addLink("addnewparent('".$this->pid."', 'HUSB', 'add_father');");
				$menu->addSubmenu($submenu);
				
				// Add a new mother
				$submenu = new Menu($gm_lang["add_mother"]);
				$submenu->addLink("addnewparent('".$this->pid."', 'WIFE', 'add_mother');");
				$menu->addSubmenu($submenu);
			
				// Add link as child to existing family
				$submenu = new Menu($gm_lang["link_as_child"]);
				$submenu->addLink("add_famc('".$this->pid."', 'link_as_child');");
				$menu->addSubmenu($submenu);
			
				// Add link as child to new family
				$submenu = new Menu($gm_lang["newfam_as_child"]);
				$submenu->addLink("add_newfamc('".$this->pid."', 'newfam_as_child');");
				$menu->addSubmenu($submenu);
				
				// Add a new wife
				$menu->addSeperator();
				if ($this->indi->getSex() != "F") {
					$submenu = new Menu($gm_lang["add_new_wife"]);
					$submenu->addLink("addspouse('".$this->pid."','WIFE', 'add_new_wife');");
					$menu->addSubmenu($submenu);
				}
			
				// Add a new husband
				if ($this->indi->getSex() != "M") {
					$submenu = new Menu($gm_lang["add_new_husb"]);
					$submenu->addLink("addspouse('".$this->pid."','HUSB', 'add_new_husb');");
					$menu->addSubmenu($submenu);
				}
			
				// Link a new wife
				if ($this->indi->getSex() != "F") {
					$submenu = new Menu($gm_lang["link_new_wife"]);
					$submenu->addLink("linkspouse('".$this->pid."','WIFE', 'link_new_wife');");
					$menu->addSubmenu($submenu);
				}
			
				// Link a new husband
				if ($this->indi->getSex() != "M") {
					$submenu = new Menu($gm_lang["link_new_husb"]);
					$submenu->addLink("linkspouse('".$this->pid."','HUSB', 'link_new_husb');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as husband
				if ($this->indi->getSex() != "F") {
					$submenu = new Menu($gm_lang["link_as_husband"]);
					$submenu->addLink("add_fams('".$this->pid."','HUSB', 'link_as_husband');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as wife
				if ($this->indi->getSex() != "M") {
					$submenu = new Menu($gm_lang["link_as_wife"]);
					$submenu->addLink("add_fams('".$this->pid."','WIFE', 'link_as_wife');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as husband to new family
				if ($this->indi->getSex() != "F") {
					$submenu = new Menu($gm_lang["newlink_as_husband"]);
					$submenu->addLink("add_newfams('".$this->pid."','HUSB', 'newlink_as_husband');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as wife to new family
				if ($this->indi->getSex() != "M") {
					$submenu = new Menu($gm_lang["newlink_as_wife"]);
					$submenu->addLink("add_newfams('".$this->pid."','WIFE', 'newlink_as_wife');");
					$menu->addSubmenu($submenu);
				}
				
				// Reorder families
				$menu->addSeperator();
				$thisopt = false;
				if (isset($this->indi->spouses) && count($this->indi->spouses) > 1) {
					$submenu = new Menu($gm_lang["reorder_families"]);
					$submenu->addLink("reorder_families('".$this->pid."', 'reorder_families');");
					$menu->addSubmenu($submenu);
					$thisopt = true;
				}
			
				// NOTE: Set family relations and primary
				if (count($this->indi->getChildFamilies())>0) {
					$submenu = new Menu($gm_lang["relation_fams_short"]);
					$submenu->addLink("relation_families('".$this->pid."', 'relation_families');");
					$menu->addSubmenu($submenu);
					$thisopt = true;
				}
				
				// Reorder_media. Only show if #media > 1
				if ($this->indi->getNumberOfMedia() > 1) {
					$submenu = new Menu($gm_lang['reorder_media']);
					$submenu->addLink("reorder_media('".$this->pid."', 'reorder_media');");
					$menu->addSubmenu($submenu);
				}
				
				if ($thisopt) $menu->addSeperator();
				// NOTE: Edit name
				if ($this->TOTAL_NAMES == 1) {
					$submenu = new Menu($gm_lang["edit_name"]);
					$submenu->addLink("edit_name('".$this->pid."', 'NAME', 1, 'edit_name');");
					$menu->addSubmenu($submenu);
				}
			
				// NOTE: Add name
				$submenu = new Menu($gm_lang["add_name"]);
				$submenu->addLink("add_name('".$this->pid."', 'add_name');");
				$menu->addSubmenu($submenu);
				$menu->addSeperator();
			
				// NOTE: Gender
				if ($this->SEX_COUNT<2) {
					if ($SEX_LINENUM=="new") $execute = "add_new_record('".$this->pid."', 'SEX', 'sex_edit');";
					else $execute = "edit_record('".$this->pid."', 'SEX', 1, 'edit_gender');";
					$submenu = new Menu($gm_lang["edit"]." ".$gm_lang["sex"]);
					$submenu->addLink($execute);
					$menu->addSubmenu($submenu);
					$menu->addSeperator();
				}
				
				// NOTE: Delete person
				$submenu = new Menu($gm_lang["delete_person"]);
				$submenu->addLink("if (confirm('".$gm_lang["confirm_delete_person"]."')) deleteperson('".$this->pid."', 'delete_person');");
				$menu->addSubmenu($submenu);
			
				// NOTE: Raw editing
				if ($this->canEditGedcomRecord) {
					$submenu = new Menu($gm_lang["edit_raw"]);
					$submenu->addLink("edit_raw('".$this->pid."', 'edit_raw');");
					$menu->addSubmenu($submenu);
				}
			
			}
		}
		if ($this->canedit) $menu->addSeperator();
		if ($show_changes == "no") $submenu = new Menu($gm_lang['show_changes']);
		else $submenu = new Menu($gm_lang['hide_changes']);
		$submenu->addLink('showchanges();');
		$menu->addSubmenu($submenu);
		return $menu;
	}
	
	/**
	 * get the "other" menu
	 * @todo Revise
	 * @return Menu
	 */
	function &getOtherMenu() {
		global $ENABLE_CLIPPINGS_CART, $gm_lang, $Users;
		
		//-- main other menu item
		$menu = new Menu($gm_lang["other"]);
		
		if (!$this->indi->isempty) {
			// Show Gedcom Record
			if ($this->canShowGedcomRecord) {
				if ($this->show_changes  && $this->canedit) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu($gm_lang["view_gedcom"]);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
			}
			// Clippings Cart
			if ($this->indi->disp && $ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) {
				$submenu = new Menu($gm_lang["add_to_cart"]);
				$submenu->addLink("clippings.php?action=add&id=".$this->pid."&type=indi");
				$menu->addSubmenu($submenu);
			}
			// Add favorite
			if ($this->indi->disp && !empty($this->uname)) {
				$submenu = new Menu($gm_lang["add_to_my_favorites"]);
				$submenu->addLink("individual.php?action=addfav&pid=".$this->pid."&gid=".$this->pid);
				$menu->addSubmenu($submenu);
			}
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
	function print_name_record($factrec, $count, $showedit=true) {
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
				if (HasChinese($name, true)) $add = "";
				else $add = " ";
				if ($NAME_REVERSE || HasChinese($name, true)) $name = ReverseName($name);
				$name = preg_replace("'/,'", ",", $name);
	   			$name = preg_replace("'/'", $add, $name);
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
					if ($NAME_REVERSE || HasChinese($name, true)) $name = ReverseName($name);
			  		$name = preg_replace("'/,'", ",", $name);
					$name = preg_replace("'/'", " ", $name);
					print PrintReady(CheckNN($name));
				}
				print " </span><br />";
			}
		}
		if ($this->TOTAL_NAMES>1 && !$this->isPrintPreview() && $this->canedit && $showedit) {
			if ($this->name_count==2) print_help_link("delete_name_help", "qm", "delete_name");
	   		print "<a href=\"#\" class=\"font9\" onclick=\"edit_name('".$this->pid."', 'NAME', '".$this->name_count."', 'edit_name'); return false;\">".$gm_lang["edit_name"]."</a> | ";
			print "<a class=\"font9\" href=\"#\" onclick=\"delete_record('".$this->pid."', 'NAME', '".$this->name_count."', 'delete_name'); return false;\">".$gm_lang["delete_name"]."</a>\n";
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
		if ($ft > 0) $sex = strtoupper(trim($match[2]));
		else $sex = "";
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
		global $GEDCOMID, $Favorites;
		global $Favorites;
		if (empty($this->uname)) return;
		if (!empty($_REQUEST["gid"])) {
			$gid = strtoupper($_REQUEST["gid"]);
			$indirec = FindPersonRecord($gid);
			if ($indirec) {
				$favorite = new Favorite();
				$favorite->username = $this->uname;
				$favorite->gid = $gid;
				$favorite->type = 'INDI';
				$favorite->file = $GEDCOMID;
				$favorite->SetFavorite();
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
?>
