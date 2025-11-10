<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use FreeElephants\LogTracer\Exception\NotInitializedTraceContextUsage;
use FreeElephants\LogTracer\TraceContextInterface;
use Psr\Http\Message\MessageInterface;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use function Sentry\continueTrace;
use function Sentry\getBaggage;

class TraceContext implements TraceContextInterface
{
    private const SENTRY_TRACE_HEADER_REGEX = '/^[ \\t]*(?<trace_id>[0-9a-f]{32})?-?(?<span_id>[0-9a-f]{16})?-?(?<sampled>[01])?[ \\t]*$/i';
    private bool $isInitialized = false;
    private string $traceId;
    private string $parentId;
    private bool $isSampled = false;

    private HubInterface $hub;

    public function __construct(HubInterface $hub = null)
    {
        $this->hub = $hub ?: SentrySdk::getCurrentHub();
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

    public function populateFromMessage(MessageInterface $request)
    {
        if ($incomeValue = $request->getHeaderLine('traceparent')) {
            if (preg_match(self::W3C_TRACEPARENT_HEADER_REGEX, $incomeValue, $parts) === 1) {
                $this->traceId = $parts['trace_id'];
                $this->parentId = $parts['parent_id'];
                $this->isSampled = $parts['sampled'] === '01';

                $this->isInitialized = true;
                continueTrace($this->buildSentryTraceValue(), $request->getHeaderLine('baggage'));
                return;
            }
        } elseif ($incomeValue = $request->getHeaderLine('sentry-trace')) {
            if (preg_match(self::SENTRY_TRACE_HEADER_REGEX, $incomeValue, $parts) === 1) {
                $this->traceId = $parts['trace_id'];
                $this->parentId = $parts['span_id'];
                if ($parts['sampled']) {
                    $this->isSampled = $parts['sampled'] === '01';
                }

                $this->isInitialized = true;
                continueTrace($this->buildSentryTraceValue(), $request->getHeaderLine('baggage'));
                return;
            }
        }

        $this->populateWithDefaults();
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

    public function populateWithDefaults()
    {
        if ($span = $this->hub->getSpan()) {
            $this->traceId = (string)$span->getTraceId();
            $this->parentId = (string)$span->getSpanId();
        } else {
            $this->hub->configureScope(function (Scope $scope) {
                $this->traceId = (string)$scope->getPropagationContext()->getTraceId();
                $this->parentId = (string)$scope->getPropagationContext()->getSpanId();
            });
        }

        $this->isInitialized = true;
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
