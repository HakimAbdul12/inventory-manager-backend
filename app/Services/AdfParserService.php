<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class AdfParserService
{
    /**
     * Parses an ADF (Auto Lead Data Format) XML string and extracts lead information.
     * Extracts name, email, phone, notes, and vehicle details.
     * 
     * @param string $xmlContent The raw ADF XML string.
     * @return array|null An array of extracted lead data, or null on failure.
     */
    public function parse(string $xmlContent): ?array
    {
        try {
            // ADF is an XML format, we suppress errors and load it cleanly
            $previousValue = libxml_use_internal_errors(true);
            $xml = simplexml_load_string(trim($xmlContent));
            libxml_use_internal_errors($previousValue);

            if ($xml === false || !isset($xml->prospect)) {
                Log::warning('AdfParserService: Failed to parse valid ADF XML or <prospect> tag is missing.', [
                    'content' => substr($xmlContent, 0, 100) . '...' // Log portion of content
                ]);
                return null;
            }

            $prospect = $xml->prospect;

            // Extract customer details
            $name = '';
            $email = null;
            $phone = null;
            $notes = null;

            if (isset($prospect->customer)) {
                $customer = $prospect->customer;

                if (isset($customer->contact)) {
                    $contact = $customer->contact;

                    // ADF name format: <name part="full">Full Name</name>
                    // Or <name part="first">First</name> <name part="last">Last</name>
                    if (isset($contact->name)) {
                        $nameParts = [];
                        foreach ($contact->name as $n) {
                            $nameParts[] = (string)$n;
                        }
                        $name = trim(implode(' ', $nameParts));
                    }

                    // Extract email
                    if (isset($contact->email)) {
                        $email = (string)$contact->email;
                    }

                    // Extract phone
                    if (isset($contact->phone)) {
                        $phone = (string)$contact->phone;
                    }
                }
            }

            // Extract vehicle interest details (if available) to be saved as notes or to match InventoryItem later
            $vehicleInterest = '';
            $vehicleDetails = null;

            if (isset($prospect->vehicle)) {
                $v = $prospect->vehicle;

                // Convert simplexml object to associative array
                $vehicleDetails = json_decode(json_encode($v), true);

                $year = isset($v->year) ? (string)$v->year : '';
                $make = isset($v->make) ? (string)$v->make : '';
                $model = isset($v->model) ? (string)$v->model : '';
                $trim = isset($v->trim) ? (string)$v->trim : '';

                $vehicleParts = array_filter([$year, $make, $model, $trim]);
                if (!empty($vehicleParts)) {
                    $vehicleInterest = implode(' ', $vehicleParts);
                }
            }

            // Extract Provider info
            $providerName = null;
            if (isset($prospect->provider) && isset($prospect->provider->name)) {
                // ADF Name part="full" or just a string
                $providerNameParts = [];
                foreach ($prospect->provider->name as $n) {
                    $providerNameParts[] = (string)$n;
                }
                $providerName = trim(implode(' ', $providerNameParts));
            }

            // Extract notes/comments
            if (isset($prospect->customer->comments)) {
                $notes = (string)$prospect->customer->comments;
            }

            // Combine vehicle interest into notes if notes is empty
            if ($vehicleInterest) {
                $notes = $notes ? "Interested in: $vehicleInterest\n\n$notes" : "Interested in: $vehicleInterest";
            }

            return [
                'name' => $name ?: 'Unknown Lead',
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
                'provider_name' => $providerName,
                'vehicle_details' => $vehicleDetails,
            ];
        } catch (Exception $e) {
            Log::error('AdfParserService: Exception while parsing ADF', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
