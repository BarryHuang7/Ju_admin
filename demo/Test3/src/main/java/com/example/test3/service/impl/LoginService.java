package com.example.test3.service.impl;

import com.example.commons.entity.LoginDTO;
import com.example.commons.entity.LoginInfoVO;
import com.example.commons.entity.VerifyCode;
import com.example.commons.tool.*;
import com.example.test3.controller.RedisUtils;
import com.example.test3.dao.LoginDao;
import com.example.test3.service.ILoginService;
import io.jsonwebtoken.JwtBuilder;
import io.jsonwebtoken.Jwts;
import io.jsonwebtoken.SignatureAlgorithm;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import javax.crypto.spec.SecretKeySpec;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import java.security.Key;
import java.util.*;

@Slf4j
@Service
@Transactional(readOnly = false, rollbackFor = Exception.class)
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
public class LoginService implements ILoginService {

    private final LoginDao loginDao;
    private final RedisUtils redisUtils;

    /**
     * 登录验证
     * @param loginDTO 登录信息
     * @return
     */
    @Override
    public Result<LoginInfoVO> verification(LoginDTO loginDTO) {
        if (loginDTO.getName() == null) {
            return new Result<LoginInfoVO>().fail("账户名不能为空！");
        }
        if (loginDTO.getPassword() == null) {
            return new Result<LoginInfoVO>().fail("密码不能为空！");
        }

        // 请求上下文
        HttpServletRequest request = SpringContextUtils.getRequest();
        if (request != null) {
            // 验证码的uuid
            String verifyCodeUUID = request.getHeader("VerifyCode");
            if (verifyCodeUUID == null) {
                return new Result<LoginInfoVO>().fail("验证码不能为空！");
            }
            // 存在redis的验证码
            Object verifyCode = redisUtils.get(verifyCodeUUID);
            // 拿到立刻删除
            redisUtils.del(verifyCodeUUID);
            if (verifyCode == null || !verifyCode.equals(loginDTO.getVerifyCode())) {
                return new Result<LoginInfoVO>().fail("验证码不正确或超时，请重试！");
            }

            // md5验证密码
            loginDTO.setPassword(EncryptUtils.md5Base64(loginDTO.getPassword()));
            LoginInfoVO loginInfo = loginDao.verification(loginDTO);

            if (loginInfo != null) {
                // token
                String token = this.getToken(loginInfo);
                loginInfo.setToken(token);
                // 保存redis，2小时过期
                redisUtils.set(Constants.TOKEN_PREFIX + token, loginInfo.getId(), 60 * 60 * 2);

                return new Result<LoginInfoVO>().success(loginInfo);
            }
            return new Result<LoginInfoVO>().fail("账户名或密码不存在！");
        }
        return new Result<LoginInfoVO>().fail("获取请求上下文失败！");
    }

    /**
     * 获取登录验证码
     */
    @Override
    public void getVerificationCode() {
        try {
            HttpServletResponse response = SpringContextUtils.getResponse();
            HttpServletRequest request = SpringContextUtils.getRequest();

            if (request != null && response != null) {
                // 头部的code
                String HeaderVerifyCode = request.getHeader("VerifyCode");
                // 生成的code
                VerifyCode verifyCode = VerifyCodeUtils.generateVerifyCode(80, 30);
                // 验证码随机数
                String uuid = UuidUtils.randomUUID();
                // 第一次获取验证码，否者覆盖之前的redis
                if (HeaderVerifyCode != null) {
                    uuid = HeaderVerifyCode;
                }
                // 保存redis，1分钟过期
                redisUtils.set(uuid, verifyCode.getCode(), 60);

                // 设置响应头
                response.setHeader("Pragma", "no-cache");
                response.setHeader("VerifyCode", uuid);
                // 暴露自定义的头部
                response.setHeader("Access-Control-Expose-Headers", "VerifyCode");
                response.setHeader("Cache-Control", "no-cache");
                // 在代理服务器端防止缓冲
                response.setDateHeader("Expires", 0);
                // 设置响应内容类型
                response.setContentType("image/jpeg");
                response.getOutputStream().write(verifyCode.getImgBytes());
                response.getOutputStream().flush();
            }
        } catch (Exception e) {
            log.error("获取登录验证码报错！", e);
        }
    }

    /**
     * 退出登录
     * @return
     */
    @Override
    public Result<String> loginOut() {
        String token = this.getHeaderToken();

        if (!token.equals("")) {
            redisUtils.del(token);
            return new Result<String>().success("退出登录成功！");
        }

        return new Result<String>().fail("退出登录失败！");
    }

    /**
     * 获取请求头部token
     * @return
     */
    public String getHeaderToken() {
        String token = "";
        HttpServletRequest request = SpringContextUtils.getRequest();

        if (request != null) {
            String headerToken = request.getHeader(Constants.HEADER_TOKEN);
            if (headerToken != null) {
                if (redisUtils.hasKey(headerToken)) {
                    token = headerToken;
                }
            }
        }
        return token;
    }

    /**
     * 获取登录token
     * @return
     */
    public String getToken(LoginInfoVO loginInfo) {
        Key key = new SecretKeySpec(UuidUtils.randomUUID().getBytes(), "HmacSHA512");

        Map<String, Object> map = new HashMap<>();
        map.put("id", loginInfo.getId());
        map.put("name", loginInfo.getName());

        JwtBuilder builder = Jwts.builder()
                // 获取签名秘钥，并采用HS256的加密算法进行提签名
                .signWith(key, SignatureAlgorithm.HS256)
                // jwt唯一标识
                .setId(UUID.randomUUID().toString())
                // 设置数据内容
                .setClaims(map)
                // 设置签发人
                .setIssuer("admin")
                // 主题
                .setSubject("JWT AUTH")
                // 签发时间
                .setIssuedAt(new Date());
        return builder.compact();
    }

    /**
     * 获取当前登陆者的用户id
     */
    public String getUserId() {
        String userId = "0";

        String token = this.getHeaderToken();

        if (!token.equals("")) {
            Object id = redisUtils.get(token);
            if (id != null) {
                userId = id.toString();
            }
        }

        return userId;
    }

    /**
     * 获取当前登陆者的用户信息
     */
    public LoginInfoVO getUserInfo() {
        return loginDao.findUserById(Integer.parseInt(this.getUserId()));
    }
}
