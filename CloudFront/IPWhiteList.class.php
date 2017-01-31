<?php
namespace Achievers\CloudFront;

class IPWhiteList
{
    /** string[] $config */
    private $config;

    /**
     * Constructor
     * @param string[] $config
     */
    public function __construct($config = [])
    {
        $defaultConfig['cloudFrontFolder'] = 'CloudFront/';
        $defaultConfig['cloudFrontType'] ="CLOUDFRONT";
        $defaultConfig['cloudFrontLastFile'] = $defaultConfig['cloudFrontFolder'] . 'ip-ranges-old.json';
        $defaultConfig['cloudFrontUrl'] = 'https://ip-ranges.amazonaws.com/ip-ranges.json';
        $defaultConfig['cloudFrontCaCrt'] = $defaultConfig['cloudFrontFolder'] . 'ca-bundle.crt';

        //Overwrite the config with the custom configs.
        $this->config = array_merge($defaultConfig, $config);
    }

    /**
     * A simple funcion to fetch the latest CloudFront IP from Amazon
     */
    public function getLastCloudFrontFile()
    {
        $ch = curl_init();

        //curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $this->config['cloudFrontUrl']);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CAPATH, $this->config['cloudFrontCaCrt']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json'
        ));

        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            print_r(curl_getinfo($ch));
            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);

        return $data;
    }


    /**
     * This function checks the difference between the last known file and the most updated file.
     * We then keep the last known file so that the next time we can do a diff to see what changed.
     *
     * @param string $data The file content dof the latest CloudFront file.
     * @return string[] an array that contains the list of last known IPs and the list of modified IPs. Also
     * has a flag to indicate if the IP has changed or not.
     */
    public function checkIP($data)
    {
        $decodedData = json_decode($data, true);
        $lastDecodedData = array();
        $syncToken = $decodedData['syncToken'];

        //get last file token
        $lastSyncToken = false;
        if (file_exists($this->config['cloudFrontLastFile'])) {
            $lastDecodedData = json_decode(file_get_contents($this->config['cloudFrontLastFile']), true);
            $lastSyncToken = $lastDecodedData['syncToken'];
        }

        //Loop through old data
        $lastListOfIPs = array(); //stores the decoded data JSON
        foreach ($lastDecodedData['prefixes'] as $prefixes) {
            if ($prefixes['service'] == $this->config['cloudFrontType']) {
                $lastListOfIPs[] = $prefixes['ip_prefix'];
            }
        }
        natsort($lastListOfIPs);
        $result = array();
        $result['lastListOfIPs'] = $lastListOfIPs;
        $result['currentListOfIPs'] = $lastListOfIPs;  //same as before unless it has been modified.
        $result['hasChanged'] = false; //A flag that indicate if the IP has changed.

        //If there is a different in sync token, output the values.
        if ($syncToken != $lastSyncToken) {
            //the IPs
            $listOfIPs = array();

            //save a copy.
            file_put_contents($this->config['cloudFrontLastFile'], $data);

            //Loop through it
            foreach ($decodedData['prefixes'] as $prefixes) {
                if ($prefixes['service'] == $this->config['cloudFrontType']) {
                    $listOfIPs[] = $prefixes['ip_prefix'];
                }
            }

            //Sort outputs so we can compare
            natsort($listOfIPs);

            //If the outputs are different, then something has changed.
            $result['currentListOfIPs'] = $listOfIPs;
            if ($lastListOfIPs === $listOfIPs) {
                $result['message'] = 'Amazon updated the file, but no changes detected for CloudFront.';
            } else {
                $result['hasChanged'] = true;
                $result['message'] = 'Changes detected.';
            }
        } else {
            $result['message'] = 'No changes detected.';
        }

        return json_encode($result);
    }
}
