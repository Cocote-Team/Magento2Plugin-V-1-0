<?php

namespace Cocote\Feed\Model\Payment;

/**
 * Pay In Store payment method model
 */
class Cocote extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'cocote';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     * @deprecated 100.2.0
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if($quote->getCocote()==1) {
            return true;
        }
    }
}