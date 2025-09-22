<?php
namespace lib\ocr;

use Exception;

class BaiduOcrService implements OcrServiceInterface
{
    private $url = 'https://aip.baidubce.com/rest/2.0/ocr';
    private $apiKey;
    private $secretKey;
    private $accessToken;
    private static $image_status = [
        'normal' => '识别正常',
        'reversed_side' => '身份证正反面颠倒',
        'non_idcard' => '上传的图片中不包含身份证',
        'blurred' => '身份证模糊',
        'other_type_card' => '非身份证照片',
        'over_exposure' => '身份证关键字段反光或过曝',
        'over_dark' => '身份证照片亮度过低',
        'unknown' => '未知状态'
    ];

    public function __construct($apiKey, $secretKey)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->accessToken = $this->getAccessTokenCache();
    }

    //身份证正面识别
	public function idcard($file_path){
        $params = [
            'image' => base64_encode(file_get_contents($file_path)),
            'id_card_side' => 'front'
        ];
        try{
            $arr = $this->request('/v1/idcard', $params);
            if($arr['image_status'] != 'normal'){
                throw new Exception(self::$image_status[$arr['image_status']]);
            }
        }catch(Exception $e){
            throw new Exception('身份证识别失败，' . $e->getMessage());
        }
        $result = ['id_no'=>$arr['words_result']['公民身份号码']['words'], 'name'=>$arr['words_result']['姓名']['words'], 'address'=>$arr['words_result']['住址']['words'], 'sex'=>$arr['words_result']['性别']['words'], 'ethnicity'=>$arr['words_result']['民族']['words'], 'birth_date'=>self::formatDate($arr['words_result']['出生']['words'])];
        return $result;
	}

    //身份证反面识别
	public function idcard_back($file_path){
        $params = [
            'image' => base64_encode(file_get_contents($file_path)),
            'id_card_side' => 'back'
        ];
        try{
            $arr = $this->request('/v1/idcard', $params);
            if($arr['image_status'] != 'normal'){
                throw new Exception(self::$image_status[$arr['image_status']]);
            }
        }catch(Exception $e){
            throw new Exception('身份证识别失败，' . $e->getMessage());
        }
        $result = ['issue_authority'=>$arr['words_result']['签发机关']['words'], 'period_begin'=>self::formatDate($arr['words_result']['签发日期']['words']), 'period_end'=>self::formatDate($arr['words_result']['失效日期']['words'])];
        return $result;
	}

    //护照识别
	public function passport($file_path){
        $params = [
            'image' => base64_encode(file_get_contents($file_path)),
        ];
        try{
            $arr = $this->request('/v1/overseas_passport', $params);
        }catch(Exception $e){
            throw new Exception('护照识别失败，' . $e->getMessage());
        }
        $result = ['passport_no'=>$arr['words_result']['护照号']['words'], 'name'=>$arr['words_result']['姓名拼音']['words'], 'sex'=>$arr['words_result']['性别']['words'], 'country'=>$arr['words_result']['国籍']['words'], 'birth_date'=>self::formatDate($arr['words_result']['出生日期']['words']), 'period_end'=>self::formatDate($arr['words_result']['有效期']['words'])];
        return $result;
    }

    //银行卡识别
	public function bank_card($file_path){
        $params = [
            'image' => base64_encode(file_get_contents($file_path)),
        ];
        try{
            $arr = $this->request('/v1/bankcard', $params);
        }catch(Exception $e){
            throw new Exception('银行卡识别失败，' . $e->getMessage());
        }
        $card_types = [0=>'', 1=>'DC', 2=>'CC', 3=>'SCC', 4=>'PC'];
        $result = ['bank_name'=>$arr['result']['bank_name'], 'card_type'=>$card_types[$arr['result']['bank_card_type']], 'card_no'=>str_replace(' ','',$arr['result']['bank_card_number']), 'period_end'=>$arr['result']['valid_date']];
        return $result;
    }

    //营业执照识别
	public function business_license($file_path){
        $params = [
            'image' => base64_encode(file_get_contents($file_path)),
        ];
        try{
            $arr = $this->request('/v1/business_license', $params);
        }catch(Exception $e){
            throw new Exception('营业执照识别失败，' . $e->getMessage());
        }

        $license_no = $arr['words_result']['社会信用代码']['words'];
        if(empty($license_no) || $license_no == '无') {
            $license_no = $arr['words_result']['证件编号']['words'];
        }
        $period_begin = $arr['words_result']['有效期起始日期']['words'];
        if(empty($period_begin) || $period_begin == '无' || $period_begin == '年月日') $period_begin = $arr['words_result']['核准日期']['words'];
        $period_end = $arr['words_result']['有效期']['words'];
        if(empty($period_end) || $period_end == '无' || $period_end == '年月日') $period_end = '长期';

        $result = ['license_no'=>$license_no, 'name'=>$arr['words_result']['单位名称']['words'], 'address'=>$arr['words_result']['地址']['words'], 'reg_date'=>str_replace(['年','月','日'], ['-','-',''], $arr['words_result']['成立日期']['words']), 'legal_name'=>$arr['words_result']['法人']['words'], 'type'=>$arr['words_result']['类型']['words'], 'registered_capital'=>$arr['words_result']['注册资本']['words'], 'business_scope'=>$arr['words_result']['经营范围']['words'], 'period_begin'=>str_replace(['年','月','日'], ['-','-',''], $period_begin), 'period_end'=>str_replace(['年','月','日'], ['-','-',''], $period_end)];
        return $result;
    }

    //银行开户许可证识别
	public function bank_account_license($file_path){
        $params = [
            'image' => base64_encode(file_get_contents($file_path)),
        ];
        try{
            $arr = $this->request('/v1/account_opening', $params);
        }catch(Exception $e){
            throw new Exception('银行开户许可证识别失败，' . $e->getMessage());
        }
        $result = ['bank_account'=>$arr['words_result']['账号']['word'][0], 'legal_name'=>$arr['words_result']['法人']['word'][0], 'bank_name'=>$arr['words_result']['开户银行']['word'][0], 'approval_no'=>$arr['words_result']['编号']['word'][0], 'name'=>$arr['words_result']['公司名称']['word'][0], 'permit_no'=>$arr['words_result']['核准号']['word'][0]];
        return $result;
    }

    private function request($path, $params){
        $url = $this->url . $path . '?access_token=' . $this->accessToken;
        $postFields = http_build_query($params);
        $response = get_curl($url, $postFields);
        $data = json_decode($response, true);
        if (!$data) {
            throw new Exception('请求失败');
        }
        if (isset($data['error_code'])) {
            if($data['error_code'] == 110) {
                $this->accessToken = $this->getAccessTokenCache(true);
                return $this->request($path, $params);
            }
            throw new Exception($data['error_msg']);
        }
        return $data;
    }

    private function getAccessTokenCache($force = false)
    {
        global $CACHE;
        $cacheKey = 'baidu_ocr_access_token';
        $row = $CACHE->read($cacheKey);
        if ($row) {
            $row = unserialize($row);
            if($row['access_token'] && strtotime($row['expiretime']) - 200 >= time() && !$force){
                return $row['access_token'];
            }
        }

        $res = $this->getAccessToken();
        $expire_time = time() + $res['expires_in'];
        $CACHE->save($cacheKey, ['access_token'=>$res['access_token'], 'expiretime'=>date("Y-m-d H:i:s", $expire_time)], $res['expires_in']);
        return $res['access_token'];
    }

    private function getAccessToken()
    {
        $url = "https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id={$this->apiKey}&client_secret={$this->secretKey}";
        $response = get_curl($url);
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            return $data;
        } elseif(isset($data['error_description'])) {
            throw new Exception('获取Access Token失败: ' . $data['error_description']);
        } else {
            throw new Exception('获取Access Token失败，未知错误');
        }
    }

    private static function formatDate($str){
        if(empty($str)) return '';
        return substr($str, 0, 4).'-'.substr($str, 4, 2).'-'.substr($str, 6, 2);
    }
}