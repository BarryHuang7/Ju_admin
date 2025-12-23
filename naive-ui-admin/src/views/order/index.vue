<script lang="ts" setup>
  import { ref } from 'vue';
  import { flashSale } from '@/api/php/order';

  /**
   * 抖动
   */
  const shaking = ref(false);
  /**
   * 加载动画
   */
  const loading = ref(false);

  /**
   * 模拟请求抢购
   */
  const simulationFlashSale = (enableIdempotency: boolean) => {
    flashSale({ enableIdempotency })
      .then((res: any) => {
        if (res.data.code === 200) {
          window['$message'].success(res.data.msg);
        } else {
          window['$message'].error(res.data.msg);
        }
      })
      .catch((e: any) => {
        console.error(e);
        window['$message'].error(e);
      })
      .finally(() => {
        shaking.value = false;
        loading.value = false;
      });
  };

  /**
   * 模拟抢购点击
   * @param type 类型：
   *
   * 1 无防抖动、无loading、接口无幂等性
   *
   * 2 防抖动、无loading、接口无幂等性
   *
   * 3 防无抖动、接口幂等性
   *
   * 4 防抖动、接口幂等性
   */
  const simulationClick = (type: number) => {
    switch (type) {
      case 1:
        simulationFlashSale(false);
        break;
      case 2:
        if (!shaking.value) {
          shaking.value = true;
          simulationFlashSale(false);
        }
        break;
      case 3:
        simulationFlashSale(true);
        break;
      case 4:
        if (!shaking.value) {
          loading.value = true;
          shaking.value = true;
          simulationFlashSale(true);
        }
        break;
    }
  };
</script>

<template>
  <div class="mt-10">
    <n-grid x-gap="24" y-gap="24" cols="2 m:4 l:4">
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">无防抖动、无loading、接口无幂等性</span>
          <n-button type="primary" @click="simulationClick(1)">抢购</n-button>
        </div>
      </n-gi>
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">防抖动、无loading、接口无幂等性</span>
          <n-button type="primary" @click="simulationClick(2)">抢购</n-button>
        </div>
      </n-gi>
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">无防抖动、无loading、接口幂等性</span>
          <n-button type="primary" @click="simulationClick(3)">抢购</n-button>
        </div>
      </n-gi>
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">防抖动、loading、接口幂等性</span>
          <n-button type="primary" @click="simulationClick(4)" :loading="loading">抢购</n-button>
        </div>
      </n-gi>
    </n-grid>
  </div>
</template>
