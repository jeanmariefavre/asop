<?php
require_once("JsonRepository.php");
require_once("Logger.php") ; 

/* Once initialized, a RepositoryServer produce from each query the corresponding http answer */
interface IRepositoryServer {
  public  function /*void*/ processQuery(
                         /*Repository|Perspective|ClassFrament|InstanceFragment*/ $entityType,
                         /*GET|PUT|OPTION*/ $method,
                         /*Map<String,String>!*/ $parameters  ) ;
}









/* A repository server supporting various protocols. 
Each protocol is identified by a short name (e.g. db, csv, etc).
New protocols can be added easily by creating a new repository factory in the following directory
PROTOCOL_REPOSITIORY_DIRECTORY */

define('PROTOCOL_REPOSITIORY_DIRECTORY',"ProtocolRepositoryFactories") ;

class MultiProtocolRepositoryServer implements IRepositoryServer {
  protected /*String!*/ $protocol ;
  protected /*String!*/ $repositoryName ;
  protected /*Logger!*/ $logger ;
  protected /*Map<String!,String!>!*/ $config ;
  protected /*JSonRepository?*/ $jsonRepository ;
  protected /*String!*/ $lastJsonErrorMessage ;
  

  protected function /*IJsonRepository!*/ openJsonRepository() {
    $this->logger->log("RepositoryServer::openJsonRepository. protocol='".
                          $this->protocol."', repository='".$this->repositoryName."'") ;
    $factoryname=PROTOCOL_REPOSITIORY_DIRECTORY."/".$this->protocol."RepositoryFactory.php" ; 
    
    $this->logger->log("RepositoryServer:: loading factory '".$factoryname."'") ;
    require_once($factoryname) ;
    $this->logger->log("Factory:: createRepository'".$factoryname."'") ;
    $repository = createRepository($this->protocol,
                                 $this->repositoryName,
                                 $this->config,
                                 $this->logger) ;
    if ($repository instanceof ISchemaFixedRepository ) {
      $this->jsonRepository = new SchemaFixedJsonRepository($repository) ;
    } else if ($repository instanceof IQueryOnlyRepository) {
      $this->jsonRepository = new QueryOnlyJsonRepository($repository) ;
    } else if ($repository instanceof IReadOnlyRepository) {
      $this->jsonRepository = new ReadOnlyJsonRepository($repository) ;
    } else {
      $this->jsonError("RepositoryServer:: Cannot open repository "
                       .$this->protocol.":".$this->repositoryName
                       ." with configuration "
                       .json_encode($this->config) ) ;                       
      return NULL ;
    }    
  }
  
  protected function /*Json!*/ jsonError($message) {
    $this->logger->log("error: ".$message) ;
    $this->lastJsonErrorMessage = '{"error":"'.$message.'"}' ;
    return $this->lastJsonErrorMessage ;
  }
  
  
  protected function /*void*/ clearError() {
    $this->lastJsonErrorMessage = NULL ;
  }
  
  protected function /*Json?*/ lastError() {
    return $this->lastJsonErrorMessage ;
  }
  
