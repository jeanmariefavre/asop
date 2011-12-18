<?php
require_once("common/FileSystemRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$config,$logger) {
  $logdir=$config["ROOT_DIR"]."/".$config["LOG_DIR"] ;
  $rootdirectory=$config["LOCAL_FS_ROOT_DIRECTORY"] ;
  assert('strlen($rootdirectory)>=1') ;
  assert('isReadableDirectory($rootdirectory)') ;
  $rooturl=$config["LOCAL_FS_ROOT_URL"] ;
  assert('strlen($rootdirectory)>=1') ;
  assert('strlen($rooturl)>=1') ;
  $logger->log("fs::createRepository:: creating FileSystemQueryOnlyRepository") ;                 
  $repository = new FileSystemQueryOnlyRepository( 
                         $config["ROOT_URL"].$protocol.'$'.$repname."/",
                         $rootdirectory,
                         $rooturl,
                         $logdir."/repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("fs::createRepository:: repository successfully opened") ;                 
  return $repository ;
}
