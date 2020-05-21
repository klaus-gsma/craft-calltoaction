<?php

/**
 * callToAction plugin for Craft CMS 3.x
 *
 * A reusable, configurable call to action field
 *
 * @link      https://dawsonandrews.com/
 * @copyright Copyright (c) 2020 Dawson Andrews
 */

namespace dawsonandrews\calltoaction\fields;

use dawsonandrews\calltoaction\assetbundles\calltoactionfield\CallToActionFieldAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * CallToAction Field
 *
 * Whenever someone creates a new field in Craft, they must specify what
 * type of field it is. The system comes with a handful of field types baked in,
 * and we’ve made it extremely easy for plugins to add new ones.
 *
 * https://craftcms.com/docs/plugins/field-types
 *
 * @author    Dawson Andrews
 * @package   CallToAction
 * @since     1.0.0
 */
class CallToAction extends Field
{
    public $type = 'primary';

    const VALID_TYPES = ['primary', 'secondary']; // @todo Configure with table

    public static function displayName(): string
    {
        return Craft::t('call-to-action', 'Call To Action');
    }

    // Field Settings
    // =========================================================================

    public function rules()
    {
        $rules = parent::rules();

        return array_merge($rules, [
            [['type'], 'required'],
            [['type'], 'default', 'value' => 'primary'],
        ]);
    }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_JSON;
    }

    public function getSettingsHtml()
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'craft-calltoaction/_components/fields/settings',
            [
                'field' => $this
            ]
        );
    }

    // Field Input
    // =========================================================================

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        return Craft::$app->getView()->renderTemplate(
            'craft-calltoaction/_components/fields/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }

    public function getElementValidationRules(): array
    {
        $rules = [];

        if ($this->required) {
            $rules = ['required'];
        }

        return array_merge(
            $rules,
            ['validateInput']
        );
    }

    public function validateInput(ElementInterface $element)
    {
        if (!$this->required) {
            return;
        }

        $value = $element->getFieldValue($this->handle);

        if ($value && is_array($value)) {
            if (!isset($value['text']) || empty($value['text'])) {
                $element->addError($this->handle, "Text value is required");
            }

            if (!isset($value['url']) || empty($value['url'])) {
                $element->addError($this->handle, "URL value is required");
            }
        }
    }

    // Field Value
    // =========================================================================

    public function normalizeValue($value, ElementInterface $element = null)
    {
        // No value
        if (is_null($value)) {
            return [
                'text' => '',
                'url' => ''
            ];
        }

        // Existing array value (i.e. on entry save)
        if (is_array($value)) {
            return $value;
        }

        // Check for wrapped " characters
        if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
            $value = substr($value, 1, strlen($value) - 2);
        }

        // If the first " is escaped, then strip slashes once
        if (substr($value, strpos($value, '"') - 1, 1) === "\\") {
            $value = stripslashes($value);
        }

        // Decode as an array
        return json_decode($value, true);
    }

    public function serializeValue($value, ElementInterface $element = null)
    {
        return json_encode($value);
    }
}
