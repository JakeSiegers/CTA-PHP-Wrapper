<?php
    require_once('../CTAWrapper.php');
    require_once('keys.php'); //Not in repo, just a file with 2 variables equal to my api keys ($yourTrainKey & $yourBusKey)

    $cta = new CTAWrapper(array(
        'busApiKey' => $yourBusKey,
        'trainApiKey' => $yourTrainKey
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