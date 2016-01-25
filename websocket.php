<?php
$serv = new swoole_websocket_server("127.0.0.1", 9503);

static $user = [];

$serv->on('Open', function($server, $req) {
		echo "connection open: ".$req->fd."\n";
		});

$serv->on('Message', function($server, $frame) {
		global $user;
		global $serv;
		global $i;
		$data = json_decode($frame->data,true);
			
		print_r($user);

		switch(array_keys($data)[0]){
			case 'login':
				$user[$frame->fd] = current($data);
				$new_user = [
					'new_user' => ['name'=>$data['login'],'id'=>$frame->fd],
					'user_list' => $user
				];
				$json_new_user  = json_encode($new_user);
				//通知新用户登录
				foreach($serv->connections as $fd){
					echo $fd."\n";
					$serv->push($fd, $json_new_user);
				}

				break;
			case 'sendMsg':
				break;
		}
		});

$serv->on('Close', function($server, $fd) {
		global $user;
		//unset($user[$fd]);
		echo "connection close: ".$fd."\n";
		});

$serv->start();

