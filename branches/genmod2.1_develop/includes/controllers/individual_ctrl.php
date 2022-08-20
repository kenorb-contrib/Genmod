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
 * @version $Id: individual_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
class IndividualController extends DetailController {
	
	public $classname = "IndividualController";	// Name of this class
	public $indi = null;						// Person object it's controlling
	private $canshowhighlightedobj = null;		// If it can show the highlighted media object for this person
	private $canshowgedrec = null;				// If it can show the gedcom record
	private $SEX_COUNT = 0;						// Number of gender records
	public $name_count = 0;						// Keeps track of the number of name records printed.
	private $TOTAL_NAMES = 0;					// Number of name records
	private $caneditown = false;				// If the user can edit this person (=self)
	private $indi_userlink = "";				// Link to user details, or username of the person showed
	private $HighlightedObject = null;			// Either empty of html img code for this object
	
	public function __construct() {
		global $GM_IMAGES, $nonfacts, $nonfamfacts;
		global $gm_user;
		
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
		if (!empty($_REQUEST["pid"])) $this->xref = strtoupper($_REQUEST["pid"]);
		$this->xref = CleanInput($this->xref);

		// NOTE: Determine which tab should be shown global value
		$this->default_tab = GedcomConfig::$GEDCOM_DEFAULT_TAB;
		
		// NOTE: Get the user details
		if (!empty($gm_user->username)) {
			
			// Set the default tab
			if ($gm_user->default_tab != $this->default_tab && $gm_user->default_tab != 9) $this->default_tab = $gm_user->default_tab;
			
			// Only display the user link for authenticated users
			$indi_username = UserController::getUserByGedcomId($this->xref, GedcomConfig::$GEDCOMID);
			$this->indi_userlink = "";
			if ($indi_username) {
				if ($gm_user->UserIsAdmin()) $this->indi_userlink = "<a href=\"useradmin.php?action=edituser&username=".$indi_username."\">";
				$this->indi_userlink .= "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"".PrintReady(GM_LANG_gm_username." ".$indi_username)."\" title=\"".PrintReady(GM_LANG_gm_username." ".$indi_username)."\" />";
				if ($gm_user->UserIsAdmin()) $this->indi_userlink .= "</a>";
			}
		}
		// Translate the (system or user) default tab to the correct tab in the definition in the detail controller
		$utabs = array('facts', 'notes', 'sources', 'media', 'relatives', '', '0');
		$tab = $utabs[$this->default_tab];
		$this->default_tab = array_search($tab, $this->tabs);
		
		// Note this is for Quick Update
		if ($gm_user->UserCanEditOwn($this->xref)) $this->caneditown = true;

		
		// NOTE: Create the person Object
		$this->indi =& Person::GetInstance($this->xref);
		
		//-- perform the desired action
		switch($this->action) {
			case "addfav":
				$this->addFavorite();
				break;
		}
		
		// NOTE: Can we show the gedcom record?
		if ($gm_user->userCanViewGedlines() && $this->indi->disp) $this->canshowgedrec = true;
		else $this->canshowgedrec = false;
		if ($this->indi->disp && !$this->indi->isempty) {
			// NOTE: add_family_facts parses all facts as it calls the parseFacts function
			$this->indi->AddFamilyFacts();
			
			// NOTE: Determine the number of names and sex records
			foreach ($this->indi->globalfacts as $key => $value) {
				if ($value->fact == "SEX") $this->SEX_COUNT++;
				if ($value->fact == "NAME") $this->TOTAL_NAMES++;
			}
			
			// NOTE: Get the parents and siblings labels
			$this->indi->getParentFamily($this->xref);
	
			// NOTE: Get the spouses and kids
			$this->indi->getSpouseFamily($this->xref);
			
			// NOTE: Get the parents other families
			$this->indi->getParentOtherFamily($this->xref);
		}
	}

