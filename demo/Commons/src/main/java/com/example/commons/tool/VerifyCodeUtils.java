package com.example.commons.tool;

import com.example.commons.entity.VerifyCode;

import javax.imageio.ImageIO;
import java.awt.*;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.OutputStream;
import java.util.Random;

public class VerifyCodeUtils {
    private static final String[] FONT_TYPES =
            {"\u5b8b\u4f53", "\u65b0\u5b8b\u4f53", "\u9ed1\u4f53", "\u6977\u4f53", "\u96b6\u4e66"};

    private static final int VALICATE_CODE_LENGTH = 4;

    /**
     * 设置背景颜色及大小，干扰线
     * @param graphics 绘图
     * @param width 宽度
     * @param height 高度
     */
    private static void fillBackground(Graphics graphics, int width, int height) {
        // 填充背景
        graphics.setColor(Color.WHITE);
        // 设置矩形坐标x y 为0
        graphics.fillRect(0, 0, width, height);
        // 加入干扰线条
        for (int i = 0; i < 8; i++) {
            // 设置随机颜色算法参数
            graphics.setColor(RandomUtils.randomColor(40, 150));
            Random random = new Random();
            int x = random.nextInt(width);
            int y = random.nextInt(height);
            int x1 = random.nextInt(width);
            int y1 = random.nextInt(height);
            graphics.drawLine(x, y, x1, y1);
        }
    }

    /**
     * 设置字符颜色大小
     * @param g 绘图
     * @param randomStr 随机数
     */
    private static void createCharacter(Graphics g, String randomStr) {
        char[] charArray = randomStr.toCharArray();
        for (int i = 0; i < charArray.length; i++) {
            // 设置RGB颜色算法参数
            g.setColor(
                    new Color(50 + RandomUtils.nextInt(100), 50 + RandomUtils.nextInt(100), 50 + RandomUtils.nextInt(100)));
            // 设置字体大小，类型
            g.setFont(new Font(FONT_TYPES[RandomUtils.nextInt(FONT_TYPES.length)], Font.BOLD, 26));
            // 设置x y 坐标
            g.drawString(String.valueOf(charArray[i]), 15 * i + 5, 19 + RandomUtils.nextInt(8));
        }
    }

    /**
     * 生成验证码并返回code，将图片写在os中
     * @param width 宽度
     * @param height 高度
     * @param os 输出流
     * @return
     */
    private static String generateCode(int width, int height, OutputStream os) {
        BufferedImage image = new BufferedImage(width, height, BufferedImage.TYPE_INT_RGB);
        Graphics graphics = image.getGraphics();
        fillBackground(graphics, width, height);
        String verifyCode = RandomUtils.randomNumberString(VALICATE_CODE_LENGTH);
        createCharacter(graphics, verifyCode);
        graphics.dispose();
        // 设置JPEG格式
        try {
            ImageIO.write(image, "JPEG", os);
        } catch (IOException e) {
            verifyCode = null;
        }
        return verifyCode;
    }

    /**
     * 生成验证码
     * @param width 宽度
     * @param height 高度
     * @return
     */
    public static VerifyCode generateVerifyCode(int width, int height) throws IOException {
        VerifyCode verifyCode = null;
        ByteArrayOutputStream baos = null;
        try {
            baos = new ByteArrayOutputStream();
            String code = generateCode(width, height, baos);
            verifyCode = new VerifyCode();
            verifyCode.setCode(code);
            verifyCode.setImgBytes(baos.toByteArray());
        } finally {
            baos.close();
        }
        return verifyCode;
    }
}
