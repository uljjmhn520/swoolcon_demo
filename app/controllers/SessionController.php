<?php

class SessionController extends ControllerBase
{

    public function indexAction()
    {

    }

    public function setAction()
    {

        $this->session->set('foo', 'bar' . time());
        var_dump('???');
    }

    public function getAction()
    {
        var_dump($this->session->get('foo'));
    }

    public function flashSetAction()
    {
        $this->flashSession->error('this is a error ; ' . time());

        var_dump('???');
        $this->response->redirect('/session/flashGet', true);
    }

    public function flashGetAction()
    {

        $this->flashSession->output();
    }


}

