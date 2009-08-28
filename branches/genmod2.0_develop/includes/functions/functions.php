<?php
/**
 * Core Functions that can be used by any page in GM
 *
 * The functions in this file are common to all GM pages and include date conversion
 * routines and sorting functions.
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
//require_once("date_class.php");
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
 * Get the current time in micro seconds
 *
 * Returns a timestamp for the current time in micro seconds
 * obtained from online documentation for the microtime() function
 * on php.net
 *
 * @return	float	Time in micro seconds
 */
function GetMicrotime(){
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
	foreach($GEDCOMS as $ged => $gedarray) {
		if ($gedarray["id"] == $ged_id) return $ged;
	}

	return $ged_id;
}

/**
 * get a databse id from its gedcom filename 
 * @param int $gedcom	The filename to get the gedcom database id for
 * @return string
 */
function get_id_from_gedcom($gedcom) {
	global $GEDCOMS;

	if (isset($GEDCOMS[$gedcom])) return $GEDCOMS[$gedcom]["id"];
	else return false;
}

/**
 * Check if a gedcom file is downloadable over the internet
 *
 * @author opus27
 * @param string $gedfile gedcom file
 * @return mixed 	$url if file is downloadable, false if not
 */
function CheckGedcomDownloadable($gedfile) {
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
function IsDeadId($pid) {
	global $indilist, $BUILDING_INDEX, $GEDCOM, $GEDCOMS, $GEDCOMID, $COMBIKEY;

	if (empty($pid)) return true;
	if ($COMBIKEY) $key = JoinKey($pid, $GEDCOMID);
	else $key = $pid;

	//-- if using indexes then first check the indi_isdead array
	if ((!$BUILDING_INDEX)&&(isset($indilist))) {
		//-- check if the person is already in the $indilist cache
		if (!isset($indilist[$key]["isdead"]) || $indilist[$key]["gedfile"]!=$GEDCOMS[$GEDCOM]['id']) {
			//-- load the individual into the cache by calling the FindPersonRecord function
			$gedrec = FindPersonRecord($pid);
			if (empty($gedrec)) return true;
		}
		if (isset($indilist[$key])) {
			if ($indilist[$key]["gedfile"]==$GEDCOMID) {
				if (!isset($indilist[$key]["isdead"])) $indilist[$key]["isdead"] = -1;
				if ($indilist[$key]["isdead"]==-1) {
					$indilist[$key]["isdead"] = UpdateIsDead($pid, $indilist[$key]);
				}
				return $indilist[$key]["isdead"];
			}
		}
	}
	return IsDead(FindPersonRecord($pid));
}

// This functions checks if an existing file is physically writeable
// The standard PHP function only checks for the R/O attribute and doesn't
// detect authorisation by ACL.
function FileIsWriteable($file) {
	$err_write = false;
	$handle = @fopen($file,"r+");
	if	($handle)	{
		$i = fclose($handle);
		$err_write = true;
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
function GmErrorHandler($errno, $errstr, $errfile, $errline) {
	global $LAST_ERROR, $ERROR_LEVEL;

	if ((error_reporting() > 0)&&($errno<2048)) {
		$LAST_ERROR = $errstr." in ".$errfile." on line ".$errline;
		if ($ERROR_LEVEL==0) return;
		if (stristr($errstr,"by reference")==true) return;
		$msg = "ERROR ".$errno.": ".$errstr."<br />";
		$logline = $msg."Error occurred on line ".$errline." of file ".basename($errfile)."<br />";
		$logline .= "Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />";
		//$msg .= "Error occurred on line ".$errline." of file ".basename($errfile)."<br />\n";
		print "\n<br />".$msg;
		WriteToLog("GmErrorHandler-> ".$logline, "E", "S");
		if (($errno<16)&&(function_exists("debug_backtrace"))&&(strstr($errstr, "headers already sent by")===false)) {
			$backtrace = array();
			$backtrace = debug_backtrace();
			$num = count($backtrace);
			if ($ERROR_LEVEL==1) $num = 1;
			for($i=0; $i<$num; $i++) {
				$logline .= $i;
				if ($i==0) $logline .= " Error occurred on ";
				else $logline .= " called from ";
				if (isset($backtrace[$i]["line"]) && isset($backtrace[$i]["file"])) $logline .= "line <b>".$backtrace[$i]["line"]."</b> of file <b>".basename($backtrace[$i]["file"])."</b>";
				if ($i<$num-1) $logline .= " in function <b>".$backtrace[$i+1]["function"]."</b>";
				$logline .= "<br />\n";
				print $logline;
			}
		}
		WriteToLog("GmErrorHandler-> ".$logline, "E", "S");
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
function StrrPos4($haystack, $needle) {
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
function GetSubRecord($level, $tag, $gedrec, $num=1) {
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
// This causes problems. If a level 2 is searched in a complete indirecord, it will return all up 
// to the next level 2 record, including the level 1 in between.
// This will find the nearest lower level.
		$plow = 99999;
		for ($L = $level; $L>0; $L--) {
			$p = strpos($gedrec, "\n$L", $pos1+1);
			if ($p !== false && $p < $plow) $plow = $p;
		}
		$pos2 = $plow;
// Below is the original code			
//		$pos2 = strpos($gedrec, "\n$level", $pos1+1);
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
				if ($newpos = StrrPos4($subrec, $lowtag)) {
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
function GetAllSubrecords($gedrec, $ignore="", $families=true, $sort=true, $ApplyPriv=true) {
	global $ASC, $IGNORE_FACTS, $IGNORE_YEAR;
	$repeats = array();

	$id = "";
	$type = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}
	$gt = preg_match("/0 @.+@ (\w+)/", $gedrec, $gmatch);
	if ($gt > 0) {
		$type = $gmatch[1];
	}
	if ($id == "" && $type == "") $gedrec = "\n".$gedrec;
	$prev_tags = array();
	$ct = preg_match_all("/\n1 (\w+)(.*)/", $gedrec, $match, PREG_SET_ORDER);
	for($i=0; $i<$ct; $i++) {
		$fact = trim($match[$i][1]);
		if (isset($prev_tags[$fact])) $prev_tags[$fact]++;
		else $prev_tags[$fact] = 1;
		if (strpos($ignore, $fact)===false) {
			if ($ApplyPriv && preg_match("/\d\sOBJE\s@(\w+)@/", $match[$i][0], $mmatch)) $dispmedialink = DisplayDetailsByID($mmatch[1], "OBJE", 1, true);
			else $dispmedialink = true;
			if ($ApplyPriv && preg_match("/\d\sSOUR\s@(\w+)@/", $match[$i][0], $mmatch)) $dispsourcelink = DisplayDetailsByID($mmatch[1], "SOUR", 1, true);
			else $dispsourcelink = true;
			if ($ApplyPriv && preg_match("/\d\sNOTE\s@(\w+)@/", $match[$i][0], $mmatch)) $dispnotelink = DisplayDetailsByID($mmatch[1], "NOTE", 1, true);
			else $dispnotelink = true;
			if (!$ApplyPriv || (showFact($fact, $id, $type) && showFactDetails($fact,$id) && $dispmedialink && $dispsourcelink && $dispnotelink)) {
				$subrec = GetSubRecord(1, "1 $fact", $gedrec, $prev_tags[$fact]);
				if (!$ApplyPriv || (!FactViewRestricted($id, $subrec) && !FactViewRestricted($id, $gedrec, 1))) {
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
			if (!is_array($families) || in_array($famid, $families)) {
			if (!$ApplyPriv || DisplayDetailsByID($famid, "FAM")) {
				$famrec = FindGedcomRecord($fmatch[$f][1]);
				$parents = FindParentsInRecord($famrec);
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
							$subrec = GetSubRecord(1, "1 $fact", $famrec, $prev_tags[$fact]);
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
		}
	}

	if ($sort) {
		$ASC = 0;
  		$IGNORE_FACTS = 0;
  		$IGNORE_YEAR = 0;
//		usort($repeats, "CompareFacts");
		SortFacts($repeats);
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
function GetGedcomValue($tag, $level, $gedrec, $truncate='', $convert=true) {
	global $SHOW_PEDIGREE_PLACES, $gm_lang;

	$tags = preg_split("/:/", $tag);

	$subrec = $gedrec;
	//print $level;
	foreach($tags as $indexval => $t) {
		$lastsubrec = $subrec;
		$subrec = GetSubRecord($level, "$level $t", $subrec);
		if (empty($subrec)) {
			if ($t=="TITL") {
				$subrec = GetSubRecord($level, "$level ABBR", $lastsubrec);
				if (!empty($subrec)) $t = "ABBR";
			}
			if (empty($subrec)) {
				if ($level>0) $level--;
				$subrec = GetSubRecord($level, "@ $t", $gedrec);
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
			$subrec = FindGedcomRecord($match[1]);
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
		if ($level!=0 || $t!="NOTE") $value .= GetCont($level+1, $subrec);
		$value = preg_replace("'\n'", "", $value);
		$value = preg_replace("'<br />'", "\n", $value);
		$value = trim($value);
		//-- if it is a date value then convert the date
		if ($convert && $t=="DATE") {
			$value = GetChangedDate($value);
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
			if ($value=="M") $value = GetFirstLetter($gm_lang["male"]);
			else if ($value=="F") $value = GetFirstLetter($gm_lang["female"]);
			else $value = GetFirstLetter($gm_lang["unknown"]);
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
function GetCont($nlevel, $nrec) {
	global $WORD_WRAPPED_NOTES;
	$text = "";
	$tt = preg_match_all("/$nlevel CON[CT](.*)(?:\r\n|\r|\n)/", $nrec, $cmatch, PREG_SET_ORDER);
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

function MakeCont($newged, $newline) {
	global $WORD_WRAPPED_NOTES;
	
	$newged = rtrim($newged)." ";
	$clevel = substr($newged, 0, 1) + 1;
	$newlines = preg_split("/\r?\n/", $newline);
	for($k=0; $k<count($newlines); $k++) {
		if ($k>0) $newlines[$k] = $clevel." CONT ".$newlines[$k];
		if (strlen($newlines[$k])>255) {
			while(strlen($newlines[$k])>255) {
				for ($ch = 255;1;$ch--) {
					if (substr($newlines[$k],$ch-1,1) != " ") break;
				}
				$str = substr($newlines[$k], 0, $ch);
				$newged .= $str."\r\n";
				$newlines[$k] = substr($newlines[$k], $ch);
				$newlines[$k] = $clevel." CONC ".$newlines[$k];
			}
			$newged .= trim($newlines[$k])."\r\n";
		}
		else {
			$newged .= trim($newlines[$k])."\r\n";
		}
	}
	return $newged;
}

/**
 * find the parents in a family
 *
 * find and return a two element array containing the parents of the given family record
 * @author Genmod Development Team
 * @param string $famid the gedcom xref id for the family
 * @return array returns a two element array with indexes HUSB and WIFE for the parent ids
 */
function FindParents($famid) {
	global $gm_lang, $gm_username, $GEDCOM, $show_changes, $Users;

	$famrec = FindFamilyRecord($famid);
	if (empty($famrec)) {
		if ($Users->userCanEdit($gm_username)) {
			$famrec = FindGedcomRecord($famid);
			if (empty($famrec)) {
				if ($show_changes && GetChangeData(true, $famid, true, "", "FAM")) {
					$f = GetChangeData(false, $famid, true, "gedlines", "FAM");
					$famrec = $f[$GEDCOM][$famid];
				}
				else return false;
			}
		}
		else return false;
	}
	return FindParentsInRecord($famrec);
}

/**
 * find the parents in a family record
 *
 * find and return a two element array containing the parents of the given family record
 * @author Genmod Development Team
 * @param string $famrec the gedcom record of the family to search in
 * @return array returns a two element array with indexes HUSB and WIFE for the parent ids
 */
function FindParentsInRecord($famrec) {
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
 * @author Genmod Development Team
 * @param string $famid the gedcom xref id for the family
 * @param string $me	an xref id of a child to ignore, useful when you want to get a person's
 * siblings but do want to include them as well
 * @return array
 */
function FindChildren($famid, $me='') {
	global $gm_lang, $gm_username, $Users;

	$famrec = FindFamilyRecord($famid);
	if (empty($famrec)) {
		if ($Users->userCanEdit($gm_username)) {
			$famrec = FindGedcomRecord($famid);
			if (empty($famrec)) return false;
		}
		else return false;
	}
	return FindChildrenInRecord($famrec);
}

/**
 * find the children in a family record
 *
 * find and return an array containing the children of the given family record
 * @author Genmod Development Team
 * @param string $famrec the gedcom record of the family to search in
 * @param string $me	an xref id of a child to ignore, useful when you want to get a person's
 * siblings but do want to include them as well
 * @return array
 */
function FindChildrenInRecord($famrec, $me='') {
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
 * Retrieve all the ID's where the person is a child from the individual_child table
 *
 * @param string $pid the gedcom xref id for the person to look in
 * @return array array of family ids
 */
function FindFamilyIds($pid, $indirec="", $newfams = false) {
	global $TBLPREFIX, $GEDCOMID, $show_changes, $GEDCOM, $Users;
	
	$resultarray = array();
	if (empty($pid)) return $resultarray;
	
	// We must get the families from the gedcom record to preserve the order. 
	$gedrec = FindGedcomRecord($pid);
	if ($newfams && $Users->UserCanEdit($Users->GetUserName()) && $show_changes && GetChangeData(true, $pid, true, "", "")) {
		$rec = GetChangeData(false, $pid, true, "gedlines", "");
		$gedrec = $rec[$GEDCOM][$pid];
	}
	$ct = preg_match_all("/1\s+FAMC\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
	if ($ct>0) {
		$i = 1;
		foreach($fmatch as $key => $value) {
			$famcrec = GetSubRecord(1, "1 FAMC", $gedrec, $i);
			$ct = preg_match("/2\s+_PRIMARY\s(.+)/", $famcrec, $pmatch);
			if ($ct>0) $prim = trim($pmatch[1]);
			else $prim = "";
			$ct = preg_match("/2\s+PEDI\s+(adopted|birth|foster|sealing)/", $famcrec, $pmatch);
			$ped = "";
			if ($ct>0) $ped = trim($pmatch[1]);
			if ($ped == "birth") $ped = "";
			$ct = preg_match("/2\s+STAT\s+(challenged|proven|disproven)/", $famcrec, $pmatch);
			$stat = "";
			if ($ct>0) $stat = trim($pmatch[1]);
			$resultarray[] = array("famid"=>$value[1], "primary"=>$prim, "relation"=>$ped, "status"=>$stat);
			$i++;
		}
	}
	return $resultarray;
}

/**
 * Find all spouse family ids
 *
 * Retrieve all the ID's where the person is a spouse from the individual_spouse table
 *
 * @param string $pid the gedcom xref id for the person to look in
 * @return array array of family ids
 */
function FindSfamilyIds($pid, $newfams = false) {
	global $TBLPREFIX, $GEDCOMID, $GEDCOM, $show_changes, $Users;
	
	$resultarray = array();
	if (empty($pid)) return $resultarray;
//	$sql = "SELECT family_id FROM ".$TBLPREFIX."individual_spouse WHERE pid = '".$pid."' AND gedfile = ".$GEDCOMID;
//	$res = NewQuery($sql);
//	if (!$res) return array();
//	else {
//		while ($row = $res->FetchRow()) {
//			$resultarray[] = $row[0];
//		}
//	}
	// We must get the families from the gedcom record to preserve the order. 
	$gedrec = FindGedcomRecord($pid);
	if ($newfams && $Users->UserCanEdit($Users->GetUserName()) && $show_changes && GetChangeData(true, $pid, true, "", "")) {
		$rec = GetChangeData(false, $pid, true, "gedlines", "");
		$gedrec = $rec[$GEDCOM][$pid];
	}
	$ct = preg_match_all("/1\s+FAMS\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
	if ($ct>0) {
		foreach($fmatch as $key => $value) {
			$resultarray[] = array("famid"=>$value[1]);
		}
	}
	return $resultarray;
}

function FindPrimaryFamilyId($pid, $indirec="", $newfams=false) {
	
    $resultarray = array();
    $famids = FindFamilyIds($pid,$indirec,$newfams);
    if (count($famids)>1) {
        $priority = array();
        foreach ($famids as $indexval => $ffamid) {
            if (!isset($priority["first"])) $priority["first"]=$indexval;
            $priority["last"]=$indexval;
            if ($ffamid["primary"]=='Y') {
				if (!isset($priority["primary"])) $priority["primary"]=$indexval;
            }

            $relation = $ffamid["relation"];
            switch ($relation) {
            case "adopted":
            case "foster": // Sometimes called "guardian"
            case "sealing":
                // nothing to do
                break;
            default: // Should be "". Sometimes called "birth","biological","challenged","disproved"
                $relation = "birth";
                break;
            }
            // in the future, we could use $ffamid["stat"]
            // to further prioritize the family relation:
            // "challenged", "disproven", ""/"proven"

            // only store the first occurance of this type of family
            if (!isset($priority[$relation])) $priority[$relation]=$indexval;
        }

        // get the actual family array according to the following priority
        // at least one of these will get some results.
        if (isset($priority["primary"])) $resultarray[]=$famids[$priority["primary"]];
        else if (isset($priority["birth"])) $resultarray[]=$famids[$priority["birth"]];
        else if (isset($priority["adopted"])) $resultarray[]=$famids[$priority["adopted"]];
        else if (isset($priority["foster"])) $resultarray[]=$famids[$priority["foster"]];
        else if (isset($priority["sealing"])) $resultarray[]=$famids[$priority["sealing"]];
        else if (isset($priority["first"])) $resultarray[]=$famids[$priority["first"]];
        else if (isset($priority["last"])) $resultarray[]=$famids[$priority["last"]];
  		return $resultarray;
    }
    else return $famids;
}

function CleanupTagsY($irec) {
	$cleanup_facts = array("ANUL","CENS","DIVF","ENGA","MARB","MARC","MARL","MARS","ADOP","DSCR","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI");
	
	// Removed MARR, CHR, BIRT, DEAT and DIV which are allowed to have "Y", but only if no DATE and PLAC are present
	// DIV is not mentioned in the gedcom standard, but DIV Y is supported because PAF (!) uses it.
	// Genmod also supports BURI Y and CREM Y
	$canhavey_facts = array("MARR","DIV","BIRT","DEAT","CHR","BURI","CREM"); 

	$subs = GetAllSubrecords($irec, "", false, false, false);
	foreach ($subs as $key => $subrec) {
		$oldsub = $subrec;
		$ft = preg_match("/1\s(\w+)/", $subrec, $match);
		$sfact = trim($match[1]);
		if (in_array($sfact, $cleanup_facts) || (in_array($sfact, $canhavey_facts) && stristr($subrec, "1 ".$sfact." Y") && (stristr($subrec, "2 DATE") || stristr($subrec, "2 PLAC")))) {
			$srchstr = "/1\s".$sfact."\sY\r\n2/";
			$replstr = "1 ".$sfact."\r\n2";
			$srchstr2 = "/1\s".$sfact."(.{0,1})\r\n2/";
			$srchstr = "/1\s".$sfact."\sY\r\n2/";
			$srchstr3 = "/1\s".$sfact."\sY\r\n1/";
			$subrec = preg_replace($srchstr,$replstr,$subrec);
			if (preg_match($srchstr2,$subrec)){
				$subrec = preg_replace($srchstr3,"1",$subrec);
			}
			$irec = str_replace($oldsub, $subrec, $irec); 
		}
		else {
			if (in_array($sfact, $canhavey_facts) && !stristr($subrec, $sfact." Y") && !stristr($irec, "2 DATE") && !stristr($irec, "2 PLAC")) {
				$subrec = preg_replace("/1 ".$sfact."/", "1 ".$sfact." Y", $subrec);
				$irec = str_replace($oldsub, $subrec, $irec); 
			}
		}
	}
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
function FindHighlightedObject($pid, $indirec) {
	global $MEDIA_DIRECTORY, $GM_IMAGE_DIR, $GM_IMAGES, $MEDIA_EXTERNAL;
	global $TBLPREFIX, $GEDCOMID, $MediaFS;
	
	if (!showFactDetails("OBJE", $pid)) return false;
	$object = array();
	$media_ids = array();
		
	// NOTE: Find the media items for that person
	$sql = "select m_file, m_media, mm_gedrec, m_gedrec from ".$TBLPREFIX."media, ".$TBLPREFIX."media_mapping where mm_gid LIKE '".$pid."' AND m_gedfile = '".$GEDCOMID."' AND m_gedfile = mm_gedfile AND m_media = mm_media AND mm_gedrec NOT LIKE '%\_PRIM N%' AND mm_gedrec LIKE '1 OBJE%' ORDER BY mm_order";
	$res = NewQuery($sql);
	while ($row = $res->FetchAssoc()) {
		if (DisplayDetailsByID($row["m_media"], "OBJE", 1, true)) $media_ids[] = $row;
	}
	$ids = count($media_ids);
	if ($ids==0) return false;
	
	// We have the candidates that can be displayed. Check for a _PRIM Y in the link record.
	// On the fly we also check the _THUM tag, first in the link, then in the media record.
	foreach($media_ids as $key => $media) {
		$prim = GetGedcomValue("_PRIM", 2, $media["mm_gedrec"]);
		if ($prim == "Y") {
			$primfile = $media["m_file"];
			$thum = GetGedcomValue("_THUM", 2, $media["mm_gedrec"]);
			if (empty($thum)) $thum = GetGedcomValue("_THUM", 1, $media["m_gedrec"]);
			$id = $media["m_media"];
			break;
		}
	}
	
	// Nothing in the link records. Now check the media records for "defaults".
	// On the fly we also check the _THUM tag, first in the link, then in the media record.
	if (!isset($primfile)) {
		foreach($media_ids as $key => $media) {
			$prim = GetGedcomValue("_PRIM", 1, $media["m_gedrec"]);
			if ($prim == "Y") {
				$primfile = $media["m_file"];
				$thum = GetGedcomValue("_THUM", 2, $media["mm_gedrec"]);
				if (empty($thum)) $thum = GetGedcomValue("_THUM", 1, $media["m_gedrec"]);
				$id = $media["m_media"];
				break;
			}
		}
	}
	// If a PRIM Y is found nowhere, we just take the first link.
	if (!isset($primfile)) {
		$primfile = $media_ids[0]["m_file"];
		$thum = GetGedcomValue("_THUM", 2, $media_ids[0]["mm_gedrec"]);
		if (empty($thum)) $thum = GetGedcomValue("_THUM", 1, $media_ids[0]["m_gedrec"]);
		$id = $media_ids[0]["m_media"];
	}
		 
	$object["use_thum"] = $thum;
	$object["file"] = $MediaFS->CheckMediaDepth($primfile);
	$object["thumb"] = $MediaFS->ThumbnailFile($MEDIA_DIRECTORY.RelativePathFile($object["file"]));
	$object["id"] = $id;
	return $object;
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
function GetRelationship($pid1, $pid2, $followspouse=true, $maxlength=0, $ignore_cache=false, $path_to_find=0) {
	global $TIME_LIMIT, $start_time, $gm_lang, $NODE_CACHE, $NODE_CACHE_LENGTH, $USE_RELATIONSHIP_PRIVACY, $GEDCOM, $gm_username, $show_changes, $Users;

	$pid1 = strtoupper($pid1);
	$pid2 = strtoupper($pid2);
	$indirec = FindPersonRecord($pid2);
	if ($show_changes) {
		if (GetChangeData(true, $pid2, true)) {
			$rec = GetChangeData(false, $pid2, true, "gedlines");
			$indirec = $rec[$GEDCOM][$pid2];
		}
	}
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
			$famrec = FindFamilyRecord($fam);
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
	$birthrec = GetSubRecord(1, "1 BIRT", $indirec);
	$byear2 = -1;
	if ($birthrec!==false) {
		$dct = preg_match("/2 DATE .*(\d\d\d\d)/", $birthrec, $match);
		if ($dct>0) $byear2 = $match[1];
	}
	if ($byear2==-1) {
		$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
		for($j=0; $j<$numfams; $j++) {
			// Get the family record
			$famrec = FindFamilyRecord($fmatch[$j][1]);
			if ($show_changes) {
				if (GetChangeData(true, $fmatch[$j][1], true)) {
					$rec = GetChangeData(false, $fmatch[$j][1], true, "gedlines");
					$famrec = $rec[$GEDCOM][$fmatch[$j][1]];
				}
			}

			// Get the set of children
			$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $cmatch, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				// Get each child's record
				$childrec = FindPersonRecord($cmatch[$i][1]);
				if ($show_changes) {
					if (GetChangeData(true, $cmatch[$i][1], true)) {
						$rec = GetChangeData(false, $cmatch[$i][1], true, "gedlines");
						$childrec = $rec[$GEDCOM][$cmatch[$i][1]];
					}
				}
				$birthrec = GetSubRecord(1, "1 BIRT", $childrec);
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
		$end_time = GetMicrotime();
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
				$indirec = FindPersonRecord($node["pid"]);
				if ($show_changes) {
					if (GetChangeData(true, $node["pid"], true)) {
						$rec = GetChangeData(false, $node["pid"], true, "gedlines");
						$indirec = $rec[$GEDCOM][$node["pid"]];
					}
				}
				$byear1 = -1;
				$birthrec = GetSubRecord(1, "1 BIRT", $indirec);
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
					$famrec = FindFamilyRecord($fam);
					if ($show_changes) {
						if (GetChangeData(true, $fam, true)) {
							$rec = GetChangeData(false, $fam, true, "gedlines");
							$famrec = $rec[$GEDCOM][$fam];
						}
					}
					$parents = FindParentsInRecord($famrec);
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
					$famrec = FindFamilyRecord($fam);
					if ($show_changes) {
						if (GetChangeData(true, $fam, true)) {
							$rec = GetChangeData(false, $fam, true, "gedlines");
							$famrec = $rec[$GEDCOM][$fam];
						}
					}
					if ($followspouse) {
						$parents = FindParentsInRecord($famrec);
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
 * get theme names
 *
 * function to get the names of all of the themes as an array
 * it searches the themes directory and reads the name from the theme_name variable
 * in the theme.php file.
 * @return array and array of theme names and their corresponding directory
 */
function GetThemeNames() {
	$themes = array();
	$d = dir("themes");
	while (false !== ($entry = $d->read())) {
		if ($entry!="." && $entry!=".." && $entry!="CVS" && is_dir("themes/$entry")) {
			$theme = array();
			if (file_exists("themes/$entry/theme.php")) {
				$themefile = implode("", file("themes/$entry/theme.php"));
				$tt = preg_match("/theme_name\s+=\s+\"(.*)\";/", $themefile, $match);
				if ($tt>0) $themename = trim($match[1]);
				else $themename = "themes/$entry";
				$theme["name"] = $themename;
				$theme["dir"] = "themes/$entry/";
				$themes[] = $theme;
			}
		}
	}
	$d->close();
	uasort($themes, "ItemSort");
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
function GetCalendarFact($factrec, $action, $filterof, $pid, $filterev="all") {
	global $gm_lang, $year, $month, $day, $TEMPLE_CODES, $CALENDAR_FORMAT, $monthtonum, $TEXT_DIRECTION, $SHOW_PEDIGREE_PLACES, $caltype;
	global $CalYear, $currhYear, $USE_RTL_FUNCTIONS;
	
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
				if (defined("GM_FACT_".$factref)) $text .= constant("GM_FACT_".$factref);
				else $text .= $factref;
			}
			else $text .= constant("GM_FACT_".$fact);
		}
		else {
			if (defined("GM_FACT_".$fact)) $text .= constant("GM_FACT_".$fact);
			else $text .= $fact;
		}
//		if ($filterev!="all" && $filterev!=$fact && $filterev!=$factref) return "filter";

		if ($text!="") $text=PrintReady($text);

		$ct = preg_match("/\d DATE(.*)/", $factrec, $match);
		if ($ct>0) {
			$text .= " - <span class=\"date\">".GetDateUrl($match[1])."</span>";
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
					$text .= " (" . str_replace("#year_var#", ConvertNumber($age), $gm_lang["year_anniversary"]).")";
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
						$plactext = "";
						for($plevel=0; $plevel<$SHOW_PEDIGREE_PLACES; $plevel++) {
							if (!empty($plevels[$plevel])) {
								if ($plevel>0) $plactext .=", ";
								$plactext .= PrintReady($plevels[$plevel]);
							}
						}
						if (HasChinese($plactext)) $plactext .= PrintReady(" (".GetPinYin($plactext).")");
						$text .= PrintReady($plactext);
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
function ConvertNumber($num) {
	global $gm_lang, $LANGUAGE;

	if ($LANGUAGE == "chinese") {
		$numstr = "$num";
		$zhnum = "";
		//-- currently limited to numbers <10000
		if (strlen($numstr)>4) return $numstr;

		$ln = strlen($numstr);
		$numstr = strrev($numstr);
		for($i=0; $i<$ln; $i++) {
			if (!is_numeric($numstr[$i])) $zhnum = $numstr[$i].$zhnum;
			else {
				if (($i==1)&&($numstr{$i}!="0")) $zhnum = $gm_lang["10"].$zhnum;
				if (($i==2)&&($numstr{$i}!="0")) $zhnum = $gm_lang["100"].$zhnum;
				if (($i==3)&&($numstr{$i}!="0")) $zhnum = $gm_lang["1000"].$zhnum;
				if (($i!=1)||($numstr{$i}!=1)) $zhnum = $gm_lang[$numstr{$i}].$zhnum;
			}
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
function GmMail($mailto, $subject, $message, $from_name='', $from_mail='', $replyto='', $filenames='', $path='', $admincopy=false){
	global $HOME_SITE_TEXT,$mail_stylesheet;
	
/*	print "mailto: ".$mailto."<br />";
	print "subject: ".$subject."<br />";
	print "message: ".$message."<br />";
	print "from_name: ".$from_name."<br />";
	print "from_mail: ".$from_mail."<br />";
	print "replyto: ".$replyto."<br />";
	print "filenames: ".$filenames."<br />";
	print "path: ".$path."<br />";
	print "admincopy: ".$admincopy."<br />";
*/	
	// NOTE: Get the mail adres where it is sent from
	if (empty($from_mail)) {
		$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
		$from_mail = "genmod-noreply@".$host;
	}
	if (empty($replyto)) $replyto = $from_mail;
	
	// NOTE: Set the home site name as from mail
	if (empty($from_name)) $from_name = $HOME_SITE_TEXT;
	
	// NOTE: Check if we send HTML or plain text
	$html=true;
	
	$styles = file_get_contents($mail_stylesheet);
	
	if ($html) {
		$html_header = "<html>\r\n";
		$html_header .= "<head>\r\n";
		$html_header .= "<title>".$subject."</title>\r\n";
		$html_header .= "<style type=\"text/css\">";
		$html_header .= $styles;
		$html_header .= "</style>";
		$html_header .= "</head>\r\n";
		$html_header .= "<body>\r\n";
		
		$html_footer = "</body>\r\n";
		$html_footer .= "</html>\r\n";
		
		// NOTE: Set the correct linebreaks
		$message = preg_replace('/\r\n/', '<br />'.chr(13).chr(10), $message);
		$message = $html_header.$message.$html_footer;
	}
	
	require_once('includes/classes/sendmail.class.php');
	$sendmail = new SendMail($filenames, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message, $html, $admincopy);
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
function FilenameDecode($filename) {
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
function FilenameEncode($filename) {
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
function GetAlphabet(){
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
function GetReportList($force=false) {
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
		if (($entry!=".") && ($entry!="..") && ($entry!="CVS") && (strstr($entry, ".") == ".xml")) {
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
				$files[$file]["type"] = $report_array["type"];
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
function CleanInput($pid) {
	$pid = preg_replace("/[%?_]/", "", trim($pid));
	return $pid;
}

/**
 * get a quick-glance view of current LDS ordinances
 * @param string $indirec
 * @return string
 */
function GetLdsGlance($indirec) {
	$text = "";

	$ord = GetSubRecord(1, "1 BAPL", $indirec);
	if ($ord) $text .= "B";
	else $text .= "_";
	$ord = GetSubRecord(1, "1 ENDL", $indirec);
	if ($ord) $text .= "E";
	else $text .= "_";
	$found = false;
	$ct = preg_match_all("/1 FAMS @(.*)@/", $indirec, $match, PREG_SET_ORDER);
	for($i=0; $i<$ct; $i++) {
		$famrec = FindFamilyRecord($match[$i][1]);
		if ($famrec) {
			$ord = GetSubRecord(1, "1 SLGS", $famrec);
			if ($ord) {
				$found = true;
				break;
			}
		}
	}
	if ($found) $text .= "S";
	else $text .= "_";
	$ord = GetSubRecord(1, "1 SLGC", $indirec);
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

//		$ft = preg_match("/1 (\w+)(.*)/", $fact[1], $match);
//		if ($ft>0) {
		if ($fact->fact != "") {
//			$fact = trim($match[1]);
			$key = array_search($fact->fact, $uniquefacts);
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
function RemoveCustomTags($gedrec, $remove="no") {
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

function EmbedMM($gedrec) {

	$ct = preg_match_all("/\n(\d) OBJE @(.+)@/", $gedrec, $match);
	for ($i=1;$i<=$ct;$i++) {
		$mmid = $match[2][$i-1];
		$level = $match[1][$i-1];
		$mediarec = FindMediaRecord($mmid);
		$oldlevel = $mediarec[0];
		$mediarec = preg_replace("/\n(\d) /e", "'\n'.SumNums($1, $level).' '", $mediarec);
		$mediarec = preg_replace("/0 @.+@ OBJE\s*\r\n/", "", $mediarec);
		$mediarec = $level." OBJE\r\n".$mediarec."\r\n";
		$gedrec = preg_replace("/$level OBJE @$mmid@\s*/", $mediarec, $gedrec);
	}
	return $gedrec;
}

function EmbedNote($gedrec) {

	$ct = preg_match_all("/\n(\d) NOTE @(.+)@/", $gedrec, $match);
	for ($i=1;$i<=$ct;$i++) {
		$nid = $match[2][$i-1];
		$level = $match[1][$i-1];
		$noterec = FindGedcomRecord($nid);
		$oldlevel = $noterec[0];
		$noterec = preg_replace("/\n(\d) /e", "'\n'.SumNums($1, $level).' '", $noterec);
		$noterec = preg_replace("/^0 @.+@ NOTE/", $level." NOTE", $noterec);
		$gedrec = preg_replace("/$level NOTE @$nid@\s*/", $noterec, $gedrec);
	}
	return $gedrec;
}

function SumNums($val1, $val2) {
	return $val1 + $val2;
}

/**
 * find the name of the first GEDCOM file in a zipfile
 * @param string $zipfile	the path and filename
 * @param boolean $extract  true = extract and return filename, false = return filename
 * @return string		the path and filename of the gedcom file
 */
function GetGedFromZip($zipfile, $extract=true) {
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
function RunHooks($type, $params=array ())
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

function GetFileSize($bytes) {
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
function SplitKey($key, $type) {
	global $GEDCOM, $GEDCOMID;
	
	$p1 = strpos($key,"[");
	if ($p1 === false) $id = $key;
	else $id = substr($key,0,$p1);
	if ($type == "id") return $id;
	if ($p1 === false) {
		if ($type == "ged") return $GEDCOM;
		if ($type == "gedid") return $GEDCOMID;
	}
	$p2 = strpos($key,"]");
	$ged = substr($key,$p1+1,$p2-$p1-1);
	if ($type == "ged") return get_gedcom_from_id($ged);
	if ($type == "gedid") return $ged;
}

function JoinKey($key, $gedid) {
	if (strpos($key, "[") === false) return $key."[".$gedid."]";
	else print "Joining an already combined key: ".$key;
}

/**
 * array merge function for GM
 * the PHP array_merge function will reindex all numerical indexes
 * This function should only be used for associative arrays
 * @param array $array1
 * @param array $array2
 */
function GmArrayMerge($array1, $array2) {
	foreach($array2 as $key=>$value) {
		$array1[$key] = $value;
	}
	return $array1;
}

/**
 * function to build an URL querystring from GET or POST variables
 * @return string
 */
function GetQueryString($encode=false) {
	$qstring = "";
	if (!empty($_GET)) {
		foreach($_GET as $key => $value) {
			if($key != "view" && $key != 'html' && $key != "NEWLANGUAGE" && $key != "changelanguage") {
				$qstring .= $key."=".$value."&";
			}
		}
	}
	else {
		if (!empty($_POST)) {
			foreach($_POST as $key => $value) {
				if($key != "view" && $key != 'html' && $key != "NEWLANGUAGE" && $key != "changelanguage") {
					$qstring .= $key."=".$value."&";
				}
			}
		}
	}
	// Remove the trailing ampersand to prevent it from being duplicated
	$qstring = substr($qstring, 0, -1);
	if ($encode) return urlencode($qstring);
	else return $qstring;
}

//--- copied from reportpdf.php
function AddAncestors($pid, $children=false, $generations=-1) {
	global $list, $indilist, $genlist;

	$genlist = array($pid);
	$list[$pid]["generation"] = 1;
	while(count($genlist)>0) {
		$id = array_shift($genlist);
		$famids = FindPrimaryFamilyId($id);
		if (count($famids)>0) {
			$ffamid = $famids[0];
			$famid = $ffamid["famid"];
			if (DisplayDetailsByID($famid, "FAM")) {
				$parents = FindParents($famid);
				if (!empty($parents["HUSB"]) && (DisplayDetailsByID($parents["HUSB"]) || ShowLivingNameByID($parents["HUSB"]))) {
					FindPersonRecord($parents["HUSB"]);
					$list[$parents["HUSB"]] = $indilist[$parents["HUSB"]];
					$list[$parents["HUSB"]]["generation"] = $list[$id]["generation"]+1;
				}
				if (!empty($parents["WIFE"]) && (DisplayDetailsByID($parents["WIFE"]) || ShowLivingNameByID($parents["WIFE"]))) {
					FindPersonRecord($parents["WIFE"]);
					$list[$parents["WIFE"]] = $indilist[$parents["WIFE"]];
					$list[$parents["WIFE"]]["generation"] = $list[$id]["generation"]+1;
				}
				if ($generations == -1 || $list[$id]["generation"]+1 < $generations) {
					if (!empty($parents["HUSB"])) array_push($genlist, $parents["HUSB"]);
					if (!empty($parents["WIFE"])) array_push($genlist, $parents["WIFE"]);
				}
				if ($children) {
					$famrec = FindFamilyRecord($famid);
					if ($famrec) {
						$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
						for($i=0; $i<$num; $i++) {
							if (DisplayDetailsByID($smatch[$i][1]) || ShowLivingNameByID($smatch[$i][1])) {
								FindPersonRecord($smatch[$i][1]);
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
}

//--- copied from reportpdf.php
function AddDescendancy($pid, $parents=false, $generations=-1) {
	global $list, $indilist;

	if (!isset($list[$pid])) {
		FindPersonRecord($pid);
		$list[$pid] = $indilist[$pid];
	}
	if (!isset($list[$pid]["generation"])) {
		$list[$pid]["generation"] = 0;
	}
	$famids = FindSfamilyIds($pid);
	if (count($famids)>0) {
		foreach($famids as $indexval => $famid) {
			$famrec = FindFamilyRecord($famid["famid"]);
			if ($famrec && DisplayDetailsByID($famid["famid"], "FAM")) {
				if ($parents) {
					$parents = FindParentsInRecord($famrec);
					if (!empty($parents["HUSB"]) && (DisplayDetailsByID($parents["HUSB"]) || ShowLivingNameByID($parents["HUSB"]))) {
						FindPersonRecord($parents["HUSB"]);
						$list[$parents["HUSB"]] = $indilist[$parents["HUSB"]];
						if (isset($list[$pid]["generation"])) $list[$parents["HUSB"]]["generation"] = $list[$pid]["generation"]-1;
						else $list[$parents["HUSB"]]["generation"] = 1;
					}
					if (!empty($parents["WIFE"]) && (DisplayDetailsByID($parents["WIFE"]) || ShowLivingNameByID($parents["WIFE"]))) {
						FindPersonRecord($parents["WIFE"]);
						$list[$parents["WIFE"]] = $indilist[$parents["WIFE"]];
						if (isset($list[$pid]["generation"])) $list[$parents["WIFE"]]["generation"] = $list[$pid]["generation"]-1;
						else $list[$parents["HUSB"]]["generation"] = 1;
					}
				}
				$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
				for($i=0; $i<$num; $i++) {
					FindPersonRecord($smatch[$i][1]);
					$list[$smatch[$i][1]] = $indilist[$smatch[$i][1]];
					if (isset($list[$smatch[$i][1]]["generation"])) $list[$smatch[$smatch[$i][1]][1]]["generation"] = $list[$pid]["generation"]+1;
					else $list[$smatch[$i][1]]["generation"] = 2;
				}
				if($generations == -1 || $list[$pid]["generation"]+1 < $generations)
				{
					for($i=0; $i<$num; $i++) {
						AddDescendancy($smatch[$i][1], $parents, $generations);	// recurse on the childs family
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
	global $MAX_VIEWS, $MAX_VIEW_TIME, $MAX_VIEW_LOGLEVEL, $gm_lang;
	
	if ($MAX_VIEW_TIME == 0) return;
	
	if (in_array(basename($_SERVER["SCRIPT_NAME"]), array("useradmin", "showblob.php")) || substr(basename($_SERVER["SCRIPT_NAME"]), 0, 4) == "edit") return;
	
	if ((!isset($_SESSION["pageviews"])) || (time() - $_SESSION["pageviews"]["time"] > $MAX_VIEW_TIME)) {
		if (isset($_SESSION["pageviews"]) && $MAX_VIEW_LOGLEVEL == "2") {
			$str = "Max pageview counter reset: max reached was ".$_SESSION["pageviews"]["number"];
			WriteToLog("CheckPageViews-> ".$str, "I", "S");
		}
		$_SESSION["pageviews"]["time"] = time();
		$_SESSION["pageviews"]["number"] = 0;
		$_SESSION["pageviews"]["hadmsg"] = false;
	}
	
	$_SESSION["pageviews"]["number"]++;
	
	if ($_SESSION["pageviews"]["number"] > $MAX_VIEWS) {
		$time = time() - $_SESSION["pageviews"]["time"];
		print $gm_lang["maxviews_exceeded"];
		if (($MAX_VIEW_LOGLEVEL == "2" || $MAX_VIEW_LOGLEVEL == "1") && $_SESSION["pageviews"]["hadmsg"] == false) {
			$str = "CheckPageViews-> Maximum number of pageviews exceeded after ".$time." seconds.";
			WriteToLog($str, "W", "S");
			$_SESSION["pageviews"]["hadmsg"] = true;
		}
		exit;
	}
	return;
}	

// This function checks if the IP from which the user authenticated, is still the IP where the request comes from.
// It also checks if the current IP is in the exclude list
function CheckSessionIP() {
	global $EXCLUDE_HOSTS;
	
	if (isset($_SESSION['gm_user']) && !empty($_SESSION['gm_user'])) {
		if (isset($_SESSION['IP']) && !empty($_SESSION['IP'])) {
			if ($_SESSION['IP'] != $_SERVER['REMOTE_ADDR']) {
				$excluded = false;
				$lines = preg_split("/[;,]/", $EXCLUDE_HOSTS);
				$hostname = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
				foreach ($lines as $key => $line) {
					$line = trim($line);
//					print $line."<br />";
					if (!empty($line)) {
						// is it a hostname?
						if (preg_match("/[a-zA_Z]/", $line, $match)) {
							$host = strtolower(preg_replace("/\*/", ".*", $line));
							if (preg_match("/($host)/", strtolower($hostname), $match)) {
								$excluded = true;
								break;
							}
						}
						// Is it a single IP?
						if (preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $line, $match)) {
							if ($line == $_SERVER["REMOTE_ADDR"]) {
								$excluded = true;
								break;
							}
						}
						// Is it an IP-range?
						if (preg_match("/^([0-9]{1,3}\.){3}([0-9]{1,3}\-)([0-9]{1,3}\.){3}[0-9]{1,3}$/", $line, $match)) {
							$ips = split("-", $line);
							if (ip2long($_SERVER["REMOTE_ADDR"]) <= ip2long($ips[1]) && ip2long($_SERVER["REMOTE_ADDR"]) >= ip2long($ips[0])) {
								$excluded = true;
								break;
							}
						}
					}
				}
				if (!$excluded) {
					$string = "CheckSessionIP-> Intrusion detected on session for IP ".$_SESSION['IP']." by ".$_SERVER['REMOTE_ADDR'];
					WriteToLog($string, "W", "S");
					HandleIntrusion($string);
					exit;
				}
			}
		}
	}
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

function GetNewXref($type='INDI') {
	global $SOURCE_ID_PREFIX, $REPO_ID_PREFIX, $changes, $GEDCOM, $TBLPREFIX, $GEDCOMS;
	global $MEDIA_ID_PREFIX, $FAM_ID_PREFIX, $GEDCOM_ID_PREFIX, $FILE, $GEDCOMID, $NOTE_ID_PREFIX;
	
	
	if (isset($FILE) && !is_array($FILE)) $gedid = $GEDCOMS[$FILE]["id"];
	else $gedid = $GEDCOMID;

	switch ($type) {
		case "INDI":
			$sqlc = "select max(cast(substring(ch_gid,".(strlen($GEDCOM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$GEDCOM_ID_PREFIX."%'";
			$sql = "select max(cast(substring(i_rin,".(strlen($GEDCOM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."individuals where i_file = '".$gedid."'";
			break;
		case "FAM":
			$sqlc = "select max(cast(substring(ch_gid,".(strlen($FAM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$FAM_ID_PREFIX."%'";
			$sql = "select max(cast(substring(f_id,".(strlen($FAM_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."families where f_file = '".$gedid."'";
			break;
		case "OBJE":
			$sqlc = "select max(cast(substring(ch_gid,".(strlen($MEDIA_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$MEDIA_ID_PREFIX."%'";
			$sql = "select max(cast(substring(m_media,".(strlen($MEDIA_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."media where m_gedfile = '".$gedid."'";
			break;
		case "SOUR":
			$sqlc = "select max(cast(substring(ch_gid,".(strlen($SOURCE_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$SOURCE_ID_PREFIX."%'";
			$sql = "select max(cast(substring(s_id,".(strlen($SOURCE_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."sources where s_file = '".$gedid."'";
			break;
		case "REPO":
			$sqlc = "select max(cast(substring(ch_gid,".(strlen($REPO_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$REPO_ID_PREFIX."%'";
			$sql = "select max(cast(substring(o_id,".(strlen($REPO_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."other where o_file = '".$gedid."' and o_type = 'REPO'";
			break;
		case "NOTE":
			$sqlc = "select max(cast(substring(ch_gid,".(strlen($NOTE_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."' AND ch_gid LIKE '".$NOTE_ID_PREFIX."%'";
			$sql = "select max(cast(substring(o_id,".(strlen($NOTE_ID_PREFIX)+1).") as signed)) as xref from ".$TBLPREFIX."other where o_file = '".$gedid."' and o_type = 'NOTE'";
			break;
		case "CHANGE":
			$sql = "select max(ch_cid) as xref from ".$TBLPREFIX."changes where ch_gedfile = '".$gedid."'";
			break;
		case "SUBM":
			return "SUB1";
			break;
	}
	$res = NewQuery($sql);
	$row = $res->fetchRow();
	$num = $row[0];
	
	// NOTE: Query from the change table
	if (isset($sqlc)) {
		$res = NewQuery($sqlc);
		$row = $res->fetchRow();
		$numc = $row[0];	
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
	else if ($type == "NOTE") $prefix = $NOTE_ID_PREFIX;
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

function IdType($id) {
	global $SOURCE_ID_PREFIX, $REPO_ID_PREFIX, $MEDIA_ID_PREFIX, $FAM_ID_PREFIX, $GEDCOM_ID_PREFIX, $NOTE_ID_PREFIX;
	
	// NOTE: Set length for the ID's
	$indi_length = strlen($GEDCOM_ID_PREFIX);
	$fam_length = strlen($FAM_ID_PREFIX);
	$source_length = strlen($SOURCE_ID_PREFIX);
	$repo_length = strlen($REPO_ID_PREFIX);
	$media_length = strlen($MEDIA_ID_PREFIX);
	$note_length = strlen($NOTE_ID_PREFIX);
	$submitter_length = 3;
	
	// NOTE: Check for individual ID
	if (substr($id, 0, $indi_length) == $GEDCOM_ID_PREFIX) return "INDI";
	else if (substr($id, 0, $submitter_length) == "SUB") return "SUBM";
	else if (substr($id, 0, $fam_length) == $FAM_ID_PREFIX) return "FAM";
	else if (substr($id, 0, $source_length) == $SOURCE_ID_PREFIX) return "SOUR";
	else if (substr($id, 0, $repo_length) == $REPO_ID_PREFIX) return "REPO";
	else if (substr($id, 0, $media_length) == $MEDIA_ID_PREFIX) return "OBJE";
	else if (substr($id, 0, $note_length) == $NOTE_ID_PREFIX) return "NOTE";
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
		WriteToLog("GetNewsItems-> News cannot be reached on Genmod News Server", "E");
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


function PrintGedcom($ged, $convert, $remove, $zip, $privatize_export, $privatize_export_level, $gedname, $embedmm) {
	GLOBAL $GEDCOM, $GEDCOMS, $gm_lang, $CHARACTER_SET, $GM_BASE_DIRECTORY, $gm_username, $TBLPREFIX, $Users;
	if ($zip == "yes") {
		$gedout = fopen($gedname, "w");
	}

	if ($privatize_export == "yes") {
		$Users->CreateExportUser($privatize_export_level);
		if (isset($_SESSION)) {
			$_SESSION["org_user"] = $_SESSION["gm_user"];
			$_SESSION["gm_user"] = "export";
		}
		if (isset($HTTP_SESSION_VARS)) {
			$HTTP_SESSION_VARS["org_user"] = $HTTP_SESSION_VARS["gm_user"];
			$HTTP_SESSION_VARS["gm_user"] = "export";
		}
		$gm_username = $Users->GetUserName();
	}

	SwitchGedcom($ged);
	$head = FindGedcomRecord("HEAD");
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
		$head .= "\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS ".GM_VERSION." ".GM_VERSION_RELEASE."\r\n";
		$head .= "1 DATE ".date("j M Y")."\r\n";
		$head .= "2 TIME ".date("h:i:s")."\r\n";
		if (strstr($head, "1 PLAC")===false) {
			$head .= "1 PLAC\r\n2 FORM ".$gm_lang["default_form"]."\r\n";
		}
	}
	else {
		$head = "0 HEAD\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS ".GM_VERSION." ".GM_VERSION_RELEASE."\r\n1 DEST DISKETTE\r\n1 DATE ".date("j M Y")."\r\n2 TIME ".date("h:i:s")."\r\n";
		$head .= "1 GEDC\r\n2 VERS 5.5\r\n2 FORM LINEAGE-LINKED\r\n1 CHAR $CHARACTER_SET\r\n1 PLAC\r\n2 FORM ".$gm_lang["default_form"]."\r\n";
	}
	if ($convert=="yes") {
		$head = preg_replace("/UTF-8/", "ANSI", $head);
		$head = utf8_decode($head);
	}
	$head = RemoveCustomTags($head, $remove);
	$head = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $head);
	if ($zip == "yes") fwrite($gedout, $head);
	else print $head;

	$sql = "SELECT i_gedcom FROM ".$TBLPREFIX."individuals WHERE i_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY CAST(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(i_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchRow()){
			$rec = trim($row[0])."\r\n";
			if ($embedmm=="yes") $rec = EmbedMM($rec);
			$rec = RemoveCustomTags($rec, $remove);
			if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
			if ($convert=="yes") $rec = utf8_decode($rec);
			if ($zip == "yes") fwrite($gedout, $rec);
			else print $rec;
		}
		$res->FreeResult();
	}
	
	$sql = "SELECT f_gedcom FROM ".$TBLPREFIX."families WHERE f_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(f_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchRow()){
			$rec = trim($row[0])."\r\n";
			if ($embedmm=="yes") $rec = EmbedMM($rec);
			$rec = RemoveCustomTags($rec, $remove);
			if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
			if ($convert=="yes") $rec = utf8_decode($rec);
			if ($zip == "yes") fwrite($gedout, $rec);
			else print $rec;
		}
		$res->FreeResult();
	}

	$sql = "SELECT s_gedcom FROM ".$TBLPREFIX."sources WHERE s_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(s_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchRow()){
			$rec = trim($row[0])."\r\n";
			if ($embedmm=="yes") $rec = EmbedMM($rec);
			$rec = RemoveCustomTags($rec, $remove);
			if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
			if ($convert=="yes") $rec = utf8_decode($rec);
			if ($zip == "yes") fwrite($gedout, $rec);
			else print $rec;
		}
		$res->FreeResult();
	}
	
	if ($embedmm != "yes") {
		$sql = "SELECT m_gedrec FROM ".$TBLPREFIX."media WHERE m_gedfile=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(m_media),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchRow()){
				$rec = trim($row[0])."\r\n";
				$rec = RemoveCustomTags($rec, $remove);
				if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
				if ($convert=="yes") $rec = utf8_decode($rec);
				if ($zip == "yes") fwrite($gedout, $rec);
				else print $rec;
			}
			$res->FreeResult();
		}
	}

	$sql = "SELECT o_gedcom, o_type FROM ".$TBLPREFIX."other WHERE o_file=".$GEDCOMS[$GEDCOM]['id']." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(o_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchRow()){
			$rec = trim($row[0])."\r\n";
			$key = $row[1];
			if (($key!="HEAD")&&($key!="TRLR")) {
				$rec = RemoveCustomTags($rec, $remove);
				if ($privatize_export == "yes") $rec = privatize_gedcom($rec);
				if ($convert=="yes") $rec = utf8_decode($rec);
				if ($zip == "yes") fwrite($gedout, $rec);
				else print $rec;
			}
		}
		$res->FreeResult();
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
		$Users->DeleteUser("export");
		$gm_username = $Users->GetUserName();
	}
	SwitchGedcom();
	if ($zip == "yes") {
		fclose($gedout);
	}
}


/* This function returns a list of directories
*
*/
function GetDirList($dirs, $recursive=true) {
	$dirlist = array();
	if (!is_array($dirs)) $dirlist[] = $dirs;
	else $dirlist = $dirs;
	foreach ($dirs as $key=>$dir) {
		$d = @dir($dir);
		if (is_object($d)) {
			while (false !== ($entry = $d->read())) {
				if ($entry != ".." && $entry != ".") {
					$entry = $dir.$entry."/";
					if(is_dir($entry)) {
						if ($recursive) $dirlist = array_merge($dirlist, GetDirList(array($entry)));
						else $dirlist[] = $entry;
					}
				}
			}
			$d->close();
		}
	}
	return $dirlist;
}
/*
function GetDescArray($pid, $ngen) {
	global $descarray, $pointtofath;
	$pointtofath[$pid]= "root";
	$pointtofathnew = array();
	$numrows = 0;
	for ($i=1;$i<=$ngen;$i++) {
//		print "Round ".$i;
//		pb();
		foreach ($pointtofath as $child => $hisfather) {
			if (in_array($child, $pointtofath)===false) {
				print "Get child ".$child;
//				pb();
				$fams = FindSFamilyIDs($child);
				foreach ($fams as $key1 => $famid) {
//					print "Get fam ".$famid;
//					pb();
					$children = GetChildrenIDs($famid["famid"]);
//					print_r($children);
//					pb();
					foreach($children as $key => $childsub) {
						$pointtofathnew[$childsub] = $child;
					}
					if ($numkidsgen > $numkids) $numkids = $numkidsgen;
				}
			}
//			else print "skipped ".$child;
		}
		$pointtofath = GMarrayMerge($pointtofath, $pointtofathnew);
	}
//	print_r ($pointtofath);
	return $numkids;
}
*/			


function GetGender($indirec) {
	
	$st = preg_match("/1 SEX (.*)/", $indirec, $smatch);
	if ($st>0) {
		$smatch[1] = trim($smatch[1]);
		if (empty($smatch[1])) return "U";
		else return trim($smatch[1]);
	}
}

function ArrayCopy (&$array, &$copy, $depth=0) {

	if(!is_array($copy)) $copy = array();
	foreach($array as $k => $v) {
		if(is_array($v)) ArrayCopy($v,$copy[$k],++$depth);
		else $copy[$k] = $v;
	}
}

function ExtractFullpath($mediarec) {
	preg_match("/(\d) _*FILE (.*)/", $mediarec, $amatch);
	if (empty($amatch[2])) return "";
	$level = trim($amatch[1]);
	$fullpath = trim($amatch[2]);
	$filerec = GetSubRecord($level, $amatch[0], $mediarec);
	$fullpath .= GetCont($level+1, $filerec);
	return $fullpath;
}

/**
 * get the relative filename for a media item
 *
 * gets the relative file path from the full media path for a media item.  checks the
 * <var>$MEDIA_DIRECTORY_LEVELS</var> to make sure the directory structure is maintained.
 * @param string $fullpath the full path from the media record
 * @return string a relative path that can be appended to the <var>$MEDIA_DIRECTORY</var> to reference the item
 */
function ExtractFilename($fullpath) {
	global $MEDIA_DIRECTORY_LEVELS, $MEDIA_DIRECTORY;

	$filename="";
	$regexp = "'[/\\\]'";
	$srch = "/".addcslashes($MEDIA_DIRECTORY,'/.')."/";
	$repl = "";
	if (!strstr($fullpath, "://")) $nomedia = stripcslashes(preg_replace($srch, $repl, $fullpath));
	else $nomedia = $fullpath;
	$ct = preg_match($regexp, $nomedia, $match);
	if ($ct>0) {
		$subelements = preg_split($regexp, $nomedia);
		$subelements = array_reverse($subelements);
		$max = $MEDIA_DIRECTORY_LEVELS;
		if ($max>=count($subelements)) $max=count($subelements)-1;
		for($s=$max; $s>=0; $s--) {
			if ($s!=$max) $filename = $filename."/".$subelements[$s];
			else $filename = $subelements[$s];
		}
	}
	else $filename = $nomedia;
	return $filename;
}

function findImageSize($file) {
	if (strtolower(substr($file, 0, 7)) == "http://")
		$file = "http://" . rawurlencode(substr($file, 7));
	else
		$file = FilenameDecode($file);
	$imgsize = @ getimagesize($file);
	if (!$imgsize) {
		$imgsize[0] = 300;
		$imgsize[1] = 300;
		$imgsize[2] = false;
	}
	return $imgsize;
}

function ParseFTSearchQuery($query) {
	$cquery = array();
	$cquery["includes"] = array();
	$cquery["excludes"] = array();
	
	
	// First extract the strings within quotes
	$ct = preg_match_all("/([+-]*\"[\w\s]*\")/", $query, $qstring);
	foreach ($qstring[0] as $key => $strquery) {
		$cquery[] = $strquery;
	}
	$query = preg_replace("/([+-]*\"[\w\s]*\")/", "", $query);
	
	// Then extract other keywords not in quotes
	$squery = preg_split("/\s/", $query);
	foreach($squery as $key => $strquery) {
		$strquery = trim ($strquery);
		if (!empty($strquery)) $cquery[] = $strquery;
	}
	foreach($cquery as $qindex => $squery) {
		if (empty($cquery[$qindex])) unset($cquery[$qindex]);
		else {
			if (substr($squery,-1) == "*") $wildcard = ".*";
			else $wildcard = "";
			if (substr($squery, 0, 1) == "-") {
				$term = preg_replace("/[+<>\*\"\~\?]/", "", substr($squery,1));
				if (!empty($term)) $cquery["excludes"][] = array("term"=>$term, "operator"=>"NOT", "wildcard"=>$wildcard);
			}
			else {
				if (substr($squery, 0, 1) == "+") $operator = "AND";
				else $operator = "OR";
				$term = preg_replace("/[+<>\*\"\~\?]/", "", $squery);
				if (!empty($term)) $cquery["includes"][] = array("term"=>$term, "operator"=>$operator, "wildcard"=>$wildcard);
			}
		}
	}
	return $cquery;
}

function GetFTMinLen($cquery) {
	
	$minwlen = 9999;
	$chinese = false;
	if (isset($cquery["includes"])) {
		foreach ($cquery["includes"] as $index => $keyword) {
			$subkeywords = preg_split("/\s/", $keyword["term"]);
			foreach($subkeywords as $key => $subkeyword) {
				$subkeyword = trim ($subkeyword);
				$len = utf8_strlen($subkeyword);
				if (HasChinese($subkeyword)) $chinese = true;
				if ($len < $minwlen) $minwlen = $len;
			}
		}
	}
	if (isset($cquery["excludes"])) {
		foreach ($cquery["excludes"] as $index => $keyword) {
			$subkeywords = preg_split("/\s/", $keyword["term"]);
			foreach($subkeywords as $key => $subkeyword) {
				$subkeyword = trim ($subkeyword);
				$len = utf8_strlen($subkeyword);
				if (HasChinese($subkeyword)) $chinese = true;
				if ($len < $minwlen) $minwlen = $len;
			}
		}
	}
	if ($chinese) return 1;
	else return $minwlen;
}

function HasMySQLStopwords($cquery) {
	
	$stopwords = array("a's","able","about","above","according","accordingly","across","actually","after","afterwards","again","against","ain't","all","allow","allows","almost","alone","along","already","also","although","always","am","among","amongst","an","and","another","any","anybody","anyhow","anyone","anything","anyway","anyways","anywhere","apart","appear","appreciate","appropriate","are","aren't","around","as","aside","ask","asking","associated","at","available","away","awfully","be","became","because","become","becomes","becoming","been","before","beforehand","behind","being","believe","below","beside","besides","best","better","between","beyond","both","brief","but","by","c'mon","c's","came","can","can't","cannot","cant","cause","causes","certain","certainly","changes","clearly","co","com","come","comes","concerning","consequently","consider","considering","contain","containing","contains","corresponding","could","couldn't","course","currently","definitely","described","despite","did","didn't","different","do","does","doesn't","doing","don't","done","down","downwards","during","each","edu","eg","eight","either","else","elsewhere","enough","entirely","especially","et","etc","even","ever","every","everybody","everyone","everything","everywhere","ex","exactly","example","except","far","few","fifth","first","five","followed","following","follows","for","former","formerly","forth","four","from","further","furthermore","get","gets","getting","given","gives","go","goes","going","gone","got","gotten","greetings","had","hadn't","happens","hardly","has","hasn't","have","haven't","having","he","he's","hello","help","hence","her","here","here's","hereafter","hereby","herein","hereupon","hers","herself","hi","him","himself","his","hither","hopefully","how","howbeit","however","i'd","i'll","i'm","i've","ie","if","ignored","immediate","in","inasmuch","inc","indeed","indicate","indicated","indicates","inner","insofar","instead","into","inward","is","isn't","it","it'd","it'll","it's","its","itself","just","keep","keeps","kept","know","knows","known","last","lately","later","latter","latterly","least","less","lest","let","let's","like","liked","likely","little","look","looking","looks","ltd","mainly","many","may","maybe","me","mean","meanwhile","merely","might","more","moreover","most","mostly","much","must","my","myself","name","namely","nd","near","nearly","necessary","need","needs","neither","never","nevertheless","new","next","nine","no","nobody","non","none","noone","nor","normally","not","nothing","novel","now","nowhere","obviously","of","off","often","oh","ok","okay","old","on","once","one","ones","only","onto","or","other","others","otherwise","ought","our","ours","ourselves","out","outside","over","overall","own","particular","particularly","per","perhaps","placed","please","plus","possible","presumably","probably","provides","que","quite","qv","rather","rd","re","really","reasonably","regarding","regardless","regards","relatively","respectively","right","said","same","saw","say","saying","says","second","secondly","see","seeing","seem","seemed","seeming","seems","seen","self","selves","sensible","sent","serious","seriously","seven","several","shall","she","should","shouldn't","since","six","so","some","somebody","somehow","someone","something","sometime","sometimes","somewhat","somewhere","soon","sorry","specified","specify","specifying","still","sub","such","sup","sure","t's","take","taken","tell","tends","th","than","thank","thanks","thanx","that","that's","thats","the","their","theirs","them","themselves","then","thence","there","there's","thereafter","thereby","therefore","therein","theres","thereupon","these","they","they'd","they'll","they're","they've","think","third","this","thorough","thoroughly","those","though","three","through","throughout","thru","thus","to","together","too","took","toward","towards","tried","tries","truly","try","trying","twice","two","un","under","unfortunately","unless","unlikely","until","unto","up","upon","us","use","used","useful","uses","using","usually","value","various","very","via","viz","vs","want","wants","was","wasn't","way","we","we'd","we'll","we're","we've","welcome","well","went","were","weren't","what","what's","whatever","when","whence","whenever","where","where's","whereafter","whereas","whereby","wherein","whereupon","wherever","whether","which","while","whither","who","who's","whoever","whole","whom","whose","why","will","willing","wish","with","within","without","won't","wonder","would","would","wouldn't","yes","yet","you","you'd","you'll","you're","you've","your","yours","yourself","yourselves","zero");
	
	if (isset($cquery["includes"])) {
		foreach ($cquery["includes"] as $index => $keyword) {
			$subkeywords = preg_split("/\s/", $keyword["term"]);
			foreach($subkeywords as $key => $subkeyword) {
				if (in_array(strtolower($subkeyword), $stopwords)) return true;
			}
		}
	}
	if (isset($cquery["excludes"])) {
		foreach ($cquery["excludes"] as $index => $keyword) {
			$subkeywords = preg_split("/\s/", $keyword["term"]);
			foreach($subkeywords as $key => $subkeyword) {
				$subkeyword = trim ($subkeyword);
				if (in_array(strtolower($subkeyword), $stopwords)) return true;
			}
		}
	}
	return false;
}

function utf8_strlen($string){
  if(!defined('UTF8_NOMBSTRING') && function_exists('mb_strlen'))
    return mb_strlen($string,'utf-8');

  $uni = utf8_to_unicode($string);
  return count($uni);
}

function utf8_to_unicode( $str ) {
  $unicode = array();  
  $values = array();
  $lookingFor = 1;
  
  for ($i = 0; $i < strlen( $str ); $i++ ) {
    $thisValue = ord( $str[ $i ] );
    if ( $thisValue < 128 ) $unicode[] = $thisValue;
    else {
      if ( count( $values ) == 0 ) $lookingFor = ( $thisValue < 224 ) ? 2 : 3;
      $values[] = $thisValue;
      if ( count( $values ) == $lookingFor ) {
  $number = ( $lookingFor == 3 ) ?
    ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
  	( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
  $unicode[] = $number;
  $values = array();
  $lookingFor = 1;
      }
    }
  }
  return $unicode;
}

function utf8_isASCII($str){
  for($i=0; $i<strlen($str); $i++){
    if(ord($str{$i}) >127) return false;
  }
  return true;
}

function HandleIntrusion($text="") {
	global $_SERVER, $_REQUEST, $LOCKOUT_TIME, $TBLPREFIX, $gm_username;
	
	// Get the username to add to the log
	if (!isset($gm_username) || empty($gm_username)) {
		$gm_username = "";
		if (isset($_SESSION)) {
			if (!empty($_SESSION['gm_user'])) $gm_username = $_SESSION['gm_user'];
		}
		if (isset($HTTP_SESSION_VARS)) {
			if (!empty($HTTP_SESSION_VARS['gm_user'])) $gm_username = $HTTP_SESSION_VARS['gm_user'];
		}
	}
	
	$ip = $_SERVER["REMOTE_ADDR"];
	
	// Make the logstring
	$str = "HandleIntrusion-> Intrusion detected for ".$_SERVER["SCRIPT_NAME"]."<br />Query string:<br />";
	foreach ($_REQUEST as $key => $value) {
		$str.= $key."&nbsp;=&nbsp;".$value."<br />";
	}
	if ($LOCKOUT_TIME == "-1") $str .= "IP not locked out.";
	else {
		if ($LOCKOUT_TIME == "0") {
			$str .= "IP locked out forever.";
			$sql = "INSERT INTO ".$TBLPREFIX."lockout VALUES ('".$ip."' , '".time()."', '0', '".$gm_username."') ON DUPLICATE KEY UPDATE lo_timestamp='".time()."', lo_release='0'";
		}
		else {
			$str .= "IP locked out for ".$LOCKOUT_TIME." minutes.";
			$newtime = time() + 60*$LOCKOUT_TIME;
			$sql = "INSERT INTO ".$TBLPREFIX."lockout VALUES ('".$ip."', '".time()."', '".$newtime."', '".$gm_username."') ON DUPLICATE KEY UPDATE lo_timestamp='".time()."', lo_release='".$newtime."'";
		}
		$res = NewQuery($sql);
		@session_destroy();
	}
	WriteToLog($str, "W", "S");
	
	header("HTTP/1.1 403 Forbidden");
	if (!empty($text)) print $text;
	exit;
}
	
function CheckLockout() {
	global $_SERVER, $_REQUEST, $LOCKOUT_TIME, $TBLPREFIX, $gm_username;
	
	// Get the username to add to the log
	if (!isset($gm_username) || empty($gm_username)) {
		$gm_username = "";
		if (isset($_SESSION)) {
			if (!empty($_SESSION['gm_user'])) $gm_username = $_SESSION['gm_user'];
		}
		if (isset($HTTP_SESSION_VARS)) {
			if (!empty($HTTP_SESSION_VARS['gm_user'])) $gm_username = $HTTP_SESSION_VARS['gm_user'];
		}
	}
	
	$ip = $_SERVER["REMOTE_ADDR"];
	$sql = "SELECT * FROM ".$TBLPREFIX."lockout WHERE lo_ip='".$ip."'";
	if (!empty($gm_username)) $sql .= " OR lo_username='".$gm_username."'";
	$res = NewQuery($sql);
	$staylocked = false;
	if ($res) {
		if ($res->NumRows()>0) {
			while($row = $res->FetchRow()){
				$ntime = time();
				if ($row[2] <= $ntime && $row[2] != '0') {
					$sql = "DELETE FROM ".$TBLPREFIX."lockout WHERE lo_ip='".$row[0]."' AND lo_username='".$row[3]."'";
					$res2 = NewQuery($sql);
				}
				else {
					$staylocked = true;
				}
			}
			if ($staylocked) {
				header("HTTP/1.1 403 Forbidden");
				exit;
			}
		}
	}
}

/* this function will read a person's gedcom record and try to determine the persons birth and death
 * year. See the rules in the code
 * 
 * @author Genmod team
 * @param string $indirec the raw gedcom record
 * @return array with 2 year values, pid and type (as 
 * requested or "true" is found a true year)
 */
function EstimateBD($indirec, $type) {
	global $CHECK_CHILD_DATES, $MAX_ALIVE_AGE, $HIDE_LIVE_PEOPLE;
	global $PRIVACY_BY_YEAR, $gm_lang, $BUILDING_INDEX, $COMBIKEY;
	global $TBLPREFIX, $GEDCOM, $GEDCOMS, $GEDCOMID;

	// Init values
	$dates = array();
		
	$ct = preg_match("/0 @(.*)@ INDI/", $indirec, $match);
	if ($ct>0) {
		$pid = trim($match[1]);
	}
	else return false;
	$dates["pid"] = $pid;
	
	$cyear = date("Y");
	
	// -- check for a death record
	$deathrec = GetSubRecord(1, "1 DEAT", $indirec);
	if (!empty($deathrec)) {
		if (preg_match("/1 DEAT Y/", $deathrec)>0) $deathyear = $cyear;
		else {
			$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $deathrec, $match);
			if ($ct>0) $truedeathyear = $match[1];
		}
	}

	//-- check for birth record
	$birthrec = GetSubRecord(1, "1 BIRT", $indirec);
	if (!empty($birthrec)) {
		$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $birthrec, $match);
		if ($ct>0) $truebirthyear = $match[1];
	}

	// if we have true dates, return now
	if (isset($truebirthyear) && isset($truedeathyear)) {
		$dates["birth"]["year"] = $truebirthyear;
		$dates["birth"]["type"] = "true";
		$dates["death"]["year"] = $truedeathyear;
		$dates["death"]["type"] = "true";
//		print_r($dates);
		return $dates;
	}
	
	// If estimate type is true, we are incomplete!
	if ($type == "true") {
		if (isset($truebirthyear)) {
			$dates["birth"]["year"] = $truebirthyear;
			$dates["birth"]["type"] = "true";
		}
		if (isset($truedeathyear)) {
			$dates["death"]["year"] = $truedeathyear;
			$dates["death"]["type"] = "true";
		}
		return $dates;
	}
		
	
	// Check the fact dates
	$ffactyear = 9999;
	$lfactyear = 0;
	$subrecs = GetAllSubRecords($indirec, "CHAN", true, true, false);
	foreach ($subrecs as $key => $rec) {
		$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $rec, $match, PREG_SET_ORDER);
//		print_r($match);
		for($i=0; $i<$ct; $i++) {
			if (strstr($match[$i][0], "@#DHEBREW@")===false) {
				$byear = $match[$i][1];
				if ($ffactyear > $byear) $ffactyear = $byear;
				if ($lfactyear < $byear) $lfactyear = $byear;
			}
		}
	}

	// If we found no dates then check the dates of close relatives.
	if($CHECK_CHILD_DATES ) {
		//-- check the parents for dates
		$numfams = preg_match_all("/1\s*FAMC\s*@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
		for($j=0; $j<$numfams; $j++) {
			$parents = FindParents($fmatch[$j][1]);
			if ($parents) {
				if (!empty($parents["HUSB"])) {
					$prec = FindPersonRecord($parents["HUSB"]);
					// For now only search the birth record
					$brec = GetSubRecord(1, "1 BIRT", $prec);
					$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $brec, $match, PREG_SET_ORDER);
					// loop for later if also facts are scanned
					for($i=0; $i<$ct; $i++) {
						$fbyear = $match[$i][1];
					}
					$drec = GetSubRecord(1, "1 DEAT", $prec);
					if (!empty($drec)) {
						if (preg_match("/1 DEAT Y/", $drec)>0) $fddate = $cyear;
						else {
							$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $drec, $match);
							if ($ct>0) $fdyear = $match[1];
						}
					}
				}
				if (!empty($parents["WIFE"])) {
					$prec = FindPersonRecord($parents["WIFE"]);
					// For now only search the birth record
					$brec = GetSubRecord(1, "1 BIRT", $prec);
					$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $brec, $match, PREG_SET_ORDER);
					// loop for later if also facts are scanned
					for($i=0; $i<$ct; $i++) {
						$mbyear = $match[$i][1];
					}
					$drec = GetSubRecord(1, "1 DEAT", $prec);
					if (!empty($drec)) {
						if (preg_match("/1 DEAT Y/", $drec)>0) $mddate = $cyear;
						else {
							$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $drec, $match);
							if ($ct>0) $mdyear = $match[1];
						}
					}
				}
			}
		}
		$children = array();
		// For each family in which this person is a spouse...
		$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
		$fmarryear = 9999;
		$lmarryear = 0;
		$fcbyear = 9999;
		$lcbyear = 0;
		for($j=0; $j<$numfams; $j++) {
			// Get the family record
			$famrec = FindFamilyRecord($fmatch[$j][1]);

			//-- check for marriage date
			$marrec = GetSubRecord(1, "1 MARR", $famrec);
			if ($marrec!==false) {
				$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $marrec, $bmatch, PREG_SET_ORDER);
				for($h=0; $h<$bt; $h++) {
					$byear = $bmatch[$h][1];
					if ($fmarryear > $byear) $fmarryear = $byear;
					if ($lmarryear < $byear) $lmarryear = $byear;
				}
			}
			
			//-- check for divorce date
			$marrec = GetSubRecord(1, "1 DIV", $famrec);
			if ($marrec!==false) {
				$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $marrec, $bmatch, PREG_SET_ORDER);
				for($h=0; $h<$bt; $h++) {
					$byear = $bmatch[$h][1];
					if ($fmarryear > $byear) $fmarryear = $byear;
					if ($lmarryear < $byear) $lmarryear = $byear;
				}
			}
			
			//-- check spouse record for dates (not yet)
//			$parents = FindParentsInRecord($famrec);
//			if ($parents) {
//				if ($parents["HUSB"]!=$pid) $spid = $parents["HUSB"];
//				else $spid = $parents["WIFE"];
//				$spouserec = FindPersonRecord($spid);
//				// Check dates
//				$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $spouserec, $bmatch, PREG_SET_ORDER);
//				for($h=0; $h<$bt; $h++) {
//					$byear = $bmatch[$h][1];
//					// if the spouse is > $MAX_ALIVE_AGE assume the individual is dead
//					if (($cyear-$byear) > $MAX_ALIVE_AGE) {
//						//print "spouse older than $MAX_ALIVE_AGE (".$bmatch[$h][0].") year is $byear\n";
//						return true;
//					}
//				}
//			}
			// Get the set of children
			$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				// Get each child's record
				$childrec = FindPersonRecord($match[$i][1]);
				$children[] = $childrec;

				// Check each child's dates (for now only birth)
				$brec = GetSubRecord(1, "1 BIRT", $childrec);
				$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $brec, $bmatch, PREG_SET_ORDER);
				for($h=0; $h<$bt; $h++) {
					$byear = $bmatch[$h][1];
					if ($fcbyear > $byear) $fcbyear = $byear;
					if ($lcbyear < $byear) $lcbyear = $byear;
				}
			}
		}
		//-- check grandchildren for dates
		$fgcbyear = 9999;
		foreach($children as $indexval => $child) {
			// For each family in which this person is a spouse...
			$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $child, $fmatch, PREG_SET_ORDER);
			for($j=0; $j<$numfams; $j++) {
				// Get the family record
				$famrec = FindFamilyRecord($fmatch[$j][1]);

				// Get the set of children
				$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					// Get each child's record
					$childrec = FindPersonRecord($match[$i][1]);

					// Check each grandchild's dates
					$brec = GetSubRecord(1, "1 BIRT", $childrec);
					$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $brec, $bmatch, PREG_SET_ORDER);
					for($h=0; $h<$bt; $h++) {
						$byear = $bmatch[$h][1];
						if ($fgcbyear > $byear) $fgcbyear = $byear;
					}
				}
			}
		}
	}
	// We have all data, now apply the rules
	if ($type = "narrow") {	
		// * The latest estimated birth year is the lowest value of:
		if (isset($truebirthyear)) {
			$dates["birth"]["year"] = $truebirthyear;
			$dates["birth"]["type"] = "true";
		}
		else {
			// *     1. Death year
			if (isset($truedeathyear)) $birthyear = $truedeathyear;
			// *     2. Earliest fact year
			if ($ffactyear != 9999 && (!isset($birthyear) || $ffactyear < $birthyear)) $birthyear = $ffactyear;
			// *     3. Mothers birth year + 50
			if (isset($mbyear) && (!isset($birthyear) || ($mbyear + 50) < $birthyear)) $birthyear = $mbyear + 50;
			// *     4. Fathers birth year + 50
			if (isset($fbyear) && (!isset($birthyear) || ($fbyear + 50) < $birthyear)) $birthyear = $fbyear + 50;
			// *     5. First childs birth year - 15
			if ($fcbyear != 9999 && (!isset($birthyear) || ($fcbyear - 15) < $birthyear)) $birthyear = $fcbyear - 15;
			// *     6. First grandchilds birth year - 30
			if (!isset($birthyear) || ($fgcbyear - 30) < $birthyear) $birthyear = $fgcbyear - 30;
			// *     7. First marriage year - 20
			if ($fmarryear != 9999 && (!isset($birthyear) || ($fmarryear - 20) < $birthyear)) $birthyear = $fmarryear - 20;
			// *     8. Mothers death year
			if (isset($mdyear) && (!isset($birthyear) || $mdyear < $birthyear)) $birthyear = $mdyear;
			// *     9. Fathers death year
			if (isset($fdyear) && (!isset($birthyear) || $fdyear < ($birthyear - 1))) $birthyear = $fdyear;
			if (isset($birthyear)) {
				$dates["birth"]["year"] = $birthyear;
				$dates["birth"]["type"] = $type;
			}
		}
 		// * The earliest estimated death year is the highest value of:
		if (isset($truedeathyear)) {
			$dates["death"]["year"] = $truedeathyear;
			$dates["death"]["type"] = "true";
		}
		else {
			// *     1. Birth year
			if (isset($truebirthyear)) $deathyear = $truebirthyear;
			// *     2. Latest fact year
//			print $lfactyear;
			if ($lfactyear != 0 && (!isset($deathyear) || $lfactyear > $deathyear)) $deathyear = $lfactyear;
			// *     3. Mothers birth year + 15
			if (isset($mbyear) && (!isset($deathyear) || ($mbyear + 15) > $deathyear)) $deathyear = $mbyear + 15;
			// *     4. Fathers birth year + 15
			if (isset($fbyear) && (!isset($deathyear) || ($fbyear + 15) > $deathyear)) $deathyear = $fbyear + 15;
			// *     5. Last childs birth year
			if ($lcbyear != 0 && (!isset($deathyear) || $lcbyear > $deathyear)) $deathyear = $lcbyear;
			// *     6. First grandchilds birth year - 15
			if ($fgcbyear != 9999 && (!isset($deathyear) || $fgcbyear > $deathyear)) $deathyear = $fgcbyear;
			// *     7. Latest marriage year and 8. Latest divorce year
			if ($lmarryear != 0 && (!isset($deathyear) || $lmarryear > $deathyear)) $deathyear = $lmarryear;
			if (isset($deathyear)) {
				$dates["death"]["year"] = $deathyear;
				$dates["death"]["type"] = $type;
			}
		}
	}
	if ($type = "wide") {	
		// * The earliest estimated birth year is the lowest value of:
		if (isset($truebirthyear)) {
			$dates["birth"]["year"] = $truebirthyear;
			$dates["birth"]["type"] = "true";
		}
		else {
			// *     1. Death year - $MAX_ALIVE_AGE
			if (isset($truedeathyear)) $birthyear = $truedeathyear - $MAX_ALIVE_AGE;
			// *     2. Latest fact year - $MAX_ALIVE_AGE
			if ($lfactyear != 0 && (!isset($birthyear) || ($lfactyear - $MAX_ALIVE_AGE) < $birthyear)) $birthyear = $lfactyear - $MAX_ALIVE_AGE;
			// *     3. Mothers birth year + 15
			if (isset($mbyear) && (!isset($birthyear) || ($mbyear + 15) < $birthyear)) $birthyear = $mbyear + 15;
			// *     4. Fathers birth year + 15
			if (isset($fbyear) && (!isset($birthyear) || ($fbyear + 15) < $birthyear)) $birthyear = $fbyear + 15;
			// *     5. First childs birth year - 50
			if ($fcbyear != 9999 && (!isset($birthyear) || ($fcbyear - 50)) < $birthyear) $birthyear = $fcbyear - 50;
			// *     6. First grandchilds birth year - 100
			if ($fgcbyear != 9999 && (!isset($birthyear) || ($fgcbyear - 100) < $birthyear)) $birthyear = $fgcbyear-100;
			// *     7. Marriage year - 70
			if ($fmarryear != 9999 && (!isset($birthyear) || ($fmarryear - 70) < $birthyear)) $birthyear = $fmarryear - 70;
			// *     BUT no later than the death year of mother or father
			if (isset($mdyear) && isset($birthyear) && $mdyear < $birthyear) $birthyear = $mdyear;
			if (isset($fdyear) && isset($birthyear) && $fdyear-1 < $birthyear) $birthyear = $fdyear-1;
		}
		if (isset($truedeathyear)) {
			$dates["death"]["year"] = $truedeathyear;
			$dates["death"]["type"] = "true";
		}
		else {
		// * The latest estimated death date is the highest value of:
			// *     1. Birth year + $MAX_ALIVE_AGE
			if (isset($truebirthyear)) $deathyear = $truebirthyear + $MAX_ALIVE_AGE;
			// *     2. Latest fact year + $MAX_ALIVE_AGE
			if ($lfactyear != 0 && (!isset($deathyear) || ($lfactyear + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $lfactyear + $MAX_ALIVE_AGE;
			// *     3. Mothers birth year + $MAX_ALIVE_AGE + 15
			if (isset($mbyear) && (!isset($deathyear) || ($mbyear + 15 + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $mbyear + 15 + $MAX_ALIVE_AGE;
			// *     4. Fathers birth year + $MAX_ALIVE_AGE + 15
			if (isset($fbyear) && (!isset($deathyear) || ($fbyear + 15 + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $fbyear + 15 + $MAX_ALIVE_AGE;
			// *     5. Last childs birth year + $MAX_ALIVE_AGE - 15
			if ($lcbyear != 0 && (!isset($deathyear) || ($lcbyear - 15 + $MAX_ALIVE_AGE)) > $deathyear) $deathyear = $fcbyear - 15 + $MAX_ALIVE_AGE;
			// *     6. First grandchilds birth year + $MAX_ALIVE_AGE - 30
			if ($fgcbyear != 9999 && (!isset($deathyear) || ($fgcbyear - 30 + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $fgcbyear - 30 + $MAX_ALIVE_AGE;
			// *     7. Marriage year + $MAX_ALIVE_AGE - 20
			if ($lmarryear != 0 && (!isset($deathyear) || ($lmarryear - 20 + $MAX_ALIVE_AGE) < $deathyear)) $deathyear = $lmarryear - 20 + $MAX_ALIVE_AGE;
		}
	}
	return $dates;
}

// Get the recordID
function GetRecID($gedrec) {
	
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) return $gmatch[1];
	else return false;
}
	
// Get the recordtype
function GetRecType($gedrec) {
	
	$gt = preg_match("/0 @.+@ (\w+)/", $gedrec, $gmatch);
	if ($gt > 0) return $gmatch[1];
	else return false;
}

// Get the recordtype
function GetRecLevel($gedrec) {
	
	if (empty($gedrec)) return -1;
	else return substr($gedrec, 0, 1);
}

/* Cloning an object */
function CloneObj($obj) {
	if (version_compare(phpversion(), '5.0') < 0)  $cloneobj = $obj;
	else $cloneobj = clone($obj);
	return $cloneobj;
}

// The next function is only for sorting the non-object medialist
function OldMediaSort($a, $b) {
	$aname = Str2Upper($a["TITL"]);
	$bname = Str2Upper($b["TITL"]);
	return StringSort($aname, $bname);
}

function CheckEmailAddress($address) {
	
	$valid = preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*([a-zA-Z0-9])+@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+\.([a-zA-Z0-9_-])+$/', $address);
	if (!$valid) return false;
	$mt = preg_match("/(.+)@(.+)/", $address, $match);
	if ($mt>0) {
		$host = trim($match[2]);
		// First try a realtime check to see if the domain exists
		if (function_exists("checkdnsrr")) {
			$ip = checkdnsrr($host);
			if ($ip === false) {
				$host = "www.".$host;
				$ip = checkdnsrr($host);
				if ($ip === false) return false;
				else return true;
			}
			else return true;
		}
		// If that cannot be done, at least check the root zone of the domain
		$rootdomains = array('AC', 'AD', 'AE', 'AERO', 'AF', 'AG', 'AI', 'AL', 'AM', 'AN', 'AO', 'AQ', 'AR', 'ARPA', 'AS', 'ASIA', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BIZ', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CAT', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'COM', 'COOP', 'CR', 'CU', 'CV', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EDU', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'EU', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GOV', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'INFO', 'INT', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JOBS', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MIL', 'MK', 'ML', 'MM', 'MN', 'MO', 'MOBI', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MUSEUM', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NAME', 'NC', 'NE', 'NET', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'ORG', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PRO', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'ST', 'SU', 'SV', 'SY', 'SZ', 'TC', 'TD', 'TEL', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TP', 'TR', 'TRAVEL', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UK', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'YU', 'ZA', 'ZM', 'ZW');
		$rootzone = preg_split("/\./", $host);
		$rootzone = array_reverse($rootzone);
		if (!in_array(strtoupper($rootzone[0]), $rootdomains)) return false;
		else return true;
	}
	return false;
}

// Switch gedcoms on the fly. 
// Call this function with an empty string to go back to the original values.
function SwitchGedcom($ged="") {
	global $GEDCOM, $GEDCOMID, $Privacy, $GedcomConfig;
	static $orgged;
	
	// If we are already there, stay there.
	if ($ged == $GEDCOM || $ged == $GEDCOMID) return;
	
	// Switching back if nothing ever changed or back to the original.
	if (empty($ged)) {
		if (!isset($orgged)) return;
		else $ged = $orgged;
	}
	
	// Switch to something else.
	if (is_numeric($ged)) {
		// Save the old value
		if (!isset($orgged)) $orgged = $GEDCOMID;
		// Set the new values
		$GEDCOMID = $ged;
		$GEDCOM = get_gedcom_from_id($GEDCOMID);
	}
	else {
		// Save the old value
		if (!isset($orgged)) $orgged = get_id_from_gedcom($GEDCOM);
		// Set the new values
		$GEDCOM = $ged;
		$GEDCOMID = get_id_from_gedcom($GEDCOM);
	}
	// Make the switch
	$Privacy->ReadPrivacy($GEDCOMID);
	$GedcomConfig->ReadGedcomConfig($GEDCOM);
	return;
}

/* Strips the trailing dot and slash from the filename
*/
function RelativePathFile($file) {
	$s = substr($file,0,1);
	if ($s == ".") $file = substr($file, 1);
	$s = substr($file,0,1);
	if ($s == "/") $file = substr($file, 1);
	return $file;
}

function ReplaceEmbedText($text) {
	global $gm_lang, $CONFIG_VARS;
	
	while (preg_match("/#(.+)#/", $text, $match) > 0) {
		$varname = $match[1];
		global $$varname;
		
		// check if config variable
		if (in_array($varname, $CONFIG_VARS)) $text = preg_replace("/$match[0]/", $gm_lang["embedvar_not_allowed"], $text);
		
		// check language variable
		else if (isset($gm_lang[$varname])) $text = preg_replace("/$match[0]/", $gm_lang[$varname], $text);
		
		// check variable
		else if (isset($$varname)) $text = preg_replace("/$match[0]/", $$varname, $text);
		
		// check constant
		else if (defined($varname)) $text = preg_replace("/$match[0]/", constant($varname), $text);
		
		// check constant with prefix
		else if (defined("GM_".$varname)) $text = preg_replace("/$match[0]/", constant("GM_".$varname), $text);
		
		// if nothing is found, replace with error message. Doing nothing would cause an endless loop anyway
		else $text = preg_replace("/$match[0]/", $gm_lang["embedvar_not_found"], $text);
	}
	return $text;
}	
?>