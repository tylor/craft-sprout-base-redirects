<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutbaseredirects\elements\actions;

use barrelstrength\sproutbaseredirects\enums\RedirectMethods;
use barrelstrength\sproutbaseredirects\SproutBaseRedirects;
use craft\base\ElementAction;
use Craft;
use craft\elements\db\ElementQueryInterface;

/**
 * @todo - refactor and clean up
 *
 * @property string $triggerLabel
 */
class ChangePermanentMethod extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public $confirmationMessage;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public $successMessage;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('sprout-base-redirects', 'Update Method to 301');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return $this->confirmationMessage;
    }

    /**
     * @param ElementQueryInterface $query
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementIds = $query->ids();

        $response = SproutBaseRedirects::$app->redirects->updateRedirectMethod($elementIds, RedirectMethods::Permanent);

        $message = SproutBaseRedirects::$app->redirects->getMethodUpdateResponse($response);

        $this->setMessage($message);

        return $response;
    }
}