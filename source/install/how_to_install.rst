How to install
##############

Installing FoOlFuuka doesn’t require science, only a properly configured
server.

To configure the server, head to the index page of this wiki where you
can find the point-by-point instructions on how to install the necessary
software.

Requirements
~~~~~~~~~~~~

Hardware
        

-  A VPS or Dedicated Server with command line access
-  At least 382MB RAM for a small installation (512MB+ suggested)
-  Multicore suggested
-  64bit suggested
-  A few gigabytes of space, for the images

Software
        

-  An UNIX-like operating system (Debian 6 64bit suggested)
-  PHP 5.3+ (5.4.1+ suggested)
-  MySQL 5.1+ (5.5+ suggested)
-  Nginx 0.8+ or Apache 2 (Nginx 1.2+ suggested)
-  ImageMagick suggested

Creating a database
~~~~~~~~~~~~~~~~~~~

Via cPanel
''''''''''

-  Login to cPanel
-  Click on MySQL Database Wizard
-  Create your database
-  Create your login information for the database and write it down for
   later

Via PhpMyAdmin
''''''''''''''

-  Login to phpMyAdmin
-  Click on “Databases” in the top navigation bar
-  Create your database with “utf8\_general\_ci” as your collation and a
   name of your choice
-  Click on “Privileges” in the top-right of the page
-  Click on “Add a new user”
-  Create your login information for the database and write it down for
   later
-  Press “Go” at the bottom to create your login information for the
   database

Via command line (not suggested)
''''''''''''''''''''''''''''''''

-  Enter mysql via command line and enter the MySQL password when asked.
   The user can be ``root`` or any user with a MySQL account with
   administrative privileges. ::

       $ mysql -u root -p

-  Create the database with whatever name you want, possibly a simple
   alphabetic lowercase name. ::

       mysql> CREATE DATABASE foolfuuka;

-  Create an user and password for the database created. It’s good
   practice to give it the same name as the database. ::

       mysql> GRANT usage ON *.* to foolfuuka@localhost identified by 'xxxpasswordxxx';
       mysql> GRANT all privileges ON foolfuuka.* to foolfuuka@localhost;

-  You’re done, close the MySQL connection. ::

       mysql> exit;

Installing
~~~~~~~~~~

-  Reach your server’s public folder ::

       $ cd /var/www

-  Download the latest FoOlFuuka via command line. Grab the latest
   version from the `TAGS page <https://github.com/FoOlRulez/FoOlFuuka/tags>`_ without a “-dev” in front of the
   version number. ::

       $ wget https://github.com/FoOlRulez/FoOlFuuka/zipball/0.7.0-dev-2 -O foolfuuka.zip

-  UnZip and change the name of the created folder ::

       $ unzip foolfuuka.zip
       $ ls # search the generated folder in case the unzip didn't show the output making it obvious
       $ mv FoOlRulez-FoOlFuuka-xxxxxxx/ foolfuuka/ # you can choose whichever name you want

-  If you are on an Nginx server, check the page on how to :doc:`setup the Nginx configuration </server_install/configuring_nginx>`.

-  Open your browser, and reach the folder where you installed
   FoOlFuuka, like ``http://yourdoma.in/foolfuuka``

-  If this page works, go ahead onto
   ``http://yourdoma.in/foolfuuka/install``, else head to :doc:`Troubleshooting installation </install/troubleshooting>`

-  If this page works, follow the instructions on screen, else head to :doc:`Troubleshooting installation </install/troubleshooting>`

