var socket = new WebSocket('ws://localhost:9503'); 

socket.onopen = function(event) { 

	// 发送一个初始化消息
	socket.send('I am the client and I\'m listening!'); 

	// 监听消息
	socket.onmessage = function(event) { 
		console.log('Client received a message',event); 
	};  

	// 监听Socket的关闭
	socket.onclose = function(event) { 
		console.log('Client notified socket has closed',event); 
	};  

	// 关闭Socket.... 
//	socket.close() 
};
