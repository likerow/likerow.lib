<?php

namespace Likerow\Util;

use Zend\Log\Logger;

class ErrorHandler {

    /**
     * @var Logger
     */
    protected $logger = null;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function logException(\Exception $e, $email) {
        $asunto = 'ERROR :' . $_SERVER['HTTP_HOST'] . ' : ' . $e->getMessage();
        $mensaje = $this->_prepararMensajedeError($e);
        //if (APPLICATION_ENV == 'production') {
        //$email->notificarError($asunto, $mensaje);
        //}
        $this->logError($mensaje);
    }

    public function logError($message) {
        $this->logger->err($message);
        error_log($message);
    }

    private function _prepararMensajedeError($errors) {
        $forwarded = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? " - $_SERVER[HTTP_X_FORWARDED_FOR];" : ";";
        $mensaje = "[[ORIGEN]]: \r\n\r\n $_SERVER[REMOTE_ADDR]$forwarded "
                . @strftime("%A, %d de %B del %Y %T  %z") . "\r\n\r\n";
        $mensaje .= "[[URL]]: \r\n\r\n" . $_SERVER['REQUEST_URI'] . "\r\n\r\n";
        $mensaje .= "[[MENSAJE DE ERROR]]: \r\n\r\n" . $errors->getMessage() . "\r\n\r\n";

        $traza = $errors->getTraceAsString();

        $mensaje .= "[[TRAZA]]: \r\n\r\n" . $traza . "\r\n\r\n";


        return $mensaje;
    }

}
