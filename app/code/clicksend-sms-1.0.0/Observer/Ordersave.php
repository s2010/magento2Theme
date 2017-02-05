<?php
namespace Clicksend\Sms\Observer;

use Magento\Framework\Event\ObserverInterface;

class Ordersave implements ObserverInterface
{
    public $clicksendHelper;
    public function __construct(\Clicksend\Sms\Helper\Data $clicksendHelper)
    {
        $this->clicksendHelper = $clicksendHelper;
    }
	
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->clicksendHelper->log('called');
        try {
            $order = $observer->getEvent()->getData('order');
            if ($order && $order->getId() > 0) {
                if ($this->clicksendHelper->getIsClicksendSent($order->getId()) != 1) {
                    if ($this->clicksendHelper->getStoreConfig('clicksendsms/messages/sendsmsonneworder') == 1) {
                        $countryCode = $this->clicksendHelper->getStoreConfig('general/country/default');
                        $body = $this->clicksendHelper->getNewOrderMessage($order);
                        $to = $this->clicksendHelper->getStoreConfig('clicksendsms/settings/adminphone');
                        $this->clicksendHelper->sendSMS($to, $body, 'New_Order', $order->getId(), $countryCode);
                        $this->clicksendHelper->setIsClicksendSent($order->getId());
                    }
                } else if ($order->getOrigData('status') != $order->getStatus()) {
                    $cpath = 'clicksendsms/messages/sendsmsonship';
                    $sendsmsonship = $this->clicksendHelper->getStoreConfig($cpath);
                    if ($order->hasShipments() &&  $sendsmsonship== 1) {
                        $address = $order->getShippingAddress();
                        if ($address) {
                            $countryCode = $address->getCountryId();
                            $to = $address->getTelephone();
                        } else {
                            $address = $order->getBillingAddress();
                            $countryCode = $address->getCountryId();
                            $to = $address->getTelephone();
                        }
                        $body = $this->clicksendHelper->getShippedStatusMessage($order);
                        $this->clicksendHelper->sendSMS($to, $body, 'Shipped_Status', $order->getId(), $countryCode);
                    }
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
			$this->clicksendHelper->log($order->getId().': Order Save, Exception:'.$e->getMessage());
        }
    }
}
