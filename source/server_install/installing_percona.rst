Installing Percona Server instead of MySQL
##########################################

`Percona Server <http://www.percona.com/software/percona-server/>`_ is a drop-in replacement for MySQL, more precisely, a modified MySQL Community Server.

We prefer Percona Server because it provides us more statistics and faster schema changes. Without much benchmarking, I could say it’s a bit faster overall with InnoDB and it uses a bit less RAM, but it’s still MySQL at the core.

In other words, there’s no good reason to keep using the official MySQL Community Server, so let’s install Percona Server.

This article covers both installation or upgrade from MySQL to Percona.

Requirements
************

-  sudo if you aren’t running as root (and you shouldn’t!) ``apt-get install sudo``
-  wget ``apt-get install wget``

Get Percona Server binaries
***************************

Go to the `Percona Server downloads page for the latest version <http://www.percona.com/downloads/Percona-Server-5.5/LATEST/>`_, go for ``binary » linux`` and choose ``x86_64`` if your OS is 64bit (I hope it is).

Then, use your server to download it and extract it: ::

    $ cd /opt
    $ wget http://www.percona.com/redir/downloads/Percona-Server-5.5/Percona-Server-5.5.22-25.2/binary/linux/x86_64/Percona-Server-5.x.xx-relxx.x-xxx.Linux.xxxx.tar.gz
    $ tar xvzf Percona-Server-5.x.xx-relxx.x-xxx.Linux.xxxx.tar.gz

Now you have the binaries ready for use.

Fresh installation
******************

If you don’t have a MySQL Server, you need to install it and create the
first databases. The process is identical to the one of installing
MySQL, and in fact we won’t use “Percona” anywhere, to keep
compatibility.

This is partly copied from the `MySQL 5.5 binary installation guide <http://dev.mysql.com/doc/refman/5.5/en/binary-installation.html>`_,
but we’ve made a few changes for your convenience. Don’t copy the hashes
and what comes after them, those are only comments. ::

    $ cd /opt
    $ mkdir /home/mysql # we like to give mysql a folder in /home, since often it's on a larger HDD
    $ groupadd mysql
    $ useradd -r -g mysql mysql
    $ cp Percona-Server-5.x.xx-relxx.x-xxx.Linux.xxxx /home/mysql/mysql
    $ cd /home/mysql/mysql
    $ chown -R mysql .
    $ chgrp -R mysql .
    $ scripts/mysql_install_db --user=mysql --basedir=/home/mysql/mysql --datadir=/home/mysql/mysql/data
    $ chown -R root .
    $ chown -R mysql data
    $ cp support-files/my-medium.cnf /etc/my.cnf # medium is fine, you will have to blow this out or proportion anyway
    $ cp support-files/mysql.server /etc/init.d/mysql

And this should be it. Start/stop/restart like this: ::

    $ /etc/init.d/mysql start [stop|restart]

To autostart it on boot: ::

    $ update-rc.d mysql defaults

You should be done installing Percona Server.

Replacing MySQL with Percona
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Stop MySQL. Should be a command like this ::

    $ /etc/init.d/mysql stop # maybe /etc/init.d/mysql.server stop

After this is done (can take minutes, be patient!), move your MySQL data
folder out of the MySQL folder. I am using example folders, switch them
with the appropriate folders. ::

    $ mv /home/mysql/mysql/data /home/mysql/data # just move it outside

Then change the MySQL folder name instead of removing it, for safety. ::

    $ mv /home/mysql/mysql /home/mysql/mysql_original

Let’s move Percona where MySQL belonged. ::

    $ mv /opt/Percona-Server-5.x.xx-relxx.x-xxx.Linux.xxxx /home/mysql/mysql

Move the data folder back. ::

    $ mv /home/mysql/data /home/mysql/mysql/data

And restart MySQL (that now will be Percona Server!) ::

    $ /etc/init.d/mysql start # maybe /etc/init.d/mysql.server start

And this is it, you’re now using Percona Server.