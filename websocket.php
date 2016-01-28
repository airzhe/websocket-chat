<?php

#redis key 
/*
 * list  uid_list 在线用户id列表 
 * uid_* string   用户id对应用户名
 * 
 */

function get_online_users(&$redis){
	$user_list = [];
	$user_id_list = $redis->lRange('uid_list',0,-1);	
	foreach($user_id_list as $uid){
		$user_list[$uid] = $redis->get('uid_'.$uid);
	}
	return $user_list;
}

function flush_data(&$redis){
	$redis->delete('uid_list');
	echo 'flusth_data';
}

try{
	$serv	= new swoole_websocket_server("127.0.0.1", 9503);
	$redis	= new Redis();
	$redis->connect('127.0.0.1',6379);


	$serv->on('Open', function($server, $req) {
		echo "connection open: ".$req->fd."\n";
	});

	$serv->on('Message', function($server, $frame) use($redis,$serv) {

			$request = json_decode($frame->data,true);

			print_r($request);

			$cmd  = $request['cmd'];
			$data = $request['data'];

			switch($cmd){
				case 'login':
					$uid = 'uid_'.$frame->fd;
					$redis->lpush('uid_list',$frame->fd);
					$username = $data['username'];
					$redis->set($uid,$username);
					
					$user_list = get_online_users($redis);
					
					$response = [
						'cmd' =>'login',
						'data'=> [
							'user'  => $username,
							'user_list' => $user_list
						]
					];
					$response_data = json_encode($response);
					//通知新用户登录
					foreach($serv->connections as $fd){
						$serv->push($fd, $response_data);
					}

					break;
				case 'send_msg':
					$username	= $data['from'];
					$send_to	= $data['send_to'];
					$content	= $data['content'];
					$color		= $data['color'] ?? '';
					$font_size	= $data['font_size'] ?? ''; 

					$response = [
						'data' => [
							'from'		=> $username,
							'content'	=> $content,
							'color'		=> $color,
							'font_size' => $font_size
						]
					];
					echo 888;
					//to all
					if($send_to == 0){
						$response['cmd'] = 'send_to_all';	
						$response_data = json_encode($response);
						foreach($serv->connections as $fd){
							$serv->push($fd, $response_data);
						}
					}else{
						$response['cmd'] = 'send_to_me';	
						$response_data = json_encode($response);
						$serv->push($send_to, $response_data);
					}
					break;
				case 'setconfig':
					$response = [
						'cmd'  => 'setconfig',
						'data' => [
							'bg_img' => $data['bg_img']
						]
					];
					$response_data = json_encode($response);
					foreach($serv->connections as $fd){
						$serv->push($fd, $response_data);
					}
					break;
				}
			});

	$serv->on('Close', function($server, $fd) use($redis,$serv) {
		//所有用户退出,清空数据			
		echo 'logout--'.$serv->connections->count();
		if($serv->connections->count() == 1){
			flush_data($redis);	
			return;
		};
		//log out
		$username = $redis->get('uid_'.$fd);	
		$redis->lRem('uid_list',$fd,1);
		$redis->delete('uid_',$fd);
		$user_list = get_online_users($redis);

		$response = [ 
			'cmd' =>'logout',
			'data'=> [
				'user'  => $username,
				'user_list' => $user_list
			]   
		];  
		$response_data = json_encode($response);
		//通知新用户登录
		foreach($serv->connections as $send_to){
			if($send_to != $fd){
				$serv->push($send_to, $response_data);
			}
		}  
	});

	$serv->start();

	
} catch (Exception $e){
	print_r($e);
}

