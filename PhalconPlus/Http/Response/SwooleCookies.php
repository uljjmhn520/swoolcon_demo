<?php
/**
 * @brief
 * Created by PhpStorm.
 * User: zy&cs
 * Date: 16-11-16
 * Time: 上午10:21
 */
namespace PhalconPlus\Http\Response;
use Phalcon\DiInterface;
use Phalcon\Http\CookieInterface;
use Phalcon\Http\Response\Cookies;
use Phalcon\Http\Response\Exception;
use Phalcon\Http\Response\CookiesInterface;
use Phalcon\Di\InjectionAwareInterface;
use PhalconPlus\Http\SwooleCookie;

class SwooleCookies extends Cookies implements CookiesInterface, InjectionAwareInterface{

    /**
     * @var \swoole_http_response
     */
    private $_swooleResponse = null;

    /**
     * @var \swoole_http_request
     */
    private $_swooleRequest = null;


    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;

        $this->_swooleRequest = $dependencyInjector->get('swooleRequest');
        $this->_swooleResponse = $dependencyInjector->get('swooleResponse');
        if (!$this->_swooleRequest) {
            throw new Exception('swoole request is empty',2314131);
        }
        if (!$this->_swooleResponse) {
            throw new Exception('swoole response is empty',2314131);
        }
    }

    public function set($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null)
    {
        $encryption = $this->_useEncryption;

        /** @var CookieInterface $cookie */
        $cookie = isset($this->_cookies[$name]) ? $this->_cookies[$name] : null;
        if(!$cookie){
            /** @var SwooleCookie $cookie */
            $cookie = $this->_dependencyInjector->get('Phalcon\\Http\\Cookie', [$name, $value, $expire, $path, $secure, $domain, $httpOnly]);
            $cookie->setDi($this->_dependencyInjector);

            if($encryption){
                $cookie->useEncryption($encryption);
            }

            $this->_cookies[$name] = $cookie;
        }else{
            $cookie->setValue($value);
            $cookie->setExpiration($expire);
            $cookie->setPath($path);
            $cookie->setSecure($secure);
            $cookie->setDomain($domain);
            $cookie->setHttpOnly($httpOnly);
        }

        if ($this->_registered === false) {
            /** @var DiInterface $di */
            $di = $this->_dependencyInjector;

            if (!is_object($di)) {
                throw new Exception("A dependency injection object is required to access the 'response' service");
            }

            $response = $di->getShared('response');

            $response->setCookies($this);

            $this->_registered = true;
        }

        return $this;

    }


    public function get($name)
    {
        if(isset($this->_cookies[$name]) && $cookie = $this->_cookies[$name]){
            return $cookie;
        }

        /** @var DiInterface $di */
        $di = $this->_dependencyInjector;

        if (!is_object($di)) {
            throw new Exception("A dependency injection object is required to access the 'response' service");
        }

        /** @var SwooleCookie $cookie */
        $cookie = $di->get('Phalcon\\Http\\Cookie', [$name]);
        $cookie->setDi($di);

        $encryption = $this->_useEncryption;
        if($encryption){
            $cookie->useEncryption($encryption);
        }

        return $cookie;
    }

    public function has($name)
    {
        if (isset($this->_cookies[$name])) {
            return true;
        }

        if($this->_swooleRequest->cookie[$name]){
            return true;
        }

        return false;
    }

    public function send()
    {

        foreach($this->_cookies as $cookie) {
            $cookie->send();
        }

        return true;

    }

}