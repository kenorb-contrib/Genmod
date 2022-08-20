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
 * @version $Id: base_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
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
	protected $action = "";					// Requested action to perform
	protected $view = "";					// View mode of the page (preview or blank)
	protected $xref = null;					// Xref of the record that is loaded by the child controller
	protected $gedcomid = null;				// Active gedcomid while loading the child controller
	protected $show_changes = null;			// Wether of not changes to the records will be displayed
	protected $uname = null;				// Name of the currently signed on user 
	protected $pagetitle = null;			// Title to show in the browser top bar
	protected $title = null;				// Title to show at the top of the page
	
	/**
	 * constructor for this class
	 */
	protected function __construct() {
		global $show_changes, $gm_user;
		
		if (isset($_REQUEST["view"])) $this->view = $_REQUEST["view"];
		if (isset($_REQUEST["action"])) $this->action = $_REQUEST["action"];
		
		$this->show_changes = $show_changes;
		
		$this->uname = $gm_user->username;
		
		$this->gedcomid = GedcomConfig::$GEDCOMID;
		
		$this->CheckAccess();
	}

	public function __get($property) {
		switch($property) {
			case "view":
				return $this->view;
				break;
			case "action":
				return $this->action;
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
			case "pagetitle":
				return $this->GetPageTitle();
				break;
			case "title":
				return $this->GetTitle();
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function __set($property, $value) {
		switch($property) {
			default:
				PrintGetSetError($property, get_class($this), "set");
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
	
	private function CheckAccess() {
		static $user, $gedadmin, $siteadmin;
		global $gm_user;
		
		// Build the array of pages where only logged in users have access
		if (!isset($user)) $user = array();
		if (!isset($gedadmin)) $gedadmin = array();
		if (!isset($siteadmin)) $siteadmin = array("admin.php", "downloadgedcom.php", "editlang.php");
		
		$noaccess = false;
		$script = substr(SCRIPT_NAME, 1);
		if (in_array($script, $user)) {
			if ($gm_user->username != "") return;
			else $noaccess = true;
		}
		if (!$noaccess && in_array($script, $gedadmin)) {
			if ($gm_user->UserGedcomAdmin()) return;
			else $noaccess = true;
		}
		if (!$noaccess && in_array($script, $siteadmin)) {
			if ($gm_user->UserIsAdmin()) return;
			else $noaccess = true;
		}
		if (!$noaccess) return;
		if (LOGIN_URL == "") header("Location: login.php?url=".$script.GetQueryString(true));
		else header("Location: ".LOGIN_URL."?url=".$script.GetQueryString(true));
		exit;
	}
		
}
?>