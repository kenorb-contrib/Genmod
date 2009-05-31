<?php
/**
 * Various functions used by the Edit interface
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
 * @subpackage Edit
 * @see functions_places.php
 * @version $Id: functions_edit.php,v 1.33 2006/04/30 18:44:15 roland-d Exp $
 */

if (strstr($_SERVER["SCRIPT_NAME"],"functions")) {
	print "Now, why would you want to do that.  You're not hacking are you?";
	exit;
}

/**
 * The DEBUG variable allows you to turn on debugging
 * which will write all communication output to the gm log files
 * in the index directory and print other information to the screen.
 * Set this to true to enable debugging,
 * but be sure to set it back to false when you are done debugging.
 * @global boolean $DEBUG
 */
$DEBUG = false;

$NPFX_accept = array("Adm", "Amb", "Brig", "Can", "Capt", "Chan", "Chapln", "Cmdr", "Col", "Cpl", "Cpt", "Dr", "Gen", "Gov", "Hon", "Lady", "Lt", "Mr", "Mrs", "Ms", "Msgr", "Pfc", "Pres", "Prof", "Pvt", "Rep", "Rev", "Sen", "Sgt", "Sir", "Sr", "Sra", "Srta", "Ven");
$SPFX_accept = array("al", "da", "de", "den", "dem", "der", "di", "du", "el", "la", "van", "von");
$NSFX_accept = array("Jr", "Sr", "I", "II", "III", "IV", "MD", "PhD");
$FILE_FORM_accept = array("avi", "bmp", "gif", "jpeg", "mp3", "ole", "pcx", "tiff", "wav");
$emptyfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","EVEN","MARR","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","OBJE","CHAN","_SEPR","RESI", "DATA", "MAP");
$templefacts = array("SLGC","SLGS","BAPL","ENDL","CONL");
$nonplacfacts = array("SLGC","SLGS","ENDL");
$nondatefacts = array("ABBR","ADDR","AFN","AUTH","EMAIL","FAX","NAME","NOTE","OBJE","PHON","PUBL","REPO","SEX","SOUR","TEXT","TITL","WWW","_EMAIL","REFN");
$typefacts = array();	//-- special facts that go on 2 TYPE lines

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
function replace_gedrec($gid, $oldrec, $newrec, $fact="", $change_id, $change_type) {
	global $GEDCOM, $manual_save, $GEDCOMS, $TBLPREFIX, $gm_username;
	
	$gid = strtoupper($gid);
	//-- the following block of code checks if the XREF was changed in this record.
	//-- if it was changed we add a warning to the change log
	$ct = preg_match("/0 @(.*)@/", $newrec, $match);
	if ($ct>0) {
		$oldgid = $gid;
		$gid = trim($match[1]);
		if ($oldgid!=$gid) {
			WriteToLog("Warning: $oldgid was changed to $gid", "W", "G", $GEDCOM);
		}
	}
	
	// NOTE: Check if there are changes present, if so flag pending changes so they cannot be approved
	if (change_present($gid,true) && ($change_type == "raw_edit" || $change_type == "reorder_families" || $change_type == "reorder_children")) {
		$sql = "select ch_cid as cid from ".$TBLPREFIX."changes where ch_gid = '".$gid."' and ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' order by ch_cid ASC";
		$res = dbquery($sql);
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$sqlcid = "update ".$TBLPREFIX."changes set ch_delete = '1' where ch_cid = '".$row["cid"]."'";
			$rescid = dbquery($sqlcid);
		}
	}
	
	if (userAutoAccept()) {
		update_record($newrec);
	}
	else {
		
		$sql = "INSERT INTO ".$TBLPREFIX."changes (ch_cid, ch_gid, ch_gedfile, ch_type, ch_user, ch_time, ch_fact, ch_old, ch_new)";
		$sql .= "VALUES ('".$change_id."', '".$gid."', '".$GEDCOMS[$GEDCOM]["id"]."', '".$change_type."', '".$gm_username."', '".time()."'";
		$sql .= ", '".$fact."', '".mysql_real_escape_string($oldrec)."', '".mysql_real_escape_string($newrec)."')";
		$res = dbquery($sql);
	}
	// if (!isset($manual_save) || ($manual_save==false)) {
		WriteToLog("Replacing gedcom record $gid ->" . $gm_username ."<-", "I", "G", $GEDCOM);
		// return write_changes();
	//}
	return true;
}

//-------------------------------------------- append_gedrec
//-- this function will append a new gedcom record at
//-- the end of the gedcom file.
function append_gedrec($newrec, $fact="", $change_id, $change_type) {
	global $GEDCOM, $gm_changes, $manual_save, $TBLPREFIX, $GEDCOMS, $gm_username;
	
	$ct = preg_match("/0 @(.*)@ (.*)/", $newrec, $match);
	$type = trim($match[2]);
	$xref = get_new_xref($type);
	$newrec = preg_replace("/0 @(.*)@/", "0 @$xref@", $newrec);
	if (userAutoAccept()) update_record($newrec);
	else {
		$sql = "INSERT INTO ".$TBLPREFIX."changes (ch_cid, ch_gid, ch_gedfile, ch_type, ch_user, ch_time, ch_fact, ch_new)";
		$sql .= "VALUES ('".$change_id."', '".$xref."', '".$GEDCOMS[$GEDCOM]["id"]."', '".$change_type."', '".$gm_username."', '".time()."', '".$fact."', '".mysql_real_escape_string($newrec)."')";
		$res = dbquery($sql);
	}
	WriteToLog("Appending new $type record $xref ->" . $gm_username ."<-", "I", "G", $GEDCOM);
	return $xref;
}

//-------------------------------------------- delete_gedrec
//-- this function will delete the gedcom record with
//-- the given $gid
function delete_gedrec($gid, $change_id, $change_type) {
	global $GEDCOMS, $GEDCOM, $manual_save, $TBLPREFIX, $gm_username;
	
	$gid = strtoupper($gid);
	
	// NOTE: Check if there are changes present, if so flag pending changes so they cannot be approved
	if (change_present($gid,true)) {
		$sql = "select ch_cid as cid from ".$TBLPREFIX."changes where ch_gid = '".$gid."' and ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' order by ch_cid ASC";
		$res = dbquery($sql);
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$sqlcid = "update ".$TBLPREFIX."changes set ch_delete = '1' where ch_cid = '".$row["cid"]."'";
			$rescid = dbquery($sqlcid);
		}
	}
	
	// NOTE Check if record exists in the database
	if (!find_gedcom_record($gid)) {
		print "ERROR 4: Could not find gedcom record with xref: $gid. <br />";
		WriteToLog("ERROR 4: Could not find gedcom record with xref: $gid ->" . $gm_username ."<-", "E", "G", $GEDCOM);
		return false;
	}
	else {
		if (userAutoAccept()) {
			update_record($undo, true);
		}
		else {
			$sql = "INSERT INTO ".$TBLPREFIX."changes (ch_cid, ch_gid, ch_gedfile, ch_type, ch_user, ch_time)";
			$sql .= "VALUES ('".$change_id."', '".$gid."', '".$GEDCOMS[$GEDCOM]["id"]."', '".$change_type."', '".$gm_username."', '".time()."')";
			$res = dbquery($sql);
		}
	}
	WriteToLog("Deleting gedcom record $gid ->" . $gm_username ."<-", "I", "G", $GEDCOM);
	//if (!isset($manual_save)) return write_changes();
	return true;
}

