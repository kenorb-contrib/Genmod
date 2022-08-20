<?php
/**
 * Core, language related  Functions that can be used by any page in GM
 *
 * The functions in this file are common to all GM pages and include all
 * language related functions.
 * All database I/O is handled here, except for the install procedure.
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
 * @version $Id: functions_language_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class LanguageFunctions {
	
	/**
	 * Load the English language
	 *
	 * This function will check if there is a language present, if not, the
	 * language variables are loaded from the English language file.  
	 *
	 * @author	Genmod Development Team
	 * @todo       add panic page if no language is present
	 * @todo		Check why language from files are loaded
	 * @param	     boolean	     $return		Whether or not to return the values
	 * @param	     string	     $help		Whether or not to load the help language 
	 * @return 	array          The array with the loaded language
	 */
	public function LoadEnglish($return=false, $help=false, $altlang = false) {
		global $DBCONN, $LANGUAGE, $language_settings;
		//print $LANGUAGE;
		$temp = array();
		if (CONFIGURED && $DBCONN->connected) {
			$sql = "SELECT COUNT(*) as total FROM ".TBLPREFIX."language";
			if ($help) $sql .= "_help";
			$res = NewQuery($sql);
			if ($res) $total_columns = $res->FetchAssoc($res->result);
			else $total_columns["total"] = 0 ;
			if ($total_columns["total"] > 0) {
				if (!$return) {
				// NOTE: include the extra english file BEFORE the DB
					if (file_exists(SystemConfig::$GM_BASE_DIRECTORY . "languages/lang.".$language_settings["english"]["lang_short_cut"].".extra.php")) {
						require_once SystemConfig::$GM_BASE_DIRECTORY . "languages/lang.".$language_settings["english"]["lang_short_cut"].".extra.php";
					}
					if ($altlang) {
						// NOTE: include the extra file
						if (file_exists(SystemConfig::$GM_BASE_DIRECTORY . "languages/lang.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php")) {
							require_once SystemConfig::$GM_BASE_DIRECTORY . "languages/lang.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php";
						}
					}
				}
				if ($altlang) $sql = "SELECT lg_string, lg_english, lg_".$LANGUAGE." FROM ".TBLPREFIX."language";
				else $sql = "SELECT lg_string, lg_english FROM ".TBLPREFIX."language";
				if ($help) $sql .= "_help";
				$res = NewQuery($sql);
				while ($row = $res->FetchAssoc($res->result)) {
					if (!$return) {
						
						if (!empty($row["lg_".$LANGUAGE])) {
							if (!defined("GM_LANG_".$row["lg_string"])) {
								define("GM_LANG_".$row["lg_string"], $row["lg_".$LANGUAGE]);
							}
						}
						else {
							if ($altlang && !defined("GM_LANG_".$row["lg_string"])) {
								define("GM_LANG_".$row["lg_string"], $row["lg_english"]);
							}
						}
					}
					else {
						if (!empty($row["lg_".$LANGUAGE])) $temp[$row["lg_string"]] = $row["lg_".$LANGUAGE];
						else $temp[$row["lg_string"]] = $row["lg_english"];
					}
				}
			}
			else {
				$number = 0;
				WriteToLog("LoadEnglish-&gt; Language table not available in database. Trying to load language from file.","E","S");
				// NOTE: If both string arrays are empty, no data came out of the database
				// NOTE: We load the language from the file and write an error to the log
				if (file_exists("languages/lang.en.txt")) {
					// NOTE: Load the English language into memory
					$lines = file("languages/lang.en.txt");
					$number = 0;
					foreach ($lines as $key => $line) {
						$data = preg_split("/\";\"/", $line, 2);
						// NOTE: Add the language variable to the language array
						if (!$return) {
							define("GM_LANG_".substr($data[0],1), substr(trim($data[1]),0,(strlen(trim($data[1]))-1)));
							$number++;
						}
						else $temp[$row["lg_string"]] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
					}
					if ($number > 0) {
						WriteToLog("LoadEnglish-&gt; Language successfully loaded from file.","I","S");
					}
					else WriteToLog("LoadEnglish-&gt; Language was not loaded from file.","E","S");
				}
	
				if (file_exists("languages/help_text.en.txt")) {
					// NOTE: Load the English help language into memory since we have no database
					$lines = file("languages/help_text.en.txt");
					$number = 0;
					foreach ($lines as $key => $line) {
						$data = preg_split("/\";\"/", $line, 2);
						// NOTE: Add the language variable to the language array
						if (!$return) {
							define("GM_LANG_".substr($data[0],1), substr(trim($data[1]),0,(strlen(trim($data[1]))-1)));
							$number++;
						}
						else $temp[$row["lg_string"]] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
					}
					if ($number > 0) WriteToLog("LoadEnglish-&gt; Language help successfully loaded from file.","I","S");
					else WriteToLog("LoadEnglish-&gt; Language help was not loaded from file.","E","S");
				}
			}
		}
		if ($return) return $temp;
	}
	// param return returns the factarray for editing purposes
	// param altlang if true fills in missing texts in the designated language with english ones
	public function LoadEnglishFacts($return=false, $altlang = false) {
		global $DBCONN, $LANGUAGE, $language_settings;
		
		$temp = array();
		if (CONFIGURED && $DBCONN->connected) {
			$sql = "SELECT COUNT(*) as total FROM ".TBLPREFIX."facts";
			$res = NewQuery($sql);
			if ($res) $total_columns = $res->FetchAssoc($res->result);
			else $total_columns["total"] = 0 ;
			if ($total_columns["total"] > 0) {
				// Load the extra files BEFORE the DB
				if (!$return) {
						// NOTE: include the extra file
					if (file_exists(SystemConfig::$GM_BASE_DIRECTORY . "languages/facts.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php")) {
							require_once SystemConfig::$GM_BASE_DIRECTORY . "languages/facts.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php";
					}
					// NOTE: include the extra english file
					if ($altlang && file_exists(SystemConfig::$GM_BASE_DIRECTORY . "languages/facts.".$language_settings["english"]["lang_short_cut"].".extra.php")) {
						require_once SystemConfig::$GM_BASE_DIRECTORY . "languages/facts.".$language_settings["english"]["lang_short_cut"].".extra.php";
					}
				}
				if ($altlang) $sql = "SELECT lg_string, lg_english, lg_".$LANGUAGE." FROM ".TBLPREFIX."facts";
				else $sql = "SELECT lg_string, lg_english FROM ".TBLPREFIX."facts";
				$res = NewQuery($sql);
				while ($row = $res->FetchAssoc($res->result)) {
					if (!$return) {
						if (!empty($row["lg_".$LANGUAGE])) {
							if (!defined("GM_FACT_".$row["lg_string"])) {
								define("GM_FACT_".$row["lg_string"], $row["lg_".$LANGUAGE]);
							}
						}
						else {
							if ($altlang && !defined("GM_FACT_".$row["lg_string"])) {
								define("GM_FACT_".$row["lg_string"], $row["lg_english"]);
							}
						}
					}
					else {
						if (!empty($row["lg_".$LANGUAGE])&& $altlang) $temp[$row["lg_string"]] = $row["lg_".$LANGUAGE];
						else $temp[$row["lg_string"]] = $row["lg_english"];
					}
				}
			}
			else {
				$number = 0;
				WriteToLog("LoadEnglishFacts-&gt; Facts table not available in database. Trying to load facts from file.","E","S");
				// NOTE: If both string arrays are empty, no data came out of the database
				// NOTE: We load the language from the file and write an error to the log
				if (file_exists("languages/facts.en.txt")) {
					$found = false;
					// NOTE: Load the English language into memory
					$lines = file("languages/facts.en.txt");
					foreach ($lines as $key => $line) {
						$data = preg_split("/\";\"/", $line, 2);
						// NOTE: Add the facts variable to the facts array
						if (!$return) {
							define("GM_FACT_".substr($data[0],1), substr(trim($data[1]),0,(strlen(trim($data[1]))-1)));
							$found = true;
						}
						else $temp[$row["lg_string"]] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
					}
					if (!$return) {
						if ($found) {
							WriteToLog("LoadEnglishFacts-&gt; Factarray successfully loaded from file.","I","S");
						}
						else WriteToLog("LoadEnglishFacts-&gt; Factarray was not loaded from file.","E","S");
					}
				}
				else WriteToLog("LoadEnglishFacts-&gt; Factarray was not loaded from file.","E","S");
			}
		}
		if ($return) return $temp;
	}
	
	/**
	 * Load a language into an array
	 *
	 * Load a language into an array. This can be the general text or the help text 
	 * It selects the language from the database and puts it into an array of the
	 * format:
	 * ["string"] = ["translation"]
	 * If the function is called to return the array the array temp is created and
	 * this will be returned to the page calling the function. If the language does
	 * not need to be returned all values will be loaded into the array gm_lang.
	 * This means, that the language is active immediately. The help variables can
	 * be loaded by specifying to load the help language. Here the same rules apply
	 * as does for the regular language variables.        
	 *
	 * @author	Genmod Development Team
	 * @param	     string	     $language		The language to load
	 * @param	     boolean	     $return		Whether or not to return the values
	 * @param	     string	     $help		Whether or not to load the help language 
	 * @return 	array          The array with the loaded language
	 */
	public function LoadLanguage($language, $return=false, $help=false) {
		global $gm_language;
		
		if (isset($gm_language[$language]) && CONFIGURED) {
			$temp = array();
			$sql = "SELECT `lg_string`, `lg_".$language."` FROM `".TBLPREFIX."language";
			if ($help) $sql .= "_help";
			$sql .= "` WHERE `lg_".$language."` != ''";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc($res->result)) {
					if (!$return) define("GM_LANG_".$row["lg_string"], $row["lg_".$language]);
					else $temp[$row["lg_string"]] = $row["lg_".$language];
				}
				if ($return) return $temp;
			}
			else return false;
		}
	}
	
	/**
	 * Get a language string from the database
	 *
	 * The function takes the original string and the language in which the string
	 * should be translated. From the language table the string in the desired
	 * language is retrieved and returned.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$string		The string to retrieve
	 * @param		string	$language2	The language the string should be taken from
	 * @param		boolean	$help		Should the text be retrieved from the help table
	 * @return 	string	The string in the requested language
	 */
	public function GetString($string, $language2, $file_type="") {

		if ($file_type == "facts") {
			$sql = "SELECT lg_".$language2.", lg_english FROM ".TBLPREFIX."facts";
			$sql .= " WHERE lg_string = '".$string."'";
		}
		else {
			$sql = "SELECT lg_".$language2.", lg_english FROM ".TBLPREFIX."language";
			if (substr($string, -5) == "_help") $sql .= "_help";
			$sql .= " WHERE lg_string = '".$string."'";
		}
	
		$res = NewQuery($sql);
		$row = $res->FetchAssoc($res->result);
		if ($res->NumRows($res) == 0) return "";
		else if (empty($row["lg_".$language2])) return $row["lg_english"];
		else return $row["lg_".$language2];
	
	}

	public function LoadLangVars($langname="") {
		
		$langsettings = array();
		$sql = "SELECT * FROM ".TBLPREFIX."lang_settings";
		if (!empty($lang)) $sql .= " WHERE ls_gm_langname='".$langname."'";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$lang = array();
			foreach ($row as $key => $value) {
				$lang[substr($key, 3)] = $value;
			}
			$langsettings[$row["ls_gm_langname"]] = $lang;
		}
		return $langsettings;
	}		
}
?>