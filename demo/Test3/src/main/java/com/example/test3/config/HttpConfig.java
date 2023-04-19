//package com.example.test3.controller;
//
//import org.springframework.context.annotation.Configuration;
//
//import javax.servlet.ServletException;
//import javax.servlet.http.HttpServlet;
//import javax.servlet.http.HttpServletRequest;
//import javax.servlet.http.HttpServletResponse;
//import java.io.IOException;
//
//@Configuration
//public class HttpConfig extends HttpServlet {
//
//    private static final long serialVersionUID = 1L;
//    @Override
//    protected void doOptions(HttpServletRequest arg0, HttpServletResponse arg1) throws ServletException, IOException {
//        System.out.println("这是一个坑");
//        arg1.setHeader("Access-Control-Allow-Methods", "OPTIONS, GET, POST");
//        arg1.setHeader("Access-Control-Allow-Headers", "x-requested-with");
//        arg1.setHeader("Access-Control-Max-Age", "86400");
////        arg1.setHeader("Access-Control-Allow-Origin", "*");
//        doPost(arg0, arg1);
//    }
//
//    @Override
//    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
//        req.setCharacterEncoding("UTF-8");
//        String name=(String) (req.getParameter("name"));
//        String action=(String) (req.getParameter("action"));
//        System.out.println(name);
//        System.out.println(action);
////        resp.setContentType("text/json; charset=utf-8");
////        resp.setHeader("Access-Control-Allow-Origin", "*");
////        resp.setHeader("Access-Control-Max-Age", "3600");
////        resp.setHeader("Access-Control-Allow-Headers", "x-requested-with");
////        resp.setHeader("Access-Control-Allow-Headers", "Content-Type, Accept");
////        resp.setHeader("Access-Control-Allow-Methods", "POST, GET, OPTIONS, DELETE");
//    }
//}
