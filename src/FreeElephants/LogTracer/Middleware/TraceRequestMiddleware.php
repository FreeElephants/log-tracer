<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Middleware;

use FreeElephants\LogTracer\TraceContextInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TraceRequestMiddleware implements MiddlewareInterface
{
    private TraceContextInterface $traceContext;

    public function __construct(TraceContextInterface $traceContext)
    {
        $this->traceContext = $traceContext;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($this->traceContext->traceMessage($request));
    }
}
