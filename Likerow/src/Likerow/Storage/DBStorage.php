<?php

/**
 * This file is part of the DBSessionStorage Module (https://github.com/Nitecon/DBSessionStorage.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/DBSessionStorage.git)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */

namespace Likerow\Storage;

use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;
use Zend\Db\Adapter\Adapter;
use Zend\Session\SessionManager;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;
use Zend\Json\Json;

class DBStorage {

    protected $adapter;
    protected $tblGW;
    protected $sessionConfig;

    public function __construct(Adapter $adapter, $session_config) {
        $this->adapter = $adapter;
        $this->sessionConfig = $session_config;
        $this->tblGW = new \Zend\Db\TableGateway\TableGateway('sessions', $this->adapter);
    }

    public function setSessionStorage($sistema = 'auht') {
        $gwOpts = new DbTableGatewayOptions();
        $gwOpts->setDataColumn('session_data');
        $gwOpts->setIdColumn('session');
        $gwOpts->setLifetimeColumn('session_expires');
        $gwOpts->setModifiedColumn('modified');
        $gwOpts->setNameColumn('name');

        $saveHandler = new DbTableGateway($this->tblGW, $gwOpts);
        $sessionManager = new SessionManager();
        if ($this->sessionConfig) {
            $sessionConfig = new \Zend\Session\Config\SessionConfig();
            $sessionConfig->setOptions($this->sessionConfig);
            $sessionManager->setConfig($sessionConfig);
        }
        $sessionManager->setSaveHandler($saveHandler);
        Container::setDefaultManager($sessionManager);
        if ($sistema != 'checkout') {
            $this->timeout($sessionManager);
        }
        $sessionManager->start();
    }

    private function timeout($sessionManager) {
        if (isset($_SERVER['REQUEST_URI'])) {
            $request = $_SERVER['REQUEST_URI'];
            if (strpos($request, 'auth/timeout')) {
                $responce = array('status' => -1);
                if (!empty($_COOKIE[$this->sessionConfig['name']])) {
                    $adapter = $this->adapter;
                    $sql = new Sql($adapter);
                    $select = $sql->select()->from(array('t1' => 'sessions'))
                            ->columns(array('*'))
                            ->where(array('session=?' => $_COOKIE[$this->sessionConfig['name']]));
                    $selectString = $sql->getSqlStringForSqlObject($select);
                    $data = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
                    if (!empty($data[0])) {
                        if ($data[0]['modified'] + 3600 > time()) {
                            $responce = array('status' => 1);
                        }
                    }
                }
                if ($responce['status'] < 0) {
                    $sessionManager->start();
                    $sessionManager->destroy();
                }
                echo Json::encode($responce);
                exit;
            }
        }
    }

}
