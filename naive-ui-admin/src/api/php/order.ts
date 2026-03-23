import { httpTwo, httpOctane } from '@/utils/http/axios';

export function simulationFlashSale(params) {
  return httpTwo.request({
    url: '/flashSale',
    method: 'POST',
    params,
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function generate() {
  return httpTwo.request({
    url: '/generateProducts',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function remove() {
  return httpTwo.request({
    url: '/removeFlashSaleProducts',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function flashSaleProducts() {
  return httpOctane.request({
    url: '/flashSaleProducts',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}
