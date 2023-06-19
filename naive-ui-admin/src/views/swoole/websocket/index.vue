<template>
  <div>
    <div bg-black mt-20 style="width: 500px; height: 300px; margin: 0 auto">
      <vue3-barrage
        ref="barrage"
        :lanesCount="6"
        boxWidth="500"
        boxHeight="300"
        :isShow="data.barrageIsShow"
        :list="data.barrageList"
        :loop="data.barrageLoop"
        :speed="data.speed"
        attachId="barrage"
        :fontSize="data.fontSize"
      >
        <!-- 自定义弹幕样式 -->
        <template #barrage="list">
          <span style="color: #ffffff">{{ list.item.msg }}</span>
        </template>
      </vue3-barrage>
    </div>

    <div flex justify-center items-center mt-10>
      <n-input v-model:value="message" placeholder="请输入消息" style="width: 200px" />
      <n-button type="primary" ml-10 @click="sendMessage()">发送弹幕</n-button>
    </div>
  </div>
</template>

<script lang="ts" setup>
  import { ref, reactive } from 'vue';

  // 弹幕位置：滚动、顶部固定、底部固定
  type PositionStatus = 'normal' | 'top' | 'bottom';

  interface BarrageList {
    id: number;
    msg: string | undefined;
    position: PositionStatus;
  }

  const message = ref('');

  let data = reactive({
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
    barrageLoop: true,
    // 弹幕速度
    speed: 5,
  });

  const sendMessage = () => {
    data.barrageList.push({
      id: ++data.currentId,
      msg: message.value,
      position: 'normal',
    });

    message.value = '';
  };
</script>
