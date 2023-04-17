package com.example.test3.service.impl;

import com.example.commons.entity.FileFileDeleteDTO;
import com.example.commons.entity.FileList;
import com.example.commons.tool.Result;
import com.example.test3.dao.TestDao;
import com.example.test3.service.ITestService;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.transaction.interceptor.TransactionAspectSupport;
import org.springframework.web.multipart.MultipartFile;

import java.io.File;
import java.text.SimpleDateFormat;
import java.util.*;

@Service
@Transactional(readOnly = false, rollbackFor = Exception.class)
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
public class TestService implements ITestService {

    private final TestDao testDao;
    // 格式化时间
    private SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMddHHmmss");
    // 存放文件的路径
    private String filePath = "/home/file";

    // 事务回滚
    // TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();

    /**
     * 上传文件
     * @param file
     * @return
     */
    @Override
    public Result<String> uploadFile(MultipartFile file) {
        if (!file.isEmpty()) {
            try {
                // 获取文件上传名称
                String originalFileName = file.getOriginalFilename();
                // 文件名
                String fileName = originalFileName.substring(0, originalFileName.indexOf("."));
                // 文件后缀
                String fileSuffix = originalFileName.substring(fileName.length() + 1);
                // 时间前缀
                Date date = new Date();
                String formatDate = sdf.format(date);
                // 本地
                // String systemDir = System.getProperty("user.dir");
                // String filePath = systemDir + "\\home\\file";
                // 服务器
                String newPath = filePath + "/" + fileName + formatDate + "." + fileSuffix;

                // 判断文件夹存不存在
                File isExist = new File(filePath);
                if (!isExist.exists()) {
                    isExist.mkdirs();
                }

                // 创建文件
                file.transferTo(new File(newPath));
                return new Result<String>().success(fileName + formatDate + "." + fileSuffix);
            } catch (Exception e) {
                TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
                return new Result<String>().fail("try catch!" + e);
            }
        }
        return new Result<String>().fail("上传文件失败！");
    }

    /**
     * 新增文件表数据
     * @return
     */
    @Override
    public Result<String> insertFileListData(FileList fileList) {
        try {
            if(testDao.insertFileListData(fileList) == 1) {
                return new Result<String>().success("保存成功！");
            }
        } catch (Exception e) {
            TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
            return new Result<String>().fail("try catch!" + e);
        }
        return new Result<String>().fail("保存失败!");
    }

    /**
     * 编辑文件表数据
     * @return
     */
    @Override
    public Result<String> updateFileListData(FileList fileList) {
        try {
            fileList.setUpdatedAt(new Date());
            if(testDao.updateFileListData(fileList) == 1) {
                return new Result<String>().success("编辑成功！");
            }
        } catch (Exception e) {
            TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
            return new Result<String>().fail("try catch!" + e);
        }
        return new Result<String>().fail("编辑失败!");
    }

    /**
     * 批量删除文件表数据
     * @param fileFileDeleteDTO
     * @return
     */
    @Override
    public Result<String> deleteFileListData(FileFileDeleteDTO fileFileDeleteDTO) {
        try {
            if (fileFileDeleteDTO.getFileIdList() == null) {
                return new Result<String>().fail("删除失败!未传参数！");
            }
            ArrayList<FileList> fileList = testDao.findFileListByIds(fileFileDeleteDTO.getFileIdList());

            // 删除文件
            for(FileList item : fileList) {
                String fileName = item.getFileUrl().substring(33);
                // 本地
                // File file = new File(System.getProperty("user.dir") + "\\home\\\\file\\" + fileName);
                // 服务器
                File file = new File(filePath + "/" + fileName);
                boolean flag = false;
                // 路径是个文件且不为空时删除文件
                if(file.isFile() && file.exists()){
                    flag = file.delete();
                }
                if (!flag) {
                    TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
                    return new Result<String>().fail("文件删除失败!");
                }
            }
            // 删除数据库
            if(testDao.deleteFileListData(fileFileDeleteDTO.getFileIdList()) != fileFileDeleteDTO.getFileIdList().size()) {
                TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
                return new Result<String>().fail("删除数据库失败!");
            }
        } catch (Exception e) {
            TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
            return new Result<String>().fail("try catch!" + e);
        }
        return new Result<String>().success("删除成功！");
    }
}
