<?php

/**
 * File for UserAuthentication Class
 *
 * @category   User
 * @package    User_Controller
 * @subpackage User_Controller_Plugin
 * @author     Marco Neumann <webcoder_at_binware_dot_org>
 * @copyright  Copyright (c) 2011, Marco Neumann
 * @license    http://binware.org/license/index/type:new-bsd New BSD License
 */
/**
 * @namespace
 */

namespace Likerow\Controller\Plugin;

/**
 * @uses Zend\Mvc\Controller\Plugin\AbstractPlugin
 * @uses Zend\Authentication\AuthenticationService
 * @uses Zend\Authentication\Adapter\DbTable
 */
use Zend\Json\Json;
use Zend\Mvc\Controller\Plugin\AbstractPlugin,
    Zend\Authentication\AuthenticationService,
    Zend\Authentication\Adapter\DbTable as AuthAdapter;

/**
 * Class for User Authentication
 *
 * Handles Auth Adapter and Auth Service to check Identity
 *
 * @category   User
 * @package    User_Controller
 * @subpackage User_Controller_Plugin
 * @copyright  Copyright (c) 2011, Marco Neumann
 * @license    http://binware.org/license/index/type:new-bsd New BSD License
 */
class UserAuthentication extends AbstractPlugin {

    /**
     * @var AuthAdapter
     */
    protected $_authAdapter = null;

    /**
     * @var AuthenticationService
     */
    protected $_authService = null;

    /**
     * Check if Identity is present
     *
     * @return bool
     */
    public function hasIdentity() {
        return $this->getAuthService()->hasIdentity();
    }

    /**
     * Return current Identity
     *
     * @return mixed|null
     */
    public function getIdentity() {
        return $this->getAuthService()->getIdentity();
    }

    /**
     * Sets Auth Adapter
     *
     * @param \Zend\Authentication\Adapter\DbTable $authAdapter
     * @return UserAuthentication
     */
    public function setAuthAdapter(AuthAdapter $authAdapter) {
        $this->_authAdapter = $authAdapter;

        return $this;
    }

    /**
     * Returns Auth Adapter
     *
     * @return \Zend\Authentication\Adapter\DbTable
     */
    public function getAuthAdapter() {
        if ($this->_authAdapter === null) {
            $this->setAuthAdapter(new AuthAdapter());
        }

        return $this->_authAdapter;
    }

    /**
     * Sets Auth Service
     *
     * @param \Zend\Authentication\AuthenticationService $authService
     * @return UserAuthentication
     */
    public function setAuthService(AuthenticationService $authService) {
        $this->_authService = $authService;

        return $this;
    }

    /**
     * Gets Auth Service
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    public function getAuthService() {
        if ($this->_authService === null) {
            $this->setAuthService(new AuthenticationService());
        }

        return $this->_authService;
    }

    /**
     * If user is inactive for 5 minutes, log them out.
     * 
     * @return void | Zend\Mvc\Controller\Plugin\Redirect
     */
    public function timeout($uri) {
        // User Active so reset time session. (5minutes)
        // Note: (I'm using $this->identity which is basically an Auth object)
        // but would work the same way as $_SESSION['timeout']
        $identity = $this->getIdentity();
        if (!empty($identity) && $_SESSION['__ZF']['_REQUEST_ACCESS_TIME'] + 600 > time()) {
            $_SESSION['__ZF']['_REQUEST_ACCESS_TIME'] = time();
        }

        // session timed out, logout user
        // this routes to the location of destroying the session, etc.
        else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo Json::encode(array('status' => 0));
            } else {
                header('location:' . $uri);
            }
            exit;
        }
    }

}
