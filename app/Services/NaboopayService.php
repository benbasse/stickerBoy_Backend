<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NabooPayService
{
    protected $apiUrl;
    protected $token;

    // Méthodes de paiement disponibles
    public const PAYMENT_ORANGE_MONEY = 'orange_money';
    public const PAYMENT_WAVE = 'wave';
    public const PAYMENT_YAS = 'yas';
    public const PAYMENT_BANK = 'bank';
    public const PAYMENT_VISA = 'visa';
    public const PAYMENT_MASTER_CARD = 'master_card';

    public function __construct()
    {
        $this->apiUrl = config('services.naboopay.url', 'https://api.dev.naboopay.com/api/v2');
        $this->token = config('services.naboopay.token');
    }

    /**
     * Créer une transaction de paiement (API v2)
     *
     * @param array $products Liste des produits [['name' => '', 'price' => 0, 'quantity' => 1, 'description' => '']]
     * @param array $paymentMethods Méthodes de paiement acceptées ['orange_money', 'wave', etc.]
     * @param string|null $successUrl URL de redirection en cas de succès
     * @param string|null $errorUrl URL de redirection en cas d'erreur
     * @param bool $feesCustomerSide Si true, les frais sont ajoutés au total client
     * @param bool $isEscrow Si true, les fonds sont retenus jusqu'à libération
     * @param bool $isMerchant Si true, applique la structure de frais marchand
     * @return array
     */
    public function createTransaction(
        array $products,
        array $paymentMethods = [self::PAYMENT_ORANGE_MONEY, self::PAYMENT_WAVE],
        ?string $successUrl = null,
        ?string $errorUrl = null,
        bool $feesCustomerSide = false,
        bool $isEscrow = false,
        bool $isMerchant = false
    ) {
        $payload = [
            'method_of_payment' => $paymentMethods,
            'products' => $products,
            'fees_customer_side' => $feesCustomerSide,
            'is_escrow' => $isEscrow,
            'is_merchant' => $isMerchant,
        ];

        if ($successUrl) {
            $payload['success_url'] = $successUrl;
        }

        if ($errorUrl) {
            $payload['error_url'] = $errorUrl;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->apiUrl}/transactions", $payload);

        return $response->json();
    }

    /**
     * Effectuer un payout (transfert direct vers un numéro) - API v2
     *
     * @param string $paymentMethod Méthode de paiement (orange_money, wave, etc.)
     * @param int $amount Montant en XOF (entre 11 et 1,500,000)
     * @param string $phone Numéro de téléphone du destinataire (format international: +221771234567)
     * @param string $firstName Prénom du destinataire
     * @param string $lastName Nom du destinataire
     * @param string|null $reason Raison du transfert
     * @return array
     */
    public function payout(
        string $paymentMethod,
        int $amount,
        string $phone,
        string $firstName,
        string $lastName,
        ?string $reason = null
    ) {
        $payload = [
            'selected_payment_method' => $paymentMethod,
            'amount' => $amount,
            'recipient' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ],
        ];

        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->apiUrl}/payouts", $payload);

        return $response->json();
    }

    /**
     * Payout via Orange Money
     */
    public function payoutOrangeMoney(int $amount, string $phone, string $firstName, string $lastName, ?string $reason = null)
    {
        return $this->payout(self::PAYMENT_ORANGE_MONEY, $amount, $phone, $firstName, $lastName, $reason);
    }

    /**
     * Payout via Wave
     */
    public function payoutWave(int $amount, string $phone, string $firstName, string $lastName, ?string $reason = null)
    {
        return $this->payout(self::PAYMENT_WAVE, $amount, $phone, $firstName, $lastName, $reason);
    }

    /**
     * Créer une transaction simple avec un seul produit
     */
    public function createSimpleTransaction(
        string $productName,
        int $price,
        int $quantity = 1,
        array $paymentMethods = [self::PAYMENT_ORANGE_MONEY, self::PAYMENT_WAVE],
        ?string $successUrl = null,
        ?string $errorUrl = null
    ) {
        $products = [
            [
                'name' => $productName,
                'price' => $price,
                'quantity' => $quantity,
            ]
        ];

        return $this->createTransaction($products, $paymentMethods, $successUrl, $errorUrl);
    }

    /**
     * Transférer vers le compte principal (numéro configuré dans .env)
     *
     * @param string $paymentMethod Méthode de paiement (orange_money, wave, etc.)
     * @param int $amount Montant en XOF
     * @param string|null $reason Raison du transfert
     * @return array
     */
    public function transferToMainAccount(
        string $paymentMethod,
        int $amount,
        ?string $reason = null
    ) {
        $phone = config('services.naboopay.recipient_phone');
        $firstName = config('services.naboopay.recipient_first_name');
        $lastName = config('services.naboopay.recipient_last_name');

        return $this->payout($paymentMethod, $amount, $phone, $firstName, $lastName, $reason);
    }

    /**
     * Transférer vers le compte principal via Wave
     */
    public function transferToMainAccountWave(int $amount, ?string $reason = null)
    {
        return $this->transferToMainAccount(self::PAYMENT_WAVE, $amount, $reason);
    }

    /**
     * Transférer vers le compte principal via Orange Money
     */
    public function transferToMainAccountOrangeMoney(int $amount, ?string $reason = null)
    {
        return $this->transferToMainAccount(self::PAYMENT_ORANGE_MONEY, $amount, $reason);
    }

    /**
     * Vérifier le statut d'une transaction auprès de NabooPay
     *
     * @param string $orderId L'ID de la transaction NabooPay
     * @return array
     */
    public function getTransactionStatus(string $orderId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->get("{$this->apiUrl}/transactions/{$orderId}");

        return $response->json();
    }

    /**
     * Vérifier si une transaction est payée
     *
     * @param string $orderId L'ID de la transaction NabooPay
     * @return bool
     */
    public function isTransactionPaid(string $orderId): bool
    {
        $response = $this->getTransactionStatus($orderId);
        $status = $response['transaction_status'] ?? null;

        return in_array($status, ['successful', 'completed', 'paid', 'success']);
    }

    /**
     * Récupérer toutes les transactions depuis NabooPay
     *
     * @param array $filters Filtres optionnels
     * @return array
     */
    public function getAllTransactions(array $filters = [])
    {
        $queryParams = array_filter([
            'page' => $filters['page'] ?? 1,
            'limit' => $filters['limit'] ?? 20,
            'status' => $filters['status'] ?? null,
            'payment_method' => $filters['payment_method'] ?? null,
            'is_escrow' => $filters['is_escrow'] ?? null,
            'is_merchant' => $filters['is_merchant'] ?? null,
            'include_deleted' => $filters['include_deleted'] ?? 'false',
            'customer_phone' => $filters['customer_phone'] ?? null,
            'min_amount' => $filters['min_amount'] ?? null,
            'max_amount' => $filters['max_amount'] ?? null,
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
            'search' => $filters['search'] ?? null,
        ], fn($value) => $value !== null);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->get("{$this->apiUrl}/transactions", $queryParams);

        return $response->json();
    }

    /**
     * Récupérer les transactions payées
     */
    public function getPaidTransactions(int $page = 1, int $limit = 20)
    {
        return $this->getAllTransactions([
            'page' => $page,
            'limit' => $limit,
            'status' => 'paid',
        ]);
    }

    /**
     * Récupérer les transactions en attente
     */
    public function getPendingTransactions(int $page = 1, int $limit = 20)
    {
        return $this->getAllTransactions([
            'page' => $page,
            'limit' => $limit,
            'status' => 'pending',
        ]);
    }
}
