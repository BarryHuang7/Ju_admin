<template>
  <div mt-20>
    <n-button type="primary" @click="linkServer">连接服务器</n-button>
    <n-button type="primary" ml-10 @click="sendMessage(2)">接收服务器消息</n-button>

    <div flex justify-center items-center mt-10>
      <n-input v-model:value="message" placeholder="请输入消息" />
      <n-button type="primary" ml-10 @click="sendMessage(1)">发送消息</n-button>
    </div>
  </div>
</template>

<script lang="ts" setup>
  import { ref } from 'vue';
  import { useUserStore } from '@/store/modules/user';
  import { toHttp } from '@/api/table/list';

  const ws = ref<WebSocket>();
  const userStore = useUserStore();
  const userInfo: object = userStore.getUserInfo || {};

  const message = ref('');

  const linkServer = () => {
    if (userInfo) {
      ws.value = new WebSocket('ws://175.178.236.223:8001/api/websocket/' + userInfo['id']);

      ws.value.onopen = () => {
        window['$message'].success('连接成功！');
      };

      ws.value.onclose = () => {
        window['$message'].error('连接断开！');
      };

      ws.value.onmessage = (msg) => {
        window['$message'].info('服务器给你发信息！');
        const data = JSON.parse(msg.data);

        if (data) {
          window['$message'].info(data.message);
        }
      };

      ws.value.onerror = (e) => {
        window['$message'].error('连接错误！');
        console.log(e);
      };
    }
  };

  const sendMessage = (type: number) => {
    if (!message.value && type === 1) {
      window['$message'].error('请输入消息！');
      return false;
    }

    if (ws.value && ws.value.readyState === 1) {
      if (type === 1 && message.value) {
        ws.value.send(message.value);
      } else if (type === 2) {
        window['$message'].info('正在发送消息...服务器会在5秒后回复你');
        sendTimingMessage();
      }
    } else {
      let errorMsg = '连接不存在！请先与服务器连接！';

      if (ws.value) {
        switch (ws.value.readyState) {
          case 0:
            errorMsg = '正在连接，请稍后...';
          case 2:
            errorMsg = '连接正在关闭...';
          case 3:
            errorMsg = '连接已关闭，请重试！';
        }
      }
      window['$message'].error(errorMsg);
    }
  };

  const sendTimingMessage = async () => {
    if (userInfo) {
      const url = '/h/sendTimingMessage';
      const type = 'POST';
      const params: object = {
        id: userInfo['id'],
      };

      await toHttp(url, type, params);
    }
  };
</script>
