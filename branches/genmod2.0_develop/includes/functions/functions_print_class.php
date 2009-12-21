<?php
/**
 * Function for printing
 *
 * Various printing functions used by all scripts and included by the functions.php file.
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
 * @subpackage Display
 * @version $Id$
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class PrintFunctions {
	
	//-- function to print a privacy error with contact method
	public function PrintPrivacyError($username) {
		
		 $method = GedcomConfig::$CONTACT_METHOD;
		
		 if ($username == GedcomConfig::$WEBMASTER_EMAIL) $method = GedcomConfig::$SUPPORT_METHOD;
		 $user =& User::GetInstance($username);
		 if (empty($user->username)) $method = "mailto";
		 print "<br /><span class=\"error\">".GM_LANG_privacy_error;
		 if ($method=="none") {
			  print "</span><br />\n";
			  return;
		 }
		 print GM_LANG_more_information;
		 if ($method=="mailto") {
			  if (!$user) {
				   $email = $username;
				   $fullname = $username;
			  }
			  else {
				   $email = $user->email;
				   $fullname = $user->firstname." ".$user->lastname;
			  }
			  print " <a href=\"mailto:$email\">".$fullname."</a></span><br />";
		 }
		 else {
			  print " <a href=\"#\" onclick=\"message('$username','$method'); return false;\">".$user->firstname." ".$user->lastname."</a></span><br />";
		 }
	}
}
?>
