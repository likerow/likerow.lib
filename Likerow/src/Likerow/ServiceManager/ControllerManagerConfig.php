<?php

namespace Likerow\ServiceManager;

use Zend\ServiceManager\Config;
use Zend\Mvc\Controller\ControllerManager;


class ControllerManagerConfig extends Config
{
    public function getFactories()
    {
        return array(
            'Auth\IndexController' => function (ControllerManager $controllerManager)
            {
                $sm = $controllerManager->getServiceLocator();
                $controller = new IndexController();
                $controller->setLogger($sm->get('Bongo\Logger'));
                return $controller;
            },
        );
    }
}