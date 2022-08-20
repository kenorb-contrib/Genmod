<?php
/**
 * Module system for adding features to Genmod.
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
 * @version $Id: module.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @author Patrick Kellum
 */

/**
 * Inclusion of the configuration file
*/
require_once 'config.php';

// Simple mod system, based on the older phpnuke/postnuke
define('GM_MOD_SIMPLE', 1);
// More advanced OO module system
define('GM_MOD_OO', 2);

if (!isset ($_REQUEST['mod'])) {
	// GM_MOD_NUKE
	if (isset ($_REQUEST['name'])) $_REQUEST['mod'] = $_REQUEST['name'];
}
if (file_exists('modules/'.$_REQUEST['mod'].'.php')) {
	$modinfo = parse_ini_file('modules/'.$_REQUEST['mod'].'.php', true);
}
else {
	header('Location: index.php');
	print ' ';
	exit;
}
switch ($modinfo['Module']['type']) {
	case GM_MOD_SIMPLE:	{
		if (!isset ($_REQUEST['gmaction'])) $_REQUEST['gmaction'] = 'index';
		if (!file_exists('modules/'.$_REQUEST['mod'].'/'.$_REQUEST['gmaction'].'.php')) {
			$_REQUEST['gmaction'] = 'index';
		}
		include_once 'modules/'.$_REQUEST['mod'].'/'.$_REQUEST['gmaction'].'.php';
		break;
	}
	case GM_MOD_OO: {
		if (!isset ($_REQUEST['method'])) {
			$_REQUEST['method'] = 'main';
		}
		if (!isset ($_REQUEST['class'])) {
			$_REQUEST['class'] = $_REQUEST['mod'];
		}
		include_once 'modules/'.$_REQUEST['mod'].'/'.$_REQUEST['class'].'.php';
		$mod = new $_REQUEST['mod']();
		if (!method_exists($mod, $_REQUEST['method'])) {
			$_REQUEST['method'] = 'main';
		}
		$out = $mod->$_REQUEST['method']();
		if (is_string($out)) print $out;
		break;
	}
	default:
	{
		print 'Error: Unknown module type.';
		break;
	}
}

function mod_print_header($title, $head='', $use_alternate_styles=true) {
	ob_start();
	PrintHeader($title, $head, $use_alternate_styles);
	$out = ob_get_contents();
	ob_end_clean();
	return $out;
}

function mod_print_simple_header($title) {
	ob_start();
	PrintSimpleHeader($title);
	$out = ob_get_contents();
	ob_end_clean();
	return $out;
}

function mod_print_footer() {
	ob_start();
	PrintFooter();
	$out = ob_get_contents();
	ob_end_clean();
	return $out;
}

function mod_print_simple_footer() {
	ob_start();
	PrintSimpleFooter();
	$out = ob_get_contents();
	ob_end_clean();
	return $out;
}
?>