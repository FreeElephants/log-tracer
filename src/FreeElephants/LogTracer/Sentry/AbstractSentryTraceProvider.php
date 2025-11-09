<?php
declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use Composer\Semver\Comparator;
use Sentry\Client;

abstract class AbstractSentryTraceProvider
{
    public static function createInstance(): self
    {
        if(Comparator::greaterThanOrEqualTo(Client::SDK_VERSION, '4.12')) {
            return new SentryTraceProviderVersionFrom4_12();
        }

        return new SentryTraceProviderVersionBefore4_12();
    }

    public function getSentryTraceHeader(): string
    {
        return \Sentry\getTraceparent();
    }

    public function getBaggageHeader(): string
    {
        return \Sentry\getBaggage();
    }

    public function continueTrace(string $sentryTraceHeader, string $baggageHeader): string
    {
        return \Sentry\continueTrace($sentryTraceHeader, $baggageHeader)->getTraceId()->__toString();
    }
}
