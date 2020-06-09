<?php

namespace Ingenico\Payment\Model;

class Schedule
{
    protected $_cfg;
    protected $_logger;
    protected $_connector;

    public function __construct(
        \Ingenico\Payment\Model\Connector $connector,
        \Ingenico\Payment\Model\Config $systemConfig
    ) {
        $this->_connector = $connector;
        $this->_cfg = $systemConfig;
    }

    public function run()
    {
        $this->_connector->cronHandler();
    }
}
