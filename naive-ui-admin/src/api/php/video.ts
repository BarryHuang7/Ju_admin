import { httpTwo, httpArrayBuffer } from '@/utils/http/axios';

export function videoInitiate(params) {
  return httpTwo.request({
    url: '/videoInitiate',
    method: 'POST',
    params,
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function handleUploadChunk(uuid, params) {
  return httpTwo.request({
    url: `/handleUploadChunk/${uuid}`,
    method: 'POST',
    params,
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
}

export function getVideoProgress(uuid) {
  return httpTwo.request({
    url: `/getVideoProgress/${uuid}`,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function cancelUploadVideo(uuid) {
  return httpTwo.request({
    url: `/cancelUploadVideo/${uuid}`,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function getVideoList(params) {
  return httpTwo.request({
    url: '/getVideoList',
    method: 'GET',
    params,
  });
}

export function deleteVideo(uuid) {
  return httpTwo.request({
    url: `/deleteVideo/${uuid}`,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

export function videoStream(uuid, start, end) {
  return httpArrayBuffer.request({
    url: `/videoStream/${uuid}`,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      Range: `bytes=${start}-${end}`,
    },
  });
}
