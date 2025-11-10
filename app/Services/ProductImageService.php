<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProductImageService
{
    protected string $disk;

    public function __construct()
    {
        $this->disk = 'products';
    }

    /**
     * Guardar imagen de producto
     */
    public function saveImage(UploadedFile $file, int $productId): string
    {
        // Generar nombre único
        $filename = $productId . '_' . time() . '.' . $file->extension();
        
        // Guardar archivo
        Storage::disk($this->disk)->putFileAs('', $file, $filename);
        
        // Retornar URL completa construida manualmente
        return url('/storage/products/' . $filename);
    }

    /**
     * Eliminar imagen de producto
     */
    public function deleteImage(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        $filename = basename($url);
        return Storage::disk($this->disk)->delete($filename);
    }
}