<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Middleware;

use FreeElephants\LogTracer\SimpleTraceContext;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TraceResponseMiddlewareTest extends TestCase
{
    public function testProcess(): void
    {
        $traceContext = new SimpleTraceContext();
        $middleware = new TraceResponseMiddleware($traceContext);

        $response = $middleware->process(new ServerRequest('GET', '/'), new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        });

        $this->assertNotEmpty($response->getHeaderLine('traceparent'));
        $this->assertStringContainsString($traceContext->getTraceId(), $response->getHeaderLine('traceparent'));

        $this->assertEmpty($response->getHeaderLine('sentry-trace'));
        $this->assertEmpty($response->getHeaderLine('baggage'));
    }
}
