<?php

/**
 * @link      https://boxhead.io
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite\controllers;

use boxhead\churchsuite\ChurchSuite;
use boxhead\churchsuite\jobs\ChurchSuiteSyncJob;
use craft\helpers\Queue;
use craft\web\Controller;

/**
 *
 * @author    Boxhead
 * @package   ChurchSuite
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['sync-with-remote'];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionSyncWithRemote() {
        Queue::push(new ChurchSuiteSyncJob());
    }
}
