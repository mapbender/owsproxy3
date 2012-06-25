<?php

namespace OwsProxy3\CoreBundle\Event;

use OwsProxy3\CoreBundle\Component\Url;
use Symfony\Component\EventDispatcher\Event;

/**
 * Description of BeforeProxyEvent
 *
 * @author A.R.Pour
 */
class AfterProxyEvent extends Event {
//    protected $url;
//    protected $browserResponse;
//    
//    public function __construct(Url $url, $browserResponse) {
//        $this->url = $url;
//        $this->browserResponse = $browserResponse;
//    }
//    
//    public function getUrl() {
//        return $this->url;
//    }
//    
//    public function getBrowserResponse() {
//        return $this->browserResponse;
//    }
    protected $url;
    protected $browserResponse;
    protected $success = false;
    
    public function __construct(Url $url, $browserResponse) {
        $this->url = $url;
        $this->browserResponse = $browserResponse;
    }
    
    public function getUrl() {
        return $this->url;
    }
    
    public function getBrowserResponse() {
        return $this->browserResponse;
    }
    
    public function getSuccess(){
        return $this->success;
    }
    
    public function setSuccess($success){
        $this->success = $success;
    }
}
