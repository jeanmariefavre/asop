<?php  defined('_SOS') or die("No direct access") ;
require_once(ABSPATH_LIB."Database.php") ;
require_once("DatabaseRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$logger) { 
  $dbserver=DATABASE_SERVER ;
  $user=DATABASE_SOCIAL_USER ;
  $password=DATABASE_SOCIAL_PASSWORD ;
  $databaselog = ABSPATH_LOGS."db-".$repname.".txt" ;
  $logger->log('db::createRepository:: opening database') ;
  $db=new Database(new DatabaseAccount($repname,$user,$password,$dbserver,"mysql","3306"),$databaselog);
  if ($db->getError()) {
    $logger->log('db::createRepository:: cannot open database "'.$repname.'" with user "'.$user.'"') ;
    return NULL ;
  } else {
    $logger->log('db::createRepository:: new MysqlIntrospector') ;
    $introspector = new MysqlIntrospector($db) ;
    $logger->log('db::createRepository::  new DatabaseSchemaFixedRepository') ;
    $repository = new DatabaseModelFixedRepository(
                         URL_REPOSITORY.$protocol.'$'.$repname."/",
                         $db,
                         $introspector, 
                         ABSPATH_LOGS."repository-".$protocol.'$'.$repname.".txt"  ) ;
    return $repository ;
  }
}
  