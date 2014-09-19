<?php

namespace Likerow\Util;

use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Part;
use Zend\Mime\Mime;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;

class Mail {

    private $serviceManager = null;
    private $useSMTP = false;
    private $smtpName = '';
    private $smtpHost = '';
    private $smtpPort = '';
    private $smtpConnectionClass = '';
    private $smtpUsername = '';
    private $smtpPassword = '';
    private $smtpSsl = '';
    private $mailBody = '';
    private $mailFrom = '';
    private $mailSubject = '';
    private $mailFromNickName = '';
    private $mailTo = '';
    private $mailSenderType = '';
    private $mailCc = array();
    private $mailBcc = array();
    private $fileNames = array();
    private $filePaths = array();
    private $params = array();

    public function __construct($serviceManager) {
        $this->serviceManager = $serviceManager;
        $emailConfig = $this->_getConfig("email");

        $this->useSMTP = $emailConfig['use_smtp'];
        $this->smtpSsl = $emailConfig['smtp_ssl'];
        $this->smtpName = $emailConfig['smtp_name'];
        $this->smtpHost = $emailConfig['smtp_host'];
        $this->smtpPort = $emailConfig['smtp_port'];
        $this->smtpUsername = $emailConfig['smtp_username'];
        $this->smtpPassword = $emailConfig['smtp_password'];
        $this->smtpConnectionClass = $emailConfig['smtp_connection_class'];

        $this->mailBody = $emailConfig['body'];
        $this->mailFrom = $emailConfig['from'];
        $this->mailSubject = $emailConfig['subject'];
        $this->mailFromNickName = $emailConfig['from_nick_name'];
        $this->mailTo = $emailConfig['to'];
        $this->mailSenderType = $emailConfig['smtp_sender_type'];
    }

    public function notificarError($asunto, $mensaje, $email = null, $enviarEmail = false) {
        $this->serviceManager->get('Likerow\Logger')->err($mensaje);
        if ($enviarEmail) {
            $config = $this->_getConfig("bongo_server");
            if (!empty($config['email_development'])) {
                $emailDeveloper = $config['email_development'][0];
                unset($config['email_development'][0]);
                $emails = array();
                foreach ($config['email_development'] as $value) {
                    $emails[] = $value;
                }
                if (!empty($email)) {
                    $this->sendMail(array(
                        'mailTo' => $email,
                        'mailFrom' => 'contactos@bongous.com',
                        'mailSubject' => $asunto,
                        'mailBody' => $mensaje,
                        'params' => array('name' => 'BongoSupport')));
                } else {

                    $this->sendMail(array(
                        'mailTo' => $emailDeveloper,
                        'mailCc' => $emails,
                        'mailFrom' => 'contactos@bongous.com',
                        'mailSubject' => $asunto,
                        'mailBody' => $mensaje,
                        'params' => array('name' => 'BongoSupport')));
                }
            }
        }
    }

    /**
     * 
     */
    public function notificarException($asunto, \Exception $e, $email = null, $enviarEmail = false) {
        $mensaje = $this->_prepararMensajedeError($e);
        $this->serviceManager->get('Bongo\Logger')->err($mensaje);
        if ($enviarEmail) {
            $this->notificarError($asunto, $mensaje, $email, $enviarEmail);
        }
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

    public function sendMail($mailOptions = array()) {
        $this->_setMailOptions($mailOptions);

        $text = new Part($this->mailBody);
        $text->type = Mime::TYPE_HTML;
        $mailBodyParts = new MimeMessage();
        $mailBodyParts->addPart($text);
        $options = array();

        if ($this->useSMTP === false) {
            $options = new SmtpOptions(array(
                "name" => $this->smtpName,
                "host" => $this->smtpHost,
                "port" => $this->smtpPort
            ));
        } else {
            $options = new SmtpOptions(array(
                'name' => $this->smtpName,
                'host' => $this->smtpHost,
                'port' => $this->smtpPort,
                'connection_class' => $this->smtpConnectionClass,
                'connection_config' => array(
                    'ssl' => $this->smtpSsl,
                    'username' => $this->smtpUsername,
                    'password' => $this->smtpPassword
                )
            ));
        }

        $mail = new Message();
        $mail->setBody($mailBodyParts);
        $mail->setFrom($this->mailFrom, $this->mailFromNickName);
        $mail->addTo($this->mailTo);
        if (!empty($this->mailCc)) {
            $mail->addCc($this->mailCc);
        }
        if (!empty($this->mailBcc)) {
            $mail->addBcc($this->mailBcc);
        }
        $mail->setSubject($this->mailSubject);
        $transport = new SmtpTransport();
        $transport->setOptions($options);
        $emailLogInfo = array(
            'email_to' => $this->mailTo,
            'email_from' => $this->mailFrom,
            'email_body' => $this->mailBody,
            'email_subject' => $this->mailSubject,
            'sender_type' => $this->mailSenderType
        );

        $emailSend = 0;
        try {
            $transport->send($mail);
            $emailSend = 1;
        } catch (\Exception $e) {
            $emailLogInfo['email_error'] = $e->getMessage();
            throw $e;
        }
        return $emailSend;
    }

    private function _setMailOptions($mailOptions) {
        if (array_key_exists('useSMTP', $mailOptions)) {
            $this->useSMTP = $mailOptions['useSMTP'];
        }
        if (array_key_exists('mailTo', $mailOptions)) {
            $this->mailTo = $mailOptions['mailTo'];
        }
        if (array_key_exists('mailCc', $mailOptions)) {
            $this->mailCc = $mailOptions['mailCc'];
        }
        if (array_key_exists('mailBcc', $mailOptions)) {
            $this->mailBcc = $mailOptions['mailBcc'];
        }
        if (array_key_exists('mailFrom', $mailOptions)) {
            $this->mailFrom = $mailOptions['mailFrom'];
        }
        if (array_key_exists('mailFromNickName', $mailOptions)) {
            $this->mailFromNickName = $mailOptions['mailFromNickName'];
        }
        if (array_key_exists('mailSubject', $mailOptions)) {
            $this->mailSubject = $mailOptions['mailSubject'];
        }
        if (array_key_exists('mailBody', $mailOptions)) {
            $this->mailBody = $mailOptions['mailBody'];
        }
        if (array_key_exists('sender_type', $mailOptions)) {
            $this->mailSenderType = $mailOptions['sender_type'];
        }
        if (array_key_exists('fileNames', $mailOptions)) {
            $this->fileNames = $mailOptions['fileNames'];
        }
        if (array_key_exists('filePaths', $mailOptions)) {
            $this->filePaths = $mailOptions['filePaths'];
        }
        if (array_key_exists('params', $mailOptions)) {
            $this->params = $mailOptions['params'];
        }
    }

    private function _getConfig($key) {
        $config = $this->serviceManager->get('config');

        if (!empty($key)) {
            return $config[$key];
        }
        return $config;
    }

}
