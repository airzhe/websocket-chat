<?php

function get_online_users(&$redis){
	$user_id_list = $redis->lRange('uid_list',0,-1);	
	foreach($user_id_list as $uid){
		$user_list[$uid] = $redis->get('uid_'.$uid);
	}
	return $user_list;
}

try{
	$serv	= new swoole_websocket_server("127.0.0.1", 9503);
	$redis	= new Redis();
	$redis->connect('127.0.0.1',6379);


	$serv->on('Open', function($server, $req) {
		echo "connection open: ".$req->fd."\n";
	});

	$serv->on('Message', function($server, $frame) {
			global $redis;
			global $serv;
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
					break;
				case 'set_config':
					break;
			}
			});

	$serv->on('Close', function($server, $fd) {
		//log out
		global $redis;
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
		foreach($serv->connections as $fd){
			$serv->push($fd, $response_data);
		}   

	});

	$serv->start();

	
} catch (Exception $e){
	print_r($e);
}

