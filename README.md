cloudfront-ip-alert
===================
Sends an email when Amazon CloudFront IP addresses are updated

=======
Basic useful feature list
=========================

 * Lightweight Library
 * Checks Amazon CloudFront IP and alert user.
 * Can easily integrate with email notification or 3rd party notification.

Description
===========

Installation
============
The easiest way to install is via Composer.
```
{
    "require": {
        "achievers/cloudfront-ip-alert": "0.*"
    }
}
```

Usage
=====
Take a look at index.php for an example. Here is a quick start:
```
require_once('CloudFront/IPWhiteList.php');

use Achievers\CloudFront\IPWhiteList;

$cloudFrontIP = new IPWhiteList(); // Initialize a class
$listOfIPs = $cloudFrontIP->getLastCloudFrontFile(); //Get the latest CloudFront file
$data = $cloudFrontIP->checkIP($listOfIPs); //Return a list of changes
```

Config
======
You can pass in configuration for customization
cloudFrontType
: The CloudFront file from Amazon contains IP of different services. Default is "CLOUDFRONT".

cloudFrontLastFile
: The is the full file path where we will store the most recent file as. ie. '/tmp/ip-ranges-old.json'.

cloudFrontUrl
: The URL to Amazon CloudFront file. Default 'https://ip-ranges.amazonaws.com/ip-ranges.json'


License
=======
MIT License
