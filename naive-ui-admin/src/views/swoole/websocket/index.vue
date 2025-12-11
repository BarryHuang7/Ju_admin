<template>
  <div mt-20>
    <n-space vertical :size="12" mb-20>
      <n-alert title="功能说明" type="info">
        <span>进页面就会</span>
        <span class="high-light">连接服务器</span>
        <span>，</span>
        <span class="high-light">在线用户</span>
        <span>可实时看到对方发送的弹幕（模拟直播弹幕）。</span>
      </n-alert>
    </n-space>

    <div
      bg-black
      :style="{ width: `${barrageData.width}px` }"
      style="height: 300px; margin: 0 auto"
    >
      <vue3-barrage
        ref="barrage"
        :lanesCount="6"
        :boxWidth="barrageData.width"
        :boxHeight="300"
        :isShow="barrageData.isShow"
        :list="barrageData.list"
        :loop="barrageData.loop"
        :speed="barrageData.speed"
        attachId="barrage"
        :fontSize="barrageData.fontSize"
      >
        <!-- 自定义弹幕样式 -->
        <template #barrage="list">
          <span :style="{ color: list.item.color }">{{ list.item.msg }}</span>
        </template>
      </vue3-barrage>
    </div>

    <div mt-10>
      <div flex justify-center items-center>
        <span>弹幕样式：</span>
        <n-select
          v-model:value="barrageData.position"
          :options="barrageOptions"
          style="width: 100px"
        />
        <span ml-10>弹幕颜色：</span>
        <n-color-picker v-model:value="barrageData.color" style="width: 100px" />
      </div>
      <div flex justify-center items-center mt-10>
        <n-space>
          <n-switch v-model:value="barrageData.isShow" />
        </n-space>
        <n-input
          v-model:value="message"
          placeholder="请输入弹幕"
          @keyup.enter="keyUp()"
          style="width: 200px"
          ml-10
        />
        <n-button type="primary" ml-10 @click="sendMessage()">发送弹幕</n-button>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
  import { ref, reactive } from 'vue';
  import { useUserStore } from '@/store/modules/user';

  const userStore = useUserStore();
  const userInfo: object = userStore.getUserInfo || {};

  // 弹幕位置：滚动、顶部固定、底部固定
  type PositionStatus = 'normal' | 'top' | 'bottom';

  const barrageOptions = [
    { label: '默认', value: 'normal' },
    { label: '顶部', value: 'top' },
    { label: '底部', value: 'bottom' },
  ];

  interface BarrageList {
    id: number;
    msg: string | undefined;
    color: string;
    position: PositionStatus;
  }

  const message = ref('');

  let barrageData = reactive({
    // 弹幕列表
    list: [] as Array<BarrageList>,
    // 弹幕位置
    position: 'normal',
    // 弹幕字体大小
    fontSize: 16,
    // 是否显示弹幕
    isShow: true,
    currentId: 0,
    // 是否循环显示
    loop: false,
    // 弹幕速度
    speed: 5,
    // 弹幕宽度
    width: window.innerWidth >= 500 ? 500 : window.innerWidth - 20,
    // 弹幕颜色
    color: '#ffffff',
  });

  const ws = ref<WebSocket>();

  ws.value = new WebSocket(
    `ws://110.41.16.194:9502/websocket/${userInfo['name']}/${userInfo['id']}`
  );

  ws.value.onmessage = (msg) => {
    const data = JSON.parse(msg.data);

    barrageData.list.push({
      id: ++data.currentId,
      msg: `${data.name}说：${data.msg}`,
      color: data.color,
      position: barrageData.position as PositionStatus,
    });
  };

  const sendMessage = () => {
    if (message.value) {
      const msg_json = {
        name: userInfo['name'],
        msg: message.value,
        color: barrageData.color,
      };

      if (ws.value) {
        ws.value.send(JSON.stringify(msg_json));
      }

      message.value = '';
    }
  };

  const keyUp = () => {
    sendMessage();
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
