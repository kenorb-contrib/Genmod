<?php
/**
 * RSS Block
 *
 * This is the RSS block
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
 * @subpackage Blocks
 * @version $Id: rss_block.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
$GM_BLOCKS["print_RSS_block"]["name"]		= GM_LANG_rss_feeds;
$GM_BLOCKS["print_RSS_block"]["descr"]		= "rss_descr";
$GM_BLOCKS["print_RSS_block"]["type"]		= "gedcom";
$GM_BLOCKS["print_RSS_block"]["canconfig"]	= true;
$GM_BLOCKS["print_RSS_block"]["config"]		= array("print_gedcom_stats"=>"yes", 
													"print_gedcom_news"=>"yes", 
													"print_recent_changes"=>"yes", 
													"print_todays_events"=>"yes",
													"print_block_name_top10"=>"yes",
													"print_upcoming_events"=>"yes");
$GM_BLOCKS["print_RSS_block"]["rss"]		= false;

/**
 * Print RSS Block
 *
 * Prints a block allowing the user to login to the site directly from the portal
 */
function print_RSS_block($block = true, $config="", $side, $index) {
	global $LANGUAGE, $command, $QUERY_STRING, $GM_BLOCKS, $gm_user, $GM_IMAGES, $bot;

	print "<!-- Start RSS Block //-->";
	print "<div id=\"rss_block\" class=\"BlockContainer\">\n";
		print "<div class=\"BlockHeader\">";
			PrintHelpLink("rss_feed_help", "qm", "rss_feeds");
			if ($GM_BLOCKS["print_RSS_block"]["canconfig"]) {
				if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&(!empty($username)))) {
					if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
					else $name = $gm_user->username;
					print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=600,height=350,scrollbars=1,resizable=1'); return false;\">";
					BlockFunctions::PrintAdminIcon();
					print "</a>";
				}
			}
			print "<div class=\"BlockHeaderText\">".GM_LANG_rss_feeds."</div>";
		print "</div>";
		print "<div class=\"BlockContent\">";
			print "<form method=\"post\" action=\"\" name=\"rssform\" class=\"RSSBlockForm\">\n";
			//print get_lang_select();
			//print "\n\t<select name=\"rssStyle\" class=\"BlockSelectField\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=' + document.rssform.lang.value + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";
			print "\n\t<select name=\"rssStyle\" class=\"BlockSelectField\" onchange=\"document.getElementById('rss_button').href = 'rss.php?lang=" . $LANGUAGE . "' + (document.rssform.module.value==''? '' : '&amp;module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&amp;rssStyle=' + document.rssform.rssStyle.value);\">";
			print "\n\t\t<option value=\"RSS0.91\"";
			if (GedcomConfig::$RSS_FORMAT=="RSS0.91") print " selected=\"selected\"";
			print ">RSS 0.91</option>";
			print "\n\t\t<option value=\"RSS1.0\"";
			if (GedcomConfig::$RSS_FORMAT=="RSS1.0") print " selected=\"selected\"";
			print ">RSS 1.0</option>";
			print "\n\t\t<option value=\"RSS2.0\"";
			if (GedcomConfig::$RSS_FORMAT=="RSS2.0") print " selected=\"selected\"";
			print ">RSS 2.0</option>";
			print "\n\t\t<option value=\"ATOM\"";
			if (GedcomConfig::$RSS_FORMAT=="ATOM") print " selected=\"selected\"";
			print ">ATOM</option>";
			print "\n\t\t<option value=\"HTML\"";
			if (GedcomConfig::$RSS_FORMAT=="HTML") print " selected=\"selected\"";
			print ">HTML</option>";
			if (!$bot) {
				print "\n\t\t<option value=\"JS\"";
				if (GedcomConfig::$RSS_FORMAT=="JS") print " selected=\"selected\"";
				print ">JavaScript</option>";
			}
			print "\n\t</select>";
			print "\n\t<select name=\"module\" class=\"BlockSelectField\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=" . $LANGUAGE . "' + (document.rssform.module.value==''? '' : '&amp;module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&amp;rssStyle=' + document.rssform.rssStyle.value);\">";
			print "\n\t\t<option value=\"\">" . GM_LANG_all . "</option>";
			if (empty($config)) $config = $GM_BLOCKS["print_RSS_block"]["config"];
			foreach ($config as $block => $value) {
				if ($value == "yes") {
					print "\n\t\t<option value=\"".$block."\">" . $GM_BLOCKS[$block]["name"] . " </option>";
				}
			}
			print "\n\t</select>";
			print "<div class=\"RSSBlockButton\"><a id=\"rss_button\" href=\"rss.php?lang=" . $LANGUAGE . "\"><img class=\"Icon\" src=\"images/xml.gif\" alt=\"RSS\" title=\"RSS\" /></a></div>";
			print "</form>\n";
		print "</div>";
	print "</div>";
	print "<!-- End Theme Select Block //-->";
}

function print_rss_block_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION;
	
	if (empty($config)) $config = $GM_BLOCKS["print_RSS_block"]["config"];
	?><tr>
		<td class="NavBlockLabel"><?php print GM_LANG_RSS_block_select; ?></td>
		<td class="NavBlockField">
			<?php foreach($GM_BLOCKS as $blockname => $params) {
				if ($params["rss"] == true) { ?>
						<input type="checkbox" value="yes" name="<?php print $blockname; ?>" <?php 
						if (!isset($config[$blockname]) && !isset($GM_BLOCKS["print_RSS_block"]["config"][$blockname])) print "disabled=\"disabled\"";
						elseif (isset($config[$blockname]) && $config[$blockname] == "yes") print "checked=\"checked\"";
						?>
						 /> <?php print $params["name"]; ?><br />
				<?php }
			} ?>
		</td>
	</tr>
	<?php
}



/*function get_lang_select() {
	 global $gm_lang, $gm_language, $flagsfile, $LANGUAGE, $language_settings;
	 global $LANG_FORM_COUNT;
	 global $QUERY_STRING;
	 $ret="";
	 if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) {
		if (empty($LANG_FORM_COUNT)) $LANG_FORM_COUNT=1;
		else $LANG_FORM_COUNT++;

		//$ret .= "<select name=\"lang\" class=\"BlockSelectField\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=' + document.rssform.lang.value + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";
		$ret .= "<select name=\"lang\" class=\"BlockSelectField\" onchange=\"javascript:document.getElementById('rss_button').href = 'rss.php?lang=" . $LANGUAGE . "' + (document.rssform.module.value==''? '' : '&module=' + document.rssform.module.value) + (document.rssform.rssStyle.value==''? '' : '&rssStyle=' + document.rssform.rssStyle.value);\">";

		foreach ($gm_language as $key=>$value) {
			if ($language_settings[$key]["gm_lang_use"]) {
				$ret .= "\n\t\t\t<option value=\"$key\" ";
				if ($LANGUAGE == $key) {
					$ret .=  "selected=\"selected\"";
				}
				$ret .=  ">".$gm_lang[$key]."</option>";
			}
		}
		$ret .=  "</select>\n\n";
	 }
	 return $ret;
}*/
?>
