<?php

$router->group([
    "middleware" => "auth",
    "namespace" => "Views",
], function ($router) {
    $router->get("volumes/{id}/ptp", [
        "as" => "volumes-ptp",
        "uses" => "PtpViewController@index",
    ]);
});

$router->group([
    "middleware" => "auth", #eingeloggt
    "namespace" => "Api",
], function ($router) {
    $router->get("ptp/jobs/{id}/review", [
        "as" => "ptp-review",
        "uses" => "PtpReviewController@showReviewPage",
    ]);
    $router->get("ptp/patches/{uuid}/{id}/{type?}", [ "as" => "ptp-patch", "uses" => "PtpReviewController@showPatch",]);



});
#http://localhost:8000/ptp/jobs/61/review





$router->group([
    'middleware' => 'auth',
    'namespace' => 'Api',
    'prefix' => 'api/v1'
], function ($router) {
    $router->post('send-ptp-job/{id}', 'PtpController@store');
    $router->post("ptp-jobs/{id}/review", "PtpReviewController@review");
});
