<?php
/**
 * Download config files that could not be saved.
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
 * @version $Id: config_download.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Admin
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

if ((UserController::AdminUserExists() && !$gm_user->userIsAdmin()) && CONFIGURED) {
	header("Location: admin.php");
	exit;
}

if (empty($file)) $file="config.php";

header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=$file");

print $_SESSION[$file];
print "\r\n";

?>
