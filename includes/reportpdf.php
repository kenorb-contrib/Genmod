<?php
/**
 * PDF Report Generator
 *
 * used by the SAX parser to generate PDF reports from the XML report file.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
 * @subpackage Reports
 * @version $Id: reportpdf.php 13 2016-04-27 09:26:01Z Boudewijn $
 */

//-- do not allow direct access to this file
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../intrusion.php";
}
define('FPDF_FONTPATH','fonts/');

/**
 * page sizes
 *
 * an array map of common page sizes
 * Page sizes should be specified in inches
 * @global array $pageSizes
 */
$pageSizes["A4"]["width"] = "8.27";		// 210 mm
$pageSizes["A4"]["height"] = "11.73";	// 297 mm
$pageSizes["A3"]["width"] = "11.73";	// 297 mm
$pageSizes["A3"]["height"] = "16.54";	// 420 mm
$pageSizes["A5"]["width"] = "5.83";		// 148 mm
$pageSizes["A5"]["height"] = "8.27";	// 210 mm
$pageSizes["letter"]["width"] = "8.5";	// 216 mm
$pageSizes["letter"]["height"] = "11";	// 279 mm
$pageSizes["legal"]["width"] = "8.5";	// 216 mm
$pageSizes["legal"]["height"] = "14";	// 356 mm

$ascii_langs = array("english", "danish", "dutch", "french", "german", "norwegian", "spanish", "spanish-ar");

//-- setup special characters array to force embedded fonts
$SpecialOrds = $RTLOrd;
for($i=195; $i<215; $i++) $SpecialOrds[] = $i;

if (!isset($embed_fonts)) {
	if (in_array($LANGUAGE, $ascii_langs)) $embed_fonts = false;
	else $embed_fonts = true;
}
//print "embed = $embed_fonts";
/**
 * load the FPDF class
 *
 * the FPDF class allows you to create PDF documents in PHP
 */
require "ufpdf/ufpdf.php";

/**
 * main GM Report Class
 * @package Genmod
 * @subpackage Reports
 */
class GMReport {
	/**
	 * GMRStyles array
	 *
	 * an array of the GMRStyles elements found in the document
	 * @var array $GMRStyles
	 */
	var $GMRStyles = array();

	var $pagew;
	var $pageh;
	var $orientation;
	var $margin;
	var $pdf;
	var $processing;

	function setup($pw, $ph, $pageSize, $o, $m, $showGenText=true) {
		global $vars, $pageSizes;

		// Determine the page dimensions
		$this->pageFormat = strtoupper($pageSize);
		if ($this->pageFormat == "LETTER") $this->pageFormat = "letter";
		if ($this->pageFormat == "LEGAL") $this->pageFormat = "legal";

		if (isset($pageSizes[$this->pageFormat]["width"])) {
			$this->pagew = $pageSizes[$this->pageFormat]["width"];
			$this->pageh = $pageSizes[$this->pageFormat]["height"];
		} else {
			if ($pw==0 || $ph==0) {
				$this->pageFormat = "A4";
				$this->pagew = $pageSizes["A4"]["width"];
				$this->pageh = $pageSizes["A4"]["height"];
			} else {
				$this->pageFormat = "";
				$this->pagew = $pw;
				$this->pageh = $ph;
			}
		}

		$this->orientation = strtoupper($o);
		if ($this->orientation == "L") {
			$temp = $this->pagew;
			$this->pagew = $this->pageh;
			$this->pageh = $temp;
		} else {
			$this->orientation = "P";
		}

		$this->margin = $m;
		$vars['pageWidth']['id'] = $this->pagew*72;
		$vars['pageHeight']['id'] = $this->pageh*72;

		if (empty($this->pageFormat)) {		//-- send a custom size
			$this->pdf = new GMRPDF($this->orientation, 'pt', array($pw*72,$ph*72));
		} else {							//-- send a known size
			$this->pdf = new GMRPDF($this->orientation, 'pt', $this->pageFormat);
		}

		$this->pdf->setMargins($m, $m);
		$this->pdf->SetCompression(true);
		$this->pdf->setReport($this);
		$this->processing = "H";
		if ($showGenText) {
			$element = new GMRCell(0,10, "C", "");
			$element->addText(GM_LANG_generated_by." Genmod ".GM_VERSION);
			$element->setUrl("https://www.sourceforge.net/projects/genmod");
			$this->pdf->addFooter($element);
		}
		$this->pdf->SetAutoPageBreak(false);
		$this->pdf->SetAutoLineWrap(false);
	}

	function setProcessing($p) {
		$this->processing = $p;
	}

	function addElement(&$element) {
		if ($this->processing=="H") return $this->pdf->addHeader($element);
		if ($this->processing=="") return $this->pdf->addPageHeader($element);
		if ($this->processing=="F") return $this->pdf->addFooter($element);
		if ($this->processing=="B") return $this->pdf->addBody($element);
	}

	function addStyle($style) {
		$this->GMRStyles[$style["name"]] = $style;
	}

	function getStyle($s) {
		if (!isset($this->GMRStyles[$s])) {
			$s = $this->pdf->getCurrentStyle();
			$this->GMRStyles[$s] = $s;
		}
		return $this->GMRStyles[$s];
	}

	function run() {
		global $download, $embed_fonts;

		$this->pdf->SetEmbedFonts($embed_fonts);
		if ($embed_fonts) $this->pdf->AddFont('LucidaSansUnicode', '', 'LucidaSansRegular.php');
		$this->pdf->setCurrentStyle(key($this->GMRStyles));
		$this->pdf->AliasNbPages();
		$this->pdf->Body();
		header("Expires:");
		header("Pragma:");
		header("Cache-control:");
//		if (!isset($download)) $this->pdf->Output();
		if ($download=="") $this->pdf->Output();
		else $this->pdf->Output("gm_report_".basename($_REQUEST["report"], ".xml").".pdf", "D");
	}

	function getMaxWidth() {
		$w = (($this->pagew * 72) - ($this->margin)) - $this->pdf->GetX();
		return $w;
	}

	function getPageHeight() {
		return ($this->pageh*72)-$this->margin;
	}

	function clearPageHeader() {
		$this->pdf->clearPageHeader();
	}
} //-- end GMReport

/**
 * GM Report PDF Class
 *
 * This class inherits from the FPDF class and is used to generate the PDF document
 * @package Genmod
 * @subpackage Reports
 */
class GMRPDF extends UFPDF {
	/**
	 * array of elements in the header
	 */
	var $headerElements = array();
	/**
	 * array of elements in the header
	 */
	var $pageHeaderElements = array();
	/**
	 * array of elements in the footer
	 */
	var $footerElements = array();
	/**
	 * array of elements in the body
	 */
	var $bodyElements = array();
	var $printedfootnotes = array();

	var $gmreport;
	var $currentStyle;

	function Header() {
		if (!isset($this->currentStyle)) $this->currentStyle = "";
		$temp = $this->currentStyle;
		foreach($this->headerElements as $indexval => $element) {
			if (is_string($element) && $element=="footnotetexts") $this->Footnotes();
			else if (is_string($element) && $element=="addpage") $this->AddPage();
			else $element->render($this);
		}
		foreach($this->pageHeaderElements as $indexval => $element) {
			if (is_string($element) && $element=="footnotetexts") $this->Footnotes();
			else if (is_string($element) && $element=="addpage") $this->AddPage();
			else if (is_object($element)) $element->render($this);
		}
		$this->currentStyle = $temp;
	}

	function Footer() {
		$this->SetY(-36);
		$this->currentStyle = "";
		foreach($this->footerElements as $indexval => $element) {
			if (is_string($element) && $element=="footnotetexts") $this->Footnotes();
			else if (is_string($element) && $element=="addpage") $this->AddPage();
			else if (is_object($element)) $element->render($this);
		}
	}

	function Body() {
		global $TEXT_DIRECTION;
		$this->AddPage();
		$this->currentStyle = "";
		foreach($this->bodyElements as $indexval => $element) {
			if (is_string($element) && $element=="footnotetexts") $this->Footnotes();
			else if (is_string($element) && $element=="addpage") $this->AddPage();
			else if (is_object($element)) {
				$element->render($this);
			}
		}
	}

	function Footnotes() {
		$this->currentStyle = "";
		foreach($this->printedfootnotes as $indexval => $element) {
			//print ($this->GetY() + $element->getFootnoteHeight($this)).">".$this->getPageHeight();
			if (($this->GetY() + $element->getFootnoteHeight($this) + 24) > $this->getPageHeight()) $this->AddPage();
			$element->renderFootnote($this);
			
			if ($this->GetY() > $this->getPageHeight()) $this->AddPage();
		}
	}

	function getFootnotesHeight() {
		$h=0;
		foreach($this->printedfootnotes as $indexval => $element) {
			$h+=$element->getHeight($this);
		}
		return $h;
	}

	function addHeader(&$element) {
		$this->headerElements[] = $element;
		return count($this->headerElements)-1;
	}

	function addPageHeader(&$element) {
		$this->pageHeaderElements[] = $element;
		return count($this->headerElements)-1;
	}

	function addFooter(&$element) {
		$this->footerElements[] = $element;
		return count($this->footerElements)-1;
	}

	function addBody(&$element) {
		$this->bodyElements[] = $element;
		return count($this->bodyElements)-1;
	}

	function removeHeader($index) {
		unset($this->headerElements[$index]);
	}

	function removePageHeader($index) {
		unset($this->pageHeaderElements[$index]);
	}

	function removeFooter($index) {
		unset($this->footerElements[$index]);
	}

	function removeBody($index) {
		unset($this->bodyElements[$index]);
	}

	function clearPageHeader() {
		$this->pageHeaderElements = array();
	}

	function setReport(&$r) {
		$this->gmreport = $r;
	}

	function getCurrentStyle() {
		return $this->currentStyle;
	}

	function setCurrentStyle($s) {
		$this->currentStyle = $s;
		$style = $this->gmreport->getStyle($s);
		//print_r($style);
		$this->SetFont($style["font"], $style["style"], $style["size"]);
	}

	function getStyle($s) {
		$style = $this->gmreport->getStyle($s);
		return $style;
	}

	function getMaxWidth() {
		return $this->gmreport->getMaxWidth();
	}

	function getCurrentStyleHeight() {
		if (empty($this->currentStyle)) return 12;
		$style = $this->gmreport->getStyle($this->currentStyle);
		return $style["size"];
	}

	function checkFootnote(&$footnote) {
		for($i=0; $i<count($this->printedfootnotes); $i++) {
			if ($this->printedfootnotes[$i]->getValue() == $footnote->getValue()) {
				return $this->printedfootnotes[$i];
			}
		}
		$footnote->setNum(count($this->printedfootnotes)+1);
		$link = $this->AddLink();
		$footnote->setAddlink($link);
		$this->printedfootnotes[] = $footnote;
		return false;
	}

	function getPageHeight() {
		return $this->gmreport->getPageHeight();
	}
} //-- END GMRPDF

/**
 * main GM Report Element class that all other page elements are extended from
 */
class GMRElement {
	var $text;

	function render(&$pdf) {
		print "Nothing rendered.  Something bad happened";
		//-- to be implemented in inherited classes
	}

	function addText($t) {
		global $embed_fonts, $TEXT_DIRECTION, $SpecialOrds;

		if (!isset($this->text)) $this->text = "";

		//$ord = ord(substr($t, 0, 1));
		//print "[".substr($t, 0, 1)."=$ord]";
		$found=false;
		foreach($SpecialOrds as $indexval => $ord) {
   			if (strpos($t, chr($ord))!==false) {
				$found=true;
			}
		}
   		if ($found) $embed_fonts = true;
		$t = trim($t, "\r\n\t");
		$t = preg_replace("/<br \/>/", "\n", $t);
		$t = strip_tags($t);
		$t = htmlspecialchars_decode ($t);
		$t = preg_replace(array('/&lrm;/','/&rlm;/'), array('',''), $t);
		if ($embed_fonts) $t = bidi_text($t);
		else $t = SmartUtf8Decode($t);
		$this->text .= $t;
	}

	function addNewline() {
		if (!isset($this->text)) $this->text = "";
		$this->text .= "\n";
	}

	function getValue() {
		if (!isset($this->text)) $this->text = "";
		return $this->text;
	}

	function getHeight(&$pdf) {
		$ct = substr_count($this->text, "\n");
		// Don't do this. As the "extra" will count for every line in a multiline text, it will generate blank space equal to the number of lines.
//		if ($ct>0) $ct+=1;
		$style = $pdf->getCurrentStyleHeight();
//		$h = ($style["size"]*$ct);
		$h = ($style*$ct);
//		print "GMRElement getHeight [Text: ".$this->text." Stylename: ".$this->styleName." Stylesize: ".$style["size"]." Lines: ".$ct." Height: ".$h."]<br />";
		return $h;
	}

	function getWidth(&$pdf) {
		return 0;
	}

	function setWrapWidth($width, $width2) {
		return;
	}

	function renderFootnote(&$pdf) {
		return false;
		//-- to be implemented in inherited classes
	}

	function get_type() {
		return "GMRElement";
	}
} //-- END GMRElement

/**
 * Cell element
 */
class GMRCell extends GMRElement {
	var $styleName;
	var $width;
	var $height;
	var $align;
	var $url;
	var $top;
	var $left;

