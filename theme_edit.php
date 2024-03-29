<?php
/**
 * Modifies the themes by means of a user friendly interface
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
 * @subpackage Themes
 * @version $Id: theme_edit.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($action)) $action="";
if (!isset($choose_theme)) $choose_theme="";

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
$uname = $gm_username;
if (empty($uname)) {
	if (LOGIN_URL == "") header("Location: login.php?url=theme_edit.php");
	else header("Location: ".LOGIN_URL."?url=theme_edit.php");
	exit;
}
$user =& User::GetInstance($uname);

// -- print html header information
PrintHeader("Theme editor");

?>
<form name="editform" method="post";">
<input type="hidden" name="oldusername" value="<?php print $uname; ?>" />
<table class="ListTable <?php print $TEXT_DIRECTION; ?>">
<tr><td class="facts_label"><?php print GM_LANG_user_theme;PrintHelpLink("edituser_user_theme_help", "qm");?></td><td class="facts_value" valign="top">
	<select name="choose_theme">
	<option value=""><?php print GM_LANG_site_default; ?></option>
			<?php
				$themes = GetThemeNames();
				foreach($themes as $indexval => $themedir) {
					print "<option value=\"".$themedir["dir"]."\"";
					if ($themedir["dir"] == $choose_theme) print " selected=\"selected\"";
					print ">".$themedir["name"]."</option>\n";
				}
			?>
		</select>
</td></tr>
</table>
<input type="submit" value="Change stylesheet" />
</form>
<?php
if (strlen($choose_theme) == 0) $choose_theme = GedcomConfig::$THEME_DIR;
$output = file($choose_theme."/style.css");
$start = FALSE;
$empty = TRUE;
$level = "";
foreach ($output as $l => $tag) {
	if (stristr($tag, ".something") || $empty == TRUE) {
		if (stristr($tag, "{") == TRUE) {
			$pos = strpos($tag, "{");
			$level = substr($tag, 0, $pos);
			$tags[$level]["id"][] = ".something {";
			$tags[$level]["names"][] = ".something";
			$tags[$level]["definitions"][] = "/*empty style to make sure that the BODY style is not ignored */";
			$tags[$level]["close"] = "}";
		}
		if (stristr($tag, "}") == TRUE) $empty = FALSE;
	}
	else {
		if (stristr($tag, "{") == TRUE && stristr($tag, "}") == TRUE) {
			$pos = strpos($tag, "{");
			$level = substr($tag, 0, $pos);
			$class = substr($tag, $pos+1);
			// Continue
// 			$items = preg_split("/;/", $tag);
// 			?><pre><?php
// 			print_r ($items);
// 			exit;
// 			?></pre><?php
			if (stristr($level, "{") != TRUE) $heading = $level."{";
			if (stristr($class, "}") == TRUE) $class = substr(trim($class), 0, -1);
			$tags[$level]["id"][] = $heading;
			$tags[$level]["names"][] = $heading;
			$tags[$level]["definitions"][] = $class;
			$tags[$level]["close"] = "}";
			$level = "";
			$start = FALSE;
		}
		else if ($start == TRUE && stristr($tag, "}") != TRUE) {
			$tagnamepos = strpos(trim($tag), ":");
			$tagname = substr(trim($tag), 0, $tagnamepos);
			$tagdef = substr(trim($tag), $tagnamepos+1);
			if (substr($tagdef,-1) == ";") $tagdef = substr($tagdef, 0, -1);
			$names[] = $tagname;
			$defs[] = $tagdef;
		}
		else if (stristr($tag, "{")){
			$start = TRUE;
			$level = trim(preg_replace("/{/", "", $tag));
			$tags[$level]["id"] = $tag;
		}
		else if (stristr($tag, "}")){
			$start = FALSE;
			if (stristr($tag, "}") == TRUE && strlen(trim($tag) > "1")) {
				$class = substr(trim($tag), 0, -1);
				$tagnamepos = strpos(trim($class), ":");
				$tagname = substr(trim($class), 0, $tagnamepos);
				$tagdef = substr(trim($class), $tagnamepos+1);
				if (substr($tagdef,-1) == ";") $tagdef = substr($tagdef, 0, -1);
			}
			else {
				$tagname = trim($tag);
				$tagdef = trim($tag);
			}
			$names[] = $tagname;
			$defs[] = $tagdef;
			$tags[$level]["names"] = $names;
			$tags[$level]["definitions"] = $defs;
			$tags[$level]["close"] = "}";
			$level = "";
			$names = array();
			$defs = array();
		}
		else {
			$level = "";
			$names = array();
			$defs = array();
			$start = FALSE;
		}
	}
}
print "<table width=\"50%\" class=\"FactsTable\" border=\"3\" cellspacing=\"0\" cellpadding=\"0\">";
foreach ($tags as $l => $tag){
	print "<tr><th class=\"FactDetailLabel\" colspan=\"3\">".trim($l)."</th></tr>";
	$i = 0;
	foreach ($tag["names"] as $n => $name) {
		print "<tr><td width=\"15%\">$name</td><td width=\"10%\">".$tag["definitions"][$n]."</td>";
		// Loop is only entered first time array is accessed
		if ($i == "0") {
			$t = 0;
			while (count($tag["names"]) > $t) {
				if (!isset($style)) $style = "style=\"";
				$style .= $tag["names"][$t].":".$tag["definitions"][$t]."; ";
				$t++;
			}
			$i = 1;
			$style .= "\">Genmod";

			// Build up third block
			$message = "<td rowspan=\"".count($tag["names"])."\" valign=\"top\" width=\"25%\" ";
			$message .= $style."</td></tr>\r\n";
			print $message;
			unset($style);
		}
		else print "<td width=\"25%\"></td></tr>";
	}
	print "<tr><td><br /></td><td><br /></td><td><br /></td></tr>\r\n";
}
print "</table>";
PrintFooter();
print "\n\t</div>\n</body>\n</html>";
?>
