==============
Installing APC
==============

Short story: PHP is patroned by Zend. Zend Accelerator is a commercial
module, a caching layer for the compiled PHP code. This means PHP never
included a caching module because Zend wanted their own to sell.

**APC**, Alternative PHP Cache, does what Zend Accelerator does, and is
Open Source. Good thing it will come by default with PHP in the close
future! Until then, we must install it separately.

APC is the single, best thing you can do to make PHP blazing fast, with
nearly no configuration.

Requirements
^^^^^^^^^^^^

-  PHP
-  nano if you aren’t using SFTP ``apt-get install nano``

Installing
^^^^^^^^^^

It’s just a line:

::

    $ pecl install apc

Then open php.ini (you can use your SFTP application)

::

    $ nano /usr/local/lib/php.ini

Find ``extension`` (``CTRL+W`` to search) and after the block of
commented-out extensions add:

::

    extension=apc.so
    apc.shm_size = 256M # make this at least 64-128M, we use 512M for giggles

Then, restart PHP, and you’re done:

::

    $ /etc/init.d/php-fpm restart

Now, you should be done with installing PHP, and getting the most out of
it.

