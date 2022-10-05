<?php

/**
 * ChurchSuite plugin for Craft CMS 3.x
 *
 * Communicate and process data from the ChurchSuite API
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite;

use boxhead\churchsuite\services\ChurchSuiteService as ChurchSuiteServiceService;
use boxhead\churchsuite\models\Settings;
use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\models\FieldGroup;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\elements\Entry;
use yii\base\Event;

/**
 *
 * @author    Boxhead
 * @package   ChurchSuite
 *
 */
class ChurchSuite extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ChurchSuite
     */
    public static ChurchSuite $plugin;

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

     /**
     * @inheritdoc
     */
    // public string $schemaVersion = '1.2.0';

    /**
     * @inheritdoc
     */
    // public string $minVersionRequired = '1.1.0';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['church-suite/sync']   = 'church-suite/default/sync-with-remote';
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                    $this->buildFieldSectionStructure();
                }
            }
        );

        Craft::info(
            Craft::t(
                'church-suite',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    protected function buildFieldSectionStructure()
    {
        // Create the Small Groups Field Group
        Craft::info('Creating the Small Groups Field Group.', __METHOD__);

        $fieldsService = Craft::$app->getFields();

        $fieldGroup = new FieldGroup();
        $fieldGroup->name = 'Small Groups';

        if (!$fieldsService->saveGroup($fieldGroup)) {
            Craft::error('Could not save the Small Groups field group.', __METHOD__);

            return false;
        }

        $fieldGroupId = $fieldGroup->id;

        // Create the Basic Fields
        Craft::info('Creating the basic Small Groups Fields.', __METHOD__);

        $basicFields = [
            [
                'handle'    => 'smallGroupId',
                'name'      => 'Small Group Id',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupIdentifier',
                'name'      => 'Small Group Identifier',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupName',
                'name'      => 'Small Group Name',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupStartDate',
                'name'      => 'Small Group Start Date',
                'type'      => 'craft\fields\Date'
            ],
            [
                'handle'    => 'smallGroupEndDate',
                'name'      => 'Small Group End Date',
                'type'      => 'craft\fields\Date'
            ],
            [
                'handle'    => 'smallGroupSignupStartDate',
                'name'      => 'Small Group Signup Start Date',
                'type'      => 'craft\fields\Date'
            ],
            [
                'handle'    => 'smallGroupSignupEndDate',
                'name'      => 'Small Group Signup End Date',
                'type'      => 'craft\fields\Date'
            ],
            [
                'handle'    => 'smallGroupFrequency',
                'name'      => 'Frequency',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupDay',
                'name'      => 'Day',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupTime',
                'name'      => 'Time',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupDescription',
                'name'      => 'Description',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupLeaders',
                'name'      => 'Small Group Leaders',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupNumberMembers',
                'name'      => 'Number of Members',
                'type'      => 'craft\fields\Number'
            ],
            [
                'handle'    => 'smallGroupCapacity',
                'name'      => 'Total Capacity',
                'type'      => 'craft\fields\Number'
            ],
            [
                'handle'    => 'smallGroupAddress',
                'name'      => 'Small Group Address',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupAddressName',
                'name'      => 'Small Group Address Name',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupLatitude',
                'name'      => 'Small Group Latitude',
                'type'      => 'craft\fields\PlainText'
            ],
            [
                'handle'    => 'smallGroupLongitude',
                'name'      => 'Small Group Longitude',
                'type'      => 'craft\fields\PlainText'
            ]
        ];

        $smallGroupsEntryLayoutIds = [];

        foreach ($basicFields as $basicField) {
            Craft::info('Creating the ' . $basicField['name'] . ' field.', __METHOD__);

            $field = $fieldsService->createField([
                'groupId' => $fieldGroupId,
                'name' => $basicField['name'],
                'handle' => $basicField['handle'],
                'type' => $basicField['type']
            ]);

            if (!$fieldsService->saveField($field)) {
                Craft::error('Could not save the ' . $basicField['name'] . ' field.', __METHOD__);

                return false;
            }

            $smallGroupsEntryLayoutIds[] = $field->id;
        }

        // Create the Small Groups General category group
        Craft::info('Creating the Small Groups General category group.', __METHOD__);

        $categoryGroup = new CategoryGroup();

        $categoryGroup->name = 'Small Groups';
        $categoryGroup->handle = 'smallGroups';

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteSettings = new CategoryGroup_SiteSettings();
            $siteSettings->siteId = $site->id;
            $siteSettings->uriFormat = null;
            $siteSettings->template = null;
            $siteSettings->hasUrls = false;

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $categoryGroup->setSiteSettings($allSiteSettings);

        if (!Craft::$app->getCategories()->saveGroup($categoryGroup)) {
            Craft::error('Could not create the Small Groups category group.', __METHOD__);

            return false;
        }

        // Create the Small Groups General Categories field
        Craft::info('Creating the Small Groups General Categories field.', __METHOD__);

        $categoriesField = $fieldsService->createField([
            'groupId'   => $fieldGroupId,
            'name'      => 'Small Group Categories',
            'handle'    => 'smallGroupCategories',
            'type'      => 'craft\fields\Categories',
            'settings'  => ['source' => 'group:' . $categoryGroup->id]
        ]);

        if (!$fieldsService->saveField($categoriesField)) {
            Craft::error('Could not save the Small Groups Categories field.', __METHOD__);

            return false;
        }

        $smallGroupsEntryLayoutIds[] = $categoriesField->id;


        // Create the Sites Field Group
        Craft::info('Creating the Sites Field Group.', __METHOD__);

        $fieldsService = Craft::$app->getFields();

        $sitesFieldGroup = new FieldGroup();
        $sitesFieldGroup->name = 'Sites';

        if (!$fieldsService->saveGroup($sitesFieldGroup)) {
            Craft::error('Could not save the Sites field group.', __METHOD__);

            return false;
        }

        $sitesFieldGroupId = $sitesFieldGroup->id;

        // Create the Basic Fields
        Craft::info('Creating the Sites Fields.', __METHOD__);

        $sitesFields = [
            [
                'handle'    => 'churchSuiteSiteId',
                'name'      => 'Site Id',
                'type'      => 'craft\fields\PlainText'
            ]
        ];

        $sitesEntryLayoutIds = [];

        foreach ($sitesFields as $sitesField) {
            Craft::info('Creating the ' . $sitesField['name'] . ' field.', __METHOD__);

            $field = $fieldsService->createField([
                'groupId' => $sitesFieldGroupId,
                'name' => $sitesField['name'],
                'handle' => $sitesField['handle'],
                'type' => $sitesField['type']
            ]);

            if (!$fieldsService->saveField($field)) {
                Craft::error('Could not save the ' . $sitesField['name'] . ' field.', __METHOD__);

                return false;
            }

            $sitesEntryLayoutIds[] = $field->id;
        }

        // Create the Sites Field Layout
        Craft::info('Creating the Sites Field Layout.', __METHOD__);

        $sitesFieldLayout = $fieldsService->assembleLayout(['Sites' => $sitesEntryLayoutIds], []);

        if (!$sitesFieldLayout) {
            Craft::error('Could not create the Sites Field Layout', __METHOD__);

            return false;
        }

        $sitesFieldLayout->type = Category::class;

        // Create the Small Groups Sites category group
        Craft::info('Creating the Small Groups Sites category group.', __METHOD__);

        $sitesCategoryGroup = new CategoryGroup();

        $sitesCategoryGroup->name = 'Sites';
        $sitesCategoryGroup->handle = 'sites';

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteSettings = new CategoryGroup_SiteSettings();
            $siteSettings->siteId = $site->id;
            $siteSettings->uriFormat = null;
            $siteSettings->template = null;
            $siteSettings->hasUrls = false;

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $sitesCategoryGroup->setSiteSettings($allSiteSettings);

        $sitesCategoryGroup->setFieldLayout($sitesFieldLayout);

        if (!Craft::$app->getCategories()->saveGroup($sitesCategoryGroup)) {
            Craft::error('Could not create the Small Groups Sites category group.', __METHOD__);

            return false;
        }

        // Create the Small Groups Sites Categories field
        Craft::info('Creating the Small Groups Sites Categories field.', __METHOD__);

        $sitesCategoriesField = $fieldsService->createField([
            'groupId'   => $fieldGroupId,
            'name'      => 'Small Group Site',
            'handle'    => 'smallGroupSite',
            'type'      => 'craft\fields\Categories',
            'settings'  => ['source' => 'group:' . $sitesCategoryGroup->id]
        ]);

        if (!$fieldsService->saveField($sitesCategoriesField)) {
            Craft::error('Could not save the Small Groups Sites Categories field.', __METHOD__);

            return false;
        }

        $smallGroupsEntryLayoutIds[] = $sitesCategoriesField->id;

        // Create the Small Groups Field Layout
        Craft::info('Creating the Small Groups Field Layout.', __METHOD__);

        $smallGroupsEntryLayout = $fieldsService->assembleLayout(['Small Groups' => $smallGroupsEntryLayoutIds], []);

        if (!$smallGroupsEntryLayout) {
            Craft::error('Could not create the Small Groups Field Layout', __METHOD__);

            return false;
        }

        $smallGroupsEntryLayout->type = Entry::class;

        // Create the Small Groups Channel
        Craft::info('Creating the Small Groups Channel.', __METHOD__);

        $smallGroupsChannelSection                   = new Section();
        $smallGroupsChannelSection->name             = 'Small Groups';
        $smallGroupsChannelSection->handle           = 'smallGroups';
        $smallGroupsChannelSection->type             = Section::TYPE_CHANNEL;
        $smallGroupsChannelSection->enableVersioning = false;

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteSettings = new Section_SiteSettings();
            $siteSettings->siteId = $site->id;
            $siteSettings->uriFormat = null;
            $siteSettings->template = null;
            $siteSettings->enabledByDefault = true;
            $siteSettings->hasUrls = false;

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $smallGroupsChannelSection->setSiteSettings($allSiteSettings);

        $sectionsService = Craft::$app->getSections();

        if (!$sectionsService->saveSection($smallGroupsChannelSection)) {
            Craft::error('Could not create the Small Groups Channel.', __METHOD__);

            return false;
        }

        // Get the array of entry types for our new section
        $smallGroupsEntryTypes = $sectionsService->getEntryTypesBySectionId($smallGroupsChannelSection->id);

        // There will only be one so get that
        $smallGroupsEntryType = $smallGroupsEntryTypes[0];
        $smallGroupsEntryType->setFieldLayout($smallGroupsEntryLayout);

        if (!$sectionsService->saveEntryType($smallGroupsEntryType)) {
            Craft::error('Could not update the Small Groups Channel Entry Type.', __METHOD__);

            return false;
        }

        // Save the settings based on the section and entry type we just created
        Craft::$app->getPlugins()->savePluginSettings($this, [
            'sectionId'             => $smallGroupsChannelSection->id,
            'entryTypeId'           => $smallGroupsEntryType->id,
            'categoryGroupId'       => $categoryGroup->id,
            'sitesCategoryGroupId'  => $sitesCategoryGroup->id
        ]);
    }



    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }


    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'church-suite/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
