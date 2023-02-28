<?php 
/*****************************\
# API: Ghost.az				  |
# Author: İsmayıl İsayev	  |
******************************/

function search_injection(&$data) {
	$array_request = array('union', 'mysql', 'select ', 'insert ', 'delete ', 'truncate', 'optimize', 'values', 'from', 'where', 'or%', '--', 'and%', '\'', '../');
	$noaccess = array(
		'msg', 'message', 'mod', 'city', 'infa', 'avtootvetm', 'name', 'pass', 'go', 
	);
	
	foreach($data as $_ind => $_val) {
		if (!in_array($_ind, $noaccess)) {
			foreach($array_request as $search) {
				if (strpos(urldecode($_val), $search) !== false) {
					unset($data[$_ind], $GLOBALS[$_ind]);
				}
				++$i;
			}
		}
	}
}
search_injection($_GET);
search_injection($_POST);
search_injection($_COOKIE);


class security_service
{

	var $attack;
	var $refreshLimit;
	function __construct( $url = null, $deleteTime = '-86400' ){
		global $_GET,$_POST,$_COOKIE;
		if( $deleteTime > 0 ) {
			die('$deleteTimeOut 0-dan boyuk olmamalidi misal -3600 (1 saat)');
		}
		
		$temp = $this->logFolder(DOCUMENT_ROOT."tmp");
		$this->temp = $temp.'/'.md5(getenv('DOCUMENT_ROOT'));
		
		if (!is_dir($this->temp)) {
			if(!mkdir($this->temp, 0777, TRUE)){
				die($this->temp.' - yaratmaq mumkun deyil. #1');
			}
		}
		
		if( $url ) {
			$this->logdir = $this->logFolder(DOCUMENT_ROOT."tmp/".$url, 2);
		}
		else {
			$this->logdir = $this->temp .'/log';
			if (!is_dir($this->logdir)) {
				if(!mkdir($this->logdir, 0777, TRUE)){
					die($this->logdir.' - yaratmaq mumkun deyil. #2');
				}
			}
		}
		if( !$this->Attack() ) {
			$this->clearTemps($deleteTime);
			search_injection($_GET);
			search_injection($_POST);
			search_injection($_COOKIE);
		}
	}
	
	function Attack(){
		global $id;
		


		$result = null;
		$urlFile = $this->logdir .'/'. getenv('REMOTE_ADDR');
		if( file_exists($urlFile) ) {
			$result = json_decode(file_get_contents( $urlFile ), true);
		}
		if( !$result || $result['timeout'] < time() ) {
			$urlFile = $this->logdir .'/'. $id;
			if( file_exists($urlFile) ) {
				$result = json_decode(file_get_contents( $urlFile ), true);
			}
		}
		
		if( $result['timeout'] > time() ) {
			$this->attack = $result;
			$this->save($this->logdir .'/'. getenv('REMOTE_ADDR').'.size', '.');
			if(file_exists($this->logdir .'/'. getenv('REMOTE_ADDR').'.size')) {
				$filesize = @filesize($this->logdir .'/'. getenv('REMOTE_ADDR').'.size');
				if( $filesize < 200 ) {
					$_SERVER['POST'] = print_r($_POST, true);
					$this->save($this->logdir .'/'. getenv('REMOTE_ADDR').'.log', print_r($_SERVER, true));
				} else if( $filesize < 201 ) {
					
					// get ddos info to ghost.az
					 $this->connect(array(
						 'url' 		=> 'http://ghost.az/ddosUpdate.php',
						 'nobody'	=> true,
						 'timeout'	=> 5,
						 'data' 		=> array(
							 'file'			=> '@'.$this->logdir .'/'. getenv('REMOTE_ADDR').'.log',
							 'host'			=> getenv('HTTP_HOST'),
							 'request_uri'	=> getenv('REQUEST_URI'),
							 'attack_ip' 	=> getenv('REMOTE_ADDR'),
							 'attack_browser'=> getenv('HTTP_USER_AGENT'),
							 'security'		=> md5('ghost:security')
						 )
					 ));
				}
			}
			return true;
		}



	}
	
	// function cron(  ) {
		
	// }
	
