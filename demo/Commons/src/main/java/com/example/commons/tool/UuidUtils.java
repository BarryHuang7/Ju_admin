
package com.example.commons.tool;

import java.util.UUID;

/**
 * uuid工具类
 */
public class UuidUtils {

    /**
     * 随机产生uuid
     * @return
     */
    public static String randomUUID() {
        return UUID.randomUUID().toString();
    }

}
