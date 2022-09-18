<?php
/**
 * Database functions file
 *
 * This file implements the datastore functions necessary for Genmod to use an SQL database as its
 * datastore. This file also implements array caches for the database tables.  Whenever data is
 * retrieved from the database it is stored in a cache.  When a database access is requested the
 * cache arrays are checked first before querying the database.
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
 * @version $Id: functions_plot.php,v 1.2 2009/01/11 09:41:04 sjouke Exp $
 * @package Genmod
 * @subpackage DB
 */
if (strstr($_SERVER["SCRIPT_NAME"],"functions")) {
	require "../intrusion.php";
}

function GetPlotData($gedcomid="") {
	global $TBLPREFIX, $GEDCOMID, $DBCONN;
	global $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $nrman, $nrvrouw;
	
	if (empty($gedcomid)) $gedcomid = $GEDCOMID;
	
	// check if we must update the cache anyway
	$sql = "SELECT gc_last_plotdata FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".get_gedcom_from_id($gedcomid)."'";
	$res = NewQuery($sql);
	if ($res->NumRows() == 0) $cache_load = false;
	else {
		$row = $res->FetchRow();
		if ($row[0] !='0') {
			$cache_load = true;
		}
		else {
			$sql = "DELETE FROM ".$TBLPREFIX."pdata WHERE pd_file='".$gedcomid."'";
			$res = NewQuery($sql);
			$cache_load = false;
		}
	}
	
	if ($cache_load) {
		$sql = "SELECT pd_data FROM ".$TBLPREFIX."pdata WHERE pd_file='".$gedcomid."' ORDER BY pd_id";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) $cache_load = false;
		else {
			$str = "";
			while ($row = $res->FetchRow()) {
				$str .= $row[0];
			}
			$pdata = unserialize($str);
			$nrfam = $pdata["nrfam"];
			$famgeg = $pdata["famgeg"];
			$nrpers = $pdata["nrpers"];
			$persgeg = $pdata["persgeg"];
			$key2ind = $pdata["key2ind"];
			$nrman = $pdata["nrman"];
			$nrvrouw = $pdata["nrvrouw"];
		}
	}
	
	if (!$cache_load) {
		GetPlotPerson();
		unset($GLOBALS['indilist']);
		
		GetPlotFamily();
		unset($GLOBALS['famlist']);
		
		CompletePlotData();
		unset($GLOBALS['indilist']);
		unset($GLOBALS['famlist']);
		
		$pdata = array();
		$pdata["nrfam"] = $nrfam;
		$pdata["famgeg"] = $famgeg;
		$pdata["nrpers"] = $nrpers;
		$pdata["persgeg"] = $persgeg;
		$pdata["key2ind"] = $key2ind;
		$pdata["nrman"] = $nrman;
		$pdata["nrvrouw"] = $nrvrouw;
		$sql = "DELETE FROM ".$TBLPREFIX."pdata WHERE pd_file='".$gedcomid."'";
		$res = NewQuery($sql);
		$str = serialize($pdata);
		$l = strlen($str);
		$start = 0;
		while ($start <= $l) {
			$data = $DBCONN->EscapeQuery(substr($str, $start, 65535));
			$sql = "INSERT INTO ".$TBLPREFIX."pdata VALUES ('0', '".$gedcomid."', '".$data."')";
			$res = NewQuery($sql);
			$start += 65535;
		}
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_plotdata='1' WHERE gc_gedcom='".get_gedcom_from_id($gedcomid)."'";
		$res = NewQuery($sql);
	}
}		
function GetPlotPerson() {
	global $nrfam, $famgeg, $famgeg1, $nrpers, $persgeg, $persgeg1,$key2ind,$nrman,$nrvrouw;
	global $match1,$match2, $indilist;

	$myindilist= array();
	$keys= array();
	$dates= array();
	$families= array();
	$indilist = array();
	GetIndiList("no");
	$keys = array_keys($indilist);

	$nrpers= count($indilist);
	$nrman=0; $nrvrouw=0;
	for($i=0; $i<$nrpers; $i++)	{
		$key = $keys[$i];
		$deathdate="";
		$birthdate="";
		$sex= "";
		$indirec= FindPersonRecord($key);
		if (DatePlace($indirec,"1 BIRT")!==false) {
			$birthdate= $match1[1]; 
			$birthplace=$match2[1];
		}
		//--	print ("geboorte:".$birthdate."--".$birthplace."<br>");
		if (DatePlace($indirec,"1 DEAT")!==false) {
			$deathdate= $match1[1]; 
			$deathplace=$match2[1];
		}
		//-- print ("overleden:".$deathdate."--".$deathplace."<br>");
		if (StringInfo($indirec,"1 SEX") !==false) {
			$sex= 0;
			if ($match1[1] == "M") {$sex= 1; $nrman++;}
			if ($match1[1] == "F") {$sex= 2; $nrvrouw++;}
		}
		//--print ("sexe=".$match1[1].":".$sex."<br>");

		//-- get the marriage date of (the first) marriage.

		$ybirth= -1; $mbirth= -1;
		$ydeath= -1; $mdeath= -1;
		if ($birthdate !== "") {
			$dates= ParseDate($birthdate);
			// the ParseDate function is in function.php
			$ik=0; $mrk= "  :  ";
			//-- print "gegevens b/m=" . $key . $mrk . $birthdate . $mrk . $dates[$ik]["day"] . $mrk . $dates[$ik]["mon"] . $mrk . $dates[$ik]["year"] . $mrk . $dates[$ik]["ext"] ;
			if ($dates[0]["ext"] == "")	{
				$ybirth= $dates[0]["year"];
				$mbirth= $dates[0]["mon"];
				//--print "gevonden jaar en maand" . $birthdate . ":" .$ybirth . ":" . $mbirth . ":<br>";
			}
		}

		if ($deathdate !== "") {
			$dates= ParseDate($deathdate);
			// the ParseDate function is in function.php
			$ik=0; $mrk= "  :  ";
			//-- print "====" . $mrk . $deathdate . $mrk . $dates[$ik]["day"] . $mrk . $dates[$ik]["mon"] . $mrk . $dates[$ik]["year"] . $mrk . $dates[$ik]["ext"] . "<br>" ;
			if ($dates[0]["ext"] == "") {
				$ydeath= $dates[0]["year"];
				$mdeath= $dates[0]["mon"];
			}
		}
		//-- else {print "==== no deathdate<br>";}

		$families= FindSfamilyIds($key); //-- get the number of marriages of this person.
		//--print "families:";
		//--if (isset($families)) { print_r($families);}
		//--print ":einde<br>";
		$persgeg[$i]["key"]= $key;
		$key2ind[$key]= $i;
		$persgeg[$i]["ybirth"]= $ybirth;
		$persgeg[$i]["mbirth"]= $mbirth;
		$persgeg[$i]["ydeath"]= $ydeath;
		$persgeg[$i]["mdeath"]= $mdeath;
		$persgeg1[$i]["arfams"]= $families;
		$persgeg[$i]["sex"]= $sex;
	}
}

