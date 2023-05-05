package com.example.test3.dao;

import com.example.commons.entity.*;
import org.apache.ibatis.annotations.Param;

import java.util.ArrayList;
import java.util.List;

public interface TestDao {
    List<Student> studentList();

    int insertFileListData(@Param("dto") FileList fileList);

    List<FileList> getFileListData(@Param("dto") FileList params);

    int updateFileListData(@Param("dto") FileList fileList);

    ArrayList<FileList> findFileListByIds(@Param("dto") FileFileDeleteDTO fileFileDeleteDTO);

    int deleteFileListData(@Param("list") ArrayList<Integer> fileIdList);

    GuestFileNumberVO getGuestFileNumber();

    VisitorNumberVO getVisitorNumber(String date);
}
