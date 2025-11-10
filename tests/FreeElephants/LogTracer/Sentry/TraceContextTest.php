<?php
declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use function Sentry\getBaggage;
use function Sentry\getTraceparent;

class TraceContextTest extends TestCase
{

    public function testPopulateWithDefaults(): void
    {
        $context = new TraceContext();

        $context->populateWithDefaults();

        $this->assertStringStartsWith($context->getTraceId(), getTraceparent());
        $this->assertStringEndsWith($context->getParentId(), getTraceparent());
    }

    public function testTraceMessage(): void
    {
        $context = new TraceContext();

        $context->populateWithDefaults();

        $tracedMessage = $context->traceMessage(new Request('GET', '/foo'));

        $this->assertNotEmpty($tracedMessage->getHeaderLine('traceparent'));
        $this->assertSame(getTraceparent(), $tracedMessage->getHeaderLine('sentry-trace'));
        $this->assertSame(getBaggage(), $tracedMessage->getHeaderLine('baggage'));
    }

    public function testPopulateFromMessageWithW3CHeader(): void
    {
        $context = new TraceContext();

        $request = (new ServerRequest('GET', '/foo'))->withHeader('traceparent', '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01');

        $context->populateFromMessage($request);

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $context->getTraceId());

        $tracedMessage = $context->traceMessage(new Response());

        $this->assertSame('00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01', $tracedMessage->getHeaderLine('traceparent'));
        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7', $tracedMessage->getHeaderLine('sentry-trace'));
        $this->assertTrue($tracedMessage->hasHeader('baggage'));
    }
}
