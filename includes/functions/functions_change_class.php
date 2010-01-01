<?php
/**
 * Various functions used by the changes system
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
 * @subpackage Edit
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class ChangeFunctions {
	
	public function ReadGedcomRecord($id, $gedid, $type) {
	
		if ($type == "INDI") $sql = "SELECT i_gedrec FROM ".TBLPREFIX."individuals WHERE i_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
		else if ($type == "FAM") $sql = "SELECT f_gedrec FROM ".TBLPREFIX."families WHERE f_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
		else if ($type == "SOUR") $sql = "SELECT s_gedrec FROM ".TBLPREFIX."sources WHERE s_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
		else if ($type == "REPO" || $type == "NOTE" || $type == "HEAD" || $type == "SUBM") $sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
		else if ($type == "OBJE") $sql = "SELECT m_gedrec FROM ".TBLPREFIX."media WHERE m_media LIKE '".DbLayer::EscapeQuery($id)."' AND m_file='".$gedid."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return false;
		else {
			$row = $res->FetchRow();
			return $row[0];
		}
	}

	/*
	* $status true: 	false/true returned: there are changes 
						$gid for this gid
	* 					$thisged false/true: in all/current gedcoms
	*					$fact for this fact
	*					($data is N/A)
	* $status false:	$data="gedlines": return array with gedcom/gedcom lines
						$data="gedcoms": return array with gedcoms with changes
						$thisged false/true: in all/current gedcom ($thisged and $data="gedcom" will have <= 1 result)
						$gid: for this gid
						$fact for this fact
	*/
	public function GetChangeData($status=false, $gid="", $thisged=false, $data="gedlines", $fact="") {
		global $changes, $GEDCOMID;
		global $chcache, $chstatcache;
		
		// NOTE: If the file does not have an ID, go back
		if (!isset($GEDCOMID)) return false;
		
		// Initialise the results cache
		if (!isset($chcache)) $chcache = array();
		
		// Initialise the status cache
		if (!isset($chstatcache)) {
			$chstatcache = array();
			$sql = "SELECT ch_gid, ch_file, ch_gid_type FROM ".TBLPREFIX."changes";
			$resc = NewQuery($sql);
			if($resc) {
				while ($row = $resc->FetchAssoc()) {
					$chstatcache[$row["ch_gid"]][$row["ch_file"]] = true;
				}
			}
		}
		
		// Check in the cache if this gid has any changes. If not, no need to get anything from the DB	
		if ($status) {
			// Specific gid
			if (!empty($gid)) {
				// Specific gid, current gedcom
				if ($thisged) {
					if (!isset($chstatcache[$gid][$GEDCOMID])) return 0;
				}
				// Specific gid, all gedcoms
				else if (!isset($chstatcache[$gid])) return 0;
			}
			else {
				// No gid, current gedcom
				if ($thisged) {
					$has = false;
					foreach ($chstatcache as $gkey => $gged) {
						if (isset($gged[$GEDCOMID])) {
							$has = true;
							break;
						}
					}
					if (!$has) return 0;
				}
				// No gid, all gedcoms
				else if (count($chstatcache) == 0) return 0;
			}
		}
		
		$whereclause = "";
		if ($thisged) $whereclause .= "ch_file = '".$GEDCOMID."'";
		if (!empty($gid)) {
			if (!empty($whereclause)) $whereclause .= " AND ";
			$whereclause .= "ch_gid = '".$gid."'";
		}
		if (!empty($fact)) {
			if (!empty($whereclause)) $whereclause .= " AND ";
			$factarr = preg_split("/(,)/", $fact);
			$or = false;
			if (count($factarr) >1) $whereclause .="(";
			foreach($factarr as $key => $fact) {
				if ($or) {
					$whereclause .= " OR ";
				}
				$or = true;
				$whereclause .= "ch_fact = '".trim($fact)."'";
			}
			if (count($factarr) >1) $whereclause .=")";
		}
	
		if ($status) $selectclause = "SELECT COUNT(ch_id) ";
		else {
			if ($data == "gedcoms") $selectclause = "SELECT ch_file ";
			else {
				$selectclause = "SELECT ch_gid, ch_type, ch_fact, ch_file, ch_old, ch_new, ch_gid_type";
				$whereclause .= " ORDER BY";
				if ($data == "gedlinesCHAN") {
					$data = "gedlines";
					$selectclause .= ", ch_user, ch_time";
					$whereclause .= " ch_time,";
				}
				$selectclause .= " ";
				$whereclause .= " ch_gid, ch_id ";
			}
		}
	
		$sql = $selectclause."FROM ".TBLPREFIX."changes";
		if (!empty($whereclause)) $sql .= " WHERE ".$whereclause;
	
		if (array_key_exists($sql, $chcache)) return $chcache[$sql];
		$res = NewQuery($sql);
		if (!$res) return false;	
		
		if($status) {
			$row = $res->FetchRow();
			$chcache[$sql] = $row[0];
			return $row[0];
		}
		else {
			if ($data == "gedcoms") {
				// NOTE: Return gedcoms which have changes
				$gedfiles = array();
				while ($row = $res->FetchAssoc($res->result)) {
					$gedfiles[$row["ch_file"]] = $row["ch_file"];
				}
				$chcache[$sql] = $gedfiles;
				return $gedfiles;
			}
			else {
				// NOTE: Construct the changed gedcom record
				$gedlines = array();
				while ($row = $res->FetchAssoc($res->result)) {
					$gedname = $row["ch_file"];
					$chgid = $row["ch_gid"];
					$gidtype = trim($row["ch_gid_type"]); // Very funny. If not trimmed, the length is 4!
					if (!isset($gedlines[$gedname][$chgid])) {
						$gedlines[$gedname][$chgid] = trim(self::ReadGedcomRecord($chgid, $gedname, $gidtype));
					}
	
					// NOTE: Add to existing ID
					// NOTE: If old is empty, just add the new data, make sure it is not new record
					if (empty($row["ch_old"]) && !empty($row["ch_new"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) == 0) {
						$gedlines[$gedname][$chgid] .= "\r\n".$row["ch_new"];
					}
					
					// NOTE: Add new ID
					// NOTE: If the old is empty and the new is a new record make sure we just store the new record
					else if (empty($row["ch_old"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) > 0) {
						$gedlines[$gedname][$chgid] = $row["ch_new"];
					}
					
					// NOTE: Delete ID
					// NOTE: if old is not empty and new is empty, AND new pid, the record needs to be deleted
					else if (!empty($row["ch_old"]) && empty($row["ch_new"])&& preg_match("/0\s@(.*)@/", $row["ch_new"]) > 0) {
						$gedlines[$gedname][$chgid] = GM_LANG_record_to_be_deleted;
					}
					
					// NOTE: Replace any other, change or delete from ID
					// NOTE: If new is empty or filled, the old needs to be replaced
					else $gedlines[$gedname][$chgid] = str_replace(trim($row["ch_old"]), $row["ch_new"], $gedlines[$gedname][$chgid]);
	
					if (isset($row["ch_user"]) && isset($row["ch_time"])) {
						$gedrecord = $gedlines[$gedname][$chgid];
						if (empty($gedrecord)) {
							// deleted record
							$gedrecord = trim(FindGedcomRecord($chgid, $gedname));
						}
						//LERMAN
						$gedrecord = EditFunctions::CheckGedcom($gedrecord, true, $row["ch_user"], $row["ch_time"]);
						$gedlines[$gedname][$chgid] = trim($gedrecord);
					}
				}
				$chcache[$sql] = $gedlines;
				return $gedlines;
			}
		}
	}
	/**
	 * Accept changed gedcom record into database
	 *
	 * This function gets an updated record from the gedcom file and replaces it in the database
	 * @author 	Genmod Development Team
	 * @param		string	$cid		The change id of the record to accept
	 * @return 	boolean	true if changes were processed correctly, false if there was a problem
	 */
	public function AcceptChange($cid, $gedfile, $all=false) {
		global $GEDCOMID, $FILE, $gm_user, $chcache;
		
		$cidchanges = array();
		if ($all) $sql = "SELECT ch_id, ch_cid, ch_gid, ch_file, ch_old, ch_new, ch_type, ch_user, ch_time, ch_gid_type FROM ".TBLPREFIX."changes WHERE ch_file = '".$gedfile."' ORDER BY ch_id ASC";
		else $sql = "SELECT ch_id, ch_cid, ch_gid, ch_file, ch_old, ch_new, ch_type, ch_user, ch_time, ch_gid_type FROM ".TBLPREFIX."changes WHERE ch_cid = '".$cid."' AND ch_file = '".$gedfile."' ORDER BY ch_id ASC";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$cidchanges[$row["ch_id"]]["cid"] = $row["ch_cid"];
			$cidchanges[$row["ch_id"]]["gid"] = $row["ch_gid"];
			$cidchanges[$row["ch_id"]]["file"] = $row["ch_file"];
			$cidchanges[$row["ch_id"]]["old"] = $row["ch_old"];
			$cidchanges[$row["ch_id"]]["new"] = $row["ch_new"];
			$cidchanges[$row["ch_id"]]["type"] = $row["ch_type"];
			$cidchanges[$row["ch_id"]]["user"] = $row["ch_user"];
			$cidchanges[$row["ch_id"]]["time"] = $row["ch_time"];
			$cidchanges[$row["ch_id"]]["gid_type"] = $row["ch_gid_type"];
		}
		if (count($cidchanges) > 0) {
			foreach ($cidchanges as $id => $details) {
				$FILE = $details["file"];
				$object = ConstructObject($details["gid"], $details["gid_type"]);
				$gedrec = $object->gedrec;
				// print "Old value of gedrec: ".$gedrec."<br />";
				// NOTE: Import the record
				$update_id = "";
				if (empty($gedrec)) $gedrec = "";
				
				// NOTE: Add anything to existing ID
				// NOTE: If old is empty, just add the new data makes sure it is not new record
				if (empty($details["old"]) && !empty($details["new"]) && preg_match("/0\s@(.*)@/", $details["new"]) == 0) {
					$gedrec .= "\r\n".$details["new"];
					// print "New value of gedrec (add to existing): ".$gedrec."<br />";
					$update_id = self::UpdateRecord(EditFunctions::CheckGedcom($gedrec, true, $details["user"], $details["time"]));
				}
				
				// NOTE: Add new ID
				// NOTE: If the old is empty and the new is a new record make sure we just store the new record
				else if (empty($details["old"]) && preg_match("/0\s@(.*)@/", $details["new"]) > 0) {
					// print "New gedrec: ".$details["new"]."<br />";
					$update_id = self::UpdateRecord(EditFunctions::CheckGedcom($details["new"], true, $details["user"], $details["time"]));
				}
				
				// Note: Delete ID
				// NOTE: if old is not empty and new is  empty, AND it's 0-level, the record needs to be deleted
				else if (!empty($details["old"]) && empty($details["new"])&& preg_match("/0\s@(.*)@/", $details["old"]) > 0) {
					$update_id = self::UpdateRecord(EditFunctions::CheckGedcom($gedrec, true, $details["user"], $details["time"]), true);
					
					// NOTE: Delete change records related to this record
					$sql = "select ch_cid from ".TBLPREFIX."changes where ch_gid = '".$details["gid"]."' AND ch_file = '".$details["file"]."'";
					$res = NewQuery($sql);
					while ($row = $res->FetchAssoc()) {
						self::RejectChange($row["ch_cid"], $details["file"]);
					}
					
				}
				
				// NOTE: Change anything on an existing ID
				// NOTE: If new is empty or filled, the old needs to be replaced
				else {
					if ($details["type"] == "raw_edit") $gedrec = $details["new"];
					else {
						$gedrec = str_replace(trim($details["old"]), trim($details["new"]), $gedrec);
					}
	//				print "Acceptchange: ".$gedrec;
					$update_id = self::UpdateRecord(EditFunctions::CheckGedcom($gedrec, true, $details["user"], $details["time"]));
				}
				WriteToLog("AcceptChange-> Accepted change for ".$details["gid"].". ->".$gm_user->username."<-", "I", "G", $gedfile);
			}
			GedcomConfig::ResetCaches($GEDCOMID);
			self::ResetChangeCaches();
		}
		// NOTE: record has been imported in DB, now remove the change
		foreach ($cidchanges as $id => $value) {
			$sql = "DELETE from ".TBLPREFIX."changes where ch_cid = '".$value["cid"]."'";
			$res = NewQuery($sql);
		}
		return true;
	}
	
	/**
	 * Reject a change
	 *
	 * This function will remove a change from the changes table. When the user
	 * has chosen to reject all changes, they will all be removed
	 *
	 * @author	Genmod Development Team
	 * @param		string 	$cid		The change id of the form gid_gedcom
	 * @param		int 		$gedfile	The file to which the changes belong
	 * @param		boolean	$all		Whether to reject all changes or not
	 * @return 	boolean	true if undo successful
	 */
	function RejectChange($cid, $gedfile, $all=false) {
		global $manual_save, $gm_user;
		
		// NOTE: Get the details of the change id, to check if we need to unlock any records
		$sql = "SELECT ch_type, ch_gid from ".TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_file = '".$gedfile."'";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()) {
			$unlock_changes = array("raw_edit", "reorder_families", "reorder_children", "delete_source", "delete_indi", "delete_family", "delete_repo");
			if (in_array($row["ch_type"], $unlock_changes)) {
				$sql = "select ch_cid, ch_type from ".TBLPREFIX."changes where ch_gid = '".$row["ch_gid"]."' and ch_file = '".$gedfile."' order by ch_cid ASC";
				$res2 = NewQuery($sql);
				while($row2 = $res2->FetchAssoc()) {
					$sqlcid = "UPDATE ".TBLPREFIX."changes SET ch_delete = '0' WHERE ch_cid = '".$row2["ch_cid"]."'";
					$rescid = NewQuery($sqlcid);
				}
			}
		}
		
		if ($all) {
			$sql = "DELETE from ".TBLPREFIX."changes where ch_file = '".$gedfile."'";
			if ($res = NewQuery($sql)) {
				WriteToLog("RejectChange-> Rejected all changes for $gedfile "." ->" . $gm_user->username ."<-", "I", "G", $gedfile);
				self::ResetChangeCaches();
				return true;
			}
			else return false;
		}
		else {
			$sql = "DELETE from ".TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_file = '".$gedfile."'";
			if ($res = NewQuery($sql)) {
				WriteToLog("RejectChange-> Rejected change $cid - $gedfile "." ->" . $gm_user->username ."<-", "I", "G", $gedfile);
				self::ResetChangeCaches();
				return true;
			}
			else return false;
		}
	}
	
	/**
	 * update a record in the database
	 * @param string $indirec
	 */
	public function UpdateRecord($indirec, $delete=false) {
		global $GEDCOMID;
	
		$tt = preg_match("/0 @(.+)@ (.+)/", $indirec, $match);
		if ($tt>0) {
			$gid = trim($match[1]);
			$type = trim($match[2]);
		}
		else {
			$ct2 = preg_match("/0 HEAD/", $indirec, $match2);
			if ($ct2 == 0) {
				print "ERROR: Invalid gedcom record.<br />";
				print "<pre>".$indirec."</pre>";
				return false;
			}
			else {
				$type = "HEAD";
				$gid = "HEAD";
			}
		}
		$kgid = JoinKey($gid, $GEDCOMID);
		
		$sql = "SELECT pl_p_id FROM ".TBLPREFIX."placelinks WHERE pl_gid='".DbLayer::EscapeQuery($gid)."' AND pl_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$placeids = array();
		while($row = $res->fetchRow()) {
			$placeids[] = $row[0];
		}
		$sql = "DELETE FROM ".TBLPREFIX."placelinks WHERE pl_gid='".DbLayer::EscapeQuery($gid)."' AND pl_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."dates WHERE d_key='".DbLayer::EscapeQuery(JoinKey($gid, $GEDCOMID))."'";
		$res = NewQuery($sql);
	
		//-- delete any unlinked places
		foreach($placeids as $indexval => $p_id) {
			$sql = "SELECT count(pl_p_id) FROM ".TBLPREFIX."placelinks WHERE pl_p_id=$p_id AND pl_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$row = $res->fetchRow();
			if ($row[0]==0) {
				$sql = "DELETE FROM ".TBLPREFIX."places WHERE p_id=$p_id AND p_file='".$GEDCOMID."'";
				$res = NewQuery($sql);
			}
		}
	
		//-- delete any MM links to this pid
			$sql = "DELETE FROM ".TBLPREFIX."media_mapping WHERE mm_gid='".DbLayer::EscapeQuery($gid)."' AND mm_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		
		if ($type=="INDI") {
			// First reset the isdead status for the surrounding records. 
			ResetIsDeadLinked($gid, "INDI");
			$sql = "DELETE FROM ".TBLPREFIX."individuals WHERE i_id='".DbLayer::EscapeQuery($gid)."' AND i_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."asso WHERE as_of='".DbLayer::EscapeQuery(JoinKey($gid, $GEDCOMID))."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."names WHERE n_gid='".DbLayer::EscapeQuery($gid)."' AND n_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."soundex WHERE s_gid='".DbLayer::EscapeQuery($kgid)."'";
			$res = NewQuery($sql);
			// Only delete the fam-indi info if the whole individual is deleted. 
			// Otherwise the info does not get reconstructed as some of it is in the family records (order).
			if ($delete) {
				$sql = "DELETE FROM ".TBLPREFIX."individual_family WHERE if_pkey='".JoinKey(DbLayer::EscapeQuery($gid), $GEDCOMID)."'";
				$res = NewQuery($sql);
			}
			$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
		else if ($type=="FAM") {
			// First reset the isdead status for the surrounding records. 
			ResetIsDeadLinked($gid, "FAM");
			$sql = "DELETE FROM ".TBLPREFIX."families WHERE f_id='".DbLayer::EscapeQuery($gid)."' AND f_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."asso WHERE as_of='".DbLayer::EscapeQuery(JoinKey($gid, $GEDCOMID))."'";
			$res = NewQuery($sql);
			// Only delete the fam-indi info if the whole family is deleted. 
			// Otherwise the info does not get reconstructed as most of it is in the individual records.
			if ($delete) {
				$sql = "DELETE FROM ".TBLPREFIX."individual_family WHERE if_fkey='".JoinKey(DbLayer::EscapeQuery($gid), $GEDCOMID)."'";
				$res = NewQuery($sql);
			}
			$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
		else if ($type=="SOUR") {
			$sql = "DELETE FROM ".TBLPREFIX."sources WHERE s_id='".DbLayer::EscapeQuery($gid)."' AND s_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			// We must preserve the links if the record is just changed and not deleted. 
			if ($delete) {
				$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_sid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
				$res = NewQuery($sql);
			}
		}
		else if ($type == "OBJE") {
			$sql = "DELETE FROM ".TBLPREFIX."media WHERE m_media='".DbLayer::EscapeQuery($gid)."' AND m_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
		}
		else {
			$sql = "DELETE FROM ".TBLPREFIX."other WHERE o_id='".DbLayer::EscapeQuery($gid)."' AND o_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			// We must preserve the links if the record is just changed and not deleted. 
			if ($delete) {
				$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
				$res = NewQuery($sql);
			}
		}
		if ($delete) {
			if ($type == "FAM" || $type = "INDI" || $type == "SOUR" || $type == "OBJE") {
				// Delete favs
				$sql = "DELETE FROM ".TBLPREFIX."favorites WHERE fv_gid='".$gid."' AND fv_type='".$type."' AND fv_file='".$GEDCOMID."'";
				$res = NewQuery($sql);
			}
			if ($type == "INDI") {
				// Clear users
				UserController::ClearUserGedcomIDs($gid, $GEDCOMID);
				if (GedcomConfig::$PEDIGREE_ROOT_ID == $gid) {
					GedcomConfig::$PEDIGREE_ROOT_ID = "";
					GedcomConfig::SetPedigreeRootId("", $GEDCOMID);
				}
			}
			// Clear privacy
			PrivacyController::ClearPrivacyGedcomIDs($gid, $GEDCOMID);
		}
	
		if (!$delete) {
			ImportFunctions::ImportRecord($indirec, true. $GEDCOMID);
		}
	}	
	
	public function IsChangedFact($gid, $oldfactrec) {
		global $GEDCOMID, $show_changes, $gm_user;
		
	//print "checking ".$gid." ".$oldfactrec."<br />";
		if ($show_changes && $gm_user->UserCanEditOwn($gid) && self::GetChangeData(true, $gid, true)) {
			$string = trim($oldfactrec);
			if (empty($string)) return false;
			$sql = "SELECT ch_old, ch_new FROM ".TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_file = '".$GEDCOMID."' ORDER BY ch_time ASC";
			$res = NewQuery($sql);
			if (!$res) return false;
			while ($row = $res->FetchRow()) {
				if (trim($row[0]) == trim($oldfactrec)) {
					return true;
				}
			}
		}
		return false;
	}
	
	
	public function RetrieveChangedFact($gid, $fact, $oldfactrec) {
		global $GEDCOMID, $show_changes, $gm_user;
		
		if ($show_changes && $gm_user->UserCanEditOwn($gid) && self::GetChangeData(true, $gid, true)) {
			$sql = "SELECT ch_old, ch_new FROM ".TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_fact = '".$fact."' AND ch_file = '".$GEDCOMID."' ORDER BY ch_time ASC";
			$res = NewQuery($sql);
			$factrec = $oldfactrec;
			$found = false;
			while ($row = $res->FetchAssoc()) {
				if (trim($row["ch_old"]) == trim($factrec)) {
					$factrec = trim($row["ch_new"]);
					$found = true;
				}
			}
			if ($found) return $factrec;
		}
		return false;
	}
	
	public function RetrieveNewFacts($gid, $includeall=false) {
		global $GEDCOMID, $show_changes;
		
		$facts = array();
		$newfacts = array();
		if ($show_changes && self::GetChangeData(true, $gid, true)) {
			$sql = "SELECT ch_old, ch_new FROM ".TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_file = '".$GEDCOMID."' ORDER BY ch_time ASC";
			$res = NewQuery($sql);
			if ($res) {
				while($row = $res->FetchAssoc()){
					if ($row["ch_old"] == "" && preg_match("/0 @.*@/", $row["ch_new"], $match) > 0) {
						$subs = getallsubrecords($row["ch_new"], "", false, false, false);
						foreach ($subs as $key => $sub) {
							$ct = preg_match("/\d (\w+) /", $sub, $match);
							$tag = $match[1];
							$facts[] = array("tag"=> $tag, "old"=>"", "new"=>$sub);
						}
					}
					else {
						$found = false;
						$ct = preg_match("/1\s+(\w+).*/", $row["ch_old"], $match);
						if ($ct == 0) $ct = preg_match("/1\s+(\w+).*/", $row["ch_new"], $match);
						if ($ct != 0) {
							$tag = $match[1];
							foreach ($facts as $key => $fact) {
								if (isset($fact["old"]) && trim($fact["new"]) == trim($row["ch_old"]) && $fact["tag"] == $tag) {
									$facts[$key]["new"] = $row["ch_new"];
									$found = true;
									break;
								}
							}
							if (!$found) $facts[] = array("tag"=>$tag, "old"=>$row["ch_old"], "new"=>$row["ch_new"]);
						}
					}
				}
				foreach($facts as $key => $fact) {
					if (empty($fact["old"])) {
						//print "Added--->".$fact["new"]."<BR>";
						$newfacts[] = $fact["new"];
					} else if (empty($fact["new"])) {
						//print "Deleted--->".$fact["old"]."<BR>";
						if ($includeall) {
							$pos = strpos ($fact["old"], "\n");
							if ($pos!==false) {
								$fact["old"] = substr($fact["old"], 0, $pos);
							}
							$fact["old"] .= "\n2 DATE (".strtolower(GM_LANG_delete).")";
							$newfacts[] = $fact["old"];
						}
					} else {
						//print "Modified--->".$fact["new"]."<BR>";
						if ($includeall) {
							$newfacts[] = $fact["new"];
						}
					}
				}
			}
		}
		return $newfacts;
	}
	
	public function HasChangedMedia($gedrec) {
		
		if (empty($gedrec)) return false;
		$ct = preg_match_all("/\d\sOBJE\s@(.*)@/", $gedrec, $match);
		for ($i=0;$i<$ct;$i++) {
			if (self::GetChangeData(true, $match[1][$i], true)) return true;
		}
		return false;
	}
	
	public function ResetChangeCaches() {
		
		// Use globals here, otherwise the cache won't be reset.
		unset($GLOBALS['chcache']);
		unset($GLOBALS['chstatcache']);
	}

	
}
?>