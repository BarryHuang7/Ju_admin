package com.example.test3.controller;

import com.example.commons.entity.LoginInfoVO;
import com.example.commons.entity.UserSendMessage;
import com.example.commons.tool.Result;
import lombok.Data;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Component;

import javax.websocket.*;
import javax.websocket.server.PathParam;
import javax.websocket.server.ServerEndpoint;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

/**
 * 长连接 WebSocket
 * 拦截器貌似不能拦截长连接
 */
@Component
@Data
@Slf4j
@ServerEndpoint(value = "/websocket/{userId}/{userName}")
public class WebSocket {

    // 每次连接都是一个新的会话对象，线程安全的
    private String userId;
    private String userName;
    // 与某个客户端的连接会话，通过此会话对象给客户端发送数据
    private Session session;

    // concurrent包的线程安全Set，用来存放每个客户端对应的WebSocket对象。
    // 注：泛型是当前类名
    // private static Set<WsMessageService> webSockets = new CopyOnWriteArraySet<>();
    private static Map<String, WebSocket> webSocketsBeanMap = new ConcurrentHashMap<>();
    // 用来保存在线连接数
    // private static Map<String, Session> sessionPool = new HashMap<>();

    /**
     * 建立连接时调用
     *
     * @param userId
     * @param session
     * @param config
     */
    @OnOpen
    public void onOpen(@PathParam("userId") String userId, @PathParam("userName") String userName, Session session, EndpointConfig config) {
        this.userId = userId;
        this.userName = userName;
        this.session = session;
        webSocketsBeanMap.put(this.userId, this);

        log.info(this.userName + "建立了连接，当前连接人数：" + webSocketsBeanMap.size() + "人。");
    }


    /**
     * 断开连接时调用
     */
    @OnClose
    public void onClose() {
        webSocketsBeanMap.remove(this.userId);
        log.info(this.userName + "断开了连接");
    }


    /**
     * 消息到达时调用
     *
     * @param message
     */
    @OnMessage
    public void onMessage(String message) {
        try {
            // this.session.getBasicRemote().sendText(message);
            String msg = new Result<>(200, "你输入了：" + message).toJson();
            this.session.getBasicRemote().sendText(msg);
        } catch (IOException e) {
            log.error("消息达到报错！", e);
        }
    }


    /**
     * 发生错误时调用
     *
     * @param session
     * @param throwable
     */
    @OnError
    public void onError(Session session, Throwable throwable) {
        log.error(session + "：该session报错！", throwable);
    }

    /**
     * 给指定用户发送消息
     * @param userId
     * @param message
     */
    public void sendMessage(String userId, String message) {
        try {
            if (message != null && !message.equals("")) {
                WebSocket user = webSocketsBeanMap.get(userId);

                if (user != null) {
                    Session userSession = user.session;

                    if (userSession != null && userSession.isOpen()) {
                        userSession.getBasicRemote().sendText(message);
                    }
                }
            }
        } catch (IOException e) {
            log.error("发送消息报错！", e);
        }
    }

    /**
     * 获取所有在线人信息
     */
    public ArrayList<UserSendMessage> getAllOnlineUser(String sendUserId) {
        ArrayList<UserSendMessage> list = new ArrayList<>();
        for (Map.Entry<String, WebSocket> item : webSocketsBeanMap.entrySet()) {
            String key = item.getKey();
            WebSocket value = item.getValue();

            // 排除自己
            if (!key.equals(sendUserId)) {
                UserSendMessage user = new UserSendMessage();

                user.setUserId(key);
                user.setUserName(value.getUserName());

                list.add(user);
            }
        }
        return list;
    }
}
