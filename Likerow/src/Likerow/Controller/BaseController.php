<?php

namespace Likerow\Controller;

use Zend\Log\Logger;


abstract class BaseController extends \Zend\Mvc\Controller\AbstractActionController
{

    /**
     * @var Logger
     */
    protected $logger = null;

    protected $logIdent = 'abstract base';

    
    public function getZParam(){
        
    }
    public function getZParams(){
        $data = array();
        $data[] = \Bongo\Util\Util::stripTags($this->params()->fromRoute());
        $data[] = \Bongo\Util\Util::stripTags($this->params()->fromPost());
        $data[] = \Bongo\Util\Util::stripTags($this->params()->fromQuery());
        $params = \Bongo\Util\Util::mergeParams($data);
        return $params;
    }

    /**
     * Retorna el los servicios definidos
     * @return type
     */
    public function service() {
        return $this->getServiceLocator();
    }
    /**
     * Sets the logger.
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    

    /**
     * Returns the logger.
     * 
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }


    protected function debug($message)
    {
        $this->logDebug($message);
    }


    protected function logDebug($message)
    {
        $this->log($message, \Zend\Log\Logger::DEBUG);
    }


    protected function logInfo($message)
    {
        $this->log($message, \Zend\Log\Logger::INFO);
    }


    protected function logError($message)
    {
        $this->log($message, \Zend\Log\Logger::ERR);
    }


    protected function log($message, $priority = \Zend\Log\Logger::INFO)
    {
        $logger = $this->getLogger();
        if ($logger instanceof Logger) {
            $logger->log($priority, $this->formatLogMessage($message));
        }
    }


    protected function formatLogMessage($message)
    {
        return sprintf("CONTROLLER [%s] %s", $this->logIdent, $message);
    }


    /**
     * Redirects to the specified route.
     * 
     * @param string $routeName
     * @param array $params
     * @param array $options
     * @return \Zend\Http\Response
     */
    protected function redirectToRoute($routeName, Array $params = array(), Array $options = array())
    {
        $path = $this->url()->fromRoute($routeName, $params, $options);
        
        $uri = new \Zend\Uri\Http($this->getBaseUri());
        $uri->setPath($path);
        
        return $this->redirect()->toUrl($uri->toString());
    }


    /**
     * Returns the base URI.
     * 
     * @return \Zend\Uri\Http
     */
    protected function getBaseUri()
    {
        $uri = $this->getRequest()->getUri();
        $uri->setPath('');
        $uri->setQuery(array());
        $uri->setFragment('');
        
        return $uri;
    }


    /**
     * Handles an exception - returns an error.
     * 
     * @param \Exception $e
     * @param string $label
     */
    protected function handleException(\Exception $e, $label = 'Exception')
    {
        _dump("$e");
        return $this->handleError(sprintf("%s: [%s] %s", $label, get_class($e), $e->getMessage()));
    }


    /**
     * Handles an application error.
     * 
     * @param string $message
     * @return \Zend\Stdlib\ResponseInterface
     */
    protected function handleError($message)
    {
        $this->logError($message);
        $this->logInfo('returnning error response...');
        $response = $this->getResponse();
        $response->setStatusCode(500);
        
        return $response;
    }
}