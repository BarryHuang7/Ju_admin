package com.example.commons.entity;

import com.example.commons.groups.Insert;
import com.example.commons.groups.Update;
import com.fasterxml.jackson.annotation.JsonFormat;
import lombok.Data;
import javax.validation.constraints.NotNull;

import java.io.Serializable;
import java.util.Date;

@Data
public class FileList implements Serializable {
    @NotNull(message = "id不能为空", groups = { Update.class})
    private int id;
    private String title;
    private String content;
    private String fileName;
    @NotNull(message = "图片链接不能为空", groups = {Insert.class, Update.class })
    private String fileUrl;
    @JsonFormat(pattern = "yyyy-MM-dd HH:mm:ss", timezone = "GMT+8")
    private Date fileDate;
    private int isAdmin;
    @JsonFormat(pattern = "yyyy-MM-dd HH:mm:ss", timezone = "GMT+8")
    private Date createdAt;
    @JsonFormat(pattern = "yyyy-MM-dd HH:mm:ss", timezone = "GMT+8")
    private Date updatedAt;
}
