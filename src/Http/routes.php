<?php

#@GetMapping("/volumes/{id}/ptp")
#public Model index(@PathVariable long id)
$router->group([
    'middleware' => 'auth', #eingeloggt
    'namespace' => 'Views',
], function ($router) {
    $router->get('volumes/{id}/ptp', [ 'as' => 'volumes-ptp', 'uses' => 'PtpJobController@index',]); #wenn route aufgerufen->nutze jobController #contr@methode
});

$router->group([
    'middleware' => 'auth', #eingeloggt
    'namespace' => 'Api',
], function ($router) {
    $router->get('ptp/jobs/{id}/review', "PtpReviewController@showReviewPage"); #wenn route aufgerufen->nutze jobController #contr@methode
    $router->get("ptp/patches/{uuid}/{id}", [ 'as' => "ptp-patch", 'uses' => 'PtpReviewController@showPatch',]); #wenn route aufgerufen->nutze jobController #contr@methode

});
#http://localhost:8000/ptp/jobs/61/review





$router->group([
    'middleware' => 'auth',
    'namespace' => 'Api',
    'prefix' => 'api/v1'
], function ($router) {
    $router->post('send-ptp-job/{id}', 'PtpController@store');
});
