import { httpTwo } from '@/utils/http/axios';

export function getFileList(params) {
  return httpTwo.request({
    url: '/image',
    method: 'GET',
    params,
  });
}

export function toHttpByPHP(url, method, params = {}) {
  return httpTwo.request({ url, method, params });
}
