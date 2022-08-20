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
 * @version $Id: openarchives.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class OpenArchivesSearchModule extends BaseExternalSearch {
	
	// Class information
	public $classname 			= "OpenArchivesSearchModule";	// Name of the class
	
	// Result information
	private $totalresults 		= 0;								// Total number of persons returned
	private $indilist 			= null;								// Array containing the person objects created from the results
	
	
	public function __construct() {
		parent::__construct();
		
		$this->display_name 	= "Open Archives";
		$this->method 			= "JSON";
		$this->link				= "https://api.openarch.nl/1.0/records/search.json?number_show=100&lang=en&";
		$this->archives_link	= "https://api.openarch.nl/1.0/stats/archives.json";
		$this->details_link		= "https://api.openarch.nl/1.0/records/show.gedcom";
		$this->click_link 		= "https://www.openarch.nl/show.php";
		$this->searchcmd		= "search";
		$this->params			= array(
									"name" 			=> "fullname"
									);
		$this->params_checked	= array(
									"fullname"
									);
	}
	
	// Construct and return the query
	public function GetQuery($params) {
		
		$query = "";
		$first = true;
		foreach($params as $param => $value) {
			if (!empty($value)) {
				if (!$first) $query .= "&";
				$first = false;
				$query .= $param."=".urlencode($value);
			}
		}
		if (empty($query)) return false;
		else return $query;
	}
	
	public function PrintResults($results) {
		
		$arch = json_decode(file_get_contents($this->archives_link));
		$archives = array();
		foreach ($arch as $index => $archive) {
			$archives[$archive->name] = $archive->archive;
//			print $archive->name ."set to".$archive->archive."<br />";
		}
		print "<br />Aantal resultaten: ".$results->response->number_found."<br />";
		if ($results->response->number_found > 0) {
			print "<table style=\"max-width: 80%;\">";
			foreach ($results->response->docs as $index => $out) {
				print "<tr>";
					print "<td>".$out->archive."</td>";
					print "<td>";
					if (array_key_exists($out->archive, $archives)) print "<a href=\"".$this->click_link."?identifier=".$out->identifier."&archive=".$archives[$out->archive]."\" target=\"_blank\">".$out->personname."</a></td>";
					else print $out->personname."</td>";
					print "<td>".$out->eventtype."</td>";
					print "<td>".$out->relationtype."</td>";
					print "<td>".$out->eventplace."</td>";
					print "<td>".(property_exists($out, "eventdate") ? (property_exists($out->eventdate, "day") ? $out->eventdate->day : "00")."/".(property_exists($out->eventdate, "month") ? $out->eventdate->month : "00")."/".$out->eventdate->year : "&nbsp;")."</td>";
				print "</tr>";
			}
			print "</table>";
/*
			foreach ($results->response->docs as $index => $out) {
				// print $this->details_link."?identifier=".$out->identifier."&archive=".$archives[$out->archive];
				$ged = file_get_contents($this->details_link."?identifier=".$out->identifier."&archive=".$archives[$out->archive]);
				// print $ged;
				$end = false;
				$pos1 = 0;
				$recs = array();
				while(!$end) {
					$pos2 = 0;
					// NOTE: find the start of the next record
					$pos2 = strpos($ged, "\n0", $pos1+1);
					if ($pos2 == 0) {
						$end = true;
						$recs[] = substr($ged, $pos1);
					}
					else {
						$recs[] = substr($ged, $pos1, $pos2-$pos1);
						$pos1 = $pos2+1;
					}
				}
				$indilist = array();
				foreach ($recs as $index => $rec) {
					print $rec;
					if (stristr($rec, "@ INDI")) {
						$ct = preg_match("/0 @(.*)@ INDI/", $rec, $match);
						$rec = str_replace(GetSubRecord(1, "1 FAMC", $rec), "", $rec);
						$rec = str_replace(GetSubRecord(1, "1 FAMS", $rec), "", $rec);
						$indilist[] = Person::GetInstance($match[1], $rec, "9999");
					}
				}
				break;
			}
			// Print the results
			foreach($indilist as $key => $person) {
				print $person->revname."<br />";
				foreach ($person->facts as $key => $factobj) {
					if ($factobj->fact != "NAME") {
						FactFunctions::PrintSimpleFact($factobj);
						FactFunctions::PrintFactNotes($factobj, 2);
						print "<br />";
					}
				}
				print "<br />";
			}
*/
		}
	}
}
?>