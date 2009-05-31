<?php
/**
 * Core Functions that can be used by any page in GM
 *
 * The functions in this file are common to all GM pages and include date conversion
 * routines and sorting functions.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @version $Id: functions.php,v 1.62 2006/04/17 20:01:52 roland-d Exp $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (strstr($_SERVER["PHP_SELF"],"functions.php")) {
	print "Why do you want to do that?";
	exit;
}

/**
 * The level of error reporting
 * $ERROR_LEVEL = 0 will not print any errors
 * $ERROR_LEVEL = 1 will only print the last function that was called
 * $ERROR_LEVEL = 2 will print a full stack trace with function arguments.
 */
$ERROR_LEVEL = 2;
if (isset($DEBUG)) $ERROR_LEVEL = 2;

// ************************************************* START OF INITIALIZATION FUNCTIONS ********************************* //
/**
 * initialize and check the database
 *
 * this function will create a database connection and return false if any errors occurred
 * @return boolean true if database successully connected, false if there was an error
 */
function check_db() {
	global $DBHOST, $DBUSER, $DBPASS, $DBNAME, $DBCONN, $TOTAL_QUERIES, $PHP_SELF, $DBPERSIST;
	global $GEDCOM, $GEDCOMS, $INDEX_DIRECTORY, $BUILDING_INDEX, $indilist, $famlist, $sourcelist, $otherlist;

	if ((is_object($DBCONN)) && (!DB::isError($DBCONN))) return true;
	//-- initialize query counter
	$TOTAL_QUERIES = 0;

	$dsn = array(
		'phptype'  => 'mysql',
		'username' => $DBUSER,
		'password' => $DBPASS,
		'hostspec' => $DBHOST,
		'database' => $DBNAME
	);

	$options = array(
		'debug' 	  => 3,
		'portability' => DB_PORTABILITY_ALL,
		'persistent'  => $DBPERSIST
	);

	$DBCONN = DB::connect($dsn, $options);
	if (DB::isError($DBCONN)) {
		//die($DBCONN->getMessage());
		return false;
	}

	//-- protect the username and password on pages other than the Configuration page
	if (strpos($_SERVER["SCRIPT_NAME"], "editconfig.php") === false) {
		unset($CONFIG_PARMS);
		$DBUSER = "";
		$DBPASS = "";
	}
	return true;
}

/**
 * get gedcom configuration file
 *
 * this function returns the path to the currently active GEDCOM configuration file
 * @return string path to gedcom.ged_conf.php configuration file
 */
function get_config_file() {
	global $GEDCOMS, $GEDCOM, $GM_BASE_DIRECTORY;
	if (count($GEDCOMS)==0) {
		return $GM_BASE_DIRECTORY."config_gedcom.php";
	}
	if ((!empty($GEDCOM))&&(isset($GEDCOMS[$GEDCOM]))) return $GEDCOMS[$GEDCOM]["config"];
	foreach($GEDCOMS as $GEDCOM=>$gedarray) {
		$_SESSION["GEDCOM"] = $GEDCOM;
		return $GM_BASE_DIRECTORY.$gedarray["config"];
	}
}

/**
 * Get the version of the privacy file
 *
 * This function opens the given privacy file and returns the privacy version from the file
 * @param string $privfile the path to the privacy file
 * @return string the privacy file version number
 */
function get_privacy_file_version($privfile) {
	$privversion = "0";

	//-- check to make sure that the privacy file is the current version
	if (file_exists($privfile)) {
		$privcontents = implode("", file($privfile));
		$ct = preg_match("/PRIVACY_VERSION.*=.*\"(.+)\"/", $privcontents, $match);
		if ($ct>0) {
			$privversion = trim($match[1]);
		}
	}

	return $privversion;
}

/**
 * Get the path to the privacy file
 *
 * Get the path to the privacy file for the currently active GEDCOM
 * @return string path to the privacy file
 */
function get_privacy_file() {
	global $GEDCOMS, $GEDCOM, $GM_BASE_DIRECTORY, $REQUIRED_PRIVACY_VERSION;

	$privfile = "privacy.php";
	if (count($GEDCOMS)==0) {
		$privfile = $GM_BASE_DIRECTORY."privacy.php";
		return $privfile;
	}
	if ((!empty($GEDCOM))&&(isset($GEDCOMS[$GEDCOM]))) {
		if ((isset($GEDCOMS[$GEDCOM]["privacy"]))&&(file_exists($GEDCOMS[$GEDCOM]["privacy"]))) $privfile = $GEDCOMS[$GEDCOM]["privacy"];
		else $privfile = $GM_BASE_DIRECTORY."privacy.php";
	}
	else {
		foreach($GEDCOMS as $GEDCOM=>$gedarray) {
			$_SESSION["GEDCOM"] = $GEDCOM;
			if ((isset($gedarray["privacy"]))&&(file_exists($gedarray["privacy"]))) $privfile = $GM_BASE_DIRECTORY.$gedarray["privacy"];
			else $privfile = $GM_BASE_DIRECTORY."privacy.php";
		}
	}
	$privversion = get_privacy_file_version($privfile);
	if ($privversion<$REQUIRED_PRIVACY_VERSION) $privfile = $GM_BASE_DIRECTORY."privacy.php";

	return $privfile;
}

/**
 * Get the current time in micro seconds
 *
 * returns a timestamp for the current time in micro seconds
 * obtained from online documentation for the microtime() function
 * on php.net
 * @return float time in micro seconds
 */
function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}


/**
 * get a gedcom filename from its database id
 * @param int $ged_id	The gedcom database id to get the filename for
 * @return string
 */
function get_gedcom_from_id($ged_id) {
	global $GEDCOMS;

	if (isset($GEDCOMS[$ged_id])) return $ged_id;
	foreach($GEDCOMS as $ged=>$gedarray) {
		if ($gedarray["id"]==$ged_id) return $ged;
	}

	return $ged;
}

/**
 * Check if a gedcom file is downloadable over the internet
 *
 * @author opus27
 * @param string $gedfile gedcom file
 * @return mixed 	$url if file is downloadable, false if not
 */
function check_gedcom_downloadable($gedfile) {
	global $SERVER_URL, $gm_lang;

	//$url = $SERVER_URL;
	$url = "http://localhost/";
	if (substr($url,-1,1)!="/") $url .= "/";
	$url .= preg_replace("/ /", "%20", $gedfile);
	@ini_set('user_agent','MSIE 4\.0b2;'); // force a HTTP/1.0 request
	@ini_set('default_socket_timeout', '10'); // timeout
	$handle = @fopen ($url, "r");
	if ($handle==false) return false;
	// open successfull : now make sure this is a GEDCOM file
	$txt = fread ($handle, 80);
	fclose($handle);
	if (strpos($txt, " HEAD")==false) return false;
	return $url;
}

/**
 * Check if a person is dead
 *
 * For the given XREF id, this function will return true if the person is dead
 * and false if the person is alive.
 * @param string $pid		The Gedcom XREF ID of the person to check
 * @return boolean			True if dead, false if alive
 */
function is_dead_id($pid) {
	global $indilist, $BUILDING_INDEX, $GEDCOM, $GEDCOMS;

	if (empty($pid)) return true;

	//-- if using indexes then first check the indi_isdead array
	if ((!$BUILDING_INDEX)&&(isset($indilist))) {
		//-- check if the person is already in the $indilist cache
		if ((!isset($indilist[$pid]["isdead"]))||($indilist[$pid]["gedfile"]!=$GEDCOMS[$GEDCOM]['id'])) {
			//-- load the individual into the cache by calling the find_person_record function
			$gedrec = find_person_record($pid);
			if (empty($gedrec)) return true;
		}
		if ($indilist[$pid]["gedfile"]==$GEDCOMS[$GEDCOM]['id']) {
			if (!isset($indilist[$pid]["isdead"])) $indilist[$pid]["isdead"] = -1;
			if ($indilist[$pid]["isdead"]==-1) $indilist[$pid]["isdead"] = update_isdead($pid, $indilist[$pid]);
			return $indilist[$pid]["isdead"];
		}
	}
	return is_dead(find_person_record($pid));
}

// This functions checks if an existing file is physically writeable
// The standard PHP function only checks for the R/O attribute and doesn't
// detect authorisation by ACL.
function file_is_writeable($file) {
	$err_write = false;
	$handle = @fopen($file,"r+");
	if	($handle)	{
		$i = fclose($handle);
		$err_write = true;
	}
	return($err_write);
}

// This functions checks if an existing directory is physically writeable
// The standard PHP function only checks for the R/O attribute and doesn't
// detect authorisation by ACL.
function dir_is_writable($dir) {
	$err_write = false;
	$handle = @fopen($dir."foo.txt","w+");
	if	($handle) {
		$i = fclose($handle);
		$err_write = true;
		@unlink($dir."foo.txt");
	}
	return($err_write);
}

/**
 * GM Error Handling function
 *
 * This function will be called by PHP whenever an error occurs.  The error handling
 * is set in the session.php
 * @see http://us2.php.net/manual/en/function.set-error-handler.php
 */
function gm_error_handler($errno, $errstr, $errfile, $errline) {
	global $LAST_ERROR, $ERROR_LEVEL;

	if ((error_reporting() > 0)&&($errno<2048)) {
		$LAST_ERROR = $errstr." in ".$errfile." on line ".$errline;
		if ($ERROR_LEVEL==0) return;
		if(check_db()) {
			$msg = "\n<br />ERROR ".$errno.": ".$errstr."<br />\n";
			$msg .= "Error occurred on line ".$errline." of file ".basename($errfile)."<br />\n";
			WriteToLog($msg, "E", "S");
		}
		if (($errno<16)&&(function_exists("debug_backtrace"))&&(strstr($errstr, "headers already sent by")===false)) {
			$backtrace = debug_backtrace();
			$num = count($backtrace);
			if ($ERROR_LEVEL==1) $num = 1;
			for($i=0; $i<$num; $i++) {
				print "Error occurred on line <b>".$backtrace[$i]["line"]."</b> of file <b>".basename($backtrace[$i]["file"])."</b> in function <b>".$backtrace[$i]["function"]."</b>";
				if ($i<$num-1) print " args(";
				if (isset($backtrace[$i]['args'])) {
					if (is_array($backtrace[$i]['args']))
						foreach($backtrace[$i]['args'] as $name=>$value) print $value.",";
					else print $backtrace[$i]['args'];
				}
				print ")<br />";
			}
		}
		if ($errno==1) die();
	}
	return false;
}

// ************************************************* START OF GEDCOM FUNCTIONS ********************************* //

/**
 * Replacement function for strrpos()
 * Returns the numeric position of the last occurrence of needle in the haystack string.
 * Note that the needle in this case can only be a single character in PHP 4. If a string
 * is passed as the needle, then only the first character of that string will be used.
 * @author escii at hotmail dot com ( Brendan )
 * @param string $haystack The text to be searched through
 * @param string $needle The text to be found
 * @param int $ret The position at which the needle is found
 */
function strrpos4($haystack, $needle) {
       while($ret = strrpos($haystack,$needle)) {
		  if(strncmp(substr($haystack,$ret,strlen($needle)), $needle,strlen($needle)) == 0 ) return $ret;
            $haystack = substr($haystack,0,$ret -1 );
       }
       return $ret;
}

/**
 * get a gedcom subrecord
 *
 * searches a gedcom record and returns a subrecord of it.  A subrecord is defined starting at a
 * line with level N and all subsequent lines greater than N until the next N level is reached.
 * For example, the following is a BIRT subrecord:
 * <code>1 BIRT
 * 2 DATE 1 JAN 1900
 * 2 PLAC Phoenix, Maricopa, Arizona</code>
 * The following example is the DATE subrecord of the above BIRT subrecord:
 * <code>2 DATE 1 JAN 1900</code>
 * @author Genmod Development Team
 * @param int $level the N level of the subrecord to get
 * @param string $tag a gedcom tag or string to search for in the record (ie 1 BIRT or 2 DATE)
 * @param string $gedrec the parent gedcom record to search in
 * @param int $num this allows you to specify which matching <var>$tag</var> to get.  Oftentimes a
 * gedcom record will have more that 1 of the same type of subrecord.  An individual may have
 * multiple events for example.  Passing $num=1 would get the first 1.  Passing $num=2 would get the
 * second one, etc.
 * @return string the subrecord that was found or an empty string "" if not found.
 */
function get_sub_record($level, $tag, $gedrec, $num=1) {
	$pos1=0;
	$subrec = "";
	if (empty($gedrec)) return "";
	while(($num>0)&&($pos1<strlen($gedrec))) {
		$pos1 = strpos($gedrec, $tag, $pos1);
		if ($pos1===false) {
			$tag = preg_replace("/(\w+)/", "_$1", $tag);
			$pos1 = strpos($gedrec, $tag, $pos1);
			if ($pos1===false) return "";
		}
		$pos2 = strpos($gedrec, "\n$level", $pos1+1);
		if (!$pos2) $pos2 = strpos($gedrec, "\n1", $pos1+1);
		if (!$pos2) $pos2 = strpos($gedrec, "\nGM_", $pos1+1); // GM_SPOUSE, GM_FAMILY_ID ...
		if (!$pos2) {
			if ($num==1) return substr($gedrec, $pos1);
			else return "";
		}
		if ($num==1) {
			$subrec = substr($gedrec, $pos1, $pos2-$pos1);
			$lowtag = "\n".($level-1).(substr($tag, 1));
			if (phpversion() < 5) {
				if ($newpos = strrpos4($subrec, $lowtag)) {
				$pos2 = $pos2 - (strlen($subrec) - $newpos);
				$subrec = substr($gedrec, $pos1, $pos2-$pos1);
				}
			}
			else if ($newpos = strripos($subrec, $lowtag)) {
				$pos2 = $pos2 - (strlen($subrec) - $newpos);
				$subrec = substr($gedrec, $pos1, $pos2-$pos1);
			}
		}
		$num--;
		$pos1 = $pos2;
	}
	return $subrec;
}

/**
 * find all of the level 1 subrecords of the given record
 * @param string $gedrec the gedcom record to get the subrecords from
 * @param string $ignore a list of tags to ignore
 * @param boolean $families whether to include any records from the family
 * @param boolean $sort whether or not to sort the record by date
 * @param boolean $ApplyPriv whether to apply privacy right now or later
 * @return array an array of the raw subrecords to return
 */
function get_all_subrecords($gedrec, $ignore="", $families=true, $sort=true, $ApplyPriv=true) {
	global $ASC, $IGNORE_FACTS, $IGNORE_YEAR;
	$repeats = array();

	$id = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}
	$prev_tags = array();
	$ct = preg_match_all("/\n1 (\w+)(.*)/", $gedrec, $match, PREG_SET_ORDER);
	for($i=0; $i<$ct; $i++) {
		$fact = trim($match[$i][1]);
		if (strpos($ignore, $fact)===false) {
			if (!$ApplyPriv || (showFact($fact, $id) && showFactDetails($fact,$id))) {
				if (isset($prev_tags[$fact])) $prev_tags[$fact]++;
				else $prev_tags[$fact] = 1;
				$subrec = get_sub_record(1, "1 $fact", $gedrec, $prev_tags[$fact]);
				if (!$ApplyPriv || !FactViewRestricted($id, $subrec)) {
					if ($fact=="EVEN") {
						$tt = preg_match("/2 TYPE (.*)/", $subrec, $tmatch);
						if ($tt>0) {
							$type = trim($tmatch[1]);
							if (!$ApplyPriv || (showFact($type, $id)&&showFactDetails($type,$id))) $repeats[] = trim($subrec)."\r\n";
						}
						else $repeats[] = trim($subrec)."\r\n";
					}
					else $repeats[] = trim($subrec)."\r\n";
				}
			}
		}
	}

	//-- look for any records in FAMS records
	if ($families) {
		$ft = preg_match_all("/1 FAMS @(.+)@/", $gedrec, $fmatch, PREG_SET_ORDER);
		for($f=0; $f<$ft; $f++) {
			$famid = $fmatch[$f][1];
			$famrec = find_gedcom_record($fmatch[$f][1]);
			$parents = find_parents_in_record($famrec);
			if ($id==$parents["HUSB"]) $spid = $parents["WIFE"];
			else $spid = $parents["HUSB"];
			$prev_tags = array();
			$ct = preg_match_all("/\n1 (\w+)(.*)/", $famrec, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				$fact = trim($match[$i][1]);
				if (strpos($ignore, $fact)===false) {
					if (!$ApplyPriv || (showFact($fact, $id)&&showFactDetails($fact,$id))) {
						if (isset($prev_tags[$fact])) $prev_tags[$fact]++;
						else $prev_tags[$fact] = 1;
						$subrec = get_sub_record(1, "1 $fact", $famrec, $prev_tags[$fact]);
						// NOTE: Record needs to be trimmed to make sure no extra linebreaks are left
						$subrec = trim($subrec)."\r\n";
						$subrec .= "1 _GMS @$spid@\r\n";
						$subrec .= "1 _GMFS @$famid@\r\n";
						if ($fact=="EVEN") {
							$ct2 = preg_match("/2 TYPE (.*)/", $subrec, $tmatch);
							if ($ct2>0) {
								$type = trim($tmatch[1]);
								if (!$ApplyPriv or (showFact($type, $id)&&showFactDetails($type,$id))) $repeats[] = trim($subrec)."\r\n";
							}
							else $repeats[] = trim($subrec)."\r\n";
						}
						else $repeats[] = trim($subrec)."\r\n";
					}
				}
			}
		}
	}

	if ($sort) {
		$ASC = 0;
  		$IGNORE_FACTS = 0;
  		$IGNORE_YEAR = 0;
		usort($repeats, "compare_facts");
	}
	return $repeats;
}

