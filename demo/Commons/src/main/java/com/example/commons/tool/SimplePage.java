package com.example.commons.tool;

import javax.validation.constraints.NotNull;

public class SimplePage<T> {
    @NotNull(message = "当前页不能为空")
    private Integer pageIndex = 1;

    @NotNull(message = "页码大小不能为空")
    private Integer pageSize = 10;

    @NotNull(message = "参数不能为空")
    private T params;

    public SimplePage() {}

    public Integer getPageIndex() {
        return this.pageIndex;
    }

    public Integer getPageSize() {
        return this.pageSize;
    }

    public T getParams() {
        return this.params;
    }

    public void setPageIndex(Integer pageIndex) {
        this.pageIndex = pageIndex;
    }

    public void setPageSize(Integer pageSize) {
        this.pageSize = pageSize;
    }

    public void setParams(T params) {
        this.params = params;
    }
}
