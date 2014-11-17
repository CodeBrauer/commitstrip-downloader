<?php
if (file_exists('../../ref/ref.php')) {
    include '../../ref/ref.php';
}

// DOMDocument throws alot of Notices and Warning because it don't knows HTML5 really good...
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

set_time_limit(0);

$last_page = 573;

// check there is an dir to save to
if (!is_dir('images')) {
    mkdir('./images/');
}

for ($i=1; $i <= $last_page ; $i++) { 
    if ($i == 1) {
        $url = "http://www.commitstrip.com/en/";
    } else {
        $url = "http://www.commitstrip.com/en/page/$i/";    
    }

    // download website to string
    $ch = curl_init();
    $options = array(
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL            => $url,
    );
    curl_setopt_array($ch, $options);
    $html = curl_exec($ch);
    curl_close($ch);

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

    // filter the main url...
    foreach ($urls as $key => $value) {
        if (preg_match('/upload/', $value)) {
            $the_posted_image = $value; // thats the image i've searched for .... i hope
        }
    }

    // now create a good filename..
    $url_parts = explode('/', $the_posted_image);
    $url_parts = array_reverse($url_parts);

    $filename = "$url_parts[2]-$url_parts[1]-$url_parts[0]"; // 2 => year, 1 => month, 0 => filename

    // and download it..

    $res = file_put_contents('./images/'.$filename, file_get_contents($the_posted_image));

    if ($res !== FALSE) {
        echo "($i/$last_page) Downloaded: $the_posted_image\n";
    }
}
