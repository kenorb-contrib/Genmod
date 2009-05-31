<?php
/**
 * Displays a fan chart
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
 * This Page Is Valid XHTML 1.0 Transitional! > 23 August 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: fanchart.php,v 1.29 2009/03/25 16:53:52 sjouke Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the chart functions
*/
require("includes/functions/functions_charts.php");

function PointLen($string) {
	global $cw;
	
	$len = 0;
	$slen = strlen($string);
	for ($i=0; $i<$slen; $i++) {
		$len += $cw[ord(substr($string, $i,1))];
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
function split_align_text($data, $maxlen) {
	global $RTLOrd;

	$maxpoints = 554 * $maxlen;
	$lines = explode("\r\n", $data);
	// more than 1 line : recursive calls
	if (count($lines)>1) {
		$text = "";
		foreach ($lines as $indexval => $line) $text .= split_align_text($line, $maxlen)."\r\n";
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
	else
	foreach ($split as $indexval => $word) {
		$len = PointLen($line);
		//if (!empty($line) and ord($line{0})==215) $len/=2; // hebrew text
		$wlen = PointLen($word);
		// line too long ?
		if (($len+$wlen)<$maxpoints) {
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
	// last line
	if (!empty($line)) {
		$len = Pointlen($line);
		$extra = max(0,floor(($maxpoints - $len)/2));
		print "puntlengte: ".$len." extra: ".$extra." ";
		while ($extra > 0) {
			$line = " ".$line;
			$len = Pointlen($line);
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
function print_fan_chart($treeid, $fanw=640, $fandeg=270) {
	global $PEDIGREE_GENERATIONS, $fan_width, $fan_style, $cw, $fontsize;
	global $name, $gm_lang, $SHOW_ID_NUMBERS, $view, $TEXT_DIRECTION;
	global $stylesheet, $print_stylesheet, $gm_username, $Users;
	global $GM_IMAGE_DIR, $GM_IMAGES, $LINK_ICONS, $GEDCOM;

	// check for GD 2.x library
	if (!defined("IMG_ARC_PIE")) {
		print "<span class=\"error\">".$gm_lang["gd_library"]."</span>";
		print " <a href=\"" . $gm_lang["gd_helplink"] . "\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"icon\" alt=\"\" /></a><br /><br />";
		return false;
	}
	if (!function_exists("ImageTtfBbox")) {
		print "<span class=\"error\">".$gm_lang["gd_freetype"]."</span>";
		print " <a href=\"" . $gm_lang["gd_helplink"] . "\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"icon\" alt=\"\" /></a><br /><br />";
		return false;
	}

	// parse CSS file
	include("includes/cssparser.inc.php");
	$css = new cssparser(false);
    if ($view=="preview") $css->Parse($print_stylesheet);
    else $css->Parse($stylesheet);

    // check for fontfile
	$fontfile = $css->Get(".fan_chart","font-family");
	$fontsize = $css->Get(".fan_chart","font-size");
	$fontfile = str_replace("url(", "", $fontfile);
	$fontfile = str_replace(")", "", $fontfile);
	print "\r\n<!-- trace start\r\n font-family\t=\t$fontfile\r\n font-size\t=\t$fontsize";
	print "\r\nDIRNAME(__FILE__)\t=\t". dirname(__FILE__);
	print "\r\ngetcwd()\t=\t". getcwd();
	print "\r\n-->";
	if (!file_exists($fontfile)) {
		if (!empty($fontfile)) print "<span class=\"error\">".$gm_lang["fontfile_error"]." : $fontfile</span>";
		$fontfile="./fonts/LucidaSansRegular.ttf";
	}
	print "\r\n<!-- trace start\r\n font-family\t=\t$fontfile\r\n-->";
	if ($fontfile{0}!='/') $fontfile = dirname(__FILE__) . "/" . $fontfile;
	if (!file_exists($fontfile)) {
		print "<span class=\"error\">".$gm_lang["fontfile_error"]." : $fontfile</span>";
		return false;
	}
	if (intval($fontsize)<2) $fontsize = 7;
	else $fontsize = intval($fontsize);
print "Fontsize: ".$fontsize."<br />";
print "Fontfile: ".basename($fontfile)."<br />";
$ff = explode(".", basename($fontfile));
print_r($ff);
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
	$username = $gm_username;
	if (!empty($username)) {
		$tuser = $Users->getUser($username);
		if (!empty($tuser->gedcomid[$GEDCOM])) $reltome=true;
	}

	// loop to create fan cells
	while ($gen>=0) {
		// clean current generation area
		$deg2=360+($fandeg-180)/2;
		$deg1=$deg2-$fandeg;
		ImageFilledArc($image, $cx, $cy, $rx, $rx, $deg1, $deg2, $bgcolor, IMG_ARC_PIE);
		$rx-=3;

		// calculate new angle
		$p2=pow(2, $gen);
		$angle=$fandeg/$p2;
		$deg2=360+($fandeg-180)/2;
		$deg1=$deg2-$angle;
		// special case for rootid cell
		if ($gen==0) {
			$deg1=90;
			$deg2=360+$deg1;
		}

		// draw each cell
		while ($sosa >= $p2) {
			$pid=$treeid[$sosa];
			if (!empty($pid)) {
				$indirec=FindPersonRecord($pid);

				if ($sosa%2) $bg=$bgcolorF;
				else $bg=$bgcolorM;
				if ($sosa==1) {
					$bg=$bgcolor; // sex unknown
					if (preg_match("/1 SEX F/", $indirec)>0) $bg=$bgcolorF;
					else if (preg_match("/1 SEX M/", $indirec)>0) $bg=$bgcolorM;
				}
				print "<br />Arc: cx: ".$cx." cy: ".$cy." rx: ".$rx." ";
				ImageFilledArc($image, $cx, $cy, $rx, $rx, $deg1, $deg2, $bg, IMG_ARC_PIE);
				$maxpix = sin(deg2rad($deg2-$deg1))*$rx/2;
				print "strlen kan: ".$maxpix." ";
				if (!showLivingNameByID($pid)) {
					$name = $gm_lang["private"];
					$addname = "";
				}
				else {
					$name = GetPersonName($pid);
					$addname = GetAddPersonName($pid);
				}

				$wmax = floor($angle*7/$fontsize*$scale);
				$wmax = min($wmax, 35*$scale);
				if ($gen==0) $wmax = min($wmax, 17*$scale);
$wmax = floor($maxpix/$fontsize);				
				
				$altname = $name; // Full name for alt
				$name = AbbreviateName($name, $wmax);
print " ".strlen($altname)." ".$wmax." ".$name." ";
				$text = ltr_string($name) . "\r\n" . ltr_string($addname). "\r\n";
				if (displayDetailsByID($pid)) {
					$birthrec = GetSubRecord(1, "1 BIRT", $indirec);
					if (!FactViewRestricted($pid, $birthrec)) {
						$ctb = preg_match("/2 DATE.*(\d\d\d\d)/", $birthrec, $matchb);
					}
					else $ctb=0;
					$deathrec = GetSubRecord(1, "1 DEAT", $indirec);
					if (!FactViewRestricted($pid, $deathrec)) {
						$ctd = preg_match("/2 DATE.*(\d\d\d\d)/", $deathrec, $matchd);
					}
					else $ctd=0;
					if ($ctb > 0 || $ctd > 0) {
						if ($ctb>0) $text .= trim($matchb[1]);
						$text .= "-";
						if ($ctd>0) $text .= trim($matchd[1]);
					}
				}
				$text = unhtmlentities($text);
				$text = strip_tags($text);

				// split and center text by lines
				$text = split_align_text($text, $wmax);

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
				print "textwidth: ".$textwidth." mr: ".$mr." center x: ".$cx." center y: ".$cy;
				print "degree: ".$deg." tangle: ".$tangle." correctie x: ".(-$maxpix*cos(deg2rad($tangle))/2);
//				if ($gen>0 and $deg2-$deg1>80) $mr=$rx/2;
				$tx=$cx + ($mr) * cos($rad);
				$ty=$cy - $mr * -sin($rad);
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
				// NOTE remove this line after fixing JS links
				$imagemap .= "\"";
				// TODO: Fix JavaScript links
				/**
				// add action url
				$url = "javascript:// " . PrintReady(strip_tags($name));
				if ($SHOW_ID_NUMBERS) $url .= " (".$pid.")";
				$imagemap .= "\" href=\"$url\" ";
				$url = "?rootid=$pid&amp;PEDIGREE_GENERATIONS=$PEDIGREE_GENERATIONS&amp;fan_width=$fan_width&amp;fan_style=$fan_style";
				if (!empty($view)) $url .= "&amp;view=$view";
				**/
				$count=0;
				$lbwidth=200;
				
				print "\n\t\t<div id=\"I".$pid.".".$count."links\" style=\"position:absolute; >";
				print "left:".$tx."px; top:".$ty."px; width: ".($lbwidth)."px; visibility:hidden; z-index:'100';\">";
				/**
				print "\n\t\t\t<table class=\"person_box\"><tr><td class=\"details1\">";
				print "<a href=\"individual.php?pid=$pid\" class=\"name1\">" . PrintReady($name);
				if (!empty($addname)) print "<br />" . PrintReady($addname);
				print "</a>\n";
				print "<br /><a href=\"pedigree.php?rootid=$pid\" >".$gm_lang["index_header"]."</a>\n";
				print "<br /><a href=\"descendancy.php?pid=$pid\" >".$gm_lang["descend_chart"]."</a>\n";
				if ($reltome)  print "<br /><a href=\"relationship.php?pid1=".$tuser->gedcomid[$GEDCOM]."&amp;pid2=".$pid."&amp;ged=$GEDCOM\" onmouseover=\"clear_family_box_timeout('".$pid.".".$count."');\" onmouseout=\"family_box_timeout('".$pid.".".$count."');\">".$gm_lang["relationship_to_me"]."</a>\n";
				print "<br /><a href=\"ancestry.php?rootid=$pid\" onmouseover=\"clear_family_box_timeout('".$pid.".".$count."');\" onmouseout=\"family_box_timeout('".$pid.".".$count."');\">".$gm_lang["ancestry_chart"]."</a>\n";
				print "<br /><a href=\"fanchart.php?rootid=$pid&amp;PEDIGREE_GENERATIONS=$PEDIGREE_GENERATIONS&amp;fan_width=$fan_width&amp;fan_style=$fan_style\" onmouseover=\"clear_family_box_timeout('".$pid.".".$count."');\" onmouseout=\"family_box_timeout('".$pid.".".$count."');\">".$gm_lang["fan_chart"]."</a>\n";
				print "<br /><a href=\"hourglass.php?pid=$pid\" onmouseover=\"clear_family_box_timeout('".$pid.".".$count."');\" onmouseout=\"family_box_timeout('".$pid.".".$count."');\">".$gm_lang["hourglass_chart"]."</a>\n";
				**/
				if ($sosa>=1) {
					$famids = FindSfamilyIds($pid);
					//-- make sure there is more than 1 child in the family with parents
					$cfamids = FindFamilyIds($pid);
					$num=0;
					for ($f=0; $f<count($cfamids); $f++) {
						$famrec = FindFamilyRecord($cfamids[$f]["famid"]);
						if ($famrec) $num += preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
					}
					if ($famids ||($num>1)) {
						//-- spouse(s) and children
						for ($f=0; $f<count($famids); $f++) {
							$famrec = FindFamilyRecord(trim($famids[$f]["famid"]));
							if ($famrec) {
								$parents = FindParents($famids[$f]["famid"]);
								if($parents) {
									if ($pid!=$parents["HUSB"]) $spid=$parents["HUSB"];
									else $spid=$parents["WIFE"];
//									if (!empty($spid)) {
//										$linkurl=str_replace("id=".$pid, "id=".$spid, $url);
										// TODO: Fix links
										//print "\n<br /><a href=\"$linkurl\" class=\"name1\">";
										//if (displayDetailsById($spid) || showLivingNameById($spid)) print PrintReady(rtrim(GetPersonName($spid)));
										//else print $gm_lang["private"];
										//print "</a>";
//									}
								}
//								$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
//								for ($i=0; $i<$num; $i++) {
//									$cpid = $smatch[$i][1];
//									$linkurl=str_replace("id=".$pid, "id=".$cpid, $url);
									// TODO: Fix links
									// print "\n<br />&nbsp;&nbsp;<a href=\"$linkurl\" class=\"name1\">&lt; ";
									//if (displayDetailsById($cpid) || showLivingNameById($cpid)) print PrintReady(rtrim(GetPersonName($cpid)));
									//else print $gm_lang["private"];
									//print "</a>";
//								}
							}
						}
						//-- siblings
						for ($f=0; $f<count($cfamids); $f++) {
							$famrec = FindFamilyRecord($cfamids[$f]["famid"]);
							if ($famrec) {
								$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
								if ($num>1) print "\n<br /><span class=\"name1\">".$gm_lang["siblings"]."</span>";
								for($i=0; $i<$num; $i++) {
									$cpid = $smatch[$i][1];
									if ($cpid!=$pid) {
//										$linkurl=str_replace("id=".$pid, "id=".$cpid, $url);
										// TODO: Fix links
										//print "\n<br />&nbsp;&nbsp;<a href=\"$linkurl\" class=\"name1\"> ";
										//if (displayDetailsById($cpid) || showLivingNameById($cpid)) print PrintReady(rtrim(GetPersonName($cpid)));
										//else print $gm_lang["private"];
										//print "</a>";
									}
								}
							}
						}
					}
				}
//				print "</td></tr></table>\n\t\t";
				print "</div>";
				// TODO: Fix JavaScript
				//$imagemap .= " onclick=\"show_family_box('".$pid.".".$count."', 'relatives'); return false;\"";
				//$imagemap .= " onmouseout=\"family_box_timeout('".$pid.".".$count."'); return false;\"";
				$imagemap .= " alt=\"".PrintReady(strip_tags($altname))."\" title=\"".PrintReady(strip_tags($name))."\" />";
			}
			$deg1-=$angle;
			$deg2-=$angle;
			$sosa--;
		}
		$rx-=$rw;
		$gen--;
	}

	$imagemap .= "\r\n</map>";
	echo "\r\n$imagemap";

	// GM banner ;-)
	ImageStringUp($image, 1, $fanw-10, $fanh/3, "www.genmod.net", $color);

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
	$image_title=preg_replace("~<.*>~", "", $name) . " " . $gm_lang["fan_chart"];
	echo "\r\n<p align=\"center\" >";
	echo "<img src=\"imageflush.php?image_type=png&amp;image_name=$image_name\" width=\"$fanw\" height=\"$fanh\" border=\"0\" alt=\"$image_title\" title=\"$image_title\" usemap=\"#fanmap\" />";
	echo "\r\n</p>\r\n";
	ImageDestroy($image);
}

// -- args
if (!isset($fan_style)) $fan_style = 0;
if ($fan_style==0) $fan_style = 3;
if ((!isset($PEDIGREE_GENERATIONS)) || ($PEDIGREE_GENERATIONS == "")) $PEDIGREE_GENERATIONS = $DEFAULT_PEDIGREE_GENERATIONS;

if ($PEDIGREE_GENERATIONS > $MAX_PEDIGREE_GENERATIONS) {
	$PEDIGREE_GENERATIONS = $MAX_PEDIGREE_GENERATIONS;
	$max_generation = true;
}

if ($PEDIGREE_GENERATIONS < 3) {
	$PEDIGREE_GENERATIONS = 3;
	$min_generation = true;
}
$OLD_PGENS = $PEDIGREE_GENERATIONS;

if (!isset($rootid)) $rootid = "";
$rootid = CleanInput($rootid);
$rootid = CheckRootId($rootid);

// -- size of the chart
if (!isset($fan_width)) $fan_width = "100";
$fan_width=max($fan_width, 50);
$fan_width=min($fan_width, 300);

if (showLivingNameByID($rootid)) {
	$name = GetPersonName($rootid);
	$addname = GetAddPersonName($rootid);
}
else {
	$name = $gm_lang["private"];
	$addname = "";
}
// -- print html header information
$title = PrintReady($name);
if ($SHOW_ID_NUMBERS) $title .= " - ".$rootid;
$title .= " - ".$gm_lang["fan_chart"];
print_header($title);
if (strlen($name)<30) $cellwidth="420";
else $cellwidth=(strlen($name)*14);
print "\n\t<table class=\"list_table $TEXT_DIRECTION\"><tr><td width=\"${cellwidth}\" valign=\"top\">\n\t\t";
if ($view == "preview") print "<h3>" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($PEDIGREE_GENERATIONS), $gm_lang["gen_fan_chart"]) . ":";
else print "<h3>" . $gm_lang["fan_chart"] . ":";
print "<br />".PrintReady($name);
if ($addname != "") print "<br />" . PrintReady($addname);
print "</h3>";

// -- print the form to change the number of displayed generations
if ($view != "preview") {
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
	}
	//-->
	</script>
	<?php
	if (isset($max_generation) == true) print "<span class=\"error\">" . str_replace("#PEDIGREE_GENERATIONS#", ConvertNumber($PEDIGREE_GENERATIONS), $gm_lang["max_generation"]) . "</span>";
	if (isset($min_generation) == true) print "<span class=\"error\">" . $gm_lang["min_generation"] . "</span>";
	print "\n\t</td><td><form name=\"people\" method=\"get\" action=\"?\">";
	print "\n\t\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr>";
	
	// NOTE: rootid
	print "<td class=\"shade2\">";
	print_help_link("rootid_help", "qm");
	print $gm_lang["root_person"]."</td>";
	print "<td class=\"shade1\">";
	print "<input class=\"pedigree_form\" type=\"text\" name=\"rootid\" id=\"rootid\" size=\"3\" value=\"$rootid\" />";
     PrintFindIndiLink("rootid","");
	print "</td>";
	
	// NOTE: fan style
	print "<td rowspan=\"3\" class=\"shade2\">";
	print_help_link("fan_style_help", "qm");
	print $gm_lang["fan_chart"]."</td>";
	print "<td rowspan=\"3\" class=\"shade1\">";
	print "<input type=\"radio\" name=\"fan_style\" value=\"2\"";
	if ($fan_style==2) print " checked=\"checked\"";
	print " /> 1/2";
	print "<br /><input type=\"radio\" name=\"fan_style\" value=\"3\"";
	if ($fan_style==3) print " checked=\"checked\"";
	print " /> 3/4";
	print "<br /><input type=\"radio\" name=\"fan_style\" value=\"4\"";
	if ($fan_style==4) print " checked=\"checked\"";
	print " /> 4/4";
	
	// NOTE: submit
	print "</td><td rowspan=\"3\" class=\"center vmiddle\">";
	print "<input type=\"submit\"  value=\"" . $gm_lang["view"] . "\" />";
	print "</td></tr>\n";
	
	// NOTE: generations
	print "<tr><td class=\"shade2\">";
	print_help_link("PEDIGREE_GENERATIONS_help", "qm");	
	print $gm_lang["generations"]."</td>";
	print "<td class=\"shade1\">";
//	print "<input type=\"text\" name=\"PEDIGREE_GENERATIONS\" size=\"3\" value=\"$OLD_PGENS\" /> ";
	print "<select name=\"PEDIGREE_GENERATIONS\">";
	for ($i=2; $i<=$MAX_PEDIGREE_GENERATIONS; $i++) {
	print "<option value=\"".$i."\"";
	if ($i == $OLD_PGENS) print " selected=\"selected\" ";
		print ">".$i."</option>";
	}
	print "</select>";
	print "</td>";
	print "</tr><tr>";
	// NOTE: fan width
	print "<td class=\"shade2\">";
	print_help_link("fan_width_help", "qm");
	print $gm_lang["fan_width"]."</td>";
	print "<td class=\"shade1\">";
	print "<input type=\"text\" size=\"3\" name=\"fan_width\" value=\"$fan_width\" /> <b>%</b> ";
	print "</td>";
	print "</tr></table>";
	print "\n\t\t</form><br />";
} 
else {
	print "<script language='JavaScript' type='text/javascript'>";
	print "if (IE) document.write('<span class=\"warning\">".str_replace("'", "\'", $gm_lang["fanchart_IE"])."</span>');";
	print "</script>";
}
print "</td></tr></table>";

$treeid = AncestryArray($rootid);
print_fan_chart($treeid, 640*$fan_width/100, $fan_style*90);

print_footer();
?>
