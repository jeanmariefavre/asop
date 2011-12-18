<?php
require_once("../common/Files.php") ;
require_once("../common/Logger.php") ; 

/* Once initialized, a DirectoryServer produce from each query the corresponding http answer */

interface IDirectoryServer {
  public function /*void*/ processQuery(
                     /*Directory|Actor|Soid*/ $entityType,
                     /*GET|PUT|OPTION*/ $method,
                     /*Map<String!,String!>!*/ $parameters ) ;
}




abstract class AbstractDirectoryServer implements IDirectoryServer {
  protected /*String!*/ $dialect ;
  protected /*URL!*/ $url ;
  protected /*Map<String!,String!>!*/ $config ;
  
  protected abstract function /*Json!*/ jsonFromQuery(    
      /*Directory|Actor|Soid*/ $entityType,
      /*GET|PUT|OPTION*/ $method,   
      /*Map<String!,String!>!*/ $parameters ) ;
      
  public function /*void*/ processQuery(
    /*Directory|Actor|Soid*/ $entityType,
    /*GET|PUT|OPTION*/ $method,
    /*Map<String!,String!>!*/ $parameters ) {
    
    $jsonanswer = $this->jsonFromQuery($entityType,$method,$parameters) ;
    $this->sendHttpHeaders() ;
    echo $jsonanswer ;
  }

  protected function sendHttpHeaders() {
    header("Content-Type:	application/json") ;
    header("Access-Control-Max-Age: 86400") ;
    header("Access-Control-Allow-Headers: Content-Type") ; 
    header("Access-Control-Allow-Methods: GET,PUT,POST,DELETE") ; 
    header("Access-Control-Allow-Origin: *") ;
  }
  
  
}


/* In this implementation the name of the actor contains the perspective it has access to,
so nothing is actually stored. */


class ActorPerspectivesHardwiredDirectory extends AbstractDirectoryServer
                                          implements IDirectoryServer {

  protected /*URL*/ $defaultRepositoryURL ;

  protected function /*Json!*/ jsonFromQuery(    
      /*Directory|Actor|Soid*/ $entityType,
      /*GET|PUT|OPTION*/ $method,   
      /*Map<String!,String!>!*/ $parameters ) {
      
    assert ('$method=="GET"') ; /* Currently the only method implemented */
    
    switch ($entityType) {

      case "Soid":
        // return the time in second since the epoch
        $json=time() ;
        break ;
        
      case "Directory":
        $a = array(
               "url"=>$this->url,
               "dialect"=>$this->dialect 
             ) ;
        $json = json_encode($a) ;
        break ;
        
      case "Actor":
        // In this directory the id of the actor is the same as its name and is the name of the db
        // in the query it can be either available from name or actor_id
        if (isset($parameters["actor_id"])) {
          $spec = $parameters["actor_id"] ;
        } else if (isset($parameters["name"])) {
          $spec = $parameters["name"] ;
        } else {
          $spec = "" ;
        }
        if ($spec) {
          $actor = array(
                 "_server" =>$this->url,
                 "_type"   =>"User",
                 "_soid"   =>$spec,
                 "name"    =>$spec,
                 "perspectives" => $this->extractPerspectiveUrls($this->defaultRepositoryURL,$spec)
               ) ;
          $json = json_encode($actor) ;        
        } else {
          $json = '{"error":"Actor $userperspectivesspec request not recognized"}' ;
        }
        break ;
        
      default:
        $json = '{"error":"method $entityType.$method is not recognized"}' ;
    }
    return $json ;
  }
  


  // parse the specification of perspectives
  // ; is the serparator between perspective
  // 
  protected function extractPerspectiveUrls($repositorysServerUrl,$spec) {
    $perspectiveurls = array() ;
    $perspectivespecs=explode(';',$spec) ;
    foreach ($perspectivespecs as $perspectivespec) {
      $segments = explode('$',$perspectivespec) ;
      if (count($segments)==1) {
        $perspectiveurl = str_replace('|','/',$perspectivespec) ;
      } else {
        if (count($segments)==3) {
          // protocol$repository$perspective
          $protocol=$segments[0] ;
          $repositoryname = $segments[1] ;
          $perspectivename = $segments[2] ;
        } else if (count($segments)==2) {
          // protocol$repository
          $protocol=$segments[0] ;
          $repositoryname = $segments[1] ;
          $perspectivename = $repositoryname ;
        } else {
          die("wrong syntax for perspective specification: $perspectivespec ") ;
        }
        $repositoryurl = addToPath($repositorysServerUrl,$protocol.'$'.$repositoryname) ;
        $perspectiveurl = addToPath($repositoryurl,addToPath("Perspective",$perspectivename)) ; 
      }
      $perspectiveurls[] = $perspectiveurl  ;
    }
    return $perspectiveurls ;
  }



  
  public function __construct($entityType,$method,$parameters ) {
    $this->config = parse_ini_file("../config.ini") ;

    $this->dialect="hardwired-multiple-protocol" ;
    $this->url = $this->config['ROOT_URL'].$this->config['SOCIAL_DIRECTORY_PATH'] ;
    $this->defaultRepositoryURL = $this->config['ROOT_URL'].$this->config['SOCIAL_REPOSITORY_PATH'] ;

  }
}


?>