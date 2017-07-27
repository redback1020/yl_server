<?php
//安全类

class Core_Security {

    //xss 过滤
    function xss_clean($val) {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);
        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(�{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(�{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $this->remove_invisible_characters($val);
    }



    //删除隐藏字符
    public function remove_invisible_characters($str, $url_encoded = TRUE) {
        $non_displayables = array();

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/i';	// url encoded 16-31
            $non_displayables[] = '/%7f/i';	// url encoded 127
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }


    //sign 生成sign
    public function generate_sign( $data ,$secure_key = null ){
        if( $secure_key === null ){
            $sign_secure_key = config_item('sign_secure_key');
        }else{
            $sign_secure_key = $secure_key;
        }


        if( is_array( $data ) ){
            ksort($data);

            $string = [];
            foreach($data as $key => $value){
                $string[] = $key.'='.$value;
            }
            $string = implode('&',$string);
        }else{
            $string = $data;
        }

        return md5($string.$sign_secure_key);
    }

    //验证sign
    public function verify_sign($data,$signature,$secure_key = null ){
        return $data && $signature == $this->generate_sign($data,$secure_key);
    }


    //是否是cli请求
    public function is_cli()
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }


    //是否是手机信息
    public function get_mobile(){

        if(  isset($_SERVER['HTTP_USER_AGENT']) ){
            foreach ($this->mobiles as $key => $val)
            {
                if (FALSE !== (stripos(trim($_SERVER['HTTP_USER_AGENT']), $key)))
                {
                    return $val;
                }
            }
        }

        return false;

    }

    //获取浏览器信息
    public function get_browser()
    {

        if(  isset($_SERVER['HTTP_USER_AGENT']) ) {
            foreach ($this->browsers as $key => $val) {
                if (preg_match('|' . $key . '.*?([0-9\.]+)|i',trim($_SERVER['HTTP_USER_AGENT']), $match)) {
                    return $val.' '.$match[1];
                }
            }
        }

        return false;
    }



    //获取当前平台信息
    public function get_platform(){

        if( isset($_SERVER['HTTP_USER_AGENT']) ){
            foreach ($this->platforms as $key => $val)
            {
                if (preg_match('|'.preg_quote($key).'|i',  trim($_SERVER['HTTP_USER_AGENT'])))
                {
                    return $val;
                }
            }
        }

        return false;
    }



    protected $platforms = array(
        'windows nt 10.0'	=> 'Windows 10',
        'windows nt 6.3'	=> 'Windows 8.1',
        'windows nt 6.2'	=> 'Windows 8',
        'windows nt 6.1'	=> 'Windows 7',
        'windows nt 6.0'	=> 'Windows Vista',
        'windows nt 5.2'	=> 'Windows 2003',
        'windows nt 5.1'	=> 'Windows XP',
        'windows nt 5.0'	=> 'Windows 2000',
        'windows nt 4.0'	=> 'Windows NT 4.0',
        'winnt4.0'			=> 'Windows NT 4.0',
        'winnt 4.0'			=> 'Windows NT',
        'winnt'				=> 'Windows NT',
        'windows 98'		=> 'Windows 98',
        'win98'				=> 'Windows 98',
        'windows 95'		=> 'Windows 95',
        'win95'				=> 'Windows 95',
        'windows phone'			=> 'Windows Phone',
        'windows'			=> 'Unknown Windows OS',
        'android'			=> 'Android',
        'blackberry'		=> 'BlackBerry',
        'iphone'			=> 'iOS',
        'ipad'				=> 'iOS',
        'ipod'				=> 'iOS',
        'os x'				=> 'Mac OS X',
        'ppc mac'			=> 'Power PC Mac',
        'freebsd'			=> 'FreeBSD',
        'ppc'				=> 'Macintosh',
        'linux'				=> 'Linux',
        'debian'			=> 'Debian',
        'sunos'				=> 'Sun Solaris',
        'beos'				=> 'BeOS',
        'apachebench'		=> 'ApacheBench',
        'aix'				=> 'AIX',
        'irix'				=> 'Irix',
        'osf'				=> 'DEC OSF',
        'hp-ux'				=> 'HP-UX',
        'netbsd'			=> 'NetBSD',
        'bsdi'				=> 'BSDi',
        'openbsd'			=> 'OpenBSD',
        'gnu'				=> 'GNU/Linux',
        'unix'				=> 'Unknown Unix OS',
        'symbian' 			=> 'Symbian OS'
    );


    // The order of this array should NOT be changed. Many browsers return
    // multiple browser types so we want to identify the sub-type first.
    protected $browsers = array(
        'OPR'			=> 'Opera',
        'Flock'			=> 'Flock',
        'Edge'			=> 'Spartan',
        'Chrome'		=> 'Chrome',
            // Opera 10+ always reports Opera/9.80 and appends Version/<real version> to the user agent string
        'Opera.*?Version'	=> 'Opera',
        'Opera'			=> 'Opera',
        'MSIE'			=> 'Internet Explorer',
        'Internet Explorer'	=> 'Internet Explorer',
        'Trident.* rv'	=> 'Internet Explorer',
        'Shiira'		=> 'Shiira',
        'Firefox'		=> 'Firefox',
        'Chimera'		=> 'Chimera',
        'Phoenix'		=> 'Phoenix',
        'Firebird'		=> 'Firebird',
        'Camino'		=> 'Camino',
        'Netscape'		=> 'Netscape',
        'OmniWeb'		=> 'OmniWeb',
        'Safari'		=> 'Safari',
        'Mozilla'		=> 'Mozilla',
        'Konqueror'		=> 'Konqueror',
        'icab'			=> 'iCab',
        'Lynx'			=> 'Lynx',
        'Links'			=> 'Links',
        'hotjava'		=> 'HotJava',
        'amaya'			=> 'Amaya',
        'IBrowse'		=> 'IBrowse',
        'Maxthon'		=> 'Maxthon',
        'Ubuntu'		=> 'Ubuntu Web Browser'
    );

    protected $mobiles = array(
        // legacy array, old values commented out
        'mobileexplorer'	=> 'Mobile Explorer',
        //  'openwave'			=> 'Open Wave',
        //	'opera mini'		=> 'Opera Mini',
        //	'operamini'			=> 'Opera Mini',
        //	'elaine'			=> 'Palm',
        'palmsource'		=> 'Palm',
        //	'digital paths'		=> 'Palm',
        //	'avantgo'			=> 'Avantgo',
        //	'xiino'				=> 'Xiino',
        'palmscape'			=> 'Palmscape',
        //	'nokia'				=> 'Nokia',
        //	'ericsson'			=> 'Ericsson',
        //	'blackberry'		=> 'BlackBerry',
        //	'motorola'			=> 'Motorola'

            // Phones and Manufacturers
        'motorola'		=> 'Motorola',
        'nokia'			=> 'Nokia',
        'palm'			=> 'Palm',
        'iphone'		=> 'Apple iPhone',
        'ipad'			=> 'iPad',
        'ipod'			=> 'Apple iPod Touch',
        'sony'			=> 'Sony Ericsson',
        'ericsson'		=> 'Sony Ericsson',
        'blackberry'	=> 'BlackBerry',
        'cocoon'		=> 'O2 Cocoon',
        'blazer'		=> 'Treo',
        'lg'			=> 'LG',
        'amoi'			=> 'Amoi',
        'xda'			=> 'XDA',
        'mda'			=> 'MDA',
        'vario'			=> 'Vario',
        'htc'			=> 'HTC',
        'samsung'		=> 'Samsung',
        'sharp'			=> 'Sharp',
        'sie-'			=> 'Siemens',
        'alcatel'		=> 'Alcatel',
        'benq'			=> 'BenQ',
        'ipaq'			=> 'HP iPaq',
        'mot-'			=> 'Motorola',
        'playstation portable'	=> 'PlayStation Portable',
        'playstation 3'		=> 'PlayStation 3',
        'playstation vita'  	=> 'PlayStation Vita',
        'hiptop'		=> 'Danger Hiptop',
        'nec-'			=> 'NEC',
        'panasonic'		=> 'Panasonic',
        'philips'		=> 'Philips',
        'sagem'			=> 'Sagem',
        'sanyo'			=> 'Sanyo',
        'spv'			=> 'SPV',
        'zte'			=> 'ZTE',
        'sendo'			=> 'Sendo',
        'nintendo dsi'	=> 'Nintendo DSi',
        'nintendo ds'	=> 'Nintendo DS',
        'nintendo 3ds'	=> 'Nintendo 3DS',
        'wii'			=> 'Nintendo Wii',
        'open web'		=> 'Open Web',
        'openweb'		=> 'OpenWeb',

            // Operating Systems
        'android'		=> 'Android',
        'symbian'		=> 'Symbian',
        'SymbianOS'		=> 'SymbianOS',
        'elaine'		=> 'Palm',
        'series60'		=> 'Symbian S60',
        'windows ce'	=> 'Windows CE',

            // Browsers
        'obigo'			=> 'Obigo',
        'netfront'		=> 'Netfront Browser',
        'openwave'		=> 'Openwave Browser',
        'mobilexplorer'	=> 'Mobile Explorer',
        'operamini'		=> 'Opera Mini',
        'opera mini'	=> 'Opera Mini',
        'opera mobi'	=> 'Opera Mobile',
        'fennec'		=> 'Firefox Mobile',

            // Other
        'digital paths'	=> 'Digital Paths',
        'avantgo'		=> 'AvantGo',
        'xiino'			=> 'Xiino',
        'novarra'		=> 'Novarra Transcoder',
        'vodafone'		=> 'Vodafone',
        'docomo'		=> 'NTT DoCoMo',
        'o2'			=> 'O2',

            // Fallback
        'mobile'		=> 'Generic Mobile',
        'wireless'		=> 'Generic Mobile',
        'j2me'			=> 'Generic Mobile',
        'midp'			=> 'Generic Mobile',
        'cldc'			=> 'Generic Mobile',
        'up.link'		=> 'Generic Mobile',
        'up.browser'	=> 'Generic Mobile',
        'smartphone'	=> 'Generic Mobile',
        'cellphone'		=> 'Generic Mobile'
    );

    // There are hundreds of bots but these are the most common.
    protected $robots = array(
        'googlebot'		=> 'Googlebot',
        'msnbot'		=> 'MSNBot',
        'baiduspider'		=> 'Baiduspider',
        'bingbot'		=> 'Bing',
        'slurp'			=> 'Inktomi Slurp',
        'yahoo'			=> 'Yahoo',
        'ask jeeves'		=> 'Ask Jeeves',
        'fastcrawler'		=> 'FastCrawler',
        'infoseek'		=> 'InfoSeek Robot 1.0',
        'lycos'			=> 'Lycos',
        'yandex'		=> 'YandexBot',
        'mediapartners-google'	=> 'MediaPartners Google',
        'CRAZYWEBCRAWLER'	=> 'Crazy Webcrawler',
        'adsbot-google'		=> 'AdsBot Google',
        'feedfetcher-google'	=> 'Feedfetcher Google',
        'curious george'	=> 'Curious George',
        'ia_archiver'		=> 'Alexa Crawler',
        'MJ12bot'		=> 'Majestic-12',
        'Uptimebot'		=> 'Uptimebot'
    );

}