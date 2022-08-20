<?php
/**
 * System for generating menus.
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
 * $Id: menu_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class Menu {
	
	public $classname = "Menu";		// Name of this class
	public $seperator = false;		// If this menu object is just a separator line
	public $label = ' ';			// Label of the menu option
	public $labelpos = 'right';		// Tells where the text should be positioned relative to the picture options are up down left right
	public $link = '#';				// Link for the menu option: either # for JS or the actual link without server URL
	public $onclick = null;			// JS to run on click
	public $icon = null;			// Icon to show in front of the menu option
	public $hovericon = null;		// Icon to show when hovering over the option
	public $flyout = 'down';		// Options are up down left right
	public $class = '';				// CSS class for the item
	public $hoverclass = '';		// CSS class for the item when hovering over it
	public $submenuclass = '';		// CSS class for the submenu
	public $accesskey = null;		// Access key for the menu option
	public $parentmenu = null;		// Parent menu of this menu
	public $submenus;				// Array of submenu's for this menu

	/**
	 * Constructor for the menu class
	 * @param string $label		the label for the menu item (usually a gm_lang variable)
	 */
	public function __construct($label=' ', $strip=true) {
		$this->submenus = array();
		if ($strip) $this->addLabel(htmlspecialchars($label, ENT_QUOTES, "UTF-8"));
		else $this->addLabel($label);
	}

	public function hasSubMenus() {
		return count($this->submenus);
	}
	public function isSeperator() {
		$this->seperator = true;
	}

	public function addLabel($label=' ', $pos='right') {
		if ($label) $this->label = $label;
		$this->labelpos = $pos;
	}

	public function addLink($link='#') {
		$this->link = $link;
	}
	
	public function addOnclick($onclick) {
		$this->onclick = $onclick;
	}
	public function addFlyout($flyout='down') {
		$this->flyout = $flyout;
	}

	public function addClass($class, $hoverclass='', $submenuclass='') {
		$this->class = $class;
		$this->hoverclass = $hoverclass;
		$this->submenuclass = $submenuclass;
	}
	
	public function addAccesskey($accesskey) {
		$this->accesskey = $accesskey;
	}

	public function addSubMenu($obj) {
		$this->submenus[] = $obj;
	}

	public function addSeperator() {
		$submenu = new Menu();
		$submenu->isSeperator();
		$this->submenus[] = $submenu;
	}
	
	public function getMenu() {
		global
			$menucount,
			$TEXT_DIRECTION,
			$GM_IMAGES
		;
		if (!isset($menucount)) $menucount = 0;
		else $menucount++;
		if ($this->seperator) {
			$output = "<div id=\"menu{$menucount}\" class=\"seperator\";\">"
				."<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES['hline']['other']."\" height=\"3\" alt=\"\" />"
				."</div>\n";
			return $output;
		}
		$c = count($this->submenus);
		$output = "<div id=\"menu{$menucount}\" style=\"clear: both;\"";
		if (!empty($this->class)) $output .= " class=\"{$this->class}\"";
		$output .= ">\n";
		if ($this->link=="#") $this->link = "javascript:;";
		$link = "<a href=\"{$this->link}\" onmouseover=\""
		;
		if ($c >= 0) $link .= "show_submenu('menu{$menucount}_subs', 'menu{$menucount}', '{$this->flyout}'); ";
		if ($this->hoverclass !== null) $link .= "change_class('menu{$menucount}', '{$this->hoverclass}'); ";
		// if ($this->hovericon !== null) $link .= "change_icon('menu{$menucount}_icon', '{$this->hovericon}'); ";
		$link .= '" onmouseout="';
		if ($c >= 0) $link .= "timeout_submenu('menu{$menucount}_subs'); ";
		if ($this->hoverclass !== null) $link .= "change_class('menu{$menucount}', '{$this->class}'); ";
		// if ($this->hovericon !== null) $link .= "change_icon('menu{$menucount}_icon', '{$this->icon}'); ";
		if ($this->onclick !== null) $link .= "\" onclick=\"{$this->onclick}";
		if ($this->accesskey !== null) $link .= '" accesskey="'.$this->accesskey;
		$link .= "\">";
		if ($this->icon !== null) {
			$MenuIcon = "<img id=\"menu{$menucount}_icon\" src=\"{$this->icon}\" class=\"icon\" alt=\"".preg_replace("/\"/", '', $this->label).'" title="'.preg_replace("/\"/", '', $this->label).'" '." />";
			switch ($this->labelpos) {
			case "right":
				$output .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
				$output .= "<tr>";
				$output .= "<td valign=\"middle\">";
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "</a>";
				$output .= "</td>";
				$output .= "<td align=\"";
				if ($TEXT_DIRECTION=="rtl") $output .= "right";
				else $output .= "left";
				$output .= "\" valign=\"middle\" style=\"white-space: nowrap;\">";
				$output .= $link;
				$output .= $this->label;
				$output .= "</a></td>";
				$output .= "</tr></table>";
				break;
			case "left":
				$output .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
				$output .= "<tr>";
				$output .= "<td align=\"";
				if ($TEXT_DIRECTION=="rtl") $output .= "left";
				else $output .= "right";
				$output .= "\" valign=\"middle\" style=\"white-space: nowrap;\">";
				$output .= $link;
				$output .= $this->label;
				$output .= "</a></td>";
				$output .= "<td valign=\"middle\">";
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "</a>";
				$output .= "</td>";
				$output .= "</tr></table>";
				break;
			case "down":
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "<br />";
				$output .= $this->label;
				$output .= "</a>";
				break;
			case "up":
				$output .= $link;
				$output .= $this->label;
				$output .= "<br />";
				$output .= $MenuIcon;
				$output .= "</a>";
				break;
			default:
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "</a>";
			}
		}
		else {
			$output .= $link;
			$output .= $this->label;
			$output .= "</a>";
		}
		
		if ($c > 0) {
			$submenuid = "menu{$menucount}_subs";
			if ($TEXT_DIRECTION == 'ltr') $output .= '<div style="text-align: left;">';
			else	$output .= '<div style="text-align: right;">';
			$output .= "<div id=\"menu{$menucount}_subs\" class=\"{$this->submenuclass}\" style=\"position: absolute; visibility: hidden; z-index: 100;";
			// if ($this->flyout == 'right') {
				if ($TEXT_DIRECTION == 'ltr') $output .= ' left: 80px;';
				else $output .= ' right: 50px;';
			// }
			$output .= "\" onmouseover=\"show_submenu('{$this->parentmenu}'); show_submenu('{$submenuid}');\" onmouseout=\"timeout_submenu('menu{$menucount}_subs');\">\n";
			foreach($this->submenus as $submenu) {
				$submenu->parentmenu = $submenuid;
				$output .= $submenu->getMenu();
			}
			$output .= "</div></div>\n";
		}
		$output .= "</div>\n";
		return $output;
	}
	
	public function printMenu() {
		print $this->getMenu();
	}
}
?>