<?php
namespace modules\forms;

use craft\enums\Color;
use verbb\formie\services\Statuses;

/**
 * Formie's submission statuses with their colours as Craft's Color enum. Formie 3.1.48 hands Craft plain strings
 * ("green"), but Craft 5.11's card view reads $color->value, so switching Submissions to the Cards view crashed
 * ("Attempt to read property "value" on string").
 * shortcut: a service override for a Formie 3.1.48 / Craft 5.11 mismatch, remove when upgrading to Formie 4 (statuses reworked).
 */
class StatusesWithColors extends Statuses
{
    public function getStatusesArray(): array
    {
        return array_map(
            fn(array $status) => ['color' => Color::tryFrom((string)($status['color'] ?? '')) ?? Color::Gray] + $status,
            parent::getStatusesArray(),
        );
    }
}
