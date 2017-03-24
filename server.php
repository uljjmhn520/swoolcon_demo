#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 3/24/17
 * Time: 8:48 PM
 */

use Swoole\Http\Server as SwooleServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Phalcon\Di\FactoryDefault;
use PhalconPlus\Session\Adapter\Files;
use PhalconPlus\Http\SwooleRequest;
use PhalconPlus\Http\SwooleResponse;
use PhalconPlus\Http\SwooleCookie;
use PhalconPlus\Http\Response\SwooleCookies;
use Phalcon\Mvc\Application;

error_reporting(E_ALL);

defined('BASE_PATH') || define('BASE_PATH', __DIR__);
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');


//config

$config = require APP_PATH . '/config/config.php';

//loader
$loader = new \Phalcon\Loader();


/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs([
    $config->application->controllersDir,
    $config->application->modelsDir,
])->registerNamespaces([
    'PhalconPlus' => BASE_PATH . '/PhalconPlus',
])->register();


class Server
{

    public function start()
    {
        $host   = '127.0.0.1';
        $port   = '9999';
        $server = new SwooleServer($host, $port);
        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('Request', [$this, 'onRequest']);

        echo sprintf('server started on %s:%s%s', $host, $port, PHP_EOL);
        $server->start();
    }

    public function onWorkerStart(SwooleServer $server, $workerId)
    {

    }

    public function onRequest(Request $request, Response $response)
    {

        $uri = $request->get['_url'] = $request->server['request_uri'];

        //static file
        $fileName = BASE_PATH . '/public'.$request->get['_url'];
        if (file_exists($fileName)
            && preg_match('#(.css|.js|.gif|.png|.jpg|.jpeg|.ttf|.woff|.ico)$#', $uri) === 1
        ) {
            $response->end(file_get_contents($fileName));
            return false;
        }

        //copy from public/index.php
        try {

            /**
             * The FactoryDefault Dependency Injector automatically registers
             * the services that provide a full stack framework.
             */
            $di = new FactoryDefault();


            /**
             * Read services
             */
            include APP_PATH . '/config/services.php';

            /**
             * 注册 swoole 的服务
             */
            $this->registerSwooleService($di,$request,$response);

            /**
             * Handle the request
             */
            $application = new Application($di);

            $content = $application->handle()->getContent();


        } catch (\Exception $e) {
            $content = $e->getMessage() . '<br>';
            $content .= '<pre>' . $e->getTraceAsString() . '</pre>';

        }


        $response->end($content);

    }

    private function registerSwooleService(\Phalcon\DiInterface $di,$request,$response)
    {

        $di->setShared('swooleRequest',$request);

        $di->setShared('swooleResponse',$response);

        //
        $di->setShared('request', function () use ($di) {
            $request = new SwooleRequest();
            $request->setDi($di);
            return $request;
        });

        //
        $di->setShared('response', function () use ($di) {
            $response = new SwooleResponse();
            $response->setDi($di);
            return $response;
        });

        //
        $di->setShared('cookies', function () use ($di) {
            $cookies = new SwooleCookies();
            $cookies->useEncryption(false);
            $cookies->setDI($di);
            return $cookies;
        });


        //cookie
        $di->set('Phalcon\\Http\\Cookie', function ($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null) {
            $cookie = new SwooleCookie($name, $value, $expire, $path, $secure, $domain, $httpOnly);
            return $cookie;
        });

        //session , 用cache 改的
        $di->setShared('session', function () use($di){

            $session = new Files(['uniqueId' => 'sessId']);
            $session->setDI($di);
            $session->start();
            return $session;

        });

        $di->setShared('router',function() use($di){
            $router = new \PhalconPlus\Mvc\SwooleRouter();
            $router->setDI($di);
            return $router;
        });
    }
}


(new Server())->start();