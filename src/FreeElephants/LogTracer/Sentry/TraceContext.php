<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use FreeElephants\LogTracer\Exception\NotInitializedTraceContextUsage;
use FreeElephants\LogTracer\TraceContextInterface;
use Psr\Http\Message\MessageInterface;
use function Sentry\continueTrace;
use function Sentry\getBaggage;
use function Sentry\getTraceparent;

class TraceContext implements TraceContextInterface
{
    private bool $isInitialized = false;
    private string $traceparentHeader;
    private string $sentryTraceHeader;
    private string $baggageHeader;
    private string $traceId;
    private string $parentId;
    private bool $isSampled = false;

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
            ->withHeader('traceparent', sprintf('00-%s-%s-%s', $this->traceId, $this->parentId, $this->isSampled ? '01' : '00'))
            ->withHeader('sentry-trace', $this->buildSentryTraceValue())
            ->withHeader('baggage', getBaggage());
    }

    public function populateFromMessage(MessageInterface $request): string
    {
        $incomeValue = $request->getHeaderLine('traceparent');
        if (preg_match(self::W3C_TRACEPARENT_HEADER_REGEX, $incomeValue, $parts)) {
            $this->traceId = $parts['trace_id'];
            $this->parentId = $parts['parent_id'];
            $this->isSampled = $parts['sampled'] === '01';

            $this->isInitialized = true;
            continueTrace($this->buildSentryTraceValue(), $request->getHeaderLine('baggage'));

            return $this->traceId;
        }

        return $this->populateWithDefaults();
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

    public function getParentId(): string
    {
        return $this->parentId;
    }

    private function buildSentryTraceValue(): string
    {
        return $this->traceId . '-' . $this->parentId;
    }
}
