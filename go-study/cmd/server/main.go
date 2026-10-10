package main

/**
	初始化项目：go mod init 你的模块名
	安装依赖：go get github.com/gin-gonic/gin
	本地编译 linux：$env:GOOS="linux"; $env:GOARCH="amd64"; go build -o goapp main.go
**/

import (
	"github.com/gin-gonic/gin"
	// 配置包
	"go-study/configs"
	// 中间件包
	"go-study/middlewares"
)

func main() {
	var httpPort string = "6077"
	var redisAddr string = "110.41.16.194"
	var redisPassword string = "151417redis"
	var redisPort string = "6379"

	// 初始化 Redis 连接
	if err := middlewares.InitRedis(redisAddr+":"+redisPort, redisPassword); err != nil {
		panic(err)
	}
	defer middlewares.CloseRedis()

	r := gin.Default()

	// 使用配置文件中的 Router 函数来注册所有路由
	configs.Router(r)

	// 端口启动
	r.Run(":" + httpPort)
}
