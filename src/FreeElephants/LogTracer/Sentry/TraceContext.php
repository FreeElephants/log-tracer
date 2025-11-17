<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use FreeElephants\LogTracer\TraceContextInterface;
use Psr\Http\Message\MessageInterface;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use function Sentry\continueTrace;
use function Sentry\getBaggage;
use function Sentry\getTraceparent;

class TraceContext implements TraceContextInterface
{
    private const SENTRY_TRACE_HEADER_REGEX = '/^[ \t]*(?<trace_id>[0-9a-f]{32})?-?(?<span_id>[0-9a-f]{16})?-?(?<sampled>[01])?[ \t]*$/i';
    private bool $isInitialized = false;
    private string $traceId;
    private string $parentId;

    private HubInterface $hub;

    public function __construct(?HubInterface $hub = null)
    {
        $this->hub = $hub ?: SentrySdk::getCurrentHub();
    }

    public function traceMessage(MessageInterface $message, bool $updateParent = true): MessageInterface
    {
        if (!$this->isInitialized) {
            $this->populateWithDefaults();
        }

        if ($updateParent) {
            continueTrace(getTraceparent(), getBaggage());
        }

        $sentryContext = $this->getSentryProvidedContext();

        return $message
            ->withHeader('traceparent', sprintf('00-%s-%s-%s', $sentryContext->getTraceId(), $sentryContext->getParentId(), $sentryContext->isSampled() ? '01' : '00'))
            ->withHeader('sentry-trace', $sentryContext->getTraceId() . '-' . $sentryContext->getParentId())
            ->withHeader('baggage', getBaggage())
        ;
    }

    public function populateFromMessage(MessageInterface $request)
    {
        if ($incomeValue = $request->getHeaderLine('traceparent')) {
            if (preg_match(self::W3C_TRACEPARENT_HEADER_REGEX, $incomeValue, $parts) === 1) {
                $this->traceId = $parts['trace_id'];
                $this->parentId = $parts['parent_id'];

                $this->isInitialized = true;
                continueTrace(sprintf('%s-%s', $this->traceId, $this->parentId), $request->getHeaderLine('baggage'));

                return;
            }
        } elseif ($incomeValue = $request->getHeaderLine('sentry-trace')) {
            if (preg_match(self::SENTRY_TRACE_HEADER_REGEX, $incomeValue, $parts) === 1) {
                $this->traceId = $parts['trace_id'];
                $this->parentId = $parts['span_id'];

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
        $sentryContext = $this->getSentryProvidedContext();

        $this->traceId = $sentryContext->getTraceId();
        $this->parentId = $sentryContext->getParentId();

        $this->isInitialized = true;
    }

    public function getParentId(bool $update = false): string
    {
        if ($update) {
            continueTrace(getTraceparent(), getBaggage());
        }

        return $this->getSentryProvidedContext()->getParentId();
    }

    private function getSentryProvidedContext(): SentryContext
    {
        if ($span = $this->hub->getSpan()) {
            $sentryContext = SentryContext::fromSpan($span);
        } else {
            $sentryContext = null;
            $this->hub->configureScope(function (Scope $scope) use (&$sentryContext) {
                $sentryContext = SentryContext::fromPropagationContext($scope->getPropagationContext());
            });
        }

        return $sentryContext;
    }

    private function buildSentryTraceValue(): string
    {
        $sentryContext = $this->getSentryProvidedContext();

        return $sentryContext->getTraceId() . '-' . $sentryContext->getParentId();
    }
}