  protected function /*boolean*/ checkSignature($signature,$effective) {
    $expected = ($signature==""?array():explode("&",$signature)) ;
    $provided = array_keys($effective) ;
    //print_r($expected) ;
    //echo"<br>" ;
    //sprint_r($provided) ;
    $unexpected = array_diff($provided,$expected) ;
    $missing = array_diff($expected,$provided) ;
    if (count($unexpected)!=0 ||count($missing)!=0){
      $message = "parameters missmatch. " ;
      if (count($unexpected)) {
        $message .= "Unexpected: ".implode(", ",$unexpected)." " ;
      }
      if (count($missing)) {
        $message .= "Missing: ".implode(", ",$missing) ;
      }
      $this->jsonError($message) ;
      return FALSE ;
    } else {
      $this->clearError() ;
      return TRUE ;
    }
  }
  
  
  protected function /*Json!*/ jsonFromQuery(
                         /*Repository|Perspective|ClassFrament
                           |Attribute|InstanceFragment*/ $entityType,
                         /*GET|PUT|OPTION*/ $method,
                         /*Map<String,String>!*/ $parameters  ) {
    $this->logger->log($entityType."::".$method ."(".json_encode($parameters).")") ;
    $this->clearError() ;
    
    switch ($entityType) {

      case "Repository":
        if ($this->checkSignature("",$parameters)) {
          return $this->jsonRepository->getJsonRepository() ;
        } else {
          return $this->lastError() ;
        }
        break ;
        
      case "Perspective":
        if (isset($parameters["perspective_id"])) {
          // seems to see a request for a particular perspective
          if ($this->checkSignature("perspective_id",$parameters)) {
            return $this->jsonRepository->getJsonPerspective($parameters["perspective_id"]) ;
          } else {
            return $this->lastError() ;
          }
        } else {
          // seems to be a request for all perspectives
          if ($this->checkSignature("perspective_id",$parameters)) {
            return $this->jsonRepository->getJsonAllPerspectiveSoids() ;
          } else {
            return $this->lastError() ;
          }
        }
        break ;
      
      case "ClassFragment":
        if ($this->checkSignature("class_fragment_id",$parameters)) {
          return $this->jsonRepository->getJsonClassFragment($parameters["class_fragment_id"]) ;
        } else {
          return $this->lastError() ;
        }
        break ;
        
      case "Attribute":
        if ($this->checkSignature("attribute_id",$parameters)) {
          return $this->jsonRepository->getJsonAttribute($parameters["attribute_id"]);
        } else {
          return $this->lastError() ;
        }         
        break ;

      case "InstanceFragment":
        switch ($method) {
          case "GET":
            if (isset($parameters["class_fragment_id"]) && $parameters["class_fragment_id"] != "") {
              // this is a request of a particular instance for a particular class fragment
              // InstanceFragment/<IFID>/<CFID>
              if ($this->checkSignature("class_fragment_id&soid",$parameters)) {
                return $this->jsonRepository
                                ->getJsonInstanceFragment($parameters["class_fragment_id"],
                                                          $parameters["soid"]) ;
              } else {
                return $this->lastError() ;
              }
              
            } else if ($parameters["class"]) {
              // this is a request for the extension of specified class fragment
              // InstanceFragment?class=<CFID>
              if ($this->checkSignature("class",$parameters)) {
                return $this->jsonRepository->getJsonAllInstanceFragmentSoids($parameters["class"]) ;
              } else {
                return $this->lastError() ;
              }
            } else {
              // this is a query specified by various attribute criteria
              // InstanceFragment?db.table.att1=pat1& ...
              $query=array() ;
              foreach( $parameters as $key => $pattern) {
                $query[$key] = $pattern ;
              }              
              if (count($query)) {
                return $this->jsonRepository->queryJsonInstanceFragmentSoids($query) ;
              } else {
                return $this->jsonRepository->getJsonAllInstanceFragmentSoids($parameters["class"]) ;
              }
            }
            break ;
          case "OPTION":
            return '';
            break ;
            
          case "PUT":
            // TODO the use of file_get_content may not not be ok for long messages. See forum for more info.
            $json = file_get_contents("php://input") ; 
            // Error should be returned.
            return $this->jsonRepository->putJsonInstanceFragment($json) ;
            break ;
        }
        break ;    
        
      default:
        return $this->jsonError("the query ".$entityType.".".$method." is not implemented") ;
    }   
  }
  
  
  public  function /*Json!*/ processQuery(
                         /*Repository|Perspective|ClassFrament|InstanceFragment*/ $entityType,
                         /*GET|PUT|OPTION*/ $method,
                         /*Map<String,String>!*/ $parameters  ) {
    if ($this->jsonRepository) {
      $jsonanswer = $this->jsonFromQuery($entityType,$method,$parameters) ;
    } else {
      $jsonanswer = $this->jsonError("Cannot execute the query."
                                     ." An error occurred with the repository.") ;
    }
    $this->sendHttpHeaders() ;
    echo $jsonanswer ;
  }
    
  public function sendHttpHeaders() {
    header("Content-Type:	application/json") ;
    header("Access-Control-Max-Age: 86400") ;
    header("Access-Control-Allow-Headers: Content-Type") ; 
    header("Access-Control-Allow-Methods: GET,PUT,POST,DELETE") ; 
    header("Access-Control-Allow-Origin: *") ;
  }  
  public function __construct($protocol,$repositoryname) {
    $this->protocol = $protocol;
    $this->repositoryName = $repositoryname ;
    $this->config = parse_ini_file("config.ini") ;
    if ($this->config === FALSE) {
      die( "global configuration file not found" );
    }
    // print_r($this->config) ;
    $this->logger = new Logger($this->config["LOG_DIR"]."/server-".$this->protocol.".txt") ;
    $protocolconfigfile = "config-".$this->protocol.".ini" ;
    $this->logger->log('loading '.$protocolconfigfile) ;
    $protocolconfig = parse_ini_file($protocolconfigfile) ;
    if ($protocolconfig === false) {
      die( "cannot open $protocolconfigfile for protocol ".$this->protocol." not found" );
    }
    $this->config = $this->config + $protocolconfig ;
    //print_r($this->config) ;
    $this->openJsonRepository() ;
  }

}

