=================
Configure PHP-FPM
=================
If you followed the compilation guide, you now have PHP with FPM. That’s
wonderful, you’re going to take on a the traffic you probably didn’t
know your server could take.

Requirements
^^^^^^^^^^^^

-  PHP-FPM installed
-  nano if you use command line ``apt-get install nano``
-  htop to monitor your PHP resource usage ``apt-get install htop``

Configuration
^^^^^^^^^^^^^

Let’s configure it. I will use command line controls, but if you’re
savvy, just use your SFTP client.

::

    $ nano /usr/local/lib/php.ini

(``CTRL+W`` to find words in ``nano``, ``CTRL+O`` to save, ``CTRL+X`` to
exit)

Change the following (you can go higher than this for some values):

::

    max_execution_time = 120
    memory_limit = 256M # 128M for a 512MB RAM server
    date.timezone = America/New_York # or wherever your server is from this list http://php.net/manual/en/timezones.php

When you’re done (you can have fun looking at all the other variables)
save and close the file. Now open the FPM configuration file, which is
important to push on the performance:

::

    $ nano /usr/local/etc/php-fpm.conf

I can’t give specific values to assign, as it depends on the server size
and on how many resources should PHP use. Remember that these are just
limits, so even if misconfigured, nothing will blow up as long as you
don’t get a spike of visits. These are values we use on a 16GB RAM
server.

::

    pm = dynamic # else the rest won't work
    pm.max_children = 100 # < 20 on 512mb RAM
    pm.start_servers = 30 # < 10 digit on 512mb RAM
    pm.min_spare_servers = 30 # < 10 on 512mb RAM
    pm.max_spare_servers = 100 # < 20 on 512mb RAM
    pm.max_requests = 250 # respawns the thread to avoid lockups, low is fine

Save and close. Now restart PHP:

::

    $ /etc/init.d/php-fpm restart

You can see your PHP resource usage with

::

    $ htop

Press F5 to group the threads. Yes, PHP will have quite a few of them.

Let’s give PHP the last blow of speed: `Install APC`_.

.. _Install APC: Install-APC