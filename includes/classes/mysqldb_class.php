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
 * $Id$
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
				$this->connection = mysql_pconnect(DBHOST,DBUSER,DBPASS);
				if (mysql_select_db(DBNAME)) {
					return true;
				}
				else return false;
			}
			else {
				$this->connection = mysql_connect(DBHOST,DBUSER,DBPASS);
				if (mysql_select_db(DBNAME)) {
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
		
		$times = GetMicrotime();
		$res = @mysql_query($sql);
		$timee = GetMicrotime();
		if (!$res && !$noreport) {
			$this->HandleError($sql);
			WriteToLog("Query-> Error occured: ".mysql_error()."<br />Query: ".htmlentities($sql),"E","S");
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
		return mysql_real_escape_string($text);
	}
	
	public function HandleError($sql) {
		$this->sqlerror = true;
		$this->sqlerrordesc = mysql_error();
		$this->sqlerrorno = mysql_errno();
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
		$rows = @mysql_num_rows($this->result);
		//if ($rows === null) {
		//    return $this->mysqlRaiseError();
		//}
		return $rows;
	}
	
	public function FetchAssoc() {
		$rows = @mysql_fetch_assoc($this->result);
		return $rows;
	}
	
	public function FreeResult() {
		@mysql_free_result($this->result);
	}
	
	public function FetchRow() {
		$rows = @mysql_fetch_row($this->result);
		return $rows;
	}
	
	public function InsertID() {
		$rows = @mysql_insert_id($this->result);
		return $rows;
	}
	
	public function AffectedRows() {
		$rows = @mysql_affected_rows($this->result);
		return $rows;
	}
}
?>
