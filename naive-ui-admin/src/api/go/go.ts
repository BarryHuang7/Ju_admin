import { httpGo } from '@/utils/http/axios';

export function hello() {
  return httpGo.request({
    url: '/health',
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
