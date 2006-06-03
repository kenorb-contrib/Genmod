<?php

function CheckDBLayout() {
	global $gm_lang, $DBHOST, $DBUSER, $DBPASS, $DBNAME, $TBLPREFIX, $setup_db, $link;
	
	require("db_layout.php");
	$db_layout = $db_original;
	$result = mysql_list_tables($DBNAME);
	if (!$result) {
		// NOTE: Database does not exist. Try to create it.
		print "<img src=\"images/nok.png\" alt=\"Database does not exist\"/> ";
		print "Database ".$DBNAME." does not exist.";
		print "<br />";
		$sqlcreate = "CREATE DATABASE `".$DBNAME."`";
		$rescreate = mysql_query($sqlcreate);
		if (!$rescreate) {
			print "<img src=\"images/nok.png\" alt=\"Database cannot be created\"/> ";
			print "Cannot create database: " . mysql_error().".";
			print "<br />";
			return false;
		}
		else {
			$result = mysql_list_tables($DBNAME);
			print "<img src=\"images/ok.png\" alt=\"Database created\"/> ";
			print "Database ".$DBNAME." has been created.";
			print "<br />";
		}
	}
	while ($row = mysql_fetch_row($result)) {
		// NOTE: Only take those tables who match the given table prefix
		if (substr($row[0], 0, strlen(trim($TBLPREFIX))) == trim($TBLPREFIX)) {
			// NOTE: Retrieve a list of fields of the current table
			$fields = mysql_list_fields($DBNAME, $row[0]);
			// NOTE: Count the number of fields the table has
			$columns = mysql_num_fields($fields);
			for ($i = 0; $i < $columns; $i++) {
				if (array_key_exists(mysql_field_name($fields, $i), $db_layout[$row[0]])) {
					unset($db_layout[$row[0]][mysql_field_name($fields, $i)]);
					if (count($db_layout[$row[0]]) == 0) unset($db_layout[$row[0]]);
				}
			}
		}
	}
	mysql_free_result($result);
	if (count($db_layout) == 0) $setup_db = true;
	return $db_layout;
}

function FixDBLayout($db_layout) {
	foreach ($db_layout as $tablename => $fields) {
		// NOTE: Check if the table exists
		if (mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$tablename."'")) == 1) {
			// NOTE: Table exists, only add the missing entries
			$sql = "ALTER TABLE `".$tablename."`";
			foreach ($fields as $column => $field) {
				$sql .= " ADD `".$column."` ".$field["details"].", ";
			}
			$sql = trim($sql);
			$sql = substr($sql, 0, strlen($sql)-1);
			if (!$res = mysql_query($sql)) {
				print "Query error:<br />".$sql; 
				print "<br />";
				print mysql_error();
			}
		}
		else {
			// NOTE: Table does not exist, so add the complete table
			AddMissingTable(substr($tablename, strlen(trim($_POST["TBLPREFIX"]))));
		}
	}
}

function CreateIndividuals() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."individuals` (
		`i_id` varchar(255) default NULL,
		`i_file` int(11) default NULL,
		`i_rin` varchar(255) default NULL,
		`i_name` varchar(255) default NULL,
		`i_isdead` int(11) default '1',
		`i_gedcom` text,
		`i_letter` varchar(5) default NULL,
		`i_surname` varchar(100) default NULL,
		`i_snsoundex` varchar(255) default NULL,
		`i_sndmsoundex` varchar(255) default NULL,
		`i_fnsoundex` varchar(255) default NULL,
		`i_fndmsoundex` varchar(255) default NULL,
		`i_gender` char(1) default NULL,
		KEY `indi_id` (`i_id`),
		KEY `indi_name` (`i_name`),
		KEY `indi_letter` (`i_letter`),
		KEY `indi_file` (`i_file`),
		KEY `indi_surn` (`i_surname`)
		)";
	$res = mysql_query($sql);
	if($res) {
		$res = mysql_query($sql);
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_indis"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_indis_fail"]."<br />\n";
	}
}

function CreateFamilies() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE ".$TBLPREFIX."families (
		`f_id` varchar(255) default NULL,
		`f_file` int(11) default NULL,
		`f_husb` varchar(255) default NULL,
		`f_wife` varchar(255) default NULL,
		`f_chil` text,
		`f_gedcom` text,
		`f_numchil` int(11) default NULL,
		KEY `fam_id` (`f_id`),
		KEY `fam_file` (`f_file`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_fams"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_fams_fail"]."<br />\n";
	}
}

function CreateSources() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."sources` (
		`s_id` varchar(255) default NULL,
		`s_file` int(11) default NULL,
		`s_name` varchar(255) default NULL,
		`s_gedcom` text,
		KEY `sour_id` (`s_id`),
		KEY `sour_name` (`s_name`),
		KEY `sour_file` (`s_file`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_sources"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_sources_fail"]."<br />\n";
	}
}

