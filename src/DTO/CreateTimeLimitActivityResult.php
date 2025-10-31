<?php

namespace PromotionEngineBundle\DTO;

readonly class CreateTimeLimitActivityResult implements \JsonSerializable
{
    public function __construct(
        public string $activityId,
        public bool $success = true,
        public ?string $message = null,
    ) {
    }

    public static function success(string $activityId): self
    {
        return new self($activityId, true, '活动创建成功');
    }

    public static function failure(string $message): self
    {
        return new self('', false, $message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'activityId' => $this->activityId,
            'message' => $this->message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
