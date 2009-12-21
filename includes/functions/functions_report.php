<?php
/**
 * Temporary collection of functions needed for reports
 *
 * The functions in this file are common to all PGV pages and include date conversion
 * routines and sorting functions.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2002 to 2009  Genmod Development Team
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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

//--- copied from reportpdf.php
function AddAncestors($pid, $children=false, $generations=-1) {
	global $list, $indilist, $genlist;

	$genlist = array($pid);
	$list[$pid]["generation"] = 1;
	while(count($genlist)>0) {
		$id = array_shift($genlist);
		$famids = FindPrimaryFamilyId($id);
		if (count($famids)>0) {
			$ffamid = $famids[0];
			$famid = $ffamid["famid"];
			if (PrivacyFunctions::DisplayDetailsByID($famid, "FAM")) {
				$parents = FindParents($famid);
				if (!empty($parents["HUSB"]) && (PrivacyFunctions::DisplayDetailsByID($parents["HUSB"]) || PrivacyFunctions::showLivingNameByID($parents["HUSB"]))) {
					FindPersonRecord($parents["HUSB"]);
					$list[$parents["HUSB"]] = $indilist[$parents["HUSB"]];
					$list[$parents["HUSB"]]["generation"] = $list[$id]["generation"]+1;
				}
				if (!empty($parents["WIFE"]) && (PrivacyFunctions::DisplayDetailsByID($parents["WIFE"]) || PrivacyFunctions::showLivingNameByID($parents["WIFE"]))) {
					FindPersonRecord($parents["WIFE"]);
					$list[$parents["WIFE"]] = $indilist[$parents["WIFE"]];
					$list[$parents["WIFE"]]["generation"] = $list[$id]["generation"]+1;
				}
				if ($generations == -1 || $list[$id]["generation"]+1 < $generations) {
					if (!empty($parents["HUSB"])) array_push($genlist, $parents["HUSB"]);
					if (!empty($parents["WIFE"])) array_push($genlist, $parents["WIFE"]);
				}
				if ($children) {
					$famrec = FindFamilyRecord($famid);
					if ($famrec) {
						$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
						for($i=0; $i<$num; $i++) {
							if (PrivacyFunctions::DisplayDetailsByID($smatch[$i][1]) || PrivacyFunctions::showLivingNameByID($smatch[$i][1])) {
								FindPersonRecord($smatch[$i][1]);
								$list[$smatch[$i][1]] = $indilist[$smatch[$i][1]];
								if (isset($list[$id]["generation"])) $list[$smatch[$i][1]]["generation"] = $list[$id]["generation"];
								else $list[$smatch[$i][1]]["generation"] = 1;
							}
						}
					}
				}
			}
		}
	}
}

function FindPrimaryFamilyId($pid, $indirec="", $newfams=false) {
	
    $resultarray = array();
    $famids = FindFamilyIds($pid,$indirec,$newfams);
    if (count($famids)>1) {
        $priority = array();
        foreach ($famids as $indexval => $ffamid) {
            if (!isset($priority["first"])) $priority["first"]=$indexval;
            $priority["last"]=$indexval;
            if ($ffamid["primary"]=='Y') {
				if (!isset($priority["primary"])) $priority["primary"]=$indexval;
            }

            $relation = $ffamid["relation"];
            switch ($relation) {
            case "adopted":
            case "foster": // Sometimes called "guardian"
            case "sealing":
                // nothing to do
                break;
            default: // Should be "". Sometimes called "birth","biological","challenged","disproved"
                $relation = "birth";
                break;
            }
            // in the future, we could use $ffamid["stat"]
            // to further prioritize the family relation:
            // "challenged", "disproven", ""/"proven"

            // only store the first occurance of this type of family
            if (!isset($priority[$relation])) $priority[$relation]=$indexval;
        }

        // get the actual family array according to the following priority
        // at least one of these will get some results.
        if (isset($priority["primary"])) $resultarray[]=$famids[$priority["primary"]];
        else if (isset($priority["birth"])) $resultarray[]=$famids[$priority["birth"]];
        else if (isset($priority["adopted"])) $resultarray[]=$famids[$priority["adopted"]];
        else if (isset($priority["foster"])) $resultarray[]=$famids[$priority["foster"]];
        else if (isset($priority["sealing"])) $resultarray[]=$famids[$priority["sealing"]];
        else if (isset($priority["first"])) $resultarray[]=$famids[$priority["first"]];
        else if (isset($priority["last"])) $resultarray[]=$famids[$priority["last"]];
  		return $resultarray;
    }
    else return $famids;
}

//--- copied from reportpdf.php
function AddDescendancy($pid, $parents=false, $generations=-1) {
	global $list, $indilist;

	if (!isset($list[$pid])) {
		FindPersonRecord($pid);
		$list[$pid] = $indilist[$pid];
	}
	if (!isset($list[$pid]["generation"])) {
		$list[$pid]["generation"] = 0;
	}
	$famids = FindSfamilyIds($pid);
	if (count($famids)>0) {
		foreach($famids as $indexval => $famid) {
			$famrec = FindFamilyRecord($famid["famid"]);
			if ($famrec && PrivacyFunctions::DisplayDetailsByID($famid["famid"], "FAM")) {
				if ($parents) {
					$parents = FindParentsInRecord($famrec);
					if (!empty($parents["HUSB"]) && (PrivacyFunctions::DisplayDetailsByID($parents["HUSB"]) || PrivacyFunctions::showLivingNameByID($parents["HUSB"]))) {
						FindPersonRecord($parents["HUSB"]);
						$list[$parents["HUSB"]] = $indilist[$parents["HUSB"]];
						if (isset($list[$pid]["generation"])) $list[$parents["HUSB"]]["generation"] = $list[$pid]["generation"]-1;
						else $list[$parents["HUSB"]]["generation"] = 1;
					}
					if (!empty($parents["WIFE"]) && (PrivacyFunctions::DisplayDetailsByID($parents["WIFE"]) || PrivacyFunctions::showLivingNameByID($parents["WIFE"]))) {
						FindPersonRecord($parents["WIFE"]);
						$list[$parents["WIFE"]] = $indilist[$parents["WIFE"]];
						if (isset($list[$pid]["generation"])) $list[$parents["WIFE"]]["generation"] = $list[$pid]["generation"]-1;
						else $list[$parents["HUSB"]]["generation"] = 1;
					}
				}
				$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
				for($i=0; $i<$num; $i++) {
					FindPersonRecord($smatch[$i][1]);
					$list[$smatch[$i][1]] = $indilist[$smatch[$i][1]];
					if (isset($list[$smatch[$i][1]]["generation"])) $list[$smatch[$smatch[$i][1]][1]]["generation"] = $list[$pid]["generation"]+1;
					else $list[$smatch[$i][1]]["generation"] = 2;
				}
				if($generations == -1 || $list[$pid]["generation"]+1 < $generations)
				{
					for($i=0; $i<$num; $i++) {
						AddDescendancy($smatch[$i][1], $parents, $generations);	// recurse on the childs family
					}
				}
			}
		}
	}
}

function ExtractFullpath($mediarec) {
	preg_match("/(\d) _*FILE (.*)/", $mediarec, $amatch);
	if (empty($amatch[2])) return "";
	$level = trim($amatch[1]);
	$fullpath = trim($amatch[2]);
	$filerec = GetSubRecord($level, $amatch[0], $mediarec);
	$fullpath .= GetCont($level+1, $filerec);
	return $fullpath;
}

/**
 * get the relative filename for a media item
 *
 * gets the relative file path from the full media path for a media item.  checks the
 * <var>$MEDIA_DIRECTORY_LEVELS</var> to make sure the directory structure is maintained.
 * @param string $fullpath the full path from the media record
 * @return string a relative path that can be appended to the <var>$MEDIA_DIRECTORY</var> to reference the item
 */
