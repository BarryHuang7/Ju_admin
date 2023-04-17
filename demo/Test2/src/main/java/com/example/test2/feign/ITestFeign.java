package com.example.test2.feign;

import com.example.commons.entity.Student;
import com.example.test2.controller.FeignConfig;
import com.github.pagehelper.PageInfo;
import org.springframework.cloud.openfeign.FeignClient;
import org.springframework.web.bind.annotation.PostMapping;

@FeignClient(name = "demo/h", configuration = FeignConfig.class)
public interface ITestFeign {
    @PostMapping("/hello")
    String hello();

    @PostMapping("/t")
    PageInfo<Student> test();
}
