<?php
/**
 * Controller for the timeline chart
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
 * @version $Id$
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Main controller class for the timeline page.
 */
class TimelineController extends BaseController {
	
	public $classname = "TimelineController";
	public $bheight = 30;
	public $placements = array();
	public $familyfacts = array();
	public $indifacts = array();						// array to store the fact records in for sorting and displaying
	public $birthyears=array();
	public $birthmonths=array();
	public $birthdays=array();
	public $baseyear=0;
	public $topyear=0;
	public $pids = array();
	public $people = array();
	public $pidlinks = "";
	public $scale = 2;
	
	/**
	 * constructor
	 */
	public function __construct() {
		global $nonfamfacts, $nonfacts;
		
		parent::__construct();
		
		// GEDCOM elements that will be found but should not be displayed
		$nonfamfacts = array("_UID", "RESN", "CHAN");
		$nonfacts = array("FAMS", "FAMC", "MAY", "BLOB", "OBJE", "SEX", "NAME", "SOUR", "NOTE", "BAPL", "ENDL", "SLGC", "SLGS", "_TODO", "HUSB", "WIFE", "CHIL", "CHAN");
		
		$this->baseyear = date("Y");
		// NOTE: New pid
		if (isset($_REQUEST['newpid'])) {
			$newpid = CleanInput($_REQUEST['newpid']);
		}
		// NOTE: pids array
		$i = 0;
		while(isset($_REQUEST["pids".$i])) {
			$this->pids[] = $_REQUEST["pids".$i];
			$i++;
		}
		if (!empty($newpid)) $this->pids[] = $newpid;
		if (count($this->pids) == 0) $this->pids[] = ChartFunctions::CheckRootId("");
		
		//-- make sure that arrays are indexed by numbers
		$this->pids = array_values($this->pids);
		$remove = "";
		if (!empty($_REQUEST['remove'])) $remove = $_REQUEST['remove'];
		//-- cleanup user input
		foreach($this->pids as $key=>$value) {
			if ($value!=$remove) {
				$value = CleanInput($value);
				$this->pids[$key] = $value;
				$this->people[] =& Person::GetInstance($value);
			}
		}
		
		$this->pidlinks = "";
		foreach($this->people as $p => $indi) {
			if (!is_null($indi) && $indi->disp) {
				//-- setup string of valid pids for links
				$this->pidlinks .= "pids[]=".$indi->xref."&amp;";
				$bdate = $indi->bdate;
				if (!empty($bdate) && (stristr($bdate, "hebrew")===false)) {
					$date = ParseDate($bdate);
					if (!empty($date[0]["year"])) {
						$this->birthyears[$indi->xref] = $date[0]["year"];
						if (!empty($date[0]["mon"])) $this->birthmonths[$indi->xref] = $date[0]["mon"];
						else $this->birthmonths[$indi->xref] = 1;
						if (!empty($date[0]["day"])) $this->birthdays[$indi->xref] = $date[0]["day"];
						$this->birthdays[$indi->xref] = 1;
					}
				}
				// find all the fact information
				$indi->AddFamilyFacts(false);
				foreach($indi->facts as $indexval => $factobj) {
					// We must be able to display the full fact (timeline is about dates)
					if (!in_array($factobj->fact, $nonfacts) && $factobj->show && $factobj->disp) {
						//-- check for a date
						if ($factobj->datestring != "") {
							$date = ParseDate($factobj->datestring);
							//-- do not print hebrew dates
							if ((stristr($date[0]["ext"], "hebrew")===false)&&($date[0]["year"]!=0)) {
								if ($date[0]["year"]<$this->baseyear) $this->baseyear=$date[0]["year"];
								if ($date[0]["year"]>$this->topyear) $this->topyear=$date[0]["year"];
								if (!$indi->isdead) {
									if ($this->topyear < date("Y")) $this->topyear = date("Y");
								}
								$tfact = array();
								$tfact["p"] = $p;
								$tfact["pid"] = $indi->xref;
								$tfact[1] = $factobj;
								$this->indifacts[] = $tfact;
							}
						}
					}
				}
			}
		}
		
		if (empty($_REQUEST['scale'])) {
			$this->scale = round(($this->topyear-$this->baseyear)/20 * count($this->indifacts)/4);
			if ($this->scale<6) $this->scale = 6;
		}
		else $this->scale = $_REQUEST['scale'];
		if ($this->scale<2) $this->scale=2;
		
		$this->baseyear -= 5;
		$this->topyear += 5;
	}
	
	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = "";
			if (GedcomConfig::$SHOW_ID_NUMBERS) {
				foreach($this->people as $p=>$indi) {
					if ($this->pagetitle != "") $this->pagetitle .= '/';
					$this->pagetitle .= $indi->xref;
				}
				if ($this->pagetitle != "") $this->pagetitle .= " - ";
			}
			$this->pagetitle .= GM_LANG_timeline_title;
		}
		return $this->pagetitle;
	}
	
	/**
	 * check the privacy of the incoming people to make sure they can be shown
	 */
	public function checkPrivacy() {
		
		$printed = false;
		for($i=0; $i<count($this->people); $i++) {
			if (!$this->people[$i]->disp) {
				if ($this->people[$i]->disp_name) {
					print "&nbsp;<a href=\"individual.php?pid=".$this->people[$i]->xref."\">".PrintReady($this->people[$i]->name)."</a>";
					PrintFunctions::PrintPrivacyError(GedcomConfig::$CONTACT_EMAIL);
					print "<br />";
					$printed = true;
				}
				else if (!$printed) {
					PrintFunctions::PrintPrivacyError(GedcomConfig::$CONTACT_EMAIL);
					print "<br />";
				}
			}
		}
	}
	
	public function PrintTimeFact($factitem) {
		global $basexoffset, $baseyoffset, $factcount, $TEXT_DIRECTION;
		global $GM_IMAGES;
	
		$factobj = $factitem[1];
		//-- check if this is a family fact
		$ct = preg_match("/1 _GMFS @(.*)@/", $factobj->factrec, $fmatch);
		if ($ct>0) {
			$famid = trim($fmatch[1]);
			//-- if we already showed this family fact then don't print it
			if (isset($this->familyfacts[$famid.$factobj->fact]) && $this->familyfacts[$famid.$factobj->fact] != $factitem["p"]) return;
			$this->familyfacts[$famid.$factobj->fact] = $factitem["p"];
		}
		$date = ParseDate($factobj->datestring);
		$year = $date[0]["year"];
	
		$month = $date[0]["mon"];
		$day = $date[0]["day"];
		$xoffset = $basexoffset+20;
		$yoffset = $baseyoffset+(($year-$this->baseyear) * $this->scale)-($this->scale);
		$yoffset = $yoffset + (($month / 12) * $this->scale);
		$yoffset = $yoffset + (($day / 30) * ($this->scale/12));
		$yoffset = floor($yoffset);
		$place = round($yoffset / $this->bheight);
		$i=1;
		$j=0;
		$tyoffset = 0;
		while(isset($this->placements[$place])) {
			if ($i==$j) {
				$tyoffset = $this->bheight * $i;
				$i++;
			}
			else {
				$tyoffset = -1 * $this->bheight * $j;
				$j++;
			}
			$place = round(($yoffset+$tyoffset) / ($this->bheight));
		}
		$yoffset += $tyoffset;
		$xoffset += abs($tyoffset);
		$this->placements[$place] = $yoffset;
		//-- do not print hebrew dates
		if (($date[0]["year"]!=0)&&(stristr($date[0]["ext"], "hebrew")===false)) {
			$thisperson =& $factobj->owner;
			print "\n\t\t<div id=\"fact$factcount\" style=\"position:absolute; ".($TEXT_DIRECTION =="ltr"?"left: ".($xoffset):"right: ".($xoffset))."px; top:".($yoffset)."px; font-size: 8pt; height: ".($this->bheight)."px; \" onmousedown=\"factMD(this, '".$factcount."', ".($yoffset-$tyoffset).");\">\n";
			print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"cursor: hand;\"><tr><td>\n";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" name=\"boxline$factcount\" id=\"boxline$factcount\" height=\"3\" align=\"left\" hspace=\"0\" width=\"10\" vspace=\"0\" alt=\"\" />\n";
			$col = $factitem["p"] % 6;
			print "</td><td valign=\"top\" class=\"person".$col."\">\n";
			if (count($this->pids) > 6)	print $thisperson->name." - ";
			print $factobj->descr;
			print "--";
			print "<span class=\"date\">".GetChangedDate($factobj->datestring)."</span> ";
			if (GedcomConfig::$SHOW_PEDIGREE_PLACES > 0) {
				$factobj->PrintFactPlace(false, false, false, true);
			}
			$age = $thisperson->GetAge($factobj->datestring);
			if (!empty($age)) print $age;
			//-- print spouse name for marriage events
			$ct = preg_match("/1 _GMS @(.*)@/", $factobj->factrec, $match);
			if ($ct > 0) {
				$spouse = $match[1];
				if ($spouse != "") {
					for($p = 0; $p < count($this->pids); $p++) {
						if ($this->pids[$p] == $spouse) break;
					}
					if ($p == count($this->pids)) $p = $factitem["p"];
					$col = $p % 6;
					$spouse =& Person::GetInstance($spouse);
					print " <span class=\"person".$col."\"> <a href=\"individual.php?pid=".$spouse->xref."&amp;gedid=".$spouse->gedcomid."\">";
					print $spouse->name;
					print "</a> </span>";
				}
			}
			print "</td></tr></table>\n";
			print "</div>";
			if ($TEXT_DIRECTION=='ltr') {
				$img = "dline2";
				$ypos = "0%";
			}
			else {
				$img = "dline";
				$ypos = "100%";
			}
			$dyoffset = ($yoffset-$tyoffset)+$this->bheight/3;
			if ($tyoffset<0) {
				$dyoffset = $yoffset+$this->bheight/3;
				if ($TEXT_DIRECTION=='ltr') {
					$img = "dline";
					$ypos = "100%";
				}
				else {
					$img = "dline2";
					$ypos = "0%";
				}
			}
			//-- print the diagnal line
			print "\n\t\t<div id=\"dbox$factcount\" style=\"position:absolute; ".($TEXT_DIRECTION =="ltr"?"left: ".($basexoffset+20):"right: ".($basexoffset+20))."px; top:".($dyoffset)."px; font-size: 8pt; height: ".(abs($tyoffset))."px; width: ".(abs($tyoffset))."px;";
			print " background-image: url('".GM_IMAGE_DIR."/".$GM_IMAGES[$img]["other"]."');";
			print " background-position: 0% $ypos; \" >\n";
			print "</div>\n";
		}
	}
}
?>