	function GMRCell($width, $height, $align, $style, $top=".", $left=".") {
		$this->text = "";
		$this->width = $width;
		$this->height = $height;
		$this->align = $align;
		$this->styleName = $style;
		$this->url = "";
		$this->top = $top;
		$this->left = $left;
	}

	function render(&$pdf) {
		global $TEXT_DIRECTION, $embed_fonts;
		/* -- commenting out because it causes too many problems
		if ($TEXT_DIRECTION=='rtl') {
			if ($this->align=='L') $this->align='R';
			else if ($this->align=='R') $this->align='L';
		}*/
		if ($pdf->getCurrentStyle()!=$this->styleName)
			$pdf->setCurrentStyle($this->styleName);
		$temptext = preg_replace("/#PAGENUM#/", $pdf->PageNo(), $this->text);
		$curx = $pdf->GetX();
		$cury = $pdf->GetY();
		if (($this->top!=".")||($this->left!=".")) {
			if ($this->top==".") $this->top = $cury;
			if ($this->left==".") $this->left = $cury;
			$pdf->SetXY($this->left, $this->top);
		}
		$pdf->MultiCell($this->width,$this->height,$temptext,0,$this->align);
		if (!empty($url)) {
			$pdf->Link($curx, $cury, $this->width, $this->height, $url);
		}
	}

	function getHeight(&$pdf) {
		return $this->height;
	}

	function setUrl($url) {
		$this->url = $url;
	}

	function getWidth(&$pdf) {
		return $this->width;
	}

	function get_type() {
		return "GMRCell";
	}
}

/**
 * TextBox element
 */
class GMRTextBox extends GMRElement {
	var $style;
	var $width;
	var $height;
	var $border;
	var $fill;
	var $newline;
	var $top;
	var $left;
	var $elements = array();
	var $pagecheck;

	function get_type() {
		return "GMRTextBox";
	}

	function GMRTextBox($width, $height, $border, $fill, $newline, $left=".", $top=".", $pagecheck="true") {
		$this->width = $width;
		$this->height = $height;
		$this->border = $border;
		$this->fill = $fill;
		$this->newline = $newline;
		if ($border>0) $this->style = "D";
		else $this->style = "";
		$this->top = $top;
		$this->left = $left;
		if ($pagecheck=="true") $this->pagecheck = true;
		else $this->pagecheck = false;
	}

	function render(&$pdf) {
		global $lastheight;
$debug = false;

		if (!empty($lastheight)) {
			if ($this->height < $lastheight) $this->height = $lastheight;
		}

		$startX = $pdf->GetX();
		$startY = $pdf->GetY();
		if (!empty($this->fill)) {
			$ct = preg_match("/#?(..)(..)(..)/", $this->fill, $match);
			if ($ct>0) {
				$this->style .= "F";
				$r = hexdec($match[1]);
				$g = hexdec($match[2]);
				$b = hexdec($match[3]);
				$pdf->SetFillColor($r, $g, $b);
			}
		}
		if ($this->width==0) {
			$this->width = $pdf->getMaxWidth();
		} else if (substr($this->width, -1)=="%") {
			$this->width = $pdf->getMaxWidth() * intval($this->width) / 100;
		}

		$newelements = array();
		$lastelement = "";
		//-- collapse duplicate elements
		for($i=0; $i<count($this->elements); $i++) {
			$element = $this->elements[$i];
			if (is_object($element)) {
				if ($element->get_type()=="GMRText") {
					if (empty($lastelement)) $lastelement = $element;
					else {
						if ($element->getStyleName()==$lastelement->getStyleName()) {
							$lastelement->addText(preg_replace("/\n/", "<br />", $element->getValue()));
						}
						else {
							if (!empty($lastelement)) {
								$newelements[] = $lastelement;
								$lastelement = $element;
							}
						}
					}
				}
				//-- do not keep empty footnotes
				else if (($element->get_type()!="GMRFootnote")||(trim($element->getValue())!="")) {
					if (!empty($lastelement)) {
						$newelements[] = $lastelement;
						$lastelement = "";
					}
					$newelements[] = $element;
				}
			}
			else {
				if (!empty($lastelement)) {
					$newelements[] = $lastelement;
					$lastelement = "";
				}
				$newelements[] = $element;
			}
		}
		if (!empty($lastelement)) $newelements[] = $lastelement;
		$this->elements = $newelements;

		//-- calculate the text box height
		// $h = 0;
		// initial value for h must be the height of 1 line
		if ($debug) print "<br /><b>Start new element array</b>";
		// set h to 0, if we get the current style size here, we get it from the previous element.
		$h = 0;
		if ($debug) print "<br />h set initially to: ".$h;
		$w = 0;
		$fncnt = 0;
		$addline = false;
		for($i=0; $i<count($this->elements); $i++) {
			if (is_object($this->elements[$i])) {
				if ($h == 0) {
					$style = $pdf->getStyle($this->elements[$i]->styleName);
					$h = $style["size"]+5;
				}
				// if adding the footnote links to the line caused the length to exceed the maximum width,
				// we must insert a line break manually.
				if ($addline && $this->elements[$i]->get_type()!="GMRFootnote") {
					$addline = false;
					$this->elements[$i]->text = "\n".$this->elements[$i]->text;
				}
				$ew = $this->elements[$i]->setWrapWidth($this->width-$w, $this->width);
				if ($ew==$this->width) $w=0;
				// For every footnote, we add the width to the line width so far.
				// The width is rougly calculated by the number of citations,
				// which varies a bit from the actual number of sources.
				if ($this->elements[$i]->get_type()=="GMRFootnote") {
					$fncnt++;
					// add 2: .5 for the space, 1 because the calculation is 0 for 1-10
					$a = 1.5 + intval(abs(log10($fncnt)));
					$w += $a * 6;
					// w will be reset later, without adding a linebreak. This will be added with the next not-footnote element.
					if ($w > $this->width) $addline = true;
				}
//				if ($debug) print "<br />w: ".$w." ew: ".$ew."";
				//-- $lw is an array 0=>last line width, 1=1 if text was wrapped, 0 if text did not wrap
				$lw = $this->elements[$i]->getWidth($pdf);
				if ($debug) {
					print "<br />element: ";
					print_r($this->elements[$i]); 
					print "<br />lw: ";
					print_r($lw); 
					print "<br />";
				}
				if (is_array($lw)) {
					if ($lw[1]==1) $w = $lw[0];
					else if ($lw[1]==2) $w=0;
					else $w += $lw[0];
					if ($w>$this->width) $w = $lw[0];
				}
//				if ($debug) print "Width of the line so far w: ".$w."<br />";
				if ($this->elements[$i]->get_type()!="GMRFootnote") $eh = $this->elements[$i]->getHeight($pdf);
				else $eh = 0;
				if ($debug) print "Height of the line eh: ".$eh."<br />";
				//if ($eh>$h) $h = $eh;
				//else if ($lw[1]) $h+=$eh;
				$h+=$eh;
				if ($debug) {
					print "Total height so far h: ".$h."<br />";
					print "Newline: ".$this->newline."<br />";
				}
//				print "h set to: ".$h."<br />";
			}
			else {
				//print "Get the footnotes height<br />";
				// Don't add this, as the foutnotes are printed OUTSIDE the text block, thus the height of
				// the footnote should not be counted.
//				$h += $pdf->getFootnotesHeight();
				if ($debug) print "h set to ".$h." in footnotes<br />";
			}
			if ($h < $this->height) $h = $this->height;
			if ($debug) print "standard height: ".$this->height."<br />";
			if ($debug) print "pageheight: ".$pdf->getPageHeight()."<br />";
		}
//		$styleh = $pdf->getCurrentStyleHeight();
//		$h += $styleh;
		if ($h>$this->height) $this->height=$h;
		//if (($this->width>0)&&($this->width<$w)) $this->width=$w;

		$curx = $pdf->GetX();
		$cury = $pdf->GetY();
		$curn = $pdf->PageNo();
		if (($this->top!=".")||($this->left!=".")) {
			if ($this->top==".") $this->top = $cury;
			if ($this->left==".") $this->left = $curx;
			$pdf->SetXY($this->left, $this->top);
			$startY = $this->top;
			$startX = $this->left;
			$cury = $startY;
			$curx = $startX;
		}

		$newpage = false;
		if ($this->pagecheck) {
			$ph = $pdf->getPageHeight();
			if ($pdf->GetY()+$this->height > $ph) {
				if ($this->border==1) {
					//print "HERE2";
					$pdf->AddPage();
					$newpage = true;
					$startX = $pdf->GetX();
					$startY = $pdf->GetY();
				}
				else if ($pdf->GetY()>$ph-36) {
					//print "HERE1";
					$pdf->AddPage();
					$startX = $pdf->GetX();
					$startY = $pdf->GetY();
				}
				else {
					//print "HERE3";
					$th = $this->height;
					$this->height = ($ph - $pdf->GetY())+36;
					$newpage = true;
				}
			}
		}

		if (!empty($this->style)) $pdf->Rect($pdf->GetX(), $pdf->GetY(), $this->width, $this->height, $this->style);
		$pdf->SetXY($pdf->GetX(), $pdf->GetY()+1);
		$curx = $pdf->GetX();
		foreach($this->elements as $indexval => $element) {
			if (is_string($element) && $element=="footnotetexts") $pdf->Footnotes();
			else if (is_string($element) && $element=="addpage") $pdf->AddPage();
			else $element->render($pdf, $curx);
		}
		if ($curn != $pdf->PageNo()) $cury = $pdf->GetY();
		if ($this->newline) {
			$lastheight = 0;
			$ty = $pdf->GetY();
			if ($curn != $pdf->PageNo()) $ny = $cury+$pdf->getCurrentStyleHeight();
			else $ny = $cury+$this->height;
			if ($ty > $ny) $ny = $ty;
			$pdf->SetY($ny);
			//Here1 ty:71 ny:185 cury:169
			//print "Here1 ty:$ty ny:$ny cury:$cury ";
		}
		else {
			//print "Here2 ";
			$ty = $pdf->GetY()-1;
			if (($ty > $startY) && ($ty < $startY + $this->height)) $ty = $startY;
			$pdf->SetXY($curx+$this->width, $ty);
			$lastheight = $this->height;
		}
//		print "Lastheight: ".$lastheight."<br />";
	}

	function addElement(&$element) {
		$this->elements[] = $element;
	}
}

/**
 * Text element
 */
class GMRText extends GMRElement {
	var $styleName;
	var $wrapWidth;
	var $wrapWidth2;
	var $align;

	function get_type() {
		return "GMRText";
	}

	function GMRText($style, $color, $align) {
		$this->text = "";
		$this->color = $color;
		$this->wrapWidth = 0;
		$this->styleName = $style;
		$this->align = $align;
	}

	function render(&$pdf, $curx=0) {
		global $embed_fonts;
		$pdf->setCurrentStyle($this->styleName);
		$temptext = preg_replace("/#PAGENUM#/", $pdf->PageNo(), $this->text);
		//print $this->text;
		$x = $pdf->GetX();
		$cury = $pdf->GetY();

		if (!empty($this->color)) {
			$ct = preg_match("/#?(..)(..)(..)/", $this->color, $match);
			if ($ct>0) {
				//$this->style .= "F";
				$r = hexdec($match[1]);
				$g = hexdec($match[2]);
				$b = hexdec($match[3]);
				$pdf->SetTextColor($r, $g, $b);
			}
		}

		$lines = preg_split("/\n/", $temptext);
		$styleh = $pdf->getCurrentStyleHeight();
		if (count($lines)>0) {
			foreach($lines as $indexval => $line) {
				if ($this->align=="R" || $this->align=="C") {
					$temp=$this->text;
					$this->text=$line;
					$widths=$this->getWidth($pdf);
					$this->text=$temp;
					$width=intval(ceil($widths[0]));
					if ($this->wrapWidth > $width) {
						$cx = $this->wrapWidth - $width;
						if ($this->align=="C") $cx /= 2;
						$x += $cx;
					}
				}
				$pdf->SetXY($x, $cury);
//				print "[$x $cury $line]";
				$pdf->Write($styleh,$line);
				$cury+=$styleh+1;
				if ($cury>$pdf->getPageHeight()) $cury = $pdf->getY()+$styleh+1;
				$x = $curx;
			}
		} else {
			if ($this->align=="R" || $this->align=="C") {
				$temp=$this->text;
				$this->text=$line;
				$widths=$this->getWidth($pdf);
				$this->text=$temp;
				$width=intval(ceil($widths[0]));
				if ($this->wrapWidth > $width) {
					$cx = $this->wrapWidth - $width;
					if ($this->align=="C") $cx /= 2;
					$x += $cx;
					$pdf->SetXY($x, $cury);
				}
			}
			$pdf->Write($pdf->getCurrentStyleHeight(),$temptext);
		}
		$ct = preg_match_all("/".chr(215)."/", $temptext, $match);
		if ($ct>1) {
			$x = $pdf->GetX();
			$x = $x - pow(1.355, $ct);
			$pdf->SetX($x);
		}
	}

