<?php
require_once '../core/Constants.php' ;
require_once ABSPATH_CONFIG.'config-srdf.php' ;
require_once ABSPATH_LIB.'RDF.php' ;

$repositoryname = $_GET["repositoryname"] ;

$arc2config = createArc2Config(
                SRDF_DATABASE_SERVER,
                SRDF_DATABASE_NAME,
                SRDF_DATABASE_USER,
                SRDF_DATABASE_PASSWORD,
                SRDF_STORE_PREFIX.$repositoryname,
                array ("soo" => SRDF_SOO_URI) 
              ) ;     
$arc2config['endpoint_read_key'] = SRDF_SPARQL_READ_API_KEY ;
$arc2config['endpoint_write_key'] = SRDF_SPARQL_WRITE_API_KEY ;
$arc2config['endpoint_features'] = explode(' ',SRDF_SPARQL_FEATURES) ;
$arc2config['endpoint_timeout']= 60 ; /* not implemented in ARC2 preview */

/* instantiation */
$ep = ARC2::getStoreEndpoint($arc2config);

if (!$ep->isSetUp()) {
  $ep->setUp(); /* create MySQL tables */
}
$ep->go();
