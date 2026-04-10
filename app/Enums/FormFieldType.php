<?php

declare(strict_types=1);

namespace App\Enums;

enum FormFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Date = 'date';
    case Time = 'time';
    case File = 'file';
}
