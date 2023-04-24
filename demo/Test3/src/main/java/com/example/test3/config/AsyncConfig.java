package com.example.test3.config;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.scheduling.concurrent.ThreadPoolTaskExecutor;

import java.util.concurrent.Executor;
import java.util.concurrent.ThreadPoolExecutor;

@Configuration
public class AsyncConfig {

    @Bean("taskExecutor")
    public Executor taskFormExecutor() {
        // 此类由Spring提供，org.springframework.scheduling.concurrent包下，是线程池的封装类
        ThreadPoolTaskExecutor executor = new ThreadPoolTaskExecutor();
        // 线程池中线程的名字前缀
        executor.setThreadNamePrefix("asyncTaskPool-task-");
        // 线程池核心线程数量
        executor.setCorePoolSize(5);
        // 线程池最大线程数量
        executor.setMaxPoolSize(10);
        // 线程池空闲线程存活时间，单位秒
        executor.setKeepAliveSeconds(100);
        // 线程池拒绝策略
        executor.setRejectedExecutionHandler(new ThreadPoolExecutor.CallerRunsPolicy());
        // 线程池任务队容量，如果不设置则默认 Integer.MAX_VALUE，
        // 队列默认使用LinkedBlockingQueue 若queueCapacity的值 <= 0,则使用SynchronousQueue
        executor.setQueueCapacity(1000);

        // 线程池中核心线程是否允许超时，默认为false
        executor.setAllowCoreThreadTimeOut(true);

        // 线程池中的超时处理时间，单位秒，有一个对应方法为毫秒，默认为不超时
        executor.setAwaitTerminationSeconds(60);

        // 初始化线程池，不可以少，否者会抛出 线程池没有初始化
        executor.initialize();
        return executor;
    }
}
