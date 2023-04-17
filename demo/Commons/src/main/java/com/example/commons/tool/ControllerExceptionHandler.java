package com.example.commons.tool;

import com.example.commons.tool.ThrowEntity;
import org.springframework.http.HttpStatus;
import org.springframework.web.bind.annotation.ControllerAdvice;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.ResponseBody;
import org.springframework.web.bind.annotation.ResponseStatus;

import java.util.HashMap;
import java.util.Map;

@ControllerAdvice
public class ControllerExceptionHandler {
    @ExceptionHandler(ThrowEntity.class)
    @ResponseBody
    @ResponseStatus(HttpStatus.INTERNAL_SERVER_ERROR)
    public Map<String, Object> handleUserNotExistException(ThrowEntity ex)
    {
        Map<String, Object> result = new HashMap<>();
        result.put("code", ex.getCode());
        result.put("message", ex.getMessage());
        return result;
    }
}
