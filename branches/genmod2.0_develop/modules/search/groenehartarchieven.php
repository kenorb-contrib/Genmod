<?php

class GroenehartArchievenSearchModule {
	
	// Class information
	public $classname 		= "GroenehartArchievenSearchModule";// Name of the class
	public $display_name 	= "GroenehartArchieven";			// Name to display on the dropdown menu
	public $accesslevel		= 2;								// Minimum userlevel required to access this module
																// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	
	// Connection information
	public $method 			= "link";							// Either "link" or "SOAP"
	public $link			= "http://www.sahm.nl/lijst.asp?";
																// For link type: The link to the website, including the ?
																// For SOAP type: The link to the service
	public $wsdl 			= true;								// For SOAP type: if using WSDL or not
	public $searchcmd		= "search";							// For SOAP type: the method to be called for performing the search
	
	// Result information
	public $totalresults 	= null;								// Total number of persons returned
	public $indilist 		= null;								// Array containing the person objects created from the results
	
	// Input data definition
	// The array key is one of those supported by the website which is linked or connected.
	// The array value is one of those supported by the external search controller. See the docu in that file.
	public $params			= array(
								"Request" 		=> "fullname",
								"Plaats"		=> "gplace",
								"van"			=> "yrange1",
								"tot"	 		=> "yrange2");
	
	public $params_checked	= array("fullname");				// Array with values of the params array,which must have their checkbox checked by
																// default
	
	
	public function __construct() {
	}
}
?>