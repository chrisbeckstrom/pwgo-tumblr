CB's Piwigo Auto-post to Tumblr Script

WHAT IT DOES:
Upload a picture to a particular album in Piwigo, and this script
will grab it and post it to your Tumblr

HOW IT WORKS
* The script runs (perhaps as a cron job) and access your Piwigo through its API
* If there is a new* image in that album, it grabs it and sends it to Tumblr
* Whoohoo! Simul-posting!
* Thanks to the efforts of Abraham Williams and Lucas Christian- all I did was
put their stuff together and make it work with Piwigo

	*It knows whether it's "new" or not because the script keeps a log
	of the images it's already posted, and looks for the image ID before
	posting again

SETUP

0.	Rename config.dist.php to config.php, fill it outexcept for the oauth token and oauth secret
1.	Run the connect.php script to get an oauth token and oauth token secret
	[This only has to happen ONCE]
2. 	Plug those values into config.php
3.	Every time the upload.php script is run

WHAT THE FILES DO

config.dist.php :
	rename to config.php
	replace the default info with your own, else it won't work!
	
connect.php	:
	connects to tumblr with the app info, asks for permission
	You only need to run this script ONCE
	
callback.php :
	after you run connect.php, it shoots you back here so you can grab the oauth info
	You only need to run this script ONCE
	
upload.php :
	connects to your piwigo's API and gets the most recently-added image
	This is the script you can run as a cron job
	
log.txt : 
	keeps a running log of the image ids that have been posted

tumblroauth/ : 
	The first PHP Library to support OAuth for Tumblr's REST API.
	originally by Abraham Williams (abraham@abrah.am http://abrah.am) for Twitter
	modified for Tumblr by Lucas Christian
	(https://groups.google.com/forum/#!topic/tumblr-api/g6SeIBWvsnE)
