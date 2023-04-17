package com.example.commons.tool;

import lombok.Data;
import org.springframework.lang.Nullable;

import java.io.Serializable;
import java.util.Optional;

@Data
public class Result<T> implements Serializable {
    private static final long serialVersionUID = 1L;

    private static final int SUCCESS_CODE = 200;

    /**
     * 返回编码
     */
    private int code;

    /**
     * 返回消息
     */
    private String message;

    /**
     * 返回数据
     */
    private T data;

    public Result() {
        super();
    }

    public Result(T data) {
        success();
        this.data = data;
    }

    public Result(int code, String message) {
        this.code = code;
        this.message = message;
    }

    public Result(int code, String message, T data) {
        this.code = code;
        this.message = message;
        this.data = data;
    }

    public String toJson() {
        return "{\"code\":" + this.code + ",\"message\":\"" + this.message + "\"}";
    }

    /**
     * success
     */
    public Result<T> success() {
        this.code = 200;
        this.message = "Success!";
        return new Result<T>(code, message);
    }

    /**
     * success
     */
    public Result<T> success(T data) {
        this.code = 200;
        this.message = "Success!";
        this.data = data;
        return new Result<T>(code, message, data);
    }

    /**
     * fail
     */
    public Result<T> fail() {
        this.code = 500;
        this.message = "Fail!";
        return new Result<T>(code, message);
    }

    /**
     * fail
     */
    public Result<T> fail(String message, T data) {
        this.code = 500;
        this.message = message;
        this.data = data;
        return new Result<T>(code, message, data);
    }

    /**
     * fail
     */
    public Result<T> fail(String message) {
        this.code = 500;
        this.message = message;
        return new Result<T>(code, message, null);
    }

    /**
     * fail
     */
    public Result<T> fail(int code, String message) {
        this.code = code;
        this.message = message;
        return new Result<T>(code, message, null);
    }

    public static boolean isSuccess(@Nullable Result<?> result) {
        return Optional.ofNullable(result).map((r) ->
                SUCCESS_CODE == r.getCode()
        ).orElse(Boolean.FALSE);
    }

    public static <T> Result<T> resultFail(String msg) {
        return new Result(500, msg);
    }

    public static <T> Result<T> resultFail(String msg, T data) {
        return new Result(500,msg, data);
    }

    public static <T> Result<T> resultSuccess() {
        return new Result(200);
    }


    public static boolean isNotSuccess(@Nullable Result<?> result) {
        return !isSuccess(result);
    }
}
