import { httpTwo } from '@/utils/http/axios';

export function sendEmail(params) {
  return httpTwo.request({
    url: '/sendEmail',
    method: 'POST',
    params,
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
