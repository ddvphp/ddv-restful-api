<?php 
namespace DdvPhp\DdvRestfulApi\Util;
use DdvPhp\DdvRestfulApi\Util\RequestHeaders as RequestHeaders;
use DdvPhp\DdvRestfulApi\Exception\NotNewClassError as NotNewClassError;
/**
* 
*/
final class RequestParse
{
  public function __construct()
  {
    throw new NotNewClassError("This RequestParse class does not support instantiation");
  }
  //获取签名信息
  public static function requestParse(){
    return self::requestParseByHttp();
  }
  public static function requestParseByHttp(){
    $info = array();
    $info['type'] = 'http';
    //获取头
    $info['header'] = RequestHeaders::getHttpHeadersAsSysXAuth();
    $info['header'] = is_array($info['header'])?$info['header']:array();
    if(empty($info['header'])||empty($info['header']['sys'])||empty($info['header']['sys']['content-length'])||$info['header']['sys']['content-length']<1){
      return false;
    }
    $contentMd5 = $info['header']['sys']['content-length'];
    $params = array();
    //获取原始的类型
    $t = self::contentTypeParse($info['header']['sys']['content-type']);
    $dataType = self::dataTypeParse($t['contentType']);
    array_push($params, $t['boundary'], $t['encoding'], $dataType, $t['contentType'], $t['contentTypeOrigin']);
    $info['boundary'] = $t['boundary'];
    $info['encoding'] = $t['encoding'];
    $info['contentType'] = $t['contentType'];
    $info['contentTypeOrigin'] = $t['contentTypeOrigin'];
    $info['dataType'] = $dataType;

    unset($t);

    //强制 $_FILES 是文件数组
    $_FILES = is_array($_FILES)?$_FILES:array();
    //强制 $_POST 是文件请求体 的数组
    $_POST = is_array($_POST)?$_POST:array();

    switch ($dataType) {
      case 'multipart':
        $t = call_user_func_array('DdvPhp\DdvRestfulApi\Util\RequestParse::requestParseToolMultipart', $params);
        break;
      case 'urlencoded':
        $t = call_user_func_array('DdvPhp\DdvRestfulApi\Util\RequestParse::requestParseToolUrlencodedJsonXml', $params);
        break;
      case 'json':
        $t = call_user_func_array('DdvPhp\DdvRestfulApi\Util\RequestParse::requestParseToolUrlencodedJsonXml', $params);
        break;
      case 'xml':
        $t = call_user_func_array('DdvPhp\DdvRestfulApi\Util\RequestParse::requestParseToolUrlencodedJsonXml', $params);
        break;
      // case 'raw':
      default:
        $t = call_user_func_array('DdvPhp\DdvRestfulApi\Util\RequestParse::requestParseToolRaw', $params);
        break;
    }
    $info['dataMd5Raw'] = $t['dataMd5Raw'];
    $info['dataMd5Hex'] = $t['dataMd5Hex'];
    $info['dataMd5Base64'] = $t['dataMd5Base64'];
    $info['isContentMd5True'] = $contentMd5===$info['dataMd5Base64'] || $contentMd5===$info['dataMd5Hex'];

    return $info;
  }
  public static function requestParseToolRaw($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin){
    var_dump('$boundary, $encoding, $dataType, $contentType, $contentTypeOrigin',$boundary, $encoding, $dataType, $contentType, $contentTypeOrigin);
  }
  public static function requestParseToolMultipart($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin){
    var_dump('$boundary, $encoding, $dataType, $contentType, $contentTypeOrigin',$boundary, $encoding, $dataType, $contentType, $contentTypeOrigin);
  }
  public static function requestParseToolUrlencodedJsonXml($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin){
    $raw = '';
    //初始化增量Md5运算上下文
    $md5Ctx = hash_init('md5');
    if (!function_exists('file_get_contents')) {
        $fp = fopen('php://input', 'rb');
        if ($fp) {
            while (!feof($fp)){
              $rawt = fread($fp, 1024);
              //增量 哈希 运算
              hash_update($md5Ctx, $rawt);
              $raw .= $rawt;
            }
            fclose($fp);
        }
    } else {
        $raw = '' . file_get_contents('php://input');
        //增量 哈希 运算
        hash_update($md5Ctx, $raw);
    }
    //获取二进制的md5
    $dataMd5Raw = hash_final($md5Ctx,true);
    //base64编码二进制的md5生成标准的 content_md5
    $dataMd5Base64 = base64_encode($dataMd5Raw);
    //生成hex_md5
    $dataMd5Hex = bin2hex($dataMd5Raw);
    // 数组
    $data = array();
    /******结尾处理******/
    switch ($dataType) {
      //如果是 urlencoded 参数
      case 'urlencoded':
        parse_str($raw,$data);
        break;
      //如果是 json 参数
      case 'json':
        $data = json_decode($raw,true);
        break;
      //如果是 xml 参数
      case 'xml':
        @$data=simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
        if($data){
          $data = json_decode(json_encode($data),true);
        }
        break;
    }
    $data = is_array($data)?$data:array();
    unset($raw);
    // 压入请求体参数数组
    $_POST = array_merge($_POST,$data);
    // 压入请求参数数组
    $_REQUEST = array_merge($_REQUEST,$data);
    // 返回
    $r = array(
      'dataMd5Raw'=>$dataMd5Raw,
      'dataMd5Base64'=>$dataMd5Base64,
      'dataMd5Hex'=>$dataMd5Hex
    );
    unset($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin);
    unset($raw, $dataMd5Raw, $dataMd5Base64, $dataMd5Hex, $data);
    return $r;
  }
  public static function dataTypeParse($contentType){
    //类型
    $dataType = 'urlencoded';
    //
    switch( $contentType ){
      //判断数据类型 是否为 multipart/form-data
      case 'multipart/form-data':
        $dataType = 'multipart';
        break;
      //判断数据类型 是否为 application/x-www-form-urlencoded
      case 'application/x-www-form-urlencoded':
      case 'text/x-www-form-urlencoded':
        $dataType = 'urlencoded';
        break;
      //判断数据类型 是否为 application/json
      case 'application/json':
      case 'text/json':
        $dataType = 'json';
        break;
      //判断数据类型 是否为 application/xml
      case 'application/xml':
      case 'text/xml':
        $dataType = 'xml';
        break;
      default:
        if (strpos($contentType, 'x-www-form-urlencoded')!==false) {
          $dataType = 'urlencoded';
        }else{
          $dataType = 'raw';
        }
        break;
    }
    return $dataType;
  }
  public static function contentTypeParse($contentType){
    //获取原始的类型
    $contentTypeOrigin = $contentType;
    //拆分字符串为数组
    $contentTypeArray = explode( ';', $contentTypeOrigin );
    //函数删除数组中第一个元素，并返回被删除元素的值
    $contentType = strtolower(array_shift( $contentTypeArray ));
    //定义 分隔符的 变量
    $boundary = '';
    //获取编码,默认要留空，因为后期有值就会自动转换编码
    $encoding = '';
    //定义临时变量
    $t = '';
    if (is_array($contentTypeArray)) {
      //循环获取
      foreach( $contentTypeArray as $t ){
        //尝试获取分隔符
        if( strpos( $t, 'boundary' ) !== false ){
          //拆分字符串，指定拆分两个返回值
          $t = explode( '=', $t, 2 );
          //如果存在第1个，就尝试获取第一个参数
          if( isset( $t[ 1 ] ) )
            $boundary = '--' . $t[1];
        }
        //尝试编码
        else if( strpos( $t, 'charset' ) !== false ){
          //拆分字符串，指定拆分两个返回值
          $t = explode( '=', $t, 2 );
          //如果存在第1个，就尝试获取第一个参数
          if( isset( $t[ 1 ] ) )
            $encoding = $t[1];
        }
        //如果这两个值都是不为空，可以直接跳出循环，没必要继续浪费循环
        if( $boundary !== '' && $encoding !== '' )
          break;
      }
    }
    $r = array(
      "boundary" => $boundary,
      "encoding" => $encoding,
      "contentType" => $contentType,
      "contentTypeOrigin" => $contentTypeOrigin,
    );
    //卸载变量，节约内存
    unset($t, $boundary, $encoding, $contentType, $contentTypeOrigin, $contentTypeArray);
    return $r;
  }
}
?>