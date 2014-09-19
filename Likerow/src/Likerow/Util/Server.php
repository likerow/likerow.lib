<?php

namespace Likerow\Util;

class Server {

    const KEY = 'likerow_server';
    const SCRIPT_VERSION_DEFAULT = '1.0';

    static $_static;
    static $_content;
    static $_element;
    static $_scritVersion;
    static $_wsdl;

    public function __construct($config) {
        if (empty($config[self::KEY]['server'])) {
            throw new \Exception('No se encuentra la variable server');
        }
        if (empty($config[self::KEY]['server']['static'])) {
            throw new \Exception('No se encuentra la variable static del server');
        }
        self::$_static = $config[self::KEY]['server']['static'];
        self::$_element = $config[self::KEY]['server']['content'];
        self::$_content = $config[self::KEY]['server']['element'];
        self::$_scritVersion = self::SCRIPT_VERSION_DEFAULT;
        if (!empty($config[self::KEY]['server']['content'])) {
            self::$_content = $config[self::KEY]['server']['content'];
        }
        if (!empty($config[self::KEY]['server']['element'])) {
            self::$_element = $config[self::KEY]['server']['element'];
        }
        if (!empty($config[self::KEY]['script_verion'])) {
            self::$_scritVersion = $config[self::KEY]['script_verion'];
        }

        if (!empty($config[self::KEY]['wsdl'])) {
            self::$_wsdl = $config[self::KEY]['wsdl'];
        }
    }

    static function getStatic() {
        return self::$_static;
    }

    static function getContent() {
        return self::$_content;
    }

    static function getElement() {
        return self::$_element;
    }

    static function getScriptVersion() {
        return self::$_scritVersion;
    }

    /**
     * retorna el client de likerow1 api v4
     * @return \nusoap_client
     */
    static function getWsTransaction() {  
        return new \nusoap_client(self::$_wsdl['transaction'] . "services/v42?wsdl", 'wsdl');         
    }
    
    /**
     * retorna el client de likerowapi
     * @return \Zend\Soap\Client
     */
    static function getWsApiTransaction() {
        return new \Zend\Soap\Client(self::$_wsdl['api_transaction'] .'services/v1?wsdl' );         
    }

}
