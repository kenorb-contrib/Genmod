<?php

class StamboomzoekerSearchModule {
	
	// Class information
	public $classname 		= "StamboomzoekerSearchModule";	// Name of the class
	public $display_name 	= "Stamboomzoeker";				// Name to display on the dropdown menu
	public $accesslevel		= 2;							// Minimum userlevel required to access this module
															// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	
	// Connection information
	public $method 			= "link";						// Either "link" or "form" or "SOAP"
	public $link			= "http://www.stamboomzoeker.nl/search.php?l=nl&amp;";
															// For link type: The link to the website, including the ?
															// For SOAP type: The link to the service
	public $wsdl 			= true;							// For SOAP type: if using WSDL or not
	
	// Input data definition
	// The array key is one of those supported by the website which is linked or connected.
	// The array value is one of those supported by the external search controller. See the docu in that file.
	public $params			= array(
								"sn"		=> "surname",
								"fn"		=> "firstname",
								"bd1" 		=> "yrange1",
								"bd2"		=> "yrange2",
								"bp"		=> "bplace");
								
	// If the year range is selected, we must indicate if the website always expects two filled in values.
	// Possible values: "single" and "both"
	public $yearrange_type	= "both";

	public $params_checked	= array("surname");				// Array with values of the params array,which must have their checkbox checked by default
								
	
	public function __construct() {
	}
}
?>