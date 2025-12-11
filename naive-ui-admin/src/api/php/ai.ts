import { httpTwo } from '@/utils/http/axios';

export function chatQWen(params) {
  return httpTwo.request({
    url: '/chatQWen',
    method: 'POST',
    params,
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
