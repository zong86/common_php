<?php
/*
 * 通用代码库，可在任意位置调用
 */

/**************************     网络       *********************/

/**
 * 获取完整请求信息
 *
 * @param - 无
 * @return array  一个数组，数组内包含完整请求信息
 *
 */
function getRequest() {
    return [
        'URL'=>$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'],//请求地址（带端口完整地址）
        'REQUEST_METHOD'=>$_SERVER['REQUEST_METHOD'],//请求方法
        'HEADERS'=>getallheaders(),//请求头（内含请求体长度、编码、Cookie等）
        'GET'=>$_GET,//GET参数
        'POST'=>$_POST,//POST参数
        'FILES'=>$_FILES,//文件参数
        'REQUEST'=>$_REQUEST,//REQUEST参数
        'INPUT'=>file_get_contents('php://input'),//原始输入流参数
    ];
}

/**
 * Get all HTTP header key/values as an associative array for the current request.
 * Original from https://github.com/ralouphie/getallheaders/blob/develop/src/getallheaders.php
 *
 * @param - 无
 * @return array The HTTP header key/value pairs.
 */
function getallheaders(){
    $headers = [];
    $copy_server = [
        'CONTENT_TYPE'   => 'Content-Type',
        'CONTENT_LENGTH' => 'Content-Length',
        'CONTENT_MD5'    => 'Content-Md5',
    ];
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === 'HTTP_') {
            $key = substr($key, 5);
            if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$key] = $value;
            }
        } elseif (isset($copy_server[$key])) {
            $headers[$copy_server[$key]] = $value;
        }
    }
    if (!isset($headers['Authorization'])) {
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
            $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
            $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
        } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
        }
    }
    return $headers;
}

