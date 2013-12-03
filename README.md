Crane
=====

Bootstrap your local environment using docker containers & git


Setup
-----

1. Create your config according to the [schema](/app/crane-schema.json). You can find detailed descriptions there
2. Push your schema to a git repository, for instance: ```git@example.com:vendor/crane-project```

Initialize a project
--------------------

1. Install crane on your local machine using [composer](http://getcomposer.org). 
   
        $ composer global require 'mlebkowski/crane:dev-master'
       
    The binary will be put in ```~/.composer/vendor/bin/crane```
   
2. Add project configuration:

         $ crane project:init git@example.com:vendor/crane-project
       
3. Edit the ```~/.crane/config.json``` to set up the ```targets```/```current-target```

Build images
------------

This is only required once per target. Docker images will be built from Dockerfiles.

    $ crane image:build vendor/project-name --verbose

If something changes or goes wrong, you may want to use the ```--rebuild``` flag.

Start the project
-----------------

Launch all containers. 

    $ crane project:start vendor/project-name --verbose
    
Use ```--restart``` if something is wrong or images have changed. The main image is always restarted regardless.

Bootstrap the application
-------------------------

You may want to bootstrap your app in some way (init the database, setup permissions, etc). Crane is application agnostic, so it does not have any build in mechanism. Id does however provide a ```project:command``` command. It will SSH into the running container (if ```22``` port is exposed inside) using the ```identity``` file as a private key.

To run a bootstrapping command on the main container, for example:

    $ crane project:command vendor/project-name 'phing -verbose -f /home/main-image-name/build.xml install'  --verbose

Permissions
-----------

Make sure you have agent forwarding setup for target host. All SSH commands are using the ```-A``` flag.
