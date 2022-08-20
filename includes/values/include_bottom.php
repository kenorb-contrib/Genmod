<?php
/**
 * File for including anything just before the </body> tag and after all existing body text of the page
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
 * @subpackage Display
 * @version $Id: include_bottom.php 2 2015-03-14 13:19:00Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

// Below the last line of this file you can add anything that will be printed on any page of genmod, except the popup pages like help and editing.
// If you enter additional PHP code, be sure to let it start before the last line, otherwise PHP won't recognize it.
// So, if you enter Java Script, just copy it after the last line.
?>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://www." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "sjouke.nl/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("UA-4848104-1");
pageTracker._initData();
pageTracker._trackPageview();
</script>