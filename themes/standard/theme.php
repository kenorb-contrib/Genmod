<?php
/**
 * Standard theme
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
 * @subpackage Themes
 * @version $Id: theme.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

$theme_name = "Standard";														//-- the name of this theme

define('GM_STYLESHEET', GedcomConfig::$THEME_DIR."style.css");					//-- CSS level 2 stylesheet to use
define('GM_RTL_STYLESHEET', GedcomConfig::$THEME_DIR."style_rtl.css");      	//-- CSS level 2 stylesheet to use
define('GM_MAIL_STYLESHEET', GedcomConfig::$THEME_DIR."style_mail.css");    	//-- CSS level 2 stylesheet to use
define('GM_PRINT_STYLESHEET', GedcomConfig::$THEME_DIR."print.css");			//-- CSS level 2 print stylesheet to use
define('GM_MENUBAR', GedcomConfig::$THEME_DIR."menubar.php");					//-- File to display the top menu
define('GM_FOOTERFILE', GedcomConfig::$THEME_DIR."footer.html");				//-- Footer information for the site
define('GM_PRINT_FOOTERFILE', GedcomConfig::$THEME_DIR."print_footer.html");	//-- Print Preview Footer information for the site
define('GM_PRINT_HEADERFILE', GedcomConfig::$THEME_DIR."print_header.html");	//-- Print Preview Header information for the site

define('GM_USE_HELPIMG', true);						// set to true to use image for help questionmark, set to false to use gm_lang["qm"]
define('GM_IMAGE_DIR', 'images');					//-- directory to look for images

//-- variables for image names
//- GM main icons
$GM_IMAGES["gedcom"]["large"] = "gedcom.gif";
$GM_IMAGES["help"]["large"] = "help.gif";
$GM_IMAGES["indis"]["large"] = "indis.gif";
$GM_IMAGES["media"]["large"] = "media.gif";
$GM_IMAGES["notes"]["large"] = "notes.gif";
$GM_IMAGES["pedigree"]["large"] = "pedigree.gif";
$GM_IMAGES["charts"]["large"] = "charts.gif";
$GM_IMAGES["sfamily"]["large"] = "sfamily.gif";
$GM_IMAGES["reports"]["large"] = "reports(II).gif";
$GM_IMAGES["source"]["large"] = "source.gif";
$GM_IMAGES["repository"]["large"] = "repository.gif";
$GM_IMAGES["list"]["large"] = "lists.gif";
$GM_IMAGES["chart"]["large"] = "chart.gif";

//- Theme main icons
$GM_IMAGES["calendar"]["large"] = "calendar.gif";
// $GM_IMAGES["clippings"]["large"] = "clippings.gif";
$GM_IMAGES["clippings"]["large"] = "reports.gif";
$GM_IMAGES["search"]["large"] = "search.gif";

//- GM small icons
$GM_IMAGES["admin"]["small"] = "small/admin.gif";
$GM_IMAGES["ancestry"]["small"] = "small/ancestry.gif";
$GM_IMAGES["calendar"]["small"] = "small/calendar.gif";
$GM_IMAGES["cfamily"]["small"] = "small/cfamily.gif";
$GM_IMAGES["clippings"]["small"] = "small/clippings.gif";
$GM_IMAGES["edit_indi"]["small"] = "small/edit_indi.gif";
$GM_IMAGES["edit_fam"]["small"] = "small/edit_fam.gif";
$GM_IMAGES["edit_sour"]["small"] = "small/edit_sour.gif";
$GM_IMAGES["fambook"]["small"] = "small/fambook.gif";
$GM_IMAGES["fanchart"]["small"] = "small/fanchart.gif";
$GM_IMAGES["gedcom"]["small"] = "small/gedcom.gif";
$GM_IMAGES["help"]["small"] = "small/help.gif";
$GM_IMAGES["indis"]["small"] = "small/indis.gif";
$GM_IMAGES["mygenmod"]["small"] = "small/my_genmod.gif";
$GM_IMAGES["patriarch"]["small"] = "small/patriarch.gif";
$GM_IMAGES["pedigree"]["small"] = "small/pedigree.gif";
$GM_IMAGES["search"]["small"] = "small/search.gif";
$GM_IMAGES["sex"]["small"] = "small/male.gif";
$GM_IMAGES["sexf"]["small"] = "small/female.gif";
$GM_IMAGES["sexn"]["small"] = "small/fe_male.gif";
$GM_IMAGES["sfamily"]["small"] = "small/sfamily.gif";
$GM_IMAGES["statistic"]["small"] = "small/statistic.gif";
$GM_IMAGES["timeline"]["small"] = "small/timeline.gif";
$GM_IMAGES["reports"]["small"] = "small/reports.gif";
$GM_IMAGES["hourglass"]["small"] = "small/hourglass.gif";
$GM_IMAGES["repository"]["small"] = "small/repository.gif";
$GM_IMAGES["list"]["small"] = "small/list.gif";
$GM_IMAGES["chart"]["small"] = "small/chart.gif";

//- GM buttons for data entry pages
$GM_IMAGES["addrepository"]["button"] = "buttons/addrepository.gif";
$GM_IMAGES["addsource"]["button"] = "buttons/addsource.gif";
$GM_IMAGES["addnote"]["button"] = "buttons/addnote.gif";
$GM_IMAGES["autocomplete"]["button"] = "buttons/autocomplete.gif";
$GM_IMAGES["calendar"]["button"] = "buttons/calendar.gif";
$GM_IMAGES["family"]["button"] = "buttons/family.gif";
$GM_IMAGES["indi"]["button"] = "buttons/indi.gif";
$GM_IMAGES["keyboard"]["button"] = "buttons/keyboard.gif";
$GM_IMAGES["media"]["button"] = "buttons/media.gif";
$GM_IMAGES["addmedia"]["button"] = "buttons/addmedia.gif";
$GM_IMAGES["note"]["button"] = "buttons/note.gif";
$GM_IMAGES["place"]["button"] = "buttons/place.gif";
$GM_IMAGES["repository"]["button"] = "buttons/repository.gif";
$GM_IMAGES["source"]["button"] = "buttons/source.gif";
$GM_IMAGES["edit"]["button"] = "buttons/edit.gif";
$GM_IMAGES["delete"]["button"] = "buttons/delete.gif";

//- Theme small icons
$GM_IMAGES["descendant"]["small"] = "small/descendancy.gif";
$GM_IMAGES["media"]["small"] = "small/media.gif";
$GM_IMAGES["place"]["small"] = "small/place.gif";
$GM_IMAGES["relationship"]["small"] = "small/relationship.gif";
$GM_IMAGES["source"]["small"] = "small/source.gif";

// Media images
$GM_IMAGES["media"]["pdf"] = "media/pdf.gif";
$GM_IMAGES["media"]["doc"] = "media/doc.gif";
$GM_IMAGES["media"]["ged"] = "media/ged.gif";

//- other images
$GM_IMAGES["dline2"]["other"] = "dline2.gif";
$GM_IMAGES["dline"]["other"] = "dline.gif";
$GM_IMAGES["hline"]["other"] = "hline.gif";
$GM_IMAGES["spacer"]["other"] = "spacer.gif";
$GM_IMAGES["genmod"]["other"] = "genmod.gif";
$GM_IMAGES["larrow2"]["other"] = "larrow2.gif";
$GM_IMAGES["larrow"]["other"] = "larrow.gif";
$GM_IMAGES["minus"]["other"] = "minus.gif";
$GM_IMAGES["note"]["other"] = "notes.gif";
$GM_IMAGES["plus"]["other"] = "plus.gif";
$GM_IMAGES["rarrow2"]["other"] = "rarrow2.gif";
$GM_IMAGES["rarrow"]["other"] = "rarrow.gif";
$GM_IMAGES["uarrow"]["other"] = "uarrow.gif";
$GM_IMAGES["uarrow2"]["other"] = "uarrow2.gif";
$GM_IMAGES["darrow"]["other"] = "darrow.gif";
$GM_IMAGES["darrow2"]["other"] = "darrow2.gif";
$GM_IMAGES["vline"]["other"] = "vline.gif";
$GM_IMAGES["uarrow3"]["other"] = "uarrow3.gif";
$GM_IMAGES["zoomin"]["other"] = "zoomin.gif";
$GM_IMAGES["zoomout"]["other"] = "zoomout.gif";
$GM_IMAGES["rdarrow"]["other"] = "rdarrow.gif";
$GM_IMAGES["udarrow"]["other"] = "udarrow.gif";
$GM_IMAGES["ldarrow"]["other"] = "ldarrow.gif";
$GM_IMAGES["ddarrow"]["other"] = "ddarrow.gif";
$GM_IMAGES["remove"]["other"]	= "remove.gif";
$GM_IMAGES["link"]["other"]		= "link.gif";
$GM_IMAGES["delete"]["other"]	= "delete.gif";
$GM_IMAGES["download"]["other"]	= "download.gif";
$GM_IMAGES["check"]["other"]	= "checked.gif";
$GM_IMAGES["nocheck"]["other"]	= "pix1.gif";

//- digits
$GM_IMAGES["0"]["digit"] = "0.jpg";
$GM_IMAGES["1"]["digit"] = "1.jpg";
$GM_IMAGES["2"]["digit"] = "2.jpg";
$GM_IMAGES["3"]["digit"] = "3.jpg";
$GM_IMAGES["4"]["digit"] = "4.jpg";
$GM_IMAGES["5"]["digit"] = "5.jpg";
$GM_IMAGES["6"]["digit"] = "6.jpg";
$GM_IMAGES["7"]["digit"] = "7.jpg";
$GM_IMAGES["8"]["digit"] = "8.jpg";
$GM_IMAGES["9"]["digit"] = "9.jpg";

//- Logfile images
$GM_IMAGES["log"]["information"] 	= "information.gif";
$GM_IMAGES["log"]["warning"] 		= "warning.gif";
$GM_IMAGES["log"]["error"] 			= "error.gif";

//-- This section defines variables for the pedigree chart
$bwidth = 225;		// -- width of boxes on pedigree chart
$bheight = 100;		// -- height of boxes on pedigree chart
$baseyoffset = 10;	// -- position the entire pedigree tree relative to the top of the page
$basexoffset = 0;	// -- position the entire pedigree tree relative to the left of the page
$bxspacing = 4;		// -- horizontal spacing between boxes on the pedigree chart
$byspacing = 4;		// -- vertical spacing between boxes on the pedigree chart

// -- global variables for the descendancy chart
$Dbaseyoffset = 30;		// -- position the entire descendancy tree relative to the top of the page
$Dbasexoffset = 0;		// -- position the entire descendancy tree relative to the left of the page
$Dbxspacing = 0;		// -- horizontal spacing between boxes
$Dbyspacing = 1;		// -- vertical spacing between boxes
$Dbwidth = 270;			// -- width of DIV layer boxes
$Dbheight = 80;			// -- height of DIV layer boxes
$Dindent = 15;			// -- width to indent descendancy boxes
$Darrowwidth = 15;		// -- additional width to include for the up arrows

// Arrow symbol or icon for up-page links on Help pages
$ImgSrc = GM_IMAGE_DIR."/uarrow3.gif";
$UpArrow = "<b>^&nbsp;&nbsp;</b>";
if (file_exists($ImgSrc)) $UpArrow = "<img src=\"$ImgSrc\" class=\"Icon\" border=\"0\" alt=\"\" />";
define("GM_LANG_UpArrow", $UpArrow);	// help_text.xx.php requires this _untranslatable_ term!

?>
