package com.example.test3.dao;


import com.example.commons.entity.FileFileDeleteDTO;
import com.example.commons.entity.FileList;
import com.example.commons.entity.Student;
import org.apache.ibatis.annotations.Param;

import java.util.ArrayList;
import java.util.List;

public interface TestDao {
    List<Student> studentList();

    int insertFileListData(@Param("dto") FileList fileList);

    List<FileList> getFileListData(@Param("dto") FileList params);

    int updateFileListData(@Param("dto") FileList fileList);

    ArrayList<FileList> findFileListByIds(@Param("list") ArrayList<Integer> fileIdList);

    int deleteFileListData(@Param("list") ArrayList<Integer> fileIdList);
}
