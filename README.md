![PLEX Logo](https://github.com/Tensai75/plpp/raw/master/favicon.ico)

PHP Library Presenter for PLEX [PLPP]
=====================================

Version
-------

v1.0 - 01.01.2018 - first release on GitHub


Download
--------

https://github.com/Tensai75/plpp/releases


Docker Image
------------

A docker image has kindly been provided by [christronyxyocum](https://github.com/christronyxyocum):

https://hub.docker.com/r/tronyx/docker-plpp/


Description
-----------

Provides a PHP front end to simply present PLEX libraries on the web without the possibility to play or download the library items. Currently movie/home video, TV show, music and photo/picture libraries are supported.


Background
----------

I always was looking for a program to present my PLEX libraries to my friends and relatives. The PLEX web gui is great, but not suitable for this purpose because I do not want them all to have access to the web gui. Hence I started to code my own solution, which should have a similar look as the PLEX web gui.
However, I haven't coded in PHP for years. Actually I did not program anything for years. So this project is also a teaching project for me especially to learn OOP. Therefore the code might be pretty ugly but I wrote this program for myself and myself solely. I release this program into public domain without any warranty. It worked for me, but it must not for you.
If you find bugs you can gladly post them here but don't expect me to correct them immediately (as a father of three little girls, I have a very busy life).


Features
--------

 * directly accesses a PLEX server via http API
 * slider view for the front page showing recently added library items (bxslider jquery plug-in)
 * 2 different views for the libraries:
   * thumbnail view
   * data table list view (dataTables jquery plug-in)
 * ajax pop-up for the detail view of a movie/tv show/music library item
 * lightbox gallery pop-up for the photos/pictures of photo/picture libraries
 * password protected admin section to change the configuration, e.g.:
   * set up the connection to the PLEX server
   * exclude libraries from being shown
 * template and CSS based and hence themeable
 * default template/theme based on bootstrap 3
 * posters and thumbnails are served via the PHP script to prevent the PLEX token to be disclosed in the generated html code
 * the images are cached locally in order to speed up image delivery


Requirements
------------

 * a webserver with PHP (tested with apache v2.2 and PHP v5.6)
 * PHP GD extension for image manipulation
 * a running PLEX server
 * a recent browser with active javascript and cookies accepted


Possible features for future releases
-------------------------------------

 * include stream/part information in details view
 * make the amount of information to be shown per media type configurable
 * download the library list in different file formats
 * support additional languages


Install instructions
--------------------

Clone the repository to your webserver root or unpack zip file and upload contents to a webserver.
Change permissions for the following folders:

 * plpp/cache --> chmod 777
 * plpp/config --> chmod 777

Point your web browser to "plpp/settings.php". You are first prompted to set the password for the settings section. Thereafter you can login to configure the settings.


Screenshots
-------------

![Screenshot 1](https://github.com/Tensai75/plpp/raw/master/screenshots/plpp1.jpg)
![Screenshot 2](https://github.com/Tensai75/plpp/raw/master/screenshots/plpp2.jpg)
![Screenshot 3](https://github.com/Tensai75/plpp/raw/master/screenshots/plpp3.jpg)
![Screenshot 4](https://github.com/Tensai75/plpp/raw/master/screenshots/plpp4.jpg)
![Screenshot 5](https://github.com/Tensai75/plpp/raw/master/screenshots/plpp5.jpg)
![Screenshot 6](https://github.com/Tensai75/plpp/raw/master/screenshots/plpp6.jpg)


Revision history
----------------

v1.0

 * First release on GitHub
