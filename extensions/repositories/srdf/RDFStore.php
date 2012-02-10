<?php  defined('_SOS') or die("No direct access") ;

// setup for the different ARC2 scripts.
require_once SRDF_ARC2_LIBRARY ;


function createARC2Config(        
    /*Hostname!*/ $server, 
    /*String!*/   $dbname, 
    /*String!*/   $user,
    /*String!*/   $passwd, 
    /*String!*/   $store, 
    /*Map*<String!,URI!>?*/ $additionalPrefixes) {
  // Compute the list of prefixes available
  $defaultprefixes = array(
      'xsd'      => 'http://www.w3.org/2001/XMLSchema#',
      'rdf'      => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'rdfs'     => 'http://www.w3.org/2000/01/rdf-schema#',
      'owl'      => ''
  ) ;
  if (isset($additionalPrefixes)) {
    $prefixes = array_merge($defaultprefixes,$additionalPrefixes) ;
  } else {
    $prefixes = $defaultprefixes ;
  }
   
  // Create the ARC2 configuration
  $config = array(
      /* db */
      'db_host'          => $server,
      'db_name'          => $dbname,
      'db_user'          => $user,
      'db_pwd'           => $passwd,
   
      /* store */
      'store_name'       => $store,
   
      /* stop after 100 errors */
      'max_errors'       => 100,
   
      /* prefixes */
      'ns'               => $prefixes
    );
  return $config ;                         
}


class RDFStore {
  protected /*Logger!*/        $logger ;        /* a logger where to trace warning and errors */

  // arc2 stuff  
  protected /*"ARC2Config"!*/  $arc2config ;  /* The configuration for ARC2 library */
  protected /*ARC2_Store!*/    $rdfstore ;    /* The RDF store containing all information */
  protected /*ARC2_Resource!*/    $resource ;    /* Used as a placeholder to access to ressource */
  
  public function log($msg) {
    $this->logger->log($msg) ;
  }
  
  
  //-----------------------------------------------------------------------------
  //  Helpers to use arc2 in a more abstract way
  //-----------------------------------------------------------------------------
  
  protected function /*void*/ setupStore(
                                  /*Hostname!*/ $server, 
                                  /*String!*/   $dbname, 
                                  /*String!*/   $user,
                                  /*String!*/   $passwd, 
                                  /*String!*/   $store, 
                                  /*Map*<String!,URI!>?*/ $additionalPrefixes) {
    $this->arc2config = createARC2Config($server,$dbname,$user,$passwd,$store,$additionalPrefixes) ;
    // Get a reference to the store
    $this->rdfstore = ARC2::getStore($this->arc2config);
    $this->checkErrors("Cannot get the RDF Store") ;
    if (!$this->rdfstore->isSetUp()) {
      $this->rdfstore->setUp();
      $this->checkErrors("Cannot set up the RDF Store") ;
    }
    
    // Create a resource placeholder (see ARC2 wiki)
    // This space will be used by the various methods to access to the store
    $this->resource = ARC2::getResource($this->arc2config) ;
    $this->resource->setStore($this->rdfstore) ;
  }
  
  protected function checkErrors( $msg, $die = true) {
    // check if there are some errors in the store or in the resource
    $errs = $this->rdfstore->getErrors() ;
    if (! $errs && isset($this->resource)) {
      $errs = $this->resource->getErrors() ; 
    }
    if ($errs) {
      $msg = "<b>SRDFStore::checkErrors - ERROR:</b>$msg (from ARC2)<br/><ul>" ;
      foreach ($errs as $err) {
        $msg .= "<li>".$err."</li>" ;
      }
      $msg .= "</ul>" ;
      $this->log($msg) ;
      ! $die || die("SRDFStore::checkErrors - Fatal error (see log for details)") ;
    }
  }

