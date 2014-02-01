=== PressBackup ===
Contributors: infinimediainc
Tags: pressbackup, pressbackup express, backup, cron, schedule, scheduling, automatic, database ,files, wp-content, Amazon S3, S3, server folder, Dropbox, free
Requires at least: 3.0
Tested up to: 3.8.1
Stable tag: 2.5.1
License: GPLv2

Easily backup your WordPress site and use our cloud storage service for free! or choose to save backups on DropBox, Amazon S3, or a server folder.

== Description ==

PressBackup is the easiest plugin available for backing up your wordpress site automatically.

This plugin allows your wordpress blog administrator to schedule backups of your entire site, restore backups, and migrate your site in the event of your server failure or moving.

PressBackup is free to use, but you must create a PressBackup account.

http://pressbackup.com


== Installation ==

* Download plugin and copy it into your folder: wp-content/plugins
* Activate the plugin
* Go to open PressBackup from the admin menu
* Create free account, o select one of our paid memberships

  DONE :)

== Requirements ==

* Sufficient disk space to store the temporary zip of your site.
* GZip extension or zip app via shell
* Curl extension (php safe mode off)

= Warnings =

To restore the backups you need to change permissions of ”wp-content” folders subfolders and files to 777 (read and write for all).
Once PressBackup finish with the restore proccess will change permission back to original values

Please be careful about doing a restore from a previous version of Wordpress if you have upgraded Wordpress core files between backups.
We cannot ensure a smooth transition between each upgrade.

We also recommend running a manual backup now after each upgrade you perform

== INCOMPATIBILITIES ==

* some IIS web servers
* Some versions of LiteSpeed web servers
* web hosting from ecowebhosting.co.uk

== Screenshots ==

1. A simple and easy way to create and manage backups

== Changelog ==

= 2.5.1 =
* Improved Comunication with PressBackup
* Fixed minor bugs

= 2.5 =
* WordPress 3.8 compatible
* Improved Database backup and restore functionality
* Improved Comunication with PressBackup
* Fixed Amazon S3

= 2.4.1 =
* fixed minor bugs from last version

= 2.4 =
* simplified wizard configuration
* cancel backup option

= 2.3 =
* fixed restore/migration function
* improved restore messages
* improved comunication with PressBackup Server
* added compatibility with developer package
* added new and safety background creation function

= 2.2.1 =
* fixes bugs from FramePress update
* fixed upload and restore page
* improved background process creation (option hard)


= 2.2 =
* Updated FramePress
* fixed schedules problems
* improved DataBase backup creation and restore
* improved database Error reporting

= 2.1.1 =
* Improved user regitration
* improved Error reporting
* Fixed backup name standardization

= 2.1 =
* Fixed bug on including config.php
* Fixed bug on including class PressbackupConfig
* added partial compatibility with IIS servers

= 2.0.2 =
* Fixed backup list paginator

= 2.0.1 =
* Now you must have a PressBackup [free] account to can use the plugin
* Free users now can use our cloud storage service! and therefore activate scheduled backups feature!
* Multiple storage services configuration for paid accounts
* fixed fatal error Pressbackupconfig not found
* fixed Download backup

= 1.6 =
* Change UI styles
* Added more functionality for "Express" users
* Fixed minor bugs
* Removed deprecated functionality

= 1.5.1 =
* Fixed follow location bug for Dropbox and Express / Pro Users
* Fixed minor bugs intruced in version 1.5

= 1.5 =
* Integrated PressBackup  with PressBackup Express, there is only one plugin
* Simplified Storage Server configuration for PressBackup Express / Pro
* sync cron jobs with UTC-0

= 1.4.7 =
* fix for no occidental characers on backup name
* Added sync to our server, on plugin confguration for Pro users

= 1.4.6 =
* Fixed next schedule backup.
* Added background process creation cheker on wizzard
* Added secure webs cheker on wizzard
* Fixed bad caracter on backup name using dropbox

= 1.4.5 =
* Added dropboxr as backup store service
* Fixed Class name problems on S3.php

= 1.4 =
* Added advanced Backups settings. Now yo can specify when backup each part of your content
* Added "keep last 30 copies" to backup settings
* implemented Internationalization (I18n), ¡Ahora esta disponible en ESPAÑOL!
* Improved data organization in UI
* Fixed migration bug, "it says restored but nothing heppen"
* Fixed no error message bug

= 1.3.1 =
* Fixed minor bugs about accented characters on backup name

= 1.3 =
* Added local server as backup store service
* Fixed minor bugs
* Changed UI styles

= 1.2 =
* Fixed: backup now (download) for users with no credentials of S3 or PressBackup Pro
* Fixed zip creation without php-zip module
* Added Compatibility tab
* Fixed minor bugs

= 1.1 =
* Fixed: backup now (download) for users with no credentials of S3 or PressBackup Pro
* Added zip creation without php-zip module (when it is available)

= 1.0 =
* Changed the way to make backups ( no PHP type dependent )

= 0.7.1 =
* Added popup off function
* Added a fix for some PHP-CGI users

= 0.7 =
* Improved data organization in UI
* Fixed "wrong file type" on upload
* Changed the way to make backups
* Updated version of Framepress core
* Added more restrictions for incompatible hosts
* Added more info about errors
* Added a fix for some PHP-CGI users

= 0.6.7 =
* Fixed minor bugs
* Added Option for european S3 servers
* Added a progress bar For S3 uploads

= 0.6.6.4  =
* Fixed minor bugs

= 0.6.6.3  =
* Fixed minor bugs on main file
* added host info page

= 0.6.6.2  =
* Fixed minor bugs on dashboard page

= 0.6.6.1  =
* Fixed minor bugs

= 0.6.6 =
* Fixed bug for PressBackup Pro users that prevented send of backups

= 0.6.5.1 =
* Change the way to inform errors to prevent WP blocking

= 0.6.5 =
* Improved backup sorting
* Improved data organization in UI
* Updated version of Framepress core

= 0.6.4 =
* Eliminated need to CHMOD your tmp folder
* Streamlined install process
* Added requirement for GZip

= 0.6.3 =
* Changed the way to inform permissions errors
* Added more integrity checks for backups

= 0.6.2 =
* Fixed bug for S3 users that prevented creation of buckets

= 0.6.1 =
* Fixed reload htaccess

= 0.6 =
* Change auto-scheduling system

= 0.5.2 =
* Fix scheduling bug that was failing to trigger backups
* Fix a bug that could result in broken backup files

= 0.5.1 =
* Fix a bug that caused lock ups on large sites
