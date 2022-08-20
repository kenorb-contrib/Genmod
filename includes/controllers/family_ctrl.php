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
 * $Id: family_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Controllers
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class FamilyController extends DetailController
{
	public $classname = "FamilyController";	// Name of this class
	public $family = null;					// Family object it controls
	
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
			$ENABLE_CLIPPINGS_CART,
			$nonfacts,
			$show_full
		;
		
		parent::__construct();
		
		$bwidth = $Dbwidth;
		$pbwidth = $bwidth + 12;
		$pbheight = $bheight + 14;
		$nonfacts = array("FAMS", "FAMC", "MAY", "BLOB", "HUSB", "WIFE", "CHIL", "_MEND", "");
		$show_full = true; // Only full boxes on the family page
		
		if (!empty($_REQUEST["famid"])) $this->xref = strtoupper($_REQUEST["famid"]);
		$this->xref = CleanInput($this->xref);
		
		$this->family =& Family::GetInstance($this->xref);

		//-- perform the desired action
		switch($this->action) {
			case "addfav":
				$this->addFavorite();
				break;
		}
	}
	
	public function __get($property) {
		switch($property) {
			default:
				return parent::__get($property);
		}
	}
	
	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			
			if (is_object($this->family->husb)) $hname = $this->family->husb->name;
			else $hname = NameFunctions::CheckNN("@P.N. @N.N.");
			
			if (is_object($this->family->wife)) $wname = $this->family->wife->name;
			else $wname = NameFunctions::CheckNN("@P.N. @N.N.");
			
			$this->pagetitle = $hname." + ".$wname.$this->family->addxref;
			$this->pagetitle .= " - ".GM_LANG_family_info;
		}
		return $this->pagetitle;
	}
	
	protected function GetTitle() {
		
		if (is_null($this->title)) {
			$this->title = $this->family->name;
			$add = $this->family->addname;
			if ($add != "") $this->title .= "<br />".$add;
		}
		return $this->title;
	}
	
	public function getChildrenUrlTimeline($start=0) {
		
		$children = $this->family->children_ids;
		$c = 0;
		foreach($children as $id => $child) {
			$children[$id] = 'pids'.($c + $start).'='.$children[$id];
			$c++;
		}
		return join('&amp;', $children);
	}

	/**
	 * get the family page charts menu
	 * @return Menu
	 */
	public function &getChartsMenu() {
		
		// charts menu
		$menu = new Menu(GM_LANG_charts);
		$menu->addLink('timeline.php?pids0='.$this->family->husb_id.'&pids1='.$this->family->wife_id);
		
		// charts / parents_timeline
		$submenu = new Menu(GM_LANG_parents_timeline);
		$submenu->addLink('timeline.php?pids0='.$this->family->husb_id.'&pids1='.$this->family->wife_id);
		$menu->addSubmenu($submenu);
		
		// charts / children_timeline
		$submenu = new Menu(GM_LANG_children_timeline);
		$submenu->addLink('timeline.php?'.$this->getChildrenUrlTimeline());
		$menu->addSubmenu($submenu);
		
		// charts / family_timeline
		$submenu = new Menu(GM_LANG_family_timeline);
		$submenu->addLink('timeline.php?pids0='.$this->family->husb_id.'&pids1='.$this->family->wife_id.'&'.$this->getChildrenUrlTimeline(2));
		$menu->addSubmenu($submenu);
		
		return $menu;
	}
	
	/**
	 * get the family page edit menu
	 */
	public function &getEditMenu() {
		global $gm_user;
		
		// edit_fam menu
		$menu = new Menu(GM_LANG_edit_fam);
		if (!$this->family->isdeleted && $this->family->canedit) {

			// edit_fam / members
			$submenu = new Menu(GM_LANG_change_family_members);
			$submenu->addLink("change_family_members('".$this->family->xref."', 'change_family_members');");
			$menu->addSubmenu($submenu);

			// edit_fam / add child
			$submenu = new Menu(GM_LANG_add_child_to_family);
			$submenu->addLink("addnewchild('".$this->family->xref."', 'add_child_to_family');");
			$menu->addSubmenu($submenu);

			// edit_fam / reorder_children. Only show if #kids > 1
			if ($this->family->children_count > 1) {
				$submenu = new Menu(GM_LANG_reorder_children);
				$submenu->addLink("reorder_children('".$this->family->xref."', 'reorder_children');");
				$menu->addSubmenu($submenu);
			}
			
			// edit_fam / reorder_media. Only show if #media > 1
			if ($this->family->media_count > 1) {
				$submenu = new Menu(GM_LANG_reorder_media);
				$submenu->addLink("reorder_media('".$this->family->xref."', 'reorder_media', 'FAM');");
				$menu->addSubmenu($submenu);
			}
			
			// edit_fam / delete_family. Don't show if unapproved fam.
			if (!$this->family->isnew && !$this->family->isdeleted) {
				$submenu = new Menu(GM_LANG_delete_family);
				$submenu->addLink("if (confirm('".GM_LANG_delete_family_confirm."')) delete_family('".$this->family->xref."', 'delete_family');");
				$menu->addSubmenu($submenu);
			}

			// edit_fam / edit_raw
			if ($gm_user->userCanEditGedlines()) {
				$submenu = new Menu(GM_LANG_edit_raw);
				$submenu->addLink("edit_raw('".$this->family->xref."', 'edit_raw', 'FAM');");
				$menu->addSubmenu($submenu);
			}
		}
		if ($this->family->ischanged || ChangeFunctions::HasChangedMedia($this->family->gedrec)) {
			// edit_fam / seperator
			if (!$this->family->isdeleted) {
				$submenu = new Menu();
				$submenu->isSeperator();
				$menu->addSubmenu($submenu);
			}

			// edit_fam / show/hide changes
				if (!$this->show_changes) $submenu = new Menu(GM_LANG_show_changes);
				else $submenu = new Menu(GM_LANG_hide_changes);
				$submenu->addLink('showchanges();');
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * get the other menu
	 * @return Menu
	 */
	public function &getOtherMenu() {
		global $ENABLE_CLIPPINGS_CART, $gm_user;
		
		// other menu
		$menu = new Menu(GM_LANG_other);
		if ($gm_user->userCanViewGedlines()) {
				// other / view_gedcom
				if ($this->show_changes) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu(GM_LANG_view_gedcom);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $gm_user->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu(GM_LANG_add_to_cart);
				$submenu->addLink('clippings.php?action=add&id='.$this->family->xref.'&type=fam');
				$menu->addSubmenu($submenu);
		}
		if ($this->family->disp && $this->uname != "" && !$this->family->isuserfav) {
				// other / add_to_my_favorites
				$submenu = new Menu(GM_LANG_add_to_my_favorites);
				$submenu->addLink('family.php?action=addfav&famid='.$this->family->xref.'&gedid='.$this->family->gedcomid);
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	public function PrintFamilyGroupHeader() {
		global $hits;
		
		print "\n\t<div class=\"SubHeader\">" . GM_LANG_family_group_info;
		print $this->family->addxref;
		print "";
		if(GedcomConfig::$SHOW_COUNTER && !$this->IsPrintPreview()) {
			// Print indi counter only if displaying a non-private person
			print "\n<span class=\"PageCounter\">".GM_LANG_hit_count."&nbsp;".$hits."</span>";
		}
		print "</div>\n";
	}
}
?>