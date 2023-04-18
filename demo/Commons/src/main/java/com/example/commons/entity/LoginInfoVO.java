package com.example.commons.entity;

import lombok.Data;

import java.io.Serializable;

@Data
public class LoginInfoVO implements Serializable {
    private Integer id;
    private String name;
    private Integer isAdmin;
    private String token;
}
