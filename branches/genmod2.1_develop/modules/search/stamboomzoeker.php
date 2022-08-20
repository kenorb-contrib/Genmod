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
 * @version $Id: stamboomzoeker.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class StamboomzoekerSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "StamboomzoekerSearchModule";	// Name of the class
	
	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "Stamboomzoeker";
		$this->method 			= "link";
		$this->link				= "http://www.stamboomzoeker.nl/search.php?l=nl&amp;";
		$this->params			= array(
									"sn"		=> "surname",
									"fn"		=> "firstname",
									"bd1" 		=> "yrange1",
									"bd2"		=> "yrange2",
									"bp"		=> "bplace");
		$this->params_checked	= array("surname");
	}
}
?>