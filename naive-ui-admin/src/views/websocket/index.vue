<template>
  <div mt-20>
    <n-space vertical :size="12" mb-20>
      <n-alert title="功能说明" type="info">
        <span>本功能使用</span>
        <span class="high-light" cursor-pointer>Swoole WebSocket</span>
        <span>实现，首先点击</span>
        <span class="high-light" cursor-pointer>连接服务器</span>
        <span>按钮，然后点击</span>
        <span class="high-light" cursor-pointer>获取所有在线用户</span>
        <span>按钮，点击对应的</span>
        <span class="high-light" cursor-pointer>用户</span>
        <span>，即可给对方发送消息。</span>
      </n-alert>
    </n-space>

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
            :type="selectUserId === item.user_id ? 'success' : undefined"
            @click="selectUser(item)"
            p-10
            cursor-pointer
            title="选中与他对话"
          >
            {{ item.user_name }}
          </n-tag>
        </n-space>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
  import { ref } from 'vue';
  import { useUserStore } from '@/store/modules/user';
  import { toHttpByPHP } from '@/api/table/list';

  const ws = ref<WebSocket>();
  const userStore = useUserStore();
  const userInfo: object = userStore.getUserInfo || {};

  const message = ref('');

  interface onlineUserType {
    user_id: string;
    user_name: string;
  }
  const onlineUserList = ref<onlineUserType[]>([]);
  const selectUserId = ref('');
  const selectUserName = ref('');

  // 连接服务器
  const linkServer = () => {
    if (userInfo) {
      ws.value = new WebSocket(
        `ws://110.41.16.194:9502/websocket/${userInfo['name']}/${userInfo['id']}`
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

        if (data && data.content) {
          const info = JSON.parse(data.content);

          if (info.type === 'TimingMessage') {
            window['$message'].info(`服务器给你发信息：${info.message}`);
          } else if (info.type === 'Message') {
            window['$message'].info(`【${info.send_user_name}】对你说：${info.message}`);
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
          ws.value.send(
            JSON.stringify({
              type: 2,
              message: message.value,
            })
          );
          message.value = '';
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
      const url = '/sendTimingMessage';
      const type = 'POST';
      const params: object = {};

      await toHttpByPHP(url, type, params);
    }
  };

  // 发送给指定用户消息
  const sendCustomizeMessage = async () => {
    if (userInfo) {
      const url = '/sendMessage';
      const type = 'POST';
      const params: object = {
        user_id: selectUserId.value,
        user_name: selectUserName.value,
        message: message.value,
        send_user_id: userInfo['id'],
        send_user_name: userInfo['name'],
      };

      await toHttpByPHP(url, type, params);
      message.value = '';
    }
  };

  // 获取所有在线用户信息
  const getAllOnlineUser = async () => {
    if (userInfo) {
      const url = '/getAllOnlineUser';
      const type = 'GET';

      await toHttpByPHP(url, type).then((res) => {
        const online = ws.value && ws.value.readyState === 1 ? 1 : 0;
        window['$message'].info('在线人数：' + (res.data.length + online) + '人');
        onlineUserList.value = res.data || [];
      });
    }
  };

  // 选择在线用户发送消息
  const selectUser = (item: onlineUserType) => {
    if (selectUserId.value === item.user_id) {
      selectUserId.value = '';
      selectUserName.value = '';
    } else {
      selectUserId.value = item.user_id;
      selectUserName.value = item.user_name;
    }
  };
</script>

<style>
  .high-light {
    padding: 0 6px;
    margin: 0 2px;
    border-radius: 3px;
    display: inline-block;
    color: #000;
    background: rgb(99, 226, 183);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
</style>
