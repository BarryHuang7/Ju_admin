package middlewares

import (
	"encoding/json"
	"net/http"

	"github.com/gin-gonic/gin"
)

// UserInfo 用户信息结构体
type UserInfo struct {
	UserID   int    `json:"userID"`
	UserName string `json:"userName"`
	IsAdmin  int    `json:"isAdmin"`
}

// AuthMiddleware 登录验证中间件 - 需要登录的接口才会放行
func AuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// 获取 Authorization 头部
		authHeader := c.GetHeader("Authorization")

		// 没有 Token，返回未授权
		if authHeader == "" {
			c.JSON(http.StatusUnauthorized, gin.H{
				"code": 401,
				"msg":  "未授权，请先登录",
				"data": nil,
			})
			c.Abort() // 中断后续处理，结束本次请求
			return
		}

		token, err := GetValue(c.Request.Context(), authHeader)

		// Token 错误或过期，返回禁止访问
		if token == "" || err != nil {
			c.JSON(http.StatusUnauthorized, gin.H{
				"code": 401,
				"msg":  "Token 无效或已过期",
				"data": nil,
			})
			c.Abort()
			return
		}

		// 将 JSON 字符串转换为 UserInfo 结构体
		var user UserInfo
		err = json.Unmarshal([]byte(token), &user)
		if err != nil {
			c.JSON(http.StatusBadRequest, Error(400, "用户信息解析失败"))
			c.Abort()
			return
		}

		// 将用户信息存入上下文
		c.Set("userId", user.UserID)
		c.Set("userName", user.UserName)
		c.Set("isAdmin", user.IsAdmin)

		// 放行请求，继续后续处理
		c.Next()
	}
}

// Recovery 恢复中间件 - 防止程序崩溃时返回错误信息给前端
func Recovery() gin.HandlerFunc {
	return func(c *gin.Context) {
		defer func() {
			if err := recover(); err != nil {
				c.JSON(http.StatusInternalServerError, InternalError("服务器内部错误"))
			}
		}()

		c.Next()
	}
}
