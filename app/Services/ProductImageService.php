<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProductImageService
{
    protected string $disk;

    public function __construct()
    {
        // Determinar el disk a usar según el entorno
        // En local (FILESYSTEM_DISK=local): usar disk 'products' para imágenes públicas
        // En staging/prod (FILESYSTEM_DISK=s3): usar disk 's3' para Laravel Cloud Object Storage

        $defaultDisk = config('filesystems.default');

        // Si el disk por defecto es 'local' o 'public', usar 'products' para imágenes
        // Esto permite que funcione en desarrollo local
        if (in_array($defaultDisk, ['local', 'public'])) {
            $this->disk = 'products';
        } else {
            // En staging/producción con S3
            $this->disk = $defaultDisk;
        }
    }

    /**
     * Guardar imagen de producto
     */
    public function saveImage(UploadedFile $file, int $productId): string
    {
        // Generar nombre único
        $filename = $productId . '_' . time() . '.' . $file->extension();

        // Guardar archivo según el disk
        if ($this->disk === 'products') {
            // Disk 'products': guarda en storage/app/public/products (raíz del disk)
            Storage::disk($this->disk)->putFileAs('', $file, $filename);
        } else {
            // Disk 's3': guarda en el path 'products/' dentro del bucket
            Storage::disk($this->disk)->putFileAs('products', $file, $filename);
        }

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

        // Eliminar archivo según el disk
        if ($this->disk === 'products') {
            // Disk 'products': archivo en la raíz del disk
            return Storage::disk($this->disk)->delete($filename);
        } else {
            // Disk 's3': archivo en 'products/'
            return Storage::disk($this->disk)->delete('products/' . $filename);
        }
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

        // Si es local (disk 'products'), usar la URL local
        return url('/storage/products/' . $filename);
    }
}
