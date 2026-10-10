package middlewares

// Response 统一响应对象
type Response struct {
	Code int         `json:"code"` // 状态码：200 成功，4xx 客户端错误，5xx 服务器错误
	Msg  string      `json:"msg"`  // 提示信息
	Data interface{} `json:"data"` // 返回的数据
}

// Success 成功响应
func Success(data interface{}) *Response {
	return &Response{
		Code: 200,
		Msg:  "success",
		Data: data,
	}
}

// Error 错误响应
func Error(code int, msg string) *Response {
	return &Response{
		Code: code,
		Msg:  msg,
		Data: nil,
	}
}

// Unauthorized 未授权
func Unauthorized() *Response {
	return &Response{
		Code: 401,
		Msg:  "未授权，请先登录",
		Data: nil,
	}
}

// Forbidden 禁止访问
func Forbidden() *Response {
	return &Response{
		Code: 403,
		Msg:  "禁止访问",
		Data: nil,
	}
}

// InternalError 服务器内部错误
func InternalError(msg string) *Response {
	return &Response{
		Code: 500,
		Msg:  msg,
		Data: nil,
	}
}
