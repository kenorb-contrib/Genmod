<?php
if (preg_match("/\Wconfig.php/", $_SERVER["SCRIPT_NAME"])>0) {
$INTRUSION_DETECTED = true;
}
//--START SITE CONFIGURATIONS
$CONFIG_PARMS = array();
$CONFIG = array();
$CONFIG["DBHOST"] = 'localhost';
$CONFIG["DBUSER"] = 'gmmain';
$CONFIG["DBPASS"] = 'gmmain';
$CONFIG["DBNAME"] = 'genmodmain-test';
$CONFIG["INDEX_DIRECTORY"] = './index/';
$CONFIG["DBPERSIST"] = true;
$CONFIG["TBLPREFIX"] = 'gm_';
$CONFIG["SERVER_URL"] = 'http://gmmain.sjouke.nl:8080/';
$CONFIG["LOGIN_URL"] = '';
$CONFIG["SITE_ALIAS"] = 'http://localhost/gmmain/,http://127.0.0.1/gmmain/,http://gmmain.sjouke.net/';
$CONFIG["GM_SESSION_SAVE_PATH"] = '';
$CONFIG["GM_SESSION_TIME"] = '7200';
$CONFIG["CONFIGURED"] = true;
$CONFIG_PARMS["http://gmmain.sjouke.nl:8080/"] = $CONFIG;
$CONFIG = array();
$CONFIG["DBHOST"] = 'localhost';
$CONFIG["DBUSER"] = 'gmmain';
$CONFIG["DBPASS"] = 'gmmain';
$CONFIG["DBNAME"] = 'genmodmain-test';
$CONFIG["INDEX_DIRECTORY"] = './index2/';
$CONFIG["DBPERSIST"] = false;
$CONFIG["TBLPREFIX"] = 'genmod_';
$CONFIG["SERVER_URL"] = 'http://83.98.248.70/gmmain/';
$CONFIG["LOGIN_URL"] = '';
$CONFIG["SITE_ALIAS"] = '';
$CONFIG["GM_SESSION_SAVE_PATH"] = './index/';
$CONFIG["GM_SESSION_TIME"] = '7200';
$CONFIG["CONFIGURED"] = true;
$CONFIG_PARMS["http://83.98.248.70/gmmain/"] = $CONFIG;
require_once("includes/session.php")
?>