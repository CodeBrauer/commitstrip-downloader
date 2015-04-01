<?php
/* -- CONFIGURATION -----------------------------------*/

// MUST have a trailing slash! - also will try to create the dir if not existing (recursive, can be relative)
$path_for_images = './images/';


// everything below is the script. you can edit it of cause ...
// ... but maybe you will break it. Or improve it! - make a pull request! :)

/* -- some settings for running in cli mode. -----------------------------------*/
set_time_limit(0);
date_default_timezone_set('Europe/Berlin');
$script_start = microtime(true);

// DOMDocument throws alot of Notices and Warning because it don't knows HTML5 really good...
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

/* -- functions start. -----------------------------------*/
function curl_url_get_contents($url) {
    $ch = curl_init();
    $options = array(
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL            => $url,
        CURLOPT_HEADER         => false,
    );
    curl_setopt_array($ch, $options);
    $html = curl_exec($ch);

    if ($html === false) {
        echo 'ERROR: '.curl_error($ch). PHP_EOL;
    }

    curl_close($ch);
    return $html;
}

function get_last_page() {
    $url = "http://www.commitstrip.com/en/page/2/";
    $dom = new DOMDocument();
    // load html page
    $dom->loadHTML(curl_url_get_contents($url));
    $dom->preserveWhiteSpace = false;
    
    // get href of anchor that has the class "last" 
    $finder    = new DomXPath($dom);
    $classname = "last";
    $nodes     = $finder->query("//*[contains(@class, '$classname')]");

    $last_url = $nodes->item(0)->getAttribute('href');
    if (is_numeric(basename($last_url))) {
        return (int)basename($last_url);
    }
    return false;
}
/* -- functions end. -----------------------------------*/

// start the script!
$last_page = get_last_page();
echo PHP_EOL . "$last_page images found..." . PHP_EOL;

// check there is an dir to save to
if (!is_dir($path_for_images)) {
    $dir = mkdir($path_for_images, 0777, true);
    if ($dir === false) {
        exit('Error: You need to set the permission correctly!.');
    }
}

// for each page ..
for ($i = 1; $i <= $last_page; $i++) {

    if ($i == 1) { // page one is special
        $url = "http://www.commitstrip.com/en/";
    } else {
        $url = "http://www.commitstrip.com/en/page/$i/";    
    }

    // download website to string
    $html = curl_url_get_contents($url);

    $doc = new DOMDocument();
    
    $doc->loadHTML($html);
    $doc->preserveWhiteSpace = false;

    // get all image-elements
    $images = $doc->getElementsByTagName('img');

    // get all urls of all images
    $urls = [];
    foreach ($images as $key => $image) {
        $urls[] = $image->getAttribute('src');
    }

    $the_posted_image = FALSE;

    // filter the main url...
    foreach ($urls as $key => $value) {
        if (preg_match('/\/uploads\/.+\.jpg$/', $value)) {
            $the_posted_image = $value; // thats the image i've searched for .... i hope
        }
    }

    if ($the_posted_image === FALSE) {
        echo "($i/$last_page) No image found..." . PHP_EOL;
        continue;
    }

    // now create a good filename...
    $url_parts = explode('/', $the_posted_image);
    $url_parts = array_reverse($url_parts); // .. done!

    // [2] => year, [1] => month, [0] => filename
    $filename = "$url_parts[2]-$url_parts[1]-$url_parts[0]";

    // check file exists already
    if (file_exists($path_for_images.$filename)) {
        echo "($i/$last_page) File skipped: $the_posted_image" . PHP_EOL;
        continue;
    }
    
    // download it...
    $res = file_put_contents($path_for_images.$filename, curl_url_get_contents($the_posted_image));

    // I hope it does not fail - Why should it fail at all?!
    if ($res !== FALSE) {
        echo "($i/$last_page) Downloaded: $the_posted_image" . PHP_EOL;
    } else {
        echo "($i/$last_page) Failed downloading: $the_posted_image" . PHP_EOL; 
    }
}

/* -- script end info -----------------------------------*/
$script_duration = microtime(true) - $script_start;
$script_duration = number_format($script_duration, 2, ',', '.');
echo PHP_EOL;
echo 'Memory Usage: ' . memory_get_usage(true) / 1024  . ' kB' . PHP_EOL;
echo 'Script Duration: ' . $script_duration . ' second(s)' . PHP_EOL;
