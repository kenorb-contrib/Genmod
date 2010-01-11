<?php
/**
 * Display a diff between two language files to help in translating.
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
 * @subpackage Languages
 * @version $Id$
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
if ($gm_user->username = "") {
	if (LOGIN_URL == "") header("Location: login.php?url=changelanguage.php");
	else header("Location: ".LOGIN_URL."?url=changelanguage.php");
	exit;
}

if (!isset($action) or $action=="") $action="editold";

switch ($action) {
	case "addnew" :
		$helpindex = "add_new_language_help";
		PrintHeader(GM_LANG_add_new_language); 
		break;

	case "editold" :
	default :
		PrintHeader(GM_LANG_edit_lang_utility);
}
?>
<script language="JavaScript" type="text/javascript">
<!--
	function postform(which) {
		window.open('editlang_edit_settings.php?action=new_lang&new_shortcut=' + document.new_lang_form.new_shortcut.value, '', 'top=50,left=10,width=1000,height=600,scrollbars=1,resizable=1');
		return false;
	}
//-->
</script>

<?php
// Create array with configured languages in gedcoms and users
$configuredlanguages = LanguageInUse();

// Sort the Language table into localized language name order
foreach ($gm_language as $key => $value){
	$d_LangName = "lang_name_".$key;
	$Sorted_Langs[$key] = constant("GM_LANG_".$d_LangName);
}
asort($Sorted_Langs);

// Split defined languages into active and inactive
$split_langs_active = array();
$split_langs_inactive = array();
foreach ($Sorted_Langs as $key => $value){
	if ($gm_lang_use["$key"]) {
		$split_langs_active[count($split_langs_active)+1] = $key; 
	}
	else {
		$split_langs_inactive[count($split_langs_inactive)+1] = $key;
	}
}

$active = count($split_langs_active);
$inactive = count($split_langs_inactive);
$maxlines = max($active, $inactive);
?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print GM_LANG_admin;?></a></div>
	<div class="admin_link"><a href="editlang.php"><?php print GM_LANG_translator_tools;?></a></div>
</div>

<div id="content">
	<?php
	switch ($action) {
		case "addnew" :  ?>
			<form name="new_lang_form" method="post" onsubmit="postform(this); return false;">
			<input type="hidden" name="action" value="new_lang" />
			<input type="hidden" name="execute" value="true" />
			<div class="admin_topbottombar">
				<?php print GM_LANG_add_new_language; ?>
			</div>
			<div class="admin_item_box">
				<div class="width25 choice_left">
					<div class="helpicon">
						<?php PrintHelpLink("add_new_language_help", "qm", "add_new_language");?>
					</div>
					<div class="description">
						<?php require($GM_BASE_DIRECTORY . "includes/values/lang_codes_std.php"); ?>
						<select name="new_shortcut">
						<?php
						asort($lng_codes);		// Sort the language codes table into language name order
						
						foreach ($lng_codes as $key => $value) {
							$showLang = true;
							foreach ($lang_short_cut as $key02=>$value) {
								if ($value == $key) {		// This language is already in GM
									$showLang = false;
									break;
								}
							}
							if ($showLang) {
								print "<option value=\"".$key."\"";
								print ">".$lng_codes[$key][0]."</option>\n";
							}
						}
						?>
						</select>
					</div>
				</div>
				<div class="width25 center choice_right">
					<input type="submit" value="<?php print GM_LANG_add_new_lang_button;?>" />
				</div>
			</div>
			</form>
			<?php
			$USERLANG = $LANGUAGE;
			break;
		default : ?>
			<form name="lang_config_form" method="get" action="<?php print SCRIPT_NAME;?>">
				<input type="hidden" name="action" value="config_lang" />
				<div class="admin_topbottombar">
					<?php PrintHelpLink("config_lang_utility_help", "qm", "config_lang_utility");?>
					<?php print GM_LANG_config_lang_utility; ?>
				</div>
				<div class="admin_item_box">
					<div class="width25 choice_left"><?php print GM_LANG_lang_language; ?></div>
					<div class="width10 choice_middle"><?php print GM_LANG_active; ?></div>
					<div class="width10 choice_middle"><?php print GM_LANG_edit_settings; ?></div>
					<div class="width25 choice_middle"><?php print GM_LANG_lang_language; ?></div>
					<div class="width10 choice_middle"><?php print GM_LANG_active; ?></div>
					<div class="width10 choice_right"><?php print GM_LANG_edit_settings; ?></div>
				</div>
				<?php
				// Print the Language table in sorted name order
				for ($i=1; $i<=$maxlines; $i++) { ?>
					<form name="activelanguage">
					<div class="admin_item_box">
						<?php
						// Left 3 columns: Active language
						$value = "";
						if ($i <= $active) $value = $split_langs_active[$i];
					
						if ($value == "") { ?>
							<div class="width25 choice_left">&nbsp;</div>
							<div class="width10 choice_middle">&nbsp;</div>
							<div class="width10 choice_middle">&nbsp;</div>
							<?php
						} 
						else { 
							$d_LangName = "lang_name_" . $value; ?>
							<div class="width25 choice_left">
								<?php print constant("GM_LANG_".$d_LangName) ?>
							</div>
							<div class="width10 choice_middle">
								<input
								<?php
								if (LanguageInUse($value)) print " disabled";
								print " type=\"checkbox\" value=\"$value\" checked=\"checked\" onclick=\"enabledisablelanguage('$value');\" />";
								?>
							</div>
							<div class="width10 choice_middle">
								<a href="javascript: "<?php print $value;?>" onclick="window.open('editlang_edit_settings.php?action=editold&ln=<?php print $value;?>', '', 'top=50,left=10,width=1000,height=600,scrollbars=1,resizable=1'); return false;""><?php print GM_LANG_lang_edit;?></a>
							</div>
							<?php
						}
						
						// Right 3 columns: Inactive language
						$value = "";
						if ($i <= $inactive) $value = $split_langs_inactive[$i];
						if ($value == "") { ?>
							<div class="width25 choice_left">&nbsp;</div>
							<div class="width10 choice_middle">&nbsp;</div>
							<div class="width10 choice_middle">&nbsp;</div>
							<?php
						} 
						else { 
							$d_LangName = "lang_name_" . $value; ?>
							<div class="width25 choice_left">
								<?php print constant("GM_LANG_".$d_LangName); ?>
							</div>
							<div class="width10 choice_middle">
								<input type="checkbox" value="<?php print $value; ?>" onclick="enabledisablelanguage('<?php print $value; ?>');" /> 
							</div>
							<div class="width10 choice_right">
								<a href="javascript: "<?php print $value;?>" onclick="window.open('editlang_edit_settings.php?action=editold&ln=<?php print $value;?>', '', 'top=50,left=10,width=1000,height=600,scrollbars=1,resizable=1'); return false;""><?php print GM_LANG_lang_edit;?></a>
							</div>
							<?php
						}
						?>
					</div>
					</form>
					<?php
				}
			print "</form>";
			$USERLANG = $LANGUAGE;
			?>
			<div class="admin_topbottombar">
				<?php print GM_LANG_configured_languages; ?>
			</div>
			<div class="admin_item_box">
				<div class="width20 choice_left">
					<?php print GM_LANG_current_gedcoms;?>
				</div>
				<?php
				foreach ($configuredlanguages["gedcom"] as $key => $value) {
					print "<div class=\"width20 choice_middle\">";
						// Print gedcom names
						foreach($value as $gedcomname => $used) {
							print "<a href=\"editconfig_gedcom.php?gedid=".get_id_from_gedcom($gedcomname)."\" target=\"blank\">".$gedcomname."</a><br />";
						}
					print "<br /></div>";
					print "<div class=\"choice_right\">";
						// Print language name and flag
						print "<img src=\"".$language_settings[$key]["flagsfile"]."\" class=\"brightflag\" alt=\"".constant("GM_LANG_lang_name_".$key)."\" title=\"".constant("GM_LANG_lang_name_".$key)."\" />&nbsp;".constant("GM_LANG_lang_name_".$key)."<br />";
					print "</div>";
					print "</div><div class=\"admin_item_box\"><div class=\"width20 choice_left\">&nbsp;</div>";
				}
				?>
			</div>
			<div class="admin_item_box">
				<div class="width20 choice_left"><?php print GM_LANG_users_langs;?></div>
				<?php
				foreach ($configuredlanguages["users"] as $key => $value) {
					print "<div class=\"width20 choice_middle\"><a href=\"useradmin.php?action=listusers&amp;filter=language&amp;usrlang=".$key."\">".count($value)."</a></div>";
					print "<div class=\"choice_right\">";
					print "<img src=\"".$language_settings[$key]["flagsfile"]."\" class=\"brightflag\" alt=\"".constant("GM_LANG_lang_name_".$key)."\" title=\"".constant("GM_LANG_lang_name_".$key)."\" />&nbsp;".constant("GM_LANG_lang_name_".$key)."<br /></div>";
					print "</div><div class=\"admin_item_box\"><div class=\"width20 choice_left\">&nbsp;</div>";
				}
				?>
			
		</div>
		<?php
		break;
		
	}
	$LANGUAGE = $USERLANG;
	
print "</div>";

PrintFooter();
?>