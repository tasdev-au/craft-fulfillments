<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\helpers;

use Craft;
use craft\db\Query;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     4.1.0
 */
class ProjectConfigData
{
    // Public Methods
    // =========================================================================


    // Project config rebuild methods
    // =========================================================================

    /**
     * Return a rebuilt project config array
     * @return array
     */
    public static function rebuildProjectConfig(): array
    {
        $output = [];
        $output['carriers'] = self::_getCarriersData();

        return $output;
    }

    /**
     * Return gala type data config array.
     *
     * @return array
     */
    private static function _getCarriersData(): array
    {
        $carrierRows = (new Query())
            ->select([
                'name',
                'trackingUrl',
                'isEnabled',
                'order',
                'uid'
            ])
            ->from(['{{%orderfulfillments_carriers}} carriers'])
            ->all();

        $data = [];

        foreach ($carrierRows as $carrierRow) {
            $rowUid = $carrierRow['uid'];

            unset($carrierRow['uid']);

            $data[$rowUid] = $carrierRow;
        }

        return $data;
    }
}
