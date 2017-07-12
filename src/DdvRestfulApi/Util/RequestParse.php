<?php 
namespace DdvPhp\DdvRestfulApi\Util;
use \DdvPhp\DdvRestfulApi\Util\RequestHeaders as RequestHeaders;
use \DdvPhp\DdvRestfulApi\Exception\RequestParseError as RequestParseError;
/**
* 
*/
final class RequestParse
{
  public function __construct()
  {
    throw new \DdvPhp\DdvException\NotNewClassError("This RequestParse class does not support instantiation");
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
    $contentMd5 = '';
    $contentLength = 0;
    if(empty($info['header'])||empty($info['header']['sys'])||empty($info['header']['sys']['content-length'])||$info['header']['sys']['content-length']<1){
      $info['isContentMd5True'] = true;
      return $info;
    }else{
      $contentMd5 = $info['header']['sys']['content-md5'];
      $contentLength = $info['header']['sys']['content-length'];
    }
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
        $t = self::requestParseToolMultipart($params);
        break;
      case 'urlencoded':
        $t = self::requestParseToolUrlencodedJsonXml($params);
        break;
      case 'json':
        $t = self::requestParseToolUrlencodedJsonXml($params);
        break;
      case 'xml':
        $t = self::requestParseToolUrlencodedJsonXml($params);
        break;
      // case 'raw':
      default:
        $t = self::requestParseToolRaw($params);
        break;
    }
    $info['dataMd5Raw'] = $t['dataMd5Raw'];
    $info['dataMd5Hex'] = $t['dataMd5Hex'];
    $info['dataMd5Base64'] = $t['dataMd5Base64'];
    $info['isContentMd5True'] = $contentMd5===$info['dataMd5Base64'] || $contentMd5===$info['dataMd5Hex'];
    $info['isContentLengthTrue'] = true;

