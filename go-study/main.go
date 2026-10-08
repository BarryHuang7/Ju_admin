package main

/**
	初始化项目：go mod init 你的模块名
	安装依赖：go get github.com/gin-gonic/gin
	本地编译linux：$env:GOOS="linux"; $env:GOARCH="amd64"; go build -o goapp main.go
**/

import (
	"github.com/gin-gonic/gin"
	// "fmt"
)

func main() {
	r := gin.Default()

	r.GET("/", func(c *gin.Context) {
		c.JSON(200, gin.H{
			"code": 200,
			"msg":  "Hello, Go!",
			"data": gin.H{},
		})
	})

	r.Run(":6077")
}
