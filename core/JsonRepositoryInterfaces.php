<?php  defined('_SOS') or die("No direct access") ;

interface IReadJsonSchemaRepository {
  public function /*Json!*/ getJsonRepository() ;
  public function /*Json!*/ getJsonAllPerspectiveSoids() ;
  public function /*Json!*/ getJsonPerspective     (/*String!*/ $perspective_soid) ;
  public function /*Json!*/ getJsonClassFragment   (/*String!*/ $class_fragment_soid) ;
  public function /*Json!*/ getJsonAttribute       (/*String!*/ $attribute_soid) ;  
}

interface IReadDataJsonRepository {
  public function /*Json!*/ getJsonInstanceFragment(/*String!*/ $class_fragment_soid, 
                                                    /*String!*/ $instance_soid) ;
  public function /*Json!*/ getJsonAllInstanceFragmentSoids( 
                                 /*String!*/ $class_fragment_soid) ;
}

interface IReadJsonRepository 
              extends IReadJsonSchemaRepository, IReadDataJsonRepository {
}

interface IQueryJsonRepository extends IReadJsonRepository {
  public function /*Json!*/queryJsonInstanceFragmentSoids(
                                 /*Map<String!,String!>!*/ $query ) ;
}

interface ISchemaFixedJsonRepository extends IQueryJsonRepository {
  public /*true|null*/ function putJsonInstanceFragment(/*Json!*/ $json_instance_fragment ) ;
}

interface IJsonRepository extends ISchemaFixedJsonRepository {
  /*TODO*/
}



