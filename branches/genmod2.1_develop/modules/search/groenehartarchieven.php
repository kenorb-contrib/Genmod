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
 * @version $Id: groenehartarchieven.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class GroenehartArchievenSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "GroenehartArchievenSearchModule";	// Name of the class
	
	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "GroenehartArchieven";
		$this->method 			= "link";
		$this->link				= "http://www.sahm.nl/lijst.asp?";
		$this->params			= array(
									"Request" 		=> "fullname",
									"Plaats"		=> "gplace",
									"van"			=> "yrange1",
									"tot"	 		=> "yrange2");
		$this->yearrange_type	= "single";
		$this->params_checked	= array("fullname");				
	}
}
?>