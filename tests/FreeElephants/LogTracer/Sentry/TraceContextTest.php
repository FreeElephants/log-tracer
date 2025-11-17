<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Sentry\SentrySdk;
use function Sentry\getBaggage;
use function Sentry\getTraceparent;

class TraceContextTest extends TestCase
{
    public function setUp(): void
    {
        SentrySdk::init();
        parent::setUp();
    }

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

        $tracedMessage = $context->traceMessage(new Request('GET', '/foo'), false);

        $w3cHeaderLine = $tracedMessage->getHeaderLine('traceparent');
        $this->assertNotEmpty($w3cHeaderLine);
        $this->assertStringEndsWith($context->getParentId() . '-00', $w3cHeaderLine);
        $this->assertSame(getTraceparent(), $tracedMessage->getHeaderLine('sentry-trace'));
        $this->assertSame(getBaggage(), $tracedMessage->getHeaderLine('baggage'));
    }

    public function testTraceMessageWithUpdateParent(): void
    {
        $context = new TraceContext();

        $context->populateWithDefaults();

        $tracedMessage = $context->traceMessage(new Request('GET', '/foo'), true);

        $w3cHeaderLine = $tracedMessage->getHeaderLine('traceparent');
        $this->assertNotEmpty($w3cHeaderLine);
        $this->assertStringEndsWith($context->getParentId() . '-00', $w3cHeaderLine);
        $this->assertSame(getBaggage(), $tracedMessage->getHeaderLine('baggage'));
    }

    public function testPopulateFromMessageWithW3CHeader(): void
    {
        $context = new TraceContext();

        $request = (new ServerRequest('GET', '/foo'))->withHeader('traceparent', '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01');

        $context->populateFromMessage($request);

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $context->getTraceId());
        $this->assertNotSame('00f067aa0ba902b7', $context->getParentId(), 'Sentry anywhere continue trace with up span on populate context');

        $tracedMessage = $context->traceMessage(new Response(), false);

        $w3cHeaderLine = $tracedMessage->getHeaderLine('traceparent');
        $this->assertStringStartsWith('00-4bf92f3577b34da6a3ce929d0e0e4736-', $w3cHeaderLine);
        $this->assertStringEndsWith($context->getParentId() . '-00', $w3cHeaderLine);
        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736-' . $context->getParentId(), $tracedMessage->getHeaderLine('sentry-trace'));
        $this->assertTrue($tracedMessage->hasHeader('baggage'));
    }

    public function testPopulateFromMessageWithVendorHeaders(): void
    {
        $context = new TraceContext();

        $request = (new ServerRequest('GET', '/foo'))
            ->withHeader('sentry-trace', '4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7')
        ;

        $context->populateFromMessage($request);

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $context->getTraceId());
        $this->assertNotSame('00f067aa0ba902b7', $context->getParentId(), 'Sentry anywhere continue trace with up span on populate context');
    }

    public function testLazyInitializeWithDefault(): void
    {
        $context = new TraceContext();

        $this->assertFalse($context->isInitialized());

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/i', $context->getTraceId());

        $this->assertTrue($context->isInitialized());
    }
}
