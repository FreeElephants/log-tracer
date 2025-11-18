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

class TraceRequestMiddlewareTest extends TestCase
{
    public function testProcess(): void
    {
        $traceContext = new SimpleTraceContext();
        $middleware = new TraceRequestMiddleware($traceContext);

        $middleware->process(new ServerRequest('GET', '/'), new class ($this, $traceContext) implements RequestHandlerInterface {
            private TestCase $testCase;

            public function __construct(TestCase $testCase)
            {
                $this->testCase = $testCase;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->testCase->assertNotEmpty($request->getHeaderLine('traceparent'));
                $this->testCase->assertEmpty($request->getHeaderLine('sentry-trace'));
                $this->testCase->assertEmpty($request->getHeaderLine('baggage'));

                return new Response();
            }
        });
    }
}
