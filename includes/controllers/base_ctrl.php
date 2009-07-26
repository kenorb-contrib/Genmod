<?php
/**
 * Base controller for all controller classes
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
 * @subpackage Controllers
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * The base controller for all classes
 *
 * @author	Genmod Development Team
 * @param		string	$view		Show the data
 * @return 	string	Return the value of $view
 * @todo Update this description
 */
abstract class BaseController {
	public $classname = "BaseController";	// Name of this class
	protected $view = "";					// View mode of the page (preview or blank)
	protected $xref = null;					// Xref of the record that is loaded by the child controller
	protected $gedcomid = null;				// Active gedcomid while loading the child controller
	protected $show_changes = null;			// Wether of not changes to the records will be displayed
	protected $uname = null;				// Name of the currently signed on user 
	
	/**
	 * constructor for this class
	 */
	protected function __construct() {
		global $show_changes, $Users, $GEDCOMID;
		
		if (isset($_REQUEST["view"])) $this->view = $_REQUEST["view"];
		if (!empty($_REQUEST["action"])) $this->action = $_REQUEST["action"];
		
		$this->show_changes = $show_changes;
		
		$this->uname = $Users->GetUserName();
		
		$this->gedcomid = $GEDCOMID;
		
	}

	public function __get($property) {
		switch($property) {
			case "view":
				return $this->view;
				break;
			case "xref":
				return $this->xref;
				break;
			case "gedcomid":
				return $this->gedcomid;
				break;
			case "show_changes":
				return $this->show_changes;
				break;
			case "uname":
				return $this->uname;
				break;
			default:
				print "<span class=\"error\">Invalid property ".$property." for __get in base controller</span><br />";
				break;
		}
	}
		
	/**
	 * check if this controller should be in print preview mode
	 */
	public function isPrintPreview() {
		if ($this->view == "preview") return true;
		else return false;
	}
}
?>