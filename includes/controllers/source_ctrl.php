<?php
/**
 * Controller for the source page view
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
 * @package Genmod
 * @subpackage Charts
 * @version $Id: source_ctrl.php,v 1.5 2005/12/18 21:16:58 roland-d Exp $
 */

require_once("config.php");
require_once 'includes/controllers/basecontrol.php';
require_once 'includes/source_class.php';
require_once 'includes/menu.php';
require_once($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE])) require_once($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE]);

$nonfacts = array();
/**
 * Main controller class for the source page.
 */
class SourceControllerRoot extends BaseController {
	var $sid;
	var $show_changes = "yes";
	var $action = "";
	var $source = null;
	var $uname = "";
	var $diffsource = null;
	var $accept_success = false;
	var $canedit = false;
	
	/**
	 * constructor
	 */
	function SourceRootController() {
		parent::BaseController();
	}
	
	/**
	 * initialize the controller
	 */
	function init() {
		global $gm_lang, $CONTACT_EMAIL, $GEDCOM, $gm_changes;
		
		if (!empty($_REQUEST["show_changes"])) $this->show_changes = $_REQUEST["show_changes"];
		if (!empty($_REQUEST["action"])) $this->action = $_REQUEST["action"];
		if (!empty($_REQUEST["sid"])) $this->sid = strtoupper($_REQUEST["sid"]);
		$this->sid = clean_input($this->sid);
		
		$sourcerec = find_source_record($this->sid);
		if (!$sourcerec) $sourcerec = "0 @".$this->sid."@ SOUR\r\n";
		
		$this->source = new Source($sourcerec);
		
		if (!$this->source->canDisplayDetails()) {
			print_header($gm_lang["private"]." ".$gm_lang["source_info"]);
			print_privacy_error($CONTACT_EMAIL);
			print_footer();
			exit;
		}
		
		$this->uname = getUserName();
		
		//-- perform the desired action
		switch($this->action) {
			case "addfav":
				$this->addFavorite();
				break;
			case "accept":
				$this->acceptChanges();
				break;
		}
		
		//-- check for the user
		//-- if the user can edit and there are changes then get the new changes
		if ($this->show_changes=="yes" && userCanEdit($this->uname) && isset($gm_changes[$this->sid."_".$GEDCOM])) {
			$newrec = find_gedcom_record($this->sid);
			$this->diffsource = new Source($newrec);
			$sourcerec = $newrec;
		}
		
		if ($this->source->canDisplayDetails()) {
			$this->canedit = userCanEdit($this->uname);
		}
		
		if ($this->show_changes=="yes" && $this->canedit) {
			$this->source->diffMerge($this->diffsource);
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
			$indirec = find_source_record($gid);
			if ($indirec) {
				$favorite = array();
				$favorite["username"] = $this->uname;
				$favorite["gid"] = $gid;
				$favorite["type"] = "SOUR";
				$favorite["file"] = $GEDCOM;
				$favorite["url"] = "";
				$favorite["note"] = "";
				$favorite["title"] = "";
				addFavorite($favorite);
			}
		}
	}
	/**
	 * Accept any edit changes into the database
	 * Also update the indirec we will use to generate the page
	 */
	function acceptChanges() {
		global $GEDCOM;
		
		if (!userCanAccept($this->uname)) return;
		if (accept_changes($this->sid."_".$GEDCOM)) {
			$this->show_changes="no";
			$this->accept_success=true;
			$indirec = find_gedcom_record($this->sid);
			$this->source = new Source($indirec);
		}
	}
	
	/**
	 * get the title for this page
	 * @return string
	 */
	function getPageTitle() {
		global $gm_lang;
		return $this->source->getTitle()." - ".$this->sid." - ".$gm_lang["source_info"];
	}
	/**
	 * check if use can edit this person
	 * @return boolean
	 */
	function userCanEdit() {
		return $this->canedit;
	}
	
	/**
	 * get edit menut
	 * @return Menu
	 */
	function &getEditMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang, $gm_changes;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
		else $ff="";
		
		if (!$this->userCanEdit()) {
			$tempvar = false;
			return $tempvar;
		}
		
