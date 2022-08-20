<?php
/**
 * Popup window for viewing images
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
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Media
 * @version $Id: imageview.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($filename)) $filename = "";
// Check if the extension is legal
$filename = urldecode($filename);
if (!MediaFS::IsValidMedia($filename)) {
	WriteToLog("ImageView-&gt; Illegal display attempt. File: ".$filename, "W", "S");
	exit;
}

PrintSimpleHeader(GM_LANG_imageview);
?>
<script language="JavaScript" type="text/javascript">
<!--
	var zoom = 100;
	function zoomin() {
		i = document.getElementById('theimage');
		zoom=zoom+10;
		i.style.width=Math.round((zoom/100)*imgwidth)+"px";
		i.style.height=null;
		document.getElementById('zoomval').value=Math.round(zoom);
	}
	function zoomout() {
		i = document.getElementById('theimage');
		zoom=zoom-10;
		if (zoom<10) zoom=10;
		i.style.width=Math.round((zoom/100)*imgwidth)+"px";
		i.style.height=null;
		document.getElementById('zoomval').value=Math.round(zoom);
	}
	function setzoom(perc) {
		i = document.getElementById('theimage');
		zoom=parseInt(perc);
		if (zoom<10) zoom=10;
		i.style.width=Math.round((zoom/100)*imgwidth)+"px";
		i.style.height=null;
		document.getElementById('zoomval').value=Math.round(zoom);
	}
	function resetimage() {
		setzoom(initzoom);
		document.getElementById('zoomval').value=zoom;
		i = document.getElementById('theimage');
		i.style.left='0px';
		i.style.top='0px';
	}
	var oldMx = 0;
	var oldMy = 0;
	var movei = "";
	function panimage() {
		if (movei=="") {
			oldMx = msX;
			oldMy = msY;
		}
		i = document.getElementById('theimage');
		//alert(i.style.top);
		movei = i;
		return false;
	}
	function releaseimage() {
		movei = "";
		return true;
	}
	// Main function to retrieve mouse x-y pos.s
	function getMouseXY(e) {
	  if (IE) { // grab the x-y pos.s if browser is IE
	    msX = event.clientX + document.documentElement.scrollLeft;
	    msY = event.clientY + document.documentElement.scrollTop;
	  } else {  // grab the x-y pos.s if browser is NS
	    msX = e.pageX;
	    msY = e.pageY;
	  }
	  // catch possible negative values in NS4
	  if (msX < 0){msX = 0;}
	  if (msY < 0){msY = 0;}
	  if (movei!="") {
		ileft = parseInt(movei.style.left);
		itop = parseInt(movei.style.top);
		ileft = ileft - (oldMx-msX);
		itop = itop - (oldMy-msY);
		movei.style.left = ileft+"px";
		movei.style.top = itop+"px";
		oldMx = msX;
		oldMy = msY;
		return false;
	  }
	}
	
	 function resizeWindow() { 
		if (document.images) { 
			if (document.images.length == 3) { 
				height=(document.images[0].height * 100 / initzoom)+80; 
				width=(document.images[0].width * 100 / initzoom)+20; 
				if(width > screen.width-100) width = screen.width-100; 
				if(height > screen.height-110) height = screen.height-110; 
				if (document.layers) window.resizeTo(width+20,height+20) 
				else if (document.all) window.resizeTo(width+30,height+50) 
				else if (document.getElementById) window.resizeTo(width+40,height+20) 
			} 
			else setTimeout('resizeWindow()',1000); 
		} 
		resizeViewport(); 
		//resetimage(); 
	} 

	function resizeViewport() {
		if (IE) {
			pagewidth = document.documentElement.offsetWidth;
			pageheight = document.documentElement.offsetHeight-140;
		}
		else {
			pagewidth = window.outerWidth-25;
			pageheight = window.outerHeight-25-140;
		}
		viewport = document.getElementById('imagecropper');
		viewport.style.width=(pagewidth-35)+"px";
		viewport.style.height=(pageheight-100)+"px"; // The page must hold the viewport AND the page footer
		return;
		i = document.getElementById('theimage');
		i.style.left="0px";
		i.style.top="0px";
		if ((pagewidth-40)-imgwidth < ((pageheight-65)-imgheight)) {
			i.style.width=(pagewidth-40)+"px";
			i.style.height=null;
			zoom = ((pagewidth-40) / imgwidth)*100;
		}
		else {
			i.style.height=(pageheight-65)+"px";
			i.style.width=null;
			zoom = ((pageheight-65) / imgheight)*100;
		}
		document.getElementById('zoomval').value=Math.round(zoom);
	}

	var IE = document.all?true:false;
	if (!IE) document.captureEvents(Event.MOUSEMOVE | Event.MOUSEUP)
	document.onmousemove = getMouseXY;
	document.onmouseup = releaseimage;

//-->
</script>
<?php
//$filename = FilenameDecode($filename);
print "<form name=\"zoomform\" onsubmit=\"setzoom(document.getElementById('zoomval').value); return false;\" action=\"imageview.php\">";
if (strstr($filename, "://")) $filename = preg_replace("/ /", "%20", $filename);
if (!SystemConfig::$MEDIA_IN_DB && (empty($filename) || !@fclose(@fopen($filename,"r")))) {
	print "<span class=\"Error\">".GM_LANG_file_not_found."&nbsp;".$filename."</span>";
	print "<br /><br /><div class=\"CloseWindow\"><a href=\"javascript:// ".GM_LANG_close_window."\" onclick=\"self.close();\">".GM_LANG_close_window."</a></div>\n";
}
else {
	print "<a href=\"#\" onclick=\"zoomin(); return false;\"><span class=\"ZoomButtons\">+</span></a> <a href=\"#\" onclick=\"zoomout();\"><span class=\"ZoomButtons\">-</span></a> ";
	print "<input type=\"text\" size=\"2\" name=\"zoomval\" id=\"zoomval\" value=\"100\" />%\n";
	print "<input type=\"button\" value=\"".GM_LANG_reset."\" onclick=\"resetimage(); return false;\" />\n";
	
	if (!strstr($filename, "://")) {
		if (!SystemConfig::$MEDIA_IN_DB) $details = MediaFS::GetFileDetails($filename, false);
		else {
			// we must strip showblob?file= from the filename, to get the real file
			$fn = preg_replace("/showblob.php\?file=/", "", $filename);
			$fn = preg_replace("/&.*/", "", $fn);
			$details = MediaFS::GetFileDetails($fn, true);
		}
		if (!empty($details["width"]) && !empty($details["height"])) {
			$imgwidth = $details["width"]+2;
			$imgheight = $details["height"]+2;
		}
		else {
			$imgwidth = 50;
			$imgheight = 50;
		}
	}
	else {
		$details = @getimagesize($filename);
		if (!empty($details[0]) && !empty($details[1])) {
			$imgwidth = $details[0]+2;
			$imgheight = $details[1]+2;
		}
		else {
			$imgwidth = 50;
			$imgheight = 50;
		}
	}
	print '<br /><div id="imagecropper" style="width: '.$imgwidth.'px; height: '.$imgheight.'px; ">';
	print "\n<img id=\"theimage\" src=\"$filename\" onmousedown=\"panimage(); return false;\" alt=\"\" />\n";
	print '</div>';
}
//	print $imgwidth." ".$imgheight;
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
	var height = document.documentElement.clientHeight;
	var imgheight = <?php print $imgheight; ?>;
	var imgwidth = <?php print $imgwidth; ?>;
	if (height < imgheight) {
		zoom = zoom * height / imgheight;
		setzoom(zoom);
	}
	var landscape = false;
	var initzoom = zoom;
	if (imgwidth > imgheight) landscape = true;
	window.onload = resizeWindow; // Use without () as it throws errors in IE
	window.onresize = resizeViewport;  // Use without () as it throws errors in IE
	//-->
	</script><?php
print "</form>\n";
print "<div style=\"position: relative; bottom: 0; \">\n";
print "</div>\n";
PrintSimpleFooter();
?>