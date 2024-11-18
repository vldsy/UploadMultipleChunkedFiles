<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        $chunkPath = $tempDir . '/' . $chunkIndex;
        $file->move($tempDir, $chunkIndex);

        // Verify the chunk was saved
        if (!file_exists($chunkPath)) {
            return response()->json(['status' => 'error', 'message' => 'Failed to save chunk'], 500);
        }

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
                if (!file_exists($chunkPath)) {
                    return response()->json(['status' => 'error', 'message' => 'Missing chunk ' . $i], 500);
                }
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



    public function uploadParallelOld(Request $request)
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
        $chunkPath = $tempDir . '/' . $chunkIndex;
        $file->move($tempDir, $chunkIndex);

        // Verify the chunk was saved
        if (!file_exists($chunkPath)) {
            return response()->json(['status' => 'error', 'message' => 'Failed to save chunk'], 500);
        }

        // Check if all chunks are uploaded
        $uploadedChunks = collect(scandir($tempDir))->filter(function ($file) {
            return is_numeric($file);
        })->map(function ($file) {
            return (int) $file;
        })->sort()->values();

        if ($uploadedChunks->count() == $totalChunks) {
            $finalDir = storage_path('app/uploads');
            if (!is_dir($finalDir)) {
                mkdir($finalDir, 0777, true);
            }

            $finalFilePath = $finalDir . '/' . $originalFilename;
            $finalFile = fopen($finalFilePath, 'wb');

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/' . $i;
                if (!file_exists($chunkPath)) {
                    return response()->json(['status' => 'error', 'message' => 'Missing chunk ' . $i], 500);
                }
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

    public function uploadParallel(Request $request)
    {
        try {
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
            $chunkPath = $tempDir . '/' . $chunkIndex;
            $file->move($tempDir, $chunkIndex);

            // Verify the chunk was saved
            if (!file_exists($chunkPath)) {
                Log::error("Failed to save chunk $chunkIndex for file $originalFilename");
                return response()->json(['status' => 'error', 'message' => 'Failed to save chunk'], 500);
            }

            // Check if all chunks are uploaded
            $uploadedChunks = collect(scandir($tempDir))->filter(function ($file) {
                return is_numeric($file);
            })->map(function ($file) {
                return (int) $file;
            })->sort()->values();

            if ($uploadedChunks->count() == $totalChunks) {
                $finalDir = storage_path('app/uploads');
                if (!is_dir($finalDir)) {
                    mkdir($finalDir, 0777, true);
                }

                $finalFilePath = $finalDir . '/' . $originalFilename;
                $finalFile = fopen($finalFilePath, 'wb');

                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkPath = $tempDir . '/' . $i;
                    if (!file_exists($chunkPath)) {
                        Log::error("Missing chunk $i for file $originalFilename");
                        return response()->json(['status' => 'error', 'message' => 'Missing chunk ' . $i], 500);
                    }
                    $chunk = fopen($chunkPath, 'rb');
                    stream_copy_to_stream($chunk, $finalFile);
                    fclose($chunk);
                    unlink($chunkPath);
                }

                fclose($finalFile);
                rmdir($tempDir);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error("Error uploading file: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }
}
