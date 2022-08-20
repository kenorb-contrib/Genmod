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
 * @version $Id: functions.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
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
if (DEBUG == true) $ERROR_LEVEL = 2;

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
function get_id_from_gedcom($gedcom) {
	global $GEDCOMS;

	// If set, it is a mistake....
	if (isset($GEDCOMS[$gedcom])) return $gedcom;
	// Scan the array until found
	foreach($GEDCOMS as $gedid => $gedarray) {
		if ($gedarray["gedcom"] == $gedcom) return $gedid;
	}

	return false;
}

/**
 * get a databse id from its gedcom filename 
 * @param int $gedcom	The filename to get the gedcom database id for
 * @return string
 */
function get_gedcom_from_id($ged_id) {
	global $GEDCOMS;

	if (isset($GEDCOMS[$ged_id])) return $GEDCOMS[$ged_id]["gedcom"];
	else return false;
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
		if ($ERROR_LEVEL == 0) return;
		if (stristr($errstr,"by reference") == true) return;
		$msg = "ERROR ".$errno.": ".$errstr."<br />";
		$logline = $msg."Error occurred on line ".$errline." of file ".basename($errfile)."<br />";
		$logline .= "Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />";
		//$msg .= "Error occurred on line ".$errline." of file ".basename($errfile)."<br />\n";
		print "\n<br />".$msg;
		WriteToLog("GmErrorHandler-&gt; ".$logline, "E", "S");
		if (($errno<16)&&(function_exists("debug_backtrace"))&&(strstr($errstr, "headers already sent by")===false)) {
			$backtrace = array();
			$backtrace = debug_backtrace();
			$num = count($backtrace);
			if ($ERROR_LEVEL == 1) $num = 1;
			for($i=0; $i<$num; $i++) {
				$logline .= $i;
				if ($i==0) $logline .= " Error occurred on ";
				else $logline .= " called from ";
				if (isset($backtrace[$i]["line"]) && isset($backtrace[$i]["file"])) $logline .= "line <b>".$backtrace[$i]["line"]."</b> of file <b>".basename($backtrace[$i]["file"])."</b>";
				if ($i<$num-1) $logline .= " in function <b>".$backtrace[$i+1]["function"]."</b>";
				$logline .= "<br />\n";
			}
			print $logline;
		}
		WriteToLog("GmErrorHandler-&gt; ".$logline, "E", "S");
		if ($errno==1) die();
	}
	return false;
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
if (is_object($gedrec)) print $pipo;
	while($num > 0 && $pos1 < strlen($gedrec)) {
		// Find the next occurance of the desired string
		$pos1 = strpos($gedrec, $tag, $pos1);
		if ($pos1 === false) {
			$tag = preg_replace("/(\w+)/", "_$1", $tag);
			$pos1 = strpos($gedrec, $tag, $pos1);
			if ($pos1 === false) return "";
		}
//		print "pos1: ".$pos1;
// This causes problems. If a level 2 is searched in a complete indirecord, it will return all up 
// to the next level 2 record, including the level 1 in between.
// This will find the nearest lower level:
// E.g. first find the next level 3, then, the nearer level 2, etc, to 1.
		$plow = 99999;
		for ($L = $level; $L>0; $L--) {
			$p = strpos($gedrec, "\n$L", $pos1+1);
			if ($p !== false && $p < $plow) $plow = $p;
		}
		if ($plow == 99999) $pos2 = false;
		else $pos2 = $plow;
//		$pos2 = $plow;
//		print " pos2: ".$pos2."<br />";
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
			if ($newpos = strripos($subrec, $lowtag)) {
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
			if ($ApplyPriv && preg_match("/\d\sOBJE\s@(\w+)@/", $match[$i][0], $mmatch)) {
				$object = ConstructObject($mmatch[1], "OBJE");
				$dispmedialink = $object->disp;
			}
			else $dispmedialink = true;
			if ($ApplyPriv && preg_match("/\d\sSOUR\s@(\w+)@/", $match[$i][0], $mmatch)) {
				$object = ConstructObject($mmatch[1], "SOUR");
				$dispsourcelink = $object->disp;
			}
			else $dispsourcelink = true;
			if ($ApplyPriv && preg_match("/\d\sNOTE\s@(\w+)@/", $match[$i][0], $mmatch)) {
				$object = ConstructObject($mmatch[1], "NOTE");
				$dispnotelink = $object->disp;
			}
			else $dispnotelink = true;
			if (!$ApplyPriv || (PrivacyFunctions::showFact($fact, $id, $type) && PrivacyFunctions::showFactDetails($fact,$id) && $dispmedialink && $dispsourcelink && $dispnotelink)) {
				$subrec = GetSubRecord(1, "1 $fact", $gedrec, $prev_tags[$fact]);
				if (!$ApplyPriv || (!PrivacyFunctions::FactViewRestricted($id, $subrec) && !PrivacyFunctions::FactViewRestricted($id, $gedrec, 1))) {
					if ($fact=="EVEN") {
						$tt = preg_match("/2 TYPE (.*)/", $subrec, $tmatch);
						if ($tt>0) {
							$type = trim($tmatch[1]);
							if (!$ApplyPriv || (PrivacyFunctions::showFact($type, $id) && PrivacyFunctions::showFactDetails($type,$id))) $repeats[] = trim($subrec)."\r\n";
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
				$fam =& Family::GetInstance($fmatch[$f][1]);
			if (!$ApplyPriv || $fam->disp) {
				$famrec = $fam->gedrec;
				if ($id == $fam->husb_id) $spid = $fam->wife_id;
				else $spid = $fam->husb_id;
				$prev_tags = array();
				$ct = preg_match_all("/\n1 (\w+)(.*)/", $famrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$fact = trim($match[$i][1]);
					if (strpos($ignore, $fact)===false) {
						if (!$ApplyPriv || (PrivacyFunctions::showFact($fact, $id) && PrivacyFunctions::showFactDetails($fact,$id))) {
							if (isset($prev_tags[$fact])) $prev_tags[$fact]++;
							else $prev_tags[$fact] = 1;
							$subrec = GetSubRecord(1, "1 $fact", $famrec, $prev_tags[$fact]);
							// NOTE: Record needs to be trimmed to make sure no extra linebreaks are left
							$subrec = trim($subrec)."\r\n";
							$subrec .= "2 _GMS @$spid@\r\n";
							$subrec .= "2 _GMFS @$famid@\r\n";
							if ($fact=="EVEN") {
								$ct2 = preg_match("/2 TYPE (.*)/", $subrec, $tmatch);
								if ($ct2>0) {
									$type = trim($tmatch[1]);
									if (!$ApplyPriv or (PrivacyFunctions::showFact($type, $id) && PrivacyFunctions::showFactDetails($type,$id))) $repeats[] = trim($subrec)."\r\n";
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
			$object = ConstructObject($match[1]);
			if (is_object($object)) {
				$value=$match[1];
				$subrec = $object->gedrec;
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
			if (GedcomConfig::$SHOW_PEDIGREE_PLACES>0) {
				$plevels = preg_split("/,/", $value);
				$value = "";
				for($plevel=0; $plevel < GedcomConfig::$SHOW_PEDIGREE_PLACES; $plevel++) {
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
		else if ($convert && $t == "SEX") {
			if ($value == "M") $value = NameFunctions::GetFirstLetter(GM_LANG_male);
			else if ($value == "F") $value = NameFunctions::GetFirstLetter(GM_LANG_female);
			else $value = NameFunctions::GetFirstLetter(GM_LANG_unknown);
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
	
	$text = "";
	$tt = preg_match_all("/$nlevel CON[CT](.*)(\r\n|\r|\n)*/", $nrec, $cmatch, PREG_SET_ORDER);
	for($i=0; $i<$tt; $i++) {
		if (strstr($cmatch[$i][0], "CONT")) $text.="<br />";
		else if (GedcomConfig::$WORD_WRAPPED_NOTES) $text.=" ";
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
 * @return array an object array with indexes "thumb" and "file" for thumbnail and filename
 */
function FindHighlightedObject($obj) {
	global $GM_IMAGES;
	

	$facts = $obj->SelectFacts(array("OBJE"));
	$medias = array();
	if (count($facts) == 0) return false;
	
	// We have the candidates that can be displayed. Check for a _PRIM Y in the link record.
	// On the fly we also check the _THUM tag, first in the link, then in the media record.
	// If a media item has no PRIM Y and PRIM N in the fact record that is pointing to it, store it for further investigation.
	foreach($facts as $key => $fact) {
		$media =& MediaItem::GetInstance($fact->linkxref, "", $fact->gedcomid);
		if ($fact->style != "ChangeOld") {
			$prim = GetGedcomValue("_PRIM", 2, $fact->factrec);
			if ($prim == "Y") {
				$primfile = $media->filename;
				$thum = GetGedcomValue("_THUM", 2, $fact->factrec);
				if (empty($thum)) $thum = GetGedcomValue("_THUM", 1, $fact->factrec);
				$id = $media->xref;
				break;
			}
			else if ($prim != "N") {
				$medias[] = $media;
			}
		}
	}

	// Nothing in the link records. Now check the media records for "defaults", PRIM Y in the media item.
	// On the fly we also check the _THUM tag, first in the link, then in the media record.
	if (!isset($primfile)) {
		foreach($medias as $key => $media) {
			if ($media->isprimary == "Y") {
				$primfile = $media->filename;
				$thum = $media->useasthumb;
				$id = $media->xref;
				break;
			}
			else if ($media->isprimary == "N") unset($medias[$key]);
		}
	}
	
	// If a PRIM Y is found nowhere, we just take the first link.
	// We can do that safely because the media items with PRIM N are already filtered out.
	if (!isset($primfile)) {
		foreach($medias as $key => $media) {
			$primfile = $media->filename;
			$thum = $media->useasthumb;
			$id = $media->xref;
			break;
		}
	}
	
	// If nothing found, just return false
	if (!isset($primfile)) return false;
	
	$object = array(); 
	$object["use_thum"] = $thum;
	$object["file"] = MediaFS::CheckMediaDepth($primfile);
	$object["thumb"] = MediaFS::ThumbnailFile(GedcomConfig::$MEDIA_DIRECTORY.RelativePathFile($object["file"]));
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
function GetRelationship(&$pid1, &$pid2, $followspouse=true, $maxlength=0, $ignore_cache=false, $path_to_find=0) {
	global $start_time, $NODE_CACHE_LENGTH, $USE_RELATIONSHIP_PRIVACY, $gm_username, $show_changes;

	if (!is_object($pid1) || !is_object($pid2)) {
		print "error";
		exit;
	}
	// print "<br /><br />Check ".$pid1->xref." and ".$pid2->xref."<br />";
	// print_r(PrivacyFunctions::$NODE_CACHE);
	//-- check the cache
	if ($USE_RELATIONSHIP_PRIVACY && !$ignore_cache) {
		if(isset(PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$pid2->xref])) {
			if (PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$pid2->xref] == "NOT FOUND") return false;
			if ($maxlength==0 || count(PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$pid2->xref]["path"])-1<=$maxlength) return PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$pid2->xref];
			else return false;
		}
		foreach($pid2->fams as $indexval => $fam) {
			$family =& Family::GetInstance($fam);
			foreach($family->children as $key => $child) {
				if(isset(PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$child->xref])) {
					if ($maxlength == 0 || count(PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$child->xref]["path"])+1 <= $maxlength) {
						$node1 = PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$child->xref];
						if ($node1 != "NOT FOUND") {
							$node1["path"][] = $pid2->xref;
							$node1["pid"] = $pid2->xref;
							if ($pid2->sex == "F") $node1["relations"][] = "mother";
							else $node1["relations"][] = "father";
						}
						PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$pid2->xref] = $node1;
						if ($node1=="NOT FOUND") return false;
						return $node1;
					}
					else return false;
				}
			}
		}

		if (!empty($NODE_CACHE_LENGTH) && $maxlength > 0) {
			if ($NODE_CACHE_LENGTH >= $maxlength) return false;
		}
	}
	//-- end cache checking

	
	$years = EstimateBD($pid2, "narrow");
	if (isset($years["birth"]["year"])) $byear2 = $years["birth"]["year"];
	else $byear2 = -1;

	//-- current path nodes
	$p1nodes = array();
	//-- ids visited
	$visited = array();

	//-- set up first node for person1
	$node1 = array();
	$node1["path"] = array();
	$node1["path"][] = $pid1->xref;
	$node1["length"] = 0;
	$node1["pid"] = $pid1->xref;
	$node1["relations"] = array();
	$node1["relations"][] = "self";
	$years = EstimateBD($pid1, "narrow");
	if (isset($years["birth"]["year"])) $byear1 = $years["birth"]["year"];
	else $byear1 = -1;
	$node1["byear"] = $byear1;
	$p1nodes[] = $node1;
	$visited[$pid1->xref] = true;

	$found = false;
	$count = 0;
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
		if (GedcomConfig::$TIME_LIMIT > 1 && $exectime > GedcomConfig::$TIME_LIMIT - 1) {
			print "<span class=\"Error\">".GM_LANG_timeout_error."</span>\n";
			return false;
		}
		if (count($p1nodes) == 0) {
			if ($maxlength != 0) {
				if (!isset($NODE_CACHE_LENGTH)) $NODE_CACHE_LENGTH = $maxlength;
				else if ($NODE_CACHE_LENGTH<$maxlength) $NODE_CACHE_LENGTH = $maxlength;
			}
			if (headers_sent()) {
				print "\n<!-- Relationship ".$pid1->xref."-".$pid2->xref." NOT FOUND | Visited ".count($visited)." nodes | Required $count iterations.<br />\n";
				PrintExecutionStats();
				print "-->\n";
			}
			PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$pid2->xref] = "NOT FOUND";
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
		if ($shortest == -1) return false;
		$node = $p1nodes[$shortest];
		if ($maxlength == 0 || count($node["path"]) <= $maxlength) {
			if ($node["pid"] == $pid2->xref) {
			}
			else {
//print "node: ".$node["pid"]." pid2: ".$pid2->xref."<br />";
				//-- hueristic values
				$fatherh = 1;
				$motherh = 1;
				$siblingh = 2;
				$spouseh = 2;
				$childh = 3;

				//-- generate heuristic values based of the birthdates of the current node and p2
				if (isset($node["byear"])) $byear1 = $node["byear"];
				else $byear1 = -1;
				
				if ($byear1 != -1 && $byear2 != -1) {
					$yeardiff = $byear1 - $byear2;
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
					else if ($yeardiff < 20) {
						$fatherh = 3;
						$motherh = 3;
						$siblingh = 1;
						$spouseh = 1;
						$childh = 5;
					}
					else if ($yeardiff < 60) {
						$fatherh = 1;
						$motherh = 1;
						$siblingh = 5;
						$spouseh = 2;
						$childh = 10;
					}
					else if ($yeardiff < 100) {
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
				// Get the ID's in the surrounding families. Also save the parents ID's for getting the grandparents
				$sql = "SELECT n.if_pkey as d_pid, n.if_role as d_role, i.i_gender as d_gender, d.d_year as d_bdate, m.if_role as n_role, n.if_fkey as d_fam FROM ".TBLPREFIX."individual_family as m LEFT JOIN ".TBLPREFIX."individual_family as n ON n.if_fkey=m.if_fkey LEFT JOIN ".TBLPREFIX."individuals AS i ON i.i_key=n.if_pkey LEFT JOIN ".TBLPREFIX."dates AS d ON (n.if_pkey=d.d_key AND (d.d_fact IS NULL OR d.d_fact='BIRT')) WHERE m.if_pkey='".DbLayer::EscapeQuery(JoinKey($node["pid"], GedcomConfig::$GEDCOMID))."' AND n.if_pkey<>m.if_pkey ORDER BY n_role, d_role";
				$res = NewQuery($sql);
				while ($row = $res->FetchAssoc()) {
					$rela = "";
					$dpid = SplitKey($row["d_pid"], "id");
					$dfam = SplitKey($row["d_fam"], "id");
					if (!isset($visited[$dpid])) {
						$nrole = $row["n_role"];
						$drole = $row["d_role"];
						// First check: node length 0 means add children, parents, spouses regardless of relationship privacy
						// Second check: with relationship privacy, don't add spouse to spouse.
						// Because of adding the wife in the first check, while spouses should not be followed, in the second check a check
						// for the role is added.
						// print "1: ".$pid1->xref." 2: ".$pid2->xref." nodepid: ".$node["pid"]." length: ".$node["length"]." role: ".(isset($node["relations"][1]) ? $node["relations"][1] : "")." found: ".$dpid."<br />";
						if (($followspouse || $node["length"] == 0) || (($drole != "S" || $nrole != "S") && (isset($node["relations"][1]) && $node["relations"][1] != "wife"))) {
							// print "added<br />";
							$ddate = $row["d_bdate"];
							$dgender = $row["d_gender"];
							if ($nrole == "C") {
								if ($drole == "C") {
									$rela = ($dgender == "M" ? "brother" : ($dgender == "F" ? "sister" : "sibling"));
									$length = $siblingh;
								}
								else {
									if ($dgender == "M") {
										$rela = "father";
										$length = $fatherh;
									}
									else {
										$rela = "mother";
										$length = $motherh;
									}
								}
							}
							else {
								if ($drole == "S") {
									$rela = $rela = ($dgender == "M" ? "husband" : "wife");
									$length = $spouseh;
								}
								else {
									$rela = ($dgender == "M" ? "son" : ($dgender == "F" ? "daughter" : "child"));
									$length = $childh;
								}
							}
							$node1 = $node;
							$node1["length"] += $length;
							$node1["byear"] = $ddate;
							$node1["path"][] = $dpid;
							$node1["pid"] = $dpid;
							$node1["relations"][] = $rela;
							$p1nodes[] = $node1;
							if ($node1["pid"] == $pid2->xref) {
								if ($path_to_find > 0) $path_to_find--;
								else {
									$found = true;
									$resnode = $node1;
								}
							}
							else $visited[$dpid] = true;
							if ($USE_RELATIONSHIP_PRIVACY) {
								PrivacyFunctions::$NODE_CACHE[$pid1->xref."-".$node1["pid"]] = $node1;
							}
						}
					}
				}
			}
		}
		unset($p1nodes[$shortest]);
	} //-- end while loop
	if (headers_sent()) {
		print "\n<!-- Relationship ".$pid1->xref."-".$pid2->xref." | Visited ".count($visited)." nodes | Required $count iterations.<br />\n";
		PrintExecutionStats();
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
		if ($entry != ".." && $entry != "." && $entry != "CVS" && $entry != ".svn" && $entry != "_svn" && is_dir("themes/$entry")) {
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


//-- this function will convert a digit number to a number in a different language
function ConvertNumber($num) {
	global $LANGUAGE;

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
				if (($i==1)&&($numstr{$i}!="0")) $zhnum = GM_LANG_10.$zhnum;
				if (($i==2)&&($numstr{$i}!="0")) $zhnum = GM_LANG_100.$zhnum;
				if (($i==3)&&($numstr{$i}!="0")) $zhnum = GM_LANG_1000.$zhnum;
				if (($i!=1)||($numstr{$i}!=1)) $zhnum = constant("GM_LANG_".$numstr{$i}).$zhnum;
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
	if (empty($from_name)) $from_name = GedcomConfig::$HOME_SITE_TEXT;
	
	// NOTE: Check if we send HTML or plain text
	$html=true;
	
	$styles = file_get_contents(GM_MAIL_STYLESHEET);
	
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
 * When $force is false, the function will first try to read the reports list from the INDEX_DIRECTORY."/reports.dat"
 * data file.  Otherwise the function will parse the report xml files and get the titles.
 * @param boolean $force	force the code to look in the directory and parse the files again
 * @return array 	The array of the found reports with indexes [title] [file]
 */
function GetReportList($force=false) {
	global $report_array, $vars, $xml_parser, $elementHandler, $LANGUAGE;

	$files = array();
	if (!$force) {
		//-- check if the report files have been cached
		if (file_exists(INDEX_DIRECTORY."/reports.dat")) {
			$reportdat = "";
			$fp = fopen(INDEX_DIRECTORY."/reports.dat", "r");
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
		if (strstr($entry, ".") == ".xml") {
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

	$fp = @fopen(INDEX_DIRECTORY."/reports.dat", "w");
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
		$family =& Family::GetInstance($match[$i][1], "", GedcomConfig::$GEDCOMID);
		$famrec = $family->gedrec;
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
 * remove any custom GM tags from the given gedcom record
 * custom tags include _GMU and _THUM
 * @param string $gedrec	the raw gedcom record
 * @return string		the updated gedcom record
 */
function RemoveCustomTags($gedrec, $remove="no") {
	if ($remove == "yes") {
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

function SumNums($val1, $val2) {
	return $val1 + $val2;
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
	
	$p1 = strpos($key,"[");
	if ($p1 === false) $id = $key;
	else $id = substr($key,0,$p1);
	if ($type == "id") return $id;
	if ($p1 === false) {
		if ($type == "ged") return get_gedcom_from_id(GedcomConfig::$GEDCOMID);
		if ($type == "gedid") return GedcomConfig::$GEDCOMID;
	}
	$p2 = strpos($key,"]");
	$ged = substr($key,$p1+1,$p2-$p1-1);
	if ($type == "ged") return get_gedcom_from_id($ged);
	if ($type == "gedid") return $ged;
}

function JoinKey($key, $gedid) {
	if (empty($key)) return "";
	if (strpos($key, "[") === false) return $key."[".$gedid."]";
	else print "Trying to join already joined key: ".$key."<br />";
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
				if (!is_array($value)){
					$qstring .= $key."=".$value."&";
				}
			}
		}
	}
	else {
		if (!empty($_POST)) {
			foreach($_POST as $key => $value) {
				if($key != "view" && $key != 'html' && $key != "NEWLANGUAGE" && $key != "changelanguage") {
					if (!is_array($value)){
						$qstring .= $key."=".$value."&";
					}
				}
			}
		}
	}
	// Remove the trailing ampersand to prevent it from being duplicated
	$qstring = substr($qstring, 0, -1);
	if ($encode) return urlencode(rtrim($qstring, "\x5C"));
	else return rtrim($qstring, "\x5C");
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
	
	// NOTE: Set length for the ID's
	$indi_length = strlen(GedcomConfig::$GEDCOM_ID_PREFIX);
	$fam_length = strlen(GedcomConfig::$FAM_ID_PREFIX);
	$source_length = strlen(GedcomConfig::$SOURCE_ID_PREFIX);
	$repo_length = strlen(GedcomConfig::$REPO_ID_PREFIX);
	$media_length = strlen(GedcomConfig::$MEDIA_ID_PREFIX);
	$note_length = strlen(GedcomConfig::$NOTE_ID_PREFIX);
	$submitter_length = 3;
	
	// NOTE: Check for individual ID
	if (substr($id, 0, $indi_length) == GedcomConfig::$GEDCOM_ID_PREFIX) return "INDI";
	else if (substr($id, 0, $submitter_length) == "SUB") return "SUBM";
	else if (substr($id, 0, $fam_length) == GedcomConfig::$FAM_ID_PREFIX) return "FAM";
	else if (substr($id, 0, $source_length) == GedcomConfig::$SOURCE_ID_PREFIX) return "SOUR";
	else if (substr($id, 0, $repo_length) == GedcomConfig::$REPO_ID_PREFIX) return "REPO";
	else if (substr($id, 0, $media_length) == GedcomConfig::$MEDIA_ID_PREFIX) return "OBJE";
	else if (substr($id, 0, $note_length) == GedcomConfig::$NOTE_ID_PREFIX) return "NOTE";
	else return "";
}


function ArrayCopy (&$array, &$copy, $depth=0) {

	if(!is_array($copy)) $copy = array();
	foreach($array as $k => $v) {
		if(is_array($v)) ArrayCopy($v,$copy[$k],++$depth);
		else $copy[$k] = $v;
	}
}
function CheckUTF8($string) {
       
    // From http://w3.org/International/questions/qa-forms-utf-8.html 
    return preg_match('%^(?: 
          [\x09\x0A\x0D\x20-\x7E]            # ASCII 
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte 
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs 
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte 
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates 
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3 
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15 
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16 
    )*$%xs', $string); 
   
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

	

/* this function will read a person's gedcom record and try to determine the persons birth and death
 * year. See the rules in the code
 * 
 * @author Genmod team
 * @param string $indirec the raw gedcom record
 * @return array with 2 year values, pid and type (as 
 * requested or "true" is found a true year)
 * Possible values for type: true, narrow and wide
 */
function EstimateBD(&$person, $type) {
	global $CHECK_CHILD_DATES, $MAX_ALIVE_AGE;

	// Init values
	$dates = array();
		
	if (!is_object($person) || $person->isempty) return false;
	
	$dates["pid"] = $person->xref;
	
	$cyear = date("Y");
	
	// -- check for a death record
	// We cannot use $person->drec here
	$drec = GetSubRecord(1, "1 DEAT", $person->gedrec);
	if ($drec != "") {
		if (preg_match("/1 DEAT Y/", $drec)>0) $deathyear = $cyear;
		else {
			$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $drec, $match);
			if ($ct>0) $truedeathyear = $match[1];
		}
	}

	//-- check for birth record
	$brec = GetSubRecord(1, "1 BIRT", $person->gedrec);
	if ($brec != "") {
		$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $brec, $match);
		if ($ct>0) $truebirthyear = $match[1];
	}

	// if we have true dates, return now
	if (isset($truebirthyear) && isset($truedeathyear)) {
		$dates["birth"]["year"] = $truebirthyear;
		$dates["birth"]["type"] = "true";
		$dates["death"]["year"] = $truedeathyear;
		$dates["death"]["type"] = "true";
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
	$facts = GetAllSubrecords($person->gedrec, "CHAN", false, false, false);
	foreach ($facts as $key => $factrec) {
		$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $factrec, $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			if (strstr($match[$i][0], "@#DHEBREW@")===false) {
				$byear = $match[$i][1];
				if ($ffactyear > $byear) $ffactyear = $byear;
				if ($lfactyear < $byear) $lfactyear = $byear;
			}
		}
	}

	// If we found no dates then check the dates of close relatives.
	if($CHECK_CHILD_DATES) {
		foreach($person->famc as $key => $famid) {
			$family =& Family::GetInstance($famid["famid"]);
			if ($family->husb_id != "") {
				$brec = GetSubRecord(1, "1 BIRT", $family->husb->gedrec);
				$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $brec, $match, PREG_SET_ORDER);
				// loop for later if also facts are scanned
				for($i=0; $i<$ct; $i++) {
					$fbyear = $match[$i][1];
				}
				$drec = GetSubRecord(1, "1 DEAT", $family->husb->gedrec);
				if (preg_match("/1 DEAT Y/", $drec)>0) $fddate = $cyear;
				else {
					$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $drec, $match);
					if ($ct>0) $fdyear = $match[1];
				}
			}
			if ($family->wife_id != "") {
				$brec = GetSubRecord(1, "1 BIRT", $family->wife->gedrec);
				$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $brec, $match, PREG_SET_ORDER);
				// loop for later if also facts are scanned
				for($i=0; $i<$ct; $i++) {
					$mbyear = $match[$i][1];
				}
				$drec = GetSubRecord(1, "1 DEAT", $family->wife->gedrec);
				if (preg_match("/1 DEAT Y/", $drec)>0) $mddate = $cyear;
				else {
					$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $drec, $match);
					if ($ct>0) $mdyear = $match[1];
				}
			}
		}
		$children = array();
		// For each family in which this person is a spouse...
		$fmarryear = 9999;
		$lmarryear = 0;
		$fcbyear = 9999;
		$lcbyear = 0;
		foreach ($person->fams as $key => $famid) {
			$family =& Family::GetInstance($famid);
			//-- check for marriage date
			$marr_fact = GetSubRecord(1, "1 MARR", $family->gedrec);
			if (!empty($marr_fact)) {
				$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $marr_fact, $bmatch, PREG_SET_ORDER);
				for($h=0; $h<$bt; $h++) {
					$byear = $bmatch[$h][1];
					if ($fmarryear > $byear) $fmarryear = $byear;
					if ($lmarryear < $byear) $lmarryear = $byear;
				}
			}
			
			//-- check for divorce date
			$div_fact = GetSubRecord(1, "1 DIV", $family->gedrec);
			if (!empty($div_fact)) {
				$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $div_fact, $bmatch, PREG_SET_ORDER);
				for($h=0; $h<$bt; $h++) {
					$byear = $bmatch[$h][1];
					if ($fmarryear > $byear) $fmarryear = $byear;
					if ($lmarryear < $byear) $lmarryear = $byear;
				}
			}
			
			// Check for the spouses date
			if ($family->husb_id != "" && $family->husb_id != $person->xref) $spouse = "husb";
			else if ($family->wife_id != "" && $family->wife_id != $person->xref) $spouse = "wife";
			if (isset($spouse)) {
				// Check the spouses birthrec
				$brec = GetSubRecord(1, "1 BIRT", $family->$spouse->brec);
				$bs = preg_match("/\d DATE.*\s(\d{3,4})\s/", $brec, $bmatch);
				if ($bs) $sbyear = $bmatch[1];

				// Check the spouses deathrec
				$drec = GetSubRecord(1, "1 DEAT", $family->$spouse->drec);
				$ds = preg_match("/\d DATE.*\s(\d{3,4})\s/", $drec, $dmatch);
				if ($ds) $sdyear = $dmatch[1];
			}
			
			// Get the set of children
			foreach ($family->children as $key2 => $child) {
				// Get each child's object and keep it. This will gather all children from all spousefamilies
				$children[] = $child;

				// Check each child's dates (for now only birth)
				$brec = GetSubRecord(1, "1 BIRT", $child->gedrec);
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
		foreach($children as $key => $child) {
			// For each family in which this person is a spouse...
			foreach ($child->fams as $key2 => $famid) {
				$family =& Family::GetInstance($famid);
				// Get the set of children
				foreach ($family->children as $key3 => $fchild) {
					// Check each grandchild's dates
					$brec = GetSubRecord(1, "1 BIRT", $fchild->gedrec);
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
	if ($type == "narrow") {	
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
			if($CHECK_CHILD_DATES) {
				// *     3. Mothers birth year + 50
				if (isset($mbyear) && (!isset($birthyear) || ($mbyear + 50) < $birthyear)) $birthyear = $mbyear + 50;
				// *     4. Fathers birth year + 50
				if (isset($fbyear) && (!isset($birthyear) || ($fbyear + 50) < $birthyear)) $birthyear = $fbyear + 50;
				// *     5. Spouses birth year
				if (isset($sbyear) && !isset($birthyear)) $birthyear = $sbyear;
				// *     6. First childs birth year - 15
				if ($fcbyear != 9999 && (!isset($birthyear) || ($fcbyear - 15) < $birthyear)) $birthyear = $fcbyear - 15;
				// *     7. First grandchilds birth year - 30
				if ($fgcbyear != 9999 && (!isset($birthyear) || ($fgcbyear - 30) < $birthyear)) $birthyear = $fgcbyear - 30;
				// *     8. First marriage year - 20
				if ($fmarryear != 9999 && (!isset($birthyear) || ($fmarryear - 20) < $birthyear)) $birthyear = $fmarryear - 20;
				// *     9. Mothers death year
				if (isset($mdyear) && (!isset($birthyear) || $mdyear < $birthyear)) $birthyear = $mdyear;
				// *     10. Fathers death year
				if (isset($fdyear) && (!isset($birthyear) || $fdyear < ($birthyear - 1))) $birthyear = $fdyear;
			}
			if (isset($birthyear)) {
				$dates["birth"]["year"] = ($birthyear > $cyear ? $cyear : $birthyear);
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
			if ($lfactyear != 0 && (!isset($deathyear) || $lfactyear > $deathyear)) $deathyear = $lfactyear;
			if($CHECK_CHILD_DATES) {
				// *     3. Mothers birth year + 15
				if (isset($mbyear) && (!isset($deathyear) || ($mbyear + 15) > $deathyear)) $deathyear = $mbyear + 15;
				// *     4. Fathers birth year + 15
				if (isset($fbyear) && (!isset($deathyear) || ($fbyear + 15) > $deathyear)) $deathyear = $fbyear + 15;
				// *     5. Spouses death year
				if (isset($sdyear) && !isset($deathyear)) $deathyear = $sdyear;
				// *     5. Last childs birth year
				if ($lcbyear != 0 && (!isset($deathyear) || $lcbyear > $deathyear)) $deathyear = $lcbyear;
				// *     6. First grandchilds birth year - 15
				if ($fgcbyear != 9999 && (!isset($deathyear) || $fgcbyear > $deathyear)) $deathyear = $fgcbyear;
				// *     7. Latest marriage year and 8. Latest divorce year
				if ($lmarryear != 0 && (!isset($deathyear) || $lmarryear > $deathyear)) $deathyear = $lmarryear;
			}
			if (isset($deathyear)) {
				$dates["death"]["year"] = $deathyear;
				$dates["death"]["type"] = $type;
			}
		}
	}
	if ($type == "wide") {	
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
			if($CHECK_CHILD_DATES) {
				// *     3. Mothers birth year + 15
				if (isset($mbyear) && (!isset($birthyear) || ($mbyear + 15) < $birthyear)) $birthyear = $mbyear + 15;
				// *     4. Fathers birth year + 15
				if (isset($fbyear) && (!isset($birthyear) || ($fbyear + 15) < $birthyear)) $birthyear = $fbyear + 15;
				// *     5. Spouses birth year
				if (isset($sbyear) && !isset($birthyear)) $birthyear = $sbyear;
				// *     5. First childs birth year - 50
				if ($fcbyear != 9999 && (!isset($birthyear) || ($fcbyear - 50) < $birthyear)) $birthyear = $fcbyear - 50;
				// *     6. First grandchilds birth year - 100
				if ($fgcbyear != 9999 && (!isset($birthyear) || ($fgcbyear - 100) < $birthyear)) $birthyear = $fgcbyear-100;
				// *     7. Marriage year - 70
				if ($fmarryear != 9999 && (!isset($birthyear) || ($fmarryear - 70) < $birthyear)) $birthyear = $fmarryear - 70;
				// *     BUT no later than the death year of mother or father
				if (isset($mdyear) && isset($birthyear) && $mdyear < $birthyear) $birthyear = $mdyear;
				if (isset($fdyear) && isset($birthyear) && $fdyear-1 < $birthyear) $birthyear = $fdyear-1;
			}
			if (isset($birthyear)) {
				$dates["birth"]["year"] = ($birthyear > $cyear ? $cyear : $birthyear);
				$dates["birth"]["type"] = $type;
			}
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
			if($CHECK_CHILD_DATES) {
				// *     3. Mothers birth year + $MAX_ALIVE_AGE + 15
				if (isset($mbyear) && (!isset($deathyear) || ($mbyear + 15 + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $mbyear + 15 + $MAX_ALIVE_AGE;
				// *     4. Fathers birth year + $MAX_ALIVE_AGE + 15
				if (isset($fbyear) && (!isset($deathyear) || ($fbyear + 15 + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $fbyear + 15 + $MAX_ALIVE_AGE;
				// *     5. Spouses death year
				if (isset($sdyear) && !isset($deathyear)) $deathyear = $sdyear;
				// *     5. Last childs birth year + $MAX_ALIVE_AGE - 15
				if ($lcbyear != 0 && (!isset($deathyear) || ($lcbyear - 15 + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $fcbyear - 15 + $MAX_ALIVE_AGE;
				// *     6. First grandchilds birth year + $MAX_ALIVE_AGE - 30
				if ($fgcbyear != 9999 && (!isset($deathyear) || ($fgcbyear - 30 + $MAX_ALIVE_AGE) > $deathyear)) $deathyear = $fgcbyear - 30 + $MAX_ALIVE_AGE;
				// *     7. Marriage year + $MAX_ALIVE_AGE - 20
				if ($lmarryear != 0 && (!isset($deathyear) || ($lmarryear - 20 + $MAX_ALIVE_AGE) < $deathyear)) $deathyear = $lmarryear - 20 + $MAX_ALIVE_AGE;
			}
			if (isset($deathyear)) {
				$dates["death"]["year"] = $deathyear;
				$dates["death"]["type"] = $type;
			}
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

// Get the recordlevel
function GetRecLevel($gedrec) {
	
	if (empty($gedrec)) return -1;
	else return substr($gedrec, 0, 1);
}

// Get the recordtype
function GetFactType($factrec) {
	
	$gt = preg_match("/1 @.+@ (\w+)/", $factrec, $gmatch);
	if ($gt > 0) return $gmatch[1];
	else return false;
}

/* Cloning an object */
function CloneObj($obj) {
	if (version_compare(phpversion(), '5.0') < 0)  $cloneobj = $obj;
	else $cloneobj = clone($obj);
	return $cloneobj;
}

function CheckEmailAddress($address, $checkmx = true) {
	static $checked;
	
	// Setup the cache
	if (!isset($checked)) $checked = array();
	
	// Check the format
	$valid = preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*([a-zA-Z0-9])+@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+\.([a-zA-Z0-9_-])+$/', $address);
	if (!$valid) return false;
	
	// Return if no MX record check
	if (!$checkmx) return true;
	
	// Check DNS MX records
	$mt = preg_match("/(.+)@(.+)/", $address, $match);
	if ($mt>0) {
		$host = trim($match[2]);
		// If in cache, return immediately
		if (in_array($host, $checked)) return true;
		// First try a realtime check to see if the domain exists
		if (function_exists("dns_get_mx")) {
			$ip = dns_get_mx($host, $mx_records);
			if ($ip == false) {
				$host = "www.".$host;
				// If in cache, return immediately
				if (in_array($host, $checked)) return true;
				$ip = dns_get_mx($host, $mx_records);
				if ($ip == false) return false;
				else {
					$checked[] = $host;
					return true;
				}
			}
			else {
				$checked[] = $host;
				return true;
			}
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
function SwitchGedcom($gedid="") {
	static $orgged;
	
	// If we are already there, stay there.
	if ($gedid == GedcomConfig::$GEDCOMID) return;
	
	// Switching back if nothing ever changed or back to the original.
	if (empty($gedid)) {
		if (!isset($orgged)) return;
		else $gedid = $orgged;
	}

	if (!is_numeric($gedid)) $gedid = get_id_from_gedcom($gedid);
		
	// Switch to something else.
	if (is_numeric($gedid)) {
		// Save the old value
		if (!isset($orgged)) $orgged = GedcomConfig::$GEDCOMID;
	}
	else return;
	
	// Make the switch
	PrivacyController::ReadPrivacy($gedid);
	GedcomConfig::ReadGedcomConfig($gedid);
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
	global $CONFIG_VARS;

	$offset = 0;	
	while (preg_match("/#(\w+)#/", $text, $match, 0, $offset) > 0) {

		$varname = $match[1];
		global $$varname;
		
		// check if config variable
		if (in_array($varname, $CONFIG_VARS)) $text = preg_replace("/$match[0]/", GM_LANG_embedvar_not_allowed, $text);
		
		// check language variable
		else if (defined("GM_LANG_".$varname)) $text = preg_replace("/$match[0]/", constant("GM_LANG_".$varname), $text);
		
		// check variable
		else if (isset($$varname)) $text = preg_replace("/$match[0]/", $$varname, $text);
		
		// check constant
		else if (defined($varname)) $text = preg_replace("/$match[0]/", constant($varname), $text);
		
		// check constant with prefix
		else if (defined("GM_".$varname)) $text = preg_replace("/$match[0]/", constant("GM_".$varname), $text);
		
		// if nothing is found, we skip this match (can be anything, like HTML) and search from there on
		else {
			$offset = strpos($text, $match[0]);
			if ($offset === false) break;
			$offset += 1;
		}
	}
	return $text;
}	

// Returns the object only if it exists, otherwise false
function ConstructObject($pid, $type="", $gedid="", $data_array="") {
	
	$pid = trim($pid);
	if (empty($pid)) return false;
	if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
	$type = strtoupper($type);
	$object = null;
	
	if (!empty($type) && in_array($type, array("INDI", "FAM", "SOUR", "REPO", "ASSO", "NOTE", "SUBM", "HEAD"))) {
		if ($type == "SOUR") $object =& Source::GetInstance($pid, $data_array, $gedid);
		elseif ($type == "REPO") $object =& Repository::GetInstance($pid, $data_array, $gedid);
		elseif ($type == "OBJE") $object =& MediaItem::GetInstance($pid, $data_array, $gedid);
		elseif ($type == "NOTE") $object =& Note::GetInstance($pid, $data_array, $gedid);
		elseif ($type == "ASSO" || $type == "INDI") $object =& Person::GetInstance($pid, $data_array, $gedid);
		elseif ($type == "FAM") $object =& Family::GetInstance($pid, $data_array, $gedid);
		elseif ($type == "SUBM") $object =& Submitter::GetInstance($pid, $data_array, $gedid);
		elseif ($type == "HEAD") $object =& Header::GetInstance($pid, $data_array, $gedid);
		if (is_object($object) && !$object->isempty) return $object;
		else return false;
	}
	else {
		// To minimize queries, we first start to guess what record type we must retrieve
		$tried = "";
		if (substr($pid,0,strlen(GedcomConfig::$GEDCOM_ID_PREFIX)) == GedcomConfig::$GEDCOM_ID_PREFIX) {
			$object =& Person::GetInstance($pid, $data_array, $gedid);
			$tried = "indi";
		}
		else if (substr($pid,0,strlen(GedcomConfig::$FAM_ID_PREFIX)) == GedcomConfig::$FAM_ID_PREFIX) {
			$object =& Family::GetInstance($pid, $data_array, $gedid);
			$tried = "fam";
		}
		else if (substr($pid,0,strlen(GedcomConfig::$SOURCE_ID_PREFIX)) == GedcomConfig::$SOURCE_ID_PREFIX) {
			$object =& Source::GetInstance($pid, $data_array, $gedid);
			$tried = "sour";
		}
		else if (substr($pid,0,strlen(GedcomConfig::$MEDIA_ID_PREFIX)) == GedcomConfig::$MEDIA_ID_PREFIX) {
			$object =& MediaItem::GetInstance($pid, $data_array, $gedid);
			$tried = "media";
		}
		else if (substr($pid,0,strlen(GedcomConfig::$NOTE_ID_PREFIX)) == GedcomConfig::$NOTE_ID_PREFIX) {
			$object =& Note::GetInstance($pid, $data_array, $gedid);
			$tried = "note";
		}
		else if (substr($pid,0,strlen(GedcomConfig::$REPO_ID_PREFIX)) == GedcomConfig::$REPO_ID_PREFIX) {
			$object =& Repository::GetInstance($pid, $data_array, $gedid);
			$tried = "repo";
		}
		if (is_object($object) && !$object->isempty) return $object;
		else {
			if ($tried != "indi") {
				$object =& Person::GetInstance($pid, $data_array, $gedid);
				if (is_object($object) && !$object->isempty) return $object;
			}
			if ($tried != "fam") {
				$object =& Family::GetInstance($pid, $data_array, $gedid);
				if (is_object($object) && !$object->isempty) return $object;
			}
			if ($tried != "sour") {
				$object =& Source::GetInstance($pid, $data_array, $gedid);
				if (is_object($object) && !$object->isempty) return $object;
			}
			if ($tried != "repo") {
				$object =& Repository::GetInstance($pid, $data_array, $gedid);
				if (is_object($object) && !$object->isempty) return $object;
			}
			if ($tried != "media") {
				$object =& MediaItem::GetInstance($pid, $data_array, $gedid);
				if (is_object($object) && !$object->isempty) return $object;
			}
			if ($tried != "note") {
				$object =& Note::GetInstance($pid, $data_array, $gedid);
				if (is_object($object) && !$object->isempty) return $object;
			}
			$object =& Submitter::GetInstance($pid, $data_array, $gedid);
			if (is_object($object) && !$object->isempty) return $object;
			else return false;
		}
	}
}
	
// Returns the object only if it exists, otherwise false
function ReConstructObject($pid, $type, $gedid="", $data_array="") {
	
	if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
	$type = strtoupper($type);
	$object = null;
	
	if (!empty($type) && in_array($type, array("INDI", "FAM", "SOUR", "REPO", "OBJE", "ASSO", "NOTE", "SUBM", "HEAD"))) {
		if ($type == "SOUR") $object =& Source::NewInstance($pid, $data_array, $gedid);
		elseif ($type == "REPO") $object =& Repository::NewInstance($pid, $data_array, $gedid);
		elseif ($type == "OBJE") $object =& MediaItem::NewInstance($pid, $data_array, $gedid);
		elseif ($type == "NOTE") $object =& Note::NewInstance($pid, $data_array, $gedid);
		elseif ($type == "ASSO" || $type == "INDI") $object =& Person::NewInstance($pid, $data_array, $gedid);
		elseif ($type == "FAM") $object =& Family::NewInstance($pid, $data_array, $gedid);
		elseif ($type == "SUBM") $object =& Submitter::NewInstance($pid, $data_array, $gedid);
		elseif ($type == "HEAD") $object =& Header::NewInstance($pid, $data_array, $gedid);
		if (is_object($object) && !$object->isempty) return $object;
	}
	return false;
}

/**
 * Put all characters in a string in lowercase
 *
 * This function is a replacement for strtolower() and will put all characters in lowercase
 *
 * @author	eikland
 * @param	string $value the text to be converted to lowercase
 * @return	string $value_lower the converted text in lowercase
 * @todo look at function performance as it is much slower than strtolower
 */
function Str2Lower($value) {
	global $language_settings,$LANGUAGE, $ALPHABET_upper, $ALPHABET_lower;
	global $all_ALPHABET_upper, $all_ALPHABET_lower;

	//-- get all of the upper and lower alphabets as a string
	if (!isset($all_ALPHABET_upper)) {
		$all_ALPHABET_upper = "";
		$all_ALPHABET_lower = "";
		foreach ($ALPHABET_upper as $l => $up_alphabet){
			$lo_alphabet = $ALPHABET_lower[$l];
			$ll = strlen($lo_alphabet);
			$ul = strlen($up_alphabet);
			if ($ll < $ul) $lo_alphabet .= substr($up_alphabet, $ll);
			if ($ul < $ll) $up_alphabet .= substr($lo_alphabet, $ul);
			$all_ALPHABET_lower .= $lo_alphabet;
			$all_ALPHABET_upper .= $up_alphabet;
		}
	}

	$value_lower = "";
	if (MB_FUNCTIONS) $len = mb_strlen($value);
	else $len = strlen($value);

	//-- loop through all of the letters in the value and find their position in the
	//-- upper case alphabet.  Then use that position to get the correct letter from the
	//-- lower case alphabet.
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 232, 233);
	for($i=0; $i<$len; $i++) {
		if (!MB_FUNCTIONS) {
			$letter = substr($value, $i, 1);
			$ord = ord($letter);
			if (in_array($ord, $ord_value2)) {
				$i++;
				$letter .= substr($value, $i, 1);
			}
			else if (in_array($ord, $ord_value3)) {
				$i++;
				$letter .= substr($value, $i, 2);
				$i++;
			}
			$pos = strpos($all_ALPHABET_upper, $letter);
			if ($pos!==false) {
				$letter = substr($all_ALPHABET_lower, $pos, strlen($letter));
			}
			$value_lower .= $letter;
		}
		else {
			$letter = mb_substr($value, $i, 1);
			$pos = mb_strpos($all_ALPHABET_upper, $letter);
			if ($pos!==false) {
				$letter = mb_substr($all_ALPHABET_lower, $pos, 1);
			}
			$value_lower .= $letter;
		}
	}
	return $value_lower;
}

/**
 * Put all characters in a string in uppercase
 *
 * This function is a replacement for strtoupper() and will put all characters in uppercase
 *
 * @author Genmod Development Team
 * @param	string $value the text to be converted to uppercase
 * @return	string $value_upper the converted text in uppercase
 * @todo look at function performance as it is much slower than strtoupper
 */
function Str2Upper($value) {
	global $language_settings,$LANGUAGE, $ALPHABET_upper, $ALPHABET_lower;
	global $all_ALPHABET_upper, $all_ALPHABET_lower;

	//-- get all of the upper and lower alphabets as a string
	if (!isset($all_ALPHABET_upper)) {
		$all_ALPHABET_upper = "";
		$all_ALPHABET_lower = "";
		foreach ($ALPHABET_upper as $l => $up_alphabet){
			$lo_alphabet = $ALPHABET_lower[$l];
			$ll = strlen($lo_alphabet);
			$ul = strlen($up_alphabet);
			if ($ll < $ul) $lo_alphabet .= substr($up_alphabet, $ll);
			if ($ul < $ll) $up_alphabet .= substr($lo_alphabet, $ul);
			$all_ALPHABET_lower .= $lo_alphabet;
			$all_ALPHABET_upper .= $up_alphabet;
		}
	}

	$value_upper = "";
	if (MB_FUNCTIONS) $len = mb_strlen($value);
	else $len = strlen($value);

	//-- loop through all of the letters in the value and find their position in the
	//-- lower case alphabet.  Then use that position to get the correct letter from the
	//-- upper case alphabet.
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 232, 233);
	for($i=0; $i<$len; $i++) {
		if (!MB_FUNCTIONS) {
			$letter = substr($value, $i, 1);
			$ord = ord($letter);
			if (in_array($ord, $ord_value2)) {
				$i++;
				$letter .= substr($value, $i, 1);
			}
			else if (in_array($ord, $ord_value3)) {
				$i++;
				$letter .= substr($value, $i, 2);
				$i++;
			}
			$pos = strpos($all_ALPHABET_lower, $letter);
			if ($pos!==false) {
				$letter = substr($all_ALPHABET_upper, $pos, strlen($letter));
			}
			$value_upper .= $letter;
		}
		else {
			$letter = mb_substr($value, $i, 1);
			$pos = mb_strpos($all_ALPHABET_lower, $letter);
			if ($pos!==false) {
				$letter = mb_substr($all_ALPHABET_upper, $pos, 1);
			}
			$value_upper .= $letter;
		}
	}
	return $value_upper;
}

/**
 * Convert a string to UTF8
 *
 * This function is a replacement for utf8_decode()
 *
 * @author	http://www.php.net/manual/en/function.utf8-decode.php
 * @param	string $in_str the text to be converted
 * @return	string $new_str the converted text
 */
function SmartUtf8Decode($in_str) {
	$new_str = html_entity_decode(htmlentities($in_str, ENT_COMPAT, 'UTF-8'));
	$new_str = str_replace("&oelig;", "\x9c", $new_str);
	$new_str = str_replace("&OElig;", "\x8c", $new_str);
	return $new_str;
}

function ParseRobotsTXT() {
	
	$robots = array();
	$printline = "";
	if (!file_exists("robots.txt")) {
		return $robots;
	}
	else {
		$r = file_get_contents("robots.txt");
		$r = rtrim($r)."\n";
		$agents = preg_split("/User-agent: /", $r);
		foreach ($agents as $agent => $line) {
			$lines = preg_match_all("/(.*)\n/", $line, $match, PREG_SET_ORDER);
			$maynot = false;
			foreach ($match as $key => $rule) {
				if ($key == 0) {
					$robot = (trim($rule[0]) == "*" ? "robots" : trim($rule[0]));
//					print "found robot ".$robot."<br />";
				}
				else {
//					print "check ".$rule[0]." ".$_SERVER["SCRIPT_NAME"]."<br />";
					if (strpos($rule[0], "/\n") !== false && strpos($rule[0], "Disallow:") !== false) {
						$maynot = true;
					}
					if (strpos($rule[0], "/".basename($_SERVER["SCRIPT_NAME"])) !== false && strpos($rule[0], "Disallow:") !== false) {
						$maynot = true;
					}
				}
			}
			if ($maynot) {
//				print "found for ".$robot." page ". $rule[0];
				$printline .= "<meta name=\"".$robot."\" content=\"".GedcomConfig::$META_ROBOTS_DENY."\" />\n";
			}
			else {
				if (isset($robot)) $printline .= "<meta name=\"".$robot."\" content=\"".GedcomConfig::$META_ROBOTS."\" />\n";
			}
		}
	}
	return $printline;
}
	
?>