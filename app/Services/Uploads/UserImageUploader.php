<?php

namespace App\Services\Uploads;

class UserImageUploader extends CompanyImageUploader
{
    public function __construct(?string $disk = null)
    {
        parent::__construct($disk, 'users');
    }
}
