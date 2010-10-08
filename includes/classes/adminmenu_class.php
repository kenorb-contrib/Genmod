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
 * $Id$
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
	private $barstyle = null;			// Additional style for the topbottombar
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
		
		$string = "<div class=\"admin_item_".$side."\">";
		if (!empty($help)) $string .= "<div class=\"helpicon\">".PrintHelpLink($help, $qm, $help2, false, true)."</div><div class=\"description\">";
		$string .= "<a href=\"".$link."\">".$text."</a></div>";
		if (!empty($help)) $string .= "</div>";
		if ($side == "left") $this->leftitems[] = $string;
		else $this->rightitems[] = $string;
	}
	
	public function PrintItems() {
		
		// Print the topbottombar
		?>
		<!-- Setup the top bar -->
		<?php
		print "<div class=\"admin_topbottombar\"".(is_null($this->barstyle) ? "" : "style=\"".$this->barstyle."\"").">";
		print ($this->narrowbar ? "" : "<h3>").$this->bartext.($this->narrowbar ? "" : "</h3>");
		if(!is_null($this->subbartext)) print $this->subbartext;
		print "</div>";
		
		// Print the items
		$items = max(count($this->leftitems), count($this->rightitems));
		?>
		<!-- Print the options -->
		<?php
		print "<div class=\"admin_item_box\">";
		for ($i=0; $i<$items; $i++) {
			if (isset($this->leftitems[$i])) print $this->leftitems[$i];
			else print "<div class=\"admin_item_left\">&nbsp;</div>";
			if (isset($this->rightitems[$i])) print $this->rightitems[$i];
			else print "<div class=\"admin_item_right\">&nbsp;</div>";
		}
		print "</div>";
	}
}
?>