	function getHeight(&$pdf) {
		$ct = substr_count($this->text, "\n");
		// Dont do this. As the "extra" will count for every line in a multiline text, it will generate blank space equal to the number of lines.
//		if ($ct>0) $ct+=1;
		$style = $pdf->getCurrentStyleHeight();
		$h = ($style*$ct);
//		$h = ($style["size"]*$ct);
//		print "GMRElement getHeight [Text: ".$this->text." Stylename: ".$this->styleName." Stylesize: ".$style["size"]." Lines: ".$ct." Height: ".$h."]<br />";
		return $h;
	}

	function getWidth(&$pdf) {
		$debug = false;
		$pdf->setCurrentStyle($this->styleName);
		if (!isset($this->text)) $this->text = "";
		$lw = $pdf->GetStringWidth($this->text);
		if ($debug) print "<br />getWidth: lw: ".$lw."wrapWidth: ".$this->wrapWidth;
		if ($this->wrapWidth > 0) {
			if ($lw > $this->wrapWidth) {
				$lines = preg_split("/\n/", $this->text);
				$newtext = "";
				$wrapwidth = $this->wrapWidth;
				foreach($lines as $indexval => $line) {
					//LERMAN - add line at beginning at next line rather than end of previous
					if ($indexval > 0) $newtext .= "\n";
//					$w = $pdf->GetStringWidth($line)+10;
					$w = $pdf->GetStringWidth($line);
					if ($w>$wrapwidth) {
						$words = preg_split("/\s/", $line);
						$lw = 0;
						foreach($words as $indexval => $word) {
							$lw += $pdf->GetStringWidth($word." ");
							if ($lw <= $wrapwidth) $newtext.=$word." ";
							else {
								//print "<br />split on lw ".$lw." NEWLNE $word\n";
								$lw = $pdf->GetStringWidth($word." ");
								$newtext .= "\n$word ";
								$wrapwidth = $this->wrapWidth2;
							}
						}
						//LERMAN $newtext .= "\n";
					}
					else $newtext .= $line;//LERMAN ."\n";
				}
				$this->text = $newtext;
				//$this->text = preg_replace("/\n/", "\n~", $this->text);
				//print $this->wrapWidth." $lw [".$this->text."]1 ";
				return array($lw, 1);
			}
		}
		$l = 0;
		if (preg_match("/\n$/", $this->text)>0) $l=2;
		//print $this->wrapWidth." $lw [".$this->text."]$l ";
		return array($lw, $l);
	}

	function setWrapWidth($width, $width2) {
		//print "setting wrap widths $width $width2\n";
		$this->wrapWidth = $width;
		if (preg_match("/^\n/", $this->text)>0) $this->wrapWidth=$width2;
		$this->wrapWidth2 = $width2;
		return $this->wrapWidth;
	}

	function getStyleName() {
		return $this->styleName;
	}
}

/**
 * Footnote element
 */
class GMRFootnote extends GMRElement {
	var $styleName;
	var $addlink;
	var $num;

	function get_type() {
		return "GMRFootnote";
	}

	function GMRFootnote($style="") {
		$this->text = "";
		if (!empty($style)) $this->styleName = $style;
		else $this->styleName="footnote";
	}

	function render(&$pdf) {
		global $footnote_count, $embed_fonts;

		$fn = $pdf->checkFootnote($this);
		if ($fn===false) {
			$pdf->setCurrentStyle("footnotenum");
			$pdf->Write($pdf->getCurrentStyleHeight(),$this->num." ", $this->addlink);
		}
		else {
			$fn->rerender($pdf);
		}
	}

	function rerender(&$pdf) {
		global $footnote_count;
		if (empty($this->num)) {
			if (empty($footnote_count)) $footnote_count = 1;
			else $footnote_count++;

			$this->num = $footnote_count;
		}
		$pdf->setCurrentStyle("footnotenum");
		$pdf->Write($pdf->getCurrentStyleHeight(),$this->num." ", $this->addlink);
	}

	function renderFootnote(&$pdf) {
		global $embed_fonts;
		if ($pdf->getCurrentStyle()!=$this->styleName)
			$pdf->setCurrentStyle($this->styleName);
		$temptext = preg_replace("/#PAGENUM#/", $pdf->PageNo(), $this->text);

		$pdf->SetLink($this->addlink, -1);
		$pdf->Write($pdf->getCurrentStyleHeight(),$this->num.". ".$temptext."\n\n");
	}

	function addText($t) {
		global $embed_fonts, $TEXT_DIRECTION, $SpecialOrds;

		if (!isset($this->text)) $this->text = "";

		$found=false;
		foreach($SpecialOrds as $indexval => $ord) {
   			if (strpos($t, chr($ord))!==false) $found=true;
		}
   		if ($found) $embed_fonts = true;

		$t = trim($t, "\r\n\t");
		$t = preg_replace("/<br \/>/", "\n", $t);
		$t = strip_tags($t);
		$t = htmlspecialchars_decode ($t);
		$t = preg_replace(array('/&lrm;/','/&rlm;/'), array('',''), $t);
		if ($embed_fonts) $t = bidi_text($t);
		else $t = SmartUtf8Decode($t);
		$this->text .= $t;
	}

	function setNum($n) {
		$this->num = $n;
	}

	function setAddlink(&$a) {
		$this->addlink = $a;
	}
	
	function getFootnoteHeight(&$pdf) {
		$ct = substr_count($this->text, "\n");
		if ($ct>0) $ct+=1;
		$style = $pdf->getStyle($this->styleName);
		$h = (($style["size"]+1)*$ct);
		//print "[".$this->text." $ct $h]";
		return $h;
	}

}

/**
 * PageHeader element
 */
class GMRPageHeader extends GMRElement {
	var $elements = array();

	function get_type() {
		return "GMRPageHeader";
	}

	function GMRTextBox() {
		$this->elements = array();
	}

	function render(&$pdf) {
		$pdf->clearPageHeader();
		foreach($this->elements as $indexval => $element) {
			$pdf->addPageHeader($element);
		}
	}

	function addElement($element) {
		$this->elements[] = $element;
	}
}

/**
 * image element
 */
class GMRImage extends GMRElement {
	var $width;
	var $height;
	var $file;
	var $x;
	var $y;
	var $type;

	function get_type() {
		return "GMRImage";
	}

	function GMRImage($file, $x, $y, $w, $h, $t='') {
//		print "$file $x $y $w $h";
		$this->file = $file;
		$this->x = $x;
		$this->y = $y;
		$this->width = $w;
		//print "height: $h ";
		$this->height = $h;
		$this->type = $t;
	}

	function render(&$pdf) {
		global $lastpicbottom, $lastpicpage, $lastpicleft, $lastpicright;;
		if ($this->x==0) $this->x=$pdf->GetX();
		if ($this->y==0) {
			//-- first check for a collision with the last picture
			if (isset($lastpicbottom)) {
				if (($pdf->PageNo()==$lastpicpage)&&($lastpicbottom >= $pdf->GetY())&&($this->x>=$lastpicleft)&&($this->x<=$lastpicright))
					$pdf->SetY($lastpicbottom+5);
			}
			$this->y=$pdf->GetY();
		}
		$pdf->Image($this->file, $this->x, $this->y, $this->width, $this->height, $this->type);
		$lastpicbottom = $this->y + $this->height;
		$lastpicpage = $pdf->PageNo();
		$lastpicleft=$this->x;
		$lastpicright=$this->x+$this->width;
	}


	function getHeight(&$pdf) {
		return $this->height;
	}

	function getWidth(&$pdf) {
		return $this->width;
	}
} //-- END GMRImage

/**
 * line element
 */
class GMRLine extends GMRElement {
	var $x1;
	var $y1;
	var $x2;
	var $y2;

	function get_type() {
		return "GMRLine";
	}

	function GMRLine($x1, $y1, $x2, $y2) {
		$this->x1 = $x1;
		$this->y1 = $y1;
		$this->x2 = $x2;
		$this->y2 = $y2;
	}

	function render(&$pdf) {
		if ($this->x1==".") $this->x1=$pdf->GetX();
		if ($this->y1==".") $this->y1=$pdf->GetY();
		if ($this->x2==".") $this->x2=$pdf->GetX();
		if ($this->y2==".") $this->y2=$pdf->GetY();
		$pdf->Line($this->x1, $this->y1, $this->x2, $this->y2);
	}


	function getHeight(&$pdf) {
		return abs($this->y2 - $this->y1);
	}

	function getWidth(&$pdf) {
		return abs($this->x2 - $this->x1);
	}
} //-- END GMRLine

/**
 * element handlers array
 *
 * An array of element handler functions
 * @global array $elementHandler
 */
$elementHandler = array();
$elementHandler["GMRStyle"]["start"] 		= "GMRStyleSHandler";
$elementHandler["GMRDoc"]["start"] 			= "GMRDocSHandler";
$elementHandler["GMRDoc"]["end"] 			= "GMRDocEHandler";
$elementHandler["GMRHeader"]["start"] 		= "GMRHeaderSHandler";
$elementHandler["GMRFooter"]["start"] 		= "GMRFooterSHandler";
$elementHandler["GMRBody"]["start"] 		= "GMRBodySHandler";
$elementHandler["GMRCell"]["start"] 		= "GMRCellSHandler";
$elementHandler["GMRCell"]["end"] 			= "GMRCellEHandler";
$elementHandler["GMRPageNum"]["start"]		= "GMRPageNumSHandler";
$elementHandler["GMRTotalPages"]["start"]	= "GMRTotalPagesSHandler";
$elementHandler["GMRNow"]["start"]			= "GMRNowSHandler";
$elementHandler["GMRGedcom"]["start"]		= "GMRGedcomSHandler";
$elementHandler["GMRGedcom"]["end"]			= "GMRGedcomEHandler";
$elementHandler["GMRTextBox"]["start"] 		= "GMRTextBoxSHandler";
$elementHandler["GMRTextBox"]["end"] 		= "GMRTextBoxEHandler";
$elementHandler["GMRText"]["start"] 		= "GMRTextSHandler";
$elementHandler["GMRText"]["end"] 			= "GMRTextEHandler";
$elementHandler["GMRGetPersonName"]["start"]= "GMRGetPersonNameSHandler";
$elementHandler["GMRGedcomValue"]["start"]	= "GMRGedcomValueSHandler";
$elementHandler["GMRRepeatTag"]["start"]	= "GMRRepeatTagSHandler";
$elementHandler["GMRRepeatTag"]["end"]		= "GMRRepeatTagEHandler";
$elementHandler["GMRvar"]["start"]			= "GMRvarSHandler";
$elementHandler["GMRvarLetter"]["start"]	= "GMRvarLetterSHandler";
$elementHandler["GMRFacts"]["start"]		= "GMRFactsSHandler";
$elementHandler["GMRFacts"]["end"]			= "GMRFactsEHandler";
$elementHandler["GMRSetVar"]["start"]		= "GMRSetVarSHandler";
$elementHandler["GMRif"]["start"]			= "GMRifSHandler";
$elementHandler["GMRif"]["end"]				= "GMRifEHandler";
$elementHandler["GMRFootnote"]["start"]		= "GMRFootnoteSHandler";
$elementHandler["GMRFootnote"]["end"]		= "GMRFootnoteEHandler";
$elementHandler["GMRFootnoteTexts"]["start"]= "GMRFootnoteTextsSHandler";
$elementHandler["br"]["start"]				= "brSHandler";
$elementHandler["GMRPageHeader"]["start"] 	= "GMRPageHeaderSHandler";
$elementHandler["GMRPageHeader"]["end"] 	= "GMRPageHeaderEHandler";
$elementHandler["GMRHighlightedImage"]["start"] 		= "GMRHighlightedImageSHandler";
$elementHandler["GMRImage"]["start"] 		= "GMRImageSHandler";
$elementHandler["GMRLine"]["start"] 		= "GMRLineSHandler";
$elementHandler["GMRList"]["start"] 		= "GMRListSHandler";
$elementHandler["GMRList"]["end"] 			= "GMRListEHandler";
$elementHandler["GMRListTotal"]["start"]    = "GMRListTotalSHandler";
$elementHandler["GMRRelatives"]["start"]	= "GMRRelativesSHandler";
$elementHandler["GMRRelatives"]["end"] 		= "GMRRelativesEHandler";
$elementHandler["GMRGeneration"]["start"]   = "GMRGenerationSHandler";
$elementHandler["GMRNewPage"]["start"]		= "GMRNewPageSHandler";

$gmreport = new GMReport();
$gmreportStack = array();
$currentElement = new GMRElement();

/**
 * should character data be printed
 *
 * this variable is turned on or off by the element handlers to tell whether the inner character
 * data should be printed
 * @global bool $printData
 */
$printData = false;

/**
 * print data stack
 *
 * as the xml is being processed there will be times when we need to turn on and off the
 * <var>$printData</var> variable as we encounter entinties in the xml.  The stack allows us to
 * keep track of when to turn <var>$printData</var> on and off.
 * @global array $printDataStack
 */
$printDataStack = array();

$gedrec = "";
$gedrecStack = array();

$repeats = array();
$repeatBytes = 0;
$repeatsStack = array();
$parser = "";
$parserStack = array();
$processRepeats = 0;
$processIfs = 0;
$processGedcoms = 0;

/**
 * xml start element handler
 *
 * this function is called whenever a starting element is reached
 * @param resource $parser the resource handler for the xml parser
 * @param string $name the name of the xml element parsed
 * @param array $attrs an array of key value pairs for the attributes
 */
