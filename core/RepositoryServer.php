<?php  defined('_SOS') or die("No direct access") ;

require_once(ABSPATH_LIB."Logger.php") ; 
require_once(ABSPATH_LIB."Files.php") ;
require_once(ABSPATH_CORE."JsonRepository.php");


/* Once initialized, a RepositoryServer produce from each query the corresponding http answer */
interface IRepositoryServer {
  public  function /*void*/ processQuery(
                         /*Repository|Perspective|ClassFrament|InstanceFragment*/ $entityType,
                         /*GET|PUT|OPTION*/ $method,
                         /*Map<String,String>!*/ $parameters  ) ;
}







/*-----------------------------------------------------------------------------------------------
  A repository server supporting various protocols. 
-------------------------------------------------------------------------------------------------
Each protocol is identified by a short name (e.g. db, csv, etc).
New protocols can be deployed easily in the directory ABSPATH_EXTENSIONS_REPOSITORIES.
*/

class MultiProtocolRepositoryServer implements IRepositoryServer {
  protected /*String!*/ $protocol ;
  protected /*String!*/ $repositoryName ;
  protected /*Logger!*/ $logger ;
  protected /*JSonRepository?*/ $jsonRepository ;
  protected /*String!*/ $lastJsonErrorMessage ;
  
  
  protected function /*IJsonRepository!*/ openJsonRepository() {
    $this->logger->log("RepositoryServer::openJsonRepository. protocol='".
                          $this->protocol."', repository='".$this->repositoryName."'") ;
                          
    // load the repository factory
    $factoryname=ABSPATH_EXTENSIONS_REPOSITORIES.$this->protocol."/RepositoryFactory.php" ; 
    
    $this->logger->log("RepositoryServer:: loading factory '".$factoryname."'") ;
    require_once($factoryname) ;
    $this->logger->log("Factory:: createRepository'".$factoryname."'") ;
    
    // create the repository
    $repository = createRepository($this->protocol,
                                 $this->repositoryName,
                                 $this->logger) ;
    
    // wrap the repository in an appropriate JSonWrapper    
    if ($repository instanceof ISchemaFixedRepository ) {
      $this->jsonRepository = new SchemaFixedJsonRepository($repository) ;
    } else if ($repository instanceof IQueryRepository) {
      $this->jsonRepository = new QueryJsonRepository($repository) ;
    } else if ($repository instanceof IReadRepository) {
      $this->jsonRepository = new ReadJsonRepository($repository) ;
    } else {
      $this->jsonError("RepositoryServer:: Cannot open repository "
                       .$this->protocol.":".$this->repositoryName)  ;                       
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
              
            } else if (isset($parameters["class"])) {
              // this is a request for the extension of specified class fragment
              // InstanceFragment?class=<CFID>
              if ($this->checkSignature("class",$parameters)) {
                return $this->jsonRepository->getJsonAllInstanceFragmentSoids($parameters["class"]) ;
              } else {
                return $this->lastError() ;
              }
            } else {
              // this is a query specified by various attribute criteria
              // InstanceFragment?db::table::att1=pat1& ...
              
              // check if the json repository handle queries
              if (! $this->jsonRepository instanceof IQueryJsonRepository) {
                return $this->jsonError("sorry, the repository does not support queries") ;
              } else {
                $query=array() ;
                foreach( $parameters as $key => $pattern) {
                  $query[$key] = $pattern ;
                }              
                if (count($query)) {
                  return $this->jsonRepository->queryJsonInstanceFragmentSoids($query) ;
                } else {
                  return $this->jsonError("invalid query. Some arguments should be specified.") ;
                }
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
    if (isset($this->jsonRepository)) {
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
  
  protected function executeProtocolSpecificConfigurationFile() {    
    // first check in the config directory for local overwriting
    $conffile = ABSPATH_CONFIG."config-".$this->protocol.".php" ;
    if (isReadableFile($conffile)) {
      require_once($conffile) ;
    } else {
      // execute the config.php file of the extension if it exist.
      $conffile = ABSPATH_EXTENSIONS_REPOSITORIES.$this->protocol."/config-".$this->protocol.".php" ;
      if (isReadableFile($conffile)) {
        require_once($conffile) ;
      } else {
        unset($conffile) ;
      }
    }
    if (isset($conffile)) {
      $this->logger->log('protocol specific configuration file '.$conffile.' has been executed') ;
    }
  }
  
  public function __construct($protocol,$repositoryname) {
    $this->protocol = $protocol;
    $this->repositoryName = $repositoryname ;
    $this->logger = new Logger(ABSPATH_LOGS."server-".$this->protocol.".txt") ;

    // execute the global configuration file
    $conffile = ABSPATH_CONFIG."config.php" ;
    $this->logger->log('executing global configuration file '.$conffile) ;
    require_once($conffile) ;

    // execute the protocol specific configuration file
    $this->executeProtocolSpecificConfigurationFile() ;
    
    // open the json repository
    $this->openJsonRepository() ;
  }

}

