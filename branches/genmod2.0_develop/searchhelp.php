<?php
/**
 * Search in help files 
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
 * This Page Is Valid XHTML 1.0 Transitional! > 3 September 2005
 *
 * @package Genmod
 * @subpackage Help
 * @version $Id$
 */

require("config.php");

if (!$gm_user->UserGedcomAdmin()) $searchuser = "yes";

else $searchuser = "no";
if (!isset($searchtext)) $searchtext = "";
if (!isset($searchhow)) $searchhow = "";
if (!isset($action) || $action != "search") {
	$searchuser = 'yes';
	$searchhow = 'any';
}
PrintSimpleHeader(GM_LANG_hs_title);

?>
<form name="entersearch" action="<?php print SCRIPT_NAME; ?>" method="post" >
	<input name="action" type="hidden" value="search" />
	<div class="topbottombar"><?php PrintHelpLink("hs_title_help", "qm", "hs_title"); print GM_LANG_hs_title; ?></div>
	<!-- // Enter the keyword(s) -->
	<div id="searchhelp_text">
		<label for="searchtext"><?php PrintHelpLink("hs_keyword_advice", "qm", "hs_keyword"); print GM_LANG_hs_keyword; ?></label>
		<input type="text" id="searchtext" name="searchtext" dir="ltr" size="60" value="<?php print $searchtext; ?>" />
	</div>
	<!-- // How to search -->
	<div id="searchhelp_how">
		<label for="searchhow"><?php PrintHelpLink("hs_searchhow_advice", "qm", "hs_searchhow"); print GM_LANG_hs_searchhow; ?></label>
		<input type="radio" id="searchhow" name="searchhow" dir="ltr" value="any"
		<?php
		if ($searchhow == "any") print " checked=\"checked\"";
		print " />".GM_LANG_hs_searchany;
		?>
		<input type="radio" name="searchhow" dir="ltr" value="all"
		<?php
		if ($searchhow == "all") print " checked=\"checked\"";
		print " />".GM_LANG_hs_searchall;
		?>
		<input type="radio" name="searchhow" dir="ltr" value="sentence"
		<?php
		if ($searchhow == "sentence") print " checked=\"checked\"";
		print " />".GM_LANG_hs_searchsentence;
		?>
	</div>
	<div class="topbottombar">
		<input type="submit" name="entertext" value="<?php print GM_LANG_hs_search;?>" />
		<input type="button" value="<?php print GM_LANG_hs_close; ?>" onclick='self.close();' />
	</div>
</form>
<?php

if (!empty($searchtext))  {
	$found = 0;
	$searchresults = "<hr />";
	// Load languages
	$helpvarnames = array();
	$helpvarnames = LanguageFunctions::LoadLanguage($LANGUAGE, true, true);
	
	// Split the search criteria if all or any is chosen. Otherwise, just fill the array with the sentence
	$criteria = array();
	if ($searchhow == "sentence") $criteria[] = $searchtext;
	else $criteria = preg_split("/ /", $searchtext);
	
	// Search in the previously stored vars for a hit and print it
	foreach ($helpvarnames as $key => $value) {
		$repeat = 0;
		$helptxt = PrintText($key,0,1);
		// Remove hyperlinks
		$helptxt = preg_replace("/<a[^<>]+>/", "", $helptxt);
		$helptxt = preg_replace("/<\/a>/", "", $helptxt);
		// Remove unresolved language variables
		$helptxt = preg_replace("/#gm[^#]+#/i", "", $helptxt);
		// Save the original text for clean search
		$helptxtorg = $helptxt;
		// Scroll through the criteria
		$cfound = 0;
		$cnotfound = 0;
		foreach ($criteria as $ckey => $criterium) {
			// See if there is a case insensitive hit
			if (strpos(Str2Upper($helptxtorg), Str2Upper($criterium))) {
				// Set the search string for preg_replace, case insensitive
				$srch = "/$criterium/i";
				// The \\0 is for wrapping the existing string in the text with the span
				$repl = "<span class=\"search_hit\">\\0</span>";
				$helptxt = preg_replace($srch, $repl, $helptxt);
				$cfound++;
			}
			else $cnotfound++;
		}
		
		if (($searchhow == "any" && $cfound >= 1) ||
			($searchhow == "all" && $cnotfound == 0) ||
			($searchhow == "sentence" && $cfound >= 1)) {
			$searchresults .= $helptxt.'<hr />';
			$found++;
		}
	}
	// Print total results, if a search has been performed
	if (!empty($searchtext)) {
		print $searchresults;
		print '<div id="searchhelp_result" class="topbottombar">'.GM_LANG_hs_results.' '.$found.'</div>';
	}
}
?>
<script language="JavaScript" type="text/javascript">
<!--
	document.entersearch.searchtext.focus();
//-->
</script>
<?php
PrintSimpleFooter();
?>