<?php
/**
 * Fulfillments plugin for Craft CMS 5.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\models;

use Craft;
use craft\base\Model;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use craft\helpers\Html;
use craft\helpers\Json;
use tasdev\orderfulfillments\OrderFulfillments;
use yii\base\InvalidConfigException;


/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 *
 * @property LineItem|null $lineItem
 * @property Fulfillment|null $fulfillment
 */
class FulfillmentLine extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var ?int
     */
    public ?int $id = null;

    /**
     * @var ?int
     */
    public ?int $fulfillmentId = null;

    /**
     * @var string
     */
    public string $lineItemId;

    /**
     * @var int
     */
    public int $fulfilledQty;

    /**
     * @var string
     */
    public string $uid;


    // Private Properties
    // =========================================================================

    /**
     * @var ?Fulfillment
     */
    private ?Fulfillment $_fulfillment = null;

    /**
     * @var ?LineItem
     */
    private ?LineItem $_lineItem = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns the purchasable name, or if there's no purchasable, the snapshot description.
     *
     * @return string
     */
    public function __toString(): string
    {
        $lineItem = $this->getLineItem();

        $purchasable = $lineItem->getPurchasable();
        if ($purchasable) {
            return $purchasable->getDescription();
        }

        $snapshot = Json::decodeIfJson($lineItem->snapshot);
        return Html::decode($snapshot['description']);
    }

    /**
     * Gets the fulfillment.
     *
     * @return ?Fulfillment
     */
    public function getFulfillment(): ?Fulfillment
    {
        if (!$this->_fulfillment) {
            $this->_fulfillment = OrderFulfillments::getInstance()->getFulfillments()->getFulfillmentById($this->fulfillmentId);
        }

        return $this->_fulfillment;
    }

    /**
     * Sets the fulfillment.
     *
     * @param Fulfillment $fulfillment
     */
    public function setFulfillment(Fulfillment $fulfillment)
    {
        $this->fulfillmentId = $fulfillment->id;
        $this->_fulfillment = $fulfillment;
    }

    /**
     * Gets the line item.
     *
     * @return ?LineItem
     * @throws InvalidConfigException
     */
    public function getLineItem(): ?LineItem
    {
        if (!$this->_lineItem) {
            $this->_lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($this->lineItemId);
        }

        return $this->_lineItem;
    }

    /**
     * Sets the fulfillment.
     *
     * @param LineItem $lineItem
     */
    public function setLineItem(LineItem $lineItem)
    {
        $this->lineItemId = $lineItem->id;
        $this->_lineItem = $lineItem;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['fulfillmentId', 'lineItemId', 'fulfilledQty'], 'required'],
            ['fulfilledQty', function($attribute) {
                $maxQty = OrderFulfillments::getInstance()
                    ->getFulfillmentLines()
                    ->getFulfillableQty($this->getLineItem());

                if ($this->$attribute > $maxQty) {
                    $this->addError($attribute, Craft::t('order-fulfillments', 'You can only fulfill {number} of this item.', [
                        'number' => $maxQty
                    ]));
                } else if ($this->$attribute < 0) {
                    $this->addError($attribute, Craft::t('order-fulfillments', 'The minimum fulfullable quantity is 0.', [
                        'number' => $maxQty
                    ]));
                }
            }]
        ];
    }
}