function CreateOther() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."other` (
		`o_id` varchar(255) default NULL,
		`o_file` int(11) default NULL,
		`o_type` varchar(20) default NULL,
		`o_gedcom` text,
		KEY `other_id` (`o_id`),
		KEY `other_file` (`o_file`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_other"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_other_fail"]."<br />\n";
	}
}

function CreatePlaces() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."places` (
		`p_id` int(11) NOT NULL default '0',
		`p_place` varchar(150) default NULL,
		`p_level` int(11) default NULL,
		`p_parent_id` int(11) default NULL,
		`p_file` int(11) default NULL,
		PRIMARY KEY  (`p_id`),
		KEY `place_place` (`p_place`),
		KEY `place_level` (`p_level`),
		KEY `place_parent` (`p_parent_id`),
		KEY `place_file` (`p_file`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_places"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_places_fail"]."<br />\n";
	}
}

function CreatePlaceLinks() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."placelinks` (
		`pl_p_id` int(11) default NULL,
		`pl_gid` varchar(255) default NULL,
		`pl_file` int(11) default NULL,
		KEY `plindex_place` (`pl_p_id`),
		KEY `plindex_gid` (`pl_gid`),
		KEY `plindex_file` (`pl_file`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_placelinks"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_placelinks_fail"]."<br />\n";
	}
}

function CreateMedia() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."media` (
		`m_id` int(11) NOT NULL auto_increment,
		`m_media` varchar(15) default NULL,
		`m_ext` varchar(6) default NULL,
		`m_titl` varchar(255) default NULL,
		`m_file` varchar(255) default NULL,
		`m_gedfile` int(11) default NULL,
		`m_gedrec` text,
		PRIMARY KEY  (`m_id`),
		KEY `m_media` (`m_media`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_media"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_media_fail"]."<br />\n";
	}
}

function CreateMediaMapping() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."media_mapping` (
		`mm_id` int(11) NOT NULL auto_increment,
		`mm_media` varchar(15) NOT NULL default '',
		`mm_gid` varchar(15) NOT NULL default '',
		`mm_order` int(11) NOT NULL default '0',
		`mm_gedfile` int(11) default NULL,
		`mm_gedrec` text,
		PRIMARY KEY  (`mm_id`),
		KEY `mm_media` (`mm_media`),
		KEY `mm_mediamapping` (`mm_media`)
		)"; 
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_media_mapping"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_media_mapping_fail"]."<br />\n";
	}
}

function CreateNames() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."names` (
		`n_gid` varchar(255) default NULL,
		`n_file` int(11) default NULL,
		`n_name` varchar(255) default NULL,
		`n_letter` varchar(5) default NULL,
		`n_surname` varchar(100) default NULL,
		`n_type` varchar(10) default NULL,
		KEY `name_gid` (`n_gid`),
		KEY `name_name` (`n_name`),
		KEY `name_letter` (`n_letter`),
		KEY `name_type` (`n_type`),
		KEY `name_surn` (`n_surname`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_names"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_names_fail"]."<br />\n";
	}
}

function CreateDates() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."dates` (
		`d_day` int(11) default NULL,
		`d_month` varchar(5) default NULL,
		`d_year` int(11) default NULL,
		`d_fact` varchar(10) default NULL,
		`d_gid` varchar(255) default NULL,
		`d_file` int(11) default NULL,
		`d_type` varchar(13) default NULL,
		KEY `date_day` (`d_day`),
		KEY `date_month` (`d_month`),
		KEY `date_year` (`d_year`),
		KEY `date_fact` (`d_fact`),
		KEY `date_gid` (`d_gid`),
		KEY `date_file` (`d_file`),
		KEY `date_type` (`d_type`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_dates"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_dates_fail"]."<br />\n";
	}
}

function CreateChanges() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."changes` (
		`ch_id` int(11) NOT NULL auto_increment,
		`ch_cid` int(11) NOT NULL default '0',
		`ch_gid` varchar(255) NOT NULL default '',
		`ch_gedfile` int(11) NOT NULL default '0',
		`ch_type` varchar(25) NOT NULL default '',
		`ch_user` varchar(255) NOT NULL default '',
		`ch_time` int(11) NOT NULL default '0',
		`ch_fact` varchar(15) default NULL,
		`ch_old` text,
		`ch_new` text,
		`ch_delete` tinyint(1) NOT NULL default '0',
		PRIMARY KEY  (`ch_id`),
		KEY `ch_gid` (`ch_gid`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_changes"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_changes_fail"]."<br />\n";
	}
}

function CreateGedcoms() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."gedcoms` (
		`g_gedcom` varchar(64) NOT NULL default '',
		`g_config` varchar(64) default NULL,
		`g_privacy` varchar(64) default NULL,
		`g_title` varchar(50) default NULL,
		`g_path` varchar(64) default NULL,
		`g_id` int(11) NOT NULL default '0',
		`g_commonsurnames` text,
		`g_isdefault` char(2) default NULL,
		PRIMARY KEY  (`g_gedcom`),
		KEY `g_id_id` (`g_id`)
		)"; 
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_gedcoms"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_gedcoms_fail"]."<br />\n";
	}
}

