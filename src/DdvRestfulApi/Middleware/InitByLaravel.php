<?php

namespace DdvPhp\DdvRestfulApi\Middleware;

use const null;
use Closure;
use Mockery\CountValidator\Exception;
use PhpParser\Error;

class InitByLaravel
{
  protected $isDdvRestfulApiInit = false;
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @param  string|null  $guard
   * @return mixed
   */
  public function handle($request, Closure $next, $guard = null)
  {
    if(!$this->isDdvRestfulApiInit){
      $this->isDdvRestfulApiInit = true;
      $restfulApi = \DdvPhp\DdvRestfulApi::getInstance();
      // 配置 ddvRestfulApi 参数
      $restfulApi->config((config('ddvRestfulApi')));
      // 使用 ddvRestfulApi 解析请求数据
      $restfulApi->requestParse();
      // 使用 ddvRestfulApi 跨越
      $restfulApi->initCors();
    }
    $r = &$restfulApi->responseData;
    $response = $next($request);
    if(class_exists('DdvPhp\DdvPage')){
      // 分页
      if(class_exists('Illuminate\Pagination\LengthAwarePaginator') && $response->original instanceof \Illuminate\Pagination\LengthAwarePaginator){
        $response->original = new \DdvPhp\DdvPage($response->original);
      }
      // 如果是\DdvPhp\DdvPage
      if($response->original instanceof \DdvPhp\DdvPage){
        $response->original = $response->original->toArray();
      }
    }

    $content = $response->getContent();
    if(is_array($response->original)){
      $r = array_merge($r , $response->original);
    }else if(is_string($response->original)){
      try{
        $r = array_merge($r , json_decode(json_encode($response->original),true));
      }catch(\Exception $e){
        $r['body'] = $content;
      }
    }else{
      return $response;
    }

    if (empty($r['statusCode'])) {
      if (method_exists($response, 'getStatusCode')) {
        $r['statusCode'] = $response->getStatusCode();
      }
    }
    if (empty($r['errorId'])) {
      $r['errorId'] = empty($response->statusTexts[$r['statusCode']])? $r['message'] : $response->statusTexts[$r['statusCode']];
    }
    $r['data'] = empty($r['data']) ? (object)array() : $r['data'];
    $r['page'] = empty($r['page']) ? (object)array() : $r['page'];

    $response->original = $r ;
    $response->setStatusCode($r['statusCode'], $r['message']);
    $response->setContent($r);
    return $response;
  }
}