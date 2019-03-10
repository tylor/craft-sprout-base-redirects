<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutbaseredirects\controllers;

use barrelstrength\sproutbaseredirects\elements\Redirect;
use barrelstrength\sproutbaseredirects\SproutBaseRedirects;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Craft;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Redirects controller
 */
class RedirectsController extends Controller
{
    /**
     * @param string|null   $siteHandle
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @throws \craft\errors\SiteNotFoundException
     */
    public function actionRedirectsIndexTemplate($siteHandle = null): Response
    {
        if ($siteHandle === null) {
            $primarySite = Craft::$app->getSites()->getPrimarySite();
            $siteHandle = $primarySite->handle;
        }

        $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        if (!$currentSite) {
            throw new ForbiddenHttpException(Craft::t('sprout-base-redirects', 'Something went wrong'));
        }

        return $this->renderTemplate('sprout-base-redirects/redirects', [
            'currentSite' => $currentSite
        ]);
    }

    /**
     * Edit a Redirect
     *
     * @param null          $redirectId
     * @param null          $siteHandle
     * @param Redirect|null $redirect
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \craft\errors\SiteNotFoundException
     */
    public function actionEditRedirect($redirectId = null, $siteHandle = null, Redirect $redirect = null)
    {
        if ($siteHandle === null)
        {
            $primarySite = Craft::$app->getSites()->getPrimarySite();
            $siteHandle = $primarySite->handle;
        }

        $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        if (!$currentSite) {
            throw new ForbiddenHttpException(Craft::t('sprout-base-redirects', 'Unable to identify current site.'));
        }

        $methodOptions = SproutBaseRedirects::$app->redirects->getMethods();

        // Now let's set up the actual redirect
        if ($redirect === null) {
            if ($redirectId !== null) {

                $redirect = Craft::$app->getElements()->getElementById($redirectId, Redirect::class, $currentSite->id);

                if (!$redirect) {
                    throw new NotFoundHttpException(Craft::t('sprout-base-redirects', 'Unable to find a Redirect with the given id: {id}', [
                        'id' => $redirectId
                    ]));
                }

                if (!$redirect){
                    $redirect = new Redirect();
                    $redirect->id = $redirectId;
                }

                $redirect->siteId = $currentSite->id;
            } else {
                $redirect = new Redirect();
                $redirect->siteId = $currentSite->id;
            }
        }

        $redirect->newUrl = $redirect->newUrl === null ? '' : $redirect->newUrl;

        $continueEditingUrl = 'sprout-base-redirects/redirects/edit/{id}/'.$currentSite->handle;
        $saveAsNewUrl = 'sprout-base-redirects/redirects/new/'.$currentSite->handle;

        $crumbs = [
            [
                'label' => Craft::t('sprout-base-redirects', 'Redirects'),
                'url' => UrlHelper::cpUrl('redirects')
            ]
        ];

        $tabs = [
            [
                'label' => 'Redirect',
                'url' => '#tab1',
                'class' => null,
            ]
        ];

        return $this->renderTemplate('sprout-base-redirects/redirects/_edit', [
            'currentSite' => $currentSite,
            'redirect' => $redirect,
            'methodOptions' => $methodOptions,
            'crumbs' => $crumbs,
            'tabs' => $tabs,
            'continueEditingUrl' => $continueEditingUrl,
            'saveAsNewUrl' => $saveAsNewUrl
        ]);
    }

    /**
     * Saves a Redirect
     *
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws \Throwable
     */
    public function actionSaveRedirect()
    {
        $this->requirePostRequest();

        $redirectId = Craft::$app->getRequest()->getBodyParam('redirectId');
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');

        if ($redirectId) {
            $redirect = Craft::$app->getElements()->getElementById($redirectId, Redirect::class, $siteId);

            if (!$redirect) {
                throw new Exception(Craft::t('sprout-base-redirects', 'No redirect exists with the ID “{id}”', [
                    'id' => $redirectId
                ]));
            }

            if (!$redirect){

                $redirect = new Redirect();
                $redirect->id = $redirectId;
            }
        } else {
            $redirect = new Redirect();
        }

        $defaultSiteId = Craft::$app->getSites()->getPrimarySite()->id;

        $oldUrl = Craft::$app->getRequest()->getRequiredBodyParam('oldUrl', $redirect->oldUrl);
        $newUrl = Craft::$app->getRequest()->getBodyParam('newUrl');

        // Set the event attributes, defaulting to the existing values for
        // whatever is missing from the post data
        $redirect->siteId = $siteId ?? $defaultSiteId;
        $redirect->oldUrl = $oldUrl;
        $redirect->newUrl = $newUrl;
        $redirect->method = Craft::$app->getRequest()->getRequiredBodyParam('method');
        $redirect->regex = Craft::$app->getRequest()->getBodyParam('regex');

        if (!$redirect->regex) {
            $redirect->regex = 0;
        }

        $redirect->enabled = Craft::$app->getRequest()->getBodyParam('enabled');

        if (!Craft::$app->elements->saveElement($redirect, true)) {
            Craft::$app->getSession()->setError(Craft::t('sprout-base-redirects', 'Couldn’t save redirect.'));

            // Send the event back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $redirect
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-base-redirects', 'Redirect saved.'));

        return $this->redirectToPostedUrl($redirect);
    }

    /**
     * Deletes a Redirect
     *
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionDeleteRedirect()
    {
        $this->requirePostRequest();

        $redirectId = Craft::$app->getRequest()->getRequiredBodyParam('redirectId');

        if (Craft::$app->elements->deleteElementById($redirectId)) {
            Craft::$app->getSession()->setNotice(Craft::t('sprout-base-redirects', 'Redirect deleted.'));
            $this->redirectToPostedUrl();
        } else {
            Craft::$app->getSession()->setError(Craft::t('sprout-base-redirects', 'Couldn’t delete redirect.'));
        }
    }
}