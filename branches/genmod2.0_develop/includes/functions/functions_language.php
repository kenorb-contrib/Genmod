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
 * @version $Id$
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (strstr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
/**
 * Stores the languages in the database
 *
 * The function reads the language files, one language at a time and
 * stores the data in the TBLPREFIX_language and TBLPREFIX_help_language
 * tables.
 *
 * @author	Genmod Development Team
 * @param		$setup		boolean	If we are not in setupmode we need to drop and recreate the tables first
 * @param		$only_english	boolean	If the language table is corrupted, only import English otherwise the script takes too long
 * @return	boolean	true or false depending on the outcome
 */
function StoreEnglish($setup=false,$only_english=false) {
	global $TBLPREFIX, $gm_username, $language_settings;
	
	if (!$setup) {
		// Empty the table
		$sql = "TRUNCATE TABLE ".$TBLPREFIX."language";
		if (!$result = NewQuery($sql)) {
			WriteToLog("StoreEnglish-> Language table could not be dropped", "E", "S");
			return false;
		}
		// Empty the table
		$sql = "TRUNCATE TABLE ".$TBLPREFIX."language_help";
		if (!$result = NewQuery($sql)) {
			WriteToLog("StoreEnglish-> Language help table could not be dropped", "E", "S");
			return false;
		}
		// Empty the table
		$sql = "TRUNCATE TABLE ".$TBLPREFIX."facts";
		if (!$result = NewQuery($sql)) {
			WriteToLog("StoreEnglish-> Facts table could not be dropped", "E", "S");
			return false;
		}
	}
	
	if (file_exists("languages/lang.en.txt")) {
		// NOTE: Import the English language into the database
		$lines = file("languages/lang.en.txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			// Cleanup the data
			if (!isset($data[1])) print "Error with language string ".$line;
			$data[0] = substr(trim($data[0]), 1);
			$data[1] = substr(trim($data[1]), 0, -1);
//			print $data[0]." - ".$data[1]."<br />";
			// NOTE: Add the language variable to the language array
			$gm_lang[$data[0]] = $data[1];
			
			// NOTE: Store the language variable in the database
			if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
				$sql = "INSERT INTO ".$TBLPREFIX."language VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_username."')";
				if (!$result = NewQuery($sql)) {
					WriteToLog("StoreEnglish-> Could not add language string ".$line." for language English to table ", "E", "S");
				}
				else $result->FreeResult();
			 }
		}
		
		$sql = "SELECT ls_gm_langname FROM ".$TBLPREFIX."lang_settings WHERE ls_gm_langname='english'";
		$res = NewQuery($sql);
		if ($res->NumRows() > 0 ) {
			$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_translated = '0', ls_md5_lang = '".md5_file("languages/lang.en.txt")."', ls_md5_help = '".md5_file("languages/help_text.en.txt")."', ls_md5_facts = '".md5_file("languages/facts.en.txt")."' WHERE ls_gm_langname='english'";
			$res = NewQuery($sql);
		}
		else {
			$sql = "INSERT INTO ".$TBLPREFIX."lang_settings (ls_gm_langname, ls_translated, ls_md5_lang, ls_md5_help, ls_md5_facts) VALUES ('english', '0','".md5_file("languages/lang.en.txt")."', '".md5_file("languages/help_text.en.txt")."', '".md5_file("languages/facts.en.txt")."')";
			$res = NewQuery($sql);
		}
		WriteToLog("StoreEnglish-> English language added to the database", "I", "S");
		if ($only_english) WriteToLog("StoreEnglish-> Additional languages are not restored", "W", "S");
	}
	
	if (file_exists("languages/facts.en.txt")) {
		// NOTE: Import the English facts into the database
		$lines = file("languages/facts.en.txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			// Cleanup the data
			$data[0] = substr(trim($data[0]), 1);
			$data[1] = substr(trim($data[1]), 0, -1);
//			print $data[0]." - ".$data[1]."<br />";
			// NOTE: Add the facts variable to the facts array
			$factarray[$data[0]] = $data[1];
			
			// NOTE: Store the language variable in the database
			if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
				$sql = "INSERT INTO ".$TBLPREFIX."facts VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_username."')";
				if (!$result = NewQuery($sql)) {
					WriteToLog("Could not add facts string ".$line." for language English to table ", "E", "S");
				}
				else $result->FreeResult();
			 }
		}
		WriteToLog("StoreEnglish-> English facts added to the database", "I", "S");
		if ($only_english) WriteToLog("StoreEnglish-> Additional facts languages are not restored", "W", "S");
	}
	
	if (!$only_english) {
		if (file_exists("languages/help_text.en.txt")) {
			// NOTE: Import the English language help into the database
			$lines = file("languages/help_text.en.txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				// NOTE: Add the help language variable to the language array
				$gm_lang[$data[0]] = $data[1];
				
				if (!isset($data[1])) WriteToLog($line, "E", "S");
				else {
					$data[0] = substr(trim($data[0]), 1);
					$data[1] = substr(trim($data[1]), 0, -1);
					$sql = "INSERT INTO ".$TBLPREFIX."language_help VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_username."')";
					if (!$result = NewQuery($sql)) {
						WriteToLog("Could not add language help string ".$line." for language English to table ", "E", "S");
					}
					else {
//						set_time_limit(10);
						$result->FreeResult();
					}
				}
			}
			WriteToLog("StoreEnglish-> English help added to the database", "I", "S");
		}
		
		// Add all active languages if english is not specified
		
		foreach ($language_settings as $name => $value) {
			if ($value["gm_lang_use"] && $name != "english") {
				StoreLanguage($name);
			}
		}
	}
	return true;
}
/**
 * Store a language into the database
 *
 * The function first reads the regular language file and imports it into the
 * database. After that it reads the help file and imports it into the database.          
 *
 * @author	Genmod Development Team
 * @param	     string	     $storelang	The name of the language to store
 */
