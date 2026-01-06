<template>
  <div>
    <div class="flex justify-center items-center">
      <div
        @click="tryClicking()"
        class="inline-block text-center cursor-pointer"
        title="你点我试试？"
      >
        首页啥都没有呢！
      </div>
    </div>

    <n-statistic tabular-nums class="mt-10">
      <span>今日登录人数：</span>
      <span c-green>
        <n-number-animation :from="0" :to="todayNumberOfLogins" />
      </span>
      <template #suffix>人</template>
    </n-statistic>

    <div class="mt-20">
      <p c-green>功能列表：</p>
      <div>
        <div>
          <div class="mt-20">
            <span>Java</span>
            <span class="text-[red]">（正在改成PHP接口，功能不可用）</span>
          </div>
          <n-button type="info" @click="skip(2)" class="mt-20 md:mt-10 mr-10">
            Java WebSocket
          </n-button>
        </div>
        <div>
          <div class="mt-20">PHP Laravel</div>
          <n-button type="info" @click="skip(1)" class="mt-20 md:mt-10 mr-10">图片列表</n-button>
          <n-button type="info" @click="skip(3)" class="mt-20 md:mt-10 mr-10"
            >Swoole WebSocket</n-button
          >
          <n-button type="info" @click="skip(4)" class="mt-20 md:mt-10 mr-10">发送邮箱</n-button>
          <n-button type="info" @click="skip(5)" class="mt-20 md:mt-10 mr-10">
            通义千问chat
          </n-button>
          <n-button type="info" @click="skip(6)" class="mt-20 md:mt-10 mr-10">
            模拟商品秒杀
          </n-button>
        </div>
      </div>
    </div>

    <div class="mt-40 w-full h-[400px]">
      <v-chart :option="chartOption" autoresize />
    </div>
  </div>
</template>

<script lang="ts" setup>
  import { ref, reactive, onMounted, computed } from 'vue';
  import { toHttpByPHP } from '@/api/table/list';
  import { guestRecord } from '@/api/php/home';
  import { useRouter } from 'vue-router';
  import { use } from 'echarts/core';
  import { CanvasRenderer } from 'echarts/renderers';
  import { PieChart, BarChart, LineChart } from 'echarts/charts';
  import {
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
  } from 'echarts/components';
  import VChart from 'vue-echarts';

  interface guestRecordDataType {
    month: string;
    xAxis: Array<string>;
    series: Array<number>;
  }

  const router = useRouter();
  use([
    CanvasRenderer,
    BarChart,
    LineChart,
    PieChart,
    GridComponent,
    TitleComponent,
    TooltipComponent,
    LegendComponent,
  ]);

  /**
   * 今日登录人数
   */
  const todayNumberOfLogins = ref(0);
  /**
   * 尝试点击数
   */
  const i = ref(0);
  /**
   * 访客图表标题
   */
  const chartTitle = ref('每月访客记录');
  /**
   * 访客图表x轴数据
   */
  const chartXAxisData = reactive<string[]>([]);
  /**
   * 访客图表y轴数据
   */
  const chartYAxisData = reactive<number[]>([]);
  /**
   * 访客图表配置
   */
  const chartOption = computed(() => ({
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow',
      },
    },
    title: {
      text: chartTitle.value,
    },
    xAxis: {
      type: 'category',
      data: chartXAxisData,
    },
    yAxis: {
      type: 'value',
    },
    series: [
      {
        data: chartYAxisData,
        type: 'bar',
      },
    ],
  }));

  /**
   * 尝试点击
   */
  const tryClicking = () => {
    i.value++;
    let msg = '你非要点是吧？';
    if (i.value > 1) {
      msg += '还点' + i.value + '次！';
    }
    window['$message'].warning(msg);
  };

  /**
   * 获取今日访客数
   */
  const getVisitorNumber = async () => {
    const url = '/getVisitorNumber';
    const type = 'GET';

    await toHttpByPHP(url, type).then((res) => {
      todayNumberOfLogins.value = res.data.number || 0;
    });
  };

  /**
   * 按钮跳转
   */
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
      case 5:
        router.push('/qwen/index');
        break;
      case 6:
        router.push('/order/index');
        break;
    }
  };

  /**
   * 获取访客图表数据
   */
  const getGuestRecord = () => {
    guestRecord().then((res: any) => {
      const data: guestRecordDataType = res.data;

      if (data) {
        chartTitle.value = data.month + ' 访客记录';
        chartXAxisData.push(...data.xAxis);
        chartYAxisData.push(...data.series);
      }
    });
  };

  onMounted(() => {
    getVisitorNumber();
    getGuestRecord();
  });
</script>
