<?php

class MarkiezenhofSearchModule {
	
	// Class information
	public $classname 		= "MarkiezenhofSearchModule";			// Name of the class
	public $display_name 	= "Markiezenhof";			// Name to display on the dropdown menu
	public $accesslevel		= 2;							// Minimum userlevel required to access this module
															// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	
	// Connection information
	public $method 			= "form";						// Either "link" or "form" or "SOAP"
	public $link			= "http://www.markiezenhof.nl/index.php?option=com_genealogie_zoeken&amp;Itemid=36&amp;sub=resultaat";
															// For link type: The link to the website, including the ?
															// For SOAP type: The link to the service
	public $wsdl 			= true;							// For SOAP type: if using WSDL or not
	
	// Input data definition. These are the fields that will display on the form, which may be prefilled and can be filled in by the user
	// The array key is one of those supported by the website which is linked or connected.
	// The array value is one of those supported by the external search controller. See the docu in that file.
	public $params			= array(
								"achternaam"				=> "stripsurname",
								"voornaam"	 				=> "firstname",
								"jaar_van"					=> "yrange1",
								"jaar_tot"					=> "yrange2"
								);

	// If the year range is selected, we must indicate if the website always expects two filled in values.
	// Possible values: "single" and "both"
	public $yearrange_type	= "both";
	
	// Array with values of the above params array,which must have their checkbox checked by default
	public $params_checked	= array("stripsurname");
	
	// For "form" type: Array with additional fields which will be hidden in the form, with the indicated value
	public $params_hidden = array(
								"zoekmethode_achternaam"	=> "exact",
								"zoekmethode_voornaam" 		=> "exact",
								"plaats"					=>	"all",
								"bron_a" 					=> "1",
								"bron_d" 					=> "1",
								"bron_t" 					=> "1",
								"bron_b" 					=> "1",
								"bron_g" 					=> "1",
								"bron_h" 					=> "1",
								"bron_o" 					=> "1",
								"bron_br" 					=> "1",
								"trefwoord"		 			=> "",
								"rol"						=> "all"
								);
								
	// For "form" type: name of the form to submit
	public $formname = "form_01";
	
	public function __construct() {
	}
}
?>