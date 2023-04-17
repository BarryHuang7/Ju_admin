package com.example.test2.service.impl;

import com.example.commons.entity.Student;
import com.example.test2.feign.ITestFeign;
import com.example.test2.service.ITest2Service;
import com.github.pagehelper.PageInfo;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

@Service
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
public class Test2Service implements ITest2Service {

    private final ITestFeign iTestFeign;

    @Override
    public String hello() {
        return iTestFeign.hello();
    }

    @Override
    public PageInfo<Student> test() {
        return iTestFeign.test();
    }
}