		// edit source menu
		$menu = new Menu($gm_lang['edit_source']);
		$menu->addOnclick("return edit_raw('".$this->sid."', 'edit_raw');");
		if (!empty($GM_IMAGES["edit_sour"]["small"]))
			$menu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_sour']['small']}");
		$menu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}", "submenu{$ff}");

		// edit source / edit_raw
		$submenu = new Menu($gm_lang['edit_raw']);
		$submenu->addOnclick("return edit_raw('".$this->sid."', 'edit_raw');");
		if (!empty($GM_IMAGES["edit_sour"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_sour']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);
		
		// edit source / delete_source
		$submenu = new Menu($gm_lang['delete_source']);
		$submenu->addOnclick("if (confirm('".$gm_lang["confirm_delete_source"]."')) return deletesource('".$this->sid."', 'delete_source'); else return false;");
		if (!empty($GM_IMAGES["edit_sour"]["small"]))
			$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_sour']['small']}");
		$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
		$menu->addSubmenu($submenu);

		if (isset($gm_changes[$this->sid.'_'.$GEDCOM]))
		{
			// edit_sour / seperator
			$submenu = new Menu();
			$submenu->isSeperator();
			$menu->addSubmenu($submenu);

			// edit_sour / show/hide changes
			if ($this->show_changes == 'no')
			{
				$submenu = new Menu($gm_lang['show_changes'], 'source.php?sid='.$this->sid.'&amp;show_changes=yes');
				if (!empty($GM_IMAGES["edit_sour"]["small"]))
				$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_sour']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
			}
			else
			{
				$submenu = new Menu($gm_lang['hide_changes'], 'source.php?sid='.$this->sid.'&amp;show_changes=no');
				if (!empty($GM_IMAGES["edit_sour"]["small"]))
				$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
			}

			if (userCanAccept($this->uname))
			{
				// edit_source / accept_all
				$submenu = new Menu($gm_lang['accept_all'], 'source.php?sid='.$this->sid.'&amp;action=accept');
				if (!empty($GM_IMAGES["edit_sour"]["small"]))
				$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['edit_fam']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
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
		
		if (!$this->source->canDisplayDetails() || (!$SHOW_GEDCOM_RECORD && $ENABLE_CLIPPINGS_CART < getUserAccessLevel())) {
			$tempvar = false;
			return $tempvar;
		}
		
			// other menu
		$menu = new Menu($gm_lang['other']);
		$menu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}", "submenu{$ff}");
		if ($SHOW_GEDCOM_RECORD)
		{
			$menu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['gedcom']['small']}");
			if ($this->show_changes == 'yes'  && $this->userCanEdit())
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
			$menu->addLink('clippings.php?action=add&amp;id='.$this->sid.'&amp;type=sour');
		}
		if ($SHOW_GEDCOM_RECORD)
		{
				// other / view_gedcom
				$submenu = new Menu($gm_lang['view_gedcom']);
				if ($this->show_changes == 'yes' && $this->userCanEdit())
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
				$submenu = new Menu($gm_lang['add_to_cart'], 'clippings.php?action=add&amp;id='.$this->sid.'&amp;type=sour');
				if (!empty($GM_IMAGES["clippings"]["small"]))
					$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['clippings']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
		}
		if ($this->source->canDisplayDetails() && !empty($this->uname))
		{
				// other / add_to_my_favorites
				$submenu = new Menu($gm_lang['add_to_my_favorites'], 'source.php?action=addfav&amp;sid='.$this->sid.'&amp;gid='.$this->sid);
				$submenu->addIcon("{$GM_IMAGE_DIR}/{$GM_IMAGES['gedcom']['small']}");
				$submenu->addClass("submenuitem{$ff}", "submenuitem_hover{$ff}");
				$menu->addSubmenu($submenu);
		}
		return $menu;
	}
}
// -- end of class
//-- load a user extended class if one exists
if (file_exists('includes/controllers/source_ctrl_user.php'))
{
	include_once 'includes/controllers/source_ctrl_user.php';
}
else
{
	class SourceController extends SourceControllerRoot
	{
	}
}
$controller = new SourceController();
$controller->init();
?>