function CreateCounters() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."counters` (
		`c_id` varchar(120) NOT NULL default '',
		`c_number` int(11) NOT NULL default '0',
		PRIMARY KEY  (`c_id`)
		)"; 
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_counters"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_counters_fail"]."<br />\n";
	}
}

function CreateGedconf() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."gedconf` (
		`gc_gedcom` varchar(64) NOT NULL default '',
		`gc_language` varchar(64) default NULL,
		`gc_calendar_format` varchar(21) default NULL,
		`gc_display_jewish_thousands` tinyint(1) default NULL,
		`gc_display_jewish_gereshayim` tinyint(1) default NULL,
		`gc_jewish_ashkenaz_pronunciation` tinyint(1) default NULL,
		`gc_use_rtl_functions` tinyint(1) default NULL,
		`gc_character_set` varchar(6) default NULL,
		`gc_enable_multi_language` tinyint(1) default NULL,
		`gc_default_pedigree_generations` int(3) default NULL,
		`gc_max_pedigree_generations` int(3) default NULL,
		`gc_max_descendancy_generations` int(3) default NULL,
		`gc_use_rin` tinyint(1) default NULL,
		`gc_pedigree_root_id` varchar(10) default NULL,
		`gc_gedcom_id_prefix` varchar(6) default NULL,
		`gc_source_id_prefix` varchar(6) default NULL,
		`gc_repo_id_prefix` varchar(6) default NULL,
		`gc_fam_id_prefix` varchar(6) default NULL,
		`gc_media_id_prefix` varchar(6) default NULL,
		`gc_pedigree_full_details` tinyint(1) default NULL,
		`gc_pedigree_layout` tinyint(1) default NULL,
		`gc_show_empty_boxes` tinyint(1) default NULL,
		`gc_zoom_boxes` varchar(10) default NULL,
		`gc_link_icons` varchar(10) default NULL,
		`gc_abbreviate_chart_labels` tinyint(1) default NULL,
		`gc_show_parents_age` tinyint(1) default NULL,
		`gc_hide_live_people` tinyint(1) default NULL,
		`gc_require_authentication` tinyint(1) default NULL,
		`gc_welcome_text_auth_mode` int(2) default NULL,
		`gc_welcome_text_auth_mode_4` text,
		`gc_welcome_text_cust_head` tinyint(1) default NULL,
		`gc_check_child_dates` tinyint(1) default NULL,
		`gc_show_gedcom_record` tinyint(1) default NULL,
		`gc_allow_edit_gedcom` tinyint(1) default NULL,
		`gc_postal_code` tinyint(1) default NULL,
		`gc_alpha_index_lists` tinyint(1) default NULL,
		`gc_name_from_gedcom` tinyint(1) default NULL,
		`gc_show_married_names` tinyint(1) default NULL,
		`gc_show_id_numbers` tinyint(1) default NULL,
		`gc_show_fam_id_numbers` tinyint(1) default NULL,
		`gc_show_pedigree_places` tinyint(1) default NULL,
		`gc_multi_media` tinyint(1) default NULL,
		`gc_media_external` tinyint(1) default NULL,
		`gc_media_directory` varchar(64) default NULL,
		`gc_media_directory_levels` int(2) default NULL,
		`gc_show_highlight_images` tinyint(1) default NULL,
		`gc_use_thumbs_main` tinyint(1) default NULL,
		`gc_thumbnail_width` int(5) default NULL,
		`gc_auto_generate_thumbs` tinyint(1) default NULL,
		`gc_hide_gedcom_errors` tinyint(1) default NULL,
		`gc_word_wrapped_notes` tinyint(1) default NULL,
		`gc_gedcom_default_tab` int(2) default NULL,
		`gc_show_context_help` tinyint(1) default NULL,
		`gc_contact_email` varchar(64) default NULL,
		`gc_contact_method` varchar(15) default NULL,
		`gc_webmaster_email` varchar(64) default NULL,
		`gc_support_method` varchar(15) default NULL,
		`gc_home_site_url` varchar(64) default NULL,
		`gc_home_site_text` varchar(64) default NULL,
		`gc_favicon` varchar(64) default NULL,
		`gc_theme_dir` varchar(64) default NULL,
		`gc_allow_theme_dropdown` tinyint(1) default NULL,
		`gc_show_stats` tinyint(1) default NULL,
		`gc_show_counter` tinyint(1) default NULL,
		`gc_days_to_show_limit` int(4) default NULL,
		`gc_common_names_threshold` int(4) default NULL,
		`gc_common_names_add` text,
		`gc_common_names_remove` text,
		`gc_meta_author` varchar(64) default NULL,
		`gc_meta_publisher` varchar(64) default NULL,
		`gc_meta_copyright` varchar(64) default NULL,
		`gc_meta_description` varchar(64) default NULL,
		`gc_meta_page_topic` varchar(64) default NULL,
		`gc_meta_audience` varchar(64) default NULL,
		`gc_meta_page_type` varchar(64) default NULL,
		`gc_meta_robots` varchar(64) default NULL,
		`gc_meta_revisit` varchar(64) default NULL,
		`gc_meta_keywords` varchar(64) default NULL,
		`gc_meta_title` varchar(64) default NULL,
		`gc_meta_surname_keywords` tinyint(1) default NULL,
		`gc_chart_box_tags` varchar(64) default NULL,
		`gc_use_quick_update` tinyint(1) default NULL,
		`gc_show_quick_resn` tinyint(1) default NULL,
		`gc_quick_add_facts` varchar(255) default NULL,
		`gc_quick_required_facts` varchar(255) default NULL,
		`gc_quick_add_famfacts` varchar(255) default NULL,
		`gc_quick_required_famfacts` varchar(255) default NULL,
		`gc_show_lds_at_glance` tinyint(1) default NULL,
		`gc_underline_name_quotes` tinyint(1) default NULL,
		`gc_split_places` tinyint(1) default NULL,
		`gc_show_relatives_events` varchar(255) default NULL,
		`gc_expand_relatives_events` tinyint(1) default NULL,
		`gc_edit_autoclose` tinyint(1) default NULL,
		`gc_sour_facts_unique` varchar(255) default NULL,
		`gc_sour_facts_add` varchar(255) default NULL,
		`gc_repo_facts_unique` varchar(255) default NULL,
		`gc_repo_facts_add` varchar(255) default NULL,
		`gc_indi_facts_unique` varchar(255) default NULL,
		`gc_indi_facts_add` varchar(255) default NULL,
		`gc_fam_facts_unique` varchar(255) default NULL,
		`gc_fam_facts_add` varchar(255) default NULL,
		`gc_rss_format` varchar(10) default NULL,
		`gc_time_limit` int(4) default NULL,
		`gc_last_change_email` int(11) default '0',
		`gc_last_upcoming` int(11) default '0',
		`gc_last_today` int(11) default '0',
		`gc_last_stats` int(11) default '0',
		PRIMARY KEY  (`gc_gedcom`)
		)"; 
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_gedconf"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_gedconf_fail"]."<br />\n";
	}
}

