<?php

namespace App\Exceptions;

use Exception;

class DeviceNotInBranchException extends Exception
{
    /** @var array{device_id: string, imei: string, host_branch_id: string, host_branch_name: string} */
    protected array $context;

    /**
     * @param array{device_id: string, imei: string, host_branch_id: string, host_branch_name: string} $context
     */
    public function __construct(string $message, array $context)
    {
        parent::__construct($message);
        $this->context = $context;
    }

    /** @return array{device_id: string, imei: string, host_branch_id: string, host_branch_name: string} */
    public function getContext(): array
    {
        return $this->context;
    }
}
