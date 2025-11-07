<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProductImageService
{
    protected string $disk;

    public function __construct()
    {
        // Usa el disk 'products' configurado
        $this->disk = 'products';
    }

    /**
     * Guardar imagen de producto
     *
     * @param UploadedFile $file
     * @param int $productId
     * @return string URL completa de la imagen
     */
    public function saveImage(UploadedFile $file, int $productId): string
    {
        // Generar nombre único: productId_timestamp.extension
        $filename = $productId . '_' . time() . '.' . $file->extension();
        
        // Guardar en storage/app/public/products/
        $path = Storage::disk($this->disk)->putFileAs(
            '',
            $file,
            $filename
        );
        
        // Retornar URL completa
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Eliminar imagen de producto
     *
     * @param string|null $url
     * @return bool
     */
    public function deleteImage(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        $path = $this->extractPathFromUrl($url);
        
        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Extraer path relativo de URL completa
     *
     * @param string $url
     * @return string
     */
    private function extractPathFromUrl(string $url): string
    {
        $baseUrl = Storage::disk($this->disk)->url('');
        return str_replace($baseUrl . '/', '', $url);
    }
}