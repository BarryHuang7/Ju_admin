package com.example.test3.filter;

import com.example.test3.controller.RedisUtils;
import org.springframework.context.annotation.Configuration;
import org.springframework.http.converter.HttpMessageConverter;
import org.springframework.http.converter.StringHttpMessageConverter;
import org.springframework.web.servlet.config.annotation.InterceptorRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurationSupport;

import java.nio.charset.StandardCharsets;
import java.util.List;

@Configuration
public class TestConfig extends WebMvcConfigurationSupport {

    private final RedisUtils redisUtils;

    public TestConfig(RedisUtils redisUtils) {
        this.redisUtils = redisUtils;
    }

    @Override
    protected void addInterceptors(InterceptorRegistry registry) {
        registry.addInterceptor(new TestInterceptor(redisUtils))
                // 放行路径
                .excludePathPatterns("/login/verification", "/login/getVerificationCode")
                // 拦截路径
                .addPathPatterns("/**");
    }

    @Override
    protected void extendMessageConverters(List<HttpMessageConverter<?>> converters) {
        converters.stream()
                // 过滤出StringHttpMessageConverter类型实例
                .filter(StringHttpMessageConverter.class::isInstance)
                .map(c -> (StringHttpMessageConverter) c)
                // 这里将转换器的默认编码设置为utf-8
                .forEach(c -> c.setDefaultCharset(StandardCharsets.UTF_8));
    }
}
