import { http } from '@/utils/http/axios';
import { httpTwo } from '@/utils/http/axios';

//获取table
export function getTableList(params) {
  return http.request({
    url: '/table/list',
    method: 'get',
    params,
  });
}

export function getFileList(params) {
  return http.request({
    url: '/h/getFileListData',
    method: 'POST',
    params,
  });
}

export function toHttp(url, method, params) {
  return http.request({ url, method, params });
}
