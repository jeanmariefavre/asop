DROP DATABASE IF EXISTS `sodb1` ;
CREATE  DATABASE `sodb1` ;


GRANT USAGE 
	ON * . * 
	TO 'socialdbuser' ; 							

GRANT ALL PRIVILEGES 
	ON `sodb1` . * 
	TO 'socialdbuser'
	WITH GRANT OPTION ;