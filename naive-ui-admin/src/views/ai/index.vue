<script lang="ts" setup>
  import { ref, nextTick, watch, onMounted } from 'vue';
  import {
    RobotOutlined,
    SendOutlined,
    ExclamationCircleOutlined,
    DeleteOutlined,
    UserOutlined,
  } from '@vicons/antd';

  interface messagesType {
    role: string;
    content: string;
    timestamp: Date;
    loading: boolean;
    isStreaming?: boolean;
  }
  interface messagesContainerType {
    scrollTop?: number;
    scrollHeight?: number;
  }

  const messages = ref<messagesType[]>([]);
  const inputText = ref('');
  const messagesContainer = ref<messagesContainerType | null>(null);
  const isAIThinking = ref(false);

  // 模拟流式响应
  const simulateStreamingResponse = async (text, messageIndex) => {
    const words = text.split('');
    let currentText = '';

    for (let i = 0; i < words.length; i++) {
      await new Promise((resolve) => setTimeout(resolve, 20 + Math.random() * 30));
      currentText += words[i];
      messages.value[messageIndex].content = currentText;
      scrollToBottom();
    }

    // 完成流式输出
    messages.value[messageIndex].isStreaming = false;
    messages.value[messageIndex].loading = false;
  };

  // 发送消息
  const handleSend = async () => {
    const text = inputText.value.trim();
    if (!text || isAIThinking.value) return;

    window['$message'].warning('接口正在开发中...');

    // 添加用户消息
    messages.value.push({
      role: 'user',
      content: text,
      timestamp: new Date(),
      loading: false,
    });

    // 清空输入框
    inputText.value = '';
    scrollToBottom();

    // 添加AI消息占位符
    const aiMessageIndex = messages.value.length;
    messages.value.push({
      role: 'assistant',
      content: '',
      timestamp: new Date(),
      loading: true,
      isStreaming: true,
    });

    isAIThinking.value = true;
    scrollToBottom();

    try {
      // 模拟延迟，显示正在思考
      await new Promise((resolve) => setTimeout(resolve, 500));

      // 示例：模拟AI响应
      const responses = [
        '你好！这是一个流式响应的示例。我能够逐字显示回复内容，就像真正的AI聊天一样。',
        '我是通义千问，由阿里云研发的超大规模语言模型。我能够回答问题、创作文字，比如写故事、写公文、写邮件、写剧本等等，还能进行逻辑推理、编程等任务。如果你有任何问题或需要帮助，欢迎随时告诉我！',
        '无法获取实时天气信息。建议你通过天气应用（如中国天气、墨迹天气）或搜索引擎查询“深圳今天天气”，即可获得最新的温度、降水、风力等信息。',
      ];

      const responseText = responses[Math.floor(Math.random() * responses.length)];

      // 更新AI消息内容
      messages.value[aiMessageIndex].content = '';

      // 开始流式输出
      await simulateStreamingResponse(responseText, aiMessageIndex);
    } catch (error) {
      console.error('发送消息失败:', error);
      messages.value[aiMessageIndex] = {
        role: 'assistant',
        content: '抱歉，发生了一些错误。请稍后再试。',
        timestamp: new Date(),
        loading: false,
        isStreaming: false,
      };
      window['$message'].error('发送失败，请重试');
    } finally {
      isAIThinking.value = false;
    }
  };

  // 清空对话
  const clearChat = () => {
    messages.value = [];
    window['$message'].success('对话已清空');
  };

  // 滚动到底部
  const scrollToBottom = () => {
    nextTick(() => {
      if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
      }
    });
  };

  // 格式化时间
  const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('zh-CN', {
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  // 监听消息变化，自动滚动
  watch(
    messages,
    () => {
      scrollToBottom();
    },
    { deep: true }
  );

  // 初始化时滚动到底部
  onMounted(() => {
    scrollToBottom();
  });
</script>

<template>
  <div class="mt-10">
    <n-space vertical :size="12" class="mb-20">
      <n-alert title="功能说明" type="info">
        <span>这是使用</span>
        <span class="high-light">阿里千问plus</span>
        <span>的流式AI问答功能。</span>
      </n-alert>
    </n-space>

    <div
      ref="messagesContainer"
      class="h-[calc(100vh-118px-56px-64px)] flex flex-col bg-[#151517] p-5 relative"
    >
      <!-- 欢迎 -->
      <div v-if="messages.length === 0" class="flex justify-center items-center h-full text-center">
        <div class="max-w-400px">
          <div>
            <n-icon size="64">
              <RobotOutlined class="text-64px text-blue-500 mb-5" />
            </n-icon>
          </div>
          <h3 class="text-24px mb-2 text-[#f9fafb]">AI 助手</h3>
          <p class="text-[#f9fafb]">你好！我是AI助手，有什么可以帮你的吗？</p>
        </div>
      </div>

      <!-- 消息列表 -->
      <div class="overflow-y-auto h-[calc(100vh-118px-56px-64px-150px)]">
        <div
          v-for="(msg, index) in messages"
          :key="index"
          :class="['mb-6 animate-fade-in', msg.role === 'user' ? 'user-message' : 'ai-message']"
        >
          <!-- 消息气泡 -->
          <div :class="['flex items-start gap-3', msg.role === 'user' ? 'justify-end' : '']">
            <div
              :class="[
                'max-w-70% px-8 py-6 rounded-22px',
                msg.role === 'user'
                  ? 'bg-[#2c2c2e] text-[#f9fafb] order-1'
                  : 'bg-gray-100 text-gray-800 order-2',
              ]"
            >
              <div v-if="!msg.isStreaming" class="leading-relaxed break-words">
                {{ msg.content }}
              </div>
              <div v-else class="leading-relaxed break-words">
                <span>{{ msg.content }}</span>
                <span class="cursor animate-pulse text-blue-500">▌</span>
              </div>

              <!-- 打字指示器 -->
              <div v-if="msg.loading" class="mt-2 flex items-center gap-1">
                <div class="w-2 h-2 rounded-full bg-blue-500 animate-bounce"></div>
                <div
                  class="w-2 h-2 rounded-full bg-blue-500 animate-bounce animation-delay--160ms"
                ></div>
                <div
                  class="w-2 h-2 rounded-full bg-blue-500 animate-bounce animation-delay--320ms"
                ></div>
              </div>
            </div>
          </div>

          <!-- 时间戳 -->
          <div
            :class="[
              'text-12px text-gray-500 mt-1',
              msg.role === 'user' ? 'text-left pl-10px' : 'text-right pr-10px',
            ]"
          >
            {{ formatTime(msg.timestamp) }}
          </div>
        </div>
      </div>

      <!-- AI正在思考 -->
      <div v-if="isAIThinking" class="p-4 text-center">
        <div
          class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 rounded-20px text-gray-600"
        >
          <span>正在思考...</span>
        </div>
      </div>

      <!-- 输入区域 -->
      <div
        class="bg-white rounded-2xl p-5 shadow-lg absolute bottom-[5px] w-[calc(100vw-40px)] md:w-[calc(100vw-240px)]"
      >
        <div class="w-full">
          <n-input
            v-model:value="inputText"
            type="textarea"
            placeholder="输入你的问题..."
            :rows="3"
            :auto-size="{ minRows: 1, maxRows: 4 }"
            @keydown.enter.exact.prevent="handleSend"
            :disabled="isAIThinking"
            class="rounded-lg border-gray-300 focus:border-blue-500 focus:shadow-outline"
          />
          <div class="mt-3 flex justify-end items-center gap-2">
            <!-- 清空按钮 -->
            <n-tooltip v-if="messages.length > 0" trigger="hover">
              <template #trigger>
                <n-button type="text" @click="clearChat" :disabled="isAIThinking">
                  <n-icon>
                    <DeleteOutlined />
                  </n-icon>
                </n-button>
              </template>
              清空对话
            </n-tooltip>

            <!-- 发送按钮 -->
            <n-button
              type="primary"
              @click="handleSend"
              :loading="isAIThinking"
              :disabled="!inputText.trim()"
              class="rounded-3xl px-6"
            >
              <template #icon>
                <n-icon>
                  <SendOutlined />
                </n-icon>
              </template>
              发送
            </n-button>
          </div>
        </div>

        <!-- 底部提示 -->
        <div class="mt-2 flex items-center gap-1 text-12px text-gray-500">
          <n-icon>
            <ExclamationCircleOutlined />
          </n-icon>
          <span>按 Enter 发送，Shift + Enter 换行</span>
        </div>
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