/**
    请求网络
    author github@zong86
    time v1 2023-07-12 16:24:03

    parame 参数：
        @param $url 请求地址
        @param $method 请求方法，选填，默认GET 不区分大小写  可选：GET（获取）、POST（更新）、PUT（新增）、DELETE（删除）、HEAD（头）五大方法
        @param $parame 请求参数  一个数组，选填，默认空字符
                请求编码 $requestHeader['Content-type'] 为：multipart/form-data【不填默认】本编码方式可以发送文件
                        //发送键值
                            $parame['name'] => '张三'
                            $parame['nickname'] => '呵呵哒'
                        //发送文件，格式规则：数组下标0为文件地址，需要绝对地址，数组下标1为指定文件的imei，可不填，不填则统一为application/octet-stream
                            $parame['image_1'] => [/www/tmp/123.jpg']
                            $parame['image_2'] => [/www/tmp/456.jpg,'image/png']
                            $parame['video'] => [/www/tmp/789.mp4,'video/mp4']
                请求编码 $requestHeader['Content-type'] 为：application/x-www-form-urlencoded 本编码方式无法发送文件，仅可发键值对参数，如：
                            $parame['name'] => '张三'
                            $parame['nickname'] => '呵呵哒'
                请求编码 $requestHeader['Content-type'] 为：text/plain、application/json、application/octet-stream  本编码方式无法发送文件，仅可发文本参数，如：
                            $parame = '6666666';
            requestHeader 请求头，选填，一个数组，可自定义头信息
                            默认项：Content-type 请求体编码 默认值：multipart/form-data，Content-type取值见下方说明【请注意Content-type的键名写法，不可填成Content-Type或content-type，区分大小写，需一字不差填写】，参数可选项：
                                        multipart/form-data【不填默认】
                                            这是1995年ietf组织为了解决HTTP的POST（也就是application/x-www-form-urlencoded编码）不支持文件上传问题出台的rfc1867规范的编码。
                                            他最大的特点就是支持文本和文件混合传送，完整的样子 Content-Type:multipart/form-data; boundary=ZnGpDtePMx0KrHh_G0X99Yef9r8JZsRJSXC
                                            boundary是rfc1867规定的分割字段，ZnGpDtePMx0KrHh_G0X99Yef9r8JZsRJSXC就是分割符，必填，可自由定义。
                                            他的请求体样子：
                                            --ZnGpDtePMx0KrHh_G0X99Yef9r8JZsRJSXC
                                            Content-Disposition: form-data; name="name"

                                            张三
                                            --ZnGpDtePMx0KrHh_G0X99Yef9r8JZsRJSXC
                                            Content-Disposition: form-data;name="age"
                                            Content-Type: text/plain; charset=UTF-8

                                            20
                                            --ZnGpDtePMx0KrHh_G0X99Yef9r8JZsRJSXC
                                            Content-Disposition: form-data;name="pic"; filename="photo.jpg"
                                            Content-Type: application/octet-stream

                                            ... 101010101010100101010101010101010101010 ...
                                            --ZnGpDtePMx0KrHh_G0X99Yef9r8JZsRJSXC--
                                            可以看到他的开头、结束、各个字段均被 --ZnGpDtePMx0KrHh_G0X99Yef9r8JZsRJSXC 分割，而且每一个字段都可以独立定义名字、编码、类型。
                                            填写此类型只需要  $requestHeader['Content-type'] = 'multipart/form-data'; 后面的boundary=.....在函数内已处理，无需在此处填写，此编码可传输文本和文件
                                        application/x-www-form-urlencoded
                                            这是http默认的编码，当使用POST请求时数据会被以 x-www-urlencoded 方式编码到请求体中传递，使用GET请求时则是附在url链接后面传递
                                            参数是键值对，一个键对应一个值，键必填，值可以为空字符。请求体只能是字符串（只能传递文本），此编码无法传输文件，PHP里使用$_POST或$_GET方式接收
                                        text/plain
                                            这是raw格式的原始请求编码，请求体只能是字符串（只能传输文本），在php里使用file_get_contents('php://input')接收
                                        application/json
                                            编码和取值与text/plain相同，虽然这里写着json，但这只是一个类型约束，只是告诉接收方请求体是json格式，此处只是标识作用，完全可以挂羊头卖狗肉，传任何格式的字符给接收方都是可以接收到的。
                                            实际用什么格式还是需要跟接收方协商，这里的规定并不起决定作用，PHP里使用$_POST或$_GET方式接收
                                        application/octet-stream
                                            二进制流编码，这是把文件或字符打包成二进制码传递，在php里使用file_get_contents('php://input')方式接收，	这里file_get_contents()得到的
                                            结果是一个文件流，文件流只能通过文件函数读取和保存如fopen 、fwrite、file_put_contents等，所以如果你想文本和文件混合传输的话，接收方需要
                                            把二进制先转为文本再提取分离文本和文件，需要和接收方协商文本和文件的分界问题，如果是纯文件或文本则没有这个问题
                                        ****** / *******
                                        自定义类型编码，编码和取值与application/octet-stream相同。这里的类型可以是w3c组织规定的类型（如image/png、video/mp4）也可是自己创造，但建议使用w3c组织规定的类型。
                            特殊项：__CURL_SETOPT_EXTEND__    CURL_SETOPT选项的专用键，本键名是一个特殊键，填数组，用作设置curl的扩展值使用。有些特殊需求：代理、下载文件、认证访问、https验证等的设置时，本函数默认情况下无法处理，可填写此数组来处理
                                                             数组的键为curl类的常量，详询：https://www.php.net/manual/en/curl.constants.php，也可直接填常量表示的数字
                                                             数组的值为自定义内容，若填写本键值，将全部合并到curl_setopt_array()里，$curlSetoptArr里有一些默认值，也可以用本参数覆盖
            timeout 超时时间 单位秒 默认25秒 区间：1-360 （1-10分钟）
            getAllData 是否获取完整信息 默认 false 不获取 true 获取  返回详细数组

        $return 返回值，一个索引数组[code（通过下标0获得）,msg（通过下标1获得）,data（通过下标2获得）]
            code（通过下标0获得）  联网结果  1成功（此处仅代表网络连接成功，并不是业务层面成功，具体得获取到返回值在上层自行判断）  0失败
            msg（通过下标0获得）   联网失败时的原因，成功时统一返回ok
            data（通过下标0获得）  返回值内容
                                    若联网成功，受$getAllData参数影响，为false时为返回值字符串，为true时为数组
                                    若联网失败，统一为空数组
                                    $getAllData为true时返回值解释：
                                                [
                                                    'request' =>[//请求数据
                                                        'url' =>'https://qq.com',//请求地址
                                                        'method' =>'get',//请求方法
                                                        'head' =>[],//请求头
                                                        'body' =>'',//请求体
                                                    ],
                                                    'response'=>[//响应数据
                                                        'http_code' => 200,//响应码，详询https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Status
                                                        'content_type' =>'',//响应编码类型
                                                        'header' =>'',//响应头
                                                        'header_arr' =>[],//响应头数组
                                                        'body' =>'',//响应体
                                                        'info' =>[],//完整响应数据
                                                        'location' =>'',//响应跳转地址
                                                        'response_time' =>0.1221,//响应时间 单位秒
                                                        'error_no' =>0,//网络错误号
                                                        'error_info' =>'',//网络错误信息
                                                        'curl_version' =>'7.68.0',//curl库版本
                                                ]


    使用方法
        极简get请求
            $url = 'https://qq.com';
            list($code,$msg,$data) = requestNetworkV1($url);
            var_dump($code,$msg,$data);
        post文本和文件组合请求
            $url = 'https://qq.com';
            $parame = [
                'name'=>'张三',
                'age'=>'25',
                'head_portrait'=>['D:\head.jpg'],
            ];
            list($code,$msg,$data) = requestNetworkV1($url,'POST',$parame);
            var_dump($code,$msg,$data);
        post纯文本请求
            $parame = '23333333333333333333333333333333';
            $requestHeader = [
                'Content-type'=>'application/octet-stream',
            ];
            list($code, $msg, $data) = requestNetworkV1('https://qq.com/','POST',$parame, $requestHeader);
            var_dump($code,$msg,$data);
        post JSON请求
            $parame = [
                'name'=>'张飞',
                'age'=>'1900',
                'heigth'=>'190cm',
            ];
            $parame = json_encode($parame,true);
            $requestHeader = [
                'Content-type'=>'application/json',
            ];
            list($code, $msg, $data) = requestNetworkV1('https://qq.com/','POST',$parame, $requestHeader);
            var_dump($code,$msg,$data);
        put请求
            $parame = file_get_contents('D:\1down\chrome\d89d6ffa658706fa.jpg');
            $requestHeader = [
                'Content-type'=>'application/octet-stream',
            ];
            list($code, $msg, $data) = requestNetworkV1('https://qq.com','PUT',$parame, $requestHeader);
            var_dump($code,$msg,$data);
        put请求
            $parame = [
                'name'=>'张三',
                'age'=>'25',
            ];
            $requestHeader = [];
            list($code, $msg, $data) = requestNetworkV1('https://qq.com','DELETE',$parame, $requestHeader);
            var_dump($code,$msg,$data);
        head请求  head请求表示只需要响应头，不要响应体，故需要填写$getAllData = true，否者反回值里看不到任何信息
            list($code, $msg, $data) = requestNetworkV1('https://qq.com','HEAD',$parame, $requestHeader,10,true);
            var_dump($code,$msg,$data);
       代理请求  PHP支持8种代理方式：CURLPROXY_HTTP、CURLPROXY_HTTPS、CURLPROXY_HTTPS2、CURLPROXY_HTTP_1_0、CURLPROXY_SOCKS4、CURLPROXY_SOCKS4A、CURLPROXY_SOCKS5、CURLPROXY_SOCKS5_HOSTNAME，详询https://www.php.net/manual/en/function.curl-setopt.php
            $requestHeader['__CURL_SETOPT_EXTEND__'] = [
                //http代理
                //    CURLOPT_PROXYTYPE =>CURLPROXY_HTTP, //代理类型
                //    CURLOPT_PROXY => '127.0.0.1:6666', //代理地址
                //SOCKS5代理
                //    CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5, //代理类型 有时CURLPROXY_SOCKS5连不通，尝试换成 CURLPROXY_SOCKS5_HOSTNAME 试下
                //    CURLOPT_PROXY => '127.0.0.1:6666', //代理地址
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5, //代理类型
                CURLOPT_PROXY => '127.0.0.1:8888', //代理地址
            ];
            list($code, $msg, $data) = requestNetworkV1('https://google.com','get', [], $requestHeader);
            var_dump($code,$msg,$data);

 */