	function connect( $data = array() ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $data['url']);
		if( $data['nobody'] ) {
			curl_setopt($ch , CURLOPT_NOBODY , 1 );
		}
		if( $data['timeout'] ) {
			curl_setopt( $ch , CURLOPT_TIMEOUT , $data['timeout'] );
		}
		if( sizeof($data['data']) > 0 ) {
			curl_setopt($ch , CURLOPT_POST , true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data['data']);
		}
		$response = curl_exec($ch);
	}
	
	function save( $file, $save, $type = 'a' )
	{
		$file = @fopen( $file, $type );
		@flock($file, LOCK_EX);
		@fwrite($file, $save."\n");
		@flock($file, LOCK_UN);
		@fclose($file);
	}
	
	
	function timeout( $str )
	{
		$str -= time();
		if ( $str < 60 && $str >= 0 ) {
			return $str . ' saniyyÉ™';
		}
		else if ( $str <= 3600 && $str >= 60 ) {
			$clock = $str / 60;
			list( $minute , $secunds ) = preg_split('/\./', $clock, -1, PREG_SPLIT_NO_EMPTY);
			$clock = $minute . ' dÉ™qiqÉ™';
			if($secunds) {
				$secunds = $str - ($minute * 60);
				if($secunds > 0) {
					$clock .= ', '.$secunds . ' saniyÉ™';
				}
			}
			return $clock;
		}
	}
	
	
	function logFolder( $f, $i=1 ){
		if ( !is_dir($f) ) {
			die($f.' - papka tapilmadi.. #1-'.$i);
		}
		else if( decoct(fileperms($f) & 0777) != 777 ) {
			die($f.' - oxumaq olmur. #2-'.$i);
		}
		return $f;
	}
	
	function clearTemps( $deleteTime ){
		if ( is_file($this->temp.'/date') ) {
			
			$night = null;
			$timeout = 600;
			if(in_array(date('H'), array('02','04'))) {
				$night = true;
				$timeout = 1800;
			}
			
			if( time() - $timeout > filemtime($this->temp.'/date') ) {
				$this->rm($this->logdir, $deleteTime);
				if( $night ) {
					$this->rm($this->temp, -86400);
				}
				$this->rm($this->temp.'/row', -120);
				$this->rm($this->temp.'/ips', -120);
				file_put_contents($this->temp.'/date', time());
			}
		}
		else {
			file_put_contents($this->temp.'/date', time());
		}
	}

	function refreshLimit($i) {
		$this->refreshLimit = intval($i);
	}
	
	function variables_counter() {
		global $row, $A_OPERA;
		
		if( is_numeric($row['time']) && getmicrotime() - $row['time'] > 1 && sizeof($_POST) == 0 ) {
			return false;
		}

		$limit = array(
			'get'	=> array('secunds' => 10, 'count' => 8),
			'post'	=> array('secunds' => 25, 'count' => 10)
		);
		
		if( is_numeric($row['time']) && getmicrotime() - $row['time'] <= 1 ) {
			$limit['get']['started'] = true;
		}
		else if( !is_numeric($row['time']) ){
			$limit['get']['started'] = true;
			$limit['get']['count'] = 20;
		}
		
		if( $this->refreshLimit ) {
			$limit['get']['count'] += $this->refreshLimit;
		}
		
		if( !$row['id'] ) {
			$file = $this->temp .'/ips';
			if( !is_dir($file) ) {
				mkdir($file, 0777);
			}
			$file .= '/'. getenv('REMOTE_ADDR');
		}
		else {
			
			$file = $this->temp .'/row';
			if( !is_dir($file) ) {
				mkdir($file, 0777);
			}
			$i = 0;
			$fsize = 2;
			
			$file .= '/'.substr(strrev($row['id']), 0, 2);
			if( !is_dir($file) ) {
				mkdir($file, 0777);
			}
			$file = $file .'/'. $row['id'];
		}
		
		$prefix = ($row['id'] ? $row['id'] : getenv('REMOTE_ADDR'));
		$post = $this->logdir .'/'. $prefix;
		
		if( $row['id'] != '1' ) {
			if( file_exists($file) ) {
				$serial = json_decode(file_get_contents($file), true);

				if( $serial['postTime'] > time() && (strlen($_POST['msg']) > 0 || strlen($_POST['message']) > 0) ) {
					if( $serial['postCount'] > $limit['post']['count'] ) {
						file_put_contents($post, json_encode(array('timeout' => time() + 600, 'type' => '2')));
						exit;
					}
					$serial['postCount']++;
				}
				else if( $serial['postTime'] <= time() ) {
					$serial['postTime'] = time() + $limit['post']['secunds'];
					$serial['postCount'] = 1;
				}
				
				if( $limit['get']['started'] ) {
					if( $serial['getTime'] > time() && $serial['getCount'] > $limit['get']['count']) {
						file_put_contents($post, json_encode(array('timeout' => time() + 300, 'type' => '1')));
						exit;
					}
					else if( $serial['getTime'] <= time() ) {
						$serial['getTime'] = time() + $limit['get']['secunds'];
						$serial['getCount'] = 0;
					}
				}

				$serial['getCount']++;
				file_put_contents($file, json_encode($serial));
			}
			else {
				$serial = array(
					'getTime' => time() + 5,
					'getCount' => 1,
					'postTime' => time() + 25,
					'postCount' => 1
				);
				file_put_contents($file, json_encode($serial));
			}
		}
	}
	
	function _wget($u, $f, $t){
		$n = $this->temp.'/'.md5($u).'.cache';
		if(file_exists($n) && @filemtime($n) > time()-$t){
			return unserialize(file_get_contents($n));
		} else {
			if($j = $f($u)){
				file_put_contents($n, serialize($j));
				chmod($n, 0777);
				return $j;
			}
		}
	}
	function file($u, $t=300){
		return $this->_wget($u, 'file', $t);
	}
	function file_get_contents($u, $t=300){
		return $this->_wget($u, 'file_get_contents', $t);
	}
	
	function set($key, $str = null, $timeout = 300){
		$file = $this->temp.'/'.md5($key);
		file_put_contents($file.'.date', time() + intval($timeout));
		file_put_contents($file.'.cache', $str);
		chmod($file.'.date', 0777);
		chmod($file.'.cache', 0777);
	}
	
	function get( $key ){
		$file = $this->temp.'/'.md5($key);
		$date = @file_get_contents($file.'.date');
		if( $date > time() ) {
			return @file_get_contents($file.'.cache');
		}
	}
	
	function rm( $directory, $timeout = null, $r = null ){
		global $count_delete;
		$isFile = 0;
		$dir = opendir($directory);
		while(($file = readdir($dir)))
		{
			if ( is_file ($directory . '/' . $file) && (!$timeout || time() + $timeout > filemtime($directory . '/' . $file)) ) {
				@unlink( $directory . '/' . $file );
				$count_delete++;
			}
			else if ( is_dir($directory . '/' . $file) && ($file != '.') && ($file != '..') ) {
				$this->rm( $directory . '/' . $file, $timeout, true );
			}
			else if( ($file != '.') && ($file != '..') ){
				$isFile++;
			}
		}
		closedir ($dir);
		if( $r && !$isFile ) {
			rmdir($directory);
		}
	}

}
$low_access = null;

 $security_service = new security_service('68214e92d79de6227577224906106491');
 $security_service->refreshLimit(120);