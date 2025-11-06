<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use FreeElephants\LogTracer\Exception\NotInitializedTraceContextUsage;
use FreeElephants\LogTracer\TraceContextInterface;
use Psr\Http\Message\MessageInterface;

class TraceContext implements TraceContextInterface
{
    private bool $isInitialized = false;
    private string $traceparentHeader;
    private string $sentryTraceHeader;
    private string $baggageHeader;
    private string $traceId;
    private SentryTraceProvider $sentryTraceProvider;

    public function __construct(?SentryTraceProvider $sentryTraceProvider = null)
    {
        $this->sentryTraceProvider = $sentryTraceProvider ?: new SentryTraceProvider();
    }

    /**
     * Получить трассировку из запроса и добавить если отсутствовала.
     */
    public function traceMessage(MessageInterface $message): MessageInterface
    {
        if (!$this->isInitialized) {
            throw new NotInitializedTraceContextUsage();
        }

        return $message
            ->withHeader('traceparent', $this->traceparentHeader)
            ->withHeader('sentry-trace', $this->sentryTraceHeader)
            ->withHeader('baggage', $this->baggageHeader)
        ;
    }

    public function populateFromMessage(MessageInterface $request): string
    {
        $this->sentryTraceHeader = $request->getHeaderLine('sentry-trace') ?: $this->sentryTraceProvider->getSentryTraceHeader();
        $this->baggageHeader = $request->getHeaderLine('baggage') ?: $this->sentryTraceProvider->getBaggageHeader();
        $this->traceparentHeader = $request->getHeaderLine('traceparent') ?: $this->sentryTraceProvider->getTranceparentHeader();

        $this->traceId = $this->sentryTraceProvider->continueTrace($this->sentryTraceHeader, $this->baggageHeader);

        $this->isInitialized = true;

        return $this->traceId;
    }

    public function getTraceId(): string
    {
        if (!$this->isInitialized) {
            $this->populateWithDefaults();
        }

        return $this->traceId;
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    public function populateWithDefaults(): string
    {
        $this->sentryTraceHeader = \Sentry\getTraceparent();
        $this->baggageHeader = \Sentry\getBaggage();
        $this->traceparentHeader = \Sentry\getW3CTraceparent();
        $this->traceId = explode('-', $this->traceparentHeader)[1];

        $this->isInitialized = true;

        return $this->sentryTraceProvider->continueTrace($this->sentryTraceHeader, $this->baggageHeader);
    }
}