    return $info;
  }
  public static function requestParseToolRaw($params){
    list($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin) = $params;
    var_dump('$boundary, $encoding, $dataType, $contentType, $contentTypeOrigin',$boundary, $encoding, $dataType, $contentType, $contentTypeOrigin);
  }
  public static function requestParseToolMultipart($params){
    list($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin) = $params;
    //默认没有找到分隔符
    $delimiter = false;
    //获取内容的分界
    $input = fopen('php://input', 'rb');
    //初始化增量Md5运算上下文
    $md5Ctx = hash_init('md5');

    //如果分隔符为空，兼容旧版发送
    if (empty($boundary)) {
      //获取分隔符
      $boundary = rtrim(self::multipartFgets( $input, null , $md5Ctx )) ;
    }else{
      //如果读取到的第一行内容不是分隔符
      if(rtrim(self::multipartFgets( $input, (strlen( $boundary ) + 5) , $md5Ctx )) !== $boundary){
        //请求定义为未知请求
        throw new RequestParseError('boundary error');
      }
    }

    //二进制头信息
    $rawHeaders = '';
    //每块读取长度
    $chunkLength = 8096;

    /******结尾处理==开始循环读流******/
    while( ( $chunk = self::multipartFgets( $input, null , $md5Ctx ) ) !== false ){
      //如果是分隔符跳过循环
      if( $chunk === $boundary )
        //跳过循环
        continue;

      //空行意味着我们拥有所有的头和将要阅读的内容
      //也就是读头信息结束
      if( rtrim( $chunk ) == '' ){
        //拆分二进制头信息为数组
        $rawHeaders = explode( "\r\n", $rawHeaders );
        //定义新头数组为空数组
        $headers = array();
        //定义正则结果
        $matches = array();

        //遍历拿到每一个头
        foreach( $rawHeaders as $header ){
          //查找是否存在:这个分隔符
          if( strpos( $header, ':' ) === false )
            //没有找到就跳过循环
            continue;
          //定义$name 和 $value
          list( $name, $value ) = explode( ':', $header, 2 );
          //赋值到数组中
          $headers[ trim( strtolower( $name ) ) ] = ltrim( $value, ' ' );
        }

        //清空二进制流为空，为下次做准备
        $rawHeaders = '';

        //如果没有设置content-disposition就跳出循环
        if( !isset( $headers[ 'content-disposition' ] ) )
          continue;

        //默认文件名为空
        $filename = NULL;
        //正则提取文件名
        preg_match(
          '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
          $headers['content-disposition'],
          $matches
        );
        if ((!is_array($matches))||count($matches)<3) 
          continue;
        //拿到类型和文件名
        list( , $type, $name ) = $matches;

        /****处理数据开始****/
        //如果存在第5个参数就是 拉文件
        if( isset( $matches[ 4 ] ) ){
          //错误状态默认为OK
          $error = UPLOAD_ERR_OK;

          //提取文件名
          $filename = $matches[ 4 ];
          //解析文件信息
          $filenameParts = pathinfo( $filename );
          //默认未知
          $fileContentType = 'unknown';

          //尝试解析
          if( isset( $headers[ 'content-type' ] ) ){
            $tmp = explode( ';', $headers[ 'content-type' ] );
            $fileContentType = $tmp[0];
            unset($tmp);
          }

          //建立一个具有唯一文件名的文件
          $tmpnam = tempnam( ini_get( 'upload_tmp_dir' ), 'php' );
          //写流打开文件
          $fileHandle = fopen( $tmpnam, 'wb' );

          //打开失败
          if( $fileHandle === false ){
            $error = UPLOAD_ERR_CANT_WRITE;
          }else{
             $lastLine = NULL;
            while( ( $chunk = self::multipartFgets( $input, $chunkLength , $md5Ctx ) ) !== false && strpos( $chunk, $boundary ) !== 0 ) {
              if( $lastLine !== NULL ){
                if( fwrite( $fileHandle, $lastLine ) === false ){
                  $error = UPLOAD_ERR_CANT_WRITE;
                  break;
                }
              }
              $lastLine = $chunk;
            }

            if( $lastLine !== NULL && $error !== UPLOAD_ERR_CANT_WRITE ){
              if( fwrite( $fileHandle, rtrim( $lastLine, "\r\n" ) ) === false )
                  $error = UPLOAD_ERR_CANT_WRITE;
            }
          }
          fclose($fileHandle);

          $items = array(
            'name' => $filename,
            'type' => $fileContentType,
            'tmp_name' => $tmpnam,
            'error' => $error,
            'size' => filesize( $tmpnam )
          );

          $currLev = $name . '=p';
          $tmp = array();
          parse_str( $currLev, $tmp );
          $isRun = true ;
          $_this = &$tmp;
          while ($isRun) {
            $isRun = false ;
            if (is_array($_this)) {
              foreach ($_this as $key => $value) {
                $_this2 = &$_this[$key];
                unset($_this);
                $_this = &$_this2;
                unset($_this2);
                $isRun = true ;
              }
            }
          }
          $_this = $items;
          unset($_this,$isRun,$currLev,$items);

          $_FILES = self::recursiveSetter( $name, $_FILES, $tmp );


          continue;
        //其他类型为拉变量
        }else{
          $fullValue = '';
          $lastLine = NULL;
          while( ( $chunk = self::multipartFgets( $input , null , $md5Ctx ) ) !== false && strpos( $chunk, $boundary ) !== 0 ){
            if( $lastLine !== NULL )
              $fullValue .= $lastLine;

            $lastLine = $chunk;
          }

          if( $lastLine !== NULL )
            $fullValue .= rtrim( $lastLine, "\r\n" );

          if( isset( $headers[ 'content-type' ] ) ){
            $tmp = explode( ';', $headers[ 'content-type' ] );
            $encoding = '';

            foreach( $tmp as $t ){
              if( strpos( $t, 'charset' ) !== false ){
                $t = explode( $t, '=', 2 );
                if( isset( $t[ 1 ] ) )
                  $encoding = $t[1];
                break;
              }
            }

            if( $encoding !== '' && strtoupper( $encoding ) !== 'UTF-8' && strtoupper( $encoding ) !== 'UTF8' ){
                $tmp = mb_convert_encoding( $fullValue, 'UTF-8', $encoding );
                if( $tmp !== false )
                  $fullValue = $tmp;
            }

          }

          $fullValue = $name . '=' . $fullValue;
          $origName = $name;
          $tmp = array();
          parse_str( $fullValue, $tmp );
          $_POST = self::recursiveSetter( $origName, $_POST, $tmp );
        }
        /****处理数据结束****/
        continue;
      }

      //拼接头信息
      $rawHeaders .= $chunk;
    }
    /******结尾处理==结束循环读流******/
    //关闭输入流
    fclose( $input );
    //获取二进制的md5
    $dataMd5Raw = hash_final($md5Ctx,true);
    //base64编码二进制的md5生成标准的 content_md5
    $dataMd5Base64 = base64_encode($dataMd5Raw);
    //生成hex_md5
    $dataMd5Hex = bin2hex($dataMd5Raw);
    // 返回
    $r = array(
      'dataMd5Raw'=>$dataMd5Raw,
      'dataMd5Base64'=>$dataMd5Base64,
      'dataMd5Hex'=>$dataMd5Hex
    );
    unset($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin);
    unset($delimiter, $rawHeaders, $chunkLength, $dataMd5Raw, $dataMd5Base64, $dataMd5Hex, $input);
    return $r;
  }
  //restful数据接收初始化
  private static function multipartFgets ( $handle , $length =null , $ctx = null ){
    $r = '';
    if (is_null($length)) {
      $r = fgets($handle);
    }else{
      $r = fgets($handle,$length);
    }
    //增量 哈希 运算
    if (!is_null($ctx)) {
      hash_update($ctx, $r);
    }
    //返回
    return $r;
  }
  public static function recursiveSetter( $spec, $array, $array2, $is_quote = false){
    if( !is_array( $spec ) )
      $spec = explode( '[', (string)$spec );
    $currLev = array_shift( $spec );
    $currLev = rtrim( $currLev, ']' );
    if( $currLev !== '' ){
      $currLev = $currLev . '=p';
      $tmp = array();
      parse_str( $currLev, $tmp );
      $tmp = array_keys( $tmp );
      $currLev = reset( $tmp );
    }

    if( !is_array( $array ) ){
      $array = $array2;
    }else if( $currLev === '' ){
      $array[] = reset( $array2 );
    }else if( isset( $array[ $currLev ] ) && isset( $array2[ $currLev ] ) ){
      $array[ $currLev ] = self::recursiveSetter( $spec, $array[ $currLev ], $array2[ $currLev ] );
    }else if( isset( $array2[ $currLev ] ) ){
      $array[ $currLev ] = $array2[ $currLev ];
      //var_dump('xx*x/x*x*x/xx//==',$array2[ $currLev ]);
    }
    return $array;
  }
  public static function requestParseToolUrlencodedJsonXml($params){
    list($boundary, $encoding, $dataType, $contentType, $contentTypeOrigin) = $params;
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
