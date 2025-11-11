<?php
declare(strict_types=1);

namespace FreeElephants\LogTracer;

use FreeElephants\LogTracer\Sentry\TraceContext;
use Sentry\State\HubInterface;

class ContextInstanceRegistry
{
    private static $instances = [];

    private const SENTRY = 'sentry';
    private const SIMPLE = 'simple';

    public static function getSentryInstance(HubInterface $hub = null): TraceContextInterface
    {
        if (empty(self::$instances[self::SENTRY])) {
            self::$instances[self::SENTRY] = new TraceContext($hub);
        }

        return self::$instances[self::SENTRY];
    }

    public static function getSimpleInstance(): TraceContextInterface
    {
        if (empty(self::$instances[self::SENTRY])) {
            self::$instances[self::SENTRY] = new SimpleTraceContext();
        }

        return self::$instances[self::SENTRY];
    }
}
