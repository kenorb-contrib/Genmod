<?php
/**
 * Parses gedcom file and gives access to information about a family.
 *
 * You must supply a $famid value with the identifier for the family.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2002 to 2005  GM Development Team
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
 * $Id: family_ctrl.php,v 1.5 2005/12/18 11:11:11 roland-d Exp $
 * @package Genmod
 * @subpackage Controllers
 */

require_once 'config.php';
require_once 'includes/controllers/basecontrol.php';
require_once 'includes/functions_charts.php';
require_once 'includes/family_class.php';
require_once 'includes/menu.php';
require_once $GM_BASE_DIRECTORY.$factsfile['english'];
if (file_exists($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE]))
{
	require_once $GM_BASE_DIRECTORY.$factsfile[$LANGUAGE];
}

class FamilyRoot extends BaseController
{
	var $user = null;
	var $uname = '';
	var $showLivingHusb = true;
	var $showLivingWife = true;
	var $parents = '';
	var $display = false;
	var $accept_success = false;
	var $show_changes = 'no';
	var $famrec = '';
	var $link_relation = 0;
	var $title = '';
	var $famid = '';
	var $family = null;
	var $difffam = null;
	
	/**
	 * constructor
	 */
	function FamilyRoot() {
		parent::BaseController();
	}

