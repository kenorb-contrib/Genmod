<?php
/**
 * Display an family book chart
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

require("config.php");

$paternals_controller = new PaternalsController();

PrintHeader($paternals_controller->pagetitle);

// -- size of the boxes
if (!$paternals_controller->show_full) $bwidth = $bwidth / 1.5;
$bwidth*=$box_width/100;
if ($paternals_controller->show_full == false) {
	$bheight = $bheight / 2.5;
}

// -- print html header information
print "<div id=\"content_pedigree\" style=\"width:".$paternals_controller->pagewidth."px; min-width:100%;\">";

print "\n\t<span class=\"PageTitleName\">".GM_LANG_paternal_chart.": ";
print PrintReady($paternals_controller->root->name);
if ($paternals_controller->root->addname != "") print " (" . PrintReady($paternals_controller->root->addname).")";
print "</span>";
?>

<script language="JavaScript" type="text/javascript">
<!--
	var pasteto;
	function open_find(textbox) {
		pasteto = textbox;
		findwin = window.open('find.php?type=indi', '', 'left=50,top=50,width=850,height=450,resizable=1,scrollbars=1');
	}
	function paste_id(value) {
		pasteto.value=value;
	}
//-->
</script>

<?php
if ($paternals_controller->view != "preview") {
	print "<form method=\"get\" name=\"people\" action=\"?\">\n";
	print "\n\t\t<table class=\"ListTable ".$TEXT_DIRECTION."\" align=\"";
	if ($TEXT_DIRECTION == "ltr") print "left";
	else print "right";
	print "\">";
	
	// Header
	$paternals_controller->PrintInputHeader();
	
	// NOTE: rootid
	$paternals_controller->PrintInputRootId();

	// NOTE: line to follow
	$paternals_controller->PrintInputLine();
	
	// NOTE: start at
	$paternals_controller->PrintInputStart();

	// NOTE: box width
	$paternals_controller->PrintInputBoxWidth();
	
	// NOTE: show full
	$paternals_controller->PrintInputShowFull();
	
	// Submit
	$paternals_controller->PrintInputSubmit();
	
	print "</table>";
	print "</form>\n";
}
$paternals_controller->PrintPaternalLines();
print "</div>\n";
print "<br /><br />\n";

PrintFooter();
?>
