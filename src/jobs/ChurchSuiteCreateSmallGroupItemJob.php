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
    private $group;

    /**
     * @inheritdoc
     */
    public function __construct($group)
    {
        $this->group = $group;
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        ChurchSuite::$plugin->churchSuiteService->createEntry($this->group);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('app', 'Creating Small Group Entry - "' . $this->group->name . '"');
    }
}
