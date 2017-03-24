<?php
/**
 * @brief
 * Created by PhpStorm.
 * User: zy&cs
 * Date: 16-11-16
 * Time: 下午2:22
 */
namespace PhalconPlus\Http\Response;
use Phalcon\Http\Response\Exception;
use Phalcon\Http\Response\Headers;

class SwooleHeader extends Headers implements \Phalcon\Http\Response\HeadersInterface,\Phalcon\Di\InjectionAwareInterface{

    protected $_dependencyInjector;
    /**
     * @var \swoole_http_response
     */
    private $_swooleResponse = null;


    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    public function setDI(\Phalcon\DiInterface $dependencyInjector) {
        $this->_dependencyInjector = $dependencyInjector;


        $this->_swooleResponse = $dependencyInjector->get('swooleResponse');
        if (!$this->_swooleResponse) {
            throw new Exception('swoole response is empty',2314131);
        }

    }

    public function send()
    {
        //swoole 里面不晓得有没有用,貌似没用。。。
        //暂时不用
        /*if(headers_sent()){
            return false;
        }*/

        $response = $this->_swooleResponse;
        foreach($this->_headers as $header=>$value){
            if ($value !== null) {
                $response->header($header . ': ' . $value, true);
            }else{
                if (strpos($header, ':') || substr($header, 0, 5) == 'HTTP/') {
                    $response->header($header, true);
                }else{
                    $response->header($header . ': ', true);
                }
            }
        }
        return true;

    }


}