<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
////
import { ref, onMounted } from 'vue';
import { useDropzone } from 'vue3-dropzone';

const chunkSize = 1000000; // 1 MB
const uploadUrl = '/upload'; // Your upload URL

const dropzone = ref(null);

const fileInput = ref(null);
const uploadQueue = ref([]);
const isUploading = ref(false);

const handleFilesAdded = (event) => {
  const files = event.target.files;
  for (let i = 0; i < files.length; i++) {
    processFile(files[i]);
  }
};

const processFile = (file) => {
  const totalChunks = Math.ceil(file.size / chunkSize);
  for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
    uploadQueue.value.push({ file, chunkIndex, totalChunks });
  }
  if (!isUploading.value) {
    uploadNextChunk();
  }
};

const uploadNextChunk = () => {
  if (uploadQueue.value.length === 0) {
    isUploading.value = false;
    return;
  }

  isUploading.value = true;
  const { file, chunkIndex, totalChunks } = uploadQueue.value.shift();
  uploadChunk(file, chunkIndex, totalChunks);
};

const uploadChunk = (file, chunkIndex, totalChunks) => {
  const start = chunkIndex * chunkSize;
  const end = Math.min(start + chunkSize, file.size);
  const chunk = file.slice(start, end);

  const formData = new FormData();
  formData.append('file', chunk);
  formData.append('filename', file.name);
  formData.append('chunkIndex', chunkIndex);
  formData.append('totalChunks', totalChunks);

  fetch(uploadUrl, {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(`Chunk ${chunkIndex + 1}/${totalChunks} uploaded successfully.`);
      uploadNextChunk();
    })
    .catch((error) => {
      console.error(`Error uploading chunk ${chunkIndex + 1}/${totalChunks}:`, error);
      // Optionally, re-add the chunk to the queue for retry
      //uploadQueue.value.push({ file, chunkIndex, totalChunks });
      //uploadNextChunk();
    });
};

const handleSending = (file) => {
  console.log('Sending file:', file);
};

const handleSuccess = (file, response) => {
  console.log('File uploaded successfully:', response);
};

const handleError = (file, response) => {
  console.error('Error uploading file:', response);
};

onMounted(() => {
  useDropzone(dropzone.value, {
    onFileAdded: handleFilesAdded,
    onSending: handleSending,
    onSuccess: handleSuccess,
    onError: handleError,
  });
});
////
</script>

<template>

  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            You're logged in!
          </div>

          <div> <input type="file" ref="fileInput" @change="handleFilesAdded" multiple /> </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
