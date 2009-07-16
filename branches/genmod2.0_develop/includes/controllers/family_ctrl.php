<?php
/**
 * Parses gedcom file and gives access to information about a family.
 *
 * You must supply a $famid value with the identifier for the family.
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
 * $Id$
 * @package Genmod
 * @subpackage Controllers
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class FamilyController extends DetailController
{
	public $classname = "FamilyRoot";
	var $user = null;
	var $uname = '';
	var $showLivingHusb = true;
	var $showLivingWife = true;
	var $link_relation = 0;
	var $title = '';
	var $xref = '';
	var $family = null;
	var $display_other_menu = false;
	var $exists = true;
	
	/**
	 * constructor
	 */
	public function __construct() {
		global
			$Dbwidth,
			$bwidth,
			$pbwidth,
			$pbheight,
			$bheight,
			$famlist,
			$GEDCOM,
			$GEDCOMID,
			$gm_lang,
			$CONTACT_EMAIL,
			$show_famlink,
			$show_changes,
			$ENABLE_CLIPPINGS_CART,
			$Users,
			$SHOW_ID_NUMBERS,
			$Favorites,
			$nonfacts,
			$GEDCOM_DEFAULT_TAB
		;
		
		parent::__construct();
		
		$bwidth = $Dbwidth;
		$pbwidth = $bwidth + 12;
		$pbheight = $bheight + 14;
		$nonfacts = array("FAMS", "FAMC", "MAY", "BLOB", "HUSB", "WIFE", "CHIL", "_MEND", "");
		
		$show_famlink = true;
		if ($this->view) $show_famlink = false;
		if (!empty($_REQUEST["famid"])) $this->xref = strtoupper($_REQUEST["famid"]);
		$this->xref = CleanInput($this->xref);
		
		// NOTE: Determine which tab should be shown global value
		$this->default_tab = $GEDCOM_DEFAULT_TAB;
		
		$this->family = new Family($this->xref);
		//-- check if we can display both parents
//		if ($this->display == false) {
//			$this->showLivingHusb = showLivingNameByID($this->parents['HUSB']);
//			$this->showLivingWife = showLivingNameByID($this->parents['WIFE']);
//		}

		// Check if we can display the Other menu
		if ($Users->userCanViewGedlines() || ($ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) || ($this->display && !empty($this->uname))) $this->display_other_menu = true;
				
		if (!empty($this->uname)) {
			$this->user = $Users->getUser($this->uname);

			//-- add favorites action
			if ($this->action == 'addfav' && (!empty($_REQUEST['gid']))) {
				$_REQUEST['gid'] = strtoupper($_REQUEST['gid']);
				$indirec = FindGedcomRecord($_REQUEST['gid']);
				if ($indirec) {
					$favorite = new Favorite();
					$favorite->username = $this->uname;
					$favorite->gid = $_REQUEST['gid'];
					$favorite->type = 'FAM';
					$favorite->file = $GEDCOMID;
					$favorite->SetFavorite();
				}
			}
		}

//		if ($this->showLivingHusb == false && $this->showLivingWife == false) {
//			print_header("{$gm_lang['private']} ({$this->famid}) {$gm_lang['family_info']}");
//			print_privacy_error($CONTACT_EMAIL);
//			print_footer();
//			exit;
//		}
		
		$none = true;
		if ($this->showLivingHusb == true) {
			$gedrec = "";
			if ($this->show_changes && !empty($this->parents['HUSB']) && GetChangeData(true, $this->parents['HUSB'], true)) {
				$rec = GetChangeData(false, $this->parents['HUSB'], true, "gedlines");
				$gedrec = $rec[$GEDCOM][$this->parents['HUSB']];
			}
			if (GetPersonName($this->parents['HUSB'], $gedrec) !== 'Individual ') {
				$this->title .= GetPersonName($this->parents['HUSB'], $gedrec);
				$none = false;
			}
		}
		else
		{
			$this->title .= $gm_lang['private'];
			$none = false;
		}
		if ($this->showLivingWife) {
			$gedrec = "";
			if ($this->show_changes && !empty($this->parents['WIFE']) && GetChangeData(true, $this->parents['WIFE'], true)) {
				$rec = GetChangeData(false, $this->parents['WIFE'], true, "gedlines");
				$gedrec = $rec[$GEDCOM][$this->parents['WIFE']];
			}
			if (GetPersonName($this->parents['WIFE'], $gedrec) !== 'Individual ') {
				if ($none == false) {
					$this->title .= ' + ';
				}
				$this->title .= GetPersonName($this->parents['WIFE'], $gedrec);
				$none = false;
			}
		}
		else
		{
			if ($none == false) {
				$this->title .= ' + ';
			}
			$this->title .= $gm_lang['private'];
			$none = false;
		}
		if ($SHOW_ID_NUMBERS) $this->title .= " - ".$this->famid;
		$this->title .= " - ".$gm_lang['family_info'];

		if (empty($this->parents['HUSB']) || empty($this->parents['WIFE'])) {
			$this->link_relation = 0;
		}
		else {
			$this->link_relation = 1;
		}
	}

	function getChildrenUrlTimeline($start=0)
	{
		$children = $this->family->children_ids;
		$c = 0;
		foreach($children as $id => $child) {
			$children[$id] = 'pids['.($c + $start).']='.$children[$id];
		}
		return join('&amp;', $children);
	}

	function getPageTitle()
	{
		return $this->title;
	}
	
	/**
	 * get the family page charts menu
	 * @return Menu
	 */
	function &getChartsMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// charts menu
		$menu = new Menu($gm_lang['charts']);
		$menu->addLink('timeline.php?pids[0]='.$this->parents['HUSB'].'&pids[1]='.$this->parents['WIFE']);
		
		// charts / parents_timeline
		$submenu = new Menu($gm_lang['parents_timeline']);
		$submenu->addLink('timeline.php?pids[0]='.$this->parents['HUSB'].'&pids[1]='.$this->parents['WIFE']);
		$menu->addSubmenu($submenu);
		
		// charts / children_timeline
		$submenu = new Menu($gm_lang['children_timeline']);
		$submenu->addLink('timeline.php?'.$this->getChildrenUrlTimeline());
		$menu->addSubmenu($submenu);
		
		// charts / family_timeline
		$submenu = new Menu($gm_lang['family_timeline']);
		$submenu->addLink('timeline.php?pids[0]='.$this->husb_id.'&pids[1]='.$this->wife_id.'&'.$this->getChildrenUrlTimeline(2));
		$menu->addSubmenu($submenu);
		
		return $menu;
	}
	
	/**
	 * get the family page reports menu
	 * @return Menu
	 */
	function &getReportsMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		$menu = new Menu($gm_lang['reports']);
		$menu->addLink('reportengine.php?action=setup&report=reports/familygroup.xml&famid='.$this->getFamilyID());

		// reports / family_group_report
		$submenu = new Menu($gm_lang['family_group_report']);
		$submenu->addLink('reportengine.php?action=setup&report=reports/familygroup.xml&famid='.$this->getFamilyID());
		$menu->addSubmenu($submenu);
		
		return $menu;
	}
	
	/**
	 * get the family page edit menu
	 */
	function &getEditMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $Users, $show_changes;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// edit_fam menu
		$menu = new Menu($gm_lang['edit_fam']);
		if (!$this->family->isdeleted && $this->family->canedit) {

			// edit_fam / members
			$submenu = new Menu($gm_lang['change_family_members']);
			$submenu->addLink("change_family_members('".$this->family->xref."', 'change_family_members');");
			$menu->addSubmenu($submenu);

			// edit_fam / add child
			$submenu = new Menu($gm_lang['add_child_to_family']);
			$submenu->addLink("addnewchild('".$this->family->xref."', 'add_child_to_family');");
			$menu->addSubmenu($submenu);

			// edit_fam / reorder_children. Only show if #kids > 1
			if ($this->family->children_count > 1) {
				$submenu = new Menu($gm_lang['reorder_children']);
				$submenu->addLink("reorder_children('".$this->family->xref."', 'reorder_children');");
				$menu->addSubmenu($submenu);
			}
			
			// edit_fam / reorder_media. Only show if #media > 1
			if ($this->family->media_count > 1) {
				$submenu = new Menu($gm_lang['reorder_media']);
				$submenu->addLink("reorder_media('".$this->family->xref."', 'reorder_media');");
				$menu->addSubmenu($submenu);
			}
			
			// edit_fam / delete_family. Don't show if unapproved fam.
			if (!$this->family->isnew && !$this->family->isdeleted) {
				$submenu = new Menu($gm_lang['delete_family']);
				$submenu->addLink("if (confirm('".$gm_lang["delete_family_confirm"]."')) delete_family('".$this->family->xref."', 'delete_family');");
				$menu->addSubmenu($submenu);
			}

			// edit_fam / edit_raw
			if ($Users->userCanEditGedlines()) {
				$submenu = new Menu($gm_lang['edit_raw']);
				$submenu->addLink("edit_raw('".$this->family->xref."', 'edit_raw');");
				$menu->addSubmenu($submenu);
			}
		}
		// TODO: also show this option when media changed
		if ($this->family->ischanged || HasChangedMedia($this->family->gedrec)) {
			// edit_fam / seperator
			if (!$this->family->isdeleted) {
				$submenu = new Menu();
				$submenu->isSeperator();
				$menu->addSubmenu($submenu);
			}

			// edit_fam / show/hide changes
				if (!$show_changes) $submenu = new Menu($gm_lang['show_changes']);
				else $submenu = new Menu($gm_lang['hide_changes']);
				$submenu->addLink('showchanges();');
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * get the other menu
	 * @return Menu
	 */
	function &getOtherMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang;
		global $ENABLE_CLIPPINGS_CART, $Users;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// other menu
		$menu = new Menu($gm_lang['other']);
		if ($Users->userCanViewGedlines()) {
				// other / view_gedcom
				if ($this->show_changes) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu($gm_lang['view_gedcom']);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu($gm_lang['add_to_cart']);
				$submenu->addLink('clippings.php?action=add&id='.$this->family->xref.'&type=fam');
				$menu->addSubmenu($submenu);
		}
		if ($this->display && !empty($this->uname)) {
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites']);
				$submenu->addLink('family.php?action=addfav&famid='.$this->family->xref.'&gid='.$this->getFamilyID());
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	public function PrintFamilyGroupHeader() {
		global $gm_lang, $SHOW_COUNTER, $hits;
		
		print "\n\t<br /><span class=\"subheaders\">" . $gm_lang["family_group_info"];
		print $this->family->addxref;
		print "</span>";
		if($SHOW_COUNTER) {
			// Print indi counter only if displaying a non-private person
			print "\n<span style=\"margin-left: 3px; vertical-align:bottom;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
		}
	}
}
?>