	function init()
	{
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
			$gm_changes
		;
		$bwidth = $Dbwidth;
		$pbwidth = $bwidth + 12;
		$pbheight = $bheight + 14;

		if (!isset($_REQUEST['action']))
		{
			$_REQUEST['action'] = '';
		}
		if (!isset($_REQUEST['show_changes']))
		{
			$_REQUEST['show_changes'] = 'yes';
		}
		$this->show_changes = $_REQUEST['show_changes'];
		if (!isset($_REQUEST['view']))
		{
			$_REQUEST['view'] = '';
		}
		$show_famlink = true;
		if ($_REQUEST['view'] == 'preview')
		{
			$show_famlink = false;
		}

		if (!isset($_REQUEST['famid']))
		{
			$_REQUEST['famid'] = '';
		}
		$_REQUEST['famid'] = clean_input($_REQUEST['famid']);
		$this->famid = $_REQUEST['famid'];
		$this->famrec = find_family_record($this->famid);
		//-- if no record was found create a default empty one
		if (empty($this->famrec)) $this->famrec = "0 @".$this->famid."@ FAM\r\n";
		$this->display = displayDetailsByID($this->famid, 'FAM');
		$this->family = new Family($this->famrec);
		
		$this->uname = getUserName();
		//-- if the user can edit and there are changes then get the new changes
		if ($this->show_changes=="yes" && userCanEdit($this->uname) && isset($gm_changes[$this->famid."_".$GEDCOM])) {
			$newrec = find_gedcom_record($this->famid);
			$this->difffam = new Family($newrec);
			$this->famrec = $newrec;
		}
		$this->parents = array('HUSB'=>$this->family->getHusbId(), 'WIFE'=>$this->family->getWifeId());

		//-- check if we can display both parents
		if ($this->display == false)
		{
			$this->showLivingHusb = showLivingNameByID($this->parents['HUSB']);
			$this->showLivingWife = showLivingNameByID($this->parents['WIFE']);
		}
		
		if (!empty($this->uname))
		{
			$this->user = getUser($this->uname);

			//-- add favorites action
			if (($_REQUEST['action'] == 'addfav') && (!empty($_REQUEST['gid'])))
			{
				$_REQUEST['gid'] = strtoupper($_REQUEST['gid']);
				$indirec = find_gedcom_record($_REQUEST['gid']);
				if ($indirec)
				{
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

		if (userCanAccept($this->uname))
		{
			if ($_REQUEST['action'] == 'accept')
			{
				if (accept_changes($_REQUEST['famid'].'_'.$GEDCOM))
				{
					$this->show_changes = 'no';
					$this->accept_success = true;
					unset($famlist[$_REQUEST['famid']]);
					$this->parents = find_parents($_REQUEST['famid']);
				}
			}
			
			if ($_REQUEST['action'] == 'undo')
			{
				$this->family->undoChange();
				unset($famlist[$_REQUEST['famid']]);
				$this->parents = find_parents($_REQUEST['famid']);
			}
		}

		//-- make sure we have the true id from the record
		$ct = preg_match("/0 @(.*)@/", $this->famrec, $match);
		if ($ct > 0)
		{
			$this->famid = trim($match[1]);
		}

		if ($this->showLivingHusb == false && $this->showLivingWife == false)
		{
			print_header("{$gm_lang['private']} {$gm_lang['family_info']}");
			print_privacy_error($CONTACT_EMAIL);
			print_footer();
			exit;
		}

		$none = true;
		if ($this->showLivingHusb == true)
		{
			if (get_person_name($this->parents['HUSB']) !== 'Individual ')
			{
				$this->title .= get_person_name($this->parents['HUSB']);
				$none = false;
			}
			if ($this->showLivingWife && (get_person_name($this->parents['WIFE']) !== 'Individual '))
			{
				if ($none == false)
				{
					$this->title .= ' + ';
				}
				$this->title .= get_person_name($this->parents['WIFE']);
				$none = false;
			}
			$this->title = "{$this->title} {$gm_lang['family_info']}";
		}
		if ($none == true)
		{
			$this->title = $gm_lang['family_info'];
		}

		if (empty($this->parents['HUSB']) || empty($this->parents['WIFE']))
		{
			$this->link_relation = 0;
		}
		else
		{
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
		return find_children_in_record($this->famrec);
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
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// charts menu
		$menu = new Menu($gm_lang['charts'], 'timeline.php?pids[0]='.$this->parents['HUSB'].'&amp;pids[1]='.$this->parents['WIFE']);
		if (!empty($GM_IMAGES["timeline"]["small"]))
			$menu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['timeline']['small']}");
		$menu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}", "submenu{$ff}");
		// charts / parents_timeline
		$submenu = new Menu($gm_lang['parents_timeline'], 'timeline.php?pids[0]='.$this->parents['HUSB'].'&amp;pids[1]='.$this->parents['WIFE']);
		if (!empty($GM_IMAGES["timeline"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['timeline']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);
		// charts / children_timeline
		$submenu = new Menu($gm_lang['children_timeline'], 'timeline.php?'.$this->getChildrenUrlTimeline());
		if (!empty($GM_IMAGES["timeline"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['timeline']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);
		// charts / family_timeline
		$submenu = new Menu($gm_lang['family_timeline'], 'timeline.php?pids[0]='.$this->getHusband().'&amp;pids[1]='.$this->getWife().'&amp;'.$this->getChildrenUrlTimeline(2));
		if (!empty($GM_IMAGES["timeline"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['timeline']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);
		
		return $menu;
	}
	
	/**
	 * get the family page reports menu
	 * @return Menu
	 */
	function &getReportsMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		$menu = new Menu($gm_lang['reports'], 'reportengine.php?action=setup&amp;report=reports/familygroup.xml&amp;famid='.$this->getFamilyID());
		if (!empty($GM_IMAGES["reports"]["small"]))
			$menu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['reports']['small']}");
		$menu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}", "submenu{$ff}");

		// reports / family_group_report
		$submenu = new Menu($gm_lang['family_group_report'], 'reportengine.php?action=setup&amp;report=reports/familygroup.xml&amp;famid='.$this->getFamilyID());
		if (!empty($GM_IMAGES["reports"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['reports']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);
		
		return $menu;
	}
	
	/**
	 * get the family page edit menu
	 */
	function &getEditMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang, $gm_changes;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		// edit_fam menu
		$menu = new Menu($gm_lang['edit_fam']);
		$menu->addOnclick("return edit_raw('".$this->getFamilyID()."', 'edit_raw');");
		if (!empty($GM_IMAGES["edit_fam"]["small"]))
			$menu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
		$menu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}", "submenu{$ff}");

		// edit_fam / delete_family
		$submenu = new Menu($gm_lang['delete_family']);
		$submenu->addOnclick("if (confirm('".$gm_lang["delete_family_confirm"]."')) return delete_family('".$this->getFamilyID()."', 'delete_family'); else return false;");
		if (!empty($GM_IMAGES["edit_fam"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);

		// edit_fam / edit_raw
		$submenu = new Menu($gm_lang['edit_raw']);
		$submenu->addOnclick("return edit_raw('".$this->getFamilyID()."', 'edit_raw');");
		if (!empty($GM_IMAGES["edit_fam"]["small"]))
		$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);

		// edit_fam / members
		$submenu = new Menu($gm_lang['change_family_members']);
		$submenu->addOnclick("return change_family_members('".$this->getFamilyID()."', 'change_family_members');");
		if (!empty($GM_IMAGES["edit_fam"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);

		// edit_fam / add child
		$submenu = new Menu($gm_lang['add_child_to_family']);
		$submenu->addOnclick("return addnewchild('".$this->getFamilyID()."', 'add_child_to_family');");
		if (!empty($GM_IMAGES["edit_fam"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);

		// edit_fam / reorder_children
		$submenu = new Menu($gm_lang['reorder_children']);
		$submenu->addOnclick("return reorder_children('".$this->getFamilyID()."', 'reorder_children');");
		if (!empty($GM_IMAGES["edit_fam"]["small"]))
		$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);

		if (isset($gm_changes[$this->getFamilyID().'_'.$GEDCOM])) {
			// edit_fam / seperator
			$submenu = new Menu();
			$submenu->isSeperator();
			$menu->addSubmenu($submenu);

			// edit_fam / show/hide changes
			if ($_REQUEST['show_changes'] == 'no') {
				$submenu = new Menu($gm_lang['show_changes'], 'family.php?famid='.$this->getFamilyID().'&amp;show_changes=yes');
				if (!empty($GM_IMAGES["edit_fam"]["small"]))
				$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
			}
			else {
				$submenu = new Menu($gm_lang['hide_changes'], 'family.php?famid='.$this->getFamilyID().'&amp;show_changes=no');
				if (!empty($GM_IMAGES["edit_fam"]["small"]))
					$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
			}

			if (userCanAccept(getUserName()))
			{
				// edit_fam / accept_all
				/*$submenu = new Menu($gm_lang['accept_all']);
				$submenu->addOnclick("window.open('edit_changes.php','','width=600,height=600,resizable=1,scrollbars=1'); return false;");
				if (!empty($GM_IMAGES["edit_fam"]["small"]))
					$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
				*/
				$submenu = new Menu($gm_lang["accept_all"], "family.php?famid=".$this->famid."&amp;action=accept");
				if (!empty($GM_IMAGES["edit_fam"]["small"]))
					$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				$submenu = new Menu($gm_lang["undo_all"], "family.php?famid=".$this->famid."&amp;action=undo");
				if (!empty($GM_IMAGES["edit_fam"]["small"]))
					$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
			}
		}
		return $menu;
	}
	
	/**
	 * get the other menu
	 * @return Menu
	 */
	function &getOtherMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		global $SHOW_GEDCOM_RECORD, $ENABLE_CLIPPINGS_CART;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
			// other menu
		$menu = new Menu($gm_lang['other']);
		$menu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}", "submenu{$ff}");
		if ($SHOW_GEDCOM_RECORD)
		{
			$menu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['gedcom']['small']}");
			if ($_REQUEST['show_changes'] == 'yes'  && userCanEdit(getUserName()))
			{
				$menu->addLink("javascript:show_gedcom_record('new');");
			}
			else
			{
				$menu->addLink("javascript:show_gedcom_record();");
			}
		}
		else
		{
			if (!empty($GM_IMAGES["clippings"]["small"]))
				$menu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['clippings']['small']}");
			$menu->addLink('clippings.php?action=add&amp;id='.$this->getFamilyID().'&amp;type=fam');
		}
		if ($SHOW_GEDCOM_RECORD)
		{
				// other / view_gedcom
				$submenu = new Menu($gm_lang['view_gedcom']);
				if ($_REQUEST['show_changes'] == 'yes'  && userCanEdit(getUserName()))
				{
					$submenu->addLink("javascript:show_gedcom_record('new');");
				}
				else
				{
					$submenu->addLink("javascript:show_gedcom_record();");
				}
				$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['gedcom']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
		}
		if ($ENABLE_CLIPPINGS_CART >= getUserAccessLevel())
		{
				// other / add_to_cart
				$submenu = new Menu($gm_lang['add_to_cart'], 'clippings.php?action=add&amp;id='.$this->getFamilyID().'&amp;type=fam');
				if (!empty($GM_IMAGES["clippings"]["small"]))
					$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['clippings']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
		}
		if ($this->display && !empty($this->uname))
		{
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites'], 'family.php?action=addfav&amp;famid='.$this->getFamilyID().'&amp;gid='.$this->getFamilyID());
				$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['gedcom']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
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
$controller = new FamilyController();
$controller->init();
?>