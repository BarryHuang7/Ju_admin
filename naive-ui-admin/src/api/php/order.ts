import { httpTwo } from '@/utils/http/axios';

export function flashSale(params) {
  return httpTwo.request({
    url: '/flashSale',
    method: 'POST',
    params,
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
