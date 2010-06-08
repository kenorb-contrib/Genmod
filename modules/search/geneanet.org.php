<?php
/**
 * Class file for external search
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
 * @package Genmod
 * @subpackage classes
 * @version $Id: asso_class.php 190 2010-05-16 11:24:59Z sjouke $
 */

class GeneanetSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "GeneanetSearchModule";		// Name of the class
		
	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "GeneaNet";
		$this->method 			= "link";
		$this->link				= "http://search.geneanet.org/result.php?";
		$this->params			= array(
									"name"		=> "surname",
									"place"		=> "gplace",
									"start" 	=> "yrange1",
									"end"		=> "yrange2");
		$this->yearrange_type	= "single";
		$this->params_checked	= array("surname");
	}
}
?>