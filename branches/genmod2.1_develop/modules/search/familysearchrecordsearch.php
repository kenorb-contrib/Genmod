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
 * @version $Id: familysearchrecordsearch.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class FamilySearchRecordSearchSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "FamilySearchRecordSearchSearchModule";	// Name of the class
	
	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "FamilySearch";
		$this->method 			= "link";
		$this->link				= "https://www.familysearch.org/search/records#count=20&amp;query=%2B";
		$this->params			= array(
									"givenname"		=> "firstname",
									"surname"		=> "surname",
									"birth_year" 	=> "gbyear",
									"birth_place"	=> "bplace"
									);
		$this->params_checked	= array("surname");
		
		$this->field_concat		= "%20%2B";
		$this->field_val_concat	= "%3A";
		
	}
}
?>