<?php  defined('_SOS') or die("No direct access") ;

interface IDirectory {
  public function /*IActor?*/ getActorNamed( /*String!*/ $name ) ;
  public function /*Iactor?*/ getActor( /*String!*/ $actor_soid ) ;
}

interface IActor {
  public function /*String!*/ getName() ;
  public function /*"Group"|"User"!*/ getKind() ;
  // /**/ getMemberships() ;
  // TODO
}

