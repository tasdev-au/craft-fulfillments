<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\models;


use craft\base\Model;
use craft\helpers\UrlHelper;


/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     4.1.0
 */
class Carrier extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var ?int
     */
    public ?int $id = null;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $trackingUrl;

    /**
     * @var bool
     */
    public bool $isEnabled = true;

    /**
     * @var int
     */
    public int $order = 0;

    /**
     * @var string
     */
    public string $uid;

    /**
     * @var ?string
     */
    public ?string $legacyClass;


    // Public Methods
    // =========================================================================

    /**
     * Returns the display name of the carrier.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the tracking URL for the carrier formatted with the tracking number.
     *
     * @param int|string $trackingNumber
     * @return string
     */
    public function getTrackingUrl(int|string $trackingNumber): string
    {
        return str_replace('{trackingNumber}', $trackingNumber, $this->trackingUrl);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('order-fulfillments/settings/carriers/' . $this->id);
    }
}
