<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de prueba para verificar configuración de Hostinger';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'guerson.rodriguez@gmail.com';

        $this->info("Enviando email de prueba a: {$email}");

        try {
            Mail::raw('¡Hola! Este es un email de prueba desde ToysandBricks. Si recibes este mensaje, tu configuración de Hostinger está funcionando correctamente. ✅', function ($message) use ($email) {
                $message->to($email)
                        ->subject('🧪 Email de Prueba - ToysandBricks');
            });

            $this->info('✅ Email enviado exitosamente!');
            $this->info('Revisa tu bandeja de entrada (y spam) en: ' . $email);

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el email:');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
