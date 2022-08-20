<?php
/**
 * MySQL layer for Genmod
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
 * $Id: mysqldb_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Database
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class MysqlDb {
	
	public $classname = "MysqlDb";	// Name of this class
	
	public $connected = null;		// The result of MakeConnection. Set in childclass DbLayer
	public $connection = null;		// Acquired connection handle
	
	public $sqlerror = false;		// Set to true if a query failed and error reporting is on
	public $sqlerrordesc = null;	// Description of the error
	public $sqlerrorno = null;		// Number of the error
	public $sqlquery = null;		// SQL query that went wrong
	public $sqlfile	= null;			// If there was an error in NewQuery, the file where that occured is here
	public $sqlline	= null;			// Also the line number
	
	public function __construct() {
	}
	
	public function MakeConnection() {
		
		if (DBUSER == "" || DBPASS == "" || DBNAME == "" || DBHOST == "") return false;
		
		if (is_null($this->connected)) {
			if (DBPERSIST) {
				$this->connection = ($GLOBALS["___mysqli_ston"] = mysqli_connect(DBHOST, DBUSER, DBPASS));
				if (((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . constant('DBNAME')))) {
					return true;
				}
				else return false;
			}
			else {
				$this->connection = ($GLOBALS["___mysqli_ston"] = mysqli_connect(DBHOST, DBUSER, DBPASS));
				if (((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . constant('DBNAME')))) {
					return true;
				}
				else return false;
			}
		}
	}
	
	/**
	 * @todo Add error handler
	**/
	public function Query($sql, $noreport) {
		global $TOTAL_QUERIES, $QUERY_EXECTIME;
		
		if (DebugCollector::$show) Debugcollector::OutputCollector($sql, "query");
		if (!isset($QUERY_EXECTIME)) $QUERY_EXECTIME = 0;
		
		// NOTE: Execute the query
		$res = @mysqli_query($GLOBALS["___mysqli_ston"], "SET NAMES 'latin1'");
		$times = GetMicrotime();
		$res = @mysqli_query($GLOBALS["___mysqli_ston"], $sql);
		$timee = GetMicrotime();
		if (!$res && !$noreport) {
			$this->HandleError($sql);
			WriteToLog("Query-&gt; Error occured: ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false))."<br />Query: ".htmlentities($sql),"E","S");
//			print "Query-> Error occured: ".mysql_error()."<br />Query: ".htmlentities($sql);
		}
		// Increase the querytime
		$QUERY_EXECTIME = $QUERY_EXECTIME + $timee - $times;
		
		// NOTE: Increase the query counter
		$TOTAL_QUERIES++;
		
		// NOTE: Return the result
		return $res;
	}
	
	public function EscapeQuery($text) {
		return ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $text) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""));
	}
	
	public function HandleError($sql) {
		$this->sqlerror = true;
		$this->sqlerrordesc = ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
		$this->sqlerrorno = ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false));
		$this->sqlquery = $sql;
		$debugtrace = debug_backtrace();
		foreach ($debugtrace as $item => $trace) {
			if ($trace["function"] == "NewQuery" && array_search($sql, $trace["args"]) !== false) $key = $item;
		}
		if (isset($key)) $this->sqlfile = $debugtrace[$key]["file"];
		if (isset($key)) $this->sqlline = $debugtrace[$key]["line"];
	}
}

class Result {
	
	public $classname = "Result";
	public $result = null;
		
	public function NumRows() {
		$rows = @mysqli_num_rows($this->result);
		//if ($rows === null) {
		//    return $this->mysqlRaiseError();
		//}
		return $rows;
	}
	
	public function FetchAssoc() {
		$rows = @mysqli_fetch_assoc($this->result);
		return $rows;
	}
	
	public function FreeResult() {
		@((mysqli_free_result($this->result) || (is_object($this->result) && (get_class($this->result) == "mysqli_result"))) ? true : false);
	}
	
	public function FetchRow() {
		$rows = @mysqli_fetch_row($this->result);
		return $rows;
	}
	
	public function InsertID() {
		global $DBCONN;
		
		$rows = @((is_null($___mysqli_res = mysqli_insert_id($DBCONN->connection))) ? false : $___mysqli_res);
		return $rows;
	}
	
	public function AffectedRows() {
		global $DBCONN;
		
		$rows = @mysqli_affected_rows($DBCONN->connection);
		return $rows;
	}
}
?>
