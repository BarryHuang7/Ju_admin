<template>
  <div>
    <div bg-black mt-20 style="width: 500px; height: 300px; margin: 0 auto">
      <vue3-barrage
        ref="barrage"
        :lanesCount="6"
        :boxWidth="500"
        :boxHeight="300"
        :isShow="barrageData.barrageIsShow"
        :list="barrageData.barrageList"
        :loop="barrageData.barrageLoop"
        :speed="barrageData.speed"
        attachId="barrage"
        :fontSize="barrageData.fontSize"
      >
        <!-- 自定义弹幕样式 -->
        <template #barrage="list">
          <span style="color: #ffffff">{{ list.item.msg }}</span>
        </template>
      </vue3-barrage>
    </div>

    <div flex justify-center items-center mt-10>
      <n-select
        v-model:value="barrageData.position"
        :options="barrageOptions"
        style="width: 100px"
      />
      <n-space ml-10>
        <n-switch v-model:value="barrageData.barrageIsShow">
          <template #checked>弹幕关</template>
          <template #unchecked>弹幕开</template>
        </n-switch>
      </n-space>
      <n-input
        v-model:value="message"
        placeholder="请输入消息"
        @keyup.enter="keyUp()"
        style="width: 200px"
        ml-10
      />
      <n-button type="primary" ml-10 @click="sendMessage()">发送弹幕</n-button>
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
    position: PositionStatus;
  }

  const message = ref('');

  let barrageData = reactive({
    // 弹幕列表
    barrageList: [] as Array<BarrageList>,
    // 弹幕位置
    position: 'normal',
    // 弹幕字体大小
    fontSize: 12,
    // 是否显示弹幕
    barrageIsShow: true,
    currentId: 0,
    // 是否循环显示
    barrageLoop: false,
    // 弹幕速度
    speed: 5,
  });

  const ws = ref<WebSocket>();

  ws.value = new WebSocket('ws://175.178.236.223:9502/websocket');

  ws.value.onmessage = (msg) => {
    const data = JSON.parse(msg.data);

    barrageData.barrageList.push({
      id: ++data.currentId,
      msg: `${data.name}说：${data.msg}`,
      position: barrageData.position as PositionStatus,
    });
  };

  const sendMessage = () => {
    if (message.value) {
      const msg_json = {
        name: userInfo['name'],
        msg: message.value,
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
