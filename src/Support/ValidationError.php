<?php

namespace Nuwave\Relay\Support;

use GraphQL\Error;
use Illuminate\Validation\Validator;

class ValidationError extends Error
{
    /**
     * The validator.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * ValidationError constructor.
     *
     * @param \Exception|string $message
     * @param Validator         $validator
     */
    public function __construct($message, Validator $validator)
    {
        parent::__construct($message);

        $this->validator = $validator;
    }

    /**
     * Get the messages from the validator.
     *
     * @return array
     */
    public function getValidatorMessages()
    {
        return $this->validator ? $this->validator->messages() : [];
    }
}
