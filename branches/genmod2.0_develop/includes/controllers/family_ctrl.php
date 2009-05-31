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
 * $Id: family_ctrl.php,v 1.42 2009/03/29 13:20:10 sjouke Exp $
 * @package Genmod
 * @subpackage Controllers
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class FamilyRoot extends BaseController
{
	var $classname = "FamilyRoot";
	var $user = null;
	var $uname = '';
	var $showLivingHusb = true;
	var $showLivingWife = true;
	var $parents = '';
	var $display = false;
	var $show_changes = false;
	var $canedit = false;
	var $famrec = '';
	var $link_relation = 0;
	var $title = '';
	var $famid = '';
	var $family = null;
	var $difffam = null;
	var $famnew = false;
	var $famdeleted = false;
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
			$GEDCOM,
			$famlist,
			$gm_lang,
			$CONTACT_EMAIL,
			$show_famlink,
			$show_changes,
			$ENABLE_CLIPPINGS_CART,
			$Users,
			$SHOW_ID_NUMBERS
		;
		
		parent::__construct();
		
		$bwidth = $Dbwidth;
		$pbwidth = $bwidth + 12;
		$pbheight = $bheight + 14;

		if (!isset($_REQUEST['action'])) $_REQUEST['action'] = '';
		if (!isset($_REQUEST['show_changes'])) $_REQUEST['show_changes'] = 'yes';
		
		if ($_REQUEST['show_changes'] == 'yes' && $Users->UserCanEdit($Users->GetUserName())) $this->show_changes = true;
		else $this->show_changes = false;

		if (!isset($_REQUEST['view'])) $_REQUEST['view'] = '';
		$show_famlink = true;
		if ($_REQUEST['view'] == 'preview') $show_famlink = false;
		if (!isset($_REQUEST['famid'])) $_REQUEST['famid'] = '';
		$_REQUEST['famid'] = CleanInput($_REQUEST['famid']);
		
		$this->famid = $_REQUEST['famid'];
		$this->famrec = FindFamilyRecord($this->famid);
		$this->uname = $Users->GetUserName();
		if ($Users->UserCanEdit($this->uname)) $this->canedit = true;
		
		//-- if the user can edit and there are changes then get the new changes
		if (!empty($this->famid) && GetChangeData(true, $this->famid, true, "", "")) {
			$rec = GetChangeData(false, $this->famid, true, "gedlines", "");
			$newrec = $rec[$GEDCOM][$this->famid];
			if (empty($newrec) && !empty($this->famrec)) $this->famdeleted = true;
			if (!empty($newrec) && empty($this->famrec)) $this->famnew = true;
			if ($this->show_changes) $this->famrec = $newrec;
		}

		if (empty($this->famrec) && !$this->famdeleted) {
			$this->exists = false;
			print_header($gm_lang['family_info']);
			print $gm_lang["family_not_found"];
			print_footer();
			exit;
		}
		
		// Recheck editing permissions based on 1 RESN. This will only affect the editing menu
		if (FactEditRestricted($this->famid, $this->famrec, 1)) {
			$this->canedit = false;
		}
		$this->parents = FindParentsInRecord($this->famrec);
		
		//-- if no record was found create a default empty one
		$famrec = trim($this->famrec);
		if (empty($famrec)) $this->famrec = "0 @".$this->famid."@ FAM\r\n";
		$this->display = displayDetailsByID($this->famid, 'FAM');
		$this->family = new Family($this->famrec);
		
		
		//-- check if we can display both parents
		if ($this->display == false) {
			$this->showLivingHusb = showLivingNameByID($this->parents['HUSB']);
			$this->showLivingWife = showLivingNameByID($this->parents['WIFE']);
		}

		// Check if we can display the Other menu
		if ($Users->userCanViewGedlines() || ($ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) || ($this->display && !empty($this->uname))) $this->display_other_menu = true;
				
		if (!empty($this->uname)) {
			$this->user = $Users->getUser($this->uname);

			//-- add favorites action
			if (($_REQUEST['action'] == 'addfav') && (!empty($_REQUEST['gid']))) {
				$_REQUEST['gid'] = strtoupper($_REQUEST['gid']);
				$indirec = FindGedcomRecord($_REQUEST['gid']);
				if ($indirec) {
					$favorite = array(
						'username' => $this->uname,
						'gid' => $_REQUEST['gid'],
						'type' => 'FAM',
						'file' => $GEDCOM,
						'url' => '',
						'note' => '',
						'title' => ''
					);
					addFavorite($favorite);
				}
			}
		}

		//-- make sure we have the true id from the record
		$ct = preg_match("/0 @(.*)@/", $this->famrec, $match);
		if ($ct > 0) {
			$this->famid = trim($match[1]);
		}

		if ($this->showLivingHusb == false && $this->showLivingWife == false) {
			print_header("{$gm_lang['private']} ({$this->famid}) {$gm_lang['family_info']}");
			print_privacy_error($CONTACT_EMAIL);
			print_footer();
			exit;
		}
		
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

	function getFamilyID()
	{
		return $this->famid;
	}

	function getFamilyRecord()
	{
		return $this->famrec;
	}

	function getHusband()
	{
		if (!is_null($this->difffam)) return $this->difffam->getHusbId();
		return $this->parents['HUSB'];
	}

	function getWife()
	{
		if (!is_null($this->difffam)) return $this->difffam->getWifeId();
		return $this->parents['WIFE'];
	}

	function getChildren()
	{
		return FindChildrenInRecord($this->famrec);
	}

	function getChildrenUrlTimeline($start=0)
	{
		$children = $this->getChildren();
		$c = count($children);
		for ($i = 0; $i < $c; $i++)
		{
			$children[$i] = 'pids['.($i + $start).']='.$children[$i];
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
		$submenu->addLink('timeline.php?pids[0]='.$this->getHusband().'&pids[1]='.$this->getWife().'&'.$this->getChildrenUrlTimeline(2));
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
		if (!$this->famdeleted && $this->canedit) {

			// edit_fam / members
			$submenu = new Menu($gm_lang['change_family_members']);
			$submenu->addLink("change_family_members('".$this->getFamilyID()."', 'change_family_members');");
			$menu->addSubmenu($submenu);

			// edit_fam / add child
			$submenu = new Menu($gm_lang['add_child_to_family']);
			$submenu->addLink("addnewchild('".$this->getFamilyID()."', 'add_child_to_family');");
			$menu->addSubmenu($submenu);

			// edit_fam / reorder_children. Only show if #kids > 1
			if ($this->family->getNumberOfChildren() > 1) {
				$submenu = new Menu($gm_lang['reorder_children']);
				$submenu->addLink("reorder_children('".$this->getFamilyID()."', 'reorder_children');");
				$menu->addSubmenu($submenu);
			}
			
			// edit_fam / reorder_media. Only show if #media > 1
			if ($this->family->getNumberOfMedia() > 1) {
				$submenu = new Menu($gm_lang['reorder_media']);
				$submenu->addLink("reorder_media('".$this->getFamilyID()."', 'reorder_media');");
				$menu->addSubmenu($submenu);
			}
			
			// edit_fam / delete_family. Don't show if unapproved fam.
			if (!$this->famnew && !$this->famdeleted) {
				$submenu = new Menu($gm_lang['delete_family']);
				$submenu->addLink("if (confirm('".$gm_lang["delete_family_confirm"]."')) delete_family('".$this->getFamilyID()."', 'delete_family');");
				$menu->addSubmenu($submenu);
			}

			// edit_fam / edit_raw
			if ($Users->userCanEditGedlines()) {
				$submenu = new Menu($gm_lang['edit_raw']);
				$submenu->addLink("edit_raw('".$this->getFamilyID()."', 'edit_raw');");
				$menu->addSubmenu($submenu);
			}
		}
		// TODO: also show this option when media changed
		if (GetChangeData(true, $this->getFamilyID(), true) || HasChangedMedia($this->famrec)) {
			// edit_fam / seperator
			if (!$this->family->famdeleted) {
				$submenu = new Menu();
				$submenu->isSeperator();
				$menu->addSubmenu($submenu);
			}

			// edit_fam / show/hide changes
				if ($show_changes == "no") $submenu = new Menu($gm_lang['show_changes']);
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
				$submenu->addLink('clippings.php?action=add&id='.$this->getFamilyID().'&type=fam');
				$menu->addSubmenu($submenu);
		}
		if ($this->display && !empty($this->uname)) {
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites']);
				$submenu->addLink('family.php?action=addfav&famid='.$this->getFamilyID().'&gid='.$this->getFamilyID());
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}

if (file_exists('includes/controllers/family_ctrl_user.php'))
{
	include_once 'includes/controllers/family_ctrl_user.php';
}
else
{
	class FamilyController extends FamilyRoot
	{
	}
}
?>