<?php
/**
 * Functions used for charts
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
 * @subpackage Charts
 * @version $Id: functions_chart_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class ChartFunctions {
	/**
	 * print a table cell with sosa number
	 *
	 * @param int $sosa
	 * @param string $pid optional pid
	 */
	public function PrintSosaNumber($sosa, $pid = "") {
		
		print "<td class=\"SubHeader\" style=\"vertical-align: middle; white-space: nowrap;\">";
		print $sosa;
		if ($sosa != "1") {
			print "<br />";
			$url = GetQueryString();
			$srch = "rootid=".$_REQUEST["rootid"];
			$repl = "rootid=".$pid;
			$url = "?".str_replace($srch, $repl, $url);
			$url = str_replace("&amp;", "&", $url);
			$url = str_replace("&", "&amp;", $url);
			self::PrintUrlArrow($pid, $url, "#$pid");
			print "&nbsp;";
		}
		print "</td>";
	}
	
	
	/**
	 * print a family with Sosa-Stradonitz numbering system
	 * ($rootid=1, father=2, mother=3 ...)
	 *
	 * @param string $famid family gedcom ID
	 * @param string $childid tree root ID
	 * @param string $sosa starting sosa number
	 * @param string $label optional indi label (descendancy booklet)
	 * @param string $parid optional parent ID (descendancy booklet)
	 * @param string $gparid optional gd-parent ID (descendancy booklet)
	 */
	public function PrintSosaFamily(&$family, $childid, $sosa, $label="", $parid="", $gparid="", $view="") {
		global $pbwidth, $pbheight, $view;
	
		if ($view != "preview") print "<hr />";
		print "\r\n\r\n<p style='page-break-before:always' />\r\n";
		print "<a name=\"".$family->xref."\"></a>\r\n";
		
		print "<p class=\"PageTitleName\">".$family->name;
		if ($family->addname != "") print "<br />".$family->addname;
		print "</p>\r\n";
		
		PersonFunctions::PrintFamilyParents($family, $sosa, $label, $parid, $gparid, $view);
		print "\n\t<br />\n";
		print "<table width=\"95%\"><tr><td valign=\"top\" style=\"width: " . ($pbwidth) . "px;\">\n";
		PersonFunctions::PrintFamilyChildren($family, $childid, $sosa, $label, $view);
		print "</td><td valign=\"top\">";
		print "</td></tr></table>\n";
		print "<br />";
	}
	
	/**
	 * check root id for pedigree tree
	 *
	 * @param string $rootid root ID
	 * @return string $rootid validated root ID
	 */
	public function CheckRootId($rootid) {
		global $gm_user;
		
		// -- if the $rootid is not already there then find the first person in the file and make him the root
		if (empty($rootid) &&!empty($gm_user->rootid[GedcomConfig::$GEDCOMID])) {
			$person =&Person::GetInstance($gm_user->rootid[GedcomConfig::$GEDCOMID], "", GedcomConfig::$GEDCOMID);
			if (!$person->isempty) $rootid = $gm_user->rootid[GedcomConfig::$GEDCOMID];
		}
		if (empty($rootid) &&!empty($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) {
			$person =&Person::GetInstance($gm_user->gedcomid[GedcomConfig::$GEDCOMID], "", GedcomConfig::$GEDCOMID);
			if (!$person->isempty) $rootid = $gm_user->gedcomid[GedcomConfig::$GEDCOMID];
		}
			
		// -- allow users to overide default id in the config file.
		if (empty($rootid)) {
			GedcomConfig::$PEDIGREE_ROOT_ID = trim(GedcomConfig::$PEDIGREE_ROOT_ID);
			if (!empty(GedcomConfig::$PEDIGREE_ROOT_ID)) {
				$person =&Person::GetInstance(GedcomConfig::$PEDIGREE_ROOT_ID, "", GedcomConfig::$GEDCOMID);
				if (!$person->isempty) $rootid = GedcomConfig::$PEDIGREE_ROOT_ID;
			}
		}
		if (empty($rootid)) $rootid = self::FindFirstPerson();
		
		if (GedcomConfig::$USE_RIN) {
			$person =&Person::GetInstance($rootid, "", GedcomConfig::$GEDCOMID);
			if ($person->isempty) $rootid = FindRinId($rootid);
		} 
		else {
			if (preg_match("/[A-Za-z]+/", $rootid) == 0) {
				GedcomConfig::$GEDCOM_ID_PREFIX = trim(GedcomConfig::$GEDCOM_ID_PREFIX);
				$rootid = GedcomConfig::$GEDCOM_ID_PREFIX . $rootid;
			}
		}
	
		return strtoupper($rootid);
	}
	
	/**
	 * creates an array with all of the individual ids to be displayed on an ascendancy chart
	 *
	 * the id in position 1 is the root person.  The other positions are filled according to the following algorithm
	 * if an individual is at position $i then individual $i's father will occupy position ($i*2) and $i's mother
	 * will occupy ($i*2)+1
	 *
	 * @param string $rootid
	 * @return array $treeid
	 */
	public function AncestryArray($rootid, $num_gens) {
		
		// -- maximum size of the id array
		$treesize = pow(2, ($num_gens + 1));
	
		$treeid = array();
		$treeid[0] = "";
		$treeid[1] = $rootid;
		// -- fill in the id array
		for($i = 1; $i < ($treesize / 2); $i++) {
			$treeid[($i * 2)] = false; // -- father
			$treeid[($i * 2) + 1] = false; // -- mother
			$parents = false;
			if (!empty($treeid[$i])) {
				print " ";
				$person =& Person::GetInstance($treeid[$i]);
				$family = $person->primaryfamily;
				if ($person->childfamilies != "") {
					$family =& Family::GetInstance($person->primaryfamily);
					if ($family->husb_id != "") $treeid[($i * 2)] = $family->husb_id; 	 // -- set father id
					if ($family->wife_id != "") $treeid[($i * 2) + 1] = $family->wife_id; // -- set mother id
				}
			}
		}
	//	print_r($treeid);
		return $treeid;
	}
	
	/**
	 * creates an array with all of the individual ids to be displayed on the pedigree chart
	 *
	 * the id in position 0 is the root person.  The other positions are filled according to the following algorithm
	 * if an individual is at position $i then individual $i's father will occupy position ($i*2)+1 and $i's mother
	 * will occupy ($i*2)+2
	 *
	 * @param string $rootid
	 * @return array $treeid
	 */
	public function PedigreeArray($rootid, $num_gens, $gens_split=-1) {
		
		// -- maximum size of the id array is 2^$PEDIGREE_GENERATIONS - 1
		$treesize = pow(2, (int)($num_gens))-1;
		if ($gens_split == -1) $gens_split = $treesize;
		else $gens_split = pow(2, (int)($gens_split))-1;
	
		$treeid = array();
		$treeid[0] = $rootid;
		// -- fill in the id array
		for($i = 0; $i < ($treesize / 2); $i++) {
			if (!empty($treeid[$i])) {
				print " ";
				$person =& Person::GetInstance($treeid[$i]);
				$famids = $person->childfamilies;
				if (count($famids) > 0) {
					$parents = false;
					$wife = null;
					$husb = null;
					// First see if there is a primary family
					foreach($famids as $famid=>$family) {
						if (is_object($family) && $family->showprimary) {
							if ($i < $gens_split) $wife = $family->wife;
							$husb = $family->husb;
							if (is_object($wife) || is_object($husb)) {
								$parents = true;
								break;
							}
						}
					}
					// If no primary found, take the first fam with at least one parent
					if (!$parents) {
						foreach($famids as $famid=>$family) {
							if (is_object($family)) {
								if ($i < $gens_split)$wife = $family->wife;
								$husb = $family->husb;
								if (is_object($wife) || is_object($husb)) {
									$parents = true;
									break;
								}
							}
						}
					}
	
					if ($parents) {
						if (is_object($husb)) $treeid[($i * 2) + 1] = $husb->xref; // -- set father id
						else $treeid[($i * 2) + 1] = false;
						if (is_object($wife)) $treeid[($i * 2) + 2] = $wife->xref; // -- set mother id
						else $treeid[($i * 2) + 2] = false;
					}
				} else {
					$treeid[($i * 2) + 1] = false; // -- father not found
					$treeid[($i * 2) + 2] = false; // -- mother not found
				}
			} else {
				$treeid[($i * 2) + 1] = false; // -- father not found
				$treeid[($i * 2) + 2] = false; // -- mother not found
			}
		}
		// -- detect the highest generation that actually has a person in it and use it for the pedigree generations
		if (!GedcomConfig::$SHOW_EMPTY_BOXES) {
			for($i = ($treesize-1); empty($treeid[$i]); $i--);
			$num_gens = ceil(log($i + 2) / log(2));
			if ($num_gens < 2) $num_gens = 2;
		}
		//print_r($treeid);
		return $treeid;
	}
	
	/**
	 * print an arrow to a new url
	 *
	 * @param string $id Id used for arrow img name (must be unique on the page)
	 * @param string $url target url
	 * @param string $label arrow label
	 * @param string $dir arrow direction 0=left 1=right 2=up 3=down (default=2)
	 */
	public function PrintUrlArrow($id, $url, $label, $dir=2) {
		global $view;
		global $GM_IMAGES;
		global $TEXT_DIRECTION;
	
		if ($id=="" or $url=="") return;
		if ($view=="preview") return;
	
		// arrow direction
		$adir=$dir;
		if ($TEXT_DIRECTION=="rtl" and $dir==0) $adir=1;
		if ($TEXT_DIRECTION=="rtl" and $dir==1) $adir=0;
	
		// arrow style		0		  1 		2		  3
		$array_style=array("larrow", "rarrow", "uarrow", "darrow");
		$astyle=$array_style[$adir];
	
		print "<a href=\"$url\" onmouseover=\"swap_image('".$astyle.$id."',$adir); window.status ='" . $label . "'; return true; \" onmouseout=\"swap_image('".$astyle.$id."',$adir); window.status=''; return true; \"><img id=\"".$astyle.$id."\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES[$astyle]["other"]."\" hspace=\"0\" vspace=\"0\" border=\"0\" alt=\"".$label."\" title=\"".$label."\" /></a>";
	}
	
	
	/**
	 * print cousins list
	 *
	 * @param string $famid family ID
	 */
	public function PrintCousins($family) {
		global $show_full, $bheight, $bwidth;
		global $GM_IMAGES;

		$save_show_full = $show_full;
		if ($save_show_full) {
			$bheight/=4;
			$bwidth-=40;
		}
		$show_full = false;
		print "<td style=\"vertical-align:middle\" height=\"100%\">";
		if ($family->children_count > 0) {
			print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" ><tr valign=\"middle\">";
			if ($family->children_count > 1) print "<td rowspan=\"".$family->children_count."\" style=\"vertical-align:middle;\" align=\"right\"><img width=\"3px\" height=\"". (($bheight+5) * ($family->children_count-1)) ."px\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."\" alt=\"\" /></td>";
			$i = 1;
			foreach ($family->children as $indexval => $chil) {
				print "<td style=\"vertical-align:middle;\"><img width=\"7px\" height=\"3px\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" alt=\"\" /></td><td style=\"vertical-align:middle;\">";
				PersonFunctions::PrintPedigreePerson($chil, 1, false, 0);
				print "</td></tr>";
				if ($i < $family->children_count) {
					print "<tr>";
					$i++;
				}
			}
			print "</table>";
		}
		$show_full = $save_show_full;
		if ($save_show_full) {
			$bheight*=4;
			$bwidth+=40;
		}
		print "</td>\n";
	}
	
	/**
	 * find and return the id of the first person in the gedcom
	 * @return string the gedcom xref id of the first person in the gedcom
	 */
	public function FindFirstPerson() {
		
		$sql = "SELECT i_id FROM ".TBLPREFIX."individuals WHERE i_file='".GedcomConfig::$GEDCOMID."' ORDER BY i_id LIMIT 1";
		$res = NewQuery($sql);
		$row = $res->FetchAssoc();
		$res->FreeResult();
		return $row["i_id"];
	}

}
?>
