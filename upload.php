<?php
// THE PIWIGO PART
include 'config.php';

/////// Go grab info from Piwigo
// access the Piwigo API
$xmlstr = $piwigourl . "/ws.php?format=rest&method=pwg.categories.getImages&cat_id=$category_id&per_page=1";

/* Notes about this API call:
It is set to find only 1 result, which is the "front-most" photo in that gallery.
Depending on the sort order of the gallery, it might not find the newest photo!
The way to fix that is to EDIT the album, and for sort order choose "automatic
order : date posted, new -> old". I think the default sort order would also work
*/

print "the url we're using is $xmlstr <BR>";

// go get the XML
$xml = simplexml_load_file($xmlstr);

//print_r($xml);

// extract a bunch of info from the parsed XML file
$title = $xml->images->image->name;
$url = $xml->images->image->derivatives->medium->url;
$caption = $xml->images->image->comment;
$id = $xml->images->image[0]['id'];
$catid = $xml->images->image->categories->category[0]['id'];
$link = $xml->images->image->categories->category[0]['page_url'];

// When setting this up it's helpful to run this in a browser
	// Print out what we got- debugging
	print "The name is $title <BR>";
	print "The \$url is $url <BR>";
	print "The caption is $caption <BR>";
	print "The image id is $id <BR>";
	print "The cat id is $catid <BR>";
	print "The link is $link <BR>";
	
	// display the image (html)
	print "
	<img src='$url'>";
	
// we get a URL like this:
// http://www.chrisbeckstrom.com/piwigo/_data/i/upload/2013/02/03/20130203154810-abc60949-me.jpg

// but we want it to be like this:
// /home/chrisbeckstrom/chrisbeckstrom.com/piwigo/_data/i/upload/2013/02/03/20130203154810-abc60949-me.jpg

// str_replace
// str_replace(find,replace,string,count)
$absolutepath = str_replace("http://www.", "/home/chrisbeckstrom/", $url);
print "the absolutepath is: $absolutepath <BR>";

print "Here's loading the file from that path:<BR>
	<img src='$absolutepath'>";

/////// Check the log find out if we've already tweeted this
// the string we're looking for:
// ex)	img_id: 8064
$idlog = "img_id: " . $id;

print "\n We're looking for $idlog";

// Scan the log looking for the image ID of th
$file = file_get_contents($logfile);
if(strpos($file, $idlog)) 
	{
		echo "\n String found! \n STOPPING SCRIPT";	// if that id is found, stop!
		die;
	}
	else
	{
		echo "\n string not found! \n proceeding..."; // if that id isn't found, carry on
	}

// THE TUMBLR PART

#Requires PHP 5.3.0
 
define("CONSUMER_KEY", $yourconsumerkey);
define("CONSUMER_SECRET", $yourconsumersecret);
define("OAUTH_TOKEN", $youroauthtoken);
define("OAUTH_SECRET", $youroauthsecret);
 
function oauth_gen($method, $url, $iparams, &$headers) {
    
    $iparams['oauth_consumer_key'] = CONSUMER_KEY;
    $iparams['oauth_nonce'] = strval(time());
    $iparams['oauth_signature_method'] = 'HMAC-SHA1';
    $iparams['oauth_timestamp'] = strval(time());
    $iparams['oauth_token'] = OAUTH_TOKEN;
    $iparams['oauth_version'] = '1.0';
    $iparams['oauth_signature'] = oauth_sig($method, $url, $iparams);
    print $iparams['oauth_signature'];  
    $oauth_header = array();
    foreach($iparams as $key => $value) {
        if (strpos($key, "oauth") !== false) { 
           $oauth_header []= $key ."=".$value;
        }
    }
    $oauth_header = "OAuth ". implode(",", $oauth_header);
    $headers["Authorization"] = $oauth_header;
}
 
function oauth_sig($method, $uri, $params) {
    
    $parts []= $method;
    $parts []= rawurlencode($uri);
   
    $iparams = array();
    ksort($params);
    foreach($params as $key => $data) {
            if(is_array($data)) {
                $count = 0;
                foreach($data as $val) {
                    $n = $key . "[". $count . "]";
                    $iparams []= $n . "=" . rawurlencode($val);
                    $count++;
                }
            } else {
                $iparams[]= rawurlencode($key) . "=" .rawurlencode($data);
            }
    }
    $parts []= rawurlencode(implode("&", $iparams));
    $sig = implode("&", $parts);
    return base64_encode(hash_hmac('sha1', $sig, CONSUMER_SECRET."&". OAUTH_SECRET, true));
}
 
// NOTE!! the parameter in (file_get_contents) can totally be a URL, so there's no need to get the whole filepath 
$headers = array("Host" => "http://api.tumblr.com/", "Content-type" => "application/x-www-form-urlencoded", "Expect" => "");
$params = array("data" => array(file_get_contents("$url")),
"type" => "photo",
"caption" => "$caption");
 
oauth_gen("POST", "http://api.tumblr.com/v2/blog/$blogname/post", $params, $headers);
 
$ch = curl_init();
curl_setopt($ch, CURLOPT_USERAGENT, "CB's Piwigo to Tumblr uploader");
curl_setopt($ch, CURLOPT_URL, "http://api.tumblr.com/v2/blog/$blogname/post");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
 
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: " . $headers['Authorization'],
    "Content-type: " . $headers["Content-type"],
    "Expect: ")
);
 
$params = http_build_query($params);
 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
 
$response = curl_exec($ch);
print $response;

/////// Log it
// get the current time
$rightnow = date("Y-m-d H:i:s"); 

// append this to the log
$fh = fopen($logfile, 'a') or die("can't open file");
$stringData = "$rightnow  img_id: $id '$caption' \n";
fwrite($fh, $stringData);
fclose($fh);

// text CB re: the tumblr post
$to = $yourcellemail;
$subject = "pwtweet";
$message = "Tweeted: $id $caption";
$from = $youremailss;
$headers = "From:" . $from;
mail($to,$subject,$message,$headers);
echo "Mail Sent.";
mysql_close();
?>
