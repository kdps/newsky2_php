<?php

namespace /NewSky2

class API
{

	private static $lgt;
	private static $lat;
	
	private static $locationArray = array();
	
	private static $sky = 0;
	private static $pty = 0;
	private static $t3h = 0;

	private static $weatherData;
	private static $dustData;
	private static $pmData;
	private static $pm25Data;
	private static $tmData;
	private static $tmData2;
	
	private static $weatherData_params;
	private static $dustData_params;
	private static $pmData_params;
	private static $pm25Data_params;
	private static $tmData_params;
	private static $tmData2_params;
	private static $closest_params;
	private static $weather_params;
	
	public function getPMStatus($value)
	{
		if ($value < 30) {
			return 1;
		} else if ($value < 80) {
			return 2;
		} else if ($value < 150) {
			return 3;
		} else if ($value >= 150) {
			return 4;
		}
	}
	
	public function getClosest($croodInfo)
	{
		$croodLocation = json_decode($croodInfo)->documents[0];
		
		$params = array(
			'tmX' => $croodLocation->x,
			'tmY' => $croodLocation->y,
			'ServiceKey' => self::$serviceKey,
			'_returnType' => "json"
		);
		
		$requestParameter = http_build_query($params);
		
		self::$closest_params = array(
			'parameter' => $params,
			'url' => urldecode("http://openapi.airkorea.or.kr/openapi/services/rest/MsrstnInfoInqireSvc/getNearbyMsrstnList?{$requestParameter}")
		);
		
		$getClosest = function () use ($requestParameter) {
			$ch = curl_init();
			$requestURL = urldecode("http://openapi.airkorea.or.kr/openapi/services/rest/MsrstnInfoInqireSvc/getNearbyMsrstnList?{$requestParameter}");
			
			curl_setopt($ch, CURLOPT_URL, $requestURL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				'Host: openapi.airkorea.or.kr'
			);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec ($ch);

			curl_close ($ch);
			
			return $result;
		};
		
		$data = $getClosest();
			
		return json_decode($data)->list[0]->stationName;
	}
	
	public function setFineDust( $stationName )
	{
		$params = array(
			'stationName' => $stationName,
			'dataTerm' => 'daily',
			'pageNo' => "1",
			'numOfRows' => "10",
			'ServiceKey' => self::$serviceKey,
			'ver' => '1.3',
			'_returnType' => 'json'
		);
		
		$requestParameter = http_build_query($params);
		
		self::$dustData_params = array(
			'parameter' => $params,
			'url' => urldecode("http://openapi.airkorea.or.kr/openapi/services/rest/ArpltnInforInqireSvc/getMsrstnAcctoRltmMesureDnsty?{$requestParameter}")
		);
		
		$setFineDust = function () use ($requestParameter) {
			$ch = curl_init();
			$requestURL = urldecode("http://openapi.airkorea.or.kr/openapi/services/rest/ArpltnInforInqireSvc/getMsrstnAcctoRltmMesureDnsty?{$requestParameter}");
			
			curl_setopt($ch, CURLOPT_URL, $requestURL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				'Host: openapi.airkorea.or.kr'
			);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec ($ch);

			curl_close ($ch);
			
			return $result;
		};
		
    $dustData = $setFineDust( $stationName );
		
		$dustArrayData = json_decode($dustData)->list;
		self::$dustData = $dustArrayData;
		$length = count($dustArrayData);
		
		self::$pmData = $dustArrayData[0]->pm10Value;
		self::$pm25Data = $dustArrayData[0]->pm25Value;
		
		$hasNotPmData = self::$pmData;
		$hasNotPm25Data = self::$pm25Data;
		
		if ($hasNotPmData) {
			if ($hasNotPm25Data) {
				self::$pmData = self::$pmData;
			} else {
				self::$pmData = self::$pm25Data;
			}
		}
		
		if ($hasNotPm25Data) {
			if ($hasNotPmData) {
				self::$pm25Data = self::$pm25Data;
			} else {
				self::$pm25Data = self::$pmData;
			}
		}
	}
	
	public function convertToTM()
	{
		$requestParameter = http_build_query(array_merge(
			array(
				'input_coord' => "WGS84",
				'output_coord' => "TM"
			), self::$locationArray)
		);
		
		$convertToTM = function () use ($requestParameter) {
			$requestURL = urldecode("http://dapi.kakao.com//v2/local/geo/transcoord.json?{$requestParameter}");
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $requestURL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				'Authorization: KakaoAK ',
				'Host: dapi.kakao.com'
			);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec ($ch);

			curl_close ($ch);
			
			return $result;
		};

		$data = $convertToTM();
		
		return $data;
	}
	
	public function getDate($x, $y) 
	{
		$baseTime = $this->getBaseTime();
		
		$params = array(
			'ServiceKey' => self::$serviceKey,
			'base_date'  => date("Ymd"),
			'base_time'  => $baseTime,
			'nx'         => $x,
			'ny'         => $y,
			'_type'      => 'json'
		);
		
		$requestParameter = http_build_query($params);
		
		self::$weather_params = array(
			'parameter' => $params,
			'url' => urldecode("http://newsky2.kma.go.kr/service/SecndSrtpdFrcstInfoService2/ForecastSpaceData?{$requestParameter}")
		);
		
		$getDate = function () use ($requestParameter) {
			$requestURL = urldecode("http://newsky2.kma.go.kr/service/SecndSrtpdFrcstInfoService2/ForecastSpaceData?{$requestParameter}");
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $requestURL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				'Host: newsky2.kma.go.kr'
			);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec ($ch);

			curl_close ($ch);
			
			return $result;
		};

		$data = $getDate();
			
		return $data
	}
	
	public function convertToAddress ()
	{
		$params = array_merge(
			array(
				'input_coord' => "WGS84"
			), self::$locationArray);
		
		$requestParameter = http_build_query($params);
		
		$convertToAddress = function () use ($requestParameter) {
			
			$requestURL = urldecode("http://dapi.kakao.com//v2/local/geo/coord2address.json?{$requestParameter}");
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $requestURL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				'Authorization: KakaoAK ',
				'Host: dapi.kakao.com'
			);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec ($ch);

			curl_close ($ch);
			
			return $result;
		};
    
		$data = $convertToAddress();
			
		$result = json_decode($data);
		
		self::$tmData = $result;
		
		self::$tmData_params = array(
			'parameter' => $params,
			'url' => urldecode("http://dapi.kakao.com//v2/local/geo/coord2address.json?{$requestParameter}")
		);
		
		return $result->documents[0]->address;
	}
	
	public function getBaseTime() 
	{
		$t = date('H');
		
		$ret = $t - ($t + 1) % 3;
		
		$return = str_pad($ret, 2, "0", STR_PAD_LEFT);
		
		return str_pad($return, 4, "0", STR_PAD_RIGHT);
	}

}