function startElement($parser, $name, $attrs) {
	global $elementHandler, $processIfs, $processGedcoms, $processRepeats, $vars;

	$newattrs = array();
	$temp = "";
	foreach($attrs as $key=>$value) {
		$ct = preg_match("/^\\$(\w+)$/", $value, $match);
		if ($ct>0) {
			if ((isset($vars[$match[1]]["id"]))&&(!isset($vars[$match[1]]["gedcom"]))) $value = $vars[$match[1]]["id"];
			//print "$match[0]=$value\n";
		}
		$newattrs[$key] = $value;
	}
	$attrs = $newattrs;
	if (($processIfs==0 || $name=="GMRif")&&($processGedcoms==0 || $name=="GMRGedcom")&&($processRepeats==0 || $name=="GMRFacts" || $name=="GMRRepeatTag")) {
		if (isset($elementHandler[$name]["start"])) call_user_func($elementHandler[$name]["start"], $attrs);
	}
}

/**
 * xml end element handler
 *
 * this function is called whenever an ending element is reached
 * @param resource $parser the resource handler for the xml parser
 * @param string $name the name of the xml element parsed
 */
function endElement($parser, $name) {
	global $elementHandler, $processIfs, $processGedcoms, $processRepeats;

	if (($processIfs==0 || $name=="GMRif")&&($processGedcoms==0 || $name=="GMRGedcom")&&($processRepeats==0 || $name=="GMRFacts" || $name=="GMRRepeatTag" || $name=="GMRList" || $name=="GMRRelatives")) {
		if (isset($elementHandler[$name]["end"])) call_user_func($elementHandler[$name]["end"]);
	}
}

/**
 * xml character data handler
 *
 * this function is called whenever raw character data is reached
 * just print it to the screen
 * @param resource $parser the resource handler for the xml parser
 * @param string $data the name of the xml element parsed
 */
function characterData($parser, $data) {
	global $printData, $currentElement, $processGedcoms, $processIfs;
	if ($printData && ($processGedcoms==0) && ($processIfs==0)) $currentElement->addText($data);
}

function GMRStyleSHandler($attrs) {
	global $gmreport;

	if (empty($attrs["name"])) return;

	$name = $attrs["name"];
	$font = "Times";
	$size = 12;
	$style = "";
	if (isset($attrs["font"])) $font = $attrs["font"];
	if (isset($attrs["size"])) $size = $attrs["size"];
	if (isset($attrs["style"])) $style = $attrs["style"];

	$s = array();
	$s["name"] = $name;
	$s["font"] = $font;
	$s["size"] = $size;
	$s["style"] = $style;
	$gmreport->addStyle($s);
}

function GMRDocSHandler($attrs) {
	global $pageSizes, $parser, $xml_parser, $gmreport;

	$parser = $xml_parser;

	$pageSize = $attrs["pageSize"];
	$orientation = $attrs["orientation"];
	$showGenText = true;
	if (isset($attrs['showGeneratedBy'])) $showGenText = $attrs['showGeneratedBy'];

	$margin = "";
	$margin = $attrs["margin"];

//	$gmreport->setup($pagew, $pageh, $pageSize, $orientation, $margin);
	$gmreport->setup(0, 0, $pageSize, $orientation, $margin, $showGenText);
}

function GMRDocEHandler() {
	global $gmreport;

	$gmreport->run();
}

function GMRHeaderSHandler($attrs) {
	global $gmreport;

	$gmreport->setProcessing("H");
}

function GMRPageHeaderSHandler($attrs) {
	global $printDataStack, $printData, $gmreportStack, $gmreport;

	array_push($printDataStack, $printData);
	$printData = false;

	array_push($gmreportStack, $gmreport);
	$gmreport = new GMRPageHeader();
}

function GMRPageHeaderEHandler() {
	global $printData, $printDataStack;
	global $gmreport, $currentElement, $gmreportStack;

	$printData = array_pop($printDataStack);
	$currentElement = $gmreport;
	$gmreport = array_pop($gmreportStack);
	$gmreport->addElement($currentElement);
}

function GMRFooterSHandler($attrs) {
	global $gmreport;

	$gmreport->setProcessing("F");
}

function GMRBodySHandler($attrs) {
	global $gmreport;

	$gmreport->setProcessing("B");
}

function GMRCellSHandler($attrs) {
	global $printData, $printDataStack, $currentElement;

	array_push($printDataStack, $printData);
	$printData = true;

	$width = 0;
	$height= 0;
	$align= "left";
	$style= "";

	if (isset($attrs["width"])) $width = $attrs["width"];
	if (isset($attrs["height"])) $height = $attrs["height"];
	if (isset($attrs["align"])) $align = $attrs["align"];
	if ($align=="left") $align="L";
	if ($align=="right") $align="R";
	if ($align=="center") $align="C";
	if ($align=="justify") $align="J";

	if (isset($attrs["style"])) $style = $attrs["style"];

	$currentElement = new GMRCell($width, $height, $align, $style);
}

function GMRCellEHandler() {
	global $printData, $printDataStack, $currentElement, $gmreport;

	$printData = array_pop($printDataStack);
	$gmreport->addElement($currentElement);
}

function GMRNowSHandler($attrs) {
	global $currentElement;

	$currentElement->addText(GetChangedDate(date("j", time()-(isset($_SESSION["timediff"])?$_SESSION["timediff"]:0))." ".date("M", time()-(isset($_SESSION["timediff"])?$_SESSION["timediff"]:0))." ".date("Y", time()-(isset($_SESSION["timediff"])?$_SESSION["timediff"]:0))));
}

function GMRPageNumSHandler($attrs) {
	global $currentElement;

	$currentElement->addText("#PAGENUM#");
}

function GMRTotalPagesSHandler($attrs) {
	global $currentElement;

	$currentElement->addText("{nb}");
}

function GMRGedcomSHandler($attrs) {
	global $vars, $gedrec, $gedrecStack, $processGedcoms, $fact, $desc, $ged_level;

	$debug = false;
	if ($processGedcoms>0) {
		$processGedcoms++;
		return;
	}
	$id = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}

	$tag = $attrs["id"];

//LERMAN - add ability to put a variable in the tag
	$ct = preg_match_all("/\\$(\w+)/", $tag, $match, PREG_SET_ORDER);
	for($i=0; $i<$ct; $i++) {
		$t = $vars[$match[$i][1]]["id"];
		$tag = preg_replace("/\\$".$match[$i][1]."/", $t, $tag, 1);
	}

	$tag = preg_replace("/@fact/", $fact, $tag);
	if ($debug) print "[$tag]";
	$tags = preg_split("/:/", $tag);
	$newgedrec = "";
	if (count($tags)<2) {
		$obj = ConstructObject($tag);
		if (is_object($obj)) $newgedrec = $obj->gedrec;
		if ($debug) print "1 rec added: ".$newgedrec."<br />";
	}
	if (empty($newgedrec)) {
		$tgedrec = $gedrec;
		$newgedrec = "";
		foreach($tags as $indexval => $tag) {
			if ($debug) print "[$tag]";
			$ct = preg_match("/\\$(.+)/", $tag, $match);
			if ($ct>0) {
				if (isset($vars[$match[1]]["gedcom"])) $newgedrec = $vars[$match[1]]["gedcom"];
				else {
					$obj = ConstructObject($match[1]);
					if (is_object($obj)) $newgedrec = $obj->gedrec;
				}
				if ($debug) print "2 rec added: ".$newgedrec."<br />";
			}
			else {
				$ct = preg_match("/@(.+)/", $tag, $match);
				if ($ct>0) {
					$gt = preg_match("/\d $match[1] @([^@]+)@/", $tgedrec, $gmatch);
					//print $gt;
					if ($gt > 0) {
						//print "[".$gmatch[1]."]";
						$obj = ConstructObject($gmatch[1]);
						if (is_object($obj)) $newgedrec = $obj->gedrec;
						if ($debug) print "3 rec added: ".$newgedrec."<br />";
						//print $newgedrec;
						$tgedrec = $newgedrec;
					}
					else {
						//print "[$tgedrec]";
						$newgedrec = "";
						break;
					}
				}
				else {
					$temp = preg_split("/\s+/", trim($tgedrec));
					$level = $temp[0] + 1;
					if (PrivacyFunctions::showFact($tag, $id) && PrivacyFunctions::showFactDetails($tag,$id)) {
						$newgedrec = GetSubRecord($level, "$level $tag", $tgedrec);
						$tgedrec = $newgedrec;
					}
					else {
						$newgedrec = "";
						break;
					}
				}
			}
		}
	}
	if (!empty($newgedrec)) {
		$rectype = GetRecType($gedrec);	
		$newreclevel = GetRecLevel($newgedrec);
		$newid = GetRecID($newgedrec);
		$newrectype = GetRecType($newgedrec);
		if (!empty($newid)) $newobj = ConstructObject($newid, $newrectype);
		if (!empty($id)) $obj = ConstructObject($id, $rectype);
		if ($debug) {
			print "Start output<br />";
			print "newgedrec: ".$newgedrec."<br />";
			print "tag: ".$tags[0]."<br />fact: ".$fact."<br />id: ".$id."<br />rectype: ".$rectype."<br />desc: ".$desc."<br />gedrec: ".$gedrec."<br />rectype: ".$rectype."<br />newreclevel ".$newreclevel."<br />newrectype ".$newrectype."<br />newid ".$newid."<br />";
		}
		if (($newreclevel == 1 && !PrivacyFunctions::FactViewRestricted($id, $newgedrec)) || 
		($newreclevel == 0 && $newobj->disp) || (!empty($id) && $obj->disp)) {
			if ($debug) print "can show<br />";
			array_push($gedrecStack, array($gedrec, $fact, $desc));
			//print "[$newgedrec]";
//			$gedrec = $gedObj->getGedcomRecord();
			$gedrec = $newgedrec;
			$ct = preg_match("/(\d+) (_?[A-Z0-9]+) (.*)/", $gedrec, $match);
			if ($debug) print_r($match);
			if ($ct>0) {
				$ged_level = $match[1];
				$fact = $match[2];
				$desc = trim($match[3]);
			}
		}
		else {
			$processGedcoms++;
			if ($debug) print "can NOT show<br /><br />";
		}
		if ($debug) print "<br />End output<br /><br />";
	}
	else {
		$processGedcoms++;
	}
}

function GMRGedcomEHandler() {
	global $gedrec, $gedrecStack, $processGedcoms, $fact, $desc;

	if ($processGedcoms>0) {
		$processGedcoms--;
	}
	else {
		$temp = array_pop($gedrecStack);
		$gedrec = $temp[0];
		$fact = $temp[1];
		$desc = $temp[2];
	}
}

function GMRTextBoxSHandler($attrs) {
	global $printData, $printDataStack;
	global $gmreport, $currentElement, $gmreportStack;

	$width = 0;
	$height= 0;
	$border= 0;
	$newline = 0;
	$fill = "";
	$style = "D";
	$left = ".";
	$top = ".";
	$pagecheck="true";

	if (isset($attrs["width"])) $width = $attrs["width"];
	if (isset($attrs["height"])) $height = $attrs["height"];
	if (isset($attrs["border"])) $border = $attrs["border"];
	if (isset($attrs["newline"])) $newline = $attrs["newline"];
	if (isset($attrs["fill"])) $fill = $attrs["fill"];
	if (isset($attrs["left"])) $left = $attrs["left"];
	if (isset($attrs["top"])) $top = $attrs["top"];
	if (isset($attrs["pagecheck"])) $pagecheck = $attrs["pagecheck"];

	array_push($printDataStack, $printData);
	$printData = false;

	array_push($gmreportStack, $gmreport);
	$gmreport = new GMRTextBox($width, $height, $border, $fill, $newline, $left, $top, $pagecheck);
}

function GMRTextBoxEHandler() {
	global $printData, $printDataStack;
	global $gmreport, $currentElement, $gmreportStack;

	$printData = array_pop($printDataStack);
	$currentElement = $gmreport;
	$gmreport = array_pop($gmreportStack);
	$gmreport->addElement($currentElement);
}

function GMRTextSHandler($attrs) {
	global $printData, $printDataStack;
	global $gmreport, $currentElement;

	array_push($printDataStack, $printData);
	$printData = true;

	$style = "";
	if (isset($attrs["style"])) $style = $attrs["style"];

	$color = "#000000";
	if (isset($attrs["color"])) $color = $attrs["color"];

	$align= "left";
	if (isset($attrs["align"])) $align = $attrs["align"];
	if ($align=="left") $align="L";
	if ($align=="right") $align="R";
	if ($align=="center") $align="C";
	//if ($align=="justify") $align="J";

	$currentElement = new GMRText($style, $color, $align);
}

function GMRTextEHandler() {
	global $printData, $printDataStack, $gmreport, $currentElement;

	$printData = array_pop($printDataStack);
	$gmreport->addElement($currentElement);
}

