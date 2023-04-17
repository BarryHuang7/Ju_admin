package com.example.commons.entity;

import lombok.Data;

/**
 * 验证码
 */
@Data
public class VerifyCode {

    /**
     * 验证码编码
     */
    private String code;

    /**
     * 验证码字节流图片
     */
    private byte[] imgBytes;

    /**
     * 验证码过期时间
     */
    private long expireTime;

}
