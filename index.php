<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->post('/api/createOrder', function (ServerRequestInterface $request, ResponseInterface $response) {
    $identityURI = "";
    $gatewayAPIURI = "";
    $apiKey = "";
    $realm = "";

    // First get the auth token
    $bodyData = (object) ["grant_type" => "client_credentials", "realm" => $realm];
    $stringBody = json_encode($bodyData);
    $stringBodyLength = strlen($stringBody);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $identityURI,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $stringBody,
        CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Authorization: Basic {$apiKey}",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Length: {$stringBodyLength}",
            "Content-Type: application/vnd.ni-identity.v1+json",
            "cache-control: no-cache"
        ),
    ));

    $authResponse = curl_exec($curl);
    $err = curl_error($curl);
    $authToken = "";

    if ($err) {
        echo "Auth failed:" . $err;
    } else {
        $authJSON = json_decode($authResponse);
        $authToken = $authJSON->access_token;
    }

    $orderRequest = json_encode(json_decode($request->getBody()));
    $orderRequestLength = strlen($orderRequest);

    curl_close($curl);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $gatewayAPIURI,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $orderRequest,
        CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Authorization: Bearer {$authToken}",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Length: {$orderRequestLength}",
            "Content-Type: application/vnd.ni-payment.v2+json",
            "cache-control: no-cache"
        ),
    ));

    $orderResponse = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "Order Creation failed:" . $err;
        return $response;
    } else {
        $response->getBody()->write($orderResponse);
        return $response->withHeader('Content-Type', 'application/json');
    }
});

$app->run();
