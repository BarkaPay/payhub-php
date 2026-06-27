<?php

declare(strict_types=1);

namespace PayHub\Enum;

enum PaymentStatus: string
{
    case Created = 'CREATED';
    case AwaitingOtp = 'AWAITING_OTP';
    case QueuedForProcessing = 'QUEUED_FOR_PROCESSING';
    case ProcessingOperator = 'PROCESSING_OPERATOR';
    case ProcessingSystem = 'PROCESSING_SYSTEM';
    case Successful = 'SUCCESSFUL';
    case Failed = 'FAILED';
    case ComplianceFlagged = 'COMPLIANCE_FLAGGED';
    case Canceled = 'CANCELED';

    /** No further transitions: SUCCESSFUL, FAILED or CANCELED. */
    public function isFinal(): bool
    {
        return \in_array($this, [self::Successful, self::Failed, self::Canceled], true);
    }
}
