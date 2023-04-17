<script lang="ts" setup>
  import { ref, onMounted, reactive, h } from 'vue';
  import { getFileList, toHttp } from '@/api/table/list';
  import { UploadFileInfo, DataTableColumns, NImage, NButton, NPopconfirm } from 'naive-ui';
  import { useUserStoreWidthOut } from '@/store/modules/user';

  const userStore = useUserStoreWidthOut();
  const token = userStore.getToken;
  // 列表数据
  const loadDataTable = ref<listType[]>([]);
  // 页码相关
  const dataInfo = reactive({
    total: 0,
    pageNo: 1,
    pageSize: 10,
  });
  // 表格类型
  type listType = {
    id: number;
    title: string;
    content: string;
    fileName: string;
    fileDate: null | number;
    fileUrl: string | null | undefined;
    createdAt: string;
  };
  // 模态框
  const showModal = ref(false);
  // 模态框标题
  const modalTitle = ref('');
  // 模态框类型
  const modalType = ref(1);
  // 模态框数据
  const modalData = reactive<listType>({
    id: 0,
    title: '',
    content: '',
    fileName: '',
    fileDate: null,
    fileUrl: '',
    createdAt: '',
  });
  // 搜索框数据
  const searchInfo = reactive({
    title: '',
    content: '',
    fileName: '',
  });
  // 图片信息
  const imgsList = ref<UploadFileInfo[]>([]);
  // 域名地址
  const domainAddress = 'http://175.178.236.223';

  // 表格字段
  const columns: DataTableColumns<listType> = [
    {
      title: '标题',
      key: 'title',
      width: 100,
    },
    {
      title: '内容',
      key: 'content',
      width: 100,
    },
    {
      title: '图片名称',
      key: 'fileName',
      width: 100,
    },
    {
      title: '图片',
      key: 'fileUrl',
      width: 100,
      render(row: listType) {
        return h(NImage, { width: 80, src: `${row.fileUrl}` });
      },
    },
    {
      title: '创建时间',
      key: 'createdAt',
      width: 100,
    },
    {
      title: '操作',
      key: 'operation',
      width: 100,
      render(row: listType) {
        return [
          h(
            NButton,
            {
              size: 'small',
              style: 'margin: 0 5px 5px 0;',
              onClick: () => openModal(2, row),
            },
            {
              default: () => '编辑',
            }
          ),
          h(
            NPopconfirm,
            {
              onPositiveClick: () => deleteData([row.id]),
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
                return '确认删除该图片？';
              },
            }
          ),
        ];
      },
    },
  ];

  // 获取列表数据
  const getList = async () => {
    const params = {
      pageIndex: dataInfo.pageNo,
      pageSize: dataInfo.pageSize,
      params: {
        title: searchInfo.title,
        content: searchInfo.content,
        fileName: searchInfo.fileName,
      },
    };
    await getFileList(params).then((res) => {
      loadDataTable.value = res.data.list || [];
      dataInfo.total = res.data.total;
    });
  };

  // 切换页码
  const changePage = (value: number) => {
    dataInfo.pageNo = value;
    getList();
  };

  // 切换每页显示数
  const changePageSize = (value: number) => {
    dataInfo.pageNo = 1;
    dataInfo.pageSize = value;
    getList();
  };

  // 图片上传前
  const beforeMaterialUpload = (data: { file: UploadFileInfo; fileList: UploadFileInfo[] }) => {
    const imageArray = ['image/png', 'image/jpeg'];
    if (data.file.file?.type) {
      if (!imageArray.includes(data.file.file.type)) {
        window['$message'].error('只能上传png或jpeg格式的图片文件，请重新上传！');
        return false;
      }
      if (data.file.file.size > 1024000 * 8) {
        window['$message'].error('只能上传8MB大小的图片文件，请重新上传！');
        return false;
      }
      return true;
    } else {
      window['$message'].error('文件后缀为空！请重新上传！');
      return false;
    }
  };

  // 拼接图片
  const handleFinish = ({ file, event }: { file: UploadFileInfo; event?: ProgressEvent }) => {
    const obj = JSON.parse((event?.target as XMLHttpRequest).response);
    file.name = `${obj.data || file.name}`;
    file.url = `${domainAddress}:8077/file/${obj.data}` || null;
    file.batchId = obj.data.split('.')[0] || null;
    // 给图片赋值
    modalData.fileUrl = file.url;
    return file;
  };

  // 图片删除事件
  const handleRemove = () => {
    modalData.fileUrl = '';
  };

  // 关闭模态框
  const closeModal = () => {
    showModal.value = false;
  };

  // 新增
  const save = async () => {
    if (!modalData.fileUrl) {
      window['$message'].error('请上传图片！');
      return false;
    }

    const url = '/h/insertFileListData';
    const type = 'POST';
    const params: object = {
      title: modalData.title,
      content: modalData.content,
      fileName: modalData.fileName,
      fileUrl: modalData.fileUrl,
      fileDate: modalData.fileDate,
    };

    await toHttp(url, type, params).then((res) => {
      if (res.code === 200) {
        window['$message'].success('新增成功');
        closeModal();
        getList();
      } else {
        window['$message'].error(res.message);
      }
    });
  };

  // 编辑
  const update = async () => {
    if (!modalData.fileUrl) {
      window['$message'].error('请上传图片！');
      return false;
    }

    const url = '/h/updateFileListData';
    const type = 'POST';
    const params: object = {
      id: modalData.id,
      title: modalData.title,
      content: modalData.content,
      fileName: modalData.fileName,
      fileUrl: modalData.fileUrl,
      fileDate: modalData.fileDate,
    };

    await toHttp(url, type, params).then((res) => {
      if (res.code === 200) {
        window['$message'].success('编辑成功');
        closeModal();
        getList();
      } else {
        window['$message'].error(res.message);
      }
    });
  };

  // 删除
  const deleteData = async (idList: Array<number>) => {
    if (idList.length < 1) {
      window['$message'].error('参数错误！删除失败！');
      return false;
    }

    const url = '/h/deleteFileListData';
    const type = 'POST';
    const params: object = {
      fileIdList: idList,
    };

    await toHttp(url, type, params).then((res) => {
      if (res.code === 200) {
        window['$message'].success('删除成功');
        getList();
      } else {
        window['$message'].error(res.message);
      }
    });
  };

  // 确认
  const confirm = () => {
    // 1新增、2编辑
    switch (modalType.value) {
      case 1:
        save();
        break;
      case 2:
        update();
        break;
    }
  };

  // 打开模态框
  const openModal = (type: number, row: any) => {
    // 初始化
    modalData.id = 0;
    modalData.title = '';
    modalData.content = '';
    modalData.fileName = '';
    modalData.fileDate = null;
    modalData.createdAt = '';
    modalData.fileUrl = '';
    imgsList.value = [];

    // 1新增、2编辑
    modalType.value = type;
    switch (type) {
      case 1:
        modalTitle.value = '新增';
        showModal.value = true;
        break;
      case 2:
        modalTitle.value = '编辑';
        modalData.id = row.id;
        modalData.title = row.title;
        modalData.content = row.content;
        modalData.fileName = row.fileName;
        modalData.fileDate = new Date(row.fileDate).getTime();
        modalData.fileUrl = row.fileUrl;
        imgsList.value = [
          {
            id: 'fileUrl',
            name: 'fileUrl.png',
            status: 'finished',
            url: row.fileUrl,
          },
        ];
        modalData.createdAt = row.createdAt;
        showModal.value = true;
        break;
    }
  };

  // 在所有组件加载完执行
  onMounted(() => {
    getList();
  });
