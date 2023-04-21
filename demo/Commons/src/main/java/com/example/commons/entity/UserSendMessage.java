package com.example.commons.entity;

import com.example.commons.groups.Groups;
import lombok.Data;

import javax.validation.constraints.NotNull;
import java.io.Serializable;

@Data
public class UserSendMessage implements Serializable {
    /**
     * 接收者id
     */
    @NotNull(message = "接收者id不能为空", groups = { Groups.A.class })
    private String userId;
    /**
     * 接收者名称
     */
    @NotNull(message = "接收者名称不能为空", groups = { Groups.A.class })
    private String userName;
    /**
     * 发送者id
     */
    @NotNull(message = "发送者id不能为空", groups = { Groups.A.class })
    private String sendUserId;
    /**
     * 发送者名称
     */
    @NotNull(message = "发送者名称不能为空", groups = { Groups.A.class })
    private String sendUserName;
    /**
     * 消息内容
     */
    @NotNull(message = "消息内容不能为空", groups = { Groups.A.class })
    private String message;
}
