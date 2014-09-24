<?php

namespace Likerow\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Server extends AbstractHelper {

    public function __invoke() {
        return $this;
    }

    public function getStatic() {
        return \Likerow\Util\Server::getStatic();
    }

    public function getContent() {
        return \Likerow\Util\Server::getContent();
    }

    public function getElement() {
        return \Likerow\Util\Server::getElement();
    }

    public function getScriptVersion() {
        return \Likerow\Util\Server::getScriptVersion();
    }

}
