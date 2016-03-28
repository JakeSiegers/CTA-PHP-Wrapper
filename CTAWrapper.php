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

        $this->dbc = new PDO('sqlite:'.$_SERVER['DOCUMENT_ROOT'].'/sqlite/ctaApiCache.sqlite3');
        $this->dbc->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $this->dbc->exec("CREATE TABLE IF NOT EXISTS apiCache (
            id INTEGER PRIMARY KEY, 
            url TEXT, 
            data TEXT, 
            time DATETIME)");
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
        $cache = $this->checkCache($url);
        if($cache !== false){
            return json_decode($cache,TRUE);;
        }
        $jsonResponse = file_get_contents($url);
        $this->setCache($url,$jsonResponse);
        $arrayResponse = json_decode($jsonResponse,true);
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
        $cache = $this->checkCache($url);
        if($cache !== false){
            return json_decode($cache,TRUE);;
        }
        $xmlResults = simplexml_load_file($url,null,LIBXML_NOCDATA);
        $jsonResults = json_encode($xmlResults);
        $this->setCache($url,$jsonResults);
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

    /**
     * Check to see if a cache exists.
     * @param $url
     * @return bool|string
     * @throws Exception
     * @internal param $requestUrl
     */
    private function checkCache($url){
        $sql = "SELECT id,url,data,time FROM apiCache WHERE url = :url";
        $stmt = $this->dbc->prepare($sql);
        $stmt->bindParam(':url', $url);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $numResults = count($results);
        if($numResults == 0){
            return false;
        }
        if($numResults !== 1){
            throw new Exception('Double Cache Values!');
        }

        //check date to see if it's older than 1 minute
        //Reduces Api Calls. None of the data needs to be within a minute anyway.
        if(strtotime($results[0]['time']) <= strtotime("-1 minute")){
            $this->deleteCache($url);
            return false;
        }

        return $results[0]['data'];
    }

    /**
     * Set a cache entry for a url
     * @param $url
     * @param $data
     */
    private function setCache($url, $data){
        $sql = "INSERT INTO apiCache (url,data,time) VALUES (:url,:data,datetime('now','localtime')) ";
        $stmt = $this->dbc->prepare($sql);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':data', $data);
        $stmt->execute();
    }

    /**
     * Delete a cache entry of a URL
     * @param $url
     */
    private function deleteCache($url){
        $sql = "DELETE FROM apiCache WHERE url = :url";
        $stmt = $this->dbc->prepare($sql);
        $stmt->bindParam(':url', $url);
        $stmt->execute();
    }

}