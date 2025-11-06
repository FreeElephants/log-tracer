<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

class SentryTraceProvider
{
    public function getSentryTraceHeader(): string
    {
        return \Sentry\getTraceparent();
    }

    public function getBaggageHeader(): string
    {
        return \Sentry\getBaggage();
    }

    public function getTranceparentHeader(): string
    {
        return \Sentry\getW3CTraceparent();
    }

    public function continueTrace(string $sentryTraceHeader, string $baggageHeader): string
    {
        return \Sentry\continueTrace($sentryTraceHeader, $baggageHeader)->getTraceId()->__toString();
    }
}