	public function __get($property) {
		switch($property) {
			case "indi_userlink":
				return $this->indi_userlink;
				break;
			case "name_count":
				return $this->name_count;
				break;
			case "canshowhighlightedobj":
				return $this->canShowHighlightedObject();
				break;
			case "canshowgedrec":
				return $this->canshowgedrec;
				break;
			case "caneditown":
				return $this->caneditown;
				break;
			case "HighlightedObject":
				return $this->HighlightedObject;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	
	protected function GetPageTitle() {

		if (is_null($this->pagetitle)) {
			if ($this->indi->disp) $this->pagetitle = $this->indi->name;
			else $this->pagetitle = GM_LANG_private;
			
			$this->pagetitle .= $this->indi->addxref;
			$this->pagetitle .= " - ".GM_LANG_indi_info;
		}
		return $this->pagetitle;
	}
		
			
	/**
	 * Check if we can show the highlighted media object
	 * If we can, construct the code for the highlighted object
	 * @return boolean
	 */
	private function canShowHighlightedObject() {

		if (is_null($this->canshowhighlightedobj)) {
			if ($this->indi->disp && GedcomConfig::$SHOW_HIGHLIGHT_IMAGES && PrivacyFunctions::showFact("OBJE", $this->xref, "OBJE")) {
				$firstmediarec = $this->indi->highlightedimage;
				if ($firstmediarec) {
					// new from here
					$media =& MediaItem::GetInstance($firstmediarec["id"]);
					if ($media->fileobj->f_file_exists) {
						if (GedcomConfig::$USE_THUMBS_MAIN && $firstmediarec["use_thum"] != "Y") $filename = $media->fileobj->f_thumb_file;
						else $filename = $media->fileobj->f_main_file;
						if ($media->fileobj->f_height != 0 && $media->fileobj->f_height < 150) $height = $media->fileobj->f_height;
						else $height = 150;
						$this->HighlightedObject = '<img src="'.$filename.'" class="IndiPicture" height="'.$height.'" alt="'.$media->title.'" title="'.$media->title.'" />';
						$this->canshowhighlightedobj = true;
					}
					else $this->canshowhighlightedobj = false;
				}
				else $this->canshowhighlightedobj = false;
			}
			else $this->canshowhighlightedobj = false;
		}
		return $this->canshowhighlightedobj;
	}
	
	/**
	 * get the edit menu
	 * @todo Revise
	 * @return Menu
	 */
	public function &getEditMenu() {
		global $gm_user;
		
		//-- main edit menu
		$menu = new Menu(GM_LANG_edit);
		if (!$this->indi->isdeleted) {
			if ($this->indi->canedit) {
				// Add a new father
				$submenu = new Menu(GM_LANG_add_father);
				$submenu->addLink("addnewparent('".$this->xref."', 'HUSB', 'add_father');");
				$menu->addSubmenu($submenu);
				
				// Add a new mother
				$submenu = new Menu(GM_LANG_add_mother);
				$submenu->addLink("addnewparent('".$this->xref."', 'WIFE', 'add_mother');");
				$menu->addSubmenu($submenu);
			
				// Add link as child to existing family
				$submenu = new Menu(GM_LANG_link_as_child);
				$submenu->addLink("add_famc('".$this->xref."', 'link_as_child');");
				$menu->addSubmenu($submenu);
			
				// Add link as child to new family
				$submenu = new Menu(GM_LANG_newfam_as_child);
				$submenu->addLink("add_newfamc('".$this->xref."', 'newfam_as_child');");
				$menu->addSubmenu($submenu);
				
				// Add a new wife
				$menu->addSeperator();
				if ($this->indi->sex != "F") {
					$submenu = new Menu(GM_LANG_add_new_wife);
					$submenu->addLink("addspouse('".$this->xref."','WIFE', 'add_new_wife');");
					$menu->addSubmenu($submenu);
				}
			
				// Add a new husband
				if ($this->indi->sex != "M") {
					$submenu = new Menu(GM_LANG_add_new_husb);
					$submenu->addLink("addspouse('".$this->xref."','HUSB', 'add_new_husb');");
					$menu->addSubmenu($submenu);
				}
			
				// Link a new wife
				if ($this->indi->sex != "F") {
					$submenu = new Menu(GM_LANG_link_new_wife);
					$submenu->addLink("linkspouse('".$this->xref."','WIFE', 'link_new_wife');");
					$menu->addSubmenu($submenu);
				}
			
				// Link a new husband
				if ($this->indi->sex != "M") {
					$submenu = new Menu(GM_LANG_link_new_husb);
					$submenu->addLink("linkspouse('".$this->xref."','HUSB', 'link_new_husb');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as husband
				if ($this->indi->sex != "F") {
					$submenu = new Menu(GM_LANG_link_as_husband);
					$submenu->addLink("add_fams('".$this->xref."','HUSB', 'link_as_husband');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as wife
				if ($this->indi->sex != "M") {
					$submenu = new Menu(GM_LANG_link_as_wife);
					$submenu->addLink("add_fams('".$this->xref."','WIFE', 'link_as_wife');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as husband to new family
				if ($this->indi->sex != "F") {
					$submenu = new Menu(GM_LANG_newlink_as_husband);
					$submenu->addLink("add_newfams('".$this->xref."','HUSB', 'newlink_as_husband');");
					$menu->addSubmenu($submenu);
				}
			
				// Add link as wife to new family
				if ($this->indi->sex != "M") {
					$submenu = new Menu(GM_LANG_newlink_as_wife);
					$submenu->addLink("add_newfams('".$this->xref."','WIFE', 'newlink_as_wife');");
					$menu->addSubmenu($submenu);
				}
				
				// Reorder families
				$menu->addSeperator();
				$thisopt = false;
				if (count($this->indi->spousefamilies) > 1) {
					$submenu = new Menu(GM_LANG_reorder_families);
					$submenu->addLink("reorder_families('".$this->xref."', 'reorder_families');");
					$menu->addSubmenu($submenu);
					$thisopt = true;
				}
			
				// NOTE: Set family relations and primary
				if (count($this->indi->childfamilies) > 0) {
					$submenu = new Menu(GM_LANG_relation_fams_short);
					$submenu->addLink("relation_families('".$this->xref."', 'relation_families');");
					$menu->addSubmenu($submenu);
					$thisopt = true;
				}
				
				// Reorder_media. Only show if #media > 1
				if ($this->indi->mediafacts_count > 1) {
					$submenu = new Menu(GM_LANG_reorder_media);
					$submenu->addLink("reorder_media('".$this->xref."', 'reorder_media', 'INDI');");
					$menu->addSubmenu($submenu);
				}
				
				if ($thisopt) $menu->addSeperator();
				// NOTE: Edit name
				if ($this->TOTAL_NAMES == 1) {
					$submenu = new Menu(GM_LANG_edit_name);
					$submenu->addLink("edit_name('".$this->xref."', 'NAME', 1, 'edit_name');");
					$menu->addSubmenu($submenu);
				}
			
				// NOTE: Add name
				$submenu = new Menu(GM_LANG_add_name);
				$submenu->addLink("add_name('".$this->xref."', 'add_name');");
				$menu->addSubmenu($submenu);
				$menu->addSeperator();
			
				// NOTE: Gender
				if ($this->SEX_COUNT < 2) {
					if ($this->SEX_COUNT == 0) $execute = "add_new_record('".$this->xref."', 'SEX', 'edit_gender', '".$this->indi->datatype."');";
					else $execute = "edit_record('".$this->xref."', 'SEX', 1, 'edit_gender', 'INDI');";
					$submenu = new Menu(GM_LANG_edit." ".GM_LANG_sex);
					$submenu->addLink($execute);
					$menu->addSubmenu($submenu);
					$menu->addSeperator();
				}
				
				// NOTE: Delete person
				$submenu = new Menu(GM_LANG_delete_person);
				$submenu->addLink("if (confirm('".GM_LANG_confirm_delete_person."')) deleteperson('".$this->xref."', 'delete_person');");
				$menu->addSubmenu($submenu);
			
				// NOTE: Raw editing
				if ($gm_user->userCanEditGedlines()) {
					$submenu = new Menu(GM_LANG_edit_raw);
					$submenu->addLink("edit_raw('".$this->xref."', 'edit_raw', 'INDI');");
					$menu->addSubmenu($submenu);
				}
			
			}
		}
		if ($this->indi->ischanged) {
			$menu->addSeperator();
			if (!$this->indi->show_changes) $submenu = new Menu(GM_LANG_show_changes);
			else $submenu = new Menu(GM_LANG_hide_changes);
			$submenu->addLink('showchanges();');
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * get the "other" menu
	 * @todo Revise
	 * @return Menu
	 */
	public function &getOtherMenu() {
		global $ENABLE_CLIPPINGS_CART, $gm_user;
		
		//-- main other menu item
		$menu = new Menu(GM_LANG_other);
		
		if (!$this->indi->isempty) {
			// Show Gedcom Record
			if ($this->canshowgedrec) {
				if ($this->show_changes  && $this->indi->canedit) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu(GM_LANG_view_gedcom);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
			}
			// Clippings Cart
			if ($this->indi->disp && $ENABLE_CLIPPINGS_CART >= $gm_user->getUserAccessLevel()) {
				$submenu = new Menu(GM_LANG_add_to_cart);
				$submenu->addLink("clippings.php?action=add&id=".$this->xref."&type=indi");
				$menu->addSubmenu($submenu);
			}
			// Add favorite
			if ($this->indi->disp && !empty($this->uname) && !$this->indi->isuserfav) {
				$submenu = new Menu(GM_LANG_add_to_my_favorites);
				$submenu->addLink("individual.php?action=addfav&pid=".$this->xref.'&gedid='.GedcomConfig::$GEDCOMID);
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
	public function PrintNameRecord($factrec, $count, $showedit=true) {
		global $NAME_REVERSE;
		
		if ((!PrivacyFunctions::showFact("NAME", $this->xref))||(!PrivacyFunctions::showFactDetails("NAME", $this->xref))) return false;
		
		$lines = explode("\n", $factrec);
		$this->name_count++;
		// NOTE: If there is more than one name, print the aka tag
		if ($this->name_count>1) print "\n\t\t<span class=\"IndiHeaderLabel\">".GM_LANG_aka." </span><br />\n";
		
		$ct = preg_match_all("/2 (SURN)|(GIVN) (.*)/", $factrec, $nmatch, PREG_SET_ORDER);
		if ($ct==0) {
			$nt = preg_match("/1 NAME (.*)/", $factrec, $nmatch);
			if ($nt>0){
				print "\n\t\t<span class=\"IndiHeaderLabel\">".GM_LANG_name.": </span><br />";
				$name = trim($nmatch[1]);
				if (NameFunctions::HasChinese($name, true)) $add = "";
				else $add = " ";
				if ($NAME_REVERSE || NameFunctions::HasChinese($name, true)) $name = NameFunctions::ReverseName($name);
				$name = preg_replace("'/,'", ",", $name);
	   			$name = preg_replace("'/'", $add, $name);
				// handle PAF extra NPFX [ 961860 ]
				$ct = preg_match("/2 NPFX (.*)/", $factrec, $match);
				if ($ct>0) {
					$npfx = trim($match[1]);
					if (strpos($name, $npfx)===false) $name = $npfx." ".$name;
				}
				print "<span class=\"IndiHeaderField\">".PrintReady($name)."</span><br />\n";
			}
		}
		$ct = preg_match_all("/\n2 (\w+) (.*)/", $factrec, $nmatch, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$fact = trim($nmatch[$i][1]);
			if (($fact!="SOUR")&&($fact!="NOTE")) {
				print "\n\t\t\t<span class=\"IndiHeaderLabel\">";
				if (defined("GM_LANG_".$fact)) print constant("GM_LANG_".$fact);
				else if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
				else print $fact;
				print ":</span><span class=\"IndiHeaderField\"> ";
				if (isset($nmatch[$i][2])) {
					$name = trim($nmatch[$i][2]);
					if ($fact == "RESN") {
						print constant("GM_LANG_".$name);
					}
					else {
						if ($NAME_REVERSE || NameFunctions::HasChinese($name, true)) $name = NameFunctions::ReverseName($name);
						$name = preg_replace("'/,'", ",", $name);
						$name = preg_replace("'/'", " ", $name);
						print PrintReady(NameFunctions::CheckNN($name));
					}
				}
				print " </span><br />";
			}
		}
		if ($this->TOTAL_NAMES>1 && !$this->isPrintPreview() && $this->indi->canedit && $showedit) {
			if ($this->name_count==2) PrintHelpLink("delete_name_help", "qm", "delete_name");
	   		print "<a href=\"#\" class=\"SmallEditLinks\" onclick=\"edit_name('".$this->xref."', 'NAME', '".$this->name_count."', 'edit_name'); return false;\">".GM_LANG_edit_name."</a> | ";
			print "<a class=\"SmallEditLinks\" href=\"#\" onclick=\"delete_record('".$this->xref."', 'NAME', '".$this->name_count."', 'delete_name', 'INDI'); return false;\">".GM_LANG_delete_name."</a>\n";
			print "<br />\n";
		}
		$ct = preg_match("/\d (NOTE)|(SOUR)/", $factrec);
		if ($ct>0) {
			// -- find sources for this name
			print "<div class=\"Indent\">";
			FactFunctions::PrintFactSources($factrec, 2);
			//-- find the notes for this name
			print "&nbsp;&nbsp;&nbsp;";
			FactFunctions::PrintFactNotes($factrec, 2);
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
	public function GenderRecord($factrec, $linenum) {
		global $GM_IMAGES;
		
		if ((!PrivacyFunctions::showFact("SEX", $this->xref))||(!PrivacyFunctions::showFactDetails("SEX", $this->xref))) return false;
		
		$this->indi->sexdetails["add"] = false;
		$ft = preg_match("/\d\s(\w+)(.*)/", $factrec, $match);
		if ($ft > 0) $sex = strtoupper(trim($match[2]));
		else $sex = "";
		if (empty($sex)) $sex = "U";
		switch ($sex) {
			case "M":
				$this->indi->sexdetails["gender"] = GM_LANG_male;
				$this->indi->sexdetails["image"] = GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"];
				break;
			case "F":
				$this->indi->sexdetails["gender"] = GM_LANG_female;
				$this->indi->sexdetails["image"] = GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"];
				break;
			case "U":
				$this->indi->sexdetails["gender"] = GM_LANG_unknown;
				$this->indi->sexdetails["image"] = GM_IMAGE_DIR."/".$GM_IMAGES["sexn"]["small"];
				break;
		}
		if ($this->SEX_COUNT>1) {
			if ((!$this->isPrintPreview()) && ($this->indi->canedit) && (preg_match("/GM_OLD/", $factrec)==0)) {
				if ($linenum=="new") $this->indi->sexdetails["add"] = true;
			}
		}
		return true;
	}

	/**
	 * get the person box stylesheet class
	 * for the given person
	 * @param Person $person
	 * @return string	returns 'PersonBox', 'PersonBoxF', or 'PersonBoxNN'
	 */
	protected function getPersonStyle(&$person) {

		switch($person->sex) {
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
		return "PersonBox".$isf;
	}
	
	protected function PrintToggleJS1() {
		?>
		<script type="text/javascript">
		<!--
		// The function below does not go well with validation.
		// The option to use getElementsByName is used in connection with code from
		// the functions_print.php file.
		function togglerow(label) {
//			ebn = document.getElementsByName(label);
			first = document.getElementById(label+'0');
			if (first != null) disp = first.style.display;
//			if (ebn.length) disp = ebn[0].style.display;
			else disp="";
			if (disp=="none") {
				disp="table-row";
				if (document.all) disp="inline"; // IE
				document.getElementById('rela_plus').style.display="none";
				document.getElementById('rela_minus').style.display="inline";
			}
			else {
				disp="none";
				document.getElementById('rela_plus').style.display="inline";
				document.getElementById('rela_minus').style.display="none";
			}
			for (i=0; i<ebn.length; i++) ebn[i].style.display=disp;
			var el = 'el';
			var cnt = 0;
			while (el != null) {
				var el = document.getElementById('row_rela'+cnt);
				if (el != null) el.style.display = disp;
				cnt++;
			}
		}
		//-->
		</script>
		<?php	
	}
	
	protected function PrintToggleJS2() {
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
		// hide button if list is empty
//		ebn = document.getElementsByName('row_rela');
		var ebn = document.getElementsByName('row_rela0');
//		if (ebn.length==0) document.getElementById('row_top').style.display="none";
		if (ebn == null) document.getElementById('row_top').style.display="none";
		<?php if (!GedcomConfig::$EXPAND_RELATIVES_EVENTS) print "togglerow('row_rela');"?>
		//-->
		</script>
		<?php
	}
}
?>
