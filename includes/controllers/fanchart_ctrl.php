<?php
/**
 * Controller for the Fanchart
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
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: fanchart_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class FanchartController extends ChartController {
	
	public $classname = "FanchartController";	// Name of this class
	private $fan_style = null;					// Style of the fanchart (2 = 1/2, 3 = 3/4, 4 = 4/4)
	
	public function __construct() {
		
		parent::__construct();
		
		if (!isset($_REQUEST["num_generations"]) || $_REQUEST["num_generations"] == "") $this->num_generations = GedcomConfig::$DEFAULT_PEDIGREE_GENERATIONS;
		else $this->num_generations = $_REQUEST["num_generations"];
		
		if (!isset($_REQUEST["fan_style"]) || $_REQUEST["fan_style"] == "" || !is_numeric($_REQUEST["fan_style"])) $this->fan_style = 3;
		else $this->fan_style = $_REQUEST["fan_style"];
		

		if ($this->num_generations > GedcomConfig::$MAX_PEDIGREE_GENERATIONS) {
			$this->num_generations = GedcomConfig::$MAX_PEDIGREE_GENERATIONS;
			$this->max_generation = true;
		}
		
		if ($this->num_generations < 3) {
			$this->num_generations = 3;
			$this->min_generation = true;
		}
	}

	public function __get($property) {
		switch($property) {
			case "fan_style":
				return $this->fan_style;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = $this->GetRootObject()->name.$this->GetRootObject()->addxref;
			$this->pagetitle .= " - ".GM_LANG_fan_chart;
		}
		return $this->pagetitle;
	}

	public function PrintInputFanStyle() {
		print "<tr><td class=\"NavBlockLabel\">";
		PrintHelpLink("fan_style_help", "qm");
		print GM_LANG_fan_chart."</td>";
		print "<td class=\"NavBlockField\">";
		print "<input type=\"radio\" name=\"fan_style\" value=\"2\"";
		if ($this->fan_style == 2) print " checked=\"checked\"";
		print " /> 1/2";
		print "<br /><input type=\"radio\" name=\"fan_style\" value=\"3\"";
		if ($this->fan_style == 3) print " checked=\"checked\"";
		print " /> 3/4";
		print "<br /><input type=\"radio\" name=\"fan_style\" value=\"4\"";
		if ($this->fan_style == 4) print " checked=\"checked\"";
		print " /> 4/4</td></tr>";
	}
		
	public function PointLen($string) {
		global $cw;
		
		$len = 0;
		$slen = strlen($string);
		for ($i=0; $i<$slen; $i++) {
			//print "cw: ".ord(substr($string, $i,1))." waarde ".$cw[ord(substr($string, $i,1))]."<br />";
			if (isset($cw[ord(substr($string, $i,1))])) $len += $cw[ord(substr($string, $i,1))];
			else $len += 316;
		}
		return $len;
	}

	/**
	 * split and center text by lines
	 *
	 * @param string $data input string
	 * @param int $maxlen max length of each line
	 * @return string $text output string
	 */
	public function SplitAlignText($data, $maxlen) {
		global $RTLOrd;
	
		$maxpoints = 554 * $maxlen;
		$lines = explode("\r\n", $data);
		// more than 1 line : recursive calls
		if (count($lines)>1) {
			$text = "";
			foreach ($lines as $indexval => $line) $text .= $this->SplitAlignText($line, $maxlen)."\r\n";
			return $text;
		}
		// process current line word by word
		$split = explode(" ", $data);
		$text = "";
		$line = "";
		// do not split hebrew line
	
		$found = false;
		foreach($RTLOrd as $indexval => $ord) {
	    	if (strpos($data, chr($ord)) !== false) $found=true;
		}
		if ($found) $line=$data;
		else {
			foreach ($split as $indexval => $word) {
				$len = $this->PointLen($line);
				//if (!empty($line) and ord($line{0})==215) $len/=2; // hebrew text
				$wlen = $this->PointLen($word);
				// line too long ?
				if (($len + $wlen) < $maxpoints) {
					if (!empty($line)) $line .= " ";
					$line .= $word;
				}
				else {
					if (!empty($line)) {
						$text .= "$line\r\n";
					}
					$line = $word;
				}
			}
		}
		// last line
		if (!empty($line)) {
			$len = $this->Pointlen($line);
			$extra = max(0,floor(($maxpoints - $len)/2));
	//		print "puntlengte: ".$len." extra: ".$extra." ";
			while ($extra > 0) {
				$line = " ".$line." ";
				$len = $this->Pointlen($line);
				$extra = max(0,floor(($maxpoints - $len)/2));
			}
			$text .= $line;
		}
		return $text;
	}
	
	/**
	 * print ancestors on a fan chart
	 *
	 * @param array $treeid ancestry pid
	 * @param int $fanw fan width in px (default=640)
	 * @param int $fandeg fan size in deg (default=270)
	 */
	public function PrintFanChart($treeid, $fanw=640, $fandeg=270) {
		global $TEXT_DIRECTION;
		global $gm_user;
		global $GM_IMAGES, $cw;
	
		// check for GD 2.x library
		if (!defined("IMG_ARC_PIE")) {
			print "<span class=\"Error\">".GM_LANG_gd_library."</span>";
			print " <a href=\"" . GM_LANG_gd_helplink . "\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"Icon\" alt=\"\" /></a><br /><br />";
			return false;
		}
		if (!function_exists("ImageTtfBbox")) {
			print "<span class=\"Error\">".GM_LANG_gd_freetype."</span>";
			print " <a href=\"" . GM_LANG_gd_helplink . "\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"Icon\" alt=\"\" /></a><br /><br />";
			return false;
		}
	
		// parse CSS file
		include("includes/cssparser.inc.php");
		$css = new cssparser(false);
	    if ($this->view == "preview") $css->Parse(GM_PRINT_STYLESHEET);
	    else $css->Parse(GM_STYLESHEET);
	
	    // check for fontfile
		$fontfile = $css->Get(".fan_chart","font-family");
		$fontsize = $css->Get(".fan_chart","font-size");
		$fontfile = str_replace("url(", "", $fontfile);
		$fontfile = str_replace(")", "", $fontfile);
		print "\r\n<!-- trace start\r\n font-family\t=\t$fontfile\r\n font-size\t=\t$fontsize";
		print "\r\nDIRNAME($fontfile)\t=\t". dirname($fontfile);
		print "\r\ngetcwd()\t=\t". getcwd();
		print "\r\n-->";
		print "\r\n<!-- trace start\r\n font-family\t=\t$fontfile\r\n-->";
		if ($fontfile{0} == '/') $fontfile = substr($fontfile, 1);
		if (!file_exists($fontfile)) {
			print "<span class=\"Error\">".GM_LANG_fontfile_error." : $fontfile</span>";
			return false;
		}
		if (intval($fontsize)<2) $fontsize = 7;
		else $fontsize = intval($fontsize);
	//print "Fontsize: ".$fontsize."<br />";
	//print "Fontfile: ".basename($fontfile)."<br />";
		$ff = explode(".", basename($fontfile));
	//print_r($ff);
		require_once("fonts/".$ff[0].".php");
		print "\r\n<!-- trace start\r\n font-family\t=\t$fontfile\r\n font-size\t=\t$fontsize\r\n-->";
	
		$treesize=count($treeid);
		if ($treesize<1) return;
	
		// generations count
		$gen=log($treesize)/log(2)-1;
		$sosa=$treesize-1;
	
		// fan size
		if ($fandeg==0) $fandeg=360;
		$fandeg=min($fandeg, 360);
		$fandeg=max($fandeg, 90);
		$cx=$fanw/2-1; // center x
		$cy=$cx; // center y
		$rx=$fanw-1;
		$rw=$fanw/($gen+1);
		$fanh=$fanw; // fan height
		if ($fandeg==180) $fanh=round($fanh*($gen+1)/($gen*2));
		if ($fandeg==270) $fanh=round($fanh*.86);
		$scale=$fanw/640;
	
		// image init
		$image = ImageCreate($fanw, $fanh);
		$black = ImageColorAllocate($image, 0, 0, 0);
		$white = ImageColorAllocate($image, 0xFF, 0xFF, 0xFF);
		ImageFilledRectangle ($image, 0, 0, $fanw, $fanh, $white);
		ImageColorTransparent($image, $white);
	
		$rgb = $css->Get(".fan_chart", "color");
		if (empty($rgb)) $rgb = "#000000";
		$color = ImageColorAllocate($image, hexdec(substr($rgb,1,2)), hexdec(substr($rgb,3,2)), hexdec(substr($rgb,5,2)));
	
		$rgb = $css->Get(".fan_chart", "background-color");
		if (empty($rgb)) $rgb = "#EEEEEE";
		$bgcolor = ImageColorAllocate($image, hexdec(substr($rgb,1,2)), hexdec(substr($rgb,3,2)), hexdec(substr($rgb,5,2)));
	
		$rgb = $css->Get(".fan_chart_box", "background-color");
		if (empty($rgb)) $rgb = "#D0D0AC";
		$bgcolorM = ImageColorAllocate($image, hexdec(substr($rgb,1,2)), hexdec(substr($rgb,3,2)), hexdec(substr($rgb,5,2)));
	
		$rgb = $css->Get(".fan_chart_boxF", "background-color");
		if (empty($rgb)) $rgb = "#D0ACD0";
		$bgcolorF = ImageColorAllocate($image, hexdec(substr($rgb,1,2)), hexdec(substr($rgb,3,2)), hexdec(substr($rgb,5,2)));
	
		// imagemap
		$imagemap="<map id=\"fanmap\" name=\"fanmap\">";
	
		// relationship to me
		$reltome=false;
		if (!empty($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) $reltome=true;
	
		// loop to create fan cells
		while ($gen >= 0) {
			// clean current generation area
			$deg2=360+($fandeg-180)/2;
			$deg1=$deg2-$fandeg;
			ImageFilledArc($image, $cx, $cy, $rx, $rx, $deg1, $deg2, $bgcolor, IMG_ARC_PIE);
			$rx-=3;
	
			// calculate new angle
			$p2 = pow(2, $gen);
			$angle = $fandeg/$p2;
			$deg2 = 360 + ($fandeg - 180)/2;
			$deg1 = $deg2 - $angle;
			// special case for rootid cell
			if ($gen == 0) {
				$deg1 = 90;
				$deg2 = 360 + $deg1;
			}
	
			// draw each cell
			while ($sosa >= $p2) {
				$pid = $treeid[$sosa];
				if (!empty($pid)) {
					$person =& Person::GetInstance($pid);
	
					if ($sosa%2) $bg=$bgcolorF;
					else $bg=$bgcolorM;
					if ($sosa==1) {
						$bg=$bgcolor; // sex unknown
						if ($person->sex == "F") $bg=$bgcolorF;
						else if ($person->sex == "M") $bg=$bgcolorM;
					}
	//				print "<br />Arc: cx: ".$cx." cy: ".$cy." rx: ".$rx." ";
					ImageFilledArc($image, $cx, $cy, $rx, $rx, $deg1, $deg2, $bg, IMG_ARC_PIE);
	//				print "deg1 ".$deg1. " deg2 ".$deg2." rx ".$rx." ";
					$maxpix = sin(deg2rad($deg2-$deg1))*$rx/2;
					if ($gen == 0) $maxpix = $rx;
	//				print "strlen kan: ".$maxpix." ";
					$name = $person->name;
					$addname = $person->addname;
	
					$wmax = floor(($scale * $angle * 7) /$fontsize);
	//				if ($gen == 0) $wmax = min($wmax, 17*$scale);
	//				else $wmax = min($wmax, 35*$scale);
					$wmax = floor($maxpix/$fontsize);				
					
					$name = NameFunctions::AbbreviateName((NameFunctions::HasChinese($name) ? $addname : $name), $wmax);
	//print " ".strlen($name)." ".$wmax." ".$name."<br />";
					$text = ltr_string($name) . "\r\n" . (NameFunctions::HasChinese($person->name) ? "" : ltr_string($addname)."\r\n");
					if ($person->disp) {
						$ctb = preg_match("/2 DATE.*(\d\d\d\d)/", $person->brec, $matchb);
						$ctd = preg_match("/2 DATE.*(\d\d\d\d)/", $person->drec, $matchd);
						if ($ctb > 0 || $ctd > 0) {
							if ($ctb>0) $text .= trim($matchb[1]);
							$text .= "-";
							if ($ctd>0) $text .= trim($matchd[1]);
						}
					}
					$text = htmlspecialchars_decode($text);
					$text = preg_replace(array('/&lrm;/','/&rlm;/'), array('',''), $text);
					$text = strip_tags($text);
	
					// split and center text by lines
					$text = $this->SplitAlignText($text, $wmax);
	
					// text angle
					$tangle = 270-($deg1+$angle/2);
					if ($gen==0) $tangle=0;
	
					// calculate text position
					$bbox=ImageTtfBbox((double)$fontsize, 0, $fontfile, $text);
					$textwidth = $bbox[4];
					$deg = $deg1+.44;
					if ($deg2-$deg1>40) $deg = $deg1+($deg2-$deg1)/11;
					if ($deg2-$deg1>80) $deg = $deg1+($deg2-$deg1)/7;
					if ($deg2-$deg1>140) $deg = $deg1+($deg2-$deg1)/4;
					if ($gen==0) $deg=180;
					$rad=deg2rad($deg);
					$mr=($rx-$rw/4)/2;
	//				print "textwidth: ".$textwidth." mr: ".$mr." center x: ".$cx." center y: ".$cy;
	//				print "degree: ".$deg." tangle: ".$tangle." correctie x: ".(-$maxpix*cos(deg2rad($tangle))/2);
	//				if ($gen>0 and $deg2-$deg1>80) $mr=$rx/2;
					$tx = $cx + ($mr) * cos($rad);
					$ty = $cy - $mr * -sin($rad);
					if ($sosa==1) $ty-=$mr/2;
	//$tx = $tx + ($maxpix-$textwidth)*cos(deg2rad($tangle))/2;
	//$ty = $ty - ($maxpix-$textwidth)*sin(deg2rad($tangle))/2;
	
					// print text
					ImageTtfText($image, (double)$fontsize, $tangle, $tx, $ty, $color, $fontfile, $text);
					
					$imagemap .= "\r\n<area shape=\"poly\" coords=\"";
					// plot upper points
					$mr=$rx/2;
					$deg=$deg1;
					while ($deg<=$deg2) {
						$rad=deg2rad($deg);
						$tx=round($cx + ($mr) * cos($rad));
						$ty=round($cy - $mr * -sin($rad));
						$imagemap .= "$tx, $ty, ";
						$deg+=($deg2-$deg1)/6;
					}
					// plot lower points
					$mr=($rx-$rw)/2;
					$deg=$deg2;
					while ($deg>=$deg1) {
						$rad=deg2rad($deg);
						$tx=round($cx + ($mr) * cos($rad));
						$ty=round($cy - $mr * -sin($rad));
						$imagemap .= "$tx, $ty, ";
						$deg-=($deg2-$deg1)/6;
					}
					// join first point
					$mr=$rx/2;
					$deg=$deg1;
					$rad=deg2rad($deg);
					$tx=round($cx + ($mr) * cos($rad));
					$ty=round($cy - $mr * -sin($rad));
					$imagemap .= "$tx, $ty";
					
					// add action url
					$url = "javascript:// " . PrintReady(strip_tags($person->name.$person->addxref));
					$imagemap .= "\" href=\"$url\" ";
					$url = "?rootid=".$person->xref."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width."&amp;fan_style=".$this->fan_style;
					if ($this->view != "") $url .= "&amp;view=".$this->view;
					$count=0;
					$mousecode = " onmouseover=\"clear_family_box_timeout('".$person->xref.".".$count."');\" onmouseout=\"family_box_timeout('".$person->xref.".".$count."');\"";
					$lbwidth=250;
	//print "tx: ".$tx." ty ".$ty."<br />";				
					print "\n\t\t<div class=\"PersonBox".($person->sex=="F"?"F":($person->sex=="U"?"NN":""))." PersonBoxLinkBox\" id=\"I".$person->xref.".".$count."links\" style=\"position:absolute; left:".$tx."px; top:".$ty."px; width:".$lbwidth."px; visibility:hidden; z-index:'1000';\">";
						print "<div class=\"PersonBoxLinkBoxFamLinks\">";
							print "<a href=\"individual.php?pid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"".$mousecode." class=\"PersonName1\">" . PrintReady($person->name);
							if (!empty($person->addname)) print "<br />" . PrintReady($person->addname);
						print "</a><br /><br /></div>\n";
						print "<div class=\"PersonBoxLinkBoxChartLinks\">";
							print "<a href=\"pedigree.php?rootid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"".$mousecode.">".GM_LANG_index_header."</a>\n";
							print "<br /><a href=\"descendancy.php?rootid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"".$mousecode.">".GM_LANG_descend_chart."</a>\n";
							print "<br /><a href=\"ancestry.php?rootid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"".$mousecode.">".GM_LANG_ancestry_chart."</a>\n";
							print "<br /><a href=\"fanchart.php?rootid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width."&amp;fan_style=".$this->fan_style."\"".$mousecode.">".GM_LANG_fan_chart."</a>\n";
							print "<br /><a href=\"hourglass.php?pid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"".$mousecode.">".GM_LANG_hourglass_chart."</a>\n";
							print "<br /><a href=\"familybook.php?rootid=".$person->xref."&amp;gedid=".$person->gedcomid."\"".$mousecode.">".GM_LANG_familybook_chart."</a>\n";
							if ($reltome)  print "<br /><a href=\"relationship.php?pid1=".$gm_user->gedcomid[GedcomConfig::$GEDCOMID]."&amp;pid2=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"".$mousecode.">".GM_LANG_relationship_to_me."</a>\n";
							print "<br /><a href=\"timeline.php?pids0=".$person->xref."&amp;gedid=".$person->gedcomid."\">".GM_LANG_timeline_chart."</a>\n";
							print "<br /><a href=\"paternals.php?rootid=".$person->xref."&amp;gedid=".$person->gedcomid."\">".GM_LANG_paternal_chart."</a>\n";
						print "</div>";
						
						print "<div class=\"PersonBoxLinkBoxFamLinks\">";
	
						if ($sosa>=1) {
							$num=0;
							foreach($person->childfamilies as $key => $childfamily) {
								$num += $childfamily->children_count;
							}
							if (count($person->spousefamilies) > 0 || $num > 1) {
								//-- spouse(s) and children
								foreach($person->spousefamilies as $key2 => $spfamily) {
									if($spfamily->husb_id != "" && $spfamily->wife_id != "") {
										print "\n<span class=\"PersonBoxLinkBoxFamLinksFam\">".GM_LANG_fam_spouse."</span><br />";
										if ($person->xref != $spfamily->husb_id) $spid = $spfamily->husb_id;
										else $spid = $spfamily->wife_id;
										$linkurl = str_replace("id=".$person->xref, "id=".$spid, $url);
											// TODO: Fix links
										print "\n<a href=\"".$linkurl."\"".$mousecode." class=\"PersonBoxLinkBoxFamLinksIndi\">";
										print ($spid == $spfamily->husb_id ? $spfamily->husb->name : $spfamily->wife->name);
										print "</a>";
									}
									foreach ($spfamily->children as $key3 => $child) {
										$linkurl=str_replace("id=".$person->xref, "id=".$child->xref, $url);
										print "\n<br /><a href=\"$linkurl\"".$mousecode." class=\"PersonBoxLinkBoxFamLinksIndi PersonBoxLinkBoxIndent\">";
										print $child->name;
										print "</a>";
									}
								}
								//-- siblings
								foreach($person->childfamilies as $key => $chfamily) {
									if ($chfamily->children_count > 1) print "\n<br /><span class=\"PersonBoxLinkBoxFamLinksFam\">".GM_LANG_siblings."</span>";
									foreach ($chfamily->children as $key2 => $child) {
										if ($child->xref != $person->xref) {
											$linkurl = str_replace("id=".$person->xref, "id=".$child->xref, $url);
												// TODO: Fix links
											print "\n<br /><a href=\"$linkurl\"".$mousecode." class=\"PersonBoxLinkBoxFamLinksIndi PersonBoxLinkBoxIndent\"> ";
											print $child->name.$child->addxref;
											print "</a>";
										}
									}
								}
							}
						}
						print "</div>";
					print "</div>";
					$imagemap .= " onclick=\"show_family_box('".$person->xref.".".$count."', 'relatives'); return false;\"";
					$imagemap .= " onmouseout=\"family_box_timeout('".$person->xref.".".$count."'); return false;\"";
					$imagemap .= " alt=\"".PrintReady(strip_tags($person->name))."\" title=\"".PrintReady(strip_tags($person->name))."\" />";
				}
				$deg1-=$angle;
				$deg2-=$angle;
				$sosa--;
			}
			$rx-=$rw;
			$gen--;
		}
	
		$imagemap .= "\r\n</map>";
		print "\r\n".$imagemap;
	
		// GM banner ;-)
		ImageStringUp($image, 1, $fanw-10, $fanh/3, "www.sourceforge.net/projects/genmod", $color);
	
		// here we cannot send image to browser ('header already sent')
		// and we dont want to use a tmp file
	
		// step 1. save image data in a session variable
		ob_start();
		ImagePng($image);
		$image_data = ob_get_contents();
		ob_end_clean();
		$image_data = serialize($image_data);
		unset ($_SESSION['image_data']);
		$_SESSION['image_data']=$image_data;
	
		// step 2. call imageflush.php to read this session variable and display image
		// note: arg "image_name=" is to avoid image miscaching
		$image_name="V".time();
		unset($_SESSION[$image_name]);          // statisticsplot.php uses this to hold a file name to send to browser
		$image_title=preg_replace("~<.*>~", "", $name) . " " . GM_LANG_fan_chart;
		print "\r\n<div style=\"float: left;\">";
		print "<img src=\"imageflush.php?image_type=png&amp;image_name=".$image_name."\" width=\"".$fanw."\" height=\"".$fanh."\" border=\"0\" alt=\"".$image_title."\" title=\"".$image_title."\" usemap=\"#fanmap\" />";
		print "\r\n</div>\r\n";
		ImageDestroy($image);
	}

}
?>