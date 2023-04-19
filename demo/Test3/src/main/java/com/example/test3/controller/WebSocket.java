package com.example.test3.controller;

import com.example.commons.tool.Result;
import lombok.Data;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Component;

import javax.websocket.*;
import javax.websocket.server.PathParam;
import javax.websocket.server.ServerEndpoint;
import java.io.IOException;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

/**
 * 长连接 WebSocket
 * 拦截器貌似不能拦截长连接
 */
@Component
@Data
@Slf4j
@ServerEndpoint(value = "/websocket/{userId}")
public class WebSocket {

    // 每次连接都是一个新的会话对象，线程安全的
    String userId;
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
    public void onOpen(@PathParam("userId") String userId, Session session, EndpointConfig config) {
        this.userId = userId;
        this.session = session;
        webSocketsBeanMap.put(this.userId, this);

        System.out.println(this.userId + "建立了连接，当前连接人数：" + webSocketsBeanMap.size() + "人。");
    }


    /**
     * 断开连接时调用
     */
    @OnClose
    public void onClose() {
        webSocketsBeanMap.remove(this.userId);
        System.out.println(this.userId + "断开了连接");
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
            e.printStackTrace();
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
        System.out.println("发生错误！");
        throwable.printStackTrace();
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
            e.printStackTrace();
        }
    }
}
