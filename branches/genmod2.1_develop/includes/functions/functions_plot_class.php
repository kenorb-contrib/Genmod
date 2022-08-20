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
 * @version $Id: functions_plot_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage DB
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class PlotFunctions {

	public function GetPlotData($gedcomid="") {
		global $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $nrman, $nrvrouw;
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		
		// check if we must update the cache anyway
		$cache_load = GedcomConfig::GetLastCacheDate("plotdata", $gedcomid);

		if (!$cache_load) {
			$sql = "DELETE FROM ".TBLPREFIX."pdata WHERE pd_file='".$gedcomid."'";
			$res = NewQuery($sql);
		}
		else {
			$sql = "SELECT pd_data FROM ".TBLPREFIX."pdata WHERE pd_file='".$gedcomid."' ORDER BY pd_id";
			$res = NewQuery($sql);
			if ($res->NumRows() == 0) $cache_load = false;
			else {
				$str = "";
				while ($row = $res->FetchRow()) {
					$str .= $row[0];
				}
				$pdata = unserialize($str);
				unset($str);
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
			self::GetPlotPerson();
			
			self::GetPlotFamily();
			
			self::CompletePlotData();
			
			$pdata = array();
			$pdata["nrfam"] = $nrfam;		// Total # of families
			
			$pdata["famgeg"] = $famgeg;		// Array with data of families:
											// index #1	: numeric starting with 0
											// index #2	:
											// 'key'	: arraykey of the original famlist
											// 'ymarr'	: year of first marriage if no prefix, numeric, or -1
											// 'mmarr'	: month of first marriage if no prefix, numeric, or -1
											// 'ydiv'	: month of divorce if no prefix, or month of earliest death date of either parents, or -1
											// 'mdiv'	: year of divorce if no prefix, or year of earliest death date of either parents, or -1
											// 'childs'	: # of children
											// 'male'	: id of the father in the family
											// 'female'	: id of the mother in the family
											// 'ymarr1'	: initially set to -1, if more marriages exist year of first marriage
											// 'mmarr1'	: initially set to -1, if more marriages exist month of first marriage
			// $famgeg1						// 'arfamc'	: array of children with id's followed by :
			
			$pdata["nrpers"] = $nrpers;		// Total # of persons
			
			$pdata["persgeg"] = $persgeg;	// Array with the data of persons:
											// index #1	: numeric starting with 0
											// 'key'	: arraykey of the original indilist
											// 'ybirth'	: year of birth, christening or baptism if no prefix, or -1
											// 'mbirth'	: month of birth, christening or baptism if no prefix, or -1
											// 'ydeath'	: year of death if no prefix, or -1
											// 'mdeath	: month of death if no prefix, or -1
											// 'sex'	: gender, 0=unknown, 1=male, 2=female
			// $persgeg1					// 'arfams'	: array of family id's where the person is spouse
			
			$pdata["key2ind"] = $key2ind;	// index #1	: family key
											// value	: index #1 of persgeg array	
			
			$pdata["nrman"] = $nrman;		// Number of persons with gender male
			
			$pdata["nrvrouw"] = $nrvrouw;	// Number of persons with gender female
			
			$sql = "DELETE FROM ".TBLPREFIX."pdata WHERE pd_file='".$gedcomid."'";
			$res = NewQuery($sql);
			$str = serialize($pdata);
			$l = strlen($str);
			$start = 0;
			while ($start <= $l) {
				$data = DbLayer::EscapeQuery(substr($str, $start, 65535));
				$sql = "INSERT INTO ".TBLPREFIX."pdata VALUES ('0', '".$gedcomid."', '".$data."')";
				$res = NewQuery($sql);
				$start += 65535;
			}
			GedcomConfig::SetLastCacheDate("plotdata", "1", $gedcomid);
		}
	}		
	private function GetPlotPerson() {
		global $nrfam, $famgeg, $famgeg1, $nrpers, $persgeg, $persgeg1, $key2ind, $nrman, $nrvrouw;
		global $monthtonum;
	
		$sql = "SELECT i_key, i_gender, d_fact, d_month, d_year, if_fkey FROM ".TBLPREFIX."individuals LEFT JOIN ".TBLPREFIX."individual_family ON ((i_key=if_pkey AND if_role='S') OR if_pkey IS NULL) LEFT JOIN ".TBLPREFIX."dates ON (i_key=d_key AND (d_fact IS NULL OR (d_month<>'' AND d_year<>0 AND d_fact IN ('BIRT', 'CHR', 'BAPM', 'DEAT') AND d_ext=''))) WHERE i_file=".GedcomConfig::$GEDCOMID." ORDER BY i_key, d_year, d_month, d_day";
	
		$res = NewQuery($sql);
		$persgeg = array();
		$persgeg1 = array();
		$key2ind = array();
		$count = -1;
		$lastkey = "";
		$nrman = 0; 
		$nrvrouw = 0;
		
		while ($row = $res->FetchAssoc()) {	
			if ($row["i_key"] != $lastkey) {
				$count++;
				$lastkey = $row["i_key"];
				$key2ind[$lastkey] = $count;
				$persgeg[$count]["key"] = $row["i_key"];
				$persgeg[$count]["ybirth"]= -1;
				$persgeg[$count]["mbirth"]= -1;
				$persgeg[$count]["ydeath"]= -1;
				$persgeg[$count]["mdeath"]= -1;
				$persgeg1[$count]["arfams"]= array();
				$sex = 0;
				if ($row["i_gender"] == "M") {
					$sex = 1;
					$nrman++;
				}
				else if ($row["i_gender"] == "F") {
					$sex = 2;
					$nrvrouw++;
				}
				$persgeg[$count]["sex"] = $sex;
			}
			if (!is_null($row["if_fkey"])) $persgeg1[$count]["arfams"][] = array("famid" => $row["if_fkey"]);
			if ($row["d_fact"] == "BIRT") {
				$persgeg[$count]["ybirth"] = $row["d_year"];
				$persgeg[$count]["mbirth"] = $monthtonum[str2lower($row["d_month"])];
			}
			else if ($row["d_fact"] == "CHR" || $row["d_fact"] == "BAPM") {
				if ($persgeg[$count]["ybirth"] == -1) {
					$persgeg[$count]["ybirth"] = $row["d_year"];
					$persgeg[$count]["mbirth"] = $monthtonum[str2lower($row["d_month"])];
				}
			}
			if ($row["d_fact"] == "DEAT") {
				$persgeg[$count]["ydeath"] = $row["d_year"];
				$persgeg[$count]["mdeath"] = $monthtonum[str2lower($row["d_month"])];
			}
		}
		$nrpers= count($persgeg);
	}
	
	private function CompletePlotData() {
		global $nrfam, $famgeg, $famgeg1, $nrpers, $persgeg, $persgeg1,$key2ind,$nrman,$nrvrouw;
		
		// fill in the first marriages instead of the keys.
		$childs = array();
		$families = array();
	
		//look in the persgeg array for marriages that occurred
		for($i=0; $i<$nrpers; $i++) {
			$families= $persgeg1[$i]["arfams"];
			$ctc= count($families);
			$marrmonth= -1; $marryear= -1;
			$first= true;
			if ($ctc > 0) {
				//-- if ($ctc > 0)
				//-- {print " eerste huwelijk. nr, aantal, key's:" . $i . " : " . $ctc . " : " ;}
				for($j=0; $j<$ctc; $j++) {
					$keyf = $families[$j]["famid"];
					$k = $key2ind[$keyf]; //get the family array and month/date of marriage
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
	
	
	private function GetPlotFamily() {
		global $nrfam, $famgeg, $famgeg1, $nrpers, $persgeg, $persgeg1,$key2ind,$nrman,$nrvrouw;
		global $match1, $famlist;
		global $monthtonum;
	
		$famgeg = array();
		$famgeg1 = array();
		$count = -1;
		$lastkey = "";
		
		$sql = "SELECT f_key, f_husb, f_wife, d_fact, d_month, d_year, if_pkey FROM ".TBLPREFIX."families LEFT JOIN ".TBLPREFIX."individual_family ON ((f_key=if_fkey AND if_role='C') OR if_fkey IS NULL) LEFT JOIN ".TBLPREFIX."dates ON (f_key=d_key AND (d_fact IS NULL OR (d_month<>'' AND d_year<>0 AND d_fact IN ('MARR', 'MARS', 'DIV') AND d_ext=''))) WHERE f_file=".GedcomConfig::$GEDCOMID." ORDER BY f_key, d_year, d_month, d_day";
		$res = NewQuery($sql);
		
		while ($row = $res->FetchAssoc()) {	
			if ($row["f_key"] != $lastkey) {
				$count++;
				$lastkey = $row["f_key"];
				$famgeg[$count]["key"] = $lastkey;
				$key2ind[$lastkey]= $count;
				$famgeg[$count]["male"] = $row["f_husb"];
				$famgeg[$count]["female"] = $row["f_wife"];
				$famgeg[$count]["ymarr1"]= -1;
				$famgeg[$count]["mmarr1"]= -1;
				$famgeg[$count]["ymarr"] = -1;
				$famgeg[$count]["mmarr"] = -1;
				$famgeg[$count]["ydiv"] = -1;
				$famgeg[$count]["mdiv"] = -1;
				$famgeg[$count]["childs"] = 0;
				$famgeg1[$count]["arfamc"]= array();
			}
			if (!is_null($row["if_pkey"])) $famgeg1[$count]["arfamc"][] = $row["if_pkey"];
			if ($row["d_fact"] == "MARR") {
				$famgeg[$count]["ymarr"] = $row["d_year"];
				$famgeg[$count]["mmarr"] = $monthtonum[str2lower($row["d_month"])];
			}
			else if ($row["d_fact"] == "MARS") {
				if ($famgeg[$count]["ymarr"] == -1) {
					$famgeg[$count]["ymarr"] = $row["d_year"];
					$famgeg[$count]["mmarr"] = $monthtonum[str2lower($row["d_month"])];
				}
			}
			if ($row["d_fact"] == "DIV") {
				$famgeg[$count]["ydiv"] = $row["d_year"];
				$famgeg[$count]["mdiv"] = $monthtonum[str2lower($row["d_month"])];
			}
		}
		$nrfam= count($famgeg);	
		
		foreach ($famgeg as $key => $fam) {
			if ($fam["ydiv"] = -1) {
				//--	check if divorcedate exists otherwise get deadthdate from husband or wife
				$ydeathf= ""; 
				$ydeathm= "";
				if (!empty($fam["male"])) {
					$ydeathf = $persgeg[$key2ind[$fam["male"]]]["ydeath"];
				}
				if (!empty($fam["female"])) {
					$ydeathm = $persgeg[$key2ind[$fam["female"]]]["ydeath"];
				}
				//--print(" keys en index father mother=" . $indf . ":" . $xfather . ":" . $indm . ":" . $xmother . "<BR>");
				if (($ydeathf !== "") and ($ydeathm !== "")) {
					if ($ydeathf > $ydeathm) {
						$ydiv = $ydeathf; 
						$mdiv = $persgeg[$key2ind[$fam["male"]]]["mdeath"];
					}
					else
					{
						$ydiv = $ydeathm; 
						$mdiv = $persgeg[$key2ind[$fam["female"]]]["mdeath"];
					}
					$famgeg[$key]["ydiv"] = $ydiv;
					$famgeg[$key]["mdiv"] = $mdiv;
				}
			}
		}
	}
	
	public function CheckPlotExtensions() {
		
		$GDcheck = 1;
		$JPcheck = 1;
	
		//-- Check if GD library is loaded
		if (!extension_loaded('gd')) $GDcheck = 0;
		
		//-- Check if JpGraph modules are available
		if (!file_exists("modules/jpgraph/jpgraph.php") || !file_exists("modules/jpgraph/jpgraph_line.php") ||!file_exists("modules/jpgraph/jpgraph_bar.php")) $JPcheck= 0;
		
		if (($GDcheck == 0) or ($JPcheck == 0))	{
			if ($GDcheck == 0) print GM_LANG_stplGDno . "<br />";
			if ($JPcheck == 0) print GM_LANG_stpljpgraphno . "<br />";
			exit;
		}
	}
	
	private function Bimo($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$m= $persgeg[$i]["mbirth"];
		if ($z_as == 301) $ys = $persgeg[$i]["sex"]-1;
		else $ys = $persgeg[$i]["ybirth"];
	
	 	if ($m > 0)	{	
			//--print "bimo:".$ys." : ".$m."<br>";
			self::FillYData($ys,$m-1,1);
			$n1++;
		}
	}
	
	private function Bimo1($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$m= $famgeg[$i]["mbirth1"];
		if ($z_as == 301) $ys = $famgeg[$i]["sex1"]-1;
		else $ys = $famgeg[$i]["ybirth1"];
	
	 	if ($m > 0)	{
			//--print "bimo:".$ys." : ".$m."<br>";
			self::FillYData($ys,$m-1,1);
			$n1++;
		}
	}
	
	private function Demo($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$m= $persgeg[$i]["mdeath"];
		if ($z_as == 301) $ys= $persgeg[$i]["sex"]-1;
		else $ys= $persgeg[$i]["ybirth"];
	
		if ($m > 0) {
			self::FillYData($ys,$m-1,1);
			$n1++;
		}
	}
	
	private function Mamo($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$m= $famgeg[$i]["mmarr"]; 
		$y= $famgeg[$i]["ymarr"];
		//--print "mamo:".$y." : ".$m."<br>";
		if ($m > 0)	{
			self::FillYData($y,$m-1,1);
			$n1++;
		}
	}
	
	private function Mamo1($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$m= $famgeg[$i]["mmarr1"]; 
		$y= $famgeg[$i]["ymarr1"];
		//--print "mamo:".$y." : ".$m."<br>";
		if ($m > 0) {
			self::FillYData($y,$m-1,1);
			$n1++;
		}
	}
	
	private function Mamam($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$m= $famgeg[$i]["mmarr"]; $y= $famgeg[$i]["ymarr"];
		//--print "mamo:".$y." : ".$m."<br>";
		if ($m > 0)	{
			$m2= $famgeg[$i]["mbirth1"]; 
			$y2= $famgeg[$i]["ybirth1"]; 
			if ($z_as == 301) $ys= $famgeg[$i]["sex1"] - 1;
			else $ys= $famgeg[$i]["ybirth1"];
			if ($m2 > 0) {
				$mm= ($y2 - $y) * 12 + $m2 - $m;
				//--print $i.":".$y."-".$m."::".$y2."-".$m2."<br>";
				self::FillYData($ys,$mm,1);
				$n1++;
			}
		}
	}
	
	private function Agbi($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$yb= $persgeg[$i]["ybirth"];
		$yd= $persgeg[$i]["ydeath"];
		if ($yb > 0 && $yd > 0)	{
			$age= $yd - $yb;
			if ($z_as == 301) $yb= $persgeg[$i]["sex"]-1;
			self::FillYData($yb,$age,1);
			//-- print "leeftijd:" . $i . ":" . $yb . ":" . $yd . ":" . $age . "<br>";
			$n1++;
		}
	}
	
	private function Agde($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$yb= $persgeg[$i]["ybirth"];
		$yd= $persgeg[$i]["ydeath"];
		if ($yb > 0 && $yd > 0) {	
			$age= $yd - $yb;
			if ($z_as == 301) $yd= $persgeg[$i]["sex"]-1;
			self::FillYData($yd,$age,1);
			//-- print "leeftijd:" . $i . ":" . $yb . ":" . $yd . ":" . $age . "<br>";
			$n1++;
		}
	}
	
	private function Agma($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$ym= $famgeg[$i]["ymarr"];
		//--print "mamo:".$y." : ".$m."<br>";
		if ($ym > 0) {
			$xfather= $famgeg[$i]["male"];
			$xmother= $famgeg[$i]["female"];
			if (!empty($xfather)) $j= $key2ind[$xfather];
			if (!empty($xmother)) $j2= $key2ind[$xmother];
			$ybirth= -1; 
			$ybirth2= -1; 
			$age= 0; 
			$age2= 0;
			if (isset($j)  && ($xfather !== "")) $ybirth= $persgeg[$j]["ybirth"];
			if (isset($j2) && ($xmother !== "")) $ybirth2= $persgeg[$j2]["ybirth"];
			$z= $ym; 
			$z1= $ym;
			if ($z_as == 301) {
				$z= 0; 
				$z1= 1;
			}
			if ($ybirth > -1) {
				$age= $ym - $ybirth; 
				self::FillYData($z,$age,1); 
				$n1++;
			}
			if ($ybirth2 > -1) {
				$age2= $ym - $ybirth2; 
				self::FillYData($z1,$age2,1); 
				$n1++;
			}
			// if ($age < 15 || $age > 70) {
				//--	print ("huw:" . $xfather . ":" . $xmother . ":" . $ym . ":" . $age . ":" .$age2 . ":" . $mm . "<BR>");
			// }
		}
	}
	
	private function Agma1($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$ym= $famgeg[$i]["ymarr"];
		//--print "mamo:".$y." : ".$m."<br>";
		if ($ym > -1) {
			$xfather= $famgeg[$i]["male"];
			$xmother= $famgeg[$i]["female"];
			if (!empty($xfather)) $j= $key2ind[$xfather];
			if (!empty($xmother)) $j2= $key2ind[$xmother];
			$ybirth= -1; 
			$ybirth2= -1; 
			$age= 0; 
			$age2= 0;
			if (isset($j) && $xfather !== "") {
				$ybirth=  $persgeg[$j]["ybirth"];  
				$ymf= $persgeg[$j]["ymarr1"];
			}
			if (isset($j2) && $xmother !== "") { 
				$ybirth2= $persgeg[$j2]["ybirth"]; 
				$ymm= $persgeg[$j2]["ymarr1"];
			}
			$z= $ym; 
			$z1= $ym; 
			if ($z_as == 301) {
				$z= 0; 
				$z1= 1;
			}
			if ($ybirth > -1 && $ymf > -1 && $ym == $ymf) {
				$age= $ym - $ybirth; 
				self::FillYData($z,$age,1); 
				$n1++;
			}
			if ($ybirth2 > -1 && $ymm > -1 && $ym == $ymm) {
				$age2= $ym - $ybirth2; 
				self::FillYData($z1,$age2,1); 
				$n1++;
			}
	//		if ($age < 15 or $age > 70) {
				//--	print ("huw:" . $xfather . ":" . $xmother . ":" . $ym . ":" . $age . ":" .$age2 . ":" . $mm . "<BR>");
	//		}
		}
	}
	
	
	private function Nuch($i) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
	
		$c= $famgeg[$i]["mmarr"]; 
		$y= $famgeg[$i]["ymarr"];
		//--print "mamo:".$y." : ".$m."<br>";
		//--	if ($m > 0)
		//{
		self::FillYData($y,$c,1);
		$n1++;
		//}
	}
	
	
	private function FillYData($z,$x,$val) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
		global $legend, $xdata, $ydata, $xmax, $xgrenzen, $zmax, $zgrenzen, $xgiven,$zgiven, $percentage, $man_vrouw;
		
		//--	calculate index $i out of given z value
		//--	calculate index $j out of given x value
	
		//--print "z,x,val,xgrenzen,zgrenzen".$z .":".$x.":".$val.":".$xgiven.":".$zgiven."<br>";
		if ($xgiven) {
			$j= $x;
		}
		else {
			$j = 0;
			while ($x > $xgrenzen[$j] && $j < $xmax) {
				//--print "xgrenzen:".$xgrenzen[$j].":".$x."==<br>"; 
				$j++;
			}
		}
		if ($zgiven) {
			$i= $z;
		}
		else {
			$i=0;
			while ($z > $zgrenzen[$i] && $i < $zmax) {
				//--print "zgrenzen:".$zgrenzen[$i].":".$z."==<br>";
				$i++;
			}
		}
		if (isset($ydata[$i][$j])) $ydata[$i][$j] += $val;
		else $ydata[$i][$j] = $val;
		//--	print "z:" . $z . ", x:" . $x . ", i:" . $i . ", j:" . $j .", val:" . $val . "<BR>";
	}
	
	private function MyPlot($mytitle,$n,$xdata,$xtitle,$ydata,$ytitle,$legend) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
		global $legend, $xdata, $ydata, $xmax, $xgrenzen, $zmax, $zgrenzen, $xgiven,$zgiven, $percentage;
		global $colors;
		global $showShadow, $valuePos, $graphSize, $value_angle, $screenRes, $windowRes;
		global $GM_BASE_DIRECTORY;
	
		$b= array();
	
		$graph_width = $graph_height = -1;
		if (($graphSize == "autoScreen") || ($graphSize == "autoWindow")) {
			$new_size = array();
			if ($graphSize == "autoScreen") $new_size = explode("x",$screenRes);
			else if ($graphSize == "autoWindow") $new_size = explode("x",$windowRes);
			if (count($new_size) >= 2) {
				$graph_width = ($new_size[0]*7)/8;
				$graph_height = ($graph_width*4)/7;
			}
		}
		if (($graph_width == -1) || ($graph_height == -1)) {
			$new_size = array();
		        $new_size = explode("x",$graphSize);
			if (count($new_size >= 2)) {
				$graph_width = $new_size [0];
				$graph_height = $new_size [1];
			}
		}
		if (($graph_width == -1) || ($graph_height == -1)) {
			$graph_width = 700; $graph_height = 400;
		}
		$graph= new Graph($graph_width,$graph_height,"auto");
	
		$graph-> SetScale("textlin");
		$graph->title->Set($mytitle);
		$graph->title->SetColor("darkred");
		$graph->xaxis->SetTickLabels($xdata);
		$graph->xaxis->title->Set($xtitle);
		$graph->yaxis->title->Set($ytitle);
		$graph->yaxis->scale->SetGrace(20);
		$graph->xaxis->scale->SetGrace(20);
		//--print "myplot".$n.":".$percentage . ":".$xmax."<br>";
		$groupBarArray = array();
		for($i=0; $i<$n; $i++) {
			if ($percentage){
				$sum= 0;
				for ($j=0; $j<$xmax;$j++) {
					$sum= $ydata[$i][$j] + $sum;
				}
				for ($j=0; $j<$xmax; $j++) {
					if ($sum > 0) {
						$ynew= $ydata[$i][$j] / $sum * 100;
						settype($ynew, 'integer'); 
						$ydata[$i][$j]= $ynew; 
					}
				}
			}
			$b[$i] = new BarPlot($ydata[$i]);
			$b[$i] ->SetFillColor($colors[$i]);
			$b[$i] ->Setlegend($legend[$i]);
			if ($valuePos != "none") {
				$b[$i] ->value->Show();
				$b[$i] ->SetValuePos($valuePos);
			}
			$b[$i] ->value->SetFormat('%d');
			if ($value_angle != 0) {
				// would look nicer with font & angle, but
				// which font? TTF font is required for angle
				//$b[$i] ->value->SetFont(FF_VERA);
				$b[$i] ->value->SetAngle($value_angle);
			}
			if ($showShadow == "yes") $b[$i] ->SetShadow();
			$groupBarArray[]=$b[$i];
		}
		$accbar = new GroupBarPlot($groupBarArray);
	
		$graphFile = tempnam($GM_BASE_DIRECTORY."index/", "GM");
		unlink($graphFile);
		$graph-> Add($accbar);
		$graph-> Stroke($graphFile);
		$tempVarName = "V".time();
		unset($_SESSION["image_data"]);			// Make sure imageflush.php
		$_SESSION[$tempVarName] = $graphFile;	//   uses the right image source
		$imageSize = getimagesize($graphFile);
		if ($imageSize===false) {
			unset($imageSize);
			$imageSize[0] = 300;
			$imageSize[1] = 300;
		}
		$titleLength = strpos($mytitle."\n", "\n");
		$title = substr($mytitle, 0, $titleLength);
		print "<img class=\"StatsPlot\" src=\"imageflush.php?image_type=png&amp;image_name=$tempVarName\" width=\"$imageSize[0]\" height=\"$imageSize[1]\" border=\"0\" alt=\"$title\" title=\"$title\" />";
	}
	
	private function CalcAxis($xas_grenzen) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
		global $legend, $xdata, $ydata, $xmax, $xgrenzen, $zmax, $zgrenzen, $xgiven,$zgiven, $percentage, $man_vrouw;
	
		//calculate xdata and zdata elements out of given POST values
		$hulpar= array();
	
		$hulpar= explode(",",$xas_grenzen);
		//--print "string x_as".$xas_grenzen."<BR>";
		//--for ($k=0;$k<10;$k++) {print "grenzen x_as:" . $k .":" . $hulpar[$k];}print "<br>";
		$i=1; 
		$xdata[0]= "<" . "$hulpar[0]"; 
		$xgrenzen[0]= $hulpar[0]-1;
		while (isset($hulpar[$i])) {
			$i1= $i-1; 
			if (($hulpar[$i] - $hulpar[$i1]) == 1) $xdata[$i]= "$hulpar[$i1]";
			else $xdata[$i]= "$hulpar[$i1]" . "-" . "$hulpar[$i]";
			$xgrenzen[$i]= $hulpar[$i]; 
			$i++;
			//--print " xgrenzen:".$i.":".$xgrenzen[$i-1].":".$xdata[$i-1].":<BR>";
		}
		$xmax= $i; 
		$xmax1= $xmax-1; 
		$xdata[$xmax]= ">" . "$hulpar[$xmax1]"; 
		$xgrenzen[$xmax]= 10000;
		$xmax= $xmax+1;
		if ($xmax > 20) $xmax=20;
	}
	
	private function CalcLegend($grenzen_zas) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
		global $legend, $xdata, $ydata, $xmax, $xgrenzen, $zmax, $zgrenzen, $xgiven,$zgiven, $percentage, $man_vrouw;
		global $colors;
	
		// calculate the legend values
		$hulpar= array();
		//-- get numbers out of $grenzen_zas
	
		$hulpar= explode(",",$grenzen_zas);
		//--print "string z_as".$grenzen_zas."<BR>";
		//--for ($k=0;$k<10;$k++) {print "grenzen z_as" . $k .":" . $hulpar[$k];} print "<br>";
		$i=1; 
		$legend[0]= "<" . "$hulpar[0]"; 
		$zgrenzen[0]= $hulpar[0]-1;
		while (isset($hulpar[$i])) {
			$i1= $i-1; 
			$legend[$i]= "$hulpar[$i1]" . "-" . "$hulpar[$i]"; 
			$zgrenzen[$i]= $hulpar[$i]; 
			$i++;
			//--print " zgrenzen:".$i.":".$zgrenzen[$i-1].":".$legend[$i-1].":<BR>";
		}
		$zmax= $i; 
		$zmax1= $zmax-1; 
		$legend[$zmax]= ">" . "$hulpar[$zmax1]"; 
		$zgrenzen[$zmax]= 10000;
		$zmax= $zmax+1;
		if ($zmax > count($colors)) $zmax=count($colors);
	}
	
	//--------------------nr,-----bron ,xgiven,zgiven,title, xtitle,ytitle,grenzen_xas, grenzen-zas,functie,
	public function SetParams($current, $indfam, $xg,  $zg, $titstr,  $xt, $yt, $gx, $gz, $myfunc) {
		global $x_as,$y_as, $z_as, $nrfam, $famgeg, $nrpers, $persgeg, $key2ind, $n1;
		global $legend, $xdata, $ydata, $xmax, $xgrenzen, $zmax, $zgrenzen, $xgiven,$zgiven, $percentage, $man_vrouw;
		global $MON_SHORT;
	
		$monthdata= array();
		$monthdata= explode(",",$MON_SHORT);
		foreach ($monthdata as $key=>$month) {
			$monthdata[$key] = utf8_decode($month);
		}
	
		//--print "xas: " . $x_as . " current: " . $current . "<br>";
		if ($x_as == $current) {
			$xgiven= $xg; 
			$zgiven= $zg;
			$title = constant("GM_LANG_".$titstr);
			$xtitle= constant("GM_LANG_".$xt); 
			$grenzen_xas= $gx; 
			$grenzen_zas= $gz;
			if ($xg == true) {
				$xdata=$monthdata; 
				$xmax=12;
			}
			else self::CalcAxis($grenzen_xas);
			self::CalcLegend($grenzen_zas);
		
			$percentage= false; 
			switch ($y_as) {
			default: //case 201:
				$percentage= false; 
				$ytitle= GM_LANG_stplnum;
				break;
			case 202:
				$percentage= true;  
				$ytitle= GM_LANG_stplperc;
				break;
			}
		
			$man_vrouw= false;
			switch ($z_as) {
			case 300:
				$zgiven= false;
				$legend[0]= "all"; 
				$zmax=1; 
				$zgrenzen[0]= 100000;
				break;
			case 301:
				$man_vrouw= true; 
				$zgiven= true;
				$legend[0]= GM_LANG_male; 
				$legend[1]= GM_LANG_female; 
				$zmax=2; 
				$xtitle= $xtitle . GM_LANG_stplmf;
				break;
			default: //case 302:
				$xtitle= $xtitle . GM_LANG_stplipot;
				break;
			}
		
			//-- reset the data array
			for($i=0; $i<$zmax; $i++) {
				for($j=0; $j<$xmax; $j++) {
					$ydata[$i][$j]= 0;
				}
			}
		
			if ($indfam == "IND") $nrmax= $nrpers;
			else $nrmax= $nrfam;
			//--print "nmax".$nrmax.":".$xg.":".$zg.":"."<br>";
			for ($i=0; $i < $nrmax; $i++) {	
				//--print "main:" . $i . "<br>";
				self::$myfunc($i);
			}
			$hstr= utf8_decode($title) . "\n" . utf8_decode(GM_LANG_stplnumof) . " N=" . $n1 . " (max= " . $nrmax. ").";
			self::MyPlot($hstr,$zmax,$xdata,utf8_decode($xtitle),$ydata,utf8_decode($ytitle),$legend);
		}
	}
}
?>