function CompletePlotData() {
	global $nrfam, $famgeg, $famgeg1, $nrpers, $persgeg, $persgeg1,$key2ind,$nrman,$nrvrouw;
	
	// fill in the first marriages instead of the keys.
	$childs= array();
	$families= array();

	//look in the persgeg array for marriages that occurred
	for($i=0; $i<$nrpers; $i++) {
		$families= $persgeg1[$i]["arfams"];
		$ctc= count($families);
		$marrmonth= -1; $marryear= -1;
		$first= true;
		//-- if ($ctc > 0)
		//-- {print " eerste huwelijk. nr, aantal, key's:" . $i . " : " . $ctc . " : " ;}
		for($j=0; $j<$ctc; $j++) {
			 $keyf= $families[$j]["famid"]; $k= $key2ind[$keyf]; //get the family array and month/date of marriage
			//-- print $keyf . " : ";
			$mm= $famgeg[$k]["mmarr"];
			$my= $famgeg[$k]["ymarr"];
			if ($first) {
				$marryear= $my; 
				$marrmonth= $mm; 
				$marrkey= $keyf; 
				$kb= $k; 
				$first= false;
			}
			if ($marryear < 0 or ($my < $marryear and $my > 0)) {
				$marryear= $my; 
				$marrmonth= $mm; 
				$marrkey= $keyf; 
				$kb= $k; 
				$first= false;
			}
		}
		$persgeg[$i]["ymarr1"]= $marryear;
		$persgeg[$i]["mmarr1"]= $marrmonth;
		$famgeg[$kb]["ymarr1"]= $marryear;
		$famgeg[$kb]["mmarr1"]= $marrmonth;
		//-- if ($ctc > 0)
		//-- {print " keuze=:" . $kb . ":". $marrkey . " : " . $marryear . " : " . $marrmonth . "<br>";}
	}
	for($i=0; $i<$nrfam; $i++) {
		$childs= $famgeg1[$i]["arfamc"];
		$ctc= count($childs);
		$birthmonth= -1; $birthyear= -1; $sex=3; $sex1=3;
		$first= true;
		//-- if ($ctc > 0)
		//-- {print " eerste kind. nr, aantal, key's:" . $i . " : " . $ctc . " : " ;}
		for($j=0; $j<$ctc; $j++) {
			$key= $childs[$j]; $k= $key2ind[$key];
			//-- print $key . ":";
			$bm= $persgeg[$k]["mbirth"];
			$by= $persgeg[$k]["ybirth"];
			$sex= $persgeg[$k]["sex"];
			if ($first) {
				$birthyear= $by; 
				$birthmonth= $bm; 
				$childkey= $key; 
				$sex1= $sex; 
				$first= false;
			}
			if ($birthyear < 0 or ($by < $birthyear and $by > 0)) {
				$birthyear= $by; 
				$birthmonth= $bm; 
				$childkey= $key; 
				$sex1= $sex; 
				$first= false;
			}
			//--{print " loop gevonden:" . $key . " : " . $sex . " : " . $by . " : " . $bm . "<br>";}
		}
		$famgeg[$i]["sex1"]= $sex1;
		$famgeg[$i]["ybirth1"]= $birthyear;
		$famgeg[$i]["mbirth1"]= $birthmonth;
		$persgeg[$k]["ybirth1"]= $birthyear;
		$persgeg[$k]["mbirth1"]= $birthmonth;
		//--if ($ctc > 0)
		//--{print " gevonden:" . $childkey . " : " . $sex1 . " : " . $birthyear . " : " . $birthmonth . "<br>";}
	}
}


