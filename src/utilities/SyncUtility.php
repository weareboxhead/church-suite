<?php
/**
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite\utilities;

use Craft;
use craft\base\Utility;
use boxhead\churchsuite\ChurchSuite;

class SyncUtility extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('church-suite', 'ChurchSuite Sync');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'church-suite';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        $iconPath = Craft::getAlias('@vendor/boxhead/church-suite/src/icon-mask.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('church-suite/_utility', [
            'actions' => self::getActions(),
        ]);
    }

    /**
     * Returns available actions.
     */
    public static function getActions(bool $showAll = false): array
    {
        $actions = [];

        $actions[] = [
            'id' => 'sync',
            'label' => Craft::t('church-suite', Craft::t('church-suite', 'Sync Now')),
            'instructions' => Craft::t('church-suite', 'Run the ChurchSuite Small Groups sync operation now.'),
        ];

        return $actions;
    }
}
