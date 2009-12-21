<?php
/**
 * Parses gedcom file and gives access to information about a family.
 *
 * You must supply a $famid value with the identifier for the family.
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
 * $Id$
 * @package Genmod
 * @subpackage Controllers
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Patriarch {
	
	var $patrilist = array();
	var $patrialpha = array();
	var $tpatrilist = array();
	var $my2indilist= array();
	var $keys= array();
	var $orignames= array();
	var $person = array();
	
	public function __construct() {
		$this->indi2roots();
		$this->SortPatriAlpha();
		$this->put_patri_list();
	}
	
	function indi2roots() {
		global $ct;
		
		$this->my2indilist = GetIndiList();
		$ct = count($this->my2indilist);
	
		// NOTE: First select the names then do the alphabetic sort
		$orignum=0;
		$i=0;
		$keys = array_keys($this->my2indilist);
		//--key is I<nr>
		
		while ($i < $ct) {
			$key=$keys[$i];
//			$person = $this->my2indilist[$key]["gedcom"];
			$person= FindPersonRecord($key);
			$famc="";
			$ctc= preg_match("/1\s*FAMC\s*@(.*)@/",$person,$match);
			if ($ctc > 0) {
				$famc= $match[1];
				$parents= FindParents($famc);
				if (($parents["WIFE"] == "") and ($parents["HUSB"] == "")) $famc= "";
			}
			
			//-- we assume that when there is a famc record, this person is not a patriarch
			//-- in special cases it is possible that a child is a member of a famc record but no parents are given
			//-- and so they are the patriarch's
	
			//-- first spouse record. assuming a person has just one father and one mother.
			if ($famc == "") {
				//--print "select:$orignum,$key,$value,$person<br>";
				$value= $this->my2indilist[$key]["names"][0][0];
				$value2= $this->my2indilist[$key]["gedfile"];
				$orignum ++;
				$this->orignames["$key"]["name"]=$value;
				$this->orignames["$key"]["gedfile"]=$value2;
//				$this->orignames["$key"]["gedcom"]=$person;
			}
			$i++;
		}
		$ct= $orignum;
		$this->patrilist=$this->orignames;
		uasort($this->patrilist, "ItemSort");
		$i=0;
		$keys = array_keys($this->patrilist);
		$oldletter= "";
		while ($i < $ct) {
			$key=$keys[$i];
			$value = GetSortableName($key);
			$value2= $this->patrilist[$key]["gedfile"];
//			$person = $this->orignames[$key]["gedcom"];
			$person= FindPersonRecord($key);
			//--> Changed MA @@@ as in extract_surname() etc.
			$tmpnames = preg_split("/,/", $value);
			$tmpnames[0] = StripPrefix($tmpnames[0]);
			//-- check for all lowercase name and start over
			if (empty($tmpnames[0])) {
				$tmpnames = preg_split("/,/", $value);
				$tmpnames[0] = trim($tmpnames[0]);
			}
			$tmpletter = GetFirstLetter($tmpnames[0]);
			if ($tmpletter!=$oldletter) $oldletter=$tmpletter;
			if ((!isset($alpha)) || ($alpha = $tmpletter)) {
				$this->orignames["$key"]["name"]=$value;
				$this->orignames["$key"]["gedfile"]=$value2;
				$letter=$tmpletter;
				//<---- MA @@@
				if (!isset($this->patrialpha[$letter])) {
					$this->patrialpha[$letter]["letter"]= "$letter";
					$this->patrialpha[$letter]["gid"]= "$key";
				}
				else $this->patrialpha[$letter]["gid"].= ",$key";
			}
			$i++;
		}
		$this->patrilist=$this->orignames;
	}
	
	function SortPatriAlpha() {
		//-- name in $patriarchalpha for sorting??? MA @@@ =====>
		uasort($this->patrialpha, "LetterSort");
	}
	
	function put_patri_list() {
		//-- save the items in the database
		global $GEDCOMID, $FP;
	
		$indexfile = INDEX_DIRECTORY.$GEDCOMID."_patriarch.php";
		$FP = fopen($indexfile, "wb");
		if (!$FP) {
			print "<font class=\"error\">".GM_LANG_unable_to_create_index."</font>";
			exit;
		}
	
		fwrite($FP, 'a:1:{s:13:"patrilist";');
		fwrite($FP, serialize($this->patrilist));
		fwrite($FP, '}');
		fclose($FP);
	}
	
	//-- find all of the individuals who start with the given letter within patriarchlist
	function get_alpha_patri($letter) {
		if (isset($this->patrialpha[$letter])) {
			$list = $this->patrialpha[$letter]["gid"];
			$gids = preg_split("/[+,]/", $list);
			foreach($gids as $indexval => $gid)	{
				$this->tpatrilist[$gid] = $this->patrilist[$gid];
			}
		}
		return $this->tpatrilist;
	}
}
?>