<?php

declare(strict_types=1);

namespace PayHub\Enum;

enum TransferStatus: string
{
    case Created = 'CREATED';
    case ProcessingOperator = 'PROCESSING_OPERATOR';
    case ProcessingSystem = 'PROCESSING_SYSTEM';
    case Successful = 'SUCCESSFUL';
    case Failed = 'FAILED';
    case ComplianceFlagged = 'COMPLIANCE_FLAGGED';
    case Refunded = 'REFUNDED';
    case Unresolved = 'UNRESOLVED';

    /** No further transitions: SUCCESSFUL, FAILED or REFUNDED. */
    public function isFinal(): bool
    {
        return \in_array($this, [self::Successful, self::Failed, self::Refunded], true);
    }
}