/**
 * get gedcom tag value
 *
 * returns the value of a gedcom tag from the given gedcom record
 * @param string $tag	The tag to find, use : to delineate subtags
 * @param int $level	The gedcom line level of the first tag to find
 * @param string $gedrec	The gedcom record to get the value from
 * @param int $truncate	Should the value be truncated to a certain number of characters
 * @param boolean $convert	Should data like dates be converted using the configuration settings
 * @return string
 */
function get_gedcom_value($tag, $level, $gedrec, $truncate='', $convert=true) {
	global $SHOW_PEDIGREE_PLACES, $gm_lang;

	$tags = preg_split("/:/", $tag);

	$subrec = $gedrec;
	//print $level;
	foreach($tags as $indexval => $t) {
		$lastsubrec = $subrec;
		$subrec = get_sub_record($level, "$level $t", $subrec);
		if (empty($subrec)) {
			if ($t=="TITL") {
				$subrec = get_sub_record($level, "$level ABBR", $lastsubrec);
				if (!empty($subrec)) $t = "ABBR";
			}
			if (empty($subrec)) {
				if ($level>0) $level--;
				$subrec = get_sub_record($level, "@ $t", $gedrec);
				if (empty($subrec)) {
					return;
				}
			}
		}
		//print "[$level $t-:$subrec:]";
		$level++;
	}
	$level--;
	//print "[".$tag.":".$subrec."]";
	$ct = preg_match("/$level $t(.*)/", $subrec, $match);
	if ($ct==0) $ct = preg_match("/$level @.+@ (.+)/", $subrec, $match);
	if ($ct==0) $ct = preg_match("/@ $t (.+)/", $subrec, $match);
	//print $ct;
	if ($ct > 0) {
		$value = trim($match[1]);
		$ct = preg_match("/@(.*)@/", $value, $match);
		if (($ct > 0 ) && ($t!="DATE")){
			$oldsub = $subrec;
			$subrec = find_gedcom_record($match[1]);
			if ($subrec) {
				$value=$match[1];
				$ct = preg_match("/0 @$match[1]@ $t (.+)/", $subrec, $match);
				if ($ct>0) {
					$value = $match[1];
					$level = 0;
				}
				else $subrec = $oldsub;
			}
			//-- set the value to the id without the @
			else $value = $match[1];
		}
		if ($level!=0 || $t!="NOTE") $value .= get_cont($level+1, $subrec);
		$value = preg_replace("'\n'", "", $value);
		$value = preg_replace("'<br />'", "\n", $value);
		$value = trim($value);
		//-- if it is a date value then convert the date
		if ($convert && $t=="DATE") {
			$value = get_changed_date($value);
			if (!empty($truncate)) {
				if (strlen($value)>$truncate) {
					$value = preg_replace("/\(.+\)/", "", $value);
					if (strlen($value)>$truncate) {
						$value = preg_replace_callback("/([^0-9\W]+)/", create_function('$matches', 'return substr($matches[1], 0, 3);'), $value);
					}
				}
			}
		}
		//-- if it is a place value then apply the pedigree place limit
		else if ($convert && $t=="PLAC") {
			if ($SHOW_PEDIGREE_PLACES>0) {
				$plevels = preg_split("/,/", $value);
				$value = "";
				for($plevel=0; $plevel<$SHOW_PEDIGREE_PLACES; $plevel++) {
					if (!empty($plevels[$plevel])) {
						if ($plevel>0) $value .= ", ";
						$value .= trim($plevels[$plevel]);
					}
				}
			}
			if (!empty($truncate)) {
				if (strlen($value)>$truncate) {
					$plevels = preg_split("/,/", $value);
					$value = "";
					for($plevel=0; $plevel<count($plevels); $plevel++) {
						if (!empty($plevels[$plevel])) {
							if (strlen($plevels[$plevel])+strlen($value)+3 < $truncate) {
								if ($plevel>0) $value .= ", ";
								$value .= trim($plevels[$plevel]);
							}
							else break;
						}
					}
				}
			}
		}
		else if ($convert && $t=="SEX") {
			if ($value=="M") $value = get_first_letter($gm_lang["male"]);
			else if ($value=="F") $value = get_first_letter($gm_lang["female"]);
			else $value = get_first_letter($gm_lang["unknown"]);
		}
		else {
			if (!empty($truncate)) {
				if (strlen($value)>$truncate) {
					$plevels = preg_split("/ /", $value);
					$value = "";
					for($plevel=0; $plevel<count($plevels); $plevel++) {
						if (!empty($plevels[$plevel])) {
							if (strlen($plevels[$plevel])+strlen($value)+3 < $truncate) {
								if ($plevel>0) $value .= " ";
								$value .= trim($plevels[$plevel]);
							}
							else break;
						}
					}
				}
			}
		}
		return $value;
	}
	return "";
}

/**
 * get CONT lines
 *
 * get the N+1 CONT or CONC lines of a gedcom subrecord
 * @param int $nlevel the level of the CONT lines to get
 * @param string $nrec the gedcom subrecord to search in
 * @return string a string with all CONT or CONC lines merged
 */
function get_cont($nlevel, $nrec) {
	global $WORD_WRAPPED_NOTES;
	$text = "";
	$tt = preg_match_all("/$nlevel CON[CT](.*)/", $nrec, $cmatch, PREG_SET_ORDER);
	for($i=0; $i<$tt; $i++) {
		if (strstr($cmatch[$i][0], "CONT")) $text.="<br />\n";
		else if ($WORD_WRAPPED_NOTES) $text.=" ";
		$conctxt = $cmatch[$i][1];
		if (!empty($conctxt)) {
			if ($conctxt{0}==" ") $conctxt = substr($conctxt, 1);
			$conctxt = preg_replace("/[\r\n]/","",$conctxt);
			$text.=$conctxt;
		}
	}
	$text = preg_replace("/~~/", "<br />", $text);
	return $text;
}

/**
 * find the parents in a family
 *
 * find and return a two element array containing the parents of the given family record
 * @author John Finlay (yalnifj)
 * @param string $famid the gedcom xref id for the family
 * @return array returns a two element array with indexes HUSB and WIFE for the parent ids
 */
function find_parents($famid) {
	global $gm_lang, $gm_username;

	$famrec = find_family_record($famid);
	if (empty($famrec)) {
		if (userCanEdit($gm_username)) {
			$famrec = find_gedcom_record($famid);
			if (empty($famrec)) return false;
		}
		else return false;
	}
	return find_parents_in_record($famrec);
}

/**
 * find the parents in a family record
 *
 * find and return a two element array containing the parents of the given family record
 * @author John Finlay (yalnifj)
 * @param string $famrec the gedcom record of the family to search in
 * @return array returns a two element array with indexes HUSB and WIFE for the parent ids
 */
function find_parents_in_record($famrec) {
	global $gm_lang;

	if (empty($famrec)) return false;
	$parents = array();
	$ct = preg_match("/1 HUSB @(.*)@/", $famrec, $match);
	if ($ct>0) $parents["HUSB"]=$match[1];
	else $parents["HUSB"]="";
	$ct = preg_match("/1 WIFE @(.*)@/", $famrec, $match);
	if ($ct>0) $parents["WIFE"]=$match[1];
	else $parents["WIFE"]="";
	return $parents;
}

/**
 * find the children in a family
 *
 * find and return an array containing the children of the given family record
 * @author John Finlay (yalnifj)
 * @param string $famid the gedcom xref id for the family
 * @param string $me	an xref id of a child to ignore, useful when you want to get a person's
 * siblings but do want to include them as well
 * @return array
 */
function find_children($famid, $me='') {
	global $gm_lang, $gm_username;

	$famrec = find_family_record($famid);
	if (empty($famrec)) {
		if (userCanEdit($gm_username)) {
			$famrec = find_gedcom_record($famid);
			if (empty($famrec)) return false;
		}
		else return false;
	}
	return find_children_in_record($famrec);
}

/**
 * find the children in a family record
 *
 * find and return an array containing the children of the given family record
 * @author John Finlay (yalnifj)
 * @param string $famrec the gedcom record of the family to search in
 * @param string $me	an xref id of a child to ignore, useful when you want to get a person's
 * siblings but do want to include them as well
 * @return array
 */
function find_children_in_record($famrec, $me='') {
	global $gm_lang;

	$children = array();
	if (empty($famrec)) return $children;

	$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $match,PREG_SET_ORDER);
	for($i=0; $i<$num; $i++) {
		$child = trim($match[$i][1]);
		if ($child!=$me) $children[] = $child;
	}
	return $children;
}

/**
 * find all child family ids
 *
 * searches an individual gedcom record and returns an array of the FAMC ids where this person is a
 * child in the family
 * @param string $pid the gedcom xref id for the person to look in
 * @return array array of family ids
 */
function find_family_ids($pid) {
	$families = array();
	if (!$pid) return $families;

	$indirec = find_person_record($pid);
	return find_families_in_record($indirec, "FAMC");
}

/**
 * find all spouse family ids
 *
 * searches an individual gedcom record and returns an array of the FAMS ids where this person is a
 * spouse in the family
 * @param string $pid the gedcom xref id for the person to look in
 * @return array array of family ids
 */
function find_sfamily_ids($pid) {
	$families = array();
	if (empty($pid)) return $families;
	$indirec = find_person_record($pid);
	return find_families_in_record($indirec, "FAMS");
}

/**
 * find all family ids in the given record
 *
 * searches an individual gedcom record and returns an array of the FAMS|C ids
 * @param string $indirec the gedcom record for the person to look in
 * @param string $tag 	The family tag to look for
 * @return array array of family ids
 */
function find_families_in_record($indirec, $tag) {
	$families = array();

	$ct = preg_match_all("/1\s*$tag\s*@(.*)@/", $indirec, $match,PREG_SET_ORDER);
	if ($ct>0){
		for($i=0; $i<$ct; $i++) {
			$families[$i] = $match[$i][1];
		}
	}
	return $families;
}

function cleanup_tags_y($irec) {
	$cleanup_facts = array("ANUL","CENS","DIV","DIVF","ENGA","MARR","MARB","MARC","MARL","MARS","BIRT","CHR","DEAT","BURI","CREM","ADOP","DSCR","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI");
	$irec .= "\r\n1";
	$ft = preg_match_all("/1\s(\w+)\s/", $irec, $match);
	for($i=0; $i<$ft; $i++){
		$sfact = $match[1][$i];
		$sfact = trim($sfact);
		if (in_array($sfact, $cleanup_facts)) {
			$srchstr = "/1\s".$sfact."\sY\r\n2/";
			$replstr = "1 ".$sfact."\r\n2";
			$srchstr2 = "/1\s".$sfact."(.{0,1})\r\n2/";
			$srchstr = "/1\s".$sfact."\sY\r\n2/";
			$srchstr3 = "/1\s".$sfact."\sY\r\n1/";
			$irec = preg_replace($srchstr,$replstr,$irec);
			if (preg_match($srchstr2,$irec)){
				$irec = preg_replace($srchstr3,"1",$irec);
			}
		}
	}
	$irec=substr($irec,0,-3);
	return $irec;
}

// ************************************************* START OF MULTIMEDIA FUNCTIONS ********************************* //
/**
 * find the highlighted media object for a gedcom entity
 *
 * New rules for finding primary picture and using thumbnails either under
 * the thumbs directory or with OBJE's with _THUM:
 * - skip files that have _PRIM/_THUM N
 * - default to first (existing) files
 * - first _PRIM and _THUM with Y override defaults
 * @param string $pid the individual, source, or family id
 * @param string $indirec the gedcom record to look in
 * @return array an object array with indexes "thumb" and "file" for thumbnail and filename
 */
function find_highlighted_object($pid, $indirec) {
	global $MEDIA_DIRECTORY, $MEDIA_DIRECTORY_LEVELS, $GM_IMAGE_DIR, $GM_IMAGES, $MEDIA_EXTERNAL;
	global $GEDCOMS, $GEDCOM, $TBLPREFIX;
	
	if (!showFactDetails("OBJE", $pid)) return false;
	$object = array();
	$media_ids = array();
	
	// NOTE: Find media ID's for person
	$sql = "select mm_media as media_id from ".$TBLPREFIX."media_mapping where mm_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' AND mm_gid = '".$pid."' AND mm_gedrec LIKE '1 OBJE%' ORDER BY mm_id ASC";
	$res = dbquery($sql);
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$media_ids[] = $row["media_id"];
	}
	$ct_media_ids = count($media_ids);
	if ($ct_media_ids==0) return false;
	
	// NOTE: Find the media items for that person
	$sql = "select m_file from ".$TBLPREFIX."media where m_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' AND m_gedrec REGEXP '_PRIM Y' AND (";
	foreach ($media_ids as $key => $media_id) {
		$sql .= "m_media = '".$media_id."'";
		if ($ct_media_ids > 1 && $key < $ct_media_ids-1) $sql .= " OR ";
	}
	$sql .= ")";
	$res = dbquery($sql);
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	// NOTE: If no media item is found then take the first media item for that person.
	if (count($row) == 0) {
		$sql = "SELECT m_id, m_file FROM ".$TBLPREFIX."media WHERE m_gedrec NOT REGEXP '_PRIM N' AND m_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' AND (";
		foreach ($media_ids as $key => $media_id) {
			$sql .= "m_media = '".$media_id."'";
			if ($ct_media_ids > 1 && $key < $ct_media_ids-1) $sql .= " OR ";
		}
		$sql .= ") ORDER BY m_id ASC";
	}
	$res = dbquery($sql);
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	
	// NOTE: If we still can't find anything, return no results
	if (count($row) == 0) return false;
	
	// NOTE: Otherwise return the details of the image
	$object["file"] = check_media_depth($row["m_file"]);
	$object["thumb"] = thumbnail_file($row["m_file"]);
	
	return $object;
}

//-- This function finds and returns all of the media objects in a given gedcom record
/**
 * @author	Genmod Development Team
 */

function find_media_in_record($gedrec) {
	global $medialist, $MEDIA_DIRECTORY, $ct, $GM_IMAGE_DIR, $GM_IMAGES, $foundlist, $medialinks, $MEDIA_EXTERNAL;

	$pos1=0;
	$findged = $gedrec;
	while($pos1 = strpos($findged, " OBJE")) {
		//-- get the media sub record from the main gedcom record
		$level = $findged[$pos1-1];

		// NOTE: Get the media record
		$mediarec = get_sub_record($level, "$level OBJE", $findged);

		// NOTE: Determine new position in the record
		if ($mediarec == "") {
			$findged = substr($findged, ($pos1+strlen($mediarec)-1));
			$pos1 = strlen($findged);
		}
		else $findged = substr($findged, ($pos1+strlen($mediarec)-1));

		//-- search if it is an embedded or linked media object
		$embed = preg_match("/(\d) _*FILE (.*)/", $mediarec, $embmatch);
		if ($embed==0) {
			//-- if it is a linked object then store a reference to this individual/family in the
			//-- $medialinks array
			$c2t = preg_match("/@(.*)@/", $mediarec, $match);
			if ($c2t>0) {
				$oid = $match[1];
				$tt = preg_match("/0 @(.*)@ (.*)/", $gedrec, $match);
				if ($tt>0) $id = $match[1];
 				else $id=$ct;
				$type = trim($match[2]);
				if (!isset($medialinks)) $medialinks = array();
				if (!isset($medialinks[$oid])) $medialinks[$oid] = array();
				$medialinks[$oid][$id] = $type;
			}
		}
		else {
			//-- if it is an embedded object then get the filename from it
			$level = $embmatch[1];
			$tt = preg_match("/\d TITL (.*)/", $mediarec, $match);
			$fullpath = check_media_depth($mediarec);
			$filename = "";
			if ((strstr( $fullpath, "://"))||(strstr( $fullpath, "mailto:"))) {
				$filename=$fullpath;
			    $image_type = array("bmp", "gif", "jpeg", "jpg", "pcx", "png", "tiff");
				$path_end=substr($fullpath, strlen($fullpath)-5);
				$type=strtolower(substr($path_end, strpos($path_end, ".")+1));
				if ($MEDIA_EXTERNAL && in_array($type, $image_type)) {
					$thumbnail = $MEDIA_DIRECTORY."thumbs/urls/".preg_replace(array("/http:\/\//", "/\//"), array("","_"),$filename);
				}
				else $thumbnail=$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["large"];
			}
			else {
				$filename = check_media_depth($fullpath);
				$thumbnail = $MEDIA_DIRECTORY."thumbs/".$filename;
				$thumbnail = trim($thumbnail);
				$filename = $MEDIA_DIRECTORY.$filename;
				$filename = trim($filename);
			}
			if ($tt>0) $title = trim($match[1]);
			else $title="";
			if (empty($title)) $title = $filename;
			$isprim="N";
			$isthumb="N";
			$pt = preg_match("/\d _PRIM (.*)/", $mediarec, $match);
			if ($pt>0) $isprim = trim($match[1]);
			$pt = preg_match("/\d _THUM (.*)/", $mediarec, $match);
			if ($pt>0) $isthumb = trim($match[1]);
			$linked = preg_match("/0 @(.*)@ OBJE/", $mediarec, $match);
			if ($linked>0) {
				$linkid = trim($match[1]);
				if (isset($medialinks[$linkid])) $links = $medialinks[$linkid];
				else $links = array();
			}
			else {
				$tt = preg_match("/0 @(.*)@ (.*)/", $gedrec, $match);
				if ($tt>0) $id = $match[1];
				else $id=$ct;
				$type = trim($match[2]);
				if ((isset($foundlist[$filename]))&&(isset($medialist[$foundlist[$filename]]["link"]))) {
					$links = $medialist[$foundlist[$filename]]["link"];
				}
				else $links = array();
				$links[$id] = $type;
			}
			if (!isset($foundlist[$filename])) {
				$media = array();
				$media["file"] = $filename;
				$media["thumb"] = $thumbnail;
				$media["title"] = $title;
				$media["gedcom"] = $mediarec;
				$media["level"] = $level;
				$media["THUM"] = $isthumb;
				$media["PRIM"] = $isprim;
				$medialist[$ct]=$media;
				$foundlist[$filename] = $ct;
				$ct++;
			}
			$medialist[$foundlist[$filename]]["link"]=$links;
		}
	}
}

