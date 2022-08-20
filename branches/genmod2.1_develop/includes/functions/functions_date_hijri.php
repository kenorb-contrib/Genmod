<?php
/**
 * Hijri/Arabic Date Functions
 *
 * The functions in this file are used when converting dates to the Hebrew or Jewish Calendar
 * This file is only loaded if the $LANGUAGE is hebrew, or if the $CALENDAR_FORMAT is hebrew or jewish
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
 * @subpackage Dates
 * @version $Id: functions_date_hijri.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Probably used to ensure floating points are rounded up and down properly
 *
 * @param float $float the number to round nicely
 * @return float a nicely rounded number?
 * @author VisualMind (visualmind@php.net)
 */
function ArdInt($float) {
       return ($float < -0.0000001) ? ceil($float-0.0000001) : floor($float+0.0000001);
}

 
/**
 * Get the Hijri conversion from gregorian $d $m $y
 *
 * @param int $d	The gregorian day of month
 * @param int $m	The gregorian month number
 * @param int $y	The gregorian Year
 * @return string a string containing the required date in Hijri form (day arabic_month_as_text year).
 * @author VisualMind (visualmind@php.net).
 * @author sfezz (sfezz@users.sourceforge.net).
 */
function GetHijri($d, $m, $y) {
	// note: see the $format string to change the form of the Hijri returned
	
	// manipulated by sfezz to use UTF-8 and to work nicely in Genmod
	// Hijri dates run according to the moon cycle and loose about 11 days per year.
	// Due to the nature of the moon not being in sync with the sun, it is possible
	// for the Hijri date to loose or gain a day, which is subsequently gained or lost.
	// This means Hijri date calculations are only accurate to day +/- 1.
	
	// The days of the week are the same and start on Sunday. The translation of the dates are as
	// follows: The First, The Second, The Third, The Fourth, The Fifth, The Gathering, The Sabbath.
	// We can include the word 'day' before each of these, but it is usual to leave it out.
	
	// Hijri works on the time since the emigration of the Prophet Mohammad from Mecca to Madina
	// which occured in 622 AD.
	// see here for more: http://en.wikipedia.org/wiki/Islamic_calendar
	
	$use_span=true;

	$arDay = array("Sat"=>"?????", 
	         "Sun"=>"?????", 
	         "Mon"=>"???????", 
	         "Tue"=>"????????", 
	         "Wed"=>"????????", 
	         "Thu"=>"??????", 
	         "Fri"=>"??????");
	$ampm=array('am'=>'????','pm'=>'????'); 

	// -- commented out because the date function will not work on dates < 1970
	// list($d,$m,$y,$dayname,$monthname,$am)=explode(' ',date('d m Y D M a', $timestamp));


	if (($y>1582)||(($y==1582)&&($m>10))||(($y==1582)&&($m==10)&&($d>14))) {
		$jd = ArdInt((1461*($y+4800+ ArdInt(($m-14)/12)))/4);
		$jd += ArdInt((367*($m-2-12*( ArdInt(($m-14)/12))))/12);
		$jd -= ArdInt((3*( ArdInt(($y+4900 + ArdInt(($m-14)/12))/100)))/4);
		$jd +=$d-32075;
	} else 	{
		$jd = 367*$y- ArdInt((7*($y+5001+ ArdInt(($m-9)/7)))/4)+ ArdInt((275*$m)/9)+$d+1729777;
	}
	$l=$jd-1948440+10632;
	$n= ArdInt(($l-1)/10631);
	$l=$l-10631*$n+355;  // Correction: 355 instead of 354
	$j=( ArdInt((10985-$l)/5316))*( ArdInt((50*$l)/17719))+( ArdInt($l/5670))*( ArdInt((43*$l)/15238));
	$l=$l -( ArdInt((30-$j)/15))*( ArdInt((17719*$j)/50))-( ArdInt($j/16))*( ArdInt((15238*$j)/43))+29;
	$m=ArdInt((24*$l)/709);
	$d=$l- ArdInt((709*$m)/24);
	$y=30*$n+$j-30;		
	
	
	$hjMonth = array("?????", 
				"???", 
				"???? ?????", 
				"???? ??????", 
				"????? ?????", 
				"????? ??????", 
				"???", 
				"?????", 
				"?????", 
				"?????", 
				"?? ??????", 
				"?? ?????"); 
	
	$format = "F/j/Y"; // <------------- Change this to show different forms of the Hijri system
	
	$format=str_replace('j', $d, $format);
	$format=str_replace('d', str_pad($d,2,0,STR_PAD_LEFT), $format);
	//$format=str_replace('l', $arDay[$dayname], $format);
	if (isset($hjMonth[$m-1])) $format=str_replace('F', $hjMonth[$m-1], $format);
	$format=str_replace('m', str_pad($m,2,0,STR_PAD_LEFT), $format);
	$format=str_replace('n', $m, $format);
	$format=str_replace('Y', $y, $format);
	$format=str_replace('y', substr($y,2), $format);
	//$format=str_replace('a', substr($ampm[$am],0,1), $format);
	//$format=str_replace('A', $ampm[$am], $format);

	//$date = date($format, $timestamp);
	return $format;
  }


