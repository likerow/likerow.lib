<?php

/**
 * File for Event Class
 *
 * @category  User
 * @package   User_Event
 * @author    Marco Neumann <webcoder_at_binware_dot_org>
 * @copyright Copyright (c) 2011, Marco Neumann
 * @license   http://binware.org/license/index/type:new-bsd New BSD License
 */
/**
 * @namespace
 */

namespace Likerow\Event;

/**
 * @uses Zend\Mvc\MvcEvent
 * @uses User\Controller\Plugin\UserAuthentication
 * @uses User\Acl\Acl
 */
use Zend\Mvc\MvcEvent as MvcEvent,
    Bongo\Controller\Plugin\UserAuthentication as AuthPlugin,
    Bongo\Acl as AclClass;
use Zend\Session\Container;

/**
 * Authentication Event Handler Class
 *
 * This Event Handles Authentication
 *
 * @category  User
 * @package   User_Event
 * @copyright Copyright (c) 2011, Marco Neumann
 * @license   http://binware.org/license/index/type:new-bsd New BSD License
 */
class Authentication {

    const MODULE_REST = 'restful';

    /**
     * @var AuthPlugin
     */
    protected $_userAuth = null;

    /**
     * @var AclClass
     */
    protected $_aclClass = null;

    /**
     * preDispatch Event Handler
     *
     * @param \Zend\Mvc\MvcEvent $event
     * @throws \Exception
     */
    public function preDispatch(MvcEvent $event) {
        $notControl = array(
            'application:console:http-notification',
            'application:ajax:get-countries',
            'application:api:black-list',
            'application:api:save-order',
            'auth:index:index',
            'auth:tools:timeout',
            'payadmin:index:logout',
            'payadmin:index:not-access',
            'api',
            'application',
        );
        $services = $event->getTarget()->getServiceManager();
        $config = $services->get('Config');

        $routeMatch = $event->getRouteMatch();
        $controller = $routeMatch->getParam('controller');
        if ($routeMatch->getMatchedRouteName() == self::MODULE_REST) {
            return;
        }
        
        $arrayController = explode('\\', $controller);
        if (count($arrayController) > 1) {
            $moduleName = strtolower($arrayController[0]);
            $controllerName = strtolower($arrayController[2]);
            $action = $routeMatch->getParam('action');
            $uri = $moduleName . ':' . $controllerName . ':' . $action;
        } else {
            $uri = $controller;
        }
        if (in_array($moduleName, $notControl)) {
            return;
        }
        
        $acl = $this->getAclClass();
        $role = AclClass::DEFAULT_ROLE;

        if (!empty($_SESSION['Zend_Auth']['storage']['UID'])) {
            $role = 'member';
        } else {
            if (!in_array($uri, $notControl)) {
                header('location:' . $config['bongo_server']['acl']['uri_session_login']);
                exit;
            }
        }
        if (!$acl->hasResource($controller)) {
            throw new \Exception('Resource ' . $controller . ' not defined');
        }
        
        if (in_array($uri, $notControl)) {
            return;
        }
        if ($moduleName != 'auth') {
            if (empty($config['bongo_server']['acl']['modules'][$moduleName])) {
                throw new \Exception('permisos no configurados');
            }
            $moduleAccess = $config['bongo_server']['acl']['modules'][$moduleName];
            $modelUsuario = $services->get('Bongo\Model\Usuarios');
            $dataUsuario = $modelUsuario->getByIdPermisos($_SESSION['Zend_Auth']['storage']['UID'], $moduleAccess, \Bongo\Model\Entity\Usuarios::ACCESS_YES);


            if (empty($dataUsuario)) {
                header('location:' . $config['bongo_server']['acl']['uri_session_login']);
                exit;
            }
        } elseif (empty($_SESSION['Zend_Auth']['storage']['UID']) && $uri != 'auth:index:index') {
            header('location:' . $config['bongo_server']['acl']['uri_session_login']);
            exit;
        }


        /* if (!$acl->isAllowed($role, $controller)) {
          header('location:' . $config['bongo_server']['acl']['uri_sin_acceso']);
          exit;
          } */
    }

    /**
     * Sets Authentication Plugin
     *
     * @param \User\Controller\Plugin\UserAuthentication $userAuthenticationPlugin
     * @return Authentication
     */
    public function setUserAuthenticationPlugin(AuthPlugin $userAuthenticationPlugin) {
        $this->_userAuth = $userAuthenticationPlugin;

        return $this;
    }

    /**
     * Gets Authentication Plugin
     *
     * @return \User\Controller\Plugin\UserAuthentication
     */
    public function getUserAuthenticationPlugin() {
        if ($this->_userAuth === null) {
            $this->_userAuth = new AuthPlugin();
        }

        return $this->_userAuth;
    }

    /**
     * Sets ACL Class
     *
     * @param \User\Acl\Acl $aclClass
     * @return Authentication
     */
    public function setAclClass(AclClass $aclClass) {
        $this->_aclClass = $aclClass;

        return $this;
    }

    /**
     * Gets ACL Class
     *
     * @return \User\Acl\Acl
     */
    public function getAclClass() {
        if ($this->_aclClass === null) {
            $this->_aclClass = new AclClass(array());
        }

        return $this->_aclClass;
    }

}
