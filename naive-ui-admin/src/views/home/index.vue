<template>
  <div @click="test()">首页啥都没有呢！</div>
</template>

<script lang="ts" setup>
  let i = 0;
  const test = () => {
    i++;
    let msg = '你非要点是吧？';
    if (i > 1) {
      msg += '还点' + i + '次！';
    }
    window['$message'].warning(msg);
  };
</script>
