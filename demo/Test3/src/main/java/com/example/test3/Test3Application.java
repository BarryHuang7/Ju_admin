package com.example.test3;

import org.mybatis.spring.annotation.MapperScan;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.cloud.netflix.eureka.EnableEurekaClient;
import org.springframework.scheduling.annotation.EnableAsync;
//import org.springframework.scheduling.annotation.EnableScheduling;

@SpringBootApplication
// mybatis
@MapperScan(basePackages = {"com.example.test3.dao", "com.example.test3.entity"})
// 定时任务
// @EnableScheduling
// 微服务
@EnableEurekaClient
// 异步
@EnableAsync
public class Test3Application {

    public static void main(String[] args) {
        SpringApplication.run(Test3Application.class, args);
    }

}
