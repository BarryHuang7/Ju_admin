<template>
  <div class="content">
    <div class="prize_wrapper">
        <div class="prize_content">
            <div class="prize_content_wrapper">
                <div
                    v-for="(item, index) in prize_list"
                    :key="index"
                    :class="['prize_content_item', index == action ? 'action' : '']"
                    :style="{
                        'top': computeTop(index) + 'px',
                        'left': computeLeft(index)  + 'px'
                    }"
                    :title="item"
                >
                    {{ item }}
                </div>
            </div>
        </div>
    </div>

    <div class="btn_content">
        <div class="prize_draw" @click="prize_draw">抽奖</div>
    </div>

    <div v-if="modal" class="modal_wrapper">
        <div class="modal">
            <div class="modal_head" title="关闭" @click="modal = false">×</div>
            <div class="modal_content">恭喜你获得【<span style="color: red;">{{ prize }}</span>】！</div>
        </div>
        <div class="modal_mask_layer" @click="modal = false"></div>
    </div>
  </div>
</template>

<script lang="ts" setup>
  import { ref, reactive } from 'vue';
  import { useUserStore } from '@/store/modules/user';

  const userStore = useUserStore();
  const isAdmin = userStore.getUserInfo.isAdmin;
  let prize_draw_set_timeout: NodeJS.Timer | null = null;
  const number_of_draws = ref(1);

  const prize_list = reactive(
    isAdmin ? [
      '1314现金', '再来一次', '1块钱', '手办+520红包', '我一个深深地吻',
      'iQOO手表', '一个我可以实现的愿望', '带你去买衣服，由黄公子买单', '再来一次', '200红包+年底换iQOO13 pro'
    ] : ['1000现金', '再来一次', '1块钱', '无', '空气', '手表', '愿望', '衣服', '再来一次', '200红包']
  );
  const action = ref(0);
  const prize = ref('');
  const modal = ref(false);

  // 计算每个奖品的头部位置
  const computeTop = (index) => {
    let num = 0

    // 总长
    const prize_length = prize_list.length
    // 长度的一半
    const half_length = prize_length / 2 - 1

    if ((index === 0 || index % half_length > 0) && index < half_length) {
      num = 0
    }

    if (index != 0 && (index + 1) % (prize_length / 2) === 0) {
      num = 1
    } else if ((index != 0 && index % half_length >= 0) && index > half_length) {
      num = prize_length % half_length
    }

    return 66 * num;
  };

  // 计算每个奖品离左边的位置
  const computeLeft = (index) => {
    let num = 0

    // 总长
    const prize_length = prize_list.length
    // 长度的一半
    const half_length = prize_length / 2 - 1

    if ((index === 0 || index % half_length > 0) && index < half_length) {
      num = index
    }

    if (index != 0 && (index + 1) % (prize_length / 2) === 0) {
      if (index > half_length) {
        num = 0
      } else {
        num = (index % half_length) + (half_length - 1)
      }
    } else if ((index != 0 && index % half_length >= 0) && index > half_length) {
      num = half_length - (index % (prize_length / 2)) - 1
    }

    return 146 * num
  };

  // 抽奖按钮
  const prize_draw = () => {
    if (number_of_draws.value < 1) {
      window['$message'].warning('你的抽奖次数用完啦！');
      return false
    }
    number_of_draws.value = 0
    prize.value = ''
    let i = 0
    const winning_number = getRandomNumbers()
    // 每次滚动速度
    let speed = 50
    // 圈数
    const number_of_turns = 5
    // 一共调用次数
    const j = ((number_of_turns + 1) * prize_list.length) - (prize_list.length - winning_number)
    let k = 0

    merryGoRound(i, j, k, speed, number_of_turns, winning_number)
  };

  // 奖品开始转动
  const merryGoRound = (i, j, k, speed, number_of_turns, winning_number) => {
    if (i == (number_of_turns - 1)) {
      speed = 100
    }

    if (i == number_of_turns) {
      speed = 200
    }

    if (j - k <= 5) {
      speed = (10 - (j - k)) * 100
    }

    prize_draw_set_timeout = setTimeout(() => {
      if (action.value == (prize_list.length - 1)) {
        action.value = 0
        i ++
      } else {
        action.value ++
      }

      if (i == number_of_turns) {
          speed += 200
      }

      // 这里判断转几圈
      if (i == number_of_turns && action.value == winning_number) {
        prize_draw_set_timeout && clearInterval(prize_draw_set_timeout)

        setTimeout(() => {
          if ([1, 8].includes(winning_number)) {
            number_of_draws.value += 1
          }
          prize.value = prize_list[winning_number]
          modal.value = true
        }, 150)
      } else {
        merryGoRound(i, j, k, speed, number_of_turns, winning_number)
      }
    }, speed)

    k ++
  };

  // 生成随机数
  const getRandomNumbers = () => {
    return Math.floor(Math.random() * prize_list.length)
  };
</script>

<style>
  .prize_wrapper {
    margin: 0 auto;
    width: 80%;
    height: 250px;
    margin-top: 60px;
  }
  .prize_content {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .prize_content_wrapper {
    margin: 0;
    padding: 0;
    position: relative;
    width: calc(126px * 4 + 10px * 4 * 2);
  }
  .prize_content_item {
    width: 120px;
    height: 40px;
    position: absolute;
    text-align: center;
    border: 1px solid #B0E0E6;
    color: #5E2612;
    padding: 10px;
    margin: 2px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    list-style-type: none;
  }
  .action {
    border: 2px solid #FF6100;
    background-color: #FFD700;
    opacity: 0.5;
  }
  .btn_content {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .prize_draw {
    text-align: center;
    cursor: pointer;
    border: 1px solid #000;
    border-radius: 20px;
    padding: 10px 20px;
    width: 50px;
  }
  .modal {
    width: 550px;
    height: 250px;
    background-color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    border-radius: 25px;
    margin: auto;
    transform: translateY(-50%);
    position: relative;
    z-index: 2;
  }
  .modal_head {
    position: absolute;
    right: 20px;
    top: 2px;
    font-size: 24px;
    cursor: pointer;
  }
  .modal_content {
    font-size: 24px;
    font-weight: bold;
  }
  .modal_mask_layer {
    background-color: rgba(0, 0, 0, .65);
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
  }
</style>