  // <PropertyExpression> ::= 
  //     [ "~" ] <PropertyName> [ "?" | "!" | "*" | "+" ] 
  //
  // Returns a PropertyDescription that is an array of the form
  //   "property"  : string
  //   "inverse"   : boolean
  //   "card"      : ?|!|*|+  [0..1]
  //   "optional"  : boolean  [0..1]
  //   "multiple"  : boolean  [0..1]
  protected function /*PropertyDescription!*/ parsePropertyExpression(/*ProperyExpression*/ $pexpr){
    $result = array() ;
    
    $firstchar = substr($pexpr,0,1) ;
    $lastchar = substr($pexpr,-1,1) ;
    $result['inverse'] = ($firstchar=='~') ;
    if ($result['inverse']) {
      $pexpr = substr($pexpr,1) ;
    }
    switch ($lastchar) {
      case '?':
        $result['optional'] = true ;
        $result['multiple'] = false ;
        $result['card'] = '?' ;
        break ;
      case '!':
        $result['optional'] = false ;
        $result['multiple'] = false ;
        $result['card'] = '!' ;
        break ;
      case '*':
        $result['optional'] = true ;
        $result['multiple'] = true ;
        $result['card'] = '*' ;
        break ;
      case '+':
        $result['optional'] = false ;
        $result['multiple'] = true ;
        $result['card'] = '+' ;
        break ;
    }
    if (isset($result['card'])) {
      $pexpr = substr($pexpr,0,strlen($pexpr)-1) ;
    }
    assert(strlen($pexpr)>=3) ;
    $result['property']=$pexpr ; 
    return $result ;
  } 
  
  // Parse a property set expression, that is a sequence of PropertyExpression separated by some spaces
  // return a map of description, the first element being the property expression  
  protected function /*Map*<PropertyExpression!,PropertyDescription!>!*/ parsePropertySetExpression(
                                                                           /*PropertySetExpression*/ $psexpr){
    $result = array() ;
    /*List*<PropertyExpression!>!*/ $properties = explode(' ',$psexpr) ;
    foreach ($properties as $pexpr) {
      if (strlen($pexpr)>=1) {
        $pdescr = $this->parsePropertyExpression($pexpr) ;
        $result[$pexpr] = $pdescr ;
      }
    }
    return $result ;
  }
  
  
  // Check for the existence of a triplet in the RDF store
  protected function /*boolean*/ isItFact(  /*RDFId!*/ $subject, 
                                            /*RDFId!*/ $predicate,
                                            /*RDFId!*/ $object ) {
    $query = 'ASK { '.$subject.' '.$predicate.' '.$object.' }' ;
    // execute the query
    $this->log('RDFStore:executeQuery '.$query) ;
    $result = $this->rdfstore->query($query,'raw') ;
    $this->checkErrors("Error executing SPARQL query $query") ;
    return ($result?true:false) ;
  }
  
  protected function /*boolean*/ isOfType(  /*RDFId!*/ $subject,
                                            /*RDFId!*/ $type ) {
    return $this->isItFact($subject,'rdf:type',$type) ;
  }
    
  // The value returned depends on the cardinality
  //   prop?  => String?
  //   prop!  => String!
  //   prop*  => Set*<String!>!
  //   prop+  => Set+<String!>!
  //   prop   => Set*<String!>!
  // PropertyValue ::= String! | Set*<String!>!
  public function /*PropertyValue?*/ 
                         evalPropertyExpression( /*RDFId*/ $objecturi,
                                          /*ProperyExpression*/ $pexpr ) {
    /*PropertyDescription!*/ $propdescr = $this->parsePropertyExpression($pexpr) ;
    // build the query according to the fact that the property is direct or inverse
    if ($propdescr['inverse']) {
      $query = 'SELECT DISTINCT ?x WHERE { ?x '.$propdescr['property'].' '.$objecturi.' }' ;
    } else {
      $query = 'SELECT DISTINCT ?x WHERE { '.$objecturi.' '.$propdescr['property'].' ?x }' ;
    }

    // execute the query
    $this->log('RDFStore:executeQuery '.$query) ;
    $rows = $this->rdfstore->query($query, 'rows') ;
    $this->checkErrors("Error executing SPARQL query $query") ;
    //print_r($rows) ;
    if (count($rows) == 0) {
      // the result is empty
      
      if (isset($propdescr['card']) && !$propdescr['optional']) {
        // the property has been explicitely defined as not-optional. Fail
        die("The expression $pexpr($objecturi) do not return any value") ;
        
      } elseif (isset($propdescr['card']) && $propdescr['optional']) {
        return $propdescr['multiple'] ? array() : NULL ;

      } else {
        // the cardinality of the property is not specified, returns always an array
        return array() ; 
      }
    } elseif (count($rows)==1 && isset($propdescr['card']) && !$propdescr['multiple']) {

      // there is one result and the property has been specified as single
      // this is ok, return this very single value
      // 'x' is the variable used in the sparql query
      return $rows[0]['x'] ;
      
    } elseif (count($rows)>=2 && isset($propdescr['card']) && !$propdescr['multiple'] ) {
      
      // various values have been found, but the property has been declared as single
      // log a warning and return the sigle value
      // 'x' is the variable used in the sparql query
      $this->log("The expression $pexpr($objecturi) returns more than one value") ;
      return $rows[0]['x'] ;
    } else {
      $result = array() ;
      foreach ($rows as $row) {
        // 'x' is the variable used in the sparql query
        $result[] = $row['x'] ;
      }
      return $result ;
    }
  }
  
