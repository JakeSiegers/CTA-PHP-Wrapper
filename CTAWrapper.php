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

    protected $statusApiUrl;
    protected $statusApiLocations;

    function __construct($config = array()){
        $this->trainApiKey = null;
        $this->busApiKey = null;

        if(isset($config['trainApiKey'])){
            $this->trainApiKey = $config['trainApiKey'];
        }

        if(isset($config['busApiKey'])){
            $this->busApiKey = $config['busApiKey'];
        }

        $this->apiUrls = array(
            'alerts'    => 'http://www.transitchicago.com/api/1.0/',
            'bus'       => 'http://www.ctabustracker.com/bustime/api/v1/',
            'train'     => 'http://lapi.transitchicago.com/api/1.0/'
        );

        $this->apiEndpoints = array(
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
    }

    /**
     * @param $endpoint
     * @param array $params
     * @return array
     * @throws Exception
     */
    function alertApiCall($endpoint, $params = array()){
        if(!isset($this->apiEndpoints['alerts'][$endpoint])){
            throw new Exception("Unknown Alert Endpoint");
        }
        return $this->fetchApiData($this->apiUrls['alerts'].$this->apiEndpoints['alerts'][$endpoint].$this->generateGetVariables($params));
    }

    /**
     * @param $endpoint
     * @param array $params
     * @return array
     * @throws Exception
     */
    function busApiCall($endpoint, $params = array()){
        $params['key'] = $this->busApiKey;
        if(!isset($this->apiEndpoints['bus'][$endpoint])){
            throw new Exception("Unknown Bus Endpoint");
        }
        return $this->fetchApiData($this->apiUrls['bus'].$this->apiEndpoints['bus'][$endpoint].$this->generateGetVariables($params));
    }

    /**
     * @param $endpoint
     * @param array $params
     * @return array
     * @throws Exception
     */
    function trainApiCall($endpoint, $params = array()){
        $params['key'] = $this->trainApiKey;
        if(!isset($this->apiEndpoints['train'][$endpoint])){
            throw new Exception("Unknown Train Endpoint");
        }
        return $this->fetchApiData($this->apiUrls['train'].$this->apiEndpoints['train'][$endpoint].$this->generateGetVariables($params));
    }

    /**
     * Fetch array formatted data from an XML url.
     * @param $url
     * @return array mixed
     */
    protected function fetchApiData($url){
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
    protected function generateGetVariables($data){
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