<?php
/**
 * Detect the browser the user is using
 *
 * Genmod: Genealogy Viewer
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2000 (c) The SourceForge Crew
 * http://sourceforge.net
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
 * @version $Id: detectbrowser.php,v 1.1 2006/03/15 21:28:38 roland-d Exp $
 */
unset ($BROWSER_AGENT);
unset ($BROWSER_VER);
unset ($BROWSER_PLATFORM);

function browser_get_agent () {
global $BROWSER_AGENT;
return $BROWSER_AGENT;
}

function browser_get_version() {
global $BROWSER_VER;
return $BROWSER_VER;
}

function browser_get_platform() {
global $BROWSER_PLATFORM;
return $BROWSER_PLATFORM;
}

function browser_is_mac() {
if (browser_get_platform()=='Mac') {
return true;
} else {
return false;
}
}

function browser_is_windows() {
if (browser_get_platform()=='Win') {
return true;
} else {
return false;
}
}

function browser_is_ie() {
if (browser_get_agent()=='IE') {
return true;
} else {
return false;
}
}

function browser_is_netscape() {
if (browser_get_agent()=='MOZILLA') {
return true;
} else {
return false;
}
}


/*
Determine browser and version
*/

if (ereg( 'MSIE ([0-9].[0-9]{1,2})',$_SERVER["HTTP_USER_AGENT"],$log_version)) {
$BROWSER_VER=$log_version[1];
$BROWSER_AGENT='IE';
} elseif (ereg( 'Opera ([0-9].[0-9]{1,2})',$_SERVER["HTTP_USER_AGENT"],$log_version)) {
$BROWSER_VER=$log_version[1];
$BROWSER_AGENT='OPERA';
} elseif (ereg( 'Mozilla/([0-9].[0-9]{1,2})',$_SERVER["HTTP_USER_AGENT"],$log_version)) {
$BROWSER_VER=$log_version[1];
$BROWSER_AGENT='MOZILLA';
} else {
$BROWSER_VER=0;
$BROWSER_AGENT='OTHER';
}

/*
Determine platform
*/

if (strstr($_SERVER["HTTP_USER_AGENT"],'Win')) {
$BROWSER_PLATFORM='Win';
} else if (strstr($_SERVER["HTTP_USER_AGENT"],'Mac')) {
$BROWSER_PLATFORM='Mac';
} else if (strstr($_SERVER["HTTP_USER_AGENT"],'Linux')) {
$BROWSER_PLATFORM='Linux';
} else if (strstr($_SERVER["HTTP_USER_AGENT"],'Unix')) {
$BROWSER_PLATFORM='Unix';
} else {
$BROWSER_PLATFORM='Other';
}

/*
//debug code
echo "\n\nAgent: $HTTP_USER_AGENT";
echo "\nIE: ".browser_is_ie();
echo "\nMac: ".browser_is_mac();
echo "\nWindows: ".browser_is_windows();
echo "\nPlatform: ".browser_get_platform();
echo "\nVersion: ".browser_get_version();
echo "\nAgent: ".browser_get_agent();
*/

?>
