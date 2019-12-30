<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\models;

use craft\commerce\Plugin as Commerce;

use Craft;
use craft\base\Model;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $fulfilledStatus = 'fulfilled';

    /**
     * @var string
     */
    public $partiallyFulfilledStatus = 'partiallyFulfilled';

    /**
     * @var bool
     */
    public $resendPartiallyFulfilledEmail = true;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $statuses = [];
        foreach (Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses() as $status) {
            $statuses[] = $status->handle;
        }

        $rules[] = [['fulfilledStatus', 'partiallyFulfilledStatus'], 'required'];
        $rules[] = [['fulfilledStatus', 'partiallyFulfilledStatus'], 'in', 'range' => $statuses];

        return $rules;
    }
}