function GetPlotFamily()
{

global $nrfam, $famgeg, $famgeg1, $nrpers, $persgeg, $persgeg1,$key2ind,$nrman,$nrvrouw;
global $match1,$match2, $famlist;

	$famlist= array();
	$keys= array();
	$parents=array();
	$dates= array();
	GetFamList("no");
	$nrfam= count($famlist);
	$keys = array_keys($famlist);

	for($i=0; $i<$nrfam; $i++) {
		$key = $keys[$i];
		$marriagedate=""; $ymarr= -1; $mmarr= -1;
		$divorcedate= ""; $ydiv= -1; $mdiv= -1;
		$indirec= FindFamilyRecord($key);
		//--	print("famrec:" . $key . ":" . $indirec . "<BR>");
		if (DatePlace($indirec,"1 MARR")!==false) {
			$marriagedate= $match1[1]; 
			$marriageplace=$match2[1]; $sex=1;
		}
		else
		if (DatePlace($indirec,"1 MARS")!==false) {
			$marriagedate= $match1[1]; 
			$marriageplace=$match2[1]; 
			$sex=0;
		}
		//--	 print ("gehuwd:".$marriagedate."--".$marriageplace."<br>");
		if (DatePlace($indirec,"1 DIV")!==false) {
			$divorcedate= $match1[1]; 
			$divorceplace=$match2[1];
		}
		if ($marriagedate !== "") {
			$dates= ParseDate($marriagedate);
			// the ParseDate function is in function.php
			$ik=0; $mrk= "  :  ";
			//-- print "marriage, nr, key=" .$i . $mrk . $key . $mrk . $marriagedate . $mrk . $dates[$ik]["day"] . $mrk . $dates[$ik]["mon"] . $mrk . $dates[$ik]["year"] . $mrk . $dates[$ik]["ext"] ;
			//--	==== beware that every about 1850 means that the value will be set to unidentified == -1 ======
			if ($dates[0]["ext"] == "") {
				$ymarr= $dates[0]["year"];
				$mmarr= $dates[0]["mon"];
			}
		}
		if ($divorcedate !== "") {
			$dates= ParseDate($divorcedate);
			// the ParseDate function is in function.php
			$ik=0; $mrk= "  :  ";
			//-- print "===divorce=" . $mrk . $divorcedate . $mrk . $dates[$ik]["day"] . $mrk . $dates[$ik]["mon"] . $mrk . $dates[$ik]["year"] . $mrk . $dates[$ik]["ext"] ;
			//		$ydiv= substr($divorcedate,6,4);
			//		$mdiv= substr($divorcedate,3,2);
			if ($dates[0]["ext"] == "") {
				$ydiv= $dates[0]["year"];
				$mdiv= $dates[0]["mon"];
			}

		}
		$parents= FindParents($key);
		//--print ("parents zijn:".$parents["HUSB"].":".$parents["WIFE"]."<BR>");
		$xfather= $parents["HUSB"]; $xmother= $parents["WIFE"];

		//--	check if divorcedate exists otherwise get deadthdate from husband or wife
		if ($ydiv !== "") {
			$ydeathf= ""; 
			$ydeathm= "";
			if ($xfather !== "") {
				$indf= $key2ind[$xfather]; 
				$ydeathf= $persgeg[$indf]["ydeath"];
			}
			if ($xmother !== "") {
				$indm= $key2ind[$xmother]; 
				$ydeathm= $persgeg[$indm]["ydeath"];
			}
			//--print(" keys en index father mother=" . $indf . ":" . $xfather . ":" . $indm . ":" . $xmother . "<BR>");
			if (($ydeathf !== "") and ($ydeathm !== "")) {
				if ($ydeathf > $ydeathm) {
					$ydiv= $ydeathf; $mdiv= $persgeg[$indf]["mdeath"];
				}
				else
				{
					$ydiv= $ydeathm; 
					$mdiv= $persgeg[$indm]["mdeath"];
				}
			}
		}
		$childs= preg_match_all("/1\s*CHIL\s*@(.*)@/",$indirec,$match1,PREG_SET_ORDER);
		//-- print "===kinderen:" . "Aantal=" . $childs . "=nrs=";
		//--	for($k=0; $k<$childs; $k++) {print $match1[$k][0] . " : ";} print "<BR>";
		$children= array();
		for($k=0; $k<$childs; $k++) {
			$children[$k]= $match1[$k][1]; //--
			$children[$k] . " : ";
		}


		$famgeg[$i]["key"]= $key;
		$key2ind[$key]= $i;
		$famgeg[$i]["ymarr"]= $ymarr;
		$famgeg[$i]["mmarr"]= $mmarr;
		$famgeg[$i]["ydiv"]= $ydiv;
		$famgeg[$i]["mdiv"]= $mdiv;
		$famgeg[$i]["childs"]= $childs;
		$famgeg1[$i]["arfamc"]= $children;
		$famgeg[$i]["male"]= $xfather;
		$famgeg[$i]["female"]= $xmother;
		$famgeg[$i]["ymarr1"]= -1;
		$famgeg[$i]["mmarr1"]= -1;
		//-- print "==ouders==:" . $xfather . ":" . $xmother . "==gehuwd==" . $ymarr . "-" . $mmarr . "<BR>";
	}
}