function CreatePrivacy() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."privacy` (
		`p_gedcom` varchar(64) NOT NULL default '',
		`p_privacy_version` varchar(6) default NULL,
		`p_priv_hide` int(3) default NULL,
		`p_priv_public` int(3) default NULL,
		`p_priv_user` int(3) default NULL,
		`p_priv_none` int(3) default NULL,
		`p_show_dead_people` varchar(12) default NULL,
		`p_show_living_names` varchar(12) default NULL,
		`p_show_sources` varchar(12) default NULL,
		`p_max_alive_age` int(4) default NULL,
		`p_show_research_log` varchar(12) default NULL,
		`p_enable_clippings_cart` varchar(12) default NULL,
		`p_use_relationship_privacy` tinyint(1) default NULL,
		`p_max_relation_path_length` int(3) default NULL,
		`p_check_marriage_relations` tinyint(1) default NULL,
		`p_privacy_by_year` tinyint(1) default NULL,
		`p_privacy_by_resn` tinyint(1) default NULL,
		`p_person_privacy` text,
		`p_user_privacy` text,
		`p_global_facts` text,
		`p_person_facts` text,
		PRIMARY KEY  (`p_gedcom`)
		)"; 
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_privacy"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_privacy_fail"]."<br />\n";
	}
}

function CreateLanguage() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."language` (
		`lg_string` varchar(255) NOT NULL default '',
		`lg_english` text NOT NULL,
		`lg_spanish` text NOT NULL,
		`lg_german` text NOT NULL,
		`lg_french` text NOT NULL,
		`lg_hebrew` text NOT NULL,
		`lg_arabic` text NOT NULL,
		`lg_czech` text NOT NULL,
		`lg_danish` text NOT NULL,
		`lg_greek` text NOT NULL,
		`lg_finnish` text NOT NULL,
		`lg_hungarian` text NOT NULL,
		`lg_italian` text NOT NULL,
		`lg_lithuanian` text NOT NULL,
		`lg_dutch` text NOT NULL,
		`lg_norwegian` text NOT NULL,
		`lg_polish` text NOT NULL,
		`lg_portugese` text NOT NULL,
		`lg_russian` text NOT NULL,
		`lg_swedish` text NOT NULL,
		`lg_turkish` text NOT NULL,
		`lg_vietnamese` text NOT NULL,
		`lg_chinese` text NOT NULL,
		`lg_last_update_date` int(11) NOT NULL default '0',
		`lg_last_update_by` varchar(255) NOT NULL default '',
		`lg_portuguese-br` text NOT NULL,
		PRIMARY KEY  (`lg_string`),
		KEY `lang_string` (`lg_string`)
		) ";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_language"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_language_fail"]."<br />\n";
	}
}

