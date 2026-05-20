<?php

namespace App\DTOs;

class NotificationData
{
    public function __construct(
        public string $title,
        public ?string $body = null,
        public ?string $category = null,
        public ?string $actionUrl = null,
        public ?int $senderId = null,
        public ?string $tenantId = null,
        public ?string $type = null,
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?array $metadata = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            body: $data['body'] ?? null,
            category: $data['category'] ?? null,
            actionUrl: $data['actionUrl'] ?? null,
            senderId: $data['senderId'] ?? null,
            tenantId: $data['tenantId'] ?? null,
            type: $data['type'] ?? null,
            subjectType: $data['subjectType'] ?? null,
            subjectId: $data['subjectId'] ?? null,
            metadata: $data['metadata'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'sender_id' => $this->senderId,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'category' => $this->category,
            'data' => $this->metadata,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
        ];
    }
}
