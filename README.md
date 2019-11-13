Netcash Pay Now PrestaShop Credit Card Payment Module
=========================================================

Introduction
------------
PrestaShop is an open source e-commerce platform.

This is the Netcash Pay Now module which gives you the ability to take credit card transactions online.

Download Instructions
-------------------------

Download the files from the location below to a temporary location on your computer.

Configuration
-------------

Prerequisites:

You will need:

* Netcash Pay Now login credentials
* Netcash Pay Now Service key
* PrestaShop admin login credentials

Netcash Pay Now Gateway Server Configuration Steps:

1. Log into your Netcash Pay Now Gateway Server configuration page:
	https://merchant.netchash.co.za/SiteLogin.aspx
2. Go to Account / Profile
3. Click Netcash Connect
4. Click Pay Now
5. Make a note of your Service key

Netcash Pay Now Callback

6. Choose the following URLs for your Notify, Redirect, Accept and Decline URLs:
	http(s)://(www.)your_domain_name.co.za/modules/paynow/paynow_callback.php

Netcash Pay Now Plugin Installation and Activation

7. Upload the contents of the downloaded ZIP archive to your site.
	In _/modules/_ there should be a _paynow/_ folder.
	No files should be overriden.
8. Login to your PrestaShop website as admin

PrestaShop Configuration

9. Select "Modules" > "Module Manager" in the admin menu.
10. Look for or search for "PayNow" and click "Install".
11. Put in you Service Key and click "Save".
12. Turn off debugging if you're in a production/live environment.


Tested with PrestaShop v1.7


Issues / Feedback / Feature Requests
------------------------------------

Please do the following should you encounter any problems:

* Ensure at Netchash that your Accept and Decline URLs are "http://www.example.com/modules/paynow/paynow\_callback.php".
For example, if your site is 'www.mysite.co.za', use:
http://www.mysite.co.za/modules/paynow/paynow\_callback.php
* Enable Debugging in the Pay Now module
* Ensure that there's a paynow.log file in _/modules/paynow_ and that it is writeable.

Turn OFF debugging when you are in a production environment.