function GMRGetPersonNameSHandler($attrs) {
	global $currentElement, $vars, $gedrec, $gedrecStack;

	$showIndID = GedcomConfig::$SHOW_ID_NUMBERS; // false, 0, "0", NOT "false"
	if (isset($vars["showIndID"]["id"])) $showIndID = $vars["showIndID"]["id"];
	if ($showIndID) { // can override showing
		if (isset($attrs["hideID"])) {
			$hideID = $attrs["hideID"];
			if (preg_match("/\\$(\w+)/", $hideID, $vmatch)>0) {
				$hideID = trim($vars[$vmatch[1]]["id"]);
			}
			$showIndID = !$hideID;
		}
	}

	$id = "";
	if (empty($attrs["id"])) {
		$ct = preg_match("/0 @(.+)@/", $gedrec, $match);
		if ($ct>0) $id = $match[1];
	} else {
		$ct = preg_match("/\\$(.+)/", $attrs["id"], $match);
		if ($ct>0) {
			if (isset($vars[$match[1]]["id"])) {
				$id = $vars[$match[1]]["id"];
			}
		} 
		else {
			$ct = preg_match("/@(.+)/", $attrs["id"], $match);
			if ($ct>0) {
				$gt = preg_match("/\d $match[1] @([^@]+)@/", $gedrec, $gmatch);
				//print $gt;
				if ($gt > 0) {
					$id = $gmatch[1];
					//print "[$id]";
				}
			} 
			else {
				$id = $attrs["id"];
			}
		}
	}
	if (!empty($id)) {
		$object = ConstructObject($id);
		if (!$object->disp_name) {
			$name = GM_LANG_private;
		} 
		else {
			$name = $object->name;
			// This is a workaround to display the PinYin name instead of the name in Chinese characters, as Chinese characters are not printed properly.
			if (NameFunctions::HasChinese($name, true)) $name = $object->addname;
			if (!empty($attrs["truncate"])) {
					$name = NameFunctions::AbbreviateName($name, $attrs["truncate"]);
			}
		}
		$currentElement->addText(trim($name));
		if ($showIndID) $currentElement->addText($object->addxref);
	}
}

function GMRGedcomValueSHandler($attrs) {
	global $currentElement, $vars, $gedrec, $gedrecStack, $fact, $desc, $type;
	global $debug;

	$id = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}

	$tag = $attrs["tag"];
	//print $tag;
	//print "<br /><br />tag: ".$tag." fact: ".$fact." desc: ".$desc."<br />";
	if (!empty($tag)) {
		if ($tag=="@desc") {
			if (PrivacyFunctions::showFact($fact, $id) && PrivacyFunctions::showFactDetails($fact,$id)) $value = $desc;
			else $value = GM_LANG_private;
			$value = trim($value);
			if (NameFunctions::HasChinese($value, true)) $currentElement->addText(NameFunctions::GetPinYin($value, true));
			else $currentElement->addText($value);
		}
		if ($tag=="@id") {
			$currentElement->addText($id);
		}
		else {
//LERMAN - add ability to put a variable in the tag
		        $ct = preg_match_all("/\\$(\w+)/", $tag, $match, PREG_SET_ORDER);
		        for($i=0; $i<$ct; $i++) {
		                $t = $vars[$match[$i][1]]["id"];
		                $tag = preg_replace("/\\$".$match[$i][1]."/", $t, $tag, 1);
		        }

			$tag = preg_replace("/@fact/", $fact, $tag);
			if (empty($attrs["level"])) {
				$temp = preg_split("/\s+/", trim($gedrec));
				$level = $temp[0];
				if ($level==0) $level++;
			}
			else $level = $attrs["level"];
			$truncate = "";
			if (isset($attrs["truncate"])) $truncate=$attrs["truncate"];
			$tags = preg_split("/:/", $tag);
			//-- check all of the tags for privacy
			foreach($tags as $t=>$subtag) {
				if (!empty($subtag)) {
					if (!PrivacyFunctions::showFact($tag, $id)||!PrivacyFunctions::showFactDetails($tag,$id)) return;
				}
			}
//LERMAN - add ability to get changed data
                        if (isset($attrs["changed"]) && $attrs["changed"] && ChangeFunctions::GetChangeData(true, $id, true, "gedlines")) {
				$pend_gedcoms = ChangeFunctions::GetChangeData(false, $id, true, "gedlinesCHAN");
				foreach($pend_gedcoms as $gedcom=>$pend_indis) {
					if ($gedcom == GedcomConfig::$GEDCOMID) {
						foreach ($pend_indis as $key=>$changed) {
							$value = GetGedcomValue($tag, $level, $changed, $truncate);
						}
					}
				}
			} else {
				$value = GetGedcomValue($tag, $level, $gedrec, $truncate);
				if ($fact == "NOTE") $value .= GetCont($level, $gedrec);
			}
			if ($debug) print "gedrec: ".$gedrec." tag: ".$tag." value: ".$value." fact: ".$fact."<br /><br />";
		//print "tag: ".$tag."<br />fact: ".$fact."<br />id: ".$id."<br />desc: ".$desc."<br />type: ".$type."<br />gedrec: ".$gedrec."<br />value: ".$value."<br />level: ".$level."<br /><br />";
			if (PrivacyFunctions::showFact($tags[0], $id) && PrivacyFunctions::showFactDetails($tags[0],$id)) {
				if (!empty($id)) $factrec = GetSubRecord(1, $tags[0], $gedrec);
				else $factrec = "";
		//print "<br /><br />value: ".nl2br($value);
				if (!PrivacyFunctions::FactViewRestricted($id, $factrec)) {
					if (NameFunctions::HasChinese($value, true)) $currentElement->addText(NameFunctions::GetPinYin($value, true));
					else $currentElement->addText($value);
				}
			}
		}
	}
}

function GMRRepeatTagSHandler($attrs) {
	global $repeats, $repeatsStack, $gedrec, $repeatBytes, $parser, $parserStack, $processRepeats;
	global $fact, $desc, $debug;
	
	$debug = false;
	//if ($attrs["tag"] == "NOTE") $debug = true;

	$processRepeats++;
	if ($processRepeats>1) return;

	$id = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}

	array_push($repeatsStack, array($repeats, $repeatBytes));
	$repeats = array();
	$repeatBytes = xml_get_current_line_number($parser);

	$tag = "";
	if (isset($attrs["tag"])) $tag = $attrs["tag"];
	if ($debug) {
		print "Start debug<br />";
		print "tag: ".$tag."<br />fact: ".$fact."<br />id: ".$id."<br />desc: ".$desc."<br />gedrec: ".$gedrec."<br /><br />";
	}
	$disp = true;
	if (!empty($id)) {
		$obj = ConstructObject($id);
		if (is_object($obj)) $disp = $obj->disp;
	}
	if (!empty($tag) && $disp) {
		// Get the factrec to check RESN privacy
		$sub = GetSubRecord(1, "1 ".$tag, $gedrec);
		if ($debug) print "sub: ".$sub."<br />";
		if (!PrivacyFunctions::FactViewRestricted($id, $sub)) {
			if ($debug) print "processing......<br />";
			if ($tag=="@desc") {
				if ($debug) Print "@desc branch<br />";
				if (PrivacyFunctions::showFact($fact, $id) && PrivacyFunctions::showFactDetails($fact,$id)) $value = $desc;
				else $value = "";
				$value = trim($value);
				$currentElement->addText($value);
			}
			else {
				if ($debug) Print "else branch<br />";
				$tag = preg_replace("/@fact/", $fact, $tag);
				$tags = preg_split("/:/", $tag);
				if ($debug) {
					Print_r($tags);
					print "<br />";
				}
				$temp = preg_split("/\s+/", trim($gedrec));
				$level = $temp[0];
				if ($level==0) $level++;
				$subrec = $gedrec;
				$t = $tag;
				for($i=0; $i<count($tags); $i++) {
					$t = $tags[$i];
					if (!empty($t)) {
						if ($level==1 && strstr("CHIL,FAMS,FAMC", $t)===false && (!PrivacyFunctions::showFact($t, $id) || !PrivacyFunctions::showFactDetails($t,$id))) return;
						if ($i<count($tags)-1) {
							$subrec = GetSubRecord($level, "$level $t", $subrec);
							if ($debug) print "<br />subrec: ".$subrec."<br />";
							if (empty($subrec)) {
								$level--;
								$subrec = GetSubRecord($level, "@ $t", $gedrec);
								if ($debug) print "<br />subrec2: ".$subrec."<br />";
								if (empty($subrec)) return;
							}
						}
						if ($debug) print "[$level $t] ";
						$level++;
					}
				}
				$level--;
				if ($level!=1 || strstr("CHIL,FAMS,FAMC", $t)!==false || (PrivacyFunctions::showFact($t, $id) && PrivacyFunctions::showFactDetails($t,$id))) {
					$ct = preg_match_all("/$level $t(.*)/", $subrec, $match, PREG_SET_ORDER);
					if ($debug) print "$ct $subrec";
					for($i=0; $i<$ct; $i++) {
						$rec = GetSubRecord($level, "$level $t", $gedrec, $i+1);
						if ($debug) print "<br />subrec3: ".$rec."<br /><br />";
						// Check if the record points to a linked note
						$ct2 = preg_match("/".$level."\sNOTE\s@(.+)@\s/", $rec, $nmatch);
						if ($ct2>0) {
							if ($debug) print "Linked note found to ".$nmatch[1];
							$obj = Note::GetInstance($nmatch[1]);
							$rec = $obj->gedrec;
//		print "4 rec added: ".$newgedrec."<br />";
							$fact = "NOTE";
							//$noterecs = GetAllSubRecords($noterec);
							//$rec = preg_replace("/@.+@/", "", $rec);
							//$clevel = $level + 1;
							//foreach($noterecs as $key =>$sub) {
							//	if (preg_match("/\d\sCON[CT]\s/", $sub)) {
							//		$rec .= "\r\n".$clevel.substr($sub,1);
							//	}
							//}
						}
						$repeats[] = $rec;
					}
					//$repeats = array_reverse($repeats);
					if ($debug) {
						print "<br />Dump of repeats: ";
						print_r($repeats);
					}
				}
			}
		}
		if ($debug) Print "Done or not<br /><br />";
	}
}

function GMRRepeatTagEHandler() {
	global $repeats, $repeatsStack, $repeatBytes, $parser, $parserStack, $report, $gmreport, $gedrec, $processRepeats;

	$processRepeats--;
	if ($processRepeats>0) return;

	$line = xml_get_current_line_number($parser)-1;
	$lineoffset = 0;
	for($i=0; $i<count($repeatsStack); $i++) {
		$p = $repeatsStack[$i];
		$l = $p[1];
		$lineoffset += $l;
	}
	//-- read the xml from the file
	$lines = file($report);
	$reportxml = "<tempdoc>\n";
	while(strstr($lines[$lineoffset+$repeatBytes], "<GMRRepeatTag")===false) $lineoffset--;
	$lineoffset++;
	$line1 = $repeatBytes;
	$ct = 1;
	while(($ct>0)&&($line1<$line+2)) {
		if (strstr($lines[$lineoffset+$line1], "<GMRRepeatTag")!==false) $ct++;
		if (strstr($lines[$lineoffset+$line1], "</GMRRepeatTag")!==false) $ct--;
		$line1++;
	}
	$line = $line1-1;
	for($i=$repeatBytes+$lineoffset; $i<$line+$lineoffset; $i++) $reportxml .= $lines[$i];
	$reportxml .= "</tempdoc>\n";
	//print $reportxml."\n";
	array_push($parserStack, $parser);

	$oldgedrec = $gedrec;
	for($i=0; $i<count($repeats); $i++) {
		$gedrec = $repeats[$i];
		//-- start the sax parser
		$repeat_parser = xml_parser_create();
		$parser = $repeat_parser;
		//-- make sure everything is case sensitive
		xml_parser_set_option($repeat_parser, XML_OPTION_CASE_FOLDING, false);
		//-- set the main element handler functions
		xml_set_element_handler($repeat_parser, "startElement", "endElement");
		//-- set the character data handler
		xml_set_character_data_handler($repeat_parser, "characterData");

		if (!xml_parse($repeat_parser, $reportxml, true)) {
			printf($reportxml."\nGMRRepeatTagEHandler XML error: %s at line %d", xml_error_string(xml_get_error_code($repeat_parser)), xml_get_current_line_number($repeat_parser));
			print_r($repeatsStack);
			exit;
		}
		xml_parser_free($repeat_parser);
	}
	$parser = array_pop($parserStack);

	$gedrec = $oldgedrec;
	$temp = array_pop($repeatsStack);
	$repeats = $temp[0];
	$repeatBytes = $temp[1];
}

function GMRvarSHandler($attrs) {
	global $currentElement, $vars, $gedrec, $gedrecStack, $fact, $desc, $type;

	$var = $attrs["var"];
	if (!empty($var)) {
		if (!empty($vars[$var]['id'])) {
			$var = $vars[$var]['id'];
		}
		else {
//LERMAN - add ability to put a variable in the tag
			$ct = preg_match_all("/\\$(\w+)/", $var, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				if (isset($vars[$match[$i][1]]["id"])) {
					$t = $vars[$match[$i][1]]["id"];
					$var = preg_replace("/\\$".$match[$i][1]."/", $t, $var, 1);
				}
				// Just a loose variable. Make it global.
				else {
					$var = $match[$i][1];
					eval ("global \$$var;");
				}
			}

			$tfact = $fact;
			if ($fact=="EVEN" || $fact=="FACT") $tfact = $type;
//			print "var2: ".$var."<br />";
			$var = preg_replace(array("/\[/","/\]/","/@fact/","/@desc/"), array("['","']",$tfact,$desc), $var);
			//print "var3: ".$var."<br />";
			if ((substr($var, 0, 8) == "GM_LANG_" || substr($var, 0, 8) == "GM_FACT_") && defined($var)) $var = constant($var);
			else if (strstr($var, " ") === false) eval("if (isset(\$$var) && !empty(\$$var)) \$var = \$$var;");
		}
		$currentElement->addText($var);
	}
}

