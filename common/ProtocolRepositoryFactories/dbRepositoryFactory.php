<?php
require_once("common/Database.php") ;
require_once("common/DatabaseRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$config,$logger) { 
  $logdir=$config["ROOT_DIR"]."/".$config["LOG_DIR"] ; 
  $dbserver=$config["DATABASE_SERVER"] ;
  $user=$config["DATABASE_SOCIAL_USER"] ;
  $password=$config["DATABASE_SOCIAL_PASSWORD"] ;
  $databaselog = $logdir."/db-".$repname.".txt" ;
  $logger->log('db::createRepository:: opening database') ;
  $db=new Database("mysql",$dbserver,$repname,$user,$password,$databaselog);
  if ($db->getError()) {
    $logger->log('db::createRepository:: cannot open database "'.$repname.'" with user "'.user.'"') ;
    return NULL ;
  } else {
    $logger->log('db::createRepository:: new MysqlIntrospector') ;
    $introspector = new MysqlIntrospector($db) ;
    $logger->log('db::createRepository::  new DatabaseSchemaFixedRepository') ;
    $repository = new DatabaseModelFixedRepository(
                         $config["ROOT_URL"].$protocol.'$'.$repname."/",
                         $db,
                         $introspector, 
                         $logdir."/repository-".$protocol.'$'.$repname.".txt"  ) ;
    return $repository ;
  }
}
  