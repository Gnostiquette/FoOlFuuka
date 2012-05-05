Editing the themes without messing up
#####################################

While making an entire is not a difficult task, it is difficult to keep up with the FoOlFuuka upgrades that might break your original theme.

Because of the rather heavy code changes that the themes go through over time, we've implemented a child theme system, in order to allow you to edit only smaller parts of the pre-installed themes. If you're PHP-savvy you can also implement new interfaces through the theme controller.

What is a child theme?
**********************

The single pages of a theme are called "views", and each of them is a single file, like ``index.php`` or ``board.php``. Child themes allow you to override views from other themes

You can also just override CSS by adding a style.css file.

What may break after an upgrade
*******************************

Don't trust the stability of the themes and of the child theme system. We will push forward on the theme system and you will have to adapt your child theme accordingly.

* Changing filenames

  The pre-installed themes may change the default name of a file. If your override stops working, check if the filename of the original file has been changed.
  
* Changing functions

  Especially in the most complex views, it is likely that we'll drop support for some functions. Occasionally you will have to keep up with the changes in order to keep having an updated theme.
  
* Config file changes

  The config files are mostly stable, but we might add override variables or change their name.  

.. note:: 

	Don't make database calls in your theme, and don't do anything that may put security at stake. A theme is about displaying data, so don't add functions for data modification. If you need data modification, consider making a plugin instead.
  
Configuration
*************

Create a new folder for your theme. At the very least you need a folder and the configuration file into it: ::

	/content/themes/yourtheme/
	/content/themes/yourtheme/theme_config.php
	
You must copy ``theme_config.php`` from the theme you are extending. You will find it in example at ::

	/content/themes/default/theme_config.php
	
Edit it by following the comments in the file itself.
  
You can now go in the admin panel and activate your new theme. Because no other file has been added, it will look exactly like the theme you're overriding.

How to override a view
**********************

Copy only the view you're interested in from the folder of the theme you're overriding. In example, if you were going to add an image to the index page: ::

	/content/themes/default/views/index.php > /content/themes/yourtheme/views/index.php
	
Then edit the new file as you like, so in example add a cute image below the board list. Your changes will be available as soon as you hit save, just make sure that you have selected your child theme.

.. note::

	Admins can always change the theme they are viewing to the ones that aren't available for the public. Visit the ``/functions/theme/yourtheme`` page with your browser to enable the new theme.

