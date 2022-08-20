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
 * @version $Id: stadsarchiefbreda.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class BredaSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "BredaSearchModule";			// Name of the class


	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "Stadsarchief Breda";
		$this->method 			= "form";
		$this->link				= "http://www.stadsarchief.breda.nl/index.php?option=com_genealogie_zoeken&Itemid=7&sub=resultaat";
		$this->params			= array(
									"achternaam"				=> "stripsurname",
									"tussenvoegsel"				=> "infix",
									"voornaam"	 				=> "firstname",
									"datum_van_jaar" 			=> "yrange1",
									"datum_tot_jaar" 			=> "yrange2"
									);
		$this->params_checked	= array("stripsurname");
		$this->params_hidden 	= array(
									"zoekmethode_achternaam"	=> "exact",
									"zoekmethode_voornaam" 		=> "exact",
									"rol" 						=> "all",
									"formsent" 					=> "1",
									"plaats_a" 					=> "1",
									"plaats[B]" 				=> "1",
									"plaats[G]" 				=> "1",
									"plaats[N]" 				=> "1",
									"plaats[P]" 				=> "1",
									"plaats[R]" 				=> "1",
									"plaats[T]" 				=> "1",
									"bron_a" 					=> "1",
									"bron_d" 					=> "1",
									"bron_t" 					=> "1",
									"bron_b" 					=> "1",
									"bron_g" 					=> "1",
									"bron_h" 					=> "1",
									"bron_o" 					=> "1",
									"datum_van_dag" 			=> "01",
									"datum_van_maand" 			=> "01",
									"datum_tot_dag" 			=> "31",
									"datum_tot_maand" 			=> "12",
									"diversen"					=> ""
									);
		$this->formname 		= "form_01";
	}
}
?>