function ExtractFilename($fullpath) {

	$filename="";
	$regexp = "'[/\\\]'";
	$srch = "/".addcslashes(GedcomConfig::$MEDIA_DIRECTORY,'/.')."/";
	$repl = "";
	if (!strstr($fullpath, "://")) $nomedia = stripcslashes(preg_replace($srch, $repl, $fullpath));
	else $nomedia = $fullpath;
	$ct = preg_match($regexp, $nomedia, $match);
	if ($ct>0) {
		$subelements = preg_split($regexp, $nomedia);
		$subelements = array_reverse($subelements);
		$max = GedcomConfig::$MEDIA_DIRECTORY_LEVELS;
		if ($max>=count($subelements)) $max=count($subelements)-1;
		for($s=$max; $s>=0; $s--) {
			if ($s!=$max) $filename = $filename."/".$subelements[$s];
			else $filename = $subelements[$s];
		}
	}
	else $filename = $nomedia;
	return $filename;
}


function findImageSize($file) {
	if (strtolower(substr($file, 0, 7)) == "http://")
		$file = "http://" . rawurlencode(substr($file, 7));
	else
		$file = FilenameDecode($file);
	$imgsize = @ getimagesize($file);
	if (!$imgsize) {
		$imgsize[0] = 300;
		$imgsize[1] = 300;
		$imgsize[2] = false;
	}
	return $imgsize;
}

?>
