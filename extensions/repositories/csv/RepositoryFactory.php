<?php
require_once("CsvRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$logger) {
  $repository = new CsvReadRepository( 
                       URL_REPOSITORY.$protocol.'$'.$repname."/",
                       ABSPATH_CSV_ROOT,
                       "repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("csv::createRepository:: repository successfully opened") ;
  return $repository ;
}
