<?php
namespace lib\ocr;

use Exception;

class AliyunOcrService implements OcrServiceInterface
{
	private $AccessKeyId;
	private $AccessKeySecret;
	private $Endpoint = 'ocr-api.cn-hangzhou.aliyuncs.com'; //API接入域名
	private $Version = '2021-07-07'; //API版本号

	function __construct($AccessKeyId, $AccessKeySecret){
		$this->AccessKeyId = $AccessKeyId;
		$this->AccessKeySecret = $AccessKeySecret;
	}
	
	//身份证正面识别
	public function idcard($file_path){
        try{
            $arr = $this->request('RecognizeIdcard', file_get_contents($file_path), true);
            $data = json_decode($arr['Data'],true);
            if(isset($data['data']['face'])){
                $arr = $data['data']['face']['data'];
                $result = ['id_no'=>$arr['idNumber'], 'name'=>$arr['name'], 'address'=>$arr['address'], 'sex'=>$arr['sex'], 'ethnicity'=>$arr['ethnicity'], 'birth_date'=>str_replace(['年','月','日'],['-','-',''],$arr['birthDate'])];
                return $result;
            }else{
                throw new Exception('请上传人像面照片');
            }
        }catch(Exception $e){
            throw new Exception('身份证识别失败，'.$e->getMessage());
        }
	}

    //身份证反面识别
	public function idcard_back($file_path){
        try{
            $arr = $this->request('RecognizeIdcard', file_get_contents($file_path), true);
            $data = json_decode($arr['Data'],true);
            if(isset($data['data']['back'])){
                $arr = $data['data']['back']['data'];
                $period = explode('-', $arr['validPeriod']);
                $result = ['issue_authority'=>$arr['issueAuthority'], 'period_begin'=>str_replace('.','-',$period[0]), 'period_end'=>str_replace('.','-',$period[1])];
                return $result;
            }else{
                throw new Exception('请上传国徽面照片');
            }
        }catch(Exception $e){
            throw new Exception('身份证识别失败，'.$e->getMessage());
        }
	}

	//护照识别
	public function passport($file_path){
        try{
            $arr = $this->request('RecognizePassport', file_get_contents($file_path), true);
            $data = json_decode($arr['Data'],true);
            if(isset($data['data'])){
                $arr = $data['data'];
                $result = ['passport_no'=>$arr['passportNumber'], 'passport_type'=>$arr['passportType'], 'last_name'=>$arr['surname'], 'first_name'=>$arr['givenName'], 'name'=>$arr['name'], 'sex'=>$arr['sex'], 'country'=>$arr['country'], 'birth_date'=>$arr['birthDateYmd'], 'period_begin'=>$arr['issueDateYmd'], 'period_end'=>$arr['validToDate']];
                return $result;
            }
        }catch(Exception $e){
            throw new Exception('护照识别失败，'.$e->getMessage());
        }
	}

	//银行卡识别
	public function bank_card($file_path){
		try{
            $arr = $this->request('RecognizeBankCard', file_get_contents($file_path), true);
		    $data = json_decode($arr['Data'],true);
            if(isset($data['data'])){
                $arr = $data['data'];
                $result = ['bank_name'=>$arr['bankName'], 'card_type'=>$arr['cardType'], 'card_no'=>$arr['cardNumber'], 'period_end'=>$arr['validToDate']];
                return $result;
            }
        }catch(Exception $e){
            throw new Exception('银行卡识别失败，'.$e->getMessage());
        }
	}

	//营业执照识别
	public function business_license($file_path){
        try{
            $arr = $this->request('RecognizeBusinessLicense', file_get_contents($file_path), true);
            $data = json_decode($arr['Data'],true);
            if(isset($data['data'])){
                $arr = $data['data'];
                $result = ['license_no'=>$arr['creditCode'], 'name'=>$arr['companyName'], 'address'=>$arr['businessAddress'], 'reg_date'=>str_replace(['年','月','日'], ['-','-',''], $arr['RegistrationDate']), 'legal_name'=>$arr['legalPerson'], 'type'=>$arr['companyType'], 'registered_capital'=>$arr['registeredCapital'], 'business_scope'=>$arr['businessScope'], 'period_begin'=>self::formatDate($arr['validFromDate']), 'period_end'=>empty($arr['validToDate'])?'长期':self::formatDate($arr['validToDate'])];
                return $result;
            }
        }catch(Exception $e){
            throw new Exception('营业执照识别失败，'.$e->getMessage());
        }
		
		return json_decode($arr['Data'],true);
	}

	//银行开户许可证识别
	public function bank_account_license($file_path){
        try{
            $arr = $this->request('RecognizeBankAccountLicense', file_get_contents($file_path), true);
            $data = json_decode($arr['Data'],true);
            if(isset($data['data'])){
                $arr = $data['data'];
                $result = ['bank_account'=>$arr['bankAccount'], 'legal_name'=>$arr['legalRepresentative'], 'bank_name'=>$arr['depositaryBank'], 'approval_no'=>$arr['approvalNumber'], 'name'=>$arr['customerName'], 'permit_no'=>$arr['permitNumber']];
                return $result;
            }
        }catch(Exception $e){
            throw new Exception('银行开户许可证识别失败，'.$e->getMessage());
        }
	}

    private static function formatDate($str){
        return substr($str, 0, 4).'-'.substr($str, 4, 2).'-'.substr($str, 6, 2);
    }

	//签名方法
	private function aliyunSignature($parameters, $accessKeySecret, $method)
	{
		ksort($parameters);
		$canonicalizedQueryString = '';
		foreach ($parameters as $key => $value) {
			if($value === null || $value instanceof \CURLFile) continue;
			$canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
		}
		$stringToSign = $method . '&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
		$signature = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&", true));

		return $signature;
	}
	private function percentEncode($str)
	{
		$search = ['+', '*', '%7E'];
		$replace = ['%20', '%2A', '~'];
		return str_replace($search, $replace, urlencode($str));
	}
	//请求方法（当需要返回列表等数据时，returnData=true）
	private function request($action, $file = null, $returnData=false){
		if(empty($this->AccessKeyId)||empty($this->AccessKeySecret))return false;
		$url='https://'.$this->Endpoint.'/';
		$data=array(
			'Action' => $action,
			'Format' => 'JSON',
			'Version' => $this->Version,
			'AccessKeyId' => $this->AccessKeyId,
			'SignatureMethod' => 'HMAC-SHA1',
			'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
			'SignatureVersion' => '1.0',
			'SignatureNonce' => $this->random(8));
		$data['Signature'] = $this->aliyunSignature($data, $this->AccessKeySecret, 'POST');
		$url.='?'.http_build_query($data);
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		if($file !== null){
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/octet-stream']);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $file);
		}
		$json=curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($httpCode>=200 && $httpCode<300){
			if($returnData==true){
				$arr = json_decode($json,true);
				if(!$arr) throw new Exception('无法解析返回数据');
				return $arr;
			}else{
				return true;
			}
		}else{
			$arr=json_decode($json,true);
			if(isset($arr['Code']) && isset($arr['Message'])){
				if(strpos($arr['Message'],' server string to sign is:')!==false){
					$arr['Message'] = substr($arr['Message'],0,strpos($arr['Message'],' server string to sign is:')-1);
				}
				throw new Exception('['.$arr['Code'].'] '.$arr['Message']);
			}else{
				throw new Exception('无法解析返回数据');
			}
		}
	}
	private function random($length, $numeric = 0) {
		$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $seed[mt_rand(0, $max)];
		}
		return $hash;
	}

}