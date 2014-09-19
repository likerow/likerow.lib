<?php

namespace Likerow\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Server extends AbstractHelper {

    public function __invoke() {
        return $this;
    }

    public function getStatic() {
        return \Bongo\Util\Server::getStatic();
    }

    public function getContent() {
        return \Bongo\Util\Server::getContent();
    }

    public function getElement() {
        return \Bongo\Util\Server::getElement();
    }

    public function getScriptVersion() {
        return \Bongo\Util\Server::getScriptVersion();
    }

}
