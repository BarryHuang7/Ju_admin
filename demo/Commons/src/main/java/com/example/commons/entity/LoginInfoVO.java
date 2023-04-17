package com.example.commons.entity;

import com.fasterxml.jackson.annotation.JsonFormat;
import lombok.Data;

import java.io.Serializable;

@Data
public class LoginInfoVO implements Serializable {
    private Integer id;
    private String name;
    private String isAdmin;
    private String token;
}
