package com.example.test3.controller;

import com.example.commons.entity.*;
import com.example.commons.groups.Groups;
import com.example.commons.groups.Insert;
import com.example.commons.groups.Update;
import com.example.commons.tool.Constants;
import com.example.commons.tool.Result;
import com.example.commons.tool.SimplePage;
import com.example.test3.dao.TestDao;
import com.example.test3.service.ITestService;
import com.example.test3.service.impl.LoginService;
import com.github.pagehelper.PageHelper;
import com.github.pagehelper.PageInfo;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.validation.annotation.Validated;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.text.ParseException;
import java.util.*;

// 日志
// @Slf4j
@RestController
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
@RequestMapping("h")
public class TestController {

    @Value("${student.a}")
    private String a;

    private final TestDao testDao;

    private final ITestService iTestService;
    private final LoginService loginService;
    private final WebSocket webSocket;

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

    /**
     * 发送定时消息
     */
    @PostMapping("/sendTimingMessage")
    public void sendTimingMessage() {
        String userId = loginService.getUserId();
        String key = Constants.REDIS_WEBSOCKET_PREFIX + userId;
        if (!redisUtils.hasKey(key)) {
            redisUtils.set(key, "", 5);
        }
    }

    /**
     * 获取所有在线人信息
     */
    @GetMapping("/getAllOnlineUser")
    public ArrayList<UserSendMessage> getAllOnlineUser() {
        String userId = loginService.getUserId();
        return webSocket.getAllOnlineUser(userId);
    }

    /**
     * 发送消息
     * @param user
     */
    @PostMapping("/sendMessage")
    public Result<String> sendMessage(@RequestBody @Validated({Groups.A.class}) UserSendMessage user) {
        String data = "{\"userId\":\"" + user.getUserId()
                + "\",\"userName\":\"" + user.getUserName()
                + "\",\"sendUserId\":\"" + user.getSendUserId()
                + "\",\"sendUserName\":\"" + user.getSendUserName()
                + "\",\"message\":\"" + user.getMessage()
                + "\"}";
        String msg = "{\"code\":200,\"data\":" + data + "}";
        webSocket.sendMessage(user.getUserId(), msg);
        return new Result<String>().success("发送成功！");
    }
}
