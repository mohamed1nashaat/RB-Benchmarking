<?php

namespace App\Services\Calculators;

use InvalidArgumentException;

class ObjectiveCalculatorFactory
{
    private static array $calculators = [
        'awareness' => AwarenessCalculator::class,
        'engagement' => EngagementCalculator::class,
        'traffic' => TrafficCalculator::class,
        'messages' => MessagesCalculator::class,
        'app_installs' => AppInstallsCalculator::class,
        'in_app_actions' => InAppActionsCalculator::class,
        'leads' => LeadsCalculator::class,
        'website_sales' => WebsiteSalesCalculator::class,
        'sales' => SalesCalculator::class,
        'calls' => CallsCalculator::class,
        'retention' => RetentionCalculator::class,
    ];

    public static function make(string $objective): ObjectiveCalculatorInterface
    {
        if (!isset(self::$calculators[$objective])) {
            throw new InvalidArgumentException("Unknown objective: {$objective}");
        }

        $calculatorClass = self::$calculators[$objective];
        return new $calculatorClass();
    }

    public static function getSupportedObjectives(): array
    {
        return array_keys(self::$calculators);
    }

    public static function isValidObjective(string $objective): bool
    {
        return isset(self::$calculators[$objective]);
    }
}
