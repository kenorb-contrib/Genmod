<?php
/**
 * Measures and stores the maximum execution time for a PHP script 
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
require("config.php");
print_header($gm_lang["max_time"]);
if(!$Users->UserIsAdmin($gm_username)) exit;
if (!isset($maxtime)) $maxtime = 0;

// Fix by Thomas for compression problems on Apache
if(function_exists('apache_setenv')) { 
	// apparently @ isn't enough to make php ignore this failing
	@apache_setenv('no-gzip', '1');
}

print $gm_lang["maxtime_explain"];
print "<br /><br />";

if ($maxtime == 0) {
	print "<form name=\"maxtimeset\" method=\"post\" action=\"maxtime.php\">";
	print $gm_lang["maxtime_explain2"];
	print "&nbsp;&nbsp;&nbsp;<select id=\"maxtime\" name=\"maxtime\">";
		for ($i=1; $i<=10; $i++) {
			$s = $i * 60;
			print "<option value=\"".$s."\"";
			print ">".$i."</option>";
		}
	print "</select>&nbsp;&nbsp;&nbsp;";
	print "<input type=\"submit\" name=\"action\" value=\"".$gm_lang["go"]."\" />";
	print "</form>";
}
else {
	@set_time_limit(0);
	$secs = 0;
	print $gm_lang["maxtime_now"]."&nbsp;<div id=\"max_progress\"></div>";
	while($secs < $maxtime) {
		$secs++;
//	print "Maximum execution time is at least ".$secs." seconds<br />";
		print "<script><!--\ndocument.getElementById('max_progress').innerHTML='".$secs."';//-->\n</script>";
		flush();
		@ob_flush();
		$SystemConfig->SetConfigDBValue('max_execution_time', $secs);
		sleep(1);
	}
	print $gm_lang["maxtime_lower"];
}

print_footer();
?>