function GMRvarLetterSHandler($attrs) {
	global $currentElement, $fact, $desc;

	$var = $attrs["var"];
	if (!empty($var)) {
		$tfact = $fact;
		$var = preg_replace(array("/\[/","/\]/","/@fact/","/@desc/"), array("['","']",$tfact,$desc), $var);
			if ((substr($var, 0, 8) == "GM_LANG_" || substr($var, 0, 8) == "GM_FACT_") && defined($var)) $var = constant($var);
			else if (strstr($var, " ") === false) eval("if (isset(\$$var) && !empty(\$$var)) \$var = \$$var;");

		$letter = NameFunctions::GetFirstLetter($var);

		$currentElement->addText($letter);
	}
}

function GMRFactsSHandler($attrs) {
	global $repeats, $repeatsStack, $gedrec, $parser, $parserStack, $repeatBytes, $processRepeats, $vars;

	$processRepeats++;
	if ($processRepeats>1) return;

	$families = 1;
	if (isset($attrs["families"])) $families = $attrs["families"];
	// The code below is to restrict the family facts for the person to the relevant family only
	if (isset($vars["restrict_fam"])) $families = array($vars["restrict_fam"]["id"]);

	array_push($repeatsStack, array($repeats, $repeatBytes));
	$repeats = array();
	$repeatBytes = xml_get_current_line_number($parser);

	$id = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}

	$tag = "";
	if (isset($attrs["ignore"])) $tag .= $attrs["ignore"];
	$ct = preg_match("/\\$(.+)/", $tag, $match);
	if ($ct>0) {
		$tag = $vars[$match[1]]["id"];
	}

//LERMAN
	$diff = 0;
	if (isset($attrs["diff"])) $diff = $attrs["diff"];

	if (!$diff) {
		$repeats = GetAllSubrecords($gedrec, $tag, $families);
//		print_r($repeats);
//		print "<br /><br />";
	} else {
		$ignorefacts = preg_split("/[\s,;:]/", $tag);
		$id = GetRecID($gedrec);
		$oldperson = Person::GetInstance($id);
		$facts = ChangeFunctions::RetrieveNewFacts($oldperson->xref, true);
		foreach ($facts as $key=>$fact) {
			$ct = preg_match("/1 (.+)/", $fact, $match);
			if ($ct<=0) {
				$fact = preg_replace("/0 @(.+)@/", "1", $fact);
				$ct = preg_match("/1 (.+)/", $fact, $match);
			}
			if ($ct>0) {
				if (!in_array ($match[1], $ignorefacts)) {
					$repeats[] = $fact;
				}
			}
		}
	}
}

function GMRFactsEHandler() {
	global $repeats, $repeatsStack, $repeatBytes, $parser, $parserStack, $report, $gedrec, $fact, $desc, $type, $processRepeats;

	$processRepeats--;
	if ($processRepeats>0) return;

	$line = xml_get_current_line_number($parser)-1;
	$lineoffset = 0;
	for($i=0; $i<count($repeatsStack); $i++) {
		$p = $repeatsStack[$i];
		$l = $p[1];
		$lineoffset += $l;
	}
	//-- read the xml from the file
	$lines = file($report);
	$reportxml = "<tempdoc>\n";
	//--back up to beginning of GMRFacts and then get past it
	while($lineoffset+$repeatBytes>0 && strstr($lines[$lineoffset+$repeatBytes], "<GMRFacts ")===false) $lineoffset--;
	$lineoffset++;
	for($i=$repeatBytes+$lineoffset; $i<$line+$lineoffset; $i++) {
		$reportxml .= $lines[$i];
	}
	$reportxml .= "</tempdoc>\n";

	array_push($parserStack, $parser);
	$oldgedrec = $gedrec;
	for($i=0; $i<count($repeats); $i++) {
		$gedrec = $repeats[$i];
		$ft = preg_match("/1 (\w+)(.*)/", $gedrec, $match);
		$fact = "";
		$desc = "";
		$type = "";
		if ($ft > 0) {
			$fact = $match[1];
			if ($fact=="EVEN" || $fact=="FACT") {
				$tt = preg_match("/2 TYPE (.+)/", $gedrec, $tmatch);
				if ($tt>0) {
					$type = trim($tmatch[1]);
				}
			}
			$desc = trim($match[2]);
			$desc .= GetCont(2, $gedrec);
		}
//		print "fact: ".$fact." desc: ".$desc."<br />";
		//-- start the sax parser
		$repeat_parser = xml_parser_create();
		$parser = $repeat_parser;
		//-- make sure everything is case sensitive
		xml_parser_set_option($repeat_parser, XML_OPTION_CASE_FOLDING, false);
		//-- set the main element handler functions
		xml_set_element_handler($repeat_parser, "startElement", "endElement");
		//-- set the character data handler
		xml_set_character_data_handler($repeat_parser, "characterData");

		if (!xml_parse($repeat_parser, $reportxml, true)) {
			die(sprintf($reportxml."\nGMRFactsEHandler XML error: %s at line %d", xml_error_string(xml_get_error_code($repeat_parser)), xml_get_current_line_number($repeat_parser)));
		}
		xml_parser_free($repeat_parser);
	}
	$parser = array_pop($parserStack);
	$gedrec = $oldgedrec;
	$temp = array_pop($repeatsStack);
	$repeats = $temp[0];
	$repeatBytes = $temp[1];
}

function NumToRoman($num, $lower) {
	$result = $num;	// return input if an error
	if (preg_match('/^[0-9]+$/', $num) === 1) {
		// only have numbers
		$num = intval($num);
		if (($num > 0) && ($num <= 3999)) {
			// valid range
			$lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90,
				'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
			$result = '';
			foreach ($lookup as $roman=>$value) {
				$matches = intval($num / $value);
				$result .= str_repeat($roman, $matches);
				$num = $num % $value;
			}
			if ($lower) $result = strtolower($result);
		}
	}
	return $result;
}

function GMRSetVarSHandler($attrs) {
	global $vars, $gedrec, $gedrecStack, $fact, $desc, $type, $generation;

	$name = $attrs["name"];
	$value = $attrs["value"];
	if ($name == "NICK") {
		GedcomConfig::$SHOW_NICK = ($value == "false" ? false : true);
		return;
	}
	if ($value=="@ID") {
		$ct = preg_match("/0 @(.+)@/", $gedrec, $match);
		if ($ct>0) $value = $match[1];
	}
	if ($value=="@fact") {
		$value = $fact;
	}
	if ($value=="@desc") {
		$value = $desc;
	}
	if ($value=="@generation") {
		$value = $generation;
	}
	$ct = preg_match("/\\$(\w+)/", $name, $match);
	if ($ct>0) {
		$name = $vars["'".$match[1]."'"]["id"];
	}

	$ct = preg_match("/@(\w+)/", $value, $match);
	if ($ct>0) {
		$gt = preg_match("/\d $match[1] (.+)/", $gedrec, $gmatch);
		if ($gt > 0) $value = preg_replace("/@/", "", trim($gmatch[1]));
	}

//	if ((substr($value, 0, 9) == "\GM_LANG_)) {
//		$var = preg_replace(array("/\[/","/\]/"), array("GM_LANG_",""), $value);
//		eval("\$value = $var;");
//	}
	if (substr($value, 0, 8) == "GM_FACT_" || substr($value, 0, 8) == "GM_LANG_") {
		$value = constant($value);
//		eval("\$value = $var;");
	}
//	if (substr($value, 0, 9) == "\$gm_lang[") {
//		$var = preg_replace(array("/\$gm_lang\[/","/\]/"), array("GM_LANG_",""), $value);
//		eval("\$value = $var;");
//	}

	$ct = preg_match_all("/\\$(\w+)/", $value, $match, PREG_SET_ORDER);
	for($i=0; $i<$ct; $i++) {
		//print $match[$i][1]."<br />";
		$t = $vars[$match[$i][1]]["id"];
		$value = preg_replace("/\\$".$match[$i][1]."/", $t, $value, 1);
	}

	$ct = preg_match("/(\d+)\s*([\-\+\*\/])\s*(\d+)/", $value, $match);
	if ($ct>0) {
		switch($match[2]) {
			case '-':
				$t = $match[1] - $match[3];
				$value = preg_replace("/".$match[1]."\s*([\-\+\*\/])\s*".$match[3]."/", $t, $value);
				break;
			case '+':
				$t = $match[1] + $match[3];
				$value = preg_replace("/".$match[1]."\s*([\-\+\*\/])\s*".$match[3]."/", $t, $value);
				break;
			case '*':
				$t = $match[1] * $match[3];
				$value = preg_replace("/".$match[1]."\s*([\-\+\*\/])\s*".$match[3]."/", $t, $value);
				break;
			case '/':
				$t = $match[1] / $match[3];
				$value = preg_replace("/".$match[1]."\s*([\-\+\*\/])\s*".$match[3]."/", $t, $value);
				break;
		}
	}
//	print "$name=[$value] ";
	if (strstr($value, "@")!==false) $value="";

	if (isset($attrs["option"])) {
		switch ($attrs["option"]) {
		case "NumToUpperRoman":
			$value = NumToRoman($value, false);
			break;
		case "NumToLowerRoman":
			$value = NumToRoman($value, true);
			break;
		}
	}

	$vars[$name]["id"]=$value;
}

function GMRifSHandler($attrs) {
	global $vars, $gedrec, $processIfs, $fact, $desc, $generation;

	$debug = false;
	if ($processIfs>0) {
		$processIfs++;
		return;
	}
	if ($debug) print "Init fact: ".$fact." desc: ".$desc."<br />";
	$vars['POSTAL_CODE']['id'] = GedcomConfig::$POSTAL_CODE;
	$conditions = preg_split("/\s[OR|or]\s/",$attrs["condition"]);
	$final_condition = "";
	$count = 1;
	foreach ($conditions as $key => $condition) {
		$condition = preg_replace("/\\$(\w+)/", "\$vars['$1'][\"id\"]", $condition);
		$condition = preg_replace(array("/ LT /", "/ GT /"), array("<", ">"), $condition);
		if ($debug) print "Evaluating condition: ".$condition."<br />";
		$ct = preg_match_all("/@([\w:]+)/", $condition, $match);
		for ($i=0;$i<$ct;$i++) {
			$id = $match[1][$i];
			$value="''";
			switch ($id) {
			case "ID":
				$ct = preg_match("/0 @(.+)@/", $gedrec, $match);
				if ($ct>0) $value = "'".$match[1]."'";
				break;
			case "fact":
				$value = "'$fact'";
				break;
			case "desc":
				$value = "'$desc'";
				break;
			case "generation":
				$value = "'$generation'";
				break;
			case "SHOW_ID_NUMBERS":
				$value = "'".GedcomConfig::$SHOW_ID_NUMBERS."'";
				break;
	                case "SHOW_FAM_ID_NUMBERS":
	                        $value = "'".GedcomConfig::$SHOW_FAM_ID_NUMBERS."'";
				break;
			default:
				$temp = preg_split("/\s+/", trim($gedrec));
				$level = $temp[0];
				if ($level==0) $level++;
				$value = GetGedcomValue($id, $level, $gedrec, "", false);
				// print "level:$level id:$id value:$value ";
				if (empty($value)) {
					$level++;
					$value = GetGedcomValue($id, $level, $gedrec, "", false);
					//print "level:$level id:$id value:$value gedrec:$gedrec<br />\n";
				}
				$value = "'".preg_replace("/'/", "\\'", $value)."'";
				break;
			}
			if ($debug) print "Value: ".$value." id: ".$id." condition: ".$condition."<br />";
			$replstring = "/@".$id."/";
			if ($debug) print "Replacing with: ".$replstring." in ".$condition." with ".$value."<br />";
			if (!empty($id)) $condition = preg_replace($replstring, $value, $condition);
			if ($debug) print "result: ".$condition."<br />";
		}
		if ($count != 1) $final_condition .= " || "; 
		$count++;
		$final_condition .= $condition;
	}
	$final_condition = "if ($final_condition) return true; else return false;";
	$ret = @eval($final_condition);
	if ($debug) print $final_condition."<br />";
//	print_r($vars);
	if ($debug) if ($ret) print " true<br />"; else print " false<br />End check<br /><br />";
	if (!$ret) {
		$processIfs++;
	}
}

function GMRifEHandler() {
	global $vars, $gedrec, $processIfs;

	if ($processIfs>0) $processIfs--;
}

function GMRFootnoteSHandler($attrs) {
	global $printData, $printDataStack;
	global $gmreport, $currentElement, $footnoteElement;

	array_push($printDataStack, $printData);
	$printData = true;

	$style = "";
	if (isset($attrs["style"])) $style=$attrs["style"];
	$footnoteElement = $currentElement;
	$currentElement = new GMRFootnote($style);
}