/**
 * Function to generate a thumbnail image
 *
 * This function takes two arguments, the $filename, which is the orginal file
 * and $thumbnail which is the name of the thumbnail image to be generated.
 *
 * @param string $filename	Name/URL of the picture
 * @param string $thumbnail	Name of the thumbnail that will be generated
 * @return	boolean	true|false
 */
function generate_thumbnail($filename, $thumbnail) {
	global $MEDIA_DIRECTORY, $THUMBNAIL_WIDTH, $AUTO_GENERATE_THUMBS;
	
	if (!$AUTO_GENERATE_THUMBS) return false;
	
	if (file_exists($thumbnail)) return false;
	
	if (!is_writable($MEDIA_DIRECTORY."thumbs")) return false;
	if (strstr($filename, "://") && !is_dir($MEDIA_DIRECTORY."thumbs/urls")) {
		mkdir($MEDIA_DIRECTORY."thumbs/urls", 0777);
		WriteToLog("Folder ".$MEDIA_DIRECTORY."thumbs/urls created.", "I", "S");
	}
	if (!is_writable($MEDIA_DIRECTORY."thumbs/urls")) return false;
	if (!strstr($filename, "://")) {
		if (!file_exists($filename)) return false;
		$imgsize = getimagesize($filename);
		// Check if a size has been determined
		if (!$imgsize) return false;

		//-- check if file is small enough to be its own thumbnail
		if (($imgsize[0]<150)&&($imgsize[1]<150)) {
			@copy($filename, $thumbnail);
			return true;
		}
	}
	else {
		$filename = preg_replace("/ /", "%20", $filename);
		if ($fp = @fopen($filename, "rb")) {
			if ($fp===false) return false;
			$conts = "";
			while(!feof($fp)) {
				$conts .= fread($fp, 4098);
			}
			fclose($fp);
			$fp = fopen($thumbnail, "wb");
			if (!fwrite($fp, $conts)) return false;
			fclose($fp);
			$thumbnail = preg_replace("/%20/", " ", $thumbnail);
			if (!stristr("://", $filename)) $imgsize = getimagesize($filename);
			else $imgsize = getimagesize($thumbnail);
			if ($imgsize===false) return false;
			if (($imgsize[0]<150)&&($imgsize[1]<150)) return true;
		}
		else return false;
	}
	$width = $THUMBNAIL_WIDTH;
	$height = round($imgsize[1] * ($width/$imgsize[0]));
	$ct = preg_match("/\.([^\.]+)$/", $filename, $match);
	if ($ct>0) {
		$ext = strtolower(trim($match[1]));
		if ($ext=="gif") {
			if (function_exists("imagecreatefromgif") && function_exists("imagegif")) {
				$im = imagecreatefromgif($filename);
				if (empty($im)) return false;
				$new = imagecreatetruecolor($width, $height);
				imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
				imagegif($new, $thumbnail);
				imagedestroy($im);
				imagedestroy($new);
				return true;
			}
		}
		else if ($ext=="jpg" || $ext=="jpeg") {
			if (function_exists("imagecreatefromjpeg") && function_exists("imagejpeg")) {
				$im = imagecreatefromjpeg($filename);
				if (empty($im)) return false;
				$new = imagecreatetruecolor($width, $height);
				imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
				imagejpeg($new, $thumbnail);
				imagedestroy($im);
				imagedestroy($new);
				return true;
			}
		}
		else if ($ext=="png") {
			if (function_exists("imagecreatefrompng") && function_exists("imagepng")) {
				$im = imagecreatefrompng($filename);
				if (empty($im)) return false;
				$new = imagecreatetruecolor($width, $height);
				imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
				imagepng($new, $thumbnail);
				imagedestroy($im);
				imagedestroy($new);
				return true;
			}
		}
	}

	return false;
}

// ************************************************* START OF SORTING FUNCTIONS ********************************* //
/**
 * Function to sort GEDCOM fact tags based on their tanslations
 */
function factsort($a, $b) {
   global $factarray;

   return stringsort(trim(strip_tags($factarray[$a])), trim(strip_tags($factarray[$b])));
}
/**
 * String sorting function
 * @param string $a
 * @param string $b
 * @return int negative numbers sort $a first, positive sort $b first
 */
function stringsort($aname, $bname) {
	global $LANGUAGE, $alphabet, $CHARACTER_SET;

	$alphabet = getAlphabet();

	if (is_array($aname)) debug_print_backtrace();

	//-- split strings into strings and numbers
	$aparts = preg_split("/(\d+)/", $aname, -1, PREG_SPLIT_DELIM_CAPTURE);
	$bparts = preg_split("/(\d+)/", $bname, -1, PREG_SPLIT_DELIM_CAPTURE);

	//-- loop through the arrays of strings and numbers
	for($j=0; ($j<count($aparts) && $j<count($bparts)); $j++) {
		$aname = $aparts[$j];
		$bname = $bparts[$j];

		//-- sort numbers differently
		if (is_numeric($aname) && is_numeric($bname)) {
			if ($aname!=$bname) return $aname-$bname;
		}
		else {
	//-- get the name lengths
	$alen = strlen($aname);
	$blen = strlen($bname);

	//-- loop through the characters in the string and if we find one that is different between the strings
	//-- return the difference
	$hungarianex = array("CS","DZ","GY","LY","NY","SZ","TY","ZS","DZS");
	$danishex = array("OE", "AE", "AA");
	for($i=0; ($i<$alen)&&($i<$blen); $i++) {
		if ($LANGUAGE == "hungarian" && $i==0){
			$aletter = substr($aname, $i, 3);
			if (strtoupper($aletter) == "DZS");
			else $aletter = substr($aname, $i, 2);
			if (in_array(strtoupper($aletter), $hungarianex));
			else $aletter = $aname{$i};

			$bletter = substr($bname, $i, 3);
			if (strtoupper($bletter) == "DZS");
			else $bletter = substr($bname, $i, 2);
			if (in_array(strtoupper($bletter), $hungarianex));
			else $bletter = $bname{$i};
		}
		else if (($LANGUAGE == "danish" || $LANGUAGE == "norwegian")){
			$aletter = substr($aname, $i, 2);
			if (in_array(strtoupper($aletter), $danishex)) {
				if (strtoupper($aletter) == "AA") {
					if ($aletter == "aa") $aname=substr_replace($aname, "å", $i, 2);
					else $aname=substr_replace($aname, "Å", $i, 2);
				}
				else if (strtoupper($aletter) == "OE") {
					if ($i==0 || $aletter=="Oe") $aname=substr_replace($aname, "Ø", $i, 2);
				}
				else if (strtoupper($aletter) == "AE") {
					if ($aletter == "ae") $aname=substr_replace($aname, "æ", $i, 2);
					else $aname=substr_replace($aname, "Æ", $i, 2);
				}
			}
			$aletter = substr($aname, $i, 1);

			$bletter = substr($bname, $i, 2);
			if (in_array(strtoupper($bletter), $danishex)) {
				if (strtoupper($bletter) == "AA") {
					if ($bletter == "aa") $bname=substr_replace($bname, "å", $i, 2);
					else $bname=substr_replace($bname, "Å", $i, 2);
				}
				else if (strtoupper($bletter) == "OE") {
					if ($i==0 || $bletter=="Oe") $bname=substr_replace($bname, "Ø", $i, 2);
				}
				else if (strtoupper($bletter) == "AE") {
					if ($bletter == "ae") $bname=substr_replace($bname, "æ", $i, 2);
					else $bname=substr_replace($bname, "Æ", $i, 2);
				}
			}
			$bletter = substr($bname, $i, 1);
		}
		else {
			$aletter = substr($aname, $i, 1);
			$bletter = substr($bname, $i, 1);
		}
		if ($CHARACTER_SET=="UTF-8") {
			$ord = ord($aletter);
			if ($ord==92 || $ord==195 || $ord==196 || $ord==197 || $ord==206 || $ord==207 || $ord==208 || $ord==209 || $ord==214 || $ord==215 || $ord==216 || $ord==217 || $ord==218 || $ord==219){
				$aletter = stripslashes(substr($aname, $i, 2));
			}
			else if ($ord==228 || $ord==229 || $ord == 230 || $ord==232 || $ord==233){
				$aletter = substr($aname, $i, 3);
			}
			else if (strlen($aletter) == 1) $aletter = strtoupper($aletter);

			$ord = ord($bletter);
			if ($ord==92 || $ord==195 || $ord==196 || $ord==197 || $ord==206 || $ord==207 || $ord==208 || $ord==209 || $ord==214 || $ord==215 || $ord==216 || $ord==217 || $ord==218 || $ord==219){
				$bletter = stripslashes(substr($bname, $i, 2));
			}
			else if ($ord==228 || $ord==229 || $ord == 230 || $ord==232 || $ord==233){
				$bletter = substr($bname, $i, 3);
			}
			else if (strlen($bletter) == 1) $bletter = strtoupper($bletter);
		}

		if ($aletter!=$bletter) {
			//-- get the position of the letter in the alphabet string
			$apos = strpos($alphabet, $aletter);
			//print $aletter."=".$apos." ";
			$bpos = strpos($alphabet, $bletter);
			//print $bletter."=".$bpos." ";
			if ($LANGUAGE == "hungarian" && $i==0){ // Check for combination of letters not in the alphabet
				if ($apos==0 || $bpos==0){			// (see array hungarianex)
					$lettera=strtoupper($aletter);
					if (in_array($lettera, $hungarianex)) {
						if ($apos==0) $apos = (strpos($alphabet, substr($lettera,0,1))*3)+(strlen($aletter)>2?2:1);
					}
					else $apos = $apos*3;
					$letterb=strtoupper($bletter);
					if (in_array($letterb, $hungarianex)) {
						if ($bpos==0) $bpos = (strpos($alphabet, substr($letterb,0,1))*3)+(strlen($bletter)>2?2:1);
					}
					else $bpos = $bpos*3;
				}
			}

			if (($bpos!==false)&&($apos===false)) return -1;
			if (($bpos===false)&&($apos!==false)) return 1;
			if (($bpos===false)&&($apos===false)) return ord($aletter)-ord($bletter);
			//print ($apos-$bpos)."<br />";
			if ($apos!=$bpos) return ($apos-$bpos);
		}
	}
	}

	//-- if we made it through the loop then check if one name is longer than the
	//-- other, the shorter one should be first
	if ($alen!=$blen) return ($alen-$blen);
	}
	if (count($aparts)!=count($bparts)) return (count($aparts)-count($bparts));

	//-- the strings are exactly the same so return 0
	return 0;
}

/**
 * User Name comparison Function
 *
 * This function just needs to call the itemsort function on the fullname
 * field of the array
 * @param array $a first user array
 * @param array $b second user array
 * @return int negative numbers sort $a first, positive sort $b first
 */
function usersort($a, $b) {
	global $usersortfields;

	$aname = "";
	$bname = "";
	if (!empty($usersortfields)) {
		foreach($usersortfields as $ind=>$field) {
			if (isset($a[$field])) $aname .= $a[$field];
			if (isset($b[$field])) $bname .= $b[$field];
		}
	}
	else {
		$aname = $a["lastname"]." ".$a["firstname"];
		$bname = $b["lastname"]." ".$b["firstname"];
	}
	return stringsort($aname, $bname);
}

/**
 * sort arrays or strings
 *
 * this function is called by the uasort PHP function to compare two items and tell which should be
 * sorted first.  It uses the language alphabets to create a string that will is used to compare the
 * strings.  For each letter in the strings, the letter's position in the alphabet string is found.
 * Whichever letter comes first in the alphabet string should be sorted first.
 * @param array $a first item
 * @param array $b second item
 * @return int negative numbers sort $a first, positive sort $b first
 */
function firstnamesort($a, $b) {
	if (isset($a["name"])) $aname = sortable_name_from_name($a["name"]);
	else if (isset($a["names"])) $aname = sortable_name_from_name($a["names"][0][0]);
	else if (is_array($a)) $aname = sortable_name_from_name($a[0]);
	else $aname=$a;
	if (isset($b["name"])) $bname = sortable_name_from_name($b["name"]);
	else if (isset($b["names"])) $bname = sortable_name_from_name($b["names"][0][0]);
	else if (is_array($b)) $bname = sortable_name_from_name($b[0]);
	else $bname=$b;
	$aname = strip_prefix($aname);
	$bname = strip_prefix($bname);
	$an = split(", ", $aname);
	if (isset($an[1])) $aname = $an[1].", ".$an[0];
	$bn = split(", ", $bname);
	if (isset($bn[1])) $bname = $bn[1].", ".$bn[0];
	return stringsort($aname, $bname);
}


/**
 * sort arrays or strings
 *
 * this function is called by the uasort PHP function to compare two items and tell which should be
 * sorted first.  It uses the language alphabets to create a string that will is used to compare the
 * strings.  For each letter in the strings, the letter's position in the alphabet string is found.
 * Whichever letter comes first in the alphabet string should be sorted first.
 * @param array $a first item
 * @param array $b second item
 * @return int negative numbers sort $a first, positive sort $b first
 */
function itemsort($a, $b) {
	if (isset($a["name"])) $aname = sortable_name_from_name($a["name"]);
	else if (isset($a["names"])) $aname = sortable_name_from_name($a["names"][0][0]);
	else if (is_array($a)) $aname = sortable_name_from_name($a[0]);
	else $aname=$a;
	if (isset($b["name"])) $bname = sortable_name_from_name($b["name"]);
	else if (isset($b["names"])) $bname = sortable_name_from_name($b["names"][0][0]);
	else if (is_array($b)) $bname = sortable_name_from_name($b[0]);
	else $bname=$b;

	$aname = strip_prefix($aname);
	$bname = strip_prefix($bname);
	return stringsort($aname, $bname);
}

/**
 * sort a list by the gedcom xref id
 * @param array $a	the first $indi array to sort on
 * @param array $b	the second $indi array to sort on
 * @return int negative numbers sort $a first, positive sort $b first
 */
function idsort($a, $b) {
	if (isset($a["gedcom"])) {
		$ct = preg_match("/0 @(.*)@/", $a["gedcom"], $match);
		if ($ct>0) $aid = $match[1];
	}
	if (isset($b["gedcom"])) {
		$ct = preg_match("/0 @(.*)@/", $b["gedcom"], $match);
		if ($ct>0) $bid = $match[1];
	}
	if (empty($aid) || empty($bid)) return itemsort($a, $b);
	else return stringsort($aid, $bid);
}

//-- comparison function for usort
//-- used for index mode
function lettersort($a, $b) {
	return stringsort($a["letter"], $b["letter"]);
}

/**
 * compare two fact records by date
 *
 * Compare facts function is used by the usort PHP function to sort fact baseds on date
 * it parses out the year and if the year is the same, it creates a timestamp based on
 * the current year and the month and day information of the fact
 *
 * @param mixed $a an array with the fact record at index 1 or just a string with the factrecord
 * @param mixed $b an array with the fact record at index 1 or just a string with the factrecord
 * @return int -1 if $a should be sorted first, 0 if they are the same, 1 if $b should be sorted first
 */
