<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProductImageService
{
    protected string $disk;

    public function __construct()
    {
        // Usa el disk configurado en FILESYSTEM_DISK (automático desde Laravel Cloud)
        // En local: 'products' o 'local'
        // En cloud: 's3' (inyectado por Laravel Cloud cuando se adjunta un bucket)
        $this->disk = config('filesystems.default');
    }

    /**
     * Guardar imagen de producto
     */
    public function saveImage(UploadedFile $file, int $productId): string
    {
        // Generar nombre único
        $filename = $productId . '_' . time() . '.' . $file->extension();

        // Guardar archivo en el path 'products/'
        Storage::disk($this->disk)->putFileAs('products', $file, $filename);

        // Retornar URL según el tipo de disk
        return $this->getFileUrl($filename);
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
        return Storage::disk($this->disk)->delete('products/' . $filename);
    }

    /**
     * Obtener URL del archivo según el disk configurado
     */
    protected function getFileUrl(string $filename): string
    {
        // Si es S3 (Laravel Cloud), usar la URL pública del bucket
        if ($this->disk === 's3') {
            return Storage::disk('s3')->url('products/' . $filename);
        }

        // Si es local o products, usar la URL local
        return url('/storage/products/' . $filename);
    }
}