function CreateLanguageHelp() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."language_help` (
		`lg_string` varchar(255) NOT NULL default '',
		`lg_english` text NOT NULL,
		`lg_spanish` text NOT NULL,
		`lg_german` text NOT NULL,
		`lg_french` text NOT NULL,
		`lg_hebrew` text NOT NULL,
		`lg_arabic` text NOT NULL,
		`lg_czech` text NOT NULL,
		`lg_danish` text NOT NULL,
		`lg_greek` text NOT NULL,
		`lg_finnish` text NOT NULL,
		`lg_hungarian` text NOT NULL,
		`lg_italian` text NOT NULL,
		`lg_lithuanian` text NOT NULL,
		`lg_dutch` text NOT NULL,
		`lg_norwegian` text NOT NULL,
		`lg_polish` text NOT NULL,
		`lg_portugese` text NOT NULL,
		`lg_russian` text NOT NULL,
		`lg_swedish` text NOT NULL,
		`lg_turkish` text NOT NULL,
		`lg_vietnamese` text NOT NULL,
		`lg_chinese` text NOT NULL,
		`lg_last_update_date` int(11) NOT NULL default '0',
		`lg_last_update_by` varchar(255) NOT NULL default '',
		`lg_portuguese-br` text NOT NULL,
		PRIMARY KEY  (`lg_string`),
		KEY `lang_help_string` (`lg_string`)
		);";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_language_help"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_language_help_fail"]."<br />\n";
	}
}

function CreatePages() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."pages` (
		`pag_id` int(11) NOT NULL auto_increment,
		`pag_content` longtext NOT NULL,
		`pag_title` varchar(40) NOT NULL default '',
		PRIMARY KEY  (`pag_id`)
		);";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_pages"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_pages_fail"]."<br />\n";
	}
}

function CreateEventcache() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."eventcache` (
		`ge_order` int(11) NOT NULL auto_increment,
		`ge_gedcom` varchar(64) NOT NULL default '',
		`ge_cache` varchar(10) NOT NULL default '',
		`ge_gid` varchar(10) NOT NULL default '',
		`ge_isdead` int(2) NOT NULL default '0',
		`ge_fact` varchar(16) NOT NULL default '',
		`ge_factrec` text NOT NULL,
		`ge_type` varchar(5) NOT NULL default '',
		`ge_datestamp` int(11) NOT NULL default '0',
		`ge_name` varchar(64) NOT NULL default '',
		`ge_gender` char(2) NOT NULL default '',
		KEY `i_gedcom` (`ge_gedcom`),
		KEY `i_order` (`ge_order`),
		KEY `i_cache` (`ge_cache`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_cache"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_cache_fail"]."<br />\n";
	}
}

function CreateStatscache() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."statscache` (
		`gs_gedcom` varchar(64) NOT NULL default '',
		`gs_title` varchar(255) NOT NULL default '',
		`gs_nr_surnames` int(11) NOT NULL default '0',
		`gs_nr_fams` int(11) NOT NULL default '0',
		`gs_nr_sources` int(11) NOT NULL default '0',
		`gs_nr_other` int(11) NOT NULL default '0',
		`gs_nr_events` int(11) NOT NULL default '0',
		`gs_earliest_birth_year` int(5) NOT NULL default '0',
		`gs_earliest_birth_gid` varchar(10) NOT NULL default '',
		`gs_latest_birth_year` int(5) NOT NULL default '0',
		`gs_latest_birth_gid` varchar(10) NOT NULL default '',
		`gs_longest_live_years` int(5) NOT NULL default '0',
		`gs_longest_live_gid` varchar(10) NOT NULL default '',
		`gs_avg_age` int(4) NOT NULL default '0',
		`gs_most_children_gid` varchar(10) NOT NULL default '',
		`gs_most_children_nr` int(3) NOT NULL default '0',
		`gs_avg_children` float NOT NULL default '0',
		PRIMARY KEY  (`gs_gedcom`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_scache"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_scache_fail"]."<br />\n";
	}
}

function CreateIndividualSpouse() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."individual_spouse` (
		`ID` int(11) NOT NULL auto_increment,
		`gedfile` int(11) NOT NULL default '0',
		`pid` varchar(255) NOT NULL default '',
		`family_id` varchar(255) default NULL,
		PRIMARY KEY  (`ID`),
		KEY `pid` (`pid`)
		);";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_individual_spouse"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_individual_spouse_fail"]."<br />\n";
	}
}

function CreateIndividualChild() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."individual_child` (
		`ID` int(11) NOT NULL auto_increment,
		`gedfile` int(11) NOT NULL default '0',
		`pid` varchar(255) NOT NULL default '',
		`family_id` varchar(255) default NULL,
		PRIMARY KEY  (`ID`),
		KEY `pid` (`pid`)
		);";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_individual_child"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_individual_child_fail"]."<br />\n";
	}
}

