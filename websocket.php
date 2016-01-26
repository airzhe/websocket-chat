<?php
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
					$redis->lpush('uid_list',$uid);
					$username = $data['username'];
					$redis->set($uid,$username);
					
					$user_list = get_online_users($redis);
					
					$response = [
						'cmd' =>'login',
						'data'=> [
							'new_user'  => $username,
							'user_list' => $user_list
						]
					];
					$response_data = json_encode($response_data);
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
	//		global $user;
			//unset($user[$fd]);
			echo "connection close: ".$fd."\n";
			});

	$serv->start();

	function get_online_users(&$redis){
		$user_id_list = $redis->lRange('uid_list',0,-1);	
		$user_list = $redis->$redis->getMultiple($user_id_list);
		return $user_list;
	}
} catch (Exception $e){
	print_r($e);
}