function compare_facts($a, $b) {
	global $factarray, $gm_lang, $ASC, $IGNORE_YEAR, $IGNORE_FACTS, $DEBUG, $USE_RTL_FUNCTIONS, $CIRCULAR_BASE;
	if (!isset($ASC)) $ASC = 0;
	if (!isset($IGNORE_YEAR)) $IGNORE_YEAR = 0;
	if (!isset($IGNORE_FACTS)) $IGNORE_FACTS = 0;
	
	$adate=0;
	$bdate=0;
	
	$bef = -1;
	$aft = 1;
	if ($ASC) {
		$bef = 1;
		$aft = -1;
	}
	
	if (is_array($a)) $arec = $a[1];
	else $arec = $a;
	if (is_array($b)) $brec = $b[1];
	else $brec = $b;
	if ($DEBUG) print "\n<br />".substr($arec,0,6)."==".substr($brec,0,6)." ";
	
	if (!$IGNORE_FACTS) {
		$ft = preg_match("/1\s(\w+)(.*)/", $arec, $match);
		if ($ft>0) $afact = $match[1];
		else $afact="";
		$afact = trim($afact);
	
		$ft = preg_match("/1\s(\w+)(.*)/", $brec, $match);
		if ($ft>0) $bfact = $match[1];
		else $bfact="";
		$bfact = trim($bfact);
	
		//-- make sure CHAN facts are displayed at the end of the list
		if ($afact=="CHAN" && $bfact!="CHAN") return $aft;
		if ($afact!="CHAN" && $bfact=="CHAN") return $bef;
	
		//-- BIRT at the top of the list
		if ($afact=="BIRT" && $bfact!="BIRT") return $bef;
		if ($afact!="BIRT" && $bfact=="BIRT") return $aft;
	
		//-- DEAT before BURI
		if ($afact=="DEAT" && $bfact=="BURI") return $bef;
		if ($afact=="BURI" && $bfact=="DEAT") return $aft;
	
		//-- DEAT before CREM
		if ($afact=="DEAT" && $bfact=="CREM") return $bef;
		if ($afact=="CREM" && $bfact=="DEAT") return $aft;
	
		//-- group address related data together
		$addr_group = array("ADDR"=>1,"PHON"=>2,"EMAIL"=>3,"FAX"=>4,"WWW"=>5);
		if (isset($addr_group[$afact]) && isset($addr_group[$bfact])) {
			return $addr_group[$afact]-$addr_group[$bfact];
		}
		if (isset($addr_group[$afact]) && !isset($addr_group[$bfact])) {
			return $aft;
		}
		if (!isset($addr_group[$afact]) && isset($addr_group[$bfact])) {
			return $bef;
		}
	}
	
	$cta = preg_match("/2 DATE (.*)/", $arec, $match);
	if ($cta>0) $adate = parse_date(trim($match[1]));
	$ctb = preg_match("/2 DATE (.*)/", $brec, $match);
	if ($ctb>0) $bdate = parse_date(trim($match[1]));
	//-- DEAT after any other fact if one date is missing
	if ($cta==0 || $ctb==0) {
		if (isset($afact)) {
			if ($afact=="BURI") return $aft;
			if ($afact=="DEAT") return $aft;
			if ($afact=="SLGC") return $aft;
			if ($afact=="SLGS") return $aft;
			if ($afact=="BAPL") return $aft;
			if ($afact=="ENDL") return $aft;
		}
		if (isset($bfact)) {
			if ($bfact=="BURI") return $bef;
			if ($bfact=="DEAT") return $bef;
			if ($bfact=="SLGC") return $bef;
			if ($bfact=="SLGS") return $bef;
			if ($bfact=="BAPL") return $bef;
			if ($bfact=="ENDL") return $bef;
		}
	}
	
	//-- check if both had a date
	if($cta<$ctb) return $aft;
	if($cta>$ctb) return $bef;
	//-- neither had a date so sort by fact name
	if(($cta==0)&&($ctb==0)) {
		if (isset($afact)) {
			if ($afact=="EVEN" || $afact=="FACT") {
				$ft = preg_match("/2 TYPE (.*)/", $arec, $match);
				if ($ft>0) $afact = trim($match[1]);
			}
		}
		else $afact = "";
		if (isset($bfact)) {
			if ($bfact=="EVEN" || $bfact=="FACT") {
				$ft = preg_match("/2 TYPE (.*)/", $brec, $match);
				if ($ft>0) $bfact = trim($match[1]);
			}
		}
		else $bfact = "";
		if (isset($factarray[$afact])) $afact = $factarray[$afact];
		else if (isset($gm_lang[$afact])) $afact = $gm_lang[$afact];
		if (isset($factarray[$bfact])) $bfact = $factarray[$bfact];
		else if (isset($gm_lang[$bfact])) $bfact = $gm_lang[$bfact];
		return stringsort($afact, $bfact);
	}
	if ($IGNORE_YEAR) {
	// Calculate Current year Gregorian date for Hebrew date
	   if ($USE_RTL_FUNCTIONS && isset($adate[0]["ext"]) && strstr($adate[0]["ext"], "#DHEBREW")!==false) $adate = jewishGedcomDateToCurrentGregorian($adate);
		if ($USE_RTL_FUNCTIONS && isset($bdate[0]["ext"]) && strstr($bdate[0]["ext"], "#DHEBREW")!==false) $bdate = jewishGedcomDateToCurrentGregorian($bdate);
	}
	else {
	// Calculate Original year Gregorian date for Hebrew date
	if ($USE_RTL_FUNCTIONS && isset($adate[0]["ext"]) && strstr($adate[0]["ext"], "#DHEBREW")!==false) $adate = jewishGedcomDateToGregorian($adate);
	if ($USE_RTL_FUNCTIONS && isset($bdate[0]["ext"]) && strstr($bdate[0]["ext"], "#DHEBREW")!==false) $bdate = jewishGedcomDateToGregorian($bdate);
	}
	
	if ($DEBUG) print $adate[0]["year"]."==".$bdate[0]["year"]." ";
	if ($adate[0]["year"]==$bdate[0]["year"] || $IGNORE_YEAR) {
		// Check month
		$montha = $adate[0]["mon"];
		$monthb = $bdate[0]["mon"];

		if ($montha == $monthb) {
		// Check day
			$newa = $adate[0]["day"]." ".$adate[0]["month"]." ".date("Y");
			$newb = $bdate[0]["day"]." ".$bdate[0]["month"]." ".date("Y");
			$astamp = strtotime($newa);
			$bstamp = strtotime($newb);
			if ($astamp==$bstamp) {
				if ($IGNORE_YEAR && ($adate[0]["year"]!=$bdate[0]["year"])) return ($adate[0]["year"] < $bdate[0]["year"]) ? $aft : $bef;
				$cta = preg_match("/[2-3] TIME (.*)/", $arec, $amatch);
				$ctb = preg_match("/[2-3] TIME (.*)/", $brec, $bmatch);
				//-- check if both had a time
				if($cta<$ctb) return $aft;
				if($cta>$ctb) return $bef;
				//-- neither had a time
				if(($cta==0)&&($ctb==0)) {
					// BIRT before DEAT on same date
					if (isset($afact) and strstr($afact, "BIRT_")) return $bef;
					if (isset($bfact) and strstr($bfact, "BIRT_")) return $aft;
					return 0;
				}
				$atime = trim($amatch[1]);
				$btime = trim($bmatch[1]);
				$astamp = strtotime($newa." ".$atime);
				$bstamp = strtotime($newb." ".$btime);
				if ($astamp==$bstamp) return 0;
			}
			return ($astamp < $bstamp) ? $bef : $aft;
		}
		else {
			if (isset($CIRCULAR_BASE)) {
				if ($montha < $CIRCULAR_BASE) $montha += 12;
				if ($monthb < $CIRCULAR_BASE) $monthb += 12;
			}
			return ($montha < $monthb) ? $bef : $aft;
		}
	}
	if ($DEBUG) print (($adate[0]["year"] < $bdate[0]["year"]) ? $bef : $aft)." ";
	return ($adate[0]["year"] < $bdate[0]["year"]) ? $bef : $aft;
}

/**
 * fact date sort
 *
 * compare individuals by a fact date
 */
function compare_date($a, $b) {
	global $sortby;

	$tag = "BIRT";
	if (!empty($sortby)) $tag = $sortby;
	$abirt = get_sub_record(1, "1 $tag", $a["gedcom"]);
	$bbirt = get_sub_record(1, "1 $tag", $b["gedcom"]);
	$c = compare_facts($abirt, $bbirt);
	if ($c==0) return itemsort($a, $b);
	else return $c;
}

function gedcomsort($a, $b) {
	$aname = str2upper($a["title"]);
	$bname = str2upper($b["title"]);

	return stringsort($aname, $bname);
}

// ************************************************* START OF MISCELLANIOUS FUNCTIONS ********************************* //
/**
 * Get relationship between two individuals in the gedcom
 *
 * function to calculate the relationship between two people it uses hueristics based on the
 * individuals birthdate to try and calculate the shortest path between the two individuals
 * it uses a node cache to help speed up calculations when using relationship privacy
 * this cache is indexed using the string "$pid1-$pid2"
 * @param string $pid1 the ID of the first person to compute the relationship from
 * @param string $pid2 the ID of the second person to compute the relatiohip to
 * @param bool $followspouse whether to add spouses to the path
 * @param int $maxlenght the maximim length of path
 * @param bool $ignore_cache enable or disable the relationship cache
 * @param int $path_to_find which path in the relationship to find, 0 is the shortest path, 1 is the next shortest path, etc
 */
function get_relationship($pid1, $pid2, $followspouse=true, $maxlength=0, $ignore_cache=false, $path_to_find=0) {
	global $TIME_LIMIT, $start_time, $gm_lang, $NODE_CACHE, $NODE_CACHE_LENGTH, $USE_RELATIONSHIP_PRIVACY, $gm_changes, $GEDCOM, $gm_username;

	$pid1 = strtoupper($pid1);
	$pid2 = strtoupper($pid2);
	if (isset($gm_changes[$pid2."_".$GEDCOM]) && userCanEdit($gm_username)) $indirec = find_gedcom_record($pid2);
	else $indirec = find_person_record($pid2);
	//-- check the cache
	if ($USE_RELATIONSHIP_PRIVACY && !$ignore_cache) {
		if(isset($NODE_CACHE["$pid1-$pid2"])) {
			if ($NODE_CACHE["$pid1-$pid2"]=="NOT FOUND") return false;
			if (($maxlength==0)||(count($NODE_CACHE["$pid1-$pid2"]["path"])-1<=$maxlength)) return $NODE_CACHE["$pid1-$pid2"];
			else return false;
		}
		//-- check the cache for person 2's children
		$famids = array();
		$ct = preg_match_all("/1\sFAMS\s@(.*)@/", $indirec, $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$famids[$i]=$match[$i][1];
		}
		foreach($famids as $indexval => $fam) {
			$famrec = find_family_record($fam);
			$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				$child = $match[$i][1];
				if (!empty($child)){
					if(isset($NODE_CACHE["$pid1-$child"])) {
						if (($maxlength==0)||(count($NODE_CACHE["$pid1-$child"]["path"])+1<=$maxlength)) {
							$node1 = $NODE_CACHE["$pid1-$child"];
							if ($node1!="NOT FOUND") {
								$node1["path"][] = $pid2;
								$node1["pid"] = $pid2;
								$ct = preg_match("/1 SEX F/", $indirec, $match);
								if ($ct>0) $node1["relations"][] = "mother";
								else $node1["relations"][] = "father";
							}
							$NODE_CACHE["$pid1-$pid2"] = $node1;
							if ($node1=="NOT FOUND") return false;
							return $node1;
						}
						else return false;
					}
				}
			}
		}

		if ((!empty($NODE_CACHE_LENGTH))&&($maxlength>0)) {
			if ($NODE_CACHE_LENGTH>=$maxlength) return false;
		}
	}
	//-- end cache checking

	//-- get the birth year of p2 for calculating heuristics
	$birthrec = get_sub_record(1, "1 BIRT", $indirec);
	$byear2 = -1;
	if ($birthrec!==false) {
		$dct = preg_match("/2 DATE .*(\d\d\d\d)/", $birthrec, $match);
		if ($dct>0) $byear2 = $match[1];
	}
	if ($byear2==-1) {
		$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
		for($j=0; $j<$numfams; $j++) {
			// Get the family record
			if (isset($gm_changes[$fmatch[$j][1]."_".$GEDCOM]) && userCanEdit($gm_username)) $famrec = find_gedcom_record($fmatch[$j][1]);
			else $famrec = find_family_record($fmatch[$j][1]);

			// Get the set of children
			$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $cmatch, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				// Get each child's record
				if (isset($gm_changes[$cmatch[$i][1]."_".$GEDCOM]) && userCanEdit($gm_username)) $famrec = find_gedcom_record($cmatch[$i][1]);
				else $childrec = find_person_record($cmatch[$i][1]);
				$birthrec = get_sub_record(1, "1 BIRT", $childrec);
				if ($birthrec!==false) {
					$dct = preg_match("/2 DATE .*(\d\d\d\d)/", $birthrec, $bmatch);
					if ($dct>0) $byear2 = $bmatch[1]-25;
				}
			}
		}
	}
	//-- end of approximating birth year

	//-- current path nodes
	$p1nodes = array();
	//-- ids visited
	$visited = array();

	//-- set up first node for person1
	$node1 = array();
	$node1["path"] = array();
	$node1["path"][] = $pid1;
	$node1["length"] = 0;
	$node1["pid"] = $pid1;
	$node1["relations"] = array();
	$node1["relations"][] = "self";
	$p1nodes[] = $node1;

	$visited[$pid1] = true;

	$found = false;
	$count=0;
	while(!$found) {
		//-- the following 2 lines ensure that the user can abort a long relationship calculation
		//-- refer to http://www.php.net/manual/en/features.connection-handling.php for more
		//-- information about why these lines are included
		if (headers_sent()) {
			print " ";
			if ($count%100 == 0) flush();
		}
		$count++;
		$end_time = getmicrotime();
		$exectime = $end_time - $start_time;
		if (($TIME_LIMIT>1)&&($exectime > $TIME_LIMIT-1)) {
			print "<span class=\"error\">".$gm_lang["timeout_error"]."</span>\n";
			return false;
		}
		if (count($p1nodes)==0) {
			if ($maxlength!=0) {
				if (!isset($NODE_CACHE_LENGTH)) $NODE_CACHE_LENGTH = $maxlength;
				else if ($NODE_CACHE_LENGTH<$maxlength) $NODE_CACHE_LENGTH = $maxlength;
			}
			if (headers_sent()) {
				print "\n<!-- Relationship $pid1-$pid2 NOT FOUND | Visited ".count($visited)." nodes | Required $count iterations.<br />\n";
				print_execution_stats();
				print "-->\n";
			}
			$NODE_CACHE["$pid1-$pid2"] = "NOT FOUND";
			return false;
		}
		//-- search the node list for the shortest path length
		$shortest = -1;
		foreach($p1nodes as $index=>$node) {
			if ($shortest == -1) $shortest = $index;
			else {
				$node1 = $p1nodes[$shortest];
				if ($node1["length"] > $node["length"]) $shortest = $index;
			}
		}
		if ($shortest==-1) return false;
		$node = $p1nodes[$shortest];
		if (($maxlength==0)||(count($node["path"])<=$maxlength)) {
			if ($node["pid"]==$pid2) {
			}
			else {
				//-- hueristic values
				$fatherh = 1;
				$motherh = 1;
				$siblingh = 2;
				$spouseh = 2;
				$childh = 3;

				//-- generate heuristic values based of the birthdates of the current node and p2
				if (isset($gm_changes[$node["pid"]."_".$GEDCOM]) && userCanEdit($gm_username)) $indirec = find_gedcom_record($node["pid"]);
				else $indirec = find_person_record($node["pid"]);
				$byear1 = -1;
				$birthrec = get_sub_record(1, "1 BIRT", $indirec);
				if ($birthrec!==false) {
					$dct = preg_match("/2 DATE .*(\d\d\d\d)/", $birthrec, $match);
					if ($dct>0) $byear1 = $match[1];
				}
				if (($byear1!=-1)&&($byear2!=-1)) {
					$yeardiff = $byear1-$byear2;
					if ($yeardiff < -140) {
						$fatherh = 20;
						$motherh = 20;
						$siblingh = 15;
						$spouseh = 15;
						$childh = 1;
					}
					else if ($yeardiff < -100) {
						$fatherh = 15;
						$motherh = 15;
						$siblingh = 10;
						$spouseh = 10;
						$childh = 1;
					}
					else if ($yeardiff < -60) {
						$fatherh = 10;
						$motherh = 10;
						$siblingh = 5;
						$spouseh = 5;
						$childh = 1;
					}
					else if ($yeardiff < -20) {
						$fatherh = 5;
						$motherh = 5;
						$siblingh = 3;
						$spouseh = 3;
						$childh = 1;
					}
					else if ($yeardiff<20) {
						$fatherh = 3;
						$motherh = 3;
						$siblingh = 1;
						$spouseh = 1;
						$childh = 5;
					}
					else if ($yeardiff<60) {
						$fatherh = 1;
						$motherh = 1;
						$siblingh = 5;
						$spouseh = 2;
						$childh = 10;
					}
					else if ($yeardiff<100) {
						$fatherh = 1;
						$motherh = 1;
						$siblingh = 10;
						$spouseh = 3;
						$childh = 15;
					}
					else {
						$fatherh = 1;
						$motherh = 1;
						$siblingh = 15;
						$spouseh = 4;
						$childh = 20;
					}
				}
				//-- check all parents and siblings of this node
				$famids = array();
				$ct = preg_match_all("/1\sFAMC\s@(.*)@/", $indirec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					if (!isset($visited[$match[$i][1]])) $famids[$i]=$match[$i][1];
				}
				foreach($famids as $indexval => $fam) {
					$visited[$fam] = true;
					if (isset($gm_changes[$fam."_".$GEDCOM]) && userCanEdit($gm_username)) $famrec = find_gedcom_record($fam);
					else $famrec = find_family_record($fam);
					$parents = find_parents_in_record($famrec);
					if ((!empty($parents["HUSB"]))&&(!isset($visited[$parents["HUSB"]]))) {
						$node1 = $node;
						$node1["length"]+=$fatherh;
						$node1["path"][] = $parents["HUSB"];
						$node1["pid"] = $parents["HUSB"];
						$node1["relations"][] = "father";
						$p1nodes[] = $node1;
						if ($node1["pid"]==$pid2) {
							if ($path_to_find>0) $path_to_find--;
							else {
								$found=true;
								$resnode = $node1;
							}
						}
						else $visited[$parents["HUSB"]] = true;
						if ($USE_RELATIONSHIP_PRIVACY) {
							$NODE_CACHE["$pid1-".$node1["pid"]] = $node1;
						}
					}
					if ((!empty($parents["WIFE"]))&&(!isset($visited[$parents["WIFE"]]))) {
						$node1 = $node;
						$node1["length"]+=$motherh;
						$node1["path"][] = $parents["WIFE"];
						$node1["pid"] = $parents["WIFE"];
						$node1["relations"][] = "mother";
						$p1nodes[] = $node1;
						if ($node1["pid"]==$pid2) {
							if ($path_to_find>0) $path_to_find--;
							else {
								$found=true;
								$resnode = $node1;
							}
						}
						else $visited[$parents["WIFE"]] = true;
						if ($USE_RELATIONSHIP_PRIVACY) {
							$NODE_CACHE["$pid1-".$node1["pid"]] = $node1;
						}
					}
					$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$ct; $i++) {
						$child = $match[$i][1];
						if ((!empty($child))&&(!isset($visited[$child]))) {
							$node1 = $node;
							$node1["length"]+=$siblingh;
							$node1["path"][] = $child;
							$node1["pid"] = $child;
							$node1["relations"][] = "sibling";
							$p1nodes[] = $node1;
							if ($node1["pid"]==$pid2) {
								if ($path_to_find>0) $path_to_find--;
								else {
									$found=true;
									$resnode = $node1;
								}
							}
							else $visited[$child] = true;
							if ($USE_RELATIONSHIP_PRIVACY) {
								$NODE_CACHE["$pid1-".$node1["pid"]] = $node1;
							}
						}
					}
				}
				//-- check all spouses and children of this node
				$famids = array();
				$ct = preg_match_all("/1\sFAMS\s@(.*)@/", $indirec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$famids[$i]=$match[$i][1];
				}
				foreach($famids as $indexval => $fam) {
					$visited[$fam] = true;
					if (isset($gm_changes[$fam."_".$GEDCOM]) && userCanEdit($gm_username)) $famrec = find_gedcom_record($fam);
					else $famrec = find_family_record($fam);
					if ($followspouse) {
						$parents = find_parents_in_record($famrec);
						if ((!empty($parents["HUSB"]))&&(!isset($visited[$parents["HUSB"]]))) {
							$node1 = $node;
							$node1["length"]+=$spouseh;
							$node1["path"][] = $parents["HUSB"];
							$node1["pid"] = $parents["HUSB"];
							$node1["relations"][] = "spouse";
							$p1nodes[] = $node1;
							if ($node1["pid"]==$pid2) {
								if ($path_to_find>0) $path_to_find--;
								else {
									$found=true;
									$resnode = $node1;
								}
							}
							else $visited[$parents["HUSB"]] = true;
							if ($USE_RELATIONSHIP_PRIVACY) {
								$NODE_CACHE["$pid1-".$node1["pid"]] = $node1;
							}
						}
						if ((!empty($parents["WIFE"]))&&(!isset($visited[$parents["WIFE"]]))) {
							$node1 = $node;
							$node1["length"]+=$spouseh;
							$node1["path"][] = $parents["WIFE"];
							$node1["pid"] = $parents["WIFE"];
							$node1["relations"][] = "spouse";
							$p1nodes[] = $node1;
							if ($node1["pid"]==$pid2) {
								if ($path_to_find>0) $path_to_find--;
								else {
									$found=true;
									$resnode = $node1;
								}
							}
							else $visited[$parents["WIFE"]] = true;
							if ($USE_RELATIONSHIP_PRIVACY) {
								$NODE_CACHE["$pid1-".$node1["pid"]] = $node1;
							}
						}
					}
					$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$ct; $i++) {
						$child = $match[$i][1];
						if ((!empty($child))&&(!isset($visited[$child]))) {
							$node1 = $node;
							$node1["length"]+=$childh;
							$node1["path"][] = $child;
							$node1["pid"] = $child;
							$node1["relations"][] = "child";
							$p1nodes[] = $node1;
							if ($node1["pid"]==$pid2) {
								if ($path_to_find>0) $path_to_find--;
								else {
									$found=true;
									$resnode = $node1;
								}
							}
							else $visited[$child] = true;
							if ($USE_RELATIONSHIP_PRIVACY) {
								$NODE_CACHE["$pid1-".$node1["pid"]] = $node1;
							}
						}
					}
				}
			}
		}
		unset($p1nodes[$shortest]);
	} //-- end while loop
	if (headers_sent()) {
		print "\n<!-- Relationship $pid1-$pid2 | Visited ".count($visited)." nodes | Required $count iterations.<br />\n";
		print_execution_stats();
		print "-->\n";
	}
	return $resnode;
}

