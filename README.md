# CTA-PHP-Wrapper
PHP Wrapper to help get data from the CTA API

(Returns Everything in an easy-to-use array format)

## To Begin
1. Have PHP 5.3+
    * (May work in older versions, but this is the earliest I've tested with)
2. Require In the wrapper:
```php
<?php
    require_once('{Whereever your project is located bla bla bla}/CTAWrapper.php');
?>
```
3. Get a bus and train API key from the CTA
    * This is pseudo-optional, if you only plan on using the alerts API. The train API and bus API both **require** API keys.
    * Busses - http://www.transitchicago.com/developers/bustracker.aspx
    * Trains - http://www.transitchicago.com/developers/traintracker.aspx
4. Create an opject of the CTAWrapper, which accepts an array of api keys as it's only parameter, for now.
```php
$cta = new CTAWrapper(array(
    'busApiKey' => $yourBusKey, //Only if you need to use the bus API
    'trainApiKey' => $yourTrainKey //Only if you need to use the train API
));
```
5. Make a call on one of the 3 CTA API's.
    * The params array is optional. If the endpoint you're calling does not require params, don't include them!
    * The **key** parameter found on train and bus endpoints is automatically added for you. You don't need to include it in your params.
```php
    $cta->trainApiCall($endpoint,$paramsArray)
    $cta->busApiCall($endpoint,$paramsArray)
    $cta->alertApiCall($endpoint,$paramsArray)
```
 Quick list of all endpoints to endpointUrl's.
```php
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
```

## CTA API Docs
This API Wrapper is very basic, and supports every endpoint, so just looking over the official CTA API docs will show you all the parameters you can use, along with nice descriptions

* Train API Docs: http://www.transitchicago.com/assets/1/developer_center/cta_Train_Tracker_API_documentation_v1_42.pdf
* Bus API Docs: http://www.transitchicago.com/assets/1/developer_center/BusTime_Developer_API_Guide.pdf
* Alert API docs: http://www.transitchicago.com/assets/1/developer_center/cta_customer_alerts_API.pdf

## Train API Example
An example of a request to the "Follow this train" endpoint.
```php
<?php
    $cta = new CTAWrapper(array(
        'trainApiKey' => $yourTrainKey
    ));

    echo '<pre>';
        var_dump($cta->trainApiCall(
            'followThisTrain',
            array(
                'runnumber' => 123
            )
        ));
    echo '</pre>';
?>
```
## Bus API Example
This example looks up vehicle info on bus #1993 & #1219
```php
<?php
    $cta = new CTAWrapper(array(
        'busApiKey' => $yourBusKey
    ));
    echo '<pre>';
    var_dump($cta->busApiCall(
        'vehicles',
        array(
            'vid' => array(1993,1219)
        )
    ));
    echo '</pre>';
?>
```

## Alerts API Example
This example lists all the train stations, and any alerts they may have:
```php
<?php
    $cta = new CTAWrapper(); //No API Keys required!

    echo '<pre>';
    var_dump($cta->alertApiCall(
        'routes',
        array(
            'type' => array('rail')
        )
    ));
    echo '</pre>';
?>
```

## Other stuff
This wrapper isn't endorsed by the CTA or the city of Chicago in any way.

2016 - Jake Siegers - MIT License, feel free to fork and improve.