function requestNetworkV1($url, $method = 'GET', $parame = '', $requestHeader = [], $timeout = 25, $getAllData = false){
    $reData = [1, 'ok', []];
    if (empty($url)){
        $reData[0] = 0;
        $reData[1] = 'url_parame_error';
        return $reData;
    }
    $method = strtoupper($method);
    if (!in_array($method,['POST','GET','PUT','DELETE','HEAD'])){
        $reData[0] = 0;
        $reData[1] = 'method_parame_error';
        return $reData;
    }
    $timeout = (int)$timeout;
    if ($timeout < 1 || $timeout > 360){
        $reData[0] = 0;
        $reData[1] = 'timeout_parame_error';
        return $reData;
    }
    $curlSetoptArr = [//curl初始选项
        CURLOPT_URL=>$url,
        CURLOPT_TIMEOUT => $timeout,//超时时间，秒
        CURLOPT_HTTPHEADER => [], // 自定义header头
        CURLOPT_CONNECTTIMEOUT => 120,// 连接建立最长耗时
        CURLOPT_SSL_VERIFYPEER => false,//对认证证书来源的检查
        CURLOPT_SSL_VERIFYHOST => false,//从证书中检查SSL加密算法是否存在
        CURLOPT_FOLLOWLOCATION => false,//使用自动跳转
        CURLOPT_AUTOREFERER => false,//自动设置Referer
        CURLOPT_HEADER => true,//不返回 Header 区域内容,填true则返回带head的所有完整内容
        CURLOPT_ACCEPT_ENCODING => isset($requestHeader['Accept-Encoding']) ? $requestHeader['Accept-Encoding'] :'gzip,deflate', //Accept-Encoding支持的编码类型
        CURLOPT_RETURNTRANSFER => true,//不打印结果
        CURLINFO_HEADER_OUT => true,//是否要获取响应头，数据在curl_getinfo()里取
        CURLOPT_REFERER => '',//在HTTP请求头中"Referer: "的内容。
    ];
    static $ch = NULL;//使用static保存curl句柄，防止循环、并发调用时内存暴涨
    if ($ch === NULL){
        $ch = curl_init();
    }
    curl_reset($ch);//重置一次设置，使每次调用都是全新句柄
    try {
        //组装请求体
        isset($requestHeader['Content-type']) ? : ($requestHeader['Content-type'] = 'multipart/form-data');
        switch ($requestHeader['Content-type']){
            case 'application/x-www-form-urlencoded':
                $parame = http_build_query($parame);
                break;
            case 'multipart/form-data':
                if (!is_array($parame)){
                    break;
                }
                $boundary = '------'.rand(10000,99999);//设置分割标识
                $parameTmp = '--'.$boundary."\r\n";
                $parameLength = count($parame);
                $i = 1;
                foreach ($parame as $key => $val) {
                    if (is_array($val) && isset($val[0]) && is_readable($val[0])){//文件
                        $fp = fopen($val[0], "r");
                        $file_content = fread($fp, filesize($val[0]));
                        fclose($fp);
                        $base_name = basename($val[0]);
                        $parameTmp .= 'content-disposition: form-data; name="'.$key.'"; filename="' . $base_name .'"'. "\r\n";
                        $parameTmp .= 'Content-type: ' . (isset($val[1]) ? $val[1]:'application/octet-stream') . "\r\n\r\n";
                        $parameTmp .= $file_content."\r\n";
                        if ($i == $parameLength){
                            $parameTmp .= '--'.$boundary."--\r\n";
                        }else{
                            $parameTmp .= '--'.$boundary."\r\n";
                        }
                    }else{
                        $parameTmp .='content-disposition: form-data; name="'.$key.'"'."\r\n\r\n";
                        $parameTmp .= ((string)$val) . "\r\n";
                        if ($i == $parameLength){
                            $parameTmp .= '--'.$boundary."--\r\n";
                        }else{
                            $parameTmp .= '--'.$boundary."\r\n";
                        }
                    }
                    $i++;
                }
                $parame = $parameTmp;
                $requestHeader['Content-type'] =  "multipart/form-data; charset=utf-8; boundary=".$boundary; //强制重写Content-type
                break;
            case 'text/plain':
            case 'text/html':
            case 'application/json':
            case 'application/xml':
            case 'application/octet-stream':
            default:
                $curlSetoptArr[CURLOPT_BINARYTRANSFER] = true;//二进制编码
        }
        $curlSetoptArr[CURLOPT_POST] = false;//默认非post
        switch ($method){
            case 'GET'://获取
                break;
            case 'POST'://新建
                $curlSetoptArr[CURLOPT_POST] = true;//发送一个常规post请求
                break;
            case 'DELETE'://刪除
                $curlSetoptArr[CURLOPT_CUSTOMREQUEST] = 'DELETE';  //发送一个DELETE请求
                break;
            case 'PUT'://更新
                $curlSetoptArr[CURLOPT_CUSTOMREQUEST] = 'PUT';  //发送一个PUT请求
                break;
            case 'HEAD'://头
                $curlSetoptArr[CURLOPT_CUSTOMREQUEST] = 'HEAD';  //发送一个HEAD请求
                $curlSetoptArr[CURLOPT_NOBODY] = true;//不返回响应体，只取响应头
                break;
        }
        $curlSetoptArr[CURLOPT_CUSTOMREQUEST] = $method;  //定义请求方法类型
        $curlSetoptArr[CURLOPT_POSTFIELDS] = $parame; //参数包
        $header = [];//自定义请求头
        //组装扩展参数
        if (isset($requestHeader['__CURL_SETOPT_EXTEND__']) && !empty($requestHeader['__CURL_SETOPT_EXTEND__']) && is_array($requestHeader['__CURL_SETOPT_EXTEND__'])){
            $curlSetoptArr = array_replace($curlSetoptArr, $requestHeader['__CURL_SETOPT_EXTEND__']);
            unset($requestHeader['__CURL_SETOPT_EXTEND__']);
        }
        //组装用户自定义头参数
        if(!empty($requestHeader)) {
            foreach($requestHeader as $key=>$val) {
                $header[] = $key.': '.$val;
            }
        }
        $header[] = 'Expect:';//禁止curl的100-continue行为    2021-12-24 19:19:50  详情https://www.cnblogs.com/lpfuture/p/13372829.html
        $curlSetoptArr[CURLOPT_HTTPHEADER] = $header;//自定义head头
        curl_setopt_array($ch, $curlSetoptArr);
        $ret = curl_exec($ch);
        $networkErrorNo = curl_errno($ch);//获取curl联网错误码
        $networkErrorInfo = '';
        if ($networkErrorNo) {//异常
            $networkErrorInfo = 'networkError;errorCode:' . $networkErrorNo . ';errorInfo:' . curl_strerror($networkErrorNo);
            if (empty($ret)){
                $ret = $networkErrorInfo;
            }
            throw new \Exception($ret);
        }
        $reDataTmp = explode("\r\n\r\n", $ret);//分离请求头和请求体
        if ($getAllData){//分离响应体和响应头
            $response_location = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
            $responseInfo = curl_getinfo( $ch );
            $reDataTmp = [
                'request' =>[//请求数据
                    'url' =>$url,//请求地址
                    'method' =>$method,//请求方法
                    'head' =>$requestHeader,//请求头
                    'body' =>$parame,//请求体
                ],
                'response'=>[//响应数据
                    'http_code' => $responseInfo['http_code'],//响应码，详询https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Status
                    'content_type' =>$responseInfo['content_type'],//响应编码类型
                    'header' =>$reDataTmp[0],//响应头
                    'header_arr' =>explode("\r\n",($reDataTmp[0] ? : [])),//响应头数组
                    'body' =>$reDataTmp[1],//响应体
                    'info' =>$responseInfo,//响应体信息
                    'location' =>$response_location,//响应跳转地址
                    'response_time' =>$responseInfo['total_time'],//响应时间
                    'error_no' =>$networkErrorNo,//网络错误号
                    'error_info' =>$networkErrorInfo,//网络错误信息
                    'curl_version' =>curl_version()['version'],//curl版本
                ],
            ];
            $reData[2] = $reDataTmp;
        }else{
            $reData[2] = $reDataTmp[1];
        }
    }catch (\Throwable $re){
        $reData[0] = 0;
        $reData[1] = $re->getMessage();
    }
    curl_close($ch);
    return $reData;
}


