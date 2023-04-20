package com.example.test3.filter;

import com.example.commons.tool.Constants;
import com.example.commons.tool.Result;
import com.example.test3.controller.RedisUtils;
import lombok.extern.slf4j.Slf4j;
import org.springframework.web.servlet.HandlerInterceptor;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.io.PrintWriter;

/**
 * 拦截器实现类
 */
@Slf4j
public class TestInterceptor implements HandlerInterceptor {

    private final RedisUtils redisUtils;

    public TestInterceptor(RedisUtils redisUtils) {
        this.redisUtils = redisUtils;
    }

    @Override
    public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) throws Exception {
        // response.setHeader("Access-Control-Allow-Methods", "OPTIONS, GET, POST");
        // response.setHeader("Access-Control-Allow-Headers", "x-requested-with");
        // response.setHeader("Access-Control-Max-Age", "86400");
        // response.setHeader("Access-Control-Allow-Origin", "*");

        String HeaderToken = request.getHeader(Constants.HEADER_TOKEN);

        if (HeaderToken != null) {
            Object token = redisUtils.get(HeaderToken);

            if (token == null) {
                log.info("============拦截器============登录超时");
                this.returnJson(response, new Result<>(401, "登录超时!").toJson());
                return false;
            } else {
                return true;
            }
        } else {
            log.info("============拦截器============无权访问");
            this.returnJson(response, new Result<>(404, "你无权访问!").toJson());
            return false;
        }

        // this.returnJson(response, "{\"code\":404,\"msg\":\"你无权访问!\"}");
        // return false;
        // throw new ThrowEntity(500, "服务器异常");
    }

    private void returnJson(HttpServletResponse response, String json) throws Exception{
        PrintWriter writer = null;
        response.setCharacterEncoding("UTF-8");
        response.setContentType("text/html; charset=utf-8");
        try {
            writer = response.getWriter();
            writer.print(json);
        } catch (IOException e) {
            log.error("拦截器返回json数据报错！", e);
        } finally {
            if (writer != null)
                writer.close();
        }
    }
}