/**
 * write changes
 *
 * this function writes the $gm_changes back to the <var>$INDEX_DIRECTORY</var>/gm_changes.php
 * file so that it can be read in and checked to see if records have been updated.  It also stores
 * old records so that they can be undone.
 * @todo Delete as soon as $LAST_CHANGE_EMAIL is in DB
 * @return bool true if successful false if there was an error
 */
function write_changes() {
	global $GEDCOMS, $GEDCOM,$INDEX_DIRECTORY, $LAST_CHANGE_EMAIL;

	if (!isset($LAST_CHANGE_EMAIL)) $LAST_CHANGE_EMAIL = time();
	//-- write the changes file
	$changestext = "<?php\n\$LAST_CHANGE_EMAIL = $LAST_CHANGE_EMAIL;\n";
	$changestext .= "\n"."?".">\n";
	$fp = fopen($INDEX_DIRECTORY."gm_changes.php", "wb");
	if ($fp===false) {
		print "ERROR 6: Unable to open changes file resource.  Unable to complete request.\n";
		return false;
	}
	$fw = fwrite($fp, $changestext);
	if ($fw===false) {
		print "ERROR 7: Unable to write to changes file.\n";
		fclose($fp);
		return false;
	}
	fclose($fp);
	return true;
}

/**
 * get theme names
 *
 * function to get the names of all of the themes as an array
 * it searches the themes directory and reads the name from the theme_name variable
 * in the theme.php file.
 * @return array and array of theme names and their corresponding directory
 */
function get_theme_names() {
	$themes = array();
	$d = dir("themes");
	while (false !== ($entry = $d->read())) {
		if ($entry!="." && $entry!=".." && $entry!="CVS" && is_dir("themes/$entry")) {
			$theme = array();
			$themefile = implode("", file("themes/$entry/theme.php"));
			$tt = preg_match("/theme_name\s+=\s+\"(.*)\";/", $themefile, $match);
			if ($tt>0) $themename = trim($match[1]);
			else $themename = "themes/$entry";
			$theme["name"] = $themename;
			$theme["dir"] = "themes/$entry/";
			$themes[] = $theme;
		}
	}
	$d->close();
	uasort($themes, "itemsort");
	return $themes;
}

/**
 * format a fact for calendar viewing
 *
 * @param string $factrec the fact record
 * @param string $action tells what type calendar the user is viewing
 * @param string $filter should the fact be filtered by living people etc
 * @param string $pid the gedcom xref id of the record this fact belongs to
 * @param string $filterev "all" to show all events; "bdm" to show only Births, Deaths, Marriages; Event code to show only that event
 * @return string a html text string that can be printed
 */
function get_calendar_fact($factrec, $action, $filterof, $pid, $filterev="all") {
	global $gm_lang, $factarray, $year, $month, $day, $TEMPLE_CODES, $CALENDAR_FORMAT, $monthtonum, $TEXT_DIRECTION, $SHOW_PEDIGREE_PLACES, $caltype;
	global $CalYear, $currhYear, $USE_RTL_FUNCTIONS;
//	global $currhMonth;

	$Upcoming = false;
	if ($action == "upcoming") {
		$action = "today";
		$Upcoming = true;
	}

	$skipfacts = array("CHAN", "BAPL", "SLGC", "SLGS", "ENDL");
	$BDMfacts = array("BIRT", "DEAT", "MARR");

//	$ft = preg_match("/1\s(_?\w+)\s(.*)/", $factrec, $match);
	$ft = preg_match("/1\s(\w+)(.*)/", $factrec, $match);
	if ($ft>0) $fact = $match[1];
	else return "filter";

	if (in_array($fact, $skipfacts)) return "filter";
// visitor returns in the following for BIRT ??
// why does the visitor get a blank from showFactDetails($fact, $pid) - because he should not see data of live??
// A logged in user in FF sees I92, in IE sees 2 on 21.4

	if ((!showFact($fact, $pid))||(!showFactDetails($fact, $pid)))  return "";
	if (FactViewRestricted($pid, $factrec)) return "";

	$fact = trim($fact);
	$factref = $fact;
	if ($fact=="EVEN" || $fact=="FACT") {
		$ct = preg_match("/2 TYPE (.*)/", $factrec, $tmatch);
		if ($ct>0) {
			$factref = trim($tmatch[1]);
		    if ((!showFact($factref, $pid))||(!showFactDetails($factref, $pid))) return "";
	    }
	}

	// Use current year for age in dayview
	if ($action == "today"){
		$yearnow = getdate();
		$yearnow = $yearnow["year"];
	}
	else	{
		$yearnow = $year;
	}

	$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
	if ($hct>0 && $USE_RTL_FUNCTIONS)
		if ($action == "today") $yearnow = $currhYear;
		else $yearnow = $CalYear;

	$text = "";

	// See whether this Fact should be filtered out or not
	$Filtered = false;
	if (in_array($fact, $skipfacts) or in_array($factref, $skipfacts)) $Filtered = true;
	if ($filterev=="bdm") {
		if (!in_array($fact, $BDMfacts) and !in_array($factref, $BDMfacts)) $Filtered = true;
	}
	if ($filterev!="all" and $filterev!="bdm") {
		if ($fact!=$filterev and $factref!=$filterev) $Filtered = true;
	}

	if (!$Filtered) {
		if ($fact=="EVEN" || $fact=="FACT") {
			if ($ct>0) {
				if (isset($factarray["$factref"])) $text .= $factarray["$factref"];
				else $text .= $factref;
			}
			else $text .= $factarray[$fact];
		}
		else {
			if (isset($factarray[$fact])) $text .= $factarray[$fact];
			else $text .= $fact;
		}
//		if ($filterev!="all" && $filterev!=$fact && $filterev!=$factref) return "filter";

		if ($text!="") $text=PrintReady($text);

		$ct = preg_match("/\d DATE(.*)/", $factrec, $match);
		if ($ct>0) {
			$text .= " - <span class=\"date\">".get_date_url($match[1])."</span>";
//			$yt = preg_match("/ (\d\d\d\d)/", $match[1], $ymatch);
			$yt = preg_match("/ (\d\d\d\d|\d\d\d)/", $match[1], $ymatch);
			if ($yt>0) {

				$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $match[1], $hmatch);
	            if ($hct>0 && $USE_RTL_FUNCTIONS && $action=='today')

// should perhaps use the month of the fact to find if should use $currhYear or $currhYear+1 or $currhYear-1 to calculate age
// use $currhMonth and the fact month for this

                   $age = $currhYear - $ymatch[1];
				else
				   $age = $yearnow - $ymatch[1];
				$yt2 = preg_match("/(...) (\d\d\d\d|\d\d\d)/", $match[1], $bmatch);
				if ($yt2>0) {
					if (isset($monthtonum[strtolower(trim($bmatch[1]))])) {
						$emonth = $monthtonum[strtolower(trim($bmatch[1]))];
						if (!$Upcoming && ($emonth<$monthtonum[strtolower($month)])) $age--;
						$bt = preg_match("/(\d+) ... (\d\d\d\d|\d\d\d)/", $match[1], $bmatch);
						if ($bt>0) {
							$edate = trim($bmatch[1]);
							if (!$Upcoming && ($edate<$day)) $age--;
						}
					}
				}
				$yt3 = preg_match("/(.+) ... (\d\d\d\d|\d\d\d)/", $match[1], $bmatch);
				if ($yt3>0) {
					if (!$Upcoming && ($bmatch[1]>$day)) $age--;
				}
				if (($filterof=="recent")&&($age>100)) return "filter";
				// Limit facts to before the given year in monthview
				if (($age<0) && ($action == "calendar")) return "filter";
				if ($action!='year'){
					$text .= " (" . str_replace("#year_var#", convert_number($age), $gm_lang["year_anniversary"]).")";
				}
 				if($TEXT_DIRECTION == "rtl"){
 					$text .= "&lrm;";
 				}
			}
			if (($action=='today')||($action=='year')) {
				// -- find place for each fact
				if ($SHOW_PEDIGREE_PLACES>0) {
					$ct = preg_match("/2 PLAC (.*)/", $factrec, $match);
					if ($ct>0) {
						$text .=($action=='today'?"<br />":" ");
						$plevels = preg_split("/,/", $match[1]);
						for($plevel=0; $plevel<$SHOW_PEDIGREE_PLACES; $plevel++) {
							if (!empty($plevels[$plevel])) {
								if ($plevel>0) $text .=", ";
								$text .= PrintReady($plevels[$plevel]);
							}
						}
					}
				}

				// -- find temple code for lds facts
				$ct = preg_match("/2 TEMP (.*)/", $factrec, $match);
				if ($ct>0) {
					$tcode = $match[1];
					$tcode = trim($tcode);
					if (array_key_exists($tcode, $TEMPLE_CODES)) $text .= "<br />".$gm_lang["temple"].": ".$TEMPLE_CODES[$tcode];
					else $text .= "<br />".$gm_lang["temple_code"].$tcode;
				}
			}
		}
		$text .= "<br />";
	}
	if ($text=="") return "filter";

	return $text;
}

//-- this function will convert a digit number to a number in a different language
function convert_number($num) {
	global $gm_lang, $LANGUAGE;

	if ($LANGUAGE == "chinese") {
		$numstr = "$num";
		$zhnum = "";
		//-- currently limited to numbers <10000
		if (strlen($numstr)>4) return $numstr;

		$ln = strlen($numstr);
		$numstr = strrev($numstr);
		for($i=0; $i<$ln; $i++) {
			if (($i==1)&&($numstr{$i}!="0")) $zhnum = $gm_lang["10"].$zhnum;
			if (($i==2)&&($numstr{$i}!="0")) $zhnum = $gm_lang["100"].$zhnum;
			if (($i==3)&&($numstr{$i}!="0")) $zhnum = $gm_lang["1000"].$zhnum;
			if (($i!=1)||($numstr{$i}!=1)) $zhnum = $gm_lang[$numstr{$i}].$zhnum;
		}
		return $zhnum;
	}
	return $num;
}

/**
 * Sent mail
 *
 * This function is a wrapper to the php mail() function so that we can change settings globally
 * for detailed info on MIME (RFC 1521) email see: http://www.freesoft.org/CIE/RFC/1521/index.htm
 *
 * @author	Genmod Development Team
 * @param		string	$to			E-mail address the mail is sent to
 * @param		string	$subject		Subject of the e-mail
 * @param		string	$message		Body of the e-mail already formatted
 * @param		string	$header		The headers to be included
 * @param		string	$mailformat	The type of mail to send. Multipart is for rich e-mails and text for plain e-mails
 */