/**************************     科学计算       *********************/

/**
 * 计算两个经纬度坐标之前的距离，返回米，有小数点
 * @param $lng1  坐标1 经度
 * @param $lat1  坐标1 纬度
 * @param $lng2  坐标2 经度
 * @param $lat2  坐标2 纬度
 * @return 一个小数，单位米
 */
function coordinateDistance($lng1,$lat1,$lng2,$lat2){
    $EARTH_RADIUS = 6378137;   //地球半径
    $RAD = pi() / 180.0;
    $radLat1 = $lat1 * $RAD;
    $radLat2 = $lat2 * $RAD;
    $a = $radLat1 - $radLat2;    // 两点纬度差
    $b = ($lng1 - $lng2) * $RAD;  // 两点经度差
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $s = $s * $EARTH_RADIUS;
    $s = round($s * 10000) / 10000;
    return $s;
}




/**************************     数值计算       *********************/

/**
 * 数字加壳 将0、1、200、980等整数转换为小数，单位分，精确到小数点后两位  0=0.00元 1 = 0.01元 100 = 1元 880 = 8.80元
 * 用在一些金融系统里，如订单系统，统计系统，场景如：保存到数据库时是整数，取出使用时需要变成小数显示，免于在数据库里操作导致精度丢失的烦恼
 * author github@zong86
 * time 2021-04-14 12:13:12
 *
 * @param $number 一个整数
 * @param $decimal2 是否强制精确到两位小数
 *           false : 3.8、7.56、0.6、0、3
 *           true 3.80、7.56、0.60、0.00、3.00
 * @return string | null  返回一个数字字符串，若失败返 null
 */
