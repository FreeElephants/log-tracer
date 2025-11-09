<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use Sentry\SentrySdk;
use Sentry\State\Scope;

class SentryTraceProviderVersionFrom4_12 extends AbstractSentryTraceProvider
{
    /**
     * @see https://github.com/getsentry/sentry-php/pull/1833
     */
    public function getTranceparentHeader(): string
    {
        $hub = SentrySdk::getCurrentHub();
        $client = $hub->getClient();

        if ($client !== null) {
            $options = $client->getOptions();

            if ($options->isTracingEnabled()) {
                $span = SentrySdk::getCurrentHub()->getSpan();
                if ($span !== null) {
                    $sampled = '';

                    if ($span->getSampled() !== null) {
                        $sampled = $span->getSampled() ? '-01' : '-00';
                    }

                    return sprintf('00-%s-%s%s', $span->getTraceId(), $span->getSpanId(), $sampled);
                }
            }
        }

        $traceParent = '';
        $hub->configureScope(function (Scope $scope) use (&$traceParent) {
            $traceParent = sprintf('00-%s-%s', $scope->getPropagationContext()->getTraceId(), $scope->getPropagationContext()->getSpanId());
        });

        return $traceParent;
    }
}