function gmMail($to, $subject, $message, $header, $mailformat="multipart"){
	global $gm_lang, $CHARACTER_SET, $LANGUAGE, $GM_STORE_MESSAGES, $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $stylesheet;
	global $THEME_DIR, $HOME_SITE_TEXT;
	
	$header = trim($header)."\r\n";
	
	if($mailformat == "multipart"){
		// NOTE: Get the stylesheet
		$handle = @fopen($stylesheet, "r");
		$styles = file_get_contents($stylesheet);
		fclose($handle);
		
		// NOTE: Extra header information
		$header .= "X-Mailer: Genmod\n";
		
		// NOTE: Boundaries
		$boundary = "==String_Boundary_x" .md5(time()). "x";
		$boundary2 = "==String_Boundary2_y" .md5(time()). "y";
		
		// NOTE: Create container
		$header .= "MIME-Version: 1.0\n";
		$header .= "Content-Type: multipart/related;\r\n";
		$header .= " type=\"multipart/alternative\";\r\n";
		$header .= " boundary=\"$boundary\";\r\n\r\n";
		
		// NOTE: Boundary start of the multipart/related
		$htmlMessage = "--$boundary\r\n";
		
		// NOTE: Start the multipart/alternative HTML Section
		$htmlMessage .= "Content-Type: multipart/alternative;\r\n";
		$htmlMessage .= " boundary=\"$boundary2\";\r\n\r\n";
		
		// HTML section
		$htmlMessage .= "--$boundary2\r\n";
		$htmlMessage .= "Content-Type: text/html; charset=\"".$CHARACTER_SET."\"\\en";
		$htmlMessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
		$htmlMessage .= "<style type=\"text/css\">\r\n";
		$htmlMessage .= $styles;
		$htmlMessage .= "</style>\r\n";
		$htmlMessage .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n";
		$htmlMessage .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\r\n";
		$htmlMessage .= "<head>\r\n";
		$htmlMessage .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\r\n";
		$htmlMessage .= "</head>\r\n";
		$htmlMessage .= "<body>\r\n";
		$htmlMessage .= "<span class=\"center\"><img src=\"cid:logo\" /></span><br /><br />".$HOME_SITE_TEXT."<br /><br />\r\n";
		$htmlMessage .= "<img src=\"cid:divider\" width=\"99%\" height=\"3\" alt=\"\" /><br /><br />";
		$htmlMessage .= "<div id=\"container\">";
		$htmlMessage .= preg_replace("/[\r\n]+/", "<br />", $message);
		$htmlMessage .= "</div>";
		$htmlMessage .= "</body></html>\r\n";
		
		// NOTE: Close HTML section
		$htmlMessage .= "--$boundary2--\n";
		
		// Site logo
		$htmlMessage .= "--$boundary\n";
		$htmlMessage .= "Content-ID: <logo>\n";
		$htmlMessage .= "Content-Type: image/gif\n";
		$htmlMessage .= "Content-Transfer-Encoding: base64\n\n";
		
		$logo = $THEME_DIR."header.gif";
		$file = fopen($logo,'rb');
		$data = fread($file,filesize($logo));
		fclose($file);
		
		$data = chunk_split(base64_encode($data));
		$htmlMessage .= "$data\n\n";
		
		// NOTE: Divider line
		$htmlMessage .= "--$boundary\n";
		$htmlMessage .= "Content-ID: <divider>\n";
		$htmlMessage .= "Content-Type: image/gif\n";
		$htmlMessage .= "Content-Transfer-Encoding: base64\n\n";
		
		$divider = $GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"];
		$file = fopen($divider,'rb');
		$data = fread($file,filesize($divider));
		fclose($file);
		
		$data = chunk_split(base64_encode($data));
		$htmlMessage .= "$data\n\n";
		
		// NOTE: Close containter
		$htmlMessage .= "--$boundary--\n";
	}                
	mail($to, $subject, $htmlMessage, $header);
}

/**
 * Decode a filename
 *
 * Windows doesn't use UTF-8 for its file system so we have to decode the filename
 * before it can be used on the filesystem
 *
 * @author	Genmod Development Team
 * @param		string	$filename		Filename to be decoded
 * @return	string	UTF-8 decoded filename
 */
function filename_decode($filename) {
	if (preg_match("/Win32/", $_SERVER["SERVER_SOFTWARE"])>0) return utf8_decode($filename);
	else return $filename;
}

/**
 * Encode a filename
 *
 * Windows doesn't use UTF-8 for its file system so we have to encode the filename
 * before it can be used in GM
 *
 * @author	Genmod Development Team
 * @param		string	$filename		Filename to be encoded
 * @return	string	UTF-8 encoded filename
 */
function filename_encode($filename) {
	if (preg_match("/Win32/", $_SERVER["SERVER_SOFTWARE"])>0) return utf8_encode($filename);
	else return $filename;
}

/**
 * Create 1 string containing the numbers and alphabets that are in the system
 *
 * A string is created starting with the 10 numbers, the uppercase and the
 * lowercase letters from the active alphabet. Then all the uppercase followed
 * by all the lowercase letters of the other languages in the system are added
 * to this string. The string is then used for sorting data in the correct order.
 *
 * @author	Genmod Development Team
 * @return 	string	String containing all numbers and lowercase and uppercase characters
 */
function getAlphabet(){
	global $ALPHABET_upper, $ALPHABET_lower, $LANGUAGE, $alphabet;

	//-- setup the language alphabet string
	if (!isset($alphabet)) {
		$alphabet = "0123456789".$ALPHABET_upper[$LANGUAGE].$ALPHABET_lower[$LANGUAGE];
		foreach ($ALPHABET_upper as $l => $upper){
			if ($l <> $LANGUAGE) $alphabet.=$upper;
		}
		foreach ($ALPHABET_lower as $l => $lower){
			if ($l <> $LANGUAGE) $alphabet.=$lower;
		}
	}
	return $alphabet;
}

/**
 * get a list of the reports in the reports directory
 *
 * When $force is false, the function will first try to read the reports list from the$INDEX_DIRECTORY."/reports.dat"
 * data file.  Otherwise the function will parse the report xml files and get the titles.
 * @param boolean $force	force the code to look in the directory and parse the files again
 * @return array 	The array of the found reports with indexes [title] [file]
 */
