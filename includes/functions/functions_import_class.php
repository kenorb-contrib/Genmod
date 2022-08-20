<?php
/**
 * Functions used Tools to cleanup and manipulate Gedcoms before they are imported
 *
 * $Id: functions_import_class.php 29 2022-07-17 13:18:20Z Boudewijn $
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
 * @package Genmod
 * @subpackage Tools
 * @see validategedcom.php
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class ImportFunctions {
	
	/**
	 * check if Gedcom needs HEAD cleanup
	 *
	 * Find where position of the 0 HEAD gedcom start element, if one does not exist then complain
	 * about the file not being a Gedcom.  If it is not at the first position in the file then
	 * we need to trim off all of the extra stuff before the 0 HEAD
	 * @return boolean	returns true if we need to cleanup the head, false if we don't
	 * @see head_cleanup()
	 */
	public function NeedHeadCleanup($fcontents) {
	
		$pos1 = strpos($fcontents, "0 HEAD");
		if ($pos1 > 0) return true;
		else return false;
	}
	
	/**
	 * cleanup the HEAD
	 *
	 * Cleans up the Gedcom header making sure that the 0 HEAD record is the very first thing in the file.
	 * @return boolean	whether or not the cleanup was successful
	 * @see need_head_cleanup()
	 */
	public function HeadCleanup() {
		global $fcontents;
	
		$pos1 = strpos($fcontents, "0 HEAD");
		if ($pos1 > 0) {
			$fcontents = substr($fcontents, $pos1);
			return true;
		}
		return false;
	}
	
	/**
	 * check if there are double line endings
	 *
	 * Normally a gedcom should not have empty lines, this will check if the file has any empty lines in it
	 * @return boolean	return true if the cleanup is needed
	 * @see line_endings_cleanup()
	 */
	public function NeedLineEndingsCleanup($fcontents) {
	
		$ct = preg_match("/\r\n(\r\n)+/", $fcontents);
		$ct += preg_match("/(\r\r+|\n\n+)/", $fcontents);
		if ($ct>0) {
			return true;
		}
		return false;
	}
	
	/**
	 * cleanup line endings
	 *
	 * this will remove any empty lines from the file
	 * @return boolean	returns true if the operation was successful
	 * @see need_line_endings_cleanup()
	 */
	public function LineEndingsCleanup() {
		global $fcontents;
	
		$ct = preg_match("/\r\n(\r\n)+/", $fcontents);
		$ct += preg_match("/\r\r+/", $fcontents);
		$ct += preg_match("/\n\n+/", $fcontents);
		if ($ct>0) {
			$fcontents = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $fcontents);
			return true;
		}
		else return false;
	}
	
	/**
	 * check if we need to cleanup the places
	 *
	 * some programs, most notoriously FTM, put data in the PLAC field when it should be on the same line
	 * as the event.  For example:<code>1 SSN
	 * 2 PLAC 123-45-6789</code> Should really be: <code>1 SSN 123-45-6789</code>
	 * this function checks if this exists
	 * @return boolean	returns true if the cleanup is needed
	 * @see place_cleanup()
	 */
	public function NeedPlaceCleanup($fcontents) {
		
		$ct = preg_match_all ("/^1 (CAST|DSCR|EDUC|IDNO|NATI|NCHI|NMR|OCCU|PROP|RELI|SSN|TITL|_MILI|_FA1|_FA2|_FA3|_FA4|_FA5|_FA6)(\s*)$[\s]+(^2 TYPE(.*)[\s]+)?(^2 DATE(.*)[\s]+)?^2 PLAC (.*)$/m",$fcontents,$matches, PREG_SET_ORDER);
		if($ct>0)
		  return $matches[0];
		return false;
	}
	
	/**
	 * clean up the bad places found by the need_place_cleanup() function
	 * @return boolean	returns true if cleanup was successful
	 * @see need_place_cleanup()
	 */
	public function PlaceCleanup() {
		global $fcontents;
	
	//searchs for '1 CAST|DSCR|EDUC|IDNO|NATI|NCHI|NMR|OCCU|PROP|RELI|SSN|TITL #chars\n'
	//				    'optional 2 TYPE #chars\n'
	//						'optional 2 DATE #chars\n'
	//						'2 PLAC #chars'
	// and replaces the 1 level #chars with the PLAC #chars and blanks out the PLAC
	$fcontents = preg_replace("/^1 (CAST|DSCR|EDUC|IDNO|NATI|NCHI|NMR|OCCU|PROP|RELI|SSN|TITL|_MILI|_FA1|_FA2|_FA3|_FA4|_FA5|_FA6)(\s*)$[\s]+(^2 TYPE(.*)[\s]+)?(^2 DATE(.*)[\s]+)?^2 PLAC (.*)$/m", self::FixReplaceVal('$1','$7','$3','$5'),$fcontents);
	return true;
	}
	
	//used to create string to be replaced back into GEDCOM
	public function FixReplaceVal($val1,$val7,$val3,$val5) {
		
		$val = "1 ".$val1." ".trim($val7)."\n";
		
		//trim off trailing spaces
		$val3 = rtrim($val3);
		if(!empty($val3)) $val = $val.$val3;
	
		//trim off trailing spaces
		$val5 = rtrim($val5);
		if(!empty($val5)) $val = $val.$val5;
	
		//$val = $val."\r\n2 PLAC";
		return trim($val);
	}
	
	
	/**
	 * check if we need to cleanup the dates
	 *
	 * Valid gedcom dates are in the form DD MMM YYYY (ie 01 JAN 2004).  However many people will enter
	 * dates in an incorrect format.  This function checks if dates have been entered incorrectly.
	 * This function will detect dates in the form YYYY-MM-DD, DD-MM-YYYY, and MM-DD-YYYY.  It will also 
	 * look for \ / - and . as delimeters.
	 * @return boolean	returns true if the cleanup is needed
	 * @see date_cleanup()
	 */
	public function NeedDateCleanup($fcontents) {
		
	  $ct = preg_match_all ("/\n\d DATE[^\d]+(\d\d\d\d)[\/\\\\\-\.](\d\d)[\/\\\\\-\.](\d\d)/",$fcontents,$matches, PREG_SET_ORDER);
		if($ct>0) {
			//print_r($matches);
		  	return $matches[0];
	  	}
		else
		{
	  		$ct = preg_match_all ("/\n\d DATE[^\d]+(\d\d)[\/\\\\\-\.](\d\d)[\/\\\\\-\.](\d\d\d\d)/",$fcontents,$matches, PREG_SET_ORDER);
			if($ct>0) {
				//print_r($matches);
				$matches[0]["choose"] = true;
				return $matches[0];
			}
			else {
				$ct = preg_match_all ("/\n\d DATE ([^\d]+) [0-9]{1,2}, (\d\d\d\d)/",$fcontents,$matches, PREG_SET_ORDER);
				if($ct>0) {
					//print_r($matches);
					return $matches[0];
				}
				else {
					$ct = preg_match_all("/\n\d DATE (\d\d)[^\s]([^\d]+)[^\s](\d\d\d\d)/", $fcontents, $matches, PREG_SET_ORDER);
					if($ct>0) {
						//print_r($matches);
						return $matches[0];
					}
				}
			}
		}
		return false;
	}
	
	private function ChangeMonth($monval)
	{
			if($monval=="01") return "JAN";
			else if($monval=="02") return "FEB";
			else if($monval=="03") return "MAR";
			else if($monval=="04") return "APR";
			else if($monval=="05") return "MAY";
			else if($monval=="06") return "JUN";
			else if($monval=="07") return "JUL";
			else if($monval=="08") return "AUG";
			else if($monval=="09") return "SEP";
			else if($monval=="10") return "OCT";
			else if($monval=="11") return "NOV";
			else if($monval=="12") return "DEC";
			return $monval;
	}
	
	private function FixDate($datestr) {
		$date = ParseDate($datestr);
		if (isset($date[0])) return $date[0]["day"]." ".Str2Upper($date[0]["month"])." ".$date[0]["year"];
		else return $datestr;
	}
	/**
	 * clean up the bad dates found by the need_date_cleanup() function
	 * @return boolean	returns true if cleanup was successful
	 * @see need_date_cleanup()
	 */
	public function DateCleanup($dayfirst=1) {
		global $fcontents;
	
		// convert all dates with anything but spaces as delimmeters
		$fcontents = preg_replace("/\n(\d)\sDATE (\d\d)[^\s]([^\d]+)[^\s](\d\d\d\d)/", "\n$1 DATE $2 $3 $4", $fcontents);
	  	//convert all dates in YYYY-MM-DD or YYYY/MM/DD or YYYY\MM\DD format to DD MMM YYYY format
		$fcontents = preg_replace("/\n(\d)\sDATE[^\d]+(\d\d\d\d)[\/\\\\\-\.](\d\d)[\/\\\\\-\.](\d\d)/e", "'\n$1 DATE $4 '.self::ChangeMonth('$3').' $2'", $fcontents);
		$fcontents = preg_replace("/\n(\d)\sDATE ([^\d]+ [0-9]{1,2}, \d\d\d\d)/e", "'\n$1 DATE '.self::FixDate('$2').''", $fcontents);
	
		//day first in date format
		if($dayfirst==1)
		{
	  	//convert all dates in DD-MM-YYYY or DD/MM/YYYY or DD\MM\YYYY to DD MMM YYYY format
		  $fcontents = preg_replace("/\n(\d)\sDATE[^\d]+(\d\d)[\/\\\\\-\.](\d\d)[\/\\\\\-\.](\d\d\d\d)/e", "'\n$1 DATE $2 '.self::ChangeMonth('$3').' $4'", $fcontents);
		}
		else if ($dayfirst==2) //month first
		{
		  //convert all dates in MM-DD-YYYY or MM/DD/YYYY or MM\DD\YYYY to DD MMM YYYY format
			$fcontents = preg_replace("/\n(\d)\sDATE[^\d]+(\d\d)[\/\\\\\-\.](\d\d)[\/\\\\\-\.](\d\d\d\d)/e", "'\n$1 DATE $3 '.self::ChangeMonth('$2').' $4'", $fcontents);
		}
		return true;
	}
	
	/**
	 * check if we need to cleanup the MAC style line endings
	 *
	 * GM runs better with DOS (\r\n) or UNIX (\n) style line endings.  This function checks if 
	 * Mac (\r) style line endings are used in the gedcom file.
	 * @return boolean	returns true if the cleanup is needed
	 * @see macfile_cleanup()
	 */
	public function NeedMacfileCleanup($fcontents) {
		
		//check to see if need macfile cleanup
		$ct = preg_match_all ("/\x0d[\d]/m",$fcontents,$matches);
		if($ct > 0)	return true;
		return false;
	}
	
	/**
	 * clean up the Mac (\r) line endings found by the need_macfile_cleanup() function
	 * @return boolean	returns true if cleanup was successful
	 * @see need_macfile_cleanup()
	 */
	public function MacfileCleanup() {
		global $fcontents;
		
		//replace all only \r (MAC files) with \r\n (DOS files)
		$fcontents = preg_replace("/\x0d([\d])/","\x0d\x0a$1", $fcontents);
		return true;
	}
	
	
	/**
	 * Check for ANSI encoded file
	 *
	 * Check the gedcom for an ansi encoded file to convert to UTF-8
	 * @return boolean 	returns true if the file claims to be ANSI encoded
	 * @see convert_ansi_utf8()
	 */
	public function IsAnsi($fcontents) {
	
		return preg_match("/1 CHAR (ANSI|ANSEL)/", $fcontents);
	}
	
	/**
	 * Convert an ANSI encoded file to UTF8
	 *
	 * converts an ANSI or ANSEL encoded file to UTF-8
	 * @see is_ansi()
	 */
	public function ConvertAnsiUtf8() {
		global $fcontents;
	
		$fcontents = utf8_encode($fcontents);
		$fcontents = preg_replace("/1 CHAR (ANSI|ANSEL)/", "1 CHAR UTF-8", $fcontents);
	}
	
	/**
	 * function that sets up the html required to run the progress bar
	 * @param long $FILE_SIZE	the size of the file
	 */
	public function SetupProgressBar($FILE_SIZE) {
		global $gedid, $timelimit;
		?>
		<script type="text/javascript">
		<!--
		function complete_progress(time, exectext, go_pedi, go_welc) {
			progress = document.getElementById("progress_header");
			if (progress) progress.innerHTML = '<?php print "<span class=\"Error\"><b>".GM_LANG_import_complete."</b></span><br />";?>'+exectext+' '+time+' '+"<?php print GM_LANG_sec; ?>";
			progress = document.getElementById("link1");
			if (progress) progress.innerHTML = '<a href="pedigree.php?gedid=<?php print $gedid; ?>">'+go_pedi+'</a>';
			progress = document.getElementById("link2");
			if (progress) progress.innerHTML = '<a href="index.php?command=gedcom&gedid=<?php print $gedid; ?>">'+go_welc+'</a>';
			progress = document.getElementById("link3");
			if (progress) progress.innerHTML = '<a href="editgedcoms.php">'+"<?php print GM_LANG_manage_gedcoms."</a>"; ?>";
		}
		function wait_progress() {
			progress = document.getElementById("progress_header");
			if (progress) progress.innerHTML = '<?php print GM_LANG_please_be_patient; ?>';
		}
		
		var FILE_SIZE = <?php print $FILE_SIZE; ?>;
		var TIME_LIMIT = <?php print $timelimit; ?>;
		function update_progress(bytes, time) {
			perc = Math.round(100*(bytes / FILE_SIZE));
			if (perc>100) perc = 100;
			progress = document.getElementById("progress_div");
			if (progress) {
				progress.style.width = perc+"%";
				progress.innerHTML = perc+"%";
			}
			perc = Math.round(100*(time / TIME_LIMIT));
			if (perc>100) perc = 100;
			progress = document.getElementById("time_div");
			if (progress) {
				progress.style.width = perc+"%";
				progress.innerHTML = perc+"%";
			}
		}
		//-->
		</script>
		<?php
		// NOTE: Print the progress bar for the GEDCOM file
			print "<div class=\"UploadGedcomGProgressBox\">\n";
				print "<b>".GM_LANG_import_progress."</b>";
				print "<div class=\"UploadGedcomInnerProgressBar\">\n";
					print "<div id=\"progress_div\" class=\"UploadGedcomProgressBar\">";
					if (isset($_SESSION["TOTAL_BYTES"])) {
						print "\n<script type=\"text/javascript\"><!--\nupdate_progress(".$_SESSION["TOTAL_BYTES"].",".$_SESSION["exectime_start"].");\n//-->\n</script>\n";
					}
					else print "1%";
					print "</div>\n";
				print "</div>\n";
			print "</div>\n";
			
			
			// NOTE: Print the progress bar for the time
			print "<div class=\"UploadGedcomTProgressBox\">\n";
				if ($timelimit == 0) print "<b>".GM_LANG_time_limit." ".GM_LANG_none."</b>";
				else print "<b>".GM_LANG_time_limit." ".$timelimit." ".GM_LANG_sec."</b>";
				print "<div class=\"UploadGedcomInnerProgressBar\">\n";
					print "<div id=\"time_div\" class=\"UploadGedcomProgressBar\">1%</div>\n";
				print "</div>\n";
			print "</div>\n";
			
			// NOTE: Print the links after import
			print "<div class=\"UploadGedcomProgressLinks\">";
				print "<div id=\"link1\">&nbsp;</div>";
				print "<div id=\"link2\">&nbsp;</div>";
				print "<div id=\"link3\">&nbsp;</div>";
			print "</div>";
		flush();
		@ob_flush();
	}
	
	/**
	 * delete a gedcom from the database
	 *
	 * deletes all of the imported data about a gedcom from the database
	 * @param string $FILE	the gedcom to remove from the database
	 */
	public function EmptyDatabase($gedid) {
	
		if (!GedcomConfig::$KEEP_ACTIONS) {
			$sql = "DELETE FROM ".TBLPREFIX."actions WHERE a_file='".$gedid."'";
			$res = NewQuery($sql);
		}
		$sql = "DELETE FROM ".TBLPREFIX."individuals WHERE i_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."asso WHERE as_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."families WHERE f_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."sources WHERE s_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."other WHERE o_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."places WHERE p_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."placelinks WHERE pl_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."names WHERE n_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."dates WHERE d_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."media WHERE m_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."media_mapping WHERE mm_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."individual_family WHERE if_file='".$gedid."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."soundex WHERE s_file='".$gedid."'";
		$res = NewQuery($sql);
		// Flush the caches
		GedcomConfig::ResetCaches($gedid);
	}
	
	/**
	 * import record into database
	 *
	 * this function will parse the given gedcom record and add it to the database
	 * @param string $indirec the raw gedcom record to parse
	 * @param boolean $update whether or not this is an updated record that has been accepted
	 */
	public function ImportRecord($indirec, $update, $gedfile) {
		
		if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
		if (strlen(trim($indirec)) ==  0) return false;
		//-- import different types of records
		$ct = preg_match("/0 @(.*)@ ([A-Z_]+)/", $indirec, $match);
		if ($ct > 0) {
			$gid = $match[1];
			$type = trim($match[2]);
		}
		else {
			$ct = preg_match("/0 (.*)/", $indirec, $match);
			if ($ct>0) {
				$gid = trim($match[1]);
				$type = trim($match[1]);
			}
			else {
				print GM_LANG_invalid_gedformat; print "<br /><pre>$indirec</pre>\n";
			}
		}
	
		//-- remove double @ signs
		$indirec = preg_replace("/@+/", "@", $indirec);
	
		// remove heading spaces
		$indirec = preg_replace("/\n(\s*)/", "\n", $indirec);
	
		//-- if this is an import from an online update then import the places
		// NOTE: What's the difference? Oh... in uploadgedcom it's also done. So only do it here in case of updates
		if ($update) {
			self::UpdatePlaces($gid, $type, $indirec, true, $gedfile);
			self::UpdateDates($gid, $indirec, $gedfile);
	
			//-- Also add the MM links to the DB
			$lines = preg_split("/[\r\n]+/", trim($indirec));
			$ct_lines = count($lines);
			foreach($lines as $key => $line) {
				$ct = preg_match_all("/([1-9])\sOBJE\s@(.+)@/", $line, $match);
				for ($i=0;$i<$ct;$i++) {
					$rec = $match[0][$i];
	//				print "rec: ".$rec."<br />";
					$level = $match[1][$i];
	//				print "level: ".$level."<br />";
					$media = $match[2][$i];
	//				print "media: ".$media."<br />";
					$gedrec = GetSubRecord($level, $rec, $indirec, 1);
	//				print "gedrec: ".$gedrec."<br />";
					self::AddDBLink($media, $gid, $gedrec, $gedfile, -1, $type);
				}
			}
		}
		$indirec = self::UpdateMedia($gid, $indirec, $update, $gedfile);
		
		if (!$update) $indirec = self::ExtractTodo($gid, $type, $indirec, $gedfile);
		
		// Insert the source links
		// Recalculate $gid as it may have changed in UpdateMedia
		$ct = preg_match_all("/([1-9])\sSOUR\s@(.+)@/", $indirec, $match);
		if ($ct > 0) {
			$cc = preg_match("/0 @(.*)@ ([A-Z_]+)/", $indirec, $cmatch);
			if ($cc > 0) {
				$gid = $cmatch[1];
				$type = trim($cmatch[2]);
			}
			else {
				$cc = preg_match("/0 (.*)/", $indirec, $cmatch);
				if ($cc>0) {
					$gid = trim($cmatch[1]);
					$type = trim($cmatch[1]);
				}
			}
		}
		$kgid = JoinKey($gid, $gedfile);
		for ($i=0;$i<$ct;$i++) {
			$rec = $match[0][$i];
			$level = $match[1][$i];
			$sour = $match[2][$i];
			$gedrec = GetSubRecord($level, $rec, $indirec, 1);
			$result = self::AddSourceLink($sour, $gid, $gedrec, $gedfile, $type);
		}
		
		// Insert the other links
		// Recalculate $gid as it may have changed in UpdateMedia
		$ct = preg_match_all("/([1-9])\s(NOTE|REPO)\s@(.+)@/", $indirec, $match);
		if ($ct > 0) {
			$cc = preg_match("/0 @(.*)@ ([A-Z_]+)/", $indirec, $cmatch);
			if ($cc > 0) {
				$gid = $cmatch[1];
				$type = trim($cmatch[2]);
			}
			else {
				$cc = preg_match("/0 (.*)/", $indirec, $cmatch);
				if ($cc>0) {
					$gid = trim($cmatch[1]);
					$type = trim($cmatch[1]);
				}
			}
		}
		for ($i=0;$i<$ct;$i++) {
			$rec = $match[0][$i];
			$level = $match[1][$i];
			$note = $match[3][$i];
			$result = self::AddOtherLink($note, $gid, $type, $gedfile);
		}
		if ($type == "INDI" || $type == "FAM") {
			if (preg_match("/[1-9]\sASSO\s@/", $indirec, $match) > 0) {
				$recs = GetAllSubrecords($indirec, "CHAN", false, false, false);
				foreach ($recs as $key => $record) {
					$ct = preg_match_all("/^1\sASSO\s@(.+)@/", $record, $match);
					if ($ct > 0) {
						$fact = "";
						for ($i=0;$i<$ct;$i++) {
							$pid1 = $match[1][$i];
							$rela = trim(GetGedcomValue("RELA", 2, $record, "", false));
							$resn = trim(GetGedcomValue("RESN", 2, $record, "", false));
							self::AddAssoLink(JoinKey($pid1, $gedfile), $kgid, $type, $fact, $rela, $resn, $gedfile);
						}
					}
					$ct = preg_match_all("/\n2\sASSO\s@(.+)@/", $record, $match);
					// The resn value is valid for all asso's for this fact
					$resn = trim(GetGedcomValue("RESN", 2, $record, "", false));
					if ($ct > 0) {
						$ct2 = preg_match("/1\s(.+)\s/", $record, $match2);
						$fact = trim($match2[1]);
						for ($i=0;$i<$ct;$i++) {
							$pid1 = $match[1][$i];
							$asso = GetSubRecord(2, "2 ASSO", $record, $i+1);
							$rela = trim(GetGedcomValue("RELA", 3, $asso, "", false));
							self::AddAssoLink(JoinKey($pid1, $gedfile), $kgid, $type, $fact, $rela, $resn, $gedfile);
						}
					}
				}
			}
		}
		
		
		if ($type == "INDI") {
			$indirec = EditFunctions::CleanupTagsY($indirec);
			$ct = preg_match_all("/1 FAMS @(.*)@/", $indirec, $match, PREG_SET_ORDER);
			$sfams = "";
			$order = 1;
			$kgid = JoinKey($gid, $gedfile);
			for($j=0; $j<$ct; $j++) {
				$sql = "INSERT INTO ".TBLPREFIX."individual_family VALUES(NULL, '".$kgid."', '".JoinKey($match[$j][1], $gedfile)."', '".$order."', 'S', '', '', '', '".$gedfile."') ON DUPLICATE KEY UPDATE if_order='".$order."'";
				$res = NewQuery($sql);
				$sfams .= $match[$j][1].";";
				$order++;
			}
			$ct = preg_match_all("/1 FAMC @(.*)@/", $indirec, $match, PREG_SET_ORDER);
			$cfams = "";
			$i=1;
			for($j=0; $j<$ct; $j++) {
				// Get the primary status
				$famcrec = GetSubRecord(1, "1 FAMC", $indirec, $i);
				$ct2 = preg_match("/2\s+_PRIMARY\s(.+)/", $famcrec, $pmatch);
				if ($ct2>0) $prim = trim($pmatch[1]);
				else $prim = "";
				// Get the pedi status
				$ct2 = preg_match("/2\s+PEDI\s+(adopted|birth|foster|sealing)/", $famcrec, $pmatch);
				$ped = "";
				if ($ct2>0) $ped = substr(trim($pmatch[1]), 0, 1);
				if ($ped == "b") $ped = "";
				// Get the stat status
				$ct2 = preg_match("/2\s+STAT\s+(challenged|proven|disproven)/", $famcrec, $pmatch);
				$stat = "";
				if ($ct2>0) $stat = substr(trim($pmatch[1]),0 ,1);
				// Insert the stuff in the DB
				$sql = "INSERT INTO ".TBLPREFIX."individual_family VALUES(NULL, '".$kgid."', '".JoinKey($match[$j][1], $gedfile)."', '', 'C', '".$prim."', '".$ped."', '".$stat."', '".$gedfile."') ON DUPLICATE KEY UPDATE if_prim='".$prim."', if_pedi='".$ped."', if_stat='".$stat."'";
				$res = NewQuery($sql);
				$cfams .= $match[$j][1].";";
				$i++;
			}
			$isdead = -1;
			$indi = array();
			$names = NameFunctions::GetIndiNames($indirec, true);
			$soundex_codes = self::GetSoundexStrings($names, true, $indirec);
			foreach($names as $indexval => $name) {
				$sql = "INSERT INTO ".TBLPREFIX."names VALUES('0', '".DbLayer::EscapeQuery($gid)."[".$gedfile."]','".DbLayer::EscapeQuery($gid)."','".$gedfile."','".DbLayer::EscapeQuery($name[0])."','".DbLayer::EscapeQuery($name[1])."','".DbLayer::EscapeQuery($name[5])."', '".DbLayer::EscapeQuery($name[2])."', '".DbLayer::EscapeQuery($name[3])."','".DbLayer::EscapeQuery($name[4])."')";
				$res = NewQuery($sql);
				if ($res) $res->FreeResult();
			}
			$indi["names"] = $names;
			$indi["isdead"] = $isdead;
			$indi["gedcom"] = $indirec;
			$indi["gedfile"] = $gedfile;
			$s = GetGedcomValue("SEX", 1, $indirec, '', false);
			if (empty($s)) $indi["sex"] = "U";
			else $indi["sex"] = $s;
			if (GedcomConfig::$USE_RIN) {
				$ct = preg_match("/1 RIN (.*)/", $indirec, $match);
				if ($ct>0) $rin = trim($match[1]);
				else $rin = $gid;
				$indi["rin"] = $rin;
			}
			else $indi["rin"] = $gid;
			
			$sql = "INSERT INTO ".TBLPREFIX."individuals VALUES ('".$kgid."', '".DbLayer::EscapeQuery($gid)."','".DbLayer::EscapeQuery($indi["gedfile"])."','".DbLayer::EscapeQuery($indi["rin"])."', -1,'".DbLayer::EscapeQuery($indi["gedcom"])."','".$indi["sex"]."')";
			$res = NewQuery($sql);
			if ($res) $res->FreeResult();
			$sqlstr = "";
			$first = true;
			foreach ($soundex_codes as $stype => $ncodes) {
				foreach ($ncodes as $nametype => $tcodes) {
					foreach ($tcodes as $key => $code) {
						if (!$first) $sqlstr .= ", ";
						$first = false;
						$sqlstr .= "(NULL, '".$kgid."', '".$gedfile."', '".$stype."', '".$nametype."', '".$code."')";
					}
				}
			}
			if (!empty($sqlstr)) {
				$sql = "INSERT INTO ".TBLPREFIX."soundex VALUES ".$sqlstr;
				$res = NewQuery($sql);
				if ($res) $res->FreeResult();
			}
			else WriteToLog("ImportRecord-&gt; Soundex: Indi without soundex codes encountered: ".$kgid, "W", "G", $gedfile);
		}
		else if ($type == "FAM") {
			$indirec = EditFunctions::CleanupTagsY($indirec);
			$parents = array();
			$ct = preg_match("/1 HUSB @(.*)@/", $indirec, $match);
			if ($ct>0) $parents["HUSB"]=$match[1];
			else $parents["HUSB"]=false;
			$ct = preg_match("/1 WIFE @(.*)@/", $indirec, $match);
			if ($ct>0) $parents["WIFE"]=$match[1];
			else $parents["WIFE"]=false;
			$ct = preg_match_all("/\d CHIL @(.*)@/", $indirec, $match, PREG_SET_ORDER);
			$chil = "";
			// NOTE: only the children are added/updated here.
			for($j=0; $j<$ct; $j++) {
				$chil .= $match[$j][1].";";
				$sql = "INSERT INTO ".TBLPREFIX."individual_family VALUES(NULL, '".Joinkey($match[$j][1], $gedfile)."', '".JoinKey(DbLayer::EscapeQuery($gid), $gedfile)."', '".($j+1)."', 'C', '', '', '', '".$gedfile."') ON DUPLICATE KEY UPDATE if_order='".($j+1)."'";
				$res = NewQuery($sql);
			}
			$fam = array();
			$fam["HUSB"] = $parents["HUSB"];
			$fam["WIFE"] = $parents["WIFE"];
			$fam["CHIL"] = $chil;
			$fam["gedcom"] = $indirec;
			$fam["gedfile"] = $gedfile;
			$sql = "INSERT INTO ".TBLPREFIX."families (f_key, f_id, f_file, f_husb, f_wife, f_chil, f_gedrec, f_numchil) VALUES ('".DbLayer::EscapeQuery($gid)."[".DbLayer::EscapeQuery($fam["gedfile"])."]','".DbLayer::EscapeQuery($gid)."','".DbLayer::EscapeQuery($fam["gedfile"])."','".DbLayer::EscapeQuery(JoinKey($fam["HUSB"], $fam["gedfile"]))."','".DbLayer::EscapeQuery(JoinKey($fam["WIFE"], $fam["gedfile"]))."','".DbLayer::EscapeQuery($fam["CHIL"])."','".DbLayer::EscapeQuery($fam["gedcom"])."','".DbLayer::EscapeQuery($ct)."')";
			$res = NewQuery($sql);
			if ($res) $res->FreeResult();
		}
		else if ($type=="SOUR") {
			$et = preg_match("/1 ABBR (.*)/", $indirec, $smatch);
			if ($et>0) $name = $smatch[1];
			$tt = preg_match("/1 TITL (.*)/", $indirec, $smatch);
			if ($tt>0) $name = $smatch[1];
			if (empty($name)) $name = $gid;
			$subindi = preg_split("/1 TITL /",$indirec);
			if (count($subindi)>1) {
				$pos = strpos($subindi[1], "\n1", 0);
				if ($pos) $subindi[1] = substr($subindi[1],0,$pos);
				$ct = preg_match_all("/2 CON[C|T] (.*)/", $subindi[1], $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$name = trim($name);
					if (GedcomConfig::$WORD_WRAPPED_NOTES) $name .= " ".$match[$i][1];
					else $name .= $match[$i][1];
				}
			}
			$sql = "INSERT INTO ".TBLPREFIX."sources VALUES ('".Joinkey($gid, $gedfile)."', '".DbLayer::EscapeQuery($gid)."','".$gedfile."','".DbLayer::EscapeQuery($name)."','".DbLayer::EscapeQuery($indirec)."')";
			$res = NewQuery($sql);
			if ($res) $res->FreeResult();
		}
		else if ($type=="OBJE") {
			//-- don't duplicate OBJE records
			//-- OBJE records are imported by UpdateMedia function
		}
		else if (preg_match("/_/", $type)==0) {
			if ($type=="HEAD") {
				$ct=preg_match("/1 DATE (.*)/", $indirec, $match);
				if ($ct == 0) {
					$indirec = trim($indirec);
					$indirec .= "\r\n1 DATE ".date("d")." ".date("M")." ".date("Y");
				}
			}
			$sql = "INSERT INTO ".TBLPREFIX."other VALUES ('".Joinkey($gid, $gedfile)."', '".DbLayer::EscapeQuery($gid)."','".$gedfile."','".DbLayer::EscapeQuery($type)."','".DbLayer::EscapeQuery($indirec)."')";
			$res = NewQuery($sql);
			if ($res) $res->FreeResult();
		}
		return $gid;
	}
	
	/**
	 * Adds a new link into the database.
	 *
	 * Replace the gedrec for an existing link record.
	 *
	 * @param string $media The gid of the record to be updated in the form Mxxxx.
	 * @param string $indi The gid that this media is linked to Ixxx Fxxx ect.
	 * @param string $gedrec The gedcom record as a string without the gid.
	 * @param string $ged The gedcom file this action is to apply to.
	 * @param integer $order The order that this record should be displayed on the gid. If not supplied then
	 *                       the order is not replaced.
	 */
	 
	 // This function is used in ImportRecord only
	 
	private function AddDBLink($media, $indi, $gedrec, $gedid, $order=-1, $rectype) {
	
		// if no preference to order find the number of records and add to the end
		if ($order=-1) {
			$sql = "SELECT * FROM ".TBLPREFIX."media_mapping WHERE mm_file='".$gedid."' AND mm_gid='".addslashes($indi)."'";
			$res = NewQuery($sql);
			$ct = $res->NumRows();
			$order = $ct + 1;
		}
	
		// add the new media link record
		$sql = "INSERT INTO ".TBLPREFIX."media_mapping VALUES(NULL,'".addslashes($media)."','".addslashes($indi)."','".addslashes($order)."','".$gedid."','".addslashes($gedrec)."', '".$rectype."')";
		$res = NewQuery($sql);
		if ($res) {
			WriteToLog("AddDBLink-&gt; New media link added to the database: ".$media, "I", "G", $gedid);
			return true;
		}
		else {
			WriteToLog("AddDBLink-&gt;There was a problem adding media record: ".$media, "E", "G", $gedid);
			return false;
		}
	}
	
	/**
	 * extract all places from the given record and insert them
	 * into the places table
	 * @param string $indirec
	 */
	public function UpdatePlaces($gid, $type, $indirec, $update, $gedfile) {
		global $placecache;
		
		if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
	// NOTE: $update=false causes double places to be added. Force true
	$update = true;
		if (!isset($placecache)) $placecache = array();
		//-- import all place locations
		$pt = preg_match_all("/\d PLAC (.*)/", $indirec, $match, PREG_SET_ORDER);
		for($i=0; $i<$pt; $i++) {
			$place = trim($match[$i][1]);
			// Split on chinese comma 239 188 140
			$place = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $place);
			$places = preg_split("/,/", $place);
			$secalp = array_reverse($places);
			$parent_id = 0;
			$level = 0;
			foreach($secalp as $indexval => $place) {
				$place = trim($place);
				$place=preg_replace('/\\\"/', "", $place);
				$place=preg_replace("/[\><]/", "", $place);
				if (empty($parent_id)) $parent_id=0;
				$key = strtolower($place."_".$level."_".$parent_id);
				$addgid = true;
				if (isset($placecache[$key])) {
					$parent_id = $placecache[$key][0];
					if (strpos($placecache[$key][1], $gid.",")===false) {
						$placecache[$key][1] = "$gid,".$placecache[$key][1];
						$sql = "INSERT INTO ".TBLPREFIX."placelinks VALUES($parent_id, '".DbLayer::EscapeQuery($gid)."', '".$type."', '".$gedfile."')";
						$res = NewQuery($sql);
					}
				}
				else {
					$skip = false;
					if ($update) {
	//					print "Search: ".$place." ".$level."<br />";
						$sql = "SELECT p_id FROM ".TBLPREFIX."places WHERE p_place LIKE '".DbLayer::EscapeQuery($place)."' AND p_level=$level AND p_parent_id='$parent_id' AND p_file='".$gedfile."'";
						$res = NewQuery($sql);
						if ($res->NumRows()>0) {
	//						if ($level == 0) print "Hit on: ".$place." ".$level."<br />";
							$row = $res->FetchAssoc();
							$res->FreeResult();
							$parent_id = $row["p_id"];
							$skip=true;
							$placecache[$key] = array($parent_id, $gid.",");
							$sql = "INSERT INTO ".TBLPREFIX."placelinks VALUES($parent_id, '".DbLayer::EscapeQuery($gid)."', '".$type."', '".$gedfile."')";
							$res = NewQuery($sql);
						}
					}
					if (!$skip) {
						if (!isset($place_id)) {
							$place_id = GetNextId("places", "p_id");
						}
						else $place_id++;
	//					if ($level == 0) print "Insert: ".$place." ".$level."<br />";
						$sql = "INSERT INTO ".TBLPREFIX."places VALUES($place_id, '".DbLayer::EscapeQuery($place)."', $level, '$parent_id', '".$gedfile."')";
						$res = NewQuery($sql);
						$parent_id = $place_id;
						$placecache[$key] = array($parent_id, $gid.",");
						$sql = "INSERT INTO ".TBLPREFIX."placelinks VALUES($place_id, '".DbLayer::EscapeQuery($gid)."', '".$type."', '".$gedfile."')";
						$res = NewQuery($sql);
					}
				}
				$level++;
			}
		}
		return $pt;
	}

	/**
	 * extract all date info from the given record and insert them
	 * into the dates table
	 * @param string $indirec
	 */
	public function UpdateDates($gid, $indirec, $gedfile) {
		
		if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
		$count = 0;
		// NOTE: Check if the record has dates, if not return
		$pt = preg_match("/\d DATE (.*)/", $indirec, $match);
		if ($pt==0) return 0;
		
		// NOTE: Get all facts
		preg_match_all("/(\d)\s(\w+)\r\n/", $indirec, $facts, PREG_SET_ORDER);
		
		$fact_count = array();
		// NOTE: Get all the level 1 records
		foreach($facts as $key => $subfact) {
			$fact = $subfact[2];
			
			if (!isset($fact_count[$fact])) $fact_count[$fact] = 1;
			else $fact_count[$fact]++;
			$subrec = GetSubRecord($subfact[1], $fact, $indirec, $fact_count[$fact]);
			$count_dates = preg_match("/\d DATE (.*)/", $subrec, $dates);
			if ($count_dates > 0) {
				$datestr = trim($dates[1]);
				$date = ParseDate($datestr);
				if (empty($date[0]["day"])) $date[0]["day"] = 0;
				$sql = "INSERT INTO ".TBLPREFIX."dates VALUES('".DbLayer::EscapeQuery($date[0]["day"])."','".DbLayer::EscapeQuery(Str2Upper($date[0]["month"]))."','".DbLayer::EscapeQuery($date[0]["year"])."','".DbLayer::EscapeQuery($fact)."','".DbLayer::EscapeQuery($gid)."','".DbLayer::EscapeQuery(JoinKey($gid, GedcomConfig::$GEDCOMID))."','".$gedfile."',";
				if (isset($date[0]["ext"])) {
					preg_match("/@#D(.*)@/", $date[0]["ext"], $extract_type);
					$date_types = array("@#DGREGORIAN@","@#DJULIAN@","@#DHEBREW@","@#DFRENCH R@", "@#DROMAN@", "@#DUNKNOWN@");
					if (isset($extract_type[0]) && in_array($extract_type[0], $date_types)) $sql .= "'".$extract_type[0]."','')";
					else $sql .= "NULL, '".$date[0]["ext"]."')";
				}
				else $sql .= "NULL, '')";
				$res = NewQuery($sql);
				$count++;
			}
		}
		return $count;
	}

	/**
	 * import media items from record
	 * @return string	an updated record
	 */
	private function UpdateMedia($gid, $indirec, $update, $gedfile) {
		global $media_count, $found_ids; // $found_ids must be global, as it is saved to/from $_SESSION during import
		
		if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
		
		if (!isset($media_count)) $media_count = 0;
		if (!isset($found_ids)) $found_ids = array();
		
		// Get the type of record we have here
		$ct = preg_match("/0 @.+@ (\w+)/", $indirec, $tmatch);
		if ($ct) $rectype = $tmatch[1];
		else {
			$r = substr($indirec, 0, 6);
			if ($r != "0 HEAD" && $r != "0 TRLR") WriteToLog("UpdateMedia-&gt; Unknown record type encountered on import: ".$indirec, "E", "G", $gedfile);
			return $indirec;
		}
		
		//-- handle level 0 media OBJE seperately
		$ct = preg_match("/0 @(.*)@ OBJE/", $indirec, $match);
		if ($ct>0) {
			$old_m_media = $match[1];
			$found = false;
			// If it's an update from edit, the ID does not change.
			if ($update) {
				$new_m_media = $old_m_media;
			}
			else {
				// It's a new record. If we already assigned a new ID, set it here.
				if (array_key_exists($match[1], $found_ids)) {
					$new_m_media = $found_ids[$match[1]]["new_id"];
					$found = true;
				}
				else {
					// If not, get a new ID
					// Check if the own ID is already assigned
					$exist = false;
					foreach($found_ids as $key => $id) {
						if ($id["new_id"] == $match[1]) {
							$exist = true;
							break;
						}
					}
					// If not, keep the old ID. If assigned, generate a new one						
					if ($exist) $new_m_media = EditFunctions::GetNewXref("OBJE");
					else $new_m_media = $match[1];
					$found_ids[$match[1]]["old_id"] = $match[1];
					$found_ids[$match[1]]["new_id"] = $new_m_media;
				}
			}
			// Change the ID of the mediarecord and get some field values
			$indirec = preg_replace("/@".$old_m_media."@/", "@".$new_m_media."@", $indirec);
			$title = GetGedcomValue("TITL", 2, $indirec);
			if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", 1, $indirec);
			$file = GetGedcomValue("FILE", 1, $indirec);
			// If the file is a link, normalize it
			if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
			// Eliminate a heading dot from the filename
			$file = RelativePathFile(MediaFS::CheckMediaDepth($file));
			// Get the extension
			$et = preg_match("/(\.\w+)$/", $file, $ematch);
			$ext = "";
			if ($et>0) $ext = substr(trim($ematch[1]),1);
			if ($found) {
				// It's the actual values for an inserted stub record. We only update the fields with the true values
				$sql = "UPDATE ".TBLPREFIX."media SET m_ext = '".DbLayer::EscapeQuery($ext)."', m_titl = '".DbLayer::EscapeQuery($title)."', m_mfile = '".DbLayer::EscapeQuery($file)."', m_gedrec = '".DbLayer::EscapeQuery($indirec)."' WHERE m_media = '".$new_m_media."' AND m_file='".$gedfile."'";
				$res = NewQuery($sql);
			}
			else {
				// It's completely new, we insert a new record
				$sql = "INSERT INTO ".TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_mfile, m_file, m_gedrec)";
				$sql .= " VALUES('0', '".DbLayer::EscapeQuery($new_m_media)."', '".DbLayer::EscapeQuery($ext)."', '".DbLayer::EscapeQuery($title)."', '".DbLayer::EscapeQuery($file)."', '".$gedfile."', '".DbLayer::EscapeQuery($indirec)."')";
				$res = NewQuery($sql);
			}
			$found = false;
			return $indirec;
		}
		
		// Here we handle all records BUT level 0 media records.
		//-- check to see if there are any media records
		//-- if there aren't any media records then don't look for them just return
		$pt = preg_match("/\d OBJE/", $indirec, $match);
		if ($pt==0) return $indirec;
		//-- go through all of the lines and replace any local
		//--- OBJE to referenced OBJEs
		$newrec = "";
		$lines = preg_split("/[\r\n]+/", trim($indirec));
		$ct_lines = count($lines);
		$inobj = false;
		$processed = false;
		$objlevel = 0;
		$objrec = "";
		$count = 1;
		foreach($lines as $key => $line) {
			if (!empty($line)) {
				// NOTE: Match lines that resemble n OBJE @0000@
				// NOTE: Renumber the old ID to a new ID and save the old ID
				// NOTE: in case there are more references to it
				if (preg_match("/^[1-9]\sOBJE\s(.*)$/", $line, $match) != 0) {
					// NOTE: Check if objlevel greater is than 0, if so then store the current object record
					if ($objlevel > 0) {
						$title = GetGedcomValue("TITL", $objlevel+1, $objrec);
						if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", $objlevel+2, $objrec);
						$file = GetGedcomValue("FILE", $objlevel+1, $objrec);
						// If the file is a link, normalize it
						if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
						$file = RelativePathFile(MediaFS::CheckMediaDepth($file));
						
						// Add a check for existing file here
						$em = EditFunctions::CheckDoubleMedia($file, $title, $gedfile);
						if (!$em) $m_media = EditFunctions::GetNewXref("OBJE");
						else $m_media = $em;
						
						// Get the extension
						$et = preg_match("/(\.\w+)$/", $file, $ematch);
						$ext = "";
						if ($et>0) $ext = substr(trim($ematch[1]),1);
						// NOTE: Make sure 1 OBJE @M1@ is treated correctly
						if (preg_match("/\d+\s\w+\s@(.*)@/", $objrec) > 0) $objrec = preg_replace("/@(.*)@/", "@".$m_media."@", $objrec);
						else $objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
						$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
						
						// Add the PRIM and THUM tags to the mapping
						$r = GetSubRecord($objlevel, $line, $indirec);
						$rlevel = $objlevel+1;
						$prim = trim(GetSubRecord($rlevel, $rlevel." _PRIM", $r));
						$thum = trim(GetSubRecord($rlevel, $rlevel." _THUM", $r));
						$add = "\r\n";
						if (!empty($prim)) {
							$rec = $objlevel." ".$prim."\r\n";
							$add .= $rlevel." ".$prim."\r\n";
							$objrec = preg_replace("/$rec/", "", $objrec);
						}
						if (!empty($thum)) {
							$rec = $objlevel." ".$thum."\r\n";
							$add .= $rlevel." ".$thum."\r\n";
							$objrec = preg_replace("/$rec/", "", $objrec);
						}
						if (!$em) {
							$sql = "INSERT INTO ".TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_mfile, m_file, m_gedrec)";
							$sql .= " VALUES('0', '".DbLayer::EscapeQuery($m_media)."', '".DbLayer::EscapeQuery($ext)."', '".DbLayer::EscapeQuery($title)."', '".DbLayer::EscapeQuery($file)."', '".$gedfile."', '".DbLayer::EscapeQuery($objrec)."')";
							$res = NewQuery($sql);
						}
						$sql = "INSERT INTO ".TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_file, mm_gedrec, mm_type)";
						$sql .= " VALUES ('0', '".DbLayer::EscapeQuery($m_media)."', '".DbLayer::EscapeQuery($gid)."', '".DbLayer::EscapeQuery($count)."', '".$gedfile."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@'.$add)."', '".$rectype."')";
						$res = NewQuery($sql);
						$media_count++;
						$count++;
						// NOTE: Add the new media object to the record
						$newrec .= $objlevel." OBJE @".$m_media."@".$add."\r\n";
						
						// NOTE: Set the details for the next media record
						$objlevel = $match[0]{0};
						$inobj = true;
						$objrec = $line."\r\n";
					}
					else {
						// NOTE: Set object level
						$objlevel = $match[0]{0};
						$inobj = true;
						$objrec = trim($line)."\r\n";
					}
					// NOTE: Look for the @M00@ reference
					if (stristr($match[1], "@") !== false) {
						// NOTE: Retrieve the old media ID
						$old_mm_media = preg_replace("/@/", "", $match[1]);
						// NOTE: Check if the id already exists and there is a value behind OBJE (n OBJE @M001@)
						if (!array_key_exists($old_mm_media, $found_ids) && !empty($match[1])) {
							//-- use the old id if we are updating from an online edit
							if ($update) {
								$new_mm_media = $old_mm_media;
							}
							else {
								// NOTE: Get a new media ID
								$new_mm_media = EditFunctions::GetNewXref("OBJE");
							}
							// NOTE: Put both IDs in the found_ids array in case we later find the 0-level
							// NOTE: The 0-level ID will have to be changed also
							$found_ids[$old_mm_media]["old_id"] = $old_mm_media;
							$found_ids[$old_mm_media]["new_id"] = $new_mm_media;
							
							if (!$update) {
								// NOTE: We found a media reference but no media item yet, we need to create an empty
								// NOTE: media object, so we do not have orhpaned media mapping links
								$sql = "INSERT INTO ".TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_mfile, m_file, m_gedrec)";
								$sql .= " VALUES('0', '".DbLayer::EscapeQuery($new_mm_media)."', '', '', '', '".$gedfile."', '0 @".DbLayer::EscapeQuery($new_mm_media)."@ OBJE\r\n')";
								$res = NewQuery($sql);
								
								// NOTE: Add the mapping to the media reference
								// The above code "forgets" all subrecords like THUM and PRIM. We therefore get the whole subrecord from the indirec.
								$gedrec = GetSubRecord($objlevel, $line, $indirec);
								$gedrec = preg_replace("/@(.*)@/", "@$new_mm_media@", $gedrec);
								$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
								$sql = "INSERT INTO ".TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_file, mm_gedrec, mm_type) ";
								$sql .= "VALUES ('0', '".DbLayer::EscapeQuery($new_mm_media)."', '".DbLayer::EscapeQuery($gid)."', '".DbLayer::EscapeQuery($count)."', '".$gedfile."', '".$gedrec."', '".$rectype."')";
								$res = NewQuery($sql);
							}
							else {
								// NOTE: This is an online update. Let's see if we already have a media mapping for this item
								$sql = "SELECT mm_media FROM ".TBLPREFIX."media_mapping WHERE mm_media = '".$new_mm_media."' AND mm_file = '".$gedfile."'";
								$res = NewQuery($sql);
								$row = $res->FetchAssoc();
								if (count($row) == 0) {
									$gedrec = GetSubRecord($objlevel, $line, $indirec); // Added
									$gedrec = preg_replace("/@(.*)@/", "@$new_mm_media@", $gedrec); // Added
									// NOTE: Add the mapping to the media reference
									$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
									$sql = "INSERT INTO ".TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_file, mm_gedrec, mm_type) ";
									$sql .= "VALUES ('0', '".DbLayer::EscapeQuery($new_mm_media)."', '".DbLayer::EscapeQuery($gid)."', '".DbLayer::EscapeQuery($count)."', '".$gedfile."', '".$gedrec."', '".$rectype."')";
									$res = NewQuery($sql);
								}
							}
						}
						else if (array_key_exists($old_mm_media, $found_ids) && !empty($match[1])) {
	
							$new_mm_media = $found_ids[$old_mm_media]["new_id"];
							if (!$update) {
								$gedrec = GetSubRecord($objlevel, $line, $indirec);
								$gedrec = preg_replace("/@(.*)@/", "@$new_mm_media@", $gedrec);
								$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
								$sql = "INSERT INTO ".TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_file, mm_gedrec, mm_type) ";
								$sql .= "VALUES ('0', '".DbLayer::EscapeQuery($new_mm_media)."', '".DbLayer::EscapeQuery($gid)."', '".DbLayer::EscapeQuery($count)."', '".$gedfile."', '".$gedrec."', '".$rectype."')";
								$res = NewQuery($sql);
							}
							else {
								// NOTE: This is an online update. Let's see if we already have a media mapping for this item
								$sql = "SELECT mm_media FROM ".TBLPREFIX."media_mapping WHERE mm_media = '".$new_mm_media."' AND mm_file = '".$gedfile."'";
								$res = NewQuery($sql);
								$row = $res->FetchAssoc();
								if (count($row) == 0) {
									// NOTE: Add the mapping to the media reference
									$line = preg_replace("/@(.+)@/", "@$new_mm_media@", $line);
									$sql = "INSERT INTO ".TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_file, mm_gedrec, mm_type) ";
									$sql .= "VALUES ('0', '".DbLayer::EscapeQuery($new_mm_media)."', '".DbLayer::EscapeQuery($gid)."', '".DbLayer::EscapeQuery($count)."', '".$gedfile."', '".$line."', '".$rectype."')";
									$res = NewQuery($sql);
								}
							}
						}
						$media_count++;
						$count++;
						$objlevel = 0;
						$objrec = "";
						$inobj = false;
					}
				}
				// NOTE: Match lines 0 @0000@ OBJE
				else if (preg_match("/^[1-9]\sOBJE$/", $line, $match)) {
					if (!empty($objrec)) {
						$title = GetGedcomValue("TITL", $objlevel+1, $objrec);
						if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", $objlevel+2, $objrec);
						$file = GetGedcomValue("FILE", $objlevel+1, $objrec);
						// If the file is a link, normalize it
						if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
						$file = RelativePathFile(MediaFS::CheckMediaDepth($file));
						
						// Add a check for existing file here
						$em = EditFunctions::CheckDoubleMedia($file, $title, $gedfile);
						if (!$em) $m_media = EditFunctions::GetNewXref("OBJE");
						else $m_media = $em;
	
						// Get the extension
						$et = preg_match("/(\.\w+)$/", $file, $ematch);
						$ext = "";
						if ($et>0) $ext = substr(trim($ematch[1]),1);
						
						// Add the PRIM and THUM tags to the mapping
						$prim = trim(GetSubRecord($objlevel+1, " _PRIM", $objrec));						
						$thum = trim(GetSubRecord($objlevel+1, " _THUM", $objrec));						
						$add = "\r\n";
						$rlevel = $objlevel+1;
						if (!empty($prim)) {
							$rec = $rlevel." ".$prim."\r\n";
							$add .= $rec;
							$objrec = preg_replace("/$rec/", "", $objrec);
						}
						if (!empty($thum)) {
							$rec = $rlevel." ".$thum."\r\n";
							$add .= $rec;
							$objrec = preg_replace("/$rec/", "", $objrec);
						}
						
						$objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
						$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
						if (!$em) {
							$sql = "INSERT INTO ".TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_mfile, m_file, m_gedrec)";
							$sql .= " VALUES('0', '".DbLayer::EscapeQuery($m_media)."', '".DbLayer::EscapeQuery($ext)."', '".DbLayer::EscapeQuery($title)."', '".DbLayer::EscapeQuery($file)."', '".$gedfile."', '".DbLayer::EscapeQuery($objrec)."')";
							$res = NewQuery($sql);
						}
	
						
						$sql = "INSERT INTO ".TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_file, mm_gedrec, mm_type)";
						$sql .= " VALUES ('0', '".DbLayer::EscapeQuery($m_media)."', '".DbLayer::EscapeQuery($gid)."', '".DbLayer::EscapeQuery($count)."', '".$gedfile."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@'.$add)."', '".$rectype."')";
						$res = NewQuery($sql);
						$media_count++;
						$count++;
						// NOTE: Add the new media object to the record
						$newrec .= $objlevel." OBJE @".$m_media."@".$add."\r\n";
					}
					// NOTE: Set the details for the next media record
					$objlevel = $match[0]{0};
					$inobj = true;
					$objrec = $line."\r\n";
				}
				else {
					$ct = preg_match("/(\d+)\s(\w+)(.*)/", $line, $match);
					if ($ct > 0) {
						$level = $match[1];
						$fact = $match[2];
						$desc = trim($match[3]);
						if ($inobj && ($level<=$objlevel || $key == $ct_lines-1)) {
							if ($key == $ct_lines-1 && $level>$objlevel) {
								$objrec .= $line."\r\n";
							}
							$title = GetGedcomValue("TITL", $objlevel+1, $objrec);
							if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", $objlevel+2, $objrec);
							$file = GetGedcomValue("FILE", $objlevel+1, $objrec);
							// If the file is a link, normalize it
							if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
							$file = RelativePathFile(MediaFS::CheckMediaDepth($file));
							// Get the extension
							$et = preg_match("/(\.\w+)$/", $file, $ematch);
							$ext = "";
							if ($et>0) $ext = substr(trim($ematch[1]),1);
							if ($objrec{0} != 0) {
								
								// Add a check for existing file here
								$em = EditFunctions::CheckDoubleMedia($file, $title, $gedfile);
								if (!$em) $m_media = EditFunctions::GetNewXref("OBJE");
								else $m_media = $em;
								
								if (preg_match("/^\d+\s\w+\s@(.*)@/", $objrec) > 0) {
									$objrec = preg_replace("/@(.*)@/", "@".$m_media."@", $objrec);
								}
								else $objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
								$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
								
								// Add the PRIM and THUM tags to the mapping
								$prim = trim(GetSubRecord($objlevel, " _PRIM", $objrec));
								$thum = trim(GetSubRecord($objlevel, " _THUM", $objrec));
								$add = "\r\n";
								$rlevel = $objlevel+1;
								if (!empty($prim)) {
									$rec = $objlevel." ".$prim."\r\n";
									$add .= $rlevel." ".$prim."\r\n";
									$objrec = preg_replace("/$rec/", "", $objrec);
								}
								if (!empty($thum)) {
									$rec = $objlevel." ".$thum."\r\n";
									$add .= $rlevel." ".$thum."\r\n";
									$objrec = preg_replace("/$rec/", "", $objrec);
								}
	
								if (!$em) {
									$sql = "INSERT INTO ".TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_mfile, m_file, m_gedrec)";
									$sql .= " VALUES('0', '".DbLayer::EscapeQuery($m_media)."', '".DbLayer::EscapeQuery($ext)."', '".DbLayer::EscapeQuery($title)."', '".DbLayer::EscapeQuery($file)."', '".$gedfile."', '".DbLayer::EscapeQuery($objrec)."')";
									$res = NewQuery($sql);
								}
								$sql = "INSERT INTO ".TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_file, mm_gedrec, mm_type)";
								$sql .= " VALUES ('0', '".DbLayer::EscapeQuery($m_media)."', '".DbLayer::EscapeQuery($gid)."', '".DbLayer::EscapeQuery($count)."', '".$gedfile."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@'.$add)."', '".$rectype."')";
								$res = NewQuery($sql);
							}
							else {
								$oldid = preg_match("/0\s@(.*)@\sOBJE/", $objrec, $newmatch);
								$m_media = $newmatch[1];
								$sql = "UPDATE ".TBLPREFIX."media SET m_ext = '".DbLayer::EscapeQuery($ext)."', m_titl = '".DbLayer::EscapeQuery($title)."', m_mfile = '".DbLayer::EscapeQuery($file)."', m_gedrec = '".DbLayer::EscapeQuery($objrec)."' WHERE m_media = '".$m_media."'";
								$res = NewQuery($sql);
							}
							$media_count++;
							$count++;
							$objrec = "";
							if ($key == $ct_lines-1 && $level>$objlevel) {
								$line = $objlevel." OBJE @".$m_media."@".$add;
							}
							else {
								$line = $objlevel." OBJE @".$m_media."@\r\n".$line;
							}
							$inobj = false;
							$objlevel = 0;
						}
						else {
							if ($inobj) $objrec .= $line."\r\n";
						}
						if ($fact=="OBJE") {
							$inobj = true;
							$objlevel = $level;
							$objrec = "";
						}
					}
				}
				if (!$inobj && !empty($line)) {
					$newrec .= $line."\r\n";
				}
			}
		}
		return $newrec;
	}
	
	private function AddAssoLink($pid1, $pid2, $type, $fact, $rela, $resn, $ged) {
	
		
		if ($type == "INDI") $type = "I";
		else $type = "F";
		if (!empty($resn)) $resn = substr($resn, 0,1);
		if (!in_array($resn, array("", "n", "l", "c", "p"))) $resn = "";
		
		$sql = "INSERT INTO ".TBLPREFIX."asso VALUES ('0', '".$pid1."', '".$pid2."', '".$type."', '".$fact."', '".$rela."', '".$resn."', '".$ged."')";
		$res = NewQuery($sql);
		if (!$res) return false;
		else return true;
		
	}
		
	private function AddSourceLink($sour, $gid, $gedrec, $gedid, $type) {
		
		$sql = "INSERT INTO ".TBLPREFIX."source_mapping (sm_id, sm_sid, sm_type, sm_gid, sm_file, sm_gedrec, sm_key) VALUES ('0', '".$sour."', '".$type."', '".$gid."', '".$gedid."', '".DbLayer::EscapeQuery($gedrec)."', '".JoinKey($sour, $gedid)."')";
		$res = NewQuery($sql);
		if ($res) {
			$res->FreeResult();
			return true;
		}
		else return false;
	}
	
	private function AddOtherLink($note, $gid, $type, $gedid) {
		
		$sql = "INSERT INTO ".TBLPREFIX."other_mapping (om_id, om_oid, om_gid, om_type, om_file) VALUES ('0', '".$note."', '".$gid."', '".$type."', '".$gedid."')";
		$res = NewQuery($sql);
		if ($res) {
			$res->FreeResult();
			return true;
		}
		else return false;
	}

	public function LockTables() {
		
		$sql = "LOCK TABLES ".
		TBLPREFIX."actions WRITE, ".
		TBLPREFIX."asso WRITE, ".
		TBLPREFIX."individuals WRITE, ".
		TBLPREFIX."individual_family WRITE, ".
		TBLPREFIX."families WRITE, ".
		TBLPREFIX."sources WRITE, ".
		TBLPREFIX."source_mapping WRITE, ".
		TBLPREFIX."other WRITE, ".
		TBLPREFIX."other_mapping WRITE, ".
		TBLPREFIX."places WRITE, ".
		TBLPREFIX."placelinks WRITE, ".
		TBLPREFIX."names WRITE, ".
		TBLPREFIX."dates WRITE, ".
		TBLPREFIX."media WRITE, ".
		TBLPREFIX."media_mapping WRITE, ".
		TBLPREFIX."soundex WRITE, ".
		TBLPREFIX."log WRITE, ".
		TBLPREFIX."changes WRITE";
		$res = NewQuery($sql);
		
	}
	/**
	 * Add a new calculated name to the individual names table
	 *
	 * this function will add a new name record for the given individual, this function is called from the
	 * importgedcom.php script stage 5
	 * @param string $gid	gedcom xref id of individual to update
	 * @param string $newname	the new calculated name to add
	 * @param string $surname	the surname for this name
	 * @param string $letter	the letter for this name
	 */
	public function AddNewName($indi, $newname, $letter, $fletter, $surname, $indirec, $nick="") {
	
		$kgid = JoinKey($indi->xref, $indi->gedcomid);
		$sql = "INSERT INTO ".TBLPREFIX."names VALUES('0', '".DbLayer::EscapeQuery($kgid)."','".DbLayer::EscapeQuery($indi->xref)."','".$indi->gedcomid."','".DbLayer::EscapeQuery($newname)."','".DbLayer::EscapeQuery($letter)."','".DbLayer::EscapeQuery($fletter)."','".DbLayer::EscapeQuery($surname)."', '".DbLayer::EscapeQuery($nick)."','C')";
		$res = NewQuery($sql);
		
		$name_array = $indi->name_array;
		$name_array[] = array($newname, $letter, $surname, "C");
		$soundex_codes = self::GetSoundexStrings($name_array, true, $indirec);
		$sql = "DELETE FROM ".TBLPREFIX."soundex WHERE s_gid='".$kgid."'";
		$sql = "INSERT INTO ".TBLPREFIX."soundex VALUES ";
		$addsql = "";
		foreach ($soundex_codes as $type => $ncodes) {
			foreach ($ncodes as $nametype => $tcodes) {
				foreach ($tcodes as $key => $code) {
					$addsql .= "(NULL, '".$kgid."', '".$indi->gedcomid."', '".$type."', '".$nametype."', '".$code."'), ";
				}
			}
		}
		$addsql = substr($addsql, 0, strlen($addsql)-2);
		$res = NewQuery($sql.$addsql);
		if ($res) $res->FreeResult();
		$sql = "UPDATE ".TBLPREFIX."individuals SET i_gedrec='".DbLayer::EscapeQuery($indirec)."' WHERE i_id='".DbLayer::EscapeQuery($indi->xref)."' AND i_file='".$indi->gedcomid."'";
		$res = NewQuery($sql);
	}
	
	public function GetFemalesWithFAMS($gedid) {
		
		$flist = array();
		$sql = "SELECT i_key, i_gedrec, i_isdead, i_id, i_file, n_name, n_surname, n_nick, n_letter, n_fletter, n_type ";
		$sql .= "FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."names ON n_key=i_key ";
		$sql .= "WHERE i_gender='F' AND i_file = '".$gedid."' AND i_gedrec LIKE '%1 FAMS%' AND n_type='P' ORDER BY i_key, n_id";
		// N.B. Only primary names are added, as this will be affected by married names!
		
		$res = NewQuery($sql);
		$key = "";
		while($row = $res->FetchAssoc($res->result)){
			if ($key != $row["i_key"]) {
				if ($key != "") $person->names_read = true;
				$person = null;
				$key = $row["i_key"];
				$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
				$flist[$row["i_key"]] = $person;
			}
			$flist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
		}
		if ($key != "") $person->names_read = true;
		$res->FreeResult();
		return $flist;
	}
	
	//-- get the famlist from the datastore
	public function GetFamListWithMARR($gedid) {
	
		$famlist = array();
		$sql = "SELECT f_id, f_gedrec, f_file FROM ".TBLPREFIX."families WHERE f_file='".$gedid."' AND f_gedrec LIKE '%1 MARR%'";
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		if ($ct > 0) {
			while($row = $res->FetchAssoc()){
				$fam = Family::GetInstance($row["f_id"], $row, $gedid);
				$famlist[$row["f_id"]] = $fam;
			}
		}
		$res->FreeResult();
		return $famlist;
	}
	
	private function ExtractTodo($gid, $type, $indirec, $gedfile) {
		
		if ($type == "INDI" || $type == "FAM" || $type == "REPO") {
			do {
				// Always take the first todo, as it will be removed before the next cycle
				$todo = GetSubRecord(1, "1 _TODO", $indirec);
				if (!empty($todo)) {
					if (!GedcomConfig::$KEEP_ACTIONS) {
						$action = new ActionItem();
						$action->gedcomid = $gedfile;
						if ($type != "REPO") {
							$action->pid = $gid;
							$action->type = $type;
							$ct = preg_match("/2 REPO @(.+)@/", $todo, $match);
							if ($ct > 0) $action->repo = $match[1];
						}
						else {
							$action->repo = $gid;
						}
						$textrec = GetSubRecord(2, "2 TEXT", $todo);
						if (!empty($textrec)) {
							$action->text = str_replace(array("2 TEXT ", "\r\n3 CONT "), array("", "\r\n"), $textrec);
						}
						$ct = preg_match("/2 STAT (\w+)/", $todo, $match);
						if ($ct > 0) $action->status = ($match[1] == "Closed" ? 1 : 0);
						$action->AddThis();
					}
					// Even if we don't add the actions, we must remove the action from the gedrec
					$indirec = str_replace($todo, "", $indirec);
				}
			} while (!empty($todo)); 
		}
		return $indirec;
	}
		
	public function GetSoundexStrings($namearray, $import=false, $indirec="") {
	
		$snsndx = "";
		$sndmsndx = "";
		$fnsndx = "";
		$fndmsndx = "";
		$soundexarray = array();
		$soundexarray["R"] = array();
		$soundexarray["R"]["F"] = array();
		$soundexarray["R"]["L"] = array();
		$soundexarray["R"]["P"] = array();
		$soundexarray["D"] = array();
		$soundexarray["D"]["F"] = array();
		$soundexarray["D"]["L"] = array();
		$soundexarray["D"]["P"] = array();
		foreach ($namearray as $key => $names) {
			if ($names[2] != "@N.N.") {
				if (NameFunctions::HasChinese($names[2], $import)) $names[2] = NameFunctions::GetPinYin($names[2], $import);
				if (NameFunctions::HasCyrillic($names[2], $import)) $names[2] = NameFunctions::GetTransliterate($names[2], $import);
				$nameparts = explode(" ",trim($names[2]));
				foreach ($nameparts as $key3 => $namepart) {
					$sval = soundex($namepart);
					if ($sval != "0000") {
						if (!in_array($sval, $soundexarray["R"]["L"])) $soundexarray["R"]["L"][] = $sval;
					}
					$sval = NameFunctions::DMsoundex($namepart);
					if (is_array($sval)) {
						foreach ($sval as $key4 => $dmcode) {
							if (!in_array($dmcode, $soundexarray["D"]["L"])) $soundexarray["D"]["L"][] = $dmcode;
						}
					}
				}
			}
			$lnames = preg_split("/\//",$names[0]);
			$fname = $lnames[0];
			if ($fname != "@P.N." && $fname !="") {
				if (NameFunctions::HasChinese($fname, $import)) $fname = NameFunctions::GetPinYin($fname, $import);
				else if (NameFunctions::HasCyrillic($fname, $import)) $fname = NameFunctions::GetTransliterate($fname, $import);
				$nameparts = explode(" ",trim($fname));
				foreach ($nameparts as $key3 => $namepart) {
				// Added: The nickname is embedded in parenthesis. They must be removed and will be added later
				// In one blow, also remove stars (starred names)
				$namepart = preg_replace(array("/\(.*\)/","/\*/"), array("", ""), $namepart);
					$sval = soundex($namepart);
					if ($sval != "0000") {
						if (!in_array($sval, $soundexarray["R"]["F"])) $soundexarray["R"]["F"][] = $sval;
					}
					$sval = NameFunctions::DMsoundex($namepart);
					if (is_array($sval)) {
						foreach ($sval as $key4 => $dmcode) {
							if (!in_array($dmcode, $soundexarray["D"]["F"])) $soundexarray["D"]["F"][] = $dmcode;
						}
					}
				}
			}
			// Now also add the nicks. Only if the indirec is added, we will get any result.
			$nicks = NameFunctions::GetNicks($indirec);
			foreach ($nicks as $key => $nick) {
				$sval = soundex($nick);
				if ($sval != "0000") {
					if (!in_array($sval, $soundexarray["R"]["F"])) $soundexarray["R"]["F"][] = $sval;
				}
				$sval = NameFunctions::DMsoundex($nick);
				if (is_array($sval)) {
					foreach ($sval as $key4 => $dmcode) {
						if (!in_array($dmcode, $soundexarray["D"]["F"])) $soundexarray["D"]["F"][] = $dmcode;
					}
				}
			}
		}
		return $soundexarray;
	}
					
}
?>