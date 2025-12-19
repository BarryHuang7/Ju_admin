import { httpTwo } from '@/utils/http/axios';

export function guestRecord() {
  return httpTwo.request({
    url: '/guestRecord',
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
