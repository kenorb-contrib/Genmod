<?php

class RijerkerkSearchModule {
	
	// Class information
	public $classname 		= "RijerkerkSearchModule";			// Name of the class
	public $display_name 	= "Genealogiedatabank Rijerkerk";	// Name to display on the dropdown menu
	public $accesslevel		= 2;								// Minimum userlevel required to access this module
																// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	
	// Connection information
	public $method 			= "link";							// Either "link" or "SOAP"
	public $link			= "http://www.rijerkerk.net/databank/?a=search&amp;";
																// For link type: The link to the website, including the ?
																// For SOAP type: The link to the service
	public $wsdl 			= true;								// For SOAP type: if using WSDL or not
	
	// Input data definition
	// The array key is one of those supported by the website which is linked or connected.
	// The array value is one of those supported by the external search controller. See the docu in that file.
	public $params			= array(
								"sn"		=> "surname",
								"gv"		=> "firstname",
								"pl"		=> "gplace",
								"dd" 		=> "yrange1",
								"ddd"		=> "yrange2");
	public $params_checked	= array("surname");				// Array with values of the params array,which must have their checkbox checked by default
								
	
	public function __construct() {
	}
}
?>