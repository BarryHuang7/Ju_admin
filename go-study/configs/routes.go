package configs

/**
	同一个包下的函数可以直接调用，不需要 import
**/

import (
	// 中间件包
	"go-study/middlewares"

	"github.com/gin-gonic/gin"
)

// Router 路由处理器函数，接收 gin.Engine 并配置所有路由
func Router(r *gin.Engine) {
	// 使用中间件组（全局中间件）
	r.Use(
		middlewares.Recovery(), // 恢复中间件
	)

	r.Use(middlewares.AuthMiddleware()) // 所有子路由都需要登录
	{
		// 检查路由健康
		r.GET("/health", func(c *gin.Context) {
			c.JSON(200, middlewares.Success(gin.H{
				"userId":   c.GetInt("userId"),
				"userName": c.GetString("userName"),
				"isAdmin":  c.GetInt("isAdmin"),
				"message":  "Hello, Go!",
			}))
		})
	}
}