  // die if the object is not existing or one of the property isn't correct
  public function /*Map*<PropertyExpression!,PropertyValue!>!*/
                       doEvalPropertySetExpression( /*RDFId*/ $objectrdfid,
                                                  /*ProperySetExpression*/ $psexpr ) {
    /*Map*<PropertyExpression!,PropertyDescription!>!*/ $propdescrmap = 
                                   $this->parsePropertySetExpression($psexpr) ;
    $result=array() ;
    foreach( $propdescrmap as $propexpr => $propdescr) {
      // actually, the property expressions are parse twice, but this is not so important
      $r = $this->evalPropertyExpression($objectrdfid,$propexpr) ;
      // optional attributes that has null value, are not put in the resulting map
      if ($r!=NULL) {
        $result[$propexpr] = $r ;
      } 
    }
    return $result ;
  }
  
  // check first if the object is of the specified type, and if this is the case
  // eval the set of propery expression
  // return NULL if there is no object of this type. Die if a property is not correct. 
  public function /*Map*<PropertyExpression!,PropertyValue!>?*/
                       tryEvalPropertySetExpression( /*RDFId*/ $objectrdfid,
                                                     /*RDFId*/ $typerdfid,
                                                     /*ProperySetExpression*/ $psexpr ) {
    if ($this->isOfType($objectrdfid,$typerdfid)) {
      return $this->doEvalPropertySetExpression($objectrdfid,$psexpr) ;
    } else {
      return NULL ;
    }
  }
  
  
  public function __construct(  /*Hostname!*/ $server,
                                /*String!*/   $dbname,
                                /*String!*/   $user,
                                /*String!*/   $passwd,
                                /*String!*/   $store,
                                /*Map*<String!,URI!>?*/ $additionalPrefixes,
                                /*Logger!*/    $logger) {      
    $this->logger = $logger ;
    $this->setupStore($server,$dbname,$user,$passwd,$store,$additionalPrefixes) ;
    $this->test() ;
  }
  
  // to test this use the following url    http://localhost/asop/srdf$acme
  
  function test() {
    foreach( array("n2","n21","n31","n51","n61",'ttodgpsf') as $n) {
      echo "<h2>perspective:$n</h2>" ;
      $perspectiverdfid = '<http://localhost/asop/srdf$acme/Perspective/'.$n.'>' ;
      echo 'is perspective :' ;
      print( $this->isItFact($perspectiverdfid,'rdf:type','soo:Perspective')) ; 
      echo ' <br/>\n' ;
      print_r($this->tryEvalPropertySetExpression($perspectiverdfid,'soo:Perspective',
          'soo:perspectiveRepository! soo:name! rdf:type! soo:perspectiveOwner! soo:classFragmentExcluded* soo:classFragmentIncluded* ~soo:classFragmentPerspective*')) ;
    }
  
  }
  
}

