<template>
  <div>
    <div flex justify-center items-center>
      <div @click="test()" inline-block text-center cursor-pointer title="你点我试试？">
        首页啥都没有呢！
      </div>
    </div>

    <n-statistic tabular-nums mt-10>
      <span>今日访客量：</span>
      <span c-green>
        <n-number-animation :from="0" :to="number" />
      </span>
      <template #suffix>人</template>
    </n-statistic>

    <div mt-20>
      <p c-green>功能列表：</p>
      <div>
        <n-button type="info" @click="skip(1)" mt-20 md:mt-10 mr-10>图片列表</n-button>
        <n-button type="info" @click="skip(2)" mt-20 md:mt-10 mr-10>Java WebSocket</n-button>
        <n-button type="info" @click="skip(3)" mt-20 md:mt-10 mr-10>Swoole WebSocket</n-button>
        <n-button type="info" @click="skip(4)" mt-20 md:mt-10 mr-10>发送邮箱</n-button>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
  import { ref } from 'vue';
  import { toHttp } from '@/api/table/list';
  import { useRouter } from 'vue-router';
  const router = useRouter();

  const number = ref(0);
  let i = 0;

  const test = () => {
    i++;
    let msg = '你非要点是吧？';
    if (i > 1) {
      msg += '还点' + i + '次！';
    }
    window['$message'].warning(msg);
  };

  const getVisitorNumber = async () => {
    const url = '/h/getVisitorNumber';
    const type = 'GET';

    await toHttp(url, type).then((res) => {
      number.value = res.data.number || 0;
    });
  };

  const skip = (type: number) => {
    switch (type) {
      case 1:
        router.push('/image/image-list');
        break;
      case 2:
        router.push('/websocket/websocket-index');
        break;
      case 3:
        router.push('/swoole/swoole-websocket');
        break;
      case 4:
        router.push('/email/index');
        break;
    }
  };

  getVisitorNumber();
</script>
