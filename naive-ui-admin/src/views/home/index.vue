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

    <n-statistic tabular-nums class="mt-10">
      <span>累计访客人数：</span>
      <span c-green>
        <n-number-animation :from="0" :to="CumulativeNumberOfLogins" />
      </span>
      <template #suffix>人</template>
    </n-statistic>

    <div class="mt-20">
      <p c-green>功能列表：</p>
      <div>
        <div class="mt-20">前端语言是：vue 3 + typescript + naive-ui + tailwind</div>
        <div class="mt-20">后端语言是：PHP 8.2 + Laravel 12 + Hyperf 3.2</div>

        <div class="mt-20">
          <n-button type="info" @click="skip(1)" class="md:mt-10 mr-10">图片列表</n-button>
          <n-button type="info" @click="skip(2)" class="mt-20 md:mt-10 mr-10">聊天室</n-button>
          <n-button type="info" @click="skip(3)" class="mt-20 md:mt-10 mr-10">直播弹幕</n-button>
          <n-button type="info" @click="skip(4)" class="mt-20 md:mt-10 mr-10">发送邮箱</n-button>
          <n-button type="info" @click="skip(5)" class="mt-20 md:mt-10 mr-10">
            通义千问chat
          </n-button>
          <n-button type="info" @click="skip(6)" class="mt-20 md:mt-10 mr-10">
            模拟商品秒杀
          </n-button>
          <n-button type="info" @click="skip(7)" class="mt-20 md:mt-10 mr-10">视频</n-button>
        </div>

        <div class="mt-20 inline-block">
          <span>另一个账号：guest2，密码：123456</span>
          <n-button type="info" @click="copyLine()" title="点击复制链接" class="ml-10">
            {{ copied ? '复制成功✅️' : '复制' }}
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
  // import { toHttpByPHP } from '@/api/table/list';
  // import { guestRecord } from '@/api/php/home';
  import { indexInfo } from '@/api/hyperf/hyperf';
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
  import { useClipboard } from '@vueuse/core';

  // interface guestRecordDataType {
  //   month: string;
  //   xAxis: Array<string>;
  //   series: Array<number>;
  // }

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
   * 累计访客人数
   */
  const CumulativeNumberOfLogins = ref(0);
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
  const { copy, copied } = useClipboard({ legacy: true });

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
  // const getVisitorNumber = async () => {
  //   const url = '/getVisitorNumber';
  //   const type = 'GET';

  //   await toHttpByPHP(url, type).then((res) => {
  //     todayNumberOfLogins.value = res.data.number || 0;
  //     CumulativeNumberOfLogins.value = res.data.cumulativeNumber || 0;
  //   });
  // };

  /**
   * 按钮跳转
   */
  const skip = (type: number) => {
    switch (type) {
      case 1:
        router.push('/image/image-list');
        break;
      case 2:
        router.push('/websocket/chat-room');
        break;
      case 3:
        router.push('/websocket/bullet-chat');
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
      case 7:
        router.push('/video/vido-upload');
        break;
    }
  };

  /**
   * 获取访客图表数据
   */
  // const getGuestRecord = () => {
  //   guestRecord().then((res: any) => {
  //     const data: guestRecordDataType = res.data;

  //     if (data) {
  //       chartTitle.value = data.month + ' 访客记录';
  //       chartXAxisData.push(...data.xAxis);
  //       chartYAxisData.push(...data.series);
  //     }
  //   });
  // };

  /**
   * 获取首页信息
   */
  const getIndexInfo = () => {
    indexInfo().then((res: any) => {
      const data: any = res.data;

      if (data) {
        /**
         * 获取访客图表数据
         */
        const guestRecord = data?.guestRecord?.data;

        if (guestRecord) {
          chartTitle.value = guestRecord.month + ' 访客记录';
          chartXAxisData.push(...guestRecord.xAxis);
          chartYAxisData.push(...guestRecord.series);
        }

        /**
         * 获取今日访客数
         */
        const visitorNumber = data?.visitorNumber?.data;

        if (visitorNumber) {
          todayNumberOfLogins.value = visitorNumber.number || 0;
          CumulativeNumberOfLogins.value = visitorNumber.cumulativeNumber || 0;
        }
      }
    });
  };

  /**
   * 复制链接
   */
  const copyLine = async () => {
    try {
      await copy('https://hjpl.cn/?u=guest2&p=123456');
    } catch (err) {
      window['$message'].error('复制失败');
      console.error('复制失败！原因:', err);
    }
  };

  /**
   * 冒泡算法
   */
  // const bubbling = () => {
  //   var arr = [23, 78, 99, 7, 56];
  //   console.log('开始数组:' + arr);

  //   for (var i = 0; i < arr.length - 1; i++) {
  //     console.log('循环i:' + i);
  //     var swapped = false;

  //     for (var j = 0; j < arr.length - 1 - i; j++) {
  //       console.log('循环j:' + j);

  //       console.log('对比: ' + arr[j] + ' 与 ' + arr[j + 1]);
  //       if (arr[j] > arr[j + 1]) {
  //         var temp = arr[j];
  //         arr[j] = arr[j + 1];
  //         arr[j + 1] = temp;
  //         swapped = true;
  //       }
  //     }
  //     console.log('循环i:' + i + ' 结果:' + arr);

  //     if (!swapped) {
  //       console.log('提前结束');
  //       break;
  //     }
  //   }

  //   console.log('最终数组:' + arr);
  // };

  onMounted(() => {
    // getVisitorNumber();
    // getGuestRecord();
    getIndexInfo();
  });
</script>
