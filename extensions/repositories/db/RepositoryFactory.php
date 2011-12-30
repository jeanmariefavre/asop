<?php  defined('_SOS') or die("No direct access") ;
require_once(ABSPATH_LIB."Database.php") ;
require_once("DatabaseRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$logger) { 
  $dbserver=DATABASE_SERVER ;
  $user=DATABASE_SOCIAL_USER ;
  $password=DATABASE_SOCIAL_PASSWORD ;
  $databaselog = "db-".$repname.".txt" ;
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
                         URL_REPOSITORY.$protocol.'$'.$repname."/",
                         $db,
                         $introspector, 
                         "repository-".$protocol.'$'.$repname.".txt"  ) ;
    return $repository ;
  }
}
  