function CreateUsers() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."users` (
		`u_username` varchar(30) binary NOT NULL default '',
		`u_password` varchar(255) default NULL,
		`u_firstname` varchar(255) default NULL,
		`u_lastname` varchar(255) default NULL,
		`u_gedcomid` text,
		`u_rootid` text,
		`u_canadmin` char(2) default NULL,
		`u_canedit` text,
		`u_email` text,
		`u_verified` varchar(20) default NULL,
		`u_verified_by_admin` varchar(20) default NULL,
		`u_language` varchar(50) default NULL,
		`u_pwrequested` varchar(20) default NULL,
		`u_reg_timestamp` varchar(50) default NULL,
		`u_reg_hashcode` varchar(255) default NULL,
		`u_theme` varchar(50) default NULL,
		`u_loggedin` char(2) default NULL,
		`u_sessiontime` int(11) default NULL,
		`u_contactmethod` varchar(20) default NULL,
		`u_visibleonline` char(2) default NULL,
		`u_editaccount` char(2) default NULL,
		`u_defaulttab` int(11) default NULL,
		`u_comment` varchar(255) default NULL,
		`u_comment_exp` varchar(20) default NULL,
		`u_sync_gedcom` char(2) default NULL,
		`u_relationship_privacy` char(2) default NULL,
		`u_max_relation_path` int(11) default NULL,
		`u_auto_accept` char(2) default NULL,
		PRIMARY KEY  (`u_username`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_users"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_users_fail"]."<br />\n";
	}
}

function CreateMessages() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."messages` (
		`m_id` int(11) NOT NULL default '0',
		`m_from` varchar(255) default NULL,
		`m_to` varchar(30) default NULL,
		`m_subject` varchar(255) default NULL,
		`m_body` text,
		`m_created` varchar(255) default NULL,
		PRIMARY KEY  (`m_id`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_messages"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_messages_fail"]."<br />\n";
	}
}

function CreateFavorites() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."favorites` (
		`fv_id` int(11) NOT NULL default '0',
		`fv_username` varchar(30) default NULL,
		`fv_gid` varchar(10) default NULL,
		`fv_type` varchar(10) default NULL,
		`fv_file` varchar(100) default NULL,
		`fv_url` varchar(255) default NULL,
		`fv_title` varchar(255) default NULL,
		`fv_note` text,
		PRIMARY KEY  (`fv_id`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_favorites"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_favorites_fail"]."<br />\n";
	}
}

function CreateBlocks() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."blocks` (
		`b_id` int(11) NOT NULL default '0',
		`b_username` varchar(100) default NULL,
		`b_location` varchar(30) default NULL,
		`b_order` int(11) default NULL,
		`b_name` varchar(255) default NULL,
		`b_config` text,
		PRIMARY KEY  (`b_id`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_blocks"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_blocks_fail"]."<br />\n";
	}
}

function CreateNews() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."news` (
		`n_id` int(11) NOT NULL default '0',
		`n_username` varchar(100) default NULL,
		`n_date` int(11) default NULL,
		`n_title` varchar(255) default NULL,
		`n_text` text,
		PRIMARY KEY  (`n_id`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_news"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_news_fail"]."<br />\n";
	}
}

function CreateLog() {
	global $TBLPREFIX, $gm_lang;
	
	$sql = "CREATE TABLE `".$TBLPREFIX."log` (
		`l_type` char(2) default NULL,
		`l_category` char(2) default NULL,
		`l_timestamp` varchar(15) default NULL,
		`l_ip` varchar(15) default NULL,
		`l_user` varchar(30) default NULL,
		`l_text` text,
		`l_gedcom` varchar(15) default NULL,
		KEY `type_letter` (`l_type`),
		KEY `category_letter` (`l_category`),
		KEY `time_order` (`l_timestamp`)
		)";
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $gm_lang["created_log"]."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $gm_lang["created_log_fail"]."<br />\n";
	}
}

function AddMissingTable($tablename) {
	global $TBLPREFIX, $gm_lang;
	switch($tablename) {
		CASE "blocks":
			CreateBlocks();
			break;
		CASE "changes":
			CreateChanges();
			break;
		CASE "counters":
			CreateCounters();
			break;
		CASE "dates":
			CreateDates();
			break;
		CASE "eventcache":
			CreateEventcache();
			break;
		CASE "families":
			CreateFamilies();
			break;
		CASE "favorites":
			CreateFavorites();
			break;
		CASE "gedcoms":
			CreateGedcoms();
			break;
		CASE "gedconf":
			CreateGedconf();
			break;
		CASE "individual_child":
			CreateIndividualChild();
			break;
		CASE "individual_spouse":
			CreateIndividualSpouse();
			break;
		CASE "individuals":
			CreateIndividuals();
			break;
		CASE "other":
			CreateOther();
			break;
		CASE "language":
			CreateLanguage();
			break;
		CASE "language_help":
			CreateLanguageHelp();
			break;
		CASE "log":
			CreateLog();
			break;
		CASE "media":
			CreateMedia();
			break;
		CASE "media_mapping":
			CreateMediaMapping();
			break;
		CASE "messages":
			CreateMessages();
			break;
		CASE "names":
			CreateNames();
			break;
		CASE "news":
			CreateNews();
			break;
		CASE "pages":
			CreatePages();
			break;
		CASE "placelinks":
			CreatePlaceLinks();
			break;
		CASE "places":
			CreatePlaces();
			break;
		CASE "privacy":
			CreatePrivacy();
			break;
		CASE "sources":
			CreateSources();
			break;
		CASE "statscache":
			CreateStatscache();
			break;
		CASE "users":
			CreateUsers();
			break;
	}
}

