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
 * @version $Id: genealogieonline.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class GenealogieOnlineSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "GenealogieOnlineSearchModule";	// Name of the class
	
	// Result information
	private $totalresults 		= 0;								// Total number of persons returned
	private $indilist 			= null;								// Array containing the person objects created from the results
	
	
	public function __construct() {
		
		parent::__construct();
		
		$this->display_name 	= "GenealogieOnline";
		$this->method 			= "SOAP";
		$this->link				= "http://www.genealogieonline.nl/pgv/genservice2.php?wsdl";
		$this->wsdl 			= true;
		$this->searchcmd		= "search";
		$this->params			= array(
									"name" 			=> "firstname",
									"surname" 		=> "surname",
									"birthplace" 	=> "bplace",
									"deathplace"	=> "dplace");
		$this->params_checked	= array("firstname", "surname");
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
		print GM_LANG_hs_results." ".$this->totalresults."<br /><br />";
		// Convert the data from the service to Genmod format
		$indilist = array();
		if (isset($results["persons"])) {
			foreach($results["persons"] as $field => $content) {
				if (is_numeric($field)) {
					$gedrec = "0 @".$content["PID"]."@ INDI\r\n";
					$gedrec .= "1 NAME ".$content["gedcomName"]."\r\n";
					foreach($content as $key => $value) {
						$content[$key] = str_replace("*", "", $value);
						$content[$key] = str_replace("00000000", "", $value);
					}
					// if (!empty($content["gender"])) $gedrec .= "1 SEX ".$content["gender"]."\r\n";
					if (!empty($content["birthDateNum"]) || !empty($content["birthPlace"])) $gedrec .= "1 BIRT\r\n";
					if (!empty($content["birthDateNum"])) $gedrec .= "2 DATE ".substr($content["birthDateNum"],0,4)."\r\n";
					if (!empty($content["birthPlace"])) $gedrec .= "2 PLAC ".utf8_encode($content["birthPlace"])."\r\n";
					if (!empty($content["deathDateNum"]) || !empty($content["deathPlace"])) $gedrec .= "1 DEAT\r\n";
					if (!empty($content["deathDateNum"])) $gedrec .= "2 DATE ".substr($content["deathDateNum"],0,4)."\r\n";
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