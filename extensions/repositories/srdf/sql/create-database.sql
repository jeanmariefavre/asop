DROP DATABASE IF EXISTS `arc2_srdf` ;
CREATE  DATABASE `arc2_srdf` ;


GRANT USAGE 
	ON * . * 
	TO 'rdfdbuser' ; 							

GRANT ALL PRIVILEGES 
	ON `arc2_srdf` . * 
	TO 'rdfdbuser'
	WITH GRANT OPTION ;