package com.example.test3.controller;

import com.example.commons.entity.LoginDTO;
import com.example.commons.entity.LoginInfoVO;
import com.example.commons.tool.Result;
import com.example.test3.service.ILoginService;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
@RequestMapping("login")
public class LoginController {

    private final ILoginService iLoginService;

    @PostMapping("/verification")
    public Result<LoginInfoVO> verification(@RequestBody LoginDTO loginDTO) {
        return iLoginService.verification(loginDTO);
    }

    @GetMapping("/loginOut")
    public Result<String> loginOut() {
        return iLoginService.loginOut();
    }

    @GetMapping("/getVerificationCode")
    public void getVerificationCode() {
        iLoginService.getVerificationCode();
    }
}
