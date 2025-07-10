### 启动项目必须的 

安装 
~~~
composer require nexophp/boot
~~~

路由

~~~
location / {
  if (!-e $request_filename){
    rewrite ^(.*)$ /index.php last;
  }
}
~~~

