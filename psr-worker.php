<?php

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use Nyholm\Psr7\Factory\Psr17Factory;

require __DIR__ . '/vendor/autoload.php';

$psrFactory = new Psr17Factory();

$worker = Worker::create();

$psr7Worker = new PSR7Worker(
    $worker,
    $psrFactory, // ServerRequestFactory
    $psrFactory, // StreamFactory
    $psrFactory, // UploadedFileFactory
);

$app = require __DIR__ . '/src/app.php';

while (true) {
    try {
        $request = $psr7Worker->waitRequest();
        
        if ($request === null) {
            break;
        }

        $response = $app->handle($request);
        $psr7Worker->respond($response);
        
    } catch (\Throwable $e) {
        $psr7Worker->getWorker()->error((string)$e);
        
        $response = $psrFactory->createResponse(500);
        $response->getBody()->write('Internal Server Error');
        
        try {
            $psr7Worker->respond($response);
        } catch (\Throwable $e) {
            $psr7Worker->getWorker()->error('Error sending error response: ' . (string)$e);
        }
    }
}