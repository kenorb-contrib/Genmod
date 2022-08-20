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
 * @version $Id: Streekarchiefbommelerwaard.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class BommelerwaardSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "BommelerwaardSearchModule";	// Name of the class
	
	
	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "Streekarchief Bommelerwaard";
		$this->method 			= "form";
		$this->link				= "http://www.streekarchiefbommelerwaard.nl/index.php?option=com_genealogie_zoeken&Itemid=187&sub=resultaat";
		$this->params			= array(
									"achternaam"				=> "stripsurname",
									"tussenvoegsel"				=> "infix",
									"voornaam"	 				=> "firstname",
									"jaar_van" 					=> "yrange1",
									"jaar_tot" 					=> "yrange2"
									);
		$this->params_checked	= array("stripsurname");
		$this->params_hidden 	= array(
									"plaats" 					=> "all",
									"bron_a" 					=> "1",
									"bron_d" 					=> "1",
									"bron_g" 					=> "1",
									"bron_t" 					=> "1",
									"bron_h" 					=> "1",
									"bron_b" 					=> "1",
									"bron_o" 					=> "1",
									"bron_br" 					=> "1",
									"bron_bp" 					=> "1",
									"rol" 						=> "all"
									);
		$this->formname 		= "form_01";
	}
}
?>