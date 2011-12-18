<?php

require_once('Logger.php') ;

class Database {
  public /*Logger!*/ $logger ;
  public /*String!*/ $protocol ;
  public /*String!*/ $host ;
  public /*String!*/ $dbname ;
  public /*String!*/ $user ;
  public /*PDO?*/    $pdo ;    // NULL if the connection is not successful
  public /*String!*/ $error ;

  public function log($message) {
    $this->logger->log($message) ;
  }
  
  public function /*String*/ getError() {
    return $this->error;
  }

  protected function /*void*/ clearError() {
    unset($this->error) ;
  }

  protected function /*void*/ error(/*String!*/ $message) {
    $this->error = $message ;
    $this->log("ERROR: ".$message) ;
    echo "ERROR with database '".$this->dbname."': ".$message ;
    if ($this->pdo) print_r($this->pdo->errorInfo()) ;
  }

  public function queryAll($query, $singleColumn=NULL) {
    $this->log("QUERY: ".$query) ;
    $qr = $this->pdo->query($query) ;
    if ($qr===FALSE) {
      $this->error("Error with query ".$query) ;
      return array() ;
    } else {
      $this->clearError() ;
      $map = $qr->fetchAll(PDO::FETCH_ASSOC) ;
      if (isset($singleColumn)) {
        $result = array() ;
        foreach ($map as $row) {
          $result[] = $row[singleColumn] ;
        }
        $this->log("RESULT: (1 row) ".implode(",",$result)) ;
        return $result ;
      } else {
        $this->log("RESULT: (".count($map)." row(s) )" ) ;
        return $map ;
      }
    }
  }

  public function execute($query) {
    $this->log("EXECUTE: ".$query) ;
    $result = $this->pdo->exec($query) ;
    if ($result===FALSE) {
      $this->error("Error with execute ".$query) ;
      return FALSE ;
    } else {
      $this->clearError() ;
      $this->log("RESULT: ".$result) ;
      return $result ;
    }
  }

  public function __construct($protocol,$host,$dbname,$user,$password,$logfile="") {
   $this->logger=new Logger($logfile) ;
   $this->protocol = $protocol ;
   $this->host = $host ;
   $this->dbname = $dbname ;
   $this->user  = $user  ;
   try {
    $dsn = $protocol.":"."host=".$host.";dbname=".$dbname ;
    $this->pdo = new PDO($dsn, $user, $password) ;
   } catch(PDOException $e) {
     $this->error("Database '".$dbname."' cannot be opened with user '".$user."@".$host."'. DSN=$dsn") ;
     print_r($e) ;    
     $this->pdo = NULL ;
   }
  }
  
  
}










interface DatabaseIntrospector {
  public function /*List*<String!>!*/ getTableNames() ;
  public function /*List+<String!>?*/ getTablePrimaryKey(/*String!*/ $tablename) ;
  public function /*List+<String!>?*/ getColumnNames(/*String!*/ $tablename) ;
  //public function /*String?*/ getColumnType(/*String!*/$tablename, /*String!*/$columnname) ;
}



/*

HERE IS SOME NOTES TAKEN FROM THE WEB THAT EXPLAIN HOW TO ACCESS METADATA INFORMATION
MySQL - dev.mysql.com/doc/refman...show-tables.html
SHOW [FULL] TABLES [FROM db_name] [LIKE 'pattern']



But in MySQL 5 you can use the following:
select * from information_schema.tables;

Oracle
SELECT * FROM dba_tables
(all_views for views, etc.)
select * from user_tables;
select * from all_tables;

Postgres
SELECT * FROM pg_tables

MSSQL
SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'

DB2 it is:
SELECT * FROM SYSCAT.TABLES

MS SQL Server
SELECT * FROM sysobjects WHERE type = 'U' 

SQL Server you can do the following:

select * from information_schema.tables;

In Oracle you can use any of the following statements:

*/

class MysqlIntrospector implements DatabaseIntrospector {
  protected /*Database!*/ $db ;
  protected /*Map*<String!,List+<String!>?*/ $tableNamesAndKeysNames ; // loaded once
  protected /*Map*<String!,List+<String!>!*/ $tableColumnNames ; // loaded incrementally
  
  public function /*Map*<String!,List1..*<String!>?*/ getTableNamesAndKeysNames() {
    if (isset($tableNamesAndKeysNames)) {
      return $this->tableNamesAndKeysNames ;
    } else {
      $query  = "SELECT `table_name`,`column_name`,`ordinal_position`"
                . " FROM information_schema.key_column_usage"
                . " WHERE `constraint_name`='PRIMARY' AND table_schema='". $this->db->dbname ."'" ;
      $rows = $this->db->queryAll($query) ; 
      $this->tableNamesAndKeysNames = array() ;
      foreach ($rows as $row) {
        $this->tableNamesAndKeysNames[$row["table_name"]][$row["ordinal_position"]-1] = $row["column_name"] ;
      }
      return $this->tableNamesAndKeysNames ;
    }
  }
  
  public function /*List*<String!>!*/ getTableNames() {
    return array_keys($this->getTableNamesAndKeysNames()) ;
  }
 
  // return an empty list if the table is not existing
  public function /*List*<String!>!*/ getTablePrimaryKey(/*String+!*/ $tablename) {
    assert($tablename!="") ;
    $r=$this->getTableNamesAndKeysNames() ;
    if (isset($r[$tablename])) {
      return $r[$tablename] ;
    } else {
      return array() ;
    }
  }  
  
  // return an empty list if the table is not existing
  public function /*List*<String!>!*/ getColumnNames(/*String+!*/ $tablename) {
    assert($tablename!="") ;
    if (isset($this->tableColumns[$tablename]) ) {
      return $this->tableColumns[$tablename] ;
    } else {    
      $query = "SELECT `column_name`,`ordinal_position`"
               . " FROM information_schema.columns"
               . " WHERE `table_schema`='". $this->db->dbname ."'"
               . " AND   `table_name`  ='". $tablename ."'" ;
      $rows = $this->db->queryAll($query) ;
      if (count($rows)) {
        $this->tableColumns[$tablename] = array() ;
        foreach ($rows as $row) {
          $this->tableColumns[$tablename][$row["ordinal_position"]-1] = $row["column_name"] ;
        }
        return $this->tableColumns[$tablename] ;
      } else {
        return array() ;
      }
    }
  }
  
  // Foreign keys
  // SELECT `CONSTRAINT_NAME`,`TABLE_SCHEMA`,`TABLE_NAME`,`COLUMN_NAME`, 
  //        `REFERENCED_TABLE_SCHEMA`,`REFERENCED_TABLE_NAME`,`REFERENCED_COLUMN_NAME` 
  // FROM `KEY_COLUMN_USAGE` 
  // WHERE `REFERENCED_TABLE_NAME` IS NOT NULL 
    
  public function __construct( /*Database!*/ $database ) {
    assert(isset($database)) ;
    $this->db = $database ;
  }
}