/**
 * Get the Gregorian from a Hijri date.
 *
 * @todo Check if hte function is necessary, currently not used.
 * @param int $d the Hijri day
 * @param int $m the Hijri month
 * @param int $y the Hijri year
 * @return string a string containing the required date in gregorian, (d-m-y)
 * @author VisualMind (visualmind@php.net)
 * @author sfezz (sfezz@users.sourceforge.net)
 */
function DateHijriToGreg($d, $m, $y) {
	
	$jd=ArdInt((11*$y+3)/30)+354*$y+30*$m-ArdInt(($m-1)/2)+$d+1948440-386;
	if ($jd> 2299160 ) {
		$l=$jd+68569;
		$n=ArdInt((4*$l)/146097);
		$l=$l-ArdInt((146097*$n+3)/4);
		$i=ArdInt((4000*($l+1))/1461001);
		$l=$l-ArdInt((1461*$i)/4)+31;
		$j=ArdInt((80*$l)/2447);
		$d=$l-ArdInt((2447*$j)/80);
		$l=ArdInt($j/11);
		$m=$j+2-12*$l;
		$y=100*($n-49)+$i+$l;
	} else	{
		$j=$jd+1402;
		$k=ArdInt(($j-1)/1461);
		$l=$j-1461*$k;
		$n=ArdInt(($l-1)/365)-ArdInt($l/1461);
		$i=$l-365*$n+30;
		$j=ArdInt((80*$i)/2447);
		$d=$i-ArdInt((2447*$j)/80);
		$i=ArdInt($j/11);
		$m=$j+2-12*$i;
		$y=4*$k+$n+$i-4716;
	}

	return "$d-$m-$y"; 
} 
	

 
/**
 * Get the Arabic form of a gregorian date
 *
 * @param int $d	The gregorian day of month
 * @param int $m	The gregorian month number
 * @param int $y	The gregorian Year
 * @return string a string containing the required date in Hijri form (day arabic_month_as_text year)
 * @author VisualMind (visualmind@php.net)
 * @author sfezz (sfezz@users.sourceforge.net)
 */
function GetArabic($d, $m, $y) {

	// This is the same as the Gregorian date, although the names of things are different.

	$use_span=true;

		$arDay = array("Sat"=>"?????", 
	         "Sun"=>"?????", 
	         "Mon"=>"???????", 
	         "Tue"=>"????????", 
	         "Wed"=>"????????", 
	         "Thu"=>"??????", 
	         "Fri"=>"??????");
	$ampm=array('am'=>'????','pm'=>'????'); 

	// -- commented out because the date function will not work on dates < 1970
	// list($d,$m,$y,$dayname,$monthname,$am)=explode(' ',date('d m Y D M a', $timestamp));

	$arMonth=array("??????",
			"??????",
			"????",
			"?????",
			"????",
			"?????",
			"?????",
			"?????",
			"??????",
			"??????",
			"??????",
			"??????");
			
	
	$format = "F/j/Y"; // <------------- Change this to show different forms of the Arabic date

	$format=str_replace('j', $d, $format);
	//$format=str_replace('l', $arDay[$dayname], $format);
	$format=str_replace('F', $arMonth[$m-1], $format);
	$format=str_replace('Y', $y, $format);
	//$format=str_replace('a', substr($ampm[$am],0,1), $format);
	//$format=str_replace('A', $ampm[$am], $format);
    
	return $format;
	//$date = date($format, $timestamp);
	//if ($use_span) return '<span dir="rtl" lang="ar-sa">'.$date.'</span>'; 
	//else return $date;
  }

?>