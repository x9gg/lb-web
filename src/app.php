<?php

use App\Handlers\RequestHandler;
use App\Handlers\HttpErrorHandler;
use App\Handlers\ShutdownHandler;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteCollectorProxy;
use Slim\Exception\HttpMethodNotAllowedException;

$displayErrorDetails = true;

// Create Slim App
$app = AppFactory::create();

// Set up Twig
$twig = Twig::create(__DIR__ . '/templates', [
    'cache' => false,
    'debug' => false
]);

$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();

$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory, $app->getContainer(), $twig);
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add security headers middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('X-Frame-Options', 'DENY')
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('X-XSS-Protection', '1; mode=block')
        ->withHeader('Content-Security-Policy', "default-src 'self'; style-src 'self' 'unsafe-inline';")
        ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

// Add GET-only middleware
$app->add(function (ServerRequestInterface $request, $handler) {
    if ($request->getMethod() !== 'GET') {
        throw new HttpMethodNotAllowedException($request, ['GET']);
    }
    return $handler->handle($request);
});

// Add Twig Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Handling Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Group routes for better organization
$app->group('', function (RouteCollectorProxy $group) use ($twig) {
    // Main route
    $group->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($twig) {
        $requestHandler = new RequestHandler($request);
        return $twig->render($response, 'main.twig', [
            'data' => $requestHandler->getInfo()
        ])->withStatus(200);
    });

    // Info route
    $group->get('/info', function (ServerRequestInterface $request, ResponseInterface $response) {
        $phpInfo = [];
        ob_start();
        phpinfo();
        $phpInfo = ob_get_clean();

        $response->getBody()->write($phpInfo);
        return $response->withHeader('Content-Type', 'text/html');
    });
})->add(function ($request, $handler) {
    // Additional middleware for this group if needed
    return $handler->handle($request);
});

return $app;