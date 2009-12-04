<?php
if (preg_match("/\Wconfig.php/", $_SERVER["SCRIPT_NAME"])>0) {
$INTRUSION_DETECTED = true;
}
//--START SITE CONFIGURATIONS
$CONFIG_PARMS = array();
$CONFIG = array();
$CONFIG["DBHOST"] = 'localhost';
$CONFIG["DBUSER"] = 'gminstall';
$CONFIG["DBPASS"] = 'gminstall';
$CONFIG["DBNAME"] = 'gminstall';
$CONFIG["INDEX_DIRECTORY"] = './index/';
$CONFIG["DBPERSIST"] = true;
$CONFIG["TBLPREFIX"] = 'gm_';
$CONFIG["SERVER_URL"] = 'http://gminstall.sjouke.nl/';
$CONFIG["LOGIN_URL"] = '';
$CONFIG["GM_SESSION_SAVE_PATH"] = '';
$CONFIG["GM_SESSION_TIME"] = '7200';
$CONFIG["CONFIGURED"] = true;
$CONFIG["SITE_ALIAS"] = '';
$CONFIG_PARMS["http://gminstall.sjouke.nl/"] = $CONFIG;
require_once("includes/session.php");
?>