package com.example.test3.service;

import com.example.commons.entity.FileFileDeleteDTO;
import com.example.commons.entity.FileList;
import com.example.commons.entity.VisitorNumberVO;
import com.example.commons.tool.Result;
import org.springframework.web.multipart.MultipartFile;

import java.text.ParseException;

public interface ITestService {
    Result<String> uploadFile(MultipartFile file) throws ParseException;

    Result<String> insertFileListData(FileList fileList);

    Result<String> updateFileListData(FileList fileList);

    Result<String> deleteFileListData(FileFileDeleteDTO fileFileDeleteDTO);

    void async();

    Result<VisitorNumberVO> getVisitorNumber();
}