</script>

<template>
  <div min-w-340 bg-white p-15>
    <div mt-10 mb-10>
      <n-grid item-responsive mb-8 x-gap="12" y-gap="12">
        <n-gi span="8">
          <div flex justify-between items-center>
            <span>标题：</span>
            <n-input
              v-model:value="searchInfo.title"
              style="width: 160px"
              placeholder=""
              clearable
              flex-1
            />
          </div>
        </n-gi>
        <n-gi span="8">
          <div flex justify-between items-center>
            <span>内容：</span>
            <n-input
              v-model:value="searchInfo.content"
              style="width: 160px"
              placeholder=""
              clearable
              flex-1
            />
          </div>
        </n-gi>
        <n-gi span="8">
          <div flex justify-between items-center>
            <span>图片名：</span>
            <n-input
              v-model:value="searchInfo.fileName"
              style="width: 160px"
              placeholder=""
              clearable
              flex-1
            />
          </div>
        </n-gi>
      </n-grid>
      <n-grid>
        <n-gi span="24">
          <div flex justify-end items-center>
            <n-button type="primary" m-r-8 @click="getList">查询</n-button>
            <n-button type="primary" m-r-8 @click="openModal(1, null)">新增</n-button>
          </div>
        </n-gi>
      </n-grid>
    </div>

    <n-data-table :columns="columns" :data="loadDataTable" />

    <p mt-8 flex justify-end>
      <n-pagination
        v-model:page="dataInfo.pageNo"
        v-model:page-size="dataInfo.pageSize"
        :item-count="dataInfo.total"
        show-size-picker
        :page-sizes="[10, 20, 30, 40]"
        show-quick-jumper
        @update:page="changePage"
        @update:page-size="changePageSize"
      >
        <template #prefix="{ pageCount }">
          共 {{ dataInfo.total }} 条数据, 共 {{ pageCount }} 页
        </template>
      </n-pagination>
    </p>

    <n-modal v-model:show="showModal">
      <n-card
        style="width: 800px; max-height: 650px; overflow-y: auto"
        :title="modalTitle"
        :bordered="false"
        size="huge"
        role="dialog"
        aria-modal="true"
      >
        <template #header-extra>
          <span cursor-pointer text-30 @click="closeModal">×</span>
        </template>
        <div mb-10 flex justify-between items-center>
          <span ml-5 class="modalTitle">标题：</span>
          <span flex-1>
            <n-input v-model:value="modalData.title" style="width: 80%" placeholder="请输入标题" />
          </span>
        </div>
        <div mb-10 flex justify-between items-center>
          <span ml-5 class="modalTitle">内容：</span>
          <span flex-1>
            <n-input
              v-model:value="modalData.content"
              style="width: 80%"
              placeholder="请输入内容"
            />
          </span>
        </div>
        <div mb-10 flex justify-between items-center>
          <span ml-5 class="modalTitle">图片名：</span>
          <span flex-1>
            <n-input
              v-model:value="modalData.fileName"
              style="width: 80%"
              placeholder="请输入图片名"
            />
          </span>
        </div>
        <div mb-10 flex justify-between items-center>
          <span ml-5 class="modalTitle">图片时间：</span>
          <span flex-1>
            <n-date-picker
              v-model:value="modalData.fileDate"
              style="width: 80%"
              type="date"
              clearable
            />
          </span>
        </div>
        <div mb-10>
          <span c-red>*</span>
          <span class="modalTitle">上传图片：</span>
          <span inline-block style="width: 45%">
            <n-upload
              :action="`${domainAddress}:8001/api/h/uploadFile`"
              :headers="{
                Authorization: `Bearer ${token}`,
              }"
              :default-file-list="imgsList"
              list-type="image-card"
              :max="1"
              @before-upload="beforeMaterialUpload"
              @update:file-list="(e:any) => imgsList = e"
              @finish="(e:any) => handleFinish(e)"
              @remove="handleRemove()"
            />
          </span>
        </div>
        <template #footer>
          <n-button type="success" @click="confirm">确定</n-button>
          <n-button float-right @click="closeModal">关闭</n-button>
        </template>
      </n-card>
    </n-modal>
  </div>
</template>

<style>
  .modalTitle {
    width: 100px;
    display: inline-block;
    padding-left: 2px;
  }
</style>
