<?php

/**
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite\services;

use boxhead\churchsuite\ChurchSuite;
use Craft;
use craft\base\Component;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use GuzzleHttp\Client;

/**
 * @author    Boxhead
 * @package   ChurchSuite
 */
class ChurchSuiteService extends Component
{
    private $settings;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        // Check for all required settings
        $this->checkSettings();
    }

    public function getLocalData($limit = 2000): array
    {
        // Create a Craft Element Criteria Model
        $query = Entry::find()
            ->sectionId($this->settings->sectionId)
            ->limit($limit)
            ->status(null)
            ->all();

        $data = array(
            'ids' => [],
            'smallgroups' => []
        );

        // For each entry
        foreach ($query as $entry) {
            $smallGroupId = "";

            // Get the id of this Small Group
            if (isset($entry->smallGroupId)) {
                $smallGroupId = $entry->smallGroupId;
            }

            // Add this id to our array
            $data['ids'][] = $smallGroupId;

            // Add this entry id to our array, using the small group id as the key for reference
            $data['smallgroups'][$smallGroupId] = $entry->id;
        }

        return $data;
    }

    public function getAPIData(): array
    {
        Craft::info('ChurchSuite: Begin sync with API', __METHOD__);

        // Get all ChurchSuite small groups
        $client = new Client();

        $url = 'https://weareemmanuel.churchsuite.com/embed/v2/smallgroups/json';

        $response = $client->request('GET', $url, [
            'query' => [
                'view' => 'active_future',
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Account' => 'weareemmanuel',
                'X-Application' => 'WeAreEmmanuel-Website',
                'X-Auth' => $this->settings->apiKey,
            ],
        ]);

        // Do we have a success response?
        if ($response->getStatusCode() !== 200) {
            Craft::error('ChurchSuite: API Reponse Error ' . $response->getStatusCode() . ": " . $response->getReasonPhrase(), __METHOD__);

            return false;
        }

        $body = json_decode($response->getBody());

        // Are there any results
        if (!isset($body->data) || !count($body->data)) {
            Craft::error('ChurchSuite: No results from API Request', __METHOD__);

            return false;
        }

        $data = array(
            'ids' => [],
            'smallgroups' => [],
        );

        // For each Small Group
        foreach ($body->data as $group) {
            // Get the id
            $smallGroupId = $group->id;

            // Add this id to our array
            $data['ids'][] = $smallGroupId;

            // Add this small group to our array, using the id as the key
            $data['smallgroups'][$smallGroupId] = $group;
        }

        // Save a reference to any label data too
        $data['labels'] = $body->labels ?? [];

        Craft::info('ChurchSuite: Finished getting remote data', __METHOD__);

        return $data;
    }

    public function createEntry($group, $labels): void
    {
        // Create a new instance of the Craft Entry Model
        $entry = new Entry();

        // Set the section id
        $entry->sectionId = $this->settings->sectionId;

        // Set the entry type
        $entry->typeId = $this->settings->entryTypeId;

        // Set the author as super admin
        $entry->authorId = 1;

        $this->saveFieldData($entry, $group, $labels);
    }

    public function updateEntry($entryId, $group, $labels): void
    {
        // Create a new instance of the Craft Entry Model
        $entry = Entry::find()
            ->sectionId($this->settings->sectionId)
            ->id($entryId)
            ->status(null)
            ->one();

        $this->saveFieldData($entry, $group, $labels);
    }

    public function closeEntry($entryId): void
    {
        // Create a new instance of the Craft Entry Model
        $entry = Entry::find()
            ->sectionId($this->settings->sectionId)
            ->id($entryId)
            ->status(null)
            ->one();

        $entry->enabled = false;

        // Re-save the entry
        Craft::$app->elements->saveElement($entry);
    }

    // Private Methods
    // =========================================================================

    private function checkSettings(): bool
    {
        $this->settings = ChurchSuite::$plugin->getSettings();

        // Check our Plugin's settings for the apiKey
        if ($this->settings->apiKey === null) {
            Craft::error('ChurchSuite: No API Key provided in settings', __METHOD__);

            return false;
        }

        if (!$this->settings->sectionId) {
            Craft::error('ChurchSuite: No Section ID provided in settings', __METHOD__);

            return false;
        }

        if (!$this->settings->entryTypeId) {
            Craft::error('ChurchSuite: No Entry Type ID provided in settings', __METHOD__);

            return false;
        }

        if (!$this->settings->categoryGroupId) {
            Craft::error('ChurchSuite: No General Category Group ID provided in settings', __METHOD__);

            return false;
        }

        if (!$this->settings->sitesCategoryGroupId) {
            Craft::error('ChurchSuite: No Sites Category Group ID provided in settings', __METHOD__);

            return false;
        }

        return true;
    }

    private function saveFieldData($entry, $group, $labels): bool
    {
        // Enabled?
        $entry->enabled = ($group->embed_visible == "1") ? true : false;

        // Set the title
        $entry->title = $group->name;

        $leaders = $this->getLeaders($group);

        // Set the other content
        $entry->setFieldValues([
            'smallGroupId' => $group->id,
            'smallGroupIdentifier' => $group->identifier,
            'smallGroupName' => $group->name,
            'smallGroupDescription' => $group->description,
            'smallGroupDay' => $group->day,
            'smallGroupFrequency' => $group->frequency,
            'smallGroupTime' => $group->time,
            'smallGroupStartDate' => $group->date_start,
            'smallGroupEndDate' => $group->date_end,
            'smallGroupSignupStartDate' => $group->signup_date_start,
            'smallGroupSignupEndDate' => $group->signup_date_end,
            'smallGroupCapacity' => $group->signup_capacity,
            'smallGroupNumberMembers' => $group->no_members,
            'smallGroupLeaders' => $leaders,
            'smallGroupAddress' => (isset($group->location->address)) ? $group->location->address : '',
            'smallGroupAddressName' => (isset($group->location->address_name)) ? $group->location->address_name : '',
            'smallGroupLatitude' => (isset($group->location->latitude)) ? $group->location->latitude : '',
            'smallGroupLongitude' => (isset($group->location->longitude)) ? $group->location->longitude : '',
            'smallGroupCategories' => (isset($group->labels)) ? $this->parseLabels($group, $labels) : [],
            'smallGroupSite' => (isset($group->site)) ? $this->parseSite($group->site) : $this->parseSite(null),
        ]);

        // Save the entry!
        if (!Craft::$app->elements->saveElement($entry)) {
            Craft::error('ChurchSuite: Couldn’t save the entry "' . $entry->title . '"', __METHOD__);

            return false;
        }

        // Set the postdate to now
        $entry->postDate = DateTimeHelper::toDateTime(time());

        // Re-save the entry
        Craft::$app->elements->saveElement($entry);

        return true;
    }

    private function getLeaders($group): string
    {
        $leaders = '';

        if (!isset($group->custom_fields)) {
            return $leaders;
        }

        foreach ($group->custom_fields as $custom_field) {
            if (isset($custom_field->name) && isset($custom_field->value) && $custom_field->name === 'Leaders') {
                $leaders = $custom_field->value;
            }
        }

        return $leaders;
    }

    private function parseLabels($group, $labels): array
    {
        // If there is no category group specified, don't do this
        if (!$this->settings->categoryGroupId) {
            return [];
        }

        // Are there any labels even assigned?
        if (!$group->labels) {
            return [];
        }

        // Get all existing categories
        $categories = [];

        // Create a Craft Element Criteria Model
        $query = Category::find()
            ->groupId($this->settings->categoryGroupId)
            ->all();

        // For each category
        foreach ($query as $category) {
            // Add its slug and id to our array
            $categories[$category->slug] = $category->id;
        }

        // Parse the assigned labels actual name from separate array
        $assignedLabels = [];

        foreach ($group->labels as $label) {
            foreach ($labels as $labelMeta) {
                if ($label->id === $labelMeta->id) {
                    $assignedLabels[] = $labelMeta;
                }
            }
        }

        $returnIds = [];

        // Loop over labels assigned to the group
        foreach ($assignedLabels as $label) {
            // We just need the text
            $tagSlug = ElementHelper::normalizeSlug($label->name);
            $categorySet = false;

            // Does this label exist already as a category?
            foreach ($categories as $slug => $id) {
                // Label already a category
                if ($tagSlug === $slug) {
                    $returnIds[] = $id;
                    $categorySet = true;

                    break;
                }
            }

            // Do we need to create the Category?
            if (!$categorySet) {
                // Create the category
                $newCategory = new Category();

                $newCategory->title = $label->name;
                $newCategory->groupId = $this->settings->categoryGroupId;

                // Save the category!
                if (!Craft::$app->elements->saveElement($newCategory)) {
                    Craft::error('ChurchSuite: Couldn’t save the category "' . $newCategory->title . '"', __METHOD__);

                    return false;
                }

                $returnIds[] = $newCategory->id;
            }
        }

        return $returnIds;
    }

    private function parseSite($site): mixed
    {
        // If there is no category group specified, don't do this
        if (!$this->settings->sitesCategoryGroupId) {
            return false;
        }

        $categories = [];

        // Create a Craft Element Criteria Model
        $query = Category::find()
            ->groupId($this->settings->sitesCategoryGroupId)
            ->all();

        // For each category
        foreach ($query as $category) {
            // Add its churchSuiteSiteId and id to our array
            $categories[$category->churchSuiteSiteId] = $category->id;
        }

        $returnIds = [];

        // If $site is null we should infer that this means the group should be assigned to all the site categories.
        if ($site == null) {
            // Does this site exist already as a category?
            foreach ($categories as $id) {
                $returnIds[] = $id;
            }

            return $returnIds;
        }

        // Does this site exist already as a category?
        $categorySet = false;

        foreach ($categories as $churchSuiteSiteId => $id) {
            // Site already a category
            if ($churchSuiteSiteId == $site->id) {
                $returnIds[] = $id;
                $categorySet = true;

                break;
            }
        }

        // Do we need to create the Category?
        if (!$categorySet) {
            // Create the category
            $newCategory = new Category();

            $newCategory->title = $site->name;
            $newCategory->groupId = $this->settings->sitesCategoryGroupId;

            $newCategory->setFieldValues([
                'churchSuiteSiteId' => $site->id,
            ]);

            // Save the category!
            if (!Craft::$app->elements->saveElement($newCategory)) {
                Craft::error('ChurchSuite: Couldn’t save the category "' . $newCategory->title . '"', __METHOD__);

                return false;
            }

            $returnIds[] = $newCategory->id;
        }

        return $returnIds;
    }
}
