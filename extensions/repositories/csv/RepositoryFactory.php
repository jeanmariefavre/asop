<?php
require_once("CSVRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$logger) {
  $repository = new CSVReadRepository( 
                       URL_REPOSITORY.$protocol.'$'.$repname."/",
                       ABSPATH_CSV_ROOT,
                       ABSPATH_LOGS."repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("csv::createRepository:: repository successfully opened") ;
  return $repository ;
}
