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
        $properties = $this->properties ?? collect();

        if ($properties->has('total_customers') && $properties->has('processed')) {
            return sprintf(
                'Monthly Interest Processed. Total customers considered is %s and interest transactions created is %s',
                $properties->get('total_customers'),
                $properties->get('processed')
            );
        }

        if (! $this->subject_type) {
            return 'Unknown activity';
        }

        $className = class_basename($this->subject_type);
        $event = ucfirst($this->event);

        if ($className === 'CustomerTransaction') {
            $transactionType = $properties->get('attributes.transaction_type')
                ?? $properties->get('old.transaction_type');

            if ($transactionType) {
                return "{$event} ".str_replace('_', ' ', $transactionType).' transaction';
            }

            return "{$event} transaction";
        }

        $subjectName = strtolower($className);
        $article = $this->getArticle($subjectName);

        return "{$event} {$article} {$subjectName}";
    }

    /**
     * Get the appropriate article (a/an) for a word
     */
    private function getArticle(string $word): string
    {
        return in_array($word[0], ['a', 'e', 'i', 'o']) ? 'an' : 'a';
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

        $patterns = [
            '/ipad/i' => 'iPad',
            '/iphone/i' => 'iPhone',
            '/android/i' => 'Android Device',
            '/mobile|phone/i' => 'Mobile Device',
        ];

        foreach ($patterns as $pattern => $device) {
            if (preg_match($pattern, $userAgent)) {
                return $device;
            }
        }

        $browsers = [
            '/edge/i' => 'Edge Browser',
            '/chrome/i' => 'Chrome Browser',
            '/firefox/i' => 'Firefox Browser',
            '/safari/i' => 'Safari Browser',
        ];

        foreach ($browsers as $pattern => $browser) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Desktop Browser';
    }
}
