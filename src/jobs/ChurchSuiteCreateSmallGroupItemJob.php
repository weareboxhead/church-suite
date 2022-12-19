<?php

/**
 * @link      https://boxhead.io
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite\jobs;

use craft\queue\BaseJob;
use boxhead\churchsuite\ChurchSuite;

class ChurchSuiteCreateSmallGroupItemJob extends BaseJob
{
    private ?object $group;
    private ?array $labels;

    /**
     * @inheritdoc
     */
    public function __construct($group, $labels)
    {
        $this->group = $group;
        $this->labels = $labels;
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        ChurchSuite::$plugin->churchSuiteService->createEntry($this->group, $this->labels);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('app', 'Creating Small Group Entry - "' . $this->group->name . '"');
    }
}