function StoreLanguage($storelang) {
	global $TBLPREFIX, $gm_username, $language_settings;

	if (file_exists("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
       		    $data[0] = substr(ltrim($data[0]), 1);
                $data[1] = substr(rtrim($data[1]), 0, -1);
                if ($storelang == "english") {
	                $sql = "SELECT lg_english FROM ".$TBLPREFIX."language WHERE lg_string='".$data[0]."'";
	                $res = NewQuery($sql);
	                if ($res->NumRows() == 0) {
						$sql = "INSERT INTO ".$TBLPREFIX."language VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_username."')";
						if (!$result = NewQuery($sql)) {
							WriteToLog("StoreLanguage-> Could not add language string ".$line." for language English to table ", "E", "S");
						}
						else $result->FreeResult();
					}
					else {
						$res->FreeResult();
		       			$sql = "UPDATE ".$TBLPREFIX."language SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
    		   			if (!$result = NewQuery($sql)) {
            	            WriteToLog("StoreLanguage-> Could not update language string ".$line." for language ".$storelang." to table ", "E", "S");
	                    }
						else $result->FreeResult();
					}
				}
				else {
	       			$sql = "UPDATE ".$TBLPREFIX."language SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
    	   			if (!$result = NewQuery($sql)) {
           	            WriteToLog("StoreLanguage-> Could not update language string ".$line." for language ".$storelang." to table ", "E", "S");
	                }
					else $result->FreeResult();
				}
  		    }
	    }
	}
	if (file_exists("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
                $data[0] = substr(ltrim($data[0]), 1);
                $data[1] = substr(rtrim($data[1]), 0, -1);
                if ($storelang == "english") {
	                $sql = "SELECT lg_english FROM ".$TBLPREFIX."language_help WHERE lg_string='".$data[0]."'";
	                $res = NewQuery($sql);
	                if ($res->NumRows() == 0) {
						$sql = "INSERT INTO ".$TBLPREFIX."language_help VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_username."')";
						if (!$result = NewQuery($sql)) {
							WriteToLog("StoreLanguage-> Could not add language help string ".$line." for language English to table ", "E", "S");
						}
						else $result->FreeResult();
					}
					else {
						$res->FreeResult();
	                	$sql = "UPDATE ".$TBLPREFIX."language_help SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
    	            	if (!$result = NewQuery($sql)) {
        	                  WriteToLog("StoreLanguage-> Could not update language help string ".$line." for language ".$storelang." to table ", "E", "S");
            	        }
						else $result->FreeResult();
					}
               	}
               	else {
	            	$sql = "UPDATE ".$TBLPREFIX."language_help SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
    	            if (!$result = NewQuery($sql)) {
        	            WriteToLog("StoreLanguage-> Could not update language help string ".$line." for language ".$storelang." to table ", "E", "S");
            	    }
					else $result->FreeResult();
               	}
          	}
     	}
 	}
	if (file_exists("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
                $data[0] = substr(ltrim($data[0]), 1);
                $data[1] = substr(rtrim($data[1]), 0, -1);
                if ($storelang == "english") {
	                $sql = "SELECT lg_english FROM ".$TBLPREFIX."facts WHERE lg_string='".$data[0]."'";
	                $res = NewQuery($sql);
	                if ($res->NumRows() == 0) {
						$sql = "INSERT INTO ".$TBLPREFIX."facts VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_username."')";
						if (!$result = NewQuery($sql)) {
							WriteToLog("StoreLanguage-> Could not add facts string ".$line." for language English to table ", "E", "S");
						}
						else $result->FreeResult();
					}
					else {
						$res->FreeResult();
    	            	$sql = "UPDATE ".$TBLPREFIX."facts SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
        	        	if (!$result = NewQuery($sql)) {
            	              WriteToLog("StoreLanguage-> Could not add facts string ".$line." for language ".$storelang." to table ", "E", "S");
                	    }
						else $result->FreeResult();
					}
               	}
               	else {
   	            	$sql = "UPDATE ".$TBLPREFIX."facts SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
       	        	if (!$result = NewQuery($sql)) {
						WriteToLog("StoreLanguage-> Could not add facts string ".$line." for language ".$storelang." to table ", "E", "S");
                	}
					else $result->FreeResult();
               	}
          	}
     	}
 	}
	$sql = "SELECT ls_gm_langname FROM ".$TBLPREFIX."lang_settings WHERE ls_gm_langname='".$storelang."'";
	$res = NewQuery($sql);
	if ($res->NumRows() > 0 ) {
		$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_translated = '0', ls_md5_lang = '".md5_file("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")."', ls_md5_help = '".md5_file("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")."', ls_md5_facts = '".md5_file("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")."' WHERE ls_gm_langname='".$storelang."'";
		$res = NewQuery($sql);
	}
	else {
		$sql = "INSERT INTO ".$TBLPREFIX."lang_settings (ls_gm_langname, ls_translated, ls_md5_lang, ls_md5_help, ls_md5_facts) VALUES ('".$storelang."', '0','".md5_file("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")."', '".md5_file("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")."', '".md5_file("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")."')";
		$res = NewQuery($sql);
	}
     
}
/**
 * Remove a language into the database
 *
 * The function removes a language from the database by making the column empty
 *
 * @author	Genmod Development Team
 * @param	     string	     $storelang	The name of the language to remove
 */
