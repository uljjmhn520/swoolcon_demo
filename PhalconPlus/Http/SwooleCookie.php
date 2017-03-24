<?php
/**
 * @brief
 * Created by PhpStorm.
 * User: zy&cs
 * Date: 16-11-15
 * Time: 下午5:50
 */
namespace PhalconPlus\Http;

use Phalcon\DiInterface;
use Phalcon\Http\Cookie;
use Phalcon\Session\AdapterInterface;

class SwooleCookie extends Cookie implements \Phalcon\Http\CookieInterface, \Phalcon\Di\InjectionAwareInterface
{


    /**
     * @var \swoole_http_response
     */
    private $_swooleResponse = null;


    /**
     * @var \swoole_http_request
     */
    private $_swooleRequest = null;


    /*public function __construct($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null) {
        parent::__construct($name, $value, $expire, $path, $secure, $domain, $httpOnly);
    }*/

    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;

        /*$this->_swooleRequest = $dependencyInjector->get('swooleRequest');
        $this->_swooleResponse = $dependencyInjector->get('swooleResponse');
        if (!$this->_swooleRequest) {
            throw new Cookie\Exception('swoole request is empty',2314131);
        }
        if (!$this->_swooleResponse) {
            throw new Cookie\Exception('swoole response is empty',2314131);
        }*/

    }

    private function getSwooleRequest()
    {
        if ($this->_swooleRequest) return $this->_swooleRequest;
        $this->_swooleRequest = $this->_dependencyInjector->get('swooleRequest');
        if (!$this->_swooleRequest) {
            throw new Cookie\Exception('swoole request is empty', 2314131);
        }
        return $this->_swooleRequest;
    }

    private function getSwooleResponse()
    {
        if ($this->_swooleResponse) return $this->_swooleResponse;
        $this->_swooleResponse = $this->_dependencyInjector->get('swooleResponse');
        if (!$this->_swooleResponse) {
            throw new Cookie\Exception('swoole response is empty', 2314131);
        }
        return $this->_swooleResponse;
    }


    public function getValue($filters = null, $defaultValue = null)
    {
        if ($this->_restored) {
            $this->restore();
        }

        if ($this->_readed !== false) {
            return $this->_value;
        }

        if (!isset($this->getSwooleRequest()->cookie[$this->_name]) || !($value = $this->getSwooleRequest()->cookie[$this->_name])) {
            return $defaultValue;
        }

        if ($this->_useEncryption) {
            $di = $this->_dependencyInjector;
            if (!is_object($di)) {
                throw new Cookie\Exception('A dependency injection object is required to access the \'filter\' service');
            }

            $crypt = $di->getShared('crypt');

            $decryptedValue = $crypt->decryptBase64($value);
        } else {
            $decryptedValue = $value;
        }
        $this->_value = $decryptedValue;

        if ($filters !== null) {
            $filter = $this->_filter;
            if (!is_object($filter)) {
                if (!isset($di)) {
                    $di = $this->_dependencyInjector;
                    if (!is_object($di)) {
                        throw new Cookie\Exception('A dependency injection object is required to access the \'filter\' service');
                    }
                }

                $filter        = $di->getShared('filter');
                $this->_filter = $filter;
            }

            return $filter->sanitize($decryptedValue, $filters);
        }

        return $decryptedValue;

    }

    public function send()
    {
        $name     = $this->_name;
        $value    = $this->_value;
        $expire   = $this->_expire;
        $domain   = $this->_domain;
        $path     = $this->_path;
        $secure   = $this->_secure;
        $httpOnly = $this->_httpOnly;

        /** @var DiInterface $di */
        $di = $this->_dependencyInjector;

        if (!is_object($di)) {
            throw new Cookie\Exception("A dependency injection object is required to access the 'session' service");
        }

        $definition = [];
        if ($expire != 0) {
            $definition['expire'] = $expire;
        }

        if (!empty($path)) {
            $definition['path'] = $path;
        }

        if (!empty($domain)) {
            $definition['domain'] = $domain;
        }

        if (!empty($secure)) {
            $definition['secure'] = $secure;
        }

        if (!empty($httpOnly)) {
            $definition['httpOnly'] = $httpOnly;
        }

        if (count($definition)) {
            $session = $di->getShared('session');
            if ($session->isStarted) {
                $session->set('_PHCOOKIE_' . $name, $definition);
            }
        }

        if ($this->_useEncryption) {
            if (!empty($value)) {
                if (!is_object($di)) {
                    throw new Cookie\Exception("A dependency injection object is required to access the 'filter' service");
                }

                $crypt = $di->getShared('crypt');

                $encryptValue = $crypt->encryptBase64((string)$value);
            } else {
                $encryptValue = $value;
            }
        } else {
            $encryptValue = $value;
        }

        $this->getSwooleResponse()->cookie($name, $encryptValue, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    public function delete()
    {
        $name     = $this->_name;
        $domain   = $this->_domain;
        $path     = $this->_path;
        $secure   = $this->_secure;
        $httpOnly = $this->_httpOnly;

        /** @var DiInterface $di */
        $di = $this->_dependencyInjector;
        if (is_object($di)) {
            /** @var AdapterInterface $session */
            $session = $di->getShared('session');

            if ($session->isStarted()) {
                $session->remove("_PHCOOKIE_" . $name);
            }
        }
        $this->_value = null;

        $this->getSwooleResponse()->cookie($name, null, time() - 691200, $path, $domain, $secure, $httpOnly);

    }


}