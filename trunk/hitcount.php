<?php
/**
 * Counts how many hits.
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
 * @subpackage Admin
 * @version $Id: hitcount.php,v 1.3 2005/11/24 21:40:32 sjouke Exp $
 */
if (strstr($_SERVER["SCRIPT_NAME"],"hitcount")) {
	print "Now, why would you want to do that.  You're not hacking are you?";
	exit;
}

//only do counter stuff if counters are enabled
if($SHOW_COUNTER) {
	$GM_COUNTER_NAME     = $GEDCOM."gm_counter";
	$GM_INDI_COUNTER_NAME = $GEDCOM."gm_indi_counter";

	if(isset($pid) && find_person_record($pid)) { //individual counter
  
  		// Capitalize ID to make sure we have a correct hitcount on the individual
  		$pid = strtoupper($pid);
  	
  		//see if already viewed individual this session
    	if(isset($_SESSION[$GM_INDI_COUNTER_NAME][$pid])) {
			$hits = $_SESSION[$GM_INDI_COUNTER_NAME][$pid];
  		}
  		else { 
  		//haven't viewed individual this session
			$id = $pid."[".$GEDCOM."]";
			$hits = UpdateCounter($id);
		}
		$_SESSION[$GM_INDI_COUNTER_NAME][$pid] = $hits;
	}
	else { 
		//web site counter
	    // has user started a session on site yet
    	if(isset($_SESSION[$GM_COUNTER_NAME])) $hits = $_SESSION[$GM_COUNTER_NAME];
    	else { //new user so increment counter and save
			$id = "Index"."[".$GEDCOM."]";
			$hits = UpdateCounter($id);
			$_SESSION[$GM_COUNTER_NAME]=$hits;
  		}
	}

	//replace the numbers with their images
	for($i=0;$i<10;$i++)
    $hits = str_replace("$i","<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES[$i]["digit"]."\" alt=\"gm_counter\" />","$hits");
	$hits = '<span dir="ltr">'.$hits.'</span>';
}
?>