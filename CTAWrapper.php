<?php
/**
 * Class CTAWrapper
 *
 * 2016 - Jake Siegers - MIT License
 *
 * Can get Data From all 3 CTA API's
 *  - CTA Train Tracker API (Requires API KEY)
 *  - CTA Bus Tracker API (Requires API KEY)
 *  - Customer Alerts API (No key required)
 */
class CTAWrapper{

    /**
     * Url's of the different API's
     */
    public static $API_URLS = array(
        'alerts'        => 'http://www.transitchicago.com/api/1.0/',
        'bus'           => 'http://www.ctabustracker.com/bustime/api/v1/',
        'train'         => 'http://lapi.transitchicago.com/api/1.0/',
        'trainStops'    => 'http://data.cityofchicago.org/resource/8mj8-j3c4.json'
    );

    /**
     * All of the known endpoints and locations for each API
     */
    public static $API_ENDPOINTS = array(
        'alerts' => array(
            'routes'            => 'routes.aspx',
            'alerts'            => 'alerts.aspx'
        ),
        'bus' => array(
            'time'              => 'gettime',
            'vehicles'          => 'getvehicles',
            'routes'            => 'getroutes',
            'routeDirections'   => 'getdirections',
            'stops'             => 'getstops',
            'patterns'          => 'getpatterns',
            'predictions'       => 'getpredictions',
            'serviceBulletins'  => 'getservicebulletins'
        ),
        'train' => array(
            'arrivals'          => 'ttarrivals.aspx',
            'followThisTrain'   => 'ttfollow.aspx',
            'locations'         => 'ttpositions.aspx'
        )
    );

    /**
     * Useful list of all the train lines.
     * For whatever reason,
     * the alertsAPI data, and the stopsAPI use different id's for referencing these lines,/
     * I had to split the values up into 2 index's.
     * 
     * alertId => array('Nice Name','trainStopsId')
     * 
     */
    public static $TRAIN_LINES = array(
        'Red'   => array(
            'niceName'      => 'Red Ln',
            'trainStopsId'  => 'red'
        ),
        'Blue'  => array(
            'niceName'      => 'Blue Ln',
            'trainStopsId'  => 'blue'
        ),
        'Brn'   => array(
            'niceName'      => 'Brown Ln',
            'trainStopsId'  => 'brn'
        ),
        'G'     => array(
            'niceName'      => 'Green Ln',
            'trainStopsId'  => 'g'
        ),
        'Org'   => array(
            'niceName'      => 'Orange Ln',
            'trainStopsId'  => 'o'
        ),
        'P'     => array(
            'niceName'      => 'Purple Ln',
            'trainStopsId'  => 'p'
        ),
        'Pexp'  => array(
            'niceName'      => 'Purple Ln Exp',
            'trainStopsId'  => 'pexp'
        ),
        'Pink'  => array(
            'niceName'      => 'Pink Ln',
            'trainStopsId'  => 'pnk'
        ),
        'Y'     => array(
            'niceName'      => 'Yellow Line',
            'trainStopsId'  => 'y'
        )
    );

    /**
     * CTAWrapper constructor.
     * @param array $config
     */
    function __construct($config = array()){
        $this->trainApiKey = null;
        $this->busApiKey = null;

        if(isset($config['trainApiKey'])){
            $this->trainApiKey = $config['trainApiKey'];
        }

        if(isset($config['busApiKey'])){
            $this->busApiKey = $config['busApiKey'];
        }

        if(isset($config['trainStopsApiKey'])){
            $this->trainStopsApiKey = $config['trainStopsApiKey'];
        }
    }

    /**
     * Make a request to the alert API
     * @param $endpoint
     * @param array $params
     * @return array
     * @throws Exception
     */
    function alertApiCall($endpoint, $params = array()){
        if(!isset(self::$API_ENDPOINTS['alerts'][$endpoint])){
            throw new Exception("Unknown Alert Endpoint");
        }
        return $this->fetchXmlApiData(self::$API_URLS['alerts'].self::$API_ENDPOINTS['alerts'][$endpoint].$this->generateGetVariables($params));
    }

    /**
     * Make a request to the bus API
     * @param $endpoint
     * @param array $params
     * @return array
     * @throws Exception
     */
    function busApiCall($endpoint, $params = array()){
        if(!isset($this->busApiKey)){
            throw new Exception('Bus API key required!');
        }
        $params['key'] = $this->busApiKey;
        if(!isset(self::$API_ENDPOINTS['bus'][$endpoint])){
            throw new Exception("Unknown Bus Endpoint");
        }
        return $this->fetchXmlApiData(self::$API_URLS['bus'].self::$API_ENDPOINTS['bus'][$endpoint].$this->generateGetVariables($params));
    }

    /**
     * Make a request to the train API
     * @param $endpoint
     * @param array $params
     * @return array
     * @throws Exception
     */
    function trainApiCall($endpoint, $params = array()){
        if(!isset($this->trainApiKey)){
            throw new Exception('Train API key required!');
        }
        $params['key'] = $this->trainApiKey;
        if(!isset(self::$API_ENDPOINTS['train'][$endpoint])){
            throw new Exception("Unknown Train Endpoint");
        }
        return $this->fetchXmlApiData(self::$API_URLS['train'].self::$API_ENDPOINTS['train'][$endpoint].$this->generateGetVariables($params));
    }

    /**
     * Make a request to the L-Stop API (Oddly separate...)
     * @param array $params
     * @return array
     * @throws Exception
     */
    function trainStopsApiCall($params = array()){
        //This keys is optional, to prevent throttling.
        if(isset($this->trainStopsApiKey)){
            $params['$$app_token'] = $this->trainStopsApiKey;    
        }
        return $this->fetchJsonApiData(self::$API_URLS['trainStops'].$this->generateGetVariables($params));
    }

    /**
     * Fetch array formatted data from a JSON API url
     * @param $url
     * @return array
     * @throws Exception
     */
    private function fetchJsonApiData($url){
        $response = file_get_contents($url);
        $arrayResponse = json_decode($response,true);
        if(is_null($arrayResponse)){
            throw new Exception("Failed to json_decode() API response");
        }
        return $arrayResponse;
    }

    /**
     * Fetch array formatted data from an XML API url.
     * @param $url
     * @return array mixed
     */
    private function fetchXmlApiData($url){
        $xmlResults = simplexml_load_file($url,null,LIBXML_NOCDATA);
        $jsonResults = json_encode($xmlResults);
        $arrayResults = json_decode($jsonResults,TRUE);
        return $arrayResults;
    }

    /**
     * Converts an array into a string of get variables
     * @param array $data
     * @return string
     */
    private function generateGetVariables($data){
        $getStr = "";
        $varCount = 0;
        foreach($data as $key => $value){
            $value = $this->convertValuesToString($value);
            if($value == ''){
                continue;
            }
            if($varCount == 0){
                $getStr.="?";
            }else{
                $getStr.="&";
            }
            $getStr .=urlencode($key)."=".urlencode($value);
            $varCount++;
        }
        return $getStr;
    }

    /**
     * Converts known data types into API-friendly strings
     * @param $value
     * @return string
     */
    private function convertValuesToString($value){
        if($value === true){
            return 'TRUE';
        }
        if($value === false){
            return 'FALSE';
        }
        if(is_array($value)){
            return implode(',',$value);
        }
        return $value;
    }

}

?>