function decimalClad($number, $decimal2 = false){
    if (floor($number) != $number || count( explode('.',$number)) != 1){
        return null;
    }
    $reData = bcdiv($number,100,2);
    if ($decimal2){//强制精确到两位小数
        $reDataTp = explode('.',$reData);
        if (!isset($reDataTp[1])){//无小数
            $reData .='.00';
        }else{
            if ((int)$reDataTp[1] < 10 ){
                $reData = $reDataTp[0] .'.0' . (int)$reDataTp[1];
            }
        }
    }else{
        $reDataTp = explode('.',$reData);
        if (isset($reDataTp[1])){
            if ($reDataTp[1] != '00'){
                if ((int)$reDataTp[1] < 10 ){
                    $reData = $reDataTp[0] .'.0' . (int)$reDataTp[1];
                }else{
                    $reData = $reDataTp[0] .'.' . str_replace('0','',$reDataTp[1]);
                }
            }else{
                $reData = $reDataTp[0];
            }
        }
    }
    return $reData;
}

/**
 * 小数去壳 将1.0 9.9 8.79等小数转为 整数，小数部分最多两位，单位分 ， 0.01元 = 1   5元 = 500  8.8元 = 880
 * 用在一些金融系统里，如订单系统，统计系统，场景如：将用户输入的金额保存为整数，免于在数据库里操作导致精度丢失的烦恼
 * 注意本函数带有小数点检查，若值不是小数或小于0则返0
 * author github@zong86
 * time 2021-04-14 12:13:12
 *
 * @param $number 一个小数或整数字符
 * @return int | null  返回一个整数（int）型数字，若失败返 null
 *
 * 使用方法：
 * $number = decimalShelling(12.36);
 * if(!is_int($number)){//去壳失败
 *  echo $number;
 * }
 *
 */
