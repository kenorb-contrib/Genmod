<?php
/**
 * Various functions used by the Edit interface
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
 * @version $Id: functions_edit_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

include_once("includes/values/edit_data.php");

abstract class EditFunctions {
	/**
	 * Replace a gedcom record
	 *
	 * It takes an old and a new gedcom record and stores it in the changes table.
	 * Further the type of change (replace person, edit source...) and the change
	 * ID are stored. The change ID is used to keep of all changes made in 1 single
	 * action since they all need to be approved at once.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$gid			The ID of the item that is being changes
	 * @param		string	$oldrec		The old gedcom record
	 * @param		string	$new			The new gedcom record
	 * @param		string	$fact		The fact that has been changed
	 * @param		string	$change_id	The ID that is used for the change
	 * @param		string	$change_type	The name of the change
	 * @return 	boolean	true if succeed/false if failed
	 */
	public function ReplaceGedrec($gid, $oldrec, $newrec, $fact="", $change_id, $change_type, $gedid="", $gid_type) {
		global $manual_save, $gm_user, $can_auto_accept, $aa_attempt;
	
		// NOTE: Check if auto accept is possible. If there are already changes present for any ID in one $change_id, changes cannot be auto accepted.
		if (!isset($can_auto_accept)) $can_auto_accept = true;
		if ($can_auto_accept) {
			if (self::HasOtherChanges($gid, $change_id)) {
				// We already have changes for this ID, so we cannot auto accept!
				$can_auto_accept = false;
			}
		}
		// NOTE: Uppercase the ID to make sure it is consistent
		$gid = strtoupper($gid);
		$newrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $newrec);
		$newrec = trim($newrec);
		// NOTE: Determine which gedcom is being updated
		if ($gedid == "") $gedid = GedcomConfig::$GEDCOMID;
		
		//-- the following block of code checks if the XREF was changed in this record.
		//-- if it was changed we add a warning to the change log
		$ct = preg_match("/0 @(.*)@/", $newrec, $match);
		if ($ct>0) {
			$oldgid = $gid;
			$gid = trim($match[1]);
			if ($oldgid!=$gid) {
				WriteToLog("ReplaceGedrec-&gt; Warning: $oldgid was changed to $gid", "W", "G", $gedid);
			}
		}
		
		// NOTE: Check if there are changes present, if so flag pending changes so they cannot be approved
		if (ChangeFunctions::GetChangeData(true, $gid, true) && ($change_type == "raw_edit" || $change_type == "reorder_families" || $change_type == "reorder_children")) {
			$sql = "select ch_cid as cid from ".TBLPREFIX."changes where ch_gid = '".$gid."' and ch_file = '".$gedid."' order by ch_cid ASC";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()){
				$sqlcid = "update ".TBLPREFIX."changes set ch_delete = '1' where ch_cid = '".$row["cid"]."'";
				$rescid = NewQuery($sqlcid);
			}
		}
	
		$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_file, ch_type, ch_user, ch_time, ch_fact, ch_old, ch_new, ch_gid_type)";
		$sql .= "VALUES ('".$change_id."', '".$gid."', '".$gedid."', '".$change_type."', '".$gm_user->username."', '".time()."'";
		$sql .= ", '".$fact."', '".DbLayer::EscapeQuery($oldrec)."', '".DbLayer::EscapeQuery($newrec)."', '".trim($gid_type)."')";
		$res = NewQuery($sql);
		
		WriteToLog("ReplaceGedrec-&gt; Replacing gedcom record $gid -&gt;" . $gm_user->username ."&lt;-", "I", "G", $gedid);
		// Clear the ChangeFunctions::GetChangeData cache
		ChangeFunctions::ResetChangeCaches();
		return true;
	}
	
	//-------------------------------------------- AppendGedrec
	//-- this function will append a new gedcom record at
	//-- the end of the gedcom file.
	public function AppendGedrec($newrec, $fact="", $change_id, $change_type, $gedid="") {
		global $manual_save, $gm_user;
	
		$newrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $newrec);
		$newrec = stripslashes(trim($newrec));
		// NOTE: Determine which gedcom is being updated
		if ($gedid == "") $gedid = GedcomConfig::$GEDCOMID;
		
		$ct = preg_match("/0 @(.*)@\s(\w+)/", $newrec, $match);
		$type = trim($match[2]);
		$xref = self::GetNewXref($type);
		$_SESSION["last_used"][$type] = JoinKey($xref, GedcomConfig::$GEDCOMID);
		$newrec = preg_replace("/0 @(.*)@/", "0 @$xref@", $newrec);
		
		$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_file, ch_type, ch_user, ch_time, ch_fact, ch_new, ch_gid_type)";
		$sql .= "VALUES ('".$change_id."', '".$xref."', '".$gedid."', '".$change_type."', '".$gm_user->username."', '".time()."', '".$fact."', '".DbLayer::EscapeQuery($newrec)."', '".$type."')";
		$res = NewQuery($sql);
		WriteToLog("AppendGedrec-&gt; Appending new $type record $xref -&gt;" . $gm_user->username ."&lt;-", "I", "G", $gedid);
	
		// Clear the ChangeFunctions::GetChangeData cache
		ChangeFunctions::ResetChangeCaches();
		return $xref;
	}
	
	//-------------------------------------------- delete_gedrec
	//-- this function will delete the gedcom record with
	//-- the given $gid
	public function DeleteGedrec($gid, $change_id, $change_type, $gid_type) {
		global $manual_save, $gm_user, $can_auto_accept, $aa_attempt;
		
		$gid = strtoupper($gid);

		// NOTE: Check if auto accept is possible. If there are already changes present for any ID in one $change_id, changes cannot be auto accepted.
		if ($can_auto_accept) {
			if (self::HasOtherChanges($gid, $change_id)) {
				// We already have changes for this ID, so we cannot auto accept!
				$can_auto_accept = false;
			}
		}
		// NOTE: Check if there are changes present, if so flag pending changes so they cannot be approved
		if (ChangeFunctions::GetChangeData(true, $gid, true)) {
			$sql = "SELECT ch_cid AS cid FROM ".TBLPREFIX."changes WHERE ch_gid = '".$gid."' AND ch_file = '".GedcomConfig::$GEDCOMID."' ORDER BY ch_cid ASC";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()){
				$sqlcid = "UPDATE ".TBLPREFIX."changes SET ch_delete = '1' WHERE ch_cid = '".$row["cid"]."'";
				$rescid = NewQuery($sqlcid);
			}
			// Clear the ChangeFunctions::GetChangeData cache
			ChangeFunctions::ResetChangeCaches();
		}
		
		// NOTE Check if record exists in the database
		$object = ReConstructObject($gid, $gid_type);
		if (!is_object($object) || $object->isempty) {
			print "DeleteGedrec-> Could not find gedcom record with xref: $gid. <br />";
			WriteToLog("DeleteGedrec-&gt; Could not find gedcom record with xref: $gid -&gt;" . $gm_user->username ."&lt;-", "E", "G", GedcomConfig::$GEDCOMID);
			return false;
		}
		else {
			if ($object->ischanged) $oldrec = $object->changedgedrec;
			else $oldrec = $object->gedrec;
			$ct = preg_match("/0 @.*@\s(\w+)\s/", $oldrec, $match);
			$ch_fact = $match[1];
			$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_fact, ch_old, ch_file, ch_type, ch_user, ch_time, ch_gid_type)";
			$sql .= "VALUES ('".$change_id."', '".$gid."', '".$ch_fact."', '".DbLayer::EscapeQuery($oldrec)."', '".GedcomConfig::$GEDCOMID."', '".$change_type."', '".$gm_user->username."', '".time()."', '".$gid_type."')";
			$res = NewQuery($sql);
			// Also delete the asso recs to an indi, to preserve referential integrity
			if ($ch_fact == "INDI" || $ch_fact == "FAM") {
				$assos = ListFunctions::GetAssoList($ch_fact, $gid);
				foreach ($assos as $p1key => $pidassos) {
					foreach ($pidassos as $nothing =>$asso) {
						$pid1 = $asso->xref1;
						$pid2 = $asso->xref2;
						if ($asso->assoperson->ischanged) $arec = $asso->assoperson->changedgedrec;
						else $arec = $asso->assoperson->gedrec;
						if (strstr($arec, "1 ASSO")) {
							$i = 1;
							do {
								$asubrec = GetSubrecord(1, "1 ASSO @".$pid1."@", $arec, $i);
								if (!empty($asubrec)) {
									$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_fact, ch_old, ch_file, ch_type, ch_user, ch_time, ch_gid_type)";
									$sql .= "VALUES ('".$change_id."', '".$pid2."', 'ASSO', '".DbLayer::EscapeQuery($asubrec)."', '".GedcomConfig::$GEDCOMID."', '".$change_type."', '".$gm_user->username."', '".time()."', 'INDI')";
								}
								$i++;
							} while (!empty($asubrec));
						}
						if (strstr($arec, "2 ASSO")) {
							$recs = GetAllSubrecords($arec, "CHAN", false, false, false);
							foreach ($recs as $sub => $subrec) {
								if (preg_match("/\n2 ASSO @$pid1@/", $subrec, $match)) {
									$asubrec = GetSubrecord(2, "2 ASSO @".$pid1."@", $subrec);
									$newsubrec = preg_replace($asubrec, "", $subrec);
									$sql = "INSERT INTO ".TBLPREFIX."changes (ch_cid, ch_gid, ch_fact, ch_old, ch_new, ch_file, ch_type, ch_user, ch_time, ch_gid_type)";
									$sql .= "VALUES ('".$change_id."', '".$pid2."', 'ASSO', '".DbLayer::EscapeQuery($subrec)."', '".DbLayer::EscapeQuery($newsubrec)."', '".GedcomConfig::$GEDCOMID."', '".$change_type."', '".$gm_user->username."', '".time()."', 'INDI')";
								}
							}
						}
					}
				}
			}
						
				
		}
		WriteToLog("DeleteGedrec-&gt; Deleting gedcom record $gid -&gt;" . $gm_user->username ."&lt;-", "I", "G", GedcomConfig::$GEDCOMID);
		// Clear the ChangeFunctions::GetChangeData cache
		ChangeFunctions::ResetChangeCaches();
		return true;
	}
	
	//-------------------------------------------- check_gedcom
	//-- this function will check a GEDCOM record for valid gedcom format
	public function CheckGedcom($gedrec, $chan=true, $user="", $tstamp="") {
		global $gm_user;
	
		$gedrec = stripslashes($gedrec);
		$ct = preg_match("/0 @(.*)@ (.*)/", $gedrec, $match);
		
		if ($ct==0) {
			$ct2 = preg_match("/0 HEAD/", $gedrec, $match2);
			if ($ct2 == 0) {
				print "CheckGedcom-> Invalid GEDCOM 5.5 format.\n";
				print "<pre>".$gedrec."</pre>";
				WriteToLog("CheckGedcom-&gt; Invalid GEDCOM 5.5 format.-&gt;" . $gm_user->username ."&lt;-", "I", "G", GedcomConfig::$GEDCOMID);
				print $pipo;
				return false;
			}
		}
		$gedrec = trim($gedrec);
		if ($chan) {
			if(empty($user)) $user = $gm_user->username;
			if (!empty($tstamp)) {
				$newd = date("d M Y", $tstamp);
				$newt = date("H:i:s", $tstamp);
			}
			else {
				$newd = date("d M Y");
				$newt = date("H:i:s");
			}
			$pos1 = strpos($gedrec, "1 CHAN");
			if ($pos1!==false) {
				$pos2 = strpos($gedrec, "\n1", $pos1+4);
				if ($pos2===false) $pos2 = strlen($gedrec);
				$newgedrec = substr($gedrec, 0, $pos1);
				$newgedrec .= "1 CHAN\r\n2 DATE ".$newd."\r\n";
				$newgedrec .= "3 TIME ".$newt."\r\n";
				$newgedrec .= "2 _GMU ".$user."\r\n";
				$newgedrec .= substr($gedrec, $pos2);
				$gedrec = $newgedrec;
			}
			else if (!isset($ct2)) {
				$newgedrec = "\r\n1 CHAN\r\n2 DATE ".$newd."\r\n";
				$newgedrec .= "3 TIME ".$newt."\r\n";
				$newgedrec .= "2 _GMU ".$user;
				$gedrec .= $newgedrec;
			}
		}
		$gedrec = preg_replace("/([\r\n])+/", "\r\n", $gedrec);
		return $gedrec;
	}
	
	/**
	 * prints a form to add an individual or edit an individual's name
	 *
	 * @param string $nextaction	the next action the edit_interface.php file should take after the form is submitted
	 * @param string $famid			the family that the new person should be added to
	 * @param string $namerec		the name subrecord when editing a name
	 * @param string $famtag		how the new person is added to the family
	 */
	public function PrintIndiForm($nextaction, $famid, $linenum="", $namerec="", $famtag="CHIL", $count="1") {
		global $pid, $GM_IMAGES, $monthtonum;
		global $NPFX_accept, $SPFX_accept, $NSFX_accept, $FILE_FORM_accept, $change_type, $pid_type;
		global $gm_user;
	
		InitCalendarPopUp();
		print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"".$nextaction."\" />\n";
		print "<input type=\"hidden\" name=\"famid\" value=\"".$famid."\" />\n";
		print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
		print "<input type=\"hidden\" name=\"count\" value=\"".$count."\" />\n";
		print "<input type=\"hidden\" name=\"pid_type\" value=\"".$pid_type."\" />\n";
		print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
		print "<table class=\"NavBlockTable EditTable\">";
		
		// preset child/father SURN
		$surn = "";
		if (empty($namerec)) {
			$namerec = "";
			if ($famtag == "CHIL" && $nextaction == "addchildaction") {
				// The child will get the fathers surname as preset
				$famrec = "";
				if (!empty($famid)) {
					$family =& Family::GetInstance($famid);
					if (is_object($family->husb)) $namerec = "1 NAME ".$family->husb->name_array[0][0];
				}
			}
			if ($famtag == "HUSB" && $nextaction == "addnewparentaction") {
				// The husband will get the surname of one of the kids as preset
				// The family may not exist yet.....
				if ($famid != "new") {
					$family =& Family::GetInstance($famid);
					foreach ($family->children as $key => $child) {
						$namerec = "1 NAME ".$child->name_array[0][0];
						break;
					}
				}
				else {
					$person =& Person::GetInstance($pid);
					if (!$person->isempty) $namerec = "1 NAME ".$person->name_array[0][0];
				}
			}
			// For the wife we don't know....
			$nt = preg_match("~1 NAME (.*)/(.*)/~", $namerec, $ntmatch);
			if ($nt) $surn = $ntmatch[2];
			if ($surn) $namerec = "1 NAME  /".trim($surn,"\r\n")."/";
		}
		self::AddTagSeparator("NAME");
		// handle PAF extra NPFX [ 961860 ]
		$nt = preg_match("/\d NPFX (.*)/", $namerec, $nmatch);
		$npfx=trim(@$nmatch[1]);
		// 1 NAME = NPFX GIVN /SURN/ NSFX
		$nt = preg_match("/\d NAME (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		if (strlen($npfx) && strpos($name, $npfx) === false) $name = $npfx." ".$name;
		self::AddSimpleTag("0 NAME ".$name);
		// 2 NPFX
		self::AddSimpleTag("0 NPFX ".$npfx);
		// 2 GIVN
		// Start input field is here
		$nt = preg_match("/\d GIVN (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		$focusfld = self::AddSimpleTag("0 GIVN ".$name);
		// 2 NICK
		$nt = preg_match("/\d NICK (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		self::AddSimpleTag("0 NICK ".$name);
		// 2 SPFX
		$nt = preg_match("/\d SPFX (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		self::AddSimpleTag("0 SPFX ".$name);
		// 2 SURN
		$nt = preg_match("/\d SURN (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		self::AddSimpleTag("0 SURN ".$name);
		// 2 NSFX
		$nt = preg_match("/\d NSFX (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		self::AddSimpleTag("0 NSFX ".$name);
		// 2 _HEB
		$nt = preg_match("/\d _HEB (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		if ($nt>0 || GedcomConfig::$USE_RTL_FUNCTIONS) {
			self::AddSimpleTag("0 _HEB ".$name);
		}
		// 2 ROMN
		$nt = preg_match("/\d ROMN (.*)/", $namerec, $nmatch);
		if ($nt) $name = $nmatch[1];
		else $name = "";
		self::AddSimpleTag("0 ROMN ".$name);
	
		if ($surn) $namerec = ""; // reset if modified

		if (empty($namerec)) {
			// 2 _MARNM
			self::AddSimpleTag("0 _MARNM");
			// 1 SEX
			self::AddTagSeparator("SEX");
			if ($famtag=="HUSB") self::AddSimpleTag("0 SEX M");
			else if ($famtag=="WIFE") self::AddSimpleTag("0 SEX F");
			else self::AddSimpleTag("0 SEX");
			// 1 BIRT
			// 2 DATE
			// 2 PLAC
			self::AddTagSeparator("BIRT");
			self::AddSimpleTag("0 BIRT");
			self::AddSimpleTag("0 DATE", "BIRT");
			self::AddSimpleTag("0 TIME", "BIRT");
			self::AddSimpleTag("0 PLAC", "BIRT");
			// 1 CHR
			// 2 DATE
			// 2 PLAC
			self::AddTagSeparator("CHR");
			self::AddSimpleTag("0 CHR");
			self::AddSimpleTag("0 DATE", "CHR");
			self::AddSimpleTag("0 TIME", "CHR");
			self::AddSimpleTag("0 PLAC", "CHR");
			// 1 DEAT
			// 2 DATE
			// 2 PLAC
			self::AddTagSeparator("DEAT");
			self::AddSimpleTag("0 DEAT");
			self::AddSimpleTag("0 DATE", "DEAT");
			self::AddSimpleTag("0 TIME", "DEAT");
			self::AddSimpleTag("0 PLAC", "DEAT");
			// print $famtag." ".$nextaction;
			if ($famtag == "CHIL" && $nextaction == "addchildaction" && !empty($famid)) {
				self::AddTagSeparator("PEDI");
				self::AddSimpleTag("0 PEDI", "", 1, true);
			}
			//-- if adding a spouse add the option to add a marriage fact to the new family
			if ($nextaction=='addspouseaction' || ($nextaction=='addnewparentaction' && $famid!='new')) {
				print "\n";
				self::AddTagSeparator("MARR");
				self::AddSimpleTag("0 MARR");
				self::AddSimpleTag("0 DATE", "MARR");
				self::AddSimpleTag("0 PLAC", "MARR");
			}
			if ($nextaction == "addnewparentaction" && $famid == 'new') {
				$child =& Person::GetInstance($pid);
				$showbio = true;
				foreach ($child->childfamilies as $id => $family) {
					if ($family->pedigreetype == "") {
						$showbio = false;
						break;
					}
				}
				self::AddTagSeparator("PEDI");
				self::AddSimpleTag("0 PEDI", "", 1, $showbio);
			}
				
			self::PrintAddLayer("SOUR", 1, true);
			self::PrintAddLayer("OBJE", 1);
			self::PrintAddLayer("NOTE", 1);
			self::PrintAddLayer("GNOTE", 1);
			self::AddAutoAcceptLink();
			print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\" value=\"".GM_LANG_save."\" /></td></tr>\n";
			print "</table>\n";
		}
		else {
			if ($namerec!="NEW") {
				$gedlines = explode("\n", $namerec);	// -- find the number of lines in the record
				$fields = preg_split("/\s/", $gedlines[0]);
				$glevel = $fields[0];
				$level = $glevel;
				$type = trim($fields[1]);
				$level1type = $type;
				$tags=array();
				$i = 0;
				$namefacts = array("NPFX", "GIVN", "NICK", "SPFX", "SURN", "NSFX", "NAME", "_HEB", "ROMN");
				do {
					if (!in_array($type, $namefacts)) {
						$text = "";
						for($j=2; $j<count($fields); $j++) {
							if ($j>2) $text .= " ";
							$text .= $fields[$j];
						}
						$iscont = false;
						while(($i+1<count($gedlines))&&(preg_match("/".($level+1)." (CON[CT])\s?(.*)/", $gedlines[$i+1], $cmatch)>0)) {
							$iscont=true;
							if ($cmatch[1]=="CONT") $text.="\r\n";
							if (GedcomConfig::$WORD_WRAPPED_NOTES) $text .= " ";
							$text .= $cmatch[2];
							$i++;
						}
						self::AddSimpleTag($level." ".$type." ".$text);
					}
					$tags[]=$type;
					$i++;
					if (isset($gedlines[$i])) {
						$fields = preg_split("/\s/", $gedlines[$i]);
						$level = $fields[0];
						if (isset($fields[1])) $type = trim($fields[1]);
					}
				} while (($level>$glevel)&&($i<count($gedlines)));
			}
			// 2 _MARNM
			self::AddSimpleTag("0 _MARNM");
			self::PrintAddLayer("SOUR");
			self::PrintAddLayer("NOTE");
			self::PrintAddLayer("GNOTE");
			self::AddAutoAcceptLink();
			print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"button\" value=\"".GM_LANG_save."\" onclick=\"document.addchildform.submit(); return false;\"/></td></tr>\n";
			print "</table>\n";
		}
		print "</form>\n";
		if ($nextaction != "update") print "<br /><div id=\"show_ids\"><a href=\"javascript: ".GM_LANG_show_next_id."\" onclick=\"sndReq('show_ids', 'getnextids', true); return false;\">".GM_LANG_show_next_id."</a></div>";
		?>
		<script type="text/javascript" src="autocomplete.js"></script>
		<script type="text/javascript">
		<!--
		//	copy php arrays into js arrays
		var npfx_accept = new Array(<?php foreach ($NPFX_accept as $indexval => $npfx) print "'".$npfx."',"; print "''";?>);
		var spfx_accept = new Array(<?php foreach ($SPFX_accept as $indexval => $spfx) print "'".$spfx."',"; print "''";?>);
		Array.prototype.in_array = function(val) {
			for (var i in this) {
				if (this[i] == val) return true;
			}
			return false;
		}
		function trim(str) {
			return str.replace(/(^\s*)|(\s*$)/g,'');
		}
		function updatewholename() {
			frm = document.forms[0];
			var npfx=trim(frm.NPFX.value);
			if (npfx) npfx+=" ";
			var givn=trim(frm.GIVN.value);
			var spfx=trim(frm.SPFX.value);
			if (spfx) spfx+=" ";
			var surn=trim(frm.SURN.value);
			var nsfx=trim(frm.NSFX.value);
			frm.NAME.value = npfx + givn + " /" + spfx + surn + "/ " + nsfx;
		}
		function togglename() {
			frm = document.forms[0];
	
			// show/hide NAME
			var ronly = frm.NAME.readOnly;
			if (ronly) {
				updatewholename();
				frm.NAME.readOnly=false;
				frm.NAME_spec.style.display="inline";
				frm.NAME_plus.style.display="inline";
				frm.NAME_minus.style.display="none";
				disp="none";
			}
			else {
				// split NAME = (NPFX) GIVN / (SPFX) SURN / (NSFX)
				var name=frm.NAME.value+'//';
				var name_array=name.split("/");
				var givn=trim(name_array[0]);
				var givn_array=givn.split(" ");
				var surn=trim(name_array[1]);
				var surn_array=surn.split(" ");
				var nsfx=trim(name_array[2]);
	
				// NPFX
				var npfx='';
				do {
					search=givn_array[0]; // first word
					search=search.replace(/(\.*$)/g,''); // remove trailing '.'
					if (npfx_accept.in_array(search)) npfx+=givn_array.shift()+' ';
					else break;
				} while (givn_array.length>0);
				frm.NPFX.value=trim(npfx);
	
				// GIVN
				frm.GIVN.value=trim(givn_array.join(' '));
	
				// SPFX
				var spfx='';
				do {
					search=surn_array[0]; // first word
					search=search.replace(/(\.*$)/g,''); // remove trailing '.'
					if (spfx_accept.in_array(search)) spfx+=surn_array.shift()+' ';
					else break;
				} while (surn_array.length>0);
				frm.SPFX.value=trim(spfx);
	
				// SURN
				frm.SURN.value=trim(surn_array.join(' '));
	
				// NSFX
				frm.NSFX.value=trim(nsfx);
	
				// NAME
				frm.NAME.readOnly=true;
				frm.NAME_spec.style.display="none";
				frm.NAME_plus.style.display="none";
				frm.NAME_minus.style.display="inline";
				disp="table-row";
				if (document.all) disp="inline"; // IE
			}
			// show/hide
			document.getElementById("NPFX_tr").style.display=disp;
			document.getElementById("GIVN_tr").style.display=disp;
			document.getElementById("NICK_tr").style.display=disp;
			document.getElementById("SPFX_tr").style.display=disp;
			document.getElementById("SURN_tr").style.display=disp;
			document.getElementById("NSFX_tr").style.display=disp;
		}
		function checkform() {
			frm = document.addchildform;
			/* if (frm.GIVN.value=="") {
				alert('<?php print GM_LANG_must_provide; print GM_LANG_given_name; ?>');
				frm.GIVN.focus();
				return false;
			}
			if (frm.SURN.value=="") {
				alert('<?php print GM_LANG_must_provide; print GM_LANG_surname; ?>');
				frm.SURN.focus();
				return false;
			}*/
			var fname=frm.NAME.value;
			fname=fname.replace(/ /g,'');
			fname=fname.replace(/\//g,'');
			if (fname=="") {
				alert('<?php print GM_LANG_must_provide; print " ".GM_FACT_NAME; ?>');
				frm.NAME.focus();
				return false;
			}
			return true;
		}
		//-->
		</script>
		<?php
		// force name expand on form load (maybe optional in a further release...)
		print "<script type='text/javascript'><!--\ntogglename(); document.getElementById('".$focusfld."').focus();\n//-->\n</script>";
	}
	
	/**
	 * generates javascript code for calendar popup in user's language
	 *
	 * @param string id		form text element id where to return date value
	 * @see InitCalendarPopUp()
	 */
	public function PrintCalendarPopup($id) {
		global $GM_IMAGES;
	
		// calendar button
		$text = GM_LANG_select_date;
		if (isset($GM_IMAGES["calendar"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["button"]."\" name=\"img".$id."\" id=\"img".$id."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print "<a href=\"javascript: ".$text."\" onclick=\"cal_toggleDate('caldiv".$id."', '".$id."'); return false;\">";
		print $Link;
		print "</a>\n";
		print "<div id=\"caldiv".$id."\" style=\"position:absolute;visibility:hidden;background-color:white;layer-background-color:white;\"></div>\n";
	}
	
	public function AddTagSeparator($fact="", $separator=true) {
		
		if ($separator) print "<tr><td colspan=\"2\" class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
		print "<tr><td colspan=\"2\" class=\"NavBlockColumnHeader EditTableColumnHeader\">";
		if(!empty($fact)) {
			if (defined("GM_LANG_".$fact)) print constant("GM_LANG_".$fact);
			else if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
			else print $fact;
		}
		print "</td></tr>";
	}
	
	
	/**
	 * add a new tag input field
	 *
	 * called for each fact to be edited on a form.
	 * Fact level=0 means a new empty form : data are POSTed by name
	 * else data are POSTed using arrays :
	 * glevels[] : tag level
	 *  islink[] : tag is a link
	 *     tag[] : tag name
	 *    text[] : tag value
	 *
	 * @param string $tag			fact record to edit (eg 2 DATE xxxxx)
	 * @param string $upperlevel	optional upper level tag (eg BIRT)
	 */
	public function AddSimpleTag($tag, $upperlevel="", $tab="1", $switch=false, $separator=true) {
		global $GM_IMAGES, $TEMPLE_CODES, $STATUS_CODES;
		global $assorela, $tags, $emptyfacts, $TEXT_DIRECTION, $confighelpfile;
		global $NPFX_accept, $SPFX_accept, $NSFX_accept, $FILE_FORM_accept, $upload_count, $separatorfacts, $canhavey_facts;
		static $tabkey;
		if (!isset($tabkey)) $tabkey = $tab;
		
		@list($level, $fact, $value) = explode(" ", $tag);
	// 	print "adding ".$tag." at level ".$level." upperlevel ".$upperlevel.", fact is ". $fact." value is ".nl2br($value)."<br />";
	
		$largetextfacts = array("TEXT","PUBL","NOTE");
		$subnamefacts = array("NPFX", "GIVN", "NICK", "SPFX", "SURN", "NSFX");
	
	
		// element name : used to POST data
		if ($upperlevel) $element_name=$upperlevel."_".$fact; // ex: BIRT_DATE | DEAT_DATE | ...
		else if ($level==0) $element_name=$fact; // ex: OCCU
		else $element_name="text[]";
	
		// element id : used by javascript functions
		if ($level==0) $element_id=$fact; // ex: NPFX | GIVN ...
		else $element_id=$fact.rand(0,99999); // ex: SOUR56402
//		else $element_id=$fact.floor(microtime()*1000000); // ex: SOUR56402
		if ($upperlevel) $element_id=$upperlevel."_".$fact; // ex: BIRT_DATE | DEAT_DATE ...

		// field value
		$islink = (substr($value,0,1)=="@" && substr($value,0,2)!="@#");
		if ($islink) {
			$value=trim(trim($value), "@");
		}
		else $value = rtrim(substr($tag, strlen($fact)+3));
		
		// rows & cols
		$rows=1;
		$cols=60;
		if ($islink) $cols=10;
		else if ($fact=="NCHI" || $fact == "NMR") $cols=3;
		else if ($fact=="FORM") $cols=5;
		else if ($fact=="DATE" or $fact=="TIME" or $fact=="TYPE" or $fact=="PEDI") $cols=20;
		else if ($fact=="LATI" or $fact=="LONG") $cols=12;
		else if (in_array($fact, $subnamefacts)) $cols=25;
		else if (in_array($fact, $largetextfacts)) { $rows=10; $cols=70; }
		else if ($fact=="ADDR") $rows=5;
		else if ($fact=="REPO") $cols = strlen(GedcomConfig::$REPO_ID_PREFIX) + 4;
	
		
		// label
		if (in_array($fact, $separatorfacts) && $level <= 2) self::AddTagSeparator($fact, $separator);
		$style="";
		print "<tr id=\"".$element_id."_tr\" ";
		if (in_array($fact, $subnamefacts) || !(!in_array($fact, $emptyfacts) || in_array($fact, $canhavey_facts))) print " style=\"display:none;\""; // hide subname facts
		print " >\n";
		print "<td class=\"NavBlockLabel\">";
		
		// NOTE: Tag level
		if ($level>0) {
			print "<input type=\"hidden\" name=\"glevels[]\" value=\"".$level."\" />\n";
			print "<input type=\"hidden\" name=\"islink[]\" value=\"".($islink)."\" />\n";
			print "<input type=\"hidden\" name=\"tag[]\" value=\"".$fact."\" />\n";
		}
		
		// NOTE: Help link
		if (!in_array($fact, $emptyfacts) || in_array($fact, $canhavey_facts)) {
			print "<div class=\"HelpIconContainer\">";
			if ($fact=="DATE") PrintHelpLink("def_gedcom_date_help", "qm", "date");
			else if ($fact=="RESN") PrintHelpLink($fact."_help", "qm");
			else PrintHelpLink("edit_".$fact."_help", "qm");
			print "</div>";
			if (defined("GM_LANG_".$fact)) print constant("GM_LANG_".$fact);
			else if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
			else print $fact;
			print "\n";
		}
		
		if(in_array($fact, $emptyfacts) && !in_array($fact, $canhavey_facts)) print "<input type=\"hidden\" name=\"text[]\" value=\"\" />\n"; 
		print "\n</td>";
		
		// NOTE: value
		print "<td class=\"NavBlockField\">\n";
		// NOTE: Retrieve linked NOTE
		// we must disable editing for this field if the note already has changes.
		$disable_edit = false;
		if (in_array($fact, $canhavey_facts)&& (empty($value) || $value=="y" || $value=="Y")) {
			$value = strtoupper($value);
			//-- don't default anything to Y when adding events through people
			//-- default to Y when specifically adding one of these events
			if ($level==1) $value="Y"; // default YES
			print "<input type=\"hidden\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".$value."\" />";
			if ($level<=1) {
				print "<input type=\"checkbox\" tabindex=\"".$tabkey."\"";
				$tabkey++;
				if ($value=="Y") print " checked=\"checked\"";
				print " onclick=\"if (this.checked) ".$element_id.".value='Y'; else ".$element_id.".value=''; \" />";
				print GM_LANG_yes;
			}
		}
		// Added
		else if ($fact=="TEMP") {
			print "<select tabindex=\"".$tabkey."\" name=\"".$element_name."\" >\n";
			print "<option value=''>".GM_LANG_no_temple."</option>\n";
			foreach($TEMPLE_CODES as $code=>$temple) {
				print "<option value=\"$code\"";
				if ($code==$value) print " selected=\"selected\"";
				print ">$temple</option>\n";
			}
			print "</select>\n";
		}
		else if ($fact=="STAT") {
			print "<select tabindex=\"".$tabkey."\" name=\"".$element_name."\" >\n";
			print "<option value=''>No special status</option>\n";
			foreach($STATUS_CODES as $code=>$status) {
				print "<option value=\"$code\"";
				if ($code==$value) print " selected=\"selected\"";
				print ">$status</option>\n";
			}
			print "</select>\n";
		}
		else if ($fact=="RELA") {
			$text=strtolower($value);
			// add current relationship if not found in default list
			if (!array_key_exists($text, $assorela)) $assorela[$text]=$text;
			print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" >\n";
			foreach ($assorela as $key=>$value) {
				print "<option value=\"". $key . "\"";
				if ($key==$text) print " selected=\"selected\"";
				print ">" . $assorela["$key"] . "</option>\n";
			}
			print "</select>\n";
		}
		else if ($fact=="RESN") {
			print "<table><tr valign=\"top\">\n";
			$idsuff = "";
			foreach (array("none", "locked", "privacy", "confidential") as $resn_index => $resn_val) {
				if ($resn_val=="none") $resnv=""; else $resnv=$resn_val;
				print "<td><input tabindex=\"".$tabkey."\" type=\"radio\" id=\"".$element_id.$idsuff."\" name=\"".$element_name."\" value=\"".$resnv."\"";
				if ($value==$resnv) print " checked=\"checked\"";
				print " /><small>".constant("GM_LANG_".$resn_val)."</small>";
				print "</td>\n";
				if (empty($idsuff)) $idsuff = 0;
				$idsuff++;
			}
			print "</tr></table>\n";
		}
		else if ($fact=="_PRIM" or $fact=="_THUM" or $fact=="_SSHOW" or $fact=="_SCBK") {
			print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" >\n";
			print "<option value=\"\"></option>\n";
			print "<option value=\"Y\"";
			if ($value=="Y") print " selected=\"selected\"";
			print ">".GM_LANG_yes."</option>\n";
			print "<option value=\"N\"";
			if ($value=="N") print " selected=\"selected\"";
			print ">".GM_LANG_no."</option>\n";
			print "</select>\n";
		}
		else if ($fact == "SEX") {
			self::PrintGender($element_id, $element_name, $value, $tabkey);
		}
		else if ($fact == "PEDI") {
			self::PrintPedi($element_id, $element_name, $value, $switch);
		}
		else if ($fact == "TYPE" && $level == '3') {?>
			<select name="text[]">
			<option selected="selected" value=""> <?php print GM_LANG_choose; ?> </option>
			<option <?php if ($value == GM_LANG_type_audio) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_audio; ?> </option>
			<option <?php if ($value == GM_LANG_type_book) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_book; ?> </option>
			<option <?php if ($value == GM_LANG_type_card) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_card; ?> </option>
			<option <?php if ($value == GM_LANG_type_electronic) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_electronic; ?> </option>
			<option <?php if ($value == GM_LANG_type_fiche) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_fiche; ?> </option>
			<option <?php if ($value == GM_LANG_type_film) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_film; ?> </option>
			<option <?php if ($value == GM_LANG_type_magazine) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_magazine; ?> </option>
			<option <?php if ($value == GM_LANG_type_manuscript) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_manuscript; ?> </option>
			<option <?php if ($value == GM_LANG_type_map) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_map; ?> </option>
			<option <?php if ($value == GM_LANG_type_newspaper) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_newspaper; ?> </option>
			<option <?php if ($value == GM_LANG_type_photo) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_photo; ?> </option>
			<option <?php if ($value == GM_LANG_type_tombstone) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_tombstone; ?> </option>
			<option <?php if ($value == GM_LANG_type_video) print "selected=\"selected\""; ?>> <?php print GM_LANG_type_video; ?> </option>
			</select>
			<?php
		}
		else if ($fact == "QUAY") {
			print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\">\n";
			print "<option value=\"\"";
				if ($value=="") print " selected=\"selected\"";
			print "></option>\n";
			for ($number = 0; $number < 4; $number++) {
				print "<option value=\"".$number."\"";
				if ($value==$number) print " selected=\"selected\"";
				print ">".$number."</option>\n";
			}
			print "</select>\n";
		}
		else {
			// textarea
			if ($rows>1 && !($fact == "NOTE" && $islink)) {
				if ($disable_edit) {
					print "<textarea tabindex=\"".$tabkey."\" rows=\"".$rows."\" cols=\"".$cols."\" disabled=\"disabled\">".$value."</textarea>\n";
					print "<input type=\"hidden\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".$value."\" />";
				}
				else {
					// The \n before the value is a workaround for stupid browsers not handling the starting newline correctly.
					print "<textarea tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" rows=\"".$rows."\" cols=\"".$cols."\">"."\n".$value."</textarea>\n";
				}
			}
			// text
			else {
				if (!in_array($fact, $emptyfacts)) {
					print "<input tabindex=\"".$tabkey."\" type=\"text\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".htmlspecialchars($value)."\" size=\"".$cols."\" dir=\"ltr\"";
					if ($fact=="NPFX") print " onkeyup=\"wactjavascript_autoComplete(npfx_accept,this,event);\" ";
					if (in_array($fact, $subnamefacts)) print " onchange=\"updatewholename();\"";
					if ($fact=="DATE") print " onblur=\"valid_date(this);if (this.value.length != 0) {sndReq('".$element_id."_date', 'getchangeddate', true, 'date', this.value, '', '');} else {document.getElementById('".$element_id."_date').innerHTML='';}\"";
					if ($fact=="EMAIL") print " onblur=\"if (this.value.length != 0) {sndReq('".$element_id."_email', 'checkemail', true, 'email', this.value, '', '')};\"";
					if ($fact == "SOUR") print " onblur=\"if (this.value.length != 0) {sndReq('".$element_id."_src', 'getsourcedescriptor', true, 'sid', this.value, '', '')};\"";
					if ($fact == "REPO") print " onblur=\"if (this.value.length != 0) {sndReq('".$element_id."_repo', 'getrepodescriptor', true, 'rid', this.value, '', '')};\"";
					if ($fact == "ASSO") print " onblur=\"if (this.value.length != 0) {sndReq('".$element_id."_asso', 'getpersonname', true, 'pid', this.value, '', '')};\"";
					if ($fact == "NOTE") print " onblur=\"if (this.value.length != 0) {sndReq('".$element_id."_gnote', 'getnotedescriptor', true, 'oid', this.value, '', '')};\"";
					if ($fact == "OBJE") print " onblur=\"if (this.value.length != 0) {sndReq('".$element_id."_obj', 'getmediadescriptor', true, 'mid', this.value, '', '')};\"";
					print " />\n";
				}
			}
			// split PLAC
			if ($fact=="PLAC") {
				print "<div id=\"".$element_id."_pop\" style=\"display: inline;\">\n";
				LinkFunctions::PrintSpecialCharLink($element_id);
				LinkFunctions::PrintFindPlaceLink($element_id);
				print "</div>\n";
				if (GedcomConfig::$SPLIT_PLACES) self::PrintPlaceSubfields($element_id);
			}
			else if ($cols>20 && $fact!="NPFX" && !in_array($fact, $emptyfacts)) {
				if (in_array($fact, array("GIVN", "SURN", "NPSX"))) LinkFunctions::PrintSpecialCharLink($element_id);
				else LinkFunctions::PrintSpecialCharLink($element_id);
			}
		}
		// MARRiage TYPE : hide text field and show a selection list
		if ($fact=="TYPE" and isset($tags[0]) and $tags[0]=="MARR") {
			print "<script type='text/javascript'><!--\n";
			print "document.getElementById('".$element_id."').style.display='none'";
			print "\n//-->\n</script>";
			print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."_sel\" onchange=\"document.getElementById('".$element_id."').value=this.value;\" >\n";
			foreach (array("Unknown", "Civil", "Religious", "Partners") as $indexval => $key) {
				if ($key=="Unknown") print "<option value=\"\"";
				else print "<option value=\"".$key."\"";
				$a=strtolower($key);
				$b=strtolower($value);
				if (@strpos($a, $b)!==false or @strpos($b, $a)!==false) print " selected=\"selected\"";
				print ">".constant("GM_FACT_MARR_".strtoupper($key))."</option>\n";
			}
			print "</select>";
		}
	
		// popup links
		if ($fact=="DATE") self::PrintCalendarPopup($element_id);
		if ($fact=="FAMC") LinkFunctions::PrintFindFamilyLink($element_id);
		if ($fact=="FAMS") LinkFunctions::PrintFindFamilyLink($element_id);
		if ($fact=="ASSO") LinkFunctions::PrintFindIndiLink($element_id,"");
		if ($fact=="FILE") LinkFunctions::PrintFindMediaFileLink($element_id);
		if ($fact=="OBJE" && $islink) {
			LinkFunctions::PrintFindMediaLink($element_id);
			if (MediaFS::DirIsWritable(GedcomConfig::$MEDIA_DIRECTORY)) LinkFunctions::PrintAddNewObjectLink($element_id);
		}
		if ($fact=="SOUR") {
			LinkFunctions::PrintFindSourceLink($element_id);
			LinkFunctions::PrintAddNewSourceLink($element_id);
		}
		if ($fact=="REPO") {
			LinkFunctions::PrintFindRepositoryLink($element_id);
			LinkFunctions::PrintAddNewRepositoryLink($element_id);
		}
		if ($fact=="NOTE" && $islink) {
			LinkFunctions::PrintFindNoteLink($element_id);
			LinkFunctions::PrintAddNewGNoteLink($element_id);
		}
	
		// current value
		if ($fact=="DATE") print " <span id=\"".$element_id."_date\">".GetChangedDate($value)."</span>";
		if ($fact=="REPO") {
			print " <span id=\"".$element_id."_repo\">";
			if ($value) {
				$repo =& Repository::GetInstance($value);
				print $repo->name.$repo->addxref;
			}
			else {
				if (isset($_SESSION["last_used"]["REPO"])) {
					$gedid = SplitKey($_SESSION["last_used"]["REPO"], "gedid");
					if ($gedid == GedcomConfig::$GEDCOMID) {
						$id = SplitKey($_SESSION["last_used"]["REPO"], "id");
						$repo =& Repository::GetInstance($id);
						if (CheckExists($id, "REPO")) print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_repo', 'getrepodescriptor', true, 'rid', '".$id."', '', ''); return false;\">".GM_LANG_click_for." ".$repo->name.$repo->addxref."</a>";
					}
				}
			}
			print "</span>";
			print "<br />";
		}
		if ($fact=="EMAIL") print " <span id=\"".$element_id."_email\"></span>";
		if ($fact=="ASSO") {
			print " <span id=\"".$element_id."_asso\">";
			if ($value) {
				$person =& Person::GetInstance($value);
				print $person->name.$person->addxref;
			}
			print "</span>";
		}
		if ($fact=="NOTE" && $islink) {
			print " <span id=\"".$element_id."_gnote\">";
			if ($value) {
				print "</span>";
				?>
				<script type="text/javascript">
				<!--
				sndReq('<?php print $element_id."_gnote";?>', 'getnotedescriptor', true, 'oid', '<?php print $value;?>', '', '');
				//-->
				</script>
			<?php
			}
			else {
				if (isset($_SESSION["last_used"]["NOTE"])) {
					$gedid = SplitKey($_SESSION["last_used"]["NOTE"], "gedid");
					if ($gedid == GedcomConfig::$GEDCOMID) {
						$id = SplitKey($_SESSION["last_used"]["NOTE"], "id");
						if (CheckExists($id, "NOTE")) {
							print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_gnote', 'getnotedescriptor', true, 'oid', '".$id."', '', ''); return false;\">".GM_LANG_click_for." ";
							print "<span id=\"".$element_id."_gnote2\"></span></a>";
							?>
							<script type="text/javascript">
							<!--
							sndReq('<?php print $element_id."_gnote2";?>', 'getnotedescriptor', true, 'oid', '<?php print $id;?>', '', '');
							//-->
							</script>
							<?php
						}
					}
				}
				print "</span>";
			}
		}
		if ($fact=="SOUR") {
			print " <span id=\"".$element_id."_src\">";
			if ($value) {
				$source =& Source::GetInstance($value);
				print $source->name.$source->addxref;
			}
			else {
				if (isset($_SESSION["last_used"]["SOUR"])) {
					$gedid = SplitKey($_SESSION["last_used"]["SOUR"], "gedid");
					if ($gedid == GedcomConfig::$GEDCOMID) {
						$id = SplitKey($_SESSION["last_used"]["SOUR"], "id");
						$obj = ConstructObject($id, "SOUR");
						if (is_object($obj)) print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_src', 'getsourcedescriptor', false, 'sid', '".$id."', '', ''); return false;\">".GM_LANG_click_for." ".$obj->name.$obj->addxref."</a>";
					}
				}
			}
			print "</span>";
		}
	
		if ($fact=="OBJE") {
			print " <span id=\"".$element_id."_obj\">";
			if ($value) {
				$media =& MediaItem::GetInstance($value);
				print $media->name.$media->addxref;
			}
			else {
				if (isset($_SESSION["last_used"]["OBJE"])) {
					$gedid = SplitKey($_SESSION["last_used"]["OBJE"], "gedid");
					if ($gedid == GedcomConfig::$GEDCOMID) {
						$id = SplitKey($_SESSION["last_used"]["OBJE"], "id");
						$obj = ConstructObject($id, "OBJE");
						if (is_object($obj)) print "<a href=\"javascript\" onclick=\"document.getElementById('".$element_id."').value='".$id."'; sndReq('".$element_id."_obj', 'getmediadescriptor', true, 'mid', '".$id."', '', ''); return false;\">".GM_LANG_click_for." ".$obj->name.$obj->addxref."</a>";
					}
				}
			}
			print "</span>";
		}
		
		// pastable values
		if ($fact=="NPFX") {
			$text = GM_LANG_autocomplete;
			if (isset($GM_IMAGES["autocomplete"]["button"])) $Link = "<img id=\"".$element_id."_spec\" name=\"".$element_id."_spec\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["autocomplete"]["button"]."\"  alt=\"".$text."\"  title=\"".$text."\" border=\"0\" align=\"middle\" />";
			else $Link = $text;
			print "&nbsp;".$Link;
		}
		if ($fact=="SPFX") LinkFunctions::PrintAutoPasteLink($element_id, $SPFX_accept);
		if ($fact=="NSFX") LinkFunctions::PrintAutoPasteLink($element_id, $NSFX_accept);
		if ($fact=="FORM") LinkFunctions::PrintAutoPasteLink($element_id, $FILE_FORM_accept, false, false);
	
		// split NAME
		if ($fact=="NAME") {
			print "&nbsp;<a href=\"javascript: ".GM_LANG_show_details."\" onclick=\"togglename(); return false;\"><img id=\"".$element_id."_plus\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /></a>\n";
			print "<a href=\"javascript: ".GM_LANG_show_details."\" onclick=\"togglename(); return false;\"><img style=\"display:none;\" id=\"".$element_id."_minus\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /></a>\n";
		}
		
		print "</td></tr>\n";
		$tabkey++;
		return $element_id;
	}
	
	/**
	 * prints collapsable fields to add ASSO/RELA, SOUR, OBJE ...
	 *
	 * @param string $tag		Gedcom tag name
	 */
	public function PrintAddLayer($tag, $level=2, $addfact=false) {
		global $GM_IMAGES, $TEXT_DIRECTION;
		static $header_printed;
		
		if (!isset($header_printed)) {
			$header_printed = true;
			print "<tr><td colspan=\"2\" class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
			print "<tr><td class=\"NavBlockColumnHeader EditTableColumnHeader\" colspan=\"2\">".GM_LANG_add_info_links."</td></tr>";
		}
	
		print "<tr><td class=\"NavBlockLabel\" colspan=\"2\">";
		if ($tag=="SOUR") {
			//-- Add new source to fact
			print "<a href=\"#\" onclick=\"expand_layer('newsource'); if(document.getElementById('newsource').style.display == 'block') document.getElementById(addsourcefocus).focus(); return false;\"><img id=\"newsource_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".GM_LANG_add_source."</a>";
			PrintHelpLink("edit_add_SOUR_help", "qm");
			print "</td></tr></table>";
			print "<div id=\"newsource\" style=\"display: none;\">\n";
			print "<table class=\"NavBlockTable EditTable\">\n";
			// 2 SOUR
			$sour_focus_element = self::AddSimpleTag("$level SOUR @", "", 1, false, false);
			// 3 PAGE
			self::AddSimpleTag(($level+1)." PAGE");
			// 3 DATA
			self::AddSimpleTag(($level+1)." DATA");
			// 4 DATE
			self::AddSimpleTag(($level+2)." DATE");
			// 4 TEXT
			self::AddSimpleTag(($level+2)." TEXT");
			if ($addfact) {
				print "<tr><td colspan=\"2\" class=\"NavBlockLabel\"><input type=\"radio\" name=\"addsource\" value=\"0\" />".GM_LANG_add_source_citation_to_person."<br />\n";
				print "<input type=\"radio\" name=\"addsource\" value=\"1\" />".GM_LANG_add_source_reference_to_fact."<br />\n";
				print "<input type=\"radio\" name=\"addsource\" value=\"2\" checked=\"checked\" />".GM_LANG_add_source_citation_to_fact."<br />\n";
				print "</td></tr>";
			}
			print "</table></div>";
			?>
			<script type="text/javascript">
			<!--
			var addsourcefocus = <?php print "'".$sour_focus_element."'"; ?>;
			//-->
			</script>
			<?php
		}
		if ($tag=="ASSO") {
			//-- Add a new ASSOciate
			print "<a href=\"#\" onclick=\"expand_layer('newasso'); if(document.getElementById('newasso').style.display == 'block') document.getElementById(addassofocus).focus(); return false;\"><img id=\"newasso_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".GM_LANG_add_asso."</a>";
			PrintHelpLink("edit_add_ASSO_help", "qm");
			print "</td></tr></table>";
			print "<div id=\"newasso\" style=\"display: none;\">\n";
			print "<table class=\"NavBlockTable EditTable\">\n";
			// 2 ASSO
			$asso_focus_element = self::AddSimpleTag(($level)." ASSO @", "", 1, false, false);
			// 3 RELA
			self::AddSimpleTag(($level+1)." RELA");
			// 3 NOTE
			self::AddSimpleTag(($level+1)." NOTE");
			print "</table></div>";
			?>
			<script type="text/javascript">
			<!--
			var addassofocus = <?php print "'".$asso_focus_element."'"; ?>;
			//-->
			</script>
			<?php
		}
		if ($tag=="NOTE") {
			//-- Add new note to fact
			print "<a href=\"#\" onclick=\"expand_layer('newnote'); if(document.getElementById('newnote').style.display == 'block') document.getElementById(addnotefocus).focus(); return false;\"><img id=\"newnote_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".GM_LANG_add_note."</a>";
			PrintHelpLink("edit_add_NOTE_help", "qm");
			print "</td></tr></table>";
			print "<div id=\"newnote\" style=\"display: none;\">\n";
			print "<table class=\"NavBlockTable EditTable\">\n";
			// 2 NOTE
			$note_focus_element = self::AddSimpleTag(($level)." NOTE", "", 1, false, false);
			print "</table></div>";
			?>
			<script type="text/javascript">
			<!--
			var addnotefocus = <?php print "'".$note_focus_element."'"; ?>;
			//-->
			</script>
			<?php
		}
		if ($tag=="GNOTE") {
			//-- Add new general note to fact
			print "<a href=\"#\" onclick=\"expand_layer('newgnote'); if(document.getElementById('newgnote').style.display == 'block') document.getElementById(addgnotefocus).focus(); return false;\"><img id=\"newgnote_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".GM_LANG_add_gnote."</a>";
			PrintHelpLink("edit_add_NOTE_help", "qm");
			print "</td></tr></table>";
			print "<div id=\"newgnote\" style=\"display: none;\">\n";
			print "<table class=\"FactsTable\">\n";
			// 2 NOTE
			$gnote_focus_element = self::AddSimpleTag("$level NOTE @", "", 1, false, false);
			print "</table></div>";
			?>
			<script type="text/javascript">
			<!--
			var addgnotefocus = <?php print "'".$gnote_focus_element."'"; ?>;
			//-->
			</script>
			<?php
		}
		if ($tag=="OBJE") {
			//-- Add new obje to fact
			print "<a href=\"#\" onclick=\"expand_layer('newobje'); if(document.getElementById('newobje').style.display == 'block') document.getElementById(addobjefocus).focus(); return false;\"><img id=\"newobje_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".GM_LANG_add_obje."</a>";
			PrintHelpLink("add_media_help", "qm");
			print "</td></tr></table>";
			print "<div id=\"newobje\" style=\"display: none;\">\n";
			print "<table class=\"NavBlockTable EditTable\">\n";
			// 2 OBJE <=== as link
			$obje_focus_element = self::AddSimpleTag(($level)." OBJE @", "", 1, false, false);
			// 2 OBJE <=== as embedded new object
	//		self::AddSimpleTag(($level)." OBJE");
			// 3 FORM
	//		self::AddSimpleTag(($level+1)." FORM");
			// 3 FILE
	//		self::AddSimpleTag(($level+1)." FILE");
			// 3 TITL
	//		self::AddSimpleTag(($level+1)." TITL");
			if ($level==1) {
				// 3 _PRIM
				self::AddSimpleTag(($level+1)." _PRIM");
				// 3 _THUM
				self::AddSimpleTag(($level+1)." _THUM");
			}
			print "</table></div>";
			?>
			<script type="text/javascript">
			<!--
			var addobjefocus = <?php print "'".$obje_focus_element."'"; ?>;
			//-->
			</script>
			<?php
		}
		print "<table class=\"NavBlockTable EditTable\">\n";
	}
	
	/**
	 * Add new gedcom lines from interface update arrays
	 * @param string $newged	the new gedcom record to add the lines to
	 * @return string	The updated gedcom record
	 */
	public function HandleUpdates($newged) {
		global $glevels, $islink, $tag, $uploaded_files, $text, $NOTE, $change_id, $change_type, $success, $can_auto_accept, $link_error;
		
		$link_error = false;
		
		foreach ($_POST as $key => $line) {
			$$key = $line;
		}
		
		// NOTE: Cleanup text fields
		foreach ($text as $key => $line) {
			$text[$key] = trim($line);
		}
		for($j=0; $j<count($glevels); $j++) {
			//print "value j: ".$j."<br />";
			// NOTE: update external note records first
			
			// print "value j:".$j."glevels: ".$glevels[$j]." tag: ".$tag[$j]." text: ".$text[$j]." strl: ".strlen($text[$j])." islink: ".$islink[$j]."<br />";
			//-- for facts with empty values they must have sub records
			//-- this section checks if they have subrecords
			// Compare with "" as 'empty' would also include '0'.
			if ($text[$j] == "") {
				$k=$j+1;
				$pass=false;
				while(($k<count($glevels))&&($glevels[$k]>$glevels[$j])) {
					if (!empty($text[$k])) {
						if (($tag[$j]!="OBJE")||($tag[$k]=="FILE")) {
							$pass=true;
							break;
						}
					}
					if (($tag[$k]=="FILE")&&(count($uploaded_files)>0)) {
						$filename = array_shift($uploaded_files);
						if (!empty($filename)) {
							$text[$k] = $filename;
							$pass=true;
							break;
						}
					}
					$k++;
				}
			}
			else $pass = true;
			
			//print "pass: ".$pass."<br />";	
			// NOTE: if the value is not empty then write the line to the gedcom record
			// The value 0 is considered as empty, se we must check this separately (1 NCHI 0)
			if ($pass == true) {			
				// Only add a link if an ID is given
				$skip = false;
				//print "check for link ".$islink[$j];
				if ($islink[$j]) {
					$passlink = false;
					//print "link found<br />";
					if (!empty($text[$j]) || $text[$j] == "0") {
						//print "ok link found".$text[$j];
						$object = ConstructObject($text[$j]);
						if (!is_object($object) || $object->isempty) $passlink = false;
						else $passlink = true;
						if (!$passlink) {
							print "<span class=\"Error\">".GM_LANG_link_not_added.": ".$text[$j]."</span><br />";
							$link_error = true;
						}
						else {
							$text[$j] = "@".strtoupper($text[$j])."@";
							$newline = $glevels[$j]." ".$tag[$j];
						}
					}
					if (!$passlink) {
						//print "empty link found<br />";
						// We found an empty link. Also the subsequent subrecords must be skipped.
						$newline = "";
						$lev = $glevels[$j];
						for ($a=$j+1; $a<count($glevels); $a++) {
							if ($glevels[$a] == $lev) {
								$j = $a - 1;
								$skip = true;
								break;
							}
						}
						if (!$skip) {
							$j = count($glevels) - 1;
							$skip = true;
						}
					}
				}
				if (!$skip) {
				
					// NOTE: check and translate the incoming dates
					if ($tag[$j] == "DATE" && !empty($text[$j])) {
						$text[$j] = self::CheckInputDate($text[$j]);
					}
				
					if (!empty($text[$j]) || $text[$j] == "0") {
						$newline = $glevels[$j]." ".$tag[$j];
						if (($newline == "2 SOUR" || $newline == "1 SOUR") && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
						else if (($newline == "2 OBJE" || $newline == "1 OBJE") && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
						else if ($newline == "1 REPO" && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
						else if ($newline == "2 ASSO" && !stristr($text[$j], "@")) $newline .= " @".strtoupper($text[$j])."@";
						else if (!empty($newline)) $newline .= " ".$text[$j]; // This is for note text in the link record
					}
					else {
						// Handle empty tags because lower level tags may follow
						if (isset($glevels[$j+1]) && $glevels[$j+1] > $glevels[$j]) {
							$newline = $glevels[$j]." ".$tag[$j];
							// print "1: ".$newline."<br />";
						}
					}
							
					// NOTE: Check if the new record already contains this line, if so, empty the new record
					if (isset($newline) && trim($newged) == trim($newline)) $newged = "";
					
					// NOTE: convert returns to CONT lines and break up lines longer than 255 chars
					$newlines = preg_split("/\r?\n/", $newline);
					for($k=0; $k<count($newlines); $k++) {
						if ($k>0) $newlines[$k] = ($glevels[$j]+1)." CONT ".$newlines[$k];
						if (strlen($newlines[$k])>255) {
							while(strlen($newlines[$k])>255) {
								for ($ch = 255;1;$ch--) {
									if (substr($newlines[$k],$ch-1,1) != " ") break;
								}
								$str = substr($newlines[$k], 0, $ch);
								$newged .= $str."\r\n";
								$newlines[$k] = substr($newlines[$k], $ch);
								$newlines[$k] = ($glevels[$j]+1)." CONC ".$newlines[$k];
							}
							$newged .= trim($newlines[$k])."\r\n";
						}
						else {
							$newged .= trim($newlines[$k])."\r\n";
						}
					}
					// print "2: ".$newline."<br />";
					// print "2 newged: ".$newged."<br />";
				}
			}
		}
		$newged = self::CleanupTagsY($newged);
		return $newged;
	}
	
	/**
	 * check the given date that was input by a user and convert it
	 * to proper gedcom date if possible
	 * @author Genmod Development Team
	 * @param string $datestr	the date input by the user
	 * @return string	the converted date string
	 */
	public function CheckInputDate($datestr) {
		$date = ParseDate($datestr);
	
	//	if (count($date)==1 && empty($date[0]['ext']) && !empty($date[0]['month'])) {
	//		$datestr = $date[0]['day']." ".$date[0]['month']." ".$date[0]['year'];
	//	}
		$str = "";
		if (isset($date[0]['ext']) && !empty($date[0]['ext'])) $str .= $date[0]['ext']." ";
		if (isset($date[0]['day']) && !empty($date[0]['day'])) $str .= $date[0]['day']." ";
		if (isset($date[0]['month']) && !empty($date[0]['month'])) $str .= $date[0]['month']." ";
		if (isset($date[0]['year']) && !empty($date[0]['year'])) $str .= $date[0]['year']." ";
		if (isset($date[1])) {
			if (isset($date[1]['ext']) && !empty($date[1]['ext'])) $str .= $date[1]['ext']." ";
			if (isset($date[1]['day']) && !empty($date[1]['day'])) $str .= $date[1]['day']." ";
			if (isset($date[1]['month']) && !empty($date[1]['month'])) $str .= $date[1]['month']." ";
			if (isset($date[1]['year']) && !empty($date[1]['year'])) $str .= $date[1]['year']." ";
		}
		$str = trim ($str);
		if (!empty($str)) return $str;
		else return $datestr;
	}
	
	public function PrintPedi($id, $name="", $value="", $showbio=true) {
		global $align, $tabkey;
	
		if (empty($name)) $name = $id;
		
		print "<select tabindex=\"".$tabkey."\" id=\"".$id."\" name=\"".$name."\">\n";
			
		if ($showbio) {
			print "<option value=\"birth\"";
			if ($value=="birth" || $value=="") print " selected=\"selected\"";
			print ">".GM_LANG_biological."</option>\n";
		}
			
		print "<option value=\"adopted\"";
		if ($value=="adopted") print " selected=\"selected\"";
		print ">".GM_LANG_adopted."</option>\n";
	
		print "<option value=\"foster\"";
		if ($value=="foster") print " selected=\"selected\"";
		print ">".GM_LANG_foster."</option>\n";
			
		print "<option value=\"sealing\"";
		if ($value=="sealing") print " selected=\"selected\"";
		print ">".GM_LANG_sealing."</option>\n";
			
		print "</select>\n";
	}
	
	public function PrintGender($element_id, $element_name, $value, $tabkey) {
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\">\n<option value=\"M\"";
		if ($value=="M") print " selected=\"selected\"";
		print ">".GM_LANG_male."</option>\n<option value=\"F\"";
		if ($value=="F") print " selected=\"selected\"";
		print ">".GM_LANG_female."</option>\n<option value=\"U\"";
		if ($value=="U" || empty($value)) print " selected=\"selected\"";
		print ">".GM_LANG_unknown."</option>\n</select>\n";
	}
	
	public function SubmitterRecord($level, $gedrec) {
		
		self::AddTagSeparator("SUBM");
		if ($gedrec == "") {
			self::AddSimpleTag(($level+1)." NAME");
			self::AddSimpleTag(($level+1)." ADDR");
			self::AddSimpleTag(($level+2)." CTRY");
			self::AddSimpleTag(($level+1)." PHON");
			self::AddSimpleTag(($level+1)." EMAIL");
			self::AddSimpleTag(($level+1)." NOTE");
		}
		else {
			$gedtype = GetSubRecord(($level+1), "NAME", $gedrec);
			if (empty($gedtype)) $gedtype = "NAME";
			self::AddSimpleTag(($level+1)." ".$gedtype);
			
			$gedtype = GetSubRecord(($level+1), "ADDR", $gedrec);
			if (empty($gedtype)) $gedtype = "ADDR";
			
			$gedlines = explode("\r\n", $gedtype);
			$fields = preg_split("/\s/", $gedlines[0]);
			
			$i = 0;
			$text = "";
			// NOTE: This retrieves the data for each fact
			for($j=1; $j<count($fields); $j++) {
				if ($j>1) $text .= " ";
				$text .= $fields[$j];
			}
			// NOTE: This retrieves the continuance of the address
			while(($i+1<count($gedlines))&&(preg_match("/".($level+2)." (CON[CT])\s?(.*)/", $gedlines[$i+1], $cmatch)>0)) {
				$iscont=true;
				if ($cmatch[1]=="CONT") $text.="\n";
				else if (GedcomConfig::$WORD_WRAPPED_NOTES) $text .= " ";
				$text .= $cmatch[2];
				$i++;
			}
			self::AddSimpleTag(($level+1)." ADDR ".$text);
			
			$gedtype = GetSubRecord(($level+2), "CTRY", $gedtype);
			if (empty($gedtype)) $gedtype = "CTRY";
			self::AddSimpleTag(($level+2)." ".$gedtype);
			
			$gedtype = GetSubRecord(($level+1), "PHON", $gedrec);
			if (empty($gedtype)) $gedtype = "PHON";
			self::AddSimpleTag(($level+1)." ".$gedtype);
			
			$gedtype = GetSubRecord(($level+1), "EMAIL", $gedrec);
			if (empty($gedtype)) $gedtype = "EMAIL";
			self::AddSimpleTag(($level+1)." ".$gedtype);
	
			$gednote = GetGedcomValue("NOTE", ($level+1), $gedrec);
			if (empty($gednote)) $gednote = "";
			self::AddSimpleTag(($level+1)." NOTE ".$gednote);
		}
	}
	
	public function ShowMediaForm($pid, $action="newentry", $change_type="add_media") {
		global $TEXT_DIRECTION;
		global $gm_user;
		
		$facts_add = explode(",", GedcomConfig::$MEDIA_FACTS_ADD);
		$facts_unique = explode(",", GedcomConfig::$MEDIA_FACTS_UNIQUE);
		
		// NOTE: add a table and form to easily add new values to the table
		print "<form method=\"post\" name=\"newmedia\" action=\"addmedia.php\" enctype=\"multipart/form-data\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
		print "<input type=\"hidden\" name=\"paste\" value=\"1\" />\n";
		print "<input type=\"hidden\" name=\"change_type\" value=\"$change_type\" />\n";
		if (isset($pid)) print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
		print "<table class=\"NavBlockTable EditTable\">\n";
		self::AddTagSeparator($change_type);
		if ($pid == "") {
			print "<tr><td class=\"NavBlockLabel\">".GM_LANG_add_fav_enter_id."</td>";
			print "<td class=\"NavBlockField\"><input type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
			LinkFunctions::PrintFindIndiLink("gid","");
			LinkFunctions::PrintFindFamilyLink("gid");
			LinkFunctions::PrintFindSourceLink("gid");
			print "</td></tr>";
		}
		if (IdType($pid) == "OBJE") {
			$media =& MediaItem::GetInstance($pid);
			if ($media->ischanged) $gedrec = $media->changedgedrec;
			else $gedrec = $media->gedrec;
		}
		else {
			$gedrec = "";
		}
		// Store the gedcom rec to compare with the new values
		print "<input type=\"hidden\" name=\"oldgedrec\" value=\"$gedrec\" />\n";
	
		// Print the JS to check for valid characters in the filename
		?><script language="JavaScript" type="text/javascript">
		<!--
		function check( filename ) {
			if( !filename.value ) return true;
			if( filename.match( /^[a-zA-Z]:[\w- \\\\]+\..*$/ ) ) 
				return true;
			else
				alert( "<?php print GM_LANG_invalid_file; ?>" ) ;
				return false;
		}
		//-->
		</script> <?php
		// 0 OBJE
		// 1 FILE
		// NOTE: probably this part of the code for existing media is not in use anymore.
		if (!empty($gedrec)) {
			$chan = GetSubrecord(1, "CHAN", $gedrec, 1);
			if (!empty($chan)) $gedrec = preg_replace("/$chan/", "", $gedrec);
			$facts = GetAllSubrecords($gedrec, "", false, false, false);
			$notefound = false;
			$foundfacts = array();
			foreach($facts as $key=>$fact) {
				$ct = preg_match_all("/(\d)\s(\w+)\s(.*)/", $fact, $match);
				for($i=0;$i<$ct;$i++) {
					$level = $match[1][$i];
					$tag = $match[2][$i];
					if (!in_array(array($tag, $level), $foundfacts)) $foundfacts[] = array($tag, $level);
					$value = $match[3][$i];
					if ($tag == "NOTE") {
						// if already in a note, write the old note first
						if ($notefound) self::AddSimpleTag("$notelevel, NOTE, $notetext");
						// Start build the note
						$notetext = $value;
						$notelevel = $level;
						$notefound = true;
					}
					else if ($tag == "CONT" || $tag == "CONC") {
						// strip the newline from the text as CONC should not start on a new line
						$notetext = preg_replace("/[\r|\n]+$/", "", $notetext);
						if ($tag == "CONT") $notetext.="\n";
						else if (GedcomConfig::$WORD_WRAPPED_NOTES) $notetext .= " ";
						$notetext .= $value;
					}
					else self::AddSimpleTag("$level $tag $value");
					if ($tag == "FILE") {
						// Box for user to choose to upload file from local computer
						print "<tr><td class=\"NavBlockLabel\">&nbsp;</td><td class=\"NavBlockField\"><input type=\"file\" name=\"picture\" size=\"60\"></td></tr>";
						// Box for user to choose the folder to store the image
						print "<tr><td class=\"NavBlockLabel\">".GM_LANG_folder."</td><td class=\"NavBlockField\"><input type=\"text\" name=\"folder\" size=\"60\"></td></tr>";
					}
				}
			}
			if (!in_array(array("FILE", 1), $foundfacts)) self::AddSimpleTag("1 FILE");
			if (!in_array(array("FORM", 2), $foundfacts)) self::AddSimpleTag("2 FORM");
			if (!in_array(array("TYPE", 3), $foundfacts)) self::AddSimpleTag("3 TYPE");
			if (!in_array(array("TITL", 2), $foundfacts)) self::AddSimpleTag("2 TITL");
			self::AddSimpleTag("1 REFN");
			self::AddSimpleTag("2 TYPE");
			self::AddSimpleTag("1 RIN");
			if ($notefound == true) self::AddSimpleTag("1 NOTE $notetext");
			self::AddSimpleTag("1 NOTE");
			self::AddSimpleTag("1 SOUR");
		}
		else {
			if (in_array("FILE", $facts_add) || in_array("FILE", $facts_unique)) {
				$element_id = self::AddSimpleTag("1 FILE");
				// Box for user to choose to upload file from local computer
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_upload_file."</td><td class=\"NavBlockField\"><input type=\"file\" name=\"picture\" size=\"60\"></td></tr>";
				// Box for user to choose the folder to store the image
				$dirlist = MediaFS::GetMediaDirList(GedcomConfig::$MEDIA_DIRECTORY, true, 1, true, false);
				print "<tr><td class=\"NavBlockLabel\">".GM_LANG_upload_to_folder."</td><td class=\"NavBlockField\">";
		//		<input type=\"text\" name=\"folder\" size=\"60\">
				print "<select name=\"folder\">";
				foreach($dirlist as $key => $dir) {
					print "<option value=\"".$dir."\">".$dir."</option>";
				}
				print "</select></td></tr>";
				self::AddSimpleTag("2 FORM");
				self::AddSimpleTag("3 TYPE");
				self::AddSimpleTag("2 TITL");
			}
			if (in_array("REFN", $facts_add) || in_array("REFN", $facts_unique)) {
				self::AddSimpleTag("1 REFN");
				self::AddSimpleTag("2 TYPE");
			}
			if (in_array("RIN", $facts_add) || in_array("RIN", $facts_unique)) self::AddSimpleTag("1 RIN");
	// 		PRIM and THUM are added at linklevel		
			if (in_array("NOTE", $facts_add) || in_array("NOTE", $facts_unique)) self::AddSimpleTag("1 NOTE");
			if (in_array("SOUR", $facts_add) || in_array("SOUR", $facts_unique)) {
				self::PrintAddLayer("SOUR", 1);
			}
		}
		self::AddAutoAcceptLink();	
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\" onclick=\"return check(document.newmedia.picture.value);\" value=\"".GM_LANG_add_media_button."\" /></td></tr>\n";
		print "</table>\n";
		print "</form>\n";
		if (isset($element_id)) print "\n<script type=\"text/javascript\">\n<!--\ndocument.getElementById(\"".$element_id."\").focus();\n//-->\n</script>";
	}
	
	public function ReplaceLinks($oldgid, $newgid, $mtype, $change_id, $change_type, $gedid) {
	
		$records = self::GetLinkedGedRecs($oldgid, $mtype, $gedid);
			
		foreach ($records as $key1 => $object) {	
			foreach ($object->facts as $key2 => $fact) {
				if ($fact->style != "ChangeOld") {
					if (preg_match("/@$oldgid@/", $fact->factrec)) {
						if ($fact->fact != "CHIL" && $fact->fact != "HUSB" && $fact->fact != "WIFE" && $fact->fact != "FAMC" && $fact->fact != "FAMS") {
							$newsub = preg_replace("/(\d) (\w+) @$oldgid@/", "$1 $2 @$newgid@", $fact->factrec);
							self::ReplaceGedrec($fact->owner->xref, $fact->factrec, $newsub, $fact->fact, $change_id, $change_type, $gedid, $object->datatype);
						}
					}
				}
			}
		}
	}
	
	public function DeleteLinks($oldgid, $mtype, $change_id, $change_type, $gedid) {
	
		// We miss the links on new records, which are only in the _changes table
		$records = self::GetLinkedGedRecs($oldgid, $mtype, $gedid);
		$success = true;
		foreach ($records as $key1 => $object) {
			foreach($object->facts as $key2 => $fact) {
				if ($fact->style != "ChangeOld") {
					if (preg_match("/(\d) (\w+ @$oldgid@)/", $fact->factrec, $match2)) {
						// print "subold: ".$fact->factrec."<br />";
						$subdel = GetSubRecord($match2[1], $match2[1]." ".$match2[2], $fact->factrec);
						// print "string: ".$subdel."<br />";
						// if (!strstr($fact->factrec, $subdel)) print "Not found!<br />";
						$subnew = str_replace($subdel, "", $fact->factrec, $count);
						// if ($count == 0) print "NOT REPLACED<br />";
						// print "subnew: ".$subnew."<br /><br />";
						$success = $success && self::ReplaceGedrec($fact->owner->xref, $fact->factrec, $subnew, $fact->fact, $change_id, $change_type, $gedid, $object->datatype);
					}
				}
			}
		}
		return $success;
	}
	
	public function GetLinkedGedrecs($oldgid, $mtype, $gedid) {
		
		$records = array();
		
		//-- Collect all gedcom records
		
		// All pointing to this source
		if ($mtype == "SOUR") {
			$sql = "SELECT sm_gid as pid, sm_type as type FROM ".TBLPREFIX."source_mapping WHERE sm_sid='".$oldgid."' AND sm_file='".$gedid."'";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()) {
				$object = ConstructObject($row["pid"], $row["type"], $gedid);
				$records[$row["pid"]] = $object;
			}
		}
		// All pointing to this repo
		if ($mtype == "REPO" || $mtype == "NOTE") {
			$sql = "SELECT om_gid as pid, om_type as type FROM ".TBLPREFIX."other_mapping WHERE om_oid='".$oldgid."' AND om_file='".$gedid."'";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()) {
				$object = ConstructObject($row["pid"], $row["type"], $gedid);
				$records[$row["pid"]] = $object;
			}
		}
		// All pointing to this mm-object
		if ($mtype == "OBJE") {
			$sql = "SELECT mm_gid as pid, mm_type as type FROM ".TBLPREFIX."media_mapping WHERE mm_media='".$oldgid."' AND mm_file='".$gedid."'";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()) {
				$object = ConstructObject($row["pid"], $row["type"], $gedid);
				$records[$row["pid"]] = $object;
			}
		}
		// All pointing to this individual
		if ($mtype == "INDI") {
			$sql = "SELECT if_fkey as pid FROM ".TBLPREFIX."individual_family WHERE if_pkey='".JoinKey($oldgid, $gedid)."'";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()) {
				$object = ConstructObject(Splitkey($row["pid"], "id"), "FAM", $gedid);
				$records[Splitkey($row["pid"], "id")] = $object;
			}
			$sql = "SELECT as_of as pid, as_type as type FROM ".TBLPREFIX."asso WHERE as_pid='".JoinKey($oldgid, $gedid)."'";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()) {
				$object = ConstructObject(Splitkey($row["pid"], "id"), ($row["type"] == "I" ? "INDI" : "FAM"), $gedid);
				$records[Splitkey($row["pid"], "id")] = $object;
			}
		}
		// All pointing to this family
		if ($mtype == "FAM") {
			$sql = "SELECT if_pkey as pid FROM ".TBLPREFIX."individual_family WHERE if_fkey='".JoinKey($oldgid, $gedid)."'";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()) {
				$object = ConstructObject(Splitkey($row["pid"], "id"), "INDI", $gedid);
				$records[Splitkey($row["pid"], "id")] = $object;
			}
		}
		
/*		//-- References from sources (REPO, OBJE, NOTE)
		if ($mtype == "REPO" || $mtype == "OBJE" || $mtype == "NOTE") {
			$sql = "SELECT s_gedrec, s_id FROM ".TBLPREFIX."sources WHERE s_gedrec REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND s_file='".$gedid."'";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					$records[] = $row["s_gedrec"];
				}
			}
		}
		
		//-- References from individuals (SOUR, INDI, FAM, NOTE, OBJE)
		if ($mtype == "SOUR" || $mtype == "INDI" || $mtype == "FAM" || $mtype == "NOTE" || $mtype == "OBJE") {
			$sql = "SELECT i_gedrec, i_id FROM ".TBLPREFIX."individuals WHERE i_gedrec REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND i_file='".$gedid."'";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					$records[] = $row["i_gedrec"];
				}
			}
		}
	
		//-- References from families (SOUR, INDI, NOTE, OBJE)
		if ($mtype == "SOUR" || $mtype == "INDI" || $mtype == "NOTE" || $mtype == "OBJE") {
			$sql = "SELECT f_gedrec, f_id FROM ".TBLPREFIX."families WHERE f_gedrec REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND f_file='".$gedid."'";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					$records[] = $row["f_gedrec"];
				}
			}
		}
		
		//-- References from multimedia (SOUR, NOTE)
		if ($mtype == "SOUR" || $mtype == "NOTE") {
			$sql = "SELECT m_gedrec, m_media FROM ".TBLPREFIX."media WHERE m_gedrec REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND m_file='".$gedid."'";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					$records[] = $row["m_gedrec"];
				}
			}
		}
	
		//-- References from notes, submitter recs (SOUR, INDI) <== links to notes and repositories
		if ($mtype == "SOUR" || $mtype == "INDI" || $mtype == "REPO" || $mtype == "OBJE") {
			$sql = "SELECT o_gedrec, o_id FROM ".TBLPREFIX."other WHERE o_gedrec REGEXP '[1-9] [[:alnum:]]+ @".$oldgid."@' AND o_file='".$gedid."'";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					$records[] = $row["f_gedrec"];
				}
			}
		}
*/		
		return $records;
	}
	
	public function DeleteFamIfEmpty($famid, $change_id, $change_type, $gedid="") {
		static $chid;
		
		// Get a new change ID. Deletions must be in another change group as changes on the same record.
		if (!isset($chid)) $chid = EditFunctions::GetNewXref("CHANGE");
		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		$family =& Family::NewInstance($famid, "", $gedid);
		if ($family->ischanged) $famrec = $family->changedgedrec;
		else $famrec = $family->gedrec;
		$ct = preg_match("/1 CHIL|HUSB|WIFE/", $famrec);
		if ($ct == 0) return self::ReplaceGedRec($famid, $famrec, "", "FAM", $chid, "delete_family", $gedid, "FAM");
		else return true;
	}
	
	public function SortFactDetails($gedrec) {
		
		// Check for level 1
		if (substr($gedrec,0,1) != 1) return $gedrec;
		
		// Split the level 2 records
		$gedlines = explode("\r\n2 ", trim($gedrec));
		if (count($gedlines) <= 2) return $gedrec;
		
		// Keep the level 1 apart
		$l1rec = array_shift($gedlines);
		
		// Split the level 2 recs into their arrays
		$other = array();
		$asso = array();
		$sour = array();
		$obje = array();
		$note = array();
		$resn = array();
		foreach($gedlines as $key=>$gedline) {
			$tag = substr($gedline, 0, strpos($gedline, " ")); 
			if ($tag == "SOUR") $sour[] = $gedline;
			else if ($tag == "OBJE") $obje[] = $gedline;
			else if ($tag == "RESN") $resn[] = $gedline;
			else if ($tag == "ASSO") $asso[] = $gedline;
			else if ($tag == "NOTE") $note[] = $gedline;
			else $other[] = $gedline;
		}
		// Add the missing tags to the basic info
	//	$tags = array();
	//	print_r($other);
	//	$otherrec = $l1rec."\r\n2 ".implode("\r\n2 ", $other);
	//	$ct = preg_match_all("/\d\s(\w+).+/", $otherrec, $match);
	//	if ($ct > 0) {
	//		foreach($match[1] as $index=>$tag) {
	//			$tags[] = $tag;
	//		}
	//		print_r($tags);	
	//	}
		
		// Recompose the record
		$all = array_merge($other, $asso);
		$all = array_merge($all, $obje);
		$all = array_merge($all, $sour);
		$all = array_merge($all, $note);
		$all = array_merge($all, $resn);
			
		// Rebuild the string
		$gedrec = $l1rec."\r\n2 ".implode("\r\n2 ", $all);
		return $gedrec;
	}
	
	public function AddMissingTags($tags) {
		global $templefacts, $nondatefacts, $nonplacfacts, $timefacts, $focus;
	
		// Now add some missing tags :
		if (in_array($tags[0], $templefacts)) {
			// 2 TEMP
			if (!in_array("TEMP", $tags)) self::AddSimpleTag("2 TEMP");
			// 2 STAT
			if (!in_array("STAT", $tags)) self::AddSimpleTag("2 STAT");
		}
		if ($tags[0]=="EVEN" or $tags[0]=="GRAD" or $tags[0]=="MARR") {
			// 1 EVEN|GRAD|MARR
			// 2 TYPE
			if (!in_array("TYPE", $tags)) self::AddSimpleTag("2 TYPE");
		}
		if ($tags[0]=="EDUC" or $tags[0]=="GRAD" or $tags[0]=="OCCU") {
			// 1 EDUC|GRAD|OCCU
			// 2 CORP
			if (!in_array("CORP", $tags)) self::AddSimpleTag("2 CORP");
		}
		if ($tags[0]=="DEAT") {
			// 1 DEAT
			// 2 CAUS
			if (!in_array("CAUS", $tags)) self::AddSimpleTag("2 CAUS");
		}
		if ($tags[0]=="REPO") {
			//1 REPO
			//2 CALN
			if (!in_array("CALN", $tags)) self::AddSimpleTag("2 CALN");
			}
		if (!in_array($tags[0], $nondatefacts)) {
			// 2 DATE
			// 3 TIME
			if (!in_array("DATE", $tags)) self::AddSimpleTag("2 DATE");
			if (!in_array("TIME", $tags) && in_array($tags[0], $timefacts)) self::AddSimpleTag("3 TIME");
			// 2 PLAC
			if (!in_array("PLAC", $tags) && !in_array($tags[0], $nonplacfacts) && !in_array("TEMP", $tags)) self::AddSimpleTag("2 PLAC");
		}
		if ($tags[0]=="BURI") {
			// 1 BURI
			// 2 CEME
			if (!in_array("CEME", $tags)) self::AddSimpleTag("2 CEME");
		}
		if ($tags[0]=="BIRT" or $tags[0]=="DEAT"
		or $tags[0]=="EDUC" or $tags[0]=="GRAD"
		or $tags[0]=="OCCU" or $tags[0]=="ORDN" or $tags[0]=="RESI") {
			// 1 BIRT|DEAT|EDUC|GRAD|ORDN|RESI
			// 2 ADDR
			if (!in_array("ADDR", $tags)) self::AddSimpleTag("2 ADDR");
		}
		if ($tags[0]=="OCCU" or $tags[0]=="RESI") {
			// 1 OCCU|RESI
			// 2 PHON|FAX|EMAIL|URL
			if (!in_array("PHON", $tags)) self::AddSimpleTag("2 PHON");
			if (!in_array("FAX", $tags)) self::AddSimpleTag("2 FAX");
			if (!in_array("EMAIL", $tags)) self::AddSimpleTag("2 EMAIL");
			if (!in_array("URL", $tags)) self::AddSimpleTag("2 URL");
		}
		if ($tags[0]=="FILE") {
			// 1 FILE
			// Box for user to choose to upload file from local computer
			print "<tr><td class=\"NavBlockLabel\">".GM_LANG_upload_file."</td><td class=\"NavBlockField\"><input type=\"file\" name=\"picture\" size=\"60\"></td></tr>";
			// Box for user to choose the folder to store the image
			$dirlist = MediaFS::GetMediaDirList(GedcomConfig::$MEDIA_DIRECTORY, true, 1, true, false);
			print "<tr><td class=\"NavBlockLabel\">".GM_LANG_upload_to_folder."</td><td class=\"NavBlockField\">";
			print "<select name=\"folder\">";
			foreach($dirlist as $key => $dir) {
				print "<option value=\"".$dir."\">".$dir."</option>";
			}
			print "</select></td></tr>";
			// 2 TITL
			if (!in_array("TITL", $tags)) self::AddSimpleTag("2 TITL");
			// 2 FORM
			if (!in_array("FORM", $tags)) self::AddSimpleTag("2 FORM");
			// 3 TYPE
			if (!in_array("TYPE", $tags)) self::AddSimpleTag("3 TYPE");
		}
		if (in_array($tags[0], $templefacts)) {
			// 2 TEMP
			self::AddSimpleTag("2 TEMP");
			// 2 STAT
			self::AddSimpleTag("2 STAT");
		}
	}

	public function HasOtherChanges($pid, $change_id, $gedid="") {
		
		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		if (ChangeFunctions::GetChangeData(true, $pid, true)) {
			$sql = "SELECT count(ch_id) FROM ".TBLPREFIX."changes WHERE ch_file='".$gedid."' AND ch_gid='".$pid."' AND ch_cid<>'".$change_id."'";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			return $row[0];
		}
		else return false;
	}
	
	/**
	 * Get the next available xref
	 *
	 * @author	Genmod Development Team
	 * @param		string	$type		The type of xref to retrieve
	 * @return 	string	The new xref that was found
	 */
	
	public function GetNewXref($type='INDI') {
		global $changes;
		global $FILE;
		
		
		if (isset($FILE) && !is_array($FILE)) $gedid = get_id_from_gedcom($FILE);
		else $gedid = GedcomConfig::$GEDCOMID;
	
		switch ($type) {
			case "INDI":
				$sqlc = "select max(cast(substring(ch_gid,".(strlen(GedcomConfig::$GEDCOM_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."changes where ch_file = '".$gedid."' AND ch_gid LIKE '".GedcomConfig::$GEDCOM_ID_PREFIX."%'";
				$sql = "select max(cast(substring(i_rin,".(strlen(GedcomConfig::$GEDCOM_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."individuals where i_file = '".$gedid."'";
				break;
			case "FAM":
				$sqlc = "select max(cast(substring(ch_gid,".(strlen(GedcomConfig::$FAM_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."changes where ch_file = '".$gedid."' AND ch_gid LIKE '".GedcomConfig::$FAM_ID_PREFIX."%'";
				$sql = "select max(cast(substring(f_id,".(strlen(GedcomConfig::$FAM_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."families where f_file = '".$gedid."'";
				break;
			case "OBJE":
				$sqlc = "select max(cast(substring(ch_gid,".(strlen(GedcomConfig::$MEDIA_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."changes where ch_file = '".$gedid."' AND ch_gid LIKE '".GedcomConfig::$MEDIA_ID_PREFIX."%'";
				$sql = "select max(cast(substring(m_media,".(strlen(GedcomConfig::$MEDIA_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."media where m_file = '".$gedid."'";
				break;
			case "SOUR":
				$sqlc = "select max(cast(substring(ch_gid,".(strlen(GedcomConfig::$SOURCE_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."changes where ch_file = '".$gedid."' AND ch_gid LIKE '".GedcomConfig::$SOURCE_ID_PREFIX."%'";
				$sql = "select max(cast(substring(s_id,".(strlen(GedcomConfig::$SOURCE_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."sources where s_file = '".$gedid."'";
				break;
			case "REPO":
				$sqlc = "select max(cast(substring(ch_gid,".(strlen(GedcomConfig::$REPO_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."changes where ch_file = '".$gedid."' AND ch_gid LIKE '".GedcomConfig::$REPO_ID_PREFIX."%'";
				$sql = "select max(cast(substring(o_id,".(strlen(GedcomConfig::$REPO_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."other where o_file = '".$gedid."' and o_type = 'REPO'";
				break;
			case "NOTE":
				$sqlc = "select max(cast(substring(ch_gid,".(strlen(GedcomConfig::$NOTE_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."changes where ch_file = '".$gedid."' AND ch_gid LIKE '".GedcomConfig::$NOTE_ID_PREFIX."%'";
				$sql = "select max(cast(substring(o_id,".(strlen(GedcomConfig::$NOTE_ID_PREFIX)+1).") as signed)) as xref from ".TBLPREFIX."other where o_file = '".$gedid."' and o_type = 'NOTE'";
				break;
			case "CHANGE":
				$sql = "select max(ch_cid) as xref from ".TBLPREFIX."changes where ch_file = '".$gedid."'";
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
		if ($type == "INDI") $prefix = GedcomConfig::$GEDCOM_ID_PREFIX;
		else if ($type == "FAM") $prefix = GedcomConfig::$FAM_ID_PREFIX;
		else if ($type == "OBJE") $prefix = GedcomConfig::$MEDIA_ID_PREFIX;
		else if ($type == "SOUR") $prefix = GedcomConfig::$SOURCE_ID_PREFIX;
		else if ($type == "REPO") $prefix = GedcomConfig::$REPO_ID_PREFIX;
		else if ($type == "NOTE") $prefix = GedcomConfig::$NOTE_ID_PREFIX;
		else if ($type == "CHANGE") return $num;
	
		return $prefix.$num;;
	}

	/*
	* Return the media ID based on the FILE and TITLE value, depending on the gedcom setting
	* If check for existing media is disabled, return false.
	*/
	public function CheckDoubleMedia($file, $title, $gedid) {
		
		if (GedcomConfig::$MERGE_DOUBLE_MEDIA == 0) return false;
		
		$sql = "SELECT m_media FROM ".TBLPREFIX."media WHERE m_file='".$gedid."' AND m_mfile LIKE '".DbLayer::EscapeQuery($file)."'";
		if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "2") $sql .= " AND m_titl LIKE '".DbLayer::EscapeQuery($title)."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return false;
		else {
			$row = $res->FetchAssoc();
			return $row["m_media"];
		}
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

	public function GetAssoRela() {
			
		// items for ASSO RELA selector :
		$assokeys = array(
		"attendant",
		"attending",
		"circumciser",
		"civil_registrar",
		"friend",
		"godfather",
		"godmother",
		"godparent",
		"informant",
		"lodger",
		"nurse",
		"priest",
		"rabbi",
		"registry_officer",
		"servant",
		"submitter",
		"twin",
		"twin_brother",
		"twin_sister",
		"witness",
		"" // DO NOT DELETE
		);
		$assorela = array();
		foreach ($assokeys as $indexval => $key) {
		  if (defined("GM_LANG_".$key)) $assorela["$key"] = constant("GM_LANG_".$key);
		  else $assorela["$key"] = "? $key";
		}
		natsort($assorela);
		return $assorela;
	}
	
	public function FindSubmitter($gedid) {
		
		if (!isset($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".$gedid."' AND o_type='SUBM'";
		$res = NewQuery($sql);
		if ($res && $res->NumRows() > 0) {
			$row = $res->FetchAssoc();
			return Submitter::GetInstance($row["o_id"], $row, $row["o_file"]);
		}
		else {
			// If there is a new unapproved submitter record, is has the default pid
			if (ChangeFunctions::GetChangeData(true, "SUB1", false, "", "")) {
				return Submitter::GetInstance("SUB1", "", $gedid);
			}
		}
		return "";
	}
	
	public function CheckReorder($array) {
		
		$check = array();
		for ($i = 1; $i <= count($array); $i++) {
			$check[$i] = true;
		}
		foreach ($array as $key => $value) {
			if (!isset($check[$value])) return false;
			else unset($check[$value]);
		}
		return true;
	} 

	public function DeleteIDFromChangeGroup($id, $file, $group) {
		
		$sql = "DELETE FROM ".TBLPREFIX."changes WHERE ch_cid='".$group."' AND ch_file='".$file."' AND ch_gid='".$id."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}

	/**
	 * creates PLAC input subfields (Country, District ...) according to Gedcom HEAD>PLACE>FORM
	 *
	 * data split/copy is done locally by javascript functions
	 *
	 * @param string $element_id	id of PLAC input element in the form
	 */
	public function PrintPlaceSubfields($element_id) {
		global $GM_IMAGES, $lang_short_cut, $LANGUAGE;
		global $countries;
		static $js_loaded;
		
		if (!isset($js_loaded)) {
			$js_loaded = true;
			?>
			<link rel="stylesheet" type="text/css" href="places/dropdown.css" />
			<script type="text/javascript" src="places/getobject.js"></script>
			<script type="text/javascript" src="places/modomt.js"></script>
			<script type="text/javascript" src="places/xmlextras.js"></script>
			<script type="text/javascript" src="places/acdropdown.js"></script>
			<?php
		}
	
		if ($element_id == "DEAT_PLAC") return; // known bug - waiting for a patch
	
		$HEAD =& Header::GetInstance("HEAD");
		$HEAD_PLAC_FORM = $HEAD->placeformat;
		if (empty($HEAD_PLAC_FORM)) $HEAD_PLAC_FORM = GM_LANG_default_form;
		$plac_label = preg_split ("/,/", $HEAD_PLAC_FORM);
		$plac_label = array_reverse($plac_label);
		if ($HEAD_PLAC_FORM == GM_LANG_default_form) $plac_label[0] = GM_FACT_CTRY;
		?>
		<script type="text/javascript" src="strings.js"></script>
		<script type="text/javascript">
		<!--
		// called to refresh field PLAC after any subfield change
		function updatewholeplace(place_tag) {
			place_value="";
			for (p=0; p<<?php print count($plac_label);?>; p++) {
				place_subtag=place_tag+'_'+p;
				if (document.getElementById(place_subtag)) {
					if (p>0) place_value = document.getElementById(place_subtag).value+", "+place_value;
					else place_value = document.getElementById(place_subtag).value;
				}
			}
			document.getElementById(place_tag).value = place_value;
		}
		// called to refresh subfields after any field PLAC change
		function splitplace(place_tag) {
			place_value = document.getElementById(place_tag).value;
//			comma = String.fromCharCode(65292);
//			place_value.replace(comma, ',');
			var place_array=place_value.split(",");
			var len=place_array.length;
			for (p=0; p<len; p++) {
				q=len-p-1;
				place_subtag=place_tag+'_'+p;
				if (document.getElementById(place_subtag)) {
					//alert(place_subtag+':'+place_array[q]);
					document.getElementById(place_subtag).value=trim(place_array[q]);
				}
			}
			//document.getElementById(place_tag+'_0').focus();
			if (document.getElementsByName('PLAC_CTRY')) {
				elt=document.getElementsByName('PLAC_CTRY')[0];
				ctry=elt.value.toUpperCase();
				//alert(elt.value.charCodeAt(0)+'\n'+elt.value.charCodeAt(1));
				if (elt.value=='\u05d9\u05e9\u05e8\u05d0\u05dc') ctry='ISR'; // Israel hebrew name
				else if (ctry.length==3) elt.value=ctry;
				ctry=ctry.substr(0,3);
				pdir='places/'+ctry+'/';
				// select current country in the list
				sel=document.getElementsByName('PLAC_CTRY_select')[0];
				for(i=0;i<sel.length;++i) if (sel.options[i].value==ctry) sel.options[i].selected=true;
				// refresh country flag
				img=document.getElementsByName('PLAC_CTRY_flag')[0];
				// Get the current flag name
				var flagnamelen = img.src.length;
				var flagname = img.src.substr(flagnamelen - 7, 7);
				if (flagname != ctry.toLowerCase()+'.gif') {
					var testimg = new Image();		
					testimg.onload = function () {
						img.src='places/flags/'+ctry.toLowerCase()+'.gif';
						img.alt=ctry;
						img.title=ctry;
					};
					testimg.onerror = function () {
						img.src='images/spacer.gif';
					}
					testimg.src = 'places/flags/'+ctry.toLowerCase()+'.gif';
				}
				// refresh country image
				img=document.getElementsByName('PLAC_CTRY_img')[0];
				if (document.getElementsByName(ctry)[0]) {
					img.src=pdir+ctry+'.gif';
					img.alt=ctry;
					img.title=ctry;
					img.useMap='#'+ctry;
				}
				else {
					img.src='images/pix1.gif'; // show image only if mapname exists
					document.getElementsByName('PLAC_CTRY_div')[0].style.height='auto';
				}
				// refresh state image
				img=document.getElementsByName('PLAC_STAE_auto')[0];
				img.alt=ctry;
				img.title=ctry;
				stae=document.getElementsByName('PLAC_STAE')[0].value;
				stae=strclean(stae);
				stae=ctry+'_'+stae;
				img=document.getElementsByName('PLAC_STAE_img')[0];
				if (document.getElementsByName(stae)[0]) {
					img.src=pdir+stae+'.gif';
					img.alt=stae;
					img.title=stae;
					img.useMap='#'+stae;
				}
				else {
					img.src='images/pix1.gif'; // show image only if mapname exists
					document.getElementsByName('PLAC_STAE_div')[0].style.height='auto';
				}
				// refresh county image
				img=document.getElementsByName('PLAC_CNTY_auto')[0];
				img.alt=stae;
				img.title=stae;
				cnty=document.getElementsByName('PLAC_CNTY')[0].value;
				cnty=strclean(cnty);
				cnty=stae+'_'+cnty;
				img=document.getElementsByName('PLAC_CNTY_img')[0];
				if (document.getElementsByName(cnty)[0]) {
					img.src=pdir+cnty+'.gif';
					img.alt=cnty;
					img.title=cnty;
					img.useMap='#'+cnty;
				}
				else {
					img.src='images/pix1.gif'; // show image only if mapname exists
					document.getElementsByName('PLAC_CNTY_div')[0].style.height='auto';
				}
				// refresh city image
				img=document.getElementsByName('PLAC_CITY_auto')[0];
				img.alt=cnty;
				img.title=cnty;
			}
		}
		// called when clicking on +/- PLAC button
		function toggleplace(place_tag) {
			var ronly=document.getElementById(place_tag).readOnly;
			document.getElementById(place_tag).readOnly=1-ronly;
			if (ronly) {
				//document.getElementById(place_tag).disabled=false;
				document.getElementById(place_tag+'_pop').style.display="inline";
				updatewholeplace(place_tag);
			}
			else {
				//document.getElementById(place_tag).disabled=true;
				document.getElementById(place_tag+'_pop').style.display="none";
				splitplace(place_tag);
			}
		}
		// called when selecting a new country in country list
		function setPlaceCountry(txt) {
			document.getElementsByName('PLAC_CTRY')[0].value=txt;
			updatewholeplace('<?php print $element_id?>');
			splitplace('<?php print $element_id?>');
			place_value = document.getElementById('<?php print $element_id?>').value;
			var place_array=place_value.split(",");
			var len=place_array.length;
			for (p=1; p<len; p++) {
				q=len-p-1;
				place_subtag='<?php print $element_id?>'+'_'+p;
				if (document.getElementById(place_subtag)) {
					//alert(place_subtag+':'+place_array[q]);
					document.getElementById(place_subtag).value="";
				}
			}
		}
		// called when clicking on a new state/region on country map
		function setPlaceState(txt) {
			document.getElementsByName('PLAC_STAE_div')[0].style.height='auto';
			p=txt.indexOf(' ('); if (1<p) txt=txt.substring(0,p); // remove code (XX)
			if (txt.length) document.getElementsByName('PLAC_STAE')[0].value=txt;
			updatewholeplace('<?php print $element_id?>');
			splitplace('<?php print $element_id?>');
		}
		// called when clicking on a new county on state map
		function setPlaceCounty(txt) {
			document.getElementsByName('PLAC_CNTY_div')[0].style.height='auto';
			p=txt.indexOf(' ('); if (1<p) txt=txt.substring(0,p); // remove code (XX)
			if (txt.length) document.getElementsByName('PLAC_CNTY')[0].value=txt;
			updatewholeplace('<?php print $element_id?>');
			splitplace('<?php print $element_id?>');
		}
		// called when clicking on a new city on county map
		function setPlaceCity(txt) {
			div=document.getElementsByName('PLAC_CNTY_div')[0];
			if (div.style.height!='auto') { div.style.height='auto'; return; } else div.style.height='32px';
			if (txt.length) document.getElementsByName('PLAC_CITY')[0].value=txt;
			updatewholeplace('<?php print $element_id?>');
			splitplace('<?php print $element_id?>');
		}
		//-->
		</script>
		<?php
		// loading all maps definitions
		$handle = opendir("places/");
		while (($file = readdir ($handle)) !== false) {
			$mapfile = "places/".$file."/".$file.".".$lang_short_cut[$LANGUAGE].".htm";
			if (!file_exists($mapfile)) $mapfile = "places/".$file."/".$file.".htm";
			if (file_exists($mapfile)) include($mapfile);
		}
		closedir($handle);
	
		$cols=40;
		print "&nbsp;<a href=\"javascript: ".GM_LANG_show_details."\" onclick=\"expand_layer('".$element_id."_div'); toggleplace('".$element_id."'); return false;\"><img id=\"".$element_id."_div_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" />&nbsp;</a>";
		print "<br /><div id=\"".$element_id."_div\" style=\"display: none; border-width:thin; border-style:none; padding:0px\">\n";
		// subtags creation : _0 _1 _2 etc...
		$icountry=-1;
		$istate=-1;
		$icounty=-1;
		$icity=-1;
		for ($i=0; $i<count($plac_label); $i++) {
			$subtagid=$element_id."_".$i;
			$subtagname=$element_id."_".$i;
			$plac_label[$i]=trim($plac_label[$i]);
			if (in_array($plac_label[$i], array("Country", "Pays", "Land", "Zeme", "?lke", "Pa?s", "Orsz?g", "Nazione", "Kraj", "Maa", GM_FACT_CTRY))) {
				$cols="8";
				$subtagname="PLAC_CTRY";
				$icountry=$i;
				$istate=$i+1;
				$icounty=$i+2;
				$icity=$i+3;
			} else $cols=40;
			if ($i==$istate) $subtagname="PLAC_STAE";
			if ($i==$icounty) $subtagname="PLAC_CNTY";
			if ($i==$icity) $subtagname="PLAC_CITY";
			$key=strtolower($plac_label[$i]);
			print "<small>";
			if (defined("GM_LANG_".$key)) print constant("GM_LANG_".$key);
			else print $plac_label[$i];
			print "</small><br />";
			print "<input type=\"text\" id=\"".$subtagid."\" name=\"".$subtagname."\" value=\"\" size=\"".$cols."\"";
			print " tabindex=\"".($i+1)."\" ";
			print " onblur=\"updatewholeplace('".$element_id."'); splitplace('".$element_id."');\" ";
			print " onchange=\"updatewholeplace('".$element_id."'); splitplace('".$element_id."');\" ";
			print " onmouseout=\"updatewholeplace('".$element_id."'); splitplace('".$element_id."');\" ";
			if ($icountry<$i and $i<=$icity) print " acdropdown=\"true\" autocomplete_list=\"url:places/getdata.php?field=".$subtagname."&amp;s=\" autocomplete=\"off\" autocomplete_matchbegin=\"false\"";
			print " />\n";
			// country selector
			if ($i==$icountry) {
				print '<img id="PLAC_CTRY_flag" name="PLAC_CTRY_flag" src="images\spacer.gif" />';
				print "<select id=\"".$subtagid."_select\" name=\"".$subtagname."_select\" class=\"submenuitem\"";
				print " onchange=\"setPlaceCountry(this.value);\"";
				print " >\n";
				print "<option value=\"\">?</option>\n";
				foreach ($countries as $alpha3=>$country) {
					$txt=$alpha3." : ".$country;
					print "<option value=\"".$alpha3."\">".$txt."</option>\n";
				}
				print "</select>\n";
			}
			else {
				if ($icountry<$i and $i<=$icity) {
					$text = GM_LANG_autocomplete;
					if (isset($GM_IMAGES["autocomplete"]["button"])) $Link = "<img id=\"".$subtagid."_auto\" name=\"".$subtagname."_auto\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["autocomplete"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" />";
					else $Link = $text;
					print "&nbsp;".$Link."&nbsp;";
				}
				LinkFunctions::PrintSpecialCharLink($subtagid);
			}
			// clickable map
			if ($i<$icountry or $i>$icounty) print "<br />\n";
			else print "<div id='".$subtagname."_div' name='".$subtagname."_div' style='overflow:hidden; border-width:thin; border-style:none;'><img name='".$subtagname."_img' src='images/spacer.gif' usemap='usemap' border='0' alt='' title='' /></div>";
		}
		print "</div>";
	}
	
	public function AddAutoAcceptLink($cols=2) {
		global $gm_user;
		
		if ($gm_user->UserCanAccept() && !$gm_user->userAutoAccept()) {
			print "<tr><td ".($cols > 1 ? "colspan=\"".$cols."\" " : "")."class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
			print "<tr><td ".($cols > 1 ? "colspan=\"".$cols."\" " : "")."class=\"NavBlockColumnHeader EditTableColumnHeader\">".GM_LANG_auto_accept_options."</td></tr>\n";
			print "<tr><td ".($cols > 1 ? "colspan=\"".$cols."\" " : "")."class=\"NavBlockLabel\">\n";
			print "<input name=\"aa_attempt\" type=\"checkbox\" value=\"1\" />".GM_LANG_attempt_auto_acc."\n";
			print "</td></tr>\n";
			print "<tr><td ".($cols > 1 ? "colspan=\"".$cols."\" " : "")."class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
		}
	}
	
	public function PrintSuccessMessage($message="") {
		
		print "<div class=\"EditMessageSuccess\">".($message == "" ? GM_LANG_update_successful : $message)."</div>";
	}

	public function PrintFailMessage($message) {
		
		print "<div class=\"EditMessageFail\">".$message."</div>";
	}
}
?>