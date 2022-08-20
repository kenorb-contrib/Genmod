<?php
/**
 * Temporary collection of functions needed for reports
 *
 * The functions in this file are common to all Genmod pages and include date conversion
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
 * @version $Id: functions_report_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class ReportFunctions {

	public function AddAncestors($pid, $children=false, $generations=-1) {
		global $list, $genlist;
	
		$genlist = array($pid);
		if (!isset($list[$pid])) $list[$pid] = Person::GetInstance($pid);
		$list[$pid]->generation = 1;
		
		while(count($genlist)>0) {
			$id = array_shift($genlist);
			$person =& Person::GetInstance($id);
			$famid = $person->primaryfamily;
			if (!empty($famid)) {
				$fam =& Family::GetInstance($famid);
				if ($fam->disp) {
					if (!$fam->husb_id == "" && $fam->husb->disp_name) {
						$list[$fam->husb_id] = $fam->husb;
						$list[$fam->husb_id]->generation = $list[$id]->generation+1;
					}
					if (!$fam->wife_id == "" && $fam->wife->disp_name) {
						$list[$fam->wife_id] = $fam->wife;
						$list[$fam->wife_id]->generation = $list[$id]->generation+1;
					}
					if ($generations == -1 || $list[$id]->generation+1 < $generations) {
						if (!$fam->husb_id == "") array_push($genlist, $fam->husb_id);
						if (!$fam->wife_id == "") array_push($genlist, $fam->wife_id);
					}
					if ($children) {
						foreach($fam->children as $key =>$child) {
							if ($child->disp) {
								$list[$child->xref] = $child;
								if (!is_null($list[$id]->generation)) $list[$child->xref]->generation = $list[$id]->generation;
								else $list[$child->xref]->generation = 1;
							}
						}
					}
				}
			}
		}
	}
	
	public function AddDescendancy($pid, $parents=false, $generations=-1) {
		global $list;
		if (!isset($list[$pid])) $list[$pid] = Person::GetInstance($pid);
	//	print "gen: ".$list[$pid]->generation." for ".$pid."<br />";
		
		if (is_null($list[$pid]->generation)) $list[$pid]->generation = 0;
	
		$person =& Person::GetInstance($pid);
		foreach($person->spousefamilies as $key => $fam) {
			if ($fam->disp) {
				if ($parents) {
					if (!$fam->husb_id == "" && $fam->husb->disp_name) {
						$list[$fam->husb_id] = $fam->husb;
						if (!is_null($list[$pid]->generation)) $list[$fam->husb_id]->generation = $list[$pid]->generation-1;
						else $list[$fam->husb_id]->generation = 1;
					}
					if (!$fam->wife_id == "" && $fam->wife->disp_name) {
						$list[$fam->wife_id] = $fam->wife;
						if (!is_null($list[$pid]->generation)) $list[$fam->wife_id]->generation = $list[$pid]->generation-1;
						else $list[$fam->wife_id]->generation = 1;
					}
				}
				foreach ($fam->children as $key2 => $child) {
					$list[$child->xref] = $child;
					$list[$child->xref]->generation = $list[$pid]->generation+1;
	//				if (!is_null($list[$child->xref]->generation)) $list[$child->xref]->generation = $list[$pid]->generation+1;
	//				else $list[$child->xref]->generation = 2;
				}
						
				if($generations == -1 || $list[$pid]->generation+1 < $generations) {
					foreach($fam->children as $key3 => $child) {
	//					print "call from ".$fam->xref." for ".$child->xref."<br />";
						self::AddDescendancy($child->xref, $parents, $generations);	// recurse on the childs family
					}
				}
			}
		}
	}
	
	public function ExtractFullpath($mediarec) {
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
	public function ExtractFilename($fullpath) {
	
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
	
	
	public function findImageSize($file) {
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
}
?>