//-------------------------------------------- check_gedcom
//-- this function will check a GEDCOM record for valid gedcom format
function check_gedcom($gedrec, $chan=true) {
	global $gm_lang, $DEBUG, $GEDCOM, $gm_username;

	$gedrec = stripslashes($gedrec);
	$ct = preg_match("/0 @(.*)@ (.*)/", $gedrec, $match);
	
	if ($ct==0) {
		print "ERROR 20: Invalid GEDCOM 5.5 format.\n";
		WriteToLog("ERROR 20: Invalid GEDCOM 5.5 format.->" . $gm_username ."<-", "I", "G", $GEDCOM);
		if ($GLOBALS["DEBUG"]) print "<pre>$gedrec</pre>\n";
		return false;
	}
	$gedrec = trim($gedrec);
	if ($chan) {
		$pos1 = strpos($gedrec, "1 CHAN");
		if ($pos1!==false) {
			$pos2 = strpos($gedrec, "\n1", $pos1+4);
			if ($pos2===false) $pos2 = strlen($gedrec);
			$newgedrec = substr($gedrec, 0, $pos1);
			$newgedrec .= "1 CHAN\r\n2 DATE ".date("d M Y")."\r\n";
			$newgedrec .= "3 TIME ".date("H:i:s")."\r\n";
			$newgedrec .= "2 _GMU ".$gm_username."\r\n";
			$newgedrec .= substr($gedrec, $pos2);
			$gedrec = $newgedrec;
		}
		else {
			$newgedrec = "\r\n1 CHAN\r\n2 DATE ".date("d M Y")."\r\n";
			$newgedrec .= "3 TIME ".date("H:i:s")."\r\n";
			$newgedrec .= "2 _GMU ".$gm_username;
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
function print_indi_form($nextaction, $famid, $linenum="", $namerec="", $famtag="CHIL") {
	global $gm_lang, $factarray, $pid, $GM_IMAGE_DIR, $GM_IMAGES, $monthtonum, $WORD_WRAPPED_NOTES;
	global $NPFX_accept, $SPFX_accept, $NSFX_accept, $FILE_FORM_accept, $USE_RTL_FUNCTIONS, $change_type;

	init_calendar_popup();
	print "<form method=\"post\" name=\"addchildform\" onsubmit=\"return checkform();\">\n";
	print "<input type=\"hidden\" name=\"action\" value=\"".$nextaction."\" />\n";
	print "<input type=\"hidden\" name=\"famid\" value=\"".$famid."\" />\n";
	print "<input type=\"hidden\" name=\"pid\" value=\"".$pid."\" />\n";
	print "<input type=\"hidden\" name=\"change_type\" value=\"".$change_type."\" />\n";
	print "<input type=\"hidden\" name=\"famtag\" value=\"".$famtag."\" />\n";
	print "<table class=\"facts_table\">";

	// preset child/father SURN
	$surn = "";
	if (empty($namerec)) {
		$indirec = "";
		if ($famtag=="CHIL" and $nextaction=="addchildaction") {
			$famrec = find_family_record($famid);
			if (empty($famrec)) $famrec = find_gedcom_record($famid);
			$parents = find_parents_in_record($famrec);
			$indirec = find_person_record($parents["HUSB"]);
		}
		if ($famtag=="HUSB" and $nextaction=="addnewparentaction") {
			$indirec = find_person_record($pid);
		}
		$nt = preg_match("/\d SURN (.*)/", $indirec, $ntmatch);
		if ($nt) $surn = $ntmatch[1];
		else {
			$nt = preg_match("/1 NAME (.*)[\/](.*)[\/]/", $indirec, $ntmatch);
			if ($nt) $surn = $ntmatch[2];
		}
		if ($surn) $namerec = "1 NAME  /".trim($surn,"\r\n")."/";
	}
	// handle PAF extra NPFX [ 961860 ]
	$nt = preg_match("/\d NPFX (.*)/", $namerec, $nmatch);
	$npfx=trim(@$nmatch[1]);
	// 1 NAME = NPFX GIVN /SURN/ NSFX
	$nt = preg_match("/\d NAME (.*)/", $namerec, $nmatch);
	$name=@$nmatch[1];
	if (strlen($npfx) and strpos($name, $npfx)===false) $name = $npfx." ".$name;
	add_simple_tag("0 NAME ".$name);
	// 2 NPFX
	add_simple_tag("0 NPFX ".$npfx);
	// 2 GIVN
	$nt = preg_match("/\d GIVN (.*)/", $namerec, $nmatch);
	add_simple_tag("0 GIVN ".@$nmatch[1]);
	// 2 NICK
	$nt = preg_match("/\d NICK (.*)/", $namerec, $nmatch);
	add_simple_tag("0 NICK ".@$nmatch[1]);
	// 2 SPFX
	$nt = preg_match("/\d SPFX (.*)/", $namerec, $nmatch);
	add_simple_tag("0 SPFX ".@$nmatch[1]);
	// 2 SURN
	$nt = preg_match("/\d SURN (.*)/", $namerec, $nmatch);
	add_simple_tag("0 SURN ".@$nmatch[1]);
	// 2 NSFX
	$nt = preg_match("/\d NSFX (.*)/", $namerec, $nmatch);
	add_simple_tag("0 NSFX ".@$nmatch[1]);
	// 2 _HEB
	$nt = preg_match("/\d _HEB (.*)/", $namerec, $nmatch);
	if ($nt>0 || $USE_RTL_FUNCTIONS) {
		add_simple_tag("0 _HEB ".@$nmatch[1]);
	}
	// 2 ROMN
	$nt = preg_match("/\d ROMN (.*)/", $namerec, $nmatch);
	add_simple_tag("0 ROMN ".@$nmatch[1]);

	if ($surn) $namerec = ""; // reset if modified

	if (empty($namerec)) {
		// 2 _MARNM
		add_simple_tag("0 _MARNM");
		// 1 SEX
		if ($famtag=="HUSB") add_simple_tag("0 SEX M");
		else if ($famtag=="WIFE") add_simple_tag("0 SEX F");
		else add_simple_tag("0 SEX");
		// 1 BIRT
		// 2 DATE
		// 2 PLAC
		add_simple_tag("0 BIRT");
		add_simple_tag("0 DATE", "BIRT");
		add_simple_tag("0 PLAC", "BIRT");
		// 1 DEAT
		// 2 DATE
		// 2 PLAC
		add_simple_tag("0 DEAT");
		add_simple_tag("0 DATE", "DEAT");
		add_simple_tag("0 PLAC", "DEAT");
		print "</table>\n";
		//-- if adding a spouse add the option to add a marriage fact to the new family
		if ($nextaction=='addspouseaction' || ($nextaction=='addnewparentaction' && $famid!='new')) {
			print "<br />\n";
			print "<table class=\"facts_table\">";
			add_simple_tag("0 MARR");
			add_simple_tag("0 DATE", "MARR");
			add_simple_tag("0 PLAC", "MARR");
			print "</table>\n";
		}
		print_add_layer("SOUR", 1);
		print_add_layer("NOTE", 1);
		print_add_layer("OBJE", 1);
		print "<input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
	}
	else {
		if ($namerec!="NEW") {
			$gedlines = split("\n", $namerec);	// -- find the number of lines in the record
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
						if ($WORD_WRAPPED_NOTES) $text .= " ";
						$text .= $cmatch[2];
						$i++;
					}
					add_simple_tag($level." ".$type." ".$text);
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
		add_simple_tag("0 _MARNM");
		print "</tr>\n";
		print "</table>\n";
		print_add_layer("SOUR");
		print_add_layer("NOTE");
		print "<input type=\"submit\" value=\"".$gm_lang["save"]."\" /><br />\n";
	}
	print "</form>\n";
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
			alert('<?php print $gm_lang["must_provide"]; print $gm_lang["given_name"]; ?>');
			frm.GIVN.focus();
			return false;
		}
		if (frm.SURN.value=="") {
			alert('<?php print $gm_lang["must_provide"]; print $gm_lang["surname"]; ?>');
			frm.SURN.focus();
			return false;
		}*/
		var fname=frm.NAME.value;
		fname=fname.replace(/ /g,'');
		fname=fname.replace(/\//g,'');
		if (fname=="") {
			alert('<?php print $gm_lang["must_provide"]; print " ".$factarray["NAME"]; ?>');
			frm.NAME.focus();
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php
	// force name expand on form load (maybe optional in a further release...)
	print "<script type='text/javascript'>togglename();</script>";
}

/**
 * generates javascript code for calendar popup in user's language
 *
 * @param string id		form text element id where to return date value
 * @see init_calendar_popup()
 */
function print_calendar_popup($id) {
	global $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;

	// calendar button
	$text = $gm_lang["select_date"];
	if (isset($GM_IMAGES["calendar"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["button"]."\" name=\"img".$id."\" id=\"img".$id."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
	else $Link = $text;
	print "<a href=\"javascript: ".$text."\" onclick=\"cal_toggleDate('caldiv".$id."', '".$id."'); return false;\">";
	print $Link;
	print "</a>\n";
	print "<div id=\"caldiv".$id."\" style=\"position:absolute;visibility:hidden;background-color:white;layer-background-color:white;\"></div>\n";
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
function add_simple_tag($tag, $upperlevel="") {
	global $factarray, $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $MEDIA_DIRECTORY, $TEMPLE_CODES, $STATUS_CODES, $REPO_ID_PREFIX, $SPLIT_PLACES;
	global $assorela, $tags, $emptyfacts, $TEXT_DIRECTION, $confighelpfile, $GM_BASE_DIRECTORY;
	global $NPFX_accept, $SPFX_accept, $NSFX_accept, $FILE_FORM_accept, $upload_count;
	static $tabkey;

	if (!isset($tabkey)) $tabkey = 1;

	// Work around for $emptyfacts being mysteriously unset
	// if (empty($emptyfacts))
     	// $emptyfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","EVEN","MARR","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","OBJE","CHAN","_SEPR","RESI", "DATA", "MAP");

	$largetextfacts = array("TEXT","PUBL","NOTE");
	$subnamefacts = array("NPFX", "GIVN", "NICK", "SPFX", "SURN", "NSFX");

	@list($level, $fact, $value) = explode(" ", $tag);

	// element name : used to POST data
	if ($upperlevel) $element_name=$upperlevel."_".$fact; // ex: BIRT_DATE | DEAT_DATE | ...
	else if ($level==0) $element_name=$fact; // ex: OCCU
	else $element_name="text[]";

	// element id : used by javascript functions
	if ($level==0) $element_id=$fact; // ex: NPFX | GIVN ...
	else $element_id=$fact.floor(microtime()*1000000); // ex: SOUR56402
	if ($upperlevel) $element_id=$upperlevel."_".$fact; // ex: BIRT_DATE | DEAT_DATE ...

	// field value
	$islink = (substr($value,0,1)=="@" && substr($value,0,2)!="@#");
	if ($islink) $value=trim($value, " @");
	else $value=trim(substr($tag, strlen($fact)+3));
	
	// rows & cols
	$rows=1;
	$cols=60;
	if ($islink) $cols=10;
	if ($fact=="FORM") $cols=5;
	if ($fact=="DATE" or $fact=="TIME" or $fact=="TYPE") $cols=20;
	if ($fact=="LATI" or $fact=="LONG") $cols=12;
	if (in_array($fact, $subnamefacts)) $cols=25;
	if (in_array($fact, $largetextfacts)) { $rows=10; $cols=70; }
	if ($fact=="ADDR") $rows=5;
	if ($fact=="REPO") $cols = strlen($REPO_ID_PREFIX) + 4;

	// label
	$style="";
	print "<tr id=\"".$element_id."_tr\" ";
	if (in_array($fact, $subnamefacts)) print " style=\"display:none;\""; // hide subname facts
	print " >\n";
	print "<td class=\"shade2 $TEXT_DIRECTION\">";
	// help link
	if (!in_array($fact, $emptyfacts)) {
		if ($fact=="DATE") print_help_link("def_gedcom_date_help", "qm", "date");
		else if ($fact=="RESN") print_help_link($fact."_help", "qm");
		else print_help_link("edit_".$fact."_help", "qm");
	}
	if ($GLOBALS["DEBUG"]) print $element_name."<br />\n";
	if (isset($gm_lang[$fact])) print $gm_lang[$fact];
	else if (isset($factarray[$fact])) print $factarray[$fact];
	else print $fact;
	print "\n";
	
	// tag level
	if ($level>0) {
		if ($fact=="TEXT") {
			print "<input type=\"hidden\" name=\"glevels[]\" value=\"".($level-1)."\" />";
			print "<input type=\"hidden\" name=\"islink[]\" value=\"0\" />";
			print "<input type=\"hidden\" name=\"tag[]\" value=\"DATA\" />";
			print "<input type=\"hidden\" name=\"text[]\" value=\" \" />";
		}
		print "<input type=\"hidden\" name=\"glevels[]\" value=\"".$level."\" />\n";
		print "<input type=\"hidden\" name=\"islink[]\" value=\"".($islink)."\" />\n";
		print "<input type=\"hidden\" name=\"tag[]\" value=\"".$fact."\" />\n";
	}
	print "\n</td>";
	
	// value
	print "<td class=\"shade1\">\n";
	
	// retrieve linked NOTE
	if ($fact=="NOTE" && $islink) {
		$noteid = $value;
		print "<input type=\"hidden\" name=\"text[]\" value=\"".$noteid."\" />\n";
		$noterec = find_gedcom_record($noteid);
		$nt = preg_match("/0 @$value@ NOTE (.*)/", $noterec, $n1match);
		if ($nt!==false) $value=trim(strip_tags(@$n1match[1].get_cont(1, $noterec)));
		$element_name="NOTE[".$noteid."]";
	}
	if (in_array($fact, $emptyfacts) && empty($value)) {
		print "<input type=\"hidden\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".$value."\" />";
	}
	else if ($fact=="TEMP") {
		print "<select tabindex=\"".$tabkey."\" name=\"".$element_name."\" >\n";
		print "<option value=''>".$gm_lang["no_temple"]."</option>\n";
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
		?>
		<script type="text/javascript">
		<!--
		function update_RESN(resn_val) {
			if (resn_val=='none') resn_val='';
			document.getElementById("<?php print $element_id?>").value=resn_val;	
		}
		//-->
		</script>
		<?php
		print "<input type=\"hidden\" id=\"".$element_id."\" name=\"".$element_name."\" />\n";
		print "<table><tr valign=\"top\">\n";
		foreach (array("none", "locked", "privacy", "confidential") as $resn_index => $resn_val) {
			if ($resn_val=="none") $resnv=""; else $resnv=$resn_val;
			print "<td><input tabindex=\"".$tabkey."\" type=\"radio\" name=\"RESN_radio\"  onclick=\"update_RESN('".$resn_val."')\"";
			print " value=\"".$resnv."\"";
			if ($value==$resnv) print " checked=\"checked\"";
			print " /><small>".$gm_lang[$resn_val]."</small>";
			print "</td>\n";
		}
		print "</tr></table>\n";
	}
	else if ($fact=="_PRIM" or $fact=="_THUM") {
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" >\n";
		print "<option value=\"\"></option>\n";
		print "<option value=\"Y\"";
		if ($value=="Y") print " selected=\"selected\"";
		print ">".$gm_lang["yes"]."</option>\n";
		print "<option value=\"N\"";
		if ($value=="N") print " selected=\"selected\"";
		print ">".$gm_lang["no"]."</option>\n";
		print "</select>\n";
	}
	else if ($fact=="SEX") {
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\">\n<option value=\"M\"";
		if ($value=="M") print " selected=\"selected\"";
		print ">".$gm_lang["male"]."</option>\n<option value=\"F\"";
		if ($value=="F") print " selected=\"selected\"";
		print ">".$gm_lang["female"]."</option>\n<option value=\"U\"";
		if ($value=="U" || empty($value)) print " selected=\"selected\"";
		print ">".$gm_lang["unknown"]."</option>\n</select>\n";
	}
	else if ($fact == "TYPE" && $level == '3') {?>
		<select name="text[]">
		<option selected="selected" value=""> <?php print $gm_lang["choose"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_audio"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_audio"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_book"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_book"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_card"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_card"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_electronic"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_electronic"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_fiche"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_fiche"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_film"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_film"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_magazine"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_magazine"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_manuscript"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_manuscript"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_map"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_map"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_newspaper"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_newspaper"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_photo"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_photo"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_tombstone"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_tombstone"]; ?> </option>
		<option <?php if ($value == $gm_lang["type_video"]) print "selected=\"selected\""; ?>> <?php print $gm_lang["type_video"]; ?> </option>
		</select>
		<?php
	}
	else if ($fact=="QUAY") {
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
		if ($rows>1) print "<textarea tabindex=\"".$tabkey."\" id=\"".$element_id."\" name=\"".$element_name."\" rows=\"".$rows."\" cols=\"".$cols."\">".$value."</textarea>\n";
		// text
		else {
			print "<input tabindex=\"".$tabkey."\" type=\"text\" id=\"".$element_id."\" name=\"".$element_name."\" value=\"".htmlspecialchars($value)."\" size=\"".$cols."\" dir=\"ltr\"";
			if ($fact=="NPFX") print " onkeyup=\"wactjavascript_autoComplete(npfx_accept,this,event)\" autocomplete=\"off\" ";
			if (in_array($fact, $subnamefacts)) print " onchange=\"updatewholename();\"";
			if ($fact=="DATE") print " onblur=\"valid_date(this);\"";
			print " />\n";
		}
		// split PLAC
		if ($fact=="PLAC") {
			print "<div id=\"".$element_id."_pop\" style=\"display: inline;\">\n";
			print_specialchar_link($element_id, false);
			print_findplace_link($element_id);
			print "</div>\n";
			if ($SPLIT_PLACES) {
				if (!function_exists("print_place_subfields")) require("includes/functions_places.php");
				print_place_subfields($element_id);
			}
		}
		else if ($cols>20 and $fact!="NPFX") print_specialchar_link($element_id, false);
	}
	// MARRiage TYPE : hide text field and show a selection list
	if ($fact=="TYPE" and $tags[0]=="MARR") {
		print "<script type='text/javascript'>";
		print "document.getElementById('".$element_id."').style.display='none'";
		print "</script>";
		print "<select tabindex=\"".$tabkey."\" id=\"".$element_id."_sel\" onchange=\"document.getElementById('".$element_id."').value=this.value;\" >\n";
		foreach (array("Unknown", "Civil", "Religious", "Partners") as $indexval => $key) {
			if ($key=="Unknown") print "<option value=\"\"";
			else print "<option value=\"".$key."\"";
			$a=strtolower($key);
			$b=strtolower($value);
			if (@strpos($a, $b)!==false or @strpos($b, $a)!==false) print " selected=\"selected\"";
			print ">".$factarray["MARR_".strtoupper($key)]."</option>\n";
		}
		print "</select>";
	}

	// popup links
	if ($fact=="DATE") print_calendar_popup($element_id);
	if ($fact=="FAMC") print_findfamily_link($element_id);
	if ($fact=="FAMS") print_findfamily_link($element_id);
	if ($fact=="ASSO") print_findindi_link($element_id);
	if ($fact=="FILE") print_findmedia_link($element_id);
	if ($fact=="OBJE") print_findobject_link($element_id);
	if ($fact=="SOUR") {
		print_findsource_link($element_id);
		print_addnewsource_link($element_id);
	}
	if ($fact=="REPO") {
		print_findrepository_link($element_id);
		print_addnewrepository_link($element_id);
	}

	// current value
	if ($fact=="DATE") print get_changed_date($value);
	if ($fact=="ASSO" && $value) print " ".get_person_name($value)." (".$value.")";
	if ($fact=="SOUR" && $value) print " ".get_source_descriptor($value)." (".$value.")";

	// pastable values
	if ($fact=="NPFX") {
		$text = $gm_lang["autocomplete"];
		if (isset($GM_IMAGES["autocomplete"]["button"])) $Link = "<img id=\"".$element_id."_spec\" name=\"".$element_id."_spec\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["autocomplete"]["button"]."\"  alt=\"".$text."\"  title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print "&nbsp;".$Link;
	}
	if ($fact=="SPFX") print_autopaste_link($element_id, $SPFX_accept);
	if ($fact=="NSFX") print_autopaste_link($element_id, $NSFX_accept);
	if ($fact=="FORM") print_autopaste_link($element_id, $FILE_FORM_accept, false);

	// split NAME
	if ($fact=="NAME") {
		print "&nbsp;<a href=\"javascript: ".$gm_lang["show_details"]."\" onclick=\"togglename(); return false;\"><img id=\"".$element_id."_plus\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /></a>\n";
		print "<a href=\"javascript: ".$gm_lang["show_details"]."\" onclick=\"togglename(); return false;\"><img style=\"display:none;\" id=\"".$element_id."_minus\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /></a>\n";
	}
	
	print "</td></tr>\n";
	$tabkey++;
}

/**
 * prints collapsable fields to add ASSO/RELA, SOUR, OBJE ...
 *
 * @param string $tag		Gedcom tag name
 */
function print_add_layer($tag, $level=2) {
	global $factarray, $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES;
	global $MEDIA_DIRECTORY;

	if ($tag=="SOUR") {
		//-- Add new source to fact
		print "<a href=\"#\" onclick=\"return expand_layer('newsource');\"><img id=\"newsource_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_source"]."</a>";
		print_help_link("edit_add_SOUR_help", "qm");
		print "<br />";
		print "<div id=\"newsource\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 SOUR
		add_simple_tag("$level SOUR @");
		// 3 PAGE
		add_simple_tag(($level+1)." PAGE");
		// 3 DATA
		// 4 TEXT
		add_simple_tag(($level+2)." TEXT");
		print "</table></div>";
	}
	if ($tag=="ASSO") {
		//-- Add a new ASSOciate
		print "<a href=\"#\" onclick=\"return expand_layer('newasso');\"><img id=\"newasso_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_asso"]."</a>";
		print_help_link("edit_add_ASSO_help", "qm");
		print "<br />";
		print "<div id=\"newasso\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 ASSO
		add_simple_tag(($level)." ASSO @");
		// 3 RELA
		add_simple_tag(($level+1)." RELA");
		// 3 NOTE
		add_simple_tag(($level+1)." NOTE");
		print "</table></div>";
	}
	if ($tag=="NOTE") {
		//-- Add new note to fact
		print "<a href=\"#\" onclick=\"return expand_layer('newnote');\"><img id=\"newnote_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_note"]."</a>";
		print_help_link("edit_add_NOTE_help", "qm");
		print "<br />\n";
		print "<div id=\"newnote\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 NOTE
		add_simple_tag(($level)." NOTE");
		print "</table></div>";
	}
	if ($tag=="OBJE") {
		//-- Add new obje to fact
		print "<a href=\"#\" onclick=\"return expand_layer('newobje');\"><img id=\"newobje_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" title=\"\" /> ".$gm_lang["add_obje"]."</a>";
		print_help_link("add_media_help", "qm");
		print "<br />";
		print "<div id=\"newobje\" style=\"display: none;\">\n";
		print "<table class=\"facts_table\">\n";
		// 2 OBJE
		add_simple_tag(($level)." OBJE");
		// 3 FORM
		add_simple_tag(($level+1)." FORM");
		// 3 FILE
		add_simple_tag(($level+1)." FILE");
		// 3 TITL
		add_simple_tag(($level+1)." TITL");
		if ($level==1) {
			// 3 _PRIM
			add_simple_tag(($level+1)." _PRIM");
			// 3 _THUM
			add_simple_tag(($level+1)." _THUM");
		}
		print "</table></div>";
	}
}
/**
 * Add Debug Log
 *
 * This function checks the if the global $DEBUG
 * variable is true and adds debugging information
 * to the log file
 * @param string $logstr	the string to add to the log
 */
function addDebugLog($logstr) {
	global $DEBUG;
	if ($DEBUG) WriteToLog($logstr, "I", "G", $GEDCOM);
}

/**
 * Add new gedcom lines from interface update arrays
 * @param string $newged	the new gedcom record to add the lines to
 * @return string	The updated gedcom record
 */
function handle_updates($newged) {
	global $glevels, $islink, $tag, $uploaded_files, $text, $NOTE;
	
	// NOTE: Cleanup text fields
	foreach ($text as $key => $line) {
		$text[$key] = trim($line);
	}
	for($j=0; $j<count($glevels); $j++) {
		//-- update external note records first
		if (($islink[$j])&&($tag[$j]=="NOTE")) {
			if (empty($NOTE[$text[$j]])) {
				delete_gedrec($text[$j]);
				$text[$j] = "";
			}
			else {
				$noterec = find_gedcom_record($text[$j]);
				$newnote = "0 @$text[$j]@ NOTE\r\n";
				$newline = "1 CONC ".$NOTE[$text[$j]];
				$newlines = preg_split("/\r?\n/", $newline);
				for($k=0; $k<count($newlines); $k++) {
					if ($k>0) $newlines[$k] = "1 CONT ".$newlines[$k];
					if (strlen($newlines[$k])>255) {
						while(strlen($newlines[$k])>255) {
							$newnote .= substr($newlines[$k], 0, 255)."\r\n";
							$newlines[$k] = substr($newlines[$k], 255);
							$newlines[$k] = "1 CONC ".$newlines[$k];
						}
						$newnote .= trim($newlines[$k])."\r\n";
					}
					else {
						$newnote .= trim($newlines[$k])."\r\n";
					}
				}
				$notelines = preg_split("/\r?\n/", $noterec);
				for($k=1; $k<count($notelines); $k++) {
					if (preg_match("/1 CON[CT] /", $notelines[$k])==0) $newnote .= trim($notelines[$k])."\r\n";
				}
				if ($GLOBALS["DEBUG"]) print "<pre>$newnote</pre>";
				replace_gedrec($text[$j], $newnote);
			}
		} //-- end of external note handling code
		
		//print $glevels[$j]." ".$tag[$j];
		//-- for facts with empty values they must have sub records
		//-- this section checks if they have subrecords
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
		//-- if the value is not empty then write the line to the gedcom record
		if ((!empty($text[$j]))||($pass==true)) {
			if ($islink[$j]) $text[$j]="@".$text[$j]."@";
			$newline = $glevels[$j]." ".$tag[$j];
			
			// NOTE: Check if the new record already contains this line, if so, empty the new record
			if (trim($newged) == trim($newline)) $newged = "";
			
			//-- check and translate the incoming dates
			if ($tag[$j]=="DATE" && !empty($text[$j])) {
				$text[$j] = check_input_date($text[$j]);
			}
			// NOTE: Check if it is a pointer record
			if (!empty($text[$j]) && $newline == "2 SOUR" && !stristr($text[$j], "@")) $newline .= " @".$text[$j]."@";
			else if (!empty($text[$j]) && $newline == "2 OBJE" && !stristr($text[$j], "@")) $newline .= " @".$text[$j]."@";
			else if (!empty($text[$j]) && $newline == "1 REPO" && !stristr($text[$j], "@")) $newline .= " @".$text[$j]."@";
			else if (!empty($text[$j])) $newline .= " ".$text[$j];
			
			//-- convert returns to CONT lines and break up lines longer than 255 chars
			$newlines = preg_split("/\r?\n/", $newline);
			for($k=0; $k<count($newlines); $k++) {
				if ($k>0) $newlines[$k] = ($glevels[$j]+1)." CONT ".$newlines[$k];
				if (strlen($newlines[$k])>255) {
					while(strlen($newlines[$k])>255) {
						$newged .= substr($newlines[$k], 0, 255)."\r\n";
						$newlines[$k] = substr($newlines[$k], 255);
						$newlines[$k] = ($glevels[$j]+1)." CONC ".$newlines[$k];
					}
					$newged .= trim($newlines[$k])."\r\n";
				}
				else {
					$newged .= trim($newlines[$k])."\r\n";
				}
			}
		}
	}
	return $newged;
}

/**
 * check the given date that was input by a user and convert it
 * to proper gedcom date if possible
 * @author John Finlay
 * @param string $datestr	the date input by the user
 * @return string	the converted date string
 */
function check_input_date($datestr) {
	$date = parse_date($datestr);
	//print_r($date);
	if ((count($date)==1)&&empty($date[0]['ext'])&&!empty($date[0]['month'])) {
		$datestr = $date[0]['day']." ".$date[0]['month']." ".$date[0]['year'];
	}
	return $datestr;
}

function print_quick_resn($name) {
	global $SHOW_QUICK_RESN, $align, $factarray, $gm_lang, $tabkey;
	
	if ($SHOW_QUICK_RESN) {
		print "<tr><td class=\"shade2\">";
		print_help_link("RESN_help", "qm");
		print $factarray["RESN"]; 
		print "</td>\n";
		print "<td class=\"shade1\" colspan=\"3\">\n";
		print "<select name=\"$name\" tabindex=\"".$tabkey."\" ><option value=\"\"></option><option value=\"confidential\"";
		$tabkey++;
		print ">".$gm_lang["confidential"]."</option><option value=\"locked\"";
		print ">".$gm_lang["locked"]."</option><option value=\"privacy\"";
		print ">".$gm_lang["privacy"]."</option>";
		print "</select>\n";
		print "</td>\n";
		print "</tr>\n";
	}
}
/**
 * Print an edit form for facts or records
 *
 * This function prints an edit form based on the fact or record that is being
 * edited. The structures hold the table layout. The details only have table rows.
 * The add_simple_tag only returns a table row.
 *
 * @author	Genmod Development Team
 * @param		string	$gid		The record being edited. Empty if it is a new record
 * @param		string	$fact	The fact to be edited
 */
function print_form($pid="", $fact) {
	global $gm_lang, $TEXT_DIRECTION, $GEDCOM, $factarray, $GM_IMAGE_DIR, $GM_IMAGES;
	
	// NOTE: Get the record for the person/family
	$gedrec = "";
	if (id_type($pid) == "OBJE") $gedrec = find_media_record($pid);
	else $gedrec = find_gedcom_record($pid);
	
	switch ($fact) {
		case "BIRT" :
			print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["birth"]."</td></tr>";
			$subrecord = get_sub_record(1, $fact, $gedrec);
			event_detail(1, $subrecord);
			
			// NOTE: n FAMC
			// NOTE: Family record
			if ($subrecord == "") $gedfamc = "FAMC";
			else {
				$gedfamc = get_sub_record(2, "FAMC", $subrecord);
				if (empty($gedfamc)) $gedfamc = "FAMC";
			}
			add_simple_tag("2 $gedfamc");
			print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["add_fact"]."</td></tr>";
			add_simple_tag("2 SOUR ");
			add_simple_tag("2 OBJE ");
			print "<tr><td class=\"topbottombar\" colspan=\"2\"><input type=\"submit\" value=\"".$gm_lang["save"]."\" /></td></tr>\n";
			break;
	}
}

/**
 * Print the event details for an edit form
 *
 * <Long description of your function. 
 * What does it do?
 * How does it work?
 * All that goes into the long description>
 *
 * @author	Genmod Development Team
 * @param		int		$level	Level of fact the event details are being added to
 * @param		string	$gedrec	The gedcom record in case the fact is being edited
 */
function event_detail($level, $gedrec) {
	if ($gedrec == "") {
		add_simple_tag(($level+1)." TYPE");
		add_simple_tag(($level+1)." DATE");
		add_simple_tag(($level+1)." PLAC");
		add_simple_tag(($level+1)." ADDR");
		add_simple_tag(($level+1)." AGNC");
		add_simple_tag(($level+1)." RELI");
		add_simple_tag(($level+1)." CAUS");
		add_simple_tag(($level+1)." RESN");
		add_simple_tag(($level+1)." NOTE ");
		add_simple_tag(($level+1)." SOUR");
		add_simple_tag(($level+1)." OBJE");
	}
	else {
		// NOTE: n TYPE
		// NOTE: EVENT_OR_FACT_CLASSIFICATION
		$gedtype = get_sub_record(($level+1), "TYPE", $gedrec);
		if (empty($gedtype)) $gedtype = "TYPE";
		add_simple_tag(($level+1)." ".$gedtype);
		
		// NOTE: n DATE
		// NOTE: DATE_VALUE
		$geddate = get_sub_record(($level+1), "DATE", $gedrec);
		if (empty($geddate)) $geddate = "DATE";
		add_simple_tag(($level+1)." ".$geddate);
		
		// NOTE: n PLAC
		// NOTE: PLACE_STRUCTURE
		$gedplac = get_sub_record(($level+1), "PLAC", $gedrec);
		if (empty($gedplac)) $gedplac = "PLAC";
		add_simple_tag(($level+1)." ".$gedplac);
		
		// NOTE: n ADDR
		// NOTE: ADDRESS_STRUCTURE
		$gedaddr = get_sub_record(($level+1), "ADDR", $gedrec);
		if (empty($gedaddr)) $gedaddr = "ADDR";
		add_simple_tag(($level+1)." ".$gedaddr);
		
		// NOTE: n AGNC
		// NOTE: RESPONSIBLE_AGENCY
		$gedagnc = get_sub_record(($level+1), "AGNC", $gedrec);
		if (empty($gedagnc)) $gedagnc = "AGNC";
		add_simple_tag(($level+1)." ".$gedagnc);
		
		// NOTE: n RELI
		// NOTE: RELIGIOUS_AFFILIATION
		$gedreli = get_sub_record(($level+1), "RELI", $gedrec);
		if (empty($gedreli)) $gedreli = "RELI";
		add_simple_tag(($level+1)." ".$gedreli);
		
		// NOTE: n CAUS
		// NOTE: CAUSE_OF_EVENT
		$gedcaus = get_sub_record(($level+1), "CAUS", $gedrec);
		if (empty($gedcaus)) $gedcaus = "CAUS";
		add_simple_tag(($level+1)." ".$gedcaus);
		
		// NOTE: n RESN
		// NOTE: RESTRICTION_NOTICE
		$gedresn = get_sub_record(($level+1), "RESN", $gedrec);
		if (empty($gedresn)) $gedresn = "RESN";
		add_simple_tag(($level+1)." ".$gedresn);
		
		// NOTE: n NOTE
		// NOTE: NOTE_STRUCTURE
		$gednote = get_gedcom_value("NOTE", ($level+1), $gedrec);
		if (empty($gednote)) $gednote = "";
		add_simple_tag(($level+1)." NOTE ".$gednote);
		
		// NOTE: n SOUR
		// NOTE: SOURCE_CITATION
		source_citation($level+1, $gedrec);
		
		// NOTE: n OBJE
		// NOTE: MULTIMEDIA_LINK
		preg_match_all("/".($level+1)."\sOBJE\s@(.*)@/", $gedrec, $gedobje);
		if (count($gedobje[0]) == 0) $gedobje = "OBJE";
		if (is_array($gedobje)) {
			foreach ($gedobje[1] as $key => $obje) {
				add_simple_tag(($level+1)." OBJE ".$obje);
			}
		}
		else add_simple_tag(($level+1)." ".$gedobje);
	}
}

function source_citation($level, $gedrec) {
	if ($gedrec == "") {
		add_simple_tag(($level+1)." PAGE");
		add_simple_tag(($level+1)." EVEN");
		add_simple_tag(($level+2)." ROLE");
		add_simple_tag(($level+1)." DATA");
		add_simple_tag(($level+2)." DATE");
		add_simple_tag(($level+2)." TEXT");
		add_simple_tag(($level+1)." OBJE");
		add_simple_tag(($level+1)." NOTE");
		add_simple_tag(($level+1)." QUAY");
	}
	else {
		$count_source = preg_match_all("/".($level)."\sSOUR\s@(.*)@/", $gedrec, $gedsour);
		if (count($gedsour[0]) == 0) $gedsour = "SOUR";
		// NOTE: Print all source references
		if (is_array($gedsour)) {
			for ($get_facts=1; $get_facts < ($count_source+1); $get_facts++) {
				$sour_fact = get_sub_record(2, "$level SOUR", $gedrec, $get_facts);
				if ($gedsour[0][$get_facts-1] == trim($sour_fact)) add_simple_tag($level." SOUR ".$gedsour[1][$get_facts-1]);
				else {
					// NOTE: Get array with all facts
					$sour_data = preg_match_all("/\d\s([A-Z]{4})\s(.*)/", trim($sour_fact), $geddata);
					
					// NOTE: Add Source
					add_simple_tag($level." SOUR ".$gedsour[1][$get_facts-1]);
					
					// NOTE: Add Page
					if (in_array("PAGE", $geddata[1])) {
						$key = array_keys($geddata[1], "PAGE");
						add_simple_tag(($level+1)." PAGE ".$geddata[2][$key[0]]);
					}
					else add_simple_tag(($level+1)." PAGE");
						
					
					// NOTE: Add Role
					if (in_array("EVEN", $geddata[1])) {
						$key = array_keys($geddata[1], "EVEN");
						add_simple_tag(($level+1)." EVEN ".$geddata[2][$key[0]]);
						if (in_array("ROLE", $geddata[1])) {
							$key = array_keys($geddata[1], "ROLE");
							add_simple_tag(($level+1)." ROLE ".$geddata[2][$key[0]]);
						}
						else add_simple_tag(($level+1)." ROLE");
					}
					else {
						add_simple_tag(($level+1)." EVEN");
						add_simple_tag(($level+1)." ROLE");
					}
					
					// NOTE: Add Data
					if (in_array("DATA", $geddata[1])) {
						$key = array_keys($geddata[1], "DATA");
						add_simple_tag(($level+1)." DATA ".$geddata[2][$key[0]]);
						if (in_array("DATE", $geddata[1])) {
							$key = array_keys($geddata[1], "DATE");
							add_simple_tag(($level+2)." DATE ".$geddata[2][$key[0]]);
						}
						else add_simple_tag(($level+2)." DATE");
						if (in_array("TEXT", $geddata[1])) {
							$key = array_keys($geddata[1], "TEXT");
							add_simple_tag(($level+2)." TEXT ".$geddata[2][$key[0]]);
						}
						else add_simple_tag(($level+2)." TEXT");
					}
					
					// NOTE: Media
					if (in_array("OBJE", $geddata[1])) {
						$key = array_keys($geddata[1], "OBJE");
						add_simple_tag(($level+1)." OBJE ".str_replace("@", "", $geddata[2][$key[0]]));
					}
					else add_simple_tag(($level+1)." OBJE");
					
					// NOTE: Add Note
					if (in_array("NOTE", $geddata[1])) {
						$key = array_keys($geddata[1], "NOTE");
						$text = $geddata[2][$key[0]];
						$text .= get_cont($level+2, $sour_fact);
						$text = preg_replace("/\<br \/\>/", "", trim($text));
						add_simple_tag(($level+1)." NOTE ".$text);
					}
					
					// NOTE: Quality of information
					if (in_array("QUAY", $geddata[1])) {
						$key = array_keys($geddata[1], "QUAY");
						add_simple_tag(($level+1)." QUAY ".$geddata[2][$key[0]]);
					}
					else add_simple_tag(($level+1)." QUAY");
				}
			}
		}
		else add_simple_tag($level." ".$gedsour);
	}
}
function show_media_form($pid, $action="newentry", $change_type="add_media") {
	global $GEDCOM, $gm_lang, $TEXT_DIRECTION, $MEDIA_ID_PREFIX, $GEDCOMS, $WORD_WRAPPED_NOTES;
	// NOTE: add a table and form to easily add new values to the table
	print "<form method=\"post\" name=\"newmedia\" action=\"addmedia.php\" enctype=\"multipart/form-data\">\n";
	print "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
	print "<input type=\"hidden\" name=\"change_type\" value=\"$change_type\" />\n";
	print "<input type=\"hidden\" name=\"ged\" value=\"$GEDCOM\" />\n";
	if (isset($pid)) print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
	print "<table class=\"facts_table center $TEXT_DIRECTION\">\n";
	print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["add_media"]."</td></tr>";
	if ($pid == "") {
		print "<tr><td class=\"shade2\">".$gm_lang["add_fav_enter_id"]."</td>";
		print "<td class=\"shade1\"><input type=\"text\" name=\"gid\" id=\"gid\" size=\"3\" value=\"\" />";
		print_findindi_link("gid");
		print_findfamily_link("gid");
		print_findsource_link("gid");
		print "</td></tr>";
	}
	if (id_type($pid) == "OBJE") $gedrec = find_media_record($pid);
	else {
		$gedrec = "";
		$gedrec = find_gedcom_record($pid);
	}
	// 0 OBJE
	// 1 FILE
	if ($gedrec == "") $gedfile = "FILE";
	else {
		$gedfile = get_sub_record(1, "FILE", $gedrec);
		if (empty($gedfile)) $gedfile = "FILE";
	}
	add_simple_tag("1 $gedfile");
	// Box for user to choose to upload file from local computer
	print "<tr><td class=\"shade2\">&nbsp;</td><td class=\"shade1\"><input type=\"file\" name=\"picture\" size=\"60\"></td></tr>";
	// Box for user to choose the folder to store the image
	print "<tr><td class=\"shade2\">".$gm_lang["folder"]."</td><td class=\"shade1\"><input type=\"text\" name=\"folder\" size=\"60\"></td></tr>";
	// 2 FORM
	if ($gedrec == "") $gedform = "FORM";
	else {
		$gedform = get_sub_record(2, "FORM", $gedrec);
		if (empty($gedform)) $gedform = "FORM";
	}
	add_simple_tag("2 $gedform");
	// 3 TYPE
	if ($gedrec == "") $gedtype = "TYPE";
	else {
		$types = preg_match("/3\sTYPE(.*)\r\n/", $gedrec, $matches);
		if (empty($matches[0])) $gedtype = "TYPE";
		else $gedtype = "TYPE ".trim($matches[1]);
	}
	add_simple_tag("3 $gedtype");
	// 2 TITL
	if ($gedrec == "") $gedtitl = "TITL";
	else {
		$gedtitl = get_sub_record(2, "TITL", $gedrec);
		if (empty($gedtitl)) $gedtitl = "TITL";
	}
	add_simple_tag("2 $gedtitl");
	// 1 REFN
	if ($gedrec == "") $gedrefn = "REFN";
	else {
		$gedrefn = get_sub_record(1, "REFN", $gedrec);
		if (empty($gedrefn)) $gedrefn = "REFN";
	}
	add_simple_tag("1 $gedrefn");
	// 2 TYPE
	if ($gedrec == "") $gedtype2 = "TYPE";
	else {
		$types = preg_match("/2 TYPE(.*)\r\n/", $gedrec, $matches);
		if (empty($matches[0])) $gedtype2 = "TYPE";
		else $gedtype2 = "TYPE ".trim($matches[1]);
	}
	add_simple_tag("2 $gedtype2");
	// 1 RIN
	if ($gedrec == "") $gedrin = "RIN";
	else {
		$gedrin = get_sub_record(1, "RIN", $gedrec);
		if (empty($gedrin)) $gedrin = "RIN";
	}
	add_simple_tag("1 $gedrin");
	// 1 NOTE
	if ($gedrec == "") $text = "NOTE";
	else {
		$gednote = get_sub_record(1, "NOTE", $gedrec);
		$gedlines = split("\r\n", $gednote);
		$level = 1;
		$i = 0;
		$text = preg_replace("/NOTE\s/", "", $gedlines[0]);
		if (count($gedlines) > 1) {
			while(($i+1<count($gedlines))&&(preg_match("/".($level+1)." (CON[CT])\s?(.*)/", $gedlines[$i+1], $cmatch)>0)) {
				$iscont=true;
				if ($cmatch[1]=="CONT") $text.="\n";
				else if ($WORD_WRAPPED_NOTES) $text .= " ";
				$text .= $cmatch[2];
				$i++;
			}
			$text = "NOTE ".$text;
		}
		if (empty($gednote)) $text = "NOTE";
	}
	add_simple_tag("1 $text");
	// 1 SOUR
	if ($gedrec == "") $gedsour = "SOUR";
	else {
		$gedsour = get_sub_record(1, "SOUR", $gedrec);
		if (empty($gedsour)) $gedsour = "SOUR";
	}
	add_simple_tag("1 $gedsour");
	// 2 _PRIM
	if ($gedrec == "") $gedprim = "_PRIM";
	else {
		$gedprim = get_sub_record(1, "_PRIM", $gedrec);
		if (empty($gedprim)) $gedprim = "_PRIM";
	}
	add_simple_tag("1 $gedprim");
	// 2 _THUM
	if ($gedrec == "") $gedthum = "_THUM";
	else {
		$gedthum = get_sub_record(1, "_THUM", $gedrec);
		if (empty($gedthum)) $gedthum = "_THUM";
	}
	add_simple_tag("1 $gedthum");
	print "<tr><td class=\"center\" colspan=\"2\"><input type=\"submit\" value=\"".$gm_lang["add_media_button"]."\" /></td></tr>\n";
	print "</table>\n";
	print "</form>\n";
}
?>