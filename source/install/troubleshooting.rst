Troubleshooting installation
############################

It is possible for your server to prevent FoOlFuuka to run. This page explains some common scenarios that we've solved before.

**At this point of time you should already have FoOlFuuka extracted in a directory.**

The webserver is Nginx and I didn't set it up
---------------------------------------------

Without setting up Nginx, your server won't know what to do with FoOlFuuka's URLs. Go to the :doc:`Nginx configuration </server_install/configuring_nginx>` page and follow the steps.

The webserver is Apache and maybe mod_rewrite is not enabled
------------------------------------------------------------

Without rewriting support, the webserver won't know what to do with FoOlFuuka URLs (and most other web applications will have issues). Open the httpd.conf file: ::

    $ nano /etc/httpd/conf/httpd.conf # you can use your SFTP connection

With ``CTRL+W`` find the line saying ``AllowOverride`` and make sure it's set on ``AllowOverride All``.

``CTRL+S` to save and ``CTRL+X`` to exit nano.

Check the folder permissions
----------------------------

Hardly a problem, but FoOlFuuka likes owning its own files, cosidering it also autoupdates. Discover the name of the user your webserver is running as. If you don't know, write this: ::

    $ cd /get/in/foolfuuka/directory
    $ echo "<?php echo exec('whoami'); ?>" > whoami.php

Then on your browser, reach ``http://yourdoma.in/foolfuuka/whoami.php`` and the output will be the name of the user running the webserver. Besides, if you created whoami.php, the file is better if it doesn't stay there, so remove it. ::

    $ rm whoami.php

Now change the permissions accordingly. In example, if the user is the classic ``www-data``: ::

    $ cd /get/in/foolfuuka/directory
    $ chown -R www-data .

And permissions should be fixed.