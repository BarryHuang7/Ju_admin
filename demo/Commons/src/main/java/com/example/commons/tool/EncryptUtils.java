package com.example.commons.tool;

import lombok.extern.slf4j.Slf4j;

import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import java.io.UnsupportedEncodingException;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.util.Base64;

/**
 * 
 * <li>import java.nio.charset.StandardCharsets;
 * <li>import java.security.MessageDigest;
 * <li>import java.util.Base64;
 * 
 * 加密工具类
 */
@Slf4j
public class EncryptUtils {

    private static final String ENCODING = "UTF-8";
    private static final String MD5 = "MD5";
    private static final String AES = "AES";

    /**
     * MD5 Base64 加密
     *
     * @param str
     *            待加密的字符串
     * @return 加密后的字符串
     */
    public static String md5Base64(String str) {
        byte[] src = null;
        try {
            MessageDigest md5 = MessageDigest.getInstance(MD5);
            src = md5.digest(str.getBytes(StandardCharsets.UTF_8));
        } catch (Exception e) {
            throw new ThrowEntity(500, "服务器异常" + e);
        }
        return Base64.getEncoder().encodeToString(src);
    }

    /**
     * 计算摘要，返回32位的MD5值
     * 
     * @param str
     * @param encoding
     * @return
     */
    public static byte[] getMessageDigest(String str, String encoding) {
        try {
            MessageDigest digest = MessageDigest.getInstance(MD5);
            digest.update(str.getBytes(encoding));
            return digest.digest();

        } catch (NoSuchAlgorithmException e) {
            log.error(e.getMessage());
        } catch (UnsupportedEncodingException e) {
            log.error(e.getMessage());
        }
        return null;
    }

    public static byte[] getMessageDigest(String str) {
        return getMessageDigest(str, ENCODING);
    }

    private static String byte2HexString(byte[] b) {
        int i;
        StringBuffer buf = new StringBuffer("");
        for (int offset = 0; offset < b.length; offset++) {
            i = b[offset];
            if (i < 0)
                i += 256;
            if (i < 16)
                buf.append("0");
            buf.append(Integer.toHexString(i));
        }
        return buf.toString();

    }

    /**
     * md5加密
     * 
     * @param str
     *            需要加密的明文
     * @return md5 密码串
     */
    public static String encryptByMD5(String str) {
        return byte2HexString(getMessageDigest(str));
    }

    private static byte[] hex2Byte(String src) {
        if (src.length() < 1)
            return null;
        byte[] encrypted = new byte[src.length() / 2];
        for (int i = 0; i < src.length() / 2; i++) {
            int high = Integer.parseInt(src.substring(i * 2, i * 2 + 1), 16);
            int low = Integer.parseInt(src.substring(i * 2 + 1, i * 2 + 2), 16);

            encrypted[i] = (byte)(high * 16 + low);
        }
        return encrypted;
    }

    // 根据密钥串生成密钥对象
    public static SecretKey getSecretKey(String keyStr) {
        try {
            KeyGenerator keyGenerator = KeyGenerator.getInstance(AES);
            SecureRandom secureRandom = SecureRandom.getInstance("SHA1PRNG");
            secureRandom.setSeed(keyStr.getBytes(ENCODING));
            keyGenerator.init(128, secureRandom);
            return keyGenerator.generateKey();
        } catch (Exception e) {
            log.error(e.getMessage());
            return null;
        }
    }

    /**
     * 使用AES加密
     * 
     * @param str
     *            要加密的字符串明文
     * @param keyStr
     *            密钥
     * @return 加密后的字符串密文
     */
    public static String encryptByAES(String str, String keyStr) {
        SecretKey secretKey = getSecretKey(keyStr);
        return encryptByAES(str, secretKey);
    }

    /**
     * 使用AES解密
     * 
     * @param str
     *            要解密的字符串密文
     * @param keyStr
     *            密钥
     * @return 解密后的明文
     */
    public static String decryptByAES(String str, String keyStr) {
        SecretKey secretKey = getSecretKey(keyStr);
        return decryptByAES(str, secretKey);
    }

    /**
     * 使用AES加密
     * 
     * @param str
     *            要加密的字符串明文
     * @param secretKey
     *            密钥对象
     * @return 加密后的字符串密文
     */
    public static String encryptByAES(String str, SecretKey secretKey) {
        try {
            Cipher cipher = Cipher.getInstance(AES);
            cipher.init(Cipher.ENCRYPT_MODE, secretKey);
            byte[] result = cipher.doFinal(str.getBytes(ENCODING));
            return byte2HexString(result);
        } catch (Exception e) {
            log.error(e.getMessage());
            return "";
        }
    }

    /**
     * 使用AES解密
     * 
     * @param str
     *            要解密的字符串密文
     * @param secretKey
     *            密钥对象
     * @return 解密后的明文
     */
    public static String decryptByAES(String str, SecretKey secretKey) {
        try {
            Cipher cipher = Cipher.getInstance(AES);
            cipher.init(Cipher.DECRYPT_MODE, secretKey);
            byte[] result = cipher.doFinal(hex2Byte(str));
            return new String(result, ENCODING);
        } catch (Exception e) {
            log.error(e.getMessage());
            return "";
        }
    }
}
