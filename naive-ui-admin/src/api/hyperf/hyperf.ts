import { httpHyperf } from '@/utils/http/axios';

export function indexInfo() {
  return httpHyperf.request({
    url: '/getIndexInfo',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