function get_report_list($force=false) {
	global $INDEX_DIRECTORY, $report_array, $vars, $xml_parser, $elementHandler, $LANGUAGE;

	$files = array();
	if (!$force) {
		//-- check if the report files have been cached
		if (file_exists($INDEX_DIRECTORY."/reports.dat")) {
			$reportdat = "";
			$fp = fopen($INDEX_DIRECTORY."/reports.dat", "r");
			while ($data = fread($fp, 4096)) {
				$reportdat .= $data;
			}
			fclose($fp);
			$files = unserialize($reportdat);
			foreach($files as $indexval => $file) {
				if (isset($file["title"][$LANGUAGE]) && (strlen($file["title"][$LANGUAGE])>1)) return $files;
			}
		}
	}

	//-- find all of the reports in the reports directory
	$d = dir("reports");
	while (false !== ($entry = $d->read())) {
		if (($entry!=".") && ($entry!="..") && ($entry!="CVS") && (strstr($entry, ".xml")!==false)) {
			if (!isset($files[$entry]["file"])) $files[$entry]["file"] = "reports/".$entry;
		}
	}
	$d->close();

	require_once("includes/reportheader.php");
	$report_array = array();
	if (!function_exists("xml_parser_create")) return $report_array;
	foreach($files as $file=>$r) {
		$report_array = array();
		//-- start the sax parser
		$xml_parser = xml_parser_create();
		//-- make sure everything is case sensitive
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		//-- set the main element handler functions
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		//-- set the character data handler
		xml_set_character_data_handler($xml_parser, "characterData");

		if (file_exists($r["file"])) {
			//-- open the file
			if (!($fp = fopen($r["file"], "r"))) {
			   die("could not open XML input");
			}
			//-- read the file and parse it 4kb at a time
			while ($data = fread($fp, 4096)) {
				if (!xml_parse($xml_parser, $data, feof($fp))) {
					die(sprintf($data."\nXML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
				}
			}
			fclose($fp);
			xml_parser_free($xml_parser);
			if (isset($report_array["title"]) && isset($report_array["access"]) && isset($report_array["icon"])) {
				$files[$file]["title"][$LANGUAGE] = $report_array["title"];
				$files[$file]["access"] = $report_array["access"];
				$files[$file]["icon"] = $report_array["icon"];
			}
		}
	}

	$fp = @fopen($INDEX_DIRECTORY."/reports.dat", "w");
	@fwrite($fp, serialize($files));
	@fclose($fp);

	return $files;
}

/**
 * clean up user submitted input before submitting it to the SQL query
 *
 * This function will take user submitted input string and remove any special characters
 * before they are submitted to the SQL query.
 * Examples of invalid characters are _ & ?
 * @param string $pid	The string to cleanup
 * @return string	The cleaned up string
 */
function clean_input($pid) {
	$pid = preg_replace("/[%?_]/", "", trim($pid));
	return $pid;
}

/**
 * get a quick-glance view of current LDS ordinances
 * @param string $indirec
 * @return string
 */
function get_lds_glance($indirec) {
	$text = "";

	$ord = get_sub_record(1, "1 BAPL", $indirec);
	if ($ord) $text .= "B";
	else $text .= "_";
	$ord = get_sub_record(1, "1 ENDL", $indirec);
	if ($ord) $text .= "E";
	else $text .= "_";
	$found = false;
	$ct = preg_match_all("/1 FAMS @(.*)@/", $indirec, $match, PREG_SET_ORDER);
	for($i=0; $i<$ct; $i++) {
		$famrec = find_family_record($match[$i][1]);
		if ($famrec) {
			$ord = get_sub_record(1, "1 SLGS", $famrec);
			if ($ord) {
				$found = true;
				break;
			}
		}
	}
	if ($found) $text .= "S";
	else $text .= "_";
	$ord = get_sub_record(1, "1 SLGC", $indirec);
	if ($ord) $text .= "P";
	else $text .= "_";
	return $text;
}

/**
 * Check for facts that may exist only once for a certain record type.
 * If the fact already exists in the second array, delete it from the first one.
 */
 function CheckFactUnique($uniquefacts, $recfacts, $type) {

	 foreach($recfacts as $indexval => $fact) {
		if (($type == "SOUR") || ($type == "REPO")) $factrec = $fact[0];
		if (($type == "FAM") || ($type == "INDI")) $factrec = $fact[1];
		$ft = preg_match("/1 (\w+)(.*)/", $factrec, $match);
		if ($ft>0) {
			$fact = trim($match[1]);
			$key = array_search($fact, $uniquefacts);
			if ($key !== false) unset($uniquefacts[$key]);
		}
	 }
	 return $uniquefacts;
 }

/**
 * remove any custom GM tags from the given gedcom record
 * custom tags include _GMU and _THUM
 * @param string $gedrec	the raw gedcom record
 * @return string		the updated gedcom record
 */
function remove_custom_tags($gedrec, $remove="no") {
	if ($remove=="yes") {
		//-- remove _GMU
		$gedrec = preg_replace("/\d _GMU .*/", "", $gedrec);
		//-- remove _THUM
		$gedrec = preg_replace("/\d _THUM .*/", "", $gedrec);
	}
	//-- cleanup so there are not any empty lines
	$gedrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $gedrec);
	//-- make downloaded file DOS formatted
	$gedrec = preg_replace("/([^\r])\n/", "$1\n", $gedrec);
	return $gedrec;
}

/**
 * find the name of the first GEDCOM file in a zipfile
 * @param string $zipfile	the path and filename
 * @param boolean $extract  true = extract and return filename, false = return filename
 * @return string		the path and filename of the gedcom file
 */

function GetGEDFromZIP($zipfile, $extract=true) {
	GLOBAL $INDEX_DIRECTORY;

	require_once "includes/pclzip.lib.php";
	$zip = new PclZip($zipfile);

	// if it's not a valid zip, just return the filename
	if (($list = $zip->listContent()) == 0) {
		return $zipfile;
	}

	// Determine the extract directory
	$slpos = strrpos($zipfile, "/");
	if (!$slpos) $slpos = strrpos($zipfile,"\\");
	if ($slpos) $path = substr($zipfile, 0, $slpos+1);
	else $path = $INDEX_DIRECTORY;
	// Scan the files and return the first .ged found
	foreach($list as $key=>$listitem) {
		if (($listitem["status"]="ok") && (strstr(strtolower($listitem["filename"]), ".")==".ged")) {
			$filename = basename($listitem["filename"]);
			if ($extract == false) return $filename;

			// if the gedcom exists, save the old one. NOT to bak as it will be overwritten on import
			if (file_exists($path.$filename)) {
				if (file_exists($path.$filename.".old")) unlink($path.$filename.".old");
				copy($path.$filename, $path.$filename.".old");
				unlink($path.$filename);
			}
			if ($zip->extract(PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_PATH, $path, PCLZIP_OPT_BY_NAME, $listitem["filename"]) == 0) {
				print "ERROR cannot extract ZIP";
			}
			return $filename;
		}
	}
	return $zipfile;
}

/**
 * look for and run any hook files found
 *
 * @param string $type		the type of hook requested (login|logout|adduser|updateuser|deleteuser)
 * @param array  $params	array of parameters
 * @return bool				returns true
 */
function runHooks($type, $params=array ())
{
	// look for core hooks
	if (file_exists("hooks/{$type}/"))
	{
		$dirs = array ("hooks/{$type}/");
	}
	else
	{
		$dirs = array ();
	}
	// look for module hooks
	$d = dir('modules/');
	while (false !== ($f = $d->read()))
	{
		if ($f === '.' || $f === '..')
		{
			continue;
		}
		if (file_exists("modules/{$f}/hooks/{$type}"))
		{
			$dirs[] = "modules/{$f}/hooks/{$type}/";
		}
	}
	$d->close();
	// run all found hooks
	foreach ($dirs as $directory)
	{
		$d = @dir($directory);
		if (is_object($d))
		{
			while (false !== ($f = $d->read()))
			{
				if (stristr($f, '.php'))
				{
					include_once "{$directory}/{$f}";
					$cl = substr($f, 0, -4);
					$obj = new $cl();
					$obj->hook($params);
				}
			}
			$d->close();
		}
	}
	return true;
}

function getfilesize($bytes) {
   if ($bytes >= 1099511627776) {
       $return = round($bytes / 1024 / 1024 / 1024 / 1024, 2);
       $suffix = "TB";
   } elseif ($bytes >= 1073741824) {
       $return = round($bytes / 1024 / 1024 / 1024, 2);
       $suffix = "GB";
   } elseif ($bytes >= 1048576) {
       $return = round($bytes / 1024 / 1024, 2);
       $suffix = "MB";
   } elseif ($bytes >= 1024) {
       $return = round($bytes / 1024, 2);
       $suffix = "KB";
   } else {
       $return = $bytes;
       $suffix = "B";
   }
   /*if ($return == 1) {
       $return .= " " . $suffix;
   } else {
       $return .= " " . $suffix . "s";
   }*/
   $return .= " " . $suffix;
   return $return;
}

/**
 * split multi-ged keys and return either key or gedcom
 *
 * @param string $key		the multi-ged key to be split
 * @param string $type		either "id" or "ged", depending on what must be returned
 * @return string			either the key or the gedcom name
 */
function splitkey($key, $type) {
	$p1 = strpos($key,"[");
	$id = substr($key,0,$p1);
	if ($type == "id") return $id;
	$p2 = strpos($key,"]");
	$ged = substr($key,$p1+1,$p2-$p1-1);
	return get_gedcom_from_id($ged);
}

/**
 * array merge function for GM
 * the PHP array_merge function will reindex all numerical indexes
 * This function should only be used for associative arrays
 * @param array $array1
 * @param array $array2
 */
function gm_array_merge($array1, $array2) {
	foreach($array2 as $key=>$value) {
		$array1[$key] = $value;
	}
	return $array1;
}

/**
 * function to build an URL querystring from GET or POST variables
 * @return string
 */
function get_query_string() {
	$qstring = "";
	if (!empty($_GET)) {
		foreach($_GET as $key => $value) {
			if($key != "view") {
				$qstring .= $key."=".$value."&amp;";
			}
		}
	}
	else {
		if (!empty($_POST)) {
			foreach($_POST as $key => $value) {
				if($key != "view") {
					$qstring .= $key."=".$value."&amp;";
				}
			}
		}
	}
	return $qstring;
}

//--- copied from reportpdf.php
	function add_ancestors($pid, $children=false, $generations=-1) {
		global $list, $indilist, $genlist;

		$genlist = array($pid);
		$list[$pid]["generation"] = 1;
		while(count($genlist)>0) {
			$id = array_shift($genlist);
			$famids = find_family_ids($id);
			if (count($famids)>0) {
				foreach($famids as $indexval => $famid) {
					$parents = find_parents($famid);
					if (!empty($parents["HUSB"])) {
						find_person_record($parents["HUSB"]);
						$list[$parents["HUSB"]] = $indilist[$parents["HUSB"]];
						$list[$parents["HUSB"]]["generation"] = $list[$id]["generation"]+1;
					}
					if (!empty($parents["WIFE"])) {
						find_person_record($parents["WIFE"]);
						$list[$parents["WIFE"]] = $indilist[$parents["WIFE"]];
						$list[$parents["WIFE"]]["generation"] = $list[$id]["generation"]+1;
					}
					if ($generations == -1 || $list[$id]["generation"]+1 < $generations) {
						if (!empty($parents["HUSB"])) array_push($genlist, $parents["HUSB"]);
						if (!empty($parents["WIFE"])) array_push($genlist, $parents["WIFE"]);
					}
					if ($children) {
						$famrec = find_family_record($famid);
						if ($famrec) {
							$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
							for($i=0; $i<$num; $i++) {
								find_person_record($smatch[$i][1]);
								$list[$smatch[$i][1]] = $indilist[$smatch[$i][1]];
								if (isset($list[$id]["generation"])) $list[$smatch[$i][1]]["generation"] = $list[$id]["generation"];
								else $list[$smatch[$i][1]]["generation"] = 1;
							}
						}
					}
				}
			}
		}
	}

	//--- copied from reportpdf.php
	function add_descendancy($pid, $parents=false, $generations=-1) {
		global $list, $indilist;

		if (!isset($list[$pid])) {
			find_person_record($pid);
			$list[$pid] = $indilist[$pid];
		}
		if (!isset($list[$pid]["generation"])) {
			$list[$pid]["generation"] = 0;
		}
		$famids = find_sfamily_ids($pid);
		if (count($famids)>0) {
			foreach($famids as $indexval => $famid) {
				$famrec = find_family_record($famid);
				if ($famrec) {
					if ($parents) {
						$parents = find_parents_in_record($famrec);
						if (!empty($parents["HUSB"])) {
							find_person_record($parents["HUSB"]);
							$list[$parents["HUSB"]] = $indilist[$parents["HUSB"]];
							if (isset($list[$pid]["generation"])) $list[$parents["HUSB"]]["generation"] = $list[$pid]["generation"]-1;
							else $list[$parents["HUSB"]]["generation"] = 1;
						}
						if (!empty($parents["WIFE"])) {
							find_person_record($parents["WIFE"]);
							$list[$parents["WIFE"]] = $indilist[$parents["WIFE"]];
							if (isset($list[$pid]["generation"])) $list[$parents["WIFE"]]["generation"] = $list[$pid]["generation"]-1;
							else $list[$parents["HUSB"]]["generation"] = 1;
						}
					}
					$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
					for($i=0; $i<$num; $i++) {
						find_person_record($smatch[$i][1]);
						$list[$smatch[$i][1]] = $indilist[$smatch[$i][1]];
						if (isset($list[$smatch[$i][1]]["generation"])) $list[$smatch[$smatch[$i][1]][1]]["generation"] = $list[$pid]["generation"]+1;
						else $list[$smatch[$i][1]]["generation"] = 2;
					}
					if($generations == -1 || $list[$pid]["generation"]+1 < $generations)
					{
						for($i=0; $i<$num; $i++) {
							add_descendancy($smatch[$i][1], $parents, $generations);	// recurse on the childs family
						}
					}
				}
			}
		}
	}

/**
 * check if the maximum number of page views per hour for a session has been exeeded.
 */
function CheckPageViews() {
	global $MAX_VIEWS, $MAX_VIEW_TIME, $gm_lang;
	
	if ($MAX_VIEW_TIME == 0) return;
	
	if ((!isset($_SESSION["pageviews"])) || (time() - $_SESSION["pageviews"]["time"] > $MAX_VIEW_TIME)) {
		if (isset($_SESSION["pageviews"])) {
			$str = "Max pageview counter reset: max reached was ".$_SESSION["pageviews"]["number"];
			WriteToLog($str, "I", "S");
		}
		$_SESSION["pageviews"]["time"] = time();
		$_SESSION["pageviews"]["number"] = 0;
	}
	
	$_SESSION["pageviews"]["number"]++;
	
	if ($_SESSION["pageviews"]["number"] > $MAX_VIEWS) {
		$time = time() - $_SESSION["pageviews"]["time"];
		print $gm_lang["maxviews_exceeded"];
		$str = "Maximum number of pageviews exceeded after ".$time." seconds.";
		WriteToLog($str, "W", "S");
		exit;
	}
	return;
}	

/**
 * Get the next available xref
 *
 * <Long description of your function. 
 * What does it do?
 * How does it work?
 * All that goes into the long description>
 *
 * @todo		Fix usage of $FILE, this is a reserved name
 * @author	Genmod Development Team
 * @param		string	$type		The type of xref to retrieve
 * @return 	string	The new xref that was found
 */

function get_new_xref($type='INDI') {
	global $SOURCE_ID_PREFIX, $REPO_ID_PREFIX, $changes, $GEDCOM, $TBLPREFIX, $GEDCOMS;
	global $MEDIA_ID_PREFIX, $FAM_ID_PREFIX, $GEDCOM_ID_PREFIX, $FILE;
	
	
	if (isset($FILE) && !is_array($FILE)) $gedid = $GEDCOMS[$FILE]["id"];
	else $gedid = $GEDCOMS[$GEDCOM]["id"];
	
	switch ($type) {
		case "INDI":
			if (isset($changes["INDI"])) $sqlc = "select max(cast(substring(ch_gid,".(strlen($GEDCOM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$GEDCOM_ID_PREFIX."%'";
			$sql = "select max(cast(substring(i_rin,".(strlen($GEDCOM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."individuals where i_file = '".$gedid."'";
			break;
		case "FAM":
			if (isset($changes["FAM"])) $sqlc = "select max(cast(substring(ch_gid,".(strlen($FAM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$FAM_ID_PREFIX."%'";
			$sql = "select max(cast(substring(f_id,".(strlen($FAM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."families where f_file = '".$gedid."'";
			break;
		case "OBJE":
			if (isset($changes["OBJE"])) $sqlc = "select max(cast(substring(ch_gid,".(strlen($MEDIA_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$MEDIA_ID_PREFIX."%'";
			$sql = "select max(cast(substring(m_media,".(strlen($MEDIA_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."media where m_gedfile = '".$gedid."'";
			break;
		case "SOUR":
			if (isset($changes["SOUR"])) $sqlc = "select max(cast(substring(ch_gid,".(strlen($SOURCE_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$SOURCE_ID_PREFIX."%'";
			$sql = "select max(cast(substring(s_id,".(strlen($SOURCE_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."sources where s_file = '".$gedid."'";
			break;
		case "REPO":
			if (isset($changes["REPO"])) $sqlc = "select max(cast(substring(ch_gid,".(strlen($REPO_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$REPO_ID_PREFIX."%'";
			$sql = "select max(cast(substring(o_id,".(strlen($REPO_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."other where o_file = '".$gedid."' and o_id = '".$REPO_ID_PREFIX."'";
			break;
		case "CHANGE":
			$sql = "select max(ch_cid) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."'";
			break;
	}
	$res = dbquery($sql);
	$num = $res->fetchRow();
	list($num) = $num;
	
	// NOTE: Query from the change table
	if (isset($sqlc)) {
		$res = dbquery($sqlc);
		$numc = $res->fetchRow();
		list($numc) = $numc;
		if ($numc > $num) $num = $numc;
	}
	
	// NOTE: Increase the number with one
	$num++;
	
	// NOTE: Determine prefix needed
	if ($type == "INDI") $prefix = $GEDCOM_ID_PREFIX;
	else if ($type == "FAM") $prefix = $FAM_ID_PREFIX;
	else if ($type == "OBJE") $prefix = $MEDIA_ID_PREFIX;
	else if ($type == "SOUR") $prefix = $SOURCE_ID_PREFIX;
	else if ($type == "REPO") $prefix = $REPO_ID_PREFIX;
	else if ($type == "CHANGE") return $num;

	return $prefix.$num;;
}

/**
 * Determine type of ID
 *
 * This function takes any kind of ID and will determine whether it is
 * a person/family/source/repository ID. Return the type of ID.
 *
 * @author	Genmod Development Team
 * @param		string	$id		The ID to check
 * @return 	string	The type of ID
 */

function id_type($id) {
	global $SOURCE_ID_PREFIX, $REPO_ID_PREFIX, $MEDIA_ID_PREFIX, $FAM_ID_PREFIX, $GEDCOM_ID_PREFIX;
	
	// NOTE: Set length for the ID's
	$indi_length = strlen($GEDCOM_ID_PREFIX);
	$fam_length = strlen($FAM_ID_PREFIX);
	$source_length = strlen($SOURCE_ID_PREFIX);
	$repo_length = strlen($REPO_ID_PREFIX);
	$media_length = strlen($MEDIA_ID_PREFIX);
	
	// NOTE: Check for individual ID
	if (substr($id, 0, $indi_length) == $GEDCOM_ID_PREFIX) return "INDI";
	else if (substr($id, 0, $fam_length) == $FAM_ID_PREFIX) return "FAM";
	else if (substr($id, 0, $source_length) == $SOURCE_ID_PREFIX) return "SOUR";
	else if (substr($id, 0, $repo_length) == $REPO_ID_PREFIX) return "REPO";
	else if (substr($id, 0, $media_length) == $MEDIA_ID_PREFIX) return "OBJE";
	else return "";
}

/**
 * Read the Genmod News from the Genmod webserver
 *
 * The function reads the newsfile from the Genmod
 * webserver and stores the data in an array.
 * The array is returned and the data displayed on the admin page.
 * News is fetched per session and stored in the session data.
 * If no news is present in the newsfile on the server, nothing is displayed.
 * If the newsfile cannot be opened, an error message is displayed.
 * News format:
 * [Item]
 * [Date]mmm dd yyyy[/Date]
 * [Type]<Normal|Urgent>[/Type]
 * [Header]News header[/Header]
 * [Text]News text[/Text]
 * [/Item]
 *
 * @author	Genmod Development Team
 * @return	array	Array with news items
 */
function GetNewsItems() {
	global $NEWS_TYPE, $PROXY_ADDRESS, $PROXY_PORT;

	// -- If the news is already retrieved, get it from the session data.
	if(isset($_SESSION["gmnews"])) return $_SESSION["gmnews"];

	// -- Retrieve the news from the website
	$gmnews = array();
	if (!empty($PROXY_ADDRESS) && !empty($PROXY_PORT)) {
		$num = "(\\d|[1-9]\\d|1\\d\\d|2[0-4]\\d|25[0-5])";
		if (!preg_match("/^$num\\.$num\\.$num\\.$num$/", $PROXY_ADDRESS)) $ip = gethostbyname($PROXY_ADDRESS);
		else $ip = $PROXY_ADDRESS;
		$handle = @fsockopen($ip, $PROXY_PORT);
		if ($handle!=false) {
			$com = "GET http://www.genmod.net/gmnews.txt HTTP/1.1\r\nAccept: */*\r\nAccept-Language: de-ch\r\nAccept-Encoding: gzip, deflate\r\nUser-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)\r\nHost: $PROXY_ADDRESS:$PROXY_PORT\r\nConnection: Keep-Alive\r\n\r\n";
			fputs($handle, $com);
			$txt = fread($handle, 65535);
			fclose($handle);
			$txt = substr($txt, strpos($txt, "\r\n\r\n") + 4);
		}
	}
	else {
		@ini_set('user_agent','MSIE 4\.0b2;'); // force a HTTP/1.0 request
		@ini_set('default_socket_timeout', '5'); // timeout
		$handle = @fopen("http://www.genmod.net/gmnews.txt", "r");
		if ($handle!=false) {
			$txt = fread($handle, 65535);
			fclose($handle);
		}
	}
	if ($handle != false) {
		$txt = preg_replace("/[\r\n]/", "", $txt);
		$ct = preg_match_all("/\[Item](.+?)\[\/Item]/", $txt, $items);
		for ($i = 0; $i < $ct; $i++) {
			$item = array();
			$ct1 = preg_match("/\[Date](.+?)\[\/Date]/", $items[1][$i], $date);
			if ($ct1 > 0) $item["date"] = $date[1];
			else $item["date"] = "";
			$ct1 = preg_match("/\[Type](.+?)\[\/Type]/", $items[1][$i], $type);
			if ($ct1 > 0) $item["type"] = $type[1];
			else $item["type"] = "";
			$ct1 = preg_match("/\[Header](.+?)\[\/Header]/", $items[1][$i], $header);
			if ($ct1 > 0) $item["header"] = $header[1];
			else $item["header"] = "";
			$ct1 = preg_match("/\[Text](.+?)\[\/Text]/", $items[1][$i], $text);
			if ($ct1 > 0) $item["text"] = $text[1];
			else $item["text"] = "";
			if (($NEWS_TYPE == "Normal") || ($NEWS_TYPE == $item["type"])) $gmnews[] = $item;
		}
	}
	else {
		WriteToLog("News cannot be reached on Genmod News Server", "E");
		$item["date"] = "";
		$item["type"] = "Urgent";
		$item["header"] = "Warning: News cannot be retrieved";
		$item["text"] = "Genmod cannot retrieve the news from the news server. If this problem persist after next logons, please report this on the <a href=\"http://www.genmod.net\">Genmod Help forum</a>";
		$gmnews[] = $item;
	}
	// -- Store the news in the session data 
	$_SESSION["gmnews"] = $gmnews;
	return $gmnews;
}


function print_gedcom($ged, $convert, $remove, $zip, $privatize_export, $privatize_export_level, $gedname) {
	GLOBAL $GEDCOM, $GEDCOMS, $VERSION, $VERSION_RELEASE, $gm_lang, $CHARACTER_SET, $GM_BASE_DIRECTORY, $gm_username, $TBLPREFIX;
	if ($zip == "yes") {
		$gedout = fopen($gedname, "w");
	}

	if ($privatize_export == "yes") {
		create_export_user($privatize_export_level);
		if (isset($_SESSION)) {
			$_SESSION["org_user"] = $_SESSION["gm_user"];
			$_SESSION["gm_user"] = "export";
		}
		if (isset($HTTP_SESSION_VARS)) {
			$HTTP_SESSION_VARS["org_user"] = $HTTP_SESSION_VARS["gm_user"];
			$HTTP_SESSION_VARS["gm_user"] = "export";
		}
		$gm_username = getUserName();
	}

	$oldged = $GEDCOM;
	$GEDCOM = $ged;
	ReadGedcomConfig($GEDCOM);
	$head = find_gedcom_record("HEAD");
	if (!empty($head)) {
		$pos1 = strpos($head, "1 SOUR");
		if ($pos1!==false) {
			$pos2 = strpos($head, "\n1", $pos1+1);
			if ($pos2===false) $pos2 = strlen($head);
			$newhead = substr($head, 0, $pos1);
			$newhead .= substr($head, $pos2+1);
			$head = $newhead;
		}
		$pos1 = strpos($head, "1 DATE ");
		if ($pos1!=false) {
			$pos2 = strpos($head, "\n1", $pos1+1);
			if ($pos2===false) {
				$head = substr($head, 0, $pos1);
			}
			else {
				$head = substr($head, 0, $pos1).substr($head, $pos2+1);
			}
		}
		$head = trim($head);
		$head .= "\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS $VERSION $VERSION_RELEASE\r\n";
		$head .= "1 DATE ".date("j M Y")."\r\n";
		$head .= "2 TIME ".date("h:i:s")."\r\n";
		if (strstr($head, "1 PLAC")===false) {
			$head .= "1 PLAC\r\n2 FORM ".$gm_lang["default_form"]."\r\n";
		}
	}
	else {
		$head = "0 HEAD\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS $VERSION $VERSION_RELEASE\r\n1 DEST DISKETTE\r\n1 DATE ".date("j M Y")."\r\n2 TIME ".date("h:i:s")."\r\n";
		$head .= "1 GEDC\r\n2 VERS 5.5\r\n2 FORM LINEAGE-LINKED\r\n1 CHAR $CHARACTER_SET\r\n1 PLAC\r\n2 FORM ".$gm_lang["default_form"]."\r\n";
	}
	if ($convert=="yes") {
		$head = preg_replace("/UTF-8/", "ANSI", $head);
		$head = utf8_decode($head);
	}
	$head = remove_custom_tags($head, $remove);
	$head = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $head);
	if ($zip == "yes") fwrite($gedout, $head);
	else print $head;

	$sql = "SELECT i_gedcom FROM ".$TBLPREFIX."individuals WHERE i_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY CAST(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(i_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = mysql_query($sql);
	if ($res) {
		while($row = mysql_fetch_row($res)){
			$rec = trim($row[0])."\r\n";
			$rec = remove_custom_tags($rec, $remove);
			if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
			if ($convert=="yes") $rec = utf8_decode($rec);
			if ($zip == "yes") fwrite($gedout, $rec);
			else print $rec;
		}
		mysql_free_result($res);
	}
	
	$sql = "SELECT f_gedcom FROM ".$TBLPREFIX."families WHERE f_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(f_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = mysql_query($sql);
	if ($res) {
		while($row = mysql_fetch_row($res)){
			$rec = trim($row[0])."\r\n";
			$rec = remove_custom_tags($rec, $remove);
			if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
			if ($convert=="yes") $rec = utf8_decode($rec);
			if ($zip == "yes") fwrite($gedout, $rec);
			else print $rec;
		}
		mysql_free_result($res);
	}

	$sql = "SELECT s_gedcom FROM ".$TBLPREFIX."sources WHERE s_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(s_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = mysql_query($sql);
	if ($res) {
		while($row = mysql_fetch_row($res)){
			$rec = trim($row[0])."\r\n";
			$rec = remove_custom_tags($rec, $remove);
			if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
			if ($convert=="yes") $rec = utf8_decode($rec);
			if ($zip == "yes") fwrite($gedout, $rec);
			else print $rec;
		}
		mysql_free_result($res);
	}
	
	$sql = "SELECT m_gedrec FROM ".$TBLPREFIX."media WHERE m_gedfile=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(m_media),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = mysql_query($sql);
	if ($res) {
		while($row = mysql_fetch_row($res)){
			$rec = trim($row[0])."\r\n";
			$rec = remove_custom_tags($rec, $remove);
			if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
			if ($convert=="yes") $rec = utf8_decode($rec);
			if ($zip == "yes") fwrite($gedout, $rec);
			else print $rec;
		}
		mysql_free_result($res);
	}

	$sql = "SELECT o_gedcom, o_type FROM ".$TBLPREFIX."other WHERE o_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(o_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = mysql_query($sql);
	if ($res) {
		while($row = mysql_fetch_row($res)){
			$rec = trim($row[0])."\r\n";
			$key = $row[1];
			if (($key!="HEAD")&&($key!="TRLR")) {
				$rec = remove_custom_tags($rec, $remove);
				if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
				if ($convert=="yes") $rec = utf8_decode($rec);
				if ($zip == "yes") fwrite($gedout, $rec);
				else print $rec;
			}
		}
		mysql_free_result($res);
	}

	if ($zip == "yes") fwrite($gedout, "0 TRLR\r\n");
	else print "0 TRLR\r\n";
	
	if ($privatize_export == "yes") {
		if (isset($_SESSION)) {
			$_SESSION["gm_user"] = $_SESSION["org_user"];
		}
		if (isset($HTTP_SESSION_VARS)) {
			$HTTP_SESSION_VARS["gm_user"] = $HTTP_SESSION_VARS["org_user"];
		}
		deleteuser("export");
		$gm_username = getUserName();
	}
	$GEDCOM = $oldged;
	ReadGedcomConfig($GEDCOM);
	if ($zip == "yes") {
		fclose($gedout);
	}
}

/**
 * Store CONFIG array
 *
 * this function will store the <var>$CONFIG_PARMS</var> array in the config.php
 * file.  The config.php file is parsed in session.php to create the system variables
 * with every page request.
 * @see session.php
 */
function store_config() {
	global $CONFIG_PARMS, $gm_lang, $configtext;

	//-- Determine which values must be written as false/true
	$boolean = array("DBPERSIST", "GM_STORE_MESSAGES", "GM_SIMPLE_MAIL", "USE_REGISTRATION_MODULE", "REQUIRE_ADMIN_AUTH_REGISTRATION", "ALLOW_USER_THEMES", "ALLOW_CHANGE_GEDCOM", "ALLOW_REMEMBER_ME", "CONFIGURED");
	
	//-- First lines
	$configtext = "<"."?php\n";
	$configtext .= "if (preg_match(\"/\Wconfig.php/\", \$_SERVER[\"SCRIPT_NAME\"])>0) {\n";
	$configtext .= "print \"Got your hand caught in the cookie jar.\";\n";
	$configtext .= "exit;\n";
	$configtext .= "}\n";
	$configtext .= "//--START SITE CONFIGURATIONS\n";
	$configtext .= "\$CONFIG_PARMS = array();\n";
	
	//-- Scroll through the site configs
	foreach($CONFIG_PARMS as $indexval => $CONFIG) {
		$configtext .= "\$CONFIG = array();\n";
		//-- Scroll through the site parms
		foreach($CONFIG as $key=>$conf) {
			//-- If boolean, add true or false
			if (in_array($key, $boolean)) {
				$configtext .= "\$CONFIG[\"".$key."\"] = ";
				if ($conf) $configtext .= "true;\n";
				else $configtext .= "false;\n";
			}
			//-- If not boolean, add the value in quotes
			else $configtext .= "\$CONFIG[\"".$key."\"] = \"".$conf."\";\n";
		}
		//-- add last line per config
		$configtext .= "\$CONFIG_PARMS[\"".$indexval."\"] = \$CONFIG;\n";
	}
	//-- Add last lines
	$configtext .= "require_once(\$GM_BASE_DIRECTORY.\"includes/session.php\")\n"."?".">";
	
	//-- Store the config file
	if (file_exists("config.php")) {
		if (file_exists("config.old") && file_is_writeable("config.old")) unlink("config.old");
		if (file_is_writeable("config.old")) copy("config.php", "config.old");
	}
	$fp = fopen("config.php", "wb");
	if (!$fp) {
		return false;
		}
	else {
		fwrite($fp, $configtext);
		fclose($fp);
		return true;
	}
}

/* This function returns a list of directories
*
*/
function get_dir_list($dirs) {
	$dirlist = array();
	if (!is_array($dirs)) $dirlist[] = $dirs;
	else $dirlist = $dirs;
	foreach ($dirs as $key=>$dir) {
		$d = @dir($dir);
		if (is_object($d)) {
			while (false !== ($entry = $d->read())) {
				if ($entry != ".." && $entry != ".") {
					$entry = $dir.$entry."/";
					if(is_dir($entry)) $dirlist = array_merge($dirlist, get_dir_list(array($entry)));
				}
			}
			$d->close();
		}
	}
	return $dirlist;
}

function storeEnglish($setup=false) {
	global $TBLPREFIX, $gm_username, $language_settings, $TOTAL_QUERIES;
	
	if (!$setup) {
		// Drop the table
		$sql = "DROP TABLE ".$TBLPREFIX."language";
		$TOTAL_QUERIES++;
		if (!$result = mysql_query($sql)) {
	          WriteToLog("Language table could not be dropped", "E", "S");
	          return false;
	     }
		
		// Add the table
		$sql = "CREATE TABLE ".$TBLPREFIX."language (
				  `lg_string` varchar(255) NOT NULL default '',
				  `lg_english` text NOT NULL,
				  `lg_spanish` text NOT NULL,
				  `lg_german` text NOT NULL,
				  `lg_french` text NOT NULL,
				  `lg_hebrew` text NOT NULL,
				  `lg_arabic` text NOT NULL,
				  `lg_czech` text NOT NULL,
				  `lg_danish` text NOT NULL,
				  `lg_greek` text NOT NULL,
				  `lg_finnish` text NOT NULL,
				  `lg_hungarian` text NOT NULL,
				  `lg_italian` text NOT NULL,
				  `lg_lithuanian` text NOT NULL,
				  `lg_dutch` text NOT NULL,
				  `lg_norwegian` text NOT NULL,
				  `lg_polish` text NOT NULL,
				  `lg_portuguese-br` text NOT NULL,
				  `lg_russian` text NOT NULL,
				  `lg_swedish` text NOT NULL,
				  `lg_turkish` text NOT NULL,
				  `lg_vietnamese` text NOT NULL,
				  `lg_chinese` text NOT NULL,
				  `lg_last_update_date` int(11) NOT NULL default '0',
				  `lg_last_update_by` varchar(255) NOT NULL default '',
				  UNIQUE KEY `lg_string` (`lg_string`)
				) ";
		$TOTAL_QUERIES++;
		if (!$result = mysql_query($sql)) {
	    WriteToLog("Language table could not be created", "E", "S");
	    return false;
	  }
		
		// Drop the table
		$sql = "DROP TABLE ".$TBLPREFIX."language_help";
		$TOTAL_QUERIES++;
		if (!$result = mysql_query($sql)) {
	    WriteToLog("Language help table could not be dropped", "E", "S");
	    return false;
	  }
		
		$sql = "CREATE TABLE ".$TBLPREFIX."language_help (
				  `lg_string` varchar(255) NOT NULL default '',
				  `lg_english` text NOT NULL,
				  `lg_spanish` text NOT NULL,
				  `lg_german` text NOT NULL,
				  `lg_french` text NOT NULL,
				  `lg_hebrew` text NOT NULL,
				  `lg_arabic` text NOT NULL,
				  `lg_czech` text NOT NULL,
				  `lg_danish` text NOT NULL,
				  `lg_greek` text NOT NULL,
				  `lg_finnish` text NOT NULL,
				  `lg_hungarian` text NOT NULL,
				  `lg_italian` text NOT NULL,
				  `lg_lithuanian` text NOT NULL,
				  `lg_dutch` text NOT NULL,
				  `lg_norwegian` text NOT NULL,
				  `lg_polish` text NOT NULL,
				  `lg_portuguese-br` text NOT NULL,
				  `lg_russian` text NOT NULL,
				  `lg_swedish` text NOT NULL,
				  `lg_turkish` text NOT NULL,
				  `lg_vietnamese` text NOT NULL,
				  `lg_chinese` text NOT NULL,
				  `lg_last_update_date` int(11) NOT NULL default '0',
				  `lg_last_update_by` varchar(255) NOT NULL default '',
				  UNIQUE KEY `lg_string` (`lg_string`)
				) ;";
		$TOTAL_QUERIES++;
		if (!$result = mysql_query($sql)) {
	    WriteToLog("Language help table could not be created", "E", "S");
	    return false;
	  }
  }
	
	// Load English so we can display the page
	loadEnglish();
	
	if (file_exists("languages/lang.en.txt")) {
		// NOTE: Import the English language into the database
		$lines = file("languages/lang.en.txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
	               $data[0] = substr(trim($data[0]), 1);
	               $data[1] = substr(trim($data[1]), 0, -1);
	               $sql = "INSERT INTO ".$TBLPREFIX."language (lg_string, lg_english, lg_last_update_date, lg_last_update_by) VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '".time()."', '".$gm_username."')";
	               $TOTAL_QUERIES++;
	               if (!$result = mysql_query($sql)) {
	                    WriteToLog("Could not add language string ".$line." for language English to table ", "W", "S");
	               }
		      }
		}
	}
	
	if (file_exists("languages/help_text.en.txt")) {
		// NOTE: Import the English language help into the database
		$lines = file("languages/help_text.en.txt");
		foreach ($lines as $key => $line) {
		  $data = preg_split("/\";\"/", $line, 2);
		  if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
		  $data[0] = substr(trim($data[0]), 1);
	   $data[1] = substr(trim($data[1]), 0, -1);
			$sql = "INSERT INTO ".$TBLPREFIX."language_help (lg_string, lg_english, lg_last_update_date, lg_last_update_by) VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '".time()."', '".$gm_username."')";
			$TOTAL_QUERIES++;
			if (!$result = mysql_query($sql)) {
	     WriteToLog("Could not add language help string ".$line." for language English to table ", "W", "S");
	   }
		}
		}
	}
	
	// Add all active languages
  foreach ($language_settings as $name => $value) {
    if ($value["gm_lang_use"] && $name != "english") {
      storeLanguage($name);
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
function storeLanguage($storelang) {
	global $TBLPREFIX, $gm_username, $language_settings, $TOTAL_QUERIES;

	if (file_exists("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) WriteToLog($line, "E", "S");
			else {
       		     $data[0] = substr(trim($data[0]), 1);
                    $data[1] = substr(trim($data[1]), 0, -1);
       			$sql = "UPDATE ".$TBLPREFIX."language SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
       			$TOTAL_QUERIES++;
       			if (!$result = mysql_query($sql)) {
                         WriteToLog("Could not add language string ".$line." for language ".$storelang." to table ", "W", "S");
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
                    $data[0] = substr(trim($data[0]), 1);
                    $data[1] = substr(trim($data[1]), 0, -1);
                    	$sql = "UPDATE ".$TBLPREFIX."language_help SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
                    	$TOTAL_QUERIES++;
                    	if (!$result = mysql_query($sql)) {
                              WriteToLog("Could not add language help string ".$line." for language ".$storelang." to table ", "W", "S");
                         }
               }
          }
     }
}
/**
 * Remove a language into the database
 *
 * The function removes a language from the database by dropping the column and
 * then create the column again           
 *
 * @author	Genmod Development Team
 * @param	     string	     $storelang	The name of the language to remove
 */
function removeLanguage($removelang) {
	global $TBLPREFIX, $TOTAL_QUERIES;
	
	if ($removelang != "english") {
		// Drop the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language DROP lg_".$removelang;
		$TOTAL_QUERIES++;
		$result = mysql_query($sql);
		
		// Add the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language ADD lg_".$removelang." TEXT NOT NULL";
		$TOTAL_QUERIES++;
		$result = mysql_query($sql);
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
 * @param	     boolean	     $return		Whether or not to return the values
 * @param	     string	     $help		Whether or not to load the help language 
 * @return 	array          The array with the loaded language
 */
function loadEnglish($return=false, $help=false) {
	global $gm_lang, $TBLPREFIX, $TOTAL_QUERIES, $CONFIGURED;
	
	$temp = array();
	if ($CONFIGURED) {
		$sql = "SELECT COUNT(*) as total FROM ".$TBLPREFIX."language";
		if ($help) $sql .= "_help";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		if ($res) $total_columns = mysql_fetch_assoc($res);
		else $total_columns["total"] = 0 ;
		if ($total_columns["total"] > 0) {
			$sql = "SELECT lg_string, lg_english FROM ".$TBLPREFIX."language";
			if ($help) $sql .= "_help";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			while ($row = mysql_fetch_assoc($res)) {
				if (!$return) $gm_lang[$row["lg_string"]] = $row["lg_english"];
				else $temp[$row["lg_string"]] = $row["lg_english"];
			}
		}
	}
	if (count($temp) == 0) {
		// Load the English language
		if (file_exists("languages/lang.en.txt")) {
			$lines = file("languages/lang.en.txt");
			foreach ($lines as $key => $line) {
                    $data = preg_split("/\";\"/", $line, 2);
                    $data[0] = substr(trim($data[0]), 1);
                    $data[1] = substr(trim($data[1]), 0, -1);
                    $gm_lang[$data[0]] = $data[1];
			}
		}
		
		// Load the configure help
		if (file_exists("languages/help_text.en.txt")) {
			$lines = file("languages/help_text.en.txt");
			foreach ($lines as $key => $line) {
                    $data = preg_split("/\";\"/", $line, 2);
                    $data[0] = substr(trim($data[0]), 1);
                    $data[1] = substr(trim($data[1]), 0, -1);
                    $gm_lang[$data[0]] = $data[1];
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
function loadLanguage($language, $return=false, $help=false) {
	global $gm_language, $gm_lang, $TBLPREFIX, $TOTAL_QUERIES, $CONFIGURED;
	
	if (isset($gm_language[$language]) && $CONFIGURED) {
		$temp = array();
		$sql = "SELECT `lg_string`, `lg_".$language."` FROM `".$TBLPREFIX."language";
		if ($help) $sql .= "_help";
		$sql .= "` WHERE `lg_".$language."` != ''";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		if ($res) {
			while ($row = mysql_fetch_assoc($res)) {
				if (!$return) $gm_lang[$row["lg_string"]] = $row["lg_".$language];
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
function getString($string, $language2) {
	global $TBLPREFIX, $TOTAL_QUERIES;
	
	$sql = "SELECT lg_".$language2.", lg_english FROM ".$TBLPREFIX."language";
	if (substr($string, -5) == "_help") $sql .= "_help";
	$sql .= " WHERE lg_string = '".$string."'";
	
	$res = mysql_query($sql);
	$row = mysql_fetch_assoc($res);
	$TOTAL_QUERIES++;
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

function writeString($string, $value, $language2) {
	global $TBLPREFIX, $TOTAL_QUERIES;
	
	$sql = "UPDATE ".$TBLPREFIX."language";
	if (substr($value, -5) == "_help") $sql .= "_help";
	$sql .= " SET lg_".$language2."= '".$string."' WHERE lg_string = '".$value."'";
	$TOTAL_QUERIES++;
	
	if ($res = mysql_query($sql)) return true;
	else return false;
}
// optional extra file
if (file_exists($GM_BASE_DIRECTORY . "functions.extra.php")) require $GM_BASE_DIRECTORY . "functions.extra.php";
?>
