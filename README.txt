====================================================================
   ASOP :  A Social Object Platform 
====================================================================


Features
  This package contains a Social Object Server supporting different kind of protocols 
  to access different kind of repositories.
  It actually provides also directory implementation that compute perspectives dynamically and give
  access to existing ressources (filesystem, database, csv store, etc.) as repository.
  In this model actors are mapped to perspective using the following syntax	
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

		
		
Installation
  - unpack this archive in a php readable directory
  - modify the settings in config/config*.ini
	- note that the log directory (ABSPATH_LOGS) should be writable.
	- for database protocol change config-db.ini
	- for file system protocol change config-fs.ini
	- change if needed other configuration files (this should not be necessary)

Testing your installation
  NOTE: there is an installation of the package available on the web.
	Note that this may not be the same version
	and that the database available on both system are certainly not the same.
	
  - Using the social browser you should be able to browse the following directory/repositories 
	  - directory:  http://localhost/asop/DIR/
	  - users: use one of the following one. (here users encode information to deduce  perspective
  - If this is not working properly then follow the troubleshooting section
	
	
Troubleshooting
  - When running a query on repository logs are created in the /logs directory
  - Individual queries in the tests directory can be used to check if valid json is returned
			
			
Content of this archive

  /.git
    Git version control settings
    
  /.settings
  
  /docs
    Some pieces of documentation 
  
  /config
    The different configuration files. 
    These files are to be adapted to your installation.
    
    config.ini
	  Main configuration file.
	config-*.ini
	  Configuration file for each protocol
	
  /core
    The core implementation of the repository server
	Note that this directory contains no actual implementation.
	Repository implementations are in the extensions/repositories directory
  
  /libraries
    Some general purposes files
    
    		
  /extensions
    Contains extensions to enhance the feature of the repository server
    
    
    repositories/
      One folder for each repository protocol. 
      The name of the folder is used in the repository spectification.
      For instance db$sodb1  refers to the protocol db and the directory db
      
      xxx/
        RepositoryFactory.php     
        The factory that trigger the creation of the various kind of repository
		The protocol is in the name of the php file
		The configuration file for each protocol is in the top level
		
  /data/csv/
	  The root for the csv repository. 
	  This can be changed in config-csv.ini
	
  /models
    Models in simple json notation. 
    This directory is used by the sss protocol as well as by PhpRepositories
			
  /logs
	This directory contains a global log for each protocol, then a log for each repository.
	Some specific logs are also available, for instance for databases.
		
  /DIR
	  A simple implementation of a directory