<?php
/**
 * SMSGlobal SMS Integration with Magento developed by SMSGlobal Team (Allam Praveen)
 * Copyright (C) 2018  SMSGlobal
 *
 * This file included in Smsglobal/Sms is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Smsglobal\Sms\Observer\Sales;

use Smsglobal\Sms\Logger\Logger as Logger;

class OrderCreditmemoSaveAfter implements \Magento\Framework\Event\ObserverInterface
{


    protected $smsHelper;
    protected $logger;


    public function __construct(\Smsglobal\Sms\Helper\Sms $smsHelper, Logger $logger
    )
    {
        $this->smsHelper = $smsHelper;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
        if ($this->smsHelper->getRefundOrderSmsEnabled()) {

            $creditmemo = $observer->getEvent()->getCreditmemo()->getData();
            $this->logger->info('Credit memo', [$creditmemo]);
            $orderId = $creditmemo['order_id'];
            $this->logger->info('Order Refund SMS Initiated', [$orderId]);
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);

            $address = $order->getShippingAddress() ?? $order->getBillingAddress();

            if (($address instanceof \Magento\Sales\Model\Order\Address) === false) {
                $this->logger->info("Billing/Shipping address not found");

                return;
            }

            $destination = $address->getTelephone();

            $this->logger->info('Customer Mobile:', [$destination]);

            if ($destination) {
                $origin = $this->smsHelper->getRefundOrderSmsSenderId();
                $message = $this->smsHelper->getRefundOrderSmsText();
                $adminNotify = $this->smsHelper->getRefundOrderSmsAdminNotifyEnabled();
                $trigger = "Order Refunded";
                $data = $this->smsHelper->getOrderData($order);
                $data['CustomerTelephone'] = $destination;
                $message = $this->smsHelper->messageProcessor($message, $data);
                $this->smsHelper->sendSms($origin, $destination, $message, null, $trigger, $adminNotify);
            }
        }
    }
}
