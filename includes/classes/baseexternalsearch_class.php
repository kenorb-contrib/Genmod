<?php
/**
 * Base Class file for external searches
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
 * @version $Id: baseexternalsearch_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

abstract class BaseExternalSearch {
	
	// Class information
	public $classname 			= "BaseExternalSearch";		// Name of the class
	protected $display_name		= "Default";				// Name to display on the dropdown menu
	protected $accesslevel		= 2;						// Minimum userlevel required to access this module
															// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	
	// Connection information
	protected $method 			= "form";					// Either "link" or "form" or "SOAP" or "JSON"
	protected $link				= "";
															// For link type: The link to the website, including the ?
															// For SOAP type: The link to the service
	protected $wsdl 			= true;						// For SOAP type: if using WSDL or not
	protected $searchcmd		= "search";					// For SOAP type: the method to be called for performing the search
	
	// Input data definition. These are the fields that will display on the form, which may be prefilled and can be filled in by the user
	// The array key is one of those supported by the website which is linked or connected.
	// The array value is one of those supported by the external search controller. See the docu in that file.
	protected $params			= array();
		
	// If the year range is selected, we must indicate if the website always expects two filled in values.
	// Possible values: "single" and "both"
	protected $yearrange_type	= "both";

	// Array with values of the above params array,which must have their checkbox checked by default
	protected $params_checked	= array();
	
	// For "form" type: Array with additional fields which will be hidden in the form, with the indicated value
	protected $params_hidden 	= array();
								
	// For "form" type: name of the form to submit
	protected $formname 		= "";
	
	// For "link" type: The character that concatenates the form fields in the URL
	protected $field_concat		= "&";
	
	// For "link" type: The character that concatenates the form fields and values in the URL
	protected $field_val_concat	= "=";
	
	public function __construct() {
	}
	
	public function __get($property) {
		switch ($property) {
			case "display_name":
				return $this->display_name;
				break;
			case "accesslevel":
				return $this->accesslevel;
				break;
			case "method":
				return $this->method;
				break;
			case "link":
				return $this->link;
				break;
			case "wsdl":
				return $this->wsdl;
				break;
			case "searchcmd":
				return $this->searchcmd;
				break;
			case "params":
				return $this->params;
				break;
			case "yearrange_type":
				return $this->yearrange_type;
				break;
			case "params_checked":
				return $this->params_checked;
				break;
			case "params_hidden":
				return $this->params_hidden;
				break;
			case "formname":
				return $this->formname;
				break;
			case "field_concat":
				return $this->field_concat;
				break;
			case "field_val_concat":
				return $this->field_val_concat;
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
}
?>