function GMRFootnoteEHandler() {
	global $printData, $printDataStack, $gmreport, $currentElement, $footnoteElement;

	$printData = array_pop($printDataStack);
	$temp = trim($currentElement->getValue());
	if (strlen($temp)>3) $gmreport->addElement($currentElement);
	$currentElement = $footnoteElement;
}

function GMRFootnoteTextsSHandler($attrs) {
	global $printData, $printDataStack;
	global $gmreport, $currentElement;

	$temp = "footnotetexts";
	$gmreport->addElement($temp);
}

function brSHandler($attrs) {
	global $printData, $currentElement, $processGedcoms;
	if ($printData && ($processGedcoms==0)) $currentElement->addText("<br />");
}

function GMRHighlightedImageSHandler($attrs) {
	global $gedrec, $gmreport;

	$id = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}

	$left = 0;
	$top = 0;
	$width = 0;
	$height = 0;
	if (isset($attrs["left"])) $left = $attrs["left"];
	if (isset($attrs["top"])) $top = $attrs["top"];
	if (isset($attrs["width"])) $width = $attrs["width"];
	if (isset($attrs["height"])) $height = $attrs["height"];

	if (PrivacyFunctions::showFact("OBJE", $id)) {
		$person = Person::GetInstance($id);
		$media = FindHighlightedObject($person);
		//print_r($media);
		//print "<br /><br />";
		$mfile =& MediaItem::GetInstance($media["id"]);
		//print_r($mfile);
		//print "<br /><br />";
		if ($mfile->fileobj->f_is_image) {
			if ($width > 0 && $height == 0) {
//			if (($width>0) && ($mfile->fileobj->f_width > $mfile->fileobj->f_height)) {
				$perc = $width / $mfile->fileobj->f_width;
				$height = round($mfile->fileobj->f_height * $perc);
			}
			if ($height > 0 && $width == 0) {
//			if (($height>0) && ($mfile->fileobj->f_height > $mfile->fileobj->f_width)) {
				$perc = $height / $mfile->fileobj->f_height;
				$width = round($mfile->fileobj->f_width * $perc);
			}
			//print "l: ".$left." t: ".$top." w: ".$width." h: ".$height;
			$image = new GMRImage($mfile->fileobj->f_file, $left, $top, $width, $height, $mfile->fileobj->f_ext);
			$gmreport->addElement($image);
		}
	}
}

function GMRImageSHandler($attrs) {
	global $gedrec, $gmreport;

	$id = "";
	$gt = preg_match("/0 @(.+)@/", $gedrec, $gmatch);
	if ($gt > 0) {
		$id = $gmatch[1];
	}

	$left = 0;
	$top = 0;
	$width = 0;
	$height = 0;
	if (isset($attrs["left"])) $left = $attrs["left"];
	if (isset($attrs["top"])) $top = $attrs["top"];
	if (isset($attrs["width"])) $width = $attrs["width"];
	if (isset($attrs["height"])) $height = $attrs["height"];
	$file = "";
	if (isset($attrs["file"])) $file = $attrs["file"];
	if ($file=="@FILE") {
		$ct = preg_match("/\d OBJE @(.+)@/", $gedrec, $match);
		if ($ct > 0) {
			$mfile = MediaItem::GetInstance($match[1]);
			if ($mfile->fileobj->f_is_image) {
				if ($width > 0 && $height == 0) {
				$perc = $width / $mfile->fileobj->f_width;
				$height = round($mfile->fileobj->f_height * $perc);
				}
				if ($height > 0 && $width == 0) {
					$perc = $height / $mfile->fileobj->f_height;
					$width = round($mfile->fileobj->f_width * $perc);
				}
				//print "l: ".$left." t: ".$top." w: ".$width." h: ".$height;
				$image = new GMRImage($mfile->fileobj->f_file, $left, $top, $width, $height, $mfile->fileobj->f_ext);
				$gmreport->addElement($image);
			}
		}
	}
	else {
		$filename = $file;
		if (preg_match("/(jpg)|(jpeg)|(png)$/i", $filename)>0) {
			if (MediaFS::FileExists($filename)) {
				$size = ReportFunctions::findImageSize($filename);
				if (($width>0)&&($size[0]>$size[1])) {
					$perc = $width / $size[0];
					$height= round($size[1]*$perc);
				}
				if (($height>0)&&($size[1]>$size[0])) {
					$perc = $height / $size[1];
					$width= round($size[0]*$perc);
				}
				//print "2 width:$width height:$height ";
				$image = new GMRImage($filename, $left, $top, $width, $height);
				$gmreport->addElement($image);
			}
		}
	}
}

function GMRLineSHandler($attrs) {
	global $gmreport;

	$x1 = 0;
	$y1 = 0;
	$x2 = 0;
	$y2 = 0;
	if (isset($attrs["x1"])) $x1 = $attrs["x1"];
	if (isset($attrs["y1"])) $y1 = $attrs["y1"];
	if (isset($attrs["x2"])) $x2 = $attrs["x2"];
	if (isset($attrs["y2"])) $y2 = $attrs["y2"];

	$line = new GMRLine($x1, $y1, $x2, $y2);
	$gmreport->addElement($line);
}

function GMRListSHandler($attrs) {
	global $gmreport, $gedrec, $repeats, $repeatBytes, $list, $repeatsStack, $processRepeats, $parser, $vars, $sortby, $selevent;
	global $status;

	$processRepeats++;
	if ($processRepeats>1) return;

	$sortby = "NAME";
	if (isset($vars["selevent"])) $selevent = $vars["selevent"]["id"];
	if (isset($vars["sortby"])) $sortby = $vars["sortby"]["id"];
	if (preg_match("/\\$(\w+)/", $sortby, $vmatch)>0) {
		$sortby = $vars[$vmatch[1]]["id"];
		$sortby = trim($sortby);
	}
	$list = array();
	$listname = "individual";
	if (isset($attrs["list"])) $listname=$attrs["list"];

	$filters = array();
	$filters2 = array();
	if (isset($attrs["filter1"])) {
		$j=0;
		foreach($attrs as $key=>$value) {
			$ct = preg_match("/filter(\d)/", $key, $match);
			if ($ct>0) {
				$condition = $value;
				$ct = preg_match("/@(\w+)/", $condition, $match);
				if ($ct > 0) {
					$id = $match[1];
					$value="''";
					if ($id=="ID") {
						$ct = preg_match("/0 @(.+)@/", $gedrec, $match);
						if ($ct>0) $value = "'".$match[1]."'";
					}
					else if ($id=="fact") {
						$value = "'$fact'";
					}
					else if ($id=="desc") {
						$value = "'$desc'";
					}
					else {
						$ct = preg_match("/\d $id (.+)/", $gedrec, $match);
						if ($ct>0) $value = "'".preg_replace("/@/", "", trim($match[1]))."'";
					}
					$condition = preg_replace("/@$id/", $value, $condition);
				}
				//-- handle regular expressions
				$ct = preg_match("/([A-Z:]+)\s*([^\s]+)\s*(.+)/", $condition, $match);
				if ($ct>0) {
					$tag = trim($match[1]);
					$expr = trim($match[2]);
					$val = trim($match[3]);
					if (preg_match("/\\$(\w+)/", $val, $vmatch)>0) {
						$val = $vars[$vmatch[1]]["id"];
						$val = trim($val);
					}
					$searchstr = "";
					$tags = preg_split("/:/", $tag);
					$level = 1;
					foreach($tags as $indexval => $t) {
						if (!empty($searchstr)) $searchstr.="[^\n]*(\n[2-9][^\n]*)*\n";
						//-- search for both EMAIL and _EMAIL... silly double gedcom standard
						if ($t=="EMAIL" || $t=="_EMAIL") $t="_?EMAIL";
						$searchstr .= $level." ".$t;
						$level++;
					}
					switch ($expr) {
						case "CONTAINS":
							if ($t=="PLAC") $searchstr.="[^\n]*[, ]".$val;
							else $searchstr.="[^\n]*".$val;
							$filters[] = $searchstr;
							break;
						default:
							if (!empty($val)) $filters2[] = array("tag"=>$tag, "expr"=>$expr, "val"=>$val);
							break;
					}
				}
			}
			$j++;
		}
	}
	switch($listname) {
		case "family":
			if (count($filters)>0) $list = SearchFunctions::SearchFams($filters);
			else $list = ListFunctions::GetFamList("no");
			if ($sortby == "NAME") uasort($list, "ItemSort");
			else if ($sortby == "ID") uasort($list, "IDSort");
			else if ($sortby == "DATE") uasort($list, "CompareDate");
			//print "sortby: ".$sortby." selevent: ".$selevent;
			break;
		case "actions":
			$select = "";
			if (isset($vars["status"])) {
				// Funny behaviour, as the values are actually action_0 and action_1
				if ($vars["status"]["id"] == "action0") $select = "0";
				else if ($vars["status"]["id"] == "action1") $select = "1";
			}
			if (isset($vars["repo"]) && !empty($vars["repo"]["id"])) {
				$repo_obj =& Repository::GetInstance($vars["repo"]["id"]);
				$alist = ActionController::GetSelectActionList($repo_obj->xref, "", $repo_obj->gedcomid, $select, false, strtolower($sortby));
				$alist = $alist[0];
			}
			else $alist = ActionController::GetActionList($select, true, strtolower($sortby));
			$list = array();
			$oldrepo = "";
			foreach ($alist as $key => $action) {
				// Skip action with no repo
				if ($action->repo != "") {
					if (!is_object($oldrepo) || $action->repo_obj->xref != $oldrepo->xref) {
						if (is_object($oldrepo)) {
							$list[$oldrepo->xref]["gedcom"] = $gedline;
							$list[$oldrepo->xref]["name"] = $oldrepo->descriptor;
						}
						$gedline = $action->repo_obj->gedrec;
						$gedline .= "\r\n1 _TODO\r\n";
						$gedline .= "2 INDI @".$action->pid."@\r\n";
						$gedline .= "2 _STAT ".constant("GM_LANG_action".$action->status)."\r\n";
						$noteline = MakeCont("2 NOTE", $action->text);
						$gedline .= $noteline;
						$oldrepo = $action->repo_obj;
					}
					else {
						$gedline .= "1 _TODO\r\n";
						$gedline .= "2 INDI @".$action->pid."@\r\n";
						$gedline .= "2 _STAT ".constant("GM_LANG_action".$action->status)."\r\n";
						$noteline = MakeCont("2 NOTE", $action->text);
						$gedline .= $noteline;
					}
				}
			}
			if (is_object($oldrepo)) {
				$list[$oldrepo->xref]["gedcom"] = $gedline;
				$list[$oldrepo->xref]["name"] = $oldrepo->descriptor;
			}
			break;
		/*
		case "source":
			$list = get_source_list();
			break;
		case "other":
			$list = get_other_list();
			break; */
//LERMAN
		case "pending":
			$list = array();
			if (ChangeFunctions::GetChangeData(true, "", true, "gedlinesCHAN")) {
				$pend_gedcoms = ChangeFunctions::GetChangeData(false, "", true, "gedlinesCHAN");
				foreach($pend_gedcoms as $gedcomid => $pend_indis) {
					if ($gedcomid == GedcomConfig::$GEDCOMID) {
						foreach ($pend_indis as $key => $changed) {
							$obj = ConstructObject($key);
							$list[$key] = $obj;
						}
					}
				}
			}
			break;
		default:
			//print_r($filters);
			if (count($filters)>0) $list = SearchFunctions::SearchIndis($filters);
//LERMAN - added "no" parameter. Fixes one list, but not sure if have other ramifications
			else $list = ListFunctions::GetIndiList("no");
			if ($sortby == "NAME") uasort($list, "ItemSort");
			else if ($sortby == "ID") uasort($list, "IDSort");
			break;
	}
			//print_r($list);
	
	//-- apply other filters to the list that could not be added to the search string
	if (count($filters2)>0) {
		$mylist = array();
		foreach($list as $key => $object) {
			$keep = true;
			foreach($filters2 as $indexval => $filter) {
				if ($keep) {
					$tag = $filter["tag"];
					$expr = $filter["expr"];
					$val = $filter["val"];
					if ($val=="''") $val = "";
					$tags = preg_split("/:/", $tag);
					$level = 1;
					$subrec = $object->gedrec;
					foreach($tags as $indexval => $t) {
						$oldsub = $subrec;
						$subrec = GetSubRecord($level, $level." ".$t, $subrec);
						if ($t == 'EMAIL' && empty($subrec)) {
							$t = "_EMAIL";
							$subrec = GetSubRecord($level, $level." ".$t, $oldsub);
						}
						$level++;
					}
					$level--;
					switch ($expr) {
						case "GTE":
							$ct = preg_match("/$level $t(.*)/", $subrec, $match);
							if ($ct>0) {
								$v = trim($match[1]);
								if ($t=="DATE") {
									$date1 = ParseDate($v);
									$date2 = ParseDate($val);
									if ($date1[0]["year"] > $date2[0]["year"]) $keep = true;
									else if ($date1[0]["year"] == $date2[0]["year"]) {
										if ($date1[0]["mon"] > $date2[0]["mon"] or empty($date1[0]["mon"]) or empty($date2[0]["mon"])) $keep = true;
										else if ($date1[0]["mon"] == $date2[0]["mon"]) {
											if ($date1[0]["day"] >= $date2[0]["day"] or empty($date1[0]["day"]) or empty($date2[0]["day"])) $keep = true;
											else $keep = false;
										} else $keep = false;
									} else $keep = false;
									//print "[$key ".implode(" ", $date1[0])." ".implode(" ", $date2[0])." keep=$keep] ";
								}
								else if ($val >= $v) $keep=true;
							}
							else $keep=false;
							break;
						case "LTE":
							$ct = preg_match("/$level $t(.*)/", $subrec, $match);
							if ($ct>0) {
								$v = trim($match[1]);
								if ($t=="DATE") {
									$date1 = ParseDate($v);
									$date2 = ParseDate($val);
									if ($date1[0]["year"] < $date2[0]["year"]) $keep = true;
									else if ($date1[0]["year"] == $date2[0]["year"]) {
										if ($date1[0]["mon"] < $date2[0]["mon"] or empty($date1[0]["mon"]) or empty($date2[0]["mon"])) $keep = true;
										else if ($date1[0]["mon"] == $date2[0]["mon"]) {
											if ($date1[0]["day"] <= $date2[0]["day"] or empty($date1[0]["day"]) or empty($date2[0]["day"])) $keep = true;
											else $keep = false;
										} else $keep = false;
									} else $keep = false;
									//print "[$key ".implode(" ", $date1[0])." ".implode(" ", $date2[0])." keep=$keep] ";
								}
								else if ($val >= $v) $keep=true;
							}
							else $keep=false;
							break;
						case "SUBCONTAINS":
							$ct = preg_match("/$val\W/i", $subrec);
							if ($ct>0) $keep = true;
							else $keep = false;
							break;
						default:
							$v = GetGedcomValue($t, $level, $subrec);
							//-- check for EMAIL and _EMAIL (silly double gedcom standard :P)
							if ($t == "EMAIL" && empty($v)) {
								$t = "_EMAIL";
								$v = GetGedcomValue($t, $level, $subrec);
							}
							//print "[$key $t $v == $val $subrec]<br />";
							if ($v == $val) $keep = true;
							else $keep = false;
							//print $keep;
							break;
					}
				}
			}
			if ($keep) $mylist[$key] = $object;
		}
		$list = $mylist;
	}
//LERMAN - fix case (not case sensitive, but that confused me)
	if ($sortby == "NAME" || $sortby == "ID") {
		// Do nothing, handle this above!
	}
//LERMAN
	else if ($sortby == "CHAN") uasort($list, "CompareDateDescending");
	else if ($sortby == "BIRT") uasort($list, "IndiBirthSort");
	else if ($sortby == "DEAT") uasort($list, "IndiDeathSort");
//	else uasort($list, "CompareDate");
	//print count($list);
	array_push($repeatsStack, array($repeats, $repeatBytes));
	$repeatBytes = xml_get_current_line_number($parser)+1;
}

