<?php
/**
 * ChurchSuite plugin for Craft CMS 3.x
 *
 * Communicate and process data from the ChurchSuite API
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2018 Boxhead
 */

namespace boxhead\churchsuite\models;

use boxhead\churchsuite\ChurchSuite;

use Craft;
use craft\base\Model;

/**
 * ChurchSuite Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Boxhead
 * @package   ChurchSuite
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $apiKey = '';
    public $sectionId = '';
    public $entryTypeId = '';
    public $categoryGroupId = '';
    public $sitesCategoryGroupId = '';

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['apiKey', 'required'],
            ['sectionId', 'required'],
            ['entryTypeId', 'required'],
            ['categoryGroupId', 'required'],
            ['sitesCategoryGroupId', 'required'],
        ];
    }
}
