<?php

/**
 * @link      https://boxhead.io
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite\jobs;

use craft\queue\BaseJob;
use boxhead\churchsuite\ChurchSuite;

class ChurchSuiteUpdateSmallGroupItemJob extends BaseJob
{
    private ?int $entryId;
    private ?object $group;
    private ?array $labels;

    /**
     * @inheritdoc
     */
    public function __construct($entryId, $group, $labels)
    {
        $this->entryId = $entryId;
        $this->group = $group;
        $this->labels = $labels;
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        ChurchSuite::$plugin->churchSuiteService->updateEntry($this->entryId, $this->group, $this->labels);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('app', 'Updating Small Group Entry - "' . $this->group->name . '"');
    }
}
