<?php
require_once("FileSystemRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$logger) {
  $rootdirectory=ABSPATH_LOCAL_FS_ROOT ;
  assert('strlen($rootdirectory)>=1') ;
  assert('isReadableDirectory($rootdirectory)') ;
  $rooturl=URL_LOCAL_FS_ROOT ;
  assert('strlen($rootdirectory)>=1') ;
  assert('strlen($rooturl)>=1') ;
  $logger->log("fs::createRepository:: creating FileSystemQueryRepository") ;                 
  $repository = new FileSystemQueryRepository( 
                         URL_REPOSITORY.$protocol.'$'.$repname."/",
                         $rootdirectory,
                         $rooturl,
                         ABSPATH_LOGS."repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("fs::createRepository:: repository successfully opened") ;                 
  return $repository ;
}
