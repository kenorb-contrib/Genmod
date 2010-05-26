<?php

class NGVSearchModule {
	
	// Class information
	public $classname 		= "NGVSearchModule";					// Name of the class
	public $display_name 	= "Nederlandse Genealogisch Vereniging";// Name to display on the dropdown menu
	public $accesslevel		= 2;									// Minimum userlevel required to access this module
																	// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	
	// Connection information
	public $method 			= "link";								// Either "link" or "SOAP"
	public $link			= "http://www.ngv.nl/genealogie/search.php?nnqualify=bevat&amp;mynickname=&amp;tqualify=bevat&amp;mytitle=&amp;sfqualify=bevat&amp;mysuffix=&amp;mybool=AND&amp;tree=-x--all--x-&amp;showspouse=yes&amp;";
																	// For link type: The link to the website, including the ? or &amp;
																	// For SOAP type: The link to the service
	public $wsdl 			= true;									// For SOAP type: if using WSDL or not
	
	// Input data definition
	// The array key is one of those supported by the website which is linked or connected.
	// The array value is one of those supported by the external search controller. See the docu in that file.
	public $params			= array(
								"lnqualify=is+gelijk+aan&amp;mylastname"	=> "surname",
								"fnqualify=begint+met&amp;myfirstname"		=> "firstname",
								"byqualify=&amp;mybirthyear" 				=> "gbyear",
								"bpqualify=is+gelijk+aan&amp;mybirthplace"	=> "bplace",
								"dyqualify=&amp;mydeathyear"				=> "gdyear",
								"dpqualify=is+gelijk+aan&amp;mydeathplace"	=> "dplace");
								
	public $params_checked	= array("surname");				// Array with values of the params array,which must have their checkbox checked by default


	public function __construct() {
	}
}
?>