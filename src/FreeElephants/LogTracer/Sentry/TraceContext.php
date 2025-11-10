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
    private AbstractSentryTraceProvider $sentryTraceProvider;

    public function __construct(?AbstractSentryTraceProvider $sentryTraceProvider = null)
    {
        $this->sentryTraceProvider = $sentryTraceProvider ?: AbstractSentryTraceProvider::createInstance();
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
            ->withHeader('baggage', $this->baggageHeader);
    }

    public function populateFromMessage(MessageInterface $request): string
    {
        $incomeValue = $request->getHeaderLine('traceparent');

        if ($this->tryFromValue($incomeValue)) {
            $this->traceparentHeader = $incomeValue;
        } else {
            $this->traceparentHeader = $this->sentryTraceProvider->getTranceparentHeader();
            $this->sentryTraceHeader = $this->sentryTraceProvider->getSentryTraceHeader();
        }

        $this->baggageHeader = $request->getHeaderLine('baggage') ?: $this->sentryTraceProvider->getBaggageHeader();

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
        $this->sentryTraceHeader = $this->sentryTraceProvider->getSentryTraceHeader();
        $this->baggageHeader = $this->sentryTraceProvider->getBaggageHeader();
        $this->traceparentHeader = $this->sentryTraceProvider->getTranceparentHeader();

        $this->traceId = explode('-', $this->traceparentHeader)[1];

        $this->isInitialized = true;

        return $this->sentryTraceProvider->continueTrace($this->sentryTraceHeader, $this->baggageHeader);
    }

    private function tryFromValue(string $incomeValue): bool
    {
        if (preg_match(self::W3C_TRACEPARENT_HEADER_REGEX, $incomeValue, $matches)) {
            if (!empty($matches['trace_id'])) {
                $this->traceId = $matches['trace_id'];
            }

            if (!empty($matches['span_id'])) {
                $parentSpanId = $matches['span_id'];
            }

            if (isset($matches['sampled'])) {
                $parentSampled = $matches['sampled'] === '01';
            }

            $this->traceparentHeader = $incomeValue;

            return true;
        }

        return false;
    }

    public function getParentId(): string
    {
        // TODO: Implement getParentId() method.
    }
}
