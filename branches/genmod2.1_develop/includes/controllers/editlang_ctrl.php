<?php
/**
 * Display a diff between two language files to help in translating.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 = 2007 Genmod Development Team
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
 * @subpackage Languages
 * @version $Id: editlang_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class EditLangController extends BaseController {
	
	public $classname = "EditLangController";	// Name of this class
	private $hide_translated = false;			// If we should hide already translated texts
	private $language1 = "english";				// Language for providing the base text
	private $language2 = null;					// Language that should be translated
	private $langhelp = array();				// Aux array for comparing languages
	private $file_type = "lang";				// Type of texts to be translated: lang, help_text or facts
	private $execute = null;					// True if a choice has to be executed (not on first display)
	private $query_string = '';					// Query string to use in JS
	private $sorted_langs = array();			// Array of language names in the users language
	private $lang_shortcut = null;				// Shortcut of the language to be exported
	
	public function __construct() {
		global $LANGUAGE, $language_settings, $QUERY_STRING;
		
		parent::__construct();
		
		if (isset($_REQUEST['hide_translated'])) $this->hide_translated = $_REQUEST['hide_translated'];
		if (isset($_REQUEST['language1'])) $this->language1 = $_REQUEST['language1'];
		if (isset($_REQUEST['language2'])) $this->language2 = $_REQUEST['language2'];
		else $this->language2 = $LANGUAGE;
		if (isset($_REQUEST['file_type'])) $this->file_type = $_REQUEST['file_type'];
		if (isset($_REQUEST['execute'])) $this->execute = $_REQUEST['execute'];
		if (isset($language_settings[$this->language2]["lang_short_cut"])) $this->lang_shortcut = $language_settings[$this->language2]["lang_short_cut"];
		$this->query_string = preg_replace("/&amp;/", "&", $QUERY_STRING);
		$this->query_string = preg_replace("/&&/", "&", $this->query_string);
		if (strpos($this->query_string,"&dv=")) $this->query_string = substr($this->query_string,0,strpos($this->query_string,"&dv="));
		
		if ($this->action == "debug" && !is_null($this->execute)) {
			if (isset($_REQUEST["DEBUG_LANG"])) $_SESSION["DEBUG_LANG"] = $_REQUEST["DEBUG_LANG"];
			else $_SESSION["DEBUG_LANG"] = "no";
		}
		$this->SortLanguageTable();
	}
	
	public function __get($property) {
		switch ($property) {
			case "hide_translated":
				return $this->hide_translated;
				break;
			case "language1":
				return $this->language1;
				break;
			case "language2":
				return $this->language2;
				break;
			case "file_type":
				return $this->file_type;
				break;
			case "execute":
				return $this->execute;
				break;
			case "query_string":
				return $this->query_string;
				break;
			case "lang_shortcut":
				return $this->lang_shortcut;
				break;
			case "sorted_langs":
				return $this->sorted_langs;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	public function __set($property, $value) {
		switch ($property) {
			case "langhelp":
				$this->langhelp = $value;
				break;
			default:
				return parent::__set($property, $value);
				break;
		}
	}
	
	protected function GetPageTitle() {
		
		switch ($this->action) {
			case "edit": 
				return GM_LANG_edit_lang_utility;
				break;
			case "export": 
				return GM_LANG_export_lang_utility;
				break;
			case "compare": 
				return GM_LANG_compare_lang_utility;
				break;
			default: 
				return GM_LANG_edit_langdiff;
				break;
		}
	}
	
	private function SortLanguageTable() { 
		global $gm_language, $language_settings;
		
		// Sort the Language table into localized language name order
		// NB we can only work on the active languages, inactive ones are not in the DB!
		foreach ($gm_language as $key => $value){
			if ($language_settings[$key]["gm_lang_use"]) {
				$d_LangName = "lang_name_".$key;
				$this->sorted_langs[$key] = constant("GM_LANG_".$d_LangName);
			}
		}
		asort($this->sorted_langs);
	}
	
	public function ShowLanguageCompare($lang1, $lang2, $facts = false, $help = false) {
		print "<tr><td colspan=\"2\" class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_additions.":</td></tr>";
		$count=0;
		foreach($lang1 as $key=>$value) {
			if (!array_key_exists($key, $lang2)) {
				print "<tr><td class=\"NavBlockLabel AdminNavBlockLabel\">\n";
				print $key;
				print "</td>\n";
				print "<td class=\"NavBlockField\">".$value."</td></tr>";
				$count++;
			}
		}
		if ($count==0) print "<tr><td colspan=\"2\" class=\"NavBlockLabel AdminNavBlockLabel\">".GM_LANG_no_additions."</td></tr>";
		
		print "<tr><td colspan=\"2\" class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_subtractions.":</td></tr>";
		$count = 0;
		foreach($lang2 as $key=>$value) {
			if (!array_key_exists($key, $lang1)) {
				print "<tr><td class=\"NavBlockLabel AdminNavBlockLabel\">\n";
				if (!$facts && !$help) print constant("GM_LANG_".$key);
				else if ($facts) print constant("GM_FACT_".$key);
				else if ($help) print $this->langhelp[$key];
				print "</td>\n";
				print "<td class=\"NavBlockField\">".$value."</td></tr>";
				$count++;
			}
		}
		if ($count==0) print "<tr><td colspan=\"2\" class=\"NavBlockLabel AdminNavBlockLabel\">".GM_LANG_no_subtractions."</td></tr>";
	}
}
?>