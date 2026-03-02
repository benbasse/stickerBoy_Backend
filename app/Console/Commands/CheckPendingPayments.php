<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\NabooPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-pending {--hours=24 : Vérifier les commandes des X dernières heures}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les paiements en attente auprès de NabooPay et met à jour les statuts';

    /**
     * Execute the console command.
     */
    public function handle(NabooPayService $nabooPayService)
    {
        $hours = $this->option('hours');

        $pendingOrders = Order::where('payment_provider', 'naboopay')
            ->whereIn('payment_status', ['unpaid', 'pending'])
            ->whereNotNull('payment_reference')
            ->where('created_at', '>', now()->subHours($hours))
            ->get();

        $this->info("Vérification de {$pendingOrders->count()} commande(s) en attente...");

        $synced = 0;
        $failed = 0;
        $stillPending = 0;

        foreach ($pendingOrders as $order) {
            try {
                $response = $nabooPayService->getTransactionStatus($order->payment_reference);
                $status = $response['transaction_status'] ?? $response['status'] ?? null;

                $this->line("Commande #{$order->id} - NabooPay status: {$status}");

                if (in_array($status, ['paid', 'successful', 'completed', 'success'])) {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'paid_at' => now(),
                    ]);

                    Log::info('CheckPendingPayments: Order synced', [
                        'order_id' => $order->id,
                        'payment_reference' => $order->payment_reference,
                    ]);

                    $synced++;
                    $this->info("  -> Paiement confirmé!");

                } elseif (in_array($status, ['failed', 'cancelled', 'expired', 'refunded'])) {
                    $order->update([
                        'payment_status' => 'failed',
                    ]);

                    $failed++;
                    $this->warn("  -> Paiement échoué: {$status}");

                } else {
                    $stillPending++;
                    $this->comment("  -> Toujours en attente");
                }

            } catch (\Exception $e) {
                Log::error('CheckPendingPayments: Error checking order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  -> Erreur: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Résumé:");
        $this->line("  - Payées: {$synced}");
        $this->line("  - Échouées: {$failed}");
        $this->line("  - En attente: {$stillPending}");

        return Command::SUCCESS;
    }
}
