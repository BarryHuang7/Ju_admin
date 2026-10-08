import { httpGo } from '@/utils/http/axios';

export function hello() {
  return httpGo.request({
    url: '/',
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
