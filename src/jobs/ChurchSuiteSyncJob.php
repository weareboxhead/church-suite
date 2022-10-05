<?php

/**
 * @link      https://boxhead.io
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite\jobs;

use craft\helpers\Queue;
use craft\queue\BaseJob;
use boxhead\churchsuite\ChurchSuite;
use boxhead\churchsuite\jobs\ChurchSuiteCreateSmallGroupItemJob;
use boxhead\churchsuite\jobs\ChurchSuiteUpdateSmallGroupItemJob;

class ChurchSuiteSyncJob extends BaseJob
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        // Get local data
        $localData = ChurchSuite::$plugin->churchSuiteService->getLocalData(null);

        $remoteData = ChurchSuite::$plugin->churchSuiteService->getAPIData();

        // Determine which entries we are missing by id
        $missingIds = array_diff($remoteData['ids'], $localData['ids']);

        // Determine which entries we shouldn't have by id
        $removeIds = array_diff($localData['ids'], $remoteData['ids']);

        // Determine which entries need updating (all active entries which we haven't just created)
        $updatingIds = array_diff($remoteData['ids'], $missingIds);

        $stepsCount = count($missingIds) + count($removeIds) + count($updatingIds);

        // Create all missing small groups
        foreach ($missingIds as $i => $id) {
            $this->setProgress(
                $queue,
                $i / $stepsCount,
                \Craft::t('app', 'Creating ChurchSuite Small Groups {step, number} of {total, number}', [
                    'step' => $i + 1,
                    'total' => $stepsCount,
                ])
            );

            Queue::push(new ChurchSuiteCreateSmallGroupItemJob($remoteData['smallgroups'][$id]));
        }

        // Update all existing small group entries
        foreach ($updatingIds as $i => $id) {
            $x = $i + count($missingIds);

            $this->setProgress(
                $queue,
                $x / $stepsCount,
                \Craft::t('app', 'Updating Small Groups Entries {step, number} of {total, number}', [
                    'step' => $x + 1,
                    'total' => $stepsCount,
                ])
            );

            Queue::push(new ChurchSuiteUpdateSmallGroupItemJob($localData['smallgroups'][$id], $remoteData['smallgroups'][$id]));
        }

        //     // If we have local data that doesn't match with anything from remote we should close the local entry
        foreach ($updatingIds as $i => $id) {
            $x = $i + count($missingIds) + count($updatingIds);

            $this->setProgress(
                $queue,
                $x / $stepsCount,
                \Craft::t('app', 'Closing Small Groups Entries {step, number} of {total, number}', [
                    'step' => $x + 1,
                    'total' => $stepsCount,
                ])
            );

            ChurchSuite::$plugin->churchSuiteService->closeEntry($localData['smallgroups'][$id]);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('app', 'Syncing ChurchSuite Small Group data');
    }
}
