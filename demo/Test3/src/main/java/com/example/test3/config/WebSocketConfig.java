package com.example.test3.config;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.socket.config.annotation.EnableWebSocket;
//import org.springframework.web.socket.config.annotation.WebSocketConfigurer;
//import org.springframework.web.socket.config.annotation.WebSocketHandlerRegistry;
import org.springframework.web.socket.server.standard.ServerEndpointExporter;
//import org.springframework.web.socket.server.standard.ServletServerContainerFactoryBean;

/**
 * 长连接 WebSocket配置类，用于开启 WebSocket支持
 */
@Configuration
@EnableWebSocket
public class WebSocketConfig {
    // implements WebSocketConfigurer
    @Bean
    public ServerEndpointExporter serverEndpointExporter(){
        return new ServerEndpointExporter();
    }

//    @Override
//    public void registerWebSocketHandlers(WebSocketHandlerRegistry registry) {
//
//    }
//
//    @Bean
//    public ServletServerContainerFactoryBean createWebSocketContainer() {
//        ServletServerContainerFactoryBean container = new ServletServerContainerFactoryBean();
//
//        container.setMaxTextMessageBufferSize(10240);
//        container.setMaxBinaryMessageBufferSize(10240);
//        container.setMaxSessionIdleTimeout(5 * 1000L);
//        return container;
//    }
}
