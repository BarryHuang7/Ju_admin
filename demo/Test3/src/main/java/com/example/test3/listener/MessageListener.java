package com.example.test3.listener;

import com.example.commons.tool.Constants;
import com.example.commons.tool.Result;
import com.example.test3.controller.WebSocket;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.data.redis.connection.Message;
import org.springframework.data.redis.listener.KeyExpirationEventMessageListener;
import org.springframework.data.redis.listener.RedisMessageListenerContainer;
import org.springframework.stereotype.Component;

/**
 * redis过期监听实现类
 */
@Slf4j
@Component
public class MessageListener extends KeyExpirationEventMessageListener {

    @Autowired
    private WebSocket webSocket;

    public MessageListener(RedisMessageListenerContainer listenerContainer) {
        super(listenerContainer);
    }

    @Override
    public void onMessage(Message message, byte[] pattern) {
        String expiredKey = message.toString();

        if (expiredKey.startsWith(Constants.REDIS_WEBSOCKET_PREFIX)) {
            String userId = expiredKey.substring(Constants.REDIS_WEBSOCKET_PREFIX.length());
            log.info("给用户" + userId + "发送消息");
            String msg = new Result<>(200, "您的余额不足10元。").toJson();
            webSocket.sendMessage(userId, msg);
        }
    }
}
