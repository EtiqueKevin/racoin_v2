<?php

namespace middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class LoggerMiddleware implements MiddlewareInterface
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $start = microtime(true);
        
        $response = $handler->handle($request);
        
        $executionTime = microtime(true) - $start;
        
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $status = $response->getStatusCode();
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'inconnu';
        
        $logMessage = sprintf(
            "[%s] %s - %s %s - Status: %d - Temps : %.4fs\n",
            date('Y-m-d H:i:s'),
            $ip,
            $method,
            $uri,
            $status,
            $executionTime
        );
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        return $response;
    }
}