function decimalShelling($number){
    $moneyArr = explode('.', $number);
    if (count($moneyArr) > 2){//小数点检查
        return null; //'小数格式错误';
    }
    //整数部分
    if (isset($moneyArr[0]) && !is_numeric($moneyArr[0])){
        return null; //'整数值错误';
    }
    if (isset($moneyArr[1]) && !is_numeric($moneyArr[1])){
        return null; //'小数值错误';
    }
    if (isset($moneyArr[1]) && (strlen($moneyArr[1]) > 2 || (int)$moneyArr[1] > 99)){
        return null; //'小数值错误，仅可精确到小数点后两位';
    }
    return (int)bcmul($number, 100, 0);
}


/**************************     时间计算       *********************/

/**
 * 获取指定日期间隔的数组，精确到天
 * author github@zong86
 * time 2021-04-14 12:13:12
 *
 * @param $startDay 开始日期
 * @param $endDay 结束日期
 * @param $sort 排序  可选：desc、asc
 * @return string  日期间隔数组
 *
 */
function getDateRegion($startDay,$endDay,$sort = 'desc'){
    $startDay = strtotime($startDay);
    $endDay = strtotime($endDay);
    if ($startDay == $endDay){//数值相等，无法比较
        return [date('Y-m-d',$startDay)];
    }
    if ($startDay > $endDay){
        list($endDay,$startDay)=[$startDay,$endDay];
    }
    $reData = [];
    if (strtolower($sort) == 'desc'){
        while ($endDay > $startDay){
            $reData[] = date('Y-m-d',$endDay);
            $endDay = strtotime('-1 day',$endDay);
        }
    }else{
        while ($startDay <= $endDay){
            $reData[] = date('Y-m-d',$startDay);
            $startDay = strtotime('+1 day',$startDay);
        }
    }
    return $reData;
}

