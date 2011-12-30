<?php  defined('_SOS') or die("No direct access") ;
require_once(ABSPATH_LIB."Files.php") ;
require_once(ABSPATH_LIB."Logger.php") ; 

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
          $actorspec = $parameters["actor_id"] ;
        } else if (isset($parameters["name"])) {
          $actorspec = $parameters["name"] ;
        } else {
          $actorspec = "" ;
        }
        if ($actorspec) {
          $json = $this->jsonActorFromActorSpecification($actorspec) ;
          if ($json===NULL) {
            $json = '{"error":"Actor $actorspec, request not recognized"}' ;
          }
        } else {
          $json = '{"error":"Actor $actorspec, request not recognized"}' ;
        }
        break ;
        
      default:
        $json = '{"error":"method $entityType.$method is not recognized"}' ;
    }
    return $json ;
  }
  
  protected function /*Array?*/ jsonActorFromActorSpecification($actorspec) {
    $fragments = explode("=>",$actorspec) ;    
    if (count($fragments)==2) {
      $actorname = $fragments[0] == "" ? "anonymous" : $fragments[0] ;
      $perspectives = $this->parsePerspectiveUrls($this->defaultRepositoryURL,$fragments[1]) ;
      $actor = array(
          "_server" =>$this->url,
          "_type"   =>"User",
          "_soid"   =>$actorspec,
          "name"    =>$actorname,
          "perspectives" => $perspectives
      ) ;
      return json_encode($actor) ;
    } else if (count($fragments)==1) {
      $json = file_get_contents(ABSPATH_DIRECTORY."data/actor-".$fragments[0].".json") ;
      if ($json!==FALSE) {
        return $json ;
      } else {
        return NULL ;
      }
    } else {
      return NULL ;
    }
  }
  
  // parse the specification of perspectives
  // <perspectives> ::= <perspective> [ " " <perspectives> ]
  // <perspective> ::= <perspective_soid>
  //                 | <protocol> "$" <repositoryname>
  //                 | <protocol> "$" <repositoryname> "$" <perspectivename>
  // 
  protected function /*Set*<PerspectiveUrl>!*/  parsePerspectiveUrls($repositorysServerUrl, $spec) {
    $perspectiveurls = array() ;
    $perspectivespecs=explode(';',$spec) ;
    foreach ($perspectivespecs as $perspectivespec) {
      $perspectiveurl = $this->parsePerspectiveUrl($repositorysServerUrl,$perspectivespec) ;
      if ($perspectiveurl !== NULL) {
        $perspectiveurls[] = $perspectiveurl  ;
      }
    }
    return $perspectiveurls ;
  }
  

  protected function /*PerspectiveUrl?*/ parsePerspectiveUrl($repositorysServerUrl,$perspectivespec) {
    if ($perspectivespec=="") {
      return NULL ;
    } else {
      $segments = explode('$',$perspectivespec) ;
    
      if (count($segments)==1) {
    
        // remote perspective
        // perspective soid
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
     return $perspectiveurl ; 
    }
  }

  
  public function __construct($entityType,$method,$parameters ) {
    $conffile = ABSPATH_CONFIG."config.php" ;
    require_once($conffile) ;

    $this->dialect="hardwired-multiple-protocol" ;
    $this->url = URL_DIRECTORY ;
    $this->defaultRepositoryURL = URL_REPOSITORY ;

  }
}


?>