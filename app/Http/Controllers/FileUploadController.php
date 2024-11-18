<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');
        $originalFilename = $request->input('filename');

        // Create a temporary directory to store chunks
        $tempDir = storage_path('app/tmp/' . $originalFilename);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Save the chunk
        $file->move($tempDir, $chunkIndex);

        // If all chunks are uploaded, merge them
        if ($chunkIndex + 1 == $totalChunks) {
            $finalDir = storage_path('app/uploads');
            if (!is_dir($finalDir)) {
                mkdir($finalDir, 0777, true);
            }

            $finalFilePath = $finalDir . '/' . $originalFilename;
            $finalFile = fopen($finalFilePath, 'wb');

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/' . $i;
                $chunk = fopen($chunkPath, 'rb');
                stream_copy_to_stream($chunk, $finalFile);
                fclose($chunk);
                unlink($chunkPath);
            }

            fclose($finalFile);
            rmdir($tempDir);
        }

        return response()->json(['status' => 'success']);
    }
}
