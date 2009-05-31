/**
 * Common javascript functions
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @subpackage Display
 * @version $Id: genmod.js,v 1.23 2006/03/14 21:55:32 roland-d Exp $
 */
if (!document.getElementById)	// Check if browser supports the getElementByID function
{
	curloc = window.location.toString();
	if (curloc.indexOf('nosupport.php')==-1) window.location.href = "nosupport.php";
}
// Show the box when the alert is triggered
var windowout, browserName;
function showbox(startpos, layer, boxpid){
	var showbox, page, newY, newX, showbox_width;
	// Get all the elements
	showbox = document.getElementById('I'+layer+'links');
	if (boxpid != "relatives") {
		box = document.getElementById(boxpid+'.1.0');
		if (box == null) boxpid = 'relatives';
		else box_width = box.offsetWidth;
	}
	
	// Get all the positions
	newY = findPosY(startpos);
	newX = findPosX(startpos);
	pagewidth = document.body.clientWidth;
	showbox_width = showbox.offsetWidth;
	image_width = startpos.offsetWidth;
	image_top = startpos.offsetTop;
	
	// Set the new position of the popup div
	// Pedigree popups
	if (boxpid != "relatives") {
		showbox.style.top = image_top + 'px';
		if ((showbox_width + newX) > pagewidth) {
			showbox.style.left = (box_width-(showbox_width+image_width)) + 'px';
		}
		else showbox.style.left = box_width + 'px';
	}
	// Other popups
	else {
		showbox.style.top = newY + 'px';
		if ((showbox_width + newX) > pagewidth) {
			
			newX = newX - showbox_width;
			showbox.style.left = newX + 'px';
		}
		else {
			showbox.style.left = newX + image_width + 'px';
		}
	}
	
	// NOTE: Show the box
	showbox.style.visibility = 'visible';
	return true;
}
// Hide the box when the mouse moves out from the layer
function moveout(layer) {
	browserName=navigator.appName;
 	if (browserName == "Microsoft Internet Explorer") {
	 		clearTimeout(windowout);
 	}
 	else {
		hidebox(layer); 
	}
}
// Hide the box when triggered
function hidebox(layer){
	var hidebox;
	hidebox = document.getElementById('I'+layer+'links');
	hidebox.style.visibility = 'hidden';
	return false;
}
// IE fix for flickering the box when the mouse is going from link to link
function keepbox(layer){
	var keepbox;
	keepbox = document.getElementById('I'+layer+'links');
	keepbox.style.visibility = 'visible';
	if (browserName == "Microsoft Internet Explorer") {
		windowout=setTimeout("moveout('"+layer+"')", 1000);
 	}
	return false;
}
// Find the horizontal position
function findPosX(obj) {
	var curleft = 0;
	if (obj.offsetParent) {
		while (obj.offsetParent)	{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x) curleft += obj.x;
	return curleft;
}
// Find the vertical position
function findPosY(obj) {
	var curtop = 0;
	if (obj.offsetParent) {
		while (obj.offsetParent)	{
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y) curtop += obj.y;
	return curtop;
}
var helpWin;
function helpPopup(which) {
	if (which==null) which = "help_contents_help";
	if ((!helpWin)||(helpWin.closed)) helpWin = window.open('help_text.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
	else helpWin.location = 'help_text.php?help='+which;
	return false;
}
function getHelp(which) {
	if ((helpWin)&&(!helpWin.closed)) helpWin.location='help_text.php?help='+which;
}
function closeHelp() {
	if (helpWin) helpWin.close();
}

function openImage(filename, width, height) {
		height=height+50;
		screenW = screen.width;
	 	screenH = screen.height;
	 	if (width>screenW-100) width=screenW-100;
	 	if (height>screenH-110) height=screenH-120;
		if ((filename.search(/\.je?pg$/gi)!=-1)||(filename.search(/\.gif$/gi)!=-1)||(filename.search(/\.png$/gi)!=-1)||(filename.search(/\.bmp$/gi)!=-1)) window.open('imageview.php?filename='+filename,'','top=50,left=50,height='+height+',width='+width+',scrollbars=1,resizable=1');
		else window.open(unescape(filename),'','top=50,left=50,height='+height+',width='+width+',scrollbars=1,resizable=1');
		return false;
	}

// variables to hold mouse x-y pos.s
	var msX = 0;
	var msY = 0;

//  the following javascript functions are for the positioning and hide/show of
//  DIV layers used in the display of the pedigree chart.
function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_showHideLayers() { //v6.0
  var i,p,v,obj,args=MM_showHideLayers.arguments;
  for (i=0; i<(args.length-3); i+=4) {
	  if ((obj=MM_findObj(args[i]))!=null) {
    	if (obj.style) {
	      div=obj;
	      obj=obj.style;
	    }
	    v=args[i+2];
	    if (v=='toggle') {
		    if (obj.visibility.indexOf('hid')!=-1) v='show';
		    else v='hide';
	    }
	    v=(v=='show')?'visible':(v=='hide')?'hidden':v;
    	obj.visibility=v;
    	if (args[i+1]=='followmouse') {
	    	pobj = MM_findObj(args[i+3]);
	    	if (pobj!=null) {
		    	if (pobj.style.top!="auto") {
			    	obj.top=5+msY-parseInt(pobj.style.top)+'px';
			    	if (textDirection=="ltr") obj.left=5+msX-parseInt(pobj.style.left)+'px';
			    	if (textDirection=="rtl") obj.right=5+msX-parseInt(pobj.style.right)+'px';
		    	}
		    	else {
			    	obj.top="auto";
			    	//obj.left="80%";
			    	pagewidth = document.documentElement.offsetWidth+document.documentElement.scrollLeft;
			    	if (textDirection=="rtl") pagewidth -= document.documentElement.scrollLeft;
			    	if (msX > pagewidth-160) msX = msX-150-pobj.offsetLeft;
			    	contentdiv = document.getElementById("content");
			    	msX = msX - contentdiv.offsetLeft;
			    	if (textDirection=="ltr") obj.left=(5+msX)+'px';
			    	obj.zIndex=1000;
		    	}
	    	}
	    	else {
	    		//obj.top="auto";
	    		if (SCRIPT_NAME.indexOf("fanchart")>0) {
		    		obj.top=(msY-20)+'px';
			    	obj.left=(msX-20)+'px';
	    		}
	    		else if (SCRIPT_NAME.indexOf("index.php")==-1) {
		    		Xadjust = document.getElementById('content').offsetLeft;
		    		obj.left=(5+(msX-Xadjust))+'px';
		    		obj.top="auto";
	    		}
	    		else {
		    		Xadjust = document.getElementById('content').offsetLeft;
		    		obj.top=(msY-50)+'px';
			    	obj.left=(10+(msX-Xadjust))+'px';
	    		}
	    		obj.zIndex=1000;
    		}
    	}
    }
  }
}

var show = false;
	function togglechildrenbox() {
		if (show) {
			MM_showHideLayers('childbox', ' ', 'hide',' ');
			show=false;
		}
		else {
			MM_showHideLayers('childbox', ' ', 'show', ' ');
			show=true;
		}
		return false;
	}

	function togglefavoritesbox() {
		favsbox = document.getElementById("favs_popup");
		if (favsbox) {
			if (favsbox.style.visibility=="visible") {
				MM_showHideLayers('favs_popup', ' ', 'hide',' ');
			}
			else {
				MM_showHideLayers('favs_popup', ' ', 'show', ' ');
			}
		}
		return false;
	}

	function expand_layer(sid,show) {
		var sbox = document.getElementById(sid);
		var sbox_img = document.getElementById(sid+"_img");
		var sbox_style = sbox.style;
		if (show===true) {
			sbox_style.display='block';
			if (sbox_img) sbox_img.src = plusminus[1].src;
		}
		else if (show===false) {
			sbox_style.display='none';
			if (sbox_img) sbox_img.src = plusminus[0].src;
		}
		else {
			if ((sbox_style.display=='none')||(sbox_style.display=='')) {
				sbox_style.display='block';
				if (sbox_img) sbox_img.src = plusminus[1].src;
			}
			else {
				sbox_style.display='none';
				if (sbox_img) sbox_img.src = plusminus[0].src;
			}
		}
		if (window.resize_content_div) resize_content_div(lasttab+1);
		return false;
	}

	//-- function used for mouse overs of arrows
	//- arrow is the id of the arrow to swap
	//- index is the index into the arrows array
	//- set index=0 for left pointing arrows
	//- set index=1 for right pointing arrows
	//- set index=2 for up pointing arrows
	//- set index=3 for down pointing arrows
	function swap_image(arrow, index) {
		arrowimg = document.getElementById(arrow);
		tmp = arrowimg.src;
		arrowimg.src = arrows[index].src;
		arrows[index].src = tmp;
	}

function quickEdit(pid, action, change_type) {
	window.open('edit_quickupdate.php?pid='+pid+'&change_type='+change_type+"&"+sessionname+"="+sessionid+"&action="+action, '', 'top=50,left=50,width=750,height=600,resizable=1,scrollbars=1');
	return false;
}

function reply(username, subject) {
	window.open('message.php?to='+username+'&subject='+subject+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=650,height=500,resizable=1,scrollbars=1');
	return false;
}

function delete_message(id) {
	window.open('message.php?action=delete&id='+id+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=650,height=500,resizable=1,scrollbars=1');
	return false;
}

function valid_date(datefield) {
	date = new Date(datefield.value);
	months = new Array("JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC");
	if (date && date.toString()!="NaN" && date.getDate().toString()!="NaN") {
		day = date.getDate();
		if (day<10) day = "0"+day;
		datefield.value = day+" "+months[date.getMonth()]+" "+date.getFullYear();
	}
}

var oldheight = 0;
var oldwidth = 0;
var oldz = 0;
var oldleft = 0;
var big = 0;
var oldboxid = "";
var oldimgw = 0;
var oldimgh = 0;
var oldimgw1 = 0;
var oldimgh1 = 0;
var diff = 0;
var oldfont = 0;
var oldname = 0;
var oldthumbdisp = 0;
var repositioned = 0;
var oldiconsdislpay = 0;
function expandbox(boxid, bstyle) {
	if (big==1) {
		restorebox(oldboxid, bstyle);
		if (boxid==oldboxid) return true;
	}
	url = window.location.toString();
	divbox = document.getElementById("out-"+boxid);
	inbox = document.getElementById("inout-"+boxid);
	inbox2 = document.getElementById("inout2-"+boxid);
	parentbox = document.getElementById("box"+boxid);
	if (!parentbox) {
		parentbox=divbox;
	//	if (bstyle!=2) divbox.style.position="absolute";
	}
	sex = document.getElementById("box-"+boxid+"-sex");
	thumb1 = document.getElementById("box-"+boxid+"-thumb");
	famlinks = document.getElementById("I"+boxid+"links");
	icons = document.getElementById("icons-"+boxid);
	iconz = document.getElementById("iconz-"+boxid);	// This is the Zoom icon
	
	if (divbox) {
		if (icons) {
		oldiconsdislpay = icons.style.display;
		icons.style.display = "block";
		}
		if (iconz) {
			if (iconz.src==zoominout[0].src) iconz.src = zoominout[1].src;
			else iconz.src = zoominout[0].src;
		}
		oldboxid=boxid;
		big = 1;
		oldheight=divbox.style.height;
		oldwidth=divbox.style.width;
		oldz = parentbox.style.zIndex;
		if (url.indexOf("descendancy.php")==-1) parentbox.style.zIndex='100';
		if (bstyle!=2) {
			divbox.style.width='350px';
			diff = 350-parseInt(oldwidth);
			if (famlinks) {
				famleft = parseInt(famlinks.style.left);
				famlinks.style.left = (famleft+diff)+"px";
			}
			//parentbox.style.width = parseInt(parentbox.style.width)+diff;
		}
		divleft = parseInt(parentbox.style.left);
		if (textDirection=="rtl") divleft = parseInt(parentbox.style.right);
		oldleft=divleft;
		divleft = divleft - diff;
		repositioned = 0;
		if (divleft<0) {
			repositioned = 1;
			divleft=0;
		}
		if (url.indexOf("pedigree.php")!=-1) {
			if (textDirection=="ltr") parentbox.style.left=divleft+"px";
			else parentbox.style.right=divleft+"px";
		}
		divbox.style.height='auto';
		if (inbox) inbox.style.display='block';
		if (inbox2) inbox2.style.display='none';
		fontdef = document.getElementById("fontdef-"+boxid);
		if (fontdef) {
			oldfont = fontdef.className;
			fontdef.className = 'detailsZoom';
		}
		namedef = document.getElementById("namedef-"+boxid);
		if (namedef) {
			oldname = namedef.className;
			namedef.className = 'nameZoom';
		}
		addnamedef = document.getElementById("addnamedef-"+boxid);
		if (addnamedef) {
			oldaddname = addnamedef.className;
			addnamedef.className = 'nameZoom';
		}
		if (thumb1) {
			oldthumbdisp = thumb1.style.display;
			thumb1.style.display='block';
			oldimgw = thumb1.width;
			oldimgh = thumb1.height;
			if (oldimgw) thumb1.style.width = oldimgw*2;
			if (oldimgh) thumb1.style.height = oldimgh*2;
		}
		if (sex) {
			oldimgw1 = sex.width;
			oldimgh1 = sex.height;
//			if (oldimgw1) sex.style.width = oldimgw1*2;
//			if (oldimgh1) sex.style.height = oldimgh1*2;
			if (oldimgw1) sex.style.width = "15px";
			if (oldimgh1) sex.style.height = "15px";
		}
	}
	return true;
}
function restorebox(boxid, bstyle) {
	divbox = document.getElementById("out-"+boxid);
	inbox = document.getElementById("inout-"+boxid);
	inbox2 = document.getElementById("inout2-"+boxid);
	famlinks = document.getElementById("I"+boxid+"links");
	parentbox = document.getElementById("box"+boxid);
	if (!parentbox) {
		parentbox=divbox;
	}
	thumb1 = document.getElementById("box-"+boxid+"-thumb");
	icons = document.getElementById("icons-"+boxid);
	iconz = document.getElementById("iconz-"+boxid);	// This is the Zoom icon
	if (divbox) {
		if (icons) icons.style.display = oldiconsdislpay;
		if (iconz) {
			if (iconz.src==zoominout[0].src) iconz.src = zoominout[1].src;
			else iconz.src = zoominout[0].src;
		}
		big = 0;
		if (sex) {
			oldimgw1 = oldimgw1+"px";
			oldimgh1 = oldimgh1+"px";
			sex.style.width = oldimgw1;
			sex.style.height = oldimgh1;
		}
		if (thumb1) {
			oldimgw = oldimgw+"px";
			oldimgh = oldimgh+"px";
			thumb1.style.width = oldimgw;
			thumb1.style.height = oldimgh;
			thumb1.style.display=oldthumbdisp;
		}
		divbox.style.height=oldheight;
		divbox.style.width=oldwidth;
		if (parentbox) {
			//if (parentbox!=divbox) parentbox.style.width = parseInt(parentbox.style.width)-diff;
			//alert("here");
			parentbox.style.zIndex=oldz;
			if (url.indexOf("pedigree.php")!=-1) {
				if (textDirection=="ltr") parentbox.style.left=oldleft+"px";
				else parentbox.style.right=oldleft+"px";
				// Keep the family links box in its place
				if (famlinks) {
					famlinks.style.left = famleft+"px";
				}
			}
		}
		if (inbox) inbox.style.display='none';
		if (inbox2) inbox2.style.display='block';
		fontdef = document.getElementById("fontdef-"+boxid);
		if (fontdef) fontdef.className = oldfont;
		namedef = document.getElementById("namedef-"+boxid);
		if (namedef) namedef.className = oldname;
		addnamedef = document.getElementById("addnamedef-"+boxid);
		if (addnamedef) addnamedef.className = oldaddname;
	}
	return true;
}

/**
 * changes a CSS class for the given element
 *
 * @author John Finlay
 * @param string elementid the id for the dom element you want to give a new class
 * @param string newclass the name of the new class to apply to the element
 */
function change_class(elementid, newclass) {
	element = document.getElementById(elementid);
	if (element) {
		element.className = newclass;
	}
}

/**
 * changes the src of an image
 *
 * @author John Finlay
 * @param string elementid the id for the dom element you want to give a new icon
 * @param string newicon the src path of the new icon to apply to the element
 */
function change_icon(elementid, newicon) {
	element = document.getElementById(elementid);
	if (element) {
		element.src = newicon;
	}
}

var menutimeouts = new Array();
var currentmenu = null;
/**
 * Shows a submenu
 *
 * @author John Finlay
 * @param string elementid the id for the dom element you want to show
 */
function show_submenu(elementid, parentid, dir) {
	var pagewidth = document.body.scrollWidth+document.documentElement.scrollLeft;
	var element = document.getElementById(elementid);
	if (element && element.style) {
		element.style.visibility='visible';
		if (dir=="down") {
			var pelement = document.getElementById(parentid);
			if (pelement) { 							
				element.style.left=pelement.style.left;
//				element.style.right=pelement.style.right;
				var boxright = element.offsetLeft+element.offsetWidth+10;
				if (document.all) {
					pagewidth = document.body.offsetWidth;
//					var pomoc = 15;
					if (textDirection=="rtl") element.style.left = (element.offsetLeft-70)+'px';
				}
				else {
//					var pomoc = 70;
					pagewidth = document.body.scrollWidth+document.documentElement.scrollLeft-70;
					if (textDirection=="rtl") {
//						element.style.width = "220px";
						boxright = element.offsetLeft+element.offsetWidth+10;
					}
				}
				if (boxright > pagewidth) {
					var menuleft = pagewidth-element.offsetWidth;
					element.style.left = menuleft + "px";						
				}				
			}
		}
		if (dir=="right") {
			var pelement = document.getElementById(parentid);
			if (pelement) {
				element.style.left=(pelement.offsetLeft+pelement.offsetWidth-40)+"px";
			}
		}

		if (element.offsetLeft < 0) element.style.left = "0px";

		//-- make sure the submenu is the size of the largest child
		var maxwidth = 0;
		var count = element.childNodes.length;
		for(var i=0; i<count; i++) {
			var child = element.childNodes[i];
			if (child.offsetWidth > maxwidth+5) maxwidth = child.offsetWidth;
		}
		if (element.offsetWidth <  maxwidth) {
			element.style.width = maxwidth+"px";
		}

		currentmenu = elementid;
	}
	clearTimeout(menutimeouts[elementid]);
	menutimeouts[elementid] = null;
}

/**
 * Hides a submenu
 *
 * @author John Finlay
 * @param string elementid the id for the dom element you want to hide
 */
function hide_submenu(elementid) {
	element = document.getElementById(elementid);
	if (element && element.style) {
		element.style.visibility='hidden';
	}
	clearTimeout(menutimeouts[elementid]);
	menutimeouts[elementid] = null;
}

/**
 * Sets a timeout to hide a submenu
 *
 * @author John Finlay
 * @param string elementid the id for the dom element you want to hide
 */
function timeout_submenu(elementid) {
	if (menutimeouts[elementid] == null) {
		tout = setTimeout("hide_submenu('"+elementid+"')", 500);
		menutimeouts[elementid] = tout;
	}
}
/*
var language_filter, magnify, pastefield;
language_filter = "";
magnify = "";
function findSpecialChar(field) {
	pastefield = field;
	window.open('find.php?type=specialchar&language_filter='+language_filter+'&magnify='+magnify, '', 'top=55,left=55,width=200,height=500,scrollbars=1,resizeable=1');
	return false;
}

function paste_char(value,lang,mag) {
	pastefield.value += value;
	language_filter = lang;
	magnify = mag;
}
*/
function checkKeyPressed(e) {
	if (IE) key = window.event.keyCode;
	else key = e.which;
	if (key==118) {
		if (pastefield) findSpecialChar(pastefield);
	}
	if (key==112) {
		helpPopup(whichhelp);
	}
	//else if (pastefield) pastefield.value=key;
}

function focusHandler(evt) {
	var e = evt ? evt : window.event;
	if (!e) return;
	if (e.target)
		pastefield = e.target;
	else if(e.srcElement) pastefield = e.srcElement;
}

function loadHandler() {
	var i, j;

	for (i = 0; i < document.forms.length; i++)
		for (j = 0; j < document.forms[i].elements.length; j++) {
			if (document.forms[i].elements[j].type=="text") {
				if (document.forms[i].elements[j].onfocus==null) document.forms[i].elements[j].onfocus = focusHandler;
			}
		}
}
var IE = document.all?true:false;
if (!IE) document.captureEvents(Event.MOUSEMOVE|Event.KEYDOWN|Event.KEYUP);
//document.onmousemove = getMouseXY;
document.onkeyup = checkKeyPressed;

//Highlight image script - START
//Highlight image script- By Dynamic Drive
//For full source code and more DHTML scripts, visit http://www.dynamicdrive.com
//This credit MUST stay intact for use

function makevisible(cur,which){
strength=(which==0)? 1 : 0.2

if (cur.style.MozOpacity)
cur.style.MozOpacity=strength
else if (cur.filters)
cur.filters.alpha.opacity=strength*100
}
//Highlight image script - END

//Enable and disable languages
function enabledisablelanguage(language) {
//	window.open('editlang_edit_settings.php?action=save&ln='+language+'&source=enabledisable');
	location.href='editlang_edit_settings.php?action=toggleActive&ln='+language;
}

function toggleStatus(sel) {
	var cbox = document.getElementById(sel);
	cbox.disabled=!(cbox.disabled);
}

var monthLabels = new Array();
  monthLabels[1] = "January";
  monthLabels[2] = "February";
  monthLabels[3] = "March";
  monthLabels[4] = "April";
  monthLabels[5] = "May";
  monthLabels[6] = "June";
  monthLabels[7] = "July";
  monthLabels[8] = "August";
  monthLabels[9] = "September";
  monthLabels[10] = "October";
  monthLabels[11] = "November";
  monthLabels[12] = "December";
  
  var monthShort = new Array();
  monthShort[1] = "JAN";
  monthShort[2] = "FEB";
  monthShort[3] = "MAR";
  monthShort[4] = "APR";
  monthShort[5] = "MAY";
  monthShort[6] = "JUN";
  monthShort[7] = "JUL";
  monthShort[8] = "AUG";
  monthShort[9] = "SEP";
  monthShort[10] = "OCT";
  monthShort[11] = "NOV";
  monthShort[12] = "DEC";
  
  var daysOfWeek = new Array();
  daysOfWeek[0] = "S";
  daysOfWeek[1] = "M";
  daysOfWeek[2] = "T";
  daysOfWeek[3] = "W";
  daysOfWeek[4] = "T";
  daysOfWeek[5] = "F";
  daysOfWeek[6] = "S";
  
  var weekStart = 0;
  
  function cal_setMonthNames(jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dec) {
  	monthLabels[1] = jan;
  	monthLabels[2] = feb;
  	monthLabels[3] = mar;
  	monthLabels[4] = apr;
  	monthLabels[5] = may;
  	monthLabels[6] = jun;
  	monthLabels[7] = jul;
  	monthLabels[8] = aug;
  	monthLabels[9] = sep;
  	monthLabels[10] = oct;
  	monthLabels[11] = nov;
  	monthLabels[12] = dec;
  }
  
  function cal_setDayHeaders(sun, mon, tue, wed, thu, fri, sat) {
  	daysOfWeek[0] = sun;
  	daysOfWeek[1] = mon;
  	daysOfWeek[2] = tue;
  	daysOfWeek[3] = wed;
  	daysOfWeek[4] = thu;
  	daysOfWeek[5] = fri;
  	daysOfWeek[6] = sat;
  }
  
  function cal_setWeekStart(day) {
  	if (day >=0 && day < 7) weekStart = day;
  }
  
  function cal_toggleDate(dateDivId, dateFieldId) {
  	var dateDiv = document.getElementById(dateDivId);
  	if (!dateDiv) return false;
  	
  	if (dateDiv.style.visibility=='visible') {
  		dateDiv.style.visibility = 'hidden';
  		return false;
  	}
  	if (dateDiv.style.visibility=='show') {
  		dateDiv.style.visibility = 'hide';
  		return false;
  	}
  	
  	var dateField = document.getElementById(dateFieldId);
  	if (!dateField) return false;
  	
  	var dateStr = dateField.value;
  	var date = new Date();
  	if (dateStr!="" && dateStr.indexOf("@")==-1) date = new Date(dateStr);
  	if (!date) return;
  	
  	dateDiv.innerHTML = cal_generateSelectorContent(dateFieldId, dateDivId, date);
  	if (dateDiv.style.visibility=='hidden') {
  		dateDiv.style.visibility = 'visible';
  		return false;
  	}
  	if (dateDiv.style.visibility=='hide') {
  		dateDiv.style.visibility = 'show';
  		return false;
  	}
  	return false;
  }
  
  function cal_generateSelectorContent(dateFieldId, dateDivId, date) {
  	var content = '<table border="1"><tr>';
  	content += '<td><select name="'+dateFieldId+'_daySelect" id="'+dateFieldId+'_daySelect" onchange="return cal_updateCalendar(\''+dateFieldId+'\', \''+dateDivId+'\');">';
  	for(i=1; i<32; i++) {
  		content += '<option value="'+i+'"';
  		if (date.getDate()==i) content += ' selected="selected"';
  		content += '>'+i+'</option>';
  	}
  	content += '</select></td>';
  	content += '<td><select name="'+dateFieldId+'_monSelect" id="'+dateFieldId+'_monSelect" onchange="return cal_updateCalendar(\''+dateFieldId+'\', \''+dateDivId+'\');">';
  	for(i=1; i<13; i++) {
  		content += '<option value="'+i+'"';
  		if (date.getMonth()+1==i) content += ' selected="selected"';
  		content += '>'+monthLabels[i]+'</option>';
  	}
  	content += '</select></td>';
  	content += '<td><input type="text" name="'+dateFieldId+'_yearInput" id="'+dateFieldId+'_yearInput" size="5" value="'+date.getFullYear()+'" onchange="return cal_updateCalendar(\''+dateFieldId+'\', \''+dateDivId+'\');" /></td></tr>';
  	content += '<tr><td colspan="3">';
  	content += '<table width="100%">';
  	content += '<tr>';
  	j = weekStart;
	for(i=0; i<7; i++) {
		content += '<td ';
		content += 'class="descriptionbox"';
		content += '>';
		content += daysOfWeek[j];
		content += '</td>';
		j++;
		if (j>6) j=0;
	}
	content += '</tr>';
  	
  	var tdate = new Date(date.getFullYear(), date.getMonth(), 1);
  	var day = tdate.getDay();
  	day = day - weekStart;
  	var daymilli = (1000*60*60*24);
  	tdate = tdate.getTime() - (day*daymilli);
  	tdate = new Date(tdate);
  	
  	for(j=0; j<6; j++) {
  		content += '<tr>';
  		for(i=0; i<7; i++) {
  			content += '<td ';
  			if (tdate.getMonth()==date.getMonth()) {
  				if (tdate.getDate()==date.getDate()) content += 'class="descriptionbox"';
  				else content += 'class="optionbox"';
  			}
  			else content += 'style="background-color:#EAEAEA; border: solid #AAAAAA 1px;"';
  			content += '><a href="#" onclick="return cal_dateClicked(\''+dateFieldId+'\', \''+dateDivId+'\', '+tdate.getFullYear()+', '+tdate.getMonth()+', '+tdate.getDate()+');">';
  			content += tdate.getDate();
  			content += '</a></td>';
  			datemilli = tdate.getTime() + daymilli;
  			tdate = new Date(datemilli);
  		}
  		content += '</tr>';
  	}
  	content += '</table>';
  	content += '</td></tr>';
  	content += '</table>';
  	
  	return content;
  }
  
  function cal_setDateField(dateFieldId, year, month, day) {
  	var dateField = document.getElementById(dateFieldId);
  	if (!dateField) return false;
  	if (day<10) day = "0"+day;
  	dateField.value = day+' '+monthShort[month+1]+' '+year;
  	return false;
  }
  
  function cal_updateCalendar(dateFieldId, dateDivId) {
  	var dateSel = document.getElementById(dateFieldId+'_daySelect');
  	if (!dateSel) return false;
  	var monthSel = document.getElementById(dateFieldId+'_monSelect');
  	if (!monthSel) return false;
  	var yearInput = document.getElementById(dateFieldId+'_yearInput');
  	if (!yearInput) return false;
  	
  	var month = parseInt(monthSel.options[monthSel.selectedIndex].value);
  	month = month-1;

  	var date = new Date(yearInput.value, month, dateSel.options[dateSel.selectedIndex].value);
  	if (!date) alert('Date error '+date);
  	cal_setDateField(dateFieldId, date.getFullYear(), date.getMonth(), date.getDate());
  	
  	var dateDiv = document.getElementById(dateDivId);
  	if (!dateDiv) {
  		alert('no dateDiv '+dateDivId);
  		return false;
  	}
  	dateDiv.innerHTML = cal_generateSelectorContent(dateFieldId, dateDivId, date);
  	
  	return false;
  }
  
function cal_dateClicked(dateFieldId, dateDivId, year, month, day) {
cal_setDateField(dateFieldId, year, month, day);
cal_toggleDate(dateDivId, dateFieldId);
return false;
}

/**
 * Find links to the following data:
 * - Individuals
 * - Places
 * - Families
 * - Media (These are media filenames)
 * - Object (These are media references e.g. M1)
 * - Sources
 * - Repositories
 * - Special characters
 *
 * These links are initated by the functions:
 * - print_findindi_link
 * - print_findplace_link
 * - print_findfamily_link
 * - print_findmedia_link
 * - print_findobject_link
 * - print_findsource_link
 * - print_findrepository_link
 * - print_specialchar_link
*/
function findIndi(field) {
     pastefield = field;
     window.open('find.php?type=indi', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
     return false;
}

function findPlace(field) {
	pastefield = field;
	window.open('find.php?type=place', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	return false;
}

function findFamily(field) {
	pastefield = field;
	window.open('find.php?type=fam', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	return false;
}
function findMedia(field) {
	pastefield = field;
	window.open('find.php?type=media', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	return false;
}
function findObject(field) {
	pastefield = field;
	window.open('find.php?type=object', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	return false;
}
function findSource(field) {
	pastefield = field;
	window.open('find.php?type=source', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	return false;
}
function findRepository(field) {
	pastefield = field;
	window.open('find.php?type=repo', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	return false;
}
function findSpecialChar(field) {
	pastefield = field;
	window.open('find.php?type=specialchar&amp;language_filter='+language_filter+'&amp;magnify='+magnify, '', 'top=55,left=55,scrollbars=1,resizeable=1');
	return false;
}

/**
 * Editing links that opens an edit window via edit_interface.php
 */
/**
 * Delete
 */
function deleteperson(pid, change_type) {
	window.open('edit_interface.php?action=deleteperson&pid='+pid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
	return false;
}

function delete_family(famid, change_type) {
	window.open('edit_interface.php?action=deletefamily&famid='+famid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=750,height=600,resizable=1,scrollbars=1');
	return false;
}

function deletesource(pid, change_type) {
	 window.open('edit_interface.php?action=deletesource&pid='+pid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
	 return false;
}

function deleterepository(pid, change_type) {
	 window.open('edit_interface.php?action=deleterepo&pid='+pid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
	 return false;
}

function delete_record(pid, fact, change_type, count) {
	if (!count) count = 1;
	window.open('edit_interface.php?action=delete&pid='+pid+'&change_type='+change_type+'&fact='+fact+'&count='+count+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Reorder
 */
function reorder_children(famid, change_type) {
	window.open('edit_interface.php?action=reorder_children&pid='+famid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function reorder_families(pid, change_type) {
	window.open('edit_interface.php?action=reorder_fams&pid='+pid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function change_family_members(famid, change_type) {
	window.open('edit_interface.php?action=changefamily&famid='+famid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=750,height=600,resizable=1,scrollbars=1');
	return false;
}

/**
 * Add
 */
function add_record(pid, fact, change_type) {
	factfield = document.getElementById(fact);
	if (factfield) {
		factvalue = factfield.options[factfield.selectedIndex].value;
		if (factvalue.substr(0, 10)=="clipboard_") window.open('edit_interface.php?action=paste&pid='+pid+'&change_type='+change_type+'&fact='+factvalue.substr(10)+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
		else window.open('edit_interface.php?action=add&pid='+pid+'&change_type='+change_type+'&fact='+factvalue+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	}
	return false;
}

function add_new_record(pid, fact, change_type) {
	window.open('edit_interface.php?action=add&pid='+pid+'&change_type='+change_type+'&fact='+fact+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewchild(famid, change_type) {
	window.open('edit_interface.php?action=addchild&famid='+famid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewspouse(famid, famtag, change_type) {
	window.open('edit_interface.php?action=addspouse&famid='+famid+'&change_type='+change_type+'&famtag='+famtag+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function addspouse(pid, famtag, change_type) {
	window.open('edit_interface.php?action=addspouse&pid='+pid+'&change_type='+change_type+'&famtag='+famtag+'&famid=new&'+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function add_famc(pid, change_type) {
	 window.open('edit_interface.php?action=addfamlink&pid='+pid+'&change_type='+change_type+'&famtag=CHIL'+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function add_fams(pid, famtag, change_type) {
	window.open('edit_interface.php?action=addfamlink&pid='+pid+'&change_type='+change_type+'&famtag='+famtag+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}
function add_name(pid, change_type) {
	window.open('edit_interface.php?action=addname&pid='+pid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewparent(pid, famtag, change_type) {
	window.open('edit_interface.php?action=addnewparent&pid='+pid+'&change_type='+change_type+'&famtag='+famtag+'&famid=new'+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewparentfamily(pid, famtag, famid, change_type) {
	window.open('edit_interface.php?action=addnewparent&pid='+pid+'&change_type='+change_type+'&famtag='+famtag+'&famid='+famid+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewsource(field, change_type) {
	pastefield = field;
	window.open('edit_interface.php?action=addnewsource&pid=newsour&change_type='+change_type, '', 'top=70,left=70,width=600,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewrepository(field, change_type) {
	pastefield = field;
	window.open('edit_interface.php?action=addnewrepository&pid=newrepo&change_type='+change_type, '', 'top=70,left=70,width=600,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Edit
 */
function edit_record(pid, fact, count, change_type) {
	window.open('edit_interface.php?action=edit&pid='+pid+'&fact='+fact+'&count='+count+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=900,height=650,resizable=1,scrollbars=1');
	return false;
}

function edit_raw(pid, change_type) {
	window.open('edit_interface.php?action=editraw&pid='+pid+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=550,resizable=1,scrollbars=1');
	return false;
}

function edit_name(pid, fact, count, change_type) {
	if (!count) count = 1;
	window.open('edit_interface.php?action=editname&pid='+pid+'&fact='+fact+'&count='+count+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Copy
*/
function copy_record(pid, fact, count, change_type) {
	window.open('edit_interface.php?action=copy&pid='+pid+'&fact='+fact+'&count='+count+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Link
 */
function linkspouse(pid, famtag, change_type) {
	window.open('edit_interface.php?action=linkspouse&pid='+pid+'&change_type='+change_type+'&famtag='+famtag+'&famid=new&'+sessionname+"="+sessionid, '', 'top=50,left=50,width=710,height=500,resizable=1,scrollbars=1');
	return false;
}
