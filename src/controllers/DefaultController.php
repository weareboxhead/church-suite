<?php

/**
 * @link      https://boxhead.io
 * @copyright Copyright (c) Boxhead
 */

namespace boxhead\churchsuite\controllers;

use boxhead\churchsuite\jobs\ChurchSuiteSyncJob;
use Craft;
use craft\helpers\Queue;
use craft\web\Controller;
use craft\web\Response;
use craft\web\View;

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
    protected array|int|bool $allowAnonymous = ['sync'];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionSync(): Response
    {
        Queue::push(new ChurchSuiteSyncJob());

        $message = 'Sync in progress.';

        return $this->getResponse($message);
    }

    /**
     * Returns a response.
     */
    private function getResponse(string $message, bool $success = true): Response
    {
        $request = Craft::$app->getRequest();

        // If front-end or JSON request
        if (Craft::$app->getView()->templateMode == View::TEMPLATE_MODE_SITE || $request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $success,
                'message' => Craft::t('church-suite', $message),
            ]);
        }

        if ($success) {
            Craft::$app->getSession()->setNotice(Craft::t('church-suite', $message));
        } else {
            Craft::$app->getSession()->setError(Craft::t('church-suite', $message));
        }

        return $this->redirectToPostedUrl();
    }
}
