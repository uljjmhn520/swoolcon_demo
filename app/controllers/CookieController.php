<?php

class CookieController extends ControllerBase
{

    public function indexAction()
    {

    }

    public function setAction(){

        ($this->cookies->set('foo','bar'));
        var_dump('???');
    }

    public function getAction(){
        var_dump($this->cookies->get('foo')->getValue('string'));
    }



}

