declare module 'vue-echarts' {
  import { DefineComponent } from 'vue';

  const component: DefineComponent<{}, {}, any>;
  export default component;
}

// 或者更完整的类型声明
declare module 'vue-echarts' {
  import { DefineComponent } from 'vue';
  import type { ComposeOption } from 'echarts/core';
  import type { EChartsOption } from 'echarts';

  export type ECOption = ComposeOption<EChartsOption>;

  const VueEcharts: DefineComponent<{
    option: ECOption;
    initOptions?: any;
    theme?: string | object;
    autoresize?: boolean;
    manualUpdate?: boolean;
  }>;

  export default VueEcharts;
};