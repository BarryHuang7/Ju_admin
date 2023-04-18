package com.example.test3.controller;

import com.example.commons.entity.FileFileDeleteDTO;
import com.example.commons.entity.FileList;
import com.example.commons.entity.LoginInfoVO;
import com.example.commons.entity.Student;
import com.example.commons.groups.Insert;
import com.example.commons.groups.Update;
import com.example.commons.tool.Result;
import com.example.commons.tool.SimplePage;
import com.example.test3.dao.TestDao;
import com.example.test3.service.ITestService;
import com.example.test3.service.impl.LoginService;
import com.github.pagehelper.PageHelper;
import com.github.pagehelper.PageInfo;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.validation.annotation.Validated;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.text.ParseException;
import java.util.*;

@RestController
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
@RequestMapping("h")
public class TestController {

    @Value("${student.a}")
    private String a;

    private final TestDao testDao;

    private final ITestService iTestService;
    private final LoginService loginService;

    private final RedisUtils redisUtils;

    @PostMapping("/t")
    public PageInfo<Student> test() {
        System.out.println("请求方法！post\n");
        System.out.println(a);

        PageHelper.startPage(1, 10);
        List<Student> b = testDao.studentList();
        return new PageInfo<>(b);
    }

    @PostMapping("/hello")
    public String hello() {
        return "你好！";
    }

    @PostMapping("/setRedis")
    public void setRedis() {
        redisUtils.set("123", "777", 50);
    }

    @PostMapping("/getRedis")
    public void getRedis() {
        System.out.println(redisUtils.get("123"));
    }

    @GetMapping("/hw")
    public String hw() {
        return "你好呀！我正在测试我的接口。偷偷改几个字看看会不会重启包？我再加呢？";
    }

    @PostMapping("/uploadFile")
    public Result<String> uploadFile(@RequestParam("file") MultipartFile file) throws ParseException {
        return iTestService.uploadFile(file);
    }

    @PostMapping("/insertFileListData")
    public Result<String> insertFileListData(@RequestBody @Validated({Insert.class}) FileList fileList) {
        return iTestService.insertFileListData(fileList);
    }

    @PostMapping("/getFileListData")
    public Result<PageInfo<FileList>> getFileListData(@RequestBody SimplePage<FileList> simplePage) {
        // 默认不让看数据
        int flag = 0;
        LoginInfoVO loginInfo = loginService.getUserInfo();
        if (loginInfo != null) {
            if (loginInfo.getIsAdmin() == 1 && loginInfo.getId() == 1) {
                flag = 1;
            }
        }
        simplePage.getParams().setIsAdmin(flag);

        PageHelper.startPage(simplePage.getPageIndex(), simplePage.getPageSize());
        return new Result<PageInfo<FileList>>().success(new PageInfo<>(testDao.getFileListData(simplePage.getParams())));
    }

    @PostMapping("/updateFileListData")
    public Result<String> updateFileListData(@RequestBody @Validated({Update.class}) FileList fileList) {
        return iTestService.updateFileListData(fileList);
    }

    @PostMapping("/deleteFileListData")
    public Result<String> deleteFileListData(@RequestBody FileFileDeleteDTO FileFileDeleteDTO) {
        return iTestService.deleteFileListData(FileFileDeleteDTO);
    }

    @PostMapping("/a")
    public void a() {
        System.out.println(loginService.getUserInfo());
    }
}
