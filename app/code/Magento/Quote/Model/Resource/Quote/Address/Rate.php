<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Quote\Model\Resource\Quote\Address;

use Magento\Framework\Model\Resource\Db\VersionControl\AbstractDb;

/**
 * Quote address shipping rate resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Rate extends AbstractDb
{
    /**
     * Main table and field initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('quote_shipping_rate', 'rate_id');
    }
}
