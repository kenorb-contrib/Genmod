<?php
/**
 * Class controller for external searches
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
 * @subpackage Controllers
 * @version $Id: action_ctrl.php 146 2010-03-20 08:18:10Z sjouke $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class ExternalSearchController {
	
	public $classname = "ExternalSearchController";	// Name of this class
	private $modules = null;						// Array with search module objects
	private $optioncount = null;					// Number of search options. If 0, no search modules are present
	private $modulelist = null;						// Array with filenames of modules (needed for inclusion)
	private $userlevel = null;						// Access level of the user calling this class
													// 0 is visitor, 1 is authenticated user, 2 is editor, 3 is (gedcom)admin.
	private $soap_present = null;					// If the soap extension is available
													
	private $addlink = null;						// for type is link: parameters to add to the link
	
	private $indi = null;							// Container for the indi object

	public function __construct($indi, $gedcomid="") {
		global $gm_user;
		
		if (!is_object($indi)) $indi = Person::GetInstance($indi, "", $gedcomid);
		
		if ($indi->gedcomid != GedcomConfig::$GEDCOMID) SwitchGedcom($gedcomid);
		if ($gm_user->userIsAdmin() || $gm_user->GedcomAdmin()) $this->userlevel = 3;
		else if ($gm_user->userCanEdit()) $this->userlevel = 2;
		else if ($gm_user->userPrivAccess()) $this->userlevel = 1;
		else $this->userlevel = 0;
		
		$this->indi = $indi;
	}
	
	public function __get($property) {
		switch($property) {
			case "optioncount":
				return $this->GetOptionCount();
				break;
			case "action":
				return $this->action;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	private function GetOptionCount() {
		
		if (is_null($this->optioncount)) {
			if (is_null($this->modules)) $this->LoadModules();
		}
		return count($this->modules);
	}
	
	private function GetModulelist() {
		
		if (is_null($this->modulelist)) {
			$this->modulelist = array();
			$d = @dir("modules/search/");
			if (is_object($d)) {
				while (false !== ($entry = $d->read())) {
					if ($entry != ".." && $entry != ".") {
						$ext = substr($entry, strrpos($entry, '.'));
						if ($ext == ".php") $this->modulelist[] = $entry;
					}
				}
				$d->close();
			}
		}
		return $this->modulelist;
	}
	
	public function PrintSearchForm($number=0) {
		
		// Do preliminary work
		if (is_null($this->modules)) $this->LoadModules();
		$module = $this->modules[$number];
		
		// Print the searchform
		print "<form name=\"extsearch\" method=\"get\">\n";
		
		// 1. Search form title
		print "<table class=\"width100\">\n";
		print "<tr><td colspan=\"3\" class=\"topbottombar\">".GM_LANG_external_search."</td></tr>\n";
		
		// 2. Choose search at
		print "<tr><td class=\"shade2\" colspan=\"2\">".GM_LANG_choose."</td>\n";
		print "<td class=\"shade1\">";
		print "<select name=\"selsearch\" onchange=\"sndReq('esearchform', 'extsearchformprint', 'pid', '".$this->indi->xref."', 'gedcomid', '".$this->indi->gedcomid."', 'formno', document.extsearch.selsearch.value); document.getElementById('esearchresults').innerHTML='';\">\n";
		foreach ($this->modules as $index => $modobj) {
			print "<option value=\"".$index."\"";
			if ($number == $index) print " selected=\"selected\"";
			print ">".$modobj->display_name."</option>\n";
		}
		print "</select></td>\n</tr>\n";
		
		// Print the input fields
		foreach($module->params as $inputname => $formname) {
			$this->PrintInputField($formname, (in_array($formname, $module->params_checked)));
		}
			
		// Print the submit button
		print "<tr><td class=\"shade2 center\" colspan=\"3\">\n";
		print "<input type=\"button\" name=\"".GM_LANG_search."\" value=\"".GM_LANG_search."\"";
		if ($module->method == "link") {
			print " onclick=\"window.open('".$module->link;
			$first = true;
			foreach($module->params as $inputname => $formname) {
				if (!$first) print "+'&";
				print $inputname."='+(document.extsearch.".$formname."_checked.checked ? escape(document.extsearch.".$formname.".value) : '')";
				$first = false;
			}
		}
		else {
			print " onclick=\"sndReq('esearchresults', 'extsearchservice', 'formno', '".$number."', 'pid', '".$this->indi->xref."', 'gedcomid', '".$this->indi->gedcomid."'";
			foreach($module->params as $inputname => $formname) {
				print ", '".$inputname."', (document.extsearch.".$formname."_checked".".checked == 1 ? escape(document.extsearch.".$formname.".value) : '')";
			}
		}
		print ")\" />\n";
		print "</td></tr>\n";
		// Close the form
		print "</table>\n";
		print "</form>\n";	
		
	}
	
	private function LoadModules() {
		
		if (is_null($this->modules)) {
			$mods = $this->GetModuleList();
			foreach ($mods as $key => $modname) {
				require_once("modules/search/".$modname);
			}
			$classes = get_declared_classes();
			foreach ($classes as $key => $class) {
				if (strpos($class, "SearchModule") !== false) {
					$object = new $class;
					if ($object->method == "link" || $this->SoapPresent()) {
						if ($object->accesslevel <= $this->userlevel) $this->modules[] = $object;
					}
				}
			}
//			sort($this->modules);
		}
		return $this->modules;
	}
	
	private function PrintInputField($field, $checked) {
		static $tabindex;
	
		/* This function supports the following fieldnames:
		 * fullname			Full name of the person, in the format: "firstname surname"
		 * surname			Surname of the person
		 * firstname		First name of the person
		 * gbdate			Birthdate in gedcom format e.g. 12 MAY 1880
		 * bplace			Birthplace
		 * gddate			Deathdate in gedcom format e.g. 12 MAY 1880
		 * dplace			Deathplace
		 * ggender			Gedcom value (M, F, U) of the gender of the person
		 * gplace			General placename selection on any fact
		 * yrange1			General date selection on yearrange: start year
		 * yrange2			General date selection on yearrange: end year
		*/
		
		if (!isset($tabindex)) $tabindex = 1;
		switch($field) {
			case "fullname":
				print "<tr><td class=\"shade2\">".GM_LANG_name.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".NameFunctions::CheckNN(NameFunctions::GetNameInRecord($this->indi->gedrec))."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "surname":
				print "<tr><td class=\"shade2\">".GM_LANG_surname."</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".($this->indi->name_array[0][2] == "@N.N." ? "" : $this->indi->name_array[0][2])."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "firstname":
				print "<tr><td class=\"shade2\">".GM_LANG_firstname.":</td>";
				$this->PrintCheckSelect($field, $checked);
				$name = trim(substr($this->indi->name_array[0][0], 0, strpos($this->indi->name_array[0][0], "/")));
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".($name == "@P.N." ? "" : $name)."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "gbdate":
				print "<tr><td class=\"shade2\">".GM_FACT_BIRT.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".GetGedcomValue("DATE", 2, $this->indi->bdate, "", false)."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "bplace":
				print "<tr><td class=\"shade2\">".GM_LANG_birthplac.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".GetGedcomValue("PLAC", 2, $this->indi->bplac, "", false)."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "gddate":
				print "<tr><td class=\"shade2\">".GM_FACT_DEAT.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".GetGedcomValue("DATE", 2, $this->indi->ddate, "", false)."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "dplace":
				print "<tr><td class=\"shade2\">".GM_LANG_deathplac.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".GetGedcomValue("PLAC", 2, $this->indi->dplac, "", false)."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "ggender":
				print "<tr><td class=\"shade2\">".GM_LANG_sex.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".$this->indi->sex."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "gplace":
				print "<tr><td class=\"shade2\">".GM_FACT_PLAC.":</td>";
				$this->PrintCheckSelect($field, $checked);
				$plac = "";
				foreach ($this->indi->facts as $key =>$factobj) {
					if ($factobj->simpleplace != "") {
						$plac = GetGedcomValue("PLAC", 2, $factobj->simpleplace);
						break;
					}
				}
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"25\" value=\"".$plac."\" tabindex=\"".$tabindex."\" /></td</tr>\n";
				break;
			case "yrange1":
				print "<tr><td class=\"shade2\">".GM_LANG_year_range_start.":</td>";
				$this->PrintCheckSelect($field, $checked);
				if ($this->indi->brec != "") {
					$year = ParseDate(GetGedcomValue("DATE", 2, $this->indi->brec, "", false));
					$byear = $year[0]["year"];
				}
				else $byear = "";
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"4\" value=\"".$byear."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "yrange2":
				print "<tr><td class=\"shade2\">".GM_LANG_year_range_end.":</td>";
				$this->PrintCheckSelect($field, $checked);
				if ($this->indi->drec != "") {
					$year = ParseDate(GetGedcomValue("DATE", 2, $this->indi->drec, "", false));
					$dyear = $year[0]["year"];
				}
				else $dyear = "";
				print "<td class=\"shade1\"><input name=\"".$field."\" size=\"4\" value=\"".$dyear."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
		}
		$tabindex++;
	}
	
	private function PrintCheckSelect($field, $checked) {
		
		print "<td class=\"shade2 center\"><input type=\"checkbox\" name=\"".$field."_checked\" ".($checked ? "checked=\"checked\" " : "")."value=\"yes\" />";
	}
	
	public function GetParams($number) {
		
		if (is_null($this->modules)) $this->LoadModules();
		$module = $this->modules[$number];
		return $module->params;
	}

	public function PrintServiceResults($number, $params) {
		
		print "<div class=\"topbottombar width100\" style=\"margin-left:10px; margin-top:2px; margin-right:0px;\">".GM_LANG_um_results."</div>\n";
		print "<div class=\"shade2 width100\" style=\"margin-left:10px; padding:10px;\">\n";
		
		$module = $this->modules[$number];
		$query = $module->GetQuery($params);
		if (!$query) print GM_LANG_no_results;
		else {
			require_once("soap/lib/nusoap.php");
			$client = new nusoap_client($module->link, ($module->wsdl ? "'wsdl'" : "''"), SystemConfig::$PROXY_ADDRESS, SystemConfig::$PROXY_PORT, SystemConfig::$PROXY_USER, SystemConfig::$PROXY_PASSWORD);
			if ($client->getError()) print "Connection error";
			else {
				if ($client->fault) {
					echo '<h2>Fault</h2><pre>';
					print_r($result);
					echo '</pre>';
				} 
				$client->soap_defencoding = 'UTF-8';
				$result = $client->call($module->searchcmd, $query);
				$module->PrintResults($result);
			}
		}
		print "</div>";
	}
	
	private function SoapPresent() {
		
		if (is_null($this->soap_present)) {
			if (file_exists("soap/lib/nusoap.php")) $this->soap_present = true;
			else $this->soap_present = false;
		}
		return $this->soap_present;
	}
}
?>