<?php
namespace Clicksend\Sms\Helper;

use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Monolog\Handler\StreamHandler;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $logger;
	
    public function __construct(Context $context, LoggerInterface $logger, DirectoryList $directory_list)
    {
        $this->logger = $logger;
        $monohander = new StreamHandler($directory_list->getRoot().'/var/log/clicksendsms.log');
        $this->logger->pushHandler($monohander);
        parent::__construct($context);
    }
    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getVariables()
    {
        $variables = ['{ORDER-NUMBER}', '{ORDER-TOTAL}', '{ORDER-STATUS}', '{CARRIER-NAME}', '{PAYMENT-NAME}','{CUSTOMER-NAME}', '{CUSTOMER-EMAIL}'];
        return $variables;
    }
    public function getIsClicksendSent($order_id)
    {
        $objManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objManager->get('Magento\Framework\App\ResourceConnection');
        $connection= $resource->getConnection();
        $table = $resource->getTableName('sales_order');
        $query = "select is_clicksend_send from {$table} where entity_id = ".(int)($order_id);
        return (int)($connection->fetchOne($query));
    }
    public function setIsClicksendSent($order_id)
    {
        $objManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objManager->get('Magento\Framework\App\ResourceConnection');
        $connection= $resource->getConnection();
        $table = $resource->getTableName('sales_order');
        $query = "update {$table} set is_clicksend_send=1 where entity_id = ".(int)($order_id);
        $connection->query($query);
    }
    public function getIncrementId($order)
    {
        $incrementId = $order->getOriginalIncrementId();
        if ($incrementId == null || empty($incrementId) || !$incrementId) {
            $incrementId = $order->getIncrementId();
        }
        return $incrementId;
    }
    public function getNewOrderMessage($order)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');
        $variables = $this->getVariables();
        $values =  [ $this->getIncrementId($order), strip_tags($priceHelper->currency($order->getGrandTotal())), $order->getStatus(), $order->getShippingDescription(), $order->getPayment()->getMethodInstance()->getTitle(), $order->getCustomerFirstname().' '.$order->getCustomerLastname(), $order->getCustomerEmail() ];
        $message = $this->getStoreConfig('clicksendsms/messages/sendsmsonnewordermessage');
        return  str_replace($variables, $values, $message);
    }
    public function getShippedStatusMessage($order)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');
        $variables = $this->getVariables();
        $values =  [ $this->getIncrementId($order), strip_tags($priceHelper->currency($order->getGrandTotal())), $order->getStatus(), $order->getShippingDescription(), $order->getPayment()->getMethodInstance()->getTitle(), $order->getCustomerFirstname().' '.$order->getCustomerLastname(), $order->getCustomerEmail() ];
        $message = $this->getStoreConfig('clicksendsms/messages/sendsmsonshipmessage');
        return  str_replace($variables, $values, $message);
    }
    public function sendSMS($to, $body, $action, $id_order, $countryCode)
    {
        try {
            $source = 'magento2';
            $from = $this->getStoreConfig('clicksendsms/settings/sender');
            $username = $this->getStoreConfig('clicksendsms/settings/username');
            $password = $this->getStoreConfig('clicksendsms/settings/password');
            $message = ['source' => $source,'from' => $from, 'body' => $body,'to' => $to, 'country' => $countryCode];
            $data = ['messages' => [ 0 => $message ] ];
            $data = json_encode($data);
            $this->log("{$id_order}-{$action}-Request");
            $this->log($data);
			$header = ["Content-Type: application/json", "Content-Length: ".strlen($data),
            "Authorization: Basic ".base64_encode($username.':'.$password)];
			$options = [CURLOPT_URL => "https://rest.clicksend.com/v3/sms/send", CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $data, CURLOPT_HTTPHEADER => $header];
            $ch = curl_init();
			curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            if (!$response) {
                throw new \Magento\Framework\Exception\LocalizedException (curl_error($ch));
            }
            curl_close($ch);
            $this->log("{$id_order}-{$action}-Response");
            $this->log($response);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->log("{$id_order}-{$action}-Response: ".$e->getMessage());
        }
    }
    public function log($content)
    {
        if ($this->getStoreConfig('clicksendsms/settings/debug')==1) {
            $this->logger->addDebug($content);
        }
    }
}
