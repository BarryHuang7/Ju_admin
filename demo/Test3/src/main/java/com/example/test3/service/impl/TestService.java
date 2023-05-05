package com.example.test3.service.impl;

import com.example.commons.entity.*;
import com.example.commons.tool.Constants;
import com.example.commons.tool.Result;
import com.example.test3.dao.TestDao;
import com.example.test3.service.ITestService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.scheduling.annotation.Async;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.transaction.interceptor.TransactionAspectSupport;
import org.springframework.web.multipart.MultipartFile;

import java.io.File;
import java.text.SimpleDateFormat;
import java.util.*;

@Slf4j
@Service
@Transactional(readOnly = false, rollbackFor = Exception.class)
@RequiredArgsConstructor(onConstructor = @__(@Autowired))
public class TestService implements ITestService {

    private final LoginService loginService;
    private final TestDao testDao;
    // 格式化时间
    private SimpleDateFormat sdf_datetime = new SimpleDateFormat("yyyyMMddHHmmss");
    private SimpleDateFormat sdf_date = new SimpleDateFormat("yyyy-MM-dd");
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
        // 非管理员的权限限制
        LoginInfoVO loginInfo = loginService.getUserInfo();
        if (loginInfo != null) {
            if (loginInfo.getIsAdmin() != 1) {
                if(this.getGuestFileNumber() >= Constants.GUEST_MAX_UPLOAD) {
                    return new Result<String>().fail("已达到你权限的最大上传数！");
                }
            }
        }

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
                String formatDate = sdf_datetime.format(date);
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
                log.error("上传文件报错！", e);
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
            // 非管理员的权限限制
            LoginInfoVO loginInfo = loginService.getUserInfo();
            if (loginInfo != null) {
                if (loginInfo.getIsAdmin() != 1) {
                    if(this.getGuestFileNumber() >= Constants.GUEST_MAX_UPLOAD) {
                        return new Result<String>().fail("已达到你权限的最大上传数！");
                    }
                }
                fileList.setIsAdmin(loginInfo.getIsAdmin());
            }

            if(testDao.insertFileListData(fileList) == 1) {
                return new Result<String>().success("保存成功！");
            }
        } catch (Exception e) {
            TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
            log.error("新增文件表数据报错！", e);
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
            // 权限限制
            LoginInfoVO loginInfo = loginService.getUserInfo();
            if (loginInfo != null) {
                fileList.setIsAdmin(loginInfo.getIsAdmin());
            }

            fileList.setUpdatedAt(new Date());
            if(testDao.updateFileListData(fileList) == 1) {
                return new Result<String>().success("编辑成功！");
            }
        } catch (Exception e) {
            TransactionAspectSupport.currentTransactionStatus().setRollbackOnly();
            log.error("编辑文件表数据报错！", e);
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

            // 权限限制
            LoginInfoVO loginInfo = loginService.getUserInfo();
            if (loginInfo != null) {
                fileFileDeleteDTO.setIsAdmin(loginInfo.getIsAdmin());
            }

            ArrayList<FileList> fileList = testDao.findFileListByIds(fileFileDeleteDTO);

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
            log.error("批量删除文件表数据报错！", e);
            return new Result<String>().fail("try catch!" + e);
        }
        return new Result<String>().success("删除成功！");
    }

    /**
     * 查询文件表非管理员传了多少个文件
     */
    public int getGuestFileNumber() {
        GuestFileNumberVO guestFileNumber = testDao.getGuestFileNumber();
        return guestFileNumber.getNumber();
    }

    @Async("taskExecutor")
    @Override
    public void async() {
        // 异步任务如果用this调用，spring就会找不到代理类而是this那个类本身
        System.out.println("async() = " + Thread.currentThread().getName());
        System.out.println("异步！！！");
    }

    @Override
    public Result<VisitorNumberVO> getVisitorNumber() {
        Date date = new Date();
        String formatDate = sdf_date.format(date);
        VisitorNumberVO vn = testDao.getVisitorNumber(formatDate);
        return new Result<VisitorNumberVO>().success(vn);
    }
}
