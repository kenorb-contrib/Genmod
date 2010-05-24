<?php

class GenealogieOnlineSearchModule {
	
	// Class information
	public $classname 		= "GenealogieOnlineSearchModule";	// Name of the class
	public $display_name 	= "GenealogieOnline";				// Name to display on the dropdown menu
	public $accesslevel		= 2;								// Minimum userlevel required to access this module
																// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	
	// Connection information
	public $method 			= "SOAP";							// Either "link" or "SOAP"
	public $link			= "http://www.genealogieonline.nl/pgv/genservice2.php?wsdl";
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
								"name" 			=> "fullname",
								"surname"		=> "surname",
								"gender"		=> "ggender",
								"birtdate" 		=> "gbdate",
								"birthplace" 	=> "bplace",
								"deathdate"		=> "gddate",
								"deathplace"	=> "dplace");
	
	public $params_checked	= array("fullname");				// Array with values of the params array,which must have their checkbox checked by
																// default
	
	
	public function __construct() {
	}
	
	// Construct and return the query
	public function GetQuery($params) {
		
		$query = "";
		$first = true;
		foreach($params as $param => $value) {
			if (!empty($value)) {
				if (!$first) $query .= "&";
				$first = false;
				$query .= strtoupper($param)."=".urlencode($value);
			}
		}
		if (empty($query)) return false;
		else return array("query" => $query);
	}
	
	public function PrintResults($results) {
		
		// Get the total results
		if (isset($results["totalResults"])) $this->totalresults = $results["totalResults"];
		else $results["totalResults"] = "0";
		print "Total results: ".$this->totalresults."<br /><br />";
		
		// Convert the data from the service to Genmod format
		$indilist = array();
		if (isset($results["persons"])) {
			foreach($results["persons"] as $field => $content) {
				if (is_numeric($field)) {
					$gedrec = "0 @".$content["PID"]."@ INDI\r\n";
					$gedrec .= "1 NAME ".$content["gedcomName"]."\r\n";
					if (!empty($content["gender"])) $gedrec .= "1 SEX ".$content["gender"]."\r\n";
					if (!empty($content["birthDate"]) || !empty($content["birthPlace"])) $gedrec .= "1 BIRT\r\n";
					if (!empty($content["birthDate"])) $gedrec .= "2 DATE ".$content["birthDate"]."\r\n";
					if (!empty($content["birthPlace"])) $gedrec .= "2 PLAC ".utf8_encode($content["birthPlace"])."\r\n";
					if (!empty($content["deathDate"]) || !empty($content["deathPlace"])) $gedrec .= "1 DEAT\r\n";
					if (!empty($content["deathDate"])) $gedrec .= "2 DATE ".$content["deathDate"]."\r\n";
					if (!empty($content["deathPlace"])) $gedrec .= "2 PLAC ".utf8_encode($content["deathPlace"])."\r\n";
					$data = array();
					$data["i_isdead"] = true;
					$data["i_file"] = 9999;
					$data["i_id"] = $content["PID"];
					$data["i_gedrec"] = $gedrec;
					
					$indilist[] = Person::GetInstance($content["PID"], $data, "9999");
				}
			}
		}
		
		// Sort the indilist on the persons name
		uasort($indilist, "ItemObjSort");
		
		// Print the results
		foreach($indilist as $key => $person) {
			print "<a href=\"http://www.genealogieonline.nl/pgv/individual.php?pid=".$person->xref."\" target=\"_blank\">".$person->revname.$person->addxref."</a><br />";
			foreach ($person->facts as $key => $factobj) {
				if ($factobj->fact != "NAME") {
					if ($factobj->fact == "SEX") print GM_LANG_sex.": ".GetGedcomValue("SEX", 1, $factobj->factrec);
					else print constant("GM_FACT_".$factobj->factref).": ".GetChangedDate($factobj->datestring)." ".GetGedcomValue("PLAC", 2, $factobj->simpleplace);
					print "<br />";
				}
			}
			print "<br />";
		}

		
		
		
	}
}
?>