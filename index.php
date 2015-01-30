<?php
/**
 * Documentaiton
 * http://docs.aws.amazon.com/general/latest/gr/aws-ip-ranges.html
 */
require_once('CloudFront/IPWhiteList.class.php');

use Achievers\CloudFront\IPWhiteList;

$cloudFrontIP = new IPWhiteList();
$data = $cloudFrontIP->getLastCloudFrontFile();
$cloudFrontIP->checkIP($data);

?>