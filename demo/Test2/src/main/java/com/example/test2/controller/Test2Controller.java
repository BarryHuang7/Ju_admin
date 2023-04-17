package com.example.test2.controller;

import com.example.commons.entity.Student;
import com.example.test2.service.ITest2Service;
import com.github.pagehelper.PageInfo;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
@RequestMapping("test2")
public class Test2Controller {

    private final ITest2Service iTest2Service;

    @PostMapping("/a")
    public String a() {
        System.out.println("test2 --- ok");
        return iTest2Service.hello();
    }

    @PostMapping("/b")
    public PageInfo<Student> b() {
        return iTest2Service.test();
    }
}
