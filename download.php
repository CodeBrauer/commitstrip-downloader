<?php
set_time_limit(0);

// DOMDocument throws alot of Notices and Warning because it don't knows HTML5 really good...
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

// include $_SERVER['DOCUMENT_ROOT'].'/ref/ref.php'; - local dev script..

function curl_url_get_contents($url) {
    $ch = curl_init();
    $options = array(
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL            => $url,
    );
    curl_setopt_array($ch, $options);
    $html = curl_exec($ch);
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
    $nodes = $finder->query("//*[contains(@class, '$classname')]");

    $last_url = $nodes->item(0)->getAttribute('href');
    if (is_numeric(basename($last_url))) {
        return (int)basename($last_url);
    }
    return false;
}

$last_page = get_last_page();
echo "\n$last_page images found...\n";

// check there is an dir to save to
if (!is_dir('images')) {
    mkdir('./images/');
}

for ($i = 1; $i <= $last_page; $i++) { 
    if ($i == 1) {
        $url = "http://www.commitstrip.com/en/";
    } else {
        $url = "http://www.commitstrip.com/en/page/$i/";    
    }

    // download website to string
    $html = curl_url_get_contents($url);

    // create DOM Document
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
        if (preg_match('/upload/', $value)) {
            $the_posted_image = $value; // thats the image i've searched for .... i hope
            break; // cause we will only the first image .. maybe there is an ad after the image..
        }
    }

    if ($the_posted_image === FALSE) {
        echo "($i/$last_page) No image found...\n";
        continue;
    }

    // now create a good filename..done
    $url_parts = explode('/', $the_posted_image);
    $url_parts = array_reverse($url_parts);

    // 2 => year, 1 => month, 0 => filename
    $filename = "$url_parts[2]-$url_parts[1]-$url_parts[0]";

    // and download it..
    if (file_exists('./images/'.$filename)) {
        echo "($i/$last_page) File skipped: $the_posted_image\n";
        continue;
    }
    
    $res = file_put_contents('./images/'.$filename, curl_url_get_contents($the_posted_image));

    if ($res !== FALSE) {
        echo "($i/$last_page) Downloaded: $the_posted_image\n";
    } else {
        echo "($i/$last_page) Failed downloading: $the_posted_image\n"; 
    }
}
