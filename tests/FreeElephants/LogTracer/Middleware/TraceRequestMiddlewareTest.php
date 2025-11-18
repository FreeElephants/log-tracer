<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Middleware;

use FreeElephants\LogTracer\SimpleTraceContext;
use FreeElephants\LogTracer\TraceContextInterface;
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
            private TraceContextInterface $traceContext;

            public function __construct(TestCase $testCase, TraceContextInterface $traceContext)
            {
                $this->testCase = $testCase;
                $this->traceContext = $traceContext;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->testCase->assertNotEmpty($request->getHeaderLine('traceparent'));
                $this->testCase->assertStringContainsString($this->traceContext->getTraceId(), $request->getHeaderLine('traceparent'));

                $this->testCase->assertEmpty($request->getHeaderLine('sentry-trace'));
                $this->testCase->assertEmpty($request->getHeaderLine('baggage'));

                return new Response();
            }
        });
    }
}
