<?php

namespace App\Handlers;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler;
use Slim\Views\Twig;

class HttpErrorHandler extends ErrorHandler
{
    private $view;

    public function __construct($responseFactory, $callableResolver, $container, Twig $view)
    {
        parent::__construct($responseFactory, $callableResolver, $container);
        $this->view = $view;
    }

    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;
        $statusCode = $exception instanceof HttpException ? $exception->getCode() : 500;

        $errorDetails = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];

        // maybe keep it only for non-production environments
        // but since just empty pod to test the pipelines ingegration, other systems and CDN, no need for now
        $errorDetails['trace'] = array_slice(
            array_map(function ($trace) {
                return [
                    'file' => $trace['file'] ?? null,
                    'line' => $trace['line'] ?? null,
                    'function' => $trace['function'] ?? null,
                    'class' => $trace['class'] ?? null,
                ];
            }, $exception->getTrace()),
            0,
            3
        );

        $requestHandler = new RequestHandler($this->request);

        $errorMessage = $exception->getMessage() ?? 'An error occurred processing your request.';

        return $this->view->render(
            $this->responseFactory->createResponse($statusCode),
            'main.twig',
            ['data' => $requestHandler->getInfo($statusCode, $errorMessage, $errorDetails)]
        );
    }
}