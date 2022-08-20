<?php
/**
 * System for generating administrative menus.
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
 * $Id: adminmenu_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class AdminMenu {
	
	public $classname = "AdminMenu";	// Name of this class
	private $leftitems = array();		// Array of items to print on the left side
	private $rightitems = array();		// Array of items to print on the rigth side
	private $bartext = null;			// Main text to print in the topbottombar
	private $subbartext = null;			// Sub text to print in the topbottombar
	private $barstyle = null;			// Additional class for the topbottombar
	private $narrowbar = false;			// Single or double height topbottombar

	/**
	 * Constructor for the menu class
	 */
	public function __construct() {
		
	}

	public function SetBarText($text, $narrow=false) {
		
		$this->bartext = $text;
		$this->narrowbar = $narrow;
	}
	
	public function SetSubBarText($text) {
		
		$this->subbartext = $text;
	}
	
	public function SetBarStyle($style) {
		
		$this->barstyle = $style;
	}
	
	public function AddItem($help, $qm, $help2, $link, $text, $side) {
		
//		$string = "<td class=\"NavBlockLabel".ucfirst(strtolower($side))."\">";
		$string = "<td class=\"NavBlockLabel AdminNavBlockOption\">";
		if (!empty($help)) $string .= "<div class=\"HelpIconContainer\">".PrintHelpLink($help, $qm, $help2, false, true)."</div><div class=\"AdminNavBlockOptionText\">";
		$string .= "<a href=\"".$link."\">".$text."</a>";
		if (!empty($help)) $string .= "</div>";
		$string .= "</td>";
		if ($side == "left") $this->leftitems[] = $string;
		else $this->rightitems[] = $string;
	}

	public function PrintSpacer() {
	
		print "<tr><td class=\"NavBlockRowSpacer\" colspan=\"2\">&nbsp;</td></tr>";
	}
	
	public function PrintItems() {
		
		// Print the topbottombar
		?>
		<!-- Setup the top bar -->
		<?php
		print "<tr><td colspan=\"2\" class=\"NavBlockHeader ".(is_null($this->barstyle) ? "" : $this->barstyle)."\">";
		print ($this->narrowbar ? "" : "<div class=\"AdminNavBlockTitle\">").$this->bartext.($this->narrowbar ? "" : "</div>");
		if(!is_null($this->subbartext)) print $this->subbartext;
		print "</td></tr>";
		
		// Print the items
		$items = max(count($this->leftitems), count($this->rightitems));
		?>
		<!-- Print the options -->
		<?php
		for ($i=0; $i<$items; $i++) {
			print "<tr>";
			if (isset($this->leftitems[$i])) print $this->leftitems[$i];
			else print "<td class=\"NavBlockLabel AdminNavBlockOption\">&nbsp;</td>";
			if (isset($this->rightitems[$i])) print $this->rightitems[$i];
			else print "<td class=\"NavBlockLabel AdminNavBlockOption\">&nbsp;</td>";
			print "</tr>";
		}
	}
}
?>