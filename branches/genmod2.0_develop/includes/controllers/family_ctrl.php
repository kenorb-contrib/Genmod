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
	public $classname = "FamilyController";
	public $family = null;
	
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
			$ENABLE_CLIPPINGS_CART,
			$SHOW_ID_NUMBERS,
			$nonfacts,
			$GEDCOM_DEFAULT_TAB
		;
		
		parent::__construct();
		
		$bwidth = $Dbwidth;
		$pbwidth = $bwidth + 12;
		$pbheight = $bheight + 14;
		$nonfacts = array("FAMS", "FAMC", "MAY", "BLOB", "HUSB", "WIFE", "CHIL", "_MEND", "");
		
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
		global $SHOW_ID_NUMBERS, $gm_lang;
		
		if (is_null($this->pagetitle)) {
			
			if (is_object($this->family->husb)) $hname = $this->family->husb->name;
			else $hname = CheckNN("@P.N. @N.N.");
			
			if (is_object($this->family->wife)) $wname = $this->family->wife->name;
			else $wname = CheckNN("@P.N. @N.N.");
			
			$this->pagetitle = $hname." + ".$wname;
			if ($SHOW_ID_NUMBERS) $this->pagetitle .= " - ".$this->family->xref;
			$this->pagetitle .= " - ".$gm_lang['family_info'];
		}
		return $this->pagetitle;
	}
	
	public function getChildrenUrlTimeline($start=0) {
		
		$children = $this->family->children_ids;
		$c = 0;
		foreach($children as $id => $child) {
			$children[$id] = 'pids['.($c + $start).']='.$children[$id];
		}
		return join('&amp;', $children);
	}

	/**
	 * get the family page charts menu
	 * @return Menu
	 */
	public function &getChartsMenu() {
		global $TEXT_DIRECTION, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// charts menu
		$menu = new Menu($gm_lang['charts']);
		$menu->addLink('timeline.php?pids[0]='.$this->family->husb_id.'&pids[1]='.$this->family->wife_id);
		
		// charts / parents_timeline
		$submenu = new Menu($gm_lang['parents_timeline']);
		$submenu->addLink('timeline.php?pids[0]='.$this->family->husb_id.'&pids[1]='.$this->family->wife_id);
		$menu->addSubmenu($submenu);
		
		// charts / children_timeline
		$submenu = new Menu($gm_lang['children_timeline']);
		$submenu->addLink('timeline.php?'.$this->getChildrenUrlTimeline());
		$menu->addSubmenu($submenu);
		
		// charts / family_timeline
		$submenu = new Menu($gm_lang['family_timeline']);
		$submenu->addLink('timeline.php?pids[0]='.$this->family->husb_id.'&pids[1]='.$this->family->wife_id.'&'.$this->getChildrenUrlTimeline(2));
		$menu->addSubmenu($submenu);
		
		return $menu;
	}
	
	/**
	 * get the family page edit menu
	 */
	public function &getEditMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $gm_user;
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
			if ($gm_user->userCanEditGedlines()) {
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
				if (!$this->show_changes) $submenu = new Menu($gm_lang['show_changes']);
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
	public function &getOtherMenu() {
		global $TEXT_DIRECTION, $gm_lang;
		global $ENABLE_CLIPPINGS_CART, $gm_user;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// other menu
		$menu = new Menu($gm_lang['other']);
		if ($gm_user->userCanViewGedlines()) {
				// other / view_gedcom
				if ($this->show_changes) $execute = "show_gedcom_record('new');";
				else $execute = "show_gedcom_record();";
				$submenu = new Menu($gm_lang['view_gedcom']);
				$submenu->addLink($execute);
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= $gm_user->getUserAccessLevel()) {
				// other / add_to_cart
				$submenu = new Menu($gm_lang['add_to_cart']);
				$submenu->addLink('clippings.php?action=add&id='.$this->family->xref.'&type=fam');
				$menu->addSubmenu($submenu);
		}
		if ($this->family->disp && $this->uname != "") {
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites']);
				$submenu->addLink('family.php?action=addfav&famid='.$this->family->xref.'&gedid='.$this->family->gedcomid);
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