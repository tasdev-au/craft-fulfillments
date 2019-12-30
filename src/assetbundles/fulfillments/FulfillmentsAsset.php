<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\assetbundles\Fulfillments;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class FulfillmentsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@tasdev/orderfulfillments/assetbundles/fulfillments/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Fulfillments.js',
        ];

        $this->css = [
            'css/Fulfillments.css',
        ];

        parent::init();
    }
}
