package com.example.test3.service;

import com.example.commons.entity.LoginDTO;
import com.example.commons.entity.LoginInfoVO;
import com.example.commons.tool.Result;

public interface ILoginService {
    Result<LoginInfoVO> verification(LoginDTO loginDTO);

    void getVerificationCode();

    Result<String> loginOut();
}
