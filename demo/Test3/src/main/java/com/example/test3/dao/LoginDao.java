package com.example.test3.dao;

import com.example.commons.entity.LoginDTO;
import com.example.commons.entity.LoginInfo;
import com.example.commons.entity.LoginInfoVO;
import org.apache.ibatis.annotations.Param;

public interface LoginDao {
    LoginInfoVO verification(@Param("dto") LoginDTO loginDTO);

    LoginInfoVO findUserById(Integer id);

    int insertVisitorInfo(@Param("dto") LoginInfo loginInfo);
}
