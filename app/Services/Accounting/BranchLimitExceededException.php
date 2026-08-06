<?php

namespace App\Services\Accounting;

use RuntimeException;

/**
 * Thrown when a branch creation is blocked by the company's branch limit.
 *
 * The message is the SINGLE source of truth for the user-facing wording, and
 * payload() returns the structured error contract the frontend consumes
 * (error_code 'branch_limit_reached' triggers the upgrade prompt).
 */
class BranchLimitExceededException extends RuntimeException
{
    public const ERROR_CODE = 'branch_limit_reached';

    public function __construct(
        public readonly ?int $branchLimit,
        public readonly int $branchCount,
    ) {
        parent::__construct(
            sprintf(
                'Your company has reached its branch limit (%d/%s). Upgrade your plan to add more branches.',
                $this->branchCount,
                $this->branchLimit === null ? '∞' : $this->branchLimit
            )
        );
    }

    /**
     * The structured error response consumed by the frontend.
     */
    public function payload(): array
    {
        return [
            'success' => false,
            'error_code' => self::ERROR_CODE,
            'message' => $this->getMessage(),
            'branch_limit' => $this->branchLimit,
            'branch_count' => $this->branchCount,
        ];
    }
}
