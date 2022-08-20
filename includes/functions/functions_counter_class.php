<?php
/**
 * Functions used for the hitcounter
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
 *
 * $Id: functions_counter_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class CounterFunctions {

	public function GetCounter() {
		global $GM_IMAGES, $bot;
		global $pid, $famid, $sid, $rid, $mid, $oid;

		//only do counter stuff if counters are enabled
		if(GedcomConfig::$SHOW_COUNTER) {
		
			$GM_COUNTER_NAME     = GedcomConfig::$GEDCOMID."gm_counter";
			$GM_INDI_COUNTER_NAME = GedcomConfig::$GEDCOMID."gm_indi_counter";
			$type = "";
		
			// First check if any id is set. If not, we assume it's the index page
			if (isset($pid) || isset($famid) || isset($sid) || isset($rid) || isset($oid) || isset($mid)) {
				// See if the ID exists. If not, we set the counter to 0.
				$object = null;
				$p = pathinfo($_SERVER["SCRIPT_NAME"]);
				switch ($p["filename"]) {
					case "individual": 
						$object =& Person::GetInstance($pid);
						$type = "INDI";
						break;
					case "family":
						$object =& Family::GetInstance($famid);
						$type = "FAM";
						break;
					case "source": 
						$object =& Source::GetInstance($sid); 
						$type = "SOUR";
						break;
					case "repo": 
						$object =& Repository::GetInstance($rid); 
						$type = "REPO";
						break;
					case "note": 
						$object =& Note::GetInstance($oid); 
						$type = "NOTE";
						break;
					case "mediadetail": 
						$object =& MediaItem::GetInstance($mid); 
						$type = "OBJE";
						break;
				}
				if (is_object($object) && !$object->isempty) {
					
					if (isset($pid)) $cpid = $pid;
					if (isset($famid)) $cpid = $famid;
					if (isset($sid)) $cpid = $sid;
					if (isset($rid)) $cpid = $rid;
					if (isset($oid)) $cpid = $oid;
					if (isset($mid)) $cpid = $mid;
					
			  		// Capitalize ID to make sure we have a correct hitcount on the individual
			  		$cpid = strtoupper($cpid);
			  	
			  		// see if already viewed individual this session
			  		// exclude bots because we want to see the real number of hits.
			    	if(isset($_SESSION[$GM_INDI_COUNTER_NAME][$cpid]) && !$bot) {
						$hits = $_SESSION[$GM_INDI_COUNTER_NAME][$cpid];
			  		}
			  		else { 
			  		//haven't viewed individual this session
						$id = $cpid."[".GedcomConfig::$GEDCOMID."]";
						$hits = self::UpdateCounter($id, $type, GedcomConfig::$GEDCOMID, $bot);
					}
					$_SESSION[$GM_INDI_COUNTER_NAME][$cpid] = $hits;
				}
				else {
					$hits = 0;
				}
			}
			else { 
				//web site counter
			    // has user started a session on site yet
		    	if(isset($_SESSION[$GM_COUNTER_NAME])) $hits = $_SESSION[$GM_COUNTER_NAME];
		    	else { //new user so increment counter and save
					$id = "Index"."[".GedcomConfig::$GEDCOMID."]";
					$hits = self::UpdateCounter($id, $type, GedcomConfig::$GEDCOMID, $bot);
					$_SESSION[$GM_COUNTER_NAME]=$hits;
		  		}
			}
		
			//replace the numbers with their images
			for($i=0;$i<10;$i++)
		    $hits = str_replace("$i","<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES[$i]["digit"]."\" alt=\"".GM_LANG_hit_count."\" title=\"".GM_LANG_hit_count."\" />","$hits");
			$hits = '<span dir="ltr">'.$hits.'</span>';
			return $hits;
		}
		else return "";
	}
	
	/**
	 * Update page counters
	 *
	 * The function updates the hit counters for the index
	 * pages of the different gedcoms and for the individuals.
	 * Hits are stored in the database.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$id		Indi id or "Index" for index page. Format: I5[myged.ged]
	 * @return 	number	$hits	The new value for the hitcounter
	 */
	private function UpdateCounter($id, $type, $gedid, $bot=false) {
	
		if ($bot) $sql = "SELECT c_bot_number FROM ".TBLPREFIX."counters WHERE (c_id='".$id."')";
		else $sql = "SELECT c_number FROM ".TBLPREFIX."counters WHERE (c_id='".$id."')";
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		if ($ct == "0") {
			if ($bot) $sql = "INSERT INTO ".TBLPREFIX."counters VALUES ('".$id."', '1', '1', '".$gedid."', '".$type."')";
			else $sql = "INSERT INTO ".TBLPREFIX."counters VALUES ('".$id."', '1', '0', '".$gedid."', '".$type."')";
		  	$res = NewQuery($sql);
			return 1;
		}
		else {
			while($row = $res->FetchAssoc()){
				if ($bot) $hits = $row["c_bot_number"];
				else $hits = $row["c_number"];
			}
			$res->FreeResult();
			if ($bot) $sql = "UPDATE ".TBLPREFIX."counters SET c_bot_number=c_bot_number+1 WHERE c_id='".$id."'"; 
			else $sql = "UPDATE ".TBLPREFIX."counters SET c_number=c_number+1 WHERE c_id='".$id."'"; 
	  		$res = NewQuery($sql);
			return $hits+1;
		}
	}

	public function GetCounters($limit, $gedid, $bot) {
		
		$ids = array();
		if ($bot) $sql = "SELECT * from ".TBLPREFIX."counters WHERE c_file LIKE '".$gedid."' AND c_bot_number>0 AND c_type NOT LIKE '' ORDER BY c_bot_number DESC LIMIT ".$limit;
		else $sql = "SELECT * from ".TBLPREFIX."counters WHERE c_file LIKE '".$gedid."' AND c_number>0 AND c_type NOT LIKE '' ORDER BY c_number DESC LIMIT ".$limit;
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$p1 = strpos($row["c_id"],"[");
			$id = substr($row["c_id"],0,$p1);
			if ($bot) $ids[$id]["number"] = $row["c_bot_number"];
			else $ids[$id]["number"] = $row["c_number"];
			$ids[$id]["type"] = $row["c_type"];
			$ids[$id]["file"] = $row["c_file"];
		}
		$res->FreeResult();
		return $ids;
	}
			
}
?>