function RestartButton() {
	global $gm_lang, $DBHOST, $DBUSER, $DBPASS, $DBNAME, $TBLPREFIX;
	
	print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
	print "<input type=\"hidden\" name=\"step\" value=\"2\">";
	print "<input type=\"hidden\" name=\"DBHOST\" value=\"".$DBHOST."\">";
	print "<input type=\"hidden\" name=\"DBUSER\" value=\"".$DBUSER."\">";
	print "<input type=\"hidden\" name=\"DBPASS\" value=\"".$DBPASS."\">";
	print "<input type=\"hidden\" name=\"DBNAME\" value=\"".$DBNAME."\">";
	print "<input type=\"hidden\" name=\"TBLPREFIX\" value=\"".$TBLPREFIX."\">";
	print "<br />";
	print "<input type=\"submit\" value=\"Restart\">";
	print "</form>";
}

function addAdminUser($newuser, $msg = "added") {
	global $TBLPREFIX;

	$newuser["firstname"] = preg_replace("/\//", "", $newuser["firstname"]);
	$newuser["lastname"] = preg_replace("/\//", "", $newuser["lastname"]);
	$sql = "INSERT INTO ".$TBLPREFIX."users VALUES('".$newuser["username"]."','".$newuser["password"]."','".mysql_real_escape_string($newuser["firstname"])."','".mysql_real_escape_string($newuser["lastname"])."','".mysql_real_escape_string(serialize($newuser["gedcomid"]))."','".mysql_real_escape_string(serialize($newuser["rootid"]))."'";
	if ($newuser["canadmin"]) $sql .= ",'Y'";
	else $sql .= ",'N'";
	$sql .= ",'".mysql_real_escape_string(serialize($newuser["canedit"]))."'";
	$sql .= ",'".mysql_real_escape_string($newuser["email"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["verified"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["verified_by_admin"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["language"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["pwrequested"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["reg_timestamp"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["reg_hashcode"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["theme"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["loggedin"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["sessiontime"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["contactmethod"])."'";
	if ($newuser["visibleonline"]) $sql .= ",'Y'";
	else $sql .= ",'N'";
	if ($newuser["editaccount"]) $sql .= ",'Y'";
	else $sql .= ",'N'";
	$sql .= ",'".mysql_real_escape_string($newuser["default_tab"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["comment"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["comment_exp"])."'";
	if (isset($newuser["sync_gedcom"])) $sql .= ",'".mysql_real_escape_string($newuser["sync_gedcom"])."'";
	else $sql .= ",'N'";
	$sql .= ",'".mysql_real_escape_string($newuser["relationship_privacy"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["max_relation_path"])."'";
	if (isset($newuser["auto_accept"]) && $newuser["auto_accept"]==true) $sql .= ",'Y'";
	else $sql .= ",'N'";
	$sql .= ")";
	$res = mysql_query($sql);
	if ($res) return true;
}

function ShowProgress() {
	global $step;
	print "<div style=\"border: 1px solid #FF0000; width: 700px;\">";
	print "<img src=\"images/progressbar.png\" width=\"".$step."00px\" height=\"10px\" alt=\"Progress\"/> ";
	print "</div>";
}

function LoadLanguage() {
	global $gm_lang;
	
	$gm_lang = array();
	// Load the language
	if (file_exists("install_lang.txt")) {
		$lines = file("install_lang.txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			$data[0] = substr(trim($data[0]), 1);
			$data[1] = substr(trim($data[1]), 0, -1);
			$gm_lang[$data[0]] = $data[1];
		}
	}
}

function StoreLanguage($storelang) {
	global $TBLPREFIX, $language_settings;
	
	$output = array();
	$output["lang"] = true;
	$output["help"] = true;
	
	// NOTE: Store the chosen languages
	if (file_exists("../languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("../languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) WriteToLog($line, "E");
			else {
				$data[0] = substr(trim($data[0]), 1);
				$data[1] = substr(trim($data[1]), 0, -1);
				$sql = "UPDATE ".$TBLPREFIX."language SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='install' WHERE lg_string = '".$data[0]."' LIMIT 1";
				if (!$result = mysql_query($sql)) {
					$output["lang"] = false;
					WriteToLog("Could not add language string ".$line." for language ".$storelang." to table ", "W");
				}
			 }
	    }
	}
	if (file_exists("../languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("../languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) WriteToLog($line, "E");
			else {
				$data[0] = substr(trim($data[0]), 1);
				$data[1] = substr(trim($data[1]), 0, -1);
				$sql = "UPDATE ".$TBLPREFIX."language_help SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='install' WHERE lg_string = '".$data[0]."' LIMIT 1";
				if (!$result = mysql_query($sql)) {
					$output["help"] = false;
					WriteToLog("Could not add language help string ".$line." for language ".$storelang." to table ", "W");
				}
			}
		}
	}
	return $output;
}

function RemoveLanguage($removelang) {
	global $TBLPREFIX;
	
	if ($removelang != "english") {
		// Drop the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language DROP lg_".$removelang;
		$result = mysql_query($sql);
		
		// Add the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language ADD lg_".$removelang." TEXT NOT NULL";
		$result = mysql_query($sql);
		
		// NOTE: Drop the column help text
		$sql = "ALTER TABLE ".$TBLPREFIX."language_help DROP lg_".$removelang;
		$result = mysql_query($sql);
		
		// Add the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language_help ADD lg_".$removelang." TEXT NOT NULL";
		$result = mysql_query($sql);
	}
	return $result;
}

/**
 * Write a Log record to the database
 *
 * The function writes the records that are logged for
 * either the Syetem Log, the Gedcom Log or the Search
 * Log. 
 *
 * @author	Genmod Development Team
 * @param		string	$LogString	Message to be stored
 * @param		string	$type		Type of record:
 *									I = Information
 *									W = Warning
 *									E = Error
 */
function WriteToLog($LogString, $type="I") {
	global $TBLPREFIX;
     
	// -- Remove the " from the logstring, as this disturbs the export
	$LogString = str_replace("\"", "'", $LogString);
	
	$sql = "INSERT INTO ".$TBLPREFIX."log VALUES('".$type."','S','".time()."', '".$_SERVER['REMOTE_ADDR']."', 'install', '".addslashes($LogString)."', '')";
	$res = mysql_query($sql);
}

/**
 * Store CONFIG array
 *
 * this function will store the <var>$CONFIG_PARMS</var> array in the config.php
 * file.  The config.php file is parsed in session.php to create the system variables
 * with every page request.
 * @see session.php
 */
function StoreConfig() {
	global $CONFIG_PARMS, $gm_lang;

	//-- Determine which values must be written as false/true
	$boolean = array("DBPERSIST", "GM_STORE_MESSAGES", "GM_SIMPLE_MAIL", "USE_REGISTRATION_MODULE", "REQUIRE_ADMIN_AUTH_REGISTRATION", "ALLOW_USER_THEMES", "ALLOW_CHANGE_GEDCOM", "ALLOW_REMEMBER_ME", "CONFIGURED");
	
	//-- First lines
	$configtext = "<"."?php\n";
	$configtext .= "if (preg_match(\"/\Wconfig.php/\", \$_SERVER[\"SCRIPT_NAME\"])>0) {\n";
	$configtext .= "print \"Got your hand caught in the cookie jar.\";\n";
	$configtext .= "exit;\n";
	$configtext .= "}\n";
	$configtext .= "//--START SITE CONFIGURATIONS\n";
	$configtext .= "\$CONFIG_PARMS = array();\n";
	
	//-- Scroll through the site configs
	foreach($CONFIG_PARMS as $indexval => $CONFIG) {
		$configtext .= "\$CONFIG = array();\n";
		//-- Scroll through the site parms
		foreach($CONFIG as $key=>$conf) {
			//-- If boolean, add true or false
			if (in_array($key, $boolean)) {
				$configtext .= "\$CONFIG[\"".$key."\"] = ";
				if ($conf) $configtext .= "true;\n";
				else $configtext .= "false;\n";
			}
			//-- If not boolean, add the value in quotes
			else $configtext .= "\$CONFIG[\"".$key."\"] = \"".$conf."\";\n";
		}
		//-- add last line per config
		$configtext .= "\$CONFIG_PARMS[\"".$indexval."\"] = \$CONFIG;\n";
	}
	//-- Add last lines
	$configtext .= "require_once(\$GM_BASE_DIRECTORY.\"includes/session.php\")\n"."?".">";
	
	//-- Store the config file
	if (file_exists("../config.php")) {
		if (file_exists("../config.old") && file_is_writeable("../config.old")) unlink("../config.old");
		if (file_is_writeable("../config.old")) copy("../config.php", "../config.old");
	}
	$fp = fopen("../config.php", "wb");
	if (!$fp) {
		return false;
		}
	else {
		fwrite($fp, $configtext);
		fclose($fp);
		return true;
	}
}
// This functions checks if an existing file is physically writeable
// The standard PHP function only checks for the R/O attribute and doesn't
// detect authorisation by ACL.
function file_is_writeable($file) {
	$err_write = false;
	$handle = @fopen($file,"r+");
	if	($handle)	{
		$i = fclose($handle);
		$err_write = true;
	}
	return($err_write);
}
?>
