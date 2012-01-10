<?php
require_once("SRDFRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$logger) {
  $rootdirectory=ABSPATH_LOCAL_FS_ROOT ;
  assert('strlen($rootdirectory)>=1') ;
  assert('isReadableDirectory($rootdirectory)') ;
  $rooturl=URL_LOCAL_FS_ROOT ;
  assert('strlen($rootdirectory)>=1') ;
  assert('strlen($rooturl)>=1') ;
  $logger->log("fs::createRepository:: creating FileSystemQueryOnlyRepository") ;                 
  $repository = new RDFBasedRepository( 
                         URL_REPOSITORY.$protocol.'$'.$repname."/",
                         $rootdirectory,
                         $rooturl,
                         "repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("fs::createRepository:: repository successfully opened") ;                 
  return $repository ;
}
