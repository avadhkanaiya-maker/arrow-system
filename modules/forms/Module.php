<?php
namespace modules\forms;

use Craft;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Phone;
use verbb\formie\models\Phone as PhoneValue;
use yii\base\Event;
use yii\base\Model;

/**
 * Server-side rules for the Formie forms (front end and CP):
 * - Phone fields with the country picker: the number must be a valid number for the chosen country. Formie checks this
 *   only in the browser (intl-tel-input), so without this a bot or a browser without JS could send "123".
 */
class Module extends \yii\base\Module
{
    public function init(): void
    {
        Craft::setAlias('@modules/forms', __DIR__);
        parent::init();

        if (!class_exists(Submission::class)) {
            return;
        }

        Event::on(Submission::class, Model::EVENT_AFTER_VALIDATE, function(Event $event) {
            /** @var Submission $submission */
            $submission = $event->sender;
            foreach ($submission->getForm()?->getCustomFields() ?? [] as $field) {
                if (!$field instanceof Phone || !$field->countryEnabled) {
                    continue;
                }
                $value = $submission->getFieldValue($field->handle);
                if (!$value instanceof PhoneValue || $value->isEmpty()) {
                    continue;   // empty: Formie's own "required" rule decides
                }
                if (!self::isValidPhone((string)$value->number, $value->country)) {
                    $submission->addError($field->getErrorKey(), Craft::t('site', '{label} is not a valid phone number for the selected country.', ['label' => $field->label]));
                }
            }
        });
    }

    /** A valid number for the country (ISO code, e.g. "US"), or in international format ("+44 …") */
    public static function isValidPhone(string $number, ?string $country): bool
    {
        $util = PhoneNumberUtil::getInstance();
        try {
            return $util->isValidNumber($util->parse($number, $country ?: null));
        } catch (NumberParseException) {
            return false;
        }
    }
}
