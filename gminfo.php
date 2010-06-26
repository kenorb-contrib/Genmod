<?php
/**
 * Displays information on the PHP installation
 *
 * Provides links for administrators to get to other administrative areas of the site
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
 * @subpackage Admin
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=gminfo.php?action=".$action);
	else header("Location: ".LOGIN_URL."?url=gminfo.php?action=".$action);
exit;
}

if (!isset($action)) $action = "";

if ($action == "phpinfo") {
	$helpindex = "phpinfo_help";
	PrintHeader(GM_LANG_phpinfo);
	 ?>
	<div class="center">
		<?php
		
		ob_start();
		  
		   phpinfo();
		   $php_info = ob_get_contents();
		      
		ob_end_clean();
		
		$php_info    = str_replace(" width=\"600\"", " width=\"\"", $php_info);
		$php_info    = str_replace("</div></body></html>", "", $php_info);
		$php_info    = str_replace("<table", "<table class=\"center facts_table ltr\"", $php_info);
		$php_info    = str_replace("td class=\"e\"", "td class=\"facts_value wrap\"", $php_info);
		$php_info    = str_replace("td class=\"v\"", "td class=\"facts_value wrap\"", $php_info);
		$php_info    = str_replace("tr class=\"v\"", "tr", $php_info);
		$php_info    = str_replace("tr class=\"h\"", "tr", $php_info);
		
		$php_info    = str_replace(";", "; ", $php_info);
		$php_info    = str_replace(",", ", ", $php_info);
		
		function strip_spaces($match) {
			return "<a name=\"".str_replace(" ", "_", $match[1])."\"";
		}
		$php_info	 = preg_replace_callback("/\<a name=\"(.*)\"/", "strip_spaces", $php_info);
		// Put logo in table header
		
		$logo_offset = strpos($php_info, "<td>");
		$php_info = substr_replace($php_info, "<td colspan=\"3\" class=\"facts_label03 wrap\">", $logo_offset, 4);
		$logo_width_offset = strpos($php_info, "width=\"\"");
		$php_info = substr_replace($php_info, "width=\"800\"", $logo_width_offset, 8);
		$php_info    = str_replace(" width=\"\"", "", $php_info);
		
		
		$offset          = strpos($php_info, "<table");
		$php_info	= substr($php_info, $offset);
		
		print $php_info;
		
		?>		
	</div>
	<?php
//	exit;
}

if ($action=="confighelp") {
	PrintHeader(GM_LANG_help_config);
	print "<h3 class=\"center\">".Str2Upper(GM_LANG_help_config)."</h3><br />";
	$language_array = array();
	$language_array = LanguageFunctions::LoadLanguage($LANGUAGE,true, true);
	
	print "<ol>";
	foreach ($language_array as $string => $text) {
		if (stristr($text, "~#gm_lang")) {
			print "<li>";
			print stripslashes(PrintText($text,0,2)) . "<br /><br /></li>\r\n";
		}
	}
    print "</ol>";
}

PrintFooter();
?>
