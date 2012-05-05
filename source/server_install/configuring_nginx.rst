Configuring Nginx
#################

Since we’re at it, let’s go through a complete configuration for Nginx.

If you have installed Nginx from the official Nginx repositories as
suggested, you will have all the configurations in ``/etc/nginx``.

nginx.conf
**********

This is the file that gets loaded when Nginx starts. All other
configuration files are loaded from this file via includes. ::

    user  www-data; # or whatever www user you have
    worker_processes  1; # 4 is good for big installations
    worker_rlimit_nofile 30000; # avoid triggering the file limit

    error_log  /var/log/nginx/error.log warn;
    pid        /var/run/nginx.pid;

    events {
        worker_connections  1024; # Tune it along with worker_process, but you won't hit this anyway
    }

    http {
        include       /etc/nginx/mime.types;
        default_type  application/octet-stream;

        log_format  main  '$remote_addr - $remote_user [$time_local] "$request" '
                          '$status $body_bytes_sent "$http_referer" '
                          '"$http_user_agent" "$http_x_forwarded_for"';

        # disabling access_log to reduce disk I/O
        access_log  off;

        # reduces disk I/O by a lot
        open_file_cache max=1000 inactive=300s; 
        open_file_cache_valid    360s; 
        open_file_cache_min_uses 2;
        open_file_cache_errors   off;

        sendfile        on;
        #tcp_nopush     on;

        keepalive_timeout  65;

        # Without this you won't upload files bigger than 1mb, set it to however high you want it
        # even 100m is not an issue here
        client_max_body_size 30m; 

        # compression on Nginx side
        gzip  on;
        gzip_vary  on;
        gzip_buffers 16 8k;
        gzip_comp_level 6;
        gzip_http_version 1.1;
        gzip_min_length 1000;
        gzip_disable     "MSIE [1-6]\.";
        gzip_proxied expired no-cache no-store private auth;
        gzip_types text/plain text/css application/x-javascript text/xml application/xml application/xml+rss text/javascript application/json application/javascript;

        include /etc/nginx/sites-available/*;
    }

Adding a domain
***************

As you see in the last lines of ``nginx.conf``, we’ve set a folder
called ``/etc/nginx/sites-available/``. If it doesn’t exist yet, let’s
create it. ::

    $ cd /etc/nginx
    $ mkdir sites-available
    $ cd sites-available/

If the directory already existed and there’s some ``default`` file that
you didn’t touch or any file, get rid of it: ::

    $ cd /etc/nginx/sites-available/
    $ rm default

Now, let’s make a new file for a domain. It will be automatically loaded
by nginx.conf. I’ll use ``foolz.us`` as example. ::

    $ cd /etc/nginx/sites-available/
    $ nano foolz.us

A first example of the file. Again, we’re going all out, so it might not
be simple to read. Please, check all the comments to adapt to your
installation. ::


	server {
		server_name archive.foolz.us # change this to your domain or subdomain
		index index.php;
		root /var/www/foolfuuka; # change it to the location of your FoOlFuuka
					
		# tell the browser to cache all static files
		# FoOlFuuka automatically deals with forcing redownload through query parameters like style.css?v=0.8.0
		location ~* \.(css|js|htc|asf|asx|wax|wmv|wmx|avi|bmp|class|divx|doc|docx|eot|exe|gif|gz|gzip|ico|jpg|jpeg|jpe|mdb|mid|midi|mov|qt|mp3|m4a|mp4|m4v|mpeg|mpg|mpe|mpp|otf|odb|odc|odf|odg|odp|ods|odt|ogg|pdf|png|pot|pps|ppt|pptx|ra|ram|svg|svgz|swf|tar|tif|tiff|ttf|ttc|wav|wma|wri|xla|xls|xlsx|xlt|xlw|zip)$ {
			expires 31536000s;
			add_header Pragma "public";
			add_header Cache-Control "public, must-revalidate, proxy-revalidate";
			try_files $uri $uri/ /index.php;
		}
					
		# most important part, this sends the requests to FoOlFuuka
		location / {
			try_files $uri $uri/ /index.php;
		}
				 
		# connection to PHP
		location ~ .php$ {
			include /etc/nginx/fastcgi_params;
			fastcgi_index index.php;
			if (-f $request_filename) {
				fastcgi_pass 127.0.0.1:9000;
				
				# you can use the sockets (10%~ faster connection) if you setup PHP with a socket file
				# fastcgi_pass unix:/dev/shm/php5-fpm.sock;
			}
		}
	}
	
	# optional
	# block all sub-domains not set above
	server {
		listen 80;
		server_name *.foolz.us;
	
		location / {
			return 403;
		}
	}

And with this you have FoOlFuuka running from ``http://archive.foolz.us``. Let's say instead that you want FoOlFuuka in a subfolder, like ``http://foolz.us/foolfuuka``. Here's how to go with it:

::

	server {
		server_name foolz.us # change this to a domain or subdomain
		index index.php;
		root /var/www/; # the root domain of your 
					
		# tell the browser to cache all static files
		# FoOlFuuka automatically deals with forcing redownload through query parameters like style.css?v=0.8.0
		location ~* \.(css|js|htc|asf|asx|wax|wmv|wmx|avi|bmp|class|divx|doc|docx|eot|exe|gif|gz|gzip|ico|jpg|jpeg|jpe|mdb|mid|midi|mov|qt|mp3|m4a|mp4|m4v|mpeg|mpg|mpe|mpp|otf|odb|odc|odf|odg|odp|ods|odt|ogg|pdf|png|pot|pps|ppt|pptx|ra|ram|svg|svgz|swf|tar|tif|tiff|ttf|ttc|wav|wma|wri|xla|xls|xlsx|xlt|xlw|zip)$ {
			expires 31536000s;
			add_header Pragma "public";
			add_header Cache-Control "public, must-revalidate, proxy-revalidate";
			try_files $uri $uri/ /index.php;
		}

		# allow reaching the base folder, without any special magic
		# if you have index.php in the root folder it will be shown when http://foolz.us is reached	
		location / {}

		# in case you have a Wordpress blog in /blog
		location /blog {
			try_files $uri $uri/ /blog/index.php;
		}
					
		# most important part, this sends the requests to FoOlFuuka
		# in this case we add a folder to location and try_files
		location /foolfuuka {
			try_files $uri $uri/ /foolfuuka/index.php;
		}
				 
		# connection to PHP
		location ~ .php$ {
			include /etc/nginx/fastcgi_params;
			fastcgi_index index.php;
			if (-f $request_filename) {
				fastcgi_pass 127.0.0.1:9000;
				
				# you can use the sockets (10%~ faster connection) if you setup PHP with a socket file
				# fastcgi_pass unix:/dev/shm/php5-fpm.sock;
			}
		}
	}

Once you're done, restart Nginx: ::

    $ /etc/init.d/nginx restart