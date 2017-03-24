<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 3/24/17
 * Time: 10:11 PM
 */
namespace PhalconPlus\Mvc;

use Phalcon\Mvc\Router\Exception;
use PhalconPlus\Http\SwooleRequest;

class SwooleRouter extends \Phalcon\Mvc\Router
{
    public function getRewriteUri()
    {
        //var url, urlParts, realUri;

        /** @var SwooleRequest $request */
        $request = $this->_dependencyInjector->getShared('request');

        if (!$request || !($request instanceof SwooleRequest)) {
            throw new Exception('there is no swoole request');
        }
        /**
         * By default we use $_GET["url"] to obtain the rewrite information
         */
        if ($this->_uriSource) {

            if ($url = $request->getQuery('_url')) {
                return $url;
            }

        } else {
            /**
             * Otherwise use the standard $_SERVER["REQUEST_URI"]
             */

            if($url = $request->getServer('request_uri')){
                list($realUri) = explode('?', $url);
                if(!empty($realUri)){
                    return $realUri;
                }
            }
        }
        return "/";
    }
}
