<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
////
import { ref, onMounted } from 'vue';
import { useDropzone } from 'vue3-dropzone';

const chunkSize = 1000000; // 1 MB
const uploadUrl = '/upload'; // Your upload URL

const dropzone = ref(null);

const handleFileAdded = (event) => {
  const file = event.target.files[0];
  console.log('File added:', file);
  processFile(file);
};

const processFile = (file) => {
  const totalChunks = Math.ceil(file.size / chunkSize);
  for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
    uploadChunk(file, chunkIndex, totalChunks);
  }
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
    })
    .catch((error) => {
      console.error(`Error uploading chunk ${chunkIndex + 1}/${totalChunks}:`, error);
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
    onFileAdded: handleFileAdded,
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

          <div> <input type="file" ref="fileInput" @change="handleFileAdded" /> </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
