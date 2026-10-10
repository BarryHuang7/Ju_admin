package middlewares

import (
	"context"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
)

var client *redis.Client

// InitRedis 初始化 Redis 连接
func InitRedis(addr, password string) error {
	client = redis.NewClient(&redis.Options{
		Addr:         addr,
		Password:     password,
		DB:           0,
		PoolSize:     100,             // 连接池最大数量
		MinIdleConns: 8,               // 最小空闲连接数
		DialTimeout:  5 * time.Second, // 拨号超时
		ReadTimeout:  3 * time.Second, // 读超时
		WriteTimeout: 3 * time.Second, // 写超时
	})

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	// 测试连接
	if _, err := client.Ping(ctx).Result(); err != nil {
		CloseRedis()
		return fmt.Errorf("❌️ Redis 连接失败：%w", err)
	}

	fmt.Println("✅ Redis 连接成功！")
	return nil
}

// 检查 Redis 连接是否初始化
func CheckRedisConnection() error {
	if client == nil {
		return fmt.Errorf("redis 客户端未初始化")
	}
	return nil
}

// 获取值
func GetValue(ctx context.Context, token string) (string, error) {
	CheckRedisConnection()

	value, err := client.Get(ctx, token).Result()
	return value, err
}

// 设置值
func SetValue(ctx context.Context, key, value string, s int64) error {
	CheckRedisConnection()

	err := client.Set(ctx, key, value, time.Second*time.Duration(s)).Err()
	if err != nil {
		return fmt.Errorf("存储值失败：%v", err)
	}

	return nil
}

// CloseRedis 关闭 Redis 连接
func CloseRedis() {
	if client != nil {
		client.Close()
		fmt.Println("Redis 连接已关闭")
	}
}
