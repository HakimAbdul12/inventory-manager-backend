<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;
use App\Services\OpenRouterClient;

class AINegotiatorService
{
    protected OpenRouterClient $client;

    public function __construct(OpenRouterClient $client)
    {
        $this->client = $client;
    }

    /**
     * AI Agent response logic for specific VIN.
     */
    public function chat(Vehicle $vehicle, string $message, string $leadSource = 'direct'): array
    {
        $context = $this->getContextForLeadSource($vehicle, $leadSource);

        $inventoryData = $vehicle->inventoryItem->generated_data ?? [];
        $vehicleInfo = json_encode([
            'year' => $vehicle->year,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'price' => $vehicle->price,
            'mileage' => $vehicle->mileage,
            'vin' => $vehicle->vin,
            'market_average' => $vehicle->market_average,
            'velocity_score' => $vehicle->velocity_score,
            'description' => $inventoryData['description'] ?? '',
        ], JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a professional, high-performance AI Sales Negotiator for a premium car dealership.
Your goal is to build rapport, answer questions accurately, and guide the customer toward a test drive.

## VEHICLE UNDER DISCUSSION
{$vehicleInfo}

## CUSTOMER CONTEXT
Lead Source: {$leadSource}
Context: {$context}

## CURRENT MESSAGE FROM CUSTOMER
"{$message}"

## INSTRUCTIONS
1. Be helpful, concise, and persuasive.
2. Use the "PFP" (Profit-First Pricing) logic: if the customer asks for a discount, explain that our prices are market-validated and dynamic (Velocity Score: {$vehicle->velocity_score}).
3. Reference specific vehicle details (mileage, features) to add value.
4. Always end with a soft call to action (e.g., test drive, inspection report, or trade-in valuation).
5. Stay in character. Do not mention you are an AI or LLM.

Return ONLY the response text.
PROMPT;

        try {
            $aiResponse = $this->client->prompt($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 300,
            ]);

            return [
                'response' => trim($aiResponse),
                'confidence_score' => $vehicle->inventoryItem->confidence_score ?? 100,
                'suggested_action' => 'Schedule Test Drive',
            ];
        } catch (\Exception $e) {
            Log::error('AI Negotiator Error: ' . $e->getMessage());

            // Fallback to semi-mocked but safe response
            return [
                'response' => "I'm currently looking up the latest market data for this {$vehicle->model}. While I do that, would you like to see our latest digital inspection report?",
                'confidence_score' => 50,
                'suggested_action' => 'View Report',
            ];
        }
    }

    protected function getContextForLeadSource(Vehicle $vehicle, string $source): string
    {
        return match ($source) {
            'towing', 'work' => "The customer is looking for performance and capability.",
            'family' => "The customer prioritizes safety and space.",
            'luxury' => "The customer expects premium features and exclusivity.",
            default => "The customer is generally interested in this vehicle.",
        };
    }
}
