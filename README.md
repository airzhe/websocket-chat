##  聊天室
使用 php 7.0.2 + [swoole-1.8.0-rc2](http://www.swoole.com/) + angular + redis 开发的聊天室

### 服务端
```
php websocket.php 

```
### 客户端

```
var socket = new WebSocket('ws://IP:port'); 
```
####数据格式

####登录
```
{'cmd':'login','data':{'username':'runner'}}
```

####发送信息
```
{'cmd':'send_msg','data':{'from':0,'to':1,'conteng':'你好!','font_size':14,'color':'#314342'}}
```

#### 服务端回复

####登录
```
{'cmd':'login','data':{'user':'runner','user_list'=>{0=>'runner',1=>'purple',5=>'testuser'}}}
```

####发送消息
```
{'cmd':'send_msg','data':{'from':0,'to':1,'conteng':'你好!','font_size':14,'color':'#314342'}}
```

####退出
```
{'cmd':'logout','data':{'user':'runner','user_list'=>{0=>'runner',1=>'purple',5=>'testuser'}}}
```
