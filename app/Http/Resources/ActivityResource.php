<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->properties ?? collect();

        return [
            'id' => $this->id,
            'description' => $this->getDescriptiveMessage(),
            'event' => $this->event,
            'subject_type' => $this->getSubjectTypeName(),
            'subject_id' => $this->subject_id,
            'causer' => [
                'id' => $this->causer?->id,
                'name' => $this->causer?->name ?? 'System',
                'email' => $this->causer?->email ?? 'system@jaytechpos.com',
            ],
            'ip_address' => $properties->get('ip_address', 'N/A'),
            'user_agent' => $properties->get('user_agent', 'Unknown'),
            'device_info' => $this->parseDeviceInfo($properties->get('user_agent')),
            'properties' => $properties,
            'created_at' => $this->created_at?->format('M d, Y h:i A'),
            'created_at_diff' => $this->created_at?->diffForHumans(),
        ];
    }

    /**
     * Get a descriptive message for the activity
     */
    protected function getDescriptiveMessage(): string
    {
        if (! $this->subject_type) {
            return 'Unknown activity';
        }

        $className = class_basename($this->subject_type);
        $event = ucfirst($this->event);

        // Special handling for CustomerTransaction
        if ($className === 'CustomerTransaction') {
            $properties = $this->properties ?? collect();

            // Try to get transaction_type from attributes first, then from old
            $attributes = $properties->get('attributes', []);
            $old = $properties->get('old', []);

            $transactionType = $attributes['transaction_type'] ?? $old['transaction_type'] ?? null;

            if ($transactionType) {
                $formattedType = str_replace('_', ' ', $transactionType);

                return "{$event} {$formattedType} transaction";
            }

            return "{$event} transaction";
        }

        // Convert class name to lowercase with article
        $subjectName = strtolower($className);

        // Special cases for article usage
        $article = match ($subjectName) {
            'user' => 'a',  // "user" starts with consonant sound
            'expense' => 'an',
            default => in_array($subjectName[0], ['a', 'e', 'i', 'o']) ? 'an' : 'a',
        };

        return "{$event} {$article} {$subjectName}";
    }

    /**
     * Get a friendly name for the subject type
     */
    protected function getSubjectTypeName(): string
    {
        if (! $this->subject_type) {
            return 'Unknown';
        }

        $className = class_basename($this->subject_type);

        return match ($className) {
            'User' => 'User',
            'Product' => 'Product',
            'Customer' => 'Customer',
            'Sale' => 'Sale',
            'CustomerTransaction' => 'Transaction',
            'Expense' => 'Expense',
            'ExpenseCategory' => 'Expense Category',
            default => $className,
        };
    }

    /**
     * Parse device information from user agent
     */
    protected function parseDeviceInfo(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown Device';
        }

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            if (preg_match('/ipad/i', $userAgent)) {
                return 'iPad';
            }
            if (preg_match('/iphone/i', $userAgent)) {
                return 'iPhone';
            }
            if (preg_match('/android/i', $userAgent)) {
                return 'Android Device';
            }

            return 'Mobile Device';
        }

        // Detect browser
        if (preg_match('/chrome/i', $userAgent) && ! preg_match('/edge/i', $userAgent)) {
            return 'Chrome Browser';
        }
        if (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox Browser';
        }
        if (preg_match('/safari/i', $userAgent) && ! preg_match('/chrome/i', $userAgent)) {
            return 'Safari Browser';
        }
        if (preg_match('/edge/i', $userAgent)) {
            return 'Edge Browser';
        }

        return 'Desktop Browser';
    }
}
