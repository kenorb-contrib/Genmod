<?php
/**
 * Class file for external search
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
 * @subpackage classes
 * @version $Id: databankrijerkerk.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class RijerkerkSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "RijerkerkSearchModule";			// Name of the class
	
	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "Genealogiedatabank Rijerkerk";
		$this->method 			= "link";
		$this->link				= "http://www.rijerkerk.net/databank/?a=search&";
		$this->params			= array(
									"sn"		=> "surname",
									"gv"		=> "firstname",
									"pl"		=> "gplace",
									"dd" 		=> "yrange1",
									"ddd"		=> "yrange2");
		$this->params_checked	= array("surname");	
	}
}
?>