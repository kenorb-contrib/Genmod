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
 * @version $Id: ngv.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class NGVSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "NGVSearchModule";					// Name of the class

	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "Nederlandse Genealogisch Vereniging";
		$this->method 			= "link";
		$this->link				= "http://www.ngv.nl/genealogie/search.php?nnqualify=bevat&amp;mynickname=&amp;tqualify=bevat&amp;mytitle=&amp;sfqualify=bevat&amp;mysuffix=&amp;mybool=AND&amp;tree=-x--all--x-&amp;showspouse=yes&amp;";
		$this->params			= array(
									"lnqualify=is+gelijk+aan&amp;mylastname"	=> "surname",
									"fnqualify=begint+met&amp;myfirstname"		=> "firstname",
									"byqualify=&amp;mybirthyear" 				=> "gbyear",
									"bpqualify=is+gelijk+aan&amp;mybirthplace"	=> "bplace",
									"dyqualify=&amp;mydeathyear"				=> "gdyear",
									"dpqualify=is+gelijk+aan&amp;mydeathplace"	=> "dplace");
		$this->params_checked	= array("surname");				
	}
}
?>