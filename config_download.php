<?php
/**
 * Download config files that could not be saved.
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
 * @version $Id: config_download.php,v 1.2 2006/01/09 14:19:29 sjouke Exp $
 * @package Genmod
 * @subpackage Admin
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
require $GM_BASE_DIRECTORY.$confighelpfile["english"];
if (file_exists($GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE];

if ((adminUserExists()&&!userIsAdmin($gm_username))&&$CONFIGURED) {
	header("Location: admin.php");
	exit;
}

if (empty($file)) $file="config.php";

header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=$file");

print $_SESSION[$file];
print "\r\n";

?>
