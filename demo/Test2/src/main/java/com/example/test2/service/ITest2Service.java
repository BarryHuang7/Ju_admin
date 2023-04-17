package com.example.test2.service;

import com.example.commons.entity.Student;
import com.github.pagehelper.PageInfo;

public interface ITest2Service {
    String hello();

    PageInfo<Student> test();
}