/**
 * Find the DATE and PLACE variables in a person or family record
 *
 * Look for a starting string in the gedcom record of a person or family
 * then find the DATE and PLACE variables
 *
 * @author	GM Development Team
 * @param		<type>	<varname>		<description>
 * @return 	<type>	<description>
 */
function DatePlace($indirec,$lookfor) {
	global $match1,$match2;

	//-- print "start dateplace<br>";
	$birthrec = GetSubRecord(1, $lookfor, $indirec);
	//--	$birthrec= $indirec;
	//-- You need to get the subrecord in order not to mistaken by another key with same subkeys.
	$match1[1]="";
	$match2[1]="";
	//-- print "dataplace:" . $lookfor . "<BR>" . $birthrec . "<br>" . $indirec . "<br>";
	if ($birthrec!== "") {
		$dct = preg_match("/2 DATE (.*)/", $birthrec, $match1);
		//-- if ($dct > 0) {print("birthrec + date" . $birthrec . ":::" . $match1[1] . "<BR>");};
		//--			if ($dct>0) $match1[1]= get_number_date($match1[1]);
		//--			$pct = preg_match("/2 PLAC (.*)/", $birthrec, $match2);
		//--			if ($pct>0) print " -- ".$match2[1]."<br>";
		if ($dct > 0) {
			$match1[1]= trim($match1[1]);
		}
		else $match1[1]="";
		//--			if ($pct > 0) {$match2[1]= trim($match2[1]);} else {$match2[1]="";}
		return true;
	}
	else return false;
}

function StringInfo($indirec,$lookfor) {
	global $match1,$match2;
	//look for a starting string in the gedcom record of a person
	//then take the stripped comment
	//-- print "start stringinfo<br>";
	$birthrec = GetSubRecord(1, $lookfor, $indirec);
	$match1[1]="";
	$match2[1]="";
	if ($birthrec!==false) {
		$dct = preg_match("/".$lookfor." (.*)/", $birthrec, $match1);
		if ($dct < 1) $match1[1]="";
		//--print("stringinfo:".$dct.":".$lookfor.":".$birthrec.":".$match1[1].":<BR>");
		$match1[1]= trim($match1[1]);
		return true;
	}
	else return false;
}

function CheckPlotExtensions() {
	global $gm_lang;
	
	$GDcheck = 1;
	$JPcheck = 1;

	//-- Check if GD library is loaded
	if (!extension_loaded('gd')) $GDcheck = 0;
	
	//-- Check if JpGraph modules are available
	if (!file_exists("modules/jpgraph/jpgraph.php") || !file_exists("modules/jpgraph/jpgraph_line.php") ||!file_exists("modules/jpgraph/jpgraph_bar.php")) $JPcheck= 0;
	
	if (($GDcheck == 0) or ($JPcheck == 0))	{
		if ($GDcheck == 0) print $gm_lang["stplGDno"] . "<br />";
		if ($JPcheck == 0) print $gm_lang["stpljpgraphno"] . "<br />";
		exit;
	}
}

	
?>