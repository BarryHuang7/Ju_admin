<script lang="ts" setup>
  import { ref } from 'vue';
  import { simulationFlashSale, generate, remove, flashSaleProducts } from '@/api/php/order';

  /**
   * 模拟抖动
   */
  const simulationShaking = ref(false);
  /**
   * 模拟加载动画
   */
  const simulationLoading = ref(false);
  /**
   * 抖动
   */
  const shaking = ref(false);
  /**
   * 加载动画
   */
  const loading = ref(false);
  /**
   * 秒杀开始时间
   */
  const time = ref('');

  /**
   * 模拟请求抢购
   */
  const simulationFlashSaleRequest = (enableIdempotency: boolean) => {
    simulationFlashSale({ enableIdempotency })
      .then((res: any) => {
        if (res.code === 200) {
          window['$message'].success(res.msg);
        } else {
          window['$message'].error(res.msg);
        }
      })
      .catch((e: any) => {
        console.error(e);
        window['$message'].error(e);
      })
      .finally(() => {
        simulationShaking.value = false;
        simulationLoading.value = false;
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
        simulationFlashSaleRequest(false);
        break;
      case 2:
        if (!simulationShaking.value) {
          simulationShaking.value = true;
          simulationFlashSaleRequest(false);
        }
        break;
      case 3:
        simulationFlashSaleRequest(true);
        break;
      case 4:
        if (!simulationShaking.value) {
          simulationLoading.value = true;
          simulationShaking.value = true;
          simulationFlashSaleRequest(true);
        }
        break;
    }
  };

  /**
   * 生成商品
   */
  const generateProducts = () => {
    generate()
      .then((res: any) => {
        if (res.code === 200) {
          window['$message'].success(res.msg);
          time.value = res.data.startTime;
        } else {
          window['$message'].error(res.msg);

          if (res?.data?.startTime) {
            time.value = res.data.startTime;
          }
        }
      })
      .catch((e: any) => {
        console.error(e);
        window['$message'].error(e);
      });
  };

  /**
   * 清除商品
   */
  const removeProducts = () => {
    remove()
      .then((res: any) => {
        if (res.code === 200) {
          time.value = '';
          window['$message'].success(res.msg);
        } else {
          window['$message'].error(res.msg);
        }
      })
      .catch((e: any) => {
        console.error(e);
        window['$message'].error(e);
      });
  };

  /**
   * 抢购
   */
  const flashSale = () => {
    if (!shaking.value) {
      loading.value = true;
      shaking.value = true;

      flashSaleProducts()
        .then((res: any) => {
          if (res.code === 200) {
            if (res.msg === '秒杀成功！') {
              window['$message'].success(res.msg);
            } else {
              window['$message'].warning(res.msg);
            }
          } else {
            window['$message'].error(res.msg);
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
    }
  };
</script>

<template>
  <div class="mt-10">
    <n-grid x-gap="24" y-gap="24" cols="2 m:4 l:4">
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">无防抖动、无loading、接口无幂等性</span>
          <n-button type="primary" @click="simulationClick(1)">模拟抢购</n-button>
        </div>
      </n-gi>
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">防抖动、无loading、接口无幂等性</span>
          <n-button type="primary" @click="simulationClick(2)">模拟抢购</n-button>
        </div>
      </n-gi>
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">无防抖动、无loading、接口幂等性</span>
          <n-button type="primary" @click="simulationClick(3)">模拟抢购</n-button>
        </div>
      </n-gi>
      <n-gi>
        <div class="flex flex-col">
          <span class="text-[16px]">防抖动、loading、接口幂等性</span>
          <n-button type="primary" @click="simulationClick(4)" :loading="simulationLoading">
            模拟抢购
          </n-button>
        </div>
      </n-gi>
    </n-grid>

    <div class="mt-20">
      <n-space vertical :size="12">
        <n-alert title="功能说明" type="info">
          <span>这是使用</span>
          <span class="high-light">Laravel Octane Swoole</span>
          <span>高性能 HTTP 服务的秒杀。首先点击</span>
          <span class="high-light">生成秒杀商品</span>
          <span>按钮，将会出现</span>
          <span class="high-light">抢购</span>
          <span>按钮，也可以点击</span>
          <span class="high-light">清除秒杀信息</span>
          <span>按钮重新开始。</span>
        </n-alert>
      </n-space>
    </div>

    <div class="mt-20 flex flex-col">
      <div class="c-red mb-10 flex items-center" v-if="time">
        <div>
          <span>秒杀开始时间：</span>
          <span>{{ time }}</span>
        </div>

        <div>
          <n-button
            v-if="time"
            type="primary"
            @click="flashSale()"
            class="ml-40"
            :loading="loading"
          >
            抢购
          </n-button>
        </div>
      </div>

      <div class="flex">
        <n-button type="primary" @click="generateProducts()">生成秒杀商品</n-button>

        <n-popconfirm @positive-click="removeProducts()">
          <template #trigger>
            <n-button type="primary" class="ml-10">清除秒杀信息</n-button>
          </template>
          确定清除吗？
        </n-popconfirm>
      </div>
    </div>
  </div>
</template>

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
