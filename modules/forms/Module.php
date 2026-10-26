<?php
namespace modules\forms;

use Craft;
use craft\services\Plugins;
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
 * - Submission status colours as Craft's Color enum (StatusesWithColors): the Cards view of Submissions crashed.
 * - Product Request Quote: each submission's "Product (entry)" field links the product its Product Page address names
 *   (/product/<slug>/), so Formie → Submissions can be filtered by product. Set here, on save, not by the browser.
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

        // Formie's statuses service → our subclass, once the plugins are loaded
        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function() {
            \verbb\formie\Formie::$plugin?->set('statuses', StatusesWithColors::class);
        });

        Event::on(Submission::class, \craft\base\Element::EVENT_BEFORE_SAVE, function(\craft\events\ModelEvent $event) {
            /** @var Submission $submission */
            $submission = $event->sender;
            if ($submission->getForm()?->handle !== 'productRequestQuote' || $submission->getFieldValue('productEntry')->ids()) {
                return;
            }
            if (preg_match('#/product/([^/?\#]+)#', (string)$submission->getFieldValue('productPage'), $m)
                && ($product = \craft\elements\Entry::find()->section('products')->slug($m[1])->status(null)->one())) {
                $submission->setFieldValue('productEntry', [$product->id]);
            }
        });

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
