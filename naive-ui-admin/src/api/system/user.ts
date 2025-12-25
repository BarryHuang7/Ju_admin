import { http, httpBlob, httpTwo } from '@/utils/http/axios';

export interface BasicResponseModel<T = any> {
  data: T;
  code: number;
  message: string;
  result: T;
}

export interface BasicPageParams {
  pageNumber: number;
  pageSize: number;
  total: number;
}

/**
 * @description: 获取用户信息
 */
export function getUserInfo() {
  return http.request({
    url: '/admin_info',
    method: 'get',
  });
}

/**
 * @description: 用户登录
 */
export function login(params, headerVerifyCode) {
  return httpTwo.request<BasicResponseModel>(
    {
      url: '/login/verification',
      method: 'POST',
      params,
      headers: {
        VerifyCode: headerVerifyCode,
      },
    },
    {
      isTransformResponse: false,
    }
  );
}

/**
 * @description: 用户修改密码
 */
export function changePassword(params, uid) {
  return http.request(
    {
      url: `/user/u${uid}/changepw`,
      method: 'POST',
      params,
    },
    {
      isTransformResponse: false,
    }
  );
}

/**
 * @description: 用户登出
 */
export function logout() {
  return httpTwo.request({
    url: '/login/loginOut',
    method: 'GET',
  });
}

/**
 * @description: 获取登录验证码
 */
export function getVerificationCode(headerVerifyCode) {
  return httpBlob.request({
    url: '/login/getVerificationCode',
    method: 'GET',
    headers: {
      VerifyCode: headerVerifyCode,
    },
  });
}
