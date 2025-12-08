<script lang="ts" setup>
  import { ref } from 'vue';
  import { sendEmail } from '@/api/php/email';

  const emails = ref('');

  const send = () => {
    const newEmails = emails.value
      .trim()
      .replace(/;+$/, '')
      .split(';')
      .map((email) => email.trim())
      .filter((email) => email);

    sendEmail({ emails: newEmails })
      .then((res: any) => {
        emails.value = '';

        if (res.data.code === 200) {
          window['$message'].success(res.data.msg);
        } else {
          window['$message'].error(res.data.msg);
        }
      })
      .catch((e: any) => {
        window['$message'].error(e);
      });
  };
</script>

<template>
  <div mt-10>
    <n-space vertical :size="12" mb-20>
      <n-alert title="功能说明" type="info">
        <span>这是使用</span>
        <span class="high-light">队列 job</span>
        <span>的异步发邮箱功能。</span>
      </n-alert>
    </n-space>

    <n-input
      v-model:value="emails"
      type="textarea"
      placeholder="请输入邮箱，多个以英文分号【;】分割"
    />
    <n-button type="primary" mt-10 @click="send()">发送</n-button>
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