/**
 * 获取指定日期间隔的数组，精确到小时
 * author github@zong86
 * time 2021-04-14 12:13:12
 *
 * @param $startDay 开始日期
 * @param $endDay 结束日期
 * @return string  日期间隔数组
 */
function getDateRegionHour($startDay,$endDay,$sort = 'desc'){
    $startDay = strtotime($startDay);
    $endDay = strtotime($endDay);
    if ($startDay == $endDay){//数值相等，无法比较
        return [date('Y-m-d H:00:00',$startDay)];
    }
    if ($startDay > $endDay){
        list($endDay,$startDay)=[$startDay,$endDay];
    }
    $reData = [];
    if (strtolower($sort) == 'desc'){
        while ($endDay > $startDay){
            $reData[] = date('Y-m-d H:00:00',$endDay);
            $endDay = strtotime('-1 hour',$endDay);
        }
    }else{
        while ($startDay <= $endDay){
            $reData[] = date('Y-m-d H:00:00',$startDay);
            $startDay = strtotime('+1 hour',$startDay);
        }
    }
    return $reData;
}


/**************************     字符操作       *********************/

/**
 * 创建系统订单号生成随机六位数字，不足六位两边补零，可用于生成验证码等场景
 * author github@zong86
 * time 2021-04-14 12:13:12

 * @param - 无
 * @return string  一个随机六位数字
 *
 */
