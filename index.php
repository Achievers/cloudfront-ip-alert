<?php
/**
 * Documentaiton
 * http://docs.aws.amazon.com/general/latest/gr/aws-ip-ranges.html
 */
$type ="CLOUDFRONT";
$lastUpdate = 'ip-ranges-old.json';
$url = 'https://ip-ranges.amazonaws.com/ip-ranges.json';
$ca = 'ca-bundle.crt';

$ch = curl_init();

//curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_URL, $url);
//curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CAPATH, $ca);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Content-Type: application/json'
));

$data = curl_exec($ch);
if(curl_errno($ch))
{
    print_r(curl_getinfo($ch));
    echo 'Curl error: ' . curl_error($ch);
}

curl_close($ch);

$decodedData = json_decode($data, true);
$syncToken = $decodedData['syncToken'];

//get last file token
$lastSyncToken = false;
if (file_exists($lastUpdate)) {
    $oldData = json_decode(file_get_contents($lastUpdate), true);
    $lastSyncToken = $oldData['syncToken'];
}

//If there is a different in sync token, output the values.
if ($syncToken != $lastSyncToken) {
    //the IPs
    $output = array();
    $oldOutput = array(); //stores the decoded old data JSON

    //save a copy.
    file_put_contents($lastUpdate, $data);

    //Loop through it
    foreach($decodedData['prefixes'] as $prefixes) {
        if ($prefixes['service'] == $type) {
            $output[] = $prefixes['ip_prefix'];
        }
    }

    //Loop through old data
    foreach($oldData['prefixes'] as $prefixes) {
        if ($prefixes['service'] == $type) {
            $oldOutput[] = $prefixes['ip_prefix'];
        }
    }

    //Sort outputs so we can compare
    natsort($oldOutput);
    natsort($output);

    //If the outputs are different, then something has changed. 
    if ($oldOutput === $output) {
        echo 'Amazon updated the file, but no changes detected for CloudFront.';
    } else {
        echo 'Changes detected. <br/>';
        foreach($output as $ip) {
            echo $ip . "<br/>";
        }
    }
} else {
    echo 'No changes detected.';
}
?>