function RemoveLanguage($removelang) {
	global $TBLPREFIX;
	
	if ($removelang != "english") {
		$sql = "UPDATE  ".$TBLPREFIX."language SET lg_".$removelang."=''";
		$result = NewQuery($sql);
		$sql = "UPDATE  ".$TBLPREFIX."language_help SET lg_".$removelang."=''";
		$result = NewQuery($sql);
		$sql = "UPDATE  ".$TBLPREFIX."facts SET lg_".$removelang."=''";
		$result = NewQuery($sql);
		$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_translated='0', ls_md5_lang='', ls_md5_help='', ls_md5_facts='' WHERE ls_gm_langname='".$removelang."'";
		$result = NewQuery($sql);
		
	}
}

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
function LoadEnglish($return=false, $help=false, $altlang = false) {
	global $gm_lang, $TBLPREFIX, $CONFIGURED, $DBCONN, $LANGUAGE, $GM_BASE_DIRECTORY, $language_settings;
//	print $LANGUAGE;
	$temp = array();
	if ($CONFIGURED && $DBCONN->connected) {
		$sql = "SELECT COUNT(*) as total FROM ".$TBLPREFIX."language";
		if ($help) $sql .= "_help";
		$res = NewQuery($sql);
		if ($res) $total_columns = $res->FetchAssoc($res->result);
		else $total_columns["total"] = 0 ;
		if ($total_columns["total"] > 0) {
			if ($altlang) $sql = "SELECT lg_string, lg_english, lg_".$LANGUAGE." FROM ".$TBLPREFIX."language";
			else $sql = "SELECT lg_string, lg_english FROM ".$TBLPREFIX."language";
			if ($help) $sql .= "_help";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc($res->result)) {
				if (!$return) {
					if (!empty($row["lg_".$LANGUAGE])) $gm_lang[$row["lg_string"]] = $row["lg_".$LANGUAGE];
					else $gm_lang[$row["lg_string"]] = $row["lg_english"];
				}
				else {
					if (!empty($row["lg_".$LANGUAGE])) $temp[$row["lg_string"]] = $row["lg_".$LANGUAGE];
					else $temp[$row["lg_string"]] = $row["lg_english"];
				}
			}
			if (!$return) {
			// NOTE: include the extra english file
				if (file_exists($GM_BASE_DIRECTORY . "languages/lang.".$language_settings["english"]["lang_short_cut"].".extra.php")) {
					require_once $GM_BASE_DIRECTORY . "languages/lang.".$language_settings["english"]["lang_short_cut"].".extra.php";
				}
				if ($altlang) {
					// NOTE: include the extra file
					if (file_exists($GM_BASE_DIRECTORY . "languages/lang.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php")) {
						require_once $GM_BASE_DIRECTORY . "languages/lang.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php";
					}
				}
			}
		}
		else {
			$number = 0;
			WriteToLog("LoadEnglish-> Language table not available in database. Trying to load language from file.","E","S");
			// NOTE: If both string arrays are empty, no data came out of the database
			// NOTE: We load the language from the file and write an error to the log
			if (file_exists("languages/lang.en.txt")) {
				// NOTE: Load the English language into memory
				$lines = file("languages/lang.en.txt");
				foreach ($lines as $key => $line) {
					$data = preg_split("/\";\"/", $line, 2);
					// NOTE: Add the language variable to the language array
					if (!$return) $gm_lang[substr($data[0],1)] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
					else $temp[$row["lg_string"]] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
				}
				if (count($gm_lang) > 0) {
					$number = count($gm_lang);
					WriteToLog("LoadEnglish-> Language successfully loaded from file.","I","S");
				}
				else WriteToLog("LoadEnglish-> Language was not loaded from file.","E","S");
			}

			if (file_exists("languages/help_text.en.txt")) {
				// NOTE: Load the English help language into memory since we have no database
				$lines = file("languages/help_text.en.txt");
				foreach ($lines as $key => $line) {
					$data = preg_split("/\";\"/", $line, 2);
					// NOTE: Add the language variable to the language array
					if (!$return) $gm_lang[substr($data[0],1)] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
					else $temp[$row["lg_string"]] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
				}
				if (count($gm_lang) > $number) WriteToLog("Language help successfully loaded from file.","I","S");
				else WriteToLog("LoadEnglish-> Language help was not loaded from file.","E","S");
			}
		}
	}
	if ($return) return $temp;
}

