package com.example.commons.tool;

public class ThrowEntity extends RuntimeException {
//    private static final long serialVersionUID = -8535710000964127898L;
    private int code;
    private String message;

    public ThrowEntity() {
        super();
        this.code = 404;
        this.message = "你无权访问!";
    }

    public ThrowEntity(int code, String message) {
        this.code = code;
        this.message = message;
    }

    public void setCode(int code) {
        this.code = code;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    public int getCode() {
        return this.code;
    }

    public String getMessage() {
        return this.message;
    }
}
