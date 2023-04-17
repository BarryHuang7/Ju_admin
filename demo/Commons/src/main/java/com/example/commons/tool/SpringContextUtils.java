package com.example.commons.tool;

import org.springframework.beans.BeansException;
import org.springframework.context.ApplicationContext;
import org.springframework.context.ApplicationContextAware;
import org.springframework.stereotype.Component;
import org.springframework.web.context.request.RequestContextHolder;
import org.springframework.web.context.request.ServletRequestAttributes;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import java.util.Map;

/**
 * 获取SpringContext上下文
 */
@Component
public class SpringContextUtils implements ApplicationContextAware {

    private static ApplicationContext app;

    private SpringContextUtils() {}

    @Override
    public void setApplicationContext(ApplicationContext applicationContext) throws BeansException {
        app = applicationContext;
    }

    public static ApplicationContext app() {
        return app;
    }

    public static Object getBean(Class<?> requiredType) {
        return app.getBean(requiredType);
    }

    public static Object getBean(String beanName) {
        return app.getBean(beanName);
    }

    public static <T> Map<String, T> getBeansOfType(Class<T> clazz) {
        return app.getBeansOfType(clazz);
    }

    public static HttpServletRequest getRequest() {
        ServletRequestAttributes servletRequestAttributes =
            (ServletRequestAttributes)RequestContextHolder.getRequestAttributes();
        if (servletRequestAttributes == null) {
            return null;
        }
        RequestContextHolder.setRequestAttributes(servletRequestAttributes, true);
        return servletRequestAttributes.getRequest();
    }

    public static HttpServletResponse getResponse() {
        ServletRequestAttributes servletRequestAttributes =
            (ServletRequestAttributes)RequestContextHolder.getRequestAttributes();
        if (servletRequestAttributes == null) {
            return null;
        }
        return servletRequestAttributes.getResponse();
    }
}
