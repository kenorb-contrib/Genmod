<?php
/**
 * Counts how many hits.
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
 * @subpackage Admin
 * @version $Id$
 */
if (strstr($_SERVER["SCRIPT_NAME"],"hitcount.php")) {
	require "intrusion.php";
}

//only do counter stuff if counters are enabled
if($SHOW_COUNTER) {
	// set some vars global if this script is called from within a function
	global $GEDCOM, $GEDCOMID, $GM_IMAGES, $GM_IMAGE_DIR, $bot;

	$GM_COUNTER_NAME     = $GEDCOM."gm_counter";
	$GM_INDI_COUNTER_NAME = $GEDCOM."gm_indi_counter";

	// First check if any id is set. If not, we assume it's the index page
	if (isset($pid) || isset($famid) || isset($sid) || isset($rid) || isset($oid)) {
		// See if the ID exists. If not, we set the counter to 0.
		if((isset($pid) && FindPersonRecord(strtoupper($pid)) != "") 
		|| (isset($famid) && FindFamilyRecord(strtoupper($famid)) != "")
		|| (isset($sid) && FindSourceRecord(strtoupper($sid)) != "")
		|| (isset($rid) && FindRepoRecord(strtoupper($rid)) != "")
		|| (isset($oid) && FindOtherRecord(strtoupper($oid), "", false, "NOTE") != "")
		) {
			
			if (isset($pid)) $cpid = $pid;
			if (isset($famid)) $cpid = $famid;
			if (isset($sid)) $cpid = $sid;
			if (isset($rid)) $cpid = $rid;
			if (isset($oid)) $cpid = $oid;
			
	  		// Capitalize ID to make sure we have a correct hitcount on the individual
	  		$cpid = strtoupper($cpid);
	  	
	  		// see if already viewed individual this session
	  		// exclude bots because we want to see the real number of hits.
	    	if(isset($_SESSION[$GM_INDI_COUNTER_NAME][$cpid]) && !$bot) {
				$hits = $_SESSION[$GM_INDI_COUNTER_NAME][$cpid];
	  		}
	  		else { 
	  		//haven't viewed individual this session
				$id = $cpid."[".$GEDCOMID."]";
				$hits = UpdateCounter($id, $bot);
			}
			$_SESSION[$GM_INDI_COUNTER_NAME][$cpid] = $hits;
		}
		else $hits = 0;
	}
	else { 
		//web site counter
	    // has user started a session on site yet
    	if(isset($_SESSION[$GM_COUNTER_NAME])) $hits = $_SESSION[$GM_COUNTER_NAME];
    	else { //new user so increment counter and save
			$id = "Index"."[".$GEDCOMID."]";
			$hits = UpdateCounter($id, $bot);
			$_SESSION[$GM_COUNTER_NAME]=$hits;
  		}
	}

	//replace the numbers with their images
	for($i=0;$i<10;$i++)
    $hits = str_replace("$i","<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES[$i]["digit"]."\" alt=\"".$gm_lang["hit_count"]."\" />","$hits");
	$hits = '<span dir="ltr">'.$hits.'</span>';
}
?>