function getRand6(){
    return str_pad(mt_rand(0, 999999), 6, "0", STR_PAD_LEFT);
}

/**
 * 创建系统订单号，生成一个22位固定长度数字
 * author github@zong86
 * 2022-06-09 12:14:30
 *
 * @param - 无
 * @return string  订单号码  一个22位固定长度数字，如：2023071267572157924694
 *
 */
function createOrderNo(){
    return date('Ymd').substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(1000, 9999));
}

/**
 * 产生随机字符串，默认16位
 * author github@zong86
 * time 2021-04-14 12:13:12
 *
 * @param int $length 指定长度 默认16位
 * @param int $chars 指定字符 默认a-z + 0-9
 * @return string 产生的随机字符串
 */
function getRandomString($length = 16, $chars = "abcdefghijklmnopqrstuvwxyz0123456789"){
    $str ="";
    if ($length < 1 || empty($chars)){
        return $str;
    }
    for ( $i = 0; $i < $length; $i++ )  {
        $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}

/**************************     系统       *********************/
/**
 * 打印log到系统日志数据库
 * author github@zong86
 * 2022-06-09 12:14:30
 *
 * 本方法目的是解决分布式、多主机环境下日志的收集归类难题，故将日志统一打印到数据库方便开发者及时查看
 * 本方法自动录入调用文件和行号，调用时间
 * 本方法log数据直接入数据库，入库字符量可能巨大，建议仅在开发期间使用，开发结束后取消使用，建议及时巡查并清理无用日志数据
 * 本函数依赖系统system_log表(类\Service\Model\SystemLog)运行
 * 本函数无抛异常行为，若异常则返回false，可无视返回值
 *
 * @param $msg   string   日志内容     可以是任意类型的变量，一般为字符串，当然像数组也是可以的
 * @param $modular string   模块         自定义名称 不填默认为system，最长32位字符，超过的将被截断
 * @return bool  操作成功返回true 操作失败返回false，可无视返回值
 */
function logSystem($msg = '', $modular = 'system'){
    try {
        if (!class_exists('\Service\Model\SystemLogModel')) {
            return false;
        }
        if (mb_strlen($modular) > 32){
            $modular = mb_substr($modular,0,32);
        }
        if (!is_string($msg)){
            $msg = var_export($msg,true);
        }
        $origin = '';
        $debugInfo = debug_backtrace();
        foreach ($debugInfo as $debugInfoV) {//获取报错行号
            if (!empty($debugInfoV['line'])){
                $origin = $debugInfoV['file'].':'.$debugInfoV['line'];
                break;
            }
        }
        $inArr = [
            'modular' =>$modular,
            'content' =>$msg,
            'origin' => $origin,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        (new \Service\Model\SystemLogModel())->addition($inArr);
    }catch (Throwable $re){
        return false;
    }
    return true;
}

/**
 * 创建软链接文件
 * author github@zong86
 * time 2021-04-14 12:13:12
 *
 * @param string $originalFile 原始文件 （完整地址，全路径）
 * @param string $symlinkFile 软链接文件 （完整地址，全路径）
 * @return bool 若原始文件不存在或软链接文件存在 或 函数执行失败 则返false    成功返true
 */
function createSymlink($originalFile, $symlinkFile){
    if (!file_exists($originalFile) || file_exists($symlinkFile)){
        return false;
    }
    return symlink($originalFile, $symlinkFile);
}
