<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutbaseredirects\elements\db;


use barrelstrength\sproutbaseredirects\elements\Redirect;
use barrelstrength\sproutredirects\SproutRedirects;
use craft\base\Plugin;
use craft\db\Connection;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Craft;

use barrelstrength\sproutbaseredirects\SproutBaseRedirects;

/**
 * RedirectQuery represents a SELECT SQL statement for Redirect Elements in a way that is independent of DBMS.
 *
 * @method Redirect[]|array all($db = null)
 * @method Redirect|array|null one($db = null)
 * @method Redirect|array|null nth(int $n, Connection $db = null)
 */
class RedirectQuery extends ElementQuery
{
    /**
     * Defined in redirects/index.twig
     *
     * @var string
     */
    public $pluginHandle;

    // General - Properties
    // =========================================================================

    public $oldUrl;

    public $newUrl;

    public $method;

    public $regex;

    public $count;

    public $status;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    /**
     * @param false|int|int[]|null $id
     *
     * @return $this|ElementQuery
     */
    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        if ($this->structureId === null) {
            $this->structureId = SproutBaseRedirects::$app->redirects->getStructureId();
        }

        $this->joinElementTable('sproutseo_redirects');

        $this->query->select([
            'sproutseo_redirects.id',
            'sproutseo_redirects.oldUrl',
            'sproutseo_redirects.newUrl',
            'sproutseo_redirects.method',
            'sproutseo_redirects.regex',
            'sproutseo_redirects.count'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'sproutseo_redirects.id', $this->id)
            );
        }

        if ($this->oldUrl) {
            $this->subQuery->andWhere(Db::parseParam(
                'sproutseo_redirects.oldUrl', $this->oldUrl)
            );
        }

        if ($this->newUrl) {
            $this->subQuery->andWhere(Db::parseParam(
                'sproutseo_redirects.newUrl', $this->newUrl)
            );
        }

        /** @var SproutRedirects $plugin */
        $plugin = Craft::$app->getPlugins()->getPlugin('sprout-redirects');
        $sproutRedirectsIsLite = $plugin !== null ? $plugin->is(SproutRedirects::EDITION_LITE) : false;
        $sproutRedirectsIsPro = $plugin !== null ? $plugin->is(SproutRedirects::EDITION_PRO) : false;

        /** @var Plugin $sproutSeoPlugin */
        $sproutSeoPlugin = Craft::$app->getPlugins()->getPlugin('sprout-seo');
        $sproutSeoPluginIsInstalled = $sproutSeoPlugin->isInstalled ?? false;

        // Only display 404s for LITE
        if (!$sproutSeoPluginIsInstalled && $sproutRedirectsIsLite) {
            if ($this->method === null || $this->method === 404) {
                // Only display 404s for All Redirects and 404 filters
                $this->subQuery->andWhere(Db::parseParam(
                    'sproutseo_redirects.method', 404)
                );
            } else {
                // Don't display any results for 301s, 302s
                $this->query->limit(0);
            }
        }

        // Filter all methods for PRO
        if ($sproutSeoPluginIsInstalled OR $sproutRedirectsIsPro && $this->method) {
            $this->subQuery->andWhere(Db::parseParam(
                'sproutseo_redirects.method', $this->method)
            );
        }

        return parent::beforePrepare();
    }
}
