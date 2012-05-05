Compiling PHP
#############

Installing PHP is an easy task, but you will want the most out of it.
That’s why you want a custom compilation!

Here’s a guide on how to install PHP so you know you will be able to
support all of the FoOlFuuka sweetness. More importantly, this installs
PHP-FPM, that will automatically manage its resources against the
incoming traffic.

Requirements
************

-  MySQL 5+ installed
-  sudo if you’re not running as root (and you shouldn’t!)
   ``apt-get install sudo``
-  wget ``apt-get install wget``

Get rid of repository PHP
*************************

If you have PHP from the Debian repo, get rid of it. The following
should do a pretty good job, but don’t trust me with it, because the
possibilities are unlimited: ::

    $ /etc/init.d/php5 stop
    $ apt-get remove php5 php5-commons

If this is not enough, document yourself on how to get rid of it for
your own case.

Download, compile and install PHP
*********************************

Now, download the latest version of PHP. Go the PHP downloads page and
grab the “Current Stable” version. Click on the download link to reach
the download page. Copy one of the download links, and use it like this: ::

    $ cd /opt
    $ wget http://xx.php.net/get/php-5.x.xx.tar.gz/from/this/mirror -O php-5.x.xx.tar.gz

For your convenience, fill up the remaining ``x`` with the version
number. Then, extract it: ::

    $ tar xvzf php-5.x.xx.tar.gz
    $ cd php-5.x.xx/

Let the magic, and the stress, begin. You *will* get errors during this
phase, and you just have to install whatever it asks for with
``apt-get``. You *must* have MySQL installed at this point.

A few requirements on top of my head so you don’t have to try too many
times: ::

    $ apt-get install build-essentials openssl libcurl4-openssl-dev sqlite3 libsqlite3-dev libxml2 libxml2-dev zlib1g-dev bzip2 zip unzip libjpeg-dev libpng-dev

Now, let’s try configuring it. See the MySQL folders? Adapt them to
where you installed MySQL ::

    $ ./configure --with-mysql=/home/mysql/mysql  \
        --with-pdo-mysql=/home/mysql/mysql/bin/mysql_config \
        --with-mysqli=/home/mysql/mysql/bin/mysql_config \
        --enable-zip --enable-sockets --enable-fpm  --with-gettext \
        --with-gd --enable-ftp --enable-exif --with-curl --with-bz2 \
        --with-openssl --with-mcrypt --enable-mbstring --with-jpeg-dir \
        --with-png-dir --with-zlib --enable-bcmath

With enough luck, with little effort you will have PHP configured. Let’s
compile it! ::

    $ make
    $ make install

If everything has gone right, you have installed PHP. To run it, you
need the init.d script that ships with PHP itself. ::

    $ cp sapi/fpm/init.d.php-fpm.in /etc/init.d/php-fpm
    $ chmod 755 /etc/init.d/php-fpm

Now, start, stop or restart PHP like this ::

    $ /etc/init.d/php-fpm start [stop|restart]

You can have it starting on boot with: ::

    $ update-rc.d php-fpm defaults

We’re not done yet!
*******************

You still have to configure PHP (at least a bit!) and install APC.
First, let’s :doc:`configure it </server_install/configuring_php>`.