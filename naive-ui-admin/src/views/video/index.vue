<script lang="ts" setup>
  import { ref, reactive, onUnmounted, onMounted, h } from 'vue';
  import {
    videoInitiate,
    handleUploadChunk,
    getVideoProgress,
    cancelUploadVideo,
    getVideoList,
    deleteVideo,
  } from '@/api/php/video';
  import { DataTableColumns, NButton, NPopconfirm, NTag } from 'naive-ui';
  import { VideoPlayer } from '@videojs-player/vue';
  import 'video.js/dist/video-js.css';

  type listType = {
    id: number;
    uuid: string;
    original_name: string;
    index_path: string;
    path: string;
    mime_type: string;
    size: number;
    chunks: string;
    total_chunks: number;
    status: string;
    created_at: string;
    updated_at: string;
  };

  /**
   * 域名地址
   */
  const domainAddress = 'http:///110.41.16.194';
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
   * 页码相关
   */
  const dataInfo = reactive({
    total: 0,
    pageNo: 1,
    pageSize: 5,
  });
  /**
   * 列表数据
   */
  const loadDataTable = ref<listType[]>([]);
  /**
   * 列表的加载
   */
  const tableLoading = ref(true);
  /**
   * 表格字段
   */
  const columns: DataTableColumns<listType> = [
    {
      title: '序号',
      key: 'id',
      width: 20,
    },
    {
      title: '视频名',
      key: 'original_name',
      width: 100,
    },
    {
      title: '视频类型',
      key: 'mime_type',
      width: 50,
    },
    {
      title: '视频大小',
      key: 'size',
      width: 50,
      render(row: listType) {
        return Number((row.size / 1024 / 1024).toFixed(2)) + ' MB';
      },
    },
    {
      title: '状态',
      key: 'status',
      width: 50,
      render(row: listType) {
        return h(
          NTag,
          {
            // 上传中uploading, 合并中merging, 处理中processing, 完成completed, 失败failed
            type:
              row.status === 'completed'
                ? 'success'
                : row.status === 'failed'
                ? 'error'
                : row.status === 'uploading'
                ? 'info'
                : 'warning',
          },
          {
            default: () =>
              row.status === 'completed'
                ? '完成'
                : row.status === 'failed'
                ? '失败'
                : row.status === 'uploading'
                ? '上传中'
                : '合并中',
          }
        );
      },
    },
    {
      title: '创建时间',
      key: 'created_at',
      width: 80,
    },
    {
      title: '更新时间',
      key: 'updated_at',
      width: 80,
    },
    {
      title: '操作',
      key: 'operation',
      width: 100,
      render(row: listType) {
        return [
          ['failed', 'uploading'].includes(row.status)
            ? h(
                NButton,
                {
                  size: 'small',
                  style: 'margin: 0 5px 0 0;',
                  onClick: () => resumeUpload(row),
                  disabled: loading.value && row.uuid == resumeUploadInfo.value?.uuid,
                },
                {
                  default: () => '断点续传',
                }
              )
            : '',
          row.status === 'completed'
            ? h(
                NButton,
                {
                  size: 'small',
                  style: 'margin: 0 5px 0 0;',
                  onClick: () => openModal(row),
                },
                {
                  default: () => '查看',
                }
              )
            : '',
          h(
            NPopconfirm,
            {
              onPositiveClick: () => deleteData(row.uuid),
            },
            {
              trigger: () => {
                return h(
                  NButton,
                  {
                    size: 'small',
                  },
                  { default: () => '删除' }
                );
              },
              default: () => {
                return '确认删除该视频？';
              },
            }
          ),
        ];
      },
    },
  ];
  /**
   * 视频模态框
   */
  const showModal = ref(false);
  /**
   * 视频模态框标题
   */
  const modalTitle = ref('');
  /**
   * 视频路径
   */
  const videoUrl = ref('');
  /**
   * 是否断点续传
   */
  const isResumeUpload = ref(false);
  /**
   * 断点续传信息
   */
  const resumeUploadInfo = ref<listType>();

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
    isResumeUpload.value = false;
    resumeUploadInfo.value = undefined;
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
   * 删除视频
   */
  const deleteData = (uuid: string) => {
    if (uuid) {
      deleteVideo(uuid)
        .then((res: any) => {
          if (res.code === 200) {
            window['$message'].success(res.msg);
            getList();
          } else {
            window['$message'].error(res.msg);
          }
        })
        .catch((e: any) => {
          window['$message'].error('删除失败！' + e);
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
              getList();
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
   * 设置上传配置
   */
  const uploadConfig = (file: File) => {
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

    return {
      fileSize,
      chunkSize,
    };
  };

  /**
   * 计算分片
   */
  const calculationChunk = async (file: File, i: number, chunkSize: number) => {
    const start = i * chunkSize;
    const end = Math.min(start + chunkSize, file.size);
    // 分片
    const chunk = file.slice(start, end);

    await uploadChunk(chunk);
  };

  /**
   * 开始上传视频
   */
  const startUpload = async (file: File) => {
    try {
      const { fileSize, chunkSize } = uploadConfig(file);

      // 初始化
      videoInitiate({
        file_name: file.name,
        size: fileSize,
        mime_type: file.type,
        total_chunks: totalChunks.value,
      })
        .then(async (res: any) => {
          if (res.code === 200) {
            getList();
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
                await calculationChunk(file, i, chunkSize);
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

    if (isResumeUpload.value) {
      await resumeUploadChunk(file);
    } else {
      await startUpload(file);
    }
  };

  /**
   * 上传视频触发input框
   */
  const triggerFileInput = () => {
    if (currentFile.value) {
      return window['$message'].error('正在上传其他文件！请稍等...');
    }

    if (videoRef.value) {
      videoRef.value.value = '';
    }
    videoRef.value?.click();
  };

  /**
   * 重新上传视频
   */
  const resumeUpload = async (row: listType) => {
    if (currentFile.value) {
      return window['$message'].error('正在上传其他文件！请稍等...');
    }

    isResumeUpload.value = true;
    resumeUploadInfo.value = row;
    currentFileUUID.value = row.uuid;
    triggerFileInput();
  };

  /**
   * 断点续传视频
   */
  const resumeUploadChunk = async (file: File) => {
    try {
      if (resumeUploadInfo.value) {
        const { chunkSize } = uploadConfig(file);
        const chunks = JSON.parse(resumeUploadInfo.value?.chunks || '[]');

        if (!timer.value) {
          // 每秒获取一次
          timer.value = setInterval(() => {
            getProgress();
          }, 1000);
        }

        for (let i = 0; i < totalChunks.value; i++) {
          if (!isCancelUpload.value) {
            currentChunks.value = i + 1;
            const oldChunk = chunks.filter((item) => item.chunkNumber == currentChunks.value);

            // 旧分片上传失败时
            if (oldChunk.length > 0) {
              if (oldChunk[0].status !== 'completed') {
                await calculationChunk(file, i, chunkSize);
              }
            }
            // 新分片上传
            else {
              await calculationChunk(file, i, chunkSize);
            }
          } else {
            break;
          }
        }
      } else {
        isResumeUpload.value = false;
        currentFileUUID.value = '';
        window['$message'].error('重新上传数据错误！');
      }
    } catch (err: any) {
      initializeData();
      window['$message'].error('上传失败！');
    }
  };

  /**
   * 获取视频列表
   */
  const getList = async () => {
    const params = {
      pageIndex: dataInfo.pageNo,
      pageSize: dataInfo.pageSize,
    };
    await getVideoList(params)
      .then((res) => {
        loadDataTable.value = res.data.list || [];
        dataInfo.total = res.data.total;
      })
      .finally(() => {
        tableLoading.value = false;
      });
  };

  /**
   * 切换页码
   */
  const changePage = (value: number) => {
    dataInfo.pageNo = value;
    getList();
  };

  /**
   * 切换每页显示数
   */
  const changePageSize = (value: number) => {
    dataInfo.pageNo = 1;
    dataInfo.pageSize = value;
    getList();
  };

  /**
   * 打开模态框查看视频
   */
  const openModal = (row: any) => {
    modalTitle.value = '查看【' + row.original_name + '】';
    videoUrl.value = `${domainAddress}:8077/file/${row.index_path}`;
    showModal.value = true;
  };

  /**
   * 关闭视频模态框
   */
  const closeModal = () => {
    modalTitle.value = '';
    videoUrl.value = '';
    showModal.value = false;
  };

  onMounted(() => {
    getList();
  });

  onUnmounted(() => {
    if (timer.value) clearInterval(timer.value);
  });
</script>

<template>
  <div class="mt-10">
    <n-space vertical :size="12">
      <n-alert title="功能说明" type="info">
        <span>这是使用</span>
        <span class="high-light">分片</span>
        <span>上传的视频列表，支持</span>
        <span class="high-light">断点续传</span>
        <span>，播放的视频格式是转化后的</span>
        <span class="high-light">m3u8</span>
        <span>。</span>
      </n-alert>
    </n-space>

    <div class="mt-20 bg-white p-15">
      <!-- 按钮 -->
      <div>
        <input
          type="file"
          ref="videoRef"
          @change="handleFileSelect"
          accept="video/*"
          style="display: none"
        />

        <div class="flex justify-between items-center">
          <div>
            <div v-if="currentFile">
              <div class="font-bold">
                上传分片中：{{ currentChunks + ' / ' + totalChunks }}（{{ currentProgress + '%' }}）
              </div>
              <div>当前文件名：{{ currentFile?.name }}</div>
            </div>
          </div>

          <div class="flex justify-end items-center">
            <n-button class="mr-10" type="primary" @click="getList">查询</n-button>
            <n-button type="primary" @click="triggerFileInput" :loading="loading">
              上传视频
            </n-button>
            <n-button v-if="currentFile" type="error" class="ml-10" @click="cancelUpload">
              取消上传
            </n-button>
          </div>
        </div>
      </div>

      <!-- 列表 -->
      <div class="mt-20">
        <n-data-table :columns="columns" :data="loadDataTable" :loading="tableLoading" />

        <p>
          共 {{ dataInfo.total }} 条数据, 共 {{ Math.ceil(dataInfo.total / dataInfo.pageSize) }} 页
        </p>
        <p mt-10 overflow-auto>
          <n-pagination
            v-model:page="dataInfo.pageNo"
            v-model:page-size="dataInfo.pageSize"
            :item-count="dataInfo.total"
            show-size-picker
            :page-sizes="[5, 10, 20]"
            @update:page="changePage"
            @update:page-size="changePageSize"
          />
        </p>
      </div>

      <!-- 视频模态框 -->
      <n-modal v-model:show="showModal">
        <n-card
          style="width: 800px; max-height: 80%; overflow-y: auto"
          :title="modalTitle"
          :bordered="false"
          size="huge"
          role="dialog"
          aria-modal="true"
        >
          <template #header-extra>
            <span cursor-pointer text-30 @click="closeModal">×</span>
          </template>

          <div v-if="videoUrl" class="w-full max-w-[400px] h-a ma">
            <VideoPlayer
              :src="videoUrl"
              :autoPlay="false"
              :controls="true"
              class="w-full h-[400px] max-w-full max-h-full object-contain"
            />
          </div>

          <template #footer>
            <n-button float-right @click="closeModal">关闭</n-button>
          </template>
        </n-card>
      </n-modal>
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
