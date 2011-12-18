====================================================================
   ASOP :  A Social Object Platform 
====================================================================


Features
  This package contains a Social Object Server supporting different kind of protocols 
	to access different kind of repositories.
	It actually provides also directory implementation that compute perspectives dynamically and give
  access to existing ressources (filesystem, database, csv store, etc.) as repository.
  In this model actors are mapped to perspective using the following syntaxe	
  	<protocol> $ <repositoryname> [ $<perspectivename> ]
  For instance db$so1$_ is the name of the actor that have one perspective
  using the protocol db, the database so1 and the "raw" perspective named _

	- database protocol
		- name : db
	  - purpose : provide access to sql database (currently mysql database and read only)
		- syntax :  db $ <dbname> $ _
		- configuration file : config-db.ini
	
	- filesystem protocol
	  - name : fs
		- purpose : providing access to a file system. Example of hierarchical repository.
		- syntax : 
		
	- TBC (see below for the example)

Change Log
  1.5 
	  - methods  getAllPerspectiveSoids, getAttribute added on repository
		  This is important for consistency and will simplify a lot the reflective repository
      getAttribute not implemented yet
	  - DatabaseRepository.php  refactored + interface/impl IDatabaseInstanceSoidKeyValuesMapper
		  The implementation DatabaseSoidConcatenateKeyValuesMapper adds database and table  
		
		
Installation
  - unpack this archive in a php readable directory
  - modify the settings in config.ini and adjust in particular ROOT_URL and ROOT_DIR
	- note that the log directory (LOG_DIR) should be writable. It is relative with ROOT_DIR
	- for database protocol change config-db.ini
	- for file system protocol change config-fs.ini
	- change if needed other configuration files (this should not be necessary)


Testing your installation
  NOTE: there is an installation of the package available on the web.
	Note that this may not be the same version
	and that the database available on both system are certainly not the same.
	
  - Using the social browser you should be able to browse the following directory/repositories 
	  - directory:  http://localhost/asop/DIR/
	  - users: use one of the following one. 
		    In fact here users encode information to deduce  perspective
	        db$so1$_
					db$so1$_;csv$test;sss$meta
          fs$fs
	        db$jtest$_
          sss$meta
          meta$fs_fs$meta
					http:||domain:10000|project-tracking|resource|Perspective|120
  - If this is not working properly then follow the troubleshooting section
	
	
Troubleshooting
  - When running a query on repository logs are created in the /logs directory
  - Check if repositories queries are working properly. 
	    The following repository URLs should return some valid json
			- http://localhost/asop/sss$meta
			- http://localhost/asop/csv$test
			- http://localhost/asop/fs$fs
	    - http://localhost/asop/db$so1
  - Check if the directory is working properly 
	    - http://localhost/asop/DIR/Actor/fs$fs
			- http://localhost/asop/DIR/Actor/db$so1$so1_raw
			- http://localhost/asop/DIR/Actor/csv$test

			
			- http://localhost/asop/csv$test/ClassFragment/test::People
			
			
Content of this archive
		
  /common/
    The implementation of the repository server and wrappers.
		This should be refactored so that each implementation goes in a
		different directory with a plugin architecture.
		
  /common/ProtocolRepositoryFactories
	  The factory that trigger the creation of the various kind of repository
		The protocol is in the name of the php file
		The configuration file for each protocol is in the top level
		
	/data/csv/
	  The root for the csv repository. This can be changed in config-csv.ini
	
  /models
    Models in simple json notation. This directory is used by the sss protocol
		as well as by NativeRepositories
		
	/DIR
	  A simple implementation of a directory in which the list of perspective of for
		a user is encoded in the name of the user
		
	/logs
	  This directory contains a global log for each protocol, then a log for each repository.
		Some specific logs are also available, for instance for databases.
		
		
	/config.ini
	  Main configuration file.
		
	/config-*.ini
	  Configuration file for each protocol
