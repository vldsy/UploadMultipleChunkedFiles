<template>
  <div>
    <input type="file" ref="fileInput" @change="handleFilesAdded" multiple />
    <div ref="dropzone" class="dropzone" @drop.prevent="handleDrop" @dragover.prevent @dragenter.prevent
      @dragleave.prevent @click="triggerFileInput">
      Drag and drop files here or click to select files
    </div>
    <div>
      <p>Upload Time: {{ uploadTime }} ms</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useDropzone } from 'vue3-dropzone';

const chunkSize = 1000000; // 1 MB
const uploadUrl = '/upload-parallel'; // Your upload URL

const fileInput = ref(null);
const dropzone = ref(null);
const uploadQueue = ref([]);
const activeUploads = ref(0);
const maxParallelUploads = 5; // Adjust this value based on your server capacity
const uploadTime = ref(0);

const processFiles = (files) => {
  startTime = performance.now(); // Start the timer when files are added
  for (let i = 0; i < files.length; i++) {
    processFile(files[i]);
  }
  // now uploadQueue has all data needed
  uploadNextChunks();
}

const handleFilesAdded = (event) => {
  const files = event.target.files;
  processFiles(files);
};

const handleDrop = (event) => {
  const files = event.dataTransfer.files;
  processFiles(files);
};

const triggerFileInput = () => {
  fileInput.value.click();
};

const processFile = (file) => {
  const totalChunks = Math.ceil(file.size / chunkSize);
  for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
    uploadQueue.value.push({ file, chunkIndex, totalChunks });
  }
};

const uploadNextChunks = () => {
  while (activeUploads.value < maxParallelUploads && uploadQueue.value.length > 0) {
    const { file, chunkIndex, totalChunks } = uploadQueue.value.shift();
    uploadChunk(file, chunkIndex, totalChunks);
  }

  if (uploadQueue.value.length === 0 && activeUploads.value === 0) {
    const endTime = performance.now();
    uploadTime.value = endTime - startTime;
  }
};

const uploadChunk = (file, chunkIndex, totalChunks) => {
  activeUploads.value++;
  const start = chunkIndex * chunkSize;
  const end = Math.min(start + chunkSize, file.size);
  const chunk = file.slice(start, end);

  const formData = new FormData();
  formData.append('file', chunk);
  formData.append('filename', file.name);
  formData.append('chunkIndex', chunkIndex);
  formData.append('totalChunks', totalChunks);

  {
    const endTime = performance.now();
    console.log("start chunk upload ", endTime - startTime)
  }

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
      {
        const endTime = performance.now();
        console.log("finish chunk upload ", endTime - startTime)
      }
    })
    .catch((error) => {
      console.error(`Error uploading chunk ${chunkIndex + 1}/${totalChunks}:`, error);
      // Optionally, re-add the chunk to the queue for retry
      uploadQueue.value.push({ file, chunkIndex, totalChunks });
    })
    .finally(() => {
      activeUploads.value--;
      setTimeout(uploadNextChunks, 0); // Ensure the next chunk is uploaded immediately
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

let startTime;

onMounted(() => {
  useDropzone(dropzone.value, {
    onFileAdded: handleFilesAdded,
    onSending: handleSending,
    onSuccess: handleSuccess,
    onError: handleError,
  });
});
</script>

<style>
.dropzone {
  border: 2px dashed #ccc;
  padding: 20px;
  text-align: center;
  cursor: pointer;
}
</style>
