<?php  defined('_SOS') or die("No direct access") ;


//--------------------------------------------------------------------------------
//--- Repository interfaces ------------------------------------------------------
//--------------------------------------------------------------------------------

interface IReadModelRepository {
  public function /*URL!*/                   getURL() ;
  public function /*String!*/                getDialect() ;
  public function /*"false"|"true"*/         isReadOnly() ;
  public function /*List*<String!>!*/        getAllPerspectiveSoids() ;
  public function /*IPerspective?*/          getPerspective     (/*String!*/ $perspective_soid) ;
  public function /*IClassModelFragment?*/   getClassFragment   (/*String!*/ $class_fragment_soid) ;
  // this method has been added both for consistency and for the reflective interface. 
  // it was not defined initially although a attribute has an soid and is therefore suceptible
  // to be accessed via the interface
  public function /*IAttribute?*/            getAttribute(/*String!*/ $attribute_soid) ;
}


interface IReadInstanceRepository {
  public function /*List*<String+!>?*/    getAllInstanceFragmentSoids(
                                                              /*String!*/ $class_fragment_soid) ;
  public function /*IInstanceFragment?*/  getInstanceFragment(/*String!*/ $class_fragment_soid, 
                                                                 /*String!*/ $instance_soid) ;
}


interface IReadRepository extends IReadModelRepository, IReadInstanceRepository {
}

interface IQueryRepository extends IReadRepository {
  public function /*List*<String!>?*/ queryInstanceFragments(
                                         /*Map<String!,String!>!*/ $query ) ;
}

interface IModelFixedRepository extends IReadRepository {
  public function /*IInstanceFragment?*/ putInstanceFragment( 
                                           /*String!*/ $class_fragment_soid,
                                           /*String!*/ $instance_fragment_soid,
                                           /*Map*<String!,String!>!*/ $attribute_map ) ;
}

interface IRepository extends IModelFixedRepository, IQueryRepository {
  /*TODO*/
}


interface ISocialObject {
  public function /*URL!*/    get_server() ;
  public function /*String!*/ get_type() ;
  public function /*String!*/ get_soid() ;
}

//--------------------------------------------------------------------------------
//--- Perspective interfaces -----------------------------------------------------
//--------------------------------------------------------------------------------

interface IPerspective extends ISocialObject {
  public function /*IRepository!*/ getRepository() ;
  public function /*String!*/ getName() ;
  public function /*IActor?*/ getOwner() ;
  public function /*List[*]<IImportDeclaration!>!*/ getImportDeclarations() ;
  public function /*Set[*]<IClassFragment!>!*/ getClassFragments() ;
}

/* TODO
public interface getImportDeclarations
*/

//--------------------------------------------------------------------------------
//--- ClassFragment interfaces ---------------------------------------------------
//--------------------------------------------------------------------------------

interface IClassModelFragment extends ISocialObject {
  public function /*String!*/ getName() ;
  public function /*List[*]<IAttribute>!*/ getAttributes() ; /*inverse:getClassFragment*/
  public function /*IPerspective!*/ getPerspective() ;  /*inverse:getClassFragments*/
  public function /*IClassFragment?*/ getTarget() ;
}

interface IClassExtensionFragment extends ISocialObject {
  // is this really usefull. its easier to implement the ids only
  // public function /*Set[*]<IInstanceFragment>!*/ getAllInstanceFragments() ;
}

interface IClassFragment extends IClassModelFragment,IClassExtensionFragment {
}


//--------------------------------------------------------------------------------
//--- Attribute interfaces -------------------------------------------------------
//--------------------------------------------------------------------------------

interface IAttribute extends ISocialObject {
  public function /*String!*/ getName() ;
  public function /*Integer>=-1*/ getPositionInLabel() ;
  public function /*String!*/ getType() ;
  public function /*IClassFragment!*/ getClassFragment() ;  /*inverse:getAttributes*/
}


//--------------------------------------------------------------------------------
//--- IntanceFragment interfaces -------------------------------------------------
//--------------------------------------------------------------------------------

interface IInstanceFragment {
  public function /*String!*/ get_soid() ;
  public function /*IClassFragment!*/ getClassFragment() ; 
// TODO  public function /*IAttributeValue>?*/ getAttributeValue(IAttribute $attribute) ;
// TODO  public function /*List[*]<IAttributeValue>!*/ getAttributeValues() ;
  public function /*Map*<String!,String!>!*/ getAttributeMap() ;
}

interface IAttributeValue {
  public function /*IAttribute!*/ getAttribute() ;
  public function /*IValue?*/ getValue() ;  /*TODO check the case of no value */
}
