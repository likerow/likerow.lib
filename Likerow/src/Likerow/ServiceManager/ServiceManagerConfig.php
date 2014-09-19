<?php

namespace Likerow\ServiceManager;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;
use Zend\Log;
use Likerow\Util\String;
use Likerow\Util\ErrorHandler;
use Likerow\Util\Server;

class ServiceManagerConfig extends Config {

    private $_service = array();

    public function setService(array $service = array()) {
        $this->_service = $service;
    }

    public function getFactories() {
        $smc = $this;
        $services = array(
            /*
             * Main logger object
             */
            'Likerow\Logger' => function (ServiceManager $sm) use($smc) {
        $config = $sm->get('Config');
        if (!isset($config['likerow_server']['logger'])) {
            throw new Exception\ConfigNotFoundException('logger');
        }
        $loggerConfig = $config['likerow_server']['logger'];
        if (!isset($loggerConfig['writers'])) {
            throw new Exception\ConfigNotFoundException('logger/writers');
        }

        $logger = new Log\Logger();

        if (count($loggerConfig['writers'])) {

            $priority = 1;
            foreach ($loggerConfig['writers'] as $writerConfig) {
                $writerConfig['options']['stream'] = 'data/log/' . date('Y-m-d') . '-checkout.log';
                $writer = $logger->writerPlugin($writerConfig['name'], $writerConfig['options']);
                if (isset($writerConfig['filters']) && is_array($writerConfig['filters'])) {
                    foreach ($writerConfig['filters'] as $filterName => $filterValue) {
                        $filterClass = '\Zend\Log\Filter\\' . String::underscoreToCamelCase($filterName);
                        $filter = new $filterClass($filterValue);
                        $writer->addFilter($filter);
                    }
                }

                if (isset($writerConfig['formatter']) && is_array($writerConfig['formatter']) && isset($writerConfig['formatter'])) {
                    $formatterConfig = $writerConfig['formatter'];
                    if (isset($formatterConfig['format'])) {
                        $formatter = new Log\Formatter\Simple($formatterConfig['format']);
                        if (isset($formatterConfig['dateTimeFormat'])) {
                            $formatter->setDateTimeFormat($formatterConfig['dateTimeFormat']);
                        }
                        $writer->setFormatter($formatter);
                    }
                }
                $logger->addWriter($writer, $priority ++);
            }
        }
        return $logger;
    },
            'Likerow\ErrorHandler' => function (ServiceManager $sm) {
        return new ErrorHandler($sm->get('Likerow\Logger'));
    },
            'Zend\HttpRequest' => function (ServiceManager $sm) {
        $request = new \Zend\Http\PhpEnvironment\Request();
        return $request;
    },
            'Server' => function (ServiceManager $sm) {
        $config = $sm->get('Config');
        return new Server($config);
    },
            'Likerow\Storage\DBStorage' => function (ServiceManager $sm) {
        $dbAdapter = $sm->get('dbsession');
        $conf = $sm->get('Config');
        $config = null;
        if (isset($conf['likerow_server']['session']) && isset($conf['likerow_server']['session']['sessionConfig'])) {
            $config = $conf['likerow_server']['session']['sessionConfig'];
        }
        $dbSession = new \Likerow\Storage\DBStorage($dbAdapter, $config);
        return $dbSession;
    },
            'Mail' => function (ServiceManager $sm) {
        return new \Likerow\Util\Mail($sm);
    },
            'Cache' => function (ServiceManager $sm) {
        
        $config = $sm->get('Config');
        if (!isset($config['likerow_server']['cache'])) {
            throw new \Exception('Error en congfiguracion de cache');
        }
        return \Zend\Cache\StorageFactory::factory($config['likerow_server']['cache']);        
    },
        );

        if (!empty($this->_service)) {
            foreach ($this->_service as $indice => $value) {
                $services[$indice] = $value;
            }
        }
        return $services;
    }

}