function LoadEnglishFacts($return=false, $help=false, $altlang = false) {
	global $gm_lang, $factarray, $TBLPREFIX, $CONFIGURED, $DBCONN, $LANGUAGE, $GM_BASE_DIRECTORY, $language_settings;
	
	$temp = array();
	$factarray = array();
	if ($CONFIGURED && $DBCONN->connected) {
		$sql = "SELECT COUNT(*) as total FROM ".$TBLPREFIX."facts";
		$res = NewQuery($sql);
		if ($res) $total_columns = $res->FetchAssoc($res->result);
		else $total_columns["total"] = 0 ;
		if ($total_columns["total"] > 0) {
			if ($altlang) $sql = "SELECT lg_string, lg_english, lg_".$LANGUAGE." FROM ".$TBLPREFIX."facts";
			else $sql = "SELECT lg_string, lg_english FROM ".$TBLPREFIX."facts";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc($res->result)) {
				if (!$return) {
					if (!empty($row["lg_".$LANGUAGE]) && $altlang) $factarray[$row["lg_string"]] = $row["lg_".$LANGUAGE];
					else $factarray[$row["lg_string"]] = $row["lg_english"];
				}
				else {
					if (!empty($row["lg_".$LANGUAGE])&& $altlang) $temp[$row["lg_string"]] = $row["lg_".$LANGUAGE];
					else $temp[$row["lg_string"]] = $row["lg_english"];
				}
			}
			if (!$return) {
			// NOTE: include the extra english file
				if (file_exists($GM_BASE_DIRECTORY . "languages/facts.".$language_settings["english"]["lang_short_cut"].".extra.php")) {
					require_once $GM_BASE_DIRECTORY . "languages/facts.".$language_settings["english"]["lang_short_cut"].".extra.php";
				}
				if ($altlang) {
					// NOTE: include the extra file
					if (file_exists($GM_BASE_DIRECTORY . "languages/facts.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php")) {
						require_once $GM_BASE_DIRECTORY . "languages/facts.".$language_settings[$LANGUAGE]["lang_short_cut"].".extra.php";
					}
				}
			}
		}
		else {
			$number = 0;
			WriteToLog("Facts table not available in database. Trying to load facts from file.","E","S");
			// NOTE: If both string arrays are empty, no data came out of the database
			// NOTE: We load the language from the file and write an error to the log
			if (file_exists("languages/facts.en.txt")) {
				// NOTE: Load the English language into memory
				$lines = file("languages/facts.en.txt");
				foreach ($lines as $key => $line) {
					$data = preg_split("/\";\"/", $line, 2);
					// NOTE: Add the facts variable to the facts array
					if (!$return) $factarray[substr($data[0],1)] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
					else $temp[$row["lg_string"]] = substr(trim($data[1]),0,(strlen(trim($data[1]))-1));
				}
				if (count($factarray) > 0) {
					$number = count($factarray);
					WriteToLog("LoadEnglishFacts-> Factarray successfully loaded from file.","I","S");
				}
				else WriteToLog("LoadEnglishFacts-> Factarray was not loaded from file.","E","S");
			}
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
function LoadLanguage($language, $return=false, $help=false) {
	global $gm_language, $gm_lang, $TBLPREFIX, $CONFIGURED;
	
	if (isset($gm_language[$language]) && $CONFIGURED) {
		$temp = array();
		$sql = "SELECT `lg_string`, `lg_".$language."` FROM `".$TBLPREFIX."language";
		if ($help) $sql .= "_help";
		$sql .= "` WHERE `lg_".$language."` != ''";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc($res->result)) {
				if (!$return) $gm_lang[$row["lg_string"]] = $row["lg_".$language];
				else $temp[$row["lg_string"]] = $row["lg_".$language];
			}
			if ($return) return $temp;
		}
		else return false;
	}
}

