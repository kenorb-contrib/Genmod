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

class SearchHelp {
	
	var $name = "SearchHelp";
	var $searchtext = "";
	var $searchuser = "no";
	var $searchconfig = "no";
	var $searchhow = "";
	// var $searchintext = "";
	var $found = 0;
	var $searchresults = "<hr />";
	
	function SearchHelp(&$genmod, &$gm_lang, &$gm_username) {
		$this->CheckAccess($gm_username);
		$this->GetPageValues($genmod);
		$this->AddHeader($gm_lang);
		$this->ShowForm($genmod, $gm_lang, $gm_username);
		if (!empty($this->searchtext))  {
			$this->PerformSearch($genmod);
			$this->PrintResults($gm_lang);
		}
		$this->AddFooter();
	}
	
	function CheckAccess($gm_username) {
		global $gm_user;
		
		// If no admin, always search in user help
		if (!$gm_user->UserGedcomAdmin()) $this->searchuser = "yes";
	}
	
	function AddHeader(&$gm_lang) {
		PrintSimpleHeader(GM_LANG_hs_title);
	}
	
	function AddFooter() {
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
			document.entersearch.searchtext.focus();
		//-->
		</script>
		<?php
		PrintSimpleFooter();
	}
	
	function GetPageValues(&$genmod) {
		if (isset($_REQUEST['searchtext'])) $this->searchtext = $_REQUEST['searchtext'];
		if (isset($_REQUEST['searchuser'])) $this->searchuser = $_REQUEST['searchuser'];
		if (isset($_REQUEST['searchconfig'])) $this->searchconfig = $_REQUEST['searchconfig'];
		if (isset($_REQUEST['searchhow'])) $this->searchhow = $_REQUEST['searchhow'];
		// if (isset($_REQUEST['searchintext'])) $this->searchintext = $_REQUEST['searchintext'];
		// On first entry, initially check the boxes
		if ($genmod['action'] !== 'search') {
			$this->searchuser = 'yes';
			$this->searchhow = 'any';
			// $this->searchintext = 'true';
		}
	}
	
	function ShowForm(&$genmod, &$gm_lang) {
		// Print the form for input
		?>
		<form name="entersearch" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post" >
			<input name="action" type="hidden" value="search" />
			<input type="hidden" name="page" value="<?php echo $genmod['page'];?>" />
			<div class="topbottombar"><?php print_help_link("hs_title_help", "qm", "hs_title"); echo GM_LANG_hs_title; ?></div>
			<!-- // Enter the keyword(s) -->
			<div id="searchhelp_text">
				<label for="searchtext"><?php print_help_link("hs_keyword_advice", "qm", "hs_keyword"); echo GM_LANG_hs_keyword; ?></label>
				<input type="text" id="searchtext" name="searchtext" dir="ltr" size="60" value="<?php echo $this->searchtext; ?>" />
			</div>
			<!-- // How to search -->
			<div id="searchhelp_how">
				<label for="searchhow"><?php print_help_link("hs_searchhow_advice", "qm", "hs_searchhow"); echo GM_LANG_hs_searchhow; ?></label>
				<input type="radio" id="searchhow" name="searchhow" dir="ltr" value="any"
				<?php
				if ($this->searchhow == "any") echo " checked=\"checked\"";
				echo " />".GM_LANG_hs_searchany;
				?>
				<input type="radio" name="searchhow" dir="ltr" value="all"
				<?php
				if ($this->searchhow == "all") echo " checked=\"checked\"";
				echo " />".GM_LANG_hs_searchall;
				?>
				<input type="radio" name="searchhow" dir="ltr" value="sentence"
				<?php
				if ($this->searchhow == "sentence") echo " checked=\"checked\"";
				echo " />".GM_LANG_hs_searchsentence;
				?>
			</div>
			<div class="topbottombar">
				<input type="submit" name="entertext" value="<?php echo GM_LANG_hs_search;?>" />
				<input type="button" value="<?php echo GM_LANG_hs_close; ?>" onclick='self.close();' />
			</div>
		</form>
		<?php
		
		/**
		// Not possible because help texts are in the database
		// Show choice where to search only to admins
		if (UserIsAdmin($gm_username)) {
			echo "<td class=\"shade1\"><input type=\"checkbox\" name=\"searchuser\" dir=\"ltr\" value=\"yes\"";
			if ($this->searchuser == "yes") echo " checked=\"checked\"";
			echo " />".GM_LANG_hs_searchuser."<br />";
			echo "<input type=\"checkbox\" name=\"searchconfig\" dir=\"ltr\" value=\"yes\"";
			if ($this->searchconfig == "yes") echo " checked=\"checked\"";
			echo " />".GM_LANG_hs_searchconfig."</td></tr><tr>";
		}
		*/
	}
	
	function PerformSearch(&$genmod) {
		// Load languages
		$helpvarnames = array();
		$helpvarnames = LoadLanguage($genmod['language'], true, true);
		
		// Split the search criteria if all or any is chosen. Otherwise, just fill the array with the sentence
		$criteria = array();
		if ($this->searchhow == "sentence") $criteria[] = $this->searchtext;
		else $criteria = preg_split("/ /", $this->searchtext);
		
		// Search in the previously stored vars for a hit and print it
		foreach ($helpvarnames as $key => $value) {
			$repeat = 0;
			$helptxt = print_text($key,0,1);
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
			
			if (	(($this->searchhow == "any") && ($cfound >= 1)) ||
				(($this->searchhow == "all") && ($cnotfound == 0)) ||
				($this->searchhow == "sentence") && ($cfound >= 1)) {
				$this->searchresults .= $helptxt.'<hr />';
				$this->found++;
			}
		}
	}
	
	function PrintResults(&$gm_lang) {
		// Print total results, if a search has been performed
		if (!empty($this->searchtext)) {
			echo $this->searchresults;
			echo '<div id="searchhelp_result" class="topbottombar">'.GM_LANG_hs_results.' '.$this->found.'</div>';
		}
	}
}
$searchhelp = new SearchHelp($genmod, $gm_lang, $gm_username);
?>