<?php
require __DIR__.'/app/bootstrap.php';

class TestApp
    extends \Magento\Framework\App\Http
    implements \Magento\Framework\AppInterface {
    public function launch()
    {
        $calculator = $this->_objectManager->create('\MageClass\Calculator\Model\Basic');
        echo $calculator->divide(10,2);
        return $this->_response;
    }

    public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception)
    {
        return false;
    }

}

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('TestApp');
$bootstrap->run($app);