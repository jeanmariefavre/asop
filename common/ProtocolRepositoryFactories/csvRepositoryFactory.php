<?php
require_once("common/CsvRepository.php") ;

function /*IRepository*/ createRepository($protocol,$repname,$config,$logger) {
  $logdir=$config["ROOT_DIR"]."/".$config["LOG_DIR"] ;
  $csvdir=$config["ROOT_DIR"]."/".$config["LOCAL_CSV_ROOT_DIRECTORY"] ;
  $repository = new CsvReadOnlyRepository( 
                       $config["ROOT_URL"].$protocol.'$'.$repname."/",
                       $csvdir,
                       $logdir."/repository-".$protocol.'$'.$repname.".txt"  ) ;
  $logger->log("csv::createRepository:: repository successfully opened") ;
  return $repository ;
}
