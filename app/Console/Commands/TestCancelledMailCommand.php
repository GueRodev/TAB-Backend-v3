<?php

namespace App\Console\Commands;

use App\Mail\OrderCancelledMail;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestCancelledMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-cancelled {email?} {--order-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de cancelación de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'guerson.rodriguez@gmail.com';
        $orderId = $this->option('order-id');

        // Si se especifica un order-id, usar ese pedido
        if ($orderId) {
            $order = Order::with(['items', 'shippingAddress'])->find($orderId);

            if (!$order) {
                $this->error("No se encontró el pedido con ID: {$orderId}");
                return 1;
            }
        } else {
            // Si no, usar el último pedido disponible
            $order = Order::with(['items', 'shippingAddress'])->latest()->first();

            if (!$order) {
                $this->error('No hay pedidos disponibles para probar');
                return 1;
            }
        }

        $this->info("Enviando email de cancelación del pedido #{$order->order_number} a: {$email}");

        try {
            Mail::to($email)->send(new OrderCancelledMail($order));

            $this->info('✅ Email de cancelación enviado exitosamente!');
            $this->info("Pedido: {$order->order_number}");
            $this->info("Total: ₡" . number_format($order->total, 2));
            $this->info('Revisa tu bandeja de entrada (y spam) en: ' . $email);

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el email:');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
