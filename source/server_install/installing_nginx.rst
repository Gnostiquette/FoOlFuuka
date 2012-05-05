Installing Nginx
################

We will just use the Nginx official repository. Nginx updates really
frequently and it would be a pain to It includes tons of modules except
the TCP/IP one that will be needed in the future if you want to support
truly real time threads. For now, don’t bother with it.

This is basically what’s written at http://wiki.nginx.org/Install

Add the new repositories to the sources.list file ::

    $ nano /etc/apt/sources.list # you can use your SFTP client

Add two lines: ::

    deb http://nginx.org/packages/ubuntu/ lucid nginx
    deb-src http://nginx.org/packages/ubuntu/ lucid nginx

Now we need to add the Nginx GPG keys to validate the repository not to
have warnings showing when using ``apt-get update``: ::

    $ gpg --recv-key 7BD9BF62
    $ gpg -a --export 7BD9BF62 | sudo apt-key add -

To end it, install Nginx by first updating from the repository sources
and then install Nginx. ::

    $ apt-get update
    $ apt-get install nginx

It might ask you to create ``/etc/init.d/nginx``, and you should allow.

Unless it already started, you can run Nginx: ::

    $ /etc/init.d/nginx start [stop|restart]

Now, this is not enough and hardly anything will work with Nginx. You
need to :doc:`configure Nginx </server_install/configuring_nginx>`.