function LoadFacts($language, $return=false) {
	global $gm_language, $gm_lang, $factarray, $TBLPREFIX, $CONFIGURED;
	
	if (isset($gm_language[$language]) && $CONFIGURED) {
		$temp = array();
		$sql = "SELECT `lg_string`, `lg_".$language."` FROM `".$TBLPREFIX."facts";
		$sql .= "` WHERE `lg_".$language."` != ''";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc($res->result)) {
				if (!$return) $factarray[$row["lg_string"]] = $row["lg_".$language];
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
function GetString($string, $language2, $file_type="") {
	global $TBLPREFIX;

	if ($file_type == "facts") {
		$sql = "SELECT lg_".$language2.", lg_english FROM ".$TBLPREFIX."facts";
		$sql .= " WHERE lg_string = '".$string."'";
	}
	else {
		$sql = "SELECT lg_".$language2.", lg_english FROM ".$TBLPREFIX."language";
		if (substr($string, -5) == "_help") $sql .= "_help";
		$sql .= " WHERE lg_string = '".$string."'";
	}

	$res = NewQuery($sql);
	$row = $res->FetchAssoc($res->result);
	if (empty($row["lg_".$language2])) return $row["lg_english"];
	else return $row["lg_".$language2];

}
/**
 * Write a translated string
 *
 * <Long description of your function. 
 * What does it do?
 * How does it work?
 * All that goes into the long description>
 *
 * @author	Genmod Development Team
 * @param		string	$string	The translated string
 * @param		string	$value	The string to update
 * @param		string	$language2	The language in which the string is translated
 * @return 	boolean	true if the update succeeded|false id the update failed
 */

function WriteString($string, $value, $language2, $file_type="") {
	global $TBLPREFIX;
	
	if ($file_type == "facts") {
		$sql = "UPDATE ".$TBLPREFIX."facts";
		$sql .= " SET lg_".$language2."= '".$string."' WHERE lg_string = '".$value."'";
	}
	else {
		$sql = "UPDATE ".$TBLPREFIX."language";
		if (substr($value, -5) == "_help") $sql .= "_help";
		$sql .= " SET lg_".$language2."= '".$string."' WHERE lg_string = '".$value."'";
	}
	if ($res = NewQuery($sql)) {
		$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_translated='1' WHERE ls_gm_langname='".$language2."'";
		$res2 = NewQuery($sql);
		return true;
	}
	else return false;
}

function LoadLangVars($langname="") {
	global $TBLPREFIX;
	
	$langsettings = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."lang_settings";
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

function StoreLangVars($vars) {
	global $TBLPREFIX, $DBCONN;
	
	$string = "";
	$first = true;
	$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ";
	foreach ($vars as $fname => $fvalue) {
		if (!$first) $string .= ", ";
		$first = false;
//		if ($fvalue == false) $fvalue = "0";
//		else $fvalue = "1";
		$string .= "ls_".$fname."='".$DBCONN->EscapeQuery($fvalue)."'";
	}
	$sql .= $string;
	$sql .= " WHERE ls_gm_langname='".$vars["gm_langname"]."'";
	$res = NewQuery($sql);
	return $res;
}	

// Activate the given language
function ActivateLanguage($lang) {
	global $TBLPREFIX;
	
	$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_gm_lang_use='1' WHERE ls_gm_langname='".$lang."'";
	$res = NewQuery($sql);
	if ($res) return true;
	else return false;
}

// Activate the given language
function DeactivateLanguage($lang) {
	global $TBLPREFIX;
	
	$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_gm_lang_use='0' WHERE ls_gm_langname='".$lang."'";
	$res = NewQuery($sql);
	if ($res) return true;
	else return false;
}

/** Determines whether a language is in use or not
 ** if $lang is empty, return the full array
 ** else return true or false
 */
function LanguageInUse($lang="") {
	global $GEDCOMS, $GEDCOMLANG, $Users;
	static $configuredlanguages, $inuse;
	
	if (!isset($configuredlanguages)) {
		$configuredlanguages = array();
		$inuse = array();

		// Read GEDCOMS configuration and collect language data
		foreach ($GEDCOMS as $key => $value) {
			SwitchGedcom($value["gedcom"]);
			if (!isset($configuredlanguages["gedcom"][$GEDCOMLANG][$key])) $configuredlanguages["gedcom"][$GEDCOMLANG][$key] = TRUE;
			$inuse[$GEDCOMLANG] = true;
		}
		// Restore the current settings
		SwitchGedcom();
		
		// Read user configuration and collect language data
		$users = $Users->GetUsers("username","asc");
		foreach($users as $username=>$user) {
			if (!isset($configuredlanguages["users"][$user->language][$username])) $configuredlanguages["users"][$user->language][$username] = TRUE;
			$inuse[$user->language] = true;
		}
		$inuse["english"] = true;
	}
	if (empty($lang)) return $configuredlanguages;
	if (array_key_exists($lang, $inuse)) return $inuse[$lang];
	else return false;
}

/* Get the language file and translation info for the admin messages
 */
function GetLangfileInfo($impexp) {
	global $TBLPREFIX, $language_settings, $gm_lang;
	static $implangs, $explangs;
	
	if (!isset($implangs)) {
		$sql = "SELECT ls_gm_langname, ls_md5_lang, ls_md5_help, ls_md5_facts, ls_translated FROM ".$TBLPREFIX."lang_settings";
		$res = NewQuery($sql);
		if ($res->NumRows() > 0) {
			$implangs = array();
			$explangs = array();
			while($lang = $res->FetchRow()) {
				if ($lang[4] == 1) $explangs[] = array("name" => $gm_lang["lang_name_".$lang[0]], "lang" => $lang[0]);
				if (file_exists($language_settings[$lang[0]]["gm_language"]) 
				&& file_exists($language_settings[$lang[0]]["helptextfile"]) 
				&& file_exists($language_settings[$lang[0]]["factsfile"])) {
					if ($language_settings[$lang[0]]["gm_lang_use"] == true && (md5_file($language_settings[$lang[0]]["gm_language"]) != $lang[1] ||
						md5_file($language_settings[$lang[0]]["helptextfile"]) != $lang[2] ||
						md5_file($language_settings[$lang[0]]["factsfile"]) != $lang[3])) {
						$implangs[] = array("name" => $gm_lang["lang_name_".$lang[0]], "lang" => $lang[0]);
					}
				}
			}
		}
	}
	
	if ($impexp == "import") return $implangs;
	else return $explangs;
}
?>