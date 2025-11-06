<?php
declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class TraceContextTest extends TestCase
{

    public function testTraceMessage(): void
    {
        $context = new TraceContext();

        $context->populateWithDefaults();
        $tracedRequest = $context->traceMessage(new Request('GET', '/foo'));
        $this->assertNotEmpty($tracedRequest->getHeader('traceparent'));
        $this->assertNotEmpty($tracedRequest->getHeader('sentry-trace'));
        $this->assertNotEmpty($tracedRequest->getHeader('baggage'));


    }

    public function testTraceMessageWithDefaultsInitValues(): void
    {
        $context = new TraceContext();
        $context->populateWithDefaults();

        $tracedRequest = $context->traceMessage(new Request('GET', '/foo'));

        $this->assertNotEmpty($tracedRequest->getHeaderLine('sentry-trace'));
    }


}
