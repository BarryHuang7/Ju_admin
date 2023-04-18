package com.example.commons.entity;

import lombok.Data;

import java.io.Serializable;
import java.util.ArrayList;

@Data
public class FileFileDeleteDTO implements Serializable {
    private ArrayList<Integer> fileIdList;
    private int isAdmin;
}
