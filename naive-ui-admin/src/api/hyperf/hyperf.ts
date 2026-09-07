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

export function generateByHyperf() {
  return httpHyperf.request({
    url: '/generateStock',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function flashSaleProductsByHyperf() {
  return httpHyperf.request({
    url: '/flashSaleProducts',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
