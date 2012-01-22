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

// This interface is based on the current weak definition of the type system.
// It will be improved when the revision of the types. At the moment this is 
// quite ugly but ...
interface IAttribute extends ISocialObject {
  public function /*String!*/ getName() ;
  public function /*Integer>=-1*/ getPositionInLabel() ;
  public function /*TypeExpression!*/ getTypeExpression() ;  // see below
  public function /*TypeKind!*/ getTypeKind()  ;
  public function /*List+<String!>?*/ getLiterals() ;  // when kind is ENUMERATION_TYPE_KIND
  public function /*Soid?*/ getInverseAtributeSoid() ; // when kind is COLLECTION_TYPE_KIND
  public function /*String?*/ getBaseTypeString() ;    // when kind is ATOMIC_, REFERENCE_ or COLLECTION_
}


//--------------------------------------------------------------------------------
//--- Type definitions -----------------------------------------------------------
//--------------------------------------------------------------------------------
//
// Currently this is directly linked with the crude representation of types which
// will change anyway sooner or later. 
//
// WARNING: DON'T CHANGE THE CONSTANTS IN THIS SECTION
// THE IMPLEMENTATION in AbstractRepository.php MAY MAKE SOME
// IMPLICIT ASSUMPTIONS SUCH AS THE SIZE OF THE STRINGS, ETC.
// ALL THAT WILL BE REWRITTEN ANYWAY WITH THE NEW TYPE SYSTEM.

define('ATOMIC_TYPE_KIND','A') ;
define('ENUMERATION_TYPE_KIND','E') ;
define('REFERENCE_TYPE_KIND','R') ;
define('COLLECTION_TYPE_KIND','C') ;
// <TypeKind> ::= ATOMIC_TYPE_KIND | ENUMERATION_TYPE_KIND | REFERENCE_TYPE_KIND | COLLECTION_TYPE_KIND

// The concrete syntax for TypeExpression is as following
// <TypeExpression> ::=
//     "A(" <AtomicType> ")"
//   | "E(" <String> "," ... ")"
//   | "R(" <URI> ")"
//   | "C(" <URI> "," <AttributeSoid> ")"

define('TYPE_EXPRESSION_PATTERN','/^(A|E|R|C)\(([^\)]+)\)$/') ;

// The abstract syntax for type is defined by the following structure

// TypeDescription == array
//    'kind'!     => <TypeKind>!
//    'literals'? => List+ <String!>!
//    'base'?     => <BaseType>!
//    'inverse'?  => <AttributeSoid>!
//
// <BaseType> ::= <AtomicType> | <URI>


define('TYPE_STRING','S') ;
define('TYPE_INTEGER','I') ;
define('TYPE_DATE','D') ;
define('TYPE_PICTURE','P') ;
define('TYPE_HTML','H') ;
define('TYPE_TEXT','T') ;
define('TYPE_LINK','L') ;
// <AtomicType> ::= TYPE_STRING, TYPE_INTEGER, ...


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
