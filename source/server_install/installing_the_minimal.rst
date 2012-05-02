======================
Installing the minimal
======================

So, you’ve logged in your new server for the first time?

For sure you’re running as ``root``, and that’s good enough for now.

Let’s fire a few commands to update the server’s software to the latest
versions.

::

    $ apt-get update
    $ apt-get upgrade

Let’s get rid of Apache if it’s installed. Some servers have it
installed for some obscure reason.

::

    $ apt-get remove apache apache2

We have many servers, so we use to change the hostname to the name of
our favorite `2hu`_ so we can just use a name to distinguish them. You
might change it too to give a cool name to the server:

::

    $ hostname hakurei # or any name you want

I suggest restarting the server after changing the hostname, or some
application might have issues.

::

    $ reboot

And wait few seconds to login into the server again. Now the command
line should display ``root@hakurei``.

Now, install some basic software. This will install a lot of
dependencies necessary to the rest of the installation.

::

    $ apt-get install build-essential sudo libpcre3-dev libssl-de imagemagick graphicsmagick

This is all for the basic installation.

.. _2hu: http://en.wikipedia.org/wiki/Touhou_Project