<?php
    require_once('../CTAWrapper.php');
    require_once('keys.php'); //Not in repo, just a file with 2 variables equal to my api keys ($trainKey & $busKey)

    $cta = new CTAWrapper(array(
        'busApiKey' => $busKey,
        'trainApiKey' => $trainKey
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