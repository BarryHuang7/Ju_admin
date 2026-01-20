<script lang="ts" setup>
  import { ref, onUnmounted } from 'vue';
  import {
    videoInitiate,
    handleUploadChunk,
    getVideoProgress,
    cancelUploadVideo,
  } from '@/api/php/video';

  /**
   * 视频文件ref
   */
  const videoRef = ref<HTMLInputElement>();
  /**
   * 视频文件大小最大上传限制
   */
  const sizeLimit = ref(20);
  /**
   * 当前视频文件
   */
  const currentFile = ref<File | null>(null);
  /**
   * 总切片数，根据实际文件大小改变
   */
  const totalChunks = ref(1);
  /**
   * 当前视频文件的uuid
   */
  const currentFileUUID = ref('');
  /**
   * 当前切片数
   */
  const currentChunks = ref(0);
  /**
   * 当前上传进度
   */
  const currentProgress = ref(0);
  /**
   * 上传视频防抖loading
   */
  const loading = ref(false);
  /**
   * 上传进度定时器
   */
  const timer = ref();
  /**
   * 是否已取消上传
   */
  const isCancelUpload = ref(false);

  /**
   * 初始化数据
   */
  const initializeData = () => {
    currentFile.value = null;
    totalChunks.value = 1;
    currentFileUUID.value = '';
    currentChunks.value = 0;
    currentProgress.value = 0;
    loading.value = false;
    if (timer.value) clearInterval(timer.value);
  };

  /**
   * 取消上传视频
   */
  const cancelUpload = () => {
    if (currentFileUUID.value) {
      cancelUploadVideo(currentFileUUID.value)
        .then((res: any) => {
          if (res.code === 200) {
            isCancelUpload.value = true;
            initializeData();
            window['$message'].success(res.msg);
          } else {
            window['$message'].error(res.msg);
          }
        })
        .catch((e: any) => {
          window['$message'].error('取消上传失败！' + e);
        });
    }
  };

  /**
   * 获取视频上传进度
   */
  const getProgress = () => {
    if (currentFileUUID.value) {
      getVideoProgress(currentFileUUID.value)
        .then((res: any) => {
          if (res.code === 200) {
            currentProgress.value = res.data.progress;
          } else {
            window['$message'].error(res.msg);
          }
        })
        .catch(() => {
          if (timer.value) clearInterval(timer.value);
        });
    } else {
      if (timer.value) clearInterval(timer.value);
    }
  };

  /**
   * 分片上传视频
   */
  const uploadChunk = async (chunk: Blob) => {
    await handleUploadChunk(currentFileUUID.value, {
      chunk,
      chunk_number: currentChunks.value,
      total_chunks: totalChunks.value,
    })
      .then((response: any) => {
        const responseCode = response.code;
        const responseMsg = response.msg;
        const responseData: any = response.data;

        if (responseCode === 200) {
          if (responseData.all_uploaded) {
            window['$message'].success('上传完成！正在合并视频...');

            setTimeout(() => {
              initializeData();
            }, 1000);
          }
        } else {
          if (!isCancelUpload.value) {
            window['$message'].error(responseMsg);
          }
        }
      })
      .catch((chunkE: any) => {
        loading.value = false;
        window['$message'].error(
          '上传视频分片失败！片段：' + currentChunks.value + '。错误信息：' + chunkE
        );
      });
  };

  /**
   * 开始上传视频
   */
  const startUpload = async (file: File) => {
    try {
      // 文件大小：字节
      const fileSize = file.size;
      // 向上取整文件大小：兆字节
      const fileSizeByMB = Math.ceil(fileSize / 1024 / 1024);

      // 小于等于1MB
      if (fileSizeByMB <= 1) {
        totalChunks.value = 2;
      }
      // 大于1MB，小于等于10MB
      else if (fileSizeByMB > 1 && fileSizeByMB <= 10) {
        totalChunks.value = 5;
      }
      // 大于10MB，小于等于20MB
      else if (fileSizeByMB > 10 && fileSizeByMB <= 20) {
        totalChunks.value = 10;
      }

      if (fileSizeByMB > sizeLimit.value) {
        currentFile.value = null;
        return window['$message'].error('视频最大上传限制为20MB！');
      }

      /**
       * 每个片的大小：字节
       */
      const chunkSize = Math.ceil(fileSize / totalChunks.value);

      // 初始化
      videoInitiate({
        file_name: file.name,
        size: fileSize,
        mime_type: file.type,
        total_chunks: totalChunks.value,
      })
        .then(async (res: any) => {
          if (res.code === 200) {
            currentFileUUID.value = res.data.uuid;

            if (!timer.value) {
              // 每秒获取一次
              timer.value = setInterval(() => {
                getProgress();
              }, 1000);
            }

            // 上传视频分片
            for (let i = 0; i < totalChunks.value; i++) {
              if (!isCancelUpload.value) {
                currentChunks.value = i + 1;
                const start = i * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                // 分片
                const chunk = file.slice(start, end);

                await uploadChunk(chunk);
              } else {
                break;
              }
            }
          } else {
            window['$message'].error(res.msg);
          }
        })
        .catch((e: any) => {
          initializeData();
          window['$message'].error('上传视频初始化失败！' + e);
        });
    } catch (err: any) {
      initializeData();
      window['$message'].error('上传失败！');
    }
  };

  /**
   * 处理上传的视频
   */
  const handleFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    if (!file.type.startsWith('video/')) {
      return window['$message'].error('请选择视频文件！');
    }

    loading.value = true;
    isCancelUpload.value = false;
    currentFile.value = file;
    await startUpload(file);
  };

  /**
   * 上传视频触发input框
   */
  const triggerFileInput = () => {
    videoRef.value?.click();
  };

  onUnmounted(() => {
    if (timer.value) clearInterval(timer.value);
  });
</script>

<template>
  <div class="mt-20">
    <div>
      <input
        type="file"
        ref="videoRef"
        @change="handleFileSelect"
        accept="video/*"
        style="display: none"
      />

      <div v-if="currentFile">
        <div>
          上传分片中：{{ currentChunks + ' / ' + totalChunks }}（{{ currentProgress + '%' }}）
        </div>
        <div>当前文件名：{{ currentFile?.name }}</div>
      </div>

      <div class="felx">
        <n-button type="primary" @click="triggerFileInput" :loading="loading">上传视频</n-button>
        <n-button v-if="currentFile" type="error" class="ml-10" @click="cancelUpload">
          取消上传
        </n-button>
      </div>
    </div>

    <div class="mt-20">上传失败列表，断点续传<span class="text-[crimson]">（开发中）</span></div>
    <div class="mt-20">视频列表<span class="text-[crimson]">（开发中）</span></div>
  </div>
</template>
