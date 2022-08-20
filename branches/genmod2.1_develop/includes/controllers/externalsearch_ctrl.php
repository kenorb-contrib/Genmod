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
 * @version $Id: externalsearch_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
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
	private $hasrange = false;						// If the input fields contain a year range
	private $range1 = null;							// Name of the first range field (required to set a value if missing)
	private $range2 = null;							// Name of the second range field (required to set a value if missing)

	public function __construct($indi, $gedcomid="") {
		global $gm_user;
		
		if (!is_object($indi)) $indi = Person::GetInstance($indi, "", $gedcomid);
		
		if ($indi->gedcomid != GedcomConfig::$GEDCOMID) SwitchGedcom($gedcomid);
		
		if (!$gm_user->userExternalSearch()) {
			$this->userlevel = -1;
			$this->optioncount = 0;
		}
		else {
			if ($gm_user->userIsAdmin() || $gm_user->userGedcomAdmin()) $this->userlevel = 3;
			else if ($gm_user->userCanEdit()) $this->userlevel = 2;
			else if ($gm_user->userPrivAccess()) $this->userlevel = 1;
			else $this->userlevel = 0;
		}
		
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
		print "<form name=\"selarchive\" method=\"get\" action=\"individual.php\">\n";
		
		// 1. Search form title
		print "<table class=\"NavBlockTable ESearchFormTable\">\n";
		print "<tr><td colspan=\"2\" class=\"NavBlockHeader\">".GM_LANG_external_search."</td></tr>\n";
		
		// 2. Choose search at
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_choose."</td>\n";
		print "<td class=\"NavBlockField\">";
		print "<select name=\"selsearch\" onchange=\"sndReq('esearchform', 'extsearchformprint', true, 'pid', '".$this->indi->xref."', 'gedcomid', '".$this->indi->gedcomid."', 'formno', document.selarchive.selsearch.value); document.getElementById('esearchresults').innerHTML='';\">\n";
		foreach ($this->modules as $index => $modobj) {
			print "<option value=\"".$index."\"";
			if ($number == $index) print " selected=\"selected\"";
			print ">".$modobj->display_name."</option>\n";
		}
		print "</select></td>\n</tr></table></form>";
		
		if ($module->method != "form") print "<form name=\"extsearch\" method=\"get\" action=\"individual.php\">\n";
		else print "<form name=\"".$module->formname."\" method=\"post\" action=\"".$module->link."\" target=\"_blank\">\n";
		print "<table class=\"NavBlockTable ESearchFormTable\">\n";
		// Print the input fields
		foreach($module->params as $inputname => $formname) {
			if ($module->method == "form") $this->PrintInputField($formname, $inputname, (in_array($formname, $module->params_checked)));
			else $this->PrintInputField($formname, $formname, (in_array($formname, $module->params_checked)));
		}
			
		// Print the hidden input fields and the submit button
		print "<tr><td class=\"NavBlockFooter\" colspan=\"3\">\n";
		if ($module->method == "form") {
			foreach($module->params_hidden as $field => $value) {
				print "<input type=\"hidden\" name=\"".$field."\" value=\"".$value."\" />\n";
			}
		}
		// Print the submit button
		if (($module->method == "link" || $module->method == "form") && $this->hasrange && $module->yearrange_type == "both") {
			if ($module->method == "link") $formname = "extsearch";
			else $formname = $module->formname;
			$checkrange = "if (document.".$formname.".yrange1_checked.checked + document.".$formname.".yrange2_checked.checked == 1) {document.".$formname.".yrange2_checked.checked = 1;document.".$formname.".yrange1_checked.checked = 1;} if (document.".$formname.".yrange1_checked.checked) {if (document.".$formname.".".$this->range1.".value == '') {document.".$formname.".".$this->range1.".value = 1500;} if (document.".$formname.".".$this->range2.".value == '') {document.".$formname.".".$this->range2.".value = ".date("Y").";}};";
		}
		else $checkrange = "";
		print "<input type=\"submit\" name=\"".GM_LANG_search."\" value=\"".GM_LANG_search."\"";
		if ($module->method == "link") {
			$jsn = "var esurl = '".$module->link."';";
			$jsn .= "first = true;";
			foreach($module->params as $inputname => $formname) {
				$jsn .= "if (!first && document.extsearch.".$formname."_checked.checked && document.extsearch.".$formname.".value.length > 0) {";
				$jsn .= "esurl = esurl + '".$module->field_concat."'";
				$jsn .= "}";
				$jsn .= "if (document.extsearch.".$formname."_checked.checked && document.extsearch.".$formname.".value.length > 0) {";
				$jsn .= "esurl = esurl + '".$inputname.$module->field_val_concat."'+(escape(document.extsearch.".$formname.".value));";
				$jsn .= "first = false;";
				$jsn .= "}";
			}
			$jsn .= "window.open(esurl)";
			print " onclick=\"".$jsn."; return false;\" />";
			
/*			print " onclick=\"ESCheckInput(); return false;\" />";
			$js = "";
			$js .= $checkrange."\n";
			$js .= "\tvar esurl = '".$module->link."';\n";
			$js .= "\talert(esurl);\n";
			$js .= "\tfirst = true;\n";
			foreach($module->params as $inputname => $formname) {
				$js .= "\tif (!first && document.extsearch.".$formname."_checked.checked && document.extsearch.".$formname.".value.length > 0) {\n";
				$js .= "\t\tesurl = esurl + '".$module->field_concat."'\n";
				$js .= "\t}\n";
				$js .= "\tif (document.extsearch.".$formname."_checked.checked && document.extsearch.".$formname.".value.length > 0) {\n";
				$js .= "\t\tesurl = esurl + '".$inputname.$module->field_val_concat."'+(escape(document.extsearch.".$formname.".value));\n";
				$js .= "\t\tfirst = false;\n";
				$js .= "\t}\n";
			}
			print "\turl = url.replace(/'/g, '\\'');";
			$js .= "\twindow.open(esurl);\n";
			$js .= "\treturn false;\n";
			?>
			<script type="text/javascript">
			<!---
			function ESCheckInput() {
				<?php print $js; ?>
			}
			//-->
			</script>
			<?php
*/		}
		elseif ($module->method == "SOAP") {
			print " onclick=\"sndReq('esearchresults', 'extsearchsoapservice', true, 'formno', '".$number."', 'pid', '".$this->indi->xref."', 'gedcomid', '".$this->indi->gedcomid."'";
			foreach($module->params as $inputname => $formname) {
				print ", '".$inputname."', (document.extsearch.".$formname."_checked".".checked == 1 ? escape(document.extsearch.".$formname.".value) : '')";
			}
			print ");return false;\" />\n";
		}
		elseif ($module->method == "JSON") {
			print " onclick=\"sndReq('esearchresults', 'extsearchjsonservice', true, 'formno', '".$number."', 'pid', '".$this->indi->xref."', 'gedcomid', '".$this->indi->gedcomid."'";
			foreach($module->params as $inputname => $formname) {
				print ", '".$inputname."', (document.extsearch.".$formname."_checked".".checked == 1 ? escape(document.extsearch.".$formname.".value) : '')";
			}
			print ");return false;\" />\n";
		}
		elseif($module->method == "form") {
			print " onclick=\"";
//			if (isset($module->preopen) && $module->preopen != "") print "pre=window.open('".$module->preopen."', 'pre');alert('wacht');var obj=pre;alert(obj.location.href);return false;pre.close();";
			$str1 = "";
			$str2 = "";
			foreach($module->params as $inputname => $formname) {
				$str1 .= "if(document.".$module->formname.".".$formname."_checked".".checked == 0) {var ".$inputname."=document.".$module->formname.".".$inputname.".value; document.".$module->formname.".".$inputname.".value=''};";
				$str2 .= "if(typeof(".$inputname.") != 'undefined') {document.".$module->formname.".".$inputname.".value=".$inputname."};";
			}
			print $checkrange.$str1."document.".$module->formname.".submit();".$str2;
			print "return false;\" />\n";
		}
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
	
	private function PrintInputField($field, $inputname, $checked) {
		static $tabindex;
	
		/* This function supports the following fieldnames:
		 * fullname			Full name of the person, in the format: "firstname surname"
		 * surname			Surname of the person
		 * stripsurname		Surname of the person, with prefixes stripped off
		 * infix			Prefix of the persons surname
		 * firstname		First name of the person
		 * gbdate			Birthdate in gedcom format e.g. 12 MAY 1880
		 * gbyear			Birthyear
		 * bplace			Birthplace
		 * gddate			Deathdate in gedcom format e.g. 12 MAY 1880
		 * gdyear			Deathyear
		 * dplace			Deathplace
		 * ggender			Gedcom value (M, F, U) of the gender of the person
		 * gplace			General placename selection on any fact
		 * yrange1			General date selection on yearrange: start year
		 * yrange2			General date selection on yearrange: end year
		*/
		
		if (!isset($tabindex)) $tabindex = 1;
		switch($field) {
			case "fullname":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_name.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".NameFunctions::CheckNN(NameFunctions::GetNameInRecord($this->indi->gedrec))."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "surname":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_surname."</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".($this->indi->name_array[0][2] == "@N.N." ? "" : $this->indi->name_array[0][2])."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "stripsurname":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_surname."</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".($this->indi->name_array[0][2] == "@N.N." ? "" : NameFunctions::StripPrefix($this->indi->name_array[0][2]))."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "infix":
				print "<tr><td class=\"NavBlockLabel\">".GM_FACT_SPFX.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".($this->indi->name_array[0][2] == "@N.N." ? "" : trim(str_replace(NameFunctions::StripPrefix($this->indi->name_array[0][2]),"", $this->indi->name_array[0][2])))."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "firstname":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_firstname.":</td>";
				$this->PrintCheckSelect($field, $checked);
				$name = trim(substr($this->indi->name_array[0][0], 0, strpos($this->indi->name_array[0][0], "/")));
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".($name == "@P.N." ? "" : $name)."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "gbdate":
				print "<tr><td class=\"NavBlockLabel\">".GM_FACT_BIRT.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" id=\"".$inputname."\" size=\"25\" value=\"".GetGedcomValue("DATE", 2, $this->indi->bdate, "", false)."\" tabindex=\"".$tabindex."\" />&nbsp;";
				EditFunctions::PrintCalendarPopup($field);
				print "</td></tr>\n";
				break;
			case "gbyear":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_birthyear.":</td>";
				$this->PrintCheckSelect($field, $checked);
				$date = ParseDate(GetGedcomValue("DATE", 2, $this->indi->bdate, "", false));
				$year = $date[0]["year"];
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" id=\"".$inputname."\" size=\"25\" value=\"".($year == 0 ? "" : $year)."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "bplace":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_birthplac.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".GetGedcomValue("PLAC", 2, $this->indi->bplac, "", false)."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "gddate":
				print "<tr><td class=\"NavBlockLabel\">".GM_FACT_DEAT.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" id=\"".$inputname."\" size=\"25\" value=\"".GetGedcomValue("DATE", 2, $this->indi->ddate, "", false)."\" tabindex=\"".$tabindex."\" />&nbsp;";
				EditFunctions::PrintCalendarPopup($field);
				print "</td></tr>\n";
				break;
			case "gdyear":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_deathyear.":</td>";
				$this->PrintCheckSelect($field, $checked);
				$date = ParseDate(GetGedcomValue("DATE", 2, $this->indi->ddate, "", false));
				$year = $date[0]["year"];
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" id=\"".$inputname."\" size=\"25\" value=\"".($year == 0 ? "" : $year)."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "dplace":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_deathplac.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".GetGedcomValue("PLAC", 2, $this->indi->dplac, "", false)."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "ggender":
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_sex.":</td>";
				$this->PrintCheckSelect($field, $checked);
				print "<td class=\"NavBlockField\">";
				EditFunctions::PrintGender($field, $field, $this->indi->sex, $tabindex);
				print "</td></tr>\n";
				break;
			case "gplace":
				print "<tr><td class=\"NavBlockLabel\">".GM_FACT_PLAC.":</td>";
				$this->PrintCheckSelect($field, $checked);
				$plac = "";
				foreach ($this->indi->facts as $key =>$factobj) {
					if ($factobj->simpleplace != "") {
						$plac = GetGedcomValue("PLAC", 2, $factobj->simpleplace);
						break;
					}
				}
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"25\" value=\"".$plac."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "yrange1":
				$this->hasrange = true;
				$this->range1 = $inputname;
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_year_range_start.":</td>";
				$this->PrintCheckSelect($field, $checked);
				if ($this->indi->brec != "") {
					$year = ParseDate(GetGedcomValue("DATE", 2, $this->indi->brec, "", false));
					$byear = $year[0]["year"];
				}
				else $byear = "";
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"4\" value=\"".($byear == 0 ? "" : $byear)."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
			case "yrange2":
				$this->hasrange = true;
				$this->range2 = $inputname;
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_year_range_end.":</td>";
				$this->PrintCheckSelect($field, $checked);
				if ($this->indi->drec != "") {
					$year = ParseDate(GetGedcomValue("DATE", 2, $this->indi->drec, "", false));
					$dyear = $year[0]["year"];
				}
				else $dyear = "";
				print "<td class=\"NavBlockField\"><input name=\"".$inputname."\" size=\"4\" value=\"".($dyear == 0 ? "" : $dyear)."\" tabindex=\"".$tabindex."\" /></td></tr>\n";
				break;
		}
		$tabindex++;
	}
	
	private function PrintCheckSelect($field, $checked) {
		
		print "<td class=\"NavBlockLabel\"><input type=\"checkbox\" name=\"".$field."_checked\" ".($checked ? "checked=\"checked\" " : "")."value=\"yes\" /></td>";
	}
	
	public function GetParams($number) {
		
		if (is_null($this->modules)) $this->LoadModules();
		$module = $this->modules[$number];
		return $module->params;
	}

	public function PrintSoapServiceResults($number, $params) {
		
		print "<div class=\"NavBlockHeader\">".GM_LANG_um_results."</div>\n";
		print "<div class=\"ListTableContent\">\n";
		
		$module = $this->modules[$number];
		$query = $module->GetQuery($params);
		if (!$query) print GM_LANG_no_results;
		else {
			require_once("modules/soap/lib/nusoap.php");
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

	public function PrintJSONServiceResults($number, $params) {
		
		print "<div class=\"NavBlockHeader\">".GM_LANG_um_results."</div>\n";
		print "<div class=\"ListTableContent\">\n";
		
		$module = $this->modules[$number];
		//print_r($params);
		$query = $module->GetQuery($params);
		//print "query: ".$query;
		if (!$query) print GM_LANG_no_results;
		else {
			$json = $module->link . $query;
			//print $json;
			$result = file_get_contents($json);
			$module->PrintResults(json_decode($result));
		}
		print "</div>";
	}
	
	private function SoapPresent() {
		
		if (is_null($this->soap_present)) {
			if (file_exists("modules/soap/lib/nusoap.php")) $this->soap_present = true;
			else $this->soap_present = false;
		}
		return $this->soap_present;
	}
}
?>