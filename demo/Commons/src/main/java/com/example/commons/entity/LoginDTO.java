package com.example.commons.entity;

import lombok.Data;

import java.io.Serializable;

@Data
public class LoginDTO implements Serializable {
    private Integer id;
    private String name;
    private String password;
    private String verifyCode;
}