function GMRListEHandler() {
	global $list, $repeats, $repeatsStack, $repeatBytes, $parser, $parserStack, $report, $gmreport, $gedrec, $processRepeats, $list_total, $list_private;
	$processRepeats--;
	if ($processRepeats>0) return;

	$line = xml_get_current_line_number($parser)-1;
	$lineoffset = 0;
	for($i=0; $i<count($repeatsStack); $i++) {
		$p = $repeatsStack[$i];
		$l = $p[1];
		$lineoffset += $l;
	}
	//-- read the xml from the file
	$lines = file($report);
	$reportxml = "<tempdoc>\n";
	while(strstr($lines[$lineoffset+$repeatBytes], "<GMRList")===false && $lineoffset+$repeatBytes>0) $lineoffset--;
	$lineoffset++;
	$line1 = $repeatBytes;
	$ct = 1;
	while(($ct>0)&&($line1<$line+2)) {
		if (strstr($lines[$lineoffset+$line1], "<GMRList")!==false) $ct++;
		if (strstr($lines[$lineoffset+$line1], "</GMRList")!==false) $ct--;
		$line1++;
	}
	$line = $line1-1;
	for($i=$repeatBytes+$lineoffset; $i<$line+$lineoffset; $i++) $reportxml .= $lines[$i];
	$reportxml .= "</tempdoc>\n";
	//print htmlentities($reportxml)."\n";
	array_push($parserStack, $parser);

	$oldgedrec = $gedrec;
	$list_total = count($list);
	$list_private = 0;
	foreach($list as $key => $object) {
		if ((is_object($object) && $object->disp) || is_array($object)) {
			if (is_object($object)) $gedrec = $object->gedrec;
			else if (isset($object["gedcom"])) $gedrec = $object["gedcom"];
			//LERMAN-- added individuals in pending list does not have Gedcom record yet. Could check $lines[$repeatBytes+$lineoffset-1] to see if list="pending"
			if (empty($gedrec)) { $gedrec = $value; }
			//-- start the sax parser
			$repeat_parser = xml_parser_create();
			$parser = $repeat_parser;
			//-- make sure everything is case sensitive
			xml_parser_set_option($repeat_parser, XML_OPTION_CASE_FOLDING, false);
			//-- set the main element handler functions
			xml_set_element_handler($repeat_parser, "startElement", "endElement");
			//-- set the character data handler
			xml_set_character_data_handler($repeat_parser, "characterData");

			if (!xml_parse($repeat_parser, $reportxml, true)) {
				printf($reportxml."\nGMRRepeatTagEHandler XML error: %s at line %d", xml_error_string(xml_get_error_code($repeat_parser)), xml_get_current_line_number($repeat_parser));
				print_r($repeatsStack);
				exit;
			}
			xml_parser_free($repeat_parser);
		}
		else $list_private++;
	}
	$parser = array_pop($parserStack);

	$gedrec = $oldgedrec;
	$temp = array_pop($repeatsStack);
	$repeats = $temp[0];
	$repeatBytes = $temp[1];
}

function GMRListTotalSHandler($attrs) {
	global $list_total, $list_private, $currentElement;

	if (empty($list_total)) $list_total = 0;

	$currentElement->addText(($list_total - $list_private)." / ".$list_total);
}

function GMRRelativesSHandler($attrs) {
	global $gmreport, $gedrec, $repeats, $repeatBytes, $list, $repeatsStack, $processRepeats, $parser, $vars, $sortby;

	$debug = false;
	
	$processRepeats++;
	if ($processRepeats>1) return;

	$sortby = "NAME";
	if (isset($attrs["sortby"])) $sortby = $attrs["sortby"];
	if (preg_match("/\\$(\w+)/", $sortby, $vmatch)>0) {
		$sortby = $vars[$vmatch[1]]["id"];
		$sortby = trim($sortby);
	}

	$maxgen = -1;
	if (isset($attrs["maxgen"])) $maxgen = $attrs["maxgen"];
	if ($maxgen=="*") $maxgen = -1;

	$group = "child-family";
	if (isset($attrs["group"])) $group = $attrs["group"];
	if (preg_match("/\\$(\w+)/", $group, $vmatch)>0) {
		$group = $vars[$vmatch[1]]["id"];
		$group = trim($group);
	}

	$id = "";
	if (isset($attrs["id"])) $id = $attrs["id"];
	if (preg_match("/\\$(\w+)/", $id, $vmatch)>0) {
		$id = $vars[$vmatch[1]]["id"];
		$id = trim($id);
	}
	
	$showempty = false;
	if (isset($attrs["showempty"])) $showempty = $attrs["showempty"];
	if (preg_match("/\\$(\w+)/", $showempty, $vmatch)>0) {
		$showempty = $vars[$vmatch[1]]["id"];
		$showempty = trim($showempty);
	}
	

	$list = array();
	$person = Person::GetInstance($id);
	if (!$person->isempty && $person->disp_name) {
		$list[$id] = $person;
		if ($debug) print "we have group: ".$group." id: ".$id."<br />";
		switch ($group) {
			case "child-family":
				$famid = $person->primaryfamily;
				if (!empty($famid)) {
					$fam = Family::GetInstance($famid);
					if ($fam->disp) {
						if ($debug) print "we can show ".$famid."<br />";
						if ($fam->husb_id != "") $list[$fam->husb_id] = $fam->husb;
						if ($fam->wife_id != "") $list[$fam->wife_id] = $fam->wife;
						foreach($fam->children as $id => $child) {
							$list[$child->xref] = $child;
						}
					}
				}
				break;
			case "spouse-family":
				foreach($person->spousefamilies as $key => $fam) {
					if ($fam->disp) {
						if ($fam->husb_id != "") $list[$fam->husb_id] = $fam->husb;
						if ($fam->wife_id != "") $list[$fam->wife_id] = $fam->wife;
						foreach($fam->children as $id => $child) {
							$list[$child->xref] = $child;
						}
					}
				}
				break;
			case "direct-ancestors":
				ReportFunctions::AddAncestors($id,false,$maxgen, $showempty);
				break;
			case "ancestors":
				ReportFunctions::AddAncestors($id,true,$maxgen,$showempty); 
				break;
			case "descendants":
				$list[$id]->generation = 1;
				ReportFunctions::AddDescendancy($id,false,$maxgen);
//				print_r($list);
				break;
			case "all":
				ReportFunctions::AddAncestors($id,true,$maxgen,$showempty);
				ReportFunctions::AddDescendancy($id,true,$maxgen);
				break;
		}
	}

	if ($sortby!="none") {
		if ($sortby=="NAME") uasort($list, "itemsort");
		else if ($sortby=="ID") uasort($list, "idsort");
		else if ($sortby=="generation") {
			$newarray = array();
			reset($list);
			$genCounter = 1;
			while (count($newarray) < count($list)) {
		        foreach ($list as $key => $object) {
			    	$generation = $object->generation;
			        if ($generation == $genCounter) {
						$newarray[$key] = $object;
					}
				}
				$genCounter++;
			}
			$list = $newarray;
		}
		else uasort($list, "CompareDate");
	}
//	print count($list);
	array_push($repeatsStack, array($repeats, $repeatBytes));
	$repeatBytes = xml_get_current_line_number($parser)+1;
}

function GMRRelativesEHandler() {
	global $list, $repeats, $repeatsStack, $repeatBytes, $parser, $parserStack, $report, $gmreport, $gedrec, $processRepeats, $list_total, $list_private, $generation;
	$processRepeats--;
	if ($processRepeats>0) return;

	$line = xml_get_current_line_number($parser)-1;
	$lineoffset = 0;
	for($i=0; $i<count($repeatsStack); $i++) {
		$p = $repeatsStack[$i];
		$l = $p[1];
		$lineoffset += $l;
	}
	//-- read the xml from the file
	$lines = file($report);
	$reportxml = "<tempdoc>\n";
	while(strstr($lines[$lineoffset+$repeatBytes], "<GMRRelatives")===false && $lineoffset+$repeatBytes>0) $lineoffset--;
	$lineoffset++;
	$line1 = $repeatBytes;
	$ct = 1;
	while(($ct>0)&&($line1<$line+2)) {
		if (strstr($lines[$lineoffset+$line1], "<GMRRelatives")!==false) $ct++;
		if (strstr($lines[$lineoffset+$line1], "</GMRRelatives")!==false) $ct--;
		$line1++;
	}
	$line = $line1-1;
	for($i=$repeatBytes+$lineoffset; $i<$line+$lineoffset; $i++) $reportxml .= $lines[$i];
	$reportxml .= "</tempdoc>\n";
//	print htmlentities($reportxml)."\n";
	array_push($parserStack, $parser);

	$oldgedrec = $gedrec;
	$list_total = count($list);
	$list_private = 0;
	foreach($list as $key=>$object) {
		if (!is_null($object->generation)) $generation = $object->generation;
		$gedrec = $object->gedrec;
//		print "5 rec added: ".$gedrec."<br />";
			//-- start the sax parser
			$repeat_parser = xml_parser_create();
			$parser = $repeat_parser;
			//-- make sure everything is case sensitive
			xml_parser_set_option($repeat_parser, XML_OPTION_CASE_FOLDING, false);
			//-- set the main element handler functions
			xml_set_element_handler($repeat_parser, "startElement", "endElement");
			//-- set the character data handler
			xml_set_character_data_handler($repeat_parser, "characterData");

			if (!xml_parse($repeat_parser, $reportxml, true)) {
				printf($reportxml."\nGMRRelativesEHandler XML error: %s at line %d", xml_error_string(xml_get_error_code($repeat_parser)), xml_get_current_line_number($repeat_parser));
				print_r($repeatsStack);
				exit;
			}
			xml_parser_free($repeat_parser);
//KN		}
//KN		else $list_private++;
	}
	$parser = array_pop($parserStack);

	$gedrec = $oldgedrec;
	$temp = array_pop($repeatsStack);
	$repeats = $temp[0];
	$repeatBytes = $temp[1];
}

function GMRGenerationSHandler($attrs) {
	global $list_total, $list_private, $generation, $currentElement;

	if (empty($generation)) $generation = 1;

	$currentElement->addText($generation);
}

function GMRNewPageSHandler($attrs) {
	global $gmreport;
	$temp = "addpage";
	$gmreport->addElement($temp);
}
?>
