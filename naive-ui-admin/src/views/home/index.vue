<template>
  <div>
    <div @click="test()" text-center cursor-pointer title="你点我试试？">首页啥都没有呢！</div>

    <n-statistic tabular-nums mt-10>
      <span>今日访客量：</span>
      <span c-green>
        <n-number-animation :from="0" :to="number" />
      </span>
      <template #suffix>人</template>
    </n-statistic>
  </div>
</template>

<script lang="ts" setup>
  import { ref } from 'vue';
  import { toHttp } from '@/api/table/list';

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

  getVisitorNumber();
</script>
