<template>
  <div mt-20>
    <n-button type="primary" @click="linkServer">连接服务器</n-button>
    <n-button type="primary" ml-10 @click="sendMessage(2)">接收服务器消息</n-button>

    <div flex justify-center items-center mt-10>
      <n-input v-model:value="message" placeholder="请输入消息" />
      <n-button type="primary" ml-10 @click="sendMessage(1)">发送消息</n-button>
    </div>

    <div mt-10>
      <n-button type="primary" @click="getAllOnlineUser">获取所有在线用户</n-button>

      <div v-if="onlineUserList.length > 0" flex items-center mt-10 p-10 bg-white>
        <n-space>
          <n-tag
            v-for="(item, index) in onlineUserList"
            :key="index"
            :type="selectUserId === item.userId ? 'success' : undefined"
            @click="selectUser(item)"
            p-10
            cursor-pointer
            title="选中与他对话"
          >
            {{ item.userName }}
          </n-tag>
        </n-space>
      </div>
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

  interface onlineUserType {
    userId: string;
    userName: string;
  }
  const onlineUserList = ref<onlineUserType[]>([]);
  const selectUserId = ref('');
  const selectUserName = ref('');

  // 连接服务器
  const linkServer = () => {
    if (userInfo) {
      ws.value = new WebSocket(
        `ws://175.178.236.223:8001/api/websocket/${userInfo['id']}/${userInfo['name']}`
      );

      ws.value.onopen = () => {
        window['$message'].success('连接成功！');
      };

      ws.value.onclose = () => {
        window['$message'].error('连接断开！');
        onlineUserList.value = [];
        selectUserId.value = '';
        selectUserName.value = '';
      };

      ws.value.onmessage = (msg) => {
        const data = JSON.parse(msg.data);

        if (data) {
          if (data.message) {
            window['$message'].info(`服务器给你发信息：${data.message}`);
          } else if (data.data) {
            const info = data.data;
            window['$message'].info(`【${info.sendUserName}】对你说：${info.message}`);
          }
        }
      };

      ws.value.onerror = (e) => {
        window['$message'].error('连接错误！');
        onlineUserList.value = [];
        selectUserId.value = '';
        selectUserName.value = '';
        console.log(e);
      };
    }
  };

  // 发送消息
  const sendMessage = (type: number) => {
    if (!message.value && type === 1) {
      window['$message'].error('请输入消息！');
      return false;
    }

    if (ws.value && ws.value.readyState === 1) {
      if (!selectUserId.value) {
        if (type === 1 && message.value) {
          ws.value.send(message.value);
        } else if (type === 2) {
          window['$message'].info('正在发送消息...服务器会在5秒后回复你');
          sendTimingMessage();
        }
      } else {
        sendCustomizeMessage();
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

  // 服务器发送定时消息
  const sendTimingMessage = async () => {
    if (userInfo) {
      const url = '/h/sendTimingMessage';
      const type = 'POST';
      const params: object = {};

      await toHttp(url, type, params);
    }
  };

  // 发送给指定用户消息
  const sendCustomizeMessage = async () => {
    if (userInfo) {
      const url = '/h/sendMessage';
      const type = 'POST';
      const params: object = {
        userId: selectUserId.value,
        userName: selectUserName.value,
        message: message.value,
        sendUserId: userInfo['id'],
        sendUserName: userInfo['name'],
      };

      await toHttp(url, type, params);
    }
  };

  // 获取所有在线用户信息
  const getAllOnlineUser = async () => {
    if (userInfo) {
      const url = '/h/getAllOnlineUser';
      const type = 'GET';

      await toHttp(url, type).then((res) => {
        const online = ws.value && ws.value.readyState === 1 ? 1 : 0;
        window['$message'].info('在线人数：' + (res.data.length + online) + '人');
        onlineUserList.value = res.data || [];
      });
    }
  };

  // 选择在线用户发送消息
  const selectUser = (item: onlineUserType) => {
    if (selectUserId.value === item.userId) {
      selectUserId.value = '';
      selectUserName.value = '';
    } else {
      selectUserId.value = item.userId;
      selectUserName.value = item.userName;
    }
  };
</script>
