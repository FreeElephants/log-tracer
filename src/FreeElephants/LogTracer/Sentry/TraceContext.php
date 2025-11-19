<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use FreeElephants\LogTracer\AbstractTraceContext;
use Psr\Http\Message\MessageInterface;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use function Sentry\continueTrace;
use function Sentry\getBaggage;
use function Sentry\getTraceparent;

class TraceContext extends AbstractTraceContext
{
    private const SENTRY_TRACE_HEADER_REGEX = '/^[ \t]*(?<trace_id>[0-9a-f]{32})?-?(?<span_id>[0-9a-f]{16})?-?(?<sampled>[01])?[ \t]*$/i';

    private HubInterface $hub;

    public function __construct(?HubInterface $hub = null)
    {
        $this->hub = $hub ?: SentrySdk::getCurrentHub();
    }

    public function traceMessage(MessageInterface $message, bool $updateParent = true): MessageInterface
    {
        if (!$this->isInitialized()) {
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
                $this->isInitialized = true;
                continueTrace(sprintf('%s-%s', $parts['trace_id'], $parts['parent_id']), $request->getHeaderLine('baggage'));

                return;
            }
        } elseif ($incomeValue = $request->getHeaderLine('sentry-trace')) {
            if (preg_match(self::SENTRY_TRACE_HEADER_REGEX, $incomeValue, $parts) === 1) {
                $this->isInitialized = true;
                continueTrace($incomeValue, $request->getHeaderLine('baggage'));

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

        return $this->getSentryProvidedContext()->getTraceId();
    }

    public function populateWithDefaults()
    {
        $this->getSentryProvidedContext();

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

    public function populateWithValues(string $traceId, string $parentId)
    {
        // TODO: Implement populateWithValues() method.
    }
}
