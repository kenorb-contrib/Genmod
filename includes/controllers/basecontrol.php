<?php
/**
 * Base controller for all controller classes
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
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage Controllers
 * @version $Id: basecontrol.php,v 1.2 2005/10/27 09:09:54 roland-d Exp $
 */
 
/**
 * The base controller for all classes
 *
 * The base controller for all classes. Also check if it is a print preview.
 *
 * @author	Genmod Development Team
 * @param		string	$view		Show the data
 * @return 	string	Return the value of $view
 * @todo Update this description
 */
class BaseController {
	var $view = "";
	/**
	 * constructor for this class
	 */
	function BaseController() {
		if (isset($_REQUEST["view"])) $this->view = $_REQUEST["view"];
	}
	/**
	 * check if this controller should be in print preview mode
	 */
	function isPrintPreview() {
		if ($this->view=="preview") return true;
	}
}
?>